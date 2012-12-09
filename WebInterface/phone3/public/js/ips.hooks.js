/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.forums.js - Forum view code				*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/

var _hooks = window.IPBoard;

_hooks.prototype.hooks = {
	activeTab: 'forums',

	init: function()
	{
		Debug.write("Initializing ips.hooks.js");
		
		document.observe("dom:loaded", function(){
			ipb.hooks.initEvents();
		});
	},

	initEvents: function()
	{
		$$('.tab_toggle').each( function(elem){
			$(elem).observe('click', ipb.hooks.changeTabContent );
		});
		
		if( $('more-watched-forums') )
		{
			$('more-watched-forums').observe('click', ipb.hooks.toggleWatchedForums );
		}
		
		if( $('more-watched-topics') )
		{
			$('more-watched-topics').observe('click', ipb.hooks.toggleWatchedTopics );
		}
	},
	
	toggleWatchedForums: function(e)
	{
		Event.stop(e);
		
		$('more-watched-forums-container').toggle();
	},
	
	toggleWatchedTopics: function(e)
	{
		Event.stop(e);
		
		$('more-watched-topics-container').toggle();
	},
						
	changeTabContent: function(e)
	{
		Event.stop(e);
		elem = Event.findElement(e, 'li');
		if( !elem.hasClassName('tab_toggle') || !elem.id ){ return; }
		id = elem.id.replace('tab_link_', '');
		if( !id || id.blank() ){ return; }
		if( !$('tab_content_' + id ) ){ return; }
		
		if( ipb.hooks.activeTab == id )
		{
			return;
		}
		
		oldTab = ipb.hooks.activeTab;
		ipb.hooks.activeTab = id;
		
		// OK, we should have an ID. Does it exist already?
		
		if( !$('tab_' + id ) )
		{
			$$('.tab_toggle_content').each( function(otherelem){
				$(otherelem).hide();
			});
			
			$('tab_content_' + id ).show();
		}
		else
		{
			new Effect.Parallel( [
				new Effect.BlindUp( $('tab_content_' + oldTab), { sync: true } ),
				new Effect.BlindDown( $('tab_content_' + ipb.hooks.activeTab), { sync: true } )
			], { duration: 0.4 } );
		}
		
		$$('.tab_toggle').each( function(otherelem){
			$(otherelem).removeClassName('active');
		});
		
		$(elem).addClassName('active');
		
	}
}
ipb.hooks.init();
