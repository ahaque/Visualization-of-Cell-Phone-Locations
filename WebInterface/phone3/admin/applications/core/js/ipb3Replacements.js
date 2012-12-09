/**
* INVISION POWER BOARD v3
*
* Topics View Javascript File
* @author Matt Mecham, Brandon Farber, Josh Williams
* @since 2008
*/

/**
* "ACP" Class. Designed for ... ACP Replacements Functions
* @author (v.1.0.0) Matt Mecham
*/

function IPBReplacements()
{
	/**
	* Template groups
	*
	* @var	array
	*/
	this.ReplacementsData = {};
	
	/**
	* Current template bit
	*
	* @var	array
	*/
	this.currentReplacement = '';
	
	/**
	* Current set ID
	*
	* @var	array
	*/
	this.currentSetData = {};
	
	/**
	* Replacements Names
	*
	* @var hash
	*/
	this.cssNames = { 
					  'replacementsRow'          : 'tablerow2',
					  'replacementsHover'        : 'replacementsHover' };
	
	
	/**
	* INIT Applications
	*/
	this.init = function()
	{
		/* Build Replacements list */
		this.buildReplacementsList();
	}
	
	/**
	* Load css groups
	*/
	this.loadReplacementsGroups = function()
	{
		/* Grab the JSON for the template bits for the current template group */
		url = ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=replacements&amp;do=getReplacementsGroups&amp;setID=" + this.currentSetData['set_id'] + '&secure_key=' + ipb.vars['md5_hash'];
	
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
										IPB3Replacements.ReplacementsData = json;
										IPB3Replacements.buildReplacementsList();
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
	* Save Replacements
	*/
	this.saveReplacement = function( replacement_id )
	{
		/* Grab the JSON for the template bits for the current template group */
		url = ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=replacements&amp;do=saveReplacement&amp;setID=" + this.currentSetData['set_id'] + '&replacement_id=' + replacement_id + '&secure_key=' + ipb.vars['md5_hash'];
		
		/* Set up params */
		var params = { 'replacement_content'  : $F('tplate_editBox_' + replacement_id ),
				       'replacement_set_id'   : this.currentSetData['set_id'],
				       '_replacement_key'     : $F('tplate_keyBox_'  + replacement_id ),
				       'type'                 : ( replacement_id == 0 ) ? 'add' : 'edit' };
				
		
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
										IPB3Replacements.ReplacementsData = json;
										IPB3Replacements.buildReplacementsList();
										
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
	this.revertReplacement = function( replacement_id )
	{
		/* Make sure we're mean it... */
		if ( $('tplate_replacementsRow_' + replacement_id ).hasClassName( 'tplateRow_new')  )
		{
			if ( ! confirm( "REMOVE Replacements file?\n This will remove this replacement in ALL skins!" ) )
			{
				return false;
			}
		}
		else
		{
			if ( ! confirm( "Revert Replacements file?\n All changes WILL be lost!" ) )
			{
				return false;
			}
		}
		
		/* Grab the JSON for the template bits for the current template group */
		url = ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=replacements&amp;do=revertReplacement&amp;setID=" + this.currentSetData['set_id'] + '&replacement_id=' + replacement_id + '&secure_key=' + ipb.vars['md5_hash'];
	
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
										IPB3Replacements.ReplacementsData = json;
										IPB3Replacements.buildReplacementsList();
										
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
	* Add Replacements form
	*/
	this.addReplacementsForm = function()
	{
		IPB3Replacements.showReplacementsEditor( 0 );
	}
	
	/**
	* Shows the template editor
	*/
	this.showReplacementsEditor = function( replacement_id )
	{
		/* Close any open editors */
		IPB3Replacements.cancelReplacements();
		
		var _tplate = new Template( $('tplate_replacementsEditor').innerHTML );
		var _keys   = Object.keys( IPB3Replacements.ReplacementsData['replacements'] ).sort();
		var _data   = {};
		
		/* Get replacement Data */
		if ( replacement_id )
		{
			_keys.each( function( i )
			{
				if ( IPB3Replacements.ReplacementsData['replacements'][ i ]['replacement_id'] == replacement_id )
				{
					_data = IPB3Replacements.ReplacementsData['replacements'][ i ];
				}
			} );
		}
		else
		{
			_data = { 'replacement_id' : 0, 'replacement_key' : '', 'SAFE_replacement_content' : '' };
		}
	
		var _div         = document.createElement( "DIV" );
		_div.id          = 'tplate_editorWrapper';
		_div.update( _tplate.evaluate( _data ) );
		document.body.appendChild( _div );
		
		/* New? */
		if ( ! replacement_id || ( _data['replacement_added_to'] == IPB3Replacements.currentSetData['set_id'] ) )
		{
			$( 'tplate_keyBoxWrap_' + replacement_id ).show();
		}
		
		/* Adjust title */
		if ( ! replacement_id )
		{
			$('tplate_title_' + replacement_id ).update("Adding Replacement");
		}
		
		ipb.positionCenter( 'tplate_editor_' + replacement_id );
	}
	
	/**
	* Preview the replacement
	*/
	this.previewReplacement = function( replacement_id )
	{
		/* Close any open editors */
		IPB3Replacements.closePreview();
		
		var _tplate = new Template( $('tplate_replacementsPreview').innerHTML );
		var _keys   = Object.keys( IPB3Replacements.ReplacementsData['replacements'] ).sort();
		var _data   = {};
		
		/* Get replacement Data */
		_keys.each( function( i )
		{
			if ( IPB3Replacements.ReplacementsData['replacements'][ i ]['replacement_id'] == replacement_id )
			{
				_data = IPB3Replacements.ReplacementsData['replacements'][ i ];
			}
		} );
		
		/* For mat.... oh, for me? */
		_data['_preview'] = _data['replacement_content'].escapeHTML();
		
		/* But, is it an image? */
		if ( _data['replacement_content'].startsWith( "<img" ) )
		{
			_data['_preview'] = _data['replacement_content'].sub( '\{style_image_url\}', IPB3Replacements.imgURL );
		}
		
		var _div         = document.createElement( "DIV" );
		_div.id          = 'tplate_previewWrapper';
		_div.update( _tplate.evaluate( _data ) );
		document.body.appendChild( _div );
		
		ipb.positionCenter( 'tplate_preview_' + _data['replacement_id'] );
	}
	
	/**
	* Close Preview
	*/
	this.closePreview = function()
	{
		try
		{
			$('tplate_previewWrapper').remove();
		}
		catch(e)
		{
		}
	}
	
	/**
	* Shows the template editor
	*/
	this.cancelReplacements = function()
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
	* Build a list of Replacements groups (files)
	*/
	this.buildReplacementsList = function()
	{
		/* INIT */
		var _output = '';
		var _tplate = new Template( $('tplate_replacementsRow').innerHTML );
		var json    = IPB3Replacements.ReplacementsData;
		
		var _groups = Object.keys( json['replacements'] ).sort();
		
		/* Clear out any current listing */
		$('tplate_replacementsList').update('');
		
		/* Format... */
		_groups.each( function( i )
		{
			/* Figure out if this in an inherited row, a changed row, a new row or a default row */
			if ( json['replacements'][i]['replacement_added_to'] == IPB3Replacements.currentSetData['set_id'] )
			{
				/* This is a modified row */
				json['replacements'][i]['_cssState']  = 'new';
				json['replacements'][i]['_cssClass']  = 'tplateRow_new';
			}
			else if ( json['replacements'][i]['replacement_set_id'] == IPB3Replacements.currentSetData['set_id'] )
			{
				/* This is a modified row */
				json['replacements'][i]['_cssState']  = 'modified';
				json['replacements'][i]['_cssClass']  = 'tplateRow_mod';
			}
			else if ( IPB3Replacements.currentSetData['_parentTree'].indexOf( json['replacements'][i]['replacement_set_id'] ) != -1 )
			{
				/* Inherited */
				json['replacements'][i]['_cssState']  = 'inherit';
				json['replacements'][i]['_cssClass']  = 'tplateRow_inh';
			}
			else
			{
				/* This is a default row */
				json['replacements'][i]['_cssState']  = 'default';
				json['replacements'][i]['_cssClass']  = 'tplateRow_def';
			}
			
			_output += _tplate.evaluate( json['replacements'][i] );
			
		} );
		
		/* Write it out */
		$('tplate_replacementsList').update( _output );
		
		/* Post Process */
		_groups.each( function( i )
		{
			/* Inline deleted? */
			if ( json['replacements'][i]['_cssState'] == 'new' )
			{
				$('tplate_replacementsRow_' + json['replacements'][i]['replacement_id'] + '_revert').update('Remove');
			}
			else if ( json['replacements'][i]['_cssState'] != 'default' )
			{
				$('tplate_replacementsRow_' + json['replacements'][i]['replacement_id'] + '_revert').show();
			}
			else
			{
				$('tplate_replacementsRow_' + json['replacements'][i]['replacement_id'] + '_revert').hide();
			}
			
		} );
	}
	
	/**
	* Template Mouse Event
	*/
	this.mouseEvent = function( e )
	{
		if ( _div = $( Event.element( e ) ).up( '.' + IPB3Replacements.cssNames['replacementsRow'] ) )
		{
			var _replacement_id = _div.id.replace( 'tplate_replacementsRow_', '' );
		
			if ( e.type == 'mouseover' )
			{
				_div.addClassName( IPB3Replacements.cssNames['replacementsHover'] );
			}
			else if ( e.type == 'mouseout' )
			{
				_div.removeClassName( IPB3Replacements.cssNames['replacementsHover'] );
			}
			else if ( e.type == 'click' )
			{
				var _el = Event.findElement( e, 'div' ).id;
				
				if ( _el.endsWith('_revert' ) )
				{
					IPB3Replacements.revertReplacement( _replacement_id );
				}
				else if ( _el.endsWith('_preview' ) )
				{
					IPB3Replacements.previewReplacement( _replacement_id );
				}
				else
				{
					IPB3Replacements.showReplacementsEditor( _replacement_id );
				}
			}
		}
	}

}