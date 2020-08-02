<?
session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once '/var/www/html/CHT/h202/h202Functions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';


if (empty($DELIVERY_TANKS))
{
	$js = "alert('It appears your session has timed out.  Please reload main page and log in again');window.close();";
}
else
{
	$sites_arr = array();
	foreach ($DELIVERY_TANKS as $monitorID)
	{
		$query = "SELECT siteID FROM monitor WHERE monitorID='$monitorID' LIMIT 1";
		$res = getResult($query);
		if (checkResult($res))
		{
			$line = mysql_fetch_assoc($res);
			extract($line);
	
			if (array_search($siteID, $sites_arr) === false)
			{
				array_push($sites_arr, $siteID);
			}		
		}
	}
	
	if ($emailAction == 'rem_sup_email' && !empty($id))
	{
		executeQuery("DELETE FROM supplierEmailDist WHERE id=$id LIMIT 1");
		executeQuery("DELETE FROM deliveryEmailSupplierSelected WHERE emailID=$id");
	}
	elseif ($emailAction == 'rem_int_email' && !empty($id) )
	{
		$query = "DELETE FROM internalEmailDistSites WHERE id=$id";
		executeQuery($query);
		// CHECK TO SEE IF internalEmailDist record exists
	
	}
	
	if ($showInternal=='all')
	{
		session_register('SHOW_INTERNAL');
		$SHOW_INTERNAL = 'all';
	}
	else
	{
		$SHOW_INTERNAL = 'checked';
	}
	
	if ($showSupplier=='all')
	{
		session_register('SHOW_SUPPLIER');
		$SHOW_SUPPLIER = 'all';
	}
	else
	{
		$SHOW_SUPPLIER = 'checked';
	}
	
	if (empty($DELIVERY_TANKS))
	{
		$js = "\nwindow.close();";
	}
	elseif ($REQUEST_METHOD == 'POST')
	{
		if ($emailAction == 'addSupplierEmail')
		{
			if (empty($addSupplierEmail))
			{
				$supmsg = "<p class=\"style4 style5\">Please provide a supplier email address</p>";
			}
			else
			{
				
				$addSupplierEmail = fixSingleQuotes($addSupplierEmail);
				
				$res = getResult("select id from supplierEmailDist where email = '$addSupplierEmail' AND supplierID=$supplierID");
				if (checkResult($res))
				{
					$supmsg = "<p class=\"style4 style5\">That email address already exists in the list</p>";
				}
				else
				{
					executeQuery("INSERT INTO supplierEmailDist (supplierID, FirstName, LastName, email) VALUES ($supplierID, '$addSupplierFirstName', '$addSupplierLastName', '$addSupplierEmail')");
					session_register('SHOW_SUPPLIER');
					$SHOW_SUPPLIER = 'all';
				}	
			}
		}
		elseif ($emailAction == 'addInternalEmail')
		{
			if (empty($addInternalFirstName) || empty($addInternalLastName) || empty($addInternalEmail))
			{
				$intmsg = "<p class=\"style4 style5\">Please provide Name and Email</p>";
			}
			else
			{
				$addInternalEmail = fixSingleQuotes($addInternalEmail);
				
				
				// get the siteID for each of the tanks.  Add this email info to each site
				foreach ($sites_arr as $siteID)
				{
					$res = getResult("select i.email from internalEmailDist i, internalEmailDistSites s where i.id=s.id and i.email = '$addInternalEmail' AND s.siteID=$siteID");
					if (checkResult($res))
					{
						$intmsg = "<p class=\"style4 style5\">That email address already exists in the list</p>";
					}
					else
					{
						$ires = getResult("SELECT id FROM internalEmailDist WHERE email='$addInternalEmail'");
						if (!checkResult($ires))
						{
							$id = executeQuery("INSERT INTO internalEmailDist (FirstName, LastName, email) VALUES ('$addInternalFirstName', '$addInternalLastName', '$addInternalEmail')", 'INSERT');
						}
						else
						{
							$iline = mysql_fetch_assoc($ires);
							extract($iline);
						}
						$ires = getResult("SELECT id FROM internalEmailDistSites WHERE id=$id AND siteID=$siteID");
						if (!checkResult($ires))
						{
							executeQuery("INSERT INTO internalEmailDistSites (id, siteID) VALUES ($id, $siteID)");
						}
					}
				}
			}
		}
		else // posting to add to the tables
		{
			$deliverySupplierID = $DELIVERY_DATA['deliverySupplierID'];
			// clear all supplier email selections.  They will be reset on this post
			if (!empty($deliverySupplierID))
			{
				executeQuery("UPDATE supplierEmailDist SET selected = 0 WHERE supplierID=$deliverySupplierID");
			}
			
			// go through and un-select all site related email
			foreach($_POST as $fld=>$val)
			{
				if ( (strpos($fld, 'custEmail_') !== false) || (strpos($fld, 'site_') !== false) )
				{
					if (strpos($fld, 'custEmail_') !== false)
					{
						list($x, $siteID) = explode('_', $fld);  // only need siteID
					}
					elseif (strpos($fld, 'site_') !== false)
					{
						list($x, $y, $siteID) = explode('_', $fld);  // only need siteID
					}
					executeQuery("UPDATE siteEmailDist SET selected = 0 WHERE siteID=$siteID");
					
					// we need to log the activity of when an internal email has been changed.  To
					// do this we must store existing checked internal emails, then compare later to
					// see if one was checked or unchecked
					if ($initialize == 'yes')
					{
						executeQuery("DROP TABLE IF EXISTS " . $siteID . "_intEmail");
						executeQuery("CREATE TABLE IF NOT EXISTS " . $siteID . "_intEmail SELECT id, selected, siteID FROM internalEmailDistSites WHERE siteID=$siteID");
						$initialize = 'no';
					}
					
				}
			}

			foreach($_POST as $fld=>$val)
			{
				if (strpos($fld, 'custEmail_') !== false)
				{
					list($x, $siteID) = explode('_', $fld);
					executeQuery("UPDATE internalEmailDistSites SET selected = 0 WHERE siteID=$siteID");
				}
			}

			$sitesLogged = "";
			foreach($_POST as $fld=>$val)
			{
				if (strpos($fld, 'custEmail_') !== false)
				{
					list($x, $siteID) = explode('_', $fld);
					$val = str_replace('*', '', $val);
	
					// update the customer login table
					$emails = explode("\n", $val);
					foreach($emails as $email)
					{
						$email = trim($email);
						$email = fixString($email);
						$email = strtolower($email);
						$email = fixSingleQuotes($email);
						
						// check to see if an email was removed.  If so, removed from the customerLoginEmail table.
						$res2 = getResult("Select deliveryEmailDist from site where siteID=$siteID");
						if (checkResult($res2))
						{
							$line2 = mysql_fetch_assoc($res2);
							extract($line2);
							$emails2 = explode("\n", $deliveryEmailDist);
							foreach($emails2 as $email2)
							{
								$email2 = trim($email2);
								$email2 = fixSingleQuotes($email2);
								// See if we have an email in the table that was NOT posted.  Meaning, and email has been removed.
								if (!empty($email2))
								{
									if (strpos($val, $email2) === false)
									{
										// remove from customerLoginEmail only if not somewhere else
										$res3 = getResult("SELECT siteID as tmpSiteID FROM site WHERE deliveryEmailDist LIKE '%$email2%' AND siteID!=$siteID");
										if (!checkResult($res3))
										{
											executeQuery("DELETE FROM customerLoginEmail WHERE email = '$email2' LIMIT 1");
										}
									}
								}
							}
						}
	
	
						$res2 = getResult("SELECT email FROM customerLoginEmail WHERE email='$email'");
						if (!checkResult($res2))
						{
							executeQuery("INSERT INTO customerLoginEmail (email) values ('$email')");		
						}
					}
					$val = fixSingleQuotes($val);
					executeQuery("UPDATE site set deliveryEmailDist='$val' WHERE siteID = $siteID LIMIT 1");
				}
				elseif (strpos($fld, 'sup_') !== false)
				{
					executeQuery("UPDATE supplierEmailDist SET selected = 1 WHERE id=$val and supplierID=$deliverySupplierID LIMIT 1");
				}
				elseif ($fld == "carrierEmailDist")
				{
					$carrierEmailDist = fixSingleQuotes($carrierEmailDist);
					if (!empty($deliveryID))
					{
						executeQuery("UPDATE delivery SET carrierEmailDist='$carrierEmailDist' WHERE deliveryID=$deliveryID LIMIT 1");
					}
	
					if ($DELIVERY_DATA['deliveryCarrierID'] > 0)
					{
						executeQuery("UPDATE carrier SET deliveryEmailDist = '$carrierEmailDist' WHERE carrierID = " . $DELIVERY_DATA['deliveryCarrierID']  ." LIMIT 1");
					}
				}
				elseif (strpos($fld, 'site_') !== false)
				{
					// set the selected value for all sites posted
					if (!empty($val))
					{
						list($x, $id, $y) = explode('_', $fld); // get the id from 'site_<id>_<site>'
											
						foreach ($sites_arr as $siteID)
						{
							
							// add the internal email contact for each site
							$sres = getResult("SELECT id FROM internalEmailDistSites where id=$id and siteID=$siteID");
							if (checkResult($sres))
							{
								executeQuery("UPDATE internalEmailDistSites SET selected = 1 WHERE id=$id and siteID=$siteID LIMIT 1");
							}
							else
							{
								executeQuery("INSERT INTO internalEmailDistSites (id, siteID, selected) VALUES ($id, $siteID, 1)");
							}
						}
	
						// loop through sites again and see if there was a difference.  If so, log the change  ---> emailAction == 'readonly'
						if ($emailAction != 'reloadOnly')
						{
	
							foreach ($sites_arr as $siteID)
							{
								if (strpos($sitesLogged, "$siteID") !== false)
									continue;
									
								$query = "SELECT table_name FROM information_schema.tables WHERE table_schema = 'h202' AND table_name = '" . $siteID . "_intEmail'";
								$tmpRes = getResult($query);
								if (checkResult($tmpRes))
								{
									$query = "SELECT a.selected as a_selected, b.selected as b_selected FROM  " . $siteID . "_intEmail b, internalEmailDistSites a WHERE (a.siteID=b.siteID and a.id=b.id) and b.siteID=$siteID and a.selected <> b.selected";
									$intRes = getResult($query);
									executeQuery("DROP TABLE IF EXISTS " . $siteID . "_intEmail");  // we're done with the table.  drop it
									if (checkResult($intRes))
									{
										$sRes = getResult("SELECT siteLocationName FROM site WHERE siteID=$siteID LIMIT 1");
										if (checkResult($sRes))
										{
											$sLine = mysql_fetch_assoc($sRes);
											extract($sLine);
										}
										$sitesLogged .= "$siteID:";
									}
								}
							}
						}
					}
				}
				
			}
			if ($emailAction != 'reloadOnly')
			{
				$js = "\nwindow.close();";
			}
			
			if (!empty($deliveryID))
			{
				executeQuery("DELETE FROM deliveryEmailSupplierSelected WHERE deliveryID=$deliveryID");
				$query = "SELECT id, selected FROM supplierEmailDist WHERE supplierID=$deliverySupplierID";
				$supRes = getResult($query);
				if (checkResult($supRes))
				{
					while ($supLine = mysql_fetch_assoc($supRes))
					{
						extract($supLine);
						executeQuery("INSERT INTO deliveryEmailSupplierSelected (deliveryID, emailID, selected) 
						VALUES ($deliveryID, $id, $selected )");
					}
				}
				
			}
			
		}
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Delivery Email Distribution</title>
<link rel="stylesheet" TYPE="text/css" href="http://h202.customhostingtools.com/main.css" >
<SCRIPT LANGUAGE="javascript" TYPE="text/javascript" SRC='http://www.customhostingtools.com/lib/admin.js'></SCRIPT>
<script language="javascript">
<?=$js?>

function addSupplierContact()
{
	document.addSupplierForm.submit();
	//document.emailForm.emailAction.value="reloadOnly"; 
	//document.emailForm.submit()
}

function showSupplier(val)
{
	document.emailForm.showSupplier.value=val; 
	document.emailForm.emailAction.value='reloadOnly';
	document.emailForm.submit();
}

function showInternal(val)
{
	document.emailForm.showInternal.value=val; 
	document.emailForm.emailAction.value='reloadOnly';
	document.emailForm.submit();
}
</script>
<style type="text/css">
<!--
.style4 {font-size: 12px}
.style6 {color: #000000}
-->
</style>
</head>
<body>
<? if (false) : ?>
<div id='ActionDiv' name='ActionDiv' style='position:absolute;left:0;top:0'>
		<iframe id='ActionFrame' name='ActionFrame' style='background-color:#FFFFFF' scrolling=no width=200 height=200 align=top frameborder=0 
		src=''  align='left' allowtransparency='true' marginheight='0' marginwidth='0' ></iframe>
</div>
<? else : ?>
<div id='ActionDiv' name='ActionDiv' style='visibility:hidden;position:absolute;left:0;top:0'>
		<iframe id='ActionFrame' name='ActionFrame' style='background-color:#FFFFFF' scrolling=no width=0 height=0 align=top frameborder=0 
		src=''  align='left' allowtransparency='true' marginheight='0' marginwidth='0' ></iframe>
</div>
<? endif; ?>
<p align="center" class="spinSmallTitle">Select each contact email to be included for this delivery confirmation email.  </p>
<p align="center">&nbsp;</p>
<div align="center">
  <table width="700" border="1" cellpadding="5" cellspacing="1" bordercolor="#333333" class="spinTableBarOdd">
  <form id="emailForm" name="emailForm" method="post" action="deliveryEmailDist.php">
  <input type='hidden' id='showInternal' name='showInternal' value="<?=$SHOW_INTERNAL?>" />
  <input type='hidden' id='showSupplier' name='showSupplier' value="<?=$SHOW_SUPPLIER?>" />
  <input type='hidden' id='deliveryID' name='deliveryID' value="<?=$deliveryID?>" />
  <input type='hidden' id='emailAction' name='emailAction'  />
  <input type='hidden' id='initialize' name='initialize' value="<?=$init?>" />
    <tr valign="top" class="spinTableTitle">
      <td width="200"><div align="left">Customer Email<br />
            <span class="spinSmallTitle style4">      List each customer email address on a seperate line. </span></div></td>
      <td width="229"><div align="left">Supplier Email </div><p align="left" class="header_3"><br />
          <span class="style6">
		  <? if ($SHOW_SUPPLIER=='all') : ?>
		  <a href='javascript:showSupplier("checked")'>show selected only</a>
		  <? else : ?>
		  <a href='javascript:showSupplier("all")'>show all</a>
		  <? endif; ?>
		  </span></p></td>
      <td width="229"><div align="left">Internal Email </div><p align="left" class="header_3"><br />
          <span class="style6">
		  <? if ($SHOW_INTERNAL=='all') : ?>
		  <a href='javascript:showInternal("checked")'>show selected only</a>
		  <? else : ?>
		  <a href='javascript:showInternal("all")'>show all</a>
		  <? endif; ?>
		  </span></p></td>
    </tr>
    <tr valign="top">
      <td align="left" width="200">
<?
//showArray($DELIVERY_DATA);
$monitor_arr = array();
$siteHTML = '';
$tmpSiteID = 0;
foreach($DELIVERY_TANKS as $monitorID)
{
	if (array_search($monitorID, $monitor_arr) === false)
	{
		$siteEmailList = '';
		array_push($monitor_arr, $monitorID);
		$query = "SELECT s.siteID, s.siteLocationName, s.deliveryEmailDist, s.contactEmail FROM monitor t, site s 
						WHERE t.monitorID='$monitorID' AND t.siteID=s.siteID LIMIT 1";
		$siteres = getResult($query);
		if (checkResult($siteres))
		{
			$sline = mysql_fetch_assoc($siteres);
			extract($sline);	
			if ($siteID != $tmpSiteID)
			{	
				$emailDist = empty($deliveryEmailDist) ? "*$contactEmail" : $deliveryEmailDist;
				$siteHTML .= "<hr><span class='spinSmallTitle'>$siteLocationName</span><br />
				<textarea name='custEmail_$siteID' cols='20' rows='4' id='custEmail_$siteID' wrap='off'>$emailDist</textarea>";
			}
			$tmpSiteID = $siteID;
		}
	}
}

if (!empty($siteHTML))
{
	$siteHTML = substr($siteHTML, 4); // strip leading <hr>
}
echo $siteHTML;
?>
      </td>
      <td width="229">
	  
	  <table width="100%" height="0" border="0" cellpadding="2" cellspacing="1">
      <?
	  	$deliverySupplierID = $DELIVERY_DATA['deliverySupplierID'];
		// if this is an existing delivery we get the values from the the supplier distribution
		// list that was at the time of the delivery.
		if (!empty($deliveryID))
		{
			$query = "SELECT sd.id, sd.FirstName, sd.LastName, sd.email, des.selected 
			FROM deliveryEmailSupplierSelected des, supplierEmailDist sd 
			WHERE 
			des.deliveryID = $deliveryID and
			des.emailID = sd.id 
			ORDER BY sd.LastName";
		}
		else
		{
	  		$query = "SELECT id, FirstName, LastName, email, selected FROM supplierEmailDist WHERE supplierID=$deliverySupplierID ORDER BY LastName";
		}
		 $res = getResult($query);
		 
		 if (!checkResult($res))
		 {
		 	// get contact email from supplier table and insert
			$query = "SELECT contact, email FROM supplier WHERE supplierID=$deliverySupplierID LIMIT 1";
			//die($query);
			$res = getResult($query);
			if (checkResult($res))
			{
				$line = mysql_fetch_assoc($res);
				extract($line);
				$email = fixSingleQuotes($email);
				list($FirstName, $LastName) = explode(' ', $contact);
				executeQuery("INSERT INTO supplierEmailDist (supplierID, FirstName, LastName, email, selected) VALUES ($deliverySupplierID, '$FirstName','$LastName','$email',1)");
				$query = "SELECT id, FirstName, LastName, email, selected FROM supplierEmailDist WHERE supplierID=$deliverySupplierID ORDER BY LastName";
				$res = getResult($query);
			}
		 }
		 
		 if (checkResult($res))
		 {
		 	while ($line = mysql_fetch_assoc($res))
			{
				extract($line);
				$chked = $selected == 1 ? 'CHECKED' : '';
				
				if ($SHOW_SUPPLIER != 'all' && $selected == 0)
				{
					continue;
				}		
				
				
				$email = strlen($email) > 20 ? substr($email, 0, 18) . '...' : $email;
				if (!empty($LastName) && !empty($FirstName))
				{
					$name = "$LastName, $FirstName <br>";
				}
				elseif (!empty($FirstName))
				{
					$name = "$FirstName <br>";
				}
				elseif (!empty($LastName))
				{
					$name = "$LastName <br>";
				}
				else
				{
					$name = '';
				}
				
				
				echo "\n<tr>
				  <td valign='top' width='20'><label>
					<input name='sup_$id' type='checkbox' id='sup_$id' value='$id' $chked />
					</label></td>
				  <td width='182'><div align='left'>$name $email</div></td>
				  <td><a href='/deliveryEmailDist.php?emailAction=rem_sup_email&id=$id'>remove</a></td>
				</tr>";
			}
		 }
		 else
		 {
		 	echo "\n<tr>
				  <td>&nbsp;</td>
				  <td><div align='left'></div></td>
				  <td>&nbsp;</td>
				</tr>";
		 }
	  
	// if modifying, get the carrier email distribution from the delivery table
	$carrierEmailRes = getResult("SELECT carrierName, deliveryEmailDist as carrierEmailDist FROM carrier WHERE carrierID= " . $DELIVERY_DATA['deliveryCarrierID']);
	if (checkResult($carrierEmailRes))
	{
		$cline = mysql_fetch_assoc($carrierEmailRes);
		extract($cline);
	}

	if (!empty($deliveryID))
	{
		// for new deliveries, get the carrier selected and find the default email distribution	  
		$carrierEmailRes = getResult("SELECT carrierEmailDist FROM delivery WHERE deliveryID= $deliveryID");
		if (checkResult($carrierEmailRes))
		{
			$cline = mysql_fetch_assoc($carrierEmailRes);
			extract($cline);
		}
	}
	  
	$carrierEmailOut = '';
	$carrierTitle = empty($carrierName) ? '' : "<strong>$carrierName Email (carrier)</strong><br />";
	
	if (!empty($carrierTitle))
	{
		$carrierEmailOut = "<hr>
			<div align=\"left\">$carrierTitle
				<span class=\"spinSmallTitle style4\">List each carrier email address on a seperate line. </span></div>
				<br />
				<textarea name='carrierEmailDist' cols='20' rows='4' id='carrierEmailDist' wrap='off'>$carrierEmailDist</textarea>";
	}
	  ?>
      </table>
	  <?= $carrierEmailOut?>
	  </td>
      <td width="229">
	  <table width="100%" height="0" border="0" cellpadding="2" cellspacing="1">
		<?
		$int_email_arr = array();
		foreach ($sites_arr as $siteID)
		{
			$query = "SELECT s.siteID as siteid, i.email, i.FirstName, i.LastName, s.selected, i.id FROM internalEmailDist i, internalEmailDistSites s 
			where i.id=s.id order by i.LastName";
			$res = getResult($query);
			if (checkResult($res))
			{
				/*
					This logic builds an array of email addresses.  Since the same email address can be associated
					with mutiple sites, we make sure that the $selected value is 1, if it's 1 for any.
				*/
				while ($line = mysql_fetch_assoc($res))
				{
					extract($line);
					if (!empty($int_email_arr[$email]))
					{
						// this email is already in the array being built in this loop
						if ($selected == 1 && ($siteID == $siteid))
						{
							$int_email_arr[$email] = "$LastName,$FirstName,$selected,$id,$siteid"; 
						}
					}
					else
					{
						$selected = $siteid == $siteID ? $selected : 0;
						$int_email_arr[$email] = "$LastName,$FirstName,$selected,$id,$siteid";
					}
				}
			}
		}
		//showArray($int_email_arr);
		
		foreach ($int_email_arr as $email=>$info)
		{
			list($LastName, $FirstName, $selected, $id, $siteid) = explode(',', $info);

			if ($SHOW_INTERNAL != 'all' && $selected == 0)
			{
				continue;
			}		
				
				$email = strlen($email) > 20 ? substr($email, 0, 18) . '...' : $email;
				$chked = $selected == 1 ? 'CHECKED' : '';
				echo "\n<tr>
				  <td width='20' valign='top'><label>
					<input $chked name='site_" . $id . '_' . "$siteid' type='checkbox' id='site_" . $id . '_' . "$siteid' value='$siteid' />
					</label></td>
				  <td width='182'><div align='left'>$LastName, $FirstName <br>$email</div></td>
				  <td><a href='/deliveryEmailDist.php?emailAction=rem_int_email&id=$id'>remove</a></td>
				</tr>";
		}

		
		?>
      </table>
	  </td>
	</form>
    </tr>
    <tr>
      <td>&nbsp;</td>
      <td><?=$supmsg?>
		<form name="addSupplierForm" id="addSupplierForm" action="deliveryEmailDist.php" method="post" >
		<input type="hidden" id="deliveryID" name="deliveryID" value="<?=$deliveryID?>"  />
		<input type="hidden" id="supplierID" name="supplierID" value="<?=$deliverySupplierID?>"  />
		<input type="hidden" id="emailAction" name="emailAction" value="addSupplierEmail"  />
      <table width="100%" height="0" border="0" cellpadding="2" cellspacing="1">
        <tr>
          <td width="89" nowrap="nowrap" class="spinNormalText"><span class="style4">
		  
            <label>First Name </label>
            </span></td>
          <td width="117"><label>
            <input name="addSupplierFirstName" type="text" id="addSupplierFirstName" size="17" maxlength="50" />
            </label></td>
        </tr>
        <tr>
          <td nowrap="nowrap" class="spinNormalText"><span class="style4">Last Name </span></td>
          <td><input name="addSupplierLastName" type="text" id="addSupplierLastName" size="17" maxlength="50" /></td>
        </tr>
        <tr>
          <td nowrap="nowrap" class="spinNormalText"><span class="style4">Email</span></td>
          <td><input name="addSupplierEmail" type="text" id="addSupplierEmail" size="17" maxlength="100" /></td>
        </tr>
        <tr>
          <td colspan="2" class="spinNormalText"><div align="center" class="style4"><a href='javascript:addSupplierContact()'>Add Contact</a></div></td>
          </tr>
      </table>
	  </form>
	  </td>
      <td><?=$intmsg?><form name="addInternalForm" id="addInternalForm" action="deliveryEmailDist.php" method="post">
		<input type="hidden" id="InternalID" name="InternalID" value="<?=$deliveryInternalID?>"  />
		<input type="hidden" id="emailAction" name="emailAction" value="addInternalEmail"  />
      <table width="100%" height="0" border="0" cellpadding="2" cellspacing="1">
        <tr>
          <td width="89" nowrap="nowrap" class="spinNormalText"><span class="style4">
		  
            <label>First Name </label>
            </span></td>
          <td width="117"><label>
            <input name="addInternalFirstName" type="text" id="addInternalFirstName" size="17" maxlength="50" />
            </label></td>
        </tr>
        <tr>
          <td nowrap="nowrap" class="spinNormalText"><span class="style4">Last Name </span></td>
          <td><input name="addInternalLastName" type="text" id="addInternalLastName" size="17" maxlength="50" /></td>
        </tr>
        <tr>
          <td nowrap="nowrap" class="spinNormalText"><span class="style4">Email</span></td>
          <td><input name="addInternalEmail" type="text" id="addInternalEmail" size="17" maxlength="100" /></td>
        </tr>
        <tr>
          <td colspan="2" class="spinNormalText"><div align="center" class="style4"><a href='javascript:document.addInternalForm.submit()'>Add Contact</a></div></td>
          </tr>
      </table>
	  </form></td>
    </tr>
    <tr>
      <td colspan="3"><div align="center">
          <input name="cancel" type="button" onclick="window.close()" value="Cancel" />&nbsp;&nbsp;
		  <input name="postEmailForm" type="button"  value="Submit" onclick="document.emailForm.submit();" />
      </div></td>
    </tr>
  </table>
</div>
</body>
</html>
