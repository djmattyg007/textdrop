<?php
if (php_sapi_name() !== "cli") {
	echo "No public access allowed.\n";
}
if (!isset($argv) || empty($argv) || count($argv) > 2 || !isset($argv[1]) || !is_numeric($argv[1])) {
	echo "Improper parameters supplied.\n";
}
$id = $argv[1];

require("../config.php");
require("../connection.php");

try {
	if (!PDO::beginTransaction()) {
		echo "Cannot create transaction. Aborting.\n";
		die;
	}
} catch (PDOExcetion $e) {
	echo "PDO Exception.\n";
	echo $e->getMessage() . "\n";
	die;
}

try {
	if (!file_exists($id . ".sql")) {
		echo "Cannot find specified build script.\n";
		die;
	}
	$BUILD_STMT = file_get_contents($id . ".sql");
} catch (Exception $e) {
	echo "Unable to open specified build script.\n";
	die;
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
