<?php
/**
 * 高级导航页模板
 * @label 高级导航页
 * @fields nav,title,desc
 * @copyright 2026 wenyinos <ruojiner@hotmail.com>
 * @license MIT License
 */
require_once __DIR__ . '/_helpers.php';

$title = template_value($site_title ?? '', '导航中心');
$desc = template_value($site_description ?? '', '精选链接导航');
$links = template_nav_links($target_url ?? '', [
    ['name' => '示例链接 1', 'url' => 'https://example.com', 'icon' => '🚀'],
    ['name' => '示例链接 2', 'url' => 'https://example.com', 'icon' => '💡'],
    ['name' => '示例链接 3', 'url' => 'https://example.com', 'icon' => '🎯'],
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
            background: linear-gradient(135deg, #0f0f0f 0%, #1a1a2e 100%);
            min-height: 100vh;
            padding: 60px 20px;
            color: #fff;
            position: relative;
            overflow-x: hidden;
        }
        .stars {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }
        .star {
            position: absolute;
            width: 2px;
            height: 2px;
            background: white;
            border-radius: 50%;
            opacity: 0.3;
            animation: twinkle 3s infinite;
        }
        @keyframes twinkle {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 0.8; }
        }
        .glow {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.1;
            z-index: 0;
        }
        .glow1 {
            width: 300px;
            height: 300px;
            background: #6366f1;
            top: -100px;
            left: -100px;
            animation: float 8s ease-in-out infinite;
        }
        .glow2 {
            width: 300px;
            height: 300px;
            background: #a855f7;
            bottom: -100px;
            right: -100px;
            animation: float 10s ease-in-out infinite reverse;
        }
        @keyframes float {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(30px, 30px); }
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 10;
        }
        .header {
            text-align: center;
            margin-bottom: 80px;
        }
        .header h1 {
            font-size: 48px;
            margin-bottom: 16px;
            font-weight: 800;
            letter-spacing: -1.5px;
            background: linear-gradient(135deg, #6366f1, #a855f7, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .header p {
            font-size: 16px;
            color: #999;
            max-width: 500px;
            margin: 0 auto;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .link-card {
            position: relative;
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(168, 85, 247, 0.05) 100%);
            border: 1px solid rgba(99, 102, 241, 0.2);
            border-radius: 16px;
            padding: 32px 24px;
            text-decoration: none;
            color: inherit;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 200px;
        }
        .link-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.5s;
            z-index: 1;
        }
        .link-card:hover::before {
            left: 100%;
        }
        .link-card::after {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at var(--x, 50%) var(--y, 50%), rgba(99, 102, 241, 0.1) 0%, transparent 80%);
            opacity: 0;
            transition: opacity 0.3s;
            z-index: 0;
        }
        .link-card:hover::after {
            opacity: 1;
        }
        .link-card:hover {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(168, 85, 247, 0.1) 100%);
            border-color: rgba(99, 102, 241, 0.4);
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(99, 102, 241, 0.2);
        }
        .card-content {
            position: relative;
            z-index: 2;
            text-align: center;
        }
        .card-icon {
            font-size: 56px;
            margin-bottom: 16px;
            display: inline-block;
            transition: transform 0.3s;
        }
        .card-icon img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 18px;
            box-shadow: 0 14px 28px rgba(17, 22, 38, 0.25);
        }
        .link-card:hover .card-icon {
            transform: scale(1.15) rotate(5deg);
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
            line-height: 1.4;
        }
        .footer {
            text-align: center;
            color: #666;
            font-size: 13px;
            margin-top: 60px;
        }
        @media (max-width: 768px) {
            .header h1 {
                font-size: 32px;
            }
            .grid {
                grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
                gap: 16px;
            }
            .link-card {
                min-height: 160px;
                padding: 24px 16px;
            }
            .card-icon {
                font-size: 40px;
            }
            .card-title {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="stars" id="stars"></div>
    <div class="glow glow1"></div>
    <div class="glow glow2"></div>
    
    <div class="container">
        <div class="header">
            <h1><?= e($title) ?></h1>
            <p><?= e($desc) ?></p>
        </div>
        
        <div class="grid">
            <?php 
                $defaultIcons = ['🔗', '📱', '💻', '🌐', '📧', '📞', '🎯', '🚀', '⭐', '💡', '🎨', '🔧', '📊', '🎬', '🎵', '📚'];
                foreach ($links as $i => $link): 
                    $name = e($link['name'] ?? '链接 ' . ($i + 1));
                    $url = e($link['url'] ?? '#');
                    $icon = template_icon_data($link['icon'] ?? '', $defaultIcons[$i % count($defaultIcons)]);
            ?>
            <a href="<?= $url ?>" class="link-card" target="_blank" rel="noopener noreferrer" onmousemove="this.style.setProperty('--x', (event.clientX - this.getBoundingClientRect().left) / this.offsetWidth * 100 + '%'); this.style.setProperty('--y', (event.clientY - this.getBoundingClientRect().top) / this.offsetHeight * 100 + '%')">
                <div class="card-content">
                    <div class="card-icon">
                        <?php if ($icon['type'] === 'image'): ?>
                        <img src="<?= e($icon['value']) ?>" alt="" loading="lazy">
                        <?php else: ?>
                        <?= e($icon['value']) ?>
                        <?php endif; ?>
                    </div>
                    <div class="card-title"><?= $name ?></div>
                    <div class="card-url"><?= $url ?></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        
        <div class="footer">
            <p>✨ 精选导航 · 快速访问</p>
        </div>
    </div>
    
    <script>
        // 生成星星背景
        const starsContainer = document.getElementById('stars');
        for (let i = 0; i < 50; i++) {
            const star = document.createElement('div');
            star.className = 'star';
            star.style.left = Math.random() * 100 + '%';
            star.style.top = Math.random() * 100 + '%';
            star.style.animationDelay = Math.random() * 3 + 's';
            starsContainer.appendChild(star);
        }
    </script>
</body>
</html>
