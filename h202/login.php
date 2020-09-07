<?php
session_start();
include_once 'GlobalConfig.php';
include_once 'h202Functions.php';
include_once '../lib/db_mysql.php';
//showArray($_SERVER);
if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
  echo($_POST["userID"].", ".$_POST["password"]);
  showArray($_POST);
	if (empty($_POST["userID"]))
	{
		$_SESSION["USERID"] = "";
		$_SESSION["USERTYPE"] = "";
	}
	$res = verifyUser($_POST["userID"], $_POST["password"]);
 	if ($res !== 0)
	{
		$errorMsg = "<span class='spinAlert'>Please try again: $res</span>";
	}
	else
	{
		logAction("User Login");
		header("location:index.php");
	}
}


?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Peroxide Management</title>
<link rel="stylesheet" TYPE="text/css" href="http://h202.customhostingtools.com/main.css" >
<SCRIPT LANGUAGE="javascript" TYPE="text/javascript" SRC='http://www.customhostingtools.com/lib/admin.js'></SCRIPT>
<style type="text/css">
<!--
.style1 {font-size: xx-large}
.style4 {font-size: 12px}
-->
</style>
</head>

<body onload="document.loginForm.userID.focus()">
<? include 'banner.php'?>
<form name="loginForm" id="loginForm" action="login.php" method="post">
<table class='spinTableBarOdd' width="379" border="0" align="center" cellpadding="5" cellspacing="5">
  <tr class="spinTableTitle">
    <td colspan="2"><?= empty($errorMsg) ? "Please sign in:" : $errorMsg?>      </td>
  </tr>
  <tr>
    <td width="145" class="spinSmallTitle"><div align="right" class="style4">User</div></td>
    <td width="199"><p>
      <label>
      <input name="userID" type="text" id="userID" size="20" maxlength="50" />
      </label>
    </p>    </td>
  </tr>
  <tr>
    <td class="spinSmallTitle"><div align="right" class="style4">Password</div></td>
    <td><label>
      <input name="password" type="password" id="password" size="20" maxlength="22" />
    </label></td>
  </tr>
  <tr>
    <td><label>
      
      <div align="center"></label>
    </div></td>
    <td><input type="submit" name="Submit" value="Submit" /></td>
  </tr>
  <tr class="header_3">
    <td align="center" colspan="2"><a href="mailto:eccl411.12@gmail.com">Click Here</a> if you are having trouble signing in</td>
  </tr>
</table>
</form>
</body>
</html>
