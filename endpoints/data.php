<?php
if (!defined("MODE")) {
	exit("No direct script access allowed.");
}

/**
 * Required
 * @var datatype (string): the type of data being posted to the server
 * @var subject (string): the main subject line for the data
 * @var createdAt (string): a UTC timestamp of when the data was created
 * Optional
 * @var summary (string): a summary, possibly of the main text of the data
 * @var text (string): the actual content of the data
 * @var sendTo (int): the ID of a user to send a piece of data to
 * @var tag (int): the ID of a tag to include with the data
 */
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
	} elseif (is_numeric($GLOBALS["CONFIG"]["MAX_SUMMARY_LEN"]) && $GLOBALS["CONFIG"]["MAX_SUMMARY_LEN"] > 0 && strlen($_POST["summary"]) > $GLOBALS["CONFIG"]["MAX_SUMMARY_LEN"]) {
		unset($data);
		respond(400, false, translate("The summary cannot be longer than {s} characters.", $GLOBALS["CONFIG"]["MAX_SUMMARY_LEN"]));
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
		$data["sendTo"] = helper("user", "userf_exists", $_POST["sendTo"], false);
	}
	if (empty($_POST["tag"])) {
		$data["tag"] = null;
	} else {
		$data["tag"] = helper("tag", "tagf_exists", $_POST["tag"], false);
	}

	createTransaction(translate("Unable to save submitted data."), __FUNCTION__);

	try {
		$statement = $db->prepare("INSERT INTO `main_data` (`dateRecorded`, `datatype`, `subject`, `summary`, `text`, `owner`, `sendTo`, `tag`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
		$statement->bindParam(1, $data["dateRecorded"], PDO::PARAM_STR);
		$statement->bindParam(2, $data["datatype"], PDO::PARAM_STR);
		$statement->bindParam(3, $data["subject"], PDO::PARAM_STR);
		$statement->bindParam(4, $data["summary"], PDO::PARAM_STR);
		$statement->bindParam(5, $data["text"], PDO::PARAM_STR);
		$statement->bindParam(6, $data["owner"], PDO::PARAM_INT);
		$statement->bindParam(7, $data["sendTo"], PDO::PARAM_INT);
		$statement->bindParam)8, $data["tag"], PDO::PARAM_INT);
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

/**
 * Optional
 * @var limit (int): the number of pieces of data to be returned
 * @var page (int): used for pagination of the returned data list
 * @var summary (string): set to off to disable selection of the summary field for all returned data
 * @var text (string): set to on to enable selection of the main text field for all returned data
 */
$methodRegistry["data_grab"] = true;
function data_grab()
{
	global $db, $GLOBAL;

	if (empty($_POST["limit"])) {
		// If the client didn't supply a limit, use the default.
		$limit = $GLOBALS["CONFIG"]["DATA"]["GRAB"]["DEFAULT"];
	} elseif (!is_numeric($_POST["limit"])) {
		// If the client didn't supply a numeric limit, tell them they made a mistake.
		respond(400, false, translate("Invalid limit supplied with the request."));
	} else {
		$intLimit = intval($_POST["limit"]);
		if ($intLimit > $GLOBALS["CONFIG"]["DATA"]["GRAB"]["MAX"]) {
			// If the client wants more than the allowed maximum, give them the maximum and warn them.
			$badLimit = true;
			$limit = $GLOBALS["CONFIG"]["DATA"]["GRAB"]["MAX"];
		} elseif ($intLimit < 1) {
			// If the client wants less than one data, warn them and give them the default.
			$badLimit = true;
			$limit = $GLOBALS["CONFIG"]["DATA"]["GRAB"]["DEFAULT"];
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
	if (!empty($_POST["summary"]) && $_POST["summary"] === "off") {
		$summary = "";
	} else {
		$summary = ", `summary`";
	}

	// The text is off by default, but can be turned on.
	if (!empty($_POST["text"]) && $_POST["text"] === "on") {
		$text = ", `text`";
	} else {
		$text = "";
	}

	try {
		$statement = $db->prepare("SELECT `id`, `dateRecorded`, `datatype`, `subject`{$summary}{$text}, `sendTo` FROM `main_data` WHERE `owner` = ? AND `archived` = 0 ORDER BY `dateRecorded` DESC LIMIT ?, ?");
		$statement->bindParam(1, $GLOBAL["CURUSER"], PDO::PARAM_INT);
		$statement->bindParam(2, $page, PDO::PARAM_INT);
		$statement->bindParam(3, $limit, PDO::PARAM_INT);
		$statement->execute();
		$data = $statement->fetchAll(PDO::FETCH_ASSOC);
		unset($statement);
	} catch (PDOException $e) {
		logEntry("ERROR", "now", 500, __FUNCTION__, $e);
		respond(500, false, translate("Unable to grab the selected data."));
	}

	$response = array();
	$response["request"] = array();
	$response["request"]["data"] = $data;
	if (isset($badLimit) && $badLimit == true) {
		$response["request"]["warning"] = translate("The supplied limit was out of the allowed range.");
	}
	$response["session"] = array();
	$response["session"]["expiryTime"] = $GLOBAL["EXPIRYTIME"];
	respond(200, true, translate("Requested data successfully retrieved."), $response);
}

$methodRegistry["data_get"] = true;
//TODO: add friendships
function data_get()
{
	if (empty($_POST["dataID"])) {
		respond(400, false, translate("There was no data ID supplied with the request."));
	}
	if (!is_numeric($_POST["dataID"])) {
		respond(400, false, translate("Invalid data ID supplied with the request."));
	}
	$dataID = intval($_POST["dataID"]);
	if (!dataf_draw($dataID)) {
		respond(400, false, translate("There was no data matching the ID supplied with the request."));
	}

	global $GLOBAL;
	if (dataf_owner($dataID) != $GLOBAL["CURUSER"]) {
		if (dataf_visibility($dataID) === constant("DATA_VISIBILITY_PRIVATE")) {
			respond(403, false, translate("You don't have permission to see that data."));
		} elseif (dataf_visibility($dataID) === constant("DATA_VISIBILITY_FRIENDS")) {
			if (helper("user", "userf_isfriend", dataf_owner($dataID), $GLOBAL["CURUSER"]) !== true) {
				respond(403, false, translate("You don't have permission to see that data."));
			}
		}
	}

	// Makes a copy of the resulting array
	$data = $GLOBALS["dataf"][$dataID];
	unset($data["attachment"]);

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
	if (empty($_POST["dataID"])) {
		respond(400, false, translate("There was no data ID supplied with the request."));
	}
	if (!is_numeric($_POST["dataID"])) {
		respond(400, false, translate("Invalid data ID supplied with the request."));
	}
	global $db, $GLOBAL;
	$dataID = intval($_POST["dataID"]);
	if (dataf_owner($dataID) != $GLOBAL["CURUSER"]) {
		// This check doesn't distinguish between the user attempting to access a data they don't own,
		// and the data simply not existing. This doesn't matter, as the user shouldn't know the
		// difference if they don't own it.
		respond(403, false, translate("You don't have permission to see that data."));
	}

	try {
		$statement = $db->prepare("SELECT `datatype` FROM `main_data` WHERE `id` = ?");
		$statement->bindParam(1, $dataID, PDO::PARAM_INT);
		$statement->execute();
		$datatype = $statement->fetchColumn();
		unset($statement);
	} catch (PDOException $e) {
		logEntry("ERROR", "now", 500, __FUNCTION__, $e);
		respond(500, false, translate("Unable to get the datatype for the selected data."));
	}

	if (!$datatype) {
		respond(400, false, translate("There was no data matching the ID supplied with the request."));
	}

	$response = array();
	$response["request"] = array();
	$response["request"]["datatype"] = $datatype;
	$response["session"] = array();
	$response["session"]["expiryTime"] = $GLOBAL["EXPIRYTIME"];
	respond(200, true, translate("Datatype for requested data successfully retrieved."), $response);
}

$methodRegistry["data_archive"] = true;
function data_archive()
{
	if (empty($_POST["dataID"])) {
		respond(400, false, translate("There was no data ID supplied with the request."));
	}
	if (!is_numeric($_POST["dataID"])) {
		respond(400, false, translate("Invalid data ID supplied with the request."));
	}
	global $db, $GLOBAL;
	$dataID = intval($_POST["dataID"]);
	if (dataf_owner($dataID) != $GLOBAL["CURUSER"]) {
		// This check doesn't distinguish between the user attempting to modify a data they don't own,
		// and the data simply not existing. This doesn't matter, as the user shouldn't know the
		// difference if they don't own it.
		respond(403, false, translate("You don't have permission to do that."));
	}

	$archived = dataf_archived($dataID);

	if ($archived == 0) {
		createTransaction(translate("Unable to archive selected data."), __FUNCTION__);

		try {
			$statement = $db->prepare("UPDATE `main_data` SET `archived` = 1 WHERE `id` = ?");
			$statement->bindParam(1, $dataID, PDO::PARAM_INT);
			$statement->execute();
			unset($statement);
		} catch (PDOException $e) {
			logEntry("ERROR", "now", 500, __FUNCTION__, $e);
			respond(500, false, translate("Unable to archive the selected data."));
		}

		finishTransaction(translate("Unable to archive selected data."), __FUNCTION__);

		$response = array();
		$response["request"] = array();
		$response["request"]["archived"] = true;
		$response["session"] = array();
		$response["session"]["expiryTime"] = $GLOBAL["EXPIRYTIME"];
		respond(200, true, translate("Supplied data successfully archived."), $response);
	} else {
		$response = array();
		$response["request"] = array();
		$response["request"]["archived"] = true;
		$response["request"]["warning"] = translate("The supplied data was already archived.");
		$response["session"] = array();
		$response["session"]["expiryTime"] = $GLOBAL["EXPIRYTIME"];
		respond(200, true, translate("Supplied data is archived."), $response);
	}
}

$methodRegistry["data_unarchive"] = true;
function data_unarchive()
{
	if (empty($_POST["dataID"])) {
		respond(400, false, translate("There was no data ID supplied with the request."));
	}
	if (!is_numeric($_POST["dataID"])) {
		respond(400, false, translate("Invalid data ID supplied with the request."));
	}
	global $db, $GLOBAL;
	$dataID = intval($_POST["dataID"]);
	if (dataf_owner($dataID) != $GLOBAL["CURUSER"]) {
		// This check doesn't distinguish between the user attempting to modify a data they don't own,
		// and the data simply not existing. This doesn't matter, as the user shouldn't know the
		// difference if they don't own it.
		respond(403, false, translate("You don't have permission to do that."));
	}

	$archived = dataf_archived($dataID);

	if ($archived == 1) {
		createTransaction(translate("Unable to unarchive selected data."), __FUNCTION__);

		try {
			$statement = $db->prepare("UPDATE `main_data` SET `archived` = 0 WHERE `id` = ?");
			$statement->bindParam(1, $dataID, PDO::PARAM_INT);
			$statement->execute();
			unset($statement);
		} catch (PDOException $e) {
			logEntry("ERROR", "now", 500, __FUNCTION__, $e);
			respond(500, false, translate("Unable to unarchive the selected data."));
		}

		finishTransaction(translate("Unable to unarchive selected data."), __FUNCTION__);

		$response = array();
		$response["request"] = array();
		$response["request"]["archived"] = false;
		$response["session"] = array();
		$response["session"]["expiryTime"] = $GLOBAL["EXPIRYTIME"];
		respond(200, true, translate("Supplied data successfully unarchived."), $response);
	} else {
		$response = array();
		$response["request"] = array();
		$response["request"]["archived"] = false;
		$response["request"]["warning"] = translate("The supplied data was already not archived.");
		$response["session"] = array();
		$response["session"]["expiryTime"] = $GLOBAL["EXPIRYTIME"];
		respond(200, true, translate("Supplied data is not archived."), $response);
	}
}

