<?
session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once '/var/www/html/CHT/h202/h202Functions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';

$trunkLineID = !empty($trunkLineID) ? $trunkLineID : $id;

$trunkLineName = $trunkLineID;

if ($REQUEST_METHOD == 'POST')
{
	if ($noteAction == 'delete')
	{
		if (!empty($noteKey))
		{
			executeQuery("DELETE FROM trunkLineNotes WHERE noteKey=$noteKey LIMIT 1");
			logAction("Note deleted for trunkLine $trunkLineName" );
			header("location: trunkLineNotes.php?id=$trunkLineID");
			exit;
		}
	}
	elseif (!empty($trunkLineID) && !empty($noteText))
	{
		$noteText = fixString($noteText);
		$noteText = htmlentities($noteText, ENT_QUOTES);
		executeQuery("INSERT into trunkLineNotes (date, trunkLineID, note, user) VALUES (NOW(), '$trunkLineID', '$noteText', '$USERID')", 'INSERT');	
		logAction("Note added for trunkLine $trunkLineName" );
		header("location: trunkLineNotes.php?id=$trunkLineID");
		exit;
	}
}


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>TrunkLine Notes</title>
<link rel="stylesheet" TYPE="text/css" href="http://h202.customhostingtools.com/main.css" >
<SCRIPT LANGUAGE="javascript" TYPE="text/javascript" SRC='http://www.customhostingtools.com/lib/admin.js'></SCRIPT>
<script language="javascript">
<?=$jsClose?>

function deleteNote(key)
{
	window.document.notesForm.noteKey.value = key;
	window.document.notesForm.noteAction.value = 'delete';
	window.document.notesForm.submit()
}
</script>

</head>
<body>
<form name="notesForm" id="notesForm" method="post" action="trunkLineNotes.php">
<input type='hidden' id='noteAction' name='noteAction', value='<?=$noteAction?>' />
<input type='hidden' id='noteKey' name='noteKey', value='' />
<input type="hidden" id="trunkLineID" name="trunkLineID" value="<?=$id?>" />
<table width="550" border="0" align="center" cellpadding="5" cellspacing="1">
  <tr class="spinTableTitle">
    <td colspan="2">TrunkLine Notes For: <?=$trunkLineName?> </td>
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
	<input name="submitButton" type="submit" id="submitButton" value="Add Note" />
      </div></td>
	</tr>
    
<?
		$query = "SELECT DATE_FORMAT(date, '%m/%d/%Y %I:%i %p') as notedate, note, user, noteKey FROM trunkLineNotes WHERE trunkLineID='$id' order by date desc";
		$res = getResult($query);
		if (checkResult($res))
		{
			while ($line = $res->fetch_assoc())
			{
				extract($line);
				echo "\n<tr class='spinTableBarOdd'>
				<td width='175' valign='top'>Entered by: <span class='spinAlert'>$user</span><br>$notedate</td>
				<td width='375'>$note&nbsp;&nbsp;
				<a href=\"javascript:deleteNote($noteKey)\">delete</a></td>
			  </tr>";
			}
		}
?>

    
</table>
</form>
</body>
</html>
