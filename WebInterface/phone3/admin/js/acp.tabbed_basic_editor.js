/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* (c) IPS, Inc 2009							*/
/* -------------------------------------------- */
/* Author: Matt Mecham 							*/
/************************************************/

var _tt = window.IPBACP;
_tt.prototype.tabbedEditor = {
	callbacks: { 'open'   : '',
				 'switch' : '',
	 			 'close'  : '' },
	wrapId: '',
	templates: { 'textArea' : new Template( "<div id='tbe_editor_wrap_#{id}' class='tmpl_pte'><textarea id='tbe_editor_textarea_#{id}' class='tmpl_te'>{content}</textarea></div>" ),
	             'tab'      : new Template("<li id='tbe_tab_#{id}'><div id='tbe_tab_c_#{id}' class='tbe_tab_c'></div><div id='tbe_tab_f_#{id}' class='tbe_tab_f'></div><div id='tbe_tab_s_#{id}' class='tbe_tab_t'>#{title}</div></li>" ) },
	openTabs: $H(),
	sPos: {},
	_sT : false,
	_body : null,
	_po: undefined,
	_poId: 0,
	
	init: function()
	{
		Debug.write("Initializing acp.tabbedEditor.js");
		document.observe("dom:loaded", function(){
			
		});
	},
	
	/**
	 * Manually called init function
	 *
	 * @access	public
	 */
	initialize: function()
	{
		/* Create some divs for use later */
		var tabs = new Element( 'div', { id: 'tbe_tabstrip' } ).hide().addClassName( 'tbe_tabstrip' );
		$( acp.tabbedEditor.wrapId ).insert( tabs );
		
		var wrap = new Element( 'div', { id: 'tbe_wrap' } );
		$( acp.tabbedEditor.wrapId ).insert( wrap );
		
		/* Set body */
		acp.tabbedEditor._body = document.getElementsByTagName('body')[0];
	},
	
	/**
	 * Open Tab
	 * Creates a tab, or opens a new one if required
	 *
	 * @access	public
	 * @param	string		ID to assign to tab
	 * @param	string		Tab title
	 * @param	string		Tab contents
	 */
	openTab: function( id, title, content, event )
	{
		/* Store scroll pos */
		if ( ! Object.isUndefined( acp.tabbedEditor._body.scrollTop ) )
		{
			acp.tabbedEditor._sT = acp.tabbedEditor._body.scrollTop;
		}
		
		Debug.write( 'OpenTab ' + id );
		
		if ( Object.isUndefined( acp.tabbedEditor.openTabs.get( id ) ) )
		{
			acp.tabbedEditor._createTab( id, title, content );
		}
		else
		{
			acp.tabbedEditor._focusTab( id );
		}
		
		/* Restore scroll pos */
		if ( acp.tabbedEditor._sT )
		{
			acp.tabbedEditor._body.scrollTop = acp.tabbedEditor._sT;
		}
	},
	
	/**
	 * Close tab
	 *
	 * @access	public
	 * @param	string		Tab ID
	 */
	closeTab: function( id )
	{
		/* INIT */
		var _l = false;
		var _t = acp.tabbedEditor.openTabs.get( id );
		
		Debug.write( "Closing tab: " + id );
		
		if( ! Object.isUndefined( acp.tabbedEditor.callbacks['close'] ) )
		{
			Debug.write( 'Calling callback' );
			if ( ! acp.tabbedEditor.callbacks['close']( _t, this ) )
			{
				return false;
			}
		}
		
		/* Remove Tab */
		acp.tabbedEditor.openTabs.unset( id );
		
		/* Remove editor div / text area */
		$( 'tbe_editor_textarea_' + id ).remove();
		$( 'tbe_editor_wrap_' + id ).remove();
		
		/* Fetch next item inline */
		$H( acp.tabbedEditor.openTabs ).each( function(tab)
		{
			/* Set last ID */
			_l = tab.value['id'];
		} );
	
		/* Make one lit? */
		if ( _l  !== false )
		{
			acp.tabbedEditor.openTab( _l );
		}
		else
		{
			$( 'tbe_tabstrip' ).hide();
			$( 'tbe_tabs' ).hide();
		}
	},
	
	/**
	 * Get current file details
	 *
	 * @access	public
	 */
	getCurrentFile: function()
	{
		if ( $$('.tbe_item_lit')[0] )
		{
			var _t = $$('.tbe_item_lit')[0].id;
	
			if ( ! Object.isUndefined( _t ) )
			{ 
				return acp.tabbedEditor.openTabs.get( _t.replace( 'tbe_tab_', '' ) );
			}
			else
			{
				return undefined;
			}
		}
		else
		{
			return undefined;
		}
	},
	
	/**
	 * Get all files
	 *
	 * @access	public
	 */
	getAllFiles: function()
	{
		return acp.tabbedEditor.openTabs;
	},
	
	/**
	 * Set Content of an editor
	 *
	 * @access	public
	 * @param	string		Tab ID
	 * @param	string		Content to set
	 */
	setContent: function( id, content )
	{
		if ( $( 'tbe_editor_textarea_' + id ) )
		{
			$( 'tbe_editor_textarea_' + id ).value = content;
		}
	},
	
	/**
	 * Get Content of an editor
	 *
	 * @access	public
	 * @param	string		Tab ID
	 * @param	string		Content to set
	 */
	getContent: function( id )
	{
		id = ( id ) ? id : acp.tabbedEditor.getCurrentFile().id;
		
		if ( $( 'tbe_editor_textarea_' + id ) )
		{
			return acp.tabbedEditor._HtmlOutgoing( $( 'tbe_editor_textarea_' + id ).value );
		}
	},
	
	/**
	 * Set the edited status of a file
	 *
	 * @access	public
	 * @param	string		Tab ID
	 * @param	string		[Content to update]
	 * @param	boolean
	 */
	setFileEditedMode: function( id, status, content )
	{
		status = ( status === true ) ? true : false;
		
		var _t = acp.tabbedEditor.openTabs.get( id );
		var _c = ( Object.isUndefined( content ) ) ? _t.content : content;
		
		acp.tabbedEditor.openTabs.set( id, { id: _t.id, title: _t.title, content: _c, edited: status } );
		
		/* Got stuff to do? */
		if ( $( 'tbe_tab_s_' + id ) )
		{
			if ( status === true && _t.edited !== true )
			{
				/* mark as edited for the first time */
				$( 'tbe_tab_s_' + id ).update(  _t.title + ' *' );
			}
			else if ( status === false && _t.edited !== false )
			{
				/* mark as NOT edited for the first time */
				$( 'tbe_tab_s_' + id ).update(  _t.title );
			}
		}
		
		return true;
	},
	
	/**
	 * Open tab (From event)
	 *
	 * @access	private
	 * @param	object		Mouse event
	 * @param	string		Tab ID
	 */
	_openTab: function( event, id )
	{
		Event.stop( event );
		
		/* Open Tab */
		acp.tabbedEditor.openTab( id );
	},
	
	/**
	 * Open tab (From event)
	 *
	 * @access	private
	 * @param	object		Mouse event
	 * @param	string		Tab ID
	 */
	_closeTab: function( event, id )
	{
		Event.stop( event );
		
		/* Open Tab */
		acp.tabbedEditor.closeTab( id );
	},
	
	/**
	 * Pop up tab (From event)
	 *
	 * @access	private
	 * @param	object		Mouse event
	 * @param	string		Tab ID
	 */
	_popUp: function( event, id )
	{
		Event.stop( event );
		
		/* Create pop-up elements */
		var _id      = '__p_' + id;
		var _data    = acp.tabbedEditor.openTabs.get( id );
		var _t       = acp.tabbedEditor.templates['textArea'].evaluate( { 'id': _id } );
		var content  = acp.tabbedEditor.getContent( id );
		var title    = _data.title;
		var _initial = '<div class="acp-box"><h3>' + title + '</h3>' + _t.replace( '{content}', content ) + "</div>";
		var _dims    = document.viewport.getDimensions();
		
		var _popUp = new ipb.Popup( '_p_' + id, { type: 'pane',
												  w: ( _dims.width - 50 ) + 'px',
												  h: ( _dims.height - 50 ) + 'px',
												  initial: _initial }, { afterShow: acp.tabbedEditor._popUpShow, afterHide: acp.tabbedEditor._popUpClose } );
		
		_popUp.show();
	},
	
	/**
	 */
	_popUpShow: function( event )
	{
		var _dims    = document.viewport.getDimensions();
		var _id      = '_' + event.id;
		
		$('tbe_editor_wrap_' + _id).setStyle( { height: ( _dims.height - 60 ) + 'px' } );
		$('tbe_editor_wrap_' + _id).up('.popupWrapper').setStyle( { top: '20px' } );
		$('tbe_editor_wrap_' + _id).up('.popupInner').setStyle( { overflow: 'hidden' } );
		
		$('tbe_editor_textarea_' + _id).setStyle( { height: ( _dims.height - 90 ) + 'px' } );
		
		/* Add listener */
		$( 'tbe_editor_textarea_' + _id ).observe('keydown', acp.tabbedEditor._keyDownCheck.bindAsEventListener( event, event.id.replace( /^_p_/, '' ) ) );
	},
	
	/**
	 */
	_popUpClose: function( event )
	{
		var _id  = event.id;
		var _rid = _id.replace( /^_p_/, '' );
		
		/* Update contents */
		acp.tabbedEditor.setContent( _rid, $('tbe_editor_textarea_' + '_' + _id).value );
		
		/* remove pop-up */
		$( _id + '_popup' ).remove();
	},
	
	/**
	 * Create a new tab
	 *
	 * @access	private
	 * @param	string		ID to assign to tab
	 * @param	string		Tab title
	 * @param	string		Tab contents
	 */
	_createTab: function( id, title, content )
	{
		/* INIT */
		var _t = acp.tabbedEditor.templates['textArea'].evaluate( { 'id': id } );

		/* Push tab */
		acp.tabbedEditor.openTabs.set( id, { id: id, title: title, content: content, edited: false } );
		
		/* Create wrapper */
		try
		{
			Debug.write("htmlincoming: " + acp.tabbedEditor._HtmlIncoming( content ) );
			$( 'tbe_wrap' ).innerHTML += _t.replace( '{content}', acp.tabbedEditor._HtmlIncoming( content ) );
		}
		catch( e )
		{
			/* insert sometimes complains with regular HTML which is why we don't use .insert() */
			Debug.error( e );
		}
		
		/* Add listener */
		$( 'tbe_editor_textarea_' + id ).observe('keydown', acp.tabbedEditor._keyDownCheck.bindAsEventListener( this, id ) );
		
		/* Set listener */
		acp.tabbedEditor._setObserver( id );
		
		Debug.write( "Created tab: " + id + " - " + title );
		
		/* Focus */
		acp.tabbedEditor._focusTab( id );
	},
	
	/**
	 * Brings a tab into focus
	 *
	 * @access	private
	 * @param	string		Tab ID
	 */
	_focusTab: function( id )
	{
		/* Hide all divs */
		$H( acp.tabbedEditor.openTabs ).each( function(tab)
		{
			acp.tabbedEditor._hideTab( tab.value['id'] );
		} );
		
		/* Show the correct one */
		acp.tabbedEditor._showTab( id );
		
		/* Redraw tab strip */
		acp.tabbedEditor._redrawTabstrip( id );
		
		/* Call back */
		if( ! Object.isUndefined( acp.tabbedEditor.callbacks['switch'] ) )
		{
			acp.tabbedEditor.callbacks['switch']( acp.tabbedEditor.openTabs.get( id ), this );
		}
	},
	
	/**
	 * Hide a tab
	 *
	 * @param	string		Tab ID
	 */
	_hideTab: function( id )
	{
		var _t = acp.tabbedEditor.openTabs.get( id );
		
		Debug.write( "Hiding tab " + id );
		
		/* Make sure it's not hidden for focus grab */
		$( 'tbe_editor_wrap_' + id ).show();
		$( 'tbe_editor_textarea_' + id ).show();
		$( 'tbe_editor_textarea_' + id ).value = _t.content;
		$( 'tbe_editor_textarea_' + id ).focus();
		
		/* Store scrollTop */
		if ( $( 'tbe_editor_textarea_' + id ).scrollTop )
		{
			acp.tabbedEditor.sPos[ id ] = $( 'tbe_editor_textarea_' + id ).scrollTop;
		}
		
		$( 'tbe_editor_wrap_' + id ).hide();
	},
	
	/**
	 * Hide a tab
	 *
	 * @param	string		Tab ID
	 */
	_showTab: function( id )
	{
		Debug.write( "Showing tab " + id );
		$( 'tbe_editor_wrap_' + id ).show();
		
		$( 'tbe_editor_textarea_' + id ).focus();
		
		/* Add listener */
		$( 'tbe_editor_textarea_' + id ).observe('keydown', acp.tabbedEditor._keyDownCheck.bindAsEventListener( this, id ) );
		
		/* Got a scroll pos? */
		if ( acp.tabbedEditor.sPos[ id ] )
		{
			$( 'tbe_editor_textarea_' + id ).scrollTop = acp.tabbedEditor.sPos[ id ];
		}
		
		/* Set listener */
		acp.tabbedEditor._setObserver( id );
	},
	
	/**
	 * Redraws the tab strip
	 *
	 * @access	private
	 * @param	string		ID to bring focus on
	 */
	_redrawTabstrip: function( focusId )
	{
		/* Init */
		var _c = 0;
		
		/* Remove old strip */
		if( $( 'tbe_tabs' ) )
		{
			$( 'tbe_tabs' ).remove();
		}
		
		/* Add tab strip */
		$( 'tbe_tabstrip' ).insert( new Element( 'ul', { id: 'tbe_tabs' } ).hide().addClassName( 'tbe_tabs' ) );
		
		/* Build 'em */
		$H( acp.tabbedEditor.openTabs ).each( function(tab)
		{
			/* Increment */
			_c++;
			
			/* Edited? */
			if ( tab.value['edited'] === true && ( ! tab.value['title'].match( /\*$/ ) ) )
			{
				tab.value['title'] += ' *';
			}
			
			$( 'tbe_tabs' ).insert( acp.tabbedEditor.templates['tab'].evaluate( { 'id': tab.value['id'], 'title' : tab.value['title'] } ));
			
			/* Focus */
			$( 'tbe_tab_' + tab.value['id'] ).observe('click', acp.tabbedEditor._openTab.bindAsEventListener( this, tab.value['id'] ));
			
			
			/* Go FS and Close */
			$( 'tbe_tab_f_' + tab.value['id'] ).observe('click', acp.tabbedEditor._popUp.bindAsEventListener( this, tab.value['id'] ));
			$( 'tbe_tab_c_' + tab.value['id'] ).observe('click', acp.tabbedEditor._closeTab.bindAsEventListener( this, tab.value['id'] ));
		} );
	
		/* Make one lit? */
		if ( focusId && $( 'tbe_tab_' + focusId ) )
		{
			$( 'tbe_tab_' + focusId ).addClassName( 'tbe_item_lit' );
			$( 'tbe_tabstrip' ).show();
			$( 'tbe_tabs' ).show();
		}
		else
		{
			$( 'tbe_tabstrip' ).hide();
			$( 'tbe_tabs' ).hide();
		}
	},
	
	/** 
	 * Make HTML safe
	 *
	 * @access	private
	 * @param	string		Incoming HTML
	 * @return	string		Outgoing HTML
	 */
	_HtmlIncoming: function( t )
	{
		if ( t == null )
		{
			return;
		}
		
		t = t.replace( /&/g, "&#38;" );
		t = t.replace( /</g, "&#60;" );
		t = t.replace( />/g, "&#62;" );
		t = t.replace( /"/g, "&#34;" );
		t = t.replace( /'/g, "&#039;");
	
		return t;
	},
	
	/** 
	 * Return normal HTML
	 *
	 * @access	private
	 * @param	string		Incoming HTML
	 * @return	string		Outgoing HTML
	 */
	_HtmlOutgoing: function( t )
	{
		if ( t == null )
		{
			return;
		}
		
		t = t.replace( "&#38;"   , "&", "g" );
		t = t.replace( "&#60;"   , "<", "g" );
		t = t.replace( "&#62;"   , ">", "g" );
		t = t.replace( "&#34;"   , '"', "g" );
		t = t.replace( "&#039;"  , "'", "g" );
		
		return t;
	},
	
	/**
	 * Set observer
	 * @access	private
	 * @param	Object		ID to observe
	 */
	_setObserver: function( id )
	{
		if ( ! Object.isUndefined( acp.tabbedEditor._po ) )
		{
			Debug.write( "Clearing time out for " + acp.tabbedEditor._poId );
			clearTimeout( acp.tabbedEditor._po );
		}
		
		Debug.write( "Setting time out for " + id );
		
		acp.tabbedEditor.poId = id;
		acp.tabbedEditor._po  = setTimeout( acp.tabbedEditor._timeOut, 400 );
	},
	
	/**
	 * Time out function
	 * @access	private
	 * @param	Object		Event
	 */
	_timeOut: function( e )
	{
		if ( acp.tabbedEditor.poId )
		{
			var id = acp.tabbedEditor.poId;
			var _t = acp.tabbedEditor.openTabs.get( id );
			
			if ( ! Object.isUndefined( _t ) )
			{
				acp.tabbedEditor.openTabs.set( id, { id: _t.id, title: _t.title, content: $F( 'tbe_editor_textarea_' + id ), edited: _t.edited } );
			
				acp.tabbedEditor._po  = setTimeout( acp.tabbedEditor._timeOut, 400 );
			}
		}
	},
	
	/**
	 * Check to see if this document is edited and if not, set it to edited
	 *
	 * @access	private
	 * @param	object		Event
	 * @param	string		Tab ID
	 */
	_keyDownCheck: function( e, id )
	{
		acp.tabbedEditor.setFileEditedMode( id, true );
			
		return true;
	}
}

acp.tabbedEditor.init();