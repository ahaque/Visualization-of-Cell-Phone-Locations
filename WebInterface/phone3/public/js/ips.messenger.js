/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.messenger.js - Messenger code			*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/

/* @TODO Lang Abstraction */

var _msg = window.IPBoard;

_msg.prototype.messenger = {
	folderTemplate: '',
	inviteInit: false,
	editShown: 0,
	
	/*------------------------------*/
	/* Constructor 					*/
	init: function()
	{
		Debug.write("Initializing ips.messenger.js");
		
		document.observe("dom:loaded", function(){
			ipb.messenger.initEvents();
		});
	},
	
	/* ------------------------------ */
	/**
	 * Initialize the events necessary for this module
	*/
	initEvents: function()
	{
		if( $('add_folder') )
		{
			$('add_folder').observe( 'click', ipb.messenger.addFolder );
		}
		
		if( $('edit_folders') )
		{
			$('edit_folders').observe( 'click', ipb.messenger.toggleEdit );
			
			ipb.delegate.register('.f_empty', ipb.messenger.emptyFolder );
			ipb.delegate.register('.f_delete', ipb.messenger.deleteFolder );
		}
		
		if( $('msg_checkall') )
		{
			$('msg_checkall').observe('click', ipb.messenger.checkAllMsgs );
		}
		
		ipb.delegate.register('.msg_check', ipb.messenger.checkSingleBox );
		
		/*$$('.msg_check').each( function(checkbox){
			$(checkbox).observe('click', ipb.messenger.checkSingleBox );
		} );*/
		
		if( $('folder_moderation') )
		{
			$('folder_moderation').observe('click', ipb.messenger.goMultiFile );
		}
		
		// Autocomplete stuff
		ipb.messenger.acURL = ipb.vars['base_url'] + 'app=core&module=ajax&section=findnames&do=get-member-names&secure_key=' + ipb.vars['secure_hash'] + '&name=';
		
		if( $('entered_name') )
		{
			ipb.messenger.autoComplete = new ipb.Autocomplete( $('entered_name'), { multibox: false, url: ipb.messenger.acURL, templates: { wrap: ipb.templates['autocomplete_wrap'], item: ipb.templates['autocomplete_item'] } } );
		}
		
		if( $('more_members') ){
			ipb.messenger.autoCompleteMore = new ipb.Autocomplete( $('more_members'), { multibox: true, url: ipb.messenger.acURL, templates: { wrap: ipb.templates['autocomplete_wrap'], item: ipb.templates['autocomplete_item'] } } );
		}
		
		if( $('add_participants') ){
			$('add_participants').observe('click', ipb.messenger.loadInvites);
		}
		
		// Delegate for delete post
		ipb.delegate.register('.delete_post', ipb.messenger.deleteReply);
		
		// Resize images
		$$('.post', '.poll').each( function(elem){
			ipb.global.findImgs( $( elem ) );
		});
	},
	
	/* ------------------------------ */
	/**
	 * Shows the popup for adding more participants
	*/
	loadInvites: function(e)
	{
		Event.stop(e);
		
		$('invite_more_default').hide();
		$('invite_more_dialogue').setStyle( { display: 'block' });
		
		if ( ! ipb.messenger.inviteInit )
		{
			$('invite_more_dialogue').innerHTML = $('invite_more_dialogue').innerHTML.gsub(/\[x\]/, ipb.messenger.nameText );
			$('invite_more_cancel').observe( 'click', ipb.messenger.closeInvites );
			ipb.messenger.autoCompleteInvite = new ipb.Autocomplete( $('invite_more_autocomplete'), { multibox: true, url: ipb.messenger.acURL, templates: { wrap: ipb.templates['autocomplete_wrap'], item: ipb.templates['autocomplete_item'] } } );
			
			ipb.messenger.inviteInit = true;
		}
	},
	
	/* ------------------------------ */
	/**
	 * Closes invites
	*/
	closeInvites: function(e)
	{
		Event.stop(e);
		
		$('invite_more_default').show();
		$('invite_more_dialogue').hide();
	},
	
	/* ------------------------------ */
	/**
	 * Checks all messages
	 * 
	 * @param	{event}		e		The event
	*/
	checkAllMsgs: function(e)
	{
		toCheck = $F('msg_checkall');
		
		$$('.msg_check').each( function(elem){
			if( toCheck != null )
			{
				elem.checked = true;
			}
			else
			{
				elem.checked = false;
			}
		});
	},
	
	/* ------------------------------ */
	/**
	 * Monitors single checkbox and updates "check all" box appropriately
	 * 
	 * @param	{event}		e		The event
	*/
	checkSingleBox: function(e, elem)
	{
		var totalBoxes		= 0;
		var totalChecked	= 0;
		
		$$('.msg_check').each( function(checkbox){
			totalBoxes++;
			
			if( checkbox.checked == true )
			{
				totalChecked++;
			}
		} );
		
		if( totalBoxes == totalChecked )
		{
			$('msg_checkall').checked	= true;
		}
		else
		{
			$('msg_checkall').checked	= false;
		}
	},
	
	/* ------------------------------ */
	/**
	 * Empty a folder
	 * 
	 * @param	{event}		e		The event
	 * @return	{boolean}
	*/
	emptyFolder: function(e, elem)
	{
		if( Object.isUndefined( elem ) ){ return; }
		Event.stop(e);		
		
		// Get folder id
		//elem = Event.findElement( e, 'li');
		id = elem.id.replace('f_', '');
		id = elem.id.replace('empty_', '');
		
		if( !confirm( ipb.lang['confirm_empty'] ) )
		{
			return false;
		}
		
		// OK, empty
		url = '&app=members&module=ajax&secure_key=' + ipb.vars['secure_hash'] + '&section=messenger&do=emptyFolder&memberID='+ipb.vars['member_id']+'&folderID=' + id;
		
		new Ajax.Request( 	ipb.vars['base_url'] + url,
							{
								method: 'post',
								parameters: { method: 'all' },
								evalJSON: 'force',
								onSuccess: function(t)
								{
									if( Object.isUndefined( t.responseJSON ) )
									{
										alert("An error occurred");
										return;
									}
									
									if( t.responseJSON['error'] && t.responseJSON['error'] != 'nothingToRemove')
									{
										alert( t.responseJSON['error'] );
										return;
									}
									
									// Reset count
									if( $('f_' + id).select('.total')[0] )
									{
										$('f_' + id).select('.total')[0].update('0');
										new Effect.Highlight( $('f_' + id) );
									}
									
									if( !Object.isUndefined( ipb.messenger.curFolder ) && ipb.messenger.curFolder == id )
									{
										window.refresh();
									}
								}
							}
						);
						
		return true;
	},
	
	/* ------------------------------ */
	/**
	 * Delete a folder
	 * 
	 * @param	{event}		e		The event
	 * @return	{boolean}
	*/
	deleteFolder: function(e, elem)
	{
		if( Object.isUndefined( elem ) ){ return; }
		
		//elem = Event.findElement( e, 'li' );
		id = elem.id.replace('f_', '');
		id = elem.id.replace('delete_', '');
		
		if( elem.hasClassName('protected') )
		{
			alert( ipb.lang['cant_delete_folder'] );
			return false;
		}
		
		if( !confirm( ipb.lang['confirm_delete'] ) )
		{
			return false;
		}
		
		// Remove it
		url = '&app=members&module=ajax&secure_key=' + ipb.vars['secure_hash'] + '&section=messenger&do=removeFolder&memberID='+ipb.vars['member_id']+'&folderID=' + id;
		
		new Ajax.Request(	ipb.vars['base_url'] + url,
							{
								method: 'post',
								evalJSON: 'force',
								onSuccess: function(t)
								{
									if( Object.isUndefined( t.responseJSON ) )
									{
										alert( ipb.lang['error_occured'] );
										return;
									}
									
									if( t.responseJSON['error'] )
									{
										switch( t.responseJSON['error'] )
										{
											case 'cannotDeleteUndeletable':
												alert( ipb.lang['cant_delete_folder'] );
												break;
											case 'cannotDeleteHasMessages':
												/* SKINNOTE: let this function delete messages too */
												break;
											default:
												alert( t.responseJSON['error'] )
												break;
										}
									}
									
									new Effect.BlindUp( $('f_' + id ), {duration: 0.3, afterFinish: function(elem){ 
										$('f_' + id).remove();
									} } );
									
									if( !Object.isUndefined( ipb.messenger.curFolder ) && ipb.messenger.curFolder == id )
									{
										window.refresh();
									}
								}
							}
						);
						
		return true;
		
	},
	
	/* ------------------------------ */
	/**
	 * Toggles the edit folder options on/off
	 * 
	 * @param	{event}		e		The event
	*/
	toggleEdit: function(e)
	{
		if( ipb.messenger.editShown )
		{
			$('folder_list').select('.edit_folders').each( function(elem){
				new Effect.Fade( elem, { duration: 0.4 } );
			});
			
			$('folder_list').select('.total').each( function(elem){
				new Effect.Appear( elem, { duration: 0.4 } );
			});
			
			// Check for any folder renames
			$('folder_list').select('a[rel="folder_name"]').each( function( elem ){
				var parent = $( elem ).up('.folder');
				if( !$( parent ) ){ return; }
				if( $( parent ).hasClassName('protected') ){ return; }
								
				if( $( $(parent).id + '_rename') ){
					$( $(parent).id + '_rename').remove();
				}
				
				if( $( $(parent).id + '_save' ) ){
					$( $(parent).id + '_save').remove();
				}
				
				$( elem ).show();				
			});
			
			ipb.messenger.editShown = 0;
		}
		else
		{	
			$('folder_list').select('.total').each( function(elem){
				new Effect.Fade( elem, { duration: 0.4 } );
			});
			
			$('folder_list').select('.edit_folders').each( function(elem){
				new Effect.Appear( elem, { duration: 0.4 } );
			});
			
			// Set up folder renaming
			$('folder_list').select('a[rel="folder_name"]').each( function( elem ){
				var parent = $( elem ).up('.folder');
				if( !$( parent ) ){ return; }
				if( $( parent ).hasClassName('protected') ){ return; }
				
				// Create new textbox
				var textbox = new Element('input', { type: 'text', 'class': 'input_text', value: $( elem ).innerHTML, size: '10', id: $(parent).id + '_rename' });
				var submit = new Element('input', { type: 'submit', 'class': 'input_submit add_folder', value: ipb.lang['save_folder'], id: $(parent).id + '_save'});
				
				$( elem ).insert( { before: submit } );
				$( submit ).insert( { before: textbox } );
				$( elem ).hide();
				
				$( submit ).observe( 'click', ipb.messenger.saveFolderName.bindAsEventListener( this, $( parent ).id ) );
				$( textbox ).observe( 'keypress', ipb.messenger.checkNameSubmit.bindAsEventListener( this, $( parent ).id ) );
			});
			
			ipb.messenger.editShown = 1;
		}
		
		Event.stop(e);
	},
	
	checkNameSubmit: function(e, id)
	{
		if( e.which == Event.KEY_RETURN )
		{
			ipb.messenger.saveFolderName(e, id);
		}
	},
	
	saveFolderName: function(e, id)
	{
		// find text box
		if( !$( id + '_rename') ){ return; }
		
		// Check for value
		if( $F( id + '_rename' ).blank() ){
			alert( ipb.lang['must_enter_name'] );
			return;
		}
		
		var folderID = id.replace('f_', '');
		var folderName = $F( id + '_rename' );
		
		// Send
		new Ajax.Request( ipb.vars['base_url'] + 'app=members&module=ajax&section=messenger&do=renameFolder&secure_key=' + ipb.vars['secure_hash'] + '&folderID=' + folderID + '&memberID=' + ipb.vars['member_id'],
							{
								method: 'post',
								parameters: {
									name: folderName.encodeParam()
								},
								evalJSON: 'force',
								onSuccess: function(t){
									if( Object.isUndefined( t.responseJSON ) )
									{
										alert( ipb.lang['error_occured'] );
										return;
									}
									
									if( t.responseJSON['error'] )
									{
										switch( t.responseJSON['error'] )
										{
											case 'noSuchFolder':
												alert( ipb.lang['folder_not_found'] );
											break;
											case 'cannotDeleteUndeletable':
												alert( ipb.lang['folder_protected'] );
											break;
											default:
												alert( ipb.lang['error_occured'] + ": " + t.responseJSON['error'] );
											break;
										}
										
										return;
									}
									
									// It was a success, so remove text boxes
									$( id ).select('a[rel="folder_name"]')[0].update( t.responseJSON['name'] ).show();
									
									if( $( id + '_rename' ) ){
										$( id + '_rename' ).remove();
									}
									
									if( $( id + '_save' ) ){
										$( id + '_save' ).remove();
									}
								}
							}
						);
	},
	
	/* ------------------------------ */
	/**
	 * Shows the textbox for adding a folder
	 * 
	 * @param	{event}		e		The event
	*/
	addFolder: function(e)
	{
		Debug.write("Adding folder");
		rand = Math.ceil( Math.random() * 10000 );
		
		// Create text box
		_textbox = new Element( 'input', { 'type': 'text', 'size': 16, 'class': 'input_text', 'id': 'fa_text_' + rand } );
		_li = new Element( 'li', { 'class': 'new_folder', 'id': 'fa_li_' + rand } ).hide();
		_submit = new Element( 'input', { 'type': 'submit', 'value': '+', 'class': 'input_submit add_folder', 'id': 'fa_submit_' + rand } );
		
		_li.insert( _textbox ).insert(  _submit );
		$('folders').insert( _li );
		
		// Add events
		_textbox.observe('keypress', ipb.messenger.checkForAddSubmit).setStyle('width: 70%');
		_submit.observe('click', ipb.messenger.doNewFolder);
		
		new Effect.BlindDown( _li, { duration: 0.3 } );
		
		Event.stop(e);
	},
	
	/* ------------------------------ */
	/**
	 * Listens for an enter keypress in textbox
	 * 
	 * @param	{event}		e		The event
	*/
	checkForAddSubmit: function(e)
	{
		if( e.which == Event.KEY_RETURN )
		{
			ipb.messenger.doNewFolder(e);
		}
	},
	
	/**
	* Delete PM
	*
	* @param	{event}		e		Event
	* @param	{int}		tid		The topic ID to delete
	*/
	deletePM: function ( e, tid )
	{
		Event.stop(e);
		
		//var topicID = e.id.replace( 'pm_delete_', '' );
		
		if ( confirm( ipb.lang['delete_pm_confirm'] ) !== true )
		{
			Event.stop(e);
			return false;
		}
		
		window.location = ipb.vars['base_url'] + 'app=members&module=messaging&section=view&do=deleteConversation&topicID=' + tid + '&authKey=' + ipb.vars['secure_hash'];
	},
	
	/* ------------------------------ */
	/**
	 * Deletes a reply in a PM
	 * 
	 * @param	{event}		e		The event
	 * @param	{element}	elem	The element in question
	*/
	deleteReply: function(e, elem)
	{
		if( ! confirm( ipb.lang['delete_reply_confirm'] ) )
		{
			Event.stop(e);
			return false;
		}		
	},
	
	/**
	* Go Multi File
	* Processes many messages
	*
	* @param	{event}		e	The event
	*/
	goMultiFile: function(e)
	{
		/* Confirm if we chose delete */
		var method = $('pm_multifile').options[ $('pm_multifile').selectedIndex ].value;
		
		if ( method == 'delete' )
		{
			if ( !confirm( ipb.lang['delete_pm_many_confirm'] ) )
			{
				Event.stop(e);
				return false;
			}
			
			$('msgFolderForm').action += '&method=delete';
		}
		else if ( method == 'markread' )
		{
			$('msgFolderForm').action += '&method=markread';
		}
		else if ( method == 'markunread' )
		{
			$('msgFolderForm').action += '&method=markunread';
		}
		else if ( method == 'notifyon' )
		{
			$('msgFolderForm').action += '&method=notifyon';
		}
		else if ( method == 'notifyoff' )
		{
			$('msgFolderForm').action += '&method=notifyoff';
		}
		else
		{
			var id = method.replace( 'move_', '' );
			
			$('msgFolderForm').action += '&method=move&folderID=' + id;
			$('pm_multifile').options[ $('pm_multifile').selectedIndex ].value = 'move';
		}
		
		$('msgFolderForm').submit();
	},
	
	/* ------------------------------ */
	/**
	 * Processes an add folder request
	 * 
	 * @param	{event}		e		The event
	*/
	doNewFolder: function(e)
	{
		elem = Event.findElement(e, 'li');
		// Find ID
		addID = elem.id.replace("fa_li_", '');
		
		if( $F('fa_text_' + addID).blank() )
		{
			alert( ipb.lang['invalid_folder_name'] );
			return;
		}
		
		new Ajax.Request( ipb.vars['base_url'] + '&app=members&module=ajax&secure_key=' + ipb.vars['secure_hash'] + '&section=messenger&do=addFolder&memberID='+ ipb.vars['member_id'],
			 			{ 	method: 'post',
							parameters: { 'name' : $F('fa_text_' + addID).encodeParam() },
							evalJSON: 'force',
			 				onSuccess: function (t)
							{				
								if( Object.isUndefined( t.responseJSON ) )
								{
									alert( ipb.lang['error_occured'] );
									return;
								}
								
								if( t.responseJSON['error'] )
								{
									if( t.responseJSON['error'] == 'invalidName' )
									{
										alert( ipb.lang['invalid_folder_name'] );
									}
									else if( t.responseJSON['error'] == 'tooManyFolders' )
									{
										alert( ipb.lang['reached_max_folders'] );
									}
									else( t.responseJSON['error'] )
									{
										alert( t.responseJSON['error'] );
									}
									
									return;
								}
								
								_temp = ipb.messenger.folderTemplate;
								
								_temp = _temp.gsub(/\[id\]/, t.responseJSON['newID']);
								_temp = _temp.gsub(/\[name\]/, $F('fa_text_' + addID).escapeHTML());
								_temp = _temp.gsub(/\[total\]/, 0);
								
								
								$('fa_li_' + addID).insert( { before: _temp } );
								$('f_' + t.responseJSON['newID']).hide();
								
								Effect.toggle( $('f_' + t.responseJSON['newID']), 'blind', {duration: 0.4});
								Effect.toggle( $('fa_li_' + addID ), 'blind', {duration: 0.4} );
								
								$('f_' + t.responseJSON['newID']).select('.f_empty')[0].observe('click', ipb.messenger.emptyFolder );
								$('f_' + t.responseJSON['newID']).select('.f_delete')[0].observe('click', ipb.messenger.deleteFolder );
							}
						}
					);
								
		
	}
}
ipb.messenger.init();