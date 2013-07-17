<?php
if (!defined("MODE")) {
	exit("No direct script access allowed.");
}

$methodRegistry["user_search"] = true;
//TODO: username search should be case-insensitive
function user_search()
{
	if (empty($_POST["user"])) {
		respond(400, false, translate("There was no username supplied with the request."));
	} elseif (strlen($_POST["user"]) < $GLOBALS["CONFIG"]["USER"]["SEARCH"]["MIN_QUERY_LEN"]) {
		respond(400, false, translate("The search query was not long enough."));
	}
	global $db, $GLOBAL;

	if (empty($_POST["limit"])) {
		// If the client didn't supply a limit, use the default.
		$limit = $GLOBALS["CONFIG"]["USER"]["SEARCH"]["DEFAULT"];
	} elseif (!is_numeric($_POST["limit"])) {
		// If the client didn't supply a numeric limit, tell them they made a mistake.
		respond(400, false, translate("Invalid limit supplied with the request."));
	} else {
		$intLimit = intval($_POST["limit"]);
		if ($intLimit > $GLOBALS["CONFIG"]["USER"]["SEARCH"]["MAX_RESULTS"]) {
			// If the client wants more than the allowed maximum, give them the maximum and warn them.
			$badLimit = true;
			$limit = $GLOBALS["CONFIG"]["USER"]["SEARCH"]["MAX_RESULTS"];
		} elseif ($intLimit < 1) {
			// If the client wants less than one result, warn them and give them the default.
			$badLimit = true;
			$limit = $GLOBALS["CONFIG"]["USER"]["SEARCH"]["DEFAULT"];
		} else {
			$badLimit = false;
			$limit = $intLimit;
		}
	}

	if (empty($_POST["method"])) {
		$method = "autocomplete";
	} elseif ($_POST["method"] != "autocomplete" && $_POST["method"] != "fuzzy") {
		respond(400, false, translate("Invalid search method supplied with the request."));
	} else {
		$method = $_POST["method"];
	}

	if ($method == "autocomplete") {
		$username = $_POST["user"] . "%";
	} else {
		$username = "%" . $_POST["user"] . "%";
	}

	try {
		$statement = $db->prepare("SELECT `userID`, `username`, `displayname` FROM `users` WHERE `username` LIKE ? LIMIT ?");
		$statement->bindParam(1, $username, PDO::PARAM_STR);
		$statement->bindParam(2, $limit, PDO::PARAM_INT);
		$statement->execute();
		$results = $statement->fetchAll(PDO::FETCH_ASSOC);
		unset($statement);
	} catch (PDOException $e) {
		logEntry("ERROR", "now", 500, __FUNCTION__, $e);
		respond(500, false, translate("Unable to grab the requested list of users."));
	}

	$response = array();
	$response["request"] = array();
	$response["request"]["results"] = $results;
	if (isset($badLimit) && $badLimit == true) {
		$response["request"]["warning"] = translate("The supplied limit was out of the allowed range.");
	}
	$response["session"] = array();
	$response["session"]["expiryTime"] = $GLOBAL["EXPIRYTIME"];
	respond(200, true, translate("Search results successfully retrieved."), $response);
}

$methodRegistry["user_get"] = true;
//TODO: username search should be case-insensitive
function user_get()
{
	if (empty($_POST["user"])) {
		respond(400, false, translate("There was no username supplied with the request."));
	}
	global $db, $GLOBAL;

	if (is_numeric($_POST["user"])) {
		$where = "userID";
		$whereType = PDO::PARAM_INT;
	} else {
		$where = "username";
		$whereType = PDO::PARAM_STR;
	}

	try {
		$statement = $db->prepare("SELECT `userID`, `username`, `displayname` FROM `users` WHERE `{$where}` = ?");
		$statement->bindParam(1, $_POST["user"], $whereType);
		$statement->execute();
		$user = $statement->fetch(PDO::FETCH_ASSOC);
		unset($statement);
	} catch (PDOException $e) {
		logEntry("ERROR", "now", 500, __FUNCTION__, $e);
		respond(500, false, translate("Unable to grab the requested user."));
	}

	if (!$user) {
		respond(400, false, translate("Unable to find the requested user."));
	}

	$response = array();
	$response["request"] = array();
	$response["request"]["user"] = $user;
	$response["session"] = array();
	$response["session"]["expiryTime"] = $GLOBAL["EXPIRYTIME"];
	respond(200, true, translate("Requested user successfully retrieved."), $response);
}

