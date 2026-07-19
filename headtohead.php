<?php
require_once(__DIR__.'/mysqli_connect.php');
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
       .tablesorter({sortList: [[0,1]], dateFormat: "uk", widgets: ['zebra']});
    }
);</script>
</head>
<body>
<?php
    include('./banner.php');
?>

<?php
    $id = $_REQUEST['id'];
    if (!ctype_digit($id))
        $id = 0;
    $opponent = $_REQUEST['opponent'];
    if (!ctype_digit($opponent))
        $opponent = 0;

    $query = "SELECT id, name FROM runners WHERE id IN ($id, $opponent)";
    $result = $mysqli->query($query) or trigger_error($mysqli->error." ".$query);
    $runners = [];
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
        $runners[$row['id']] = $row['name'];

    $my_name  = isset($runners[$id])       ? $runners[$id]       : 'Unknown';
    $opp_name = isset($runners[$opponent]) ? $runners[$opponent] : 'Unknown';

    echo "<div id=\"RunnerLabel\"><a href=\"matchups.php?id=$id\">".htmlspecialchars($my_name)."</a> vs <em>".htmlspecialchars($opp_name)."</em></div>";

    $query = "SELECT
        COUNT(*) as races,
        SUM(res1.points > res2.points) as wins,
        SUM(res1.points < res2.points) as losses,
        SUM(res1.points = res2.points) as ties
    FROM results res1
    JOIN results res2 ON res1.eventid = res2.eventid AND res1.class = res2.class
    WHERE res1.runnerid = $id AND res2.runnerid = $opponent";
    $result = $mysqli->query($query) or trigger_error($mysqli->error." ".$query);
    $summary = mysqli_fetch_array($result, MYSQLI_ASSOC);
    $winpct = $summary['races'] > 0 ? round(100 * $summary['wins'] / $summary['races']) : 0;
    echo "<p style=\"text-align:center;font-family:'Inter',Arial,sans-serif;font-size:14px;margin:10px\">";
    echo $summary['wins']." wins / ".$summary['losses']." losses / ".$summary['ties']." ties &mdash; ".$winpct."% win rate";
    echo "</p>";

    $query = "SELECT e.id as eventid, e.name as event_name, e.url as event_url,
        DATE_FORMAT(e.date, '%e/%c/%Y') as date,
        res1.points as my_points, res2.points as opp_points, res1.class as class
    FROM results res1
    JOIN results res2 ON res1.eventid = res2.eventid AND res1.class = res2.class
    JOIN events e ON res1.eventid = e.id
    WHERE res1.runnerid = $id AND res2.runnerid = $opponent
    ORDER BY e.date DESC";
    $result = $mysqli->query($query) or trigger_error($mysqli->error." ".$query);
?>
<table id="myTable" class="tablesorter" cellspacing="0" cellpadding="2">
<thead>
<tr>
    <th>Date</th>
    <th>Event</th>
    <th>Class</th>
    <th>Pts Diff</th>
</tr>
</thead>
<?php
    if ($result)
        {
        echo '<tbody>';
        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
            {
            $diff = $row['my_points'] - $row['opp_points'];
            $sign = $diff > 0 ? '+' : '';
            echo "<tr>";
            echo "<td>".$row['date']."</td>";
            echo "<td><a href=\"".$row['event_url']."\">".$row['event_name']."</a>  <a class=\"PointsLink\" href=\"displayevent.php?id=".$row['eventid']."\">points</a></td>";
            echo "<td>".$row['class']."</td>";
            echo "<td>".($diff > 0 ? "<em>" : "").$sign.$diff.($diff > 0 ? "</em>" : "")."</td>";
            echo "</tr>";
            }
        echo '</tbody>';
        }
?>
</table>
</body>
</html>
