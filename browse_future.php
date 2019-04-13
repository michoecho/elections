<?php
session_start();
require_once "authorize.php";
auth(["voter"]);

require_once "config.php";

$result =
	pg_query($db_link,
	"SELECT *, CURRENT_TIMESTAMP < filing_deadline as filing_possible
	FROM election WHERE voting_start > CURRENT_TIMESTAMP ORDER BY filing_deadline ASC");

if ($result) {
	$elections = pg_fetch_all($result);
	if (!$elections) {
		$elections = [];
	}
} else {
	$elections = [];
	echo "Oops! Something went wrong. Please try again later.";
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<link href="default.css" rel="stylesheet" type="text/css">
	<title>Upcoming elections</title>
</head>
<body>
<a href="voter.php">Back</a>
<h2>Upcoming elections</h2>
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
		<tr>
			<td><?= $election["name"] ?></td>
			<td><?= $election["seats"] ?></td>
			<td><?= $election["filing_deadline"] ?></td>
			<td><?= $election["voting_start"] ?></td>
			<td><?= $election["voting_end"] ?></td>
			<td>
<?php if ($election["filing_possible"] == 't'): ?>
				<a href="<?= "add_candidature.php?" . http_build_query(["election" => $election["id"]]) ?>">Register candidates</a>
<?php else: ?>
				<a href="<?= "add_candidature.php?" . http_build_query(["election" => $election["id"]]) ?>">View candidates</a>
<?php endif; ?>
			</td>
		</tr>
<?php endforeach; ?>
	</tbody>
</table>
</body>
</html>
