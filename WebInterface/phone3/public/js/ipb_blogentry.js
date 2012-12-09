//------------------------------------------
// IP.Blog <#VERSION#>
// Entry JS File
// (c) 2006 Invision Power Services, Inc.
//
// http://www.
//------------------------------------------

var comment_cache		= new Array();
var ajax_loaded			= 1;
var cignore_cache		= new Array();

function ajax_std_window_resize(pix,cid)
{
	var box=document.getElementById('comment-edit-'+cid);
	var h1=parseInt(box.style.height)? parseInt(box.style.height): 300;
	var h2=h1+pix;
	if(h2>0)
	{
		box.style.height=h2+"px";
	}
	return false;
}

/*--------------------------------------------*/
// Ajax: Use full editor
/*--------------------------------------------*/

function ajax_fulleditor_for_edit( entry_id, comment_id )
{
	if ( entry_id && comment_id )
	{
		var _form = document.getElementById( 'quick-edit-form-' + comment_id );
		var _url  = ipb_var_blog_url + 'req=editcomment&eid=' + entry_id + '&cid=' + comment_id + '&_from=quickedit';
		
		_form.action = _url;
		_form.method = 'POST';
		
		_form.submit();
		
		xmlobj.show_loading();
		
		return false;
	}
	else
	{
		return false;
	}
}

/*--------------------------------------------*/
// Ajax: Cancel for edit
/*--------------------------------------------*/

function ajax_cancel_for_edit( comment_id )
{
	if ( comment_cache[ comment_id ] != "" )
	{
		document.getElementById( 'comment-'+comment_id ).innerHTML = comment_cache[ comment_id ];
	}
	
	return false;
}


/*--------------------------------------------*/
// Ajax: Save for edit
/*--------------------------------------------*/

function ajax_save_for_edit( entry_id, comment_id )
{
	//----------------------------------
	// INIT
	//----------------------------------
	
	var url    = ipb_var_blog_url+'req=doxmledit&eid='+entry_id+'&cid='+comment_id;
	var fields = new Array();

	//----------------------------------
	// Populate fields
	//----------------------------------
	
	fields['md5check']         = ipb_md5_check;
	fields['eid']			   = entry_id;
	fields['cid']			   = comment_id;
	fields['req']			   = 'doxmledit';
	fields['Post']			   = document.getElementById( comment_id + '_textarea' ).value;
	fields['std_used']         = 1;  // Make sure STD BBCode parser is used
	
	//----------------------------------
	// Is there a post?
	//----------------------------------
	
	var post_check = fields['Post'];
	
	if ( post_check.replace( /^\s*|\s*$/g, "" ) == "" )
	{
		alert( js_no_empty_post );
		return false;
	}
	
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
			xmlobj.show_loading();
			return;
		}
		
		xmlobj.hide_loading();
		
		//----------------------------------
		// INIT
		//----------------------------------
		
		var html = xmlobj.xmlhandler.responseText;
		
		//-----------------------------------------
		// Execute JS...
		//-----------------------------------------
		
		if ( html == 'nopermission' )
		{
			alert( js_error_no_permission );
			document.getElementById( 'comment-'+comment_id ).innerHTML = comment_cache[ comment_id ];
		}
		else if ( html != 'error' )
		{
			document.getElementById( 'comment-'+comment_id ).innerHTML = html;
			xmlobj.execute_javascript( html );
			fix_linked_image_sizes();
		}
	};
	
	//----------------------------------
	// LOAD XML
	//----------------------------------
	
	xmlobj = new ajax_request();
	xmlobj.onreadystatechange( do_request_function );
	var xmlreturn = xmlobj.process( url, 'POST', xmlobj.format_for_post(fields) );
	
	comment_cache[ comment_id ] = '';
	
	return false;
}


/*--------------------------------------------*/
// Ajax: Prep for edit
/*--------------------------------------------*/

function ajax_prep_for_edit( entry_id, comment_id, event )
{
	//----------------------------------
	// Cancel bubble (Prevent IE scroll...)
	//----------------------------------
	
	global_cancel_bubble( event, true );
	
	var comment_main_obj = document.getElementById( 'comment-main-' + comment_id );
	var comment_box_top  = _get_obj_toppos( comment_main_obj );
	
	//----------------------------------
	// INIT
	//----------------------------------
	
	var url = ipb_var_blog_url+'req=xmledit&eid='+entry_id+'&cid='+comment_id;
	comment_cache[ comment_id ] = document.getElementById( 'comment-'+comment_id ).innerHTML;

	//----------------------------------
	// Attempt to close open menus
	//----------------------------------
	
	try
	{
		menu_action_close();
	}
	catch(e)
	{
		//alert( e );
	}
	
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
			xmlobj.show_loading();
			return;
		}
		
		xmlobj.hide_loading();
		
		//----------------------------------
		// INIT
		//----------------------------------
		
		var html = xmlobj.xmlhandler.responseText;
		
		if ( html == 'nopermission' )
		{
			alert( js_error_no_permission );
		}
		else if ( html != 'error' )
		{
			if ( comment_box_top )
			{
				scroll( 0, comment_box_top - 30 );
			}
			
			document.getElementById( 'comment-main-' + comment_id ).innerHTML = html;

			//-----------------------------------------
			// Set up new editor
			//-----------------------------------------
			
			IPS_Lite_Editor[ comment_id ] = new ips_text_editor_lite( comment_id );
			IPS_Lite_Editor[ comment_id ].init();
		}
	};
	
	//----------------------------------
	// LOAD XML
	//----------------------------------
	
	xmlobj = new ajax_request();
	xmlobj.onreadystatechange( do_request_function );
	
	xmlobj.process( url );
	
	return false;
}

/*--------------------------------------------*/
// Add multi-quote
/*--------------------------------------------*/

function multiquote_add(id)
{
	saved=new Array();
	clean=new Array();
	add=1;
	if(tmp=my_getcookie('mqcids'))
	{
		saved=tmp.split(",");
	}
	for(i=0;i<saved.length;i++)
	{
		if(saved[i] !="")
		{
			if(saved[i]==id)
			{
				add=0;
			}
			else
			{
				clean[clean.length]=saved[i];
			}
		}
	}
	if(add)
	{
		clean[ clean.length ]=id;
		eval("document.mad_"+id+".src=removequotebutton");
	}
	else
	{
		eval(" document.mad_"+id+".src=addquotebutton");
	}
	my_setcookie('mqcids',clean.join(','),0);
	return false;
}

/*--------------------------------------------*/
// Show hidden comment
/*--------------------------------------------*/

function comment_show_ignored_comment( cid )
{
	try
	{
		// Set up
		var comment_main   = document.getElementById( 'comment-main-'   + cid );
		var comment_ignore = document.getElementById( 'comment-ignore-' + cid );
		
		// Show it
		comment_main.innerHTML = cignore_cache[ cid ];
	}
	catch( e )
	{
		//alert( e );
	}
	
	return false;
}


/*--------------------------------------------*/
// Initiate topic hide
/*--------------------------------------------*/

function comment_init_ignored_comment( cid )
{
	try
	{
		// Set up
		var comment_main   = document.getElementById( 'comment-main-'   + cid );
		var comment_ignore = document.getElementById( 'comment-ignore-' + cid );
		
		// Cache it...
		cignore_cache[ cid ] = comment_main.innerHTML;
		
		// Display "ignored" msg
		comment_main.innerHTML = comment_ignore.innerHTML;
	}
	catch( e )
	{
		//alert( e );
	}
}
