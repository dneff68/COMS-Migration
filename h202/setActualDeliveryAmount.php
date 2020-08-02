<?
session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once '/var/www/html/CHT/h202/h202Functions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';

if (empty($id) || empty($monitorID))
{
	$js = "window.close();";
}


if ($REQUEST_METHOD == 'POST')
{
	$js = '';
	$res = getResult("SELECT quantity from deliveryTanks WHERE deliveryID=$id AND monitorID='$monitorID' LIMIT 1");
	if (checkResult($res))
	{
		$line = mysql_fetch_assoc($res);	
		$divID = "del_$id-$monitorID";
		$js = "obj = callWin.document.getElementById('$divID');\n";
		$quantityOut = "Qty: <strong>$quanty gal</strong>&nbsp;&nbsp; - &nbsp;&nbsp;<a href=\"javascript:surfDialog(\'/setActualDeliveryAmount.php?id=$id&monitorID=$monitorID\',460,200,window,false)\">set actual delivered</a></strong>";
		$js .= "obj.innerHTML = '$quantityOut';";
	}
	//$js .= "window.close();";
}

$tankName = getTankName($monitorID);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Actual Delivery Amount</title>
<link rel="stylesheet" TYPE="text/css" href="http://h202.customhostingtools.com/main.css" >
<SCRIPT LANGUAGE="javascript" TYPE="text/javascript" SRC='http://www.customhostingtools.com/lib/admin.js'></SCRIPT>
<script language="javascript">
<?=$js?>
</script>
</head>
<body>
<center>
<p class="spinLargeTitle">Set Actual Delivered Amount for 
<?=$tankName?></p>
<p class="spinNormalText">Date: <?= getDeliveryInfo($id) ?></p><br />
<?=$err?>
</center>
<form action="setActualDeliveryAmount.php" method="post" name="actualDeliveryForm" >
  <input type="hidden" name="actionVal" id="actionVal" value=callWin />
  <table width="404" border="1" align="center" cellpadding="5" cellspacing="0">
    <tr class="spinTableBarEven">
      <td width="155" valign="middle"><div align="right"><strong>Amount Delivered</strong></div></td>
      <td width="223" nowrap="nowrap">
      <input type="hidden" value="<?=$id?>" name="id"  />
      <input type="hidden" value="<?=$monitorID?>" name='monitorID'  />
      <input name="txtNewValue" type="text" id="txtNewValue" size="5" maxlength="6"  onkeypress="return numbersonly(this, event)" /></td>
    </tr>
    <tr class="spinTableBarOdd">
      <td colspan="2"><table width="100%">
          <tr>
            <td width="100%" colspan="2"><div align="center">
                <input type="button" name="Submit" value="Submit" onclick="document.actualDeliveryForm.submit()" />
                &nbsp;
                <input type="button" name="cancel" value="Cancel" onclick="window.close()" />
              </div></td>
            
          </tr>
        </table></td>
    </tr>
  </table>
</form>
<p>&nbsp;</p>
</body>
</html>
