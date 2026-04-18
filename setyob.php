<?php
$DEBUGME = true;
function set_yobs()
{
	Trace("Skipping set_yobs - use Eventor data instead !");
	return;
	update_yobs();
	/*$result = @mysql_query ("begin work") or trigger_error(mysql_error()."begin work");
	if ($result)
		{
		if (update_yobs())		
			$query = "commit";
		else
			$query = "rollback";
		$result = @mysql_query ($query) or trigger_error(mysql_error().$query);
		}*/
}

function update_yobs()
	{
	$current_year = date("Y");
	$query = "select name, id, yob_ceiling, yob_floor from runners";
	$result = $mysqli->query ($query) or trigger_error($mysqli->error." ".$query);
	if (!$result)
		return false;
	
	$rows = array();
	while ($rows[] = mysqli_fetch_array ($result, MYSQLI_ASSOC))
		;	
	
	foreach ($rows as $row)
		{
		if (($row['yob_floor'] == 0 || $row['yob_ceiling'] == 0  ||
			$row['yob_floor'] != $row['yob_ceiling']) && 
			strlen($row['id']) > 0)
			if (!process_yob($row))
				return false;
		}		
		
	}
	
function process_yob($row)
	{
	global $DEBUGME;
    $query ="select distinct  YEAR(date) as year, class as class
    from events, results
	where results.eventid = events.id and results.runnerid = ". $row['id'];
	$result = $mysqli->query ($query) or trigger_error($mysqli->error." ".$query);
	if (!$result)
		return false;

	$floor = $ceiling = 0;	
	while ($orientResult = mysql_fetch_array($result))
		{
		//Trace("Processing class[".$orientResult['class']."] for ".$row['name'] );
		if (preg_match("/([M|W])([\d]{2,2})[-20]?[A|B|E|S|L]?/", $orientResult['class'], $matches) &&
		    $matches[0] == $orientResult['class'])
			{
			$c = $f = 0;
			if ($matches[2] > 21)
				$f = $orientResult['year'] - $matches[2];
			else if ($matches[2] < 21 && $matches[2] > 10)
				$c = $orientResult['year'] - $matches[2];
			//Trace("Class was ".$orientResult['class']." year ".$orientResult['year']." ceiling $c, floor $f, saved floor $floor");	
			}

		if ($f > 0)
			$floor = $floor == 0 ? $f : min($f, $floor);
		$ceiling = max($c, $ceiling);
		}
	//Trace("Final ceiling $ceiling, floor $floor");
	if ($floor != 0 || $ceiling != 0)
		{
		$query = "update runners set yob_floor = $floor, yob_ceiling = $ceiling where id = ".$row['id'];
		Trace($query);
		$result = $mysqli->query ($query) or trigger_error($mysqli->error." ".$query);
		if (!$result)
			return false;
		}

	return true;
}

?>

