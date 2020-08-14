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
?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Process Control Management</title>
<link rel="stylesheet" TYPE="text/css" href="http://h202.customhostingtools.com/main.css" >
<link rel="stylesheet" TYPE="text/css" href="planning.css" >
<SCRIPT LANGUAGE="javascript" TYPE="text/javascript" SRC='http://www.customhostingtools.com/lib/helper.js'></SCRIPT>
<!--<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
--><SCRIPT LANGUAGE="javascript" TYPE="text/javascript" SRC='http://www.customhostingtools.com/lib/admin.js'></SCRIPT>
<script src="http://www.customhostingtools.com/lib/jquery.js" type="text/javascript"></script>
<script type="text/javascript">
<? include_once "custSummary.php"; ?>
</script>
<script type="text/javascript">

//alert(11);

<?	
	include "mapsjs.php";
?>
//alert(22);

function loadScript() {
//	alert(33);
  var script = document.createElement("script");
  script.type = "text/javascript";
  script.src = "http://maps.googleapis.com/maps/api/js?sensor=false&callback=initialize";
  document.body.appendChild(script);
}
  

function showBudgetHistory(selobj)
{
	var selval = selobj.value;
	
	if (selval == 'budget')
		surfDialog('budgetHistory.php?customerID=<?=$customerID?>', 835, 650, window, false);
	else if (selval == 'service')
		surfDialog('serviceHistory.php', 835, 650, window, false);	

	selobj.selectedIndex = 0;
}

</script>
</head>
<body>
<center>
  <table width="800" align="center" cellpadding="0" cellspacing="0">
    <tr>
      <? include 'customerBanner.php' ?>
    </tr>
    <tr class="customerBanner2">
      <td colspan="3" valign="top">
      <table width="100%">
          <tr>
            <td>
                <select onChange="showBudgetHistory(this)" name="operations">
                  <option value="-1">-- Operations --</option>
                  <option value="budget">Budget History</option>
                  <option value="service">Service History</option>
                </select>
              </td>
            <td><div class="<?=  $USERID != $CUSTOMER_EMAIL ? 'planningButtonBarCenter' : 'planningButtonBarLeft'?>"> <?=$customerName?></div></td>
            <td><div style="text-align:right;padding-right:15px"> &nbsp;
<?php if ( $USERID != $CUSTOMER_EMAIL) : ?>
            <img onMouseUp="javascript:surfDialog('/planning.php?customerID=<?=$customerID?>', 835, 650, window, false)" onMouseOver="this.src='images/planning_down.gif'" onMouseOut="this.src='images/planning_up.gif'" src="images/planning_up.gif"> 
<?php endif; ?>
            </div></td>
          </tr>
        </table>
       </td>
    </tr>
    <tr>
      <td width="800" align="center" colspan="3">
        <div style="width:100%; height:500px" id="map_canvas"></div>
       </td>
    </tr>
  </table>
  <div id='project-list'>
    <table bordercolor="#888888" width="800" border="1" align="center" cellpadding="6" cellspacing="0">
      <tr>
        <th colspan="8" class="customerBanner" style="font-size:20px; height:20px" scope="col"> Project Planning </th>
      </tr>
      <?
$catRes = getResult("select catID, name from planning_categories order by name");
if (checkResult($catRes))
{
	while ($catLine = mysql_fetch_assoc($catRes))
	{
		extract($catLine);
		$itemRes = getResult("SELECT pi.itemID, pi.custView, pi.title, pi.pctComplete, pi.responsible, pi.item_timing, pi.impact FROM planning_items pi where pi.customerID='$customerID' and pi.custView=1 and pi.catID=$catID and pi.pctComplete < 100 order by itemID");
		$itemCount = mysqli_num_rows($itemRes);
		
		// Category Row
		echo("\n
		<tr class='category-row'>
		<td colspan='8'><div style='float:left'>$name ($itemCount)</div><div id='plus_$catID' class='expand-row' onClick='expand($catID)'>+</div>
		</tr>");

		// title Bar
		echo("\n  <tr class='planningTitleRow row_$catID'>
			<td width='189'>Issue Title</td>
			<td width='47'><p>% </p>
			<p><span style='font-size:smaller'>Complete</span></p></td>
			<td width='90'>Responsible<br>
			  Parties</td>
			<td width='44'>Timing</td>
			<td width='196'>Status</td>
			<td width='121'>Impact</td>
		  </tr>
		");

		if (checkResult($itemRes))
		{
			while( $itemLine = mysql_fetch_assoc($itemRes) )
			{
				extract($itemLine);
				$statusRes = getResult("SELECT DATE_FORMAT(date, '%m/%d/%Y %I:%i %p') as statusDate, author, status FROM planning_status WHERE itemID=$itemID order by date DESC LIMIT 1");
				if (checkResult($statusRes))
				{
					$statusLine = mysql_fetch_assoc($statusRes);
					extract($statusLine);
					$status = "<p class=\"smallerText\">$status</p><div class=\"author-date\">$author: $statusDate</div>";
				}

				$visible = $custView == 1 ? 'Visible' : 'Hidden';
				//$responsible = str_replace(',', '<br />', $responsible);
				$statusLink = "<a href='javascript:surfDialog(\"planningStatus.php?id=$itemID\", 600, 315, window, false, false)'>add/view status</a>";

				echo("\n  <tr id='rowID_$itemID' class='planningItemRow row_$catID'>
					<td>$title</td>
					<td align='center'>$pctComplete</td>
					<td class='smallerText' nowrap>$responsible</td>
					<td align='center'>$item_timing</td>
					<td class='smallerText' valign='top'>$status<p style='text-align:right;font-size:smaller'>$statusLink</p></td>
					<td class='smallerText'>$impact</td>
				  </tr>");
				
			}
		}
		
	}
	
	
}
?>
    </table>
  </div>
</center>
<script language="javascript" type="text/javascript">
	loadScript();
</script>
</body>
</html>
