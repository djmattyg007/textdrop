<?php

define("MODE", "API");

// Initialise response functions.
require("response.php");

// The client did not specify which method they were calling.
if (!isset($_GET["q"])) {
	respond(400, false, "No request method was specified.");
}

// If no API key was supplied, no further processing should be done.
if (!isset($_SERVER["HTTP_X_API_KEY"])) {
	respond(400, false, "There was no API key supplied with the request.");
}

//TODO: Check for created at time

// Setup site configuration.
require("config.php");

// Start routing.
$request = explode("/", $_GET["q"]); // Break down request URI.
$requestType = $request[0]; // Explicitly grab the first segment of the request URI (the endpoint name).
$endpoint = "endpoints" . DIRECTORY_SEPARATOR . $requestType . ".php"; // Construct the endpoint filename.
// Make sure the endpoint exists.
if (!file_exists($endpoint)) {
	respond(404, false, "The requested method could not be found.");
}
$methodRegistry = array(); // Used to set whether or not a method requires authentication.
require($endpoint); // Initialise the method registry and the endpoint's methods.

$requestMethod = $request[1]; // Explicitly grab the second segment of the request URI (the endpoint method).
$requestFunction = $requestType . "_" . $requestMethod; // Construct the endpoint method's function name.
// Make sure the endpoint method exists.
if (!function_exists($requestFunction)) {
	respond(404, false, "The requested method could not be found.");
}
// Make sure the endpoint method's registry entry exists.
if (!isset($methodRegistry[$requestFunction])) {
	respondFatal();
}

// Setup database connection.
require("db" . DIRECTORY_SEPARATOR . "connection.php");

// If the endpoint method requires authentication, check to see whether or not we have it.
if ($methodRegistry[$requestFunction]) {
	verifySession();
}

// We are now ready to actually process the client's request.
call_user_func($requestFunction);

