<?php
if (!defined("MODE")) {
	exit("No direct script access allowed.");
}

$methodRegistry["session_login"] = false;
//TODO: check for number of active sessions per api key
//TODO: record log of transactions
//TODO: create access and error logs
function session_login()
{
	// The client quite obviously can't authenticate without a username or password.
	if (!isset($_POST["username"]) || !isset($_POST["password"])) {
		respond(401, false, "There was no username or password supplied with the request.");
	}

	// Check to see if the username and password exist and match.
	try {
		$statement = $db->prepare("SELECT userID, active FROM users WHERE username = ? AND password = ?");
		$statement->bindParam(1, $_POST["username"], PDO::PARAM_STR);
		$statement->bindParam(2, $_POST["password"], PDO::PARAM_STR);
		$statement->execute();
		$userDetail = $statement->fetch(PDO::FETCH_BOTH);
		unset($statement);
	} catch (PDOException $e) {
		//TODO: examine code & message in exception
		respond(500, false, "Unidentified database error.");
	}

	// Make sure the user exists and is active.
	if (isset($userDetail["userID"]) && isset($userDetail["active"]) && $userDetail["active"] === 1) {
		$userID = $userDetail["userID"];
		unset($userDetail);
	} else {
		// Do not inform the client the selected user may be inactive.
		respond(401, false, "Incorrect username or password.");
	}

	// Grab the user's API keys from the database.
	try {
		$statement = $db->prepare("SELECT id, key, active FROM api_keys WHERE owner = ?");
		$statement->bindParam(1, $userID, PDO::PARAM_INT);
		$statement->execute();
		$keys = $statement->fetchAll(PDO::FETCH_BOTH);
		unset($statement);
	} catch (PDOException $e) {
		//TODO: examine code & message in exception
		respond(500, false, "Unidentified database error.");
	}

	// No keys were found.
	if (count($keys) == 0) {
		// Do not inform the client there are no API keys for the user.
		respond(401, false, "Invalid API key.");
	}
	// Cycle through all of the keys returned from the database to find one that matches
	// the supplied key.
	$findKey = NULL;
	foreach ($keys as $key) {
		if ($key["key"] === $_SERVER["HTTP_X_API_KEY"]) {
			if ($key["active"] === 1) {
				$findKey = $key["id"];
				break;
			} else {
				// Do not inform the client that the API key it supplied is inactive.
				respond(401, false, "Invalid API key.");
			}
		}
	}
	// No active keys matched the supplied key.
	if (!$findKey) {
		// No matching API keys were found for that user.
		respond(401, false, "Invalid API key.");
	}
	unset($keys);

	// Check to see if the user already has any existing sessions.
	// First, clean up old ones.
	cleanSessions();

	// If we reach this point, the client has (theoretically) successfully authenticated with the system.
	// Therefore, create a session for the user as requested.
	try {
		if (!$db->beginTransaction()) {
			respond(503, false, "Unable to create new session.");
		}
	} catch (PDOException $e) {
		//TODO: examine code & message in exception
		respond(500, false, "Unidentified database error.");
	}

	$expiryTime = date("Y-m-d H:i:s", time() + 300);
	$sessionToken = sha1(md5("$userId" . time() . "$findKey" . rand()));
	try {
		$statement = $db->prepare("INSERT INTO `sessions` (`key`, `expiry`, `token`) VALUES (?, ?, ?)");
		$statement->bindParam(1, $findKey, PDO::PARAM_INT);
		$statement->bindParam(2, $expiryTime, PDO::PARAM_STR);
		$statement->bindParam(3, $sessionToken, PDO::PARAM_STR);
		$statement->execute();
		unset($statement);
	} catch (PDOException $e) {
		//TODO: examine code & message in exception
		$db->rollBack();
		respond(500, false, "Unidentified database error.");
	}

	try {
		$statement = $db->prepare("UPDATE users SET lastLogin = UTC_TIMESTAMP() WHERE userID = ?");
		$statement->bindParam(1, $userID, PDO::PARAM_INT);
		$statement->execute();
		unset($statement);
	} catch (PDOException $e) {
		//TODO: examine code & message in exception
		$db->rollBack();
		respond(500, false, "Unidentified database error.");
	}

	try {
		if (!$db->commit()) {
			$db->rollBack();
			respond(503, false, "Unable to create new session.");
		}
	} catch (PDOException $e) {
		//TODO: examine code & message in exception
		$db->rollBack();
		respond(500, false, "Unidentified database error.");
	}

	$response = array();
	$response["session"] = array();
	$response["session"]["expiryTime"] = $expiryTime;
	$response["session"]["token"] = $sessionToken;
	respond(200, true, "Session created successfully.", $response);
}
