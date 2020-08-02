<?
session_start();
if (empty($USERID))
{
	echo "
	<script language='javascript'>
	window.parent.location = '/index.php';
	</script>";
}
?>
<div id="bannerDiv" class="spinTableTitle" style="border-bottom:groove">
<p align="center" class="banner3" style="margin-top:5">Tank Details</p>
<table width="600" border="0" align="center" cellpadding="6" cellspacing="3">
  <tr align="center">
    <td width="33%" valign="middle"><a href='javascript:window.location="tankDetails.php?tab=1"'><div align="center" class="tab2<?=$tab==1 || empty($tab)? '_selected' : ''?>">Summary</div></a></td>
    <td width="33%" valign="middle"><a href='javascript:window.location="/charts/tankGraph.php?tab=2"'><div align="center" class="tab2<?=$tab==2? '_selected' : ''?>">Graph</div></a></td>
    <td width="33%">
	  <div align="center" class="tab2">Readings</div></td>
    </tr>
</table>
</div>