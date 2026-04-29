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
    for( i = 0; i < radios.length; i++ )
        {
        var id = radios[i].value;
        if (!radios[i].checked)
            {
            document.getElementsByName("date" + id)[0].disabled=true;
            document.getElementsByName("url" + id)[0].disabled=true;
            }
        else
            {
            document.getElementsByName("date" + id)[0].removeAttribute("disabled");
            document.getElementsByName("url" + id)[0].removeAttribute("disabled");
            }
        }
}

function selectRow( id ) {
    var radios = document.getElementsByName( "which" );
    for( i = 0; i < radios.length; i++ )
        {
        if( radios[i].value == id )
            {
            radios[i].checked = true;
            break;
            }
        }
    enableCtrls();
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
    
    if ($sprint === '1' || $sprint === '0')
    {
        $sprintVal = (int)$sprint;
        $query = "UPDATE results SET sprint = $sprintVal where eventid = $eventId";
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
function do_delete()
{
    global $mysqli;

    if (!isset($_POST['hash']))
    {
        echo '<p style="color:red">missing password</p>';
        return;
    }
    $query = "SELECT value from control where name = 'maintenance pw'";
    $result = $mysqli->query($query) or trigger_error($mysqli->error." ".$query);
    if ($result !== false)
    {
        $row = $result->fetch_assoc();
        if ($row['value'] != md5($_POST['hash']))
        {
            echo "<p style='color:red'>invalid password</p>";
            return;
        }
    }

    $eventId = mysqli_real_escape_string($mysqli, $_POST['delete']);
    if (!ctype_digit($eventId))
        return;

    $mysqli->query("DELETE FROM results WHERE eventid = $eventId")
        or trigger_error($mysqli->error, E_USER_ERROR);
    $mysqli->query("DELETE FROM events WHERE id = $eventId")
        or trigger_error($mysqli->error, E_USER_ERROR);

    echo "<p style='color:green'>Deleted event $eventId</p>";
}

    if ($_SERVER['REQUEST_METHOD'] === 'POST')
    {
        if (isset($_POST['delete']))
            do_delete();
        else
            do_update();
    }
    
    $nameSearch   = isset($_GET['name_search'])   ? $_GET['name_search']   : '';
    $sprintFilter = isset($_GET['sprint_filter']) ? $_GET['sprint_filter'] : '';

    $whereClause  = '';
    $havingClause = '';
    if ($nameSearch !== '') {
        $safeSearch  = mysqli_real_escape_string($mysqli, $nameSearch);
        $whereClause = "WHERE e.name LIKE '$safeSearch'";
    }
    switch ($sprintFilter) {
        case '1':     $havingClause = "HAVING MIN(r.sprint) = 1 AND MAX(r.sprint) = 1"; break;
        case '0':     $havingClause = "HAVING MIN(r.sprint) = 0 AND MAX(r.sprint) = 0"; break;
        case 'mixed': $havingClause = "HAVING MIN(r.sprint) IS NOT NULL AND MIN(r.sprint) != MAX(r.sprint)"; break;
    }

    $query = "SELECT e.name, e.url, e.date, e.id, MIN(r.sprint) as min_sprint, MAX(r.sprint) as max_sprint
              FROM events e LEFT JOIN results r ON r.eventid = e.id
              $whereClause
              GROUP BY e.id, e.name, e.url, e.date $havingClause ORDER BY e.date DESC LIMIT 100";
    $result = $mysqli->query ($query) or trigger_error($mysqli->error." ".$query);

    if ($result)
    {
    echo '<form method="get">';
    echo '<input type="text" size="40" name="name_search" placeholder="Name pattern (use % as wildcard)" value="'.htmlspecialchars($nameSearch).'">';
    echo ' <select name="sprint_filter">';
    echo '<option value=""'     .($sprintFilter===''     ?' selected':'').'>Any</option>';
    echo '<option value="1"'    .($sprintFilter==='1'    ?' selected':'').'>Sprint</option>';
    echo '<option value="0"'    .($sprintFilter==='0'    ?' selected':'').'>Not sprint</option>';
    echo '<option value="mixed"'.($sprintFilter==='mixed'?' selected':'').'>Mixed</option>';
    echo '</select>';
    echo ' <input type="submit" value="Search"/>';
    echo '</form>';
    echo "\r\n";
    echo'<form onsubmit="return sendChange()" method=post>';
    echo "\r\n";
    echo '<label for="hash">Password</label>';
    echo '<input type="password" size="16" name="hash">';
    echo ' <input type="submit" value="Submit"/><br/>';
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
        echo ' <input type="text" size="70" name="name'.$row["id"].'" value="'.$row['name'].'" onclick="selectRow('.$row["id"].')">';
        echo ' <input type="text" size="80" '.$enabled.' name="url'.$row["id"].'" value="'.$row['url'].'">';
        if ($row['min_sprint'] !== null && $row['min_sprint'] == $row['max_sprint'])
            $sprintSel = (string)(int)$row['min_sprint'];
        else
            $sprintSel = '';
        echo ' <select name="sprint'.$row["id"].'" onclick="selectRow('.$row["id"].')">';
        echo '<option value=""'.($sprintSel===''?' selected':'').'>mixed</option>';
        echo '<option value="1"'.($sprintSel==='1'?' selected':'').'>sprint</option>';
        echo '<option value="0"'.($sprintSel==='0'?' selected':'').'>not sprint</option>';
        echo '</select>';
        echo ' <a href="'.$row['url'].'"  target="_blank">link</a>';
        echo ' <button type="submit" name="delete" value="'.$row["id"].'" onclick="return confirm(\'Delete event and all its results?\')">Delete</button>';
        echo '<br/>';
        echo "\r\n";
        $i++;
        }
    echo '<input type="submit" value="Submit"/>';
    echo '</form>';
    }
?>


</body>
</html>
