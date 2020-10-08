var selectedCatID='';
var editItem;


function toggleHidden(id)
{
//  ($action == 'toggleHidden'
		$.get("customerSummaryAjax.php?action=toggleHidden&itemID=" + id, function(data){
			if (data == 1)
				$('#vis_' + id).html('Visible');
			else
				$('#vis_' + id).html('Hidden');
		});
	 
}

function initializeEdit(id)
{
	if (typeof id != 'undefined')
	{
		
		$.get("customerSummaryAjax.php?action=getItem&itemID=" + id, function(data){
			var dataArray = data.split('~');
			var title 		= dataArray[0];  
			var pctComplete = dataArray[1];  
			var responsible	= dataArray[2];  
			var timing 		= dataArray[3];  
			var impact 		= dataArray[4];
			
			$('#title').val(title);
			$('#percent_complete').val(pctComplete);
			$('#responsible-party-list').html('<strong>selected:</strong><br />' + responsible);
			$('#timing').val(timing);
			$('#impact').val(impact);
			
		});
	
		$.get("customerSummaryAjax.php?action=getItemStatus&itemID=" + id, function(data){
			$('#statusHistory').html(data);
		});
	}
	else
	{
			$('#title').val('');
			$('#percent_complete').val('');
			$('#responsible-party-list').html('');
			$('#timing').val('');
			$('#impact').val('');
	}
}

function editItem(id)
{
	if (typeof id == 'number')
	{
		$('#itemID').html(id);
		initializeEdit(id);
		$('#addEditBanner').html('Edit Planning Item');
	}
	else
	{
		$('#itemID').html('');
		initializeEdit();
		$('#addEditBanner').html('Project Planning - Add Item');
	}
	
	$('#project-list').hide('slow');
	$('#editProjectDiv').show('slow');
	$('.planningTitleRow').show();
	$('.planningItemRow').show();
	
	
}

function saveItem()
{
	var itemID		= $('#itemID').html();
	var title 		= $('#title').val();
	var category 	= $('#categories').val();
	var pctComplete	= $('#percent_complete').val();
	var respParties	= $('#responsible-party-list').html();
	var timing	= $('#timing').val();
	var status	= $('#new-status').val();
	var impact	= $('#impact').val();
	var customerLogin = $('#customerLogin').html();
	
	if ( itemID == '')
	{
		var action = 'addItem';
	}
	else
	{
		var action = 'editItem';
	}
	
			$.post("customerSummaryAjax.php", { customerID: '<?=$customerID?>', action: action, itemID: itemID, customerLogin: customerLogin, title: title, category:category, pctComplete: pctComplete, respParties: respParties, timing: timing, status: status, impact: impact }, function(saveData){
			if (saveData == 'session-timeout') 
			{
				alert("Error: Session Timed Out");
				window.close();
			}
			if (saveData == 'success')
			{
				window.location = "planning.php?customerID=<?=$customerID?>&catID=" + selectedCatID;// .reload();
				return;
			}
			alert(saveData); // if not successful simply alert the result
		});

	$('#itemID').text('');
}

function cancelItem()
{
	$('#itemID').text('');
	$('#editProjectDiv').hide('fast');
	$('#project-list').show('fast');
	$('.planningTitleRow').hide();
	$('.planningItemRow').hide();
	expand(selectedCatID);

}


function updateRespDiv()
{
	values = $("#sel_respList").val() || [];
	
	$('#responsible-party-list').html('<strong>selected:</strong><br>' + values.join("<br>") ).css('author-date');
}

$().ready(function() 
	{  
	 $('#sel_respList').change(updateRespDiv);	
	 $('#editProjectDiv').hide();
	 $('.planningTitleRow').hide();
	 $('.planningItemRow').hide();
	 $('.planningTitleRow_complete').hide();
	 $('.planningItemRow_complete').hide();
	 
//<? 
//	if ( !empty($catID) )
//	{
//		echo "\nexpand($catID);"; 
//	}
//?> 
	}); 

function expand(id)
{
	selectedCatID = id;
	$("#categories option[value=" + id + "]").attr("selected", true);
    $('.planningTitleRow').hide();
	$('.planningItemRow').hide();
	$('.row_' + id).show(600);
}

function expand_lower(id)
{
	selectedCatID_lower = id;
    $('.planningTitleRow_complete').hide();
	$('.planningItemRow_complete').hide();
	$('.row_' + id + '_complete').show(600);
}


function isNumberKey(evt)
{
	var charCode = (evt.which) ? evt.which : event.keyCode
	if (charCode > 31 && (charCode < 48 || charCode > 57)) return false;
	
	return true;
}

function deleteItem(itemID)
{
	if (confirm("Are you sure you wish to delete this issue?"))
	{
		$.get("customerSummaryAjax.php?action=remove&itemID=" + itemID, function() {
			window.location = "planning.php?customerID=<?=$customerID?>&catID=" + selectedCatID;// .reload();
		});
//		$('#rowID_' + itemID).hide();
	}
}
