<?php
session_start();
require_once "authorize.php";
auth(["voter"]);
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<link href="default.css" rel="stylesheet" type="text/css">
	<title>Voter</title>
</head>
<body>
<a href="logout.php">Logout</a>
<h1>Voter</h1>
<ul>
	<li><a href="browse_future.php">Browse future elections</a>
	<li><a href="browse_current.php">Browse current elections</a>
	<li><a href="browse_past.php">Browse past elections</a>
</ul>
</body>
</html>
