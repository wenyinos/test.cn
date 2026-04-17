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
            background: rgba(255,255,255,.88);
            border: 1px solid rgba(108, 137, 255, .12);
            border-radius: 24px;
            box-shadow: 0 24px 50px rgba(27, 63, 147, .12);
            max-width: 720px;
            width: calc(100% - 40px);
        }
        .image {
            width: 512px;
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
        .loading h1 {
            margin: 18px 0 10px;
            font-size: 28px;
            color: #1d2a44;
        }
        .loading-text {
            margin: 0 0 22px;
            color: #5d6b89;
            line-height: 1.7;
        }
        .spinner {
            width: 52px;
            height: 52px;
            border: 4px solid rgba(60, 94, 216, .12);
            border-top: 4px solid #4b74ff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        .jump-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 22px;
            border-radius: 999px;
            background: linear-gradient(135deg, #4b74ff, #14b87a);
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            box-shadow: 0 14px 28px rgba(75, 116, 255, .22);
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="loading">
        <?php if ($image_src): ?>
        <a href="<?= e($target_href) ?>">
            <img src="<?= e($image_src) ?>" class="image" alt="<?= e($title) ?>">
        </a>
        <?php endif; ?>
        <div class="spinner"></div>
        <?php if ($title !== ''): ?>
        <h1><?= e($title) ?></h1>
        <?php endif; ?>
        <?php if ($desc !== ''): ?>
        <div class="loading-text"><?= e($desc) ?></div>
        <?php endif; ?>
        <div class="loading-text">
            <span id="countdown"><?= $delay ?></span> 秒后自动跳转
        </div>
        <?php if ($is_show_link): ?>
        <div style="font-size:14px;color:#666;margin-bottom:15px;word-break:break-all;">即将跳转至：<a href="<?= e($target_href) ?>" style="color:#4b74ff;text-decoration:none;"><?= e($target_href) ?></a></div>
        <?php endif; ?>
        <a class="jump-link" href="<?= e($target_href) ?>">立即前往</a>
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
