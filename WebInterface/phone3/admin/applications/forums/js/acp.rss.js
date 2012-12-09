/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* acp.rss.js - RSS Form javascript 			*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Brandon Farber						*/
/************************************************/

ACPRss = {
	
	/*------------------------------*/
	/* Constructor 					*/
	init: function()
	{
		Debug.write("Initializing acp.rss.js");
		
		document.observe("dom:loaded", function(){
			if( $('rss_import_mid') )
			{
				ACPRss.autoComplete = new ipb.Autocomplete( $('rss_import_mid'), { multibox: false, url: acp.autocompleteUrl, templates: { wrap: acp.autocompleteWrap, item: acp.autocompleteItem } } );
			}
		});
	},
	
	showAuthBoxes: function()
	{
		var auth_req = $('rss_import_auth_userinfo');
		
		if( !auth_req.visible() )
		{
			auth_req.show();
		}
		else
		{
			auth_req.hide();
		}
	},
	
	validate: function()
	{
		formobj = $('rssimport_validate');
		formobj.value = "1";
		$('rssimport_form').submit();
	}
	
	
};

ACPRss.init();