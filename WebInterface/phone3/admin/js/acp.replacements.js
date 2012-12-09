/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* acp.replacements.js - Replacements functions	*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/

var _temp = window.IPBACP;

_temp.prototype.replacements = {
	allReplacements: {},
	currentSetData: {},
	iconUrl:'',
	icons: {},
	add_popup: null,
	
	/* ------------------------------ */
	/**
	 * Setup
	*/
	initialize: function()
	{
		document.observe("dom:loaded", function(){
			
		});
	},
	
	addReplacement: function( e )
	{
		Event.stop(e);
		
		if( acp.replacements.add_popup != null )
		{
			acp.replacements.add_popup.show();
			return;
		}
		
		var afterInit = function( popup ){
			$( 'popup_submit' ).observe('click', acp.replacements.doAddReplacement );
		};
		
		// Make popup
		acp.replacements.add_popup = new ipb.Popup('add_replacement_popup', { type: 'pane', modal: false, hideAtStart: false, w: '600px', initial: ipb.templates['add_replacement'] }, { afterInit: afterInit } );
		
	},
	
	doAddReplacement: function(e)
	{
		var key 	= $F('popup_key');
		var content = $F('popup_content');
		var url 	= 	ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=replacements&amp;do=saveReplacement&amp;setID=" +
		 				acp.replacements.currentSetData['set_id'] + '&secure_key=' + ipb.vars['md5_hash'];
		
					/* Set up params */
		var params 	= 	{	'replacement_content'  : content.encodeParam(),
				       		'replacement_set_id'   : acp.replacements.currentSetData['set_id'],
				       		'_replacement_key'     : key.encodeParam(),
				       		'type'                 : 'add'
						};
						
		// Send
		new Ajax.Request( url.replace(/&amp;/g, '&'),
						{
							method: 'post',
							evalJSON: 'force',
							parameters: params,
							onSuccess: function(t)
							{
								if( Object.isUndefined( t.responseJSON ) )
								{
									alert("An error occurred trying to add this replacement");
									return;
								}
								
								if( t.responseJSON['error'] )
								{
									alert("There was an error adding this replacement: " + t.responseJSON['error']);
									return;
								}
								
								document.location.reload(true);
							}
						});
	},
	
	/* ------------------------------ */
	/**
	 * Registers a replacement
	 * 
	 * @param	{string}	id		ID of replacement
	*/
	register: function( id )
	{
		// find the edit button
		if( !$('r_' + id + '_edit') ){
			Debug.info("Couldn't find edit button for " + id);
			return;
		}
		
		$('r_' + id + '_edit').observe('click', acp.replacements.showEdit.bindAsEventListener( this, id ) );
		
		if( $('r_' + id + '_revert') ){
			$('r_' + id + '_revert').observe('click', acp.replacements.revertReplacement.bindAsEventListener( this, id ) );
		}
		
		if( $('r_' + id + '_delete') ){
			$('r_' + id + '_delete').observe('click', acp.replacements.deleteReplacement.bindAsEventListener( this, id ) );
		}

	},
	
	/* ------------------------------ */
	/**
	 * Deletes a replacement
	 * 
	 * @param	{event}		e		The event
	 * @param	{string}	id		The replacement key
	 * @SKINNOTE 	Merge this with revertReplacement
	*/
	deleteReplacement: function( e, id )
	{
		if( !$('r_' + id + '_delete') ){
			return;
		}
		
		if( !confirm("Are you sure you want to delete this replacement? Deleting will also remove it from any child set.") )
		{
			return;
		}
		
		var replace = acp.replacements.allReplacements.replacements[ id ];
		var realid = replace['replacement_id'];
		
		var url = ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=replacements&amp;do=revertReplacement&amp;setID=" + acp.replacements.currentSetData['set_id'] + '&replacement_id=' + realid + '&secure_key=' + ipb.vars['md5_hash'];
		
		new Ajax.Request( url.replace( /&amp;/g, '&'),
						{
							method: 'get',
							evalJSON: 'force',
							onSuccess: function(t)
							{
								if( Object.isUndefined( t.responseJSON ) )
								{
									alert("There was an error deleting this replacement");
									return;
								}
								
								if( t.responseJSON['error'] )
								{
									alert("There was an error deleting this replacement: " + t.responseJSON['error']);
									return;
								}
								
								document.location.reload(true);
							}
						});
	},
	
	/* ------------------------------ */
	/**
	 * Reverts a replacement
	 * 
	 * @param	{event}		e		The event
	 * @param	{string}	id		Replacement key
	*/
	revertReplacement: function( e, id )
	{
		if( !$('r_' + id + '_revert') ){
			return;
		}
		
		if( !confirm("Are you sure you want to revert this replacement? Reverting will remove any changes made to this replacement in this skin set.") )
		{
			return;
		}
		
		var replace = acp.replacements.allReplacements.replacements[ id ];
		var realid = replace['replacement_id'];
		
		var url = ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=replacements&amp;do=revertReplacement&amp;setID=" + acp.replacements.currentSetData['set_id'] + '&replacement_id=' + realid + '&secure_key=' + ipb.vars['md5_hash'];
		
		new Ajax.Request( url.replace( /&amp;/g, '&'),
						{
							method: 'get',
							evalJSON: 'force',
							onSuccess: function(t)
							{
								if( Object.isUndefined( t.responseJSON ) )
								{
									alert("There was an error reverting this replacement");
									return;
								}
								
								if( t.responseJSON['error'] )
								{
									alert("There was an error reverting this replacement: " + t.responseJSON['error']);
									return;
								}
								
								// Swap the image url so we can build proper content
								var newContent = t.responseJSON['replacements'][ id ].replacement_content;
								newContent = newContent.gsub(/\{style_image_url\}/, acp.replacements.realImgDir);
								
								// Update our array
								t.responseJSON['replacements'][ id ]['real_content'] = newContent;
								acp.replacements.allReplacements.replacements[ id ] = t.responseJSON['replacements'][ id ];
								
								// Update cell with new content
								$('r_' + id + '_content').update( newContent );
								
								// Hide revert button
								$('r_' + id + '_revert').hide();
								
								// Update img
								var status = 'default';
								
								if ( acp.replacements.currentSetData['_parentTree'].indexOf( t.responseJSON['replacements'][ id ]['replacement_set_id'] ) != -1 )
								{
									var status = 'inherit';
								}
								
								$('r_status_' + id ).src = acp.replacements.templateUrl + status + '.png';
							},
							onException: function( f,e ){ alert( "Exception: " + e ) },
							onFailure: function( t ){ alert( "Failure: " + t.responseText ) },
							onComplete: function(t){ }
						});
							
	},
	
	/* ------------------------------ */
	/**
	 * Shows the edit box for this replacement
	 * 
	 * @param	{event}		e		The event
	 * @param	{string}	id		ID of replacement
	*/
	showEdit: function( e, id )
	{
		if( !$('r_' + id + '_content') ){
			return false;
		}
		
		// Update the array with the current content
		acp.replacements.allReplacements.replacements[ id ]['real_content'] = $('r_' + id + '_content').innerHTML;
		Debug.dir( acp.replacements.allReplacements );
		var newContent = ipb.templates['edit_box'].evaluate( { id: id, content: acp.replacements.allReplacements.replacements[ id ]['SAFE_replacement_content'] } );
		$('r_' + id + '_content').update( newContent );
		
		// Add events
		$('r_' + id + '_save').observe('click', acp.replacements.saveReplacement.bindAsEventListener( this, id ) );
		$('r_' + id + '_cancel').observe('click', acp.replacements.cancelReplacement.bindAsEventListener( this, id ) );
	},
	
	/* ------------------------------ */
	/**
	 * Saves changes to a replacement
	 * 
	 * @param	{event}		e		The event
	 * @param	{string}	id		Replacement to save
	*/
	saveReplacement: function( e, id )
	{
		if( !$('r_' + id + '_content') || !$('r_' + id + '_textbox') ){
			return;
		}
		
		var replace = acp.replacements.allReplacements.replacements[ id ];
		var realid = replace['replacement_id'];
		
		// If its the same as it was, just cancel
		if( $F('r_' + id + '_textbox' ) == replace['replacement_content'] )
		{
			acp.replacements.cancelReplacement( e, id );
			return;
		}
		
		var url 	= 	ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=replacements&amp;do=saveReplacement&amp;setID=" +
		 				acp.replacements.currentSetData['set_id'] + '&replacement_id=' + realid + '&secure_key=' + ipb.vars['md5_hash'];
		
					/* Set up params */
		var params 	= 	{	'replacement_content'  : $F('r_' + id + '_textbox' ).encodeParam(),
				       		'replacement_set_id'   : acp.replacements.currentSetData['set_id'],
				       		'_replacement_key'     : id.encodeParam(),
				       		'type'                 : 'edit'
						};
		
		new Ajax.Request( url.replace( /&amp;/g, '&'),
						{
							method: 'post',
							parameters: params,
							evalJSON: 'force',
							onSuccess: function(t)
							{
								if( Object.isUndefined( t.responseJSON ) )
								{
									alert("There was an error saving this replacement");
									return;
								}
								else if( t.responseJSON['error'] )
								{
									alert("There was an error saving this replacement: " + t.responseJSON['error']);
									return;
								}
								
								// Swap the image url so we can build proper content
								var newContent = t.responseJSON['replacements'][ id ].replacement_content;
								newContent = newContent.gsub(/\{style_image_url\}/, acp.replacements.realImgDir);
								
								// Update our array
								t.responseJSON['replacements'][ id ]['real_content'] = newContent;
								acp.replacements.allReplacements.replacements[ id ] = t.responseJSON['replacements'][ id ];
								
								// Update cell with new content
								$('r_' + id + '_content').update( newContent );
								
								// Update img
								$('r_status_' + id ).src = acp.replacements.templateUrl + 'modified.png';
								
								// Add in revert button
								$('r_revert_wrap_' + id ).update( ipb.templates['revert_button'].evaluate( { id: id } ) );
								
								if( $('r_' + id + '_revert') ){
									$('r_' + id + '_revert').observe('click', acp.replacements.revertReplacement.bindAsEventListener( this, id ) );
								}

								if( $('r_' + id + '_delete') ){
									$('r_' + id + '_delete').observe('click', acp.replacements.deleteReplacement.bindAsEventListener( this, id ) );
								}
							},
							onException: function( f,e ){ alert( "Exception: " + e ) },
							onFailure: function( t ){ alert( "Failure: " + t.responseText ) },
							onComplete: function(t){ }
						});
							
	},
	
	/* ------------------------------ */
	/**
	 * Cancels editing of replacement
	 * 
	 * @param	{event}		e		The event
	 * @param	{string}	id		Replacement to cancel
	*/
	cancelReplacement: function( e, id )
	{
		if( !$('r_' + id + '_content') ){
			return false;
		}
		
		$('r_' + id + '_content').update( acp.replacements.allReplacements.replacements[ id ]['real_content'] );
	}
}
