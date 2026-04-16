<?php
/**
 * 验证码生成
 * @copyright 2026 wenyinos <ruojiner@hotmail.com>
 * @license MIT License
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// GD 扩展检查
if (!extension_loaded('gd') || !function_exists('imagecreatetruecolor')) {
    // GD 不可用时返回 1x1 透明 PNG 占位
    header('Content-Type: image/png');
    echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
    exit;
}

$width  = 130;
$height = 42;
$length = 4;

$chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
$code  = '';
for ($i = 0; $i < $length; $i++) {
    $code .= $chars[random_int(0, strlen($chars) - 1)];
}
$_SESSION['captcha'] = $code;

$img = imagecreatetruecolor($width, $height);
$bg  = imagecolorallocate($img, 30, 30, 46);
imagefilledrectangle($img, 0, 0, $width, $height, $bg);

// 噪点
for ($i = 0; $i < 80; $i++) {
    $nc = imagecolorallocate($img, random_int(60,130), random_int(60,130), random_int(80,150));
    imagesetpixel($img, random_int(0, $width-1), random_int(0, $height-1), $nc);
}

// 干扰线
for ($i = 0; $i < 3; $i++) {
    $lc = imagecolorallocate($img, random_int(60,110), random_int(60,110), random_int(80,140));
    imageline($img, random_int(0,20), random_int(0,$height-1), random_int($width-20,$width-1), random_int(0,$height-1), $lc);
}

// 字符颜色
$colors = [
    imagecolorallocate($img, 124, 106, 247),
    imagecolorallocate($img,  34, 211, 165),
    imagecolorallocate($img, 247, 169,  74),
    imagecolorallocate($img, 247, 106, 106),
];

$font = 5;
$x    = 10;
for ($i = 0; $i < $length; $i++) {
    $c = $colors[$i % count($colors)];
    imagechar($img, $font, $x + random_int(-2, 2), random_int(8, 16), $code[$i], $c);
    $x += 28;
}

header('Content-Type: image/png');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
imagepng($img);
imagedestroy($img);
