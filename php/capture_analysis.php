<?php
require_once("_basic_tasks.php");
$url_this_page = "capture_analysis.php";
if(isset($_GET['title'])) $this_title = urldecode($_GET['title']);
else $this_title = '';
if(isset($_GET['data'])) $capture_file = urldecode($_GET['data']);
else {
    echo "No captured data file…"; die();
    }
$quantization = $_GET['quantization'];
$minimum_period = $_GET['minimum_period'];

require_once("_header.php");
display_darklight();

echo "<p>👉 Analysing captured events…</p>";
$captured_events = process_captured_events($capture_file,$quantization,$minimum_period);

function process_captured_events($capture_file,$quantization,$minimum_period) {
    $bigtest = TRUE;
    $captured_events = '';
    $file = fopen($capture_file,'r');
    if($file) {
        $line = fgets($file); // names of parameters
        $event = $arg = array();
        $table = explode("\t",$line);
        $max_args = count($table);
        if($max_args < 2) return '';
        for($i = 0; $i < $max_args; $i++) {
            $arg[$i] = $table[$i];
            echo $arg[$i]." <font color=\"red\">|</font> "; // names of parameters
            }
        echo "<p></p>";
        $i_event = $lastNoteOnTime = 0; $lastNoteOnCaptured = '';
        $fixed = FALSE;
        while(!feof($file)) {
            $line = trim(fgets($file));
            if($line == '') continue;
            $table = explode("\t",$line);
            if($bigtest AND $table[4] > 0) echo "<font color=\"green\">";
            for($i = 0; $i < $max_args; $i++) {
                $event[$i_event][$arg[$i]] = $table[$i];
                if($bigtest) echo $event[$i_event][$arg[$i]]." ";
                }
            if($bigtest AND $table[4] > 0) echo "</font>";
            if($event[$i_event]['source'] > 0 AND $event[$i_event]['event'] == "NoteOn") {
                $lastNoteOnCaptured = $event[$i_event]['note'];
                $lastNoteOnTime = $event[$i_event]['time'];
                $lastNoteOnIndex = $i_event;
                }
            if($event[$i_event]['source'] > 0 AND $event[$i_event]['event'] == "NoteOff" AND $event[$i_event]['note'] == $lastNoteOnCaptured) {
                $lastNoteOnCaptured = '';
                if($event[$i_event]['time'] < $lastNoteOnTime) {
                    if($bigtest) echo " *** fixed";
                    else echo $event[$i_event]['note']." ".$event[$i_event]['event']." fixed from ".$event[$i_event]['time']." to ".$lastNoteOnTime."<br />";
                    $event[$i_event]['time'] = $lastNoteOnTime;
                    $fixed = TRUE;
                    }
                }
            $i_event++;
            if($bigtest) echo "<br />";
            }
        if($bigtest) echo "-----<br />";
        if($lastNoteOnCaptured <> '') {
            echo "<i>".$event[$lastNoteOnIndex]['time']." ".$event[$lastNoteOnIndex]['note']." ".$event[$lastNoteOnIndex]['event']." suppressed</i><br />";
            unset($event[$lastNoteOnIndex]);
            $event = array_values($event);
            $fixed = TRUE;
            }
        fclose($file);
        if($fixed) echo "-----";
        usort($event,function($a, $b) {
            // Ensure both 'time' values exist and compare them numerically
            $timeA = $a['time'] ?? null;
            $timeB = $b['time'] ?? null;
            if ($timeA === null || $timeB === null) return 0;
            return $timeA <=> $timeB; // Numeric comparison for ascending order
            });
        echo "<p><i>After sorting events:</i></p>";
        displayEvents($event,$arg);
        echo "<br />";
        $minPeriod = $minimum_period; // Minimum expected period (milliseconds)
        if($minPeriod == 0) {
            $minPeriod = 2 * $quantization;
            echo "Minimum period = ".$minPeriod." ms (2 times the quantization)<br />";
            }
        else if($minPeriod < (2 * $quantization)) {
            $minPeriod = 2 * $quantization;
            echo "Minimum period (".$minimum_period." ms) was too small. It has been set to ".$minPeriod." ms, i.e. 2 times the quantization (".$quantization." ms)<br />";
            }
        else echo "Minimum period = ".$minPeriod." ms (as found in the settings)<br />";
        $step = 1;     // Step size for period testing (milliseconds)
        $period = calculatePeriod($event,$minPeriod,$step,FALSE);
        echo "The most likely period is ".$period." milliseconds<br />";
        echo "<p><i>After adjusting to the ".$period." ms period:</i></p>";
        $object = adjustToPeriod($event,$period);
        displayObjects($object);
        }
    return $captured_events;
    }

function calculatePeriod($events,$minPeriod,$step,$test) {
    // Extract event times
    $times = array_map(function($event) {
		$the_time = $event['time'];
        return $the_time;
   		}, $events);
	$maxtime = max(array_map(function($event) {
		return $event['time'];
		}, $events));
    sort($times);
    $deltas = []; $deltamin = $maxtime;
    for($i = 1; $i < count($times); $i++) {
        $the_delta = $times[$i] - $times[$i - 1];
		if($the_delta > $minPeriod AND $the_delta < $deltamin) $deltamin = $the_delta;
		$deltas[] = $the_delta;
    	}
	$maxPeriod = $deltamin;
	echo "Maximum period = ".$maxPeriod." ms<br />";
	echo "Maximum time of captured flow = ".$maxtime." ms<br />";
    $bestPeriod = 0;
    $bestScore = PHP_FLOAT_MIN;
    for($period = $minPeriod; $period <= $maxPeriod; $period += $step) {
		$score = scorePeriod($deltas,$period);
		if($test) echo $period." --> ".round($score,2)."<br />";
        if($score > $bestScore) {
            $bestScore = $score;
            $bestPeriod = $period;
			}
		}
    return round($bestPeriod,2);
	}

function adjustToPeriod($event,$period) {
    $object = array(); $j = 0;
    $imax = count($event);
    for($i = 0; $i < $imax; $i++) {
        $this_event = $event[$i];
        if($event[$i]['event'] == "NoteOn") {
            $object[$j]['type'] = "note";
            $object[$j]['start'] = $event[$i]['time'];
            $object[$j]['note'] = $event[$i]['note'];
            $object[$j]['source'] = $event[$i]['source'];
            $object[$j]['part'] = $event[$i]['part'];
            $channel = $object[$j]['channel'] = $event[$i]['channel'];
            $object[$j]['status'] = $event[$i]['status'];
            $object[$j]['data1'] = $event[$i]['data1'];
            $object[$j]['data2'] = $event[$i]['data2'];
  //          echo $event[$i]['note']." on -> ".$j."<br />";
            $NoteOn[$event[$i]['note']][$channel] = $j++;
            }
        else {
            $channel = $event[$i]['channel'];
            if($event[$i]['event'] == "NoteOff" AND isset($NoteOn[$event[$i]['note']][$channel])) {
                $jj = $NoteOn[$event[$i]['note']][$channel];
                unset($NoteOn[$event[$i]['note']][$channel]);
    //         echo $event[$i]['note']." off -> ".$jj."<br />";
                $object[$jj]['end'] = $event[$i]['time'];
                }
            else {
                $object[$j]['type'] = $event[$i]['event'];
                $object[$j]['note'] = '';
                $object[$j]['source'] = $event[$i]['source'];
                $object[$j]['part'] = $event[$i]['part'];
                $object[$j]['channel'] = $event[$i]['channel'];
                $object[$j]['status'] = $event[$i]['status'];
                $object[$j]['data1'] = $event[$i]['data1'];
                $object[$j]['data2'] = $event[$i]['data2'];
                $object[$j]['start'] = $object[$j++]['end'] = $event[$i]['time'];
                }
            }
        }
    foreach($object AS $this_object) {
        if(!isset($this_object['end'])) unset($this_object);
        }
    $object = array_values($object);
    $date_zero = $object[0]['start'];
    $jmax = count($object);
    for($j = 0; $j < $jmax; $j++) {
        $this_date = $object[$j]['start'] - $date_zero;
        $mismatch = fmod($this_date,$period);
        if($mismatch <> 0) {
    //        echo $j.") ".$this_date." -> ".$mismatch."<br />";
            if($mismatch <= ($period - $mismatch)) $object[$j]['start'] -= $mismatch;
            else $object[$j]['start'] += ($period - $mismatch);
            }
        if($this_object['type'] == "note") {
            $duration = $object[$j]['end'] - $object[$j]['start'];
            if($duration < $period) $object[$j]['end'] += ($period - $duration);
            }
        }
    return $object;
    }

function scorePeriod($deltas,$period) {
	$score = 0;
	foreach ($deltas as $delta) {
		$fraction = fmod($delta,$period) / $period;
		$distance = min($fraction, 1 - $fraction); // Closest alignment
	//    $score += exp(-10 * $distance); // Exponential decay for mismatches
		$score += 1 - $distance; // Higher score for better alignment
		}
	return $score;	
	}

function displayEvents($event,$arg) {
    echo "<table style=\"border-collapse:collapse;\">";
    $max_args = count($arg);
    echo "<tr>";
    for($i = 0; $i < $max_args; $i++) echo "<td style=\"padding:3px;\">".$arg[$i]."</td>";
    echo "</tr>";
    foreach($event as $this_event) {
        echo "<tr>";
        for($i = 0; $i < $max_args; $i++) {
            echo "<td style=\"padding:3px;\">";
            if($this_event['source'] > 0) echo "<span class=\"green-text\">";
            echo $this_event[$arg[$i]];
        if($this_event['source'] > 0) echo "</span>";
            echo "</td>";
            }
        echo "</tr>";
        }
    echo "</table>";
    return;
    }

function displayObjects($object) {
    echo "<table style=\"border-collapse:collapse;\">";
    $jmax = count($object);
    $td = "<td style=\"padding:3px;\">";
    $ttd = "</td>".$td;
    echo "<tr>";
    echo $td.$ttd.$ttd."start".$ttd."end".$ttd."source".$ttd."part".$ttd."status".$ttd."data1".$ttd."data2"."</td>";
    echo "</tr>";
    for($j = 0; $j < $jmax; $j++) {
        echo "<tr>";
        $this_object = $object[$j];
        echo $td.$this_object['type'].$ttd;
        if($this_object['source'] > 0) echo "<span class=\"green-text\">";
        echo $this_object['note'];
        if($this_object['source'] > 0) echo "</span>";
        echo $ttd.$this_object['start'].$ttd.$this_object['end'].$ttd;
        if($this_object['source'] > 0) echo "<span class=\"green-text\">";
        echo $this_object['source'];
        if($this_object['source'] > 0) echo "</span>";
        echo $ttd.$this_object['part'].$ttd.$this_object['status'].$ttd.$this_object['data1'].$ttd.$this_object['data2']."</td>";
        echo "</tr>";
        }
    echo "</table>";
    return;
    }

/*
// Example usage
$minPeriod = 200; // Minimum expected period (milliseconds)
$step = 1;     // Step size for period testing (milliseconds)
$in = [
    ["time" => 0],
    ["time" => 2000],
    ["time" => 2000],
    ["time" => 2100],
    ["time" => 3000],
    ["time" => 3000],
    ["time" => 3900],
    ["time" => 4500],
    ["time" => 5000],
    ["time" => 5500],
    ["time" => 6500],
    ["time" => 6700],
	];
$period = calculatePeriod($in,$minPeriod,$step,TRUE);
echo "<p>The most likely period is ".$period." milliseconds</p>"; */

// http://localhost/try/bolprocessor/php/_capture_analysis.php
?>
