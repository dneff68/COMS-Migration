<?
session_start();
include_once '/var/www/html/CHT/h202/GlobalConfig.php';
include_once '/var/www/html/CHT/h202/h202Functions.php';
include_once 'chtFunctions.php';
include_once 'db_mysql.php';

if (david())
{
	error_reporting(E_PARSE | E_ERROR); 
	ini_set("display_errors", 1); 		
}


?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<?
if ( empty($workKey) )
{
	echo "<h3>No Work Order ID Provided</h3>";
}
else
{
	$query = "SELECT html FROM serviceHistory WHERE workKey=$workKey LIMIT 1";
	$res = getResult($query);
	if (checkResult($res))
	{
		$line = $res->fetch_assoc();
		extract($line);
		$html = getHTMLPart('<HTML>', '</HTML>', $html);

// parse out the html
	$html = str_replace('<style>', "\n<style>/*", $html);
	$html = str_replace('</style>', "*/</style>\n", $html);
	$html = str_replace('v42', "v50", $html);
	
	$html = str_replace("ShowHideLoading('1')", 'var x=0', $html); // kill the call that hides content
		echo($html);
	}
	else
	{
		echo "<h3>Invalid Work Key</h3>";
	}
}

?>
</html>