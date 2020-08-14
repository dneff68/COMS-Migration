<?
session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once '/var/www/html/CHT/h202/h202Functions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';

if ($action == "showActualDeliveryEdit")
{
		$divID = substr($divID, 4);
		list($deliveryID, $monitorID) = explode('__', $divID);
		$res = getResult("SELECT quantity from deliveryTanks WHERE deliveryID=$deliveryID AND monitorID='$monitorID' LIMIT 1");
		//echo "SELECT quantity from deliveryTanks WHERE deliveryID=$deliveryID AND monitorID='$monitorID' LIMIT 1";
		if (checkResult($res))
		{
			$line = $res->fetch_assoc();	
			extract($line);
			$divID = "del_" . $deliveryID . "__" . $monitorID;
			$quantityOut = "Qty: <strong>$quantity gal</strong>&nbsp;&nbsp; - &nbsp;&nbsp;<font color='#BB0000'><a href=\"javascript:showActualDeliveryEdit('$divID')\">Actual: <strong>$actual</strong></a></font>";
			executeQuery("UPDATE deliveryTanks SET actual_quantity=$actual WHERE  deliveryID=$deliveryID AND monitorID='$monitorID' LIMIT 1");
			$tankName = getTankName($monitorID);
			$deliveryTime = getDeliveryInfo($deliveryID);
			generateStats($monitorID, $deliveryTime);
			logAction("Actual Delivery Amount modified for $tankName scheduled for $deliveryTime");
			echo $quantityOut;		
		}
}
elseif ($action == "changeRTU")
{
		//$divID = substr($divID, 4);
		list($junk, $monitorID) = explode('__', $divID);
		$query = "UPDATE monitor SET rtuID='$RTU' WHERE monitorID='$monitorID' LIMIT 1";
		executeQuery($query);
		$tankName = getTankName($monitorID);
		logAction("RTU id updated for $tankName");
		$rtuID_out = "RTU: <a href='javascript:showChangeRTUEdit(\"rtu__" . $monitorID. "\")'>$RTU</a>"; 
		echo $rtuID_out;
}


?>