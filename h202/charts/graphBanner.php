<?php
session_start();
if (empty($_SESSION["USERID"]))
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
Tank: <strong><?=$tankName?></strong> (Usable Volume: <?php echo $usableVolume?> gallons): <?php echo "$value $concentration" ?><br />
</div>
