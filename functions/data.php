<?php
if (!defined("MODE")) {
	exit("No direct script access allowed.");
}

$dataf = null;

// Draw an entire data into memory and cache it. This reduces the number of
// requests to the database.
function dataf_draw($dataID)
{
	if (!is_numeric($dataID)) {
		respondFatal();
	}
	global $db;

	try {
		$statement = $db->prepare("SELECT * FROM `main_data` WHERE `id` = ?");
		$statement->bindParam(1, $dataID, PDO::PARAM_INT);
		$statement->execute();
		$data = $statement->fetch(PDO::FETCH_ASSOC);
		unset($statement);
	} catch (PDOException $e) {
		logEntry("ERROR", "now", 500, __FUNCTION__, $e);
		respond(500, false, translate("Unidentified database error."));
	}

	if ($data) {
		if ($GLOBALS["dataf"] === null) {
			$GLOBALS["dataf"] = array();
		}
		$GLOBALS["dataf"][$dataID] = $data;
		return true;
	} else {
		return false;
	}
}

// Returns an integer that represents the user that owns the piece of data identified
// by the supplied ID number. If the data does not exist, it returns null.
function dataf_owner($dataID)
{
	if (!is_numeric($dataID)) {
		respondFatal();
	}

	if (dataf_draw($dataID)) {
		return $GLOBALS["dataf"][$dataID]["owner"];
	} else {
		return null;
	}
}

function dataf_archived($dataID)
{
	if (!is_numeric($dataID)) {
		respondFatal();
	}

	if (dataf_draw($dataID)) {
		return $GLOBALS["dataf"][$dataID]["archived"];
	} else {
		return null;
	}
}

