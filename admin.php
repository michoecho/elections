<?php
session_start();
require_once "authorize.php";
auth(["admin"]);

require_once "config.php";
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<link href="default.css" rel="stylesheet" type="text/css">
	<title>Admin</title>
</head>
<body>
<a href="logout.php">Logout</a>
<h1>Admin</h1>
<ul>
	<li><a href="add_commission.php">Register new commissions</a>
</ul>
</body>
</html>
