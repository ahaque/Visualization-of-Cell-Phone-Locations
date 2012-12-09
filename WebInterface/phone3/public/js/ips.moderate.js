var _moderate = window.IPBoard;

_moderate.prototype.moderate = {
	init: function()
	{
		Debug.write("Initializing ips.moderate.js");
		
		document.observe("dom:loaded", function(){
			$$('.cancel').each( function cancelMod(elem) {
				elem.observe('click', ipb.moderate.resetCookie);
			} );
		});
	},
	
	/* ------------------------------ */
	/**
	 * Reset the cookie so topic page works properly
	 * 
	 * @param	{event}		e	The event
	*/
	resetCookie: function(e)
	{
		link = Event.findElement(e, 'a');
		href = link.getAttribute('href');
		pids = href.replace( new RegExp( /.+?\/selectedpids[\/=]([0-9,]+)(\/|$)/ ), '$1' );
		
		ipb.Cookie.set('modpids', pids, 0);
		return true;
	}
}
ipb.moderate.init();
