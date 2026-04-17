<?php
require_once __DIR__ . '/config/config.php';

function sync_lib($lib) {
    echo "正在同步图库: $lib ...<br>";
    $db = get_db();
    $dir = ROOT_PATH . '/uploads/' . $lib;
    if (!is_dir($dir)) {
        echo "目录不存在: $dir<br>";
        return;
    }
    
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        // 简单过滤，确保是图片文件
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) continue;

        $url = '/uploads/' . $lib . '/' . $file;
        
        $stmt = $db->prepare("SELECT id FROM media WHERE url=?");
        $stmt->execute([$url]);
        if (!$stmt->fetch()) {
            $db->prepare("INSERT INTO media (filename, url, lib, owner_id, created_at) VALUES (?, ?, ?, ?, NOW())")
               ->execute([$file, $url, $lib, 1]); // 默认为管理员(ID 1)
            echo "已同步: $file<br>";
        }
    }
    echo "图库 $lib 同步完成<br><br>";
}

sync_lib('gallery');
sync_lib('random');
echo "所有同步任务完成。";
