<?php
session_start();
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
	if ($_SESSION["category"] == "admin") {
		header("location: admin.php");
	} else if ($_SESSION["category"] == "commission") {
		header("location: commission.php");
	} else {
		header("location: voter.php");
	}
	exit();
}

require_once "config.php";

$login = $password = "";
$login_err = $password_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	if (empty(trim($_POST["login"]))) {
		$login_err = "Please enter login.";
	} else {
		$login = trim($_POST["login"]);
	}

	if (empty(trim($_POST["password"]))) {
		$password_err = "Please enter your password.";
	} else {
		$password = trim($_POST["password"]);
	}

	if (empty($login_err) && empty($password_err)) {
		$result =
			pg_query_params($db_link,
			"SELECT id, password, category FROM account WHERE login = $1",
			array($login));

		if ($result) {
			if (pg_numrows($result) == 1) {
				$row = pg_fetch_assoc($result);
				if (password_verify($password, trim($row["password"]))) {
					session_start();

					$_SESSION["loggedin"] = true;
					$_SESSION["login"] = $login;
					$_SESSION["id"] = $row["id"];
					$_SESSION["category"] = $row["category"];

					if ($_SESSION["category"] == "admin") {
						header("location: admin.php");
					} else if ($_SESSION["category"] == "commission") {
						header("location: commission.php");
					} else {
						header("location: voter.php");
					}
				} else {
					$password_err = "The password you entered was not valid.";
				}
			} else {
				$login_err = "No account found with that login.";
			}
		} else {
			echo "Oops! Something went wrong. Please try again later.";
		}
	}

	pg_close($db_link);
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<link href="default.css" rel="stylesheet" type="text/css">
	<title>Login</title>
</head>
<body>
	<h1>Login</h1>
	<p>Please fill in your credentials to login.</p>
	<form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]) ?>" method="post">
	<table>
		<tr>
			<td><label>Username</label>
			<td><input type="text" name="login" value="<?= $login ?>">
			<td><span><?= $login_err ?></span>
		<tr>
			<td><label>Password</label>
			<td><input type="password" name="password">
			<td><span><?= $password_err ?></span>
	</table>
	<input type="submit" value="Login">
	</form>
</body>
</html>
