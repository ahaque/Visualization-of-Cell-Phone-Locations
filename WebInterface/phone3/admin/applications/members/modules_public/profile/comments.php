<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Profile View
 * Last Updated: $Date: 2009-05-18 22:05:12 -0400 (Mon, 18 May 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Members
 * @link		http://www.
 * @since		20th February 2002
 * @version		$Revision: 4668 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_members_profile_comments extends ipsCommand
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
		// Get HTML and skin
		//-----------------------------------------

		$this->registry->class_localization->loadLanguageFile( array( 'public_profile' ), 'members' );

		//-----------------------------------------
		// Can we access?
		//-----------------------------------------
		
		if ( !$this->memberData['g_mem_info'] )
 		{
 			$this->registry->output->showError( 'comments_profiles', 10231 );
		}

		switch( $this->request['do'] )
		{
			case 'view':
			default:
				$this->_viewComments();
			break;
			
			case 'save':
				$this->_saveComment();
			break;
			
			case 'delete':
				$this->_deleteComment();
			break;
			
			case 'approve':
				$this->_approveComment();
			break;
			
			case 'add_new_comment':
				$this->_doAddComment();
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
		$comment_id			= intval( $this->request['comment_id'] );
		//-----------------------------------------
		// Try it. You might like it.
		//-----------------------------------------
		
		require_once( IPSLib::getAppDir( 'members' ) . '/sources/comments.php' );
		$comment_lib = new profileCommentsLib( $this->registry );
		
		$result = $comment_lib->approveComment( $member_id, $comment_id );
		
		/* Check for error */
		if( $result )
		{
			$this->registry->output->showError( $result, 10232 );
		}
		else
		{
			$member = IPSMember::load( $member_id );
			$this->registry->output->redirectScreen( $this->lang->words['comment_was_approved'], $this->settings['base_url'] . 'showuser=' . $member_id, $member['members_seo_name'] );
		}
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
		$comment_id			= intval( $this->request['comment_id'] );
		
		//-----------------------------------------
		// Try it. You might like it.
		//-----------------------------------------
		
		require_once( IPSLib::getAppDir( 'members' ) . '/sources/comments.php' );
		$comment_lib = new profileCommentsLib( $this->registry );
		
		$result = $comment_lib->deleteComment( $member_id, $comment_id );
		
		/* Check for error */
		if( $result )
		{
			$this->registry->output->showError( $result, 10232 );
		}
		else
		{
			$member = IPSMember::load( $member_id );
			$this->registry->output->redirectScreen( $this->lang->words['comment_was_deleted'], $this->settings['base_url'] . 'showuser=' . $member_id, $member['members_seo_name'] );
		}
	}
	
 	/**
	 * Updates the comments
	 *
	 * @access	private
	 * @return	void			[Prints to screen]
	 * @since	IPB 2.2.0.2006-08-15
	 */
 	private function _saveComment()
 	{
 		//-----------------------------------------
 		// INIT
 		//-----------------------------------------
		
		$member_id		= intval( $this->request['member_id'] );
		$md5check		= IPSText::md5Clean( $this->request['md5check'] );
		$content		= '';
		$comment_ids	= array();
		$final_ids		= '';
		
		//-----------------------------------------
		// MD5 check
		//-----------------------------------------
		
		if (  $md5check != $this->member->form_hash )
    	{
    		die( '' );
    	}

		//-----------------------------------------
		// My tab?
		//-----------------------------------------
		
		if ( $member_id != $this->memberData['member_id'] AND !$this->memberData['g_is_supmod'] )
    	{
    		die( '' );
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
			die( '' );
    	}

		//-----------------------------------------
		// Grab comment_ids
		//-----------------------------------------
		
		if ( is_array( $this->request['pp-checked'] ) AND count( $this->request['pp-checked'] ) )
		{
			foreach( $this->request['pp-checked'] as $key => $value )
			{
				$key = intval( $key );
				
				if ( $value )
				{
					$comment_ids[ $key ] = $key;
				}
			}
		}
	
		//-----------------------------------------
		// Update the database...
		//-----------------------------------------
		
		if ( is_array( $comment_ids ) AND count( $comment_ids ) )
		{
			$final_ids = implode( ',', $comment_ids );
			
			//-----------------------------------------
			// Now update...
			//-----------------------------------------

			switch( $this->request['pp-moderation'] )
			{
				case 'approve':
					$this->DB->update( 'profile_comments', array( 'comment_approved' => 1 ), 'comment_id IN(' . $final_ids . ')' );
					break;
				case 'unapprove':
					$this->DB->update( 'profile_comments', array( 'comment_approved' => 0 ), 'comment_id IN(' . $final_ids . ')' );
					break;
				case 'delete':
					$this->DB->delete( 'profile_comments', 'comment_id IN(' . $final_ids . ')' );
					break;
			}
		}
		
		//-----------------------------------------
		// Bounce...
		//-----------------------------------------
		
		$this->registry->output->silentRedirect( $this->settings['base_url'] . 'app=members&section=comments&module=profile&member_id=' . $member_id . '&do=list&_saved=1&___msg=pp_comments_updated&md5check=' . $this->member->form_hash );
	}

 	/**
	 * Loads the content for the comments tab
	 *
	 * @access	private
	 * @return	void		[Prints to screen]
	 * @since	IPB 2.2.0.2006-08-02
	 */
 	private function _viewComments()
 	{
 		//-----------------------------------------
 		// INIT
 		//-----------------------------------------
		
		$member_id 		 = intval( $this->request['member_id'] );
		$md5check  		 = IPSText::md5Clean( $this->request['md5check'] );
		$content   		 = '';
		$comment_perpage = 10;
		$pages           = '';
		$start			 = intval( $this->request['st'] );
		$sql_extra       = '';
		
		//-----------------------------------------
		// MD5 check
		//-----------------------------------------
		
		if (  $md5check != $this->member->form_hash )
    	{
    		die( '' );
    	}

		//-----------------------------------------
		// Not my tab? So no moderation...
		//-----------------------------------------
		
		if (  ( $member_id != $this->memberData['member_id'] ) AND ( ! $this->memberData['g_is_supmod'] ) )
    	{
    		$sql_extra = ' AND comment_approved=1';
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
			die( '' );
    	}

		//-----------------------------------------
		// How many comments must a man write down
		// before he is considered a spammer?
		//-----------------------------------------
		
		$comment_count = $this->DB->buildAndFetch( array( 'select'	=> 'count(*) as count_comment',
																 'from'		=> 'profile_comments',
																 'where'	=> 'comment_for_member_id=' . $member_id . $sql_extra 
														) 		);
																		
		//-----------------------------------------
		// Pages
		//-----------------------------------------
		
		$pages = $this->registry->output->generatePagination( array(	'totalItems'		=> intval( $comment_count['count_comment'] ),
																		'itemsPerPage'		=> $comment_perpage,
																		'currentStartValue'	=> $start,
																		'baseUrl'			=> $this->settings['base_url'] . 'app=members&amp;section=comments&amp;module=profile&amp;member_id=' . $member_id . '&amp;do=view&amp;md5check=' . $this->member->form_hash,																	
														 ) );
												
		//-----------------------------------------
		// Regenerate comments...
		//-----------------------------------------
		
		$this->DB->build( array( 'select'		=> 'pc.*',
										'from'		=> array( 'profile_comments' => 'pc' ),
										'where'		=> 'pc.comment_for_member_id='.$member_id . $sql_extra,
										'order'		=> 'pc.comment_date DESC',
										'limit'		=> array( $start, $comment_perpage ),
										'add_join'	=> array( 
															0 => array( 
																		'select'	=> 'm.members_display_name, m.login_anonymous',
																		'from'		=> array( 'members' => 'm' ),
																		'where'		=> 'm.member_id=pc.comment_by_member_id',
																		'type'		=> 'left' 
																		),
															1 => array( 
																		'select'	=> 'pp.*',
																		'from'		=> array( 'profile_portal' => 'pp' ),
																		'where'		=> 'pp.pp_member_id=m.member_id',
																		'type'		=> 'left' 
																		),	
															) 
								) 		);
		$o = $this->DB->execute();
		
		while( $row = $this->DB->fetch($o) )
		{
			$row['comment_content'] = IPSText::wordwrap( $row['comment_content'], '19', ' ' );
			
			$row = IPSMember::buildDisplayData( $row, 0 );
			
			if( !$row['members_display_name_short'] )
			{
				$row = array_merge( $row, IPSMember::setUpGuest() );
			}
			
			$comments[] = $row;
		}

		//-----------------------------------------
		// Ok.. show the settings
		//-----------------------------------------
		
		$content = $this->registry->getClass('output')->getTemplate('profile')->showIframeComments( $member, $comments, $pages );
		
		$this->registry->getClass('output')->setTitle( $this->settings['board_name'] );
		$this->registry->getClass('output')->popUpWindow( $content );
	}
	
	/**
	 * Save the new comment
	 *
	 * @access	private
	 * @return	void
	 */
	private function _doAddComment()
	{
		/* INIT */
		$member_id = intval( $this->request['member_id'] );
		$comment   = $this->request['comment_text'];

		/* Add Comment */
		require_once( IPSLib::getAppDir( 'members' ) . '/sources/comments.php' );
		$comment_lib = new profileCommentsLib( $this->registry );
		
		$result = $comment_lib->addCommentToDB( $member_id, $comment );
		
		/* Check for error */
		if( $result AND $result != 'pp_comment_added_mod' )
		{
			$this->registry->output->showError( $result, 10232 );
		}
		else if ( $result == 'pp_comment_added_mod' )
		{
			$this->registry->output->redirectScreen( $this->lang->words[ $result ], $this->settings['base_url'] . 'showuser=' . $member_id );
		}
		else
		{
			$this->registry->output->silentRedirect( $this->settings['base_url'] . 'showuser=' . $member_id );
		}		
	}
}