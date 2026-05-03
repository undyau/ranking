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
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="themes/pink/style.css" type="text/css" media="print, projection, screen" />
<link rel="stylesheet" href="themes/style.css" type="text/css" media="print, projection, screen" />
<title>Big Pink Australian Orienteering Rankings</title>
</head>
<body>
<?php
    include('./banner.php');

    $id = isset($_REQUEST['id']) && ctype_digit($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

    $query = "SELECT runners.name as name, clubs.name as club, runners.current_ranking as current_ranking
              FROM runners JOIN clubs ON clubs.id = runners.clubid
              WHERE runners.id = $id";
    $result = $mysqli->query($query) or trigger_error($mysqli->error." ".$query);
    $runner = $result->fetch_assoc();

    if (!$runner)
        {
        echo "<p>Runner not found.</p>";
        exit;
        }

    echo "<div id='RunnerLabel'><em>".htmlspecialchars($runner['name'])."</em> (".htmlspecialchars($runner['club']).")</div>";

    // Fetch our runner's results with event names for tooltip
    $query = "SELECT YEAR(e.date) as year, e.name as eventname, r.class, r.points, r.sprint
              FROM results r JOIN events e ON r.eventid = e.id
              WHERE r.runnerid = $id AND r.points > 0
              ORDER BY YEAR(e.date) ASC, r.points DESC";
    $result = $mysqli->query($query) or trigger_error($mysqli->error." ".$query);

    $runnerByYear = [];
    while ($row = $result->fetch_assoc())
        {
        $y = (int)$row['year'];
        $runnerByYear[$y][] = ['points' => (int)$row['points'], 'sprint' => (int)$row['sprint'], 'name' => $row['eventname'], 'class' => $row['class']];
        }

    // Fetch all runners' current_ranking for current-year percentile
    $currentRankings = [];
    $qr = $mysqli->query("SELECT current_ranking FROM runners WHERE current_ranking > 0")
          or trigger_error($mysqli->error);
    while ($rr = $qr->fetch_row())
        $currentRankings[] = (int)$rr[0];

    // Fetch all results for all runners to calculate year-end scores for everyone
    $query = "SELECT r.runnerid, YEAR(e.date) as year, r.points, r.sprint
              FROM results r JOIN events e ON r.eventid = e.id
              WHERE r.points > 0
              ORDER BY YEAR(e.date) ASC, r.runnerid, r.points DESC";
    $result = $mysqli->query($query) or trigger_error($mysqli->error." ".$query);

    // Group by year → runner
    $allByYear = [];
    while ($row = $result->fetch_assoc())
        {
        $y   = (int)$row['year'];
        $rid = (int)$row['runnerid'];
        $allByYear[$y][$rid][] = ['points' => (int)$row['points'], 'sprint' => (int)$row['sprint']];
        }

    // Calculate best-6 (max 2 sprints) year-end score for every runner in every year
    function yearEndScore($events)
        {
        usort($events, function($a, $b) { return $b['points'] - $a['points']; });
        $total = 0; $counted = 0; $sprintsUsed = 0; $counted_events = [];
        foreach ($events as $e)
            {
            if ($counted >= 6) break;
            if ($e['sprint'] && $sprintsUsed >= 2) continue;
            $total += $e['points'];
            $counted++;
            if ($e['sprint']) $sprintsUsed++;
            $counted_events[] = $e;
            }
        return $counted > 0 ? ['score' => $total, 'count' => $counted, 'events' => $counted_events] : null;
        }

    $labels      = [];
    $percentiles = [];
    $tooltips    = [];
    $currentYear = (int)date('Y');

    $currentYearAdded = false;

    foreach ($allByYear as $year => $runners)
        {
        if (!isset($runners[$id])) continue;

        $isCurrent = ($year == $currentYear && $runner['current_ranking'] > 0);
        $mine      = yearEndScore($runnerByYear[$year] ?? $runners[$id]);

        if (!$isCurrent && !$mine) continue;

        $myScore = $isCurrent ? $runner['current_ranking'] : $mine['score'];

        // Calculate percentile: current year uses current_ranking pool, past years use yearEndScore
        if ($isCurrent)
            {
            $pool = $currentRankings;
            }
        else
            {
            $pool = [];
            foreach ($runners as $rid => $events)
                {
                $s = yearEndScore($events);
                if ($s) $pool[] = $s['score'];
                }
            }

        $total = count($pool);
        $below = count(array_filter($pool, function($s) use ($myScore) { return $s < $myScore; }));
        $percentile = round(($below / $total) * 100);

        $eventLines = [];
        if ($mine)
            foreach ($mine['events'] as $e)
                {
                $name  = isset($e['name'])  ? $e['name']  : 'Event';
                $class = isset($e['class']) && $e['class'] !== '' ? ' (' . $e['class'] . ')' : '';
                $eventLines[] = $name . $class;
                }

        $position = $total - $below;
        if ($percentile >= 99)
            {
            $mod100 = $position % 100;
            $mod10  = $position % 10;
            if ($mod100 >= 11 && $mod100 <= 13)      $sfx = 'th';
            elseif ($mod10 == 1)                      $sfx = 'st';
            elseif ($mod10 == 2)                      $sfx = 'nd';
            elseif ($mod10 == 3)                      $sfx = 'rd';
            else                                      $sfx = 'th';
            $summary = $position . $sfx . ' of ' . $total . ' runners';
            }
        else
            $summary = 'Top ' . (100 - $percentile) . '% of ' . $total . ' runners';

        if ($isCurrent)
            $summary .= ' (current ranking)';
        elseif ($mine && $mine['count'] < 6)
            $summary .= ' (' . $mine['count'] . ' events)';

        $labels[]      = $year;
        $percentiles[] = $percentile;
        $tooltips[]    = array_merge([$summary], $eventLines);

        if ($year == $currentYear) $currentYearAdded = true;
        }

?>
<div style="max-width:800px; margin:20px auto; padding:0 20px;">
    <canvas id="rankingChart"></canvas>
</div>
<script>
const tooltips = <?php echo json_encode($tooltips); ?>;
new Chart(document.getElementById('rankingChart').getContext('2d'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode($labels); ?>,
        datasets: [{
            label: 'Percentile rank',
            data: <?php echo json_encode($percentiles); ?>,
            borderColor: '#C8689A',
            backgroundColor: 'rgba(200,104,154,0.1)',
            borderWidth: 2,
            pointBackgroundColor: '#C8689A',
            pointRadius: 5,
            tension: 0.3,
            fill: true,
            clip: false
        }]
    },
    options: {
        responsive: true,
        layout: {
            padding: { top: 12 }
        },
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return tooltips[context.dataIndex];
                    }
                },
                displayColors: false
            },
            title: {
                display: true,
                text: 'Year-end percentile rank',
                font: { family: 'Inter', size: 16, weight: '600' }
            }
        },
        scales: {
            y: {
                min: 0,
                max: 100,
                title: { display: true, text: 'Percentile', font: { family: 'Inter' } }
            },
            x: {
                title: { display: true, text: 'Year', font: { family: 'Inter' } }
            }
        }
    }
});
</script>
</body>
</html>
