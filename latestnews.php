<TABLE BORDER CELLPADDING="0" background="bocimages/scrollbgdark.gif" width="100%">
	<TR>
	<!-- Header or whatever -->
	<center><a href="https://discord.gg/DUKQRXf" target="_blank"><IMG SRC="bocimages/largebanner.png" border="0"></a><BR></center>
		<TD valign="top" class="notes">


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

echo "</center>";

?>

		</TD>
	</TR>
</TABLE>


