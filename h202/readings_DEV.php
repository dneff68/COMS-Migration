READINGS DEVELOPMENT
#!/usr/bin/php -q
<?php
// THIS FILE HAS BEEN RELOCATED TO 
// etc/smrsh/readings.php  
//

//$current = file_get_contents($testFile);
//$current .= "In readings_DEV\n";
//file_put_contents($testFile, $current, FILE_APPEND | LOCK_EX);
// die('done.  check /Library/WebServer/Documents/COMS-Migration/test.txt');



$processType = '';

function jim()
{
	return false;
}

function david()
{
	// return true if David Neff is the client
	//return false;
	global $REMOTE_ADDR, $debug;
	$debug = false;
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);

	$s = $REMOTE_ADDR == "68.96.88.124";
	$s = $s || "::1";
	return $s;
}


function bigEcho($txt)
{
	if (jim() || david())
	{
		echo("<h4>$txt</h4>");
	}
}


function isRemote()
{
	// return true if David Neff is the client
	//return false;
	$debug = false;
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
	return $_SERVER['HTTP_HOST'] == '72.32.58.210' || $_SERVER['HTTP_HOST'] == 'neffhost.com'  || $_SERVER['HTTP_HOST'] == 'h2o2.neffhost.com'; // 72.32.58.210
}

if (isRemote())
{
//die("Migration Server: readings.php");
	bigEcho("REMOTE Development Server");
	$hostname= 'localhost'; //'127.0.0.1'; // localhost
	$dbuser = 'DevUser';
	$dbpass = 'QsTTeVfn';
	$database = 'h202';
	$comsDir = '/var/www/html/COMS-Migration/h202';
    $localLogPath = '/var/www/html/COMS-Migration';
    $david_debug = false;

	// $hostname='localhost';
	// $dbuser = 'root';
	// $dbpass = '5aowCao7mvbw';
	// $database = 'h202';
}
else
{
	bigEcho("LOCAL Development Server");
	$comsDir = '/Library/WebServer/Documents/COMS-Migration/h202';
    $localLogPath = '/Library/WebServer/Documents/COMS-Migration';
    $david_debug = true;
	$hostname="127.0.0.1";
	$dbuser = 'root';
	$dbpass = 'smap0tCfl';
	$database = 'h202';
}
$testFile = $localLogPath . '/test.txt';
//error_log('entering readings_DEV.php', 0, '/var/log/httpd/error_log');

//include_once '/var/www/html/CHT/h202/h202Functions.php';
include_once $comsDir . '/h202Functions.php';  // Migration Server

function showArray($arr)
{
	ksort($arr);
	reset($arr);
	
	foreach ($arr as $var=>$val)
	{
		echo "<br>$var = $val";
	}
}

function roundToNextQuarterHour($datetime)
{
	//bigEcho($datetime);
	//die;

	list($date, $time) = explode(' ', $datetime);
	list($m, $d, $y) = explode('/', $date);
	$m = sprintf("%02d", $m);
	$d = sprintf("%02d", $d);
	
	$date = "$y-$m-$d";
	
	$hour = '';
	$min = '';
	$sec = '';
	if (substr_count($time, ':') == 1)
	{
		$time = $time . ':00';
	}
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

function executeQuery($query, $type="")
{
	global $REQUEST_URI, $HTTP_HOST, $hostname, $dbuser, $dbpass, $database;
    $result = false;
	// connect and execute query
	if (isRemote())
	{
		$connection = new mysqli($hostname, $dbuser, $dbpass, $database);
		if ($connection->connect_errno) {
 	   		error_log("Connect failed: %s\n", $connection->connect_error);
    		exit();
		}
	}
	else
	{
		$connection = mysqli_connect("127.0.0.1", $dbuser, $dbpass) or die ("Unable to connect!");
	}
	$connection->query($query);

	if ($type == "CREATE")
	{
		return 1;
	}
	if ($type == 'UPDATE')
	{
		return 1;
	}
	if ($type == "INSERT")
	{
		$ID = $connection->insert_id;
		return $ID;
	}

	if (!$result) // most likely a select statement
	{
		error_log($query);
		error_log($result->error);

		if (david())
		{
			bigEcho("Error in query: $query\n$HTTP_HOST$REQUEST_URI", 1, "dneff@CustomHostingTools.com");
			error_log($query);
			die;
		}
		else
			error_log("We're sorry, there was problem with the last operation.  Please try again.<br>");
	}
	return 1;
}

/* -------- getResult() -------------

Description:
Call getResult to execute an SQL statement and return the
result set.
--------------------------------------*/
function getResult($query, $handleError=false)
{
	global $REQUEST_URI,$HTTP_HOST, $hostname, $dbuser, $dbpass, $database;

	// connect and execute query
	if (isRemote())
	{
		$connection = mysqli_connect($hostname, $dbuser, $dbpass) or die ("Unable to connect!");
	}
	else
	{
		$connection = mysqli_connect("127.0.0.1", $dbuser, $dbpass) or die ("Unable to connect!");
	}

	$databaseObj = mysqli_select_db($connection, $database);
	$result = mysqli_query($connection, $query);
	if (!$result)
	{
		if (!$handleError)
		{
		}
		else
		{
			return false;
		}
	} 

	return $result;
}


function checkResult($result)
{
	if ($result)
	{
		if (mysqli_num_rows($result) > 0)
		{
			return true;
		}
	}
	return false;
}

function sendMail($fromName, $fromEmail, $toEmail, $subject, $msg, $toName="", $content_type="html")
{
//die('sendMail');
    $headers = '';

	if (empty($fromName))
	{
		$fromName = "CHT Services";
	}


	if (!empty($fromName) && !empty($fromEmail) && !empty($toEmail) &&!empty($subject))
	{
		$from_name = $fromName;
		$from_address = $fromEmail;
		
		$to_name = empty($toName) ? $toEmail : $toName;
		$to_address = "$toName <$toEmail>";
		
		$message = $msg;

		$headers .= "MIME-Version: 1.0\n";
		$headers .= "Content-type: text/" . $content_type . "; charset=iso-8859-1\n";
		$headers .= "From: ".$from_name." <".$from_address.">\n";
		$headers .= "Reply-To: ".$from_name." <".$from_address.">\n";
		$headers .= "X-Priority: 3\n";
		$headers .= "X-MSMail-Priority: Low\n";
		$headers .= "X-Mailer: iCEx Networks HTML-Mailer v1.0";
		$res = mail($to_address, $subject, $message, $headers, "-fdneff@customhostingtools.com");
		return $res;
	}

	
	return false;
} 

//function getHTMLPart($startString, $endString, $sourceString)
//{
//	$startStrLen = strlen($startString);
//	$stpos = strpos($sourceString, $startString) + $startStrLen;
//	$endpos = strpos($sourceString, $endString, $stpos);
//	$subject = substr($sourceString, $stpos, $endpos-$stpos);
//	return $subject;
//}
//

//$stdin = fopen('php://stdin', 'r'); // use for delivered version
//$stdin = file_get_contents("php://stdin"); // use for delivered version
$stdin = fopen($testFile, 'r'); // use for local version
$ftpdir = $comsDir . "/ftp"; // neff

//$stdin = fopen($ftpdir . '/test.txt', 'r'); // neff

$message_head = array();
$message_body = "";
$msg = '';
$cnt = 0;
$inthereads = false;
$datestr=strftime('%F %T');
$hourlyTargets = '';

$ef = fopen($localLogPath . '/Readings.log', 'a+');
fwrite($ef, "New Message Received\n" . $datestr . "\n");

$err = "-- Process Log --";
$workOrderStr = '<!-- work order -->';
$startGrabbingAlarm = false;
$alarmData = '-- alarm description --';

while (!feof($stdin)) // LOCAL VERSION
//while ($line = fgets($stdin)) // use for delivered version
{
	$line = fgets($stdin, 500); // LOCAL VERSION

	$line = trim($line);
	if (substr($line, 0, 1) == '>')
	{
			$line = substr($line, 1);
	}
	$line = trim($line);
	//echo('<br />' . $line); // neff

    // this is a data line for a process reading
	if (strpos(strtolower($line), "--flowline") !== false)
	{
		$processType =  'FlowLine';
	}
	if (strpos(strtolower($line), "--samplepoint") !== false)
	{
		$processType =  'SamplePoint';
	}
	
	if (strpos(strtolower($line), "subject: alarm") !== false)
	{
		$alarmData = '';
		$processType =  'flowAlarm';
	}
	
	if (strpos(strtolower($line), 'work order') !== false)
	{
		fwrite($ef, "set type to workorder\n");
	
		$processType =  'WorkOrder';
	}
	
	if (strpos(strtolower($line), 'profile change confirmation') !== false)
	{
		echo('<h3>setting process type</h3>');
		$processType =  'TargetConfirmation';
		$startWriting = false;
	}
	
	if ($processType ==  'TargetConfirmation') // neff: next several lines
	{
		if ( strpos($line, ',') > 0 )
		{
			// get monitor id from this line
			$monitorID = substr( $line, 0, strpos($line, ',') );
			if (!isRemote())
    			echo('<h3>getting monitor id: ' . $monitorID . '</h3>');
		}
		if ( strpos($line, 'Monday') !== false )
		{
			if (empty($monitorID)) continue;
			
			$query = "SELECT hourlyTargets FROM processTargetHistory WHERE monitorID='$monitorID' ORDER BY date DESC LIMIT 1";
			$targetRes = getResult($query);
			if (checkResult($targetRes))
			{
				// get target values
				$targetLine = mysqli_fetch_assoc($targetRes);
				extract($targetLine);
				$targetsArray = unserialize($hourlyTargets);
				showArray($targetsArray);
				/*				
				00:00 = 0.24
				06:00 = 1
				09:00 = 2
				12:00 = 3
				16:00 = 4
				21:00 = 5
				*/
				$activeTarget = $targetsArray['00:00'];
				//echo("<h3>initial $activeTarget = " . $targetsArray['00:00'] . ";</h3>");
			}
			else
			{
				continue;
			}
			//echo('<br />setting start writing to true');

			$startWriting = true;
			$hour = -1;
			continue; // next loop we start reading the values
		}
		if ($startWriting)
		{
			if (strpos($line, 'GPH') === false)
			{
				$startWriting = false;
				continue;
			}
			$hour++;
			$hr = sprintf("%02d",$hour) . ':00';
			//echo("<h3>$hr</h3>");
			if ( !empty( $targetsArray[$hr] ) )
				$activeTarget = $targetsArray[$hr];
				
			//echo("<h3>line 271: $activeTarget = " . $targetsArray[$hr] . ";</h3>");
			if (empty($monitorID)) continue;
			
			
			$target = substr($line, 0, strpos($line, ' '));
			//echo('<br />target in file: ' . $target . '  --  activeTarget: ' . $activeTarget);
			if ($target != $activeTarget)
			{
				// report a mismatch
				$startWriting = false;
				$htmlOut = "<h3>Process Targets Update Failed for $monitorID</h3>";
				$htmlOut .= "<h4>COMS Target for hour $hr is $activeTarget.  The monitor reported a target of $target for this register.</h4>";
				sendMail("COMS System", "noreply@neffhost.com", 'eccl411.12@gmail.com', "Monitor Target Update Failed", $htmlOut, "Jim Frederick");
				sendMail("COMS System", "noreply@neffhost.com", 'dneff68@gmail.com', "Monitor Target Update Failed", $htmlOut, "David Neff");
				//echo("<h1>mismatch: $target != $activeTarget</h1>");
			}
			else
			{
				// echo('<br />match');
			}
			
		}
	} // neff end
	elseif ($processType == 'WorkOrder')
	{
		$line = str_replace(chr(13), "", $line);
		$line = str_replace(chr(10), "", $line);
		$workOrderStr .= $line;
		if (strpos(strtolower($line), '</html>') !== false) $processType = '';
		continue;
	}		
	elseif ($processType == 'flowAlarm')
	{
		if ( (substr($line, 0, 1) == '"') && (strpos($line, '"', 2) > 1) && (strpos($line, ':', 2) > 1))
		{
			$startGrabbingAlarm = true;
			list($monitorID, $datetime, $value) = explode(',', $line);
            if (!empty($value) && !empty($datetime) && !empty($monitorID))
            {
				list($date, $time) = explode(' ', $datetime);

				if (strpos($date, '-') !== false)
				{
					list($m, $d, $y) = explode('-', $date);
				}
				else
				{
					list($m, $d, $y) = explode('/', $date);
				}

				// some monitors have delivered a time with seconds > 59.  We'll check and fix this now
				list($hour, $min, $sec) = explode(':', $time);
				$sec = $sec > 59 ? 59 : $sec;
				$min = $min > 59 ? 59 : $min;
				$time = "$hour:$min:$sec";
				
				$monitorID = trim($monitorID);
				$monitorID = str_replace('"', '', $monitorID);
				$value = str_replace("'", "''", $value);

				$alarmID = executeQuery("INSERT INTO flowAlarm (monitorID, date, description) VALUES ('$monitorID', '$y-$m-$d $time', '$value')", 'INSERT');
			}
			continue;
		}
		elseif ($startGrabbingAlarm)
		{
			$line = str_replace(chr(13), "", $line);
			$line = str_replace(chr(10), "", $line);
			$alarmData .= $line;
			continue;
		}
	}
	
	elseif (strpos($line, chr(9)) !== false)
	{
		if ($processType == 'SamplePoint')
		{
			list($monitorID, $datetime, $flowLineID, $samplePointID, $ppm, $temperature) = explode(chr(9), $line);
			$temperature = trim($temperature);
			list($date, $time) = explode(' ', $datetime);
			$date = str_replace('-', '/', $date);
			//error_log("date -- $date");

			$newDateTime = roundToNextQuarterHour($datetime);
			//error_log("new date - $newDateTime");
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
				executeQuery($query, 'UPDATE');
			}
			else
			{
				// add value for this quarter
				$query = "INSERT INTO processData (monitorID, flowLineID, samplePointID, date, ppm, temperature) VALUES ('$monitorID', '$flowLineID', '$samplePointID', '$newDateTime', $ppm, $temperature)";
				executeQuery($query, 'INSERT');
			}	
		}
		elseif ($processType == 'FlowLine')
		{
			// handling format for field hardware that couldn't get the format the way we wanted it
			if (strpos( $line, chr(9).'  ') )
			{
				$line = str_replace(chr(9).chr(9).chr(9), chr(9), $line); 				// strip tripple tabs
				$line = str_replace('  ', '', $line); 						// strip double spaces
				list($monitorID, $flowDatetime, $flowLineID, $flowRate, $level) = explode(chr(9), $line);
				
				// fix the date format
				$flowDatetime = str_replace('-', '/', $flowDatetime);  				// handle the case where dashes are used instead of slashes
				list($date, $time) = explode(' ', $flowDatetime);
				list($y, $m, $d) = explode('/', $date);
				$flowDatetime = "$m/$d/$y $time:00";		
				list($date, $time) = explode(' ', $flowDatetime);
			}
			else
			{
				// normal expected format
				list($monitorID, $flowDatetime, $flowLineID, $flowRate, $level) = explode(chr(9), $line);
				$flowDatetime = str_replace('-', '/', $flowDatetime);  				// handle the case where dashes are used instead of slashes
				list($date, $time) = explode(' ', $flowDatetime);	
			}
			
			$newFlowDateTime = roundToNextQuarterHour($flowDatetime);
			$query = "select monitorID from processData WHERE date = '$newFlowDateTime' AND monitorID='$monitorID' AND flowLineID='$flowLineID'"; //  AND (flowLineID=$flowLineID OR flowLineID='')
			$result = getResult($query);
			
			$addLevel = '';
			if ( checkResult($result) )
			{		
				if (!empty($level))
				{
					$addLevel = "level=$level, ";
				}
				// update existing with this more current reading for the quarter
				$query = "UPDATE processData SET $addLevel flowRate=$flowRate WHERE monitorID='$monitorID' AND flowLineID='$flowLineID' 
					AND date='$newFlowDateTime' AND flowLineID='$flowLineID' LIMIT 1";
				executeQuery($query, 'UPDATE');
			}
			else
			{
				if (!empty($level))
				{
					$addLevel1 = ",level";
					$addLevel2 = ",$level";
				}
				// no corresponding monitorID.  We may want to send an email alert here
				$query = "INSERT INTO processData (monitorID, flowLineID, date, flowRate $addLevel1) VALUES ('$monitorID', '$flowLineID', '$newFlowDateTime', $flowRate $addLevel2)";
				executeQuery($query, 'INSERT');
			}	
		}
	}
	// check to see if the line has a " as the first char and has a , and : somewhere else in the line
	elseif ( (substr($line, 0, 1) == '"') && (strpos($line, '"', 2) > 1) && (strpos($line, ':', 2) > 1))
	{
		if (strpos($line, '=') === false)
		{
                        // this is a data line for a tank Reading
                        list($monitorID, $datetime, $value) = explode(',', $line);
                        if (!empty($value) && !empty($datetime) && !empty($monitorID))
                        {
                                if (is_numeric($value))
                                {
                                        list($date, $time) = explode(' ', $datetime);

                                        if (strpos($date, '-') !== false)
                                        {
                                                list($m, $d, $y) = explode('-', $date);
                                        }
                                        else
                                        {
                                                list($m, $d, $y) = explode('/', $date);
                                        }

                                        // list($m, $d, $y) = explode('/', $date);

                                        // some monitors have delivered a time with seconds > 59.  We'll check and fix this now

                                        list($hour, $min, $sec) = explode(':', $time);
                                        $sec = $sec > 59 ? 59 : $sec;
                                        $min = $min > 59 ? 59 : $min;
                                        $time = "$hour:$min:$sec";

                                        $monitorID = str_replace('"', '', $monitorID);
                                        $value = empty($value) ? 0 : $value;
										
										// get the units that are stored in the monitor table.  This keeps record of the units programmed into the monitor
										// at the time of the reading, so later if the units switch we can still graph correctly.
										$units = 'Gallons';
										$res = getResult("select units from monitor where monitorID='$monitorID' LIMIT 1");
										if (checkResult($res))
										{
											$line = $res->fetch_assoc();
											extract($line);
										}
														
                                        executeQuery("INSERT INTO data (monitorID, date, value, units, processDate) VALUES ('$monitorID', '$y-$m-$d $time', $value, '$units', NOW())", 'INSERT');
                                        updateTankStats($monitorID, 3);
                                }
                        }
		}
	}
	$msg .= $line;
	$cnt++;
}
fclose($stdin);

if ($alarmData != '-- alarm description --')
{
	if (!empty($alarmID) && !empty($alarmData))
	{
		$alarmData = str_replace("'", "''", $alarmData);
		$alarmData = trim($alarmData);
		executeQuery("UPDATE flowAlarm SET description = CONCAT(description, '~$alarmData') WHERE alarmID=$alarmID LIMIT 1");
	}
}
elseif ($workOrderStr != '<!-- work order -->')
{
	$subject = getHTMLPart('Work Order ISSUED ', 'Date:', $workOrderStr);
	if (strlen($subject) > 50)
	{
		$subject = getHTMLPart('Work Order ISSUED ', 'From:', $workOrderStr);
	}
	
	$parts = explode(' ', $subject);
	$monitorID = end($parts);  	
	$html = str_replace("'", "''", $workOrderStr);
	executeQuery("INSERT INTO serviceHistory (monitorID, html) values ('$monitorID', '$html')", 'INSERT' );
}
else
{
	fwrite($ef, "\n");
	$msg = addslashes($msg);
	executeQuery("INSERT INTO rawreadings set date=NOW(), text = '$cnt: $msg'", 'INSERT');
}
fclose($ef);
bigEcho("Complete");
?>
