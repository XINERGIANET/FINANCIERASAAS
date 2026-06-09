<?php
// Script to generate application icons from assets/images/logocrececonmigo.jpeg

$sourcePath = 'assets/images/logocrececonmigo.jpeg';
$outputDir = 'public/assets/images/';

if (!file_exists($sourcePath)) {
    die("Error: Source image not found at $sourcePath\n");
}

if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

// Load source image
$sourceImg = imagecreatefromjpeg($sourcePath);
if (!$sourceImg) {
    die("Error: Failed to load source image using GD.\n");
}

$width = imagesx($sourceImg);
$height = imagesy($sourceImg);
echo "Source image dimensions: {$width}x{$height}\n";

// Crop to square
$size = min($width, $height);
$srcX = ($width - $size) / 2;
$srcY = ($height - $size) / 2;

$squareImg = imagecreatetruecolor($size, $size);
// Retain alpha/colors
imagealphablending($squareImg, false);
imagesavealpha($squareImg, true);

imagecopyresampled($squareImg, $sourceImg, 0, 0, $srcX, $srcY, $size, $size, $size, $size);
echo "Cropped source image to square size {$size}x{$size}\n";

// Function to resize and save as PNG
function resizeAndSave($src, $targetWidth, $targetHeight, $outputPath) {
    $dst = imagecreatetruecolor($targetWidth, $targetHeight);
    
    // Set transparency support for target PNG
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $targetWidth, $targetHeight, imagesx($src), imagesy($src));
    
    if (imagepng($dst, $outputPath)) {
        echo "Generated: $outputPath ({$targetWidth}x{$targetHeight})\n";
    } else {
        echo "Error generating: $outputPath\n";
    }
    imagedestroy($dst);
}

// Target sizes and files
$targets = [
    ['w' => 16, 'h' => 16, 'path' => $outputDir . 'favicon-16x16.png'],
    ['w' => 32, 'h' => 32, 'path' => $outputDir . 'favicon-32x32.png'],
    ['w' => 180, 'h' => 180, 'path' => $outputDir . 'apple-touch-icon.png'],
    ['w' => 192, 'h' => 192, 'path' => $outputDir . 'android-chrome-192x192.png'],
    ['w' => 512, 'h' => 512, 'path' => $outputDir . 'android-chrome-512x512.png'],
];

foreach ($targets as $target) {
    resizeAndSave($squareImg, $target['w'], $target['h'], $target['path']);
}

// Generate favicon.ico (as PNG format, which modern browsers support, placed in public root and public/assets/images)
resizeAndSave($squareImg, 32, 32, 'public/favicon.ico');
copy('public/favicon.ico', $outputDir . 'favicon.ico');
echo "Generated public/favicon.ico and $outputDir" . "favicon.ico\n";

// Cleanup
imagedestroy($sourceImg);
imagedestroy($squareImg);
echo "Icon generation complete!\n";
