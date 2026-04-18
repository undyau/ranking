<?php
$DEBUGME = true;
function set_classes()
{
  global $mysqli;
    $result = $mysqli->query ("begin work") or trigger_error($mysqli->error." begin work");
    if ($result)
        {
        if (update_classes())       
            $query = "commit";
        else
            $query = "rollback";
        $result = $mysqli->query ($query) or trigger_error($mysqli->error." ".$query);
        }
}

function update_classes()
    {
  global $mysqli;
    $current_year = date("Y");
    //$query = "select  name, id as runner_id, yob_floor, yob_ceiling, gender from runners where $current_year - yob_ceiling >= 18";
    $query = "select  name, id as runner_id, yob_floor, yob_ceiling, gender from runners";

    $result = $mysqli->query ($query) or trigger_error($mysqli->error." ".$query);
    if (!$result)
        return false;

    $rows = array();
    while ($rows[] = mysqli_fetch_array ($result, MYSQLI_ASSOC))
        ;

    foreach ($rows as $row)
        {
        $max_age = $row['yob_floor'] == 0 ? 0 : $current_year - $row['yob_floor'];
        $min_age = $row['yob_ceiling'] == 0 ? 0 : $current_year - $row['yob_ceiling'];

        $age = 0;
        // Unknown - make 21
        if ($min_age == 0 && $max_age == 0) 
            $age = 21;
        // Junior ?     
        elseif ($max_age > 0 && $max_age < 21)
            $age = "10-20";
        // 21
        elseif ($min_age >= 21 && $max_age < 35 && $min_age < 35)
            $age = 21;
        elseif ($max_age >= 35 && $min_age == 0 || ($min_age == $max_age))
            $age = floor($max_age/5) * 5;
        elseif ($min_age >= 35)
            $age = floor(($max_age+4)/5) * 5;
        if ($age != 0 && ($row['gender'] == "M" || $row['gender'] == "F") )
            {
            $gender = $row['gender'] == "F" ? "W" : $row['gender'];
            $class = $gender.$age;  
            $query = "update runners set class = \"$class\" where id = ".$row['runner_id'];
            $result = $mysqli->query ($query) or trigger_error($mysqli->error." ".$query);
            if (!$result)
                return false;
            }
        }
    
    return true;
}

?>

