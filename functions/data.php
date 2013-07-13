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

	if ($archived == 0 || $archived == 1) {
		return $archived;
	} elseif ($archived < 0 || $archived > 1) {
		respondFatal();
	} else {
		return null;
	}
}

