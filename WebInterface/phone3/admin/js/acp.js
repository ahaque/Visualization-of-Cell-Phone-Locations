
var IPBACP = Class.create({
	
	autocompleteWrap: new Template("<ul id='#{id}' class='ipbmenu_content' style='width: 250px;'></ul>"),
	autocompleteItem: new Template("<li id='#{id}'>#{itemvalue}</li>"),
	autocompleteUrl: '',
	
	
	initialize: function()
	{
		// Tell everyone we are ready
		document.observe("dom:loaded", function(){
			
			Ajax.Responders.register({
			  onLoading: function() {
			    if( !$('ajax_loading') )
				{
					if( !ipb.templates['ajax_loading'] ){ return; }
					$('ipboard_body').insert( ipb.templates['ajax_loading'] );
				}
				
				var effect = new Effect.Appear( $('ajax_loading'), { duration: 0.2 } );
			  },
			  onComplete: function() {
			
				if( !$('ajax_loading') ){ return; }
			    var effect = new Effect.Fade( $('ajax_loading'), { duration: 0.2 } );
			  },
			  onSuccess: function(t){
				if( t.responseText == 'logout' )
				{
					alert( "Sorry, your session has expired. Click OK to log back in." );
					window.location.href = ipb.vars['base_url'];
				}
			  }
			});
			
			this.autocompleteUrl = ipb.vars['front_url'] + 'app=core&module=ajax&section=findnames&do=get-member-names&secure_key=' + ipb.vars['md5_hash'] + '&name=';
		}.bind(this));
	},
	
	confirmDelete: function( url, msg )
	{
		url = url.replace( /&amp;/g, '&' );
		
		if ( ! msg )
		{
			msg = ipb.lang['ok_to_delete'];
		}
		
		if ( confirm( msg ) )
		{
			window.location.href = url;
		}
		else
		{
			return false;
		}
	},
	
	openWindow: function( url, width, height, name )
	{
		if ( ! name )
		{
			var mydate = new Date();
			name = mydate.getTime();
		}
		
		var Win = window.open( url, name, 'width='+width+',height='+height + ',resizable=1,scrollbars=1,location=no,directories=no,status=no,menubar=no,toolbar=no');
		
		return false;
	},
	
	redirect: function( url, full )
	{
		url = url.replace( /&amp;/g, '&' );
		
		if ( ! full )
		{
			url = ipb.vars['base_url'] + url;
		}
		
		window.location.href = url;
	},
	
	// Todo: language abstraction
	pageJump: function( url_bit, total_posts, per_page )
	{
		pages = 1;
		cur_st = ipb.vars['st'];
		cur_page  = 1;
		
		if ( total_posts % per_page == 0 )
		{
			pages = total_posts / per_page;
		}
		else
		{
			pages = Math.ceil( total_posts / per_page );
		}
		
		msg = ipb.lang['page_multijump'] + pages;
		
		if ( cur_st > 0 )
		{
			cur_page = cur_st / per_page; cur_page = cur_page -1;
		}
		
		show_page = 1;
		
		if ( cur_page < pages )
		{
			show_page = cur_page + 1;
		}
		
		if ( cur_page >= pages )
		{
			show_page = cur_page - 1;
		}
		else
		{
			show_page = cur_page + 1;
		}
		
		userPage = prompt( msg, show_page );
		
		if ( userPage > 0  )
		{
			if ( userPage < 1 )     {    userPage = 1;  }
			if ( userPage > pages ) { userPage = pages; }
			if ( userPage == 1 )    {     start = 0;    }
			else { start = (userPage - 1) * per_page; }

			window.location = url_bit + "&st=" + start;
		}
	},
	
	ajaxRefresh: function( url, text, addtotext )
	{
		new Ajax.Request(
							url + '&__notabsave=1',
							{
								method: 'post',
								onSuccess: function( t )
								{
									var html = t.responseText;
			
									eval( html );
								},
								onFailure: function()
								{
								},
								onException: function()
								{
								}
							}
				);
		
		if ( text )
		{
			// Put it to the top
			if ( addtotext )
			{
				$('refreshbox').innerHTML = text + '<br />' + $('refreshbox').innerHTML;
			}
			else
			{
				$('refreshbox').innerHTML = text;
			}
		}
	},
	
	location_jump: function( url, full )
	{
		url=url.replace( /&amp;/g,'&');
		
		if(full){
			window.location.href=url;
		} else {
			window.location.href=ipb.vars['base_url']+url;
		}
	}
});