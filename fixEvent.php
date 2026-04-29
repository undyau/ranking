<?php
require_once(__DIR__.'/mysqli_connect.php');
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
<title>Big Pink Rankings — Fix Event</title>
<link rel="stylesheet" href="themes/pink/style.css" type="text/css" id="" media="print, projection, screen" />
<link rel="stylesheet" href="themes/style.css" type="text/css" id="" media="print, projection, screen" />
<style>
body {
    font-family: 'Inter', Arial, sans-serif;
    background: #f9f4f7;
    margin: 0;
    padding: 0;
    font-size: 13px;
    color: #333;
}
.page-header {
    background: linear-gradient(135deg, #C8689A 0%, #A04070 100%);
    color: #fff;
    padding: 14px 20px;
    font-size: 20px;
    font-weight: 700;
    letter-spacing: -0.3px;
    margin-bottom: 20px;
    border-radius: 0 0 8px 8px;
}
.page-content { padding: 0 20px 20px; }
.form-bar {
    background: #fff;
    border: 1px solid #E0C0D4;
    border-radius: 8px;
    padding: 12px 15px;
    margin-bottom: 12px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.06);
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.events-form { background: #fff; border: 1px solid #E0C0D4; border-radius: 8px; padding: 12px 15px; box-shadow: 0 2px 6px rgba(0,0,0,0.06); }
input[type="text"], input[type="password"] {
    border: 1px solid #E0C0D4;
    border-radius: 4px;
    padding: 5px 8px;
    font-family: 'Inter', Arial, sans-serif;
    font-size: 13px;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
    background: #fff;
}
input[type="text"]:focus, input[type="password"]:focus {
    border-color: #C8689A;
    box-shadow: 0 0 0 3px rgba(200,104,154,0.18);
}
input[type="text"]:disabled { background: #f5f5f5; color: #aaa; border-color: #e8e8e8; }
select {
    border: 1px solid #E0C0D4;
    border-radius: 4px;
    padding: 5px 8px;
    font-family: 'Inter', Arial, sans-serif;
    font-size: 13px;
    background: #fff;
}
input[type="submit"] {
    background: #C8689A;
    color: #fff;
    border: none;
    border-radius: 4px;
    padding: 6px 16px;
    font-family: 'Inter', Arial, sans-serif;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}
input[type="submit"]:hover { background: #A04070; }
button[name="delete"] {
    background: #dc3545;
    color: #fff;
    border: none;
    border-radius: 4px;
    padding: 4px 10px;
    font-family: 'Inter', Arial, sans-serif;
    font-size: 12px;
    cursor: pointer;
    transition: background 0.2s;
}
button[name="delete"]:hover { background: #b02a37; }
label { font-weight: 600; }
p.success { color: #28a745; font-weight: 600; }
p.error   { color: #dc3545; font-weight: 600; }
</style>
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
<div class="page-header">Big Pink Rankings &mdash; Fix Event</div>
<div class="page-content">
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
    echo '<div class="form-bar">';
    echo '<form method="get" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">';
    echo '<input type="text" size="40" name="name_search" placeholder="Name pattern (use % as wildcard)" value="'.htmlspecialchars($nameSearch).'">';
    echo '<select name="sprint_filter">';
    echo '<option value=""'     .($sprintFilter===''     ?' selected':'').'>Any</option>';
    echo '<option value="1"'    .($sprintFilter==='1'    ?' selected':'').'>Sprint</option>';
    echo '<option value="0"'    .($sprintFilter==='0'    ?' selected':'').'>Not sprint</option>';
    echo '<option value="mixed"'.($sprintFilter==='mixed'?' selected':'').'>Mixed</option>';
    echo '</select>';
    echo '<input type="submit" value="Search"/>';
    echo '</form>';
    echo '</div>';
    echo "\r\n";
    echo '<div class="events-form">';
    echo'<form onsubmit="return sendChange()" method=post>';
    echo "\r\n";
    echo '<div class="form-bar" style="margin-bottom:10px">';
    echo '<label for="hash">Password</label>';
    echo '<input type="password" size="16" name="hash">';
    echo '<input type="submit" value="Submit"/>';
    echo '</div>';
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
    echo '</div>';
    }
?>
</div>


</body>
</html>
