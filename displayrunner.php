<?php
require_once(__DIR__.'/mysqli_connect.php');
require_once(__DIR__.'/trace.php');
$userAgent = 'Mozilla/5.0 (X11; Linux x86_64; rv:2.0b9pre) Gecko/20110111 Firefox/4.0b9pre';
?>

<!DOCTYPE html>
<html>
<head>
<meta content="text/html; charset=utf-8" http-equiv="content-type">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<title>Big Pink Australian Orienteering Rankings</title>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.2.6/jquery.min.js"></script>
<script type="text/javascript" src="jscript/tablesorter/jquery.tablesorter.js"></script> 
<link rel="stylesheet" href="themes/pink/style.css" type="text/css" id="" media="print, projection, screen" />
<link rel="stylesheet" href="themes/style.css" type="text/css" id="" media="print, projection, screen" />
<script type="text/javascript" id="js">$(document).ready(function() 
    { 
    $table=$("#myTable") 
       .tablesorter({sortList: [[0,0], [1,0]], widgets: ['zebra']});
    }
});</script>

 
</head>
<body>
<?php
    include('./banner.php');
?>

<?php
    $id = $_REQUEST['id'];
    if (!ctype_digit($id))
        $id = 0;
    if ($id == 153366)  // Lyra Simpson
        $DEBUGME = true;
    $query = "select max(date) from events";
    $result = $mysqli->query ($query) or trigger_error($mysqli->error." ".$query);
    if (!$result)
        return false;
    $daterow = mysqli_fetch_row($result);
    $latest_date = $daterow[0];     
    $query = "SELECT runners.name as name,clubs.name as club,clubs.state as state,runners.gender as gender, runners.current_ranking as points
    from `clubs`, runners where clubs.id = clubid and runners.id = $id";
    $result = $mysqli->query ($query) or trigger_error($mysqli->error." ".$query);
    if ($result)
        {
        $row = mysqli_fetch_array ($result, MYSQLI_ASSOC);
        echo "<div id=\"RunnerLabel\"><em>".$row['name']."</em>    (".$row['club'].")  <em>".$row['points']."</em>  <a href=\"runnerchart.php?id=$id\">history</a></div>";
        }
    
    $query = "SELECT results.points as points, DATE_FORMAT(events.date, '%e/%c/%Y') as date, events.name as event, events.url as url, results.class as class, events.id as eventid, results.sprint as sprint    
    from results, events 
    where results.eventid = events.id and results.runnerid = $id
    and datediff('".$latest_date."', events.date) <= 366
    order by points desc";
    $result = $mysqli->query ($query) or trigger_error($mysqli->error." ".$query);
?>
<table id="myTable" class="tablesorter" cellspacing="0" cellpadding="2"> 
<thead> 
<tr> 
    <th>Points</th>
    <th>Date</th> 
    <th>Event</th>
    <th>Class</th>
</tr> 
</thead> 
<?php
    if ($result) 
        {
        echo '<tbody>';
        $counted = 0;
        $sprints = 0;
        $lastpoints = 0;
        while ($row = mysqli_fetch_array ($result, MYSQLI_ASSOC))
            {   
            echo "<tr>";                
            echo "<td>";

            $count_this = false;
            if ($row['sprint'] == 1)
                {
                $sprints++;
                if ($sprints <= 2 && $counted < 6)
                    $count_this = true;
                }
            else
                {
                if ($counted < 6)
                    $count_this = true;
                }

            if ($count_this) echo "<em>";
            echo $row['points'];
            if ($count_this) echo "</em>";
            echo "</td><td>";
            if ($count_this) echo "<em>";
            echo $row['date'];
            echo "</td>";
            if ($count_this) echo "</em>";
            echo "<td><a href=\"".$row['url']."\">".$row['event']."</a>  <a class=\"PointsLink\" href=\"displayevent.php?id=".$row['eventid']."\">points</a></td><td>".$row['class']."</td>\n";
            
            echo "</tr>";
            if ($count_this)
                    $counted++;
            }
        echo '</tbody>';
        }
?>
</table>
</body>
</html>
