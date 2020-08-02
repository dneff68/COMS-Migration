<?
session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once '/var/www/html/CHT/h202/h202Functions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';

function validRemoteIP()
{
	// global $REMOTE_ADDR;
	// $s = $REMOTE_ADDR == "174.67.208.179"; // David Neff's IP
	// $s = $s || $REMOTE_ADDR == '162.223.5.38';
	// return $s;


	global $REMOTE_ADDR;
	$s = $REMOTE_ADDR == "174.67.208.179"; // David Neff's IP
	$s = $s || $REMOTE_ADDR == '162.223.5.38';
	$s = $s || $REMOTE_ADDR == '162.223.5.51';
	$s = $s || $REMOTE_ADDR == '162.223.5.52';
	$s = $s || $REMOTE_ADDR == '72.142.78.147';
	$s = $s || $REMOTE_ADDR == '72.142.78.148';
	return $s;

}

function pushHeaderLabel(&$array, $value, $isEncrypted)
{
	$key = "Vbr6FT9oViR8QcV71SgEY85m";
	$iv = "qR93P3yJ";

	if ($isEncrypted)
	{
		// get the amount of bytes to pad
	    $extra = 8 - (strlen($value) % 8);
		if($extra > 0) {
			for($i = 0; $i < $extra; $i++) {
				$value .= "\0";
			}
    	}
		$value = bin2hex(mcrypt_cbc(MCRYPT_3DES, $key, $value, MCRYPT_ENCRYPT, $iv));
	}

	array_push($array, $value);
}

// verify IP Address
if (!validRemoteIP()) 
{
	die( "Invalid IP: Please provide a valid IP address to your COMS administrator" );   
}

$tankQuery = empty($tankID) ? "" : " AND t.monitorID =  '$tankID' ";
$days = empty($days) ? 30 : $days;

// Get Delivery Data
//$tankName = getTankName($tankID);
$query = "SELECT DISTINCT d.deliveryDate as dateToDeliver, t.time as timeToDeliver, s.PO, t.quantity, d.deliveryID, d.lastModified as lastModifiedDate, 
							d.lastModifiedBy, d.product, d.notes, d.noteDate, d.noteAuthor, d.status, tank.tankName, tank.tankID,
							CONCAT(s.siteID, '-', tank.baan_number) as baan
			FROM delivery d, deliverySite s, deliveryTanks t, tank
			WHERE d.deliveryID = s.deliveryID
			AND s.siteID = t.siteID
			AND s.deliveryID = t.deliveryID
			$tankQuery
			and t.monitorID = tank.monitorID
			AND d.deliveryDate >= DATE_ADD(NOW(), interval -$days DAY)
			ORDER BY d.deliveryDate DESC";

$res = getResult($query);
if (checkResult($res))
{
	$output = array();
	$encryptedOutput = array();
	
	// output header
	$RecordsSent = mysql_num_rows($res);
	$QueryString = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
	$date = date("m/d/Y");
	$time = date("H:i:s");
	
	$headerLabel = "header_Start|";
	array_push($output, $headerLabel);
	pushHeaderLabel($encryptedOutput, $headerLabel, true);
	
	$headerLabel = "numberOfRecordsSent|$RecordsSent|";
	array_push($output, $headerLabel);
	pushHeaderLabel($encryptedOutput, $headerLabel, true);

	$headerLabel = "requestQueryString|$QueryString|";
	array_push($output, $headerLabel);
	pushHeaderLabel($encryptedOutput, $headerLabel, true);

	$headerLabel = "transmissionDate|$date|";
	array_push($output, $headerLabel);
	pushHeaderLabel($encryptedOutput, $headerLabel, true);

	$headerLabel = "transmissionTime|$time|";
	array_push($output, $headerLabel);
	pushHeaderLabel($encryptedOutput, $headerLabel, true);

	$headerLabel = "header_End|\n";
	array_push($output, $headerLabel);
	pushHeaderLabel($encryptedOutput, $headerLabel, true);
	
	// output content
	$headerLabel = "orderData_Start|";
	array_push($output, $headerLabel); 
	pushHeaderLabel($encryptedOutput, $headerLabel, true);

	$text = "DeliveryDate|DeliveryTime|Status|MonitorID|TankName|COMS/BAAN|PO|Quantity|Units|LastModifiedBy|LastModifiedDate|LastModifiedTime|";
	array_push($output, $text); 
	pushHeaderLabel($encryptedOutput, $text, true);

	while ($line = mysql_fetch_assoc($res))
	{
		extract($line);
		$text = "$dateToDeliver|$timeToDeliver|$status|$tankID|$tankName|$baan|$PO|$quantity|Gallons|$lastModifiedBy|$lastModifiedDate|00:00:00|";
		array_push($output, $text); 
		pushHeaderLabel($encryptedOutput, $text, true);		
	}
	
	$headerLabel = "orderData_End|";
	array_push($output, $headerLabel);
	pushHeaderLabel($encryptedOutput, $headerLabel, true);
	
	if (david())
	{
		foreach ($output as $val) echo ($val . "\n");
		echo("\n\n");
	}

	foreach ($encryptedOutput as $val) echo ($val . "\n");
}
else
{
	echo( "No Deliveries Found" );
}

?>