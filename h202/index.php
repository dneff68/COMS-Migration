<?php

ini_set ('display_errors', 1);  
ini_set ('display_startup_errors', 1);  
error_reporting (E_ALL); 
include_once '../lib/chtFunctions.php';
include_once '../lib/db_mysql.php';
include_once 'GlobalConfig.php';
include_once 'h202Functions.php';
//	global $USERID, $_SESSION;


//if (david()) generateAllStats();
if ($david_debug)
{
	$_SESSION['time_start'] 	= getmicrotime();
	$_SESSION['debugTime'] 		= date('s');
	$_SESSION['last_stamp'] 	= $_SESSION['time_start'];
	$_SESSION['TOTAL_DB_TIME'] 	= 0.0;
	$_SESSION['dbhitcount'] 	= 0;
	$_SESSION['queryArray'] 	= array();
	timestamp('MAIN', true);
}

if (is_null($_SESSION['logout'])) $_SESSION['logout'] = 'no'; 
//if (typeof($_SESSION['logout']) == '')
if ($_SESSION['logout'] == 'yes')
{
	$_SESSION['USERID'] = '';
	$_SESSION['USERTYPE'] = '';
}

if (empty($USERID) || empty($USERTYPE))
{
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
	
	if (strpos($USERID, '@') > 0)
	{
		// customer is logged in
		$query = "SELECT
					cust.siteID as custSiteID
					FROM
					customer cust, 
					customerLoginEmail c
					WHERE
					cust.customerID=c.customerID and
					c.email='$USERID' and cust.siteID IS NOT NULL LIMIT 1";
		$custres = getResult($query);
//		bigEcho(gettype($custres));
		if (checkResult($custres))
		{
			session_register('CUSTOMER_EMAIL');
			$CUSTOMER_EMAIL = $customerEmail;
			$customerView = 1;
		}
	}
	
	if (strpos($customerEmail, '@') > 0)
	{
		// USP is clicking into the customer summary area
		session_register('CUSTOMER_EMAIL');
		$CUSTOMER_EMAIL = $customerEmail;
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
	$time_end = getmicrotime();
	$time = $time_end - $time_start;
	$time = number_format($time, 2, '.', '');
	$TOTAL_DB_TIME = number_format($TOTAL_DB_TIME, 2, '.', '');
	echo "Total Process Time: $time seconds";
	echo "<br>Total hits to the database: $dbhitcount ($TOTAL_DB_TIME seconds)<br>";
	echo "</font></td></tr></table>";
	echo "<table><tr><td bgcolor='#FF9933'><font color='#000000'>";
	echo "<hr>";
	showSessionVars();
	echo "<hr>";
	$queries = "";
	foreach ($queryArray as $query)
	{
		echo "<p>$query</p><hr>";
	}
	echo "</font></td></tr></table>";
}
?>

