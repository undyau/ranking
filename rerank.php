<?php
require_once(__DIR__.'/mysqli_connect.php');
require_once(__DIR__.'/trace.php');

global $DEBUGME;
$DEBUGME = false;
do_rerank();
function do_rerank()
{
  global $mysqli;
    
    $result = $mysqli->query("begin work") or trigger_error($mysqli->error." "."begin work");
    if ($result)
        {
        if (calculate_current_scores())     
            {   
            $query = calculate_current_ranking() ? "commit" : "rollback";
            $result = $mysqli->query($query) or trigger_error($mysqli->error." ".$query);
            }
        else    
            {
            $query = "rollback";
            $result = $mysqli->query($query) or trigger_error($mysqli->error." ".$query);
            }
        }

}

function calculate_current_scores()
    {
  global $mysqli;
    // Average of all scores in last period (should be year)
    $query = "select max(date) from events";
    $result = $mysqli->query($query) or trigger_error($mysqli->error." ".$query);
    if (!$result)
        return false;
    $row = mysqli_fetch_array($result);
    global $lastEventDate;
    $lastEventDate = $row[0];
    
    $query = "update runners set current_score = (select avg(points)
                from results, events
                where runners.id = runnerid AND
                eventid = events.id and 
                points > 0 AND
                datediff('".$lastEventDate."', events.date) <= 366)";

    $result = $mysqli->query($query) or trigger_error($mysqli->error." ".$query);
    if (!$result)
        return false;
        
    $query = "update runners set current_sd = (select std(points)
                from results, events
                where runners.id = runnerid AND
                eventid = events.id and 
                points > 0 AND
                datediff('".$lastEventDate."', events.date) <= 366)";

    $result = $mysqli->query($query) or trigger_error($mysqli->error." ".$query);
    if (!$result)
        return false;       
        
    $query = "update runners set best_score = (select max(points)
                from results, events
                where runners.id = runnerid AND
                eventid = events.id and 
                points > 0 AND
                datediff('".$lastEventDate."', events.date) <= 366)";

    $result = $mysqli->query($query) or trigger_error($mysqli->error." ".$query);
    if (!$result)
        return false;               
    return true;
    }

function calculate_current_ranking()
    {
    global $lastEventDate;
  global $mysqli;
    
    // Sum of top 6 scores or 0 if not >5 scores in period

    $query = "update runners set current_ranking = 0";  
    $result = $mysqli->query($query) or trigger_error($mysqli->error." ".$query);
    if (!$result)
        return false;
        
    $query = 
    "select runnerid as runner_id, count(*) as event_count
        from results
        where eventid in
        (select id from events where datediff('".$lastEventDate."', events.date) <= 366)
        group by runnerid";
    
    $result = $mysqli->query($query) or trigger_error($mysqli->error." ".$query);
    if (!$result)
        return false;

    while ($row = mysqli_fetch_array ($result, MYSQLI_ASSOC))
        {
        if ($row['event_count'] > 6)
            if (!process_individual($row['runner_id']))
                return false;
        }   

    return true;
}

function process_individual($id)
{
    global $lastEventDate;
  global $mysqli;
    
    if (strlen($id) == 0)
        return true;
    
/*  $query = 
    "select sum(points) as current_ranking from 
        (select points from results
        where runnerid = $id and 
        eventid in
        (select id from events where datediff('".$lastEventDate."', events.date) <= 366) 
        order by points desc limit 6) as runnerpoints";*/
    
// Only allow two sprint events 
    $query = "select sum(points) as current_ranking from        
    (select points from (       
    (select points, eventid from results
            where runnerid = $id and sprint = 0
            and eventid in
            (select id from events where datediff('".$lastEventDate."', events.date) <= 366) 
            order by points desc limit 6) 
    union
    (select points, eventid from results
            where runnerid = $id and sprint = 1 
            and eventid in
            (select id from events where datediff('".$lastEventDate."', events.date) <= 366) 
            order by points desc limit 2))  as a order by points desc   limit 6) 
            as runnerpoints";       
/*  
    // Query above should work but PHP spits the dummy, so do this the long way
    $query = "select points, eventid, sprint from results
            where runnerid = $id 
            and eventid in
            (select id from events where datediff('".$lastEventDate."', events.date) <= 366) 
            order by points desc";
    
    $result = $mysqli->query($query) or trigger_error($mysqli->error." ".$query);
    if (!$result)
        return false;   
    
    // For each row
    $sprints = 0;
    $counted = 0;
    $current_ranking;
    while ($row = mysqli_fetch_array ($result, MYSQLI_ASSOC) && $counted < 6) 
        {
        if ($row['sprint'] == 1 && $sprints < 2)
            {
            $current_ranking += $row['points'];
            $sprints++;
            $counted++;
            }
        elseif ($row['sprint'] == 0)
            {
            $current_ranking += $row['points'];
            $sprints++;
            $counted++;
            }
        }
    if ($counted < 6)
        $current_ranking = 0;*/
    $result = $mysqli->query($query) or trigger_error($mysqli->error." ".$query);
    if (!$result)
        return false;
    $row = mysqli_fetch_array ($result, MYSQLI_ASSOC);  
    $current_ranking = round($row['current_ranking']);  
    $query = "update runners set current_ranking = $current_ranking  where id = $id";
    
    $result = $mysqli->query($query) or trigger_error($mysqli->error." ".$query);
    if (!$result)
        return false;   
    return true;
}
?>

