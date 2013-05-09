<?php

if (php_sapi_name() !== "cli") {
	exit("No public access allowed.\n");
}
define("MODE", "CLI");
if (!isset($argv) || empty($argv) || count($argv) > 2 || !isset($argv[1]) || !is_numeric($argv[1])) {
	exit("Improper parameters supplied.\n");
}
$id = $argv[1];

require(".." . DIRECTORY_SEPARATOR . "config.php");
require(".." . DIRECTORY_SEPARATOR . "db" . DIRECTORY_SEPARATOR . "connection.php");

try {
	if (!file_exists($id . ".sql")) {
		exit("Cannot find specified build script.\n");
	}
	$BUILD_STMT = file_get_contents($id . ".sql");
} catch (Exception $e) {
	exit("Unable to open specified build script.\n");
}

die; //TODO: do not perform any database operations until we actually have a way to complete the transaction
try {
	if (!$db->beginTransaction()) {
		exit("Cannot create database transaction. Aborting.\n");
	}
} catch (PDOExcetion $e) {
	exit("PDO Exception.\n" . $e->getMessage() . "\n";
}

echo "Running build script $id\n";
try {
	$query = $db->query($BUILD_STMT);
} catch (PDOException $e) {
	echo "Error running build script.\n";
	echo $e->getMessage();
	die;
}
echo "Finished build script $id\n";

