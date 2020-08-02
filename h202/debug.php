<?
session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once '/var/www/html/CHT/h202/h202Functions.php';
include_once '/var/www/html/CHT/lib/chtFunctions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';
error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors', 'On');

$query = "SELECT siteID, count(siteID) as total_count FROM internalEmailDistSites GROUP BY siteID";	
executeQuery("CREATE TEMPORARY TABLE temp0 $query");
$query = "SELECT siteID, count(siteID) as selected_count FROM internalEmailDistSites WHERE selected=1 GROUP BY siteID";	
executeQuery("CREATE TEMPORARY TABLE temp1 $query");
$query = "SELECT siteID, count(siteID) as not_selected_count FROM internalEmailDistSites WHERE selected=0 GROUP BY siteID";	
executeQuery("CREATE TEMPORARY TABLE temp2 $query");



$query = "CREATE TEMPORARY TABLE temp5 SELECT s1.siteID, s1.total_count, s3.not_selected_count from temp0 s1, temp2 s3 where s1.siteID=s3.siteID and s1.total_count=s3.not_selected_count order by s1.siteID";
executeQuery($query);

$query = "SELECT s.siteLocationName, t.* from site s, temp5 t where s.siteID=t.siteID order by s.siteLocationName";
$res = getResult($query);
echoResults($res);

die;
?>