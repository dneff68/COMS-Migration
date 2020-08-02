<?
session_start();
include_once 'GlobalConfig.php';
include_once 'h202Functions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';

	if (empty($id))
	{
		die("Delivery manifest does not exist.");
	}
	
	if (!empty($key))
	{
		$fullView = true;
	}
	elseif (empty($custid))
	{
		// no key passed and no custid passed.  Bail
		die("Invalid delivery manifest.");
	}
	
	if ($disp == 0)
	{
		$eid = fixSingleQuotes($eid);
		executeQuery("UPDATE deliveryEmailLog SET dateReceived=NOW() where deliveryID=$id and id='$eid' and dateReceived = '0000-00-00 00:00:00'");
	}
	
	if (!$fullView)
	{
//		$siteCond = "and s.siteID = $custid";
		$siteCond = "and s.po = '$custid'";
	}
	
	$query = "SELECT DISTINCT 
				IF(SUBSTRING(t.time, 1,2)='12','00', SUBSTRING(t.time, 1,2)) AS noon, 
				SUBSTRING(t.time, 6) as ampm, 
				site.address, 
				site.city, 
				site.state, 
				site.zip, 
				s.PO, 
				t.monitorID, 
				t.quantity, 
				t.deliveryUnitQuantity,
				t.notes, 
				t.time
			FROM site, deliverySite s, deliveryTanks t
			WHERE site.siteID = s.siteID
			AND t.siteID = s.siteID
			AND s.deliveryID = t.deliveryID
			$siteCond
			AND s.deliveryID = $id
			ORDER BY ampm, noon, t.time, s.PO" ;
	$siteres = getResult($query);
//	ddie($query);
	
	$res = getResult("SELECT 
							DATE_FORMAT(d.dateOrdered, '%W, %M %d, %Y') AS dateOrdered, 
							DATE_FORMAT(d.deliveryDate, '%m/%d/%Y') AS deliveryDate, 
							d.supplierID, 
							d.carrierID, 
							d.concentration, 
							d.product, 
							d.deliveryKey, 
							d.status 
						FROM delivery d
						WHERE d.deliveryID=$id");
	if (!checkResult($res))
	{
		die("Invalid delivery manifest.");
	}

	//echoResults($res);

	$line = mysql_fetch_assoc($res);
	extract($line);
	
	$res = getResult("SELECT s.supplierName, s.contact as supplierContact FROM supplier s WHERE supplierID = $supplierID");
	if (checkResult($res))
	{
		$line = mysql_fetch_assoc($res);
		extract($line);
	}
	
	$res = getResult("SELECT CONCAT(u.firstName, ' ', u.lastName) AS internalContact, u.phone as internalPhone FROM delivery d, users u WHERE d.internalContact=u.loginID 
			and d.deliveryID=$id LIMIT 1");
	if (checkResult($res))
	{
		$line = mysql_fetch_assoc($res);
		extract($line);
	}
	
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Delivery Manifest</title>
<link rel="stylesheet" TYPE="text/css" href="http://h202.customhostingtools.com/main.css" >
<SCRIPT LANGUAGE="javascript" TYPE="text/javascript" SRC='http://www.customhostingtools.com/lib/admin.js'></SCRIPT>
<style type="text/css">
<!--
.style1 {font-weight: bold}
.style2 {font-size: 28pt; font-weight: bold}
-->
</style>
</head>

<body>
<table width="700" border="0" cellspacing="1" cellpadding="3">
  <tr>
    <td width="216" rowspan="4" class="spinNormalText"><img src="images/logo.png" alt="USP Technologies" width="216" height="72" class="style1" /></td>
    <td width="230" align="right" valign="middle" class="spinSmallTitle">Date Ordered:</td>
    <td width="230" valign="middle"><?=$dateOrdered?></td>
  </tr>
	<?
		$poRow1 = "&nbsp;";
		$poRow2 = "&nbsp;";
		if (!$fullView)
		{
			if (checkResult($siteres))
			{
				$line = mysql_fetch_assoc($siteres);
				$poRow1 = 'PO:';
				$poRow2 = $line['PO'];
			}
		}		
	
	?>
  <tr>
    <td align="right" valign="middle" class="spinSmallTitle"><?=$poRow1?></td>
    <td valign="middle"><?=$poRow2?></td>
  </tr>
  <tr>
    <td align="right" valign="top" class="spinSmallTitle">&nbsp;</td>
    <td valign="top">&nbsp;</td>
  </tr>
  <tr>
    <td align="right" valign="top" class="spinSmallTitle">&nbsp;</td>
    <td valign="top">&nbsp;</td>
  </tr>
  <tr>
    <td class="spinSmallTitle"><strong>USP Technologies<br />
900 Circle 75 Parkway<br />Suite 1330<br />
Atlanta, GA 30339<br />
(877) 346-4262 </strong></td>

<?

	if ($status != 'Ordered')
	{
		$alertNotice = $status;
	}
?>

    <td colspan="2" align="center" valign="middle" class="spinAlert style2"><?=$alertNotice?></td>
  </tr>
  <tr>
    <td class="spinNormalText">&nbsp;</td>
    <td colspan="2" align="right" valign="top" bordercolor="#000000" class="spinMedTitle"><div align="center">CONTACT INFORMATION </div></td>
  </tr>
  <tr>
    <td class="spinNormalText">&nbsp;</td>
    <td align="right" class="spinSmallTitle"><div align="right">Supplier: </div></td>
    <td align="right" class="spinNormalText"><div align="left"><?=$supplierName?></div></td>
  </tr>
  <? if (!empty($carrierID)): 
  
  $res = getResult("SELECT carrierName, contact as carrierContact, phone as carrierPhone FROM carrier WHERE carrierID = $carrierID");
  if (checkResult($res))
  {
  	$line = mysql_fetch_assoc($res);
	extract($line);
  }
  ?>
  <tr>
    <td class="spinNormalText">&nbsp;</td>
    <td align="right" class="spinSmallTitle"><div align="right">Carrier Name: </div></td>
    <td align="right" class="spinNormalText"><div align="left"><?=$carrierName?></div></td>
  </tr>
  <tr>
    <td class="spinNormalText">&nbsp;</td>
    <td align="right" class="spinSmallTitle"><div align="right">Carrier Phone #: </div></td>
    <td align="right" class="spinNormalText"><div align="left"><?=$carrierPhone?></div></td>
  </tr>
  <? endif; ?>
  <tr>
    <td class="spinMedTitle">
      <p>Deliver To: </p>
 </td>
    <td align="right"><div align="right"></div></td>
    <td align="right"><div align="left"></div></td>
  </tr>
<? 
    if ($fullView)
	{
		$query = "SELECT DISTINCT s.siteID, s.siteLocationName, s.contact as siteContact,
					IF(SUBSTRING(t.time, 1,2)='12','00', SUBSTRING(t.time, 1,2)) AS noon, 
					SUBSTRING(t.time, 6) as ampm 
					FROM deliverySite ds, site s, deliveryTanks t 
					WHERE 
						ds.siteID=s.siteID and 
						s.siteID = t.siteID and
						ds.deliveryID = $id and
						t.deliveryID = $id
					ORDER BY ampm, noon, t.time, ds.PO";
	}
	else
	{
		$query = "SELECT DISTINCT s.siteID, s.siteLocationName, s.contact as siteContact,
					IF(SUBSTRING(t.time, 1,2)='12','00', SUBSTRING(t.time, 1,2)) AS noon, 
					SUBSTRING(t.time, 6) as ampm 
					FROM deliverySite ds, site s, deliveryTanks t
		WHERE 
			ds.siteID=s.siteID and 
			s.siteID = t.siteID and
			ds.po='$custid' and 
			t.deliveryID = $id and
			ds.deliveryID = $id 
		ORDER BY ampm, noon, t.time, ds.PO";
		//die($query);
	}


	// get a list of all the sites that are being delivered to
	$res = getResult($query);

	if (checkResult($res))
	{
		$cnt = 1;
		while ($line = mysql_fetch_assoc($res))
		{
			extract($line);
			$po_out = '';
			if ($fullView)
			{
				$query = "SELECT PO from deliverySite WHERE siteID=$siteID AND deliveryID=$id ORDER BY PO LIMIT 1";
				$pores = getResult($query);
				$poline = mysql_fetch_assoc($pores);
				extract($poline);
				$po_out = "&nbsp;(PO: $PO)";
				$cnt_out = "$cnt: ";
			}			
			else
			{
				$cnt_out = '';
			}
			echo "\n
			  <tr>
				<td class='header_1' nowrap><div align='left'>$cnt_out$siteLocationName $po_out</div></td>
				<td align='right' class='spinSmallTitle'><div align='right'>Customer Contact $cnt_out</div></td>
				<td align='right'><div align='left'>$siteContact</div></td>
			  </tr>\n";
			$cnt++;
		}
	}
?>
</table>
<br />
<table width="700" border="2" cellpadding="3" cellspacing="0" bordercolor="#666666">
  <tr>
    <td class="spinSmallTitle"><div align="right">Delivery Date: </div></td>
    <td class="spinSmallTitle"><div align="left"><?=$deliveryDate?> </div></td>
    <td class="spinSmallTitle"><div align="right">Approved By: </div></td>
    <td><div align="left"><?=$internalContact?> </div></td>
  </tr>
  <tr>
    <td class="spinSmallTitle"><div align="right">Product:</div></td>
<?
	//$pct = strpos('%', $concentration) === false ? '%' : '';
?>
    <td><div align="left"><?= "$product $concentration"?></div></td>
    <td class="spinSmallTitle"><div align="right">Contact Phone: </div></td>
    <td><div align="left"><?=$internalPhone?> </div></td>
  </tr>
</table>
<br />
<?

$cnt = 1;
$totalQuantity = 0;
$totalWeight = 0;
$notesOut = '';

if (checkResult($siteres))
{
	mysql_data_seek($siteres, 0);

	$tableDetailRows = '';
	
	// need to loop through each row and see if we have only Pounds as a delivery unit, a mix of Pounds and other units, or only others
	$anyPounds = false;
	$anyNonPounds = false;
	while ($line = mysql_fetch_assoc($siteres))
	{
		$query = "select deliveryUnits from tank where tankID = '" . $line['monitorID'] . "' AND deliveryUnits = 'Pounds'";
		$tres = getResult($query);
		$anyPounds = (checkResult($tres) || $anyPounds);

		$query = "select deliveryUnits from tank where tankID = '" . $line['monitorID'] . "' AND deliveryUnits != 'Pounds'";
		$tres = getResult($query);
		$anyNonPounds = (checkResult($tres) || $anyNonPounds);
	}
	
	$poundsOnly = $anyPounds && !$anyNonPounds;
	
	//bigecho($poundsOnly ? "Pounds Only" : "Not Pounds Only");
	
  	mysql_data_seek($siteres, 0);
	while ($line = mysql_fetch_assoc($siteres))
	{
		extract($line);
		// get the weight based on the quantity, product, and concentration
		$wRes = getResult("SELECT r.ratio FROM productWeightRatios r, product p WHERE p.prodID=r.prodID and p.value='$product' and '$concentration' LIKE CONCAT('%', r.concentration, '%')");
		if (checkResult($wRes))
		{
			$wLine = mysql_fetch_assoc($wRes);
			extract($wLine);
		}
		else
		{
			$ratio = 1.0;
		}
		$weight = $ratio * $quantity;
				
		// monitorID below needs to be replaced with tankName from the tank table.
		$query = "select deliveryUnits, tankName from tank where tankID = '$monitorID' LIMIT 1";
		$tres = getResult($query);
		if (checkResult($tres))
		{
			$tline = mysql_fetch_assoc($tres);
			extract($tline);
		}
		else
		{
			$tankID = $monitorID;
		}
		
		// if units was passed in the url then override what's in the db
		if (!empty($units))
		{
			$deliveryUnits = $units;
		}
		
		//bigecho("Quantity in Gallons: $quantity");
		$conversionArr = convertUnits($quantity, $deliveryUnits);
		$deliveryUnits = $conversionArr[0];
		$quantity 	   = $conversionArr[1];
		//bigecho("Quantity in $deliveryUnits: $quantity");
		
		if ($deliveryUnitQuantity > 0)
		{
			$quantity = $deliveryUnitQuantity;
			if ($deliveryUnits == "Pounds")
			{
				$weight = $quantity;
			}
		}


		if ( $deliveryUnits == 'Gallons' || $deliveryUnits == 'Units' || strpos($deliveryUnits, 'Tote') === 0 || $deliveryUnits == 'Drum' || $deliveryUnits == 'Liters')
		{
			$quantity_fmt = number_format($quantity);
		}
		else
		{
			$quantity_fmt = number_format($quantity, 1);
		}

		$weight_fmt	  = number_format($weight);
		
		$unitsRow = '';
		if (!$poundsOnly)
		{
			$quantityVal = $deliveryUnits == 'Pounds' ? '' : "$quantity_fmt ($deliveryUnits)";
			$unitsRow = "<td class='spinNormalText'><div align='center'>$quantityVal</div></td>";
		}
		
		$tableDetailRows .= "\n<tr>
			<td class='spinNormalText'><div align='center'>$cnt</div></td>
			<td class='spinNormalText'><div align='center'>$time</div></td>
			<td class='spinNormalText'><div align='center'>$tankName</div></td>
			<td class='spinNormalText' align='center'><div align='left'>$address<br>$city, $state $zip</div></td>
			$unitsRow
			<td class='spinNormalText'><div align='center'>$weight_fmt (Pounds)</div></td>
		  </tr>";
		$totalQuantity += $quantity;
		$totalWeight += $weight;
		if (!empty($notes))
		{
			$notesOut .= "$cnt: $notes<br>";
		}
		$cnt++;
	}	
	
	$addressWidth = (!$poundsOnly) ? '226' : '341';
	$tableDetails = "
		<table width='700' border='1' cellpadding='5' cellspacing='0' bordercolor='#000000'>
		  <tr valign='middle'>
			<td width='20' class='spinSmallTitle'><div align='center'>#</div></td>
			<td width='102' class='spinSmallTitle'><div align='center'>Delivery Time </div></td>
			<td width='72' class='spinSmallTitle'>Tank ID </td>
			<td width='$addressWidth' class='spinSmallTitle'><div align='left'>Address</div></td>";
    
	if (!$poundsOnly)
	{
		$tableDetails .= "     <td width='115' class='spinSmallTitle'><div align='center'>Quantity</div></td>";
	}
	$tableDetails .="    <td width='115' class='spinSmallTitle'><div align='center'>Weight</div></td>
	  </tr>";

	echo $tableDetails . $tableDetailRows;
}

//bigecho($totalQuantity);
if ( $deliveryUnits == 'Gallons' || $deliveryUnits == 'Units' || strpos($deliveryUnits, 'Tote') === 0 || $deliveryUnits == 'Drum' || $deliveryUnits == 'Liters')
{
	$totalQuantity_fmt = number_format($totalQuantity);
}
else
{
	$totalQuantity_fmt = number_format($totalQuantity, 1);
}


?> 
  <tr>
    <td colspan="4" class="spinSmallTitle"><div align="right">Totals: </div></td>
    <? if (!$poundsOnly): ?> 
    <td class="spinNormalText"><strong><div align="center"><?=$totalQuantity_fmt?></div></strong></td>
	<? endif; ?>
    <td class="spinNormalText"><strong><div align="center"><?=number_format($totalWeight)?></div></strong></td>
  </tr>
  <tr>
    <td colspan="6" class="spinSmallTitle"><p>Notes:</p>
    <p>&nbsp;</p>
    <p class="spinNormalText"><?=$notesOut?></p></td>
  </tr>
</table>
<div align="left" class="style1"><br />
  SUPPLIER: Please return your order confirmation. <br />
  For more information please call <?=$internalContact?> at <?=$internalPhone?>
</div>
</body>
</html>
