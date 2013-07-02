<?php
if (!defined("MODE")) {
	exit("No direct script access allowed.");
}

$methodRegistry["data_send"] = true;
function data_send()
{
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
	} elseif (strlen($_POST["summary"]) > $CONFIG["MAX_SUMMARY_LEN"]) {
		respond(400, false, translate("The summary cannot be longer than " . $CONFIG["MAX_SUMMARY_LEN"] . " characters."));
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
		$statement->execute($data);
		$dataID = $db->lastInsertId();
		unset($statement);
	} catch (PDOException $e) {
		$db->rollBack();
		logEntry("ERROR", "now", 500, __FUNCTION__, $e);
		respond(500, false, translate("Unidentified database error."));
	}
	
	finishTransaction(translate("Unable to save submitted data.", __FUNCTION__);

	$response = array();
	$response["data"] = array();
	$response["data"]["dataID"] = $dataID;
	$response["session"] = array();
	$response["session"]["expiryTime"] = $GLOBAL["EXPIRYTIME"];
	respond(200, true, translate("Submitted data saved successfully."), $response);
}

