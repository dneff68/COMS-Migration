<?
session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once '/var/www/html/CHT/h202/h202Functions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';

if (!empty($id))
{
	$query = "SELECT tankName, notes FROM tank WHERE tankID='$id'";
	$res = getResult($query);
	$line = mysql_fetch_assoc($res);
	extract($line);
	$notes = empty($TANK_NOTES[$id]) ? $notes : $TANK_NOTES[$id];
}
elseif (!empty($delID))
{
	$query = "SELECT notes FROM delivery WHERE deliveryID=$delID";
	$res = getResult($query);
	$line = mysql_fetch_assoc($res);
	extract($line);
	$notes = empty($DELIVERY_NOTES) ? $notes : $DELIVERY_NOTES;
}
elseif (!empty($tankDelivID))
{
	$query = "SELECT tankName, deliveryNote as notes, deliveryNoteDate, deliveryNoteAuthor FROM tank WHERE tankID='$tankDelivID'";
	$res = getResult($query);
	$line = mysql_fetch_assoc($res);
	extract($line);
}
else
{
	$notes = $DELIVERY_NOTES;
}

if ($REQUEST_METHOD == 'POST')
{
//showArray($_POST);
//ddie('pose');
	if (!empty($monitorID))
	{
		if (empty($TANK_NOTES))
		{
			session_register('TANK_NOTES');
			$TANK_NOTES = array();	
		}
		
		$TANK_NOTES[$monitorID] = $noteText;
		$jsClose = "window.opener.document.deliveryForm.submit();\nwindow.close();\n";
	}
	elseif (!empty($delID))
	{
		$noteText = fixSingleQuotes($noteText);
		$noteText = fixString($noteText);
		executeQuery("UPDATE delivery SET noteDate=NOW(), noteAuthor='$USERID', notes='$noteText' WHERE deliveryID=$delID LIMIT 1");
		$deliverySites = getDeliverySites($delID);
		$deliveryDate =  getDeliveryInfo($delID);
		logAction("Delivery notes updated for $deliverySites dated $deliveryDate" );
		$DELIVERY_NOTES = $noteText;
		$jsClose = "window.close();\n";
	}
	elseif (!empty($tankDelivID))
	{
		$noteText = fixSingleQuotes($noteText);
		$noteText = fixString($noteText);
//			$noteText = fixString($noteText);
//			$noteText = htmlentities($noteText, ENT_QUOTES);			
		executeQuery("UPDATE tank SET deliveryNoteDate=NOW(), deliveryNoteAuthor='$USERID', deliveryNote='$noteText' WHERE monitorID='$tankDelivID' LIMIT 1");
		$tankName = getTankName($tankDelivID);
		logAction("Delivery Tank notes updated for $tankName" );
		$noteText = fixString($noteText);
		$noteText = str_replace("''", '&#039;', $noteText);

		$noteText = date('Y-m-d') . ": $USERID<br>$noteText";

		$jsClose = "
			window.opener.document.getElementById('tdelNote_$tankDelivID').innerHTML = '$noteText';\n
			\nwindow.close();\n
		";
	}
	else
	{
		$DELIVERY_NOTES = $noteText; // must be a new delivery 
		$jsClose = "window.close();\n";
	}
	
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Delivery Notes</title>
<link rel="stylesheet" TYPE="text/css" href="http://h202.customhostingtools.com/main.css" >
<SCRIPT LANGUAGE="javascript" TYPE="text/javascript" SRC='http://www.customhostingtools.com/lib/admin.js'></SCRIPT>
<script language="javascript">
<?=$jsClose?>
</script>

</head>
<body>
<form name="notesForm" id="notesForm" method="post" action="deliveryNote.php">
<input type="hidden" id="monitorID" name="monitorID" value="<?=$id?>" />
<input type="hidden" id="delID" name="delID" value="<?=$delID?>" />
<input type="hidden" id="tankDelivID" name="tankDelivID" value="<?=$tankDelivID?>" />
<table width="300" border="0" align="center" cellpadding="5" cellspacing="1">
  <tr class="spinTableTitle">
    <td colspan="2">Delivery Notes <?= !empty($tankName) ? "For: $tankName" : '' ?> </td>
  </tr>
  <tr class="spinTableBarOdd">
    <td colspan="2"><label>
      <div align="center">
      <?
		$notes = str_replace('<br />', "\n", $notes);
	  ?>
        <textarea name="noteText" cols="45" rows="5" wrap="virtual" id="noteText"><?=$notes?></textarea>
        </div>
    </label></td>
  </tr>
  <tr valign="middle" class="spinTableBarOdd">
    <td width="132"><div align="center"><input type="button" value="Cancel" onclick='window.close()'/></div></td>
	<td width="145"><div align="center"><input name="submitButton" type="submit" id="submitButton" value="Submit" />
        </div></td>
  </tr>
</table>
</form>
</body>
</html>
