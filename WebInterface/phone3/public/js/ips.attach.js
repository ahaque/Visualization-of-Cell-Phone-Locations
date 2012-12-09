/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.attach.js - Attachment manager			*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/
/* -TRUE- MULTIPLE ATTACHMENTS!!!				*/
/* -------------------------------------------- */

var _attach = window.IPBoard;

_attach.prototype.attach = {
	uploaders: [],
	template: '',
	
	init: function()
	{
		Debug.write("Initializing ips.attach.js");
		
		document.observe("dom:loaded", function(){
			//ipb.attach.initUploaders();
		});
	},
	
	/* ------------------------------ */
	/**
	 * Registers an upload object
	 * 
	 * @param	{int}		id			The uploader ID
	 * @param	{object}	options		Options to pass to object
	*/
	registerUploader: function( id, type, wrapper, options )
	{		
		//if( !ipb.vars['use_swfupload'] ){ return false; }
		
		if( Object.isUndefined( id ) || id == null )
		{
			Debug.error("ips.attach.js: Attachment manager already has that ID");
			return;
		}
		// Already exists?
		if( ipb.attach.uploaders[ id ] )
		{
			Debug.error("ips.attach.js: This uploader has already been registered");
		}
		
		//-----------------------------------------
		// Make object
		
		if( type == 'swf' ){
			if( options.file_size_limit )
			{
				options.file_size_limit = options.file_size_limit + " B";
			}
			uploader = new ipb.attachSWF( id, options, wrapper, ipb.attach.template );
		} else {
			uploader = new ipb.attachTraditional( id, options, wrapper, ipb.attach.template );
		}
		
		if( uploader )
		{
			ipb.attach.uploaders[ id ] = uploader;
			
			if( $( 'nojs_' + id + '_1' ) ){
				$( 'nojs_' + id + '_1' ).hide();
			}
			if( $( 'nojs_' + id + '_2' ) ){
				$( 'nojs_' + id + '_2' ).hide();
			}
		}		
	},
	
	removeUpload: function(e)
	{
		elem = Event.findElement( e, 'li' );
		elemid = elem.id.match( /^ali_(.+?)_([0-9]+)$/ );
		
		if( !elemid[1] ){ return; }
		
		obj = ipb.attach.uploaders[ elemid[1] ];
		
		// Send request to remove upload
		new Ajax.Request( ipb.vars['base_url'] + "app=core&module=attach&section=attach&do=attach_upload_remove&attach_rel_module=" +
		 				   obj.options['attach_rel_module'] + " &attach_rel_id=" + obj.options['attach_rel_id'] + " &attach_post_key=" + 
						  obj.options['attach_post_key'] + "&forum_id=" + obj.options['forum_id'] + "&attach_id=" + elem.readAttribute('attachid') + '&secure_key=' + ipb.vars['secure_hash'],
						{
							method: 'post',
							evalJSON: 'force',
							onSuccess: function(t)
							{
								if( Object.isUndefined( t.responseJSON ) ){ alert( ipb.lang['action_failed'] ); return; }
								
								if( t.responseJSON.msg == 'attach_removed' )
								{
									ipb.attach.uploaders[ elemid[1] ].removeUpload( elem.readAttribute('attachid'), elemid[2], t.responseJSON );
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
	
	startedUploading: function(handler)
	{
		
	},
	
	finishedUploading: function( attachid, fileindex )
	{
		if( !ipb.attach.uploaders[ attachid ] ){ return; }
		if( !ipb.attach.uploaders[ attachid ].boxes[ fileindex ] ){ return; }
		
		var row = ipb.attach.uploaders[ attachid ].boxes[ fileindex ];
		
		if( $( row ) )
		{
			var link = $( row ).down('.add_to_post');
			if( $(link) )
			{
				$( link ).writeAttribute( 'fileindex', fileindex ).writeAttribute( 'attachid', attachid );
				$( link ).observe('click', ipb.attach.insertIntoPost );
			}
		}		
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
		if( ipb.attach.uploaders[ id ] )
		{
			return ipb.attach.uploaders[ id ];
		}
		else
		{
			return false;
		}
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
		
		if( !Object.isUndefined( ipb.lang[ msg ] ) )
		{
			return ipb.lang[ msg ];
		}
		else
		{
			return ipb.lang['silly_server'];
		}
		
		/*switch( msg )
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
		}	*/
	},
	
	_jsonPass: function( id, json )
	{
		ipb.attach.uploaders[ 'attach_' + id ].json = json;
		ipb.attach.uploaders[ 'attach_' + id ].isReady();
		
		Debug.write( "ips.attach.js: Got json back from iframe id " + id );
	},
	
	/**
	* Builds boxes
	*/
	_buildBoxes: function( currentItems )
	{
		for( var i in currentItems )
		{
			Debug.write( "Templating item: " + currentItems[i][1] );
			
			id    = i;
			index = currentItems[i][0];
			name  = currentItems[i][1];
			size  = currentItems[i][2];
			temp = uploader.template.gsub(/\[id\]/, uploader.id + '_' + index).gsub(/\[name\]/, name);

			$( uploader.wrapper ).insert( temp );

			$( 'ali_' + uploader.id + '_' + index ).select('.progress_bar')[0].hide();

			new Effect.Appear( $( 'ali_' + uploader.id + '_' + index ), { duration: 0.3 } );
			
			$( 'ali_' + uploader.id + '_' + index ).select('.info')[0].update( ipb.global.convertSize( size ) );
			// Add attachID
			$( 'ali_' + uploader.id + '_' + index ).writeAttribute( 'attachid', index );
			// Add event handlers
			$( 'ali_' + uploader.id + '_' + index ).select('.delete')[0].observe( 'click', ipb.attach.removeUpload );
			
			// Remove old statuses
			['complete', 'in_progress', 'error'].each( function( cName ){ $( 'ali_' + uploader.id + '_' + index ).removeClassName( cName ); }.bind( uploader ) );

			$( 'ali_' + uploader.id + '_' + index ).addClassName( 'complete' );
			
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

				$( 'ali_' + uploader.id + '_' + index ).select('.img_holder')[0].insert( thumb );
				new Effect.Appear( $( thumb ), { duration: 0.4 } );
			}
			
			uploader.boxes[ index ] = 'ali_' + uploader.id + '_' + index;
			
			// Fire finishedUpload
			ipb.attach.finishedUploading( uploader.id, index );
		}
	},
	
	insertIntoPost: function(e)
	{
		Event.stop(e);
		var elem = Event.element(e);
		
		if( !elem.hasClassName('add_to_post') )
		{
			elem = Event.findElement(e, 'add_to_post');
		}
		
		elem = elem.up('.attach_row');
		
		var fileindex = elem.readAttribute('attachid');
		var filename = elem.down('.attach_name').innerHTML;
		
		if( fileindex && filename )
		{
			ipb.editorInsert( "[attachment=" + fileindex + ":" + filename + "]", 'ed-0' );
		}
		else if( fileindex )
		{
			ipb.editorInsert( "[attachment=" + fileindex + "]", 'ed-0' );
		}
	}	
}
ipb.attach.init();

//==============================================================

_attach.prototype.attachTraditional = Class.create({
	options: [],
	boxes: [],
	
	initialize: function( id, options, wrapper, template )
	{
		this.id = id;
		this.wrapper = wrapper;
		this.template = template;
		this.options = options;
		
		/* Build iframe */
		this.iframe = new Element('iframe', { 	id: 'iframeAttach_' + this.options['attach_rel_id'],
		 										name: 'iframeAttach_' + this.options['attach_rel_id'],
												scrolling: 'no',
												frameBorder: 'no',
												border: '0',
												className: '',
												allowTransparency: true,
												src: this.options.upload_url,
												tabindex: '1'
											}).setStyle({
												width: '500px',
												height: '50px',
												overflow: 'hidden',
												backgroundColor: 'transparent'
											});
											
		$( this.wrapper ).insert( { after: this.iframe } ).addClassName('traditional');
		
		$('add_files_' + this.id ).observe('click', this.processUpload.bindAsEventListener( this ) );		
	},
	
	_createFrame: function()
	{
		
	},
	
	removeUpload: function( attachid, fileindex, fileinfo )
	{
		// Remove box
		new Effect.Fade( $( this.boxes[ fileindex ] ), { duration: 0.4 } );
		
		// Remove reference
		this.boxes[ fileindex ] = null;
		
		// Update totals
		if( $('space_info_' + this.id ) && ipb.lang['used_space'] )
		{
			$('space_info_' + this.id ).update( ipb.lang['used_space'].gsub(/\[used\]/, fileinfo['attach_stats']['space_used_human']).gsub(/\[total\]/, fileinfo['attach_stats']['total_space_allowed_human']) );
		}
		
		Debug.write( "ips.attach.js: (ID " + this.id + ", removeUpload) Attach ID: " + attachid + ", File index: " + fileindex );
	},
	
	/**
	* Processes upload
	*/
	processUpload: function( e )
	{
		$('attach_error_box').hide();
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
				$('attach_error_box').update( ipb.lang['error'] + " <strong>" + ipb.attach._determineServerError( this.json['msg'] ) + "</strong>" );
				$('attach_error_box').show();
			}
			
			if ( this.json['current_items'] )
			{
				$( this.wrapper ).update();
				ipb.attach._buildBoxes( this.json['current_items'] );
			}
			
			if ( typeof( this.json['attach_rel_id']) !='undefined' )
			{
				if( $('space_info_attach_' + this.json['attach_rel_id'] ) )
				{
					$('space_info_attach_' + this.json['attach_rel_id'] ).update( ipb.lang['used_space'].gsub(/\[used\]/, this.json['attach_stats']['space_used_human']).gsub(/\[total\]/, this.json['attach_stats']['total_space_allowed_human']) );
				}
			}
		}
		
		Debug.write( "ips.attach.js: iFrame is ready" );
	},
	
	_setJSON: function( json )
	{
		Debug.write( "ips.attach.js: Got JSON from the iFrame" );
	}
});

//==============================================================

_attach.prototype.attachSWF = Class.create({
	options: [],
	boxes: [], /* simple array to hold file index => list id relationship, because im lazy :) */
	
	initialize: function( id, options, wrapper, template )
	{
		this.id = id;
		this.wrapper = wrapper;
		this.template = template;

		this.options = Object.extend({
			upload_url: 				'',
			file_post_name: 			'FILE_UPLOAD',
			file_types:					'*.*',
			file_types_description:		ipb.lang['att_select_files'],
			file_size_limit: 			"10 MB",
			file_upload_limit: 			0,
			file_queue_limit: 			10,
			flash_color: 				ipb.vars['swf_bgcolor'] || '#FFFFFF',
			custom_settings: 			{},
			post_params: 				{ 's': ipb.vars['session_id'] }
		}, arguments[1] || {});
		
		if( this.options.upload_url.blank() ){ Debug.error( "(ID " + id + ") No upload URL" ); return false; }
		
		// Update the text of the button to indicate more than one can be attached
		try {
			$('add_files_' + this.id).value = ipb.lang['click_to_attach'];
		} catch(err) {
			Debug.write( err );
		}
		
		// Set up SWFU
		try {
			var swfu;
			
				var settings = {
					upload_url: 			this.options.upload_url,
					flash_url: 				ipb.vars['swfupload_swf'],
					file_post_name: 		this.options.file_post_name,
					file_types: 			this.options.file_types,
					file_types_description: this.options.file_types_description,
					file_size_limit: 		this.options.file_size_limit,
					file_upload_limit:  	this.options.file_upload_limit,
					file_queue_limit: 		this.options.file_queue_limit,
					custom_settings: 		this.options.custom_settings,
					post_params: 			this.options.post_params,
					debug: 					ipb.vars['swfupload_debug'],
					
					// ---- BUTTON SETTINGS ----
					button_placeholder_id: 			'buttonPlaceholder',
					button_width: 					$('add_files_' + this.id).getWidth(),
					button_height: 					30,
					button_window_mode: 			SWFUpload.WINDOW_MODE.TRANSPARENT,
					button_cursor: 					SWFUpload.CURSOR.HAND,
				
					// ---- EVENTS ---- 
					upload_error_handler: 			this._uploadError.bind(this),
					upload_start_handler: 			this._uploadStart.bind(this),
					upload_success_handler: 		this._uploadSuccess.bind(this),
					upload_complete_handler: 		this._uploadComplete.bind(this),
					upload_progress_handler: 		this._uploadProgress.bind(this),
					file_dialog_complete_handler: 	this._fileDialogComplete.bind(this),
					file_queue_error_handler: 		this._fileQueueError.bind(this),
					queue_complete_handler: 		this._queueComplete.bind(this),
					file_queued_handler: 			this._fileQueued.bind(this)
				}
				
				swfu = new SWFUpload( settings );
			
			// Add events
			/*if( $('add_files_' + this.id ) )
			{
				$('add_files_' + this.id ).observe('click', this.showFilesDialog.bindAsEventListener( this ) );
			}*/
			
			this.obj = swfu;
			
			// Now we have to get existing files
			var getExisting	=	ipb.vars['base_url'] + "app=core&module=attach&section=attach&do=attach_upload_show&attach_rel_module=" +
 				   				options['attach_rel_module'] + "&attach_rel_id=" + options['attach_rel_id'] + "&attach_post_key=" + 
				  				options['attach_post_key'] + "&forum_id=" + options['forum_id'] + "&attach_id=" + id + '&secure_key=' + ipb.vars['secure_hash'] + '&fetch_all=1';

			// Send request to get the uploads
			new Ajax.Request( getExisting,
							{
								method: 'get',
								evalJSON: 'force',
								onSuccess: function(t)
								{
									if( Object.isUndefined( t.responseJSON ) ){ alert( ipb.lang['action_failed'] ); return; }

									if( t.responseJSON.current_items )
									{
										ipb.attach._buildBoxes( t.responseJSON.current_items );
									}
								}
							});

			this.obj.onmouseover	= $('SWFUpload_0').focus();

			Debug.write( "ips.attach.js: (ID " + this.id + ") Created uploader");
			return true;
		}
		catch(e)
		{
			Debug.error( "ips.attach.js: (ID " + this.id + ") " + e );
			return false;
		}
	},
	
	/* ------------------------------ */
	/**
	 * Removes an upload from the list (note: actual removing of file is done in the wrapper object
	 * 
	 * @param	{int}	attachid		Server ID of the attachment
	 * @param	{int}	fileindex		The file index that SWFU is using
	*/
	removeUpload: function( attachid, fileindex, fileinfo )
	{
		// Remove box
		new Effect.Fade( $( this.boxes[ fileindex ] ), { duration: 0.4 } );
		
		// Remove reference
		this.boxes[ fileindex ] = null;
		
		if( $('space_info_' + this.id ) && ipb.lang['used_space'] )
		{
			$('space_info_' + this.id ).update( ipb.lang['used_space'].gsub(/\[used\]/, fileinfo['attach_stats']['space_used_human']).gsub(/\[total\]/, fileinfo['attach_stats']['total_space_allowed_human']) );
		}
		
		Debug.write( "ips.attach.js: (ID " + this.id + ", removeUpload) Attach ID: " + attachid + ", File index: " + fileindex );
	},
	
	/* ------------------------------ */
	/**
	 * Updates the info string for an upload
	 * 
	 * @param	{object}	file		The file object from SWFU
	 * @param	{string}	msg			The message to set
	*/
	_updateInfo: function( file, msg )
	{
		$( this.boxes[ file ] ).select('.info')[0].update( msg );
	},
	
	/* ------------------------------ */
	/**
	 * Sets a CSS class on the box depending on status
	 * 
	 * @param	{object}	file		The file object from SWFU
	 * @param	{string}	type		Status to set
	*/
	_setStatus: function( file, type )
	{
		// Remove old statuses
		['complete', 'in_progress', 'error'].each( function( cName ){ $( this.boxes[ file ] ).removeClassName( cName ); }.bind( this ) );
		
		$( this.boxes[ file ] ).addClassName( type );
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
		
		if( !Object.isUndefined( ipb.lang[ msg ] ) )
		{
			return ipb.lang[ msg ];
		}
		else
		{
			return ipb.lang['silly_server'];
		}
		
		/*switch( msg )
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
				return ipb.lang['silly_server'] + " " + msg;
				break;
		}*/
		
	},
	
	/* ------------------------------ */
	/**
	 * Builds the list row for each upload
	 * 
	 * @param	{object}	file	The file object passed from SWFU
	*/
	_buildBox: function( file )
	{
		temp = this.template.gsub(/\[id\]/, this.id + '_' + file.index).gsub(/\[name\]/, file.name);
		this.boxes[ file.index ] = 'ali_' + this.id + '_' + file.index;
		
		$( this.wrapper ).insert( temp );
		
		new Effect.Appear( $( this.boxes[ file.index ] ), { duration: 0.3 } );
		this._updateInfo( file.index, ipb.global.convertSize( file.size ) + "bytes" );
	},
	
	_queueComplete: function( numFiles )
	{
		Debug.write( "ips.attach.js: (ID " + this.id + ", " + numFiles + " finished uploading");
	},
	
	_fileQueued: function( file )
	{
		this._buildBox( file );
		$( this.boxes[ file.index ] ).addClassName('in_progress');
		this._updateInfo( file.index, ipb.lang['pending'] );
	},
	
	/* ------------------------------ */
	/**
	 * Event handler for fileQueueError
	 * 
	 * @param	{object}	file			The file object
	 * @param	{int}		errorCode		Error code
	 * @param	{string}	message			Message from SWFUpload
	*/
	_fileQueueError: function( file, errorCode, message )
	{
		var msg;
		
		try {
			if( errorCode === SWFUpload.QUEUE_ERROR.QUEUE_LIMIT_EXCEEDED ){
				alert( ipb.lang['upload_queue'] + message );
				return false;
			}
		
			switch (errorCode) {
				case SWFUpload.QUEUE_ERROR.FILE_EXCEEDS_SIZE_LIMIT:
					msg = ipb.lang['upload_too_big'];
					break;
				case SWFUpload.QUEUE_ERROR.ZERO_BYTE_FILE:
					msg = ipb.lang['upload_no_file'];
					break;
				case SWFUpload.QUEUE_ERROR.INVALID_FILETYPE:
					msg = ipb.lang['invalid_mime_type'];
					break;
				default:
					if( file !== null ) {
						msg = ipb.lang['upload_failed'] + " " + errorCode;
					}
					break;
			}
			
			this._setStatus( file.index, 'error' );
			this._updateInfo( file.index, ipb.lang['upload_skipped'] + " (" + msg + ")" );
			
			Debug.write( "ips.attach.js: (ID " + this.id + ", fileQueueError) " + errorCode + ": " + message );
		}
		catch( err )
		{
			Debug.write( "ips.attach.js: (ID " + this.id + ", fileQueueError) " + errorCode + ": " + message );
		}
	},
	
	/* ------------------------------ */
	/**
	 * Event handler for uploadError
	 * 
	 * @param	{object}	file			The file object
	 * @param	{int}		errorCode		The error code returned
	 * @param	{string}	message			The message returned
	*/
	_uploadError: function( file, errorCode, message )
	{
		var msg;
		
		switch( errorCode )
		{
			case SWFUpload.UPLOAD_ERROR.HTTP_ERROR:
				msg = ipb.lang['error'] + message;
				break;
			case SWFUpload.UPLOAD_ERROR.UPLOAD_FAILED:
				msg = message;
				break;
			case SWFUpload.UPLOAD_ERROR.IO_ERROR:
				msg = ipb.lang['error'] + " IO";
				break;
			case SWFUpload.UPLOAD_ERROR.SECURITY_ERROR:
				msg = ipb.lang['error_security'];
				break;
			case SWFUpload.UPLOAD_ERROR.UPLOAD_LIMIT_EXCEEDED:
				msg = ipb.lang['upload_limit_hit'];
				break;
			case SWFUpload.UPLOAD_ERROR.FILE_VALIDATION_FAILED:
				msg = ipb.lang['invalid_mime_type'];
				break;
			/*case SWFUpload.UPLOAD_ERROR.FILE_CANCELLED:
				// If there aren't any files left (they were all cancelled) disable the cancel button
				if (this.getStats().files_queued === 0) {
					document.getElementById(this.customSettings.cancelButtonId).disabled = true;
				}
				progress.setStatus("Cancelled");
				progress.setCancelled();
				break;
			case SWFUpload.UPLOAD_ERROR.UPLOAD_STOPPED:
				progress.setStatus("Stopped");
				break;*/
			default:
				msg = ipb.lang['error'] + ": " + errorCode;
				break;
		}
		
		this._setStatus( file.index, 'error' );
		this._updateInfo( file.index, ipb.lang['upload_skipped'] + " (" + msg + ")" );
		
		Debug.write( "ips.attach.js: (ID " + this.id + ", uploadError) " + errorCode + ": " + message );
		return false;
	},
	
	/* ------------------------------ */
	/**
	 * Event handler for uploadStart
	 * 
	 * @param	{object}	file			The file object
	*/
	_uploadStart: function( file )
	{
		ipb.attach.startedUploading( this.id );
		
		Debug.write( "ips.attach.js: (ID " + this.id + ", uploadStart) " );
		/*this._buildBox( file );
		
		$( this.boxes[ file.index ] ).addClassName('in_progress');
		
		this._updateInfo( file.index, ipb.lang['uploading'] );*/
	},
	
	/* ------------------------------ */
	/**
	 * Event handler for uploadSuccess
	 * 
	 * @param	{object}	file			The file object
	 * @param	{string}	serverData		Data from the server (should be JSON)
	*/
	_uploadSuccess: function( file, serverData )
	{
		if( !serverData.isJSON() ){ this._setStatus( file.index, 'error' ); this._updateInfo( file.index, ipb.lang['silly_server'] ); }
		returnedObj = serverData.evalJSON();
		
		if( Object.isUndefined( returnedObj ) ){ this._setStatus( file.index, 'error' ); this._updateInfo( file.index, ipb.lang['silly_server'] ); }
		
		// Error?
		if( returnedObj.is_error == 1 )
		{
			msg = this._determineServerError( returnedObj.msg );
			this._setStatus( file.index, 'error' );
			this._updateInfo( file.index, msg );
			return false;
		}
			
		if( $('space_info_' + this.id ) && ipb.lang['used_space'] )
		{
			//$('space_info_' + this.id ).update( "Used <strong>" + returnedObj.attach_stats.space_used_human + "</strong> of <strong>" + returnedObj.attach_stats.total_space_allowed_human + "</strong>" );
			$('space_info_' + this.id ).update( ipb.lang['used_space'].gsub(/\[used\]/, returnedObj.attach_stats.space_used_human).gsub(/\[total\]/, returnedObj.attach_stats.total_space_allowed_human) );
		}
		
		// IMAGE RESIZING
		if( returnedObj.current_items[ returnedObj.insert_id ][ 3 ] == 1 )
		{
			tmp = returnedObj.current_items[ returnedObj.insert_id ];
			
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
			
			$( this.boxes[ file.index ] ).select('.img_holder')[0].insert( thumb );
			new Effect.Appear( $( thumb ), { duration: 0.4 } );
		}
		
		// SET STATUS & INFO
		this._setStatus( file.index, 'complete' );
		this._updateInfo( file.index, ipb.lang['upload_done'].gsub( /\[total\]/, ipb.global.convertSize( file.size ) ) );
		
		// Write attachID to the object for easy retreival later
		$( this.boxes[ file.index ] ).writeAttribute( 'attachid', returnedObj.insert_id );
		
		// Add event handlers
		$( this.boxes[ file.index ] ).select('.delete')[0].observe( 'click', ipb.attach.removeUpload );
		
		ipb.attach.finishedUploading( this.id, file.index );
		
		Debug.write( "ips.attach.js: (ID " + this.id + ", uploadSuccess) " + serverData );
	},
	
	/* ------------------------------ */
	/**
	 * Event handler for uploadComplete
	 * 
	 * @param	{object}	file			The file object
	*/
	_uploadComplete: function( file )
	{
		ipb.attach.finishedUploading( this.id );
		
		progress_bar = $( this.boxes[ file.index ] ).select('.progress_bar span')[0];
		progress_bar.setStyle( "width: 100%" );
		new Effect.Fade( $( this.boxes[ file.index ] ).select('.progress_bar')[0], { duration: 0.6 } );
				
		Debug.write( "ips.attach.js: (ID " + this.id + ", uploadComplete)" );
	},
	
	/* ------------------------------ */
	/**
	 * Event handler for uploadProgress
	 * 
	 * @param	{object}	file			The file object
	 * @param	{int}		bytesLoaded		Number of bytes loaded so far
	 * @param	{int}		bytesTotal		Total size of file
	*/
	_uploadProgress: function( file, bytesLoaded, bytesTotal)
	{
		var percent = Math.ceil((bytesLoaded / bytesTotal) * 100);
		
		progress_bar = $( this.boxes[ file.index ] ).select('.progress_bar span')[0];
		progress_bar.setStyle( "width: " + percent + "%" ).update( percent + "%" );
		
		this._setStatus( file.index, 'in_progress' );
		this._updateInfo( file.index, ipb.lang['upload_progress'].gsub( /\[done\]/, ipb.global.convertSize( bytesLoaded ) ).gsub( /\[total\]/, ipb.global.convertSize( bytesTotal ) ) )
		
		Debug.write( "ips.attach.js: (ID " + this.id + ", uploadProgress)" );
	},
	
	/* ------------------------------ */
	/**
	 * Event handler for fileDialogComplete (called when used finishes selecting files
	 * 
	 * @param	{int}		number			Number of files selected
	 * @param	{int}		queued			Number in the queue
	*/
	_fileDialogComplete: function( number, queued )
	{
		Debug.write( "ips.attach.js: (ID " + this.id + ", fileDialogComplete) Number: " + number + ", Queued: " + queued );
		this.obj.startUpload();
	}
	
	/* ------------------------------ */
	/**
	 * Event handler for the Add Files button
	 * 
	 * @param	{event}		e		The event
	*/
	/*showFilesDialog: function(e)
	{
		Debug.write( "ips.attach.js: Adding files" );
		this.obj.selectFile();
	}*/
});

/*
Copyright (c) 2007, James Auldridge
All rights reserved.
Code licensed under the BSD License:
  http://www.jaaulde.com/license.txt

Version 1.0

Change Log:
	* 09 JAN 07 - Version 1.0 written

*/
//Preparing namespace
var jimAuld = window.jimAuld || {};
jimAuld.utils = jimAuld.utils || {};
jimAuld.utils.flashsniffer = {
	lastMajorRelease: 10,
	installed: false,
	version: null,
	detect: function()
	{
		var fp,fpd,fAX;
		if (navigator.plugins && navigator.plugins.length)
		{
			fp = navigator.plugins["Shockwave Flash"];
			if (fp)
			{
				jimAuld.utils.flashsniffer.installed = true;
				if (fp.description)
				{
					fpd = fp.description;
					jimAuld.utils.flashsniffer.version = fpd.substr( fpd.indexOf('.')-2, 2 ).strip();
					Debug.write( jimAuld.utils.flashsniffer.version );
				}
			}
			else
			{
				jimAuld.utils.flashsniffer.installed = false;
			}
			if (navigator.plugins["Shockwave Flash 2.0"]){
				jimAuld.utils.flashsniffer.installed = true;
				jimAuld.utils.flashsniffer.version = 2;
			}
		}
		else if (navigator.mimeTypes && navigator.mimeTypes.length)
		{
			fp = navigator.mimeTypes['application/x-shockwave-flash'];
			if (fp && fp.enabledPlugin)
			{
				jimAuld.utils.flashsniffer.installed = true;
			}
			else
			{
				jimAuld.utils.flashsniffer.installed = false;
			}
		}
		else
		{
			for(var i=jimAuld.utils.flashsniffer.lastMajorRelease;i>=2;i--)
			{
				try
				{
					fAX = new ActiveXObject("ShockwaveFlash.ShockwaveFlash."+i);
					jimAuld.utils.flashsniffer.installed = true;
					jimAuld.utils.flashsniffer.version = i;
					break;
				}
				catch(e)
				{
				}
			}
			if(jimAuld.utils.flashsniffer.installed == null){
				try
				{
					fAX = new ActiveXObject("ShockwaveFlash.ShockwaveFlash");
					jimAuld.utils.flashsniffer.installed = true;
					jimAuld.utils.flashsniffer.version = 2;
				}
				catch(e)
				{
				}
			}
			if(jimAuld.utils.flashsniffer.installed == null)
			{
				jimAuld.utils.flashsniffer.installed = false;
			}
			fAX = null;
		}
		
	},
	isVersion: function(exactVersion)
	{
		return (jimAuld.utils.flashsniffer.version!=null && jimAuld.utils.flashsniffer.version==exactVersion);
	},
	isLatestVersion: function()
	{
		return (jimAuld.utils.flashsniffer.version!=null && jimAuld.utils.flashsniffer.version==jimAuld.utils.flashsniffer.lastMajorRelease);
	},
	meetsMinVersion: function(minVersion)
	{
		return (jimAuld.utils.flashsniffer.version!=null && jimAuld.utils.flashsniffer.version>=minVersion);
	}
};
jimAuld.utils.flashsniffer.detect();

