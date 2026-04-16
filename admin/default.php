<?php
/**
 * @copyright 2026 wenyinos <ruojiner@hotmail.com>
 * @license MIT License
 */
// 访问 /admin/ 时自动跳转到后台首页或登录页
require_once dirname(__DIR__) . '/config/config.php';
session_start_safe();
if (!empty($_SESSION['admin_id'])) {
    header('Location: /admin/index.php');
} else {
    header('Location: /admin/login.php');
}
exit;
