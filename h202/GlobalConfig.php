<?php
include_once "../lib/chtFunctions.php";
	
$_SESSION['ROOT_URL'] = "/";
$_SESSION['SHOWFACTORIES'] = '';
$_SESSION['SHOWCARRIERS'] = '';
$_SESSION['SHOWTERMINALS'] = '';
$_SESSION['JUMP'] = '';
$_SESSION['DELIVERY_COMMITTED'] = '';
$_SESSION['USED_PO_CODES'] = '';

$_SESSION['DELIVERY_NOTES'] = '';
$_SESSION['DELIVERY_TANKS'] = array();

$_SESSION['TANK_DETAILS'] = array();			

$_SESSION['ZIPCOLLECTION'] = array();
$_SESSION['CONVERTED_QUANTITIES'] = array();


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