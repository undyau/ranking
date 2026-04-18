<?php
require_once(__DIR__.'/mysqli_connect.php');
require_once(__DIR__.'/rebase.php');
require_once(__DIR__.'/rerank.php');
require_once(__DIR__.'/fixAliases.php');
require_once(__DIR__.'/setyob.php');
require_once(__DIR__.'/setclass.php');
require_once(__DIR__.'/removeDups.php');
global $DEBUGME;
$DEBUGME = true;

fix_aliases();  // Fix up results for people entered under a well-recognised alias
set_classes();  // Set age group based on YOB
do_rerank();
do_rebase();

?>

