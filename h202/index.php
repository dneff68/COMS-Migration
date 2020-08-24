<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once '../lib/chtFunctions.php';
include_once '../lib/db_mysql.php';
include_once 'GlobalConfig.php';
include_once 'h202Functions.php';

$search = 0;
$customerView = 0; 
$delivery = 0;
$status = 'all';
if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
	extract($_POST);
}
else
{
	if(isset($_GET['status']))
	{
    	$_SESSION['STATUS_FILTER'] = $_GET['status'];
    	$status = $_SESSION['STATUS_FILTER'];
    	//die($_SESSION['STATUS_FILTER']);
	}
}

//if ($_POST['debug'] = 1) showArray($_SESSION);

//if (david()) generateAllStats();
$_SESSION['TOTAL_DB_TIME'] 	= 0.0;
if ($david_debug)
{
	$current_timestamp = microtime(true);
	$_SESSION['time_start'] 	= microtime();
	$_SESSION['debugTime'] 		= date('s');
	$_SESSION['last_stamp'] 	= $_SESSION['time_start'];
	$dbhitcount	= 0;
	$_SESSION['queryArray'] 	= array();
	timestamp('MAIN', true);
}

if (is_null($_SESSION['logout'])) $_SESSION['logout'] = 'no'; 
//if (typeof($_SESSION['logout']) == '')
if ($_SESSION['logout'] == 'yes')
{
	error_log('SESSION WAS LOGGED OUT');
	$_SESSION['USERID'] = '';
	$_SESSION['USERTYPE'] = '';
}


if ($_SESSION["USERID"] == '' || $_SESSION["USERTYPE"] == '')
{
	error_log("NO USERID -- Returning to login.php");
	include 'login.php';
}
else
{
	$customerView = 0;
	if (david())
	{
		error_reporting(E_PARSE | E_ERROR); 
		ini_set("display_errors", 1); 				
	}
	
	if (strpos($_SESSION["USERID"], '@') > 0)
	{
		// customer is logged in
		$query = "SELECT
					cust.siteID as custSiteID
					FROM
					customer cust, 
					customerLoginEmail c
					WHERE
					cust.customerID=c.customerID and
					c.email='" . $_SESSION["USERID"] . "' and cust.siteID IS NOT NULL LIMIT 1";
		$custres = getResult($query);
//		bigEcho(gettype($custres));
		if (checkResult($custres))
		{
			session_register('CUSTOMER_EMAIL');
			$CUSTOMER_EMAIL = $customerEmail;
			$customerView = 1;
		}
	}
	
	if (strpos($_SESSION['CUSTOMER_EMAIL'], '@') > 0)
	{
		// USP is clicking into the customer summary area
		$_SESSION['CUSTOMER_EMAIL'] = $customerEmail;
		$customerView = 1;
	}
	
	if ($search==1)
	{
		include "search.php";
	}
	elseif ($customerView === 1)
	{
		include "customerSummary.php";
	}
	elseif ($delivery==1)
	{

		include "deliverySummary.php";
	}
	else
	{
		include 'tanks.php';
	}
}

if ($david_debug)
{
	echo "<table><tr><td bgcolor='#FF9933'><font color='#000000'>";
	$s = session_id();
	echo "-- Neff DEVELOPMENT --<br>";
	bigecho("session id: $s");
	$_SESSION['time_end'] = microtime(true);
	$time_used = microtime_used($_SESSION['time_start'], $_SESSION['time_end']);
	bigEcho("Time to execute: $time_used");
	echo "<br>Total hits to the database: $dbhitcount (" . $_SESSION['TOTAL_DB_TIME'] . " seconds)<br>";
	echo "</font></td></tr></table>";
	echo "<table><tr><td bgcolor='#FF9933'><font color='#000000'>";
	echo "<hr>";
	//showSessionVars();
	echo "<hr>";
	$queries = "";
	foreach ($_SESSION['queryArray'] as $query)
	{
		echo "<p>$query</p><hr>";
	}
	echo "</font></td></tr></table>";
}
?>

