/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* easeter.js - Easter Eggs Shhhh				*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Brandon Farber						*/
/************************************************/

var _easter = window.IPBoard;
var timer;
var comet;

_easter.prototype.easter = {
	snowFlakesCount:	35,
	
	snowFlakesColors:	new Array("#AAAACC","#DDDDFF","#CCCCDD","#F3F3F3","#F0FFFF"),
	
	snowFlakesTypes:	new Array("Arial Black","Arial Narrow","Times","Comic Sans MS"),
	
	snowFlakesMaxSize:	22,
	snowFlakesMinSize:	8,
	
	cometColors:		new Array('#ff0000','#00ff00','#ffffff','#ff00ff','#ffa500','#ffff00','#00ff00','#ffffff','ff00ff'),
	
	/**	No more config **/
	
	snow:				new Array(),
	coordinates:		new Array(),
	positions:			new Array(),
	movements:			new Array(),
	browserWidth:		document.viewport.getWidth(),
	browserHeight:		document.viewport.getHeight(),
	mouseX:				0,
	mouseY:				0,
	
	/** Random number generator **/
	getRandomNumber:	function(range)
	{
		return Math.floor( range * Math.random() );
	},
	
	/** Initialization **/
	init:				function()
	{
		document.observe("dom:loaded", function(){
			/** Snowflakes **/
			var snowsizerange = ipb.easter.snowFlakesMaxSize - ipb.easter.snowFlakesMinSize;

			for ( i=0; i<=ipb.easter.snowFlakesCount; i++ ) 
			{
				var blah = new Element( 'span', { id: "s" + i } ).update( '*' );
				blah.absolutize();
				$$('body')[0].insert( { top: blah } );
				
				ipb.easter.coordinates[i]		= 0;                      
		    	ipb.easter.positions[i]			= Math.random()*15;
		    	ipb.easter.movements[i]			= 0.03 + Math.random()/10;
	
				$( "s" + i ).setStyle( { fontFamily: ipb.easter.snowFlakesTypes[ ipb.easter.getRandomNumber(ipb.easter.snowFlakesTypes.length) ] } );
				$( "s" + i ).size				= ipb.easter.getRandomNumber(snowsizerange) + ipb.easter.snowFlakesMinSize;
				$( "s" + i ).setStyle( { fontSize: $( "s" + i ).size } );
				$( "s" + i ).setStyle( { color: ipb.easter.snowFlakesColors[ ipb.easter.getRandomNumber(ipb.easter.snowFlakesColors.length) ] } );
				$( "s" + i ).sink				= .6 * $( "s" + i ).size/5;
				$( "s" + i ).posx				= ipb.easter.getRandomNumber( ipb.easter.browserWidth - $( "s" + i ).size );
				$( "s" + i ).posy				= ipb.easter.getRandomNumber( 2 * ipb.easter.browserHeight - ipb.easter.browserHeight - 2 * $( "s" + i ).size );
				$( "s" + i ).setStyle( { left: $( "s" + i ).posx + 'px' } );
				$( "s" + i ).setStyle( { top: $( "s" + i ).posy + 'px' } );
			}
			
			ipb.easter.moveSnow();
			
			/** Comet Trail **/
			for (i = 0; i < 10; i++ )
			{
				var blah = new Element( 'div', { id: "dots" + i } );
				blah.absolutize();
				blah.setStyle(
					{
						position: 'absolute',
						top: '0px',
						left: '0px',
						width: i/2 + 'px',
						height: i/2 + 'px',
						background: '#ff0000',
						fontSize: i/2
					}
				)

				$$('body')[0].insert( { bottom: blah } );
			}
			
			Event.observe( document, "mousemove", ipb.easter.captureMouse );
			ipb.easter.mouseTrail();
			
			$('branding').update( "<img src='admin/skin_cp/images/const.gif'> <marquee width='70%'><blink><FONT size=9>UNDER CONSTRUCTION</FONT></blink></marquee>" );
		});
	},

	/** Move snow **/
	moveSnow:			function()
	{
		for ( i=0; i <= ipb.easter.snowFlakesCount; i++ )
		{
			ipb.easter.coordinates[i]		+= ipb.easter.movements[i];
			$( "s" + i ).posy		+= $( "s" + i ).sink;

			$( "s" + i ).setStyle( { left: Math.floor($( "s" + i ).posx + ipb.easter.positions[i] * Math.sin( ipb.easter.coordinates[i] )) + 'px' } );
			$( "s" + i ).setStyle( { top:  Math.floor($( "s" + i ).posy) + 'px' } );

			if ( $( "s" + i ).posy >= ipb.easter.browserHeight - 2 * $( "s" + i ).size || 
					parseInt($( "s" + i ).style.left) > ( ipb.easter.browserHeight - 3 * ipb.easter.positions[i] ) )
			{
				$( "s" + i ).posx	= ipb.easter.getRandomNumber( ipb.easter.browserWidth - $( "s" + i ).size );
				$( "s" + i ).posy	= 0;
			}
		}

		timer = setTimeout( "ipb.easter.moveSnow()", 50 );
	},

	captureMouse:		function(e)
	{
		ipb.easter.mouseX	= Event.pointerX(e);
		ipb.easter.mouseY	= Event.pointerY(e);
	},

	mouseTrail:			function()
	{
		for ( i = 0; i < 10; i++ )
		{
			var randcolours	= ipb.easter.cometColors[ ipb.easter.getRandomNumber( ipb.easter.cometColors.length ) ];
			$( "dots" + i ).setStyle( { background: randcolours } );

			if (i < 9)
			{
				$( "dots" + i ).setStyle( { top: parseInt($( "dots" + (i+1) ).getStyle('top') ) + 'px' } );
				$( "dots" + i ).setStyle( { left: parseInt($( "dots" + (i+1) ).getStyle('left') ) + 'px' } );
			} 
			else
			{
				$( "dots" + i ).setStyle( { top: ipb.easter.mouseY + 'px' } );
				$( "dots" + i ).setStyle( { left: ipb.easter.mouseX + 'px' } );
			}
		}
		
		comet = setTimeout( "ipb.easter.mouseTrail()", 10 );
	}
};

ipb.easter.init();