<?
session_start();
include_once 'chtFunctions.php';

if (empty($SELECTED_TANK))
{
	echo "<chart><chart_data><row><string>No Data</string></row></chart_data></chart>";
}
	

	
?>
<chart>
<license>JTAJ-9N1PLHO.945CWK-2XOI1X0-7L</license>
<chart_value prefix='' 
          suffix='' 
          decimals='0' 
          decimal_char='.'
          separator=''
          position='top_above'
          hide_zero='false' 
          as_percentage='false'
          font='arial' 
          bold='true' 
          size='10' 
          color='FFFFFF' 
          alpha='90'
          />


  <series_color>
    <color>009933</color>
  </series_color>
  <chart_data>
<?=$VARIANCE_TITLE?>
<?=$VARIANCE_DATA?>
  </chart_data>
  <chart_type>
      <string>column</string>
   </chart_type>
	<chart_label shadow='low' color='ffffff' alpha='90' size='10' position='top' prefix='' suffix='' decimals='0' separator='' as_percentage='false' />
   
</chart>
