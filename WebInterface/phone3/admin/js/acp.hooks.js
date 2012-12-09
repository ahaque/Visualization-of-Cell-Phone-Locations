/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* acp.uagents.js - User agent mapping			*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier 						*/
/************************************************/

var _hooks = window.IPBACP;
_hooks.prototype.hooks = {
	popups: {},
	fields: {},
	languageMax: null,
	
	init: function()
	{
		Debug.write("Initializing acp.hooks.js");
		document.observe("dom:loaded", function(){
	
		});
	},
	
	generateStrings: function( i )
	{
		Debug.write("Getting strings...");
		
		var selected_cat = $F('language_' + i );

		url = ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=hooks&amp;do=getStrings&amp;group=" + selected_cat + '&secure_key=' + ipb.vars['md5_hash'] + "&id=" + acp.hooks.hookID + "&i=" + i;
		url = url.replace( /&amp;/g, '&' );
		
		new Ajax.Request( url,
						  {
							method: 'GET',
							evalJSON: true,
							onSuccess: function(t)
							{
								if( Object.isUndefined( t.responseJSON ) || t.responseJSON == null )
								{
									
									$('container_' + i).update( t.responseText ).show();
									$('container_desc_' + i).show();
								}
								else
								{
									if( t.responseJSON['error'] )
									{
										$('container_' + i).update( t.responseJSON['error'] ).show();
									}
									else if( t.responseJSON['success'] )
									{
										$('container_' + i).update( t.responseJSON['html'] ).show();
										$('container_desc_' + i).show();
									}
									else
									{
										$('container_' + i).update( t.responseText ).show();
										$('container_desc_' + i).show();
									}
								}
							}
						});
									
	},
	
	generateTemplates: function( i )
	{
		var selected_cat = $F('skin_' + i );

		url = ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=hooks&amp;do=getTemplates&amp;group=" + selected_cat + '&secure_key=' + ipb.vars['md5_hash'] + "&id=" + acp.hooks.hookID + "&i=" + i;
		url = url.replace( /&amp;/g, '&' );
		
		new Ajax.Request( url,
						  {
							method: 'GET',
							evalJSON: true,
							onSuccess: function(t)
							{
								if( Object.isUndefined( t.responseJSON ) || t.responseJSON == null )
								{
									
									$('s_container_' + i).update( t.responseText ).show();
									$('s_container_desc_' + i).show();
								}
								else
								{
									if( t.responseJSON['error'] )
									{
										$('s_container_' + i).update( t.responseJSON['error'] ).show();
									}
									else if( t.responseJSON['success'] )
									{
										$('s_container_' + i).update( t.responseJSON['html'] ).show();
										$('s_container_desc_' + i).show();
									}
									else
									{
										$('s_container_' + i).update( t.responseText ).show();
										$('s_container_desc_' + i).show();
									}
								}
							}
						});
	},
	
	generateFields: function( i )
	{
		
		var selected_type = $F('type_' + i );

		if( $('d_container_' + i) != null )
		{
			$('d_container_' + i).update('');
		}
		
		var template = '';
		
		switch( selected_type )
		{
			case 'create':
				template = ipb.templates['db_create'].evaluate( { id: i } );
				acp.hooks.fields['MF__database']['fields'].push( "name_" + i );
				acp.hooks.fields['MF__database']['fields'].push( "fields_" + i );
				acp.hooks.fields['MF__database']['fields'].push( "tabletype_" + i );
			break;
			case 'alter':
				template = ipb.templates['db_alter'].evaluate( { id: i } );
				acp.hooks.fields['MF__database']['fields'].push( "altertype_" + i );
				acp.hooks.fields['MF__database']['fields'].push( "table_" + i );
				acp.hooks.fields['MF__database']['fields'].push( "field_" + i );
				acp.hooks.fields['MF__database']['fields'].push( "newfield_" + i );
				acp.hooks.fields['MF__database']['fields'].push( "fieldtype_" + i );
				acp.hooks.fields['MF__database']['fields'].push( "default_" + i );
			break;
			case 'update':
				template = ipb.templates['db_update'].evaluate( { id: i } );
				acp.hooks.fields['MF__database']['fields'].push( "table_" + i );
				acp.hooks.fields['MF__database']['fields'].push( "field_" + i );
				acp.hooks.fields['MF__database']['fields'].push( "newvalue_" + i );
				acp.hooks.fields['MF__database']['fields'].push( "oldvalue_" + i );
				acp.hooks.fields['MF__database']['fields'].push( "where_" + i );
			break;
			case 'insert':
				template = ipb.templates['db_insert'].evaluate( { id: i } );
				acp.hooks.fields['MF__database']['fields'].push( "table_{$k}" );
				acp.hooks.fields['MF__database']['fields'].push( "updates_{$k}" );
				acp.hooks.fields['MF__database']['fields'].push( "fordelete_{$k}" );
			break;
			default:
				return;
			break;
		}
				 
		$('d_container_' + i).insert( template ).show();
		$('d_container_desc_' + i).show();
	},
	
	addAnotherLanguage: function( e )
	{
		Event.stop(e);
		
		var id = parseInt( acp.hooks.languageMax ) + 1;
		
		var url = ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=hooks&amp;do=getLangFiles&amp;secure_key=" + ipb.vars['md5_hash'] + "&id=" + acp.hooks.hookID + "&i=" + id;
		url = url.replace( /&amp;/g, '&' );
		
		new Ajax.Request( url,
						{
							method: 'GET',
							evalJSON: true,
							onSuccess: function(t)
							{
								if( Object.isUndefined( t.responseJSON ) || t.responseJSON == null )
								{
									var template = ipb.templates['lang_row'].evaluate({ 'containerid': id, 'control': t.responseText});
									$('language_wrap').insert( template );
									
									acp.hooks.fields['MF__language']['fields'].push("language_" + id);
									acp.hooks.fields['MF__language']['fields'].push("strings_" + id);
									acp.hooks.languageMax = id;
								}
								else
								{
									if( t.responseJSON['error'] )
									{
										alert( t.responseJSON['error'] );
									}
									else if( t.responseJSON['success'] )
									{
										$('container_' + id).update( t.responseJSON['html'] ).show();
									}
									else
									{
										alert( t.responseText );
									}
								}
								
							}
						});
						
		Debug.write( acp.hooks.languageMax + 1 );
		
	},
	
	addAnotherTemplate: function( e )
	{
		Event.stop(e);
		
		var id = parseInt( acp.hooks.skinMax ) + 1;
		
		var url = ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=hooks&amp;do=getSkinFiles&amp;secure_key=" + ipb.vars['md5_hash'] + "&id=" + acp.hooks.hookID + "&i=" + id;
		url = url.replace( /&amp;/g, '&' );
		
		new Ajax.Request( url,
						{
							method: 'GET',
							evalJSON: true,
							onSuccess: function(t)
							{
								if( Object.isUndefined( t.responseJSON ) || t.responseJSON == null )
								{
									var template = ipb.templates['skin_row'].evaluate({ 'containerid': id, 'control': t.responseText});
									$('skin_wrap').insert( template );
									
									acp.hooks.fields['MF__templates']['fields'].push("templates_" + id);
									acp.hooks.fields['MF__templates']['fields'].push("skin_" + id);
									acp.hooks.skinMax = id;
								}
								else
								{
									if( t.responseJSON['error'] )
									{
										alert( t.responseJSON['error'] );
									}
									else if( t.responseJSON['success'] )
									{
										$('s_container_' + id).update( t.responseJSON['html'] ).show();
									}
									else
									{
										alert( t.responseText );
									}
								}
								
							}
						});
						
		Debug.write( acp.hooks.skinMax + 1 );
		
	},
	
	addAnotherDB: function( e )
	{
		Event.stop(e);
		
		var id = parseInt( acp.hooks.dbMax ) + 1;
		var wrapper = ipb.templates['db_row'].evaluate( { 'id': id } );
		
		$('database_wrap').insert( wrapper );
		
		acp.hooks.dbMax = id;
		acp.hooks.fields['MF__database']['fields'].push( "type_" + id );
	},
	
	exportHook: function( e, id, lang, url )
	{
		Event.stop(e);
		
		url = ipb.vars['base_url'] + url + '&secure_key=' + ipb.vars['md5_hash'];
		url = url.replace( /&amp;/g, '&' );
		
		var afterInit = function( popup ){
							$( id + '_save' ).observe('click', acp.hooks.saveField.bindAsEventListener( this, id ) );
							//Debug.write("This is the callback");
						}.bind(this);
						
		var afterHide = function( popup ){
							$( popup.getObj() ).remove();
							acp.hooks.popups[ id ] = null;
						}.bind(this);
		
		acp.hooks.popups[ id ] = new ipb.Popup('m_' + id, { type: 'pane', modal: true, hideAtStart: false, w: '600px', ajaxURL: url }, { 'afterInit': afterInit, 'afterHide': afterHide } );
		
		Debug.write("Hook " + id);
	},
	
	saveField: function( e, id )
	{
		Event.stop(e);
		
		var url = acp.hooks.fields[ id ]['url'];
		
		if( !url ){ alert("Cannot save form; url missing"); return; }
		
		url = ipb.vars['base_url'] + url + '&secure_key=' + ipb.vars['md5_hash'];
		url = url.replace( /&amp;/g, '&' );
		
		//-----------------
		
		var fields = acp.hooks.fields[ id ]['fields'];
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
							_params[ fields[i] ] = $F( fields[i] );
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
										if( acp.hooks.fields[ id ]['callback'] )
										{
											acp.hooks.fields[ id ]['callback']( t, t.responseJSON );
										}

										acp.hooks.popups[ id ].hide();

									}

									Debug.write("Success");
								},
								onException: function(t){ alert("An error occurred: " + t.responseText) },
								onFailure: function(t){ alert("An error occurred: " + t.responseText ) }
							} );
	}
}

acp.hooks.init();
