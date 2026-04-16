<?php
/**
 * 绿色导航跳转模板
 * @label 绿色导航页
 * @fields nav,title,desc
 * @copyright 2026 wenyinos <ruojiner@hotmail.com>
 * @license MIT License
 */
require_once __DIR__ . '/_helpers.php';

$title = template_value($site_title ?? '', '绿色导航');
$desc = template_value($site_description ?? '', '请选择您要访问的入口');
$links = template_nav_links($target_url ?? '', [
    ['name' => '默认链接 1', 'url' => 'https://example.com'],
    ['name' => '默认链接 2', 'url' => 'https://example.com'],
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
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(to bottom, #5dd3d6 0%, #3eb8ea 50%, #239efb 100%);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            overflow: hidden;
        }

        .container {
            display: flex;
            flex-direction: column;
            gap: 24px;
            align-items: center;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
        }

        .title {
            font-size: 32px;
            font-weight: 600;
            color: white;
            margin-bottom: 8px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .subtitle {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.9);
            margin-top: 8px;
        }

        .btn {
            min-width: 240px;
            padding: 16px 64px;
            background: linear-gradient(135deg, #6f62c3 0%, #637edf 100%);
            color: white;
            font-size: 18px;
            font-weight: 500;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            opacity: 0;
            animation: fadeInUp 0.5s ease forwards;
        }

        .btn:nth-child(1) { animation-delay: 0.1s; }
        .btn:nth-child(2) { animation-delay: 0.2s; }
        .btn:nth-child(3) { animation-delay: 0.3s; }
        .btn:nth-child(4) { animation-delay: 0.4s; }
        .btn:nth-child(5) { animation-delay: 0.5s; }
        .btn:nth-child(6) { animation-delay: 0.6s; }
        .btn:nth-child(7) { animation-delay: 0.7s; }
        .btn:nth-child(8) { animation-delay: 0.8s; }
        .btn:nth-child(9) { animation-delay: 0.9s; }
        .btn:nth-child(10) { animation-delay: 1.0s; }

        .btn:hover {
            background: linear-gradient(135deg, #7a6dd4 0%, #7488f0 100%);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            transform: scale(1.05);
        }

        .btn:active {
            transform: scale(0.98);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .btn {
                min-width: 200px;
                padding: 14px 48px;
                font-size: 16px;
            }

            .title {
                font-size: 28px;
            }

            .subtitle {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
        <div class="container">
        <div class="header">
            <h1 class="title"><?= e($title) ?></h1>
            <p class="subtitle"><?= e($desc) ?></p>
        </div>
        
        <?php foreach ($links as $link): ?>
            <a href="<?= e($link['url']) ?>" class="btn" target="_blank" rel="noopener noreferrer">
                <?= e($link['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>
</body>
</html>
