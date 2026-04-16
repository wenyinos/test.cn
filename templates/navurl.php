<?php
/**
 * 基础导航跳转模板
 * @label 渐变卡片导航
 * @fields nav,title,desc,img
 */
require_once __DIR__ . '/_helpers.php';

$title = template_value($site_title ?? '', '导航中心');
$desc = template_value($site_description ?? '', '');
$background_image = template_href($img_url ?? '', '');
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            height: 100vh;
            overflow: hidden;
        }

        .page-wrapper {
            position: fixed;
            inset: 0;
            <?php if (!empty($background_image)): ?>
            background-image: url('<?= e($background_image) ?>');
            <?php else: ?>
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            <?php endif; ?>
            background-size: cover;
            background-position: center;
        }

        .background-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom, transparent 0%, rgba(0, 0, 0, 0.7) 100%);
        }

        .content-wrapper {
            position: relative;
            z-index: 10;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .header {
            text-align: center;
            padding: 24px 16px;
            margin-top: 16px;
        }

        .title {
            font-size: 30px;
            font-weight: bold;
            letter-spacing: 0.05em;
            color: white;
            margin-bottom: 4px;
        }

        .subtitle {
            font-size: 18px;
            color: #93c5fd;
        }

        .buttons-container {
            padding: 0 16px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            overflow-y: auto;
            padding-bottom: 24px;
        }

        .nav-button {
            border-radius: 12px;
            padding: 16px;
            cursor: pointer;
            opacity: 0;
            animation: slideIn 0.5s ease forwards;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -1px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            text-decoration: none;
            display: block;
        }

        .nav-button:hover {
            transform: translateY(-2px);
            opacity: 1 !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.4), 0 4px 6px -2px rgba(0, 0, 0, 0.3);
        }

        .nav-button:active {
            transform: translateY(0);
        }

        .button-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .button-title {
            font-size: 18px;
            font-weight: bold;
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .button-arrow {
            font-size: 12px;
            color: #dbeafe;
            margin-top: 4px;
        }

        /* 按钮渐变颜色 */
        .btn-gradient-1 {
            background: linear-gradient(135deg, #6043bf 0%, #7c5fd9 100%);
            animation-delay: 0.1s;
        }

        .btn-gradient-2 {
            background: linear-gradient(135deg, #4f2e94 0%, #6b3ec8 100%);
            animation-delay: 0.2s;
        }

        .btn-gradient-3 {
            background: linear-gradient(135deg, #be2c79 0%, #d93d8f 100%);
            animation-delay: 0.3s;
        }

        .btn-gradient-4 {
            background: linear-gradient(135deg, #35a8a6 0%, #45c4c2 100%);
            animation-delay: 0.4s;
        }

        .btn-gradient-5 {
            background: linear-gradient(135deg, #cd973f 0%, #e5ab52 100%);
            animation-delay: 0.5s;
        }

        /* 更多按钮样式 */
        .btn-gradient-6 {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            animation-delay: 0.6s;
        }

        .btn-gradient-7 {
            background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
            animation-delay: 0.7s;
        }

        .btn-gradient-8 {
            background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
            animation-delay: 0.8s;
        }

        .btn-gradient-9 {
            background: linear-gradient(135deg, #ef4444 0%, #f87171 100%);
            animation-delay: 0.9s;
        }

        .btn-gradient-10 {
            background: linear-gradient(135deg, #06b6d4 0%, #22d3ee 100%);
            animation-delay: 1.0s;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 0.8;
                transform: translateX(0);
            }
        }

        /* 底部营销图片样式 */
        .marketing-banner {
            margin-top: 16px;
            border-radius: 12px;
            overflow: hidden;
            opacity: 0;
            animation: slideIn 0.5s ease forwards;
            animation-delay: 0.6s;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -1px rgba(0, 0, 0, 0.2);
        }

        .banner-image {
            width: 100%;
            height: auto;
            display: block;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .banner-image:hover {
            transform: scale(1.02);
        }

        /* 响应式设计 */
        @media (max-width: 768px) {
            .title {
                font-size: 28px;
            }

            .subtitle {
                font-size: 16px;
            }

            .nav-button {
                padding: 14px;
            }

            .button-title {
                font-size: 16px;
            }
        }

        @media (min-width: 769px) {
            .buttons-container {
                max-width: 500px;
                margin: 0 auto;
            }
        }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <div class="background-overlay"></div>

        <div class="content-wrapper">
            <div class="header">
                <h1 class="title"><?= e($title) ?></h1>
                <p class="subtitle"><?= e($desc) ?></p>
            </div>

            <div class="buttons-container">
                <?php foreach ($links as $index => $link): ?>
                    <a href="<?= e($link['url']) ?>" class="nav-button btn-gradient-<?= ($index % 10) + 1 ?>" target="_blank" rel="noopener noreferrer">
                        <div class="button-content">
                            <h3 class="button-title"><?= e($link['name']) ?></h3>
                            <p class="button-arrow">GO&gt;</p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>
