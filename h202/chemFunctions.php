<?

$mailfile = fopen('/var/spool/mail/readings', 'r');
if ($mailfile)
{
	echo("file opened");
	fclose($mailfile);
}
else
{
	echo("failue");
}
?>