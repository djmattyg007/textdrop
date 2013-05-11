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

try {
	if (!$db->beginTransaction()) {
		exit("Cannot create database transaction. Aborting.\n");
	}
} catch (PDOException $e) {
	exit("PDO Exception.\n" . $e->getMessage() . "\n";
}

echo "Running build script $id\n";
try {
	$query = $db->query($BUILD_STMT);
} catch (PDOException $e) {
	$db->rollBack();
	exit("Error running build script.\n" . $e->getMessage() . "\n";
}

try {
	if (!$db->commit()) {
		$db->rollBack();
		exit("Error while attempting to commit results of build script.\n");
	}
} catch (PDOException $e) {
	//TODO: examine code & message in exception
	$db->rollBack();
	exit("PDO Exception.\n" . $e->getMessage() . "\n";
}
echo "Finished build script $id\n";

