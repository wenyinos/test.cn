<?php
/**
 * 倒计时跳转模板
 * @label 倒计时跳转
 * @fields url,delay,title,desc
 */
require_once __DIR__ . '/_helpers.php';

$target_href = template_href($target_url ?? '', '');
if ($target_href === '') {
    http_response_code(400);
    exit('Invalid redirect');
}

$delay = max(1, min(60, (int)($delay ?? 5)));
$title = template_value($site_title ?? '', '页面跳转中');
$desc = template_value($site_description ?? '', '正在为您跳转到目标页面');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { width: 100%; height: 100%; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', sans-serif;
            background: #0f0f0f;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            color: #fff;
        }
        .bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 20% 50%, rgba(99, 102, 241, 0.1) 0%, transparent 50%),
                        radial-gradient(circle at 80% 80%, rgba(168, 85, 247, 0.1) 0%, transparent 50%);
            z-index: 0;
        }
        .container {
            text-align: center;
            z-index: 10;
            position: relative;
        }
        .circle-container {
            width: 200px;
            height: 200px;
            margin: 0 auto 40px;
            position: relative;
        }
        svg {
            width: 100%;
            height: 100%;
            transform: rotate(-90deg);
        }
        .circle-bg {
            fill: none;
            stroke: rgba(255, 255, 255, 0.1);
            stroke-width: 2;
        }
        .circle-progress {
            fill: none;
            stroke: url(#gradient);
            stroke-width: 2;
            stroke-linecap: round;
            stroke-dasharray: 565.48;
            stroke-dashoffset: 565.48;
            transition: stroke-dashoffset 1s linear;
        }
        .countdown-num {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 64px;
            font-weight: 700;
            background: linear-gradient(135deg, #6366f1, #a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        h1 {
            font-size: 32px;
            margin-bottom: 12px;
            font-weight: 600;
            letter-spacing: -0.5px;
        }
        p {
            font-size: 15px;
            color: #999;
            margin-bottom: 40px;
            line-height: 1.6;
        }
        .link {
            display: inline-block;
            padding: 12px 28px;
            background: linear-gradient(135deg, #6366f1, #a855f7);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            border: 1px solid rgba(99, 102, 241, 0.3);
        }
        .link:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(99, 102, 241, 0.3);
        }
    </style>
</head>
<body>
    <div class="bg"></div>
    
    <div class="container">
        <div class="circle-container">
            <svg viewBox="0 0 200 200">
                <defs>
                    <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#6366f1;stop-opacity:1" />
                        <stop offset="100%" style="stop-color:#a855f7;stop-opacity:1" />
                    </linearGradient>
                </defs>
                <circle class="circle-bg" cx="100" cy="100" r="90"></circle>
                <circle class="circle-progress" id="progress" cx="100" cy="100" r="90"></circle>
            </svg>
            <div class="countdown-num" id="countdown"><?= $delay ?></div>
        </div>
        
        <h1><?= e($title) ?></h1>
        <p><?= e($desc) ?></p>
        <a href="<?= e($target_href) ?>" class="link">立即跳转</a>
    </div>
    
    <script>
        let count = <?= $delay ?>;
        const total = <?= $delay ?>;
        const el = document.getElementById('countdown');
        const progress = document.getElementById('progress');
        const circumference = 565.48;
        
        function updateProgress() {
            const offset = circumference * (1 - (total - count) / total);
            progress.style.strokeDashoffset = offset;
        }
        
        updateProgress();
        
        const timer = setInterval(() => {
            count--;
            el.textContent = count;
            updateProgress();
            
            if (count <= 0) {
                clearInterval(timer);
                window.location.replace(<?= template_js($target_href) ?>);
            }
        }, 1000);
    </script>
</body>
</html>
