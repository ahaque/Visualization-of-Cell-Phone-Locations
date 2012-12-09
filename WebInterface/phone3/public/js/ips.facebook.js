/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.facebook.js - Facebook Connect code		*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Matt Mecham, Rikki Tissier			*/
/************************************************/

var _fb = window.IPBoard;

_fb.prototype.facebook = {
	api: '',
	linkedMember: {},
	mem_fb_uid: 0,
	
	/*------------------------------*/
	/* Constructor 					*/
	init: function()
	{
		Debug.write("Initializing ips.facebook.js");
		
		//document.observe("dom:loaded", function(){
			
		//});
	},
	
	/**
	 * Wrapper for loadUser when used on the log in page
	 */
	login_loadUser: function()
	{
		/* Annoyingly, this has to go here */
		ipb.facebook.api = FB.Facebook.apiClient;
		
		ipb.facebook.loadUser();
	},
	
	/**
	 * Wrapper for loadUser when used on the register page
	 */
	register_loadUser: function()
	{
		var content = $('fb-template-main').innerHTML;
		
		/* Annoyingly, this has to go here */
		ipb.facebook.api = FB.Facebook.apiClient;
		
		$('fbUserBox').update( content );
		FB.XFBML.Host.parseDomTree();
		
		/* Set up handler */
		$('fbc_completeNewAcc').observe('click', ipb.facebook.login_completeNewAcc );
	},
	
	/**
	 * Wrapper for loadUser when used on the UserCP page
	 */
	usercp_loadUser: function()
	{
		/* Has this ID been saved, yet? */
		if ( ! ipb.facebook.mem_fb_uid )
		{
			window.location = ipb.vars['base_url'] + 'app=core&module=usercp&tab=members&area=facebookLink&do=custom&secure_key=' + ipb.vars['secure_hash'];
		}
		
		var content = $('fb-template').innerHTML;
		
		/* Annoyingly, this has to go here */
		ipb.facebook.api = FB.Facebook.apiClient;
		
		$('fbUserBox').update( content );
		FB.XFBML.Host.parseDomTree();
		
		/* Set up handler */
		$('userCPForm').observe( 'submit', ipb.facebook.stupidBug );
		$('fbc_remove').observe( 'click', ipb.facebook.usercp_remove );
	},
	
	/**
	* Updates the log in box
	*
	*/
	login_updateBox: function()
	{
		/* INIT */
		var content       = $('fb-template-main').innerHTML;
		
		if ( ipb.facebook.linkedMember['member_id'] > 0 )
		{
			content = content + $('fb-template-linked').innerHTML;
		}
		else
		{
			content = content + $('fb-template-notlinked').innerHTML;
		}

		$('fbUserBox').update( content );
		FB.XFBML.Host.parseDomTree();
			
		/* Set up handlers */
		$('fbc_completeNewAcc').observe('click', ipb.facebook.login_completeNewAcc );
		$('fbc_completeWithLink').observe('click', ipb.facebook.login_linkCheck );
		$('fbc_complete').observe('click', ipb.facebook.login_complete );
	},
	
	/**
	* Loads the URL to remove the app
	*
	*/
	usercp_remove: function()
	{
		window.location = ipb.vars['base_url'] + 'app=core&module=usercp&tab=members&area=facebookRemove&do=custom&secure_key=' + ipb.vars['secure_hash'];
	},
	
	/**
	* Get around clashes with FBJS
	*
	*/
	stupidBug: function(e)
	{
		Event.stop(e);
		var location = $('userCPForm').action + '&secure_hash=' + ipb.vars['secure_hash'] + '&do=save';
		
		[ '_pic', '_avatar', '_status', '_aboutme' ].each( function (i) {
			location += '&fbc_s' + i + '='+ ( $('fbc_s' + i).checked ? 1 : 0 );
		} );
		
		window.location = location;
		return false;
	},
	
	/**
	* Simply submits our form so PHP can do the final checks
	*
	*/
	login_completeNewAcc: function()
	{
		$('fbc_linkNewAccForm').submit();
	},
	
	/**
	* Simply submits our form so PHP can do the final checks
	*
	*/
	login_complete: function()
	{
		$('fbc_linkAlreadyForm').submit();
	},
	
	/**
	* Check, via AJAX for an existing account
	*
	*/
	login_linkCheck: function()
	{
		/* Fetch FB ID */
		fbUserId = ipb.facebook.api.get_session().uid;
		
		new Ajax.Request( 	ipb.vars['base_url'] + '&app=core&module=ajax&secure_key=' + ipb.vars['secure_hash'] + '&section=login&do=authenticateUser&fbid=' + fbUserId,
							{
								method: 'post',
								parameters: { 'emailaddress' : $('fbc_emailAddress').value, 'password' : $('fbc_password').value },
								evalJSON: 'force',
								onSuccess: function(t)
								{
									if( Object.isUndefined( t.responseJSON ) )
									{
										return false;
									}

									_result = t.responseJSON;
									
									if ( _result['status'] == 'error' )
									{
										$('fbc_linkError').update("Email or password incorrect");
										$('fbc_linkError').show();
									}
									else
									{
										if ( _result['memberData']['fb_uid'] != 0 )
										{
											$('fbc_linkError').update("That member is already linked to a facebook account");
											$('fbc_linkError').show();
										}
										else
										{
											/* Submit the form and let PHP re-check this and use the facebook PHP ipb.facebook.api to validate the signature, etc */
											$('fbc_linkForm').submit();
										}
									}
								}
							}
						);
	},
	/**
	 * Check to see if the facebook user is linked to an IPB account
	 */
	loadUser: function()
	{
		/* Already been here? */
		if ( typeof( ipb.facebook.linkedMember['member_id'] ) != 'undefined' )
		{
			return;
		}
		
		/* Fetch FB ID */
		fbUserId = ipb.facebook.api.get_session().uid;
		
		/* Got a linked member? */
		new Ajax.Request( 	ipb.vars['base_url'] + '&app=core&module=ajax&secure_key=' + ipb.vars['secure_hash'] + '&section=facebook&do=getUserByFbId&fbid=' + fbUserId,
							{
								method: 'post',
								evalJSON: 'force',
								onSuccess: function(t)
								{
									if( Object.isUndefined( t.responseJSON ) )
									{
										ipb.facebook.linkedMember = { 'member_id' : 0 };
									}
									else
									{
										ipb.facebook.linkedMember = t.responseJSON;
									}
									
									ipb.facebook.login_updateBox();
								}
							}
						);
	}
}

ipb.facebook.init();