<?php
require_once "config.php";
pg_query_params($db_link, 'insert into account values(DEFAULT, $1, $2, $3, $4)',
	["admin", password_hash("admin", PASSWORD_DEFAULT), "admin", "Administrator"]);
pg_close($db_link);
?>
