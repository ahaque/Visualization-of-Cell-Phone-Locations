/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.help.js - Help File Javascript			*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Brandon Farber						*/
/************************************************/

var _help = window.IPBoard;

_help.prototype.help = {
	
	/**
	 * Initialization
	 * 
	 */
	init: function()
	{
		Debug.write("Initializing ips.help.js");
		
		document.observe("dom:loaded", function(){
			$$('li.helpRow a').each( function(elem){
				elem.observe('click', ipb.help.showHelpFile );
			});
		});
	},
	
	/**
	 * Shows a prompt allowing user to copy the URL
	 * 
	 * @var		{event}		e		The event
	 */
	showHelpFile: function(e)
	{
		if( $( Event.element(e) ).tagName != 'A' ){	return;	}
		
		Event.stop(e);
		
		if( ipb.help.loading == true ){ return; }
		
		if( Event.element(e).hasClassName( 'isOpen' ) )
		{
			Event.element(e).removeClassName( 'isOpen' );
			Event.element(e).up('.helpRow').down( '.openedText' ).hide();
			return;
		}
		
		if( Event.element(e).up('.helpRow').down( '.openedText' ) )
		{
			Event.element(e).addClassName( 'isOpen' );
			Event.element(e).up('.helpRow').down( '.openedText' ).show();
			return;
		}

		var url	= $( Event.element(e) ).readAttribute('href');
		url 	= url + '&xml=1';
		url 	= url.replace( /&amp;/g, '&' );
		
		ipb.help.loading = true;
		
		new Ajax.Request(	url,
							{
								method: 'get',
								onSuccess: function(t)
								{
									if( t.responseText == 'error' )
									{
										alert( ipb.lang['action_failed'] );
										ipb.help.loading = false;
										return;
									}
									
									// Create a div and show the help text
									var text = new Element( 'div' ).update( t.responseText ).addClassName('openedText');
									$( Event.element(e) ).up('.helpRow').insert( { bottom: text } );
									Event.element(e).addClassName( 'isOpen' );
									ipb.help.loading = false;
								}
							}
						);
		
		
	}

};

ipb.help.init();