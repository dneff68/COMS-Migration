alert('dev');
function validateForm(processSectionNumber, selectKey)
{
	var valid = true;

	$("#section_" + processSectionNumber + " .selectObj").each(function() {
			//first().css("background-color", "red");
			if ( $(this).val() == selectKey )
			{
				// $(this).parent().css("background-color", "red");
				$(this).removeClass("required");
				$(this).addClass("requiredHighlighted");
				valid = false;
				
			}
			else
			{
				// $(this).parent().css("background-color", '');					
				$(this).removeClass("requiredHighlighted");
			}
		}); 
	
	
	$("#section_" + processSectionNumber + " .required").each(function() {
				if ( $(this).val() == '' )
				{
					
					$(this).addClass("requiredHighlighted");
					valid = false;
				}
				else
				{
					$(this).removeClass("requiredHighlighted");
				}
	});
	return valid;

}

function getSection( sectionNumber )
{
	var fieldVal;
	$.post("newCustomerAjax.php",  { "action": "getSection", "section": sectionNumber }, function(resultStr){
		
		if ( resultStr.substr(0, 4) == 'FAIL' )
		{
			return;
		}
		
		
		resultStr = resultStr.replace(/(\r\n|\n|\r)/gm,"<br>");
		resultStr = resultStr.replace(/\\\\"/g,'\"');
//		resultStr = resultStr.replace(/\\"/g,'"');
		var jsonObj = $.parseJSON(resultStr);
		$.each(jsonObj, function(key) {
			if (key.indexOf('chk_') >= 0 || key.indexOf('rdo_') >= 0 )
			{
//				alert("input[name=" + key + ']');
				$("input[name=" + key + ']').attr('checked', true)
			}
			else if (key.indexOf('sel_') >= 0)
			{
				$("select#" + key + " option[selected]").removeAttr("selected");
				$("select#" + key + " option[value='" + this.toString() + "']").attr("selected", "selected");
			}
			else
			{
				if ($("#" + key).val() == '') // don't populate fields that have a value
				{
					fieldVal = this.toString();
					fieldVal = fieldVal.replace("<br>", '\n');
					fieldVal = fieldVal.replace('&quot;', '"');
					fieldVal = fieldVal.replace(/"/g,'\"');
					fieldVal = fieldVal.replace(/\\\\"/g,'\"');
					$("#" + key).val( fieldVal );
					
					// set any defaults here
					if (key == 'training_type_1' && fieldVal == '')
					{
						// alert('here: ' + fieldVal);
						$("#" + key).val( 'none' );
					}
					
				}
			}
		});
	});
}


function processSection( processSectionNumber, action )
{
		var valid = true;
		var validate = true;
		if (action != 'back' && validate)
		{
			valid = validateForm(processSectionNumber, '-select-');
			if (!valid)
			{
				window.scroll(0,0);
				$("#dialog").html("Please correct the highlighted areas.  All required fields must have a value.");
				$("#dialog").dialog('open');
				
				return;	
			}

		}
		
		
		var customerItems = $("#section_" + processSectionNumber + " :input").not("input:checkbox,input:radio");
		
		// we need put backslashes before all double quotes or it'll break JSON
		$(customerItems).each( function() {
			var thisVal = $(this).val(); 
			if (typeof thisVal == 'string')
			{
				if ( thisVal.indexOf('"') >= 0 )
				{
					thisVal = thisVal.replace(/"/g, '\\"');
					thisVal = thisVal.replace(/\\\\/g, '\\');
					$(this).val(thisVal);
				}
			}
		});
		
//		if (processSectionNumber == 1)
//		{
////			// update fields from page one
////			var address = $('#address').val();
////			address += '\n' + $('#city').val() + ', ' + $('#state').val() + ' ' + $('#zipcode').val();
////			$("#site_address").val(address);
////			$('#manifest_cust_contact_name1').val($('#customer_name_formal').val());
//		}
		
		var radioButtons = $("#section_" + processSectionNumber + " :input:checked");

		$.merge(customerItems, radioButtons);
		var serializedString = customerItems.serializeArray();
		//$("#debugDiv").html(serializedString);

		$.post("newCustomerAjax.php", { "action": "setPage", "currentPage": processSectionNumber }, function () {
				$.post("newCustomerAjax.php", serializedString, function(resultStr){
					if (action == 'forward')
					{
						var next = processSectionNumber + 1;
						$("#section_" + processSectionNumber).hide();
						$("#section_" + next).show(1000);
						$("#currentSection").html(next);
					}
					else if (action == 'back')
					{
						var next = processSectionNumber - 1;
						$("#section_" + processSectionNumber).hide();
						$("#section_" + next).show(1000);
						$("#currentSection").html(next);
					}
					else if (action == 'commit')
					{
//						if (confirm("Post this New Customer Form to COMS now?") )
//						{
							$("#section_1").hide();
							$("#section_2").hide();
							$("#section_3").hide();
							$("#thank_you").show();
							$.post("newCustomerAjax.php", { action: "commit" });
//						}
					}	
					window.scroll(0,0);
					// $('*').filter("select").attr('disabled', 'disabled');

				})});


	
	if (false)
	{		
			var debug = '';
			values = {};
			customerItems.each(function (i) {
				debug += this.name + ':' + $(this).val() + '<br >';
				values[this.name] = $(this).val();
				// $(this).val()
				  });	
			$("#debugDiv").html(debug);
	}
	//return true;
}

function setEmail(fieldName)
{
	// get select value
	var selectedText = $("#sel_" + fieldName + " option:selected").text();

	// get corresponding email
	$.post("newCustomerAjax.php", { "action": "getEmail", "Name": selectedText }, function (retVal) {
		var returnData = retVal.split('|');
		var email = returnData[0];
		var phone = returnData[1];		
		var cell = returnData[2];		
		var fax = returnData[3];		
		$("#" + fieldName + "_email").val(email);
		$("#" + fieldName + "_phone").val(phone);
		$("#" + fieldName + "_cell").val(cell);
		$("#" + fieldName + "_fax").val(fax);
	});
	
}

function mapToCOMS()
{
	processSection(3, '');
	
	$("#site_address, #site_city, #sel_site_state, #site_zipcode, #cust_contact_primary, #cust_contact_primary_phone, #cust_contact_primary_email, #sel_site_info_supplier_1, #tank_name, #sel_product_grade, #tank_details_height, #tank_inner_diameter, #tank_details_tank_total_capacity").addClass("required");
	valid = validateForm('1', '--select--');
	if (!valid)
	{
		$("#section_1").show();
		$("#section_2").hide();
		$("#section_3").hide();
		
		window.scroll(0,0);
		$("#dialog").html("Please correct the highlighted areas.  All required fields must have a value.");
		$("#dialog").dialog('open');
		
		return;	
	}
	valid = validateForm('2', '--select--');
	if (!valid)
	{
		$("#section_1").hide();
		$("#section_2").show();
		$("#section_3").hide();
		
		window.scroll(0,0);
		$("#dialog").html("Please correct the highlighted areas.  All required fields must have a value.");
		$("#dialog").dialog('open');
		
		return;	
	}
	valid = validateForm('3', '--select--');
	if (!valid)
	{
		$("#section_1").hide();
		$("#section_2").hide();
		$("#section_3").show();
		
		window.scroll(0,0);
		$("#dialog").html("Please correct the highlighted areas.  All required fields must have a value.");
		$("#dialog").dialog('open');
		
		return;	
	}
	$.post("newCustomerAjax.php", { "action": "mapToCOMS" }, function (result) {
		if (result.indexOf('Success') >= 0)
		{
			$('#thank_you').html(result);
			$("#section_1").hide();
			$("#section_2").hide();
			$("#section_3").hide();
			$("#thank_you").show();
		}
		else
		{
			alert(result);
		}
	});
}

function setDoseDefaults(value)
{
	$('#dose_site_name').val(value);
	$('#dose_site_name2').val(value);	
}

function copySelValue(source, target)
{
	var selectedState = $("select#" + source).val();
	$("select#" + target + " option[selected]").removeAttr("selected");
	$("select#" + target + " option[value='" + selectedState + "']").attr("selected", "selected");
}

function copyValue(sourceVal, target)
{
//	var sourceVal = $("#" + source).val();
//	alert("sourceVal: " + sourceVal + " --> " + target);
	$("#" + target).val(sourceVal);	
}