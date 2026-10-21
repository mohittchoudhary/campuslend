<?php
// Generate clean PWA icons using GD
$targetDir = __DIR__ . '/../public/assets/img';
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

function makePwaIcon($size, $path) {
    $img = imagecreatetruecolor($size, $size);
    // Anti-alias background
    $bg = imagecolorallocate($img, 79, 70, 229); // #4f46e5
    imagefill($img, 0, 0, $bg);
    
    // Inner circle
    $inner = imagecolorallocate($img, 99, 102, 241);
    imagefilledellipse($img, (int)($size/2), (int)($size/2), (int)($size*0.75), (int)($size*0.75), $inner);

    $white = imagecolorallocate($img, 255, 255, 255);
    $text = "CL";
    // Scale text
    $font = 5;
    $fw = imagefontwidth($font) * strlen($text);
    $fh = imagefontheight($font);
    
    // Scale up rendering on a mini buffer or draw simple shapes
    $x = (int)(($size - $fw) / 2);
    $y = (int)(($size - $fh) / 2);
    imagestring($img, $font, $x, $y, $text, $white);

    imagepng($img, $path);
    imagedestroy($img);
}

makePwaIcon(192, $targetDir . '/icon-192.png');
makePwaIcon(512, $targetDir . '/icon-512.png');
echo "Icons generated successfully!\n";
