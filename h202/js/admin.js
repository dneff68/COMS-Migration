function reposition(w, h)
{
  // Centers the current window and resizes to the w, h passed
  var top = ""+(screen.height/2)-(h/2);
  var left = ""+(screen.width/2)-(w/2);
  window.resizeTo(w, h);
  window.moveTo(left, top);
}

function addSiteFrame(w, h, siteAction)
{
  	if (typeof(siteAction)=="undefined")
	{
		loc = "/addSite.php";
	}
	else
	{
		loc = "/addSite.php?action=" + siteAction;
	}
	var w = w;
	var h = h;
	showIFramePopup(loc, w, h, 'parent', 'menu');
}


function showIFramePopup(loc, w, h, level, framename)
{
  	if (typeof(framename)=="undefined")
	{
  		divName = 'menuDiv';
  		frameName = 'menuFrame';
	}
	else
	{
  		divName = framename + 'Div';
  		frameName = framename + 'Frame';
	}
//	 alert(frameName);
	var bw = browseWidth();
	var bh = browseHeight();
	
	var nOffsets = getScrollXY();
	var nScrollLeft = nOffsets[0];
	var nScrollTop = nOffsets[1];
	
	var left = new String( (bw / 2) - (w / 2) + nScrollLeft);	
	var top = 69 + nScrollTop;
		
	if (level == 'parent')
	{
		oMenu = window.parent.document.getElementById(divName);
	}
	else
	{
		oMenu = document.getElementById(divName);
	}

	oMenu.style.top = '1px'; //top + 'px';
	oMenu.style.left = left + 'px';
	obj = window.parent.document.getElementById(frameName);	
	//alert(loc);
	obj.width = w + 'px';
	obj.height = h + 'px';
	obj.src = loc;
	oMenu.style.visibility = 'visible';
}

function browseHeight()
{
	if (parent.window.innerWidth)
	{
		frameHeight = parent.window.innerHeight;
	}
	else if (parent.document.documentElement && parent.document.documentElement.clientWidth)
	{
		frameHeight = parent.document.documentElement.clientHeight;
	}
	else if (parent.document.body)
	{
		frameHeight = parent.document.body.clientHeight;
	}
	winH = frameHeight;
	return winH;
}	

function browseWidth()
{
	if (parent.window.innerWidth)
	{
		frameWidth = parent.window.innerWidth;
	}
	else if (parent.document.documentElement && parent.document.documentElement.clientWidth)
	{
		frameWidth = parent.document.documentElement.clientWidth;
	}
	else if (parent.document.body)
	{
		frameWidth = parent.document.body.clientWidth;
	}
	winW = frameWidth;
	return winW;
}

function getScrollXY() {
  var scrOfX = 0, scrOfY = 0;
  if( typeof( window.pageYOffset ) == 'number' ) {
    //Netscape compliant
    scrOfY = window.pageYOffset;
    scrOfX = window.pageXOffset;
  } else if( document.body && ( document.body.scrollLeft || document.body.scrollTop ) ) {
    //DOM compliant
    scrOfY = document.body.scrollTop;
    scrOfX = document.body.scrollLeft;
  } else if( document.documentElement &&
      ( document.documentElement.scrollLeft || document.documentElement.scrollTop ) ) {
    //IE6 standards compliant mode
    scrOfY = document.documentElement.scrollTop;
    scrOfX = document.documentElement.scrollLeft;
  }
  return [ scrOfX, scrOfY ];
}

function hideAdminMenu()
{
	oMenu = document.getElementById('menuDiv');
	oMenu.style.visibility = 'hidden';
	obj = window.parent.document.getElementById("menuFrame");	
	obj.src = "";
	obj.width = 0;
	obj.height = 0;
}

function quietCommit(locVal, bRefresh, timeoutval)
{
	obj = window.parent.document.getElementById("ActionFrame");	
	obj.src = locVal;

	if (bRefresh=='yes')
	{
		if (typeof(timeoutval) == "undefined")
		{
			timeoutval = 1000;
		}
		// this in needed because without the delay the page reloads before the iFrame has loaded
		setTimeout('parent.location.reload()', timeoutval);
	}
}

function showAddSupplier()
{
	loc = "http://www.customhostingtools.com/adminDialogs/adminDialogBorder.php?dialogName=addSupplier";
	var w = 700;
	var h = 350;
	showIFramePopup(loc, w, h);
}

function surfDialog(loc, w, h, callingWin, bRefresh, bReturnWin)
{
  if (typeof(bRefresh)=="undefined")
  	 bRefresh = true;
  if (typeof(bReturnWin)=="undefined")
  	 bReturnWin = false;

  var top = new String( (screen.height/2)-(h/2) );
  var left = new String( (screen.width/2)-(w/2) );

  // remove any decimals if there are any
  if (top.indexOf(".") > -1)
  	top = top.substring(0,top.indexOf("."));
	
  if (left.indexOf(".") > -1)
  	left = left.substring(0,left.indexOf("."));
  
  winStr = "resizable=yes,status=yes,scrollbars=yes,toolbar=no,location=no,menu=no,top=" + top +",screenY=" + top + ",left=" + left + ",width=" + w + ",height=" + h ;

  if (bRefresh)
  {
  	window.onfocus = refreshCommit;
  }

  win = window.open(loc, "_blank", winStr);
  
  if (!win)
  {
	alert("COMS has detected a popup blocker on your system.  In order work with this site, you must allow administration windows to open.  \n\nPlease disable popup blocking for this URL.");
  }

  if (typeof(callingWin) != "undefined")
  {
  	win.callWin = callingWin;
  }
  
  if (bReturnWin){
	return win;
  }
}

function refreshCommit()
{
  window.onfocus = ''; 
  location.reload();
}

function addPhone(myfield, e, dec, com)
{
	
	//alert(myfield.value + ': ' + myfield.length);
	if (!numbersonly(myfield, e, dec, com))
	{
		return false;
	}
	else if (myfield.value.length == 3)
	{
		myfield.value = '(' + myfield.value + ') ';
		return true;
	}
	else if (myfield.value.length == 9)
	{
		myfield.value = myfield.value + '-';
		return true;
	}
	
	return true;
}

function numbersonly(myfield, e, dec, com)
{
	var key;
	var keychar;
	
	if (window.event)
	   key = window.event.keyCode;
	else if (e)
	   key = e.which;
	else
	   return true;
	keychar = String.fromCharCode(key);
	
	if (com && (keychar == ","))
		return true;
	
	// control keys
	if ((key==null) || (key==0) || (key==8) || 
		(key==9) || (key==13) || (key==27) )
	   return true;
	
	// numbers
	else if ((("0123456789").indexOf(keychar) > -1))
	   return true;
		  
	
	// decimal point jump
	else if (dec && (keychar == "."))
	   {
	   myfield.form.elements[dec].focus();
	   return false;
	   }
	else
	   return false;
}

function getCookie(NameOfCookie)
{
	if (document.cookie.length > 0)
	{ 
		begin = document.cookie.indexOf(NameOfCookie+"=");
		if (begin != -1)
		{
			begin += NameOfCookie.length+1;
			end = document.cookie.indexOf(";", begin);
			if (end == -1) end = document.cookie.length;
				return unescape(document.cookie.substring(begin, end)); 
		}
	}
	return null;
}



function setCookie(NameOfCookie, value, expiredays)
{ 
	var ExpireDate = new Date ();
	ExpireDate.setTime(ExpireDate.getTime() + (expiredays * 24 * 3600 * 1000));
	document.cookie = NameOfCookie + "=" + escape(value) +
	((expiredays == null) ? "" : "; expires=" + ExpireDate.toGMTString());
}



function delCookie (NameOfCookie)
{ 
	if (getCookie(NameOfCookie)) 
	{
		document.cookie = NameOfCookie + "=" +
		"; expires=Thu, 01-Jan-70 00:00:01 GMT";
	}
}