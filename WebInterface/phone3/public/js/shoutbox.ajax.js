/**
 * IP.Shoutbox 1.1.0
 *  - IPS Community Project Developers
 *
 * Ajax JavaScript
 * Last Updated: $Date: 2009-08-02 10:56:49 -0400 (Sun, 02 Aug 2009) $
 *
 * Author:		Alex Hobbs
 *
 * @lastcommit	$Author: terabyte $
 * @copyright	2001 - 2008 Invision Power Services, Inc.
 * @license		
 * @package		IP.Shoutbox
 * @subpackage	PublicJavascript
 * @link		
 * @version		$Revision: 259 $
 */

/**
 * AJAX class
 *
 * Class for ajax-specific functions
 */
_ajax = window.shoutbox;

_ajax.prototype.shoutAjax = {
	
	init: function()
	{
		Debug.write( 'IP.Shoutbox AJAX javascript is loading' );
	},
	
	/**
	 * Submit a shout
	 *
	 * @return  false
	 */
	submitShout: function()
	{
		if ( ipshoutbox.submittingShout )
		{
			ipshoutbox.produceError('already_submitting');
			return false;
		}

		/**
		 * Beta 3
		 * Can only view shoutbox?
		 */
		if ( !ipshoutbox.can_use )
		{
			return false;
		}
		
		var globalOn 	= ( ipshoutbox.global_on ) ? '&global=1' : '',
			postedShout	= ipshoutbox.getShout();

		if ( postedShout == '' )
		{
			ipshoutbox.produceError('blank_shout');
			return false;
		}
		
		if ( postedShout.length * 1024 > ipshoutbox.max_length )
		{
			ipshoutbox.produceError('shout_too_big');
			return false;
		}
		
		/**
		 * Beta 3
		 * Re-added flood check also JS side
		 */
		if ( ipshoutbox.flood_limit && ipshoutbox.bypass_flood != 1 && ipshoutbox.my_last_shout )
		{
			var flood_check = ipshoutbox.getTimestamp() - ipshoutbox.my_last_shout;
			
			if (flood_check < ipshoutbox.flood_limit)
			{
				ipshoutbox.produceError( ipshoutbox.errors['flooding'].replace( '{#EXTRA#}', ( ipshoutbox.flood_limit - flood_check ) ) );
				return false;
			}
		}
		
		var c = ipshoutbox.checkForCommands();
		
		if ( c != null && c == 'doshout' )
		{
			/**
			 * 1.1.0 Alpha
			 *
			 * Clear timeout to avoid loading twice the same shouts
			 * And also setup a boolean value to stop new attempts
			 */
			clearTimeout(ipshoutbox.timeoutShouts);
			ipshoutbox.submittingShout = true;
			
			
			// Take care of the other things
			ipshoutbox.clearShout();
			ipshoutbox.updateLastActivity();
			ipshoutbox.my_last_shout = ipshoutbox.last_active;
			
			/** Finally ajax it! =D **/
			new Ajax.Request( ipshoutbox.baseUrl + 'type=submit&lastid=' + ipshoutbox.last_shout_id + globalOn,
				{
					method: 'post',
					encoding: ipb.vars['charset'],
					parameters: {
						secure_key:	ipb.vars['secure_hash'],
						shout:		postedShout.encodeParam()
					},
					onSuccess: function(s)
					{
						// Beta 3: process inactive prompt if we are submitting a new shout
						if ( ipshoutbox.global_on && ipshoutbox._inactive )
						{
							ipshoutbox.processInactivePrompt();
						}
						
						if ( ipshoutbox.checkForErrors(s.responseText) )
						{
							ipshoutbox.restoreShout();
							ipshoutbox.submittingShout = false;
							return false;
						}
						
						/**
						 * 1.1.0 RC 1
						 * Everything is okay, reset our tempShout
						 */
						ipshoutbox.tempShout = null;
						
						/**
						 * 1.0.0 Final
						 * Fix no shouts message
						 */
						if ( ipshoutbox.total_shouts <= 0 )
						{
							$('shoutbox-no-shouts-message').hide();
						}
						
						var shoutsDiv = $('shoutbox-shouts-table').down('tbody');
						
						if ( ipshoutbox.shout_order == 'asc' )
						{
							shoutsDiv.update( shoutsDiv.innerHTML + s.responseText );
						}
						else 
						{
							if ( ipshoutbox.total_shouts > 1 )
							{
								shoutsDiv.down('tr').insert( { before: s.responseText } );
							}
							else
							{
								shoutsDiv.update( s.responseText );
							}
						}
						
						// Fix shout classes
						ipshoutbox.rewriteShoutClasses();
						
						// Remove the block
						ipshoutbox.submittingShout = false;
						
						// Setup latest ID
						ipshoutbox.shoutsGetLastID();
						
						if ( ipshoutbox.can_use && ipshoutbox.my_prefs['display_refresh_button'] == 1 )
						{
							$('shoutbox-refresh-button').show();
						}
						
						/**
						 * Beta 2
						 * Restart timer
						 */
						ipshoutbox.timeoutShouts = setTimeout("ipshoutbox.shoutAjax.reloadShouts(true)", ipshoutbox.shouts_refresh);
						
						ipshoutbox.shoutsScrollThem();
					}
				}
			);
		}	
	},
	
	reloadShouts: function(doLoad)
	{
		Debug.info("reloadShouts Called");
		
		/**
		 * Beta 2
		 * Fix timeout with clearTimeout
		 */
		clearTimeout( ipshoutbox.timeoutShouts );
		
		// If for any odd chance we get there while submitting block it!
		if ( ipshoutbox.submittingShout )
		{
			return false;
		}
		
		doLoad = (doLoad == true) ? true : false;
		
		if ( doLoad )
		{
			var globalOn = ( ipshoutbox.global_on ) ? '&global=1' : '';
			
			// Setup latest ID
			ipshoutbox.shoutsGetLastID();
			
			// Hide refresh button?
			if ( ipshoutbox.hide_refresh )
			{
				$('shoutbox-refresh-button').hide();
			}
			
			new Ajax.Request( ipshoutbox.baseUrl + 'type=getShouts&secure_key=' + ipb.vars[ 'secure_hash' ] + '&lastid=' + ipshoutbox.last_shout_id + globalOn,
				{
					method: 'get',
					encoding: ipb.vars['charset'],
					onSuccess: function(s)
					{
						ipshoutbox.shoutAjax.updateShouts(s.responseText);
						return true;
					}
				}
			);
		}
		else if ( ipshoutbox.isInactive() )
		{
			Debug.write("reloadShouts: shoutbox inactive, timer is activated again clicking I'm back now");
			return false;
		}
		
		/**
		 * Beta 2
		 * Set again timeout if we are not inactive :O
		 */
		ipshoutbox.timeoutShouts = setTimeout("ipshoutbox.shoutAjax.reloadShouts(true)", ipshoutbox.shouts_refresh);
	},
	
	updateShouts: function(response)
	{
		/* Show again the button! */
		if ( ipshoutbox.can_use && ipshoutbox.my_prefs['display_refresh_button'] == 1 )
		{
			$('shoutbox-refresh-button').show();
		}
		
		/** And reset timer **/
		ipshoutbox.shoutAjax.reloadShouts();
		
		// Finally update shouts
		// Leave the code above there or it causes a bug | Terabyte 
		if ( response != '' && response != '<!--nothing-->' )
		{
			if ( ipshoutbox.checkForErrors(response) )
			{
				return false;
			}
			
			/**
			 * 1.0.0 Final
			 * Fix no shouts message
			 */
			if ( ipshoutbox.total_shouts <= 0 )
			{
				$('shoutbox-no-shouts-message').hide();
			}
			
			var shoutsDiv = $('shoutbox-shouts-table').down('tbody');
			
			if ( ipshoutbox.shout_order == 'asc' )
			{
				shoutsDiv.update( shoutsDiv.innerHTML + response );
			}
			else 
			{
				if ( ipshoutbox.total_shouts > 1 )
				{
					shoutsDiv.down('tr').insert( { before: response } );
				}
				else
				{
					shoutsDiv.update( response );
				}
			}
		
			// Fix shout classes
			ipshoutbox.rewriteShoutClasses(false);
			
			// Setup latest ID
			ipshoutbox.shoutsGetLastID();
			
			ipshoutbox.shoutsScrollThem();
		}
	},
	
	reloadMembers: function(doLoad)
	{
		Debug.info("reloadMembers Called");
		
		/**
		 * 1.1.0 Alpha
		 * Fix timeout with clearTimeout
		 */
		clearTimeout( ipshoutbox.timeoutMembers );
		
		doLoad = (doLoad == true) ? true : false;
		
		if ( doLoad )
		{
			new Ajax.Request( ipshoutbox.baseUrl + 'type=getMembers&secure_key=' + ipb.vars[ 'secure_hash' ],
				{
					method: 'get',
					evalJSON: 'force',
					encoding: ipb.vars['charset'],
					onSuccess: function(d)
					{
						if( Object.isUndefined( d.responseJSON ) )
						{
							ipshoutbox.produceError('loading_members_viewing');
						}
						else
						{
							/* Update stats! =D */
							$('shoutbox-active-total').update( d.responseJSON['TOTAL'] );
							$('shoutbox-active-member').update( d.responseJSON['MEMBERS'] );
							$('shoutbox-active-guests').update( d.responseJSON['GUESTS'] );
							$('shoutbox-active-anon').update( d.responseJSON['ANON'] );
							
							/* Sort out names */
							if ( d.responseJSON['NAMES'] == '' )
							{
								$('shoutbox-active-names').hide();
							}
							else
							{
								$('shoutbox-active-names').update( d.responseJSON['NAMES'].join(', ') ).show();
							}
							
							/* Highligh the names! */
							if ( ipshoutbox.enable_fade )
							{
								new Effect.Highlight( $('shoutbox-active-names'),
									{
										startcolor: '#ffff99'
									}
								);
							}
						}
						
						ipshoutbox.shoutAjax.reloadMembers();
						return true;
					}
				}
			);
		}
		else if ( ipshoutbox.isInactive() )
		{
			Debug.write("reloadMembers: shoutbox inactive, timer is activated again clicking I'm back now");
			return false;
		}
		
		/**
		 * 1.1.0 Alpha
		 * Set again timeout if we are not inactive :O
		 */
		ipshoutbox.timeoutMembers = setTimeout("ipshoutbox.shoutAjax.reloadMembers(true)", ipshoutbox.members_refresh);
	},
	
	myPrefsLoad: function()
	{
		if ( ipshoutbox.myMemberID <= 0 )
		{
			return false;
		}
		
		if ( ipshoutbox.blur )
		{
			ipshoutbox.blur();
		}
		
		if ( ipshoutbox.global_on )
		{
			ipshoutbox.setActionAndReload('myprefs');
			return false;
		}
		
		ipshoutbox.updateLastActivity();
		
		/* Popup already exist? */
		if ( $('myPrefs_popup') )
		{
			ipshoutbox.setupPopup('preferences');
			return true;
		}
		
		new Ajax.Request( ipshoutbox.baseUrl + 'type=prefs&action=load&secure_key=' + ipb.vars['secure_hash'],
			{
				method: 'get',
				encoding: ipb.vars['charset'],
				onSuccess: function(s)
				{
					if ( ipshoutbox.checkForErrors(s.responseText) )
					{
						return false;
					}
					
					ipshoutbox.preferences = new ipb.Popup( 'myPrefs',
									{
										type: 'pane',
										modal: true,
										w: '550px',
										h: 'auto',
										initial: s.responseText,
										hideAtStart: true,
										close: '.cancel'
									}
								);
					
					/* Setup close button */
					$('myPrefs_close').stopObserving();
					$('myPrefs_close').observe( 'click',
						function()
						{
							ipshoutbox.closePopup('preferences');
						}
					);
					
					ipshoutbox.setupPopup('preferences');
				}
			}
			
		);
		
		return false;
	},
	
	myPrefsSave: function()
	{
		if ( !ipshoutbox.myMemberID || ipshoutbox.global_on )
		{
			return false;
		}

		ipshoutbox.updateLastActivity();

		// Update our prefs on-fly
		ipshoutbox.my_prefs['global_display']         = ($('my_prefs_gsb_y').checked) ? 1 : 0;
		ipshoutbox.my_prefs['enter_key_shout']        = ($('my_prefs_ets_y').checked) ? 1 : 0;
		ipshoutbox.my_prefs['enable_quick_commands']  = ($('my_prefs_eqc_y').checked) ? 1 : 0;
		ipshoutbox.my_prefs['display_refresh_button'] = ($('my_prefs_drb_y').checked) ? 1 : 0;
		
		// Setup status
		ipshoutbox.popupUpdateStatus('saving_prefs');
		
		new Ajax.Request( ipshoutbox.baseUrl + 'type=prefs&action=save&secure_key=' + ipb.vars['secure_hash'],
			{
				method: 'post',
				encoding: ipb.vars['charset'],
				parameters: {
					prefs_gsb : ipshoutbox.my_prefs['global_display'],
					prefs_ets : ipshoutbox.my_prefs['enter_key_shout'],
					prefs_eqc : ipshoutbox.my_prefs['enable_quick_commands'],
					prefs_drb : ipshoutbox.my_prefs['display_refresh_button']
				},
				onSuccess: function(s)
				{
					if ( ipshoutbox.checkForErrors(s.responseText, 'my_prefs_loaded') )
					{
						return false;
					}
					
					// Run code and update prefs
					s.responseText.evalScripts();
					
					// Stop observing so we don't have double onclick events later
					$('myprefs_save').stopObserving();
					$('myprefs_restore').stopObserving();
					
					ipshoutbox.preferences.hide();
					ipshoutbox.in_prefs = false;
				}
			}
			
		);
		
		return false;
	},
	
	myPrefsRestore: function()
	{
		if ( !ipshoutbox.myMemberID || ipshoutbox.global_on )
		{
			return false;
		}

		ipshoutbox.updateLastActivity();

		// Setup status
		ipshoutbox.popupUpdateStatus('processing');
		
		new Ajax.Request( ipshoutbox.baseUrl + 'type=prefs&action=restore&secure_key=' + ipb.vars['secure_hash'],
			{
				method: 'get',
				encoding: ipb.vars['charset'],
				onSuccess: function(s)
				{
					if ( ipshoutbox.checkForErrors(s.responseText, 'my_prefs_loaded') )
					{
						return false;
					}
					
					// Update popup and update prefs
					$('myPrefs_inner').update( s.responseText );
					
					ipshoutbox.preferences.hide();
					ipshoutbox.in_prefs = false;
				}
			}
		);
		
		return false;
	},
	
	myPrefsHeightSave: function( newHeight )
	{
		if ( !ipshoutbox.myMemberID || ipshoutbox.global_on )
		{
			return false;
		}

		ipshoutbox.updateLastActivity();
		
		new Ajax.Request( ipshoutbox.baseUrl + 'type=prefs&action=appHeight',
			{
				method: 'post',
				parameters: {
					height:		newHeight,
					secure_key:	ipb.vars['secure_hash']
				},
				onSuccess: function(s)
				{
					if ( ipshoutbox.checkForErrors(s.responseText) )
					{
						return false;
					}
					
					//All done succesfully
				}
			}
		);
		
		return false;
	},
	
	myPrefsGlobalHeightSave: function( newHeight )
	{
		if ( !ipshoutbox.myMemberID || !ipshoutbox.global_on )
		{
			return false;
		}

		ipshoutbox.updateLastActivity();
		
		new Ajax.Request( ipshoutbox.baseUrl + 'type=prefs&action=globalHeight',
			{
				method: 'post',
				parameters: {
					height:		newHeight,
					secure_key:	ipb.vars['secure_hash']
				},
				onSuccess: function(s)
				{
					if ( ipshoutbox.checkForErrors(s.responseText) )
					{
						return false;
					}
					
					//All done succesfully
				}
			}
		);
		
		return false;
	},
	
	/**
	 * Load moderator popup for a shout
	 */
	modOptsLoadShout: function(id)
	{
		if ( (!ipshoutbox.moderator && !ipshoutbox.can_edit) || ipshoutbox.mod_in_action )
		{
			return false;
		}
		
		if ( ipshoutbox.global_on )
		{
			ipshoutbox.setActionAndReload('mod|shout|'+id);
			return false;
		}
		
		/* We are in the archive? Let's close it then */
		if ( ipshoutbox.in_archive )
		{
			ipshoutbox.closePopup('archive');
		}
		
		ipshoutbox.updateLastActivity();
		
		new Ajax.Request( ipshoutbox.baseUrl + 'type=mod&action=loadShout&id=' + id + '&secure_key=' + ipb.vars['secure_hash'],
			{
				method: 'get',
				onSuccess: function(s)
				{
					ipshoutbox.shoutAjax.modOptsShow(s.responseText);
				}
			}
		);
		
		return false;
	},
	
	/**
	 * Load moderator popup for a member
	 */
	modOptsLoadMember: function(id)
	{
		if ( !ipshoutbox.moderator || ipshoutbox.mod_in_action )
		{
			return false;
		}
		
		if ( ipshoutbox.global_on )
		{
			ipshoutbox.setActionAndReload('mod|member|number|'+id);
			return false;
		}
		
		/* We are in the archive? Let's close it then */
		if ( ipshoutbox.in_archive )
		{
			ipshoutbox.closePopup('archive');
		}
		
		ipshoutbox.updateLastActivity();
		
		new Ajax.Request( ipshoutbox.baseUrl + 'type=mod&action=loadMember&mid=' + id + '&secure_key=' + ipb.vars['secure_hash'],
			{
				method: 'get',
				encoding: ipb.vars['charset'],
				onSuccess: function(s)
				{
					ipshoutbox.shoutAjax.modOptsShow(s.responseText);
				}
			}
		);
		
		return false;
	},
	
	/**
	 * Show the moderation popup
	 */
	modOptsShow: function(response)
	{
		if ( (!ipshoutbox.moderator && !ipshoutbox.can_edit) || ipshoutbox.mod_in_action )
		{
			return false;
		}
		
		if ( ipshoutbox.checkForErrors(response) )
		{
			return false;
		}
		
		/* Popup already exist, show it! */
		if ( $('modOpts_popup') )
		{
			$('modOpts_inner').update( response );
		}
		else
		{
			ipshoutbox.modOpts	= new ipb.Popup( 'modOpts',
							{
								type: 'pane',
								modal: true,
								w: '550px',
								h: 'auto',
								initial: response,
								hideAtStart: true,
								close: '.cancel'
							}
						);
			
			/* Setup close button */
			$('modOpts_close').stopObserving();
			$('modOpts_close').observe( 'click',
				function()
				{
					ipshoutbox.closePopup('moderator');
				}
			);
		}
		
		ipshoutbox.setupPopup('moderator');
	},
	
	/**
	 * Do a moderator action
	 */
	modOptsDo: function(event)
	{
		if ( (!ipshoutbox.moderator && !ipshoutbox.can_edit) || ipshoutbox.mod_in_action )
		{
			return false;
		}
		
		if ( ipshoutbox.blur )
		{
			ipshoutbox.blur();
		}
		
		var element = Event.element(event);
		
		/**
		 * 1.1.0 Alpha
		 * 
		 * Leave this if in place or clicking on images in the menu won't trigger the proper id
		 */
		if ( element.tagName == 'IMG' )
		{
			element = element.up('li');
		}
		
		var modID = (ipshoutbox.mod_shout_id) ? ipshoutbox.mod_shout_id : ipshoutbox.mod_member_id;
		var type  = (ipshoutbox.mod_shout_id) ? 'shout' : 'member';
		
		// Se to true by default and set false if needed
		ipshoutbox.mod_in_action = true;
		
		/* Save our command and compare it with our choices */
		ipshoutbox.mod_command = element.id.sub( '_shout', '' );
		
		switch ( ipshoutbox.mod_command )
		{
			case 'edit':
			case 'delete':
			case 'deleteAll':
			case 'ban':
			case 'unban':
			case 'removeMod':
			case 'editHistory':
				break;
			default:
				ipshoutbox.mod_in_action = false;
				ipshoutbox.produceError('mod_no_action');
				return false; //Leave this return in place and don't change it with a break!
		}
		
		ipshoutbox.updateLastActivity();
		
		// Setup status
		ipshoutbox.popupUpdateStatus('processing');
		
		new Ajax.Request( ipshoutbox.baseUrl + 'type=mod&action=loadCommand&command=' + ipshoutbox.mod_command + '&modtype=' + type + '&id=' + modID + '&secure_key=' + ipb.vars['secure_hash'],
			{
				method: 'get',
				encoding: ipb.vars['charset'],
				onSuccess: function(s)
				{
					if ( ipshoutbox.checkForErrors(s.responseText) )
					{
						ipshoutbox.mod_in_action = false;
						return false;
					}
					
					/* Update status & content */
					ipshoutbox.popupUpdateStatus('mod_loaded_confirm');
					$('shoutbox-popup-content').update( s.responseText );
					
					/* Setup onclick events */
					if ( ipshoutbox.mod_command == 'edit' )
					{
						// Change width & reposition
						$('modOpts_inner').setStyle('width:750px;');
						ipb.positionCenter( $('modOpts_popup') );
						
						$('mod_edit_shout_confirm').observe('click', ipshoutbox.mod_opts_do_edit_shout );
						$('mod_edit_shout_clear').observe('click', ipshoutbox.shoutAjax.modOptsEditClear );
						$('mod_edit_shout_cancel').observe('click', ipshoutbox.shoutAjax.modOptsEditReset );
					}
					else if ( ipshoutbox.mod_command == 'editHistory' )
					{
						ipshoutbox.mod_in_action = false;
						ipshoutbox.popupUpdateStatus('processed');
					}
					else
					{
						if ( $('confirm_option_yes') )
						{
							$('confirm_option_yes').observe('click', ipshoutbox.shoutAjax.modOptsDoConfirm.bindAsEventListener(this) );
						}
						if ( $('confirm_option_no') )
						{
							$('confirm_option_no').observe('click', ipshoutbox.shoutAjax.modOptsDoConfirm.bindAsEventListener(this) );
						}
					}
				}
			}
		);
	},
	
	modOptsEditClear: function()
	{
		if ( !ipshoutbox.moderator && !ipshoutbox.can_edit )
		{
			return false;
		}
		
		ipshoutbox.updateLastActivity();
		
		// Update editor ^.^
		ipb.editors[ ipshoutbox.mod_editor_id ].editor_write_contents("");
		ipb.editors[ ipshoutbox.mod_editor_id ].editor_check_focus();
	},
	
	modOptsEditReset: function()
	{
		ipshoutbox.popupModeratorReset();
		
		$('modOpts_inner').setStyle('width:550px;');
		ipb.positionCenter( $('modOpts_popup') );
	},
	
	modOptsDoConfirm: function(event)
	{
		if ( !ipshoutbox.moderator || !ipshoutbox.mod_in_action )
		{
			return false;
		}
	
		ipshoutbox.updateLastActivity();
		
		/* Get element from event */
		var element = Event.element(event);
		
		// Action confirmed?
		if ( element.id == 'confirm_option_yes' )
		{
			var modID = (ipshoutbox.mod_shout_id) ? ipshoutbox.mod_shout_id : ipshoutbox.mod_member_id;
			var type  = (ipshoutbox.mod_shout_id) ? 'shout' : 'member';
			
			// Setup status
			ipshoutbox.popupUpdateStatus('processing');
			
			new Ajax.Request( ipshoutbox.baseUrl + 'type=mod&action=performCommand&command=' + ipshoutbox.mod_command + '&modtype=' + type + '&id=' + modID + '&secure_key=' + ipb.vars['secure_hash'],
				{
					method: 'get',
					onSuccess: function(s)
					{
						if ( ipshoutbox.checkForErrors(s.responseText) )
						{
							ipshoutbox.mod_in_action = false;
							return false;
						}
						
						ipshoutbox.popupUpdateStatus('processed');
						
						// We have any action to perform?
						switch ( ipshoutbox.mod_command )
						{
							case 'delete':
								$('shout-row-'+ipshoutbox.mod_shout_id).remove();
								
								ipshoutbox.rewriteShoutClasses();
								
								// Close popup, no need for it since the shout has been deleted
								ipshoutbox.closePopup('moderator');
								
								/** Update live total shouts count **/
								ipshoutbox.updateTotalShouts( ipshoutbox.total_shouts - 1 );
								break;
							case 'deleteAll':
								var ids = s.responseText.split(",");
								
								ids.each( function(id) {
									if ( $('shout-row-'+id) )
									{
										$('shout-row-'+id).remove();
									}
								});
								
								ipshoutbox.closePopup('moderator');
								
								// Reload the page if we deleted all shouts >.<!
								if ( $('shoutbox-shouts-table').down('tbody').childElements().length < 1 )
								{
									window.location=window.location;
								}
								
								ipshoutbox.rewriteShoutClasses();
								
								/** Update live total shouts count **/
								ipshoutbox.updateTotalShouts( ipshoutbox.total_shouts - ids.length );
								break;
							case 'ban':
								$('ban_shout').hide();
								
								if ( $('unban_shout') )
								{
									$('unban_shout').show();
								}
								break;
							case 'unban':
								$('unban_shout').hide();
								
								if ( $('ban_shout') )
								{
									$('ban_shout').show();
								}
								break;
							default:
								break;
						}
						
						ipshoutbox.popupModeratorReset();
						//Update properly our status after the reset
						ipshoutbox.popupUpdateStatus( s.responseText, true );
					}
				}
			);
		}
		else
		{
			// Reset our popup so =O
			ipshoutbox.popupModeratorReset();
		}
	},
	
	/**
	 * Edit shouts from the global shoutbox! :D
	 * Added in 1.1.0 Final
	 */
	editShout: function(id)
	{
		if ( !id || !ipshoutbox.can_edit || ipshoutbox.mod_in_action )
		{
			return false;
		}
		
		// Se to true by default and set false if needed
		ipshoutbox.mod_in_action = true;
		
		ipshoutbox.updateLastActivity();
		
		new Ajax.Request( ipshoutbox.baseUrl + 'type=mod&action=loadCommand&command=edit&modtype=shout&id=' + id + '&global=1&secure_key=' + ipb.vars['secure_hash'],
			{
				method: 'get',
				encoding: ipb.vars['charset'],
				onSuccess: function(s)
				{
					if ( ipshoutbox.checkForErrors(s.responseText) )
					{
						ipshoutbox.mod_in_action = false;
						return false;
					}
					
					/* Popup already exist, show it! */
					if ( $('editShout_popup') )
					{
						$('editShout_inner').update( response );
					}
					else
					{
						ipshoutbox.editShoutPopup = new ipb.Popup( 'editShout',
										{
											type: 'pane',
											modal: true,
											w: '750px',
											h: 'auto',
											initial: s.responseText,
											hideAtStart: true,
											close: '.cancel'
										}
									);
						
						/* Hide close button */
						$('editShout_close').stopObserving();
						$('editShout_close').observe( 'click',
							function()
							{
								ipshoutbox.closePopup('editShout');
							}
						);
					}
					
					/* Run setup */
					ipshoutbox.setupPopup('editShout');
				}
			}
		);
	}
};

ipshoutbox.shoutAjax.init();