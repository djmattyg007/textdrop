<?php
if (!defined("MODE")) {
	exit("No direct script access allowed.");
}

$tagf = null;

function tagf_draw($tagID)
{
	if (!is_numeric($dataID)) {
		respondFatal();
	}
	if (!empty($GLOBALS["dataf"][$dataID]) {
		return true;
	}

	try {
		$statement = $db->prepare("SELECT * FROM `tags` WHERE `tagID` = ?");
		$statement->bindParam(1, $tagID, PDO::PARAM_INT);
		$statement->execute();
		$tag = $statement->fetch(PDO::FETCH_ASSOC);
		unset($statement);
	} catch (PDOException $e) {
		logEntry("ERROR", "now", 500, __FUNCTION__, $e);
		respond(500, false, translate("Unidentified database error."));
	}

	if ($tag) {
		if ($GLOBALS["tagf"] === null) {
			$GLOBALS["tagf"] = array();
		}
		$GLOBALS["tagf"][$tagID] = $tag;
		return true;
	} else {
		return false;
	}
}

