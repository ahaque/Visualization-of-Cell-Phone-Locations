/**
* IPS Upgrade Javascript
*
* @author Matt Mecham
* @started Monday 7th January 2008
*
* STEPS-----------------
* SQL
* App Module
* Check for more modules
* Templates
* Languages
* Tasks
* Settings
* Caches / Done
*/

ipsUpgrade = new ipsUpgrade();

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
																if ( ipsUpgrade.callInProgress( t.transport) )
																{
																	ipsUpgrade.timeOutAjax( t );
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
																		ipsUpgrade.updateProgressImage( 'wait' );
																	break;	
																	case 3:
																	default:
																	case 4:
																		ipsUpgrade.updateProgressImage( 'receive' );
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

/**
* Main Parent Class
*/
function ipsUpgrade()
{
	/**
	* Applications array
	*/
	this.applications = {};
	
	/**
	* Upgrade Steps
	*/
	this.upgradeSteps = new Array( 'sql_steps', 'app_module', 'next_check', 'templates', 'languages', 'tasks', 'settings', 'finish' );
	
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
	* Current upgrade app
	*/
	this.upgradingApp = {};
	
	/**
	* Current step
	*/
	this.currentStep = '';
	
	/**
	* Upgrading Version
	*/
	this.upgradingVersion = 0;
	
	/*
	* Current Image
	*/
	this.currentImage = '';
	
	/**
	* Init Function
	* @author Matt Mecham 
	*/
	this.init = function()
	{
		/* Reset buttons */
		this.resetApplications();
	};
	
	/**
	* Begin the upgrade procedure
	*/
	this.beginUpgrade = function( e, div, app_dir )
	{
		/* Blank out all other rows... */
		for( var i in this.applications )
		{
			var _element = $( 'upgradeRowWrapper-' + this.applications[i].app_dir );
			
			if ( this.applications[i].app_dir != app_dir )
			{
				var newdiv            = document.createElement('DIV');
				newdiv.id             = 'upgradeRowWrapper-' + this.applications[i].app_dir + '-HIDDEN';
				newdiv.className      = (is_ie ) ? 'dragmove-hide-ie' : 'dragmove-hide-moz';
				newdiv.style.position = 'absolute';
				newdiv.style.top      = _element.cumulativeOffset().top + 'px';
				newdiv.style.left     = _element.cumulativeOffset().left + 'px';
				newdiv.style.width    = _element.offsetWidth + 'px';
				newdiv.style.height   = _element.offsetHeight + 'px';
				document.body.appendChild( newdiv );
			}
			
			/* Stop observing any upgrade buttons */
			Event.stopObserving( 'upgradeRowButton-' + this.applications[i].app_dir, 'click', this._buttonClicker );
		}
		
		/* Update array */
		this.upgradingApp = this.applications[ app_dir ];
		
		/* Get the next available upgrade */
		this.upgradingVersion = this.applications[ app_dir ].next_version;
		
		/* Slide out log tray */
		if ( ! $( 'upgradeLogDraw' ).visible() )
		{
			Effect.SlideDown( $( 'upgradeLogDraw' ), {duration:0.5} );
		}
		
		/* Start text output.. */
		this.writeToLog( "Starting upgrade for <strong>" + this.applications[ app_dir ].real_name + "</strong>" );
		
		/* Reset progress bar */
		this.updateProgressBar( false );
		
		/* Reset image */
		this.updateProgressImage( 'send' );
		
		/* Start off with the SQL */
		this.fireAjax( 'sql_step', 'do=sql_steps');
	}
	
	/**
	* Update the progress bar
	*/
	this.updateProgressBar = function( step )
	{
		/* INIT */
		var _element = $( 'upgradeLogProgressBarInner' );
		
		if ( step != false )
		{
			if ( step == 'finish' )
			{
				_element.style.backgroundImage = 'url(' + this.imageUrl + 'donebar.gif)';
				_element.style.width = '100%';
			}
			else
			{
				var i = 0;
			
				for( var x = 0 ; x <= this.upgradeSteps.length ; x++ )
				{
					i++;
				
					if ( step == this.upgradeSteps[x] )
					{
						break;
					}
				}
				
				_element.style.backgroundImage = 'url(' + this.imageUrl + 'progressbar.gif)';
				
				_element.style.width = Math.round( ( 100 / this.upgradeSteps.length ) * i ) + '%';
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
		if ( ipsUpgrade.currentImage != _img )
		{
			$( 'upgradeStatusImage' ).src = ipsUpgrade.imageUrl + _img;
			ipsUpgrade.currentImage       = _img;
		}
		
		//$( 'upgradeLogText' ).innerHTML = ipsUpgrade.imageUrl + _img + "<br />" + $( 'upgradeLogText' ).innerHTML;
	}
	
	/**
	* Fire Ajax
	*/
	this.fireAjax = function( step, url)
	{
		/* Update image */
		this.updateProgressImage( 'send' );
		
		/* Update current step */
		this.currentStep = step;
		
		new Ajax.Request( this.baseUrl + '&app_directory=' + this.upgradingApp.app_dir + '&version=' + this.upgradingVersion + '&' + url,
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
			this.showError( json );
			return false;
		}
		
		if ( json['warning'] )
		{
			alert( 'Warning: ' + json['warning'] );
		}
		
		/* All good: Update status message... */
		this.writeToLog( json['message'] );
		
		/* Update progress bar */
		if ( json['current_step'] != json['next_step'] )
		{
			this.updateProgressBar( json['current_step'] );
		}
		
		/* Update version? */
		if ( json['next_version'] != '' )
		{
			this.upgradingVersion = json['next_version'];
		}
		
		/* Finish? If so - say all done and go tubby-bye-bye */
		if ( json['next_step'] != '__FINISH__' )
		{
			/* Fire Ajax */
			this.fireAjax( json['next_step'], json['next_url'] );
		}
		else
		{
			/* All Done */
			this.updateProgressBar( 'finish' );
			this.updateProgressImage( 'ready' );
			
			this.writeToLog( "<strong>Upgrade Completed</strong>" );
			
			/* Update version information */
			this.applications[ this.upgradingApp.app_dir ].current_human = json['new_human'];
			this.applications[ this.upgradingApp.app_dir ].current_long  = json['new_long'];
			
			/* Remove Blanks... */
			for( var i in this.applications )
			{
				try
				{
					/* Remove any old rows */
					document.body.removeChild( $('upgradeRowWrapper-' + this.applications[i].app_dir + '-HIDDEN') );
					
					/* Stop observing any upgrade buttons */
					Event.stopObserving( 'upgradeRowButton-' + this.applications[i].app_dir, 'click', this._buttonClicker );
				}
				catch(err)
				{
					//alert( err );
				}
			}
			
			this.resetApplications();
		}
	};
	
	/**
	* Show Error
	*/
	this.showError = function( json )
	{
		var _parent  = $( 'upgradeWrap' );
		var _thisone = $( 'upgradeErrorBox' );
		
		_thisone.style.top      = _parent.cumulativeOffset().top + 'px';
		_thisone.style.left     = _parent.cumulativeOffset().left + 'px';
		_thisone.style.width    = _parent.offsetWidth - 2 + 'px';
		_thisone.style.height   = _parent.offsetHeight - 23 + 'px';
		_thisone.style.position = 'absolute';
		
		_thisone.style.display  = '';
		
		/* Store JSON */
		this.storedJSON = json;
		
		/* Add error message */
		$( 'upgradeErrorText' ).innerHTML = json['error'];
	}
	
	/**
	* Error; Continue
	*/
	this.errorContinue = function()
	{
		/* Button has been clicked, so hide error box... */
		$( 'upgradeErrorBox' ).hide();
		
		/* Fire Ajax */
		ipsUpgrade.fireAjax( ipsUpgrade.storedJSON['next_step'], ipsUpgrade.storedJSON['next_url'] );
	}
	
	/**
	* Write to Log
	*/
	this.writeToLog = function( text )
	{
		$( 'upgradeLogText' ).innerHTML = text + "<br />" + $( 'upgradeLogText' ).innerHTML;
	};
	
	/**
	* Reset Application Buttons
	*/
	this.resetApplications = function()
	{
		/* Loopy */
		for( var i in this.applications )
		{
			var _button  = $( 'upgradeRowButton-' + this.applications[i].app_dir );
			var _version = $( 'upgradeRowVersion-' + this.applications[i].app_dir );
			
			/* Version */
			_version.innerHTML = this.applications[i].current_human;
			
			/* Button */
			if ( this.applications[i].current_long >= this.applications[i].latest )
			{
				_button.className = _button.className.replace( / available$/, '' );
				_button.title     = '';
				_button.innerHTML = "No Upgrade Available";
				
				Event.stopObserving( 'upgradeRowButton-' + this.applications[i].app_dir, 'click', this._buttonClicker );
				
			}
			else
			{ 
				_button.className = _button.className + ' available';
				_button.title     = 'Click to begin upgrade';
				_button.innerHTML = "Upgrade Available";
				
				this._buttonClicker  = this.beginUpgrade.bindAsEventListener( this, 'upgradeRowButton-' + this.applications[i].app_dir, this.applications[i].app_dir );
				Event.observe(  'upgradeRowButton-' + this.applications[i].app_dir, 'click', this._buttonClicker );
			}
		}
	};
	
	/**
	* Add application to the array
	*/
	this.addApplication = function( app_dir, real_name, current_long_version, latest_long_version, current_human_version, latest_human_version, next_version )
	{
		/* INIT */
		var _found = 0;
		
		/* Loop to see if this already exists */
		for( var i in this.applications )
		{
			if ( this.applications[i].app_dir == app_dir )
			{
				_found = 1;
				break;
			}
		}
		
		/* Add it */
		if ( _found != 1 )
		{
			this.applications[ app_dir ] = { 'app_dir'       : app_dir,
											 'real_name'     : real_name,
											 'latest'        : latest_long_version,
											 'current_long'  : current_long_version,
											 'latest_human'  : latest_human_version,
											 'current_human' : current_human_version,
											 'next_version'  : next_version,
											 'upgrading'     : 0 };
		}
	};
	
	

}
