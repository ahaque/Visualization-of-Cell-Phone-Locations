/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.editor.js - Editor class					*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier (based on Matt's code)	*/
/************************************************/

var _editor = window.IPBoard;

var isRTL = ( isRTL ) ? isRTL : false;

// =============================================================================================================//
// Class for RTE EDITOR which is inherited by main Editor class
_editor_rte = Class.create({
	_identifyType: function()
	{
		Debug.write( "(Editor " + this.id + ") This is the RTE class" );
	},
	togglesource_pre_show_html: function()
	{
	},
	togglesource_post_show_html: function()
	{
	},
	editor_write_contents: function( text, do_init )
	{
		if ( text.blank() && Prototype.Browser.Gecko )
		{
			text = '<br />';
		}
		
		if ( this.editor_document && this.editor_document.initialized )
		{
			this.editor_document.body.innerHTML = text;
		}
		else
		{
			if ( do_init )
			{
				this.editor_document.designMode = 'on';
			}

			this.editor_document = this.editor_window.document;
			this.editor_document.open( 'text/html', 'replace' );
			this.editor_document.write( this.ips_frame_html.replace( '{:content:}', text ) );
			this.editor_document.close();
			
			if ( do_init )
			{
				this.editor_document.body.contentEditable = true;
				this.editor_document.initialized          = true;
			}
		}
	},
	removeformat: function(e)
	{
		this.apply_formatting( 'unlink'      , false, false );
		this.apply_formatting( 'removeformat', false, false );
		/*this.apply_formatting( 'killword', false, false );*/
		
		var text = this.get_selection();

		if ( text )
		{
			text = this.strip_html( text );
			text = this.strip_empty_html( text );
			text = text.replace( /\r/g, "" );
			text = text.replace( /\n/g, "<br />" );
			text = text.replace( /<!--(.*?)-->/g, "" );
			text = text.replace( /&lt;!--(.*?)--&gt;/g, "" );

			this.insert_text( text );
		}
	},
	editor_get_contents: function()
	{
		return this.editor_document.body.innerHTML;
	},
	editor_set_content: function( init_text )
	{ 
		if( $( this.id + '_iframe' ) )
		{
			this.editor_box = $( this.id + '_iframe' );
		}
		else
		{
			// Create iframe
			var iframe = new Element('iframe', { 'id': this.id + '_iframe', 'tabindex': 0 } );
			
			if( Prototype.Browser.IE && window.location.protocol == 'https:' )
			{
				iframe.writeAttribute( 'src', this.options.file_path + '/index.html' );
			}
			
			// Insert into DOM
			this.items['text_obj'].up().insert( iframe );
			this.editor_box = iframe;
		}
		
		if( !Prototype.Browser.IE )
		{
			this.editor_box.setStyle( 'border: 1px inset' );
		}
		else
		{
			// Bug #17772
			if( !Object.isUndefined( init_text ) )
			{
				init_text = init_text.replace( /&sect/g, "&amp;sect");
			}
		}
		
		//-----------------------------------------
		// Is there a height in the cookies?
		//-----------------------------------------
		
		var test_height = ipb.Cookie.get( 'ips_rte_height' );
		
		/*Debug.write( "Height is " + test_height );*/
		
		if ( Object.isNumber( test_height ) && test_height > 50 )
		{
			this.items['text_obj'].setStyle( { height: test_height + 'px' } );
			Debug.write( "Set text_obj height to " + test_height );
		}
		
		// Set up
		var tobj_dims = this.items['text_obj'].getDimensions();
		
		if( Object.isUndefined( tobj_dims ) || tobj_dims['height'] == 0 )
		{
			tobj_dims['height'] = 250;
		}
		
		//Debug.write( tobj_dims );
		
		/*Debug.write( this.items['text_obj'].getDimensions()['height'] );*/
		
		this.editor_box.setStyle( { width: 		'100%',
									height: 	tobj_dims.height + 'px',
									className: 	this.items['text_obj'].className
								 });
								
		this.items['text_obj'].hide();
		
		//---------
		
		this.editor_window   = this.editor_box.contentWindow;
		this.editor_document = this.editor_window.document;
		
		this.editor_write_contents( (Object.isUndefined( init_text ) || ! init_text ?  this.items['text_obj'].value : init_text), true );

		this.editor_document.editor_id = this.editor_id;
		this.editor_window.editor_id   = this.editor_id;
		this.editor_window.has_focus   = false;
		
		//-----------------------------------------
		// Kill tags
		//-----------------------------------------
		
		//document.getElementById( this.editor_id + '_cmd_justifyfull' ).style.display  = 'none';
	},
	apply_formatting: function(cmd, dialog, argument)
	{
		dialog   = ( Object.isUndefined( dialog ) ? false : dialog);
		argument = ( Object.isUndefined( argument ) ? true  : argument);
		
		if ( Prototype.Browser.IE && this.forum_fix_ie_newlines )
		{
			if ( cmd == 'justifyleft' || cmd == 'justifycenter' || cmd == 'justifyright' )
			{
				var _a  = cmd.replace( "justify", "" );

				this.wrap_tags_lite( "[" + _a + "]", "[/" + _a + "]" );
				return true;
			}
			else if ( cmd == 'outdent' || cmd == 'indent' || cmd == 'insertorderedlist' || cmd == 'insertunorderedlist' ) 
			{
				this.editor_check_focus();

				var sel = this.editor_document.selection;
				var ts  = this.editor_document.selection.createRange();

				var t   = ts.htmlText.replace(/<p([^>]*)>(.*)<\/p>/i, '$2');

				if ( (sel.type == "Text" || sel.type == "None") )
				{
					ts.pasteHTML( t + "<p />\n" );
				}
				else
				{
					this.editor_document.body.innerHTML += "<p />";
				}
			}
		}

		//alert( "Here" );
		/*Debug.write( "Apply formatting selection is: " + this.get_selection() + ", cmd is " + cmd );*/
		
		if( Prototype.Browser.IE && this._ie_cache != null )
		{
			this.editor_check_focus();
			this._ie_cache.select();
		}
		
		this.editor_document.execCommand( cmd, dialog, argument );
		return false;
	},
	get_selection: function()
	{
		var rng = this._ie_cache ? this._ie_cache : this.editor_document.selection.createRange();
		
		if ( rng.htmlText )
		{
			return rng.htmlText;
		}
		else
		{
			var rtn = '';
			
			for (var i = 0; i < rng.length; i++)
			{
				rtn += rng.item(i).outerHTML;
			}
		}
		
		return rtn;
	},
	editor_set_functions: function()
	{
		//Debug.write( this.editor_document );
		Event.observe( this.editor_document, 'mouseup', this.events.editor_document_onmouseup.bindAsEventListener( this ) );
		Event.observe( this.editor_document, 'keyup', this.events.editor_document_onkeyup.bindAsEventListener( this ) );
		Event.observe( this.editor_document, 'keydown', this.events.editor_document_onkeydown.bindAsEventListener( this ) );
		
		
		Event.observe( this.editor_window, 'blur', this.events.editor_window_onblur.bindAsEventListener( this ) );
		Event.observe( this.editor_window, 'focus', this.events.editor_window_onfocus.bindAsEventListener( this ) );
	},
	set_context: function( cmd )
	{
		// Showing HTML?
		if ( this._showing_html )
		{
			return false;
		}
		
		this.button_update.each( function(item)
		{
			//Debug.write( this.id + '_cmd_' + item );
			var obj = $( this.id + '_cmd_' + item );
			
			if( obj != null )
			{
				try {
					var state = new String( this.editor_document.queryCommandState( item ) );
					
					if( obj.readAttribute('state') != state )
					{
						obj.writeAttribute( 'state', new String( state ) );
						this.set_button_context( obj, ( obj.readAttribute('cmd') == cmd ? 'mouseover' : 'mouseout' ) );
					}
				}
				catch( error )
				{
					Debug.write( "#1 " + error );
				}
			}
		}.bind(this) );
		
		//-----------------------------------------
		// Check and set font context
		//-----------------------------------------
		
		this.button_set_font_context();
		
		//-----------------------------------------
		// Check and set size context
		//-----------------------------------------
		
		this.button_set_size_context();
	},
	button_set_font_context: function( font_state )
	{	
		changeto = '';
		
		// Showing HTML?
		if ( this._showing_html )
		{
			return false;
		}
		
		if( this.items['buttons']['fontname'] )
		{
			if( Object.isUndefined( font_state ) ){
				font_state = this.editor_document.queryCommandValue('fontname') || '';
			}
			
			if( font_state.blank() ){
				if ( !Prototype.Browser.IE && window.getComputedStyle )
				{
					font_state = this.editor_document.body.style.fontFamily;
				}
			} else if( font_state == null ){
				font_state = '';
			}
			
			if( font_state != this.font_state )
			{
				this.font_state	= font_state;
				var fontword	= font_state;
				var commapos 	= fontword.indexOf(",");

				if( commapos != -1 ){
					fontword = fontword.substr(0, commapos);
				}
				
				fontword = fontword.toLowerCase();
				changeto = '';
				
				ipb.editor_values.get('primary_fonts').any( function(font){
					if( font.value.toLowerCase() == fontword ){
						changeto = font.value;
						return true;
					} else {
						return false;
					}					
				});
				
				changeto = ( changeto == '' ) ? this.fontoptions['_default'] : changeto;
				
				this.items['buttons']['fontname'].update( changeto );
			}
		}
	},		
	
	button_set_size_context: function( size_state )
	{		
		if( this.items['buttons']['fontsize'] )
		{
			if( Object.isUndefined( size_state ) ){
				size_state = this.editor_document.queryCommandValue('fontsize');
			}
			
			if( size_state == null || size_state == ''  )
			{
				if ( Prototype.Browser.Gecko )
				{
					size_state = this.convert_size( this.editor_document.body.style.fontSize, 0 );
					
					if ( ! size_state )
					{
						size_state = '2';
					}
				}
			}
			
			changeto = '';
			if ( size_state != this.size_state )
			{
				this.size_state = size_state;
				
				ipb.editor_values.get('font_sizes').any( function(size){
					if( parseInt( size ) == parseInt( this.size_state ) ){
						changeto = size;
						return true;
					} else {
						return false;
					}
				}.bind(this));
				
				changeto = ( changeto == '' ) ? this.sizeoptions['_default'] : changeto;
				
				this.items['buttons']['fontsize'].update( changeto );
			}
		}
	},
		
	insert_text: function(text)
	{
		//Debug.write( this.editor_document );
		this.editor_check_focus();

		if ( typeof( this.editor_document.selection )    != 'undefined'
				  && this.editor_document.selection.type != 'Text'
			      && this.editor_document.selection.type != 'None' )
		{
			this.editor_document.selection.clear();
		}
		
		/*if( this.get_selection() ){
			var sel = this._ie_cache;
		} else {
			var sel = this.editor_document.selection.createRange();
		}*/
		
		var sel = this._ie_cache ? this._ie_cache : this.editor_document.selection.createRange();
		
		sel.pasteHTML(text);
		sel.select();		
		this._ie_cache = null;
	},
	
	insert_emoticon: function( emo_id, emo_image, emo_code, event )
	{
		try
		{
			// INIT
			var _emo_url  = ipb.vars['emoticon_url'] + "/" + emo_image;
			var _emo_html = ' <img src="' + _emo_url + '" class="bbc_emoticon" alt="' + this.unhtmlspecialchars( emo_code ) + '" />';
			
			this.wrap_tags_lite( " " + _emo_html, " ");
		}
		catch( error )
		{
			Debug.write( "#2 " + error );
			//alert( error );
		}
		
		/*if ( this.emoticon_window_id != '' && typeof( this.emoticon_window_id ) != 'undefined' )
		{
			this.emoticon_window_id.focus();
		}*/
	},
	
	togglesource: function( e, update )
	{
		Event.stop(e);
		
		// Already showing?
		if( this._showing_html )
		{
			if( update )
			{
				this.editor_document.initialized = false;
				this.editor_write_contents( $( this.id + '_htmlsource' ).value, true );
				//this.editor_document.body.innerHTML = $( this.id + '_htmlsource' ).value;
			}
			
			$( this.editor_box ).show();
			$( this.items['controls'] ).show();
			
			// Remove source editor
			$( this.id + '_htmlsource' ).remove();
			$( this.id + '_ts_controls' ).remove();
			
			// Post process
			//this.togglesource_post_show_html();
			this._showing_html = false;	
			// Set context
			this.editor_check_focus();
			//this.set_context();
		}
		else
		{
			this._showing_html = true;
			this.togglesource_pre_show_html();
			
			// Create a new textarea
			var textarea = new Element( 'textarea', { id: this.id + '_htmlsource', tabindex: 3 } );
			textarea.className = this.items['text_obj'].className;
			var dims = this.items['text_obj'].getDimensions();
			
			/*textarea.setStyle('width: ' + dims.width + 'px; height: ' + dims.height + 'px;');*/
			
			textarea.value = this.clean_html( this.editor_get_contents() );
			
			// Build controls
			var controlbar = ipb.editor_values.get('templates')['togglesource'].evaluate( { id: this.id } );
			
			// Insert into page
			$( this.items['text_obj'] ).insert( { after: textarea } );
			$( textarea ).insert( { after: controlbar } );
			
			// Hook up events
			$( this.id + '_ts_update' ).writeAttribute('cmd', 'togglesource').writeAttribute('editor_id', this.id).observe('click', this.togglesource.bindAsEventListener( this, 1 ) );
			$( this.id + '_ts_cancel' ).writeAttribute('cmd', 'togglesource').writeAttribute('editor_id', this.id).observe('click', this.togglesource.bindAsEventListener( this, 0 ) );
			
			// Hide editor
			$( this.items['controls'] ).hide();
			$( this.editor_box ).hide();
			
			// Set context
			this.editor_check_focus();
			//this.set_context();
		}	
		
	},
	
	update_for_form_submit: function()
	{
		Debug.write("Updating for submit");
		this.items['text_obj'].value = this.editor_get_contents();
		return true;
	}
});


// =============================================================================================================//
// Class for STANDARD EDITOR which is inherited by main Editor class
_editor_std = Class.create({
	_identifyType: function()
	{
		Debug.write( "(Editor " + this.id + ") This is the STD class" );
	},
	editor_set_content: function( init_text )
	{
		var iframe = this.items['text_obj'].up().down('iframe', 0);
		
		if ( !Object.isUndefined( iframe ) )
		{
			var iframeDims = iframe.getDimensions();
			$( this.items['text_obj'] ).setStyle( { 'width': iframeDims.width, 'height': iframeDims.height } ).show();
			$( iframe ).setStyle( 'width: 0px; height: 0px; border: none;' );
		}

		this.editor_window   = this.items['text_obj'];
		this.editor_document = this.items['text_obj'];
		this.editor_box      = this.items['text_obj'];

		if( !Object.isUndefined( init_text ) )
		{
			this.editor_write_contents( init_text );
		}

		this.editor_document.editor_id = this.id;
		this.editor_window.editor_id   = this.id;
		
		// Hide those pesky buttons we dont need
		if( !Prototype.Browser.IE && $( this.id + '_cmd_spellcheck' ) ){
			$( this.id + '_cmd_spellcheck').hide();
		}
		
		if( $(this.id + '_cmd_removeformat') ){
			$( this.id + '_cmd_removeformat' ).hide();
		}
		
		if( $(this.id + '_cmd_togglesource' ) ){
			$( this.id + '_cmd_togglesource' ).hide();
		}
		
		if( $(this.id + '_cmd_justifyfull' ) ){
			$( this.id + '_cmd_justifyfull' ).hide();
		}
		
		if( $(this.id + '_cmd_outdent' ) ){
			$( this.id + '_cmd_outdent' ).hide();
		}
		
		if( $(this.id + '_cmd_switcheditor' ) ){
			$( this.id + '_cmd_switcheditor' ).hide();
		}
	},
	editor_write_contents: function(text)
	{
		this.items['text_obj'].value = text;
	},
	editor_get_contents: function()
	{
		return this.editor_document.value;
	},
	apply_formatting: function(cmd, dialog, argument)
	{
		/*Debug.write("Click");*/
		switch (cmd)
		{
			case 'bold':
			case 'italic':
			case 'underline':
			{
				this.wrap_tags(cmd.substr(0, 1), false);
				return;
			}
			case 'justifyleft':
			case 'justifycenter':
			case 'justifyright':
			{
				this.wrap_tags(cmd.substr(7), false);
				return;
			}
			case 'indent':
			{
				this.wrap_tags(cmd, false);
				return;
			}
			case 'createlink':
			{
				var sel = this.get_selection();
				
				if (sel)
				{
					this.wrap_tags('url', argument);
				}
				else
				{
					this.wrap_tags('url', argument, argument);
				}
				return;
			}
			case 'fontname':
			{
				this.wrap_tags('font', argument);
				return;
			}
			case 'fontsize':
			{
				this.wrap_tags('size', argument);
				return;
			}
			case 'forecolor':
			{
				this.wrap_tags('color', argument);
				return;
			}			
			case 'backcolor':
			{
				this.wrap_tags('background', argument);
				return;
			}
			case 'insertimage':
			{
				this.wrap_tags('img', false, argument);
				return;
			}			
			case 'strikethrough':
			{
				this.wrap_tags('s', false);
				return;
			}			
			case 'superscript':
			{
				this.wrap_tags('sup', false);
				return;
			}			
			case 'subscript':
			{
				this.wrap_tags('sub', false);
				return;
			}
			case 'removeformat':
			return;
		}
	},
	editor_set_functions: function()
	{
		Event.observe( this.editor_document, 'keypress', this.events.editor_document_onkeypress.bindAsEventListener(this) );
		Event.observe( this.editor_window, 'focus', this.events.editor_window_onfocus.bindAsEventListener( this ) );
		Event.observe( this.editor_window, 'blur', this.events.editor_window_onblur.bindAsEventListener( this ) );
	},
	set_context: function()
	{
		//Debug.write('Set context');
	},
	get_selection: function()
	{
		if ( !Object.isUndefined( this.editor_document.selectionStart ) )
		{
			return this.editor_document.value.substr(this.editor_document.selectionStart, this.editor_document.selectionEnd - this.editor_document.selectionStart);
		}
		else if ( ( document.selection && document.selection.createRange ) || this._ie_cache )
		{
			return this._ie_cache ? this._ie_cache.text : document.selection.createRange().text;
		}
		else if ( window.getSelection )
		{
			return window.getSelection() + '';
		}
		else
		{
			return false;
		}
	},
	insert_text: function(text)
	{
		this.editor_check_focus();

		if ( !Object.isUndefined( this.editor_document.selectionStart ) )
		{
			var open = this.editor_document.selectionStart + 0;
			var st   = this.editor_document.scrollTop;
			var end  = open + text.length;
			
			/* Opera doesn't count the linebreaks properly for some reason */
			if( Prototype.Browser.Opera )
			{
				var opera_len = text.match( /\n/g );

				try
				{
					end += parseInt(opera_len.length);
				}
				catch(e)
				{
					Debug.write( "#3 " + e );
				}
			}
			
			this.editor_document.value = this.editor_document.value.substr(0, this.editor_document.selectionStart) + text + this.editor_document.value.substr(this.editor_document.selectionEnd);

			// Don't adjust selection if we're simply adding <b></b>, etc
			if ( ! text.match( new RegExp( "\\" + this.open_brace + "(\\S+?)" + "\\" + this.close_brace + "\\" + this.open_brace + "/(\\S+?)" + "\\" + this.close_brace ) ) )
			{
				this.editor_document.selectionStart = open;
				this.editor_document.selectionEnd   = end;
				this.editor_document.scrollTop      = st;
			}
			else
			{
				// Only firefox seems to need this...
				if( Prototype.Browser.Gecko ){
					this.editor_document.scrollTop      = st;
				}
			}
			
			this.editor_document.setSelectionRange( end, end );
			Debug.write("Insert 1");
		}
		else if ( ( document.selection && document.selection.createRange ) || this._ie_cache )
		{
			var sel  = this._ie_cache ? this._ie_cache : document.selection.createRange();
			sel.text = text.replace(/\r?\n/g, '\r\n');
			sel.select();
			Debug.write("Insert 2");
		}
		else
		{
			this.editor_document.value += text;
			Debug.write("Insert 3");
		}
		
		this._ie_cache = null;
	},
	insert_emoticon: function( emo_id, emo_image, emo_code, event )
	{
		this.editor_check_focus();
		
		emo_code = this.unhtmlspecialchars( emo_code );
		
		this.wrap_tags_lite( " " + emo_code, " ");		
		
		/*if ( Prototype.Browser.IE )
		{
			if ( this.emoticon_window_id != '' && !Object.isUndefined( this.emoticon_window_id ) )
			{
				this.emoticon_window_id.focus();
			}
		}*/
	},
	insertorderedlist: function(e)
	{
		this.insertlist( 'ol');
	},
	insertunorderedlist: function(e)
	{
		this.insertlist( 'ul');
	},
	insertlist: function( list_type )
	{
		var open_tag;
		var close_tag;
		var item_open_tag  = '<li>';
		var item_close_tag = '</li>';
		var regex          = '';
		var all_add        = '';
		
		if ( this.use_bbcode )
		{
			regex          = new RegExp('([\r\n]+|^[\r\n]*)(?!\\[\\*\\]|\\[\\/?list)(?=[^\r\n])', 'gi');
			open_tag       = list_type == 'ol' ? '[list=1]\n' : '[list]\n';
			close_tag      = '[/list]';
			item_open_tag  = '[*]';
			item_close_tag = '';
		}
		else
		{
			regex     = new RegExp('([\r\n]+|^[\r\n]*)(?!<li>|<\\/?ol|ul)(?=[^\r\n])', 'gi');
			open_tag  = list_type == 'ol'  ? '<ol>\n'  : '<ul>\n';
			close_tag = list_type == 'ol'  ? '</ol>\n' : '</ul>\n';
		}
		
		if ( text = this.get_selection() )
		{
			text = open_tag + text.replace( regex, "\n" + item_open_tag + '$1' + item_close_tag ) + '\n' + close_tag;
			
			if ( this.use_bbcode )
			{
				text = text.replace( new RegExp( '\\[\\*\\][\r\n]+', 'gi' ), item_open_tag );
			}
			
			this.insert_text( text );
		}
		else
		{
			if ( Prototype.Browser.Gecko )
			{
				//this.insert_text( open_tag + close_tag );
				this.insert_text( open_tag  );
				
				/* SKINNOTE: use proper lang for these */
				while ( val = prompt( ipb.lang['editor_enter_list'], '') )
				{
					this.insert_text( item_open_tag + val + item_close_tag + '\n' );
					//all_add += item_open_tag + val + item_close_tag + '\n';
				}
				this.insert_text( close_tag );
			}
			else
			{
				var to_insert = open_tag;

				while ( val = prompt( ipb.lang['editor_enter_list'], '') )
				{
					to_insert += item_open_tag + val + item_close_tag + '\n';
				}

				//this.insert_text( close_tag );
				to_insert += close_tag;
				this.insert_text( to_insert );
			}
		}
	},
	removeformat: function()
	{
		var text = this.get_selection();
		
		if ( text )
		{
			text = this.strip_html( text );
			this.insert_text( text );
		}
	},
	unlink: function()
	{
		var text       = this.get_selection();
		var link_regex = '';
		var link_text  = '';
		
		if ( text !== false )
		{
			if ( text.match( link_regex ) )
			{ 
				text = ( this.use_bbcode ) ? text.replace( /\[url=([^\]]+?)\]([^\[]+?)\[\/url\]/ig, "$2" )
										   : text.replace( /<a href=['\"]([^\"']+?)['\"]([^>]+?)?>(.+?)<\/a>/ig, "$3" );
			}
			this.insert_text( text );
		}
	},
	undo: function()
	{
		this.history_record_state( this.editor_get_contents() );
		
		this.history_time_shift( -1 );
		
		if ( ( text = this.history_fetch_recording() ) !== false )
		{
			this.editor_document.value = text;
		}
	},
	redo: function()
	{
		this.history_time_shift( 1 );
		
		if ( ( text = this.history_fetch_recording() ) !== false )
		{
			this.editor_document.value = text;
		}
	},
	update_for_form_submit: function(subjecttext, minchars)
	{
		return true;
	}	
});


//==============================================================================================================//
// MAIN EDITOR CODE

if( USE_RTE && !Prototype.Browser.WebKit ){ // Dont support safari
	Debug.write("Extending with RTE")
	_type = _editor_rte;
} else {
	Debug.write("Extending with STD");
	_type = _editor_std;
}

_editor.prototype.editor = Class.create(_type, {

	initialize: function( editor_id, mode, initial_content, options )
	{
		this.id 				= editor_id; 
		this.is_rte 			= mode;      
		this.use_bbcode 		= !mode;
		this.events 			= null;
		this.options    		= [];        
		this.items				= [];        
		this.settings			= {};        
		this.open_brace 		= '';        
		this.close_brace		= '';        
		this.allow_advanced 	= 0;
		this.initialized 		= 0;
		this.ips_frame_html		= '';
		this.forum_fix_ie_newlines	= 1;
		this.has_focus			= null;
		this.history_recordings	= [];
		this.history_pointer	= -1;
		this._ie_cache			= null;
		this._showing_html		= false;
		this._loading			= false;
		this.original			= $H();
		this.hidden_objects		= [];
		this.fontoptions		= $A();
		this.sizeoptions		= $A();
		this.font_state			= null;
		this.size_state			= null;
		this.palettes			= {};
		this.defaults			= {};
		this.key_handlers		= [];
		this.showing_sidebar	= false;
		this.emoticons_loaded	= false;
		
		this.options = Object.extend({
			file_path: 					'',
			forum_fix_ie_newlines: 		1,
			char_set: 					'UTF-8',
			ignore_controls: 			[],
			button_update: 				[]
		}, arguments[3] || {});
		
		
		this.button_update = $A( [ "bold", "italic", "underline", "justifyleft", "justifycenter",
								"justifyright",	"insertorderedlist", "insertunorderedlist", "superscript", "subscript", "strikethrough" ].concat( this.options.button_update ) );
		
		this.values = ipb.editor_values; // The values for things like font list, colors etc.
		
		this.items['text_obj'] = $( this.id + "_textarea" );
		this.items['buttons'] = $A();
		this.items['controls'] = $( this.id + '_controls' );
		
		//Other configuration
		this.open_brace            = this.use_bbcode ? '[' : '<';
		this.close_brace           = this.use_bbcode ? ']' : '>';
		this.allow_advanced        = this.use_bbcode ?  0  :  1;
		this.doc_body			   = $$('body')[0];
		
		//Object.extend( this._history, _editor_history );
		this.events = new _editor_events();				
		this._identifyType();
		
		// Any custom BBCodes to build?
		if( this.values.get('bbcodes') && $( this.id + '_cmd_otherstyles' ) ) // check for existence of other styles menu
		{
			this.buildCustomStyles();
		}
		
		// Lets try and find the form this belongs to, and add a submit
		// event to convert the text properly
		if( this.items['text_obj'].up('form') ){
			this.items['text_obj'].up('form').observe( 'submit', function(e){
				this.update_for_form_submit();
			}.bindAsEventListener(this) );
		}
		
		this.init( initial_content );
		
		Debug.write("All editor initialization complete");
	},
	/* ------------------------------ */
	/**
	 * Initialization
	*/
	init: function( initial_text )
	{
		try {
			if ( this.initialized ){
				return;
			}
		
			// Set WYSIWYG flag
			if( $( this.id + '_wysiwyg_used' ) ){
				$( this.id + '_wysiwyg_used' ).value = parseInt( this.is_rte );
			}
			
			if( ipb.Cookie.get('emoticon_sidebar') == '1' && $( this.id + '_sidebar' ) )
			{
				this.buildEmoticons();
				$( this.id + '_sidebar' ).show();
				$( 'editor_' + this.id ).addClassName('with_sidebar');
				ipb.Cookie.set( 'emoticon_sidebar', 1, 1 );
				this.showing_sidebar = true;
			}
			
			// Get default frame HTML
			this.ips_frame_html = this.get_frame_html();
			
			// Set editor up
			this.editor_set_content( initial_text );
					
			// Set mouse events
			this.editor_set_functions();
			// Set controls up
			this.editor_set_controls();			
			
			// Having this here forces the page to jump down to the editor when it loads
			// which is highly undesirable.  Tested STD and RTE in FF and IE8, and Safari with STd
			// with this commented out and seems fine... Bug 15476
			//this.editor_check_focus();
		
			this.initialized = true;
		}
		catch(err){ Debug.error( "#4 " + err ); }
	},
	
	/* ------------------------------ */
	/**
	 * Builds custom styles menu & toolbars
	 */
	buildCustomStyles: function()
	{
		var buttons = false;
		var other_styles = false;
		
		// Create the toolbar to start with
		var toolbar = ipb.editor_values.get('templates')['toolbar'].evaluate( { 'id': this.id, 'toolbarid': '3' } );
		$( this.id + '_toolbar_2' ).insert( { after: toolbar } );
		
		// And init the other styles menu
		this.init_editor_menu( $( this.id + '_cmd_otherstyles' ) );
		
		this.values.get('bbcodes').each( function( bbcode ){
			
			if( !bbcode.value['image'].blank() )
			{
				$( this.id + '_toolbar_3' ).insert( ipb.editor_values.get('templates')['button'].evaluate( { 'id': this.id, 'cmd': bbcode.key, 'title': bbcode.value['title'], 'img': bbcode.value['image'] } ) );
				buttons = true;
			}
			else
			{
				$( this.id + '_popup_otherstyles_menu' ).insert( ipb.editor_values.get('templates')['menu_item'].evaluate( { 'id': this.id, 'cmd': bbcode.key, 'title': bbcode.value['title'] } ) );
				other_styles = true;
			}
			
			if( !( bbcode.value['useoption'] == '0' && bbcode.value['single_tag'] == '1' ) )
			{
				var item_wrap = new Element('div', { id: this.id + '_palette_otherstyles_' + bbcode.key } );
				item_wrap.addClassName('ipb_palette').addClassName('extended');
				item_wrap.writeAttribute("styleid", bbcode.key);

				var _content = this.values.get('templates')['generic'].evaluate( {
					id: this.id + '_' + bbcode.key,
					title: bbcode.value['title'],
					example: bbcode.value['example'],
					option_text: bbcode.value['menu_option_text'] || '',
					value_text: bbcode.value['menu_content_text'] || ''
				} );

				item_wrap.update( _content );
				this.doc_body.insert( { top: item_wrap } );

				// Figure out what needs to be shown
				if( bbcode.value['useoption'] == '0' ){
					item_wrap.select('.optional').invoke('remove');
				}
				
				if( bbcode.value['single_tag'] == '1' ){
					item_wrap.select('.tagcontent').invoke('remove');
				}
				
				this.palettes[ 'otherstyles_' + bbcode.key ] = new ipb.Menu( $( this.id + '_cmd_custom_' + bbcode.key ), item_wrap, { stopClose: true, offsetX: 0, offsetY: 42, positionSource: $( this.id + '_cmd_otherstyles' ) } );
				item_wrap.down('input[type="submit"]').observe('click', this.events.handle_custom_onclick.bindAsEventListener( this ) );
			}
			
			// Set up events
			$( this.id + '_cmd_custom_' + bbcode.key ).observe('click', this.events.handle_custom_command.bindAsEventListener( this, bbcode.key ));
		
		}.bind(this));
		
		if( buttons ){
			$( this.id + '_toolbar_3' ).show();
		}
		
		if( other_styles ){
			$( this.id + '_cmd_otherstyles' ).show();
		}
		
	},

	/* ------------------------------ */
	/**
	 * Resizes the content area
	*/
	resize_to: function( size )
	{
		try {
			if( this.is_rte )
			{
				new Effect.Morph( $( this.editor_box ), { style: 'height: ' + size + 'px', duration: 0.3 } );
				$( this.items['text_obj'] ).setStyle( 'height: ' + size + 'px' );
			}
			else
			{
				new Effect.Morph( $( this.items['text_obj'] ), { style: 'height: ' + size + 'px', duration: 0.3 } );
				if( $( this.editor_box ) ){ $( this.editor_box ).setStyle( 'height: ' + size + 'px' ); }
			}
		} catch(err) { Debug.write( "#5 " + err ); }		
	},
	
	/* ------------------------------ */
	/**
	 * Returns HTML for the RTE frame
	*/
	get_frame_html: function()
	{
		var ips_frame_html = "";
		ips_frame_html += "<html id=\""+this.id+"_html\">\n";
		ips_frame_html += "<head>\n";
		ips_frame_html += "<meta http-equiv=\"content-type\" content=\"text/html; charset=" + this.options.char_set + "\" />";
		ips_frame_html += "<style type='text/css' media='all'>\n";
		ips_frame_html += "body {\n";
		ips_frame_html += "	background: #FFFFFF;\n";
		ips_frame_html += "	margin: 0px;\n";
		ips_frame_html += "	padding: 4px;\n";
		ips_frame_html += "	font-family: Verdana, arial, sans-serif;\n";
		ips_frame_html += "	font-size: 9pt;\n";
		ips_frame_html += "}\n";
		ips_frame_html += "</style>\n";
		
		if( isRTL && rtlFull )
		{
			ips_frame_html += "<link rel='stylesheet' type='text/css' media='screen' href='" + rtlFull + "' />\n";
			
			if( rtlIe && Prototype.Browser.IE )
			{
				ips_frame_html += "<!--[if lte IE 7]>\n";
				ips_frame_html += "<link rel='stylesheet' type='text/css' media='screen' href='" + rtlIe + "' />\n";
				ips_frame_html += "<![endif]-->\n";
			}
		}
		ips_frame_html += "</head>\n";
		ips_frame_html += "<body class='withRTL'>\n";
		ips_frame_html += "{:content:}\n";
		ips_frame_html += "</body>\n";
		ips_frame_html += "</html>";

		return ips_frame_html;
	},
	editor_check_focus: function()
	{
		Debug.write( "Focussing" );
		try {
			this.editor_window.focus();
		} catch(err) { }
		/*if ( ! this.editor_window.has_focus ){
			this.editor_window.focus();
		}*/
	},
	editor_set_controls: function()
	{
		//Debug.write( $( this.id + '_controls' ).select('span') );
		controls = $( this.id + '_controls' ).select('.rte_control').each( function(elem)
		{
			if( !elem.id ){ return; }
			if( this.options.ignore_controls.include( elem.id.toLowerCase() ) ){ return; }
			
			if( elem.hasClassName('rte_button') ){
				this.init_editor_button( elem );
			} else if ( elem.hasClassName( 'rte_menu' ) && !elem.hasClassName( 'rte_special') ){
				this.init_editor_menu( elem ); // SKINNOTE: IE error on this line
			} else if ( elem.hasClassName( 'rte_palette' ) ){
				this.init_editor_palette( elem );
			} else {
				return;
			}
			this.set_control_unselectable( elem );
		}.bind(this));
		
		if( $( this.id + '_resizer' ) ){
			this.init_editor_button( $( this.id + '_cmd_r_small' ) );
			this.set_control_unselectable( $( this.id + '_cmd_r_small' ) );
			this.init_editor_button( $( this.id + '_cmd_r_big' ) );
			this.set_control_unselectable( $( this.id + '_cmd_r_big' ) );
		}
			
	},
	set_button_context: function(obj, state, type)
	{
		//-----------------------------------------
		// Showing HTML?
		//-----------------------------------------
		if ( this._showing_html ){
			return false;
		}
		
		if ( Object.isUndefined( type ) ){
			type = 'button';
		}
		
		if ( state == 'mousedown' && ( obj.readAttribute('cmd') == 'undo' || obj.readAttribute('cmd') == 'redo' ) ){
			return false;
		}
		
		switch ( obj.readAttribute('state') )
		{
			case 'true':
			{
				switch (state)
				{
					case 'mouseout':
						this.editor_set_ctl_style(obj, 'button', 'selected');
						break;
					case 'mouseover':
					case 'mousedown':
					case 'mouseup':
						this.editor_set_ctl_style(obj, type, 'down');
						break;
				}
				break;
			}
			default:
			{
				switch (state)
				{
					case 'mouseout':
						this.editor_set_ctl_style(obj, type, 'normal');
						break;
					case 'mousedown':
						this.editor_set_ctl_style(obj, type, 'down');
						break;
					case 'mouseover':
					case 'mouseup':
						this.editor_set_ctl_style(obj, type, 'hover');
						break;
				}
				break;
			}
		}
	},
	editor_set_ctl_style: function( obj, type, mode )
	{
		if ( obj.readAttribute('mode') == mode ){ return; }
	
		// Add in -menu class
		var extra = '';
		
		if ( type == 'menu' ){
			extra = '_menu';
		} else if ( type == 'menubutton' ) {
			extra = '_menubutton';
		}
		
		// Add in -color if it's a color box
		extra     += obj.readAttribute('colorname') ? '_color' : '';
		
		// Add in -emo if it's an emo		
		extra     += obj.readAttribute('emo_id') ? '_emo' : '';
		
		// Set mode...
		obj.writeAttribute('mode', mode);
		
		try
		{
			switch ( mode )
			{
				case "normal":
					obj.addClassName('rte_normal' + extra).removeClassName('rte_hover').removeClassName('rte_selected');
					break;
				case "hover":
					obj.addClassName('rte_hover' + extra ).removeClassName('rte_normal').removeClassName('rte_selected');
					break;
				case "selected":
				case "down":
					obj.addClassName('rte_selected' + extra ).removeClassName('rte_normal').removeClassName('rte_hover');
					break;
			}
		}
		catch (err)
		{
			Debug.write( "#6 " + err );
		}
	},
	set_control_unselectable: function(elem)
	{
		if( !$(elem) ){ return; }
		$( elem ).descendants().each( function(dec){
			dec.writeAttribute( 'unselectable', 'on' );
		});
		
		$(elem).writeAttribute( 'unselectable', 'on' );
	},
	init_editor_button: function( elem )
	{
		// What command are we running?
		elem.writeAttribute( 'cmd', 		elem.id.replace( this.id + '_cmd_', '' ) );
		elem.writeAttribute( 'editor_id',	this.id );

		// Add to buttons array
		this.items['buttons'][ elem.readAttribute('cmd') ] = elem;

		// Set up defaults
		elem.writeAttribute('state',		false);
		elem.writeAttribute('mode',			'normal');
		elem.writeAttribute('real_type',	'button');
		
		elem.observe( 'click',			this.events.button_onmouse_event.bindAsEventListener( this ) );
		elem.observe( 'mousedown',		this.events.button_onmouse_event.bindAsEventListener( this ) );
		elem.observe( 'mouseover',		this.events.button_onmouse_event.bindAsEventListener( this ) );
		elem.observe( 'mouseout',		this.events.button_onmouse_event.bindAsEventListener( this ) );
	},
	
	/* ------------------------------ */
	/**
	 * Builds a simple menu
	 * 
	 * @var		{element}	elem	The relevant element
	*/
	init_editor_menu: function( elem )
	{		
		// Build the details
		elem.writeAttribute( 'cmd', 		elem.id.replace( this.id + '_cmd_', '' ) );
		elem.writeAttribute( 'editor_id', 	this.id );
		
		this.items['buttons'][ elem.readAttribute('cmd') ] = elem;
		
		// Build wrapper
		var wrap = new Element('ul', { id: this.id + '_popup_' + elem.readAttribute('cmd') + '_menu' } );
		wrap.writeAttribute( 'cmd', elem.readAttribute('cmd' ) ).addClassName('ipbmenu_content');
		wrap.hide();
		
		if( elem.hasClassName('rte_font') )
		{
			this.fontoptions[ '_default' ] = elem.innerHTML;
			
			ipb.editor_values.get('primary_fonts').each( function(font){
				var item = new Element( 'li', { 'id': this.id + '_fontoption_' + font.key } );
				item.setStyle( 'font-family: "' + font.value + '"; cursor: pointer' ).addClassName('fontitem');
				item.update( font.value );
				$( item ).observe( 'click', this.events.font_format_option_onclick.bindAsEventListener( this ) );
				wrap.insert( item );
				
				this.fontoptions[ font.key ] = item;
			}.bind(this));
			
			elem.insert( { after: wrap } );
		}
		else if( elem.hasClassName('rte_fontsize') )
		{
			this.sizeoptions[ '_default' ] = elem.innerHTML;
			
			ipb.editor_values.get('font_sizes').each( function( size ){
				var item = new Element( 'li', { id: this.id + '_sizeoption_' + size } );
				item.setStyle( 'font-size: ' + this.convert_size( size, 1 ) + 'pt; cursor: pointer' ).addClassName('fontitem');
				item.update( size );
				$( item ).observe( 'click', this.events.font_format_option_onclick.bindAsEventListener( this ) );
				wrap.insert( item );
				wrap.addClassName("fontsizes");
				
				this.sizeoptions.push( size );
			}.bind(this));
			
			elem.insert( { after: wrap } );
		}
		else if( elem.hasClassName('rte_special') )
		{
			/*ipb.editor_values.get('other_styles').each( function(style){
				var item = new Element( 'li', { id: this.id + '_otherstyle_' + style.key } ).setStyle('cursor: pointer;').addClassName('specialitem');
				item.update( style.value[0] );
				//$( item ).observe( 'click', this.events.other_styles_onclick.bindAsEventListener( this ) );
				wrap.insert( item );
				
				// Set up palette
				var item_wrap = new Element('div', {id: this.id + '_palette_other_' + style.key } );
				item_wrap.hide().addClassName('ipb_palette').addClassName('extended');
				item_wrap.writeAttribute("styleid", style.key);
				
				var _content = ipb.editor_values.get('templates')['generic'].evaluate( {
					id: this.id + '_' + style.key,
					title: style.value[0],
					example: style.value[3],
					option_text: style.value[5] || '',
					value_text: style.value[4] || ''
				} );
				
				item_wrap.update( _content );
				item_wrap.down('input[type="submit"]').observe('click', this.events.other_styles_onclick.bindAsEventListener( this ) );
				
				$( this.id + '_controls').insert( { bottom: item_wrap } );
				
				if( !style.value[2] )
				{
					item_wrap.select('.optional').invoke('remove');
				}
				
				this.palettes[ 'otherstyles_' + style.key ] = new ipb.Menu( $( item ), item_wrap, { stopClose: true, offsetX: 50, offsetY: 50 } );
				$( item ).observe('click', this.events.other_styles_preshow_onclick.bindAsEventListener( this, style.key ) );
			}.bind(this));
			*/
			
			elem.insert( { after: wrap } );
		}
		
		// Init function for IE
		var beforeOpen = function( menu ){
			this.preserve_ie_range();
			/*Debug.write("Preserved IE Range");*/
		}.bind(this);
		
		// Create new menu
		new ipb.Menu( $( elem ), wrap, {}, { beforeOpen: beforeOpen } );				 
	},
	
	/* ------------------------------ */
	/**
	 * Builds a palette
	 * 
	 * @var		{element}	elem	The relevant element
	*/
	init_editor_palette: function( elem )
	{
		elem.writeAttribute( 'cmd', 		elem.id.replace( this.id + '_cmd_', '' ) );
		elem.writeAttribute( 'editor_id', 	this.id );
		
		this.items['buttons'][ elem.readAttribute('cmd') ] = elem;
		
		wrap = new Element('div', { id: this.id + '_palette_' + elem.readAttribute('cmd') } );
		wrap.writeAttribute( 'cmd', elem.readAttribute('cmd') ).addClassName('ipb_palette');
		wrap.hide();
		opt = {};
		var handleReturn = false;
		
		switch( elem.readAttribute('cmd') )
		{
			case 'forecolor':
			case 'backcolor':
				var table = new Element('table', { id: this.id + '_' + elem.readAttribute('cmd') + '_palette' }).addClassName('rte_colors');
				
				// At this point IE would quite like you to have a tbody too
				var tbody = new Element('tbody');
				table.insert( tbody );
				
				var tr = null;
				var td = null;
				var color = '';
				
				for( i=0; i < ipb.editor_values.get('colors').length; i++ )
				{
					color = ipb.editor_values.get('colors')[i];
					
					if( i % ipb.editor_values.get('colors_perrow') == 0 ){
						tr = new Element('tr');
						tbody.insert( tr );
					}
					
					td = new Element('td', { id: this.id + '_' + elem.readAttribute('cmd') + '_color_' + color } );
					td.setStyle('background-color: #' + color).writeAttribute('colorname', color);
					
					tr.insert( td );
					
					// Events
					$( td ).observe('click', this.events.color_cell_onclick.bindAsEventListener( this ) );
				}
				
				wrap.insert( table ).addClassName('color_palette');
				elem.insert( { after: wrap } );
			break;
			/*case 'emoticons':
				if( Object.isUndefined( ipb.editor_values.get('emoticons') ) )
				{
					elem.hide();
					return;
				}
				
				/*var table = new Element( 'ul', { id: this.id + '_emoticons_palette' } ).addClassName('rte_emoticons');
				var perrow = 10;
				var row = null;
				var i = 0;
				
				ipb.editor_values.get('emoticons').each( function(emote){
						
					var _tmp = emote.value.split(',');
					var img = new Element('img', { id: 'smid_' + _tmp[0], src: ipb.vars['emoticon_url'] + '/' + _tmp[1] } );
					var li = new Element('li', { id: this.id + '_emoticons_emote_' + _tmp[0] } ).setStyle('').addClassName('emote');
					
					li.writeAttribute('emo_id', _tmp[0] );
					li.writeAttribute('emo_img', _tmp[1] );
					li.writeAttribute('emo_code', emote.key);
					
					table.insert( li.insert( img ) );
					$( li ).observe('click', this.events.emoticon_onclick.bindAsEventListener( this ) );
					
				
				}.bind(this));
				
				//-------------
				var table = new Element( 'table', { id: this.id + '_emoticons_palette' } ).addClassName('rte_emoticons');
				
				// Same as before, tbody please, says IE
				var tbody = new Element('tbody');
				table.insert( tbody );
				
				var tr = null;
				var td = null;
				
				var perrow = 10;
				var i = 0;
				
				//Debug.write( ipb.editor_values.get('emoticons') );
				ipb.editor_values.get('emoticons').each( function(emote){
					
					if( i % perrow == 0 )
					{
						tr = new Element( 'tr' );
						tbody.insert( tr );
					}
					
					i++;
					
					var _tmp = emote.value.split(',');
					var img = new Element('img', { id: 'smid_' + _tmp[0], src: ipb.vars['emoticon_url'] + '/' + _tmp[1] } );
					
					td = new Element('td', { id: this.id + '_emoticons_emote_' + _tmp[0] } ).setStyle('cursor: pointer; text-align: center').addClassName('emote');
					td.writeAttribute('emo_id', _tmp[0] );
					td.writeAttribute('emo_img', _tmp[1] );
					td.writeAttribute('emo_code', emote.key);
					
					td.insert( img );
					tr.insert( td );
					$( td ).observe('click', this.events.emoticon_onclick.bindAsEventListener( this ) );
				}.bind(this));
						
				
				// IE sucks
				if( Prototype.Browser.IE )
				{
					var tmpdiv = new Element( 'div' ).setStyle('overflow-x: scroll');
					wrap.insert( tmpdiv.insert( table ) ).addClassName('emoticons_palette');
					elem.insert( { after: wrap } );
				}
				else
				{
					wrap.insert( table ).addClassName('emoticons_palette');
					elem.insert( { after: wrap } );
				}
				
				if( this.values.get('show_emoticon_link') )
				{
					var emote_link = this.values.get('templates')['emoticons_showall'].evaluate( { id: this.id } );
					wrap.insert( { bottom: emote_link } );
					
					$( this.id + '_all_emoticons' ).observe( 'click', this.events.show_emoticon_sidebar.bindAsEventListener( this ) );
				}			
				
			break;*/
			case 'link':
			case 'image':
			case 'email':
			case 'media':
				if( Object.isUndefined( ipb.editor_values.get('templates')[ elem.readAttribute('cmd') ] ) )
				{
					elem.hide();
					return;
				}
				try {
					wrap.update( ipb.editor_values.get('templates')[ elem.readAttribute('cmd') ].evaluate( { id: this.id } ) );
					wrap.down('input[type="submit"]').observe('click', this.events.palette_submit.bindAsEventListener( this ) );
				} catch( err ) { Debug.write("#7 " + err); }
					
				this.doc_body.insert( { top: wrap } );
				
				if( elem.readAttribute('cmd') == 'link' )
				{
					try {
						this.defaults['link_text'] = $F( this.id + '_urltext' );
						this.defaults['link_url'] = $F( this.id + '_url' );
					} catch( err ){ Debug.write( "#8 " + err ); }
					$( elem ).observe('click', this.events.link_onclick.bindAsEventListener( this ) );
				}
				
				if( elem.readAttribute('cmd') == 'image' )
				{
					try {
						this.defaults['img'] = $F( this.id + '_img' );
					} catch( err ){ Debug.write( "#15 " + err ); }
					$( elem ).observe('click', this.events.image_onclick.bindAsEventListener( this ) );
				}
				
				if( elem.readAttribute('cmd') == 'email' )
				{
					try {
						this.defaults['email_text'] = $F( this.id + '_emailtext' );
						this.defaults['email'] = $F( this.id + '_email' );
					} catch( err ){ Debug.write( "#8 " + err ); }
					$( elem ).observe('click', this.events.email_onclick.bindAsEventListener( this ) );
				}
				
				if( elem.readAttribute('cmd') == 'media' )
				{
					try {
						this.defaults['media'] = $F( this.id + '_media' );
					} catch( err ){ Debug.write( "#17 " + err ); }
					$( elem ).observe('click', this.events.media_onclick.bindAsEventListener( this ) );
				}
				
				this.key_handlers[ elem.readAttribute('cmd') ] = false;
			break;
			default:
				this.items['buttons'][ elem.readAttribute( 'cmd' ) ] = null;
				elem.hide(); // Hide since we dont know what to do with it
				wrap.remove();
			break;
		}
		
		// Insert title
		if( elem.readAttribute('title' ) )
		{
			title = new Element( 'div' );
			title.update( elem.readAttribute('title') ).addClassName('rte_title');
			wrap.insert( { top: title } );
		}
		
		if( !elem.readAttribute('cmd').endsWith('color') ){
			opt = { stopClose: true };
		}
		 
		$( elem ).observe('mouseover', this.events.palette_button_onmouseover.bindAsEventListener( this ) );
		$( elem ).observe('mouseover', this.events.palette_onmouse_event.bindAsEventListener( this ) );
		$( elem ).observe('mouseout',  this.events.palette_onmouse_event.bindAsEventListener( this ) );
	
		// Init function for IE
		var beforeOpen = function( menu ){
			this.preserve_ie_range();
			/*Debug.write("Preserved IE Range");*/
		}.bind(this);

		
		//this.doc_body.insert( wrap.setStyle('position: absolute; top:0px; left:0px;') );
		this.palettes[ elem.readAttribute('cmd') ] = new ipb.Menu( $( elem ), wrap, opt, { beforeOpen: beforeOpen } );
	},
	
	convert_size: function( size, type )
	{			
		if( type == 1 )
		{
			switch( size )
			{
				case 1:
					return 7.5;
					break;
				case 2:
					return 10;
					break;
				case 3:
					return 12;
					break;
				case 4:
					return 14;
					break;
				case 5:
					return 18;
					break;
				case 6:
					return 24;
					break;
				case 7:
					return 36;
					break;
				default:
					return 10;
			}
		} else {
			switch( size )
			{
				case '7.5pt':
				case '10px': return 1;
				case '10pt': return 2;
				case '12pt': return 3;
				case '14pt': return 4;
				case '18pt': return 5;
				case '24pt': return 6;
				case '36pt': return 7;
				default:     return '';
			}
		}
	},
	
	format_text: function( e, command, arg )
	{		
		
		// Check for special commands
		if( command.startsWith( 'resize_' ) ){
			//
		}
		
		if( command == 'switcheditor' ){
			try {
				ipb.switchEditor( this.id );
			} catch(err) {
				Debug.error( "#9 " + err );
			}
		}
		
		// Recording state?
		if ( ! this.is_rte && command != 'redo' ){
			this.history_record_state( this.editor_get_contents() );
		}
		
		this.editor_check_focus();
		
		// Execute command
		if ( this[ command ] )
		{
			var return_val = this[ command ](e);
		}
		else
		{
			try
			{
				/*Debug.write( "In format text, selection is: " + this.get_selection() );*/
				var return_val = this.apply_formatting( command, false, (typeof arg == 'undefined' ? true : arg) );
			}
			catch(e)
			{
				Debug.warn( "#10 " + e );
				var return_val = false;
			}
		}
		
		// Recording state
		if ( ! this.is_rte && command != 'undo' ){
			this.history_record_state( this.editor_get_contents() );
		}
		
		// Set context
		this.set_context(command);
		
		// Check focus		
		this.editor_check_focus();

		return return_val;
	},
	
	spellcheck: function()
	{
		// Only IE can use this
		if( !Prototype.Browser.IE ){ return; }
		
		try	{
			if ( this.rte_mode ){
				var tmpis = new ActiveXObject("ieSpell.ieSpellExtension").CheckDocumentNode( this.editor_document );
			} else {
				var tmpis = new ActiveXObject("ieSpell.ieSpellExtension").CheckAllLinkedDocuments( this.editor_document );
			}
		} catch( exception ) {
			if ( exception.number == -2146827859 ) {
				if ( confirm( ipb.lang['js_rte_erroriespell'] ? ipb.lang['js_rte_erroriespell'] : "ieSpell not detected.  Click Ok to go to download page." ) )
				{
					window.open("http://www.iespell.com/download.php", "Download");
				}
			}
			else
			{
				alert( ipb.lang['js_rte_errorloadingiespell'] ? ipb.lang['js_rte_errorloadingiespell'] : "Error Loading ieSpell: Exception " + exception.number);
			}
		}
		
	},
	
	wrap_tags: function(tag_name, has_option, selected_text)
	{
		//-----------------------------------------
		// Fix up for HTML use
		//-----------------------------------------
		
		var tag_close = tag_name;
		
		if ( ! this.use_bbcode )
		{
			switch( tag_name )
			{
				case 'url':
					tag_name  = 'a href';
					tag_close = 'a';
					break;
			 	case'email':
					tag_name   = 'a href';
					tag_close  = 'a';
					has_option = 'mailto:' + has_option;
					break;
				case 'img':
					tag_name  = 'img src';
					tag_close = '';
					break;
				case 'font':
					tag_name  = 'font face';
					tag_close = 'font';
					break;
				case 'size':
					tag_name  = 'font size';
					tag_close = 'font';
					break;
				case 'color':
					tag_name  = 'font color';
					tag_close = 'font';
					break;
				case 'background':
					tag_name  = 'font bgcolor';
					tag_close = 'font';
					break;
				case 'indent':
					tag_name = tag_close = 'blockquote';
					break;
				case 'left':
				case 'right':
				case 'center':
					has_option = tag_name;
					tag_name   = 'div align';
					tag_close  = 'div';
					break;
			}
		}
		
		//-----------------------------------------
		// Got selected text?
		//-----------------------------------------
		
		if ( Object.isUndefined( selected_text ) )
		{
			selected_text = this.get_selection();
			selected_text = (selected_text === false) ? '' : new String(selected_text);
		}
		
		//-----------------------------------------
		// Using option?
		//-----------------------------------------
		
		if ( has_option === true )
		{
			var option = prompt( ips_language_arrayp['js_rte_optionals'] ? ips_language_arrayp['js_rte_optionals'] : "Enter the optional arguments for this tag", '');
			
			if ( option )
			{
				var opentag = this.open_brace + tag_name + '="' + option + '"' + this.close_brace;
			}
			else
			{
				return false;
			}
		}
		else if ( has_option !== false )
		{
			var opentag = this.open_brace + tag_name + '="' + has_option + '"' + this.close_brace;
		}
		else
		{
			var opentag = this.open_brace + tag_name + this.close_brace;
		}

		var closetag = ( tag_close != '' ) ? this.open_brace + '/' + tag_close + this.close_brace : '';
		var text     = opentag + selected_text + closetag;
		//Debug.write( text );
		
		/*Debug.write("Wrap tags selection is: " + this.get_selection() );*/
		this.insert_text( text );
		
		return false;
	},
	wrap_tags_lite: function( start_text, close_text, replace_selection )
	{
		selected_text = '';
		
		if( Object.isUndefined( replace_selection ) || replace_selection == 0 )
		{
			// Got selected text?
			selected_text = this.get_selection();
			selected_text = (selected_text === false) ? '' : new String(selected_text);
		}
		
		this.insert_text( start_text + selected_text + close_text );
		
		return false;
	},
	preserve_ie_range: function()
	{ 
		if ( Prototype.Browser.IE )
		{
			this._ie_cache = this.is_rte ? this.editor_document.selection.createRange() : document.selection.createRange();
		}
	},
	clean_html: function( t )
	{
		if ( t.blank() || Object.isUndefined(t) )
		{
			return t;
		}
		
		// Sort out BR tags
		t = t.replace( /<br>/ig, "<br />");

		// Remove empty <p> tags
		t = t.replace( /<p>(\s+?)?<\/p>/ig, "");

		// HR issues
		t = t.replace( /<p><hr \/><\/p>/ig                    , "<hr />"); 
		t = t.replace( /<p>&nbsp;<\/p><hr \/><p>&nbsp;<\/p>/ig, "<hr />");

		// Attempt to fix some formatting issues...
		t = t.replace( /<(p|div)([^&]*)>/ig     , "\n<$1$2>\n" );
		t = t.replace( /<\/(p|div)([^&]*)>/ig   , "\n</$1$2>\n");
		t = t.replace( /<br \/>(?!<\/td)/ig     , "<br />\n"   );

		// And some table issues...
		t = t.replace( /<\/(td|tr|tbody|table)>/ig  , "</$1>\n");
		t = t.replace( /<(tr|tbody|table(.+?)?)>/ig , "<$1>\n" );
		t = t.replace( /<(td(.+?)?)>/ig             , "\t<$1>" );
		
		// Newlines
		t = t.replace( /<p>&nbsp;<\/p>/ig     , "<br />");
		t = t.replace( /<br \/>/ig            , "<br />\n");
		t = t.replace( /<br>/ig               , "<br />\n");
		
		t = t.replace( /<td><br \/>\n<\/td>/ig  , "<td><br /></td>" );
		
		// Script tags
		t = t.replace( /<script/g , "&lt;script" );
		t = t.replace( /<\/script>/g , "&lt;/script&gt;" );
		
		t = t.replace( /^[\s\n\t]+/g, '' );
		t = t.replace( /[\s\n\t]+$/g, '' );
		t = t.replace( /<br \/>$/g, '' );
		
		return t;
	},
	strip_empty_html: function( html )
	{
		html = html.replace( '<([^>]+?)></([^>]+?)>', "");
		return html;
	},
	strip_html: function( html )
	{
		html = html.replace( /<\/?([^>]+?)>/ig, "");
		return html;
	},
	history_record_state: function( content )
	{
		// Make sure we're not recording twice		
		if ( this.history_recordings[ this.history_pointer ] != content )
		{
			this.history_pointer++;
			this.history_recordings[ this.history_pointer ] = content;
			
			// Make sure we've not gone back in time			
			if ( !Object.isUndefined( this.history_recordings[this.history_pointer + 1] ) )
			{
				this.history_recordings[this.history_pointer + 1] = null;
			}
		}
	},
	unhtmlspecialchars: function( html )
	{
		html = html.replace( /&quot;/g, '"' );
		html = html.replace( /&lt;/g  , '<' );
		html = html.replace( /&gt;/g  , '>' );
		html = html.replace( /&amp;/g , '&' );
		
		return html;
	},
	htmlspecialchars: function( html )
	{
		html = html.replace(/&/g, "&amp;");
		html = html.replace(/"/g, "&quot;");
		html = html.replace(/</g, "&lt;");
		html = html.replace(/>/g, "&gt;");
		
		return html;
	},
	history_time_shift: function( inc )
	{
		var i = this.history_pointer + inc;
		
		if ( i >= 0 && this.history_recordings[ i ] != null && typeof this.history_recordings[ i ] != 'undefined' )
		{
			this.history_pointer += inc;
		}
	},
	history_fetch_recording: function()
	{
		if ( 	!Object.isUndefined( this.history_recordings[ this.history_pointer ]  ) &&
		 		this.history_recordings[ this.history_pointer ] != null
			)
		{
			return this.history_recordings[ this.history_pointer ];
		}
		else
		{
			return false;
		}
	},
	ipb_quote: function()
	{
		this.wrap_tags_lite(  '[quote]', '[/quote]', 0);
	},
	
	ipb_code: function()
	{
		this.wrap_tags_lite( '[code]', '[/code]', 0);
	},
	
	toggleEmoticons: function( e )
	{
		if( this.showing_sidebar )
		{
			$( this.id + '_sidebar').hide();
			$( 'editor_' + this.id ).removeClassName('with_sidebar'); 
			ipb.Cookie.set( 'emoticon_sidebar', 0, 0 );
			this.showing_sidebar = false;
		}
		else
		{
			if( this.emoticons_loaded )
			{
				$( this.id + '_emoticon_holder' ).show();
			}
			else
			{				
				this.buildEmoticons();
			}
			
			
			$( this.id + '_sidebar' ).show();
			$( 'editor_' + this.id ).addClassName('with_sidebar');
			ipb.Cookie.set( 'emoticon_sidebar', 1, 1 );
			this.showing_sidebar = true;
		}
		
	},
	
	buildEmoticons: function()
	{
		var table = new Element( 'table', { id: this.id + '_emoticons_palette' } ).addClassName('rte_emoticons');
		var tbody = new Element('tbody'); // IE needs a tbody
		table.insert( tbody );
		var perrow = 2;
		var classname = '1';
		var i = 0;
		
		//Cycle through emoticons list to build
		ipb.editor_values.get('emoticons').each( function(emote){
			
			if( i % perrow == 0 )
			{
				tr = new Element( 'tr' ).addClassName('row' + classname);
				tbody.insert( tr );
				classname = ( classname == '1' ) ? '2' : '1';
			}
			
			i++;
			
			var _tmp = emote.value.split(',');
			var img = new Element('img', { id: 'smid_' + _tmp[0], src: ipb.vars['emoticon_url'] + '/' + _tmp[1] } );
			
			td = new Element('td', { id: this.id + '_emoticons_emote_' + _tmp[0] } ).setStyle('cursor: pointer; text-align: center').addClassName('emote');
			td.writeAttribute('emo_id', _tmp[0] );
			td.writeAttribute('emo_img', _tmp[1] );
			td.writeAttribute('emo_code', emote.key);
			
			td.insert( img );
			tr.insert( td );
			$( td ).observe('click', this.events.emoticon_onclick.bindAsEventListener( this ) );
		}.bind(this));
		
		$( this.id + '_emoticon_holder' ).update( table ).show();
		this.emoticons_loaded = true;
		
		if( $( this.id + '_showall_emoticons' ) ){
			$( this.id + '_showall_emoticons' ).observe('click', this.showAllEmoticons.bindAsEventListener( this ));
		}
		
		if( $( this.id + '_close_sidebar' ) ){
			$( this.id + '_close_sidebar' ).observe('click', this.toggleEmoticons.bindAsEventListener( this ) );
		}
	},
	
	showAllEmoticons: function( e )
	{
		// Get the emoticons
		if( Object.isUndefined( inACP ) || inACP == false ){
			var url = ipb.vars['base_url'];
		} else {
			var url = ipb.vars['front_url'];
		}

		url += "app=forums&amp;module=ajax&amp;section=emoticons&amp;editor_id=" + this.id + "&amp;secure_key=" + ipb.vars['secure_hash'];

		new Ajax.Request( url.replace(/&amp;/g, '&'),
						{
							method: 'get',
							onSuccess: function(t)
							{
								$( this.id + '_emoticon_holder').update( t.responseText );
								if( $( this.id + '_showall_bar' ) ){
									$( this.id + '_showall_bar' ).hide();
									$( this.id + '_emoticon_holder').addClassName('no_bar');
								}
							}.bind(this)
						});
		
	}
	
});

//==============================================================================================================//
// MOZ SPECIFIC CODE
if( Prototype.Browser.Gecko && USE_RTE ){
	_editor.prototype.editor.prototype.ORIGINAL_editor_set_content = _editor.prototype.editor.prototype.editor_set_content;
	_editor.prototype.editor.prototype.ORIGINAL_apply_formatting = _editor.prototype.editor.prototype.apply_formatting;
	
	Debug.write( "Adding mozilla-specific methods" );
	
	_editor.prototype.editor.addMethods({
		_moz_test: function()
		{
			return "Test: " + this.id;
		},
		togglesource_pre_show_html: function()
		{
			this.editor_document.designMode = 'off';
		},
		togglesource_post_show_html: function()
		{
			this.editor_document.designMode = 'on';
		},
		editor_set_content: function( initial_text )
		{
			this.ORIGINAL_editor_set_content( initial_text );
			
			Event.observe( this.editor_document, 'keypress', this.events.editor_document_onkeypress.bindAsEventListener(this) );

			// Remove spellcheck button
			if( $( this.id + '_cmd_spellcheck' ) ){ 
				$( this.id + '_cmd_spellcheck' ).hide();
				this.hidden_objects[ this.id + '_cmd_spellcheck' ] = 1;
			}

			// Kill tags
			if ( this.use_bbcode && $( this.id + '_cmd_justifyfull' ) )
			{
				$( this.id + '_cmd_justifyfull' ).hide();
				this.hidden_objects[ this.id + '_cmd_justifyfull' ] = 1;
			}

			// Go on.. cursor, flash you bastard		
			try
			{
				var _y = parseInt( window.pageYOffset );

				// Sometimes moves the focus to the RTE
				this.editor_document.execCommand("inserthtml", false, " ");
				this.editor_document.execCommand("undo"      , false, null);

				// Restore Y
				scroll( 0, _y );
			}
			catch(error)
			{
				Debug.write( "#11 " + error );
			}
		},
		
		editor_set_functions: function()
		{
			Event.observe( this.editor_document, 	'mouseup', 	this.events.editor_document_onmouseup.bindAsEventListener(this) );
			Event.observe( this.editor_document, 	'keyup', 	this.events.editor_document_onkeyup.bindAsEventListener(this) );
			Event.observe( this.editor_document, 	'keydown', 	this.events.editor_document_onkeydown.bindAsEventListener(this) );
		
			Event.observe( this.editor_window, 		'focus', 	this.events.editor_window_onfocus.bindAsEventListener( this ) );
			Event.observe( this.editor_window, 		'blur', 	this.events.editor_window_onblur.bindAsEventListener( this ) );			
		},
		apply_formatting: function(cmd, dialog, arg)
		{
			// Moz fix to allow list button click before clicking in RTE
			if ( cmd != 'redo' )
			{
				this.editor_document.execCommand("inserthtml", false, " ");
				this.editor_document.execCommand("undo"      , false, null);
			}
		
			this.editor_document.execCommand( 'useCSS', false, true );
			return this.ORIGINAL_apply_formatting(cmd, dialog, arg);
		},
		get_selection: function()
		{
			var selection = this.editor_window.getSelection();
		
			this.editor_check_focus();
			var range     = selection ? selection.getRangeAt(0) : this.editor_document.createRange();
		
			return this.moz_read_nodes( range.cloneContents(), false );
		},
		moz_add_range: function(node, text_length)
		{
			/*Debug.write( "In moz_add_range" );*/
			this.editor_check_focus();

			var sel   = this.editor_window.getSelection();
			var range = this.editor_document.createRange();
		
			range.selectNodeContents(node);
		
			if ( text_length )
			{
				range.setEnd(  node, text_length);
				range.setStart(node, text_length);
			}
		
			sel.removeAllRanges();
			sel.addRange(range);
		},
		moz_read_nodes: function(root, toptag)
		{
			var html      = "";
			var moz_check = /_moz/i;

			switch (root.nodeType)
			{
				case Node.ELEMENT_NODE:
				case Node.DOCUMENT_FRAGMENT_NODE:
				{
					var closed;
					if (toptag)
					{
						closed   = ! root.hasChildNodes();
						html     = '<' + root.tagName.toLowerCase();
						var attr = root.attributes;
						for (var i = 0; i < attr.length; ++i)
						{
							var a = attr.item(i);
							if (!a.specified || a.name.match(moz_check) || a.value.match(moz_check))
							{
								continue;
							}

							html += " " + a.name.toLowerCase() + '="' + a.value + '"';
						}
						html += closed ? " />" : ">";
					}
					for (var i = root.firstChild; i; i = i.nextSibling)
					{
						html += this.moz_read_nodes(i, true);
					}
					if (toptag && !closed)
					{
						html += "</" + root.tagName.toLowerCase() + ">";
					}
				}
				break;

				case Node.TEXT_NODE:
				{
					html = this.htmlspecialchars(root.data);
			
				}
				break;
			}

			return html;
		},
		moz_goto_parent_then_body: function( n )
		{
			var o = n;

			while (n.parentNode != null && n.parentNode.nodeName == 'HTML')
			{
				n = n.parentNode;
			}

			if (n)
			{
				for ( var c = 0; c < n.childNodes.length; c++ )
				{
					if ( n.childNodes[c].nodeName == 'BODY' )
					{
						return n.childNodes[c];
					}
				}
			}

			return o;
		},
		moz_insert_node_at_selection: function(text, text_length)
		{
			this.editor_check_focus();

			var sel   = this.editor_window.getSelection();
			var range = sel ? sel.getRangeAt(0) : this.editor_document.createRange();
		
			sel.removeAllRanges();
			range.deleteContents();

			var node = range.startContainer;
			var pos  = range.startOffset;
		
			text_length = text_length ? text_length : 0;
		
			if ( node.nodeName == 'HTML' )
			{
				node = this.moz_goto_parent_then_body( node );
			}
		
			switch (node.nodeType)
			{
				case Node.ELEMENT_NODE:
				{ 
					if (text.nodeType == Node.DOCUMENT_FRAGMENT_NODE)
					{
						selNode = text.firstChild;
					}
					else
					{
						selNode = text;
					}
					node.insertBefore(text, node.childNodes[pos]);
					this.moz_add_range(selNode, text_length);
				}
				break;

				case Node.TEXT_NODE:
				{
					if (text.nodeType == Node.TEXT_NODE)
					{
						var text_length = pos + text.length;
						node.insertData(pos, text.data);
						range = this.editor_document.createRange();
						range.setEnd(node, text_length);
						range.setStart(node, text_length);
						sel.addRange(range);
					}
					else
					{
						node = node.splitText(pos);
						var selNode;
						if (text.nodeType == Node.DOCUMENT_FRAGMENT_NODE)
						{
							selNode = text.firstChild;
						}
						else
						{
							selNode = text;
						}
						node.parentNode.insertBefore(text, node);
						this.moz_add_range(selNode, text_length);
					}
				}
				break;
			}
			
			sel.removeAllRanges();
		},
		insert_text: function(str, len)
		{
			fragment = this.editor_document.createDocumentFragment();
			holder   = this.editor_document.createElement('span');
		
			holder.innerHTML = str;

			while (holder.firstChild){
				fragment.appendChild( holder.firstChild );
			}
		
			var my_length = parseInt( len ) > 0 ? len : 0;

			this.moz_insert_node_at_selection( fragment, my_length );
			
		},
		insert_emoticon: function( emo_id, emo_image, emo_code, event )
		{
			this.editor_check_focus();
			try
			{
				// INIT
				var _emo_url  = ipb.vars['emoticon_url'] + "/" + emo_image;
			
				this.editor_document.execCommand('InsertImage', false, _emo_url);
			
				var images = this.editor_document.getElementsByTagName('img');

				//----------------------------------
				// Sort through and fix emo
				//----------------------------------
			
				if ( images.length > 0 )
				{
					for ( var i = 0 ; i <= images.length ; i++ )
					{
						if ( !Object.isUndefined( images[i] ) && images[i].src.match( new RegExp( _emo_url + "$" ) ) )
						{
							if ( ! images[i].getAttribute('alt') )
							{
								images[i].setAttribute( 'alt', this.unhtmlspecialchars( emo_code ) );
								images[i].setAttribute( 'class', 'bbc_emoticon' );
							}
						}
					}
				}
			}
			catch(error)
			{
				Debug.write( "#12 " + error );
				//alert( error );
			}
		
			if ( this.emoticon_window_id != '' && typeof( this.emoticon_window_id ) != 'undefined' )
			{
				this.emoticon_window_id.focus();
			}
		}
	}
)
}

//==============================================================================================================//
// OPERA SPECIFIC CODE

if( Prototype.Browser.Opera && USE_RTE ){
	_editor.prototype.editor.prototype.ORIGINAL_editor_set_content = _editor.prototype.editor.prototype.editor_set_content;
	
	_editor.prototype.editor.addMethods({
		editor_set_content: function(initial_text)
		{
			this.ORIGINAL_editor_set_content(initial_text);
		
		
			// Opera doesn't auto 100% the height, so
			// lets force the body to be 100% high		
			this.editor_document.body.style.height = '95%';
		
			Event.observe( this.editor_document, 'keypress', this.events.editor_document_onkeypress.bindAsEventListener( this ) );
		
			// Remove spellcheck button
			$( this.id + '_cmd_spellcheck' ).hide();
			this.hidden_objects[ this.id + '_cmd_spellcheck' ] = 1;
		
			// Kill tags
			if ( this.use_bbcode )
			{
				$( this.id + '_cmd_justifyfull' ).hide();			
				this.hidden_objects[ this.id + '_cmd_justifyfull' ] = 1;
			}
		
			// Go on.. cursor, flash you bastard
			try
			{
				var _y = parseInt( window.pageYOffset );
			
				// Sometimes moves the focus to the RTE
				this.editor_document.execCommand("inserthtml", false, "-");
				this.editor_document.execCommand("undo"      , false, null);
			
				// Restore Y
				scroll( 0, _y );
			}
			catch(error)
			{
				Debug.write( "#13 " + error );
			}
		},
		insert_text: function(str)
		{
			this.editor_document.execCommand('insertHTML', false, str);
		},
		get_selection: function()
		{
			var selection = this.editor_window.getSelection();
		
			this.editor_check_focus();
		
			var range = selection ? selection.getRangeAt(0) : this.editor_document.createRange();
			var lsserializer = document.implementation.createLSSerializer();
		
			return lsserializer.writeToString(range.cloneContents());
		},
		editor_set_functions: function()
		{
			Event.observe( this.editor_document, 	'mouseup', 	this.events.editor_document_onmouseup.bindAsEventListener( this ) );
			Event.observe( this.editor_document, 	'keyup', 	this.events.editor_document_onkeyup.bindAsEventListener( this ) );
			Event.observe( this.editor_window, 		'focus', 	this.events.editor_window_onfocus.bindAsEventListener( this ) );
			Event.observe( this.editor_window, 		'blur', 	this.events.editor_window_onblur.bindAsEventListener( this ) );
		}
	});
}


//==============================================================================================================//
// EVENTS
_editor_events = Class.create({
	handle_custom_command: function( e, cmd )
	{
		Event.stop(e);
		
		//Debug.write( Event.element(e) );
		
		var elem = ( $( Event.element(e) ).hasClassName('specialitem') ) ? $( Event.element(e) ) : $( Event.element( e ) ).up('.specialitem');
		if( !$( elem ) ){ return; }
		
		var cmd = $( elem ).id.replace( this.id + '_cmd_custom_', '' );
		var info = this.values.get('bbcodes').get( cmd );
		
		Debug.write( cmd );
		
		if( info['single_tag'] == '1' && info['useoption'] == '0' )
		{
			this.wrap_tags_lite( '[' + info['tag'] + ']', '', 1);
			ipb.menus.registered.get( this.id + '_cmd_otherstyles' ).doClose();
			return;
		}
		else
		{
			var selection = this.get_selection();

			try {			
				window.setTimeout( function()
					{ 
						if( info['useoption'] == '1' && info['single_tag'] == '1' )
						{
							if( $( this.id + '_' + cmd + '_option') ){
								$( this.id + '_' + cmd + '_option' ).focus();
							}
						}
						else
						{		
							if( selection )
							{
								$( this.id + '_' + cmd + '_text' ).value = selection;
							}

							if( $( this.id + '_' + cmd + '_option') ){
								$( this.id + '_' + cmd + '_option' ).focus();
							} else {
								$( this.id + '_' + cmd + '_text' ).activate();
							}
						}
					}.bind(this), 200
				);
			} catch( err ){
				Debug.write( err );
			}
		}
	},
	handle_custom_onclick: function(e)
	{
		Debug.write("Here 5");
		var elem = Event.element(e);
		if( !elem.hasClassName('ipb_palette') ){
			elem = Event.findElement(e, '.ipb_palette');
		}
		
		var styleid = elem.readAttribute("styleid");
		if( !styleid || Object.isUndefined( this.values.get('bbcodes').get( styleid ) ) ) { return; }
		var info = this.values.get('bbcodes').get( styleid );
		Debug.write("Here 4");
		// Check for required option
		if( info['useoption'] == '1' && info['optional_option'] == '0' && $F( this.id + '_' + styleid + '_option' ).blank() )
		{
			alert( ipb.lang['option_is_empty'] );
			try {
				$( this.id + '_' + styleid + '_option' ).focus();
			} catch( err ){ }
			
			return;
		}
		Debug.write("Here 3");
		// Now work out what kind of tag to build...
		if( info['useoption'] == '1' && info['single_tag'] == '1' )
		{
			var option = $F( this.id + '_' + styleid + '_option' );
			
			if( info['optional_option'] == '1' && option.blank() ){
				this.wrap_tags_lite( '[' + info['tag'] + ']', '', 1 );
			} else {
				this.wrap_tags_lite( '[' + info['tag'] + "='" + option + "']", '', 1 );
			}
		}
		else
		{
			Debug.write("Here 1");
			var value = $F( this.id + '_' + styleid + '_text' );
			Debug.write("Here 2");
			var option = ( $(this.id + '_' + styleid + '_option') ) ? $F( this.id + '_' + styleid + '_option' ) : '';
			
			if( ( info['optional_option'] == '1' && option.blank() ) || info['useoption'] == '0' ){
				this.wrap_tags_lite( '[' + info['tag'] + ']' + value, '[/' + info['tag'] + ']', 1 );
			} else {
				this.wrap_tags_lite( '[' + info['tag'] + "='" + option + "']" + value, '[/' + info['tag'] + ']', 1 );
			}
		}
		/*var value = $F( this.id + '_' + styleid + '_text' );
		
		if( style[2] ){
			var option = $F( this.id + '_' + styleid + '_option' );
			this.wrap_tags_lite( '[' + style[1] + "='" + option + "']" + value, '[/' + style[1] + ']' );
		}
		else
		{
			this.wrap_tags_lite( '[' + style[1] + ']' + value, '[/' + style[1] + ']' );
		}*/
		
		this.palettes[ 'otherstyles_' + styleid ].doClose();
	},
	
	editor_document_onmouseup: function(e)
	{
		Debug.write("Mouse up");
		ipb.menus.closeAll(); // Close menus
		this.set_context();
	},
	editor_document_onkeyup: function(e)
	{
		this.set_context();
	},
	editor_document_onkeydown: function(e)
	{
		//alert( this.forum_fix_ie_newlines + "   " + Prototype.Browser.IE + "    " + e.keyCode );
		
		if (	this.forum_fix_ie_newlines &&
			 	Prototype.Browser.IE &&
			 	e.keyCode == Event.KEY_RETURN ) // Enter key 
		{
			var _test = ['Indent', 'Outdent', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'InsertOrderedList', 'InsertUnorderedList'];
	        
			for ( i=0; i< _test.length; i++ )
			{
				if ( this.editor_document.queryCommandState( _test[ i ] ) )
				{
					return true;
				}
			}
			
			var sel   = this.editor_document.selection; 
	        var ts    = this.editor_document.selection.createRange();
			var t     = ts.htmlText.replace(/<p([^>]*)>(.*)<\/p>/i, '$2');
			
	        if ( (sel.type == "Text" || sel.type == "None") ) 
	        { 
	             ts.pasteHTML( "<br />" + t + "\n" ); 
	        } 
	        else 
	        { 
	             this.editor_document.innerHTML += "<br />\n"; 
	        } 

	        this.editor_window.event.returnValue = false; 
	        ts.select(); 
	        this.editor_check_focus();
	    }
	},
	editor_document_onkeypress: function(e)
	{
		if ( e.ctrlKey )
		{
			switch (String.fromCharCode(e.charCode).toLowerCase())
			{
				case 'b': cmd = 'bold';      break;
				case 'i': cmd = 'italic';    break;
				case 'u': cmd = 'underline'; break;
				default: return;
			}

			//e.preventDefault();
			Event.stop(e);

			this.apply_formatting(cmd, false, null);
			return false;
		}
	},
	editor_window_onfocus: function(e)
	{
		this.has_focus = true;
	},
	editor_window_onblur: function(e)
	{
		this.has_focus = false;
	},
	button_onmouse_event: function(e)
	{
		if( !Event.element(e).hasClassName( 'rte_control' ) ){
			elem = $( Event.element(e) ).up('.rte_control');
		} else {
			elem = Event.element(e);
		}
		
		Debug.write( elem.readAttribute('cmd') );
		
		if ( e.type == 'click' )
		{
			if( elem.readAttribute('cmd').startsWith('custom_') )
			{
				// Return so that the custom styles code can run interruptedb
				return;
			}
			else if( elem.readAttribute('cmd') == 'help' )
			{
				//Event.stop(e);
				window.open( ipb.vars['base_url'] + "&app=forums&module=extras&section=legends&do=bbcode", "bbcode", "status=0,toolbar=0,width=1024,height=800,scrollbars=1");
				Event.stop(e);
				return false;
			}
			else if( elem.readAttribute('cmd').startsWith('r_') )
			{
				// Resize commands
				var editorSize = $( this.id + '_textarea' ).getHeight();
				
				if( elem.readAttribute('cmd') == 'r_small' ){
					this.resize_to( editorSize - 100 );
				} else {
					this.resize_to( editorSize + 100 );
				}
			}
			else if( elem.readAttribute('cmd') == 'emoticons' )
			{
				this.toggleEmoticons( e );
			}
			else
			{
				this.format_text( e, elem.readAttribute('cmd'), false, true);
			}
		}
		
		Event.stop(e); // Removed if(IE) check

		this.set_button_context(elem, e.type);
	},
	font_format_option_onclick: function(e)
	{		
		if( !Event.element(e).hasClassName('fontitem') ){
			elem = Event.element(e).up('.fontitem');
		} else {
			elem = Event.element(e);
		}
		
		cmd = $( elem ).up('.ipbmenu_content').readAttribute('cmd');
		
		//this.editor_check_focus();
		//this.preserve_ie_range();
		/*Debug.write( "Selection: " + this.get_selection() );
		Debug.write( "Format text option: " + elem.innerHTML );*/
		
		this.format_text(e, cmd, elem.innerHTML);
	},
	color_cell_onclick: function(e)
	{
		elem = Event.element(e);
		cmd = $( elem ).up('.ipb_palette').readAttribute('cmd');
		
		this.format_text(e, cmd, '#' + elem.readAttribute('colorname'));
		this.palettes[ cmd ].doClose();
	},
	
	palette_button_onmouseover: function(e)
	{
		if( !Event.element(e).hasClassName( 'rte_palette' ) ){
			elem = $( Event.element(e) ).up('.rte_palette');
		} else {
			elem = Event.element(e);
		}
		
		elem.setStyle('cursor: pointer;');
	},
	palette_onmouse_event: function(e)
	{
		if( !Event.element(e).hasClassName( 'rte_control' ) ){
			elem = $( Event.element(e) ).up('.rte_control');
		} else {
			elem = Event.element(e);
		}
		
		if( e.type == 'mouseover' ){
			$( elem ).addClassName('rte_hover');
		} else {
			$( elem ).removeClassName('rte_hover');
		}
	},
	
	palette_return_key: function(e)
	{	
		/*elem = Event.element(e);
		palette = elem.up('.ipb_palette');
		if( !palette ){ return; }
		cmd = palette.readAttribute('cmd');
			
		if( e.keyCode == Event.KEY_RETURN )
		{
			Debug.write( cmd );
			
			var test = this.events.palette_submit.bind( this, e );
			test();
			test = null;
			Event.stop(e);
		}*/
	},
	
	palette_submit: function(e)
	{
		elem = Event.element(e);
		palette = elem.up('.ipb_palette');
		if( !palette ){ return; }
		cmd = palette.readAttribute('cmd');
		
		Debug.write( $( elem ).id + " " + $( palette ).id + " " + cmd  );
		// What are we doing?
		switch( cmd )
		{
			case 'link':
				var url = $F( this.id + '_url' );
				var text = $F( this.id + '_urltext' );
				
				if( url == 'http://' || url.blank() ){ return; };
				if( text.blank() ){
					text = url;
				}
				
				this.wrap_tags( 'url', url, text );
			break;
			case 'image':
				var img = $F( this.id + '_img' );
				
				if( img == 'http://' || img.blank() ){ return; };
				
				if ( ! this.is_rte ){
					this.wrap_tags( 'img', false, img );
				} else {
					this.wrap_tags( 'img', img, '' );
				}
			break;
			case 'email':
				var email = $F( this.id + '_email' );
				var text = $F( this.id + '_emailtext' );
				
				if( email.blank() || email.indexOf('@') == -1 ){ return; }
				if( text.blank() ){
					text = email;
				}
				
				this.wrap_tags( 'email', email, text );
			break;
			case 'media':
				var url = $F( this.id + '_media' );
				
				if( url.blank() ){ return; }
				
				//this.wrap_tags_lite( '[media]' + url, '[/media]' );
				this.wrap_tags( 'media', false, url );
				/*
				if( ! this.is_rte ){
					this.wrap_tags( 'media', false, url );
				} else {
					this.wrap_tags( 'media', url, '' );
				}*/
			break;
		}
		
		this.palettes[ cmd ].doClose();
	},
	emoticon_onclick: function(e)
	{
		Event.stop(e);
		var elem = Event.element(e);
		var emo = elem.up('.emote');
		
		this.insert_emoticon( emo.readAttribute('emo_id'), emo.readAttribute('emo_img'), emo.readAttribute('emo_code'), e );
	},
	link_onclick: function(e)
	{
		// Any selected text?
		var selection = this.get_selection();
		var _active = null;
		
		try {
			if( selection )
			{
				Debug.write( "**selection: " + selection );
				
				/* Got an image? */
				if( selection.match( /<img/ ) || selection.match( /\[img\]/ ) )
				{
					/* Make sure it's valid BBcode */
					if ( ! this.is_rte )
					{
						selection = selection.gsub( /<img src=['"]([^'"]+?)['"]\s+?\/>/, function( match ) { return "[img]" + match[1] + "[/img]"; } );
					}
					
					$( this.id + '_url' ).value     = this.defaults['link_url'];
					$( this.id + '_urltext' ).value = selection;
				}
				else if( selection.match(/[A-Za-z]+:\/\/[A-Za-z0-9\.-]{3,}\.[A-Za-z]{3}/) ){
					$( this.id + '_url' ).value = selection.strip();
					$( this.id + '_urltext' ).value = this.defaults['link_text'];
					_active = $( this.id + '_urltext' );
				} else {
					$( this.id + '_urltext' ).value = selection.strip();
					$( this.id + '_url' ).value = this.defaults['link_url'];
					_active = $( this.id + '_url' );
				}
			}
			else
			{
				$( this.id + '_urltext' ).value = this.defaults['link_text'];
				$( this.id + '_url' ).value = this.defaults['link_url'];
				_active = $( this.id + '_url' );
			}
		
			if( !this.key_handlers[ 'url' ] )
			{
				$( this.id + '_url' ).observe('keypress', this.events.palette_return_key.bindAsEventListener( this ));
				$( this.id + '_urltext' ).observe('keypress', this.events.palette_return_key.bindAsEventListener( this ));
				this.key_handlers[ 'url' ] = true;
			}
			
			window.setTimeout( function()
				{ 
					$( _active ).activate();
				}.bind(this), 200
			);
				
		} catch(err){ Debug.write( "#13 " + err ); }
	},
	image_onclick: function(e)
	{		
		var selection = this.get_selection();
		var msg = ( selection && !selection.startsWith('<IMG') ) ? selection : this.defaults['img'];
		
		try {
			$( this.id + '_img' ).value = msg;
			
			if( !this.key_handlers[ 'image' ] )
			{
				$( this.id + '_img' ).observe('keypress', this.events.palette_return_key.bindAsEventListener( this ));
				this.key_handlers[ 'image' ] = true;
			}
			
			window.setTimeout( function()
				{ 
					$( this.id + '_img' ).activate();
				}.bind(this), 200
			);
		} catch(err){ Debug.write( "#14 " + err ); }
	},
	media_onclick: function(e)
	{		
		var selection = this.get_selection();
		var msg = ( selection ) ? selection : this.defaults['media'];
		
		try {
			$( this.id + '_media' ).value = msg;
			
			if( !this.key_handlers[ 'media' ] )
			{
				$( this.id + '_media' ).observe('keypress', this.events.palette_return_key.bindAsEventListener( this ));
				this.key_handlers[ 'media' ] = true;
			}
			
			window.setTimeout( function()
				{ 
					$( this.id + '_media' ).activate();
				}.bind(this), 200
			);
		} catch(err){ Debug.write( "#18 " + err ); }
	},
	email_onclick: function(e)
	{
		var selection = this.get_selection();
		var _active = $( this.id + '_email' );
		
		try {
			if( selection )
			{
				if( selection.match(/([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+/) )
				{
					$( this.id + '_email' ).value = selection.strip();
					$( this.id + '_emailtext' ).value = this.defaults['email_text'];
					_active = $( this.id + '_emailtext' );
				}
				else
				{
					$( this.id + '_emailtext' ).value = selection.strip();
					$( this.id + '_email' ).value = this.defaults['email'];
				}
			}
			else
			{
				$( this.id + '_emailtext' ).value = this.defaults['email_text'];
				$( this.id + '_email' ).value = this.defaults['email'];
			}
			
			if( !this.key_handlers[ 'email' ] )
			{
				$( this.id + '_email' ).observe('keypress', this.events.palette_return_key.bindAsEventListener( this ));
				$( this.id + '_emailtext' ).observe('keypress', this.events.palette_return_key.bindAsEventListener( this ));
				this.key_handlers[ 'email' ] = true;
			}
			
			window.setTimeout( function()
				{ 
					$( _active ).activate();
				}.bind(this), 200
			);
		} catch( err ){ Debug.write( "#16 " + err ); }
	},
	
	show_emoticon_sidebar: function( e )
	{
		// Get the emoticons
		if( Object.isUndefined( inACP ) || inACP == false ){
			var url = ipb.vars['base_url'];
		} else {
			var url = ipb.vars['front_url'];
		}
		
		url += "app=forums&amp;module=ajax&amp;section=emoticons&amp;editor_id=" + this.id;
		
		new Ajax.Request( url.replace(/&amp;/g, '&'),
						{
							method: 'get',
							onSuccess: function(t)
							{
								// Add the sidebar
								$('editor_' + this.id).addClassName('with_sidebar');
								var div = $('editor_' + this.id).down('.sidebar');
								
								div.update( this.values.get('templates')['emoticon_wrapper'].evaluate( { id: this.id } ) );
								$( this.id + '_emoticon_holder').update( t.responseText );
								div.show();
								
								this.palettes[ 'emoticons' ].doClose();
							}.bind(this)
						});
	},
	
	//called statically
	addEmoticon: function( code, id, img, editorid )
	{
		try {
			this.insert_emoticon( '', img, code, '' );
		}
		catch(err)
		{
			Debug.error( err );
		}
	}
});

/*_editor.prototype.switchEditor = function( editorID )
{
	if( !ipb.editors[ editorID ] ){ Debug.error( "No editor found for ID " + editorID ); }
	
	var editor = ipb.editors[ editorID ];
	var url = ipb.vars['base_url'] + "app=";
	var newmode = !ipb.editors[ editorID ].is_rte;
	
	if( editor._loading ){
		Debug.info("Editor is already loading")
		return false;
	} else {
		editor._loading = true;
	}
}*/


