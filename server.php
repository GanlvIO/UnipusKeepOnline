<?php
if (php_sapi_name() !== 'cli') {
	header('Location: .');
	exit;
}
require 'config.php';
require 'UnipusKeepOnline.php';
function log_record($level, $tag, $msg) {
	echo date('Y-m-d H:i:s'), ' | ', $level, ' | ', $tag, ' : ', $msg, "\n";
}
log_record('INFO', 'general', '=============== START ===============');
$mysqli = new mysqli($cfg['DB_SERVER'], $cfg['DB_USER'], $cfg['DB_PWD'], $cfg['DB_NAME']);
$mysqli->query("SET NAMES 'utf8'");
$error_times = 0;
for (;;) {
	$timestamp = time();
	if (!$result = $mysqli->query("SELECT `id`, `token`, `username`, `password`, `url` FROM {$cfg['TABLE_USER']} WHERE `state` = '1'")) {
		log_record('ERROR', 'general', 'get user data error');
		++$error_times;
		if ($error_times > $cfg['MAX_ERROR_TIMES'])
			exit;
		continue;
	}
	log_record('INFO', 'general', 'num_rows = ' . $result->num_rows);
	$users = $result->fetch_all(MYSQLI_ASSOC);
	$error_times = 0;
	foreach ($users as &$user) {
		$uko = new UnipusKeepOnline($cfg['BASE_URL'], $cfg['RUNTIME_PATH'] . $user['token'], $user['username'], $user['password']);
		if (!$uko->keep($user['url'])) {
			log_record('WARNING', $user['id'], 'login failed');
			if (!$mysqli->query("UPDATE {$cfg['TABLE_USER']} SET `state` = '-1' WHERE `id` = '{$user['id']}'")) {
				log_record('ERROR', $user['id'], 'update wrong password state failed');
			}
			log_record('ERROR', $user['id'], 'stop');
		}
		log_record('INFO', $user['id'], 'keep online ok');
	}
	while (time() - $timestamp < $cfg['INTERVAL']) {
		sleep(10);
	}
}
