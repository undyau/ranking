<div class="instructions">
<p>Results data from <a href="http://eventor.orienteering.asn.au">Eventor</a>. Ranking system is based on the <a href="https://www.britishorienteering.org.uk/images/uploaded/downloads/Competition%20Rule%20S%202014rankingscheme.pdf">BOF system</a> with some small improvements. A competitors best six events are used in calculating their ranking, but only two sprint events are counted in that six. Urban events are not counted.
Run of the week is based on how many standard deviations a competitor beats their average performance by.</p>
<p>Use <a href="https://github.com/undyau/ranking/issues">GitHub</a> to raise issues</p>
<p>Type in filter boxes to filter by name, club, state or gender. <button id='demo' onclick='copyToClipboard(getFilterUrl())'>Filter as link</button></p></div>
<div class = "notes">
<!-- <h2>Rankings are being defrosted now orienteering is happening again across the country</h2> -->
<p>Runs of the week:</p>
<ol>
<?php
$query = "SELECT events.name as ename,results.points, (results.points - runners.current_score)/runners.current_sd as perf, runners.name as rname, runners.id as runnerid, events.id as eventid FROM `events`,results,runners where results.eventid = events.id and results.runnerid = runners.id and runners.current_sd > 0 and events.date > DATE_SUB(NOW(), INTERVAL 1 WEEK) and results.points > runners.current_score order by 3 desc limit 3";
$result = $mysqli->query ($query) or trigger_error($mysqli->error." ".$query);
if (mysqli_num_rows($result) > 0)
  {
  while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
    echo "<li><a href=\"../displayrunner.php?id=".$row['runnerid']."\">".$row['rname']."</a> scored ".$row['points']." at <a href=\"../displayevent.php?id=".$row['eventid']."\">".$row['ename']."</a> (rated ".$row['perf'].")</li>";
  }
else
  echo "No results for last week";
    
$query = "SELECT runners.name as rname, runners.id as runnerid, count(*) as ecount, clubs.name as club from results, runners, events, clubs where results.runnerid = runners.id and results.eventid = events.id and runners.clubid = clubs.id and datediff(curdate(), events.date) <= 365 group by 2 order by 3 desc limit 0,1";
$result = $mysqli->query ($query) or trigger_error($mysqli->error." ".$query);
if (mysqli_num_rows($result) > 0)
  {
  while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
    $most = "<li><a href=\"../displayrunner.php?id=".$row['runnerid']."\">".$row['rname']."</a> (".$row['club'].") has the most ranking events for the past year - ".$row['ecount']."</li>";
  }
?>
</ol>
<!--<p>Tasks:</p>
<ul>
<li>New information from Eventor about Y.O.B. not yet being used - should make age classes accurate.</li>
</ul>-->
<p>News:</p>
<ul>
<li>Bug fixed: Non-sprint events being flagged as sprints (remediating past events...).</li>
<li>Nea Shingler was top female at the end of 2025.</li>
<li>Alastair George was top male at the end of 2025.</li>
<li>Congratulations to Big Foot for taking down both gender titles.</li>
<!--<li>Michael Burt had the most ranked events for the 2023 with 63.</li>-->
<?php
echo $most;
?>
</ul>
</div>
