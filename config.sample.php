<?php
if (!defined("MODE")) {
	exit("No direct script access allowed.");
}

$CONFIG = array();

$CONFIG["APP"] = array();
$CONFIG["APP"]["NAME"] = "TextDrop";
$CONFIG["APP"]["LANG"] = "en_AU";

// Set this to zero or null to remove the limit.
$CONFIG["MAX_SUMMARY_LEN"] = 1000;

$CONFIG["SESSION"] = array();
// Set these to zero or null to remove the limit.
$CONFIG["SESSION"]["MAX_PER_KEY"] = 3;
$CONFIG["SESSION"]["MAX_PER_USER"] = 10;

$CONFIG["USER"] = array();
$CONFIG["USER"]["SEARCH"] = array();
$CONFIG["USER"]["SEARCH"]["MIN_QUERY_LEN"] = 2; // This shouldn't be set any lower than 1.
$CONFIG["USER"]["SEARCH"]["MAX_RESULTS"] = 10;
$CONFIG["USER"]["SEARCH"]["DEFAULT"] = 10;
//TODO: allow the removal of the cap on results

// It's up to the administrator to ensure max is greater than default.
$CONFIG["DATA"]["GRAB"]["MAX"] = 50;
$CONFIG["DATA"]["GRAB"]["DEFAULT"] = 10;

$CONFIG["LOG"] = array();
// Enter the directory to store the logs.
// No trailing slashes!
$CONFIG["LOG"]["TYPES"] = array();
$CONFIG["LOG"]["TYPES"]["ACCESS"] = "";
$CONFIG["LOG"]["TYPES"]["ERROR"] = "";
// Maximum size for a log file (in Megabytes) before it is rotated.
$CONFIG["LOG"]["SIZE"] = 5;
// Maximum number of logs to keep before the eldest is deleted.
// This number includes the current log.
$CONFIG["LOG"]["MAX"] = 10;
// If you want to disable logging, do not set LOG_MAX to zero.
// Instead, simply leave the config entries for the logs you do not want active as empty strings.

$CONFIG["DB"] = array();
$CONFIG["DB"]["HOST"] = "";
$CONFIG["DB"]["NAME"] = "";
$CONFIG["DB"]["USER"] = "";
$CONFIG["DB"]["PASS"] = "";

date_default_timezone_set("UTC");

$CONFIG["API"] = array();
// Enter values in minutes
$CONFIG["API"]["SESSION_LEN"] = 5;
$CONFIG["API"]["REQUEST_TIMEOUT"] = 2;

srand(time());

require("translation" . DIRECTORY_SEPARATOR . "translate.php");
require("config.func.php");

// Generally there should be no reason to touch these.
$GLOBAL = array();
$GLOBAL["CURUSER"] = null;
$GLOBAL["EXPIRYTIME"] = null;

