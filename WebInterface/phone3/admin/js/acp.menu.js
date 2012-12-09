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
	readyForMoving: false,
	
	init: function()
	{
		Debug.write("Initializing acp.menu.js");
		document.observe("dom:loaded", function(){
			ipb.menus.readyForMoving = true;
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
				//$$('body')[0].insert( $( id + "_menucontent" ) );
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
			if( ( except && menu.key != except ) )
			{
				menu.value.doClose();
			}
		});
	}	
}

ipb.menus.init();

_menu.prototype.Menu = Class.create({
	initialize: function( source, target, options ){
		if( !$( source ) || !$( target ) ){ return; }
		if( !$( source ).id ){
			$( source ).identify();
		}
		this.id = $( source ).id + '_menu';
		this.source = $( source );
		this.target = $( target );
		
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
		
		if( this.options.callback )
		{
			this.options.callback.each( function(c){
				$( source ).observe( c.key, c.value );
			});
		}
		
		if( ipb.menus.readyForMoving ){
			$$('body')[0].insert( { bottom: $( this.target ) } );
		} else {
			// Move into body
			document.observe("dom:loaded", function(){
				$$('body')[0].insert( { bottom: $( this.target ) } );
			}.bind(this));
		}
		
		// Set up target
		$( this.target ).setStyle( 'position: absolute;' ).hide().setStyle( { zIndex: 20000 } );
		$( this.target ).descendants().each( function( elem ){
			$( elem ).setStyle( { zIndex: 20000 } );
		});
		
		ipb.menus.register( $( source ).id, this ); 
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
		Debug.write( "menuDim: " + menuDim.height + " x " + menuDim.width );
		Debug.write( "screenDim: " + screenDim.height );
		Debug.write( "manual ofset: " + this.options.offsetX + " x " + this.options.offsetY );

		// Ok, if it's a relative parent, do one thing, else be normal
		_a      = _getOffsetParent( _source );
		_b      = _getOffsetParent( this.target );

		if( _a != _b )
		{
			Debug.write( "a unequal to b" );
			// Left
			if( ( realSourcePos.left + menuDim.width ) > screenDim.width ){
				diff = menuDim.width - sourceDim.width;
				
				if( Prototype.Browser.IE7 )
				{
					pos.left = (sourcePos.left - diff) + this.options.offsetX;
				}
				else
				{
					pos.left = (_sourcePos.left - diff) + this.options.offsetX;
				}
			} else {
				pos.left = _sourcePos.left + this.options.offsetX;
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
			Debug.write( "a equal to b" );
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
		
		/*finalPos['left'] = sourcePos[0] - delta[0];
		finalPos['top'] = sourcePos[1] - delta[1] + screenScroll.top;*/
		
		// Now try and keep it on screen
		/*if( ( finalPos['left'] + menuDim.width ) > screenDim.width ){
			finalPos['left'] = finalPos['left'] - ( menuDim.width - sourceDim.width );
		}
		
		if( ( ( finalPos['top'] + sourceDim.height ) + menuDim.height ) > ( screenDim.height + screenScroll.top ) ){
			finalPos['top'] = finalPos['top'] - menuDim.height;
		} else {
			finalPos['top'] = finalPos['top'] + sourceDim.height;
		}
		
		finalPos['left'] += this.options.offsetX;
		finalPos['top'] += this.options.offsetY;*/
		
		// Now position
		//$( this.target ).setStyle( 'top: ' + (finalPos.top-1) + 'px; left: ' + finalPos.left + 'px;' );
		
		// Left
		/*if( ( sourcePos.left + menuDim.width ) > ( screenDim.width ) ){
			diff = menuDim.width - sourceDim.width;
			pos.left = sourcePos.left - diff + this.options.offsetX;
		} else {
			pos.left = sourcePos.left + this.options.offsetX;
		}
		
		// Top
		if( ( ( sourcePos.top + sourceDim.height ) + menuDim.height ) > ( screenDim.height + screenScroll.top ) ){
			pos.top = sourcePos.top - menuDim.height + this.options.offsetY;
		} else {
			pos.top = sourcePos.top + sourceDim.height + this.options.offsetY;
		}
		
		// Now set pos
		$( this.target ).setStyle( 'top: ' + (pos.top-1) + 'px; left: ' + pos.left + 'px;' );*/
		
		// And show
		new Effect.Appear( $( this.target ), { duration: 0.2 } );
	},
	
	doClose: function()
	{
		new Effect.Fade( $( this.target ), { duration: 0.3 } );
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
			this.doClose();
		} else {
			ipb.menus.closeAll( $(this.source).id );
			this.doOpen();
		}
	},
	
	eventOver: function()
	{
		
	}
});
 