<?php
if (!defined("MODE")) {
	exit("No direct script access allowed.");
}

if (empty($CONFIG) || empty($CONFIG["DB_HOST"]) || empty($CONFIG["DB_NAME"]) || empty($CONFIG["DB_USER"]) || empty($CONFIG["DB_PASS"])) {
	respond(503, false, translate("Unable to connect to database."));
}

try {
	$db = new PDO('mysql:host=' . $CONFIG["DB_HOST"] . ';dbname=' . $CONFIG["DB_NAME"], $CONFIG["DB_USER"], $CONFIG["DB_PASS"]);
} catch (PDOException $e) {
	respond(503, false, translate("Unable to create database connection."));
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
		logEntry("ERROR", "now", 500, "verifySession()", $e);
		respond(500, false, translate("Unidentified database error."));
	}

	if (empty($session)) {
		respond(401, false, translate("Session does not exist."));
	}
	if ($session["key"] !== $_SERVER["HTTP_X_API_KEY"]) {
		respond(401, false, translate("Session token and API key do not match."));
	}
	if (strtotime($_POST["createdAt"]) > strtotime($session["expiry"])) {
		cleanSessions();
		respond(401, false, translate("Session has expired. Please reauthenticate."));
	}

	try {
		if (!$db->beginTransaction()) {
			respond(503, false, translate("Unable to verify session token."));
		}
	} catch (PDOException $e) {
		logEntry("ERROR", "now", 500, "verifySession()", $e);
		respond(500, false, translate("Unidentified database error."));
	}

	try {
		$statement = $db->prepare("UPDATE sessions SET expiry = ?, totalRequests = totalRequests + 1 WHERE token = ?");
		$statement->bindParam(1, $_POST["createdAt"], PDO::PARAM_STR);
		$statement->bindParam(2, $_POST["token"], PDO::PARAM_STR);
		$statement->execute();
		unset($statement);
	} catch (PDOException $e) {
		respond(500, false, translate("Unidentified database error."));
	}

	try {
		if (!$db->commit()) {
			$db->rollBack();
			respond(503, false, translate("Unable to verify session token."));
		}
	} catch (PDOException $e) {
		$db->rollBack();
		logEntry("ERROR", "now", 500, "verifySession()", $e);
		respond(500, false, translate("Unidentified database error."));
	}
	return true;
	respond(401, false, translate("Unable to verify session token."));
}

function cleanSessions()
{
	try {
		if (!$db->beginTransaction()) {
			respond(503, false, translate("Unidentified database error."));
		}
	} catch (PDOException $e) {
		logEntry("ERROR", "now", 500, "cleanSessions()", $e);
		respond(500, false, translate("Unidentified database error."));
	}

	try {
		$statement = $db->prepare("DELETE FROM sessions WHERE expiry < UTC_TIMESTAMP()");
		$statement->execute();
		unset($statement);
	} catch (PDOException $e) {
		logEntry("ERROR", "now", 500, "cleanSessions()", $e);
		respond(500, false, translate("Unidentified database error."));
	}

	try {
		if (!$db->commit()) {
			$db->rollBack();
			respond(503, false, translate("Unidentified database error."));
		}
	} catch (PDOException $e) {
		$db->rollBack();
		logEntry("ERROR", "now", 500, "cleanSessions()", $e);
		respond(500, false, translate("Unidentified database error."));
	}
}

// Assumes $msg has already been run through translation.
function createTransaction($msg, $call)
{
	try {
		if (!$db->beginTransaction()) {
			if (MODE == "CLI") {
				exit("Cannot create database transaction. Aborting.\n");
			} else {
				respond(503, false, (($msg == "" || $msg === "default") ? translate("Unidentified database error.") : $msg));
			}
		}
	} catch (PDOException $e) {
		if (MODE == "CLI") {
			$msg = processPDOException($e);
			exit($msg);
		} else {
			logEntry("ERROR", "now", 500, $call, $e);
			respond(500, false, translate("Unidentified database error."));
		}
	}
	return true;
}

// Assumes $msg has already been run through translation.
function finishTransaction($msg, $call)
{
	try {
		if (!$db->commit()) {
			$db->rollBack();
			respond(503, false, (($msg == "" || $msg === "default") ? translate("Unidentified database error.") : $msg));
		}
	} catch (PDOException $e) {
		$db->rollBack();
		logEntry("ERROR", "now", 500, $call, $e);
		respond(500, false, translate("Unidentified database error."));
	}
	return true;
}

