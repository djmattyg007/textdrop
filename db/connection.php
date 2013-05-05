<?php

if (!isset($CONFIG) || empty($CONFIG) || !isset($CONFIG["DB_HOST"]) || !isset($CONFIG["DB_NAME"]) || !isset($CONFIG["DB_USER"]) || !isset($CONFIG["DB_PASS"])) {
	respond(503, false, "Unable to connect to database.");
}

try {
	$db = new PDO('mysql:host=' . $CONFIG["DB_HOST"] . ';dbname=' . $CONFIG["DB_NAME"], $CONFIG["DB_USER"], $CONFIG["DB_PASS"]);
} catch (PDOException $e) {
	respond(503, false, "Unable to connect to database.");
}

