<?php
/**
 * 图片点击跳转模板
 * @label 图片点击跳转
 * @fields url,img,title,desc
 */
require_once __DIR__ . '/_helpers.php';

// 检查必要参数
$target_href = template_href($target_url ?? '', '');
if ($target_href === '') {
    error_log('Image redirect error: target_url is empty');
    http_response_code(400);
    exit('Invalid redirect');
}

$image_src = template_href($img_url ?? '', '/img.php');
$title = template_value($site_title ?? '', '点击图片继续访问');
$desc = template_value($site_description ?? '', '轻触图片即可跳转到目标页面');
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
            width: min(100%, 720px);
            margin: 20px;
            background: rgba(255,255,255,.9);
            border-radius: 28px;
            box-shadow: 0 24px 48px rgba(33, 61, 144, .12);
        }
        .image {
            max-width: 100%;
            height: auto;
            margin-bottom: 18px;
            cursor: pointer;
            border-radius: 18px;
            box-shadow: 0 14px 32px rgba(17, 45, 116, .12);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .image:hover {
            transform: scale(1.01);
            box-shadow: 0 20px 42px rgba(17, 45, 116, .18);
        }
        h1 {
            margin: 0 0 10px;
            font-size: 28px;
            color: #1d2a44;
        }
        .loading-text {
            color: #5d6b89;
            margin: 0 0 16px;
            line-height: 1.7;
        }
        .action {
            display: inline-flex;
            padding: 12px 22px;
            border-radius: 999px;
            background: linear-gradient(135deg, #4b74ff, #14b87a);
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            box-shadow: 0 14px 28px rgba(75, 116, 255, .18);
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="<?= e($image_src) ?>" class="image" onclick="redirect()" alt="<?= e($title) ?>" onerror="handleImageError(this)">
        <h1><?= e($title) ?></h1>
        <div class="loading-text"><?= e($desc) ?></div>
        <a href="<?= e($target_href) ?>" class="action">立即前往</a>
    </div>
    <script>
        (function() {
            'use strict';
            
            var targetUrl = <?= template_js($target_href) ?>;
            
            // 点击跳转
            window.redirect = function() {
                window.location.replace(targetUrl);
            };
            
            // 处理图片加载失败
            window.handleImageError = function(img) {
                img.style.display = 'none';
                redirect();
            };
            
            // 如果页面被隐藏超过3秒，自动跳转
            var hiddenTime;
            document.addEventListener('visibilitychange', function() {
                if (document.hidden) {
                    hiddenTime = Date.now();
                } else if (hiddenTime && Date.now() - hiddenTime > 3000) {
                    redirect();
                }
            });
            
            // 30秒后自动跳转
            setTimeout(redirect, 30000);
        })();
    </script>
</body>
</html> 
