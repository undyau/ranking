<?php
require_once(__DIR__.'/mysqli_connect.php');
require_once(__DIR__.'/rebase.php');
require_once(__DIR__.'/rerank.php');
require_once(__DIR__.'/statresult.php');
require_once(__DIR__.'/trace.php');
$MinRankers = 7;

// Set url, date and name for 2nd and subsequent days of a multi-day event here
$url="https://eventor.orienteering.asn.au/Events/ResultList?eventId=22548&eventRaceId=23128&groupBy=EventClass";
$stageDate = "2026-04-05";
$stageName = "AOC 2026 - Australian Long";
$courseLenLookup = array(
    'NonSuch' => 5.1,
    'NonSuch2' => 5.1
    );


$userAgent = 'Mozilla/5.0 (X11; Linux x86_64; rv:2.0b9pre) Gecko/20110111 Firefox/4.0b9pre';
$event = array();
$clubdb = array(array());
$DEBUGME = true;
$ignoreRe = '/(MTBO|TEST|RELAY|SCORE|STREET|BIKE|TEAM|DUO|NIGHT|MTB|SSS|SKI|MELBOURNE PAS|SCATTER|WALKER|SATURDAY O.*SERIES|SOS |URBAN|MAPUN)/';
$logCount = 0;
$sprint = false;

$clubs = array();
$results = array(array());
$courses = array(array());
load_clubs_db();
process_event();

function load_clubs_db()
    {
    global $clubdb;
    global $mysqli;
    
    $query = 'SELECT id,UPPER(name) as name,UPPER(shortname) as shortname,UPPER(evname) as evname,state,country,isreal FROM `clubs`';
    $result = $mysqli->query($query) or trigger_error($mysqli->error." ".$query);

    if ($result)
        {
        while ($row = mysqli_fetch_array ($result, MYSQLI_ASSOC))
            if (count($row) > 0)
                $clubdb[(string)$row['id']] = $row;
        }
    else
        {
        echo "Error reading club database<br/>";
        return;
        }
    }

function process_event()
    {
    global $url;
    global $event;
    global $results;
    global $courses;
    global $mysqli;

    $html = get_result_page($url);

    if (strlen($html) > 200)
        {
        Trace("Got html for event ".$url);
    
        $event['url'] = $url;
        if (!parse_page($html))
            return;

        $query = delete_existing() ? "commit" : "rollback";
        $result = $mysqli->query($query) or trigger_error($mysqli->error." ".$query); 
        if ($result)
            {
            Trace("Before remediate_clubs  count(results) is ".count($results));
            remediate_clubs();  
            Trace("Following remediate_clubs count(results) is ".count($results));
            remediate_names();
            remediate_courses();
            Trace("Event is ".$event['name']." ".$event['date']." ".count($results)." results");
            if (lookup_runners() && count($results) > 0)
                {
                apply_points();
                $query = save_results()  ? "commit" : "rollback";
                $result = $mysqli->query($query) or trigger_error($mysqli->error." ".$query);
                if ($result)
                    {
                    do_rerank();
                    do_rebase();
                    }
                }
            }
        }
    else
        Trace($url." contents was only ".strlen($html)." bytes !");
    }
    
function delete_existing()
    {
    global $event;
    global $mysqli;
    $query = "select id from events where url = '".$event['url']."'";
    $result = $mysqli->query($query) or trigger_error($mysqli->error." ".$query);
    if ($result)
        {
        if ($result->num_rows > 0)
            {
            $row = $result->fetch_row();
            $id = $row[0];
            $query = "begin work";
            $result = $mysqli->query($query) or trigger_error($mysqli->error." ".$query);
            if (!$result)
                return false;
                
            $query = "delete from results where eventid = $id";
            $result = $mysqli->query($query) or trigger_error($mysqli->error." ".$query);
            if (!$result)
                return false;
            $query = "delete from events where id = $id";
            $result = $mysqli->query($query) or trigger_error($mysqli->error." ".$query);
            if (!$result)
                return false;
            }
        }
    return true;
    }
    
function apply_points()
{
global $results;
global $event;
// Collect stats by course
$data= array(array());
$stats = array(array());
foreach ($results as $result)
    {
    if ($result['current_score'] > 0)
        {
        $statRes = new StatResult();
        $statRes->time = time_to_secs($result['time']);
        $statRes->current_score = $result['current_score'];
        $statRes->current_sd = $result['current_sd'];
        $statRes->name = $result['name'];
        $data[$result['course']][] = $statRes;
        }           
    }
    
foreach ($data as $key => $row)  //process by course
    {
    global $MinRankers;
    Trace("Processing course ".$key." for ".$event['name']);
    $year = (int)substr($event['date'], 0, 4);  
    if ($year < 2011)
        $min = $MinRankers - (2011-$year);
    else
        $min = $MinRankers;
        
    if (count ($row) >= $min)
        {
recalc: 
        $times = array();
        $points = array();
        $zeroes = 0;
        foreach ($row as $result)
            {
            $times[] = $result->time;
            $points[] = $result->current_score;
            if ($result->time %60 == 0)
                $zeroes++;
            else
                $zeroes--;
            }           
        //$row = eliminateBottom10($value);
        /*foreach ($row as $ttime => $tpoints)
            {
            Trace("ranking result,$key,$ttime,$tpoints");
            }*/
        // calc stats on ranked runners
        $stats[$key]['MT'] = array_sum($times) / count($times);
        $stats[$key]['ST'] = standard_deviation($times);
        $stats[$key]['MP'] = array_sum($points) / count($points);
        $stats[$key]['SP'] = standard_deviation($points);
        $stats[$key]['MIN'] = min($times);
        
        if ($stats[$key]['ST'] == 0 || $zeroes > 0)
            {
            if ($zeroes > 0)
                Trace("Dropping course result for $key - too many times with zero seconds");
            else
                Trace("Dropping course result for $key - SDev of times is 0");
            $stats[$key]['MT'] = 0;
            $stats[$key]['ST'] = 0;
            $stats[$key]['MP'] = 0;
            $stats[$key]['SP'] = 0;
            $stats[$key]['MIN'] = 0;
            }
        elseif ($year >= 2010)
        // eliminate runners with bad results from being in the calculation
            {
            $count = count($row);
            $maxDeviation = 0;

            foreach ($row as $result)
                {       
                $score = $stats[$key]['MP'] + 
                    ($stats[$key]['SP'] * ($stats[$key]['MT'] - $result->time))/$stats[$key]['ST'];
                $sd = max(35, $result->current_sd);
                if ($result->current_score - $score > 2 * $sd)
                    {
                    if ($result->current_score - $score > $maxDeviation)
                        $maxDeviation = $result->current_score - $score;
                    }
                //Trace("Ranked score: ".$result->current_score." Event score:".$score." SD:".$sd." Max deviation:".$maxDeviation);
                }
            if ($maxDeviation > 0)
                {
                foreach ($row as $result)
                    {       
                    $score = $stats[$key]['MP'] + 
                        ($stats[$key]['SP'] * ($stats[$key]['MT'] - $result->time))/$stats[$key]['ST'];
                    $sd = max(35, $result->current_sd);
                    if ($maxDeviation == $result->current_score - $score) 
                        {
                        Trace("Dropping ranker score: ".$score.", name: ".$result->name.", current_score: ".$result->current_score.", dev: ".$sd);
                        $row = array_diff($row, array($result)); // remove me from the array
                        break;
                        }
                    }
                }
                
            if (count($row) >= $MinRankers && count($row) < $count)
                goto recalc;
                
            if (count($row) <= $MinRankers)
                {
                $stats[$key]['MT'] = 0;
                $stats[$key]['ST'] = 0;
                $stats[$key]['MP'] = 0;
                $stats[$key]['SP'] = 0;
                $stats[$key]['MIN'] = 0;
                Trace("Dropping course, now only ".count ($row)." ranked");
                }
            else
                Trace(count($row)." rankers left on course ".$key);
            }
        else
            {
            Trace(count($row)." rankers on course ".$key);
            /*Trace("Mean time:".$stats[$key]['MT'] );
            Trace("SD time:".$stats[$key]['ST'] );
            Trace("Mean points:".$stats[$key]['MP'] );
            Trace("SD points:".$stats[$key]['SP'] );*/  
            }
        }
    else
        {
        Trace("Dropping course, only ".count ($row)." ranked");
        }
    }

// Count results where seconds are zeros - if too many, its probably a score event
$zeroes = 0;
foreach ($results as $key => $result)
    if (substr($result['time'],-3) == ".00")
        ++$zeroes;
    else
        --$zeroes;

foreach ($results as $key => $result)
    {
    $course = $result['course'];
    $time = time_to_secs($result['time']);
    if (array_key_exists($course, $stats) && 
    array_key_exists('ST', $stats[$course]) && 
        array_key_exists('MIN', $stats[$course]) &&
    $stats[$course]['ST'] != 0 && $zeroes < 0 && $time > 0)
        {
        $results[$key]['score'] = $stats[$course]['MP'] + 
            ($stats[$course]['SP'] * ($stats[$course]['MT'] - $time))/$stats[$course]['ST'];
        $results[$key]['sprint'] = $stats[$course]['MIN'] < 1200 ? 1 : 0;   // if under 20 minute winning time, then sprint
        if ($results[$key]['score'] <= 0)
            unset ($results[$key]);
//      else
//          Trace($results[$key]['name']." ".$time." ".$results[$key]['score']." ".$course." MP:".$stats[$course]['MP']." SP:".$stats[$course]['SP']." MT:".$stats[$course]['MT']." ST:".$stats[$course]['ST']);
        }
    else
        {
        unset ($results[$key]); 
        }
    }
Trace ("There are ".count($results)." results for the whole event");
}

function normalise_event_name($name)
    {
    $pos = strpos($name,',');
    if ($pos  !== false && strlen($name) > 40)
        return substr($name, 0, $pos);
    $pos = strpos($name,' -');
    if ($pos  !== false && strlen($name) > 40)
        return substr($name, 0, $pos);
    return $name;
    }

function save_results()
    {
    global $results;
    global $event;
    global $mysqli;
    global $sprint;
    global $stageDate;
    global $stageName;

    $query = "begin work";
    $result = $mysqli->query($query) or trigger_error($mysqli->error." ".$query);
    if (!$result)
            return false;   

    $query = "insert into events(name, date, url) ";
    $query .= "values ('".$stageName."','".$stageDate."','".$event['url']."')";
    $result = $mysqli->query($query) or trigger_error($mysqli->error." ".$query);
    if (!$result)
            {
            $result = $mysqli->query ("rollback") or trigger_error(mysqli_error()."rollback");
            return false;   
            }

    $event['id'] = $eventid = $mysqli->insert_id;

    foreach ($results as $key => $row)
        {
        if ($row['db_id'] >= 0)
            {
            $query = "update runners set ";
            $query .= "gender = '".$row['gender']."',";
            $query .= "clubid = ".$row['club'];
            $query .= " where id = ".$row['db_id'];
            }
        else
            {
            $query = "insert into runners (name,  gender, clubid) values (";
            $query .= "'".$mysqli->real_escape_string($row['name'])."','".$row['gender']."',".$row['club'].")";
            }
        $result = $mysqli->query ($query); 
        if (!$result)
                {
                if ($row['db_id'] < 0 && preg_match("/Duplicate entry.*/i", $mysqli->error))
                    {
                    unset($results[$key]);
                    continue;
                    }
                else
                    {
                    trigger_error($mysqli->error."\n".$query);
                    $result = $mysqli->query ("rollback") or trigger_error($mysqli->error."rollback");
                    return false;   
                    }
                }
        if ($row['db_id'] < 0)
            $row['db_id'] = $mysqli->insert_id;

        $query = "insert into results (runnerid, eventid, points, class, sprint) values (";
        $query .= $row['db_id'].",".$eventid.",".round($row['score']).",'".$row['coursename']."'";
        if ($sprint || $row['sprint'] == 1)
            $query .= ",1)";
        else
            $query .= ",0)";

        $result = $mysqli->query($query) or trigger_error($mysqli->error." ".$query);
        if (!$result)
                {
                $result = $mysqli->query ("rollback") or trigger_error($mysqli->error."rollback");
                return false;   
                }
        }

    $query = "select count(*), avg(points) from results where eventid = $eventid and points <> 0";
    $result = $mysqli->query($query) or trigger_error($mysqli->error." ".$query);
    if (!$result)
        Trace("It all looked good until the check on how many results were added for this event");
    else
        {
        $row = $result->fetch_row();
        Trace("Added ".$row[0]." results with average ".$row[1]." points");
        }   

    return true;
    }

function courses_can_merge($name1, $name2)
    {
    $name1IsSeniorClass = $name2IsSeniorClass = false;
    // If one class is a senior age group class and other is not, don't merge
    if (strlen($name1) >= 4 && 
        (substr($name1, -1) == "A" || substr($name1, -1) == "E") &&
        (strpos($name1,'14') == false) &&
        (strpos($name1,'12') == false) &&
        (strpos($name1,'10') == false) &&
        (substr($name1, 0, 1) == "M" || substr($name1,0,1) == "W"))
        $name1IsSeniorClass = true;
    if (strlen($name2) >= 4 && 
        (substr($name2, -1) == "A" || substr($name2, -1) == "E") && 
        (strpos($name2,'14') == false) &&
        (strpos($name2,'12') == false) &&
        (strpos($name2,'10') == false) &&       
        (substr($name2, 0, 1) == "M" || substr($name2,0,1) == "W"))
        $name2IsSeniorClass = true;
    return $name1IsSeniorClass == $name2IsSeniorClass;
    }

function remediate_courses()
    {
    global $results;
    global $courses;

    if (count($courses) == 0)
        return;
    // Any courses with the same name or same control count or length should be merged
    
    $lookups = array();
    foreach ($courses as $courseid => $course)
        {
        $lookups[$courseid] = $courseid;  //set default value, assume course is unique
        foreach ($lookups as $key  => $value)
            {   
            if ($courses[$value]['name'] == $course['coursename'] && strlen($course['coursename']) > 0)
                {   
                $lookups[$courseid] = $value;
                break;
                }
            if ($courses[$value]['length'] == $course['length'] &&
                $courses[$value]['controls'] == $course['controls'] &&
                $course['length'] > 0.01 && // 0.0 may mean not-set
                ($course['controls']  > 0 || strpos($course['length'],'.') !== false) &&
                courses_can_merge($courses[$value]['name'], $course['name']))
                {   
                Trace("Merging ".$courses[$value]['name']." with ".$course['name']);
                $lookups[$courseid] = $value;
                break;
                }
            }
        }
    foreach ($lookups as $lkey => $lval)
        Trace("course lookup $lkey => $lval");
    // Loop through all results and fix up the course
    foreach ($results as $id => $result)
        $results[$id]['course'] = $lookups[$result['course']];
    }
    
    
function remediate_names()
    {
    global $results;
    $forwards = 1;
    $backwards = 0;
    foreach ($results as $id => $result)
        {
        $pieces = explode(" ", $result['name']);
        $pattern = '/John|Peter|Ian|Michael|Robert|Mark|Andrew|Tom|Chris|Rob|Jim|Sue|Nick|Geoff|Tim|Paul|Barbara|Jenny/';
        if (count($pieces) > 1)
            {
            if (preg_match($pattern, $pieces[0]))
                ++$forwards;
            if (preg_match($pattern, $pieces[count($pieces)-1]))
                ++$backwards;
            }
        }

    if ($backwards > $forwards)  // names are the wrong way round !
        {
        foreach ($results as $id => $result)
            {
            $pieces = explode(" ", $result['name']);
            $reversed = array_reverse($pieces);
            $results[$id]['name'] = implode(' ', $reversed);
            }
        }           
    }

function lookup_runners()
{
    global $results;
    foreach ($results as $id => $result)
        {
        if (!lookup_runner($id))
            return false;
        }       
    return true;
}

function lookup_runner($id)
{
    // Try for exact match 
    global $results;
    global $mysqli;
    global $clubdb;
    $realClubMatch = 0;
    $query = "select runners.name as name, runners.id, clubid, gender, current_score, current_sd, clubs.isreal as isreal, ";
    $query .= "clubs.country, UPPER(clubs.shortname) as shortname, clubs.state, MIN(DATEDIFF(events.date,NOW())) as dayspast ";
    $query .= "from runners, clubs, results, events ";
    $query .= "where runners.name = '".$mysqli->real_escape_string($results[$id]['name'])."' ";
    $query .= "and clubid = clubs.id and events.id = results.eventid and results.runnerid = runners.id ";
    $query .= "group by runners.id order by dayspast asc";

    $result = $mysqli->query($query) or trigger_error($mysqli->error." ".$query);
    if (!$result)
        return false;
            
    if ($result->num_rows > 0)
        {
        $rows = array(array());
        while ($rows[] = $result->fetch_array (MYSQLI_ASSOC))
            ;
        $count = 0;
        foreach ($rows as $row)
            {
            if (count($row) == 0)
                continue;
            // See if we are pretty sure of a match:
            // a) country matches
            // b) was ranked under a representative team, result is for real club (so update table)
            //    or vice-versa (so update result data) 
            if ($row['clubid'] == $results[$id]['club'] && $row['isreal'])
                {
                $results[$id]['db_id'] = $row['id'];
                $results[$id]['current_score'] = $row['current_score'];
                $results[$id]['current_sd'] = $row['current_sd'];
                update_gender($id, $row['gender']);
                return true;
                }
            if (could_be_same_runner($id, $row))
                {
                ++$count;
                if ($row['isreal'])
                    $realClubMatch++;
                }
            }
        
        if ($count == 1)
            {
            foreach ($rows as $row)
                {
                if (could_be_same_runner($id, $row))
                    {               
                    $results[$id]['db_id'] = $row['id'];
                    $results[$id]['current_score'] = $row['current_score'];
                    $results[$id]['current_sd'] = $row['current_sd'];
                    update_gender($id, $row['gender']);
                    if (!$clubdb[$results[$id]['club']]['isreal'])                  
                        update_club($id, $row['clubid']);
                    return true;
                    }
                }   
            }
        elseif ($count > 1 && $realClubMatch == 1)
            {
            foreach ($rows as $row)
                {
                if (could_be_same_runner($id, $row) && $row['isreal'])
                    {
                    $results[$id]['db_id'] = $row['id'];
                    $results[$id]['current_score'] = $row['current_score'];
                    $results[$id]['current_sd'] = $row['current_sd'];
                    update_gender($id, $row['gender']);                 
                    update_club($id, $row['clubid']);
                    return true;                    
                    }
                }
            }
        else
            {
            $result = get_best_runner($rows, $id);
            if (empty($result))
                Trace("Found multiple possible runners for ".$results[$id]['name']." ".$clubdb[$results[$id]['club']]['state']);
            else
                {
                $results[$id]['db_id'] = $result['id'];
                $results[$id]['current_score'] = $result['current_score'];
                $results[$id]['current_sd'] = $result['current_sd'];
                update_gender($id, $result['gender']);
                update_club($id, $result['clubid']);
                return true;
        }
            }
            
        }
        
    $results[$id]['db_id'] = -1;
    $results[$id]['current_score'] = 0; 
    $results[$id]['current_sd'] = 0;    
    return true;
}


function get_best_runner($dbrows, $runnerid)
    {
    global $results;
    global $clubdb;
    $rows = array();
    foreach ($dbrows as $row)
        {
        if (is_array($row) && array_key_exists("isreal", $row) && $row['isreal'])
            $rows[$row['id']] = $row;
        }
    if (count($rows) == 0)
        {
        Trace("No real clubs found for $results[$runnerid]['name']");
        return NULL;
        }
        
    foreach ($rows as $row)
        {
        if (array_key_exists("shortname", $row) && array_key_exists("state", $row))
            {
            if ($row['state'] = $clubdb[$results[$runnerid]['club']]['state'])
                {
                Trace("Duplicate runner - using most recent runner with same state - ".$row['id'].": ".$row['name']." ".$row['shortname']." ". $row['state']);
                return $row;
                }
            }
        }
        
    foreach ($rows as $row)
        {
        if (array_key_exists("shortname", $row) && array_key_exists("state", $row))
            {
            Trace("Duplicate runner - using most recent runner with same name - ".$row['name']." ".$row['shortname']." ". $row['state']);
            return $row;
            }
        }       
    return NULL;
    }

function update_gender($id, $db_value)
    {
    global $results;
    if (strlen($results[$id]['gender']) == 0)
        {
        $results[$id]['gender'] = $db_value;
        return;
        }
    if ($results[$id]['gender'] == "M" &&
        $db_value == "F")
        {
        $results[$id]['gender'] = $db_value;
        return;
        }
    if ($results[$id]['gender'] == "F" &&
        $db_value == "M")
        {
        $results[$id]['gender'] = $db_value;
        return;
        }    
    return;
    }   

function update_club($id, $db_value)
    {
    global $results;    
    $results[$id]['club'] = $db_value;
    return;
    }       
    
function could_be_same_runner($id, $row)
    {
    global $results;
    global $clubdb;
    
    if (!array_key_exists('shortname', $clubdb[$results[$id]['club']]))
        return false;
    if (!array_key_exists('shortname', $row))
        return false;
    
  // Clubs have same short name
    if ($clubdb[$results[$id]['club']]['shortname'] == $row['shortname'])
        return true;
        
    // Can't be from different countries
    if ($clubdb[$results[$id]['club']]['country'] != $row['country'])
        return false;
        
    // One and only one has to be a real club
    if ($clubdb[$results[$id]['club']]['isreal'] == $row['isreal'])
        return false;  
    return true;
    }
    
function remediate_clubs()
    {
    $lookup = array();
    identify_clubs($lookup);
    assign_clubs($lookup);
    }

function assign_clubs(&$lookup)
    {
    global $results;
    global $clubdb;
    foreach ($results as $id => $result)
        {
        if (array_key_exists('club', $result) && array_key_exists($result['club'], $lookup))
            {
            $club = $lookup[$result['club']];
            if ($club < 0)
                unset($results[$id]);
            else
                {
                $results[$id]['club'] = $club;
                }
            }
        else
            {
            unset($results[$id]);
            }
        }
    }

function get_state($club, $stateLetter)
    {
    global $clubdb;
    
    foreach ($clubdb as $row)
        {
        if (count($row) > 0 && $row['shortname'] == $club && 
            strlen($stateLetter) > 0 && 
            substr($row['state'],0,1) == $stateLetter)
            return $row['state'];
        }
        
    // No match with state - try without
    foreach ($clubdb as $row)
        {
        if (count($row) > 0 && $row['shortname'] == $club)
            if (strlen($row['state']) > 0)
                return $row['state'];
        }
    return "XXX";
    }

function get_likely_club($club, $stateCount, $stateLetter = "")
    {
    // Some club shortnames are duplicated between states (go figure)
    global $clubdb;
    global $logCount;
    
    $candidates = array();
    
    foreach ($clubdb as $id => $row)
        {   
    if (count($row) == 0)
      continue;
        $state = $clubdb[$id]['state'];
        if (strcasecmp($row['name'],$club) == 0 || strcasecmp($row['evname'],$club) == 0)
            {
            return $id;         
            }
        elseif (strlen($row['evname'])> 25 && strlen($club) > 25 &&
                strcasecmp(substr($row['evname'], 1, 25), substr($club, 1, 25)) ==0)
            {
            return $id;
            }
        elseif (strcasecmp($row['name'],$club." Orienteers") == 0 ||
                strcasecmp($row['name'], $club." Orienteering Club") == 0)
            {
            return $id;
            }                   
        elseif ($row['shortname'] == $club)
            {
            if (strlen($stateLetter) > 0 && 
                (substr($state,0,1) == $stateLetter ||  ($state == "NZL" && $stateLetter =="Z")))
                return $id;         
            $candidates[$id] = array_key_exists($state, $stateCount) ? $stateCount[$state] : 0;
            }
        }

    if (count($candidates) == 1)
        foreach ($candidates as $key => $candidate)
            return $key;

    if (count($candidates) == 0)
        return -1;


// Find best match, based on most common state of the candidates        
    $maxval = -1;
    $best = -1;
    foreach ($candidates as $id => $count)
        {
        if ($count > $maxval)
            {
            $maxval = $count;
            $best = $id;
            }
        }

    return $best > 0 ? $best : -1;
    }
    
function identify_clubs(&$lookup)
    {
    global $clubs;
    global $clubdb;

  // Count competitors by state
    $stateCount = array();
    foreach ($clubs as $club => $count)
        {
        $pattern = '/^[\s]*([a-zA-Z]{2,2})[\.|\s]?([S|W|N|V|T|Q|A]?)[^a-zA-Z]*/';
        if (preg_match($pattern, $club, $matches))
            {   
      $state = get_state($matches[1], $matches[2]);
      if (!array_key_exists($state, $stateCount))
        $stateCount[$state] = 0;
            $stateCount[get_state($matches[1], $matches[2])] += $count;
            }
        }       
    unset ($stateCount["XXX"]);
    
  // Attempt to match each club found, categorise matches
    foreach ($clubs as $club => $count)
        {
        // Search for long club names
        $pattern = '/^(\S.{6,49})/';
        if (preg_match($pattern, $club, $matches))
            {
            //Trace("Doing basic search on $club");
            $id = get_likely_club($matches[1], $stateCount);
            if ($id > 0)
                {
                //Trace("Found match for $club - ".$clubdb[$id]['name']);
                $lookup[$club] = $clubdb[$id]['id'];
                continue;
                }
            }
        
        $pattern = '/^[\s]*([a-zA-Z]{2,2})[\.|\s]?([S|W|N|V|T|Q|A|Z]?)[^a-zA-Z]*/';
        if (preg_match($pattern, $club, $matches))
            {
            //Trace("Doing AB.S search on $club");
            $id = (count($matches) == 1) ?  get_likely_club($matches[1], $stateCount) : get_likely_club($matches[1], $stateCount, $matches[2]);
            if ($id > 0)
                {
                //Trace("Found match for $club - ".$clubdb[$id]['name']);
                $lookup[$club] = $clubdb[$id]['id'];
                continue;
                }
            }
        // Search for representative teams (eg AUS, NZL)    
        $pattern = '/^[\s]*([a-zA-Z]{3,3})[\.|\s]?/';
        if (preg_match($pattern, $club, $matches))
            {
            $id = get_likely_club($matches[1], $stateCount);
            if ($id > 0)
                {
                $lookup[$club] = $clubdb[$id]['id'];
                continue;
                }
            }   
                        
            
        if (strlen($club) == 0 ||
           $club == "-" || 
           strpos($club, "--") > -1 || 
           strpos($club, "- -") > -1 || 
            strpos($club, "NON") > -1)
            {
            $lookup[$club] = -1;
            continue;
            }

        $lookup[$club] = -1;
        Trace("Didn't find club $club");    
        }
    }

function get_result_page($url)
    {
  global $userAgent;
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_USERAGENT, $userAgent);
    curl_setopt($curl, CURLOPT_AUTOREFERER, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt($curl, CURLOPT_TIMEOUT, 10 );             

    $html = curl_exec( $curl ); 
    return $html;
    }

    
function process_row(&$courseid, $item, &$count, &$prevtime)
    {
    global $clubs;
    global $runnerid;
    global $results;
    global $courses;
    global $year;
    global $ignoreRe;
    static $gotScoreEvent = false;

    $items = $item->getElementsByTagName("td");

    if (!$item->hasAttribute('data-resultid'))
        return;
    
    $time = 0;
    foreach ($items as $col)
        {
        $colclass = $col->getAttribute('class');
        $colContent = $col->textContent; // this seems to eliminate the weird character substituted for &nbsp;

        switch ($colclass)
            {
            case "n" : $name = NomeProprio($colContent); break;
            case "o" : $club = $col->textContent; break;
            case "t" : $time = $col->textContent; break;
            case "sp":
            case "s" : 
                {
                $val = $col->textContent;
                if (!$gotScoreEvent && $val > 0)
                    {
                    Trace("Dropping some results as they seem to be score event results");
//                  $gotScoreEvent = true;
                    }
                if ($val > 0)
                    return;  // Looks like a score event 
                }
            default: break;         
            }
        }
    
    if (strlen($club) == 0 || strlen($name) == 0 || strlen($time) == 0)
        return;
    $club = trim(strtoupper($club));
    if (!array_key_exists($club, $clubs))
        $clubs[$club] = 1;
    else
        $clubs[$club] = 1 + $clubs[$club];
                    
    if (strlen($club) > 0 && strlen($time) > 0 &&
            !preg_match($ignoreRe, strtoupper($courses[$courseid]['name'])) &&
            (str_word_count($name) < 4 || (str_word_count($name) == 4 && stripos($name,'van der'))) &&
            strpos($name,'+') === false &&
            strpos($name,'&') === false &&
            strpos($name,',') === false &&
            valid_time($time) &&
            time_to_secs($time) >= $prevtime
            )
        {   
        $prevtime = time_to_secs($time);
        $results[$runnerid]['name'] = $name;
        $results[$runnerid]['club'] = $club;
        $results[$runnerid]['time'] = $time;
        $results[$runnerid]['course'] = $courseid;
        $results[$runnerid]['coursename'] = $courses[$courseid]['name'];            
        if (preg_match("/([M|W])([\d]{2,3})[-20]?[A|B|E|S|L]/", $courses[$courseid]['name'], $matches))
            {
            $gender = $matches[1];
            if ($gender == "W")
                $gender = "F";
            $results[$runnerid]['gender'] = $gender;
            }
        else
            $results[$runnerid]['gender'] = "";

        ++$count;
        ++$runnerid;
        }
    }   
    
function process_table(&$courseid, $item)
    {   
    global $courses;
    global $allCoursesSame;
    global $courseLenLookup;
    // Check if is a class result
    if (strpos($item->getAttribute('class'), 'resultList') === false)
        return;
        
    // Get the node before this, should be parent of the course info
    $coursenode = $item->previousSibling->previousSibling->firstChild;
    if ($coursenode == NULL)
        {
        Trace ("Didn't find course info node");
        return;
        }   
    $coursename = $coursenode->firstChild->textContent;
    $courseinfo = substr($coursenode->textContent, strlen($coursename));

    $courses[$courseid]['controls'] = 0;  // Not available at the moment
    if (preg_match("/.*([\d]+) (\d\d\d) m,.*/", $courseinfo, $matches)) 
        {   
        $courses[$courseid]['coursename'] = $courses[$courseid]['name'] = $coursename;
        $courses[$courseid]['length'] = $matches[1].".".$matches[2];
        }
      else
        {
        if (array_key_exists($coursename, $courseLenLookup))
            {
            Trace("Missing course length for $coursename, using lookup value $courseLenLookup[$coursename]");
            $courses[$courseid]['coursename'] = $courses[$courseid]['name'] = $coursename;
            $courses[$courseid]['length'] = $courseLenLookup[$coursename];
            }
        else
            {
            Trace("Missing course length for $coursename, faking - $courseinfo");
            $courses[$courseid]['coursename'] = $courses[$courseid]['name'] = $coursename;
            if ($allCoursesSame)
                $courses[$courseid]['length'] = "1.0";
            else
                $courses[$courseid]['length'] = count($courses);
            }
        }
    $prevtime = 0;
    $count = 0;

    $items = $item->getElementsByTagName('tr');
    for ($i = 1; $i < $items->length; $i++)
        {
        process_row($courseid, $items->item($i), $count, $prevtime);
        }
    $courseid++;
    }   
 
    
function process_header(&$item)
    {
    global $event;
    global $year;
    global $ignoreRe;
    global $mysqli;
    $items = $item->getElementsByTagName('p');
    if ($items->length > 1)
        {
        foreach ($items as $para) 
            {
            $text = trim( preg_replace( '/\s+/', ' ', $para->textContent ) ); 
            
            if (preg_match("/.*Name:[\s]*([^<]*).*Organis.*Date:[\D]*([\d]+)[\s]*([A-Za-z]{3,12}).*/",
                $text, $matches))
                {
                $year = date('Y');
                $day = strlen($matches[2]) == 1 ? "0".$matches[2] : $matches[2];

                if (strtotime($year."-".date('m',strtotime($matches[3]))."-".$day) >
                    strtotime("+1 day", strtotime(date('Y-m-d'))))
                        $year = $year - 1;
                $event['date'] = $year."-".date('m',strtotime($matches[3]))."-".$day;
                $event['name'] = trim(mysqli_real_escape_string($mysqli, $matches[1]));
                Trace( "Processing <a href =\"".$event['url']."\">".$event['name']."</a> ".$event['date']);     
                return true;
                }
            }    
        }

        echo "Couldn't identify date, result dropped<br/>";
        return false;
    }
    
function parse_page(&$html) 
    {
    global $event;
    global $runnerid;
    global $ignoreRe;
    global $sprint;

    $DOM = new DOMDocument;
    libxml_use_internal_errors(true);
    $DOM->loadHTML($html);
    libxml_use_internal_errors(false);  
    
    if (!process_header($DOM))
        return false;
    
    if (preg_match($ignoreRe, strtoupper($event['name']), $matches))
        {
        Trace("Skipping - looks like a ".strtolower($matches[0]));
        return false;
        }
        
    if (preg_match('/(SPRINT)/', strtoupper($event['name']), $matches))
        {
        Trace("Checking - ".$event['name']." looks like a ".strtolower($matches[0]));
        $sprint = true;
        }       

    $items = $DOM->getElementsByTagName('table');

    //display all table headers
    $courseid = 0;
    $runnerid = 0;
    for ($i = 0; $i < $items->length; $i++)
        {
        process_table($courseid, $items->item($i)); 
        }   

    return true;
    }
    
    
function NomeProprio($nome)
   {
   // Weird DOM character that replaces &nbsp;
   $nome = str_replace("\xc2\xa0", " ", $nome);
   //two space to one   
   $nome = str_replace("  ", " ", $nome);
   $nome = str_replace("  ", " ", $nome);
   $nome = str_replace("  ", " ", $nome);
   $novonome = "";

   $intervalo = 1;
   for ($i=0; $i < strlen($nome); $i++)
       {
       $letra = substr($nome,$i,1);
       if (((ord($letra) > 64) && (ord($letra) < 123)) || ((ord($letra) > 48) && (ord($letra) < 58)))
          {
          $checa_palavra = substr($nome, $i - 2, 2);
          if (!strcasecmp($checa_palavra, 'Mc') || !strcasecmp($checa_palavra, "O'"))
             {
             $novonome .= strtoupper($letra);
             }
            elseif ($intervalo)
             {
             $novonome .= strtoupper($letra);
             }
            else
             {
             $novonome .= strtolower($letra);
             }
          $intervalo=0;
          }
         else
          {
          $novonome .= $letra;
          $intervalo = 1;
          }
       }
   $novonome = str_replace(" Of ", " of ", $novonome);
   $novonome = str_replace(" Da ", " da ", $novonome);
   $novonome = str_replace(" De ", " de ", $novonome);
   $novonome = str_replace(" Do ", " do ", $novonome);
   $novonome = str_replace(" E " , " e " , $novonome);
   return $novonome;
   }
   
   
function standard_deviation($aValues)
{
    if (count($aValues) == 0)
        {
        var_dump($aValues);
        return 0;    
        }
    $fMean = array_sum($aValues) / count($aValues);
    $fVariance = 0.0;
    foreach ($aValues as $i)
        {
        $fVariance += pow($i - $fMean, 2);
        }
    //Trace("Calculating variance, dividing $fVariance by ".count($aValues));
    $fVariance /=  count($aValues);
    return (float) sqrt($fVariance);
}  

function time_to_secs($mmss)
{
$parts = explode(":", $mmss);

switch (sizeof($parts))
    {
    case 0: $secs = 0; break;
    case 1: $secs = is_numeric($parts[0]) ? $parts[0] : 0; break;
    case 2: $secs = ($parts[0]*60) + $parts[1]; break;
    default: $secs = ($parts[0]*3600) + ($parts[1]*60) + $parts[2]; break;
    }
return $secs;
}

function valid_time($mmss)
    {
    $parts = explode(":", $mmss);

    $retVal = true;

    if (sizeof($parts) < 2)
        $retVal = false;
    else
        for ($i = 0; $i < sizeof($parts); $i++)
            if (!is_numeric($parts[$i]) || $parts[$i] >= 60 || $parts[$i] < 0)
                {
                Trace("Invalid part - ".$parts[$i]);
                $retVal = false;
                }

    return $retVal;
    }
    
?>
