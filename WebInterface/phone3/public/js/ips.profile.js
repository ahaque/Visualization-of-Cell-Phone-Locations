/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.profile.js - Forum view code				*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/

var _profile = window.IPBoard;

_profile.prototype.profile = {
	activeTab: '',
	viewingProfile: 0,
	
	init: function()
	{
		Debug.write("Initializing ips.profile.js");
		
		document.observe("dom:loaded", function(){
			ipb.profile.initEvents();
		});
	},
	
	/* ------------------------------ */
	/**
	 * Initialize events for the profile page
	*/
	initEvents: function()
	{
		if( $('comment_text') && $('char_remain') ){
			$('comment_text').observe('keyup', ipb.profile.checkCommentLength);
		}
	
		$$('.tab_toggle').each( function(elem){
			$(elem).observe('click', ipb.profile.changeTabContent );
		});
	
		if( $('commentForm') ){
			$('commentForm').observe('submit', ipb.profile.saveComment );
		}
	
		if( $('friend_toggle') ){
			$('friend_toggle').observe('click', ipb.profile.toggleFriendStatus );
		}
		
		if( $('dname_history') ){
			$('dname_history').observe('click', ipb.profile.showDNameHistory );
		}
		
		if( $('update_status_form') )
		{
			$('update_status_form').hide();
			$('status_box').insert( { bottom: "<a href='#' id='update_status' title='" + ipb.lang['prof_update_tooltip'] + "'>" + ipb.lang['prof_update_button'] + "</a>" } );
			$('update_status').observe('click', ipb.profile.initStatusEdit );
		}
		
		if( $('view-all-friends') )
		{
			$('view-all-friends').observe('click', ipb.profile.retrieveFriends );
		}
		
		ipb.delegate.register('.delete_comment', ipb.profile.deleteComment );
		/*ipb.delegate.register('.bbc_spoiler_show', ipb.global.toggleSpoiler);*/
	},
	
	deleteComment: function(e, elem)
	{
		if( !confirm( ipb.lang['delete_confirm'] ) )
		{
			Event.stop(e);
		}		
	},
	
	/* ------------------------------ */
	/**
	 * Retrieve all of a member's friends
	 * 
	 * @param	{event}		e		The event
	*/
	retrieveFriends: function(e)
	{
		Event.stop(e);
		link	= Event.findElement(e, 'a');
		href	= link.href.replace( /module=profile/, 'module=ajax' );
		
		new Ajax.Request( href,
						{
							method: 'post',
							parameters: { md5check: ipb.vars['secure_hash'] },
							onSuccess: function(t)
							{
								$('friend_list').innerHTML = t.responseText;
							}
						});

		return false;
	},
	
	/* ------------------------------ */
	/**
	 * Shows the text box for updating the users status
	 * 
	 * @param	{event}		e		The event
	*/
	initStatusEdit: function(e)
	{
		Event.stop(e);
		
		if( !$('status_holder') ){ return; }
		
		var old_status = $('status_holder').innerHTML;
		$('status_holder').insert( { before: ipb.templates['edit_status'] } ).hide();
		
		$('updated_status').value = old_status.unescapeHTML();
		$('update_status').hide();
		
		$('cancel_status').observe('click', ipb.profile.cancelStatus);
		$('save_status').observe('click', ipb.profile.saveStatus);
		$('updated_status').observe('keypress', ipb.profile.watchForKeypress);
	},
	
	/* ------------------------------ */
	/**
	 * Responds to Enter and Esc keys
	*/
	watchForKeypress: function(e)
	{
		if( e.which == Event.KEY_RETURN )
		{
			ipb.profile.saveStatus( e );
		}
		
		if( e.keyCode == Event.KEY_ESC )
		{
			ipb.profile.cancelStatus( e );
		}		
	},
	
	/* ------------------------------ */
	/**
	 * Saves a users status
	 * 
	 * @param	{event}		e		The event
	*/
	saveStatus: function(e)
	{
		var new_status = $('updated_status').value
		
		new Ajax.Request( ipb.vars['base_url'] + "app=members&section=status&module=ajax&md5check=" + ipb.vars['secure_hash'],
						{
							method: 'post',
							evalJSON: 'force',
							parameters: {
								new_status: 	new_status.encodeParam()
							},
							onSuccess: function(t)
							{
								if( t.responseJSON['error'] )
								{
									alert( t.responseJSON['error'] );
									return false;
								}
								else
								{
									$('edit_status').remove();
									$('status_holder').innerHTML = t.responseJSON['new_status'];
									$('status_holder').show();
									$('update_status').show();
								}
							}
						});
	},
	
	/* ------------------------------ */
	/**
	 * Cancels a status edit and puts back the old one
	 * 
	 * @param	{event}		e		The event
	*/
	cancelStatus: function(e)
	{
		Event.stop(e);
		
		$('edit_status').remove();
		
		$('status_holder').show();
		$('update_status').show();
	},
	
	/* ------------------------------ */
	/**
	 * Shows the display name history popup
	 * 
	 * @param	{event}		e		The event
	*/
	showDNameHistory: function(e)
	{		
		var mid = ipb.profile.viewingProfile;
		
		if( parseInt(mid) == 0 )
		{
			return false;
		}
		
		Event.stop(e);
		
		var _url 		= ipb.vars['base_url'] + '&app=members&module=ajax&secure_key=' + ipb.vars['secure_hash'] + '&section=dname&id=' + mid;
		warnLogs = new ipb.Popup( 'dnamehistory', {type: 'pane', modal: true, w: '500px', h: '500px', ajaxURL: _url, hideAtStart: false, close: '.cancel' } );
	},
	
	/* ------------------------------ */
	/**
	 * Adds/Removes a friend
	 * 
	 * @param	{event}		e		The event
	*/
	toggleFriendStatus: function(e)
	{
		Event.stop(e);
		
		// Are they a friend?
		if( ipb.profile.isFriend ){
			urlBit = "remove";
		} else {
			urlBit = "add";
		}
		
		new Ajax.Request( ipb.vars['base_url'] + "app=members&section=friends&module=ajax&do=" + urlBit + "&member_id=" + ipb.profile.viewingProfile + "&md5check=" + ipb.vars['secure_hash'],
						{
							method: 'post',
							onSuccess: function(t)
							{
								switch( t.responseText )
								{
									case 'pp_friend_timeflood':
										alert(ipb.lang['cannot_readd_friend']);
										Event.stop(e);
										break;
									case "pp_friend_already":
										alert(ipb.lang['friend_already']);
										break;
									case "error":
										alert(ipb.lang['action_failed']);
										break;
									default:
									 	if ( ipb.profile.isFriend ) { 
											ipb.profile.isFriend = false;
											newShow = ipb.templates['add_friend'];
										} else {
											ipb.profile.isFriend = true;
											newShow = ipb.templates['remove_friend'];
										}
										
										$('friend_toggle').update( newShow );
									break;
								}
							}
						});
	},
										
	saveComment: function(e)
	{
		if( !$('comment_text') ){ Event.stop(e); return; }
		
		// Check for comment
		if( $F('comment_text').blank() )
		{
			alert( ipb.lang['prof_comment_empty'] );
			Event.stop(e);
			return;
		}
		
		if( $('comment_submit') )
		{
			$('comment_submit').disabled = true;
		}
		
		// Check we have a comment list; if not, we'll do a proper submit
		if( !$('comment_list') ){
			return;
		} else {
			Event.stop(e);
		}
		
		new Ajax.Request( ipb.vars['base_url'] + 'app=members&section=comments&module=ajax&do=add&member_id=' + ipb.profile.viewingProfile + '&md5check=' + ipb.vars['secure_hash'],
						{
							method: 'post',
							parameters: {
								'comment': $F('comment_text').encodeParam()
							},
							onSuccess: function(t)
							{
								if( $('comment_submit') )
								{
									$('comment_submit').disabled = false;
								}
								
								//Check for errors
								if( t.responseText == 'nopermission' )
								{
									alert( ipb.lang['prof_comment_perm'] );
									return;
								}
								else if( t.responseText == 'error-no-comment' )
								{
									alert(ipb.lang['prof_comment_empty']);
									return;
								}
								else if( t.responseText == 'error' )
								{
									alert(ipb.lang['action_failed']);
									return;
								}
								else if( t.responseText == 'pp_comment_added_mod' )
								{
									alert( ipb.lang['prof_comment_mod'] );
									return;
								}
								else
								{
									$('comment_innerwrap').update( t.responseText );
									$('commentForm').reset();
									$('comment_wrap').scrollTo();
									
									if( $('comment_list').select(':first-child')[0] )
									{
										new Effect.Highlight( $('comment_list').select(':first-child')[0], { startcolor: ipb.vars['highlight_color'], delay: 0.3 } );
									}
								}
							}
						});
	},
								
	changeTabContent: function(e)
	{
		
		Event.stop(e);
		elem = Event.findElement(e, 'li');
		if( !elem.hasClassName('tab_toggle') || !elem.id ){ return; }
		id = elem.id.replace('tab_link_', '');
		if( !id || id.blank() ){ return; }
		if( !$('tab_content') ){ return; }
		
		if( ipb.profile.activeTab == id )
		{
			return;
		}
		
		oldTab = ipb.profile.activeTab;
		ipb.profile.activeTab = id;
		
		// OK, we should have an ID. Does it exist already?
		
		if( !$('tab_' + id ) )
		{
			new Ajax.Request( ipb.vars['base_url'] + 'app=members&section=load&module=ajax&member_id=' + ipb.profile.viewingProfile + '&tab=' + id + '&md5check=' + ipb.vars['secure_hash'],
							{
								method: 'post',
								onSuccess: function(t)
								{
									if( t.responseText == 'nopermission' )
									{
										alert( ipb.lang['no_permission'] );
										return;
									}
									
									if( t.responseText != 'error' )
									{
										newdiv = new Element('div', { 'id': 'tab_' + id } ).hide();
										newdiv.update( t.responseText );
										
										$('tab_content').insert( newdiv );
										
										new Effect.Parallel( [
											new Effect.BlindUp( $('tab_' + oldTab), { sync: true } ),
											new Effect.BlindDown( $('tab_' + ipb.profile.activeTab), { sync: true } )
										], { duration: 0.4, afterFinish: function(){ 	// Re-execute JS for various things in posts
											ipb.profile.executeJavascript( $( newdiv ) ); } } );
										
										
									}
									else
									{
										alert( ipb.lang['action_failed'] );
										return;
									}
								}
							});
		}
		else
		{
			new Effect.Parallel( [
				new Effect.BlindUp( $('tab_' + oldTab), { sync: true } ),
				new Effect.BlindDown( $('tab_' + ipb.profile.activeTab), { sync: true } )
			], { duration: 0.4 } );
		}
		
		$$('.tab_toggle').each( function(otherelem){
			$(otherelem).removeClassName('active');
		});
		
		$(elem).addClassName('active');
		
	},
	
	/* ------------------------------ */
	/**
	 * Executes IPBs post handling JS for the topic/post tabs
	 * 
	 * @param	{element}	wrapper		The wrapper to look in
	*/
	executeJavascript: function( wrapper )
	{
		// Image resize
		ipb.global.findImgs( wrapper );
				
		//Code highlighting
		//dp.SyntaxHighlighter.HighlightAll('bbcode_code');
		prettyPrint();
		
	},
		
	checkCommentLength: function(e)
	{
		newTotal = parseInt( ipb.vars['max_comment_length'] ) - parseInt( $F('comment_text').length );
		
		if( newTotal < 0 )
		{
			$('comment_text').value = $F('comment_text').truncate( ipb.vars['max_comment_length'], '' );
			newTotal = 0;
		}
		
		$('char_remain').update( newTotal );
	}
}
ipb.profile.init();
