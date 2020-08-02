<?
session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';

if (empty($USERID) || empty($USERTYPE))
{
		header("location:/");
		die;
}

if ($REQUEST_METHOD == 'POST')
{
	executeQuery("INSERT INTO supplier (supplierName, contact, email, phone) values ('$supplier', '$contact', 'email', 'phone')");
	$js = "\nwindow.close();\n";
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>New Supplier</title>
<link rel="stylesheet" TYPE="text/css" href="http://h202.customhostingtools.com/main.css" >
<SCRIPT LANGUAGE="javascript" TYPE="text/javascript" SRC='http://www.customhostingtools.com/lib/admin.js'></SCRIPT>
<script language="javascript">
<?=$js?>
</script>

</head>
<body>
<p align="center" class="spinLargeTitle style1">New Supplier </p>
<p align="center" class="spinLargeTitle style1">&nbsp;</p>
<form name="addSupplierForm" action="addSupplier.php" method="post">
<input type="hidden" name="addSupplierAction" value='' />
<table width="600" border="1" align="center" cellpadding="5" cellspacing="1" class="spinTableBarOdd">
  <tr class="spinMedTitle">
    <td colspan="2" class="spinTableTitle"><div align="center" class="spinMedTitle">Supplier Details </div></td>
  </tr>
  <tr>
    <td width="199" nowrap="nowrap" class="spinTableTitle"><div align="right">Supplier Name:</div></td>
    <td width="372" valign="middle"><input name="supplier" type="text" id="supplier" size="35" maxlength="40" /></td>
  </tr>
  <tr>
    <td nowrap="nowrap" class="spinTableTitle"><div align="right">Contact:</div></td>
    <td valign="middle"><div align="left">
      <input name="contact" type="text" id="contact" size="35" maxlength="40" />
    </div></td>
  </tr>
  <tr>
    <td nowrap="nowrap" class="spinTableTitle"><div align="right">Contact Phone: </div></td>
    <td valign="middle"><input name="phone" type="text" id="phone" size="15" maxlength="15" /></td>
  </tr>
  <tr>
    <td nowrap="nowrap" class="spinTableTitle"><div align="right">Contact Email: </div></td>
    <td valign="middle"><input name="email" type="text" id="email" size="35" maxlength="40" /></td>
  </tr>
</table>
</form>
<table width="600" border="0" align="center" cellpadding="5" cellspacing="0">
  <tr class="spinBoxedNormal">
    <td width="287"><div align="left"><a href='javascript:window.close()'>Cancel</a></div></td>
    <td width="287"><div align="right"><a href='javascript:document.addSupplierForm.submit()'>Commit</a></div></td>
  </tr>
</table>
<p align="center" class="spinLargeTitle style1">&nbsp;</p>
</body>
</html>