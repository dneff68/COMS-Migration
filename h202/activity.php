<?php
session_start();

include_once 'GlobalConfig.php';
include_once 'h202Functions.php';
include_once '../lib/chtFunctions.php';
include_once '../lib/db_mysql.php';
include_once 'h202Functions.php';
extract($_GET);
?>

<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
<html xmlns='http://www.w3.org/1999/xhtml'>
<head>
<meta http-equiv='Content-Type' content='text/html; charset=iso-8859-1' />
<title>COMS Activity Details Details</title>
	<?php if (!david()): ?> 
		<link rel='stylesheet' TYPE='text/css' href='http://h202.customhostingtools.com/main.css' >
		<SCRIPT LANGUAGE='javascript' TYPE='text/javascript' SRC='http://www.customhostingtools.com/lib/admin.js'>	
		</SCRIPT>
	<?php else: ?>
		<link rel='stylesheet' TYPE='text/css' href='/h202/main.css' >
		<SCRIPT LANGUAGE='javascript' TYPE='text/javascript' SRC='/h202/lib/admin.js'>	
		</SCRIPT>
	<?php endif ?>
</head>
<body>
<?php
if ($showHidden == 1)
{
	$where = " or hidden=1";
}
		
if (!empty($filt))
{
	$where .= " and message like '%$filt%'";
}
// test
$query = "SELECT  userID, date, message FROM activityLog where hidden=0 $where ORDER BY date desc LIMIT 100";
$res = getResult($query);
//die($query);
if (checkResult($res))
{
	echoResults($res);
	
}
?>
</body>
</html>
