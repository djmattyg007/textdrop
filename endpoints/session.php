<?php
if (!defined("MODE")) {
	exit("No direct script access allowed.");
}

$methodRegistry["session_login"] = false;
//TODO: record log of transactions
//TODO: create access and error logs
function session_login()
{
	// The client quite obviously can't authenticate without a username or password.
	if (empty($_POST["username"]) || empty($_POST["password"])) {
		respond(401, false, translate("There was no username or password supplied with the request."));
	}
	global $db, $GLOBAL;

	// Check to see if the username and password exist and match.
	try {
		$statement = $db->prepare("SELECT `userID`, `active` FROM `users` WHERE `username` = ? AND `password` = ?");
		$statement->bindParam(1, $_POST["username"], PDO::PARAM_STR);
		$statement->bindParam(2, $_POST["password"], PDO::PARAM_STR);
		$statement->execute();
		$userDetail = $statement->fetch(PDO::FETCH_BOTH);
		unset($statement);
	} catch (PDOException $e) {
		logEntry("ERROR", "now", 500, __FUNCTION__, $e);
		respond(500, false, translate("Unidentified database error."));
	}

	// Make sure the user exists and is active.
	if (isset($userDetail["userID"]) && isset($userDetail["active"]) && intval($userDetail["active"]) === 1) {
		$userID = intval($userDetail["userID"]);
		unset($userDetail);
	} else {
		// Do not inform the client the selected user may be inactive.
		respond(401, false, translate("Incorrect username or password."));
	}

	// Grab the user's API keys from the database.
	try {
		$statement = $db->prepare("SELECT `keyID`, `key`, `active` FROM `api_keys` WHERE `owner` = ?");
		$statement->bindParam(1, $userID, PDO::PARAM_INT);
		$statement->execute();
		$keys = $statement->fetchAll(PDO::FETCH_BOTH);
		unset($statement);
	} catch (PDOException $e) {
		logEntry("ERROR", "now", 500, __FUNCTION__, $e);
		respond(500, false, translate("Unidentified database error."));
	}

	// No keys were found.
	if (count($keys) == 0) {
		// Do not inform the client there are no API keys for the user.
		respond(401, false, translate("Invalid API key."));
	}
	// Cycle through all of the keys returned from the database to find one that matches
	// the supplied key.
	$findKey = array();
	$findKey["key"] = NULL;
	$findKey["all"] = array();
	foreach ($keys as $key) {
		if ($key["key"] === $_SERVER["HTTP_X_API_KEY"]) {
			if (intval($key["active"]) === 1) {
				$findKey["key"] = $key["keyID"];
			} else {
				// Do not inform the client that the API key it supplied is inactive.
				respond(401, false, translate("Invalid API key."));
			}
		}
		$findKey["all"][] = $key["keyID"];
	}
	// No active keys matched the supplied key.
	if (!$findKey["key"]) {
		// No matching API keys were found for that user.
		respond(401, false, translate("Invalid API key."));
	}
	unset($keys);

	// Check to see if the user already has any existing sessions.
	// First, clean up old ones.
	cleanSessions();
	// Check the number of active sessions for the supplied API key.
	if ($GLOBALS["CONFIG"]["SESSION"]["MAX_PER_KEY"]) {
		// Grab the count.
		try {
			$statement = $db->prepare("SELECT COUNT(*) FROM `sessions` WHERE `key` = ?");
			//TODO: shouldn't this be an int?
			$statement->bindParam(1, $findKey["key"], PDO::PARAM_STR);
			$statement->execute();
			$sessionKeyTotal = $statement->fetchColumn();
			unset($statement);
		} catch (PDOException $e) {
			logEntry("ERROR", "now", 500, __FUNCTION__, $e);
			respond(500, false, translate("Unidentified database error."));
		}

		if ($sessionKeyTotal >= $GLOBALS["CONFIG"]["SESSION"]["MAX_PER_KEY"]) {
			// The user has too many sessions with the current API key. Slow them down.
			respond(429, false, translate("You already have at least {s} active session(s) with this API key. Please wait a few minutes for one of your existing sessions to expire before creating a new one.", $GLOBALS["CONFIG"]["SESSION"]["MAX_PER_KEY"]));
		}
	}
	// Check the number of active sessions for all the user's API keys.
	if ($GLOBALS["CONFIG"]["SESSION"]["MAX_PER_USER"]) {
		try {
			$statement = $db->prepare("SELECT COUNT(*) FROM `sessions`, `api_keys` WHERE `sessions`.`key` = `api_keys`.`keyID` AND `api_keys`.`owner` = ?");
			$statement->bindParam(1, $userID, PDO::PARAM_INT);
			$statement->execute();
			$sessionUserTotal = $statement->fetchColumn();
			unset($statement);
		} catch (PDOException $e) {
			logEntry("ERROR", "now", 500, __FUNCTION__, $e);
			respond(500, false, translate("Unidentified database error."));
		}

		if ($sessionUserTotal >= $GLOBALS["CONFIG"]["SESSION"]["MAX_PER_USER"]) {
			// The user has too many sessions with the current API key. Slow them down.
			respond(429, false, translate("You already have at least {s} active session(s). Please wait a few minutes for one of your existing sessions to expire before creating a new one.", $GLOBALS["CONFIG"]["SESSION"]["MAX_PER_USER"]));
		}
	}

	// If we reach this point, the client has (theoretically) successfully authenticated with the system.
	// Therefore, create a session for the user as requested.
	createTransaction(translate("Unable to create new session."), __FUNCTION__);

	$expiryTime = date("Y-m-d H:i:s", time() + ($GLOBALS["CONFIG"]["API"]["SESSION_LEN"] * 60));
	$sessionToken = sha1(md5("$userID" . time() . "{$findKey["key"]}" . rand()));
	try {
		$statement = $db->prepare("INSERT INTO `sessions` (`key`, `expiry`, `token`) VALUES (?, ?, ?)");
		//TODO: shouldn't this be an int?
		$statement->bindParam(1, $findKey["key"], PDO::PARAM_STR);
		$statement->bindParam(2, $expiryTime, PDO::PARAM_STR);
		$statement->bindParam(3, $sessionToken, PDO::PARAM_STR);
		$statement->execute();
		unset($statement);
	} catch (PDOException $e) {
		$db->rollBack();
		logEntry("ERROR", "now", 500, __FUNCTION__, $e);
		respond(500, false, translate("Unidentified database error."));
	}

	try {
		$statement = $db->prepare("UPDATE `users` SET `lastLogin` = UTC_TIMESTAMP() WHERE `userID` = ?");
		$statement->bindParam(1, $userID, PDO::PARAM_INT);
		$statement->execute();
		unset($statement);
	} catch (PDOException $e) {
		$db->rollBack();
		logEntry("ERROR", "now", 500, __FUNCTION__, $e);
		respond(500, false, translate("Unidentified database error."));
	}

	finishTransaction("Unable to create new session.", __FUNCTION__);

	$response = array();
	$response["session"] = array();
	$response["session"]["expiryTime"] = $expiryTime;
	$response["session"]["token"] = $sessionToken;
	$response["session"]["active"] = array();
	$response["session"]["active"]["key"] = ($sessionKeyTotal + 1);
	$response["session"]["active"]["user"] = ($sessionUserTotal + 1);
	respond(200, true, translate("New session created successfully."), $response);
}

$methodRegistry["session_logout"] = true;
function session_logout()
{
	global $db;
	createTransaction("default", __FUNCTION__);

	try {
		$statement = $db->prepare("DELETE FROM `sessions` WHERE `token` = ?");
		$statement->bindParam(1, $_POST["token"], PDO::PARAM_STR);
		$statement->execute();
		unset($statement);
	} catch (PDOException $e) {
		$db->rollBack();
		logEntry("ERROR", "now", 500, __FUNCTION__, $e);
		respond(500, false, translate("Unidentified database error."));
	}

	finishTransaction("default", __FUNCTION__);

	respond(200, true, translate("Session ended successfully."));
}

