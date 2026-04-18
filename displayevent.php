<?php
require_once(__DIR__.'/mysqli_connect.php');
$userAgent = 'Mozilla/5.0 (X11; Linux x86_64; rv:2.0b9pre) Gecko/20110111 Firefox/4.0b9pre';
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta content="text/html; charset=utf-8" http-equiv="content-type">
<title>Australian Orienteering Rankings</title>
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.2.6/jquery.min.js"></script>
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
    if (array_key_exists('id', $_REQUEST) && ctype_digit($_REQUEST['id']))
        $id = $_REQUEST['id'];
    else
        $id = 0;
        
    $query = "SELECT name, url from events where id = $id";
    $result = $mysqli->query ($query) or trigger_error($mysqli->error." ".$query);
    $row = mysqli_fetch_array ($result, MYSQLI_ASSOC);
    echo "<h1><a href=\"".$row['url']."\">".$row['name']."</a></h1>\r\n";
        
    $query = "SELECT runners.name as name, results.class as class, results.points as points, clubs.shortname as clubname, runners.id as runnerid
FROM results, runners, clubs
WHERE results.runnerid = runners.id
AND results.eventid = $id
AND runners.clubid = clubs.id
ORDER BY points DESC";

    $result = $mysqli->query ($query) or trigger_error($mysqli->error." ".$query);
?>
<table id="myTable" class="tablesorter" cellspacing="0" cellpadding="2"> 
<thead> 
<tr> 
    <th>Runner</th>
    <th>Club</th> 
    <th>Class</th> 
    <th>Points</th>
</tr> 
</thead> 
<?php
    if ($result)
        {
        echo '<tbody>';
        $i = 1;
        while ($row = mysqli_fetch_array ($result, MYSQLI_ASSOC))
            {           
            echo "<tr><td>";    
            echo "<a href = \"http://ranking.bigfootorienteers.com/displayrunner.php?id=".$row['runnerid']."\">".$row['name']."</a></td><td>".$row['clubname']."</td><td>".$row['class']."</td><td>".$row['points']."</td>";
            echo "</tr>";
            $i++;               
            }
        echo '</tbody>';
        }
?>
</table>
</body>
</html>
