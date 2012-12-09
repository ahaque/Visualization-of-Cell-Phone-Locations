/**
* INVISION POWER BOARD v3
*
* Topics View Javascript File
* @author Matt Mecham, Brandon Farber, Josh Williams
* @since 2008
*/

/**
* "ACP" Class. Designed for ... ACP CSS Functions
* @author (v.1.0.0) Matt Mecham
*/

function IPBCSS()
{
	/**
	* Template groups
	*
	* @var	array
	*/
	this.CSSData = {};
	
	/**
	* Current template bit
	*
	* @var	array
	*/
	this.currentCSS = '';
	
	/**
	* Current set ID
	*
	* @var	array
	*/
	this.currentSetData = {};
	
	/**
	* CSS Names
	*
	* @var hash
	*/
	this.cssNames = { 
					  'cssRow'          : 'tablerow2',
					  'cssHover'        : 'cssHover' };
	
	
	/**
	* INIT Applications
	*/
	this.init = function()
	{
		/* Build CSS list */
		this.buildCSSList();
	}
	
	/**
	* Load css groups
	*/
	this.loadCSSGroups = function()
	{
		/* Grab the JSON for the template bits for the current template group */
		url = ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=css&amp;do=getCSSGroups&amp;setID=" + this.currentSetData['set_id'] + '&secure_key=' + ipb.vars['md5_hash'];
	
		new Ajax.Request( url.replace( /&amp;/g, '&' ),
						  {
							method: 'GET',
							onSuccess: function (t )
							{
								if ( t.responseText.match( /^(\s+?)?\{/ ) )
								{
									$('tplate_debug').innerHTML += "\n" + t.responseText;
									
									eval( "var json = " + t.responseText );
									
									if ( json['error'] )
									{
										alert( json['error'] );
										return;
									}
									else
									{
										IPB3CSS.CSSData = json;
										IPB3CSS.buildCSSList();
									}
								}
								else
								{
									alert( "Ooops. Something went wrong. Does this help?: " + t.responseText );
								}
							},
							onException: function( f,e ){ alert( "Exception: " + e ) },
							onFailure: function( t ){ alert( "Failure: " + t.responseText ) }
						  } );
	}
	
	/**
	* Save CSS
	*/
	this.saveCSS = function( css_id )
	{
		/* Grab the JSON for the template bits for the current template group */
		url = ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=css&amp;do=saveCSS&amp;setID=" + this.currentSetData['set_id'] + '&css_id=' + css_id + '&secure_key=' + ipb.vars['md5_hash'];
		
		/* Set up params */
		var params = { 'css_content'  : $F('tplate_editBox_' + css_id ),
					   'css_position' : $F('tplate_posBox_' + css_id ),
				       'css_set_id'   : this.currentSetData['set_id'],
				       '_css_group'   : $F('tplate_groupBox_'  + css_id ),
				       'type'         : ( css_id == 0 ) ? 'add' : 'edit' };
				
		
		new Ajax.Request( url.replace( /&amp;/g, '&' ),
						  {
							method: 'POST',
							parameters: params,
							onSuccess: function (t )
							{
								if ( t.responseText.match( /^(\s+?)?\{/ ) )
								{
									eval( "var json = " + t.responseText );
									
									if ( json['error'] )
									{
										alert( json['error'] );
										return;
									}
									else
									{
										IPB3CSS.currentCSS = json['cssData'];
										IPB3CSS.showCSSEditor( json );
										IPB3CSS.loadCSSGroups();
										
										if ( json['errors'] )
										{
											/* Something inline would be very nice here.. rather than this ugly alert */
											alert("UGLY ALERT BOX SAYS: " + json['errors'] );
										}
										else
										{
											alert("UGLY ALERT BOX SAYS: Saved!");
										}
									}
								}
								else
								{
									alert( "Ooops. Something went wrong. Does this help?: " + t.responseText );
								}
							},
							onException: function( f,e ){ alert( "Exception: " + e ) },
							onFailure: function( t ){ alert( "Failure: " + t.responseText ) }
						  } );
	}
	
	/**
	* Revert css
	*/
	this.revertCSS = function( css_id )
	{
		/* Make sure we're mean it... */
		if ( $('tplate_cssRow_' + css_id ).hasClassName( 'tplateRow_new')  )
		{
			if ( ! confirm( "REMOVE CSS file?\n This will remove this template bit in ALL skins!" ) )
			{
				return false;
			}
		}
		else
		{
			if ( ! confirm( "Revert CSS file?\n All changes WILL be lost!" ) )
			{
				return false;
			}
		}
		
		/* Grab the JSON for the template bits for the current template group */
		url = ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=css&amp;do=revertCSS&amp;setID=" + this.currentSetData['set_id'] + '&css_id=' + css_id + '&secure_key=' + ipb.vars['md5_hash'];
	
		new Ajax.Request( url.replace( /&amp;/g, '&' ),
						  {
							method: 'GET',
							onSuccess: function (t )
							{
								if ( t.responseText.match( /^(\s+?)?\{/ ) )
								{
									eval( "var json = " + t.responseText );
									
									if ( json['error'] )
									{
										alert( json['error'] );
										return;
									}
									else
									{
										IPB3CSS.loadCSSGroups();
										
										if ( json['errors'] )
										{
											/* Something inline would be very nice here.. rather than this ugly alert */
											alert("UGLY ALERT BOX SAYS: " + json['errors'] );
										}
									}
								}
								else
								{
									alert( "Ooops. Something went wrong. Does this help?: " + t.responseText );
								}
							},
							onException: function( f,e ){ alert( "Exception: " + e ) },
							onFailure: function( t ){ alert( "Failure: " + t.responseText ) }
						  } );
	}
	
	/**
	* Load template bits
	*/
	this.loadCSSEditor = function( css_id )
	{
		/* Grab the JSON for the template bits for the current template group */
		url = ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=css&amp;do=getCSSForEdit&amp;setID=" + this.currentSetData['set_id'] + '&css_id=' + css_id + '&secure_key=' + ipb.vars['md5_hash'];
	
		new Ajax.Request( url.replace( /&amp;/g, '&' ),
						  {
							method: 'GET',
							onSuccess: function (t )
							{
								if ( t.responseText.match( /^(\s+?)?\{/ ) )
								{
									eval( "var json = " + t.responseText );
									
									if ( json['error'] )
									{
										alert( json['error'] );
										return;
									}
									else
									{
										IPB3CSS.showCSSEditor( json );
									}
								}
								else
								{
									alert( "Ooops. Something went wrong. Does this help?: " + t.responseText );
								}
							},
							onException: function( f,e ){ alert( "Exception: " + e ) },
							onFailure: function( t ){ alert( "Failure: " + t.responseText ) }
						  } );
	}
	
	/**
	* Add CSS form
	*/
	this.addCSSForm = function()
	{
		IPB3CSS.showCSSEditor( { 'cssData' : { 'css_id' : 0, 'css_group' : '', 'css_content' : '', 'css_added_to' : 0, '_type' : 'new' } } );
	}
	
	/**
	* Shows the template editor
	*/
	this.showCSSEditor = function( json )
	{
		/* Close any open editors */
		IPB3CSS.cancelCSS( json['cssData']['css_id'] );
		
		var _tplate = new Template( $('tplate_cssEditor').innerHTML );
		
		IPB3CSS.currentCSS = json['cssData'];
		
		var _div         = document.createElement( "DIV" );
		_div.id          = 'tplate_editorWrapper';
		_div.update( _tplate.evaluate( json['cssData'] ) );
		document.body.appendChild( _div );
		
		/* New? */
		if ( json['cssData']['_type'] == 'new' || ( json['cssData']['css_added_to'] == IPB3CSS.currentSetData['set_id'] ) )
		{
			$( 'tplate_groupBoxWrap_' + json['cssData']['css_id'] ).show();
		}
		
		ipb.positionCenter( 'tplate_editor_' + json['cssData']['css_id'] );
	}
	
	/**
	* Shows the template editor
	*/
	this.cancelCSS = function( cssID )
	{
		/*if ( ! confirm( "All unsaved changes will be lost!" ) )
		{
			return false;
		}*/
		
		try
		{
			$('tplate_editorWrapper').remove();
		}
		catch(e)
		{
		}
	}
	
	/**
	* Build a list of CSS groups (files)
	*/
	this.buildCSSList = function()
	{
		/* INIT */
		var _output = '';
		var _tplate = new Template( $('tplate_cssRow').innerHTML );
		var json    = IPB3CSS.CSSData;
		
		var _groups = Object.keys( json['css'] ).sort( function(a,b) { return a-b; } );
		
		/* Clear out any current listing */
		$('tplate_cssList').update('');
		
		/* Format... */
		_groups.each( function( i )
		{
			/* Inline deleted? */
			if ( ! json['css'][i]['_deleted'] )
			{
				/* Figure out if this in an inherited row, a changed row, a new row or a default row */
				if ( json['css'][i]['css_added_to'] == IPB3CSS.currentSetData['set_id'] )
				{
					/* This is a modified row */
					json['css'][i]['_cssState']  = 'new';
					json['css'][i]['_cssClass']  = 'tplateRow_new';
				}
				else if ( json['css'][i]['css_set_id'] == IPB3CSS.currentSetData['set_id'] )
				{
					/* This is a modified row */
					json['css'][i]['_cssState']  = 'modified';
					json['css'][i]['_cssClass']  = 'tplateRow_mod';
				}
				else if ( IPB3CSS.currentSetData['_parentTree'].indexOf( json['css'][i]['css_set_id'] ) != -1 )
				{
					/* Inherited */
					json['css'][i]['_cssState']  = 'inherit';
					json['css'][i]['_cssClass']  = 'tplateRow_inh';
				}
				else
				{
					/* This is a default row */
					json['css'][i]['_cssState']  = 'default';
					json['css'][i]['_cssClass']  = 'tplateRow_def';
				}
				
				_output += _tplate.evaluate( json['css'][i] );
			}
		} );
		
		/* Write it out */
		$('tplate_cssList').update( _output );
		
		/* Post Process */
		_groups.each( function( i )
		{
			/* Inline deleted? */
			if ( ! json['css'][i]['_deleted'] )
			{
				if ( json['css'][i]['_cssState'] == 'new' )
				{
					$('tplate_cssRow_' + json['css'][i]['css_id'] + '_revert').update('Remove');
				}
				else if ( json['css'][i]['_cssState'] != 'default' )
				{
					$('tplate_cssRow_' + json['css'][i]['css_id'] + '_revert').show();
				}
				else
				{
					$('tplate_cssRow_' + json['css'][i]['css_id'] + '_revert').hide();
				}
			}
		} );
	}
	
	/**
	* Template Mouse Event
	*/
	this.mouseEvent = function( e )
	{
		if ( _div = $( Event.element( e ) ).up( '.' + IPB3CSS.cssNames['cssRow'] ) )
		{
			var _css_id = _div.id.replace( 'tplate_cssRow_', '' );
		
			if ( e.type == 'mouseover' )
			{
				_div.addClassName( IPB3CSS.cssNames['cssHover'] );
			}
			else if ( e.type == 'mouseout' )
			{
				_div.removeClassName( IPB3CSS.cssNames['cssHover'] );
			}
			else if ( e.type == 'click' )
			{
				var _el = Event.findElement( e, 'div' ).id;
				
				if ( _el.endsWith('_revert' ) )
				{
					IPB3CSS.revertCSS( _css_id );
				}
				else
				{
					IPB3CSS.loadCSSEditor( _css_id );
				}
			}
		}
	}

}