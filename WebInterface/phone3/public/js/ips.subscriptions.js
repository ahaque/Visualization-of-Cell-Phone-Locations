/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.subscriptions.js - Subs Manager Public	*/
/* (c) IPS, Inc 2009							*/
/* -------------------------------------------- */
/* Author: Matt Mecham, Rikki Tissier			*/
/************************************************/

var _subscriptions = window.IPBoard;

_subscriptions.prototype.subscriptions = {
	initDone: false,
	
	/*------------------------------*/
	/* Constructor 					*/
	init: function()
	{
		document.observe("dom:loaded", function()
		{
			if ( ipb.subscriptions.initDone === false )
			{
				/* Set up submit handler */
				$('register').observe( 'submit', ipb.subscriptions.formSubmit );
				
				ipb.subscriptions.initDone = true;
				
				Debug.write("Initializing ips.subscriptions.js");
			}
		});
	},
	
	/**
	 * handle a form submit
	 */
	formSubmit: function(e)
	{
		Event.stop(e);
		
		var _go = false;
		
		/* Did we check a radio button? */
		$$('.sm__radio').each( function( element )
		{
			if ( element.checked )
			{
				_go = true;
			}
			
		} );
		
		if ( _go !== true )
		{
			alert( 'Please choose a subscriptions package before continuing' );
			return false;
		}
		else
		{
			$('register').submit();
		}
	}

}
ipb.subscriptions.init();