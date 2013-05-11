<?php
if (!defined(MODE)) {
	exit("No direct script access allowed.");
}

if (empty($CONFIG) || empty($CONFIG["DB_HOST"]) || empty($CONFIG["DB_NAME"]) || empty($CONFIG["DB_USER"]) || empty($CONFIG["DB_PASS"])) {
	respond(503, false, "Unable to connect to database.");
}

try {
	$db = new PDO('mysql:host=' . $CONFIG["DB_HOST"] . ';dbname=' . $CONFIG["DB_NAME"], $CONFIG["DB_USER"], $CONFIG["DB_PASS"]);
} catch (PDOException $e) {
	respond(503, false, "Unable to connect to database.");
}

function verifySession()
{
	try {
		$statement = $db->prepare("SELECT created, key, expiry FROM sessions WHERE token = ?");
		$statement->bindParam(1, $_POST["token"], PDO::PARAM_STR);
		$statement->execute();
		$session = $statement->fetch(PDO::FETCH_BOTH);
		unset($statement);
	} catch (PDOException $e) {
		//TODO: examine code & message in exception
		respond(500, false, "Unidentified database error.");
	}

	if (empty($session)) {
		//TODO: check error code
		respond(400, false, "Session does not exist.");
	}
	if ($session["key"] !== $_SERVER["HTTP_X_API_KEY"]) {
		respond(400, false, "Session token and API key do not match.");
	}
	if (strtotime($_POST["createdAt"]) > strtotime($session["expiry"])) {
		cleanSessions();
		//TODO: check error code
		respond(401, false, "Session has expired.");
	}

	try {
		$statement = $db->prepare("UPDATE sessions SET expiry = ? WHERE token = ?");
		$statement->bindParam(1, $_POST["createdAt"], PDO::PARAM_STR);
		$statement->bindParam(2, $_POST["token"], PDO::PARAM_STR);
		$statement->execute();
	} catch (PDOException $e) {
		respond(500, false, "Unidentified database error.");
	}
	return true;
	respond(401, false, "Unable to verify session token.");
}

