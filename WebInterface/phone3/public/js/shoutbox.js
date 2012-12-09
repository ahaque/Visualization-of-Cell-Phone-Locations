/**
 * IP.Shoutbox 1.1.0
 *  - IPS Community Project Developers
 *
 * JavaScript
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
 * Loader class
 *
 * Loads the needed shoutbox files
 */
var shoutboxLoader = {
	
	require: function( name )
	{
		document.write("<script type='text/javascript' src='" + name + ".js'></script>");
	},
	
	boot: function()
	{
		$A( document.getElementsByTagName("script") ).findAll(
			function(s)
			{
  				return (s.src && s.src.match(/shoutbox\.js(\?.*)?$/))
			}
		).each( 
			function(s) {
  				var path = s.src.replace(/shoutbox\.js(\?.*)?$/,'');
  				var includes = s.src.match(/\?.*load=([a-z0-9_,]*)/);
				if( includes[1] )
				{
					includes[1].split(',').each(
						function(include)
						{
							if( include )
							{
								shoutboxLoader.require( path + "shoutbox." + include );
							}
						}
					)
				}
			}
		);
	}
};

var Resize =
{
	obj     : null,
	objloop : null,
	int     : null,

	init: function(o, oRoot, ho, wo, minX, maxX, minY, maxY)
	{
		o.onmousedown = Resize.start;
		o.hmode       = true;
		o.vmode       = true;
		o.root        = (oRoot && oRoot != null) ? oRoot : o;

		if (o.hmode  && isNaN(parseInt(o.root.style.left  ))) o.root.style.left   = "0px";
		if (o.vmode  && isNaN(parseInt(o.root.style.top   ))) o.root.style.top    = "0px";
		if (!o.hmode && isNaN(parseInt(o.root.style.right ))) o.root.style.right  = "0px";
		if (!o.vmode && isNaN(parseInt(o.root.style.bottom))) o.root.style.bottom = "0px";

		o.minX = (typeof minX != 'undefined') ? minX : null;
		o.minY = (typeof minY != 'undefined') ? minY : null;
		o.maxX = (typeof maxX != 'undefined') ? maxX : null;
		o.maxY = (typeof maxY != 'undefined') ? maxY : null;

		o.h_only = false;
		o.w_only = false;

		o.root.Resizing = new Function();

		ho = (ho == true) ? true : false;
		wo = (wo == true) ? true : false;

		if (ho == true)
		{
			o.h_only = true;
		}
		else if (wo == true)
		{
			o.w_only = true;
		}
	},

	start: function(e)
	{
		var o = Resize.obj = Resize.objloop = this;
		e     = Resize.fixE(e);
		var y = parseInt((o.vmode) ? o.root.style.top  : o.root.style.bottom);
		var x = parseInt((o.hmode) ? o.root.style.left : o.root.style.right );

		o.lastMouseX = o.startMouseX = e.clientX;
		o.lastMouseY = o.startMouseY = e.clientY;

		var obj = Resize.data();
		var rec = ipshoutbox.rect(obj.x, obj.y, obj.w, obj.h);
		o.oh    = rec.h;
		o.ow    = rec.w;

		if (o.hmode)
		{
			if (o.minX != null) o.minMouseX	= e.clientX - x + o.minX;
			if (o.maxX != null) o.maxMouseX	= o.minMouseX + o.maxX - o.minX;
		}
		else
		{
			if (o.minX != null) o.maxMouseX = -o.minX + e.clientX + x;
			if (o.maxX != null) o.minMouseX = -o.maxX + e.clientX + x;
		}

		if (o.vmode)
		{
			if (o.minY != null) o.minMouseY	= e.clientY - y + o.minY;
			if (o.maxY != null) o.maxMouseY	= o.minMouseY + o.maxY - o.minY;
		}
		else
		{
			if (o.minY != null) o.maxMouseY = -o.minY + e.clientY + y;
			if (o.maxY != null) o.minMouseY = -o.maxY + e.clientY + y;
		}

		document.onmousemove = Resize.resize;
		document.onmouseup   = Resize.end;

		return false;
	},

	resize : function(e)
	{
		e     = Resize.fixE(e);
		var o = Resize.obj;

		var ey	= e.clientY;
		var ex	= e.clientX;
		var y   = parseInt(o.vmode ? o.root.style.top  : o.root.style.bottom);
		var x   = parseInt(o.hmode ? o.root.style.left : o.root.style.right );
		var h   = parseInt(o.root.offsetHeight);
		var t   = (document.all) ? ipshoutbox.truebody().scrollTop : window.pageYOffset;

		if (o.minX != null) ex = o.hmode ? Math.max(ex, o.minMouseX) : Math.min(ex, o.maxMouseX);
		if (o.maxX != null) ex = o.hmode ? Math.min(ex, o.maxMouseX) : Math.max(ex, o.minMouseX);
		if (o.minY != null) ey = o.vmode ? Math.max(ey, o.minMouseY) : Math.min(ey, o.maxMouseY);
		if (o.maxY != null) ey = o.vmode ? Math.min(ey, o.maxMouseY) : Math.max(ey, o.minMouseY);

		var rec = Resize.data();
		if (Resize.obj.h_only == true)
		{
			ajh     = ey-o.startMouseY;
			rec.h   = o.oh+ajh;

			if (!isNaN(o.root.min_height) && o.root.min_height > 0)
			{
				if (rec.h < o.root.min_height)
				{
					rec.h = o.root.min_height;
				}
			}

			rec.ho = true;
			if (ey >= 0 && ey <= 3)
			{
				Resize.int = setInterval(Resize.resizeloop, 1);
				ipshoutbox.scroll_page_up();
			}
			else
			{
				if (Resize.int)
				{
					clearInterval(Resize.int);
				}
			}

			Resize.obj.root.style['height'] = rec.h+'px';
		}
		else if (Resize.obj.w_only == true)
		{
			ajw     = ex-o.startMouseX;
			rec.w   = o.ow+ajw;

			if (!isNaN(o.root.min_width) && o.root.min_width > 0)
			{
				if (rec.w < o.root.min_width)
				{
					rec.w = o.root.min_width;
				}
			}

			rec.wo = true;
			if (!isNaN(o.root.max_width) && o.root.max_width > 0)
			{
				if (rec.w > o.root.max_width)
				{
					rec.w = o.root.max_width;
				}
			}

			Resize.obj.root.style['width'] = rec.w+'px';
		}
 
		Resize.obj.lastMouseX	        = ex;
		Resize.obj.lastMouseY	        = ey;
		Resize.obj.root.Resizing(rec);

		return false;
	},

	resizeloop : function(e)
	{
		Resize.obj = Resize.obj_loop;
		Resize.resize(e);
	},

	end : function(e)
	{
		document.onmousemove = null;
		document.onmouseup   = null;

		Resize.obj.root.Resize_end(Resize.data());

		Resize.obj     = null;
		Resize.objloop = null;

		if (Resize.int)
		{
			clearInterval(Resize.int);
		}
	},

	data : function(e)
	{
		var oo = Resize.obj.root;
		var xx = Resize.style(oo, 'left');
		var yy = Resize.style(oo, 'top');
		var ww = Resize.style(oo, 'width');
		var hh = Resize.style(oo, 'height');

		if (hh <= 0)
		{
			hh = oo.offsetHeight;
		}

		if (ww <= 0)
		{
			ww = oo.offsetWidth;
		}

		rect = ipshoutbox.rect( xx, yy, ww, hh );
		return rect;
	},

	style : function(o, n)
	{
		if (!o)
		{
			return 0;
		}

		if (!o.style)
		{
			return 0;
		}

		var t;
		var s = o.style;

		try
		{
			eval("t = parseInt(s."+n+", 10);");
		}

		catch(e)
		{
			return 0;
		}

		if (isNaN(t))
		{
			t=0;
		}

		return t;
	},

	fixE : function(e)
	{
		if (typeof e == 'undefined') e = window.event;
		if (typeof e.layerX == 'undefined') e.layerX = e.offsetX;
		if (typeof e.layerY == 'undefined') e.layerY = e.offsetY;
		return e;
	}
};

/**
 * Core javascript class
 *
 * Contains global functions
 */
window.shoutbox = Class.create({
	
	/* Paths to fix cross-site AJAX issues */
	realBaseUrl: 	location.protocol + '//' + location.host,
	realBaseUrlWww:	location.protocol + '//www.' + location.host + location.pathname + '?',
	
	/* Normal Base URL which PHP uses */
	baseUrl:		ipb.vars['base_url'] + 'app=shoutbox&module=ajax&section=coreAjax&',
	
	/**
	 * Member specific variables
	 */
	can_use:			0,
	can_edit: 			0,
	members_refresh:	15,
	shouts_refresh:		30,
	hide_refresh:		1,
	editor_rte:			0,
	editor_height:		'125px',
	flood_limit:		0,
	bypass_flood:		0,
	my_last_shout:		0,
	total_shouts:		0,
	last_shout_id:		0,
	inactive_timeout:	5,	
	
	/**
	 * OTHER: Array variables
	 */
	errors:			[],
	langs:			[],
	month_days:		[31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31],
	langs:			[],
	
	/**
	 * OTHER: Boolean variables
	 */
	global_on:			false,
	enable_cmds:		true,
	mod_in_action:		false,
	moderator:			false,
	_inactive:			false,
	view_archive:		false,
	events_loaded:		false,
	events_rte_loaded:	false,
	submittingShout:	false,
	inactiveWhenPopup:	false,
	animatingGlobal:	false,
	archive_filtering:	false, //Prevent too much filter requests to be done
	
	/**
	 * OTHER: Integer variables
	 */
	mod_shout_id:			0,
	time_minute:			60 * 1000,
	events_load_max_tries:	10,
	events_load_tries:		0,
	
	/**
	 * OTHER: Other variables
	 */
	timeoutShouts:	null,
	timeoutMembers:	null,
	mod_command:	'',
	emoPalette:		null,
	tempShout:		null, //1.1.0 RC1
	
	/**
	 * Javascript functions
	 *
	 * Code below this comment are functions
	 * used within the javascript
	 */
	initialize:	function()
	{
		Debug.write( 'IP.Shoutbox javascript is loading' );
		
		document.observe( 'dom:loaded', function()
		{
			// Is global shoutbox?
			if ( shoutboxGLOBAL )
			{
				ipshoutbox.global_on = true;
			}
			
			/**
			 * Sort out AJAX URLS
			 *
			 * This fixes a bug as AJAX treats http://
			 * and http://www. as two seperate URLs
			 */
			
			/* Without www */
			if ( ipshoutbox.realBaseUrl.match( /^http:\/\/www/ ) && ! ipb.vars['base_url'].match( /^http:\/\/www/ ) )
			{
				ipb.vars['base_url'] = ipb.vars['base_url'].replace( /^http:\/\//, 'http://www.' );
			}
			
			/* With www */
			if ( ipb.vars['base_url'].match( /^http:\/\/www/ ) && ! ipshoutbox.realBaseUrl.match( /^http:\/\/www/ ) )
			{
				location.href = location.href.replace( /^http:\/\//, 'http://www.' );
			}
			
			ipshoutbox.setupShoutbox();
		}.bind(this));
	},
	
	setupShoutbox: function()
	{
		
		
		/* Allowed to use it? =O */
		if ( ipshoutbox.can_use )
		{
			/** Init buttons **/
			var bts = [
				[ 'shoutbox-refresh-button', ipshoutbox.refreshShouts ],
				[ 'shoutbox-submit-button' , ipshoutbox.shoutAjax.submitShout ],
				[ 'shoutbox-clear-button'  , ipshoutbox.clearShout ],
				[ 'shoutbox-myprefs-button', ipshoutbox.shoutAjax.myPrefsLoad ]
			];
			
			/** Global SB?  **/
			if ( ipshoutbox.global_on )
			{
				/* Setup global resizer */
				ipshoutbox.resizeGlobalShouts();
				
				// Create emoticons popup!
				if ( $('shoutbox-smilies-button') )
				{
					ipshoutbox.emoticonsCreatePopup();
				}
				
				/* Add also BBCODE button (emoticons are already taken care of above) */
				bts.push( [ 'shoutbox-bbcode-button' , ipshoutbox.bbcodePopup ] );
				
				// Setup our global SB toggle
				ipshoutbox.setupToggle();
			}
			else
			{
				ipshoutbox.resizeShouts();
				
				/* Resize editor */
				ipb.editors[ ipshoutbox.editor_id ].resize_to(50);
				
				/* Archive time =O */
				if ( ipshoutbox.view_archive && $('load-shoutbox-archive') )
				{
					bts.push( [ 'load-shoutbox-archive' , ipshoutbox.displayArchive ] );
				}
			}
			
			/**
			 * 1.1.0 Beta 2
			 * Setup onlick for all buttons
			 */
			for ( var x=0; x<bts.length; x++ )
			{
				Debug.info("Setting up onlick for ID => " + bts[x][0]);
				if ( $( bts[x][0] ) )
				{
					$( bts[x][0] ).observe('click', bts[x][1]);
				}
			}
		}
		else
		{
			/* Reset some vars to be sure */
			ipshoutbox.myMemberID   = 0;
			ipshoutbox.moderator    = 0;
			ipshoutbox.hide_refresh = 0;
			ipshoutbox.view_archive = false;
		}
		
		/* Fix editor focus on load */
		if ( !ipshoutbox.global_on )
		{
			/* Scroll to the top of the page content */
			Effect.ScrollTo( 'j_content', { duration: 0, offset: 10 } );
		}
		
		/** Sort other things **/
		ipshoutbox.resizeShoutbox();
		ipshoutbox.initEvents();
		
		/**
		 * 1.0.0 Final
		 * Update shouts view (class, scroll, etc)
		 */
		ipshoutbox.updateLastActivity();
		ipshoutbox.updateJSPreferences();
		ipshoutbox.rewriteShoutClasses();
		ipshoutbox.shoutsGetLastID();
		ipshoutbox.shoutsScrollThem();
		
		
		if( ipshoutbox.global_on )
		{
			/**
			 * 1.1.0 RC 1
			 * Let's update live active users on board index! =D
			 */
			if ( $('shoutbox-active-total') && $('shoutbox-active-total').hasClassName('ajax_update') )
			{
				Debug.info("Active users hook FOUND with ajax_update, initializing reloadMembers!");
				ipshoutbox.shoutAjax.reloadMembers(false);
			}
			
			/* Block refresh if collapsed */
			if ( $('category_shoutbox').hasClassName('collapsed') )
			{

				Debug.info("Global shoutbox collapsed, refresh timer blocked!")
				return false;
			}
		}
		else
		{
			/**
			 * 1.1.0 Beta 2
			 * Set timer, we load members on display
			 */
			ipshoutbox.shoutAjax.reloadMembers(false);
		}
		
		/**
		 * 1.0.0 Final
		 * Set timer, we load shouts on display
		 */
		ipshoutbox.shoutAjax.reloadShouts(false);
	},
	
	checkForCommands: function()
	{
		var s = ipshoutbox.getShout(),
			a = s.split(' '),
			m = new Array();
	
		if ( !ipshoutbox.global_on && ipshoutbox.editor_rte )
		{
			s = ipb.editors[ ipshoutbox.editor_id ].clean_html(s);
		}
		
		if ( !ipshoutbox.validCommandSyntax( a[0], true  ))
		{
			return 'doshout';
		}
	
		if ( !ipshoutbox.enable_cmds )
		{
			ipshoutbox.produceError('no_cmds_enabled');
			return null;
		}
		else
		{
			switch (a[0])
			{
				case '/announce':
					//Let's clear the shout there
					ipshoutbox.clearShout();
					
					if ( ipshoutbox.can_access_acp )
					{
						new Ajax.Request( ipshoutbox.baseUrl + 'type=announce',
							{
								method: 'post',
								encoding: ipb.vars['charset'],
								parameters: {
									secure_key: ipb.vars['secure_hash'],
									announce: s.substring(9)
								},
								onSuccess: function(s)
								{
									if ( ipshoutbox.checkForErrors(s.responseText) )
									{
										return false;
									}
									
									if ( s.responseText == '<!--nothing-->' || s.responseText == '' )
									{
										$('shoutbox-announcement-row').hide();
									}
									else
									{
										$('shoutbox-announcement-row').show();
										$('shoutbox-announcement-text').update( s.responseText );
									}
									
									return true;
								}
							}
						);
					}
					else
					{
						ipshoutbox.produceError('no_acp_access');
					}
					break;
				/**
				 * RC1
				 * Prune old shouts
				 */
				case '/prune':
					if ( ipshoutbox.can_access_acp )
					{
						var days = s.substring(6);
						
						if ( !isNaN(days) && days != '' )
						{
							ipshoutbox.clearShout();
							
							new Ajax.Request( ipshoutbox.baseUrl + 'type=prune',
								{
									method: 'post',
									encoding: ipb.vars['charset'],
									parameters: {
										secure_key:	ipb.vars['secure_hash'],
										days:		days
									},
									onSuccess: function(s)
									{
										if ( ipshoutbox.checkForErrors(s.responseText) )
										{
											return false;
										}
										
										/**
										 * 1.1.0 Final
										 * 
										 * Reset this value just in case
										 * the page is not reloaded
										 */
										ipshoutbox.last_shout_id = 0;
										
										ipshoutbox.actionTaken(s.responseText);
										
										/**
										 * 1.1.0 Beta 1
										 * Reload page if shouts are pruned
										 */
										window.location=window.location;
									}
								}
							);
						}
						else
						{
							ipshoutbox.produceError('prune_invalid_number');
						}
					}
					else
					{
						ipshoutbox.clearShout();
						ipshoutbox.produceError('no_acp_access');
					}
					break;
				/**
				 * RC1
				 * Ban members
				 */
				case '/ban':
					if (ipshoutbox.mod_perms['m_ban_members'])
					{
						var banName = s.substring(4);
						
						if ( banName != null && banName != '' )
						{
							ipshoutbox.clearShout();
							
							new Ajax.Request( ipshoutbox.baseUrl + 'type=ban',
								{
									method: 'post',
									encoding: ipb.vars['charset'],
									parameters: {
										secure_key:	ipb.vars['secure_hash'],
										name:		banName
									},
									onSuccess: function(s)
									{
										if ( ipshoutbox.checkForErrors(s.responseText) )
										{
											return false;
										}
										
										ipshoutbox.actionTaken(s.responseText);
									}
								}
							);
						}
						else
						{
							ipshoutbox.produceError('mod_invalid_name');
						}
					}
					else
					{
						ipshoutbox.clearShout();
						ipshoutbox.produceError('mod_no_perm');
					}
					break;
				/**
				 * RC1
				 * Unban members
				 */
				case '/unban':
					if (ipshoutbox.mod_perms['m_unban_members'])
					{
						var unbanName = s.substring(6);
						
						if ( unbanName != null && unbanName != '' )
						{
							ipshoutbox.clearShout();
							
							new Ajax.Request( ipshoutbox.baseUrl + 'type=unban',
								{
									method: 'post',
									encoding: ipb.vars['charset'],
									parameters: {
										secure_key:	ipb.vars['secure_hash'],
										name:		unbanName
									},
									onSuccess: function(s)
									{
										if ( ipshoutbox.checkForErrors(s.responseText) )
										{
											return false;
										}
										
										ipshoutbox.actionTaken(s.responseText);
									}
								}
							);
						}
						else
						{
							ipshoutbox.produceError('mod_invalid_name');
						}
					}
					else
					{
						ipshoutbox.clearShout();
						ipshoutbox.produceError('mod_no_perm');
					}
					break;
				case '/refresh':
					ipshoutbox.clearShout();
				 	ipshoutbox.shoutAjax.reloadShouts(true);
					break;
				case '/prefs':
					ipshoutbox.clearShout();
					if (ipshoutbox.myMemberID > 0)
					{
						if ( ipshoutbox.global_on )
						{
							ipshoutbox.setActionAndReload('myprefs');
						}
						else
						{
							ipshoutbox.shoutAjax.myPrefsLoad();
						}
					}
					else
					{
						ipshoutbox.produceError('prefs_login');
					}
					break;
				case '/archive':
					ipshoutbox.clearShout();
					
					if (ipshoutbox.view_archive)
					{
						if (ipshoutbox.global_on)
						{
							ipshoutbox.setActionAndReload('archive');
						}
						else
						{
							ipshoutbox.displayArchive();
						}
					}
					else
					{
						ipshoutbox.produceError('no_archive_perm');
					}
	
					break;
				case '/moderator':
					if (ipshoutbox.moderator)
					{
						if ( ipshoutbox.validCommandSyntax(a[1]) && ipshoutbox.validCommandSyntax(a[2]))
						{
							t = a[1];
							d = a[2];
	
							var modType  = null,
								shoutID  = 0,
								memType  = '',
								memberID = 0;
	
							switch (t)
							{
								case 'shout':
									if (parseInt(d) > 0)
									{
										modType = 'shout';
										shoutID = parseInt(d);
									}
									break;
								case 'member':
									modType = 'member';
									
									if (parseInt(d) > 0)
									{
										memType  = 'number';
										memberID = parseInt(d);
									}
									else
									{
										memType  = 'string';
										memberID = d.toString();
									}
									break;
								default:
									break;
							}
	
							if ( modType != null && modType != '' )
							{
								ipshoutbox.clearShout();
								
								if ( ipshoutbox.global_on )
								{
									ipshoutbox.setActionAndReload('mod|'+modType+'|'+((modType == 'member') ? memType+'|'+memberID : shoutID));
								}
								else
								{
									new Ajax.Request( ipshoutbox.baseUrl + 'type=mod&action=loadQuickCmd',
										{
											method: 'post',
											encoding: ipb.vars['charset'],
											parameters: {
												secure_key:	ipb.vars['secure_hash'],
												modtype:	modType,
												shout:		shoutID,
												memtype:	memType,
												member:		memberID
											},
											onSuccess: function(s)
											{
												if ( ipshoutbox.checkForErrors(s.responseText) )
												{
													return false;
												}
												
												/* Popup already exist, show it! */
												if ( $('modOpts_popup') )
												{
													$('modOpts_inner').update( s.responseText );
												}
												else
												{
													ipshoutbox.modOpts	= new ipb.Popup( 'modOpts',
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
													$('modOpts_close').stopObserving();
													$('modOpts_close').observe( 'click',
														function()
														{
															ipshoutbox.closePopup('moderator');
														}
													);
												}
												
												ipshoutbox.setupPopup('moderator');
											}
										}
									);
								}
							}
							else
							{
								ipshoutbox.produceError('invalid_command');
							}
						}
						else
						{
							ipshoutbox.produceError('invalid_command');
						}
					}
					else
					{
						ipshoutbox.clearShout();
						ipshoutbox.produceError('mod_no_perms');
					}
					
					break;
				default:
					return 'doshout';
			}
		}
	},
	
	validCommandSyntax: function(c, m)
	{
		if (c != '' && typeof(c) != 'undefined' && c != null && c)
		{
			if (m == true)
			{
				c = c.toString();
				if (c.match(new RegExp("^/([a-zA-Z]+?)$", 'i')))
				{
					return true;
				}
				else
				{
					return false;
				}
			}
			else
			{
				return true;
			}
		}
	
		return false;
	},
	
	/**
	 * Rewrites the classes for the shouts
	 * 
	 * @var		boolean		check	Tells the function to skip or perfom the check for in_archive (needed when we reload shouts while the archive popup is open!)
	 */
	rewriteShoutClasses: function(check)
	{
		var table = null,
			check = check == false ? false : true;
			skip  = 0;
		
		if ( check && ipshoutbox.in_archive )
		{
			table = $('shoutbox-archive-shouts');
		}
		else
		{
			table = $('shoutbox-shouts-table');
		}
		
		/* Let's update the rows! =D */
		$A( table.down('tbody').childElements() ).each(
			function(tr)
			{
				skip = ( skip == 0 ) ? 1 : 0;
				
				$A( tr.childElements() ).each(
					function(td)
					{
						/* Remove inline styles added by Highlight */
						td.setStyle({
							backgroundColor: '',
							backgroundImage: ''
						});
						
						if ( skip )
						{
							if ( td.hasClassName('altrow') )
							{
								td.removeClassName('altrow');
							}
						}
						else
						{
							td.addClassName('altrow');
						}
					}
				);
			}
		);
	},
	
	initEvents: function()
	{
		if ( !ipshoutbox.events_loaded )
		{
			ipshoutbox.events_loaded = true;
			document.observe('keypress', ipshoutbox.keypress_handler );
		}
		
		if ( ipshoutbox.can_use && !ipshoutbox.global_on && ipshoutbox.events_load_tries < ipshoutbox.events_load_max_tries && !ipshoutbox.events_rte_loaded)
		{
			ipshoutbox.events_load_tries++;
			
			try
			{
				if ( ipb.editors[ ipshoutbox.editor_id ].is_rte )
				{
					Event.observe( ipb.editors[ ipshoutbox.editor_id ].editor_document, 'keypress', ipshoutbox.keypress_handler_iframe.bindAsEventListener(this) );
					
					if (Prototype.Browser.IE)
					{
						Event.observe( ipb.editors[ ipshoutbox.editor_id ].editor_document, 'keydown', ipshoutbox.keydown_handler_iframe.bindAsEventListener(this) );
					}
				}

				ipshoutbox.events_rte_loaded = true;
			}
	
			catch(e)
			{
				try
				{
					Debug.error("initEvents CATCH 1 error | TRY => "+ipshoutbox.events_load_tries);
					ipshoutbox.initEvents();
				}
				catch(e)
				{
					Debug.error("initEvents CATCH 2 error | TRY => "+ipshoutbox.events_load_tries);
				}
			}
		}
	},
	
	displayArchive: function(e)
	{
		if ( !ipshoutbox.view_archive || ipshoutbox.global_on )
		{
			return false;
		}

		if ( ipshoutbox.in_prefs || ipshoutbox.in_archive || ipshoutbox.in_mod )
		{
			Debug.write("displayArchive: in_prefs, in_archive or in_mod are set to true so this check fails and this should never happen!");
			return false;
		}
		
		if ( typeof( e ) != 'undefined' )
		{
			Event.stop(e);
		}
		
		if ( $('archiveArea_popup') )
		{
			ipshoutbox.setupPopup('archive');
			return true;
		}
		
		new Ajax.Request( ipshoutbox.baseUrl + '&type=archive&action=load&secure_key=' + ipb.vars['secure_hash'],
			{
				method: 'get',
				encoding: ipb.vars['charset'],
				onSuccess: function(s)
				{
					ipshoutbox.archiveArea = new ipb.Popup(
						'archiveArea',
						{
							type: 'pane',
							modal: true,
							w: '700px',
							h: '450px',
							initial: s.responseText,
							hideAtStart: true,
							close: '.cancel'
						}
					);
					
					/* Setup close button */
					$('archiveArea_close').stopObserving();
					$('archiveArea_close').observe( 'click',
						function()
						{
							ipshoutbox.closePopup('archive');
						}
					);
					
					/* Setup the filter button */
					$('shoutbox-archive-filter-button').observe( 'click', ipshoutbox.archiveDoFilter );
					
					/* Setup menu and onclick for archive filters */
					new ipb.Menu( $( 'shoutbox_archive_filters' ), $( 'shoutbox_archive_filters_menucontent' ) );
					
					$('filter_today').observe('click', ipshoutbox.archiveQuickFilters.bindAsEventListener(this) );
					$('filter_yesterday').observe('click', ipshoutbox.archiveQuickFilters.bindAsEventListener(this) );
					$('filter_month').observe('click', ipshoutbox.archiveQuickFilters.bindAsEventListener(this) );
					$('filter_all').observe('click', ipshoutbox.archiveQuickFilters.bindAsEventListener(this) );
					$('filter_mine').observe('click', ipshoutbox.archiveQuickFilters.bindAsEventListener(this) );
					
					/* Let's "fix" the ugly scrollbar removing overflow:auto */
					$('archiveArea_inner').setStyle( { overflow: 'hidden' } );
					
					ipshoutbox.setupPopup('archive');
				}
			}
		);
	},
	
	produceError: function( error )
	{
		var errorDiv  = 'app';
		
		/* Global shoutbox? */
		if ( ipshoutbox.global_on )
		{
			if ( ipshoutbox.can_edit && ipshoutbox.mod_in_action )
			{
				errorDiv = 'editShout';
			}
			else
			{
				errorDiv = 'glb';
			}
		}
		else if ( ipshoutbox.in_prefs )
		{
			errorDiv = 'myprefs';
		}
		else if ( ipshoutbox.in_mod )
		{
			errorDiv = 'moderator';
		}
		else if ( ipshoutbox.in_archive )
		{
			errorDiv = 'archive';
		}
		
		Debug.error("produceError: errorDiv => "+errorDiv+"  |  Error string => "+error);
		
		/* Got a key to find the string? */
		if ( ipshoutbox.errors[ error ] )
		{
			error = ipshoutbox.errors[ error ];
		}
		
		/* Div exists? Display it! =D */
		if ( $('shoutbox-inline-error-'+errorDiv) )
		{
			$('shoutbox-inline-error-'+errorDiv).update( error ).show();
			
			// Set timer to hide it
			setTimeout("$('shoutbox-inline-error-" + errorDiv + "').hide()", 2000);
		}
		else
		{
			alert( error );
		}
	},
	
	getShout: function()
	{
		var d = '';
		
		if ( ipshoutbox.global_on )
		{
			d = $('shoutbox-global-shout').value;
		}
		else
		{
			d = ipb.editors[ ipshoutbox.editor_id ].editor_get_contents();
		}
	
		d = d.strip();
		
		while (d.match(new RegExp("^(.+?)<br>$", 'i')))
		{
			d = d.replace(new RegExp("^(.+?)<br>$", 'i'), '$1');
		}
	
		d = d.strip();
		
		if (d.toLowerCase().substring(d.length-4, d.length) == '<br>')
		{
			d = d.substring(0, d.length-4);
		}
	
		d = d.strip();
		
		return d;
	},
	
	clearShout: function()
	{
		ipshoutbox.updateLastActivity();
		
		if ( ipshoutbox.global_on )
		{
			// Save our shout if we get errors
			ipshoutbox.tempShout = $('shoutbox-global-shout').getValue();
			
			$('shoutbox-global-shout').setValue("");
			$('shoutbox-global-shout').focus();
		}
		else
		{
			// Save our shout if we get errors
			ipshoutbox.tempShout = ipb.editors[ ipshoutbox.editor_id ].editor_get_contents();
			
			ipb.editors[ ipshoutbox.editor_id ].editor_write_contents("");
			ipb.editors[ ipshoutbox.editor_id ].editor_check_focus();
		}
	},
	
	restoreShout: function()
	{
		if ( ipshoutbox.tempShout != null )
		{
			if ( ipshoutbox.global_on )
			{
				$('shoutbox-global-shout').setValue( ipshoutbox.tempShout );
				$('shoutbox-global-shout').focus();
			}
			else
			{
				ipb.editors[ ipshoutbox.editor_id ].editor_write_contents( ipshoutbox.tempShout );
				ipb.editors[ ipshoutbox.editor_id ].editor_check_focus();
			}
		}
	},
	
	resizeShouts: function()
	{
		var ss = $('shoutbox-shouts'),
			sr = $('shouts-resizer');

		if ( Prototype.Browser.IE )
		{
			sr.style.marginTop    = '5px';
			ss.style.marginBottom = '-6px';
	
			$('shoutbox-shouts-td').setStyle( 'padding-bottom:0;' );
		}
	
		if ( ! ipshoutbox.myMemberID)
		{
			sr.style.cursor = 'default';
			return false;
		}
	
		Resize.init( sr, ss, true, false );
		
		ss.min_height = 100;
		ss.Resizing   = function()
		{
			ipshoutbox.shoutsScrollThem();
		}
		
		ss.Resize_end = function(data)
		{
			ipshoutbox.shoutAjax.myPrefsHeightSave( parseInt(data.h) );
		}
	},
	
	getTimestamp: function()
	{
		var d = new Date(),
			t = d.getTime();
	
		return Math.floor( t / 1000 );
	},
	
	resizeShoutbox: function()
	{
		if ( ipshoutbox.global_on )
		{
			return false;
		}
	
		var w = 0,
			o = $('shoutbox-wrapper');
	
		if (typeof(parent.window.innerWidth) == 'number')
		{
			w = parent.window.innerWidth;
		}
		else if (parent.document.documentElement && parent.document.documentElement.clientWidth)
		{
			w = parent.document.documentElement.clientWidth;
		}
		else if (parent.document.body && parent.document.body.clientWidth)
		{
			w = parent.document.body.clientWidth;
		}
	
		if (o && w < 1400)
		{
			o.setStyle( 'width:100%;' );
		}
	},
	
	updateJSPreferences: function()
	{
		if ( !ipshoutbox.myMemberID || !ipshoutbox.can_use )
		{
			return false;
		}
		
		if ( ipshoutbox.my_prefs['display_refresh_button'] == 1 )
		{
			$('shoutbox-refresh-button').show();
		}
		else
		{
			$('shoutbox-refresh-button').hide();
		}
		
		ipshoutbox.enable_cmds = ( ipshoutbox.my_prefs['enable_quick_commands'] == 1 ) ? true : false;
	},
	
	shoutsGetLastID: function()
	{
		var tempLastID = 0;
		
		$A( $('shoutbox-shouts-table').down('tbody').childElements() ).each(
			function(tr)
			{
				tempLastID = parseInt(tr.id.substring(10));
				
				if ( tempLastID > ipshoutbox.last_shout_id )
				{
					ipshoutbox.last_shout_id = tempLastID;
				}
			}
		);
		
		return ipshoutbox.last_shout_id;
	},
	
	shoutsScrollThem: function()
	{
		var area = $( 'shoutbox-shouts' );
		
		if ( ipshoutbox.shout_order == 'asc' )
		{
			area.scrollTop = area.scrollHeight - parseInt( area.getHeight() ) + 500;
		}
		else
		{
			area.scrollTop = 0;
		}
	},
	
	isInactive: function()
	{
		Debug.write("isInactive called");
		
		if ( ipshoutbox._inactive )
		{
			return true;
		}
	
		var diff     = parseInt( ipshoutbox.getTimestamp() - ipshoutbox.last_active ),
			myMin    = ( diff / 60 ) * ipshoutbox.time_minute,
			checkMin = ipshoutbox.inactive_timeout * ipshoutbox.time_minute;
		
		if ( myMin >= checkMin )
		{
			ipshoutbox.displayInactivePrompt();
			return true;
		}
		else
		{
			return false;
		}
	},
	
	displayInactivePrompt: function()
	{
		/** Do some common things =O **/
		ipshoutbox._inactive = true;
		clearTimeout( ipshoutbox.timeoutShouts );
		clearTimeout( ipshoutbox.timeoutMembers );
		
		// Which shoutbox? :D
		if ( ipshoutbox.global_on )
		{
			$('shoutbox-shouts-table').hide();
			
			$('shoutbox-inactive-prompt').setStyle( { height: $('shoutbox-shouts').getStyle('height') } );
			$('shoutbox-inactive-prompt').show();
		}
		else
		{
			// Do we have another popup open already?
			if ( ipshoutbox.in_prefs || ipshoutbox.in_mod || ipshoutbox.in_archive )
			{
				ipshoutbox.inactiveWhenPopup = true;
			}
			else
			{
				if ( $('inactivePrompt_popup') )
				{
					ipshoutbox.inactivePrompt.show();
				}
				else
				{
					ipshoutbox.inactivePrompt = new ipb.Popup( 'inactivePrompt',
						{
							type: 'pane',
							modal: true,
							w: '450px',
							initial: "<table>" + $('shoutbox-inactive-prompt').innerHTML + "</table>",
							hideAtStart: false,
							close: '.close'
						}
					);
					
					$('inactivePrompt_close').hide();
				}
			}
		}
	},
	
	processInactivePrompt: function()
	{
		if ( !ipshoutbox._inactive )
		{
			return false;
		}
		
		/** Do some common things =O **/
		ipshoutbox._inactive = false;
		ipshoutbox.updateLastActivity();
		
		// Which shoutbox? :D
		if ( ipshoutbox.global_on )
		{
			$('shoutbox-inactive-prompt').hide();
			$('shoutbox-shouts-table').show();
			
			// Refresh shout only if we are not submitting
			if ( !ipshoutbox.submittingShout )
			{
				ipshoutbox.shoutAjax.reloadShouts(true);
			}
		}
		else
		{
			ipshoutbox.inactivePrompt.hide();
			
			// App page, get also members :D
			ipshoutbox.shoutAjax.reloadMembers(true);
			ipshoutbox.shoutAjax.reloadShouts(true);
		}
	},
	
	actionTaken: function(text)
	{
		if ( !text)
		{
			return false;
		}
		
		alert( text );
	},
	
	setActionAndReload: function(action)
	{
		if ( action != '' && typeof(action) != 'undefined' && action != null )
		{
			var url = ipshoutbox.baseUrl.replace( '&module=ajax', '' );
			url = url.replace( '&section=coreAjax', '' );
			
			ipb.Cookie.set( '_shoutbox_jscmd', action );
			try
			{
				window.location = url.replace(/&$/ig, '');
			}
	
			catch(me)
			{
				window.location.href = url.replace(/&$/ig, '');
			}
		}
	
		return false;
	},
	
	emoticonsCreatePopup: function()
	{
		wrap = new Element('div', { id: 'global_palette_emoticons' } );
		wrap.addClassName('ipb_palette').hide();
		
		var table = new Element( 'table', { id: 'global_emoticons_palette' } ).addClassName('rte_emoticons');
		
		// Same as before, tbody please, says IE
		var tbody = new Element('tbody');
		table.insert( tbody );
		
		var tr = null;
		var td = null;
		
		var perrow = 8;
		var i = 0;
		
		//Debug.write( ipb.editor_values.get('emoticons') );
		shoutboxEMOTICONS.each( function(emote){
			if( i % perrow == 0 )
			{
				tr = new Element( 'tr' );
				tbody.insert( tr );
			}
			
			i++;
			
			var _tmp = emote.value.split(',');
			var img = new Element('img', { id: 'smid_' + _tmp[0], src: ipb.vars['emoticon_url'] + '/' + _tmp[1] } );
			
			td = new Element('td', { id: 'global_emoticons_emote_' + _tmp[0] } ).setStyle('cursor: pointer').addClassName('emote');
			td.writeAttribute('emo_id', _tmp[0] );
			td.writeAttribute('emo_img', _tmp[1] );
			td.writeAttribute('emo_code', emote.key);
			
			td.insert( img );
			tr.insert( td );
			$( td ).observe('click', ipshoutbox.emoticonOnclick.bindAsEventListener( this ) );
		}.bind(this));
		
		wrap.insert( table ).addClassName('emoticons_palette');
		$('shoutbox-smilies-button').insert( { after: wrap } );
		
		// Insert title
		title = new Element( 'div' );
		title.update('Emoticons').addClassName('rte_title');
		wrap.insert( { top: title } );
		
		//Create a new menu! =D
		ipshoutbox.emoPalette = new ipb.Menu( $('shoutbox-smilies-button'), wrap );
	},
	
	emoticonOnclick: function(e)
	{
		var elem     = Event.element(e);
		var emo      = elem.up('.emote');
		var emo_code = emo.readAttribute('emo_code')
		
		//Check focus
		$('shoutbox-global-shout').focus();		
		
		// Parse properly emo_code
		emo_code = emo_code.replace( /&quot;/g, '"' );
		emo_code = emo_code.replace( /&lt;/g  , '<' );
		emo_code = emo_code.replace( /&gt;/g  , '>' );
		emo_code = emo_code.replace( /&amp;/g , '&' );
		
		// Update textarea and close menu
		ipshoutbox.insertAtCursor( " " + emo_code + " " );
		
		ipshoutbox.emoPalette.doClose();
		
		return false;
	},
	
	bbcodePopup: function(e)
	{
		window.open( ipb.vars['base_url'] + "&app=forums&module=extras&section=legends&do=bbcode", "bbcode", "status=0,toolbar=0,width=1024,height=800,scrollbars=1");
		Event.stop(e);
		return false;
	},
	
	popupUpdateStatus: function(lang, text)
	{
		if ( !ipshoutbox.myMemberID || ipshoutbox.global_on )
		{
			return false;
		}
		
		text = ( text == true ) ? true : false;
		
		if ( !text && ( !lang || !ipshoutbox.langs[lang] ) )
		{
			return false;
		}
		
		if ( text )
		{
			$('shoutbox-popup-status').update( lang );
		}
		else
		{
			$('shoutbox-popup-status').update( ipshoutbox.langs[lang] );
		}
	},
	
	popupUpdateContent: function(lang, text)
	{
		if ( !ipshoutbox.myMemberID || ipshoutbox.global_on )
		{
			return false;
		}
		
		text = ( text == true ) ? true : false;
		
		if ( !text && ( !lang || !ipshoutbox.langs[lang] ) )
		{
			return false;
		}
		
		if ( text )
		{
			$('shoutbox-popup-content').update( lang );
		}
		else
		{
			$('shoutbox-popup-content').update( ipshoutbox.langs[lang] );
		}
	},
	
	mod_opts_get_edit_shout: function()
	{
		if ( !ipshoutbox.moderator && !ipshoutbox.can_edit && !ipshoutbox.mod_in_action )
		{
			return false;
		}

		var d = ipb.editors[ ipshoutbox.mod_editor_id ].editor_get_contents();
	
		d = d.strip();
		
		while ( d.match( new RegExp("^(.+?)<br>$", 'i') ) )
		{
			d = d.replace( new RegExp("^(.+?)<br>$", 'i'), '$1' );
		}
	
		d = d.strip();
		
		if ( d.toLowerCase().substring(d.length-4, d.length) == '<br>' )
		{
			d = d.substring(0, d.length-4);
		}
	
		d = d.strip();
		
		return d;
	},
	
	mod_opts_do_edit_shout: function()
	{
		if ( (!ipshoutbox.moderator && !ipshoutbox.can_edit) || !ipshoutbox.mod_in_action )
		{
			return false;
		}

		ipshoutbox.updateLastActivity();

		var shout = ipshoutbox.mod_opts_get_edit_shout();

		if ( shout == '' )
		{
			ipshoutbox.produceError('blank_shout');
			return false;
		}

		if ( shout.length * 1024 > ipshoutbox.max_length )
		{
			ipshoutbox.produceError('shout_too_big');
			return false;
		}

		ipshoutbox.popupUpdateStatus('processing');
			
		new Ajax.Request( ipshoutbox.baseUrl + 'type=mod&action=performCommand&command=' + ipshoutbox.mod_command + '&modtype=shout',
			{
				method: 'post',
				encoding: ipb.vars['charset'],
				parameters: {
					id:			ipshoutbox.mod_shout_id,
					shout:		shout,
					secure_key:	ipb.vars['secure_hash']
				},
				onSuccess: function(s)
				{
					$('shout-row-' + ipshoutbox.mod_shout_id ).update( s.responseText );
					
					$('editHistory_shout').show();
					ipshoutbox.shoutAjax.modOptsEditReset();
				}
			}
		);
	},
	
	refreshShouts: function()
	{
		/* Block it if we are inactive */
		if ( ipshoutbox._inactive )
		{
			return false;
		}
		
		ipshoutbox.updateLastActivity();
		ipshoutbox.shoutAjax.reloadShouts(true);
	},
	
	archive_get_dropdowns: function(t, v)
	{
		if (t != 'start' && t != 'end')
		{
			return new Array();
		}

		var a = new Array
		(
			$('filter_'+t+'_month'),
			$('filter_'+t+'_day'),
			$('filter_'+t+'_year'),
			$('filter_'+t+'_hour'),
			$('filter_'+t+'_minute'),
			$('filter_'+t+'_meridiem')
		);

		if (v == true)
		{
			for (var i=0; i<a.length; i++)
			{
				a[i] = a[i].getValue();
			}
		}

		return a;
	},
	
	archive_set_dropdown_option: function(o, v)
	{
		if (o.options.length > 0)
		{
			for (var i=0; i<o.options.length; i++)
			{
				if (o.options[i].value == v)
				{
					o.selectedIndex = i;
					break;
				}
			}
		}
	},

	archiveDoFilter: function()
	{
		if ( !ipshoutbox.view_archive || ipshoutbox.global_on || !ipshoutbox.in_archive )
		{
			return false;
		}
		
		/* Already filtering? prevent odd things then */
		if ( ipshoutbox.archive_filtering )
		{
			ipshoutbox.produceError('already_filtering');
			return false;
		}
		
		ipshoutbox.archive_filtering = true;
		
		
		if ( ipshoutbox.blur )
		{
			ipshoutbox.blur();
		}

		ipshoutbox._inactive   = true;
		ipshoutbox.updateLastActivity();
		
		var p = {
			'type':		'archive',
			'action':	'filter',
			'secure_key':	ipb.vars['secure_hash'],
			'start':	ipshoutbox.archive_get_dropdowns( 'start', true ),
			'end':		ipshoutbox.archive_get_dropdowns( 'end', true ),
			'member':	$('filter_member_name').getValue().strip()
		};

		if ( p['member'].indexOf(',') > 0 )
		{
			var x = new Array();
			var m = p['member'].split(',');

			for (var i=0; i<m.length; i++)
			{
				m[i] = m[i].strip();
				if (m[i] == '' || m[i].length < 3)
				{
					continue;
				}

				x[x.length] = m[i];
			}

			if (x.length <= 0)
			{
				ipshoutbox.produceError('member_names_too_short');
				return false;
			}
		}
		else if (p['member'].length > 0 && p['member'].length < 3)
		{
			ipshoutbox.produceError('member_name_too_short');
			return false;
		}

		ipshoutbox.archive_cur_filter = p;

		ipshoutbox.popupUpdateStatus('filtering');
		
		new Ajax.Request( ipshoutbox.baseUrl + 'type=archive&action=filter&secure_key=' + ipb.vars['secure_hash'],
			{
				method: 	'post',
				encoding: ipb.vars['charset'],
				parameters:	{
					'start':	ipshoutbox.archive_get_dropdowns( 'start', true ).join(','),
					'end':		ipshoutbox.archive_get_dropdowns( 'end', true ).join(','),
					'member':	$('filter_member_name').getValue().strip()
				},
				onSuccess: function(s)
				{
					ipshoutbox.archive_filter_process(s);
				}
			}
		);

		return false;
	},
	
	archive_filter_process: function(s)
	{
		ipshoutbox.popupUpdateStatus('processed');
		
		if ( ipshoutbox.checkForErrors(s.responseText) )
		{
			ipshoutbox.archive_filtering = false;
			return false;
		}
		
		$('shoutbox-archive-shouts').update( s.responseText );
		
		new Effect.Parallel(
			[
				new Effect.BlindUp( $('beforeButtonClick') ),
				new Effect.BlindDown( $('afterButtonClick') )
			],
			{
				duration: 1.0
			}
		);
		
		$('backToFilters').stopObserving();
		$('backToFilters').observe( 'click', function()
			{
				new Effect.Parallel(
					[
						new Effect.BlindUp( $('afterButtonClick') ),
						new Effect.BlindDown( $('beforeButtonClick') )
					],
					{
						duration: 1.0
					}
				);					
			}
		);
		
		ipshoutbox.rewriteShoutClasses();
		ipshoutbox.archive_update_floaters();
	},
	
	rect: function(x, y, w, h)
	{
		var recta = new Object;
		recta.x = x;
		recta.y = y;
		recta.w = w;
		recta.h = h;
		
		return recta;
	},
	
	archiveQuickFilters: function(event)
	{
		if ( !ipshoutbox.view_archive || ipshoutbox.global_on || !ipshoutbox.in_archive )
		{
			return false;
		}
		
		if ( ipshoutbox.blur )
		{
			ipshoutbox.blur();
		}
		
		/* Get the proper filter */
		var element = Event.element(event);
		var filter  = element.id.sub( 'filter_', '' );
		
		/* Ypdate last active */
		ipshoutbox.updateLastActivity();

		var d = new Date();
		var s = ipshoutbox.archive_get_dropdowns('start', false);
		var e = ipshoutbox.archive_get_dropdowns('end', false);

		switch ( filter )
		{
			case 'today':
				ipshoutbox.archive_set_dropdown_option(s[0], d.getMonth()+1);
				ipshoutbox.archive_set_dropdown_option(s[1], d.getDate());
				ipshoutbox.archive_set_dropdown_option(s[2], d.getFullYear());
				ipshoutbox.archive_set_dropdown_option(s[3], 12);
				ipshoutbox.archive_set_dropdown_option(s[4], 0);
				ipshoutbox.archive_set_dropdown_option(s[5], 'am');

				ipshoutbox.archive_set_dropdown_option(e[0], d.getMonth()+1);
				ipshoutbox.archive_set_dropdown_option(e[1], d.getDate());
				ipshoutbox.archive_set_dropdown_option(e[2], d.getFullYear());
				ipshoutbox.archive_set_dropdown_option(e[3], 11);
				ipshoutbox.archive_set_dropdown_option(e[4], 59);
				ipshoutbox.archive_set_dropdown_option(e[5], 'pm');

				$('filter_member_name').value = '';
				break;
			case 'yesterday':
				var m = d.getMonth()+1;
				var a = d.getDate();
				var y = d.getFullYear();

				if (a == 1)
				{
					if (m == 1)
					{
						m  = 12;
						y -= 1;
					}
					else
					{
						m -= 1;
					}

					a = ipshoutbox.month_days(m-1);
				}
				else
				{
					a -= 1;
				}

				ipshoutbox.archive_set_dropdown_option(s[0], m);
				ipshoutbox.archive_set_dropdown_option(s[1], a);
				ipshoutbox.archive_set_dropdown_option(s[2], y);
				ipshoutbox.archive_set_dropdown_option(s[3], 12);
				ipshoutbox.archive_set_dropdown_option(s[4], 0);
				ipshoutbox.archive_set_dropdown_option(s[5], 'am');

				ipshoutbox.archive_set_dropdown_option(e[0], m);
				ipshoutbox.archive_set_dropdown_option(e[1], a);
				ipshoutbox.archive_set_dropdown_option(e[2], y);
				ipshoutbox.archive_set_dropdown_option(e[3], 11);
				ipshoutbox.archive_set_dropdown_option(e[4], 59);
				ipshoutbox.archive_set_dropdown_option(e[5], 'pm');

				$('filter_member_name').value = '';
				break;
			case 'month':
				ipshoutbox.archive_set_dropdown_option(s[0], d.getMonth()+1);
				ipshoutbox.archive_set_dropdown_option(s[1], 1);
				ipshoutbox.archive_set_dropdown_option(s[2], d.getFullYear());
				ipshoutbox.archive_set_dropdown_option(s[3], 12);
				ipshoutbox.archive_set_dropdown_option(s[4], 0);
				ipshoutbox.archive_set_dropdown_option(s[5], 'am');

				ipshoutbox.archive_set_dropdown_option(e[0], d.getMonth()+1);
				ipshoutbox.archive_set_dropdown_option(e[1], ipshoutbox.month_days[d.getMonth()]);
				ipshoutbox.archive_set_dropdown_option(e[2], d.getFullYear());
				ipshoutbox.archive_set_dropdown_option(e[3], 11);
				ipshoutbox.archive_set_dropdown_option(e[4], 59);
				ipshoutbox.archive_set_dropdown_option(e[5], 'pm');

				$('filter_member_name').value = '';
				break;
			case 'all':
			case 'mine':
				dd = new Date(ipshoutbox.oldest_shout);
				hr = dd.getHours();
				md = '';

				if (hr < 12)
				{
					md = 'am';
					if (hr == 0)
					{
						hr = 12;
					}
				}
				else if (hr > 12)
				{
					md  = 'pm';
					hr -= 12;
				}

				ipshoutbox.archive_set_dropdown_option(s[0], dd.getMonth()+1);
				ipshoutbox.archive_set_dropdown_option(s[1], dd.getDate());
				ipshoutbox.archive_set_dropdown_option(s[2], dd.getFullYear());
				ipshoutbox.archive_set_dropdown_option(s[3], hr);
				ipshoutbox.archive_set_dropdown_option(s[4], dd.getMinutes());
				ipshoutbox.archive_set_dropdown_option(s[5], md);

				ipshoutbox.archive_set_dropdown_option(e[0], d.getMonth()+1);
				ipshoutbox.archive_set_dropdown_option(e[1], d.getDate());
				ipshoutbox.archive_set_dropdown_option(e[2], d.getFullYear());
				ipshoutbox.archive_set_dropdown_option(e[3], 11);
				ipshoutbox.archive_set_dropdown_option(e[4], 59);
				ipshoutbox.archive_set_dropdown_option(e[5], 'pm');

				if ( filter == 'mine' )
				{
					$('filter_member_name').value = ipshoutbox.my_dname;
				}
				else
				{
					$('filter_member_name').value = '';
				}
				break;
			default:
				ipshoutbox.archive_filtering = false;
				return false; //Leave a return here!
		}
		
		ipshoutbox.archiveDoFilter();
	},
	
	archive_update_floaters: function()
	{
		if ( !ipshoutbox.view_archive || ipshoutbox.global_on || !ipshoutbox.in_archive )
		{
			return false;
		}
		
		ipshoutbox.archive_filtering = false;
		
		var o = $('shoutbox-archive-shouts-div');
		var r = $('shoutbox-archive-pages-floater');
		
		/* Scroll shouts to top again after changing page */
		o.scrollTop = 0;
		
		if ( ipshoutbox.shout_pages == 0 )
		{
			r.hide();
			return false;
		}
		
		/*r.show();
		r.style.marginTop = o.scrollTop+'px';
		r.style.zIndex    = 30;
		
		if (o.scrollHeight > 0)
		{
			r.style.right = '16px';
		}
		else
		{
			r.style.right = '0px';
		}
		
		if (is_opera)
		{
			ipshoutbox.archive_update_floaters();
		}*/
	},

	archive_update_pager: function(p)
	{
		var html = '';
		
		ipshoutbox.cur_page = p;
		
		if ( p > 1 )
		{
			html += "<span onclick='ipshoutbox.archive_goto_prev_page()' style='cursor:pointer'>&laquo;</span>&nbsp;";
		}
		
		//html += ipshoutbox.langs['page']+" <span id='shoutbox-archive-page-changer'>"+p+'</span> '+ipshoutbox.langs['of']+' '+ipshoutbox.shout_pages;
		html += ipshoutbox.langs['page']+' '+p+' '+ipshoutbox.langs['of']+' '+ipshoutbox.shout_pages;

		if ( p < ipshoutbox.shout_pages )
		{
			html += "&nbsp;<span onclick='ipshoutbox.archive_goto_next_page()' style='cursor:pointer'>&raquo;</span>";
		}
		
		$('shoutbox-archive-pages-data').innerHTML = html;
		//$('shoutbox-archive-pages-data').update( html );
		
		//$('shoutbox-archive-page-changer').observe('click', ipshoutbox.archive_change_page_init );
	},
	
	/*archive_change_page_init: function()
	{
		if (!ipshoutbox.view_archive || ipshoutbox.global_on || !ipshoutbox.in_archive)
		{
			return false;
		}

		if ($('shoutbox-archive-page-changer-input'))
		{
			$('shoutbox-archive-page-changer-input').parentNode.removeChild($('shoutbox-archive-page-changer-input'));
		}

		ipshoutbox.allow_keys = new Array(8, 13, 27, 35, 36, 37, 39, 46);
		for (var i=48; i<58; i++)
		{
			ipshoutbox.allow_keys[ipshoutbox.allow_keys.length] = i;
		}

		var o = $('shoutbox-archive-page-changer');
		var i = document.createElement('input');
		var w = o.offsetWidth;

		o.cur_page  = Math.round(o.innerHTML);
		o.innerHTML = '';

		i.id                = 'shoutbox-archive-page-changer-input';
		i.className         = 'row2';
		i.value             = parseInt(o.cur_page);
		i.style['padding']  = 0;
		i.style['margin']   = '-2px 0 0 0';
		i.style['border']   = 0;
		i.style['width']    = parseInt(w)+'px';
		i.setAttribute('maxlength', ipshoutbox.shout_pages.toString().length);

		o.appendChild(i);

		//$('shoutbox-archive-page-changer-input').observe('blur', ipshoutbox.archive_change_page_process );
		//$('shoutbox-archive-page-changer-input').observe('keydown', ipshoutbox.archive_change_page_keydown );

		o.stopObserving('dblclick');

		i.focus();
		i.select();

		ipshoutbox.in_archive_page_change = true;
	},

	archive_change_page_keydown: function(e)
	{
		if (document.all)
		{
			if (e && window.event && e._skip != true)
			{
				e = window.event;
			}
		}

		if (!e || e == null || typeof(e) == 'undefined')
		{
			return false;
		}

		try
		{
			ipshoutbox.updateLastActivity();
		}

		catch(me){}

		if (document.layers)
		{
			var alt   = (e.modifiers&Event.ALT_MASK) ? true : false;
			var ctrl  = (e.modifiers&Event.CONTROL_MASK) ? true : false;
			var shift = (e.modifiers&Event.SHIFT_MASK) ? true : false;
			var key   = e.which;
		}
		else
		{
			var alt   = e.altKey;
			var ctrl  = e.ctrlKey;
			var shift = e.shiftKey;

			if (document.all)
			{
				var key = e.keyCode;
			}
			else
			{
				if (e.keyCode > 0)
				{
					var key = e.keyCode;
				}
				else if (e.which > 0)
				{
					var key = e.which;
				}
			}
		}

		var obj = (e.srcElement) ? e.srcElement : e.target;
		if ( Prototype.Browser.WebKit || Prototype.Browser.Gecko )
		{
			if (obj.nodeType == 3 && e.target.parentNode.nodeType == 1)
			{
				obj = e.target.parentNode;
			}
		}

		if (ipsclass.in_array(key, ipshoutbox.allow_keys) == false)
		{
			Event.stop(e);
			return false;
		}

		switch (key)
		{
			case 13:
				Event.stop(e);
				ipshoutbox.archive_change_page_process();
				return false;

				break;
			case 27:
				Event.stop(e);
				ipshoutbox.archive_change_page_cancel();
				return false;

				break;
			default:
				return true;
		}

		Event.stop(e);
		return false;
	},
	
	archive_change_page_process: function()
	{
		if (!ipshoutbox.view_archive || ipshoutbox.global_on || !ipshoutbox.in_archive)
		{
			return false;
		}

		ipshoutbox.in_archive_page_change = false;

		var o = $('shoutbox-archive-page-changer');
		var i = $('shoutbox-archive-page-changer-input');
		var p = parseInt(i.value);

		if (p <= 0 || p > ipshoutbox.shout_pages)
		{
			p = o.cur_page;
		}

		i.parentNode.removeChild(i);
		o.cur_page  = null;
		o.innerHTML = p;

		if (p == o.cur_page)
		{
			return false;
		}

		ipshoutbox.updateLastActivity();
		ipshoutbox.archive_cur_filter['page'] = p;

		ipshoutbox.popupUpdateStatus('filtering');
		ipshoutbox.new_ajax('filter-archive', 'post', ipshoutbox.base_url, ipshoutbox.archive_filter_process, ipshoutbox.archive_cur_filter);
		
		o.observe('dblclick', ipshoutbox.archive_change_page_init );
	},

	archive_change_page_cancel: function()
	{
		if (!ipshoutbox.view_archive || ipshoutbox.global_on || !ipshoutbox.in_archive)
		{
			return false;
		}

		ipshoutbox.in_archive_page_change = false;

		var o = $('shoutbox-archive-page-changer');
		var i = $('shoutbox-archive-page-changer-input');
		var p = o.cur_page;

		i.parentNode.removeChild(i);
		o.cur_page  = null;
		o.innerHTML = p;

		o.observe('dblclick', ipshoutbox.archive_change_page_init );
		return false;
	},*/
	
	archive_goto_prev_page: function()
	{
		if ( !ipshoutbox.view_archive || ipshoutbox.global_on || !ipshoutbox.in_archive )
		{
			return false;
		}

		var p = ipshoutbox.cur_page;
		var t = ipshoutbox.shout_pages;
		var n = Math.floor(p-1);

		if (n < 1)
		{
			return false;
		}

		ipshoutbox.updateLastActivity();
		ipshoutbox.archive_cur_filter['page'] = n;

		ipshoutbox.popupUpdateStatus('filtering');
		
		new Ajax.Request( ipshoutbox.baseUrl + 'type=archive&action=filter&secure_key=' + ipb.vars['secure_hash'],
			{
				method: 	'post',
				encoding: ipb.vars['charset'],
				parameters:	{
					'start':	ipshoutbox.archive_get_dropdowns( 'start', true ).join(','),
					'end':		ipshoutbox.archive_get_dropdowns( 'end', true ).join(','),
					'member':	$('filter_member_name').getValue().strip(),
					'page':		n
				},
				onSuccess: function(s)
				{
					ipshoutbox.archive_filter_process(s);
				}
			}
		);
	},

	archive_goto_next_page: function()
	{
		if (!ipshoutbox.view_archive || ipshoutbox.global_on || !ipshoutbox.in_archive)
		{
			return false;
		}

		var p = ipshoutbox.cur_page;
		var t = ipshoutbox.shout_pages;
		var n = Math.floor(p+1);

		if (n > t)
		{
			return false;
		}

		ipshoutbox.updateLastActivity();
		ipshoutbox.archive_cur_filter['page'] = n;

		ipshoutbox.popupUpdateStatus('filtering');
		
		new Ajax.Request( ipshoutbox.baseUrl + 'type=archive&action=filter&secure_key=' + ipb.vars['secure_hash'],
			{
				method: 	'post',
				encoding: ipb.vars['charset'],
				parameters:	{
					'start':	ipshoutbox.archive_get_dropdowns( 'start', true ).join(','),
					'end':		ipshoutbox.archive_get_dropdowns( 'end', true ).join(','),
					'member':	$('filter_member_name').getValue().strip(),
					'page':		n
				},
				onSuccess: function(s)
				{
					ipshoutbox.archive_filter_process(s);
				}
			}
		);
	},
	
	shouts_fade: function(ids)
	{
		if ( ids != null && ids.length > 0 )
		{
			/** Update live total shouts count **/
			ipshoutbox.updateTotalShouts( ipshoutbox.total_shouts + ids.length );
			
			/* Fade disabled, stop here u_u */
			if ( !ipshoutbox.enable_fade )
			{
				return false;
			}
			
			// Fade our new shouts
			ids.each(
				function( ID )
				{
					$A( $( 'shout-row-' + ID ).childElements() ).each(
						function( td )
						{
							new Effect.Highlight( td, { startcolor: '#ffff99' } );
						}
					);
				}
			);
		}
	},
	
	scroll_page_up: function()
	{
		window.scrollBy(0, -3);
	},

	scroll_page_down: function()
	{
		window.scrollBy(0, 3);
	},
	
	resizeGlobalShouts: function()
	{
		var sso = $('shoutbox-shouts');
		var srh = $('shouts-global-resizer');

		if ( !ipshoutbox.myMemberID )
		{
			srh.style.cursor = 'default';
			return false;
		}

		Resize.init(srh, sso, true, false);

		sso.min_height = 100;
		sso.Resizing   = function()
		{
			ipshoutbox.shoutsScrollThem();
		}

		sso.Resize_end = function(data)
		{
			ipshoutbox.shoutAjax.myPrefsGlobalHeightSave( parseInt(data.h) );
		}
	},

	keydown_handler_iframe: function(e)
	{
		if (document.all)
		{
			if (ipb.editors[ipshoutbox.editor_id].editor_window.event)
			{
				e = ipb.editors[ipshoutbox.editor_id].editor_window.event;
				e._skip = true;
			}

			if (e.keyCode == 13)
			{
				e._sbrte = true;
				ipshoutbox.keypress_handler(e);
			}
		}
	},
	
	keypress_handler_iframe: function(e)
	{
		if (document.all)
		{
			if (ipb.editors[ipshoutbox.editor_id].editor_window.event)
			{
				e = ipb.editors[ipshoutbox.editor_id].editor_window.event;
				e._skip = true;
			}
		}

		e._sbrte = true;
		ipshoutbox.keypress_handler(e);
	},

	keypress_handler: function(e)
	{
		if (document.all)
		{
			if (e && window.event && e._skip != true)
			{
				e = window.event;
			}
		}

		if (!e || e == null || typeof(e) == 'undefined')
		{
			return false;
		}

		ipshoutbox.updateLastActivity();

		var ret = true;
		if (document.layers)
		{
			var alt   = (e.modifiers&Event.ALT_MASK) ? true : false;
			var ctrl  = (e.modifiers&Event.CONTROL_MASK) ? true : false;
			var shift = (e.modifiers&Event.SHIFT_MASK) ? true : false;
			var key   = e.which;
		}
		else
		{
			var alt   = e.altKey;
			var ctrl  = e.ctrlKey;
			var shift = e.shiftKey;

			if (document.all)
			{
				var key = e.keyCode;
			}
			else
			{
				if (e.keyCode > 0)
				{
					var key = e.keyCode;
				}
				else if (e.which > 0)
				{
					var key = e.which;
				}
			}
		}

		var obj = (e.srcElement) ? e.srcElement : e.target;
		if ( Prototype.Browser.WebKit || Prototype.Browser.Gecko )
		{
			if (obj.nodeType == 3 && e.target.parentNode.nodeType == 1)
			{
				obj = e.target.parentNode;
			}
		}

		if (typeof(obj.id) == 'undefined')
		{
			obj.id = '';
		}

		if ( ipshoutbox.my_prefs['enter_key_shout'] == 1 && (obj.id == 'shoutbox-global-shout' || obj.id == ipshoutbox.editor_id+'_textarea' || e._sbrte == true) && !shift && !alt && !ctrl && key == 13)
		{
			ipshoutbox.shoutAjax.submitShout();
			
			Event.stop(e);
			return false;
		}
		else if ( (alt && key == 13) || (ctrl && key == 13) )
		{
			ipshoutbox.shoutAjax.submitShout();
			
			Event.stop(e);
			return false;
		}

		return true;
	},
	
	insertAtCursor: function(text)
	{
		var editor = $('shoutbox-global-shout');
		
		editor.focus();

		if ( !Object.isUndefined( editor.selectionStart ) )
		{
			var open = editor.selectionStart + 0;
			var st   = editor.scrollTop;
			var end  = open + text.length;
			
			/* Opera doesn't count the linebreaks properly for some reason */
			if( Prototype.Browser.Opera )
			{
				var opera_len = text.match( /\n/g );

				try
				{
					end += parseInt(opera_len.length);
				}
				catch(e)
				{
					Debug.write( "Error with Opera => " + e );
				}
			}
			
			editor.value = editor.value.substr(0, editor.selectionStart) + text + editor.value.substr(editor.selectionEnd);
			
			editor.setSelectionRange( end, end );
		}
		else if ( document.selection && document.selection.createRange )
		{
			var sel  = document.selection.createRange();
			sel.text = text.replace(/\r?\n/g, '\r\n');
			sel.select();
		}
		else
		{
			editor.value += text;
		}
	},
	
	truebody: function()
	{
		return (document.compatMode && document.compatMode != 'BackCompat') ? document.documentElement : document.body;
	},

	get_offset_left: function(o, p)
	{
		var l = 0;
		if (o.offsetParent)
		{
			while (o.offsetParent)
			{
				if (p != null && o == p)
				{
					break;
				}

				l += o.offsetLeft;
				o = o.offsetParent;
			}
		}
		else if (o.x)
		{
			l += o.x;
		}

		return l;
	},

	get_offset_top: function(o, p)
	{
		var t = 0;
		if (o.offsetParent)
		{
			while (o.offsetParent)
			{
				if (p != null && o == p)
				{
					break;
				}

				t += o.offsetTop;
				o = o.offsetParent;
			}
		}
		else if (o.y)
		{
			t += o.y;
		}

		return t;
	},

	get_page_size: function()
	{
		var x;
		var y;
		var ww;
		var wh;
		var pw;
		var ph;

		if (window.innerHeight && window.scrollMaxY)
		{
			x = document.body.scrollWidth;
			y = window.innerHeight+window.scrollMaxY;
		}
		else if (document.body.scrollHeight > document.body.offsetHeight)
		{
			x = document.body.scrollWidth;
			y = document.body.scrollHeight;
		}
		else
		{
			x = document.body.offsetWidth;
			y = document.body.offsetHeight;
		}

		if (self.innerHeight)
		{
			ww = self.innerWidth;
			wh = self.innerHeight;
		}
		else if (document.documentElement && document.documentElement.clientHeight)
		{
			ww = document.documentElement.clientWidth;
			wh = document.documentElement.clientHeight;
		}
		else if (document.body)
		{
			ww = document.body.clientWidth;
			wh = document.body.clientHeight;
		}

		pw = x;
		ph = y;

		if (y < wh)
		{
			ph = wh;
		}

		if (x < ww)
		{	
			pw = ww;
		}

		return new Array(pw, ph, ww, wh);
	},
	
	checkForErrors: function( text, status )
	{
		if ( text.substring(0, 6) == 'error-' )
		{
			if ( status != null && status != '' )
			{
				ipshoutbox.popupUpdateStatus( status );
			}
			
			ipshoutbox.produceError( text.substring(6) );
			
			return true;
		}
		else
		{
			return false;
		}
	},
	
	setupPopup: function(popup)
	{
		switch(popup)
		{
			case 'preferences':
				ipshoutbox.in_prefs = true;
				
				ipshoutbox.popupUpdateStatus('my_prefs_loaded');
				
				/* Setup onclick for buttons */
				$('myprefs_save').observe('click', ipshoutbox.shoutAjax.myPrefsSave );
				$('myprefs_restore').observe('click', ipshoutbox.shoutAjax.myPrefsRestore );
				
				/* Show it */
				ipshoutbox.preferences.show();
				break;
			case 'moderator':
				ipshoutbox.in_mod = true;
				
				new ipb.Menu( $( 'shoutbox_mod_options' ), $( 'shoutbox_mod_options_menucontent' ) );
				
				/* Setup onclick for mod menu */
				if ( $('edit_shout') )
				{
					$('edit_shout').observe('click', ipshoutbox.shoutAjax.modOptsDo.bindAsEventListener(this) );
				}
				if ( $('editHistory_shout') )
				{
					$('editHistory_shout').observe('click', ipshoutbox.shoutAjax.modOptsDo.bindAsEventListener(this) );
				}
				if ( $('delete_shout') )
				{
					$('delete_shout').observe('click', ipshoutbox.shoutAjax.modOptsDo.bindAsEventListener(this) );
				}
				if ( $('deleteAll_shout') )
				{
					$('deleteAll_shout').observe('click', ipshoutbox.shoutAjax.modOptsDo.bindAsEventListener(this) );
				}
				if ( $('ban_shout') )
				{
					$('ban_shout').observe('click', ipshoutbox.shoutAjax.modOptsDo.bindAsEventListener(this) );
				}
				if ( $('unban_shout') )
				{
					$('unban_shout').observe('click', ipshoutbox.shoutAjax.modOptsDo.bindAsEventListener(this) );
				}
				if ( $('removeMod_shout') )
				{
					$('removeMod_shout').observe('click', ipshoutbox.shoutAjax.modOptsDo.bindAsEventListener(this) );
				}
				
				/* Show it */
				ipshoutbox.modOpts.show();
				break;
			case 'archive':
				$('load-shoutbox-archive').stopObserving();
				$('load-shoutbox-archive').observe( 'click', ipshoutbox.displayArchive );
				
				ipshoutbox.in_archive = true;
				
				ipshoutbox.popupUpdateStatus('sb_archive_loaded');
				
				/* Show it */
				ipshoutbox.archiveArea.show();
				break;
			// Added in 1.1.1
			case 'editShout':
				ipshoutbox.in_mod = true;
				
				/* Observe buttons >_< */
				$('mod_edit_shout_confirm').observe('click', ipshoutbox.mod_opts_do_edit_shout );
				$('mod_edit_shout_clear').observe('click', ipshoutbox.shoutAjax.modOptsEditClear );
				$('mod_edit_shout_cancel').observe('click',
					function()
					{
						ipshoutbox.closePopup('editShout');
					}
				);
				
				/* Show it */
				ipshoutbox.editShoutPopup.show();
				break;
			default:
				Debug.write("setupPopup called without a proper popup defined: "+popup);
				break;
		}
	},
	
	closePopup: function(popup)
	{
		switch(popup)
		{
			case 'preferences':
				ipshoutbox.preferences.hide();
				
				ipshoutbox.in_prefs = false;
				break;
			case 'moderator':
				ipshoutbox.shoutAjax.modOptsEditReset();
				ipshoutbox.modOpts.hide();
				
				ipshoutbox.in_mod = false;
				ipshoutbox.mod_shout_id = 0;
				ipshoutbox.mod_member_id = 0;
				ipshoutbox.mod_in_action = false;
				break;
			case 'archive':
				ipshoutbox.archiveArea.hide();
				
				ipshoutbox.in_archive = false;
				ipshoutbox.archive_filtering = false;
				break;
			// Added in 1.1.1
			case 'editShout':
				ipshoutbox.editShoutPopup.hide();
				
				ipshoutbox.in_mod = false;
				ipshoutbox.mod_shout_id = 0;
				ipshoutbox.mod_member_id = 0;
				ipshoutbox.mod_in_action = false;
				break;
			default:
				Debug.write("closePopup called without a proper popup defined: "+popup);
				break;
		}
		
		/* We got inactive while a popup was open! =O */
		if ( ipshoutbox.inactiveWhenPopup )
		{
			ipshoutbox.inactiveWhenPopup = false;
			
			setTimeout("ipshoutbox.displayInactivePrompt()", 600);
		}
	},
	
	popupModeratorReset: function()
	{
		/* Update status/content */
		ipshoutbox.popupUpdateStatus('mod_opts_start_status');
		ipshoutbox.popupUpdateContent('mod_opts_start_content');
		
		/* Reset our mod vars */
		ipshoutbox.mod_in_action = false;
		ipshoutbox.mod_editor_id  = '';
		ipshoutbox.mod_editor_rte = 0;
	},
	
	updateLastActivity: function()
	{
		ipshoutbox.last_active = ipshoutbox.getTimestamp();
	},
	
	updateTotalShouts: function( newCount )
	{
		ipshoutbox.total_shouts = parseInt(newCount);
		
		if ( !ipshoutbox.global_on )
		{
			$('shoutbox-total-shouts').update( ipshoutbox.total_shouts );
			
			if ( ipshoutbox.enable_fade )
			{
				new Effect.Highlight( $('shoutbox-total-shouts'), { startcolor: '#ffff99' } );
			}
		}
	},
	
	/**
	 * Setup the toggle hide/show for the shoutbox
	 */
	setupToggle: function()
	{
		if ( !ipshoutbox.global_on )
		{
			return false;
		}
		
		$('category_shoutbox').select('.toggle')[0].stopObserving();
		$('category_shoutbox').select('.toggle')[0].observe( 'click', ipshoutbox.toggleShoutbox );
		
		/* ipb.board not loaded? */
		if ( Object.isUndefined(ipb.board) )
		{
			cookie = ipb.Cookie.get('toggleCats');
			
			if( cookie )
			{
				var cookies = cookie.split( ',' );
				
				//-------------------------
				// Little fun for you...
				//-------------------------
				for( var abcdefg=0; abcdefg < cookies.length; abcdefg++ )
				{
					if( cookies[ abcdefg ] == 'shoutbox' )
					{
						var wrapper	= $('category_shoutbox').up('.category_block').down('.table_wrap');
						
						wrapper.hide();
						$('category_shoutbox').addClassName('collapsed');
						
						// Block there, we found our shoutbox :D
						break;
					}
				}
			}
		}
	},
	
	/**
	 * Show/hide the shoutbox
	 * 
	 * @var		{event}		e	The event
	 */
	toggleShoutbox: function(e)
	{
		if ( !ipshoutbox.global_on )
		{
			return false;
		}
		
		/* Code taked from the function ipb.board.toggleCat(e); because the board file is not loaded always */
		if( ipshoutbox.animatingGlobal || (!Object.isUndefined(ipb.board) && ipb.board.animating) ){ return false; }
		
		/* Init some vars */
		var click   = Event.element(e),
			remove  = $A(),
			wrapper = $( click ).up('.category_block').down('.table_wrap');
		
		$( wrapper ).identify(); // IE8 fix
		
		ipshoutbox.animatingGlobal = true;
		
		// Get cookie
		cookie = ipb.Cookie.get('toggleCats');
		if( cookie == null ){
			cookie = $A();
		} else {
			cookie = cookie.split(',');
		}
		
		Effect.toggle( wrapper, 'blind', {duration: 0.4, afterFinish: function(){ ipshoutbox.animatingGlobal = false; } } );
		
		if( $('category_shoutbox').hasClassName('collapsed') )
		{
			$('category_shoutbox').removeClassName('collapsed');
			remove.push('shoutbox');
			
			// Not inactive? load new shouts!
			if ( !ipshoutbox._inactive )
			{
				ipshoutbox.shoutAjax.reloadShouts(true);
			}
		}
		else
		{
			// Remove Shouts timer
			clearTimeout(ipshoutbox.timeoutShouts);
			
			new Effect.Morph( $('category_shoutbox'), {style: 'collapsed', duration: 0.4, afterFinish: function(){
				$('category_shoutbox').addClassName('collapsed');
				
				ipshoutbox.animatingGlobal = false;
			} });
			cookie.push('shoutbox');
		}
		
		cookie = "," + cookie.uniq().without( remove ).join(',') + ",";
		ipb.Cookie.set('toggleCats', cookie, 1);
		
		Event.stop( e );
	},
	
	/**
	 * Show hidden shouts from ignored users
	 * 
	 * @var		int		id		Shout ID
	 */
	showHiddenShout: function(id)
	{
		if( $('hidden_shout_' + id ) )
		{
			elem = $('hidden_shout_' + id );
			new Effect.Parallel( [
				new Effect.BlindDown( elem ),
				new Effect.Appear( elem )
			], { duration: 0.5 } );
		}
		
		if( $('unhide_shout_' + id ) )
		{
			$('unhide_shout_' + id ).hide();
		}
		
		//Event.stop();
	}
});