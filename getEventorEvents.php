<?php
require_once(__DIR__.'/mysqli_connect.php');
require_once(__DIR__.'/trace.php');

//$url = "http://eventor.orienteering.asn.au/Events?organisations=9,4,5,7,8,10,6&mode=List&map=false";
$url = "https://eventor.orienteering.asn.au/Events?organisations=2&mode=List&map=false";
$DEBUGME = true;

$html = file_get_contents($url);
save_all_finished($html);

function save_all_finished($html)
{
    global $mysqli;
    $parts = explode('<span><a href="/Events/ResultList?eventId=', $html);
    Trace("There are ".count($parts)." parts to check");
    Trace("The HTML is ".strlen($html)." long");
    foreach ($parts as $part)
        {
        $matches = array();
        if (preg_match('/([0-9]+)"><img alt="Results"/', $part, $matches))
            {
            $id = $matches[1];
            $query = "insert ignore into eventorEvents set id = $id, processed = false";
            $result = $mysqli->query($query) or   trigger_error($mysqli->error." ".$query);
            }
        }
}
