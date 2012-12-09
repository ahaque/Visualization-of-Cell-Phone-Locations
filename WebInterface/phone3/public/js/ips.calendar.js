/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.calendar.js - Calendar code				*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/

var _cal = window.IPBoard;

_cal.prototype.calendar = {
	inSection: 	'',
	
	/* ------------------------------ */
	/**
	 * Constructor
	*/
	init: function()
	{
		Debug.write("Initializing ips.calendar.js");
		
		document.observe("dom:loaded", function(){
			ipb.calendar.initEvents();
		});
	},
	
	/* ------------------------------ */
	/**
	 * Set up page events
	*/
	initEvents: function()
	{
		//Sets up double click events
		/*$$('#calendar_table td', '#week_list li.day').each( function(day){
			if( day.hasClassName('blank') ){ return; } // Dont handle non-days
			
			day.observe('click', function(e){
				ipb.calendar.highlightCell(e);
			});
			day.observe('dblclick', function(e){
				ipb.calendar.addEvent(e);
			});
		});*/
		
		if( ipb.calendar.inSection == 'form' )
		{
			if( $('set_times') )
			{
				$('set_times').observe('click', function(e){
					$$('.time_setting').invoke('toggle');
				});
			}
		
			if( $('e_groups') )
			{
				$('e_type').observe( 'change', ipb.calendar.hideAdminOptions );
			}
		
			if( $('all_groups') )
			{
				$('all_groups').observe( 'click', ipb.calendar.checkAllGroups );
			}
		}
	},
	
	/* ------------------------------ */
	/**
	 * Checks all groups on the add event form
	 * 
	 * @var		{event}		e	The event
	*/
	checkAllGroups: function(e)
	{
		if( $F('all_groups') == '1' )
		{
			for( var i=0; i< $('e_groups').options.length; i++){
				$('e_groups').options[i].selected = true;
			}
			
			$('e_groups').disable();
		}
		else
		{
			$('e_groups').enable();
			
			for( var i=0; i< $('e_groups').options.length; i++){
				$('e_groups').options[i].selected = false;
			}
		}
	},

	/* ------------------------------ */
	/**
	 * Hides unused options on the add event form
	 * 
	 * @var		{event}		e	The event
	*/	
	hideAdminOptions: function(e)
	{
		if( $F('e_type') == 'public' )
		{
			$$('.type_setting').invoke('show');
		}
		else
		{
			$$('.type_setting').invoke('hide');
		}
	},
	
	/*------------------------------*/
	/* Add new calendar event		*/
	addEvent: function(e)
	{
		// Get cell id
		id = Event.element(e).id;
		if( !id.startsWith('day-') ){ return; }
		
		Debug.write("Adding event popup for " + id );
		
		popup = new ipb.popup(id, { h: '200px', modal: true});
		ipb.positionCenter( $( popup.wrapper ) );
		
		//Get form
		new Ajax.Request( ipb.baseURI + "ajax.php?v=event",
		{
			method: 'get',
			onSuccess: function(t){
				popup.update( t.responseText );
				
				//Hook up events
				$( popup.inner ).select('.cancel').each( function(s){
					s.observe('click', function(e){
				 		popup.destroy();
					})
				});
			}
		});		
		
		ipb.showModal();
		popup.show();
		Event.stop(e);
		
		return false;
	},
	
	/*------------------------------*/
	/* Highlight a cell				*/
	highlightCell: function(e)
	{
		id = Event.element(e).id;
		if( !id.startsWith('day-') ){ return; }
		if( ipb.calendar.selectedCell == id ){ return; }
		
		ipb.calendar.unselectAll();
		$( id ).addClassName('selected');
	},
	
	/* ------------------------------ */
	/**
	 * Unselects all cells
	 * 
	 * @var		{event}		e	The event
	*/
	unselectAll: function(e)
	{
		$$('#calendar_table td').each( function(cell){
			cell.removeClassName('selected');
		} );
	}
	
}
ipb.calendar.init();
