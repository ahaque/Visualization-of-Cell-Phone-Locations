/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.topic.js - Topic view code				*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/

var _topic = window.IPBoard;

_topic.prototype.topic = {
	totalChecked: 0,
	inSection: '',
	postcache: [],
	poll: [],
	pollPopups: [],
	
	init: function()
	{
		Debug.write("Initializing ips.topic.js");
		
		document.observe("dom:loaded", function(){
			if( ipb.topic.inSection == 'topicview' )
			{
				if( $('show_filters') )
				{
					$('show_filters').observe('click', ipb.topic.toggleFilters );
					$('filter_form').hide();
				}
			
				ipb.topic.preCheckPosts();
				
				// Set up delegates
				ipb.delegate.register('.multiquote', ipb.topic.toggleMultimod);
				ipb.delegate.register('.delete_post', ipb.topic.confirmSingleDelete);
				ipb.delegate.register('.edit_post', ipb.topic.ajaxEditShow);
				ipb.delegate.register('.toggle_post', ipb.topic.ajaxTogglePostApprove);
				ipb.delegate.register('input.post_mod', ipb.topic.checkPost);
				ipb.delegate.register('a[rel="bookmark"]', ipb.topic.showLinkToTopic );
			}
		});
		
		Event.observe( window, 'load', function(){
			// Resize images
			$$('.post', '.poll').each( function(elem){
				ipb.global.findImgs( $( elem ) );
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
		if( $F('tact') == 'delete' ){
			if( !confirm(ipb.lang['delete_confirm']) ){
				Event.stop(e);
			}
		}
	},
	
	/* ------------------------------ */
	/**
	 * Event handler for moderating the topic
	 * 
	 * @param	{event}		e		The event
	*/
	submitTopicModeration: function(e)
	{
		if( $F('topic_moderation') == '03' ){ // Delete code
			if( !confirm(ipb.lang['delete_confirm']) ){
				Event.stop(e);
			}
		}
	},
	
	/**
	* MATT
	* Toggle post approval thingy majigy
	*/
	ajaxTogglePostApprove: function(e, elem)
	{
		Event.stop(e);
		
		var postid = elem.id.replace( /toggle(text)?_post_/, '' );
		if( !postid ){ return; }
		
		var toApprove = ( $('post_id_' + postid).hasClassName( 'moderated' ) ) ? 1 : 0;
		
		var url = ipb.vars['base_url'] + 'app=forums&module=ajax&section=topics&do=postApproveToggle&p=' + postid + '&t=' + ipb.topic.topic_id + '&f=' + ipb.topic.forum_id + '&approve=' + toApprove;
		
		new Ajax.Request(	url,
							{
								method: 'post',
								evalJSON: 'force',
								parameters: {
									md5check: 	ipb.vars['secure_hash']
								},
								onSuccess: function(t)
								{
									if( t.responseJSON['error'] )
									{
										switch( t.responseJSON['error'] )
										{
											case 'notopic':
												alert(ipb.lang['no_permission']);
											break;
											case 'nopermission':
												alert(ipb.lang['no_permission']);
											break;
										}
									}
									else
									{
										if ( toApprove )
										{	
											$('post_id_' + postid).removeClassName( 'moderated' );
											$('toggletext_post_' + postid).update( ipb.lang['unapprove'] );
										}
										else
										{
											$('post_id_' + postid).addClassName( 'moderated' );
											$('toggletext_post_' + postid).update( ipb.lang['approve'] );
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
	ajaxEditShow: function(e, elem)
	{	
		// If user is holding ctrl or command, just submit since they
		// want to open a new tab (requested by Luke)
		if( e.ctrlKey == true || e.metaKey == true || e.keyCode == 91 )
		{
			return false;
		}
		
		Event.stop(e);
		var edit = [];
		
		edit['button'] = elem;
		if( !edit['button'] ){ return; }
		
		// Prevents loading the editor twice
		if( edit['button'].readAttribute('_editing') == '1' )
		{
			return false;
		}		
		
		edit['pid'] = edit['button'].id.replace('edit_post_', '');
		edit['tid'] = ipb.topic.topic_id;
		edit['fid'] = ipb.topic.forum_id;
		edit['post'] = $( 'post_id_' + edit['pid'] ).down('.post');
		
		// Find post content
		ipb.topic.postcache[ edit['pid'] ] = edit['post'].innerHTML;

		url = ipb.vars['base_url'] + 'app=forums&module=ajax&section=topics&do=editBoxShow&p=' + edit['pid'] +'&t=' + edit['tid'] +'&f=' + edit['fid'];
		
		if ( Prototype.Browser.IE7 )
		{
			window.location = '#entry' + edit['pid'];
		}
		else
		{
			new Effect.ScrollTo( edit['post'], { offset: -50 } );
		}
		
		// DO TEH AJAX LOL
		new Ajax.Request( 	url, 
							{
								method: 'post',
								parameters: {
									md5check: 	ipb.vars['secure_hash']
								},
								onSuccess: function(t)
								{
									if( t.responseText == 'nopermission' || t.responseText == 'NO_POST_FORUM' || t.responseText == 'NO_EDIT_PERMS' )
									{
										alert(ipb.lang['no_permission']);
										return;
									}
									if( t.responseText == 'error' )
									{
										alert(ipb.lang['action_failed']);
										return;
									}
									
									// Put it in
									edit['button'].writeAttribute('_editing', '1');
									
									edit['post'].update( t.responseText );
									
									edit['pid'] = 'e' + edit['pid'];
									
									// Init the editor
									ipb.editors[ edit['pid'] ] = new ipb.editor( edit['pid'], USE_RTE );
									
									// Set up events
									if( $('edit_save_' + edit['pid'] ) ){
										$('edit_save_' + edit['pid'] ).observe('click', ipb.topic.ajaxEditSave );
									}
									if( $('edit_switch_' + edit['pid'] ) ){
										$('edit_switch_' + edit['pid'] ).observe('click', ipb.topic.ajaxEditSwitch );
									}
									if( $('edit_cancel_' + edit['pid'] ) ){
										$('edit_cancel_' + edit['pid'] ).observe('click', ipb.topic.ajaxEditCancel );
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
		var postid = elem.id.replace('edit_switch_e', '');
		if( !postid ){ return; }		
		var url = ipb.vars['base_url'] + 'app=forums&module=post&section=post&do=edit_post&f=' + ipb.topic.forum_id + '&t=' + ipb.topic.topic_id + '&p=' + postid + '&st=' + ipb.topic.start_id + '&_from=quickedit';
		
		try {
			// Need to update for submit manually in this case
			ipb.editors[ 'e' + postid ].update_for_form_submit();
			var Post = $F( 'e' + postid + '_textarea' );
		} catch(err) {
			Debug.error( err );
			return;
		}

		form = new Element('form', { action: url, method: 'post' } );
		textarea = new Element('textarea', { name: 'Post' } );
		reason	 = new Element('input', { name: 'post_edit_reason' } );
		md5check = new Element('input', { type: 'hidden', name: 'md5check', value: ipb.vars['secure_hash'] } );
		
		// Opera needs "value", but don't replace the & or it will freak out at you
		if( Prototype.Browser.Opera ){
			textarea.value = Post;//.replace( /&/g, '&amp;' );
		} else {
			textarea.value = Post.replace( /&/g, '&amp;' );
		}
		
		reason.value = $('post_edit_reason').value;

		form.insert( md5check ).insert( textarea ).insert( reason ).hide();
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
		var postid = elem.id.replace('edit_save_e', '');
		if( !postid ){ return; }
		
		try {
			// Need to update for submit manually in this case
			ipb.editors[ 'e' + postid ].update_for_form_submit();
			var Post = $F( 'e' + postid + '_textarea' );
		} catch(err) {
			Debug.error( err );
			Debug.dir( ipb.editors );
			return;
		}

		if( Post.blank() )
		{
			alert( ipb.lang['post_empty'] );
			return;
		}
		
		var add_edit    = null;
		var edit_reason = '';
		var post_htmlstatus = '';
		
		if( $('add_edit') ){
			add_edit = $F('add_edit');
		}
		
		if( $('post_edit_reason') ){
			edit_reason = $F('post_edit_reason');
		}	
		
		if( $('post_htmlstatus') ) {
			post_htmlstatus = $F('post_htmlstatus');
		}

		var url = ipb.vars['base_url'] + 'app=forums&module=ajax&section=topics&do=editBoxSave&p=' + postid + '&t=' + ipb.topic.topic_id + '&f=' + ipb.topic.forum_id;
		
		new Ajax.Request(	url,
							{
								method: 'post',
								evalJSON: 'force',
								encoding: ipb.vars['charset'],
								parameters: {
									md5check: 			ipb.vars['secure_hash'],
									Post: 				Post.encodeParam(),
									add_edit:			add_edit,
									post_edit_reason:	edit_reason.encodeParam(),
									post_htmlstatus: 	post_htmlstatus
								},
								onSuccess: function(t)
								{
									if( t.responseJSON['error'] )
									{
										if( $('error_msg_e' + postid) )
										{
											$('error_msg_e' + postid).update( t.responseJSON['error'] );
											new Effect.BlindDown( $('error_msg_e' + postid), { duration: 0.4 } );
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
										$('edit_post_' + postid).writeAttribute('_editing', '0');
										
										$('post_id_' + postid).down('.post').update( t.responseJSON['successString'] );
										
										ipb.global.findImgs( $( 'post_id_' + postid ).down('.post') );										
										//dp.SyntaxHighlighter.HighlightAll('bbcode_code');
										prettyPrint();
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
		var postid = elem.id.replace('edit_cancel_e', '');
		if( !postid ){ return; }
		
		if( ipb.topic.postcache[ postid ] ){
			$('post_id_' + postid).down('.post').update( ipb.topic.postcache[ postid ] );
			ipb.editors[ postid ] = null;
			$('edit_post_' + postid).writeAttribute('_editing', '0');
		}
		
		return;
	},

	/* ------------------------------ */
	/**
	 * Reads the cookie and checks posts as necessary
	*/
	preCheckPosts: function()
	{
		if( !$('selectedpidsJS') ){ return; }
		
		// Get the cookie
		pids = $F('selectedpidsJS').split(',');
		
		if( pids )
		{
			pids.each( function(pid){
				if( !pid.blank() && $('checkbox_' + pid) )
				{
					$('checkbox_' + pid).checked = true;
					ipb.topic.totalChecked++;
				}
			});
		}
		
		ipb.topic.updatePostModButton();
	},
	
	/* ------------------------------ */
	/**
	 * Checks a post
	 * 
	 * @var		{event}		e		The event
	*/
	checkPost: function(e, check)
	{
		Debug.write("Check post");
		remove = $A();
		cookie = ipb.Cookie.get('modpids');
		
		if( cookie != null ){
			pids = cookie.split(',') || $A();
		} else {
			pids = $A();
		}
		
		if( check.checked == true )
		{
			pids.push( check.id.replace('checkbox_', '') );
			ipb.topic.totalChecked++;
		}
		else
		{
			remove.push( check.id.replace('checkbox_', '') );
			ipb.topic.totalChecked--;
		}
		
		pids = pids.uniq().without( remove ).join(',');
		ipb.Cookie.set('modpids', pids, 0);
		ipb.topic.updatePostModButton();
	},
	
	/* ------------------------------ */
	/**
	 * Updates the text on the moderation submit button
	*/
	updatePostModButton: function()
	{
		if( $('mod_submit') )
		{
			if( ipb.topic.totalChecked == 0 ){
				$('mod_submit').disabled = true;
			} else {
				$('mod_submit').disabled = false;
			}
			
			$('mod_submit').value = ipb.lang['with_selected'].replace('{num}', ipb.topic.totalChecked);
		}
	},
	
	/* ------------------------------ */
	/**
	 * Shows a prompt allowing user to copy the URL
	 * 
	 * @var		{event}		e		The event
	*/
	showLinkToTopic: function(e, elem)
	{	
		_t = prompt( ipb.lang['copy_topic_link'], $( elem ).readAttribute('href') );
		Event.stop(e);
	},
	
	/* ------------------------------ */
	/**
	 * Confirm they want to delete stuff
	 * 
	 * @var 	{event}		e	The event
	*/
	confirmSingleDelete: function(e, elem)
	{
		if( !confirm( ipb.lang['delete_post_confirm'] ) )
		{
			Event.stop(e);
		}
	},
	
	/* ------------------------------ */
	/**
	 * Toggles the multimod buttons in posts
	 * 
	 * @param	{event}		e		The event
	 * @param	{element}	elem	The element that fired
	*/
	toggleMultimod: function(e, elem)
	{
		Event.stop(e);
		
		// Get list of already quoted posts
		try {
			quoted = ipb.Cookie.get('mqtids').split(',').compact();
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
		ipb.Cookie.set('mqtids', quoted, 0);			
	},
	
	/* ------------------------------ */
	/**
	 * Toggles the filters bar
	 * 
	 * @var		{event}		e	The event
	*/
	toggleFilters: function(e)
	{
		if( $('filter_form') )
		{
			Effect.toggle( $('filter_form'), 'blind', {duration: 0.2} );
			Effect.toggle( $('show_filters'), 'blind', {duration: 0.2} );
		}
	},
	
	/* ------------------------------ */
	/**
	 * Sets the supplied post to hidden
	 * 
	 * @var		{int}	id		The ID of the post to hide
	*/
	setPostHidden: function(id)
	{
		if( $( 'post_id_' + id ).select('.post_wrap')[0] )
		{
			$( 'post_id_' + id ).select('.post_wrap')[0].hide();

			if( $('unhide_post_' + id ) )
			{
				$('unhide_post_' + id).observe('click', ipb.topic.showHiddenPost );
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
		
		if( $('post_id_' + id ).select('.post_wrap')[0] )
		{
			elem = $('post_id_' + id ).select('.post_wrap')[0];
			new Effect.Parallel( [
				new Effect.BlindDown( elem ),
				new Effect.Appear( elem )
			], { duration: 0.5 } );
		}
		
		if( $('post_id_' + id ).select('.post_ignore')[0] )
		{
			elem = $('post_id_' + id ).select('.post_ignore')[0];
			/*new Effect.BlindUp( elem, {duration: 0.2} );*/
			elem.hide();
		}
		
		Event.stop(e);
	},
	
	/* ------------------------------ */
	/**
	 * Scrolls the browser to a particular post
	 * 
	 * @var 	{int}	pid		Post ID to scroll to
	*/
	scrollToPost: function( pid )
	{
		if( !pid || !Object.isNumber( pid ) ){ return; }
		$('entry' + pid).scrollTo();
	},
	
	showVoters: function( e, qid, cid )
	{
		Event.stop(e);
		
		if( !ipb.topic.poll[ qid ] || !ipb.topic.poll[ qid ][ cid ] ){ return; }
		
		/*if( ipb.topic.pollPopups[ qid+'_'+cid ] )
		{
			ipb.topic.pollPopups[ qid+'_'+cid ].show();
		}
		else
		{*/
			var content = ipb.templates['poll_voters'].evaluate( { title: ipb.topic.poll[ qid ][ cid ]['name'], content: ipb.topic.poll[ qid ][ cid ]['users'] } );
		
			ipb.topic.pollPopups[ qid+'_'+cid ] = new ipb.Popup( 'b_voters_' + qid + '_' + cid, {
															type: 'balloon',
															initial: content,
															stem: true,
															hideAtStart: false,
															attach: { target: $('l_voters_' + qid + '_' + cid ), position: 'auto', 'event': 'click' },
															w: '500px'
														});
		//}
	}
	
}
ipb.topic.init();