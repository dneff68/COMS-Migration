<?
session_start();
?>
<chart
	PYAxisName="Temperature (&#176;F) / Flow Rate (GPH)" 
	SYAxisName="H2S (PPM)" 
    PYAxisMaxValue='<?=$LEFT_Y_MAX?>'
    PYAxisMinValue='<?=$LEFT_Y_MIN?>'
	anchorRadius='2' 
	exportEnabled='1' 
	exportAtClient='1' 
	exportHandler='processExporter' 
    exportFileName='<?=$SELECTED_TANK_NAME?>' 
    showFCMenuItem='0' 
    bgColor='E9E9E9' 
    outCnvBaseFontColor='666666' 
    caption='Process for <?=$SELECTED_TANK_NAME?>'  
    subcaption='<?=$GRAPH_START_DATE?>'  
    xAxisName='Process over Time' 
    numberPrefix='' 
    showValues='0' 
    numVDivLines='10' 
    showAlternateVGridColor='1' 
    AlternateVGridColor='e1f5ff'
    divLineColor='e1f5ff' 
    vdivLineColor='e1f5ff'  
    baseFontColor='666666'
    toolTipBgColor='F3F3F3' 
    toolTipBorderColor='666666' 
    canvasBorderColor='666666' 
    canvasBorderThickness='1' 
    showPlotBorder='1'
    plotFillAlpha='80'
    plotGradientColor='ffffff'
    useRoundEdges='1'
    labelStep='<?=$LABEL_STEP?>'>
 
<categories>
<?=$PROCESS_CATEGORIES?>
</categories>

<? 
if (!empty($TEMPERATURE_DATASET))
{
	echo "
		\n<dataset seriesName='Temperature' renderAs='Line' color='005500' lineThickness='3' parentYAxis='P'>
		$TEMPERATURE_DATASET
		\n</dataset>";
}
?>

<? if (!empty($FLOW_DATASET)): ?>
<dataset seriesName='Flow Rate'  color='0000AA' renderAs='Line' alpha='80' plotBorderColor='cccccc' plotBorderThickness='2' parentYAxis='P'>
<?=$FLOW_DATASET?>
</dataset>
<? endif ;?>

<? if (!empty($FLOW_TARGET)): ?>
<dataset seriesName='Flow Rate Target' renderAs='Line' alpha='60' color='ff0000' plotBorderThickness='2' parentYAxis='P'>
<?=$FLOW_TARGET?>
</dataset>
<? endif ;?>

<? if (!empty($FLOW_AVERAGE)): ?>
<dataset seriesName='Flow Average' renderAs='Line' alpha='60' color='008800' plotBorderThickness='2' parentYAxis='S'>
<?=$FLOW_AVERAGE?>
</dataset>
<? endif ;?>

<? if (!empty($PPM_DATASET)): ?>
<dataset seriesName='PPM' renderAs='Area' alpha='60' color='FF9933' plotBorderThickness='2' parentYAxis='S'>
<?=$PPM_DATASET?>
</dataset>
<? endif ;?>


<? if (!empty($PROCESS_TARGET) && $PROCESS_TARGET > 0): ?>
<trendLines>
      <line startValue='<?=$PROCESS_TARGET?>' color='009933' displayvalue='Process Target' valueOnRight ='1'  parentYAxis='S' toolText='Target: <?=$PROCESS_TARGET?>'/>
   </trendLines>
<? endif; ?>

<styles>
	<definition>
		<style type='animation' name='TrendAnim' param='_alpha' duration='1' start='0' />
	</definition>
	<application>
		<apply toObject='TRENDLINES' styles='TrendAnim' />
	</application>
</styles>
</chart>