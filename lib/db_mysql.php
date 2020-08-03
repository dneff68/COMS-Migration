<?php
/*
db_mysql.php

Purpose: Wrapper functions for all interaction with the MySQL database
*/

/* -------- executeQuery() -------------

Description:
Call executeQuery to execute an SQL statement that does
not return a result set.  Pass the optional string "INSERT"
if it is an insert statement.  By doing this, the function
will return the ID of the newly inserted row.
--------------------------------------*/
function executeQuery($query, $type="")
{
	global $REQUEST_URI, $HTTP_HOST, $hostname, $dbuser, $dbpass, $database;

	// connect and execute query
	$connection = mysqli_connect($hostname, $dbuser, $dbpass) or die ("Unable to connect!");
	mysql_select_db($database, $connection) or die ("Couldn't select database");
	$result = mysql_unbuffered_query($query, $connection);
	if (!$result)
	{
		error_log("Error in query: $query\n$HTTP_HOST$REQUEST_URI", 1, "dneff@CustomHostingTools.com");
		if (david() || jim())
			die($query);
		else
			die ("We're sorry, there was problem with the last operation.  Please try again.<br>");
	}
	if ($type == "INSERT")
	{
		$ID = mysql_insert_id($connection);
		return $ID;
	}
}


/* -------- getResult() -------------

Description:
Call getResult to execute an SQL statement and return the
result set.
--------------------------------------*/
function getResult($query, $handleError=false)
{
	global $dbhitcount, $david_debug, $queryArray, $TOTAL_DB_TIME, $REQUEST_URI,$HTTP_HOST, $hostname, $dbuser, $dbpass, $database;
	$david_debug = false; //david();
	if ($david_debug === true)
	{
		$stime = getmicrotime();
	}

	// connect and execute query
	$connection = mysqli_connect("127.0.0.1", $dbuser, $dbpass) or die ("Unable to connect!");
	//mysql_select_db($database, $connection) or die ("Couldn't select database");
	$database = mysqli_select_db($connection, "h2o2");
	// $result = mysql_query($query, $connection);
	$result = $connection->query($query);
	if (!$result)
	{
		if (!$handleError)
		{
			if (david() || jim())
				die($query);
			else
				die ("We're sorry, there was problem with the last operation.  Please try again.<br>");
		}
		else
		{
			return false;
		}
	} 

	if ($david_debug === true)
	{
		$dbhitcount++;
		$etime = getmicrotime();
		$ttime = $etime - $stime;
		$TOTAL_DB_TIME = $TOTAL_DB_TIME + $ttime;
		$query .= "<br>Query Time: $ttime"; 
		//array_push($queryArray, $query);
		if ($ttime > 0.01)
		{
			bigecho($query);
		}
	}
	return $result;
}


function checkResult($result)
{
	if ($result)
	{

		if ($result->num_rows > 0)
		{
			return true;
		}
	}
	return false;
}

?>