<?
session_start();
include_once 'GlobalConfig.php';
include_once 'h202Functions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';

$deliveryRows = '';
$dateMod = '';
$_SESSION['DELIVERY_COMMITTED'] = '';
error_log("PING DELIVERY SUMMARY");

if (empty($SORT_DELIVERIES_BY))
{
	session_register('SORT_DELIVERIES_BY');
	session_register('SORT_ASCENDING');
	session_register('SORT_DESC');
	$SORT_DELIVERIES_BY = 'deliveryDate';
	$SORT_ASCENDING = '';
	$SORT_DESC = 'DESC';

}


if ($REQUEST_METHOD == 'POST')
{
	if (!empty($rdoSortDeliveries))
	{
		$SORT_DELIVERIES_BY = $rdoSortDeliveries;
		$SORT_ASCENDING = $chkAscending == 'ascend' ? 'checked' : '';
		$SORT_DESC = $SORT_ASCENDING == 'checked' ? '' : 'DESC';
	}

	if (!empty($startDate) && !empty($endDate) )
	{		
		if (empty($DELIVERY_STARTDATE))
		{
			session_register('DELIVERY_STARTDATE');
			session_register('DELIVERY_ENDDATE');
		}
		$DELIVERY_STARTDATE = $startDate;
		$DELIVERY_ENDDATE = $endDate;
	}
	
	if (!empty($siteFilter))
	{
		if (empty($DELIVERY_SITE_FILTER))
		{
			session_register('DELIVERY_SITE_FILTER');
		}
		
		$DELIVERY_SITE_FILTER = $siteFilter != 'All' ? $siteFilter : '';
	}
	
	
}


if (empty($DELIVERY_STARTDATE))
{
	$dateMod = " and d.deliveryDate >= DATE_ADD(NOW(), INTERVAL -14 DAY)";
}
else
{
	$dateMod = " and d.deliveryDate >= '$DELIVERY_STARTDATE' and d.deliveryDate <= '$DELIVERY_ENDDATE'";
}

if (!empty($DELIVERY_SITE_FILTER))
{
	$filterMod = " and dt.monitorID='$DELIVERY_SITE_FILTER'";
}

$query = "SELECT DISTINCT
		DATE_FORMAT(d.deliveryDate, '%M %d, %Y (%W)') as deliveryDateFmt,
		DATE_FORMAT(d.lastModified, '%m/%d/%Y') as lastModified,
		d.lastModifiedBy,
		d.deliveryID, 
		d.deliveryKey, 
		d.status 
	FROM delivery d, deliverySite ds
	WHERE d.deliveryID=ds.deliveryID $dateMod
	ORDER BY d.$SORT_DELIVERIES_BY $SORT_DESC, ds.po";

if ($genTxt == 1 && file_exists('/var/www/html/CHT/h202/deliveries.txt'))
	unlink('/var/www/html/CHT/h202/deliveries.txt');
$res = getResult($query);
if (checkResult($res))
{
	while ($line = $res->fetch_assoc())
	{
		extract($line);
	
		$query = "SELECT DISTINCT 
						CONCAT(ds.siteID, '-', t.baan_number) as site_monitor,
						IF(SUBSTRING(dt.time, 1,2)='12','00', SUBSTRING(dt.time, 1,2)) AS noon, 
						IF(LOCATE('pm', dt.time) = 0,'1','2') AS sorting1, 
						ds.PO, dt.time, dt.quantity, t.tankName, t.monitorID 
					from 
						deliverySite ds,
						deliveryTanks dt,
						tank t
					where 
						ds.deliveryID = $deliveryID and
						dt.deliveryID = $deliveryID	and
						ds.siteID = dt.siteID and
						dt.monitorID = t.monitorID
					ORDER BY sorting1, noon, dt.time";

		$po_res = getResult($query);
		if (checkResult($po_res))
		{
			//echoResults($po_res);
			$pos = '';
			while ($pline = mysqli_fetch_assoc($po_res))
			{
				extract($pline);
				
				// loop if this doesn't satisfy the filter
				if ( !empty($DELIVERY_SITE_FILTER) && $DELIVERY_SITE_FILTER != 'All' && $DELIVERY_SITE_FILTER != $monitorID )
				{
					continue;
				}

				$pos .= "<table width='100%'><tr valign='top'><td width='150' align='left'>$PO<br>$tankName</td><td align='right' nowrap>$time&nbsp;$quantity gal</td></tr></table><hr>";
				
				// If requested, output this informatino to a tab delimited text file
				if ($genTxt == 1)
				{
					$textFileRow = $deliveryDateFmt . chr(9) . $site_monitor . CHR(9) . $PO . chr(9) . $tankName . chr(9) . $time . chr(9) . $quantity . chr(9) . $lastModified . chr(9) . $lastModifiedBy . "\n";
					$txtFileRef = fopen('/var/www/html/CHT/h202/deliveries.txt', 'a+');
					fwrite($txtFileRef, $textFileRow);
					fclose($txtFileRef);
				}
				
			}
		}
				
		$rowclass = $rowclass == 'spinTableBarOdd' ? 'spinTableBarEven' : 'spinTableBarOdd';
		
		$statusVal = '';
		if ($status == 'Cancelled')
			$statusVal =  '<span class="spinAlert"><br>-- Cancelled --</span>';
		elseif ($status == 'Change Order')
			$statusVal =  '<span class="spinAlert"><br>-- Change Order --</span>';
		
		$cntRes = getResult("SELECT deliveryID FROM deliveryEmailLog WHERE deliveryID=$deliveryID");
		$totCnt = mysqli_num_rows($cntRes);
		$cntRes = getResult("SELECT deliveryID FROM deliveryEmailLog WHERE deliveryID=$deliveryID AND dateReceived > '0'");
		$readCnt = mysqli_num_rows($cntRes);
		
		if ($totCnt > 0)
		{
			$pctRead = round(($readCnt / $totCnt) * 100, 0);
		}
		else
			$pctRead = 0;

		$modifyCancelDelete = '';
		if ($_SESSION['USERTYPE'] == 'super')
		{
			$modifyCancelDelete = "&nbsp;&nbsp;<a href='/tanks.php?tankAction=deliveryView&deliveryID=$deliveryID&init=yes'>modify/cancel</a>
			  &nbsp;&nbsp;<a href=\"javascript:deleteDelivery($deliveryID)\">delete</a>";
		}

		if (!empty($pos))
		{
			$devMod = strpos($_SERVER['DOCUMENT_ROOT'], 'h202-dev') === false ? '' : '_dev';
//			die($_SERVER['DOCUMENT_ROOT']);
			$deliveryRows .= "
				<tr class='$rowclass'>
				  <td valign='top' nowrap><div align='left'>$deliveryDateFmt $statusVal</div></td>
				  <td align='left'>$pos</td>
				  <td valign='top' nowrap width='150'>
					<div align='left'>
						<a target='_blank' href='manifest.php?id=$deliveryID&key=$deliveryKey'>view</a>&nbsp;&nbsp;
						<a href=\"javascript:surfDialog('/emailSummary.php?id=$deliveryID', 800, 515, window, false)\">email dist</a>
						$modifyCancelDelete
				  </div></td>
				  <td valign='top'>$lastModified</td>
				  <td valign='top'>$lastModifiedBy</td>
				</tr>
				";
		}
	}
}

if ($genTxt == 1)
{
	header("location:/deliveries.txt");
	exit;
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Deliveries</title>
<link rel="stylesheet" TYPE="text/css" href="http://h202.customhostingtools.com/main.css" >
<SCRIPT LANGUAGE="javascript" TYPE="text/javascript" SRC='http://www.customhostingtools.com/lib/admin.js'></SCRIPT>
<script language="JavaScript" src="datetimepicker.js"></script>
<script language="javascript">
<?=$jsClose?>
function deleteDelivery(id)
{
	if (confirm("Are you sure you wish to delete this delivery and all it's details?"))
	{
		quietCommit("/deleteDelivery.php?id=" + id, true);
	}
}
</script>
</head>

<body>
<div id='ActionDiv' name='ActionDiv' style='visibility:hidden;position:absolute;left:0;top:0'>
		<iframe id='ActionFrame' name='ActionFrame' style='background-color:#FFFFFF' scrolling=no width=0 height=0 align=top frameborder=0 
		src=''  align='left' allowtransparency='true' marginheight='0' marginwidth='0' ></iframe>
</div>
<? include 'banner.php'; ?>
<table width="800" border="0" cellpadding="1" cellspacing="1" class="tab2" align="center">
<tr>
<form name="dateForm" action="deliverySummary.php" method="post">
	<td colspan="5">
		<div align="left">Start Date:
					<input readonly name='startDate' id="startDate" type="text" size="10" value="<?=$DELIVERY_STARTDATE?>">
					<a href="javascript:NewCal('startDate','yyyymmdd')">
					<img src="/images/calbtn.gif" width="16" height="16" border="0" alt="Pick a start date"></a>
					&nbsp;
					End Date:
					<input readonly name='endDate' id="endDate" type="text" size="10" value="<?=$DELIVERY_ENDDATE?>">
					<a href="javascript:NewCal('endDate','yyyymmdd')">
					<img src="/images/calbtn.gif" width="16" height="16" border="0" alt="Pick an end date"></a>
					<input type="button" value="Set Date Range" onclick="document.dateForm.submit()" />
                    &nbsp;<a target='_blank' href="<?= "http://h202.customhostingtools.com/index.php?delivery=1&genTxt=1"?>">Create Text File</a>                    
					</div>
                    
	</td>
</form>
</tr>

<tr>
<form name="sortForm" action="deliverySummary.php" method="post">
<td colspan="5" align="left" valign="middle">
Sort by: 
  <label>
    <input type="radio" name="rdoSortDeliveries" id="rdoSortDeliveries" value="deliveryDate" <?= $SORT_DELIVERIES_BY == 'deliveryDate' ? 'checked' : ''?> onchange="document.sortForm.submit()" />
    Delivery Date </label>&nbsp;
  <label>
    <input type="radio" name="rdoSortDeliveries" id="rdoSortDeliveries" value="lastModified" <?= $SORT_DELIVERIES_BY=='lastModified' ? 'checked' : ''?> onchange="document.sortForm.submit()" />
    Last Modified </label>&nbsp;    
 &nbsp;&nbsp;
    <input name="chkAscending" type="checkbox" value="ascend" <?=$SORT_ASCENDING?> onchange="document.sortForm.submit()" /> Ascending
    <br />
    <select name="siteFilter" id="siteFilter" onchange="document.sortForm.submit()">
      <option value="All">-- All Sites --</option>
    <?
    	$res = getResult("SELECT tankName, monitorID FROM tank ORDER BY tankName");
		if (checkResult($res))
		{
			while ($line = $res->fetch_assoc())
			{
				extract($line);
				$sel = $monitorID == $DELIVERY_SITE_FILTER ? ' SELECTED' : '';
				echo("\n<option $sel value='$monitorID'>$tankName</option>");
			}
		}
	?>
    </select>
    </td>
</form>
</tr>
<tr><td colspan="5"><hr /></td></tr>
</tr>
  <tr class="spinMedTitle">
    <td align="left" width="200">Delivery Date</td>
    <td align="left" width="200">PO's</td>
    <td>&nbsp;</td>
    <td width="200" align='center' nowrap="nowrap">Last<br />Modified</td>
    <td align='center' width="200">Modified<br />By</td>
  </tr>
  <?=$deliveryRows?>
</table>
</body>
</html>
