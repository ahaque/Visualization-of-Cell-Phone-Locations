/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* acp.members.js - Member functions			*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/

var _temp = window.IPBACP;

_temp.prototype.members = {
	
	popups: {},
	fields: {},
	
	movePruneAction: function( e, type )
	{
		$('f_search_type').value = type;
		$('memberListForm').submit(); 
	},
	
	switchSearch: function(e, type)
	{
		try {
			if( type == 'advanced' )
			{
				$('m_simple').hide();
				$('m_advanced').show();
			}
			else
			{
				$('m_advanced').hide();
				$('m_simple').show();
			}
		} catch(err) {}
	},
	
	goToTab: function(tabid) 
	{
		var evt;
		var el = $(tabid);

		if ( document.createEvent )
		{
			evt = document.createEvent("MouseEvents");
			evt.initMouseEvent("click", true, true, window, 0, 0, 0, 0, 0, false, false, false, false, 0, null);
		}

		(evt) ? el.dispatchEvent(evt) : (el.click && el.click());
	},
	
	changeAvatar: function( e, url )
	{
		Event.stop(e);
		
		url = ipb.vars['base_url'] + url + '&secure_key=' + ipb.vars['md5_hash'];
		url = url.replace( /&amp;/g, '&' );
		
		var afterInit = function( popup ){
							if( $('avatar_gallery') )
							{
								$('avatar_gallery').observe('change', acp.members.updateAvatarGallery);
							}
						}
		acp.members.popups['avatar'] = new ipb.Popup('m_avatar', { type: 'pane', modal: false, hideAtStart: false, w: '600px', ajaxURL: url }, { 'afterInit': afterInit } );
	},
	
	updateAvatarGallery: function( e )
	{
		Event.stop(e);
		
		var selected_cat = $F('avatar_gallery');
		var url = ipb.vars['base_url'] + "app=forums&amp;module=ajax&amp;section=member_editform&amp;do=get_avatar_images&amp;cat=" + selected_cat + '&secure_key=' + ipb.vars['md5_hash'];
		
		new Ajax.Request( url.replace(/&amp;/g, '&'),
						{
							method: 'get',
							evalJSON: 'force',
							onSuccess: function(t)
							{
								if( Object.isUndefined( t.responseJSON ) )
								{
									$('avatarGalleryContainer').update( t.responseText ).show();
									return;
								}
								
								if( t.responseJSON['error'] )
								{
									alert("There was an error retreiving this avatar gallery: " + t.responseJSON['error']);
									return;
								}
								
								if( t.responseJSON['html'] )
								{
									$('avatarGalleryContainer').update( t.responseJSON['html'] ).show();
								}
							}
						});
	},
	
	updateAvatarPreview: function()
	{
		var cat = $F('avatar_gallery');
		var img = $F('avatar_image');
		
		var img_url			= ipb.vars['public_avatar_url'] + cat + '/' + img;
		
		if( !$('avatarPreview') )
		{
			var newimg = new Element( 'img', { 'id': 'avatarPreview', src: img_url });
			$('avatarContainer').insert( newimg );
		}
		else
		{
			$('avatarPreview').src = img_url;
		}
	},
	
	newPhoto: function( e, url )
	{
		Event.stop(e);
		
		url = ipb.vars['base_url'] + url + '&secure_key=' + ipb.vars['md5_hash'];
		url = url.replace( /&amp;/g, '&' );
		
		acp.members.popups['photo'] = new ipb.Popup('m_photo', { type: 'pane', modal: false, hideAtStart: false, w: '600px', ajaxURL: url } );		
	},
	
	banManager: function( e, url )
	{
		Event.stop(e);
		
		url = ipb.vars['base_url'] + url + '&secure_key=' + ipb.vars['md5_hash'];
		url = url.replace( /&amp;/g, '&' );
		
		acp.members.popups['photo'] = new ipb.Popup('m_ban', { type: 'pane', modal: false, hideAtStart: false, w: '600px', h: '600px', ajaxURL: url } );		
	},
	
	removeAvatar: function( e, member_id )
	{
		Event.stop(e);
		
		Debug.write("Calling once...");
		var url = ipb.vars['base_url'] + "app=forums&amp;module=ajax&amp;section=member_editform&amp;do=remove_avatar&amp;member_id=" + member_id + '&secure_key=' + ipb.vars['md5_hash'];
		
		new Ajax.Request( url.replace( /&amp;/g, '&' ),
						{
							method: 'get',
							evalJSON: 'force',
							onSuccess: function(t)
							{
								if( Object.isUndefined( t.responseJSON ) )
								{
									Debug.write( t.responseText );
									alert("An error occurred: " + t.responseText );
									return;
								}
								
								if( t.responseJSON['error'] )
								{
									Debug.write( t.responseJSON['error'] );
									alert("There was an error removing this avatar: " + t.responseJSON['error']);
									return;
								}
								else
								{
									try {
										$('MF__avatar').src = '';
									} catch(err) {
										Debug.write( err );
									}
								}								
							}
						});
	},
	
	removePhoto: function( e, member_id )
	{
		Event.stop(e);
		Debug.write("Removing photo...");
		
		url = ipb.vars['base_url'] + "app=members&amp;module=ajax&amp;section=editform&amp;do=remove_photo&amp;member_id=" + member_id + '&secure_key=' + ipb.vars['md5_hash'];

		new Ajax.Request( url.replace( /&amp;/g, '&' ),
						  {
							method: 'GET',
							evalJSON: 'force',
							onSuccess: function (t )
							{
								if( Object.isUndefined( t.responseJSON ) )
								{
									Debug.write( t.responseText );
									alert("An error occurred: " + t.responseText );
									return;
								}
								
								if( t.responseJSON['error'] )
								{
									Debug.write( t.responseJSON['error'] );
									alert("There was an error removing this avatar: " + t.responseJSON['error']);
									return;
								}
								else
								{
									try {
										$('MF__pp_photo').src = t.responseJSON['pp_main_photo'];
										$('MF__pp_photo').setStyle( { width: t.responseJSON['pp_main_width'] + 'px' } );
										$('MF__pp_photo').setStyle( { height: t.responseJSON['pp_main_height'] + 'px' } );
										$('MF__pp_photo_container').setStyle( { width: t.responseJSON['pp_main_width'] + 'px' } );
									} catch(err) {
										Debug.write( err );
									}
								}
							}
						  } );
	},
	
	editField: function( e, id, lang, url )
	{
		Event.stop(e);
		
		url = ipb.vars['base_url'] + url + '&secure_key=' + ipb.vars['md5_hash'];
		url = url.replace( /&amp;/g, '&' );
		
		var afterInit = function( popup ){
							$( id + '_save' ).observe('click', acp.members.saveField.bindAsEventListener( this, id ) );
							//Debug.write("This is the callback");
						}.bind(this);
						
		var afterHide = function( popup ){
							$( popup.getObj() ).remove();
							acp.members.popups[ id ] = null;
						}.bind(this);
						
		acp.members.popups[ id ] = new ipb.Popup('m_' + id, { type: 'pane', modal: false, hideAtStart: false, w: '500px', ajaxURL: url }, { 'afterInit': afterInit, 'afterHide': afterHide } );
	},
	
	saveField: function( e, id )
	{
		Event.stop(e);
		
		//firebug.d.console.cmd.dir( acp.members.fields );
		
		var url = acp.members.fields[ id ]['url'];
		
		if( !url ){ alert("Cannot save form; url missing"); return; }
		
		url = ipb.vars['base_url'] + url + '&secure_key=' + ipb.vars['md5_hash'];
		url = url.replace( /&amp;/g, '&' );
		
		//-----------------
		
		var fields = acp.members.fields[ id ]['fields'];
		var _params = {};
		
		if ( fields.length )
		{
			for( var i = 0 ; i <= fields.length ; i++ )
			{
				if( $( fields[i] ) )
				{
					try 
					{
						if ( $( fields[i] ).type == 'select-multiple' )
						{
							_params[ fields[i] + '[]' ] = $F( fields[i] );
						}
						else
						{
							_params[ fields[i] ] = $F( fields[i] ).encodeParam();
						}
					}
					catch( e )
					{
						
						try
						{
							_params[ fields[i] ] = $F( fields[i] + '_yes' );
						}
						catch( e ){}
					}
				}
				else if( $( fields[i] + '_yes' ) )
				{
					try
					{
						_params[ fields[i] ] = $F( fields[i] + '_yes' );
					}
					catch( e ){}
				}
			}
		}
		
		// Send request
		new Ajax.Request( 	url, {
							method: 'post',
							parameters: _params,
							evalJSON: 'force',
							onSuccess: function(t)
							{
								if( Object.isUndefined( t.responseJSON ) )
								{
									alert("An error occurred: " + t.responseJSON);
									return;
								}
								
								if( !t.responseJSON['success'] )
								{
									alert("An error occurred: " + t.responseJSON['error'] );
									return;
								}
								else
								{
									if( acp.members.fields[ id ]['callback'] )
									{
										acp.members.fields[ id ]['callback']( t, t.responseJSON );
									}
									
									acp.members.popups[ id ].hide();
										
								}
								
								Debug.write("Success");
							},
							onException: function(t){ alert("An error occurred: " + t.responseText) },
							onFailure: function(t){ alert("An error occurred: " + t.responseText ) }
						} );
								
								
	}
			
}