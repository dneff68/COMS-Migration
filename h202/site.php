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
<title>Site Location Information</title>
<link rel="stylesheet" TYPE="text/css" href="http://h202.customhostingtools.com/main.css" >
<SCRIPT LANGUAGE="javascript" TYPE="text/javascript" SRC='http://www.customhostingtools.com/lib/admin.js'></SCRIPT>

<script language="javascript">
hideAdminMenu();
</script>

</head>

<body class="spinMedTitle">
<?
$popframe = getPopupFrame();
echo $popframe;
?>
<p class="spinLargeTitle">Site Locations</p>
<hr />
<? 
$res = getResult("select siteID, siteLocationName as 'Location', address as 'Address', city as 'City', state as 'State', zip as 'Zip', contact as 'Contact', contactPhone as 'Phone', 
contactEmail as 'Email' from site");
showSiteTable($res);
?>
<p><a href='javascript:addSiteFrame(600, 500)'>Add New Site</a></p>

</body>
</html>
