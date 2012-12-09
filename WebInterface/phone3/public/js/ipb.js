//************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ipb.js - Global code							*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/

/* ===================================================================================================== */
/* IPB3 JS Debugging */

var Debug = {
	write: function( text ){
		if( jsDebug && !Object.isUndefined(window.console) ){
			console.log( text );
		}
	},
	dir: function( values ){
		if( jsDebug && !Object.isUndefined(window.console) ){
			console.dir( values );
		}
	},
	error: function( text ){
		if( jsDebug && !Object.isUndefined(window.console) ){
			console.error( text );
		}
	},
	warn: function( text ){
		if( jsDebug && !Object.isUndefined(window.console) ){
			console.warn( text );
		}
	},
	info: function( text ){
		if( jsDebug && !Object.isUndefined(window.console) ){
			console.info( text );
		}
	}
}

/* ===================================================================================================== */
/* OVERWRITE getOffsetParent TO CORRECT IE8 PROTOTYPE ISSUE */

Event.observe( window, 'load', function(e){
	Element.Methods.getOffsetParent = function( element ){
		//alert( "Using overloaded getOffsetParent" );
		if (element.offsetParent && element.offsetParent != document.body) return $(element.offsetParent);
		if (element == document.body) return $(element);

		while ((element = element.parentNode) && element != document.body)
		  if (Element.getStyle(element, 'position') != 'static')
		    return $(element);

		return $(document.body);
	}
});

function _getOffsetParent( element )
{
	//alert( "Using overloaded getOffsetParent" );
	if (element.offsetParent && element.offsetParent != document.body) return $(element.offsetParent);
	if (element == document.body) return $(element);

	while ((element = element.parentNode) && element != document.body)
	  if (Element.getStyle(element, 'position') != 'static')
	    return $(element);

	return $(document.body);
}

/* Set up version specifics */
Prototype.Browser.IE6 = Prototype.Browser.IE && parseInt(navigator.userAgent.substring(navigator.userAgent.indexOf("MSIE")+5)) == 6;
Prototype.Browser.IE7 = Prototype.Browser.IE && parseInt(navigator.userAgent.substring(navigator.userAgent.indexOf("MSIE")+5)) == 7;
Prototype.Browser.IE8 = Prototype.Browser.IE && !Prototype.Browser.IE6 && !Prototype.Browser.IE7;

/* Add in stuff prototype does not include */
Prototype.Browser.Chrome = Prototype.Browser.WebKit && ( navigator.userAgent.indexOf('Chrome/') > -1 );

/* ===================================================================================================== */
/* MAIN ROUTINE */

window.IPBoard = Class.create({
	namePops: [],
	vars: [],
	lang: [],
	templates: [],
	editors: $A(),
	initDone: false,
	
	initialize: function()
	{
		Debug.write("IPBjs is loading...");
		
		document.observe("dom:loaded", function(){
			
			this.Cookie.init();
			// Show a little loading graphic
			Ajax.Responders.register({
			  onLoading: function() {
			    if( !$('ajax_loading') )
				{
					if( !ipb.templates['ajax_loading'] ){ return; }
					$('ipboard_body').insert( ipb.templates['ajax_loading'] );
				}
				
				var effect = new Effect.Appear( $('ajax_loading'), { duration: 0.2 } );
			  },
			  onComplete: function() {
			
				if( !$('ajax_loading') ){ return; }
			    var effect = new Effect.Fade( $('ajax_loading'), { duration: 0.2 } );
			  }
			});
			
			// Initialize our delegation manager
			ipb.delegate.initialize();
			
			ipb.initDone = true;
			
		}.bind(this));
	},
	positionCenter: function( elem, dir )
	{
		if( !$(elem) ){ return; }
		elem_s = $(elem).getDimensions();
		window_s = document.viewport.getDimensions();
		window_offsets = document.viewport.getScrollOffsets();

		center = { 	left: ((window_s['width'] - elem_s['width']) / 2),
					 top: ((window_s['height'] - elem_s['height']) / 2)
				}
		
		if( typeof(dir) == 'undefined' || ( dir != 'h' && dir != 'v' ) )
		{
			$(elem).setStyle('top: ' + center['top'] + 'px; left: ' + center['left'] + 'px');
		}
		else if( dir == 'h' )
		{
			$(elem).setStyle('left: ' + center['left'] + 'px');
		}
		else if( dir == 'v' )
		{
			$(elem).setStyle('top: ' + center['top'] + 'px');
		}
		
		$(elem).setStyle('position: fixed');
	},
	showModal: function()
	{
		if( !$('ipb_modal') )
		{
			this.createModal();
		}
		this.modal.show();
	},
	hideModal: function()
	{
		if( !$('ipb_modal') ){ return; }
		this.modal.hide();		
	},
	createModal: function()
	{
		this.modal = new Element('div', { id: 'ipb_modal' } ).hide().addClassName('modal');
		this.modal.setStyle("width: 100%; height: 100%; position: absolute; top: 0px; left: 0px; overflow: hidden; z-index: 1000; opacity: 0.2");
		$('ipboard_body').insert({bottom: this.modal});
	},
	alert: function( message )
	{
		if( !$('ipb_alert') )
		{
			this.createAlert();
		}
		
		this.showModal();
		
		$('ipb_alert_message').update( message );
	},
	createAlert: function()
	{
		wrapper = new Element('div', { id: 'ipb_alert' } );
		icon = new Element('div', { id: 'ipb_alert_icon' } );
		message = new Element('div', { id: 'ipb_alert_message' } );
		ok_button = new Element('input', { 'type': 'button', 'value': "OK", id: 'ipb_alert_ok' });
		cancel_button = new Element('input', { 'type': 'button', 'value': "Cancel", id: 'ipb_alert_cancel' });
		
		wrapper.insert( {bottom: icon} ).insert( {bottom: message} ).insert( {bottom: ok_button} ).insert( {bottom: cancel_button} ).setStyle('z-index: 1001');
		
		$('ipboard_body').insert({bottom: wrapper});
		
		this.positionCenter( wrapper, 'h' );
	},
	editorInsert: function( content, editorid )
	{
		// If no editor id supplied, lets use the first one
		if( !editorid )	{
			Debug.dir( ipb.editors );
			var editor = $A( ipb.editors ).first();
			
			Debug.write( editor );
		} else {
			var editor = ipb.editors[ editorid ];
		}
		
		if( Object.isUndefined( editor ) )
		{
			Debug.error( "Can't find any suitable editor" );
			return;
		}
		
		editor.insert_text( content );
		editor.editor_check_focus();
	}
});

/* ===================================================================================================== */
/* IPB3 Delegation manager */
/* Simple class that allows us to specify css selectors and an associated function to run */
/* when an appropriate element is clicked */

IPBoard.prototype.delegate = {
	store: $A(),
	
	initialize: function()
	{
		document.observe('click', function(e){

			if( Event.isLeftClick(e) || Prototype.Browser.IE ) // IE doesnt provide isLeftClick info for click event
			{
				var elem = null;
				var handler = null;
			
				var target = ipb.delegate.store.find( function(item){
					elem = e.findElement( item['selector'] );
					if( elem ){
						handler = item;
						return true;
					} else {
						return false;
					}
				});
			
				if( !Object.isUndefined( target ) )
				{				
					if( handler )
					{
						Debug.write("Firing callback for selector " + handler['selector'] );
						handler['callback']( e, elem, handler['params'] );
					}
				}
			}
        })
	},
	
	register: function( selector, callback, params )
	{
		ipb.delegate.store.push( { selector: selector, callback: callback, params: params } );
	}
}

/* ===================================================================================================== */
/* IPB3 Cookies */

/* Meow */
IPBoard.prototype.Cookie = {
	store: [],
	initDone: false,
	
	set: function( name, value, sticky )
	{
		var expires = '';
		var path = '/';
		var domain = '';
		
		if( !name )
		{
			return;
		}
		
		if( sticky )
		{	
			if( sticky == 1 )
			{
				expires = "; expires=Wed, 1 Jan 2020 00:00:00 GMT";
			}
			else if( sticky == -1 ) // Delete
			{
				expires = "; expires=Thu, 01-Jan-1970 00:00:01 GMT";
			}
			else if( sticky.length > 10 )
			{
				expires = "; expires=" + sticky;
			}
		}
		if( ipb.vars['cookie_domain'] )
		{
			domain = "; domain=" + ipb.vars['cookie_domain'];
		}
		if( ipb.vars['cookie_path'] )
		{
			path = ipb.vars['cookie_path'];
		}
		
		document.cookie = ipb.vars['cookie_id'] + name + "=" + escape( value ) + "; path=" + path + expires + domain + ';';
		
		ipb.Cookie.store[ name ] = value;
		
		Debug.write( "Set cookie: " + ipb.vars['cookie_id'] + name + "=" + value + "; path=" + path + expires + domain + ';' );
	},
	get: function( name )
	{
		/* Init done yet? */
		if ( ipb.Cookie.initDone !== true )
		{
			ipb.Cookie.init();
		}
		
		if( ipb.Cookie.store[ name ] )
		{
			return ipb.Cookie.store[ name ];
		}
		
		return '';
	},
	doDelete: function( name )
	{
		Debug.write("Deleting cookie " + name);
		ipb.Cookie.set( name, '', -1 );
	},
	init: function()
	{
		// Already init?
		if ( ipb.Cookie.initDone )
		{
			return true;
		}
		
		// Init cookies by pulling in document.cookie
		skip = ['session_id', 'ipb_admin_session_id', 'member_id', 'pass_hash'];
		cookies = $H( document.cookie.replace(" ", '').toQueryParams(";") );
	
		if( cookies )
		{
			cookies.each( function(cookie){
				cookie[0] = cookie[0].strip();
				
				if( ipb.vars['cookie_id'] != '' )
				{
					if( !cookie[0].startsWith( ipb.vars['cookie_id'] ) )
					{
						return;
					}
					else
					{
						cookie[0] = cookie[0].replace( ipb.vars['cookie_id'], '' );
					}
				}
				
				if( skip[ cookie[0] ] )
				{
					return;
				}
				else
				{
					ipb.Cookie.store[ cookie[0] ] = unescape( cookie[1] || '' );
					Debug.write( "Loaded cookie: " + cookie[0] + " = " + cookie[1] );
				}				
			});
		}
		
		ipb.Cookie.initDone = true;	
	}
};

/* ===================================================================================================== */
/* Form validation */

IPBoard.prototype.validate = {
	// Checks theres actually a value
	isFilled: function( elem )
	{
		if( !$( elem ) ){ return null; }
		return !$F(elem).blank();
	},
	isNumeric: function( elem )
	{
		if( !$( elem ) ){ return null; }
		return $F(elem).match( /^[\d]+?$/ );
	},
	isMatching: function( elem1, elem2 )
	{
		if( !$( elem1 ) || !$( elem2 ) ){ return null; }
		return $F(elem1) == $F(elem2);
	},
	email: function( elem )
	{
		if( !$( elem ) ){ return null; }
		if( $F( elem ).match( /^.+@.+\..{2,4}$/ ) ){
			return true;
		} else {
			return false;
		}
	}
	/*isURL: function( elem )
	{
		if( !$(elem) ){ return null; }		
		return ( $F(elem).match( /^[A-Za-z]+:\/\/[A-Za-z0-9-_]+\\.[A-Za-z0-9-_%&\?\/.=]+$/ ) == null ) ? true : false;
	}*/
};

/* ===================================================================================================== */
/* AUTOCOMPLETE */

IPBoard.prototype.Autocomplete = Class.create( {
	
	initialize: function(id, options)
	{
		this.id = $( id ).id;
		this.timer = null;
		this.last_string = '';
		this.internal_cache = $H();
		this.pointer = 0;
		this.items = $A();
		this.observing = true;
		this.objHasFocus = null;
		this.options = Object.extend({
			min_chars: 3,
			multibox: false,
			global_cache: false,
			classname: 'ipb_autocomplete',
			templates: 	{ 
							wrap: new Template("<ul id='#{id}'></ul>"),
							item: new Template("<li id='#{id}'>#{itemvalue}</li>")
						}
		}, arguments[1] || {});
		
		//-----------------------------------------
		
		if( !$( this.id ) ){
			Debug.error("Invalid textbox ID");
			return false;
		}
		
		this.obj = $( this.id );
		
		if( !this.options.url )
		{
			Debug.error("No URL specified for autocomplete");
			return false;
		}
		
		$( this.obj ).writeAttribute('autocomplete', 'off');
		
		this.buildList();
		
		// Observe keypress
		$( this.obj ).observe('focus', this.timerEventFocus.bindAsEventListener( this ) );
		$( this.obj ).observe('blur', this.timerEventBlur.bindAsEventListener( this ) );
		$( this.obj ).observe('keypress', this.eventKeypress.bindAsEventListener( this ) );
		
	},
	
	eventKeypress: function(e)
	{
		if( ![ Event.KEY_TAB, Event.KEY_UP, Event.KEY_DOWN, Event.KEY_LEFT, Event.KEY_RIGHT, Event.KEY_RETURN ].include( e.keyCode ) ){
			return; // Not interested in anything else
		}
		
		if( $( this.list ).visible() )
		{
			switch( e.keyCode )
			{
				case Event.KEY_TAB:
				case Event.KEY_RETURN:
					this.selectCurrentItem(e);
				break;
				case Event.KEY_UP:
				case Event.KEY_LEFT:
					this.selectPreviousItem(e);
				break;
				case Event.KEY_DOWN:
				case Event.KEY_RIGHT:
					this.selectNextItem(e);
				break;
			}
			
			Event.stop(e);
		}	
	},
	
	// MOUSE & KEYBOARD EVENT
	selectCurrentItem: function(e)
	{
		var current = $( this.list ).down('.active');
		this.unselectAll();
		
		if( !Object.isUndefined( current ) )
		{
			var itemid = $( current ).id.replace( this.id + '_ac_item_', '');
			if( !itemid ){ return; }
			
			// Get value
			var value = this.items[ itemid ].replace('&amp;', '&');
			
			if( this.options.multibox )
			{
				// some logic to get current name
				if( $F( this.obj ).indexOf(',') !== -1 )
				{
					var pieces = $F( this.obj ).split(',');
					pieces[ pieces.length - 1 ] = '';

					$( this.obj ).value = pieces.join(',') + ' ';
				}
				else
				{
					$( this.obj ).value = '';
				}
				
				$( this.obj ).value = $F( this.obj ) + value + ', ';
			}
			else
			{
				$( this.obj ).value = value;
				var effect = new Effect.Fade( $(this.list), { duration: 0.3 } );
				this.observing = false;
			}			
		}
		
		$( this.obj ).focus();
	},
	
	// MOUSE EVENT
	selectThisItem: function(e)
	{
		this.unselectAll();
		
		var items = $( this.list ).immediateDescendants();
		var elem = Event.element(e);
		
		// Find the element
		while( !items.include( elem ) )
		{
			elem = elem.up();
		}
		
		$( elem ).addClassName('active');
	},
	
	// KEYBOARD EVENT
	selectPreviousItem: function(e)
	{
		var current = $( this.list ).down('.active');
		this.unselectAll();
		
		if( Object.isUndefined( current ) )
		{
			this.selectFirstItem();
		}
		else
		{
			var prev = $( current ).previous();
			
			if( prev ){
				$( prev ).addClassName('active');
			}
			else
			{
				this.selectLastItem();
			}
		}
	},
	
	// KEYBOARD EVENT
	selectNextItem: function(e)
	{
		// Get the current item
		var current = $( this.list ).down('.active');
		this.unselectAll();
		
		if( Object.isUndefined( current ) ){
			this.selectFirstItem();
		}
		else
		{
			var next = $( current ).next();
			
			if( next ){
				$( next ).addClassName('active');
			}
			else
			{
				this.selectFirstItem();
			}
		}				
	},
	
	// INTERNAL CALL
	selectFirstItem: function()
	{
		if( !$( this.list ).visible() ){ return; }
		this.unselectAll();
		
		$( this.list ).firstDescendant().addClassName('active');		
	},
	
	// INTERNAL CALL
	selectLastItem: function()
	{
		if( !$( this.list ).visible() ){ return; }
		this.unselectAll();
		
		var d = $( this.list ).immediateDescendants();
		var l = d[ d.length -1 ];
		
		if( l )
		{
			$( l ).addClassName('active');
		}
	},
	
	unselectAll: function()
	{
		$( this.list ).childElements().invoke('removeClassName', 'active');
	},
	
	// Ze goggles are blurry!
	timerEventBlur: function(e)
	{
		window.clearTimeout( this.timer );
		this.eventBlur.bind(this).delay( 0.6, e );
	},
	
	// Phew, ze goggles are focussed again
	timerEventFocus: function(e)
	{
		this.timer = this.eventFocus.bind(this).delay(0.4, e);
	},
	
	eventBlur: function(e)
	{
		this.objHasFocus = false;
		
		if( $( this.list ).visible() )
		{
			var effect = new Effect.Fade( $(this.list), { duration: 0.3 } );
		}
	},
	
	eventFocus: function(e)
	{
		if( !this.observing ){ return; }
		this.objHasFocus = true;
		
		// Keep loop going
		this.timer = this.eventFocus.bind(this).delay(0.6, e);
		
		var curValue = this.getCurrentName();
		if( curValue == this.last_string ){ return; }
		
		if( curValue.length < this.options.min_chars ){
			// Hide list if necessary
			if( $( this.list ).visible() )
			{
				var effect = new Effect.Fade( $( this.list ), { duration: 0.3, afterFinish: function(){ $( this.list ).update() }.bind(this) } );
			}
			
			return;
		}
		
		this.last_string = curValue;
		
		// Cached?
		json = this.cacheRead( curValue );
		
		if( json == false ){
			// No results yet, get them
			var request = new Ajax.Request( this.options.url + escape( curValue ),
								{
									method: 'get',
									evalJSON: 'force',
									onSuccess: function(t)
									{
										if( Object.isUndefined( t.responseJSON ) )
										{
											// Well, this is bad.
											Debug.error("Invalid response returned from the server");
											return;
										}
										
										if( t.responseJSON['error'] )
										{
											switch( t.responseJSON['error'] )
											{
												case 'requestTooShort':
													Debug.warn("Server said request was too short, skipping...");
												break;
												default:
													Debug.error("Server returned an error: " + t.responseJSON['error']);
												break;
											}
											
											return false;
										}
										
										if( t.responseText != "[]" )
										{
										
											// Seems to be OK!
											this.cacheWrite( curValue, t.responseJSON );
											this.updateAndShow( t.responseText.evalJSON() );
										}
									}.bind( this )
								}
							);
		}
		else
		{
			this.updateAndShow( json );
		}				
		
		//Debug.write( curValue );
	},
	
	updateAndShow: function( json )
	{
		if( !json ){ return; }
		
		this.updateList( json );

		if( !$( this.list ).visible() && this.objHasFocus )
		{
			Debug.write("Showing");
			var effect = new Effect.Appear( $( this.list ), { duration: 0.3, afterFinish: function(){ this.selectFirstItem(); }.bind(this) } );
		}
	},
	
	cacheRead: function( value )
	{
		if( this.options.global_cache != false )
		{
			if( !Object.isUndefined( this.options.global_cache[ value ] ) ){
				Debug.write("Read from global cache");
				return this.options.global_cache[ value ];
			}
		}
		else
		{
			if( !Object.isUndefined( this.internal_cache[ value ] ) ){
				Debug.write("Read from internal cache");
				return this.internal_cache[ value ];
			}
		}
		
		return false;
	},
	
	cacheWrite: function( key, value )
	{
		if( this.options.global_cache !== false ){
			this.options.global_cache[ key ] = value;
		} else {
			this.internal_cache[ key ] = value;
		}
		
		return true;
	},
	
	getCurrentName: function()
	{
		if( this.options.multibox )
		{
			// some logic to get current name
			if( $F( this.obj ).indexOf(',') === -1 ){
				return $F( this.obj ).strip();
			}
			else
			{
				var pieces = $F( this.obj ).split(',');
				var lastPiece = pieces[ pieces.length - 1 ];
				
				return lastPiece.strip();
			}
		}
		else
		{
			return $F( this.obj ).strip();
		}
	},
	
	buildList: function()
	{
		if( $( this.id + '_ac' ) )
		{
			return;
		}
		
		var ul = this.options.templates.wrap.evaluate({ id: this.id + '_ac' });
		$$('body')[0].insert( {bottom: ul} );
		
		var finalPos = {};
		
		// Position menu to keep it on screen
		var sourcePos = $( this.id ).viewportOffset();
		var sourceDim = $( this.id ).getDimensions();
		var delta = [0,0];
		var parent = null;
		var screenScroll = document.viewport.getScrollOffsets();
		
		if (Element.getStyle( $( this.id ), 'position') == 'absolute')
		{
			parent = element.getOffsetParent();
			delta = parent.viewportOffset();
	    }
	
		finalPos['left'] = sourcePos[0] - delta[0];
		finalPos['top'] = sourcePos[1] - delta[1] + screenScroll.top;
		
		// Now try and keep it on screen
		finalPos['top'] = finalPos['top'] + sourceDim.height;
		
		$( this.id + '_ac' ).setStyle('position: absolute; top: ' + finalPos['top'] + 'px; left: ' + finalPos['left'] + 'px;').hide();
		
		
		this.list = $( this.id + '_ac' );
	},
	
	updateList: function( json )
	{
		if( !json || !$( this.list ) ){ return; }
	
		var newitems ='';
		this.items = $A();
		
		json = $H( json );
		
		json.each( function( item )
			{		
				var li = this.options.templates.item.evaluate({ id: this.id + '_ac_item_' + item.key,
				 												itemid: item.key,
				 												itemvalue: item.value['showas'] || item.value['name'],
				 												img: item.value['img'] || '',
																img_w: item.value['img_w'] || '',
																img_h: item.value['img_h'] || ''
															});
				this.items[ item.key ] = item.value['name'];
	
				newitems = newitems + li;
			}.bind(this)
		);
		
		$( this.list ).update( newitems );
		$( this.list ).immediateDescendants().each( function(elem){
			$( elem ).observe('mouseover', this.selectThisItem.bindAsEventListener(this));
			$( elem ).observe('click', this.selectCurrentItem.bindAsEventListener(this));
			$( elem ).setStyle('cursor: pointer');
		}.bind(this));
		
		if( $( this.list ).visible() )
		{
			this.selectFirstItem();
		}
	}
				
});

/* ===================================================================================================== */
/* Values for the IPB text editor */

IPBoard.prototype.editor_values = $H({
	'templates': 		$A(),
	'colors_perrow': 	8,
	'colors': [ 		'000000' , 'A0522D' , '556B2F' , '006400' , '483D8B' , '000080' , '4B0082' , '2F4F4F' ,
						'8B0000' , 'FF8C00' , '808000' , '008000' ,	'008080' , '0000FF' , '708090' , '696969' ,
						'FF0000' , 'F4A460' , '9ACD32' , '2E8B57' , '48D1CC' , '4169E1' , '800080' , '808080' ,
						'FF00FF' , 'FFA500' , 'FFFF00' , '00FF00' ,	'00FFFF' , '00BFFF' , '9932CC' , 'C0C0C0' ,
						'FFC0CB' , 'F5DEB3' , 'FFFACD' , '98FB98' ,	'AFEEEE' , 'ADD8E6' , 'DDA0DD' , 'FFFFFF'
					],
	
	// You can add new fonts here if you wish, HOWEVER, if you use
	// non-standard fonts, users without those fonts on their computer
	// will not see them. The default list below has the generally accepted
	// safe fonts; add others at your own risk! 
	'primary_fonts': $H({ 	arial:					"Arial",
							arialblack:				"Arial Black",
							arialnarrow:			"Arial Narrow",
							bookantiqua:			"Book Antiqua",
							centurygothic:			"Century Gothic",
							comicsansms:			"Comic Sans MS",
							couriernew:				"Courier New",
							franklingothicmedium:	"Franklin Gothic Medium",
							garamond:				"Garamond",
							georgia:				"Georgia",
							impact:					"Impact",
							lucidaconsole:			"Lucida Console",
							lucidasansunicode:		"Lucida Sans Unicode",
							microsoftsansserif:		"Microsoft Sans Serif",
							palatinolinotype:		"Palatino Linotype",
							tahoma:					"Tahoma",
							timesnewroman:			"Times New Roman",
							trebuchetms:			"Trebuchet MS",
							verdana:				"Verdana"
					}),
	'font_sizes': $A([ 1, 2, 3, 4, 5, 6, 7 ])
				
});

/* ===================================================================================================== */
/* Extended objects */

// Extend RegExp with escape
Object.extend( RegExp, { 
	escape: function(text)
	{
		if (!arguments.callee.sRE)
		{
		   	var specials = [ '/', '.', '*', '+', '?', '|', '(', ')', '[', ']', '{', '}', '\\', '$' ];
		   	arguments.callee.sRE = new RegExp( '(\\' + specials.join('|\\') + ')' ); // IMPORTANT: dont use g flag
		}
		return text.replace(arguments.callee.sRE, '\\$1');
	}
});

// Extend String with URL UTF-8 escape
String.prototype.encodeUrl = function()
{
		text = this;
		var regcheck = text.match(/[\x90-\xFF]/g);
		
		if ( regcheck )
		{
			for (var i = 0; i < regcheck.length; i++)
			{
				text = text.replace(regcheck[i], '%u00' + (regcheck[i].charCodeAt(0) & 0xFF).toString(16).toUpperCase());
			}
		}
	
		return escape(text).replace(/\+/g, "%2B").replace(/%20/g, '+').replace(/\*/g, '%2A').replace(/\//g, '%2F').replace(/@/g, '%40');
};

// Extend String with URL UTF-8 escape - duplicated so it can be changed from above
String.prototype.encodeParam = function()
{
		text = this;
		var regcheck = text.match(/[\x90-\xFF]/g);

		if ( regcheck )
		{
			for (var i = 0; i < regcheck.length; i++)
			{
				text = text.replace(regcheck[i], '%u00' + (regcheck[i].charCodeAt(0) & 0xFF).toString(16).toUpperCase());
			}
		}
		
		/* Return just text as it is then encoded by prototype lib */
		return escape(text).replace(/\+/g, "%2B");
};


// Extend Date object with a function to check for DST
Date.prototype.getDST = function()
{
	var beginning	= new Date( "January 1, 2008" );
	var middle		= new Date( "July 1, 2008" );
	var difference	= middle.getTimezoneOffset() - beginning.getTimezoneOffset();
	var offset		= this.getTimezoneOffset() - beginning.getTimezoneOffset();
	
	if( difference != 0 )
	{
		return (difference == offset) ? 1 : 0;
	}
	else
	{
		return 0;
	}
};

/* ==================================================================================================== */
/* IPB3 JS Loader */

var Loader = {
	require: function( name )
	{
		document.write("<script type='text/javascript' src='" + name + ".js'></script>");
	},
	boot: function()
	{
		$A( document.getElementsByTagName("script") ).findAll(
			function(s)
			{
  				return (s.src && s.src.match(/ipb\.js(\?.*)?$/))
			}
		).each( 
			function(s) {
  				var path = s.src.replace(/ipb\.js(\?.*)?$/,'');
  				var includes = s.src.match(/\?.*load=([a-z0-9_,]*)/);
				if( ! Object.isUndefined(includes) && includes != null && includes[1] )
				{
					includes[1].split(',').each(
						function(include)
						{
							if( include )
							{
								Loader.require( path + "ips." + include );
							}
						}
					)
				}
			}
		);
	}
}

/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.global.js - Global functionality			*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/

var _global = window.IPBoard;

_global.prototype.global = {
	searchTimer: [],
	searchLastQuery: '',
	rssItems: [],
	reputation: {},
	ac_cache: $H(),
	pageJumps: $H(),
	pageJumpMenus: $H(),
	boardMarkers: $H(),
	
	/*------------------------------*/
	/* Constructor 					*/
	init: function()
	{
		Debug.write("Initializing ips.global.js");
		
		document.observe("dom:loaded", function(){
			ipb.global.initEvents();
		});
	},
	initEvents: function()
	{
		// Delegate our user popup links/warn logs
		ipb.delegate.register(".__user", ipb.global.userPopup);
		ipb.delegate.register(".warn_link", ipb.global.displayWarnLogs);
		ipb.delegate.register(".mini_friend_toggle", ipb.global.toggleFriend);
		
		/*if( ipb.vars['use_live_search'] )
		{
			$('main_search').observe("focus", ipb.global.timer_liveSearch );
			$('main_search').observe("blur", ipb.global.timer_hideLiveSearch );
			$('main_search').writeAttribute({autocomplete: "off"});
		}*/
		
		if( $('rss_feed') ){
			ipb.global.buildRSSmenu();
		}
		
		if( $('newSkin') || $('newLang') ){
			ipb.global.setUpSkinLang();
		}
		
		if( $('pm_notification') ){
			new Effect.Parallel([
				new Effect.Appear( $('pm_notification') ),
				new Effect.BlindDown( $('pm_notification') )
			], { duration: 0.5 } );
		}
		
		if( $('close_pm_notification') ){
			$('close_pm_notification').observe('click', ipb.global.closePMpopup );
		}
		
		ipb.global.buildPageJumps();
		
		ipb.delegate.register('.bbc_spoiler_show', ipb.global.toggleSpoiler);
		ipb.delegate.register('a[rel~="external"]', ipb.global.openNewWindow );
	},
	
	userPopup: function( e, elem )
	{
		Event.stop(e);
		
		var sourceid = elem.identify();
		var user = $( elem ).className.match('__id([0-9]+)');
		if( user == null || Object.isUndefined( user[1] ) ){ Debug.error("Error showing popup"); return; }
		var popid = 'popup_' + user[1] + '_user';
		var _url 		= ipb.vars['base_url'] + '&app=members&module=ajax&secure_key=' + ipb.vars['secure_hash'] + '&section=card&mid=' + user[1];
		
		ipb.namePops[ user ]	 = new ipb.Popup( popid, {
			 												type: 'balloon',
			 												ajaxURL: _url,
			 												stem: true,
															hideAtStart: false,
			 												attach: { target: elem, position: 'auto' },
			 												w: '400px'
														});
	},
	
	/* SKINNOTE: Needs cleaning up */
	displayWarnLogs: function( e, elem )
	{		
		mid = elem.id.match('warn_link_([0-9a-z]+)_([0-9]+)')[2];
		if( Object.isUndefined(mid) ){ return; }
		
		if( parseInt(mid) == 0 ){
			return false;
		}
		
		Event.stop(e);
		
		var _url 		= ipb.vars['base_url'] + '&app=members&module=ajax&secure_key=' + ipb.vars['secure_hash'] + '&section=warn&do=view&mid=' + mid;
		warnLogs = new ipb.Popup( 'warnLogs', {type: 'pane', modal: false, w: '500px', h: '500px', ajaxURL: _url, hideAtStart: false, close: '.cancel' } );
		
	},
	
	/* ------------------------------ */
	/**
	 * Toggle mini friend button
	 * 
	 * @param	{event}		e		The event
	 * @param	{int}		id		Member id
	*/
	toggleFriend: function(e, elem)
	{
		Event.stop(e);
		
		// Get ID of friend
		var id = $( elem ).id.match('friend_(.*)_([0-9]+)');
		if( Object.isUndefined( id[2] ) ){ return; }
		
		var isFriend = ( $(elem).hasClassName('is_friend') ) ? 1 : 0;
		var urlBit = ( isFriend ) ? 'remove' : 'add';
		
		var url = ipb.vars['base_url'] + "app=members&section=friends&module=ajax&do=" + urlBit + "&member_id=" + id[2] + "&md5check=" + ipb.vars['secure_hash'];
		
		// Send
		new Ajax.Request( 	url,
			 				{
								method: 'get',
								onSuccess: function(t)
								{
									switch( t.responseText )
									{
										case 'pp_friend_timeflood':
											alert( ipb.lang['cannot_readd_friend'] );
											Event.stop(e);
											break;
										case "pp_friend_already":
											alert( ipb.lang['friend_already'] );
											Event.stop(e);
											break;
										case "error":
											return true;
											break;
										default:
											
											var newIcon = ( isFriend ) ? ipb.templates['m_add_friend'].evaluate({ id: id[2]}) : ipb.templates['m_rem_friend'].evaluate({ id: id[2] });
											 
											// Find all friend links for this user
											var friends = $$('.mini_friend_toggle').each( function( fr ){
												if( $(fr).id.endsWith('_' + id[2] ) )
												{
													if ( isFriend ) {
														$(fr).removeClassName('is_friend').addClassName('is_not_friend').update( newIcon );
													} else {
														$(fr).removeClassName('is_not_friend').addClassName('is_friend').update( newIcon );
													}
												}											
											});
											
											new Effect.Highlight( $( elem ), { startcolor: ipb.vars['highlight_color'] } );
											
											// Fire an event so we can update if necessary
											document.fire('ipb:friendRemoved', { friendID: id[2] } );
											Event.stop(e);
										break;
									}
								}
							}
						);
	},
	
	/**
	* MATT
	* Toggle spammer
	*/
	toggleFlagSpammer: function( memberId, flagStatus )
	{
		if ( flagStatus == true )
		{
			if( confirm( ipb.lang['set_as_spammer'] ) )
			{
				var tid	= 0;
				var fid	= 0;
				var sid	= 0;
				
				if( typeof(ipb.topic) != 'undefined' )
				{
					tid = ipb.topic.topic_id;
					fid = ipb.topic.forum_id;
					sid = ipb.topic.start_id;
				}

				window.location = ipb.vars['base_url'] + 'app=forums&module=moderate&section=moderate&do=setAsSpammer&member_id=' + memberId + '&t=' + tid + '&f=' + fid + '&st=' + sid + '&auth_key=' + ipb.vars['secure_hash'];
				return false;
			}
			else
			{
				return false;
			}
		}
		else
		{
			alert( ipb.lang['is_spammer'] );
			return false;
		}
	},
	
	/* ------------------------------ */
	/**
	 * Toggle spoiler
	 * 
	 * @param	{event}		e		The event
	*/
	toggleSpoiler: function(e, button)
	{
		Event.stop(e);
		
		var returnvalue = $(button).up().down('.bbc_spoiler_wrapper').down('.bbc_spoiler_content').toggle();
		
		if( returnvalue.visible() )
		{
			$(button).value = 'Hide';
		}
		else
		{
			$(button).value = 'Show';
		}
	},
	
	/* ------------------------------ */
	/**
	 * Adds some events for skin/language changer
	*/
	setUpSkinLang: function()
	{
		if( $('newSkin') )
		{
			var form = $('newSkin').up('form');
			if( form )
			{
				if( $('newSkinSubmit') ){ $('newSkinSubmit').hide(); }
				$('newSkin').observe('change', function(e)
				{
					form.submit();
					return true;
				});
			}
		}
		if( $('newLang') )
		{
			var form1 = $('newLang').up('form');
			if( form1 )
			{
				if( $('newLangSubmit') ){ $('newLangSubmit').hide(); }
				$('newLang').observe('change', function(e)
				{
					form1.submit();
					return true;
				});
			}
		}
	},
					
	/* ------------------------------ */
	/**
	 * Builds the popup menu for RSS feeds
	*/
	buildRSSmenu: function()
	{
		// Get all link tags
		$$('link').each( function(link)
		{
			if( link.readAttribute('type') == "application/rss+xml" )
			{
				ipb.global.rssItems.push( ipb.templates['rss_item'].evaluate( { url: link.readAttribute('href'), title: link.readAttribute('title') } ) );
			}
		});
		
		if( ipb.global.rssItems.length > 0 )
		{
			rssmenu = ipb.templates['rss_shell'].evaluate( { items: ipb.global.rssItems.join("\n") } );
			$( 'rss_feed' ).insert( { after: rssmenu } );
			new ipb.Menu( $( 'rss_feed' ), $( 'rss_menu' ) );
		}
		else
		{
			$('rss_feed').hide();
		}
	},
	
	/* ------------------------------ */
	/**
	 * Hides the PM notification box
	 * 
	 * @param	{event}		e		The event
	*/
	closePMpopup: function(e)
	{
		if( $('pm_notification') )
		{
			new Effect.Parallel([
				new Effect.Fade( $('pm_notification') ),
				new Effect.BlindUp( $('pm_notification') )
			], { duration: 0.5 } );
		}
		
		Event.stop(e);
	},
	
	/* ------------------------------ */
	/**
	 * Initializes GD image
	 *
	 * @param	{element}	elem	The GD image element
	*/
	initGD: function( elem )
	{
		if( !$(elem) ){ return; }
		$(elem).observe('click', ipb.global.generateNewImage);
		
		if( $('gd-image-link') )
		{
			$('gd-image-link').observe('click', ipb.global.generateNewImage);
		}
	},

	/* ------------------------------ */
	/**
	 * Simulate clicking the image
	 *
	 * @param	{element}	elem	The GD image element
	*/
	generateImageExternally: function( elem )
	{
		if( !$(elem) ){ return; }
		
		$(elem).observe('click', ipb.global.generateNewImage);
	},	
	
	/* ------------------------------ */
	/**
	 * Click event for generating new GD image
	 * 
	 * @param	{event}		e	The event
	*/
	generateNewImage: function(e)
	{
		img = Event.findElement( e, 'img' );
		Event.stop(e);
		if( img == document ){ return; }
		
		// Coming from the link?		
		if( !img )
		{
			anchor	= Event.findElement( e, 'a' );
			
			if( anchor )
			{
				img		= anchor.up().down('img');
			}
		}
		
		oldSrc = img.src.toQueryParams();
		oldSrc = $H( oldSrc ).toObject();
		
		if( !oldSrc['captcha_unique_id'] ){	Debug.error("No captcha ID found"); }
		
		// Get new image
		new Ajax.Request( 
			ipb.vars['base_url'] + "app=core&module=global&section=captcha&do=refresh&captcha_unique_id=" + oldSrc['captcha_unique_id'] + '&secure_key=' + ipb.vars['secure_hash'],
			{
				method: 'get',
				onSuccess: function(t)
				{
					//Change src
					oldSrc['captcha_unique_id'] = t.responseText;
					img.writeAttribute( { src: ipb.vars['base_url'] + $H( oldSrc ).toQueryString() } );
					$F('regid').value = t.responseText;
				}
			}
		);
	},
	
	/* ------------------------------ */
	/**
	 * Registers a reputation toggle on the page
	 * 
	 * @param	{int}		id		The element that wraps rep
	 * @param	{string}	url		The URL to ping
	 * @param	{int}		rating	The current rep rating
	*/
	registerReputation: function( id, url, rating )
	{
		if( !$( id ) ){ return; }
				
		// Find rep up
		var rep_up = $( id ).down('.rep_up');
		var rep_down = $( id ).down('.rep_down');
		var sendUrl = ipb.vars['base_url'] + '&app=core&module=ajax&section=reputation&do=add_rating&app_rate=' + url.app + '&type=' + url.type + '&type_id=' + url.typeid + '&secure_key=' + ipb.vars['secure_hash'];
		
		if( $( rep_up ) ){
			$( rep_up ).observe( 'click', ipb.global.repRate.bindAsEventListener(this, 1, id) );
		}
		
		if( $( rep_down ) ){
			$( rep_down ).observe( 'click', ipb.global.repRate.bindAsEventListener(this, -1, id) );
		}
		
		ipb.global.reputation[ id ] = { obj: $( id ), url: url, sendUrl: sendUrl, currentRating: rating || 0 };
		Debug.write( "Registered reputation" );
	},
	
	/* ------------------------------ */
	/**
	 * Does a reputation rating action
	 * 
	 * @param	{event}		e		The event
	*/
	repRate: function( e )
	{
		Event.stop(e);
		var type = $A(arguments)[1];
		var id = $A(arguments)[2];
		var value = ( type == 1 ) ? 1 : -1;
		
		if( !ipb.global.reputation[ id ] ){
			return;
		} else {
			var rep = ipb.global.reputation[ id ];
		}
		
		// Send ping
		new Ajax.Request( rep.sendUrl + '&rating=' + value,
						{
							method: 'get',
							onSuccess: function( t )
							{
								if( t.responseText == 'done' )
								{
									try {									
										// It worked! Hide the rep buttons
										rep.obj.down('.rep_up').hide();
										rep.obj.down('.rep_down').hide();
									} catch(err) { }
									
									// Update the figure
									var rep_display = rep.obj.down('.rep_show');
									if( rep_display )
									{										
										['positive', 'negative', 'zero'].each(function(c){ rep_display.removeClassName(c) });
										
										var newValue = rep.currentRating + value;
										
										if( newValue > 0 )
										{
											rep_display.addClassName('positive');
										}
										else if( newValue < 0 )
										{
											rep_display.addClassName('negative');
										}
										else
										{
											rep_display.addClassName('zero');
										}
										
										rep_display.update( parseInt( rep.currentRating + value ) );
									}
								}
								else
								{
									if( t.responseText == 'nopermission' )
									{
										alert( ipb.lang['no_permission'] );
									}
									else
									{
										alert(ipb.lang['action_failed'] + ": " + t.responseText );
									}
								}
							}
						});
	
	 },
	
	/* ------------------------------ */
	/**
	 * Timer for live searching
	 * 
	 * @param	{event}		e	The event
	*/
	timer_liveSearch: function(e)
	{
		ipb.global.searchTimer['show'] = setTimeout( ipb.global.liveSearch, 400 );
	},
	
	/* ------------------------------ */
	/**
	 * TImer for hiding live search
	 * 
	 * @param	{event}		e	The event
	*/
	timer_hideLiveSearch: function(e)
	{
		ipb.global.searchTimer['hide'] = setTimeout( ipb.global.hideLiveSearch, 800 );
	},
	
	/* ------------------------------ */
	/**
	 * Actually hides live search
	 * 
	 * @param	{event}		e	The event
	*/
	hideLiveSearch: function(e)
	{
		new Effect.Fade( $('live_search_popup'), { duration: 0.4, afterFinish: function(){
			$('ajax_result').update('');
		 } } );
		
		ipb.global.searchLastQuery = '';
		clearTimeout( ipb.global.searchTimer['show'] );
		clearTimeout( ipb.global.searchTimer['hide'] );
	},
	
	/* ------------------------------ */
	/**
	 * Live search routine
	 * 
	 * @param	{event}		e	The event
	*/
	liveSearch: function(e)
	{
		// Keep loopy going
		ipb.global.timer_liveSearch();
		
		// If too few chars, dont do anything
		if( $F('main_search').length < ipb.vars['live_search_limit'] ){ return; }
		
		// Is the popup available?
		if( !$('live_search_popup') )
		{
			Debug.write("Creating popup");
			ipb.global.buildSearchPopup();
		}
		else if( !$('live_search_popup').visible() )
		{
			new Effect.Appear( $('live_search_popup'), {duration: 0.4} );
		}
		
		// Is the text the same as last time?
		if( $F('main_search') == ipb.global.searchLastQuery ){ return; } /* continue looping */
		
		// Refine search?
		var refine_search = '';
		
		if( ipb.vars['active_app'] )
		{
			refine_search += "&app_search=" + ipb.vars['active_app'];
		}
		
		if( ipb.vars['search_type'] && ipb.vars['search_type_id'] )
		{
			refine_search += '&search_type=' + ipb.vars['search_type'] + '&search_type_id=' + ipb.vars['search_type_id'];
		}
		
		if( ipb.vars['search_type_2'] && ipb.vars['search_type_id_2'] )
		{
			refine_search += '&search_type_2=' + ipb.vars['search_type_2'] + '&search_type_id_2=' + ipb.vars['search_type_id_2'];
		}		

		//Get hits
		new Ajax.Request( 
			ipb.vars['base_url'] + "app=core&module=ajax&section=livesearch&do=search&secure_key=" + ipb.vars['secure_hash'] + "&search_term=" + $F('main_search').encodeUrl() + refine_search ,
			{
				method: 'get',
				onSuccess: function(t){
					if( !$('ajax_result') ){ return; }
								 
					$('ajax_result').update( t.responseText );
					//$('ajax_result').update( t.responseText.gsub( $F('main_search'), "<span class='hl'>" + $F('main_search') + "</span>" ) );
				}
			}
		);
				
		/* Make sure we set this value so we don't run unnecessary ajax requests */
		ipb.global.searchLastQuery = $F('main_search');
	},
	
	/* ------------------------------ */
	/**
	 * Builds the popup for live search
	 * 
	 * @param	{event}		e	The event
	*/
	buildSearchPopup: function(e)
	{
		pos = $('main_search').cumulativeOffset();
		finalPos = { 
			top: pos.top + $('main_search').getHeight(),
			left: ( pos.left + 45 )
		};
		
		popup =	new Element('div', { id: 'live_search_popup' } ).hide().setStyle('top: ' + finalPos.top + 'px; left: ' + finalPos.left + 'px');
		$('content').insert({ bottom: popup });

		var refine_search	= '';
		
		if( ipb.vars['active_app'] )
		{
			refine_search += "&app_search=" + ipb.vars['active_app'];
		}
		
		if( ipb.vars['search_type'] && ipb.vars['search_type_id'] )
		{
			refine_search += '&search_type=' + ipb.vars['search_type'] + '&search_type_id=' + ipb.vars['search_type_id'];
		}
		
		if( ipb.vars['search_type_2'] && ipb.vars['search_type_id_2'] )
		{
			refine_search += '&search_type_2=' + ipb.vars['search_type_2'] + '&search_type_id_2=' + ipb.vars['search_type_id_2'];
		}

		//Get form
		new Ajax.Request( ipb.vars['base_url'] + "app=core&module=ajax&section=livesearch&do=template&secure_key=" + ipb.vars['secure_hash'] + refine_search,
		{
			method: 'get',
			onSuccess: function(t){
				popup.update( t.responseText );
				//ipb.global.liveSearch();
			}
		});		
		
		new Effect.Appear( $('live_search_popup'), {duration: 0.3} );
	},

	/* ------------------------------ */
	/**
	 * Utility function for converting bytes
	 * 
	 * @param	{int}		size	The value in bytes to convert
	 * @return	{string}			The converted string, with unit
	*/
	convertSize: function(size)
	{
		var kb = 1024;
		var mb = 1024 * 1024;
		var gb = 1024 * 1024 * 1024;
		
		if( size < kb ){ return size + " B"; }
		if( size < mb ){ return ( size / kb ).toFixed( 2 ) + " KB"; }
		if( size < gb ){ return ( size / mb ).toFixed( 2 ) + " MB"; }
		
		return ( size / gb ).toFixed( 2 ) + " GB";
	},
	
	/* ------------------------------ */
	/**
	 * Initializes stuff for image scaling
	*/
	initImageResize: function()
	{
		var dims = document.viewport.getDimensions();
		 
		ipb.global.screen_w 	= dims.width;
		ipb.global.screen_h 	= dims.height;
		ipb.global.max_w		= Math.ceil( ipb.global.screen_w * ( ipb.vars['image_resize'] / 100 ) );
	},
	
	/* ------------------------------ */
	/**
	 * Find large images in the given wrapper
	 * 
	 * @param	{element}	wrapper		The wrapper to search in
	*/
	findImgs: function( wrapper )
	{
		
		if( !$( wrapper ) ){ return; }
		if( !ipb.vars['image_resize'] ){ return; }
		
		// Resize images
		$( wrapper ).select('img.bbc_img').each( function(elem){
			if( !ipb.global.screen_w )
			{
				ipb.global.initImageResize();
			}
			ipb.global.resizeImage( elem );
		});
	},
	
	/* ------------------------------ */
	/**
	 * Resizes a large image
	 * 
	 * @param	{element}	elem	The image to resize
	*/
	resizeImage: function( elem )
	{
		if( elem.tagName != 'IMG' ){ return; }
		if( elem.readAttribute('handled') ){ Debug.write("Handled..."); return; }
		
		// Check we have a cached post size
		if( !ipb.global.post_width )
		{
			var post = $( elem ).up('.post');
			if( !Object.isUndefined( post ) )
			{
				var extra = parseInt( post.getStyle('padding-left') ) + parseInt( post.getStyle('padding-right') );
				ipb.global.post_width = $( post ).getWidth() - ( extra * 2 );
			}
		}
		
		// Uses post width if it can work it out, otherwise screen width
		var widthCompare = ( ipb.global.post_width ) ? ipb.global.post_width : ipb.global.max_w;
		
		var dims = elem.getDimensions();
		
		if( dims.width > widthCompare )
		{
			//elem.width = ipb.global.max_w;
			var percent = Math.ceil( ( widthCompare / dims.width) * 100 );
	
			if( percent < 100 )
			{
				elem.height = dims.height * ( percent / 100 );
			}
			
			var temp = ipb.templates['resized_img'];
			
			var wrap = $( elem ).wrap('div').addClassName('resized_img');
			$( elem ).insert({ before: temp.evaluate({ percent: percent, width: dims.width, height: dims.height }) });
			
			$( elem ).addClassName('resized').setStyle('cursor: pointer;');
			$( elem ).writeAttribute( 'origWidth', dims.width ).writeAttribute( 'origHeight', dims.height ).writeAttribute( 'shrunk', 1 );
			$( elem ).writeAttribute( 'newWidth', elem.width ).writeAttribute( 'newHeight', elem.height ).writeAttribute( 'handled', 1 );
			
			// SKINNOTE: Add event handler
			$( elem ).observe('click', ipb.global.enlargeImage);
		}	
	},
	
	/* ------------------------------ */
	/**
	 * Resizes a shrunk image back to normal
	 * 
	 * @param	{event}		e		The event
	*/
	enlargeImage: function(e)
	{
		var elem = Event.element(e);
		if( !elem.hasClassName('resized') ){ elem = Event.findElement(e, '.resized'); }
		
		//var img = elem.down('img.resized');
		var img = elem;
		
		if( !img ){ return; }
		
		if( $( img ).readAttribute( 'shrunk' ) == 1 )
		{
			$( img ).setStyle( 'width: ' + img.readAttribute('origWidth') + 'px; height: ' + img.readAttribute('origHeight') + 'px; cursor: pointer');
			$( img ).writeAttribute( 'shrunk', 0 );
		}
		else
		{
			$( img ).setStyle( 'width: ' + img.readAttribute('newWidth') + 'px; height: ' + img.readAttribute('newHeight') + 'px; cursor: pointer');
			$( img ).writeAttribute( 'shrunk', 1 );
		}
	},
	
	/* ------------------------------ */
	/**
	 * Registers a page jump toggle
	 * 
	 * @param	{int}	source		ID of this jump
	 * @param	{hash}	options		Options for this jump
	*/
	registerPageJump: function( source, options )
	{
		if( !source || !options ){
			return;
		}
		
		ipb.global.pageJumps[ source ] = options;	
	},
	
	/* ------------------------------ */
	/**
	 * Builds a page jump control
	*/
	buildPageJumps: function()
	{
		$$('.pagejump').each( function(elem){
			// Find the pj ID
			var classes = $( elem ).className.match(/pj([0-9]+)/);
			
			if( !classes[1] ){
				return;
			}
			
			$( elem ).identify();
			
			// Doth a popup exist?
			//Debug.write( "This wrapper has been created! " + classes[1]  );
			var temp = ipb.templates['page_jump'].evaluate( { id: 'pj_' + $(elem).identify() } );
			$$('body')[0].insert( temp );
			
			$('pj_' + $(elem).identify() + '_submit').observe('click', ipb.global.pageJump.bindAsEventListener( this, $(elem).identify() ) );
			
			// So it submits on enter
			$('pj_' + $(elem).identify() + '_input').observe('keypress', function(e){
				if( e.which == Event.KEY_RETURN )
				{
					ipb.global.pageJump( e, $(elem).identify() );
				}
			});
			
			var wrap = $( 'pj_' + $(elem).identify() + '_wrap' ).addClassName('pj' + classes[1]).writeAttribute('jumpid', classes[1] );
			
			var callback = { 
				afterOpen: function( popup ){
					try {
						$( 'pj_' + $(elem).identify() + '_input').activate();
					}
					catch(err){ }
				}
		 	};
			
			ipb.global.pageJumpMenus[ classes[1] ] = new ipb.Menu( $( elem ), $( wrap ), { stopClose: true }, callback );
		});
	},
	
	/* ------------------------------ */
	/**
	 * Executes a page jump
	 * 
	 * @param	{event}		e		The event
	 * @param	{element}	elem	The page jump element
	*/
	pageJump: function( e, elem )
	{
		if( !$( elem ) || !$( 'pj_' + $(elem).id + '_input' ) ){ return; }
		
		var value = $F( 'pj_' + $(elem).id + '_input' );
		var jumpid = $( 'pj_' + $(elem).id + '_wrap' ).readAttribute( 'jumpid' );
		
		if( value.blank() ){
			try {
				ipb.global.pageJumpMenus[ source ].doClose();
			} catch(err) { }
		}
		else
		{
			value = parseInt( value );
		}
		
		// Work out page number 
		var options = ipb.global.pageJumps[ jumpid ];
		if( !options ){ Debug.dir( ipb.global.pageJumps ); Debug.write( jumpid ); return; }
		
		var pageNum = ( ( value - 1 ) * options.perPage );
		Debug.write( pageNum );
		
		if( pageNum < 1 ){
			pageNum = 0;
		}
		/*else if( pageNum > options.totalPages ){
			pageNum = options.totalPages;
		}*/
		
		if( ipb.vars['seo_enabled'] && document.location.toString().match( ipb.vars['seo_params']['start'] ) && document.location.toString().match( ipb.vars['seo_params']['end'] ) ){
			if ( options.url.match( ipb.vars['seo_params']['varBlock'] ) )
			{
				var url = options.url + ipb.vars['seo_params']['varSep'] + options.stKey + ipb.vars['seo_params']['varSep'] + pageNum;
			}
			else
			{
				var url = options.url + ipb.vars['seo_params']['varBlock'] + options.stKey + ipb.vars['seo_params']['varSep'] + pageNum;
			}
		} else {
			var url = options.url + '&amp;' + options.stKey + '=' + pageNum;
		}
	
		url = url.replace(/&amp;/g, '&');
		// Without a negative lookbehind, http:// gets replaced with http:/ when we replace // with /
		// @see http://blog.stevenlevithan.com/archives/mimic-lookbehind-javascript
		url = url.replace(/(http:)?\/\//g, function($0, $1) { return $1 ? $0 : '/' } );
		
		document.location = url;
		
		return;
	},
	
	/* ------------------------------ */
	/**
	 * Open the link in a new window
	 * 
	 * @param	{event}		e		The event
	 * @param	{boolean}	force	Force new window regardless of host?
	*/
	openNewWindow: function(e, link, force)
	{		
		var ourHost	= document.location.host;
		var newHost = link.host;

		/**
		 * Open a new window, if link is to a different host
		 */
		if( ourHost != newHost || force )
		{
			window.open(link.href);
			Event.stop(e);
			return false;
		}
		else
		{
			return true;
		}
	},
	
	/* ------------------------------ */
	/**
	 * Registers an ajax marker
	 * 
	 * @param	{string}	id		ID of the wrapper element
	 * @param	{string}	key		Key of the current marker status (e.g. f_unread)
	 * @param	{string}	url		URL to ping
	*/
	registerMarker: function( id, key, url )
	{
		if( !$(id) || key.blank() || url.blank() ){ return; }
		if( Object.isUndefined( ipb.global.boardMarkers ) ){ return; }
		Debug.write( "Marker INIT: " + id );
		$( id ).observe('click', ipb.global.sendMarker.bindAsEventListener( this, id, key, url ) );		
	},
	
	/* ------------------------------ */
	/**
	 * Sends a marker read request
	 * 
	 * @param	{event}		e		The event
	 * @param	{string}	id		ID of containing element
	 * @param	{string}	key		Key of current marker
	 * @param	{string}	url		URL to ping
	*/
	sendMarker: function( e, id, key, url )
	{
		Event.stop(e);
	
		// Check again that the replacement exists
		if( !ipb.global.boardMarkers[ key ] ){ return; }
		
		new Ajax.Request( url + "&secure_key=" + ipb.vars['secure_hash'], 
							{
								method: 'get',
								evalJSON: 'force',
								onSuccess: function(t)
								{
									if( Object.isUndefined( t.responseJSON ) )
									{
										Debug.error("Invalid server response");
										return false;
									}
									
									if( t.responseJSON['error'] )
									{
										Debug.error( t.responseJSON['error'] );
										return false;
									}
									
									// Update icon
									$( id ).replace( ipb.global.boardMarkers[ key ] );
								}
							});
	},
	
	registerCheckAll: function( id, classname )
	{
		if( !$( id ) ){ return; }
		
		$( id ).observe('click', ipb.global.checkAll.bindAsEventListener( this, classname ) );
		
		$$('.' + classname ).each( function(elem){
			$( elem ).observe('click', ipb.global.checkOne.bindAsEventListener( this, id ) );
		});
	},
	
	checkAll: function( e, classname )
	{
		Debug.write('checkAll');
		var elem = Event.element(e);
		
		// Get all checkboxes
		var checkboxes = $$('.' + classname);
		
		if( elem.checked ){
			checkboxes.each( function(check){
				check.checked = true;
			});
		} else {
			checkboxes.each( function(check){
				check.checked = false;
			});
		}			
	},
	
	checkOne: function(e, id)
	{
		var elem = Event.element(e);
		
		if( $( id ).checked && elem.checked == false )
		{
			$( id ).checked = false;
		}		
	},
	
	updateReportStatus: function(e, reportID, noauto, noimg )
	{
		Event.stop(e);
		
		var url = ipb.vars['base_url'] + "app=core&amp;module=ajax&amp;section=reports&amp;do=change_status&secure_key=" + ipb.vars['secure_hash'] + "&amp;status=3&amp;id=" + parseInt( reportID ) + "&amp;noimg=" + parseInt( noimg ) + "&amp;noauto=" + parseInt( noauto );
		
		// Do request, see what we get
		new Ajax.Request( url.replace(/&amp;/g, '&'),
							{
								method: 'post',
								evalJSON: 'force',
								onSuccess: function( t )
								{
									if( Object.isUndefined( t.responseJSON ) )
									{
										alert( ipb.lang['action_failed'] );
										return;
									}
									
									try {
										$('rstat-' + reportID).update( t.responseJSON['img'] );
										ipb.menus.closeAll( e );
									} catch(err) {
										Debug.error( err );
									}
								}
							});
	},
	
	getTotalOffset: function(elem, top, left)
	{
		if( $( elem ).getOffsetParent() != document.body )
		{
			Debug.write( "Checking " + $(elem).id );
			var extra = $(elem).positionedOffset();
			top += extra['top'];
			left += extra['left'];
			
			return ipb.global.getTotalOffset( $( elem ).getOffsetParent(), top, left );
		}
		else
		{
			Debug.write("OK Finished!");
			return { top: top, left: left };
		}
	},
	
	// Checks a server response from an ajax request for 'nopermission'
	checkPermission: function( text )
	{
		if( text == "nopermission")
		{
			alert( ipb.lang['nopermission'] );
			return false;
		}
		
		return true;
	}
		
}

/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.menu.js - Me n you class	<3				*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier 						*/
/************************************************/

/* ipb.menus is a menu manager; ipb.Menu is a menu object */

var _menu = window.IPBoard;
_menu.prototype.menus = {
	registered: $H(),
	
	init: function()
	{
		Debug.write("Initializing ips.menu.js");
		document.observe("dom:loaded", function(){
			ipb.menus.initEvents();
		});
	},
	
	initEvents: function()
	{
		// Set document event
		Event.observe( document, 'click', ipb.menus.docCloseAll );
		
		// Auto-find menus
		$$('.ipbmenu').each( function(menu){
			id = menu.identify();
			if( $( id + "_menucontent" ) )
			{
				new ipb.Menu( menu, $( id + "_menucontent" ) );
			}
		});
	},
	
	register: function( source, obj )
	{
		ipb.menus.registered.set( source, obj );
	},
	
	docCloseAll: function( e )
	{
		if( ( !Event.isLeftClick(e) || e.ctrlKey == true || e.keyCode == 91 ) && !Prototype.Browser.IE ) // IE handles this fine anyway
		{
			// This line caused the IPB3 preview to break spectacularly.
			// Left here for the memories. Dont uncomment. Fair warning.
			//Event.stop(e);
		}
		else
		{
			ipb.menus.closeAll( e );
		}
	},
	
	closeAll: function( except )
	{
		ipb.menus.registered.each( function(menu, force){
			if( typeof( except ) == 'undefined' || ( except && menu.key != except ) )
			{
				try{
					menu.value.doClose();
				} catch(err) {
					// Assume this menu gone byebye
				}
			}
		});
	}	
}

_menu.prototype.Menu = Class.create({
	initialize: function( source, target, options, callbacks ){
		if( !$( source ) || !$( target ) ){ return; }
		if( !$( source ).id ){
			$( source ).identify();
		}
		this.id = $( source ).id + '_menu';
		this.source = $( source );
		this.target = $( target );
		this.callbacks = callbacks || {};
		
		
		this.options = Object.extend( {
			eventType: 'click',
			stopClose: false,
			offsetX: 0,
			offsetY: 0
		}, arguments[2] || {});
		
		// Set up events
		$( source ).observe( 'click', this.eventClick.bindAsEventListener( this ) );
		$( source ).observe( 'mouseover', this.eventOver.bindAsEventListener( this ) );
		$( target ).observe( 'click', this.targetClick.bindAsEventListener( this ) );

		// Set up target
		$( this.target ).setStyle( 'position: absolute;' ).hide().setStyle( { zIndex: 9999 } );
		$( this.target ).descendants().each( function( elem ){
			$( elem ).setStyle( { zIndex: 10000 } );
		});
		
		ipb.menus.register( $( source ).id, this ); 
		
		if( Object.isFunction( this.callbacks['afterInit'] ) )
		{
			this.callbacks['afterInit']( this );
		}
	},
	
	doOpen: function()
	{
		Debug.write("Menu open");
		var pos = {};
		
		_source = ( this.options.positionSource ) ? this.options.positionSource : this.source;
	
		// This is the positioned offset of the source element
		var sourcePos		= $( _source ).positionedOffset();
		
		// Cumulative offset (actual position on the page, e.g. if you scrolled down it could be higher than max resolution height)
		var _sourcePos		= $( _source ).cumulativeOffset();
		
		// Cumulative offset of your scrolling (how much you have scrolled)
		var _offset			= $( _source ).cumulativeScrollOffset();
		
		// Real source position: Actual position on page, minus scroll offset (provides position on page within viewport)
		var realSourcePos	= { top: _sourcePos.top - _offset.top, left: _sourcePos.left - _offset.left };
		
		// Dimensions of source object
		var sourceDim		= $( _source ).getDimensions();
		
		// Viewport dimensions (e.g. 1280x1024)
		var screenDim		= document.viewport.getDimensions();
		
		// Target dimensions
		var menuDim			= $( this.target ).getDimensions();
		
		// Some logging	
		Debug.write( "realSourcePos: " + realSourcePos.top + " x " + realSourcePos.left );
		Debug.write( "sourcePos: " + sourcePos.top + " x " + sourcePos.left );
		Debug.write( "scrollOffset: " + _offset.top + " x " + _offset.left );
		Debug.write( "_sourcePos: " + _sourcePos.top + " x " + _sourcePos.left );
		Debug.write( "sourceDim: " + sourceDim.width + " x " + sourceDim.height);
		Debug.write( "menuDim: " + menuDim.height );
		Debug.write( "screenDim: " + screenDim.height );
		Debug.write( "manual ofset: " + this.options.offsetX + " x " + this.options.offsetY );

		// Ok, if it's a relative parent, do one thing, else be normal
		// Getting fed up of this feature and IE bugs
		if ( Prototype.Browser.IE7 )
		{
			_a = _source.getOffsetParent();
			_b = this.target.getOffsetParent();
		}
		else
		{
			_a = _getOffsetParent( _source );
			_b = _getOffsetParent( this.target );
		}
		
		if( _a != _b )
		{
			// Left
			if( ( realSourcePos.left + menuDim.width ) > screenDim.width ){
				diff = menuDim.width - sourceDim.width;
				pos.left = _sourcePos.left - diff + this.options.offsetX;
			} else {
				if( Prototype.Browser.IE7 )
				{
					pos.left = (sourcePos.left) + this.options.offsetX;
				}
				else
				{
					pos.left = (_sourcePos.left) + this.options.offsetX;
				}
				//pos.left = _sourcePos.left + this.options.offsetX;
			}
			
			// Top		
			if( ( ( realSourcePos.top + sourceDim.height ) + menuDim.height ) > screenDim.height ){
				pos.top = _sourcePos.top - menuDim.height + this.options.offsetY;
			} else {
				pos.top = _sourcePos.top + sourceDim.height + this.options.offsetY;
			}
		}
		else
		{
			// Left
			if( ( realSourcePos.left + menuDim.width ) > screenDim.width ){
				diff = menuDim.width - sourceDim.width;
				pos.left = sourcePos.left - diff + this.options.offsetX;
			} else {
				pos.left = sourcePos.left + this.options.offsetX;
			}
			
			// Top		
			if( ( ( realSourcePos.top + sourceDim.height ) + menuDim.height ) > screenDim.height ){
				pos.top = sourcePos.top - menuDim.height + this.options.offsetY;
			} else {
				pos.top = sourcePos.top + sourceDim.height + this.options.offsetY;
			}
		}
	
		// Now set pos
		Debug.write("Menu position: " + pos.top + " x " + pos.left );
		$( this.target ).setStyle( 'top: ' + (pos.top-1) + 'px; left: ' + pos.left + 'px;' );
		
		// And show
		new Effect.Appear( $( this.target ), { duration: 0.2, afterFinish: function(e){
				if( Object.isFunction( this.callbacks['afterOpen'] ) )
				{
					this.callbacks['afterOpen']( this );
				}
		}.bind(this) } );
		
		// Set key event so we can close on ESC
		Event.observe( document, 'keypress', this.checkKeyPress.bindAsEventListener( this ) );
	},
	
	checkKeyPress: function( e )
	{
		//Debug.write( e );
		
		if( e.keyCode == Event.KEY_ESC )
		{
			this.doClose();
		}		
	},

	doClose: function()
	{		
		new Effect.Fade( $( this.target ), { duration: 0.3, afterFinish: function(e){
				if( Object.isFunction( this.callbacks['afterClose'] ) )
				{
					this.callbacks['afterClose']( this );
				}
		 }.bind( this ) } );
	},
	
	targetClick: function(e)
	{		
		if( this.options.stopClose )
		{
			Event.stop(e);
		}
	},
	
	eventClick: function(e)
	{
		Event.stop(e);
		
		if( $( this.target ).visible() ){
			
			if( Object.isFunction( this.callbacks['beforeClose'] ) )
			{
				this.callbacks['beforeClose']( this );
			}
			
			this.doClose();
		} else {
			ipb.menus.closeAll( $(this.source).id );
			
			if( Object.isFunction( this.callbacks['beforeOpen'] ) )
			{
				this.callbacks['beforeOpen']( this );
			}
			
			this.doOpen();
		}
	},
	
	eventOver: function()
	{
		
	}
});

/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.popup.js - Popup creator					*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/

/**
 * Full list of options:
 * 
 * type: 			balloon, pane
 * modal: 			true/false
 * w: 				width
 * h: 				height
 * classname: 		classname to be applied to wrapper
 * initial: 		initial content
 * ajaxURL: 		If supplied, will ping URL for content and update popup
 * close: 			element that will close popup (wont work with balloon)
 * attach: 			{ target, event, mouse, offset }
 * hideAtStart: 	Hide after creation (allows showing at a later time)
 * stem: 			true/false
 * delay: 			{ show, hide }
 */
_popup = window.IPBoard;
_popup.prototype.Popup = Class.create({
		
	initialize: function( id, options, callbacks )
	{
		/* Set up properties */
		this.id				= '';
		this.wrapper		= null;
		this.inner			= null;
		this.stem			= null;
		this.options		= {};
		this.timer			= [];
		this.ready			= false;
		this._startup		= null;
		this.hideAfterSetup	= false;
		this.eventPairs		= {	'mouseover': 	'mouseout',
								'mousedown': 	'mouseup'
							  };
		this._tmpEvent 		= null;
		
		/* Now run */
		this.id = id;
		this.options = Object.extend({
			type: 				'pane',
			w: 					'500px',
			modal: 				false,
			modalOpacity: 		0.4,
			hideAtStart: 		true,
			delay: 				{ show: 0, hide: 0 },
			defer: 				false,
			hideClose: 			false,
			closeContents: 		ipb.templates['close_popup']		
		}, arguments[1] || {});
		
		this.callbacks = callbacks || {};
		
		// Are we deferring the load?
		if( this.options.defer && $( this.options.attach.target ) )
		{
			this._defer = this.init.bindAsEventListener( this );
			$( this.options.attach.target ).observe( this.options.attach.event, this._defer );
			
			if( this.eventPairs[ this.options.attach.event ] )
			{
				this._startup = function(e){ this.hideAfterSetup = true; this.hide() }.bindAsEventListener( this );
				$( this.options.attach.target ).observe( this.eventPairs[ this.options.attach.event ], this._startup  );
			}
		}
		else
		{
			this.init();
		}
	},
	
	init: function()
	{
		try {
			Event.stopObserving( $( this.options.attach.target ), this.options.attach.event, this._defer );
		} catch(err) { }
		
		this.wrapper = new Element('div', { 'id': this.id + '_popup' } ).setStyle('z-index: 16000').hide().addClassName('popupWrapper');
		this.inner = new Element('div', { 'id': this.id + '_inner' } ).addClassName('popupInner');
		
		if( this.options.w ){ this.inner.setStyle( 'width: ' + this.options.w ); }
		if( this.options.h ){ this.inner.setStyle( 'max-height: ' + this.options.h ); }
		this.wrapper.insert( this.inner );
		
		if( this.options.hideClose != true )
		{
			this.closeLink = new Element('div', { 'id': this.id + '_close' } ).addClassName('popupClose').addClassName('clickable');
			this.closeLink.update( this.options.closeContents );
			this.closeLink.observe('click', this.hide.bindAsEventListener( this ) );
			this.wrapper.insert( this.closeLink );
		}
		
		$$('body')[0].insert( this.wrapper );
		
		if( this.options.classname ){ this.wrapper.addClassName( this.options.classname ); }
		
		if( this.options.initial ){
			this.update( this.options.initial );
		}
		
		// If we are updating with ajax, handle the show there
		if( this.options.ajaxURL ){
			this.updateAjax();
			setTimeout( this.continueInit.bind(this), 80 );
		} else {
			this.ready = true;
			this.continueInit();
		}
		
		// Need to set a timeout for continue,
		// in case ajax is still running
	},
	
	continueInit: function()
	{
		if( !this.ready )
		{
			setTimeout( this.continueInit.bind(this), 80 );
			return;
		}
		
		//Debug.write("Continuing...");
		// What are we making?
		if( this.options.type == 'balloon' ){
			this.setUpBalloon();
		} else {
			this.setUpPane();
		}
		
		// Set up close event
		try {
			if( this.options.close ){
				closeElem = $( this.wrapper ).select( this.options.close )[0];
				
				if( Object.isElement( closeElem ) )
				{
					$( closeElem ).observe( 'click', this.hide.bindAsEventListener( this ) );
				}
			}
		} catch( err ) {
			Debug.write( err );
		}
		
		// Callback
		if( Object.isFunction( this.callbacks['afterInit'] ) )
		{
			this.callbacks['afterInit']( this );
		}
		
		if( !this.options.hideAtStart && !this.hideAfterSetup )
		{
			this.show();
		}
		if( this.hideAfterSetup && this._startup )
		{	
			Event.stopObserving( $( this.options.attach.target ), this.eventPairs[ this.options.attach.event ], this._startup );
		}
	},
	
	updateAjax: function()
	{
		new Ajax.Request( this.options.ajaxURL,
						{
							method: 'get',
							onSuccess: function(t)
							{
								if( t.responseText != 'error' )
								{
									if( t.responseText == 'nopermission' )
									{
										alert( ipb.lang['no_permission'] );
										return;
									}
									
									//Debug.write( t.responseText );
									Debug.write( "AJAX done!" );
									this.update( t.responseText );
									this.ready = true;
									
									// Callback
									if( Object.isFunction( this.callbacks['afterAjax'] ) )
									{
										this.callbacks['afterAjax']( this, t.responseText );
									}
								}
								else
								{
									Debug.write( t.responseText );
									return;
								}
							}.bind(this)
						});
	},
	
	show: function(e)
	{
		if( e ){ Event.stop(e); }
		
		if( this.timer['show'] ){
			clearTimeout( this.timer['show'] );
		}
		
		if( this.options.delay.show != 0 ){
			this.timer['show'] = setTimeout( this._show.bind( this ), this.options.delay.show );
		} else {
			this._show(); // Just show it
		}
	},
	
	hide: function(e)
	{
		if( e ){ Event.stop(e); }
		if( this.document_event ){
			Event.stopObserving( document, 'click', this.document_event );
		}
		
		if( this.timer['hide'] ){
			clearTimeout( this.timer['hide'] );
		}
				
		if( this.options.delay.hide != 0 ){
			this.timer['hide'] = setTimeout( this._hide.bind( this ), this.options.delay.hide );
		} else {
			this._hide(); // Just hide it
		}
	},
	
	_show: function()
	{		
		if( this.options.modal == false ){
			new Effect.Appear( $( this.wrapper ), { duration: 0.3, afterFinish: function(){
				if( Object.isFunction( this.callbacks['afterShow'] ) )
				{
					this.callbacks['afterShow']( this );
				}
			}.bind(this) } );
			this.document_event = this.handleDocumentClick.bindAsEventListener(this);
			Event.observe( document, 'click', this.document_event );
		} else {
			new Effect.Appear( $('document_modal'), { duration: 0.3, to: this.options.modalOpacity, afterFinish: function(){
				new Effect.Appear( $( this.wrapper ), { duration: 0.4, afterFinish: function(){
					if( Object.isFunction( this.callbacks['afterShow'] ) )
					{
						this.callbacks['afterShow']( this );
					}
			 	}.bind(this) } )
			}.bind(this) });
		}
	},
	
	_hide: function()
	{
		if( this._tmpEvent != null )
		{
			Event.stopObserving( $( this.wrapper ), 'mouseout', this._tmpEvent );
			this._tmpEvent = null;
		}
		
		if( this.options.modal == false ){
			new Effect.Fade( $( this.wrapper ), { duration: 0.3, afterFinish: function(){
				if( Object.isFunction( this.callbacks['afterHide'] ) )
				{
					this.callbacks['afterHide']( this );
				}
			}.bind(this) } );
		} else {
			new Effect.Fade( $( this.wrapper ), { duration: 0.3, afterFinish: function(){
				new Effect.Fade( $('document_modal'), { duration: 0.2, afterFinish: function(){
					if( Object.isFunction( this.callbacks['afterHide'] ) )
					{
						this.callbacks['afterHide']( this );
					}
				}.bind(this) } )
			}.bind(this) });
		}
	},
	
	handleDocumentClick: function(e)
	{
		if( !Event.element(e).descendantOf( this.wrapper ) )
		{
			this._hide(e);
		}
	},
	
	update: function( content )
	{
		this.inner.update( content );
	},
	
	setUpBalloon: function()
	{
		// Are we attaching?
		if( this.options.attach )
		{
			var attach = this.options.attach;
			
			if( attach.target && $( attach.target ) )
			{
				if( this.options.stem == true )
				{
					this.createStem();
				}
				
				// Get position
				if( !attach.position ){ attach.position = 'auto'; }
				if( Object.isUndefined( attach.offset ) ){ attach.offset = { top: 0, left: 0 } }
				if( Object.isUndefined( attach.offset.top ) ){ attach.offset.top = 0 }
				if( Object.isUndefined( attach.offset.left ) ){ attach.offset.left = 0 }
				
				if( attach.position == 'auto' )
				{
					Debug.write("Popup: auto-positioning");
					var screendims 		= document.viewport.getDimensions();
					var screenscroll 	= document.viewport.getScrollOffsets();
					var toff			= $( attach.target ).viewportOffset();
					var wrapSize 		= $( this.wrapper ).getDimensions();
					var delta 			= [0,0];
					
					if (Element.getStyle( $( attach.target ), 'position') == 'absolute')
					{
						var parent = element.getOffsetParent();
						delta = parent.viewportOffset();
				    }
				
					toff['left'] = toff[0] - delta[0];
					toff['top'] = toff[1] - delta[1] + screenscroll.top;
					
					//Debug.write( toff['left'] + "    " + toff['top'] );
					// Need to figure out if it will be off-screen
					var start 	= 'top';
					var end 	= 'left';
					
					//Debug.write( "Target offset top: " + toff.top + ", wrapSize Height: " + wrapSize.height + ", screenscroll top: " + screenscroll.top);
					if( ( toff.top - wrapSize.height - attach.offset.top ) < ( 0 + screenscroll.top ) ){
						var start = 'bottom';
					}
					
					if( ( toff.left + wrapSize.width - attach.offset.left ) > ( screendims.width - screenscroll.left ) ){
						var end = 'right';
					}
					
					finalPos = this.position( start + end, { target: $( attach.target ), content: $( this.wrapper ), offset: attach.offset } );
					
					if( this.options.stem == true )
					{
						finalPos = this.positionStem( start + end, finalPos );
					}
				}
				else
				{
					Debug.write("Popup: manual positioning");
					
					finalPos = this.position( attach.position, { target: $( attach.target ), content: $( this.wrapper ), offset: attach.offset } );
					
					if( this.options.stem == true )
					{
						finalPos = this.positionStem( attach.position, finalPos );
					}
				}
				
				// Add mouse events
				if( !Object.isUndefined( attach.event ) ){
					$( attach.target ).observe( attach.event, this.show.bindAsEventListener( this ) );
					
					if( attach.event != 'click' && !Object.isUndefined( this.eventPairs[ attach.event ] ) ){
						$( attach.target ).observe( this.eventPairs[ attach.event ], this.hide.bindAsEventListener( this ) );
					}
						
					$( this.wrapper ).observe( 'mouseover', this.wrapperEvent.bindAsEventListener( this ) );					
				}				
			}
		}
		
		Debug.write("Popup: Left: " + finalPos.left + "; Top: " + finalPos.top);
		$( this.wrapper ).setStyle( 'top: ' + finalPos.top + 'px; left: ' + finalPos.left + 'px; position: absolute;' );		
	},
	
	wrapperEvent: function(e)
	{
		if( this.timer['hide'] )
		{
			// Cancel event now
			clearTimeout( this.timer['hide'] );
			this.timer['hide'] = null;
			
			if( this.options.attach.event && this.options.attach.event == 'mouseover' )
			{
				// Set new event to account for mouseout of the popup,
				// but only if we don't already have one - otherwise we get
				// expontentially more event calls. Bad.
				if( this._tmpEvent == null ){
					this._tmpEvent = this.hide.bindAsEventListener( this );
					$( this.wrapper ).observe('mouseout', this._tmpEvent );
				}
			}
		}
	},
	
	positionStem: function( pos, finalPos )
	{
		var stemSize = { height: 16, width: 31 };
		var wrapStyle = {};
		var stemStyle = {};
		
		switch( pos.toLowerCase() )
		{
			case 'topleft':
				wrapStyle = { marginBottom: stemSize.height + 'px' };
				stemStyle = { bottom: -(stemSize.height) + 'px', left: '5px' };
				finalPos.left = finalPos.left - 15;
				break;
			case 'topright':
				wrapStyle = { marginBottom: stemSize.height + 'px' };
				stemStyle = { bottom: -(stemSize.height) + 'px', right: '5px' };
				finalPos.left = finalPos.left + 15;
				break;
			case 'bottomleft':
				wrapStyle = { marginTop: stemSize.height + 'px' };
				stemStyle = { top: -(stemSize.height) + 'px', left: '5px' };
				finalPos.left = finalPos.left - 15;
				break;
			case 'bottomright':
				wrapStyle = { marginTop: stemSize.height + 'px' };
				stemStyle = { top: -(stemSize.height) + 'px', right: '5px' };
				finalPos.left = finalPos.left + 15;
				break;
		}
		
		$( this.wrapper ).setStyle( wrapStyle );
		$( this.stem ).setStyle( stemStyle ).setStyle('z-index: 6000').addClassName( pos.toLowerCase() );
		
		return finalPos;
	},
	
	position: function( pos, v )
	{
		finalPos = {};
		var toff			= $( v.target ).viewportOffset();
		var tsize	 		= $( v.target ).getDimensions();
		var wrapSize 		= $( v.content ).getDimensions();
		var screenscroll 	= document.viewport.getScrollOffsets();
		var offset 			= v.offset;
		var delta			= [0,0];
		
		if (Element.getStyle( $( v.target ), 'position') == 'absolute')
		{
			var parent = element.getOffsetParent();
			delta = parent.viewportOffset();
	    }
		
		toff['left'] = toff[0] - delta[0];
		toff['top'] = toff[1] - delta[1];
		
		if( !Prototype.Browser.Opera ){
			toff['top'] += screenscroll.top;
		}
		
		switch( pos.toLowerCase() )
		{
			case 'topleft':
				finalPos.top = ( toff.top - wrapSize.height - tsize.height ) - offset.top;
				finalPos.left = toff.left + offset.left;						
				break;
			case 'topright':
			 	finalPos.top = ( toff.top - wrapSize.height - tsize.height ) - offset.top;
				finalPos.left = ( toff.left - ( wrapSize.width - tsize.width ) ) - offset.left;
				break;
			case 'bottomleft':
				finalPos.top = ( toff.top + tsize.height ) + offset.top;
				finalPos.left = toff.left + offset.left;
				break;
			case 'bottomright':
				finalPos.top = ( toff.top + tsize.height ) + offset.top;
				finalPos.left = ( toff.left - ( wrapSize.width - tsize.width ) ) - offset.left;
				break;
		}
		
		return finalPos;
	},
	
	createStem: function()
	{
		this.stem = new Element('div', { id: this.id + '_stem' } ).update('&nbsp;').addClassName('stem');
		this.wrapper.insert( { top: this.stem } );
	},
	
	setUpPane: function()
	{
		// Does the document have a modal blackout?
		if( !$('document_modal') ){
			this.createDocumentModal();
		}
		
		this.positionPane();	
	},
	
	positionPane: function()
	{
		// Position it in the middle
		var elem_s = $( this.wrapper ).getDimensions();
		var window_s = document.viewport.getDimensions();
		var window_offsets = document.viewport.getScrollOffsets();

		var center = { 	left: ((window_s['width'] - elem_s['width']) / 2),
					 	top: (((window_s['height'] - elem_s['height']) / 2)/2)
					}
					
		if( center.top < 10 ){ center.top = 10; }
					
		$( this.wrapper ).setStyle('top: ' + center['top'] + 'px; left: ' + center['left'] + 'px; position: fixed;');
	},
			
	createDocumentModal: function()
	{
		var pageSize = $('ipboard_body').getDimensions();
		var viewSize = document.viewport.getDimensions();
		
		var dims = [];
		
		//Debug.dir( pageSize );
		//Debug.dir( viewSize );
		
		if( viewSize['height'] < pageSize['height'] ){
			dims['height'] = pageSize['height'];
		} else {
			dims['height'] = viewSize['height'];
		}
		
		if( viewSize['width'] < pageSize['width'] ){
			dims['width'] = pageSize['width'];
		} else {
			dims['width'] = viewSize['width'];
		}
		
		var modal = new Element( 'div', { 'id': 'document_modal' } ).addClassName('modal').hide();
		modal.setStyle('width: ' + dims['width'] + 'px; height: ' + dims['height'] + 'px; position: absolute; top: 0px; left: 0px; z-index: 15000;');
		
		$$('body')[0].insert( modal );
	},
	
	getObj: function()
	{
		return $( this.wrapper );
	}
});


ipb = new IPBoard;
ipb.global.init();
ipb.menus.init();