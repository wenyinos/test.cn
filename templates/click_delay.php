<?php
/**
 * 延时跳转模板
 * @label 点击+自动跳转
 * @fields url,delay,img,title,desc
 * @copyright 2026 wenyinos <ruojiner@hotmail.com>
 * @license MIT License
 */
require_once __DIR__ . '/_helpers.php';

// 检查必要参数
$target_href = template_href($target_url ?? '', '');
if ($target_href === '') {
    error_log('Delay redirect error: target_url is empty');
    http_response_code(400);
    exit('Invalid redirect');
}

// 验证延时时间
if (!isset($delay) || !is_numeric($delay) || $delay < 1 || $delay > 60) {
    $delay = 3; // 默认3秒
}

$delay = (int)$delay;
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
            font-family: "Segoe UI", "PingFang SC", "Microsoft YaHei", sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: linear-gradient(160deg, #eef4ff 0%, #f8fbff 45%, #f0fff8 100%);
        }
.loading {
            text-align: center;
            padding: 36px 32px;
        }
        .image {
            width: 512px;
            max-width: 100%;
            height: auto;
            margin-bottom: 18px;
        }
        .text {
            color: #888;
            font-size: 16px;
            margin-bottom: 18px;
        }
        .link {
            color: #4b74ff;
            text-decoration: none;
        }
        .link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="loading">
        <?php if ($image_src): ?>
        <a href="<?= e($target_href) ?>">
            <img src="<?= e($image_src) ?>" class="image" alt="<?= e($title) ?>">
        </a>
        <?php else: ?>
        <a href="<?= e($target_href) ?>" class="link">点此跳转</a>
        <?php endif; ?>
    </div>
    <script>
        (function() {
            'use strict';
            
            var seconds = <?= $delay ?>;
            var countdown = document.getElementById('countdown');
            var targetUrl = <?= template_js($target_href) ?>;
            
            function redirect() {
                window.location.replace(targetUrl);
            }
            
            var timer = setInterval(function() {
                seconds--;
                if (countdown) {
                    countdown.textContent = seconds;
                }
                
                if (seconds <= 0) {
                    clearInterval(timer);
                    redirect();
                }
            }, 1000);
            
            // 如果页面被隐藏，加快倒计时
            document.addEventListener('visibilitychange', function() {
                if (document.hidden && seconds > 2) {
                    seconds = 2;
                }
            });
        })();
    </script>
</body>
</html> 
