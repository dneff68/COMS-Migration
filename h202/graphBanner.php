<?
session_start();
if (empty($USERID))
{
	echo "
	<script language='javascript'>
	window.close();
	</script>";
}
?>
<div id="bannerDiv" class="spinBoxedNormal" style="border-bottom:groove; font-size:20px">
Tank: <strong><?=$tankName?></strong> (capacity: <?=$capacity?> gallons): <?="$value $concentration" ?><br />
</div>
