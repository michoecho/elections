<?php
session_start();
require_once "authorize.php";
auth(["voter"]);

if (!isset($_GET["election"])) {
	die("Page not found");
}

require_once "config.php";

$error = "";
$id = $_GET["election"];
$result = pg_query_params($db_link,
	"SELECT seats, name, published, voting_end, voting_start,
	CURRENT_TIMESTAMP < voting_end AS ongoing
	FROM election WHERE id = $1",
	[$id]);
if (!$result) {
	die("Oops! Something went wrong. Please try again later.". pg_last_error());
}
$election = pg_fetch_assoc($result);
if (!$election) {
	die("Page not found");
} else if ($election["published"] == 'f' or $election["ongoing"] == 't') {
	$not_published = true;
} else {
	$result =
		pg_query_params($db_link,
		"SELECT account.name, vote_count, rank() OVER (ORDER BY vote_count DESC) < seats AS winner
		FROM candidature
		JOIN election ON election.id = candidature.election_id
		JOIN account ON account.id = candidature.candidate_id
		WHERE election.id = $1
		ORDER BY vote_count DESC", [$id]);
	if (!$result) {
		die("Oops! Something went wrong. Please try again later.". pg_last_error());
	}

	$results = pg_fetch_all($result);
	if (!$results) {
		$results = [];
	}
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<link href="default.css" rel="stylesheet" type="text/css">
	<title>Results</title>
</head>
<body>
<a href="browse_past.php">Back</a>
<?php if (!empty($not_published)): ?>
<h2>Not published</h2>
<?php else: ?>
<h2>Results</h2>
<p>
	Election: <?= $election["name"] ?><br>
	Voting start: <?= $election["voting_start"] ?><br>
	Voting end: <?= $election["voting_end"] ?><br>
	Seats: <?= $election["seats"] ?><br>
	<?= $error ?>
</p>
<table>
	<thead>
		<tr>
			<th>Name</th>
			<th>Vote count</th>
		</tr>
	</thead>
	<tbody>
<?php foreach($results as $key => $result) :?>
		<tr class="<?= $result["winner"] == 't' ? 'winner' : 'loser'?>">
			<td><?= $result["name"] ?></td>
			<td><?= $result["vote_count"] ?></td>
		</tr>
<?php endforeach; ?>
	<tbody>
</table>
<?php endif; ?>
</body>
</html>
