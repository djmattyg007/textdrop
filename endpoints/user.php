<?php
if (!defined("MODE")) {
	exit("No direct script access allowed.");
}

/**
 * Required
 * @var user (string): a (partial) username
 * Optional
 * @var limit (int): the maximum number of results to be returned
 * @var method (string): the search method to be used
 */
$methodRegistry["user_search"] = true;
function user_search()
{
	if (empty($_POST["user"])) {
		respond(400, false, translate("There was no username supplied with the request."));
	} elseif (strlen($_POST["user"]) < $GLOBALS["CONFIG"]["USER"]["SEARCH"]["MIN_QUERY_LEN"]) {
		respond(400, false, translate("The search query was not long enough."));
	}
	global $db, $GLOBAL;
	$methods = array("autocomplete", "fuzzy");

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
	} elseif (!in_array($_POST["method"], $methods) {
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

/**
 * Required
 * @var user (string/int): a complete ID representing the user whose details are being requested
 */
$methodRegistry["user_get"] = true;
function user_get()
{
	if (empty($_POST["user"])) {
		respond(400, false, translate("There was no username supplied with the request."));
	}

	$user = userf_exists($_POST["user"], true);
	if (!$user) {
		respond(400, false, translate("Unable to find the requested user."));
	}

	global $GLOBAL;

	$response = array();
	$response["request"] = array();
	$response["request"]["user"] = $user;
	$response["session"] = array();
	$response["session"]["expiryTime"] = $GLOBAL["EXPIRYTIME"];
	respond(200, true, translate("Requested user successfully retrieved."), $response);
}

/**
 * Required
 * @var user (string/int): a complete ID representing a user whose friendship status with the requestor is being determined
 */
$methodRegistry["user_isfriend"] = true;
function user_isfriend()
{
	if (empty($_POST["user"])) {
		respond(400, false, translate("There was no user ID supplied with the request."));
	}

	$user = userf_exists($_POST["user"], false);
	if (!$user) {
		respond(400, false, translate("Unable to find the requested user."));
	}
	global $GLOBAL;

	$friendship = userf_isfriend($user, $GLOBAL["CURUSER"]);

	$response = array();
	$response["request"] = array();
	$response["request"]["friendship"] = $friendship;
	$response["session"] = array();
	$response["session"]["expiryTime"] = $GLOBAL["EXPIRYTIME"];
	respond(200, true, translate("Friendship status successfully retrieved."), $response);
}

