<?php
/**
 * img.php — 随机图片接口
 * 优先从后台资源管理「随机图库」（uploads/random/）读取
 * 若数据库中无记录则回退到文件系统扫描
 *
 * 调用方式：
 *   <img src="/img.php">
 *   background-image: url('/img.php')
 *   直接访问 /img.php  → 输出随机图片二进制流
 *
 * 可选参数：
 *   ?format=json  → 返回 JSON：{"url":"..."}
 *   ?format=url   → 纯文本返回图片URL
 */

$format = strtolower(trim($_GET['format'] ?? ''));

// ── 尝试从数据库随机图库取图 ──────────────────────────────
$img_url  = '';
$img_path = '';

try {
    require_once __DIR__ . '/config/config.php';
    $stmt = get_db()->prepare(
        "SELECT `url` FROM `media` WHERE `lib`='random' ORDER BY RAND() LIMIT 1"
    );
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && !empty($row['url'])) {
        $img_url  = $row['url'];
        $img_path = rtrim($_SERVER['DOCUMENT_ROOT'] ?? __DIR__, '/\\') . '/' . ltrim($img_url, '/');
        // 文件不存在则清空，走回退
        if (!file_exists($img_path)) {
            $img_url = ''; $img_path = '';
        }
    }
} catch (Throwable $e) {
    // 数据库不可用，走回退
}

// ── 回退：扫描 uploads/random/ 目录 ──────────────────────
if (empty($img_path)) {
    $scan_dir    = __DIR__ . '/uploads/random/';
    $allowed_ext = ['jpg','jpeg','png','gif','webp'];
    $files       = [];

    if (is_dir($scan_dir)) {
        foreach (array_diff(scandir($scan_dir), ['.','..']) as $f) {
            $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
            if (in_array($ext, $allowed_ext)) {
                $files[] = $f;
            }
        }
    }

    if (!empty($files)) {
        $pick     = $files[array_rand($files)];
        $img_path = $scan_dir . $pick;
        $img_url  = '/uploads/random/' . $pick;
    }
}

// ── 无图可用 ─────────────────────────────────────────────
if (empty($img_path) || !file_exists($img_path)) {
    if ($format === 'json') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['url' => '', 'error' => 'no image available']);
    } elseif ($format === 'url') {
        header('Content-Type: text/plain; charset=utf-8');
        echo '';
    } else {
        http_response_code(404);
        header('Content-Type: text/plain');
        echo '404 No Image';
    }
    exit;
}

// ── 输出 ─────────────────────────────────────────────────
if ($format === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    // 构建完整 URL
    $scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? '';
    $full_url = $host ? $scheme . '://' . $host . $img_url : $img_url;
    echo json_encode(['url' => $full_url]);
    exit;
}

if ($format === 'url') {
    header('Content-Type: text/plain; charset=utf-8');
    $scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? '';
    echo $host ? $scheme . '://' . $host . $img_url : $img_url;
    exit;
}

// 默认：直接输出图片二进制流
$mime = @mime_content_type($img_path) ?: 'image/jpeg';
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($img_path));
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
readfile($img_path);
exit;
