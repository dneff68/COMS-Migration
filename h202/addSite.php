<?
session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';

if (!empty($action))
{
	$title = "Modify Site";
	$confirmationLink = "";
	$res = getResult("select siteID, siteLocationName, address, city, state, zip, contact, contactPhone, contactEmail from site where siteID=$action");
	if (checkResult($res))
	{
		$line = $res->fetch_assoc();
		extract($line);
	}
}

if ($REQUEST_METHOD == 'POST')
{
	if (!empty($action))
	{
		$txtStreetAddr = htmlentities($txtStreetAddr, ENT_QUOTES);
		$txtLocationName = htmlentities($txtLocationName, ENT_QUOTES);
		$query = "UPDATE site set siteLocationName='$txtLocationName', address='$txtStreetAddr', 
		city='$txtCity', state='$state', zip='$txtZip', contact='$txtContactName', contactPhone='$txtPhone', contactEmail='$txtEmail'
		WHERE siteID=$action LIMIT 1";
		executeQuery($query);
	}
	else
	{
		$query = "INSERT INTO site 
		(siteLocationName, address, city, state, zip, contact, contactPhone, contactEmail)
		VALUES
		('$txtLocationName', '$txtStreetAddr', '$txtCity', '$state', '$txtZip', '$txtContactName', '$txtPhone', '$txtEmail')";
		$siteID = executeQuery($query, 'INSERT');
	}
	
	$jsRedir = "parent.location='/site.php';\n\n";
}

//if ($reload == 'yes')
//{
//	$jsRedir = "parent.location.reload();";
//}
?>

<html>
<head>
<title><?=$title?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link rel="stylesheet" TYPE="text/css" href="http://h202.customhostingtools.com/main.css" >
<SCRIPT LANGUAGE="javascript" TYPE="text/javascript" SRC='http://www.customhostingtools.com/lib/admin.js'></SCRIPT>

<style type="text/css">
<!--
.style1 {color: #CCCCCC}
-->
</style>
<script language="javascript">
<?=$jsRedir?>

function hideThisFrame()
{
	oMenu = window.parent.document.getElementById('menuDiv');
	oMenu.style.visibility = 'hidden';
	obj = window.parent.document.getElementById("menuFrame");	
	obj.src = "";
	obj.width = 0;
	obj.height = 0;
}

function postNewSite()
{
	// validate fields
	error = "";
	if (document.siteForm.txtLocationName.value == "")
	{
		error += "\n--Please enter a site location name";
	}
	if (document.siteForm.txtStreetAddr.value == "")
	{
		error += "\n--Please enter a street address";
	}
	var Phone = document.siteForm.txtCity;
	if (Phone.value == "")
	{
		error += "\n--Please enter a city";
	}
	if (document.siteForm.state.value == "noneSelected")
	{
		error += "\n--Please select a state";
	}
	if (document.siteForm.txtZip.value == "")
	{
		error += "\n--Please enter a zip code";
	}
	if (document.siteForm.txtContactName.value == "")
	{
		error += "\n--Please enter a contact name";
	}
	if (document.siteForm.txtEmail.value == "")
	{
		error += "\n--Please an email address";
	}
	if (error != "")
	{
		error = "Please correct the following problems:\n\n" + error;
		alert(error);
		return;
	}
	else
	{
		document.siteForm.submit();
	}

}

</script>
</head>
<body>
<p align="center" class="spinLargeTitle"><br>
<?=$title?></p>
<form action="addSite.php" method="post" name="siteForm" class="style1" id="siteForm">
<input type="hidden" name="action" id="action" value="<?=$action?>">

<div align="center">
  <table width="500" border="1" cellpadding="5" cellspacing="1" bordercolor="#990000">
      <tr>
        <td width="144" class="spinTableTitle"><div align="right">Location Name </div></td>
        <td width="321" class="spinTableBarOdd"><label>
          <input name="txtLocationName" type="text" id="txtLocationName" size="40" maxlength="80" value="<?= $siteLocationName?>" />
        </label></td>
      </tr>
    <tr>
      <td class="spinTableTitle"><div align="right">Street Address </div></td>
        <td class="spinTableBarOdd"><input name="txtStreetAddr" type="text" id="txtStreetAddr" size="40" maxlength="80" value="<?= $address?>" /></td>
      </tr>
    <tr>
      <td class="spinTableTitle"><div align="right">City</div></td>
        <td class="spinTableBarOdd"><input name="txtCity" type="text" id="txtCity" size="40" maxlength="80" value="<?= $city?>" /></td>
      </tr>
    <tr>
      <td class="spinTableTitle"><div align="right">State</div></td>
        <td class="spinTableBarOdd"><select name="state"><? include 'stateOptions.php' ?></select></td>
      </tr>
    <tr>
      <td class="spinTableTitle"><div align="right">Zip</div></td>
        <td class="spinTableBarOdd"><input name="txtZip" type="text" id="txtZip" size="11" maxlength="10" value="<?= $zip?>" /></td>
      </tr>
    <tr>
      <td class="spinTableTitle"><div align="right">Contact Name </div></td>
        <td class="spinTableBarOdd"><input name="txtContactName" type="text" id="txtContactName" size="40" maxlength="80" value="<?= $contact?>" /></td>
      </tr>
    <tr>
      <td class="spinTableTitle"><div align="right">Phone</div></td>
        <td class="spinTableBarOdd"><input name="txtPhone" type="text" id="txtPhone" size="13" maxlength="12" value="<?= $contactPhone?>" />
          <br />
        <span class="header_3">ex: 555-555-5555</span> </td>
      </tr>
    <tr>
      <td class="spinTableTitle"><div align="right">Email</div></td>
        <td class="spinTableBarOdd"><input name="txtEmail" type="text" id="txtEmail" size="40" maxlength="80" value="<?= $contactEmail?>" /></td>
      </tr>
  </table>
</div></form>
<p align="center">&nbsp;</p>
<table width="500" border="0" align="center" cellpadding="5" cellspacing="5">
  <tr>
    <td><div align="left"><span class="spinAHoverOn"><a href='javascript:postNewSite()'><?= empty($action) ? 'Add Site' : 'Update Site'?></a></span></div></td> 
    <td><div align="right"><span class="spinAHoverOn"><a href='javascript:hideThisFrame()'>Close</a></span></div></td>
  </tr>
</table>
<p align="center" class="spinAHoverOn">&nbsp;</p>

</body>
</html>