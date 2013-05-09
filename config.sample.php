<?php

$CONFIG = array();

$CONFIG["APP_NAME"] = "TextDrop";

$CONFIG["DB_HOST"] = "";
$CONFIG["DB_NAME"] = "";
$CONFIG["DB_USER"] = "";
$CONFIG["DB_PASS"] = "";

date_default_timezone_set("UTC");

// Enter values in minutes
$CONFIG["API_SESSION_LENGTH"] = 5;
$CONFIG["API_REQUEST_TIMEOUT"] = 2;

srand(time());

function checkRequestTimeout($requestTime)
{
	$timeout = strtotime($requestTime) + ($CONFIG["API_REQUEST_TIMEOUT"] * 60);
	if ($timeout > $time()) {
		return true;
	} else {
		return false;
	}
}

