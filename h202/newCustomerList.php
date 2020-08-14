<?
session_start();
include_once 'GlobalConfig.php';
include_once 'h202Functions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';


if (david())
{
	error_reporting(E_PARSE | E_ERROR); 
	ini_set("display_errors", 1); 		
}

if (empty($customerID))
{
	$customerID='--none--'; // give a zero result count in SQL later
}

$js = '';
if (empty($USERID))
{
	$js = "alert('Your session has expired');\n window.close();\n";
}

?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>New Customer Forms</title>
<link rel="stylesheet" TYPE="text/css" href="http://h202.customhostingtools.com/main.css" >
<link rel="stylesheet" TYPE="text/css" href="planning.css" >
<link rel="stylesheet" href="/ui_theme/themes/base/jquery.ui.all.css">
<SCRIPT LANGUAGE="javascript" TYPE="text/javascript" SRC='http://www.customhostingtools.com/lib/helper.js'></SCRIPT>
<SCRIPT LANGUAGE="javascript" TYPE="text/javascript" SRC='http://www.customhostingtools.com/lib/admin.js'></SCRIPT>
<script src="http://www.customhostingtools.com/lib/jquery.js" type="text/javascript"></script>
<script src="/ui_theme/ui/jquery.ui.core.js"></script>
<script src="/ui_theme/ui/jquery.ui.widget.js"></script>
<script src="/ui_theme/ui/jquery.ui.datepicker.js"></script>
<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js"></script>


<script type="text/javascript">
<?
	echo($js);
?>

function callPrint()
{
	window.print();
}

function sendEmail(key)
{
	// ajax call to send email
	var emailVal = new String($("#txt_" + key).val());
	if (emailVal == '' || emailVal.indexOf('@') == -1 || emailVal.indexOf('.') == -1 )
	{
		alert('Please enter a valid email address');
		return;
	}
	
	$.post("newCustomerAjax.php", { "action": "sendEmail", "keyCode": key, "email": emailVal.valueOf() },
	 function(data) {
		 if (data == 'success')
		 {
	   		alert("Success: This customer form has been emailed to " + emailVal);
		 }
		 else
		 {
			 alert(data);
		 }
	 }
	);
	
	// hide div and show email link
	$("#emailDiv_" + key).html('');
	$("#emailDiv_" + key).hide();
	$("#emailLink_" + key).show();
}

function showEmailEdit(key)
{
	$.post("newCustomerAjax.php", { "action": "sendEmail", "keyCode": key},
	 function(data) {
		 var dataString = new String(data);
		 if (dataString.indexOf('Sent To:') != -1)
		 {
			
			var htmlOut = "This New Customer Form has been sent to the following email recipients:<hr><br />" + data;
		 }
		 else
		 {
			var htmlOut = data;
		 }
		$("#dialog").html(htmlOut);
		$("#dialog").dialog('open');
	 }
	);
	
	
	
//	var editBox = "<span style='font-size:smaller'>Send To: </span><input id='txt_" + key + "' name='txt_" + key + "' type='text' size='10' maxlength='30'> <a href='javascript:sendEmail(\"" + key + "\")'>send</a>";
//	$("#emailDiv_" + key).html(editBox);
//	$("#emailDiv_" + key).show();
//	$("#emailLink_" + key).hide();
}

$(document).ready(function(){
	
	$("#dialog").dialog(
		{ 
			width: 600,
			height: 400,
			autoOpen: false 
		}
	);
	
});

</script>

<style>
.ItemRow {
	color:#444;
	text-align:center;
	vertical-align: middle;
}

.TitleRow {
	color:#9d532e; font-size:16px; vertical-align:middle;
}

a {
	color:#9d532e; text-decoration:underline;font-size:smaller;
}

</style>

</head>

<body>
<div id='in-process'>
<table bordercolor="#888888" width="800" border="1" align="center" cellpadding="6" cellspacing="0">
  <tr style="border:#4d67a0">
    <th colspan="10" class="customerBanner" style="font-size:20px; height:20px" scope="col">
    New Customer Forms
    </td>
	</th>
  </tr>
 <tr class='category-row'>
 <td width="223" >Customer Name</td>
 <td width="173">Date Added</td>
 <td width="135">Updated By</td>
 <td width="211">&nbsp;</td>
 </tr>
<?
	$query = "SELECT keyCode, section1, DATE_FORMAT(creationDate, '%m/%d/%Y') as creationDate FROM newCustomerForm WHERE complete=0 ORDER BY creationDate"; 
	$res = getResult($query);
	if (checkResult($res))
	{
		while ($line = $res->fetch_assoc())
		{
			extract($line);
			$section1 = fixString($section1);
			$values = json_decode($section1, true);
			
			extract($values);
			$editLink = "<a href='javascript:surfDialog(\"newCustomerForm.php?key=$keyCode&st=2\", 900, 700, window, false)'>edit</a>";

			$emailLink = '';
			if ($_SESSION['USERTYPE'] == 'super')
			{
				$emailLink = "&nbsp;<a id='emailLink_$keyCode' href=\"javascript:showEmailEdit('$keyCode')\">email</a><div id='emailDiv_$keyCode'></div>";
			}

			echo(" <tr class='TitleRow'>
			 <td>$customer_name_formal</td>
			 <td>$creationDate</td>
			 <td>$updated_by</td>
			 <td>
			 <a href='javascript:surfDialog(\"newCustomerForm.php?key=$keyCode&st=1\", 900, 700, window, false)'>view</a>&nbsp;
			 $editLink
			 $emailLink
			 </td>
			 </tr>
			");
		}	
	}
?>
</table>
</div>

<div id='complete' style="padding-top:25px">
<table bordercolor="#888888" width="800" border="1" align="center" cellpadding="6" cellspacing="0">
  <tr style="border:#4d67a0">
    <th colspan="10" class="customerBanner_complete" style="font-size:20px; height:20px" scope="col">
    New Customer Forms (processed)
    </td>
	</th>
  </tr>
 <tr class='category-row_complete'>
 <td width="223" >Customer Name</td>
 <td width="173">Date Added</td>
 <td width="135">Updated By</td>
 <td width="211">&nbsp;</td>
 </tr>
<?
	$query = "SELECT keyCode, section1, creationDate FROM newCustomerForm WHERE committed=1 AND complete=1 order by creationDate desc"; 
	$res = getResult($query);
	if (checkResult($res))
	{
		while ($line = $res->fetch_assoc())
		{
			extract($line);
			$editLink = '&nbsp;';
			$emailLink = '&nbsp;';

			if ($_SESSION['USERTYPE'] == 'super')
			{
				$editLink = "<a href='javascript:surfDialog(\"newCustomerForm.php?key=$keyCode&st=2\", 900, 700, window, false)'>edit</a>";
				$emailLink = "&nbsp;<a id='emailLink_$keyCode' href=\"javascript:showEmailEdit('$keyCode')\">email</a><div id='emailDiv_$keyCode'></div>";
			}
			
			$values = json_decode($section1);
			$updated_by = $values->{'updated_by'};
			$customer_name_formal = $values->{'customer_name_formal'};
			echo(" <tr class='TitleRow'>
			 <td>$customer_name_formal</td>
			 <td>$creationDate</td>
			 <td>$updated_by</td>
			 <td>
			 <a href='javascript:surfDialog(\"newCustomerForm.php?key=$keyCode&st=1\", 900, 700, window, false)'>view</a>&nbsp;
			 $editLink
			 $emailLink
			 </td>
			 </tr>
			");
		}	
	}
?>
</table>
</div>
<div id="dialog" title="Message"></div>

<div id='debugDiv'></div>

</body>
</html>