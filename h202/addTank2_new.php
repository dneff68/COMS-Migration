<?
session_start();
$result= getResult("SHOW COLUMNS FROM monitor LIKE 'status'");
if( checkResult( $result ) )
{
	$line = mysql_fetch_assoc($result);
	extract($line);
	$Type = str_replace('enum(', '', $Type);
	$Type = str_replace(')', '', $Type);
	$Type = str_replace("'", '', $Type);
	$statusArr = explode(',', $Type);
}
?>

<script language="javascript">
function checkPage()
{
	// validate fields
	error = "";
	if (document.addTankForm.monitorID.value == "")
	{
		if (!document.addTankForm.nomonitor.checked)
			error += "\n--Please enter a Monitor ID";
	}
	if (document.addTankForm.tankName.value == "")
	{
		error += "\n--Please enter a Tank Name";
	}
	if (document.addTankForm.height.value == "")
	{
		error += "\n--Please enter a Height value";
	}
	if (document.addTankForm.diameter.value == "")
	{
		error += "\n--Please enter a Diameter value";
	}
	if (document.addTankForm.capacity.value == "")
	{
		error += "\n--Please enter a Capacity value";
	}
	if (document.addTankForm.volume.value == "")
	{
		error += "\n--Please enter a Volume value";
	}
	if (document.addTankForm.dosage.value == "")
	{
		error += "\n--Please enter a Dosage value";
	}
//	if (document.addTankForm.concentration.value == "")
//	{
//		if (document.addTankForm.prodID.value == 3)
//			error += "\n--Please enter a Concentration value";
//	}
	if (document.addTankForm.tolerence.value == "")
	{
		error += "\n--Please enter a Tolerence value";
	}

	if (error != "")
	{
		error = "Please correct the following problems:\n\n" + error;
		alert(error);
		return;
	}
	else
	{
		gotopage(3);
		return;
	}
}

function setCapacity()
{

	obj = document.addTankForm;

	h = obj.height.value;
	h2 = obj.height2.value;
	d = obj.diameter.value;
	d2 = obj.diameter2.value;
	
	if (parseInt(h2) > 0)
	{
		h = parseFloat(h + '.' + h2);
	}
	if (parseInt(d2) > 0)
	{
		d = parseFloat(d + '.' + d2);
	}
	
	
	/*
	Convert cubic inches to gallons, 1 gallon  = 231 cubic inches
	8635 cubic inches divided by 231  =  37.38 gallons
	*/
	r = (d/2);	
	a = (3.1416 * r * r);
	cap = (a * h) / 231;
	cap = Math.round(cap);
	obj.capacity.value=cap;
	
	divval = document.getElementById('capacityDiv');
	divval.innerHTML = "Capacity: " + cap + " gallons";
}

function setConcentration(val)
{
	return;
	
	if (val != '3')
	{
		document.addTankForm.concentration.value = '';
		document.addTankForm.concentration.disbled = true;
	}
	else
	{
		document.addTankForm.concentration.disbled = false;
	}
}

function monitorCheck(val)
{
	if (val)
	{
		document.addTankForm.monitorID.value = '';
		document.addTankForm.monitorID.disabled = true;
		//document.addTankForm.status.disabled = true;
		//document.addTankForm.units.disabled = true;
	}
	else
	{
		document.addTankForm.monitorID.disabled = false;
		//document.addTankForm.status.disabled = false;
		//document.addTankForm.units.disabled = false;
	}
}

</script>
<style type="text/css">
<!--
.style1 {font-weight: bold}
.style2 {
	font-size: 16px;
	font-weight: bold;
}
.style3 {font-size: 18px}
.style4 {FONT-FAMILY: Arial; font-weight: bold; color: #333333;}
-->
</style>

<?
	if ( empty($tankName) && !empty($editMonitor))
	{
		$query = "SELECT
				t.monitorID,
				m.status,
				m.units,
				t.tankName,
				t.height,
				t.diameter,
				t.multiple,
				t.orientation,
				t.usableVolume as volume,
				t.pumpCapacity,
				t.targetDosage as dosage,
				t.deviation_minus,
				t.deviation_plus,
				t.dosage_days,
				t.prodID,
				t.concentration,
				m.tolerance as tolerence
			FROM
				tank t, monitor m
			WHERE
				t.monitorID = m.monitorID AND
				t.monitorID = '$editMonitor'";
		$res = getResult($query);
//echoResults($res);
		if (checkResult($res))
		{
			$line = mysql_fetch_assoc($res);
			extract($line);
			
			session_register('originalUnits');
			session_register('originalStatus');
			$originalUnits = $units;
			$originalStatus = $status;
			
			$height = (string)$height;
			list($height, $height2) = explode('.', $height);

			$diameter = (string)$diameter;
			list($diameter, $diameter2) = explode('.', $diameter);

			$dosage = (string)$dosage;
			list($dosage, $dosage2) = explode('.', $dosage);

			$volume = (string)$volume;
			list($volume, $volume2) = explode('.', $volume);
			
			$pumpCapacity = (string)$pumpCapacity;
			list($pumpCapacity, $pumpCapacity2) = explode('.', $pumpCapacity);
			
			// get costPerGallon
			$costPerGallon = '0.00';
			$cpgRes = getResult("SELECT costPerGallon FROM costHistory WHERE monitorID='$editMonitor' ORDER BY date DESC LIMIT 1");
			if (checkResult($cpgRes))
			{
				$cpgLine = mysql_fetch_assoc($cpgRes);
				extract($cpgLine);
				$costPerGallon = (string)$costPerGallon;
			}
			list($costPerGallon, $costPerGallon2) = explode('.', $costPerGallon);
			
		}
		
}

?>

<form name="addTankForm" action="addTank.php" method="post">
<input type="hidden" name="addTankAction" value='' />
<input type="hidden" name="page" value='2' />
<input type="hidden" name="gotopage" value='' />
<input name="capacity" id="capacity" type="hidden" />
<input type="hidden" name="editMonitor" value='<?=$editMonitor?>' />

<? //showArray($ADDTANK2); ?>

<table width="700" border="1" align="center" cellpadding="5" cellspacing="1" class="spinTableBarOdd">
  <tr valign="top" class="spinMedTitle">
    <td colspan="5" class="spinTableTitle style3"><span class="style4">Step Two:</span> Tank Details </td>
  </tr>
  <tr>
    <td valign="middle" nowrap="nowrap" class="spinTableBarEven"><div align="left" class="style2">Monitor: 
      <label class="header_3"></label>
    </div></td>
    <td colspan="2" valign="middle" nowrap="nowrap" class="spinTableBarEven"><span class="header_3">
	<?
		if (strpos($monitorID, 'none') !== false || $nomonitor == 'none')
			$nomon = 'checked';
		else
			$nomon = '';
	?>
      <input name="nomonitor" id="nomonitor" type="checkbox" onclick="monitorCheck(this.checked)" value="none" <?=$nomon ?> />
unmonitored</span></td>
    <td colspan="2" valign="middle" nowrap="nowrap" class="spinTableBarEven">&nbsp;</td>
  </tr>
  <tr>
    <td width="171" valign="middle" nowrap="nowrap" class="spinTableTitle"><div align="right">Monitor ID </div></td>
    <td colspan="4"><label>
      <input name="monitorID" type="text" id="monitorID" size="10" maxlength="10" value='<?=$monitorID?>' />
    </label></td>
  </tr>
   <tr>
    <td colspan="5" valign="middle" nowrap="nowrap" class="spinTableBarEven"><div align="left" class="style2">Tank:</div></td>
    </tr>
 <tr>
    <td valign="middle" nowrap="nowrap" class="spinTableTitle"><div align="right">Status</div></td>
    <td colspan="4"><select name="status" id="status">
      <?
	  foreach ($statusArr as $statVal)
	  {
	  	$sel = $status == $statVal ? 'SELECTED' : '';
	  	echo "<option value=\"$statVal\" $sel>$statVal</option>\n";
	  }
	  ?>
    </select></td>
  </tr>
  <tr>
    <td valign="middle" nowrap="nowrap" class="spinTableTitle"><div align="right">Units</div></td>
    <td colspan="4"><label>
      <select name="units" id="units">
        <option value="Gallons" <?= $units == 'Gallons' ? 'SELECTED' : ''?> >Gallons</option>
        <option value="Inches" <?= $units == 'Inches' ? 'SELECTED' : ''?>>Inches</option>
      </select>
    </label></td>
  </tr>

  <tr>
    <td valign="middle" nowrap="nowrap" class="spinTableTitle"><div align="right">Tank Name  </div></td>
    <td colspan="4"><input name="tankName" type="text" id="tankName" value='<?=$tankName?>' size="30" maxlength="40" /></td>
  </tr>
  <tr>
    <td valign="middle" nowrap="nowrap" class="spinTableTitle"><div align="right">Height (inches) </div></td>
    <td colspan="2"><input   name="height" value='<?=$height?>' type="text" id="height" size="5" maxlength="5" onblur="setCapacity()" onkeypress="return numbersonly(this, event, 'height2')"/>
      <strong>.
      <input name="height2" value='<?=$height2?>' type="text" id="height2" size="2" maxlength="2" onkeypress="return numbersonly(this, event)" onblur="setCapacity()"/>
      </strong></td>
    <td colspan="2" rowspan="2"><div align="center"><span class="spinMedTitle"><div id="capacityDiv">&nbsp;</div></span></div></td>
  </tr>
  <tr>
    <td valign="middle" nowrap="nowrap" class="spinTableTitle"><div align="right">Diameter (inches) </div></td>
    <td colspan="2"><input   name="diameter" value='<?=$diameter?>' type="text" id="diameter" size="5" maxlength="5" onkeypress="return numbersonly(this, event, 'diameter2')"  onblur="setCapacity()"/>
      <strong>.
      <input name="diameter2" value='<?=$diameter2?>' type="text" id="diameter2" size="2" maxlength="2" onkeypress="return numbersonly(this, event)" onblur="setCapacity()"/>
      </strong></td>
    </tr>
  <tr>
    <td valign="middle" nowrap="nowrap" class="spinTableTitle"><div align="right">Multiple </div></td>
    <td colspan="4"><select name="multiple" id="multiple">
	  	<option value="1" <?=$multiple == '1' ? 'SELECTED' : ''?>>1</option>
	  	<option value="2" <?=$multiple == '2' ? 'SELECTED' : ''?>>2</option>
	  	<option value="3" <?=$multiple == '3' ? 'SELECTED' : ''?>>3</option>
	  	<option value="4" <?=$multiple == '4' ? 'SELECTED' : ''?>>4</option>
    </select></td>
  </tr>
  <tr>
    <td valign="middle" nowrap="nowrap" class="spinTableTitle"><div align="right">Orientation</div></td>
    <td colspan="4"><label>
      <select name="orientation" size="1" id="orientation">
        <option value="horizontal" <?= $orientation == 'horizontal' ? 'SELECTED' : ''?>>Horizontal</option>
        <option value="vertical" <?=empty($orientation) || $orientation == 'vertical' ? 'SELECTED' : ''?>>Vertical</option>
      </select>
    </label></td>
  </tr>
  <tr>
    <td valign="middle" nowrap="nowrap" class="spinTableTitle"><div align="right">Usable Volume </div></td>
    <td colspan="4"><input name="volume" value='<?=$volume?>' type="text" id="volume" size="5" maxlength="5" onkeypress="return numbersonly(this, event, 'volume2')"/>
      <strong>.
      <input name="volume2" value='<?=$volume2?>' type="text" id="volume2" size="2" maxlength="2" onkeypress="return numbersonly(this, event)"/>
      </strong></td>
  </tr>
  
  
<tr>
    <td valign="middle" nowrap="nowrap" class="spinTableTitle"><div align="right">Pump Capacity</div></td>
    <td colspan="4"><input name="pumpCapacity" value='<?=$pumpCapacity?>' type="text" id="pumpCapacity" size="5" maxlength="5" onkeypress="return numbersonly(this, event, 'pumpCapacity2')"/>
      <strong>.
      <input name="pumpCapacity2" value='<?=$pumpCapacity2?>' type="text" id="pumpCapacity2" size="2" maxlength="2" onkeypress="return numbersonly(this, event)"/>
      </strong></td>
</tr>
<tr>
    <td valign="middle" nowrap="nowrap" class="spinTableTitle"><div align="right">Cost Per Gallon </div></td>
    <td colspan="4"><input name="costPerGallon" value='<?=$costPerGallon?>' type="text" id="costPerGallon" size="5" maxlength="5" onkeypress="return numbersonly(this, event, 'costPerGallon2')"/>
      <strong>.
      <input name="costPerGallon2" value='<?=$costPerGallon2?>' type="text" id="costPerGallon2" size="2" maxlength="2" onkeypress="return numbersonly(this, event)"/>
      </strong></td>
</tr>    
  
  
  
  <tr>
  <?
        $deviation_plus = empty($deviation_plus) ? 0 : $deviation_plus;
        $deviation_minus = empty($deviation_minus) ? 0 : $deviation_minus;
  ?>
  <? if (empty($editMonitor)) : ?>
    <td rowspan="2" valign="middle" nowrap="nowrap" class="spinTableTitle"><div align="right">Dosage Criteria </div></td>
    <td width="42" rowspan="2">Target</td>
    <td width="168" rowspan="2"><input name="dosage" value='<?=empty($dosage) ? 0 : $dosage?>' type="text" id="dosage" size="5" maxlength="5" 
	onkeypress="return numbersonly(this, event, 'dosage2')"/>
    <input name="dosage2" type='hidden' value='0' id="dosage2" />
	<!--
      <strong>.
      <input name="dosage2" value='<?=$dosage2?>' type="text" id="dosage2" size="2" maxlength="2" onkeypress="return numbersonly(this, event)"/>
      </strong>
	 --> 
	  </td>
    <td width="105">Deviation + </td>
    <td width="146">
	<input   name="deviation_plus" value='<?=$deviation_plus?>' type="text" id="deviation_plus" size="5" maxlength="5" onblur="setCapacity()" onkeypress="return numbersonly(this, event, 'height2')"/>
	</td>
  </tr>
  <tr>
    <td>Deviation - </td>
    <td>
	<input   name="deviation_minus" value='<?=$deviation_minus?>' type="text" id="deviation_minus" size="5" maxlength="5" onblur="setCapacity()" onkeypress="return numbersonly(this, event, 'height2')"/>
	<input type='hidden' name="dosage_days" id='dosage_days' value="0" />
	</td>
  <? else : ?>
  <input type="hidden" name="dosage" value="0" id="dosage"  />
  <input type="hidden" name="dosage2" value="0" id="dosage2"  />
  <input type="hidden" name="deviation_plus" value='<?=$deviation_plus?>' id="deviation_plus"  />
  <input type="hidden" name="deviation_minus" value='<?=$deviation_minus?>' id="deviation_minus"  />
  <input type='hidden' name="dosage_days" id='dosage_days' value="0" />
  <? endif; ?>  
  </tr>
  <tr>
    <td valign="middle" nowrap="nowrap" class="spinTableTitle"><div align="right">Product </div></td>
    <td colspan="4">
<select name="prodID" id="prodID" onchange="setConcentration(this.value)">
      <?
	  $res = getResult("select prodID as prodid, value from product order by value desc");
	  while ($line = mysql_fetch_assoc($res))
	  {
	  	extract($line);
	  	$sel = $prodID == $prodid ? 'SELECTED' : '';
	  	echo "<option value=\"$prodid\" $sel>$value</option>\n";
	  }
	  ?>
    </select>	</td>
  </tr>
  <tr>
    <td valign="middle" nowrap="nowrap" class="spinTableTitle"><div align="right">Concentration</div></td>
    <td colspan="4"><!-- 
<input   name="concentration" value='<?=$concentration?>' type="text" id="concentration" size="3" maxlength="3" onkeypress="return numbersonly(this, event)"/>
 <strong>
      %</strong>
-->	
<?
?>
      <select name="concentration">
        <option value=''>---</option>
		<?= strpos($concentration, '%') === false ? "<option value='$concentration' 'SELECTED'>$concentration</option>" : '' ?>
        <option value="12%" <?= $concentration == '12%' ? 'SELECTED' : ''?>>12%</option>
        <option value="25%" <?= $concentration == '25%' ? 'SELECTED' : ''?>>25%</option>
        <option value="27% Standard" <?= $concentration == '27% Standard' ? 'SELECTED' : ''?>>27% Standard</option>
        <option value="28% FeCl2" <?= $concentration == '28% FeCl2' ? 'SELECTED' : ''?>>28% FeCl2</option>
        <option value="30% FeCl2" <?= $concentration == '30% FeCl2' ? 'SELECTED' : ''?>>30% FeCl2</option>
        <option value="32% FeCl2" <?= $concentration == '32% FeCl2' ? 'SELECTED' : ''?>>32% FeCl2</option>
        <option value="35% FeCl2" <?= $concentration == '35% FeCl2' ? 'SELECTED' : ''?>>35% FeCl2</option>
        <option value="35% ASG Valsterane" <?= $concentration == '35% ASG Valsterane' ? 'SELECTED' : ''?>>35% ASG Valsterane</option>
        <option value="35% NSF Grade" <?= $concentration == '35% NSF Grade' ? 'SELECTED' : ''?>>35% NSF Grade</option>
        <option value="35% Food Grade" <?= $concentration == '35% Food Grade' ? 'SELECTED' : ''?>>35% Food Grade</option>
        <option value="35% Standard" <?= $concentration == '35% Standard' ? 'SELECTED' : ''?>>35% Standard</option>
        <option value="35% Oxypure" <?= $concentration == '35% Oxypure' ? 'SELECTED' : ''?>>35% Oxypure</option>
        <option value="42% FeCl3" <?= $concentration == '42% FeCl3' ? 'SELECTED' : ''?>>42% FeCl3</option>
        <option value="50% B-Cap" <?= $concentration == '50% B-Cap' ? 'SELECTED' : ''?>>50% B-Cap</option>
        <option value="50% Food Grade" <?= $concentration == '50% Food Grade' ? 'SELECTED' : ''?>>50% Food Grade</option>
        <option value="50% Standard" <?= $concentration == '50% Standard' ? 'SELECTED' : ''?>>50% Standard</option>
        <option value="50% NSF Grade" <?= $concentration == '50% NSF Grade' ? 'SELECTED' : ''?>>50% NSF Grade</option>
        <option value="70% Standard" <?= $concentration == '70% Standard' ? 'SELECTED' : ''?>>70% Standard</option>
      </select>
</td>
  </tr>
  <tr>
    <td valign="middle" nowrap="nowrap" class="spinTableTitle"><div align="right">Tolerance </div></td>
    <td colspan="4"><input   name="tolerence" type="text" id="tolerence" value='<?=empty($tolerence) ? '10' : $tolerence?>' size="3" maxlength="2" onkeypress="return numbersonly(this, event)"/> 
      <strong>%</strong></td>
  </tr>
  
  <tr>
    <td valign="top" nowrap="nowrap" bordercolor="#999999" class="spinTableBarOdd"><a href='javascript:window.location="/index.php"'>Cancel</a></td>
    <td colspan="4" valign="top" nowrap="nowrap" bordercolor="#999999" class="spinTableBarOdd">
	<div align="center"><span class="spinNormalText"><a href='javascript:gotopage(1)'>Previous</a> &nbsp;&nbsp;
	<a href='javascript:checkPage()'>Continue to page 3 </a></span></div></td>
  </tr>
</table>
</form>
<p align="center" class="spinLargeTitle style1">&nbsp;</p>
<script language="javascript">
setCapacity();
monitorCheck(document.addTankForm.nomonitor.checked);
</script>
