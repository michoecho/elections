<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$db_link = pg_connect("host=host dbname=dbname user=user password=password");
if ($db_link === false) {
	die("ERROR: Could not connect. ");
}
?>
