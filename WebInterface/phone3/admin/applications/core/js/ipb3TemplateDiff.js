/**
* INVISION POWER BOARD v3
*
* Topics View Javascript File
* @author Matt Mecham, Brandon Farber, Josh Williams
* @since 2008
*/

/**
* "ACP" Class. Designed for ... ACP Template Diff Functions
* @author (v.1.0.0) Matt Mecham
*/

/* Prototype Ajax Global Responders
 * Based on code from: http://codejanitor.com/wp/2006/03/23/ajax-timeouts-with-prototype/
 * Aborts ajax after a 5 minute delay of nothing happening  
*/

var IPB3TemplateDiff = new IPBTemplateDiff;

Ajax.Responders.register( {
							onCreate: function( t )
							{
								t['_t'] = window.setInterval(
															function()
															{
																if ( IPB3TemplateDiff.callInProgress( t.transport) )
																{
																	IPB3TemplateDiff.timeOutAjax( t );
																}
															},
															300000
														);
														
								t['_t2'] = window.setInterval(
															function()
															{
																switch( t.transport.readyState )
																{
																	case 1:
																	case 2:
																		IPB3TemplateDiff.updateProgressImage( 'wait' );
																	break;	
																	case 3:
																	default:
																	case 4:
																		IPB3TemplateDiff.updateProgressImage( 'receive' );
																	break;
																}
															},
															500
														);
							},
							onComplete: function( t )
							{
								window.clearInterval( t['_t'] );
								window.clearInterval( t['_t2'] );
							}
						} );
						
function IPBTemplateDiff()
{
	/**
	* URLS
	*/
	this.baseUrl  = '';
	this.imageUrl = '';
	
	/*
	* Stored JSON
	*/
	this.storedJSON = {};
	
	/**
	* Per go
	*/
	this.perGo = 10;
	
	/**
	* Total bits
	*/
	this.totalBits = 0;
	
	/**
	* Init Function
	* @author Matt Mecham 
	*/
	this.init = function()
	{
		this.beginDiff();
	};
	
	/**
	* Begin the upgrade procedure
	*/
	this.beginDiff = function()
	{
		/* Start text output.. */
		this.updateCounter( 0 );
		
		/* Reset progress bar */
		this.updateProgressBar( false );
		
		/* Reset image */
		this.updateProgressImage( 'send' );
		
		/* Start off with the SQL */
		this.fireAjax( 0 );
	}
	
	/**
	* Update the progress bar
	*/
	this.updateProgressBar = function( processed )
	{
		/* INIT */
		var _element = $( 'diffLogProgressBarInner' );
		
		if ( processed !== false )
		{
			if ( processed == -1 )
			{
				_element.style.backgroundImage = 'url(' + this.imageUrl + 'donebar.gif)';
				_element.style.width = '100%';
			}
			else
			{
				_element.style.backgroundImage = 'url(' + this.imageUrl + 'progressbar.gif)';
				_element.style.width = Math.round( ( 100 / this.totalBits ) * processed ) + '%';
			}
		}
		else
		{
			_element.style.width = '1%';
		}
	};
	
	/**
	* Update progress image
	*/
	this.updateProgressImage = function( type )
	{
		/* INIT */
		var _img = '';
		
		switch( type )
		{
			default:
			case 'stop':
				_img = 'stop.png';
			break;
			case 'ready':
				_img = 'ready.png';
			break;
			case 'warn':
				_img = 'warning.png';
			break;
			case 'send':
				_img = 'sending.png';
			break;
			case 'wait':
				_img = 'mini-wait.gif';
			break;
			case 'receive':
				_img = 'receiving.png';
			break;
		}
		
		/* Update image */
		if ( IPB3TemplateDiff.currentImage != _img )
		{
			$( 'diffStatusImage' ).src = IPB3TemplateDiff.imageUrl + _img;
			IPB3TemplateDiff.currentImage       = _img;
		}
	}
	
	/**
	* Fire Ajax
	*/
	this.fireAjax = function()
	{
		/* Update image */
		this.updateProgressImage( 'send' );
		
		new Ajax.Request( this.baseUrl + '&do=process',
						  {
							method: 'get',
							onSuccess: this.processAjax.bind(this),
							onException: this.exceptionAjax.bind(this),
							onFailure: this.failureAjax.bind(this)
						  } );
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
	this.exceptionAjax = function( t )
	{
		alert( "Exception: " + t.responseText );
	}
	
	/**
	* Process Ajax (Success)
	*/
	this.processAjax = function( t )
	{
		/* Update Image */
		this.updateProgressImage( 'receive' );
		
		/* Not a JSON response? */
		if ( ! t.responseText.match( /^(\s+?)?\{/ ) )
		{
			alert( "Error:\n" + t.responseText );
			return;
		}
		
		/* Process results */
		eval( "var json = " + t.responseText );
		
		if ( json['error'] )
		{
			alert( "An error occured: " . json['error'] );
			return false;
		}
		
		/* All good: Update status message... */
		this.updateCounter( json['processed'] );
		
		/* Update progress bar */
		this.updateProgressBar( json['processed'] );
		
		/* Finish? If so - say all done and go tubby-bye-bye */
		if ( json['completed'] != 1 )
		{
			/* Fire Ajax */
			this.fireAjax();
		}
		else
		{
			/* All Done */
			this.updateProgressBar( -1 );
			this.updateProgressImage( 'ready' );
			
			/* Show done thing */
			$( 'diffDone' ).show();
		}
	};
	
	/**
	* Write to Log
	*/
	this.updateCounter = function( count )
	{
		$( 'diffLogProcessed' ).innerHTML = count;
	};

}