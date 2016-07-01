<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_GET['action']) || !is_string($_GET['action'])) {
	header('HTTP/1.0 403 Forbidden');
	header('Location: .');
	exit;
}
function ajaxReturn($ret, $msg, $data = null) {
	header('Content-type: application/json');
	global $mysqli;
	if (isset($mysqli))
		$mysqli->close();
	$ret_arr = array(
		'ret' => $ret,
		'msg' => $msg,
	);
	if (!is_null($data)) {
		$ret_arr['data'] = $data;
	}
	exit(json_encode($ret_arr));
}
require 'config.php';
require 'UnipusKeepOnline.php';
function &connect_db() {
	static $mysqli = null;
	global $cfg;
	if (is_null($mysqli)) {
		$mysqli = new mysqli($cfg['DB_SERVER'], $cfg['DB_USER'], $cfg['DB_PWD'], $cfg['DB_NAME']);
		$mysqli->query("SET NAMES 'utf8'");
	}
	return $mysqli;
}
session_name($cfg['SESSION_NAME']);	
session_start();
switch ($_GET['action']) {
case 'login':
	if (isset($_SESSION['id']))
		ajaxReturn(-1, 'You have already logged in.');
	if (!isset($_POST['captcha']))
		ajaxReturn(-2, 'Captcha needed.');
	if (!is_string($_POST['captcha']))
		ajaxReturn(0, 'You aren\'t a good person, yes?');
	if (!isset($_SESSION['captcha']) || strtolower($_SESSION['captcha']) !== strtolower($_POST['captcha'])) {
		unset($_SESSION['captcha']);
		ajaxReturn(-3, 'Wrong captcha.');
	}
	unset($_SESSION['captcha']);
	if (!isset($_POST['username']) || !isset($_POST['password'])) 
		ajaxReturn(-4, 'You cannot leave username or password empty.');
	if (!is_string($_POST['username']) || !is_string($_POST['password']))
		ajaxReturn(0, 'Hey guys! What are you fucking doing?');
	$mysqli = connect_db();
	$stmt = $mysqli->prepare("SELECT `id`, `token`, `password`, `url` FROM `{$cfg['TABLE_USER']}` WHERE `state` = '1' AND `username` = ? LIMIT 1");
	$stmt->bind_param('s', $_POST['username']);
	if (!$stmt->execute()) {
		$stmt->close();
		ajaxReturn(0, 'There is something wrong with the server.');
	}
	$result = $stmt->get_result();
	if ($row = $result->fetch_assoc()) {
		if ($_POST['password'] !== $row['password']) {
			$_SESSION = array();
			ajaxReturn(-5, 'Wrong username or password.');
		}
		$uko = new UnipusKeepOnline($cfg['BASE_URL'], $cfg['RUNTIME_PATH'] . $row['token'], $_POST['username'], $_POST['password']);
		if (!$uko->info()) {
			if (!$mysqli->query("UPDATE {$cfg['TABLE_USER']} SET `state` = '0' WHERE `id` = '{$row['id']}'"))
				ajaxReturn(0, 'There is something wrong with the server.');
			$_SESSION = array();
			ajaxReturn(-5, 'Wrong username or password.');
		}
		$_SESSION['id'] = $row['id'];
		$_SESSION['token'] = $row['token'];
		$_SESSION['url'] = $row['url'];
		$result->free();
		$stmt->close();
	} else {
		$result->free();
		$stmt->close();
		$token = '';
		for ($i = 0; $i < 32; ++$i)
			$token .= dechex(mt_rand(0, 15));
		$uko = new UnipusKeepOnline($cfg['BASE_URL'], $cfg['RUNTIME_PATH'] . $token, $_POST['username'], $_POST['password']);
		if (!$uko->info()) {
			$_SESSION = array();
			ajaxReturn(-5, 'Wrong username or password.');
		}
		$url = '/login/nsindex_student.php';
		$uko->keep($url);
		$stmt = $mysqli->prepare("INSERT INTO {$cfg['TABLE_USER']} (`token`, `username`, `password`, `name`, `url`) VALUES (?, ?, ?, ?, ?)");
		$stmt->bind_param('sssss', $token, $_POST['username'], $_POST['password'], $uko->name, $url);
		if (!$stmt->execute()) {
			$stmt->close();
			ajaxReturn(0, 'There is something wrong with the server.' . $stmt->error);
		}
		$_SESSION['id'] = $stmt->insert_id;
		$_SESSION['token'] = $token;
		$_SESSION['url'] = $url;
		$stmt->close();
	}
	$_SESSION['username'] = $_POST['username'];
	$_SESSION['password'] = $_POST['password'];
	ajaxReturn(1, 'Login ok.', array(
		'username' => $_SESSION['username'],
		'name'     => $uko->name,
		'time'     => $uko->time,
		'book'     => $uko->book,
		'url'      => $_SESSION['url'],
	));
	break;
case 'info':
	if (!isset($_SESSION['id']))
		ajaxReturn(-6, 'You need to login first.');
	$uko = new UnipusKeepOnline($cfg['BASE_URL'], $cfg['RUNTIME_PATH'] . $_SESSION['token'], $_SESSION['username'], $_SESSION['password']);
	if (!$uko->info()) {
		$_SESSION = array();
		ajaxReturn(-5, 'Wrong username or password.');
	}
	ajaxReturn(2, 'Get info ok.', array(
		'username' => $_SESSION['username'],
		'name'     => $uko->name,
		'time'     => $uko->time,
		'book'     => $uko->book,
		'url'      => $_SESSION['url'],
	));
	break;
case 'book':
	if (!isset($_SESSION['id']))
		ajaxReturn(-6, 'You need to login first.');
	if (!isset($_POST['url']) || !is_string($_POST['url']))
		ajaxReturn(0, 'Hey guys! What are you fucking doing?');
	$mysqli = connect_db();
	$stmt = $mysqli->prepare("UPDATE {$cfg['TABLE_USER']} SET `url` = ? WHERE `id` = '{$_SESSION['id']}'");
	$stmt->bind_param('s', $_POST['url']);
	if (!$stmt->execute()) {
		$stmt->close();
		ajaxReturn(0, 'There is something wrong with the server.');
	}
	$stmt->close();
	$_SESSION['url'] = $_POST['url'];
	ajaxReturn(3, 'Select book ok.', array(
		'url'      => $_SESSION['url'],
	));
	break;
case 'logout':
	if (!isset($_SESSION['id']))
		ajaxReturn(-6, 'You need to login first.');
	$_SESSION = array();
	ajaxReturn(4, 'Logout ok');
	break;
case 'stop':
	if (!isset($_SESSION['id']))
		ajaxReturn(-6, 'You need to login first.');
	$uko = new UnipusKeepOnline($cfg['BASE_URL'], $cfg['RUNTIME_PATH'] . $_SESSION['token'], $_SESSION['username'], $_SESSION['password']);
	$uko->keep($_SESSION['url']);
	$mysqli = connect_db();
	if (!$mysqli->query("UPDATE {$cfg['TABLE_USER']} SET `state` = '0' WHERE `id` = '{$_SESSION['id']}'"))
		ajaxReturn(0, 'There is something wrong with the server.');
	$_SESSION = array();
	ajaxReturn(5, 'Stop ok.');
	break;
default:
	header('HTTP/1.0 404 Not Found');
	ajaxReturn(-1, 'Excuse me? Are you sure?');
	break;
}
