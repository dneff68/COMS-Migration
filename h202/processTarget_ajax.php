<?
session_start();
// $PROCESS_TARGET_ARRAY = '';

if (empty($PROCESS_TARGET_ARRAY))
{
	session_register('PROCESS_TARGET_ARRAY');
	$PROCESS_TARGET_ARRAY = array();
}

if (isset($removeItem))
{
	unset(	$PROCESS_TARGET_ARRAY[$removeItem] );
	reset($PROCESS_TARGET_ARRAY);
}

if ($action == 'clear')
{
	session_register('PROCESS_TARGET_ARRAY');
	$PROCESS_TARGET_ARRAY = array();
	echo '-- No Targets Set --';
	return;
}

if (isset($getTarget))
{
	list($target, $lag) = explode(':',  $PROCESS_TARGET_ARRAY[$getTarget]);
	echo $target;
	return;
}


if (isset($_GET['hr']))
{
	//echo "$hr:$minute";
	//return;
	$PROCESS_TARGET_ARRAY["$hr"] = "$target"; 
}
	
$output = '';
$cnt = count($PROCESS_TARGET_ARRAY);
ksort($PROCESS_TARGET_ARRAY);

$i = 0;
$endval = '23:45';
while (list($key, $value) = each($PROCESS_TARGET_ARRAY)) 
{
	$target = $value;
	if ($i+1 != $cnt) // at the end of the array
	{
		list($nextKey, $nextVal) = each($PROCESS_TARGET_ARRAY);		
		$nextTarget = $nextVal;
		$endval = $nextKey;
		prev($PROCESS_TARGET_ARRAY);
	}
			
		$output .= "<tr id='$key' onclick=\"document.getElementById('hr').value='$key'; document.getElementById('target').value='$target';this.setAttribute('class', 'spinTableBarEven')\">
  <td>$key - $endval</td>
  <td>$target</td>
  <td><a href='javascript:clearSingleTarget(\"$key\")'>clear</a></td>
</tr>\n";
	$i++;
}

if ($cnt > 1)
{
	// get the last array element
	krsort($PROCESS_TARGET_ARRAY);
	list($key, $value) = each($PROCESS_TARGET_ARRAY);
	list($target, $holder) = explode(':', $value);

		$output .= "<tr id='$key' onclick=\"document.getElementById('hr').value='$key'; document.getElementById('target').value='$target';this.setAttribute('class', 'spinTableBarEven')\">
	  <td>$key - 23:45</td>
	  <td>$target</td>
	  <td><a href='javascript:clearSingleTarget(\"$key\")'>clear</a></td>
	</tr>\n";
}

// set the output with the full table
$output = "<table width='500' border='1' cellspacing='0' cellpadding='6'>
<tr>
  <td>Time</td>
  <td>Target</td>
  <td>&nbsp;</td>
</tr>
$output
</table>";

echo $output;


?>