<?php
session_start();
require_once "authorize.php";
auth(["voter"]);

require_once "config.php";

$result =
	pg_query_params($db_link,
	"SELECT *, voter_id IS NOT NULL voted
	FROM election LEFT JOIN vote ON vote.election_id = election.id AND vote.voter_id = $1
	WHERE CURRENT_TIMESTAMP BETWEEN voting_start AND voting_end
	ORDER BY voting_end ASC", [$_SESSION["id"]]);

if ($result) {
	$elections = pg_fetch_all($result);
	if (!$elections) {
		$elections = [];
	}
} else {
	die("Oops! Something went wrong. Please try again later.");
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<link href="default.css" rel="stylesheet" type="text/css">
	<title>Open elections</title>
</head>
<body>
<a href="voter.php">Back</a>
<h2>Open elections</h2>
<table>
	<thead>
		<tr>
			<th>Name</th>
			<th>Seats</th>
			<th>Filing deadline</th>
			<th>Voting start</th>
			<th>Voting end</th>
			<th>Action</th>
		</tr>
	</thead>
	<tbody>
<?php foreach($elections as $key => $election) :?>
		<tr class="<?= $election["voted"] == 'f' ? 'available' : 'unavailable'?>">
			<td><?= $election["name"] ?></td>
			<td><?= $election["seats"] ?></td>
			<td><?= $election["filing_deadline"] ?></td>
			<td><?= $election["voting_start"] ?></td>
			<td><?= $election["voting_end"] ?></td>
			<td>
<?php if ($election["voted"] == 'f'): ?>
				<a href="<?= "vote.php?" . http_build_query(["election" => $election["id"]]) ?>">Vote</a>
<?php else: ?>
				Already voted
<?php endif; ?>
			</td>
		</tr>
<?php endforeach; ?>
	</tbody>
</table>
</body>
</html>
