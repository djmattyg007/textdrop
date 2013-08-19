<?php
if (!defined("MODE")) {
	exit("No direct script access allowed.");
}

//TODO: Move session response (such as expiry time) in here
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
	header("Content-Type: application/json");
	exit(json_encode($response));
}

function respondFatal()
{
	respond(500, false, "A fatal internal error occurred.");
}

