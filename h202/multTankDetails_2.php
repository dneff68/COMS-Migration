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
<title>Anomaly Report</title>
<link rel="stylesheet" TYPE="text/css" href="http://h202.customhostingtools.com/main.css" >
<SCRIPT LANGUAGE="javascript" TYPE="text/javascript" SRC='http://www.customhostingtools.com/lib/admin.js'></SCRIPT>
<script language="javascript">
</script>
<style type="text/css">
<!--
.style1 {font-size: 11px}
-->
</style>
</head>

<body>
<?

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

$marr1 = array();
$marr2 = array();
$marr3 = array();
$marr4 = array();

if (!empty($REGION_FILTER) && $REGION_FILTER != 'all')
{
	$regfilt = getRegionFilter();
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
			t.tankName, 
			t.targetDosage,
			t.deviation_plus,
			t.deviation_minus,
			t.tankID, 
			m.monitorID 
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

$res = getResult($query);

if (checkResult($res))
{

	$rowcnt = 0;
	$rows = '';
	while ($line = $res->fetch_assoc())
	{
		extract($line);
		
		$query = "select monitorID from tankStats where cast( readingDate AS date ) >= DATE_ADD( cast( NOW( ) AS date ) , INTERVAL -2 DAY ) and monitorID = '$monitorID' AND normal=1 ORDER BY readingDate DESC";
		$res2 = getResult($query);
		
		
		if (checkResult($res2))
		{
			// Tank has been normal within the past two days
			continue;
		}
		
		$status = checkTankStatus($monitorID);  // neff

		list($statkey, $status) = explode(',', $status);
		$fontColor = $statkey == 'reorder' ? '#FFFF00' : '#ffffff';
		
		$status = empty($status) ? '&nbsp;' : $status;
		$status = "<span style=\"color:#000000\">$status</span>";

		//echo("$statkey<br>");
		if ( $statkey == 'H_Dose' ||  $statkey == 'L_Dose' ||  $statkey == 'ExceedCap' ||  $statkey == 'NoReading') // neff
		{
			$mkey = str_replace('-', '_', $monitorID);
			$href = "javascript:parent.doAction('showMap');parent.frames['mapFrame'].marker" . $mkey . ".openInfoWindowHtml(parent.frames['mapFrame'].marker" . $mkey . ".html)";

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
			<td align='left' width='60%'>Target</td><td align='right'><a href='javascript:surfDialog(\"setTargetDose.php?tankid=$monitorID\", 500, 250, window, true)'>$targetDosage</a></td>
			</tr>
			<tr>
			<td align='right' colspan='2'>+$deviation_plus</td>
			</tr>
			<tr>
			<td align='right' colspan='2'>-$deviation_minus</td>
			</tr>
			</table>
			</span>";

			$daysInStatus = daysInCurrentStatus($monitorID);
			if ($daysInStatus < 2)
				continue;
				
			if ( $statkey == 'H_Dose' )
			{
				$tankFont = '1125fe';
				$status .= "<br><font color='#ff0000'>$daysInStatus days reporting High Dose</font>";
			}
			elseif ( $statkey == 'L_Dose' )
			{
				$tankFont = 'FFFFFF';
				$status .= "<br><font color='#ff0000'>$daysInStatus days reporting Low Dose</font>";
			}
			elseif ( $statkey == 'ExceedCap' )
			{
				$tankFont = '660000';
				$status .= "<br><font color='#ff0000'>$daysInStatus days reporting Exceeded Capacity</font>";
			}
			elseif ( $statkey == 'NoReading' )
			{
				$tankFont = 'f1c30d';
			}



			$editLink = '';
			$rowOut = "<tr class=\"spinTableBarOdd\">
					<td bgcolor='#999999'><font color='#$tankFont'>$tankName</font></td>
					<td>$monitorID</td>
					<td align='center'>$targetDoseLink</td>
					<td align='center' nowrap bgcolor='#F2F2F2'>$status</td>";
				
			if ( $statkey == 'NoReading' )
			{
				$marr1[$monitorID] = $rowOut;
			}
			elseif ( $statkey == 'H_Dose' )
			{
				$marr2[$monitorID] = $rowOut;
			}
			elseif ( $statkey == 'L_Dose' )
			{
				$marr3[$monitorID] = $rowOut;
			}
			elseif ( $statkey == 'ExceedCap' )
			{
				$marr4[$monitorID] = $rowOut;
			}
			$rowcnt++;
		}
	}
}
$rowcnt = sizeof($marr1) + sizeof($marr2) + sizeof($marr3) + sizeof($marr1) ;
if (count($ZIPCOLLECTION) > 0 && $STATUS_FILTER != 'unass')
	$title = "<td colspan=\"4\"><div align=\"right\"><a href='multTankDetails.php?clearlist=yes'>reset list</a></div></td>";
else
{
	$t2 = '&nbsp;'; //$STATUS_FILTER == 'unass' ? '&nbsp;' : 'All Tanks';
	$title = "<td colspan=\"4\"><div align=\"right\">$t2</div></td>";
}

?> 
<img src="/images/abnormal.jpg"  />
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
    <td width="106"><div align="center" class="style1">Monitor ID </div></td>
	<td width="81"><div align="center" class="style1">Target Dose</div></td>
    <td width="229"><div align="center" class="style1">Status</div></td>
  </tr>
  
<?
//=$rows
foreach ($marr1 as $row)
{
	echo($row);
}
foreach ($marr2 as $row)
{
	echo($row);
}
foreach ($marr3 as $row)
{
	echo($row);
}
foreach ($marr4 as $row)
{
	echo($row);
}

?>
</tr></table>

</body>
</html>
