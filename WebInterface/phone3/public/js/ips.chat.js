/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.chat.js - Chat javascript				*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Brandon Farber						*/
/************************************************/

var _chat = window.IPBoard;

_chat.prototype.chat = {
	
	ajaxTrigger: 60,
	
	init: function()
	{
		Debug.write("Initializing ips.chat.js");
		
		document.observe("dom:loaded", function(){
			ipb.chat.ping();
			new PeriodicalExecuter( ipb.chat.ping, ipb.chat.ajaxTrigger );
		});
	},
	
	ping: function()
	{
		new Ajax.Request( 
						ipb.vars['base_url'] + "app=chat&module=ajax&section=update&md5check="+ipb.vars['secure_hash'], 
						{ method: 'get' }
						);
	}
}

ipb.chat.init();