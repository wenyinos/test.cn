<?php
/**
 * 图片点击跳转模板
 * @label 点击跳转
 * @fields url,img,title,desc
 * @copyright 2026 wenyinos <ruojiner@hotmail.com>
 * @license MIT License
 */
require_once __DIR__ . '/_helpers.php';

// 检查必要参数
$target_href = template_href($target_url ?? '', '');
if ($target_href === '') {
    error_log('Image redirect error: target_url is empty');
    http_response_code(400);
    exit('Invalid redirect');
}

$has_img = !empty(trim($img_url ?? ''));
$image_src = template_href($img_url ?? '', '');
$title = is_string($site_title ?? null) ? trim($site_title) : '';
$desc = is_string($site_description ?? null) ? trim($site_description) : '';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <style>
        body {
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(145deg, #f4f7ff 0%, #fbfcff 55%, #eefcf4 100%);
            font-family: "Segoe UI", "PingFang SC", "Microsoft YaHei", sans-serif;
        }
        .container {
            text-align: center;
            padding: 28px;
        }
        .image {
            width: 512px;
            max-width: 100%;
            height: auto;
            cursor: pointer;
        }
        .text {
            color: #888;
            font-size: 16px;
        }
        .link {
            color: #4b74ff;
            font-size: 16px;
            text-decoration: none;
        }
        .link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($image_src): ?>
        <img src="<?= e($image_src) ?>" class="image" onclick="redirect()" alt="<?= e($title) ?>" onerror="handleImageError(this)">
        <?php else: ?>
        <a href="<?= e($target_href) ?>" class="link">点此跳转</a>
        <?php endif; ?>
    </div>
    <script>
        (function() {
            'use strict';
            
            var targetUrl = <?= template_js($target_href) ?>;
            var hasImage = <?= $has_img ? 'true' : 'false' ?>;
            
            window.redirect = function() {
                window.location.replace(targetUrl);
            };
            
            window.handleImageError = function(img) {
                img.style.display = 'none';
                redirect();
            };
            
        })();
    </script>
</body>
</html> 
