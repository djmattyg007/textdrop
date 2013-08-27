<?php
if (!defined("MODE")) {
	exit("No direct script access allowed.");
}

//TODO: implement cron lock
require(".." . DIRECTORY_SEPARATOR . "config.php");
require(".." . DIRECTORY_SEPARATOR . "db" . DIRECTORY_SEPARATOR . "connection.php");

try {
	$statement = $db->prepare("SELECT * FROM `tasks` WHERE `status` = 0 ORDER BY `taskID` LIMIT ?");
	$statement->bindParam(1, $GLOBALS["CONFIG"]["CRON"]["MAX_TASKS"], PDO::PARAM_INT);
	$statement->execute();
	$tasks = $statement->fetchAll(PDO::FETCH_ASSOC);
	unset($statement);
} catch (PDOException $e) {
	$msg = processPDOException($e);
	exit($msg);
}

