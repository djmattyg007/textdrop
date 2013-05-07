<?php

$methodRegistry["session_login"] = false;
function session_login() {
	if (!isset($_POST["username"]) || !isset($_POST["password"])) {
		respond(401, false, "There was no username or password supplied with the request.");
	}
	
	try {
		$statement = $db->prepare("SELECT user_id, active FROM users WHERE username = ? AND password = ?");
		$statement->bindParam(1, $_POST["username"], PDO::PARAM_STR);
		$statement->bindParam(2, $_POST["password"], PDO::PARAM_STR);
		$statement->execute();
		$user_detail = $statement->fetch(PDO::FETCH_BOTH);
		unset($statement);
	} catch (PDOException $e) {
		//TODO: examine code & message in exception
		respond(500, false, "Unidentified database error.");
	}

	if (isset($user_detail["user_id"]) && isset($user_detail["active"]) && $user_detail["active"] === 1) {
		$userID = $user_detail["user_id"];
		unset($user_detail);
	} else {
		// Do not inform the client the selected user may be inactive.
		respond(401, false, "Incorrect username or password.");
	}

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

	if (count($keys) == 0) {
		// Do not inform the client there are no API keys for the user.
		respond(401, false, "Invalid API key.");
	}
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
	if (!$findKey) {
		// No matching API keys were found for that user.
		respond(401, false, "Invalid API key.");
	}
	unset($keys);

	// If we reach this point, the client has (theoretically) successfully authenticated with the system.
	// Therefore, create a session for the user as requested.
	try {
		//TODO: transaction
		$statement = $db->prepare("INSERT INTO `sessions` (`owner`, `key`, `expiry`, `token`) VALUES (?, ?, ?, ?)");
		$statement->bindParam(1, $userID, PDO::PARAM_INT);
		$statement->bindParam(2, $findKey, PDO::PARAM_INT);
		$statement->bindParam(3, date("Y-m-d H:i:s", $time() + 300), PDO::PARAM_STR);
		$statement->bindParam(4, sha1(md5("$userId" . time() . "$findKey" . rand())), PDO::PARAM_STR);
		$statement->execute();
	} catch (PDOException $e) {
		//TODO: examine code & message in exception
		respond(500, false, "Unidentified database error.");
	}
}
