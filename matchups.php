<?php
require_once(__DIR__.'/mysqli_connect.php');
?>

<!DOCTYPE html>
<html>
<head>
<meta content="text/html; charset=utf-8" http-equiv="content-type">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
<title>Big Pink Australian Orienteering Rankings</title>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.2.6/jquery.min.js"></script>
<script type="text/javascript" src="jscript/tablesorter/jquery.tablesorter.js"></script>
<link rel="stylesheet" href="themes/pink/style.css" type="text/css" id="" media="print, projection, screen" />
<link rel="stylesheet" href="themes/style.css" type="text/css" id="" media="print, projection, screen" />
<script type="text/javascript" id="js">$(document).ready(function()
    {
    $table=$("#myTable")
       .tablesorter({sortList: [[2,1]], widgets: ['zebra']});
    function applyFilters() {
        var nameFilter = $('#opponentFilter').val().toLowerCase();
        var clubFilter = $('#clubFilter').val().toLowerCase();
        $('#myTable tbody tr').each(function() {
            var cells = $(this).find('td');
            var name = $(cells[0]).text().toLowerCase();
            var club = $(cells[1]).text().toLowerCase();
            var show = name.indexOf(nameFilter) >= 0 && club.indexOf(clubFilter) >= 0;
            $(this).css('display', show ? '' : 'none');
        });
    }
    $('#opponentFilter, #clubFilter').keyup(applyFilters);
    }
);</script>
</head>
<body>
<?php
    include('./banner.php');
?>

<?php
    $id = $_REQUEST['id'];
    if (!ctype_digit($id))
        $id = 0;

    $query = "SELECT runners.name as name, clubs.name as club, runners.current_ranking as points
    FROM runners, clubs WHERE clubs.id = clubid AND runners.id = $id";
    $result = $mysqli->query($query) or trigger_error($mysqli->error." ".$query);
    if ($result)
        {
        $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
        echo "<div id=\"RunnerLabel\"><a href=\"displayrunner.php?id=$id\"><em>".$row['name']."</em></a> &ndash; Matchups</div>";
        }

    $query = "SELECT r2.id as opponent_id, r2.name as opponent_name, c2.name as opponent_club,
        COUNT(*) as races,
        SUM(res1.points > res2.points) as wins,
        SUM(res1.points < res2.points) as losses,
        SUM(res1.points = res2.points) as ties
    FROM results res1
    JOIN results res2 ON res1.eventid = res2.eventid AND res1.class = res2.class AND res2.runnerid != res1.runnerid
    JOIN runners r2 ON res2.runnerid = r2.id
    JOIN clubs c2 ON r2.clubid = c2.id
    WHERE res1.runnerid = $id
    GROUP BY r2.id, r2.name, c2.name
    ORDER BY races DESC, wins DESC";
    $result = $mysqli->query($query) or trigger_error($mysqli->error." ".$query);
?>
<div style="margin:8px 0;display:flex;gap:8px">
<input type="text" id="opponentFilter" placeholder="Filter opponent..." style="font-family:'Inter',Arial,sans-serif;font-size:13px;padding:5px 10px;border:1px solid #E0C0D4;border-radius:4px;outline:none;width:220px">
<input type="text" id="clubFilter" placeholder="Filter club..." style="font-family:'Inter',Arial,sans-serif;font-size:13px;padding:5px 10px;border:1px solid #E0C0D4;border-radius:4px;outline:none;width:220px">
</div>
<table id="myTable" class="tablesorter" cellspacing="0" cellpadding="2">
<thead>
<tr>
    <th>Opponent</th>
    <th>Club</th>
    <th>Races</th>
    <th>Wins</th>
    <th>Losses</th>
    <th>Ties</th>
    <th>Win %</th>
</tr>
</thead>
<?php
    if ($result)
        {
        echo '<tbody>';
        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
            {
            $winpct = $row['races'] > 0 ? round(100 * $row['wins'] / $row['races']) : 0;
            echo "<tr>";
            echo "<td><a href=\"headtohead.php?id=$id&amp;opponent=".$row['opponent_id']."\">".$row['opponent_name']."</a></td>";
            echo "<td>".$row['opponent_club']."</td>";
            echo "<td>".$row['races']."</td>";
            echo "<td>".$row['wins']."</td>";
            echo "<td>".$row['losses']."</td>";
            echo "<td>".$row['ties']."</td>";
            echo "<td>".$winpct."%</td>";
            echo "</tr>";
            }
        echo '</tbody>';
        }
?>
</table>
</body>
</html>
