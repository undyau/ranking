<?php 
DEFINE  ('DB_USER', 'bigfoot1_webuser');
DEFINE  ('DB_PASSWORD', 'W7Oovdgt3udqX2CZ15Vs');
DEFINE ('DB_HOST', 'localhost');
DEFINE ('DB_NAME', 'bigfoot1_ranking');

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, 3306);
if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
}
?>
