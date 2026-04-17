<?php
/**
 * 跳转管理 — 列表页（含新增/编辑/删除，全部在本文件处理）
 * @copyright 2026 wenyinos <ruojiner@hotmail.com>
 * @license MIT License
 */
$config_file = dirname(dirname(__DIR__)) . '/config/config.php';
if (!file_exists($config_file)) {
    header('Location: /install/install.php');
    exit;
}
require_once $config_file;
admin_auth();

$db = get_db();

$templates = get_templates();

// ── GET 操作：删除 / 切换状态 / 清空统计 ───────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    session_start_safe();
    $token = $_GET['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403); exit('CSRF verification failed.');
    }
    $aid = (int)($_GET['id'] ?? 0);
    if ($aid) {
        try {
            if ($_GET['action'] === 'delete') {
                // 权限校验：非 super 只能删自己的
                [$dw,$dp] = domain_owner_where();
                $chk = get_db()->prepare("SELECT id FROM `domains` WHERE `id`=? AND {$dw}");
                $chk->execute(array_merge([$aid], $dp));
                if ($chk->fetch()) {
                    get_db()->prepare('DELETE FROM `domains` WHERE `id`=?')->execute([$aid]);
                    write_admin_log("删除域名 id={$aid}");
                }
                header('Location: /admin/domains/index.php?msg=deleted'); exit;
            } elseif ($_GET['action'] === 'toggle') {
                [$dw,$dp] = domain_owner_where();
                $st = get_db()->prepare("SELECT `status` FROM `domains` WHERE `id`=? AND {$dw}");
                $st->execute(array_merge([$aid], $dp));
                $cur = $st->fetch();
                if ($cur) {
                    $new = $cur['status'] === 'active' ? 'paused' : 'active';
                    get_db()->prepare('UPDATE `domains` SET `status`=? WHERE `id`=?')->execute([$new, $aid]);
                    write_admin_log("切换域名状态 id={$aid} status={$new}");
                }
                header('Location: /admin/domains/index.php?msg=toggled'); exit;
            } elseif ($_GET['action'] === 'clear_stats') {
                role_guard('super');
                get_db()->prepare("DELETE FROM `access_logs` WHERE `domain_id`=?")->execute([$aid]);
                write_admin_log("清空域名访问数据 id={$aid}");
                header('Location: /admin/domains/index.php?msg=cleared'); exit;
            }
        } catch (Throwable $e) {
            error_log('Domain action error: ' . $e->getMessage());
        }
    }
    header('Location: /admin/domains/index.php'); exit;
}

// ── AJAX 保存（新增/编辑）────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_ajax'])) {
    header('Content-Type: application/json; charset=utf-8');

    // CSRF
    session_start_safe();
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        echo json_encode(['ok' => false, 'error' => 'CSRF验证失败，请刷新页面']);
        exit;
    }

    $id      = (int)($_POST['id'] ?? 0);
    $name    = trim($_POST['name'] ?? '');
    $domain  = strtolower(trim($_POST['domain'] ?? ''));
    $proto   = ($_POST['protocol'] ?? '') === 'http' ? 'http' : 'https';
    $tpl     = $_POST['template'] ?? 'img';
    $url     = trim($_POST['target_url'] ?? '');
    $status  = ($_POST['status'] ?? '') === 'paused' ? 'paused' : 'active';
    $delay   = max(1, min(60, (int)($_POST['delay'] ?? 3)));
    $img     = trim($_POST['img_url'] ?? '');
    $title   = trim($_POST['site_title'] ?? '');
    $desc    = trim($_POST['site_description'] ?? '');
    $is_show_link = (int)($_POST['is_show_link'] ?? 1);
    $remarks = trim($_POST['remarks'] ?? '');
    $sort_order = (int)($_POST['sort_order'] ?? 0);

    // 处理文件上传
    if (isset($_FILES['img_file']) && $_FILES['img_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['img_file'];
        $allowed_ext = ['jpg','jpeg','png','gif','webp'];
        $max_size = 5 * 1024 * 1024;
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed_ext) && $file['size'] <= $max_size) {
            $upload_dir = ROOT_PATH . '/uploads/gallery';
            if (!is_dir($upload_dir)) @mkdir($upload_dir, 0755, true);
            $new_name = date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $upload_dir . '/' . $new_name)) {
                $img = '/uploads/gallery/' . $new_name;
                // 记录到媒体库
                try {
                    $db->prepare("INSERT INTO `media` (`filename`,`url`,`lib`,`owner_id`) VALUES (?,?,'gallery',?)")
                       ->execute([$new_name, $img, current_uid()]);
                } catch (Throwable $e) {}
            }
        }
    }

    $template_fields = $templates[$tpl]['fields'] ?? [];
    $is_nav_template = in_array('nav', $template_fields, true);
    $needs_url = in_array('url', $template_fields, true) || $is_nav_template;
    $needs_img = in_array('img', $template_fields, true);
    $has_blackgold_meta = in_array('blackgold', $template_fields, true);

    $errors = [];
    if (!$domain) $errors[] = '域名不能为空';
    if (!array_key_exists($tpl, $templates)) $errors[] = '无效的模板';
    if ($needs_url && !$url) $errors[] = $is_nav_template ? '请至少添加一个跳转链接' : '目标URL不能为空';
    if (!$is_nav_template && $url && !is_valid_url($url)) $errors[] = '目标URL必须是有效的http或https地址';
    if ($needs_img && $img !== '' && !is_valid_asset_url($img)) $errors[] = '图片地址必须是有效的 http/https 地址或站内路径';
    if (strlen($domain) > 255) $errors[] = '域名过长';
    $max_target_length = $is_nav_template ? 12000 : 2048;
    if (strlen($url) > $max_target_length) $errors[] = $is_nav_template ? '导航配置内容过长' : '目标URL过长';
    if (strlen($img) > 2048) $errors[] = '图片地址过长';
    if (strlen($desc) > 500) $errors[] = '描述过长';

    if ($is_nav_template && $url !== '') {
        $nav_payload = decode_nav_payload($url);
        $nav_links = normalize_nav_links($url, 10);
        if (empty($nav_links)) {
            $errors[] = '导航模板至少需要一个有效的链接，且链接必须以 http 或 https 开头';
        } else {
            if ($has_blackgold_meta) {
                $url = json_encode([
                    'links' => $nav_links,
                    'meta' => sanitize_blackgold_meta(is_array($nav_payload['meta'] ?? null) ? $nav_payload['meta'] : []),
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } else {
                $url = json_encode($nav_links, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }
    }

    if ($errors) {
        echo json_encode(['ok' => false, 'error' => implode('；', $errors)]);
        exit;
    }

    try {
        if ($id) {
            // 编辑：校验是否有权限操作该域名
            [$dw,$dp] = domain_owner_where();
            $own = $db->prepare("SELECT id FROM `domains` WHERE `id`=? AND {$dw}");
            $own->execute(array_merge([$id], $dp));
            if (!$own->fetch()) {
                echo json_encode(['ok'=>false,'error'=>'无权限操作该域名']); exit;
            }
            $chk = $db->prepare("SELECT id FROM `domains` WHERE `domain`=? AND `protocol`=? AND `id`!=?");
            $chk->execute([$domain, $proto, $id]);
            if ($chk->fetch()) {
                echo json_encode(['ok' => false, 'error' => '该域名+协议组合已被其他记录占用']);
                exit;
            }
            $db->prepare(
                "UPDATE `domains` SET `name`=?,`domain`=?,`protocol`=?,`target_url`=?,`template`=?,`status`=?,
                 `delay`=?,`img_url`=?,`site_title`=?,`site_description`=?,`is_show_link`=?,`remarks`=?,`sort_order`=? WHERE `id`=?"
            )->execute([$name,$domain,$proto,$url,$tpl,$status,$delay,$img?:null,$title?:null,$desc?:null,$is_show_link,$remarks?:null,$sort_order,$id]);
            write_admin_log("编辑域名 id={$id} domain={$domain} protocol={$proto}");
            echo json_encode(['ok' => true, 'msg' => 'updated']);
        } else {
            // 新增
            $chk = $db->prepare("SELECT id FROM `domains` WHERE `domain`=? AND `protocol`=?");
            $chk->execute([$domain, $proto]);
            if ($chk->fetch()) {
                echo json_encode(['ok' => false, 'error' => '该域名+协议组合已存在']);
                exit;
            }
            $db->prepare(
                "INSERT INTO `domains` (`name`,`domain`,`protocol`,`target_url`,`template`,`status`,`delay`,`img_url`,`site_title`,`site_description`,`is_show_link`,`remarks`,`sort_order`,`owner_id`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
            )->execute([$name,$domain,$proto,$url,$tpl,$status,$delay,$img?:null,$title?:null,$desc?:null,$is_show_link,$remarks?:null,$sort_order,current_uid()]);
            write_admin_log("新增域名 domain={$domain} protocol={$proto}");
            echo json_encode(['ok' => true, 'msg' => 'added']);
        }
    } catch (Throwable $e) {
        echo json_encode(['ok' => false, 'error' => '数据库错误：'.$e->getMessage()]);
    }
    exit;
}

// ── 列表查询 ─────────────────────────────────────────────
$search        = trim($_GET['q'] ?? '');
$status_filter = $_GET['status'] ?? '';

[$ow,$op] = domain_owner_where();
$where  = "WHERE {$ow}";
$params = $op;
if ($search) {
    $where   .= ' AND (`domain` LIKE ? OR `target_url` LIKE ? OR `remarks` LIKE ?)';
    $params[] = '%'.$search.'%';
    $params[] = '%'.$search.'%';
    $params[] = '%'.$search.'%';
}
if (in_array($status_filter, ['active','paused'])) {
    $where   .= ' AND `status`=?';
    $params[] = $status_filter;
}

$count_stmt = $db->prepare("SELECT COUNT(*) FROM `domains` $where");
$count_stmt->execute($params);
$total = (int)$count_stmt->fetchColumn();

$pager = paginate($total, (int)($_GET['page'] ?? 1), 20);
$stmt  = $db->prepare("
    SELECT d.*,
           a.username AS owner_name,
           COUNT(DISTINCT CASE WHEN DATE(l.created_at)=CURDATE() THEN l.ip END) AS today_ip,
           SUM(CASE WHEN DATE(l.created_at)=CURDATE() THEN 1 ELSE 0 END) AS today_pv
    FROM `domains` d
    LEFT JOIN `admins` a ON a.id=d.owner_id
    LEFT JOIN `access_logs` l ON l.domain_id=d.id
    $where
    GROUP BY d.id
    ORDER BY d.sort_order ASC, d.id DESC
    LIMIT {$pager['per_page']} OFFSET {$pager['offset']}
");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$flash = '';
if (!empty($_GET['msg'])) {
    $msgs  = ['added'=>'域名已添加','updated'=>'域名已更新','deleted'=>'域名已删除','toggled'=>'状态已切换','cleared'=>'域名统计已清空'];
    $flash = $msgs[$_GET['msg']] ?? '';
}

$csrf = csrf_token();
$page_title = '跳转管理';
$active_nav = 'domains';
require dirname(__DIR__) . '/_layout_header.php';
?>

<?php if ($flash): ?>
<div class="alert alert-success">&#10003; <?= e($flash) ?></div>
<?php endif; ?>

<div class="card">
  <div class="card-header">
    <span class="card-title">域名列表</span>
    <button class="btn btn-primary" id="btnAdd">&#43; 新增域名</button>
  </div>

  <form method="GET" style="display:flex;gap:10px;margin-bottom:18px;flex-wrap:wrap">
    <input type="text" name="q" class="form-control" style="max-width:280px" placeholder="搜索域名/目标URL" value="<?= e($search) ?>">
    <select name="status" class="form-control" style="max-width:140px">
      <option value="">全部状态</option>
      <option value="active" <?= $status_filter==='active'?'selected':'' ?>>活跃</option>
      <option value="paused" <?= $status_filter==='paused'?'selected':'' ?>>暂停</option>
    </select>
    <button type="submit" class="btn btn-ghost">搜索</button>
    <?php if ($search||$status_filter): ?>
    <a href="/admin/domains/index.php" class="btn btn-ghost">重置</a>
    <?php endif; ?>
  </form>

  <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>排序</th><th>名称</th><th>域名</th><th>协议</th><th>备注</th><th>模板</th><th>添加人</th><th>今日PV</th><th>今日IP</th><th>状态</th><th>创建时间</th><th>操作</th></tr></thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="13" style="padding:28px;text-align:center" class="text-muted">暂无数据</td></tr>
      <?php else: foreach ($rows as $row): ?>
        <tr>
          <td class="text-muted"><?= $row['id'] ?></td>
          <td><span class="badge" style="background:#f0f0f0;color:#666"><?= (int)$row['sort_order'] ?></span></td>
          <td><?= e($row['name'] ?? '—') ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:8px">
              <a href="<?= e($row['protocol']) ?>://<?= e($row['domain']) ?>" target="_blank"><?= e($row['domain']) ?></a>
              <button class="btn btn-ghost btn-sm" style="padding:2px 6px" onclick="showQr('<?=e(addslashes($row['domain']))?>','<?=e($row['protocol'])?>')" title="二维码">&#9641;</button>
            </div>
          </td>
          <td><span class="badge" style="<?= $row['protocol']==='https' ? 'background:#1a7f37;color:#fff' : 'background:#6b7280;color:#fff' ?>"><?= e($row['protocol']) ?></span></td>
          <td class="text-muted text-sm" style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= e($row['remarks']??'') ?>"><?= e($row['remarks']??'—') ?></td>
          <td><span class="badge badge-warning"><?= e($templates[$row['template']]['label'] ?? $row['template']) ?></span></td>
          <td class="text-muted text-sm"><?= e($row['owner_name']??'—') ?></td>
          <td><a href="/admin/domains/stat.php?id=<?=$row['id']?>" class="badge badge-active" style="text-decoration:none"><?=(int)$row['today_pv']?></a></td>
          <td><a href="/admin/domains/stat.php?id=<?=$row['id']?>" class="badge" style="text-decoration:none"><?=(int)$row['today_ip']?></a></td>
          <td><?= $row['status']==='active' ? '<span class="badge badge-active">活跃</span>' : '<span class="badge badge-paused">暂停</span>' ?></td>
          <td class="text-muted text-sm"><?= e(substr($row['created_at'],0,10)) ?></td>
          <td>
            <div class="flex gap-2">
              <a href="/admin/domains/stat.php?id=<?=$row['id']?>" class="btn btn-ghost btn-sm">报表</a>
              <button class="btn btn-ghost btn-sm btn-edit"
                data-row="<?= htmlspecialchars(json_encode($row),ENT_QUOTES) ?>">编辑</button>
              <?php if(is_super()): ?>
              <a href="/admin/domains/index.php?action=clear_stats&id=<?= $row['id'] ?>&csrf_token=<?= $csrf ?>"
                 class="btn btn-ghost btn-sm" style="color:#f76a6a"
                 onclick="return confirm('确认清空该域名的访问数据吗？')">清空统计</a>
              <?php endif; ?>
              <a href="/admin/domains/index.php?action=toggle&id=<?= $row['id'] ?>&csrf_token=<?= $csrf ?>"
                 class="btn btn-sm <?= $row['status']==='active'?'btn-warning':'btn-success' ?>"
                 onclick="return confirm('确认切换状态？')"><?= $row['status']==='active'?'暂停':'启用' ?></a>
              <a href="/admin/domains/index.php?action=delete&id=<?= $row['id'] ?>&csrf_token=<?= $csrf ?>"
                 class="btn btn-danger btn-sm"
                 onclick="return confirm('确认删除？此操作不可撤销。')">删除</a>
            </div>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($pager['total_pages']>1): ?>
  <div class="pagination">
    <?php for($i=1;$i<=$pager['total_pages'];$i++): ?>
    <a href="?page=<?=$i?>&q=<?=urlencode($search)?>&status=<?=urlencode($status_filter)?>"
       class="page-btn <?=$i===$pager['page']?'active':''?>"><?=$i?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>

<!-- Modal -->
<div id="dm" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.55);overflow-y:auto;padding:30px 16px">
  <div style="max-width:620px;margin:0 auto;background:var(--bg-card,#1e2433);border:1px solid var(--border,#2d3448);border-radius:12px;padding:28px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px">
      <h3 id="dm-title" style="font-size:16px;font-weight:600;margin:0">新增域名</h3>
      <button id="dm-close" style="background:none;border:none;font-size:22px;cursor:pointer;color:#888">&#10005;</button>
    </div>
    <div id="dm-err" style="display:none;padding:10px 14px;background:#fee;border:1px solid #fcc;border-radius:6px;color:#c00;margin-bottom:14px;font-size:14px"></div>
    <div class="form-group">
      <label class="form-label">项目名称</label>
      <input type="text" id="f-name" class="form-control" placeholder="仅用于管理标识，如：主站、备份1">
    </div>
    <div class="form-group">
      <label class="form-label">绑定域名 <span style="color:red">*</span></label>
      <div style="display:flex;gap:8px">
        <select id="f-protocol" class="form-control" style="max-width:120px">
          <option value="https">https://</option>
          <option value="http">http://</option>
        </select>
        <input type="text" id="f-domain" class="form-control" placeholder="example.com" style="flex:1">
      </div>
      <p class="form-hint">仅填写域名本身，协议在左侧选择</p>
    </div>
    <div class="form-group">
      <label class="form-label">跳转模板 <span style="color:red">*</span></label>
      <select id="f-tpl" class="form-control">
        <?php foreach($templates as $k=>$v): ?>
        <option value="<?=e($k)?>"><?=e($v['label'])?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div id="g-url" class="form-group">
      <label class="form-label">目标 URL <span style="color:red">*</span></label>
      <input type="text" id="f-url" class="form-control" placeholder="https://example.com">
    </div>
    <div id="g-nav" class="form-group" style="display:none">
      <label class="form-label">跳转链接 <span style="color:red">*</span></label>
      <div id="nav-rows" style="display:flex;flex-direction:column;gap:8px;margin-bottom:8px"></div>
      <button type="button" id="btn-addrow" class="btn btn-ghost btn-sm">&#43; 添加链接</button>
    </div>
    <div id="g-delay" class="form-group" style="display:none">
      <label class="form-label">延时秒数（1-60）</label>
      <input type="number" id="f-delay" class="form-control" style="max-width:100px" value="3" min="1" max="60">
    </div>
    <div id="g-img" class="form-group" style="display:none">
      <label class="form-label">展示图片</label>
      <div id="img-preview-container" style="margin-bottom:10px;display:none">
        <img id="img-preview" src="" style="max-width:100%;max-height:150px;border-radius:8px;border:1px solid var(--border)">
      </div>
      <div style="display:flex;gap:8px;margin-bottom:8px">
        <input type="text" id="f-img" class="form-control" placeholder="图片 URL" style="flex:1">
        <button type="button" id="btn-img-picker" class="btn btn-ghost btn-sm" style="padding:10px 14px">📷 图库</button>
      </div>
      <div style="position:relative;overflow:hidden;display:inline-block;width:100%">
        <button type="button" class="btn btn-ghost btn-sm" style="width:100%;border-style:dashed;padding:12px">选择本地图片上传...</button>
        <input type="file" id="f-img-file" accept="image/*" style="position:absolute;inset:0;opacity:0;cursor:pointer">
      </div>
      <p class="form-hint">支持 URL、图库选择或直接上传图片</p>
    </div>
    <div id="g-navtitle" style="display:none">
      <div class="form-group">
        <label class="form-label">页面标题</label>
        <input type="text" id="f-stitle" class="form-control" placeholder="展示在页面顶部的标题">
      </div>
      <div class="form-group">
        <label class="form-label">页面次标题</label>
        <input type="text" id="f-sdesc" class="form-control" placeholder="展示在页面顶部的描述文字">
      </div>
    </div>
    <div id="g-blackgold" style="display:none">
      <div class="form-group">
        <label class="form-label">品牌副标题</label>
        <input type="text" id="bg-brand-subtitle" class="form-control" placeholder="官方访问入口">
      </div>
      <div class="form-group">
        <label class="form-label">主标语第一行</label>
        <input type="text" id="bg-headline-1" class="form-control" placeholder="多线路访问体验">
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <div class="form-group">
          <label class="form-label">第二行前缀</label>
          <input type="text" id="bg-headline-2-prefix" class="form-control" placeholder="尽在">
        </div>
        <div class="form-group">
          <label class="form-label">第二行高亮</label>
          <input type="text" id="bg-headline-2-highlight" class="form-control" placeholder="品牌导航">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">展示黄条</label>
        <textarea id="bg-hero-bars" class="form-control" rows="3" placeholder="jinshaguoji.com&#10;J999.com&#10;9420.com"></textarea>
        <p class="form-hint">每行一条，最多 3 条。留空则自动使用导航前三项。</p>
      </div>
      <div class="form-group">
        <label class="form-label">右侧标签</label>
        <textarea id="bg-promos" class="form-control" rows="4" placeholder="red|官方推荐&#10;gold|多线访问&#10;green|移动适配&#10;cyan|快速打开"></textarea>
        <p class="form-hint">每行格式：颜色|文案。颜色可填 `red`、`gold`、`green`、`cyan`。</p>
      </div>
      <div class="form-group">
        <label class="form-label">分类入口</label>
        <textarea id="bg-categories" class="form-control" rows="6" placeholder="体育|/uploads/sports.png&#10;电竞|🎮&#10;真人|👑"></textarea>
        <p class="form-hint">每行格式：名称|图标。图标可填图片地址、站内路径或 emoji；不填则显示默认圆形图标。</p>
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">显示跳转链接</label>
      <select id="f-showlink" class="form-control" style="max-width:150px">
        <option value="1">是</option>
        <option value="0">否</option>
      </select>
      <p class="form-hint">如果选“是”，跳转页面将显示即将跳转到的目标地址</p>
    </div>
    <div class="form-group">
      <label class="form-label">排序号</label>
      <input type="number" id="f-sort" class="form-control" style="max-width:150px" value="0">
      <p class="form-hint">数字越小越靠前</p>
    </div>
    <div class="form-group">
      <label class="form-label">备注</label>
      <textarea id="f-remarks" class="form-control" rows="2" placeholder="仅管理员可见"></textarea>
    </div>
    <div class="form-group">
      <label class="form-label">状态</label>
      <select id="f-status" class="form-control" style="max-width:150px">
        <option value="active">活跃</option>
        <option value="paused">暂停</option>
      </select>
    </div>
    <div style="display:flex;gap:10px;margin-top:18px">
      <button id="dm-save" class="btn btn-primary">保存</button>
      <button id="dm-cancel" class="btn btn-ghost">取消</button>
    </div>
  </div>
</div>

<script>
(function(){
  var CSRF     = '<?= addslashes($csrf) ?>';
  var TEMPLATES = <?= json_encode(array_map(function($t){ return ['name'=>$t['name'], 'label'=>$t['label'], 'fields'=>$t['fields']]; }, $templates)) ?>;
  var editId   = 0;

  var dm       = document.getElementById('dm');
  var dmTitle  = document.getElementById('dm-title');
  var dmErr    = document.getElementById('dm-err');
  var dmSave   = document.getElementById('dm-save');
  var navRows  = document.getElementById('nav-rows');

  function $(id){ return document.getElementById(id); }
  function getTemplateConfig(name){ return TEMPLATES[name] || {fields: []}; }
  function templateHasField(name, field){
    return (getTemplateConfig(name).fields || []).indexOf(field) !== -1;
  }
  function defaultBlackgoldMeta(){
    return {
      brand_subtitle: '官方访问入口',
      headline_line1: '多线路访问体验',
      headline_line2_prefix: '尽在',
      headline_line2_highlight: '品牌导航',
      hero_bars: [],
      promo_tags: [
        {style: 'red', text: '官方推荐'},
        {style: 'gold', text: '多线访问'},
        {style: 'green', text: '移动适配'},
        {style: 'cyan', text: '快速打开'}
      ],
      categories: [
        {label: '体育', icon: ''},
        {label: '电竞', icon: ''},
        {label: '真人', icon: ''},
        {label: '电子', icon: ''},
        {label: '彩票', icon: ''},
        {label: '棋牌', icon: ''}
      ]
    };
  }
  function parseNavPayload(raw){
    if(!raw){
      return {links: [], meta: {}};
    }
    try{
      var parsed = JSON.parse(raw);
      if(parsed && typeof parsed === 'object' && !Array.isArray(parsed) && Array.isArray(parsed.links)){
        return {
          links: parsed.links,
          meta: parsed.meta && typeof parsed.meta === 'object' ? parsed.meta : {}
        };
      }
      if(Array.isArray(parsed)){
        return {links: parsed, meta: {}};
      }
      if(parsed && typeof parsed === 'object' && (parsed.url || parsed.name || parsed.icon)){
        return {links: [parsed], meta: {}};
      }
    }catch(e){}
    return {links: [], meta: {}};
  }
  function lineList(items, formatter){
    return (items || []).map(formatter).filter(function(item){ return item !== ''; }).join('\n');
  }
  function fillBlackgoldMeta(meta){
    var data = Object.assign({}, defaultBlackgoldMeta(), meta || {});
    $('bg-brand-subtitle').value = data.brand_subtitle || '';
    $('bg-headline-1').value = data.headline_line1 || '';
    $('bg-headline-2-prefix').value = data.headline_line2_prefix || '';
    $('bg-headline-2-highlight').value = data.headline_line2_highlight || '';
    $('bg-hero-bars').value = lineList(data.hero_bars, function(item){
      return item ? String(item) : '';
    });
    $('bg-promos').value = lineList(data.promo_tags, function(item){
      if(!item || !item.text){ return ''; }
      return (item.style || 'gold') + '|' + item.text;
    });
    $('bg-categories').value = lineList(data.categories, function(item){
      if(!item || !item.label){ return ''; }
      return item.label + (item.icon ? '|' + item.icon : '');
    });
  }
  function parseLineItems(raw){
    return String(raw || '')
      .replace(/\r/g, '')
      .split('\n')
      .map(function(line){ return line.trim(); })
      .filter(function(line){ return line; });
  }
  function collectBlackgoldMeta(){
    var heroBars = parseLineItems($('bg-hero-bars').value).slice(0, 3);

    var promos = parseLineItems($('bg-promos').value).map(function(line){
      var parts = line.split('|');
      var style = (parts.shift() || '').trim();
      var text = parts.join('|').trim();
      if(!text){
        text = style;
        style = 'gold';
      }
      return {style: style || 'gold', text: text};
    }).filter(function(item){ return item.text; });

    var categories = parseLineItems($('bg-categories').value).map(function(line){
      var parts = line.split('|');
      var label = (parts.shift() || '').trim();
      var icon = parts.join('|').trim();
      return {label: label, icon: icon};
    }).filter(function(item){ return item.label; });

    return {
      brand_subtitle: $('bg-brand-subtitle').value.trim(),
      headline_line1: $('bg-headline-1').value.trim(),
      headline_line2_prefix: $('bg-headline-2-prefix').value.trim(),
      headline_line2_highlight: $('bg-headline-2-highlight').value.trim(),
      hero_bars: heroBars,
      promo_tags: promos,
      categories: categories
    };
  }

  function showErr(msg){ dmErr.textContent=msg; dmErr.style.display='block'; }
  function hideErr(){ dmErr.style.display='none'; }

  function addNavRow(name,url,icon,group){
    var row=document.createElement('div');
    row.style.cssText='display:flex;gap:8px;align-items:center';
    var ni=document.createElement('input'); ni.type='text'; ni.className='form-control m-name'; ni.placeholder='按钮名'; ni.value=name||''; ni.style.maxWidth='100px';
    var ui=document.createElement('input'); ui.type='text'; ui.className='form-control m-url'; ui.placeholder='https://...'; ui.value=url||'';
    var gi=document.createElement('select'); gi.className='form-control m-group'; gi.style.maxWidth='108px';
    gi.innerHTML='<option value="">默认</option><option value="official">官网</option><option value="download">下载</option><option value="backup">备用</option><option value="service">客服</option>';
    gi.value=group||'';
    var ii=document.createElement('input'); ii.type='hidden'; ii.className='m-icon'; ii.value=icon||'';
    var ib=document.createElement('button'); ib.type='button'; ib.className='btn btn-ghost btn-sm'; ib.textContent='🖼️ 图标'; ib.style.padding='8px 12px';
    ib.onclick=function(e){
      e.preventDefault();
      openIconPickerForRow(ii);
    };
    var del=document.createElement('button'); del.type='button'; del.className='btn btn-danger btn-sm'; del.textContent='×'; del.onclick=function(){row.remove();};
    row.appendChild(ni); row.appendChild(ui); row.appendChild(gi); row.appendChild(ib); row.appendChild(ii); row.appendChild(del);
    navRows.appendChild(row);
  }
  
  function openIconPickerForRow(iconInput){
    currentIconInput = iconInput;
    iconPickerModal.style.display='block';
    loadDefaultIcons();
  }
  
  var currentIconInput = null;

  function showImgPreview(url){
    var container = $('img-preview-container');
    var img = $('img-preview');
    if(url){
      img.src = url;
      container.style.display = 'block';
    } else {
      container.style.display = 'none';
    }
  }

  $('f-img').addEventListener('input', function(){
    showImgPreview(this.value);
  });

  $('f-img-file').addEventListener('change', function(){
    if(this.files && this.files[0]){
      var reader = new FileReader();
      reader.onload = function(e){
        showImgPreview(e.target.result);
      };
      reader.readAsDataURL(this.files[0]);
    }
  });

  function updateFields(){
    var v=$('f-tpl').value;
    var tplConfig = TEMPLATES[v] || {fields: []};
    var fields = tplConfig.fields || [];
    
    // 隐藏所有可选字段组
    $('g-url').style.display      = 'none';
    $('g-nav').style.display      = 'none';
    $('g-delay').style.display    = 'none';
    $('g-img').style.display      = 'none';
    $('g-navtitle').style.display = 'none';
    $('g-blackgold').style.display = 'none';
    
    // 默认显示标题和描述，除非是纯 301/302 模板（可选）
    // 这里我们强制只要有 site_title 字段需求就显示
    $('g-navtitle').style.display = ''; 
    $('g-img').style.display = '';

    // 根据模板配置显示对应字段
    fields.forEach(function(field){
      if(field === 'url') $('g-url').style.display = '';
      if(field === 'nav') $('g-nav').style.display = '';
      if(field === 'delay') $('g-delay').style.display = '';
      // 如果模板明确需要 img，则保持显示（上面已经默认开启预览）
    });
    
    if(v === 'blackgold') $('g-blackgold').style.display = '';
  }

  function openModal(row){
    hideErr();
    editId=0;
    // 清空所有字段
    $('f-name').value=''; 
    $('f-domain').value=''; 
    $('f-protocol').value='https';
    $('f-url').value=''; 
    $('f-delay').value='3';
    $('f-img').value=''; 
    $('f-img-file').value=''; 
    $('f-stitle').value=''; 
    $('f-sdesc').value='';
    fillBlackgoldMeta(defaultBlackgoldMeta());
    showImgPreview(''); 
    $('f-showlink').value='1';
    $('f-sort').value='0';
    $('f-remarks').value='';
    $('f-status').value='active'; 
    $('f-tpl').value='img';
    navRows.innerHTML='';
    dmSave.disabled=false; 
    dmSave.textContent='保存';
    
    if(row){
      dmTitle.textContent='编辑域名';
      editId=row.id;
      $('f-name').value=row.name||'';
      $('f-domain').value=row.domain||'';
      $('f-protocol').value=row.protocol||'https';
      $('f-tpl').value=row.template||'img';
      $('f-delay').value=row.delay||3;
      $('f-img').value=row.img_url||'';
      showImgPreview(row.img_url);
      $('f-stitle').value=row.site_title||'';
      $('f-sdesc').value=row.site_description||'';
      $('f-showlink').value=row.is_show_link ?? '1';
      $('f-sort').value=row.sort_order ?? '0';
      $('f-remarks').value=row.remarks||'';
      $('f-status').value=row.status||'active';
      updateFields();
      if(templateHasField(row.template, 'nav')){
        var payload = parseNavPayload(row.target_url);
        if(payload.links.length){
          payload.links.forEach(function(i){addNavRow(i.name||'',i.url||'',i.icon||'',i.group||'');});
        } else if(row.target_url && row.target_url.indexOf('http') === 0){
          addNavRow('', row.target_url, '', '');
        } else {
          addNavRow('','','','');
        }
        if(templateHasField(row.template, 'blackgold')){
          fillBlackgoldMeta(payload.meta || {});
        }
      } else { 
        $('f-url').value=row.target_url||''; 
      }
    } else {
      dmTitle.textContent='新增域名';
      updateFields();
    }
    dm.style.display='block';
    document.body.style.overflow='hidden';
  }

  function closeModal(){
    dm.style.display='none';
    document.body.style.overflow='';
  }

  // 二维码弹窗
  var qrModal = document.createElement('div');
  qrModal.style.cssText='display:none;position:fixed;inset:0;z-index:10000;background:rgba(0,0,0,.6);display:none;align-items:center;justify-content:center';
  qrModal.innerHTML='<div style="background:#1e2433;border-radius:12px;padding:28px;text-align:center;min-width:240px"><div id="qr-title" style="font-size:14px;color:#aaa;margin-bottom:16px"></div><div id="qr-box"></div><button onclick="this.closest(\'div\').parentNode.style.display=\'none\'" style="margin-top:16px;background:none;border:1px solid #444;color:#aaa;padding:6px 20px;border-radius:6px;cursor:pointer">关闭</button></div>';
  document.body.appendChild(qrModal);
  qrModal.addEventListener('click',function(e){if(e.target===qrModal)qrModal.style.display='none';});

  window.showQr = function(domain, protocol){
    var url = (protocol || 'https') + '://' + domain;
    document.getElementById('qr-title').textContent = url;
    var box = document.getElementById('qr-box');
    box.innerHTML = '<img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data='+encodeURIComponent(url)+'" style="border-radius:8px;width:180px;height:180px">';
    qrModal.style.display='flex';
  };

  // 事件绑定
  $('btnAdd').addEventListener('click', function(){ openModal(null); });
  $('dm-close').addEventListener('click', closeModal);
  $('dm-cancel').addEventListener('click', closeModal);
  dm.addEventListener('click', function(e){ if(e.target===dm) closeModal(); });
  $('f-tpl').addEventListener('change', function(){
    hideErr();
    updateFields();
    
    // 清空所有可选字段
    $('f-url').value = '';
    $('f-delay').value = '3';
    $('f-img').value = '';
    $('f-stitle').value = '';
    $('f-sdesc').value = '';
    navRows.innerHTML = '';
    fillBlackgoldMeta(defaultBlackgoldMeta());
  });
  $('btn-addrow').addEventListener('click', function(){ addNavRow('','','',''); });

  document.querySelectorAll('.btn-edit').forEach(function(btn){
    btn.addEventListener('click', function(){
      var row=JSON.parse(this.getAttribute('data-row'));
      openModal(row);
    });
  });

  dmSave.addEventListener('click', function(){
    hideErr();
    dmSave.disabled=true; dmSave.textContent='保存中...';

    var isNav=templateHasField($('f-tpl').value, 'nav');
    var hasBlackgold=templateHasField($('f-tpl').value, 'blackgold');
    var targetUrl='';
    if(isNav){
      var links=[];
      navRows.querySelectorAll('div').forEach(function(r){
        var n=r.querySelector('.m-name').value.trim();
        var u=r.querySelector('.m-url').value.trim();
        var g=r.querySelector('.m-group').value.trim();
        var i=r.querySelector('.m-icon').value.trim();
        if(n||u) links.push({name:n,url:u,icon:i,group:g});
      });
      targetUrl=JSON.stringify(hasBlackgold ? {links: links, meta: collectBlackgoldMeta()} : links);
    } else {
      targetUrl=$('f-url').value;
    }

    var fd=new FormData();
    fd.append('_ajax','1');
    fd.append('csrf_token',CSRF);
    fd.append('id',editId);
    fd.append('name',$('f-name').value);
    fd.append('domain',$('f-domain').value);
    fd.append('protocol',$('f-protocol').value);
    fd.append('template',$('f-tpl').value);
    fd.append('target_url',targetUrl);
    fd.append('delay',$('f-delay').value);
    fd.append('img_url',$('f-img').value);
    if($('f-img-file').files[0]){
      fd.append('img_file',$('f-img-file').files[0]);
    }
    fd.append('site_title',$('f-stitle').value);
    fd.append('site_description',$('f-sdesc').value);
    fd.append('is_show_link',$('f-showlink').value);
    fd.append('sort_order',$('f-sort').value);
    fd.append('remarks',$('f-remarks').value);
    fd.append('status',$('f-status').value);

    fetch('/admin/domains/index.php',{method:'POST',body:fd,credentials:'same-origin'})
      .then(function(r){return r.json();})
      .then(function(data){
        if(data.ok){
          window.location.href='/admin/domains/index.php?msg='+data.msg;
        } else {
          showErr(data.error||'保存失败');
          dmSave.disabled=false; dmSave.textContent='保存';
        }
      })
      .catch(function(e){
        showErr('请求失败：'+e.message);
        dmSave.disabled=false; dmSave.textContent='保存';
      });
  });

  // 图库选择器
  var pickerModal = document.createElement('div');
  pickerModal.id = 'pickerModal';
  pickerModal.style.cssText='display:none;position:fixed;inset:0;z-index:10001;background:rgba(0,0,0,.7);overflow-y:auto;padding:20px';
  pickerModal.innerHTML='<div style="max-width:900px;margin:0 auto;background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:24px;pointer-events:auto"><div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px"><h3 style="font-size:16px;font-weight:600;margin:0">选择图片</h3><button id="picker-close" style="background:none;border:none;font-size:22px;cursor:pointer;color:#888">&#10005;</button></div><div id="picker-list" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:12px;max-height:60vh;overflow-y:auto"></div></div>';
  document.body.appendChild(pickerModal);

  var pickerList = document.getElementById('picker-list');
  var pickerClose = document.getElementById('picker-close');
  var currentPickerLib = 'gallery';

  function closePicker(){
    pickerModal.style.display='none';
  }

  function loadPickerImages(lib){
    currentPickerLib = lib;
    pickerList.innerHTML='<div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--text-muted)">加载中...</div>';
    fetch('/admin/media/index.php?lib='+lib)
      .then(function(r){return r.text();})
      .then(function(html){
        var parser=new DOMParser();
        var doc=parser.parseFromString(html,'text/html');
        var items=doc.querySelectorAll('.media-item');
        if(items.length===0){
          pickerList.innerHTML='<div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--text-muted)">暂无图片</div>';
          return;
        }
        pickerList.innerHTML='';
        items.forEach(function(item){
          var img=item.querySelector('.media-thumb img');
          if(!img) return;
          var url=img.src;
          var thumb=document.createElement('div');
          thumb.style.cssText='cursor:pointer;border:1px solid var(--border);border-radius:8px;overflow:hidden;aspect-ratio:1;background:var(--bg);transition:border-color .2s';
          thumb.innerHTML='<img src="'+url+'" style="width:100%;height:100%;object-fit:cover">';
          thumb.onmouseover=function(){this.style.borderColor='var(--accent)'};
          thumb.onmouseout=function(){this.style.borderColor='var(--border)'};
          thumb.onclick=function(e){
            e.stopPropagation();
            $('f-img').value=url;
            showImgPreview(url);
            closePicker();
          };
          pickerList.appendChild(thumb);
        });
      });
  }

  $('btn-img-picker').addEventListener('click',function(e){
    e.stopPropagation();
    pickerModal.style.display='block';
    loadPickerImages('gallery');
  });
  
  // 图标选择器
  var iconPickerModal = document.createElement('div');
  iconPickerModal.id = 'iconPickerModal';
  iconPickerModal.style.cssText='display:none;position:fixed;inset:0;z-index:10001;background:rgba(0,0,0,.7);overflow-y:auto;padding:20px';
  iconPickerModal.innerHTML='<div style="max-width:700px;margin:0 auto;background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:24px;pointer-events:auto"><div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px"><h3 style="font-size:16px;font-weight:600;margin:0">选择图标</h3><button id="icon-picker-close" style="background:none;border:none;font-size:22px;cursor:pointer;color:#888">&#10005;</button></div><div style="display:flex;gap:10px;margin-bottom:16px"><button id="icon-tab-emoji" class="btn btn-primary btn-sm">Emoji</button><button id="icon-tab-gallery" class="btn btn-ghost btn-sm">图库</button></div><div id="icon-list" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(70px,1fr));gap:12px;max-height:60vh;overflow-y:auto"></div></div>';
  document.body.appendChild(iconPickerModal);

  var iconPickerClose = document.getElementById('icon-picker-close');
  var iconList = document.getElementById('icon-list');
  var iconTabEmoji = document.getElementById('icon-tab-emoji');
  var iconTabGallery = document.getElementById('icon-tab-gallery');
  var currentIconTab = 'emoji';

  function closeIconPicker(){
    iconPickerModal.style.display='none';
    currentIconInput = null;
  }

  function loadDefaultIcons(){
    currentIconTab = 'emoji';
    iconTabEmoji.className = 'btn btn-primary btn-sm';
    iconTabGallery.className = 'btn btn-ghost btn-sm';
    var icons = ['🚀', '💡', '🎯', '📱', '💻', '🌐', '📧', '📞', '🔗', '⭐', '🎨', '🔧', '📊', '🎬', '🎵', '📚', '🎓', '🏆', '💎', '🔥', '✨', '🌟', '💫', '🎁', '🎉', '🎊', '🎈', '🎀', '🎭', '🎪', '🐙', '🔍', '📸', '🎮', '🎲', '🃏'];
    iconList.innerHTML='';
    icons.forEach(function(icon){
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.style.cssText = 'background:var(--bg);border:1px solid var(--border);border-radius:8px;padding:12px;font-size:28px;cursor:pointer;transition:all .2s;display:flex;align-items:center;justify-content:center;aspect-ratio:1';
      btn.textContent = icon;
      btn.onmouseover = function(){ this.style.borderColor='var(--accent)'; this.style.transform='scale(1.1)'; };
      btn.onmouseout = function(){ this.style.borderColor='var(--border)'; this.style.transform='scale(1)'; };
      btn.onclick = function(e){
        e.preventDefault();
        if(currentIconInput) currentIconInput.value = icon;
        else $('f-icon').value = icon;
        closeIconPicker();
      };
      iconList.appendChild(btn);
    });
  }

  function loadGalleryIcons(){
    currentIconTab = 'gallery';
    iconTabEmoji.className = 'btn btn-ghost btn-sm';
    iconTabGallery.className = 'btn btn-primary btn-sm';
    iconList.innerHTML='<div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--text-muted)">加载中...</div>';
    fetch('/admin/media/index.php?lib=gallery&q=&page=1')
      .then(function(r){return r.text();})
      .then(function(html){
        var parser=new DOMParser();
        var doc=parser.parseFromString(html,'text/html');
        var items=doc.querySelectorAll('.media-item');
        if(items.length===0){
          iconList.innerHTML='<div style="grid-column:1/-1;text-align:center;padding:40px;color:var(--text-muted)">暂无图片</div>';
          return;
        }
        iconList.innerHTML='';
        items.forEach(function(item){
          var img=item.querySelector('.media-thumb img');
          var url=img.src;
          var thumb=document.createElement('button');
          thumb.type='button';
          thumb.style.cssText='background:var(--bg);border:1px solid var(--border);border-radius:8px;overflow:hidden;aspect-ratio:1;cursor:pointer;transition:all .2s;padding:0';
          thumb.innerHTML='<img src="'+url+'" style="width:100%;height:100%;object-fit:cover">';
          thumb.onmouseover=function(){this.style.borderColor='var(--accent)'; this.style.transform='scale(1.05)';};
          thumb.onmouseout=function(){this.style.borderColor='var(--border)'; this.style.transform='scale(1)';};
          thumb.onclick=function(e){
            e.preventDefault();
            if(currentIconInput) currentIconInput.value = url;
            else $('f-icon').value = url;
            closeIconPicker();
          };
          iconList.appendChild(thumb);
        });
      });
  }

  iconTabEmoji.addEventListener('click',function(e){
    e.stopPropagation();
    loadDefaultIcons();
  });

  iconTabGallery.addEventListener('click',function(e){
    e.stopPropagation();
    loadGalleryIcons();
  });

  iconPickerClose.addEventListener('click',function(e){
    e.stopPropagation();
    closeIconPicker();
  });

  iconPickerModal.addEventListener('click',function(e){
    if(e.target===iconPickerModal) closeIconPicker();
  });
  
  $('btn-img-picker').addEventListener('click',function(e){
    e.stopPropagation();
    pickerModal.style.display='block';
    loadPickerImages('gallery');
  });
  
  pickerGallery.addEventListener('click',function(e){
    e.stopPropagation();
    loadPickerImages('gallery');
  });
  
  pickerRandom.addEventListener('click',function(e){
    e.stopPropagation();
    loadPickerImages('random');
  });
  
  pickerClose.addEventListener('click',function(e){
    e.stopPropagation();
    closePicker();
  });
  
  pickerModal.addEventListener('click',function(e){
    if(e.target===pickerModal) closePicker();
  });
})();
</script>

<?php require dirname(__DIR__) . '/_layout_footer.php'; ?>
