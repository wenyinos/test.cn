<?php
/**
 * 极简跳转页
 * @label 极简跳转页
 * @fields url,title,desc
 * @copyright 2026 wenyinos <ruojiner@hotmail.com>
 * @license MIT License
 */
require_once __DIR__ . '/_helpers.php';

$target_href = template_href($target_url ?? '', '');
if ($target_href === '') {
    http_response_code(400);
    exit('Invalid redirect');
}

$title = template_value($site_title ?? '', '跳转中');
$desc = template_value($site_description ?? '', '正在为您跳转...');
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
            font-family: 'SF Pro Display', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #fafafa;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .container {
            text-align: center;
            max-width: 500px;
            padding: 40px;
        }
        .icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
        }
        h1 {
            font-size: 28px;
            color: #1a1a1a;
            margin-bottom: 12px;
            font-weight: 600;
        }
        p {
            font-size: 15px;
            color: #666;
            margin-bottom: 40px;
            line-height: 1.6;
        }
        .loader {
            width: 40px;
            height: 4px;
            margin: 0 auto 30px;
            background: #e0e0e0;
            border-radius: 2px;
            overflow: hidden;
        }
        .loader::after {
            content: '';
            display: block;
            width: 30%;
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            animation: loading 1.5s ease-in-out infinite;
        }
        @keyframes loading {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(400%); }
        }
        a {
            display: inline-block;
            padding: 12px 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        a:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">→</div>
        <h1><?= e($title) ?></h1>
        <p><?= e($desc) ?></p>
        <div class="loader"></div>
        <a href="<?= e($target_href) ?>">立即跳转</a>
    </div>
    <script>
        setTimeout(() => {
            window.location.replace(<?= template_js($target_href) ?>);
        }, 2000);
    </script>
</body>
</html>
