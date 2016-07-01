<?php
require 'config.php';

session_name($cfg['SESSION_NAME']);
session_start();

$chars_count = mb_strlen($cfg['CAPTCHA_CHARS']);
$captcha = array();
for ($i = 0; $i < $cfg['CAPTCHA_LEN']; ++$i)
	$captcha[] = mb_substr($cfg['CAPTCHA_CHARS'], mt_rand(0, $chars_count - 1), 1);
$_SESSION['captcha'] = implode('', $captcha);

// draw normal captcha
$h = $cfg['CAPTCHA_HEIGHT'];
$size = 0.5 * $cfg['CAPTCHA_HEIGHT'];
$w = $h * $cfg['CAPTCHA_LEN'];
$im_tmp = imagecreatetruecolor($w, $h);
$bgcolor = imagecolorallocate($im_tmp, 255, 255, 255);
imagefill($im_tmp, 0, 0, $bgcolor);
$x = 0;
$color = imagecolorallocate($im_tmp, 0, 0, 0);
foreach ($captcha as $char) {
	$y = mt_rand(0.5 * $h, 0.8 * $h);
	imagettftext($im_tmp, $size, 0, $x - 2, $y, $color, $cfg['CAPTCHA_FONT'], $char);
	$x = $w;
	while ($x > 0) {
		--$x;
		for ($j = 0; $j < $h; ++$j) {
			if (imagecolorat($im_tmp, $x, $j) === 0)
				break 2;
		}
	}
}
$w = $x;

// prepare for distrotion
$nx = 1 + (int)($cfg['CAPTCHA_WIDTH'] / $cfg['CAPTCHA_DISTROTION']);
$ny = 1 + (int)($cfg['CAPTCHA_HEIGHT'] / $cfg['CAPTCHA_DISTROTION']);
$point_dx = $cfg['CAPTCHA_WIDTH'] / (2 * $nx + 1);
$point_dy = $cfg['CAPTCHA_HEIGHT'] / (2 * $ny + 1);
$block_w = $w / $nx;
$block_h = $h / $ny;
$points = array();
for ($i = 0; $i <= $nx; ++$i) {
	$points[$i] = array();
	for ($j = 0; $j <= $ny; ++$j) {
		$points[$i][$j] = array(
			'x' => mt_rand(2 * $i * $point_dx, (2 * $i + 1) * $point_dx),
			'y' => mt_rand(2 * $j * $point_dy, (2 * $j + 1) * $point_dy),
		);
	}
}

// draw distrotion captcha
$im = imagecreatetruecolor($cfg['CAPTCHA_WIDTH'], $cfg['CAPTCHA_HEIGHT']);
imagefill($im, 0, 0, $bgcolor);
for ($i = 0; $i < $w; ++$i) { 
	for ($j = 0; $j < $h; ++$j) { 
		$rgb = imagecolorat($im_tmp, $i, $j);
		if ($rgb === 0) {
			$i2 = (int)($i / $block_w);
			$j2 = (int)($j / $block_h);
			$i1 = $i / $block_w - $i2;
			$j1 = $j / $block_h - $j2;
			$x = (1 - $i1) * (1 - $j1) * $points[$i2][$j2]['x'] + $i1 * (1 - $j1) * $points[$i2 + 1][$j2]['x'] + $j1 * (1 - $i1) * $points[$i2][$j2 + 1]['x'] + $i1 * $j1 * $points[$i2 + 1][$j2 + 1]['x'];
			$y = (1 - $i1) * (1 - $j1) * $points[$i2][$j2]['y'] + $i1 * (1 - $j1) * $points[$i2 + 1][$j2]['y'] + $j1 * (1 - $i1) * $points[$i2][$j2 + 1]['y'] + $i1 * $j1 * $points[$i2 + 1][$j2 + 1]['y'];
			imagesetpixel($im, $x, $y, $rgb); 
		}
	} 
}

// output
header("Content-type: image/gif");
imagegif($im);
imagedestroy($im_tmp);
imagedestroy($im);
