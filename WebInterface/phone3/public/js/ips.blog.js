/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.blog.js - Blog javascript				*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/

var _blog = window.IPBoard;

_blog.prototype.blog = {
	
	cblocks: {},
	_updating: 0,
	updateLeft: false,
	updateRight: false,
	// Properties for sortable
	props:  { 	tag: 'div', 				only: 'cblock_drag',
	 			handle: 'draggable', 		containment: ['cblock_left', 'cblock_right'],
	 			constraint: '', 			dropOnEmpty: true,
	 		 	hoverclass: 'over'
	 		},
	popups: {},
	cp1: null,
	
	
	/*------------------------------*/
	/* Constructor 					*/
	init: function()
	{
		Debug.write("Initializing ips.blog.js");
		
		document.observe("dom:loaded", function(){
			if( ipb.blog.inEntry && ipb.blog.ownerID == ipb.vars['member_id'] && ipb.blog.withBlocks )
			{
				ipb.blog.setUpDrags();
				ipb.blog.setUpCloseLinks();
				
				if( $('change_header') )
				{
					$('change_header').observe('click', ipb.blog.changeHeader);
				}
				
				if( $('add_theme') )
				{
					$('add_theme').observe('click', ipb.blog.changeTheme);
				}
			}
			
			// Resize images
			$$('.entry', '.poll').each( function(elem){
				ipb.global.findImgs( $( elem ) );
			});
			
			ipb.delegate.register('a[rel="bookmark"]', ipb.blog.showLinkToEntry );
			ipb.delegate.register('.delete_entry', ipb.blog.deleteEntry);
			ipb.delegate.register('.delete_comment', ipb.blog.deleteComment );
		});
	},
	
	showLinkToEntry: function(e, elem)
	{
		_t = prompt( ipb.lang['copy_entry_link'], $( elem ).readAttribute('href') );
		Event.stop(e);
	},
	
	deleteEntry: function(e, elem)
	{
		if( !confirm( ipb.lang['delete_confirm'] ) )
		{
			Event.stop(e);
		}
	},
	
	deleteComment: function( e, elem )
	{
		if( ! confirm( ipb.lang['delete_confirm'] ) )
		{
			Event.stop(e);
		}
	},
	
	changeTheme: function(e)
	{
		Event.stop(e);
		
		if( ipb.blog.popups['themes'] )
		{
			ipb.blog.popups['themes'].show();
		}
		else
		{
			// Set up content
			var afterInit = function( popup )
			{
				/*$('color_editor').insert( { top: $('color_tmp') } );
				$('color_tmp').show();
				ipb.blog.cp1 = new Refresh.Web.ColorPicker('cp1',{startHex: 'ffcc00', startMode:'h', clientFilesPath:clientImagePath});*/
				
				$('theme_preview').observe('click', ipb.blog.previewTheme);
				$('theme_save').observe('click', ipb.blog.saveTheme);
				$('theme_color_picker').observe('click', ipb.blog.openPicker);
			}
			
			ipb.blog.popups['themes'] = new ipb.Popup('theme_editor', { type: 'pane', modal: false, hideAtStart: true, initial: ipb.templates['add_theme'] }, { afterInit: afterInit } );
			ipb.blog.popups['themes'].show();
			
			//ipb.blog.colorpickerRepos();
		}
	},
	
	openPicker: function(e)
	{
		Event.stop(e);
		window.open( ipb.vars['board_url'] + "/blog/colorpicker.html", "colorpicker", "status=0,toolbar=0,width=500,height=400,scrollbars=0");
	},
	
	saveTheme: function(e)
	{
		var url = ipb.vars['base_url'] + "app=blog&amp;module=ajax&amp;section=themes&amp;blogid=" + ipb.blog.blogID;
		var content = $F( 'themeContent' );
		
		new Ajax.Request( url.replace(/&amp;/g, '&'),
						{
							method: 'post',
							parameters: {
								content: content.encodeParam(),
								md5check: ipb.vars['secure_hash']
							},
							evalJSON: 'force',
							onSuccess: function(t)
							{
								if( !Object.isUndefined( t.responseJSON ) )
								{
									alert( ipb.lang['action_failed'] + ": " + t.responseJSON['error'] );
									return;
								}
								
								ipb.blog.popups['themes'].update( ipb.templates['theme_saved'] );
								//ipb.blog.popups['themes'].hide();
								
							}
						});
		
	},
	
	previewTheme: function(e)
	{
		for( var i=0; i < document.styleSheets.length; i++ )
		{
			if( document.styleSheets[ i ].title == 'Theme' )
			{
				document.styleSheets[ i ].disabled = true;
			}
		}

		var style = document.createElement( 'style' );
		style.type = 'text/css';

		var content = $F( 'themeContent' );

		if( ! content )
		{
			return false;
		}
		
		var h = document.getElementsByTagName("head");
		h[0].appendChild( style );
		
		Debug.write( content );
		
		try
		{
	    	style.styleSheet.cssText = content;
	  	}
	  	catch(e)
	  	{
	  		try
	  		{
	    		style.appendChild( document.createTextNode( content ) );
	    		style.innerHTML=content;
	  		}
	  		catch(e){}
	  	}

		return false;
	},
	
	/*colorpickerRepos: function()
	{
		ipb.blog.cp1.show();
		ipb.blog.cp1.updateMapVisuals();
		ipb.blog.cp1.updateSliderVisuals();
	},*/
	
	changeHeader: function(e)
	{
		Event.stop(e);
		
		if( ipb.blog.popups['header'] )
		{
			ipb.blog.popups['header'].show();
		}
		else
		{
			var html = ipb.templates['headers'];
			
			var afterInit = function( popup )
			{
				if( $('reset_header') )
				{
					$('reset_header').observe('click', function(e){
						if( !confirm( ipb.lang['blog_revert_header'] ) )
						{
							Event.stop(e);
						}
						
						window.location.href = ipb.blog.blogURL.replace(/&amp;/g, '&') + "changeHeader=0";
					});
				}
			};
			
			ipb.blog.popups['header'] = new ipb.Popup('change_header', { type: 'pane', modal: true, hideAtStart: false, w: '600px', initial: html }, { afterInit: afterInit } );
		}
	},
	
	setUpCloseLinks: function()
	{
		ipb.delegate.register('.close_link', ipb.blog.closeBlock );
		ipb.delegate.register('.configure_link', ipb.blog.configureBlock );
		ipb.delegate.register('.block_control', ipb.blog.addBlock );
		ipb.delegate.register('.delete_block', ipb.blog.deleteBlock );
	},
	
	deleteBlock: function(e, elem)
	{
		if( !confirm( ipb.lang['blog_sure_delcblock'] ) )
		{
			Event.stop(e);
		}
	},
	
	configureBlock: function(e, elem)
	{
		Event.stop(e);
		
		// Get id
		Debug.write( $(elem).id );
		var elem = $( elem ).up( '.cblock_drag' );
		var blockid = $( elem ).id.replace('cblock_', '');
		var wrapper = $( elem ).down('.cblock_inner');
		
		if( !wrapper ){ return; }
		
		// Get block
		new Ajax.Request( ipb.vars['base_url'] + "app=blog&module=ajax&section=cblocks&do=showcblockconfig&secure_key=" + ipb.vars['secure_hash'] + "&cblock_id=" + blockid + "&blogid=" + ipb.blog.blogID,
							{
								method: 'get',
								evalJSON: 'force',
								onSuccess: function(t)
								{
									if( t.responseText == 'error' )
									{
										alert( ipb.lang['action_failed'] );
										return;
									}
									else
									{
										$( elem ).replace( t.responseText );
										Sortable.create('cblock_right', ipb.blog.props );
										Sortable.create('cblock_left', ipb.blog.props );
									}
								}
							}
						);
	},
	
	addBlock: function(e, elem)
	{		
		if( $( elem ).id == 'new_cblock' )
		{
			return;
		}
		else
		{
			Event.stop(e);
			
			// Get id
			Debug.write( $(elem).id );
			var blockid = $( elem ).id.replace('enable_cblock_', '');
			
			if( $( elem ).hasClassName('enable') ){
				var req = 'doenablecblock';
			} else {
				var req = 'doaddcblock';
			}				
			
			// Get block
			new Ajax.Request( ipb.vars['base_url'] + "app=blog&module=ajax&section=cblocks&do=" + req + "&secure_key=" + ipb.vars['secure_hash'] + "&cbid=" + blockid + "&blogid=" + ipb.blog.blogID,
								{
									method: 'get',
									evalJSON: 'force',
									onSuccess: function(t)
									{
										if( Object.isUndefined( t.responseJSON ) )
										{
											alert( ipb.lang['action_failed'] );
											return;
										}
										
										if( t.responseJSON['error'] )
										{
											alert( ipb.lang['action_failed'] + ": " + t.responseJSON['error'] );
											return;
										}
										
										if( t.responseJSON['cb_html'] )
										{
											// Figure out where to put it
											if( $('cblock_right').visible() )
											{
												$('cblock_right').insert( { bottom: t.responseJSON['cb_html'] } );
												Sortable.create('cblock_right', ipb.blog.props );
												Sortable.create('cblock_left', ipb.blog.props );
											}
											else if( $('cblock_left').visible() )
											{
												$('cblock_left').insert( { bottom: t.responseJSON['cb_html'] } );
												Sortable.create('cblock_right', ipb.blog.props );
												Sortable.create('cblock_left', ipb.blog.props );
											}
											else
											{
												document.location.reload(true);
											}
											
											// Remove it from the menu
											if( $('enable_cblock_' + blockid) ){
												$('enable_cblock_' + blockid).remove();
											}
										}
									}
								} );
			
		}
	},

	closeBlock: function(e, elem)
	{
		Event.stop(e);
		
		var elem = $( elem ).up( '.cblock_drag' );
		var cblockid = $( elem ).id.replace('cblock_', '');
		
		if( Object.isUndefined( cblockid ) ){ return; }
		if( !elem ){ return; }
		
		var url = ipb.vars['base_url'] + 'app=blog&module=ajax&section=cblocks&do=doremovecblock&blogid='+ipb.blog.blogID + '&cbid=' + cblockid + "&secure_key=" + ipb.vars['secure_hash'];
		
		new Ajax.Request( url.replace(/&amp;/, '&'),
						{
							method: 'post',
							evalJSON: 'force',
							onSuccess: function(t)
							{
								if( Object.isUndefined( t.responseJSON ) || t.responseText == 'error' )
								{
									Debug.write( "Error removing block" );
								}
								else
								{
									new Effect.Parallel( [
										new Effect.BlindUp( $(elem), { sync: true } ),
										new Effect.Fade( $(elem), { sync: true } )
									], { duration: 0.5, afterFinish: function(){
										// Get the name of the item
									
										var menu_item = ipb.templates['cblock_item'].evaluate( { 'id': cblockid, 'name': t.responseJSON['name'] } );
									
										$('content_blocks_menucontent').insert( menu_item );
									
										$(elem).remove();
										ipb.blog.updatedBlocks('');
									} } );
								}
							}
						});	
	},
	
	setUpDrags: function()
	{
		Debug.write("Here");
		
		if( !$('main_column') ){
			Debug.error("No main column found, cannot create draggable blocks");
		}
		
		var height_l = null;
		var height_r = null;
		var width_c = null;
		
		if( $('cblock_left') ){
			height_l = $('cblock_left').getHeight();
			width_c = $('cblock_left').getWidth();
		}
		
		if( $('cblock_right') ){
			height_r = $('cblock_right').getHeight();
			if( width_c != null )
			{
				var n_width_c = $('cblock_right').getWidth();
				width_c = ( n_width_c > width_c ) ? n_width_c : width_c;
			}
			else
			{
				width_c = $('cblock_right').getWidth();
			}
		}
		
		// Step one: if side column doesnt exist, create it
		if( !$('cblock_left') )
		{
			var cblockleft = new Element('div', { id: 'cblock_left' } );
			cblockleft.setStyle('width: ' + width_c + 'px; height: ' + height_r + 'px;').addClassName('cblock').addClassName('temp').hide();
			$('sidebar_holder').insert( { before: cblockleft } );
			ipb.blog.updateLeft = true;
		}
		
		if( !$('cblock_right') )
		{
			var cblockright = new Element('div', { id: 'cblock_right' } );
			cblockright.setStyle('width: ' + width_c + 'px; height: ' + height_l + 'px;').addClassName('cblock').addClassName('temp').hide();
			$('sidebar_holder').insert( { after: cblockright } );
			ipb.blog.updateRight = true;
		}
		
		Sortable.create('cblock_right', ipb.blog.props );
		Sortable.create('cblock_left', ipb.blog.props );
		
		// Add observer
		Draggables.addObserver(
			{
				onStart: function( eventName, draggable, event )
				{
					$('cblock_left').show().addClassName('drop_zone');
					$('cblock_right').show().addClassName('drop_zone');
					
					if( !Prototype.Browser.IE )
					{
						$('cblock_left').setStyle('opacity: 0.3');
						$('cblock_right').setStyle('opacity: 0.3');
					}
				},
				onEnd: function( eventName, draggable, event )
				{
					$('cblock_left').removeClassName('drop_zone').setStyle('opacity: 1');
					$('cblock_right').removeClassName('drop_zone').setStyle('opacity: 1');
					
					ipb.blog._updated( draggable );
				}
			}
		);
	},
	
	_updated: function( draggable )
	{
		if( ipb.blog._updating ){ return; }
		ipb.blog._updating = true;
		
		id = 0;
		
		// Get the ID
		if( draggable.element )
		{
			id = $( draggable.element ).id.replace('cblock_', '');
		}
		
		// Update classes
		ipb.blog.updatedBlocks( id );
		
		// Update position by ajax
		ipb.blog.updatePosition( id, draggable );
	},
	
	updatePosition: function( id, draggable )
	{
		if( !$('cblock_' + id ) ){ return; }
		
		// Need to figure out which column it is in
		if( $('cblock_' + id ).descendantOf('cblock_left') ){
			var pos = 'l';
		} else {
			var pos = 'r';
		}
		
		var nextid = 0;
		
		// Which block is next to it?
		var nextelem = $('cblock_' + id).next('.cblock_drag');
		
		if( !Object.isUndefined( nextelem ) && $(nextelem).id )
		{
			nextid = $( nextelem ).id.replace('cblock_', '');
		}
		
		var url = ipb.vars['base_url'] + "app=blog&module=ajax&section=cblocks&do=savecblockpos&oldid="+id+"&newid="+nextid+"&pos="+pos+"&blogid="+ipb.blog.blogID+"&secure_key="+ipb.vars['secure_hash'];
		
		// Ok, send the infos
		new Ajax.Request( 	url.replace('&amp;', '&'),
							{
								method: 'get',
								onSuccess: function(t){
									Debug.write( t.responseText );
								}
							}
						);
		
	},
	
	updatedBlocks: function( id )
	{
		var d_l = $('cblock_left').select('.cblock_drag');
		var d_r = $('cblock_right').select('.cblock_drag');
		
		//var d_l = Sortable.sequence('cblock_left');
		//var d_r = Sortable.sequence('cblock_right');
		//Debug.dir( d_l );
		
		// Check for descendants
		if( d_l.size() > 0 ){
			$('main_blog_wrapper').addClassName('with_left');
			$('cblock_left').removeClassName('temp');
		} else {
			$('main_blog_wrapper').removeClassName('with_left');
			$('cblock_left').addClassName('temp').hide();
			$('cblock_left').innerHTML += "&nbsp;"; // Force a redraw for safari
		}
		
		if( d_r.size() > 0 ){
			$('main_blog_wrapper').addClassName('with_right');
			$('cblock_right').removeClassName('temp');
		} else {
			$('main_blog_wrapper').removeClassName('with_right');
			$('cblock_right').addClassName('temp').hide();
			$('cblock_left').innerHTML += "&nbsp;"; // Force a redraw for safari
		}
		
		if( ipb.blog.updateLeft )
		{
			//$('cblock_left').setStyle('height: auto; position: static; top: auto; left: auto;');
		}
		
		ipb.blog._updating = false;
	},
	
	saveCblock: function( e, cblock, fields )
	{
		var save_fields = '';
		
		for( var i = 0; i < fields.length; i++ )
		{
			save_fields += '&' + 'cblock_config[' + fields[i] + ']' + '=' + $F( fields[i] );
		}
		
		var url = ipb.vars['base_url'] + "app=blog&module=ajax&section=cblocks&do=savecblockconfig&cblock_id=" + cblock + "&blogid=" + ipb.blog.blogID + "&secure_key=" + ipb.vars['secure_hash'];
		
		new Ajax.Request( url.replace(/&amp;/g, '&' ) + save_fields,
							{
								method: 'get',
								onSuccess: function(t)
								{
									if( t.responseText == 'error' )
									{
										alert( ipb.lang['action_failed'] );
										return;
									}
									else if( t.responseText == 'refresh' )
									{
										document.location.reload(true);
									}
									else
									{
										$( 'cblock_' + cblock_id ).replace( t.responseText );
										Sortable.create('cblock_right', ipb.blog.props );
										Sortable.create('cblock_left', ipb.blog.props );
									}
									
								}
							}
						);
	},
	
	register: function( id, position )
	{
		if( !ipb.blog.inEntry ){ return; }
		if( !$('cblock_' + id) ){ return; }
		
		//new Draggable( $('cblock_' + id), { handle: 'draggable', revert: true } );		
	}
}

ipb.blog.init();