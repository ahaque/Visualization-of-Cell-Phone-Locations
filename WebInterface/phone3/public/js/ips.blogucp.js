/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.blogucp.js - Blog javascript				*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/

var _blogucp = window.IPBoard;

_blogucp.prototype.blogucp = {
	
	/*------------------------------*/
	/* Constructor 					*/
	init: function(){
		
		Debug.write("Initializing ips.blogucp.js");
		
		document.observe("dom:loaded", function(){
			ipb.blogucp.updateForm();
			
			$$('.blogform').each( function(elem)
			{
				Debug.write( elem );
				switch( elem.tagName )
				{
					case 'INPUT':
					case 'LABEL':
						$( elem ).observe('click', ipb.blogucp.updateForm);
					break;
					case 'SELECT':
						$( elem ).observe('change', ipb.blogucp.updateForm);
					break;
				}
			});				
		});
	},
	
	updateForm: function(e)
	{
		if( $F('blog_type') == 'external' ){
			ipb.blogucp.hide( $('blog_local_settings') );
			ipb.blogucp.hide( $('list_blog_view_level') );
			ipb.blogucp.hide( $('blog_rss_settings') );
			ipb.blogucp.hide( $('blog_customize_settings') );
			ipb.blogucp.hide( $('blog_private_club') );
			ipb.blogucp.hide( $('blog_editors') );
			ipb.blogucp.show( $('blog_local_settings_hidden') );
			ipb.blogucp.show( $('blog_external_settings') );
		} else {
			ipb.blogucp.show( $('blog_local_settings') );
			ipb.blogucp.show( $('list_blog_view_level') );
			ipb.blogucp.show( $('blog_rss_settings') );
			ipb.blogucp.show( $('blog_customize_settings') );
			ipb.blogucp.show( $('blog_private_club') );
			ipb.blogucp.show( $('blog_editors') );
			ipb.blogucp.hide( $('blog_local_settings_hidden') );
			ipb.blogucp.hide( $('blog_external_settings') );
		}
		
		if( $F('blog_view_level') == 'private' || $F('blog_view_level') == 'privateclub' ){
			ipb.blogucp.hide( $('list_allowguest') );
			ipb.blogucp.hide( $('list_allowguestcomments') );
		} else {
			ipb.blogucp.show( $('list_allowguest') );
			ipb.blogucp.show( $('list_allowguestcomments') );
		}
		
		if( $F('blog_view_level') == 'privateclub' ){
			ipb.blogucp.show( $('blog_private_club' ) );
		} else {
			ipb.blogucp.hide( $('blog_private_club' ) );
		}
		
		if( $('blog_allowguest').checked ){
			ipb.blogucp.show( $('list_allowguestcomments') );
		} else {
			ipb.blogucp.hide( $('list_allowguestcomments') );
		}
		
	},
	
	hide: function(elem)
	{
		if( $( elem ) ){ $( elem ).hide(); }
	},
	
	show: function(elem)
	{
		if( $( elem ) ){ $( elem ).show(); }
	}	
}

ipb.blogucp.init();