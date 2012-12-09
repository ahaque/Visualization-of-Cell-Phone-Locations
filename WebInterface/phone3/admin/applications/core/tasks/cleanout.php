<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Task: Clean out 'dead' sessions, validations, registration image entires, etc
 * Last Updated: $LastChangedDate: 2009-07-01 07:35:02 -0400 (Wed, 01 Jul 2009) $
 *
 * @author 		$Author: matt $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @since		27th January 2004
 * @version		$Rev: 4832 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class task_item
{
	/**
	 * Parent task manager class
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $class;

	/**
	 * This task data
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $task			= array();

	/**
	 * Prevent logging
	 *
	 * @access	protected
	 * @var		boolean
	 */
	protected $restrict_log	= false;
	
	/**#@+
	 * Registry Object Shortcuts
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $cache;
	/**#@-*/
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param 	object		ipsRegistry reference
	 * @param 	object		Parent task class
	 * @param	array 		This task data
	 * @return	void
	 */
	public function __construct( ipsRegistry $registry, $class, $task )
	{
		/* Make registry objects */
		$this->registry	  = $registry;
		$this->DB		  = $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang		  = $this->registry->getClass('class_localization');
		$this->member	  = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache	  = $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();

		$this->class	= $class;
		$this->task		= $task;
	}
	
	/**
	 * Run this task
	 * 1235503784
	 * @access	public
	 * @return	void
	 */
	public function runTask()
	{
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_global' ), 'core' );
		
		//-----------------------------------------
		// Delete reg_anti_spam
		//-----------------------------------------

		$this->DB->delete( 'captcha', 'captcha_date < ' . (time() - (60*60*6)) );
		
		//-----------------------------------------
		// Delete sessions
		//-----------------------------------------
		
		$this->DB->delete( 'sessions', 'running_time < ' . ( IPS_UNIX_TIME_NOW - $this->settings['session_expiration'] ) );
		
		//-----------------------------------------
		// Log to log table - modify but dont delete
		//-----------------------------------------
		
		$this->class->appendTaskLog( $this->task, $this->lang->words['task_cleanout'] );
		
		//-----------------------------------------
		// Unlock Task: DO NOT MODIFY!
		//-----------------------------------------
		
		$this->class->unlockTask( $this->task );
	}

}