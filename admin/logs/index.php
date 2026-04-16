<?php
/**
 * 操作日志
 * @copyright 2026 wenyinos <ruojiner@hotmail.com>
 * @license MIT License
 */
require_once dirname(dirname(__DIR__)) . '/config/config.php';
admin_auth();

$db = get_db();

// 搜索
$search = trim($_GET['q'] ?? '');
$where  = 'WHERE 1=1';
$params = [];
if ($search) {
    $where   .= ' AND (`username` LIKE ? OR `action` LIKE ? OR `ip` LIKE ? OR `location` LIKE ? OR `domain` LIKE ?)';
    $params[] = '%'.$search.'%';
    $params[] = '%'.$search.'%';
    $params[] = '%'.$search.'%';
    $params[] = '%'.$search.'%';
    $params[] = '%'.$search.'%';
}

$count_stmt = $db->prepare("SELECT COUNT(*) FROM `admin_logs` $where");
$count_stmt->execute($params);
$total = (int)$count_stmt->fetchColumn();

$pager = paginate($total, (int)($_GET['page'] ?? 1), 30);
$stmt  = $db->prepare("SELECT * FROM `admin_logs` $where ORDER BY `id` DESC LIMIT {$pager['per_page']} OFFSET {$pager['offset']}");
$stmt->execute($params);
$rows = $stmt->fetchAll();

// 检测 admin_logs 是否有 location / domain 列
$has_location = false;
$has_domain   = false;
try {
    $cols = $db->query("SHOW COLUMNS FROM `admin_logs`")->fetchAll(PDO::FETCH_COLUMN);
    $has_location = in_array('location', $cols);
    $has_domain   = in_array('domain',   $cols);
} catch (Throwable $e) {}

$page_title = '操作日志';
$active_nav = 'logs';
require dirname(__DIR__) . '/_layout_header.php';
?>

<div class="card">
  <div class="card-header">
    <span class="card-title">操作日志</span>
  </div>

  <form method="GET" style="display:flex;gap:10px;margin-bottom:18px;flex-wrap:wrap">
    <input type="text" name="q" class="form-control" style="max-width:340px"
           placeholder="搜索用户名 / 操作内容 / IP / 归属地 / 域名" value="<?= e($search) ?>">
    <button type="submit" class="btn btn-ghost">搜索</button>
    <?php if ($search): ?>
    <a href="/admin/logs/index.php" class="btn btn-ghost">重置</a>
    <?php endif; ?>
  </form>

  <div class="table-wrap">
    <table>
      <thead><tr>
        <th>#</th>
        <th>用户</th>
        <th>登录IP</th>
        <?php if ($has_location): ?><th>归属地</th><?php endif; ?>
        <?php if ($has_domain):   ?><th>访问域名</th><?php endif; ?>
        <th>操作内容</th>
        <th>UA</th>
        <th>时间</th>
      </tr></thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="<?= 6 + ($has_location?1:0) + ($has_domain?1:0) ?>" style="padding:28px;text-align:center" class="text-muted">暂无日志</td></tr>
      <?php else: foreach ($rows as $row): ?>
        <tr>
          <td class="text-muted"><?= $row['id'] ?></td>
          <td><strong><?= e($row['username']) ?></strong></td>
          <td class="text-muted"><?= e($row['ip']) ?></td>
          <?php if ($has_location): ?>
          <td class="text-muted text-sm"><?= e($row['location'] ?? '—') ?></td>
          <?php endif; ?>
          <?php if ($has_domain): ?>
          <td class="text-muted text-sm"><?= e($row['domain'] ?? '—') ?></td>
          <?php endif; ?>
          <td><?= e($row['action']) ?></td>
          <td class="text-muted text-sm" style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= e($row['ua'] ?? '') ?>"><?= e(substr($row['ua']??'',0,50)) ?></td>
          <td class="text-muted text-sm"><?= e($row['created_at']) ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($pager['total_pages'] > 1): ?>
  <div class="pagination">
    <?php for ($i=1;$i<=$pager['total_pages'];$i++): ?>
    <a href="?page=<?=$i?>&amp;q=<?=urlencode($search)?>"
       class="page-btn <?=$i===$pager['page']?'active':''?>"><?=$i?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>

<?php require dirname(__DIR__) . '/_layout_footer.php'; ?>
