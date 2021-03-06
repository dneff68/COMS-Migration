<?php
session_start();

if ($_SESSION['LOCAL_DEVELOPMENT']=='yes')
{
	include_once $_SESSION['SYSTEM_ROOT_PATH'] . '/GlobalConfig.php';
	include_once $_SESSION['SYSTEM_ROOT_PATH'] . '/h202Functions.php';
	include_once 'db_mysql.php';
	//include_once $_SESSION['SYSTEM_LIB_PATH'] . '/chtFunctions.php';	
	include($_SESSION['SYSTEM_ROOT_PATH'] . "FusionCharts/Code/PHP/Includes/FusionCharts.php");
}
else
{
	include_once '/var/www/html/CHT/h202/GlobalConfig.php';
	include_once '/var/www/html/CHT/h202/h202Functions.php';
	include_once 'chtFunctions.php';
	include_once 'db_mysql.php';
	include("/var/www/html/CHT/h202/FusionCharts/Code/PHP/Includes/FusionCharts.php");
}

$jsClose = '';

if (!isset($PROCESS_TARGET)) $PROCESS_TARGET = 0;
$invalidDate = false;

error_log('process graph');

if (empty($PROCESS_CATEGORIES) || empty($TEMPERATURE_DATASET))
{
	$_SESSION['PROCESS_CATEGORIES'] = '';
	$_SESSION['TEMPERATURE_DATASET'] = '';
	$_SESSION['DAYS_PLOTTED'] = '';
	$_SESSION['PPM_DATASET'] = '';
	$_SESSION['PROCESS_START_DATE'] = '';
	$_SESSION['PROCESS_END_DATE'] = '';
	$_SESSION['GRAPH_START_DATE'] = '';
	$_SESSION['LEFT_Y_MAX'] = '';
	$_SESSION['LEFT_Y_MIN'] = '';
	$_SESSION['FLOW_DATASET'] = '';
	$_SESSION['FLOW_AVERAGE'] = '';
	$_SESSION['PROCESS_TARGET_ARRAY'] = '';
	$_SESSION['PROCESS_START_DATE_FMT'] = '';
	$_SESSION['FLOW_TARGET'] = '';
	$_SESSION['LABEL_STEP'] = '';
	$_SESSION['FLOW_AVERAGE'] = '';
}
	
$SELECTED_TANK = '';

if (isset($_GET['monitorID']))
{
	$monitorID = $_GET['monitorID'];
	$SELECTED_TANK = $monitorID;
} 

if (!isset($SELECTED_TANK))
{
	$_SESSION['SELECTED_TANK'] = '';
	$_SESSION['SELECTED_TANK_NAME'] = '';
	$_SESSION['SELECTED_SAMPLE_POINT'] = '';
	$_SESSION['PROCESS_TARGET'] = '';
}

//$SELECTED_TANK = empty($monitorID) ? $SELECTED_TANK : $monitorID;
$PROCESS_START_DATE = isset($PROCESS_START_DATE) ? $PROCESS_START_DATE : '';

if (!empty($endDate))
{
	$PROCESS_START_DATE = $startDate;
	$PROCESS_END_DATE = $endDate;
	list($y, $m, $d) = explode('-', $PROCESS_START_DATE);
	$PROCESS_START_DATE_FMT = "$m/$d/$y";
}

if ($_POST)
{
	if ($PROCESS_START_DATE == $PROCESS_END_DATE)
	{
		// about to view one day.  Get the targets into the array
		$query = "SELECT hourlyTargets FROM processTargetHistory WHERE monitorID='$SELECTED_TANK' AND date <= '$PROCESS_START_DATE' ORDER BY date DESC LIMIT 1";
		$targetRes = getResult($query);
		if (checkResult($targetRes))
		{
			// get target values
			$targetLine = mysqli_fetch_assoc($targetRes);
			extract($targetLine);
			$PROCESS_TARGET_ARRAY = unserialize($hourlyTargets);
		}
		else
		{
			$PROCESS_TARGET_ARRAY = array();
		}
	}
	header('Location: ' . $_SESSION['ROOT_URL'] . 'charts/processGraph.php');
	exit;
}

if (empty($PROCESS_START_DATE))
{
	$query = "select date_add(date(now()), interval -7 day) as PROCESS_START_DATE, date(NOW()) as PROCESS_END_DATE";
	$res = getResult($query);
	$line = $res->fetch_assoc();
	extract($line);
	list($y, $m, $d) = explode('-', $PROCESS_START_DATE);
	$PROCESS_START_DATE_FMT = "$m/$d/$y";
}
// Get tank name
$SELECTED_TANK_NAME = getTankName($SELECTED_TANK);


// Get lag time if any
$LAG_MINUTES = 0;
$query = "SELECT lagMinutes FROM processData p, processLagTime lt 
WHERE p.samplePointID = lt.samplePointID and p.monitorID='$SELECTED_TANK' and lt.samplePointID <> '' and lt.lagMinutes > 0 LIMIT 1";
$res = getResult($query);
//bigecho($query);
if (checkResult($res))
{
	$line = $res->fetch_assoc();
	extract($line);
	$LAG_MINUTES = $lagMinutes;
}

$DAYS_PLOTTED = 7;
include "processGraphBanner.php";


$singleDayGraph = 0;

if (!empty($PROCESS_START_DATE))
{
	$usingDefaultStart = FALSE;
	// set the value of $i to go back more than $DAYS_PLOTTED days
	$daysRes = getResult("SELECT DATEDIFF(  NOW(), '$PROCESS_START_DATE' ) as daysAgo, DATEDIFF(  '$PROCESS_END_DATE', '$PROCESS_START_DATE' ) as daysPlotted");

	$daysLine = mysqli_fetch_assoc($daysRes);
	extract($daysLine);

	if ($PROCESS_END_DATE == $PROCESS_START_DATE) // this is actually 1 day, there just insn't a range 
	{
		$singleDayGraph = 1;
		$DAYS_PLOTTED = 1;
	}
	elseif ($daysPlotted < 0)
	{
		$invalidDate = 1;
		$PROCESS_START_DATE = '';
		$endDate = '';
	}
	else
	{
		$FLOW_AVERAGE = '';
		$DAYS_PLOTTED = $daysPlotted + 1;
		$stopDay = max(0, $daysAgo - $DAYS_PLOTTED + 1);
	}

}


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Process</title>
<link rel="stylesheet" TYPE="text/css" href="<?php echo $_SESSION['ROOT_URL']?>main.css" >
<SCRIPT LANGUAGE="javascript" TYPE="text/javascript" SRC='<?php echo $_SESSION['ROOT_URL']?>lib/admin.js'></SCRIPT>
<SCRIPT LANGUAGE="javascript" TYPE="text/javascript" SRC='<?php echo $_SESSION['ROOT_URL']?>lib/jquery.js'></SCRIPT>
<script language="JavaScript" src="<?php echo $_SESSION['ROOT_URL']?>datetimepicker.js"></script>
<SCRIPT LANGUAGE="Javascript" SRC="<?php echo $_SESSION['ROOT_URL']?>FusionCharts/Code/FusionCharts/FusionCharts.js"></SCRIPT>
<SCRIPT LANGUAGE="Javascript" SRC="<?php echo $_SESSION['ROOT_URL']?>FusionCharts/Code/FusionCharts/FusionChartsExportComponent.js"></SCRIPT>

<?php if (david() || jim()) : ?>
	<link rel="stylesheet" href="../ui_theme/themes/base/jquery.ui.all.css"> 
	<script src="<?php echo $_SESSION['ROOT_URL']?>ui_theme/ui/jquery.ui.core.js"></script> 
	<script src="<?php echo $_SESSION['ROOT_URL']?>ui_theme/ui/jquery.ui.widget.js"></script> 
	<script src="<?php echo $_SESSION['ROOT_URL']?>ui_theme/ui/jquery.ui.datepicker.js"></script> 
    <script language="javascript" type="text/javascript">
	$(function() {
		var dates = $( "#startDate, #endDate" ).datepicker({
			defaultDate: "+1w",
			changeMonth: true,
			numberOfMonths: 3,
			showButtonPanel: true,
			dateFormat: 'yy-mm-dd',
			onSelect: function( selectedDate ) {
				var option = this.id == "startDate" ? "minDate" : "maxDate",
					instance = $( this ).data( "datepicker" ),
					date = $.datepicker.parseDate(
						instance.settings.dateFormat ||
						$.datepicker._defaults.dateFormat,
						selectedDate, instance.settings );
				dates.not( this ).datepicker( "option", option, date );
			}
		});
	});	
	</script>
<?php endif; ?>

<script language="javascript">
<?php echo $jsClose?>

function setTarget()
{
	document.targetForm.submit();
}

// Get the HTTP Object
function getHTTPObject()
{
	if (window.ActiveXObject) 
		return new ActiveXObject("Microsoft.XMLHTTP");
	else if (window.XMLHttpRequest) 
		return new XMLHttpRequest();
	else 
	{
		alert("Your browser does not support AJAX.");
		return null;
 	}
}

// Change the value of the outputText field
function setOutput()
{
	if(httpObject.readyState == 4)
	{
		document.getElementById('outputText').innerHTML = httpObject.responseText;
	}
}

function reloadPage()
{
	if(httpObject.readyState == 4)
	{
		window.location.reload();
	}
}

function clearSingleTarget(t)
{
	httpObject = getHTTPObject();
	if (httpObject != null) 
	{
		httpObject.open("GET", "processTarget_ajax.php?removeItem=" + t, true);
		httpObject.send(null);
		httpObject.onreadystatechange = setOutput;
	}

}

function  setLagtime()
{
	hours = document.getElementById('lagHours').value;
	minutes = document.getElementById('lagMinutes').value;
	
	httpObject = getHTTPObject();
	if (httpObject != null) 
	{
		httpObject.open("GET", "processTarget_ajax.php?action=setLag&hr=" + hours + "&min=" + minutes, true);
		httpObject.send(null);
		httpObject.onreadystatechange =  reloadPage;
	}
}

function setProcessTarget()
{
	httpObject = getHTTPObject();
	if (httpObject != null) 
	{
		httpObject.open("GET", "processTarget_ajax.php?action=setProcessTarget&target=" + document.getElementById('txt_processTarget').value, true);
		httpObject.send(null);
		httpObject.onreadystatechange =  reloadPage;
	}
}

function saveTargets(action)
{
	httpObject = getHTTPObject();
	if (httpObject != null) 
	{
		var actionStr = "action=saveTargets";
		if (action == 'submitToMonitor')
		{
			actionStr += "&submit=yes";
		}
		
		httpObject.open("GET", "processTarget_ajax.php?" + actionStr, true);
		httpObject.send(null);
		httpObject.onreadystatechange =  reloadPage;
	}
}

function clearTargets()
{
	httpObject = getHTTPObject();
	if (httpObject != null) 
	{
		httpObject.open("GET", "processTarget_ajax.php?action=clear", true);
		httpObject.send(null);
		httpObject.onreadystatechange = setOutput;
	}
}

function refreshView()
{
	httpObject = getHTTPObject();
	if (httpObject != null) 
	{
		httpObject.open("GET", "processTarget_ajax.php", true);
		httpObject.send(null);
		httpObject.onreadystatechange = setOutput;
	}
}

function doWork()
{
	httpObject = getHTTPObject();
	if (httpObject != null) 
	{
		httpObject.open("GET", "processTarget_ajax.php?hr=" + document.getElementById('hr').value + "&target=" + document.getElementById('target').value, true);
		httpObject.send(null);
		httpObject.onreadystatechange = setOutput;
	}
}

function updateTarget()
{
	if(httpObject.readyState == 4)
	{
		document.getElementById('target').value = httpObject.responseText;
	}
}

function readTarget()
{
	httpObject = getHTTPObject();
	if (httpObject != null) 
	{
		httpObject.open("GET", "processTarget_ajax.php?getTarget=" +  document.getElementById('hr').value, true);
		httpObject.send(null);
		httpObject.onreadystatechange = updateTarget;
	}
}

function updateEdits()
{
	readTarget();
	document.getElementById('target').focus();
}

var httpObject = null;
</script>
</head>

<body <?php echo $PROCESS_END_DATE == $PROCESS_START_DATE ? 'onload="refreshView();"' : ''?>>

<table width='600' class="spinNormalText">
  <tr valign="top" class="spinTableBarEven">
	    <form name="dateForm" action="processGraph.php" method="post">
      <td width="466" valign="middle" nowrap="nowrap">
<?php if (david() || jim()) : ?>
    <label for="from">From</label> 
    <input type="text" id="startDate" name="startDate" value="<?php echo $PROCESS_START_DATE?>"/> 
    <label for="to">to</label> 
    <input type="text" id="endDate" name="endDate" value="<?php echo $PROCESS_END_DATE?>"/> 
<?php else : ?>
      	<div align="left">Start Date: 
        <input readonly name='startDate' id="startDate" type="text" size="10" value="<?php echo $PROCESS_START_DATE?>">
        <a href="javascript:NewCal('startDate','yyyymmdd')"> <img src="/images/calbtn.gif" width="16" height="16" border="0" alt="Pick a start date"></a>
        &nbsp;&nbsp;End Date: 
        <input readonly name='endDate' id="endDate" type="text" size="10" value="<?php echo $PROCESS_END_DATE?>">
        <a href="javascript:NewCal('endDate','yyyymmdd')"> <img src="/images/calbtn.gif" width="16" height="16" border="0" alt="Pick an end date"></a>
<?php endif; ?>
        
        <input type="button" value="Set Date Range" onclick="submitDates()" />
        </div>
            </td>
            </form>
      <td width="60" height="20" align="right" valign="middle" nowrap="nowrap">Lag Time:</td>
<?php
	if ($LAG_MINUTES > 0)
	{
		$hrs = $LAG_MINUTES / 60;
		$hrs = $hrs < 1 ? 0 : floor($hrs);
		$min = $LAG_MINUTES % 60;
	}
	else
	{
		$hrs = 0;
		$min = 0;
	}
	
	$LAG_TIME_FMT = $hrs . 'hr ' . $min . 'min';
?>      
      <td width="159" height="20" align="right" valign="middle" nowrap="nowrap"><select name="lagHours" id="lagHours">
		  <option value="0" <?php echo  $hrs == 0 ? 'selected' : ''?>>0</option>
    	  <option value="1" <?php echo  $hrs == 1 ? 'selected' : ''?>>1</option>
          <option value="2" <?php echo  $hrs == 2 ? 'selected' : ''?>>2</option>
          <option value="3" <?php echo  $hrs == 3 ? 'selected' : ''?>>3</option>
          <option value="4" <?php echo  $hrs == 4 ? 'selected' : ''?>>4</option>
          <option value="5" <?php echo  $hrs == 5 ? 'selected' : ''?>>5</option>
          <option value="6" <?php echo  $hrs == 6 ? 'selected' : ''?>>6</option>
          <option value="7" <?php echo  $hrs == 7 ? 'selected' : ''?>>7</option>
          <option value="8" <?php echo  $hrs == 8 ? 'selected' : ''?>>8</option>
          <option value="9" <?php echo  $hrs == 9 ? 'selected' : ''?>>9</option>
          <option value="10" <?php echo  $hrs == 10 ? 'selected' : ''?>>10</option>
          <option value="11" <?php echo  $hrs == 11 ? 'selected' : ''?>>11</option>
          <option value="12" <?php echo  $hrs == 12 ? 'selected' : ''?>>12</option>
      </select>
        hr&nbsp;&nbsp;
        <select name="lagMinutes" id="lagMinutes">
          <option value="0" <?php echo  $min == 0 ? 'selected' : ''?>>0</option>
          <option value="15" <?php echo  $min == 15 ? 'selected' : ''?>>15</option>
          <option value="30" <?php echo  $min == 30 ? 'selected' : ''?>>30</option>
          <option value="45" <?php echo  $min == 45 ? 'selected' : ''?>>45</option>
      </select>
        min      </td>
      <td width="103" align="center" valign="middle" nowrap="nowrap"><input type="submit" name="button" id="button" value="Set Lag" onclick="setLagtime()" /></td>

  </tr>
  <?php if (true) : ?>
  <tr valign="top" class="spinTableBarEven">
    <td height="20" colspan="2" align="right" valign="middle" nowrap="nowrap">Process Target:</td>
    <td height="20" align="left" valign="middle" nowrap="nowrap">&nbsp;&nbsp;<input value="<?php echo $PROCESS_TARGET?>" name="txt_processTarget" type="text" id="txt_processTarget" size="4" maxlength="4" onkeypress="return numbersonly(this, event)" /></td>
    <td align="center" valign="middle" nowrap="nowrap"><input type="submit" name="button2" id="button2" value="Set Target" onclick="setProcessTarget()" /></td>
  </tr>
  <?php endif; ?>
  
 <?php if ($singleDayGraph == 1): ?>
  <tr>
    <td colspan="4">
    <table width="800" border="0" align="center" cellpadding="5" cellspacing="1">
      <tr align="center" class="spinTableTitle" style="font-size:larger">
        <td height="44" colspan="3">Update Process Targets For: <?php echo $SELECTED_TANK?><br /><font size="-1">Note: Targets will be applied from <?php echo $PROCESS_START_DATE_FMT?> forward</font>
          <?php echo $tankName?></td>
      </tr>
      <tr class="spinTableBarOdd">
        <td width="189" align="center" valign="top">Starting Hour</td>
        <td width="149" align="center" valign="top">Target Flow</td>
        <td width="428" align="center" valign="top">&nbsp;</td>
      </tr>
      <tr class="spinTableBarOdd">
        <td width="189" align="center" valign="middle">Hr:
          <select name="hr" id="hr" onchange="updateEdits()">
            <option value="00:00">00:00</option>
            <option value="01:00">01:00</option>
            <option value="02:00">02:00</option>
            <option value="03:00">03:00</option>
            <option value="04:00">04:00</option>
            <option value="05:00">05:00</option>
            <option value="06:00">06:00</option>
            <option value="07:00">07:00</option>
            <option value="08:00">08:00</option>
            <option value="09:00">09:00</option>
            <option value="10:00">10:00</option>
            <option value="11:00">11:00</option>
            <option value="12:00">12:00</option>
            <option value="13:00">13:00</option>
            <option value="14:00">14:00</option>
            <option value="15:00">15:00</option>
            <option value="16:00">16:00</option>
            <option value="17:00">17:00</option>
            <option value="18:00">18:00</option>
            <option value="19:00">19:00</option>
            <option value="20:00">20:00</option>
            <option value="21:00">21:00</option>
            <option value="22:00">22:00</option>
            <option value="23:00">23:00</option>
          </select></td>
        <td width="149" align="center" valign="middle"><input name="target" type="text" id="target" size="5" maxlength="5" onkeyup="doWork()" /></td>
        <td width="428" align="center" valign="middle"><input type="button" name="addTarget" id="addTarget" value="Clear Targets" onclick="clearTargets()"/>&nbsp;
        <input type="button" name="updateGraph" id="updateGraph" value="Save Targets" onclick="saveTargets('saveOnly')"/>
        &nbsp;<input type="button" name="submitToMonitor" id="submitToMonitor" value="Submit To Monitor" onclick="saveTargets('submitToMonitor')"/>
        </td>
      </tr>
      <tr class="spinTableBarOdd">
        <td colspan="3" align="left" valign="middle"><div id="outputText">-- No Targets Set --</div></td>
      </tr>
    </table></td>
  </tr>
  <?php endif ; ?>
  
 <TR><td colspan="4">
	<div id="ProcessDiv" align="left">
	</div>
    <div id="processExportDiv" align="center">FusionCharts Export Handler Component</div>
</td>
</TR>

</table>
<?php

if (empty($PROCESS_START_DATE) || $invalidDate == 1)
{
	$usingDefaultStart = TRUE;
	$daysRes = getResult("SELECT cast( NOW() AS date) as endDate, DATE_ADD( cast( NOW() AS date ) , INTERVAL -" . $DAYS_PLOTTED . " DAY ) as startDate");
	$daysLine = mysqli_fetch_assoc($daysRes);
	extract($daysLine);
	//$PROCESS_START_DATE = $startDate;
	$daysAgo = $DAYS_PLOTTED;
	$stopDay = 0;
}

$GRAPH_START_DATE = "$PROCESS_START_DATE thru $PROCESS_END_DATE (Lag Time: $LAG_TIME_FMT)";
$PROCESS_CATEGORIES = '';
$PPM_DATASET = '';
$TEMPERATURE_DATASET = '';
$FLOW_DATASET = '';
$FLOW_TARGET = '';
$FLOW_AVERAGE = '';
$max = -1;
$min = 99999;

// plotting more than 3 days causes problems.  If the days plotted
// are over 3, then we don't do quarters
$rangeDetail = $DAYS_PLOTTED > 3 ? 0 : 3;
$plotsPerDay = $rangeDetail == 3 ? 96 : 24;

$plotPointCount = $DAYS_PLOTTED * $plotsPerDay;
$loopStartDate = $PROCESS_START_DATE;
$hourlyTargets = '';
$flowTarget = '';

// create a temp table that will be used to reflect lag time
executeQuery("DROP TABLE IF EXISTS tmp_processData");
//$debug = "00";
$query = "
	CREATE  TABLE tmp_processData SELECT *  , DATE_ADD( date, INTERVAL -$LAG_MINUTES
	MINUTE ) AS lagDate
	FROM processData
	WHERE 
	 monitorID='$SELECTED_TANK' and
	date > DATE_ADD( '$PROCESS_START_DATE', INTERVAL -3$debug DAY )
	AND date < DATE_ADD( '$PROCESS_END_DATE', INTERVAL 3 DAY )";

executeQuery($query, "CREATE");
$res = getResult("SELECT * FROM tmp_processData");
echoResults($res);
$foo = executeQuery("drop table tmp_processData");


// Get monitor process target
$PROCESS_TARGET = 0;
$query = "SELECT processTarget FROM monitor WHERE monitorID='$SELECTED_TANK' LIMIT 1";
error_log($query);
$pres = getResult($query);
if (checkResult($pres))
{
	$pline = mysqli_fetch_assoc($pres);
	extract($pline);
	$PROCESS_TARGET = $processTarget;
}

for ($i = $DAYS_PLOTTED; $i > 0; $i--)
{
	$query = "SELECT hourlyTargets FROM processTargetHistory WHERE monitorID='$SELECTED_TANK' AND date <= '$loopStartDate' ORDER BY date DESC LIMIT 1";
	$targetRes = getResult($query);
	if (checkResult($targetRes))
	{
		// get target values
		$targetLine = mysqli_fetch_assoc($targetRes);
		extract($targetLine);
		$PROCESS_TARGET_ARRAY = unserialize($hourlyTargets);
	}

	foreach (range(0, 23) as $v) 
	{
		$hr = sprintf("%02d",$v);		
		$hr2 = sprintf("%02d",$v+1);	
		

		foreach (range(0, $rangeDetail) as $qtr) 
		{
			if ( ($singleDayGraph == 1) && ($qtr != 0) ) continue;				
			
			if ($qtr == 0) 
				$quarterTime = "00";
			elseif ($qtr == 1)
				$quarterTime = "15";
			elseif ($qtr == 2)
				$quarterTime = "30";
			else // qtr 3
				$quarterTime = "45";
			
			
			$dateOut = $loopStartDate . ' ' . "$hr:$quarterTime:00";
			list($y, $m, $d) = explode('-', $loopStartDate);

			if ($singleDayGraph == 1)  
			{
				$flow_avg = 0;
				// get FLOW_AVERAGE
				$query = "SELECT ROUND(AVG(flowRate)) as flow_avg FROM processData
							WHERE 
								monitorID='$SELECTED_TANK' AND 
								date > DATE_ADD( '$PROCESS_START_DATE', INTERVAL - 30 DAY) and 
								HOUR(date) = '$hr' and
								samplePointID <> '' 
							LIMIT 30";
				$avgResult = getResult($query);
		
				if (checkResult($avgResult))
				{
					$avgLine = mysqli_fetch_assoc($avgResult);
					extract($avgLine);
				}
			}


			// Get the Target for this hour
			if (!empty($PROCESS_TARGET_ARRAY))
			{
				ksort($PROCESS_TARGET_ARRAY);
				reset($PROCESS_TARGET_ARRAY);
				foreach ($PROCESS_TARGET_ARRAY as $targetHR=>$target)
				{
					if ($targetHR == "$hr:00")
					{
						$flowTarget = $target;
					}
				}	
			}
			
			$LABEL_STEP = $singleDayGraph==1 ? 1 : floor($plotPointCount / 5);
			if ($singleDayGraph==1)
			{
				$PROCESS_CATEGORIES .= "<category label='$hr:00 - $hr2:00' />\n";
			}
			else
			{
				$PROCESS_CATEGORIES .= "<category label='$loopStartDate $hr:$quarterTime' />\n";
			}


			
			if ($LAG_MINUTES > 0)
			{
				$ppm_query = "SELECT 
							p.PPM
						FROM 
							tmp_processData p 
						WHERE 
							p.monitorID='$SELECTED_TANK' AND 
							p.lagDate = '$dateOut' and 
							p.samplePointID <> '' 
						limit 1";
//				bigecho($ppm_query);
				$res = getResult($ppm_query);
				if (checkResult($res))
				{
					
						$ppmLine = $res->fetch_assoc();
						extract($ppmLine);
				}

				$query = "SELECT 
							p.date, 
							p.flowLineID, 
							p.samplePointID, 
							p.flowRate, 
							p.temperature 
						FROM 
							tmp_processData p 
						WHERE 
							p.monitorID='$SELECTED_TANK' AND 
							p.date = '$dateOut' and 
							p.samplePointID <> '' 
						limit 1";
			}
			else
			{
				$query = "SELECT 
							p.date, 
							p.flowLineID, 
							p.samplePointID, 
							p.flowRate, 
							lt.lagMinutes, 
							p.PPM,
							p.temperature 
						FROM 
							processData p left outer join processLagTime lt on p.samplePointID=lt.samplePointID 
						WHERE 
							p.monitorID='$SELECTED_TANK' AND 
							p.date = '$dateOut' and 
							p.samplePointID <> '' 
						limit 1";
			}
			
			
			$res = getResult($query);
			if (checkResult($res))
			{
					$readlingLIne = $res->fetch_assoc();
					extract($readlingLIne);
					$SELECTED_SAMPLE_POINT = $samplePointID;
					$FLOW_DATASET .= "<set value='$flowRate' toolText='Flow Rate: $flowRate{br}($loopStartDate $hr:$quarterTime:00)' />\n";
					$PPM_DATASET .= "<set value='$PPM' toolText='PPM: $PPM{br}($loopStartDate $hr:$quarterTime:00)' />\n";
					$FLOW_TARGET .= "<set value='$flowTarget' toolText='Flow Target: $flowTarget' />\n";
					if ($singleDayGraph != 1)
					{
						$TEMPERATURE_DATASET .= "<set value='$temperature' toolText='Temperature: $temperature{br}($loopStartDate $hr:$quarterTime:00)' />\n";
					}
					else
					{
						$FLOW_AVERAGE .= "<set value='$flow_avg' toolText='Flow Average: $flow_avg' />\n";
					}
					$max = max($max, $temperature);
					$min = min($min, $temperature);
			}
			else
			{
					$FLOW_DATASET .= "<set value='' toolText='No Value' />\n";
					$PPM_DATASET .= "<set value='' toolText='No Value' />\n";
					$FLOW_TARGET .= "<set value='' toolText='No Value' />\n";
					if ($singleDayGraph != 1)
					{
						$TEMPERATURE_DATASET .= "<set value='' toolText='No Value' />\n";
					}
					else
					{
						$FLOW_AVERAGE .= "<set value='' toolText='No Value' />\n";
					}
			}
		}
	}
	$LEFT_Y_MAX = round($max * 1.01);
	$LEFT_Y_MIN = round($min * 0.99);
	
	$query = "SELECT DATE_ADD( cast( '$loopStartDate' AS date ) , INTERVAL 1 DAY ) as loopStartDate";
	$res = getResult($query);
	$line = $res->fetch_assoc();
	extract($line);
}




?>

<script language="javascript">
<?php if ($invalidDate) : ?>
	alert("Date range invalid.  Please choose a start date that preceeds the end date");
<?php endif; ?>
	function submitDates()
	{
		if (document.dateForm.startDate.value == '')
		{
			alert('Please select a Start Date');	
			return;
		}
		else if (document.dateForm.endDate.value == '')
		{
			alert('Please select an End Date');	
			return;
		}
		else
		{
			document.dateForm.submit()
		}
	}

</script>

<script type="text/javascript">	
	var chart_Process = new FusionCharts("/FusionCharts/Charts/<?php echo $singleDayGraph == 1? 'MSBar2D.swf' : 'MSCombiDY2D.swf'?>", "Process", "800", "<?php echo $singleDayGraph == 1 ? '1800' : '400'?>", "0", "1");
	chart_Process.setTransparent("false");
	chart_Process.setDataURL("processData.php");	
	chart_Process.render("ProcessDiv");
</script>	


<script type="text/javascript">
	//Render the export component in this
	//Note: varianceExporter is the DOM ID of the DIV and should be specified as value of exportHandler
	//attribute of chart XML.
	var ProcessExportComponent = new FusionChartsExportObject("processExporter", "/FusionCharts/Code/FusionCharts/FCExporter.swf");
	ProcessExportComponent.debugMode = true;

	//Width and height
	ProcessExportComponent.componentAttributes.width = '400';
	ProcessExportComponent.componentAttributes.height = '60';
	//Customize the component properties
	ProcessExportComponent.componentAttributes.fontFace = 'Arial';
	ProcessExportComponent.componentAttributes.fontColor = '0372AB';
	ProcessExportComponent.componentAttributes.fontSize = '12';
	//Button visual configuration
	ProcessExportComponent.componentAttributes.btnWidth = '200';
	ProcessExportComponent.componentAttributes.btnHeight= '25';
	ProcessExportComponent.componentAttributes.btnColor = 'E1f5ff';
	ProcessExportComponent.componentAttributes.btnBorderColor = '0372AB';
	//Button font properties
	ProcessExportComponent.componentAttributes.btnFontFace = 'Verdana';
	ProcessExportComponent.componentAttributes.btnFontColor = '0372AB';
	ProcessExportComponent.componentAttributes.btnFontSize = '15';
	//Title of button
	ProcessExportComponent.componentAttributes.btnsavetitle = 'Save the chart'
	ProcessExportComponent.componentAttributes.btndisabledtitle = 'Waiting for export'; 

	//Render the exporter SWF in our DIV fcexpDiv
	ProcessExportComponent.Render("processExportDiv");
<?php //echo $js_reposition?>
</script>
</body>
</html>
