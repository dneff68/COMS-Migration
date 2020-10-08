<script>
function checkPage()
{
	// validate fields
	error = "";
	if (document.addTankForm.region.value == "--none--")
	{
		error += "\n--Please select a region for the customer location to continue";
	}
	if (document.addTankForm.zipcode.value == "")
	{
		error += "\n--Please enter a zip code for the customer location to continue";
	}
	if (document.addTankForm.address.value == "")
	{
		error += "\n--Please enter the address for the customer location to continue";
	}
	if (document.addTankForm.city.value == "")
	{
		error += "\n--Please enter a city for the customer location to continue";
	}
	if (document.addTankForm.state.value == "")
	{
		error += "\n--Please choose a state for the customer location to continue";
	}
	if (document.addTankForm.contact.value == "")
	{
		error += "\n--Please provide a contact for the customer location to continue";
	}
	if (document.addTankForm.phone.value == "")
	{
		error += "\n--Please enter a contact phone for the customer location to continue";
	}
//	if (document.addTankForm.email.value == "")
//	{
//		error += "\n--Please enter an email address for the customer location to continue";
//	}

	if (error != "")
	{
		error = "Please correct the following problems:\n\n" + error;
		alert(error);
		return;
	}
	else
	{
		gotopage(2);
		return;
	}
}

</script>
<style type="text/css">
<!--
.style1 {font-size: 18px}
.style2 {FONT-FAMILY: Arial; font-weight: bold; color: #333333;}

-->
</style>

<?php

    $selCustomerSite = '';
    $siteID = '';
    $customerSite = '';
    $regionid = '';
    $region = '';
    $value = '';
    $zipcode = '';
    $address = '';
    $city = '';
    $contact = '';
    $phone = '';
    $email = '';

	if (empty($ADDTANK1) && !empty($editMonitor))
	{
		$query = "SELECT s.siteID as selCustomerSite, s.siteLocationName as customerSite, s.regionID, s.address, s.city, s.state, s.zip as zipcode, s.contact, s.contactPhone as phone, 
		s.contactEmail as email
		FROM tank t, monitor m, site s
		WHERE t.monitorID = m.monitorID AND m.siteID = s.siteID AND t.monitorID='$editMonitor'";
		$res = getResult($query);
		if (checkResult($res))
		{
			$line = $res->fetch_assoc();
			extract($line);
		}
	}
?>

<form name="addTankForm" action="addTank.php" method="post">
<input type="hidden" name="addTankAction" value='' />
<input type="hidden" name="page" value='1' />
<input type="hidden" name="gotopage" value='' />
<input type="hidden" name="editMonitor" value='<?php echo  $editMonitor?>' />


<table width="600px" border="1" align="center" cellpadding="5" cellspacing="1" class="spinTableBarOdd">
  <tr valign="top" class="spinMedTitle">
    <td colspan="3" class="spinTableTitle style1"><span class="style2">Step One:</span> Set Customer Site </td>
  </tr>
  <?php if (empty($editMonitor)) : ?>
  <tr>
    <td width="113" valign="top" nowrap="nowrap" class="spinTableTitle"><div align="right">Customer Site: </div></td>
    <td width="210" valign="top">    <div align="left">choose existing sites:<br />
          <select name="selCustomerSite" id="selCustomerSite" onchange="lookupSite(this.value)">
            <option value="--none--">--- choose customer site ---</option>
              <?php
// Site Information
// ----------------------------------	
	if (!empty($selCustomerSite) && $selCustomerSite != '--none--')
	{
		// set the city, state, etc variables
		$res = getResult("SELECT s.siteID, s.siteLocationName as 'customerSite', s.address, s.city, s.state, 
		s.regionID, s.zip as zipcode, s.contact, s.contactPhone as 'phone', s.contactEmail as 'email' 
		FROM site s, region r where s.regionID=r.regionID and siteID = $selCustomerSite");
		if (checkResult($res))
		{
			$line = $res->fetch_assoc();
			extract($line);
		}
	}
	else
	{
		$siteLocationName = '';
	}

	$clearNew = false;
	$res = getResult("SELECT siteID, siteLocationName FROM site order by siteLocationName");
	if (checkResult($res))
	{
		while ($line = $res->fetch_assoc())
		{
			extract($line);
			$sel = '';
			if ($siteID == $selCustomerSite)
			{
				$sel = 'selected';
				$clearNew = true;
			}
			
			echo("<option value=\"$siteID\" $sel>$siteLocationName</option>");
		}
	}
?>
              </select>
          <br>
      <br>
      </div>
        <p align="left" class="header_3">--or--</p>
      <div align="left"><br>
        create new customer site: <br />
        </div>      <label>
          <div align="left">
            <input name="customerSite" type="text" id="customerSite" size="35" maxlength="40" value="<?php echo $clearNew ? '' : stripslashes($customerSite)?>"
			onkeydown="clearSiteVals(this.value)" />
            </div>
        </label>    </td>
    <td width="235">Create a new customer site by typing a Customer Site Name, or choose an existing customer site from the dropdown list. </td>
  </tr>
  <? else: ?>
  <tr>
    <td valign="top" nowrap="nowrap" class="spinTableTitle"><div align="right">Customer:</div></td>
	  <td>
	  <input type='hidden' name="selCustomerSite" value="<?php echo $selCustomerSite?>" />
	  <input name="customerSite" type="text" id="customerSite" size="35" maxlength="40" value="<?php echo $customerSite ?>"  />
	  </td>
	  <td>&nbsp;</td>
  </tr>
  <?php endif; ?>
  <tr>
    <td valign="top" nowrap="nowrap" class="spinTableTitle"><div align="right">Region:</div></td>
    <td valign="top"><select name="region" id="region" <?php echo  $clearNew ? 'disabled="disabled"' : ''?>>
      <option value="--none--">--- choose region ---</option>
<?
	$res = getResult("SELECT regionID as regionid, value FROM region order by value");
	if (checkResult($res))
	{
		while ($line = $res->fetch_assoc())
		{
			extract($line);
			if (!empty($regionID))
				$sel = $regionID == $regionid ? 'selected' : '';
			else
				$sel = $region == $regionid ? 'selected' : '';
			echo("<option value=\"$regionid\" $sel>$value</option>");
		}
	}
?>
     </select></td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td valign="top" nowrap="nowrap" class="spinTableTitle"><div align="right">Zip Code:</div></td>
    <td valign="top"><div align="left">
      <input name="zipcode" type="text" id="zipcode" size="12" maxlength="12" value="<?php echo $zipcode?>" <?php echo  $clearNew ? 'disabled="disabled"' : ''?> onblur="lookupZip(this.value)"  />
    </div></td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td valign="top" nowrap="nowrap" class="spinTableTitle"><div align="right">Address:</div></td>
    <td valign="top"><input name="address" type="text" id="address" size="35" maxlength="40" value="<?php echo $address?>" <?php echo  $clearNew ? 'disabled="disabled"' : ''?>/></td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td valign="top" nowrap="nowrap" class="spinTableTitle"><div align="right">City:</div></td>
    <td valign="top"><input name="city" type="text" id="city" size="35" maxlength="40" value="<?php echo $city?>" <?php echo  $clearNew ? 'disabled="disabled"' : ''?>/></td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td valign="top" nowrap="nowrap" class="spinTableTitle"><div align="right">State:</div></td>
    <td valign="top"><div align="left"><select name="state" <?php echo  $clearNew ? 'disabled="disabled"' : ''?>><? include 'stateOptions.php' ?></select></div></td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td valign="top" nowrap="nowrap" class="spinTableTitle"><div align="right">Contact:</div></td>
    <td valign="top"><div align="left">
      <input name="contact" type="text" id="contact" size="35" maxlength="40" value="<?php echo $contact?>" <?php echo  $clearNew ? 'disabled="disabled"' : ''?> />
    </div></td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td valign="top" nowrap="nowrap" class="spinTableTitle"><div align="right">Contact Phone: </div></td>
    <td valign="top"><input name="phone" type="text" id="phone" size="15" maxlength="14" value="<?php echo $phone?>" <?php echo  $clearNew ? 'disabled="disabled"' : ''?>  onkeypress="return addPhone(this, event)"/></td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td valign="top" nowrap="nowrap" class="spinTableTitle"><div align="right">Contact Email: </div></td>
    <td valign="top"><input name="email" type="text" id="email" size="35" maxlength="40" value="<?php echo $email?>" <?php echo  $clearNew ? 'disabled="disabled"' : ''?> /></td>
    <td>&nbsp;</td>
  </tr>
<tr>
    <td colspan="2" valign="top" nowrap="nowrap" bordercolor="#999999" class="spinTableBarOdd"><a href='javascript:window.location="/index.php"'>Cancel</a></td>
    <td valign="top" nowrap="nowrap" bordercolor="#999999" class="spinTableBarOdd"><div align="center"><span class="spinNormalText">
	<a href='javascript:checkPage();'>Continue</a></span></div></td>
  </tr>  
</table>
</form>