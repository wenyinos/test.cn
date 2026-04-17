<?php
/**
 * JumpHost 跳转分发入口
 * @copyright 2026 wenyinos <ruojiner@hotmail.com>
 * @license MIT License
 */

require_once __DIR__ . '/config/ip_api_config.php';

// ── 检测是否已安装 ────────────────────────────────────────
$config_file = __DIR__ . '/config/config.php';
if (!file_exists($config_file)) {
    header('Location: /install/install.php');
    exit;
}

require_once $config_file;

if (!defined('DB_USER') || DB_USER === '') {
    header('Location: /install/install.php');
    exit;
}

try {
    get_db();
} catch (Throwable $e) {
    header('Location: /install/install.php');
    exit;
}

// ── 获取当前访问域名（去掉端口） ──────────────────────────
$host = strtolower(trim($_SERVER['HTTP_HOST'] ?? ''));
$host = preg_replace('/:\d+$/', '', $host);

if (empty($host)) {
    http_response_code(400);
    exit('Bad Request');
}

// ── 排除 /admin/ 等路径，确保物理文件可访问 ────────────
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$request_path = parse_url($uri, PHP_URL_PATH);
if (strpos($request_path, '/admin') === 0 || strpos($request_path, '/install') === 0 || strpos($request_path, '/assets') === 0) {
    // 如果物理文件存在，由 Web 服务器处理；如果 index.php 运行到这里说明文件可能没被服务器直接命中
    if (file_exists(ROOT_PATH . $request_path)) {
        return false; // 在某些环境（如内置服务器）下允许继续寻找物理文件
    }
    http_response_code(404);
    exit('Not Found');
}

// ── 查询域名配置 ──────────────────────────────────────────
try {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') === '443' ? 'https' : 'http';
    $stmt = get_db()->prepare(
        "SELECT * FROM `domains` WHERE `domain` = ? AND `protocol` = ? LIMIT 1"
    );
    $stmt->execute([$host, $protocol]);
    $rule = $stmt->fetch();
    if (!$rule) {
        $stmt2 = get_db()->prepare(
            "SELECT * FROM `domains` WHERE `domain` = ? LIMIT 1"
        );
        $stmt2->execute([$host]);
        $rule = $stmt2->fetch();
    }
} catch (Throwable $e) {
    error_log('JumpHost DB error: ' . $e->getMessage());
    http_response_code(503);
    exit('Service Unavailable');
}

// ── 未找到规则 ────────────────────────────────────────────
if (!$rule) {
    try {
        $cnt = (int) get_db()->query("SELECT COUNT(*) FROM `domains`")->fetchColumn();
        if ($cnt === 0) {
            header('Location: /admin/index.php');
            exit;
        }
    } catch (Throwable $e) {}
    http_response_code(404);
    exit('404 Not Found');
}

// ── 记录访问日志 ──────────────────────────────────────────
try {
    // 获取真实 IP（支持代理）
    $client_ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
    $client_ip = trim(explode(',', $client_ip)[0]);
    
    // 验证 IP 格式
    if (!filter_var($client_ip, FILTER_VALIDATE_IP)) {
        $client_ip = '';
    }
    
    $ip_info = get_ip_info($client_ip);
    
    $log = get_db()->prepare(
        "INSERT INTO `access_logs` (`domain_id`, `ip`, `location`, `isp`, `user_agent`) VALUES (?, ?, ?, ?, ?)"
    );
    $log->execute([
        $rule['id'],
        $client_ip,
        $ip_info['location'],
        $ip_info['isp'],
        substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
    ]);
} catch (Throwable $e) {}

// ── 暂停状态 ──────────────────────────────────────────────
if ($rule['status'] === 'paused') {
    require TEMPLATE_PATH . '/pause.php';
    exit;
}

// ── 注入模板变量 ──────────────────────────────────────────
$target_url       = $rule['target_url'];
$delay            = (int) $rule['delay'];
$img_url          = $rule['img_url'] ?? '';
$site_title       = $rule['site_title'] ?? '';
$site_description = $rule['site_description'] ?? '';
$is_show_link     = (int)($rule['is_show_link'] ?? 1);

// ── 验证模板文件存在 ──────────────────────────────────────
$template_name = preg_replace('/[^a-z0-9_-]/i', '', $rule['template']);
$templates_list = get_templates();
if (!isset($templates_list[$template_name])) {
    error_log('JumpHost: invalid template: ' . $rule['template']);
    header('HTTP/1.1 302 Found');
    header('Location: ' . $target_url);
    exit;
}
$template_file = TEMPLATE_PATH . '/' . $template_name . '.php';

if (!file_exists($template_file)) {
    error_log('JumpHost: template not found: ' . $template_file);
    header('HTTP/1.1 302 Found');
    header('Location: ' . $target_url);
    exit;
}

require $template_file;
