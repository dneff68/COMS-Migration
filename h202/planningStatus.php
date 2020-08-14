<?
session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once '/var/www/html/CHT/h202/h202Functions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';

$itemID = !empty($itemID) ? $itemID : $id;

$query = "SELECT title FROM planning_items WHERE itemID = $itemID";
$res = getResult($query);
if (checkResult($res))
{
	$line = $res->fetch_assoc();
	extract($line);
}

if ($REQUEST_METHOD == 'POST')
{
	if (!empty($itemID) && !empty($noteText))
	{
		$noteText = fixString($noteText);
		$noteText = htmlentities($noteText, ENT_QUOTES);
		executeQuery("INSERT into planning_status (itemID, date, author, status) VALUES ($itemID, NOW(), '$USERID', '$noteText')", 'INSERT');	
		header("location: planningStatus.php?id=$itemID");
		exit;
	}
}


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Planning Notes</title>
<link rel="stylesheet" TYPE="text/css" href="http://h202.customhostingtools.com/main.css" >
<SCRIPT LANGUAGE="javascript" TYPE="text/javascript" SRC='http://www.customhostingtools.com/lib/admin.js'></SCRIPT>
<script language="javascript">
<?=$jsClose?>

</script>

</head>
<body>
<form name="notesForm" id="notesForm" method="post" action="planningStatus.php">
<input type='hidden' id='noteAction' name='noteAction', value='<?=$noteAction?>' />
<input type="hidden" id="itemID" name="itemID" value="<?=$id?>" />
<table width="550" border="0" align="center" cellpadding="5" cellspacing="1">
  <tr class="spinTableTitle">
    <td colspan="2"><?=$title?></td>
  </tr>
  <tr class="spinTableBarOdd">
    <td colspan="2"><label>
      <div align="center">
        <textarea name="noteText" cols="65" rows="5" wrap="virtual" id="noteText"><?=$notes?></textarea>
        </div>
    </label></td>
  </tr>
  <tr valign="middle" class="spinTableBarOdd">
    <td colspan="2"><div align="center"><input type="button" value="Cancel" onclick='window.close()'/>&nbsp;
	<input name="submitButton" type="submit" id="submitButton" value="Add Status" />
      </div></td>
	</tr>
    
<?
		$query = "SELECT DATE_FORMAT(date, '%m/%d/%Y %I:%i %p') as notedate, status as note, author as user FROM planning_status WHERE itemID='$id' order by date desc";
		$res = getResult($query);
		if (checkResult($res))
		{
			while ($line = $res->fetch_assoc())
			{
				extract($line);
				echo "\n<tr class='spinTableBarOdd'>
				<td width='175' valign='top'>Entered by: <span class='spinAlert'>$user</span><br>$notedate</td>
				<td width='375'>$note</td>
			  </tr>";
			}
		}
?>

    
</table>
</form>
</body>
</html>
