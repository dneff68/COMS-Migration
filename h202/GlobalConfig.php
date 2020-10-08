<?php
// if (isset($_SESSION['STATUS_FILTER']))
// 	echo("<h4>1.5: STATUS_FILTER inside GlobalConfig: STATUS FILTER IS NOT SET</h4>");
// else
// 	echo("<h4>1. STATUS_FILTER inside GlobalConfig: " . $_SESSION['STATUS_FILTER'] . "</h4>");
$pwd = getcwd();
if (strpos($pwd, 'charts') !== false)
	include_once "../../lib/chtFunctions.php";
else
	include_once "../lib/chtFunctions.php";


$_SESSION['ROOT_URL'] 			= "";
$_SESSION['LIB_URL']			= '../lib';
$_SESSION['CUSTOMER_EMAIL'] 	= '';
$_SESSION['JUMP'] = '';
$_SESSION['DELIVERY_COMMITTED'] = '';
$_SESSION['USED_PO_CODES'] = '';
$_SESSION['ZIPCOLLECTION'] = array();
$_SESSION['CONVERTED_QUANTITIES'] = array();
//$_SESSION['sendInvoices'] == 'yes';
//$_SESSION['ROOT_URL'] = "http://h202.customhostingtools.com/";

ini_set('memory_limit', '1G');
if (isRemote())
{
	$david_debug = false;
	$hostname= 'localhost'; //'127.0.0.1'; // localhost
	$dbuser = 'DevUser';
	$dbpass = 'QsTTeVfn';
	$_SESSION['DATABASE'] = 'h202';
    $_SESSION['LOCAL_DEVELOPMENT'] 	= 'no';
    $_SESSION['SYSTEM_LIB_PATH']	= '/var/www/html/COMS-Migration/lib/';
}
else
{
	$david_debug = true;
	$hostname="127.0.0.1";
	$dbuser = 'root';
	$dbpass = 'smap0tCfl';
	$_SESSION['DATABASE'] = 'h2o2';
    $_SESSION['SYSTEM_LIB_PATH']	= '/Library/WebServer/Documents/lib/';
    $_SESSION['LOCAL_DEVELOPMENT'] 	= 'yes';
}

if (david())
{
	// $database = 'h202_back';
	error_reporting(E_ALL ^ E_NOTICE);
	ini_set('display_errors', 'On');
}
?>