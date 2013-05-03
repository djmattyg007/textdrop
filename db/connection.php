<?php
if (!isset($CONFIG) || empty($CONFIG) || !isset($CONFIG["DB_HOST"]) || !isset($CONFIG["DB_NAME"]) || !isset($CONFIG["DB_USER"]) || !isset($CONFIG["DB_PASS"])) {
	echo "Configuration missing.";
	die;
}
try {
	$db = new PDO('mysql:host=' . $CONFIG["DB_HOST"] . ';dbname=' . $CONFIG["DB_NAME"], $CONFIG["DB_USER"], $CONFIG["DB_PASS"]);
} catch (PDOException $e) {
	echo "Error connecting to database.";
	die;
}
