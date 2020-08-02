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

if (empty($BUDGET_START_DATE))
{
	session_register('BUDGET_START_DATE');
	session_register('BUDGET_END_DATE');
	session_register('BUDGET_START_DATE_FMT');
}

if (!empty($endDate))
{
	$BUDGET_START_DATE = $startDate;
	$BUDGET_END_DATE = $endDate;
	list($y, $m, $d) = explode('-', $BUDGET_START_DATE);
	$BUDGET_START_DATE_FMT = "$m/$d/$y";
}

if (empty($BUDGET_START_DATE))
{
	$query = "select CONCAT(YEAR(now()), '-01-01') as BUDGET_START_DATE, date(NOW()) as BUDGET_END_DATE";
	$res = getResult($query);
	$line = mysql_fetch_assoc($res);
	extract($line);
	list($y, $m, $d) = explode('-', $BUDGET_START_DATE);
	$BUDGET_START_DATE_FMT = "$m/$d/$y";
}

//if ($_POST)
//{
//	bigecho($BUDGET_START_DATE);
//	bigecho($BUDGET_END_DATE);
//	showPostVars();
//}

function getTankRow($monitorID)
{
	global $deliveryCostForTank, $BUDGET_START_DATE, $BUDGET_END_DATE;
	$tankName = getTankName($monitorID);
	$output = "<table bordercolor='#888888' width='798' border='1' align='center' cellpadding='6' cellspacing='0'>
  <tr>
    <th width='782' colspan='8' class='customerBanner' style='font-size:20px; height:20px' scope='col'> $tankName
    </th>
  </tr>
<tr>";

	
	//$dateRange = "d.deliveryDate > date_add(NOW(), interval -12 month)";
	$dateRange = "d.deliveryDate > '$BUDGET_START_DATE' AND d.deliveryDate <= '$BUDGET_END_DATE'";

	$query = "SELECT DISTINCT 
		s.PO, 
		d.deliveryID,
		d.deliveryDate, 
		t.quantity,
		t.actual_quantity,
		t.time
	FROM
		delivery d, 
		deliverySite s, 
		deliveryTanks t
	WHERE
		d.deliveryID = s.deliveryID AND 
		s.siteID = t.siteID AND
		s.deliveryID = t.deliveryID AND
		t.monitorID = '$monitorID' AND
		d.status != 'Cancelled' and
		$dateRange
	ORDER BY d.deliveryDate DESC";

	$deliveryRes = getResult($query);

	$deliveryCostForTank = 0;
	while ($line = mysql_fetch_assoc($deliveryRes))
	{
		extract($line);
		
		// get costPerGallon
		$query = "SELECT costPerGallon FROM costHistory WHERE date <= '$deliveryDate' and monitorID='$monitorID' and costPerGallon > 0 ORDER BY date DESC limit 1";
		$costRes = getResult($query);
		if (!checkResult($costRes))
		{
			// get the most recent cost per gallon
			//bigecho("SELECT costPerGallon FROM costHistory WHERE monitorID='$monitorID' and costPerGallon > 0 ORDER BY date DESC limit 1");
			$costRes = getResult("SELECT costPerGallon FROM costHistory WHERE monitorID='$monitorID' and costPerGallon > 0 ORDER BY date DESC limit 1");
		}
		
		$costPerGallon = 0;
		if (checkResult( $costRes ))
		{
			$costLine = mysql_fetch_assoc($costRes);
			extract($costLine);
		}	
		
		$costOfDelivery = 0;
		if ($actual_quantity > 0)
		{
			$costOfDelivery = $actual_quantity * $costPerGallon ;
			$quantityOut = "Qty: <strong>$quantity gal</strong>&nbsp;&nbsp; - &nbsp;&nbsp;<a href=\"javascript:showActualDeliveryEdit('del_" . $deliveryID . "__" . $monitorID . "')\">Actual: <strong>$actual_quantity</strong></a>";
		}
		else
		{
			$costOfDelivery = $quantity * $costPerGallon;
			$quantityOut = "Qty: <strong>$quantity gal</strong>&nbsp;&nbsp; - &nbsp;&nbsp; <a href=\"javascript:showActualDeliveryEdit('del_" . $deliveryID . "__" . $monitorID . "')\">set actual delivered</a>";
		}
		
		$deliveryCostForTank += $costOfDelivery;
		$costOfDelivery = '$ ' . number_format($costOfDelivery);
			
		$output .=  "<tr><td>
		<table width='100%' border='0' cellpadding='6' cellspacing='1'>
				<tbody class='customerBanner2' style='font-size:smaller'>
				  <tr style='border-bottom:thin'>
					<td width='270' nowrap=''>PO: <strong>$PO</strong></td>
					<td width='394'><div class='floatLeft' style='padding-left:40px'>Date: $deliveryDate</div</td>
					<td width='329' rowspan='2' align='right' valign='top' id='deliveryCostTD$deliveryID'>Delivery Cost: $costOfDelivery</td>
				  </tr>
				  <tr>
					<td nowrap=''><div id='del_" . $deliveryID . "__" . $monitorID . "'>
					$quantityOut
					</div></td>
					<td width='394'><div class='floatLeft' style='padding-left:40px'>Time: $time</div></td>
				  </tr>
				</tbody>
			  </table></td></tr>";  
	} 	
	return $output . "</table>";
}
?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Budget History</title>
<link rel="stylesheet" TYPE="text/css" href="http://h202.customhostingtools.com/main.css" >
<link rel="stylesheet" TYPE="text/css" href="planning.css" >
<SCRIPT LANGUAGE="javascript" TYPE="text/javascript" SRC='http://www.customhostingtools.com/lib/helper.js'></SCRIPT>
<SCRIPT LANGUAGE="javascript" TYPE="text/javascript" SRC='http://www.customhostingtools.com/lib/admin.js'></SCRIPT>
<script src="http://www.customhostingtools.com/lib/jquery.js" type="text/javascript"></script>
<script src="datetimepicker.js" type="text/javascript"></script>
<script type="text/javascript">
function showActualDeliveryEdit(divID)
{
//	obj = document.getElementById(divID);
//	if (typeof(obj) == "undefined")
//	{
//		return;
//	}
//	
	var divVal = "Actual: <input id=\"txtActual_" + divID + "\" type=\"text\" id=\"txtActual_" + divID + "\" size=\"5\" maxlength=\"6\"  onkeypress=\"return numbersonly(this, event)\" /> <a href='javascript:setActualDelivered(\"" + divID + "\")'>set</a>";

	$('#' + divID).html(divVal);	
	return;
}


function setActualDelivered(divID)
{
	var actual = $('#txtActual_' + divID).val();
//	alert(divID);
	$.get("ajax_handler.php?action=showActualDeliveryEdit&divID=" + divID + "&actual=" + actual, function(data){
//		$('#' + divID).html(data);	
		window.location.reload();

	});
	
}

function showNormalDelivery(divID)
{
	alert('Actual Delivered Set');
	if(httpObject.readyState == 4)
	{
		document.getElementById(divID).innerHTML = httpObject.responseText;
	}
}

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
</head>
<body>
<table bordercolor="#888888" width="798" border="1" align="center" cellpadding="6" cellspacing="0">
<tbody id="totalsBanner">
</tbody>
  <tr>
    <td colspan="2"><form name="dateForm" action="budgetHistory.php" method="post">
        <div align="left">Start Date:
          <input readonly="" name="startDate" id="startDate" type="text" size="12" value="<?=$BUDGET_START_DATE?>">
          <a href="javascript:NewCal('startDate','yyyymmdd')"> <img src="/images/calbtn.gif" width="16" height="16" border="0" alt="Pick a start date"></a> &nbsp;&nbsp;End Date:
          <input readonly="" name="endDate" id="endDate" type="text" size="12" value="<?=$BUDGET_END_DATE?>">
          <a href="javascript:NewCal('endDate','yyyymmdd')"> <img src="/images/calbtn.gif" width="16" height="16" border="0" alt="Pick an end date"></a>
          <input type="button" value="Set Date Range" onClick="submitDates()">
        </div>
        <input type="hidden" value='<?=$customerID?>' name='customerID' id="customerID">
      </form></td>
  </tr>
</table>
<?  
	$totalDeliveryCost = 0;
	$bannerContentsArray = array();
	if (!empty($customerID))
	{
		// Get each monitorID for the customer and output
		$query = "SELECT c.name, c.siteID as siteIDs from customer c, customerLoginEmail cs WHERE c.customerID=cs.customerID AND c.customerID='$customerID' LIMIT 1";
		//$query = "SELECT cs.siteID as siteIDs from customerLoginEmail cs WHERE cs.customerID='$customerID'";
		$custRes = getResult($query);
		if (checkResult($custRes))
		{
			while ($custLine = mysql_fetch_assoc($custRes))
			{
				extract($custLine);
				$sites = explode(',', $siteIDs);
				foreach ($sites as $siteID)
				{
					$monRes = getResult("SELECT monitorID FROM monitor WHERE siteID=$siteID");
					if (checkResult($monRes))
					{
						while ($monLine = mysql_fetch_assoc($monRes))
						{
							extract($monLine);
							$tankRow = getTankRow($monitorID);  
							echo $tankRow;
							//array_push( $bannerContentsArray, '<tr class="customerBanner2"><td width="627" align="right">Cost for '. $monitorID .':</td><td align="right" width="141"> ' . $totalDeliveryCost . '</td></tr>');
							$bannerContentsArray[$monitorID] = $deliveryCostForTank;
						}
					}
				}
			}
		}
	}
	elseif (!empty($monitorID))
	{
		$tankRow = getTankRow($monitorID);  
		echo $tankRow;
	}
	
$totalDeliveryCost = '$ ' . number_format($totalDeliveryCost);

?>
<script type="text/javascript">
$(document).ready(function() {
  // Handler for .ready() called.
/*
  <tr class="customerBanner2">
    <td colspan="2" id='deliveryTotals'><h2 style='text-align:center'>Neff Company</h2></td>
  </tr>
  <tr class="customerBanner2">
    <td width="627" align="right" id='deliveryTotals2'>Cost for Test Tank:</td>
    <td width="141" id='deliveryTotals2'> $3,343</td>
  </tr>
  <tr class="customerBanner2" style="font-weight:bold">
    <td align="right" id='deliveryTotals3'>Total Costs:</td>
    <td id='deliveryTotals3'>$4,545</td>
  </tr>

*/
	var bannerContents = "  <tr class='customerBanner2'><td colspan='2' id='deliveryTotals'><h2 style='text-align:center'>Budget History: <?=$name?></h2></td> </tr>";
//	bannerContents += '<tr class="customerBanner2"><td width="627" align="right" id="deliveryTotals2">Cost for Test Tank:</td><td align="right" width="141" id="deliveryTotals2"> $3,343</td></tr>';
	<?
		foreach ($bannerContentsArray as $monitorID => $totalCosts)
		{
			if ($totalCosts > 0)
			{
				$totalDeliveryCost += $totalCosts;
				$tankName = getTankName($monitorID);
				$totalCosts = '$ ' . number_format($totalCosts);
				echo "\nbannerContents += '<tr class=\"customerBanner2\"><td width=\"627\" align=\"right\">Cost for $tankName:</td><td align=\"right\" width=\"141\"> $totalCosts</td></tr>'";
			}
		}
		$totalDeliveryCost = '$ ' . number_format($totalDeliveryCost);
	?>


	bannerContents += '<tr class="customerBanner2" style="font-weight:bold"><td align="right" id="deliveryTotals3">Total Costs:</td><td align="right" id="deliveryTotals3"><?=$totalDeliveryCost?></td></tr>';
	$('#totalsBanner').html(bannerContents);

	//$('#deliveryTotals').html("<h2 style='text-align:center'><?=$name?></h2><br>Total Delivery Cost: <?=$totalDeliveryCost?>");
});

</script>
</body>
</html>