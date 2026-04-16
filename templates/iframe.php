<?php
/**
 * iframe嵌入模板
 * @label 内嵌框架跳转
 * @fields url,title,desc
 * @copyright 2026 wenyinos <ruojiner@hotmail.com>
 * @license MIT License
 */
require_once __DIR__ . '/_helpers.php';

$target_href = template_href($target_url ?? '', '');
if ($target_href === '') {
    error_log('Iframe redirect error: target_url is empty');
    http_response_code(400);
    exit('Invalid redirect');
}

$target_host = parse_url($target_href, PHP_URL_HOST) ?: '目标页面';
$page_title = template_value($site_title ?? '', $target_host);
$page_description = template_value($site_description ?? '', '若页面无法正常显示，请使用右上角按钮在新窗口打开。');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?></title>
    <meta name="description" content="<?= e($page_description) ?>"/>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; }
        body {
            display: flex;
            flex-direction: column;
            font-family: "Segoe UI", "PingFang SC", "Microsoft YaHei", sans-serif;
            background: #091019;
            color: #fff;
        }
        .toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 14px 18px;
            background: rgba(8, 15, 24, .92);
            border-bottom: 1px solid rgba(255,255,255,.08);
        }
        .title {
            min-width: 0;
        }
        .title h1 {
            font-size: 15px;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .title p {
            font-size: 12px;
            color: rgba(255,255,255,.7);
        }
        .actions {
            display: flex;
            gap: 10px;
            flex-shrink: 0;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 14px;
            border-radius: 999px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
        }
        .btn-open {
            background: linear-gradient(135deg, #4b74ff, #14b87a);
            color: #fff;
        }
        .btn-direct {
            color: rgba(255,255,255,.85);
            border: 1px solid rgba(255,255,255,.15);
        }
        iframe {
            width: 100%;
            height: 100%;
            border: 0;
            flex: 1;
            background: #fff;
        }
        @media (max-width: 680px) {
            .toolbar {
                flex-direction: column;
                align-items: stretch;
            }
            .actions {
                width: 100%;
            }
            .btn {
                flex: 1;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div class="title">
            <h1><?= e($page_title) ?></h1>
            <p><?= e($page_description) ?></p>
        </div>
        <div class="actions">
            <a class="btn btn-open" href="<?= e($target_href) ?>" target="_blank" rel="noopener noreferrer">新窗口打开</a>
            <a class="btn btn-direct" href="<?= e($target_href) ?>">直接访问</a>
        </div>
    </div>
    <iframe src="<?= e($target_href) ?>" title="<?= e($page_title) ?>" loading="eager" referrerpolicy="no-referrer-when-downgrade"></iframe>
</body>
</html> 
