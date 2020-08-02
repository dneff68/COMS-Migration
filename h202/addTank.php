<?
session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once '/var/www/html/CHT/h202/h202Functions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';

if (empty($USERID) || empty($USERTYPE))
{
	header("location:/");
	die;
}

if ($init=='yes')
{
	if (!empty($ADDTANK3))
	{
		$ADDTANK3['monitorID'] = '';
		$ADDTANK3['tankName'] = '';
		$ADDTANK3['concentration'] = '';
	}
	
	$ADDTANK1 = '';
	$ADDTANK2 = '';
	$ADDTANK3 = '';
	$editMonitor = '';
	unset($ADDTANK1);
	unset($ADDTANK2);
	unset($ADDTANK3);
	unset($editMonitor);
	session_unregister('ADDTANK1');
	session_unregister('ADDTANK2');
	session_unregister('ADDTANK3');

	$editMonitor = '';
	unset($editMonitor);
	//die($editMonitor);
}

if (!empty($mon))
{
	// edit tank 
	$res = getResult("SELECT monitorID FROM tank where monitorID='$mon' LIMIT 1");
	if (checkResult($res))
	{
		$editMonitor = $mon;
	}
	else
	{
		bigecho("SELECT monitorID FROM tank where monitorID='$mon' LIMIT 1");
		die("Error: That tank does not exist");
	}
}

//bigecho($editMonitor);
if ($REQUEST_METHOD == 'POST')
{
	// store values in an array
	if ($page == 1)
	{
		if (empty($ADDTANK1))
		{
			session_register('ADDTANK1');
		}
		$ADDTANK1 = $_POST;
	}
	elseif ($page == 2)
	{
		if (empty($ADDTANK2))
		{
			session_register('ADDTANK2');
		}
		$ADDTANK2 = $_POST;
	}
	elseif ($page == 3)
	{
		if (empty($ADDTANK3))
		{
			session_register('ADDTANK3');
		}
		$ADDTANK3 = $_POST;
				
		if ($addTankAction == 'addtank')
		{
			// Add the tank and get outta here
			if (!empty($editMonitor))
			{
				$err = addTank($ADDTANK1, $ADDTANK2, $ADDTANK3, $editMonitor);
				$msg = "Tank%20Successfully%20Modified";
			}
			else
			{
				$err = addTank($ADDTANK1, $ADDTANK2, $ADDTANK3);
				$msg = "Tank%20Successfully%20Added";
			}

			if ($err !== 0)
			{
				// problem
				list($gotopage, $addTankError) = explode(',', $err);
				$addTankError = "&addTankError=$addTankError";
			}
			else
			{
				$ADDTANK1 = '';
				$ADDTANK2 = '';
				$ADDTANK3 = '';
				$editMonitor = '';
				unset($ADDTANK1);
				unset($ADDTANK2);
				unset($ADDTANK3);
				unset($editMonitor);
				session_unregister('ADDTANK1');
				session_unregister('ADDTANK2');
				session_unregister('ADDTANK3');
				header("location:/index.php?msg=$msg");
				die;
			}
		}
	}
	
	if (!empty($gotopage))
	{
		$page = $gotopage;
	}
	if (!empty($zipcode))
	{
		$addTankAction .= "&zipcode=$zipcode";
	}
	$lnkMod = empty($addTankAction) ? '' : "&addTankAction=$addTankAction";
	header("location:/addTank.php?page=$page$lnkMod" . $addTankError);
}


if (!empty($ADDTANK1))
{
	unset($ADDTANK1['page']);
	unset($ADDTANK1['gotopage']);
	unset($ADDTANK1['addTankAction']);

//	showArray($ADDTANK1);
	extract($ADDTANK1);
}
if (!empty($ADDTANK2))
{
	unset($ADDTANK2['page']);
	unset($ADDTANK2['gotopage']);
	unset($ADDTANK2['addTankAction']);

//	showArray($ADDTANK2);
	extract($ADDTANK2);
}
if (!empty($ADDTANK3))
{
	unset($ADDTANK3['page']);
	unset($ADDTANK3['gotopage']);
	unset($ADDTANK3['addTankAction']);
	extract($ADDTANK3);
}

if ($addTankAction == 'lookupZip')
{
	$query = "select city, state from zipcodes where zip = '$zipcode' LIMIT 1";
	$res = getResult($query);
	if (checkResult($res))
	{
		$line = mysql_fetch_assoc($res);
		extract($line);
		$js = "document.addTankForm.address.focus()";
	}
	else
	{
		$city = '';
		$state = '';
	}
}
elseif ($addTankAction == 'lookupSite')
{
	if ($selCustomerSite == '--none--')
		return;
		
	$query = "select siteLocationName, address, city, state, zip as 'zipcode', contact, regionID, contactPhone, contactEmail from site where siteID=$selCustomerSite LIMIT 1";
	$res = getResult($query);
	if (checkResult($res))
	{
		$line = mysql_fetch_assoc($res);
		extract($line);
	}
}
elseif ($addTankAction == 'lookupSupplier')
{
	if ($supplier=='--none')
		return;
		
	$query = "select supplierID, supplierName, contact as supplierContact, email as supplierEmail, phone as supplierPhone 
	from supplier where supplierID=$supplier LIMIT 1";
	$res = getResult($query);
	if (checkResult($res))
	{
		$line = mysql_fetch_assoc($res);
		extract($line);
	}
}

elseif ($addTankAction == 'lookupCarrier')
{
	if ($carrier != '--none--')
	{
		$query = "select carrierID, carrierName, contact as carrierContact, email as carrierEmail, phone as carrierPhone 
		from carrier where carrierID=$carrier LIMIT 1";
		$res = getResult($query);
		if (checkResult($res))
		{
			$line = mysql_fetch_assoc($res);
			extract($line);
		}
	}
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>New Tank</title>
<link rel="stylesheet" TYPE="text/css" href="http://h202.customhostingtools.com/main.css" >
<SCRIPT LANGUAGE="javascript" TYPE="text/javascript" SRC='http://www.customhostingtools.com/lib/admin.js'></SCRIPT>
<script language="javascript">
function gotopage(pageno)
{
	document.addTankForm.gotopage.value = pageno;
	document.addTankForm.submit();
}

function lookupZip(zip)
{
	if (zip != '')
	{
		document.addTankForm.addTankAction.value='lookupZip';
		document.addTankForm.submit();
	}
}

function lookupSite(siteid)
{
	document.addTankForm.addTankAction.value='lookupSite';
	document.addTankForm.submit();
}

function lookupSupplier(supplierid)
{
	document.addTankForm.addTankAction.value='lookupSupplier';
	document.addTankForm.submit();
}

function lookupCarrier(carrierid)
{
	document.addTankForm.addTankAction.value='lookupCarrier';
	document.addTankForm.submit();
}

function clearSiteVals(val)
{
	if (val.length >= 0)
	{
		document.addTankForm.selCustomerSite.selectedIndex = 0;
		document.addTankForm.regionID.selectedIndex = 0;
		document.addTankForm.zipcode.value = '';
		document.addTankForm.address.value = '';
		document.addTankForm.city.value = '';
		document.addTankForm.state.selectedIndex = 0;
		document.addTankForm.contact.value = '';
		document.addTankForm.phone.value = '';
		document.addTankForm.email.value = '';

		document.addTankForm.zipcode.disabled=false;
		document.addTankForm.address.disabled=false;
		document.addTankForm.city.disabled=false;
		document.addTankForm.state.disabled=false;
		document.addTankForm.regionID.disabled=false;
		document.addTankForm.contact.disabled=false;
		document.addTankForm.phone.disabled=false;
		document.addTankForm.email.disabled=false;

	}
}

function clearSupplierVals(val)
{
	if (val != '')
	{
		document.addTankForm.supplier.selectedIndex = 0;
		document.addTankForm.supplierContact.value = '';
		document.addTankForm.supplierPhone.value = '';
		document.addTankForm.supplierEmail.value = '';
		document.addTankForm.supplierContact.disabled=false;
		document.addTankForm.supplierPhone.disabled=false;
		document.addTankForm.supplierEmail.disabled=false;
	}
}

function clearCarrierVals(val)
{
	if (val != '')
	{
		document.addTankForm.carrier.selectedIndex = 0;
		document.addTankForm.carrierContact.value = '';
		document.addTankForm.carrierPhone.value = '';
		document.addTankForm.carrierEmail.value = '';
		document.addTankForm.carrierContact.disabled=false;
		document.addTankForm.carrierPhone.disabled=false;
		document.addTankForm.carrierEmail.disabled=false;
	}
}

</script>

</head>
<body onload="<?=$js?>">
<?
include 'banner.php';

$popframe = getPopupFrame();
echo $popframe;

//if (!david())
//{
//	echo("<h3>Hi Jim: I wasn't quite able to get add tank finished.  I'll let you know when it's ready.</h3>");
//	die;
//}
?>


<? if (empty($editMonitor)) : ?>
	<p align="center" class="spinLargeTitle style1">Add New Tank</p>
<? else : ?>
	<p align="center" class="spinLargeTitle style1">Edit Tank: <?=$editMonitor?></p>
<? endif; ?>
<p align="center" class="spinAlert style1"><?=empty($addTankError) ? '&nbsp;' : $addTankError?></p>
<?
if ($page == 1 || empty($page))
{
	include "addTank1.php";
}
elseif ($page == 2)
{
	include "addTank2.php";
}
elseif ($page == 3)
{
	include "addTank3.php";
}

?>

</body>
</html>