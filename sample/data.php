<?php

include('../src/google_proxy.php');

$calendar = new GoogleCalendarProxy(
	"847185095563@developer.gserviceaccount.com",			// email from Google API console
	"847185095563.apps.googleusercontent.com",				// user id from Google API console
    file_get_contents("./key"),								// private key
    "35hnoajpn21hmjtk27s7rnc77g@group.calendar.google.com"	// calendar id
);

$calendar->connect();

?>