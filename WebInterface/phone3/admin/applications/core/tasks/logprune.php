<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Task: Prune old logs
 * Last Updated: $LastChangedDate: 2009-05-20 16:34:08 -0400 (Wed, 20 May 2009) $
 *
 * @author 		$Author: josh $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
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
		// Spider Logs
		//-----------------------------------------		
		
		if( $this->settings['ipb_prune_spider'] )
		{
			$this->DB->delete( "spider_logs", "entry_date < " . (time() - (60*60*24*30)) );
		}
		
		//-----------------------------------------
		// Admin Login Logs
		//-----------------------------------------		
		
		if( $this->settings['prune_admin_login_logs'] )
		{
			$this->DB->delete( "admin_login_logs", "admin_time < " . (time() - (60*60*24*30)) );
		}
		
		//-----------------------------------------
		// Task Logs
		//-----------------------------------------		
		
		if( $this->settings['ipb_prune_task'] )
		{
			$this->DB->delete( "task_logs", "log_date < " . (time() - (60*60*24*30)) );
		}
		
		//-----------------------------------------
		// Admin Logs
		//-----------------------------------------		
		
		if( $this->settings['ipb_prune_admin'] )
		{
			$this->DB->delete( "admin_logs", "ctime < " . (time() - (60*60*24*30)) );
		}
		
		//-----------------------------------------
		// Mod Logs
		//-----------------------------------------		
		
		if ( $this->settings['ipb_prune_mod'] )
		{
			$this->DB->delete( "moderator_logs", "ctime < " . (time() - (60*60*24*30)) );
		}
		
		//-----------------------------------------
		// Email Logs
		//-----------------------------------------		
		
		if ( $this->settings['ipb_prune_email'] )
		{
			$this->DB->delete( "email_logs", "email_date < " . (time() - (60*60*24*30)) );
		}
		
		//-----------------------------------------
		// Email Error Logs
		//-----------------------------------------		
		
		if ( $this->settings['ipb_prune_emailerror'] )
		{
			$this->DB->delete( "mail_error_logs", "mlog_date < " . (time() - (60*60*24*30)) );
		}
		
		//-----------------------------------------
		// Error Logs
		//-----------------------------------------		
		
		if ( $this->settings['prune_error_logs'] )
		{
			$this->DB->delete( "error_logs", "log_date < " . (time() - (60*60*24*30)) );
		}
		
		//-----------------------------------------
		// SQL Error Logs
		// --Only prune older than 30 days
		//-----------------------------------------		
		
		if ( $this->settings['ipb_prune_sql'] )
		{
			try
			{
				foreach( new DirectoryIterator( DOC_IPS_ROOT_PATH . 'cache' ) as $file )
				{
					if( $file->isDot() OR !$file->isFile() )
					{
						continue;
					}
            	
					if( preg_match( "#^sql_error_log_(\d+)_(\d+)_(\d+).cgi$#", $file->getFilename(), $matches ) )
					{
						if( $file->getMTime() < (time() - (60*60*24*30)) )
						{
							@unlink( $file->getPathname() );
						}
					}
				}
			} catch ( Exception $e ) {}
		}	
		
		//-----------------------------------------
		// Log to log table - modify but dont delete
		//-----------------------------------------
		
		$this->class->appendTaskLog( $this->task, $this->lang->words['task_logprune'] );
		
		//-----------------------------------------
		// Unlock Task: DO NOT MODIFY!
		//-----------------------------------------
		
		$this->class->unlockTask( $this->task );
	}

}