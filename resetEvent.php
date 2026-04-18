<?php
require_once(__DIR__.'/mysqli_connect.php');
$userAgent = 'Mozilla/5.0 (X11; Linux x86_64; rv:2.0b9pre) Gecko/20110111 Firefox/4.0b9pre';
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta content="text/html; charset=utf-8" http-equiv="content-type">
<title>Australian Orienteering Rankings - RESET AN EVENT</title>
<link rel="stylesheet" href="themes/pink/style.css" type="text/css" id="" media="print, projection, screen" />
<link rel="stylesheet" href="themes/style.css" type="text/css" id="" media="print, projection, screen" />
<script language="JavaScript">
function sendDelete() {
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
			document.getElementsByName("date" + id)[0].removeAttribute("style");
			document.getElementsByName("name" + id)[0].removeAttribute("style");
			document.getElementsByName("url" + id)[0].removeAttribute("style");
			}
		else
			{
			document.getElementsByName("date" + id)[0].style="color:red";
			document.getElementsByName("name" + id)[0].style="color:red";
			document.getElementsByName("url" + id)[0].style="color:red";				
			}
		}
}
</script> 
</head>
<body>

<?php
function do_delete()
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
	$url="";
	$query = "SELECT url from events where id = $eventId";
	$result = $mysqli->query ($query) or trigger_error($mysqli->error." ".$query);
	if ($result !== false) 
	{
		$row = $result->fetch_assoc();
    $url = $row['url'];
	}
	$matches = array();
	if (preg_match('/.*eventId=([0-9]+).*/', $url, $matches) != 1)
		{
		echo "<p style='color:red'>couldn't determine event id from $url</p>";
		var_dump($_POST);
		return;
		}
	else
		{
		$eventorId = $matches[1];
		}
	
	$mysqli->begin_transaction();
	
	// Make variables db compliant
	// Do the update using prepared statement
	$stmt = $mysqli->prepare("DELETE from results where eventid = ?");
	if ($stmt === false) 
	{
		trigger_error($mysqli->error, E_USER_ERROR);
		$mysqli->rollback();
		return;
	}
	$stmt->bind_param('i', $eventId);
	$status = $stmt->execute();
	if ($status === false) 
	{
		trigger_error($stmt->error, E_USER_ERROR);
		$mysqli->rollback();
		return;
	}
	
	$stmt = $mysqli->prepare("DELETE from events where id = ?");
	if ($stmt === false) 
	{
		trigger_error($mysqli->error, E_USER_ERROR);
		$mysqli->rollback();
		return;
	}
	$stmt->bind_param('i', $eventId);
	$status = $stmt->execute();
	if ($status === false) 
	{
		trigger_error($stmt->error, E_USER_ERROR);
		$mysqli->rollback();
		return;
	}
	
	$stmt = $mysqli->prepare("UPDATE eventorEvents set processed = 0 where id = ?");
	if ($stmt === false) 
	{
		trigger_error($mysqli->error, E_USER_ERROR);
		$mysqli->rollback();
		return;
	}
	$stmt->bind_param('i', $eventorId);
	$status = $stmt->execute();
	if ($status === false) 
	{
		trigger_error($stmt->error, E_USER_ERROR);
		$mysqli->rollback();
		return;
	}
	$mysqli->commit();
}

	if ($_SERVER['REQUEST_METHOD'] === 'POST') 
	{
		do_delete();
	}
	
	$query = "SELECT name, url, date, id from events order by date desc limit 40";
	$result = $mysqli->query ($query) or trigger_error($mysqli->error." ".$query);

	if ($result)
	{
	echo'<form onsubmit="return sendDelete()" method=post>';
	echo "\r\n";
	echo '<label for="hash">Password</label>';
	echo '<input type="password" size="16" name="hash">';
	echo '<input type="submit" value="Reset for re-processing"/><br/>';
	echo "\r\n";
	$i = 0;
	while ($row = mysqli_fetch_array ($result, MYSQLI_ASSOC))
		{
		if (!$i)
		{
		$color = "";
		echo '<input type="radio" name="which" value="'.$row["id"].'" checked onclick = "enableCtrls()">';
		}
		else
		{
		$color = 'style="color:red"';
		echo '<input type="radio" name="which" value="'.$row["id"].'" onclick = "enableCtrls()">';
		}
		echo ' <input type="text" size="10" disabled $color name="date'.$row["id"].'"  value="'.$row['date'].'">';
		echo ' <input type="text" size="70" disabled $color name="name'.$row["id"].'"  value="'.$row['name'].'">';
		echo ' <input type="text" size="80" disabled $color name="url'.$row["id"].'"  value="'.$row['url'].'"><a href="'.$row['url'].'"  target="_blank">link</a><br/>';
		echo "\r\n";
		$i++;
		}
	echo '</form>';
	}
?>


</body>
</html>
