/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.board.js - Board index code				*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/

var _search = window.IPBoard;

_search.prototype.search = {
	checks: [],
	
	/*------------------------------*/
	/* Constructor 					*/
	init: function()
	{
		Debug.write("Initializing ips.search.js");
		
		document.observe("dom:loaded", function(){
			// set up calendars
			if( $('date_start') && $('date_start_icon') )
			{
				$('date_start_icon').observe('click', function(e){
					ipb.search.calendar_start = new CalendarDateSelect( $('date_start'), { year_range: 6, time: true, close_on_click: true } );
				});
			}
			
			if( $('date_end') && $('date_end_icon') )
			{
				$('date_end_icon').observe('click', function(e){
					ipb.search.calendar_start = new CalendarDateSelect( $('date_end'), { year_range: 6, time: true, close_on_click: true } );
				});
			}
		});
	},
	
	initSearchForm: function()
	{		
		// Get all checkboxes
		$$('input.filter').each( function(check){
			appID = check.identify().match('select_app_(.*)')[1];
			if( appID && appID != 'all' )
			{
				if( $('app_filter_' + appID) )
				{
					$('app_filter_' + appID ).wrap('div', { id: 'app_filter_' + appID + '_wrap' } ).hide(); //Wrap it in a div because scriptaculous is funky with fieldsets
				}
				ipb.search.checks.push( appID );
			}
			$( check ).observe('click', ipb.search.doFilters );
		});
		
		if( $('author') )
		{
			// Autocomplete stuff
			document.observe("dom:loaded", function(){
				var url = ipb.vars['base_url'] + 'secure_key=' + ipb.vars['secure_hash'] + '&app=core&module=ajax&section=findnames&do=get-member-names&name=';
				var ac = new ipb.Autocomplete( $('author'), { multibox: false, url: url, templates: { wrap: ipb.templates['autocomplete_wrap'], item: ipb.templates['autocomplete_item'] } } );
			});
		}
	},
	
	doFilters: function(e)
	{
		elem = Event.element(e);
		appid = elem.id.replace('select_app_', '');
		
		//if( !ipb.search.checks.length ){ return; }
		
		if( $( elem ) && $( elem ).id == 'select_app_all' )
		{
			if( $('select_app_all').checked )
			{
				ipb.search.checks.each( function(check){
					if(	$('select_app_' + check) ){
						$('select_app_' + check).checked = false;
					}
					if(	$('app_filter_' + check + '_wrap') )
					{
						new Effect.BlindUp( $('app_filter_' + check + '_wrap' ), { duration: 0.3 } );
					}
				});
			}
			else
			{
				// No change
			}
		}
		else if( $( elem ) )
		{
			if( $(elem).checked )
			{
				// Uncheck everything
				$('select_app_all').checked = false;
				
				if( $('app_filter_' + appid + '_wrap' ) && !$('app_filter_' + appid + '_wrap').visible() )
				{
					new Effect.BlindDown( $('app_filter_' + appid + '_wrap' ), { duration: 0.3 } );
				}
			}
			else
			{
				if( $('app_filter_' + appid + '_wrap' ) && $('app_filter_' + appid + '_wrap' ).visible() )
				{
					new Effect.BlindUp( $('app_filter_' + appid + '_wrap' ), { duration: 0.3 } );
				}
			}
		}
	}
}
ipb.search.init();