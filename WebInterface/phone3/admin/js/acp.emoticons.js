/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* acp.emoticons.js - Emoticon functions		*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/

var _temp = window.IPBACP;

_temp.prototype.emoticons = {
	
	popups: {},
	
	folder: function( e, id  )
	{
		Event.stop(e);
		
		// do we have a popup?
		if( acp.emoticons.popups[ id ] )
		{
			acp.emoticons.popups[ id ].show();
		}
		else
		{
			var lang = ( id == 0 ) ? ipb.lang['emoticons']['add'] : ipb.lang['emoticons']['edit'];
			var formdo = ( id == 0 ) ? 'emo_setadd' : 'emo_setedit';
			var name = ( id == 0 ) ? '' : id;
			
			var content = ipb.templates['emo_manage'].evaluate( { 	form_do: formdo,
				 													form_id: id,
				 													folder_name: name, 
																	form_value: lang
																} );
																
			// Make popup
			acp.emoticons.popups[ id ] = new ipb.Popup('emoticon_folder_' + id, { type: 'pane', modal: false, hideAtStart: false, w: '600px', initial: content } );
		}
	}
}