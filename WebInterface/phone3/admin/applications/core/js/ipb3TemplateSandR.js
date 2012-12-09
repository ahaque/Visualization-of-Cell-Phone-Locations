/**
* INVISION POWER BOARD v3
*
* Topics View Javascript File
* @author Matt Mecham, Brandon Farber, Josh Williams
* @since 2008
*/

/**
* "ACP" Class. Designed for ... ACP Template Search And Replace Functions
* @author (v.1.0.0) Matt Mecham
*/
						
function IPBTemplateSandR()
{
	/**
	* Session ID
	*/
	this.sessionID  = '';
	
	/*
	* JSON
	*/
	this.sessionData          = {};
	this.templateGroups       = {};
	this.setData              = {};
	this.currentTemplateBit   = {};
	this.currentTemplateGroup = {};
	
	/**
	* Init Function
	* @author Matt Mecham 
	*/
	this.init = function()
	{
		/* Set Session ID */
		this.sessionID = this.sessionData['sandr_session_id'];
		/* Build group list */
		this.buildGroupsList();
	};
	
	/**
	* Toggle all template bits box
	*/
	this.toggleGroupBox = function( groupName )
	{
		var checked = $( 'cbox_group_' + groupName ).checked;

		$$('.cboxGroup' + groupName ).each(function(cbox) 
		{
			 cbox.checked = checked;
		});
	}
	
	/**
	* Check group check box
	*/
	this.checkGroupBox = function( groupName )
	{
		var _checkedCount = 0;
		var _allCount	  = 0;
		
		$$('.cboxGroup' + groupName ).each(function(cbox) 
		{
			if ( cbox.checked )
			{
				_checkedCount++;
			}
			
			_allCount++;
		});
		
		$( 'cbox_group_' + groupName ).checked = ( _allCount == _checkedCount ) ? true : false;
	}
	
	/**
	* Load template bits
	*/
	this.loadTemplateEditor = function( template_id )
	{
		/* Grab the JSON for the template bits for the current template group */
		url = ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=templates&amp;do=getTemplateForEdit&amp;setID=" + this.setData['set_id'] + '&template_id=' + template_id + '&secure_key=' + ipb.vars['md5_hash'];
	
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
										IPB3TemplatesSandR.showTemplateEditor( json );
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
	* Shows the template editor
	*/
	this.showTemplateEditor = function( json )
	{
		/* Close any open editors */
		IPB3TemplatesSandR.cancelTemplateBit( json['templateData']['template_id'] );
		
		var _tplate = new Template( $('tplate_templateEditor').innerHTML );
		
		IPB3TemplatesSandR.currentTemplateBit = json['templateData'];
		
		var _div         = document.createElement( "DIV" );
		_div.id          = 'tplate_editorWrapper';
		_div.update( _tplate.evaluate( json['templateData'] ) );
		document.body.appendChild( _div );
		
		ipb.positionCenter( 'tplate_editor_' + json['templateData']['template_id'] );
	}
	
	/**
	* Peform the placements
	*/
	this.performReplacement = function()
	{
		/* Grab the JSON for the template bits for the current template group */
		url  = ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=templatesandr&amp;do=replace&&sessionID=" + this.sessionID + '&secure_key=' + ipb.vars['md5_hash'];
		
		$('replaceButton').disabled = true;
		$('replaceButton').value = "Saving...";
		
		new Ajax.Request( url.replace( /&amp;/g, '&' ),
						  {
							method: 'POST',
							parameters: $('sandrForm').serialize(true),
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
										if ( json['errors'] )
										{
											/* Something inline would be very nice here.. rather than this ugly alert */
											alert("UGLY ALERT BOX SAYS: " + json['errors'] );
										}
										else
										{
											$('replaceButton').disabled = false;
											$('replaceButton').value = "Replace Selected";
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
	* Save template bit
	*/
	this.saveTemplateBit = function( template_id )
	{
		/* Grab the JSON for the template bits for the current template group */
		url = ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=templates&amp;do=saveTemplateBit&amp;setID=" + this.setData['set_id'] + '&template_id=' + template_id + '&secure_key=' + ipb.vars['md5_hash'];
		
		/* Set up params */
		var params = { 'template_content' : $F('tplate_editBox_' + template_id ),
					   'template_data'    : $F('tplate_dataBox_' + template_id ),
				       'template_set'     : this.setData['set_id'],
				       'template_group'   : IPB3TemplatesSandR.currentTemplateGroup,
				       'type'             : 'edit' };
				
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
										IPB3TemplatesSandR.currentTemplateBit = json['templateData'];
										IPB3TemplatesSandR.showTemplateEditor( json );
										
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
	* Shows the template editor
	*/
	this.cancelTemplateBit = function( templateID )
	{
		try
		{
			$('tplate_editorWrapper').remove();
		}
		catch(e)
		{
		}
	}
	
	/**
	* Build a list of template groups
	*/
	this.buildGroupsList = function()
	{
		/* INIT */
		var _output = '';
		var _tplate = new Template( $('tplate_groupRow').innerHTML );
		var json    = IPB3TemplatesSandR.templateGroups;
		
		var _groups = Object.keys( json['groups'] ).sort();
		
		/* Clear out any current listing */
		$('tplate_groupList').update('');
		
		/* Format... */
		_groups.each( function( i )
		{
			_output += _tplate.evaluate( { 'groupName' : i, '_matches' : Object.keys( json['groups'][i] ).length } );
		} );
		
		/* Write it out */
		$('tplate_groupList').update( _output );
		
		/* Post process */
		_groups.each( function( i )
		{
			if ( IPB3TemplatesSandR.sessionData['sandr_search_only'] )
			{
				$( 'groupRowCbox_' + i ).hide();
			}
			else
			{
				$( 'groupRowCbox_' + i ).show();
			}
		} );
	};
	
	/**
	* Build a list of template bits
	*/
	this.buildTemplateList = function( groupName, json )
	{
		/* INIT */
		var _output = '';
		var _tplate = new Template( $('tplate_templateRow').innerHTML );
		
		IPB3TemplatesSandR.currentTemplateGroup = groupName;
		
		/* Clear out any current listing */
		$( 'groupRowTemplates_' + groupName ).update('');
		
		/* Add in the new... */
		for( i = 0 ; i < json['templates'].length; i++ )
		{
			_output += _tplate.evaluate( json['templates'][i] );
		};
		
		/* Write it out */
		$( 'groupRowTemplates_' + groupName ).update( _output );
		
		/* Post process */
		for( i = 0 ; i < json['templates'].length; i++ )
		{
			if ( IPB3TemplatesSandR.sessionData['sandr_search_only'] )
			{
				$( 'templateRowCbox_' + json['templates'][i]['template_id'] ).hide();
			}
			else
			{
				$( 'templateRowCbox_' + json['templates'][i]['template_id'] ).show();
				
				/* Checked, please? */
				IPB3TemplatesSandR.toggleGroupBox( json['templates'][i]['template_group'] );
			}
		};
	};
	
	/**
	* Show / Hide templates
	*/
	this.toggleTemplates = function( groupName )
	{
		if ( $('groupRowTemplates_' + groupName ).hasClassName( 'tmplShow' ) )
		{
			$('groupRowTemplates_' + groupName ).removeClassName( 'tmplShow' );
			$('groupRowTemplates_' + groupName ).hide();
		}
		else
		{
			$('groupRowTemplates_' + groupName ).addClassName( 'tmplShow' );
			$('groupRowTemplates_' + groupName ).show();
			
			/* Ajax */
			/* Grab the JSON for the template bits for the current template group */
			url = ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=templatesandr&amp;do=getTemplateBitList&amp;setID=" + this.setData['set_id'] + '&templateGroup=' + groupName + '&secure_key=' + ipb.vars['md5_hash'] + '&sessionID=' + this.sessionID;

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
											IPB3TemplatesSandR.buildTemplateList( groupName, json );
										}
									}
									else
									{
										alert( "Ooops. Something went wrong. Does this help?: " + t.responseText );
									}
								}
							  } );
		}
	}
}