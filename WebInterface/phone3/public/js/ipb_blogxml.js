//------------------------------------------------------------------------------
// IPS XML HTTP REQUEST:: BLOG SETTINGS
//------------------------------------------------------------------------------
// Supports Safari, Mozilla 1.3+ (Firefox, etc) and IE 5.5+
// (c) 2005 Invision Power Services, Inc.
// http://www.
//------------------------------------------------------------------------------

/*--------------------------------------------*/
// INIT VARS
/*--------------------------------------------*/

var divobj;
var divopen  = 0;
var xmlobj;

var myblogset_main;
var myblogcont_loaded = 0;
var myblogset_loaded   = 0;
var myblogset_timeout;
var myblogset_htmlcache = "";
var myblogset_editid = 0;
var cblock_handle = new Array();
var cblock_main = new Array();
var cblock_position = new Array();
var cblock_ph = new Array();
var cblock_ph_orgh = new Array();
var cblock_ph_orgx = new Array();
var cblock_ph_orgy = new Array();
var cblock_ph_dragging = new Array();
var cblock_anim = new Array();
var cblock_shim = new Array();
var cblock_newpos = 0;
var cblocks_left = 0;
var cblocks_right = 0;
var screen_width = 0;
var screen_height = 0;
var screen_middle = 0;
var pos_ind;
var left_bar;
var right_bar;
var open_config = 0;

/*--------------------------------------------*/
// Load MyBlogSettings!
/*--------------------------------------------*/

function xml_myblogsettings_init( tab )
{
	clearTimeout( _timemout_main );
	myblogset_main = $( 'get-myblogsettings' );
	myblogset_drag = $( 'myblogset-drag' );

	ipb_var_settings_close = 0;
	ipb_var_settings_changed = 0;
	
	if ( ! tab )
	{
		var ajax_url = ipb_var_base_url + 'autocom=blog&req=mysettingsxml&blogid=' + ipb_var_blog_id;
	}
	else
	{
		var ajax_url = ipb_var_base_url + 'autocom=blog&req=mysettingsxml&blogid=' + ipb_var_blog_id + '&tab=' + tab;
	}
	
	new Ajax.Request(
						ajax_url,
						{
							method: 'post',
							onSuccess: function( s )
							{
								var settings_html = s.responseText;
						
								if ( is_ie )
								{
									settings_html = "<iframe id='myblogset-shim' src='javascript:false;' scrolling='no' frameborder='0' style='position:absolute; top:0px; left:0px; display:none;'></iframe>" + settings_html;
								}
								
								myblogset_content = $('myblogset-content');
								myblogset_content.innerHTML = settings_html;
								
								if ( is_ie )
								{
									myblogset_shim               = $('myblogset-shim');
									myblogset_shim.style.width   = myblogset_content.offsetWidth;
						    		myblogset_shim.style.height  = myblogset_content.offsetHeight;
						    		myblogset_shim.style.zIndex  = myblogset_content.style.zIndex - 1;
									myblogset_shim.style.top     = myblogset_content.style.top;
									myblogset_shim.style.left    = myblogset_content.style.left;
						    		myblogset_shim.style.display = "block";
						    	}
						
								blogset_initnamelookup( 'blogset_form', 'entered_name' );								
							}
						}
	);

  	myblogset_main.style.position = 'absolute';
	myblogset_main.style.display  = 'block';
	myblogset_main.style.zIndex   = 50;

	//----------------------------------
	// Not loaded? INIT
	//----------------------------------

	if ( ! myblogset_loaded )
	{
		//----------------------------------
		// Figure width and height
		//----------------------------------
		var my_width  = 0;
		var my_height = 0;

		if ( typeof( window.innerWidth ) == 'number' )
		{
			//----------------------------------
			// Non IE
			//----------------------------------

			my_width  = window.innerWidth;
			my_height = window.innerHeight;
		}
		else if ( document.documentElement && ( document.documentElement.clientWidth || document.documentElement.clientHeight ) )
		{
			//----------------------------------
			// IE 6+
			//----------------------------------

			my_width  = document.documentElement.clientWidth;
			my_height = document.documentElement.clientHeight;
		}
		else if( document.body && ( document.body.clientWidth || document.body.clientHeight ) )
		{
			//----------------------------------
			// Old IE
			//----------------------------------

			my_width  = document.body.clientWidth;
			my_height = document.body.clientHeight;
		}

		//----------------------------------
		// Get div height && width
		//----------------------------------
		var divheight = parseInt( myblogset_main.style.Height );
		var divwidth  = parseInt( myblogset_main.style.width );

		divheight = divheight ? divheight : 500;
		divwidth  = divwidth  ? divwidth  : 700;

		//----------------------------------
		// Reposition DIV roughly centered
		//----------------------------------

		myblogset_main.style.left = ( my_width  / 2  - (divwidth / 2) )  + 'px';
		var myblogset_top = parseInt( ( my_height / 2 - (divheight / 2 )) );
		if ( myblogset_top < 10 )
		{
			myblogset_top = 10;
		}
		myblogset_main.style.top  = myblogset_top + 'px';

		Drag.init( myblogset_drag, myblogset_main );

		myblogset_loaded = 1;
	}
}

/*--------------------------------------------*/
// Close Settings Window!
/*--------------------------------------------*/
function close_set_window()
{
	$("get-myblogsettings").style.display="none";
	clearTimeout( _timemout_main );

	if ( ipb_var_settings_changed == 1 )
	{
		window.location = ipb_var_blog_url.replace( '&amp;', '&');
	}
}

/*--------------------------------------------*/
// Save Blog Settings!
/*--------------------------------------------*/

function blogset_save( tab, postform )
{
	/*--------------------------------------------*/
	// Main function to do on request
	/*--------------------------------------------*/
	do_request_function = function()
	{
		if ( ! xmlobj.readystate_ready_and_ok() )
		{
 			xmlobj.show_loading();
 			return;
		}
		xmlobj.hide_loading();

		//----------------------------------
		// INIT
		//----------------------------------
		var settings_status = xmlobj.xmlhandler.responseXML.getElementsByTagName("settings-status");
		var message_status	= xmlobj.get_element_text_ns( "", 'status', settings_status[0], 0 );
		var message_html	= xmlobj.get_element_text_ns( "", 'status_html', settings_status[0], 0 );

		$('myblogset-info').innerHTML = message_html;

		if ( ipb_var_settings_close == 1 && message_status == "OK" )
		{
			close_set_window();
		}
	};

	//----------------------------------
	// LOAD XML
	//----------------------------------
	var post_array = build_postarray( postform );
	var editorsObj = $('blog_editors');
	if ( editorsObj != null )
	{
		post_array['editors'] = "";
		for (i=0; i<editorsObj.options.length; i++)
		{
			post_array['editors'] = post_array['editors'] + editorsObj.options[i].value + ",";
		}
	}
	var privateclubObj = $('blog_privateclub');
	if ( privateclubObj != null )
	{
		post_array['privateclub'] = "";
		for (i=0; i<privateclubObj.options.length; i++)
		{
			post_array['privateclub'] = post_array['privateclub'] + privateclubObj.options[i].value + ",";
		}
	}

	xmlobj = new ajax_request();
	xmlobj.onreadystatechange( do_request_function );

	var to_post = xmlobj.format_for_post( post_array );
	xmlobj.process( ipb_var_base_url + '&tab=' + tab, "POST", to_post );
	ipb_var_settings_changed = 1;
}

/*--------------------------------------------*/
// Save Blog Settings and Close window!
/*--------------------------------------------*/

function blogset_saveclose( tab, fields )
{
	ipb_var_settings_close = 1;
	blogset_save( tab, fields );
}

/*--------------------------------------------*/
// Init name lookup
/*--------------------------------------------*/

function blogset_initnamelookup( formname, fieldname )
{
	if ( $( fieldname ) != null )
	{
		// INIT find names
		init_js( formname, fieldname );
		// Run main loop
		mainloop_timeout = setTimeout( 'main_loop()', 10 );
	}
}

/*--------------------------------------------*/
// Add editor
/*--------------------------------------------*/

function blogset_addeditor( postform )
{
	/*--------------------------------------------*/
	// Main function to do on request
	/*--------------------------------------------*/
	do_request_function = function()
	{
		if ( ! xmlobj.readystate_ready_and_ok() )
		{
 			xmlobj.show_loading();
 			return;
		}
		xmlobj.hide_loading();

		//----------------------------------
		// INIT
		//----------------------------------
		var settingsstatus = xmlobj.xmlhandler.responseXML.getElementsByTagName("settings-status");
		var member_status	= xmlobj.get_element_text_ns( "", 'status', settingsstatus[0], 0 );
		var member_message  = xmlobj.get_element_text_ns( "", 'message', settingsstatus[0], 0 );

		$('myblogset-info').innerHTML = member_message;

		if ( member_status == "ok" )
		{
			var member_id		= xmlobj.get_element_text_ns( "", 'member-id', settingsstatus[0], 0 );
			var member_name		= convert_html_entities( xmlobj.get_element_text_ns( "", 'member-name', settingsstatus[0], 0 ) );
			var editorlist = $('blog_editors');
			var i = editorlist.options.length;
			editorlist.options[i] = new Option(member_name, member_id);
			$('entered_name').value = "";
		}
	};

	//----------------------------------
	// LOAD XML
	//----------------------------------
	var post_array = build_postarray( postform );
	var editorsObj = $('blog_editors');
	post_array['editors'] = "";
	for (i=0; i<editorsObj.options.length; i++)
	{
		post_array['editors'] = post_array['editors'] + editorsObj.options[i].value + ",";
	}

	xmlobj = new ajax_request();
	xmlobj.onreadystatechange( do_request_function );

	var to_post = xmlobj.format_for_post( post_array );
	xmlobj.process( ipb_var_base_url + '&tab=addeditor', "POST", to_post );
}

/*--------------------------------------------*/
// Add private club member
/*--------------------------------------------*/

function blogset_addprivmember( postform )
{
	/*--------------------------------------------*/
	// Main function to do on request
	/*--------------------------------------------*/
	do_request_function = function()
	{
		if ( ! xmlobj.readystate_ready_and_ok() )
		{
 			xmlobj.show_loading();
 			return;
		}
		xmlobj.hide_loading();

		//----------------------------------
		// INIT
		//----------------------------------
		var settings_status = xmlobj.xmlhandler.responseXML.getElementsByTagName("settings-status");
		var member_status	= xmlobj.get_element_text_ns( "", 'status', settings_status[0], 0 );
		var member_message  = xmlobj.get_element_text_ns( "", 'message', settings_status[0], 0 );

		$('myblogset-info').innerHTML = member_message;

		if ( member_status == "ok" )
		{
			var member_id		= xmlobj.get_element_text_ns( "", 'member-id', settings_status[0], 0 );
			var member_name		= convert_html_entities( xmlobj.get_element_text_ns( "", 'member-name', settings_status[0], 0 ) );
			var privateclublist = $('blog_privateclub');
			var i = privateclublist.options.length;
			privateclublist.options[i] = new Option(member_name, member_id);
			$('entered_name').value = "";
		}
	};

	//----------------------------------
	// LOAD XML
	//----------------------------------
	var post_array = build_postarray( postform );
	var privateclubObj = $('blog_privateclub');
	post_array['privateclub'] = "";
	for (i=0; i<privateclubObj.options.length; i++)
	{
		post_array['privateclub'] = post_array['privateclub'] + privateclubObj.options[i].value + ",";
	}

	xmlobj = new ajax_request();
	xmlobj.onreadystatechange( do_request_function );

	var to_post = xmlobj.format_for_post( post_array );
	xmlobj.process( ipb_var_base_url + '&tab=addprivateclub', "POST", to_post );
}

/*--------------------------------------------*/
// Remove Private Club Member
/*--------------------------------------------*/

function blogset_delprivmember()
{
	var privateclublist = $('blog_privateclub');
	for ( i=0; i < privateclublist.options.length; i++ )
	{
		if ( privateclublist.options[i].selected )
		{
			privateclublist.options[i] = null;
			i--;
		}
	}
}
/*--------------------------------------------*/
// Add category
/*--------------------------------------------*/

function blogset_addcategory( postform )
{
	if ( myblogset_editid != 0 )
	{
		var oldcat = $( "cat_" + myblogset_editid );
		oldcat.innerHTML = myblogset_htmlcache;
		myblogset_htmlcache = "";
		myblogset_editid = 0;
	}

	/*--------------------------------------------*/
	// Main function to do on request
	/*--------------------------------------------*/
	do_request_function = function()
	{
		if ( ! xmlobj.readystate_ready_and_ok() )
		{
 			xmlobj.show_loading();
 			return;
		}
		xmlobj.hide_loading();

		var category_html = xmlobj.xmlhandler.responseText;

		if ( is_ie )
		{
			category_html = "<iframe id='myblogset-shim' src='javascript:false;' scrolling='no' frameborder='0' style='position:absolute; top:0px; left:0px; display:none;'></iframe>" + category_html;
		}
		myblogset_content = $('myblogset-content');
		myblogset_content.innerHTML = category_html;
		if ( is_ie )
		{
			myblogset_shim = $('myblogset-shim');
			myblogset_shim.style.width = myblogset_content.offsetWidth;
    		myblogset_shim.style.height = myblogset_content.offsetHeight;
    		myblogset_shim.style.zIndex = myblogset_content.style.zIndex - 1;
    		myblogset_shim.style.display = "block";
    	}
	};

	//----------------------------------
	// LOAD XML
	//----------------------------------
	var post_array = build_postarray( postform );

	xmlobj = new ajax_request();
	xmlobj.onreadystatechange( do_request_function );

	var to_post = xmlobj.format_for_post( post_array );
	xmlobj.process( ipb_var_base_url, "POST", to_post );
}

/*--------------------------------------------*/
// Add category
/*--------------------------------------------*/

function blogset_delcat( postform, category_id )
{
	if ( myblogset_editid != 0 )
	{
		var oldcat = $( "cat_" + myblogset_editid );
		oldcat.innerHTML = myblogset_htmlcache;
		myblogset_htmlcache = "";
		myblogset_editid = 0;
	}

	if ( confirm( "Are you sure you want to delete this category?" ) )
	{
		/*--------------------------------------------*/
		// Main function to do on request
		/*--------------------------------------------*/
		do_request_function = function()
		{
			if ( ! xmlobj.readystate_ready_and_ok() )
			{
	 			xmlobj.show_loading();
	 			return;
			}
			xmlobj.hide_loading();

			var category_html = xmlobj.xmlhandler.responseText;

			if ( is_ie )
			{
				html = "<iframe id='myblogset-shim' src='javascript:false;' scrolling='no' frameborder='0' style='position:absolute; top:0px; left:0px; display:none;'></iframe>" + category_html;
			}
			myblogset_content = $('myblogset-content');
			myblogset_content.innerHTML = category_html;
			if ( is_ie )
			{
				myblogset_shim = $('myblogset-shim');
				myblogset_shim.style.width = myblogset_content.offsetWidth;
	    		myblogset_shim.style.height = myblogset_content.offsetHeight;
	    		myblogset_shim.style.zIndex = myblogset_content.style.zIndex - 1;
	    		myblogset_shim.style.display = "block";
	    	}
		};

		//----------------------------------
		// LOAD XML
		//----------------------------------
		var post_array = build_postarray( postform );
		post_array['category_id'] = category_id;
		post_array['tab'] = 'delcategory';

		xmlobj = new ajax_request();
		xmlobj.onreadystatechange( do_request_function );

		var to_post = xmlobj.format_for_post( post_array );
		xmlobj.process( ipb_var_base_url, "POST", to_post );
	}
}

/*--------------------------------------------*/
// Edit category
/*--------------------------------------------*/

function blogset_editcat( auth_key, category_id )
{
	if ( myblogset_editid != 0 )
	{
		var oldcat = $( "cat_" + myblogset_editid );
		oldcat.innerHTML = myblogset_htmlcache;
		myblogset_htmlcache = "";
		myblogset_editid = 0;
	}

	/*--------------------------------------------*/
	// Main function to do on request
	/*--------------------------------------------*/
	do_request_function = function()
	{
		if ( ! xmlobj.readystate_ready_and_ok() )
		{
 			xmlobj.show_loading();
 			return;
		}
		xmlobj.hide_loading();

		var catrow = $( "cat_" + category_id );
		myblogset_htmlcache = catrow.innerHTML;
		myblogset_editid = category_id;
		catrow.innerHTML = xmlobj.xmlhandler.responseText;
	};

	//----------------------------------
	// LOAD XML
	//----------------------------------
	xmlobj = new ajax_request();
	xmlobj.onreadystatechange( do_request_function );

	xmlobj.process( ipb_var_base_url+"&autocom=blog&req=dosettingsxml&tab=editcat&blogid="+ipb_var_blog_id+"&auth_key="+auth_key+"&category_id="+category_id );
}

/*--------------------------------------------*/
// Do Edit category
/*--------------------------------------------*/

function blogset_doeditcat()
{
	/*--------------------------------------------*/
	// Main function to do on request
	/*--------------------------------------------*/
	do_request_function = function()
	{
		if ( ! xmlobj.readystate_ready_and_ok() )
		{
 			xmlobj.show_loading();
 			return;
		}
		xmlobj.hide_loading();

		//----------------------------------
		// INIT
		//----------------------------------
		var settings_status = xmlobj.xmlhandler.responseXML.getElementsByTagName("settings-status");
		var cat_status	= xmlobj.get_element_text_ns( "", 'status', settings_status[0], 0 );
		var cat_message  = xmlobj.get_element_text_ns( "", 'message', settings_status[0], 0 );

		var oldcat = $( "cat_" + myblogset_editid );
		if ( cat_status != "ok" )
		{
			$('myblogset-info').innerHTML = cat_message;
			oldcat.innerHTML = myblogset_htmlcache;
		}
		else
		{
			oldcat.innerHTML = xmlobj.get_element_text_ns( "", 'category-html', settings_status[0], 0 );
		}
		myblogset_htmlcache = "";
		myblogset_editid = 0;
	};

	//----------------------------------
	// LOAD XML
	//----------------------------------
	var post_array = build_postarray( 'editcat_form' );

	xmlobj = new ajax_request();
	xmlobj.onreadystatechange( do_request_function );

	var to_post = xmlobj.format_for_post( post_array );
	xmlobj.process( ipb_var_base_url, "POST", to_post );
}

/*--------------------------------------------*/
// Remove editor
/*--------------------------------------------*/

function blogset_deleditor()
{
	var editorlist = $('blog_editors');
	for ( i=0; i < editorlist.options.length; i++ )
	{
		if ( editorlist.options[i].selected )
		{
			editorlist.options[i] = null;
			i--;
		}
	}
}

/*--------------------------------------------*/
// Init Drag/Drop Vars
/*--------------------------------------------*/
function cblock_init_dragdrop_vars()
{
	right_bar = $( 'cblock_right' );
	left_bar = $( 'cblock_left' );
	right_bar_container = $( 'cblock_right_wrap' );
	left_bar_container = $( 'cblock_left_wrap' );
}

/*--------------------------------------------*/
// Content Block DragDrop Initialisation
/*--------------------------------------------*/

function cblock_dragdrop_init( ph_id, pos )
{
	cblock_anim[ph_id] = setTimeout('null',1);
	cblock_main[ph_id] = $( 'cblock_'+ph_id );
	if ( is_ie )
	{
		cblock_main[ph_id].innerHTML = "<iframe id='cblock_"+ph_id+"-shim' src='javascript:false;' scrolling='no' frameborder='0' style='position:absolute; top:0px; left:0px; display:none;'></iframe>" + cblock_main[ph_id].innerHTML;
		cblock_shim[ph_id] = $('cblock_'+ph_id+'-shim');
	}
	cblock_handle[ph_id] = $( 'cblock_'+ph_id+'-handle' );
	cblock_ph[ph_id] = $( 'cblock_'+ph_id+'-ph' );
	cblock_ph_orgh[ph_id] = parseInt(cblock_ph[ph_id].offsetHeight);
	if ( pos=='left' )
	{
		cblocks_left++;
		cblock_position[ph_id] = 'l';
	}
	else
	{
		cblocks_right++;
		cblock_position[ph_id] = 'r';
	}
	cblock_ph_dragging[ph_id] = 0;

	Drag.keeponscreen = false;
	Drag.init( cblock_handle[ph_id], cblock_main[ph_id] );
	cblock_main[ph_id].onDragStart = function(x, y) { CBlock_DragStart( ph_id, x, y ) };
	cblock_main[ph_id].onDragEnd = function(x, y) { CBlock_DragEnd( ph_id, x, y ) };
	cblock_main[ph_id].onDrag = function(x, y) { CBlock_DragMove( ph_id, x, y ) };
}

/*--------------------------------------------*/
// Content Block DragDrop start
/*--------------------------------------------*/

function CBlock_DragStart( ph_id, x, y )
{
	cblock_ph_orgx[ph_id] = x;
	cblock_ph_orgy[ph_id] = y;
	cblock_ph_dragging[ph_id] = 0;

	if ( typeof( window.innerWidth ) == 'number' )
	{
		screen_width  = window.innerWidth;
		screen_height  = window.innerHeight;
	}
	else if ( document.documentElement && ( document.documentElement.clientWidth || document.documentElement.clientHeight ) )
	{
		screen_width  = document.documentElement.clientWidth;
		screen_height  = document.documentElement.clientHeight;
	}
	else if( document.body && ( document.body.clientWidth || document.body.clientHeight ) )
	{
		screen_width  = document.body.clientWidth;
		screen_height  = document.body.clientHeight;
	}
	screen_middle = parseInt( (screen_width / 2) - (cblock_main[ph_id].offsetWidth / 2) );
}

/*--------------------------------------------*/
// Content Block DragMove
/*--------------------------------------------*/

function CBlock_DragMove( ph_id, x, y )
{
	if ( cblock_ph_dragging[ph_id]==0 && ( (x-cblock_ph_orgx[ph_id]) > 3 || (x-cblock_ph_orgx[ph_id]) < -3 || (y-cblock_ph_orgy[ph_id]) > 3 || (y-cblock_ph_orgy[ph_id]) < -3 ) )
	{
		cblock_ph_dragging[ph_id] = 1;

		cblock_ph[ph_id].style.width = cblock_ph[ph_id].offsetWidth + 'px';
		cblock_ph[ph_id].style.height = cblock_ph[ph_id].offsetHeight + 'px';

		if ( is_ie )
		{
			cblock_shim[ph_id].style.width = cblock_main[ph_id].offsetWidth + 'px';
			cblock_shim[ph_id].style.height = ( cblock_main[ph_id].offsetHeight - 15 ) + 'px';
			cblock_shim[ph_id].style.zIndex = -1;
			cblock_shim[ph_id].style.display = "block";
		}
		
		
		cblock_main[ph_id].className += " cblock_dragging";
		
		cblock_main[ph_id].style.width = cblock_main[ph_id].offsetWidth + 'px';
		cblock_main[ph_id].style.height = cblock_main[ph_id].offsetHeight + 'px';
		cblock_main[ph_id].style.left = _get_obj_leftpos(cblock_main[ph_id]) + 'px';
		cblock_main[ph_id].style.top = _get_obj_toppos(cblock_main[ph_id]) + 'px';
		cblock_main[ph_id].style.position = 'absolute';
		cblock_main[ph_id].style.display = 'block';
		cblock_main[ph_id].style.zIndex = 3;

		pos_ind = document.createElement("div");
		pos_ind.style.height = '3px';
		pos_ind.style.width = cblock_main[ph_id].offsetWidth + 'px';
		pos_ind.style.fontSize = '0px';
		pos_ind.style.left = _get_obj_leftpos(cblock_main[ph_id]) + 'px';
		pos_ind.style.top = (_get_obj_toppos(cblock_main[ph_id]) - 9 ) + 'px';
		pos_ind.style.position = 'absolute';
		pos_ind.style.backgroundColor = '#000000';
		pos_ind.style.zIndex = 2;

		if ( cblock_position[ph_id]=='l' )
		{
			left_bar_container.appendChild( pos_ind );
		}
		else
		{
			right_bar_container.appendChild( pos_ind );
		}

		x = _get_obj_leftpos(cblock_main[ph_id]);
		y = _get_obj_toppos(cblock_main[ph_id]);
		ClearBlockSpace( ph_id );
	}

	if ( cblock_ph_dragging[ph_id]==1 )
	{
		if ( x < screen_middle )
		{
			pos = 'l';
		}
		else
		{
			pos = 'r';
		}

		new_id = Get_NewCBlockPosition( ph_id, x, y, pos );
		if ( new_id > 0 )
		{
			pos_ind.style.left =  _get_obj_leftpos(cblock_ph[new_id]) + 'px';
			pos_ind.style.top = ( _get_obj_toppos(cblock_ph[new_id]) - 9 ) + 'px';
			pos_ind.style.width = cblock_main[ph_id].offsetWidth + 'px';
			pos_ind.style.height = '3px';
		}
		else
		{
			bb_id = Get_BottomBlockPosition( ph_id, pos );
			if ( bb_id > 0 )
			{
				pos_ind.style.width = cblock_main[ph_id].offsetWidth + 'px';
				pos_ind.style.height = '3px';
				pos_ind.style.left = _get_obj_leftpos(cblock_ph[bb_id]) + 'px';
				pos_ind.style.top = ( _get_obj_toppos(cblock_ph[bb_id]) + cblock_ph[bb_id].offsetHeight - 9 ) + 'px';
			}
			else
			{
				pos_ind.style.width = '3px';
				if ( pos == 'l' )
				{
					pos_ind.style.left = ipb_var_block_width + 'px';
				}
				else
				{
					pos_ind.style.left = ( screen_width - ipb_var_block_width ) + 'px';
				}
				if ( cblock_position[ph_id] == 'l' )
				{
					pos_ind.style.top = ( _get_obj_toppos( left_bar ) - 9 ) + 'px';
					pos_ind.style.height = ( screen_height - _get_obj_toppos( left_bar ) + 9 ) + 'px';
				}
				else
				{
					pos_ind.style.top = ( _get_obj_toppos( right_bar ) - 9 ) + 'px';
					pos_ind.style.height = ( screen_height - _get_obj_toppos( right_bar ) + 9 ) + 'px';
				}
			}
		}
	}
}

/*--------------------------------------------*/
// Content Block DragDrop end
/*--------------------------------------------*/

function CBlock_DragEnd( ph_id, x, y )
{
	if ( cblock_ph_dragging[ph_id] )
	{
		if ( x < parseInt( (screen_width / 2) - (cblock_main[ph_id].offsetWidth / 2) ) )
		{
			pos = 'l';
			bar = left_bar_container;
		}
		else
		{
			pos = 'r';
			bar = right_bar_container;
		}

		pos_ind.style.display = "none";
		if ( cblock_position[ph_id] == 'l' )
		{
			left_bar_container.removeChild(pos_ind);
			left_bar_container.removeChild(cblock_ph[ph_id]);
			cblocks_left--;
		}
		else
		{
			right_bar_container.removeChild(pos_ind);
			right_bar_container.removeChild(cblock_ph[ph_id]);
			cblocks_right--;
		}

		ph_obj = document.createElement("div");
		ph_obj.style.height = '0px';
		ph_obj.appendChild( cblock_main[ph_id] );

		new_id = Get_NewCBlockPosition( ph_id, x, y, pos );
		if ( new_id > 0 )
		{
			bar.insertBefore( ph_obj, cblock_ph[new_id] );
		}
		else
		{
			bar.appendChild( ph_obj );
		}

		cblock_ph[ph_id] = ph_obj;
		cblock_position[ph_id] = pos;
		if ( pos == 'l' )
		{
			cblocks_left++;
		}
		else
		{
			cblocks_right++;
		}

		if ( is_ie )
		{
			cblock_shim[ph_id].style.display = "none";
		}

		Check_CBlockBars();

		new Ajax.Request(
							ipb_var_base_url+'autocom=blog&req=doblockchange&blogid='+ipb_var_blog_id+'&pos='+pos+'&oldid='+ph_id+'&newid='+new_id,
							{
								method: 'post',
								onSuccess: function( s )
								{
									if ( s.responseText != "ok" )
									{
										alert( ipb.lang['fail_cblock'] );
									}									
								}
							}
		);

		ReturnCBlock( ph_id );
	}
}

/*--------------------------------------------*/
// Remove Content Block
/*--------------------------------------------*/
function remove_cblock( ph_id )
{
	if ( cblock_position[ph_id] == 'l' )
	{
		left_bar_container.removeChild(cblock_ph[ph_id]);
		cblocks_left--;
	}
	else
	{
		right_bar_container.removeChild(cblock_ph[ph_id]);
		cblocks_right--;
	}

	Check_CBlockBars();

	new Ajax.Request(
						ipb_var_base_url+'autocom=blog&req=doremoveblock&blogid='+ipb_var_blog_id+'&cbid='+ph_id,
						{
							method: 'post',
							onSuccess: function( s )
							{
								menu_html = s.responseText;

								if ( menu_html == "error" )
								{
									alert( ipb.lang['fail_cblock'] );
								}
								else
								{
									menu_html = menu_html.replace( /^--IMGITEM--/, img_item );
									mi_obj = document.createElement("div");
									mi_obj.id = 'cbmenu_'+ph_id;
									mi_obj.className = 'popupmenu-item';
									mi_obj.innerHTML = menu_html;
									var cblock_menu = $('cblock-options_menu');
									cblock_menu.appendChild(mi_obj);
								}								
							}
						}
	);
}

/*--------------------------------------------*/
// Delete Content Block
/*--------------------------------------------*/
function delete_cblock( ph_id, auth_key )
{
	if (confirm( ipb_lang_blog_sure_delcblock ))
	{		
		new Ajax.Request(
							ipb_var_base_url+'autocom=blog&req=dodelcblock&blogid='+ipb_var_blog_id+'&cbid='+ph_id+'&auth_key='+auth_key,
							{
								method: 'post',
								onSuccess: function( s )
								{
									var cblock_status = s.responseText;
									if ( cblock_status == "error" )
									{
										alert( ipb.lang['fail_cblock'] );
									}
									else
									{
										if ( cblock_position[ph_id] == 'l' )
										{
											left_bar_container.removeChild(cblock_ph[ph_id]);
											cblocks_left--;
										}
										else
										{
											right_bar_container.removeChild(cblock_ph[ph_id]);
											cblocks_right--;
										}
										Check_CBlockBars();
									}									
								}
							}
						);
	}
}

/*--------------------------------------------*/
// Enable Content Block
/*--------------------------------------------*/
function enable_cblock( ph_id, add )
{
	// Disable link (Bug 9054)
	if( !add )
	{
		link = $('cbmenu_' + ph_id).getElementsByTagName('a');
		if( link )
		{
			link[0].href = "javascript: void(0)";
		}
	}
	
	do_request_function = function()
	{
		if ( ! xmlobj.readystate_ready_and_ok() )
		{
		// Could do a little loading graphic here?
		return;
		}

		var cb_xml = xmlobj.xmlhandler.responseXML.getElementsByTagName("cblock-xml");
		var cb_id = xmlobj.get_element_text_ns( "", 'cb-id', cb_xml[0], 0 );
		var cb_html = xmlobj.get_element_text_ns( "", 'cb-html', cb_xml[0], 0 );
		
		if ( cb_html == "error" )
		{
			alert( ipb.lang['fail_cblock'] );
		}
		else
		{
			menu_action_close();
			ph_obj = document.createElement("div");
			ph_obj.innerHTML = cb_html;
			ph_obj.id = 'cblock_'+cb_id+'-ph';
			right_bar_container.appendChild(ph_obj);
			cblocks_right++;
			Check_CBlockBars();
			cblock_dragdrop_init(cb_id,'right');
			var cblock_menu = $('cblock-options_menu');
			if ( add )
			{
				var cblock_item = $('cbmenu_a'+ph_id);
			}
			else
			{
				var cblock_item = $('cbmenu_'+cb_id);
			}
			cblock_menu.removeChild(cblock_item);
		}
	};

	xmlobj = new ajax_request();
	xmlobj.onreadystatechange( do_request_function );

	if ( add )
	{
		xmlobj.process( ipb_var_base_url+'autocom=blog&req=doaddblock&blogid='+ipb_var_blog_id+'&cbid='+ph_id );
	}
	else
	{
		xmlobj.process( ipb_var_base_url+'autocom=blog&req=doenableblock&blogid='+ipb_var_blog_id+'&cbid='+ph_id );
	}
}

/*--------------------------------------------*/
// Save content block config
/*--------------------------------------------*/

function cblock_save_form( cblock_id, fields )
{
	var save_fields = '';
	for( var i = 0; i < fields.length; i++ )
	{
		save_fields += '&' + 'cblock_config[' + fields[i] + ']' + '=' + $( fields[i] ).value;
	}
		
	new Ajax.Request(
						ipb_var_base_url + 'autocom=blog&req=cblocksaveconfig&blogid=' + ipb_var_blog_id + '&cblock_id=' + cblock_id + save_fields,
						{
							method: 'post',
							onSuccess: function( t )
							{
								if( t.responseText == 'error' )
								{
									alert( ipb.lang['fail_config'] );
								}
								else if( t.responseText == 'refresh' )
								{
									document.location.reload(true);
								}
								else
								{
									$( 'cblock_' + cblock_id ).innerHTML = t.responseText;
									
									var position = ( $( 'cblock_' + cblock_id ).positionedOffset().left > ( document.viewport.getWidth() / 2 ) ) ? 'right' : 'left';
									cblock_dragdrop_init( cblock_id, position );
								}
							},
							onFailure: function()
							{
								alert( ipb.lang['fail_config'] );
							},
							onException: function( o, e)
							{								
								alert( ipb.lang['fail_config'] + "\n" + e );
							}
						}
	);
}

/*--------------------------------------------*/
// Content block config form
/*--------------------------------------------*/

function cblock_show_config_form( cblock_id )
{
	new Ajax.Request(
						ipb_var_base_url + 'autocom=blog&req=cblockshowconfig&blogid=' + ipb_var_blog_id + '&cblock_id=' + cblock_id,
						{
							method: 'post',
							onSuccess: function( t )
							{
								if( t.responseText == "error" )
								{
									alert( ipb.lang['fail_config'] );
								}
								else
								{
									$( 'cblock_' + cblock_id ).parentNode.innerHTML = t.responseText;
									
									var position = ( $( 'cblock_' + cblock_id ).positionedOffset().left > ( document.viewport.getWidth() / 2 ) ) ? 'right' : 'left';									
									cblock_dragdrop_init( cblock_id, position );
								}								
							},
							onFailure: function()
							{
								alert( ipb.lang['fail_config'] );
							},
							onException: function()
							{
								alert( ipb.lang['fail_config'] );
							}
						}
	);
}

/*--------------------------------------------*/
// Clear the Block's space when dragging
/*--------------------------------------------*/

function ClearBlockSpace( ph_id )
{
    clearTimeout( cblock_anim[ph_id] );
	ph_obj = cblock_ph[ph_id];
	if ( cblock_ph_orgh[ph_id] == 0 )
	{
		cblock_ph_orgh[ph_id] = parseInt(ph_obj.offsetHeight);
	}
    new_h = parseInt(ph_obj.offsetHeight) - 5;
    if ( new_h < 0 )
    {
    	new_h = 0;
    }
    ph_obj.style.height = new_h + 'px';
	if ( new_h > 0 )
	{
		cblock_anim[ph_id] = setTimeout('ClearBlockSpace( '+ph_id+' )', 10);
	}
}

/*--------------------------------------------*/
// Return the CBlock to its (new) position
/*--------------------------------------------*/

function ReturnCBlock( ph_id )
{
	clearTimeout( cblock_anim[ph_id] );

	ph_obj = cblock_ph[ph_id];
    new_h = parseInt(ph_obj.offsetHeight) + 10;

    if ( new_h > cblock_ph_orgh[ph_id] )
    {
    	new_h = cblock_ph_orgh[ph_id];
    }
    ph_obj.style.height = new_h + 'px';

    orgx = _get_obj_leftpos(cblock_ph[ph_id]);
    orgy = _get_obj_toppos(cblock_ph[ph_id]);
    curx = parseInt( cblock_main[ph_id].style.left );
    cury = parseInt( cblock_main[ph_id].style.top );

    if ( curx < orgx )
    {
    	curx += 25;
    	if ( curx > orgx ) { curx = orgx; }
    }
	else if ( curx > orgx )
	{
		curx -= 25;
    	if ( curx < orgx ) { curx = orgx; }
	}

    if ( cury < orgy )
    {
    	cury += 25;
    	if ( cury > orgy ) { cury = orgy; }
    }
	else if ( cury > orgy )
	{
		cury -= 25;
    	if ( cury < orgy ) { cury = orgy; }
	}
	cblock_main[ph_id].style.left = curx + 'px';
	cblock_main[ph_id].style.top = cury + 'px';
	
	
	cblock_main[ph_id].className = cblock_main[ph_id].className.replace(/cblock_dragging/, '');

	if ( new_h < cblock_ph_orgh[ph_id] || orgx != curx || orgy != cury )
	{
		cblock_anim[ph_id] = setTimeout('ReturnCBlock( '+ph_id+' )', 5);
	}
	else
	{
		cblock_main[ph_id].style.position = 'static';
	}
}

function Get_NewCBlockPosition( ph_id, x, y, pos )
{
	new_y = 0;
	new_id = 0;
	for (var cb_id in cblock_ph)
	{
		if ( cb_id != ph_id && cblock_position[cb_id]==pos )
		{
			ph_y = _get_obj_toppos(cblock_ph[cb_id]);
			if ( y < ph_y && (new_y > ph_y || new_id==0) )
			{
				new_y = ph_y;
				new_id = cb_id;
			}
		}
	}
	return new_id;
}

function Get_BottomBlockPosition( ph_id, pos )
{
	new_y = 0;
	bb_id = 0;
	for (var cb_id in cblock_ph)
	{
		if ( cb_id != ph_id && cblock_position[cb_id]==pos )
		{
			ph_y = _get_obj_toppos(cblock_ph[cb_id]);
			if ( ph_y > new_y )
			{
				new_y = ph_y;
				bb_id = cb_id;
			}
		}
	}
	return bb_id;
}

function Check_CBlockBars()
{
	if ( cblocks_left > 0 && left_bar.style.display == "none")
	{
		left_bar.style.display="";
	}
	if ( cblocks_left == 0 && left_bar.style.display == "")
	{
		left_bar.style.display="none";
	}
	if ( cblocks_right >= 0 && right_bar.style.display == "none")
	{
		right_bar.style.display="";
	}
	if ( cblocks_right == 0 && right_bar.style.display == "")
	{
		right_bar.style.display="none";
	}
}

function build_postarray( postform )
{
	var post_array = new Array();
	var fieldvalue = "";
	var field;
	var obj_form = $( postform );
	for (var i=0;i<obj_form.length;i++)
	{
		field = obj_form.elements[i];
		if ( field.type == 'checkbox' )
		{
			if ( field.checked )
			{
				fieldvalue = field.value;
			}
			else
			{
				fieldvalue = '';
			}
		}
		else
		{
			fieldvalue = field.value;
		}
		post_array[field.name] = fieldvalue;
	}
	return post_array;
}

/*-------------------------------------------------------------------------*/
// INIT Post GD Image
/*-------------------------------------------------------------------------*/

function init_bloggd_image()
{
	var reg_img = $('gd-antispam');

	try
	{
		reg_img.style.cursor = 'pointer';
	}
	catch(e)
	{
		reg_img.style.cursor = 'hand';
	};

	reg_img._ready  = 1;

	reg_img.onclick = do_changeblog_img;
};

function do_changeblog_img()
{
	var rc 	    = '';
	var req	    = 'captchaimage';
	var reg_img = $('gd-antispam');

	var qparts  = reg_img.src.split("?");

	if ( ! reg_img._ready )
	{
		return false;
	}

	if ( qparts.length > 1 )
	{
		var qvars = qparts[1].split("&");

		for ( var i=0; (i < qvars.length); i++ )
		{
			var qparts = qvars[i].split("=");

			if ( qparts[0] == 'rc' )
			{
				rc = qparts[1];
			};

			if ( qparts[0] == 'req' )
			{
				req = qparts[1];
			};
		};
	};

	var url = ipb_var_base_url+'act=xmlout&do=change-gd-img&img='+rc;

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

		var html = xmlobj.xmlhandler.responseText;

		reg_img.src = ipb_var_base_url+'autocom=blog&req='+req+'&rc='+html;

		var reg_field  	= $('regid');
		reg_field.value = html;

		reg_img._ready = 1;
	};

	//----------------------------------
	// LOAD XML
	//----------------------------------

	reg_img._ready = 0;

	xmlobj = new ajax_request();
	xmlobj.onreadystatechange( do_request_function );
	xmlobj.process( url );
}

//----------------------------------
// Convert HTML entities
//----------------------------------

function convert_html_entities( variable )
{
	var matches = variable.match(/&#\d+;?/g);

	if (matches != null)
	{
		for(var i = 0; i < matches.length; i++)
		{
			// line wraps here -- be careful copy/pasting
			var replacement = String.fromCharCode((matches[i]).replace(/\D/g,""));

			variable = variable.replace(/&#\d+;?/,replacement);
		}
	}
	return variable;
}
