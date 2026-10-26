<?php

$im = imagecreatetruecolor(32, 32);
imagealphablending($im, false);
imagesavealpha($im, true);

$bg = imagecolorallocatealpha($im, 79, 70, 229, 0); // Indigo #4F46E5
imagefilledrectangle($im, 0, 0, 31, 31, $bg);

$white = imagecolorallocate($im, 255, 255, 255);
// Draw B letter
imagefilledrectangle($im, 7, 5, 11, 26, $white);
imagefilledrectangle($im, 11, 5, 19, 8, $white);
imagefilledrectangle($im, 11, 14, 18, 17, $white);
imagefilledrectangle($im, 11, 23, 20, 26, $white);
imagefilledrectangle($im, 18, 7, 22, 15, $white);
imagefilledrectangle($im, 19, 16, 23, 24, $white);

ob_start();
imagepng($im);
$pngData = ob_get_clean();
imagedestroy($im);

$size = strlen($pngData);
// Standard ICO directory header: 6 bytes header + 16 bytes directory entry
$header = pack('vvvCCCCvvVV', 0, 1, 1, 32, 32, 0, 0, 1, 32, $size, 22);

file_put_contents(__DIR__.'/../public/favicon.ico', $header.$pngData);
echo 'Favicon generated successfully: '.filesize(__DIR__.'/../public/favicon.ico')." bytes\n";
