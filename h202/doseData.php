<?
session_start();

if (empty($SELECTED_TANK))
{
	echo "<chart><chart_data><row><string>No Data</string></row></chart_data></chart>";
}
	

	
?>
<chart>
<chart_value prefix='' 
          suffix='' 
          decimals='0' 
          decimal_char='.'
          separator=''
          position='cursor'
          hide_zero='false' 
          as_percentage='false'
          font='arial' 
          bold='true' 
          size='10' 
          color='000000' 
          alpha='90'
          />


  <series_color>
    <color>009933</color>
  </series_color>
  <chart_data>
	<?=$VARIANCE_TITLE?>
	<?=$VARIANCE_DOSE?>
	<?=$VARIANCE_TARGET_DOSE?>
  </chart_data>
  <chart_type>
    <string>line</string>
    <string>area</string>
  </chart_type>
</chart>
