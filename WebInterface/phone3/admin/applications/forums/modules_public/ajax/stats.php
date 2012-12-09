<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Retrieve who posted stats
 * Last Updated$
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Forums
 * @link		http://www.
 * @version		$Revision: 4955 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_forums_ajax_stats extends ipsAjaxCommand 
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
    	switch( $this->request['do'] )
    	{
			case 'who':
				$this->_whoPosted();
			break;
    	}
	}

	/**
	 * Retrieve posters in a given topic
	 *
	 * @access	protected
	 * @return	void		[Outputs to screen]
	 */
	protected function _whoPosted()
	{
		ipsRegistry::getClass( 'class_localization')->loadLanguageFile( array( 'public_stats' ), 'forums' );
		
		require_once( IPSLib::getAppDir('forums') . '/modules_public/extras/stats.php' );
		$stats	= new public_forums_extras_stats( $this->registry );
		$stats->makeRegistryShortcuts( $this->registry );
		
		$output	= $stats->whoPosted( true );
		
		if ( !$output )
		{
			$this->returnJsonError( $this->lang->words['ajax_nohtml_return'] );
		}
		else
		{
			$this->returnHtml( $this->registry->getClass('output')->getTemplate('stats')->whoPostedAjaxWrapper( $output ) );
		}
	}
}