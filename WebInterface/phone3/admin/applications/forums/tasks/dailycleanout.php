<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Task: Prunes subscribed topics
 * Last Updated: $LastChangedDate: 2009-08-24 20:56:22 -0400 (Mon, 24 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Forums
 * @since		27th January 2004
 * @version		$Rev: 5041 $
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
		// Delete old subscriptions
		//-----------------------------------------
		
		$deleted	= 0;
		$trids		= array();
		
		if ( $this->settings['subs_autoprune'] > 0 )
 		{
			$time = time() - ($this->settings['subs_autoprune'] * 86400);
			
			$this->DB->build( array( 'select'		=> 'tr.trid',
											'from'		=> array( 'tracker' => 'tr' ),
											'where'		=> 't.last_post < ' . $time,
											'add_join'	=> array( 
																array( 'from'	=> array( 'topics' => 't' ),
																		'where'	=> 't.tid=tr.topic_id',
																		'type'	=> 'left'
																	)
																)
								)		);
			$this->DB->execute();
			
			while ( $r = $this->DB->fetch() )
			{
				$trids[] = $r['trid'];
			}
			
			if (count($trids) > 0)
			{
				$this->DB->delete( 'tracker', "trid IN (" . implode( ",", $trids ) . ")" );
			}
			
			$deleted = intval( count($trids) );
 		}
 		
		//-----------------------------------------
		// Delete old unattached uploads
		//-----------------------------------------
		
		$time_cutoff	= time() - 7200;
		$deadid			= array();
		
		$this->DB->build( array( "select" => '*', 'from' => 'attachments',  'where' => "attach_rel_id=0 AND attach_date < $time_cutoff") );
		$this->DB->execute();
		
		while( $killmeh = $this->DB->fetch() )
		{
			if ( $killmeh['attach_location'] )
			{
				@unlink( $this->settings['upload_dir'] . "/" . $killmeh['attach_location'] );
			}
			if ( $killmeh['attach_thumb_location'] )
			{
				@unlink( $this->settings['upload_dir'] . "/" . $killmeh['attach_thumb_location'] );
			}
			
			$deadid[] = $killmeh['attach_id'];
		}
		
		$_attach_count = count( $deadid );
		
		if ( $_attach_count )
		{
			$this->DB->delete( 'attachments', "attach_id IN(" . implode( ",", $deadid ) . ")" );
		}
		
		//-----------------------------------------
		// Remove old XML-RPC logs...
		//-----------------------------------------
		
		if ( $this->settings['xmlrpc_log_expire'] > 0 )
		{
			$time = time() - ( $this->settings['xmlrpc_log_expire'] * 86400 );
 			
 			$this->DB->delete( 'api_log', "api_log_date < {$time}" );
 			
 			$xmlrpc_logs_deleted = $this->DB->getAffectedRows();
		}
		
		//-----------------------------------------
		// Log to log table - modify but dont delete
		//-----------------------------------------
		
		$this->class->appendTaskLog( $this->task, sprintf( $this->lang->words['task_dailycleanout'], $xmlrpc_logs_deleted, $_attach_count, $deleted ) );
		
		//-----------------------------------------
		// Unlock Task: DO NOT MODIFY!
		//-----------------------------------------
		
		$this->class->unlockTask( $this->task );
	}

}