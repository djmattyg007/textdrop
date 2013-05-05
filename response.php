<?php

function respond($statusCode, $successful, $message, $payload = NULL) {
	// Error check the provided variables.
	if (!is_int($statusCode)) {
		respond_fatal();
	} else if ($statusCode < 100 || >= 600) {
		// There aren't any HTTP status codes outside of these bounds.
		respond_fatal();
	}
	if (!is_bool($successful) {
		respond_fatal();
	}
	// The payload must be an array.
	if ($payload === NULL) {
		$payload = array();
	} else {
		if (!is_array($payload)) {
			respond_fatal();
		}
	}
	// Do not allow overwriting of status parameters.
	if (isset($payload["status"]) || isset($payload["successful"]) || isset($payload["message"])) {
		respond_fatal();
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

function respond_fatal() {
	respond(500, false, "Fatal error.");
}

