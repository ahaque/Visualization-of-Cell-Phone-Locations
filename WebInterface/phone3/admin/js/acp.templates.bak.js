/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* acp.templates.js - Template editor functions	*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/

var _temp = window.IPBACP;

_temp.prototype.template_editor = {
	
	templateGroups: $H(),
	currentTemplateGroup: '',
	currentSetData: $A(),
	animDur: 0.4,
	templateSyntax: 'html',
	cssSyntax: 'css',
	toolbarDisabled: true,
	currentlyOpen: $H(),
	events: $H(),
	popups: $H(),
	
	// OK, listen up, this is complex.
	// If we edit an inherited template bit, then we would need to update the ID we use throughout,
	// because it would have changed from when the file was opened. Instead of closing and reopening
	// the editor though, I'm going to create a map here of old ID => new ID. We can check this to
	// make sure the correct ID is sent for relevant requests. Got it?
	modifyMap: {
		'css': $A,
		'template': $A
	},
	
	/* ------------------------------ */
	/**
	 * Gets things going, sets events etc.
	*/
	initialize: function()
	{
		acp.template_editor.buildGroups();
		acp.template_editor.loadGroupBits( '', acp.template_editor.currentTemplateGroup, 1 );
		
		acp.template_editor.buildCSS();
		
		// Set up tab handling
		$( 'e_templates' ).observe('click', acp.template_editor.toggleList.bindAsEventListener( this, 'templates' ) );
		$( 'e_css' ).observe('click', acp.template_editor.toggleList.bindAsEventListener( this, 'css' ) );
		
		// Add events for the add bit/add group/add css links
		$( 't_add_bit' ).observe('click', acp.template_editor.launchAddBit);
		$( 'css_add_css' ).observe('click', acp.template_editor.launchAddCSS);
		
		// Add a body event handler to detect unsaved files
		Event.observe( window, 'unload', acp.template_editor.checkUnsavedFiles );
	},
	
	/* ------------------------------ */
	/**
	 * Shows the popup for adding a css file
	*/
	launchAddCSS: function(e)
	{
		Event.stop(e);
		
		// Have we already made the popup before?
		if( !Object.isUndefined( acp.template_editor.popups['add_css'] ) )
		{
			acp.template_editor.popups['add_css'].show();
			return;
		}
		
		// Pre-make popup
		acp.template_editor.popups['add_css'] = new ipb.Popup('add_css_popup', { type: 'pane', modal: false, hideAtStart: true, w: '600px', initial: ipb.templates['form_add_css'] } );
		var popup = acp.template_editor.popups['add_css'];
		
		$('add_css_submit').observe('click', acp.template_editor.doAddCSS);
		popup.show();
	},
	
	/* ------------------------------ */
	/**
	 * Processes adding a new CSS file
	*/
	doAddCSS: function(e)
	{
		var cssid = 0;
		var url		= ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=css&amp;do=saveCSS&amp;setID=" + acp.template_editor.currentSetData['set_id'] +
		 				'&css_id=' + cssid + '&type=add&secure_key=' + ipb.vars['md5_hash'];		
		/* Set up params */
		var params = { 'css_content'  : '',
					   'css_position' : 0,
				       'css_set_id'   : acp.template_editor.currentSetData['set_id'],
				       '_css_group'   : $F('add_css_name').encodeParam(), // this is actually the file name
				       'type'         : 'add'
					};
		
		if( $F('add_css_name').blank() )
		{
			alert("The css name cannot be blank");
			return;
		}
		
		acp.template_editor.setLoadingMsg('Adding...');	
		
		new Ajax.Request( url.replace( /&amp;/g, '&' ), {
							method: 'post',
							parameters: params,
							evalJSON: 'force',
							onSuccess: function(t)
							{
								if( Object.isUndefined( t.responseJSON ) )
								{
									alert("There was an error adding this template bit");
									return;
								}
								else if( t.responseJSON['error'] )
								{
									alert("There was an error adding this template bit: " + t.responseJSON['error']);
									return;
								}
								
								var cssid = t.responseJSON['cssData']['css_id'];
								var cssname = t.responseJSON['cssData']['css_group'];
								
								// Add file to list
								acp.template_editor._buildCSS( true );
								
								// Create object to pass to editor
								var new_file = { 
									id: 'css_file_' + cssid,
									text: '', title: cssname + ".css",
									syntax: acp.template_editor.cssSyntax
								}
								editAreaLoader.openFile('editor_main', new_file);

								if( Object.isUndefined( acp.template_editor.currentlyOpen.get( 'css_' + cssid ) ) ){
									acp.template_editor.currentlyOpen.set( 'css_' + cssid, t.responseJSON['cssData'] );
								}

								// Does the toolbar need enabling?
								if( acp.template_editor.toolbarDisabled ){
									acp.template_editor.enableToolbar('css');
								}
								
								// And finally...
								$('add_css_name').value = '';
								acp.template_editor.popups['add_css'].hide();
							},
							onException: function( f,e ){ alert( "Exception: " + e ) },
							onFailure: function( t ){ alert( "Failure: " + t.responseText ) },
							onComplete: function(t){ acp.template_editor.setLoadingMsg(''); }
						});
	},
	
	/* ------------------------------ */
	/**
	 * Shows the popup for adding a bit
	*/
	launchAddBit: function(e)
	{
		Event.stop(e);
				
		// Have we already made the popup before?
		if( !Object.isUndefined( acp.template_editor.popups['add_bit'] ) )
		{
			acp.template_editor.popups['add_bit'].show();
			return;
		}
		
		// Pre-make popup
		acp.template_editor.popups['add_bit'] = new ipb.Popup('add_bit_popup', { type: 'pane', modal: false, hideAtStart: true, w: '600px', initial: ipb.templates['form_add_bit'] } );
		var popup = acp.template_editor.popups['add_bit'];
		

		// Build the list of groups
		if( Object.isUndefined( acp.template_editor.templateGroups['groups'] ) ){
			alert("An error occurred generating the template groups");
			return;
		}
		
		$H( acp.template_editor.templateGroups['groups'] ).each( function(group){							
			var title = group.value['template_group'];							
			var elem = new Element('option', { 'value': group.value['template_group'] });
			elem.update( title );			
			$( 'add_bit_group' ).insert( elem );
		});
		
		$('add_bit_submit').observe('click', acp.template_editor.doAddBit);
		
		popup.show();
		/*popup.positionPane();
		popup.show();*/			
	},
	
	/* ------------------------------ */
	/**
	 * Processes adding a bit
	 * 
	 * @param	{event}		e		The event
	*/
	doAddBit: function(e)
	{
		var fileid = 0;
		var url		= ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=templates&amp;do=saveTemplateBit&amp;setID=" +
		 			  acp.template_editor.currentSetData['set_id'] + '&template_id=' + fileid + '&type=add&secure_key=' + ipb.vars['md5_hash'];
		var newGroup = $F('add_bit_new_group');
		var currentGroup = newGroup ? 'skin_' + newGroup : $F('add_bit_group');
		var params 	= { 	'template_content' : '',
				       		'template_set'     : acp.template_editor.currentSetData['set_id'],
				       		'template_group'   : currentGroup.encodeParam(),
							'template_data'	   : $F('add_bit_variables'),
							'_template_name'   : $F('add_bit_name').encodeParam(),
						    '_template_group'  : currentGroup
				 	};
		
		if( $F('add_bit_name').blank() )
		{
			alert("The template name cannot be blank");
			return;
		}
		
		/*if( !$('group_' + currentGroup ) )
		{
			alert("Could not find the selected group");
			return;
		}*/
		
		acp.template_editor.setLoadingMsg('Adding...');		
		
		new Ajax.Request( url.replace( /&amp;/g, '&' ), {
							method: 'post',
							parameters: params,
							evalJSON: 'force',
							onSuccess: function(t, e)
							{
								if( Object.isUndefined( t.responseJSON ) )
								{
									alert("There was an error adding this template bit");
									return;
								}
								else if( t.responseJSON['error'] )
								{
									alert("There was an error adding this template bit: " + t.responseJSON['error']);
									return;
								}
								
								var templateid = t.responseJSON['templateData']['template_id'];
								
								// Is this a new group?
								if( newGroup )
								{
									var newGroupList = ipb.templates['template_group'].evaluate( { id: 'group_skin_' + newGroup, title: 'skin_' + newGroup } );
									$('template_list').insert( { bottom: newGroupList } );
									
									// Create the wrapper
									var wrap = new Element('ul', { id: 'group_skin_' + newGroup + '_bits' }).addClassName('bits_list');
									$( 'group_skin_' + newGroup ).insert( wrap );
								}
								
								// Create object to pass to editor
								var new_file = { 
									id: 'template_bit_' + templateid,
									text: '', title: t.responseJSON['templateData']['template_name'],
									syntax: acp.template_editor.templateSyntax
								}
								editAreaLoader.openFile('editor_main', new_file);

								if( Object.isUndefined( acp.template_editor.currentlyOpen.get( 'template_' + templateid ) ) ){
									acp.template_editor.currentlyOpen.set( 'template_' + templateid, t.responseJSON['templateData'] );
								}
								
								// Reload bits
								acp.template_editor.loadGroupBits(e, currentGroup, true)
								
								// Does the toolbar need enabling?
								if( acp.template_editor.toolbarDisabled ){
									acp.template_editor.enableToolbar('template');
								}
								
								
								// And finally...
								$('add_bit_name').value = '';
								$('add_bit_variables').value = '';
								acp.template_editor.popups['add_bit'].hide();
																
							},
							onException: function( f,e ){ alert( "Exception: " + e ) },
							onFailure: function( t ){ alert( "Failure: " + t.responseText ) },
							onComplete: function(t){ acp.template_editor.setLoadingMsg(''); }
							
						});
	},
	
	/* ------------------------------ */
	/**
	 * Shows the popup for adding a group
	*/
	launchAddGroup: function(e)
	{
		
	},
	
	/* ------------------------------ */
	/**
	 * Event fired when navigating away from the page
	 * Checks whether there's unsaved files
	 * 
	 * @param	{event}		e		The event
	*/
	checkUnsavedFiles: function(e)
	{
		var files = $H( editAreaLoader.getAllFiles('editor_main') );
		var changes = false;
		
		if( !Object.isUndefined( files ) && files.size() )
		{
			files.each( function( file ){
				Debug.dir( file );
				if( file.value['edited'] ){
					changes = true;
				}
			});
		}
		
		if( changes )
		{
			if( confirm( "Navigating away from this page will cause the changes you've made to be lost. Are you sure you want to continue?" ) )
			{
				return true;
			}
		}
		
		return false;
	},
	
	/* ------------------------------ */
	/**
	 * Toggles between showing templates & css
	 * 
	 * @param	{event}		e		The event
	 * @param	{string}	tab		The tab to show (temmplates or css)
	*/
	toggleList: function(e, tab)
	{
		if( tab == 'templates' && !$('e_templates').hasClassName('active') )
		{
			// Make templates active
			$( 'css_list_wrap' ).hide();
			$( 'template_list_wrap' ).show();
			$( 'e_templates' ).addClassName('active');
			$( 'e_css' ).removeClassName('active');
		}
		else if( tab == 'css' && !$('e_css').hasClassName('active') )
		{
			// Make css active			
			$( 'template_list_wrap' ).hide();
			$( 'css_list_wrap' ).show();
			$( 'e_css' ).addClassName('active');
			$( 'e_templates' ).removeClassName('active');
		}
	},
	
	/* ------------------------------ */
	/**
	 * Callback function from EditArea when loading
	 * 
	 * @param	{string}	id		The editor ID
	*/
	CALLBACK_editor_loaded: function(id)
	{
		//editAreaLoader.hide( id );
		acp.template_editor._hideEditor();
	},
	
	/* ------------------------------ */
	/**
	 * Callback: when switching tab in editor
	 * 
	 * @param	{object}	file	File info from editarea
	*/
	CALLBACK_file_switch: function(file)
	{
		if( file['id'].startsWith('template_') ){
			acp.template_editor.enableToolbar('template', file['id'].replace('template_bit_', '') );
		} else if (file['id'].startsWith('css_') ){
			acp.template_editor.enableToolbar('css', file['id'].replace('css_file_', '' ) );
		}
	},
	
	/* ------------------------------ */
	/**
	 * Builds the list of CSS files. Used internally
	 * so that the ajax is asynchronized
	 * @param	boolean		TRUE means grab data from DB first
	*/
	_buildCSS: function( reload )
	{
		if ( reload == true )
		{
			acp.template_editor.setLoadingMsg('Loading...');

			var url = ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=css&amp;do=getCSSGroups&amp;setID=" + acp.template_editor.currentSetData['set_id'] + '&secure_key=' + ipb.vars['md5_hash'];

			new Ajax.Request( url.replace( /&amp;/g, '&' ),
							{
								method: 'get',
								evalJSON: 'force',
								onSuccess: function(t)
								{
									if( Object.isUndefined( t.responseJSON ) )
									{
										alert( "The server returned an error: " + t.responseText );
										return;
									}
									else if( t.responseJSON['error'] )
									{
										alert( "The server returned an error: " + t.responseJSON['error'] );
										return;
									}
									
									var current = editAreaLoader.getCurrentFile('editor_main');
									
									acp.template_editor.cssFiles['css'] = t.responseJSON['css'];
									acp.template_editor.buildCSS();
									acp.template_editor.disableToolbar();
									
									acp.template_editor.enableToolbar('css', current['id'].replace( 'css_file_', '' ) );
								},
								onFailure: function(t){ alert("There was an error executing this command"); Debug.error(t.responseText); return; },
								onException: function(f,e){ alert("There was an exception executing this command"); Debug.error(e); return; },
								onComplete: function(t){ acp.template_editor.setLoadingMsg(''); }
							});
		}
		else
		{
			acp.template_editor.buildCSS();
		}
	},
	
	/* ------------------------------ */
	/**
	 * Builds the list of CSS files
	 * @param	boolean		TRUE means grab data from DB first
	*/
	buildCSS: function()
	{
		$( 'css_list' ).update();
		
		$H( acp.template_editor.cssFiles['css'] ).each( function(css){		
			var icon = 'default';
	
			// Determine icon
			/* Root skin? */
			if ( ! acp.template_editor.currentSetData['_parentTree'].length && acp.template_editor.currentSetData['_isMaster'] == 1  )
			{
				icon = 'root';
			}
			else if( css.value['css_added_to'] == acp.template_editor.currentSetData['set_id'] )
			{
				icon = 'new';
			}
			else if( css.value['css_set_id'] == acp.template_editor.currentSetData['set_id'] )
			{
				icon = 'modified';
			}
			else if( acp.template_editor.currentSetData['_parentTree'].indexOf( css.value['css_set_id'] ) != -1 )
			{
				icon = 'inherit';
			}
			
			var item = ipb.templates['css_file'].evaluate( { id: 'css_' + css.value['css_id'], name: css.value['css_group'] + '.css', icon: icon } );
			$( 'css_list' ).insert( item );
			
			if( icon == 'new' || icon == 'root' )
			{
				$('delete_css_css_' + css.value['css_id']).show();
				$('delete_css_css_' + css.value['css_id']).observe('click', acp.template_editor.removeFile.bindAsEventListener( this, css.value ) );
			}
			
			$( 'css_' + css.value['css_id'] ).writeAttribute('state', icon);
			$( 'css_' + css.value['css_id'] ).observe('click', acp.template_editor.loadCSSfile.bindAsEventListener( this, css.value['css_id'], 0 ) );
		});
	},
	
	/* ------------------------------ */
	/**
	 * Builds the list of template groups
	*/
	buildGroups: function()
	{
		$( 'template_list' ).update();
		
		//if( !acp.template_editor.templateGroups['groups'] ){ return; }
		$H( acp.template_editor.templateGroups['groups'] ).each( function(group){
			//Debug.dir( group );
			
			var title = ( Object.isUndefined( ipb.lang['bit_' + group.value['template_group'] ] ) ) ?
							group.value['template_group'] :
							ipb.lang['bit_' + group.value['template_group'] ];
		
			var item = ipb.templates['template_group'].evaluate( { id: 'group_' + group.key, title: title } );
			$( 'template_list' ).insert( item );
			$( 'group_' + group.key ).observe('click', acp.template_editor.loadGroupBits.bindAsEventListener( this, group.key, 0 ));
		});
	},
	
	/* ------------------------------ */
	/**
	 * Loads a CSS file from the server if it isn't open already
	 * 
	 * @param	{event}		e		The event
	 * @param	{int}		cssid	The CSS file ID
	*/
	loadCSSfile: function( e, cssid )
	{
		// Is this file already open?
		if( !Object.isUndefined( acp.template_editor.currentlyOpen.get('css_' + acp.template_editor.reverseCheckMap( cssid, 'css' ) ) ) )
		{
			editAreaLoader.execCommand('editor_main', 'switch_to_file', 'css_file_' + acp.template_editor.reverseCheckMap( cssid, 'css' ) );
			return true;
		}
		
		acp.template_editor.setLoadingMsg('Loading...');
		
		var url = ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=css&amp;do=getCSSForEdit&amp;setID=" + acp.template_editor.currentSetData['set_id'] + '&amp;css_id=' + acp.template_editor.checkMap( cssid, 'css' ) + '&secure_key=' + ipb.vars['md5_hash'];
		
		new Ajax.Request( url.replace( /&amp;/g, '&' ),
						{
							method: 'get',
							evalJSON: 'force',
							onSuccess: function(t)
							{
								if( Object.isUndefined( t.responseJSON ) )
								{
									alert( "The server returned an error: " + t.responseText );
									return;
								}
								else if( t.responseJSON['error'] )
								{
									alert( "The server returned an error: " + t.responseJSON['error'] );
									return;
								}
								
								var cssfile = t.responseJSON['cssData'];
								//Debug.dir( cssfile );
								acp.template_editor.openCSSfile( cssid, cssfile );
							},
							onFailure: function(t){ alert("There was an error executing this command"); Debug.error(t.responseText); return; },
							onException: function(f,e){ alert("There was an exception executing this command"); Debug.error(e); return; },
							onComplete: function(t){ acp.template_editor.setLoadingMsg(''); }
						});
								
	},
	
	/* ------------------------------ */
	/**
	 * Opens a CSS file in the editor
	 * 
	 * @param	{int}		cssid		The CSS file ID
	 * @param	{object}	json		The object returned from ajax
	*/
	openCSSfile: function( cssid, json )
	{
		// Create object to pass to editor
		var new_css = { id: 'css_file_' + cssid, text: json['_css_content'], title: json['css_group'] + '.css', syntax: acp.template_editor.cssSyntax, 'do_highlight' : false }
		editAreaLoader.openFile('editor_main', new_css);

		if( Object.isUndefined( acp.template_editor.currentlyOpen.get( 'css_' + cssid ) ) )
		{
			acp.template_editor.currentlyOpen.set( 'css_' + cssid, json );
		}

		// Does the toolbar need enabling?
		if( acp.template_editor.toolbarDisabled )
		{
			acp.template_editor.enableToolbar('css', cssid);
		}
	},
	
	/* ------------------------------ */
	/**
	 * Loads the template bits within a group
	 * 
	 * @param	{event}		e		The event
	 * @param	{string}	group	The group to load
	 * @param	{boolean}	force	Force update even if group exists?
	*/
	loadGroupBits: function(e, group, force)
	{
		// Already exists?
		if( $( 'group_' + group + '_bits' ) && !force )
		{
			acp.template_editor.toggleBitsList( e, group );
			return true;
		}
		
		// Set to loading
		$('group_' + group).addClassName('loading');
		
		/* Grab the JSON for the template bits for the current template group */
		var url = ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=templates&amp;do=getTemplateBitList&amp;setID=" + acp.template_editor.currentSetData['set_id'] + '&templateGroup=' + group + '&secure_key=' + ipb.vars['md5_hash'];
		
		new Ajax.Request( url.replace( /&amp;/g, '&' ),
						  {
							method: 'get',
							evalJSON: 'force',
							onSuccess: function (t)
							{
								if( Object.isUndefined( t.responseJSON ) )
								{
									alert( "The server returned an error: " + t.responseText );
									return;
								}
								else if( t.responseJSON['error'] )
								{
									alert( "The server returned an error: " + t.responseJSON['error'] );
									return;
								}
								
								acp.template_editor.buildTemplateList( group, t.responseJSON );
								acp.template_editor.toggleBitsList( e, group );	
								
								var current = editAreaLoader.getCurrentFile('editor_main');
								
								if( ! Object.isUndefined( current ) && ! Object.isUndefined( current['id'] ) && current['id'] )
								{
									acp.template_editor.disableToolbar();
									acp.template_editor.enableToolbar('template', current['id'].replace( 'template_bit_', '' ) );
								}				
							},
							onFailure: function(t){ alert("There was an error executing this command"); Debug.error(t.responseText); return; },
							onException: function(f,e){ alert("There was an exception executing this command"); Debug.error(e); return; },
							onComplete: function(t){ $('group_' + group).removeClassName('loading'); }
						});
	},
	
	/* ------------------------------ */
	/**
	 * Toggle a bits list thats already populated
	 * 
	 * @param	{event}		e		The event
	 * @param	{string}	group	The group to toggle
	*/
	toggleBitsList: function( e, group )
	{
		if( e )
		{
			var elem = $( Event.element(e) ).up('ul.bits_list');
			
			if( !Object.isUndefined( elem ) && elem != document )
			{
				// We dont want to toggle the menu for this
				Event.stop(e); 
				return;
			}
		}
		
		if( !$( 'group_' + group + '_bits' ) ){
			return false;
		}
		
		if( $('group_' + group + '_bits').visible() )
		{
			new Effect.BlindUp( $('group_' + group + '_bits'), { duration: acp.template_editor.animDur } );
		}
		else
		{
			new Effect.BlindDown( $('group_' + group + '_bits'), { duration: acp.template_editor.animDur } );
		}
	},
	
	/* ------------------------------ */
	/**
	 * Builds the list of template groups
	 * 
	 * @param	{string}	group	The group ID
	 * @param	{object}	json	The JSON passed into the page containing the groups
	*/
	buildTemplateList: function( group, json )
	{
		// Remove old list if it exists
		if( $( 'group_' + group + '_bits' ) ){
			$( 'group_' + group + '_bits' ).remove();
		}
		
		// Create the wrapper
		var wrap = new Element('ul', { id: 'group_' + group + '_bits' }).hide().addClassName('bits_list');
		$( 'group_' + group ).insert( wrap );
		
		
		for( i = 0 ; i < json['templates'].length; i++ )
		{
			var state = '';
			var css = '';
			
			/* Figure out if this in an inherited row, a changed row, a new row or a default row */
			/* Root skin? */
			if ( ! acp.template_editor.currentSetData['_parentTree'].length && acp.template_editor.currentSetData['set_id'] != 1 && acp.template_editor.currentSetData['_isMaster'] == 1  )
			{
				state = 'root';
			}
			else if ( json['templates'][i]['template_added_to'] == acp.template_editor.currentSetData['set_id'] )
			{
				/* This is a modified row */
				state  = 'new';
			}
			else if ( json['templates'][i]['template_set_id'] == acp.template_editor.currentSetData['set_id'] )
			{
				/* This is a modified row */
				state = 'modified';
			}
			else if ( acp.template_editor.currentSetData['_parentTree'].indexOf( json['templates'][i]['template_set_id'] ) != -1 )
			{
				/* Inherited */
				state = 'inherit';
			}
			else
			{
				/* This is a default row */
				state = 'default';
			}
							
			var elem = ipb.templates['template_bit'].evaluate( { id: 'group_' + group + '_bit_' + json['templates'][i]['template_id'], title: json['templates'][i]['template_name'], icon: state } );
			$('group_' + group + '_bits').insert( elem );
			
			// Show delete?
			if( state == 'new' )
			{
				$('delete_bit_group_' + group + '_bit_' + json['templates'][i]['template_id']).show();
				$('delete_bit_group_' + group + '_bit_' + json['templates'][i]['template_id']).observe('click', acp.template_editor.removeFile.bindAsEventListener( this, json['templates'][i] ) );
			}
			
			$( 'group_' + group + '_bit_' + json['templates'][i]['template_id'] ).writeAttribute('state', state);
			$( 'group_' + group + '_bit_' + json['templates'][i]['template_id'] ).observe('click', acp.template_editor.loadTemplateBit.bindAsEventListener( this, json['templates'][i]['template_id'] ) );
		};		
	},
	
	/* ------------------------------ */
	/**
	 * Removes a template bit from the list
	*/
	removeFile: function( e, file )
	{
		Event.stop(e);
		
		if( file['template_id'] )
		{
			// Template
			var fileid = file['template_id'];
			var filename = file['template_name'];
			var elemprefix = "group_" + file['template_group'] + "_bit_";
			var url = "app=core&module=ajax&section=templates&do=revertTemplateBit&setID=" + this.currentSetData['set_id'] + "&template_id=" + acp.template_editor.checkMap( fileid, 'template' );
			var message = "Are you sure you want to delete this template bit from this set and all child sets? THIS CANNOT BE UNDONE.";
		}
		else
		{
			//CSS
			var fileid     = file['css_id'];
			var filename   = file['css_group'];
			var elemprefix = "css_";
			var url        = "app=core&module=ajax&section=css&do=revertCSS&fromDelete=1&setID=" + acp.template_editor.currentSetData['set_id'] + "&css_id=" + acp.template_editor.checkMap( fileid, 'css' );
			var message    = "Are you sure that you want to delete this CSS file from this set and all child sets?";
		}
			
		// Check they want to
		if( !confirm( message ) )
		{
			return;
		}
		
		url = ipb.vars['base_url'] + url;
		acp.template_editor.setLoadingMsg('Removing...');
		
		new Ajax.Request( url.replace(/&amp;/g, '&' ),
						{
							method: 'GET',
							evalJSON: 'force',
							onSuccess: function( t )
							{
								if( Object.isUndefined( t.responseJSON ) )
								{
									alert( "The server returned an error: " + t.responseText );
									return;
								}
								else if( t.responseJSON['errors'] )
								{
									alert("The server returned an error: " + t.responseJSON['errors'] );
									return;
								}
								
								// Remove that bit from the list
								if( $(elemprefix + fileid) )
								{
									new Effect.BlindUp( $(elemprefix + fileid ), { duration: 0.4, afterFinish: function()
										{
											$(elemprefix + fileid).remove();
										}
									});
								}
								
								// Is the file open? We need to close it
								if( file['template_id'] )
								{
									if( !Object.isUndefined( acp.template_editor.currentlyOpen.get('template_' + fileid) ) ){
										editAreaLoader.closeFile('editor_main', 'template_bit_' + fileid);
										return true;
									}
								}
								else
								{
									if( !Object.isUndefined( acp.template_editor.currentlyOpen.get('css_' + fileid) ) ){
										editAreaLoader.closeFile('editor_main', 'css_file_' + fileid);
										return true;
									}
								}
							},
							onException: function( f,e ){ alert( "Exception: " + e ) },
							onFailure: function( t ){ alert( "Failure: " + t.responseText ) },
							onComplete: function(t){ acp.template_editor.setLoadingMsg(''); }
						});
	},
	
	/* ------------------------------ */
	/**
	 * Reverts the currently open file (template or css)
	*/
	revertCurrentFile: function(e)
	{
		Event.stop(e);
		
		// Get current file info
		var current = editAreaLoader.getCurrentFile('editor_main');
		
		if( Object.isUndefined( current ) || Object.isUndefined( current['id'] ) )
		{
			return false;
		}
		
		if( current['id'].startsWith('template_') )
		{
			// Template
			var type	= 'template_';
			var fileid 	= current['id'].replace('template_bit_', '');
			var info	= acp.template_editor.currentlyOpen.get('template_' + fileid);
			var url 	= "app=core&module=ajax&section=templates&do=revertTemplateBit&setID=" + acp.template_editor.currentSetData['set_id'] + "&template_id=" + acp.template_editor.checkMap( fileid, 'template' ) + '&secure_key=' + ipb.vars['md5_hash'];
			var message = "Are you sure you want to remove ALL customizations to this bit in this set? THIS CANNOT BE UNDONE.";
		}
		else
		{
			var type	= 'css_';
			var fileid 	= current['id'].replace('css_file_', '');
			var info	= acp.template_editor.currentlyOpen.get('css_' + fileid );
			var url 	= "app=core&amp;module=ajax&amp;section=css&amp;do=revertCSS&amp;setID=" +
			 			  acp.template_editor.currentSetData['set_id'] + '&css_id=' + acp.template_editor.checkMap( fileid, 'css' ) + '&secure_key=' + ipb.vars['md5_hash'];
			var message = "Are you sure you want to remove ALL customizations to this CSS file in this set? THIS CANNOT BE UNDONE.";
		}
		
		// Are they sure?
		if( !confirm( message ) )
		{
			return;
		}
		
		acp.template_editor.setLoadingMsg('Reverting...');
		
		url = ipb.vars['base_url'] + url;
		
		new Ajax.Request( url.replace(/&amp;/g, '&' ),
						{
							method: 'GET',
							evalJSON: 'force',
							onSuccess: function( t, e )
							{
								if( Object.isUndefined( t.responseJSON ) )
								{
									alert( "The server returned an error: " + t.responseText );
									return;
								}
								else if( t.responseJSON['error'] )
								{
									alert("The server returned an error: " + t.responseJSON['error'] );
									return;
								}
								
								if( type == 'template_' )
								{		
									// Update text editor content
									editAreaLoader.setValue( 'editor_main', t.responseJSON['templateData']['template_content'] );
								
									// Set not edited
									editAreaLoader.setFileEditedMode('editor_main', 'template_bit_' + fileid, false);
									
									Debug.write( "Current fileid is " + fileid );
									Debug.write( "Returned fileid is " + t.responseJSON['templateData']['template_id'] );
									
									if( acp.template_editor.checkMap( fileid, 'template' ) != t.responseJSON['templateData']['template_id'] )
									{
										acp.template_editor.modifyMap['template'][ fileid ]  = t.responseJSON['templateData']['template_id'];
										Debug.write("Updated modify map!");
									}
									
									// Update array
									acp.template_editor.currentlyOpen.set( 'template_' + fileid, t.responseJSON['templateData'] );
									
									acp.template_editor.loadGroupBits(e, t.responseJSON['templateData']['template_group'], true);
								}
								else
								{
									// Update text editor content
									editAreaLoader.setValue( 'editor_main', t.responseJSON['cssData'][ info['css_group'] ]['css_content'] );
								
									// Set not edited
									editAreaLoader.setFileEditedMode('editor_main', 'css_file_' + fileid, false);
									
									Debug.write( "Current cssid is " + fileid );
									Debug.write( "Returned cssid is " + t.responseJSON['cssData'][ info['css_group'] ]['css_id'] );
									
									if ( acp.template_editor.checkMap( fileid, 'css' ) != t.responseJSON['cssData'][ info['css_group'] ]['css_id'] )
									{
										acp.template_editor.modifyMap['css'][ fileid ]  = t.responseJSON['cssData'][ info['css_group'] ]['css_id'];
										Debug.write("Updated modify map! fileid " + fileid + " is now " + acp.template_editor.modifyMap['css'][ fileid ] );
									}
									
									/* Rebuild CSS groups */
									acp.template_editor._buildCSS( true );
									
									// Scroll to top
									setTimeout("editAreaLoader.execCommand('editor_main', 'scroll_to_view', 'top');", 50);
									
									//Debug.dir( t.responseJSON );
								}
							},
							onException: function( f,e ){ alert( "Exception: " + e ) },
							onFailure: function( t ){ alert( "Failure: " + t.responseText ) },
							onComplete: function(t){ acp.template_editor.setLoadingMsg(''); }
						});
	},
	
	/* ------------------------------ */
	/**
	 * Loads a template bit
	 * 
	 * @param	{event}		e				The event
	 * @param	{int}		templateid		The template ID to load
	*/
	loadTemplateBit: function( e, templateid )
	{
		// Is this bit already open?
		if( !Object.isUndefined( acp.template_editor.currentlyOpen.get('template_' + acp.template_editor.reverseCheckMap( templateid, 'template' ) ) ) )
		{
			editAreaLoader.execCommand('editor_main', 'switch_to_file', 'template_bit_' + acp.template_editor.reverseCheckMap( templateid, 'template' ) );
			return true;
		}
		
		acp.template_editor.setLoadingMsg('Loading...');
		
		url = ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=templates&amp;do=getTemplateForEdit&amp;setID=" + this.currentSetData['set_id'] + '&template_id=' + acp.template_editor.checkMap( templateid, 'template' ) + '&secure_key=' + ipb.vars['md5_hash'];
	
		new Ajax.Request( url.replace( /&amp;/g, '&' ),
						  {
							method: 'GET',
							evalJSON: 'force',
							onSuccess: function (t )
							{
								if( Object.isUndefined( t.responseJSON ) )
								{
									alert("The server returned an error: " + t.responseText );
									return;
								}
								else if( t.responseJSON['error'] )
								{
									alert("The server returned an error: " + t.responseJSON['error'] );
									return;
								}
								
								var template = t.responseJSON['templateData'];
								//Debug.dir( template );
								acp.template_editor.openTemplateBit( templateid, template );
							},
							onException: function( f,e ){ alert( "Exception: " + e ) },
							onFailure: function( t ){ alert( "Failure: " + t.responseText ) },
							onComplete: function(t){ acp.template_editor.setLoadingMsg(''); }
						  } );
	},
	
	/* ------------------------------ */
	/**
	 * Opens a template bit in the editor
	 * 
	 * @param	{int}		templateid		The template ID
	 * @param	{object}	json			The object returned from ajax
	*/
	openTemplateBit: function( templateid, json )
	{	
		if( Object.isUndefined( acp.template_editor.currentlyOpen.get( 'template_' + templateid ) ) )
		{
			acp.template_editor.currentlyOpen.set( 'template_' + templateid, json );
		}
		
		//opera.postError("Pre-loading file");
		
		// Create object to pass to editor
		var new_template = { id: 'template_bit_' + templateid, text: json['_template_content'], title: json['template_name'], syntax: acp.template_editor.templateSyntax }
		editAreaLoader.openFile('editor_main', new_template);
		
		//opera.postError("Post-loading file");
		
		// Does the toolbar need enabling?
		if( acp.template_editor.toolbarDisabled )
		{
			acp.template_editor.enableToolbar('template', templateid);
		}
	},
	
	/* ------------------------------ */
	/**
	 * Enables the toolbar for templates or css
	 * 
	 * @param	{string}	type	The type of toolbar (css or template)
	*/
	enableToolbar: function( type, fileid )
	{
		Debug.write( type + ' - ' + fileid );
		
		/* Preserve old-un */
		_fileid = fileid;
		
		/* Grab remap */
		fileid = acp.template_editor.checkMap( fileid, type );
		
		Debug.write( 'Checked with map, fileid now - ' + fileid );
		
		var toolbar = $('document_buttons');
		var elems = $( toolbar ).immediateDescendants();
	
		if( $('t_save') )
		{
			var save = $('t_save').observe('click', acp.template_editor.saveCurrentFile);
			acp.template_editor.events.set('save', save );
			$( 't_save' ).removeClassName('disabled');
			$( 't_save' ).setStyle('cursor: pointer;');
		}
		if( $('t_revert') )
		{
			var state = null;
			
			// Check whether we can revert this bit
			try {
				if( type == 'template' )
				{
					// Get current file info
					var info 	= acp.template_editor.currentlyOpen.get( 'template_' + _fileid );
					var state 	= $('group_' + info['template_group'] + '_bit_' + fileid).readAttribute('state');
				}
				else
				{
					var info 	= acp.template_editor.currentlyOpen.get( 'css_' + fileid );
					var state 	= $('css_' + fileid).readAttribute('state');
				}
			} catch(err) { Debug.error( err ) }
			
			if( state == 'modified' )
			{
				var revert = $('t_revert').observe('click', acp.template_editor.revertCurrentFile);
				acp.template_editor.events.set('revert', revert );
				$( 't_revert' ).show().removeClassName('disabled').setStyle('cursor: pointer;');
			}
			else
			{
				$('t_revert').stopObserving('click', acp.template_editor.revertCurrentFile);
				$('t_revert').show().addClassName('disabled').setStyle('cursor: default');
			}
		}
		if( $('t_compare') )
		{
			//if( type == 'template' )
			//{
				var compare = $('t_compare').observe('click', acp.template_editor.compareTemplate);
				acp.template_editor.events.set('compare', compare );
				$( 't_compare' ).show().removeClassName('disabled').setStyle('cursor: pointer;');
			//}
			//else
			//{
			//	$('t_compare').stopObserving('click', acp.template_editor.compareTemplate);
			//	$( 't_compare' ).addClassName('disabled').setStyle('cursor: default;').hide();
			//}
		}
		if( $('t_variables') )
		{
			if( type == 'template' )
			{
				var variables = $('t_variables').observe('click', acp.template_editor.showVariables);
				acp.template_editor.events.set('variables', variables );
				$('t_variables').show().removeClassName('disabled').setStyle('cursor: pointer;');
			}
			else
			{
				$('t_variables').stopObserving('click', acp.template_editor.showVariables);
				$('t_variables').addClassName('disabled').setStyle('cursor: default').hide();
			}
		}
		if( $('t_properties') )
		{
			if( type == 'template' )
			{
				$('t_properties').stopObserving('click', acp.template_editor.showCssProperties);
				$('t_properties').hide().addClassName('disabled').setStyle('cursor: default');
			}
			else
			{
				var properties = $('t_properties').observe('click', acp.template_editor.showCssProperties);
				acp.template_editor.events.set('properies', properties);
				$('t_properties').show().removeClassName('disabled').setStyle('cursor: pointer');
			}
		}
		
		/* Unhide editor */
		acp.template_editor._unhideEditor();
	},
	
	/* ------------------------------ */
	/**
	 * Disables the toolbar
	*/
	disableToolbar: function()
	{
		if( $('t_save') )
		{
			$('t_save').stopObserving('click', acp.template_editor.saveCurrentFile);
			$('t_save').addClassName('disabled').setStyle('cursor: default;');
		}
		if( $('t_revert') )
		{
			$('t_revert').stopObserving('click', acp.template_editor.revertTemplate);
			$('t_revert').addClassName('disabled').setStyle('cursor: default;').hide();
		}
		if( $('t_compare') )
		{
			$('t_compare').stopObserving('click', acp.template_editor.compareTemplate);
			$('t_compare').addClassName('disabled').setStyle('cursor: default;').hide();
		}
		if( $('t_variables') )
		{
			$('t_variables').stopObserving('click', acp.template_editor.showVariables);
			$('t_variables').addClassName('disabled').setStyle('cursor: default;').hide();
		}
		if( $('t_properties') )
		{
			$('t_properties').stopObserving('click', acp.template_editor.showCssProperties);
			$('t_properties').addClassName('disabled').setStyle('cursor: default;').hide();
		}
	},
	
	/* ------------------------------ */
	/**
	 * Shows the panel for css property editing
	*/
	showCssProperties: function(e)
	{
		Event.stop(e);
		
		// Get current file info
		var current = editAreaLoader.getCurrentFile('editor_main');
		
		if( Object.isUndefined( current ) || Object.isUndefined( current['id'] ) )
		{
			return false;
		}
		
		if( !current['id'].startsWith('css_') )
		{
			Debug.error( "This isn't a CSS file!" );
			return;
		}
		
		var _id    = current['id'].replace('css_file_', '');
		var fileid = ( Object.isUndefined( acp.template_editor.currentlyOpen.get( 'css_' + _id ) ) ) ? acp.template_editor.checkMap( _id, 'css' ) : _id;
		var file   = acp.template_editor.currentlyOpen.get( 'css_' + fileid );
		
		// How many files are there?
		var cssfiles = $('css_list').childElements().size();
		var options = '';
		
		if( cssfiles )
		{
			for( i = 0; i <= cssfiles; i++ )
			{
				if( i != parseInt( file['css_position'] ) ){
					options += "<option value='" + i + "'>" + i + "</option>\n";
				} else {
					options += "<option value='" + i + "' selected='selected'>" + i + "</option>\n";
				}
			}
		}
		
		// Now we can build/show the popup
		if( acp.template_editor.popups.get('properties_' + fileid) )
		{
			$('cssposition_' + fileid).update( options );
			acp.template_editor.popups.get('properties_' + fileid).show();
		}
		else
		{
			Debug.dir( file );
			
			var content = ipb.templates['css_properties'].evaluate( { 
				id: 				fileid,
				cssposition: 		options,
				attributes: 		file['css_attributes'],
				app: 				( file['css_app'] == "0" ) ? "" : file['css_app'],
				modules: 			( file['css_modules'] ) ? file['css_modules'] : '',
				apphide: 			( file['css_app_hide'] == "0" ) ? "" : "checked='checked'"
			});
			
			var popup = new ipb.Popup( 'variables', { type: 'pane', modal: false, w: '700px', initial: content, hideAtStart: false, close: 'a[rel="close"]' } );
			acp.template_editor.popups.set('properties_' + fileid, popup);
			
			// Add event handler for saving
			$('save_properties_' + fileid).observe('click', acp.template_editor.saveCssProperties.bindAsEventListener( this, fileid ) );
		}
	},
	
	/* ------------------------------ */
	/**
	 * Saves properties changed with the above function
	*/
	saveCssProperties: function(e, fileid)
	{
		var url 	= ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=css&amp;do=saveCSS&amp;setID=" +
		 			  acp.template_editor.currentSetData['set_id'] + '&css_id=' + acp.template_editor.checkMap( fileid, 'css' ) + '&secure_key=' + ipb.vars['md5_hash'];
		
		var currentContent = acp.template_editor.currentlyOpen.get('css_' + fileid)['css_content'];
		var currentSet = acp.template_editor.currentSetData['set_id'];
		var cssPosition = $F('cssposition_' + fileid);
		var cssAttributes = $F('cssattributes_' + fileid);
		var cssApp     = $F('cssapp_' + fileid);
		var cssModules = $F('cssmodules_' + fileid);
		var cssHideApp = $F('cssapphide_' + fileid);
		var cssName = acp.template_editor.currentlyOpen.get('css_' + fileid)['css_group'];
		
		/* Set up params */
		var params 	= { 	'css_content'  		: editAreaLoader.getValue('editor_main').encodeParam(),
				       		'css_set_id'   		: acp.template_editor.currentSetData['set_id'],
							'_css_group'		: cssName.encodeParam(),
				       		'type'         		: 'edit',
							'css_app'	   		: cssApp,
							'css_app_hide' 		: cssHideApp,
							'css_attributes'	: cssAttributes,
							'css_modules'		: cssModules,
							'css_position'		: cssPosition
				 	};
				
		acp.template_editor.setLoadingMsg('Saving properties...');
		
		new Ajax.Request( url.replace( /&amp;/g, '&' ),
						  {
							method: 'POST',
							parameters: params,
							evalJSON: 'force',
							onSuccess: function (t)
							{

								if( Object.isUndefined( t.responseJSON ) )
								{
									alert( "The server returned an error: " + t.responseText );
									return;
								}
								else if( t.responseJSON['errors'] )
								{
									acp.template_editor.displayError( t.responseJSON['errors'] );
									return;
								}

								//-----------------------------------
								Debug.write("Current file ID: " + fileid);
								Debug.write("File ID returned: " + t.responseJSON['cssData']['css_id']);
								
								if( acp.template_editor.checkMap( fileid, 'css' ) != t.responseJSON['cssData']['css_id'] )
								{
									acp.template_editor.modifyMap['css'][ fileid ] = t.responseJSON['cssData']['css_id'];
									Debug.write("Updated modify map!");
								}
								//-----------------------------------
								
								// Update array
								acp.template_editor.currentlyOpen.set( 'css_' + fileid, t.responseJSON['cssData'] );
								acp.template_editor.popups.get('properties_' + fileid).hide();
								acp.template_editor._buildCSS( true );
								
							},
							onException: function( f,e ){ alert( "Exception: " + e ) },
							onFailure: function( t ){ alert( "Failure: " + t.responseText ) },
							onComplete: function(t){ acp.template_editor.setLoadingMsg(''); }
						});
	},
	
	/* ------------------------------ */
	/**
	 * Shows the panel for variable editing
	 * 
	 * @param	{event}		e		The event
	*/
	showVariables: function(e)
	{
		Event.stop(e);
		
		// Get current file info
		var current = editAreaLoader.getCurrentFile('editor_main');
		
		if( Object.isUndefined( current ) || Object.isUndefined( current['id'] ) )
		{
			return false;
		}
		
		if( !current['id'].startsWith('template_') )
		{
			Debug.error( "This isn't a template" );
			return;
		}
		
		var _id    = current['id'].replace('template_bit_', '');
		var fileid = ( Object.isUndefined( acp.template_editor.currentlyOpen.get( 'template_' + _id ) ) ) ? acp.template_editor.checkMap( _id, 'template' ) : _id;
		var _data  = acp.template_editor.currentlyOpen.get( 'template_' + fileid )['template_data'].replace( '&#34;', "'", 'g' ).replace( '&#039;', "'", 'g' );
		
		var content = ipb.templates['edit_variables'].evaluate( { id: fileid, value: _data } );
		
		// Is there already a popup for this?
		if( acp.template_editor.popups.get('variables_' + fileid) )
		{
			$('variables_' + fileid).value = _data;
			acp.template_editor.popups.get('variables_' + fileid).show();
		}
		else
		{
			var popup = new ipb.Popup( 'variables', { type: 'pane', modal: false, w: '800px', initial: content, hideAtStart: false, close: 'a[rel="close"]' } );
			acp.template_editor.popups.set('variables_' + fileid, popup);
		}
		
		// Add event handler for saving
		$('edit_variables_' + fileid).observe('click', acp.template_editor.saveVariables.bindAsEventListener( this, fileid ) );
	},
	
	/* ------------------------------ */
	/**
	 * Saves changes to the template variables
	 * 
	 * @param	{event}		e		The event
	 * @param	{string}	fileid	The id of the file being edited
	*/
	saveVariables: function(e, fileid)
	{
		if( !$('variables_' + fileid) ){ Debug.error("No variable box found"); return; }
		
		var url		= ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=templates&amp;do=saveTemplateBit&amp;setID=" +
		 			  acp.template_editor.currentSetData['set_id'] + '&template_id=' + acp.template_editor.checkMap( fileid, 'template' ) + '&secure_key=' + ipb.vars['md5_hash'];
		
		var currentGroup   = acp.template_editor.currentlyOpen.get('template_' + fileid)['template_group'];
		var currentContent = acp.template_editor.currentlyOpen.get('template_' + fileid)['template_content'];
		var currentSet     = acp.template_editor.currentSetData['set_id'];
		var variableData   = $F('variables_' + fileid);
		
		var params = {	'template_content'	: currentContent.encodeParam(),
						'template_set'		: currentSet,
						'template_group'	: currentGroup.encodeParam(),
						'template_data'		: variableData
					};
		
		acp.template_editor.setLoadingMsg('Saving variables...');

		new Ajax.Request( url.replace( /&amp;/g, '&' ),
						  {
							method: 'POST',
							parameters: params,
							evalJSON: 'force',
							onSuccess: function (t)
							{

								if( Object.isUndefined( t.responseJSON ) )
								{
									alert( "The server returned an error: " + t.responseText );
									return;
								}
								else if( t.responseJSON['errors'] )
								{
									acp.template_editor.displayError( t.responseJSON['errors'] );
									return;
								}
								
								//---------------------------------
								Debug.write("Current file ID: " + fileid);
								Debug.write("File ID returned: " + t.responseJSON['templateData']['template_id']);
								
								if( acp.template_editor.checkMap( fileid, 'template' ) != t.responseJSON['templateData']['template_id'] )
								{
									acp.template_editor.modifyMap['template'][ fileid ] = t.responseJSON['templateData']['template_id'];
									Debug.write("Updated modify map!");
								}
								//---------------------------------
								
								// Update array
								acp.template_editor.currentlyOpen.set( 'template_' + fileid, t.responseJSON['templateData'] );
								
								// Reflect edited status
								editAreaLoader.setFileEditedMode( 'editor_main', 'template_bit_' + fileid, false );
								acp.template_editor.loadGroupBits(e, t.responseJSON['templateData']['template_group'], true);
								
								acp.template_editor.popups.get('variables_' + fileid).hide();
								
							},
							onException: function( f,e ){ alert( "Exception: " + e ) },
							onFailure: function( t ){ alert( "Failure: " + t.responseText ) },
							onComplete: function(t){ acp.template_editor.setLoadingMsg(''); }
						});
		
	},
	
	/* ------------------------------ */
	/**
	 * Saves the currently open file
	 * 
	 * @param	{event}		e		The event
	*/
	saveCurrentFile: function(e)
	{
		var current = editAreaLoader.getCurrentFile('editor_main');
		
		if( Object.isUndefined( current ) || Object.isUndefined( current['id'] ) )
		{
			return false;
		}
		
		// So are we dealing with CSS or template?
		if( current['id'].startsWith('template_') )
		{
			var type	= 'template_';
			var fileid 	= current['id'].replace('template_bit_', '');
			var url		= ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=templates&amp;do=saveTemplateBit&amp;setID=" +
			 			  acp.template_editor.currentSetData['set_id'] + '&template_id=' + acp.template_editor.checkMap( fileid, 'template' ) + '&secure_key=' + ipb.vars['md5_hash'];
			var currentGroup = acp.template_editor.currentlyOpen.get( 'template_' + fileid )['template_group'];
			var params 	= { 	'template_content' : editAreaLoader.getValue('editor_main').encodeParam(),
					       		'template_set'     : acp.template_editor.currentSetData['set_id'],
					       		'template_group'   : currentGroup,
								'template_data'	   : acp.template_editor.currentlyOpen.get( 'template_' + fileid )['template_data']
					 	};
					
			//Debug.dir( acp.template_editor.currentlyOpen.get( 'template_' + fileid ) );
					       //'type'             : IPB3Templates.currentTemplateBit['_type'] };
		}
		else
		{
			var type	= 'css_';
			var fileid 	= current['id'].replace('css_file_', '');
			var _fileid = acp.template_editor.checkMap( fileid, 'css' );
			var url 	= ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=css&amp;do=saveCSS&amp;setID=" +
			 			  acp.template_editor.currentSetData['set_id'] + '&css_id=' + acp.template_editor.checkMap( fileid, 'css' ) + '&secure_key=' + ipb.vars['md5_hash'];
			/* Set up params */
			var params 	= { 	'css_content'    : editAreaLoader.getValue('editor_main').encodeParam(),
					       		'css_set_id'     : acp.template_editor.currentSetData['set_id'],
								'_css_group'     : current['title'].replace( '.css', '' ).encodeParam(),
					       		'type'         : ( fileid == 0 ) ? 'add' : 'edit'
					 	};
			/* add on other params if we're editing */
			if ( params['type'] == 'edit' )
			{
				params['css_app']	   	 = acp.template_editor.currentlyOpen.get('css_' + fileid)['css_app'];
				params['css_app_hide'] 	 = acp.template_editor.currentlyOpen.get('css_' + fileid)['css_app_hide'];
				params['css_attributes'] = acp.template_editor.currentlyOpen.get('css_' + fileid)['css_attributes'];
				params['css_modules']	 = acp.template_editor.currentlyOpen.get('css_' + fileid)['css_modules'];
				params['css_position']	 = acp.template_editor.currentlyOpen.get('css_' + fileid)['css_position'];
			}
		}
				
		// Set status
		acp.template_editor.setLoadingMsg('Saving...');
				
		new Ajax.Request( url.replace( /&amp;/g, '&' ),
						  {
							method: 'POST',
							parameters: params,
							evalJSON: 'force',
							onSuccess: function (t, e)
							{
								if( Object.isUndefined( t.responseJSON ) )
								{
									alert( "The server returned an error: " + t.responseText );
									return;
								}
								else if( t.responseJSON['error'] )
								{
									acp.template_editor.displayError( t.responseJSON['error'] );
									return;
								}
								
								// Update array
								if( type == 'template_' ){
									Debug.write("Current file ID: " + fileid);
									Debug.write("File ID returned: " + t.responseJSON['templateData']['template_id']);
									
									if( acp.template_editor.checkMap( fileid, 'template' ) != t.responseJSON['templateData']['template_id'] )
									{
										acp.template_editor.modifyMap['template'][ fileid ] = t.responseJSON['templateData']['template_id'];
										Debug.write("Updated modify map!");
									}
									
									acp.template_editor.currentlyOpen.set( 'template_' + fileid, t.responseJSON['templateData'] );
									editAreaLoader.setFileEditedMode( 'editor_main', 'template_bit_' + fileid, false );
									/* Rebuild Template groups */
									acp.template_editor.loadGroupBits(e, currentGroup, true);
								} else {
									Debug.write("Current css ID: " + fileid);
									Debug.write("Css ID returned: " + t.responseJSON['cssData']['css_id']);
									
									if( acp.template_editor.checkMap( fileid, 'css' )!= t.responseJSON['cssData']['css_id'] )
									{
										acp.template_editor.modifyMap['css'][ fileid ] = t.responseJSON['cssData']['css_id'];
										Debug.write("Updated modify map!");
									}
									
									acp.template_editor.currentlyOpen.set( 'css_' + fileid, t.responseJSON['cssData'] );
									editAreaLoader.setFileEditedMode( 'editor_main', 'css_file_' + fileid, false );
									/* Rebuild CSS groups */
									acp.template_editor._buildCSS( true );
								}
							},
							onException: function( f,e ){ alert( "Exception: " + e ) },
							onFailure: function( t ){ alert( "Failure: " + t.responseText ) },
							onComplete: function(t){ acp.template_editor.setLoadingMsg(''); }
						});		
	},
	
	/* ------------------------------ */
	/**
	 * Shows an error alert
	 * 
	 * @param	{string}	err		The message
	 * @SKINNOTE 	Make this a nicer popup
	*/
	displayError: function(err)
	{
		alert( err );
		return false;
	},
	
	/* ------------------------------ */
	/**
	 * Callback: when a file is closed
	 * 
	 * @param	{object}	file	File object retuend from editarea
	*/
	CALLBACK_template_closed: function( file )
	{
		if( file['id'].startsWith('template_') )
		{
			var type = 'template_';
			var fileid = file['id'].replace('template_bit_', '');
		}
		else
		{
			var type = 'css_';
			var fileid = file['id'].replace('css_file_', '');
		}
		
		// Check if this is unsaved
		if( file['edited'] )
		{
			if( type == 'template_' ){
				var close_anyway = confirm("This template bit has unsaved changes! Are you sure you want to close it?");
			} else {
				var close_anyway = confirm("This css file has unsaved changes! Are you sure you want to close it?");
			}
			
			if( !close_anyway )
			{
				return false;
			}
		}
		
		try{
			acp.template_editor.currentlyOpen.unset( type + fileid );
		} catch( err ){ 
			Debug.write("Unsetting " + fileid + " failed!");
		}
		
		if( acp.template_editor.currentlyOpen.size() == 0 )
		{
			//editAreaLoader.setValue('editor_main', '' );
			acp.template_editor.disableToolbar( );
			acp.template_editor._hideEditor();
			//editAreaLoader.delete_instance( 'editor_main ').delay(3);
		}
		
		Debug.write("Unset " + fileid + " left open=" + acp.template_editor.currentlyOpen.size() );
		
		return true;
		
	},
	
	/* ------------------------------ */
	/**
	 * Compares a template and shows results in a popup
	 * 
	 * @param	{event}		e		The event
	*/
	compareTemplate: function(e)
	{
		var current = editAreaLoader.getCurrentFile('editor_main');
		
		if( Object.isUndefined( current ) || Object.isUndefined( current['id'] ) )
		{
			return false;
		}
		
		/* Template bit */
		if( current['id'].startsWith('template_') )
		{
			var templateid = acp.template_editor.checkMap( current['id'].replace('template_bit_', ''), 'template' );
			var url = ipb.vars['base_url'] + "&app=core&module=ajax&section=compare&secure_key=" + ipb.vars['md5_hash'] + '&setId=' + acp.template_editor.currentSetData['set_id'] + '&template_id=' + templateid;
			
			var _callback = function( popup, ajax ){
								if( ajax == 'nopermission' )
								{
									alert( "You do not have permission to compare templates." );
									popup.ready = false;
									$( popup.getObj() ).remove();
								}
							}
		}
		/* CSS file */
		else
		{
			var fileid 	= acp.template_editor.checkMap( current['id'].replace('css_file_', ''), 'css' );
			var url 	= ipb.vars['base_url'] + "&app=core&module=ajax&section=compare&do=css&secure_key=" + ipb.vars['md5_hash'] + '&setId=' + acp.template_editor.currentSetData['set_id'] + '&file_id=' + fileid;

			var _callback = function( popup, ajax ){
								if( ajax == 'nopermission' )
								{
									alert( "You do not have permission to compare CSS files." );
									popup.ready = false;
									$( popup.getObj() ).remove();
								}
							}
		}
						
		popup = new ipb.Popup( 'comparison', { type: 'pane', modal: false, w: '800px', ajaxURL: url, hideAtStart: false, close: 'a[rel="close"]' }, { 'afterAjax': _callback } );
		return false;
	},
	
	/* ------------------------------ */
	/**
	 * Sets the loading message in the toolbar
	 * 
	 * @param	{string}	msg		The message to show
	*/
	setLoadingMsg: function(msg)
	{
		if( msg.blank() )
		{
			$('t_status').removeClassName('loading').update();
			return;
		}
		
		$('t_status').addClassName('loading').update(msg);
	},
	
	checkMap: function( id, type )
	{
		if( type == 'template' && !Object.isUndefined( acp.template_editor.modifyMap['template'][ id ] ) ){
			return acp.template_editor.modifyMap['template'][ id ];
		}
		else if ( type == 'css' && !Object.isUndefined( acp.template_editor.modifyMap['css'][ id ] ) ){
			return acp.template_editor.modifyMap['css'][ id ];
		}
		else
		{
			return id;
		}
	},
	
	removeFromMap: function( id, type )
	{
		if( type == 'template' && !Object.isUndefined( acp.template_editor.modifyMap['template'][ id ] ) ){
			delete( acp.template_editor.modifyMap['template'][ id ] );
		}
		else if ( type == 'css' && !Object.isUndefined( acp.template_editor.modifyMap['css'][ id ] ) ){
			delete( acp.template_editor.modifyMap['css'][ id ] );
		}
		else
		{
			return id;
		}
	},
	
	/**
	 * Pass a 'new' value and return the old value
	 *
	 */
	reverseCheckMap: function( id, type )
	{
		if( type == 'template' && !Object.isUndefined( acp.template_editor.modifyMap['template'] ) )
		{
			for( var i in acp.template_editor.modifyMap['template'] )
			{
				if ( acp.template_editor.modifyMap['template'][ i ] == id )
				{
					Debug.write( "Found reverse map for " + id + " - " + i );
					return i;
				}
			}
		}
		else if ( type == 'css' && !Object.isUndefined( acp.template_editor.modifyMap['css'] ) )
		{
			for( var i in acp.template_editor.modifyMap['css'] )
			{
				if ( acp.template_editor.modifyMap['css'][ i ] == id )
				{
					Debug.write( "Found reverse map for " + id + " - " + i );
					return i;
				}
			}
		}
		
		return id;
	},
	
	/**
	 * Hide editor $('editor')
	 */
	_hideEditor: function()
	{
		$('editor_main').value = '';
		return;
		$('hideEditor').show();
		
		$('hideEditor').setStyle( { top   : $('editor_main').cumulativeOffset.top + 'px',
									left  : $('editor_main').cumulativeOffset.left + 'px',
									width : '100%',//( $('editor_main').getWidth() - 2 ) + 'px',
									height: '100%' } );//$('editor_main').getHeight() - 2 + 'px' } );
		
	},
	
	/**
	 * Unhide editor
	 */
	_unhideEditor: function()
	{
		$('hideEditor').hide();
	}
}






