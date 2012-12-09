function blog_toggle_bid( bid )
{
	saved = new Array();
	clean = new Array();
	add   = 1;
	//-----------------------------------
	// Get form info
	//-----------------------------------
	tmp = document.modform.selectedbids.value;
	saved = tmp.split(",");
	//-----------------------------------
	// Remove bit if exists
	//-----------------------------------
	for( i = 0 ; i < saved.length; i++ )
	{
		if ( saved[i] != "" )
		{
			if ( saved[i] == bid )
			{
				 add = 0;
			}
			else
			{
				clean[clean.length] = saved[i];
			}
		}
	}
	//-----------------------------------
	// Add?
	//-----------------------------------
	if ( add )
	{
		clean[ clean.length ] = bid;
		eval("document.img"+bid+".src=selectedbutton");
	}
	else
	{
		eval("document.img"+bid+".src=unselectedbutton");
	}
	newvalue = clean.join(',');
	my_setcookie( 'modbids', newvalue, 0 );
	document.modform.selectedbids.value = newvalue;
	newcount = stacksize(clean);
	document.modform.gobutton.value = lang_gobutton + '(' + newcount + ')';
	return false;
}

function do_sort( sort_key, sort_order )
{
	document.sortform.sort_key.value = sort_key;
	document.sortform.sort_order.value = sort_order;
	document.sortform.submit();
}


function blog_send_marker_update( blog_id )
{
	//----------------------------------
	// Get current image...
	//----------------------------------

	try
	{
		var imgsrc = document.getElementById( 'b-'+blog_id );
	}
	catch(e){}

	var text_return = 0;

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
		// Remove image
		//----------------------------------
		
		try {
			imgsrc.parentNode.removeChild( imgsrc );
		}
		catch(e){}
		
		text_return = xmlobj.xmlhandler.responseText;
	};

	xmlobj = new ajax_request();
	xmlobj.onreadystatechange( do_request_function );

	xmlobj.process( ipb_var_base_url + 'autocom=blog&req=domarkblog&blogid='+blog_id );

	if ( text_return == 1 )
	{
		return false;
	}
}