<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Retrieve member warn log
 * Last Updated: $Date: 2009-07-28 20:26:37 -0400 (Tue, 28 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Members
 * @link		http://www.
 * @since		Tuesday 1st March 2005 (11:52)
 * @version		$Revision: 4948 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_members_ajax_warn extends ipsAjaxCommand 
{
	/**
	 * Class entry point
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		ipsRegistry::getClass( 'class_localization')->loadLanguageFile( array( 'public_mod' ), 'forums' );

		if ( ! $this->settings['warn_on'] )
		{
			$this->returnJsonError( $this->lang->words['ajax_no_warn'] );
		}
		
		switch( $this->request['do'] )
		{
			case 'view':
				$this->_viewWarnLogs();
			break;
		}
	}

	/**
	 * Retieve warn logs and return them
	 *
	 * @access	private
	 * @return	void
	 */
 	private function _viewWarnLogs()
 	{
		require_once( IPSLib::getAppDir('members') . '/modules_public/warn/warn.php' );
		$warn	= new public_members_warn_warn( $this->registry );
		$warn->makeRegistryShortcuts( $this->registry );
		$warn->loadData();
		
		$warnHTML	= $warn->viewLog();
		
		if ( !$warnHTML )
		{
			$this->returnJsonError( $this->lang->words['ajax_bad_html'] );
		}
		else
		{
			$this->returnHtml( $this->registry->getClass('output')->getTemplate('mod')->warnLogsAjaxWrapper( $warnHTML ) );
		}
	}
}