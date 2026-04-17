<?php
/**
 * @copyright 2026 wenyinos <ruojiner@hotmail.com>
 * @license MIT License
 */
define('DB_HOST',    'localhost');
define('DB_PORT',    '3306');
define('DB_NAME',    '78rg_test');
define('DB_USER',    '78rg_test');
define('DB_PASS',    'f8MHHYEYaSJrCJ6d');
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
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
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
    if (empty($_SESSION['admin_id'])) {
        header('Location: ' . ADMIN_PREFIX . '/login.php');
        exit;
    }
    if (isset($_SESSION['last_active']) && time() - $_SESSION['last_active'] > SESSION_LIFETIME) {
        session_unset(); session_destroy();
        header('Location: ' . ADMIN_PREFIX . '/login.php?timeout=1');
        exit;
    }
    $_SESSION['last_active'] = time();
}

/** 当前用户角色 */
function current_role(): string { return $_SESSION['admin_role'] ?? 'personal'; }

/** 当前用户 ID */
function current_uid(): int { return (int)($_SESSION['admin_id'] ?? 0); }

/** 是否超级管理员 */
function is_super(): bool { return current_role() === 'super'; }

/** 是否代理 */
function is_agent(): bool { return current_role() === 'agent'; }

/** 是否超级管理员或代理 */
function is_super_or_agent(): bool { return in_array(current_role(), ['super','agent']); }

/** 角色守卫 */
function role_guard($roles): void {
    if (is_string($roles)) $roles = [$roles];
    if (!in_array(current_role(), $roles)) {
        http_response_code(403); exit('无权限访问');
    }
}

/** 域名所有权过滤 */
function domain_owner_where(string $alias = ''): array {
    $col = $alias ? "{$alias}.`owner_id`" : '`owner_id`';
    if (is_super()) return ['1=1', []];
    if (is_agent()) {
        try {
            $ids = get_db()->prepare("SELECT `id` FROM `admins` WHERE `owner_id`=? AND `role`='personal'");
            $ids->execute([current_uid()]);
            $sub = array_column($ids->fetchAll(), 'id');
        } catch (Throwable $e) { $sub = []; }
        $sub[] = current_uid();
        $ph = implode(',', array_fill(0, count($sub), '?'));
        return ["{$col} IN ({$ph})", $sub];
    }
    return ["{$col}=?", [current_uid()]];
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
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(): void {
    session_start_safe();
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403); exit('CSRF verification failed.');
    }
}

// ─── 全局工具函数 ──────────────────────────────────────────
function get_site_name(): string {
    static $name = null;
    if ($name === null) {
        try {
            $stmt = get_db()->prepare("SELECT `value` FROM `settings` WHERE `key` = 'site_name'");
            $stmt->execute();
            $row  = $stmt->fetch();
            $name = $row ? $row['value'] : APP_NAME;
        } catch (Throwable $e) { $name = APP_NAME; }
    }
    return $name;
}

function get_setting(string $key, string $default = ''): string {
    try {
        $stmt = get_db()->prepare("SELECT `value` FROM `settings` WHERE `key` = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? $row['value'] : $default;
    } catch (Throwable $e) { return $default; }
}

function set_setting(string $key, string $value): void {
    get_db()->prepare("INSERT INTO `settings` (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)")
           ->execute([$key, $value]);
}

function is_valid_url(string $url): bool {
    return (bool) filter_var($url, FILTER_VALIDATE_URL) && in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true);
}

function is_valid_asset_url(string $url): bool {
    $url = trim($url);
    if ($url === '') return false;
    if ($url[0] === '/' && substr($url, 0, 2) !== '//') return true;
    return is_valid_url($url);
}

function normalize_nav_icon(string $icon): string {
    $icon = trim($icon);
    if ($icon === '' || is_valid_asset_url($icon)) return $icon;
    $icon = strip_tags($icon);
    return function_exists('mb_substr') ? mb_substr($icon, 0, 6, 'UTF-8') : substr($icon, 0, 6);
}

function decode_nav_payload(string $raw): array {
    $raw = trim($raw);
    if ($raw === '') return ['links' => [], 'meta' => []];
    $decoded = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) return ['links' => [], 'meta' => []];
    $meta = [];
    if (isset($decoded['links']) && is_array($decoded['links'])) {
        $meta = isset($decoded['meta']) && is_array($decoded['meta']) ? $decoded['meta'] : [];
        $decoded = $decoded['links'];
    } elseif (isset($decoded['items']) && is_array($decoded['items'])) {
        $meta = isset($decoded['meta']) && is_array($decoded['meta']) ? $decoded['meta'] : [];
        $decoded = $decoded['items'];
    }
    if (isset($decoded['url']) || isset($decoded['name']) || isset($decoded['icon'])) $decoded = [$decoded];
    return ['links' => is_array($decoded) ? $decoded : [], 'meta' => $meta];
}

function normalize_nav_links(string $raw, int $max = 10): array {
    $raw = trim($raw); if ($raw === '') return [];
    $payload = decode_nav_payload($raw); $decoded = $payload['links'];
    if (empty($decoded)) return is_valid_url($raw) ? [['name' => '默认链接', 'url' => $raw]] : [];
    $links = [];
    foreach ($decoded as $item) {
        if (!is_array($item)) continue;
        $url = trim((string)($item['url'] ?? '')); if ($url === '' || !is_valid_url($url)) continue;
        $name = trim((string)($item['name'] ?? '')); if ($name === '') $name = '链接 ' . (count($links) + 1);
        $name = function_exists('mb_substr') ? mb_substr($name, 0, 50, 'UTF-8') : substr($name, 0, 50);
        $link = ['name' => $name, 'url' => $url];
        $icon = normalize_nav_icon((string)($item['icon'] ?? '')); if ($icon !== '') $link['icon'] = $icon;
        $group = trim((string)($item['group'] ?? '')); if (in_array($group, ['official','download','backup','service'])) $link['group'] = $group;
        $links[] = $link; if (count($links) >= $max) break;
    }
    return $links;
}

function e(string $str): string { return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function write_admin_log(string $action): void {
    try {
        session_start_safe();
        $admin_id = (int)($_SESSION['admin_id'] ?? 0);
        $username = $_SESSION['admin_username'] ?? '';
        $ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '')[0]);
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
        $domain = $_SERVER['HTTP_HOST'] ?? '';
        $location = (!empty($ip) && $ip !== '127.0.0.1') ? get_ip_location($ip) : '';
        get_db()->prepare('INSERT INTO `admin_logs` (`admin_id`,`username`,`ip`,`location`,`domain`,`ua`,`action`) VALUES (?,?,?,?,?,?,?)')
               ->execute([$admin_id, $username, $ip, $location, $domain, $ua, $action]);
    } catch (Throwable $e) { error_log('write_admin_log error: ' . $e->getMessage()); }
}

function get_ip_info(string $ip): array {
    static $cache = []; static $table_checked = false; $key = $ip;
    if (isset($cache[$key])) return $cache[$key];
    $result = ['location' => '', 'isp' => ''];
    if (!defined('IP_LOCATION_ENABLED') || !IP_LOCATION_ENABLED) { $cache[$key] = $result; return $result; }
    if ($ip === '127.0.0.1' || strpos($ip, '192.168.') === 0 || strpos($ip, '10.') === 0) { $result['location'] = '本地'; $cache[$key] = $result; return $result; }
    if (!$table_checked) {
        try { get_db()->exec("CREATE TABLE IF NOT EXISTS `ip_cache` (`ip` varchar(45) NOT NULL, `location` varchar(100) NOT NULL DEFAULT '', `isp` varchar(50) NOT NULL DEFAULT '', `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (`ip`), KEY `idx_updated_at` (`updated_at`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"); } catch (Throwable $e) {}
        $table_checked = true;
    }
    try {
        $stmt = get_db()->prepare("SELECT `location`, `isp` FROM `ip_cache` WHERE `ip` = ? LIMIT 1");
        $stmt->execute([$ip]); $row = $stmt->fetch();
        if ($row && $row['location'] !== '') { $result['location'] = $row['location']; $result['isp'] = $row['isp'] ?? ''; $cache[$key] = $result; return $result; }
    } catch (Throwable $e) {}
    $location = ''; $isp = '';
    try {
        $api_url = IP_API_URL . '?ip=' . urlencode($ip);
        $ctx = stream_context_create(['http' => ['timeout' => IP_API_TIMEOUT, 'ignore_errors' => true]]);
        $response = @file_get_contents($api_url, false, $ctx);
        if ($response) { $data = json_decode($response, true); if ($data && ($data['code'] ?? 0) === 200) { $location = $data['data']['location']['desc'] ?? ''; $isp = $data['data']['isp'] ?? ''; } }
    } catch (Throwable $e) {}
    if ($location === '' && defined('IP_API_FALLBACK_URL')) {
        try {
            $fallback_url = IP_API_FALLBACK_URL . urlencode($ip) . '?lang=zh-CN&fields=status,message,regionName,city,isp';
            $ctx2 = stream_context_create(['http' => ['timeout' => IP_API_FALLBACK_TIMEOUT, 'ignore_errors' => true]]);
            $response2 = @file_get_contents($fallback_url, false, $ctx2);
            if ($response2) {
                $data2 = json_decode($response2, true);
                if ($data2 && ($data2['status'] ?? '') === 'success') {
                    $parts = array_filter([$data2['regionName'] ?? '', $data2['city'] ?? ''], function($v) { return $v !== ''; });
                    $location = implode(' ', $parts); $isp = $data2['isp'] ?? '';
                }
            }
        } catch (Throwable $e) {}
    }
    if ($location !== '') {
        try { get_db()->prepare("INSERT INTO `ip_cache` (`ip`, `location`, `isp`) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE `location` = VALUES(`location`), `isp` = VALUES(`isp`)")->execute([$ip, $location, $isp]); }
        catch (Throwable $e) {}
    }
    $result['location'] = $location; $result['isp'] = $isp; $cache[$key] = $result; return $result;
}

function get_ip_location(string $ip): string {
    return get_ip_info($ip)['location'];
}

function parse_user_agent(string $ua): array {
    $ua = trim($ua); $result = ['device' => '未知', 'browser' => '未知', 'os' => '未知'];
    if ($ua === '') return $result;
    if (preg_match('/iPad/i', $ua)) { $result['device'] = '平板'; }
    elseif (preg_match('/Mobile|Android|iPhone|iPod|BlackBerry|IEMobile|Opera Mini/i', $ua)) { $result['device'] = '手机'; }
    elseif (preg_match('/Tablet|PlayBook|Silk/i', $ua)) { $result['device'] = '平板'; }
    else { $result['device'] = '电脑'; }
    if (preg_match('/Windows NT (\d+\.?\d*)/i', $ua, $m)) {
        $ver = $m[1]; $map = ['10.0' => '10', '6.3' => '8.1', '6.2' => '8', '6.1' => '7', '6.0' => 'Vista', '5.1' => 'XP'];
        $result['os'] = 'Windows ' . ($map[$ver] ?? $ver);
    } elseif (preg_match('/Mac OS X (\d+[._]\d+)/i', $ua, $m)) { $result['os'] = 'macOS ' . str_replace('_', '.', $m[1]); }
    elseif (preg_match('/Android (\d+\.?\d*)/i', $ua, $m)) { $result['os'] = 'Android ' . $m[1]; }
    elseif (preg_match('/iPhone OS (\d+)/i', $ua, $m)) { $result['os'] = 'iOS ' . $m[1]; }
    elseif (preg_match('/Linux/i', $ua)) { $result['os'] = 'Linux'; }
    elseif (preg_match('/CrOS/i', $ua)) { $result['os'] = 'ChromeOS'; }
    if (preg_match('/Edg[ea]?\/(\d+)/i', $ua, $m)) { $result['browser'] = 'Edge ' . $m[1]; }
    elseif (preg_match('/Chrome\/(\d+)/i', $ua, $m) && !preg_match('/Chromium|Edg|OPR|Opera|Vivaldi|Brave/i', $ua)) { $result['browser'] = 'Chrome ' . $m[1]; }
    elseif (preg_match('/Firefox\/(\d+)/i', $ua, $m)) { $result['browser'] = 'Firefox ' . $m[1]; }
    elseif (preg_match('/Safari\/(\d+)/i', $ua, $m) && !preg_match('/Chrome/i', $ua)) { $result['browser'] = 'Safari ' . $m[1]; }
    elseif (preg_match('/OPR\/(\d+)/i', $ua, $m)) { $result['browser'] = 'Opera ' . $m[1]; }
    elseif (preg_match('/MSIE (\d+)/i', $ua, $m)) { $result['browser'] = 'IE ' . $m[1]; }
    elseif (preg_match('/Trident.*rv:(\d+)/i', $ua, $m)) { $result['browser'] = 'IE ' . $m[1]; }
    return $result;
}

function paginate(int $total, int $page, int $per_page = 20): array {
    $total_pages = max(1, (int) ceil($total / $per_page));
    $page = max(1, min($page, $total_pages));
    return ['total'=>$total, 'per_page'=>$per_page, 'page'=>$page, 'total_pages'=>$total_pages, 'offset'=>($page - 1) * $per_page];
}

function get_templates(): array {
    static $templates = null; if ($templates !== null) return $templates;
    $templates = []; $template_dir = TEMPLATE_PATH; if (!is_dir($template_dir)) return $templates;
    $files = glob($template_dir . '/*.php');
    $allowed_templates = ['img', 'delay', 'click_delay'];
    foreach ($files as $file) {
        $name = basename($file, '.php'); if (!in_array($name, $allowed_templates)) continue;
        $content = file_get_contents($file, false, null, 0, 800);
        $config = ['name' => $name, 'label' => $name, 'fields' => []];
        if (preg_match('/@label\s+(.+?)(?:
|$)/i', $content, $m)) $config['label'] = trim($m[1]);
        if (preg_match('/@fields\s+(.+?)(?:
|$)/i', $content, $m)) $config['fields'] = array_filter(array_map('trim', explode(',', $m[1])));
        $templates[$name] = $config;
    }
    ksort($templates); return $templates;
}