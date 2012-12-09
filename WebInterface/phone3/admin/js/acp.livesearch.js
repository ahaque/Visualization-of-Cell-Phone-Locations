/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.global.js - Global functionality			*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/

ACPLiveSearch = {
	searchTimer: [],
	searchLastQuery: '',
	hasCleared: false,
	
	/*------------------------------*/
	/* Constructor 					*/
	init: function()
	{
		Debug.write("Initializing acp.livesearch.js");
		
		document.observe("dom:loaded", function(){
			ACPLiveSearch.initEvents();
		});
	},
	initEvents: function()
	{
		if( !$('acpSearchKeyword') )
		{
			return false;
		}

		/*$$('.__user').each( function(elem){
			ipb.namePops[ elem.identify() ] = [];
			
			elem.observe("mouseover", ACPLiveSearch.timer_showUserPopup);
			elem.observe("mouseout", ACPLiveSearch.timer_hideUserPopup);
		});*/
		
		$('acpSearchKeyword').observe("focus", ACPLiveSearch.timer_liveSearch );
		$('acpSearchKeyword').observe("blur", ACPLiveSearch.timer_hideLiveSearch );
		$('acpSearchKeyword').writeAttribute({autocomplete: "off"}); /* Turn off autocomplete */
	},
	
	/* ------------------------------ */
	/**
	 * Timer for live searching
	 * 
	 * @param	{event}		e	The event
	*/
	timer_liveSearch: function(e)
	{
		if( !ACPLiveSearch.hasCleared )
		{
			$('acpSearchKeyword').value	= '';
			ACPLiveSearch.hasCleared	= true;
		}

		ACPLiveSearch.searchTimer['show'] = setTimeout( ACPLiveSearch.liveSearch, 400 );
	},
	
	/* ------------------------------ */
	/**
	 * TImer for hiding live search
	 * 
	 * @param	{event}		e	The event
	*/
	timer_hideLiveSearch: function(e)
	{
		ACPLiveSearch.searchTimer['hide'] = setTimeout( ACPLiveSearch.hideLiveSearch, 800 );
	},
	
	/* ------------------------------ */
	/**
	 * Actually hides live search
	 * 
	 * @param	{event}		e	The event
	*/
	hideLiveSearch: function(e)
	{
		new Effect.Fade( $('live_search_popup'), { duration: 0.4, afterFinish: function(){
			$('ajax_result').update('');
		 } } );
		
		ACPLiveSearch.searchLastQuery = '';
		clearTimeout( ACPLiveSearch.searchTimer['show'] );
		clearTimeout( ACPLiveSearch.searchTimer['hide'] );
	},
	
	/* ------------------------------ */
	/**
	 * Live search routine
	 * 
	 * @param	{event}		e	The event
	*/
	liveSearch: function(e)
	{
		// Keep loopy going
		ACPLiveSearch.timer_liveSearch();
		
		var val = $F('acpSearchKeyword').strip();
		
		// If too few chars, dont do anything
		if( val.length < 3 ){ return; }
		
		// Is the popup available?
		if( !$('live_search_popup') )
		{
			Debug.write("Creating popup");
			ACPLiveSearch.buildSearchPopup();
		}
		else if( !$('live_search_popup').visible() )
		{
			new Effect.Appear( $('live_search_popup'), {duration: 0.4} );
		}
		
		// Is the text the same as last time?
		if( $F('acpSearchKeyword') == ACPLiveSearch.searchLastQuery ){ return; } /* continue looping */

		//Get hits
		new Ajax.Request( 
			ipb.vars['base_url'] + "app=core&module=ajax&section=livesearch&do=search&secure_key=" + ipb.vars['md5_hash'] + "&search_term=" + val ,
			{
				method: 'get',
				onSuccess: function(t){
					//if( !$('ajax_result') ){ return; }

					if( t.responseText == 'logout' )
					{
						ACPLiveSearch.searchLastQuery = val;
						ACPLiveSearch.hideLiveSearch();
						return false;
					}

					$('ajax_result').update( t.responseText );
				}
			}
		);
				
		/* Make sure we set this value so we don't run unnecessary ajax requests */
		ACPLiveSearch.searchLastQuery = $F('acpSearchKeyword');
	},
	
	/* ------------------------------ */
	/**
	 * Builds the popup for live search
	 * 
	 * @param	{event}		e	The event
	*/
	buildSearchPopup: function(e)
	{
		pos = $('acpSearchKeyword').cumulativeOffset();
		finalPos = { 
			top: pos.top + $('acpSearchKeyword').getHeight(),
			left: ( pos.left + 45 )
		};
		
		popup =	new Element('div', { id: 'live_search_popup' } ).hide().setStyle('top: ' + finalPos.top + 'px; left: ' + finalPos.left + 'px');
		$('ipboard_body').insert({ bottom: popup });
				
				
		//Get form
		new Ajax.Request( ipb.vars['base_url'] + "app=core&module=ajax&section=livesearch&do=template&secure_key=" + ipb.vars['md5_hash'],
		{
			method: 'get',
			onSuccess: function(t){
				popup.update( t.responseText );
				//ACPLiveSearch.liveSearch();
			}
		});		
		
		new Effect.Appear( $('live_search_popup'), {duration: 0.3} );
	}
}
ACPLiveSearch.init();