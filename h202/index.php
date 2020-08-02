<?
session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once '/var/www/html/CHT/h202/h202Functions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';



//if (david()) generateAllStats();
if ($david_debug)
{
	$time_start = getmicrotime();

	$debugTime = date('s');
	session_register('time_start');
	session_register('last_stamp');
	$last_stamp = $time_start;
	session_register("TOTAL_DB_TIME");
	$TOTAL_DB_TIME = 0.0;
	session_register("debugTime");
	session_register("dbhitcount");
	$dbhitcount = 0;
	session_register("queryArray");
	$queryArray = array();
	timestamp('MAIN', true);
}

if ($logout == 'yes')
{
	$USERID = '';
	$USERTYPE = '';
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

