<?php
if (!defined("MODE")) {
	exit("No direct script access allowed.");
}

$methodRegistry["user_search"] = true;
//TODO: allow user to request less results than the maximum
function user_search()
{
	if (empty($_POST["user"])) {
		respond(400, false, translate("There was no username supplied with the request."));
	} elseif (strlen($_POST["user"]) < $GLOBALS["CONFIG"]["USER"]["SEARCH"]["MIN_QUERY_LEN"]) {
		respond(400, false, translate("The search query was not long enough."));
	}
	global $db, $GLOBAL;

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
		$statement->bindParam(2, $GLOBALS["CONFIG"]["USER"]["SEARCH"]["MAX_RESULTS"], PDO::PARAM_INT);
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
	$response["session"] = array();
	$response["session"]["expiryTime"] = $GLOBAL["EXPIRYTIME"];
	respond(200, true, translate("Search results successfully retrieved."), $response);
}

