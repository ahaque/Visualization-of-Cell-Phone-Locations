/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.attachajax.js - Attachment manager			*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/
/* -TRUE- MULTIPLE ATTACHMENTS!!!				*/
/* -------------------------------------------- */

var _attach = window.IPBoard;

_attach.prototype.attachajax = {
	uploaders: [],
	template: '',

	
	init: function()
	{
		Debug.write("Initializing ips.attachAjax.js");
		
		document.observe("dom:loaded", function(){
			//ipb.attachajax.initUploaders();
		});
	},
	
	/* ------------------------------ */
	/**
	 * Registers an upload object
	 * 
	 * @param	{int}		id			The uploader ID
	 * @param	{object}	options		Options to pass to object
	*/
	registerUploader: function( id, wrapper, options )
	{
		/* Build iframe */
		iframe_obj = document.createElement( 'IFRAME' );
		
		//iframe_obj.src	           = ipb.vars['base_url'] + "app=core&module=attach&section=attach&do=attachiFrame&attach_rel_module=" +
		// 				   			 options['attach_rel_module'] + "&attach_rel_id=" + options['attach_rel_id'] + "&attach_post_key=" + 
		//				  			 options['attach_post_key'] + "&forum_id=" + options['forum_id'] + "&attach_id=" + id + '&fetch_all=1';
		iframe_obj.id              = 'iframeAttach_' + options['attach_rel_id'];
		iframe_obj.name			   = 'iframeAttach_' + options['attach_rel_id'];
		iframe_obj.scrolling       = 'no';
		iframe_obj.frameBorder     = 'no';
		iframe_obj.border          = '0';
		iframe_obj.className       = '';
		iframe_obj.style.width     = '300px';
		iframe_obj.style.height    = '50px';
		iframe_obj.style.overflow  = 'hidden';
		iframe_obj.style.display   = '';
		iframe_obj.style.backgroundColor = 'transparent';
		iframe_obj.allowtransparency = true;
		
		$( wrapper ).appendChild( iframe_obj );
		
		this.options = options;
		this.wrapper = wrapper;
		
		if( $(id) )
		{
			$(id).hide();
		}
		
		$('add_files_attach_' + options['attach_rel_id'] ).observe('click', this.processUpload.bindAsEventListener( this ) );
	},
	
	/**
	* Processes upload
	*/
	processUpload: function( e )
	{
		var iFrameBox  = window.frames[ 'iframeAttach_' + this.options['attach_rel_id'] ].document.getElementById('iframeUploadBox');
		var iFrameForm = window.frames[ 'iframeAttach_' + this.options['attach_rel_id'] ].document.getElementById('iframeUploadForm');
		var box        = $('attach_' + this.options['attach_rel_id'] );
		
		iFrameForm.action = ipb.vars['base_url'] + "app=core&module=attach&section=attach&do=attachUploadiFrame&attach_rel_module=" +
		 				   	this.options['attach_rel_module'] + "&attach_rel_id=" + this.options['attach_rel_id'] + "&attach_post_key=" + 
						  	this.options['attach_post_key'] + "&forum_id=" + this.options['forum_id'] + '&fetch_all=1';
		
		iFrameForm.submit();
	},
	/**
	* Iframe is ready
	*/
	isReady: function()
	{
		if ( this.json )
		{
			if ( this.json['is_error'] )
			{
				$('attach_error_box').update( ipb.lang['error'] + " <strong>" + this._determineServerError( this.json['msg'] ) + "</strong>" );
				
			}
			
			if ( this.json['current_items'] )
			{
				this.buildBoxes( this.json['current_items'] );
			}
			
			if ( typeof( this.json['attach_rel_id']) !='undefined' )
			{
				if( $('space_info_attach_' + this.json['attach_rel_id'] ) )
				{
					$('space_info_attach_' + this.json['attach_rel_id'] ).update( "Used <strong>" + this.json.attach_stats.space_used_human + "</strong> of <strong>" + this.json.attach_stats.total_space_allowed_human + "</strong>" );
				}
			}
		}
		
		Debug.write( "Attach: iFrame is ready" );
	},
	
	/**
	* Builds boxes
	*/
	buildBoxes: function( currentItems )
	{
		for( var i in currentItems )
		{
			id    = i;
			index = currentItems[i][0];
			name  = currentItems[i][1];
			size  = currentItems[i][2];
			temp = this.template.gsub(/\[id\]/, id + '_' + index).gsub(/\[name\]/, name);
	
			$( 'attachments' ).insert( temp );
		
			new Effect.Appear( $( 'ali_' + id + '_' + index ), { duration: 0.3 } );
			
			$( 'ali_' + id + '_' + index ).select('.info')[0].update( ipb.global.convertSize( size ) );
			
			// Add event handlers
			$( 'ali_' + id + '_' + index ).select('.delete')[0].observe( 'click', ipb.attachajax.removeUpload );
			
			// Remove old statuses
			['complete', 'in_progress', 'error'].each( function( cName ){ $( 'ali_' + id + '_' + index ).removeClassName( cName ); }.bind( this ) );

			$( 'ali_' + id + '_' + index ).addClassName( 'complete' );
			
			if( currentItems[ i ][ 3 ] == 1 )
			{
				tmp = currentItems[ i ];

				var width = tmp[5];
				var height = tmp[6];

				if( ( tmp[5] && tmp[5] > 30 ) )
				{
					width = 30;
					factor = ( 30 / tmp[5] );
					height = tmp[6] * factor;
				}

				if( ( tmp[6] && tmp[6] > 30 ) )
				{
					height = 30;
					factor = ( 30 / tmp[5] );
					width = tmp[5] * factor;
				}

				thumb = new Element('img', { src: ipb.vars['upload_url'] + '/' + tmp[4], 'width': width, 'height': height, 'class': 'thumb_img' } ).hide();

				$( 'ali_' + id + '_' + index ).select('.img_holder')[0].insert( thumb );
				new Effect.Appear( $( thumb ), { duration: 0.4 } );
			}
		}
	},
	
	removeUpload: function(e)
	{
		elem = Event.findElement( e, 'li' );
		elemid = elem.id.match( /^ali_(.+?)_([0-9]+)$/ );
		
		if( !elemid[1] ){ return; }
		
		// Send request to remove upload
		new Ajax.Request( ipb.vars['base_url'] + "app=core&module=attach&section=attach&do=attach_upload_remove&attach_rel_module=" +
		 				   ipb.attachajax.options['attach_rel_module'] + "&attach_rel_id=" + ipb.attachajax.options['attach_rel_id'] + "&attach_post_key=" + 
						  ipb.attachajax.options['attach_post_key'] + "&forum_id=" + ipb.attachajax.options['forum_id'] + "&attach_id=" + elemid[1] + '&secure_key=' + ipb.vars['secure_hash'],
						{
							method: 'post',
							evalJSON: 'force',
							onSuccess: function(t)
							{
								if( Object.isUndefined( t.responseJSON ) ){ alert( ipb.lang['action_failed'] ); return; }
								
								if( t.responseJSON.msg == 'attach_removed' )
								{
									$( elem.id ).hide();
								}
								else
								{
									alert( ipb.lang['action_failed'] );
									return;
								}
							}
						});
		
		Event.stop(e);
	},
	
	/* ------------------------------ */
	/**
	 * Returns a human-readable string for errors
	 * 
	 * @param	{string}	msg		The msg code
	 * @return	{string}			The human-readable message
	*/
	_determineServerError: function( msg )
	{
		if( msg.blank() ){ return ipb.lang['silly_server']; }
		
		switch( msg )
		{
			case 'upload_no_file':
				return ipb.lang['upload_no_file'];
				break;
			case 'invalid_mime_type':
				return ipb.lang['invalid_mime_type'];
				break;
			case 'upload_too_big':
				return ipb.lang['upload_too_big'];
				break;
			case 'upload_failed':
				return ipb.lang['upload_failed'];
				break;
			default:
				return ipb.lang['silly_server'];
				break;
		}
		
	},
	
	startedUploading: function(handler)
	{
		
	},
	
	finishedUploading: function(handler)
	{
		
	},
	
	/* ------------------------------ */
	/**
	 * Fetches an uploader
	 * 
	 * @param	{int}	id		ID of uploader to get
	 * @return	mixed			Object if exists, false if not
	*/
	getUploader: function( id )
	{
		if( ipb.attachajax.uploaders[ id ] )
		{
			return ipb.attachajax.uploaders[ id ];
		}
		else
		{
			return false;
		}
	}
}
ipb.attachajax.init();