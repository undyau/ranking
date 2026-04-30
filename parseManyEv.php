<?php
require_once(__DIR__.'/mysqli_connect.php');
require_once(__DIR__.'/processEvEvent.php');
require_once(__DIR__.'/setclass.php');
$url = "https://eventor.orienteering.asn.au/Events/ResultList?eventId=";
$userAgent = 'Mozilla/5.0 (X11; Linux x86_64; rv:2.0b9pre) Gecko/20110111 Firefox/4.0b9pre';

$clubs = array();
$results = array(array());
$courses = array(array());
$allCoursesSame = false;

load_clubs_db();
process_events();
//set_classes();


function process_events()
    {
    global $mysqli;
    global $clubs;
    global $results;
    global $courses;
    global $url;

    $sql = "SELECT id, raceid FROM `eventorEvents` WHERE `processed` = 0";

    $result = $mysqli->query($sql) or trigger_error(mysqli_error().$sql);

    while($row = $result->fetch_assoc())
        {
        $ids[]     = $row['id'];
        $raceids[] = $row['raceid'];
        }
    $count = $result->num_rows;
    $result->free();

    for ($i = 0; $i < $count ; $i++)
        {
        $clubs = array();
        $results = array(array());
        $courses = array(array());
        $raceStr = $raceids[$i] > 0 ? "&eventRaceId=".$raceids[$i] : "";
        Trace("Going to process $url".$ids[$i].$raceStr);
        if (process_event($ids[$i], $raceids[$i]))
            {
            $sql = "UPDATE `eventorEvents` SET `processed` = 1 WHERE `id` = ".$ids[$i]." AND `raceid` = ".$raceids[$i];
            $result = $mysqli->query($sql) or trigger_error(mysqli_error().$sql);
            if(!$result)
                {
                die('There was an error running the query [' . mysqli_error().']\n'.$sql);
                }
            }
        }
    }       

?>
