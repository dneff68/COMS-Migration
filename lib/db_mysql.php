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
    global $REQUEST_URI, $HTTP_HOST, $hostname, $dbuser, $dbpass;
    $result = false;
    $database = $_SESSION['DATABASE'];
    // connect and execute query
    if (isRemote())
    {

        $connection = new mysqli($hostname, $dbuser, $dbpass, $database);
        if ($connection->connect_errno) {
            bigEcho("Connect failed: %s\n", $connection->connect_error);
            exit();
        }
    }
    else
    {
        $connection = mysqli_connect("127.0.0.1", $dbuser, $dbpass) or die ("Unable to connect!");
    }
    $connection->query($query);

    if ($type == "CREATE")
    {
        return 1;
    }
    if ($type == 'UPDATE' || $type == 'DELETE')
    {
        return 1;
    }
    if ($type == "INSERT")
    {
        $ID = $connection->insert_id;
        return $ID;
    }

    if (!$result) // most likely a select statement
    {
//        bigEcho($query);
//        bigEcho($result->error);
//        die;

        if (david())
        {
            bigEcho("Error in query: $query\n$HTTP_HOST$REQUEST_URI", 1, "dneff@CustomHostingTools.com");
            die($query);
        }
        else
            die ("We're sorry, there was problem with the last operation.  Please try again.<br>");
    }
    return 1;
}


/* -------- getResult() -------------

Description:
Call getResult to execute an SQL statement and return the
result set.
--------------------------------------*/
function getResult($query, $handleError=false)
{
	global $dbhitcount, $david_debug, $queryArray, $REQUEST_URI, $HTTP_HOST, $hostname, $dbuser, $dbpass, $database;
	$david_debug = false; //david();
    $result = false;


	//die("$REQUEST_URI, $HTTP_HOST, $hostname, $dbuser, $dbpass, $database;");

	if ($david_debug === true)
	{
		$stime = microtime(true);
	}

	// connect and execute query
	if (isRemote())
	{
		$connection = mysqli_connect($hostname, $dbuser, $dbpass) or die ("Unable to connect!");
	}
	else
	{
		$connection = mysqli_connect("127.0.0.1", $dbuser, $dbpass) or die ("Unable to connect!");
	}

	$database = mysqli_select_db($connection, $_SESSION['DATABASE']);
	//$result = mysqli_query($connection, $query);

    $result = mysqli_query($connection, $query) or trigger_error("Query Failed! SQL: $query - Error: ".mysqli_error($connection), E_USER_ERROR);

//bigEcho("result: " . $result);

	if (!$result)
	{
		if (!$handleError)
		{
			if (david() || jim())
				die("Error in Query: " . $query);
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
		$etime = microtime(true);
		$ttime = microtime_used($stime, $etime);
		//$ttime = $etime - $stime;
		$_SESSION['TOTAL_DB_TIME'] = $_SESSION['TOTAL_DB_TIME'] + $ttime;
		$query .= "<br>Query Time: $ttime"; 
		//array_push($queryArray, $query);
		if ($ttime > 0.01)
		{
		//	bigecho($query);
		}
	}
	return $result;
}


function checkResult($result)
{
//	var_dump(get_object_vars($result));
	if ($result)
	{
		if ($result->num_rows > 0)
		{
			return true;
		}
	}
	return false;
}

function executeAndSelect($query1, $query2)
{
    global $REQUEST_URI, $HTTP_HOST, $hostname, $dbuser, $dbpass, $database;
    $result = false;

    // connect and execute query
    if (isRemote())
    {
        $connection = new mysqli($hostname, $dbuser, $dbpass, "h202");
        if ($connection->connect_errno) {
            bigEcho("Connect failed: %s\n", $connection->connect_error);
            exit();
        }
    }
    else
    {
        $connection = mysqli_connect("127.0.0.1", $dbuser, $dbpass) or die ("Unable to connect!");
    }
    $database = mysqli_select_db($connection, $_SESSION['DATABASE']);
    $connection->query($query1);
    $result = mysqli_query($connection, $query2) or trigger_error("Query Failed! SQL: $query2 - Error: ".mysqli_error($connection), E_USER_ERROR);
    return $result;

}

?>