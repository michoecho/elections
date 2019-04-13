<?php
session_start();
require_once "authorize.php";
auth(["commission"]);

require_once "config.php";

$login = $password = $name = "";
$login_err = $password_err = $name_err = "";
$success_info = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	if (empty(trim($_POST["login"]))) {
		$login_err = "Please enter login.";
	} else {
		$login = trim($_POST["login"]);
		$result =
			pg_query_params($db_link,
			"SELECT id, password, category FROM account WHERE login = $1",
			array($login));
		if ($result) {
			if (pg_numrows($result) == 1) {
				$login_err = "Login already in use.";
			}
		} else {
			echo "Oops! Something went wrong. Please try again later.";
		}
	}
	
	if (empty(trim($_POST["password"]))) {
		$password_err = "Please enter password.";
	} else {
		$password = trim($_POST["password"]);
	}
	
	if (empty(trim($_POST["name"]))) {
		$name_err = "Please enter name.";
	} else {
		$name = trim($_POST["name"]);
	}

	if (empty($login_err) && empty($password_err) && empty($name_err)) {
		$result =
			pg_query_params($db_link,
			"INSERT into account values(DEFAULT, $1, $2, $3, $4)",
			array($login, password_hash($password, PASSWORD_DEFAULT), "voter", $name));

		if ($result) {
			$success_info = "Voter '{$name}' registered successfully";
		} else {
			echo "Oops! Something went wrong. Please try again later.";
		}
	}
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<link href="default.css" rel="stylesheet" type="text/css">
	<title>Add voters</title>
</head>
<body>
<a href="commission.php">Back</a>
<h1>Add a new voter account</h1>
<?php if (!empty($success_info)) { ?>
<p><?= $success_info ?></p>
<?php } ?>
<form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
<table>
	<tr>
		<td><label>Username</label>
		<td><input type="text" name="login">
		<td><span><?= $login_err; ?></span>
	<tr>
		<td><label>Password</label>
		<td><input type="password" name="password">
		<td><span><?= $password_err; ?></span>
	<tr>
		<td><label>Name</label>
		<td><input type="test" name="name">
		<td><span><?= $name_err; ?></span>
</table>
<input type="submit" value="Submit">
</form>
</body>
</html>
