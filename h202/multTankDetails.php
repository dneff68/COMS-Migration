<?
session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once '/var/www/html/CHT/h202/h202Functions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';

$david_debug = false;
if ($david_debug)
{
	$time_start = getmicrotime();

	$debugTime = date('s');
	session_register('time_start');
	session_register('last_stamp');
	$last_stamp = $time_start;
	session_register("TOTAL_DB_TIME");
	$TOTAL_DB_TIME = 0.0;
	session_register("debugTime");
	session_register("dbhitcount");
	$dbhitcount = 0;
	session_register("queryArray");
	$queryArray = array();
	timestamp('MAIN', true);
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Tank Details</title>
<link rel="stylesheet" TYPE="text/css" href="http://h202.customhostingtools.com/main.css" >
<SCRIPT LANGUAGE="javascript" TYPE="text/javascript" SRC='http://www.customhostingtools.com/lib/admin.js'></SCRIPT>
<script language="javascript">
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

		function updateTS_alert(chkObj)
		{
			quietCommit("/updateTS_alert.php?ischecked=" + chkObj.checked + '&monitorID=' + chkObj.value);
		}
		
		function updateHideProcessLink(chkObj)
		{
			httpObject = getHTTPObject();
			if (httpObject != null) 
			{
				httpObject.open("GET", "doAction.php?actionID=setViewProcess&ischecked=" + chkObj.checked + '&monitorID=' + chkObj.value, true);
				httpObject.send(null);
				if (chkObj.checked)
				{
					document.getElementById('processLink_' + chkObj.value).innerHTML = "process (hidden)";
				}
				else
				{
					document.getElementById('processLink_' + chkObj.value).innerHTML = "process";
				}
				//httpObject.onreadystatechange = updateTarget;
			}
		}

</script>
<style type="text/css">
<!--
.style1 {font-size: 11px}
-->
</style>
</head>

<body>
<?

echo "<iframe id='ActionFrame'
		 name='ActionFrame'
		 style='width:0px; height:0px; border:0px'
		 src=''></iframe>";

/*
Location	Tank	Tank Id	Monitor Id	Error Code	Type	Reading	Reading	Reading	Reading	Reading
(processed)	(date)	(inches)	(gallons)	
*/
if ($clearlist == 'yes')
{
	array_splice($ZIPCOLLECTION,0);
	unset($ZIPCOLLECTION);
	$ZIPCOLLECTION = '';
}

if (empty($ZIPCOLLECTION))
{
	session_register('ZIPCOLLECTION');
	$ZIPCOLLECTION = array();
}

if (!empty($zip))
{
	if (!array_key_exists($zip, $ZIPCOLLECTION))
	{
		$ZIPCOLLECTION[$zip] = 1; // value of 1 is just a holder
	}
}

if (count($ZIPCOLLECTION) > 0)
{
	$more = " and (";
	foreach ($ZIPCOLLECTION as $key => $storedzip)
	{
		$more .= "s.zip LIKE '%$key%' || ";
	}
	$more .= 'false)'; // end the or
}

$marr = array();
if ($STATUS_FILTER == 'unass')
{
	$rowcnt = 0;
	$rows = '';
	$query = "select 
				s.siteLocationName as 'Location', 
				s.city as City, 
				s.state as State, 
				s.zip, 
				t.tankName, 
				t.tankID, 
				m.monitorID,
				m.hideProcessLink,
				t.tankID 
			from monitor m, tank t, site s
			where 
			t.monitorID=m.monitorID and 
			m.status = 'Inactive' and
			m.siteID = s.siteID order by t.tankName";
	$res = getResult($query);
	
	if (checkResult($res))
	{
		while ($line = mysql_fetch_assoc($res))
		{
			extract($line);
			
			$status = substr($monitorID, 0, 5) == 'none-' ? 'Unmonitored' : 'Inactive';
			$monOut = strpos($monitorID, 'none-') === false ? '' : $monitorID;

			if ($_SESSION['USERTYPE'] == 'super')
			{
				$editLink = "<a href=\"javascript:window.parent.location = 'addTank.php?init=yes&mon=$monitorID';\">edit</a>";
			}

				$processLink = '';
				$showProcessLink = 0;
				if ($_SESSION['USERTYPE'] == 'super' || $_SESSION['USERTYPE'] == 'service')
				{
					if ($hideProcessLink == 0)	$showProcessLink = 1;
				}
				
				// check for customer email login
				$query = "SELECT email FROM customerLoginEmail WHERE email = '$USERID' and hideProcessLink=0 LIMIT 1";
				$processRes = getResult($query);
				if (checkResult($processRes))
				{
					if ($hideProcessLink == 0)	$showProcessLink = 1;
				}
				
				$txt_hidden = '';
				if (david() || jim())
				{
					$showProcessLink = 1;
					$txt_hidden = $hideProcessLink == 1 ? '(hidden)' : '';
				}
				
				if ($showProcessLink == 1)
				{
					$processLink = "
						&nbsp;&nbsp;
						<a href=\"javascript:surfDialog('/charts/processGraph.php?monitorID=$monitorID', 835, 550, window, false)\">process $txt_hidden</a>";
				}

				$marr[$monitorID] = "<tr class=\"spinTableBarOdd\">
					<td>&nbsp;</td>
					<td>$tankName</td>
					<td>$monOut</td>
					<td nowrap>$status</td>
					<td nowrap><a href=\"javascript:surfDialog('/charts/tankGraph.php?tab=2&tankID=$monitorID', 830, 430, window, false)\">graph</a>
						$processLink
					&nbsp;&nbsp;
						$editLink
					<br>";

			$marr[$monitorID] .= "<a href=''>add note</a>";
					
			$marr[$monitorID] .= "</td>
			  </tr>";
			$rowcnt++;
		}	
	}

	$query = "select DISTINCT data.monitorID from data left join monitor ON data.monitorID=monitor.monitorID 
	where monitor.monitorID IS NULL and data.date > DATE_ADD(NOW(), INTERVAL -11 DAY)";
	$res = getResult($query);
	
	if (checkResult($res))
	{
		while ($line = mysql_fetch_assoc($res))
		{
			extract($line);
			$marr[$monitorID] = "<tr class=\"spinTableBarOdd\">
				<td>&nbsp;</td><td>[Monitor: $monitorID ]</td><td colspan='3'> does not exist</td>
			  </tr>";
			$rowcnt++;
		}	
	}


}
else
{
	if (!empty($REGION_FILTER) && $REGION_FILTER != 'all')
	{
		$regfilt = "and s.regionID=$REGION_FILTER";
		if (true)
		{
			$regfilt = getRegionFilter();
		}
		
	}

	if ( $_SESSION['USERTYPE'] == 'customer' )
	{
		$custTanks = "and s.deliveryEmailDist LIKE '%$USERID%'";
	}
	else
	{
		$inactiveFilt = $SHOWINACTIVE == 'yes' ? '' : "and m.status != 'Inactive'";
		$tmpShutdownFilt = $SHOWTEMPSHUTDOWN == 'yes' ? '' : "and m.status != 'Temporary Shutdown'";
		$unmonFilt	  = $SHOWUNMONITORED 	== 'yes' ? '' : "and t.monitorID NOT LIKE 'none%'";
	}
	
	$query = "select 
				s.siteID, 
				s.siteLocationName as 'Location', 
				s.city as City, 
				s.state as State, 
				s.zip, 
				t.notesStatic,
				t.tankName, 
				t.targetDosage,
				t.targetDaily,
				t.deviation_plus,
				t.deviation_minus,
				t.tankID, 
				m.monitorID,
				m.no_tsAlert,
				m.hideProcessLink,
				m.status as monitorStatus
			from monitor m, tank t, site s
			where 
				t.monitorID=m.monitorID and
				m.siteID = s.siteID 
				$inactiveFilt
				$tmpShutdownFilt
				$more 
				$regfilt 
				$unmonFilt
				$custTanks
			order by t.tankName";
	
	//bigecho($query);
	
	$res = getResult($query);
	
	
	if (checkResult($res))
	{
	
		$rowcnt = 0;
		$rows = '';
		while ($line = mysql_fetch_assoc($res))
		{
			extract($line);
			$status = checkTankStatus($monitorID, $STATUS_FILTER);
	
			list($statkey, $status) = explode(',', $status);
			$fontColor = $statkey == 'reorder' ? '#FFFF00' : '#ffffff';
			
			$status = empty($status) ? '&nbsp;' : $status;
			if ($statkey == 'TempShutdown')
			{
				$status = "<strong>$status</strong>";
			}
			else
			{
				$status = "<span style=\"color:#000000\">$status</span>";
			}			

			if ($monitorStatus == 'Inactive')
			{
				$status .= "<div style=\"color:#ff0000\">-- Inactive --</div>";
			}

			if ( empty($STATUS_FILTER) || $statkey == $STATUS_FILTER || $STATUS_FILTER == 'all')
			{
			    $mkey = str_replace('-', '_', $monitorID);
				$href = "javascript:parent.doAction('showMap');parent.frames['mapFrame'].marker" . $mkey . ".openInfoWindowHtml(parent.frames['mapFrame'].marker" . $mkey . ".html)";
				//$href = "javascript:parent.frames['mapFrame'].GEvent.trigger(marker" . $siteID . ", 'mouseover');";
				
				/*
					Get the most recent note for the tank to display in the grid
				*/	
				$noteLink = "<a href='javascript:surfDialog(\"tankNotes.php?id=$monitorID\", 600, 315, window, true)'>add note</a>";	
				$notesOut = '';
				$query = "SELECT DATE_FORMAT(date, '%m/%d/%Y %I:%i %p') as noteDate, note, user FROM tankNotes WHERE tankID='$monitorID' order by date desc LIMIT 1";
				$noteres = getResult($query);
				if (checkResult($noteres))
				{
					$noteline = mysql_fetch_assoc($noteres);
					extract($noteline);
					$notesOut = strlen($note) > 50 ? substr($note, 0, 50) . '...' : $note;
					$notesOut = "$noteDate<br>&nbsp;&nbsp;$notesOut&nbsp;(<span class='spinAlert'>$user</span>)";
					$noteLink = "<a href='javascript:surfDialog(\"tankNotes.php?id=$monitorID\", 600, 315, window, true)'>add/view notes</a>";
				}
						
				if (!empty($targetDaily))
				{
					$dow = date('N');
					$targets = unserialize($targetDaily);
					$targetDosage = $targets[$dow];
				}
				
				// override targetDosage with the history for this day
				$query = "SELECT targetDose as targetDosage, targetDaily FROM tankHistory WHERE monitorID='$monitorID' order by date desc";
				$histRes = getResult($query);
				if (checkResult($histRes))
				{
					$histLine = mysql_fetch_assoc($histRes);
					extract($histLine);
					if (!empty($targetDaily))
					{
						$targets = unserialize($targetDaily);
						$dow = date('N', strtotime("-0 day"));  // day of week for this day
						$targetDosage = $targets[$dow];
					}
					
				}

				$targetDoseLink = "<span id='dose$monitorID'>
				<table width='100%'>
				<tr>
				<td align='left' width='60%'>Target</td><td align='right'><a href='javascript:surfDialog(\"setTargetDose.php?tankid=$monitorID\", 475, 385, window, true)'>$targetDosage</a></td>
				</tr>
				<tr>
				<td align='right' colspan='2'>+$deviation_plus</td>
				</tr>
				<tr>
				<td align='right' colspan='2'>-$deviation_minus</td>
				</tr>
				</table>
				</span>";					
				
				$editLink = '';
				if ($_SESSION['USERTYPE'] == 'super')
				{
					$editLink = "<a href=\"javascript:window.parent.location = 'addTank.php?init=yes&mon=$monitorID';\">edit</a>";
				}

				$ts_check = '';
				if (($_SESSION['USERTYPE'] == 'super') && strpos($status, 'Temporary Shutdown') !== false)
				{
					$checked = $no_tsAlert == 1 ? 'CHECKED' : '';
					$ts_check = "<br><input type='checkbox' onclick='updateTS_alert(this)' value='$monitorID' $checked>No TS Alert ";
				}
				
				$process_check = '';
				if (jim() || david())
				{
					$checked = $hideProcessLink == 1 ? 'CHECKED' : '';
					$process_check = "<br><input type='checkbox' onclick='updateHideProcessLink(this)' value='$monitorID' $checked>Hide Process Link";
				}				

				$notesStatic = strlen($notesStatic) > 62 ? substr($notesStatic, 0, 60) . '...' : $notesStatic;
				$notesStatic = !empty($notesStatic) ? "notes: $notesStatic" : '';
				$staticNotes = "<div style='font-size:-1'>$notesStatic</div>";
				
				if ($_SESSION['USERTYPE'] == 'super')
				{
					$spaces = empty($notesStatic) ? '<br><br><br><br>' : '';
//					if (david() || jim())
//					{
//						$custEmail = getCustomerSummarySites($monitorID);
//						$custSummary = '';
//						if ( $custEmail )
//						{
//							$custSummary = "<a target='_parent' href='index.php?cust=1&customerEmail=$custEmail'>Customer Summary</a>&nbsp;&nbsp;";
//						}
//						$staticNotes .= "<div align='right' style='font-size:smaller'>$spaces $custSummary<a href='javascript:surfDialog(\"tankNotes.php?id=$monitorID&noteAction=static_note\",600,200,window,true)'>tank note</a></div>";
//					}
//					else

if (true) //david() || jim())
{
						$staticNotes .= $spaces;
						$last = strtolower( $monitorID[strlen($monitorID)-1] );
						
						if ($last == 'x')
						{
							$alarmRes = getResult("SELECT cleared FROM flowAlarm WHERE monitorID='$monitorID'");
							if (mysql_num_rows($alarmRes) > 0)
							{
								$alarmRes = getResult("SELECT cleared FROM flowAlarm WHERE monitorID='$monitorID' AND cleared=0");
								if (mysql_num_rows($alarmRes) > 0)
									$alarmColor = '#C00';
								else
									$alarmColor = '#0000ff';
							
								$staticNotes .= "<div style='float:left;font-size:smaller'><a style='color:$alarmColor' href='javascript:surfDialog(\"customerAlarms.php?monitorID=$monitorID\",550,300,window,false)'>alarms</a></div>";
							}
						}
						$staticNotes .= "<div style='float:right;font-size:smaller'><a href='javascript:surfDialog(\"tankNotes.php?id=$monitorID&noteAction=static_note\",600,200,window,true)'>tank note</a></div>";
}
else
{
						$staticNotes .= "<div align='right' style='font-size:smaller'>$spaces<a href='javascript:surfDialog(\"tankNotes.php?id=$monitorID&noteAction=static_note\",600,200,window,true)'>tank note</a></div>";
}
				}
				$processLink = '';
				$showProcessLink = 0;
				if ($_SESSION['USERTYPE'] == 'super' || $_SESSION['USERTYPE'] == 'service')
				{
					if ($hideProcessLink == 0)	$showProcessLink = 1;
				}
				
				// check for customer email login
				$query = "SELECT email FROM customerLoginEmail WHERE email = '$USERID' and hideProcessLink=0 LIMIT 1";
				$processRes = getResult($query);
				if (checkResult($processRes))
				{
					if ($hideProcessLink == 0)	$showProcessLink = 1;
				}
				
				$txt_hidden = '';
				if (david() || jim())
				{
					$showProcessLink = 1;
					$txt_hidden = $hideProcessLink == 1 ? '(hidden)' : '';
				}
				
				if ($showProcessLink == 1)
				{
					$processLink = "
						&nbsp;&nbsp;
						<a id='processLink_$monitorID' href=\"javascript:surfDialog('/charts/processGraph.php?monitorID=$monitorID', 835, 550, window, false)\">process $txt_hidden</a>";
				}
				
				$marr[$monitorID] = "<tr class=\"spinTableBarOdd\">
					<!--<td><input type=\"checkbox\" name=\"T$monitorID\" id=\"T$monitorID\" /></td>-->
					<td valign='top'><a href=\"$href\">$tankName</a>$staticNotes</td>
					<td>$monitorID $ts_check $process_check</td>
					<td align='center'>$targetDoseLink</td>
					<td align='center' nowrap bgcolor='#F2F2F2'>$status</td>
					<td><a href=\"javascript:surfDialog('/charts/tankGraph.php?tab=2&tankID=$monitorID', 830, 430, window, false)\">graph</a>
					$processLink
					&nbsp;&nbsp;
					$editLink &nbsp;&nbsp;&nbsp;$noteLink
				";

				if (!empty($notesOut))
				{
					$marr[$monitorID] .= "<hr>$notesOut";
				}
				$marr[$monitorID] .= "
						</td>
					  </tr>";
					$rowcnt++;
			}
		}
	}
}
//$debug .= "number added: $rowcnt<br>";
$rowcnt = sizeof($marr);
//bigecho($query);
// $cnt = mysql_num_rows($res);
if (count($ZIPCOLLECTION) > 0 && $STATUS_FILTER != 'unass')
	$title = "<td colspan=\"4\"><div align=\"right\"><a href='multTankDetails.php?clearlist=yes'>reset list</a></div></td>";
else
{
	$t2 = '&nbsp;'; //$STATUS_FILTER == 'unass' ? '&nbsp;' : 'All Tanks';
	$title = "<td colspan=\"4\"><div align=\"right\">$t2</div></td>";
}

?> 
<table width="100%" border="1" align="left" cellpadding="3" cellspacing="0" bordercolorlight="#333333">
  <tr class="spinTableBarOdd">
    <td width="259" align='left'>Showing <?=$rowcnt?> Tanks</td>
    <?=$title?>
  </tr>
  <tr class="spinTableTitle">
   <!-- <td><div align="center" class="style1">Customer Site</div></td>
    <td><div align="center" class="style1">Location</div></td>
    -->
	<td><div align="center" class="style1">Tank</div></td>
    <td width="206"><div align="center" class="style1">Monitor ID </div></td>
	<td width="81"><div align="center" class="style1">Target Dose</div></td>
    <td width="129"><div align="center" class="style1">Status</div></td>
	<td width="496"><div align="center" class="style1">&nbsp;</div></td>
  </tr>
  
<?
//=$rows
foreach ($marr as $row)
{
	echo($row);
}

?>
</table>

</body>
</html>
<?
if ($david_debug)
{
	echo "<table><tr><td bgcolor='#FF9933'><font color='#000000'>";
	$s = session_id();
	echo "-- Neff DEVELOPMENT --<br>";
	bigecho("session id: $s");
	$time_end = getmicrotime();
	$time = $time_end - $time_start;
	$time = number_format($time, 2, '.', '');
	$TOTAL_DB_TIME = number_format($TOTAL_DB_TIME, 2, '.', '');
	echo "Total Process Time: $time seconds";
	echo "<br>Total hits to the database: $dbhitcount ($TOTAL_DB_TIME seconds)<br>";
	echo "</font></td></tr></table>";
	echo "<table><tr><td bgcolor='#FF9933'><font color='#000000'>";
	echo "<hr>";
	showSessionVars();
	echo "<hr>";
	$queries = "";
	foreach ($queryArray as $query)
	{
		echo "<p>$query</p><hr>";
	}
	echo "</font></td></tr></table>";
}

?>