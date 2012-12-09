//------------------------------------------------------------------------------
// IPS JS: Permissions
// (c) 2008 Invision Power Services, Inc.
// http://www.
// Brandon Farber - 16th December
//------------------------------------------------------------------------------

ACPPermissions = {
	hasBeenLoaded:	false,
	
	/*------------------------------*/
	/* Constructor 					*/
	/*------------------------------*/
	init: function()
	{
		Debug.write("Initializing acp.permissions.js");
		
		document.observe("dom:loaded", function(){
			Event.observe( 
							'adminform', 
							'submit', 
							function( e ) 
							{ 
								var checkboxes = $('perm-matrix').getElementsByTagName( 'input' );
								
								for( var i = 0; i < checkboxes.length; i++ )
								{
									if( checkboxes[i].checked )
									{
										return true;
									}
								}
								
								if( confirm( 'You did not select any permissions, would you like to continue anyways?' ) )
								{
									return true;
								}
								else
								{
									Event.stop( e );
								}
							} 					
						);

			$$('.column_header').each( function(elem){
				elem.observe( "click", ACPPermissions.checkColumn );
			});
		});
	},
	
	checkColumn: function(e)
	{
		// Conditional to see if checkbox or cell was clicked...
		
		try
		{
			var master	= Event.findElement( e, 'input' ).id;
			var checked	= $(master).checked ? 1 : 0;
			
			$(master).checked	= checked;
		}
		catch( error )
		{
			var master	= Event.findElement( e, 'td' ).down('input').id;
			var checked	= $(master).checked ? 0 : 1;
			$(master).checked	= checked;
		}

		var column	= master.replace( /col_/, '' );
		
		var checkboxes = $('perm-matrix').getElementsByTagName( 'input' );
		
		for( var i = 0; i < checkboxes.length; i++ )
		{
			var cbox = checkboxes[i];
			
			if( cbox.id.match( "^perm_(.+?)_" + column ) )
			{
				cbox.checked = checked;
			}
		}
		
		Event.stop(e);
		return false;
	},
	
	checkRow: function( row, value )
	{
		var checkboxes = $('perm-matrix').getElementsByTagName( 'input' );
		
		for( var i = 0; i < checkboxes.length; i++ )
		{
			var cbox = checkboxes[i];
			
			if( cbox.id.match( "^perm_" + row + "_(.+?)" ) )
			{
				cbox.checked = value;
			}
		}
	}
};

ACPPermissions.init();