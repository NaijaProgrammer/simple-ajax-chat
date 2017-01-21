<?php
require_once dirname(__DIR__). '/config.php';

//Verify_request_origin; same-origin requests won't set an origin.
if (isset($_SERVER['HTTP_ORIGIN'])) {
	if ( in_array($_SERVER['HTTP_ORIGIN'], $allowed_cors_domains) ) {
		header('Access-Control-Allow-Origin: '. $_SERVER['HTTP_ORIGIN']);
	} 
	else {
		header("HTTP/1.0 403 Origin Denied");
		die($message);
	}
}

//is_ajax_request() or die('Invalid request method');

define("VALID_AJAX_REQUEST", true);

if( $_SERVER['REQUEST_METHOD'] == 'GET' ) {
	if( !isset($_GET['p']) ) {
		die('Bad request');
	}

	$requested_file = $_GET['p']. '.php';
}
else if( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
	if( !isset($_POST['p'])) {
		die('Bad request');
	}

	$requested_file = $_POST['p']. '.php';
}

if( file_exists($requested_file) ) {
	include __DIR__. '/'. $requested_file;
}
else {
	echo 'Requested resource not found';
}