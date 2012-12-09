//------------------------------------------------------------------------------
// IPS XML HTTP REQUEST:: Albums Typeahead
//------------------------------------------------------------------------------
// Supports Safari, Mozilla 1.3+ (Firefox, etc) and IE 5.5+
// (c) 2005 Invision Power Services, Inc.
// http://www.
//------------------------------------------------------------------------------

/*--------------------------------------------*/
// INIT VARS
/*--------------------------------------------*/

var mem_div  = 'ipb-get-members';
var divobj;
var iframeobj;
var formobj;
var xmlobj;
var _result_cache       = new Array();
var _div_rows           = 0;
var _cur_updown_pressed = 0;
var _last_key_code      = 0;
var _event_key_code     = 0;
var _cur_input_val      = '';
var _old_input_val      = '';
var _hiliteindex        = -1;
var _hilite_sug_div;
var _complete_div_list;
var delay_action        = false;
var _allow_update       = true;
var _form_onsubmit_saved;
var _urllookup;
var _timemout_main = 0;
var _timeout_blur  = 0;
var _in_focus      = 0;

/*--------------------------------------------*/
// SHOW POSSIBLE MEMBER NAME MATCHES
/*--------------------------------------------*/

main_loop = function()
{
	if ( ( _old_input_val != _cur_input_val ) && ( _cur_input_val.length >= 3 ) && ( _in_focus == 1 ) )
	{
		if ( ! delay_action && _allow_update )
		{
			//----------------------------------
			// Try cache
			//----------------------------------
			
			var cached = _result_cache[ _cur_input_val ];
			
			if ( cached )
			{
				returnSearch( _cur_input_val, cached[0], cached[1] );
			}
			else
			{
				/*--------------------------------------------*/
				// Main function to do on request
				// Must be defined first!!
				/*--------------------------------------------*/
				
				do_request_function = function()
				{
					//----------------------------------
					// Ignore unless we're ready to go
					//----------------------------------
					
					if ( ! xmlobj.readystate_ready_and_ok() )
					{
						// Could do a little loading graphic here?
						return;
					}
		
					//----------------------------------
					// INIT
					//----------------------------------
					
					var returned = xmlobj.xmlhandler.responseText;
					
					//----------------------------------
					// Did we get something?
					//----------------------------------
					
					if ( returned.charAt(0) == '<' )
					{
						return false;
					}
					else
					{
						eval( returned );
					}
				};
				
				xmlobj = new ajax_request();
				xmlobj.onreadystatechange( do_request_function );
				
				xmlobj.process( ipb_var_base_url + '&autocom=gallery&req=quickch&op=albumnames&name='+escape(inobj.value) );
				
				inobj.focus();
			}
		}
		
		delay_action = false;
	}
	
	_old_input_val = _cur_input_val;
		
	_timemout_main = setTimeout( 'main_loop()', 10 );
	
	return false;
};

/*--------------------------------------------*/
// Initalize...
/*--------------------------------------------*/

function init_js( formobjname, fieldobjname )
{
	//----------------------------------
	// Set up
	//----------------------------------
	
	formobj = document.getElementById( formobjname );
	inobj   = document.getElementById( fieldobjname );
	divobj  = document.getElementById( mem_div );
	
	inobj.setAttribute( "autocomplete", "off" );
	inobj.onfocus      = function () { _in_focus = 1; };
	inobj.onblur       = onblurhandler;
	inobj.onsubmit     = submit_handler;
	
	//----------------------------------
	// Already got a submit handler?
	//----------------------------------
	
	if ( formobj.onsubmit )
	{
		_form_onsubmit_saved = formobj.onsubmit;
	}
	
	formobj.onsubmit   = submit_handler;
	
	if ( inobj.createTextRange )
	{
		inobj.onkeyup = new Function( "return onkeyuphandler(event);" );
	}
	else
	{
		inobj.onkeyup = onkeyuphandler;
	}
	
	cache_name_results("",new Array(),new Array(),new Array());
	
	_cur_input_val     = inobj.value;
	
	document.onkeydown = keydownhandler;
	
	set_up_key_down();
}

/*--------------------------------------------*/
// Set up key down 
/*--------------------------------------------*/

function set_up_key_down()
{
	if ( document.createEventObject )
	{
   		var y = document.createEventObject();
    	y.ctrlKey = true;
    	y.keyCode = 70;
    	document.fireEvent( "onkeydown", y )
	}
}

/*--------------------------------------------*/
// Key down handler 
/*--------------------------------------------*/

function keydownhandler( event )
{
	if( ! event && window.event )
	{
    	event = window.event;
	}
	if ( event )
	{
		_last_key_code = event.keyCode;
	}
}

/*--------------------------------------------*/
// On blur handler
/*--------------------------------------------*/

function onblurhandler( event )
{
	if ( ! event && window.event )
	{
		event = window.event;
	}
	
	if ( ! _cur_updown_pressed )
	{
		_in_focus = 0;
		
		my_xr_hide_div( divobj );
		_hiliteindex = -1;
		
		// Tab?
		
		if ( _last_key_code == 9 )
		{
			_last_key_code = -1;
		}
	}
	
	_cur_updown_pressed = false;
}

/*--------------------------------------------*/
// On key up handler
/*--------------------------------------------*/

function key_handler_function()
{
	//----------------------------------
	// trap up/down cursor
	//----------------------------------
	
	if ( _event_key_code == 40 || _event_key_code == 38 )
	{
		_allow_update = false;
		blur_then_focus();
	}
	else
	{
		_cur_updown_pressed = false;
		_allow_update       = true;
	}
	
	_cur_input_val = inobj.value;
	
	handle_cursor_press( _event_key_code );
}

/*--------------------------------------------*/
// Handle cursor press
/*--------------------------------------------*/

function handle_cursor_press( eventcode )
{
	if ( eventcode == 40 )
	{
		hilite_new_value( _hiliteindex + 1 );
	}
	else if ( eventcode == 38 )
	{
		hilite_new_value( _hiliteindex - 1 );
	}
	else if ( eventcode == 13 || eventcode == 3 )
	{
		//----------------------------------
		// Are we looking through the divs?
		//----------------------------------
		
		if ( _hiliteindex != -1 )
		{
			my_xr_hide_div( divobj );
			_hiliteindex = -1;
		}
		
		return false;
	}
	
	return true;
}

/*--------------------------------------------*/
// Highlight new value (cursor keys pressed)
/*--------------------------------------------*/

function hilite_new_value( index )
{
	if ( _div_rows <= 0 )
	{
		return;
	}
	
	my_show_div( divobj );
	
	if ( index > _div_rows )
	{
		index = _div_rows - 1;
	}
	
	if ( _hiliteindex != -1 && index != _hiliteindex )
	{
		set_style_for_element( _hilite_sug_div, 'wrapdiv' );
	}
	
	if ( index < 0 )
	{
		_hiliteindex = -1;
		inobj.focus();
		return;
	}
	
	_hiliteindex = index;
	_hilite_sug_div = divobj.getElementsByTagName('DIV').item( index );
	
	set_style_for_element( _hilite_sug_div, 'spanhilite' );
	
	newval = find_span_value_for_class( _hilite_sug_div, 'namespan' );
	
	if ( newval && typeof(newval) != 'undefined' )
	{
		inobj.value = find_span_value_for_class( _hilite_sug_div, 'namespan' );
	}
}

/*--------------------------------------------*/
// Blur then focus
/*--------------------------------------------*/

function blur_then_focus()
{
	_cur_updown_pressed = true;
	
	inobj.blur();
	
	_timemout_blur = setTimeout( "set_input_field_focus();", 10 );
	return;
}

/*--------------------------------------------*/
// Set input field focus
/*--------------------------------------------*/

function set_input_field_focus()
{
	inobj.focus();
}

/*--------------------------------------------*/
// Styles element
/*--------------------------------------------*/

function set_style_for_element( c, name )
{
	try
	{
		if ( ! c )
		{
			return;
		}
	}
	catch(e)
	{
		return;
	}
	
	c.className = name;
	
	switch( name )
	{
		case 'wrapdiv':
			c.style.backgroundColor = "white";
      		c.style.color           = "black";
      		
      		if ( c.displaySpan )
      		{
        		c.displaySpan.style.color = "green";
        	}
        	
        	break;
        case 'wrapspan':
			c.style.display       = "block";
      		c.style.paddingLeft   = "3";
     		c.style.paddingRight = "3";
      		c.style.height        = "16px";
      		c.style.overflow      = "hidden";
        	break;
        case 'namespan':
        	c.style.cssFloat = "left";
        	c.style.width    = '100%';
      		break;
      	case 'idspan':
      		c.style.cssFloat = "right";
      		c.style.display  = 'none';
      		break;
      	case 'spanhilite':
      		c.style.backgroundColor = "#3366cc";
      		c.style.color           = "white";
      		
      		if ( c.displaySpan )
      		{
        		c.displaySpan.style.color = "white";
      		}
      		
      		break;
      	}
}

/*--------------------------------------------*/
// Get value of named span
/*--------------------------------------------*/

function find_span_value_for_class( i, dc )
{
	try
	{
		if ( ! i )
		{
			return;
		}
	}
	catch(e)
	{
		return;
	}

	var ga = i.getElementsByTagName('div');
   
	if ( ga )
	{
		for( var f=0; f < ga.length; ++f )
		{
			if ( ga[f].className == dc )
			{
		  		var value = ga[f].innerHTML;
		  		
				if ( value == "&nbsp;" )
				{
					return "";
				}
				else
				{
					return value;
				}
			}
		}
    }
	else
	{
    	return "";
	}
}


/*--------------------------------------------*/
// Displays the actual list
/*--------------------------------------------*/

function display_suggested_list( na, da, ia )
{
	//----------------------------------
	// Clear div
	//----------------------------------
	
	while( divobj.childNodes.length > 0 )
	{
    	divobj.removeChild( divobj.childNodes[0] );
  	}
 
	//----------------------------------
	// Write Div
	//----------------------------------
	
	_div_rows = 0;
	
	for( var f = 0 ; f < na.length; ++f )
	{
		_div_rows++;
		var od = document.createElement("DIV");
		set_style_for_element( od, 'wrapdiv' );
		od.onmousedown = sb_mdown;
		od.onmouseover = sb_mover;
		od.onmouseout  = sb_mout;
		
		var span_wrap  = document.createElement("SPAN");
		set_style_for_element( span_wrap, 'wrapspan' );
		
		var span_name       = document.createElement("SPAN");
		span_name.innerHTML = da[f];
		
		var span_dname       = document.createElement("div");
		span_dname.innerHTML = na[f];
		
		var span_id    = document.createElement("SPAN");
		
		set_style_for_element( span_name, 'namespan' );
		set_style_for_element( span_dname, 'namespan' );
		set_style_for_element( span_id,   'idspan'   );
		
		od.displaySpan      = span_id;
		
		span_id.innerHTML   = ia[f];
		
		span_wrap.appendChild( span_name );
		span_wrap.appendChild( span_id   );
		span_wrap.appendChild( span_dname   );
		od.appendChild( span_wrap );
		divobj.appendChild( od );
	}
	 
	//----------------------------------
	// Calc pos.
	//----------------------------------
	
	var mid     = inobj;	
	var left_px = _get_obj_leftpos(mid);
	var top_px  = _get_obj_toppos(mid) + mid.offsetHeight;
	var width   = parseInt( divobj.style.width );
	var height  = parseInt( divobj.style.height );
	
	//----------------------------------
	// Show menu DIV
	//----------------------------------
	
	divobj.style.position = "absolute";
	
	//----------------------------------
	// Try and keep it on screen
	//----------------------------------
	
	if ( (left_px + width) >= document.body.clientWidth )
	{
		left_px = left_px + mid.offsetWidth - width;
	}
	
	//----------------------------------
	// Try and keep it on screen
	//----------------------------------
	
	if ( (top_px + height) >= document.body.clientHeight )
	{
		top_px = top_px - height;
	}
	
	//----------------------------------
	// Finalize menu position
	//----------------------------------
	
	divobj.style.left     = left_px + "px";
	divobj.style.top      = top_px  + "px";
	divobj.style.display  = "block";

	_hiliteindex= -1;
	
	//----------------------------------
	// Workaround for IE bug which shows
	// select boxes and other windows GUI
	// over divs. SHOW IFRAME
	//----------------------------------
	
	if ( is_ie )
	{
		try
		{
			if ( ! document.getElementById( 'if_xhr_' + mem_div ) )
			{ 
				iframeobj = document.createElement('iframe');
				
				iframeobj.src = 'javascript:;';
				iframeobj.id  = 'if_xhr_' + mem_div;
				
				document.getElementsByTagName('body')[0].appendChild( iframeobj );
			}
			else
			{
				iframeobj = document.getElementById( 'if_xhr_' + mem_div );
			}
			
			iframeobj.scrolling      = 'no';
			iframeobj.frameborder    = 'no';
			iframeobj.className      = 'iframeshim';
			iframeobj.style.position = 'absolute';
				
			iframeobj.style.width   = parseInt(divobj.offsetWidth)  + 'px';
			iframeobj.style.height  = parseInt(divobj.offsetHeight) + 'px';
			iframeobj.style.top     = divobj.style.top;
			iframeobj.style.left    = divobj.style.left;
			iframeobj.style.zIndex  = divobj.style.zIndex - 1;
			iframeobj.style.display = "block";
    		
    	}
    	catch(error)
    	{
    		//alert(error); // Oh dear, someones stolen the iframe
    	}
	}
}

/*--------------------------------------------*/
// Caches the results
/*--------------------------------------------*/

function cache_name_results( mstring, na, da, ia )
{
	_result_cache[mstring] = new Array( na, da, ia );
}

/*--------------------------------------------*/
// This is the function the response XML text gives
/*--------------------------------------------*/

returnSearch = function( mstring, namearray, displayarray, idarray )
{
	//----------------------------------
	// Cache..
	//----------------------------------
	
	cache_name_results( mstring, namearray, displayarray, idarray );
	
	if ( _in_focus != 1 )
	{
		return false;
	}
	
	//----------------------------------
	// Display
	//----------------------------------
	
	display_suggested_list( namearray, displayarray, idarray );
	
	//----------------------------------
	// Resize or hide?
	//----------------------------------
	
	if ( _div_rows > 0 )
	{
		divobj.style.height = 16 * _div_rows + 4;
	}
	else
	{
		my_xr_hide_div( divobj );
		_hiliteindex = -1;
	}
};

/*--------------------------------------------*/
// Submit handler
/*--------------------------------------------*/

submit_handler = function ( event )
{
	delay_action = true;
	var retval   = true;
	
	if ( _hiliteindex != -1 )
	{
		return false;
	}
	else
	{
		if ( _form_onsubmit_saved )
		{
			eval( 'tmpsubmit = ' + _form_onsubmit_saved );
			retval = tmpsubmit();
		}
		
		return retval;
	}
};

/*--------------------------------------------*/
// On key up handler
/*--------------------------------------------*/

onkeyuphandler = function( event )
{
	_event_key_code = event.keyCode;
	key_handler_function();
};

/*--------------------------------------------*/
// Div mouse events
/*--------------------------------------------*/

sb_mdown = function()
{
	delay_action = true;
	inobj.value  = find_span_value_for_class( this, 'namespan' );
};

sb_mover = function()
{
	set_style_for_element( this, 'spanhilite' );
};

sb_mout = function()
{
	set_style_for_element( this, 'wrapdiv' );
};

function my_xr_hide_div ( dobj )
{
	dobj.style.display = "none";
	
	if ( is_ie )
	{
		try
		{
			document.getElementById( 'if_xhr_' + mem_div ).style.display = "none";
		}
		catch(e)
		{
			//alert(e);// Oh dear, someones stolen the iframe
		}
	}
} 



