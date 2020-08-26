<?php
session_start();

if ($_SESSION['LOCAL_DEVELOPMENT']=='yes')
{
	include_once 'GlobalConfig.php';
	include_once 'h202Functions.php';
	include_once '../lib/db_mysql.php';
	include_once '../lib/chtFunctions.php';	
}
else
{
	include_once '/var/www/html/CHT/h202/GlobalConfig.php';
	include_once '/var/www/html/CHT/h202/h202Functions.php';
	include_once 'chtFunctions.php';
	include_once 'db_mysql.php';
}
bigEcho("deliveryDetails.php");

$sendInvoices = 'no';
$submitted = 'no';
$tankAction = '';
$clearlist = 'no';
$deliveryProduct = '';
$deliverySupplierID = '';
$deliveryConcentration = '';
$deliveryCarrierID = '';
$truckCapacity = 0;
$totalFill = 0;
$update = 0;
$deliveryTankRows = '';
$modifyDeliveryID = -1;
$truckCaps = '';
extract($_POST);

$USERID = $_SESSION['USERID'];
$_SESSION['STATUS_FILTER'] = '';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


// include_once 'GlobalConfig.php';
// include_once 'h202Functions.php';
// include_once 'chtFunctions.php';
// include_once 'db_mysql.php';


// if (empty($JUMP))
// {
// 	session_register('JUMP');
// }
// else
// {
// 	// set global variable
// 	$jump = $JUMP;
// 	$JUMP = '';
// }

// set global variable
$jump = $_SESSION['JUMP'];
$_SESSION['JUMP'] = '';

if (!empty($id))
{
	// we're modifying an existing delivery
	$modifyDeliveryID = $id;

	// allow for adding tanks to a delivery
	$aTankNotes 	= $TANK_NOTES;
	$aDeliveryTanks = $_SESSION['DELIVERY_TANKS'];
	$aTankDetails 	= $_SESSION['TANK_DETAILS'];

	$TANK_NOTES 	= array();
	$_SESSION['DELIVERY_TANKS'] = array();
	$_SESSION['TANK_DETAILS'] 	= array();
	
	$query = "select monitorID, time, quantity, deliveryUnitQuantity, actual_quantity, notes from deliveryTanks where deliveryID = $id";
//	die("123: " . $query);
	$res = getResult($query);
	if (checkResult($res))
	{	
		$cnt = 0;
		while ($line = $res->fetch_assoc())
		{
			extract($line);
			$_SESSION['DELIVERY_TANKS'][$cnt] = $monitorID;
			$_SESSION['TANK_DETAILS'][$monitorID]['time'] = $time;
			$_SESSION['TANK_DETAILS'][$monitorID]['quantity'] = $quantity; 
			$_SESSION['TANK_DETAILS'][$monitorID]['deliveryUnitQuantity'] = $deliveryUnitQuantity; 
			$TANK_NOTES[$monitorID] = $notes;
			$cnt++;
		}
	}

	if (!empty($aDeliveryTanks))
	{
		foreach($aDeliveryTanks as $tmp_tankName)
		{
			if ( array_search($tmp_tankName, $_SESSION['DELIVERY_TANKS']) === false )
			{
				// add the tank to the array
				array_push($_SESSION['DELIVERY_TANKS'], $tmp_tankName);
				$_SESSION['TANK_DETAILS'][$tmp_tankName]['time'] = $aTankDetails[$tmp_tankName]['time'];
				$_SESSION['TANK_DETAILS'][$tmp_tankName]['quantity'] = $aTankDetails[$tmp_tankName]['quantity']; 
				$_SESSION['TANK_DETAILS'][$tmp_tankName]['deliveryUnitQuantity'] = $aTankDetails[$tmp_tankName]['deliveryUnitQuantity']; 
				$TANK_NOTES[$tmp_tankName] = $aTankNotes[$tmp_tankName];
			}
		}
	}


	$query = "SELECT 
			carrierID as deliveryCarrierID, 
			concentration as deliveryConcentration, 
			deliveryDate, 
			product as deliveryProduct, 
			supplierID as deliverySupplierID, 
			truckCapacity,
			notes
		FROM delivery where deliveryID = $id;";

	$res = getResult($query);
	if (checkResult($res))
	{
		$DELIVERY_DATA = $res->fetch_assoc();
		extract($DELIVERY_DATA);
		$truckCaps = $truckCapacity . ' gallons';
		$_SESSION['DELIVERY_NOTES'] = $notes;
	}
}


if ($sendInvoices == 'yes')
{
	sendDeliveryEmails($modifyDeliveryID);
	$_SESSION['DELIVERY_NOTES'] = '';
	$TANK_NOTES = false;
	$_SESSION['DELIVERY_TANKS'] = array(); //false
	$_SESSION['TANK_DETAILS'] = false;
	$DELIVERY_DATA = false;
	$sentArray = false;
	array_splice($_SESSION['ZIPCOLLECTION'],0);
	unset($_SESSION['ZIPCOLLECTION']);
	$_SESSION['ZIPCOLLECTION'] = '';
	header('location:/deliveryDetails.php');
}

if ($submitted == 'yes')
{
	if (!empty($modifyDeliveryID))
	{
		executeQuery("UPDATE delivery SET lastModified = NOW(), lastModifiedBy = '$USERID', committed=1 WHERE deliveryID=$modifyDeliveryID LIMIT 1");
	}
}


if ($_SERVER['REQUEST_METHOD'] == 'POST')
{

	$_SESSION['JUMP'] = "<a href='#$tankid'>jump to last</a> ";
	
	if ($tankAction == 'cancel')
	{
		$_SESSION['DELIVERY_COMMITTED'] = '';
		$_SESSION['DELIVERY_NOTES'] = '';
		$TANK_NOTES = false;
		$CONVERTED_QUANTITIES = false;
		$_SESSION['DELIVERY_TANKS'] = array(); //false;
		$_SESSION['TANK_DETAILS'] = false;
		$DELIVERY_DATA = false;
		$sentArray = false;
		if (!empty($_SESSION['ZIPCOLLECTION']))
		{
			array_splice($_SESSION['ZIPCOLLECTION'],0);
		}
		unset($_SESSION['ZIPCOLLECTION']);
		$_SESSION['ZIPCOLLECTION'] = '';
		header('location:/deliveryDetails.php');

	}
	elseif (!empty($DELIVERY_DATA) && ($tankAction == 'submit' || $tankAction == 'markcancel'))
	{
		extract($DELIVERY_DATA);
		extract($_POST); // update delivery data with what was posted

		// add to delivery tank
		$carrierID == '-1' ? 'NULL' : $carrierID;
		$truckCapacity = empty($truckCapacity) ? '0' : $truckCapacity ;

	
		if (!empty($deliveryProduct) && sizeof($_SESSION['DELIVERY_TANKS']) > 0)
		{
			$key = generateCode();
			if (!empty($modifyDeliveryID))
			{
				// hang onto the original delivery date so we can preserve the PO
				$origRes = getResult("SELECT DATE_FORMAT(origDeliveryDate, '%y%m%d') as origDeliveryDate FROM delivery WHERE deliveryID = $modifyDeliveryID");
				if (checkResult($origRes))
				{
					$origLine = mysqli_fetch_assoc($origRes);
					extract($origLine);
				}
			
				if ($update == 1)
				{
					$statusVal = 'Ordered';
				}
				else
				{
					$statusVal = $tankAction == 'markcancel' ? 'Cancelled' : 'Change Order';
				}

				updateDeliveryTankStats($modifyDeliveryID);  // update the tank stats 
				
				// modify an existing delivery
				$del_notes = fixSingleQuotes($_SESSION['DELIVERY_NOTES']);
				$query = "UPDATE delivery SET 
						deliveryDate = '$deliveryDate',
						dateOrdered = NOW(),
						lastModified = NOW(),
						lastModifiedBy = '$USERID',
						supplierID = $supplierID,
						carrierID = $carrierID,
						concentration = '$deliveryConcentration',
						product = '$deliveryProduct',
						truckCapacity = $truckCapacity,
						internalContact = '$USERID',
						status = '$statusVal',
						notes = '$del_notes'
						WHERE deliveryID = $modifyDeliveryID LIMIT 1";
				executeQuery($query);
				$deliverySites = getDeliverySites($modifyDeliveryID);
				logAction("Delivery Updated for $deliverySites dated $deliveryDate" );
				$_SESSION['DELIVERY_NOTES'] = '';
				$deliveryID = $modifyDeliveryID;
				//executeQuery("DELETE FROM deliverySite WHERE deliveryID=$deliveryID");
				executeQuery("UPDATE deliverySite SET markDelete=1 WHERE deliveryID=$deliveryID");
				executeQuery("DELETE FROM deliveryTanks WHERE deliveryID=$deliveryID");
			}
			else
			{
				// adding new delivery.  Get the carrier email dist from the carrier table
				$cres = getResult("SELECT deliveryEmailDist as carrierEmails FROM carrier WHERE carrierID=$carrierID");
				if (checkResult($cres))
				{
					$cline = mysqli_fetch_assoc($cres);
					extract($cline);
				}
				
				$del_notes = htmlentities($_SESSION['DELIVERY_NOTES'], ENT_QUOTES);
				$query = "INSERT INTO delivery 
					(
						deliveryDate,
						dateOrdered,
						origDeliveryDate,
						lastModified,
						supplierID,
						carrierID,
						concentration,
						product,
						truckCapacity,
						deliveryKey,
						internalContact,
						carrierEmailDist,
						notes
					) 
					VALUES 
					(
						'$deliveryDate',
						NOW(),
						'$deliveryDate',
						NOW(),
						$supplierID,
						$carrierID,
						'$deliveryConcentration',
						'$deliveryProduct',
						$truckCapacity,
						'$key',
						'$USERID',
						'$carrierEmails',
						'$del_notes'
					)";
				$deliveryID = executeQuery($query, 'INSERT');
				$_SESSION['DELIVERY_COMMITTED'] = $deliveryID;
				$_SESSION['DELIVERY_NOTES'] = '';
				// preserve the supplier email distribution
				$query = "SELECT id, selected FROM supplierEmailDist WHERE supplierID=$supplierID";
				$supRes = getResult($query);
				if (checkResult($supRes))
				{
					while ($supLine = mysqli_fetch_assoc($supRes))
					{
						extract($supLine);
						executeQuery("INSERT INTO deliveryEmailSupplierSelected (deliveryID, emailID, selected) 
						VALUES ($deliveryID, $id, $selected )");
					}
				}
			}
			
	
			$sites_arr = array();
			
			/*
				The following loops through each tank in a delivery.  If a delivery has several tanks 
				with the same PO Code (stored in the site table), then we allow duplicates.  Otherwise
				we append the generated PO with -A, -B, etc.  This is all done in the generatePO()
				function in h202Functions.
			*/
			
			$_SESSION['USED_PO_CODES'] = array();
			foreach($_SESSION['DELIVERY_TANKS'] as $monitorID)
			{
				if (array_search($monitorID, $sites_arr) === false)
				{
					array_push($sites_arr, $monitorID);
					
					$poInfo = generatePO($monitorID, $deliveryDate);
					list($po, $siteID, $po_code) = explode(':', $poInfo);

					// preserve the PO
					if (!empty($origDeliveryDate))
					{
						$poRes = getResult("SELECT PO as po FROM deliverySite WHERE siteID=$siteID AND deliveryID=$deliveryID");
						if (checkResult($poRes))
						{
							$poLine = mysqli_fetch_assoc($poRes);
							extract($poLine);
						}
						else
						{
							$po = $po_code . $origDeliveryDate; 
						}
						
						// at this time we have the correct PO, but if we are modifying a delivery we need to add
						// or iterate a revision number
						$po = iterateRevNo($po);
					}	
					
					
					
					executeQuery("DELETE FROM deliverySite WHERE deliveryID=$deliveryID and siteID=$siteID");
					executeQuery("INSERT INTO deliverySite (deliveryID, siteID, PO) VALUES ($deliveryID, $siteID, '$po')", 'INSERT');
				}
			
				if (strpos($monitorID, '-') !== false)
				{
					// need to swap out the - because it breaks an eval() call later. 
					$monitorOut = str_replace('-', '__', $monitorID);
					
				}
				else
				{
					$monitorOut = $monitorID;
				}
				$monitorOut = trim($monitorOut);
				eval('$time = $time_' . $monitorOut . ';');
				eval('$quantity = $amt_' . $monitorOut . ';');
				
				$deliveryUnitQuantity = $quantity; // preserve the actual value entered before any conversion
				$query = "SELECT deliveryUnits FROM tank WHERE tankID = '$monitorID'";
				$delUnitsRes = getResult($query);
				if (checkResult($delUnitsRes))
				{
					// get weight using ratio
					$delUnitLine = mysqli_fetch_assoc($delUnitsRes);
					extract($delUnitLine);
					
					if ($deliveryUnits == "Pounds")
					{					
						$wRes = getResult("SELECT r.ratio FROM productWeightRatios r, product p WHERE p.prodID=r.prodID and p.value='$deliveryProduct' and '$deliveryConcentration' LIKE CONCAT('%', r.concentration, '%')");
						if (checkResult($wRes))
						{
							$wLine = mysqli_fetch_assoc($wRes);
							extract($wLine);
						}
						else
						{
							$ratio = 1.0;
						}
						$quantity = $quantity / $ratio;
					}
					
					if ($deliveryUnits != "Gallons" && $deliveryUnits != "Unit")
					{
						// convert to gallons
						$quantity = convertUnitsToGallons($quantity, $deliveryUnits);
					}
				}
				
				
				eval('$notes = $note_' . $monitorOut . ';');
				$quantity = $tankAction == 'markcancel' ? 0 : $quantity;

				$notes = fixSingleQuotes($notes);
				$notes = str_replace('"', '&quot;', $notes);
				$notes = fixString($notes);
				
				$query = "INSERT INTO deliveryTanks (deliveryID, siteID, monitorID, quantity, deliveryUnitQuantity, time, notes)	VALUES ($deliveryID, $siteID, '$monitorID', $quantity, $deliveryUnitQuantity, '$time', '$notes')";
				executeQuery($query);
				updateDeliveryTankStats($deliveryID);  // update the tank stats on the dates of the delivery after the update or addition of a new delivery
			}
			executeQuery("DELETE FROM deliverySite WHERE deliveryID=$deliveryID AND markDelete=1");
			$_SESSION['USED_PO_CODES'] = '';
			
			echo('<hr>');
			sendDeliveryEmails($deliveryID, 1); // 1 means display only
			$cancelLink = "<a href='$cancelRef'>Cancel Order</a>&nbsp;";
			
			echo("<br>
				<a href='/deliveryDetails.php?submitted=yes'>Complete Order</a>&nbsp;&nbsp;
				<a href=\"javascript:window.parent.location='/tanks.php?tankAction=deliveryView&deliveryID=$deliveryID&update=1'\">Modify Delivery</a>
				&nbsp;<a href='/deliveryDetails.php?sendInvoices=yes&submitted=yes&id=$deliveryID'>Email Delivery Requests Now</a></br>");

			$TANK_NOTES = false;
			$CONVERTED_QUANTITIES = false;
			$_SESSION['DELIVERY_TANKS'] = array(); //false;
			$_SESSION['TANK_DETAILS'] = false;
			$DELIVERY_DATA = false;
			$sentArray = false;
			array_splice($_SESSION['ZIPCOLLECTION'],0);
			unset($_SESSION['ZIPCOLLECTION']);
			$_SESSION['ZIPCOLLECTION'] = '';
		}
	}
}


// if (empty($DELIVERY_TANKS))
// {
// 	session_register('DELIVERY_TANKS');
// 	session_register('DELIVERY_NOTES');
// 	$DELIVERY_TANKS = array();
// }
$reloadParent = '';
if (!empty($tankAction))
{
	if ($tankAction == 'addTank' && !empty($tankid))
	{
		if (array_search($tankid, $_SESSION['DELIVERY_TANKS']) === false)
		{
			array_push($_SESSION['DELIVERY_TANKS'], $tankid);
			
			$_SESSION['STATUS_FILTER'] = '';
			$reloadParent = "window.parent.location='/index.php?deliveryID=$modifyDeliveryID&status=all';\n";
		}
	}	
	elseif ($tankAction == 'removeTank' && !empty($tankid))
	{
		$key = array_search($tankid, $_SESSION['DELIVERY_TANKS']);
		if ( $key !== false)
		{
			unset($_SESSION['DELIVERY_TANKS'][$key]);
		}
	}
}

$divVis = "";
if (sizeof($_SESSION['DELIVERY_TANKS']) == 0)
{
	$divVis = "style=\"visibility:hidden;height:0\"";
	$DELIVERY_DATA = false;
}
elseif (sizeof($_SESSION['DELIVERY_TANKS']) == 1)
{
	$reorderInfo = reorderInfo(first($_SESSION['DELIVERY_TANKS']));
	extract($reorderInfo);
	if ($REQUEST_METHOD == 'POST')
	{
		$supplierID = $_POST['supplierID']; // override what is stored in first tank
	}
	
	$DELIVERY_DATA['deliveryProduct'] = $product;
	$DELIVERY_DATA['deliveryConcentration'] = $concentration;
	$DELIVERY_DATA['deliverySupplierID'] = $supplierID;
	$DELIVERY_DATA['deliveryDate'] = $reorderInfo['fillDate'];
	
	// get deliveryUnits
	$query = "SELECT deliveryUnits FROM tank where monitorID = '$monitorID' LIMIT 1";
	$dRes = getResult($query);
	if (checkResult($dRes))
	{
		$dline = mysqli_fetch_assoc($dRes);
		extract($dline);
	}
	
	$res = getResult("SELECT c.carrierName as carrierName1, c.carrierID as carrierID1 FROM tank t, carrier c WHERE t.carrierID	= c.carrierID AND t.monitorID = '$monitorID' LIMIT 1");
	if (checkResult($res))
	{
		$line = $res->fetch_assoc();
		extract($line);
	}
	$DELIVERY_DATA['deliveryCarrierID'] = $carrierID1;
	
	$truckRes = getResult("SELECT DISTINCT capacity as truckCapacity1 FROM truck WHERE tankID = '$monitorID' ORDER BY capacity");
	$truckCaps = '';
	if (checkResult($truckRes))
	{
		$truckCaps = '';
		while ($truckline = mysqli_fetch_assoc($truckRes))
		{
			extract($truckline);
			$truckCaps .= "<option value='$truckCapacity1'>$truckCapacity1 gallons</option>\n";
		}
	}
	$DELIVERY_DATA['truckCaps'] = $truckCaps;
	$DELIVERY_DATA['truckCapacity'] = $truckCapacity1;


}

if (sizeof($_SESSION['DELIVERY_TANKS']) > 0)
{
	if (!empty($supplierID))
	{
		$DELIVERY_DATA['deliverySupplierID'] = $supplierID;
		//die("$supplierID --");
	}

	if (!empty($carrierID))
	{
		$DELIVERY_DATA['deliveryCarrierID'] = $carrierID;
	}

	if (!empty($deliveryDate))
	{
		$DELIVERY_DATA['deliveryDate'] = $deliveryDate;
	}

	if ($REQUEST_METHOD == 'POST')
	{
		
		$_SESSION['TANK_DETAILS'] = '';
		$_SESSION['TANK_DETAILS'] = array(); // start fresh and build

	
		foreach ($_SESSION['DELIVERY_TANKS'] as $monitorID)
		{
			if (strpos($monitorID, '-') !== false)
			{
				// need to swap out the - because it breaks an eval() call later. 
				$monitorOut = str_replace('-', '__', $monitorID);
			}
			else
			{
				$monitorOut = $monitorID;
			}
			$monitorOut = trim($monitorOut);

			eval('$t = $time_' . $monitorOut . ';');
			$_SESSION['TANK_DETAILS'][$monitorID]['time'] = $t;
			if (empty($_SESSION['TANK_DETAILS'][$monitorID]['time']))
			{
				$timeres = getResult("select timeOfDelivery from tank where monitorID='$monitorID' LIMIT 1");
				if (checkResult($timeres))
				{
					$timeline = mysqli_fetch_assoc($timeres);
					extract($timeline);
					$_SESSION['TANK_DETAILS'][$monitorID]['time'] = $timeOfDelivery;
				}
			}			

			eval('$q = $amt_' . $monitorOut . ';');
	
			if (!empty($q) && $tankAction != 'changeTime')
			{
				$_SESSION['TANK_DETAILS'][$monitorID]['quantity'] = $q;
			}
			else
			{
				$_SESSION['TANK_DETAILS'][$monitorID]['quantity'] = $refillAmount; 
			}
			//$_SESSION['TANK_DETAILS'][$monitorID]['deliveryUnitQuantity'] = $q;
		}
	}
	extract($DELIVERY_DATA);
	
}
else
{
	$DELIVERY_DATE = '';
	$deliveryDate = '';
}

$js = '';
if ($tankAction == 'showEmailDist')
{
	$js = "surfDialog(\"/deliveryEmailDist.php?deliveryID=$modifyDeliveryID&init=yes\", 800, 515, window, false);\n";
}

 function getDeliveryUnitFmt($units)
 {
	if ($units == 'Ton_Metric')
		return 'Metric Tons'; 
	else if ($units == 'Ton_US')
		return 'Tons - US'; 
	else if ($units == 'Kilogram')
		return 'Kilograms'; 
	else if ($units == 'Liters')
		return 'Liters'; 
	else if ($units == 'Drum')
		return 'Drums'; 
	else if ($units == "Tote_300")
		return 'Totes'; 
	else if ($units == "Tote_320")
		return 'Totes'; 
	else if ($units == "Tote_330")
		return 'Totes'; 
	else if ($units == 'Unit')
		return 'Units'; 
	else
		return $units;	 
 }

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Tank Details</title>
<link rel="stylesheet" TYPE="text/css" href="<?php echo $_SESSION['ROOT_URL']?>main.css" >
<link rel="stylesheet" href="ui_theme/themes/base/jquery.ui.all.css">

<SCRIPT LANGUAGE="javascript" TYPE="text/javascript" SRC='<?php echo $_SESSION['LIB_URL']?>/admin.js'></SCRIPT>
<script language="JavaScript" src="datetimepicker.js"></script>
<script src="<?php echo $_SESSION['LIB_URL']?>/jquery.js" type="text/javascript"></script>
<script src="<?php echo $_SESSION['LIB_URL']?>/jquery-ui.custom.min.js" type="text/javascript"></script>

<script language="javascript" type="text/javascript">

<?php echo $reloadParent?>
<?php echo $js?>

$(document).ready(function() {
	$( "#messageBox" ).dialog(
		{
        	autoOpen: false,
			width: 400
		}
	);
 });
 
 function getDeliveryUnitFmt(units)
 {
	if (units == 'Ton_Metric')
		return 'Metric Tons'; 
	else if (units == 'Ton_US')
		return 'Tons - US'; 
	else if (units == 'Kilogram')
		return 'Kilograms'; 
	else if (units == 'Liters')
		return 'Liters'; 
	else if (units == 'Drum')
		return 'Drums'; 
	else if (units == "Tote_300")
		return 'Totes'; 
	else if (units == "Tote_320")
		return 'Totes'; 
	else if (units == "Tote_330")
		return 'Totes'; 
	else if (units == 'Unit')
		return 'Units'; 
	else
		return units;	 
 }
 
function convertUnits(value, units, ratio)
{
	<?php	
		echo "\n // product: " . $DELIVERY_DATA['deliveryProduct'] ; 
		echo "\n // concentration: " . $DELIVERY_DATA['deliveryConcentration'] . "\n\n" ; 
	?>
	
	if (typeof ratio != 'undefined')
		value = value / ratio;
	
	if (units == 'Ton_Metric')
	{
		value = value * 2204.6226218;			
	}
	else if (units == 'Ton_US')
	{
		value = value * 2000;
	}
	else if (units == 'Kilogram')
	{
		value = value * 2.20462262;
	}
	else if (units == 'Liters')
	{
		value = value / 3.78541178;
	}
	else if (units == 'Drum')
	{
		value = value * 55;
	}
	else if (units == "Tote_300")
	{
		value = value * 300;
	}
	else if (units == "Tote_320")
	{
		value = value * 320;
	}
	else if (units == "Tote_330")
	{
		value = value * 330;
	}
	else if (units == "Pounds")
	{
		value = value * 1;
	}
	
	units = getDeliveryUnitFmt(units);

	return  new Array(units, value);
}


function showMessage(title, message)
{
	$( "#messageBox" ).dialog("option", "title", title);
	$( "#messageBox" ).html(message);
	$( "#messageBox" ).dialog("open");
}

function showConversion(tankID, units, ratio)
{
	var elementName = tankID.indexOf('none-') >= 0 ? tankID.replace('-', '__') : tankID;
	var value = $("#amt_" + elementName).val();
	var title = "Delivery Units";
	var conversion = convertUnits(value, units, ratio);
	units = conversion[0];
	convertedValue = conversion[1].toFixed(1); 
	var message = value + " " + units + " = " + convertedValue + ' Gallons';
	message = "<div style='font-size: smaller'>" + message + "</div>";
	showMessage(title, message);
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

function showActualDeliveryEdit(divID)
{
	obj = document.getElementById(divID);
	if (typeof(obj) == "undefined")
	{
		return;
	}
	
	obj.innerHTML = "Actual: <input id=\"txtActual_" + divID + "\" type=\"text\" id=\"txtActual_" + divID + "\" size=\"5\" maxlength=\"6\"  onkeypress=\"return numbersonly(this, event)\" /> <a href='javascript:setActualDelivered(\"" + divID + "\")'>set</a>";
	return;
}

function showChangeRTUEdit(divID)
{
	// divID is = rtu__$monitorID
	obj = document.getElementById(divID);
	if (typeof(obj) == "undefined")
	{
		return;
	}
	
	obj.innerHTML = "RTU: <input id=\"txtRTU_" + divID + "\" type=\"text\" size=\"5\" maxlength=\"12\"  onkeypress=\"return numbersonly(this, event)\" /> <a href='javascript:changeRTU(\"" + divID + "\")'>set</a>";
	return;
}

function changeRTU(divID)
{
	var rtuVal = $("#txtRTU_" + divID).val();
	$.post("ajax_handler.php",  { "action": "changeRTU", "divID": divID, "RTU": rtuVal }, function(resultStr){
		$("#" + divID).html(resultStr);
	});
	
}

function setActualDelivered(divID)
{
	httpObject = getHTTPObject();
	if (httpObject != null) 
	{
		httpObject.open("GET", "ajax_handler.php?action=showActualDeliveryEdit&divID=" + divID + "&actual=" + document.getElementById('txtActual_' + divID).value, true);
		httpObject.send(null);
		httpObject.onreadystatechange = showNormalDelivery(divID);
	}
}

function showRTU(divID)
{
	alert('RTU Value Set: ' + divID);
	if(httpObject.readyState == 4)
	{
		alert('readystate good');
		document.getElementById(divID).innerHTML = httpObject.responseText;
	}
}

function showNormalDelivery(divID)
{
	alert('Actual Delivered Set');
	if(httpObject.readyState == 4)
	{
		document.getElementById(divID).innerHTML = httpObject.responseText;
	}
}

function CallNewCal(dateKey, dateVal)
{
	NewCal(dateKey, dateVal);
}

function submitDelivery()
{
	document.deliveryForm.tankAction.value = "submit";
	document.deliveryForm.submit();
}

function markCanceled()
{
	document.deliveryForm.tankAction.value = "markcancel";
	document.deliveryForm.submit();
}

function cancelModify()
{
	document.deliveryForm.tankAction.value = "cancel";
	document.deliveryForm.submit();
}

function setDelTime(monitorID)
{
	var hidden_obj = document.getElementById("time_" + monitorID); // hidden fld
	tmp = "hr_" + monitorID;
	hr = document.getElementById(tmp);
	mn = document.getElementById('mn_' + monitorID);
	ampm = document.getElementById('ampm_' + monitorID);
	hidden_obj.value = hr.value + ':' + mn.value + ' ' + ampm.value;
}

function changeDate(newdate)
{
	document.deliveryForm.tankAction.value = "changeTime";
	document.deliveryForm.submit();
}

function updateTankQuantity()
{
	document.deliveryForm.confirmButton.disabled='disabled';
	document.deliveryForm.tankAction.value = "updateQuantity";
	document.deliveryForm.submit();
}

function checkTank(name, checked)
{
	if (checked)
	{
		document.deliveryForm.tankAction.value = "addTank";
		document.deliveryForm.tankid.value = name;
	}
	else
	{
		document.deliveryForm.tankAction.value = "removeTank";
		document.deliveryForm.tankid.value = name;
	}
	document.deliveryForm.submit();

}
</script>
<style type="text/css">
<!--
.style1 {font-size: 11px}
.style2 {font-size: 12px}
.style3 {color: #0000CC}
.style4 { text-align:center; font-size: 24px}
-->
</style>
</head>

<body>
<?php
if ($clearlist == 'yes')
{
	array_splice($_SESSION['ZIPCOLLECTION'],0);
	unset($_SESSION['ZIPCOLLECTION']);
	$_SESSION['ZIPCOLLECTION'] = '';
}

// include selected delivery tanks
$selTanks = '';

//if (count($_SESSION['DELIVERY_TANKS'] > 0))
if (!empty($_SESSION['DELIVERY_TANKS']))
{
	foreach ($_SESSION['DELIVERY_TANKS'] as $selectedMonitorID)
	{
		$selTanks .= "m.monitorID='$selectedMonitorID' or ";	
	}
	if (!empty($selTanks))
	{
		$selTanks = substr($selTanks, 0, strlen($selTanks)-4);
		$selTanks = "($selTanks)";
	}
}


if (!empty($zip))
{
	if (!array_key_exists($zip, $_SESSION['ZIPCOLLECTION']))
	{
		$_SESSION['ZIPCOLLECTION'][$zip] = 1; // value of 1 is just a holder
	}
}

$more = "";
if (count($_SESSION['ZIPCOLLECTION']))
{
	$more = " and (";
	foreach ($_SESSION['ZIPCOLLECTION'] as $key => $storedzip)
	{
//		$more .= "s.zip = $key || ";
		$more .= "s.zip LIKE '$key%' || ";
	}
	$more .= !empty($selTanks) ? $selTanks . ')' : 'false)'; // end the or

}

//if (empty($marr))
//{
//	session_register('marr');
	$marr = array();
//}



$regfilt = '';
//if (!empty($REGION_FILTER) && $REGION_FILTER != 'all')

if ($_SESSION['REGION_FILTER'] != '' && $_SESSION['REGION_FILTER'] != 'all')
{	
	$regfilt = "and s.regionID=" . $_SESSION['REGION_FILTER'];
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
	$custTanks = '';
	$inac = $_SESSION['SHOWINACTIVE'] != 'yes' ? " && m.status != 'Inactive'" : '';
	$tmpshut   = $_SESSION['SHOWTEMPSHUTDOWN'] != 'yes' ? " && m.status != 'Temporary Shutdown'" : '';
	$unmonFilt = $_SESSION['SHOWUNMONITORED'] 	== 'yes' ? '' : "and t.monitorID NOT LIKE 'none%'";
}

$query = "select s.siteID, s.siteLocationName as 'Location', s.city as City, 
		s.state as State, s.zip, t.tankName, t.tankID, t.usableVolume, m.rtuID, m.units, m.hideProcessLink, t.diameter, m.monitorID, t.notes
		from monitor m, tank t, site s
		where 
		t.monitorID=m.monitorID and
		m.siteID = s.siteID $custTanks $inac $tmpshut $unmonFilt $more $regfilt order by t.tankName";

$res = getResult($query);
if (checkResult($res))
{
	$rowcnt = 0;
	$totalFill = 0;
	$rows = '';
	$deliveryTankRows = '';
	$notesOut = '';

	while ($line = $res->fetch_assoc())
	{
		extract($line);
		$reorderInfo = reorderInfo($monitorID, $deliveryDate);
		if ($reorderInfo)
		{
			extract($reorderInfo);
			if (!empty($deliveryProduct))
			{
				$match = ($deliveryProduct == $product);
				$match = $match && ($deliveryConcentration == $concentration);

				if (!$match)
				{
					continue;
				}
			}
		}
		else
		{
			if (!empty($deliveryProduct))
			{
				continue;  // we have no reorder info on this tank.
			}
			$fillDate = 'no data';
			$refillAmount = '0';
		}
		
		$dataRes = getResult("SELECT date as lastReading, value as lastValue, if(CAST(date as date) = CAST(NOW() as date), 'yes', 'no') as isToday 
							FROM data WHERE monitorID='$monitorID' and value > 0 ORDER BY date DESC LIMIT 1");
		if (checkResult($dataRes))
		{
			$dataLine = mysqli_fetch_assoc($dataRes);
			extract($dataLine);
			if ($units == 'Inches')
			{
				$lastValue = inchToGal($lastValue, $diameter);
			}
		}
		else
		{
			$lastValue = 'No Value';
		}
	
		$statres = getResult("SELECT avgDose as normalizedDose FROM tankStats WHERE monitorID='$monitorID' ORDER BY readingDate DESC LIMIT 1");
		if (checkResult($statres))
		{
			$statline = mysqli_fetch_assoc($statres);
			extract($statline);
		}
		else
		{
			$normalizedDose = 'no data';
		}
		
		$status = checkTankLevel($monitorID);
		list($statkey, $status) = explode(',', $status);


		// If this is a reorder filter and a leadtime override value
		// has been selected, override whatever checkTankLevel() returned
		// since it gets it's value from the stats table.
		if ($_SESSION['STATUS_FILTER'] =='Reorder' && $_SESSION['LEADTIME_OVERRIDE'] != 'default')
		{
			$reorderData = reorderInfo($monitorID);
			if ($reorderData['daysToDelivery'] <= $_SESSION['LEADTIME_OVERRIDE'])
			{
				$status = 'Reorder';
				$statkey = 'Reorder';
			}
			else
			{
				$statkey = 'OK';
				$status = 'Level Ok';
			}
		}

		$fontColor = $statkey == 'reorder' ? '#FFFF00' : '#ffffff';
		$status = empty($status) ? '&nbsp;' : $status;
		$status = "<span style=\"color:#000000\">$status</span>";
		$deliveryAverage = getDeliveryAvg($monitorID);

		$inSelection = array_search($monitorID, $_SESSION['DELIVERY_TANKS']) !== false;

		if ( empty($_SESSION['STATUS_FILTER']) || $statkey == $_SESSION['STATUS_FILTER'] || $_SESSION['STATUS_FILTER'] == 'all' || $inSelection)
		{
			$mkey = str_replace('-', '_', $monitorID);
			$href = "javascript:parent.doAction('showMap');parent.frames['mapFrame'].marker" . $mkey . ".openInfoWindowHtml(parent.frames['mapFrame'].marker" . $mkey . ".html)";
			$lastReadingStyle = $isToday == 'yes' ? 'style2' : 'spinAlert';
			$tankinfo = "<table border='0' width='200' border='0' cellpadding='2' cellspacing='1' class='spinTableBarOdd'>
						  <tr style='border-bottom:thin'>
							<td width='150'><strong>Usable Volume</strong> </td>
							<td width='50' align='right'>$usableVolume</td>
						  </tr>
						  <tr>
							<td><strong>Weighted Average</strong> </td>
							<td align='right'>$deliveryAverage</td>
						  </tr>
						  <tr valign='top'>
							<td nowrap><strong>Current Level</strong> <span class='$lastReadingStyle'><br>($lastReading)</span> </td>
							<td align='right'>$lastValue</td>
						  </tr>
						</table>";
						
			$tableHTML = "<table width='100%' border='0' cellpadding='2' cellspacing='1' class='spinTableBarOdd'>";		
			
			// get previous delivery
			$query = "SELECT DISTINCT 
				s.PO, 
				d.deliveryID,
				d.deliveryDate as prevDeliveryDate, 
				t.quantity as prevQuantity,
				t.actual_quantity,
				t.time as prevTime
			FROM
				delivery d, 
				deliverySite s, 
				deliveryTanks t
			WHERE
				d.deliveryID = s.deliveryID AND 
				s.siteID = t.siteID AND
				s.deliveryID = t.deliveryID AND
				t.monitorID = '$monitorID' AND
				d.status != 'Cancelled'
			ORDER BY d.deliveryDate DESC LIMIT 3";
			$res2 = getResult($query);
			
			//echoResults($res2);
			$style = "";
			$prevDeliveryHTML = '';
			$deliveryNote = '';
			
			$tres = getResult("SELECT tankName, deliveryUnits from tank where monitorID = '$monitorID'");
			if (checkResult($tres))
			{
				$tline = mysqli_fetch_assoc($tres);
				extract($tline);				
			}
			else
			{
				$deliveryUnits = "Gallons";
				$tankName = strpos($monitorID, 'none-') === false ? $monitorID : '';
			}
	
			if (checkResult($res2))
			{
				while ($line2 = mysqli_fetch_assoc($res2))
				{
					extract($line2);
					$cntRes = getResult("SELECT deliveryID FROM deliveryEmailLog WHERE deliveryID=$deliveryID");
					$totCnt = mysqli_num_rows($cntRes);
					$cntRes = getResult("SELECT deliveryID FROM deliveryEmailLog WHERE deliveryID=$deliveryID AND dateReceived != '0000-00-00 00:00:00'");
					$readCnt = mysqli_num_rows($cntRes);
					
					
					// check to see if there is a supplier for this site... 
					$query1 = "SELECT category FROM deliveryEmailLog WHERE deliveryID=$deliveryID and category='supplier'";
					$cntRes1 = getResult($query1);
					if (checkResult($cntRes1))
					{
						$query2 = "SELECT category FROM deliveryEmailLog WHERE deliveryID=$deliveryID AND dateReceived != '0000-00-00 00:00:00' AND category='supplier'";
						$cntRes2 = getResult($query2);
					}
					else
					{
						$query = "SELECT category FROM deliveryEmailLog WHERE deliveryID=$deliveryID AND dateReceived != '0000-00-00 00:00:00' AND category='carrier'";
						$cntRes2 = getResult($query);
					}
					
					
					if (checkResult($cntRes2))
					{
						$style = "style='color:#336666'";
					}
					
					if ($totCnt > 0)
					{
						$pctRead = round(($readCnt / $totCnt) * 100, 0);
					}
					else
						$pctRead = 0;
					
					$modCancel = '&nbsp;';
					if ($_SESSION['USERTYPE'] == 'super')
					{
						$modCancel = "<a target='_parent' href='tanks.php?tankAction=deliveryView&deliveryID=$deliveryID'>modify/cancel</a>";
					}
					
				if ($_SESSION['USERTYPE'] == 'super' || $_SESSION['USERTYPE'] == 'service')
				{
									if ($actual_quantity > 0)
									{
										$quantityOut = "Qty: <strong>$prevQuantity gal</strong>&nbsp;&nbsp; - &nbsp;&nbsp;<a href=\"javascript:showActualDeliveryEdit('del_" . $deliveryID . "__" . $monitorID . "')\">Actual: <strong>$actual_quantity</strong></a>";
									}
									else
									{
										$quantityOut = "Qty: <strong>$prevQuantity gal</strong>&nbsp;&nbsp; - &nbsp;&nbsp;<a href=\"javascript:showActualDeliveryEdit('del_" . $deliveryID . "__" . $monitorID . "')\">set actual delivered</a></strong>";
									}
									
									$prevDeliveryHTML .= "\n
									$tableHTML
										<tr style='border-bottom:thin'>
											<td width='118' nowrap>PO: <strong>$PO</strong></td>
											<td width='135' nowrap align='right'>$prevDeliveryDate</td>
										  </tr>
										  <tr>
											<td id='del_" . $deliveryID . "__" . $monitorID . "' nowrap>$quantityOut</td>
											<td align='right' width='135' nowrap>Time: $prevTime</td>
										  </tr>
										  <tr><td><a $style href=\"javascript:surfDialog('/emailSummary.php?id=$deliveryID',800,515,window,false)\">Email: $pctRead%</a>
										  </td><td align='left'>$modCancel</td></tr>
										</table> <hr>";
									$style = "";

				}
				else
				{
									$prevDeliveryHTML .= "\n
									$tableHTML
										<tr style='border-bottom:thin'>
											<td width='118' nowrap>PO: <strong>$PO</strong></td>
											<td width='135' nowrap align='right'>$prevDeliveryDate</td>
										  </tr>
										  <tr>
											<td width='118'>Quantity: <strong>$prevQuantity gal</strong></td>
											<td align='right' width='135' nowrap>Time: $prevTime</td>
										  </tr>
										  <tr><td><a href=\"javascript:surfDialog('/emailSummary.php?id=$deliveryID',800,515,window,false)\">Email: $pctRead%</a>
										  </td><td align='left'>$modCancel</td></tr>
										</table> <hr>";
				}

				}
				$prevDeliveryHTML = substr($prevDeliveryHTML, 0, strlen($prevDeliveryHTML) - 5);
			}

			$checked = '';
			$key = array_search($monitorID, $_SESSION['DELIVERY_TANKS']);
			if ( $key !== false)
			{
				$checked = " checked='checked'";
				$refillAmount = $refillAmount + 0;
				
				if (strpos($monitorID, '-') !== false)
				{
					// need to swap out the - because it breaks an eval() call later. 
					$monitorOut = str_replace('-', '__', $monitorID);
				}
				else
				{
					$monitorOut = $monitorID;
				}
				$monitorOut = trim($monitorOut);

				if ($tankAction != 'changeTime')
				{
					eval('$postedFillAmount = $amt_' . $monitorOut . ';');
					$refillAmount = empty($postedFillAmount) ? $refillAmount : $postedFillAmount; // this overrides the stored quantity if a change is posted
				}

				eval('$postedTime = $time_' . $monitorOut . ';');
				$timeOfDelivery = $_SESSION['TANK_DETAILS'][$monitorID]['time'];
				$timeOfDelivery = empty($postedTime) ? $timeOfDelivery : $postedTime;

				if (!empty($timeOfDelivery))
				{
					list($hrmn, $ampm) = explode(' ', $timeOfDelivery);
					list($hr, $mn) = explode(':', $hrmn);
					$h1 = $hr=='01' ? 'SELECTED' : '';
					$h2 = $hr=='02' ? 'SELECTED' : '';
					$h3 = $hr=='03' ? 'SELECTED' : '';
					$h4 = $hr=='04' ? 'SELECTED' : '';
					$h5 = $hr=='05' ? 'SELECTED' : '';
					$h6 = $hr=='06' ? 'SELECTED' : '';
					$h7 = $hr=='07' ? 'SELECTED' : '';
					$h8 = $hr=='08' ? 'SELECTED' : '';
					$h9 = $hr=='09' ? 'SELECTED' : '';
					$h10 = $hr=='10' ? 'SELECTED' : '';
					$h11 = $hr=='11' ? 'SELECTED' : '';
					$h12 = $hr=='12' ? 'SELECTED' : '';
					
					$m0 = $mn=='00' ? 'SELECTED' : '';
					$m15 = $mn=='15' ? 'SELECTED' : '';
					$m30 = $mn=='30' ? 'SELECTED' : '';
					$m45 = $mn=='45' ? 'SELECTED' : '';
					
					$am = $ampm=='am' ? 'SELECTED' : '';
					$pm = $ampm=='pm' ? 'SELECTED' : '';
				}
				if (strpos($monitorID, '-') !== false)
				{
					// need to swap out the - because it breaks an eval() call later. 
					$monitorOut = str_replace('-', '__', $monitorID);
				}
				else
				{
					$monitorOut = $monitorID;
				}
				$monitorOut = trim($monitorOut);

 				$deliveryTimeHTML = "
				  <input name='time_$monitorOut' type='hidden' id='time_$monitorOut' value='$timeOfDelivery' />
				  <label>
				  <select name='hr_$monitorOut' id='hr_$monitorOut' onchange='setDelTime(\"$monitorOut\")'>
					<option value='01' $h1>01</option>
					<option value='02' $h2>02</option>
					<option value='03' $h3>03</option>
					<option value='04' $h4>04</option>
					<option value='05' $h5>05</option>
					<option value='06' $h6>06</option>
					<option value='07' $h7>07</option>
					<option value='08' $h8>08</option>
					<option value='09' $h9>09</option>
					<option value='10' $h10>10</option>
					<option value='11' $h11>11</option>
					<option value='12' $h12>12</option>
				  </select>
				  </label>
				  <strong>:</strong>
				  <select name='mn_$monitorOut' id='mn_$monitorOut' onchange='setDelTime(\"$monitorOut\")'>
				  <option value='00' $m0>00</option>
				  <option value='15' $m15>15</option>
				  <option value='30' $m30>30</option>
				  <option value='45' $m45>45</option>
					</select> 
				<select name='ampm_$monitorOut' id='ampm_$monitorOut' onchange='setDelTime(\"$monitorOut\")'>
				  <option value='am' $am>am</option>
				  <option value='pm' $pm>pm</option>
					</select>";
				
				$notes = empty($TANK_NOTES[$monitorID]) ? $notes : $TANK_NOTES[$monitorID];
				$notesOut = "\n<input type=\"hidden\" id=\"note_$monitorOut\" name=\"note_$monitorOut\" value=\"$notes\" />\n";

				if (empty($notes))
				{
					$notesOut .= "<a href='javascript:surfDialog(\"deliveryNote.php?id=$monitorID\", 470, 215, window, false)'>add note</a>";
				}
				else
				{
					$notesOut .= strlen($notes) > 50 ? substr($notes, 0, 50) . '...' : $notes;
					$notesOut .= " (<a href='javascript:surfDialog(\"deliveryNote.php?id=$monitorID\", 470, 215, window, false)'>view/modify note</a>)";
					//die($notesOut);
				}

				$quantity = $_SESSION['TANK_DETAILS'][$monitorID]['quantity'];
				if ($tankAction == 'changeTime')
				{
					$newFillInfo = reorderInfo($monitorID, $deliveryDate);
					$refillAmount = $newFillInfo['refillAmount']; 
				}
				elseif (!empty($quantity) && empty($postedFillAmount))
				{
					$refillAmount = empty($refillAmount) ? $quantity : $refillAmount; // doing a modify of a delivery
				}
				else
				{				
					//die("amt: $amt    --     refillAmount: $refillAmount");
					$refillAmount = empty($amt) ? $refillAmount : $amt; 
				}	
				
				if ($deliveryUnits != "Drum")
				{
					$refillAmount = floor($refillAmount);
					//$refillAmount = floor($refillAmount / 100);
					//$refillAmount = $refillAmount * 100;
				}
				// override with posted amount if necessary
				
				$refillAmount = empty($postedFillAmount) ? $refillAmount : $postedFillAmount;
				$totalFill += $refillAmount;
				// customer wants the quantity to be preserved when the date is changed.
				if ($tankAction == "changeTime" && !empty($modifyDeliveryID) )
				{
					//ddie('-- one moment --');
					eval('$q = $amt_' . $monitorOut . ';');
					if (!empty($q))
					{
						$refillAmount = $q;
					}
				}

				$hideConversion = $deliveryUnits != "Gallons" && $deliveryUnits != "Unit" ? "" : '; display: none';
				//bigecho(" $hideConversion = $deliveryUnits != 'Gallons' && $deliveryUnits != 'Unit' ? '' : '; display: none'; " );
				$deliveryUnitsfmt = getDeliveryUnitFmt($deliveryUnits);
				$refillAmountVal = $refillAmount;
			    if ($tankAction == "updateQuantity")	
				{
					$CONVERTED_QUANTITIES[$monitorID][0] = $refillAmountVal;
				}

				if ( $deliveryUnits != "Gallons" && $deliveryUnits != "Unit" )
				{
					if (!array_key_exists($monitorID, $CONVERTED_QUANTITIES))
					{
						$ratio = 1.0;
						// for pounds, get the ratio
						if ($deliveryUnits == 'Pounds')
						{
							// get weight using ratio
							$wRes = getResult("SELECT r.ratio FROM productWeightRatios r, product p WHERE p.prodID=r.prodID and p.value='" . $DELIVERY_DATA['deliveryProduct'] . "' and '" . $DELIVERY_DATA['deliveryConcentration'] . "' LIKE CONCAT('%', r.concentration, '%')");
							if (checkResult($wRes))
							{
								$wLine = mysqli_fetch_assoc($wRes);
								extract($wLine);
							}
							$weightarr = convertUnits($refillAmount * $ratio, $deliveryUnits);
						}
						else
						{
							$weightarr = convertUnits($refillAmount, $deliveryUnits);
						}

						$refillAmountVal = ceil( $weightarr[1] );
						$CONVERTED_QUANTITIES[$monitorID] = array($refillAmountVal, $ratio); // store converted value
					}
				}


				// showArray($CONVERTED_QUANTITIES);
				// showArray($CONVERTED_QUANTITIES[$monitorID]);
				$refillAmountVal = empty($CONVERTED_QUANTITIES[$monitorID]) ? $refillAmountVal : $CONVERTED_QUANTITIES[$monitorID][0];
				//bigecho("refillAmountVal = $refillAmountVal   --  refillAmount=$refillAmount ");
				$ratioOut = $deliveryUnits == 'Pounds' ? ', ' . $CONVERTED_QUANTITIES[$monitorID][1] : '';	
				$refillAmountVal = $_SESSION['TANK_DETAILS'][$monitorID]['deliveryUnitQuantity'] > 0 ? $_SESSION['TANK_DETAILS'][$monitorID]['deliveryUnitQuantity'] : $refillAmountVal;			
				$deliveryTankRows .= "
				<tr class='spinBoxedNormal'>
				  <td nowrap='nowrap' class='spinSmallTitle'><div align='left'>$tankName</div></td>
				  <td nowrap='nowrap' class='spinSmallTitle'>
				  <div style='float: left'>

				  	<input style='padding-left: 4px; width:40px'
						onChange=\"updateTankQuantity()\" 
				  		onkeypress=\"return numbersonly(this, event, 'amt_$monitorOut')\" 
						size='4' 
						type='text' 
						name='amt_$monitorOut' 
						id='amt_$monitorOut' 
						value='$refillAmountVal'>
					</div>
					<div style='float: left; padding-top: 3px; padding-left: 3px; font-size: smaller'> ($deliveryUnitsfmt)</div>
					<div  style='float: right; padding-right: 9px; padding-top: 3px $hideConversion'><a alt='view conversion' href='javascript:showConversion(\"$monitorID\", \"$deliveryUnits\" $ratioOut)'>?</a></div>
					<div style='clear: both'></div>
					</td>
				  <td nowrap='nowrap' class='spinSmallTitle'><div align='left'>$deliveryTimeHTML</div></td>
				  <td nowrap='nowrap' class='spinSmallTitle'><div align='right' style='font-size:9px'>$notesOut</div></td>
				</tr>";
			}

	$weekendFormat = '';
	if (strpos($fillDate, 'Saturday') !== false || strpos($fillDate, 'Sunday') !== false)
	{
		$weekendFormat = " class='spinAlert'";
	}
			$deliveryinfo = "
						$tableHTML
						  <tr>
						  <td nowrap align='left'>Days Until Delivery:</td><td align='right'><strong>$daysToDelivery</strong></td></tr>
						  <tr valign='top'>
							<td nowrap align='left'>Reorder Date:</td><td $weekendFormat align='right'>$fillDate</td>
						</tr>
						<tr>
							<td align='left'>Reorder Level:</td><td align='right'>$reorderLevel</td>
						  </tr>
						  <tr>
							<td align='left'>Quantity:</td>
							<td align='right'><strong>$refillAmount</strong></td>
						  </tr>
							</table>";

			
			$checkboxhtml = '&nbsp;';
			if ($_SESSION['USERTYPE'] == 'super')
			{
				$checkboxhtml = "<input type=\"checkbox\" name=\"$monitorID\" value='check' $checked id=\"$monitorID\" onClick='checkTank(this.name, this.checked)'/>";
			}
			$zippart = substr($zip, 0, 3);
			$noteRes = getResult("SELECT deliveryNote, deliveryNoteDate, deliveryNoteAuthor from tank where monitorID = '$monitorID'");
			if (checkResult($noteRes))
			{
				$noteLine = mysqli_fetch_assoc($noteRes);
				extract($noteLine);				
				if ( strlen($deliveryNote) > 225)
				{
					$deliveryNote = substr($deliveryNote, 0, 225) . '...';
				} 
				$deliveryNote = "$deliveryNoteDate: $deliveryNoteAuthor<br>$deliveryNote";
			}
			$tankDeliveryNote = "<br><br><div class='spinGreen' id='tdelNote_$monitorID' name='tdelNote_$monitorID'>$deliveryNote</div>";
			//$pct = strpos('%', $concentration) === false ? '%' : '';
			
			$edit_addEditNote = '';

			$rtuID_out = '';
			if ($_SESSION['USERTYPE'] == 'super')
			{
				// get existing rtuID if it exists
				// if ((david() || jim()) && !empty($rtuID))
				if ( !empty($rtuID) )
				{
					$rtuID_out = "<span id='rtu__$monitorID'>RTU: <a href='javascript:showChangeRTUEdit(\"rtu__$monitorID\")'>$rtuID</a></span> &nbsp;"; 
				}
				
				$edit_addEditNote = "&nbsp;
				<a href=\"javascript:window.parent.location='addTank.php?init=yes&mon=$monitorID';\">edit</a>&nbsp;
				<a href=\"javascript:surfDialog('deliveryNote.php?tankDelivID=$monitorID',470,215,window,false)\">add/edit note</a>
				$rtuID_out
				$tankDeliveryNote";
			}
			
			
			// Get the Process Link if it's suppose to be visible
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
					<a id='processLink_$monitorID' href=\"javascript:surfDialog('<?php echo $_SESSION['ROOT_URL']?>charts/processGraph.php?monitorID=$monitorID', 835, 550, window, false)\">process $txt_hidden</a>";
			}
			
			
			$group = $_SESSION['USERTYPE'] == 'customer' ? '' : "<a href='<?php echo $_SESSION['ROOT_URL']?>deliveryDetails.php?zip=$zippart'>group</a>&nbsp;";
			$tankNameOut = $_SESSION['USERTYPE'] == 'customer' ? "<strong>$Location</strong>" : "<a href=\"$href\">$Location</a>";
			
			// add link to customer page 
			// look up monitor id in customer
			$monitorSiteID = getSiteID($monitorID);
			//$query = "SELECT email as customerEmail FROM customerLoginEmail WHERE siteID LIKE '%$monitorSiteID%'";
			if ( $_SESSION['USERTYPE'] != 'customer' )
			{
				$query = "SELECT c.email as customerEmail FROM customerLoginEmail c, customer cust WHERE c.customerID=cust.customerID and concat(',',cust.siteID,',') LIKE '%,$monitorSiteID,%'";
				$monitorRes = getResult($query);
				if (checkResult($monitorRes))
				{
					$monitorLine = mysqli_fetch_assoc($monitorRes);
					extract($monitorLine);
					$tankNameOut = $tankNameOut . "<br><a style='font-size:smaller;color:#336666' target='_blank' href='index.php?customerEmail=$customerEmail'>(customer summary)</a>";
				}
			}

			$marr[$mkey] = "<tr class=\"spinTableBarOdd\">
				<td valign='top'><a name='a_$tankName' />$checkboxhtml</td>
				<td valign='top'>$tankNameOut<br><br>$tankName<br>$product: $concentration
				<br>
				$group &nbsp;
				<a href=\"javascript:surfDialog('/charts/tankGraph.php?tab=2&tankID=$monitorID',830, 430,window,false)\">graph</a>
				$processLink
				$edit_addEditNote
				
				</td>
				<td valign='top'>$tankinfo<br>$deliveryinfo</td>
				<td valign='top'>$prevDeliveryHTML</div>
				</td>
			  </tr>";
			$rowcnt++;
		}
		else
		{
			//$debug .= "$monitorID<br>";
		}
	}
}

$rowcnt = sizeof($marr);
if (count($_SESSION['ZIPCOLLECTION']) > 0) // && $_SESSION['STATUS_FILTER'] != 'unass')
{
	$title = "<td colspan=\"3\"><div align=\"right\"><a href='deliveryDetails.php?clearlist=yes'>reset list</a></div></td>";
}
else
{
	$t2 = '&nbsp;'; //$_SESSION['STATUS_FILTER'] == 'unass' ? '&nbsp;' : 'All Tanks';
	$title = "<td colspan=\"3\"><div align=\"right\">$t2</div></td>";
}

?> 
<iframe id='ActionFrame'
			 name='ActionFrame'
			 style='width:0px; height:0px; border:0px'
			 src=''></iframe>

<div id="infoDiv" <?php echo $divVis?>>
<div id="messageBox" style="display: none"></div>
<form name="deliveryForm" action="deliveryDetails.php" method="post" />
<input type='hidden' name='modifyDeliveryID' id='modifyDeliveryID' value='<?php echo !empty($id) ? $id : $modifyDeliveryID?>' />
<input type='hidden' name='update' id='update' value='<?=$update?>'  />
<input type="hidden" id="tankAction" name="tankAction" value='' />
<input type="hidden" id="tankid" name="tankid" value='' />
  <table width="100%" border="0" cellpadding="1" cellspacing="1" class="tab2">
  
  <?php
  	$change_order = 0;
  	if (!empty($modifyDeliveryID))
	{
		$res_status = getResult("SELECT status FROM delivery WHERE deliveryID=$modifyDeliveryID and status = 'Change Order' LIMIT 1");		
		if (checkResult($res_status))
		{
			$change_order = 1;
		}
	}
  ?>
  
  <?php if ( $change_order || ( (!empty($modifyDeliveryID) || !empty($id)) && $update != 1 ) ) : ?>
    <tr align="center" class="tab1">
      <td height="34" colspan="5" nowrap="nowrap" class="style4"><div align="center">Change Order</div></td>
      </tr>
  <?php endif; ?>
    <tr>
      <td width="108" height="34" nowrap="nowrap" class="spinSmallTitle"><div align="left">Delivery Date: </div>
      <td width="147" nowrap="nowrap">
        <div align="left">
        <?php
        	// $delivery date may have formatting.  Strip it for the value
			if ( strpos($deliveryDate, '(') !== false )
			{
				$deliveryDateNoFmt = substr($deliveryDate, 0, 10);
			}
			else
			{
				$deliveryDateNoFmt = $deliveryDate;
			}
		?>
			<input name='deliveryDate' id="deliveryDate" type="text" size="10" value="<?=$deliveryDateNoFmt?>" onchange="changeDate(this.value)">
			<a href="javascript:CallNewCal('deliveryDate','yyyymmdd')">
			<img src="/images/calbtn.gif" width="16" height="16" border="0" alt="Pick a date"></a>		
			</div></td>
      <td width="117" class="spinSmallTitle"><div align="left">Product:</div></td>
      <td width="145" class="spinSmallTitle">
	  <input type="hidden" name="deliveryProduct" id="deliveryProduct" value="<?=$deliveryProduct?>"  />
	  <div align="left" class="style2"><?=$deliveryProduct?> </div></td>
      <td width="213" nowrap="nowrap" class="spinSmallTitle"><a href='javascript:document.deliveryForm.tankAction.value="showEmailDist"; document.deliveryForm.submit();'>Email Distribution</a></td>
    </tr>
    <tr>
      <td class="spinSmallTitle"><div align="left">Supplier:</div></td>
      <td><label>
        <div align="left">
          <select name="supplierID" id="supplierID">
		  <?php
		  	$res = getResult("SELECT supplierName as supName, supplierID as supid FROM supplier ORDER BY supplierName");
			if (checkResult($res))
			{
				while ($line = $res->fetch_assoc())
				{
					extract($line);
					$chk = ($supid == $deliverySupplierID) ? 'SELECTED' : '';
					echo "<option value='$supid' $chk>$supName</option>";
				}
			}
		  ?>
          </select>
        </div>
      </label></td>
      <td class="spinSmallTitle"><div align="left">Concentration:</div></td>
      <td class="spinSmallTitle">
	  <input type="hidden" name="deliveryConcentration" id="deliveryConcentration" value="<?=$deliveryConcentration?>"  />
	  <div align="left" class="style2"><?=$deliveryConcentration?></div></td>
      <td class="spinSmallTitle">&nbsp;</td>
    </tr>
    <tr>
      <td class="spinSmallTitle"><div align="left">Carrier:</div></td>
      <td><div align="left">
          <select name="carrierID" id="carrierID">
		  <option value='-1' <?=$deliveryCarrierID=='-1' ? 'SELECTED' : ''?>>--none--</option>
		  <?php
		  	$res = getResult("SELECT carrierName as carName, carrierID as carid FROM carrier ORDER BY carrierName");
			if (checkResult($res))
			{
				while ($line = $res->fetch_assoc())
				{
					extract($line);
					$chk = ($carid == $deliveryCarrierID) ? 'SELECTED' : '';
					echo "\n<option value='$carid' $chk>$carName</option>";
				}
			}
		  ?>
		  </select>
      </div>	  </td>
      <td valign="top" class="spinSmallTitle"><div align="left" class="header_3">Truck: <br />
        (<?=$truckCapacity - $totalFill?> free)
		</div></td>
      <td class="spinSmallTitle"><div align="left" class="style2">
	  <select name='truckSel'><?=$truckCaps?></select> </div></td>
      <td class="spinSmallTitle">
            
		<?php if (!empty($modifyDeliveryID) || !empty($id) ): ?>	
		<?php
			$devID = empty($modifyDeliveryID) ? $id : $modifyDeliveryID;
			$res = getResult("SELECT status FROM delivery WHERE deliveryID = $devID AND status = 'Cancelled' LIMIT 1");
			if (mysqli_num_rows($res) > 0)
			{
				echo "<br />-- Delivery Cancelled --";
			}
			else
			{
				if ($update==1 && !empty($deliveryID) && $_SESSION['DELIVERY_COMMITTED'] == $modifyDeliveryID)
				{
					$cancelDeliveryButton = '';
				}
				else
				{
					$cancelDeliveryButton = "<br /><input type='button' name='confirmButton' id='confirmButton' value='Cancel Delivery' onclick='markCanceled()' />";
				}
				
				echo $cancelDeliveryButton;
			}
		?>
          
		<?php endif ; ?>
          <br /><input type="button" name="confirmButton"  id="confirmButton" value="<?= $update != 1 && ((!empty($modifyDeliveryID) || !empty($id)))? 'Submit Change' : 'Submit Request'?>" onclick="submitDelivery()" />

		  </td>
    </tr>
  </table>
    <table width="100%" border="1" cellpadding="1" cellspacing="1" class="tab2">
		<tr class='spinBoxedNormal'>
		  <td colspan="4" align="center" nowrap='nowrap' class="spinSmallTitle style3">-- Tanks Selected for Delivery --</td>
		</tr>
				<tr class='spinBoxedNormal'>
				  <td width="150" nowrap='nowrap' class='spinLargeTitle'><div align='center'>Tank</div></td>
				  <td style="width:150px" nowrap='nowrap' class='spinLargeTitle'><div align='center'>Quantity</div></td>
				  <td width="150" nowrap='nowrap' class='spinLargeTitle'><div align='center'>Delivery Time</div></td>
				  <td nowrap='nowrap' class='spinLargeTitle'><div align='center'>Notes</div></td>
				</tr>
		<?=$deliveryTankRows?>
  </table>
<?php
	$delID = !empty($modifyDeliveryID) ? "?delID=$modifyDeliveryID" : '';
	$delID = empty($delID) && !empty($id) ? "?delID=$id" : '';
	
	if ($update==1 && !empty($modifyDeliveryID) && $_SESSION['DELIVERY_COMMITTED'] == $modifyDeliveryID)
	{
		$cancelLink = "javascript:quietCommit(\"/deleteDelivery.php?id=$modifyDeliveryID\", true)";
	}
	else
	{
		$cancelLink = "javascript:cancelModify()";		
	}
	
?>
   <a href = '<?=$cancelLink?>'>cancel</a>&nbsp;<a href='javascript:surfDialog("deliveryNote.php<?=$delID?>",470,215,window,false)'>delivery notes</a>&nbsp;   
  <?= $jump ?>
</form>
</div>

<table width="100%" border="1" align="left" cellpadding="3" cellspacing="0" bordercolorlight="#333333">
  <tr class="spinTableBarOdd">
    <td width="46">&nbsp;</td>
    <?php
    	$countOut = $rowcnt > 1 ? "Showing $rowcnt Tanks" : '&nbsp;';
	?>
    <td align='left'><?=$countOut?></td>
    <?=$title?>
  </tr>
  <tr class="spinTableTitle">
   <!-- <td><div align="center" class="style1">Customer Site</div></td>
    <td><div align="center" class="style1">Location</div></td>
    -->
	<td class="spinTableTitle">&nbsp;</td>
	<td width="200" class="spinTableTitle"><div align="center" class="style1">Tank / Monitor ID</div></td>
    <td width="200" class="spinTableTitle"><div align="center" class="style1">Tank Information </div></td>
    <td width="275" class="spinTableTitle"><div align="center" class="style1">Deliveries</div></td>
  </tr>
  
<?php
//=$rows
foreach ($marr as $row)
{
	echo($row);
}
?>

</table><br /><br /><br /><br />
</body>
</html>
