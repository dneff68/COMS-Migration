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


function addSiteToArray($siteID, &$arr)
{
	
	$query = "SELECT 
				site.siteID, 
				site.latlng as siteLatLng, 
				site.zip as siteZip, 
				t.tankName, 
				m.monitorID, 
				t.carrierID, 
				t.usableVolume, 
				carr.carrierName, 
				supp.supplierName, 
				m.LatLng as monitorLatLng, 
				carr.zip as carrierZip
			FROM 
				tank t, 
				monitor m, 
				carrier carr, 
				supplier supp, 
				site
			WHERE
				site.siteID = $siteID and
				m.siteID = $siteID  and
				m.monitorID = t.monitorID and
				carr.carrierID = t.carrierID and
				supp.supplierID = t.supplierID and 
				carr.zip != ''";
	$res = getResult($query);
	if (checkResult($res))
	{
		$line = mysql_fetch_assoc($res);
		extract($line);
		
		if (empty($monitorLatLng))
		{
			if (!empty($siteLatLng))
			{
				$monitorLatLng = $siteLatLng;
			}
			else
			{
				// look up zip 
				$query = "SELECT CONCAT(lat, ',', lng) as monitorLatLng FROM zipcodes WHERE zip = '$siteZip' LIMIT 1";
				$cRes = getResult($query);
				if (checkResult($cRes))
				{
					$cline = mysql_fetch_assoc($cRes);
					extract($cline);
				}
			}
		}
		
		$query = "SELECT CONCAT(lat, ',', lng) as carrierLatLng FROM zipcodes WHERE zip = '$carrierZip' LIMIT 1";
		$cRes = getResult($query);
		if (checkResult($cRes))
		{
			$cline = mysql_fetch_assoc($cRes);
			extract($cline);
		}
		$arr[$monitorID] = "$tankName~$carrierName~$supplierName~$usableVolume~$carrierLatLng~$monitorLatLng";
	}
}


$tankArray = array();

if (!empty($custEmail))
{
	$query = "SELECT distinct
				cust.siteID as siteIDs
			FROM
				customer cust, customerLoginEmail custEmail
			WHERE
				cust.customerID = custEmail.customerID and
				custEmail.email = '$custEmail'";

		$res = getResult($query);
		if (checkResult($res))
		{
			$line = mysql_fetch_assoc($res);
			extract($line);
			$sites = explode(',', $siteIDs);
			foreach ($sites as $siteID)
			{
				if (!empty($siteID)) 
					addSiteToArray($siteID, $tankArray);
			}
			
		}
}
else
{
	$query = "SELECT siteID
		FROM site where zip != ''";
	$res = getResult($query);
	if (checkResult($res))
	{
		while ($line = mysql_fetch_assoc($res))
		{
			extract($line);
			addSiteToArray($siteID, $tankArray);
		}
	}
}






?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Carrier Info</title>
<link rel="stylesheet" TYPE="text/css" href="http://h202.customhostingtools.com/main.css" >
<link rel="stylesheet" TYPE="text/css" href="planning.css" >
<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
<script src="http://www.customhostingtools.com/lib/jquery.js" type="text/javascript"></script>

<script type="text/javascript">

function getDistance(start, end, divID)
{
	directionsService = new google.maps.DirectionsService();
	var request = {
	  origin: start,
	  destination: end,
	  travelMode: google.maps.DirectionsTravelMode.DRIVING
	};
	
	directionsService.route(request, function(response, status) {
	if (status == google.maps.DirectionsStatus.OK) {
		var distance = response.routes[0].legs[0].distance;
		var distanceStr = distance.text;
		distanceStr = distanceStr.replace(' mi', '');
		  $('#' + divID).html(distanceStr);
		}
	});	
}


$(document).ready( function()
{
<?
asort($tankArray);
foreach ($tankArray as $monitorID => $monitorInfo)
{
	$tankName = '';
	$carrier = '';
	$supplier = '';
	$usableVolume = '';
	$carrierLatLng = '';
	$monitorLatLng = '';
	list($tankName, $carrier, $supplier, $usableVolume, $carrierLatLng, $monitorLatLng) = explode('~', $monitorInfo);
	echo "\ngetDistance('$carrierLatLng','$monitorLatLng','distDiv_$monitorID');";
}
?>	
});

</script>
</head>

<body>
<table width="746" border="1" align="center" cellpadding="6" cellspacing="1">
  <tr style="font-size:20px" class="customerBanner2">
    <td width="145" height="39">Tank Name</td>
    <td width="146">Carrier</td>
    <td width="154">Supplier</td>
    <td width="91">Usable Volume</td>
    <td width="176">Distance (miles)</td>
  </tr>
  
<?


foreach ($tankArray as $monitorID => $monitorInfo)
{
	list($tankName, $carrier, $supplier, $usableVolume, $carrierLatLng, $monitorLatLng) = explode('~', $monitorInfo);
	$usableVolume = number_format($usableVolume);
	echo "<tr align='left' class='category-row'>
		<td nowrap='nowrap' height='29'>$tankName</td>
		<td nowrap='nowrap'>$carrier</td>
		<td nowrap='nowrap'>$supplier</td>
		<td align='right'>$usableVolume</td>
		<td align='right' id='distDiv_$monitorID'></td>
	  </tr>";
}
?>  
  
</table>
<?
//showArray($tankArray);
?>
</body>
</html>
