<?
session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once '/var/www/html/CHT/h202/h202Functions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';


if (david())
{
	error_reporting(E_PARSE | E_ERROR); 
	ini_set("display_errors", 1); 		
}

if (empty($customerID))
{
	$customerID='--none--'; // give a zero result count in SQL later
}

$query = "SELECT
			cust.siteID as siteIDs, 
			c.customerID
			FROM
			customer cust, 
			customerLoginEmail c
			WHERE
			cust.customerID=c.customerID and
			c.email='$CUSTOMER_EMAIL'";
$res = getResult($query);
if (checkResult($res))
{
	$line = $res->fetch_assoc();
	extract($line);
	$sites = explode(',', $siteIDs);
}


function processWorkOrder($workKey)
{
	$query = "SELECT html FROM serviceHistory WHERE workKey=$workKey LIMIT 1";
	$res = getResult($query);
	if (checkResult($res))
	{
		$line = $res->fetch_assoc();
		extract($line);

		if (strpos($html, 'Work Order Closed') > 0)
		{
			
			$workOrderNumber = getHTMLPart('>Work Order ', '</div>', $html);
			if (strlen($workOrderNumber) < 25 )
			{
			   	executeQuery("UPDATE serviceHistory SET workOrderNumber='$workOrderNumber', closed=1 WHERE workKey=$workKey OR workOrderNumber='$workOrderNumber'");
			}
			return false;
		}
	

		$html = getHTMLPart('<HTML>', '</HTML>', $html);
		$sentDate = getHTMLPart('Sent ', '</div>', $html);
		$sentDate = str_replace("&nbsp;", ' ', $sentDate);
		$sentDate = str_replace('=', '', $sentDate);
		$datestr = str_replace(' - ', ' ', $sentDate);
		$datestr = strtotime($datestr);
		$datestr = date('Y-m-d H:i:s', $datestr);

		$workOrderNumber = getHTMLPart('>Work Order ', '</div>', $html);
		$html = str_replace("'", "''", $html);
		executeQuery("UPDATE serviceHistory SET dateString='$sentDate', workOrderNumber='$workOrderNumber' WHERE workKey=$workKey LIMIT 1");
		return "$sentDate~$workOrderNumber";
	}
	return false;
}

$wkRes = getResult("SELECT html, workKey FROM serviceHistory WHERE workOrderNumber=''");
if (checkResult($wkRes))
{
	while ($wkLine = mysqli_fetch_assoc($wkRes))
	{
		extract($wkLine);
		processWorkOrder($workKey);
	}
}


?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Service History</title>
<link rel="stylesheet" TYPE="text/css" href="http://h202.customhostingtools.com/main.css" >
<link rel="stylesheet" TYPE="text/css" href="planning.css" >
<SCRIPT LANGUAGE="javascript" TYPE="text/javascript" SRC='http://www.customhostingtools.com/lib/helper.js'></SCRIPT>
<SCRIPT LANGUAGE="javascript" TYPE="text/javascript" SRC='http://www.customhostingtools.com/lib/admin.js'></SCRIPT>
<script src="http://www.customhostingtools.com/lib/jquery.js" type="text/javascript"></script>
<script type="text/javascript">
<? include_once "custSummary.php"; ?>
</script>
</head>
<body>
<div id='service-history'>
  <table bordercolor="#888888" width="800" border="1" align="center" cellpadding="6" cellspacing="0">
    <tbody>
      <tr>
        <th colspan='3' class='customerBanner' style='font-size:20px; height:15px' scope='col'> Service History Open</th>
      </tr>
    
<?
	foreach ($sites as $siteID)
	{
		// get each monitor ID for the site
		$query = "SELECT m.monitorID, sh.workKey, sh.workOrderNumber, sh.dateString from monitor m, site s, serviceHistory sh 
		WHERE m.siteID=s.siteID AND m.monitorID=sh.monitorID AND s.siteID=$siteID and sh.closed=0 order by sh.monitorID, sh.workOrderNumber";
		$res = getResult($query);
		if (checkResult($res))
		{
			$currentMonitorID = '';
			$monitorRowsOut = '';
			$rowCount = 0;
			while ($line = $res->fetch_assoc())
			{
				extract($line);
				if (empty($workOrderNumber))
				{
					error_log('process work order');
					$processResult = processWorkOrder($workKey);
					if ($processResult)
					{
						list($dateString, $workOrderNumber) = explode('~', $processResult);
					}
					else
					{
						continue;
					}
				}
				$monitorID = trim($monitorID);
				$tankName = getTankName($monitorID);
				if ($monitorID != $currentMonitorID)
				{
					if ( $monitorRowsOut != '' )
					{
						$monitorRowsOut = str_replace("--$currentMonitorID--", $rowCount, $monitorRowsOut);
						echo $monitorRowsOut;
						$monitorRowsOut = '';
						$rowCount = 0;
					}
					$rowOut = "<tr class='category-row'>
						<td colspan='3'><div style='float:left'>$tankName (--$monitorID--)</div>
						  <div id='plus_$monitorID' class='expand-row' onclick='expand(\"$monitorID\")'>+</div></td>
					  </tr>";
					$monitorRowsOut .= $rowOut; 
					$currentMonitorID = $monitorID;

					//echo($rowOut);
				}
				
				$rowOut = "<tr id='' class='planningItemRow row_$monitorID' style=''>
						<td width='317' height='50' align='left' valign='middle'>
							Work Order: $workOrderNumber
						</td>
						<td width='317' align='center' valign='middle'>Requested - $dateString</td>
						<td width='52' colspan='-5' align='center'><p><a href='javascript:surfDialog(\"workOrder.php?workKey=$workKey\", 835, 650, window, false)'>view</a></p></td>
					  </tr>";
				$rowCount++;
				$monitorRowsOut .= $rowOut; 
				//echo($rowOut);
			}
			if ( $monitorRowsOut != '' )
			{
				$monitorRowsOut = str_replace("--$monitorID--", $rowCount, $monitorRowsOut);
				echo $monitorRowsOut;
				$monitorRowsOut = '';
			}
			
		}
		

	}


?>    
    </tbody>
  </table>
</div>
<br />
<br />
<!-- SHOW COMPLETED ITEMS -->
<div id='service-history-complete'>
  <table bordercolor="#888888" width="800" border="1" align="center" cellpadding="6" cellspacing="0">
    <tbody>
      <tr>
        <th colspan='3' class='customerBanner' style='font-size:20px; height:15px' scope='col'> Service History Closed </th>
      </tr>
    
<?
	foreach ($sites as $siteID)
	{
		// get each monitor ID for the site
		$query = "SELECT m.monitorID, sh.workKey, sh.workOrderNumber, sh.dateString from monitor m, site s, serviceHistory sh WHERE m.siteID=s.siteID AND m.monitorID=sh.monitorID AND s.siteID=$siteID and sh.closed=1  and sh.workOrderNumber != '' AND sh.dateString != '' order by sh.monitorID, sh.dateRequested";
		$res = getResult($query);
		if (checkResult($res))
		{
			$currentMonitorID = '';
			$monitorRowsOut = '';
			$rowCount = 0;
			while ($line = $res->fetch_assoc())
			{
				extract($line);
				if (empty($workOrderNumber))
				{
					error_log('process work order');
					$processResult = processWorkOrder($workKey);
					if ($processResult)
					{
						list($dateString, $workOrderNumber) = explode('~', $processResult);
					}
					else
					{
						continue;
					}
				}
				$monitorID = trim($monitorID);
				$tankName = getTankName($monitorID);
				if ($monitorID != $currentMonitorID)
				{
					if ( $monitorRowsOut != '' )
					{
						$monitorRowsOut = str_replace("--$currentMonitorID--", $rowCount, $monitorRowsOut);
						echo $monitorRowsOut;
						$monitorRowsOut = '';
						$rowCount = 0;
					}
					$rowOut = "<tr class='category-row_complete'>
						<td colspan='3'><div style='float:left'>$tankName (--$monitorID--)</div>
						  <div id='lower_plus_$monitorID' class='expand-row' onclick='expand_lower(\"$monitorID\")' style='color:#ffffff' >+</div></td>
					  </tr>";
					$monitorRowsOut .= $rowOut; 
					$currentMonitorID = $monitorID;

					//echo($rowOut);
				}
				
				$rowOut = "<tr id='row_" . $monitorID . "_complete' class='planningItemRow_complete row_" . $monitorID . "_complete' style=''>
						<td width='317' height='50' align='left' valign='middle'>
							Work Order: $workOrderNumber
						</td>
						<td width='317' align='center' valign='middle'>Requested - $dateString</td>
						<td width='52' colspan='-5' align='center'><p><a href='javascript:surfDialog(\"workOrder.php?workKey=$workKey\", 835, 650, window, false)'>view</a></p></td>
					  </tr>";
				$rowCount++;
				$monitorRowsOut .= $rowOut; 
				//echo($rowOut);
			}
			if ( $monitorRowsOut != '' )
			{
				$monitorRowsOut = str_replace("--$monitorID--", $rowCount, $monitorRowsOut);
				echo $monitorRowsOut;
				$monitorRowsOut = '';
			}
			
		}
		

	}


?>    
    </tbody>
  </table>
</div>

<?php if (david()): ?>
<div id='debugDiv' style='display:inline'></div>
<?php endif; ?>


<div id='customerLogin' style="visibility:hidden">
  <?=$CUSTOMER_EMAIL?>
</div>
<div id='itemID' style="visibility:hidden"></div>
</body>
</html>