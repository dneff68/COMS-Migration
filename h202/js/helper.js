// JavaScript Document
function w_source()
  {
  var wr = ''
  function ws()
    {
    wr += ''.concat.apply('', arguments)
    }
  function wl()
    {
    wr += ''.concat.apply('', arguments) + '\r\n'
    }
  function wx( AA, BB )
    {
    wl(spf(AA,BB))
    }
  wl( "var wr = ''" )
  wl( ws )
  wl( wl )
  wl( wx )
  return wr
  }
  
function spf( s, t )
  {
  var n=0
  function F()
    {
    return t[n++]
    }
  return s.replace(/~/g, F)
  }
  

function xid( a )
  {
	  return window.document.getElementById( a )
  }

function globalize_id( the_id )
  {
  	window [ the_id ] = xid(the_id)
  }
