<?
session_start();
include_once 'GlobalConfig.php';
include_once 'h202Functions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';

if (empty($_SESSION['USERID']) || empty($_SESSION['USERTYPE']))
{
	$js = "alert('Your session has timed out.  Please reload the page and sign in.');\n";
}

if (!empty($id))
{
	$res = getResult("select deliveryID, deliveryDate from delivery where deliveryID=$id");
	if (checkResult($res))
	{
		$line = mysql_fetch_assoc($res);
		$deliveryDate = $line['deliveryDate'];
		executeQuery("delete from delivery where deliveryID=$id LIMIT 1");
		updateDeliveryTankStats($id, $deliveryDate); // this MUST come after the delete from delivery query
		executeQuery("delete from deliverySite where deliveryID=$id");
		executeQuery("delete from deliveryTanks where deliveryID=$id");
		executeQuery("delete from deliveryEmailLog where deliveryID=$id");
		$js .= "parent.location='/index.php';\n";

	}	
	$DELIVERY_COMMITTED = '';
	$DELIVERY_NOTES = '';
	$TANK_NOTES = false;
	$DELIVERY_TANKS = false;
	$TANK_DETAILS = false;
	$DELIVERY_DATA = false;
	$sentArray = false;
	array_splice($ZIPCOLLECTION,0);
	unset($ZIPCOLLECTION);
	$ZIPCOLLECTION = '';
}


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Delete Delivery</title>
<script language="javascript">
<?=$js?>
</script>
</head>

<body>
</body>
</html>
