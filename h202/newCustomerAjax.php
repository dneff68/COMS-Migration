<?
session_start();
include_once 'GlobalConfig.php';
include_once 'h202Functions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';

error_log("action: $action");
if ($REQUEST_METHOD == 'POST')
{
	if ($action == 'sendEmail')
	{
		// get value of sel_site_info_supplier_1
		$query = "SELECT section1 FROM newCustomerForm WHERE keyCode = '$keyCode' LIMIT 1";
		$res = getResult($query);

		$errFlag = 1;
		if (checkResult($res))
		{
			$line = $res->fetch_assoc();
			extract($line);
			$values = json_decode($section1);
			$supplierID = $values->{'sel_site_info_supplier_1'};
			$errFlag = empty($supplierID); // setting to 0 clears error flag
		}

		if ($errFlag == 1)
		{
			echo "Error: Unable to get email recipient list.  Please verify a supplier was entered for this New Customer Form.";
			return;
		}
		
		// get email list from supplierEmailDist
		$subject = "New Customer Form";
		$body = "There is a new customer form ready for your review: <br /><br /><a href='http://h202.customhostingtools.com/newCustomerForm.php?key=" . $keyCode . "&st=1'>Click Here</a> to view customer form.";
		$res = getResult("SELECT supplierID, email as emailTo FROM supplierEmailDist where supplierID = $supplierID and selected = 1 order by email");
		if (checkResult($res))
		{
			$emailDistList = '';
			while ($line = $res->fetch_assoc())
			{
				extract($line);
				$emailDistList .= "<br />$emailTo";
				if ($database == 'h202') sendMail("COMS System", "noreply@customhostingtools.com", $emailTo, "New Customer Form", $body, $emailTo);
			}
		}
		
		// send additional emails if listed in newCustomerEmailDist
		$res = getResult("SELECT emailAddresses FROM newCustomerEmailDist where supplierID = $supplierID");
		if (checkResult($res))
		{
			$line = $res->fetch_assoc();
			extract($line);
			$emailArr = explode(',', $emailAddresses);
			foreach( $emailArr as $emailTo )
			{
				$emailDistList .= "<br />$emailTo";
	 		 	if ($database == 'h202') sendMail("COMS System", "noreply@customhostingtools.com", $emailTo, "New Customer Form", $body, $emailTo);
			}
		}
		
		echo("Sent To:" . $emailDistList);
	}
	elseif ($action == 'setPage')
	{
		if (empty($currentPage)) return;
		if (empty($CURRENT_PAGE))
		{
			session_register('CURRENT_PAGE');
		}
		$CURRENT_PAGE = $currentPage;
	}
	elseif ($action == 'getSection')
	{
		$query = "SELECT section$section as sectionVals FROM newCustomerForm WHERE keyCode = '$KEY_CODE' LIMIT 1";
		$res = getResult($query);
		if (checkResult($res))
		{
			$line = $res->fetch_assoc();
			extract($line);
			echo $sectionVals;
		}
		else
			echo "FAIL: $query";
	}
	elseif ($action == 'getEmail')
	{
		// SELECT email, phone FROM users WHERE CONCAT(FirstName, ' ', LastName) = 'Chip Kahl' LIMIT 1
		$query = "SELECT email, phone, cell, fax FROM users WHERE CONCAT(trim(FirstName), ' ', trim(LastName)) = '$Name' LIMIT 1";
		$res = getResult($query);
		if (checkResult($res))
		{
			$line = $res->fetch_assoc();
			extract($line);
			$phone = str_replace('(', '', $phone);
			$phone = str_replace(')', '', $phone);
			$phone = str_replace(' ', '', $phone);
			$phone = str_replace('-', '', $phone);
			$a = substr($phone, 0, 3);
			$b = substr($phone, 3, 3);
			$c = substr($phone, 6, 4);
			$phone = "($a) $b-$c";

			$cell = str_replace('(', '', $cell);
			$cell = str_replace(')', '', $cell);
			$cell = str_replace(' ', '', $cell);
			$cell = str_replace('-', '', $cell);
			$a = substr($cell, 0, 3);
			$b = substr($cell, 3, 3);
			$c = substr($cell, 6, 4);
			$cell = "($a) $b-$c";

			$fax = str_replace('(', '', $fax);
			$fax = str_replace(')', '', $fax);
			$fax = str_replace(' ', '', $fax);
			$fax = str_replace('-', '', $fax);
			$a = substr($fax, 0, 3);
			$b = substr($fax, 3, 3);
			$c = substr($fax, 6, 4);
			$fax = "($a) $b-$c";


			echo "$email|$phone|$cell|$fax" ;
		}
		else
		{
			echo $query; //'enter email';
		}
	}
	elseif ($action == 'commit')
	{
		$query = "SELECT section1 FROM newCustomerForm WHERE keyCode = '$KEY_CODE'";
		$res = getResult($query);

		if (checkResult($res))
		{
			$line = $res->fetch_assoc();
			extract($line);
			$values = json_decode($section1);
			$updated_by = $values->{'updated_by'};
		}
		$query = "UPDATE newCustomerForm SET committed=1, userID='$updated_by' WHERE keyCode = '$KEY_CODE' LIMIT 1";
		executeQuery($query);
		$KEY_CODE = '';
		unset($KEY_CODE);	
		logAction("New Customer From Created");
	}
	elseif ($action == 'mapToCOMS')
	{
			$res = getResult("SELECT monitorID FROM tank WHERE monitorID = 'none-$KEY_CODE' LIMIT 1");			
			if (checkResult($res))
			{
				//executeQuery("DELETE FROM tank WHERE monitorID='$KEY_CODE' LIMIT 1");
				echo 'This tank has already been added.';
				return;
			}
			$res = getResult("SELECT monitorID FROM monitor WHERE monitorID = 'none-$KEY_CODE' LIMIT 1");			
			if (checkResult($res))
			{
				//executeQuery("DELETE FROM monitor WHERE monitorID='$KEY_CODE' LIMIT 1");
				echo "The monitor 'none-$KEY_CODE has already been added.";
				return;
			}
			
		
			$query = "SELECT section1, section2, section3 FROM newCustomerForm WHERE keyCode = '$KEY_CODE'";
			error_log($query);
			$res = getResult($query);
	
			if (checkResult($res))
			{
				$line = $res->fetch_assoc();
				extract($line);
				$values = json_decode($section1, true);
				extract($values);
				$values = json_decode($section2, true);
				//echo($section2);
				//return;
				if (is_array($section2))
					extract($values);
				$values = json_decode($section3, true);
				extract($values);
			}
			$monitorID = 'none-' . $KEY_CODE;
		

			$query = "SELECT siteLocationName as tmp_SiteLocationName, siteID as tmp_siteID FROM site WHERE siteLocationName = '$customer_name_formal' LIMIT 1";
			//echo($query);
			//return;
			
			$res = getResult($query);			
			if (checkResult($res))
			{
				$line = $res->fetch_assoc();
				extract($line);
				
				echo "The site '$customer_name_formal (id:$tmp_siteID)' already exists.  Update the customer name in Section 1 to continue.";
				return;
			}
			
			$customer_name_formal = fixSingleQuotes($customer_name_formal);
			$cust_contact_primary = fixSingleQuotes($cust_contact_primary);
			$site_address = fixSingleQuotes($site_address);
			$siteQuery = "INSERT INTO site (siteLocationName, address, city, state, zip, contact, contactPhone, contactEmail) VALUES 
					('$customer_name_formal', '$site_address', '$site_city', '$sel_site_state', '$site_zipcode', '$cust_contact_primary', '$cust_contact_primary_phone', '$cust_contact_primary_email')";
			error_log($siteQuery);

			$siteID = executeQuery($siteQuery, 'INSERT');


			// add new monitor
			$monitorQuery = "INSERT INTO monitor (monitorID, siteID, startDate, status) values
			('$monitorID', $siteID, NOW(), 'Inactive')";
			error_log($monitorQuery);
			executeQuery($monitorQuery);

			$supplierID = $sel_site_info_supplier_1; 
			$tank_details_tank_total_capacity = empty($tank_details_tank_total_capacity) ? 0 : $tank_details_tank_total_capacity;

			$site_info_sitename_1 = fixSingleQuotes($site_info_sitename_1);
			$directions_to_site = fixSingleQuotes($directions_to_site);
			$sel_tank_details_orientation = empty($sel_tank_details_orientation) ? 'Vertical' : $sel_tank_details_orientation;
			$sel_tank_details_tank_count = empty($sel_tank_details_tank_count) ? 1 : $sel_tank_details_tank_count;
			$deliveryNote = "Preferred Delivery Days: $preferred_delivery_days<br />Preferred Delivery Hours: $preferred_delivery_hours";
			$deliveryNote = fixSingleQuotes($deliveryNote);
			$tankQuery 	= "INSERT INTO tank (
							tankID,
							supplierID,
							monitorID,
							carrierID,
							tankName,
							prodID,
							notes,
							height,
							diameter,
							multiple,
							orientation,
							usableVolume,
							deliveryNote,
							deliveryNoteDate
							)
						VALUES
							(
								'$monitorID', $supplierID, '$monitorID', 0, '$site_info_sitename_1', $sel_site_info_product_1, '$directions_to_site', 
								$tank_details_height, $tank_inner_diameter, $sel_tank_details_tank_count, 
								'$sel_tank_details_orientation', 
								$tank_details_tank_total_capacity,
								'$deliveryNote', NOW())";

			executeQuery($tankQuery);
			executeQuery("UPDATE newCustomerForm SET complete = 1 WHERE keyCode='$KEY_CODE'");

			$htmlOut = "<h3>New Customer Form Submitted</h3>";
			$htmlOut .= "<br/><strong>Site Name:</strong> $customer_name_formal";
			$htmlOut .= "<br/><strong>Tank:</strong> $site_info_sitename_1";
			if ($database == 'h202') sendMail("COMS System", "noreply@customhostingtools.com", 'eccl411.12@gmail.com', "New Customer Form Submitted", $htmlOut, "Jim Frederick");
			logAction("$customer_name_formal added to COMS from New Customer Form");

			echo "Success: Tank '$site_info_sitename_1' has been added to COMS.<br /><br />Please edit tank in COMS to complete tank details";
	}
	else // save progress
	{	
		$allvals = json_encode($_POST);
		$allvals = fixSingleQuotes($allvals);

		// check for existing key
		$query = "SELECT keyCode FROM newCustomerForm WHERE keyCode = '$KEY_CODE'";
		$res = getResult($query);

		if (checkResult($res))
		{
			$query = "UPDATE newCustomerForm SET section$CURRENT_PAGE='$allvals', userID='$updated_by' WHERE keyCode = '$KEY_CODE' LIMIT 1";
		}
		else
		{
			$query = "INSERT INTO newCustomerForm (keyCode, userID, section$CURRENT_PAGE, creationDate) VALUES ('$KEY_CODE', '$updated_by', '$allvals', NOW())";
		}
	
		error_log($query);
		executeQuery($query);
		//logAction("New Customer Form Progress Saved (keyCode: $KEY_CODE)");

		echo $allvals;
	}
}
?>