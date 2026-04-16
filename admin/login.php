<?php
/**
 * 后台登录页
 */
require_once dirname(__DIR__) . '/config/config.php';
session_start_safe();

// 已登录则跳转
if (!empty($_SESSION['admin_id'])) {
    header('Location: /admin/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $captcha  = strtoupper(trim($_POST['captcha'] ?? ''));
    $sess_cap = strtoupper($_SESSION['captcha'] ?? '');

    // 验证码校验
    if (!$captcha || $captcha !== $sess_cap) {
        $error = '验证码错误';
    } elseif (!$username || !$password) {
        $error = '请填写用户名和密码';
    } else {
        // 验证码用完即失效
        unset($_SESSION['captcha']);
        try {
            $stmt = get_db()->prepare("SELECT * FROM `admins` WHERE `username` = ? LIMIT 1");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                session_regenerate_id(true);
                $_SESSION['admin_id']       = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_role']     = $admin['role'] ?? 'personal';
                $_SESSION['last_active']    = time();
                write_admin_log('登录成功');
                header('Location: /admin/index.php');
                exit;
            } else {
                $error = '用户名或密码错误';
                write_admin_log("登录失败 username={$username}");
            }
        } catch (Throwable $e) {
            $error = '系统错误，请稍后重试';
            error_log('Login error: ' . $e->getMessage());
        }
    }
}

$timeout = isset($_GET['timeout']);
?>
<!DOCTYPE html>
<html lang="zh-CN" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover,maximum-scale=5">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="theme-color" content="#0f1117">
<title>登录 · <?= e(get_site_name()) ?></title>
<link rel="stylesheet" href="/assets/css/admin.css">
<script src="/assets/js/admin.js" defer></script>
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <div class="login-logo">
      <div class="logo-icon">J</div>
      <div>
        <div class="logo-text"><?= e(get_site_name()) ?></div>
        <div class="logo-sub">管理后台</div>
      </div>
    </div>

    <?php if ($timeout): ?>
    <div class="alert alert-warning">&#9203; 登录已超时，请重新登录</div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert alert-danger">&#10007; <?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label class="form-label">用户名</label>
        <input type="text" name="username" class="form-control"
               value="<?= e($_POST['username'] ?? '') ?>"
               placeholder="请输入用户名" autofocus autocomplete="username">
      </div>
      <div class="form-group">
        <label class="form-label">密码</label>
        <input type="password" name="password" class="form-control"
               placeholder="请输入密码" autocomplete="current-password">
      </div>
      <div class="form-group">
        <label class="form-label">验证码</label>
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
          <input type="text" name="captcha" class="form-control" style="flex:1;min-width:120px"
                 placeholder="请输入验证码" autocomplete="off" maxlength="4">
          <img id="captchaImg" src="/admin/captcha.php" alt="验证码"
               style="height:40px;border-radius:6px;cursor:pointer;flex-shrink:0" title="点击刷新">
        </div>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:8px;padding:12px">
        登录
      </button>
    </form>
  </div>
</div>
<script>
document.getElementById('captchaImg').addEventListener('click', function(){
  this.src = '/admin/captcha.php?t=' + Date.now();
});
</script>
</body>
</html>
