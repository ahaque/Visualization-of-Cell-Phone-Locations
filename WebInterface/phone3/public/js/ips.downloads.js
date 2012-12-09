/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.board.js - Board index code				*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/

var _idx = window.IPBoard;

_idx.prototype.downloads = {
	totalChecked: 0,
	
	/*------------------------------*/
	/* Constructor 					*/
	init: function()
	{
		Debug.write("Initializing ips.downloads.js");
		
		document.observe("dom:loaded", function(){
			ipb.downloads.setUpToggle();
			ipb.downloads.initEvents();
		});
	},
	
	/**
	 * Init events for cat listing
	 */
	initEvents: function()
	{
		if( $('show_filters') )
		{
			$('show_filters').observe('click', ipb.downloads.toggleFilters );
			$('filter_form').hide();
		}
		
		/* Set up mod checkboxes for cats */
		if( $('files_all') )
		{
			ipb.downloads.preCheckFiles();
			$('files_all').observe( 'click', ipb.downloads.checkAllFiles );
		}
		
		$$('.topic_mod').each( function(check){
			check.observe( 'click', ipb.downloads.checkFile );
		} );
		
		/* Checkboxes in moderation panel */
		$$('.check_all').each( function(check){
			check.observe( 'click', ipb.downloads.checkAllInForm );
		} );
		
		/* Checkboxes in moderation panel */
		$$('.delete_link').each( function(check){
			check.observe( 'click', ipb.downloads.checkConfirm );
		} );
		
		$$('.topic_moderation').each( function(check){
			check.observe( 'click', ipb.downloads.checkModfile );
		} );
		
		if( $('showUploadField') )
		{
			$('showUploadField').observe('click', ipb.downloads.toggleField );
		}
		
		if( $('showEstimates') )
		{
			$('showEstimates').observe('click', ipb.downloads.showEstimates );
		}
		
		if( $('showEmailForm') )
		{
			$('showEmailForm').observe('click', ipb.downloads.showEmailForm );
		}
		
		// Open rel='external' in a new window
		//$$('a[rel~="external"]').each( function(elem){
		//	elem.observe('click', ipb.global.openNewWindow.bindAsEventListener( this, true ) );
		//});
		
		// Resize images
		$$('.download-description').each( function(elem){
			ipb.global.findImgs( $( elem ) );
		});
		
		if( typeof(hidecomments) != 'undefined' && hidecomments )
		{
			if( showcomments )
			{
				if( $('displayComments') )
				{
					$('displayComments').observe('click', function(e){ Event.stop(e); new Effect.ScrollTo( 'comments-wrapper', { offset: -50 } ); } );
				}
				
				if( $('comment' + showcomments ) )
				{
					new Effect.ScrollTo( 'comment' + showcomments, { offset: 0 } );
				}
				else
				{
					new Effect.ScrollTo( 'comments-wrapper', { offset: -50 } );
				}
			}
			else
			{
				$('comments-wrapper').hide();
				
				if( $('displayComments') )
				{
					$('displayComments').observe('click', ipb.downloads.showMeComments );
				}
			}
		}
		else if( typeof(showcomments) != 'undefined' && showcomments )
		{
			if( $('comment' + showcomments ) )
			{
				new Effect.ScrollTo( 'comment' + showcomments, { offset: 0 } );
			}
			else
			{
				new Effect.ScrollTo( 'comments-wrapper', { offset: -50 } );
			}
		}
	},
	
	showMeComments: function(e)
	{
		Event.stop(e);
		
		$('comments-wrapper').toggle();
		
		new Effect.ScrollTo( 'comments-wrapper', { offset: -50 } );
	},
	
	checkEmailForm: function(e)
	{
		if( !$('field-email').value )
		{
			alert( ipb.lang['idm_msg_email'] );
			Event.stop(e);
			return false;
		}
		
		if( !$('field-content').value )
		{
			alert( ipb.lang['idm_msg_text'] );
			Event.stop(e);
			return false;
		}
		
		return true;
	},
	
	showEstimates: function(e)
	{
		Event.stop(e);
		popup = new ipb.Popup( 'dlestimates', { type: 'pane', modal: false, w: '500px', initial: $('dlestimates').innerHTML, hideAtStart: false, close: 'a[rel="close"]' } );
		return false;
	},
	
	showEmailForm: function(e)
	{
		Event.stop(e);
		popup = new ipb.Popup( 'email_form', { type: 'pane', modal: false, w: '500px', initial: $('email_form').innerHTML, hideAtStart: false, close: 'a[rel="close"]' } );
		
		//$('email_form').innerHTML = '';

		if( $('email-form_form') )
		{
			$('email-form_form').observe('submit', ipb.downloads.checkEmailForm);
		}
		
		return false;
	},
	
	/**
	 * Toggling the upload field displaying
	 */
	toggleField: function(e)
	{
		if( $('uploadField') )
		{
			Event.stop(e);
			Effect.toggle( $('uploadField'), 'blind', {duration: 0.2} );
			Effect.toggle( $('uploadText'), 'blind', {duration: 0.2} );
		}
	},
	
	/**
	 * Confirmation for all delete links
	 */
	checkConfirm: function(e)
	{
		if( !confirm( ipb.lang['delete_confirm'] ) )
		{
			Event.stop(e);
		}
	},
	
	/**
	 * Moderator submitting the mod form
	 */
	submitModForm: function(e)
	{
		var action = $( Event.findElement(e, 'form') ).down('select');
		
		// Check for delete action
		if( $F(action) == 'del' ){
			if( !confirm( ipb.lang['delete_confirm'] ) ){
				Event.stop(e);
			}
		}
	},
	
	/* ------------------------------ */
	/**
	 * Inits the forum tables ready for collapsing
	*/
	setUpToggle: function()
	{
		$$('.ipb_table').each( function(tab){
			$( tab ).wrap( 'div', { 'class': 'table_wrap' } );
		});
		
		$$('.category_block').each( function(cat){
			if( $(cat).select('.toggle')[0] )
			{
				$(cat).select('.toggle')[0].observe( 'click', ipb.downloads.toggleCat );			
			}
		});
		
		cookie = ipb.Cookie.get('toggleIdmCats');
		
		if( cookie )
		{
			var cookies = cookie.split( ',' );
			
			//-------------------------
			// Little fun for you...
			//-------------------------
			for( var abcdefg=0; abcdefg < cookies.length; abcdefg++ )
			{
				if( cookies[ abcdefg ] )
				{
					var wrapper	= $( cookies[ abcdefg ] ).up('.category_block').down('.table_wrap');
					
					wrapper.hide();
					$( cookies[ abcdefg ] ).addClassName('collapsed');
				}
			}
		}
	},
	
	/* ------------------------------ */
	/**
	 * Show/hide a category
	 * 
	 * @var		{event}		e	The event
	*/
	toggleCat: function(e)
	{
		var click = Event.element(e);
		var remove = $A();
		var wrapper = $( click ).up('.category_block').down('.table_wrap');
		catname = $( click ).up('h3');
		var catid = catname.id;
		
		// Get cookie
		cookie = ipb.Cookie.get('toggleIdmCats');
		if( cookie == null ){
			cookie = $A();
		} else {
			cookie = cookie.split(',');
		}
		
		Effect.toggle( wrapper, 'blind', {duration: 0.4} );
		
		if( catname.hasClassName('collapsed') )
		{
			catname.removeClassName('collapsed');
			remove.push( catid );
		}
		else
		{
			new Effect.Morph( $(catname), {style: 'collapsed', duration: 0.4, afterFinish: function(){
				$( catname ).addClassName('collapsed');
			} });
			cookie.push( catid );
		}
		
		cookie = "," + cookie.uniq().without( remove ).join(',') + ",";
		ipb.Cookie.set('toggleIdmCats', cookie, 1);
		
		Event.stop( e );
	},
	
	/**
	 * Toggling the filters
	 */
	toggleFilters: function(e)
	{
		if( $('filter_form') )
		{
			Effect.toggle( $('filter_form'), 'blind', {duration: 0.2} );
			Effect.toggle( $('show_filters'), 'blind', {duration: 0.2} );
		}
	},
	
	/**
	 * Check the files we've selected
	 */
	preCheckFiles: function()
	{
		topics = $F('selectedfileids').split(',');
		
		var checkboxesOnPage	= 0;
		var checkedOnPage		= 0;

		if( topics )
		{
			topics.each( function(check){
				if( check != '' )
				{
					if( $('file_' + check ) )
					{
						checkedOnPage++;
						$('file_' + check ).checked = true;
					}
					
					ipb.downloads.totalChecked++;
				}
			});
		}

		$$('.topic_mod').each( function(check){
			checkboxesOnPage++;
		} );
		
		if( checkedOnPage == checkboxesOnPage && checkboxesOnPage > 0 )
		{
			$('files_all').checked = true;
		}
		
		ipb.downloads.updateFileModButton();
	},	
	
	/**
	 * Check all the files in this form
	 */			
	checkAllInForm: function(e)
	{
		checked	= 0;
		check	= Event.findElement(e, 'input');
		toCheck	= $F(check);
		form	= check.up('form');
		selectedTopics	= new Array;
		
		form.select('.selectedfileids').each( function(field){
			selectedTopics	= field.value.split(',').compact();
		});
		
		toRemove		= new Array();

		form.select('.topic_moderation').each( function(check){
			if( toCheck != null )
			{
				check.checked = true;
				selectedTopics.push( check.id.replace('file_', '') );
				checked++;
			}
			else
			{
				check.checked = false;
				toRemove.push( check.id.replace('file_', '') );
			}
		});
		
		selectedTopics = selectedTopics.uniq().without( toRemove ).join(',');

		form.select('.submit_button').each( function(button)
		{
			if( checked == 0 ){
				button.disabled = true;
			} else {
				button.disabled = false;
			}
		
			button.value = ipb.lang['with_selected'].replace('{num}', checked);
		});
		
		form.select('.selectedfileids').each( function(hidden)
		{
			hidden.value = selectedTopics;
		});
	},
	
	/**
	 * Check a file on the moderation form
	 */			
	checkModfile: function(e)
	{
		check	= Event.findElement(e, 'input');
		toCheck	= $(check);
		form	= check.up('form');
		
		var checkboxesOnPage	= 0;
		var checkedOnPage		= 0;
		
		form.select('.selectedfileids').each( function(field){
			selectedTopics	= field.value.split(',').compact();
		});
		remove			= new Array();

		form.select('.topic_moderation').each( function(check){
			checkboxesOnPage++;
			
			if( check.checked == true )
			{
				checkedOnPage++;
				selectedTopics.push( check.id.replace('file_', '') );
			}
			else
			{
				remove.push( check.id.replace('file_', '') );
				form.select('.check_all')[0].checked = false;
			}
		} );
		
		if( checkedOnPage == checkboxesOnPage )
		{
			form.select('.check_all')[0].checked = true;
		}

		selectedTopics = selectedTopics.uniq().without( remove ).join(',');

		form.select('.submit_button').each( function(button)
		{
			if( checkedOnPage == 0 ){
				button.disabled = true;
			} else {
				button.disabled = false;
			}
		
			button.value = ipb.lang['with_selected'].replace('{num}', checkedOnPage);
		});
		
		form.select('.selectedfileids').each( function(hidden)
		{
			hidden.value = selectedTopics;
		});
	},
	
	/**
	 * Check all the files
	 */			
	checkAllFiles: function(e)
	{
		check = Event.findElement(e, 'input');
		toCheck = $F(check);
		ipb.downloads.totalChecked = 0;
		toRemove = new Array();
		selectedTopics = $F('selectedfileids').split(',').compact();

		$$('.topic_mod').each( function(check){
			if( toCheck != null )
			{
				check.checked = true;
				selectedTopics.push( check.id.replace('file_', '') );
				ipb.downloads.totalChecked++;
			}
			else
			{
				toRemove.push( check.id.replace('file_', '') );
				check.checked = false;
			}
		});

		selectedTopics = selectedTopics.uniq().without( toRemove ).join(',');
		ipb.Cookie.set('modfileids', selectedTopics, 0);

		$('selectedfileids').value = selectedTopics;
		
		ipb.downloads.updateFileModButton();
	},
	
	/**
	 * Check a single file
	 */	
	checkFile: function(e)
	{
		remove = new Array();
		check = Event.findElement( e, 'input' );
		selectedTopics = $F('selectedfileids').split(',').compact();
		
		var checkboxesOnPage	= 0;
		var checkedOnPage		= 0;
		
		if( check.checked == true )
		{
			selectedTopics.push( check.id.replace('file_', '') );
			ipb.downloads.totalChecked++;
		}
		else
		{
			remove.push( check.id.replace('file_', '') );
			ipb.downloads.totalChecked--;
		}
		
		$$('.topic_mod').each( function(check){
			checkboxesOnPage++;
			
			if( $(check).checked == true )
			{
				checkedOnPage++;
			}
		} );
		
		if( $('files_all' ) )
		{
			if( checkedOnPage == checkboxesOnPage )
			{
				$('files_all' ).checked = true;
			}
			else
			{
				$('files_all' ).checked = false;
			}
		}
		
		selectedTopics = selectedTopics.uniq().without( remove ).join(',');		
		ipb.Cookie.set('modfileids', selectedTopics, 0);
		
		$('selectedfileids').value = selectedTopics;

		ipb.downloads.updateFileModButton();		
	},
	
	/**
	 * Update the moderation button
	 */	
	updateFileModButton: function( )
	{
		if( $('mod_submit') )
		{
			if( ipb.downloads.totalChecked == 0 ){
				$('mod_submit').disabled = true;
			} else {
				$('mod_submit').disabled = false;
			}
		
			$('mod_submit').value = ipb.lang['with_selected'].replace('{num}', ipb.downloads.totalChecked);
		}
	}
}

ipb.downloads.init();