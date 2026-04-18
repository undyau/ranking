<?php
require_once(__DIR__.'/mysqli_connect.php');
require_once(__DIR__.'/rebase.php');
require_once(__DIR__.'/rerank.php');

$aliasdb = array(array());

function fix_aliases()
{
load_aliases_db();
if (process_aliases())
	{
	do_rerank();
	do_rebase();
	}
}

function load_aliases_db()
	{
	global $aliasdb;
  global $mysqli;

	$query = 'SELECT aliases.name as name, aliases.alias as alias, min(ar.id) as aliasid, min(nr.id) as runnerid, count(*) as count from aliases, runners ar, runners nr';
  $query .= ' where ar.name = aliases.alias and nr.name = aliases.name group by alias';
	$result = $mysqli->query ($query) or trigger_error($mysqli->error." ".$query);

	if ($result)
		{
		while ($row = mysqli_fetch_array ($result, MYSQLI_ASSOC))
			$aliasdb[(string)$row['alias']] = $row;
		}
	else
		{
		echo "Error reading alias database<br/>";
		return;
		}
	}

function process_aliases()
	{
	global $aliasdb;
	foreach ($aliasdb as $alias => $row)
		{
		if (!process_alias($alias, $row))
			return false;
		}
	return true;
	}	
	
function process_alias($alias, $row)
{
  global $mysqli;
	if (!array_key_exists("count", $row))
		return false;
	if ($row['count'] != 1)
		return true;  //Skip it, too many matches
	
	$query = 'update results set runnerid = '.$row['runnerid'].' where runnerid = '.$row['aliasid'];
	$result = $mysqli->query ($query) or trigger_error($mysqli->error." ".$query);
	if ($result)
		{
		$query = 'delete from runners where id = '.$row['aliasid'];
		$result = $mysqli->query ($query) or trigger_error($mysqli->error." ".$query);
		}
	else
		return false;
	return true;
}
?>
