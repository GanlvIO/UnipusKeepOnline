<?php
if (php_sapi_name() !== 'cli') {
	header('Location: .');
	exit;
}
require 'config.php';
$mysqli = new mysqli($cfg['DB_SERVER'], $cfg['DB_USER'], $cfg['DB_PWD']);
$mysqli->query("SET NAMES 'utf8'");
$mysqli->query("CREATE DATABASE IF NOT EXISTS `{$cfg['DB_NAME']}` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci");
$mysqli->query("CREATE TABLE IF NOT EXISTS `{$cfg['DB_NAME']}`.`{$cfg['TABLE_USER']}` (`id` INT NOT NULL AUTO_INCREMENT, `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, `state` TINYINT NOT NULL DEFAULT '1', `token` CHAR(32) NOT NULL, `username` VARCHAR(11) NOT NULL, `password` VARCHAR(32) NOT NULL, `name` VARCHAR(30) NOT NULL, `url` VARCHAR(50) NOT NULL, PRIMARY KEY (`id`)) ENGINE = InnoDB CHARSET=utf8 COLLATE utf8_general_ci");
$mysqli->close();