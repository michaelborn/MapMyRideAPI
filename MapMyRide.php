<?php
$action = "";
if (isset($_GET["action"])) { $action=$_GET["action"]; }

require_once("api.class.php");

$API = new API();

$tokens = $API->getTokens();
$convert = new Convert();


/*
print '<textarea>';
var_dump(json_encode($workouts));
print '</textarea>';
 */

switch($action) {
  case "current":
    //print "Where's waldo: current speed and location.<br />";
    //print "Get latest workout, then get time series of workout by ID.<br />";

    // get data for all workouts since DATE.
    $tripStart = new DateTime('2016-06-27');
    $fields = array(
      "user" => $tokens["user_href"],
      "order_by"=>"-start_datetime", // the last shall be first. :)
      "limit"=>"1"
    );

    // Make the API call to /v7.1/workouts
    $data = $API->getWorkouts($fields);

    // get the workouts from the returned data
    $workouts = $data["_embedded"]["workouts"];
    $id = $workouts[0]["_links"]["self"][0]["id"];


    //print "Now, do an API call to get the workout by id:" . $id . "<br />";
    $latest = $API->getWorkout($id);

    // All we really care about is today's route (lat/lon), and stats like today's speed, distance, etc.
    $json = array(
      "position"=>$latest["time_series"]["position"],
      "aggregates"=>convertAggregates($convert,$latest["aggregates"])
    );

    print json_encode($json);
  break;
  case "tripstats":
    // get data for all workouts since DATE.
    $tripStart = new DateTime('2016-06-27');
    $tripEnd = new DateTime('2016-07-04');
    $fields = array(
      "user" => $tokens["user_href"],
      "order_by"=>"-start_datetime", // the last shall be first. :)
      "started_after"=>$tripStart->format('c'),
      "started_before"=>$tripEnd->format('c')
    );

    // Make the API call to /v7.1/workouts
    $data = $API->getWorkouts($fields);

    // get the workouts from the returned data
    $workouts = $data["_embedded"]["workouts"];

    $totals = totalAggregates($workouts);

    print json_encode(convertAggregates($convert,$totals));

    break;
  case "test":
    print "Testing the unit conversion.<br />";
    print "========== Meters Per Second to Miles Per Hour ==========<br />";
    print "1 meters per second is " . $convert->mpsToMph(1) . " miles per hour!<br />";
    print "5 meters per second is " . $convert->mpsToMph(5) . " miles per hour!<br />";
    print "========== Joules to Calories ========<br />";
    print "1 joules is " . $convert->joulesToKCals(1) . " k calories!<br />";
    print "500 joules is " . $convert->joulesToKCals(500) . " k calories!<br />";

    print "========== Meters to Miles ==========<br />";
    print "1000 meters is " . $convert->metersToMiles(1000) . " miles!<br />";
    print "9999 meters is " . $convert->metersToMiles(9999) . " miles!<br />";

    print "========== Seconds to time interval ==========<br />";
    print "1000 seconds is " . $convert->secondsToTimeString(1000) . " hh:mm:ss<br />";
    print "1 seconds is " . $convert->secondsToTimeString(1) . " hh:mm:ss<br />";

  break;
  default:

    print '{"error":"Bad URL action."}';
  break;
}
  

function allTimeSeries($workouts) {
  $dat = array();

  foreach($workouts as $workout) {
    print "Time series:";
    var_dump($workout);
    array_push($dat,$workout["time_series"]);
  }
  return $dat;
}

function totalAggregates($workouts) {
  $sumAgg = array(
    "distance_total" => 0,
    "speed_avg" => 0,
    "speed_max" => 0,
    "speed_min" => 0,
    "active_time_total" => 0,
    "metabolic_energy_total" => 0
  );

  // loop through each workout and get the sum stats
  foreach($workouts as $workout) {
    $stats = $workout["aggregates"];
    $sumAgg["distance_total"] += $stats["distance_total"];
    $sumAgg["speed_avg"] += $stats["speed_avg"];
    $sumAgg["active_time_total"] += $stats["active_time_total"];
    $sumAgg["metabolic_energy_total"] += $stats["metabolic_energy_total"];

    // set speed_max IF greater than previous speed_max
    if ($stats["speed_max"] > $sumAgg["speed_max"]) {
      $sumAgg["speed_max"] = $stats["speed_max"];
    }
  }

  $sumAgg["speed_avg"] = $sumAgg["speed_avg"] / count($workouts);

  return $sumAgg;
}

function convertAggregates($convert,$arr) {
  // For each item, convert to a more friendly unit.
  return array(
    "distance_total"=>$convert->metersToMiles($arr["distance_total"]),
    "speed_avg"=>$convert->mpsToMph($arr["speed_avg"]),
    "speed_max"=>$convert->mpsToMph($arr["speed_max"]),
    "active_time_total"=>$convert->secondsToTimeString($arr["active_time_total"]),
    "kcalories_burned_total"=>$convert->joulesToKCals($arr["metabolic_energy_total"])
  );
}

?>
