<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Profile AJAX hCard
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

class public_members_ajax_card extends ipsAjaxCommand 
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
		$this->registry->class_localization->loadLanguageFile( array( 'public_profile') );
		
		/* INIT */
		$member_id	= intval( $this->request['mid'] );

		//-----------------------------------------
		// Can we access?
		//-----------------------------------------
		
		if ( !$this->memberData['g_mem_info'] )
 		{
 			$this->returnString( 'error' );
		}
		
		if( !$member_id )
		{
			$this->returnString( 'error' );
		}
		
		$member		= IPSMember::load( $member_id, 'profile_portal,pfields_content,sessions,groups,basic', 'id' );
		
		if( !$member['member_id'] )
		{
			$this->returnString( 'error' );
		}
		
		$this->registry->class_localization->loadLanguageFile( array( 'public_online' ), 'members' );

		$member		= IPSMember::buildDisplayData( $member, array( 'customFields' => 1, 'cfSkinGroup' => 'profile' ) );
		$member		= IPSMember::getLocation( $member );

		$board_posts = $this->caches['stats']['total_topics'] + $this->caches['stats']['total_replies'];
		
		if( $member['posts'] and $board_posts  )
		{
			$member['_posts_day'] = round( $member['posts'] / ( ( time() - $member['joined']) / 86400 ), 2 );
	
			# Fix the issue when there is less than one day
			$member['_posts_day'] = ( $member['_posts_day'] > $member['posts'] ) ? $member['posts'] : $member['_posts_day'];
			$member['_total_pct'] = sprintf( '%.2f', ( $member['posts'] / $board_posts * 100 ) );
		}
		
		$member['_posts_day'] = floatval( $member['_posts_day'] );
	
		$this->returnHtml( $this->registry->getClass('output')->getTemplate('profile')->showCard( $member ) );
	}
}