<?php
/*

This is incomplete !!

*/


require_once(__DIR__.'/mysqli_connect.php');
require_once(__DIR__.'/trace.php');
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta content="text/html; charset=utf-8" http-equiv="content-type">
<title>Australian Orienteering Rankings</title>
<link rel="stylesheet" href="themes/pink/style.css" type="text/css" id="" media="print, projection, screen" />
<link rel="stylesheet" href="themes/style.css" type="text/css" id="" media="print, projection, screen" />
<script language="JavaScript">
function sendChange() {
    return true;
}

function getCheckedValue( groupName ) {
    var radios = document.getElementsByName( groupName );
    for( i = 0; i < radios.length; i++ ) {
        if( radios[i].checked ) 
            {
            return radios[i].value;
            }
    }
    return null;
}

function enableCtrls( ) {
    var radios = document.getElementsByName( "which" );
    for( i = 0; i < radios.length; i++ ) 
        {
        var ids = radios[i].value.split('-');
        if (!radios[i].checked)
            {
            document.getElementsByName("runner1-" + ids[0])[0].disabled=true;
            document.getElementsByName("runner2-" + ids[1])[0].disabled=true;
            }
        else
            {
            document.getElementsByName("runner1-" + ids[0])[0].removeAttribute("disabled");
            document.getElementsByName("runner2-" + ids[1])[0].removeAttribute("disabled");
            }
        }
}
</script> 
</head>
<body>

<?php
function do_update()
{
    global $mysqli;
    
    // Check Password
    if (!isset($_POST['hash']) )
    {
        echo '<p style="color:red">missing password</p>';
        return;
    }
    $query = "SELECT value from control where name = 'maintenance pw'";
    $result = $mysqli->query ($query) or trigger_error($mysqli->error." ".$query);
    if ($result !== false) 
    {
        $row = $result->fetch_assoc();
        $hash = $row['value'];
        if ($hash != md5($_POST['hash']))
            {
                echo "<p style='color:red'>invalid password</p>";
                return;
            }
    }
    $which = $_POST['which'];
    $runners = explode("-", $which);
    $count = 0;
    if (array_key_exists("runner1-".$runners[0], $_POST))        
        $count++;
    if (array_key_exists("runner2-".$runners[1], $_POST))
        $count++;
    if ($count != 1)
    {
        echo "<p style='color:red'>Select one and only one runner not $count</p>";
        return;
    }
    if (array_key_exists("runner1-".$runners[0], $_POST))
    {
        $from = $runners[1];
        $to = $runners[0];
    }
    else
    {
        $from = $runners[0];
        $to = $runners[1];
    }


    $query = "UPDATE results set runnerid = ".$to." where runnerid = ".$from;
    $result = $mysqli->query ($query) or trigger_error($mysqli->error." ".$query);
    $query = "DELETE from runners where id = ". $from;
    $result = $mysqli->query ($query) or trigger_error($mysqli->error." ".$query);
}
    if ($_SERVER['REQUEST_METHOD'] === 'POST') 
    {
        do_update();
    }
    
    $query = "SELECT a.name, a.id, a.current_ranking, b.id, b.current_ranking, ca.name, cb.name from runners a,runners b, clubs ca, clubs cb where a.name = b.name and a.id < b.id and a.current_ranking > 0 and b.current_ranking > 0 and a.clubid = ca.id and b.clubid = cb.id order by a.name asc limit 50";
    
    /*
    SELECT a.name, a.id, ca.name, a.current_ranking, b.id, cb.name, b.current_ranking from runners a,runners b, clubs ca, clubs cb where a.name = b.name and a.id < b.id and a.current_ranking >= 0 and b.current_ranking >= 0 and (a.current_ranking > 0 or b.current_ranking > 0) and a.clubid = ca.id and b.clubid = cb.id order by a.name asc limit 150;
    */
    $result = $mysqli->query ($query) or trigger_error($mysqli->error." ".$query);

    if ($result)
    {
    echo'<form onsubmit="return sendChange()" method=post>';
    echo "\r\n";
    echo '<label for="hash">Password</label>';
    echo '<input type="password" size="16" name="hash"><br/>';
    echo "\r\n";
    $i = 0;
    echo "<table>\n";
    while ($row = mysqli_fetch_array ($result))
        {
        echo ("<tr>\n");
        if (!$i)
        {
            $enabled = "";
            echo '<td><input type="radio" name="which" value="'.$row[1].'-',$row[3].'" checked onclick = "enableCtrls()"></td>';
        }
        else
        {
            $enabled = "disabled";
            echo '<td><input type="radio" name="which" value="'.$row[1].'-'.$row[3].'" onclick = "enableCtrls()"></td>';
        }
        echo "<td>".$row[0]."</td>\n";
        echo "<td><a href='https://ranking.bigfootorienteers.com/displayrunner.php?id=".$row[1]."'  target='_blank'>".$row[5]."</a></td>\n";
        echo "<td>".$row[2]."</td>\n";
        echo "<td><a href='https://ranking.bigfootorienteers.com/displayrunner.php?id=".$row[3]."'  target='_blank'>".$row[6]."</a></td>\n";
        echo "<td>".$row[4]."</td>\n";        
        echo '<td><input type="checkbox" '.$enabled.' name="runner1-'.$row[1].'" value="'.$row[1].'">club 1</td>';   
        echo '<td><input type="checkbox" '.$enabled.' name="runner2-'.$row[3].'" value="'.$row[3].'">club 2</td>';          
//        echo "\r\n";
        $i++;
        echo "</tr>\n";
        }
    echo "</table>";
    echo '<input type="submit" value="Submit"/>';
    echo '</form>';
    }
?>


</body>
</html>
