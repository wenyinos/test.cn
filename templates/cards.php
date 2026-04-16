<?php
/**
 * 卡片式导航模板
 * @label 卡片式导航
 * @fields nav,title,desc
 */
require_once __DIR__ . '/_helpers.php';

$title = template_value($site_title ?? '', '导航中心');
$desc = template_value($site_description ?? '', '选择您要访问的链接');
$links = template_nav_links($target_url ?? '', [
    ['name' => '示例链接 1', 'url' => 'https://example.com', 'icon' => '🚀'],
    ['name' => '示例链接 2', 'url' => 'https://example.com', 'icon' => '📱'],
    ['name' => '示例链接 3', 'url' => 'https://example.com', 'icon' => '⭐'],
]);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Helvetica Neue', sans-serif;
            background: #0f0f0f;
            min-height: 100vh;
            padding: 60px 20px;
            color: #fff;
        }
        .bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 20% 50%, rgba(99, 102, 241, 0.08) 0%, transparent 50%),
                        radial-gradient(circle at 80% 80%, rgba(168, 85, 247, 0.08) 0%, transparent 50%);
            z-index: 0;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            position: relative;
            z-index: 10;
        }
        .header {
            text-align: center;
            margin-bottom: 80px;
        }
        .header h1 {
            font-size: 42px;
            margin-bottom: 16px;
            font-weight: 700;
            letter-spacing: -1px;
        }
        .header p {
            font-size: 16px;
            color: #999;
        }
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
        }
        .card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, rgba(255, 255, 255, 0.02) 100%);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 32px 24px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 180px;
            position: relative;
            overflow: hidden;
        }
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s;
        }
        .card:hover::before {
            left: 100%;
        }
        .card:hover {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(168, 85, 247, 0.05) 100%);
            border-color: rgba(99, 102, 241, 0.3);
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(99, 102, 241, 0.2);
        }
        .card-icon {
            font-size: 48px;
            margin-bottom: 16px;
            display: inline-block;
        }
        .card-icon img {
            width: 52px;
            height: 52px;
            object-fit: cover;
            border-radius: 16px;
            box-shadow: 0 12px 24px rgba(17, 22, 38, 0.25);
        }
        .card-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .card-url {
            font-size: 12px;
            color: #666;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <div class="bg"></div>
    
    <div class="container">
        <div class="header">
            <h1><?= e($title) ?></h1>
            <p><?= e($desc) ?></p>
        </div>
        
        <div class="cards">
            <?php 
                $icons = ['🔗', '📱', '💻', '🌐', '📧', '📞', '🎯', '🚀', '⭐', '💡', '🎨', '🔧'];
                foreach ($links as $i => $link): 
                    $name = e($link['name'] ?? '链接 ' . ($i + 1));
                    $url = e($link['url'] ?? '#');
                    $icon = template_icon_data($link['icon'] ?? '', $icons[$i % count($icons)]);
            ?>
            <a href="<?= $url ?>" class="card" target="_blank" rel="noopener noreferrer">
                <div class="card-icon">
                    <?php if ($icon['type'] === 'image'): ?>
                    <img src="<?= e($icon['value']) ?>" alt="" loading="lazy">
                    <?php else: ?>
                    <?= e($icon['value']) ?>
                    <?php endif; ?>
                </div>
                <div class="card-title"><?= $name ?></div>
                <div class="card-url"><?= $url ?></div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
