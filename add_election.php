<?php
session_start();
require_once "authorize.php";
auth(["commission"]);
 
require_once "config.php";


$fields = array("name", "seats", "filing_deadline", "voting_start", "voting_end");
foreach ($fields as $field) {
	if (!isset($_POST[$field])) {
		$_POST[$field] = "";
	}
	$_POST[$field] = trim($_POST[$field]);
}

$name_err = "";
$seats_err = "";
$filing_deadline_err = "";
$voting_start_err = "";
$voting_end_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

	$name = $_POST["name"];
	$seats = $_POST["seats"];
	$filing_deadline = $_POST["filing_deadline"];
	$voting_start = $_POST["voting_start"];
	$voting_end = $_POST["voting_end"];

	if (empty($name)) {
		$name_err = "Please enter name.";
	}

	if (empty($seats)) {
		$seats_err = "Please enter seats.";
	} else if (!is_numeric($seats) || $seats <= 0) {
		$seats_err = "Invalid";
	} else {
		$seats = intval($seats);
	}

	if (empty($filing_deadline)) {
		$filing_deadline_err = "Please enter filing_deadline.";
	} else {
		$filing_deadline = strtotime($filing_deadline);
		if (!$filing_deadline) {
			$filing_deadline_err = "Not a datetime";
		} else if ($filing_deadline <= time()) {
			$filing_deadline_err = "Too early";
		}
	}

	if (empty($voting_start)) {
		$voting_start_err = "Please enter voting_start.";
	} else {
		$voting_start = strtotime($voting_start);
		if (!$voting_start) {
			$voting_start_err = "Not a datetime";
		} else if ($voting_start <= $filing_deadline) {
			$voting_start_err = "Too early";
		}
	}

	if (empty($voting_end)) {
		$voting_end_err = "Please enter voting_end.";
	} else {
		$voting_end = strtotime($voting_end);
		if (!$voting_end) {
			$voting_end_err = "Not a datetime";
		} else if ($voting_end <= $voting_start) {
			$voting_end_err = "Too early";
		}
	}

	if (empty($name_err) &&
		empty($seats_err) &&
		empty($filing_deadline_err) &&
		empty($voting_start_err) &&
		empty($voting_end_err))
	{
		$result =
			pg_query_params($db_link,
			"INSERT into election values(DEFAULT, $1, $2, to_timestamp($3),
				to_timestamp($4), to_timestamp($5), 'f', $6)",
			array($name, $seats, $filing_deadline, $voting_start, $voting_end, $_SESSION["id"]));

		if ($result) {
			$success_info = "Election '{$name}' registered successfully";
			$_POST["name"] = "";
			$_POST["seats"] = "";
			$_POST["filing_deadline"] = "";
			$_POST["voting_start"] = "";
			$_POST["voting_end"] = "";
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
	<title>Add election</title>
</head>
<body>
<a href="commission.php">Back</a>
<h1>Add a new election</h1>
<?php if (!empty($success_info)) { ?>
<p><?= $success_info ?></p>
<?php } ?>
<form action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
<table>
	<tr>
		<td><label>Name</label>
		<td><input type="text" name="name" value="<?= $_POST["name"]; ?>">
		<td><span><?= $name_err; ?></span>
	<tr>
		<td><label>Seats</label>
		<td><input type="number" name="seats" value="<?= $_POST["seats"]; ?>">
		<td><span><?= $seats_err; ?></span>
	<tr>
		<td><label>Registration deadline</label>
		<td><input type="datetime-local" placeholder="YYYY-MM-DD hh:mm:ss"
		name="filing_deadline" value="<?= $_POST["filing_deadline"]; ?>">
		<td><span><?= $filing_deadline_err; ?></span>
	<tr>
		<td><label>Voting start</label>
		<td><input type="datetime-local" placeholder="YYYY-MM-DD hh:mm:ss"
		name="voting_start" value="<?= $_POST["voting_start"]; ?>">
		<td><span><?= $voting_start_err; ?></span>
	<tr>
		<td><label>Voting end</label>
		<td><input type="datetime-local" placeholder="YYYY-MM-DD hh:mm:ss"
		name="voting_end" value="<?= $_POST["voting_end"]; ?>">
		<td><span><?= $voting_end_err; ?></span>
</table>
<input type="submit">
</form>
</body>
</html>
