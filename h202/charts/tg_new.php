<?
session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once '/var/www/html/CHT/h202/h202Functions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';
include("/var/www/html/CHT/h202/FusionCharts/Code/PHP/Includes/FusionCharts.php");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Graph</title>
<link rel="stylesheet" TYPE="text/css" href="http://h202.customhostingtools.com/main.css" >
<SCRIPT LANGUAGE="javascript" TYPE="text/javascript" SRC='http://www.customhostingtools.com/lib/admin.js'></SCRIPT>
<script language="JavaScript" src="/datetimepicker.js"></script>
<SCRIPT LANGUAGE="Javascript" SRC="/FusionCharts/Code/FusionCharts/FusionCharts.js"></SCRIPT>
<SCRIPT LANGUAGE="Javascript" SRC="/FusionCharts/Code/FusionCharts/FusionChartsExportComponent.js"></SCRIPT>
</head>
<body>
<?
if (empty($DEV_PLUS_SERIES) || empty($GRAPH_TANK_NAME) || empty($VARIANCE_DATA) || empty($VARIANCE_TITLE) || empty($VARIANCE_TARGET_DOSE) || empty($VARIANCE_DOSE) || empty($targetDosage) || empty($DEV_PLUS))
{
	session_register('VARIANCE_DATA');
	session_register('VARIANCE_TITLE');
	session_register('VARIANCE_TARGET_DOSE');
	session_register('VARIANCE_DOSE');
	session_register('GRAPH_TANK_NAME');
	session_register('GRAPH_CATEGORIES');
	session_register('DOSE_TARGET');
	session_register('DEV_PLUS');
	session_register('DEV_MINUS');
	session_register('DEV_MINUS_SERIES');
	session_register('DEV_PLUS_SERIES');
	session_register('targetDosage');
}
	
if (empty($SELECTED_TANK))
{
	session_register('SELECTED_TANK');
}

$SELECTED_TANK = empty($tankID) ? $SELECTED_TANK : $tankID;
$query = "
select s.siteLocationName, t.targetDosage, t.targetDaily, t.tankName, t.tankID, m.monitorID, m.units, t.diameter, t.usableVolume, t.capacity, p.value, t.concentration
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
	if (!empty($targetDaily))
	{
		$dow = date('N');
		$targets = unserialize($targetDaily);
		$targetDosage = $targets[$dow];
	}
}

// override targetDosage with the history for this day
$query = "SELECT targetDose as targetDosage, targetDaily FROM tankHistory WHERE monitorID='$monitorID' order by date desc";
$histRes = getResult($query);
if (checkResult($histRes))
{
	$histLine = mysqli_fetch_assoc($histRes);
	extract($histLine);
	if (!empty($targetDaily))
	{
		$targets = unserialize($targetDaily);
		$dow = date('N', strtotime("-0 day"));  // day of week for this day
		$targetDosage = $targets[$dow];
	}
	
}

include "graphBanner.php";

?>
<table width='600' class="spinNormalText">
  <tr>
    <form name="dateForm" action="tankGraph.php" method="post">
      <td colspan="3"><div align="left">Start Date:
          <input readonly name='startDate' id="startDate" type="text" size="10" value="<?=$startDate?>">
          <a href="javascript:NewCal('startDate','yyyymmdd')"> <img src="/images/calbtn.gif" width="16" height="16" border="0" alt="Pick a start date"></a>
          <input type="button" value="Set Start Date" onclick="document.dateForm.submit()" />
        </div></td>
    </form>
  </tr>
</table>
<?



$GRAPH_CATEGORIES = '';
$VARIANCE_DATA = '';
$VARIANCE_DOSE = '';
$DOSE_TARGET = '';

$VARIANCE_TARGET_DOSE = $targetDosage > 0 ? "<row><string>Target Dose ($targetDosage gallons)</string>" : '<row><string>Target Dose (not set)</string>';
$debug = '';

$prevDose = 0;

$units = 'Gallons';
$ures = getResult("SELECT m.units, t.diameter FROM monitor m, tank t WHERE t.monitorID=m.monitorID and m.monitorID = '$monitorID' LIMIT 1");
if (checkResult($ures))
{
	$uline = mysqli_fetch_assoc($ures);
	extract($uline);
}

if (!empty($startDate))
{
	// set the value of $i to go back more than 30 days
	$daysRes = getResult("SELECT DATEDIFF(  NOW(), '$startDate' ) as daysAgo");
	$daysLine = mysqli_fetch_assoc($daysRes);
	extract($daysLine);
	$stopDay = max(0, $daysAgo - 30);
}
else
{
	$daysAgo = 30;
	$stopDay = 0;
}

$DEV_PLUS 	= 0;
$DEV_MINUS 	= 0;
$DEV_PLUS_SERIES 	= '';
$DEV_MINUS_SERIES 	= '';
$res = getResult("SELECT (deviation_plus + $targetDosage) AS DEV_PLUS, CAST($targetDosage - deviation_minus AS SIGNED) AS DEV_MINUS from tank WHERE tankID='$monitorID' LIMIT 1");
if (checkResult($res))
{
	$line = $res->fetch_assoc();
	extract($line);
}

$flag = false;
for ($i = $daysAgo; $i >= $stopDay; $i--)
{
	if ($flag==$false)
	{
		$i++; // add one day to get the prev dose for the day prior to this series range
	}
	
	// see if there was a value previous to today in history
	$histRes = getResult("SELECT targetDose as targetDosage, targetDaily FROM tankHistory WHERE monitorID='$monitorID' ORDER BY date LIMIT 1");
	if (checkResult($histRes))
	{
		$histLine = mysqli_fetch_assoc($histRes);
		extract($histLine);
		if (!empty($targetDaily))
		{
			$targets = unserialize($targetDaily);
			$dow = date('N', strtotime("-$i day"));  // day of week for this day
			$targetDosage = $targets[$dow];
		}
	}
	
	$doseOut = getDose($monitorID, $i, $debug);
	$res = getResult("SELECT cast(readingDate as date) as readingDateVal, avgDose FROM tankStats WHERE monitorID='$monitorID' and 
					 cast(readingDate as date) = DATE_ADD( cast( NOW() AS date ) , INTERVAL -$i DAY )");
	if (checkResult($res))
	{
		$line = $res->fetch_assoc();
		extract($line);
		// override targetDosage with the history for this day
		$query = "SELECT targetDose as targetDosage, targetDaily FROM tankHistory WHERE date <= '$readingDateVal' and monitorID='$monitorID' order by date desc";
		$histRes = getResult($query);
		if (checkResult($histRes))
		{
			$histLine = mysqli_fetch_assoc($histRes);
			extract($histLine);
			if (!empty($targetDaily))
			{
				$targets = unserialize($targetDaily);
				$dow = date('N', strtotime("-$i day"));  // day of week for this day
				$targetDosage = $targets[$dow];
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
				$prevAvgLine = mysqli_fetch_assoc($prevAvgRes);
				extract($prevAvgLine);
			}
		}
		
		// see what the previous targetDosage was in the history
		$histRes = getResult("SELECT targetDose as targetDosage, targetDaily FROM tankHistory 
								WHERE
									date <= DATE_ADD( cast( NOW() AS date ) , INTERVAL -$i DAY ) and
									monitorID='$monitorID' ORDER BY date DESC LIMIT 1");
		if (checkResult($histRes))
		{
			$histLine = mysqli_fetch_assoc($histRes);
			extract($histLine);
			if (!empty($targetDaily))
			{
				$targets = unserialize($targetDaily);
				$dow = date('N', strtotime("-$i day"));  // day of week for this day
				$targetDosage = $targets[$dow];
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
			
			// Commenting this out per Jim's request on Feb 5, 2010
			// $doseOut = 0;
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
	
	if ($flag)  // skip the output of the first loop.  The first loop was just to get the previous dose
	{
		$day = '';
		$res = getResult("SELECT DATE_FORMAT(date, '%d') as 'day' from data 
		where monitorID='$monitorID' and cast(date as date) = DATE_ADD(cast(NOW() as date), INTERVAL -$i DAY) ORDER BY date DESC LIMIT 1");
		if (checkResult($res))
		{
			$line = $res->fetch_assoc();
			extract($line);
		}
		$VARIANCE_DOSE .= "\n<set value='$doseOut' toolText='$doseOut' />";
		$DOSE_TARGET .= "\n<set value='$targetDosage' toolText='$targetDosage' />";
		$GRAPH_CATEGORIES .= "<category label='$day' />";
		
		
		$res = getResult("SELECT (deviation_plus + $targetDosage) AS DEV_PLUS, CAST($targetDosage - deviation_minus AS SIGNED) AS DEV_MINUS from tank WHERE tankID='$monitorID' LIMIT 1");
		if (checkResult($res))
		{
			$line = $res->fetch_assoc();
			extract($line);
		}
		
		if ($DEV_PLUS > 0)
		{
			$DEV_PLUS_SERIES .= "\n<set value='$DEV_PLUS' toolText='$DEV_PLUS' />"; 
		}
		if ($DEV_MINUS > 0)
		{
			$DEV_MINUS_SERIES .= "\n<set value='$DEV_MINUS' toolText='$DEV_MINUS' />"; 
		}
		
	}
	else
	{
		$flag = true;
	}
}

$GRAPH_CATEGORIES = "<categories>\n$GRAPH_CATEGORIES\n</categories>";
//$VARIANCE_DOSE = "<dataset seriesName='Dose Value'>$VARIANCE_DOSE</dataset>";
//$DOSE_TARGET = "<dataset seriesName='Dose Value'>$DOSE_TARGET</dataset>";

for ($i = $daysAgo; $i >= $stopDay; $i--)
{
	$res = getResult("SELECT DATE_FORMAT(date, '%d') as 'day', value from data 
	where monitorID='$monitorID' and cast(date as date) = DATE_ADD(cast(NOW() as date), INTERVAL -$i DAY) ORDER BY date DESC LIMIT 1");
	if (checkResult($res))
	{
		$line = $res->fetch_assoc();
		extract($line);

		$value = $units == 'Inches' ? inchToGal($value, $diameter) : $value;
		$VARIANCE_DATA .= "<set toolText='$value' color='009933' label='$day' value='$value' />";
		//$VARIANCE_DATA .= "<set label='$day' value='$value' />";
	}
	else
	{
		// no reading on this day
		//$tmpres = getResult("select DATE_FORMAT(DATE_ADD(NOW(), INTERVAL -$i DAY), '%d') as day");
		$VARIANCE_DATA .= "<set  toolText='$value' color='009933'  label='--' value='0' />";
	}
}

$status = checkTankStatus($monitorID);
list($status,$statusMsg) = explode(',', $status);

$varianceXML = "<chart  rotateValues='1' placeValuesInside='1'  showValues='1' registerWithJS='1' exportEnabled='1' exportAtClient='1' exportHandler='fcExporter1' exportFileName='$monitorID' caption='Tank Level Gallons' numberPrefix='' formatNumberScale='0'>";
$varianceXML .= $VARIANCE_DATA;
$varianceXML .= "</chart>";


$GRAPH_TANK_NAME = $tankName;
//Create the chart - Column 3D Chart with data contained in strXML
echo renderChart("/FusionCharts/Charts/Column3D.swf", "", $varianceXML, "Variance", 800, 300, false, false);
echo '<div id="fcexpDiv" align="left">FusionCharts Export Handler Component</div>';
echo renderChart("/FusionCharts/Charts/MSCombi2D.swf", "doseData_new.php", "", "Dose", 800, 300, false, false);
?>

<script type="text/javascript">

	//Render the export component in this
	//Note: fcExporter1 is the DOM ID of the DIV and should be specified as value of exportHandler
	//attribute of chart XML.
	var myExportComponent = new FusionChartsExportObject("fcExporter1", "/FusionCharts/Code/FusionCharts/FCExporter.swf");
	myExportComponent.debugMode = true;

	//Width and height
	myExportComponent.componentAttributes.width = '400';
	myExportComponent.componentAttributes.height = '60';
	//Customize the component properties
	myExportComponent.componentAttributes.fontFace = 'Arial';
	myExportComponent.componentAttributes.fontColor = '0372AB';
	myExportComponent.componentAttributes.fontSize = '12';
	//Button visual configuration
	myExportComponent.componentAttributes.btnWidth = '200';
	myExportComponent.componentAttributes.btnHeight= '25';
	myExportComponent.componentAttributes.btnColor = 'E1f5ff';
	myExportComponent.componentAttributes.btnBorderColor = '0372AB';
	//Button font properties
	myExportComponent.componentAttributes.btnFontFace = 'Verdana';
	myExportComponent.componentAttributes.btnFontColor = '0372AB';
	myExportComponent.componentAttributes.btnFontSize = '15';
	//Title of button
	myExportComponent.componentAttributes.btnsavetitle = 'Save the chart'
	myExportComponent.componentAttributes.btndisabledtitle = 'Waiting for export'; 
	//Render the exporter SWF in our DIV fcexpDiv
	myExportComponent.Render("fcexpDiv");
</script>
<!-- IFrame for stats data --><br />
<p class="spinLargeTitle">Raw Readings</p>
<iframe scrolling="auto" width="650" height="250" src="/tankRawData.php?monitorID=<?=$monitorID?>&startDate=<?=$startDate?>" ></iframe><br /><br />
<p class="spinLargeTitle">Statistics</p>
<iframe scrolling="auto" width="650" height="250" src="/tankStatsRaw.php?monitorID=<?=$monitorID?>&" ></iframe>

</body>
</html>
