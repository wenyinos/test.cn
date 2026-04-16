<?php
/**
 * 悬浮按钮导航模板
 * @label 悬浮按钮导航
 * @fields nav,title,desc
 */
require_once __DIR__ . '/_helpers.php';

$title = template_value($site_title ?? '', '导航菜单');
$desc = template_value($site_description ?? '', '点击按钮查看更多');
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
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
            text-align: center;
            max-width: 500px;
            position: relative;
            z-index: 10;
        }
        h1 {
            font-size: 36px;
            margin-bottom: 12px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        p {
            font-size: 15px;
            color: #999;
            margin-bottom: 60px;
        }
        .fab-container {
            position: relative;
            width: 100px;
            height: 100px;
            margin: 0 auto;
        }
        .fab-main {
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #6366f1, #a855f7);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            cursor: pointer;
            box-shadow: 0 12px 32px rgba(99, 102, 241, 0.4);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            color: white;
            z-index: 10;
        }
        .fab-main:hover {
            transform: translateX(-50%) scale(1.08);
            box-shadow: 0 16px 40px rgba(99, 102, 241, 0.5);
        }
        .fab-main.active {
            transform: translateX(-50%) rotate(45deg);
        }
        .fab-items {
            position: absolute;
            bottom: 120px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            flex-direction: column;
            gap: 16px;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .fab-items.active {
            opacity: 1;
            visibility: visible;
        }
        .fab-item {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(168, 85, 247, 0.2));
            border: 1px solid rgba(99, 102, 241, 0.3);
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            cursor: pointer;
            box-shadow: 0 8px 24px rgba(99, 102, 241, 0.2);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            color: #fff;
            position: relative;
        }
        .fab-item:hover {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.3), rgba(168, 85, 247, 0.3));
            border-color: rgba(99, 102, 241, 0.5);
            transform: scale(1.1);
            box-shadow: 0 12px 32px rgba(99, 102, 241, 0.3);
        }
        .fab-label {
            position: absolute;
            right: 100px;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(20, 20, 20, 0.95);
            backdrop-filter: blur(10px);
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            color: #fff;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
            font-weight: 500;
            border: 1px solid rgba(99, 102, 241, 0.2);
        }
        .fab-item:hover .fab-label {
            opacity: 1;
        }
        .fab-text {
            font-size: 10px;
            margin-top: 2px;
            max-width: 70px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .fab-icon-img {
            width: 34px;
            height: 34px;
            object-fit: cover;
            border-radius: 12px;
            margin-bottom: 2px;
            box-shadow: 0 8px 18px rgba(10, 14, 27, 0.25);
        }
    </style>
</head>
<body>
    <div class="bg"></div>
    
    <div class="container">
        <h1><?= e($title) ?></h1>
        <p><?= e($desc) ?></p>
        
        <div class="fab-container">
            <div class="fab-items" id="fabItems">
                <?php 
                    $icons = ['🔗', '📱', '💻', '🌐', '📧', '📞', '🎯', '🚀', '⭐', '💡', '🎨', '🔧'];
                    foreach ($links as $i => $link): 
                        $url = e($link['url'] ?? '#');
                        $name = e($link['name'] ?? '链接');
                        $icon = template_icon_data($link['icon'] ?? '', $icons[$i % count($icons)]);
                ?>
                <a href="<?= $url ?>" class="fab-item" target="_blank" rel="noopener noreferrer" title="<?= $name ?>">
                    <?php if ($icon['type'] === 'image'): ?>
                    <img class="fab-icon-img" src="<?= e($icon['value']) ?>" alt="" loading="lazy">
                    <?php else: ?>
                    <div><?= e($icon['value']) ?></div>
                    <?php endif; ?>
                    <div class="fab-text"><?= $name ?></div>
                    <span class="fab-label"><?= $name ?></span>
                </a>
                <?php endforeach; ?>
            </div>
            <button class="fab-main" id="fabMain">☰</button>
        </div>
    </div>
    
    <script>
        const fabMain = document.getElementById('fabMain');
        const fabItems = document.getElementById('fabItems');
        
        fabMain.addEventListener('click', () => {
            fabMain.classList.toggle('active');
            fabItems.classList.toggle('active');
        });
        
        document.querySelectorAll('.fab-item').forEach(item => {
            item.addEventListener('click', () => {
                fabMain.classList.remove('active');
                fabItems.classList.remove('active');
            });
        });
        
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.fab-container')) {
                fabMain.classList.remove('active');
                fabItems.classList.remove('active');
            }
        });
    </script>
</body>
</html>
