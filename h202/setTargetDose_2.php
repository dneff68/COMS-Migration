<?
session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once '/var/www/html/CHT/h202/h202Functions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';

function getTargetDay($dayval, $serializedTargets, $defaultTarget=-1)
{
	if ($dayval == 1)
		$dow = 'Mon';
	elseif ($dayval == 2)
		$dow = 'Tue';
	elseif ($dayval == 3)
		$dow = 'Wed';
	elseif ($dayval == 4)
		$dow = 'Thurs';
	elseif ($dayval == 5)
		$dow = 'Fri';
	elseif ($dayval == 6)
		$dow = 'Sat';
	elseif ($dayval == 7)
		$dow = 'Sun';
	
	if ($defaultTarget > -1)
	{
		$dosage = (string)$defaultTarget;
	}
	else
	{
		$targets = unserialize($serializedTargets);
		$dosage = (string)$targets[$dayval];
	}
	list($dosage, $dosage2) = explode('.', $dosage);
	
	return "
	<td width='36'>$dow</td>
	  <td width='216'><input name='dosage$dayval' value='$dosage' type='text' id='dosage$dayval' size='5' maxlength='5' onkeypress='return numbersonly(this, event)'/>
	</td>\n";
	
}

if ($REQUEST_METHOD == 'POST')
{
	
	showPostVars();
	
	$targets = array();
	$targets[1] = $dosage1 ;
	$targets[2] = $dosage2 ;
	$targets[3] = $dosage3 ;
	$targets[4] = $dosage4 ;
	$targets[5] = $dosage5 ;
	$targets[6] = $dosage6 ;
	$targets[7] = $dosage7 ;
	$targets = serialize($targets);

//die($targets);
	
	
	$deviation_plus = empty($deviation_plus) ? 0 : $deviation_plus;
	$deviation_minus = empty($deviation_minus) ? 0 : $deviation_minus;
	executeQuery("UPDATE tank set targetDaily='$targets', deviation_plus=$deviation_plus, deviation_minus=$deviation_minus where tankID='$tankid' LIMIT 1");

	$query = "SELECT tankName FROM tank WHERE tankID='$tankid'";
	$res = getResult($query);
	$line = mysql_fetch_assoc($res);
	extract($line);

	logAction("Target Dose/Deviation for $tankName updated (+$deviation_plus or -$deviation_minus)" );

	// store the change to the tank.
	$historyRes = getResult("SELECT monitorID FROM tankHistory WHERE monitorID='$tankid' and date = cast(NOW() as date)");
	if (checkResult($historyRes))
	{
		executeQuery("UPDATE tankHistory SET targetDaily = '$targets' WHERE monitorID='$tankid' and date = cast(NOW() as date) LIMIT 1");	
	}
	else
	{
		executeQuery("INSERT INTO tankHistory (monitorID, date, targetDaily) VALUES ('$tankid', NOW(), '$targets')");	
	}			

	// update today's statistics
	generateStats($tankid);
	$jsClose = 'window.close();';
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Update Target Dose</title>
<link rel="stylesheet" TYPE="text/css" href="http://h202.customhostingtools.com/main.css" >
<SCRIPT LANGUAGE="javascript" TYPE="text/javascript" SRC='http://www.customhostingtools.com/lib/admin.js'></SCRIPT>
<script language="javascript">
<?=$jsClose?>

function setTarget()
{
	t1 = document.targetForm.dosage1.value;
	t2 = document.targetForm.dosage2.value;
	t3 = document.targetForm.dosage3.value;
	t4 = document.targetForm.dosage4.value;
	t5 = document.targetForm.dosage5.value;
	t6 = document.targetForm.dosage6.value;
	t7 = document.targetForm.dosage7.value;
	
	if (t1 == '' || t2 == '' || t3 == '' || t4 == '' || t5 == '' || t6 == '' || t7 == '')
	{
		alert('Please provide a target for each day');
		return;
	}
	document.targetForm.submit();
}

</script>
</head>

<?
$targetDaily = '';
$res = getResult("select tankName, targetDosage, targetDaily, deviation_minus, deviation_plus from tank where tankID = '$tankid'");
if (checkResult($res))
{
	$line = mysql_fetch_assoc($res);
	extract($line);
	$dosage = (string)$targetDosage;
	list($dosage, $dosage2) = explode('.', $dosage);
}
if (empty($targetDaily))
{
	$defaultTarget = $targetDosage;
}
else
{
	$defaultTarget = -1;
}

?>

<body>
<form action="setTargetDose_2.php" method="post" id="targetForm" name="targetForm">
<input type="hidden" id="tankid" name="tankid" value='<?=$tankid?>' /> 
<table width="427" border="0" align="center" cellpadding="5" cellspacing="1">
  <tr class="spinTableTitle">
    <td colspan="3">Update Target Dose For: <?=$tankName?> </td>
  </tr>
<!-- 	<tr class="spinTableBarOdd"><td width="141" align="right"><strong>Current Target:</strong></td>
 	<td colspan="2"><?=$targetDosage?></td>
 	</tr>
--> 	<tr class="spinTableBarOdd">
 	  <td rowspan="7" align="right" valign="top"><strong> Target:</strong></td>
  <?= getTargetDay(7, $targetDaily, $defaultTarget) ?>
    </tr>

<tr class='spinTableBarOdd'>
  <?= getTargetDay(1, $targetDaily, $defaultTarget) ?>
</tr>    
<tr class='spinTableBarOdd'>
  <?= getTargetDay(2, $targetDaily, $defaultTarget) ?>
</tr>    
<tr class='spinTableBarOdd'>
  <?= getTargetDay(3, $targetDaily, $defaultTarget) ?>
</tr>    
<tr class='spinTableBarOdd'>
  <?= getTargetDay(4, $targetDaily, $defaultTarget) ?>
</tr>    
<tr class='spinTableBarOdd'>
  <?= getTargetDay(5, $targetDaily, $defaultTarget) ?>
</tr>    
<tr class='spinTableBarOdd'>
  <?= getTargetDay(6, $targetDaily, $defaultTarget) ?>
</tr>    
 	<tr class="spinTableBarOdd">
  <td align="right"><strong>Deviation +</strong></td>
  <td colspan="2">
  	<input name="deviation_plus" value='<?=empty($deviation_plus) ? 0 : $deviation_plus?>' type="text" id="deviation_plus" size="5" maxlength="5" onkeypress="return numbersonly(this, event)"/> 
	</td>
 	</tr>

 	<tr class="spinTableBarOdd">
  <td align="right"><strong>Deviation -</strong></td>
  <td colspan="2">
  	<input name="deviation_minus" value='<?=empty($deviation_minus) ? 0 : $deviation_minus?>' type="text" id="deviation_minus" size="5" maxlength="5" onkeypress="return numbersonly(this, event)"/> 
	</td>
 	</tr>


 	<tr class="spinTableBarOdd">
 	  <td colspan="3" align="right" class="spinToolBarTitle"><div align="center">
		<input type="button" value="Cancel" onclick="window.close()" />&nbsp;&nbsp;&nbsp;
		<input type="button" value="Submit New Target" onclick="setTarget();" />
 	  </div></td>
    </tr>
</table>
</form>
</body>
</html>
