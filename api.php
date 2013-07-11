<?php
//TODO: finish abstracting the creation and committing of DB transactions
//TODO: re-check 500 errors

define("MODE", "API");

// Initialise response functions.
require("response.php");

// Setup site configuration.
require("config.php");

// The client did not specify which method they were calling.
if (!isset($_GET["q"])) {
	respond(400, false, translate("No request method was specified."));
}

// If no API key was supplied, no further processing should be done.
if (empty($_SERVER["HTTP_X_API_KEY"])) {
	respond(400, false, translate("There was no API key supplied with the request."));
}

if (empty($_POST["createdAt"])) {
	respond(400, false, translate("Unable to ascertain when the request was generated."));
}

// Do not accept requests made over two minutes ago.
if (!checkRequestTimeout($_POST["createdAt"])) {
	respond(408, false, translate("Your request timed out. Please check the time on your device."));
}

// Start routing.
$request = explode("/", $_GET["q"]); // Break down request URI.
$requestType = $request[0]; // Explicitly grab the first segment of the request URI (the endpoint name).
$endpoint = "endpoints" . DIRECTORY_SEPARATOR . $requestType . ".php"; // Construct the endpoint filename.
// Make sure the endpoint exists.
if (!file_exists($endpoint)) {
	respond(404, false, translate("The requested method could not be found."));
}
$methodRegistry = array(); // Used to set whether or not a method requires authentication.
require($endpoint); // Initialise the method registry and the endpoint's methods.
$endpointFunc = "functions" . DIRECTORY_SEPARATOR . $requestType . ".php";
if (file_exists($endpointFunc)) {
	require($endpointFunc);
}

$requestMethod = $request[1]; // Explicitly grab the second segment of the request URI (the endpoint method).
$requestFunction = $requestType . "_" . $requestMethod; // Construct the endpoint method's function name.
// Make sure the endpoint method exists.
if (!function_exists($requestFunction)) {
	respond(404, false, translate("The requested method could not be found."));
}
// Make sure the endpoint method's registry entry exists.
if (!isset($methodRegistry[$requestFunction])) {
	respondFatal();
}

// Setup database connection.
require("db" . DIRECTORY_SEPARATOR . "connection.php");

// Check to see if the selected endpoint method requires authentication
if ($methodRegistry[$requestFunction]) {
	verifySession();
}

// We are now ready to actually process the client's request.
call_user_func($requestFunction);

