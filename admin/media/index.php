<?php
/**
 * 资源管理 — 图片上传 / 图库管理
 */
require_once dirname(dirname(__DIR__)) . '/config/config.php';
admin_auth();

$db         = get_db();
$upload_base = ROOT_PATH . '/uploads';
$upload_rand = $upload_base . '/random';
$upload_norm = $upload_base . '/gallery';

// 确保目录存在
foreach ([$upload_base, $upload_rand, $upload_norm] as $dir) {
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
}

$ALLOWED_EXT  = ['jpg','jpeg','png','gif','webp'];
$ALLOWED_MIME = ['image/jpeg','image/png','image/gif','image/webp'];
$MAX_SIZE     = 5 * 1024 * 1024; // 5MB

// ── AJAX: 上传 ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    session_start_safe();
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        echo json_encode(['ok'=>false,'error'=>'CSRF验证失败']); exit;
    }
    $type = ($_POST['lib'] ?? '') === 'random' ? 'random' : 'gallery';
    $file = $_FILES['file'] ?? null;
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['ok'=>false,'error'=>'文件上传失败，错误码：'.($file['error']??-1)]); exit;
    }
    if ($file['size'] > $MAX_SIZE) {
        echo json_encode(['ok'=>false,'error'=>'文件超过5MB限制']); exit;
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $ALLOWED_MIME)) {
        echo json_encode(['ok'=>false,'error'=>'不支持的文件类型，仅支持 JPG/PNG/GIF/WEBP']); exit;
    }
    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $ALLOWED_EXT)) $ext = explode('/', $mime)[1];
    $fname    = date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest_dir = $type === 'random' ? $upload_rand : $upload_norm;
    $dest     = $dest_dir . '/' . $fname;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        echo json_encode(['ok'=>false,'error'=>'文件保存失败，请检查目录权限']); exit;
    }
    $rel_url = '/uploads/' . ($type === 'random' ? 'random' : 'gallery') . '/' . $fname;
    try {
        $db->prepare(
            "INSERT INTO `media` (`filename`,`url`,`lib`,`owner_id`) VALUES (?,?,?,?)"
        )->execute([$fname, $rel_url, $type, current_uid()]);
        $new_id = (int)$db->lastInsertId();
    } catch (Throwable $e) {
        $new_id = 0;
    }
    write_admin_log("上传图片 lib={$type} file={$fname}");
    echo json_encode(['ok'=>true,'url'=>$rel_url,'id'=>$new_id,'filename'=>$fname]);
    exit;
}

// ── AJAX: 删除 ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_delete'])) {
    header('Content-Type: application/json; charset=utf-8');
    session_start_safe();
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        echo json_encode(['ok'=>false,'error'=>'CSRF验证失败']); exit;
    }
    $mid = (int)($_POST['id'] ?? 0);
    try {
        $row = $db->prepare("SELECT * FROM `media` WHERE `id`=?");
        $row->execute([$mid]);
        $item = $row->fetch();
        if ($item) {
            if (!is_super() && (int)$item['owner_id'] !== current_uid()) {
                echo json_encode(['ok'=>false,'error'=>'无权限']); exit;
            }
            $path = ROOT_PATH . $item['url'];
            if (file_exists($path)) @unlink($path);
            $db->prepare("DELETE FROM `media` WHERE `id`=?")->execute([$mid]);
            write_admin_log("删除媒体 id={$mid} file={$item['filename']}");
            echo json_encode(['ok'=>true]); exit;
        }
    } catch (Throwable $e) {}
    echo json_encode(['ok'=>false,'error'=>'删除失败']); exit;
}

// ── 列表 ─────────────────────────────────────────────────
$lib    = in_array($_GET['lib'] ?? '', ['random','gallery']) ? $_GET['lib'] : 'random';
$search = trim($_GET['q'] ?? '');

$where  = "WHERE `lib`=?";
$params = [$lib];
if (!is_super()) { $where .= " AND `owner_id`=?"; $params[] = current_uid(); }
if ($search)     { $where .= " AND `filename` LIKE ?"; $params[] = '%'.$search.'%'; }

try {
    $total_st = $db->prepare("SELECT COUNT(*) FROM `media` $where");
    $total_st->execute($params);
    $total = (int)$total_st->fetchColumn();
} catch (Throwable $e) { $total = 0; }

$pager = paginate($total, (int)($_GET['page'] ?? 1), 24);
$rows  = [];
try {
    $st = $db->prepare("SELECT m.*,a.username AS owner_name FROM `media` m LEFT JOIN `admins` a ON a.id=m.owner_id $where ORDER BY m.`id` DESC LIMIT {$pager['per_page']} OFFSET {$pager['offset']}");
    $st->execute($params);
    $rows = $st->fetchAll();
} catch (Throwable $e) {}

$csrf = csrf_token();
$page_title = '资源管理';
$active_nav = 'media';
require dirname(__DIR__) . '/_layout_header.php';
?>

<div class="card" style="margin-bottom:20px">
  <div class="card-header">
    <span class="card-title">资源管理</span>
    <div style="display:flex;gap:10px;align-items:center">
      <a href="?lib=random"  class="btn <?=$lib==='random' ?'btn-primary':'btn-ghost'?> btn-sm">随机图库</a>
      <a href="?lib=gallery" class="btn <?=$lib==='gallery'?'btn-primary':'btn-ghost'?> btn-sm">普通图库</a>
      <button class="btn btn-primary btn-sm" id="btnUpload">&#43; 上传图片</button>
    </div>
  </div>

  <?php if ($lib === 'random'): ?>
  <div style="padding:0 0 14px;font-size:13px;color:var(--text-muted)">
    随机图库供 <code style="background:var(--bg-hover);padding:1px 6px;border-radius:4px">/img.php</code> 随机调用，上传后系统自动随机返回其中一张。
  </div>
  <?php else: ?>
  <div style="padding:0 0 14px;font-size:13px;color:var(--text-muted)">
    普通图库供模板中手动引用使用，上传后可复制 URL 填入域名配置的图片字段。
  </div>
  <?php endif; ?>

  <form method="GET" style="display:flex;gap:10px;margin-bottom:20px">
    <input type="hidden" name="lib" value="<?=e($lib)?>">
    <input type="text" name="q" class="form-control" style="max-width:260px" placeholder="搜索文件名" value="<?=e($search)?>">
    <button type="submit" class="btn btn-ghost">搜索</button>
    <?php if ($search): ?>
    <a href="?lib=<?=e($lib)?>" class="btn btn-ghost">重置</a>
    <?php endif; ?>
  </form>

  <?php if (empty($rows)): ?>
  <div style="text-align:center;padding:48px 0;color:var(--text-muted)">
    <div style="font-size:40px;margin-bottom:12px">&#128444;</div>
    <div>暂无图片，点击右上角「上传图片」添加</div>
  </div>
  <?php else: ?>
  <div class="media-grid">
    <?php foreach ($rows as $item): ?>
    <div class="media-item" id="mi-<?=$item['id']?>">
      <div class="media-thumb" onclick="previewImg('<?=e(addslashes($item['url']))?>')">
        <img src="<?=e($item['url'])?>" alt="<?=e($item['filename'])?>" loading="lazy">
      </div>
      <div class="media-info">
        <div class="media-name" title="<?=e($item['filename'])?>"><?=e(substr($item['filename'],0,22))?></div>
        <div class="media-meta"><?=e($item['owner_name']??'—')?> · <?=e(substr($item['created_at']??'',0,10))?></div>
        <div style="display:flex;gap:6px;margin-top:6px">
          <button class="btn btn-ghost btn-sm" style="flex:1" onclick="copyUrl('<?=e(addslashes($item['url']))?>')">复制URL</button>
          <button class="btn btn-danger btn-sm" onclick="deleteMedia(<?=$item['id']?>)">删除</button>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if ($pager['total_pages'] > 1): ?>
  <div class="pagination" style="margin-top:20px">
    <?php for ($i=1;$i<=$pager['total_pages'];$i++): ?>
    <a href="?lib=<?=e($lib)?>&page=<?=$i?>&q=<?=urlencode($search)?>"
       class="page-btn <?=$i===$pager['page']?'active':''?>"><?=$i?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>

<!-- 上传弹窗 -->
<div id="upModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.55);display:none;align-items:center;justify-content:center">
  <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:12px;padding:28px;width:100%;max-width:480px;position:relative">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px">
      <h3 style="font-size:16px;font-weight:600;margin:0">上传图片</h3>
      <button id="upClose" style="background:none;border:none;font-size:22px;cursor:pointer;color:#888">&#10005;</button>
    </div>
    <div class="form-group">
      <label class="form-label">图库类型</label>
      <select id="upLib" class="form-control" style="max-width:200px">
        <option value="random"  <?=$lib==='random' ?'selected':''?>>随机图库</option>
        <option value="gallery" <?=$lib==='gallery'?'selected':''?>>普通图库</option>
      </select>
    </div>
    <div id="upDrop" style="border:2px dashed var(--border);border-radius:10px;padding:36px;text-align:center;cursor:pointer;transition:border-color .2s;margin-bottom:14px">
      <div style="font-size:32px;margin-bottom:8px">&#128444;</div>
      <div style="color:var(--text-muted);font-size:13px">点击或拖拽图片到此处上传</div>
      <div style="color:var(--text-muted);font-size:12px;margin-top:4px">支持 JPG / PNG / GIF / WEBP，最大 5MB</div>
      <input type="file" id="upFile" accept="image/*" multiple style="display:none">
    </div>
    <div id="upProgress" style="display:none;margin-bottom:12px">
      <div style="background:var(--border);border-radius:4px;height:6px;overflow:hidden">
        <div id="upBar" style="background:var(--accent);height:100%;width:0%;transition:width .3s"></div>
      </div>
      <div id="upStatus" style="font-size:12px;color:var(--text-muted);margin-top:6px">上传中...</div>
    </div>
    <div id="upErr" style="display:none;color:var(--danger);font-size:13px;margin-bottom:10px"></div>
  </div>
</div>

<!-- 预览弹窗 -->
<div id="previewModal" style="display:none;position:fixed;inset:0;z-index:10000;background:rgba(0,0,0,.8);align-items:center;justify-content:center">
  <div style="position:relative;max-width:90vw;max-height:90vh">
    <img id="previewImg" src="" style="max-width:90vw;max-height:85vh;border-radius:8px;display:block">
    <button onclick="document.getElementById('previewModal').style.display='none'" style="position:absolute;top:-16px;right:-16px;background:#333;border:none;color:#fff;border-radius:50%;width:32px;height:32px;font-size:18px;cursor:pointer;line-height:1">&#10005;</button>
  </div>
</div>

<style>
.media-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 14px;
  margin-bottom: 8px;
}
.media-item {
  background: var(--bg);
  border: 1px solid var(--border);
  border-radius: 10px;
  overflow: hidden;
  transition: border-color .2s;
}
.media-item:hover { border-color: var(--accent); }
.media-thumb {
  width: 100%;
  aspect-ratio: 1;
  overflow: hidden;
  cursor: zoom-in;
  background: var(--bg-hover);
  display: flex; align-items: center; justify-content: center;
}
.media-thumb img {
  width: 100%; height: 100%;
  object-fit: cover;
  transition: transform .2s;
}
.media-thumb:hover img { transform: scale(1.05); }
.media-info { padding: 10px; }
.media-name { font-size: 12px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 2px; }
.media-meta { font-size: 11px; color: var(--text-muted); margin-bottom: 4px; }
#upDrop.drag-over { border-color: var(--accent); background: rgba(124,106,247,.06); }
</style>

<script>
(function(){
  var CSRF = '<?= addslashes($csrf) ?>';
  var currentLib = '<?= addslashes($lib) ?>';

  var upModal = document.getElementById('upModal');
  var upDrop  = document.getElementById('upDrop');
  var upFile  = document.getElementById('upFile');

  document.getElementById('btnUpload').onclick = function(){ upModal.style.display='flex'; };
  document.getElementById('upClose').onclick   = function(){ upModal.style.display='none'; };
  upModal.onclick = function(e){ if(e.target===upModal) upModal.style.display='none'; };
  upDrop.onclick  = function(){ upFile.click(); };

  upDrop.addEventListener('dragover',  function(e){ e.preventDefault(); upDrop.classList.add('drag-over'); });
  upDrop.addEventListener('dragleave', function(){ upDrop.classList.remove('drag-over'); });
  upDrop.addEventListener('drop', function(e){
    e.preventDefault(); upDrop.classList.remove('drag-over');
    uploadFiles(e.dataTransfer.files);
  });
  upFile.addEventListener('change', function(){ uploadFiles(this.files); this.value=''; });

  document.getElementById('upLib').onchange = function(){ currentLib = this.value; };

  function uploadFiles(files){
    if (!files || !files.length) return;
    var arr = Array.from(files);
    var done = 0;
    var errs = [];
    var prog = document.getElementById('upProgress');
    var bar  = document.getElementById('upBar');
    var stat = document.getElementById('upStatus');
    var errDiv = document.getElementById('upErr');
    errDiv.style.display='none';
    prog.style.display='block';
    bar.style.width='0%';

    function next(i){
      if (i >= arr.length) {
        bar.style.width='100%';
        stat.textContent = '上传完成，共 '+arr.length+' 张'+(errs.length?' ('+errs.length+'个失败)':'');
        setTimeout(function(){ location.reload(); }, 800);
        return;
      }
      stat.textContent = '正在上传第 '+(i+1)+'/'+arr.length+' 张...';
      bar.style.width = Math.round(i/arr.length*100)+'%';
      var fd = new FormData();
      fd.append('_ajax','1');
      fd.append('csrf_token', CSRF);
      fd.append('lib', document.getElementById('upLib').value);
      fd.append('file', arr[i]);
      fetch('/admin/media/index.php',{method:'POST',body:fd,credentials:'same-origin'})
        .then(function(r){return r.json();})
        .then(function(d){
          if(!d.ok) errs.push(arr[i].name+': '+(d.error||'失败'));
          next(i+1);
        })
        .catch(function(e){ errs.push(arr[i].name+': 网络错误'); next(i+1); });
    }
    next(0);
  }

  window.copyUrl = function(url){
    if (navigator.clipboard) {
      navigator.clipboard.writeText(url).then(function(){ showToast('URL已复制'); });
    } else {
      var ta = document.createElement('textarea');
      ta.value = url; document.body.appendChild(ta); ta.select();
      document.execCommand('copy'); document.body.removeChild(ta);
      showToast('URL已复制');
    }
  };

  window.deleteMedia = function(id){
    if (!confirm('确认删除该图片？此操作不可撤销。')) return;
    var fd = new FormData();
    fd.append('_delete','1');
    fd.append('csrf_token', CSRF);
    fd.append('id', id);
    fetch('/admin/media/index.php',{method:'POST',body:fd,credentials:'same-origin'})
      .then(function(r){return r.json();})
      .then(function(d){
        if(d.ok){
          var el = document.getElementById('mi-'+id);
          if(el) el.remove();
          showToast('已删除');
        } else {
          alert(d.error||'删除失败');
        }
      });
  };

  window.previewImg = function(url){
    document.getElementById('previewImg').src = url;
    document.getElementById('previewModal').style.display = 'flex';
  };
  document.getElementById('previewModal').onclick = function(e){
    if(e.target === this) this.style.display='none';
  };

  function showToast(msg){
    var t = document.createElement('div');
    t.textContent = msg;
    t.style.cssText='position:fixed;bottom:28px;left:50%;transform:translateX(-50%);background:var(--accent);color:#fff;padding:8px 20px;border-radius:20px;font-size:13px;z-index:99999;pointer-events:none;animation:fadeInUp .2s ease';
    document.body.appendChild(t);
    setTimeout(function(){ t.remove(); }, 2000);
  }
})();
</script>
<?php require dirname(__DIR__) . '/_layout_footer.php'; ?>
 