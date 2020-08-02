<?
// CHT Functions

function david()
{
	// return true if David Neff is the client
	// return false;
 	//return ed();
	global $REMOTE_ADDR;
	$s = $REMOTE_ADDR == "184.179.73.19";
	$s = $s || $REMOTE_ADDR == '76.102.61.207';
	return $s;
}


function fixString($str)
{
	$str = nl2br($str);
	$str = str_replace(chr(13), "", $str);
	$str = str_replace(chr(10), "", $str);
	return $str;
}

function showArray($arr)
{
//	if (!david())
//	{
//		return;
//	}


	ksort($arr);
	reset($arr);
	
	foreach ($arr as $var=>$val)
	{
		echo "<br>$var = $val";
	}
}

function first($array) {
	if (!is_array($array)) return $array;
	if (!count($array)) return null;
	reset($array);
	return $array[key($array)];
}

function bigEcho($txt)
{
	if (david())
	{
		echo("<h3>$txt</h3>");
	}
}

  /* 
  --------------------------------------------------
  echoResults
  
  Use this function for debugging.  It simply outputs
  the resultset to the browser.
  --------------------------------------------------
  */
  function echoResults(&$result, $title='defalut', $width='')
  {
  	if (mysql_num_rows($result) <= 0)
  	{
  	  return;
  	}
	if ($title == 'defalut')
	{
  		echo "Total Rows: ".mysql_num_rows($result);
	}
	else
	{
		echo "<span class='spinMedTitle'>$title</span>";	
	}
  	mysql_data_seek($result, 0);
  	$fieldCnt = mysql_num_fields($result);
  	
	$width = empty($width) ? '' : "width='$width'";
	
  	echo "<table border='1' cellspacing='0' cellpadding='3' bordercolor='#cccccc' $width>\n<tr>\n";
	for ($i = 0; $i < $fieldCnt; $i++)
	{
      $fn = mysql_field_name($result, $i);
	  echo "<td align='center' class='spinTableTitle'>$fn</th>";
	}
	echo "</tr>";
	
  	while ($line = mysql_fetch_array($result))
  	{
			echo "<tr class='spinTableBarOdd'>";
			for ($i = 0; $i < $fieldCnt; $i++)
			{
				echo "<td align='left'>$line[$i]</td>";
			}
	  		echo "</tr>";
  	}
  	echo "</table>";
  	mysql_data_seek($result, 0);
  }
  
 function showPostVars($tofile='no')
{
	global $HTTP_POST_VARS;
	$a = $HTTP_POST_VARS;

	ksort($a);
	reset($a);
	
	
	foreach ($a as $var=>$val)
	{
		echo "<br>$var = $val";
	}
}
 
function sendMail($fromName, $fromEmail, $toEmail, $subject, $msg, $toName="", $content_type="html")
{
	if (empty($fromName))
	{
		$fromName = "CHT Services";
	}


	if (!empty($fromName) && !empty($fromEmail) && !empty($toEmail) &&!empty($subject))
	{
		$from_name = $fromName;
		$from_address = $fromEmail;
		
		$to_name = empty($toName) ? $toEmail : $toName;
		$to_address = "$toName <$toEmail>";
		
		$message = $msg;

		$headers .= "MIME-Version: 1.0\n";
		$headers .= "Content-type: text/" . $content_type . "; charset=iso-8859-1\n";
		$headers .= "From: ".$from_name." <".$from_address.">\n";
		$headers .= "Reply-To: ".$from_name." <".$from_address.">\n";
		$headers .= "X-Priority: 3\n";
		$headers .= "X-MSMail-Priority: Low\n";
		$headers .= "X-Mailer: iCEx Networks HTML-Mailer v1.0";
		$res = mail($to_address, $subject, $message, $headers, "-fjfrederick@h2o2.com");
		return $res;
	}
	return false;
} 

function getPopupFrame()
{
	return "\n
		<div id='menuDiv' name='menuDiv' style='visibility:hidden;position:absolute;left:0;top:0'>
		<iframe id='menuFrame' name='menuFrame' style='background-color:#FFFFFF' 
		scrolling=auto width=0 height=0 align=top frameborder=0 
		src=''  align='left' allowtransparency='true' marginheight='0' marginwidth='0' ></iframe>
		</div>\n
			<iframe id='ActionFrame'
			 name='ActionFrame'
			 style='width:0px; height:0px; border:0px'
			 src=''></iframe>";
}

function generateCode($length=8)
{
	// generate a unique code
	//srand((double)microtime()*1000000);
	//$r = md5(rand(0,9999999));
	//return $r;
	

  // start with a blank password
  $password = "";

  // define possible characters
  $possible = "0123456789bcdfghjkmnpqrstvwxyz"; 
    
  // set up a counter
  $i = 0; 
    
  // add random characters to $password until $length is reached
  while ($i < $length) { 

    // pick a random character from the possible ones
    $char = substr($possible, mt_rand(0, strlen($possible)-1), 1);
        
    // we don't want this character if it's already in the password
    if (!strstr($password, $char)) { 
      $password .= $char;
      $i++;
    }

  }

  // done!
  return $password;
	
}

function showSessionVars()
{
	global $HTTP_SESSION_VARS;
	
	if (!david())
	{
		return;
	}
	
	$a = $HTTP_SESSION_VARS;
	
	ksort($a);
	reset($a);
	
	foreach ($a as $var=>$val)
	{
		echo "<br>$var = $val";
	}
}


function getmicrotime()
{ 
    list($usec, $sec) = explode(" ",microtime()); 
    return ((float)$usec + (float)$sec); 
} 

function timestamp($msg, $reset=false)
{
	return;
	if (!david()) return;
	
	global $last_stamp, $time_start, $uploadLocation;
	$time_end = getmicrotime();
	$f = fopen("$uploadLocation/timeline.html", 'a+');
	if ($reset)
	{
		$time_start = getmicrotime();
		fputs($f, "<hr><b>Start Timer</b><br>");
		$diff = 0.0;
	}	
	else
	{
		$diff = $time_end - $last_stamp;
	}
	$diff = number_format($diff, 4, '.', '');

	$time = $time_end - $time_start;
	$time = number_format($time, 4, '.', '');
	$string = "Elapsed: $time -- $msg ($diff)<br>";

	fputs($f, $string.chr(13).chr(10));
	$last_stamp = $time_end;
	fclose($f);
}  


?>