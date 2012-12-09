/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* acp.permissions.js - Permission functions	*/
/* (c) IPS, Inc 2009							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/

/* Options:
 * form			The ID of the form this is contained in
 * table		The ID of the matrix table
 */
var _temp = window.IPBACP;
_temp.prototype.permissions = Class.create({
	
	initialize: function( options )
	{	
		if( !$( options.form ) )
		{
			return false;
		}
		
		this.options = options;
		
		document.observe("dom:loaded", function(){
			Debug.write("Initializing permission masks");
			
			$( this.options.form ).observe('submit', this.submitForm.bindAsEventListener( this ) );
			
			// Set delegate observer on table
			$( this.options.table ).observe('click', this.boxChecked.bindAsEventListener( this ) );
			
			$( this.options.table ).select('.select_row').each( function(button){
				$( button ).observe('click', this.selectRow.bindAsEventListener( this ) );
			}.bind(this) );
			
			$( this.options.table ).select('.select_col').each( function(button){
				$( button ).observe('click', this.selectColumn.bindAsEventListener( this ) );
			}.bind(this) );
			
		}.bind(this));
	},
	
	selectColumn: function(e)
	{
		var elem = Event.element(e);
		if( !elem ){ return; }
		var parts = elem.id.match(/(.*)_select_col_([0|1])_(.*)/);
		if( !parts ){ return; }
		
		var boo = ( parts[2] == 1 ) ? true : false;
		
		$( this.options.form ).select('input[type=checkbox]').each( function( elem ){
			if( $(elem).id.match( "^" + parts[1] + "_" + parts[3] ) )
			{
				$(elem).checked = boo;
			}
		});
	},
	
	selectRow: function(e)
	{
		var elem = Event.element(e);
		if( !elem ){ return; }
		
		// Get ID
		var parts = elem.id.match(/(.*)_select_row_([0|1])_(.*)/);
		if( !parts ){ return; }
		
		if( !$( this.options.app + '_row_' + parts[3]) ){ return; }
		
		$( this.options.app + '_row_' + parts[3]).select('input[type=checkbox]').each( function(check){
			if( parts[2] == 1 ){
				check.checked = true;
			} else {
				check.checked = false;
				
				// If column header is checked, uncheck it
				var tmpid = check.id.replace(/^perm_(.+?)_/, '');
				if( $('col_' + tmpid) && $('col_' + tmpid).checked ){
					$('col_' + tmpid).checked = false;
				}
			}
		});

	},
	
	boxChecked: function(e)
	{		
		var elem = Event.findElement(e, '.perm');
		if( !elem ){ return; }
		
		var input = elem.down('input');
		if( !input ){ return; }
		
		// If this is on the input itself, lets skip this bit
		if( Event.element(e).tagName != 'INPUT' )
		{
			if( !input.checked ){
				input.checked = true;
			} else {
				input.checked = false;
			}
		}
		
		if( elem.hasClassName('column') )
		{
			// Toggle all in this column
			this.toggleColumn( elem.id.replace('column_', ''), input.checked );
		}
		else
		{
			// See whether we need to uncheck the column header
			if( !input.checked )
			{
				try {
					var colid = input.id.replace(/^perm_(.+?)_/, '');
					var col = $('column_' + colid).down('input');
					col.checked = false;
				} catch(err) { }
			}
			
		}
	},
	
	toggleColumn: function( id, boo )
	{		
		$( this.options.form ).select('input[type=checkbox]').each( function( elem ){
			if( $(elem).id.match( "^perm_(.+?)_" + id ) )
			{
				$(elem).checked = boo;
			}
		});
	},
	
	submitForm: function(e)
	{
		// Get all inputs
		var test = $( this.options.form ).select('input[type=checkbox]').any( function( elem ){ return elem.checked } );
		
		if( !test )
		{
			if( !confirm("No permissions have been checked, are you sure you want to continue?") )
			{
				Event.stop(e);
				return;
			}
		}
	}
		
});