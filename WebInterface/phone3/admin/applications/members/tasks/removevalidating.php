<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Task: Removes validating members over configured time
 * Last Updated: $LastChangedDate: 2009-02-04 15:03:36 -0500 (Wed, 04 Feb 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Members
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
		
		$mids	= array();
		$vids	= array();
		$emails	= array();
		
		// If enabled, remove validating new_reg members & entries from members table
		
		if ( intval($this->settings['validate_day_prune']) > 0 )
		{
			$less_than = time() - $this->settings['validate_day_prune'] * 86400;
			
			$this->DB->build( array( 'select' => 'v.vid, v.member_id',
													 'from'	  => array( 'validating' => 'v' ),
													 'where'  => 'v.new_reg=1 AND v.coppa_user<>1 AND v.entry_date < '.$less_than.' AND v.lost_pass<>1',
													 'add_join' => array( 0 => array( 'select' 	=> 'm.posts, m.member_group_id, m.email',
													 								  'from'	=> array( 'members' => 'm' ),
													 								  'where'	=> 'm.member_id=v.member_id',
													 								  'type'	=> 'left'
													 					)			)
											)		);
			
			$outer = $this->DB->execute();
		
			while( $i = $this->DB->fetch($outer) )
			{
				if( $i['member_group_id'] != $this->settings['auth_group'] )
				{
					// No longer validating?
					
					$this->DB->delete( 'validating', "vid='{$i['vid']}'" );
					continue;
				}
				
				if ( intval($i['posts']) < 1 )
				{
					$mids[] 					= $i['member_id'];
				}
			}
			
			// Remove non-posted validating members
			
			if ( count($mids) > 0 )
			{
				IPSMember::remove( $mids );
			}		
		
			//-----------------------------------------
			// Log to log table - modify but dont delete
			//-----------------------------------------
			
			$this->class->appendTaskLog( $this->task, sprintf( $this->lang->words['task_removevalidating'], count($mids) ) );
		}
		
		//-----------------------------------------
		// Unlock Task: DO NOT MODIFY!
		//-----------------------------------------
		
		$this->class->unlockTask( $this->task );
	}
	
}