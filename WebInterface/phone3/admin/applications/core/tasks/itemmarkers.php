<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Task: Update 'dead' markers
 * Last Updated: $LastChangedDate: 2009-02-04 20:03:59 +0000 (Wed, 04 Feb 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @since		27th January 2004
 * @version		$Rev: 3887 $
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
		$this->registry	  =  $registry;
		$this->DB		  =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang		  =  $this->registry->getClass('class_localization');
		$this->member	  =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache	  =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();

		$this->class	= $class;
		$this->task		= $task;
		
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_global' ), 'core' );
	}
	
	/**
	 * Run this task
	 * 
	 * @access	public
	 * @return	void
	 */
	public function runTask()
	{
		/* INIT */
		if ( ! $this->registry->isClassLoaded( 'classItemMarking' ) )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/itemmarking/classItemMarking.php' );
			$this->registry->setClass( 'classItemMarking', new classItemMarking( $this->registry ) );
		}
		
		$itemsRemoved = $this->registry->getClass('classItemMarking')->manualCleanUp( 100 );
		
		$this->class->appendTaskLog( $this->task, "Cleaned up " . $itemsRemoved . " markers" );
	
		$this->class->unlockTask( $this->task );
	}

}