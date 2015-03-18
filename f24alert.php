#!/usr/bin/php
<?php

require ("lib/F24Client.php");

error_reporting(E_ERROR|E_WARNING|E_PARSE);

$options = getopt("u:p:i:dhm:");

if (isset($options['h'])) {

	print_usage();
	exit(1);
}

if (!isset($options['u'])) {
	echo "username is missing\n";
	print_usage();
	exit(2);
}

if (!isset($options['p'])) {
	echo "password is missing\n";
	print_usage();
	exit(2);
}

if (!isset($options['i'])) {
	echo "alert id is missing\n";
	print_usage();
	exit(2);
}

if (isset($options['d'])) {
	error_reporting(E_ALL);
}

$f24 = new F24Client($options['u'], $options['p']);

$f24->sendAlarm($options['i'], $options['m']);

function print_usage() {
	echo "Usage:	f24client [-d|-h] -u<username> -p<password> -i<alert id> -m\"<message>\"";
	echo "\n";
}

?>