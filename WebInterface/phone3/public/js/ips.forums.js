/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.forums.js - Forum view code				*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/

var _forums = window.IPBoard;

_forums.prototype.forums = {
	totalChecked: 0,
	showMod: [],
	
	init: function()
	{
		Debug.write("Initializing ips.forums.js");
		
		document.observe("dom:loaded", function(){
			ipb.forums.initEvents();
		});
	},
	initEvents: function()
	{
		// Find all moderation tools		
		$$('ul.topic_moderation').each( function(mod){
			ipb.forums.setUpModeration( mod );
		});
		
		//$('forum_table').observe( 'mouseout', ipb.forums.hideAllModeration );
		
		if( $('show_filters') )
		{
			$('show_filters').observe('click', ipb.forums.toggleFilters );
			$('filter_form').hide();
		}
		
		/* Set up mod checkboxes */
		if( $('tmod_all') )
		{ 
			ipb.forums.preCheckTopics();
			$('tmod_all').observe( 'click', ipb.forums.checkAllTopics );
		}
		
		$$('.topic_mod').each( function(check){
			check.observe( 'click', ipb.forums.checkTopic );
		} );
	},
	
	submitModForm: function(e)
	{
		// Check for delete action
		if( $F('mod_tact') == 'delete' ){
			if( !confirm( ipb.lang['delete_confirm'] ) ){
				Event.stop(e);
			}
		}
		
		// Check for merge action
		if( $F('mod_tact') == 'merge' ){
			if( !confirm( ipb.lang['delete_confirm'] ) ){
				Event.stop(e);
			}
		}
	},
	
	setUpModeration: function( mod )
	{
		cell = mod.up('td');
		mod.hide();
		mod.identify();
		
		$( cell ).observe('mouseover', ipb.forums.showModeration);
		$( mod ).observe('mouseover', ipb.forums.moverModeration);
		$( mod ).observe('mouseout', ipb.forums.moutModeration);
		
		
		$( mod ).observe('mouseover', function(e){
			$(mod).setStyle( 'cursor: pointer;' );
			Event.stop(e);
		});
		$( mod ).observe('mouseout', function(e){
			Event.stop(e);
		});
	},
	
	showModeration: function(e)
	{
		ipb.forums.hideAllModeration();
		elem = Event.findElement(e, 'td');
		
		if( elem != document)
		{
			theUL = $( elem ).down('ul.topic_moderation');
			theUL.show().setOpacity( 0.3 );
			ipb.forums.showMod.push( theUL );
			
			if( theUL.down('.t_rename') ){ $( theUL.down('.t_rename') ).observe( 'click', ipb.forums.topicRename ) }
			if( theUL.down('.t_delete') ){ $( theUL.down('.t_delete') ).observe( 'click', ipb.forums.topicDelete ) }
		}
		Event.stop(e);
	},
	
	moverModeration: function(e)
	{
		elem = Event.element(e);
		if( !elem.hasClassName('topic_moderation') )
		{
			elem = elem.up('.topic_moderation');
		}
		elem.setStyle('cursor: pointer').setOpacity(1); // Fix the cursor
		//new Effect.Appear( $( elem ), {duration: 0.2} );
	},
	
	moutModeration: function(e)
	{
		elem = Event.element(e);
		if( !elem.hasClassName('topic_moderation') )
		{
			elem = elem.up('.topic_moderation');
		}
		
		//new Effect.Fade( $(elem), {duration: 0.2, to: 0.3} );
		elem.setOpacity( 0.3 );
	},
	
	topicDelete: function(e)
	{
		if( !confirm( ipb.lang['delete_topic_confirm'] ) )
		{
			Event.stop(e);
		}
	},
	
	topicRename: function(e)
	{
		Event.stop(e);
		
		// We need to find the topic concerned
		var link = Event.findElement(e, 'td').down('a.topic_title');
		if( $( link ).readAttribute('showingRename') == 'true' ){ return; }
		
		// Create elements
		var temp = ipb.templates['topic_rename'].evaluate( { 	inputid: link.id + '_input',
																submitid: link.id + '_submit',
																cancelid: link.id + '_cancel',
																value: link.innerHTML.unescapeHTML().replace( /'/g, "&#039;" ) } );
																
		$( link ).insert( { before: temp } );
		
		// Event handlers
		$( link.id + '_input' ).observe('keydown', ipb.forums.saveTopicRename );
		$( link.id + '_submit' ).observe('click', ipb.forums.saveTopicRename );
		$( link.id + '_cancel' ).observe('click', ipb.forums.cancelTopicRename );	
		
		$( link ).hide().writeAttribute('showingRename', 'true');
	},
	
	cancelTopicRename: function(e)
	{
		var elem = Event.element(e);
		if( !elem.hasClassName( '_cancel' ) )
		{
			elem = Event.findElement(e, '.cancel');
		}
		
		try {
			var tid = elem.up('tr').id.replace( 'trow_', '' );
		} catch(err){ Debug.write( err ); return; }
		
		var linkid = 'tid-link-' + tid;
		
		if( $(linkid + '_input') ){ 
			$( linkid + '_input' ).remove();
		}
		
		if( $( linkid + '_submit' ) ){
			$( linkid + '_submit' ).remove();
		}
		
		$( linkid + '_cancel' ).remove();
		
		$( linkid ).show().writeAttribute('showingRename', false);
		
		Event.stop(e);		
	},
	
	saveTopicRename: function(e)
	{
		elem = Event.element(e);
		if( e.type == 'keydown' )
		{
			if( e.which != Event.KEY_RETURN )
			{
				return;
			}
		}
		
		try {
			tid = elem.up('tr').id.replace( 'trow_', '' );
		} catch(err){ Debug.write( err ); return; }
		
		//$('tid-link-' + tid ).update( $F('tid-link-' + tid + '_input' ) );
		//$('tid-link-' + tid + '_input').hide().remove();
		//$('tid-link-' + tid + '_save').hide().remove();
		//$('tid-link-' + tid).show();
		
		new Ajax.Request( ipb.vars['base_url'] + "app=forums&module=ajax&section=topics&do=saveTopicTitle&md5check="+ipb.vars['secure_hash']+'&tid='+tid,
						{
							method: 'post',
							evalJSON: 'force',
							parameters: {
								'name': $F('tid-link-' + tid + '_input').replace( /&#039;/g, "'" ).encodeParam()
							},
							onSuccess: function(t)
							{
								if( Object.isUndefined( t.responseJSON ) )
								{
									alert( ipb.lang['action_failed'] );
								}
								else if ( t.responseJSON['error'] )
								{
									alert( ipb.lang['error'] + ": " + t.responseJSON['error'] );
								}
								else
								{
									$('tid-link-' + tid ).update( t.responseJSON['title'] );
									$('tid-link-' + tid ).href = t.responseJSON['url'];
								}
								
								$('tid-link-' + tid + '_input').hide().remove();
								$('tid-link-' + tid + '_submit').hide().remove();
								$('tid-link-' + tid + '_cancel').hide().remove();
								$('tid-link-' + tid).show().writeAttribute('showingRename', false);
							}
						});
	},
	
	hideAllModeration: function()
	{
		//if( !ipb.forums.showMod || ipb.forums.showMod.length == 0 ){ return; }	
		ipb.forums.showMod.invoke('hide');
	},
	
	toggleFilters: function(e)
	{
		if( $('filter_form') )
		{
			Effect.toggle( $('filter_form'), 'blind', {duration: 0.2} );
			Effect.toggle( $('show_filters'), 'blind', {duration: 0.2} );
		}
	},
	preCheckTopics: function()
	{
		topics = $F('selectedtids').split(',');
		
		if( topics )
		{
			topics.each( function(check){
				if( check != '' )
				{
					if( $('tmod_' + check ) )
					{
						$('tmod_' + check ).checked = true;
					}
					
					ipb.forums.totalChecked++;
				}
			});
		}
		
		ipb.forums.updateTopicModButton();
	},				
	checkAllTopics: function(e)
	{
		Debug.write('checkAllTopics');
		check = Event.findElement(e, 'input');
		toCheck = $F(check);
		ipb.forums.totalChecked = 0;
		toRemove = new Array();
		selectedTopics = $F('selectedtids').split(',').compact();
		
		$$('.topic_mod').each( function(check){
			if( toCheck != null )
			{
				check.checked = true;
				selectedTopics.push( check.id.replace('tmod_', '') );
				ipb.forums.totalChecked++;
			}
			else
			{
				toRemove.push( check.id.replace('tmod_', '') );
				check.checked = false;
			}
		});
		
		selectedTopics = selectedTopics.uniq();
		
		if( toRemove.length >= 1 ){
			for( i=0; i<toRemove.length; i++ ){
				selectedTopics = selectedTopics.without( parseInt( toRemove[i] ) );
			}
		}
		
		selectedTopics = selectedTopics.join(',');
		ipb.Cookie.set('modtids', selectedTopics, 0);
		
		$('selectedtids').value = selectedTopics;
		
		ipb.forums.updateTopicModButton();
	},
	checkTopic: function(e)
	{
		remove = new Array();
		check = Event.findElement( e, 'input' );
		selectedTopics = $F('selectedtids').split(',').compact();
		
		if( check.checked == true )
		{
			selectedTopics.push( check.id.replace('tmod_', '') );
			ipb.forums.totalChecked++;
		}
		else
		{
			remove.push( check.id.replace('tmod_', '') );
			ipb.forums.totalChecked--;
		}
		
		selectedTopics = selectedTopics.uniq().without( remove ).join(',');		
		ipb.Cookie.set('modtids', selectedTopics, 0);
		
		$('selectedtids').value = selectedTopics;

		ipb.forums.updateTopicModButton();		
	},
	updateTopicModButton: function( )
	{
		if( $('mod_submit') )
		{
			if( ipb.forums.totalChecked == 0 ){
				$('mod_submit').disabled = true;
			} else {
				$('mod_submit').disabled = false;
			}
		
			$('mod_submit').value = ipb.lang['with_selected'].replace('{num}', ipb.forums.totalChecked);
		}
	},
	
	retrieveAttachments: function( id )
	{
		url = ipb.vars['base_url'] + "&app=forums&module=ajax&secure_key=" + ipb.vars['secure_hash'] + '&section=attachments&tid=' + id;
		popup = new ipb.Popup( 'attachments', { type: 'pane', modal: false, w: '500px', h: '600px', ajaxURL: url, hideAtStart: false, close: 'a[rel="close"]' } );
		return false;
	},
	
	retrieveWhoPosted: function( tid )
	{
		if( parseInt(tid) == 0 )
		{
			return false;
		}
		
		url = ipb.vars['base_url'] + "&app=forums&module=ajax&secure_key=" + ipb.vars['secure_hash'] + '&section=stats&do=who&t=' + tid;
		popup = new ipb.Popup( 'whoPosted', { type: 'pane', modal: false, w: '500px', h: '400px', ajaxURL: url, hideAtStart: false, close: 'a[rel="close"]' } );
		return false;
	}
}
ipb.forums.init();
