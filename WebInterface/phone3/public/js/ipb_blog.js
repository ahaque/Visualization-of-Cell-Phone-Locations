//------------------------------------------
// IP.Blog v1.2
// Blog JS File
// (c) 2005 Invision Power Services, Inc.
//
// http://www.
//------------------------------------------

	function delete_entry(theURL)
	{
		if (confirm( "{$this->lang->words['sure_delentry']}" ))
		{
			window.location.href=theURL;
		}
		else
		{
			alert ( "{$this->lang->words['del_no_action']}" );
		}
	}
	function delete_comment(theURL)
	{
		if (confirm( "{$this->lang->words['sure_delcomment']}" ))
		{
			window.location.href=theURL;
		}
		else
		{
			alert ( "{$this->lang->words['del_no_action']}" );
		}
	}
	function sendtrackback_pop(eid)
	{
		ShowHide("modmenuopen_"+eid, "modmenuclosed_"+eid);
		window.open("{$this->settings['board_url']}/index.{$this->settings['php_ext']}?app=blog&module=sendtrackback&eid="+eid+"&s={$this->member->session_id}","SendTrackback","width=600,height=300,resizable=yes,scrollbars=yes");
	}
	function permalink_to_entry(eid){
		temp = prompt( "{$this->lang->words['permalink_prompt']}", "{$this->settings['base_url']}app=blog&blogid={$blog['blog_id']}&showentry="+eid );
		return false;
	}
	function emo_pop( formobj )
	{
		emoticon = function( ecode, eobj, eurl ){
			document.getElementById( formobj ).value += ' ' + ecode + ' ';
		}
		window.open("{$this->settings['board_url']}/index.{$this->settings['php_ext']}?act=legends&do=emoticons&s={$this->member->session_id}","Legends","width=250,height=500,resizable=yes,scrollbars=yes");
	}
	function bbc_pop()
	{
		window.open("{$this->settings['board_url']}/index.{$this->settings['php_ext']}?act=legends&do=bbcode&s={$this->member->session_id}","Legends","width=700,height=500,resizable=yes,scrollbars=yes");
	}
	
/*-------------------------------------------------------------------------*/
// Pop up Settings window
/*-------------------------------------------------------------------------*/

function blogsettings_pop()
{
	var not_loaded_yet = 0;

	if ( use_enhanced_js )
	{
		try
		{
			xml_myblogsettings_init();
			not_loaded_yet = 1;
		}
		catch( e )
		{
			not_loaded_yet = 0;
		}
	}

	if ( ! not_loaded_yet )
	{
		ipb_var_base_url = ipb_var_base_url.replace( '&amp;', '&' );
		window.location = ipb_var_base_url + 'autocom=blog&req=ucp_main';
	}
}

/*-------------------------------------------------------------------------*/
// Blog rating
/*-------------------------------------------------------------------------*/
function blog_rate()
{
	/**
	* Settings
	*/
	this.settings = {
						'allow_rating'           : 0,
						'default_rating'         : 3,
						'img_star_on'            : 'star_filled.gif',
						'img_star_selected'      : 'star_selected.gif',
						'img_star_off'           : 'star_empty.gif',
						'img_main_star_0'        : 'rating_0.gif',
						'img_main_star_1'        : 'rating_1.gif',
						'img_main_star_2'        : 'rating_2.gif',
						'img_main_star_3'        : 'rating_3.gif',
						'img_main_star_4'        : 'rating_4.gif',
						'img_main_star_5'        : 'rating_5.gif',
						'img_base_url'           : '',
						'div_rating_wrapper'     : 'blog-rating-wrapper',
						'text_rating_image'      : 'blog-rating-img-',
						'blog-rating-img-main'   : 'blog-rating-img-main',
						'blog-rating-my-rating'  : 'blog-rating-my-rating',
						'blog-rating-hits'       : 'blog-rating-hits'
	 				};

	this.languages = {
						'img_alt_rate'       : '',
						'rate_me'            : ''
					 };

	/**
	* INIT rating images
	*/
	this.init_rating_images = function()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		var html = '';

		//-----------------------------------------
		// Can rate this member?
		//-----------------------------------------

		if ( ! this.settings[ 'allow_rating' ] )
		{
			return false;
		}

		//-----------------------------------------
		// Still here? OK...
		//-----------------------------------------

		for( var i = 1 ; i <= 5 ; i++ )
		{
			var _onmouseover = '';
			var _onmouseout  = '';
			var _onclick     = '';
			var _title       = '';

			_onmouseover = ' onmouseover="this.style.cursor=\'pointer\'; blog_rate.show_rating_images(' + i + ', 0)"';
			_onmouseout  = ' onmouseout="blog_rate.show_rating_images(-1, 1)"';
			_onclick     = ' onclick="blog_rate.send_rating(' + i + ')"';
			_title       = this.languages['img_alt_rate'];

			html += "<img style='vertical-align:top' src='" + this.settings['img_base_url'] + '/' + this.settings['img_star_off'] + "' " + _onmouseover + _onmouseout + _onclick + "id='" + this.settings['text_rating_image'] + i + "' alt='-' title='" + _title + "' />";
		}

		document.getElementById( this.settings['div_rating_wrapper'] ).innerHTML = this.languages['rate_me'] + ' ' + html;

		//-----------------------------------------
		// Now set the image...
		//-----------------------------------------

		this.show_rating_images( this.settings['default_rating'], 1 );
	};

	/**
	* Send rating..
	*/
	this.send_rating = function( rating )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		rating = rating ? rating : 0;

		//-----------------------------------------
		// Got a rating?
		//-----------------------------------------

		if ( rating )
		{
			//----------------------------------
			// INIT
			//----------------------------------

			var url = ipb_var_base_url+'autocom=blog&req=dorateblog&blogid='+ipb_var_blog_id+'&rating='+rating;

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
					xmlobj.show_loading( '' );
					return;
				}

				xmlobj.hide_loading();

				//----------------------------------
				// INIT
				//----------------------------------

				var html = xmlobj.xmlhandler.responseText;

				if ( html == 'no_permission' )
				{
					alert( js_error_no_permission );
				}
				else if ( html != 'error' )
				{
					var _result    = html.split(',');
					var _new_value = _result[0];
					var _new_hits  = _result[1];
					var _new_stars = _result[2];
					var _type      = _result[3];

					//-----------------------------------------
					// Now set the image...
					//-----------------------------------------

					blog_rate.settings['default_rating'] = parseInt( _new_stars );

					blog_rate.show_rating_images( blog_rate.settings['default_rating'], 1 );

					menu_action_close();

					//-----------------------------------------
					// Update counts
					//-----------------------------------------

					document.getElementById('blog-rating-hits').innerHTML      = _new_hits;
					document.getElementById('blog-rating-my-rating').innerHTML = rating;

					show_inline_messages_instant( 'rating_updated' );
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
	};

	/**
	* Show rating images..
	*/
	this.show_rating_images = function( rating, restore_default )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		rating   = restore_default ? this.settings['default_rating'] : parseInt( rating );
		var star = restore_default ? this.settings['img_star_on'] : this.settings['img_star_selected'];

		//-----------------------------------------
		// Set to 0
		//-----------------------------------------

		for( var i = 1 ; i <= 5 ; i++ )
		{
			var _img = document.getElementById( this.settings['text_rating_image'] + i );
			_img.src = this.settings['img_base_url'] + '/' + this.settings['img_star_off'];
		}

		//-----------------------------------------
		// Show ones coloured...
		//-----------------------------------------

		for( var i = 1 ; i <= rating ; i++ )
		{
			var _img = document.getElementById( this.settings['text_rating_image'] + i );
			_img.src = this.settings['img_base_url'] + '/' + star;
		}

		//-----------------------------------------
		// Set main image
		//-----------------------------------------

		document.getElementById( this.settings['blog-rating-img-main'] ).src = this.settings['img_base_url'] + '/' + this.settings['img_main_star_' + rating ];
	};

};


var size_loaded 	= 0;
var cp1;

function show_theme_editor()
{
	var size_main    = document.getElementById( 'theme_form' );
	var size_drag    = document.getElementById( 'theme-drag' );
	var size_content = document.getElementById( 'theme-content' );
	
	//----------------------------------
	// Not loaded? INIT
	//----------------------------------
	
	if ( ! size_loaded )
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
		
		var divheight = parseInt( size_main.style.Height );
		var divwidth  = parseInt( size_main.style.Width );
		
		divheight = divheight ? divheight : 400;
		divwidth  = divwidth  ? divwidth  : 400;
		
		//----------------------------------
		// Got it stored in a cookie?
		//----------------------------------
		
		var divxy = my_getcookie( 'ipb-size-div' );
		var co_ords;
		
		if ( divxy && divxy != null )
		{
			co_ords = divxy.split( ',' );
		
			//----------------------------------
			// Got co-ords?
			//----------------------------------
			
			if ( co_ords.length )
			{
				var final_width  = co_ords[0];
				var final_height = co_ords[1];
				
				if ( co_ords[0] > my_width )
				{
					//----------------------------------
					// Keep it on screen
					//----------------------------------
					
					final_width = my_width - divwidth;
				}
				
				if ( co_ords[1] > my_height )
				{
					//----------------------------------
					// Keep it on screen
					//----------------------------------
					
					final_height = my_height - divheight;
				}
				
				size_main.style.left = final_width  + 'px';
				size_main.style.top  = final_height + 'px';
			}
		}
		else
		{
			//----------------------------------
			// Reposition DIV roughly centered
			//----------------------------------
			
			size_main.style.left = my_width  / 2  - (divwidth / 2)  + 'px';
			size_main.style.top  = my_height / 2 - (divheight / 2 ) + 'px';
		}
		
		Drag.cookiename = 'ipb-size-div';
		Drag.init( size_drag, size_main );
		size_main.onDrag = function( x,y ) { colorpickerRepos(); };
		
		size_loaded = 1;
	}
	
  	size_main.style.position = 'absolute';
	size_main.style.display  = 'block';
	size_main.style.zIndex   = 99;
	
	if ( is_ie )
	{
		var html = size_content.innerHTML;
		
		html = "<iframe id='size-shim' src='" + ipb_var_image_url + "/iframe.html' class='iframshim' scrolling='no' frameborder='0' style='position:absolute; top:0px; left:0px; right:0px; display: none;'></iframe>" + html;
		
		size_content.innerHTML = html;
	}
	
	//----------------------------------
	// Stop IE showing select boxes over
	// floating div [ 1 ]
	//----------------------------------
			
	if ( is_ie )
	{
		var drag_html		= size_drag.innerHTML;
		var main_drag_html 	= "<iframe id='size-shim-two' src='" + ipb_var_image_url + "/iframe.html' class='iframshim' scrolling='no' frameborder='0' style='position:absolute; top:0px; left:0px; right:0px; display: none;'></iframe>" + drag_html;
		
		size_drag.innerHTML = main_drag_html;
	}		
	
	//----------------------------------
	// Stop IE showing select boxes over
	// floating div [ 2 ]
	//----------------------------------
	
	if ( is_ie )
	{
		size_shim               = document.getElementById('size-shim');
		size_shim.style.width   = size_content.offsetWidth;
		size_shim.style.height  = size_content.offsetHeight;
		size_shim.style.zIndex  = size_content.style.zIndex - 1;
		size_shim.style.top     = size_content.style.top;
		size_shim.style.left    = size_content.style.left;
		size_shim.style.display = "block";

		size_shim_d               = document.getElementById('size-shim-two');
		size_shim_d.style.width   = size_drag.offsetWidth;
		size_shim_d.style.height  = size_drag.offsetHeight;
		size_shim_d.style.zIndex  = size_drag.style.zIndex - 1;
		size_shim_d.style.top     = size_drag.style.top;
		size_shim_d.style.left    = size_drag.style.left;
		size_shim_d.style.display = "block";			
	}

	if( typeof(cp1) == 'undefined' )
	{
		cp1 = new Refresh.Web.ColorPicker('cp1',{startHex: 'ffcc00', startMode:'h', clientFilesPath:clientImagePath});
		
		setTimeout( "colorpickerRepos()", 10 );
	}
	else
	{
		colorpickerRepos();
	}
		
	
	return false;
};

function colorpickerRepos()
{
	cp1.show();
	cp1.updateMapVisuals();
	cp1.updateSliderVisuals();
};

function colorpickerHide()
{
	cp1.hide();
	
	toggleview("theme_form"); 
	return false;
};


function theme_preview()
{
	for( var i=0; i < document.styleSheets.length; i++ )
	{
		if( document.styleSheets[ i ].title == 'Theme' )
		{
			document.styleSheets[ i ].disabled = true;
		}
	}
	
	var style = document.createElement( 'style' );
	style.type = 'text/css';
	
	var content = document.getElementById( 'themeContent' ).value;
	
	if( ! content )
	{
		return false;
	}
	
	var h = document.getElementsByTagName("head");
	h[0].appendChild( style );
	
	try
	{
    	style.styleSheet.cssText = content;
  	}
  	catch(e)
  	{
  		try
  		{
    		style.appendChild( document.createTextNode( content ) );
    		style.innerHTML=content;
  		}
  		catch(e){}
  	}

	return false;
};


function save_theme()
{
	var url    			= ipb_var_base_url + 'autocom=blog&blogid=' + ipb_var_blog_id + '&req=mythemexml';
	var update_div 		= document.getElementById( 'update_div' );
	var fields			= new Array();
	fields['content']	= document.getElementById( 'themeContent' ).value;
	
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
		
		var returned = xmlobj.xmlhandler.responseText;

		update_div.innerHTML = returned;
		update_div.style.display = '';
		colorpickerRepos();
	};
	
	//----------------------------------
	// LOAD XML
	//----------------------------------
	
	xmlobj = new ajax_request();
	xmlobj.onreadystatechange( do_request_function );
	var xmlreturn = xmlobj.process( url, 'POST', xmlobj.format_for_post(fields) );
	
	return false;
};


function blog_reset_theme()
{
	if( confirm( ipb_lang_theme_reset_confirm ) )
	{
		window.location = ipb_var_blog_url + 'changeTheme=0';
	}
	
	return false;
};