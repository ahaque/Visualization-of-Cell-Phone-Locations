/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.gallery.js - Gallery javascript			*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier & Brandon Farber		*/
/************************************************/

/* Hack to get lastDescendant
	Thanks: http://proto-scripty.wikidot.com/prototype:tip-getting-last-descendant-of-an-element
*/
Element.addMethods({
    lastDescendant: function(element) {
        element = $(element).lastChild;
        while (element && element.nodeType != 1) 
            element = element.previousSibling;
        return $(element);
    }
});



var _gallery = window.IPBoard;

_gallery.prototype.gallery = {
	
	totalChecked:	0,
	inSection: '',
	
	cur_left:	0,
	cur_right:	0,
	cur_image:	0,
	
	/*------------------------------*/
	/* Constructor 					*/
	init: function()
	{
		Debug.write("Initializing ips.gallery.js");
		
		document.observe("dom:loaded", function(){
			/* Gallery meta popup */
			if( $('meta-link') )
			{
				$('meta-link').observe('click', ipb.gallery.showMeta );
			}

			if( ipb.gallery.inSection == 'image' )
			{
				ipb.gallery.preCheckComments();

				ipb.delegate.register('a[rel="bookmark"]', ipb.gallery.showLinkToComment );
				ipb.delegate.register('a[rel~=newwindow]', ipb.global.openNewWindow, { 'force': 1 } );
				ipb.delegate.register('a[rel~=popup]', ipb.gallery.openPopUp );
				ipb.delegate.register('.delete_item', ipb.gallery.confirmSingleDelete );
				ipb.delegate.register('.comment_mod', ipb.gallery.checkComment );
				ipb.delegate.register('.multiquote', ipb.gallery.toggleMultiquote);
			}
			else if( ipb.gallery.inSection == 'category' )
			{
				ipb.gallery.preCheckImages();
			
				//ipb.delegate.register('.image_mod', ipb.gallery.checkImage );
				ipb.delegate.register('.check_all', ipb.gallery.checkAllInForm );
			}
			
			if( $('album') )
			{
				$('album').hide();
			}
			
			$$('.subcatsTrigger').each( function(elem) {

				var thisid = elem.identify();
				thisid = thisid.replace( 'subCatsDDTrigger_', '' );

				if( $('subCatsDD_' + thisid ) )
				{
					$('subCatsDD_' + thisid ).hide();
					$(elem).observe('click', ipb.gallery.showSubCats );
				}
			});
		});
	},
	
	openPopUp: function(e, link)
	{		
		window.open(link.href, "image", "status=0,toolbar=0,location=0,menubar=0,directories=0,resizable=1,scrollbars=1");
		Event.stop(e);
		return false;
	},
	
	showSubCats: function(e)
	{
		Event.stop(e);
		elem	= Event.findElement( e, 'h5' );
		var thisid = elem.identify();
		thisid = thisid.replace( 'subCatsDDTrigger_', '' );

		$('subCatsDD_' + thisid ).toggle();
	},
	
	/**
	 * Photostrip code - at the top because it'll prob be updated the most :P
	 */
	photostripInit: function()
	{
		if( ipb.gallery.cur_left > 0 )
		{
			$('slide_left').show();
			$('slide_left').observe( 'mouseover', ipb.gallery.photostripMouesover );
			$('slide_left').observe( 'mouseout', ipb.gallery.photostripMouesout );
			$('slide_left').observe( 'click', ipb.gallery.photostripSlideLeft );
		}
		else
		{
			$('slide_left').hide();
		}
		
		if( ipb.gallery.cur_right > 0 )
		{
			$('slide_right').show();
			$('slide_right').observe( 'mouseover', ipb.gallery.photostripMouesover );
			$('slide_right').observe( 'mouseout', ipb.gallery.photostripMouesout );
			$('slide_right').observe( 'click', ipb.gallery.photostripSlideRight );
		}
		else
		{
			$('slide_right').hide();
		}
	},
	
	/**
	 * Photostrip code - at the top because it'll prob be updated the most :P
	 */
	resetPhotostrip: function()
	{
		var count = 1;

		$('strip').childElements().each( function(elem){
			if( count == 1 )
			{
				ipb.gallery.cur_left	= elem.id.replace( /strip_/, '' );
			}
			
			if( count == 5 )
			{
				ipb.gallery.cur_right	= elem.id.replace( /strip_/, '' );
			}
			
			count++;
		});
	},
	
	/**
	 * Photostrip slide left
	 */
	photostripSlideLeft: function(e)
	{
		new Ajax.Request( 
							ipb.vars['base_url']+'app=gallery&module=ajax&section=photostrip&do=slide_right&secure_key=' + ipb.vars['secure_hash'] + '&img='+ipb.gallery.cur_left+'&cur_img='+ipb.gallery.cur_image,
							{
								method: 'post',
								onSuccess: function(t)
								{
									/* No Permission */
									if( t.responseText == 'nopermission' )
									{
										alert( ipb.lang['no_permission'] );
									}
									else
									{
										// Get rid of first item in the list
										$('strip').lastDescendant().remove();
										
										// Add the new item
										$('strip').insert( { top: t.responseText } );

										// And then reset
										ipb.gallery.resetPhotostrip();
										ipb.gallery.photostripInit();
									}
								}
							}						
						);	
		return false;
	},
	
	/**
	 * Photostrip slide right
	 */
	photostripSlideRight: function(e)
	{
		new Ajax.Request( 
							ipb.vars['base_url']+'app=gallery&module=ajax&section=photostrip&do=slide_left&secure_key=' + ipb.vars['secure_hash'] + '&img='+ipb.gallery.cur_right+'&cur_img='+ipb.gallery.cur_image,
							{
								method: 'post',
								onSuccess: function(t)
								{
									/* No Permission */
									if( t.responseText == 'nopermission' )
									{
										alert( ipb.lang['no_permission'] );
									}
									else
									{
										// Get rid of first item in the list
										$('strip').firstDescendant().remove();
										
										// Add the new item
										$('strip').insert( { bottom: t.responseText } );

										// And then reset
										ipb.gallery.resetPhotostrip();
										ipb.gallery.photostripInit();
									}
								}
							}						
						);	
		return false;
	},
	
	/**
	 * Photostrip slider cell mouseover
	 */
	photostripMouesover: function(e)
	{
		cell = Event.findElement( e, 'div' );
		
		$(cell.id).addClassName('post2');
		$(cell.id).addClassName('clickable');
	},
	
	/**
	 * Photostrip slider cell mouseout
	 */
	photostripMouesout: function(e)
	{
		cell = Event.findElement( e, 'div' );
		
		$(cell.id).removeClassName('post2');
		$(cell.id).removeClassName('clickable');
	},
	
	/**
	 * Show the meta information popup
	 */
	showMeta: function(e)
	{
		Event.stop(e);
		popup = new ipb.Popup( 'showmeta', { type: 'pane', modal: false, w: '600px', h: '500px', initial: $('metacontent').innerHTML, hideAtStart: false, close: 'a[rel="close"]' } );
		return false;
	},
	
	/**
	 * Show the comment link
	 */
	showLinkToComment: function(e, elem)
	{	
		_t = prompt( ipb.lang['copy_topic_link'], $( elem ).readAttribute('href') );
		Event.stop(e);
	},
	
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
	
	/**
	 * Do album manager dropdown change
	 * 
	 * @var 	object	select	The select box element
	*/
	goToAlbumOp: function( select )
	{
		goto_url = select.options[select.selectedIndex].value;
		
		if( goto_url == 'null' )
		{
			return false;
		}

		if( goto_url.match( /do=del/ ) )
		{
			if( confirm( deletion_confirm_lang ) )
			{
				document.location = ipb.vars['base_url'] + goto_url;
			}
		}
		else
		{
	    	document.location = ipb.vars['base_url'] + goto_url;
	 	}
	},
	
	/**
	 * Toggles the multimod buttons in posts
	 * 
	 * @param	{event}		e		The event
	 * @param	{element}	elem	The element that fired
	*/
	toggleMultiquote: function(e, elem)
	{
		Event.stop(e);
		
		// Get list of already quoted posts
		try {
			quoted = ipb.Cookie.get('gal_pids').split(',').compact();
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
		ipb.Cookie.set('gal_pids', quoted, 0);			
	},
	
	/**
	 * Check the files we've selected
	 */
	preCheckComments: function()
	{
		if( $('selectedgcids') )
		{
			var topics = $F('selectedgcids').split(',');
		}
		
		var checkboxesOnPage	= 0;
		var checkedOnPage		= 0;

		if( topics )
		{
			topics.each( function(check){
				if( check != '' )
				{
					if( $('pid_' + check ) )
					{
						checkedOnPage++;
						$('pid_' + check ).checked = true;
					}
					
					ipb.gallery.totalChecked++;
				}
			});
		}

		$$('.comment_mod').each( function(check){
			checkboxesOnPage++;
		} );
		
		if( $('comments_all') )
		{
			if( checkedOnPage == checkboxesOnPage )
			{
				$('comments_all').checked = true;
			}
		}
		
		ipb.gallery.updateModButton();
	},
	
	/**
	 * Confirm they want to delete stuff
	 * 
	 * @var 	{event}		e	The event
	*/
	checkComment: function(e, elem)
	{
		remove = new Array();
		check = elem;
		selectedTopics = $F('selectedgcids').split(',').compact();
		
		var checkboxesOnPage	= 0;
		var checkedOnPage		= 0;
		
		if( check.checked == true )
		{
			Debug.write("Checked");
			selectedTopics.push( check.id.replace('pid_', '') );
			ipb.gallery.totalChecked++;
		}
		else
		{
			remove.push( check.id.replace('pid_', '') );
			ipb.gallery.totalChecked--;
		}
		
		$$('.comment_mod').each( function(check){
			checkboxesOnPage++;
			
			if( $(check).checked == true )
			{
				checkedOnPage++;
			}
		} );
		
		if( $('comments_all') )
		{
			if( checkedOnPage == checkboxesOnPage )
			{
				$('comments_all').checked = true;
			}
			else
			{
				$('comments_all' ).checked = false;
			}
		}
		
		selectedTopics = selectedTopics.uniq().without( remove ).join(',');		
		ipb.Cookie.set('modgcids', selectedTopics, 0);
		
		$('selectedgcids').value = selectedTopics;

		ipb.gallery.updateModButton();
	},
	
	/**
	 * Check the files we've selected
	 */
	preCheckImages: function()
	{
		var topics = [];
		
		if( $('selectedimgids' ) ){
			topics = $F('selectedimgids').split(',');
		} 
		
		var checkboxesOnPage	= 0;
		var checkedOnPage		= 0;

		if( topics )
		{
			topics.each( function(check){
				if( check != '' )
				{
					if( $('img_' + check ) )
					{
						checkedOnPage++;
						$('img_' + check ).checked = true;
					}
					
					ipb.gallery.totalChecked++;
				}
			});
		}

		$$('.image_mod').each( function(check){
			checkboxesOnPage++;
		} );
		
		if( $('imgs_all') )
		{
			if( checkedOnPage == checkboxesOnPage )
			{
				$('imgs_all').checked = true;
			}
		}
		
		ipb.gallery.updateModButton();
	},
	
	/**
	 * Update the moderation button
	 */	
	updateModButton: function( )
	{
		if( $('mod_submit') )
		{
			if( ipb.gallery.totalChecked == 0 ){
				$('mod_submit').disabled = true;
			} else {
				$('mod_submit').disabled = false;
			}
		
			$('mod_submit').value = ipb.lang['with_selected'].replace('{num}', ipb.gallery.totalChecked);
		}
	},
	
	/**
	 * Check all the files in this form
	 */			
	checkAllInForm: function(e)
	{
		selectedTopics	= $F('selectedimgids').split(',').compact();
		remove			= new Array();
		
		check	= Event.findElement(e, 'input');
		toCheck	= $F(check);
		form	= check.up('form');
		
		form.select('.image_mod').each( function(check){
			if( toCheck != null )
			{
				selectedTopics.push( check.id.replace('img_', '') );
				check.checked = true;
			}
			else
			{
				remove.push( check.id.replace('img_', '') );
				check.checked = false;
			}
		});
		
		selectedTopics = selectedTopics.uniq().without( remove ).join(',');		
		ipb.Cookie.set('modimgids', selectedTopics, 0);
	},
	
	/**
	 * Sets the supplied post to hidden
	 * 
	 * @var		{int}	id		The ID of the post to hide
	*/
	setCommentHidden: function(id)
	{
		if( $( 'comment_id_' + id ).select('.post_wrap')[0] )
		{
			$( 'comment_id_' + id ).select('.post_wrap')[0].hide();

			if( $('unhide_post_' + id ) )
			{
				$('unhide_post_' + id).observe('click', ipb.gallery.showHiddenComment );
			}
		}
	},
	
	/**
	 * Unhides the supplied post
	 * 
	 * @var		{event}		e	The link event
	*/
	showHiddenComment: function(e)
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
	}
}

ipb.gallery.init();