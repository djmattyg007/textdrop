<?php
if (!defined("MODE")) {
	exit("No direct script access allowed.");
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

