<center><a href="https://discord.gg/DUKQRXf" target="_blank"><IMG SRC="bocavamassbot.jpg"></a></center>

<div class="middle-content-container">
	<?php
	include_once "bbcode.php";
	include_once "secrets.php";

	$dsn = "mysql:host=$host;dbname=$db;";
	$pdo = new PDO($dsn, $user, $pass);
	$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

	$stmt = $pdo->query("SELECT * FROM `smf_messages` WHERE `ID_BOARD` = 8 GROUP BY ID_TOPIC ORDER BY postertime DESC LIMIT 20");

	while ($covenewsentry = $stmt->fetch())
	{
		$time = ToUnix($covenewsentry["posterTime"]);
		$title = $covenewsentry["subject"];
		$text = $covenewsentry["body"];

		echo "<FONT COLOR=\"#ECEB1D\"><B><U>" . doUBBC($title) . "</U></B></FONT><BR>";
		echo doUBBC($text);

		echo "<P>";
	}
	?>
</div>
