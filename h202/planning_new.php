<?
session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once '/var/www/html/CHT/h202/h202Functions.php';
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
if (empty($_SESSION["USERID"]))
{
	$js = "alert('Your session has expired');\n window.close();\n";
}


?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Planning</title>
<link rel="stylesheet" TYPE="text/css" href="http://h202.customhostingtools.com/main.css" >
<link rel="stylesheet" TYPE="text/css" href="planning.css" >
<SCRIPT LANGUAGE="javascript" TYPE="text/javascript" SRC='http://www.customhostingtools.com/lib/helper.js'></SCRIPT>
<SCRIPT LANGUAGE="javascript" TYPE="text/javascript" SRC='http://www.customhostingtools.com/lib/admin.js'></SCRIPT>

<script src="http://www.customhostingtools.com/lib/jquery.js" type="text/javascript"></script>

<script type="text/javascript">
<?
	echo($js);
?>
<? include_once "custSummary.php"; ?>

function callPrint()
{
	window.print();
}

</script>

</head>

<body>
<div id='project-list'>
<table bordercolor="#888888" width="800" border="1" align="center" cellpadding="6" cellspacing="0">
  <tr style="border:#4d67a0">
    <th width="60" colspan="1" nowrap class="customerBanner" style="font-size:20px; height:20px" scope="col"><span id='upper-expand-link' class='expand-all' onClick="expandAll('upper')">+</span>&nbsp;<span style="float:right"><a href='javascript:expandAll("lower");expandAll("upper", "print")'>print</a></span></th>
    <th width="710" colspan="7" class="customerBanner" style="font-size:20px; height:20px" scope="col">
    <div style="float:right"><a href='javascript:editItem()'>add new item</a></div>
    Project Planning
    </td>
	</th>
  </tr>
  
<?
$catRes = getResult("select catID, name from planning_categories order by name");
if (checkResult($catRes))
{
	while ($catLine = mysqli_fetch_assoc($catRes))
	{
		extract($catLine);
		$itemRes = getResult("SELECT pi.itemID, pi.custView, pi.title, pi.highlight, pi.pctComplete, pi.responsible, pi.item_timing, pi.impact FROM planning_items pi where pi.customerID='$customerID' and pi.pctComplete < 100  and pi.catID=$catID order by itemID");
		$itemCount = mysqli_num_rows($itemRes);
		
		// Category Row
		echo("\n
		<tr class='category-row'>
		<td colspan='8'><div style='float:left'>$name ($itemCount)</div><div id='plus_$catID' class='expand-row' onClick='expand($catID)'>+</div>
		</tr>");

		// title Bar
		echo("\n  <tr class='planningTitleRow row_$catID'>
			<td width='67'> Customer Visibility</td>
			<td width='121'>Issue Title</td>
			<td width='47'><p>% </p>
			<p><span style='font-size:smaller'>Complete</span></p></td>
			<td width='90'>Responsible<br>
			  Parties</td>
			<td width='44'>Timing</td>
			<td width='196'>Status</td>
			<td width='69'>Impact</td>
			<td width='52'>&nbsp;</td>
		  </tr>
		");

		if (checkResult($itemRes))
		{
			while( $itemLine = mysqli_fetch_assoc($itemRes) )
			{
				extract($itemLine);
				$statusRes = getResult("SELECT DATE_FORMAT(date, '%m/%d/%Y %I:%i %p') as statusDate, author, status FROM planning_status WHERE itemID=$itemID order by date DESC LIMIT 1");
				if (checkResult($statusRes))
				{
					$statusLine = mysqli_fetch_assoc($statusRes);
					extract($statusLine);
					$status = "<p class=\"smallerText\">$status</p><div class=\"author-date\">$author: $statusDate</div>";
				}

				$visible = $custView == 1 ? 'Visible' : 'Hidden';
				//$responsible = str_replace(',', '<br />', $responsible);
				echo("\n  <tr id='rowID_$itemID' class='planningItemRow row_$catID'>
					<td height='75' align='center' valign='middle'><a id='vis_$itemID' href='javascript:toggleHidden($itemID)'>$visible</a></td>
					<td>$title</td>
					<td align='center'>$pctComplete</td>
					<td class='smallerText' nowrap>$responsible</td>
					<td align='center' class='" . $highlight . "Box'>$item_timing</td>
					<td class='smallerText' valign='top'>$status</td>
					<td class='smallerText'>$impact</td>
					<td align='center'><p><a href='javascript:editItem($itemID)'>edit</a></p><p><a href='javascript:deleteItem($itemID)'>delete</a></p></td>
				  </tr>");
				
			}
		}
		
	}
	
	
}
?>
</table>

<!----- 100% Complete Area -------->
<br><br>
<table bordercolor="#888888" width="800" border="1" align="center" cellpadding="6" cellspacing="0">
  <tr>
    <th colspan="8" class="customerBanner_complete" style="font-size:20px; height:20px" scope="col">
    <div style="float:left" id='lower-expand-link' class='expand-all' onClick="expandAll('lower')">+</div>
    <div style="text-align:center; padding-right:85px">Completed Items</div>
    </td>
</th>
  </tr>
  
<?
$catRes = getResult("select catID, name from planning_categories order by name");
if (checkResult($catRes))
{
	while ($catLine = mysqli_fetch_assoc($catRes))
	{
		extract($catLine);
		$itemRes = getResult("SELECT pi.itemID, pi.custView, pi.title, pi.pctComplete, pi.responsible, pi.item_timing, pi.impact FROM planning_items pi where pi.customerID='$customerID' and pi.pctComplete = 100  and pi.catID=$catID order by itemID");
		$itemCount = mysqli_num_rows($itemRes);
		
		// Category Row
		echo("\n
		<tr class='category-row_complete'>
		<td colspan='8'><div style='float:left'>$name ($itemCount)</div><div id='lower_plus_$catID' class='expand-row' style='color:#ffffff' onClick='expand_lower($catID)'>+</div>
		</tr>");

		// title Bar
		echo("\n  <tr class='planningTitleRow_complete row_" . $catID . "_complete'>
			<td width='189'>Issue Title</td>
			<td width='47'><p>% </p>
			<p><span style='font-size:smaller'>Complete</span></p></td>
			<td width='90'>Responsible<br>
			  Parties</td>
			<td width='44'>Timing</td>
			<td width='196'>Status</td>
			<td width='69'>Impact</td>
			<td width='52'>&nbsp;</td>
		  </tr>
		");

		if (checkResult($itemRes))
		{
			while( $itemLine = mysqli_fetch_assoc($itemRes) )
			{
				extract($itemLine);
				$statusRes = getResult("SELECT DATE_FORMAT(date, '%m/%d/%Y %I:%i %p') as statusDate, author, status FROM planning_status WHERE itemID=$itemID order by date DESC LIMIT 1");
				if (checkResult($statusRes))
				{
					$statusLine = mysqli_fetch_assoc($statusRes);
					extract($statusLine);
					$status = "<p class=\"smallerText\">$status</p><div class=\"author-date\">$author: $statusDate</div>";
				}

				$visible = $custView == 1 ? 'Visible' : 'Hidden';
				//$responsible = str_replace(',', '<br />', $responsible);
				echo("\n  <tr id='rowID_" . $itemID . "_complete' class='planningItemRow_complete row_" . $catID . "_complete'>
					<td>$title</td>
					<td align='center'>$pctComplete</td>
					<td class='smallerText' nowrap>$responsible</td>
					<td align='center'>$item_timing</td>
					<td class='smallerText' valign='top'>$status</td>
					<td class='smallerText'>$impact</td>
					<td align='center'><p><a href='javascript:editItem($itemID)'>edit</a></p><p><a href='javascript:deleteItem($itemID)'>delete</a></p></td>
				  </tr>");
				
			}
		}
		
	}
	
	
}
?>
</table>
<!--------- end of 100% complete ----------->


</div>

<div id='editProjectDiv'>
<table width="700" align="center" bordercolor="#888888" border='1' cellpadding="6" cellspacing="0">
  <tr>
    <th id="addEditBanner" colspan="8" class="customerBanner" style="font-size:20px; height:20px; padding-top:20px; padding-bottom:20px" scope="col"></th>
  </tr>

  <tr>
    <td width="156" class="planningTitleRow">Category:</td>
    <td colspan="2" class="planningItemRow"><select name="categories" id="categories">
<?
mysql_data_seek($catRes, 0);
while ($catLine = mysqli_fetch_assoc($catRes))
{
	extract($catLine);
	$selected = "";
	if ($catID == $selectedCatID)
	{
		$selected = " SELECTED";
	}
	echo("\n<option value='$catID' $selected>$name</option>");
}
?>
    </select></td>
    </tr>
  <tr>
    <td class="planningTitleRow">Title:</td>
    <td colspan="2" class="planningItemRow"><input name="title" type="text" id="title" size="35" maxlength="200"></td>
    </tr>
  <tr>
    <td class="planningTitleRow">% Complete:</td>
    <td colspan="2" class="planningItemRow"><input onKeyPress="return isNumberKey(event)" name="percent_complete" type="text" id="percent_complete" size="5" maxlength="3"></td>
    </tr>
  <tr>
    <td class="planningTitleRow"><p>Responsible Parties:</p>
      <p class="author-date">(ctrl-click for multiple)</p></td>
    <td width="177" class="planningItemRow"><select name="select" size="7" multiple id="sel_respList">
<?
	$interEmailRes = getResult("SELECT DISTINCT FirstName as internFirst, LastName as internLast FROM internalEmailDist ORDER BY LastName");
	if (checkResult($interEmailRes))
	{
		while($interEmailLine = mysqli_fetch_assoc($interEmailRes))
		{
			extract($interEmailLine);
			echo("\n<option value='$internLast, $internFirst'>$internLast, $internFirst</option>");
		}
	}
?>    
    </select></td>
    <td width="323"><div class="" style="font-size:smaller; width:100%" id='responsible-party-list'></div></td>
  </tr>
  <tr>
    <td class="planningTitleRow">Timing:</td>
    <td class="planningItemRow"><input name="timing" type="text" id="timing" size="15"></td>
    <td valign="bottom"><span style="font-size:smaller">Highlight:</span>
    <img id="redButton" onclick='setSelectedColor("red")' src="images/red.png" width="75" height="20" alt="red">
    <img id="greenButton" onclick='setSelectedColor("green")' src="images/green.png" width="75" height="20" alt="green">
    <img id="yellowButton" onclick='setSelectedColor("yellow")' src="images/yellow.png" width="75" height="20" alt="yellow">
    <div style="text-align:center"><a style="font-size:smaller; color:#040" href='javascript:clearHighlight()'>clear highlight</a></div>
    </td>
    </tr>
  <tr>
    <td class="planningTitleRow">Add Status:</td>
    <td class="planningItemRow"><textarea name="new-status" id="new-status" cols="20" rows="4"></textarea></td>
    <td class="planningItemRow">
    <div id='statusHistory'>&nbsp;</div>
    </td>
  </tr>
  <tr>
    <td class="planningTitleRow">Impact:</td>
    <td colspan="2" class="planningItemRow"><input name="impact" type="text" id="impact" size="25"></td>
    </tr>
  <tr>
    <td class="planningTitleRow">&nbsp;</td>
    <td class="planningItemRow">&nbsp;</td>
    <td class="planningItemRow" style="text-align:right"><a href='javascript:cancelItem()'>Cancel</a> <a href='javascript:saveItem()'>Save</a>&nbsp;</td>
  </tr>
</table>
</div>
<div id='customerLogin' style="visibility:hidden"><?=$CUSTOMER_EMAIL?></div>
<div id='itemID' style="visibility:hidden"></div>
</body>
</html>