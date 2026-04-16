<?php
/**
 * 用户管理 — 权限隔离版（弹窗交互）
 * @copyright 2026 wenyinos <ruojiner@hotmail.com>
 * @license MIT License
 */
require_once dirname(dirname(__DIR__)) . '/config/config.php';
admin_auth();

$db   = get_db();
$csrf = csrf_token();
$role = current_role();
$uid  = current_uid();

// ── AJAX 接口 ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    session_start_safe();
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        echo json_encode(['ok'=>false,'error'=>'CSRF验证失败']); exit;
    }
    $act = $_POST['act'] ?? '';

    if ($act === 'add') {
        role_guard(['super','agent']);
        $username  = trim($_POST['username'] ?? '');
        $password  = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';
        $new_role  = $role === 'agent' ? 'personal' : ($_POST['role'] ?? 'personal');
        if (!in_array($new_role,['super','agent','personal'])) $new_role='personal';
        $errs=[];
        if (!$username)              $errs[]='用户名不能为空';
        if (strlen($password)<6)    $errs[]='密码不能少于 6 位';
        if ($password!==$password2)  $errs[]='两次密码不一致';
        if ($errs){echo json_encode(['ok'=>false,'error'=>implode('；',$errs)]);exit;}
        $chk=$db->prepare('SELECT id FROM `admins` WHERE `username`=?');$chk->execute([$username]);
        if ($chk->fetch()){echo json_encode(['ok'=>false,'error'=>'用户名已存在']);exit;}
        $owner=(is_super()&&in_array($new_role,['super','agent']))?0:$uid;
        $db->prepare('INSERT INTO `admins`(`username`,`password`,`role`,`owner_id`) VALUES(?,?,?,?)')
           ->execute([$username,password_hash($password,PASSWORD_BCRYPT),$new_role,$owner]);
        write_admin_log("新增用户 username={$username} role={$new_role}");
        echo json_encode(['ok'=>true]);exit;
    }

    if ($act === 'edit_info') {
        $eid         = (int)($_POST['id']??0);
        $new_uname   = trim($_POST['username']??'');
        $new_role2   = $_POST['role']??'';
        $new_pass    = $_POST['password']??'';
        $new_pass2   = $_POST['password2']??'';
        $errs=[];
        if (!$new_uname) $errs[]='用户名不能为空';
        if ($new_pass!==''&&strlen($new_pass)<6) $errs[]='密码不能少于 6 位';
        if ($new_pass!==$new_pass2) $errs[]='两次密码不一致';
        if ($errs){echo json_encode(['ok'=>false,'error'=>implode('；',$errs)]);exit;}
        $eu=$db->prepare('SELECT * FROM `admins` WHERE `id`=?');$eu->execute([$eid]);$eu=$eu->fetch();
        if (!$eu){echo json_encode(['ok'=>false,'error'=>'用户不存在']);exit;}
        if (!is_super()){
            $ok2=($role==='agent'&&$eu['role']==='personal'&&(int)$eu['owner_id']===$uid)||$eid===$uid;
            if (!$ok2){echo json_encode(['ok'=>false,'error'=>'无权限']);exit;}
        }
        $chk=$db->prepare('SELECT id FROM `admins` WHERE `username`=? AND `id`!=?');$chk->execute([$new_uname,$eid]);
        if ($chk->fetch()){echo json_encode(['ok'=>false,'error'=>'用户名已被占用']);exit;}
        $set_role=$eu['role'];
        if (is_super()&&in_array($new_role2,['super','agent','personal'])) $set_role=$new_role2;
        $sql='UPDATE `admins` SET `username`=?,`role`=?';$params=[$new_uname,$set_role];
        if ($new_pass!==''){$sql.=',`password`=?';$params[]=password_hash($new_pass,PASSWORD_BCRYPT);}
        $sql.=' WHERE `id`=?';$params[]=$eid;
        $db->prepare($sql)->execute($params);
        write_admin_log("编辑用户 id={$eid} username={$new_uname}");
        echo json_encode(['ok'=>true]);exit;
    }

    if ($act === 'toggle_status') {
        $eid=(int)($_POST['id']??0);
        if ($eid===$uid){echo json_encode(['ok'=>false,'error'=>'不能禁用自己']);exit;}
        $eu=$db->prepare('SELECT * FROM `admins` WHERE `id`=?');$eu->execute([$eid]);$eu=$eu->fetch();
        if (!$eu){echo json_encode(['ok'=>false,'error'=>'用户不存在']);exit;}
        if (!is_super()){
            $ok2=$role==='agent'&&$eu['role']==='personal'&&(int)$eu['owner_id']===$uid;
            if (!$ok2){echo json_encode(['ok'=>false,'error'=>'无权限']);exit;}
        }
        $new_status=($eu['status']??'active')==='active'?'disabled':'active';
        $db->prepare('UPDATE `admins` SET `status`=? WHERE `id`=?')->execute([$new_status,$eid]);
        write_admin_log("切换用户状态 id={$eid} status={$new_status}");
        echo json_encode(['ok'=>true,'status'=>$new_status]);exit;
    }
    echo json_encode(['ok'=>false,'error'=>'未知操作']);exit;
}

// ── GET 删除 ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD']==='GET'&&isset($_GET['action'])){
    session_start_safe();
    if (!hash_equals($_SESSION['csrf_token']??'',$_GET['csrf_token']??'')){
        http_response_code(403);exit('CSRF verification failed.');
    }
    if ($_GET['action']==='delete'){
        role_guard(['super','agent']);
        $aid=(int)($_GET['id']??0);
        if ($aid&&$aid!==$uid){
            $chk=$db->prepare('SELECT `id`,`role`,`owner_id` FROM `admins` WHERE `id`=?');
            $chk->execute([$aid]);$target=$chk->fetch();
            if ($target){
                $can=is_super()||($role==='agent'&&$target['role']==='personal'&&(int)$target['owner_id']===$uid);
                if ($can){get_db()->prepare('DELETE FROM `admins` WHERE `id`=?')->execute([$aid]);write_admin_log("删除用户 id={$aid}");}
            }
        }
    }
    header('Location: /admin/users/index.php?msg=deleted');exit;
}

// ── 列表 ────────────────────────────────────────────────
$flash='';
if (!empty($_GET['msg'])){$msgs=['added'=>'用户已添加','updated'=>'信息已更新','deleted'=>'用户已删除'];$flash=$msgs[$_GET['msg']]??'';}
[$uw,$up]=user_owner_where();
$stmt=$db->prepare("
    SELECT a.*,
           p.username AS parent_username,
           COUNT(d.id) AS domain_count
    FROM `admins` a
    LEFT JOIN `admins` p ON p.id=a.owner_id AND a.owner_id>0
    LEFT JOIN `domains` d ON d.owner_id=a.id
    WHERE {$uw}
    GROUP BY a.id
    ORDER BY a.id ASC
");$stmt->execute($up);$rows=$stmt->fetchAll();
$role_labels=['super'=>'超级管理员','agent'=>'代理','personal'=>'个人'];
$role_colors=['super'=>'badge-active','agent'=>'badge-warning','personal'=>'badge-paused'];

$page_title='用户管理';$active_nav='users';
require dirname(__DIR__).'/_layout_header.php';
?>
<div id="flashMsg" class="alert alert-success" style="display:<?=$flash?'block':'none'?>">&#10003; <?=e($flash)?></div>

<div class="card">
  <div class="card-header">
    <span class="card-title">用户列表</span>
    <?php if(is_super_or_agent()): ?>
    <button class="btn btn-primary" onclick="openAddModal()">&#43; 新增用户</button>
    <?php endif; ?>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>用户名</th><th>角色</th><th>上级</th><th>域名数</th><th>状态</th><th>创建时间</th><th>操作</th></tr></thead>
      <tbody>
      <?php foreach($rows as $u):
        $is_me=(int)$u['id']===$uid;
        $u_status=$u['status']??'active';
        $can_edit=is_super()||($role==='agent'&&$u['role']==='personal'&&(int)$u['owner_id']===$uid)||$is_me;
        $can_manage=!$is_me&&(is_super()||($role==='agent'&&$u['role']==='personal'&&(int)$u['owner_id']===$uid));
      ?>
      <tr id="urow-<?=$u['id']?>">
        <td class="text-muted"><?=$u['id']?></td>
        <td><strong><?=e($u['username'])?></strong><?php if($is_me): ?> <span class="text-muted text-sm">(我)</span><?php endif; ?></td>
        <td><span class="badge <?=$role_colors[$u['role']]??''?>"><?=e($role_labels[$u['role']]??$u['role'])?></span></td>
        <td class="text-muted text-sm"><?=e($u['parent_username']??'—')?></td>
        <td><span class="badge"><?=(int)$u['domain_count']?></span></td>
        <td><span id="status-<?=$u['id']?>" class="badge <?=$u_status==='active'?'badge-active':'badge-paused'?>"><?=$u_status==='active'?'启用':'禁用'?></span></td>
        <td class="text-muted text-sm"><?=e($u['created_at'])?></td>
        <td><div class="flex gap-2">
          <?php if($can_edit): ?>
          <button class="btn btn-ghost btn-sm" onclick="openEditModal(<?=$u['id']?>,'<?=e(addslashes($u['username']))?>','<?=$u['role']?>')">编辑</button>
          <?php endif; ?>
          <?php if($can_manage): ?>
          <button id="tbtn-<?=$u['id']?>" class="btn btn-sm <?=$u_status==='active'?'btn-warning':'btn-ghost'?>" onclick="toggleStatus(<?=$u['id']?>)"><?=$u_status==='active'?'禁用':'启用'?></button>
          <a href="/admin/users/index.php?action=delete&id=<?=$u['id']?>&csrf_token=<?=$csrf?>" class="btn btn-danger btn-sm" onclick="return confirm('确认删除？')">删除</a>
          <?php endif; ?>
        </div></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- 新增用户弹窗 -->
<div id="addModal" class="modal-overlay" style="display:none">
  <div class="modal">
    <div class="modal-header"><span class="modal-title">新增用户</span><button class="modal-close" onclick="closeModal('addModal')">&times;</button></div>
    <div id="addError" class="alert alert-danger" style="display:none"></div>
    <form id="addForm">
      <input type="hidden" name="_ajax" value="1">
      <input type="hidden" name="csrf_token" value="<?=$csrf?>">
      <input type="hidden" name="act" value="add">
      <div class="form-group"><label class="form-label">用户名 <span class="text-danger">*</span></label><input type="text" name="username" class="form-control" autocomplete="off" placeholder="请输入用户名"></div>
      <?php if(is_super()): ?>
      <div class="form-group"><label class="form-label">角色</label><select name="role" class="form-control"><option value="personal">个人</option><option value="agent">代理</option><option value="super">超级管理员</option></select></div>
      <?php endif; ?>
      <div class="form-group"><label class="form-label">密码 <span class="text-danger">*</span></label><input type="password" name="password" class="form-control" placeholder="不少于 6 位"></div>
      <div class="form-group"><label class="form-label">确认密码 <span class="text-danger">*</span></label><input type="password" name="password2" class="form-control"></div>
      <div class="modal-footer"><button type="button" class="btn btn-ghost" onclick="closeModal('addModal')">取消</button><button type="submit" class="btn btn-primary">保存</button></div>
    </form>
  </div>
</div>

<!-- 编辑用户弹窗 -->
<div id="editModal" class="modal-overlay" style="display:none">
  <div class="modal">
    <div class="modal-header"><span class="modal-title">编辑用户 — <span id="editUsername"></span></span><button class="modal-close" onclick="closeModal('editModal')">&times;</button></div>
    <div id="editError" class="alert alert-danger" style="display:none"></div>
    <form id="editForm">
      <input type="hidden" name="_ajax" value="1">
      <input type="hidden" name="csrf_token" value="<?=$csrf?>">
      <input type="hidden" name="act" value="edit_info">
      <input type="hidden" name="id" id="editId">
      <div class="form-group"><label class="form-label">用户名 <span class="text-danger">*</span></label><input type="text" name="username" id="editUsernameInput" class="form-control" autocomplete="off"></div>
      <?php if(is_super()): ?>
      <div class="form-group"><label class="form-label">角色</label><select name="role" id="editRole" class="form-control"><option value="personal">个人</option><option value="agent">代理</option><option value="super">超级管理员</option></select></div>
      <?php endif; ?>
      <div class="form-group"><label class="form-label">新密码<span class="text-muted text-sm">（留空不修改）</span></label><input type="password" name="password" class="form-control" placeholder="留空则不修改密码"></div>
      <div class="form-group"><label class="form-label">确认密码</label><input type="password" name="password2" class="form-control"></div>
      <div class="modal-footer"><button type="button" class="btn btn-ghost" onclick="closeModal('editModal')">取消</button><button type="submit" class="btn btn-primary">保存</button></div>
    </form>
  </div>
</div>

<script>
var CSRF='<?=$csrf?>';
function openAddModal(){
  var el=document.getElementById('addModal');if(!el)return;
  document.getElementById('addForm').reset();
  var e=document.getElementById('addError');if(e)e.style.display='none';
  el.style.display='flex';
}
function openEditModal(id,username,role){
  var el=document.getElementById('editModal');if(!el)return;
  document.getElementById('editId').value=id;
  document.getElementById('editUsername').textContent=username;
  document.getElementById('editUsernameInput').value=username;
  var roleEl=document.getElementById('editRole');
  if(roleEl) roleEl.value=role;
  document.getElementById('editForm').querySelectorAll('input[type=password]').forEach(function(i){i.value='';});
  var e=document.getElementById('editError');if(e)e.style.display='none';
  el.style.display='flex';
}
function closeModal(id){document.getElementById(id).style.display='none';}
document.querySelectorAll('.modal-overlay').forEach(function(el){
  el.addEventListener('click',function(e){if(e.target===el)el.style.display='none';});
});
function submitAjax(formId,errId,onSuccess){
  var form=document.getElementById(formId);
  var btn=form.querySelector('[type=submit]');
  var errEl=document.getElementById(errId);
  if(errEl)errEl.style.display='none';
  btn.disabled=true;btn.textContent='保存中...';
  fetch(location.pathname,{method:'POST',body:new FormData(form)})
    .then(function(r){return r.json();})
    .then(function(d){
      if(d.ok){onSuccess();}
      else{
        if(errEl){errEl.textContent=d.error||'操作失败';errEl.style.display='block';}
        else{alert(d.error||'操作失败');}
        btn.disabled=false;btn.textContent='保存';
      }
    })
    .catch(function(){
      if(errEl){errEl.textContent='网络错误';errEl.style.display='block';}
      else{alert('网络错误');}
      btn.disabled=false;btn.textContent='保存';
    });
}
function toggleStatus(id){
  var fd=new FormData();
  fd.append('_ajax','1');fd.append('csrf_token',CSRF);
  fd.append('act','toggle_status');fd.append('id',id);
  fetch(location.pathname,{method:'POST',body:fd})
    .then(function(r){return r.json();})
    .then(function(d){
      if(d.ok){
        var s=d.status;
        var badge=document.getElementById('status-'+id);
        var btn=document.getElementById('tbtn-'+id);
        if(badge){badge.textContent=s==='active'?'启用':'禁用';badge.className='badge '+(s==='active'?'badge-active':'badge-paused');}
        if(btn){btn.textContent=s==='active'?'禁用':'启用';btn.className='btn btn-sm '+(s==='active'?'btn-warning':'btn-ghost');}
      }else{alert(d.error||'操作失败');}
    })
    .catch(function(){alert('网络错误');});
}
var addForm=document.getElementById('addForm');
if(addForm)addForm.addEventListener('submit',function(e){
  e.preventDefault();
  submitAjax('addForm','addError',function(){location.href='/admin/users/index.php?msg=added';});
});
var editForm=document.getElementById('editForm');
if(editForm)editForm.addEventListener('submit',function(e){
  e.preventDefault();
  submitAjax('editForm','editError',function(){location.href='/admin/users/index.php?msg=updated';});
});
</script>
<?php require dirname(__DIR__).'/_layout_footer.php'; ?>
