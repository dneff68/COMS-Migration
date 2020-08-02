<?
session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once '/var/www/html/CHT/h202/h202Functions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';

$checked = $ischecked == 'true' ? 1 : 0;
$query = "UPDATE monitor SET no_tsAlert=$checked WHERE monitorID='$monitorID' LIMIT 1";
executeQuery($query);

$res = getResult("SELECT tankName as monitorID from tank where monitorID='$monitorID' limit 1");
if (checkResult($res))
{
	$line = mysql_fetch_assoc($res);
	extract($line);
}

logAction("TS Alert Updated for $monitorID");
?>