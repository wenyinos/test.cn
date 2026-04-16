<?php
/**
 * 黑金品牌落地页
 * @label 黑金品牌落地页
 * @fields nav,title,desc,img,blackgold
 * @copyright 2026 wenyinos <ruojiner@hotmail.com>
 * @license MIT License
 */
require_once __DIR__ . '/_helpers.php';

if (!function_exists('blackgold_host')) {
    function blackgold_host(string $url): string {
        $host = parse_url($url, PHP_URL_HOST);
        return $host ? strtolower($host) : $url;
    }

    function blackgold_group_key(array $link): string {
        $group = trim((string)($link['group'] ?? ''));
        if (in_array($group, ['official', 'download', 'backup', 'service'], true)) {
            return $group;
        }

        $name = trim((string)($link['name'] ?? ''));
        if ($name !== '' && preg_match('/^\s*(?:\[(官网|下载|备用|客服)\]|(官网|下载|备用|客服)\s*[:：\-｜|])/u', $name, $m)) {
            $flag = $m[1] ?: $m[2];
            if ($flag === '官网') return 'official';
            if ($flag === '下载') return 'download';
            if ($flag === '备用') return 'backup';
            if ($flag === '客服') return 'service';
        }

        return 'official';
    }

    function blackgold_clean_name(array $link): string {
        $name = trim((string)($link['name'] ?? ''));
        $name = preg_replace('/^\s*(?:\[(官网|下载|备用|客服)\]|(官网|下载|备用|客服)\s*[:：\-｜|])\s*/u', '', $name);
        return $name !== '' ? $name : blackgold_host((string)($link['url'] ?? ''));
    }

    function blackgold_display_label(array $link): string {
        $name = trim((string)($link['name'] ?? ''));
        $host = blackgold_host((string)($link['url'] ?? ''));
        if ($name === '') {
            return $host;
        }
        if (preg_match('/^[\p{Han}A-Za-z]{1,4}$/u', $name)) {
            return $host;
        }
        return blackgold_clean_name($link);
    }
}

$brand = template_value($site_title ?? '', '品牌导航');
$desc = template_value($site_description ?? '', '多线路稳定访问，手机与电脑均可快速打开。');
$nav_payload = template_nav_payload($target_url ?? '', [
    ['name' => '主线路', 'url' => 'https://example.com'],
    ['name' => '备用线路', 'url' => 'https://example.com/backup'],
    ['name' => '高速线路', 'url' => 'https://example.com/fast'],
    ['name' => '下载入口', 'url' => 'https://example.com/app'],
]);
$links = $nav_payload['links'];
$meta = sanitize_blackgold_meta(is_array($nav_payload['meta'] ?? null) ? $nav_payload['meta'] : []);
$brand_subtitle = template_value($meta['brand_subtitle'] ?? '', '官方访问入口');
$headline_line1 = template_value($meta['headline_line1'] ?? '', '多线路访问体验');
$headline_line2_prefix = template_value($meta['headline_line2_prefix'] ?? '', '尽在');
$headline_line2_highlight = template_value($meta['headline_line2_highlight'] ?? '', $brand);

$grouped_links = [
    'official' => [],
    'download' => [],
    'backup' => [],
    'service' => [],
];

foreach ($links as $link) {
    $group = blackgold_group_key($link);
    $link['name'] = blackgold_clean_name($link);
    $grouped_links[$group][] = $link;
}

$all_links = [];
foreach ($grouped_links as $group_items) {
    foreach ($group_items as $item) {
        $all_links[] = $item;
    }
}

$hero_links = [];
$hero_seen = [];
foreach (['official', 'backup', 'download', 'service'] as $hero_group) {
    foreach ($grouped_links[$hero_group] as $item) {
        $hero_key = strtolower(trim((string)($item['url'] ?? '')));
        if ($hero_key === '' || isset($hero_seen[$hero_key])) {
            continue;
        }
        $hero_seen[$hero_key] = true;
        $hero_links[] = $item;
        if (count($hero_links) >= 3) {
            break 2;
        }
    }
}

if (empty($hero_links)) {
    $hero_links = array_slice($all_links, 0, 3);
}

$hero_bar_items = array_slice(array_values(array_filter(
    $meta['hero_bars'] ?? [],
    static fn($item): bool => trim((string)$item) !== ''
)), 0, 3);

if (empty($hero_bar_items)) {
    $hero_bar_items = array_map(
        static fn(array $link): string => blackgold_display_label($link),
        $hero_links
    );
}

$drawer_links = [
    'groups' => [
        'official' => array_slice($grouped_links['official'], 0, 8),
        'download' => array_slice($grouped_links['download'], 0, 8),
        'backup' => array_slice($grouped_links['backup'], 0, 8),
        'service' => array_slice($grouped_links['service'], 0, 8),
    ],
    'all' => array_slice($all_links, 0, 8),
];

$background_image = template_href($img_url ?? '', '');
$action_cards = [
    ['key' => 'official', 'title' => '进入官网', 'sub' => 'OFFICIAL'],
    ['key' => 'download', 'title' => 'APP 下载', 'sub' => 'DOWNLOAD'],
    ['key' => 'backup', 'title' => '备用网址', 'sub' => 'BACKUP'],
    ['key' => 'service', 'title' => '在线客服', 'sub' => 'SERVICE'],
];
$action_titles = [
    'official' => '官方入口',
    'download' => '下载入口',
    'backup' => '备用网址',
    'service' => '在线客服',
];
$action_button_labels = [
    'official' => '立即进入',
    'download' => '下载 APP',
    'backup' => '立即进入',
    'service' => '立即联系',
];
$promo_tags = $meta['promo_tags'] ?? default_blackgold_meta()['promo_tags'];
$category_items = $meta['categories'] ?? default_blackgold_meta()['categories'];
$category_count = max(1, min(6, count($category_items)));
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($brand) ?></title>
    <meta name="description" content="<?= e($desc) ?>">
    <style>
        :root {
            --bg: #0a0a0a;
            --panel: rgba(10, 10, 10, 0.94);
            --panel-soft: rgba(255, 255, 255, 0.03);
            --line: rgba(255, 255, 255, 0.08);
            --gold: #ffd42a;
            --gold-deep: #e3b100;
            --text: #f4f0df;
            --muted: rgba(255, 255, 255, 0.75);
            --shadow: 0 26px 58px rgba(0, 0, 0, 0.42);
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
            background: #0f0f0f;
        }

        .page {
            width: 100%;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        .frame {
            position: relative;
            overflow: hidden;
            min-height: 100vh;
            border-radius: 0;
            border: 0;
            background:
                linear-gradient(180deg, rgba(0,0,0,.12), rgba(0,0,0,.42)),
                <?php if ($background_image !== ''): ?>
                url('<?= e($background_image) ?>') center top / cover no-repeat,
                <?php else: ?>
                radial-gradient(circle at 50% 12%, rgba(255,255,255,.06), transparent 26%),
                <?php endif; ?>
                var(--panel);
            box-shadow: none;
        }

        .frame::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(0,0,0,.04), rgba(0,0,0,.68));
            pointer-events: none;
        }

        .visual {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            padding: 18px 12px 16px;
        }

        .content-grid {
            display: block;
        }

        .hero-stage {
            position: relative;
            min-height: 396px;
            margin-top: 10px;
        }

        .mock-building {
            position: absolute;
            left: 44px;
            top: 22px;
            width: 208px;
            height: 312px;
            opacity: .28;
            transform: rotate(-9deg);
            pointer-events: none;
            background:
                linear-gradient(180deg, rgba(255,255,255,.2), rgba(255,255,255,.04)),
                repeating-linear-gradient(90deg, transparent 0 14px, rgba(255,255,255,.03) 14px 16px),
                repeating-linear-gradient(180deg, transparent 0 16px, rgba(255,255,255,.025) 16px 18px),
                linear-gradient(135deg, rgba(255,255,255,.08), rgba(255,255,255,.02));
            clip-path: polygon(10% 0, 88% 0, 100% 78%, 14% 100%, 0 24%);
            filter: grayscale(1);
        }

        .outline-box {
            position: absolute;
            left: 6px;
            top: 18px;
            width: 108px;
            height: 226px;
            border: 1px solid rgba(255, 214, 42, 0.62);
            pointer-events: none;
        }

        .brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 0;
        }

        .brand-mark {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            position: relative;
            flex-shrink: 0;
            background:
                radial-gradient(circle at center, #0a0a0a 0 9px, transparent 9px),
                repeating-conic-gradient(from 0deg, var(--gold) 0 11deg, #a86a00 11deg 18deg);
        }

        .brand-mark::before {
            content: "";
            position: absolute;
            inset: 7px;
            border-radius: 50%;
            background: radial-gradient(circle at center, #0a0a0a 0 7px, transparent 7px);
        }

        .brand-copy {
            min-width: 0;
            text-align: left;
        }

        .brand h1 {
            color: var(--gold);
            font-size: 26px;
            line-height: 1;
            letter-spacing: -.02em;
            font-weight: 800;
            margin-bottom: 2px;
            text-shadow: 0 2px 8px rgba(0,0,0,.25);
        }

        .brand p {
            color: rgba(255, 212, 42, 0.82);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .08em;
        }

        .hero-main {
            position: relative;
            display: grid;
            grid-template-columns: minmax(0, 1fr) 104px;
            gap: 10px;
            align-items: start;
            padding: 84px 18px 0 54px;
        }

        .domain-list {
            position: relative;
            z-index: 1;
            display: grid;
            gap: 8px;
        }

        .domain-btn {
            display: block;
            width: 100%;
            padding: 7px 12px 8px;
            background: linear-gradient(180deg, #ffd837, #e3b000);
            color: #fefefe;
            text-decoration: none;
            font-size: 17px;
            font-weight: 900;
            line-height: 1.1;
            border-radius: 2px;
            letter-spacing: -.01em;
            box-shadow: inset 0 -2px 0 rgba(0,0,0,.18);
            text-shadow:
                -1px -1px 0 rgba(0,0,0,.38),
                1px -1px 0 rgba(0,0,0,.38),
                -1px 1px 0 rgba(0,0,0,.38),
                1px 1px 0 rgba(0,0,0,.38);
            cursor: default;
            user-select: none;
        }

        .promo-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding-top: 2px;
            justify-self: end;
        }

        .promo {
            min-width: 96px;
            padding: 7px 8px;
            border-radius: 999px;
            border: 1px solid currentColor;
            font-size: 10px;
            text-align: center;
            line-height: 1;
            background: rgba(0,0,0,.34);
            white-space: nowrap;
        }

        .promo.red { color: #ff5a4d; }
        .promo.gold { color: #ffc862; }
        .promo.green { color: #aaf43d; }
        .promo.cyan { color: #4de3d6; }

        .headline {
            position: relative;
            z-index: 1;
            margin: 18px 24px 0 18px;
            font-size: 20px;
            line-height: 1.18;
            font-weight: 700;
            text-shadow: 0 4px 14px rgba(0,0,0,.36);
        }

        .headline span {
            color: var(--gold);
        }

        .categories {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 7px;
            margin: 18px 0 0;
            padding: 10px 10px 8px;
            border-radius: 16px;
            background: rgba(255,255,255,.11);
            backdrop-filter: blur(10px);
            box-shadow: inset 0 1px 0 rgba(255,255,255,.05);
        }

        .category {
            text-align: center;
        }

        .category-icon {
            width: 44px;
            height: 44px;
            margin: 0 auto 5px;
            border-radius: 50%;
            border: 2px solid rgba(255,255,255,.5);
            background:
                radial-gradient(circle at 35% 30%, rgba(255,255,255,.94), rgba(255,255,255,.1)),
                linear-gradient(135deg, #7d7d7d, #272727);
            box-shadow: inset 0 -5px 10px rgba(0,0,0,.25);
        }

        .category-icon.is-image,
        .category-icon.is-text {
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .category-icon.is-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            border-radius: 50%;
        }

        .category-icon.is-text span {
            color: #fff;
            font-size: 15px;
            font-weight: 700;
        }

        .category span {
            display: block;
            color: #fff;
            font-size: 10px;
        }

        .category:nth-child(1) .category-icon {
            background:
                radial-gradient(circle at 30% 28%, rgba(255,255,255,.94), rgba(255,255,255,.08)),
                linear-gradient(135deg, #bd2b21, #4e0907);
        }

        .category:nth-child(2) .category-icon {
            background:
                radial-gradient(circle at 32% 28%, rgba(255,255,255,.94), rgba(255,255,255,.08)),
                linear-gradient(135deg, #39495f, #10161f);
        }

        .category:nth-child(3) .category-icon {
            background:
                radial-gradient(circle at 30% 28%, rgba(255,255,255,.94), rgba(255,255,255,.08)),
                linear-gradient(135deg, #bf9a73, #5d3111);
        }

        .category:nth-child(4) .category-icon {
            background:
                radial-gradient(circle at 30% 28%, rgba(255,255,255,.94), rgba(255,255,255,.08)),
                linear-gradient(135deg, #d33b1f, #7a1a09);
        }

        .category:nth-child(5) .category-icon {
            background:
                radial-gradient(circle at 30% 28%, rgba(255,255,255,.94), rgba(255,255,255,.08)),
                linear-gradient(135deg, #4e81d2, #132952);
        }

        .category:nth-child(6) .category-icon {
            background:
                radial-gradient(circle at 30% 28%, rgba(255,255,255,.94), rgba(255,255,255,.08)),
                linear-gradient(135deg, #8a5927, #2a1607);
        }

        .actions {
            position: relative;
            z-index: 1;
            margin: 18px 8px 0;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1px;
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.08);
        }

        .side-panel {
            position: relative;
            z-index: 1;
            margin-top: 18px;
        }

        .panel-desc {
            display: none;
        }

        .device-panel {
            display: none;
        }

        .action-card {
            appearance: none;
            border: 0;
            background: rgba(13, 13, 13, 0.96);
            color: inherit;
            padding: 18px 16px 14px;
            text-align: left;
            min-height: 76px;
            cursor: pointer;
            transition: background .2s ease, color .2s ease;
        }

        .action-card.active,
        .action-card:hover {
            background: linear-gradient(180deg, #ffd52f, #d9a800);
            color: #111;
        }

        .action-icon {
            width: 28px;
            height: 28px;
            margin-bottom: 10px;
            color: var(--gold);
        }

        .action-icon svg {
            width: 100%;
            height: 100%;
            display: block;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .action-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 4px;
            color: #f4f0df;
        }

        .action-sub {
            font-size: 11px;
            letter-spacing: .08em;
            color: rgba(255,255,255,.48);
        }

        .action-card.active .action-icon,
        .action-card:hover .action-icon,
        .action-card.active .action-title,
        .action-card:hover .action-title,
        .action-card.active .action-sub,
        .action-card:hover .action-sub {
            color: #111;
        }

        .copyright {
            position: relative;
            z-index: 1;
            text-align: center;
            color: rgba(255,255,255,.82);
            font-size: 12px;
            padding: 10px 8px 0;
        }

        .overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.6);
            opacity: 0;
            pointer-events: none;
            transition: opacity .2s ease;
            z-index: 20;
        }

        .overlay.show {
            opacity: 1;
            pointer-events: auto;
        }

        .drawer {
            position: fixed;
            left: 50%;
            bottom: 14px;
            transform: translateX(-50%) translateY(24px);
            width: min(440px, calc(100% - 16px));
            background: rgba(12, 12, 12, 0.98);
            border: 1px solid rgba(255, 214, 42, 0.46);
            box-shadow: var(--shadow);
            opacity: 0;
            pointer-events: none;
            transition: transform .22s ease, opacity .22s ease;
            z-index: 21;
        }

        .drawer.show {
            opacity: 1;
            pointer-events: auto;
            transform: translateX(-50%) translateY(0);
        }

        .drawer-handle {
            width: 44px;
            height: 4px;
            border-radius: 999px;
            background: rgba(255,255,255,.18);
            margin: 9px auto 0;
        }

        .drawer-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 16px 8px;
            color: var(--gold);
            font-size: 18px;
            font-weight: 700;
        }

        .drawer-close {
            border: 0;
            background: transparent;
            color: rgba(255,255,255,.7);
            font-size: 24px;
            line-height: 1;
            cursor: pointer;
        }

        .drawer-list {
            padding: 0 12px 12px;
        }

        .drawer-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 4px;
            border-top: 1px solid rgba(255,255,255,.06);
        }

        .drawer-name {
            color: #fff4b0;
            font-size: 16px;
            font-weight: 700;
        }

        .drawer-host {
            color: rgba(255,255,255,.76);
            font-size: 12px;
            margin-top: 4px;
        }

        .drawer-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 96px;
            height: 34px;
            padding: 0 14px;
            border-radius: 999px;
            background: linear-gradient(180deg, #ffd52f, #d9a800);
            color: #111;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            flex-shrink: 0;
        }

        @media (min-width: 640px) and (max-width: 1099px) {
            body {
                background:
                    radial-gradient(circle at top, rgba(255, 212, 42, 0.06), transparent 28%),
                    linear-gradient(180deg, #efefef, #f8f8f8 40%, #ececec);
            }

            .page {
                width: min(468px, calc(100% - 28px));
                margin: 0 auto;
                padding: 26px 0 34px;
            }

            .frame {
                border: 1px solid rgba(255, 214, 42, 0.18);
                border-radius: 28px;
                border-color: rgba(255, 214, 42, 0.18);
                box-shadow: 0 26px 66px rgba(0, 0, 0, 0.16);
            }

            .visual {
                min-height: 820px;
                padding: 28px 14px 20px;
            }

            .brand {
                gap: 12px;
                margin-bottom: 6px;
            }

            .brand-mark {
                width: 52px;
                height: 52px;
                background:
                    radial-gradient(circle at center, #0a0a0a 0 10px, transparent 10px),
                    repeating-conic-gradient(from 0deg, var(--gold) 0 11deg, #a86a00 11deg 18deg);
            }

            .brand-mark::before {
                inset: 8px;
                background: radial-gradient(circle at center, #0a0a0a 0 8px, transparent 8px);
            }

            .brand h1 {
                font-size: 38px;
                margin-bottom: 3px;
            }

            .brand p {
                font-size: 12px;
                letter-spacing: .1em;
            }

            .hero-stage {
                min-height: 470px;
                margin-top: 14px;
            }

            .mock-building {
                left: 52px;
                top: 24px;
                width: 246px;
                height: 372px;
                opacity: .3;
            }

            .outline-box {
                left: 12px;
                top: 26px;
                width: 122px;
                height: 250px;
            }

            .hero-main {
                grid-template-columns: minmax(0, 1fr) 120px;
                gap: 12px;
                padding: 96px 20px 0 62px;
            }

            .domain-list {
                gap: 9px;
            }

            .domain-btn {
                font-size: 20px;
                padding: 8px 14px 9px;
            }

            .promo {
                min-width: 110px;
                padding: 8px 10px;
                font-size: 11px;
            }

            .headline {
                margin: 18px 28px 0 22px;
                font-size: 26px;
                line-height: 1.16;
            }

            .categories {
                margin: 22px 4px 0;
                padding: 10px 12px 8px;
                gap: 8px;
            }

            .category-icon {
                width: 50px;
                height: 50px;
                margin-bottom: 6px;
            }

            .category-icon.is-text span {
                font-size: 16px;
            }

            .category span {
                font-size: 11px;
            }

            .side-panel {
                margin-top: 18px;
            }

            .panel-desc {
                display: none;
            }

            .actions {
                margin: 20px 4px 0;
            }

            .action-card {
                min-height: 92px;
                padding: 20px 18px 16px;
            }

            .action-icon {
                width: 30px;
                height: 30px;
                margin-bottom: 12px;
            }

            .action-title {
                font-size: 18px;
                margin-bottom: 6px;
            }

            .action-sub {
                font-size: 12px;
            }

            .copyright {
                padding: 16px 4px 4px;
                font-size: 13px;
                letter-spacing: .04em;
            }

            .drawer {
                width: min(520px, calc(100% - 36px));
                bottom: 20px;
            }
        }

        @media (min-width: 1100px) {
            body {
                min-width: 1240px;
                background: #0f0f0f;
            }

            .page {
                width: calc(100% - 24px);
                margin: 12px;
                padding: 0;
            }

            .frame {
                position: relative;
                min-height: calc(100vh - 24px);
                border-radius: 0;
                border: 0;
                box-shadow: none;
                background: #111;
            }

            .frame::before {
                display: none;
            }

            .visual {
                min-height: calc(100vh - 24px);
                padding: 0;
            }

            .brand {
                position: absolute;
                left: 34px;
                top: 30px;
                z-index: 10;
                justify-content: flex-start;
                gap: 16px;
                margin: 0;
            }

            .brand-mark {
                width: 72px;
                height: 72px;
                background:
                    radial-gradient(circle at center, #0a0a0a 0 14px, transparent 14px),
                    repeating-conic-gradient(from 0deg, var(--gold) 0 12deg, #a86a00 12deg 18deg);
            }

            .brand-mark::before {
                inset: 10px;
                background: radial-gradient(circle at center, #0a0a0a 0 10px, transparent 10px);
            }

            .brand h1 {
                font-size: 58px;
                line-height: .98;
                margin-bottom: 6px;
            }

            .brand p {
                font-size: 19px;
                font-style: italic;
                letter-spacing: .01em;
            }

            .content-grid {
                position: relative;
                height: calc(100vh - 24px);
                min-height: 760px;
                display: block;
            }

            .hero-stage {
                position: absolute;
                left: 0;
                top: 0;
                bottom: 0;
                width: 50%;
                margin: 0;
                min-height: 0;
                background:
                    linear-gradient(180deg, rgba(0,0,0,.08), rgba(0,0,0,.5)),
                    <?php if ($background_image !== ''): ?>
                    url('<?= e($background_image) ?>') center bottom / cover no-repeat;
                    <?php else: ?>
                    radial-gradient(circle at 30% 35%, rgba(255,255,255,.05), transparent 28%),
                    #151515;
                    <?php endif; ?>
                overflow: hidden;
            }

            .hero-stage::before {
                content: "";
                position: absolute;
                inset: 0;
                background: linear-gradient(180deg, rgba(0,0,0,.04), rgba(0,0,0,.38));
                pointer-events: none;
            }

            .mock-building {
                left: 104px;
                top: 82px;
                width: 372px;
                height: 566px;
                opacity: .34;
                transform: rotate(-8deg);
            }

            .outline-box {
                left: 32px;
                top: 206px;
                width: 286px;
                height: 468px;
                border-width: 2px;
            }

            .hero-main {
                position: absolute;
                left: 96px;
                top: 286px;
                width: calc(100% - 132px);
                display: grid;
                grid-template-columns: minmax(0, 1fr) 192px;
                gap: 28px;
                padding: 0;
            }

            .domain-list {
                gap: 14px;
                max-width: 520px;
                position: relative;
                padding-left: 20px;
            }

            .domain-list::before,
            .domain-list::after {
                content: "";
                position: absolute;
                left: 0;
                width: 6px;
                background: var(--gold);
            }

            .domain-list::before {
                top: 10px;
                height: 54px;
            }

            .domain-list::after {
                top: 90px;
                height: 54px;
            }

            .domain-btn {
                width: fit-content;
                min-width: 470px;
                max-width: 100%;
                font-size: 34px;
                line-height: 1;
                padding: 12px 22px 14px;
                box-shadow: inset 0 -4px 0 rgba(0,0,0,.14);
                border-radius: 0;
            }

            .promo-list {
                gap: 28px;
                justify-self: end;
            }

            .promo {
                min-width: 170px;
                padding: 13px 14px;
                font-size: 17px;
                background: rgba(0,0,0,.24);
            }

            .headline {
                position: absolute;
                left: 82px;
                top: 492px;
                margin: 0;
                max-width: 660px;
                font-size: 64px;
                font-weight: 400;
                line-height: 1.06;
                letter-spacing: -.03em;
            }

            .headline span {
                font-weight: 700;
            }

            .categories {
                position: absolute;
                left: 102px;
                bottom: 48px;
                width: 640px;
                margin: 0;
                padding: 16px 18px 12px;
                gap: 14px;
                border-radius: 18px;
                background: rgba(255,255,255,.16);
            }

            .category-icon {
                width: 76px;
                height: 76px;
                margin-bottom: 8px;
                border-width: 3px;
            }

            .category-icon.is-text span {
                font-size: 22px;
            }

            .category span {
                font-size: 16px;
            }

            .side-panel {
                position: absolute;
                right: 0;
                top: 0;
                bottom: 0;
                width: 50%;
                margin: 0;
                padding: 18px 18px 16px 0;
                background: #101010;
            }

            .panel-desc {
                display: none;
            }

            .device-panel {
                display: block;
                position: absolute;
                left: 20px;
                right: 18px;
                top: 20px;
                height: calc(50% - 34px);
                overflow: hidden;
                border: 1px solid #292929;
                background: #000;
            }

            .device-copy {
                position: absolute;
                top: 106px;
                font-size: 60px;
                font-weight: 300;
                color: rgba(255,255,255,.92);
                letter-spacing: .02em;
            }

            .device-copy.left {
                left: 52px;
            }

            .device-copy.right {
                right: 52px;
                color: rgba(255, 212, 42, .92);
            }

            .device-word {
                position: absolute;
                left: 40px;
                right: 40px;
                top: 148px;
                text-align: center;
                font-size: 162px;
                font-weight: 800;
                line-height: 1;
                color: rgba(255,255,255,.06);
                letter-spacing: .08em;
                user-select: none;
            }

            .device-phone {
                position: absolute;
                left: 50%;
                top: 28px;
                transform: translateX(-50%);
                width: 236px;
                height: 458px;
                border-radius: 38px;
                background: #050505;
                border: 4px solid rgba(255,255,255,.26);
                box-shadow: 0 12px 28px rgba(0,0,0,.32);
            }

            .device-phone::before {
                content: "";
                position: absolute;
                left: 50%;
                top: 10px;
                transform: translateX(-50%);
                width: 102px;
                height: 18px;
                border-radius: 0 0 12px 12px;
                background: #121212;
                z-index: 2;
            }

            .device-screen {
                position: absolute;
                inset: 10px;
                border-radius: 30px;
                overflow: hidden;
                background: linear-gradient(180deg, #7a38d4 0 54px, #ffffff 54px 100%);
            }

            .device-topbar {
                height: 54px;
                display: flex;
                align-items: center;
                justify-content: center;
                color: rgba(255,255,255,.96);
                font-size: 13px;
                font-weight: 700;
            }

            .device-banner {
                margin: 12px;
                height: 78px;
                border-radius: 12px;
                background: linear-gradient(135deg, #f0b300, #ffde67);
            }

            .device-grid {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 10px;
                padding: 0 12px;
            }

            .device-app {
                height: 70px;
                border-radius: 14px;
                background: linear-gradient(135deg, #f4bf22, #ffe27e);
            }

            .device-app:nth-child(2n) {
                background: linear-gradient(135deg, #b85ddb, #efb0e8);
            }

            .device-app:nth-child(3n) {
                background: linear-gradient(135deg, #ff8733, #ffd06f);
            }

            .actions {
                position: absolute;
                left: 20px;
                right: 18px;
                bottom: 48px;
                margin: 0;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                border: 1px solid #292929;
                background: transparent;
            }

            .action-card {
                min-height: 148px;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 24px;
                padding: 24px 26px;
                background: #111;
            }

            .action-card.active,
            .action-card:hover {
                background: #111;
                color: inherit;
            }

            .action-icon {
                width: 54px;
                height: 54px;
                margin-bottom: 0;
                flex-shrink: 0;
            }

            .action-copy {
                min-width: 0;
                text-align: left;
            }

            .action-title {
                font-size: 26px;
                margin-bottom: 8px;
                color: #fff;
            }

            .action-sub {
                font-size: 15px;
                color: rgba(255,255,255,.42);
            }

            .action-card.active .action-icon,
            .action-card:hover .action-icon {
                color: var(--gold);
            }

            .action-card.active .action-title,
            .action-card:hover .action-title {
                color: #fff;
            }

            .action-card.active .action-sub,
            .action-card:hover .action-sub {
                color: rgba(255,255,255,.42);
            }

            .copyright {
                position: absolute;
                left: 50%;
                right: 18px;
                bottom: 12px;
                text-align: center;
                padding: 0;
                font-size: 14px;
            }

            .drawer {
                width: min(820px, calc(100% - 40px));
            }
        }

        @media (max-width: 360px) {
            .hero-stage {
                min-height: 374px;
            }

            .hero-main {
                grid-template-columns: minmax(0, 1fr) 104px;
                padding-top: 78px;
                padding-left: 42px;
                padding-right: 12px;
            }

            .domain-btn {
                font-size: 16px;
            }

            .headline {
                margin-left: 14px;
                font-size: 18px;
            }

            .outline-box {
                width: 98px;
            }

            .promo {
                min-width: 90px;
            }
        }
    </style>
</head>
<body>
    <main class="page">
        <section class="frame">
            <div class="visual">
                <div class="brand">
                    <div class="brand-mark"></div>
                    <div class="brand-copy">
                        <h1><?= e($brand) ?></h1>
                        <p><?= e($brand_subtitle) ?></p>
                    </div>
                </div>

                <div class="content-grid">
                    <div class="hero-stage">
                        <?php if ($background_image === ''): ?>
                        <div class="mock-building"></div>
                        <?php endif; ?>
                        <div class="outline-box"></div>

                        <div class="hero-main">
                            <div class="domain-list">
                                <?php foreach ($hero_bar_items as $item): ?>
                                <div class="domain-btn">
                                    <?= e($item) ?>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="promo-list">
                                <?php foreach ($promo_tags as $tag): ?>
                                <div class="promo <?= e($tag['style'] ?? 'gold') ?>"><?= e($tag['text']) ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="headline">
                            <?= e($headline_line1) ?><br>
                            <?= e($headline_line2_prefix) ?> <span><?= e($headline_line2_highlight) ?></span>
                        </div>

                        <div class="categories" style="grid-template-columns: repeat(<?= $category_count ?>, minmax(0, 1fr));">
                            <?php foreach ($category_items as $item): ?>
                            <?php $icon = template_icon_data($item['icon'] ?? '', ''); ?>
                            <div class="category">
                                <div class="category-icon<?= $icon['type'] === 'image' ? ' is-image' : ($icon['value'] !== '' ? ' is-text' : '') ?>">
                                    <?php if ($icon['type'] === 'image'): ?>
                                    <img src="<?= e($icon['value']) ?>" alt="<?= e($item['label']) ?>">
                                    <?php elseif ($icon['value'] !== ''): ?>
                                    <span><?= e($icon['value']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <span><?= e($item['label']) ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="side-panel">
                        <div class="device-panel">
                            <div class="device-copy left">极速稳定</div>
                            <div class="device-copy right">指尖畅享</div>
                            <div class="device-word">MOBILE</div>
                            <div class="device-phone">
                                <div class="device-screen">
                                    <div class="device-topbar"><?= e($brand) ?></div>
                                    <div class="device-banner"></div>
                                    <div class="device-grid">
                                        <div class="device-app"></div>
                                        <div class="device-app"></div>
                                        <div class="device-app"></div>
                                        <div class="device-app"></div>
                                        <div class="device-app"></div>
                                        <div class="device-app"></div>
                                        <div class="device-app"></div>
                                        <div class="device-app"></div>
                                        <div class="device-app"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if ($desc !== ''): ?>
                        <p class="panel-desc"><?= e($desc) ?></p>
                        <?php endif; ?>

                        <div class="actions">
                            <?php foreach ($action_cards as $item): ?>
                            <button class="action-card" type="button" data-mode="<?= e($item['key']) ?>">
                                <div class="action-icon">
                                    <?php if ($item['key'] === 'official'): ?>
                                    <svg viewBox="0 0 24 24"><path d="M3 11.5 12 4l9 7.5"/><path d="M6.5 10.5V20h11V10.5"/></svg>
                                    <?php elseif ($item['key'] === 'download'): ?>
                                    <svg viewBox="0 0 24 24"><path d="M12 3v11"/><path d="m8.5 10.5 3.5 3.5 3.5-3.5"/><path d="M5 18h14"/></svg>
                                    <?php elseif ($item['key'] === 'backup'): ?>
                                    <svg viewBox="0 0 24 24"><path d="M7 7h10v10H7z"/><path d="M4 12h3"/><path d="M17 12h3"/></svg>
                                    <?php else: ?>
                                    <svg viewBox="0 0 24 24"><path d="M21 11.5a8.5 8.5 0 1 1-3.1-6.6"/><path d="M22 4 12.5 13.5"/></svg>
                                    <?php endif; ?>
                                </div>
                                <div class="action-copy">
                                    <div class="action-title"><?= e($item['title']) ?></div>
                                    <div class="action-sub"><?= e($item['sub']) ?></div>
                                </div>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="copyright">COPYRIGHT © <?= e($brand) ?> 版权所有</div>
            </div>
        </section>
    </main>

    <div class="overlay" id="bgOverlay"></div>
    <div class="drawer" id="bgDrawer">
        <div class="drawer-handle"></div>
        <div class="drawer-head">
            <span id="bgDrawerTitle">官方入口</span>
            <button class="drawer-close" id="bgDrawerClose" type="button">×</button>
        </div>
        <div class="drawer-list" id="bgDrawerList"></div>
    </div>

    <script>
        (function () {
            var payload = <?= json_encode($drawer_links, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
            var titles = <?= json_encode($action_titles, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
            var labels = <?= json_encode($action_button_labels, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
            var overlay = document.getElementById('bgOverlay');
            var drawer = document.getElementById('bgDrawer');
            var drawerTitle = document.getElementById('bgDrawerTitle');
            var drawerList = document.getElementById('bgDrawerList');
            var closeBtn = document.getElementById('bgDrawerClose');
            var actionCards = document.querySelectorAll('.action-card');

            function escapeHtml(value) {
                return String(value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function hostOf(url) {
                try {
                    return new URL(url).host;
                } catch (e) {
                    return url;
                }
            }

            function openDrawer(mode) {
                var buttonLabel = labels[mode] || '立即进入';
                var modeLinks = (payload.groups && payload.groups[mode] && payload.groups[mode].length)
                    ? payload.groups[mode]
                    : (payload.all || []);

                drawerTitle.textContent = titles[mode] || '线路列表';
                if (!modeLinks.length) {
                    drawerList.innerHTML = '<div class="drawer-item"><div><div class="drawer-name">暂无可用入口</div><div class="drawer-host">请在后台为该分组添加线路</div></div></div>';
                } else {
                    drawerList.innerHTML = modeLinks.map(function (item) {
                        var host = hostOf(item.url || '');
                        return '' +
                            '<div class="drawer-item">' +
                                '<div>' +
                                    '<div class="drawer-name">' + escapeHtml(item.name || host) + '</div>' +
                                    '<div class="drawer-host">' + escapeHtml(host) + '</div>' +
                                '</div>' +
                                '<a class="drawer-btn" href="' + encodeURI(item.url || '#') + '" target="_blank" rel="noopener noreferrer">' + escapeHtml(buttonLabel) + '</a>' +
                            '</div>';
                    }).join('');
                }

                overlay.classList.add('show');
                drawer.classList.add('show');
                actionCards.forEach(function (card) {
                    card.classList.toggle('active', card.getAttribute('data-mode') === mode);
                });
            }

            function closeDrawer() {
                overlay.classList.remove('show');
                drawer.classList.remove('show');
                actionCards.forEach(function (card) { card.classList.remove('active'); });
            }

            actionCards.forEach(function (card) {
                card.addEventListener('click', function () {
                    openDrawer(card.getAttribute('data-mode') || 'official');
                });
            });

            overlay.addEventListener('click', closeDrawer);
            closeBtn.addEventListener('click', closeDrawer);
        })();
    </script>
</body>
</html>
