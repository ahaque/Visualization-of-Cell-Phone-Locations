<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Silly toggle function(?s{0,})
 * Last Updated: $Date: 2009-02-04 15:03:36 -0500 (Wed, 04 Feb 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Forums
 * @link		http://www.
 * @since		20th February 2002
 * @version		$Revision: 3887 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_forums_extras_toggle extends ipsCommand
{
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
		// Lang & skin
		//-----------------------------------------
	
		ipsRegistry::getClass( 'class_localization')->loadLanguageFile( array( 'public_forums' ) );

		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'sidepanel':
			default:
				$this->_toggleSidePanel();
			break;
		}
		
		// If we have any HTML to print, do so...
		
		$this->registry->output->addContent( $this->output );
		$this->registry->output->sendOutput();
 	}
 	
 	/**
 	 * Toggle side panel on/off without JS
 	 *
 	 * @access	public
 	 * @return	void
 	 * @see		The Dark Knight (it was an awesome movie)
 	 */
 	public function _toggleSidePanel()
 	{
 		$current	= IPSCookie::get('hide_sidebar');
 		$new		= $current ? 0 : 1;
 		
 		IPSCookie::set( 'hide_sidebar', $new );
 		
 		$this->registry->getClass('output')->silentRedirect( $this->settings['base_url'] );
 	}
 
}
