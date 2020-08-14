<?
session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once '/var/www/html/CHT/h202/h202Functions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';

$res = getResult("SELECT m.units, d.value as storedValue, t.diameter from monitor m, data d, tank t where d.monitorID=m.monitorID and d.date='$datetime' and m.monitorID='$monitorID' and t.monitorID='$monitorID' LIMIT 1");
if (checkResult($res))
{
	$line = $res->fetch_assoc();
	extract($line);
}


if ($REQUEST_METHOD=='POST')
{
	$tankName = getTankName($monitorID);

	if ( $actionVal == 'delete' )
	{
		
		$query = "DELETE FROM data WHERE date='$datetime' AND monitorID='$monitorID' LIMIT 1";
		executeQuery($query);
		$query = "DELETE FROM tankStats WHERE readingDate='$datetime' AND monitorID='$monitorID' LIMIT 1";
		executeQuery($query);
		//generateStats($monitorID, "'$datetime'");
		logAction("Reading dated $datetime DELETED for $tankName" );
		updateTankStats($monitorID);  // update last 11 days of statistics
		$js = "\nwindow.close();\n";
	}

/*
actionVal = delete
datetime = 2008-08-16 02:27:51
monitorID = Agrium_
*/
	if (empty($txtNewValue))
	{
		$err = "<p class='spinAlert'>Please enter a new value</p>";
	}
	else
	{
		
		if (!empty($set_units))
		{
			if ($set_units != $units)
			{
				if ($set_units == 'Inches')
				{
					$txtNewValue = inchToGal($txtNewValue, $diameter);
				}
				else // show in inches
				{
					$txtNewValue = galToInch($txtNewValue, $diameter);
				}
			}
		}
		// perform udate
		$txtNewValue2	= empty($txtNewValue2) ? '00' : trim($txtNewValue2);
		$txtNewValue 	= "$txtNewValue.$txtNewValue2";
		
		$query = "UPDATE data SET value=$txtNewValue, badReading=1 WHERE monitorID='$monitorID' and date='$datetime' LIMIT 1";
		executeQuery($query);
		logAction("Reading dated $datetime CHANGED to $txtNewValue for $tankName" );
		generateStats($monitorID, "'$datetime'");
		$js = "\nwindow.close();\n";
	}


}

if (empty($datetime))
	$js = "\nwindow.close();\n";

// override if units were toggled
if (!empty($set_units))
{
		if ($set_units == 'Gallons')
		{
			$value = inchToGal($storedValue, $diameter);
		}
		else // show in inches
		{
			$value = galToInch($storedValue, $diameter);
		}
	
	
	$units = $set_units;
}
else
{
	$value = $storedValue;
}

bigecho($units);

$insel = $units == 'Inches' ? 'checked' : '';
$galsel = empty($insel) ? 'checked' : '';

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Modify Reading</title>
<link rel="stylesheet" TYPE="text/css" href="http://h202.customhostingtools.com/main.css" >
<SCRIPT LANGUAGE="javascript" TYPE="text/javascript" SRC='http://www.customhostingtools.com/lib/admin.js'></SCRIPT>
<script language="javascript">
<?=$js?>
function deleteReading()
{
	if ( confirm('Are you sure you want to delete this reading?') )
	{
		document.readingForm.actionVal.value = 'delete';
		document.readingForm.submit();	
	}
}
</script>
</head>

<body>
<p class="spinLargeTitle">Modify Reading</p><?=$err?>
<form action="changeReading_new.php" method="post" name="readingForm" >
<input type="hidden" name="actionVal" id="actionVal" value="" />
<input type="hidden" name="set_units" id="set_units" value="<?=$set_units?>" />

<table width="404" border="1" align="center" cellpadding="5" cellspacing="0">
  <tr class="spinTableBarEven">
    <td width="155" valign="middle"><div align="right"><strong>Current Reading Value </strong></div></td>
    <td width="223"><strong><?=$value?> </strong> <?=$units?><br /><?=$datetime?></td>
  </tr>
  <tr class="spinTableBarEven">
    <td valign="middle"><div align="right"><strong>New Value </strong></div></td>
    <td nowrap="nowrap">
	  <input type="hidden" value="<?=$datetime?>" name="datetime"  />
	  <input type="hidden" value="<?=$monitorID?>" name='monitorID'  />
      <input name="txtNewValue" type="text" id="txtNewValue" size="5" maxlength="6"  onkeypress="return numbersonly(this, event, 'txtNewValue2')" />
      .
      <input name="txtNewValue2" type="text" id="txtNewValue2" size="2" maxlength="2"  onkeypress="return numbersonly(this, event)" />
      &nbsp;<?=$units?>
<!--
      <input name="rdoUnits" type="radio" value="gallons"  <?=$galsel?>/> 
      Gallons 
	  <input name="rdoUnits" type="radio" value="inches"  <?=$insel?>/> 
	  Inches
-->	  </td>
  </tr>
  <tr class="spinTableBarOdd">
    <td colspan="2">
		<table width="100%">
		  <tr>
		<td width="99%"><div align="center">
				<input type="button" name="Submit" value="Submit" onclick="document.readingForm.submit()" />
				&nbsp;<input type="button" name="cancel" value="Cancel" onclick="window.close()" />
			  </div>
		</td>
		<td width="1%" align="right"><a href="javascript:deleteReading();"><img alt="Delete this reading" src="/images/delete.gif" border="false" /></a></td>
		</tr></table>
	</td>
  </tr>
  <tr align="right"><td colspan="2" style="font-size:smaller"><a href='/changeReading_new.php?set_units=<?=$units == 'Inches' ? 'Gallons' : 'Inches' ?>&monitorID=<?=$monitorID?>&datetime=<?=$datetime?>'>Toggle to <?=$units == 'Inches' ? 'Gallons' : 'Inches' ?></a></td></tr>
</table>
</form>
<p>&nbsp;</p>
</body>
</html>
