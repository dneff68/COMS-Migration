<?php
// if (isset($_SESSION['STATUS_FILTER']))
// 	echo("<h4>1.5: STATUS_FILTER inside GlobalConfig: STATUS FILTER IS NOT SET</h4>");
// else
// 	echo("<h4>1. STATUS_FILTER inside GlobalConfig: " . $_SESSION['STATUS_FILTER'] . "</h4>");
if (strpos($pwd, 'charts') !== false)
	include_once "../../lib/chtFunctions.php";
else
	include_once "../lib/chtFunctions.php";


$_SESSION['LOCAL_DEVELOPMENT'] 	= 'yes';
$_SESSION['ROOT_URL'] 			= "";  			
//$_SESSION['SYSTEM_ROOT_PATH']	= '/Library/WebServer/Documents/COMS-Migration/h202/';

$_SESSION['LIB_URL']			= '../lib';
$_SESSION['SYSTEM_LIB_PATH']	= '/Library/WebServer/Documents/lib/';

$_SESSION['CUSTOMER_EMAIL'] 	= '';
$_SESSION['JUMP'] = '';
$_SESSION['DELIVERY_COMMITTED'] = '';
$_SESSION['USED_PO_CODES'] = '';
$_SESSION['ZIPCOLLECTION'] = array();
$_SESSION['CONVERTED_QUANTITIES'] = array();
//include_once $_SESSION['SYSTEM_LIB_PATH'] . "chtFunctions.php";


//$_SESSION['sendInvoices'] == 'yes';


//$_SESSION['ROOT_URL'] = "http://h202.customhostingtools.com/";

if (david())
{

	$david_debug = true;
	$hostname="127.0.0.1";
	$dbuser = 'root';
	$dbpass = 'smap0tCfl';
	$database = 'h202';
}
else
{
	$david_debug = false;
	$hostname='localhost';
	$dbuser = 'phpuser';
	$dbpass = 'fog9stOol';
	$database = 'h202';
}

if (david())
{
	// $database = 'h202_back';
	error_reporting(E_ALL ^ E_NOTICE);
	ini_set('display_errors', 'On');
}
?>