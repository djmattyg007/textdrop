<?php
if (!defined("MODE")) {
	exit("No direct script access allowed.");
}

/* error-check config entries */

if (!empty($CONFIG["LOG_ACCESS"]))
	if (!is_dir($CONFIG["LOG_ACCESS"]) || !is_writable($CONFIG["LOG_ACCESS"])) {
		if (MODE == "CLI") {
			echo "Critical access log file error.\n";
		}
		exit(1);
	}
}

if (!empty($CONFIG["LOG_ERROR"]))
	if (!is_dir($CONFIG["LOG_ERROR"]) || !is_writable($CONFIG["LOG_ERROR"])) {
		if (MODE == "CLI") {
			echo "Critical error log file error.\n";
		}
		exit(1);
	}
}

if (!is_numeric($CONFIG["LOG_SIZE"]) || $CONFIG["LOG_SIZE"] < 0.5) {
	if (MODE == "CLI") {
		echo "Critical log file error.\n";
	}
	exit(1);
}

//TODO: see if this can be changed to zero once log rotation is in operation
if (!is_numeric($CONFIG["LOG_MAX"]) || $CONFIG["LOG_MAX"] < 1) {
	if (MODE == "CLI") {
		echo "Critical log file error.\n";
	}
	exit(1);
}

// Do not put a new line at the end of the description.
// The logRequestTime should be the server time, not the time included in a request.
// The statusCode variable should match what is sent to the client.
function logEntry($logType, $logRequestTime, $statusCode, $call, $description)
{
	$type = "LOG_" . strtoupper($logType);
	if (!isset($CONFIG[$type])) {
		if (MODE == "CLI") {
			echo "Critical error log file error.\n";
		}
		$descrip = "The specified log type ({$logType}) does not exist. The following information was supposed to be logged:\n";
		$descrip .= "logRequestTime: {$logRequestTime}, statusCode: {$statusCode}, call: {$call}, description: {$description}"
		logEntry("ERROR", date("Y-m-d H:i:s", time()), 500, "logEntry()", $descrip);
		return;
	}
	if (empty($CONFIG[$type])) {
		return null;
	}

	logRotate($type);
}

//TODO: strip out log rotation into a library
function logRotate($logType)
{
	// If there is no existing log file, create one and finish.
	$logFile = $CONFIG[$logType] . DIRECTORY_SEPARATOR . $logType . ".log";
	if (!file_exists($CONFIG[$logType])) {
		file_put_contents($logFile, "");
		return;
	}

	// Check the size of the current log file.
	// If it is above the maximum size allowed for a log, we need to rotate it.
	$logFileSize = filesize($logFile);
	$maxLogFileSize = 5242880; // Default maximum size allowed for a log.
	if (!empty($CONFIG["LOG_SIZE"]) && is_numeric($CONFIG["LOG_SIZE"])) {
		$maxLogFileSize = $CONFIG["LOG_SIZE"] * 1024 * 1024;
	}
	if ($logFileSize > $maxLogFileSize) {
		// Rotate the logs.
		$maxLogs = 10;
	} else {
		// No rotation required.
		return;
	}
	if (!empty($CONFIG["LOG_MAX"]) && is_numeric($CONFIG["LOG_MAX"])) {
		$maxLogs = $CONFIG["LOG_MAX"];
	}

	// Perform the log rotation.
	for ($x = $maxLogs; $x > 1; $x--) {
		$oldLog = $logFile . ".{$x}.gz";
		if (file_exists($oldLog)) {
			if ($x = $maxLogs) {
				unlink($oldLog);
			} else {
				rename($oldLog, $logFile . "." . ($x + 1) . ".gz";
			}
		}
	}
	rename($logFile, $logFile . ".2");
	//compress log!
	file_put_contents($logFile, "");
	return;
}

function checkRequestTimeout($requestTime)
{
	$timeout = strtotime($requestTime) + ($CONFIG["API_REQUEST_TIMEOUT"] * 60);
	if ($timeout > $time()) {
		return true; // Request has not timed out.
	} else {
		return false; // Request has timed out.
	}
}
