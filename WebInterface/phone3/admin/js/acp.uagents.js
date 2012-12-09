/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* acp.uagents.js - User agent mapping			*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier 						*/
/************************************************/

var _uagents = window.IPBACP;
_uagents.prototype.uagents = {
	popups: {},
	
	init: function()
	{
		Debug.write("Initializing acp.uagents.js");
		document.observe("dom:loaded", function(){
			Debug.write("Here");
			Sortable.create( 'sortable_handle', { tag: 'li', only: 'isDraggable', revert: true, format: 'uagent_([0-9]+)', handle: 'draghandle', onUpdate: acp.uagents.updateAgents } );
			
			if( $('add_uagent') )
			{
				$('add_uagent').observe('click', acp.uagents.addNewAgent);
			}
		});
	},
	
	updateAgents: function( draggable, mouse )
	{
		var uagents = Sortable.serialize( 'sortable_handle', { tag: 'li', name: 'uagents' } );
		
		new Ajax.Request( acp.uagents.updateURL,
						{
							method: 'post',
							parameters: uagents,
							onSuccess: function(t)
							{
								Debug.write( t.responseText );
								doStriping();
							},
							onException: function(t)
							{
								doStriping();
							},
							onFailure: function(t)
							{
								doStriping();
							}
						});
	},
	
	deleteAgent: function(e, id)
	{
		if( !$('uagent_' + id) ){
			return;
		}
		
		if( !confirm( ipb.lang['ua_perm_remove'] ) )
		{
			return;
		}
		
		var url = ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=uagents&amp;do=removeuAgent&amp;uagent_id=" + id + '&secure_key=' + ipb.vars['md5_hash'];
		
		new Ajax.Request( url.replace(/&amp;/g, '&'),
						{
							method: 'post',
							evalJSON: 'force',
							onSuccess: function(t)
							{
								if( Object.isUndefined( t.responseJSON ) )
								{
									alert( ipb.lang['ua_error_remove'] );
									return;
								}
								
								if( t.responseJSON['errors'] )
								{
									alert( ipb.lang['ua_error_remove2'] + t.responseJSON['errors']);
									return;
								}
								
								acp.uagents.json = t.responseJSON;
								
								$('uagent_' + id).remove();
								doStriping();
							}
						});
	},
	
	addNewAgent: function( e )
	{
		Event.stop(e);
		
		if( acp.uagents.popups['new'] )
		{
			acp.uagents.popups['new'].show();
		}
		else
		{
			var html = ipb.templates['add_uagent'].evaluate( { id: 'new', box_title: ipb.lang['ua_add_string'] } );
			
			var afterInit = function( popup ){
				$('uagent_new_save').observe('click', acp.uagents.saveEditAgent.bindAsEventListener( this, 'new' ) );
			};
			
			acp.uagents.popups['new'] = new ipb.Popup('add_uagent_popup', { type: 'pane', modal: false, hideAtStart: false, w: '600px', initial: html }, { afterInit: afterInit } );
		}
	},
	
	editAgent: function(e, id)
	{
		Event.stop(e);
		
		if( !$('uagent_' + id) ){
			return;
		}
		
		if( acp.uagents.popups[ id ] )
		{
			acp.uagents.popups[ id ].show();
		}
		else
		{
			// Get info
			var info = acp.uagents.json['uagents'][ id ];
			if( !info ){ return; }
			
			var replace = {
							'id': 			id,
							'box_title': 	ipb.lang['ua_edit_string'],
							'a_name': 		info['uagent_name'],
							'a_key': 		info['uagent_key'],
							'a_position': 	info['uagent_position'],
							'a_regex': 		info['uagent_regex'],
							'a_capture': 	info['uagent_regex_capture'],
							'type_search': 	'',
							'type_browser': '',
							'type_other': ''
						};
						
			if( info['uagent_type'] == 'browser' ){
				replace['type_browser'] = "selected='selected'";
			} else if ( info['uagent_type'] == 'search' ) {
				replace['type_search'] = "selected='selected'";
			} else {
				replace['type_pther'] = "selected='selected'";
			}
						
			var html = ipb.templates['add_uagent'].evaluate( replace );
			
			var afterInit = function( popup ){
				$('uagent_' + id + '_save').observe('click', acp.uagents.saveEditAgent.bindAsEventListener( this, id ) );
			};
			
			acp.uagents.popups[ id ] = new ipb.Popup('edit_uagent_' + id , { type: 'pane', modal: false, hideAtStart: false, w: '600px', initial: html }, { afterInit: afterInit } );
		}
	},
	
	saveEditAgent: function(e, id)
	{
		var url = ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=uagents&amp;do=saveuAgent&amp;&uagent_id=" + id + '&secure_key=' + ipb.vars['md5_hash'];
		
		var params = {
						'uagent_key'			: $F('uagent_key_' + id ),
						'uagent_name'			: $F('uagent_name_' + id ).encodeParam(),
						'uagent_regex'			: $F('uagent_regex_' + id ),
						'uagent_regex_capture'	: $F('uagent_capture_' + id ),
						'uagent_type'			: $F('uagent_type_' + id ),
						'uagent_position'		: $F('uagent_position_' + id ),
						'type'					: ( id == 'new' ) ? 'add' : 'edit'
					};
					
		if( id == 'new' )
		{
			params['uagent_position'] = $H( acp.uagents.json['uagents'] ).size() + 1;
		}
		
		/* Got enough data? */
		if ( ! params['uagent_key'] || ! params['uagent_name'] || ! params['uagent_regex'] || ! params['uagent_type'] )
		{
			alert( ipb.lang['ua_form_incomplete'] );
			return false;
		}
					
		new Ajax.Request( url.replace( /&amp;/g, '&' ),
						{
							method: 'post',
							parameters: params,
							evalJSON: 'force',
							onSuccess: function(t)
							{
								if( Object.isUndefined( t.responseJSON ) )
								{
									alert( ipb.lang['ua_error_save'] );
									return;
								}
								else if( t.responseJSON['error'] )
								{
									t.responseJSON['error'] = ( t.responseJSON['error'] == 'UAGENT_EXISTS' ) ? ipb.lang['ua_exists'] : t.responseJSON['error'];
									alert( ipb.lang['ua_error_save2'] + t.responseJSON['error'] );
									return;
								}
								
								acp.uagents.json = t.responseJSON;
								
								if( id == 'new' )
								{
									var newparams = {
										'id': 		t.responseJSON['returnid'],
										'name': 	params['uagent_name'],
										'type': 	params['uagent_type']
									};
									
									// Need to add it to the list
									var html = ipb.templates['agent_row'].evaluate( newparams );
									$('sortable_handle').insert( { bottom: html } );
									
									acp.uagents.popups['new'].hide();
									
									$('uagent_key_new').value = '';
									$('uagent_name_new').value = '';
									$('uagent_regex_new').value = '';
									$('uagent_capture_new').value = '';
									$('uagent_position_new').value = 0;
									
									doStriping();
									
									/* Update ID */
									id = newparams['id'];
								}
								else
								{
									/* Update contents so that the name and image are updated in place */
									var newparams = {
										'id': 		id,
										'name': 	unescape(params['uagent_name']),
										'type': 	params['uagent_type']
									};
									
									// Need to add it to the list
									var html = ipb.templates['agent_row'].evaluate( newparams );
									
									$('uagent_' + id ).update( html.replace( /^<li([^>]+?)>(.*)<\/li>/, "$2" ) );
									
									acp.uagents.popups[ id ].hide();
								}
								
								/* Reset listeners */
								$('agent_' + id + '_edit').observe('click', acp.uagents.editAgent.bindAsEventListener( this, id ) );
								$('agent_' + id + '_delete').observe('click', acp.uagents.deleteAgent.bindAsEventListener( this, id ) );
								
								/* Reset sortable */
								Sortable.create( 'sortable_handle', { tag: 'li', only: 'isDraggable', revert: true, format: 'uagent_([0-9]+)', handle: 'draghandle', onUpdate: acp.uagents.updateAgents } );
							}
						});
	}
}

acp.uagents.init();