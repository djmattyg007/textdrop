<?php
if (!defined("MODE")) {
	exit("No direct script access allowed.");
}

$CONFIG = array();

$CONFIG["APP_NAME"] = "TextDrop";

// Set this to zero or null to remove the limit.
$CONFIG["MAX_SUMMARY_LEN"] = 1000;

// Set these to zero or null to remove the limit.
$CONFIG["MAX_SESSIONS_PER_USER"] = 10;
$CONFIG["MAX_SESSIONS_PER_KEY"] = 3;

// This shouldn't be set any lower than 1.
$CONFIG["MIN_SEARCH_LEN"] = 2;
$CONFIG["MAX_SEARCH_RESULTS"] = 10;

// It's up to the administrator to ensure max is greater than default.
$CONFIG["DATA_GET_MAX"] = 50;
$CONFIG["DATA_GET_DEFAULT"] = 10;

// Enter the directory to store the logs.
// No trailing slashes!
$CONFIG["LOG_ACCESS"] = "";
$CONFIG["LOG_ERROR"] = "";
// Maximum size for a log file (in Megabytes) before it is rotated.
$CONFIG["LOG_SIZE"] = 5;
// Maximum number of logs to keep before the eldest is deleted.
// This number includes the current log.
$CONFIG["LOG_MAX"] = 10;
// If you want to disable logging, do not set LOG_MAX to zero.
// Instead, simply leave the config entries for the logs you do not want active as empty strings.

$CONFIG["DB_HOST"] = "";
$CONFIG["DB_NAME"] = "";
$CONFIG["DB_USER"] = "";
$CONFIG["DB_PASS"] = "";

date_default_timezone_set("UTC");

// Enter values in minutes
$CONFIG["API_SESSION_LENGTH"] = 5;
$CONFIG["API_REQUEST_TIMEOUT"] = 2;

$CONFIG["LANG"] = "en_AU";

srand(time());

require("translation" . DIRECTORY_SEPARATOR . "translate.php");
require("config.func.php");

// Generally there should be no reason to touch these.
$GLOBAL = array();
$GLOBAL["CURUSER"] = null;
$GLOBAL["EXPIRYTIME"] = null;

