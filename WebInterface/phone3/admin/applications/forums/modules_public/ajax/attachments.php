<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Grab attachments and return via AJAX (AJAX)
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

class public_forums_ajax_attachments extends ipsAjaxCommand 
{
	/**
	 * Class entry point
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$topic_id		= intval( $this->request['tid'] );
		
		ipsRegistry::getClass( 'class_localization')->loadLanguageFile( array( 'public_stats' ), 'forums' );
		
		//-----------------------------------------
		// Check..
		//-----------------------------------------
		
		if ( ! $topic_id )
        {
        	$this->returnJsonError( $this->lang->words['notopic_attach'] );
        }
        
        //-----------------------------------------
        // get topic..
        //-----------------------------------------
        
        $topic = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'topics', 'where' => 'tid=' . $topic_id ) );
        
        if ( ! $topic['topic_hasattach'] )
        {
        	$this->returnJsonError( $this->lang->words['topic_noattach'] );
        }
        
        //-----------------------------------------
        // Check forum..
        //-----------------------------------------
        
        if ( $this->registry->getClass('class_forums')->forumsCheckAccess( $topic['forum_id'], 0, 'forum', $topic, true ) === false )
		{
			$this->returnJsonError( $this->lang->words['topic_noperms'] );
		}
		
		require_once( IPSLib::getAppDir('forums') . '/modules_public/forums/attach.php' );
		$attach	= new public_forums_forums_attach( $this->registry );
		$attach->makeRegistryShortcuts( $this->registry );
		
		$attachHTML	= $attach->getAttachments( $topic );
		
		if ( !$attachHTML )
		{
			$this->returnJsonError( $this->lang->words['ajax_nohtml_return'] );
		}
		else
		{
			$this->returnHtml( $this->registry->getClass('output')->getTemplate('forum')->forumAttachmentsAjaxWrapper( $attachHTML ) );
		}
	}
}