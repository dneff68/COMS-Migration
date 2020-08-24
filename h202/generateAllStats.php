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
	die("NOT LOCAL DEVELOPMENT: multiTankDetails: 13");
	include_once '/var/www/html/CHT/h202/GlobalConfig.php';
	include_once '/var/www/html/CHT/h202/h202Functions.php';
	include_once 'chtFunctions.php';
	include_once 'db_mysql.php';
}

generateAllStats();
?>