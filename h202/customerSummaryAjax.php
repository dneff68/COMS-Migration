<?
session_start();
include_once 'GlobalConfig.php';
include_once 'h202Functions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';

error_log("action: $action");
if ($REQUEST_METHOD == 'POST')
{
	if (empty($customerLogin))
	{
		echo "session-timeout";
	}
}

if ($action=='checkLoginStatus')
{
	echo ($USERID);
}

elseif ($action == 'remove')
{
	executeQuery("DELETE FROM planning_items WHERE itemID = $itemID LIMIT 1");
	executeQuery("DELETE FROM planning_status WHERE itemID = $itemID LIMIT 1");
	echo 'success';
	return;
}

elseif ($action == 'getItemStatus')
{
	if ( empty( $itemID ) ) return;
	
	$statusRes = getResult("SELECT DATE_FORMAT(date, '%m/%d/%Y %I:%i %p') as statusDate, author, status FROM planning_status WHERE itemID=$itemID order by date DESC");
	if (!checkResult($statusRes)) return;
	$statOut = '';
	while ($line = mysqli_fetch_assoc($statusRes))
	{
		extract($line);
		$statOut .= "<p class=\"smallerText\">$status</p><br /><div class=\"author-date\">$author: $statusDate</div><br /><hr>";
	}
	echo ($statOut);
}

elseif ($action == 'toggleHidden')
{
	if ( empty( $itemID ) ) return;
	$query = "SELECT custView from planning_items where itemID=$itemID";
	$res = getResult($query);
	if (!checkResult($res)) return;
	$line = $res->fetch_assoc();
	extract($line);
	
	$newView = $custView == 1 ? 0 : 1;
	executeQuery("UPDATE planning_items SET custView=$newView WHERE itemID=$itemID LIMIT 1");
	echo $newView;
	return;
}

elseif ($action == 'getItem')
{
	if ( empty( $itemID ) ) return;
	
	$query = "SELECT i.catID, i.title, i.highlight, i.pctComplete, i.responsible, i.item_timing, i.impact  FROM planning_items i, planning_categories c WHERE i.catID = c.catID and i.itemID=$itemID";
	$res = getResult($query);
	if (!checkResult($res)) return;
	
	$line = $res->fetch_assoc();
	extract($line);
	echo("$title~$pctComplete~$responsible~$item_timing~$impact~$highlight");
	return;
}

// This is a test to see if I have broken the insert item capabilities. This is only a test and there is no need to be alarmed if this fails.
elseif ($action == 'editItem')
{
	if (empty($title) || empty($pctComplete) )
	{
		echo "Please provide item values";
		return;
	}
	
	if (empty($itemID))
	{
		echo "Item ID not provided";
		return;
	}
	$title = str_replace("'", "''", $title);
	$respParties = str_replace('<strong>selected:</strong><br>', '', $respParties);
	$status = htmlentities($status, ENT_QUOTES);
	$impact = htmlentities($impact, ENT_QUOTES);

	executeQuery("UPDATE planning_items SET catID=$category, title='$title', highlight='$highlight', pctComplete=$pctComplete, responsible='$respParties', item_timing='$timing', impact='$impact' WHERE itemID=$itemID LIMIT 1");
	if (!empty($status))
	{
		executeQuery("INSERT INTO planning_status (itemID, date, author, status) values
												   ($itemID, NOW(), '$USERID', '$status')");
	}
	
	echo 'success';

}

elseif ($action == 'addItem')
{
	$res = getResult("
		SELECT
		cust.siteID as siteIDs
		FROM
		customer cust, 
		customerLoginEmail c
		WHERE
		cust.customerID=c.customerID and
		c.email='$CUSTOMER_EMAIL'");
	if (checkResult($res))
	{
		$line = $res->fetch_assoc();
		extract($line);
		//echo "$title: $siteIDs";
	}
	
	if (empty($title) || empty($pctComplete) || empty($status))
	{
		echo "Please provide item values";
		return;
	}
	
	$title = str_replace("'", "''", $title);
	$respParties = str_replace('<strong>selected:</strong><br>', '', $respParties);
	$status = htmlentities($status, ENT_QUOTES);
	$impact = htmlentities($impact, ENT_QUOTES);
	$id = executeQuery("INSERT INTO planning_items (customerID, catID, custView, title, highlight, pctComplete, responsible, item_timing, impact)
											  values ('$customerID', $category, 0, '$title', '$highlight', $pctComplete, '$respParties', '$timing', '$impact')", 'INSERT');

	executeQuery("INSERT INTO planning_status (itemID, date, author, status) values
											   ($id, NOW(), '$USERID', '$status')");
	echo 'success';
}

elseif ($action=='clearAlarm')
{
	if (isset($alarmID))
	{
		$query = "SELECT flowLineID, description, date FROM flowAlarm WHERE alarmID=$alarmID";
		$res = getResult($query);
		if (checkResult($res))
		{
			$line = $res->fetch_assoc();
			extract($line);
			executeQuery("UPDATE flowAlarm SET cleared=1 WHERE alarmID=$alarmID LIMIT 1");
			echo "-- Cleared --";
			logAction("Alarm Cleared: $flowLineID - $date - $description");
		}
		else
		{
			//echo($query);
		}
	}
}
?>