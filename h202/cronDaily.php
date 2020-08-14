<?
session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once '/var/www/html/CHT/h202/h202Functions.php';
include_once '/var/www/html/CHT/lib/chtFunctions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';
error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors', 'On');
error_log('-- CRON DAILY RUN --');

// gen stats for all tanks
$res = getResult("select m.monitorID from monitor m where  m.status != 'Temporary Shutdown' order by m.monitorID");
if (checkResult($res))
{
	while ($line = $res->fetch_assoc())
	{
		extract($line);
		updateTankStats($monitorID, 3);
	}
}


// loop through all temp shutdown
$tanksArray = array();
$res = getResult("select m.* from monitor m where  m.status = 'Temporary Shutdown' and no_tsAlert=0 order by m.monitorID");
if (checkResult($res))
{
	while ($line = $res->fetch_assoc())
	{
		extract($line);
		// get last three readings from data
		$query = "SELECT  DISTINCT cast(date as date) as date, value FROM data WHERE monitorID='$monitorID' and cast(date as date) >= DATE_ADD(cast(NOW() as date), INTERVAL -7 day) ORDER BY date DESC LIMIT 4";
		// "SELECT cast(date as date) as date, value FROM data WHERE monitorID='$monitorID' ORDER BY date DESC LIMIT 4
		$dataRes = getResult($query);
		if (checkResult($dataRes))
		{
			$pass = 1;
			$prevVal = -1;
			$values = '';
			$cnt = 0;
			while($dataLine = mysqli_fetch_assoc($dataRes))
			{
				$cnt++;
				extract($dataLine);
				$values .= "<b>Date:</b> $date  <b>Reading:</b> $value<br>";
				if ($prevVal == -1)
				{
					$prevVal = $value;
					continue;
				}
				
				if ($value <= $prevVal)
				{
					$pass = 0;
				}
				$prevVal = $value;
			}
			
			if ($pass == 1 && $cnt == 4)
			{
				$tanksArray["$monitorID"] = $values;
			}
		}
	}
	
	$cnt = 0;
	$htmlOut = 'The following tanks are on Temporary Shutdown yet have dosed on their last three readings:<hr><br>';
	foreach ($tanksArray as $monitorID=>$readings)
	{
		$res = getResult("SELECT tankName FROM tank WHERE monitorID='$monitorID' LIMIT 1");
		if (checkResult($res))
		{
			$line = $res->fetch_assoc();
			extract($line);
			$htmlOut .= "<strong>$tankName</strong><br>$readings<br><br>";
			$cnt++;
		}
	}
	
	if ($cnt > 0)
	{
		//sendMail("COMS System", "dneff@customhostingtools.com", 'dneff68@gmail.com', "Tanks Dosing on Temporary Shutdown", $htmlOut, "David Neff");
		sendMail("COMS System", "noreply@customhostingtools.com", 'mengram@h2o2.com', "Tank(s) Dosing on Temporary Shutdown", $htmlOut, "Michael Engram");
		sendMail("COMS System", "noreply@customhostingtools.com", 'rjoseph@h2o2.com', "Tank(s) Dosing on Temporary Shutdown", $htmlOut, "Ricky Joseph");
		sendMail("COMS System", "noreply@customhostingtools.com", 'mfoundoulis@h2o2.com', "Tank(s) Dosing on Temporary Shutdown", $htmlOut, "Mike Foundoulis");
		sendMail("COMS System", "noreply@customhostingtools.com", 'jfragoso@h2o2.com', "Tank(s) Dosing on Temporary Shutdown", $htmlOut, "Jose Fragoso");
		echo($htmlOut);
	}
}