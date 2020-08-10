<?php
  	function getRegionFilter()
  	{
		global $REGION_FILTER;
		
		$result = '';
		$arr = explode(':', $REGION_FILTER);
		foreach ($arr as $regID)
		{
			if (!empty($regID))
			{
				$result .= " or s.regionID = $regID";
			}
		}
		if (!empty($result))
		{
			// strip leading 'or'
			$result = ' and (' . substr($result, 3) . ') ';
		}
		return $result;
	}

function getHTMLPart($startString, $endString, $sourceString)
{
	$startStrLen = strlen($startString);
	$stpos = strpos($sourceString, $startString) + $startStrLen;
	$endpos = strpos($sourceString, $endString, $stpos);
	$subject = substr($sourceString, $stpos, $endpos-$stpos);
	return $subject;
}


function getSiteID($monitorID)
{
	$query = "SELECT siteID FROM monitor WHERE monitorID='$monitorID'";
	$res = getResult($query);
	if (checkResult($res))
	{
		$line = $res->fetch_assoc();
		extract($line);
		return $siteID;
	}
	
	return -1;
}

function getLevelOfService($montiorID, $startDate, $endDate)
{
	// get Process Target
	$processTarget = 0;
	$query = "SELECT processTarget FROM monitor WHERE monitorID='$montiorID' LIMIT 1";
	$pres = getResult($query);
	if (checkResult($pres))
	{
		$pline = mysql_fetch_assoc($pres);
		extract($pline);
	}
	
	if (empty($startDate))
	{
		$whereClause = "monitorID='$montiorID' and date > date_add(NOW(), interval -24 hour)";
	}
	else
	{
		$whereClause = "monitorID='$montiorID' and date(date) >= '$startDate' and date(date) <= '$endDate'";
	}
	
	$totalCount = 0;
	$countAboveTarget = 0;
	$query = "select minute(date) as readingMin, hour(date) as readingHour, date as readingDate, PPM from processData where PPM > 0 and date != '' and $whereClause";
	//error_log($query);
	$res = getResult($query);
	if (checkResult($res))
	{	
		while( $line = $res->fetch_assoc() )
		{
			$totalCount++;
			extract($line);		
			if ($processTarget > $PPM)
			{
				$countAboveTarget++;
			}
		}
	}
	if ($totalCount == 0) return 0;
	if ($countAboveTarget == 0) return 100;
	
	$ret = abs(((1-$countAboveTarget) / $totalCount) * 100);
	$ret = round($ret, 2);
	// error_log("----- 	$ret = ( ($totalCount -  $countAboveTarget) / $totalCount ) * 100;");	
	return $ret;
}

  function iteratePO($po)
  {	  
		$dashPos = strpos($po, '-');
		if (strpos($po, '-') === false)
		{
		  // no interation yet on this po
		  $po = $po . '-A';
		}
		else
		{
			$po_rev = substr($po, strpos($po, '-') + 1);
			$po_revLen = strlen($po_rev);
			if ($po_revLen == 2)
			{
				// iterate first part
				$letter = substr($po_rev, 0, 1);
				$letter = chr( ord( $letter ) + 1 );
				$po_rev = $letter . substr($po_rev, 1, 1);
			}
			else
			{
				// either an alpho character or a number
				if (is_numeric($po_rev))
				{
					$po_rev = 'A' . $po_rev;  // Numeric, insert a 'A'
				}
				else
				{
					$po_rev = chr( ord( $po_rev ) + 1 );
				}
			}
			$po = substr($po, 0, $dashPos) . "-$po_rev";
		}
			
		$res = getResult("SELECT PO FROM deliverySite WHERE PO='$po' AND markDelete=0");
		if (checkResult($res))
		{
			// po already there, must iterate 
			$po = iteratePO($po);
		}
	
		return $po;
  }
  
	function iterateRevNo($po)
	{
		/*
			If this is a revision then we need to add/iterate the revison number.
		*/	
		$dashPos = strpos($po, '-');
		if ($dashPos === false)
		{
			$po .= '-1';
		}
		else
		{
			$po_rev = substr($po, strpos($po, '-') + 1);
			$po_revLen = strlen($po_rev);
			if ($po_revLen == 2)
			{
				// iterate second part
				$revNum = substr($po_rev, 1, 1);
				$revNum += 1;
				$po_rev = substr($po_rev, 0, 1) . "$revNum";
			}
			else
			{
				// either an alpho character or a number
				if (is_numeric($po_rev))
				{
					$po_rev += 1;  // already a rev, iterate it
				}
				else
				{
					$po_rev .= '1';  // make it something like -A1
				}
			}
			$po = substr($po, 0, $dashPos) . "-$po_rev";
		}

		return $po;
	}
  

  function generatePO($monitorID, $deliveryDate)
  {
	  	global $USED_PO_CODES;
		// generate PO and add to deliverySite table
		$query = "SELECT 
				DATE_FORMAT('$deliveryDate', '%y%m%d') as today, 
				s.PO_code, 
				s.siteID 
			FROM monitor t, site s 
		WHERE t.monitorID='$monitorID' AND t.siteID=s.siteID LIMIT 1";
		$siteres = getResult($query);
		if (checkResult($siteres))
		{
			$siteline = mysql_fetch_assoc($siteres);
			extract($siteline);
			$po = $PO_code . "$today";
		}
		else
		{
			executeQuery("DELETE FROM delivery WHERE deliveryID = $deliveryID LIMIT 1");
			die("Error: The site associated with monitor $monitorID does not exist.");
		}
		
		if (empty($USED_PO_CODES[$PO_code])) // When looping through tanks, it's possible that mulitple delivery tanks can have the same PO_code.  In that case we allow duplicate PO's
		{
			// check to see if po already exists
			$res = getResult("SELECT PO FROM deliverySite WHERE PO='$po' AND markDelete=0");
			if (checkResult($res))
			{
				// po already there, must iterate 
				$po = iteratePO($po);
			}
			$USED_PO_CODES[$PO_code] = $po;
		}
		else
		{
			$po = $USED_PO_CODES[$PO_code];	
		}

		return "$po:$siteID:$PO_code";
  }

  function getTankName($tankID)
  {
	$query = "SELECT tankName FROM tank WHERE tankID='$tankID'";
	$res = getResult($query);
	if (checkResult($res))
	{
		$line = $res->fetch_assoc();
		extract($line);
		return $tankName;
	}
	else
	{
		return $tankID;
	}
  }
  
  function verifyCustomer($siteid)
  {
	  
  }

  function logAction($msg, $hidden=0)
  {
	global $USERID, $_SESSION;
	
	$ip = $_SERVER['REMOTE_ADDR']; 
	  
	if (!empty($msg) && !empty($USERID))
	{
		executeQuery("INSERT INTO activityLog (UserID, date, message, ip, hidden) VALUES ('$USERID', NOW(), '$msg', '$ip', $hidden)");	
	}
   }

  function verifyUser($usr, $pw)
  {
//  	bigEcho($usr . ", -- " . $pw);
  	global $USERID, $PASSWORD, $USERTYPE, $CUSTOMER_SITES;
	$query = "select type from users where loginID='$usr' LIMIT 1";
	$res = getResult($query);
	if (checkResult($res))
	{
		$query = "select type from users where loginID='$usr' and password='$pw' LIMIT 1";
		$res = getResult($query);
	 	bigEcho("Result Type: " . gettype($res));
		if (checkResult($res))
		{
			$line = $res->fetch_assoc();
			//bigEcho("Line --> " .  $line[$usr]);
			$_SESSION["USERID"] = $usr;
			$_SESSION["PASSWORD"] = $pw;
			$_SESSION["USERTYPE"] = $line["type"];
			return 0;
		}		
		else
		{
			return 'Invalid password';
		}

	}
	elseif (!empty($pw))
	{
		// this may be a customer login
		$query = "SELECT email FROM customerLoginEmail WHERE email = '$usr' and password='$pw' LIMIT 1";
		$res = getResult($query);
		if (checkResult($res))
		{
			$line = $res->fetch_assoc();
			extract($line);
			$USERID = $usr;
			$PASSWORD = $pwd;
			$USERTYPE = 'customer';
			
			if (empty($CUSTOMER_SITES))
			{
				session_register('CUSTOMER_SITES');	
			}
			setCustomerSites($USERID);
			return 0;
		}		
	}
	else
	{
		return 'User not found';
	}		
  }
  
  function setCustomerSites($user)
  {
		global $CUSTOMER_SITES;
		$CUSTOMER_SITES = array();
		$res = getResult("Select siteID, deliveryEmailDist from site where deliveryEmailDist LIKE '%$user%'");
		if (checkResult($res))
		{
			while( $line = $res->fetch_assoc() )
			{
				extract($line);
				array_push($CUSTOMER_SITES, $siteID);
			}
		}
  }
  
  function updateTankStats($monitorID, $days=11)
  {
	$res = getResult("SELECT max(date) as date, cast(date as date) as dateOnly, monitorID FROM data WHERE monitorID='$monitorID' and
	cast(date as date) >= DATE_ADD(cast(NOW() as date), INTERVAL -$days day) group by cast(date as date) order by date desc"); 
	if (checkResult($res))
	{
		while( $line = $res->fetch_assoc() )
		{
			extract($line);
			generateStats($monitorID, "'$dateOnly'", 0);
			//echo("generateStats($monitorID, \"'$date'\");<br>");
		}
	} 
	
	// get today's status if it wasn't already gotten above
	generateStats($monitorID, 'NOW()');	
  }

  function updateDeliveryTankStats($deliveryID, $deliveryDate='')
  {
  		if (empty($deliveryDate))
		{
			$query = "SELECT deliveryDate FROM delivery WHERE deliveryID=$deliveryID";
			$res = getResult($query);
			if (!checkResult($res)) return;
			$line = $res->fetch_assoc();
			extract($line);
		}
					
		$query = "SELECT monitorID FROM deliveryTanks WHERE deliveryID=$deliveryID";
		$res = getResult($query);
		if (!checkResult($res)) return;

		while ($line = $res->fetch_assoc())
		{
			extract($line);
			// get date/time of reading for monitor on the delivery date
			$query = "SELECT date FROM data WHERE monitorID='$monitorID' and CAST(date as date) = '$deliveryDate' ORDER BY date DESC LIMIT 1";
			$mres = getResult($query);
			if (checkResult($mres))
			{
				$mline = mysql_fetch_assoc($mres);
				extract($mline);
				generateStats($monitorID, "'$date'", 0);
			}
			
			// get following day
			$query = "SELECT date FROM data WHERE monitorID='$monitorID' and CAST(date as date) = DATE_ADD('$deliveryDate', INTERVAL 1 DAY) ORDER BY date DESC LIMIT 1";
			$mres = getResult($query);
			if (checkResult($mres))
			{
				$mline = mysql_fetch_assoc($mres);
				extract($mline);
				generateStats($monitorID, "'$date'", 0);
			}
		}
//		die;
  }

  function showSiteTable(&$result, $title='defalut')
  {
  	if (mysql_num_rows($result) <= 0)
  	{
  	  return;
  	}
	if ($title == 'defalut')
	{
  		echo "Total Rows: ".mysql_num_rows($result);
	}
	else
	{
		echo "<span class='spinMedTitle'>$title</span>";	
	}
  	mysql_data_seek($result, 0);
  	$fieldCnt = mysql_num_fields($result);
  	
	$width = '';
	
  	echo "<table border='1' cellspacing='0' cellpadding='3' bordercolor='#cccccc' $width>\n<tr>\n";
	for ($i = 0; $i < $fieldCnt; $i++)
	{
		if ($i > 0) // skip first field which is siteID
		{
    	  $fn = mysql_field_name($result, $i);
		  echo "<td align='center' class='spinTableTitle'>$fn</th>";
		}
	}
	echo "<td align='center' class='spinTableTitle'>&nbsp;</th>";
	//echo "<span class='spinMedTitle'>&nbsp;</span>";	
	echo "</tr>";
	
  	while ($line = mysql_fetch_array($result))
  	{
			echo "<tr class='spinTableBarOdd'>";
			for ($i = 0; $i < $fieldCnt; $i++)
			{
				if ($i > 0) // skip first field which is siteID
				{
					echo "<td align='left'>$line[$i]</td>";
				}
			}
			$modifyLink = "<a class='normalLinks' href=\"javascript:addSiteFrame(600, 500," . $line['siteID'] . ")\">Modify</a>";
			$deleteLink = "<a class='normalLinks' href=\"javascript:if (confirm('Are you sure you want to delete this site?')) quietCommit('/deleteSite.php?sid=" . $line['siteID'] . "')\">Delete</a>";
			echo "<td align='center' width='100' nowrap align='center'>$modifyLink $deleteLink</td>";
	  		echo "</tr>";
  	}
  	echo "</table>";
  	mysql_data_seek($result, 0);
  }
  
function changeMonitorID($oldMonitorID, $newMonitorID)
{
		
	// Changre monitor ID in all necessary tables
	if ($oldMonitorID == $newMonitorID)
	{
		return;
	}
	
	$query = "UPDATE tank SET tankID='$newMonitorID', monitorID='$newMonitorID' WHERE tankID='$oldMonitorID' LIMIT 1";
	executeQuery($query);
	$query = "UPDATE monitor SET monitorID='$newMonitorID' WHERE monitorID='$oldMonitorID' LIMIT 1";
	executeQuery($query);
	$query = "UPDATE data SET monitorID='$newMonitorID' WHERE monitorID='$oldMonitorID'";
	executeQuery($query);
	$query = "UPDATE deliveryTanks SET monitorID='$newMonitorID' WHERE monitorID='$oldMonitorID'";
	executeQuery($query);
	$query = "UPDATE NoReadings SET monitorID='$newMonitorID' WHERE monitorID='$oldMonitorID'";
	executeQuery($query);
	$query = "UPDATE tankNotes SET tankID='$newMonitorID' WHERE tankID='$oldMonitorID'";
	executeQuery($query);
	$query = "UPDATE tankStats SET monitorID='$newMonitorID' WHERE monitorID='$oldMonitorID'";
	executeQuery($query);
	$query = "UPDATE tankHistory SET monitorID='$newMonitorID' WHERE monitorID='$oldMonitorID'";
	executeQuery($query);
	$query = "UPDATE processTargetHistory SET monitorID='$newMonitorID' WHERE monitorID='$oldMonitorID'";
	executeQuery($query);
	$query = "UPDATE processData SET monitorID='$newMonitorID' WHERE monitorID='$oldMonitorID'";
	executeQuery($query);
	
	
}

function addTank($tankArr1, $tankArr2, $tankArr3, $mon='')
{
	global $originalUnits, $originalStatus, $originalDeliveryUnits;
	extract($tankArr1);
	extract($tankArr2);
	extract($tankArr3);

	if (!empty($editMonitor))
	{
		// edit stuff
		$siteID = $selCustomerSite;
		$address = htmlentities($address, ENT_QUOTES);
		$contact = fixString($contact);
		$contact = htmlentities($contact, ENT_QUOTES);
		$email = fixString($email);
		$email = htmlentities($email, ENT_QUOTES);
		$customerSite = htmlentities($customerSite, ENT_QUOTES);
		
		$siteQuery = "UPDATE site SET 
					siteLocationName= '$customerSite', 
					regionID=$region, 
					address='$address', 
					city='$city', 
					state='$state', 
					zip='$zipcode', 
					contact='$contact', 
					contactPhone='$phone', 
					contactEmail='$email', 
					PO_code='$PO_code' WHERE siteID=$siteID LIMIT 1";
		
		executeQuery($siteQuery);
	}
	elseif (!empty($customerSite))
	{
		// add new customer - Check for that customer's existance
		$query = "SELECT siteID from site WHERE siteLocationName='$customerSite' LIMIT 1";
		$res = getResult($query);
		if (checkResult($res))
		{
			// site exists
			return "1,The customer site you entered already exists.<br>Please choose from the existing customer site list or enter a different customer site name.";
		}

		if (
			empty($address) || 
			empty($city) || 
			empty($contact) || 
			empty($customerSite) || 
			empty($phone) || 
			empty($region) ||
			empty($state) || 
			empty($zipcode) )
		{
			return "1,Please fill in all field values";
		}		

		$contact = fixString($contact);
		$contact = htmlentities($contact, ENT_QUOTES);
		$email = fixString($email);
		$email = htmlentities($email, ENT_QUOTES);
		$customerSite = htmlentities($customerSite, ENT_QUOTES);

		$siteQuery = "INSERT INTO site (siteLocationName, regionID, address, city, state, zip, contact, contactPhone, contactEmail, PO_code) VALUES 
					('$customerSite', $region, '$address', '$city', '$state', '$zipcode', '$contact', '$phone', '$email', '$PO_code')";
	
		$siteID = executeQuery($siteQuery, 'INSERT');
	}
	elseif ($selCustomerSite !== '--none--')
	{
		// Selected a current customer
		$siteID = $selCustomerSite ;
	}
	else
	{
		// nothing selected
		return "1,Please select an existing customer site location or add a new customer site";
	}

	
	if ($nomonitor == 'none')
	{
		if (!empty($editMonitor))
		{
			$monitorID = $editMonitor;
		}
		else
		{
			$rand = generateCode(5);
			$monitorID = "none-$rand";
		}
	}

	if (!empty($editMonitor))
	{
		$no_tsAlert = '';
		if (strpos($status, 'Temporary Shutdown') !== false)
		{
			// check to see if this is being changed to TS
			$tsRes = getResult("SELECT status FROM monitor WHERE status != 'Temporary Shutdown' AND monitorID='$editMonitor' LIMIT 1");
			if (checkResult($tsRes))
			{
				$no_tsAlert =  "no_tsAlert=0, ";
			}
		}
		$monitorQuery = "
			UPDATE monitor 
			SET $no_tsAlert
				monitorID = '$monitorID', 
				siteID =$siteID, 
				status = '$status', 
				units = '$units', 
				tolerance=$tolerence 
			WHERE monitorID = '$editMonitor' LIMIT 1";
		executeQuery($monitorQuery);

		if ($originalStatus != $status)
		{
			// log the change in status
			logAction("Status changed to $status for $monitorID");
			$originalStatus = '';
		}

		if ($originalDeliveryUnits != $deliveryUnits)
		{
			// log the change in units
			logAction("Delivery Units changed to $deliveryUnits for $monitorID");
			$originalDeliveryUnits = '';
		}

		if ($originalUnits != $units)
		{
			// log the change in units
			logAction("Units changed to $units for $monitorID");
			$originalUnits = '';
		}
	}
	else
	{
		// Check and see if the monitor exists
		// ----------------------------------------
		$res = getResult("select monitorID FROM monitor WHERE monitorID='$monitorID' LIMIT 1");
		if (checkResult($res))
		{
			return "2,The monitor you have entered already exists.  Please enter another Monitor ID";
		}
		
		// add new monitor
		$monitorQuery = "INSERT INTO monitor (monitorID, siteID, startDate, status, units, tolerance) values
		('$monitorID', $siteID, NOW(), '$status', '$units', $tolerence)";
	}

	// add tank
	$dosage = empty($dosage) ? 0 : $dosage;
	//$concentration = $prodID != '3' ? '100' : $concentration;  // all product other than hydrogen peroxide are 100% concentration 

	if (
		empty($capacity) ||
		empty($diameter) ||
		empty($height) ||
		empty($status) ||
		empty($monitorID) ||
		empty($prodID) ||
		empty($tankName) ||
		empty($tolerence) ||
		empty($units) ||
		empty($volume) ||
		$volume == '0' || 
		$capacity == '0'
	)
	{
		return "2,Please fill in all field values";
	}


	$height2	= empty($height2) ? '00' : trim($height2);
	$height 	= "$height.$height2";
	
	$diameter2	= empty($diameter2) ? '00' : trim($diameter2);
	$diameter 	= "$diameter.$diameter2";

	$usableVolume2	= empty($volume2) ? '00' : trim($volume2);
	$usableVolume = "$volume.$usableVolume2";

	$pumpCapacity = empty($pumpCapacity) ? '0' : $pumpCapacity;
	$pumpCapacity2	= empty($pumpCapacity2) ? '00' : trim($pumpCapacity2);
	$pumpCapacity = "$pumpCapacity.$pumpCapacity2";

	$costPerGallon = empty($costPerGallon) ? '0' : $costPerGallon;
	$costPerGallon2	= empty($costPerGallon2) ? '00' : trim($costPerGallon2);
	$costPerGallon = "$costPerGallon.$costPerGallon2";


	$targetDosage2	= empty($dosage2) ? '00' : trim($dosage2);
	$targetDosage = "$dosage.$targetDosage2";

	if (empty($tankID))
		$tankID = $monitorID;  // THIS COULD CHANGE WHEN ONE MONITOR HAS SEVERAL TANKS

	if (empty($supplier) || $supplier == '--none--')
	{
		$sres = getResult("SELECT supplierName from supplier where supplierName = '$supplierName' LIMIT 1");
		if (checkResult($sres))
		{
				return "3,Supplier already exists.  Please select the supplier from the existing supplier list.";			
		}
	
		$supplierQuery = "INSERT INTO supplier (supplierName, contact, email, phone) values
		('$supplierName', '$supplierContact', '$supplierEmail', '$supplierPhone')";
		$supplier = executeQuery($supplierQuery, 'INSERT');
	}

	// carrier 
	if (!empty($carrierName))
	{
		// Adding a new carrier
		if (
			empty($carrierContact) ||
			empty($carrierEmail) ||
			empty($carrierName) ||
			empty($carrierPhone)
			)
		{
			return "3,Please provide all carrier contact information";
		}	


		$res = getResult("select carrierName from carrier where carrierName = '$carrierName'");
		// check for existing carrier, add if it doesn't exist.
		if (checkResult($res))
		{
			return "3,That carrier already exists.  Please choose it from the list or use another carrier name.";
		}
		
		// get carrier id and associate with tank
		$carrier = executeQuery("INSERT INTO carrier (carrierName, contact, phone, email) VALUES ('$carrierName','$carrierContact','$carrierPhone','$carrierEmail')", 'INSERT');
	}
	elseif ($carrier == '--none--')
	{
		$carrier = 0;
	}

	if (
		empty($leadTime) ||
		empty($timeOfDelivery) ||
		empty($reorder) ||
		empty($low) ||
		empty($critical) 
		)
	{
		return "3,Please provide all Tank/Supplier Details";
	}	
	
	$deviation_plus	= empty($deviation_plus) ? 0 : $deviation_plus;
	$deviation_plus	= $deviation_plus < 0 ? 0 : $deviation_plus;
	$deviation_minus	= empty($deviation_minus) ? 0 : $deviation_minus;
	$deviation_minus	= $deviation_minus < 0 ? 0 : $deviation_minus;
	if (empty($editMonitor))
	{
		$dosage_days	= empty($dosage_days) ? 0 : $dosage_days;
		$dosage_days	= $dosage_days < 0 ? 0 : $dosage_days;
		$noteText = htmlentities($notes, ENT_QUOTES);
		$tankName = htmlentities($tankName, ENT_QUOTES);
		
		$tankQuery 	= "INSERT INTO tank (
						tankID,
						supplierID,
						monitorID,
						carrierID,
						tankName,
						notes,
						height,
						diameter,
						multiple,
						orientation,
						capacity,
						pumpCapacity,
						usableVolume,
						targetDosage,
						deviation_plus,
						deviation_minus,
						dosage_days,
						prodID,
						concentration,
						reorder,
						low,
						critical,
						timeOfDelivery,
						leadTime,
						deliveryUnits)
					VALUES
						(
							'$tankID', $supplier, '$monitorID', $carrier, '$tankName', '$noteText', $height, $diameter, $multiple, '$orientation', 
							$capacity, $pumpCapacity, $usableVolume, $targetDosage, $deviation_plus, $deviation_minus, $dosage_days, $prodID, 
							'$concentration', $reorder, $low, $critical, '$timeOfDelivery', $leadTime, '$deliveryUnits')";

		
		executeQuery("INSERT INTO tankHistory (monitorID, date, targetDose) VALUES ('$tankID', NOW(), $targetDosage)");	
		executeQuery("INSERT INTO costHistory (monitorID, date, costPerGallon) VALUES ('$tankID', NOW(), $costPerGallon)");	
	}
	else
	{
		$noteText = htmlentities($notes, ENT_QUOTES);
		$tankName = htmlentities($tankName, ENT_QUOTES);

		$tankQuery = "UPDATE tank 
						SET 
							tankID = '$monitorID',
							supplierID = $supplier,
							monitorID = '$monitorID',
							carrierID = $carrier,
							tankName = '$tankName',
							notes = '$noteText',
							height = $height,
							diameter = $diameter,
							multiple = $multiple ,
							orientation = '$orientation',
							capacity = $capacity,
							usableVolume = $usableVolume,
							pumpCapacity = $pumpCapacity,
							deviation_plus = $deviation_plus,
							deviation_minus = $deviation_minus,
							dosage_days = $dosage_days,
							prodID = $prodID,
							concentration = '$concentration',
							reorder = $reorder,
							low = $low,
							critical = $critical,
							timeOfDelivery = '$timeOfDelivery',
							leadTime = $leadTime,
							deliveryUnits = '$deliveryUnits'
						WHERE monitorID='$editMonitor' LIMIT 1";

		// log activity if somebody changes the reorder level
		$activityRes = getResult("SELECT reorder as tmp_reorder FROM tank WHERE monitorID='$editMonitor' and reorder <> $reorder LIMIT 1");
		if (checkResult($activityRes))
		{
			$activityLine = mysql_fetch_assoc($activityRes);
			extract($activityLine);
			logAction("Reorder changed from $tmp_reorder" . '%' . " to $reorder" . '%' . " for $tankName");
		}

//die($tankQuery);
		executeQuery($tankQuery);
		
		// update costPerGallon in history
		executeQuery("DELETE FROM costHistory WHERE date=date(NOW()) AND monitorID='$monitorID' LIMIT 1");		
		executeQuery("INSERT INTO costHistory (monitorID, date, costPerGallon) VALUES ('$monitorID', NOW(), '$costPerGallon')");		
		
		changeMonitorID($editMonitor, $monitorID);

	}
	
	if (empty($editMonitor))
	{
		executeQuery($monitorQuery);
		executeQuery($tankQuery);
	}

	generateStats($monitorID, 'NOW()', 0);

	// add each truck per capacity listed. remove all trucks for the tank before adding. 
	if (!empty($truckCapacities))
	{
		executeQuery("delete from truck where tankID='$tankID'"); // remove all trucks before readding
		$capArray = explode(',', $truckCapacities);
		foreach ($capArray as $capval)
		{
			if (!empty($capval))
			{
				executeQuery("INSERT INTO truck (tankID, capacity) VALUES ('$monitorID', $capval)");
			}
		}
	}
	return 0;
}

function checkTankLevel($monitorID, $statDate='')
{
	global $debug;
	
	$more = '';
	if ($statDate !== '')
	{
		$more = "and cast(statDate as date) = DATE(NOW())";
	}
	
	$query = "SELECT levelStat from tankStats where monitorID='$monitorID' $more ORDER BY readingDate DESC LIMIT 1";
	$res = getResult($query);
	
	
	if (checkResult($res))
	{
		$line = $res->fetch_assoc();
		extract($line);
		if ($levelStat == 'Critical' )
		{
			return "Critical,Critical";
		} 
		if ($levelStat == 'Low' )
		{
			return "Low,Low";
		} 
		if ($levelStat == 'Reorder' )
		{
			return "Reorder,Reorder";
		} 
	}
	return "Ok,Level OK";
}

function getDaysSinceLastReading($monitorID, $readingDate='NOW()')
{
	$daysSinceLastReading = 0;
	$query = "SELECT DISTINCT 
					DATEDIFF( $readingDate , date ) AS daysSinceLastReading,
					date as fulldate 
				FROM 
					data 
				WHERE 
					cast(date as date) <= cast($readingDate as date) AND monitorID='$monitorID' AND value > 0 ORDER BY fulldate DESC LIMIT 1";
	$res = getResult($query);
	if (checkResult($res))
	{
		$line = $res->fetch_assoc();
		extract($line);
	}
	return $daysSinceLastReading;
}

function daysInCurrentStatus($monitorID)
{
	$status = checkTankStatus($monitorID);  
	list($statkey, $status) = explode(',', $status);
	if ($statkey == 'H_Dose')
		$fld = 'high';
	elseif ($statkey == 'L_Dose')
		$fld = 'low';
	elseif ($statkey == 'ExceedCap')
		$fld = 'exceedcap';
	elseif ($statkey == 'NoReading')
		$fld = 'noreading';
	else
		return -1;

	$query = "select monitorID, DATEDIFF(NOW(), readingDate) as DateDifference from tankStats where monitorID='$monitorID' and $fld <> 1 order by readingDate desc limit 1";
	$res = getResult($query);
	if (checkResult($res))
	{
		$line = $res->fetch_assoc();
		extract($line);
		return $DateDifference;
	}
	else
		return -1;
}

function checkTankStatus($monitorID, $statkey='', $msgColor='ff0000')
{
	global $debug;

	if (($statkey == 'unmon' || $statkey == '') && 	substr($monitorID, 0, 5) == 'none-')
	{
		return "unmon,Tank Unmonitored";
	}

	$query = "SELECT 
				status, 
				DATE_FORMAT(shutdownStartDate, '%m/%d/%Y %r') as 'shutdownStartDate', 
				DATE_FORMAT(shutdownEndDate, '%m/%d/%Y %r') as 'shutdownEndDate' 
			from monitor where monitorID='$monitorID' and status='Temporary Shutdown'";
	$res = getResult($query);
	if (checkResult($res))
	{
		$line = $res->fetch_assoc();
		extract($line);
		return "TempShutdown,Temporary Shutdown ($shutdownStartDate - $shutdownEndDate)";
	}

	// check for no reading EVER
//	$query = "SELECT date as lastReading 
//					FROM data WHERE monitorID='$monitorID'";
//	$res = getResult($query);
//	if (!checkResult($res))
//	{
//		$noreading = 1;
//		$daysSinceLastReading = getDaysSinceLastReading($monitorID);
//	}
//	else // there was at least one reading, check to see if it was over 0
//	{
		$query = "SELECT date as lastReading 
						FROM data WHERE monitorID='$monitorID' and value > 0 ORDER BY date DESC LIMIT 1";
		$res = getResult($query);
		if (checkResult($res))
		{
			$line = $res->fetch_assoc();
			extract($line);
		}
		else
		{
			if ($status == 'Inactive')
				return "Inactive,Inactive";
			else
				return "$status,$status";
		}
//	}

	$query = "SELECT DATE(NOW()) as today, cast(readingDate as date) as lastReadingDAY, 
	daysSinceLastReading
	FROM tankStats 
	WHERE readingDate='$lastReading' AND monitorID='$monitorID' AND statGenDate = DATE(NOW()) ORDER BY statGenDate DESC LIMIT 1";
	$res = getResult($query);

	$_POST["genstat"] = false;
	if (!checkResult($res))
	{
		updateTankStats($monitorID, 2);
	}
	
	$res = getResult("SELECT tankStats.* FROM tankStats WHERE monitorID = '$monitorID' ORDER BY readingDate DESC LIMIT 1");
	if (checkResult($res))
	{
		$line = $res->fetch_assoc();
		extract($line);
	}	

	// At this point today's stats should have been generated.  We now check to see if there is a status row for today.  If not then
	// there was no reading.
	$res = getResult("SELECT noreading FROM tankStats WHERE monitorID='$monitorID' and cast(readingDate as date) = DATE(NOW()) LIMIT 1");
	if (!checkResult($res))
	{
		// No reading today.  Get the number of days since last reading and make sure that noreading returns 1
		$noreading = 1;
		$daysSinceLastReading = getDaysSinceLastReading($monitorID);
	}
	
	$s = $daysSinceLastReading > 1 ? 's' : '';
	$daysMsg = "<div class='header_1'>Normalized Dose: $latestDose";
	$daysMsg .= $daysSinceLastReading > 0 ? "<br><font color='#$msgColor'>$daysSinceLastReading day$s since last reading</font>" : '';
	//$daysMsg = $daysSinceLastReading > 0 ? "<br><font color='#$msgColor'>$daysSinceLastReading day$s since last reading</font>" : '';
	//$daysMsg .= "<div class='header_1'>Normalized Dose: $latestDose";


	if (($statkey == 'ExceedCap' || $statkey == '') && $exceedcap == 1)
		return "ExceedCap,Capacity Exceeded$daysMsg";

	if (($statkey == 'NoReading' || $statkey == '') && $noreading == 1)
		return "NoReading,No Reading$daysMsg";

	if (($statkey == 'H_Dose' || $statkey == '') && $high == 1)
	{
		return "$high_low_message$daysMsg";
	}
	
	if (($statkey == 'L_Dose' || $statkey == '') && $low == 1)
	{
		return "$high_low_message$daysMsg";
	}

	if ($statkey == 'unass' || $statkey == '')
	{
		$res = getResult("SELECT monitorID FROM monitor WHERE monitorID='$monitorID' LIMIT 1");
		if (!checkResult($res))
		{
			return "unass,Unassociated Tank$daysMsg";
		}
	}

	if (($statkey == 'Normal' || $statkey == '') && $normal == 1)
		return "Normal,Normal Reading<div class='header_1'>Normalized Dose: $latestDose";
}

function galToInch($gallons, $d)
{
	//x*Pi*d*d/231*4
	$inches = 231 * 4 * $gallons / (3.1416 * $d * $d);
	return round($inches, 2);
}

function inchToGal($inches, $d)
{
	$r = ($d/2);	
	$a = (3.1416 * $r * $r);
	$cap = ($a * $inches) / 231;
	$cap = round($cap,0);
	return $cap;
}

function getDose($monitorID, $daysAgo, &$debug)
{
	$units = 'Gallons';	
//	$ures = getResult("SELECT m.units, t.diameter FROM monitor m, tank t WHERE m.monitorID=t.monitorID and m.monitorID = '$monitorID' and m.units = 'Inches'");

	$ures = getResult("SELECT t.diameter FROM tank t WHERE t.monitorID = '$monitorID'");
	if (checkResult($ures))
	{
		$uline = mysql_fetch_assoc($ures);
		extract($uline);
	}
	
	
	// get the value of the reading for today minus $daysAgo.  This is used to compare with the previous reading.
	if ($daysAgo == 0)
	{
		// get latest
		$res = getResult("SELECT units as secondUnits, value as secondReadingValue, cast(date as date) as secondDate, date as secondFullDate
							FROM data 
							WHERE 
								monitorID = '$monitorID'
							ORDER BY date DESC LIMIT 1");
	}	
	else
	{
		$query = "SELECT units as secondUnits, value as secondReadingValue, cast(date as date) as secondDate, date as secondFullDate
							FROM data
							WHERE 
								monitorID = '$monitorID' and
								cast(date as date) = DATE_ADD(DATE(NOW()), INTERVAL -$daysAgo DAY) 
							ORDER BY date desc LIMIT 1";
		$res = getResult($query);
		//echoResults($res);
	}
		
		
	if (!checkResult($res))
	{
		/*
			The following two lines replace the 60+ lines.  We are now taking today's weighted average (what's shown on the delivery
			page), and using that for any day plotted on the graph that does not have a dose.  I suggested to Jim that we add a parameter
			to the getDeliveryAvg() function to account for 'days ago' but he said lets leave it like this for now.
		
		*/
		$weightedAvg = getDeliveryAvg($monitorID);
		return $weightedAvg;


	}
	else
	{
		// We have the second date's reading
		$line = $res->fetch_assoc();
		extract($line);
	}

	/*
		This query gives us the reading for the day preceeding the date current date minus $daysAgo (passed in).
		
		For example, if 10 is passed in as $daysAgo, this query would look for the most recent reading 
		older than 10 days.
	*/
	$query = "SELECT 
				ROUND((UNIX_TIMESTAMP('$secondFullDate') - UNIX_TIMESTAMP(date)) / 60 / 60, 1) AS diff_in_hours,
				cast(date as date) as firstDate,
				value as firstValue, units as firstUnits
			 from data
			 where 
				monitorID = '$monitorID' and
			 	cast(date as date) < '$secondDate' order by date DESC LIMIT 1";

	$res = getResult($query);
	// echoResults($res);
	$debug .= "<h3>$query</h3>";
	
	if (checkResult($res))
	{
		$line = $res->fetch_assoc();
		extract($line);
		$diff_in_hours = round($diff_in_hours);
		$diff_in_hours = $diff_in_hours < 1 ? 1 : $diff_in_hours;

		if ($firstUnits == 'Inches')
		{
			$firstValue = inchToGal($firstValue, $diameter);
		}
		
		if ($secondUnits == 'Inches')
		{
			$secondReadingValue = inchToGal($secondReadingValue, $diameter);
		}
	
		// check to see if the date matches the date of a delivery
		$query = "	SELECT 
					actual_quantity
					FROM delivery d, deliveryTanks dt
					WHERE d.deliveryID = dt.deliveryID
					AND (
						d.deliveryDate = DATE_ADD( cast( '$secondDate' AS date ) , INTERVAL -1 DAY )
						OR d.deliveryDate = cast( '$secondDate' AS date )
						)
					AND dt.monitorID = '$monitorID' and actual_quantity > 0";  

			$debug .= "<h3>$query</h3>";
		
			$res = getResult($query);
			$useActualQuantity = false;
			if (checkResult($res))
			{
				$useActualQuantity = true;
				$line = $res->fetch_assoc();
				extract($line);
			}
	
		if ($useActualQuantity && ($firstValue < $secondReadingValue))
		{
			error_log("DOSE CALCUALTED WITH Acutal Delivered Set - ($firstValue - ($secondReadingValue - $actual_quantity)");
			$diffInReadings = $firstValue - ($secondReadingValue - $actual_quantity);
			$debug .= "<h3>$diffInReadings = $firstValue - ($secondReadingValue - $actual_quantity);</h3>";
		}
		else
		{
			$diffInReadings = $firstValue - $secondReadingValue;
		}
		$normalizedDose = round((24/$diff_in_hours) * $diffInReadings);
		$debug .= "<h3>normalized dose: $normalizedDose [diffinHours=$diff_in_hours]</h3>";
		return $normalizedDose;
	}
	else
	{
		// there were no readings preceeding the day requested.
		//$weightedAvg = getDeliveryAvg($monitorID);
		//return $weightedAvg;
		return -1;
	}
}


function getDeliveryAvg($monitorID, $endDate = 'NOW()')
{
	// get a weighted average from the dose falue from the statistics table 
	//$query = "SELECT latestDose FROM tankStats WHERE monitorID='$monitorID' AND nodose=0 and latestDose > 0 ORDER BY readingDate DESC LIMIT 5";

	$query = "SELECT `readingDate`, latestDose FROM tankStats WHERE monitorID='$monitorID' AND nodose=0 and latestDose > 0 and cast(readingDate as date) <= $endDate ORDER BY readingDate DESC LIMIT 5";

	$res = getResult($query);
	if (checkResult($res))
	{
		//echoResults($res);
		$cnt = $res->num_rows;
		if ($cnt == 1) 
		{
			$line = $res->fetch_assoc();
			extract($line);
			return $latestDose;
		}
		
		$high = -1;
		$low = 0;
		$sum = 0;
		$loopCtr = 1;
		$sumOfLastTwoValues = 0;
		$firstVal = 0;
		$secondVal = 0;
		while ($line = $res->fetch_assoc())
		{
			extract($line);
	
			// We track the first two doses so we can later use them to get the weighted average
			if ($loopCtr == 1)
				$firstVal = $latestDose;
			if ($loopCtr == 2)
				$secondVal = $latestDose;
	
			$loopCtr++;
			
			if ($low == 0)
			{
				$low = $latestDose;
			}
			$sum += $latestDose;
			$high = max($high, $latestDose);
			$low = min($low, $latestDose);
		}
		
		// get weighted average
		if ($cnt >= 4)
		{
			$sum = $sum - $high - $low;
			$cnt = $cnt - 2; 
			$averageDose = round( $sum / $cnt ); // get average of middle 3
			
			// If the first two values are either the high or the low, then we use the averageDose instead
			$firstVal 	= $firstVal == $high || $firstVal == $low ? $averageDose : $firstVal;
			$secondVal 	= $secondVal == $high || $secondVal == $low ? $averageDose : $secondVal;
			$sumOfLastTwoValues = $firstVal + $secondVal;
			$averageDose = round( (($averageDose * $cnt) + $sumOfLastTwoValues) / ($cnt + 2) ); 
		}
		else
		{
			// not enough values for a weighted average so do a straight average
			$averageDose = round( $sum / $cnt ); 
		}
		
	}
	else
	{
		return 0; // no dose in statistics
	}
	return $averageDose;
}

function generateAllStats()
{
	$res = getResult("SELECT DISTINCT monitorID FROM data");
	if (checkResult($res))
	{
		while ($line = $res->fetch_assoc())
		{
			extract($line);
			//generateStats($monitorID);
			updateTankStats($monitorID, 2);
		}
	}
}


function generateStats($monitorID, $statdate='NOW()', $notify=1)
{
	global $debug, $database;
	$updateStat = $statdate == 'NOW()' ? false : true;
	executeQuery("DELETE FROM NoReadings WHERE monitorID='$monitorID'");

	// Check to see if the date passed in has a corresponding reading for that day.  If not we need to back out.
	$query = "SELECT date FROM data WHERE cast(date as date) = cast($statdate as date) AND monitorID='$monitorID'";	
	$res = getResult($query);
	if (!checkResult($res))
	{
		// No reading was received for this day so delete whatever stat may be there and bail ouy
		executeQuery("INSERT INTO NoReadings (monitorID, date) VALUES ('$monitorID', NOW())");
		executeQuery("DELETE FROM tankStats WHERE monitorID='$monitorID' AND cast(readingDate as date) = cast($statdate as date)");
		return;
	}


	// check to see if the date matches the date of a delivery
	$query = "SELECT 
				DATEDIFF( $statdate, d.deliveryDate ) as deliveryDaysAgo, 
				d.deliveryDate, 
				dt.quantity as deliveryQuantity, 
				dt.time as deliveryTime
				FROM delivery d, deliveryTanks dt
				WHERE d.deliveryID = dt.deliveryID
				AND (
					d.deliveryDate = DATE_ADD( cast( $statdate AS date ) , INTERVAL -1 DAY )
					OR d.deliveryDate = cast( $statdate AS date )
					)
				AND dt.monitorID = '$monitorID'";  

	$res = getResult($query);
	
	$deliveryWasMade = false;
	if (checkResult($res))
	{
		$deliveryWasMade = true;
		$line = $res->fetch_assoc();
		extract($line);
	}
	
	// get last tank reading
	if ($updateStat)
	{
		$query = "SELECT DISTINCT 
			value, 
			units,
			DATEDIFF( $statdate , date ) AS daysSinceLast,
			DATEDIFF( NOW(), $statdate ) AS daysSinceStatDate,
			date as fulldate, 
			cast(date as date) as date, 
			cast($statdate as date) as day 
		FROM 
			data 
		WHERE 
			monitorID='$monitorID' AND value > 0 and cast(date as date) <= cast($statdate as date) ORDER BY fulldate DESC LIMIT 1";
	}
	else
	{
		$query = "SELECT DISTINCT 
			value, 
			units,
			cast(date as date) as date, 
			DATEDIFF( NOW( ) , date ) AS daysSinceLast,
			date as fulldate, 
			DATE(NOW()) as day 
		FROM 
			data 
		WHERE 
			cast(date as date) <= DATE(NOW()) AND monitorID='$monitorID' AND value > 0 ORDER BY fulldate DESC LIMIT 1";
	}

	$res = getResult($query);
	if (!checkResult($res))
	{
		return -1;
	}

	$line = $res->fetch_assoc();
	extract($line);
	$daysSinceStatDate = empty($daysSinceStatDate) ? '0' : $daysSinceStatDate;
	
	$noReading = ($date != $day) ? '1' : 0;
	$res = getResult("SELECT 
						m.tolerance, 
						t.diameter, 
						t.capacity, 
						t.reorder, 
						t.low, 
						t.critical, 
						t.targetDosage,
						t.targetDaily,
						t.deviation_plus,
						t.deviation_minus,
						t.dosage_days
					FROM 
						tank t, 
						monitor m 
					WHERE 
						t.monitorID = m.monitorID AND 
						t.monitorID='$monitorID'");

	if (!checkResult($res))  // this isn't a monitored tank
	{
		if (!$updateStat) // statDate is NOW()
		{
			$unassMonitor = 1;
			$daysSinceLast = 'NULL';
			// delete any stats that have already been generating for today
			executeQuery("DELETE FROM tankStats WHERE monitorID='$monitorID' AND readingDate = DATE(NOW())");
			executeQuery("INSERT INTO tankStats 
			(monitorID, daysSinceLastReading, readingDate, latestDose, avgDose, exceedcap, high, low, nodose, normal, unass, noreading, high_low_message, statGenDate) VALUES
			('$monitorID', $daysSinceLast, '$fulldate', 0, 0, 0, 0, 0, 0, 0, 1, $noReading, '', NOW() )");
		}
	
		return;
	}
	$unassMonitor = 0;
	
	$line = $res->fetch_assoc();
	extract($line);
	
	if (!empty($targetDaily))
	{
		$dow = date('N');
		$targets = unserialize($targetDaily);
		$targetDosage = $targets[$dow];
	}
	
	// override targetDosage with the history for this day
	$query = "SELECT targetDose as targetDosage, targetDaily FROM tankHistory WHERE monitorID='$monitorID' order by date desc";
	$histRes = getResult($query);
	if (checkResult($histRes))
	{
		$histLine = mysql_fetch_assoc($histRes);
		extract($histLine);
		if (!empty($targetDaily))
		{
			$targets = unserialize($targetDaily);
			$dow = date('N', strtotime("-$daysSinceStatDate day"));  // day of week for this day
			$targetDosage = $targets[$dow];
		}
	}
	
	if ($units == 'Inches')
	{
		$value = inchToGal($value, $diameter);
	}
	
	if ($updateStat)
	{
		$query = "SELECT units, value as prevValue FROM data WHERE monitorID='$monitorID' and cast(date as date) < cast($statdate as date) ORDER BY date DESC";
	}
	else
	{
		$query = "SELECT units, value as prevValue FROM data WHERE monitorID='$monitorID' and cast(date as date) < DATE(NOW()) ORDER BY date DESC";
	}	


	$res = getResult($query);
	$nodose = 0;
	$low = 0;
	
	if (checkResult($res))
	{
		$line = $res->fetch_assoc();
		extract($line);
		
		if ($units == 'Inches')
		{
			$prevValue = inchToGal($prevValue, $diameter);
		}
		
		$varianceAllowed = $capacity * .20;
		$nodose = 0;
		
		if ($value >= $prevValue)
		{
			// increase in volume
			//ddie("if ($value - $prevValue < $varianceAllowed)");
			if ($value - $prevValue < $varianceAllowed)
			{
				$nodose = 1; 
				$HiLowDoseMsg = "L_Dose,No Dose";
				$low = 1;
			}
		}
	}
	else
	{
		$nodose = 1;
		$HiLowDoseMsg = "L_Dose,No Dose";
		$low = 1;
	}

	if ($updateStat)
	{
		$query = "select min(value) as prevDelValue from data where monitorID = '$monitorID' and cast(date as date) < cast($statdate as date) and date > date_add( cast($statdate as date), interval -2 day)";
	}
	else
	{
		$query = "select min(value) as prevDelValue from data where monitorID = '$monitorID' and cast(date as date) < DATE(NOW()) and date > date_add( DATE(NOW()), interval -2 day)";
	}	
	
	$prevRes = getResult($query);
	if (checkResult($prevRes))
	{
		$line = mysql_fetch_assoc($prevRes);
		extract($line);
	}
	else
	{
		$prevDelValue = 0;
	}



	if ($deliveryWasMade && $deliveryDaysAgo == 1 && ($prevDelValue > $value))
	{
		// A delivery was made either on this date or the day before.  
		if ($notify && $nodose == 0) // prevent tons of emails going out by passing false.  Mostly a debug switch 
		{
			$query = "SELECT t.tankName, s.contact, s.contactEmail, s.contactPhone, m.alertRule FROM tank t, monitor m, site s 
						WHERE t.monitorID=m.monitorID and m.siteID=s.siteID and t.monitorID='$monitorID'";

			$res2 = getResult($query);
			if (checkResult($res2))
			{
				$line = mysql_fetch_assoc($res2);
				extract($line);
			}
			else
			{
				$tankName = $monitorID;
			}

			$differenceInReadings = $value - $prevValue;
			$msg = "A delivery was supposed to occur for $tankName at $deliveryTime for $deliveryQuantity gallons.  This delivery has not been made.
					<br><br>
					<b>Responsible Person:</b><br>$contact<br>$contactEmail<br>$contactPhone";
			
			$sendAlert = false;
			if ($database == 'h202' && ($alertRule != 'NoAlert'))
			{
				if ($alertRule == 'Normal')
				{
					$sendAlert = true;
				}
				elseif ($alertRule == 'AfterDeliveryTime')
				{
					// compare last reading time to scheduled delivery time
					$deliveryTimeMil = timeToMilitary($deliveryTime);
					$query = "select * from data 
								where 
									cast(date as date) = '$deliveryDate' and 
									monitorID = '$monitorID' and 
									date > concat( '$deliveryDate', ' $deliveryTimeMil' )";
					$alertRes = getResult($query);
					if (mysql_num_rows($alertRes) > 0)
					{
						$sendAlert = true;
					}
				}
				else
				{
					// either AfterSecondReading or AfterThirdReading
					$query = "select * from data 
								where 
									cast(date as date) = '$deliveryDate' and 
									monitorID = '$monitorID'";
					$alertRes = getResult($query);
					$readingCount = mysql_num_rows($alertRes);
					if ($alertRule == 'AfterSecondReading')
					{
						$sendAlert = $readingCount > 2;
					}
					elseif ($alertRule == 'AfterThirdReading')
					{
						$sendAlert = $readingCount > 3;
					}
				}
				
				if ($sendAlert)
				{
					$logKey = "eccl411.12@gmail.com-$tankName-DeliveryNotMade";
					$activityRes = getResult("SELECT date FROM activityLog WHERE UserID = 'SYSTEM' AND cast(date as date) = DATE(NOW()) AND message='$logKey'");
					if (!checkResult($activityRes))
					{
						error_log("$logKey");
						
						sendMail("COMS System", "noreply@customhostingtools.com", 'mengram@h2o2.com', "Delivery alert for $tankName", $msg, "Michael Engram");
						sendMail("COMS System", "noreply@customhostingtools.com", 'mfoundoulis@h2o2.com', "Delivery alert for $tankName", $msg, "Mike Foundoulis");
						sendMail("COMS System", "noreply@customhostingtools.com", 'mkuper@usptechnologies.com', "Delivery alert for $tankName", $msg, "Michelle Kuper");
						sendMail("COMS System", "noreply@customhostingtools.com", 'rjoseph@h2o2.com', "Delivery alert for $tankName", $msg, "Joseph Ricardo");
						executeQuery("INSERT INTO activityLog (UserID, date, message, ip, hidden) VALUES ('SYSTEM', NOW(), '$logKey', '', 1)", 'INSERT');
					}
	//				
	//				$logKey = "dneff68@gmail.com@gmail.com-$tankName-DeliveryNotMade";
	//				$activityRes = getResult("SELECT date FROM activityLog WHERE UserID = 'SYSTEM' AND cast(date as date) = DATE(NOW()) AND message='$logKey'");
	//				if (!checkResult($activityRes))
	//				{
	//					error_log("$logKey");
	//					sendMail("COMS System", "dneff@customhostingtools.com", 'dneff68@gmail.com', "Delivery alert for $tankName", $msg, "Jim Frederick");
	//					executeQuery("INSERT INTO activityLog (UserID, date, message, ip, hidden) VALUES ('SYSTEM', NOW(), '$logKey', '', 1)", 'INSERT');
	//				}
				}
				
			}
		}		
	}
	elseif (!$deliveryWasMade && ($value > $prevDelValue))
	{

		// No delivery was made, however the tank increased in volume
		if ($notify && $nodose == 0) // prevent tons of emails going out by passing false.  Mostly a debug switch 
		{
			$value_gallons = $value;
			$prev_value_gallons = $prevValue;
			$differenceInReadings = $value_gallons - $prev_value_gallons;
			$valAllowance = ($prev_value_gallons * 1.20);
			if ( $value_gallons > $valAllowance )
			{
				$query = "SELECT t.tankName, s.contact, s.contactEmail, s.contactPhone, m.alertRule FROM tank t, monitor m, site s 
				WHERE t.monitorID=m.monitorID and m.siteID=s.siteID and t.monitorID='$monitorID'";
				$res2 = getResult($query);
				if (checkResult($res2))
				{
					$line = mysql_fetch_assoc($res2);
					extract($line);
				}
				else
				{
					$tankName = $monitorID;
				}
	
				$msg = "$tankName showed and increase in volume from $prevValue to $value equalling $differenceInReadings gallons. 
							No delivery was scheduled.<br><br>
							<b>Responsible Person:</b><br>$contact<br>$contactEmail<br>$contactPhone";

				$sendAlert = false;
				if ($database == 'h202' && ($alertRule != 'NoAlert'))
				{
					if ($alertRule == 'Normal')
					{
						$sendAlert = true;
					}
					elseif ($alertRule == 'AfterDeliveryTime')
					{
						// compare last reading time to scheduled delivery time
						$deliveryTimeMil = timeToMilitary($deliveryTime);
						$query = "select * from data 
									where 
										cast(date as date) = '$deliveryDate' and 
										monitorID = '$monitorID' and 
										date > concat( '$deliveryDate', ' $deliveryTimeMil' )";
						$alertRes = getResult($query);
						if (mysql_num_rows($alertRes) > 0)
						{
							$sendAlert = true;
						}
					}
					else
					{
						// either AfterSecondReading or AfterThirdReading
						$query = "select * from data 
									where 
										cast(date as date) = '$deliveryDate' and 
										monitorID = '$monitorID'";
						$alertRes = getResult($query);
						$readingCount = mysql_num_rows($alertRes);
						if ($alertRule == 'AfterSecondReading')
						{
							$sendAlert = $readingCount > 2;
						}
						elseif ($alertRule == 'AfterThirdReading')
						{
							$sendAlert = $readingCount > 3;
						}
					}
					
					if ($sendAlert)
					{
						$logKey = "eccl411.12@gmail.com-$tankName-IncreaseNoDeliveryScheduled";
						$activityRes = getResult("SELECT date FROM activityLog WHERE UserID = 'SYSTEM' AND cast(date as date) = DATE(NOW()) AND message='$logKey'");
						if (!checkResult($activityRes))
						{
							error_log("$logKey");
							sendMail("COMS System", "noreply@customhostingtools.com", 'mengram@h2o2.com', "Anomalous increase in volume for $tankName", $msg, "Michael Engram");
							sendMail("COMS System", "noreply@customhostingtools.com", 'mfoundoulis@h2o2.com', "Anomalous increase in volume for $tankName", $msg, "Mike Foundoulis");
							sendMail("COMS System", "noreply@customhostingtools.com", 'mkuper@usptechnologies.com', "Anomalous increase in volume for $tankName", $msg, "Michelle Kuper");
							sendMail("COMS System", "noreply@customhostingtools.com", 'rjoseph@h2o2.com', "Anomalous increase in volume for $tankName", $msg, "Joseph Ricardo");
							executeQuery("INSERT INTO activityLog (UserID, date, message, ip, hidden) VALUES ('SYSTEM', NOW(), '$logKey', '', 1)", 'INSERT');
						}
					}
				}
			}
		}
	}

	// exceed capacity
	$value_gallons = $value; //$units == 'Inches' ? inchToGal($value, $diameter) : $value;
	$exceedCap = $value_gallons > $capacity ? 1 : 0;
	
	// get average dose
	if ($updateStat)
	{
		$latestDose = getDose($monitorID, $daysSinceStatDate, $debug); 
		$prevDose = getDose($monitorID, $daysSinceStatDate+1, $debug);
	}
	else
	{
		$latestDose = getDose($monitorID, 0, $debug);
		$prevDose = getDose($monitorID, 1, $debug);

		//ddie("latestDose: $latestDose   prevDose: $prevDose");
	}
	$lastReadingGal = $value;

	// average dose for last 11 days
	$high = 0;
	$averageDose = 0;
	$doseCnt = 0;
	$doseSum = 0;

	$startDaysAgoForCalc = $updateStat ? $daysSinceStatDate+1 : 1;
	$endDaysAgoForCalc = $startDaysAgoForCalc + 10;

	for ($i = $startDaysAgoForCalc; $i <= $endDaysAgoForCalc; $i++)
	{
		$dose = getDose($monitorID, $i, $debug);
		if ($dose > 0)
		{
			$maxDose = empty($maxDose) ? $dose : max($maxDose, $dose);
			$minDose = empty($minDose) ? $dose : min($minDose, $dose);
			$doseCnt++;
			$doseSum += $dose;
		}
	}
	if ($doseCnt >= 4)
	{
		$doseSum = $doseSum - $maxDose - $minDose;
		$doseCnt = $doseCnt - 2; 
	}

	$normalOverride = false;
	if ($doseCnt > 0)
	{
		$averageDose = round( $doseSum / $doseCnt );
		$targetDosage = $targetDosage > 0 ? $targetDosage : $averageDose;
		if ($targetDosage > 0)
		{
			if ($deviation_plus > 0)
			{
				$doseHigh = $targetDosage + $deviation_plus;
			}
			else
			{
				$doseHigh = $targetDosage + ($targetDosage * .10);
			}

			if ($deviation_minus > 0)
			{
				$doseLow = $targetDosage - $deviation_minus;
				//ddie("$doseLow = $targetDosage - $deviation_minus;");
			}
			else
			{
				$doseLow = $targetDosage - ($targetDosage * .10);
			}
		}

		if ($latestDose < $doseLow)  // Last dosed too low
		{
			if ($latestDose <= 0) // Tank added volume  (Use to be just less than.  Changed to <= on Jan 12, 2009)
			{				
				if ($deliveryWasMade)
				{
					$latestDose = $averageDose; 
					$nodose = 0;
					$HiLowDoseMsg = "";
					$low = 0;
					
				}
				else
				{			
					// went up and a delivery was not scheduled.
					$diff = $targetDosage - $latestDose;
					$pctDiff = round( ($diff / $targetDosage) * 100, 0 );
					$pctDiff = abs($pctDiff);
					if ( $latestDose < $doseLow )
					{
						if ($nodose == 1)
						{
							$HiLowDoseMsg = "L_Dose,No Dose";
						}
						else
						{
							$HiLowDoseMsg = "L_Dose,Low Dose (%" . $pctDiff . ' down)';
						}
						$low = 1;
					}
				}
			}
			else  // tank volume went down (typical scenario)
			{
				if ($doseLow > 0)
				{
					if ($latestDose == 0 && $nodose == 0)
					{
						// no dose is always 100 % down
						$HiLowDoseMsg = "L_Dose,No Dose";
						$low = 1;
					}
					else
					{
						$debug .= "latestDose: $latestDose<br>";
						$diff = $targetDosage - $latestDose;
						$debug .= "targetDosage: $targetDosage - latestDose: $latestDose<br>";
						$debug .= "diff = $diff</br>";
						$pctDiff = round( ($diff / $targetDosage) * 100, 0 );
						$debug .= "<br>(percent difference is $pctDiff)   diff: $diff  -  pctDiff = ($diff / $latestDose) * 100";
						//ddie($debug);
						if ( $latestDose < $doseLow  && $nodose == 0)
						{
							//$pctDiff = round(($latestDose / $doseLow) * 100, 0);
							$HiLowDoseMsg = "L_Dose,Low Dose (%" . $pctDiff . " down)";
							$low = 1;
						}
					}			
				}
			}
		}
		elseif ($latestDose > $doseHigh)
		{
			$checkDose = false;
			if ( $deliveryWasMade && $deliveryDaysAgo==0)  // Tank dosed higher than the high limit
			{
				$checkDose = true;
			}
			
			if ( !$deliveryWasMade )
			{
				$checkDose = true;
			}

			if ($checkDose)
			{
				if ($doseHigh > 0)
				{
					$diff = $latestDose - $targetDosage;
					$pctDiff = round( ($diff / $targetDosage) * 100, 0 );
					//ddie("$pctDiff > $tolerance && $nodose == 0)");
					if ( $pctDiff > $tolerance && $nodose == 0)
					{
						$HiLowDoseMsg = "H_Dose,High Dose (%" . $pctDiff . ' up)';
						$high = 1;
					}
				}
			}
		}
	}
	else
	{

		// no doeses for last 11 days
		$HiLowDoseMsg = "L_Dose,No Dose";
		$low = 1;
	}

	$normal = ($nodose + $high + $low + $exceedCap + $noReading + $unassMonitor) == 0 ? 1 : 0;
	$levelStat = "Ok";
	$query = "SELECT 
				t.capacity, 
				t.reorder, 
				t.low as lowCap, 
				t.critical, 
				m.units, 
				t.leadTime,
				t.diameter
			from 
				tank t, 
				monitor m 
			where 
				t.monitorID=m.monitorID and 
				t.monitorID='$monitorID'
			LIMIT 1";
	$res = getResult($query);
	if (checkResult($res))
	{
		$line = $res->fetch_assoc();
		extract($line);
		$reorderLevel = ( $reorder / 100 ) * $capacity ;
		$lowLevel =  ( $lowCap / 100 ) * $capacity ;
		$criticalLevel = ( $critical / 100 ) * $capacity ;
		$reorderData = reorderInfo($monitorID);

		if ($value <= $criticalLevel )
		{
			$levelStat = "Critical";
		} 
		elseif ($value <= $lowLevel )
		{
			$levelStat = "Low";
		} 
		elseif ($reorderData['daysToDelivery'] <= $leadTime)
		{
			$levelStat = "Reorder";
		}
	}

	if ($updateStat)  // a statDate was passed in
	{
		if (isset( $daysSinceLast ))
		{	
			$query1 = "DELETE FROM tankStats WHERE monitorID='$monitorID' AND cast(readingDate as date) = '$date'";
			executeQuery($query1);
			$query = "INSERT INTO tankStats (statGenDate, daysSinceLastReading, monitorID, readingDate, latestDose, avgDose, exceedcap, high, low, nodose, normal, unass, noreading, high_low_message, levelStat) VALUES
						($statdate, $daysSinceLast, '$monitorID', '$fulldate', $latestDose, $averageDose, $exceedCap, $high, $low, $nodose, $normal, $unassMonitor, $noReading, '$HiLowDoseMsg', '$levelStat' )";

			executeQuery($query);
		}
	}
	else // generating stats for NOW()
	{
		$query1 = "DELETE FROM tankStats WHERE monitorID='$monitorID' AND cast(readingDate as date) = '$date'";
		executeQuery($query1);
		$query = "INSERT INTO tankStats (statGenDate, daysSinceLastReading, monitorID, readingDate, latestDose, avgDose, exceedcap, high, low, nodose, normal, unass, noreading, high_low_message, levelStat) VALUES
		(NOW(), $daysSinceLast, '$monitorID', '$fulldate', $latestDose, $averageDose, $exceedCap, $high, $low, $nodose, $normal, $unassMonitor, $noReading, '$HiLowDoseMsg', '$levelStat' )";
		executeQuery($query);
	}
}

function reorderInfo($monitorID, $deliveryDate='')
{
	$infoArray = array();
	// This query getis information from the tank, including its most recent reading
	$mvar = " and d.value > 0";	 // fix for zero readings
	$query = "SELECT 
				t.usableVolume, 
				d.value as lastReadingValue, 
				cast(d.date as date) as lastReadingDate, 
				t.reorder, 
				t.capacity, 
				t.leadTime, 
				t.concentration,
				p.value as product, 
				sup.supplierName, 
				sup.supplierID, 
				t.tankName, 
				t.timeOfDelivery,
				t.diameter,
				m.units
			FROM tank t, data d, product p, supplier sup, monitor m
			WHERE 
				t.monitorID=d.monitorID AND 
				t.prodID = p.prodID AND
				t.supplierID = sup.supplierID AND
				t.monitorID='$monitorID' and
				t.monitorID=m.monitorID $mvar
			ORDER BY d.date DESC LIMIT 1";
			

	$res = getResult($query);
	
	if (!checkResult($res))
	{		
		// If we didn't get any information from the tank that was passed in we run this query which is similar but
		// doesn't get the reading value.  This gets the information from the tank that it can, and returns an array
		// with 0 for the refill amount.
		$query = "SELECT 
			t.usableVolume, 
			t.diameter, 
			m.units, 
			t.reorder, 
			t.capacity, 
			t.leadTime, 
			t.concentration, 
			p.value AS product, 
			sup.supplierName, 
		sup.supplierID, t.tankName, t.timeOfDelivery
		FROM tank t, product p, supplier sup, monitor m
		WHERE t.prodID = p.prodID
		AND t.supplierID = sup.supplierID
		AND t.monitorID = '$monitorID'
		AND t.monitorID = m.monitorID
		LIMIT 1 ";
		$res = getResult($query);
		
		if (checkResult($res))
		{
			$line = $res->fetch_assoc();
			extract($line);
		}

		// If there is no result then there is no data for the tank.
		$res = getResult("SELECT DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 7 DAY), '%Y-%m-%d') as fillDate");
		$line = $res->fetch_assoc();
		extract($line);
		
		$infoArray['fillDate'] = $fillDate;
		$infoArray['refillAmount'] = 0;
		$infoArray['daysToDelivery'] = 7;

		$reorderLevel = 0;
		$remainingVolume = 0;
		$daysUntilFill = 7; // - $leadTime);

		$infoArray['monitorID'] = $monitorID;
		$infoArray['tankName'] = $tankName; //$monitorID;
		$infoArray['timeOfDelivery'] = $timeOfDelivery;
		$infoArray['product'] = $product;
		$infoArray['concentration'] = $concentration;
		$infoArray['supplierName'] = $supplierName;
		$infoArray['supplierID'] = $supplierID;
		$infoArray['reorderLevel'] = 0;
		return $infoArray;
	}

	$line = $res->fetch_assoc(); // get result from the first query above
	$avgDose = getDeliveryAvg($monitorID);
	
	extract($line);

	// Convert last reading from Inches to Gallons if necessary
	if ($units == 'Inches')
	{
		$lastReadingValue = inchToGal($lastReadingValue, $diameter);
	}

	// Here is where we calculate the days until delivery.  
	$moreWhere = '';
	if (!empty($deliveryDate)) // Delivery date would have been passed into this function
	{
		$moreWhere = " and d.deliveryDate <= '$deliveryDate'"; 
	}
		
	// Get the Future Quantity.  This is the sum of all gallons from deliveries between now and the delivery date passed in.	
	$query = "SELECT sum(dt.quantity) as futureQuantity
		FROM delivery d, deliveryTanks dt 
		WHERE 
			d.deliveryID=dt.deliveryID AND
			dt.monitorID = '$monitorID' AND
			d.status != 'Cancelled' and 
			d.deliveryDate >= DATE(NOW()) $moreWhere";
	$dres = getResult($query);

	if (checkResult($dres))
	{
		$dline = $dres->fetch_assoc();
		extract($dline);
	}

	$futureQuantity = empty($futureQuantity) ? 0 : $futureQuantity;
		
	if (!empty($deliveryDate))
	{
		$query = "SELECT 
					value as lastReading, 
					date as lastDeliveryDate, 
					TO_DAYS('$deliveryDate') - TO_DAYS(date) as daysUntilDelivery,
					TO_DAYS(NOW()) - TO_DAYS(date) as daysUntilDelivery2
				  FROM data 
				  WHERE 
				  	monitorID='$monitorID' 
					AND date < '$deliveryDate'
					ORDER BY date DESC LIMIT 20";
					
		$res = getResult($query);
		if (!checkResult($res))
		{
			// This would return false if there were never a reading for the tank.
			return false;
		}
		$line = $res->fetch_assoc();
		extract($line);

		if ($units == 'Inches')
		{
			// convert to Gallons
			$lastReading = inchToGal($lastReading, $diameter);
		}

		$reorderLevel = round($usableVolume * ($reorder / 100) );
		$remainingVolume = max(0, ($lastReading + $futureQuantity) - $reorderLevel);
		$tankVolumeOnThatDate = $remainingVolume - ($daysUntilDelivery * $avgDose);
		$refillAmount = $usableVolume - $tankVolumeOnThatDate - $reorderLevel;
		$refillAmount = $refillAmount < 0 ? $usableAmount : $refillAmount;

		// Load array with resulting values
		$infoArray['lastReading_'] 	= $lastReading;
		$infoArray['usableVolume_'] = $usableVolume;
		$infoArray['reorder_'] 		= $reorder;
		$infoArray['futureQuantity_'] 		= $futureQuantity;
		$infoArray['tankVolumeOnThatDate_'] = $tankVolumeOnThatDate;
		$infoArray['remainingVolume_'] 		= $remainingVolume;
		$infoArray['avgDose_'] 				= $avgDose;
		$infoArray['fillDate'] 				= $deliveryDate;
		$infoArray['refillAmount'] 			= $refillAmount;
		$infoArray['daysToDelivery'] 		= $daysUntilDelivery - $daysUntilDelivery2;
	}
	elseif (empty($avgDose))
	{
		// This is likely an unmonitored tank.  We get no readings, but we still need to handle 
		// this because deliveries are still made.
		$res = getResult("SELECT DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 7 DAY), '%Y-%m-%d') as fillDate");
		$line = $res->fetch_assoc();
		extract($line);
		
		$infoArray['fillDate'] = $fillDate;
		$infoArray['refillAmount'] = 0;
		$infoArray['daysToDelivery'] = 7;
		$infoArray['usableVolume_'] = $usableVolume;

		$reorderLevel = 0;
		$remainingVolume = 0;
		$daysUntilFill = 7; // - $leadTime);
		$infoArray['debug'] = "average dose empty";
	}
	else
	{
		// This section will typically be entered when making delivery predictions before a tank
		// has been selected.  No date was passed in so the next delivery date (days until delivery) is predicted
		$reorderLevel = round($usableVolume * ($reorder / 100) );
		$daysSinceLast = getDaysSinceLastReading($monitorID);	
		$remainingVolume = max(0, ($lastReadingValue + $futureQuantity) - $reorderLevel);

		$nonDosingDaysVolume = 0;
		if ($daysSinceLast > 0)
		{
			// we have at least one day of no reading.  Add to the remaining volume what the 
			// estimated dosing was for the days of no readings
			$nonDosingDaysVolume = ($daysSinceLast * $avgDose);			
		}
		$daysUntilFill = max(0, floor(($remainingVolume) / $avgDose)); // - $leadTime);
		

		// Adjust for cases where there wasn't a delivery today
		$daysUntilFill = $daysUntilFill - $daysSinceLast;
		$tankVolumeOnThatDate = $remainingVolume - ($daysUntilFill * $avgDose);
		$refillAmount = floor($usableVolume - $tankVolumeOnThatDate - $reorderLevel);

		$dow = "<br><font size=\"-2\">(%W)</font>"; // formatting for showing the day of week
		$res = getResult("SELECT DATE_FORMAT(DATE_ADD(NOW(), INTERVAL $daysUntilFill DAY), '%Y-%m-%d$dow') as fillDate");
		$line = $res->fetch_assoc();
		extract($line);
		
		$infoArray['usableVolume_'] = $usableVolume;
		$infoArray['debug'] = "$refillAmount = floor($usableVolume - $reorderLevel);";
		$infoArray['fillDate'] = $fillDate;
		$infoArray['refillAmount'] = $refillAmount + $nonDosingDaysVolume;
		$infoArray['daysToDelivery'] = $daysUntilFill;
	}

	// Finish loading array with general tank informaion
	$infoArray['monitorID'] 	= $monitorID;
	$infoArray['tankName'] 		= $tankName;
	$infoArray['timeOfDelivery']	= $timeOfDelivery;
	$infoArray['product'] 		= $product;
	$infoArray['concentration'] = $concentration;
	$infoArray['supplierName'] 	= $supplierName;
	$infoArray['supplierID'] 	= $supplierID;
	$infoArray['reorderLevel'] 	= $reorderLevel;	
	return $infoArray;
}



function getDeliverySites($deliveryID)
{
	$query = "SELECT DISTINCT s.siteLocationName
				FROM delivery d, deliverySite ds, site s
				WHERE 
					d.deliveryID=$deliveryID AND d.deliveryID=ds.deliveryID AND ds.siteID=s.siteID";
	
	
	
	$siteres = getResult($query);
	
	if (!checkResult($siteres))
	{
		return "no sites (delivery id $deliveryID)";
	}
	
	$sites = '';
	while ($line = mysql_fetch_assoc($siteres))
	{
		extract($line);
		$sites .= "$siteLocationName, ";
	}
	$sites = substr($sites, 0, strlen($sites) - 2); // strip trailing comma
	return $sites;
}

function getDeliveryInfo($deliveryID, $info='deliveryDateFmt')
{
	if ($info == 'deliveryDateFmt') // DELIVERY DATE FORMATTED
	{
		$fmt = "DATE_FORMAT(deliveryDate,'%M %D, %Y (%W)') as ";
	}
	else
	{
		$fmt = '';
	}
		$query = "SELECT $fmt deliveryDate FROM delivery WHERE deliveryID = $deliveryID";
		$res = getResult($query);
		if (checkResult($res))
		{
			$line = $res->fetch_assoc();
			extract($line);
			return $deliveryDate;
		}
	
}

function sendDeliveryEmails($deliveryID, $displayOnly=0)
{
	global $USERTYPE, $USERID, $database;
	if ( $USERTYPE == 'customer' )
	{
		$custTanks = "and s.deliveryEmailDist LIKE '%$USERID%'";
	}
	
	$query = "SELECT DISTINCT 
				DATE_FORMAT(d.deliveryDate, '%m/%d/%Y') as deliveryDate,
				d.product, 
				d.concentration, 
				s.siteID, 
				d.deliveryID, 
				d.carrierID, 
				s.siteLocationName, 
				s.deliveryEmailDist, 
				d.carrierEmailDist, 
				d.deliveryKey,
				ds.po
			FROM 
				delivery d, deliverySite ds, site s
			WHERE 
				d.deliveryID=$deliveryID AND 
				d.deliveryID=ds.deliveryID AND
				ds.siteID=s.siteID
				$custTanks
			ORDER BY s.siteID";
	$siteres = getResult($query);

	if (!checkResult($siteres))
	{
		return;
	}
	
	$emailArray = array();

	$contactres = getResult("SELECT CONCAT(u.firstName, ' ', u.lastName) AS deliveryContact, u.phone as deliveryPhone 
			FROM delivery d, users u WHERE d.internalContact=u.loginID 
			and d.deliveryID=$deliveryID LIMIT 1");
	if (checkResult($contactres))
	{
		$contactline = mysql_fetch_assoc($contactres);
		extract($contactline);
	}
		
	if ($displayOnly == 1)
	{
		$noEmailLog = '&disp=1';
		echo("<h3>Email Distribution</h3>");
		echo("<table align=\"center\" width=\"700\" border=\"1\" cellspacing=\"0\" cellpadding=\"5\">");
	}
	else
	{
		// clear the logs before we send out the emails
		$noEmailLog = '&disp=0';
		executeQuery("DELETE FROM deliveryEmailLog WHERE deliveryID=$deliveryID");
	}
	$sentAddresses = '';
	$notesOut = 0;
	
	// get delivery nots
	$notres = getResult("SELECT notes FROM delivery WHERE deliveryID=$deliveryID LIMIT 1");
	if (checkResult($notres))
	{
		$noteline = mysql_fetch_assoc($notres);
		extract($noteline);
		$notes = empty($notes) ? '' : "<b>Message:</b> $notes<br>";
	}
	
	$flag = 0;
	while ($line = mysql_fetch_assoc($siteres))
	{
		extract($line);
		// email body template
		$tBody = "<table width='600' border='1' cellspacing='1' cellpadding='5'>
		  <tr>
			<td nowrap='nowrap' bordercolor='#666666'><div align='right'><strong>Delivery Date:</strong></div></td>
			<td nowrap='nowrap' bordercolor='#666666'><div align='left'>%s</div></td>
			<td nowrap='nowrap' bordercolor='#666666'><div align='right'><strong>Approved By: </strong></div></td>
			<td nowrap='nowrap' bordercolor='#666666'><div align='left'>%s</div></td>
		  </tr>
		  <tr>
			<td nowrap='nowrap' bordercolor='#666666'><div align='right'><strong>Product:</strong></div></td>
			<td nowrap='nowrap' bordercolor='#666666'><div align='left'>%s</div></td>
			<td nowrap='nowrap' bordercolor='#666666'><div align='right'><strong>Contact Phone: </strong></div></td>
			<td nowrap='nowrap' bordercolor='#666666'><div align='left'>%s</div></td>
		  </tr>
		</table>";
	
		if ($notesOut == 0 && !empty($notes) && $displayOnly == 1)
		{
			if ( $USERTYPE != 'customer' )
			{
				echo "$notes<br>";
			}
			$notesOut = 1;
		}

		//$pct = strpos('%', $concentration) === false ? 'percent' : '';
		$subject = " Delivery of $product ($concentration $pct) on $deliveryDate";

		/*
			INTERNAL CONTACT EMAILS
		*/
		$query = "SELECT s.id as emailID, s.siteID as emailSiteID, CONCAT(i.FirstName, ' ', i.LastName) AS internalContact, i.email AS internalEmail FROM internalEmailDist i, internalEmailDistSites s
					WHERE i.id=s.id AND s.siteID=$siteID and s.selected = 1";
		$res = getResult($query);

		if (checkResult($res))
		{
			while ($line = $res->fetch_assoc())
			{
				extract($line);
				if (!empty($internalEmail))
				{
					if ( strpos($sentAddresses, "$internalEmail") !== false && $displayOnly==1)
					{
						continue;
					}
					$sentAddresses .= "$internalEmail-";
					$internalContact = empty($internalContact) ? 'US Peroxide Customer' : $internalContact;

					
					$internalEmail_out = htmlentities($internalEmail, ENT_QUOTES);
					$internalEmail_sql = fixSingleQuotes($internalEmail);
					if ( strpos($_SERVER['DOCUMENT_ROOT'], 'h202-dev') === false )
					{
					$linkval = "<a href='http://h202.customhostingtools.com/manifest.php?id=$deliveryID&eid=$internalEmail_out&key=$deliveryKey$noEmailLog' target='_blank'>Delivery Request</a>";
					}
					else
					{
						$linkval = "<a href='http://comdev.customhostingtools.com/manifest.php?id=$deliveryID&eid=$internalEmail_out&key=$deliveryKey$noEmailLog' target='_blank'>Delivery Request</a>";
					}
					$emailBody = ''; //sprintf($tBody, $deliveryDate, $deliveryContact, "$product $concentration%", $deliveryPhone);
					$emailBody .= "$notes<br><br>Click on Link below for detailed information<br><br>$linkval";

					// debug override
					// $emailBody .= "<hr>debug: will be sent to $internalEmail";
					if ($displayOnly==1)
					{
						if ($flag == 0 && ( $USERTYPE != 'customer' ))
						{
							echo "<tr class='spinTableBarEven'>
							<td colspan='3'  class='spinMedTitle'>Internal</td>
							</tr>";
							$flag = 1;
						}
						
						$eres = getResult("SELECT DATE_FORMAT(dateReceived, '%m/%d/%Y %r') as readDate FROM deliveryEmailLog WHERE deliveryID=$deliveryID AND id='$internalEmail_sql' AND dateReceived != '0000-00-00 00:00:00'");
						$readon = '';
						if (checkResult($eres))
						{
							$eline = mysql_fetch_assoc($eres);
							extract($eline);
							$readon = " <span class='spinAlert'>(read: $readDate)</span>";
						}

						if ( $USERTYPE != 'customer' )
						{
							echo "<tr class='spinTableBarOdd'>
							<td width='25%' nowrap valign='top'>
							<blockquote>$internalContact:</blockquote></td>
							<td colspan='2' valign='top'>
							$internalEmail ($linkval) $readon</td>
							</tr>\n";
						}

					}
					else
					{
						//$internalEmail = 'eccl412@ca.rr.com';
						//sendMail('US Peroxide Inventory Management', 'USPDelivery@h2o2.com', $internalEmail, $subject, $emailBody, $internalContact);
						if (empty($emailArray[$internalEmail]))
						{
							executeQuery("INSERT INTO deliveryEmailLog (deliveryID, category, id, dateSent) VALUES ($deliveryID, 'internal', '$internalEmail_sql', NOW())");
							$emailArray[$internalEmail] = "$emailBody~~~$internalContact~~~$siteLocationName";
						}
						else
						{
							$emailArray[$internalEmail] .= "/$siteLocationName";
						} 
					}
				}
			}
		}
	}
	
	
	/*
		SUPPLIER CONTACT EMAILS
	*/
	$query = "SELECT s.siteLocationName, ds.siteID, sup.supplierName, CONCAT(sd.FirstName, ' ', sd.LastName) AS supplierContact, sd.email as supplierEmail
				FROM deliveryEmailSupplierSelected des, supplierEmailDist sd,
					supplier sup, deliverySite ds, site s
				WHERE 
				des.deliveryID = $deliveryID and
				des.emailID = sd.id and
				ds.siteID=s.siteID and
				des.deliveryID = ds.deliveryID and
				sup.supplierID = sd.supplierID and
				des.selected = 1
				$custTanks
				ORDER BY sd.LastName, s.siteID";

	$flag = 0;
	$res = getResult($query);
	
	if (checkResult($res))
	{
		while ($line = $res->fetch_assoc())
		{
			extract($line);
			if ( strpos($sentAddresses, "$supplierEmail") !== false && $displayOnly==1)
			{
				continue;
			}
			
			if (!empty($supplierEmail))
			{
				$sentAddresses .= "$supplierEmail-";
				$supplierContact = empty($supplierContact) ? 'US Peroxide Customer' : $supplierContact;

				$supplierEmail_sql = fixSingleQuotes($supplierEmail);
				$supplierEmail_out = htmlentities($supplierEmail, ENT_QUOTES);
				if ( strpos($_SERVER['DOCUMENT_ROOT'], 'h202-dev') === false )
				{
				$linkval = "<a href='http://h202.customhostingtools.com/manifest.php?id=$deliveryID&eid=$supplierEmail_out&key=$deliveryKey$noEmailLog' target='_blank'>Delivery Request</a>";
				}
				else
				{
					$linkval = "<a href='http://comdev.customhostingtools.com/manifest.php?id=$deliveryID&eid=$supplierEmail_out&key=$deliveryKey$noEmailLog' target='_blank'>Delivery Request</a>";
				}
				$emailBody = ''; //sprintf($tBody, $deliveryDate, $deliveryContact, "$product $concentration%", $deliveryPhone);
				$emailBody .= "$notes<br><br>Click on Link below for detailed information<br><br>$linkval";

				if ($displayOnly==1)
				{
					if ($flag == 0 && ( $USERTYPE != 'customer' ))
					{
						echo "<tr class='spinTableBarEven'>
							<td colspan='2'  class='spinMedTitle'>Supplier: $supplierName</td>
							</tr>";
						$flag = 1;
					}
		
					$eres = getResult("SELECT DATE_FORMAT(dateReceived, '%m/%d/%Y %r') as readDate FROM deliveryEmailLog WHERE deliveryID=$deliveryID AND id='$supplierEmail_sql' AND dateReceived != '0000-00-00 00:00:00'");
					$readon = '';
					if (checkResult($eres))
					{
						$eline = mysql_fetch_assoc($eres);
						extract($eline);
						$readon = " <span class='spinAlert'>(read: $readDate)</span>";
					}

					if ( $USERTYPE != 'customer' )
					{
						echo "<tr class='spinTableBarOdd'>
						<td nowrap valign='top'>
						&nbsp;</td>
						<td valign='top'>
						$supplierEmail ($linkval) $readon</td>
						</tr>";
					}
				}
				else
				{
					if (empty($emailArray[$supplierEmail]))
					{
						executeQuery("INSERT INTO deliveryEmailLog (deliveryID, category, id, dateSent) VALUES ($deliveryID, 'supplier', '$supplierEmail_sql', NOW())");
						$emailArray[$supplierEmail] = "$emailBody~~~$supplierContact~~~$siteLocationName";
					}
					else
					{
						$emailArray[$supplierEmail] .= "/$siteLocationName";
					} 
				}
			}
		}
	}

	$flag = 0;
	mysql_data_seek($siteres, 0);
	while ($line = mysql_fetch_assoc($siteres))
	{
		$flag = 0;
		extract($line);
		$carrierEmailOut = '';
		if (!empty($carrierEmailDist))
		{
				$emails = explode(chr(13), $carrierEmailDist);
				foreach($emails as $carrierEmail)
				{
					// $carrierEmail = str_replace(chr(13), '', $carrierEmail);
					$carrierEmail = trim($carrierEmail);
					if (empty($carrierEmail))
					{
						continue;
					}
				
					if ( strpos($sentAddresses, "$carrierEmail") !== false && $displayOnly==1)
					{
						continue;
					}
					$sentAddresses .= "$carrierEmail-";
					
					$carrierEmail_sql = fixSingleQuotes($carrierEmail);
					$carrierEmail_out = htmlentities($carrierEmail, ENT_QUOTES);
					if ( strpos($_SERVER['DOCUMENT_ROOT'], 'h202-dev') === false )
					{
					$linkval = "<a href='http://h202.customhostingtools.com/manifest.php?id=$deliveryID&eid=$carrierEmail_out&key=$deliveryKey$noEmailLog' target='_blank'>Delivery Request</a>";
					}
					else
					{
						$linkval = "<a href='http://comdev.customhostingtools.com/manifest.php?id=$deliveryID&eid=$carrierEmail_out&key=$deliveryKey$noEmailLog' target='_blank'>Delivery Request</a>";
					}
					
					$emailBody = ''; //sprintf($tBody, $deliveryDate, $deliveryContact, "$product $concentration%", $deliveryPhone);
					$emailBody .= "$notes<br><br>Click on Link below for detailed information<br><br>$linkval";
	
					if ($displayOnly==1)
					{
						if ($flag == 0 && ( $USERTYPE != 'customer' ))
						{
							echo "<tr class='spinTableBarOdd'>
							<td nowrap valign='top'>
							Carrier Emails</td>
							<td valign='top'>";
							$flag = 1;
						}
						
						$eres = getResult("SELECT DATE_FORMAT(dateReceived, '%m/%d/%Y %r') as readDate FROM deliveryEmailLog WHERE deliveryID=$deliveryID AND id='$carrierEmail_sql' AND dateReceived != '0000-00-00 00:00:00'");
						$readon = '';
						if (checkResult($eres))
						{
							$eline = mysql_fetch_assoc($eres);
							extract($eline);
							$readon = " <span class='spinAlert'>(read: $readDate)</span>";
						}
						$carrierEmailOut .= "$carrierEmail ($linkval) $readon<br>";
					}
					else
					{
						if (empty($emailArray[$carrierEmail]))
						{
							executeQuery("INSERT INTO deliveryEmailLog (deliveryID, category, id, dateSent) VALUES ($deliveryID, 'carrier', '$carrierEmail_sql', NOW())");
							$emailArray[$carrierEmail] = "$emailBody~~~US Peroxide Customer~~~$siteLocationName";
						}
						else
						{
							$emailArray[$carrierEmail] .= "/$siteLocationName";
						} 
					}
				}
				if (!empty($carrierEmailOut) && ( $USERTYPE != 'customer' ))
				{
					echo "$carrierEmailOut</td></tr>";
				}
		}
	}
	
	mysql_data_seek($siteres, 0);
	$siteEmailOut = '';
	while ($line = mysql_fetch_assoc($siteres))
	{
		$flag = 0;
		extract($line);
		if (!empty($deliveryEmailDist))
		{
			$emails = explode(chr(13), $deliveryEmailDist);
			foreach($emails as $siteEmail)
			{
				$siteEmail = trim($siteEmail);
				if (empty($siteEmail))
				{
					continue;
				}
				
				if ( strpos($sentAddresses, "$siteEmail:$po") !== false || empty($siteEmail))
				{
					if ($displayOnly==1)
					{
						continue;
					}
				}
		
				$sentAddresses .= "$siteEmail:$po-";
				$siteEmail_sql = fixSingleQuotes($siteEmail);
				$siteEmail_out = htmlentities($siteEmail, ENT_QUOTES);
				if ( strpos($_SERVER['DOCUMENT_ROOT'], 'h202-dev') === false )
				{
				$linkval = "<a href='http://h202.customhostingtools.com/manifest.php?id=$deliveryID&eid=$siteEmail_out&custid=$po$noEmailLog' target='_blank'>Delivery Request</a>";
				}
				else
				{
					$linkval = "<a href='http://comdev.customhostingtools.com/manifest.php?id=$deliveryID&eid=$siteEmail_out&custid=$po$noEmailLog' target='_blank'>Delivery Request</a>";
				}
				$emailBody = ''; //sprintf($tBody, $deliveryDate, $deliveryContact, "$product $concentration%", $deliveryPhone);
				$emailBody .= "<br>Click on Link below for detailed information<br><br>$linkval";  // Jim suggested that notes should not be sent to the customer

				if ($displayOnly==1)
				{		
				  if ($flag == 0)
				  {
				  	echo "
					  <tr class='spinTableBarEven'>
						<td colspan='3'  class='spinMedTitle'>Site: $siteLocationName</td>
					  </tr>
					  <tr class='spinTableBarOdd'>
						<td colspan='3'>
						<blockquote>";
					$flag = 1;
				  }
	
				  $eres = getResult("SELECT DATE_FORMAT(dateReceived, '%m/%d/%Y %r') as readDate FROM deliveryEmailLog WHERE deliveryID=$deliveryID AND id='$siteEmail_sql' AND dateReceived != '0000-00-00 00:00:00'");
				  $readon = '';
				  if (checkResult($eres))
				  {
				  	$eline = mysql_fetch_assoc($eres);
					extract($eline);
				  	$readon = " <span class='spinAlert'>(read: $readDate)</span>";
				  }
				  $siteEmailOut .= "$siteEmail ($linkval) $readon<br>";
				}
				else
				{
					if (empty($emailArray[$siteEmail]))
					{
						executeQuery("INSERT INTO deliveryEmailLog (deliveryID, category, id, dateSent) VALUES ($deliveryID, 'site', '$siteEmail_sql', NOW())");
						$emailArray[$siteEmail] = "$emailBody~~~US Peroxide Customer~~~$siteLocationName";
					}
					else
					{
						$emailArray[$siteEmail] .= "/$siteLocationName";
					} 
				}
			}
		}
		if (!empty($siteEmailOut))
		{
			echo "$siteEmailOut
				</blockquote>
				</td>
				</tr>";	
			$siteEmailOut = '';
		}
	}	
	if ($displayOnly == 1)
	{
		echo("</table>");
	}
	else
	{
		executeQuery("UPDATE delivery SET committed=1 WHERE deliveryID=$deliveryID LIMIT 1");
		$sites = getDeliverySites($deliveryID);
		logAction("Delivery emails sent for $sites on $deliveryDate", 1);
		foreach ($emailArray as $emailAddr => $emailInfo)
		{
			list($body, $contact, $sites) = explode('~~~', $emailInfo);
			$subjectOut = "$sites $subject";
			
			// START DEBUG CODE
			if (david())
			{
				$body .= "<hr>debug: will be sent to $emailAddr";
				//$emailAddr = 'eccl412@ca.rr.com';
				$emailAddr = 'dneff68@gmail.com';
			}
			// END DEBUG CODE
	
			if ($database == 'h202')
				sendMail('US Peroxide Inventory Management', 'USPDelivery@h2o2.com', $emailAddr, $subjectOut, $body, $contact);
			//echo("$emailAddr, <b>$subjectOut</b><br><br>");
		}
	}
}


function getCustomerSummarySites($monitorID)
{
	$query = "SELECT s.siteID as singleSite from site s, monitor m where s.siteID=m.siteID and m.monitorID='$monitorID'";
	$res = getResult($query);
	if (!checkResult($res)) return false;
	$line = $res->fetch_assoc();
	extract( $line );
	
	$query = "select email as customerEmail from customerLoginEmail where siteID like '%$singleSite%'";
	$res = getResult($query);
	if (!checkResult($res)) return false;
	$line = $res->fetch_assoc();
	extract( $line );
	
	return $customerEmail;
	
}

function timeToMilitary($t)
{
	if (empty($t))
	{
		return '00:00:00';
	}
		
	list($hm, $ampm) = explode(' ', $t);
	list($h, $m) = explode(':', $hm);
	
	if ($ampm == 'am')
	{
		if ($h == '12')	$hm = "00:$m";
		
		return "$hm:00";
	}
	else
	{
		if ($h == '12')
			return "$hm:00";

		$h = $h + 12;
		return "$h:$m:00";
	}	
}

function convertUnitsToGallons($value, $unit)
{
	if ($unit == 'Ton_Metric')
	{
		$value = $value * 2204.6226218;			
	}
	elseif ($unit == 'Ton_US')
	{
		$value = $value * 2000;
	}
	elseif ($unit == 'Kilogram')
	{
		$value = $value * 2.20462262;
	}
	elseif ($unit == 'Liters')
	{
		$value = $value / 3.78541178;
	}
	elseif ($unit == 'Drum')
	{
		$value = $value * 55;
	}
	elseif ($unit == "Tote_300")
	{
		$value = $value * 300;
	}
	elseif ($unit == "Tote_320")
	{
		$value = $value * 320;
	}
	elseif ($unit == "Tote_330")
	{
		$value = $value * 330;
	}
	
	return ceil($value);
}

function convertUnits($weight, $unit)
{
	if ($unit == 'Ton_Metric')
	{
		$weight = $weight / 2204.6226218;			
		$unit = 'Metric Tons'; 
	}
	elseif ($unit == 'Ton_US')
	{
		$weight = $weight / 2000;
		$unit = 'Tons - US'; 
	}
	elseif ($unit == 'Kilogram')
	{
		$weight = $weight / 2.20462262;
		$unit = 'Kilograms'; 
	}
	elseif ($unit == 'Liters')
	{
		$weight = $weight * 3.78541178;
		$unit = 'Liters'; 
	}
	elseif ($unit == 'Drum')
	{
		$weight = $weight / 55;
		$unit = 'Drums'; 
	}
	elseif ($unit == "Tote_300")
	{
		$weight = $weight / 300;
		$unit = 'Totes'; 
	}
	elseif ($unit == "Tote_320")
	{
		$weight = $weight / 320;
		$unit = 'Totes'; 
	}
	elseif ($unit == "Tote_330")
	{
		$weight = $weight / 330;
		$unit = 'Totes'; 
	}
	elseif ($unit == 'Unit')
	{
		$unit = 'Units'; 
	}
	
	return array($unit, $weight);
}

function ddie($text='')
{
	if (david())
	{
		die('die: ' . $text);
	}
}

function microtime_used($before,$after) {
    return (substr($after,11)-substr($before,11))
        +(substr($after,0,9)-substr($before,0,9));
}
?>