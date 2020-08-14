<?
session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once '/var/www/html/CHT/h202/h202Functions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Graph</title>
<link rel="stylesheet" TYPE="text/css" href="http://h202.customhostingtools.com/main.css" >
<SCRIPT LANGUAGE="javascript" TYPE="text/javascript" SRC='http://www.customhostingtools.com/lib/admin.js'></SCRIPT>
<script language="JavaScript" src="datetimepicker.js"></script>
<script src="http://www.customhostingtools.com/Scripts/AC_RunActiveContent.js" type="text/javascript"></script>
</head>

<script language="javascript">AC_FL_RunContent = 0;</script>
<script language="javascript"> DetectFlashVer = 0; </script>
<script src="AC_RunActiveContent.js" language="javascript"></script>
<script language="JavaScript" type="text/javascript">
<!--
var requiredMajorVersion = 9;
var requiredMinorVersion = 0;
var requiredRevision = 45;
-->
</script>
<body>
<?

if ($REQUEST_METHOD == 'POST')
{
	
}

if (empty($SELECTED_TANK))
{
	session_register('SELECTED_TANK');
}

$SELECTED_TANK = empty($tankID) ? $SELECTED_TANK : $tankID;
$query = "
select s.siteLocationName, t.targetDosage, t.tankName, t.tankID, m.monitorID, m.units, t.diameter, t.capacity, p.value, t.concentration
		from monitor m, tank t, site s, product p
		where 
		t.monitorID=m.monitorID and 
		m.siteID = s.siteID and
		t.tankID='$SELECTED_TANK' and
t.prodID = p.prodID";

$res = getResult($query);
if (checkResult($res))
{
	$line = $res->fetch_assoc();
	extract($line);
}

include "graphBanner.php";

?>
<table width='600' class="spinNormalText">
<tr>
<form name="dateForm" action="tankGraph.php" method="post">
	<td colspan="3">
		<div align="left">Start Date:
					<input readonly name='startDate' id="startDate" type="text" size="10" value="<?=$startDate?>">
					<a href="javascript:NewCal('startDate','yyyymmdd')">
					<img src="/images/calbtn.gif" width="16" height="16" border="0" alt="Pick a start date"></a>
					<input type="button" value="Set Start Date" onclick="document.dateForm.submit()" />		
		</div>
	</td>
</form>
</tr>
</table>

<?

if (empty($VARIANCE_DATA) || empty($VARIANCE_TITLE) || empty($VARIANCE_TARGET_DOSE) || empty($VARIANCE_DOSE))
{
	session_register('VARIANCE_DATA');
	session_register('VARIANCE_TITLE');
	session_register('VARIANCE_TARGET_DOSE');
	session_register('VARIANCE_DOSE');
}


$VARIANCE_DATA = '<row><string>Tank Level Gallons</string>';
$VARIANCE_TITLE = "\n<row><null/>";
$VARIANCE_DOSE = '<row><string>Normalized Dose</string>';
$VARIANCE_TARGET_DOSE = $targetDosage > 0 ? "<row><string>Target Dose ($targetDosage gallons)</string>" : '';
$debug = '';

//if (david()) generateStats($SELECTED_TANK);

$prevDose = 0;

$units = 'Gallons';
$ures = getResult("SELECT m.units, t.diameter FROM monitor m, tank t WHERE t.monitorID=m.monitorID and m.monitorID = '$monitorID' LIMIT 1");
if (checkResult($ures))
{
	$uline = mysql_fetch_assoc($ures);
	extract($uline);
}

if (!empty($startDate))
{
	// set the value of $i to go back more than 11 days
	$daysRes = getResult("SELECT DATEDIFF(  NOW(), '$startDate' ) as daysAgo");
	$daysLine = mysql_fetch_assoc($daysRes);
	extract($daysLine);
	$stopDay = max(0, $daysAgo - 11);
}
else
{
	$daysAgo = 11;
	$stopDay = 0;
}

//die("daysAgo: $daysAgo   -     stopDay: $stopDay");
$flag = true;
if (david())
{
	$daysAgo++;
	$flag = false;
	ddie("$daysAgo");
}

for ($i = $daysAgo; $i >= $stopDay; $i--)
{
	$doseOut = getDose($monitorID, $i, $debug);

	$res = getResult("SELECT cast(readingDate as date) as readingDateVal, avgDose FROM tankStats WHERE monitorID='$monitorID' and 
					 cast(readingDate as date) = DATE_ADD( cast( NOW() AS date ) , INTERVAL -$i DAY )");

	ddie('here');
	if (checkResult($res))
	{
		$line = $res->fetch_assoc();
		extract($line);
		
		
		if (david())
		{
			
			// override targetDosage with the history for this day
			$histRes = getResult("SELECT targetDose as targetDosage FROM tankHistory WHERE date = '$readingDateVal' and monitorID='$monitorID'");
			bigecho($histRes);
			if (checkResult($histRes))
			{
				$histLine = mysql_fetch_assoc($histRes);
				extract($histLine);
			}
		}
	}
	else
	{
		$status = checkTankStatus($monitorID);
		list($statkey, $status) = explode(',', $status);
		if ($statkey == 'NoReading')
		{
			$prevAvgRes = getResult("SELECT avgDose as doseOut FROM tankStats WHERE monitorID='$monitorID' ORDER BY readingDate DESC");
			if (checkResult($prevAvgRes))
			{
				$prevAvgLine = mysql_fetch_assoc($prevAvgRes);
				extract($prevAvgLine);
			}
		}
	}
	
	if ($statkey == 'NoReading')
	{
		// doseOut was set by the last average  
	}
	elseif ($doseOut < 0)
	{
		if ($prevDose > 0)
		{	
			// Tank volume has increased. Check for delivery
			$deliveryWasMade = false;
			$DelDaysAgo = $i + 1;
			
			$query = "SELECT d.deliveryDate
						FROM delivery d, deliveryTanks dt
						WHERE d.deliveryID = dt.deliveryID
						AND (
							d.deliveryDate = DATE_ADD( cast( NOW() AS date ) , INTERVAL -$DelDaysAgo DAY ) or
							d.deliveryDate = DATE_ADD( cast( NOW() AS date ) , INTERVAL -$i DAY )
							)
						AND dt.monitorID = '$monitorID'";  				


			$res = getResult($query);
			
			if (checkResult($res))
			{
				$deliveryWasMade = true;
			}
		
			if ( $deliveryWasMade)
			{
				$doseOut = $avgDose;
			}
		}
		else
		{
			// no previous dose.  Set to 0
			$doseOut = 0;
		}
	}
	elseif ($doseOut == -1)
	{
		$doseOut = $avgDose; //$prevDose; // dose returned 0.  Use last valid dose
	}
	else
	{
		$prevDose = $doseOut;
	}
	
	if ($flag)
	{
		$VARIANCE_DOSE .= "\n<number>$doseOut</number>";
		$VARIANCE_TARGET_DOSE .= $targetDosage > 0 ? "\n<number>$targetDosage</number>" : '';
	}
	else
	{
		$flag = true;
	}
}

for ($i = $daysAgo; $i >= $stopDay; $i--)
{
	$res = getResult("SELECT DATE_FORMAT(date, '%d') as 'day', value from data 
	where monitorID='$monitorID' and cast(date as date) = DATE_ADD(cast(NOW() as date), INTERVAL -$i DAY) ORDER BY date DESC LIMIT 1");
	if (checkResult($res))
	{
		$line = $res->fetch_assoc();
		extract($line);

		$value = $units == 'Inches' ? inchToGal($value, $diameter) : $value;


		$VARIANCE_DATA .= "\n<number>$value</number>";
		$VARIANCE_TITLE .= "\n<string>$day</string>";
	}
	else
	{
		// no reading on this day
		$tmpres = getResult("select DATE_FORMAT(DATE_ADD(NOW(), INTERVAL -$i DAY), '%d') as day");
		
		$VARIANCE_DATA .= "\n<number>0</number>";
		$VARIANCE_TITLE .= "\n<string>n/r</string>";
	}
}
$VARIANCE_DATA .= "</row>";
$VARIANCE_TITLE .= '</row>';
$VARIANCE_DOSE .= '</row>';
$VARIANCE_TARGET_DOSE .= '</row>';

$status = checkTankStatus($monitorID);
list($status,$statusMsg) = explode(',', $status);

$pid = getmypid();
$t 	= time();
$rand = "$pid.$t"; 

//$V = htmlentities($VARIANCE_TITLE);
//echo("<br>$V<br>");
//die;
?>

<?php if (david()) : ?>

<script language="JavaScript" type="text/javascript">
<!--
if (AC_FL_RunContent == 0 || DetectFlashVer == 0) {
	alert("This page requires AC_RunActiveContent.js.");
} else {
	var hasRightVersion = DetectFlashVer(requiredMajorVersion, requiredMinorVersion, requiredRevision);
	if(hasRightVersion) { 
		AC_FL_RunContent(
			'codebase', 'http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,45,0',
			'width', '725',
			'height', '250',
			'scale', 'noscale',
			'salign', 'TL',
			'bgcolor', '#777788',
			'wmode', 'opaque',
			'movie', 'charts',
			'src', 'charts',
			'FlashVars', 'library_path=charts_library&xml_source=variance.php%3FtankID%3D<?=$monitorID?>%26uniqueID%3D<?=$rand?>', 
			'id', 'chart1',
			'name', 'chart1',
			'menu', 'true',
			'allowFullScreen', 'true',
			'allowScriptAccess','sameDomain',
			'quality', 'high',
			'align', 'middle',
			'pluginspage', 'http://www.macromedia.com/go/getflashplayer',
			'play', 'true',
			'devicefont', 'false'
			); 
	} else { 
		var alternateContent = 'This content requires the Adobe Flash Player. '
		+ '<u><a href=http://www.macromedia.com/go/getflash/>Get Flash</a></u>.';
		document.write(alternateContent); 
	}
}
// -->
</script>
<noscript>
	<P>This content requires JavaScript.</P>
</noscript>




<? else : ?>

<script type="text/javascript">
AC_FL_RunContent( 'codebase','http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0','width','725','height','250','id','charts','align','','src','charts?library_path=charts_library&xml_source=variance.php%3FtankID%3D<?=$monitorID?>%26uniqueID%3D<?=$rand?>','quality','high','bgcolor','#ffffff','name','charts','swliveconnect','true','pluginspage','http://www.macromedia.com/go/getflashplayer','movie','charts?library_path=charts_library&xml_source=variance.php%3FtankID%3D<?=$monitorID?>%26uniqueID%3D<?=$rand?>' ); //end AC code
</script>
<noscript>
<OBJECT classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"
	codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0" 
	WIDTH="725" 
	HEIGHT="250" 
	id="charts" 
	ALIGN="">
<PARAM NAME=movie VALUE="/charts.swf?library_path=charts_library&xml_source=variance.php%3FtankID%3D<?=$monitorID?>%26uniqueID%3D<?=$rand?>">
<PARAM NAME=quality VALUE=high>
<PARAM NAME=bgcolor VALUE=#ffffff>

<EMBED src="/charts.swf?library_path=charts_library&xml_source=variance.php%3FtankID%3D<?=$monitorID?>%26uniqueID%3D<?=$rand?>" 
       quality=high 
       bgcolor=#ffffff  
       WIDTH="725" 
       HEIGHT="250" 
       NAME="charts" 
       ALIGN="" 
       swLiveConnect="true" 
       TYPE="application/x-shockwave-flash" 
       PLUGINSPAGE="http://www.macromedia.com/go/getflashplayer">
</EMBED>
</OBJECT></noscript>
<hr />
<script type="text/javascript">
AC_FL_RunContent( 'codebase','http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0','width','725','height','250','id','charts','align','','src','charts?library_path=charts_library&xml_source=doseData.php%3FtankID%3D<?=$monitorID?>%26uniqueID%3D<?=$rand?>','quality','high','bgcolor','#ffffff','name','charts','swliveconnect','true','pluginspage','http://www.macromedia.com/go/getflashplayer','movie','charts?library_path=charts_library&xml_source=doseData.php%3FtankID%3D<?=$monitorID?>%26uniqueID%3D<?=$rand?>' ); //end AC code
</script><noscript><OBJECT classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"
	codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0" 
	WIDTH="725" 
	HEIGHT="250" 
	id="charts" 
	ALIGN="">
<PARAM NAME=movie VALUE="charts.swf?library_path=charts_library&xml_source=doseData.php%3FtankID%3D<?=$monitorID?>%26uniqueID%3D<?=$rand?>">
<PARAM NAME=quality VALUE=high>
<PARAM NAME=bgcolor VALUE=#ffffff>

<EMBED src="charts.swf?library_path=charts_library&xml_source=doseData.php%3FtankID%3D<?=$monitorID?>%26uniqueID%3D<?=$rand?>" 
       quality=high 
       bgcolor=#ffffff  
       WIDTH="725" 
       HEIGHT="250" 
       NAME="charts" 
       ALIGN="" 
       swLiveConnect="true" 
       TYPE="application/x-shockwave-flash" 
       PLUGINSPAGE="http://www.macromedia.com/go/getflashplayer">
</EMBED>
</OBJECT></noscript>

<?php endif; ?>

<!-- IFrame for stats data -->
<p class="spinLargeTitle">Statistics</p>
<iframe scrolling="auto" width="650" height="250" src="/tankStatsRaw.php?monitorID=<?=$monitorID?>&" ></iframe><br />
<p class="spinLargeTitle">Raw Readings</p>
<iframe scrolling="auto" width="650" height="250" src="/tankRawData.php?monitorID=<?=$monitorID?>&startDate=<?=$startDate?>" ></iframe><br />

</body>
</html>
