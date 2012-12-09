/**
* INVISION POWER BOARD v3
*
* Topics View Javascript File
* @author Matt Mecham, Brandon Farber, Josh Williams
* @since 2008
*/

/**
* "ACP" Class. Designed for ... ACP Templating Functions
* @author (v.1.0.0) Matt Mecham
*/

function IPBTemplates()
{
	/**
	* Template groups
	*
	* @var	array
	*/
	this.templateGroups = {};
	
	/**
	* Current template group
	*
	* @var	array
	*/
	this.currentTemplateGroup = '';
	
	/**
	* Current template bit
	*
	* @var	array
	*/
	this.currentTemplateBit = '';
	
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
	this.cssNames = { 'groupRowLit'     : 'tplateLit',
					  'groupHover'      : 'groupHover',
					  'groupRowLitMain' : 'tablerow1',
					  'groupRow'        : 'tablerow2',
					  'templateRow'     : 'tablerow2',
					  'templateHover'   : 'templateHover' };
	
	
	/**
	* INIT Applications
	*/
	this.init = function()
	{
		/* Build group list */
		this.buildGroupsList();
		
		/* fix template window to same height as group list and make it scrollable */
		$('tplate_bitList').setStyle( { 'height' : $('tplate_groupList').getHeight() + 'px', 'overflow' : 'auto'} );
	
		this.loadTemplateBits( this.currentTemplateGroup );
	}
	
	/**
	* Load template groups
	*/
	this.loadTemplateGroups = function()
	{
		/* Grab the JSON for the template bits for the current template group */
		url = ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=templates&amp;do=getTemplateGroupList&amp;setID=" + this.currentSetData['set_id'] + '&secure_key=' + ipb.vars['md5_hash'];
	
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
										IPB3Templates.templateGroups = json;
										IPB3Templates.buildGroupsList();
										
										/* Hilite */
										IPB3Templates.hiliteGroup( IPB3Templates.currentTemplateGroup );
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
	this.loadTemplateBits = function( groupName, force )
	{		
		/* Grab the JSON for the template bits for the current template group */
		url = ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=templates&amp;do=getTemplateBitList&amp;setID=" + this.currentSetData['set_id'] + '&templateGroup=' + groupName + '&secure_key=' + ipb.vars['md5_hash'];
	
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
										var _reload = 0;
										
										/* New group? */
										if ( ! IPB3Templates.templateGroups['groups'][ groupName ] )
										{
											IPB3Templates.templateGroups['groups'][ groupName ] = { '_modCount' : 0, 'template_group' : groupName };
											_reload = 1;
										}
										
										/* Update group's template count */
										if ( json['groupData'] )
										{
											if ( json['groupData']['_modCount'] != IPB3Templates.templateGroups['groups'][ groupName ]['_modCount'] )
											{
												IPB3Templates.templateGroups['groups'][ groupName ]['_modCount'] = json['groupData']['_modCount'];
												_reload = 1;
											}
										}
										
										/* Re-draw list */
										if ( _reload )
										{
											IPB3Templates.buildGroupsList();
										}
										
										/* Hilite */
										IPB3Templates.hiliteGroup( groupName );
										IPB3Templates.buildTemplateList( json );
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
	* Save template bit
	*/
	this.saveTemplateBit = function( template_id )
	{
		/* Grab the JSON for the template bits for the current template group */
		url = ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=templates&amp;do=saveTemplateBit&amp;setID=" + this.currentSetData['set_id'] + '&template_id=' + template_id + '&secure_key=' + ipb.vars['md5_hash'];
		
		/* Set up params */
		var params = { 'template_content' : $F('tplate_editBox_' + template_id ),
					   'template_data'    : $F('tplate_dataBox_' + template_id ),
				       'template_set'     : this.currentSetData['set_id'],
				       'template_group'   : IPB3Templates.currentTemplateGroup,
				       'type'             : IPB3Templates.currentTemplateBit['_type'] };
				
		if ( IPB3Templates.currentTemplateBit['_type'] == 'add' )
		{
			params['_template_name']  = $F('tplate_addNameTextField_0');
		    params['_template_group'] = $F('tplate_addGroupTextField_0');
		}
		
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
										IPB3Templates.currentTemplateBit = json['templateData'];
										IPB3Templates.showTemplateEditor( json );
										IPB3Templates.loadTemplateBits( json['templateData']['template_group'] );
										
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
	* Revert template bit
	*/
	this.revertTemplateBit = function( template_id )
	{
		/* Make sure we're mean it... */
		if ( $('tplate_templaterow_' + template_id ).hasClassName( 'tplateRow_new')  )
		{
			if ( ! confirm( "REMOVE template bit?\n This will remove this template bit in ALL skins!" ) )
			{
				return false;
			}
		}
		else
		{
			if ( ! confirm( "Revert template bit?\n All changes WILL be lost!" ) )
			{
				return false;
			}
		}
		
		/* Grab the JSON for the template bits for the current template group */
		url = ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=templates&amp;do=revertTemplateBit&amp;setID=" + this.currentSetData['set_id'] + '&template_id=' + template_id + '&secure_key=' + ipb.vars['md5_hash'];
	
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
										if ( json['templateData'] )
										{
											IPB3Templates.currentTemplateBit = json['templateData'];
										}
										else
										{
											/* Reload the group */
											IPB3Templates.loadTemplateGroups();
										}
										
										IPB3Templates.loadTemplateBits( IPB3Templates.currentTemplateBit['template_group'] );
										
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
	this.loadTemplateEditor = function( template_id )
	{
		/* Grab the JSON for the template bits for the current template group */
		url = ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=templates&amp;do=getTemplateForEdit&amp;setID=" + this.currentSetData['set_id'] + '&template_id=' + template_id + '&secure_key=' + ipb.vars['md5_hash'];
	
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
										IPB3Templates.showTemplateEditor( json );
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
	* Shows the new template bit form
	*/
	this.addBitForm = function()
	{
		/* Close any open editors */
		IPB3Templates.cancelTemplateBit( 0 );
		
		var _tplate = new Template( $('tplate_templateEditorAdd').innerHTML );
		var _data   = { 'template_id'      : 0,
						'template_set_id'  : IPB3Templates.currentSetData['set_id'],
					    'template_group'   : IPB3Templates.currentTemplateGroup,
					    'template_content' : '',
					    '_type'            : 'add' };
													
		var _div         = document.createElement( "DIV" );
		_div.id          = 'tplate_editorWrapper';
		_div.update( _tplate.evaluate( _data ) );
		document.body.appendChild( _div );
		
		ipb.positionCenter( 'tplate_editor_0' );
		
		IPB3Templates.currentTemplateBit = _data;
	}
	
	/**
	* Shows the template editor
	*/
	this.showTemplateEditor = function( json )
	{
		/* Close any open editors */
		IPB3Templates.cancelTemplateBit( json['templateData']['template_id'] );
		
		var _tplate = new Template( $('tplate_templateEditor').innerHTML );
		
		IPB3Templates.currentTemplateBit = json['templateData'];
		
		var _div         = document.createElement( "DIV" );
		_div.id          = 'tplate_editorWrapper';
		_div.update( _tplate.evaluate( json['templateData'] ) );
		document.body.appendChild( _div );
		
		ipb.positionCenter( 'tplate_editor_' + json['templateData']['template_id'] );
	}
	
	/**
	* Shows the template editor
	*/
	this.cancelTemplateBit = function( templateID )
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
	* Shows a group entry as 'lit'
	*/
	this.hiliteGroup = function( templateGroup )
	{
		/* Un-hi-lite all groups */
		var _groups = Object.keys( IPB3Templates.templateGroups['groups'] ).sort();
		
		/* Format... */
		_groups.each( function( i )
		{
			/* Fix up template */
			$('tplate_grouprow_' + i ).removeClassName( IPB3Templates.cssNames['groupRowLitMain'] );
			$('tplate_grouprow_' + i ).removeClassName( IPB3Templates.cssNames['groupRowLit'] );
			$('tplate_grouprow_' + i ).addClassName( IPB3Templates.cssNames['groupRow'] );
		} );
		
		$('tplate_grouprow_' + templateGroup ).removeClassName( IPB3Templates.cssNames['groupRow'] );
		$('tplate_grouprow_' + templateGroup ).addClassName( IPB3Templates.cssNames['groupRowLitMain'] );
		$('tplate_grouprow_' + templateGroup ).addClassName( IPB3Templates.cssNames['groupRowLit'] );
	}
	
	/**
	* Build a list of template groups
	*/
	this.buildGroupsList = function()
	{
		/* INIT */
		var _output = '';
		var _tplate = new Template( $('tplate_groupRow').innerHTML );
		var json    = IPB3Templates.templateGroups;
		
		var _groups = Object.keys( json['groups'] ).sort();
		
		/* Clear out any current listing */
		$('tplate_groupList').update('');
		
		/* Format... */
		_groups.each( function( i )
		{
			_output += _tplate.evaluate( { 'groupName' : i, '_modCount' : json['groups'][i]['_modCount'] } );
		} );
		
		/* Write it out */
		$('tplate_groupList').update( _output );
	}
	
	/**
	* Build a list of template bits
	*/
	this.buildTemplateList = function( json )
	{
		/* INIT */
		var _output = '';
		var _tplate = new Template( $('tplate_templateRow').innerHTML );
		
		/* Clear out any current listing */
		$('tplate_bitList').update('');
		
		/* Add in the new... */
		for( i = 0 ; i < json['templates'].length; i++ )
		{
			/* Figure out if this in an inherited row, a changed row, a new row or a default row */
			if ( json['templates'][i]['template_added_to'] == IPB3Templates.currentSetData['set_id'] )
			{
				/* This is a modified row */
				json['templates'][i]['_templateState']  = 'new';
				json['templates'][i]['_cssClass']       = 'tplateRow_new';
			}
			else if ( json['templates'][i]['template_set_id'] == IPB3Templates.currentSetData['set_id'] )
			{
				/* This is a modified row */
				json['templates'][i]['_templateState']  = 'modified';
				json['templates'][i]['_cssClass']       = 'tplateRow_mod';
			}
			else if ( IPB3Templates.currentSetData['_parentTree'].indexOf( json['templates'][i]['template_set_id'] ) != -1 )
			{
				/* Inherited */
				json['templates'][i]['_templateState']  = 'inherit';
				json['templates'][i]['_cssClass']       = 'tplateRow_inh';
			}
			else
			{
				/* This is a default row */
				json['templates'][i]['_templateState']  = 'default';
				json['templates'][i]['_cssClass']       = 'tplateRow_def';
			}
			
			_output += _tplate.evaluate( json['templates'][i] );
		};
		
		/* Write it out */
		$('tplate_bitList').update( _output );
		
		/* Post Process */
		for( i = 0 ; i < json['templates'].length; i++ )
		{
			if ( json['templates'][i]['_templateState'] == 'new' )
			{
				$('tplate_templateRow_' + json['templates'][i]['template_id'] + '_revert').update('Remove');
			}
			else if ( json['templates'][i]['_templateState'] != 'default' )
			{
				$('tplate_templateRow_' + json['templates'][i]['template_id'] + '_revert').show();
			}
			else
			{
				$('tplate_templateRow_' + json['templates'][i]['template_id'] + '_revert').hide();
			}
		}
	}
	
	
	/**
	* Template Mouse Event
	*/
	this.templateMouseEvent = function( e )
	{
		if ( _div = $( Event.element( e ) ).up( '.' + IPB3Templates.cssNames['templateRow'] ) )
		{
			var _template_id = _div.id.replace( 'tplate_templaterow_', '' );
			
			if ( e.type == 'mouseover' )
			{
				_div.addClassName( IPB3Templates.cssNames['templateHover'] );
			}
			else if ( e.type == 'mouseout' )
			{
				_div.removeClassName( IPB3Templates.cssNames['templateHover'] );
			}
			else if ( e.type == 'click' )
			{
				var _el = Event.findElement( e, 'div' ).id;
				
				if ( _el.endsWith('_revert' ) )
				{
					IPB3Templates.revertTemplateBit( _template_id );
				}
				else
				{
					IPB3Templates.loadTemplateEditor( _template_id );
				}
			}
		}
	}
	
	/**
	* Group Mouse Event
	*/
	this.groupMouseEvent = function( e )
	{
		var groupName = $( Event.findElement( e, 'div' ) ).id;
		var _groupName = groupName.replace( 'tplate_grouprow_', '' );
		
		if ( e.type == 'mouseover' )
		{
			$( groupName ).addClassName( IPB3Templates.cssNames['groupHover'] );
		}
		else if ( e.type == 'mouseout' )
		{
			$( groupName ).removeClassName( IPB3Templates.cssNames['groupHover'] );
		}
		else if ( e.type == 'click' )
		{
			IPB3Templates.currentTemplateGroup = _groupName;
			
			IPB3Templates.loadTemplateBits( _groupName );
		}
	}

}