function check_boxes()
{
	var ticked = $('maincheckbox').checked;
	
	var checkboxes = document.getElementsByTagName('input');

	for ( var i = 0 ; i <= checkboxes.length ; i++ )
	{
		var e = checkboxes[i];
		
		if ( typeof(e) != 'undefined' && e.type == 'checkbox')
		{
			var boxname		= e.id;
			var boxcheck	= boxname.replace( /^(.+?)_.+?$/, "$1" );
			
			if ( boxcheck == 'mid' )
			{
				e.checked = ticked;
			}
		}
	}
}

function checkThisForm()
{
	var selectedOpt = $F('manage_type');
	
	if( selectedOpt == 'delete' )
	{
		return confirm( "Are you SURE you want to delete all of the selected accounts?\n\nThere are no other confirmation screens and there is no way to undo this action!" );
	}
	else if( selectedOpt == 'unban' )
	{
		return confirm( "Please note that this action ONLY removes the 'Ban this member' flag!\n\nIf you moved the member to a banned group, or banned the member's IP address, email address or username, you will need to update each of these items separately.\n\nAlternatively you can click 'Cancel' and click on the member's name to use the member's centralized ban management panel." );
	}

	return false;
}