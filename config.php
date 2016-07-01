<?php
$cfg = array(
	'DB_SERVER'       => '127.0.0.1:3306',
	'DB_USER'         => 'test',
	'DB_PWD'          => 'test',
	'DB_NAME'         => 'test',
	'TABLE_USER'      => 'unipuskeeponline_user',

	'SESSION_NAME'    => 'UNIPUSKEEPONLINE',
	'BASE_URL'        => 'http://202.117.216.249:8003/',
	'RUNTIME_PATH'    => __DIR__ . '/runtime/',

	'CAPTCHA_CHARS'   => '23456789ABCDEFGHJKLMNPWRTUVWXYZabcdefghijkmnpqrstuvwxyz',
	'CAPTCHA_LEN'     => 4,
	'CAPTCHA_FONT'    => './OpenSans-Regular.ttf',
	'CAPTCHA_WIDTH'   => 200,
	'CAPTCHA_HEIGHT'  => 100,
	'CAPTCHA_DISTROTION' => 40,

	'MAX_ERROR_TIMES' => 20,
	'INTERVAL'        => 600,
);
