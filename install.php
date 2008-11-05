<?php

global $db;
global $amp_conf;

$sql = "
	CREATE TABLE IF NOT EXISTS `cidpopup` (
		`type` VARCHAR( 30 ) NOT NULL ,
		`id` VARCHAR( 30 ) NOT NULL ,
		`postagi` INTEGER( 255 ) NOT NULL ,
		PRIMARY KEY ( `type` , `id` )
	)";

$check = $db->query($sql);
if(DB::IsError($check)) {
	die_freepbx("Can not create cidpopup table\n");
}

$autoincrement = (($amp_conf["AMPDBENGINE"] == "sqlite") || ($amp_conf["AMPDBENGINE"] == "sqlite3")) ? "AUTOINCREMENT":"AUTO_INCREMENT";
$sql = "CREATE TABLE IF NOT EXISTS `cidpopup_instance` (
	`cidpopup_id` INTEGER NOT NULL PRIMARY KEY $autoincrement,
	`description` VARCHAR( 50 ) ,
	`ipaddr` VARCHAR( 80 ) ,
	`popup_script` VARCHAR( 255 )
)";

$check = $db->query($sql);
if(DB::IsError($check)) {
	die_freepbx("Can not create cidpopup_instance table\n");
}

?>
