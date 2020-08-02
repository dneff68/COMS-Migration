<?
session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once '/var/www/html/CHT/h202/h202Functions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';

$tankID = !empty($tankID) ? $tankID : $id;

$query = "SELECT tankName FROM tank WHERE tankID='$tankID'";
$res = getResult($query);
$line = mysql_fetch_assoc($res);
extract($line);

if ($REQUEST_METHOD == 'POST')
{
	if ($noteAction == 'delete')
	{
		if (!empty($noteKey))
		{
			executeQuery("DELETE FROM tankNotes WHERE noteKey=$noteKey LIMIT 1");
			logAction("Note deleted for tank $tankName" );
			header("location: tankNotes.php?id=$tankID");
			exit;
		}
	}
	elseif ($noteAction == 'static_note')
	{
		$noteText = fixString($noteText);
		$noteText = htmlentities($noteText, ENT_QUOTES);
		executeQuery("UPDATE tank SET notesStatic='$noteText' WHERE tankID='$tankID' LIMIT 1");	
		logAction("Static Note updated for tank $tankName" );
		$jsClose = "window.opener.location.reload();\nwindow.close();\n";
	}
	elseif (!empty($tankID) && !empty($noteText))
	{
		$noteText = fixString($noteText);
		$noteText = htmlentities($noteText, ENT_QUOTES);
		executeQuery("INSERT into tankNotes (date, tankID, note, user) VALUES (NOW(), '$tankID', '$noteText', '$USERID')", 'INSERT');	
		logAction("Note added for tank $tankName" );
		$jsClose = "window.opener.location.reload();\nwindow.close();\n";
	}
}

if ($noteAction == 'static_note')
{
	$res = getResult("select notesStatic as notes from tank where monitorID='$tankID'");
	if (checkResult($res))
	{
		$line = mysql_fetch_assoc($res);
		extract($line);
	}
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Tank Notes</title>
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
<form name="notesForm" id="notesForm" method="post" action="tankNotes.php">
<input type='hidden' id='noteAction' name='noteAction', value='<?=$noteAction?>' />
<input type='hidden' id='noteKey' name='noteKey', value='' />
<input type="hidden" id="tankID" name="tankID" value="<?=$id?>" />
<table width="550" border="0" align="center" cellpadding="5" cellspacing="1">
  <tr class="spinTableTitle">
    <td colspan="2">Tank Notes For: <?=$tankName?> </td>
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
	if ($noteAction != 'static_note') 
	{
		$query = "SELECT DATE_FORMAT(date, '%m/%d/%Y %I:%i %p') as notedate, note, user, noteKey FROM tankNotes WHERE tankID='$id' order by date desc";
		$res = getResult($query);
		if (checkResult($res))
		{
			while ($line = mysql_fetch_assoc($res))
			{
				extract($line);
				echo "\n<tr class='spinTableBarOdd'>
				<td width='175' valign='top'>Entered by: <span class='spinAlert'>$user</span><br>$notedate</td>
				<td width='375'>$note&nbsp;&nbsp;
				<a href=\"javascript:deleteNote($noteKey)\">delete</a></td>
			  </tr>";
			}
		}
	}
?>	
</table>
</form>
</body>
</html>
