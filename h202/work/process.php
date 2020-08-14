<?
session_start();
error_reporting(E_ALL & E_PARSE);
ini_set("display_errors", 1); 
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once '/var/www/html/CHT/h202/h202Functions.php';
include_once '/var/www/html/CHT/lib/chtFunctions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';

//die('disabled');

function roundToNextQuarterHour($datetime)
{
	list($date, $time) = explode(' ', $datetime);
	list($m, $d, $y) = explode('/', $date);
	$m = sprintf("%02d", $m);
	$d = sprintf("%02d", $d);
	
	$date = "$y-$m-$d";
	
	list($hour, $min, $sec) = explode(':', $time);
	$hour = sprintf("%02d", $hour);
	$min = sprintf("%02d", $min);
	$sec = sprintf("%02d", $sec);

	$sec = $sec > 59 ? 59 : $sec;
	$min = $min > 59 ? 59 : $min;
	
	if ( ($min > 0) && ($min < 15) )
		$min = '15';
	elseif ( ($min > 15) && ($min < 30) )
		$min = '30';
	elseif ( ($min > 30) && ($min < 45) )
		$min = '45';
	elseif ( ($min > 45) && ($min < 60) )
	{
		if ($hour == '23')
		{
			$result = getResult("select date_add('$date', interval 1 day) as date");
			$line = mysqli_fetch_assoc($result);
			extract($line);
			$hour = '00';
		}
		else
		{
			$hour++;
		}
		
		$min = '00';
	}
	
	$sec = '00';
	return "$date $hour:$min:$sec";
}


//executeQuery("TRUNCATE TABLE processData");
$process_tank = fopen("/var/www/html/CHT/h202/work/Process.txt", 'r');
$processType = '';
while (!feof($process_tank))
{
	$line = fgets($process_tank);
//	echo("$line<br>");
	$line = trim($line);
	if (substr($line, 0, 1) == '>')
	{
			$line = substr($line, 1);
	}
	$line = trim($line);
	
    // this is a data line for a process reading
	if (strpos(strtolower($line), "--flowline") !== false)
	{
		$processType =  'FlowLine';
	}
	if (strpos(strtolower($line), "--samplepoint") !== false)
	{
		$processType =  'SamplePoint';
	}
		
	// check to see if the line has a " as the first char and has a , and : somewhere else in the line
	if (strpos($line, chr(9)) !== false)
	{
		if ($processType == 'SamplePoint')
		{
			list($monitorID, $datetime, $flowLineID, $samplePointID, $ppm, $temperature) = explode(chr(9), $line);
			$temperature = trim($temperature);
			list($date, $time) = explode(' ', $datetime);
			$newDateTime = roundToNextQuarterHour($datetime);
			
			$query = "select monitorID from processData WHERE date = '$newDateTime' AND monitorID='$monitorID' AND flowLineID='$flowLineID' AND (samplePointID='$samplePointID' OR samplePointID='')";
			$result = getResult($query);
			if ( checkResult($result) )
			{
				// update existing with this more current reading for the quarter
				$query = "UPDATE processData 
								SET 
								samplePointID='$samplePointID', 
								ppm=$ppm, 
								temperature=$temperature 
								WHERE 
								monitorID='$monitorID' AND 
								flowLineID='$flowLineID' AND 
								(samplePointID='$samplePointID' OR samplePointID='') 
								AND date='$newDateTime' LIMIT 1";
				executeQuery($query);
			}
			else
			{
				// add value for this quarter
				$query = "INSERT INTO processData (monitorID, flowLineID, samplePointID, date, ppm, temperature) VALUES ('$monitorID', '$flowLineID', '$samplePointID', '$newDateTime', $ppm, $temperature)";
				executeQuery($query);
			}	
		}
		elseif ($processType == 'FlowLine')
		{
//			echo("FlowLine<br>");
			list($monitorID, $flowDatetime, $flowLineID, $flowRate) = explode(chr(9), $line);
			list($date, $time) = explode(' ', $flowDatetime);
			$newFlowDateTime = roundToNextQuarterHour($flowDatetime);
			$query = "select monitorID from processData WHERE date = '$newFlowDateTime' AND monitorID='$monitorID' AND flowLineID='$flowLineID'"; //  AND (flowLineID=$flowLineID OR flowLineID='')
			$result = getResult($query);
			if ( checkResult($result) )
			{		
				// update existing with this more current reading for the quarter
				$query = "UPDATE processData SET flowRate=$flowRate WHERE monitorID='$monitorID' AND flowLineID='$flowLineID' 
					AND date='$newFlowDateTime' AND flowLineID='$flowLineID' LIMIT 1";
				executeQuery($query);
			}
			else
			{
				// no corresponding monitorID.  We may want to send an email alert here
				$query = "INSERT INTO processData (monitorID, flowLineID, date, flowRate) VALUES ('$monitorID', '$flowLineID', '$newFlowDateTime', $flowRate)";
				executeQuery($query);
			}	
		}
	}

	if (!empty($query))
	{
	//	echo("<br>$query");
	}
//	echo("-- $line");
	$msg .= $line;
	$cnt++;	

}
fclose($process_tank);
?>