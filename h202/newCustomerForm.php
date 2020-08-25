<?php
session_start();
if ($_SESSION['LOCAL_DEVELOPMENT']=='yes')
{
	include_once 'GlobalConfig.php';
	include_once 'h202Functions.php';
	include_once '../lib/db_mysql.php';
	include_once '../lib/chtFunctions.php';	
}
else
{
	die("NOT LOCAL DEVELOPMENT: multiTankDetails: 13");
	include_once '/var/www/html/CHT/h202/GlobalConfig.php';
	include_once '/var/www/html/CHT/h202/h202Functions.php';
	include_once 'chtFunctions.php';
	include_once 'db_mysql.php';
}


if (david())
{
	error_reporting(E_PARSE | E_ERROR); 
	ini_set("display_errors", 1); 		
}

if (isset($_GET['st']))
{
	$st = $_GET['st'];
}
//if (!empty($key))
if (isset($_GET['key']))
{
	// if (!isset( $_SESSION['KEY_CODE'] ))
	// {
	// 	session_register('KEY_CODE');
	// }
	$key = $_GET['key'];
	$_SESSION['KEY_CODE'] = $key;
	
	$query = "SELECT DATE_FORMAT(creationDate, '%m/%d/%Y')  as update_date, committed, complete FROM newCustomerForm WHERE keyCode='$key' LIMIT 1";
	$res = getResult($query);
	if (checkResult($res))
	{
		$line = $res->fetch_assoc();
		extract($line);
	}
	else
	{
		$query = "SELECT DATE_FORMAT(NOW(), '%m/%d/%Y') as update_date";
		$res = getResult($query);
		$line = $res->fetch_assoc();
		extract($line);
	}
}
else
{
//	session_register('KEY_CODE');
	$_SESSION['KEY_CODE'] = generateCode();
}

$_SESSION['CURRENT_PAGE'] = 1;

?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>COMS - New Customer Form</title>
<link rel="stylesheet" TYPE="text/css" href="http://h202.customhostingtools.com/main.css" >
<link rel="stylesheet" TYPE="text/css" href="planning.css" >
<SCRIPT LANGUAGE="javascript" TYPE="text/javascript" SRC='http://www.customhostingtools.com/lib/helper.js'></SCRIPT>
<SCRIPT LANGUAGE="javascript" TYPE="text/javascript" SRC='http://www.customhostingtools.com/lib/admin.js'></SCRIPT>
<SCRIPT LANGUAGE="javascript" TYPE="text/javascript" SRC='http://www.customhostingtools.com/lib/newCustomer.js'></SCRIPT>
<script src="http://www.customhostingtools.com/lib/jquery.js" type="text/javascript"></script>
<link rel="stylesheet" href="/ui_theme/themes/base/jquery.ui.all.css">
<script src="/ui_theme/ui/jquery.ui.core.js"></script>
<script src="/ui_theme/ui/jquery.ui.widget.js"></script>
<script src="/ui_theme/ui/jquery.ui.datepicker.js"></script>
<script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js"></script>
<script language="javascript" type="text/javascript">
var validate = false;


$(document).ready(function() {
	
<?php	if (!isset($_SESSION["USERID"]) && $st != 1) : ?>

		$("#thank_you").html("<h3>Your Session Has Expired</h3><br /><br />Please login to COMS to add or edit a customer form.");
		$("#section_1").hide();
		$("#section_2").hide();
		$("#section_3").hide();
		$("#thank_you").show();
		return;
<?php endif; ?>
	
	<?php if (empty($key)) : ?>
	window.onbeforeunload = function() 
	{
		if (st > 1) // check for read only state
		{
			if ( $("#thank_you").css('display') == 'none' )
			{
				$.ajaxSetup({async:false});
				processSection(1, '');
				processSection(2, '');
				processSection(3, '');
				return "This form is incomplete.  Reloading this page starts a brand new customer form.\n\nTo continue editing this form at a later time you must open it from the 'New Customer List'";
			}
		}
	};
	<?php elseif ($st != 1): ?>
	window.onunload = function() 
	{
		if ( $("#thank_you").css('display') == 'none' )
		{
			$.ajaxSetup({async:false});
			processSection(1, '');
			processSection(2, '');
			processSection(3, '');
		}
		return -1;
	}
	<?php endif;?>		
	
	var st=<?php echo isset($st) ? 3 : $st?>;
	
	if (st==1) // read only
	{
		$(".navDiv").hide();
		$(".postDiv").hide();
		$(".mapDiv").hide();
		
		$("#section_1").show();
		$("#section_2").show();
		$("#section_3").show();
		$("#thank_you").hide();
	}
//	else if (st==2) // edit existing
//	{
//		$(".navDiv").hide();
//		$("#section_1").show();
//		$("#section_2").show();
//		$("#section_3").show();
//		$("#thank_you").hide();
//	}
	else if (st==3 || st==2) // 3 page create/edit
	{
		$("#section_1").show();
		$("#section_2").hide();
		$("#section_3").hide();
		$("#thank_you").hide();
	}
	else if (st==4) // post to COMS New Tank
	{
		validate = true;
	}
	
	$(function() {
		$( "#passivated_date" ).datepicker();
	});
	
	$("#dialog").dialog(
		{ 
			width: 500,
			height: 300,
			autoOpen: false 
		}
	);
	
	$(function() {
		var dates = $( "#install_date, #start_date" ).datepicker({
			defaultDate: "+1w",
			changeMonth: true,
			numberOfMonths: 1,
			showButtonPanel: true,
			dateFormat: 'yy-mm-dd',
			onSelect: function( selectedDate ) {
				var option = this.id == "install_date" ? "minDate" : "maxDate",
					instance = $( this ).data( "datepicker" ),
					date = $.datepicker.parseDate(
						instance.settings.dateFormat ||
						$.datepicker._defaults.dateFormat,
						selectedDate, instance.settings );
				dates.not( this ).datepicker( "option", option, date );
			}
		});
	});	
	
	getSection( 1 );
	getSection( 2 );
	getSection( 3 );
	
	
	if (st == 1) // disable all controls
	{
		$('* :input').attr('readonly', true);
		$('*').filter("select").attr('disabled', 'disabled');
	}
	

});
	
	
	</script>
<script type="text/javascript">
<?php include_once "newCustomer.php"; ?>
</script>
<style>
.newCustomerDiv {
	width:830px;
	padding:5px;
	font-family: Arial, Helvetica, sans-serif;
	color: #333;
	font-size: 14px;
	vertical-align:text-bottom;
	background-color: #A5C0DB;
}
#formHeader {
	padding-top:10px;
}
.sectionHeader1 {
	text-align: right;
	color: #810381;
	border-bottom-color: #810381;
}
.sectionHeader2 {
	text-align: right;
	color: #063;
	border-bottom-color: #063;
}
.sectionHeader3 {
	text-align: right;
	color: #009;
	border-bottom-color: #009;
}
.sectionBody {
	background-color: #A5C0DB;
}
.newCustomerDiv .sectionHeader1, .sectionHeader2, .sectionHeader3, .sectionBody {
	padding-top:10px;
	padding-right:5px;
	background-color:inherit;
}
.newCustomerDiv .sectionHeader1, .sectionHeader2, .sectionHeader3 {
	margin-bottom:15px;
	text-align:right;
	font-size:larger;
	border-bottom-style: solid;
}
.newCustomerDiv .sectionBlockContainer {
	width:820px;
	padding-left:10px;
	border-style:solid;
	border-width:thin;
	border-color: #009;
}
.sectionBlock {
	background-color:inherit;
}
.sectionBlockTitle {
	color: #4d67a0;
	font-weight:bold;
	font-size:16px;
	padding-top:18px;
}
.label-field-right {
	float:right;
	padding-top:5px;
}
.label-field-left {
	float:left;
	padding-top:5px;
	background-color: #A5C0DB;
}
.label-field-right .label {
	text-align: right;
	width:150px;
	float:left;
	padding-right: 10px;
	font-weight: bolder;
}
.label-field-right .field {
	width:150px;
	float:right;
	text-align:left;
	padding-left:5px;
}
.label-field-left .label {
	text-align: right;
	width:200px;
	float:left;
	padding-right: 3px;
	font-weight: bolder;
}
.label-field-left .5col {
	width:200px;
}
.label-field-left .field {
	width:150px;
	float:left;
	text-align:left;
	padding-left:5px;
}
.field input {
	margin-top:-2px;
}
.field select {
	margin-top:-5px;
}
.reducedTableCell {
	font-size:smaller;
	font-weight:700;
}
.required {
	background-color:#F6F5B0;
}
.requiredHighlighted {
	background-color:#FF5F67;
}
</style>
</head>

<body style="width:850px; padding-left:15px">
<?php if ($st==1): ?>
<p style="width:850px; text-align:right">
<a href='javascript:window.print()'>print</a>
</p>
<?php endif; ?>

<div id="sectionIdentifier" name="sectionIdentifier">
  <input type="hidden" name="sectionID" id="sectionID" value="1">
</div>
<div id='formHeader' class="customerBanner customerBannerText" style="padding-left:10px; background-color:#4d67a0"> <img src="images/logo-us-peroxide.gif">&nbsp;&nbsp;US Peroxide New Customer Form </div>
<div id='all_sections' style="background-color:#A5C0DB; padding-left:15px"> 
  <!-- SECTION 1 START -->
  <div id='section_1' class='newCustomerDiv'  style="display:inline">
    <div class='sectionHeader1'>Section 1 - Account Information (BD/Sales)</div>
    <div class='sectionBody'>
      <div class='label-field-right'>
        <div class='label'>Updated By:</div>
        <div class='field' style="width:150px">
          <?php echo $USERID?>
          <input name="updated_by" type="hidden" id="updated_by" value="<?php echo $USERID?>">
        </div>
      </div>
      <div class='label-field-right'>
        <div class='label'>Date Updated:</div>
        <div class='field' style="width:150px">
          <?php echo $update_date ?>
        </div>
      </div>
      <div style="clear:both"></div>
      
      <!-- CUSTOMER INFORMATION BLOCK -->
      <div class='sectionBlock'>
        <div class='sectionBlockTitle'>Customer Information</div>
      </div>
      <div class='sectionBlockContainer'>
        <div class='label-field-left'>
          <div class='label'>Customer Name (formal):</div>
          <div class='field'>
            <input name="customer_name_formal" type="text" class="field required" id="customer_name_formal">
          </div>
          <div style="clear:both"></div>
        </div>
        <div class='label-field-left'>
          <div class='label'>Customer Name (informal):</div>
          <div class='field'>
            <input name="customer_name_informal" type="text" class="field" id="customer_name_informal">
          </div>
          <div style="clear:both"></div>
        </div>
        <div style="clear:both"></div>
        
        <!-- address -->
        <div class='label-field-left' style="">
          <div class='label'>Address:</div>
          <div style="width:600px">
            <input name="address" type="text" id="address" size="35" maxlength="40" onChange="copyValue(this.value, 'site_address')"/>
            <br />
          </div>
          <div class='label'>City:</div>
          <div style="width:600px">
            <input name="city" type="text" id="city" size="35" maxlength="40" onChange="copyValue(this.value, 'site_city')"/>
            <br />
          </div>
          <div class='label'>State:</div>
          <div style="width:600px">
            <select name="sel_state" id='sel_state' onChange="copySelValue('sel_state', 'sel_site_state')">
              <?php include 'stateOptions.php' ?>
            </select>
          </div>
          <div class='label'>Zip:</div>
          <div style="width:600px">
            <input name="zipcode" type="text" id="zipcode" size="12" maxlength="12" onChange="copyValue(this.value, 'site_zipcode')"/>
          </div>
        </div>
        <div style="clear:both"></div>
        
        <!-- accounts payable addr -->
        <div class='label-field-left' style="">
          <div class='label' style="width:600px; text-align:left; padding-top:15px">Accounts Payable Addr (if different):</div>
        </div>
        <div style="clear:both"></div>
        <div class='label-field-left' style="">
          <div class='label'>Address:</div>
          <div style="width:600px">
            <input name="accounts_pay_address" type="text" id="accounts_pay_address" size="35" maxlength="40"/>
            <br />
          </div>
          <div class='label'>City:</div>
          <div style="width:600px">
            <input name="accounts_pay_city" type="text" id="accounts_pay_city" size="35" maxlength="40"/>
            <br />
          </div>
          <div class='label'>State:</div>
          <div style="width:600px">
            <select name="sel_accounts_pay_state" id="sel_accounts_pay_state">
              <?php include 'stateOptions.php' ?>
            </select>
          </div>
          <div class='label'>Zip:</div>
          <div style="width:600px">
            <input name="accounts_pay_zipcode" type="text" id="accounts_pay_zipcode" size="12" maxlength="12"/>
          </div>
        </div>
        <div style="clear:both"></div>
      </div>
      <div style="clear:both"></div>
    
    <!-- END CUSTOMER INFORMATION BLOCK --> 
    
    <!-- CUSTOMER CONTACT BLOCK -->
    <div class='sectionBlock'>
      <div class='sectionBlockTitle'>Customer Contact Information</div>
    </div>
    <div class="">
      <div>
        <table width="830px"  border="1" cellspacing="0" bordercolor="#666666" style="padding-left:-5px">
          <tr align="center" class="category-row_complete">
            <td width="136">Name</td>
            <td width="202">Role</td>
            <td width="121">Email</td>
            <td width="100">Office</td>
            <td width="100">Cell</td>
            <td width="101">Fax</td>
          </tr>
          <tr>
            <td><input name="cust_contact_primary" type="text" class="required" id="cust_contact_primary" size="17" maxlength="50" onChange="copyValue(this.value, 'manifest_cust_contact_name1')"></td>
            <td>Primary Contact</td>
            <td><input name="cust_contact_primary_email" type="text" class="required" id="cust_contact_primary_email" size="17" maxlength="50" onChange="copyValue(this.value, 'manifest_cust_email1')")></td>
            <td><input name="cust_contact_primary_phone" type="text" class="required" id="cust_contact_primary_phone" size="14" maxlength="14" onkeypress="return addPhone(this, event)" onChange="copyValue(this.value, 'manifest_cust_office1')"/></td>
            <td><input name="cust_contact_primary_cell" type="text" id="cust_contact_primary_cell" size="14" maxlength="14" onkeypress="return addPhone(this, event)"/></td>
            <td><input name="cust_contact_primary_fax" type="text" id="cust_contact_primary_fax" size="14" maxlength="14" onkeypress="return addPhone(this, event)"/></td>
          </tr>
          <tr>
            <td><input id="cust_contact_purchasing" name="cust_contact_purchasing" type="text" size="17" maxlength="50"></td>
            <td>Purchasing</td>
            <td><input id="cust_contact_purchasing_email" name="cust_contact_purchasing_email" type="text" size="17" maxlength="50"></td>
            <td><input name="cust_contact_purchasing_phone" type="text" id="cust_contact_purchasing_phone" size="14" maxlength="14" onkeypress="return addPhone(this, event)"/></td>
            <td><input name="cust_contact_purchasing_cell" type="text" id="cust_contact_purchasing_cell" size="14" maxlength="14" onkeypress="return addPhone(this, event)"/></td>
            <td><input name="cust_contact_purchasing_fax" type="text" id="cust_contact_purchasing_fax" size="14" maxlength="14" onkeypress="return addPhone(this, event)"/></td>
          </tr>
          <tr>
            <td><input id="cust_contact_maintenance" name="cust_contact_maintenance" type="text" size="17" maxlength="50"></td>
            <td>Maintenance</td>
            <td><input id="cust_contact_maintenance_email" name="cust_contact_maintenance_email" type="text" size="17" maxlength="50"></td>
            <td><input name="cust_contact_maintenance_phone" type="text" id="cust_contact_maintenance_phone" size="14" maxlength="14" onkeypress="return addPhone(this, event)"/></td>
            <td><input name="cust_contact_maintenance_cell" type="text" id="cust_contact_maintenance_cell" size="14" maxlength="14" onkeypress="return addPhone(this, event)"/></td>
            <td><input name="cust_contact_maintenance_fax" type="text" id="cust_contact_maintenance_fax" size="14" maxlength="14" onkeypress="return addPhone(this, event)"/></td>
          </tr>
          <tr>
            <td><input id="cust_contact_operations" name="cust_contact_operations" type="text" size="17" maxlength="50"></td>
            <td>Operations</td>
            <td><input id="cust_contact_operations_email" name="cust_contact_operations_email" type="text" size="17" maxlength="50"></td>
            <td><input name="cust_contact_operations_phone" type="text" id="cust_contact_operations_phone" size="14" maxlength="14" onkeypress="return addPhone(this, event)"/></td>
            <td><input name="cust_contact_operations_cell" type="text" id="cust_contact_operations_cell" size="14" maxlength="14" onkeypress="return addPhone(this, event)"/></td>
            <td><input name="cust_contact_operations_fax" type="text" id="cust_contact_operations_fax" size="14" maxlength="14" onkeypress="return addPhone(this, event)"/></td>
          </tr>
          <tr>
            <td><input id="cust_contact_ap" name="cust_contact_ap" type="text" size="17" maxlength="50"></td>
            <td>A/P</td>
            <td><input id="cust_contact_ap_email" name="cust_contact_ap_email" type="text" size="17" maxlength="50"></td>
            <td><input name="cust_contact_ap_phone" type="text" id="cust_contact_ap_phone" size="14" maxlength="14" onkeypress="return addPhone(this, event)"/></td>
            <td><input name="cust_contact_ap_cell" type="text" id="cust_contact_ap_cell" size="14" maxlength="14" onkeypress="return addPhone(this, event)"/></td>
            <td><input name="cust_contact_ap_fax" type="text" id="cust_contact_ap_fax" size="14" maxlength="14" onkeypress="return addPhone(this, event)"/></td>
          </tr>
          <tr>
            <td><input id="cust_contact_other1" name="cust_contact_other1" type="text" size="17" maxlength="50"></td>
            <td nowrap>other:
              <input id="cust_contact_other1_roll" name="cust_contact_other1_roll" type="text" size="10" maxlength="50"></td>
            <td><input name="cust_contact_other1_email" type="text" size="17" maxlength="50"></td>
            <td><input name="cust_contact_other1_phone" type="text" id="cust_contact_other1_phone" size="14" maxlength="14" onkeypress="return addPhone(this, event)"/></td>
            <td><input name="cust_contact_other1_cell" type="text" id="cust_contact_other1_cell" size="14" maxlength="14" onkeypress="return addPhone(this, event)"/></td>
            <td><input name="cust_contact_other1_fax" type="text" id="cust_contact_other1_fax" size="14" maxlength="14" onkeypress="return addPhone(this, event)"/></td>
          </tr>
          <tr>
            <td><input id="cust_contact_other2" name="cust_contact_other2" type="text" size="17" maxlength="50"></td>
            <td nowrap>other:
              <input id="cust_contact_other2_roll" name="cust_contact_other2_roll" type="text" size="10" maxlength="50"></td>
            <td><input name="cust_contact_other2_email" type="text" size="17" maxlength="50"></td>
            <td><input name="cust_contact_other2_phone" type="text" id="cust_contact_other2_phone" size="14" maxlength="14" onkeypress="return addPhone(this, event)"/></td>
            <td><input name="cust_contact_other2_cell" type="text" id="cust_contact_other2_cell" size="14" maxlength="14" onkeypress="return addPhone(this, event)"/></td>
            <td><input name="cust_contact_other2_fax" type="text" id="cust_contact_other2_fax" size="14" maxlength="14" onkeypress="return addPhone(this, event)"/></td>
          </tr>
        </table>
      </div>
    </div>
    <!-- END CUSTOMER CONTACT BLOCK --> 
    
    <!-- BEGIN PROJECT INFORMATION BLOCK -->
    <div class='sectionBlock'>
      <div class='sectionBlockTitle'>Project Information</div>
    </div>
    <div class="sectionBlockContainer">
      <div class='label-field-left'>
        <div class='label'>Project Description:</div>
        <div class='field'>
          <textarea name="project_description" id="project_description" cols="65" rows="3"></textarea>
        </div>
      </div>
      <div style="clear:both"></div>
      <div class='label-field-left' style="width:550px">
        <div class='label'>Project Type:</div>
        <div style="width:450px">
          <select class='selectObj' id="sel_project_type" name="sel_project_type">
            <option value="---select---" selected>Choose</option>
            <option value="New Install (existing account)">New Install (existing account)</option>
            <option value="Existing Equipment Upgrade">Existing Equipment Upgrade</option>
            <option value="New Account">New Account</option>
            <option value="Drop Trailer">Drop Trailer</option>
            <option value="Isotainer">Isotainer</option>
            <option value="Pump Off">Pump Off</option>
          </select>
        </div>
        <div style="clear:both"></div>
      </div>
      <div style="clear:both"></div>
      <div class='label-field-left'>
        <div class='label'>Technical Approval By:</div>
        <div class='field'>
          <select class='selectObj' id="sel_technical_approval_by" name="sel_technical_approval_by">
            <option value="---select---" selected>Choose</option>
            <?
//              	$query = "SELECT DISTINCT CONCAT(FirstName, ' ', LastName) as comsContact, email as comsContactEmail FROM internalEmailDist order by FirstName";
              	$query = "SELECT DISTINCT CONCAT(trim(FirstName), ' ', trim(LastName)) as comsContact, email as comsContactEmail, phone as office FROM users where LastName NOT LIKE '%Neff%' order by FirstName";
				$res = getResult($query);
				if (checkResult($res))
				{
					$comsContacts = "";
					$comsContactsEmail = "";
					while ($line = $res->fetch_assoc())
					{
						extract($line);
						$comsContacts .= "<option value=\"$comsContact\">$comsContact</option>\n";	
					}
					echo($comsContacts);
				}
			  
			  ?>
          </select>
        </div>
        <div style="clear:both"></div>
      </div>
      <div style="clear:both"></div>
      <div class='label-field-left'>
        <div class='label'>Estimated Install Date:</div>
        <div class='field'>
          <input name="install_date" type="text" class="field" id="install_date">
        </div>
        <div style="clear:both"></div>
      </div>
      <div class='label-field-left'>
        <div class='label'>Estimated Start Date:</div>
        <div class='field'>
          <input name="start_date" type="text" class="field" id="start_date">
        </div>
        <div style="clear:both"></div>
      </div>
      <div style="clear:both"></div>
      <div class='label-field-left'>
        <div class='label'>Segment Type (LOB):</div>
        <div class='field'>
          <select class='selectObj' id="sel_segment_type" name="sel_segment_type">
            <option value="---select---" selected>---select---</option>
            <!--              <option value="EDW - East Drinking Water">EDW - East Drinking Water</option>
              <option value="EID - East Industrial">EID - East Industrial</option>
              <option value="EPR - East PRI-SC">EPR - East PRI-SC</option>
              <option value="ERM - East Remediation">ERM - East Remediation</option>
              <option value="EWW - East Wastewater">EWW - East Wastewater</option>
              <option value="WDW - West Drinking Water">WDW - West Drinking Water</option>
              <option value="WID - West Industrial">WID - West Industrial</option>
              <option value="WPR - West PRI-SC">WPR - West PRI-SC</option>
              <option value="WRM - West Remediation">WRM - West Remediation</option>
              <option value="WWW - West Wastewater">WWW - West Wastewater</option> -->
            <option value="EDW">EDW</option>
            <option value="EID">EID</option>
            <option value="EPR">EPR</option>
            <option value="ERM">ERM</option>
            <option value="EWW">EWW</option>
            <option value="WDW">WDW</option>
            <option value="WID">WID</option>
            <option value="WPR">WPR</option>
            <option value="WRM">WRM</option>
            <option value="WWW">WWW</option>
          </select>
        </div>
        <div style="clear:both"></div>
      </div>
      <div class='label-field-left'>
        <div class='label'>Service Type:</div>
        <div class='field'>
          <select class='selectObj' id="sel_service_type" name="sel_service_type">
            <option value="---select---" selected>---select---</option>
            <option value="EQON - Equipment Only">EQON - Equipment Only</option>
            <option value="EQSV - Equipment + Service">EQSV - Equipment + Service</option>
            <option value="FULL - Full Service">FULL - Full Service</option>
            <option value="PROD - Product Only">PROD - Product Only</option>
            <option value="PREQ - Product + Equipment">PREQ - Product + Equipment</option>
            <option value="PRSV - Product + Service">PRSV - Product + Service</option>
          </select>
        </div>
        <div style="clear:both"></div>
      </div>
      <div style="clear:both"></div>
      <div class='label-field-left'>
        <div class='label'>Capital Project No:</div>
        <div class='field'>
          <input name="capital_project_no" type="text" class="field" id="capital_project_no">
        </div>
        <div style="clear:both"></div>
      </div>
      <div class='label-field-left'>
        <div class='label'>BAAN Account No:</div>
        <div class='field'>
          <input name="baan_no" type="text" class="field" id="baan_no">
        </div>
        <div style="clear:both"></div>
      </div>
      <div style="clear:both"></div>
      <div class='label-field-left'>
        <div class='label'>Project Charge Code:</div>
        <div class='field'>
          <input name="project_charge_code" type="text" class="field" id="project_charge_code">
        </div>
      </div>
      <div style="clear:both"></div>
    </div>
    <!-- END PROJECT INFORMATION BLOCK --> 
    
    <!-- BEGIN UPS TEAM INFORMATION BLOCK -->
    <div class='sectionBlock'>
      <div class='sectionBlockTitle'>USP Team Information</div>
    </div>
    <div class="">
      <div>
        <table width="830px"  border="1" cellspacing="0" bordercolor="#666666" style="padding-left:-5px">
          <tr align="center" class="category-row_complete">
            <td width="140">Name</td>
            <td width="143" nowrap>Role</td>
            <td width="142">Email</td>
            <td width="115">Office</td>
            <td width="109">Cell</td>
            <td width="130">Fax</td>
          </tr>
          <tr>
            <td><select class='selectObj' id="sel_usp_team_mgr" name="sel_usp_team_mgr" onChange="setEmail('usp_team_mgr')">
                <option value="---select---" selected>---select---</option>
                <?php echo $comsContacts?>
              </select></td>
            <td nowrap>EES Project Mgr</td>
            <td><input id="usp_team_mgr_email" name="usp_team_mgr_email" type="text" size="17" maxlength="50"></td>
            <td><input name="usp_team_mgr_phone" type="text" id="usp_team_mgr_phone" size="14" maxlength="14" onkeypress="return addPhone(this, event)"/></td>
            <td><input name="usp_team_mgr_cell" type="text" id="usp_team_mgr_cell" size="14" maxlength="14" onkeypress="return addPhone(this, event)"/></td>
            <td><input name="usp_team_mgr_fax" type="text" id="usp_team_mgr_fax" size="14" maxlength="14" onkeypress="return addPhone(this, event)"/></td>
          </tr>
          <tr>
            <td><select class='selectObj' id="sel_usp_team_applications" name="sel_usp_team_applications" onChange="setEmail('usp_team_applications')">
                <option value="---select---" selected>---select---</option>
                <?php echo $comsContacts?>
              </select></td>
            <td nowrap>Applications / BD</td>
            <td><input id="usp_team_applications_email" name="usp_team_applications_email" type="text" size="17" maxlength="50"></td>
            <td><input name="usp_team_applications_phone" type="text" id="usp_team_applications_phone" size="14" maxlength="14" onkeypress="return addPhone(this, event)"/></td>
            <td><input name="usp_team_applications_cell" type="text" id="usp_team_applications_cell" size="14" maxlength="14" onkeypress="return addPhone(this, event)"/></td>
            <td><input name="usp_team_applications_fax" type="text" id="usp_team_applications_fax" size="14" maxlength="14" onkeypress="return addPhone(this, event)"/></td>
          </tr>
          <tr>
            <td><select class='selectObj' id="sel_usp_team_sales" name="sel_usp_team_sales" onChange="setEmail('usp_team_sales')">
                <option value="---select---" selected>---select---</option>
                <?php echo $comsContacts?>
              </select></td>
            <td nowrap>Sales</td>
            <td><input id="usp_team_sales_email" name="usp_team_sales_email" type="text" size="17" maxlength="50"></td>
            <td><input name="usp_team_sales_phone" type="text" id="usp_team_sales_phone" size="14" maxlength="14" onkeypress="return addPhone(this, event)"/></td>
            <td><input name="usp_team_sales_cell" type="text" id="usp_team_sales_cell" size="14" maxlength="14" onkeypress="return addPhone(this, event)"/></td>
            <td><input name="usp_team_sales_fax" type="text" id="usp_team_sales_fax" size="14" maxlength="14" onkeypress="return addPhone(this, event)"/></td>
          </tr>
          <tr>
            <td><select class='selectObj' id="sel_usp_team_installer1" name="sel_usp_team_installer1" onChange="setEmail('usp_team_installer1')">
                <option value="---select---" selected>---select---</option>
                <?php echo $comsContacts?>
              </select></td>
            <td nowrap>Installer 1</td>
            <td><input id="usp_team_installer1_email" name="usp_team_installer1_email" type="text" size="17" maxlength="50"></td>
            <td><input name="usp_team_installer1_phone" type="text" id="usp_team_installer1_phone" size="14" maxlength="14" onkeypress="return addPhone(this, event)"/></td>
            <td><input name="usp_team_installer1_cell" type="text" id="usp_team_installer1_cell" size="14" maxlength="14" onkeypress="return addPhone(this, event)"/></td>
            <td><input name="usp_team_installer1_fax" type="text" id="usp_team_installer1_fax" size="14" maxlength="14" onkeypress="return addPhone(this, event)"/></td>
          </tr>
          <tr>
            <td><select class='selectObj' id="sel_usp_team_installer2" name="sel_usp_team_installer2" onChange="setEmail('usp_team_installer2')">
                <option value="---select---" selected>---select---</option>
                <?php echo $comsContacts?>
              </select></td>
            <td nowrap>Installer 2</td>
            <td><input id="usp_team_installer2_email" name="usp_team_installer2_email" type="text" size="17" maxlength="50"></td>
            <td><input name="usp_team_installer2_phone" type="text" id="usp_team_installer2_phone" size="14" maxlength="14" onkeypress="return addPhone(this, event)"/></td>
            <td><input name="usp_team_installer2_cell" type="text" id="usp_team_installer2_cell" size="14" maxlength="14" onkeypress="return addPhone(this, event)"/></td>
            <td><input name="usp_team_installer2_fax" type="text" id="usp_team_installer2_fax" size="14" maxlength="14" onkeypress="return addPhone(this, event)"/></td>
          </tr>
        </table>
      </div>
    </div>
    <!-- END UPS TEAM INFORMATION BLOCK --> 
    
    <!-- BEGIN CHEMICAL DOSE INFORMATION BLOCK -->
    <div class='sectionBlock'>
      <div class='sectionBlockTitle'>Chemical Dose Site Information <span style="font-size:smaller">-- Complete Separate "New Customer Form" for each dose site</span></div>
    </div>
    <div class="">
      <div>
        <table width="830px"  border="1" cellspacing="0" bordercolor="#666666" style="padding-left:-5px">
          <tr style="font-size:smaller; font-weight:800" align="center" class="category-row_complete">
            <td width="50">Site #</td>
            <td width="200">Site Name</td>
            <td width="137">Product<br />
              (Grade %)</td>
            <td width="112">Dose Rate (GPD)</td>
            <td width="174">Chemical Supplier</td>
            <td width="87">Planned Storage (GAL)</td>
          </tr>
          <tr align="center">
            <td width="50">1</td>
            <td width="200"><input name="site_info_sitename_1" type="text" class="required" id="site_info_sitename_1" onChange="setDoseDefaults(this.value)" size="25" maxlength="50"></td>
            <td width="137"><span class="field">
              <select class='selectObj required' name="sel_site_info_product_1" id="sel_site_info_product_1"  onChange="copySelValue('sel_site_info_product_1', 'sel_product_grade')">
                <option value="-select-" selected>-select-</option>
                <?
                    $query = "SELECT prodID, value as prodDesc FROM product ORDER BY value";
                    $res = getResult($query);
                    if (checkResult($res))
                    {
						$productList = '';
                        while ($line = $res->fetch_assoc())
                        {
                            extract($line);
                            $productList .= "\n<option value=\"$prodID\">$prodDesc</option>\n";	
                        }
                    }
                  	echo($productList);
                  ?>
<!--
                <option value="--select--" selected>---select---</option>
                <option value="H202 50%">H202 50%</option>
                <option value="H202 35%">H202 35%</option>
                <option value="H202 27%">H202 27%</option>
                <option value="H202 50% NSF">H202 50% NSF</option>
                <option value="H202 35% FG">H202 35% FG</option>
                <option value="H202 35% NSF">H202 35% NSF</option>
                <option value="FeCI2">FeCI2</option>
                <option value="FeCI3">FeCI3</option>
                <option value="FeSo4">FeSo4</option>
                <option value="Caustic 25%">Caustic 25%</option>
                <option value="Caustic 50%">Caustic 50%</option>
                <option value="Fe2(SO4)3">Fe2(SO4)3</option>
                <option value="Bioxide">Bioxide</option>
                <option value="Nitra-NOX">Nitra-NOX</option>
                <option value="NaOH">NaOH</option>
                <option value="Polymer">Polymer</option>
                <option value="VTX">VTX</option>
                <option value="Kloxur">Kloxur</option>
                <option value="Fe EDTA">Fe EDTA</option>
                <option value="Envirofirst">Envirofirst</option>
                <option value="Other">Other</option>
-->
              </select>
              </span></td>
            <td width="112"><input id="site_info_dose_1" name="site_info_dose_1" type="text" size="10" maxlength="5"></td>
            <td width="174"><span class="field">
              <select id="sel_site_info_supplier_1" class='selectObj required' name="sel_site_info_supplier_1">
                <option value="-select-" selected>-select-</option>
<?php
	$query = "SELECT supplierID, supplierName FROM supplier WHERE supplierName NOT LIKE \"%test%\" and supplierName != '' order by SupplierName";
	$res = getResult($query);
	if (checkResult($res))
	{
		$supplierList = '';
		while($line = $res->fetch_assoc())
		{
			extract($line);
			$supplierList .= "\n<option value='$supplierID'>$supplierName</option>";
		}		
		echo($supplierList);
	}
?>                
<!--
                <option value="AOT">AOT</option>
                <option value="Arkema">Arkema</option>
                <option value="Aulick">Aulick</option>
                <option value="BCS">BCS</option>
                <option value="Borden & Remington">Borden & Remington</option>
                <option value="Brenntag">Brenntag</option>
                <option value="CWT">CWT</option>
                <option value="Evonik-Degussa">Evonik-Degussa</option>
                <option value="FanChem">FanChem</option>
                <option value="Fertizona">Fertizona</option>
                <option value="FMC">FMC</option>
                <option value="Kemira">Kemira</option>
                <option value="Mann Chemicals">Mann Chemicals</option>
                <option value="OFS">OFS</option>
                <option value="PVS">PVS</option>
                <option value="Siemans">Siemans</option>
                <option value="Solvay">Solvay</option>
                <option value="Univar">Univar</option>
                <option value="Customer Supplied">Customer Supplied</option>
                <option value="Other">Other</option>
-->                
              </select>
              </span></td>
            <td width="87"><input class='required' id="site_info_storage_1" name="site_info_storage_1" type="text" size="10" maxlength="5" onkeypress="return numbersonly(this, event)"></td>
          </tr>
          <tr align="center">
            <td width="50">2</td>
            <td width="200"><input id="site_info_sitename_2" name="site_info_sitename_2" type="text" size="25" maxlength="50"></td>
            <td width="137"><span class="field">
              <select name="sel_site_info_product_2" id="sel_site_info_product_2" >
                <option value="---select---" selected>---select---</option>
                <?php echo $productList?>
              </select>
              </span></td>
            <td width="112"><input id="site_info_dose_2" name="site_info_dose_2" type="text" size="10" maxlength="5"></td>
            <td width="174"><span class="field">
              <select id="sel_site_info_supplier_2" name="sel_site_info_supplier_2">
                <option value="---select---" selected>---select---</option>
				<?php echo $supplierList?>
              </select>
              </span></td>
            <td width="87"><input id="site_info_storage_2" name="site_info_storage_2" type="text" size="10" maxlength="5" onkeypress="return numbersonly(this, event)"></td>
          </tr>
          <tr align="center">
            <td width="50">3</td>
            <td width="200"><input id="site_info_sitename_3" name="site_info_sitename_3" type="text" size="25" maxlength="50"></td>
            <td width="137"><span class="field">
              <select name="sel_site_info_product_3" id="sel_site_info_product_3" >
                <option value="---select---" selected>---select---</option>
                <?php echo $productList?>
              </select>
              </span></td>
            <td width="112"><input id="site_info_dose_3" name="site_info_dose_3" type="text" size="10" maxlength="5"></td>
            <td width="174"><span class="field">
              <select id="sel_site_info_supplier_3" name="sel_site_info_supplier_3">
                <option value="---select---" selected>---select---</option>
				<?php echo $supplierList?>
              </select>
              </span></td>
            <td width="87"><input id="site_info_storage_3" name="site_info_storage_3" type="text" size="10" maxlength="5" onkeypress="return numbersonly(this, event)"></td>
          </tr>
          <tr align="center">
            <td width="50">4</td>
            <td width="200"><input id="site_info_sitename_4" name="site_info_sitename_4" type="text" size="25" maxlength="50"></td>
            <td width="137"><span class="field">
              <select name="sel_site_info_product_4" id="sel_site_info_product_4" >
                <option value="---select---" selected>---select---</option>
                <?php echo $productList?>
              </select>
              </span></td>
            <td width="112"><input id="site_info_dose_4" name="site_info_dose_4" type="text" size="10" maxlength="5"></td>
            <td width="174"><span class="field">
              <select id="sel_site_info_supplier_4" name="sel_site_info_supplier_4">
                <option value="---select---" selected>---select---</option>
				<?php echo $supplierList?>
              </select>
              </span></td>
            <td width="87"><input id="site_info_storage_4" name="site_info_storage_4" type="text" size="10" maxlength="5" onkeypress="return numbersonly(this, event)"></td>
          </tr>
          <tr align="center">
            <td colspan="2" align="center">More than one supplier?</td>
            <td><span class="field">
              <select name="sel_more_than_one_supplier" id="sel_more_than_one_supplier" >
                <option value="---select---" selected>---select---</option>
                <option value="Yes">Yes</option>
                <option value="No">No</option>
              </select>
              </span></td>
            <td colspan="3" align="left">If Yes, List here:
              <input id="site_info_2nd_supplier" name="site_info_2nd_supplier" type="text" size="25" maxlength="50"></td>
          </tr>
        </table>
      </div>
    </div>
    <!-- END CHEMICAL DOSE INFORMATION BLOCK --> 
    
    <!-- BEGIN SPECIAL TRAINING BLOCK -->
    <div class='sectionBlock'>
      <div class='sectionBlockTitle'>Special Training Requirements <span style="font-size:smaller"> (note all required)</span></div>
    </div>
    <div class="">
      <div>
        <table width="830px"  border="1" cellspacing="0" bordercolor="#666666" style="padding-left:-5px">
          <tr align="center" class="category-row_complete">
            <td width="177">Type of Training</td>
            <td width="135">Chemical Delivery<br>
              (y/n)</td>
            <td width="219">USP Engineer</td>
            <td width="119">USP FST</td>
            <td width="116">Sales/BD</td>
          </tr>
          <tr align="center">
            <td><input id="training_type_1" name="training_type_1" type="text" size="25" maxlength="50"></td>
            <td><select class='selectObj' name="sel_training_chemical_delivery_1" id="sel_training_chemical_delivery_1">
                <option value="Yes" selected>Yes</option>
                <option value="No">No</option>
              </select></td>
            <td>
            <select class='selectObj' id="sel_training_usp_engineer_1" name="sel_training_usp_engineer_1">
            <option value="---select---" selected>---select---</option>
            <?php echo $comsContacts?>
            </select>
            </td>

            <td>
            <select class='selectObj' id="sel_training_usp_fst_1" name="sel_training_usp_fst_1">
            <option value="---select---" selected>---select---</option>
            <?php echo $comsContacts?>
            </select>
            </td>

            <td>
            <select class='selectObj' id="sel_training_usp_sales_1" name="sel_training_usp_sales_1">
            <option value="---select---" selected>---select---</option>
            <?php echo $comsContacts?>
            </select>
            </td>

          </tr>
          <tr align="center">
            <td><input id="training_type_2" name="training_type_2" type="text" size="25" maxlength="50"></td>
            <td><select name="sel_training_chemical_delivery_2" id="sel_training_chemical_delivery_2">
                <option value="Yes" selected>Yes</option>
                <option value="No">No</option>
              </select></td>
            <td>
            <select class='selectObj' id="sel_training_usp_engineer_2" name="sel_training_usp_engineer_2">
            <option value="---select---" selected>---select---</option>
            <?php echo $comsContacts?>
            </select>
            </td>

            <td>
            <select class='selectObj' id="sel_training_usp_fst_2" name="sel_training_usp_fst_2">
            <option value="---select---" selected>---select---</option>
            <?php echo $comsContacts?>
            </select>
            </td>

            <td>
            <select class='selectObj' id="sel_training_usp_sales_2" name="sel_training_usp_sales_2">
            <option value="---select---" selected>---select---</option>
            <?php echo $comsContacts?>
            </select>
            </td>
          </tr>
          <tr align="center">
            <td><input id="training_type_3" name="training_type_3" type="text" size="25" maxlength="50"></td>
            <td><select name="sel_training_chemical_delivery_3" id="sel_training_chemical_delivery_3">
                <option value="Yes" selected>Yes</option>
                <option value="No">No</option>
              </select></td>
            <td>
            <select class='selectObj' id="sel_training_usp_engineer_3" name="sel_training_usp_engineer_3">
            <option value="---select---" selected>---select---</option>
            <?php echo $comsContacts?>
            </select>
            </td>

            <td>
            <select class='selectObj' id="sel_training_usp_fst_3" name="sel_training_usp_fst_3">
            <option value="---select---" selected>---select---</option>
            <?php echo $comsContacts?>
            </select>
            </td>

            <td>
            <select class='selectObj' id="sel_training_usp_sales_3" name="sel_training_usp_sales_3">
            <option value="---select---" selected>---select---</option>
            <?php echo $comsContacts?>
            </select>
            </td>
          </tr>
        </table>
      </div>
    </div>
    <!-- END SPECIAL TRAINING BLOCK -->
    
    <div style="width:830px">
      <div style="float:left">
        <div class="sectionBlockTitle" style="margin-bottom:20px; float:left">Notes:</div>
        <div style="float:left; margin-top:20px; padding-left:15px">
          <textarea id="notes" name="notes" cols="25" rows="3"></textarea>
        </div>
        <div style="clear:both"></div>
      </div>
      <div class="label-field-right navDiv" style="padding-top:20px"> <a href="javascript:processSection(1, 'forward')"><img src="images/goto-section-2.png" alt="Continue to Section 2" border="0" ></a> </div>
      <div style="clear:both"></div>
    </div>
  </div>
</div>
<!-- SECTION 1 END --> 

<!-- SECTION 2 START -->
<div id='section_2' class='newCustomerDiv' style="display:inline">
  <input name="updated_by" type="hidden" id="updated_by" value="<?php echo $USERID?>">
  <div class='sectionHeader2'>Section 2 - Chemical Delivery and Safety Information<br />
    (Sales/BD -EES)</div>
  <div class='sectionBlock'>
    <div class='sectionBlockTitle'>Site Delivery Details</div>
  </div>
  <div class='sectionBlockContainer'>
    <div class='label-field-left'>
      <div class='label'>Dose Site Name:</div>
      <div class='field'>
        <input name="dose_site_name" type="text" class="field" id="dose_site_name" readonly>
      </div>
      <div style="clear:both"></div>
    </div>
    <div style="clear:both"></div>

        <!-- address -->
        <div class='label-field-left' style="padding-top:15px; padding-bottom:15px">
          <div class='label'>Address:</div>
          <div style="width:600px">
            <input class='required' name="site_address" type="text" id="site_address" size="35" maxlength="40"/>
            <br />
          </div>
          <div class='label'>City:</div>
          <div style="width:600px">
            <input class='required' name="site_city" type="text" id="site_city" size="35" maxlength="40"/>
            <br />
          </div>
          <div class='label'>State:</div>
          <div style="width:600px">
            <select class='required' name="sel_site_state" id='sel_site_state'>
              <?php include 'stateOptions.php' ?>
            </select>
          </div>
          <div class='label'>Zip:</div>
          <div style="width:600px">
            <input class='required' name="site_zipcode" type="text" id="site_zipcode" size="12" maxlength="12"/>
          </div>
        </div>
        <div style="clear:both"></div>

<!--
    <div class='label-field-left'>
      <div class='label'>Address:</div>
      <div class='field'>
        <textarea id="site_address" name="site_address" cols="22" rows="3" readonly></textarea>
      </div>
      <div style="clear:both"></div>
    </div>
    <div style="clear:both"></div>
-->

    <div class='label-field-left'>
      <div class='label'>Product and Grade:</div>
      <div class='field'>
      
          <select class='selectObj required' id="sel_product_grade" name="sel_product_grade">
            <option value="--select--" selected>--select--</option>
            <?php
              	$query = "SELECT prodID, value as prodDesc FROM product ORDER BY value";
				$res = getResult($query);
				if (checkResult($res))
				{
					while ($line = $res->fetch_assoc())
					{
						extract($line);
						echo "<option value=\"$prodID\">$prodDesc</option>\n";	
					}
				}
			  
			  ?>
          </select>
      
      </div>
    </div>
    <div style="clear:both"></div>
    <div class='label-field-left' style="margin-top:25px">
      <div class='label'>Directions to Site:</div>
      <div class='field'>
        <textarea id="directions_to_site" name="directions_to_site" cols="50" rows="3"></textarea>
      </div>
      <div style="clear:both"></div>
    </div>
    <div class='label-field-left'>
      <div class='label'>&nbsp;</div>
      <div class='field'>&nbsp;</div>
      <div style="clear:both"></div>
    </div>
    <div style="clear:both"></div>
    <div class='label-field-left'>
      <div class='label'>Approx Distance from Truck to Off-load Point (ft):</div>
      <div class='field' style="margin-top:7px">
        <input name="distance_to_offload" type="text" id="distance_to_offload" size="5" maxlength="5" onkeypress="return numbersonly(this, event)">
      </div>
      <div style="clear:both"></div>
    </div>
    <div class='label-field-left'>
      <div class='label'>Length of Hose<br >
        (20ft each):</div>
      <div class='field'>
        <textarea id="length_of_hose" name="length_of_hose" cols="22" rows="3"></textarea>
      </div>
      <div style="clear:both"></div>
    </div>
    <div style="clear:both"></div>
    <div class='label-field-left'>
      <div class='label'>Site Accessibility:</div>
      <div class='field'>
        <input name="site_accessibility" type="text" id="site_accessibility" size="25" maxlength="150">
      </div>
    </div>
    <div style="clear:both"></div>
    <div class='label-field-left' style="margin-top:25px">
      <div class='label'>Special Driver Instructions or Training Requirements:</div>
      <div class='field'>
        <textarea id="driver_instructions" name="driver_instructions" cols="50" rows="3"></textarea>
      </div>
      <div style="clear:both"></div>
    </div>
    <div class='label-field-left'>
      <div class='label'>&nbsp;</div>
      <div class='field'>&nbsp;</div>
      <div style="clear:both"></div>
    </div>
    <div style="clear:both"></div>
    <div class='label-field-left' style="margin-top:25px">
      <div class='label'>Location or Procedure for Residual Product Disposal:</div>
      <div class='field'>
        <textarea id="product_disposal" name="product_disposal" cols="25" rows="3"></textarea>
      </div>
      <div style="clear:both"></div>
    </div>
    <div class='label-field-left'>
      <div class='label'>&nbsp;</div>
      <div class='field'>&nbsp;</div>
      <div style="clear:both"></div>
    </div>
    <div style="clear:both"></div>
    <div class='label-field-left'>
      <div class='label'>Is Driver Inspection Required:</div>
      <div class='field'>
        <select class='selectObj' name="sel_driver_inspection_required" id="sel_driver_inspection_required">
          <option value=""> </option>
          <option value="Yes">Yes</option>
          <option value="No">No</option>
        </select>
      </div>
      <div style="clear:both"></div>
    </div>
    <div class='label-field-left'>
      <div class='label'>Does driver have to back into the site? (how far?)</div>
      <div class='field'>
        <input name="back_in_distance" type="text" id="back_in_distance" size="5" maxlength="5" onkeypress="return numbersonly(this, event)">
        ft </div>
      <div style="clear:both"></div>
    </div>
    <div style="clear:both"></div>
    <div class='label-field-left'>
      <div class='label'>Tank Name:</div>
      <div class='field'>
        <input id="tank_name" name="tank_name" type="text" class="field required">
      </div>
      <div style="clear:both"></div>
    </div>
    <div class='label-field-left'>
      <div class='label'>No of Tanks:</div>
      <div class='field'>
        <input name="number_of_tanks" type="text" id="number_of_tanks" size="5" maxlength="5" onkeypress="return numbersonly(this, event)">
      </div>
      <div style="clear:both"></div>
    </div>
    <div style="clear:both"></div>
    <div class='label-field-left'>
      <div class='label'>Tank Capacity:</div>
      <div class='field'>
        <input name="tank_capacity" type="text" id="tank_capacity" size="5" maxlength="5" onkeypress="return numbersonly(this, event)" onChange="copyValue(this.value, 'tank_details_tank_total_capacity')">
      </div>
      <div style="clear:both"></div>
    </div>
    <div class='label-field-left'>
      <div class='label'>Total Capacity:</div>
      <div class='field'>
        <input name="total_capacity" type="text" id="total_capacity" size="5" maxlength="5" onkeypress="return numbersonly(this, event)">
      </div>
      <div style="clear:both"></div>
    </div>
    <div style="clear:both"></div>
    <div class='label-field-left'>
      <div class='label'>Local Level (Y/N):</div>
      <div class='field'>
        <select class='selectObj' name="sel_local_level" id="sel_local_level">
          <option value=""> </option>
          <option value="Yes">Yes</option>
          <option value="No">No</option>
        </select>
      </div>
      <div style="clear:both"></div>
    </div>
    <div class='label-field-left'>
      <div class='label'>Matl of Constr:</div>
      <div class='field'>
        <input name="material_constr" type="text" class="field" id="material_constr">
      </div>
      <div style="clear:both"></div>
    </div>
    <div style="clear:both"></div>
  </div>
  <!-- end section block container --> 
  
  <!-- ENTRY ACCESS METHODS BLOCK -->
  <div class='sectionBlock'>
    <div class='sectionBlockTitle'>Entry / Access Methods</div>
  </div>
  <div class='sectionBlockContainer'>
    <div class='label-field-left'>
      <div class='label'>Means of Access:</div>
      <div class='field'>
      
      
        <select class='selectObj' name="sel_access_means" id="sel_access_means">
          <option value="---select---">---select---</option>
          <option value="Locked Building">Locked Building</option>
          <option value="Locked Gate">Locked Gate</option>
          <option value="Security Entrance">Security Entrance</option>
          <option value="Other">Other</option>
        </select>
      
      
      </div>
      <div style="clear:both"></div>
    </div>
    <div class='label-field-left'>
      <div class='label'>If Other, list here:</div>
      <div class='field'>
        <input name="other_access" type="text" id="other_access" class="field">
      </div>
      <div style="clear:both"></div>
    </div>
    <div style="clear:both"></div>
    <div class='label-field-left'>
      <div class='label'>Entry Contact Name:</div>
      <div class='field'>
        <input name="entry_contact" type="text" id="entry_contact" class="field">
      </div>
      <div style="clear:both"></div>
    </div>
    <div class='label-field-left'>
      <div class='label'>Entry Contact Phone:</div>
      <div class='field'>
        <input name="entry_contact_phone" type="text" id="entry_contact_phone" size="14" maxlength="14" onkeypress="return addPhone(this, event)">
      </div>
      <div style="clear:both"></div>
    </div>
    <div style="clear:both"></div>
    <div class='label-field-left'>
      <div class='label'>USP Contact:</div>
      <div class='field'>
      
        
        <select class='selectObj' id="sel_usp_contact" name="sel_usp_contact" onChange="setEmail('usp_contact')">
        <option value="---select---" selected>---select---</option>
        <?php echo $comsContacts?>
        </select>      
      </div>
      <div style="clear:both"></div>
    </div>
    <div class='label-field-left'>
      <div class='label'>USP Contact Phone:</div>
      <div class='field'>
        <input name="usp_contact_phone" type="text" id="usp_contact_phone" size="14" maxlength="14" onkeypress="return addPhone(this, event)">
      </div>
      <div style="clear:both"></div>
    </div>
    <div style="clear:both"></div>
    <div class='label-field-left'>
      <div class='label'>Acceptable Delivery Days:</div>
      <div class='field'>
        <input name="acceptible_delivery_days" type="text" id="acceptible_delivery_days" class="field">
      </div>
      <div style="clear:both"></div>
    </div>
    <div class='label-field-left'>
      <div class='label'> Preferred Delivery Days:</div>
      <div class='field'>
        <input name="preferred_delivery_days" type="text" id="preferred_delivery_days" class="field required">
      </div>
      <div style="clear:both"></div>
    </div>
    <div style="clear:both"></div>
    <div class='label-field-left'>
      <div class='label'>Acceptable Delivery Hours:</div>
      <div class='field'>
        <input name="acceptable_delivery_hours" type="text" id="acceptable_delivery_hours" class="field">
      </div>
      <div style="clear:both"></div>
    </div>
    <div class='label-field-left'>
      <div class='label'>Preferred Delivery Hours:</div>
      <div class='field'>
        <input name="preferred_delivery_hours" type="text" id="preferred_delivery_hours" class="field required">
      </div>
      <div style="clear:both"></div>
    </div>
    <div style="clear:both"></div>
  </div>
  <!-- END ENTRY ACCESS METHODS --> 
  
  <!-- SAFETY / SECURTY BLOCK -->
  <div class='sectionBlock'>
    <div class='sectionBlockTitle'>Safety / Security <span style="font-size:smaller">(check all that apply)</span></div>
  </div>
  <div class='sectionBlockContainer' style="font-size:12px">
    <div class='label-field-left'>
      <div class='label' style="width:190px; text-align:left">
        <input type="checkbox" name="chk_portable_water" id="chk_portable_water">
        Potable Water Available</div>
      <div class='label' style="width:190px; text-align:left">
        <input type="checkbox" name="chk_safety_water" id="chk_portable_water">
        Safety Shower Available</div>
      <div style="clear:both"></div>
    </div>
    <div class='label-field-left'>
      <div class='label' style="width:190px; text-align:left">
        <input type="checkbox" name="chk_eye_wash" id="chk_portable_water">
        Eye Wash Available</div>
      <div class='label' style="width:190px; text-align:left">
        <input type="checkbox" name="chk_hose_avail" id="chk_portable_water">
        Hose Available</div>
      <div style="clear:both"></div>
    </div>
    <div style="clear:both"></div>
    <div class='label-field-left'>
      <div class='label' style="width:190px; text-align:left">
        <input type="checkbox" name="chk_non_potable_water" id="chk_portable_water">
        Non-Potable for Washdown</div>
      <div class='label' style="width:190px; text-align:left">
        <input type="checkbox" name="chk_tank_containment" id="chk_portable_water">
        Tank Containment (Dike)</div>
      <div style="clear:both"></div>
    </div>
    <div class='label-field-left'>
      <div class='label'>&nbsp;</div>
      <div class='label'>&nbsp;</div>
      <div style="clear:both"></div>
    </div>
    <div style="clear:both"></div>
    <div class='label-field-left' style="margin-top:25px">
      <div class='label' style="width:380px">Are there any systems indoors or outdoors (describe)</div>
      <div class='field'>
        <textarea id="systems_outdoor" name="systems_outdoor" cols="30" rows="3"></textarea>
      </div>
      <div style="clear:both"></div>
    </div>
    <div class='label-field-left'>
      <div class='label'>&nbsp;</div>
      <div class='field'>&nbsp;</div>
      <div style="clear:both"></div>
    </div>
    <div style="clear:both"></div>
    <div class='label-field-left'>
      <div class='label' style="width:380px">Is this site Secure (describe)</div>
      <div class='field'>
        <textarea id="site_secure" name="site_secure" cols="30" rows="3"></textarea>
      </div>
      <div style="clear:both"></div>
    </div>
    <div class='label-field-left'>
      <div class='label'>&nbsp;</div>
      <div class='field'>&nbsp;</div>
      <div style="clear:both"></div>
    </div>
    <div style="clear:both"></div>
  </div>
  <!-- END SAFETY / SECURTY BLOCK --> 
  
  <!-- DELIVERY MANIFEST ROUTE LIST -->
  <div class='sectionBlock'>
    <div class='sectionBlockTitle'>Delivery Manifest Route List</div>
  </div>
  <div class='' style="font-size:12px">
    <table width="830px"  border="1" cellspacing="0" bordercolor="#666666" style="padding-left:-5px">
      <tr style="font-weight:800" align="center" class="category-row_complete">
        <td colspan="6" align="left">Customer</td>
      </tr>
      <tr>
      <tr style="font-size:smaller;font-weight:800" align="center" class="category-row_complete">
        <td>Name</td>
        <td>Role / Title</td>
        <td>Email</td>
        <td>Office</td>
        <td>Cell</td>
        <td>Fax</td>
      </tr>
      <tr>
        <td><input name="manifest_cust_contact_name1" type="text" id="manifest_cust_contact_name1" size="17" maxlength="50"></td>
        <td><input name="manifest_cust_role1" type="text" id="manifest_cust_role1" size="17" maxlength="50"></td>
        <td><input name="manifest_cust_email1" type="text" id="manifest_cust_email1" size="17" maxlength="50"></td>
        <td><input name="manifest_cust_office1" type="text" id="manifest_cust_office1" onkeypress="return addPhone(this, event)" size="14" maxlength="14"/></td>
        <td><input name="manifest_cust_cell1" type="text" id="manifest_cust_cell1" onkeypress="return addPhone(this, event)" size="14" maxlength="14"/></td>
        <td><input name="manifest_cust_fax1" type="text" id="manifest_cust_fax1" onkeypress="return addPhone(this, event)" size="14" maxlength="14"/></td>
      </tr>
      <tr>
        <td><input id="manifest_cust_contact_name2" name="manifest_cust_contact_name2" type="text" size="17" maxlength="50"></td>
        <td><input id="manifest_cust_role2" name="manifest_cust_role2" type="text" size="17" maxlength="50"></td>
        <td><input id="manifest_cust_email2" name="manifest_cust_email2" type="text" size="17" maxlength="50"></td>
        <td><input id="manifest_cust_office2" name="manifest_cust_office2" type="text" size="14" maxlength="14" onkeypress="return addPhone(this, event)"/></td>
        <td><input id="manifest_cust_cell2" name="manifest_cust_cell2" type="text" size="14" maxlength="14" onkeypress="return addPhone(this, event)"/></td>
        <td><input id="manifest_cust_fax2" name="manifest_cust_fax2" type="text" size="14" maxlength="14" onkeypress="return addPhone(this, event)"/></td>
      </tr>
    </table>
    <table width="830px"  border="1" cellspacing="0" bordercolor="#666666" style="padding-left:-5px; margin-top:8px">
      <tr style="font-weight:800" align="center" class="category-row_complete">
        <td colspan="6" align="left">US Peroxide</td>
      </tr>
      <tr>
      <tr style="font-size:smaller;font-weight:800" align="center" class="category-row_complete">
        <td>Name</td>
        <td>Role / Title</td>
        <td>Email</td>
        <td>Office</td>
        <td>Cell</td>
        <td>Fax</td>
      </tr>
      <tr>


            <td><select name="sel_manifest_usp_contact_name1" class='required' id="sel_manifest_usp_contact_name1" onChange="setEmail('manifest_usp_contact_name1')">
                <option value="-select-" selected>-select-</option>
                <?php echo $comsContacts?>
          </select></td>


        <td><input name="manifest_usp_role1" type="text" id="manifest_usp_role1" size="17" maxlength="50"></td>
        <td><input name="manifest_usp_contact_name1_email" type="text" class="required" id="manifest_usp_contact_name1_email" size="17" maxlength="50"></td>
        <td><input name="manifest_usp_contact_name1_phone" type="text" id="manifest_usp_contact_name1_phone" onkeypress="return addPhone(this, event)" size="14" maxlength="14"/></td>
        <td><input name="manifest_usp_contact_name1_cell" type="text" id="manifest_usp_contact_name1_cell" onkeypress="return addPhone(this, event)" size="14" maxlength="14"/></td>
        <td><input name="manifest_usp_fax1" type="text" id="manifest_usp_fax1" onkeypress="return addPhone(this, event)" size="14" maxlength="14"/></td>
      </tr>
      <tr>

            <td><select class='selectObj' id="sel_manifest_usp_contact_name2" name="sel_manifest_usp_contact_name2" onChange="setEmail('manifest_usp_contact_name2')">
                <option value="---select---" selected>---select---</option>
                <?php echo $comsContacts?>
              </select></td>

        <td><input id="manifest_usp_role2" name="manifest_usp_role2" type="text" size="17" maxlength="50"></td>
        <td><input id="manifest_usp_contact_name2_email" name="manifest_usp_contact_name2_email" type="text" size="17" maxlength="50"></td>
        <td><input id="manifest_usp_contact_name2_phone" name="manifest_usp_contact_name2_phone" type="text" size="14" maxlength="14" onkeypress="return addPhone(this, event)"/></td>
        <td><input id="manifest_usp_contact_name2_cell" name="manifest_usp_contact_name2_cell" type="text" size="14" maxlength="14" onkeypress="return addPhone(this, event)"/></td>
        <td><input id="manifest_usp_fax2" name="manifest_usp_fax2" type="text" size="14" maxlength="14" onkeypress="return addPhone(this, event)"/></td>
      </tr>
      <tr>
            <td><select class='selectObj' id="sel_manifest_usp_contact_name3" name="sel_manifest_usp_contact_name3" onChange="setEmail('manifest_usp_contact_name3')">
                <option value="---select---" selected>---select---</option>
                <?php echo $comsContacts?>
              </select></td>
        <td><input id="manifest_usp_role3" name="manifest_usp_role3" type="text" size="17" maxlength="50"></td>
        <td><input id="manifest_usp_contact_name3_email" name="manifest_usp_contact_name3_email" type="text" size="17" maxlength="50"></td>
        <td><input id="manifest_usp_contact_name3_phone" name="manifest_usp_contact_name3_phone" type="text" size="14" maxlength="14" onkeypress="return addPhone(this, event)"/></td>
        <td><input id="manifest_usp_contact_name3_cell" name="manifest_usp_contact_name3_cell" type="text" size="14" maxlength="14" onkeypress="return addPhone(this, event)"/></td>
        <td><input id="manifest_usp_fax3" name="manifest_usp_fax3" type="text" size="14" maxlength="14" onkeypress="return addPhone(this, event)"/></td>
      </tr>
    </table>
  </div>
  <!-- END DELIVERY MANIFEST ROUTE LIST --> 
  
  <!-- EQUIPMENT DELIVERY INFORMATION -->
  <div class='sectionBlock'>
    <div class='sectionBlockTitle'>Equipment Delivery Information</div>
  </div>
  <div class='sectionBlockContainer'>
    <div class='label-field-left'>
      <div class='label' style="width:380px">Equipment Ship-To Address (if different)</div>
      <div class='field'>
        <textarea id="ship_address" name="ship_address" cols="30" rows="3"></textarea>
      </div>
      <div style="clear:both"></div>
    </div>
    <div class='label-field-left'>
      <div class='label'>&nbsp;</div>
      <div class='field'>&nbsp;</div>
      <div style="clear:both"></div>
    </div>
    <div style="clear:both"></div>
    <div class='label-field-left'>
      <div class='label' style="width:210px; font-size:12px">Offload Equipment Provided By:</div>
      <div class='field' style="width:100px">
        <select class='selectObj' name="sel_off_load_equipment_provider" id="sel_off_load_equipment_provider">
          <option value=""> </option>
          <option value="USP">UPS</option>
          <option value="Customer">Customer</option>
        </select>
      </div>
      <div style="clear:both"></div>
    </div>
    <div class='label-field-left'>
      <div class='label' style="width:300px">
        <input type="checkbox" name="chk_boom" id="chk_boom">
        Boom
        <input type="checkbox" name="chk_crane" id="chk_crane">
        Crane
        <input type="checkbox" name="chk_forklift" id="chk_forklift">
        Forklift 
        Other: </div>
      <div class='field'>
        <input name="off_load_equipment_other" type="text" id="off_load_equipment_other" class="field">
      </div>
      <div style="clear:both"></div>
    </div>
    <div style="clear:both"></div>
    <table width="830px"  border="1" cellspacing="0" bordercolor="#666666" style="margin-left:-10px;margin-top:12px">
      <tr style="font-weight:800" align="left" class="category-row_complete">
        <td width="358">Contact for Equipment Delivery</td>
        <td width="160">Phone</td>
        <td width="121">Fax</td>
        <td width="148">Email</td>
      </tr>
      <tr>
        <td><input id="equip_deliv_contact" name="equip_deliv_contact" type="text" size="25" maxlength="50"></td>
        <td><input id="equip_deliv_phone" name="equip_deliv_phone" type="text" size="14" maxlength="14" onkeypress="return addPhone(this, event)"/></td>
        <td><input id="equip_deliv_fax" name="equip_deliv_fax" type="text" size="14" maxlength="14" onkeypress="return addPhone(this, event)"/></td>
        <td><input id="equip_deliv_email" name="equip_deliv_email" type="text" size="17" maxlength="50"></td>
      </tr>
      <tr>
        <td align="left">Permits Required (Fire / Building / Discharge / Other</td>
        <td colspan='3'>List:
          <input id="equip_deliv_permits" name="equip_deliv_permits" type="text" size="30" maxlength="100"/></td>
      </tr>
    </table>
    <div class="label-field-right navDiv" style="padding-top:20px"> <a href="javascript:processSection(2, 'back')"><img src="images/go-back-to-section-1.png" alt="Back to Section 1" border="0" ></a> <a href="javascript:processSection(2, 'forward')"><img src="images/goto-section-3.png" alt="Continue to Section 2" border="0" ></a> </div>
    <div style="clear:both"></div>
  </div>
</div>
<!-- END EQUIPMENT DELIVERY INFORMATION --> 
<!-- SECTION 2 END --> 

<!-- SECTION 3 START -->
<div id='section_3' class='newCustomerDiv' style="display:inline">
  <input name="updated_by" type="hidden" id="updated_by" value="<?php echo $USERID?>">
  <div class='sectionHeader3'>Section 3 - Dose Information - EES</div>
  <div class='sectionBlock'>
    <div class='sectionBlockTitle'>Dose Site</div>
  </div>
  <div class='sectionBlockContainer'>
    <div class='label-field-left'>
      <div class='label' style="width:220px">Dose Site Name:</div>
      <div class='field'>
        <input id="dose_site_name2" name="dose_site_name2" type="text" size="35" maxlength="150" readonly>
      </div>
    </div>
    <div style="clear:both"></div>
  </div>
  <!-- END SECTION BLOCK -->
  
  <div class='sectionBlock'>
    <div class='sectionBlockTitle'>Dose Point Process Information</div>
  </div>
  <div class=''>
    <table width="795px"  border="1" cellspacing="0" bordercolor="#666666" style="padding-left:-5px; margin-top:12px">
      <tr style="font-weight:800" align="center" class="category-row_complete">
        <td>Name</td>
        <td>Size</td>
        <td>Matl</td>
        <td>Psig(max)</td>
        <td>Temp</td>
        <td>Quill in Place (describe)</td>
        <td>% Solids</td>
      </tr>
      <tr align="center">
        <td><input id="point_process_names1" name="point_process_names1" type="text" size="15" maxlength="50"></td>
        <td><input id="point_process_size1" name="point_process_size1" type="text" size="10" maxlength="50"></td>
        <td><select class='selectObj' id="sel_point_process_matl1" name="sel_point_process_matl1">
            <option value="---select---" selected>---select---</option>
            <option value="Concrete">Concrete</option>
            <option value="PVC">PVC</option>
            <option value="CS">CS</option>
            <option value="SST">SST</option>
            <option value="Other">Other</option>
          </select></td>
        <td><input id="point_process_psig1" name="point_process_psig1" type="text" size="10" maxlength="50"></td>
        <td><input id="point_process_temp1" name="point_process_temp1" type="text" size="10" maxlength="50"></td>
        <td><input id="point_process_quill1" name="point_process_quill1" type="text" size="22" maxlength="50"></td>
        <td><input id="point_process_solids1" name="point_process_solids1" type="text" size="10" maxlength="50"></td>
      </tr>
      <tr align="center">
        <td><input id="point_process_names2" name="point_process_names2" type="text" size="15" maxlength="50"></td>
        <td><input id="point_process_size2" name="point_process_size2" type="text" size="10" maxlength="50"></td>
        <td><select id="sel_point_process_matl2" name="sel_point_process_matl2">
            <option value="---select---" selected>---select---</option>
            <option value="Concrete">Concrete</option>
            <option value="PVC">PVC</option>
            <option value="CS">CS</option>
            <option value="SST">SST</option>
            <option value="Other">Other</option>
          </select></td>
        <td><input id="point_process_psig2" name="point_process_psig2" type="text" size="10" maxlength="50"></td>
        <td><input id="point_process_temp2" name="point_process_temp2" type="text" size="10" maxlength="50"></td>
        <td><input id="point_process_quill2" name="point_process_quill2" type="text" size="22" maxlength="50"></td>
        <td><input id="point_process_solids2" name="point_process_solids2" type="text" size="10" maxlength="50"></td>
      </tr>
      <tr align="center">
        <td><input id="point_process_names3" name="point_process_names3" type="text" size="15" maxlength="50"></td>
        <td><input id="point_process_size3" name="point_process_size3" type="text" size="10" maxlength="50"></td>
        <td><select id="sel_point_process_matl3" name="sel_point_process_matl3">
            <option value="---select---" selected>---select---</option>
            <option value="Concrete">Concrete</option>
            <option value="PVC">PVC</option>
            <option value="CS">CS</option>
            <option value="SST">SST</option>
            <option value="Other">Other</option>
          </select></td>
        <td><input id="point_process_psig3" name="point_process_psig3" type="text" size="10" maxlength="50"></td>
        <td><input id="point_process_temp3" name="point_process_temp3" type="text" size="10" maxlength="50"></td>
        <td><input id="point_process_quill3" name="point_process_quill3" type="text" size="22" maxlength="50"></td>
        <td><input id="point_process_solids3" name="point_process_solids3" type="text" size="10" maxlength="50"></td>
      </tr>
    </table>
  </div>
  <!-- END SECTION BLOCK -->
  
  <div class='sectionBlock'>
    <div class='sectionBlockTitle'>Dose Line Details</div>
  </div>
  <div class=''>
    <table width="830px"  border="1" cellspacing="0" bordercolor="#666666" style="padding-left:-5px; margin-top:12px">
      <tr style="font-weight:800; font-size:smaller" align="center" class="category-row_complete">
        <td>Name</td>
        <td>Size</td>
        <td>Length/Rise</td>
        <td>Matl</td>
        <td>Containment<br />
          Type/Size</td>
        <td>Carrier<br />
          Water<br />
          (type)</td>
        <td>Carrier<br />
          Water Flow<br />
          (gpm)</td>
        <td>Carrier<br />
          Water Pressure<br />
          (psig)</td>
      </tr>
      <tr align="center">
        <td><input id="dose_line_name1" name="dose_line_name1" type="text" size="15" maxlength="50"></td>
        <td><input id="dose_line_size1" name="dose_line_size1" type="text" size="5" maxlength="10"></td>
        <td><input id="dose_line_length1" name="dose_line_length1" type="text" size="10" maxlength="50"></td>
        <td><select class='selectObj' id="sel_dose_line_matl1" name="sel_dose_line_matl1">
            <option value="---select---" selected>---select---</option>
            <option value="PVC">PVC</option>
            <option value="SST">SST</option>
            <option value="Poly">Poly</option>
            <option value="HDPE">HDPE</option>
          </select></td>
        <td><select class='selectObj' id="sel_dose_line_containment1" name="sel_dose_line_containment1">
            <option value="---select---" selected>---select---</option>
            <option value="PVC">PVC</option>
            <option value="None">None</option>
          </select></td>
        <td><select class='selectObj' id="sel_dose_line_water_type1" name="sel_dose_line_water_type1">
            <option value="---select---" selected>---select---</option>
            <option value="Potable">Potable</option>
            <option value="Process">Process</option>
            <option value="None">None</option>
          </select></td>
        <td><input id="dose_line_flow1" name="dose_line_flow1" type="text" size="10" maxlength="50"></td>
        <td><input id="dose_line_pressure1" name="dose_line_pressure1" type="text" size="10" maxlength="50"></td>
      </tr>
      <tr align="center">
        <td><input id="dose_line_name2" name="dose_line_name2" type="text" size="15" maxlength="50"></td>
        <td><input id="dose_line_size2" name="dose_line_size2" type="text" size="5" maxlength="10"></td>
        <td><input id="dose_line_length2" name="dose_line_length2" type="text" size="10" maxlength="50"></td>
        <td><select id="sel_dose_line_matl2" name="sel_dose_line_matl2">
            <option value="---select---" selected>---select---</option>
            <option value="PVC">PVC</option>
            <option value="SST">SST</option>
            <option value="Poly">Poly</option>
            <option value="HDPE">HDPE</option>
          </select></td>
        <td><select id="sel_dose_line_containment2" name="sel_dose_line_containment2">
            <option value="---select---" selected>---select---</option>
            <option value="PVC">PVC</option>
            <option value="None">None</option>
          </select></td>
        <td><select id="sel_dose_line_water_type2" name="sel_dose_line_water_type2">
            <option value="---select---" selected>---select---</option>
            <option value="Potable">Potable</option>
            <option value="Process">Process</option>
            <option value="None">None</option>
          </select></td>
        <td><input id="dose_line_flow2" name="dose_line_flow2" type="text" size="10" maxlength="50"></td>
        <td><input id="dose_line_pressure2" name="dose_line_pressure2" type="text" size="10" maxlength="50"></td>
      </tr>
      <tr align="center">
        <td><input id="dose_line_name3" name="dose_line_name3" type="text" size="15" maxlength="50"></td>
        <td><input id="dose_line_size3" name="dose_line_size3" type="text" size="5" maxlength="10"></td>
        <td><input id="dose_line_length3" name="dose_line_length3" type="text" size="10" maxlength="50"></td>
        <td><select id="sel_dose_line_matl3" name="sel_dose_line_matl3">
            <option value="---select---" selected>---select---</option>
            <option value="PVC">PVC</option>
            <option value="SST">SST</option>
            <option value="Poly">Poly</option>
            <option value="HDPE">HDPE</option>
          </select></td>
        <td><select id="sel_dose_line_containment3" name="sel_dose_line_containment3">
            <option value="---select---" selected>---select---</option>
            <option value="PVC">PVC</option>
            <option value="None">None</option>
          </select></td>
        <td><select id="sel_dose_line_water_type3" name="sel_dose_line_water_type3">
            <option value="---select---" selected>---select---</option>
            <option value="Potable">Potable</option>
            <option value="Process">Process</option>
            <option value="None">None</option>
          </select></td>
        <td><input id="dose_line_flow3" name="dose_line_flow3" type="text" size="10" maxlength="50"></td>
        <td><input id="dose_line_pressure3" name="dose_line_pressure3" type="text" size="10" maxlength="50"></td>
      </tr>
    </table>
    <div class='sectionBlockContainer'>
      <div class='label-field-left'>
        <div class='label' style="width:220px">Trench Details (if applicable):</div>
        <div class='field'>
          <input id="trench_details" name="trench_details" type="text" size="35" maxlength="150">
        </div>
      </div>
      <div style="clear:both"></div>
    </div>
    <!-- END SECTION BLOCK -->
    
    <div class='sectionBlock'>
      <div class='sectionBlockTitle'>Tank System Details</div>
    </div>
    <div class=''>
      <table width="830px" border="1" cellspacing="0" bordercolor="#666666" style="padding-left:-5px; margin-top:12px">
        <tr align="center">
          <td colspan="2" class="reducedTableCell">Existing Customer Tank (Y/N)</td>
          <td><select class='selectObj' name="sel_tank_details_existing_tank" id="sel_tank_details_existing_tank">
              <option value="---select---" selected>---select---</option>
              <option value="Yes">Yes</option>
              <option value="No">No</option>
            </select></td>
          <td colspan="2" class="reducedTableCell">If yes, describe in detail:</td>
          <td align="left"><textarea name="tank_details_existing_tank_details" id="tank_details_existing_tank_details" cols="20" rows="3"></textarea></td>
        </tr>
        <tr align="center">
          <td class="reducedTableCell">No. Tanks</td>
          <td><select class='selectObj' name="sel_tank_details_tank_count" id="sel_tank_details_tank_count">
              <option value="1" selected>1</option>
              <option value="2">2</option>
            </select></td>
          <td class="reducedTableCell">Tank Capacity</td>
          <td><select class='selectObj' name="sel_tank_details_tank_capacity" id="sel_tank_details_tank_capacity">
              <option value="---select---" selected>---select---</option>
              <option value="750">750</option>
              <option value="1100">1100</option>
              <option value="1550">1500</option>
              <option value="2100">2100</option>
              <option value="3000">3000</option>
              <option value="5000">5000</option>
              <option value="6000">6000</option>
              <option value="6500">6500</option>
              <option value="7800">7800</option>
              <option value="8400">8400</option>
              <option value="10500">10500</option>
              <option value="Other">Other</option>
            </select></td>
          <td class="reducedTableCell">Total Capacity</td>
          <td align="left"><input class="required" id="tank_details_tank_total_capacity" name="tank_details_tank_total_capacity" type="text" size="6" maxlength="10" onkeypress="return numbersonly(this, event)"></td>
        </tr>
        <tr align="center">
          <td class="reducedTableCell">Tank Orientation</td>
          <td><select class='selectObj' name="sel_tank_details_orientation" id="sel_tank_details_orientation">
              <option value="Vertical" selected>Vertical</option>
              <option value="Horizontal">Horizontal</option>
            </select></td>
          <td class="reducedTableCell">Tank Wall Type</td>
          <td><select class='selectObj' name="sel_tank_details_tank_wall" id="sel_tank_details_tank_wall">
              <option value="---select---" selected>---select---</option>
              <option value="Single Wall">Single Wall</option>
              <option value="Double Wall">Double Wall</option>
              <option value="Other">Other</option>
            </select></td>
          <td class="reducedTableCell">Inverse Level (Y/N)</td>
          <td align="left"><select class='selectObj' name="sel_tank_details_inverse" id="sel_tank_details_inverse">
              <option value="---select---" selected>---select---</option>
              <option value="Yes">Yes</option>
              <option value="No">No</option>
            </select></td>
        </tr>
        <tr align="center">
          <td class="reducedTableCell">Tank Height</td>
          <td align="left"><input class='required' id="tank_details_height" name="tank_details_height" type="text" size="7" maxlength="5" onkeypress="return numbersonly(this, event)">
            in.</td>
          <td class="reducedTableCell">Inner Tank Diameter</td>
          <td align="left"><input class='required' id="tank_inner_diameter" name="tank_inner_diameter" type="text" size="7" maxlength="5" onkeypress="return numbersonly(this, event)">
            in.</td>
          <td class="reducedTableCell">Outer Tank Diameter</td>
          <td align="left"><input id="tank_outer_diameter" name="tank_outer_diameter" type="text" size="7" maxlength="5" onkeypress="return numbersonly(this, event)">
            in.</td>
        </tr>
        <tr align="center">
          <td class="reducedTableCell">Level Sensor (type)</td>
          <td align="left"><input id="tank_details_level_sensor" name="tank_details_level_sensor" type="text" size="12" maxlength="12"></td>
          <td class="reducedTableCell">Fill Connection (type)</td>
          <td align="left"><input id="tank_fill_connection" name="tank_fill_connection" type="text" size="12" maxlength="12"></td>
          <td class="reducedTableCell">Access Ladder (Y/N)</td>
          <td align="left"><select class='selectObj' name="sel_tank_details_access_ladder" id="sel_tank_details_access_ladder">
              <option value="---select---" selected>---select---</option>
              <option value="Yes">Yes</option>
              <option value="No">No</option>
            </select></td>
        </tr>
        <tr align="center">
          <td colspan="2" class="reducedTableCell">Site Containment System</td>
          <td colspan="4" align="left"><input id="tank_details_site_containment" name="tank_details_site_containment" type="text" size="25" maxlength="150"></td>
        </tr>
      </table>
    </div>
    <!-- END SECTION BLOCK -->
    
    <div class='sectionBlock'>
      <div class='sectionBlockTitle'>Pump System Details</div>
    </div>
    <div class=''>
      <table width="830px" border="1" cellspacing="0" bordercolor="#666666" style="padding-left:-5px; margin-top:12px">
        <tr align="center">
          <td width="151" class="reducedTableCell">No. of Skids</td>
          <td width="185" align="left"><input id="pump_skid_count" name="pump_skid_count" type="text" size="7" maxlength="5" onkeypress="return numbersonly(this, event)"></td>
          <td width="90" class="reducedTableCell">Pumps per Skid</td>
          <td width="147" align="left"><input id="pumps_per_skid" name="pumps_per_skid" type="text" size="7" maxlength="5" onkeypress="return numbersonly(this, event)"></td>
          <td width="89" class="reducedTableCell">Enclosure Type</td>
          <td width="117" align="left"><select class='selectObj' name="sel_pump_enclosure_type" id="sel_pump_enclosure_type">
              <option value="---select---" selected>---select---</option>
              <option value="Large">Large</option>
              <option value="Small">Small</option>
              <option value="None">None</option>
            </select></td>
        </tr>
        <tr align="center">
          <td class="reducedTableCell">Dose Range</td>
          <td align="left"><input id="dose_range" name="dose_range" type="text" size="9" maxlength="9">
            min-max gph</td>
          <td class="reducedTableCell">Pump Model</td>
          <td align="left"><input id="pump_model" name="pump_model" type="text" size="9" maxlength="9"></td>
          <td class="reducedTableCell">Pump Size</td>
          <td align="left"><input id="pump_size" name="pump_size" type="text" size="12" maxlength="12"></td>
        </tr>
        <tr align="center">
          <td class="reducedTableCell">Cal Cylinder</td>
          <td align="left"><select class='selectObj' name="sel_cal_cylinder" id="sel_cal_cylinder">
              <option value="---select---" selected>---select---</option>
              <option value="Yes">Yes</option>
              <option value="No">No</option>
            </select></td>
          <td class="reducedTableCell">Cylinder Size</td>
          <td align="left"><select class='selectObj' name="sel_cylinder_size" id="sel_cylinder_size">
              <option value="---select---" selected>---select---</option>
              <option value="100 mL PVC">100 mL PVC</option>
              <option value="500 mL PVC">500 mL PVC</option>
              <option value="1000 mL PVC">1000 mL PVC</option>
              <option value="2000 mL PVC">2000 mL PVC</option>
              <option value="4000 mL PVC">4000 mL PVC</option>
              <option value="10000 mL PVC">10000 mL PVC</option>
              <option value="20000 mL PVC">20000 mL PVC</option>
              <option value="Other">Other</option>
            </select></td>
          <td class="reducedTableCell">BPV Setting</td>
          <td align="left"><input id="bpv_setting" name="bpv_setting" type="text" size="7" maxlength="5"></td>
        </tr>
        <tr align="center" valign="middle">
          <td class="reducedTableCell">Dose Control Type</td>
          <td align="left" colspan="5">Analog Signal Source:
            <input type="checkbox" name="chk_dose_control_type_scada" id="chk_dose_control_type_scada">
            SCADA&nbsp;&nbsp;
            <input type="checkbox" name="chk_dose_control_type_dcs" id="chk_dose_control_type_dcs">
            DCS&nbsp;&nbsp;
            <input type="checkbox" name="chk_dose_control_type_cw" id="chk_dose_control_type_cw">
            CW&nbsp;&nbsp;
            <input type="checkbox" name="chk_dose_control_type_timer" id="chk_dose_control_type_timer">
            Timer&nbsp;&nbsp;List Other:
            <input id="chk_dose_control_type_other" name="chk_dose_control_type_other" type="text" size="25" maxlength="150"></td>
        </tr>
        <tr align="center" valign="middle">
          <td colspan="2" class="reducedTableCell">List Process Variables <span style="font-size:smaller">(high pressure, pump inactive, etc)</span></td>
          <td align="left" colspan="4"><input id="pump_process_variables" name="pump_process_variables" type="text" size="25" maxlength="150"></td>
        </tr>
        <tr align="center" valign="middle">
          <td class="reducedTableCell">Does site have an interlocked system?</td>
          <td align="left" colspan="5"><input type="checkbox" name="chk_interlocked_low_voltage" id="chk_interlocked_low_voltage">
            Low Voltage Discrete&nbsp;&nbsp;
            <input type="checkbox" name="chk_interlocked_power_cutoff" id="chk_interlocked_power_cutoff">
            Main Power Cut-Off&nbsp;&nbsp;
            <input type="checkbox" name="chk_interlocked_power_other" id="chk_interlocked_power_other">
            Other:&nbsp;&nbsp;
            List:
            <input id="interlocked_other_list" name="interlocked_other_list" type="text" size="25" maxlength="150"></td>
        </tr>
      </table>
      <div class="sectionBlockContainer">
        <div class='label-field-left' style="text-align:left;width:750px">
          <div class='label' style="text-align:left;width:350px">Additional Equipment Requirements (describe)</div>
          <div>
            <input style="text-align:left;width:320px" id="equipment_requirements" name="equipment_requirements" type="text" size="35" maxlength="250">
          </div>
        </div>
        <div style="clear:both"></div>
        <div class='label-field-left' style="text-align:left;width:750px">
          <div class='label' style="text-align:left;width:350px">Foundation Provided (describe)</div>
          <div>
            <input style="text-align:left;width:320px" id="pump_foundation_provided" name="pump_foundation_provided" type="text" size="35" maxlength="250">
          </div>
        </div>
        <div style="clear:both"></div>
      </div>
    </div>
    <!-- END SELECTION BLOCK -->
    
    <div class='sectionBlock'>
      <div class='sectionBlockTitle'>Monitor / Controller Details</div>
    </div>
    <div class=''>
      <table width="830px" border="1" cellspacing="0" bordercolor="#666666" style="padding-left:-5px; margin-top:12px">
        <tr align="center">
          <td class="">Monitor Type</td>
          <td align="left"><input type="checkbox" name="chk_monitor_type_cwv2" id="chk_monitor_type_cwv2">
            CW V2&nbsp;&nbsp;
            <input type="checkbox" name="chk_monitor_type_cwv1" id="chk_monitor_type_cwv1">
            CW V1&nbsp;&nbsp;
            <input type="checkbox" name="chk_chk_monitor_type_xi" id="chk_monitor_type_xi">
            XI&nbsp;&nbsp;
            <input type="checkbox" name="chk_monitor_type_other" id="chk_monitor_type_other" onChange="$('#chk_monitor_type_other_list').val('ACS')">
            other&nbsp;&nbsp;
            List:
            <input id="chk_monitor_type_other_list" name="chk_monitor_type_other_list" type="text" size="25" maxlength="150"></td>
        </tr>
        <tr align="center">
          <td class="">Signal Type</td>
          <td align="left"><input type="checkbox" name="chk_signal_type_cell" id="chk_signal_type_cell">
            Cell&nbsp;&nbsp;
            <input type="checkbox" name="chk_signal_type_landline" id="chk_signal_type_landline">
            Landline&nbsp;&nbsp;
            <input type="checkbox" name="chk_signal_type_satellite" id="chk_signal_type_satellite">
            Satellite XI&nbsp;&nbsp;
            <input type="checkbox" name="chk_signal_type_none" id="chk_signal_type_none">
            None&nbsp;&nbsp;
            List Other:
            <input id="chk_signal_type_other_list" name="chk_signal_type_other_list" type="text" size="25" maxlength="150"></td>
        </tr>
      </table>
    </div>
    <!-- END SELECTION BLOCK --> 
    
    <!-- CONTAINMENT / FOUNDATION BLOCK -->
    <div class='sectionBlock'>
      <div class='sectionBlockTitle'>Containment / Foundation</div>
    </div>
    <div class='sectionBlockContainer'>
      <div class='label-field-left'>
        <div class='label'>Foundation Type</div>
        <div class='field'>
          <select class='selectObj' name="sel_foundation_type" id="sel_foundation_type">
            <option value="---select---" selected>---select---</option>
            <option value="Dirt">Dirt</option>
            <option value="Gravel">Gravel</option>
            <option value="Sand">Sand</option>
            <option value="Concrete Pad">Concrete Pad</option>
            <option value="Asphalt">Asphalt</option>
          </select>
        </div>
        <div style="clear:both"></div>
      </div>
      <div class='label-field-left'>
        <div class='label'>Containment Type</div>
        <div class='field'>
          <select class='selectObj' name="sel_containment_type" id="sel_containment_type">
            <option value="---select---" selected>---select---</option>
            <option value="Concrete">Concrete</option>
            <option value="Berm">Berm</option>
            <option value="USP Steel">USP Steel</option>
            <option value="None">None</option>
          </select>
        </div>
        <div style="clear:both"></div>
      </div>
      <div style="clear:both"></div>
      <div class='label-field-left'>
        <div class='label'>Additional Comments</div>
        <div class='field'>
          <textarea id="containment_comments" name="containment_comments" cols="50" rows="3"></textarea>
        </div>
        <div style="clear:both"></div>
      </div>
      <div style="clear:both"></div>
    </div>
    <!-- END CONTAINMENT / FOUNDATION BLOCK --> 
    
    <!-- MISC -->
    <div class='sectionBlock'>
      <div class='sectionBlockTitle'>Miscellaneous</div>
    </div>
    <div class=''>
      <table width="830px" border="1" cellspacing="0" bordercolor="#666666" style="padding-left:-5px; margin-top:12px">
        <tr align="center">
          <td width="224" class="reducedTableCell">New or Existing Peroxide Customer?</td>
          <td width="144" align="left"><select class='selectObj' name="sel_new_existing_peroxide_user" id="sel_new_existing_peroxide_user">
              <option value="---select---" selected>---select---</option>
              <option value="New">New</option>
              <option value="Existing">Existing</option>
            </select></td>
          <td width="154" class="reducedTableCell">What is peroxide being used for?</td>
          <td width="265" align="left"><input id="peroxide_use" name="peroxide_use" type="text" size="20" maxlength="75"></td>
        </tr>
        <tr align="center">
          <td class="reducedTableCell">Have new installations been properly (and recently) passivated?  (Y/N)</td>
          <td align="left"><select class='selectObj' name="sel_passivated" id="sel_passivated">
              <option value="---select---" selected>---select---</option>
              <option value="Yes">Yes</option>
              <option value="No">No</option>
            </select></td>
          <td class="reducedTableCell">If passivated provide date</td>
          <td align="left"><input name="passivated_date" type="text" id="passivated_date" size="15" maxlength="15"></td>
        </tr>
        <tr align="center">
          <td class="reducedTableCell">What is the fill line material?</td>
          <td align="left"><input name="fill_material" type="text" id="fill_material" size="20" maxlength="50"></td>
          <td class="reducedTableCell">Fill line connection size</td>
          <td align="left"><input name="fill_line_connection_size" type="text" id="fill_line_connection_size" size="15" maxlength="15">
            <span style="font-size:smaller">
            <input type="radio" id='rdo_connection_type' name='rdo_connection_type' value="male">
            Male
            <input type="radio" id='rdo_connection_type' name='rdo_connection_type' value="female" checked>
            Female </span></td>
        </tr>
        <tr align="center">
          <td class="reducedTableCell">Is there an anti-siphon valve on dose line?</td>
          <td align="left"><select class='selectObj' name="sel_anti_siphon_valve" id="sel_anti_siphon_valve">
              <option value="---select---" selected>---select---</option>
              <option value="Yes">Yes</option>
              <option value="No">No</option>
            </select></td>
          <td class="reducedTableCell">What is the venting area on tank?</td>
          <td align="left"><input name="tank_venting_area" type="text" id="tank_venting_area" size="15" maxlength="15"></td>
        </tr>
      </table>
    </div>
    <!-- END MISC -->
    <div style="width:830px">
      <div class="label-field-right" style="padding-top:20px"> 
      <span class='navDiv'><a href="javascript:processSection(3, 'back')"><img src="images/go-back-to-section-2.png" alt="Back to Section 2" border="0" ></a></span> 
      <span class='postDiv'><a href="javascript:processSection(3, 'commit')"><img src="images/save-Form.png" alt="Save Form" border="0" ></a></span> 
<?php if ( $_SESSION['USERTYPE'] == 'super' && $committed==1 && $complete==0): ?>  
      <span class='mapDiv'><a href="javascript:mapToCOMS()"><img src="images/post-Form.png" alt="Post to COMS" border="0" ></a></span> 
<?php endif; ?>
      </div>
      <div style="clear:both"></div>
    </div>
  </div>
  <!-- SECTION 3 END --> 
</div>
<div id='thank_you' class='sectionBlockTitle' style="padding-top:125px; padding-bottom:400px; text-align:center; display:block; font-size:larger"> Your New Customer Form has been submitted to COMS </div>
</div>
<!-- end newCustomerDiv -->

<div id="dialog" title="Message"></div>
<div style="float:left">
  <div id='customerLogin' style="visibility:hidden">
    <?php echo $CUSTOMER_EMAIL?>
  </div>
</div>
<div id='debugDiv'></div>
</body>
</html>