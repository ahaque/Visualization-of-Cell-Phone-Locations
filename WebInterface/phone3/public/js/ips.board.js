/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.board.js - Board index code				*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/

var _idx = window.IPBoard;

_idx.prototype.board = {
	/*------------------------------*/
	/* Constructor 					*/
	init: function()
	{
		Debug.write("Initializing ips.board.js");
		
		document.observe("dom:loaded", function(){
			ipb.board.setUpForumTables();
			ipb.board.initSidebar();
		});
	},
	
	/* ------------------------------ */
	/**
	 * Inits the forum tables ready for collapsing
	*/
	setUpForumTables: function()
	{
		$$('.ipb_table').each( function(tab){
			var tmp = $( tab ).wrap( 'div' );
			$( tmp ).addClassName('table_wrap');
		});
		
		$$('.category_block').each( function(cat){
			$(cat).select('.toggle')[0].observe( 'click', ipb.board.toggleCat );			
		});	
		
		cookie = ipb.Cookie.get('toggleCats');
		
		if( cookie )
		{
			var cookies = cookie.split( ',' );
			
			//-------------------------
			// Little fun for you...
			//-------------------------
			for( var abcdefg=0; abcdefg < cookies.length; abcdefg++ )
			{
				if( cookies[ abcdefg ] )
				{
					var wrapper	= $( 'category_' + cookies[ abcdefg ] ).up('.category_block').down('.table_wrap');
					
					wrapper.hide();
					$( 'category_' + cookies[ abcdefg ] ).addClassName('collapsed');
				}
			}
		}
	},
	
	/* ------------------------------ */
	/**
	 * Show/hide a category
	 * 
	 * @var		{event}		e	The event
	*/
	toggleCat: function(e)
	{
		if( ipb.board.animating ){ return false; }
		
		
		var click = Event.element(e);
		var remove = $A();
		var wrapper = $( click ).up('.category_block').down('.table_wrap');
		Debug.write( wrapper );
		$( wrapper ).identify(); // IE8 fix
		catname = $( click ).up('h3');
		var catid = catname.id.replace('category_', '');
		
		ipb.board.animating = true;
		
		// Get cookie
		cookie = ipb.Cookie.get('toggleCats');
		if( cookie == null ){
			cookie = $A();
		} else {
			cookie = cookie.split(',');
		}
		
		Effect.toggle( wrapper, 'blind', {duration: 0.4, afterFinish: function(){ ipb.board.animating = false; } } );
		
		if( catname.hasClassName('collapsed') )
		{
			catname.removeClassName('collapsed');
			remove.push( catid );
		}
		else
		{
			new Effect.Morph( $(catname), {style: 'collapsed', duration: 0.4, afterFinish: function(){
				$( catname ).addClassName('collapsed');
				ipb.board.animating = false;
			} });
			cookie.push( catid );
		}
		
		cookie = "," + cookie.uniq().without( remove ).join(',') + ",";
		
		ipb.Cookie.set('toggleCats', cookie, 1);
		
		Event.stop( e );
	},
	
	/* ------------------------------ */
	/**
	 * Sets up the sidebar
	*/
	initSidebar: function()
	{
		if( !$('index_stats') )
		{
			return false;
		}

		if( $('index_stats').visible() )
		{
			Debug.write("Stats are visible");
			$('open_sidebar').hide();
			$('close_sidebar').show();
		}
		else
		{
			Debug.write("Stats aren't visible");
			$('open_sidebar').show();
			$('close_sidebar').hide();
		}
		
		ipb.board.animating = false;
		
		if( $('close_sidebar') )
		{
			$('close_sidebar').observe('click', function(e){
				if( ipb.board.animating ){ Event.stop(e); return; }
				
				ipb.board.animating = true;		
				new Effect.Fade( $('index_stats'), {duration: 0.4, afterFinish: function(){
					new Effect.Morph( $('categories'), { style: 'no_sidebar', duration: 0.4, afterFinish: function(){
						ipb.board.animating = false;
					 } } );
				} } );
				
				Event.stop(e);
				$('close_sidebar').toggle();
				$('open_sidebar').toggle();
				ipb.Cookie.set('hide_sidebar', '1', 1);
			});
		}
		if( $('open_sidebar') )
		{
			$('open_sidebar').observe('click', function(e){
				if( ipb.board.animating ){ Event.stop(e); return; }
				
				ipb.board.animating = true;
				
				new Effect.Morph( $('categories'), { style: 'with_sidebar', duration: 0.4, afterFinish: function(){
					$('categories').removeClassName('with_sidebar').removeClassName('no_sidebar');
					new Effect.Appear( $('index_stats'), { duration: 0.4, queue: 'end', afterFinish: function(){
						ipb.board.animating = false;
				 	} } );
				} } );
				
				Event.stop(e);
				$('close_sidebar').toggle();
				$('open_sidebar').toggle();
				
				/* Bug fix */
				if ( Prototype.Browser.Chrome )
				{
					setTimeout( "\$('index_stats').show()", 300 );
				}
				
				ipb.Cookie.set('hide_sidebar', '0', 1);
			});
		}
	},
	
	/**
	 * Check for DST
	 */
	checkDST: function()
	{
		var memberHasDst	= ipb.vars['dst_on'];
		var dstInEffect		= new Date().getDST();

		if( memberHasDst - dstInEffect != 0 )
		{
			var url = ipb.vars['base_url'] + 'app=members&module=ajax&section=dst&md5check='+ipb.vars['secure_hash'];
			
			new Ajax.Request(	url,
								{
									method: 'get',
									onSuccess: function(t)
									{
										// We don't need to do anything about this..
										return true;
									}
								}
							);
		}
	}
}

ipb.board.init();