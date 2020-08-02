<?
session_start();
if (empty($USERID))
{
	echo "
	<script language='javascript'>
	window.close();
	</script>";
}
if ($bannerBuffer == 1)
{
	echo('<br>');
}
?>
<div id="bannerDiv" class="spinBoxedNormal" style="border-bottom:groove; font-size:20px">
Tank: <strong><?=$SELECTED_TANK_NAME?></strong><br />
</div>
