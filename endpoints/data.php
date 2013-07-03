<?php
if (!defined("MODE")) {
	exit("No direct script access allowed.");
}

$methodRegistry["data_send"] = true;
function data_send()
{
	global $db, $GLOBAL;

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

$methodRegistry["data_recv"] = true;
function data_recv()
{
	global $db, $GLOBAL;

	if (empty($_POST["limit"])) {
		$limit = $GLOBALS["CONFIG"]["DATA_GET_DEFAULT"];
	} elseif (!is_numeric($_POST["limit"])) {
		respond(400, false, translate("Invalid limit requested."));
	} else {
		$intLimit = intval($_POST["limit"]);
		if ($intLimit > $GLOBALS["CONFIG"]["DATA_GET_MAX"]) {
			$badLimit = true;
			$limit = $GLOBALS["CONFIG"]["DATA_GET_MAX"];
		} elseif ($intLimit < 1) {
			$badLimit = true;
			$limit = $GLOBALS["CONFIG"]["DATA_GET_DEFAULT"];
		} else {
			$badLimit = false;
			$limit = $intLimit;
		}
	}

	try {
		$statement = $db->prepare("SELECT `id`, `dateRecorded`, `datatype`, `subject`, `summary`, `text`, `sendTo` FROM `main_data` WHERE `owner` = ? ORDER BY `dateRecorded` DESC LIMIT ?");
		$statement->bindParam(1, $GLOBAL["CURUSER"], PDO::PARAM_INT);
		$statement->bindParam(2, $limit, PDO::PARAM_INT);
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

