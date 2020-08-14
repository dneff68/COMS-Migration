<?
session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once '/var/www/html/CHT/h202/h202Functions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';

$query = "
	select s.siteLocationName, t.monitorID
			from monitor m, tank t, site s, product p
			where 
			t.monitorID=m.monitorID and 
			m.siteID = s.siteID and
	t.prodID = p.prodID ORDER BY s.siteLocationName";

$res = getResult($query);



?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Site Listing</title>
<link rel="stylesheet" type="text/css" href="http://h202.customhostingtools.com/main.css" />
<script language="JavaScript" type="text/javascript" src='http://www.customhostingtools.com/lib/admin.js'></script>
</head>

<body class="spinTableBarOdd">
<?

while ($line=$res->fetch_assoc())
{
	extract($line);
	echo("<a href='tankGraph_all2.php?monitorID=$monitorID' target='mainFrame'>$siteLocationName</a><br>");
}
?>

</body>
</html>