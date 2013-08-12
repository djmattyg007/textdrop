<?php
if (!defined("MODE")) {
	exit("No direct script access allowed.");
}

/**
 * Required
 * @var tag (string): a (partial) tag name
 * Optional
 * @var limit (int): the maximum number of results to be returned
 * @var method (string): the search method to be used
 */
$methodRegistry["tag_search"] = true;
function tag_search()
{
	if (empty($_POST["tag"])) {
		respond(400, false, translate("There was no tag label supplied with the request."));
	} elseif (strlen($_POST["tag"]) < $GLOBALS["CONFIG"]["TAG"]["SEARCH"]["MIN_QUERY_LEN"]) {
		respond(400, false, translate("The search query was not long enough."));
	}
	global $db, $GLOBAL;
	$methods = array("autocomplete", "fuzzy");

	if (empty($_POST["limit"])) {
		// If the client didn't supply a limit, use the default.
		$limit = $GLOBALS["CONFIG"]["TAG"]["SEARCH"]["DEFAULT"];
		$badLimit = false;
	} elseif (!is_numeric($_POST["limit"])) {
		// If the client didn't supply a numeric limit, tell them they made a mistake.
		respond(400, false, translate("Invalid limit supplied with the request."));
	} else {
		$intLimit = intval($_POST["limit"]);
		if ($intLimit > $GLOBALS["CONFIG"]["TAG"]["SEARCH"]["MAX_RESULTS"]) {
			// If the client wants more than the allowed maximum, give them the maximum and warn them.
			$badLimit = true;
			$limit = $GLOBALS["CONFIG"]["TAG"]["SEARCH"]["MAX_RESULTS"];
		} elseif ($intLimit < 1) {
			// If the client wants less than one result, warn them and give them the default.
			$badLimit = true;
			$limit = $GLOBALS["CONFIG"]["TAG"]["SEARCH"]["DEFAULT"];
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
		$tagname = $_POST["user"] . "%";
	} else {
		$tagname = "%" . $_POST["user"] . "%";
	}

	try {
		$statement = $db->prepare("SELECT `tagID`, `label` FROM `tags` WHERE `label` LIKE ? LIMIT ?");
		$statement->bindParam(1, $tagname, PDO::PARAM_STR);
		$statement->bindParam(2, $limit, PDO::PARAM_INT);
		$statement->execute();
		$results = $statement->fetchAll(PDO::FETCH_ASSOC);
		unset($statement);
	} catch (PDOException $e) {
		logEntry("ERROR", "now", 500, __FUNCTION__, $e);
		respond(500, false, translate("Unable to grab a list of tags."));
	}

	$response = array();
	$response["request"] = array();
	$response["request"]["results"] = $results;
	if ($badLimit == true) {
		$response["request"]["warning"] = translate("The supplied limit was out of the allowed range.");
	}
	$response["session"] = array();
	$response["session"]["expiryTime"] = $GLOBAL["EXPIRYTIME"];
	respond(200, true, translate("Search results successfully retrieved."), $response);
}

$methodRegistry["tag_get"] = true;
function tag_get()
{
	if (empty($_POST["tagID"])) {
		respond(400, false, translate("There was no tag ID supplied with the request."));
	}
	if (!is_numeric($_POST["tagID"])) {
		respond(400, false, translate("Invalid tag ID supplied with the request."));
	}
	$tagID = intval($_POST["tagID"]);
	if (!tagf_draw($tagID)) {
		respond(400, false, translate("There was no tag matching the ID supplied with the request."));
	}

	$response = array();
	$response["request"] = array();
	$response["request"]["tag"] = $GLOBALS["tagf"][$tagID];
	$response["session"] = array();
	$response["session"]["expiryTime"] = $GLOBAL["EXPIRYTIME"];
	respond(200, true, translate("Requested tag successfully retrieved."), $response);
}

