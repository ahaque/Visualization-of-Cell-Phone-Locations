<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Posting
 * Last Updated: $Date: 2009-08-19 20:17:18 -0400 (Wed, 19 Aug 2009) $
 * File Created By: Matt Mecham
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage  Forums 
 * @version		$Rev: 5032 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_forums_post_post extends ipsCommand
{
	/**
	 * Post Class
	 *
	 * @access	protected
	 * @var		object	Post Class
	 */
	protected $_postClass;
	
	/**
	 * Post Form Class
	 *
	 * @access	protected
	 * @var		object	Post Class
	 */
	protected $_postFormClass;
	
	/**
	 * Controller run function
	 * 
	 * @access	public
	 * @param	object	Registry
	 * @return	void
	 */
    public function doExecute( ipsRegistry $registry )
    {
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$doCodes = array(
							'new_post'       => array( '0'  , 'new'     ),
							'new_post_do'    => array( '1'  , 'new'     ),
							'reply_post'     => array( '0'  , 'reply'   ),
							'reply_post_do'  => array( '1'  , 'reply'   ),
							'edit_post'      => array( '0'  , 'edit'    ),
							'edit_post_do'   => array( '1'  , 'edit'    )
						);
						
		$do = $this->request['do'];

		//-----------------------------------------
        // Make sure our input doCode element is legal.
        //-----------------------------------------
        
        if ( ! isset( $doCodes[ $do ] ) )
        {
        	$this->registry->getClass('output')->showError( 'posting_bad_action', 103125 );
        }
		
		//-----------------------------------------
        // Check the input
        //-----------------------------------------
        
        $this->request[ 't' ] =  intval($this->request['t'] );
        $this->request[ 'p' ] =  intval($this->request['p'] );
        $this->request[ 'f' ] =  intval($this->request['f'] );
        $this->request[ 'st'] =  intval($this->request['st'] );
        
		//-----------------------------------------
		// Grab the post class
		//-----------------------------------------
		
		require_once( IPSLib::getAppDir( 'forums' ) . '/sources/classes/post/classPost.php' );
		require_once( IPSLib::getAppDir( 'forums' ) . '/sources/classes/post/classPostForms.php' );
		
		$this->_postClass = new classPostForms( $registry );
	
		//-----------------------------------------
		// Set up some stuff
		//-----------------------------------------
		
		# IDs
		$this->_postClass->setTopicID( $this->request['t'] );
		$this->_postClass->setPostID( $this->request['p'] );
		$this->_postClass->setForumID( $this->request['f'] );
		
		# Topic Title and description - use _POST as it is cleaned in the function.
		# We wrap this because the title may not be set when showing a form and would
		# throw a length error
		if ( $_POST['TopicTitle'] )
		{
			$this->_postClass->setTopicTitle( $_POST['TopicTitle'] );
			$this->_postClass->setTopicDescription( $_POST['TopicDesc'] );
		}
		
		# Is Preview Mode
		$this->_postClass->setIsPreview( ( $this->request['preview'] ) ? TRUE : FALSE );

		# Forum Data
		$this->_postClass->setForumData( $this->registry->getClass('class_forums')->forum_by_id[ $this->request['f'] ] );
		
		# Topic Data
		$this->_postClass->setTopicData( $this->DB->buildAndFetch( array( 
																			'select'   => 't.*, p.poll_only', 
																			'from'     => array( 'topics' => 't' ), 
																			'where'    => "t.forum_id={$this->_postClass->getForumID()} AND t.tid={$this->_postClass->getTopicID()}",
																			'add_join' => array(
																								array( 
																										'type'	=> 'left',
																										'from'	=> array( 'polls' => 'p' ),
																										'where'	=> 'p.tid=t.tid'
																									)
																								)
									) 							)	 );
		
		
		# Published
		$this->_postClass->setPublished( $this->_checkPostModeration( $doCodes[ $do ][1] ) === TRUE ? TRUE : FALSE );
	
		# Post Content
		$this->_postClass->setPostContent( isset( $_POST['Post'] ) ? $_POST['Post'] : '' );
		
		# Set Author
		$this->_postClass->setAuthor( $this->memberData['member_id'] );
	
		# Mod Options
		$this->_postClass->setModOptions( $this->request['mod_options'] );
	
		# Set Settings
		if ( ! $doCodes[ $do ][0] )
		{
			if ( $this->_postClass->getIsPreview() !== TRUE )
			{
				/* Showing form */
				$this->request['enablesig'] = ( isset( $this->request['enablesig'] ) ) ? $this->request['enablesig'] : 'yes';
				$this->request['enableemo'] = ( isset( $this->request['enableemo'] ) ) ? $this->request['enableemo'] : 'yes';
			}
		}
		
		$this->_postClass->setSettings( array( 'enableSignature' => ( $this->request['enablesig']  == 'yes' ) ? 1 : 0,
											   'enableEmoticons' => ( $this->request['enableemo']  == 'yes' ) ? 1 : 0,
											   'post_htmlstatus' => intval( $this->request['post_htmlstatus'] ),
											   'enableTracker'   => intval( $this->request['enabletrack'] ) ) );
											
		//-----------------------------------------
        // Checks...
        //-----------------------------------------
       
        $this->registry->getClass('class_forums')->forumsCheckAccess( $this->_postClass->getForumData('id'), 1, 'forum', $this->_postClass->getTopicData() );
		
        //-----------------------------------------
        // Are we allowed to post at all?
        //-----------------------------------------
        
        if ( $this->memberData['member_id'] )
        {
        	if ( $this->memberData['restrict_post'] )
        	{
        		if ( $this->memberData['restrict_post'] == 1 )
        		{
        			$this->registry->getClass('output')->showError( 'posting_restricted', 103126 );
        		}
        		
        		$post_arr = IPSMember::processBanEntry( $this->memberData['restrict_post'] );
        		
        		if ( time() >= $post_arr['date_end'] )
        		{
        			//-----------------------------------------
        			// Update this member's profile
        			//-----------------------------------------
        			
					IPSMember::save( $this->memberData['member_id'], array( 'core' => array( 'restrict_post' => 0 ) ) );
        		}
        		else
        		{
        			$this->registry->getClass('output')->showError( array( 'posting_off_susp', $this->registry->getClass( 'class_localization')->getDate( $post_arr['date_end'], 'LONG', 1 ) ), 103127 );
        		}
        	}
        	
        	//-----------------------------------------
        	// Flood check..
        	//-----------------------------------------
			
        	if (  ! in_array( $do, array( 'edit_post', 'edit_post_do', 'poll_add', 'poll_add_do' ) ) )
        	{
				if ( $this->settings['flood_control'] > 0 )
				{
					if ( $this->memberData['g_avoid_flood'] != 1 )
					{
						if ( time() - $this->memberData['last_post'] < $this->settings['flood_control'] )
						{
							$this->registry->getClass('output')->showError( array( 'flood_control', $this->settings['flood_control'] ), 103128 );
						}
					}
				}
			}
        }
        else if ( $this->member->is_not_human == 1 )
        {
        	$this->registry->getClass('output')->showError( 'posting_restricted', 103129 );
        }
        
        //-----------------------------------------
        // Show form or process?
        //-----------------------------------------
        
        if ( $doCodes[ $do ][0] )
        {
        	//-----------------------------------------
        	// Make sure we have a valid auth key
        	//-----------------------------------------
        	
        	if ( $this->request['auth_key'] != $this->member->form_hash )
			{
				$this->registry->getClass('output')->showError( 'posting_bad_auth_key', 20310 );
			}
			
			//-----------------------------------------
			// Guest captcha?
			//-----------------------------------------
			
			try
			{
				$this->_postClass->checkGuestCaptcha();
			}
			catch( Exception $error )
			{
				$this->_postClass->setPostError( $error->getMessage() );
				$this->showForm( $doCodes[ $do ][1] );
			}
        	
        	//-----------------------------------------
        	// Make sure we have a "Guest" Name..
        	//-----------------------------------------
        	
        	$this->_check_guest_name();
        	$this->checkDoublePost();
        	
        	$this->saveForm( $doCodes[ $do ][1] );
        }
        else
        {
        	$this->showForm( $doCodes[ $do ][1] );
        }
	}
	
	/**
	 * Save the form
	 *
	 * @access	public
	 * @param	string	Type of form to show
	 * @return	void
	 */
	public function saveForm( $type )
	{
		switch( $type )
		{
			case 'reply':
				try
				{
					if ( $this->_postClass->addReply() === FALSE )
					{
						$this->lang->loadLanguageFile( array('public_error'), 'core' );
						
						$this->showForm( $type );
					}
					
					$topic = $this->_postClass->getTopicData();
					$post  = $this->_postClass->getPostData();
							
					# Redirect
					if( $topic['_returnToMove'] )
					{
						ipsRegistry::getClass('output')->silentRedirect( "{$this->settings['base_url']}t={$topic['tid']}&amp;f={$topic['forum_id']}&amp;auth_key={$this->memberData['form_hash']}&amp;app=forums&amp;module=moderate&amp;section=moderate&amp;do=02" );
					}
					else if ( $this->settings['post_order_sort'] == 'desc' )
					{
						ipsRegistry::getClass('output')->silentRedirect( $this->settings['base_url']."showtopic=" . $this->_postClass->getTopicID() . "&#entry" . $this->_postClass->getPostID(), $topic['title_seo'] );
					}
					else
					{
						$posts = $topic['posts'] + 1;

						if( $this->moderator['post_q'] OR $this->memberData['g_is_supmod'] )
						{
							$posts += $topic['topic_queuedposts'];
						}

						if( ( $posts % $this->settings['display_max_posts'] ) == 0 )
						{
							$page = ( ($posts) / $this->settings['display_max_posts'] );
						}
						else
						{
							$page = ( ($posts) / $this->settings['display_max_posts'] );
							$page = ceil($page) - 1;
						}

						$page = $page * $this->settings['display_max_posts'];

						ipsRegistry::getClass('output')->silentRedirect( $this->settings['base_url']."showtopic={$topic['tid']}&st=$page&gopid={$post['pid']}&#entry{$post['pid']}", $topic['title_seo'] );
					}
				}
				catch( Exception $error )
				{
					if( $this->_postClass->getIsPreview() )
					{
						$this->showForm( $type );
					}

					$this->registry->getClass('output')->showError( $error->getMessage(), 103130 );
				}
			break;
			case 'new':
				try
				{
					if ( $this->_postClass->addTopic() === FALSE )
					{
						$this->lang->loadLanguageFile( array('public_error'), 'core' );
						
						$this->showForm( $type );
					}
					
					$topic = $this->_postClass->getTopicData();
					$post  = $this->_postClass->getPostData();
					
					# Redirect
					$this->registry->getClass('output')->silentRedirect( $this->settings['base_url']."showtopic={$topic['tid']}", $topic['title_seo'] );
				}
				catch( Exception $error )
				{
					$language	= $this->lang->words[ $error->getMessage() ] ? $this->lang->words[ $error->getMessage() ] : $error->getMessage();
					$this->registry->getClass('output')->showError( $language, 103131 );
				}
			break;
			case 'edit':
				try
				{
					if ( $this->_postClass->editPost() === FALSE )
					{
						$this->lang->loadLanguageFile( array('public_error'), 'core' );
						
						$this->showForm( $type );
					}
					
					$topic = $this->_postClass->getTopicData();
					$post  = $this->_postClass->getPostData();
					
					# Redirect
					ipsRegistry::getClass('output')->redirectScreen( $this->lang->words['post_edited'], $this->settings['base_url'] . "showtopic={$topic['tid']}&st=" . $this->request['st'] . "#entry{$post['pid']}", $topic['title_seo'] );
					
				}
				catch( Exception $error )
				{
					$this->registry->getClass('output')->showError( $error->getMessage(), 103132 );
				}
			break;
		}
	}
	
	/**
	 * Show the Form
	 *
	 * @access	public
	 * @param	string	Type of form to show
	 * @param	string	Error message
	 * @return	null	[ Returns HTML to the output engine for immediate printing ]
	 */
	public function showForm( $type )
	{
		switch( $type )
		{
			case 'reply':
				try
				{
					$this->_postClass->showReplyForm();
				}
				catch( Exception $error )
				{
					if ( $error->getMessage() == 'NO_POSTING_PPD' )
					{
						$_l = $this->_fetchPpdError();
						
						$this->registry->output->showError( $_l, 0 );
					}
					else
					{
						$this->registry->getClass('output')->showError( $this->lang->words[ $error->getMessage() ] ? $this->lang->words[ $error->getMessage() ] : $error->getMessage(), 103133 );
					}
				}
			break;
			case 'new':
				try
				{
					$this->_postClass->showTopicForm();
				}
				catch( Exception $error )
				{
					if ( $error->getMessage() == 'NOT_ENOUGH_POSTS' )
					{
						$this->registry->output->showError( 'posting_not_enough_posts', 103140 );
					}
					else if ( $error->getMessage() == 'NO_POSTING_PPD' )
					{
						$_l = $this->_fetchPpdError();
						
						$this->registry->output->showError( $_l, 0 );
					}
					else
					{
						$this->registry->getClass('output')->showError( $this->lang->words[ $error->getMessage() ] ? $this->lang->words[ $error->getMessage() ] : $error->getMessage(), 103134 );
					}
				}
			break;
			case 'edit':
				try
				{
					$this->_postClass->showEditForm();
				}
				catch( Exception $error )
				{
					if ( $error->getMessage() == 'NO_POSTING_PPD' )
					{
						$_l = $this->_fetchPpdError();
						
						$this->registry->output->showError( $_l, 0 );
					}
					else
					{
						$this->registry->getClass('output')->showError( $this->lang->words[ $error->getMessage() ] ? $this->lang->words[ $error->getMessage() ] : $error->getMessage(), 103135 );
					}
				}
			break;
		}
	}
	
	/**
	 * Check for double post
	 *
	 * @access	public
	 * @return void
	 **/
	public function checkDoublePost()
	{
		if( ! $this->_postClass->getIsPreview() )
		{
			if( time() - $this->memberData['last_post'] <= 5 )
			{
				if( $this->request['do'] == '01' or $this->request['do'] == '11' )
				{
					/* Redirect to the newest topic in the forum */
					$forum = $this->_postClass->getForumData();

					$this->DB->build( array( 
											'select' => 'tid',
											'from'   => 'topics',
											'where'  => "forum_id='" . $forum['id'] . "' AND approved=1",
											'order'  => 'last_post DESC',
											'limit'  => array( 0, 1 )
									)	);										   
					$this->DB->execute();
					
					$topic = $this->DB->fetch();
					
					$this->registry->getClass('output')->silentRedirect( "{$this->settings['base_url']}showtopic={$topic['tid']}" );
					exit();
				}
				else
				{
					/* It's a reply, so simply show the topic... */
					$this->registry->getClass('output')->silentRedirect( "{$this->settings['base_url']}showtopic={$this->request['t']}&amp;view=getlastpost" );
					exit();
				}
			}
		}
	}
	
	/**
	 * Check for guest's name being in use
	 *
	 * @access	private
	 * @return	void
	 **/
	private function _check_guest_name()
	{
		/* is this even used anymore? 
		   I disabled it 'cos it was adding the prefix and suffix twice when using a 'found' name
		   -- Matt */
		
		if ( ! $this->memberData['member_id'] )
		{
			$this->request['UserName'] = trim( $this->request['UserName'] );
			$this->request['UserName'] = str_replace( '<br />', '', $this->request['UserName'] );
			
			$this->request['UserName'] = $this->request['UserName'] ? $this->request['UserName'] : $this->lang->words['global_guestname'] ;
			$this->request['UserName'] = IPSText::mbstrlen( $this->request['UserName'] ) > $this->settings['max_user_name_length'] ? $this->lang->words['global_guestname'] : $this->request['UserName'];
			
		}
		
		return;
		
		/*if( ! $this->memberData['member_id'] )
		{
			$this->request['UserName'] = trim( $this->request['UserName'] );
			$this->request['UserName'] = str_replace( '<br />', '', $this->request['UserName'] );
			
			$this->request['UserName'] = $this->request['UserName'] ? $this->request['UserName'] : $this->lang->words['global_guestname'] ;
			$this->request['UserName'] = IPSText::mbstrlen( $this->request['UserName'] ) > $this->settings['max_user_name_length'] ? $this->lang->words['global_guestname'] : $this->request['UserName'];
			
			if ($this->request['UserName'] != $this->lang->words['global_guestname'])
			{
				$this->DB->build( array( 
											'select' => 'member_id, name, members_display_name, members_created_remote, email, member_group_id, member_login_key, ip_address, login_anonymous',
											'from'	 => 'members',
											'where'	 => 'members_l_username=\'' . trim( strtolower( $this->request['UserName'] ) ) . '\''
								)	);
				$this->DB->execute();
				
				if ( $this->DB->getTotalRows() )
				{
					$this->request['UserName'] =  $this->settings['guest_name_pre'] . $this->request['UserName'] . $this->settings['guest_name_suf'] ;
				}
			}
		}*/
	}
	
	/**
	 * Checks to see if this member is forced to have their posts
	 * moderated
	 *
	 * @access	private
	 * @param	string	Type of post (new, reply, edit, poll)
	 * @return	boolean	Whether to PUBLISH this topic or not
	 */
	private function _checkPostModeration( $type )
	{
		/* Does this member have mod_posts enabled? */
		if ( $this->memberData['mod_posts'] )
		{
			/* Mod Queue Forever */
			if ( $this->memberData['mod_posts'] == 1 )
			{
				return FALSE;
			}
			else
			{
				/* Do we need to remove the mod queue for this user? */
				$mod_arr = IPSMember::processBanEntry( $this->memberData['mod_posts'] );
				
				/* Yes, they are ok now */
				if ( time() >= $mod_arr['date_end'] )
				{
					IPSMember::save( $this->memberData['member_id'], array( 'core' => array( 'mod_posts' => 0 ) ) );
				}
				/* Nope, still don't want to see them */
				else
				{
					return FALSE;
				}
			}
		}
		
		/* Group can bypass mod queue */
		if( $this->memberData['g_avoid_q'] )
		{
			return TRUE;
		}
		
		/* Is the member's group moderated? */
		if ( $this->_postClass->checkGroupIsPostModerated( $this->memberData ) === TRUE )
		{
			return FALSE;
		}
		
		/* Check to see if this forum has moderation enabled */
		$forum = $this->_postClass->getForumData();

		switch( intval( $forum['preview_posts'] ) )
		{
			default:
			case 0:
				return TRUE;
			break;
			case 1:
				return FALSE;
			break;
			case 2:
				return ( $type == 'new' ) ? FALSE : TRUE;
			break;
			case 3:
				return ( $type == 'reply' ) ? FALSE : TRUE;
			break;
		}
		
		/* Our post can be seen! */
		return TRUE;
	}
	
	/**
	 * Fetch post per day error
	 *
	 * @access	public
	 * @return	string
	 */
	private function _fetchPpdError()
	{
		$_g = $this->caches['group_cache'][ $this->memberData['member_group_id'] ];
		$_l = sprintf( $this->lang->words['NO_POSTING_PPD'], $_g['g_ppd_limit'] );
		
		if ( $_g['g_ppd_unit'] )
		{
			if ( $_g['gbw_ppd_unit_type'] )
			{
				$_l .= "<br />" . sprintf( $this->lang->words['NO_PPD_DAYS'], $this->lang->getDate( ( time() + ( 86400 * $_g['g_ppd_unit'] ) ) - $this->memberData['joined'], 'LONG' ) );
			}
			else
			{
				$_l .= "<br />" . sprintf( $this->lang->words['NO_PPD_POSTS'], $_g['g_ppd_unit'] - $this->memberData['posts'] );
			}
		}
		
		return $_l;
	}
}
