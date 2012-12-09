/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.board.js - Board index code				*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/

var _memberlist = window.IPBoard;

_memberlist.prototype.mlist = {
	/*------------------------------*/
	/* Constructor 					*/
	init: function()
	{
		Debug.write("Initializing ips.mlist.js");
		
		document.observe("dom:loaded", function(){
			ipb.mlist.initEvents();
		});
	},
	initEvents: function()
	{
		if( $('use_filters') )
		{
			$('use_filters').observe( 'click', ipb.mlist.toggleFilters );
		}
		
		if( $('close_filters') )
		{
			$('close_filters').observe( 'click', ipb.mlist.toggleFilters );
		}
		
		// Add calendars
		if( $('joined') && $('joined_date_icon') ){
			$('joined_date_icon').observe('click', function(){
				new CalendarDateSelect( $('joined'), { year_range: 6, close_on_click: true } );
			});
		}
		if( $('last_post') && $('last_post_date_icon') ){
			$('last_post_date_icon').observe('click', function(){
				new CalendarDateSelect( $('last_post'), { year_range: 6, close_on_click: true } );
			});
		}
		if( $('last_visit') && $('last_visit_date_icon') ){
			$('last_visit_date_icon').observe('click', function(){
				new CalendarDateSelect( $('last_visit'), { year_range: 6, close_on_click: true } );
			});
		}
	},
	toggleFilters: function(e)
	{
		Effect.toggle( $('member_filters'), 'blind', { duration: 0.4, afterFinish: function(e){
			 	$('filters_1').addClassName('dummy'); $('filters_2').removeClassName('dummy'); // Forces a redraw
		} } );
		Event.stop(e);
	}
}
ipb.mlist.init();