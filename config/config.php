<?php
/**
 * @copyright 2026 wenyinos <ruojiner@hotmail.com>
 * @license MIT License
 */
define('DB_HOST',    'localhost');
define('DB_PORT',    '3306');
define('DB_NAME',    'host_78rg_cc');
define('DB_USER',    'host_78rg_cc');
define('DB_PASS',    '9aiapPZGMydpYFYh');
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
    // 超时检测
    if (isset($_SESSION['last_active']) && time() - $_SESSION['last_active'] > SESSION_LIFETIME) {
        session_unset();
        session_destroy();
        header('Location: ' . ADMIN_PREFIX . '/login.php?timeout=1');
        exit;
    }
    $_SESSION['last_active'] = time();
}

/** 当前用户角色 */
function current_role(): string {
    return $_SESSION['admin_role'] ?? 'personal';
}

/** 当前用户 ID */
function current_uid(): int {
    return (int)($_SESSION['admin_id'] ?? 0);
}

/** 是否超级管理员 */
function is_super(): bool { return current_role() === 'super'; }

/** 是否代理 */
function is_agent(): bool { return current_role() === 'agent'; }

/** 是否超级管理员或代理 */
function is_super_or_agent(): bool { return in_array(current_role(), ['super','agent']); }

/**
 * 角色守卫：不满足则返回 403
 * @param string|array $roles 允许的角色
 */
function role_guard($roles): void {
    if (is_string($roles)) $roles = [$roles];
    if (!in_array(current_role(), $roles)) {
        http_response_code(403);
        exit('无权限访问');
    }
}

/**
 * 构建域名查询的 owner 过滤条件
 * super: 全部; agent: 自己 + 下属 personal; personal: 仅自己
 */
function domain_owner_where(string $alias = ''): array {
    $col = $alias ? "{$alias}.`owner_id`" : '`owner_id`';
    if (is_super()) return ['1=1', []];
    if (is_agent()) {
        // 自己 + 自己创建的 personal 用户
        try {
            $ids = get_db()->prepare("SELECT `id` FROM `admins` WHERE `owner_id`=? AND `role`='personal'");
            $ids->execute([current_uid()]);
            $sub = array_column($ids->fetchAll(), 'id');
        } catch (Throwable $e) { $sub = []; }
        $sub[] = current_uid();
        $ph = implode(',', array_fill(0, count($sub), '?'));
        return ["{$col} IN ({$ph})", $sub];
    }
    // personal
    return ["{$col}=?", [current_uid()]];
}

/**
 * 构建用户查询的 owner 过滤
 * super: 全部; agent: 自己创建的 personal; personal: 无权查看他人
 */
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
        http_response_code(403);
        exit('CSRF verification failed.');
    }
}

// ─── 全局工具函数 ──────────────────────────────────────────

/** 获取站点名称（从 settings 表读取，失败则用默认值） */
function get_site_name(): string {
    static $name = null;
    if ($name === null) {
        try {
            $stmt = get_db()->prepare("SELECT `value` FROM `settings` WHERE `key` = 'site_name'");
            $stmt->execute();
            $row  = $stmt->fetch();
            $name = $row ? $row['value'] : APP_NAME;
        } catch (Throwable $e) {
            $name = APP_NAME;
        }
    }
    return $name;
}

/** 获取任意 settings 值 */
function get_setting(string $key, string $default = ''): string {
    try {
        $stmt = get_db()->prepare("SELECT `value` FROM `settings` WHERE `key` = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? $row['value'] : $default;
    } catch (Throwable $e) {
        return $default;
    }
}

/** 写入 settings */
function set_setting(string $key, string $value): void {
    $db = get_db();
    $stmt = $db->prepare(
        "INSERT INTO `settings` (`key`, `value`) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)"
    );
    $stmt->execute([$key, $value]);
}

/** 验证 URL 是否合法 */
function is_valid_url(string $url): bool {
    return (bool) filter_var($url, FILTER_VALIDATE_URL)
        && in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true);
}

/** 验证资源 URL 是否合法（支持站内绝对路径） */
function is_valid_asset_url(string $url): bool {
    $url = trim($url);
    if ($url === '') {
        return false;
    }
    if ($url[0] === '/' && substr($url, 0, 2) !== '//') {
        return true;
    }
    return is_valid_url($url);
}

/** 标准化导航图标，支持 emoji 文本和图片 URL */
function normalize_nav_icon(string $icon): string {
    $icon = trim($icon);
    if ($icon === '') {
        return '';
    }
    if (is_valid_asset_url($icon)) {
        return $icon;
    }

    $icon = strip_tags($icon);
    if (function_exists('mb_substr')) {
        return mb_substr($icon, 0, 6, 'UTF-8');
    }
    return substr($icon, 0, 6);
}

/** 解析导航模板的原始配置，支持 links/meta 结构 */
function decode_nav_payload(string $raw): array {
    $raw = trim($raw);
    if ($raw === '') {
        return ['links' => [], 'meta' => []];
    }

    $decoded = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
        return ['links' => [], 'meta' => []];
    }

    $meta = [];
    if (isset($decoded['links']) && is_array($decoded['links'])) {
        $meta = isset($decoded['meta']) && is_array($decoded['meta']) ? $decoded['meta'] : [];
        $decoded = $decoded['links'];
    } elseif (isset($decoded['items']) && is_array($decoded['items'])) {
        $meta = isset($decoded['meta']) && is_array($decoded['meta']) ? $decoded['meta'] : [];
        $decoded = $decoded['items'];
    }

    if (isset($decoded['url']) || isset($decoded['name']) || isset($decoded['icon'])) {
        $decoded = [$decoded];
    }

    return [
        'links' => is_array($decoded) ? $decoded : [],
        'meta' => $meta,
    ];
}

/** 黑金品牌页默认可配置项 */
function default_blackgold_meta(): array {
    return [
        'brand_subtitle' => '官方访问入口',
        'headline_line1' => '多线路访问体验',
        'headline_line2_prefix' => '尽在',
        'headline_line2_highlight' => '品牌导航',
        'hero_bars' => [],
        'promo_tags' => [
            ['style' => 'red', 'text' => '官方推荐'],
            ['style' => 'gold', 'text' => '多线访问'],
            ['style' => 'green', 'text' => '移动适配'],
            ['style' => 'cyan', 'text' => '快速打开'],
        ],
        'categories' => [
            ['label' => '体育', 'icon' => ''],
            ['label' => '电竞', 'icon' => ''],
            ['label' => '真人', 'icon' => ''],
            ['label' => '电子', 'icon' => ''],
            ['label' => '彩票', 'icon' => ''],
            ['label' => '棋牌', 'icon' => ''],
        ],
    ];
}

/** 黑金品牌页配置清洗 */
function sanitize_blackgold_meta(array $meta): array {
    $defaults = default_blackgold_meta();
    $clean_text = static function ($value, int $limit, string $fallback = ''): string {
        $value = trim(strip_tags((string)$value));
        if ($value === '') {
            return $fallback;
        }
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $limit, 'UTF-8');
        }
        return substr($value, 0, $limit);
    };

    $result = [
        'brand_subtitle' => $clean_text($meta['brand_subtitle'] ?? '', 30, $defaults['brand_subtitle']),
        'headline_line1' => $clean_text($meta['headline_line1'] ?? '', 30, $defaults['headline_line1']),
        'headline_line2_prefix' => $clean_text($meta['headline_line2_prefix'] ?? '', 20, $defaults['headline_line2_prefix']),
        'headline_line2_highlight' => $clean_text($meta['headline_line2_highlight'] ?? '', 20, $defaults['headline_line2_highlight']),
        'hero_bars' => [],
        'promo_tags' => [],
        'categories' => [],
    ];

    $hero_bars = is_array($meta['hero_bars'] ?? null) ? $meta['hero_bars'] : [];
    foreach ($hero_bars as $item) {
        $text = $clean_text($item, 40, '');
        if ($text === '') {
            continue;
        }
        $result['hero_bars'][] = $text;
        if (count($result['hero_bars']) >= 3) {
            break;
        }
    }

    $allowed_styles = ['red', 'gold', 'green', 'cyan'];
    $promo_tags = is_array($meta['promo_tags'] ?? null) ? $meta['promo_tags'] : [];
    foreach ($promo_tags as $item) {
        if (!is_array($item)) {
            continue;
        }
        $text = $clean_text($item['text'] ?? '', 20, '');
        if ($text === '') {
            continue;
        }
        $style = trim((string)($item['style'] ?? 'gold'));
        if (!in_array($style, $allowed_styles, true)) {
            $style = 'gold';
        }
        $result['promo_tags'][] = ['style' => $style, 'text' => $text];
        if (count($result['promo_tags']) >= 4) {
            break;
        }
    }
    if (empty($result['promo_tags'])) {
        $result['promo_tags'] = $defaults['promo_tags'];
    }

    $categories = is_array($meta['categories'] ?? null) ? $meta['categories'] : [];
    foreach ($categories as $item) {
        if (!is_array($item)) {
            continue;
        }
        $label = $clean_text($item['label'] ?? '', 12, '');
        if ($label === '') {
            continue;
        }
        $result['categories'][] = [
            'label' => $label,
            'icon' => normalize_nav_icon((string)($item['icon'] ?? '')),
        ];
        if (count($result['categories']) >= 6) {
            break;
        }
    }
    if (empty($result['categories'])) {
        $result['categories'] = $defaults['categories'];
    }

    return $result;
}

/**
 * 标准化导航模板的链接数据
 * 支持 JSON 数组，也兼容旧数据里直接填写单个 URL 的场景
 */
function normalize_nav_links(string $raw, int $max = 10): array {
    $raw = trim($raw);
    if ($raw === '') {
        return [];
    }

    $payload = decode_nav_payload($raw);
    $decoded = $payload['links'];
    if (empty($decoded) && json_last_error() !== JSON_ERROR_NONE) {
        return is_valid_url($raw)
            ? [['name' => '默认链接', 'url' => $raw]]
            : [];
    }

    if (!is_array($decoded)) {
        return [];
    }

    $links = [];
    foreach ($decoded as $item) {
        if (!is_array($item)) {
            continue;
        }

        $url = trim((string)($item['url'] ?? ''));
        if ($url === '' || !is_valid_url($url)) {
            continue;
        }

        $name = trim((string)($item['name'] ?? ''));
        if ($name === '') {
            $name = '链接 ' . (count($links) + 1);
        }
        if (function_exists('mb_substr')) {
            $name = mb_substr($name, 0, 50, 'UTF-8');
        } else {
            $name = substr($name, 0, 50);
        }

        $link = [
            'name' => $name,
            'url' => $url,
        ];

        $icon = normalize_nav_icon((string)($item['icon'] ?? ''));
        if ($icon !== '') {
            $link['icon'] = $icon;
        }

        $group = trim((string)($item['group'] ?? ''));
        if (in_array($group, ['official', 'download', 'backup', 'service'], true)) {
            $link['group'] = $group;
        }

        $links[] = $link;
        if (count($links) >= $max) {
            break;
        }
    }

    return $links;
}

/** XSS 安全输出 */
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** 写操作日志 */
function write_admin_log(string $action): void {
    try {
        session_start_safe();
        $admin_id = (int)($_SESSION['admin_id'] ?? 0);
        $username = $_SESSION['admin_username'] ?? '';
        $ip       = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
        $ip       = trim(explode(',', $ip)[0]);
        $ua       = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
        $domain   = $_SERVER['HTTP_HOST'] ?? '';
        
        // 获取 IP 归属地
        $location = '';
        if (!empty($ip) && $ip !== '127.0.0.1') {
            $location = get_ip_location($ip);
        }

        get_db()->prepare(
            'INSERT INTO `admin_logs` (`admin_id`,`username`,`ip`,`location`,`domain`,`ua`,`action`) VALUES (?,?,?,?,?,?,?)'
        )->execute([$admin_id, $username, $ip, $location, $domain, $ua, $action]);
    } catch (Throwable $e) {
        error_log('write_admin_log error: ' . $e->getMessage());
    }
}

/** 获取 IP 信息（归属地+运营商） */
function get_ip_info(string $ip): array {
    static $cache = [];
    static $table_checked = false;
    $key = $ip;
    if (isset($cache[$key])) return $cache[$key];
    
    $result = ['location' => '', 'isp' => ''];
    
    if (!defined('IP_LOCATION_ENABLED') || !IP_LOCATION_ENABLED) {
        $cache[$key] = $result;
        return $result;
    }
    
    if ($ip === '127.0.0.1' || strpos($ip, '192.168.') === 0 || strpos($ip, '10.') === 0) {
        $result['location'] = '本地';
        $cache[$key] = $result;
        return $result;
    }
    
    if (!$table_checked) {
        try {
            get_db()->exec(
                "CREATE TABLE IF NOT EXISTS `ip_cache` (" .
                "`ip` varchar(45) NOT NULL, " .
                "`location` varchar(100) NOT NULL DEFAULT '', " .
                "`isp` varchar(50) NOT NULL DEFAULT '', " .
                "`updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, " .
                "PRIMARY KEY (`ip`), KEY `idx_updated_at` (`updated_at`)" .
                ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
        } catch (Throwable $e) {}
        $table_checked = true;
    }
    
    try {
        $stmt = get_db()->prepare("SELECT `location`, `isp` FROM `ip_cache` WHERE `ip` = ? LIMIT 1");
        $stmt->execute([$ip]);
        $row = $stmt->fetch();
        if ($row && $row['location'] !== '') {
            $result['location'] = $row['location'];
            $result['isp'] = $row['isp'] ?? '';
            $cache[$key] = $result;
            return $result;
        }
    } catch (Throwable $e) {}
    
    $location = '';
    $isp = '';
    
    try {
        $api_url = IP_API_URL . '?ip=' . urlencode($ip);
        $ctx = stream_context_create([
            'http' => [
                'timeout' => IP_API_TIMEOUT,
                'ignore_errors' => true
            ]
        ]);
        $response = @file_get_contents($api_url, false, $ctx);
        if ($response) {
            $data = json_decode($response, true);
            if ($data && isset($data['code']) && $data['code'] === 200) {
                $location = $data['data']['location']['desc'] ?? '';
                $isp = $data['data']['isp'] ?? '';
            }
        }
    } catch (Throwable $e) {
        error_log('get_ip_info error: ' . $e->getMessage());
    }
    
    if ($location === '' && defined('IP_API_FALLBACK_URL')) {
        try {
            $fallback_url = IP_API_FALLBACK_URL . urlencode($ip) . '?lang=zh-CN&fields=status,message,regionName,city,isp';
            $ctx2 = stream_context_create([
                'http' => [
                    'timeout' => IP_API_FALLBACK_TIMEOUT,
                    'ignore_errors' => true
                ]
            ]);
            $response2 = @file_get_contents($fallback_url, false, $ctx2);
            if ($response2) {
                $data2 = json_decode($response2, true);
                if ($data2 && ($data2['status'] ?? '') === 'success') {
                    $parts = array_filter([
                        $data2['regionName'] ?? '',
                        $data2['city'] ?? '',
                    ], function($v) { return $v !== ''; });
                    $location = implode(' ', $parts);
                    $isp = $data2['isp'] ?? '';
                }
            }
        } catch (Throwable $e) {
            error_log('get_ip_info fallback error: ' . $e->getMessage());
        }
    }
    
    if ($location !== '') {
        try {
            get_db()->prepare(
                "INSERT INTO `ip_cache` (`ip`, `location`, `isp`) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE `location` = VALUES(`location`), `isp` = VALUES(`isp`)"
            )->execute([$ip, $location, $isp]);
        } catch (Throwable $e) {}
    }
    
    $result['location'] = $location;
    $result['isp'] = $isp;
    $cache[$key] = $result;
    return $result;
}

/** 获取 IP 归属地（调用 API） */
function get_ip_location(string $ip): string {
    return get_ip_info($ip)['location'];
}

/** 解析 User-Agent */
function parse_user_agent(string $ua): array {
    $ua = trim($ua);
    $result = [
        'device' => '未知',
        'browser' => '未知',
        'os' => '未知',
    ];
    
    if ($ua === '') return $result;
    
    // 设备类型
    if (preg_match('/iPad/i', $ua)) {
        $result['device'] = '平板';
    } elseif (preg_match('/Mobile|Android|iPhone|iPod|BlackBerry|IEMobile|Opera Mini/i', $ua)) {
        $result['device'] = '手机';
    } elseif (preg_match('/Tablet|PlayBook|Silk/i', $ua)) {
        $result['device'] = '平板';
    } else {
        $result['device'] = '电脑';
    }
    
    // 操作系统
    if (preg_match('/Windows NT (\d+\.?\d*)/i', $ua, $m)) {
        $ver = $m[1];
        $map = ['10.0' => '10', '6.3' => '8.1', '6.2' => '8', '6.1' => '7', '6.0' => 'Vista', '5.1' => 'XP'];
        $result['os'] = 'Windows ' . ($map[$ver] ?? $ver);
    } elseif (preg_match('/Mac OS X (\d+[._]\d+)/i', $ua, $m)) {
        $result['os'] = 'macOS ' . str_replace('_', '.', $m[1]);
    } elseif (preg_match('/Android (\d+\.?\d*)/i', $ua, $m)) {
        $result['os'] = 'Android ' . $m[1];
    } elseif (preg_match('/iPhone OS (\d+)/i', $ua, $m)) {
        $result['os'] = 'iOS ' . $m[1];
    } elseif (preg_match('/Linux/i', $ua)) {
        $result['os'] = 'Linux';
    } elseif (preg_match('/CrOS/i', $ua)) {
        $result['os'] = 'ChromeOS';
    }
    
    // 浏览器
    if (preg_match('/Edg[ea]?\/(\d+)/i', $ua, $m)) {
        $result['browser'] = 'Edge ' . $m[1];
    } elseif (preg_match('/Chrome\/(\d+)/i', $ua, $m) && !preg_match('/Chromium|Edg|OPR|Opera|Vivaldi|Brave/i', $ua)) {
        $result['browser'] = 'Chrome ' . $m[1];
    } elseif (preg_match('/Firefox\/(\d+)/i', $ua, $m)) {
        $result['browser'] = 'Firefox ' . $m[1];
    } elseif (preg_match('/Safari\/(\d+)/i', $ua, $m) && !preg_match('/Chrome/i', $ua)) {
        $result['browser'] = 'Safari ' . $m[1];
    } elseif (preg_match('/OPR\/(\d+)/i', $ua, $m)) {
        $result['browser'] = 'Opera ' . $m[1];
    } elseif (preg_match('/MSIE (\d+)/i', $ua, $m)) {
        $result['browser'] = 'IE ' . $m[1];
    } elseif (preg_match('/Trident.*rv:(\d+)/i', $ua, $m)) {
        $result['browser'] = 'IE ' . $m[1];
    }
    
    return $result;
}

/** 分页辅助 */
function paginate(int $total, int $page, int $per_page = 20): array {
    $total_pages = max(1, (int) ceil($total / $per_page));
    $page        = max(1, min($page, $total_pages));
    return [
        'total'       => $total,
        'per_page'    => $per_page,
        'page'        => $page,
        'total_pages' => $total_pages,
        'offset'      => ($page - 1) * $per_page,
    ];
}

/** 动态加载模板配置 */
function get_templates(): array {
    static $templates = null;
    if ($templates !== null) return $templates;
    
    $templates = [];
    $template_dir = TEMPLATE_PATH;
    
    if (!is_dir($template_dir)) return $templates;
    
    $files = glob($template_dir . '/*.php');
    $allowed_templates = ['img', 'delay', 'click_delay']; // 允许的模板白名单
    foreach ($files as $file) {
        $name = basename($file, '.php');
        if (!in_array($name, $allowed_templates)) continue;
        
        // 读取文件头部的配置注释
        $content = file_get_contents($file, false, null, 0, 800);
        $config = [
            'name' => $name,
            'label' => $name,
            'fields' => [],
        ];
        
        // 解析配置注释格式：
        // /**
        //  * 模板描述
        //  * @label 显示名称
        //  * @fields url,delay,img
        //  */
        if (preg_match('/@label\s+(.+?)(?:\n|$)/i', $content, $m)) {
            $config['label'] = trim($m[1]);
        }
        if (preg_match('/@fields\s+(.+?)(?:\n|$)/i', $content, $m)) {
            $fields_str = trim($m[1]);
            $config['fields'] = array_filter(array_map('trim', explode(',', $fields_str)));
        }
        
        $templates[$name] = $config;
    }
    
    // 按名称排序
    ksort($templates);
    return $templates;
}
