<?php
/**
 * 302临时重定向模板
 * @label 302临时重定向
 * @fields url
 * @copyright 2026 wenyinos <ruojiner@hotmail.com>
 * @license MIT License
 */
require_once __DIR__ . '/_helpers.php';

$redirect_url = template_href($target_url ?? '', '');
if ($redirect_url === '') {
    error_log('302 redirect error: target_url is empty');
    http_response_code(400);
    exit('Invalid redirect');
}

// 清理输出缓冲
while (ob_get_level()) ob_end_clean();

// 发送302头
header('Location: ' . $redirect_url, true, 302);
exit; 
