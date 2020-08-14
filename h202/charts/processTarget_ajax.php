<?
session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once '/var/www/html/CHT/h202/h202Functions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';

// $PROCESS_TARGET_ARRAY = '';

if (empty($PROCESS_TARGET_ARRAY))
{
	session_register('PROCESS_TARGET_ARRAY');
	$PROCESS_TARGET_ARRAY = array();
}

if (isset($removeItem))
{
	unset(	$PROCESS_TARGET_ARRAY[$removeItem] );
	reset($PROCESS_TARGET_ARRAY);
}

if ($action == 'setProcessTarget')
{
	executeQuery("UPDATE monitor SET processTarget=$target WHERE monitorID = '$SELECTED_TANK' LIMIT 1");
	logAction("Process Target changed to $target on $SELECTED_TANK_NAME");
	return;
}

if ($action == 'saveTargets')
{
	executeQuery("DELETE FROM processTargetHistory WHERE monitorID='$SELECTED_TANK' AND date >= '$PROCESS_START_DATE'");
	if (empty($PROCESS_TARGET_ARRAY))
	{
		$targets = 'none';
	}
	$targets = serialize($PROCESS_TARGET_ARRAY);
	
	$res = getResult("SELECT hourlyTargets as mvar FROM processTargetHistory WHERE monitorID='$SELECTED_TANK' AND date='$PROCESS_START_DATE' LIMIT 1");
	if (checkResult($res))
		$query = "UPDATE processTargetHistory SET monitorID='$SELECTED_TANK', date='$PROCESS_START_DATE', hourlyTargets='$targets' LIMIT 1";
	else
		$query = "INSERT INTO processTargetHistory (monitorID, date, hourlyTargets) VALUES ('$SELECTED_TANK', '$PROCESS_START_DATE', '$targets')";
	
	executeQuery($query);
	
	if ($submit == 'yes')
	{
		if (true) //david() || jim())
		{
			// build an array of every hour of the day, for an entire week.
			$dailyArray = array();
			$firstVal = 'none';
			$currentTarget = '-1';
			for ($i = 0; $i < 24; $i++)
			{
				$key = str_pad("$i", 2, '0', STR_PAD_LEFT) . ':00' ;
				if ( !empty($PROCESS_TARGET_ARRAY[$key]) )
				{
					if ($firstVal == 'none')
						$firstVal = $PROCESS_TARGET_ARRAY[$key];
					$currentTarget = $PROCESS_TARGET_ARRAY[$key];
				}
				$dailyArray[$key] = $currentTarget;
			}
			reset($dailyArray);
			foreach ($dailyArray as $key => $val)
			{
				if ($val == '-1')
					$dailyArray[$key] = $firstVal;
				else
					break;
			}
			
			$query = "SELECT rtuID FROM monitor WHERE monitorID='$SELECTED_TANK' AND rtuID !=  '' LIMIT 1";
			$res = getResult($query);
			if (checkResult($res))
			{
				$line = $res->fetch_assoc();
				extract($line);
				$titleString = "#I" . $rtuID;
				$slotcnt = 0;
				$w_count = 0;
				for ($dow = 1; $dow <= 7; $dow++)
				{
					reset($dailyArray);
					foreach ($dailyArray as $key => $val)
					{
						$val = str_pad("$slotcnt", 3, '0', STR_PAD_LEFT) . "=$val";
						$titleString .= ",W4" . $val;
						$slotcnt += 2;
						$w_count++;
						// if (strlen($titleString) > 240)
						if (($w_count >= 25) || (strlen($titleString) > 240))
						{
							$w_count = 0;
							sendMail("COMS System", "noreply@customhostingtools.com", 'tboxmaster@fastmail.fm', $titleString . '#', '', "TBox Master");
							//sendMail("COMS System", "noreply@customhostingtools.com", 'dneff68@gmail.com', $titleString . '#', '', "David Neff");
							sendMail("COMS System", "noreply@customhostingtools.com", 'eccl411.12@gmail.com', $titleString . '#', '', "Jim Frederick");
							$titleString = "#I" . $rtuID;
						}
					}
				}
				$titleString .= ",W1000=01";
				sendMail("COMS System", "noreply@customhostingtools.com", 'tboxmaster@fastmail.fm', $titleString . '#', '', "TBox Master");
				//sendMail("COMS System", "noreply@customhostingtools.com", 'dneff68@gmail.com', $titleString . '#', '', "David Neff");
				sendMail("COMS System", "noreply@customhostingtools.com", 'eccl411.12@gmail.com', $titleString . '#', '', "Jim Frederick");
			}
		}
	}
	
	logAction("Process Targets updated for tank $SELECTED_TANK for $PROCESS_START_DATE_FMT");
	return;
}

if ($action == 'setLag')
{
	$lagMinutes = ($hr * 60) + $min;
	$LAG_MINUTES = $lagMinutes;
	$res = getResult("SELECT samplePointID FROM processLagTime WHERE samplePointID='$SELECTED_SAMPLE_POINT' LIMIT 1");
	if (checkResult($res))
		$query = "UPDATE processLagTime SET lagMinutes = $lagMinutes WHERE samplePointID='$SELECTED_SAMPLE_POINT' LIMIT 1";
	else
		$query = "INSERT INTO processLagTime (samplePointID, lagMinutes) VALUES ('$SELECTED_SAMPLE_POINT', $lagMinutes)";

	//error_log($query);
	executeQuery($query);
	logAction("Process Lag Time updated to $hr" . 'hr  ' . $min . "min for tank $SELECTED_TANK");
	return;
}



if ($action == 'clear')
{
	session_register('PROCESS_TARGET_ARRAY');
	$PROCESS_TARGET_ARRAY = array();
	echo '-- No Targets Set --';
	return;
}

if (isset($getTarget))
{
	list($target, $lag) = explode(':',  $PROCESS_TARGET_ARRAY[$getTarget]);
	echo $target;
	return;
}


if (isset($_GET['hr']))
{
	//echo "$hr:$minute";
	//return;
	$PROCESS_TARGET_ARRAY["$hr"] = "$target"; 
}
	
$output = '';
$cnt = count($PROCESS_TARGET_ARRAY);
ksort($PROCESS_TARGET_ARRAY);

$i = 0;
$endval = '24:00';
while (list($key, $value) = each($PROCESS_TARGET_ARRAY)) 
{
	$target = $value;
	if ($i+1 != $cnt) // at the end of the array
	{
		list($nextKey, $nextVal) = each($PROCESS_TARGET_ARRAY);		
		$nextTarget = $nextVal;
		$endval = $nextKey;
		prev($PROCESS_TARGET_ARRAY);
	}
			
		$output .= "<tr id='$key' onclick=\"document.getElementById('hr').value='$key'; document.getElementById('target').value='$target';this.setAttribute('class', 'spinTableBarEven');document.getElementById('target').focus();\">
  <td>$key - $endval</td>
  <td>$target</td>
  <td><a href='javascript:clearSingleTarget(\"$key\")'>clear</a></td>
</tr>\n";
	$i++;
}

if ($cnt > 1)
{
	// get the last array element
	krsort($PROCESS_TARGET_ARRAY);
	list($key, $value) = each($PROCESS_TARGET_ARRAY);
	list($target, $holder) = explode(':', $value);

		$output .= "<tr id='$key' onclick=\"document.getElementById('hr').value='$key'; document.getElementById('target').value='$target';this.setAttribute('class', 'spinTableBarEven')\">
	  <td>$key - 24:00</td>
	  <td>$target</td>
	  <td><a href='javascript:clearSingleTarget(\"$key\")'>clear</a></td>
	</tr>\n";
}

// set the output with the full table
$output = "<table width='100%' border='1' cellspacing='0' cellpadding='6'>
<tr>
  <td width='40%'>Time</td>
  <td width='40%'>Target</td>
  <td width='20%'>&nbsp;</td>
</tr>
$output
</table>";

echo $output;


?>