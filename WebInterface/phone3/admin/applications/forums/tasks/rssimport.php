<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Task: Update the rss import
 * Last Updated: $LastChangedDate: 2009-02-04 15:03:36 -0500 (Wed, 04 Feb 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Forums
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
	
	/**
	* Limit
	*
	* @access	protected
	* @var		integer
	*/
	protected $limit		= 10;
	
	/**
	* Registry Object Shortcuts
	*/
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $cache;
	
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
		// INIT
		//-----------------------------------------
		
		$feeds_to_update	= array();
		
		$time				= time();
		$t_minus_30			= time() - ( 30 * 60 );
		
		//-----------------------------------------
		// Got any to update?
		// 30 mins is RSS friendly.
		//-----------------------------------------
		
		$this->DB->build( array( 'select'	=> '*', 
										'from'	=> 'rss_import', 
										'where'	=> 'rss_import_enabled=1 AND rss_import_last_import <= '.$t_minus_30,
										'order'	=> 'rss_import_last_import ASC',
										'limit'	=> array( $this->limit )
							) 		);
		$rss_main_query = $this->DB->execute();
		
		if ( $this->DB->getTotalRows( $rss_main_query ) )
		{
			define( 'IN_ACP', 1 );
			
			require_once( IPSLib::getAppDir( 'forums' ) . '/modules_admin/rss/import.php' );
			$rss		   =  new admin_forums_rss_import();
			$rss->makeRegistryShortcuts( $this->registry );

			while( $rss_feed = $this->DB->fetch( $rss_main_query ) )
			{
				$this_check = time() - ( $rss_feed['rss_import_time'] * 60 );
				
				if ( $rss_feed['rss_import_last_import'] <= $this_check )
				{
					//-----------------------------------------
					// Set the feeds we need to update...
					//-----------------------------------------
					
					$feeds_to_update[] = $rss_feed['rss_import_id'];
				}
			}
			
			$timeStart	= time();
			
			//-----------------------------------------
			// Do the update now...
			//-----------------------------------------

			if ( count( $feeds_to_update ) )
			{
				$rss->rssImportRebuildCache( implode( ",", $feeds_to_update), 0, 1 );
				
				//-----------------------------------------
				// Running longer than 30 seconds?
				//-----------------------------------------
				
				if( time() - $timeStart > 30 )
				{
					break;
				}
			}
		}

		//-----------------------------------------
		// Log to log table - modify but dont delete
		//-----------------------------------------

		$this->class->appendTaskLog( $this->task, sprintf( $this->lang->words['task_rssimport'], count($feeds_to_update) ) );
		
		//-----------------------------------------
		// Unlock Task: DO NOT MODIFY!
		//-----------------------------------------
		
		$this->class->unlockTask( $this->task );
	}
}