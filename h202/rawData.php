<?
	error_reporting(E_ALL ^ E_NOTICE);
	ini_set('display_errors', 'On');


include_once "chtFunctions.php";
include_once "db_mysql.php";
include_once "GlobalConfig.php";

$query = "select monitorID as 'Monitor ID', date as 'Date', processDate as 'Process Date', value as 'Value' from data where date > DATE_ADD(NOW(), INTERVAL -11 DAY) order by date desc";
$res = getResult($query);
if (checkResult($res))
{
	echoResults($res, "Readings for the past 11 days");
}
else
{
	echo("<h3>no data in table</h3>");
}



?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Readings</title>
</head>

<body>
</body>
</html>
