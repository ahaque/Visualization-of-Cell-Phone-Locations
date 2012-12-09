<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Comments library
 * Last Updated: $Date: 2009-07-08 21:23:44 -0400 (Wed, 08 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Members
 * @link		http://www.
 * @since		20th February 2002
 * @version		$Revision: 4856 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class profileCommentsLib
{
	/**
	 * Registry object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $registry;
	
	/**
	 * Database object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $DB;
	
	/**
	 * Settings object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $settings;
	
	/**
	 * Request object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $request;
	
	/**
	 * Language object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $lang;
	
	/**
	 * Member object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $member;
	
	/**
	 * Cache object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $cache;
	
	/**
	 * Cached parsed members
	 *
	 * @access	protected
	 * @var		array
	 */	
	protected $parsedMembers;
		
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @return	void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make object */
		$this->registry = $registry;
		$this->DB       = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->lang     = $this->registry->getClass('class_localization');
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache    = $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
		
		$this->lang->loadLanguageFile( array( 'public_profile' ) );
	}
	
	/**
	 * Adds a new profile comment to the database
	 *
	 * @access	public
	 * @param	integer	$comment_for_id	Member id that this comment is for
	 * @param	string	$comment		Text of the comment to create
	 * @return	string					Error key on failure, blank on success
	 */
	public function addCommentToDB( $comment_for_id, $comment )
	{
		/* Load the member that this comment is for */
		$member = IPSMember::load( $comment_for_id );

		/* Make sure we found a member */
		if ( ! $member['member_id'] )
		{
			return 'error';
		}
		
		/* Are we allowed to comment? */
		if( ! $this->memberData['g_reply_other_topics'] )
		{
			return 'nopermission';
		}
		
		if( $this->memberData['restrict_post'] )
		{
			if( $this->memberData['restrict_post'] == 1 )
			{
				return 'nopermission';
			}
			
			$post_arr = IPSMember::processBanEntry( $this->memberData['restrict_post'] );
			
			if( time() >= $post_arr['date_end'] )
			{
				/* Update this member's profile */
				IPSMember::save( $this->memberData['member_id'], array( 'core' => array( 'restrict_post' => 0 ) ) );
			}
			else
			{
				return 'nopermission';
			}
		}

		/* Does this member have mod_posts enabled? */
		$comment_approved = 1;
		
		if( $this->memberData['mod_posts'] )
		{
			if( $this->memberData['mod_posts'] == 1 )
			{
				$comment_approved = 0;
			}
			else
			{
				$mod_arr = IPSMember::processBanEntry( $this->memberData['mod_posts'] );
				
				if( time() >= $mod_arr['date_end'] )
				{
					/* Update this member's profile */
					IPSMember::save( $this->memberData['member_id'], array( 'core' => array( 'mod_posts' => 0 ) ) );
				}
				else
				{
					$comment_approved = 0;
				}
			}
		}

		/* Format the comment */
		$comment = IPSText::truncate( $comment, 400 );
		$comment = preg_replace( "#(\r\n|\r|\n|<br />|<br>){1,}#s", "\n", $comment );
		$comment = trim( IPSText::getTextClass('bbcode')->stripBadWords( $comment ) );

		/* Make sure we still have a comment */
		if ( ! $comment )
		{
			return 'error-no-comment';
		}

		/* Comment requires approval? */
		if( $member['pp_setting_moderate_comments'] AND $member['member_id'] != $this->memberData['member_id'] )
		{
			$comment_approved = 0;
		}
		
		/* Member is ignoring you! */
		if( $comment_approved )
		{ 
			$_you_are_being_ignored = explode( ",", $member['ignored_users'] );
		
			if( is_array( $_you_are_being_ignored ) and count( $_you_are_being_ignored ) )
			{
				if( in_array( $this->memberData['member_id'], $_you_are_being_ignored ) )
				{
					$comment_approved = 0;
				}
			}
		}
	
		/* Add comment to the DB... */
		$this->DB->insert( 'profile_comments', array(
														'comment_for_member_id'	=> $comment_for_id,
														'comment_by_member_id'	=> $this->memberData['member_id'],
														'comment_date'			=> time(),
														'comment_ip_address'	=> $this->member->ip_address,
														'comment_approved'		=> $comment_approved,
														'comment_content'		=> nl2br( $comment ) 
							)						);
		
		$new_id = $this->DB->getInsertId();
		
		/* Send notifications.. */
		if( ! $comment_approved AND $member['pp_setting_notify_comments'] AND ( $member['member_id'] != $this->memberData['member_id'] ) )
		{
			IPSText::getTextClass( 'email' )->getTemplate( "new_comment_request", $member['language'] );
				
			IPSText::getTextClass( 'email' )->buildMessage( array( 
																	'MEMBERS_DISPLAY_NAME'	=> $member['members_display_name'],
																	'COMMENT_NAME'			=> $this->memberData['members_display_name'],
																	'LINK'					=> $this->settings['board_url'] . '/index.' . $this->settings['php_ext'] . '?showuser=' . $member['member_id'] 
															) 	);
			 
			$message	= IPSText::getTextClass( 'email' )->message;
			$subject	= IPSText::getTextClass( 'email' )->subject;
			$to			= $member;
			$from		= $this->memberData;
			$return_msg	= 'pp_comment_added_mod';
		}
		else if( $member['pp_setting_notify_comments'] AND ( $member['member_id'] != $this->memberData['member_id'] ) )
		{
			IPSText::getTextClass( 'email' )->getTemplate( "new_comment_added", $member['language'] );

			IPSText::getTextClass( 'email' )->buildMessage( array( 
																	'MEMBERS_DISPLAY_NAME'	=> $member['members_display_name'],
																	'COMMENT_NAME'			=> $this->memberData['members_display_name'],
																	'LINK'					=> $this->settings['board_url'] . '/index.' . $this->settings['php_ext'] . '?showuser=' . $member['member_id'] 
															)	 );

			$message	= IPSText::getTextClass( 'email' )->message;
			$subject	= IPSText::getTextClass( 'email' )->subject;
			$to			= $member;
			$from		= $this->memberData;
			$return_msg	= '';
		}
		
		/* Got anything to send? */
		if( $message AND $subject )
		{
			/* Email ? */
			if( $member['pp_setting_notify_comments'] == 'email' OR ( $member['pp_setting_notify_comments'] AND $member['members_disable_pm'] ) )
			{
				IPSText::getTextClass( 'email' )->subject	= $subject;
				IPSText::getTextClass( 'email' )->message	= $message;
				IPSText::getTextClass( 'email' )->to		= $to['email'];
				
				IPSText::getTextClass( 'email' )->sendMail();
			}
			/* PM? */
			else if ( $member['pp_setting_notify_comments'] != 'none' )
			{
				require_once( IPSLib::getAppDir( 'members' ) . '/sources/classes/messaging/messengerFunctions.php' );
				$this->messengerFunctions = new messengerFunctions( $this->registry );

				try
				{
				 	$this->messengerFunctions->sendNewPersonalTopic( $to['member_id'], 
															$from['member_id'], 
															array(), 
															$subject, 
															IPSText::getTextClass( 'editor' )->method == 'rte' ? nl2br($message) : $message, 
															array( 'origMsgID'			=> 0,
																	'fromMsgID'			=> 0,
																	'postKey'			=> md5(microtime()),
																	'trackMsg'			=> 0,
																	'addToSentFolder'	=> 0,
																	'hideCCUser'		=> 0,
																	'forcePm'			=> 1,
																	'isSystem'          => 1
																)
															);
				}
				catch( Exception $error )
				{
					$msg		= $error->getMessage();
					$toMember	= IPSMember::load( $toMemberID, 'core', 'displayname' );

					if( strstr( $msg, 'BBCODE_' ) )
					{
						$msg = str_replace( 'BBCODE_', $msg, 10258 );

						$this->registry->output->showError( $msg );
					}
					else if ( isset($this->lang->words[ 'err_' . $msg ]) )
					{
						$this->lang->words[ 'err_' . $msg ] = $this->lang->words[ 'err_' . $msg ];
						$this->lang->words[ 'err_' . $msg ] = str_replace( '#NAMES#'   , implode( ",", $this->messengerFunctions->exceptionData ), $this->lang->words[ 'err_' . $msg ] );
						$this->lang->words[ 'err_' . $msg ] = str_replace( '#TONAME#'  , $toMember['members_display_name']    , $this->lang->words[ 'err_' . $msg ] );
						$this->lang->words[ 'err_' . $msg ] = str_replace( '#FROMNAME#', $this->memberData['members_display_name'], $this->lang->words[ 'err_' . $msg ] );
						
						$this->registry->output->showError( 'err_' . $msg, 10259 );
					}
					else
					{
						$_msgString = $this->lang->words['err_UNKNOWN'] . ' ' . $msg;
						
						$this->registry->output->showError( 'err_UNKNOWN', 10260 );
					}
				}
			}
		}
		
		return $return_msg;
	}	
	
	/**
	 * Approve a comment
	 *
	 * @access	public
	 * @param	int			Member ID of user who is attempting to access
	 * @param	int			Comment ID to approve
	 * @return	mixed		Error string or nothing on success
	 */
	public function approveComment( $member_id, $comment_id )
	{
		//-----------------------------------------
		// Load member
		//-----------------------------------------
		
		$member     = IPSMember::load( intval( $member_id ) );
    	$comment_id = intval( $comment_id );

		//-----------------------------------------
		// Load member
		//-----------------------------------------
		
		$member = IPSMember::load( $member_id );
    	
		//-----------------------------------------
		// Check
		//-----------------------------------------

    	if ( ! $member['member_id'] )
    	{
			return 'nopermission';
    	}

		//-----------------------------------------
		// Get comment
		//-----------------------------------------

		$comment = $this->DB->buildAndFetch( array( 'select' => '*',
													'from'   => 'profile_comments',
													'where'  => 'comment_id=' . $comment_id ) );

		//-----------------------------------------
		// Can remove?
		//-----------------------------------------
		
		if ( ( $member['member_id'] == $this->memberData['member_id'] AND ( $member['member_id'] == $comment['comment_for_member_id'] ) ) OR $this->memberData['g_is_supmod'] )
		{
			$this->DB->update( 'profile_comments', array( 'comment_approved' => 1 ), 'comment_id=' . $comment_id );
		}
		else
		{
			return 'nopermission';
		}

		return '';
	}
	
	/**
	 * Delete a comment
	 *
	 * @access	public
	 * @param	int			Member ID of user who is attempting to access
	 * @param	int			Comment ID to remove
	 * @return	mixed		Error string or nothing on success
	 */
	public function deleteComment( $member_id, $comment_id )
	{
		//-----------------------------------------
		// Load member
		//-----------------------------------------
		
		$member     = IPSMember::load( intval( $member_id ) );
    	$comment_id = intval( $comment_id );

		//-----------------------------------------
		// Check
		//-----------------------------------------

    	if ( ! $member['member_id'] )
    	{
			return 'nopermission';
    	}

		//-----------------------------------------
		// Get comment
		//-----------------------------------------

		$comment = $this->DB->buildAndFetch( array( 'select' => '*',
													'from'   => 'profile_comments',
													'where'  => 'comment_id=' . $comment_id ) );
												
		
		//-----------------------------------------
		// Can remove?
		//-----------------------------------------
		
		if ( ( $member['member_id'] == $this->memberData['member_id'] AND ( $member['member_id'] == $comment['comment_for_member_id'] ) ) OR $this->memberData['g_is_supmod'] )
		{
			$this->DB->delete( 'profile_comments', 'comment_id=' . $comment_id );
		}
		else
		{
			return 'nopermission';
		}
		
		return '';
	}
	
 	/**
	 * Builds comments
	 *
	 * @access	public
	 * @param	array 		Member information
	 * @param	boolean		Use a new id
	 * @param	string		Message to display
	 * @return	string		Comment HTML
	 * @since	IPB 2.2.0.2006-08-02
	 */
	public function buildComments( $member, $new_id=0, $return_msg='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$comments			= array();
		$member_id			= intval( $member['member_id'] );
		$comment_perpage	= 15; //intval( $member['pp_setting_count_comments'] );
		$comment_html		= 0;
		$comment_start		= intval($this->request['st']);
		$comment_approved	= ( $this->memberData['member_id'] == $member['member_id'] OR $this->memberData['g_is_supmod'] ) ? '' : ' AND ( pc.comment_approved=1 OR ( pc.comment_approved=0 AND pc.comment_by_member_id=' . $member_id . ') )';
		
		//-----------------------------------------
		// Not showing comments?
		//-----------------------------------------
		
		if ( $comment_perpage < 1 )
		{
			return '';
		}
		
		//-----------------------------------------
		// Regenerate comments...
		//-----------------------------------------
		
		$this->DB->build( array( 
								'select'		=> 'pc.*',
								 'from'			=> array( 'profile_comments' => 'pc' ),
								 'where'		=> 'pc.comment_for_member_id=' . $member_id . $comment_approved,
								 'order'		=> 'pc.comment_date DESC',
								 'limit'		=> array( $comment_start, $comment_perpage ),
								 'calcRows'		=> TRUE,
								 'add_join'		=> array(
														array(
																'select' => 'm.members_display_name, m.members_seo_name, m.posts, m.last_activity, m.member_group_id, m.member_id, m.last_visit, m.warn_level',
								 								'from'   => array( 'members' => 'm' ),
								 								'where'  => 'm.member_id=pc.comment_by_member_id',
								 								'type'   => 'left' 
															),
								 					  	array( 
																'select' => 'pp.*',
								 								'from'   => array( 'profile_portal' => 'pp' ),
								 								'where'  => 'pp.pp_member_id=m.member_id',
								 								'type'   => 'left' ),	
								 							) 
							)	);
		$o		= $this->DB->execute();
		$max	= $this->DB->fetchCalculatedRows();

		while( $row = $this->DB->fetch($o) )
		{
			$row['_comment_date']   = ipsRegistry::getClass( 'class_localization')->getDate( $row['comment_date'], 'TINY' );

			$row = IPSMember::buildDisplayData( $row );
			
			if( !$row['members_display_name_short'] )
			{
				$row = array_merge( $row, IPSMember::setUpGuest() );
			}
			
			$comments[] = $row;
		}
		
		//-----------------------------------------
		// Pagination
		//-----------------------------------------
		
		$links	= $this->registry->output->generatePagination(  array( 'totalItems'  	    => $max,
																	   'itemsPerPage'		=> $comment_perpage,
																	   'currentStartValue'	=> $comment_start,
																	   'baseUrl'	  	    => "showuser={$member_id}",
																	   'seoTitle'			=> $member['members_seo_name'],
															)		);

		$comment_html = $this->registry->getClass('output')->getTemplate('profile')->showComments( $member, $comments, $new_id, $return_msg, $links );

		//-----------------------------------------
		// Return it...
		//-----------------------------------------
		
		return $comment_html;
	}
	
}