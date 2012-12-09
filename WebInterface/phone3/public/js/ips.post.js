/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.board.js - Board index code				*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/

var _post = window.IPBoard;

_post.prototype.post = {
	cal_open: '',
	cal_close: '',
	
	/*------------------------------*/
	/* Constructor 					*/
	init: function()
	{
		Debug.write("Initializing ips.post.js");
		
		document.observe("dom:loaded", function(){
			ipb.post.initEvents();
		});
	},
	initEvents: function()
	{
		// Form validation
		if( $('postingform') ){
			$('postingform').observe('submit', ipb.post.postFormSubmit);
		}
		
		if( $('open_emoticons') ){
			$('open_emoticons').observe('click', ipb.post.toggleEmoticons);
		}
		
		if( $('post_options_options') && $('toggle_post_options') ){
			$('toggle_post_options').update( ipb.lang['click_to_show_opts'] );
			$('toggle_post_options').observe('click', ipb.post.showOptions );
		}
		
		// Add calendars
		if( $('mod_open_date') && $('mod_open_date_icon') ){
			$('mod_open_date_icon').observe('click', function(){
				new CalendarDateSelect( $('mod_open_date'), { year_range: 6, close_on_click: true } );
			});
		}
		if( $('mod_close_date') && $('mod_close_date_icon') ){
			$('mod_close_date_icon').observe('click', function(){
				new CalendarDateSelect( $('mod_close_date'), { year_range: 6, close_on_click: true } );
			});
		}
		
		if( $('post_preview' ) ){
			// Resize images
			ipb.global.findImgs( $( 'post_preview' ) );
		}
		
		// Image resizing for topic summary
		if( $('topic_summary') ){
			ipb.global.findImgs( $('topic_summary') );
		}

		if( $('review_topic') ){
			$('review_topic').observe('click', ipb.global.openNewWindow.bindAsEventListener( this, $('review_topic'), 1 ) );
		}
	},
	
/*	toggleEmoticons: function(e)
	{
		Event.stop(e);
		
		// Get the emoticons
		var url = ipb.vars['base_url'] + "app=forums&amp;module=ajax&amp;section=emoticons&amp;editor_id=ed-0";
		
		new Ajax.Request( url.replace(/&amp;/g, '&'),
						{
							method: 'get',
							onSuccess: function(t)
							{
								// Add the sidebar
								$('editor_ed-0').addClassName('with_sidebar');
								var div = $('editor_ed-0').down('.sidebar');
								
								div.update( ipb.templates['emoticon_wrapper'] );
								$('emoticon_holder').update( t.responseText );
								div.show();
							}
						});
						
		
	},
	
	addEmoticon: function( code, id, img, editor_id )
	{		
		try {
			ipb.editors[ editor_id ].insert_emoticon( '', img, code, '' );
		}
		catch(err)
		{
			Debug.error( err );
		}
		
		return false;		
	},*/
	
	postFormSubmit: function(e)
	{
		return true;
		
		Event.stop(e);
		Debug.write( "Submitting" );
		if( $('username') && $F('username').blank() ){
			alert( ipb.lang['post_empty_username'] );
			error = true;
		}
		if( $('topic_title')  ){
			alert( ipb.lang['post_empty_title'] );
			error = true;
		}
		if( $('ed-0_textarea') && $F('ed-0_textarea').blank() ){
			alert( ipb.lang['post_empty_post'] );
			error = true;
		}
		
		
		if( error ){ Event.stop(e); };
		
	},
	
	showOptions: function(e)
	{
		new Effect.Fade( $('toggle_post_options'), { duration: 0.2 } );
		//$('toggle_post_options').hide();
		new Effect.BlindDown( $( 'post_options_options' ), { duration: 0.3 } );
	},
	
	hideOptions: function()
	{
		if( $('post_options_options') )
		{
			$('post_options_options').hide();
		}
	}
}
ipb.post.init();