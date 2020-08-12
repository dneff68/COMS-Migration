<?php
session_start();
include_once 'GlobalConfig.php';
include_once 'h202Functions.php';
include_once '../lib/chtFunctions.php';
include_once 'db_mysql.php';

error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors', 'On');

echo $SERVERNAME . " " . $DOCUMENT_ROOT;

if (david())
{
	echo "Hello David";	
}
else
{
	echo "Good day sir";
}

<?php if ($_SESSION['USERTYPE'] != 'customer'): ?>
	<table width="750" border="0" align="center" cellpadding="5" cellspacing="1">
  	<tr valign="middle" class="spinSmallTitle">    
    <td width="100%" valign="middle" nowrap="nowrap" colspan="2">
        <table width="381" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td width="64">Regions:</td>
            <td width="84"><input <?php echo strpos($_SESSION['USERID'], '1') !== false ? 'checked' : ''  ?> onchange="setRegion(this)" type="checkbox" name="reg_1" id="reg_1" />
              North</td>
            <td width="85"><input <?php echo strpos($_SESSION['USERID'], '3') !== false ? 'checked' : ''  ?> onchange="setRegion(this)" type="checkbox" name="reg_3" id="reg_3" /> 
            East
        </td>
            <td width="100"><input <?php echo strpos($_SESSION['USERID'], '5') !== false ? 'checked' : ''  ?> onchange="setRegion(this)" type="checkbox" name="reg_5" id="reg_5" />
            S. West</td>
          </tr>
          <tr>
            <td>&nbsp;</td>
            <td><input <?php echo strpos($_SESSION['USERID'], '2') !== false ? 'checked' : ''  ?> onchange="setRegion(this)" type="checkbox" name="reg_2" id="reg_2" /> 
              S. East
        </td>
            <td><input <?php echo strpos($_SESSION['USERID'], '4') !== false ? 'checked' : ''  ?> onchange="setRegion(this)" type="checkbox" name="reg_4" id="reg_4" /> 
            West
        </td>
            <td>&nbsp;<!-- <input type="checkbox" name="reg_all" id="reg_all" /> 
            All Regions --></td>
          </tr>
        </table>
    </td>
    <td width="53" rowspan="2" valign="top" nowrap="nowrap"><label></label></td>
    <td width="94" valign="middle" nowrap="nowrap"><div align="right">Action:
        
    </div></td>
    <td width="240" valign="top" nowrap="nowrap"><select name="selAction" id="selAction" onchange="doAction(this.value)">
      <option value="choose" selected="selected">-- Choose Action --</option>
      <option value="hideMap">Hide Map</option>
      <option value="newCustomerForm">New Customer Form</option>
      <option value="newCustomerList">New Customer List</option>
      <option value="<?php echo$_SESSION['SHOWINACTIVE']=='yes' ? 'hideInactive' : 'showInactive'?>"><?php echo$_SESSION['SHOWINACTIVE']=='yes' ? 'Hide Inactive Tanks' : 'Show Inactive Tanks'?></option>
      <option value="<?php echo$_SESSION['SHOWTEMPSHUTDOWN']=='yes' ? 'hideTempShutdown' : 'showTempShutdown'?>"><?php echo$_SESSION['SHOWTEMPSHUTDOWN']=='yes' ? 'Hide Temporary Shutdown' : 'Show Temporary Shutdown'?></option>
      <option value="<?php echo$_SESSION['SHOWUNMONITORED']=='yes' ? 'hideUnmonitored' : 'showUnmonitored'?>"><?php echo$_SESSION['SHOWUNMONITORED']=='yes' ? 'Hide Unmonitored Sites' : 'Show Unmonitored Sites'?></option>
      <option value="<?php echo$_SESSION['SHOWFACTORIES']=='yes' ? 'hideFactories' : 'showFactories'?>"><?php echo$_SESSION['SHOWFACTORIES']=='yes' ? 'Hide' : 'Show'?> Suppliers</option>
      <option value="<?php echo$_SESSION['SHOWCARRIERS']=='yes' ? 'hideCarriers' : 'showCarriers'?>"><?php echo$_SESSION['SHOWCARRIERS']=='yes' ? 'Hide' : 'Show'?> Carriers</option>
      <option value="<?php echo$_SESSION['SHOWTERMINALS']=='yes' ? 'hideTerminals' : 'showTerminals'?>"><?php echo$_SESSION['SHOWTERMINALS']=='yes' ? 'Hide' : 'Show'?> Terminals</option>
      <?php 
		  if ($_SESSION['USERTYPE'] == 'super')
		  {
			echo('<option value="addTank">Add Tank</option>');
		  }
	  ?>
      <option value="varReport">View Variance Report</option>
      <option value="anomalyReport">View Anomaly Report</option>
    </select></td>
  </tr>
  <tr valign="middle" class="spinSmallTitle">
    <td width="108" valign="middle" nowrap="nowrap">Status Filter: </td>
    <td nowrap="nowrap"><select name="status" class="spinNormalText" id="status" onchange="setStatusFilter(this.value)">
	<?php if ($_SESSION['VIEWMODE'] == 'statusView') : ?>	
	      <option value="all" <?php echo$_SESSION['STATUS_FILTER']=='all' ? 'Selected' : ''?>>All (<?php echo$allCnt?>)</option>
	      <option value="Normal" <?php echo$_SESSION['STATUS_FILTER']=='Normal' ? 'Selected' : ''?>>Normal (<?php echo$normalCnt?>)</option>
	      <option value="NoReading" <?php echo$_SESSION['STATUS_FILTER']=='NoReading' ? 'Selected' : ''?>>No Reading (<?php echo$nrCnt?>)</option>
	      <option value="ExceedCap" <?php echo$_SESSION['STATUS_FILTER']=='ExceedCap' ? 'Selected' : ''?>>Exceed Capacity (<?php echo$ecCnt?>)</option>
	      <option value="TempShutdown" <?php echo$_SESSION['STATUS_FILTER']=='TempShutdown' ? 'Selected' : ''?>>Temporary Shutdown (<?php echo$tsCnt?>)</option>
	      <option value="H_Dose" <?php echo$_SESSION['STATUS_FILTER']=='H_Dose' ? 'Selected' : ''?>>High Dose (<?php echo$HdoseCnt?>)</option>
	      <option value="L_Dose" <?php echo$_SESSION['STATUS_FILTER']=='L_Dose' ? 'Selected' : ''?>>Low Dose (<?php echo$LdoseCnt?>)</option>
	      <option value="unmon" <?php echo$_SESSION['STATUS_FILTER']=='unmon' ? 'Selected' : ''?>>Unmonitored Tanks (<?php echo$unmonCnt?>)</option>
	      <option value="unass" <?php echo$_SESSION['STATUS_FILTER']=='unass' ? 'Selected' : ''?>>Unassociated Readings (<?php echo$unassCnt?>)</option>
	<? else : ?>
	      <option value="all" <?php echo$_SESSION['STATUS_FILTER']=='all' ? 'Selected' : ''?>>All (<?php echo$allCnt?>)</option>
	      <option value="Ok" <?php echo$_SESSION['STATUS_FILTER']=='Ok' ? 'Selected' : ''?>>Ok (<?php echo$okCnt?>)</option>
	      <option value="Reorder" <?php echo$_SESSION['STATUS_FILTER']=='Reorder' ? 'Selected' : ''?>>Reorder (<?php echo$reorderCnt?>)</option>
	      <option value="Low" <?php echo$_SESSION['STATUS_FILTER']=='Low' ? 'Selected' : ''?>>Low (<?php echo$lowCnt?>)</option>
	      <option value="Critical" <?php echo$_SESSION['STATUS_FILTER']=='Critical' ? 'Selected' : ''?>>Critical (<?php echo$criticalCnt?>)</option>
	<?php endif ; ?>	  
	    </select>
	<?php if ($_SESSION['STATUS_FILTER'] == 'Reorder'): ?>
		<span class="spinNormalText">Lead Time:</span> <select name="leadTimeOverride" id="leadTimeOverride" onchange="doAction(this.value)">
	    <option value="lead_default" <?php echo $_SESSION['LEADTIME_OVERRIDE'] == 'default' ? 'Selected' : ''?>>-Default-</option>
	    <option value="lead_1" <?php echo $_SESSION['LEADTIME_OVERRIDE'] == '1' ? 'Selected' : ''?>>1</option>
	    <option value="lead_2" <?php echo $_SESSION['LEADTIME_OVERRIDE'] == '2' ? 'Selected' : ''?>>2</option>
	    <option value="lead_3" <?php echo $_SESSION['LEADTIME_OVERRIDE'] == '3' ? 'Selected' : ''?>>3</option>
	    <option value="lead_4" <?php echo $_SESSION['LEADTIME_OVERRIDE'] == '4' ? 'Selected' : ''?>>4</option>
	    <option value="lead_5" <?php echo $_SESSION['LEADTIME_OVERRIDE'] == '5' ? 'Selected' : ''?>>5</option>
	    <option value="lead_6" <?php echo $_SESSION['LEADTIME_OVERRIDE'] == '6' ? 'Selected' : ''?>>6</option>
	    <option value="lead_7" <?php echo $_SESSION['LEADTIME_OVERRIDE'] == '7' ? 'Selected' : ''?>>7</option>
	    <option value="lead_8" <?php echo $_SESSION['LEADTIME_OVERRIDE'] == '8' ? 'Selected' : ''?>>8</option>
	    <option value="lead_9" <?php echo $_SESSION['LEADTIME_OVERRIDE'] == '9' ? 'Selected' : ''?>>9</option>
	    <option value="lead_10" <?php echo $_SESSION['LEADTIME_OVERRIDE'] == '10' ? 'Selected' : ''?>>10</option>
	    <option value="lead_11" <?php echo $_SESSION['LEADTIME_OVERRIDE'] == '11' ? 'Selected' : ''?>>11</option>
	    <option value="lead_12" <?php echo $_SESSION['LEADTIME_OVERRIDE'] == '12' ? 'Selected' : ''?>>12</option>
	    <option value="lead_13" <?php echo $_SESSION['LEADTIME_OVERRIDE'] == '13' ? 'Selected' : ''?>>13</option>
	    <option value="lead_14" <?php echo $_SESSION['LEADTIME_OVERRIDE'] == '14' ? 'Selected' : ''?>>14</option>
	    <option value="lead_15" <?php echo $_SESSION['LEADTIME_OVERRIDE'] == '15' ? 'Selected' : ''?>>15</option>
	    <option value="lead_16" <?php echo $_SESSION['LEADTIME_OVERRIDE'] == '16' ? 'Selected' : ''?>>16</option>
	    <option value="lead_17" <?php echo $_SESSION['LEADTIME_OVERRIDE'] == '17' ? 'Selected' : ''?>>17</option>
	    <option value="lead_18" <?php echo $_SESSION['LEADTIME_OVERRIDE'] == '18' ? 'Selected' : ''?>>18</option>
	    <option value="lead_19" <?php echo $_SESSION['LEADTIME_OVERRIDE'] == '19' ? 'Selected' : ''?>>19</option>
	    <option value="lead_20" <?php echo $_SESSION['LEADTIME_OVERRIDE'] == '20' ? 'Selected' : ''?>>20</option>
	    </select>
	<?php endif; ?>    
    
    </td>
    <td width="94" align="right" valign="middle" nowrap="nowrap" class="spinSmallTitle">View Mode:  </td>
    <td width="240" align="right" valign="middle" nowrap="nowrap" class="spinSmallTitle">
      <div align="left">
        <input name="rdoStatus" type="radio" value="statusView" <?php echo$_SESSION['VIEWMODE'] == 'statusView' ? 'checked' : ''?> onclick="doAction('statusView')" />
      Status&nbsp;<input name="rdoStatus" type="radio" value="deliveryView" <?php echo$_SESSION['VIEWMODE'] == 'deliveryView' ? 'checked' : ''?>  onclick="doAction('deliveryView')"/>
        Deliveries </div>
    </div></td>
  	</tr>
	</table>
<?php endif; ?>
<center>


<?php if ($_COOKIE['mapVisible'] == 1): ?>
	<iframe frameborder="0" align="top" name="mapFrame" id="mapFrame" width="750" height=440 src="map.php" style="border-style:ridge"></iframe><br />
<? else: ?>
	<iframe frameborder="0" align="top" name="mapFrame" id="mapFrame" width="750" height=0  style="border-style:none"></iframe><br />
<?php endif; ?>


die;
?>