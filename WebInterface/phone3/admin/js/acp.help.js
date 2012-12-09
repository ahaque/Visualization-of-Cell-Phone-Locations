//------------------------------------------------------------------------------
// IPS JS: ACP Help
// (c) 2008 Invision Power Services, Inc.
// http://www.
// Brandon Farber - 16th December
//------------------------------------------------------------------------------

ACPHelp = {
	hasBeenLoaded:	false,
	
	/*------------------------------*/
	/* Constructor 					*/
	/*------------------------------*/
	init: function()
	{
		Debug.write("Initializing acp.help.js");
		
		document.observe("dom:loaded", function(){
			
			if( $('help_link') )
			{
				$('help_link').observe('click', ACPHelp.toggleHelp);
			}
			
			if( $('help_nw') )
			{
				$('help_nw').observe('click', ACPHelp.newWindow);
			}
			
			if( ipb.Cookie.get('acp_help') == 'open' )
			{
				ACPHelp.toggleHelp();
			}
			
			// Set up events on settings
			ipb.delegate.register('.acp-help-settings', ACPHelp.loadSettingGroup);
			ipb.delegate.register('.triggerPopup', ACPHelp.triggerDiv);
			
			/*if( $('help_link') )
			{
				$('help_link').observe( 'click', ACPHelp.loadHelp );
			}
			else
			{
				return false;
			}
			
			try
			{
				$('acp-help-contents').innerHTML = JSON_acp_help;
			}
			catch(error)
			{
				// Shouldn't hit this...
				Debug.write(error);
				$$('.help-box').each( function(elem){ elem.hide(); });
			}
			
			//-----------------------------------------
			// Temporarily - this is just here so the links
			// work when we have the box auto-displayed
			//-----------------------------------------
			
			$$('.triggerPopup').each( function(elem){
				elem.observe('click', ACPHelp.triggerPopup );
			});
			
			$$('.acp-help-settings').each( function(elem){
				elem.observe('click', ACPHelp.loadSettingGroup );
			});*/
		});
	},
	
	newWindow: function(e)
	{
		window.open("http://acpdocs./showfull.php?pageKey=" + acpHelp['pageKey'] + "&domain=" + acpHelp['domain'] + "&version=" + acpHelp['version'], 'fullhelp', 'status=0,toolbar=0,location=0,scrollbars=1,width=600,height=700');
		
		return;
	},
	
	triggerDiv: function( e, elem )
	{
		Event.stop(e);
		
		try {
			var id = $(elem).id.replace('trigger_', '');
			var help_elem = $(id);
			if( !$(help_elem) ){ return; }
		} catch( err ){ }
		
		Effect.toggle( $(help_elem), 'blind', { duration: 0.3 } );		
	},
	
	loadJavascript: function()
	{
		var url = "http://acpdocs./retrieve.php?pageKey=" + acpHelp['pageKey'] + "&domain=" + acpHelp['domain'] + "&version=" + acpHelp['version'];
		var js = new Element( 'script', { 'type': 'text/javascript', 'src': url } );
		
		$$('head')[0].insert( js );
		
		ACPHelp.pageLoaded = true;
	},
	
	toggleHelp: function(e)
	{
		if( $('main_help').visible() )
		{
			//$('main_help').hide();
			new Effect.BlindUp( $('main_help'), { duration: 0.3 } );
			$('help_link').removeClassName('showing').update( acpHelp['open_help'] );
			$('help_nw').hide();
			ipb.Cookie.set('acp_help', 'closed', 1);
		}
		else
		{
			// Load up the page
			if( !ACPHelp.pageLoaded )
			{
				ACPHelp.loadJavascript();
			}
			
			//$('main_help').show();
			new Effect.BlindDown( $('main_help'), { duration: 0.3 } );
			$('help_link').addClassName('showing').update( acpHelp['close_help'] );
			$('help_nw').show();
			
			ipb.Cookie.set('acp_help', 'open', 1);
			
			var wait = function(){
				try {
					$('acp-help-contents').update( JSON_acp_help );
				} catch( err ) {
					Debug.write("Waiting...");
					wait.delay(0.1);
				}
			}
					
			wait.delay(0.1);
			
			//Debug.write( JSON_acp_help );
		}
	},
	
	loadSettingGroup: function(e, elem)
	{
		Event.stop(e);
		var id = elem.id;
		
		// Load up the page
		if( !ACPHelp.pageLoaded )
		{
			ACPHelp.loadJavascript();
		}
		
		var wait = function(){
			try {
				$('acp-help-contents').update( JSON_acp_help );
				var content = acpHelp['popup_template'].evaluate( { 'content': $('help_' + id).innerHTML, 'title': $( id + '_title' ).innerHTML } );
				popup = new ipb.Popup( id + 'popup', { type: 'balloon', stem: true, attach: { target: elem, position: 'auto' }, w: '500px', initial: content, hideAtStart: false } );
			} catch( err ) {
				Debug.write("Waiting...");
				wait.delay(0.1);
			}
		}
				
		wait.delay(0.1);
	},
	
	/*loadJavascript: function()
	{
		//alert('testing..');
		
		
		$$('.triggerPopup').each( function(elem){
			elem.observe('click', ACPHelp.triggerPopup );
		});
		
		ACPHelp.hasBeenLoaded	= true;
	},*/


	loadHelp: function(e)
	{
		Event.stop(e);
		
		if( !ACPHelp.hasBeenLoaded )
		{
			ACPHelp.loadJavascript();
		}
		
		//$('acp-help-contents').toggle();

		return false;
	},
	
/*	loadSettingGroup: function(e)
	{
		if( !ACPHelp.hasBeenLoaded )
		{
			ACPHelp.loadJavascript();
		}

		var elem	= Event.findElement(e, 'a');
		Event.stop(e);

		popup = new ipb.Popup( 'acphelp', { type: 'pane', modal: false, w: '500px', initial: $('help_' + elem.id).innerHTML, hideAtStart: false, close: 'a[rel="close"]' } );

		return false;
	},*/
	
	triggerPopup: function(e)
	{
		var elem	= Event.findElement(e, 'a');
		var divId	= elem.id.replace( /trigger_/, '' );
		Event.stop(e);
		
		if( $(divId) )
		{
			// Charles wants it to toggle, but to jump down to the right spot...
			// popup = new ipb.Popup( 'acphelp', { type: 'pane', modal: false, w: '500px', initial: $(divId).innerHTML, hideAtStart: false, close: 'a[rel="close"]' } );
			$(divId).toggle();
		}

		return false;
	}
};

ACPHelp.init();