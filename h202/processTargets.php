<?
session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once '/var/www/html/CHT/h202/h202Functions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';

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
		httpObject.open("GET", "processTarget_ajax.php?hr=" + document.getElementById('hr').value + "&target=" + document.getElementById('target').value, true);
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
		httpObject.open("GET", "processTarget_ajax.php?getTarget=" +  document.getElementById('hr').value, true);
		httpObject.send(null);
		httpObject.onreadystatechange = updateTarget;
	}
}

function updateEdits()
{
	readTarget();
	document.getElementById('target').focus();
}

var httpObject = null;
</script>
</head>


<body onload="refreshView()">
      
<form action="processTarger.php" method="post" id="targetForm" name="targetForm">
<input type="hidden" id="tankid" name="tankid" value='<?=$tankid?>' /> 
<table width="450" border="0" align="center" cellpadding="5" cellspacing="1">
  <tr class="spinTableTitle">
    <td height="44" colspan="3">Update Process Targets For: 001234<?=$tankName?> </td>
  </tr>
 	<tr class="spinTableBarOdd">
 	  <td width="139" align="center" valign="top">Starting Hour</td>
 	  <td width="153" align="center" valign="top">Target Flow</td>
 	  <td width="124" align="center" valign="top">&nbsp;</td>
    </tr>    

<tr class="spinTableBarOdd">
 	  <td width="139" align="center" valign="middle">Hr: 
 	    
 	    <select name="hr" id="hr" onchange="updateEdits()">
 	      <option value="00:00">00:00</option>
 	      <option value="01:00">01:00</option>
 	      <option value="02:00">02:00</option>
 	      <option value="03:00">03:00</option>
 	      <option value="04:00">04:00</option>
 	      <option value="05:00">05:00</option>
 	      <option value="06:00">06:00</option>
 	      <option value="07:00">07:00</option>
 	      <option value="08:00">08:00</option>
 	      <option value="09:00">09:00</option>
 	      <option value="10:00">10:00</option>
 	      <option value="11:00">11:00</option>
 	      <option value="12:00">12:00</option>
 	      <option value="13:00">13:00</option>
 	      <option value="14:00">14:00</option>
 	      <option value="15:00">15:00</option>
 	      <option value="16:00">16:00</option>
 	      <option value="17:00">17:00</option>
 	      <option value="18:00">18:00</option>
 	      <option value="19:00">19:00</option>
 	      <option value="20:00">20:00</option>
 	      <option value="21:00">21:00</option>
 	      <option value="22:00">22:00</option>
 	      <option value="23:00">23:00</option>
      </select></td>
 	  <td width="153" align="center" valign="middle"><input name="target" type="text" id="target" size="5" maxlength="5" onkeyup="doWork()" /></td>
 	  <td width="124" align="center" valign="middle">
      <input type="button" name="addTarget" id="addTarget" value="Clear Targets" onclick="clearTargets()"/></td>
    </tr>
<tr class="spinTableBarOdd">
  <td colspan="3" align="left" valign="middle">
  <div id="outputText">-- No Targets Set --</div>
  </td>
  </tr>
</table>
</form>
<?
if (!empty($PROCESS_TARGET_ARRAY))
{
	showArray($PROCESS_TARGET_ARRAY);
	$cnt = count($PROCESS_TARGET_ARRAY);
	bigecho($cnt);
}
?>
</body>
</html>
