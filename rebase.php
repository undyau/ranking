<?php
require_once(__DIR__.'/mysqli_connect.php');

function do_rebase()
{ 
global $mysqli;
$query = "select avg(current_score), std(current_score) from runners where current_score > 0";
$result = $mysqli->query($query) or trigger_error($mysqli->error." ".$query);
if ($result)
    {
    $row = mysqli_fetch_row($result);
    $avg = $row[0];
    $std = $row[1];
    $result = $mysqli->query ("begin work") or trigger_error($mysqli->error." "."begin work");
    if ($result)
        {
        $query = do_updates($avg,$std) ? "commit" : "rollback";
        $result = $mysqli->query ($query) or trigger_error($mysqli->error." ".$query);
        }
    }
}

function do_updates($avg, $std)
{
  global $mysqli;
    $query = "update results set points = (((points - $avg)/$std)*200)+1000";
    $result = $mysqli->query ($query) or trigger_error($mysqli->error." ".$query);
    if (!$result)
        return false;
    
    $query = "update runners set current_score = (((current_score - $avg)/$std)*200)+1000 where current_score > 0";
    $result = $mysqli->query ($query) or trigger_error($mysqli->error." ".$query);
    if (!$result)
        return false;   

    return true;
}

?>

