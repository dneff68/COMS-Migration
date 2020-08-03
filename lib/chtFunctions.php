<?php
// CHT Functions

function david()
{
	// return true if David Neff is the client
	//return false;
	global $REMOTE_ADDR, $debug;
	$debug = false;
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);

	$s = $REMOTE_ADDR == "68.5.60.170";
	$s = $s || "::1";
	return $s;
}

function jim()
{
	global $_SESSION;
	return $_SESSION['USERID'] == 'Jim';
}


function fixSingleQuotes($str)
{
	$str = str_replace("'", "''", $str);
	return $str;
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
	if (!david() && !jim())
	{
		return;
	}


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
	if (jim() || david())
	{
		echo("<h4>$txt</h4>");
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
  	if ($result->num_rows <= 0)
  	{
  	  return;
  	}
	if ($title == 'defalut')
	{
  		echo "Total Rows: ".$result->num_rows;
	}
	else
	{
		echo "<span class='spinMedTitle'>$title</span>";	
	}
	$result->data_seek(0);
  	$fieldCnt = mysqli_num_fields($result);
  	//bigEcho($fieldCnt);
	$width = empty($width) ? '' : "width='$width'";
	
  	echo "<table border='1' cellspacing='0' cellpadding='6' bordercolor='#cccccc' $width>\n<tr>\n";

	$finfo = $result->fetch_fields();   
    foreach ($finfo as $val) 
    {
      $fn = $val->name;
	  echo "<td align='center' class='spinTableTitle'>$fn</th>";
    }

	// for ($i = 0; $i < $fieldCnt; $i++)
	// {
	// 	$finfo = $result->fetch_fields();

 //      $fn = mysqli_field_name($result, $i);
	//   echo "<td align='center' class='spinTableTitle'>$fn</th>";
	// }
	echo "</tr>";
	





  	while ($line = $result->fetch_array(MYSQLI_NUM))
  	{
			echo "<tr class='spinTableBarOdd'>";
			for ($i = 0; $i < $fieldCnt; $i++)
			{
				echo "<td align='left'>$line[$i]</td>";
			}
	  		echo "</tr>";
  	}
  	echo "</table>";
  	$result->data_seek(0);
  }
  
function showPostVars($tofile='no')
{
	if (!david())
	{
		return;
	}
	
	global $_POST;
	$a = $_POST;

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
		$headers .= "X-MSMail-Priority: Normal\n";
		$headers .= "X-Mailer: iCEx Networks HTML-Mailer v1.0";
		$res = mail($to_address, $subject, $message, $headers, "-fjames.frederick@h2o2.com");
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
	global $_SESSION;
	
	if (!david())
	{
		return;
	}
	
	$a = $_SESSION;
	
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