<?php

if (empty($CONFIG) || empty($CONFIG["DB_HOST"]) || empty($CONFIG["DB_NAME"]) || empty($CONFIG["DB_USER"]) || empty($CONFIG["DB_PASS"])) {
	respond(503, false, "Unable to connect to database.");
}

try {
	$db = new PDO('mysql:host=' . $CONFIG["DB_HOST"] . ';dbname=' . $CONFIG["DB_NAME"], $CONFIG["DB_USER"], $CONFIG["DB_PASS"]);
} catch (PDOException $e) {
	respond(503, false, "Unable to connect to database.");
}

function verifySession() {
	//TODO: actually write the code
	return NULL;
	respond(401, false, "Unable to verify session token.");
}
