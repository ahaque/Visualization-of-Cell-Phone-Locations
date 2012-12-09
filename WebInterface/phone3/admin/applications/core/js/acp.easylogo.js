/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.global.js - Global functionality			*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/

ACPEasyLogo = {
	init:		function()
	{
		Debug.write("Initializing acp.easylogo.js");
		
		document.observe("dom:loaded", function(){
			$('skin').observe( "change", ACPEasyLogo.resetMacro );
		});
	},
	
	resetMacro:	function(e)
	{
		var	value	= $F('skin');
		
		//Get hits
		new Ajax.Request( 
			ipb.vars['base_url'] + "app=core&module=ajax&section=replacements&do=retrieve&secure_key=" + ipb.vars['md5_hash'] + "&value=" + value ,
			{
				method: 'get',
				evalJSON: 'force',
				onSuccess: function(t){
					if ( t.responseJSON['url'] )
					{
						$('logo_url').value = t.responseJSON['url'];
						$('replacementId').value = t.responseJSON['id'];
					}
				}
			}
		);
		
		return false;
	}
}
ACPEasyLogo.init();