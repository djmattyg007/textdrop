<?php

function respond($statusCode, $successful, $message, $payload = NULL) {
	//TODO: error-check the MODE constant

	// Error check the provided variables.
	if (!is_int($statusCode)) {
		respondFatal();
	} else if ($statusCode < 100 || >= 600) {
		// There aren't any HTTP status codes outside of these bounds.
		respondFatal();
	}
	if (!is_bool($successful) {
		respondFatal();
	}
	// The payload must be an array.
	if ($payload === NULL) {
		$payload = array();
	} else {
		if (!is_array($payload)) {
			respondFatal();
		}
	}
	// Do not allow overwriting of status parameters.
	if (isset($payload["status"]) || isset($payload["successful"]) || isset($payload["message"])) {
		respondFatal();
	}

	http_response_code($statusCode);

	$response = "";
	if (MODE === "API") {
		$response .= json_encode(array_merge(array("status" => $statusCode, "successful" => $successful, "message" => $message), $payload));
	} else if (MODE === "WEB") {
		$response .= "<h2>Status: $statusCode</h2>\n<h2>Success: $successful</h2>\n<h2>Message:</h2><h3>$message</h3>\n\n";
		$response .= "Payload:\n<pre>" . $print_r($payload, true) . "</pre>";
	}

	exit($response);
}

function respondFatal() {
	respond(500, false, "A fatal internal error occurred.");
}

