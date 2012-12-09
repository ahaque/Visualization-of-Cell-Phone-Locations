<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Portal plugin: poll
 * Last Updated: $Date: 2009-06-16 08:17:58 -0400 (Tue, 16 Jun 2009) $
 *
 * @author 		$Author: matt $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Forums
 * @since		1st march 2002
 * @version		$Revision: 4778 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class ppi_poll extends public_portal_portal_portal 
{
	/**
	 * Initialize module
	 *
	 * @access	public
	 * @return	void
	 */
	public function init()
 	{
 	}
 	
	/**
	 * Display a poll
	 *
	 * @access	public
	 * @return	string		HTML content to replace tag with
	 */
	public function poll_show_poll()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
 		$extra	= "";
 		$sql	= "";
 		$check	= 0;
 		
 		//-----------------------------------------
 		// Got a poll?
 		//-----------------------------------------
 		
 		if ( ! $this->settings['poll_poll_url'] )
 		{
 			return;
 		}
 		
 		//-----------------------------------------
		// Get the topic ID of the entered URL
		//-----------------------------------------
		
		/* Friendly URL */
		if( $this->settings['use_friendly_urls'] )
		{
			preg_match( "#/topic/(\d+)(.*?)/#", $this->settings['poll_poll_url'], $match );
			$tid = intval( trim( $match[1] ) );
		}
		/* Normal URL */
		else
		{
			preg_match( "/(\?|&amp;)(t|showtopic)=(\d+)($|&amp;)/", $this->settings['poll_poll_url'], $match );
			$tid = intval( trim( $match[3] ) );
		}
		
		if ( !$tid )
		{
			return;
		}
		
		//-----------------------------------------
		// Get topic...
		//-----------------------------------------
		
		$this->registry->class_localization->loadLanguageFile( array( 'public_boards', 'public_topic' ), 'forums' );
		$this->registry->class_localization->loadLanguageFile( array( 'public_editors' ), 'core' );

		require_once( IPSLib::getAppDir( 'forums' ) . "/sources/classes/forums/class_forums.php" );
		$this->registry->setClass( 'class_forums', new class_forums( $this->registry ) );
		
		$this->registry->getClass('class_forums')->strip_invisible = 1;
		$this->registry->getClass('class_forums')->forumsInit();
		
		require_once( IPSLib::getAppDir( 'forums', 'forums' ) . '/topics.php' );
		$topic = new public_forums_forums_topics();
		$topic->makeRegistryShortcuts( $this->registry );

		$topic->topic = $this->DB->buildAndFetch( array( 'select' => '*', 'from'   => 'topics', 'where'  => "tid=" . $tid ) );
		$topic->forum = ipsRegistry::getClass('class_forums')->forum_by_id[ $topic->topic['forum_id'] ];
	
		$this->request['f'] =  $topic->forum['id'] ;
		$this->request['t'] =  $tid ;
		
		if ( $topic->topic['poll_state'] )
		{
 			return $this->registry->getClass('output')->getTemplate('portal')->pollWrapper( $topic->_generatePollOutput(), $tid );
 		}
 		else
 		{
 			return;
 		}
 	}

}
