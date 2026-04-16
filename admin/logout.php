<?php
/**
 * 登出
 */
require_once dirname(__DIR__) . '/config/config.php';
session_start_safe();
session_unset();
session_destroy();
header('Location: /admin/login.php');
exit;
