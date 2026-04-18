<div id="banner">
<div id="BannerTitle"><a href='http://ranking.bigfootorienteers.com'>Big Pink Rankings</a></div>
<?php
  $showCount = 5;
    $query = "SELECT events.id as id,events.name as name,events.date as tdate, date_format(events.date,'%a %D') as date, count(*) as count FROM `events`,results where results.eventid = events.id group by 1 order by tdate desc, name desc limit ".$showCount;
    $result = $mysqli->query ($query) or trigger_error($mysqli->error." ".$query);
  
  echo "<div id=\"LastEvent\"><p>Recent Events : ";
  while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
    {
    echo "<a href=\"http://ranking.bigfootorienteers.com/displayevent.php?id=".$row['id']."\">&bull; ".$row['name']." (".$row['date'].") - ".$row['count']." scored</a>"; 
    }
  echo "</p></div>";
    
    
?>
<div id="owner">Another DamnSillyBigFootIdea</div>
</div>
