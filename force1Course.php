<?php
require_once(__DIR__.'/mysqli_connect.php');
require_once(__DIR__.'/processEvEvent.php');
require_once(__DIR__.'/setclass.php');
require_once(__DIR__.'/trace.php');
$url = "https://eventor.orienteering.asn.au/Events/ResultList?eventId=";
$userAgent = 'Mozilla/5.0 (X11; Linux x86_64; rv:2.0b9pre) Gecko/20110111 Firefox/4.0b9pre';

$clubs = array();
$results = array(array());
$courses = array(array());
$allCoursesSame = true;
?>

<html>
 <head>
  <title>Force results to 1 course</title>
 </head>
 <body>
 
<?php
if (have_get_value("EventorId"))
	$EventorId = $_GET["EventorId"];
else
	$EventorId = 0;
	
if (have_get_value("RankingId"))
	$RankingId = $_GET["RankingId"];
else
	$RankingId = 0;

if (($RankingId == 0 && $EventorId == 0) || ($RankingId > 0 && $EventorId > 0))
	{
	echo "<p>You must specify ONE and only one of the arguments EventorId or RankingId</p></body></html>";
	exit(0);
	}
	
if ($RankingId > 0)
	$EventorId = find_eventorid($RankingId);
	
if ($EventorId == 0)
	{
	echo "<p>Could not find Eventor Id for ranking id $RankingId</p></body></html>";
  Trace("<Could not find Eventor Id for ranking id $RankingId");
	exit(0);
	}

if ($RankingId == 0)
	$RankingId = find_rankingid($EventorId);

echo "<p>Eventor Id: $EventorId, Ranking id: $RankingId</p>";
remove_ranking_event($RankingId);

load_clubs_db();
process_1_event($EventorId);
echo "<p>Complete</p></body></html>";

 
function find_eventorid($RankingId)
	{
	global $mysqli;
	global $url;
	
	$sql = "SELECT url FROM `events` WHERE `id` = $RankingId";
	$result = $mysqli->query($sql) or trigger_error(mysqli_error().$sql);
	$row = $result->fetch_assoc();

	if (!array_key_exists("url", $row))
		return 0;
	
	return substr($row["url"], strlen($url));
	}
	
function find_rankingid($EventorId)
	{
	global $mysqli;
	global $url;
	
	$sql = "SELECT id FROM `events` WHERE `url` = \"$url$EventorId\"";
	Trace("executing $sql");
	$result = $mysqli->query($sql) or trigger_error(mysqli_error().$sql);
	$row = $result->fetch_assoc();
	if (!array_key_exists("id", $row))
		return 0;
	
	return $row["id"];
	}

function remove_ranking_event($RankingId)
	{
	global $mysqli;
	if ($RankingId == 0)
		return;
	echo "<p>Deleting event $RankingId</p>";
	$sql = "DELETE from results where eventId = $RankingId";
	$result = $mysqli->query($sql) or trigger_error(mysqli_error().$sql);
	$sql = "DELETE from events where id = $RankingId";
	$result = $mysqli->query($sql) or trigger_error(mysqli_error().$sql);	
	}
	
function have_get_value($key)
	{
	return (array_key_exists($key, $_GET) && isset($_GET[$key]) && $_GET[$key] != "");
	}

function process_1_event($EventorId)
	{
	global $mysqli;
	global $clubs;
	global $results;
	global $courses;
	global $url;

	$clubs = array();
	$results = array(array());
	$courses = array(array());
	Trace("Going to process ".$url.$EventorId);
	process_event($EventorId);
	$sql = "UPDATE `eventorEvents` SET `processed` = 1 WHERE `id` = ".$EventorId;
	$result = $mysqli->query($sql) or trigger_error(mysqli_error().$sql);
	if(!$result)
		{
		die('There was an error running the query [' . mysqli_error().']\n'.$sql);		
		}
	}
?>
