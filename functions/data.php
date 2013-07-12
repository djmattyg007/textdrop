<?php
if (!defined("MODE")) {
	exit("No direct script access allowed.");
}
//TODO: draw an entire data into memory and cache it

// Returns an integer that represents the user that owns the piece of data identified
// by the supplied ID number. If the data does not exist, it returns null.
function dataf_owner($dataID)
{
	if (!is_numeric($dataID) {
		respondFatal();
	}
	global $db;

	try {
		$statement = $db->prepare("SELECT `owner` FROM `main_data` WHERE `id` = ?");
		$statement->bindParam(1, $dataID, PDO::PARAM_INT);
		$statement->execute();
		$ownerID = $statement->fetchColumn();
		unset($statement);
	} catch (PDOException $e) {
		logEntry("ERROR", "now", 500, __FUNCTION__, $e);
		respond(500, false, translate("Unidentified database error."));
	}

	if ($ownerID) {
		return $ownerID;
	} else {
		return null;
	}
}

function dataf_archived($dataID)
{
	if (!is_numeric($dataID)) {
		respondFatal();
	}
	global $db;

	try {
		$statement = $db->prepare("SELECT `archived` FROM `main_data` WHERE `id` = ?");
		$statement->bindParam(1, $dataID, PDO::PARAM_INT);
		$statement->execute();
		$archived = $statement->fetchColumn();
		unset($statement);
	} catch (PDOException $e) {
		logEntry("ERROR", "now", 500, __FUNCTION__, $e);
		respond(500, false, translate("Unidentified database error."));
	}

	//TODO: check to make sure archived isn't an integer that is less than zero or greater than one.
	if ($archived == 1 || $archived == 0) {
		return $archived;
	} else {
		return null;
	}
}

