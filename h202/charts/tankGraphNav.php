<?
session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once '/var/www/html/CHT/h202/h202Functions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';
//error_reporting(E_ALL);
$query = "
	select s.siteLocationName, t.tankName
			from monitor m, tank t, site s, product p
			where 
			t.monitorID=m.monitorID and 
			m.siteID = s.siteID and
	t.prodID = p.prodID ORDER BY t.tankName";

//bigecho($query);
$res = getResult($query);
//echoResults($res);
$cnt = 0;
$flag = true;
$links = '';
while ($line = $res->fetch_assoc())
{
	extract($line);
	$cnt++;
	if ($flag)
	{
		// get first part of range
		$first = substr($tankName, 0, 3);
		$flag = false;
	}
	
	if ($cnt == 15)
	{
		$cnt = 0;
		$flag = true;
		// get last part of range
		$last = substr($tankName, 0, 3);
		//$last = $siteLocationName;
		$links .= "<b>[</b><a target='mainFrame' href='javascript:parent.mainFrame.location=\"tankGraph_all.php?st=$first\"'>$first - $last</a><b>]</b>&nbsp;&nbsp;";
	}
	
}
// get last part of range
$last = substr($tankName, 0, 3);
$links .= "<b>[</b><a target='mainFrame' href='javascript:parent.mainFrame.location=\"tankGraph_all.php?st=$first\"'>$first - $last</a><b>]</b>&nbsp;&nbsp;";
logAction("Variance Report Viewed");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Variance Banner</title>
<link rel="stylesheet" type="text/css" href="http://h202.customhostingtools.com/main.css" />
<style type="text/css">
<!--
.bigTitle {
	font-size: 36px;
	background-color: #CCC;
	border-top-color: #666;
	border-right-color: #666;
	border-bottom-color: #666;
	border-left-color: #666;
}
-->
</style>
</head>

<body>
<div align="center" class="bigTitle"> Variance Report </div>
<table width = '600' class="spinNormalText"><tr><td><?= $links?></td></tr></table>
</body>
</html>
