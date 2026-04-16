<?php
/**
 * 彩色导航跳转模板
 * @label 彩色背景导航
 * @fields nav,title,desc
 * @copyright 2026 wenyinos <ruojiner@hotmail.com>
 * @license MIT License
 */
require_once __DIR__ . '/_helpers.php';

$title = template_value($site_title ?? '', '导航中心');
$desc = template_value($site_description ?? '', '');
$links = template_nav_links($target_url ?? '', [
    ['name' => '默认链接 1', 'url' => 'https://example.com'],
    ['name' => '默认链接 2', 'url' => 'https://example.com'],
    ['name' => '默认链接 3', 'url' => 'https://example.com'],
]);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= e($title) ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "PingFang SC", "Hiragino Sans GB", "Microsoft YaHei", sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .page-wrapper {
            position: fixed;
            inset: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            background: url('/img.php') center center / cover no-repeat;
        }

        .background-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom,
                rgba(0, 0, 0, 0.3) 0%,
                rgba(0, 0, 0, 0.4) 50%,
                rgba(0, 0, 0, 0.6) 100%
            );
        }

        .content-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 400px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* 装饰线 */
        .decorative-line {
            width: 60%;
            height: 1px;
            background: rgba(255, 255, 255, 0.5);
            margin: 20px 0;
        }

        /* 标题区域 */
        .header {
            text-align: center;
            margin-bottom: 10px;
        }

        .title {
            font-size: 28px;
            font-weight: 400;
            letter-spacing: 0.3em;
            color: white;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
        }

        .subtitle {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.8);
            margin-top: 15px;
            letter-spacing: 0.1em;
        }

        /* 分隔线带竖线 */
        .divider-with-line {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 70%;
            margin: 25px 0;
        }

        .divider-with-line::before {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255, 255, 255, 0.5);
        }

        .divider-with-line::after {
            content: '';
            width: 1px;
            height: 20px;
            background: rgba(255, 255, 255, 0.5);
            margin-left: 0;
            position: absolute;
            transform: translateY(15px);
        }

        /* 按钮容器 */
        .buttons-container {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 0;
            margin-top: 15px;
        }

        /* 按钮样式 */
        .nav-button {
            display: block;
            padding: 14px 20px;
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-bottom: none;
            color: white;
            font-size: 15px;
            text-align: center;
            text-decoration: none;
            letter-spacing: 0.1em;
            transition: all 0.3s ease;
            opacity: 0;
            animation: fadeIn 0.5s ease forwards;
        }

        .nav-button:last-child {
            border-bottom: 1px solid rgba(255, 255, 255, 0.6);
        }

        .nav-button:nth-child(1) { animation-delay: 0.1s; }
        .nav-button:nth-child(2) { animation-delay: 0.2s; }
        .nav-button:nth-child(3) { animation-delay: 0.3s; }
        .nav-button:nth-child(4) { animation-delay: 0.4s; }
        .nav-button:nth-child(5) { animation-delay: 0.5s; }
        .nav-button:nth-child(6) { animation-delay: 0.6s; }
        .nav-button:nth-child(7) { animation-delay: 0.7s; }
        .nav-button:nth-child(8) { animation-delay: 0.8s; }
        .nav-button:nth-child(9) { animation-delay: 0.9s; }
        .nav-button:nth-child(10) { animation-delay: 1.0s; }

        .nav-button:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.9);
        }

        .nav-button:active {
            background: rgba(255, 255, 255, 0.25);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* 底部文字 */
        .footer {
            position: fixed;
            bottom: 30px;
            left: 0;
            right: 0;
            text-align: center;
            z-index: 10;
        }

        .footer-text {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.5);
            letter-spacing: 0.2em;
        }

        /* 响应式 */
        @media (max-width: 480px) {
            .title {
                font-size: 24px;
                letter-spacing: 0.2em;
            }

            .nav-button {
                padding: 12px 16px;
                font-size: 14px;
            }

            .content-wrapper {
                padding: 15px;
            }
        }

        @media (min-height: 800px) {
            .content-wrapper {
                margin-top: -50px;
            }
        }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <div class="background-overlay"></div>

        <div class="content-wrapper">
            <!-- 顶部装饰线 -->
            <div class="decorative-line"></div>

            <!-- 标题区域 -->
            <div class="header">
                <h1 class="title"><?= e($title) ?></h1>
                <p class="subtitle"><?= e($desc) ?></p>
            </div>

            <!-- 分隔线 -->
            <div class="divider-with-line"></div>

            <!-- 按钮区域 -->
            <div class="buttons-container">
                <?php foreach ($links as $link): ?>
                    <a href="<?= e($link['url']) ?>" class="nav-button" target="_blank" rel="noopener noreferrer">
                        <?= e($link['name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 底部文字 -->
        <div class="footer">
            <p class="footer-text">本站已加密，请放心浏览</p>
        </div>
    </div>
</body>
</html>
