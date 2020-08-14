<?
session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once '/var/www/html/CHT/h202/h202Functions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';

error_log("action: $action");
if ($REQUEST_METHOD == 'POST')
{
	if ($action == 'setPage')
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
		$query = "SELECT email FROM internalEmailDist WHERE CONCAT(FirstName, ' ', LastName) = '$Name' LIMIT 1";
		$res = getResult($query);
		if (checkResult($res))
		{
			$line = $res->fetch_assoc();
			extract($line);
			echo $email ;
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
	}
	elseif ($action == 'mapToCOMS')
	{
			$res = getResult("SELECT monitorID FROM tank WHERE monitorID = '$KEY_CODE' LIMIT 1");			
			if (checkResult($res))
			{
				//executeQuery("DELETE FROM tank WHERE monitorID='$KEY_CODE' LIMIT 1");
				echo 'This tank already added.';
				return;
			}
			$res = getResult("SELECT monitorID FROM monitor WHERE monitorID = '$KEY_CODE' LIMIT 1");			
			if (checkResult($res))
			{
				//executeQuery("DELETE FROM monitor WHERE monitorID='$KEY_CODE' LIMIT 1");
				echo 'This monitor already added.';
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
				extract($values);
				$values = json_decode($section3, true);
				extract($values);
			}
			$monitorID = $KEY_CODE;
		

			$res = getResult("SELECT siteLocationName FROM site WHERE siteLocationName = '$customer_name_formal' LIMIT 1");			
			if (checkResult($res))
			{
				echo "The site '$customer_name_formal' already exists.  Update the customer name in Section 1 to continue.";
				return;
			}

			$siteQuery = "INSERT INTO site (siteLocationName, address, city, state, zip, contact, contactPhone, contactEmail) VALUES 
					('$customer_name_formal', '$site_address', '$site_city', '$site_state', '$site_zipcode', '$cust_contact_primary', '$cust_contact_primary_phone', '$cust_contact_primary_email')";
			error_log($siteQuery);

			$siteID = executeQuery($siteQuery, 'INSERT');


			// add new monitor
			$monitorQuery = "INSERT INTO monitor (monitorID, siteID, startDate, status) values
			('$monitorID', $siteID, NOW(), 'Inactive')";
			error_log($monitorQuery);
			executeQuery($monitorQuery);

			$supplierID = '';
			$query = "SELECT supplierID FROM supplier WHERE supplierName = '$sel_site_info_supplier_1' LIMIT 1";
			error_log($query);
			
			$res = getResult($query);
			if (checkResult($res))
			{
				$line = $res->fetch_assoc();
				extract($line);
			}

			$tank_details_tank_total_capacity = empty($tank_details_tank_total_capacity) ? 0 : $tank_details_tank_total_capacity;
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
							capacity
							)
						VALUES
							(
								'$KEY_CODE', $supplierID, '$KEY_CODE', 0, '$tank_name', $product_grade, '$directions_to_site', 
								$tank_details_height, $tank_inner_diameter, $sel_tank_details_tank_count, 
								'$sel_tank_details_orientation', 
								$tank_details_tank_total_capacity)";
			error_log($tankQuery);
			executeQuery($tankQuery);

			executeQuery("UPDATE newCustomerForm SET complete = 1 WHERE keyCode='$KEY_CODE'");

			echo "Succes: Tank '$tank_name' has been added to COMS.<br /><br />Please edit tank in COMS to complete tank details";
	}
	else
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
		echo $allvals;
	}
}
?>