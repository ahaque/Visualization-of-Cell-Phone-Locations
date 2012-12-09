/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.topic.js - Topic view code				*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/

var _comments = window.IPBoard;

_comments.prototype.idmcomments = {
	totalChecked: 0,
	postcache: [],
	
	init: function()
	{
		Debug.write("Initializing ips.idmcomments.js");
		
		document.observe("dom:loaded", function(){
			$$('.multiquote', '.delete_post', '.edit_post', '.toggle_post').each( function(elem){
				if( elem.hasClassName('multiquote') ){
					elem.observe('click', ipb.idmcomments.toggleMultimod);
				}
				if( elem.hasClassName('delete_post') ){
					elem.observe('click', ipb.idmcomments.confirmSingleDelete);
				}
				if( elem.hasClassName('edit_post') ){
					elem.observe('click', ipb.idmcomments.ajaxEditShow);
				}
				if( elem.hasClassName('toggle_post') ){
					elem.observe('click', ipb.idmcomments.ajaxTogglePostApprove);
				}
			});
			
			// Resize images
			$$('.post').each( function(elem){
				ipb.global.findImgs( $( elem ) );
			});
			
			// Open rel='external' in a new window
			$$('a[rel~="external"]').each( function(elem){
				elem.observe('click', ipb.global.openNewWindow );
			});
			
			// Checkboxes
			ipb.idmcomments.preCheckPosts();

			$$('input.post_mod').each( function(elem){
				elem.observe('click', ipb.idmcomments.checkPost );
			});
		
			$$('a[rel="bookmark"]').each( function(elem){
				elem.observe('click', ipb.idmcomments.showLinkToTopic );
			});
		});
	},	
	
	/* ------------------------------ */
	/**
	 * Event handler for moderating posts
	 * 
	 * @param	{event}		e		The event
	*/
	submitPostModeration: function(e)
	{
		if( $F('idmact') == 'delete' ){
			if( !confirm( ipb.lang['delete_confirm'] ) ){
				Event.stop(e);
			}
		}
	},

	/**
	* MATT
	* Toggle post approval thingy majigy
	*/
	ajaxTogglePostApprove: function(e)
	{
		Event.stop(e);
		var elem = Event.element(e);
		var postid = elem.id.replace( new RegExp( /toggle(text)?_post_/ ), '' );
		if( !postid ){ return; }
		
		var toApprove = ( $('comment_id_' + postid).hasClassName( 'moderated' ) ) ? 1 : 0;
		
		var url = ipb.vars['base_url'] + 'app=downloads&module=post&section=comments&do=appcomment&xml=1&cid=' + postid + '&approve=' + toApprove;
		
		new Ajax.Request(	url,
							{
								method: 'post',
								evalJSON: 'force',
								parameters: {
									secure_key: 	ipb.vars['secure_hash']
								},
								onSuccess: function(t)
								{
									if( t.responseJSON['error'] )
									{
										switch( t.responseJSON['error'] )
										{
											case 'nofile':
												alert( ipb.lang['idm_invalid_file'] );
											break;
											case 'nopermission':
												alert( ipb.lang['no_permission'] );
											break;
										}
									}
									else
									{
										if ( toApprove )
										{	
											$('comment_id_' + postid).removeClassName( 'moderated' );
											$('toggletext_post_' + postid).update( "Unapprove" );
										}
										else
										{
											$('comment_id_' + postid).addClassName( 'moderated' );
											$('toggletext_post_' + postid).update( "Approve" );
										}
									}
								}
							}
						);
	},
	/* END MATT */
	
	/* ------------------------------ */
	/**
	 * Shows the quick ajax edit box
	 * 
	 * @var		{event}		e		The event
	*/
	ajaxEditShow: function(e)
	{	
		// If user is holding ctrl or command, just submit since they
		// want to open a new tab (requested by Luke)
		if( e.ctrlKey == true || e.metaKey == true || e.keyCode == 91 )
		{
			return false;
		}
		
		Event.stop(e);
		var edit = [];
		
		edit['button'] = ( !Event.element(e).hasClassName('post_edit') ) ? Event.element(e).up('.post_edit') : Event.element(e);
		if( !edit['button'] ){ return; }
		
		edit['cid'] = edit['button'].id.replace('edit_post_', '');
		edit['post'] = $( 'comment_id_' + edit['cid'] ).down('.post');
		
		// Find post content
		ipb.idmcomments.postcache[ edit['cid'] ] = edit['post'].innerHTML;

		url = ipb.vars['base_url'] + 'app=downloads&module=ajax&section=comments&do=commentEdit&cid=' + edit['cid'] + '&secure_key=' + ipb.vars['secure_hash'];
		
		// DO TEH AJAX LOL
		new Ajax.Request( 	url, 
							{
								method: 'post',
								onSuccess: function(t)
								{
									if( t.responseText == 'nopermission' )
									{
										alert( ipb.lang['no_permission'] );
										return;
									}
									if( t.responseText == 'error' )
									{
										alert( ipb.lang['action_failed'] );
										return;
									}
									
									// Put it in
									edit['post'].update( t.responseText );
									new Effect.ScrollTo( edit['post'], { offset: -50 } );
									
									// Init the editor SKINNOTE: this needs to respect user preference
									ipb.editors[ edit['cid'] ] = new ipb.editor( edit['cid'], USE_RTE );
									
									// Set up events
									if( $('edit_save_' + edit['cid'] ) ){
										$('edit_save_' + edit['cid'] ).observe('click', ipb.idmcomments.ajaxEditSave );
									}
									if( $('edit_switch_' + edit['cid'] ) ){
										$('edit_switch_' + edit['cid'] ).observe('click', ipb.idmcomments.ajaxEditSwitch );
									}
									if( $('edit_cancel_' + edit['cid'] ) ){
										$('edit_cancel_' + edit['cid'] ).observe('click', ipb.idmcomments.ajaxEditCancel );
									}
								}
							}
						);
								
		Debug.write( url );
	},
	
	/* ------------------------------ */
	/**
	 * Switches from quick edit to full editor
	*/
	ajaxEditSwitch: function(e)
	{
		// Because all posts on a topic page are wrapped in a form tag for moderation
		// purposes, to switch editor we have to perform a bit of trickery by building
		// a new form at the bottom of the page, filling it with the right values,
		// and submitting it.
		
		Event.stop(e);
		var elem = Event.element(e);
		var postid = elem.id.replace('edit_switch_', '');
		if( !postid ){ return; }		
		var url = ipb.vars['base_url'] + 'app=downloads&module=post&section=comments&do=fulledit&cid=' + postid + '&st=' + ipb.idmcomments.start_id + '&_from=quickedit';
		
		try {
			// Need to update for submit manually in this case
			ipb.editors[ postid ].update_for_form_submit();
			var Post = $F( postid + '_textarea' );
		} catch(err) {
			Debug.error( err );
			return;
		}

		form = new Element('form', { action: url, method: 'post' } );
		textarea = new Element('textarea', { name: 'Post' } );
		md5check = new Element('input', { type: 'hidden', name: 'auth_key', value: ipb.vars['secure_hash'] } );
		textarea.innerHTML = Post.replace( /&/g, '&amp;' );
		
		form.insert( md5check ).insert( textarea ).hide();
		$$('body')[0].insert( form );
		
		form.submit();
	},
	
	/* ------------------------------ */
	/**
	 * Saves the contents of quick edit
	*/
	ajaxEditSave: function(e)
	{
		Event.stop(e);
		var elem = Event.element(e);
		var postid = elem.id.replace('edit_save_', '');
		if( !postid ){ return; }
		
		try {
			// Need to update for submit manually in this case
			ipb.editors[ postid ].update_for_form_submit();
			var Post = $F( postid + '_textarea' );
		} catch(err) {
			Debug.error( err );
			Debug.dir( ipb.editors );
			return;
		}
		
		if( Post.blank() )
		{
			alert( ipb.lang['idm_comment_empty'] );
			return;
		}
		
		var url = ipb.vars['base_url'] + 'app=downloads&module=ajax&section=comments&do=commentEditSave&cid=' + postid;
		
		new Ajax.Request(	url,
							{
								method: 'post',
								evalJSON: 'force',
								encoding: ipb.vars['charset'],
								parameters: {
									secure_key: 			ipb.vars['secure_hash'],
									Post: 				Post.encodeParam()
								},
								onSuccess: function(t)
								{
									if( t.responseJSON['error'] )
									{
										if( $('error_msg_' + postid) )
										{
											$('error_msg_' + postid).update( t.responseJSON['error'] );
											new Effect.BlindDown( $('error_msg_' + postid), { duration: 0.4 } );
										}
										else
										{
											alert( t.responseJSON['error'] );
										}
										
										return false;
									}
									else
									{
										// Update post; SKINNOTE: need to fix linked image sizes
										// SKINNOTE: also need to reapply "code" javascript
										$('comment_id_' + postid).down('.post').update( t.responseJSON['successString'] );
										
										ipb.global.findImgs( $( 'comment_id_' + postid ) );
									}
								}
							}
						);
	},
									
		
	/* ------------------------------ */
	/**
	 * Cancel the quick edit
	 * 
	 * @var		{event}		e		The event
	*/
	ajaxEditCancel: function(e)
	{
		Event.stop(e);
		var elem = Event.element(e);
		var postid = elem.id.replace('edit_cancel_', '');
		if( !postid ){ return; }
		
		if( ipb.idmcomments.postcache[ postid ] ){
			$('comment_id_' + postid).down('.post').update( ipb.idmcomments.postcache[ postid ] );
			ipb.editors[ postid ] = null;
		}
		
		return;
	},

	/* ------------------------------ */
	/**
	 * Reads the cookie and checks posts as necessary
	*/
	preCheckPosts: function()
	{
		// Get the cookie
		cookie = ipb.Cookie.get('idmmodpids');
		if( cookie == null ){ return; }
		pids = cookie.split(',');
		
		if( pids )
		{
			pids.each( function(pid){
				if( !pid.blank() && $('checkbox_' + pid) )
				{
					$('checkbox_' + pid).checked = true;
					ipb.idmcomments.totalChecked++;
				}
			});
		}
		
		ipb.idmcomments.updatePostModButton();
	},
	
	/* ------------------------------ */
	/**
	 * Checks a post
	 * 
	 * @var		{event}		e		The event
	*/
	checkPost: function(e)
	{
		check = Event.findElement(e, 'input');
		remove = $A();
		cookie = ipb.Cookie.get('idmmodpids');

		if( cookie != null ){
			pids = cookie.split(',') || $A();
		} else {
			pids = $A();
		}
		
		if( check.checked == true )
		{
			pids.push( check.id.replace('checkbox_', '') );
			ipb.idmcomments.totalChecked++;
		}
		else
		{
			remove.push( check.id.replace('checkbox_', '') );
			ipb.idmcomments.totalChecked--;
		}
		
		pids = pids.uniq().without( remove ).join(',');
		ipb.Cookie.set('idmmodpids', pids, 0);
		ipb.idmcomments.updatePostModButton();
	},
	
	/* ------------------------------ */
	/**
	 * Updates the text on the moderation submit button
	*/
	updatePostModButton: function()
	{
		if( $('mod_submit') )
		{
			if( ipb.idmcomments.totalChecked == 0 ){
				$('mod_submit').disabled = true;
			} else {
				$('mod_submit').disabled = false;
			}
			
			$('mod_submit').value = ipb.lang['with_selected'].replace('{num}', ipb.idmcomments.totalChecked);
		}
	},
	
	/* ------------------------------ */
	/**
	 * Shows a prompt allowing user to copy the URL
	 * 
	 * @var		{event}		e		The event
	*/
	showLinkToTopic: function(e)
	{
		if( $( Event.element(e) ).tagName != 'A' ){	return;	}		
		_t = prompt( ipb.lang['copy_topic_link'], $( Event.element(e) ).readAttribute('href') );
		Event.stop(e);
	},
	
	/* ------------------------------ */
	/**
	 * Confirm they want to delete stuff
	 * 
	 * @var 	{event}		e	The event
	*/
	confirmSingleDelete: function(e)
	{
		if( !confirm( ipb.lang['delete_confirm'] ) )
		{
			Event.stop(e);
		}
	},
	
	/* ------------------------------ */
	/**
	 * Toggles the multimod buttons in posts
	 * 
	 * @var		{event}		e	The event
	*/
	toggleMultimod: function(e)
	{
		Event.stop(e);
		
		elem = Event.element(e);
		if( !elem.hasClassName('multiquote') )
		{
			elem = $( Event.element(e) ).up('.multiquote');
		}
		
		// Get list of already quoted posts
		try {
			quoted = ipb.Cookie.get('idm_pids').split(',').compact();
		} catch(err){
			quoted = $A();
		}
		
		id = elem.id.replace('multiq_', '');
		
		// Hokay, are we selecting/deselecting?
		if( elem.hasClassName('selected') )
		{
			elem.removeClassName('selected');
			quoted = quoted.uniq().without( id ).join(',');
		}
		else
		{
			elem.addClassName('selected');
			quoted.push( id );
			quoted = quoted.uniq().join(',');
		}
		
		// Save cookie
		ipb.Cookie.set('idm_pids', quoted, 0);			
	},
	
	/* ------------------------------ */
	/**
	 * Sets the supplied post to hidden
	 * 
	 * @var		{int}	id		The ID of the post to hide
	*/
	setPostHidden: function(id)
	{
		if( $( 'comment_id_' + id ).select('.post_wrap')[0] )
		{
			$( 'comment_id_' + id ).select('.post_wrap')[0].hide();

			if( $('unhide_post_' + id ) )
			{
				$('unhide_post_' + id).observe('click', ipb.idmcomments.showHiddenPost );
			}
		}
	},
	
	/* ------------------------------ */
	/**
	 * Unhides the supplied post
	 * 
	 * @var		{event}		e	The link event
	*/
	showHiddenPost: function(e)
	{
		link = Event.findElement(e, 'a');
		id = link.id.replace('unhide_post_', '');
		
		if( $('comment_id_' + id ).select('.post_wrap')[0] )
		{
			elem = $('comment_id_' + id ).select('.post_wrap')[0];
			new Effect.Parallel( [
				new Effect.BlindDown( elem ),
				new Effect.Appear( elem )
			], { duration: 0.5 } );
		}
		
		if( $('comment_id_' + id ).select('.post_ignore')[0] )
		{
			elem = $('comment_id_' + id ).select('.post_ignore')[0];
			/*new Effect.BlindUp( elem, {duration: 0.2} );*/
			elem.hide();
		}
		
		Event.stop(e);
	},
	
}
ipb.idmcomments.init();