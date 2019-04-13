<?php
function auth($roles) {
	if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
		header("location: login.php");
		exit();
	} else if (!in_array($_SESSION["category"], $roles)) {
		http_response_code(403);
		die("Unauthorized");
	}
}
?>
