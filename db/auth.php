<?php

if (!isset($_SERVER["HTTP_X_API_KEY"])) {
	respond(400, false, "There was no API key supplied with the request.");
}

//TODO: rewrite this. the user may accidentally attempt to login AND provide a session token
//and the user may not even want to login if they don't provide a session token
//TODO: logout function
if (isset($_SERVER["HTTP_SESSION_TOKEN"]) {
	if (verifyToken()) {
		//TODO: Why is this here?
		apiRouter();
	} else {
		respond(400, false, "Unable to verify session.");
	}
} else (
	apiLogin();
}

function apiLogin() {
	if (!isset($_POST["USER"]) || !isset($_POST["PASS"])) {
		respond(400, false, "There was no username or password supplied with the request.");
	}
	
	try {
		$statement = $db->prepare("SELECT user_id, active FROM users WHERE username = ? AND password = ?");
		$statement->bindParam(1, $_POST["USER"], PDO::PARAM_STR);
		$statement->bindParam(2, $_POST["PASS"], PDO::PARAM_STR);
		$statement->execute();
		$user_detail = $statement->fetch(PDO::FETCH_BOTH);
		unset($statement);
	} catch (PDOException $e) {
		//TODO: examine code & message in exception
		respond(500, false, "Unidentified database error.");
	}

	if (isset($user_detail["user_id"]) && isset($user_detail["active"]) && $user_detail["active"] === 1) {
		$user_id = $user_detail["user_id"];
		unset($user_detail);
	} else {
		// Do not inform the client the selected user may be inactive.
		respond(401, false, "Incorrect username or password.");
	}

	try {
		$statement = $db->prepare("SELECT key, active FROM api_keys WHERE owner = ?");
		$statement->bindParam(1, $user_id, PDO::PARAM_INT);
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
	$findKey = false;
	foreach ($keys as $key) {
		if ($key["key"] === $_SERVER["HTTP_X_API_KEY"]) {
			if ($key["active"] === 1) {
				$findKey = true;
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
	unset($keys, $findKey);

	// If we reach this point, the client has (theoretically) successfully authenticated with the system.
	// Therefore, create a session for the user as requested.
}

