<?php
session_start();
require_once "authorize.php";
auth(["commission"]);

require_once "config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["publish"])) {
	pg_query($db_link, "BEGIN") or die("Database error");
	foreach ($_POST["publish"] as $key => $value) {
		pg_query_params($db_link, "SELECT publish($1)", [$value]);
	}
	pg_query($db_link, "COMMIT");
}

$result =
	pg_query($db_link,
	"SELECT id, name, voting_end, voting_start, filing_deadline, published
	FROM election
	ORDER BY voting_end DESC");
if (!$result) {
	die("Oops! Something went wrong. Please try again later.". pg_last_error());
}
$elections = pg_fetch_all($result);
if (!$elections) {
	$elections = [];
}

?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<link href="default.css" rel="stylesheet" type="text/css">
	<title>Publish results</title>
</head>
<body>
<a href="commission.php">Back</a>
<h2>Publish</h2>
<form action="<?= htmlspecialchars($_SERVER["PHP_SELF"] . '?' . $_SERVER["QUERY_STRING"]); ?>" method="post">
<table>
	<thead>
		<tr>
			<th>Name</th>
			<th>Publish</th>
		</tr>
	</thead>
	<tbody>
<?php foreach($elections as $key => $value): if($value["published"] == "f"): ?>
		<tr>
			<td><?= $value["name"] ?></td>
			<td><input type="checkbox" name="publish[]" value="<?= $value["id"] ?>"></td>
		</tr>
<?php endif; endforeach; ?>
	<tbody>
</table>
<input type="submit">
</form>
<h2>Published</h2>
<table>
	<thead>
		<tr>
			<th>Name</th>
		</tr>
	</thead>
	<tbody>
<?php foreach($elections as $key => $value): if($value["published"] == "t"): ?>
		<tr>
			<td><?= $value["name"] ?></td>
		</tr>
<?php endif; endforeach; ?>
	</tbody>
</table>
</body>
</html>
