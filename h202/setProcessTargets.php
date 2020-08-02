<?
session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once '/var/www/html/CHT/h202/h202Functions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';

function getTargetRow($timeIncrement)
{
	$startRange = $timeIncrement - 15;
	
	
	
	$row = "<tr class=\"spinTableBarOdd\">
 	  <td width=\"149\" align=\"center\" valign=\"middle\">00:$startRange - 00:$timeIncrement</td>
 	  <td width=\"128\" align=\"center\" valign=\"middle\"><input name=\"target\" type=\"text\" id=\"target\" size=\"5\" maxlength=\"5\" /></td>
 	  <td width=\"102\" align=\"center\" valign=\"middle\"><input name=\"lag\" type=\"text\" id=\"lag\" size=\"5\" maxlength=\"5\" /></td>
    </tr>\n";
	echo($row);
}

if ($REQUEST_METHOD == 'POST')
{
	
	showPostVars();
	$jsClose = 'window.close();';
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Update Process Targets</title>
<link rel="stylesheet" TYPE="text/css" href="http://h202.customhostingtools.com/main.css" >
<SCRIPT LANGUAGE="javascript" TYPE="text/javascript" SRC='http://www.customhostingtools.com/lib/admin.js'></SCRIPT>
<script language="javascript">
<?=$jsClose?>

function dupeVals()
{
}

function setTarget()
{
	document.targetForm.submit();
}

// Get the HTTP Object
function getHTTPObject()
{
	if (window.ActiveXObject) 
		return new ActiveXObject("Microsoft.XMLHTTP");
	else if (window.XMLHttpRequest) 
		return new XMLHttpRequest();
	else 
	{
		alert("Your browser does not support AJAX.");
		return null;
 	}
}

// Change the value of the outputText field
function setOutput()
{
	if(httpObject.readyState == 4)
	{
		document.getElementById('outputText').innerHTML = httpObject.responseText;
	}
}

function clearSingleTarget(t)
{
	httpObject = getHTTPObject();
	if (httpObject != null) 
	{
		httpObject.open("GET", "processTarget_ajax.php?removeItem=" + t, true);
		httpObject.send(null);
		httpObject.onreadystatechange = setOutput;
	}

}

function clearTargets()
{
	httpObject = getHTTPObject();
	if (httpObject != null) 
	{
		httpObject.open("GET", "processTarget_ajax.php?action=clear", true);
		httpObject.send(null);
		httpObject.onreadystatechange = setOutput;
	}
}

function refreshView()
{
	httpObject = getHTTPObject();
	if (httpObject != null) 
	{
		httpObject.open("GET", "processTarget_ajax.php", true);
		httpObject.send(null);
		httpObject.onreadystatechange = setOutput;
	}
}

function doWork()
{
	httpObject = getHTTPObject();
	if (httpObject != null) 
	{
		httpObject.open("GET", "processTarget_ajax.php?hr=" + document.getElementById('hr').value + "&minute=" + document.getElementById('minute').value  + "&target=" + document.getElementById('target').value + "&lag=" + document.getElementById('lag').value, true);
		httpObject.send(null);
		httpObject.onreadystatechange = setOutput;
	}
}

function updateTarget()
{
	if(httpObject.readyState == 4)
	{
		document.getElementById('target').value = httpObject.responseText;
	}
}

function readTarget()
{
	httpObject = getHTTPObject();
	if (httpObject != null) 
	{
		httpObject.open("GET", "processTarget_ajax.php?getTarget=" +  document.getElementById('hr').value + ':' + document.getElementById('minute').value, true);
		httpObject.send(null);
		httpObject.onreadystatechange = updateTarget;
	}
}

function updateLag()
{
	if(httpObject.readyState == 4)
	{
		document.getElementById('lag').value = httpObject.responseText;
	}
}

function readLag()
{
	httpObject = getHTTPObject();
	if (httpObject != null) 
	{
		httpObject.open("GET", "processTarget_ajax.php?getLag=" +  document.getElementById('hr').value + ':' + document.getElementById('minute').value, true);
		httpObject.send(null);
		httpObject.onreadystatechange = updateLag;
	}
	
}

function updateEdits()
{
	readTarget();
	setTimeout("readLag()", 1250);
}

var httpObject = null;
</script>
</head>


<body onload="refreshView()">
      
<form action="setProcessTarger.php" method="post" id="targetForm" name="targetForm">
<input type="hidden" id="tankid" name="tankid" value='<?=$tankid?>' /> 
<table width="450" border="0" align="center" cellpadding="5" cellspacing="1">
  <tr class="spinTableTitle">
    <td colspan="4">Update Process Targets For: <?=$tankName?> </td>
  </tr>
 	<tr class="spinTableBarOdd">
 	  <td width="170" align="center" valign="top">Time Range</td>
 	  <td width="60" align="center" valign="top">Target Flow</td>
 	  <td width="69" align="center" valign="top">Lag Time</td>
 	  <td width="106" align="center" valign="top">&nbsp;</td>
    </tr>    
	<?
//	for ($i=15; $i<360; $i+=15)
//	{
 //   	getTargetRow($i);
//	}
	?>
<tr class="spinTableBarOdd">
 	  <td width="170" align="left" valign="middle">Hr: 
 	    
 	    <select name="hr" id="hr" onchange="updateEdits()">
 	      <option value="00">00</option>
 	      <option value="01">01</option>
 	      <option value="02">02</option>
 	      <option value="03">03</option>
 	      <option value="04">04</option>
 	      <option value="05">05</option>
 	      <option value="06">06</option>
 	      <option value="07">07</option>
 	      <option value="08">08</option>
 	      <option value="09">09</option>
 	      <option value="10">10</option>
 	      <option value="11">11</option>
 	      <option value="12">12</option>
 	      <option value="13">13</option>
 	      <option value="14">14</option>
 	      <option value="15">15</option>
 	      <option value="16">16</option>
 	      <option value="17">17</option>
 	      <option value="18">18</option>
 	      <option value="19">19</option>
 	      <option value="20">20</option>
 	      <option value="21">21</option>
 	      <option value="22">22</option>
 	      <option value="23">23</option>
      </select>
 	    &nbsp;&nbsp;Min:
      
       	    <select name="minute" id="minute" onchange="updateEdits()">
       	      <option value="00">00</option>
       	      <option value="15">15</option>
       	      <option value="30">30</option>
       	      <option value="45">45</option>
            </select>
      </td>
 	  <td width="60" align="center" valign="middle"><input name="target" type="text" id="target" size="5" maxlength="5" onkeyup="doWork()" /></td>
 	  <td width="69" align="center" valign="middle"><input name="lag" type="text" id="lag" size="5" maxlength="5"  onkeyup="doWork()"/></td>
 	  <td width="106" align="center" valign="middle">
      <input type="button" name="addTarget" id="addTarget" value="Clear Targets" onclick="clearTargets()"/></td>
    </tr>
<tr class="spinTableBarOdd">
  <td colspan="4" align="left" valign="middle">
  <div id="outputText">-- No Targets Set --</div>
  </td>
  </tr>
</table>
</form>
<?
showArray($PROCESS_TARGET_ARRAY);
$cnt = count($PROCESS_TARGET_ARRAY);
bigecho($cnt);
?>
</body>
</html>
