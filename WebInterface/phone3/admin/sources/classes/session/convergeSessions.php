<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * This class overrides parent publicSessions to prevent construct being called
 *	It doesn't do anything else...
 * Last Updated: $Date: 2009-02-16 11:03:34 -0500 (Mon, 16 Feb 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	IP.Converge
 * @link		http://www.
 * @since		22nd May 2008 11:15 AM
 * @version		$Revision: 3992 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class convergeSessions extends publicSessions
{
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	void
	 */	
	public function __construct( $registry )
	{
		/* Make object */
		$this->registry		= ipsRegistry::instance();
		$this->DB			= $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->cache		= $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
		$this->_member		=  self::instance();
		$this->_memberData	=& self::instance()->fetchMemberData();
		
		$this->_userAgent = substr( $this->_member->user_agent, 0, 200 );
		
		//-----------------------------------------
		// Fix up app / section / module
		//-----------------------------------------
		
		$this->current_appcomponent	= IPS_APP_COMPONENT;
		$this->current_module		= IPSText::alphanumericalClean( $this->request['module'] );
		$this->current_section		= IPSText::alphanumericalClean( $this->request['section'] );
		
		$this->settings['session_expiration'] = ( $this->settings['session_expiration'] ) ? $this->settings['session_expiration'] : 60;
	}

	/**
	 * Create a member session
	 *
	 * @access	public
	 * @return	string		Session id
	 */	
	public function createMemberSession()
	{
		parent::_createMemberSession();
		
		return $this->session_data['id'];
	}
}