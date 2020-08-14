<?
session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once '/var/www/html/CHT/h202/h202Functions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';

if ($regen == 1)
{
	updateTankStats($monitorID, 35);
	header("location:tankStatsRaw.php?monitorID=$monitorID");
	die;
}

function echoRawData(&$result, $title='defalut', $width='', $units='Gallons', $diameter=1)
{
	if (mysqli_num_rows($result) <= 0)
	{
	  return;
	}
	if ($title == 'defalut')
	{
		echo "Total Rows: ".mysqli_num_rows($result);
	}
	else
	{
		echo "<span class='spinMedTitle'>$title</span>";	
	}
	mysql_data_seek($result, 0);
	$fieldCnt = mysql_num_fields($result);
	
	$width = empty($width) ? '' : "width='$width'";
	
	echo "<table border='1' cellspacing='0' cellpadding='3' bordercolor='#cccccc' $width>\n<tr>\n";
	$LastDoseFieldPos = -1;
	for ($i = 0; $i < $fieldCnt; $i++)
	{
	  $fn = mysql_field_name($result, $i);
	  if ($fn == 'Normalized' && $units == 'Inches')
	  {
			$LastDoseFieldPos = $i;
	  }
	  echo "<td align='center' class='spinTableTitle'>$fn</th>";
	}
	echo "</tr>";
	
	while ($line = mysql_fetch_array($result))
	{
			echo "<tr class='spinTableBarOdd'>";
			for ($i = 0; $i < $fieldCnt; $i++)
			{
				if ($LastDoseFieldPos == $i)
				{
				  $line[$i] = inchToGal($line[$i], $diameter);
				}
				echo "<td align='left'>$line[$i]</td>";
			}
			echo "</tr>";
	}
	echo "</table>";
	mysql_data_seek($result, 0);
}


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Statistics</title>
<link rel="stylesheet" TYPE="text/css" href="http://h202.customhostingtools.com/main.css" >
<SCRIPT LANGUAGE="javascript" TYPE="text/javascript" SRC='http://www.customhostingtools.com/lib/admin.js'></SCRIPT>
<script language="JavaScript" src="datetimepicker.js"></script>
<script src="http://www.customhostingtools.com/Scripts/AC_RunActiveContent.js" type="text/javascript"></script>
</head>

<body>
<?
if ($_SESSION['USERID'] == 'dneff' || $_SESSION['USERID'] == 'Jim')
{
	echo "<a href='javascript:window.location=\"tankStatsRaw.php?monitorID=$monitorID&regen=1\"'>Regenerate Stats</a><br>";
}

$res = getResult("SELECT DISTINCT readingDate as 'Reading Date', 
latestDose as 'Normalized', 
avgDose as 'Avg Dose', 
exceedcap as 'Exceed Capacity', 
nodose as 'No Dose', 
normal as 'Normal', 
unass as 'Unassociated', 
unmon as 'Unmonitored', 
noreading as 'No Reading', 
high as 'High', 
low as 'Low', 
high_low_message as 'Message', 
levelStat as 'Status', 
daysSinceLastReading as 'Days Since Last Reading' 
 from tankStats where monitorID='$monitorID' and readingDate >= DATE_ADD(NOW(), INTERVAL -200 DAY) ORDER BY readingDate DESC");
if (checkResult($res))
{
	echoResults($res, '', 1500);
}
?>

</body>
</html>