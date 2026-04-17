<?php
/**
 * JumpHost 安装向导
 * 流程：环境检查 → 配置安装 → 完成
 * @copyright 2026 wenyinos <ruojiner@hotmail.com>
 * @license MIT License
 */

$config_file = dirname(__DIR__) . '/config/config.php';
$sql_file    = __DIR__ . '/jumphost.sql';

// ── 已安装检测 ────────────────────────────────────────────
if (file_exists($config_file) && !isset($_GET['force'])) {
    try {
        $cfg = file_get_contents($config_file);
        if (preg_match("/define\('DB_HOST',\s*'(.+?)'/", $cfg, $m) && $m[1] !== '') {
            // ... (验证逻辑保持不变) ...
            preg_match("/define\('DB_PORT',\s*'(.*?)'/", $cfg, $mp);
            preg_match("/define\('DB_NAME',\s*'(.*?)'/", $cfg, $mn);
            preg_match("/define\('DB_USER',\s*'(.*?)'/", $cfg, $mu);
            preg_match("/define\('DB_PASS',\s*'(.*?)'/", $cfg, $mw);
            $testDsn = 'mysql:host='.$m[1].';port='.($mp[1]??'3306').';dbname='.($mn[1]??'').';charset=utf8mb4';
            $testPdo = new PDO($testDsn, $mu[1]??'', $mw[1]??'', [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
            $testPdo->query('SELECT 1 FROM `admins` LIMIT 1');
            show_installed(); exit;
        }
    } catch (Throwable $e) { }
}

function show_installed(): void {
    page_start('系统已安装');
    echo '<div style="text-align:center;padding:20px 0">';
    echo '<div style="font-size:48px;margin-bottom:16px">⚠️</div>';
    echo '<h2 style="color:#c62828;margin-bottom:12px">系统已安装，禁止重复安装</h2>';
    echo '<p style="color:#666;font-size:14px;line-height:1.8">检测到系统已完成安装。<br>如果确需重装，请点击下方按钮（注意：将丢失原有配置）。</p>';
    echo '<div style="margin-top:24px;display:flex;gap:12px;justify-content:center">';
    echo '<a href="/admin/login.php" class="btn" style="text-decoration:none;display:inline-block;width:auto;padding:12px 32px;background:#eee;color:#333">进入后台</a>';
    echo '<a href="?step=2&force=1" class="btn" style="text-decoration:none;display:inline-block;width:auto;padding:12px 32px;background:#c62828">强制重装</a>';
    echo '</div>';
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
    $dbname  = trim($_POST['db_name']    ?? 'host_78rg_cc');
    $user    = trim($_POST['db_user']    ?? '');
    $pass    = $_POST['db_pass']         ?? '';
    $clear_db = isset($_POST['clear_db']) && $_POST['clear_db'] === '1';
    $admin_u = trim($_POST['admin_user'] ?? 'admin');
    $admin_p = $_POST['admin_pass']      ?? '';
    $site_n  = trim($_POST['site_name']  ?? 'JumpHost');

    if (!$user)                  $errors[] = '数据库用户名不能为空';
    if (!$admin_u)               $errors[] = '管理员账号不能为空';
    if (strlen($admin_p) < 6)   $errors[] = '管理员密码不能少于 6 位';

    if (empty($errors)) {
        try {
            $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
            
            if ($clear_db) {
                $pdo->exec("DROP DATABASE IF EXISTS `{$dbname}`");
            }
            
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$dbname}`");

            // 执行 SQL 文件
            $sql_content = file_get_contents($sql_file);
            // 移除注释，替换多余空白
            $sql_content = preg_replace('/--[^\n]*/', '', $sql_content);
            $sql_content = preg_replace('/\s+/', ' ', $sql_content);
            // 按分号分割语句
            $queries = explode(';', $sql_content);
            foreach ($queries as $query) {
                $query = trim($query);
                if ($query !== '') {
                    try {
                        $pdo->exec($query . ';');
                    } catch (Throwable $e) {
                        $msg = $e->getMessage();
                        // 忽略非关键错误
                        if (strpos($msg, 'already exists') === false && strpos($msg, 'Duplicate') === false) {
                            // 可选：记录错误
                        }
                    }
                }
            }

            // 更新管理员
            $pdo->prepare("UPDATE `admins` SET `username`=?,`password`=? WHERE `id`=1")
                ->execute([$admin_u, password_hash($admin_p, PASSWORD_BCRYPT)]);
            $pdo->prepare("INSERT INTO `settings`(`key`,`value`) VALUES('site_name',?) ON DUPLICATE KEY UPDATE `value`=?")
                ->execute([$site_n, $site_n]);

            // 写 config.php
            $content = <<<PHP
<?php
/**
 * @copyright 2026 wenyinos <ruojiner@hotmail.com>
 * @license MIT License
 */
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
require_once __DIR__ . '/ip_api_config.php';

function get_db(): PDO {
    static \$pdo = null;
    if (\$pdo === null) {
        \$dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );
        \$pdo = new PDO(\$dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return \$pdo;
}

// ─── Session 初始化 ────────────────────────────────────────
function session_start_safe(): void {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_strict_mode', 1);
        session_start();
    }
}

// ─── 管理员鉴权 ────────────────────────────────────────────
function admin_auth(): void {
    session_start_safe();
    if (empty(\$_SESSION['admin_id'])) {
        header('Location: ' . ADMIN_PREFIX . '/login.php');
        exit;
    }
    if (isset(\$_SESSION['last_active']) && time() - \$_SESSION['last_active'] > SESSION_LIFETIME) {
        session_unset(); session_destroy();
        header('Location: ' . ADMIN_PREFIX . '/login.php?timeout=1');
        exit;
    }
    \$_SESSION['last_active'] = time();
}

/** 当前用户角色 */
function current_role(): string { return \$_SESSION['admin_role'] ?? 'personal'; }

/** 当前用户 ID */
function current_uid(): int { return (int)(\$_SESSION['admin_id'] ?? 0); }

/** 是否超级管理员 */
function is_super(): bool { return current_role() === 'super'; }

/** 是否代理 */
function is_agent(): bool { return current_role() === 'agent'; }

/** 是否超级管理员或代理 */
function is_super_or_agent(): bool { return in_array(current_role(), ['super','agent']); }

/** 角色守卫 */
function role_guard(\$roles): void {
    if (is_string(\$roles)) \$roles = [\$roles];
    if (!in_array(current_role(), \$roles)) {
        http_response_code(403); exit('无权限访问');
    }
}

/** 域名所有权过滤 */
function domain_owner_where(string \$alias = ''): array {
    \$col = \$alias ? "{\$alias}.`owner_id`" : '`owner_id`';
    if (is_super()) return ['1=1', []];
    if (is_agent()) {
        try {
            \$ids = get_db()->prepare("SELECT `id` FROM `admins` WHERE `owner_id`=? AND `role`='personal'");
            \$ids->execute([current_uid()]);
            \$sub = array_column(\$ids->fetchAll(), 'id');
        } catch (Throwable \$e) { \$sub = []; }
        \$sub[] = current_uid();
        \$ph = implode(',', array_fill(0, count(\$sub), '?'));
        return ["{\$col} IN ({\$ph})", \$sub];
    }
    return ["{\$col}=?", [current_uid()]];
}

/** 用户所有权过滤 */
function user_owner_where(): array {
    if (is_super()) return ['1=1', []];
    if (is_agent()) return ["`owner_id`=? AND `role`='personal'", [current_uid()]];
    return ['`id`=?', [current_uid()]];
}

// ─── CSRF Token ────────────────────────────────────────────
function csrf_token(): string {
    session_start_safe();
    if (empty(\$_SESSION['csrf_token'])) {
        \$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return \$_SESSION['csrf_token'];
}

function csrf_verify(): void {
    session_start_safe();
    \$token = \$_POST['csrf_token'] ?? '';
    if (!hash_equals(\$_SESSION['csrf_token'] ?? '', \$token)) {
        http_response_code(403); exit('CSRF verification failed.');
    }
}

// ─── 全局工具函数 ──────────────────────────────────────────
function get_site_name(): string {
    static \$name = null;
    if (\$name === null) {
        try {
            \$stmt = get_db()->prepare("SELECT `value` FROM `settings` WHERE `key` = 'site_name'");
            \$stmt->execute();
            \$row  = \$stmt->fetch();
            \$name = \$row ? \$row['value'] : APP_NAME;
        } catch (Throwable \$e) { \$name = APP_NAME; }
    }
    return \$name;
}

function get_setting(string \$key, string \$default = ''): string {
    try {
        \$stmt = get_db()->prepare("SELECT `value` FROM `settings` WHERE `key` = ?");
        \$stmt->execute([\$key]);
        \$row = \$stmt->fetch();
        return \$row ? \$row['value'] : \$default;
    } catch (Throwable \$e) { return \$default; }
}

function set_setting(string \$key, string \$value): void {
    get_db()->prepare("INSERT INTO `settings` (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)")
           ->execute([\$key, \$value]);
}

function is_valid_url(string \$url): bool {
    return (bool) filter_var(\$url, FILTER_VALIDATE_URL) && in_array(parse_url(\$url, PHP_URL_SCHEME), ['http', 'https'], true);
}

function is_valid_asset_url(string \$url): bool {
    \$url = trim(\$url);
    if (\$url === '') return false;
    if (\$url[0] === '/' && substr(\$url, 0, 2) !== '//') return true;
    return is_valid_url(\$url);
}

function normalize_nav_icon(string \$icon): string {
    \$icon = trim(\$icon);
    if (\$icon === '' || is_valid_asset_url(\$icon)) return \$icon;
    \$icon = strip_tags(\$icon);
    return function_exists('mb_substr') ? mb_substr(\$icon, 0, 6, 'UTF-8') : substr(\$icon, 0, 6);
}

function decode_nav_payload(string \$raw): array {
    \$raw = trim(\$raw);
    if (\$raw === '') return ['links' => [], 'meta' => []];
    \$decoded = json_decode(\$raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array(\$decoded)) return ['links' => [], 'meta' => []];
    \$meta = [];
    if (isset(\$decoded['links']) && is_array(\$decoded['links'])) {
        \$meta = isset(\$decoded['meta']) && is_array(\$decoded['meta']) ? \$decoded['meta'] : [];
        \$decoded = \$decoded['links'];
    } elseif (isset(\$decoded['items']) && is_array(\$decoded['items'])) {
        \$meta = isset(\$decoded['meta']) && is_array(\$decoded['meta']) ? \$decoded['meta'] : [];
        \$decoded = \$decoded['items'];
    }
    if (isset(\$decoded['url']) || isset(\$decoded['name']) || isset(\$decoded['icon'])) \$decoded = [\$decoded];
    return ['links' => is_array(\$decoded) ? \$decoded : [], 'meta' => \$meta];
}

function normalize_nav_links(string \$raw, int \$max = 10): array {
    \$raw = trim(\$raw); if (\$raw === '') return [];
    \$payload = decode_nav_payload(\$raw); \$decoded = \$payload['links'];
    if (empty(\$decoded)) return is_valid_url(\$raw) ? [['name' => '默认链接', 'url' => \$raw]] : [];
    \$links = [];
    foreach (\$decoded as \$item) {
        if (!is_array(\$item)) continue;
        \$url = trim((string)(\$item['url'] ?? '')); if (\$url === '' || !is_valid_url(\$url)) continue;
        \$name = trim((string)(\$item['name'] ?? '')); if (\$name === '') \$name = '链接 ' . (count(\$links) + 1);
        \$name = function_exists('mb_substr') ? mb_substr(\$name, 0, 50, 'UTF-8') : substr(\$name, 0, 50);
        \$link = ['name' => \$name, 'url' => \$url];
        \$icon = normalize_nav_icon((string)(\$item['icon'] ?? '')); if (\$icon !== '') \$link['icon'] = \$icon;
        \$group = trim((string)(\$item['group'] ?? '')); if (in_array(\$group, ['official','download','backup','service'])) \$link['group'] = \$group;
        \$links[] = \$link; if (count(\$links) >= \$max) break;
    }
    return \$links;
}

function e(string \$str): string { return htmlspecialchars(\$str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function write_admin_log(string \$action): void {
    try {
        session_start_safe();
        \$admin_id = (int)(\$_SESSION['admin_id'] ?? 0);
        \$username = \$_SESSION['admin_username'] ?? '';
        \$ip = trim(explode(',', \$_SERVER['HTTP_X_FORWARDED_FOR'] ?? \$_SERVER['REMOTE_ADDR'] ?? '')[0]);
        \$ua = substr(\$_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
        \$domain = \$_SERVER['HTTP_HOST'] ?? '';
        \$location = (!empty(\$ip) && \$ip !== '127.0.0.1') ? get_ip_location(\$ip) : '';
        get_db()->prepare('INSERT INTO `admin_logs` (`admin_id`,`username`,`ip`,`location`,`domain`,`ua`,`action`) VALUES (?,?,?,?,?,?,?)')
               ->execute([\$admin_id, \$username, \$ip, \$location, \$domain, \$ua, \$action]);
    } catch (Throwable \$e) { error_log('write_admin_log error: ' . \$e->getMessage()); }
}

function get_ip_info(string \$ip): array {
    static \$cache = []; static \$table_checked = false; \$key = \$ip;
    if (isset(\$cache[\$key])) return \$cache[\$key];
    \$result = ['location' => '', 'isp' => ''];
    if (!defined('IP_LOCATION_ENABLED') || !IP_LOCATION_ENABLED) { \$cache[\$key] = \$result; return \$result; }
    if (\$ip === '127.0.0.1' || strpos(\$ip, '192.168.') === 0 || strpos(\$ip, '10.') === 0) { \$result['location'] = '本地'; \$cache[\$key] = \$result; return \$result; }
    if (!\$table_checked) {
        try { get_db()->exec("CREATE TABLE IF NOT EXISTS `ip_cache` (`ip` varchar(45) NOT NULL, `location` varchar(100) NOT NULL DEFAULT '', `isp` varchar(50) NOT NULL DEFAULT '', `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (`ip`), KEY `idx_updated_at` (`updated_at`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"); } catch (Throwable \$e) {}
        \$table_checked = true;
    }
    try {
        \$stmt = get_db()->prepare("SELECT `location`, `isp` FROM `ip_cache` WHERE `ip` = ? LIMIT 1");
        \$stmt->execute([\$ip]); \$row = \$stmt->fetch();
        if (\$row && \$row['location'] !== '') { \$result['location'] = \$row['location']; \$result['isp'] = \$row['isp'] ?? ''; \$cache[\$key] = \$result; return \$result; }
    } catch (Throwable \$e) {}
    \$location = ''; \$isp = '';
    try {
        \$api_url = IP_API_URL . '?ip=' . urlencode(\$ip);
        \$ctx = stream_context_create(['http' => ['timeout' => IP_API_TIMEOUT, 'ignore_errors' => true]]);
        \$response = @file_get_contents(\$api_url, false, \$ctx);
        if (\$response) { \$data = json_decode(\$response, true); if (\$data && (\$data['code'] ?? 0) === 200) { \$location = \$data['data']['location']['desc'] ?? ''; \$isp = \$data['data']['isp'] ?? ''; } }
    } catch (Throwable \$e) {}
    if (\$location === '' && defined('IP_API_FALLBACK_URL')) {
        try {
            \$fallback_url = IP_API_FALLBACK_URL . urlencode(\$ip) . '?lang=zh-CN&fields=status,message,regionName,city,isp';
            \$ctx2 = stream_context_create(['http' => ['timeout' => IP_API_FALLBACK_TIMEOUT, 'ignore_errors' => true]]);
            \$response2 = @file_get_contents(\$fallback_url, false, \$ctx2);
            if (\$response2) {
                \$data2 = json_decode(\$response2, true);
                if (\$data2 && (\$data2['status'] ?? '') === 'success') {
                    \$parts = array_filter([\$data2['regionName'] ?? '', \$data2['city'] ?? ''], function(\$v) { return \$v !== ''; });
                    \$location = implode(' ', \$parts); \$isp = \$data2['isp'] ?? '';
                }
            }
        } catch (Throwable \$e) {}
    }
    if (\$location !== '') {
        try { get_db()->prepare("INSERT INTO `ip_cache` (`ip`, `location`, `isp`) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE `location` = VALUES(`location`), `isp` = VALUES(`isp`)")->execute([\$ip, \$location, \$isp]); }
        catch (Throwable \$e) {}
    }
    \$result['location'] = \$location; \$result['isp'] = \$isp; \$cache[\$key] = \$result; return \$result;
}

function get_ip_location(string \$ip): string {
    return get_ip_info(\$ip)['location'];
}

function parse_user_agent(string \$ua): array {
    \$ua = trim(\$ua); \$result = ['device' => '未知', 'browser' => '未知', 'os' => '未知'];
    if (\$ua === '') return \$result;
    if (preg_match('/iPad/i', \$ua)) { \$result['device'] = '平板'; }
    elseif (preg_match('/Mobile|Android|iPhone|iPod|BlackBerry|IEMobile|Opera Mini/i', \$ua)) { \$result['device'] = '手机'; }
    elseif (preg_match('/Tablet|PlayBook|Silk/i', \$ua)) { \$result['device'] = '平板'; }
    else { \$result['device'] = '电脑'; }
    if (preg_match('/Windows NT (\d+\.?\d*)/i', \$ua, \$m)) {
        \$ver = \$m[1]; \$map = ['10.0' => '10', '6.3' => '8.1', '6.2' => '8', '6.1' => '7', '6.0' => 'Vista', '5.1' => 'XP'];
        \$result['os'] = 'Windows ' . (\$map[\$ver] ?? \$ver);
    } elseif (preg_match('/Mac OS X (\d+[._]\d+)/i', \$ua, \$m)) { \$result['os'] = 'macOS ' . str_replace('_', '.', \$m[1]); }
    elseif (preg_match('/Android (\d+\.?\d*)/i', \$ua, \$m)) { \$result['os'] = 'Android ' . \$m[1]; }
    elseif (preg_match('/iPhone OS (\d+)/i', \$ua, \$m)) { \$result['os'] = 'iOS ' . \$m[1]; }
    elseif (preg_match('/Linux/i', \$ua)) { \$result['os'] = 'Linux'; }
    elseif (preg_match('/CrOS/i', \$ua)) { \$result['os'] = 'ChromeOS'; }
    if (preg_match('/Edg[ea]?\/(\d+)/i', \$ua, \$m)) { \$result['browser'] = 'Edge ' . \$m[1]; }
    elseif (preg_match('/Chrome\/(\d+)/i', \$ua, \$m) && !preg_match('/Chromium|Edg|OPR|Opera|Vivaldi|Brave/i', \$ua)) { \$result['browser'] = 'Chrome ' . \$m[1]; }
    elseif (preg_match('/Firefox\/(\d+)/i', \$ua, \$m)) { \$result['browser'] = 'Firefox ' . \$m[1]; }
    elseif (preg_match('/Safari\/(\d+)/i', \$ua, \$m) && !preg_match('/Chrome/i', \$ua)) { \$result['browser'] = 'Safari ' . \$m[1]; }
    elseif (preg_match('/OPR\/(\d+)/i', \$ua, \$m)) { \$result['browser'] = 'Opera ' . \$m[1]; }
    elseif (preg_match('/MSIE (\d+)/i', \$ua, \$m)) { \$result['browser'] = 'IE ' . \$m[1]; }
    elseif (preg_match('/Trident.*rv:(\d+)/i', \$ua, \$m)) { \$result['browser'] = 'IE ' . \$m[1]; }
    return \$result;
}

function paginate(int \$total, int \$page, int \$per_page = 20): array {
    \$total_pages = max(1, (int) ceil(\$total / \$per_page));
    \$page = max(1, min(\$page, \$total_pages));
    return ['total'=>\$total, 'per_page'=>\$per_page, 'page'=>\$page, 'total_pages'=>\$total_pages, 'offset'=>(\$page - 1) * \$per_page];
}

function get_templates(): array {
    static \$templates = null; if (\$templates !== null) return \$templates;
    \$templates = []; \$template_dir = TEMPLATE_PATH; if (!is_dir(\$template_dir)) return \$templates;
    \$files = glob(\$template_dir . '/*.php');
    \$allowed_templates = ['img', 'delay', 'click_delay'];
    foreach (\$files as \$file) {
        \$name = basename(\$file, '.php'); if (!in_array(\$name, \$allowed_templates)) continue;
        \$content = file_get_contents(\$file, false, null, 0, 800);
        \$config = ['name' => \$name, 'label' => \$name, 'fields' => []];
        if (preg_match('/@label\s+(.+?)(?:\n|\$)/i', \$content, \$m)) \$config['label'] = trim(\$m[1]);
        if (preg_match('/@fields\s+(.+?)(?:\n|\$)/i', \$content, \$m)) \$config['fields'] = array_filter(array_map('trim', explode(',', \$m[1])));
        \$templates[\$name] = \$config;
    }
    ksort(\$templates); return \$templates;
}
PHP;
            file_put_contents($config_file, $content);

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
      
      <div style="margin-top:16px;display:flex;align-items:center;gap:8px;background:#fff5f5;padding:12px;border-radius:8px;border:1px solid #fed7d7">
        <input type="checkbox" name="clear_db" value="1" style="width:16px;height:16px;cursor:pointer">
        <span style="font-size:13px;color:#c53030;font-weight:600">清空原有数据库并继续安装（慎选！）</span>
      </div>
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
