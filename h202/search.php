<?
session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once '/var/www/html/CHT/h202/h202Functions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';

if (empty($USERID) || empty($_SESSION['USERTYPE']))
{
	include 'login.php';
	die;
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Search</title>
<link rel="stylesheet" TYPE="text/css" href="http://h202.customhostingtools.com/main.css" >
<SCRIPT LANGUAGE="javascript" TYPE="text/javascript" SRC='http://www.customhostingtools.com/lib/admin.js'></SCRIPT>
<script language="JavaScript" src="datetimepicker.js"></script>
</head>
<body>
<? include 'banner.php'; ?>
<form name="searchForm" id="searchForm" method="post" action="search.php">
<input type="hidden" id="search" name="seach" value="1" />
<table width="576">
<tr><td width="81">Site Name</td>
<td width="212"><label>
  <input name="siteLocationName" type="text" id="siteLocationName" size="20" maxlength="50" />
</label></td>
<td width="215" rowspan="3" align="center" valign="top">
Reading Date:
<input readonly name='readingDate' id="readingDate" type="text" size="10" value="<?=$READING_DATE?>">
<a href="javascript:NewCal('readingDate','yyyymmdd')">
<img src="/images/calbtn.gif" width="16" height="16" border="0" alt="Choose a Reading Date"></a>
&nbsp;

</td>
</tr>
<tr><td>Monitor ID</td><td><input name="monitorID" type="text" id="monitorID" size="20" maxlength="50" /></td>
  </tr>
<tr><td>Tank Name</td><td><input name="tankName" type="text" id="tankName" size="20" maxlength="50" /></td>
  </tr>
</table>
<p><input type="submit" /></p>
</form>
<hr />
<?
	if (!empty($monitorID))
	{
		$res = getResult("SELECT DISTINCT s.* FROM site s, tank t, monitor m WHERE s.siteID=m.siteID and m.monitorID=t.monitorID and m.monitorID LIKE '$monitorID%'");
		if (checkResult($res))
			echoResults($res);
		echo "<BR>";
		$res = getResult("SELECT DISTINCT m.* FROM site s, tank t, monitor m WHERE s.siteID=m.siteID and m.monitorID=t.monitorID and m.monitorID LIKE '$monitorID%'");
		if (checkResult($res))
			echoResults($res);
		echo "<BR>";
		$res = getResult("SELECT DISTINCT t.* FROM site s, tank t, monitor m WHERE s.siteID=m.siteID and m.monitorID=t.monitorID and m.monitorID LIKE '$monitorID%'");
		if (checkResult($res))
			echoResults($res);
		echo "<BR>";
	}
	elseif (!empty($siteLocationName) || !empty($tankName))
	{
		if (!empty($siteLocationName))
			$mfilt = "and s.siteLocationName LIKE '%$siteLocationName%'";
		elseif (!empty($tankName))
			$mfilt = "and t.tankName LIKE '%$tankName%'";
	
		if (!empty($mfilt))
		{
			$res = getResult("SELECT s.* FROM site s, tank t, monitor m 
				WHERE s.siteID=m.siteID and m.monitorID=t.monitorID $mfilt");
			if (checkResult($res))
				echoResults($res);
echo "<BR>";
	
			$res = getResult("SELECT m.* FROM site s, tank t, monitor m 
				WHERE s.siteID=m.siteID and m.monitorID=t.monitorID $mfilt");
			if (checkResult($res))
				echoResults($res);
echo "<BR>";
	
			$res = getResult("SELECT t.* FROM site s, tank t, monitor m 
				WHERE s.siteID=m.siteID and m.monitorID=t.monitorID $mfilt");
			if (checkResult($res))
				echoResults($res);
		}
	}
	else
	{
		$readingDate_sql = !empty($readingDate) ? "'$readingDate'" : 'cast(NOW() as date)';
		$query = "SELECT
				1 as included,
				d.monitorID, 
				d.value, 
				t.tankName, 
				t.diameter, 
				d.date
			FROM 
				tank t, 
				data d 
			WHERE 
				t.monitorID=d.monitorID AND 
				cast(d.date as date) = $readingDate_sql 
			ORDER BY 
				t.tankName, 
				d.date";
		
		
		executeQuery("DROP TABLE IF EXISTS tmp_search");
		executeQuery("CREATE TEMPORARY TABLE tmp_search $query");
		executeQuery("CREATE TEMPORARY TABLE tmp_latestReadings SELECT max(date) as lastReadingDate, value, monitorID from data GROUP BY monitorID ORDER BY monitorID");
		executeQuery("CREATE TEMPORARY TABLE tmp_latestReadingsReduced SELECT lr.* FROM tmp_latestReadings lr LEFT JOIN tmp_search ts ON ts.monitorID=lr.monitorID WHERE ts.value IS NULL");
		$query = "(SELECT * FROM tmp_search) UNION (SELECT
				0 as included,
				d.monitorID, 
				d.value, 
				t.tankName, 
				t.diameter, 
				d.lastReadingDate as date 
			FROM 
				tank t, 
				tmp_latestReadingsReduced d 
			WHERE 
				t.monitorID=d.monitorID)
			ORDER BY 
				tankName";

		$res = getResult($query);
		$tableOut = '<table width="1500" border="0" cellspacing="0" cellpadding="6" class="tab2"><tr align="left" class="spinSmallTitle">
						<td>Tank Name</td>
						<td>Date of Reading</td>
						<td align="right">Usable Volume</td>
						<td align="right">Level Reading</td>
						<td align="right">Reorder Level</td>
						<td align="right">Weighted Average</td>
						<td>Last Delivery</td>
						<td nowrap>Future Delivery Date 1</td><td>Amount 1</td><td>Date 2</td><td>Amount 2</td><td>Date 3</td><td>Amount 3</td>
					  </tr>';
		if (checkResult($res))
		{
			while ($line = $res->fetch_assoc())
			{
				extract($line);
				
				
				// get the value based on the date of the reading.  In some cases it's an older reading date
				$xres = getResult("select value from data where date = '$date' and monitorID = '$monitorID'");
				$xline = mysql_fetch_assoc($xres);
				extract($xline);
			
				// get region
				$regRes = getResult("SELECT r.value AS region, m.units FROM site s, region r, monitor m WHERE m.monitorID='$monitorID' AND m.siteID = s.siteID AND s.regionID = r.regionID LIMIT 1");
				$region = '&nbsp;';
				if (checkResult($regRes))
				{
					$regLine = mysql_fetch_assoc($regRes);
					extract($regLine);
				}
				
				$value = $units == 'Gallons' ? $value : inchToGal($value, $diameter);
				
				if (empty($readingDate))
					$reorderInfo = reorderInfo($monitorID, '');
				else
					$reorderInfo = reorderInfo($monitorID, $readingDate);
				$usableVolume = $reorderInfo['usableVolume_'];
				$reorderLevel = $reorderInfo['reorderLevel'];
				$weightedAverage = getDeliveryAvg($monitorID);
				
				// get most recent delivery date before today
				$mostRecentDeliveryDate = '&nbsp;';
				$query = "SELECT d.deliveryDate as mostRecentDeliveryDate  
							FROM delivery d, deliveryTanks dt 
							WHERE 
								dt.monitorID='$monitorID' and 
								d.deliveryID=dt.deliveryID and 
								d.status != 'Cancelled' and
								d.deliveryDate < date(NOW()) order by d.deliveryDate desc LIMIT 1";
				$delRes = getResult($query);
				if (checkResult($delRes))
				{
					$delLine = mysql_fetch_assoc($delRes);
					extract($delLine);
				}
			
				// get future delivery info
				$date1 = '&nbsp;';
				$amt1  = '&nbsp;';
				$date2 = '&nbsp;';
				$amt2  = '&nbsp;';
				$date3 = '&nbsp;';
				$amt3  = '&nbsp;';
				$query = "SELECT d.deliveryDate, dt.quantity  
							FROM delivery d, deliveryTanks dt 
							WHERE 
								dt.monitorID='$monitorID' and 
								d.deliveryID=dt.deliveryID and 
								d.status != 'Cancelled' and
								d.deliveryDate >= date(NOW())";
				$delRes = getResult($query);

				if (checkResult($delRes))
				{
					$i = 1;
					while($delLine = mysql_fetch_assoc($delRes))
					{
						extract($delLine);
						eval('$date' . "$i" . '=$deliveryDate;');
						eval('$amt' . "$i" . '=$quantity;');
						$i++;
					}
				}
			
				
				$date = $included == 1 ? $date : "<span class='spinAlert'>$date</span>";
				$tableOut .= " <tr class='spinTableBarOdd'>
								<td align='left'>$tankName</td>
								<td nowrap>$date</td>
								<td align='right'>$usableVolume</td>
								<td align='right'>$value</td>
								<td align='right'>$reorderLevel</td>
								<td align='right'>$weightedAverage</td>
								<td>$mostRecentDeliveryDate</td>
								<td align='center'>$date1</td><td>$amt1</td><td align='center'>$date2</td><td>$amt2</td><td align='center'>$date3</td><td>$amt3</td>
								
							  </tr>";
			}
		}
		$tableOut .= "</table>";
		echo $tableOut;
	}

?>

</body>
</html>
