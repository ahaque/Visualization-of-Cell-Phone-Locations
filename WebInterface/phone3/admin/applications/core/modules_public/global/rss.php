<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * RSS output functionality
 * Last Updated: $Date: 2009-02-04 15:03:36 -0500 (Wed, 04 Feb 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @since		Friday 18th March 2005
 * @version		$Revision: 3887 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_core_global_rss extends ipsCommand
{
	/**
	 * XML document content
	 *
	 * @access	private
	 * @var		string			XML document content
	 */
	private $to_print			= '';

	/**
	 * Class entry point
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	void		[Outputs to screen/redirects]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		if ( $this->request['j_do'] )
		{
			$this->request['do'] = $this->request['j_do'];
		}
		
		//-----------------------------------------
		// We offline?
		//-----------------------------------------
		
		if( $this->settings['board_offline'] )
		{
			header("HTTP/1.1 503 Service Temporarily Unavailable");
			print $this->lang->words['rss_board_offline'];
			exit();
		}
		
		//-----------------------------------------
		// Grab the plugin
		//-----------------------------------------
		
		$type	= 'forums';
		
		if( $this->request['type'] )
		{
			if( file_exists( IPSLib::getAppDir( IPSText::alphanumericalClean( $this->request['type'] ) ) . '/extensions/rssOutput.php' ) )
			{
				$type = IPSText::alphanumericalClean( $this->request['type'] );
			}
		}
		
		//-----------------------------------------
		// And grab the content
		//-----------------------------------------
		
		require_once( IPSLib::getAppDir( $type ) . '/extensions/rssOutput.php' );
		$classname		= "rss_output_" . $type;
		$rss_library	= new $classname( $this->registry );
		
		$this->to_print	= $rss_library->returnRSSDocument();
		$expires		= $rss_library->grabExpiryDate();
		
		//-----------------------------------------
		// Then output
		//-----------------------------------------
		
		@header( 'Content-Type: text/xml; charset=' . IPS_DOC_CHAR_SET );
		@header( 'Expires: ' . gmstrftime( '%c', $expires ) . ' GMT' );
		@header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		@header( 'Pragma: public' );
		print $this->to_print;
		exit();
	}
}