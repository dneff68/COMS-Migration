<script language="javascript">
function postTank()
{
	setDeliveryTime();
	document.addTankForm.addTankAction.value='addtank';
	document.addTankForm.submit();
}

function checkPage()
{
	// validate fields
	error = "";
	if (document.addTankForm.supplier.value == "--none--")
	{
		error += "\n--Please select a supplier to continue";
	}

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

function setDeliveryTime()
{
	obj = document.addTankForm;
	obj.timeOfDelivery.value = obj.hr.value + ':' + obj.mn.value + ' ' + obj.ampm.value;
}

</script>
<style type="text/css">
<!--
.style1 {font-size: 12}
.style2 {font-size: 18px}
.style3 {FONT-FAMILY: Arial; font-weight: bold; color: #333333;}
.style4 {font-size: 18}
-->
</style>



<form name="addTankForm" action="addTank.php" method="post">
<input type="hidden" name="addTankAction" value='' />
<input type="hidden" name="page" value='3' />
<input type="hidden" name="gotopage" value='' />
<input type="hidden" name="editMonitor" value='<?=$editMonitor?>' />
<?
	if ( empty($reorder) && !empty($editMonitor))
	{
		$query = "SELECT capacity FROM truck WHERE tankID='$editMonitor' ORDER BY capacity";
		$res = getResult($query);
		$truckCapacities = '';
		if (checkResult($res))
		{
			while ($line = mysql_fetch_assoc($res))
			{
				extract($line);
				$truckCapacities .= ",$capacity";
			}
			$truckCapacities = empty($truckCapacities) ? '' : substr($truckCapacities, 1); // strip leading comma
		}
		
		$query = "SELECT c.carrierID as carrier, c.carrierName, c.contact as carrierContact, 
				c.phone as carrierPhone, c.email as carrierEmail
				FROM 
					tank t, carrier c
				WHERE
					t.monitorID='$editMonitor' AND
					t.carrierID = c.carrierID";
		$res = getResult($query);
		if (checkResult($res))
		{
			$line = mysql_fetch_assoc($res);
			extract($line);
		}
			
		$query = "SELECT
			s.PO_code, sup.supplierID as supplier, sup.supplierName, sup.contact as supplierContact, 
			sup.email as supplierEmail, sup.phone as supplierPhone, t.timeOfDelivery, t.leadTime,
			t.reorder, t.low, t.critical, t.notes
			FROM 
				tank t, monitor m, supplier sup, site s
			WHERE
				t.monitorID = '$editMonitor' AND
				t.supplierID = sup.supplierID AND
				t.monitorID = m.monitorID AND
				m.siteID = s.siteID";
		$res = getResult($query);
		if (checkResult($res))
		{
			$line = mysql_fetch_assoc($res);
			extract($line);
		}

		// get costPerGallon
		$costPerGallon = '0.00';
		$cpgRes = getResult("SELECT costPerGallon FROM costHistory WHERE monitorID='$editMonitor' AND costPerGallon > 0 ORDER BY date DESC LIMIT 1");
		if (checkResult($cpgRes))
		{
			$cpgLine = mysql_fetch_assoc($cpgRes);
			extract($cpgLine);
			$costPerGallon = (string)$costPerGallon;
		}
		list($costPerGallon, $costPerGallon2) = explode('.', $costPerGallon);

		
	}

//showArray($ADDTANK3);
?>

<table width="700" border="1" align="center" cellpadding="5" cellspacing="1" class="spinTableBarOdd">
  <tr valign="top" class="spinMedTitle">
    <td colspan="3" class="spinTableTitle style2"><span class="style3">Step Three:</span> Tank Supplier </td>
  </tr>
  <tr class="spinTableBarEven">
    <td colspan="3" valign="top" nowrap="nowrap" class="style2">Supplier</td>
    </tr>
  <tr>
    <td valign="top" nowrap="nowrap" class="spinTableTitle"><div align="right">PO Code : </div></td>
    <td class="spinSmallTitle"><label>
      <input name="PO_code" type="text" id="PO_code" size="7" maxlength="6" value="<?=$PO_code?>"/>
    </label></td>
    <td>
		Cost per Gal: <input style="text-align:right; width:15px" name="costPerGallon" type="text" id="costPerGallon" onkeypress="return numbersonly(this, event, 'costPerGallon2')" value='<?=$costPerGallon?>' size="1" maxlength="2"/>.
			<input name="costPerGallon2" value='<?=$costPerGallon2?>' type="text" id="costPerGallon2" size="2" maxlength="2" onkeypress="return numbersonly(this, event)"/>

    
    </td>
  </tr>
  <tr>
    <td width="201" valign="top" nowrap="nowrap" class="spinTableTitle"><div align="right">Choose Supplier: </div></td>
    <td width="282" class="spinSmallTitle">
    <div align="left" class="style4"> existing supplier:<br />
      <select name="supplier" id="supplier" onchange="lookupSupplier()">
        <option value="--none--">--- choose supplier ---</option>
        <?
			$clearNew = false;
			$res = getResult("SELECT supplierName as suppliername, supplierID as t_supplier FROM supplier order by supplierName");
			if (checkResult($res))
			{
				while ($line = mysql_fetch_assoc($res))
				{
					extract($line);
					$sel = '';
					if ($t_supplier == $supplier)
					{
						$clearNew=true;
						$sel = 'selected';
					}
					echo("\n<option value=\"$t_supplier\" $sel>$suppliername</option>");
				}
			}
?>
        </select>
      <br>
      <br>
    </div>
      <p align="left" class="header_3 style4">--or--</p>
      <div align="left" class="style4"><br>
        add a new supplier: <br />
      </div>
      <span class="style4"><span class="style1">
      <label>
        </span>        </span>
      <div align="left" class="style4">	  
          <input name="supplierName" type="text" id="supplierName" size="35" maxlength="40" value="<?= $clearNew ? '' : stripslashes($supplierName)?>" onblur="clearSupplierVals(this.value)" />
        </div>        
      <span class="style4">
        </label>    
      </span>	</td>
    <td width="175">Create a new <strong>supplier</strong> by typing a Supplier Name, or choose an existing supplier from the dropdown list. </td>
  </tr>
  <?
	if (!empty($supplier) && $supplier != '--none--')
	{
		$query = "select supplierID, supplierName, contact as supplierContact, email as supplierEmail, phone as supplierPhone from supplier where supplierID=$supplier LIMIT 1";
		$res = getResult($query);
		if (checkResult($res))
		{
			$line = mysql_fetch_assoc($res);
			extract($line);
		}
	} 
  ?>
  <tr>
    <td valign="top" nowrap="nowrap" class="spinTableTitle"><div align="right">Contact:</div></td>
    <td colspan="2"><input name="supplierContact" type="text" id="supplierContact" size="35" maxlength="40" value="<?=$supplierContact?>" <?= $clearNew ? 'disabled="disabled"' : ''?> /></td>
  </tr>
  <tr>
    <td valign="top" nowrap="nowrap" class="spinTableTitle"><div align="right"><span class="style1">Phone:</span></div></td>
    <td colspan="2"><input name="supplierPhone" type="text" id="supplierPhone" size="15" maxlength="14" value="<?=$supplierPhone?>"  <?= $clearNew ? 'disabled="disabled"' : ''?>  onkeypress="return addPhone(this, event)"/></td>
  </tr>

   <tr>
    <td valign="top" nowrap="nowrap" class="spinTableTitle"><div align="right"><span class="style1">Email:</span></div></td>
    <td colspan="2"><input name="supplierEmail" type="text" id="supplierEmail" size="35" maxlength="40" value="<?=$supplierEmail?>" <?= $clearNew ? 'disabled="disabled"' : ''?> /></td>
  </tr>


  <tr class="spinTableBarEven">
    <td colspan="3" valign="top" nowrap="nowrap" class="style2">Trucks / Carrier</td>
    </tr>
  <tr>
    <td valign="middle" nowrap="nowrap" class="spinTableTitle"><div align="right">Truck Capacities: </div></td>
    <td class="header_3">Enter each truck capacity separated by a comma: <br />
      <label>
      <input name="truckCapacities" value="<?=$truckCapacities?>" type="text" size="40" maxlength="60" onkeypress="return numbersonly(this, event, '', ',')" />
      </label></td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td width="201" valign="top" nowrap="nowrap" class="spinTableTitle"><div align="right">Choose Carrier (optional): </div></td>
    <td width="282" class="spinSmallTitle">
    <div align="left" class="style4"> existing carrier:<br />
      <select name="carrier" id="carrier" onchange="lookupCarrier(this.value)">
        <option value="--none--">--- no carrier ---</option>
        <?
			$clearNew = false;
			$res = getResult("SELECT carrierName as carriername, carrierID as t_carrier FROM carrier order by carrierName");
			if (checkResult($res))
			{
				while ($line = mysql_fetch_assoc($res))
				{
					extract($line);
					$sel = '';
					if ($t_carrier == $carrier)
					{
						$clearNew=true;
						$sel = 'selected';
					}
					echo("\n<option value=\"$t_carrier\" $sel>$carriername</option>");
				}
			}
?>
        </select>
      <br>
      <br>
    </div>
      <p align="left" class="header_3 style4">--or--</p>
      <div align="left" class="style4"><br>
        add a new carrier: <br />
      </div>
      <span class="style4"><span class="style1">
      <label>
        </span>        </span>
      <div align="left" class="style4">	  
          <input name="carrierName" type="text" id="carrierName" size="35" maxlength="40" value="<?= $clearNew ? '' : stripslashes($carrierName)?>" 
		  onblur="clearCarrierVals(this.value)" />
        </div>        
      <span class="style4">
        </label>    
      </span>	</td>
    <td width="175">Create a new <strong>carrier</strong> by typing a Carrier Name, or choose an existing carrier from the dropdown list. </td>
  </tr>
  <? 
	if ($carrier != '--none--')
	{
		if (!empty($carrier))
		{
			$query = "select carrierID, carrierName, contact as carrierContact, email as carrierEmail, phone as carrierPhone from carrier where carrierID=$carrier LIMIT 1";
			$res = getResult($query);
			if (checkResult($res))
			{
				$line = mysql_fetch_assoc($res);
				extract($line);
			}
		}
	} 
  ?>
  <tr>
    <td valign="top" nowrap="nowrap" class="spinTableTitle"><div align="right">Contact:</div></td>
    <td colspan="2"><input name="carrierContact" type="text" id="carrierContact" size="35" maxlength="40" value="<?=$carrierContact?>" <?= $clearNew ? 'disabled="disabled"' : ''?> /></td>
  </tr>
  <tr>
    <td valign="top" nowrap="nowrap" class="spinTableTitle"><div align="right"><span class="style1">Phone:</span></div></td>
    <td colspan="2"><input name="carrierPhone" type="text" id="carrierPhone" size="15" maxlength="14" value="<?=$carrierPhone?>"  <?= $clearNew ? 'disabled="disabled"' : ''?>  onkeypress="return addPhone(this, event)"/></td>
  </tr>

   <tr>
    <td valign="top" nowrap="nowrap" class="spinTableTitle"><div align="right"><span class="style1">Email:</span></div></td>
    <td colspan="2"><input name="carrierEmail" type="text" id="carrierEmail" size="35" maxlength="40" value="<?=$carrierEmail?>" <?= $clearNew ? 'disabled="disabled"' : ''?> /></td>
  </tr>




    <tr class="spinTableBarEven">
      <td colspan="3" valign="top" nowrap="nowrap" class="style2">Tank / Supplier Details </td>
    </tr>
    <tr>
    <td valign="top" nowrap="nowrap" class="spinTableTitle"><div align="right" class="style1">Time of Delivery: </div></td>
    <td colspan="2"><span class="style1">
      <input name="timeOfDelivery" type="hidden" id="timeOfDelivery" value="<?=$timeOfDelivery?>" />
      <label>
<?
	if (!empty($timeOfDelivery))
	{
		list($hrmn, $ampm) = explode(' ', $timeOfDelivery);
		list($hr, $mn) = explode(':', $hrmn);
	}
?>	  
	  
      <select name="hr" onchange="setDeliveryTime()">
        <option value="01" <?=$hr=='01' ? 'SELECTED' : ''?>>01</option>
        <option value="02" <?=$hr=='02' ? 'SELECTED' : ''?>>02</option>
        <option value="03" <?=$hr=='03' ? 'SELECTED' : ''?>>03</option>
        <option value="04" <?=$hr=='04' ? 'SELECTED' : ''?>>04</option>
        <option value="05" <?=$hr=='05' ? 'SELECTED' : ''?>>05</option>
        <option value="06" <?=$hr=='06' ? 'SELECTED' : ''?>>06</option>
        <option value="07" <?=$hr=='07' ? 'SELECTED' : ''?>>07</option>
        <option value="08" <?=empty($hr) || $hr=='08' ? 'SELECTED' : ''?>>08</option>
        <option value="09" <?=$hr=='09' ? 'SELECTED' : ''?>>09</option>
        <option value="10" <?=$hr=='10' ? 'SELECTED' : ''?>>10</option>
        <option value="11" <?=$hr=='11' ? 'SELECTED' : ''?>>11</option>
        <option value="12" <?=$hr=='12' ? 'SELECTED' : ''?>>12</option>
      </select>
      </label>
      <strong>:</strong>
      <select name="mn" onchange="setDeliveryTime()">
      <option value="00" <?=$mn=='00' ? 'SELECTED' : ''?>>00</option>
      <option value="15" <?=$mn=='15' ? 'SELECTED' : ''?>>15</option>
      <option value="30" <?=$mn=='30' ? 'SELECTED' : ''?>>30</option>
      <option value="45" <?=$mn=='45' ? 'SELECTED' : ''?>>45</option>
        </select> 
    <select name="ampm" onchange="setDeliveryTime()">
      <option value="am" <?=empty($ampm) || $ampm=='am' ? 'SELECTED' : ''?>>am</option>
      <option value="pm" <?=$ampm=='pm' ? 'SELECTED' : ''?>>pm</option>
        </select>
    </span></td>
  </tr>
  <tr>
    <td valign="top" nowrap="nowrap" class="spinTableTitle"><div align="right" class="style1">Lead Time (days): </div></td>
    <td colspan="2"><span class="style1">
      <input name="leadTime" type="text" id="leadTime" size="3" value="<?=empty($leadTime) ? 10 : $leadTime?>" maxlength="2" onkeypress="return numbersonly(this, event)" />
    </span></td>
  </tr>
  <tr>
    <td valign="top" nowrap="nowrap" class="spinTableTitle"><div align="right" class="style1">Reorder:</div></td>
    <td colspan="2"><input name="reorder" type="text" class="style1" id="reorder" value='<?= empty($reorder) ? '30' : $reorder?>' size="3" maxlength="2" onkeypress="return numbersonly(this, event)" /> 
      <span class="spinSmallTitle">%</span> </td>
  </tr>
  <tr>
    <td valign="top" nowrap="nowrap" class="spinTableTitle"><div align="right" class="style1">Low Point </div></td>
    <td colspan="2"><input name="low" type="text" class="style1" id="low" value='<?= empty($low) ? '20' : $low?>' size="3" maxlength="2" onkeypress="return numbersonly(this, event)" />
	<span class="spinSmallTitle">%</span></td>
  </tr>
  <tr>
    <td valign="top" nowrap="nowrap" class="spinTableTitle"><div align="right" class="style1">Critical Point </div></td>
    <td colspan="2"><input name="critical" type="text" class="style1" id="critical" value='<?= empty($critical) ? '10' : $critical?>' size="3" maxlength="2" onkeypress="return numbersonly(this, event)" />
	<span class="spinSmallTitle">%</span></td>
  </tr>
  <tr>
    <td valign="top" nowrap="nowrap" class="spinTableTitle"><div align="right" class="style1">Notes:</div></td>
    <td colspan="2"><span class="spinSmallTitle style1">
      <textarea name="notes" cols="40" rows="5" id="notes"><?=$notes?></textarea>
    </span></td>
  </tr>
<tr>
    <td valign="top" nowrap="nowrap" bordercolor="#999999" class="spinTableBarOdd"><a href='javascript:window.location="/index.php"'>Cancel</a></td>
    <td colspan="2" valign="top" nowrap="nowrap" bordercolor="#999999" class="spinTableBarOdd"><div align="center"><span class="spinNormalText">
	<a href='javascript:gotopage(2)'>Previous</a> &nbsp;&nbsp;<a href='javascript:postTank();'>Submit Tank</a></span></div></td>
  </tr>  
</table>
</form>
<script language="javascript">
//setDeliveryTime();
</script>