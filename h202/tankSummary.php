<?
session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once '/var/www/html/CHT/h202/h202Functions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Summary</title>
<link rel="stylesheet" TYPE="text/css" href="http://h202.customhostingtools.com/main.css" >
<SCRIPT LANGUAGE="javascript" TYPE="text/javascript" SRC='http://www.customhostingtools.com/lib/admin.js'></SCRIPT>
<script language="JavaScript" src="datetimepicker.js"></script>
<script src="http://www.customhostingtools.com/Scripts/AC_RunActiveContent.js" type="text/javascript"></script>
</head>

<body>

<?
$daysSinceLastReading = getDaysSinceLastReading($monitorID);
executeQuery("CREATE TEMPORARY TABLE tmpSummary 
		SELECT DISTINCT readingDate, 
		latestDose, 
		avgDose, 
		exceedcap, 
		nodose, 
		normal, 
		unass, 
		unmon, 
		noreading, 
		high, 
		low
	 from tankStats where monitorID='$monitorID' and readingDate >= '$startDate' and  readingDate <= DATE_ADD('$startDate', INTERVAL $DAYS_PLOTTED DAY)  ORDER BY readingDate desc LIMIT $DAYS_PLOTTED");

$query = "SELECT 
 					count(readingDate) as readingCount,
				 	sum(nodose) as 'ndCount',
					sum(noreading) as 'nrCount',
					sum(high) as 'highCount',
					sum(low) as 'lowCount',
					sum(latestDose) as 'productConsumed'
		from tmpSummary";


$res = getResult($query);

//bigEcho("Days Plotted: $DAYS_PLOTTED");
//bigEcho($query);
//echoResults($res);

if (checkResult($res))
{
	$line = $res->fetch_assoc();
	extract($line);
}


?>

<table width="598" border="0" cellspacing="0" cellpadding="6">
  <tr>
    <td width="302" nowrap="nowrap" class="spinSmallTitle">Total No Reading Days</td>
    <td width="272"><?= $DAYS_PLOTTED - $readingCount?></td>
  </tr>
  <tr>
    <td nowrap="nowrap" class="spinSmallTitle">Low Dose Total</td>
    <td><?=$lowCount?></td>
  </tr>
  <tr>
    <td nowrap="nowrap" class="spinSmallTitle">High Dose Total</td>
    <td><?=$highCount?></td>
  </tr>
  <tr>
    <td nowrap="nowrap" class="spinSmallTitle">Total Amount of Product Consumed</td>
    <td><?=$productConsumed?></td>
  </tr>
</table>
</body>
</html>