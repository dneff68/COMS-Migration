<div id="bannerDiv" class="spinTableTitle" style="border-bottom:groove">
<?php if (!empty($USERID)) : ?>
<div align="right"><a style="color:#FFFFFF" href='/index.php?logout=yes'>logout</a>&nbsp;</div>
<?php endif;?>
<p align="center" class="banner1"><span class="banner2">Chemical Ordering Management Sys </span></p>
<?php if (!empty($USERID) && ($_SESSION['USERTYPE'] != 'customer')) : ?>
<table width="600" border="0" align="center" cellpadding="6" cellspacing="3">
  <tr>
    <td width="200" valign="middle"><div align="center"><a href='/index.php'><img border="0" onMouseOver="this.src='images/reportsBtn_over.gif'" onMouseOut="this.src='images/reportsBtn.gif'" 
	src="images/reportsBtn.gif" alt="Reports" width="145" height="22" longdesc="View Reports"></a></div></td>
    <td width="200" valign="middle">
	  <div align="center"><a href='/index.php?search=1'><img border="0" onMouseOver="this.src='images/searchBtn_over.gif'" onMouseOut="this.src='images/searchBtn.gif'" 
	src="images/searchBtn.gif" alt="Customers" longdesc="View Customers"></a></div></td>
    <td width="200">
	  <div align="center"><a href='/index.php?delivery=1'><img border="0" onMouseOver="this.src='images/deliveriesBtn_over.gif'" onMouseOut="this.src='images/deliveriesBtn.gif'" 
	src="images/deliveriesBtn.gif" alt="Routes" longdesc="View Routes"></a></div>	
	</td>
	<!--
    <td width="27">	  <div align="center"><img border="0" onMouseOver="this.src='images/usersBtn_over.jpg'" onMouseOut="this.src='images/usersBtn.jpg'" 
	src="images/usersBtn.jpg" alt="Users" longdesc="View Users">        </div></td>
	-->
  </tr>
</table>
<?php endif; ?>
</div>