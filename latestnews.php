<TABLE BORDER CELLPADDING="0" background="bocimages/scrollbgdark.gif" width="100%">
	<TR>
	<!-- Header or whatever -->
	<center><IMG SRC="https://image.ibb.co/k8tEFy/largebanner.png"><BR></center>
		<TD valign="top" class="notes">


<?php

include_once "bbcode.php";

include_once "secrets.php";

$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$opt = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
$pdo = new PDO($dsn, $user, $pass, $opt);

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


