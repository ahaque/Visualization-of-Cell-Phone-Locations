<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Calendar core extensions
 * Last Updated: $LastChangedDate: 2009-02-23 21:27:51 +0000 (Mon, 23 Feb 2009) $
 *
 * @author 		$Author: josh $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Calendar
 * @link		http://www.
 * @since		27th January 2004
 * @version		$Rev: 4081 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 * Member Synchronization extensions
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage  Calendar
 * @link		http://www.
 * @version		$Rev: 4081 $ 
 **/

class calendarMemberSync
{
	/**
	 * Registry reference
	 *
	 * @access	public
	 * @var		object
	 */
	public $registry;
	
	/**
	 * CONSTRUCTOR
	 *
	 * @access	public
	 * @return	void
	 **/
	public function __construct()
	{
		$this->registry = ipsRegistry::instance();
	}

	/**
	 * This method is called after a member account has been removed
	 *
	 * @access	public
	 * @param	string	$ids	SQL IN() clause
	 * @return	void
	 **/
	public function onDelete( $mids )
	{
		if( $this->registry->DB()->checkForTable( 'cal_events' ) )
		{
			$this->registry->DB()->update( 'cal_events', array( 'event_member_id' => 0 ), 'event_member_id' . $mids );
		}
	}
	
	/**
	 * This method is called after a member's account has been merged into another member's account
	 *
	 * @access	public
	 * @param	array	$member		Member account being kept
	 * @param	array	$member2	Member account being removed
	 * @return	void
	 **/
	public function onMerge( $member, $member2 )
	{
		IPSDebug::addLogMessage( "Markers init done:", 'merge', $member );
		
		if( $this->registry->DB()->checkForTable( 'cal_events' ) )
		{
			$this->registry->DB()->update( 'cal_events', array( 'event_member_id' => intval($member['member_id']) ), "event_member_id=" . $member2['member_id'] );
		}
	}
}