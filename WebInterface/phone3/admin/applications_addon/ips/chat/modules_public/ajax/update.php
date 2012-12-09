<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Chat services
 * Last Updated: $Date: 2009-02-04 15:03:59 -0500 (Wed, 04 Feb 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Chat
 * @since		Fir 12th Aug 2005
 * @version		$Revision: 3887 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class public_chat_ajax_update extends ipsAjaxCommand
{
	/**
	 * Main class entry point
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Got sess ID and mem ID?
		//-----------------------------------------
		
		if ( ! $this->member->getProperty('member_id') )
		{
			$this->returnString( "no" );
		}
		
		//-----------------------------------------
		// Two hours of not doing anything...
		//-----------------------------------------
		
		if ( $this->member->getProperty('last_activity') < ( time() - 7200 ) )
		{
			$this->returnString( "no" );
		}
		
		$tmp_cache = $this->cache->getCache('chatting');
		$new_cache = array();
		
		//-----------------------------------------
		// Goforit
		//-----------------------------------------
		
		if ( is_array( $tmp_cache ) and count( $tmp_cache ) )
		{
			foreach( $tmp_cache as $id => $data )
			{
				//-----------------------------------------
				// Not hit in 2 mins?
				//-----------------------------------------
				
				if ( $data['updated'] < ( time() - 120 ) )
				{
					continue;
				}
				
				if ( $id == $this->member->getProperty('member_id') )
				{
					continue;
				}
				
				$new_cache[ $id ] = $data;
			}
		}
		
		//-----------------------------------------
		// Add in us
		//-----------------------------------------
		
		$new_cache[ $this->member->getProperty('member_id') ] = array( 'updated' => time(), 'name' => $this->member->getProperty('members_display_name') );
		
		//-----------------------------------------
		// Update cache
		//-----------------------------------------
														  
		$this->cache->setCache( 'chatting', $new_cache, array( 'deletefirst' => 1, 'donow' => 1, 'array' => 1 ) );
		
		//-----------------------------------------
		// Something to return
		//-----------------------------------------
		
		$this->returnString( "ok" );
	}
}