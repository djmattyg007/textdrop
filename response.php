<?php
if (!defined("MODE")) {
	exit("No direct script access allowed.");
}

function respond($statusCode, $successful, $message, $payload = null)
{
	// Error check the provided variables.
	if (!is_int($statusCode)) {
		respondFatal();
	} else if ($statusCode < 100 || $statusCode >= 600) {
		// There aren't any HTTP status codes outside of these bounds.
		respondFatal();
	}
	if (!is_bool($successful)) {
		respondFatal();
	}
	// The payload must be an array.
	if ($payload === null) {
		$payload = array();
	} else {
		if (!is_array($payload)) {
			respondFatal();
		}
	}

	$response = array();
	$response["response"] = array();
	$response["response"]["status"] = $statusCode;
	$response["response"]["successful"] = $successful;
	$response["response"]["message"] = $message;
	$response["response"]["createdAt"] = date("Y-m-d H:i:s", time());
	$response["payload"] = $payload;

	http_response_code($statusCode);

	//TODO: set content-type HTTP header
	$responseMsg = "";
	if (MODE === "API") {
		$responseMsg .= json_encode($response);
	} else if (MODE === "WEB") {
		$responseMsg .= "<h2>Status: " . $response["response"]["status"] . "</h2>\n";
		$responseMsg .= "<h2>Success: " . $response["response"]["successful"] . "</h2>\n";
		$responseMsg .= "<h2>Created at: " . $response["response"]["createdAt"] . "</h2>\n";
		$responseMsg .= "<h2>Message:</h2><h3>" . $response["response"]["message"] . "</h3>\n\n";
		$responseMsg .= "Payload:\n<pre>" . $print_r($response["payload"], true) . "</pre>";
	}

	exit($responseMsg);
}

function respondFatal()
{
	respond(500, false, "A fatal internal error occurred.");
}

