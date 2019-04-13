<?php
session_start();
require_once "authorize.php";
auth(["commission"]);
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<link href="default.css" rel="stylesheet" type="text/css">
	<title>Commission</title>
</head>
<body>
<a href="logout.php">Logout</a>
<h1>Commission</h1>
<ul>
	<li><a href="add_voter.php">Register new voters</a>
	<li><a href="add_election.php">Register new elections</a>
	<li><a href="publish.php">Publish election results</a>
</ul>
</body>
</html>
