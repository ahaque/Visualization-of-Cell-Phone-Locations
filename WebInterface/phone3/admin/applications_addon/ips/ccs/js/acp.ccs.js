/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* acp.uagents.js - User agent mapping			*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier 						*/
/************************************************/

var _ccs = window.IPBACP;
_ccs.prototype.ccs = {
	
	init: function()
	{
		Debug.write("Initializing acp.ccs.js");
		
		document.observe("dom:loaded", function(){
			if( $('template-tags-link') )
			{
				$('template-tags-link').observe('click', acp.ccs.showTemplateTags );
			}
			if( $('inline-template-tags-link') )
			{
				$('inline-template-tags-link').observe('click', acp.ccs.showInlineTemplateTags );
			}
			if( $('page-tags-link') )
			{
				$('page-tags-link').observe('click', acp.ccs.showPageTags );
			}
			$$('.block-preview-link').each( function(elem){
				$(elem).observe('click', acp.ccs.showBlockPreview );
			});
			
			if( $('uploadForm') )
			{
				$('uploadForm').hide();
				$('uploadTrigger').observe('click', acp.ccs.showUploadForm );
			}
		});
	},
	
	showUploadForm: function( e )
	{
		Event.stop(e);
		popup = new ipb.Popup( 'dlestimates', { type: 'pane', modal: false, w: '500px', initial: $('uploadForm').innerHTML, hideAtStart: false, close: 'a[rel="close"]' } );
		
		return false;
	},

	showBlockPreview: function( e )
	{
		Event.stop(e);
		var elem	= Event.findElement(e, 'a' );
		var id		= elem.id.replace( '-blockPreview', '' );
		var url		= ipb.vars['base_url'] + "&app=ccs&module=ajax&section=blocks&secure_key=" + ipb.vars['md5_hash'] + '&do=preview&id=' + id;
		
		popup = new ipb.Popup( 'blockPreview', { type: 'pane', modal: false, w: '600px', h: '450px', ajaxURL: url, hideAtStart: false, close: 'a[rel="close"]' } );
		return false;
	},

	showInlineTemplateTags: function( e )
	{
		Event.stop(e);
		
		if( $('template-tags') )
		{
			$('template-tags').show();
			$('content-label').addClassName( 'withSidebar' );
		}
		else
		{
			var inlineDiv	= new Element( 'div', { 'id': 'template-tags', 'class': 'templateTags-container' } );
			
			$('content-label').addClassName( 'withSidebar' );
			$('content-label').insert( { before: inlineDiv } );
			
			new Ajax.Updater( 'template-tags', ipb.vars['base_url'] + "&app=ccs&module=ajax&section=templates&secure_key=" + ipb.vars['md5_hash'] + '&do=showtags&inline=1', { evalScripts: true } );
		}
		
		return false;
	},
	
	closeInlineHelp: function( e )
	{
		if( $('template-tags') )
		{
			$('template-tags').hide();
			$('content-label').removeClassName( 'withSidebar' );
		}
	},
	
	showTemplateTags: function( e )
	{
		Event.stop(e);

		var url = ipb.vars['base_url'] + "&app=ccs&module=ajax&section=templates&secure_key=" + ipb.vars['md5_hash'] + '&do=showtags';
		
		popup = new ipb.Popup( 'templateTags', { type: 'pane', modal: false, w: '600px', h: '450px', ajaxURL: url, hideAtStart: false, close: 'a[rel="close"]' } );
		return false;
	},
	
	showPageTags: function( e )
	{
		Event.stop(e);

		if( $('template-tags') )
		{
			$('template-tags').show();
			$('content-label').addClassName( 'withSidebar' );
		}
		else
		{
			var inlineDiv	= new Element( 'div', { 'id': 'template-tags', 'class': 'templateTags-container' } );
			
			$('content-label').addClassName( 'withSidebar' );
			$('content-label').insert( { before: inlineDiv } );
			
			new Ajax.Updater( 'template-tags', ipb.vars['base_url'] + "&app=ccs&module=ajax&section=templates&secure_key=" + ipb.vars['md5_hash'] + '&do=taghelp&inline=1', { evalScripts: true } );
		}

		return false;
	},
	
	showFullPageTags: function( e )
	{
		Event.stop(e);

		var url = ipb.vars['base_url'] + "&app=ccs&module=ajax&section=templates&secure_key=" + ipb.vars['md5_hash'] + '&do=taghelp';
		
		popup = new ipb.Popup( 'templateTags', { type: 'pane', modal: false, w: '600px', h: '450px', ajaxURL: url, hideAtStart: false, close: 'a[rel="close"]' } );
		return false;
	},
	
	initBlockCategorization: function( categoryIds )
	{
		categoryReorder = function( draggableObject, mouseObject )
		{
			var options = {
							method : 'post',
							parameters : Sortable.serialize( 'category-containers', { tag: 'div', name: 'category' } )
						};
		 
			new Ajax.Request( ipb.vars['base_url'].replace( /&amp;/g, '&' ) + "app=ccs&module=ajax&section=blocks&do=reorderCats&md5check=" + ipb.vars['md5_hash'], options );
		
			return false;
		};
			
		for( var i=0; i < categoryIds.length; i++ )
		{
			Sortable.create( categoryIds[ i ], { 
					containment: categoryIds, 
					dropOnEmpty: true, 
					revert: 'failure', 
					format: 'record_([0-9]+)', 
					handle: 'draghandle',
					onUpdate: function( draggableObject, mouseObject )
					{
						if( $('record_00' + parseInt( draggableObject.id.replace( "sortable_handle_", "" ) ) ) )
						{
							draggableObject.removeChild( $('record_00' + parseInt( draggableObject.id.replace( "sortable_handle_", "" ) ) ) );
						}
						else
						{
							if( parseInt( draggableObject.childElements().length ) == 0 )
							{
								// Create empty row
								var temp = ipb.templates['cat_empty'].evaluate( { category: parseInt( draggableObject.id.replace( "sortable_handle_", "" ) ) } );
																						
								draggableObject.innerHTML = temp;
							}
						}

						new Ajax.Request( ipb.vars['base_url'].replace( /&amp;/g, '&' ) + "app=ccs&module=ajax&section=blocks&do=reorder&category=" + draggableObject.id.replace( "sortable_handle_", "" ) + "&md5check=" + ipb.vars['md5_hash'], {
											method : 'post',
											parameters : Sortable.serialize( draggableObject.id, { tag: 'li', name: 'block' } )
										} );
					
						return false;
					}  
			} );
			
			if( categoryIds[ i ] == 0 )
			{
				return true;
			}
		}
		
		Sortable.create( 'category-containers', { tag: 'div', revert: 'failure', format: 'container_([0-9]+)', onUpdate: categoryReorder, handle: 'draghandle' } );
		
		if( $('container_0') )
		{
			$('container_0').down('.draghandle').hide();
		}
	},
	
	initTemplateCategorization: function( categoryIds )
	{
		categoryReorder = function( draggableObject, mouseObject )
		{
			var options = {
							method : 'post',
							parameters : Sortable.serialize( 'category-containers', { tag: 'div', name: 'category' } )
						};
		 
			new Ajax.Request( ipb.vars['base_url'].replace( /&amp;/g, '&' ) + "app=ccs&module=ajax&section=blocks&do=reorderCats&md5check=" + ipb.vars['md5_hash'], options );
		
			return false;
		};
			
		for( var i=0; i < categoryIds.length; i++ )
		{
			Sortable.create( categoryIds[ i ], { 
					containment: categoryIds, 
					dropOnEmpty: true, 
					revert: 'failure', 
					format: 'record_([0-9]+)', 
					handle: 'draghandle',
					onUpdate: function( draggableObject, mouseObject )
					{
						if( $('record_00' + parseInt( draggableObject.id.replace( "sortable_handle_", "" ) ) ) )
						{
							draggableObject.removeChild( $('record_00' + parseInt( draggableObject.id.replace( "sortable_handle_", "" ) ) ) );
						}
						else
						{
							if( parseInt( draggableObject.childElements().length ) == 0 )
							{
								// Create empty row
								var temp = ipb.templates['cat_empty'].evaluate( { category: parseInt( draggableObject.id.replace( "sortable_handle_", "" ) ) } );
																						
								draggableObject.innerHTML = temp;
							}
						}

						new Ajax.Request( ipb.vars['base_url'].replace( /&amp;/g, '&' ) + "app=ccs&module=ajax&section=templates&do=reorder&category=" + draggableObject.id.replace( "sortable_handle_", "" ) + "&md5check=" + ipb.vars['md5_hash'], {
											method : 'post',
											parameters : Sortable.serialize( draggableObject.id, { tag: 'li', name: 'template' } )
										} );
					
						return false;
					}  
			} );
			
			if( categoryIds[ i ] == 0 )
			{
				return true;
			}
		}
		
		Sortable.create( 'category-containers', { tag: 'div', revert: 'failure', format: 'container_([0-9]+)', onUpdate: categoryReorder, handle: 'draghandle' } );
		
		if( $('container_0') )
		{
			$('container_0').down('.draghandle').hide();
		}
	},
	
	fetchEncoding: function()
	{
		if( !$('rss_feed_url') || !$F('rss_feed_url') )
		{
			alert( ipb.lang['no_rss_feed_url'] );
		}
		else
		{
			var url = ipb.vars['base_url'].replace( /&amp;/g, '&' ) + "app=ccs&module=ajax&section=blocks&do=fetchEncoding";
			
			new Ajax.Request(	url,
								{
									method: 'post',
									evalJSON: 'force',
									parameters: {
										md5check: 	ipb.vars['secure_hash'],
										url:		$F('rss_feed_url')
									},
									onSuccess: function(t)
									{
										if( t.responseJSON['error'] )
										{
											alert(t.responseJSON['error']);
										}
										else
										{
											$('rss_charset').value = t.responseJSON['encoding'];
										}
									}
								}
							);
		}
		
		return false;
	}
}

acp.ccs.init();