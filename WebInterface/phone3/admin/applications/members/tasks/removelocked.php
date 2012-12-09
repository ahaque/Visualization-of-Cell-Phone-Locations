<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Task: Removes locked membes who are unlocked
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
		
		$count		= 0;
		$canUnlock	= array();
		
		if ( $this->settings['ipb_bruteforce_attempts'] AND $this->settings['ipb_bruteforce_unlock'] )
		{
			$this->DB->build( array( 'select'		=> 'member_id, failed_logins, failed_login_count',
									 'from'		=> 'members',
									 'where'		=> 'failed_login_count > 0 AND failed_logins ' . $this->DB->buildIsNull( false ),
									)		);
			$outer = $this->DB->execute();
		
			while( $r = $this->DB->fetch($outer) )
			{
				$used_ips 		= array();
				$this_attempt 	= array();
				$oldest			= 0;
				$newest			= 0;
				
				if( $r['failed_logins'] )
				{
					$failed_logins = explode( ",", IPSText::cleanPermString( $r['failed_logins'] ) );
					
					if( is_array($failed_logins) AND count($failed_logins) )
					{
						sort($failed_logins);
						
						foreach( $failed_logins as $attempt )
						{
							$this_attempt = explode( "-", $attempt );
							
							if( isset($used_ips[ $this_attempt[1] ]) AND $this_attempt[0] > $used_ips[ $this_attempt[1] ] )
							{
								$used_ips[ $this_attempt[1] ] = $this_attempt[0];
							}
						}

						$totalLocked	= count($used_ips);
						$totalToUnlock	= 0;
						
						if( count($used_ips) )
						{
							foreach( $used_ips as $ip => $timestamp )
							{
								if( $timestamp < time() - ($this->settings['ipb_bruteforce_period']*60) )
								{
									$totalToUnlock++;
								}
							}
						}
						
						if( $totalToUnlock == $totalLocked )
						{
							$canUnlock[] = $r['member_id'];
						}
					}
					else
					{
						$canUnlock[]	= $r['member_id'];
					}
				}
				else
				{
					$canUnlock[]	= $r['member_id'];
				}
			}
			
			if( count($canUnlock) )
			{
				$this->DB->update( 'members', array( 'failed_logins' => null, 'failed_login_count' => 0 ), 'member_id IN(' . implode( ',', $canUnlock ) . ')' );
			}

			//-----------------------------------------
			// Log to log table - modify but dont delete
			//-----------------------------------------
			
			$this->class->appendTaskLog( $this->task, sprintf( $this->lang->words['task_removelocked'], count($canUnlock) ) );
		}
		
		//-----------------------------------------
		// Unlock Task: DO NOT MODIFY!
		//-----------------------------------------
		
		$this->class->unlockTask( $this->task );
	}
	
}