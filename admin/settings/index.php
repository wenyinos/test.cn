<?php
/**
 * 网站配置
 */
require_once dirname(dirname(__DIR__)) . '/config/config.php';
admin_auth();

$saved = false;
$errors = [];

$keys = ['site_name', 'site_description', 'icp'];
$form = [];
foreach ($keys as $k) {
    $form[$k] = get_setting($k);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $form['site_name']        = trim($_POST['site_name'] ?? '');
    $form['site_description'] = trim($_POST['site_description'] ?? '');
    $form['icp']              = trim($_POST['icp'] ?? '');

    if (!$form['site_name']) $errors[] = '站点名称不能为空';

    if (empty($errors)) {
        foreach ($form as $k => $v) {
            set_setting($k, $v);
        }
        $saved = true;
    }
}

$page_title = '网站配置';
$active_nav = 'settings';
require dirname(__DIR__) . '/_layout_header.php';
?>

<div style="max-width:680px">
  <?php if ($saved): ?>
  <div class="alert alert-success">&#10003; 配置已保存</div>
  <?php endif; ?>

  <?php foreach ($errors as $err): ?>
  <div class="alert alert-danger">&#10007; <?= e($err) ?></div>
  <?php endforeach; ?>

  <div class="card">
    <div class="card-header"><span class="card-title">网站配置</span></div>
    <form method="POST" style="padding:8px 0">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

      <div class="form-group">
        <label class="form-label">站点名称 <span class="text-danger">*</span></label>
        <input type="text" name="site_name" class="form-control" value="<?= e($form['site_name']) ?>">
      </div>

      <div class="form-group">
        <label class="form-label">站点描述</label>
        <input type="text" name="site_description" class="form-control"
               value="<?= e($form['site_description']) ?>" placeholder="专业的域名跳转管理系统">
      </div>

      <div class="form-group">
        <label class="form-label">ICP 备案号</label>
        <input type="text" name="icp" class="form-control"
               value="<?= e($form['icp']) ?>" placeholder="京ICP备xxxxxxxx号">
        <p class="form-hint">将显示在暂停页底部版权信息中</p>
      </div>

      <div class="mt-3">
        <button type="submit" class="btn btn-primary">保存配置</button>
      </div>
    </form>
  </div>
</div>

<?php require dirname(__DIR__) . '/_layout_footer.php'; ?>
