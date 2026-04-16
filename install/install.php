<?php
/**
 * JumpHost 安装向导
 * 流程：环境检查 → 配置安装 → 完成
 */

$config_file = dirname(__DIR__) . '/config/config.php';
$sql_file    = __DIR__ . '/jumphost.sql';

// ── 已安装检测 ────────────────────────────────────────────
if (file_exists($config_file)) {
    try {
        $cfg = file_get_contents($config_file);
        if (preg_match("/define\('DB_HOST',\s*'(.+?)'/", $cfg, $m) && $m[1] !== '') {
            preg_match("/define\('DB_PORT',\s*'(.*?)'/", $cfg, $mp);
            preg_match("/define\('DB_NAME',\s*'(.*?)'/", $cfg, $mn);
            preg_match("/define\('DB_USER',\s*'(.*?)'/", $cfg, $mu);
            preg_match("/define\('DB_PASS',\s*'(.*?)'/", $cfg, $mw);
            $testDsn = 'mysql:host='.$m[1].';port='.($mp[1]??'3306').';dbname='.($mn[1]??'').';charset=utf8mb4';
            $testPdo = new PDO($testDsn, $mu[1]??'', $mw[1]??'', [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
            // 验证表存在
            $testPdo->query('SELECT 1 FROM `admins` LIMIT 1');
            // 能到这里说明已安装
            show_installed(); exit;
        }
    } catch (Throwable $e) { /* 连不上则继续安装 */ }
}

function show_installed(): void {
    page_start('系统已安装');
    echo '<div style="text-align:center;padding:20px 0">';
    echo '<div style="font-size:48px;margin-bottom:16px">⚠️</div>';
    echo '<h2 style="color:#c62828;margin-bottom:12px">系统已安装，禁止重复安装</h2>';
    echo '<p style="color:#666;font-size:14px;line-height:1.8">检测到系统已完成安装。<br>如需重装，请先清空数据库并删除 config.php 中的数据库配置后再访问此页面。</p>';
    echo '<div style="margin-top:24px"><a href="/admin/login.php" class="btn" style="text-decoration:none;display:inline-block;width:auto;padding:12px 32px">进入后台</a></div>';
    echo '</div>';
    page_end();
}

// ── 环境检查 ──────────────────────────────────────────────
$env_items = [
    ['PHP >= 7.4',       version_compare(PHP_VERSION,'7.4.0','>='), 'PHP '.PHP_VERSION,        true],
    ['pdo',              extension_loaded('pdo'),                   '',                       true],
    ['pdo_mysql',        extension_loaded('pdo_mysql'),             '',                       true],
    ['json',             extension_loaded('json'),                  '',                       true],
    ['mbstring',         extension_loaded('mbstring'),              '',                       true],
    ['openssl',          extension_loaded('openssl'),               '',                       true],
    ['gd（验证码）',      extension_loaded('gd'),                    '非必须',                 false],
    ['config/ 可写',     is_writable(dirname($config_file))||is_writable($config_file), '', true],
    ['根目录可写(.htaccess)', is_writable(dirname(__DIR__)),         '',                       true],
    ['jumphost.sql 存在', file_exists($sql_file),                   '',                       true],
];
$env_ok = true;
foreach ($env_items as $item) { if ($item[3] && !$item[1]) { $env_ok = false; break; } }

// ── 步骤控制 ──────────────────────────────────────────────
$step    = $_POST['step'] ?? ($_GET['step'] ?? '1');
$errors  = [];
$success = false;

// 只有 step=2 且 POST 才执行安装
if ($step === '2' && $_SERVER['REQUEST_METHOD']==='POST' && $env_ok) {
    $host    = trim($_POST['db_host']    ?? 'localhost');
    $port    = trim($_POST['db_port']    ?? '3306');
    $dbname  = trim($_POST['db_name']    ?? 'jumphost');
    $user    = trim($_POST['db_user']    ?? '');
    $pass    = $_POST['db_pass']         ?? '';
    $admin_u = trim($_POST['admin_user'] ?? 'admin');
    $admin_p = $_POST['admin_pass']      ?? '';
    $site_n  = trim($_POST['site_name']  ?? 'JumpHost');

    if (!$user)                  $errors[] = '数据库用户名不能为空';
    if (!$admin_u)               $errors[] = '管理员账号不能为空';
    if (strlen($admin_p) < 6)   $errors[] = '管理员密码不能少于 6 位';

    if (empty($errors)) {
        try {
            $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$dbname}`");
            try { $pdo->exec("SET GLOBAL innodb_large_prefix=ON"); } catch(Throwable $e){}
            try { $pdo->exec("SET GLOBAL innodb_file_format=Barracuda"); } catch(Throwable $e){}

            // 导入完整 SQL
            foreach (array_filter(array_map('trim', explode(';', file_get_contents($sql_file)))) as $s) {
                if ($s !== '' && stripos($s, 'ALTER TABLE') === false) {
                    $pdo->exec($s);
                }
            }

            // 更新管理员
            $pdo->prepare("UPDATE `admins` SET `username`=?,`password`=? WHERE `id`=1")
                ->execute([$admin_u, password_hash($admin_p, PASSWORD_BCRYPT)]);
            $pdo->prepare("INSERT INTO `settings`(`key`,`value`) VALUES('site_name',?) ON DUPLICATE KEY UPDATE `value`=?")
                ->execute([$site_n, $site_n]);

            // 写 config.php
            $base    = file_get_contents($config_file);
            $fn_pos  = strpos($base, 'function get_db()');
            $fns     = $fn_pos !== false ? substr($base, $fn_pos) : '';
            $content = <<<PHP
<?php
define('DB_HOST',    '{$host}');
define('DB_PORT',    '{$port}');
define('DB_NAME',    '{$dbname}');
define('DB_USER',    '{$user}');
define('DB_PASS',    '{$pass}');
define('DB_CHARSET', 'utf8mb4');
define('APP_NAME',        'JumpHost');
define('APP_VERSION',     '2.0.0');
define('ADMIN_PREFIX',    '/admin');
define('SESSION_LIFETIME', 7200);
define('ROOT_PATH',     dirname(__DIR__));
define('TEMPLATE_PATH', ROOT_PATH . '/templates');
define('ADMIN_PATH',    ROOT_PATH . '/admin');
ini_set('display_errors', 0); ini_set('log_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('Asia/Shanghai');

PHP;
            file_put_contents($config_file, $content . $fns);

            // 写 .htaccess
            file_put_contents(dirname(__DIR__).'/.htaccess',
                "Options -Indexes\n\nRewriteEngine On\nRewriteRule ^config(/|\$) - [F,L]\nRewriteRule ^install(/|\$) - [F,L]\nRewriteCond %{REQUEST_FILENAME} -f [OR]\nRewriteCond %{REQUEST_FILENAME} -d\nRewriteRule ^ - [L]\nRewriteRule ^admin(/|\$) - [L]\nRewriteRule ^ index.php [L]\n"
            );
            $success = true;
        } catch (PDOException $e) { $errors[] = '数据库错误：'.$e->getMessage(); }
          catch (Throwable  $e) { $errors[] = '安装失败：'.$e->getMessage(); }
    }
}

// ── 页面辅助 ──────────────────────────────────────────────
function page_start(string $title): void { ?>
<!DOCTYPE html><html lang="zh-CN"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=htmlspecialchars($title)?> · JumpHost</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',system-ui,sans-serif;background:linear-gradient(135deg,#1a1a2e,#16213e,#0f3460);min-height:100vh;display:flex;justify-content:center;align-items:flex-start;padding:48px 16px}
.card{background:#fff;border-radius:16px;box-shadow:0 8px 48px rgba(0,0,0,.35);padding:40px;width:100%;max-width:560px}
.logo{width:52px;height:52px;background:linear-gradient(135deg,#667eea,#764ba2);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:26px;font-weight:800;color:#fff;margin-bottom:18px}
h1{font-size:22px;color:#1a1a2e;margin-bottom:4px}
.sub{color:#999;font-size:13px;margin-bottom:0}
.divider{border:none;border-top:1px solid #f0f0f0;margin:24px 0}
.env-row{display:flex;align-items:center;gap:10px;padding:7px 0;border-bottom:1px solid #f8f8f8;font-size:13px}
.env-row:last-child{border:none}
.tag{padding:2px 10px;border-radius:20px;font-size:11px;font-weight:700}
.ok{background:#e8f5e9;color:#2e7d32}.fail{background:#ffebee;color:#c62828}.warn{background:#fff8e1;color:#e65100}
.env-note{margin-left:auto;color:#bbb;font-size:12px}
section h3{font-size:11px;text-transform:uppercase;letter-spacing:.1em;color:#bbb;margin-bottom:14px}
label{display:block;font-size:13px;color:#555;font-weight:600;margin-bottom:5px;margin-top:16px}
input{width:100%;padding:10px 14px;border:1.5px solid #e8e8e8;border-radius:8px;font-size:14px;outline:none;transition:border .2s;color:#222}
input:focus{border-color:#667eea;box-shadow:0 0 0 3px rgba(102,126,234,.1)}
.alert{border-radius:8px;padding:10px 14px;margin-bottom:12px;font-size:13px}
.alert-err{background:#ffebee;border:1px solid #ffcdd2;color:#c62828}
.alert-ok{background:#e8f5e9;border:1px solid #c8e6c9;color:#2e7d32}
.btn{display:block;width:100%;margin-top:28px;padding:13px;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:600;cursor:pointer;transition:opacity .2s}
.btn:hover{opacity:.88}
.btn[disabled]{opacity:.45;cursor:not-allowed}
.success-icon{font-size:52px;margin-bottom:16px}
</style>
</head><body><div class="card">
<div class="logo">J</div>
<?php }
function page_end(): void { echo '</div></body></html>'; }
?>
<?php
page_start('JumpHost 安装向导');

if ($success): ?>
  <div style="text-align:center;padding:16px 0">
    <div class="success-icon">✅</div>
    <h2 style="color:#2e7d32;font-size:22px;margin-bottom:12px">安装成功！</h2>
    <p style="color:#666;font-size:14px;line-height:1.8;margin-bottom:20px">数据库和配置文件已就绪。<br><strong>请立即删除 install/ 目录</strong>，防止他人重复安装。</p>
    <a href="/admin/login.php" class="btn" style="text-decoration:none;display:block">进入后台 →</a>
  </div>

<?php elseif ($step === '1' || !$env_ok): ?>
  <!-- Step 1: 环境检查 -->
  <h1>JumpHost 安装向导</h1>
  <p class="sub">Step 1 / 2 · 环境检查</p>
  <hr class="divider">
  <?php if (!$env_ok): ?>
  <div class="alert alert-err">⚠️ 以下必要条件未通过，请修复后刷新页面重试</div>
  <?php else: ?>
  <div class="alert alert-ok">✓ 环境检查全部通过，可以继续安装</div>
  <?php endif; ?>
  <div style="margin-bottom:8px">
    <?php foreach ($env_items as [$name,$pass,$note,$required]): ?>
    <div class="env-row">
      <span class="tag <?= $pass?'ok':($required?'fail':'warn') ?>"><?= $pass?'✓':($required?'✗':'—') ?></span>
      <span><?= htmlspecialchars($name) ?></span>
      <?php if ($note): ?><span class="env-note"><?= htmlspecialchars($note) ?></span><?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php if ($env_ok): ?>
  <form method="GET">
    <input type="hidden" name="step" value="2">
    <button type="submit" class="btn">下一步：配置安装 →</button>
  </form>
  <?php else: ?>
  <button class="btn" disabled>无法安装，请修复上方问题后刷新</button>
  <?php endif; ?>

<?php else: ?>
  <!-- Step 2: 配置安装 -->
  <h1>JumpHost 安装向导</h1>
  <p class="sub">Step 2 / 2 · 配置安装</p>
  <hr class="divider">
  <?php foreach ($errors as $err): ?>
  <div class="alert alert-err">✗ <?= htmlspecialchars($err) ?></div>
  <?php endforeach; ?>
  <form method="POST">
    <input type="hidden" name="step" value="2">
    <section>
      <h3>数据库配置</h3>
      <label>主机地址</label>
      <input type="text" name="db_host" value="<?= htmlspecialchars($_POST['db_host']??'localhost') ?>" placeholder="localhost">
      <label>端口</label>
      <input type="text" name="db_port" value="<?= htmlspecialchars($_POST['db_port']??'3306') ?>" placeholder="3306">
      <label>数据库名</label>
      <input type="text" name="db_name" value="<?= htmlspecialchars($_POST['db_name']??'jumphost') ?>" placeholder="jumphost">
      <label>用户名</label>
      <input type="text" name="db_user" value="<?= htmlspecialchars($_POST['db_user']??'') ?>" placeholder="root">
      <label>密码</label>
      <input type="password" name="db_pass" placeholder="留空表示无密码">
    </section>
    <hr class="divider">
    <section>
      <h3>管理员账号</h3>
      <label>用户名</label>
      <input type="text" name="admin_user" value="<?= htmlspecialchars($_POST['admin_user']??'admin') ?>">
      <label>密码（不少于 6 位）</label>
      <input type="password" name="admin_pass">
    </section>
    <hr class="divider">
    <section>
      <h3>站点信息</h3>
      <label>站点名称</label>
      <input type="text" name="site_name" value="<?= htmlspecialchars($_POST['site_name']??'JumpHost') ?>" placeholder="JumpHost">
    </section>
    <div style="display:flex;gap:12px;margin-top:28px">
      <a href="install.php" style="flex:0 0 auto;padding:13px 20px;border:1.5px solid #e0e0e0;border-radius:10px;color:#666;text-decoration:none;font-size:14px;font-weight:600">← 上一步</a>
      <button type="submit" class="btn" style="margin-top:0;flex:1">开始安装 →</button>
    </div>
  </form>
<?php endif; ?>
<?php page_end(); ?>
