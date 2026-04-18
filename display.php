<?php
// Table handling is from http://www.javascripttoolbox.com/lib/table/examples.php
require_once(__DIR__.'/mysqli_connect.php');
$userAgent = 'Mozilla/5.0 (X11; Linux x86_64; rv:2.0b9pre) Gecko/20110111 Firefox/4.0b9pre';
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta content="text/html; charset=utf-8" http-equiv="content-type">
<title>Big Pink Australian Orienteering Rankings</title>
<script type="text/javascript" src="jscript/table.js"></script> 
<script type="text/javascript" src="jscript/postfilter.js"></script> 
<link rel="stylesheet" href="themes/pink/style.css" type="text/css" id="" media="print, projection, screen" />
<link rel="stylesheet" href="themes/style.css" type="text/css" id="" media="print, projection, screen" />
<script language="javascript">
    function initFiltering(id) {
        var elem = document.getElementById(id);
        if (elem != null && elem.value != null && elem.value != "")
            Table.filter(elem,elem);
    }
    
    function filterUrlText(id) {
        var elem = document.getElementById(id);
       // console.log("Processing " + id + " - " + elem.value);
        if (elem != null && elem.value != null && elem.value != "")
            return "&" + id.substring(0, id.length -6) + "=" + elem.value;
        else    
            return "";
    }
    
    window.onload = function(e) {
        initFiltering("namefilter");
        initFiltering("clubfilter");
        initFiltering("statefilter");
        initFiltering("genderfilter");
        initFiltering("classfilter");
    };    

function copyToClipboard(text) {
    window.prompt("Copy to clipboard: Ctrl+C, Enter", text);
  } 

function getFilterUrl() {
    retVal = "http://ranking.bigfootorienteers.com/display.php?a=1";
    retVal += filterUrlText("namefilter");
    retVal += filterUrlText("clubfilter");
    retVal += filterUrlText("statefilter");
    retVal += filterUrlText("genderfilter");
    retVal += filterUrlText("classfilter");
    return retVal;
}  
</script> 
</head>
<body>


<?php
    include 'Mobile_Detect.php';
    $detect = new Mobile_Detect;    
    $isMobile = $detect->isMobile() && !$detect->isTablet();
    
    
    if (!$isMobile)
        {
        include('./banner.php');
        include('./notes.php');
        }

  $name = in_array('name', $_GET) ? $_GET['name'] : "";
  $club = in_array('club', $_GET) ? $_GET['club'] : "";
  $state = in_array('state', $_GET) ? $_GET['state'] : "";
  $gender = in_array('gender', $_GET) ? $_GET['gender'] : "";
  $class = in_array('class', $_GET) ? $_GET['class'] : "";
    $query = 'SELECT COUNT(*) as countall from runners where current_score > 0';
    $result = $mysqli->query($query) or trigger_error($mysqli->error." ".$query);
    $row = mysqli_fetch_array ($result, MYSQLI_ASSOC);
    $count_all = $row['countall'];
        
    $query = 'SELECT runners.id as id, runners.name as name,clubs.name as club, clubs.shortname as clubshort, clubs.state as state, runners.gender as gender,
    runners.class as class, runners.current_ranking as points
from `clubs`, runners where clubs.id = clubid and current_ranking > 0 and clubs.country = "AUS"
order by runners.current_ranking desc';
    $result = $mysqli->query ($query) or trigger_error($mysqli->error." ".$query);
    if ($isMobile)
        echo '<table id="rankingTable" class="tablesorter" cellspacing="0" cellpadding="2" style="width:auto">';
    else
        echo '<table id="rankingTable" class="tablesorter" cellspacing="0" cellpadding="2">';
    ?>

<thead> 
<tr> 
        <th>Pos</th>
    <th>Name</th> 
    <th class="filterable">Club</th>
    <th class="filterable">State</th>
<?php
    if (!$isMobile)
        echo '<th class="filterable">Gender</th>';
?>      
    <th class="filterable">Class</th>
    <th>Points</th> 
</tr> 
    <tr>
        <th>Filter:</th>
        <th>        
<?php
    echo "<input id='namefilter' name='filter' size='10' onkeyup='Table.filter(this,this)' value='$name'></th>";
    if (!$isMobile)
        echo "<th><input id='clubfilter' name='filter' size='15' onkeyup='Table.filter(this,this)' value='$club'></th>";
    else
        echo "<th><input id='clubfilter' name='filter' size='2' onkeyup='Table.filter(this,this)' value='$club'></th>";

    echo "<th><input id='statefilter' name='filter' size='3' onkeyup='Table.filter(this,this)' value='$state'></th>";

    if (!$isMobile)
        echo "<th><input id='genderfilter' name='filter' size='1' onkeyup='Table.filter(this,this)' value='$gender'></th>";

    echo "<th><input id='classfilter' name='filter' size='6' onkeyup='Table.filter(this,this)' value='$class'></th>";
?>
<th></th>
</thead> 
<?php
    if ($result)
        {
        echo '<tbody>';
        $j = $i = 1;
        $lastpoints = 0;
        $found = mysqli_num_rows($result);
        while ($row = mysqli_fetch_array ($result, MYSQLI_ASSOC))
            {
            if ($row['points'] != $lastpoints)
                $j = $i;
            if ($isMobile)
                echo "<tr><td>$j</td><td><a href=\"../displayrunner.php?id=".$row['id']."\">".$row['name']."</a></td><td>".$row['clubshort']."</td><td>".$row['state']."</td><td>".$row['class']."</td><td>".$row['points']."</td></tr>\r";
            else
                echo "<tr><td>$j</td><td><a href=\"../displayrunner.php?id=".$row['id']."\">".$row['name']."</a></td><td>".$row['club']."</td><td>".$row['state']."</td><td>".$row['gender']."</td><td>".$row['class']."</td><td>".$row['points']."</td></tr>\r";
            $i++;           
            $lastpoints = $row['points'];       
            }
        $missing = $count_all - $found;
        echo "<tr><td></td><td colspan='4'><em>".$missing." further current orienteers omitted - not enough events/points</em></td></tr>";
        echo '</tbody>';
        }
?>
</table>
</body>
</html>
