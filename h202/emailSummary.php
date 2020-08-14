<?
session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once '/var/www/html/CHT/h202/h202Functions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';

if (empty($id))
{
	die('Invalid Delivery Order ID');
}

$query = "SELECT DISTINCT s.siteID, d.deliveryID, d.carrierID, s.siteLocationName, s.deliveryEmailDist, d.carrierEmailDist, 
	DATE_FORMAT(d.deliveryDate, '%M %D, %Y (%W)') as deliveryDateFmt FROM delivery d, deliverySite ds, site s
			WHERE d.deliveryID=$id AND d.deliveryID=ds.deliveryID AND ds.siteID=s.siteID";



$siteres = getResult($query);

if (!checkResult($siteres))
{
	die("Invalid Delivery Order");
}

$sites = '';
while ($line = mysqli_fetch_assoc($siteres))
{
	extract($line);
	$sites .= "$siteLocationName, ";
}
$sites = substr($sites, 0, strlen($sites) - 2); // strip trailing comma
logAction("Delivery Email Percentage viewed for $sites scheduled $deliveryDateFmt " );

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Delivery Email Distribution</title>
<link rel="stylesheet" TYPE="text/css" href="http://h202.customhostingtools.com/main.css" >
<SCRIPT LANGUAGE="javascript" TYPE="text/javascript" SRC='http://www.customhostingtools.com/lib/admin.js'></SCRIPT>
<script language="javascript">
<?=$js?>
</script>
<style type="text/css">
<!--
.style4 {font-size: 12px}
.style5 {color: #990000}
-->
</style>
</head>

<body>
<?
sendDeliveryEmails($id, 1);
?>
</body>
</html>
