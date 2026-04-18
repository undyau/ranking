<?php
require_once(__DIR__.'/mysqli_connect.php');
require_once(__DIR__.'/rebase.php');
require_once(__DIR__.'/rerank.php');

function remove_dups()
{
    $query = "SELECT DISTINCT b.id
FROM events a, events b, results ar, results br
WHERE a.date = b.date
AND ar.eventid = a.id
AND br.eventid = b.id
AND ar.runnerid = br.runnerid
AND a.id < b.id
AND substring( a.url, 1, 10 ) <> substring( b.url, 1, 10 )";


    $result = $mysqli->query ($query) or trigger_error($mysqli->error." ".$query);
    $dups = array();
    if ($result)
        {
        while ($row = mysqli_fetch_array ($result, MYSQLI_ASSOC))
            $dups[] = $row['id'];
        }
    else
        {
        echo "Error reading events and results database tables<br/>";
        return;
        }

        for ($i = 0; $i < sizeof($dups); $i++)
        {
        $query = "delete from results where eventid = ".$dups[$i];
        $result = $mysqli->query ($query) or trigger_error($mysqli->error." ".$query);
        }
        
if ($result)
    {
    do_rerank();
    do_rebase();
    }
}

?>
