<?php

ini_set('default_socket_timeout', '3');
const TIMEOUT = 3;

header('Content-Type: application/json');
$error = false;

if (!empty($_GET['channel'])) {
	$type = strtolower($_GET['channel']);
} else {
	//Default channel
	$type = "stable";
}

function response_500($message){
	http_response_code(500);
	echo json_encode([
		"error" => "Internal error ($message)"
	]);
	die();
}

function curl_url($url){
	$ch = curl_init($url);
	curl_setopt_array($ch, [
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_HEADER => 1,
		CURLOPT_CONNECTTIMEOUT_MS => TIMEOUT * 1000,
		CURLOPT_TIMEOUT_MS => TIMEOUT * 1000,
	]);
	$output = curl_exec($ch);
	$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	$error = curl_error($ch);
	curl_close($ch);

	return [$output, $headerSize, $error];
}

$url = "https://raw.githubusercontent.com/pmmp/update.pmmp.io/master/channels/$type.json";

list($output, $headerSize, $error) = curl_url($url);
if($output === false) response_500("Failed to communicate with GitHub");

$headers = explode("\n", substr($output, 0, $headerSize));
if(strpos($headers[0], "404 Not Found") !== false){
	http_response_code(404);
	echo json_encode([
		"error" => "Channel not found"
	]);
	die();

}

$jobJSON = substr($output, $headerSize);
if($jobJSON === false) response_500("Couldn't get build information");

$buildInfo = json_decode($jobJSON, true);
if(!is_array($buildInfo)) response_500("Couldn't get build information");

echo $jobJSON;
