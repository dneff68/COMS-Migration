<?
session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';

if (!empty($sid))
{
	// delte site
	executeQuery("DELETE FROM site WHERE siteID = $sid LIMIT 1");
}

?>
<html>
<head>
<title><?=$title?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link rel="stylesheet" TYPE="text/css" href="http://h202.customhostingtools.com/main.css" >
<SCRIPT LANGUAGE="javascript" TYPE="text/javascript" SRC='http://www.customhostingtools.com/lib/admin.js'></SCRIPT>

<script language="javascript">
	parent.location='/site.php';
</script>
</head>
<BODY></BODY>
</html>
