<?php
/**
 * 暂停页面模板
 * @label 不跳转
 * @fields
 * @copyright 2026 wenyinos <ruojiner@hotmail.com>
 * @license MIT License
 */

// config.php 由 index.php 统一加载，此处做兼容处理
if (!defined('ROOT_PATH')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>停止跳转</title>
    <style>
        body {
            font-family: "Microsoft YaHei", sans-serif;
            background-color: #f5f6fa;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
            width: 90%;
        }
        .icon {
            font-size: 60px;
            color: #ffa426;
            margin-bottom: 20px;
        }
        h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 15px;
        }
        p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .contact {
            font-size: 14px;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">⚠️</div>
        <h1>停止跳转</h1>
        <p>抱歉，该域名的转发服务目前已暂停。</p>
        <p>如需恢复访问，请联系域名所有者。</p>
        <div class="contact">
            <p>Copyright &copy; <?php echo date('Y'); ?> <?php echo get_site_name(); ?>. All rights reserved.</p>
        </div>
    </div>
</body>
</html> 