<?
session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once '/var/www/html/CHT/h202/h202Functions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';

//date_default_timezone_set('America/Los_Angeles');

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

	// if the minutes is on one of the quarter hour and seconds is 00 then return the passed time
	//if ($sec == 00 && ($min == 00 || $min == 15 || $min == 30 || $min == 45))
//	{
//			return "$date $hour:$min:$sec";
//	}

	$sec = $sec > 59 ? 59 : $sec;
	$min = $min > 59 ? 59 : $min;
	
	if ( ($min >= 0) && ($min < 15) )
		$min = '15';
	elseif ( ($min >= 15) && ($min < 30) )
		$min = '30';
	elseif ( ($min >= 30) && ($min < 45) )
		$min = '45';
	elseif ( ($min >= 45) && ($min < 60) )
	{
		if ($hour == '23')
		{
			$result = getResult("select date_add('$date', interval 1 day) as date");
			$line = mysql_fetch_assoc($result);
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

function getLogValue($name, $filename)
{
	$res = '';
	$f = fopen($filename, 'r');
	while (!feof($f))
	{
		$line = fgets($f, 100);
		if (strpos($line, $name) !== false )
		{
			list($name, $res) = explode(':', $line);
			//break;
		}
	}
	return trim($res);
	fclose($f);
}

function getInfoFromCFG($filename)
{
	$res = '';
	if ( !file_exists($filename) ) return 'fail';
	$f = fopen($filename, 'r');
	$line = fgets($f, 5000);

	bigecho($line);
	$start_pos =  strpos($line, 'MID: "') + 6; // 293;
	if ($start_pos == 6) 
	{
		$start_pos =  strpos($line, 'MID:"') + 5; // Handle no space;
	}
	
	if ($start_pos <= 6) 
	{
		$line = fgets($f, 5000);
		$start_pos =  strpos($line, 'MID: "') + 6; // 293;
	}
	$end_pos = strpos($line, '"', $start_pos);
	$lenth = $end_pos - $start_pos;
	$monitorID = trim( substr($line, $start_pos, $lenth) );

//bigecho("($filename)  start_pos: $start_pos  --  end_pos: $end_pos --  length: $lenth");

	$start_pos =  strpos($line, '--', $end_pos + 1) + 2;
	$end_pos = strpos($line, '--', $start_pos) ;
	$lenth = $end_pos - $start_pos;
	$flowLineID = trim( substr($line, $start_pos, $lenth) );

	$start_pos =  strpos($line, '--', $end_pos) + 2;
	$end_pos = strpos($line, '--', $start_pos) ;
	$lenth = $end_pos - $start_pos;
	$samplePointID = trim( substr($line, $start_pos, $lenth) );

	
bigecho("$monitorID . '--' . $flowLineID . '--' . $samplePointID;");
	return $monitorID . '--' . $flowLineID . '--' . $samplePointID;
	fclose($f);
}

function processLogFile($cfgInfo, $interval, $filename)
{
	list($monitorID, $flowLineID, $samplePointID) = explode('--', $cfgInfo);
	bigecho("MonitorID: " . $monitorID);
	$res = '';
	$f = fopen($filename, 'r');
	$skipped = 0;
	$lineNo = 0;
	$lineNoDebug = 0;
	while (!feof($f))
	{
		$line = fgets($f, 200);
		$lineNo++;
		$lineNoDebug++;
		if ( trim($line) == "" )
		{
			continue;
		}
		elseif (substr($line, 0, 2) == '<!')
		{
			// clear variables and continue
			$lineNo = 0;
			$skipped = 0;
			$newStartTime = "";
			$end_time = "";
			$start_time = "";
			$odainfo = "";
			$diff = "";
			$skipcount = "";
			continue;
		}
		elseif ( substr($line, 0, 1) == '<' && substr($line, 0, 2) != '<!' )
		{
			$line = str_replace('<', '', $line);
			$line = str_replace('>', '', $line);
			// bigecho("First Line of Log: " . $line);
			$odainfo = explode(';', $line);
			$start_time = $odainfo[2];
			
			$newStartTime = $start_time;
			$originalStartTime = $start_time;			
			
			$start_time_fmt = gmdate("m/d/Y H:i:s", $start_time);
			$end_time_fmt = roundToNextQuarterHour($start_time_fmt);
			$end_time = strtotime($end_time_fmt);
			
			// adjust for PST
$testing = $end_time - $start_time;			
bigecho("$testing = $end_time - $start_time;");
			
			$end_time -= 28800;
			
			//$diff = $end_time - $start_time;
			$diff = 900;  // <-- This assumes that we always have 15 minutes between writes

			$skipcount = round( $diff / $interval ); 
			$skipcount = max($skipcount, 1);
			//bigecho("$skipcount = round( $diff / $interval ); ");
			$skipped = 0;
			/*
				Need to figure out how many readings to skip in order to get the one that matches $t3;
			*/
			
			bigecho("$start_time --> $end_time ($diff) --> skip $skipcount");
			bigecho("$start_time_fmt --> $end_time_fmt ($diff) --> skip $skipcount");
		}
		$skipped++;

$newStartTime_fmt = gmdate("m/d/Y H:i:s", $newStartTime);
bigecho("Line: $lineNoDebug  --  New StartTime (GMT): $newStartTime_fmt --  Interval: $interval  --  Difference: $diff");

		if ($skipped == $skipcount)
		{
			// write this reading
			// bigecho($line);
			
			if ( substr($line, 0, 1) == '{' ) 
			{
				$line = str_replace('{', '', $line);
				$line = str_replace('}', '', $line);
				list($ppm, $temp) = explode(';', $line);
		
				$temp = trim($temp) == '' ? 0 : $temp;
				if ($temp > 0)
					$temp = $temp / 10;
				$start_time_fmt = gmdate("m/d/Y H:i:s", $newStartTime);
				$reading_time_fmt = roundToNextQuarterHour($start_time_fmt);
				echo("---- Doing a Write:  $monitorID - $flowLineID -- $samplePointID -- $reading_time_fmt - temp=$temp - ppm=$ppm (line $lineNoDebug )<br />");
				// either update or insert.  We are waiting for the readings to include the flowLineID
				
				$query = "select monitorID from processData WHERE date = '$reading_time_fmt' AND monitorID='$monitorID' AND flowLineID='$flowLineID' AND (samplePointID='$samplePointID' OR samplePointID='')";
				$result = getResult($query);
				if ( checkResult($result) )
				{
					$query = "UPDATE processData 
								SET 
								samplePointID='$samplePointID', 
								ppm=$ppm, 
								temperature=$temp 
								WHERE 
								monitorID='$monitorID' AND 
								flowLineID='$flowLineID' AND 
								(samplePointID='$samplePointID' OR samplePointID='') 
								AND date='$reading_time_fmt' LIMIT 1";
					// executeQuery($query);
				}
				else
				{
					// add value for this quarter
					$query = "INSERT INTO processData (monitorID, flowLineID, samplePointID, date, ppm, temperature) VALUES ('$monitorID', '$flowLineID', '$samplePointID', '$reading_time_fmt', $ppm, $temp)";
					// executeQuery($query);
				}	
				executeQuery($query);
						
			}
			$start_time += $interval; //$diff; //  + 100  <-- delete this
			$diff = 900; // 15 minutes <-- don't think we need this
			$skipped = 0;
			$skipcount = round( $diff / $interval ); // next reading at 15 minute point
		}
		else
		{
			$start_time_fmt = gmdate("m/d/Y H:i:s", $newStartTime);
			$reading_time_fmt = roundToNextQuarterHour($start_time_fmt);
			
			bigecho("----Considering line $lineNoDebug - Date/Time is: $start_time_fmt  <br />");
			//$start_time += $diff + 100; // 
		}
			
		// bigecho("newStartTime -------> $newStartTime = $originalStartTime + ($lineNoDebug * $interval)");
		$newStartTime = $originalStartTime + ($lineNo * $interval);
		
	}
	return trim($res);
	fclose($f);	
}

$ftpdir = "/var/www/html/CHT/h202/ftp";
$d = dir($ftpdir);
while (false !== ($entry = $d->read())) 
{
	if ($entry == '.' || $entry == '..') continue;

	if (is_dir($ftpdir . '/' . $entry))
	{
		$subdir = dir($ftpdir . '/' . $entry);
		
		$filterMatch = true;
		if (!empty($logDirectory))
		{
			if ($logDirectory == $entry)
			{
				$filterMatch = true;
			}
			else
			{
				continue;
			}
		}
		
bigecho("--> $ftpdir/$entry/log.txt");
		
		
		if (file_exists("$ftpdir/$entry/log.txt") && $filterMatch)
		{
bigecho("--> $ftpdir/$entry/log.txt (found)");
			$serialNumber = getLogValue('Serial Number', "$ftpdir/$entry/log.txt");
			$interval = getLogValue('Logging Interval', "$ftpdir/$entry/log.txt");
			$cfgInfo = getInfoFromCFG("$ftpdir/$entry/$entry.cfg");
			if ($cfgInfo == 'fail') 
			{
				bigecho("failed to read one or more entries in $ftpdir/$entry/$entry.cfg");
				continue;
			}
			list($monitorID, $flowLineID, $samplePointID) = explode('--', $cfgInfo);
			//$flowLineID = getFlowlineID("$ftpdir/$entry/$entry.cfg");
			//$samplePointID = getSamplePointID("$ftpdir/$entry/$entry.cfg");
			
			// bigecho("$monitorID, $flowLineID, $samplePointID,  $serialNumber, $interval");
	
			while (false !== ($sub_entry = $subdir->read())) 
			{
				if ($sub_entry == '.' || $sub_entry == '..') continue;
				if ( (strpos($sub_entry, $serialNumber) !== false) && (strpos($sub_entry, '.log') !== false ) )
				{
					// process log
					error_log("Processing: $ftpdir/$entry/$sub_entry");
					processLogFile($cfgInfo, $interval, "$ftpdir/$entry/$sub_entry");
				
					if (true) // enable when going live
					{
						$processTime = gmdate("Ymd-H_i_s");

						$newName = str_replace('.log', '.'.$processTime, $sub_entry);
						if ( rename( "$ftpdir/$entry/$sub_entry", "$ftpdir/$entry/$newName" ) )
						{
							 bigecho('rename successful');
						}
						else
						{
							bigecho('rename failed');
						}
					}
				}
			}
		}
		$subdir->close();
	}
}
$d->close();
bigecho('complete');