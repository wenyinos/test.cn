<?php
/**
 * JumpHost 跳转分发入口
 */

require_once __DIR__ . '/config/config.php';

// ── 检测是否已安装 ────────────────────────────────────────
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

// ── 排除 /admin/ 路径，防止后台自身被跳转捕获 ────────────
$uri = $_SERVER['REQUEST_URI'] ?? '/';
if (strpos($uri, '/admin') === 0 || strpos($uri, '/install') === 0 || strpos($uri, '/assets') === 0) {
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
    
    $location = get_ip_location($client_ip);
    
    $log = get_db()->prepare(
        "INSERT INTO `access_logs` (`domain_id`, `ip`, `location`, `user_agent`) VALUES (?, ?, ?, ?)"
    );
    $log->execute([
        $rule['id'],
        $client_ip,
        $location,
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
