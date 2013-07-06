<?php
if (!defined("MODE")) {
	exit("No direct script access allowed.");
}

$methodRegistry["data_send"] = true;
function data_send()
{
	global $db, $GLOBAL;

	// Check for compulsory values.
	if (empty($_POST["datatype"])) {
		respond(400, false, translate("There was no datatype supplied with the request."));
	} elseif (empty($_POST["subject"])) {
		respond(400, false, translate("There was no subject supplied with the request."));
	} elseif ($_POST["datatype"] !== "text") {
		respond(415, false, translate("Datatypes other than text are not supported at this time."));
	}
	$data["dateRecorded"] = $_POST["createdAt"];
	$data["datatype"] = $_POST["datatype"];
	$data["subject"] = $_POST["subject"];

	// Sanity-check the optional values.
	if (empty($_POST["summary"])) {
		$data["summary"] = "";
	} elseif (strlen($_POST["summary"]) > $GLOBALS["CONFIG"]["MAX_SUMMARY_LEN"]) {
		unset($data);
		respond(400, false, translate("The summary cannot be longer than " . $GLOBALS["CONFIG"]["MAX_SUMMARY_LEN"] . " characters."));
	} else {
		$data["summary"] = $_POST["summary"];
	}
	if (empty($_POST["text"])) {
		$data["text"] = null;
	} else {
		$data["text"] = $_POST["text"];
	}
	$data["owner"] = $GLOBAL["CURUSER"];
	if (empty($_POST["sendTo"])) {
		$data["sendTo"] = null;
	} else {
		$data["sendTo"] = null;
		//check sendTo ID to make sure it exists
	}

	createTransaction(translate("Unable to save submitted data."), __FUNCTION__);

	try {
		$statement = $db->prepare("INSERT INTO `main_data` (`dateRecorded`, `datatype`, `subject`, `summary`, `text`, `owner`, `sendTo`) VALUES (?, ?, ?, ?, ?, ?, ?)");
		$statement->bindParam(1, $data["dateRecorded"], PDO::PARAM_STR);
		$statement->bindParam(2, $data["datatype"], PDO::PARAM_STR);
		$statement->bindParam(3, $data["subject"], PDO::PARAM_STR);
		$statement->bindParam(4, $data["summary"], PDO::PARAM_STR);
		$statement->bindParam(5, $data["text"], PDO::PARAM_STR);
		$statement->bindParam(6, $data["owner"], PDO::PARAM_INT);
		$statement->bindParam(7, $data["sendTo"], PDO::PARAM_INT);
		$statement->execute();
		$dataID = $db->lastInsertId();
		unset($statement);
	} catch (PDOException $e) {
		$db->rollBack();
		logEntry("ERROR", "now", 500, __FUNCTION__, $e);
		respond(500, false, translate("Unidentified database error."));
	}
	
	finishTransaction(translate("Unable to save submitted data."), __FUNCTION__);

	$response = array();
	$response["data"] = array();
	$response["data"]["dataID"] = $dataID;
	$response["session"] = array();
	$response["session"]["expiryTime"] = $GLOBAL["EXPIRYTIME"];
	respond(200, true, translate("Submitted data saved successfully."), $response);
}

$methodRegistry["data_grab"] = true;
function data_grab()
{
	global $db, $GLOBAL;

	if (empty($_POST["limit"])) {
		// If the user didn't supply a limit, use the default.
		$limit = $GLOBALS["CONFIG"]["DATA_GET_DEFAULT"];
	} elseif (!is_numeric($_POST["limit"])) {
		// If the user didn't supply a numeric limit, tell them they made a mistake.
		respond(400, false, translate("Invalid limit supplied with the request."));
	} else {
		$intLimit = intval($_POST["limit"]);
		if ($intLimit > $GLOBALS["CONFIG"]["DATA_GET_MAX"]) {
			// If the user wants more than the allowed maximum, give them the maximum and warn them.
			$badLimit = true;
			$limit = $GLOBALS["CONFIG"]["DATA_GET_MAX"];
		} elseif ($intLimit < 1) {
			// If the user wants less than one data, warn them and give them the default.
			$badLimit = true;
			$limit = $GLOBALS["CONFIG"]["DATA_GET_DEFAULT"];
		} else {
			$badLimit = false;
			$limit = $intLimit;
		}
	}

	// If the user doesn't supply a valid limit, ignore any value they gave as a page number.
	if (isset($badLimit) && $badLimit == false) {
		if (empty($_POST["page"])) {
			$page = 0;
		} elseif (!is_numeric($_POST["page"])) {
			respond(400, false, translate("Invalid page number supplied with the request."));
		} else {
			$page = intval($_POST["page"]) - 1;
			if ($page < 0) {
				respond(400, false, translate("Invalid page number supplied with the request."));
			}
			$page *= $limit;
		}
	} else {
		$page = 0;
	}

	// The summary can be turned off, but is on by default.
	if ($_POST["summary"] === "off") {
		$summary = "";
	} else {
		$summary = ", `summary`";
	}

	// The text is off by default, but can be turned on.
	if ($_POST["text"] === "on") {
		$text = ", `text`";
	} else {
		$text = "";
	}

	try {
		$statement = $db->prepare("SELECT `id`, `dateRecorded`, `datatype`, `subject`{$summary}{$text}, `sendTo` FROM `main_data` WHERE `owner` = ? ORDER BY `dateRecorded` DESC LIMIT ?, ?");
		$statement->bindParam(1, $GLOBAL["CURUSER"], PDO::PARAM_INT);
		$statement->bindParam(2, $page, PDO::PARAM_INT);
		$statement->bindParam(3, $limit, PDO::PARAM_INT);
		$statement->execute();
		$data = array();
		while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
			$data[] = $row;
		}
		unset($statement);
	} catch (PDOException $e) {
		logEntry("ERROR", "now", 500, __FUNCTION__, $e);
		respond(500, false, translate("Unable to grab the selected data."));
	}

	$response = array();
	$response["request"] = array();
	$response["request"]["data"] = $data;
	if ($badLimit) {
		$response["request"]["warning"] = translate("The limit you supplied was out of the allowed range.");
	}
	$response["session"] = array();
	$response["session"]["expiryTime"] = $GLOBAL["EXPIRYTIME"];
	respond(200, true, translate("Requested data successfully retrieved."), $response);
}

$methodRegistry["data_get"] = true;
//TODO: add public/private attribute of data
//TODO: add friendships
function data_get()
{
	if (empty($_POST["dataID"]) {
		respond(400, false, translate("There was no data ID supplied with the request."));
	}
	if (!is_numeric($_POST["dataID"]) {
		respond(400, false, translate("Invalid data ID supplied with the request."));
	}
	global $db, $GLOBAL;

	try {
		$statement = $db->prepare("SELECT `dateRecorded`, `datatype`, `subject`, `summary`, `text`, `owner`, `sendTo` FROM `main_data` WHERE `id` = ?");
		$statement->bindParam(1, intval($_POST["dataID"]), PDO::PARAM_INT);
		$statement->execute();
		$data = $statement->fetch(PDO::FETCH_ASSOC);
		unset($statement);
	} catch (PDOException $e) {
		logEntry("ERROR", "now", 500, __FUNCTION__, $e);
		respond(500, false, translate("Unable to get the selected data."));
	}

	if (!$data) {
		respond(400, false, translate("There was no data matching the ID supplied with the request."));
	}

	$response = array();
	$response["request"] = array();
	$response["request"]["data"] = $data;
	$response["session"] = array();
	$response["session"]["expiryTime"] = $GLOBAL["EXPIRYTIME"];
	respond(200, true, translate("Requested data successfully retrieved."), $response);
}

$methodRegistry["data_type"] = true;
function data_type()
{
	if (empty($_POST["dataID"]) {
		respond(400, false, translate("There was no data ID supplied with the request."));
	}
	if (!is_numeric($_POST["dataID"]) {
		respond(400, false, translate("Invalid data ID supplied with the request."));
	}
	global $db, $GLOBAL;

	try {
		$statement = $db->prepare("SELECT `datatype` FROM `main_data` WHERE `id` = ?");
		$statement->bindParam(1, intval($_POST["dataID"]), PDO::PARAM_INT);
		$statement->execute();
		$datatype = $statement->fetch(PDO::FETCH_ASSOC);
		unset($statement);
	} catch (PDOException $e) {
		logEntry("ERROR", "now", 500, __FUNCTION__, $e);
		respond(500, false, translate("Unable to get the datatype for the selected data."));
	}

	if (!$data) {
		respond(400, false, translate("There was no data matching the ID supplied with the request."));
	}

	$response = array();
	$response["request"] = array();
	$response["request"]["datatype"] = $datatype;
	$response["session"] = array();
	$response["session"]["expiryTime"] = $GLOBAL["EXPIRYTIME"];
	respond(200, true, translate("Datatype for requested data successfully retrieved."), $response);
}

