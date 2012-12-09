<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Member AJAX DST switcher
 * Last Updated: $Date: 2009-07-28 20:26:37 -0400 (Tue, 28 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Members
 * @link		http://www.
 * @since		Tuesday 1st March 2005 (11:52)
 * @version		$Revision: 4948 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_members_ajax_dst extends ipsAjaxCommand 
{
	/**
	 * Class entry point
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		if( !$this->memberData['member_id'] )
		{
			if( $this->request['xml'] )
			{
				$this->returnNull();
			}
			else
			{
				$this->registry->output->silentRedirect( $this->settings['base_url'] );
			}
		}
		
		if( $this->memberData['members_auto_dst'] == 1 AND $this->settings['time_dst_auto_correction'] )
		{
			$newValue	= $this->memberData['dst_in_use'] ? 0 : 1;
			
			IPSMember::save( $this->memberData['member_id'], array( 'members' => array( 'dst_in_use' => $newValue ) ) );
		}
		
		if( $this->request['xml'] == 1 )
		{
			$this->returnNull();
		}
		else
		{
			$this->registry->output->silentRedirect( $this->settings['base_url'] );
		}
	}
}