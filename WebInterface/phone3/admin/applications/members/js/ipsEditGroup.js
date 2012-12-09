function checkform() {

	isAdmin = $('g_access_cp_yes');
	isMod   = $('g_is_supmod_yes');
	msg		= '';
	
	if ( isAdmin && isAdmin.checked == true )
	{
		msg += 'Members in this group can access the Admin Control Panel\n\n';
	}
	
	if ( isMod && isMod.checked == true )
	{
		msg += 'Members in this group are super moderators.\n\n';
	}
	
	if ( msg != '' )
	{
		if( confirm( "Security Check\n--------------\nMember Group Title: " + $F('g_title') + "\n--------------\n\n" + msg + 'Is this correct?' ) )
		{
			return true;
		}
		else
		{
			return false;
		}
	}
}

function stripGuestLegend()
{
	$$('.guest_legend').each( 
							function( elem )
							{
								elem.hide();
							} 
						);
	//$('tabtab-GROUPS|6').hide();
}