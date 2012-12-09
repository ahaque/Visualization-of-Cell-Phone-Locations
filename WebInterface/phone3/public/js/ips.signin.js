var _signin = window.IPBoard;

_signin.prototype.signin = {
	init: function()
	{
		Debug.write("Initializing ips.signin.js");
		
		document.observe("dom:loaded", function(){
			if( $('openid_signin') ){
				$('openid_signin').hide();
				$('openid_open').observe('click', ipb.signin.toggleOpenID);
				$('openid_close').observe('click', ipb.signin.toggleOpenID);
			}
			
			if( $('live_signin') ){
				$('live_signin').hide();
				$('live_open').observe('click', ipb.signin.toggleLive);
				$('live_close').observe('click', ipb.signin.toggleLive);
			}
			
			if( $('login') )
			{
				$('login').observe('submit', ipb.signin.validateLogin );
			}
		});
	},
	
	/* ------------------------------ */
	/**
	 * Toggles the OpenID login field
	 * 
	 * @param	{event}		e	The event
	*/
	toggleOpenID: function(e)
	{
		if( $('openid_signin').visible() )
		{
			new Effect.Parallel([
				new Effect.BlindUp( $('openid_signin'), { sync: true } ),
				new Effect.BlindDown( $('regular_signin'), { sync: true } )
			]);
		}
		else
		{
			new Effect.Parallel([
				new Effect.BlindDown( $('openid_signin'), { sync: true } ),
				new Effect.BlindUp( $('regular_signin'), { sync: true } )
			]);
		}
		
		Event.stop(e);
	},
	
	/* ------------------------------ */
	/**
	 * Toggles the Windows Live login field
	 * 
	 * @param	{event}		e	The event
	*/
	toggleLive: function(e)
	{
		if( $('live_signin').visible() )
		{
			new Effect.Parallel([
				new Effect.BlindUp( $('live_signin'), { sync: true } ),
				new Effect.BlindDown( $('regular_signin'), { sync: true } )
			]);
		}
		else
		{
			new Effect.Parallel([
				new Effect.BlindDown( $('live_signin'), { sync: true } ),
				new Effect.BlindUp( $('regular_signin'), { sync: true } )
			]);
		}
		
		Event.stop(e);
	},
	
	/* ------------------------------ */
	/**
	 * Form validation for login
	 * 
	 * @param	{event}		e	The event
	 * @return	void
	*/
	validateLogin: function(e)
	{
		if( !$('openid_signin') || ( $F('openid') == '' || $F('openid') == 'http://') )
		{
			if( !ipb.signin.isFilled( $('username') ) )
			{
				alert("No sign in name entered");
				Event.stop(e);
				return;
			}
			if( !ipb.signin.isFilled( $('password') ) )
			{
				alert("No password entered");
				Event.stop(e);
				return;
			}
		}
		else
		{
			if( !ipb.signin.isValidUrl( $F('openid') ) )
			{
				alert("Supplied OpenID url is invalid");
				Event.stop(e);
				return;
			}
		}		
	},
	
	/* ------------------------------ */
	/**
	 * Validate that content is filled
	 * 
	 * @param	{event}		e	The event
	 * @return	void
	 * @SKINNOTE 	Stop using this duplicated code and use ipb.js validate object
	*/
	isFilled: function( obj )
	{
		if( !obj.value )
		{
			return false;
		}
		else
		{
			return true;
		}
	},
	
	/* ------------------------------ */
	/**
	 * Validate the openid url is a valid url
	 * 
	 * @param	{event}		e	The event
	 * @return	void
	 * @SKINNOTE 	Stop using this duplicated code and use ipb.js validate object
	*/
	isValidUrl: function( value )
	{
		if( !value )
		{
			return false;
		}
		
		var regexp = new RegExp();
		regexp.compile("^[A-Za-z]+://[A-Za-z0-9-_]+\\.[A-Za-z0-9-_%&\?\/.=]+$"); 

		return regexp.test( value );
	}
}
ipb.signin.init();
