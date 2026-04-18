<?php
function Trace($msg)
	{
	global $DEBUGME;
	if ($DEBUGME)
		error_log(date("d-m-y h:i:s").": ".$msg."\n",3,__DIR__."/evtrace".date("Y-m").".txt");
	} 	
?>	