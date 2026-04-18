<?php
require_once(__DIR__.'/mysqli_connect.php');
$userAgent = 'Mozilla/5.0 (X11; Linux x86_64; rv:2.0b9pre) Gecko/20110111 Firefox/4.0b9pre';
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
    var id = getCheckedValue("which");
    for( i = 0; i < radios.length; i++ ) 
        {
        var id = radios[i].value;
        if (!radios[i].checked)
            {
            document.getElementsByName("date" + id)[0].disabled=true;
            document.getElementsByName("name" + id)[0].disabled=true;
            document.getElementsByName("url" + id)[0].disabled=true;
            document.getElementsByName("sprint" + id)[0].disabled=true;
            }
        else
            {
            document.getElementsByName("date" + id)[0].removeAttribute("disabled");
            document.getElementsByName("name" + id)[0].removeAttribute("disabled");
            document.getElementsByName("url" + id)[0].removeAttribute("disabled");
            document.getElementsByName("sprint" + id)[0].removeAttribute("disabled");
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

    // Make variables safe
    $eventId = mysqli_real_escape_string($mysqli, $_POST['which']);
    if (!ctype_digit($eventId))
        return;
    $name = mysqli_real_escape_string($mysqli, $_POST["name".$eventId]);
    $date = mysqli_real_escape_string($mysqli, $_POST["date".$eventId]);
    $url = mysqli_real_escape_string($mysqli, $_POST["url".$eventId]);        
    $sprint = mysqli_real_escape_string($mysqli, $_POST["sprint".$eventId]);
    
    if ($sprint == 1)
    {
        $query = "UPDATE results SET sprint = 1 where eventid = $eventId";
        $result = $mysqli->query ($query) or trigger_error($mysqli->error." ".$query);
        if ($result == false) 
        {
            echo "<p style='color:red'>error updating sprint status</p>";
            return;
        }        
    }
    
    // Make variables db compliant
    // Do the update using prepared statement
    $stmt = $mysqli->prepare("UPDATE events SET name=?, date = ?, url = ? WHERE id=?");
    if ($stmt === false) 
    {
        trigger_error($mysqli->error, E_USER_ERROR);
        return;
    }

/* Bind our params */
    $stmt->bind_param('sssi', $name,  $date,  $url, $eventId);

/* Execute the prepared Statement */
    $status = $stmt->execute();
    if ($status === false) 
    {
        trigger_error($stmt->error, E_USER_ERROR);
        return;
    }
    else
    {
        echo "<p style='color:green'>Updated $name</p>";
        echo "\r\n";
    }
    
}
    if ($_SERVER['REQUEST_METHOD'] === 'POST') 
    {
        do_update();
    }
    
    $query = "SELECT name, url, date, id from events order by date desc limit 150";
    $result = $mysqli->query ($query) or trigger_error($mysqli->error." ".$query);

    if ($result)
    {
    echo'<form onsubmit="return sendChange()" method=post>';
    echo "\r\n";
    echo '<label for="hash">Password</label>';
    echo '<input type="password" size="16" name="hash"><br/>';
    echo "\r\n";
    $i = 0;
    while ($row = mysqli_fetch_array ($result, MYSQLI_ASSOC))
        {
        if (!$i)
        {
            $enabled = "";
            echo '<input type="radio" name="which" value="'.$row["id"].'" checked onclick = "enableCtrls()">';
        }
        else
        {
            $enabled = "disabled";
            echo '<input type="radio" name="which" value="'.$row["id"].'" onclick = "enableCtrls()">';
        }
        echo ' <input type="text" size="10" '.$enabled.' name="date'.$row["id"].'" value="'.$row['date'].'">';
        echo ' <input type="text" size="70" '.$enabled.' name="name'.$row["id"].'" value="'.$row['name'].'">';        
        echo ' <input type="text" size="80" '.$enabled.' name="url'.$row["id"].'" value="'.$row['url'].'">';
        echo ' <input type="checkbox" '.$enabled.' name="sprint'.$row["id"].'">'; 
        echo '<a href="'.$row['url'].'"  target="_blank">link</a><br/>';
        echo "\r\n";
        $i++;
        }
    echo '<input type="submit" value="Submit"/>';
    echo '</form>';
    }
?>


</body>
</html>
