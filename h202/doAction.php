<?
session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once '/var/www/html/CHT/h202/h202Functions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';

if ($actionID == 'setViewProcess')
{
	if ( empty($monitorID) || empty($ischecked) )
		return '';
		
	$checked = $ischecked == 'true' ? 1 : 0;
	$query = "UPDATE monitor SET hideProcessLink=$checked WHERE monitorID='$monitorID' LIMIT 1";
	executeQuery($query);
	return '';
}

?>