<?php
/**
 * 301永久重定向模板
 * @label 301永久重定向
 * @fields url
 */
require_once __DIR__ . '/_helpers.php';

$redirect_url = template_href($target_url ?? '', '');
if ($redirect_url === '') {
    error_log('301 redirect error: target_url is empty');
    http_response_code(400);
    exit('Invalid redirect');
}

// 清理输出缓冲
while (ob_get_level()) ob_end_clean();

// 发送301头
header('Location: ' . $redirect_url, true, 301);
exit; 
