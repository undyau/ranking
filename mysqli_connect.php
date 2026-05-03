<?php 
DEFINE  ('DB_USER', 'webuser');
DEFINE  ('DB_PASSWORD', '0000000000000000');
DEFINE ('DB_HOST', 'localhost');
DEFINE ('DB_NAME', 'ranking');

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, 3306);
if ($mysqli->connect_errno) {
    echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
}
?>
