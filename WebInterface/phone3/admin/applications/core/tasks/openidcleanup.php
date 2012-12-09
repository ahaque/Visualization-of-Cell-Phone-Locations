<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Task: Cleanup for uncleared OpenID caches
 * Last Updated: $LastChangedDate: 2009-05-20 16:34:08 -0400 (Wed, 20 May 2009) $
 *
 * @author 		$Author: josh $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Core
 * @link		http://www.
 * @since		27th January 2004
 * @version		$Rev: 4679 $
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
		$this->registry	= $registry;
		$this->DB		= $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->lang		= $this->registry->getClass('class_localization');
		$this->member	= $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache	= $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();

		$this->class	= $class;
		$this->task		= $task;
	}
	
	/**
	 * Run this task
	 *
	 * @access	public
	 * @return	void
	 */
	public function runTask()
	{
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_global' ), 'core' );
		
		//-----------------------------------------
		// This is mysql only
		//-----------------------------------------
		
		if ( strtolower( $this->settings['sql_driver'] ) != 'mysql' )
		{
			$this->class->unlockTask( $this->task );
			return;
		}
		
		//-----------------------------------------
		// Clean out openid caches older than 24 hours
		// For whatever reason the OpenID libraries
		// seem to have an issue where caches can start
		// to accumulate.  On boards heavily using
		// OpenID, it's possible to eventually fill up
		// the cache directories, which causes all subsequent
		// logins to fail.  This task just clears out those
		// caches once every 24 hours to keep that from happening.
		//-----------------------------------------
		
		try
		{
			if( is_dir( DOC_IPS_ROOT_PATH . 'cache/openid' ) )
			{
				if( is_dir( DOC_IPS_ROOT_PATH . 'cache/openid/associations' ) )
				{
					foreach( new DirectoryIterator( DOC_IPS_ROOT_PATH . 'cache/openid/associations' ) as $cache )
					{
						if( $cache->getMTime() < ( time() - ( 60 * 60 * 24 ) ) )
						{
							@unlink( $cache->getPathname() );
						}
					}
				}
				
				if( is_dir( DOC_IPS_ROOT_PATH . 'cache/openid/nonces' ) )
				{
					foreach( new DirectoryIterator( DOC_IPS_ROOT_PATH . 'cache/openid/nonces' ) as $cache )
					{
						if( $cache->getMTime() < ( time() - ( 60 * 60 * 24 ) ) )
						{
							@unlink( $cache->getPathname() );
						}
					}
				}
				
				if( is_dir( DOC_IPS_ROOT_PATH . 'cache/openid/temp' ) )
				{
					foreach( new DirectoryIterator( DOC_IPS_ROOT_PATH . 'cache/openid/temp' ) as $cache )
					{
						if( $cache->getMTime() < ( time() - ( 60 * 60 * 24 ) ) )
						{
							@unlink( $cache->getPathname() );
						}
					}
				}
			}
		} catch ( Exception $e ) {}

		//-----------------------------------------
		// Log to log table - modify but dont delete
		//-----------------------------------------
		
		$this->class->appendTaskLog( $this->task, $this->lang->words['task_openidcleanup'] );
		
		//-----------------------------------------
		// Unlock Task: DO NOT MODIFY!
		//-----------------------------------------
		
		$this->class->unlockTask( $this->task );
	}

}