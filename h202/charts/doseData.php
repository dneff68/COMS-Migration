<?
session_start();

?>
<chart yAxisMinValue='<?=$Y_MIN?>' yAxisMaxValue='<?=$Y_MAX?>' anchorRadius='2' exportEnabled='1' exportAtClient='1' exportHandler='doseExporter' exportFileName='<?="$DOSE_EXPORT_FILENAME"?>' showFCMenuItem='0' bgColor='E9E9E9' outCnvBaseFontColor='666666' caption='Dosing for <?=$GRAPH_TANK_NAME?>'  subcaption='<?=$GRAPH_START_DATE?>'   xAxisName='Day' yAxisName='Gallons' numberPrefix='' showValues='0' 
numVDivLines='10' showAlternateVGridColor='1' AlternateVGridColor='e1f5ff' divLineColor='e1f5ff' vdivLineColor='e1f5ff'  baseFontColor='666666'
toolTipBgColor='F3F3F3' toolTipBorderColor='666666' canvasBorderColor='666666' canvasBorderThickness='1' showPlotBorder='1' plotFillAlpha='80'>
<?=$GRAPH_CATEGORIES?>
<dataset seriesName="Normalized Dose" renderAs="Line" color="005500" lineThickness="3">
<?=$VARIANCE_DOSE?>
</dataset>

<dataset seriesName="Weighted Average" renderAs="Line" alpha="60" color="0000aa" lineThickness="2">
<?=$WEIGHTED_AVERAGE?>
</dataset>

<dataset seriesName="Target Dose (<?= $targetDosage > 0 ? $targetDosage:'Not Set'?>)" renderAs="Line" alpha="60" plotBorderColor="0372ab" plotBorderThickness="2">
<?=$DOSE_TARGET?>
</dataset>

<dataset seriesName="Target Range+ (<?=$DEV_PLUS?>)"  color="FFCC99" renderAs="Area" alpha="60" plotBorderColor="cccccc" plotBorderThickness="2">
<?=$DEV_PLUS_SERIES?>
</dataset>

<dataset seriesName="Target Range- (<?=$DEV_MINUS?>)" color="FFFFFF" renderAs="Area" alpha="100" plotBorderColor="999999" plotBorderThickness="2">
<?=$DEV_MINUS_SERIES?>
</dataset>


<styles>
	<definition>
		<style type='animation' name='TrendAnim' param='_alpha' duration='1' start='0' />
	</definition>
	<application>
		<apply toObject='TRENDLINES' styles='TrendAnim' />
	</application>
</styles>
</chart>