<?php
/**
 * 商务极简导航模板
 * @label 商务极简导航
 * @fields nav,title,desc
 * @copyright 2026 wenyinos <ruojiner@hotmail.com>
 * @license MIT License
 */
require_once __DIR__ . '/_helpers.php';

if (!function_exists('apex_nav_host')) {
    function apex_nav_host(string $url): string {
        $host = parse_url($url, PHP_URL_HOST);
        return $host ? strtolower($host) : '目标站点';
    }
}

$title = template_value($site_title ?? '', '访问导航');
$desc = template_value($site_description ?? '', '请选择可用线路继续访问，如当前入口不稳定，可切换其他线路。');
$links = template_nav_links($target_url ?? '', [
    ['name' => '主线路', 'url' => 'https://example.com'],
    ['name' => '备用线路', 'url' => 'https://example.com/path'],
    ['name' => '高速线路', 'url' => 'https://example.com/fast'],
]);

$host_label = e($_SERVER['HTTP_HOST'] ?? '当前域名');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?></title>
    <meta name="description" content="<?= e($desc) ?>">
    <style>
        :root {
            --bg: #0a0c12;
            --panel: rgba(14, 18, 28, 0.92);
            --panel-soft: rgba(255, 255, 255, 0.03);
            --border: rgba(255, 255, 255, 0.08);
            --border-strong: rgba(167, 139, 250, 0.22);
            --text: #edf1f7;
            --muted: #8f97ab;
            --accent: #a78bfa;
            --shadow: 0 24px 48px rgba(0, 0, 0, 0.28);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            font-family: "Segoe UI", "PingFang SC", "Microsoft YaHei", sans-serif;
            color: var(--text);
            background:
                radial-gradient(ellipse 70% 52% at 50% 0%, rgba(99, 70, 255, 0.12) 0%, transparent 72%),
                repeating-linear-gradient(0deg, transparent, transparent 39px, rgba(255,255,255,.022) 39px, rgba(255,255,255,.022) 40px),
                repeating-linear-gradient(90deg, transparent, transparent 39px, rgba(255,255,255,.022) 39px, rgba(255,255,255,.022) 40px),
                var(--bg);
            padding: 34px 0;
        }

        .page {
            width: min(700px, calc(100% - 52px));
            margin: 0 auto;
        }

        .hero {
            border: 1px solid var(--border);
            border-radius: 28px;
            background: var(--panel);
            box-shadow: var(--shadow);
            padding: 26px 24px 22px;
        }

        .tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 999px;
            border: 1px solid rgba(167, 139, 250, 0.3);
            background: rgba(167, 139, 250, 0.1);
            color: #c4b5fd;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .08em;
            margin-bottom: 22px;
        }

        .tag::before {
            content: "";
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: currentColor;
        }

        .hero h1 {
            font-size: clamp(32px, 5vw, 52px);
            line-height: 1.08;
            letter-spacing: -.03em;
            margin-bottom: 14px;
            font-weight: 800;
        }

        .hero p {
            max-width: 640px;
            color: var(--muted);
            font-size: 15px;
            line-height: 1.8;
        }

        .host {
            margin-top: 18px;
            color: rgba(237, 241, 247, 0.72);
            font-size: 13px;
        }

        .routes {
            margin-top: 20px;
            border: 1px solid var(--border);
            border-radius: 28px;
            background: var(--panel);
            box-shadow: var(--shadow);
            padding: 16px;
        }

        .routes-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 8px 6px 18px;
            border-bottom: 1px solid rgba(255,255,255,.06);
            margin-bottom: 18px;
        }

        .routes-head h2 {
            font-size: 22px;
            letter-spacing: -.02em;
        }

        .routes-head span {
            color: var(--muted);
            font-size: 13px;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }

        .card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            text-decoration: none;
            color: inherit;
            border: 1px solid var(--border);
            border-radius: 18px;
            background: var(--panel-soft);
            padding: 14px 16px;
            transition: border-color .2s ease, transform .2s ease, background .2s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            border-color: var(--border-strong);
            background: rgba(255, 255, 255, 0.045);
        }

        .card-main {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
            flex: 1;
        }

        .mark {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            overflow: hidden;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.08);
            font-size: 14px;
            font-weight: 800;
            color: #d7d2ff;
            flex-shrink: 0;
        }

        .mark img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .card-body {
            min-width: 0;
            flex: 1;
        }

        .card-head {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 6px;
            min-width: 0;
        }

        .badge {
            padding: 4px 9px;
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,.08);
            color: var(--muted);
            font-size: 11px;
            white-space: nowrap;
        }

        .name {
            font-size: 20px;
            font-weight: 700;
            letter-spacing: -.02em;
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .domain {
            color: #d9def0;
            font-size: 14px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .visit-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 88px;
            height: 40px;
            padding: 0 18px;
            border-radius: 999px;
            background: rgba(167, 139, 250, 0.12);
            border: 1px solid rgba(167, 139, 250, 0.26);
            color: #d8cbff;
            font-size: 13px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .note {
            margin-top: 14px;
            padding: 0 4px;
            color: rgba(143, 151, 171, 0.88);
            font-size: 12px;
            line-height: 1.7;
        }

        @media (max-width: 960px) {
            .page {
                width: min(660px, calc(100% - 44px));
            }
        }

        @media (max-width: 720px) {
            body {
                padding: 18px 0;
            }

            .page {
                width: min(390px, calc(100% - 24px));
            }

            .hero,
            .routes {
                border-radius: 22px;
                padding: 16px 14px;
            }

            .routes-head {
                flex-direction: column;
                align-items: flex-start;
            }

            .card {
                width: min(100%, 360px);
                margin: 0 auto;
                padding: 13px 14px;
                gap: 12px;
            }

            .hero h1 {
                font-size: 30px;
            }

            .name {
                font-size: 18px;
            }

            .domain {
                font-size: 13px;
            }

            .visit-btn {
                min-width: 76px;
                height: 36px;
                padding: 0 14px;
                font-size: 12px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            * {
                transition: none !important;
            }
        }
    </style>
</head>
<body>
    <main class="page">
        <section class="hero">
            <div class="tag">导航页</div>
            <h1><?= e($title) ?></h1>
            <p><?= e($desc) ?></p>
            <div class="host">当前域名：<?= $host_label ?></div>
        </section>

        <section class="routes">
            <div class="routes-head">
                <h2>可用线路</h2>
                <span>共 <?= count($links) ?> 条</span>
            </div>

            <div class="grid">
                <?php foreach ($links as $index => $link): ?>
                    <?php
                        $icon = template_icon_data($link['icon'] ?? '', str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT));
                        $label = $index === 0 ? '推荐' : '线路 ' . str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT);
                    ?>
                    <a class="card" href="<?= e($link['url']) ?>" target="_blank" rel="noopener noreferrer">
                        <div class="card-main">
                            <span class="mark">
                                <?php if ($icon['type'] === 'image'): ?>
                                <img src="<?= e($icon['value']) ?>" alt="" loading="lazy">
                                <?php else: ?>
                                <?= e($icon['value']) ?>
                                <?php endif; ?>
                            </span>

                            <div class="card-body">
                                <div class="card-head">
                                    <div class="name"><?= e($link['name']) ?></div>
                                    <span class="badge"><?= e($label) ?></span>
                                </div>
                                <div class="domain"><?= e(apex_nav_host($link['url'])) ?></div>
                            </div>
                        </div>

                        <span class="visit-btn">访问</span>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="note">如果当前线路访问较慢，可切换其他入口继续访问。</div>
        </section>
    </main>
</body>
</html>
