<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Profile AJAX Comment Handler
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

class public_members_ajax_comments extends ipsAjaxCommand 
{
	/**
	 * Comments library
	 *
	 * @access	private
	 * @var		object
	 */
	private $comments;

	/**
	 * Class entry point
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		require_once( IPSLib::getAppDir( 'members' ) . '/sources/comments.php' );
		$this->comments = new profileCommentsLib( $this->registry );
		
		switch( $this->request[ 'do' ] )
		{
			case 'view':
			default:
				$this->returnHtml( $this->comments->buildComments( IPSMember::load( intval( $this->request['member_id'] ) ) ) );
			break;

			case 'add':
				$this->_addComment();
			break;
			
			case 'delete':
				$this->_deleteComment();
			break;
			
			case 'approve':
				$this->_approveComment();
			break;
				
			case 'reload':
				$this->_reloadComment();
			break;			
		}
	}

 	/**
	 * Approve a comment on member's profile
	 *
	 * @access	private
	 * @return	void		[Prints to screen]
	 * @since	IPB 2.2.0.2006-08-02
	 */
 	private function _approveComment()
 	{
 		//-----------------------------------------
 		// INIT
 		//-----------------------------------------
		
		$member_id			= intval( $this->request['member_id'] );
		$md5check			= IPSText::md5Clean( $this->request['md5check'] );
		$comment_id			= intval( $this->request['comment_id'] );

		//-----------------------------------------
		// MD5 check
		//-----------------------------------------
		
		if (  $md5check != $this->member->form_hash )
    	{
    		$this->returnString( 'error' );
    	}

		//-----------------------------------------
		// Delete
		//-----------------------------------------

    	$result = $this->comments->approveComment( $member_id, $comment_id );
		
		/* Check for error */
		if( $result )
		{
			$this->returnString( $result );
		}
		else
		{
			$this->returnHtml( $this->comments->buildComments( IPSMember::load( $member_id ) ) );
		}
	}
	
 	/**
	 * Reload comments
	 *
	 * @access	private
	 * @return	void		[Prints to screen]
	 * @since	IPB 2.2.0.2006-08-15
	 */
 	private function _reloadComment()
 	{
 		//-----------------------------------------
 		// INIT
 		//-----------------------------------------
		
		$member_id		= intval( $this->request['member_id'] );
		$md5check		= IPSText::md5Clean( $this->request['md5check'] );

		//-----------------------------------------
		// MD5 check
		//-----------------------------------------
		
		if (  $md5check != $this->member->form_hash )
    	{
    		$this->returnString( 'error' );
    	}

		//-----------------------------------------
		// Load member
		//-----------------------------------------
		
		$member = IPSMember::load( $member_id );
    	
		//-----------------------------------------
		// Check
		//-----------------------------------------

    	if ( ! $member['member_id'] )
    	{
			$this->returnString( 'error' );
    	}
		
		//-----------------------------------------
		// Regenerate comments...
		//-----------------------------------------
		
		$this->returnHtml( $this->comments->buildComments( $member ) );
	}
	

 	/**
	 * Deletes a comment on member's profile
	 *
	 * @access	private
	 * @return	void		[Prints to screen]
	 * @since	IPB 2.2.0.2006-08-02
	 */
 	private function _deleteComment()
 	{
 		//-----------------------------------------
 		// INIT
 		//-----------------------------------------
		
		$member_id			= intval( $this->request['member_id'] );
		$md5check			= IPSText::md5Clean( $this->request['md5check'] );
		$comment_id			= intval( $this->request['comment_id'] );

		//-----------------------------------------
		// MD5 check
		//-----------------------------------------
		
		if (  $md5check != $this->member->form_hash )
    	{
    		$this->returnString( 'error' );
    	}

		//-----------------------------------------
		// Delete
		//-----------------------------------------

    	$result = $this->comments->deleteComment( $member_id, $comment_id );
		
		/* Check for error */
		if( $result )
		{
			$this->returnString( $result );
		}
		else
		{
			$this->returnHtml( $this->comments->buildComments( IPSMember::load( $member_id ) ) );
		}
	}
	

 	/**
	 * Saves a comment on member's profile
	 *
	 * @access	private
	 * @return	void		[Prints to screen]
	 * @since	IPB 2.2.0.2006-08-02
	 */
 	private function _addComment()
 	{
		/* INIT */
		$member_id = intval( $this->request['member_id'] );

		$result = $this->comments->addCommentToDB( $member_id, IPSText::parseCleanValue( $_POST['comment'] ) );
		
		/* Check for error */
		if( $result AND $result != 'pp_comment_added_mod' )
		{
			$this->returnString( $result );
		}
		else
		{
			$this->returnHtml( $this->comments->buildComments( IPSMember::load( $member_id ), $new_id, $return_msg ) );
		}
	}
}