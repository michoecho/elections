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
	"SELECT voting_end, seats, name, voter_id,
		CURRENT_TIMESTAMP BETWEEN voting_start AND voting_end AS voting_open
	FROM election LEFT JOIN vote ON vote.election_id = election.id AND vote.voter_id = $2
	WHERE election.id = $1",
	[$id, $_SESSION["id"]]);
if (!$result) {
	die("Oops! Something went wrong. Please try again later.". pg_last_error());
}
$election = pg_fetch_assoc($result);
if (!$election or !is_null($election["voter_id"]) or $election["voting_open"] == 'f') {
	header("location: browse_current.php");
	exit();
} else {
	if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["vote"])) {
		if (count($_POST["vote"]) > $election["seats"]) {
			$error = "Too many votes";
		} else {
			foreach ($_POST["vote"] as $key => $value) {
				$values[] = sprintf(
					'(%s, %s, %s)',
					pg_escape_literal($id),
					pg_escape_literal($value),
					pg_escape_literal($_SESSION["id"])
				);
			}
			$result = pg_query($db_link, "INSERT INTO pending_vote VALUES" . implode(',', $values));
			if (!$result) {
				die("Oops! Something went wrong. Please try again later.". pg_last_error());
			}
			header("location: browse_current.php");
			exit();
		}
	}
	if (empty($already_voted)) {
		$result =
			pg_query_params($db_link,
			"SELECT account.name, account.id
			FROM candidature
			JOIN election ON election.id = candidature.election_id
			JOIN account ON account.id = candidature.candidate_id
			WHERE election.id = $1
			ORDER BY account.name ASC", [$id]);
		if (!$result) {
			die("Oops! Something went wrong. Please try again later.". pg_last_error());
		}

		$choices = pg_fetch_all($result);
		if (!$choices) {
			$choices = [];
		}
	}
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<link href="default.css" rel="stylesheet" type="text/css">
	<title>Vote</title>
</head>
<body>
	<a href="voter.php">Back</a>
	<h2>Vote</h2>
	<p>
		Election: <?= $election["name"] ?><br>
		Voting ends: <?= $election["voting_end"] ?><br>
		Seats: <?= $election["seats"] ?><br>
		<?= $error ?>
	</p>
	<form action="<?= htmlspecialchars($_SERVER["PHP_SELF"] . '?' . $_SERVER["QUERY_STRING"]); ?>" method="post">
	<table>
		<thead>
			<tr>
				<th>Name</th>
				<th>Vote</th>
			</tr>
		<tbody>
<script>
function limitChecked() {
	var max = <?= $election["seats"] ?>;
	if (document.querySelectorAll("input[name='vote[]']:checked").length >= max) {
		document.querySelectorAll("input[name='vote[]']:not(:checked)").
			forEach(x => x.setAttribute("disabled", "disabled"));
	} else {
		document.querySelectorAll("input[name='vote[]']").
			forEach(x => x.removeAttribute("disabled"));
	}
}
</script>
<?php foreach($choices as $key => $choice) :?>
			<tr>
				<td><?= $choice["name"] ?></td>
				<td><input type="checkbox" name="vote[]" onclick="limitChecked()"
					value="<?= $choice["id"] ?>"></td>
			</tr>
<?php endforeach; ?>
	</table>
	<input type="submit">
	</form>
</body>
</html>
