/**
* IPS Inline Form Javascript
*
* @author Matt Mecham
* @started Tuesday 19th February 2008
*
* What does this do?
* Well, I'm glad you asked!
*
* It handles inline form creation and posting to save
* page reloads and such. It's really quite good even if
* I do say so myself. Which I do.
*/

ipsInlineForm = new ipsInlineForm();

/* Prototype Ajax Global Responders
 * Based on code from: http://codejanitor.com/wp/2006/03/23/ajax-timeouts-with-prototype/
 * Aborts ajax after a 5 minute delay of nothing happening  
*/
Ajax.Responders.register( {
							onCreate: function( t )
							{
								t['_t'] = window.setInterval(
															function()
															{
																if ( ipsInlineForm.callInProgress( t.transport) )
																{
																	ipsInlineForm.timeOutAjax( t );
																}
															},
															300000
														);
							},
							onComplete: function( t )
							{
								window.clearInterval( t['_t'] );
							}
						} );

/**
* Main Parent Class
*/
function ipsInlineForm()
{
	/**
	* Applications array
	*/
	this.css = {};
	
	/**
	* Init Function
	* @author Matt Mecham 
	*/
	this.init = function()
	{
	};
	
	/**
	* Load the inline form. Please.
	*
	* @param title The title of the form
	* @param url   The ajax URL to load to return the HTML
	*/
	this.loadForm = function( title, url )
	{
		if ( title && url )
		{
			url = ipb.vars['base_url'] + url + '&secure_key=' + ipb.vars['md5_hash'];
			url = url.replace( /&amp;/g, '&' );
			
			/* Update title */
			$('inlineFormInnerTitle').innerHTML = "<h4>" + title + "</h4>";
			
			/* Hide error */
			$('inlineErrorBox').hide();
			
			/* Show it */
			ipb.positionCenter( 'inlineFormWrap' );
			Effect.Appear( 'inlineFormWrap', {duration: 0.5} );
			//$('inlineFormWrap').show();
			
			$('inlineFormInnerContent').hide();
			$('inlineFormLoading').show();
			
			/* Load it */
			new Ajax.Updater( 'inlineFormInnerContent', url,
							  {
								method: 'get',
								evalScripts: 'force',
								evalJS: 'force',
								onSuccess: function (t )
								{
									$('inlineFormLoading').hide();
									
									if ( t.responseText.match( /^(\s+?)?\{/ ) )
									{
										eval( "var json = " + t.responseText );

										if ( typeof( json['error'] ) != 'undefined' )
										{
											$('inlineErrorBox').show();
											$('inlineErrorText').innerHTML = json['error'];
										}
										else
										{
											alert( "Unspecified error" );
										}
										return;
									}
									else
									{
										$('inlineFormInnerContent').show();
										$('inlineFormInnerContent').innerHTML = t.responseText;
									}
									
									$( 'inlineFormLoading' ).hide();
								},
								onException: this.exceptionAjax.bind(this),
								onFailure: this.failureAjax.bind(this)
							  } );
			
		}
		else
		{
			alert("Data missing, could not load the form");
		}
		
		return false;
	};
	
	/**
	* Save the inline form. Please.
	*
	* @param url    The ajax URL to load to return the HTML
	* @param array  Array of field IDs to send as POST data
	*/
	this.saveForm = function( url, postArray )
	{
		if ( url )
		{
			url         = ipb.vars['base_url'] + url + '&secure_key=' + ipb.vars['md5_hash'];
			var _params = {};
			
			if ( postArray.length )
			{
				for( var i = 0 ; i <= postArray.length ; i++ )
				{
					if ( typeof( postArray[i] ) != 'object' && typeof( postArray[i] ) != 'function' && typeof( postArray[i] ) != 'undefined' )
					{
						try
						{
							if ( $( postArray[i] ).type == 'select-multiple' )
							{
								_params[ postArray[i] + '[]' ] = $F( postArray[i] );
							}
							else
							{
								_params[ postArray[i] ] = $F( postArray[i] ).encodeParam();
							}
						}
						catch( e )
						{
							try
							{
								_params[ postArray[i] ] = $F( postArray[i] + '_yes' );
							}
							catch( e ){}
						}
					}
				}
			}
			
			/* Fix URL */
		
			url = url.replace( /&amp;/g, '&' );
		
			$('inlineFormInnerContent').hide();
			$('inlineFormLoading').show();
			$('inlineErrorBox').hide();
			
			/* Load it */
			new Ajax.Request( url,
							  {
								method: 'POST',
								parameters: _params,
								onSuccess: function (t )
								{
									$('inlineFormLoading').hide();
									
									if ( t.responseText.match( /^(\s+?)?\{/ ) )
									{
										eval( "var json = " + t.responseText );

										if ( typeof( json['error'] ) != 'undefined' )
										{
											$('inlineFormInnerContent').show();
											$('inlineErrorBox').show();
											$('inlineErrorText').innerHTML = json['error'];
											$('inlineFormWrap').show();
										}
										else if ( json['success'] === true )
										{
											$('inlineFormWrap').hide();
											$('inlineFormInnerContent').innerHTML = '';
											
											if ( formCallback )
											{
												formCallback( t, json );
											}
										}
										else
										{
											$('inlineFormInnerContent').show();
											$('inlineErrorBox').show();
											$('inlineErrorText').innerHTML = t.responseText;
											$('inlineFormWrap').show();
										}
										return;
									}
									else
									{
										$('inlineFormInnerContent').show();
										$('inlineErrorBox').show();
										$('inlineErrorText').innerHTML = t.responseText;
										$('inlineFormWrap').show();
									}
									
									$( 'inlineFormLoading' ).hide();
								},
								onException: this.exceptionAjax.bind(this),
								onFailure: this.failureAjax.bind(this)
							  } );
			
		}
		else
		{
			alert("Data missing, could not load the form");
		}
	};
	
	
	/**
	* Checking to see if there's a call in progres...
	*/
	this.callInProgress = function( t )
	{
		switch ( t.readyState )
		{
			case 1:
			case 2:
			case 3:
				return true;
			break;
			default:
				return false;
			break;
		}
	};
	
	/**
	* On Timeout
	*/
	this.timeOutAjax = function( t )
	{
		if ( confirm( "No response from the webserver.\nDo you wish to continue waiting?" ) )
		{
			return true;
		}
		else
		{
			t.transport.abort();
			alert( "Request Cancelled" );
		}
	};
	
	/**
	* On Failure
	*/
	this.failureAjax = function( t )
	{
		alert( "Failure: " + t.responseText );
	};
	
	/**
	* On Failure
	*/
	this.exceptionAjax = function()
	{
		if (typeof console == "object")
		{
		 	console.log("ajax exception occurred args = %o", arguments);
		}
	};

}
