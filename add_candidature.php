<?php
session_start();
require_once "authorize.php";
auth(["voter"]);
 
if (!isset($_GET["election"])) {
	die("Page not found");
}

require_once "config.php";

$id = $_GET["election"];
$result = pg_query_params($db_link,
	"SELECT *, CURRENT_TIMESTAMP < filing_deadline AS filing_open
	FROM election WHERE id = $1", [$id]);
if (!$result) {
	die("Oops! Something went wrong. Please try again later.". pg_last_error());
}
$election = pg_fetch_assoc($result);
if (!$election) {
	die("Page not found");
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["register"])) {
	$result = pg_prepare($db_link, "add_candidature",
		"INSERT INTO candidature values($1, $2, 0)");
	foreach ($_POST["register"] as $key => $value) {
		$result = pg_execute($db_link, "add_candidature", [$id, $value]);
	}
}

$result =
	pg_query_params($db_link,
	"SELECT account.name
	FROM candidature
	JOIN election ON election.id = candidature.election_id AND election.id = $1
	JOIN account ON account.id = candidature.candidate_id
	ORDER BY account.name ASC", [$id]);
if (!$result) {
	die("Oops! Something went wrong. Please try again later.". pg_last_error());
}
$candidatures = pg_fetch_all($result);
if (!$candidatures) {
	$candidatures = [];
}

$result =
	pg_query_params($db_link,
	"SELECT account.name, account.id
	FROM candidature
	JOIN election ON election.id = candidature.election_id AND election.id = $1
	RIGHT JOIN account ON account.id = candidature.candidate_id
	WHERE account.category = 'voter' AND candidature.candidate_id IS NULL
	ORDER BY account.name ASC", [$id]);
if (!$result) {
	die("Oops! Something went wrong. Please try again later.". pg_last_error());
}
$choices = pg_fetch_all($result);
if (!$choices) {
	$choices = [];
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<link href="default.css" rel="stylesheet" type="text/css">
	<title>Add candidature</title>
</head>
<body>
<a href="browse_future.php">Back</a>
<p>
	Election: <?= $election["name"] ?><br>
	Registration ends: <?= $election["filing_deadline"] ?><br>
	Voting starts: <?= $election["voting_start"] ?><br>
	Voting ends: <?= $election["voting_end"] ?><br>
	Seats: <?= $election["seats"] ?><br>
</p>
<?php if ($election["filing_open"] == 't'): ?>
<h2>Register candidates</h2>
<form action="<?= htmlspecialchars($_SERVER["PHP_SELF"] . '?' . $_SERVER["QUERY_STRING"]); ?>" method="post">
<table>
	<caption>Possible candidates</caption>
	<thead>
		<tr>
			<th>Name</th>
			<th>Register</th>
		</tr>
	</thead>
	<tbody>
<?php foreach($choices as $key => $choice) :?>
		<tr>
			<td><?= $choice["name"] ?></td>
			<td><input type="checkbox" name="register[]" value="<?= $choice["id"] ?>"></td>
		</tr>
<?php endforeach; ?>
	<tbody>
</table>
<input type="submit">
</form>
<?php else: ?>
<h2>Registration closed.</h2>
<?php endif; ?>
<table>
	<caption>Registered candidates</caption>
	<thead>
		<tr>
			<th>Name</th>
		</tr>
	</thead>
	<tbody>
<?php foreach($candidatures as $key => $candidature) :?>
		<tr>
			<td><?= $candidature["name"] ?></td>
		</tr>
<?php endforeach; ?>
	</tbody>
</table>
</body>
</html>
