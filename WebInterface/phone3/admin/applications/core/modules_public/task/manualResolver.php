<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Task Manager
 * Last Updated: $Date: 2009-07-28 20:26:37 -0400 (Tue, 28 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @version		$Rev: 4948 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_core_task_manualResolver extends ipsCommand
{
	/**
	 * Main class entry point
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		@set_time_limit(1200);
		
		//-----------------------------------------
		// Require and run
		//-----------------------------------------
		
		require_once( IPS_ROOT_PATH . 'sources/classes/class_taskmanager.php' );
		$functions = new class_taskmanager( $registry );
		
		//-----------------------------------------
		// Check shutdown functions
		//-----------------------------------------
		
		if( IPS_USE_SHUTDOWN )
		{
			define( 'IS_SHUTDOWN', 1 );
			register_shutdown_function( array( $functions, 'runTask') );
		}
		else
		{
			$functions->runTask();
		}
		
		if( $functions->type != 'cron' && ! $_SERVER['SHELL'] )
		{
			//-----------------------------------------
			// Print out the 'blank' gif
			//-----------------------------------------
		
			@header( "Content-Type: image/gif" );
			print base64_decode( "R0lGODlhAQABAID/AMDAwAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==" );
		}
 	}
}