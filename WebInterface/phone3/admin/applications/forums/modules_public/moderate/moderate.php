<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Moderator actions
 * Last Updated: $Date: 2009-08-30 23:34:46 -0400 (Sun, 30 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Forums
 * @version		$Revision: 5064 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_forums_moderate_moderate extends ipsCommand
{
	/**
	 * Temporary stored output HTML
	 *
	 * @access	public
	 * @var		string
	 */
	public $output;
	
	/**
	 * Moderator function library
	 *
	 * @access	private
	 * @var		object
	 */
	public $modLibrary;

	/**
	 * Moderator information
	 *
	 * @access	private
	 * @var		array		Array of moderator details
	 */
	private $moderator		= array();

	/**
	 * Forum information
	 *
	 * @access	private
	 * @var		array		Array of forum details
	 */
	private $forum			= array();

	/**
	 * Topic information
	 *
	 * @access	private
	 * @var		array		Array of topic details
	 */
	private $topic			= array();

	/**
	 * Trash can forum ID
	 *
	 * @access	private
	 * @var		integer		Forum id
	 */
	private $trash_forum	= 0;

	/**
	 * Trash can in use flag
	 *
	 * @access	private
	 * @var		boolean		Trash can in use
	 */
	private $trash_inuse	= false;
	
	/**
	 * Topic ids stored
	 *
	 * @access	private
	 * @var		array		Topic ids for multimoderation
	 */
	private $tids			= array();
	
	/**
	 * Post ids stored
	 *
	 * @access	private
	 * @var		array		Post ids for multimoderation
	 */
	private $pids			= array();
	
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
		// Load language & skin files
		//-----------------------------------------
		
		ipsRegistry::getClass( 'class_localization')->loadLanguageFile( array( 'public_mod' ) );

		//-----------------------------------------
		// Check the input
		//-----------------------------------------
		
		$this->_setupAndCheckInput();
		
		//-----------------------------------------
		// Load moderator functions
		//-----------------------------------------
		
		require( IPSLib::getAppDir( 'forums' ) . '/sources/classes/moderate.php');
		$this->modLibrary = new moderatorLibrary( $this->registry );
		$this->modLibrary->init( $this->forum );

		//-----------------------------------------
		// Trash-can set up
		//-----------------------------------------
		
		$this->_takeOutTrash();
	  
		//-----------------------------------------
		// Convert the code ID's into something
		// use mere mortals can understand....
		//-----------------------------------------
		
		switch ( $this->request['do'] )
		{
			case '02':
				$this->_moveForm();
			break;
			case '03':
				$this->_deleteForm();
			break;
			case '04':
				$this->_deletePost();
			break;
			case '05':
				$this->_editform();
			break;
			case '00':
				$this->_closeTopic();
			break;
			case '01':
				$this->_openTopic();
			break;
			case '08':
				$this->_deleteTopic();
			break;
			case '12':
				$this->_doEdit();
			break;
			case '14':
				$this->_doMove();
			break;
			case '15':
				$this->_topicPinAlter( 'pin' );
			break;
			case '16':
				$this->_topicPinAlter( 'unpin' );
			break;
			//-----------------------------------------
			// Unsubscribe
			//-----------------------------------------
			case '30':
				$this->_unsubscribeAllForm();
			break;
			case '31':
				$this->_unsubscribeAll();
			break;
			//-----------------------------------------
			// Merge Start
			//-----------------------------------------
			case '60':
				$this->_mergeStart();
			break;
			case '61':
				$this->_mergeComplete();
			break;
			//-----------------------------------------
			// Topic History
			//-----------------------------------------
			case '90':
				$this->_topicHistory();
			break;
			//-----------------------------------------
			// Multi---
			//-----------------------------------------	
			case 'topicchoice':
				$this->_multiTopicModify();
			break;
			//-----------------------------------------
			// Multi---
			//-----------------------------------------	
			case 'postchoice':
				$this->_multiPostModify();
			break;
			//-----------------------------------------
			// Resynchronize Forum
			//-----------------------------------------
			case 'resync':
				$this->_resyncForum();
			break;
			//-----------------------------------------
			// Prune / Move Topics
			//-----------------------------------------
			case 'prune_start':
				$this->_pruneStart();
			break;
			case 'prune_finish':
				$this->_pruneFinish();
			break;
			case 'prune_move':
				$this->_pruneMove();
			break;
			//-----------------------------------------
			// Add. topic view func.
			//-----------------------------------------
			case 'topic_approve':
				$this->_topicApproveAlter('approve');
			break;
			case 'topic_unapprove':
				$this->_topicApproveAlter('unapprove');
			break;
			//-----------------------------------------
			// Edit member
			//-----------------------------------------
			case 'editmember':
				$this->_editMember();
			break;
			case 'doeditmember':
				$this->_doEditMember();
			break;
			
			case 'setAsSpammer':
				$this->_setAsSpammer();
			break;
			
			default:
				$this->_showError();
			break;
		}
		
		// If we have any HTML to print, do so...
		
		$this->registry->output->addContent( $this->output );
		$this->registry->getClass('output')->sendOutput();
	}
	
	/**
	 * Flag a user account as a spammer
	 *
	 * @access	private
	 * @return	void		Outputs error screen
	 */
	private function _setAsSpammer()
	{
		/* INIT */
		$member_id = intval( $this->request['member_id'] );
		$toSave	   = array( 'core' => array( 'bw_is_spammer' => 1 ) );
		
		/* Load member */
		$member = IPSMember::load( $member_id );
		
		if ( ! $member['member_id'] )
		{
			$this->_showError( 'moderate_no_permission', 10311900 );
		}
		
		/* Check permissions */
		$this->_genericPermissionCheck('bw_flag_spammers');
		
		/* Protected group? */
		if ( strstr( ','.$this->settings['warn_protected'].',', ','.$member['member_group_id'].',' ) )
		{
			$this->_showError( 'moderate_no_permission', 10311901 );
		}
		
		/* What do to.. */
		if ( $this->settings['spm_option'] )
		{
			switch( $this->settings['spm_option'] )
			{
				case 'disable':
					$toSave['core']['restrict_post']      = 1;
					$toSave['core']['members_disable_pm'] = 2;
				break;
				case 'unapprove':
					$toSave['core']['restrict_post']      = 1;
					$toSave['core']['members_disable_pm'] = 2;
					/* Unapprove posts and topics */
					$this->modLibrary->toggleApproveMemberContent( $member_id, FALSE, 'all', intval( $this->settings['spm_post_days'] ) * 24 );
				break;
				case 'ban':
					/* Unapprove posts and topics */
					$this->modLibrary->toggleApproveMemberContent( $member_id, FALSE, 'all', intval( $this->settings['spm_post_days'] ) * 24 );
					
					$toSave	= array(
									'core'				=> array(
																'member_banned'		=> 1,
																'title'				=> '',
																'bw_is_spammer'		=> 1,
																),
									'extendedProfile'	=> array(
																'signature'			=> '',
																'pp_bio_content'	=> '',
																'pp_about_me'		=> '',
																'pp_status'			=> '',
																)
									);

					//-----------------------------------------
					// Avatar
					//-----------------------------------------
					
					$toSave['extendedProfile']['avatar_location']	= "";
					$toSave['extendedProfile']['avatar_size']		= "";
		
					try
					{
						IPSMember::getFunction()->removeAvatar( $member['member_id'] );
					}
					catch( Exception $e )
					{
						// Maybe should show an error or something
					}
					
					//-----------------------------------------
					// Photo
					//-----------------------------------------
					
					IPSMember::getFunction()->removeUploadedPhotos( $member['member_id'] );
		
					$toSave['extendedProfile'] = array_merge( $toSave['extendedProfile'], array(
													'pp_main_photo'		=> '',
													'pp_main_width'		=> '',
													'pp_main_height'	=> '',
													'pp_thumb_photo'	=> '',
													'pp_thumb_width'	=> '',
													'pp_thumb_height'	=> ''
													)	);

					//-----------------------------------------
					// Profile fields
					//-----------------------------------------
			
					require_once( IPS_ROOT_PATH . 'sources/classes/customfields/profileFields.php' );
					$fields = new customProfileFields();
					
					$fields->member_data = $member;
					$fields->initData( 'edit' );
					$fields->parseToSave( array() );
					
					if ( count( $fields->out_fields ) )
					{
						$toSave['customFields']	= $fields->out_fields;
					}

					//-----------------------------------------
					// Update signature content cache
					//-----------------------------------------
					
					IPSContentCache::update( $member['member_id'], 'sig', '' );
				break;
			}
		}
		
		/* Send an email */
		if ( $this->settings['spm_notify'] AND ( $this->settings['email_out'] != $this->memberData['email'] ) )
		{
			IPSText::getTextClass('email')->getTemplate( 'possibleSpammer' );

			IPSText::getTextClass('email')->buildMessage( array( 'DATE'         => $this->registry->class_localization->getDate( $member['joined'], 'LONG', 1 ),
																 'MEMBER_NAME'  => $member['members_display_name'],
																 'IP'			=> $member['ip_address'],
																 'EMAIL'		=> $member['email'],
																 'LINK'         => $this->registry->getClass('output')->buildSEOUrl("showuser=" . $member['member_id'], 'public', $member['members_seo_name'], 'showuser') ) );

			IPSText::getTextClass('email')->subject = $this->lang->words['new_registration_email_spammer'] . $this->settings['board_name'];
			IPSText::getTextClass('email')->to      = $this->settings['email_out'];
			IPSText::getTextClass('email')->sendMail();
		}
		
		/* Flag them as a spammer */
		IPSMember::save( $member_id, $toSave );
		
		/* Send Spammer to Spam Service */
		if( $this->settings['spam_service_send_to_ips'] && $this->settings['spam_service_api_key'] )
		{
			IPSMember::querySpamService( $member['email'], $member['ip_address'], 'markspam' );
		}
		
		/* Add mod log */
		$this->_addModeratorLog( $this->lang->words['flag_spam_done'] . ': ' . $member['member_id'] . ' - ' . $member['email'] );
		
		/* Redirect */
		if( $this->topic['tid'] )
		{
			$this->registry->output->redirectScreen( $this->lang->words['flag_spam_done'], $this->settings['base_url'] . "showtopic=" . $this->topic['tid'] . "&amp;st=" . intval($this->request['st']), $this->topic['title_seo'] );
		}
		else
		{
			$this->registry->output->redirectScreen( $this->lang->words['flag_spam_done'], $this->settings['base_url'] . "showuser=" . $member['member_id'], $member['members_seo_name'] );
		}
		
	}
	
	/**
	 * Save the member updates
	 *
	 * @access	private
	 * @return	void		Outputs error screen
	 * @todo 	[Future] Determine what items should be editable and allow moderators to edit them
	 */
	private function _doEditMember()
	{
		$member = $this->_checkAndGetMember();

		/* Get the signature */
		$signature	= IPSText::getTextClass( 'editor' )->processRawPost( 'Post' );
		$aboutme	= IPSText::getTextClass( 'editor' )->processRawPost( 'aboutme' );
		
		/* Parse the signature */
		IPSText::getTextClass( 'bbcode' )->parse_smilies    		= 0;
		IPSText::getTextClass( 'bbcode' )->parse_html       		= intval( $this->settings['sig_allow_html'] );
		IPSText::getTextClass( 'bbcode' )->parse_bbcode     		= intval( $this->settings['sig_allow_ibc'] );
		IPSText::getTextClass( 'bbcode' )->parsing_section			= 'signatures';
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $member['member_group_id'];
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $member['mgroup_others'];

		$signature	= IPSText::getTextClass('bbcode')->preDbParse( $signature );
		
		/* Parse the about me */
		IPSText::getTextClass( 'bbcode' )->parse_smilies    		= $this->settings['aboutme_emoticons'];
		IPSText::getTextClass( 'bbcode' )->parse_html       		= intval( $this->settings['aboutme_html'] );
		IPSText::getTextClass( 'bbcode' )->parse_bbcode     		= intval( $this->settings['aboutme_bbcode'] );
		IPSText::getTextClass( 'bbcode' )->parsing_section			= 'aboutme';
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $member['member_group_id'];
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $member['mgroup_others'];

		$aboutme	= IPSText::getTextClass('bbcode')->preDbParse( $aboutme );	
		
		/* Add sig to the save array */
		$save['extendedProfile']	= array( 'signature' => $signature, 'pp_status' => trim($this->request['status']), 'pp_about_me' => $aboutme );
		$save['members']			= array( 'title' => $this->request['title'] );

		if ( $this->request['avatar'] == 1 )
		{
			$save['extendedProfile']['avatar_location']	= "";
			$save['extendedProfile']['avatar_size']		= "";

			try
			{
				IPSMember::getFunction()->removeAvatar( $member['member_id'] );
			}
			catch( Exception $e )
			{
				// Maybe should show an error or something
			}
		}
		
		if ( $this->request['photo'] == 1 )
		{
			IPSMember::getFunction()->removeUploadedPhotos( $member['member_id'] );

			$save['extendedProfile'] = array_merge( $save['extendedProfile'], array(
											'pp_main_photo'		=> '',
											'pp_main_width'		=> '',
											'pp_main_height'	=> '',
											'pp_thumb_photo'	=> '',
											'pp_thumb_width'	=> '',
											'pp_thumb_height'	=> ''
											)	);
		}
		
		//-----------------------------------------
		// Profile fields
		//-----------------------------------------

		require_once( IPS_ROOT_PATH . 'sources/classes/customfields/profileFields.php' );
		$fields = new customProfileFields();
		
		$fields->member_data = $member;
		$fields->initData( 'edit' );
		$fields->parseToSave( $_POST );
		
		if ( count( $fields->out_fields ) )
		{
			$save['customFields']	= $fields->out_fields;
		}

		//-----------------------------------------
		// Write it to the DB.
		//-----------------------------------------
		
		IPSMember::save( $member['member_id'], $save );
		
		//-----------------------------------------
		// Update signature content cache
		//-----------------------------------------
		
		IPSContentCache::update( $member['member_id'], 'sig', $save['extendedProfile']['signature'] );

		//-----------------------------------------
		// Add a mod log entry and redirect
		//-----------------------------------------
		
		$this->_addModeratorLog( $this->lang->words['acp_edited_profile'] . " {$member['members_display_name']}" );
		
		$this->registry->output->redirectScreen(  $this->lang->words['acp_edited_profile'] . " {$member['members_display_name']}", $this->settings['base_url'] . "app=forums&amp;module=moderate&amp;section=moderate&do=editmember&auth_key={$this->member->form_hash}&mid={$member['member_id']}" );
	}

	/**
	 * Form to edit a member
	 *
	 * @access	private
	 * @return	void		Outputs error screen
	 * @todo 	[Future] Determine what items should be editable and allow moderators to edit them
	 * @todo 	[Future] Show avatar and profile picture previews?
	 */
	private function _editMember()
	{
		$member = $this->_checkAndGetMember();

		if ( IPSText::getTextClass( 'editor' )->method == 'rte' )
		{
			$editable['signature']	= IPSText::getTextClass( 'bbcode' )->convertForRTE( $member['signature'] );
		}
		else
		{
			$editable['signature']	= IPSText::getTextClass('bbcode')->preEditParse( $member['signature'] );
		}
		
		if ( IPSText::getTextClass( 'editor' )->method == 'rte' )
		{
			$editable['aboutme']	= IPSText::getTextClass( 'bbcode' )->convertForRTE( $member['pp_about_me'] );
		}
		else
		{
			$editable['aboutme']	= IPSText::getTextClass('bbcode')->preEditParse( $member['pp_about_me'] );
		}
		
		$editable['member_id']		 		= $member['member_id'];
		$editable['members_display_name']	= $member['members_display_name'];
		$editable['title']					= $member['title'];
		$editable['pp_status']				= $member['pp_status'];

		//-----------------------------------------
		// Profile fields
		//-----------------------------------------

		require_once( IPS_ROOT_PATH . 'sources/classes/customfields/profileFields.php' );
		$fields = new customProfileFields();
		
		$fields->member_data = $member;
		$fields->initData( 'edit' );
		$fields->parseToEdit();
		
		$editable['signature']	= IPSText::getTextClass( 'editor' )->showEditor( $editable['signature'], 'Post' );
		$editable['aboutme']	= IPSText::getTextClass( 'editor' )->showEditor( $editable['aboutme'], 'aboutme' );

		//-----------------------------------------
		// Show?
		//-----------------------------------------
		
		$this->output .= $this->registry->getClass('output')->getTemplate('mod')->editUserForm( $editable, $fields );

		$this->registry->getClass('output')->setTitle( $this->lang->words['cp_em_title'] );
		$this->registry->getClass('output')->addNavigation( $this->lang->words['cp_vp_title'], "showuser={$member['member_id']}", $member['members_seo_name'], 'showuser' );
		$this->registry->getClass('output')->addNavigation( $this->lang->words['cp_em_title'], '' );
	}
	
	/**
	 * Edit member: check permissions and return member
	 *
	 * @access	private
	 * @return	array		Member information
	 */
	private function _checkAndGetMember()
	{
		$mid = intval($this->request['mid']) ? intval($this->request['mid']) : intval($this->request['member']);
		
		//-----------------------------------------
		// Got anyfink?
		//-----------------------------------------
		 
		if ( ! $mid )
		{
			$this->_showError( 'mod_no_mid', 10369 );
		}

		//-----------------------------------------
		// Check Permissions
		//-----------------------------------------
		
		if ( ! $this->memberData['g_is_supmod'] )
		{
			$this->_showError( 'mod_only_supermods', 10370 );
		}
		
		//-----------------------------------------
		// Load and config the post parser
		//-----------------------------------------

		IPSText::getTextClass('bbcode')->parse_html					= $this->settings['sig_allow_html'];
		IPSText::getTextClass('bbcode')->parse_nl2br				= 1;
		IPSText::getTextClass('bbcode')->parse_smilies				= 0;
		IPSText::getTextClass('bbcode')->parse_bbcode				= $this->settings['sig_allow_ibc'];
		IPSText::getTextClass( 'bbcode' )->parsing_section			= 'signatures';

		
		ipsRegistry::getClass( 'class_localization')->loadLanguageFile( array( 'public_post' ) );

		//-----------------------------------------
		// Get member
		//-----------------------------------------
		
		$member = IPSMember::load( $mid );
		
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $member['member_group_id'];
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $member['mgroup_others'];
		
		//-----------------------------------------
		// No editing of admins!
		//-----------------------------------------
		
		if ( ! $this->memberData['g_access_cp'] AND $member['g_access_cp'] )
		{
			$this->_showError( 'mod_admin_edit', 3032 );
		}
		
		return $member;
	}
	
	/**
	 * Alter approve/unapprove state of topic
	 *
	 * @access	private
	 * @param	string		[approve|unapprove]
	 * @return	void		[Outputs to screen]
	 */
	private function _topicApproveAlter( $type='approve' )
	{
		$this->_resetModerator( $this->topic['forum_id'] );
		
		$this->_genericPermissionCheck( 'post_q' );
		
		$approve_int = $type == 'approve' ? 1 : 0;
		
		$this->DB->update( 'topics', array( 'approved' => $approve_int ), 'tid=' . $this->topic['tid'] );
		
		if( $approve_int )
		{
			$this->DB->build( array( 
									'select'   => 'p.*', 
									'from'     => array( 'posts' => 'p' ),
									'order'    => 'pid ASC',
									'where'    => 'p.queued=0 AND p.topic_id=' . $this->topic['tid'],
									'add_join' => array( 
														array(
																'select' => 't.title, t.forum_id, t.topic_firstpost',
																'from'   => array( 'topics' => 't' ),
																'where'  => 'p.topic_id=t.tid',
																'type'   => 'left'											
															)
													 )
							)		);
			$q = $this->DB->execute();

			while( $r = $this->DB->fetch( $q ) )
			{
				/* The first post needs some different data */
				if( $r['topic_firstpost'] == $r['pid'] )
				{
					$pid   = 0;
					$title = $r['title'];
				}
				else
				{
					$pid   = serialize( array( 'pid' => $r['pid'], 'title' => $r['title'] ) );
					$title = '';
				}
			}
			
			$this->modLibrary->clearModQueueTable( 'topic', $this->topic['tid'], true );
		}
		else
		{
			$this->registry->class_forums->removePostFromSearchIndex( $this->topic['tid'], 0, 1 );
		}

		$this->modLibrary->forumRecount( $this->forum['id'] );
		$this->modLibrary->statsRecount();
		
		$this->_addModeratorLog( sprintf( $type == 'approve' ? $this->lang->words['acp_approve_topic'] : $this->lang->words['acp_unapprove_topic'], $this->topic['tid'] ) );
		
		$this->registry->output->redirectScreen( $this->lang->words['redirect_modified'], $this->settings['base_url'] . "showtopic=" . $this->topic['tid'] . "&amp;st=" . intval($this->request['st']) );
	}
	
	/**
	 * Alter pin/unpinned state of topic
	 *
	 * @access	private
	 * @param	string		[pin|unpin]
	 * @return	void		[Outputs to screen]
	 */
	private function _topicPinAlter( $type='pin' )
	{
		$this->_resetModerator( $this->topic['forum_id'] );
		
		if( $type == 'pin' )
		{
			if ( $this->topic['pinned'] )
			{
				$this->_showError( 'mod_topic_pinned', 10371 );
			}
			
			$this->_genericPermissionCheck( 'pin_topic' );
			
			$this->modLibrary->topicPin($this->topic['tid']);
			
			$this->_addModeratorLog( $this->lang->words['acp_pinned_topic'] );
			
			$words = $this->lang->words['p_pinned'];
		}
		else
		{
			if ( !$this->topic['pinned'] )
			{
				$this->_showError( 'mod_topic_unpinned', 10372 );
			}
			
			$this->_genericPermissionCheck( 'unpin_topic' );
			
			$this->modLibrary->topicUnpin($this->topic['tid']);
			
			$this->_addModeratorLog( $this->lang->words['acp_unpinned_topic'] );
			
			$words = $this->lang->words['p_unpinned'];
		}

		$url	= "showtopic=".$this->topic['tid']."&amp;st=".intval($this->request['st']);
		
		if( $this->request['from'] == 'forum' )
		{
			$url	= "showforum=".$this->topic['forum_id']."&amp;st=".intval($this->request['st']);
		}

		$this->registry->output->redirectScreen( $words, $this->settings['base_url'] . $url );
	}

	/**
	 * Alter pin/unpinned state of topic
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _resyncForum()
	{
		$this->registry->class_forums->allForums[ $this->forum['id'] ]['_update_deletion'] = 1;
		$this->modLibrary->forumRecount( $this->forum['id'] );
		$this->modLibrary->statsRecount();
		
		$this->registry->output->redirectScreen( $this->lang->words['cp_resync'], $this->settings['base_url'] . "showforum=".$this->forum['id'] );
	}
	
	/**
	 * Process post multi-moderation
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _multiPostModify()
	{
		$this->pids  = $this->_getIds( 'selectedpids' );

		if ( count( $this->pids ) )
		{
			switch ( $this->request['tact'] )
			{
				case 'approve':
					$this->_multiApprovePost(1);
				break;
				case 'unapprove':
					$this->_multiApprovePost(0);
				break;
				case 'delete':
					$this->_multiDeletePost();
				break;
				case 'merge':
					$this->_multiMergePost();
				break;
				case 'split':
					$this->_multiSplitTopic();
				break;
				case 'move':
					$this->_multiMovePost();
				break;
			}
		}
		
		IPSCookie::set( 'modpids', '', 0 );
		
		if ( $this->topic['tid'] )
		{
			$this->registry->output->redirectScreen( $this->lang->words['cp_redirect_posts'], $this->settings['base_url'] . "showtopic=" . $this->topic['tid'] . '&amp;st=' . intval($this->request['st']) );
		}
	}
	
	/**
	 * Post multi-mod: Move posts
	 *
	 * @access	private
	 * @param 	string		[Optional] error message
	 * @return	void		[Outputs to screen]
	 */
	private function _multiMovePost( $error='' )
	{
		$this->_resetModerator( $this->topic['forum_id'] );
		
		$this->_genericPermissionCheck( 'split_merge' );

		if( ! $this->topic['tid'] )
		{
			$this->_showError( 'mod_no_tid', 10373 );
		}

		if( $this->request['checked'] != 1 )
		{
			$posts		= array();

			//-----------------------------------------
			// Display the posty wosty's
			//-----------------------------------------
			
			$this->DB->build( array(
									  'select' => 'p.post, p.pid, p.post_date, p.author_id, p.author_name, p.use_emo, p.post_htmlstate',
									  'from'   => array( 'posts' => 'p' ),
									  'where'  => 'p.topic_id=' . $this->topic['tid'] . ' AND p.pid IN (' . implode( ',', $this->pids ) . ')',
									  'order'  => 'p.post_date',
									  'add_join'	=> array(
									  						array( 'select'	=> 'm.member_group_id, m.mgroup_others',
									  								'from'	=> array( 'members' => 'm' ),
									  								'where'	=> 'm.member_id=p.author_id',
									  							)
									  						)
							)  );
								 
			$post_query = $this->DB->execute();

			while( $row = $this->DB->fetch( $post_query ) )
			{
				$row['post']	= IPSText::truncate( $row['post'], 800 );
				$row['date']	= ipsRegistry::getClass( 'class_localization')->getDate( $row['post_date'], 'LONG' );
				
				/* Parse the post */
				IPSText::getTextClass( 'bbcode' )->parse_smilies			= $row['use_emo'];
				IPSText::getTextClass( 'bbcode' )->parse_html				= 0;
				IPSText::getTextClass( 'bbcode' )->parse_nl2br				= $row['post_htmlstate'] == 2 ? 1 : 0;
				IPSText::getTextClass( 'bbcode' )->parse_bbcode				= 1;
				IPSText::getTextClass( 'bbcode' )->parsing_section			= 'topics';
				IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $row['member_group_id'];
				IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $row['mgroup_others'];

				$row['post']	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $row['post'] );				
				
				/* Add to output array */				
				$posts[]		= $row;
			}

			//-----------------------------------------
			// print my bottom, er, the bottom
			//-----------------------------------------

			$this->output .= $this->registry->getClass('output')->getTemplate('mod')->movePostForm( $this->forum, $this->topic, $posts, $error );

			$this->registry->getClass('output')->addNavigation( $this->forum['name'], "{$this->settings['_base_url']}showforum={$this->forum['id']}" );
			$this->registry->getClass('output')->addNavigation( $this->topic['title'], "{$this->settings['_base_url']}showtopic={$this->topic['tid']}" );
			$this->registry->getClass('output')->setTitle( $this->lang->words['cmp_title'].": ".$this->topic['title'] );
			$this->registry->output->addContent( $this->output );
			$this->registry->getClass('output')->sendOutput();
		}
		else
		{
			//-----------------------------------------
			// PROCESS Check the input
			//-----------------------------------------
			
			$old_id = $this->_getTidFromUrl();

			if ( !$old_id )
			{
				$this->request[ 'checked'] =  0 ;
				$this->_multiMovePost( $this->lang->words['cmp_notopic'] );
			}
			
			//-----------------------------------------
			// Grab topic
			//-----------------------------------------
			
			$move_to_topic = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'topics', 'where' => 'tid=' . $old_id ) );
			
			if ( ! $move_to_topic['tid'] or !$this->registry->class_forums->allForums[ $move_to_topic['forum_id'] ]['id'] )
			{
				$this->request[ 'checked'] =  0 ;
				$this->_multiMovePost( $this->lang->words['cmp_notopic'] );
			}
			
			$affected_ids	= count( $this->pids );
			
			//-----------------------------------------
			// Do we have enough?
			//-----------------------------------------
			
			if ( $affected_ids < 1 )
			{
				$this->_showError( 'mod_not_enough_split', 10374 );
			}
			
			//-----------------------------------------
			// Do we choose too many?
			//-----------------------------------------
			
			$count = $this->DB->buildAndFetch( array( 'select' => 'count(pid) as cnt', 'from' => 'posts', 'where' => "topic_id={$this->topic['tid']}" ) );
			
			if ( $affected_ids >= $count['cnt'] )
			{
				$this->_showError( 'mod_too_much_split', 10375 );
			}
			
			//-----------------------------------------
			// Move the posts
			//-----------------------------------------
			
			$this->DB->update( 'posts', array( 'topic_id' => $move_to_topic['tid'], 'new_topic' => 0 ), "pid IN(" . implode( ",", $this->pids ) . ")" ); 
			$this->DB->update( 'posts', array( 'new_topic' => 0 ), "topic_id={$this->topic['tid']}" ); 
		
			//-----------------------------------------
			// Is first post queued for new topic?
			//-----------------------------------------

			$first_post = $this->DB->buildAndFetch( array( 'select'	=> 'pid, queued',
																	'from'	=> 'posts',
																	'where'	=> "topic_id={$move_to_topic['tid']}",
																	'order'	=> $this->settings['post_order_column'] . ' ASC',
																	'limit'	=> array( 1 ),
														)		);

			if( $first_post['queued'] )
			{
				$this->DB->update( 'topics', array( 'approved' => 0 ), "tid={$move_to_topic['tid']}" );
				$this->DB->update( 'posts', array( 'queued' => 0 ), 'pid=' . $first_post['pid'] );
			}
			
			//-----------------------------------------
			// Is first post queued for old topic?
			//-----------------------------------------

			$other_first_post = $this->DB->buildAndFetch( array( 'select'	=> 'pid, queued',
																		'from'		=> 'posts',
																		'where'		=> "topic_id={$this->topic['tid']}",
																		'order'		=> $this->settings['post_order_column'] . ' ASC',
																		'limit'		=> array( 1 ),
																)		);

			if( $other_first_post['queued'] )
			{
				$this->DB->update( 'topics', array( 'approved' => 0 ), "tid={$this->topic['tid']}" );
				$this->DB->update( 'posts', array( 'queued' => 0 ), 'pid=' . $other_first_post['pid'] );
			}	
			
			//-----------------------------------------
			// Rebuild the topics
			//-----------------------------------------
			
			$this->modLibrary->rebuildTopic($move_to_topic['tid']);
			$this->modLibrary->rebuildTopic($this->topic['tid']);
			
			//-----------------------------------------
			// Update the forum(s)
			//-----------------------------------------
			
			$this->modLibrary->forumRecount( $this->topic['forum_id'] );
			
			if ( $this->topic['forum_id'] != $move_to_topic['forum_id'] )
			{
				$this->modLibrary->forumRecount( $move_to_topic['forum_id'] );
			}
			
			if( $move_to_topic['forum_id'] == $this->settings['forum_trash_can_id'] )
			{
				$this->modLibrary->clearModQueueTable( 'post', $this->pids );
			}
			
			$this->_addModeratorLog( sprintf( $this->lang->words['acp_moved_posts'], $this->topic['title'], $move_to_topic['title'] ) );
		}
	}
	
	/**
	 * Post multi-mod: Approve posts
	 *
	 * @access	private
	 * @param 	integer		1=approve, 0=unapprove
	 * @return	void		[Outputs to screen]
	 */
	private function _multiApprovePost( $approve=1 )
	{
		$_approve = ( $approve ) ? TRUE : FALSE;
		
		$this->_resetModerator( $this->topic['forum_id'] );
		
		$this->_genericPermissionCheck( 'post_q' );
		
		$this->modLibrary->postToggleApprove( $this->pids, $_approve, $this->topic['tid'] );
	}
	
	/**
	 * Post multi-mod: Delete posts
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _multiDeletePost()
	{
		$this->_resetModerator( $this->topic['forum_id'] );
		
		$this->_genericPermissionCheck( 'delete_post' );
		
		//-----------------------------------------
		// Check to make sure that this isn't the first post in the topic..
		//-----------------------------------------
		
		foreach( $this->pids as $p )
		{
			if ( $this->topic['topic_firstpost'] == $p )
			{ 
				$this->_showError( 'mod_delete_first_post', 10376 );
			}
		}
		
		$this->_addModeratorLog( sprintf( $this->lang->words['multi_post_delete_mod_log'], count( $this->pids ), $this->topic['title'] ) );
		
		if ( $this->trash_forum and $this->trash_forum != $this->forum['id'] )
		{
			//-----------------------------------------
			// Set up and pass to split topic handler
			//-----------------------------------------
			
			$this->request['checked']	= 1;
			$this->request['fid']		= $this->trash_forum;
			$this->request['title']		= $this->lang->words['mod_from'] . " " . $this->topic['title'];
			$this->request['desc']		= $this->lang->words['mod_from_id']." ".$this->topic['tid'];
			
			foreach( $this->pids as $p )
			{
				$this->request['selectedpids_' . $p ] = 1;
			}
			
			$this->trash_inuse = 1;
			
			$this->_multiSplitTopic();
			
			$this->trash_inuse = 0;
		}
		else
		{
			$this->modLibrary->postDelete( $this->pids );
			$this->modLibrary->forumRecount( $this->topic['forum_id'] );
		}
		
		$this->modLibrary->clearModQueueTable( 'post', $this->pids );
	}
	
	/**
	 * Post multi-mod: Split topic
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _multiSplitTopic()
	{
		$this->_resetModerator( $this->topic['forum_id'] );
		
		$this->_genericPermissionCheck( 'split_merge', '', 1 );
		 
		if ( ! $this->topic['tid'] )
		{
			$this->_showError( 'mod_no_tid', 10377 );
		}

		//-----------------------------------------
		// Show the form
		//-----------------------------------------
		
		if ( $this->request['checked'] != 1 )
		{			
			$jump_html	= $this->registry->getClass('class_forums')->buildForumJump(0,1,1);
			$posts		= array();

			//-----------------------------------------
			// Display the posty wosty's
			//-----------------------------------------
			
			$this->DB->build( array(
									  'select' => 'p.post, p.pid, p.post_date, p.author_id, p.author_name, p.use_emo, p.post_htmlstate',
									  'from'   => array( 'posts' => 'p' ),
									  'where'  => 'p.topic_id=' . $this->topic['tid'] . ' AND p.pid IN (' . implode( ',', $this->pids ) . ')',
									  'order'  => 'p.post_date',
									  'add_join'	=> array(
									  						array( 'select'	=> 'm.member_group_id, m.mgroup_others',
									  								'from'	=> array( 'members' => 'm' ),
									  								'where'	=> 'm.member_id=p.author_id',
									  							)
									  						)
							)  );
								 
			$post_query = $this->DB->execute();

			while ( $row = $this->DB->fetch($post_query) )
			{
				// This causes HTML to get cut off sometimes
				//$row['post']	= IPSText::truncate( $row['post'], 800 );
				$row['date']	= ipsRegistry::getClass( 'class_localization')->getDate( $row['post_date'], 'LONG' );
				
				IPSText::getTextClass( 'bbcode' )->parse_smilies			= $row['use_emo'];
				IPSText::getTextClass( 'bbcode' )->parse_html				= ( $this->forum['use_html'] and $row['post_htmlstate'] ) ? 1 : 0;
				IPSText::getTextClass( 'bbcode' )->parse_nl2br				= $row['post_htmlstate'] == 2 ? 1 : 0;
				IPSText::getTextClass( 'bbcode' )->parse_bbcode				= $this->forum['use_ibc'];
				IPSText::getTextClass( 'bbcode' )->parsing_section			= 'topics';
				IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $row['member_group_id'];
				IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $row['mgroup_others'];
				
				$row['post']	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $row['post'] );
				
				$posts[]		= $row;
			}
			
			//-----------------------------------------
			// print my bottom, er, the bottom
			//-----------------------------------------

			$this->output .= $this->registry->getClass('output')->getTemplate('mod')->splitPostForm( $this->forum, $this->topic, $posts, $jump_html );

			$this->registry->getClass('output')->addNavigation( $this->forum['name'], "{$this->settings['_base_url']}showforum={$this->forum['id']}" );
			$this->registry->getClass('output')->addNavigation( $this->topic['title'], "{$this->settings['_base_url']}showtopic={$this->topic['tid']}" );
			$this->registry->getClass('output')->setTitle( $this->lang->words['st_top'].": ".$this->topic['title'] );
			$this->registry->output->addContent( $this->output );
			$this->registry->getClass('output')->sendOutput();
		}
		else
		{
			//-----------------------------------------
			// PROCESS Check the input
			//-----------------------------------------
			
			if ( $this->request['title'] == "" )
			{
				$this->_showError( 'mod_need_title', 10378 );
			}

			$affected_ids = count( $this->pids );
			
			//-----------------------------------------
			// Do we have enough?
			//-----------------------------------------
			
			if ( $affected_ids < 1 )
			{
				$this->_showError( 'mod_not_enough_split', 10379 );
			}
			
			//-----------------------------------------
			// Do we choose too many?
			//-----------------------------------------
			
			$count = $this->DB->buildAndFetch( array( 'select' => 'count(pid) as cnt', 'from' => 'posts', 'where' => "topic_id={$this->topic['tid']}" ) );
			
			if ( $affected_ids >= $count['cnt'] )
			{
				$this->_showError( 'mod_too_much_split', 10380 );
			}

			//-----------------------------------------
			// Check the forum we're moving this too
			//-----------------------------------------
			
			$this->request[ 'fid'] =  intval($this->request['fid'] );
			
			if ( $this->request['fid'] != $this->forum['id'] )
			{
				$f = $this->registry->class_forums->allForums[ $this->request['fid'] ];
				
				if ( ! $f['id'] )
				{
					$this->_showError( 'mod_no_forum_move', 10381 );
				}
			
				if ( !$f['sub_can_post'] )
				{
					$this->_showError( 'mod_forum_no_posts', 10382 );
				}
			}
			
			//-----------------------------------------
			// Is first post queued?
			//-----------------------------------------
			
			$topic_approved	= 1;
			
			$first_post = $this->DB->buildAndFetch( array( 'select'	=> 'pid, queued',
																	'from'	=> 'posts',
																	'where'	=> 'topic_id=' . $this->topic['tid'] . " AND pid IN(" . implode( ",", $this->pids ). ")",
																	'order'	=> $this->settings['post_order_column'] . ' ASC',
																	'limit'	=> array(0,1),
															)		);

			if( $first_post['queued'] )
			{
				$topic_approved	= 0;

				$this->DB->update( 'posts', array( 'queued' => 0 ), 'pid=' . $first_post['pid'] );
			}
			
			//-----------------------------------------
			// Complete a new dummy topic
			//-----------------------------------------
			
			$this->DB->insert( 'topics', array(
												'title'				=> $this->request['title'],
												'description'		=> $this->request['desc'] ,
												'state'				=> 'open',
												'posts'				=> 0,
												'starter_id'		=> 0,
												'starter_name'		=> 0,
												'start_date'		=> time(),
												'last_poster_id'	=> 0,
												'last_poster_name'	=> 0,
												'last_post'			=> time(),
												'icon_id'			=> 0,
												'author_mode'		=> 1,
												'poll_state'		=> 0,
												'last_vote'			=> 0,
												'views'				=> 0,
												'forum_id'			=> $this->request['fid'],
												'approved'			=> $topic_approved,
												'pinned'			=> 0,
							)				);
								
			$new_topic_id = $this->DB->getInsertId();
	
			//-----------------------------------------
			// Move the posts
			//-----------------------------------------
			
			$this->DB->update( 'posts', array( 'topic_id' => $new_topic_id, 'new_topic' => 0 ), 'topic_id=' . $this->topic['tid'] . " AND pid IN(" . implode( ",", $this->pids ). ")" ); 
			
			//-----------------------------------------
			// Move the posts
			//-----------------------------------------
			
			if ( $this->trash_inuse )
			{
				$this->DB->update( 'posts', array( 'queued' => 0 ), "topic_id={$new_topic_id}" );
			}

			$this->DB->update( 'posts', array( 'new_topic' => 0 ), "topic_id={$this->topic['tid']}" );
			
			//-----------------------------------------
			// Rebuild the topics
			//-----------------------------------------
			
			$this->modLibrary->rebuildTopic( $new_topic_id );
			$this->modLibrary->rebuildTopic( $this->topic['tid'] );

			//-----------------------------------------
			// Update the forum(s)
			//-----------------------------------------
			
			$this->modLibrary->forumRecount($this->topic['forum_id']);
			
			if ( $this->topic['forum_id'] != $this->request['fid'] )
			{
				$this->modLibrary->forumRecount( $this->request['fid'] );
			}
			
			if ( $this->trash_inuse )
			{
				$this->_addModeratorLog( $this->lang->words['acp_trashcan_post'] . " '{$this->topic['title']}'" );
			}
			else
			{
				$this->_addModeratorLog( $this->lang->words['acp_split_topic'] . " '{$this->topic['title']}'" );
			}
		}
	}
	
	/**
	 * Post multi-mod: Merge posts
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _multiMergePost()
	{
		$this->_resetModerator( $this->topic['forum_id'] );
		
		$this->_genericPermissionCheck( 'delete_post' );

		if ( count( $this->pids ) < 2 )
		{
			$this->_showError( 'mod_only_one_pid', 10383 );
		}
		
		//-----------------------------------------
		// Form or print?
		//-----------------------------------------
		
		if ( !$this->request['checked'] )
		{
			//-----------------------------------------
			// Get post data
			//-----------------------------------------
			
			$master_post	= "";
			$dropdown		= array();
			$authors		= array();
			$seen_author	= array();
			$upload_html	= "";
			$seoTitle		= '';
			
			//-----------------------------------------
			// Grab teh posts
			//-----------------------------------------
			
			$this->DB->build( array(
									'select'	=> 'p.*',
									'from'		=> array( 'posts' => 'p' ),
									'where'		=> "p.pid IN (" . implode( ",", $this->pids ) . ")",
									'add_join'	=> array(
														array(
																'select'	=> 't.forum_id, t.title_seo',
																'from'		=> array( 'topics' => 't' ),
																'where'		=> 't.tid=p.topic_id',
																'type'		=> 'left',
															)
														)
								)		);
			$outer = $this->DB->execute();
		
			while ( $p = $this->DB->fetch( $outer ) )
			{
				if ( IPSMember::checkPermissions('read', $p['forum_id'] ) == TRUE )
				{
					$master_post .= "<br /><br />" . $p['post'];
					
					$dropdown[]			= array( $p['pid'], ipsRegistry::getClass( 'class_localization')->getDate( $p['post_date'], 'LONG') ." (#{$p['pid']})" );
					
					if ( !in_array( $p['author_id'], $seen_author ) )
					{
						$authors[]		= array( $p['author_id'], "{$p['author_name']} (#{$p['pid']})" );
						$seen_author[]	= $p['author_id'];
					}
					
					$seoTitle	= $p['title_seo'];
				}
			}
			
			//-----------------------------------------
			// Get Attachment Data
			//-----------------------------------------
			
			$this->DB->build( array( 'select'	=> '*',
									 'from'		=> 'attachments',
									 'where'	=> "attach_rel_module='post' AND attach_rel_id IN (" . implode( ",", $this->pids ) . ")" ) );
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$row['image']		= $this->caches['attachtypes'][ $row['attach_ext'] ]['atype_img'];
				$row['size']		= IPSLib::sizeFormat( $row['attach_filesize'] );
				$row['attach_file']	= IPSText::truncate( $row['attach_file'], 50 );
				$attachments[]		= $row;
			}
			
			//-----------------------------------------
			// Print form
			//-----------------------------------------
			
			if ( IPSText::getTextClass('editor')->method == 'rte' )
			{
				IPSText::getTextClass('bbcode')->parse_wordwrap	= 0;
				IPSText::getTextClass('bbcode')->parse_html		= 0;

				$master_post = IPSText::getTextClass('bbcode')->convertForRTE( trim($master_post) );
			}
			else
			{
				IPSText::getTextClass('bbcode')->parse_html    			= 0;
				IPSText::getTextClass('bbcode')->parse_nl2br   			= 0;
				IPSText::getTextClass('bbcode')->parse_smilies 			= 1;
				IPSText::getTextClass('bbcode')->parse_bbcode  			= 1;
				IPSText::getTextClass('bbcode')->parsing_section		= 'topics';

				if( IPSText::getTextClass('bbcode')->parse_html )
				{
					if( !IPSText::getTextClass('bbcode')->parse_nl2br )
					{
						$master_post = str_replace( array( '<br />', '<br>' ), "", trim($master_post) );
					}
				}

				$master_post = IPSText::getTextClass('bbcode')->preEditParse( $master_post );
			}

			$editor = IPSText::getTextClass('editor')->showEditor( $master_post, 'Post' );
			
			$this->output .= $this->registry->getClass('output')->getTemplate('mod')->mergePostForm( $editor, $dropdown, $authors, $attachments, $seoTitle );

			if ( $this->topic['tid'] )
			{
				$this->registry->getClass('output')->addNavigation( $this->topic['title'], "{$this->settings['_base_url']}showtopic={$this->topic['tid']}" );
			}
			
			$this->registry->getClass('output')->addNavigation( $this->lang->words['cm_title'], '' );
			$this->registry->getClass('output')->setTitle( $this->lang->words['cm_title'] );
			$this->registry->output->addContent( $this->output );
			$this->registry->getClass('output')->sendOutput();
		}
		else
		{
			//-----------------------------------------
			// DO THE THING, WITH THE THING!!
			//-----------------------------------------
			
			$this->request['postdate'] =  intval($this->request['postdate']);
			
			if ( !$this->request['selectedpids'] or !$this->request['postdate'] or !$this->request['postauthor'] or !$this->request['Post'] )
			{
				$this->_showError( 'mod_merge_posts', 10384 );
			}
			
			IPSText::getTextClass('bbcode')->parse_smilies		= 1;
			IPSText::getTextClass('bbcode')->parse_html			= 0;
			IPSText::getTextClass('bbcode')->parse_bbcode		= 1;
			IPSText::getTextClass('bbcode')->parsing_section	= 'topics';

			$post = IPSText::getTextClass('editor')->processRawPost( 'Post' );
			$post = IPSText::getTextClass('bbcode')->preDbParse( $post );

			//-----------------------------------------
			// Post to keep...
			//-----------------------------------------
			
			$posts			= array();
			$author			= array();
			$post_to_delete	= array();
			$new_post_key	= md5(time());
			$topics			= array();
			$forums			= array();
			$append_edit	= 0;
			
			//-----------------------------------------
			// Grab teh posts
			//-----------------------------------------
			
			$this->DB->build( array(
									'select'	=> 'p.*',
									'from'		=> array( 'posts' => 'p' ),
									'where'		=> "p.pid IN (" . implode( ",", $this->pids ) . ")",
									'add_join'	=> array(
														array(
																'select'	=> 't.forum_id',
																'from'		=> array( 'topics' => 't' ),
																'where'		=> 't.tid=p.topic_id',
																'type'		=> 'left',
															)
														)
							)		);
			$outer = $this->DB->execute();
			
			while ( $p = $this->DB->fetch($outer) )
			{
				$posts[ $p['pid'] ]			= $p;
				$topics[ $p['topic_id'] ]	= $p['topic_id'];
				$forums[ $p['forum_id'] ]	= $p['forum_id'];
				
				if ( $p['author_id'] == $this->request['postauthor'] )
				{
					$author = array( 'id' => $p['author_id'], 'name' => $p['author_name'] );
				}
				
				if ( $p['pid'] != $this->request['postdate'] )
				{
					$post_to_delete[] = $p['pid'];
				}
				
				if( $p['append_edit'] )
				{
					$append_edit = 1;
				}
			}
			
			//-----------------------------------------
			// Update main post...
			//-----------------------------------------
			
			$this->DB->update( 'posts', array(	'author_id'		=> $author['id'],
												'author_name'	=> $author['name'],
												'post'			=> $post,
												'post_key'		=> $new_post_key,
												'post_parent'	=> 0, 
												'edit_time'		=> time(),
												'edit_name'		=> $this->memberData['members_display_name'],
												'append_edit'	=> ( $append_edit OR !$this->memberData['g_append_edit'] ) ? 1 : 0,
										  ), 'pid=' . $this->request['postdate']
						 );

			//-----------------------------------------
			// Fix attachments
			//-----------------------------------------
			
			$attach_keep	= array();
			$attach_kill	= array();
			
			foreach ( $_POST as $key => $value )
			{
				if ( preg_match( "/^attach_(\d+)$/", $key, $match ) )
				{
					if ( $this->request[ $match[0] ] == 'keep' )
					{
						$attach_keep[] = $match[1];
					}
					else
					{
						$attach_kill[] = $match[1];
					}
				}
			}
			
			$attach_keep	= IPSLib::cleanIntArray( $attach_keep );
			$attach_kill	= IPSLib::cleanIntArray( $attach_kill );
			
			//-----------------------------------------
			// Keep
			//-----------------------------------------
			
			if ( count( $attach_keep ) )
			{
				$this->DB->update( 'attachments', array( 'attach_rel_id'		=> $this->request['postdate'],
															'attach_post_key'	=> $new_post_key,
															'attach_member_id'	=> $author['id'] ), 'attach_id IN(' . implode( ",", $attach_keep ) . ')' );
			}
			
			//-----------------------------------------
			// Kill Attachments
			//-----------------------------------------
			
			if( count( $attach_kill ) )
			{
				require_once( IPSLib::getAppDir( 'core' ) . '/sources/classes/attach/class_attach.php' );
				$class_attach                  =  new class_attach( $this->registry );
				
				$class_attach->type            =  $rel_module;
				$class_attach->attach_post_key =  $post_key;
				$class_attach->attach_rel_id   =  $rel_id;
				$class_attach->init();
				
				$class_attach->bulkRemoveAttachment( $attach_kill, 'attach_id' );
			}
			
			//-----------------------------------------
			// Kill old posts
			//-----------------------------------------
			
			if ( count($post_to_delete) )
			{
				$this->DB->delete( 'posts', 'pid IN(' . implode( ",", $post_to_delete ) . ')' );
			}
			
			foreach( $topics as $t )
			{
				$this->modLibrary->rebuildTopic( $t, 0 );
			}
			
			foreach( $forums as $f )
			{
				$this->modLibrary->forumRecount( $f );
			}
			
			$this->modLibrary->statsRecount();
			
			/* Clear the content cache */
			IPSContentCache::drop( 'post', $this->pids );
			
			$this->_addModeratorLog( sprintf( $this->lang->words['acp_merged_posts'], implode( ", ", $this->pids ) ) );
		}
	}
	
	/**
	 * Close a topic
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _closeTopic()
	{
		$this->_resetModerator( $this->topic['forum_id'] );
		
		if( !$this->_genericPermissionCheck( 'close_topic', '', 0, true ) )
		{
			if ( $this->topic['starter_id'] != $this->memberData['member_id'] OR !$this->memberData['g_open_close_posts'] )
			{
				$this->_showError( 'mod_no_close_topic', 10385 );
			}
		}

		$this->modLibrary->topicClose($this->topic['tid']);
		
		$this->_addModeratorLog( $this->lang->words['acp_locked_topic'] );
	
		$this->registry->output->redirectScreen( $this->lang->words['p_closed'], $this->settings['base_url'] . "showforum=".$this->forum['id'] . '&st=' . intval( $this->request['st'] ) );
	}
	
	/**
	 * Delete a topic
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _deleteTopic()
	{
		$this->_resetModerator( $this->topic['forum_id'] );
		
		if( !$this->_genericPermissionCheck( 'delete_topic', '', 0, true ) )
		{
			if ( $this->topic['starter_id'] != $this->memberData['member_id'] OR !$this->memberData['g_delete_own_topics'] )
			{
				$this->_showError( 'mod_no_delete_topic', 10386 );
			}
		}

		// Do we have a linked topic to remove?
		$this->DB->build( array( 'select' => 'tid, forum_id', 'from' => 'topics', 'where' => "state='link' AND moved_to='" . $this->topic['tid'] . '&' . $this->forum['id'] . "'" ) );
		$this->DB->execute();
		
		if ( $linked_topic = $this->DB->fetch() )
		{
			$this->DB->delete( 'topics', "tid=" . $linked_topic['tid'] );
			
			$this->modLibrary->forumRecount( $linked_topic['forum_id'] );
		}
		
		if ( $this->trash_forum and $this->trash_forum != $this->forum['id'] )
		{
			//-----------------------------------------
			// Move, don't delete
			//-----------------------------------------
			
			$this->modLibrary->topicMove($this->topic['tid'], $this->forum['id'], $this->trash_forum);
			
			$this->registry->class_forums->allForums[ $this->forum['id'] ]['_update_deletion'] = 1;
			$this->registry->class_forums->allForums[ $this->trash_forum ]['_update_deletion'] = 1;
			
			$this->modLibrary->forumRecount( $this->forum['id'] );
			$this->modLibrary->forumRecount( $this->trash_forum );
			
			$this->_addModeratorLog( $this->lang->words['acp_trashcan_a_topic'] . " " . $this->topic['tid'] );
		}
		else
		{
			$this->modLibrary->topicDelete($this->topic['tid']);
			$this->_addModeratorLog( $this->lang->words['acp_deleted_a_topic'] );
		}
		
		$this->modLibrary->clearModQueueTable( 'topic', $this->topic['tid'] );

		$this->registry->output->redirectScreen( $this->lang->words['p_deleted'], $this->settings['base_url'] . "showforum=" . $this->forum['id'] . '&st=' . intval( $this->request['st'] ), $this->topic['title_seo'] );
	}
	
	/**
	 * Delete a topic (confirmation screen)
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _deleteForm()
	{
		$this->_resetModerator( $this->topic['forum_id'] );
		
		if( !$this->_genericPermissionCheck( 'delete_topic', '', 0, true ) )
		{
			if ( $this->topic['starter_id'] != $this->memberData['member_id'] OR !$this->memberData['g_delete_own_topics'] )
			{
				$this->_showError( 'mod_no_delete_topic', 10387 );
			}
		}

		$this->output = $this->registry->getClass('output')->getTemplate('mod')->deleteTopicForm( $this->forum, $this->topic );

		$this->registry->getClass('output')->addNavigation( $this->forum['name'], "{$this->settings['_base_url']}showforum={$this->forum['id']}" );
		$this->registry->getClass('output')->addNavigation( $this->topic['title'], "{$this->settings['_base_url']}showtopic={$this->topic['tid']}" );
		$this->registry->getClass('output')->setTitle( $this->lang->words['t_delete'].": ".$this->topic['title'] );
	}
	
	/**
	 * Display the topic history
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _topicHistory()
	{
		$this->_genericPermissionCheck();
		
		//-----------------------------------------
		// Admin only
		//-----------------------------------------
		
		if( !$this->memberData['g_access_cp'] )
		{
			$this->_showError( 'moderate_no_permission', 103119 );
		}

		if ($this->topic['last_post'] == $this->topic['start_date'])
		{
			$avg_posts = 1;
		}
		else
		{
			$avg_posts = round( ($this->topic['posts'] + 1) / ((( $this->topic['last_post'] - $this->topic['start_date']) / 86400)), 1 );
		}
		
		if ($avg_posts < 0)
		{
			$avg_posts = 1;
		}
		
		if ($avg_posts > ( $this->topic['posts'] + 1) )
		{
			$avg_posts = $this->topic['posts'] + 1;
		}
		
		$mod_logs = array();
		
		// Do we have any logs in the mod-logs DB about this topic? eh? well?
		
		$this->DB->build( array( 
								'select'	=> 'l.*',
								'from'		=> array( 'moderator_logs' => 'l' ),
								'where'		=> 'l.topic_id=' . $this->topic['tid'],
								'order'		=> 'l.ctime DESC',
								'add_join'	=> array( array(
															'select' 	=> 'm.members_seo_name',
															'from'		=> array( 'members' => 'm' ),
															'where'		=> 'l.member_id=m.member_id',
															'type'		=> 'left'
													)	),
												
						)	);
		$this->DB->execute();
		
		while ( $row = $this->DB->fetch() )
		{
			$mod_logs[] = $row;
		}

		$this->output .= $this->registry->getClass('output')->getTemplate('mod')->topicHistory( $this->topic, $avg_posts, $mod_logs );

		foreach( $this->registry->class_forums->forumsBreadcrumbNav( $this->topic['forum_id'] ) as $_nav )
		{
			$this->registry->getClass('output')->addNavigation( $_nav[0], $_nav[1] );
		}

		$this->registry->getClass('output')->addNavigation( $this->topic['title'], "{$this->settings['_base_url']}showtopic={$this->topic['tid']}" );
		$this->registry->getClass('output')->setTitle( $this->topic['title'] );
	}
	
	/**
	 * Unsubscribe all form
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _unsubscribeAllForm()
	{
		$this->_genericPermissionCheck();
		
		$tracker = $this->DB->buildAndFetch( array( 'select' => 'COUNT(trid) as subbed', 'from' => 'tracker', 'where' => "topic_id=" . $this->topic['tid'] ) );

		if ( $tracker['subbed'] < 1 )
		{
			$text = $this->lang->words['ts_none'];
		}
		else
		{
			$text = sprintf( $this->lang->words['ts_count'], $tracker['subbed'] );
		}

		$this->output .= $this->registry->getClass('output')->getTemplate('mod')->unsubscribeForm( $this->forum, $this->topic, $text );

		$this->registry->getClass('output')->addNavigation( $this->forum['name'], "{$this->settings['_base_url']}showforum={$this->forum['id']}" );
		$this->registry->getClass('output')->addNavigation( $this->topic['title'], "{$this->settings['_base_url']}showtopic={$this->topic['tid']}" );
		$this->registry->getClass('output')->setTitle( $this->lang->words['ts_title']." &gt; ".$this->topic['title'] );
	}
	
	/**
	 * Unsubscribe all complete
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _unsubscribeAll()
	{
		$this->_genericPermissionCheck();
		
		// Delete the subbies based on this topic ID
		
		$this->DB->delete( 'tracker', "topic_id=" . $this->topic['tid'] );
		
		$this->registry->output->redirectScreen( $this->lang->words['ts_redirect'], $this->settings['base_url'] . "showtopic=" . $this->topic['tid'] . "&amp;st=" . intval($this->request['st']) );
	}
	
	/**
	 * Merge two topics form
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _mergeStart()
	{
		$this->_resetModerator( $this->topic['forum_id'] );
		
		$this->_genericPermissionCheck( 'split_merge' );

		$this->output .= $this->registry->getClass('output')->getTemplate('mod')->mergeTopicsForm( $this->forum, $this->topic );

		$this->registry->getClass('output')->addNavigation( $this->forum['name'], "{$this->settings['_base_url']}showforum={$this->forum['id']}" );
		$this->registry->getClass('output')->addNavigation( $this->topic['title'], "{$this->settings['_base_url']}showtopic={$this->topic['tid']}" );
		$this->registry->getClass('output')->setTitle( $this->lang->words['mt_top']." ".$this->topic['title'] );
	}
	
	/**
	 * Merge two topics
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _mergeComplete()
	{
		$this->_resetModerator( $this->topic['forum_id'] );
		
		$this->_genericPermissionCheck( 'split_merge' );

		//-----------------------------------------
		// Check the input
		//-----------------------------------------
		
		if ( $this->request['topic_url'] == "" or $this->request['title'] == "" )
		{
			$this->_showError( 'mod_missing_url_title', 10388 );
		}
		
		//-----------------------------------------
		// Get the topic ID of the entered URL
		//-----------------------------------------

		$old_id = $this->_getTidFromUrl();
		
		if ( !$old_id )
		{
			$this->_showError( 'mod_missing_old_topic', 10389 );
		}

		//-----------------------------------------
		// Get the topic from the DB
		//-----------------------------------------
		
		$old_topic = $this->DB->buildAndFetch( array( 'select' => 'tid, title, forum_id, last_post, last_poster_id, last_poster_name, posts, views, topic_hasattach', 'from' => 'topics', 'where' => 'tid=' . intval($old_id) ) );

		if ( ! $old_topic['tid'] )
		{
			$this->_showError( 'mod_missing_old_topic', 10390 );
		}
		
		//-----------------------------------------
		// Did we try and merge the same topic?
		//-----------------------------------------
		
		if ( $old_id == $this->topic['tid'] )
		{
			$this->_showError( 'mod_same_topics', 10391 );
		}
		
		//-----------------------------------------
		// Do we have moderator permissions for this
		// topic (ie: in the forum the topic is in)
		//-----------------------------------------
		
		$pass = FALSE;
		
		if ( $this->topic['forum_id'] == $old_topic['forum_id'] )
		{
			$pass = TRUE;
		}
		else
		{
			if ( $this->memberData['g_is_supmod'] == 1 )
			{
				$pass = TRUE;
			}
			else
			{
				$other_mgroups = array();
				
				if( $this->memberData['mgroup_others'] )
				{
					$other_mgroups = explode( ",", IPSText::cleanPermString( $this->memberData['mgroup_others'] ) );
				}
				
				$other_mgroups[] = $this->memberData['member_group_id'];
				
				
				$this->DB->build( array( 'select'	=> 'mid',
												'from'	=> 'moderators',
												'where'	=> "forum_id LIKE '%,{$old_topic['forum_id']},%' AND (member_id='" . $this->memberData['member_id'] . "' OR (is_group=1 AND group_id IN(" . implode( ",", $other_mgroups ) . ")))" ) );
											  
				$this->DB->execute();
				
				if ( $this->DB->getTotalRows() )
				{
					$pass = TRUE;
				}
			}
		}
		
		if ( $pass == FALSE )
		{
			// No, we don't have permission
			
			$this->_showError();
		}
		
		//-----------------------------------------
		// Update the posts, remove old polls, subs and topic
		//-----------------------------------------
		
		$this->DB->update( 'posts', array( 'topic_id' => $this->topic['tid'] ), 'topic_id=' . $old_topic['tid'] );
		
		$this->DB->delete( 'polls', "tid=" . $old_topic['tid'] );
		
		$this->DB->delete( 'voters', "tid=" . $old_topic['tid'] );
		
		$this->DB->delete( 'tracker', "topic_id=" . $old_topic['tid'] );
		
		$this->DB->delete( 'topics', "tid=" . $old_topic['tid'] );
		
		//-----------------------------------------
		// Update the newly merged topic
		//-----------------------------------------
		
		$updater = array(  'title'			=> $this->request['title'],
						   'description'	=> $this->request['desc'],
						   'views'			=> $old_topic['views'] + $this->topic['views']
						);
						
		if ( $old_topic['last_post'] > $this->topic['last_post'] )
		{
			$updater['last_post']			= $old_topic['last_post'];
			$updater['last_poster_name']	= $old_topic['last_poster_name'];
			$updater['seo_last_name']       = IPSText::makeSeoTitle( $old_topic['last_poster_name'] );
			$updater['last_poster_id']		= $old_topic['last_poster_id'];
		}
		
		if( $old_topic['topic_hasattach'] )
		{
			$updater['topic_hasattach']		= intval($this->topic['topic_hasattach']) + $old_topic['topic_hasattach'];
		}

		$this->DB->update( 'topics', $updater, 'tid=' . $this->topic['tid'] );
		
		//-----------------------------------------
		// Fix up the "new_topic" attribute.
		//-----------------------------------------
		
		$this->DB->build( array( 'select'	=> 'pid, author_name, author_id, post_date',
										'from'	=> 'posts',
										'where'	=> "topic_id=" . $this->topic['tid'],
										'order'	=> 'post_date ASC',
										'limit'	=> array( 0,1 ) ) );
		
		$this->DB->execute();
		
		if ( $first_post = $this->DB->fetch() )
		{
			$this->DB->update( 'posts', array( 'new_topic' => 0 ), "topic_id={$this->topic['tid']}" );
			$this->DB->update( 'posts', array( 'new_topic' => 1 ), "pid={$first_post['pid']}" );
		}
		
		//-----------------------------------------
		// Reset the post count for this topic
		//-----------------------------------------
		
		$amode = $first_post['author_id'] ? 1 : 0;
		
		$this->DB->build( array( 'select'	=> 'COUNT(*) as posts',
										'from'	=> 'posts',
										'where'	=> "queued <> 1 AND topic_id=" . $this->topic['tid'] ) );
		
		$this->DB->execute();
		
		if ( $post_count = $this->DB->fetch() )
		{
			$post_count['posts']--; //Remove first post
			
			$this->DB->update( 'topics', array( 'posts'				=> $post_count['posts'],
													'starter_name'		=> $first_post['author_name'],
													'starter_id'		=> $first_post['author_id'],
													'start_date'		=> $first_post['post_date'],
													'author_mode'		=> $amode,
													'topic_firstpost'	=> $first_post['pid']
								) , 'tid=' . $this->topic['tid'] );
		}
		
		$this->registry->class_forums->removePostFromSearchIndex( $old_topic['tid'], 0, 1 );
		$this->modLibrary->rebuildTopic( $this->topic['tid'] );
				
		//-----------------------------------------
		// Update the forum(s)
		//-----------------------------------------
		
		$this->registry->class_forums->allForums[ $this->topic['forum_id'] ]['_update_deletion'] = 1;
		$this->modLibrary->forumRecount( $this->topic['forum_id'] );
		
		if ( $this->topic['forum_id'] != $old_topic['forum_id'] )
		{
			$this->registry->class_forums->allForums[ $old_topic['forum_id'] ]['_update_deletion'] = 1;
			$this->modLibrary->forumRecount( $old_topic['forum_id'] );
		}
		
		$this->_addModeratorLog( sprintf( $this->lang->words['acp_merged_topic'], $old_topic['title'], $this->topic['title'] ) );
		
		$this->registry->output->redirectScreen( $this->lang->words['mt_redirect'], $this->settings['base_url'] . "showtopic=" . $this->topic['tid'] );
	}
	
	/**
	 * Merge two topics
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _moveForm()
	{
		$this->_resetModerator( $this->topic['forum_id'] );
		
		$this->_genericPermissionCheck( 'move_topic' );

		$jump_html = $this->registry->getClass('class_forums')->buildForumJump(0,1,1);
				 								
		$this->output .= $this->registry->getClass('output')->getTemplate('mod')->moveTopicForm( $this->forum, $this->topic, $jump_html );

		$this->registry->getClass('output')->addNavigation( $this->forum['name'], "{$this->settings['_base_url']}showforum={$this->forum['id']}" );
		$this->registry->getClass('output')->addNavigation( $this->topic['title'], "{$this->settings['_base_url']}showtopic={$this->topic['tid']}" );
		$this->registry->getClass('output')->setTitle( $this->lang->words['t_move'].": ".$this->topic['title'] );
	}
	
	/**
	 * Merge two topics
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _doMove()
	{
		$this->_resetModerator( $this->topic['forum_id'] );
		
		$this->_genericPermissionCheck( 'move_topic' );

		//-----------------------------------------
		// Check for input..
		//-----------------------------------------

		if ( !$this->request['move_id'] or $this->request['move_id'] == -1 )
		{
			$this->_showError( 'mod_no_move_forum', 10392 );
		}

		if ( $this->request['move_id'] == $this->request['f'] )
		{
			$this->_showError( 'mod_no_move_save', 10393 );
		}
		
		$source = intval($this->request['f']);
		$moveto = intval($this->request['move_id']);
		
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => 'id, sub_can_post, name, redirect_on', 'from' => 'forums', 'where' => "id IN(" . $source . ',' . $moveto . ")" ) );
		$this->DB->execute();
		
		if ($this->DB->getTotalRows() != 2)
		{
			$this->_showError( 'mod_no_move_forum', 10394 );
		}
		
		$source_name	= "";
		$dest_name		= "";
		
		//-----------------------------------------
		// Check for an attempt to move into a subwrap forum
		//-----------------------------------------
		
		while ( $f = $this->DB->fetch() )
		{
			if ( $f['id'] == $source )
			{
				$source_name	= $f['name'];
			}
			else
			{
				$dest_name		= $f['name'];
			}
			
			if ( ( $f['sub_can_post'] != 1 ) OR $f['redirect_on'] == 1 )
			{
				$this->_showError( 'mod_forum_no_posts', 10395 );
			}
		}

		$this->modLibrary->topicMove( $this->topic['tid'], $source, $moveto, $this->request['leave'] == 'y' ? 1 : 0 );

		$this->_addModeratorLog( sprintf( $this->lang->words['acp_moved_a_topic'], $source_name, $dest_name ) );
		
		// Resync the forums..
		
		$this->registry->class_forums->allForums[ $source ]['_update_deletion'] = 1;
		$this->registry->class_forums->allForums[ $moveto ]['_update_deletion'] = 1;
		
		$this->modLibrary->forumRecount($source);
		$this->modLibrary->forumRecount($moveto);
		
		if( $moveto == $this->settings['forum_trash_can_id'] )
		{
			$this->modLibrary->clearModQueueTable( 'topic', $this->topic['tid'] );
		}
	
		$this->registry->output->redirectScreen( $this->lang->words['p_moved'], $this->settings['base_url'] . "showforum=" . $this->forum['id'] );
	}

	/**
	 * Delete a single post
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _deletePost()
	{
		// Get this post id.
		
		$this->request['p'] = intval( $this->request['p'] );
		
		$post = $this->DB->buildAndFetch( array( 'select' => 'pid, author_id, post_date, new_topic', 'from' => 'posts', 'where' => "topic_id={$this->topic['tid']} and pid=" . $this->request['p'] ) );

		if ( !$post['pid'] )
		{
			$this->_showError( 'mod_no_delete_post_find', 10396 );
		}
		
		$this->_resetModerator( $this->topic['forum_id'] );
		
		if( !$this->_genericPermissionCheck( 'delete_post', '', 0, true ) )
		{
			if( !$this->memberData['g_delete_own_posts'] OR $this->memberData['member_id'] != $post['author_id'] )
			{
				$this->_showError( 'mod_no_delete_post', 10397 );
			}
		}
		
		//-----------------------------------------
		// Check to make sure that this isn't the first post in the topic..
		//-----------------------------------------
		
		if ( $post['new_topic'] == 1 )
		{
			$this->_showError( 'mod_delete_first_post', 10398 );
		}
		
		if ( $this->trash_forum and $this->trash_forum != $this->forum['id'] )
		{
			//-----------------------------------------
			// Set up and pass to split topic handler
			//-----------------------------------------

			$this->request['checked']			= 1;
			$this->request['fid']				= $this->trash_forum;
			$this->request['title']				= $this->lang->words['mod_from'] . " " . $this->topic['title'];
			$this->request['desc']				= $this->lang->words['mod_from_id'] . " " . $this->topic['tid'];
			$this->request['selectedpids'][]	= $this->request['p'];
			
			$this->trash_inuse = 1;
			
			$this->pids  = $this->_getIds( 'selectedpids' );
			$this->_multiSplitTopic();
			
			$this->trash_inuse = 0;
		}
		else
		{
			$this->modLibrary->postDelete( $this->request['p'] );
			$this->modLibrary->forumRecount( $this->forum['id'] );
		}
		
		$this->modLibrary->clearModQueueTable( 'post', $post['pid'] );
		
		$this->registry->output->redirectScreen( $this->lang->words['post_deleted'], $this->settings['base_url'] . "showtopic=" . $this->topic['tid'] . "&amp;st=" . intval($this->request['st']), $this->topic['title_seo'] );
	}
	
	/**
	 * Show the edit topic title form
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _editForm()
	{
		$this->_resetModerator( $this->topic['forum_id'] );
		
		$this->_genericPermissionCheck( 'edit_topic' );
								
		$this->output .= $this->registry->getClass('output')->getTemplate('mod')->editTopicTitle( $this->forum, $this->topic );

		$this->registry->getClass('output')->addNavigation( $this->forum['name'], "{$this->settings['_base_url']}showforum={$this->forum['id']}" );
		$this->registry->getClass('output')->addNavigation( $this->topic['title'], "{$this->settings['_base_url']}showtopic={$this->topic['tid']}" );
		$this->registry->getClass('output')->setTitle( $this->lang->words['t_edit'].": ".$this->topic['title'] );
	}
	
	/**
	 * Save the topic title edits
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _doEdit()
	{
		$this->_resetModerator( $this->topic['forum_id'] );
		
		$this->_genericPermissionCheck( 'edit_topic' );

		if ( trim($this->request['TopicTitle']) == "" )
		{
			$this->_showError( 'mod_no_topic_title', 10399 );
		}
		
		require_once( IPSLib::getAppDir( 'forums' ) . '/sources/classes/post/classPost.php' );
		require_once( IPSLib::getAppDir( 'forums' ) . '/sources/classes/post/classPostForms.php' );
		
		$_postClass = new classPostForms( $this->registry );
		
		$this->request['TopicTitle'] =  $_postClass->cleanTopicTitle( $this->request['TopicTitle']  );
		$this->request['TopicTitle'] =  trim( IPSText::getTextClass( 'bbcode' )->stripBadWords( $this->request['TopicTitle'] ) );

		$this->request['TopicDesc'] =  trim( IPSText::getTextClass( 'bbcode' )->stripBadWords( $this->request['TopicDesc'] ) );
		$this->request['TopicDesc'] =  IPSText::mbsubstr( $this->request['TopicDesc'], 0, 70  );
		
		$title_seo = IPSText::makeSeoTitle( $this->request['TopicTitle'] );
		
		$this->DB->update( 'topics', array( 'title' => $this->request['TopicTitle'], 'description' => $this->request['TopicDesc'], 'title_seo' => $title_seo ), 'tid=' . $this->topic['tid'] );

		$this->modLibrary->forumRecount( $this->forum['id'] );

		$this->_addModeratorLog( sprintf( $this->lang->words['acp_edit_title'], $this->topic['tid'], $this->topic['title'], $topic_title ) );
	
		$this->registry->output->redirectScreen( $this->lang->words['p_edited'], $this->settings['base_url'] . "showtopic=" . $this->topic['tid'] );
	}
		
	/**
	 * Open a closed topic
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _openTopic()
	{
		if ( $this->topic['state'] == 'open' )
		{
			$this->_showError( 'mod_no_open_opened', 103100 );
		}
		
		$this->_resetModerator( $this->topic['forum_id'] );
		
		if( !$this->_genericPermissionCheck( 'open_topic', '', 0, true ) )
		{
			if( !$this->memberData['g_open_close_posts'] OR $this->topic['starter_id'] != $this->memberData['member_id'] )
			{
				$this->_showError( 'mod_no_open_perms', 103101 );
			}
		}

		$this->modLibrary->topicOpen($this->topic['tid']);
		
		$this->_addModeratorLog( $this->lang->words['acp_opened_topic'] );
	
		$this->registry->output->redirectScreen( $this->lang->words['p_opened'], $this->settings['base_url'] . "showforum=" . $this->topic['forum_id'] . '&st=' . intval( $this->request['st'] ) );
	}
	
	/**
	 * Move topics to another forum from the prune popup tool
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _pruneMove()
	{
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		$this->_resetModerator( $this->topic['forum_id'] );
		
		$this->_genericPermissionCheck( 'mass_move' );
		
		///-----------------------------------------
		// SET UP
		//-----------------------------------------
		
		$pergo		= intval( $this->request['pergo'] ) ? intval( $this->request['pergo'] ) : 50;
		$max		= intval( $this->request['max'] );
		$current	= intval($this->request['current']);
		$maxdone	= $pergo + $current;
		$tid_array	= array();
		$starter	= trim( $this->request['starter'] );
		$state		= trim( $this->request['state'] );
		$posts		= intval( $this->request['posts'] );
		$dateline	= intval( $this->request['dateline'] );
		$source		= $this->forum['id'];
		$moveto		= intval($this->request['df']);
		$date		= 0;
		$ignore_pin	= intval( $this->request['ignore_pin'] );
		
		if( $dateline )
		{
			$date	= time() - $dateline*60*60*24;			
		}

		//-----------------------------------------
		// Carry on...
		//-----------------------------------------
		
		$dbPruneWhere = $this->modLibrary->sqlPruneCreate( $this->forum['id'], $starter, $state, $posts, $date, $ignore_pin );

		$this->DB->build( array(
									'select'	=> 'tid',
									'from'		=> 'topics',
									'where'		=> $dbPruneWhere,
									'limit'		=> array( 0, $pergo ),
							)		);
		$batch	= $this->DB->execute();
		
		//-----------------------------------------
		// Get tids
		//-----------------------------------------
		
		while ( $row = $this->DB->fetch($batch) )
		{
			$tid_array[] = $row['tid'];
		}
		
		//-----------------------------------------
		// Check for an attempt to move into a subwrap forum
		//-----------------------------------------
		
		$f = $this->registry->class_forums->allForums[ $moveto ];
		
		if ( $f['sub_can_post'] != 1 )
		{
			$this->_showError( 'mod_forum_no_posts', 103102 );
		}
		
		$num_rows	= 0;
		
		if( $this->modLibrary->topicMove( $tid_array, $source, $moveto ) )
		{
			$num_rows	= count($tid_array);
		}
		
		$this->_addModeratorLog( $this->lang->words['acp_mass_moved'] );
		
		//-----------------------------------------
		// Show results or refresh..
		//-----------------------------------------
		
		if ( ! $num_rows )
		{
			//-----------------------------------------
			// Update forum deletion
			//-----------------------------------------
			
			$this->registry->class_forums->allForums[ $moveto ]['_update_deletion'] = 1;
			$this->registry->class_forums->allForums[ $source ]['_update_deletion'] = 1;
			
			//-----------------------------------------
			// Resync the forums..
			//-----------------------------------------
			
			$this->modLibrary->forumRecount($source);
			$this->modLibrary->forumRecount($moveto);
		
			//-----------------------------------------
			// Done...
			//-----------------------------------------
			
			$this->request[ 'check'] =  0 ;
			$this->_pruneStart( $this->registry->getClass('output')->getTemplate('mod')->simplePage( $this->lang->words['cp_results'], $this->lang->words['cp_result_move'] . ($max) ) );
		}
		else
		{
			$link  = "app=forums&amp;module=moderate&amp;section=moderate&amp;f={$this->forum['id']}&amp;do=prune_move&amp;df=" . $this->request['df'] . "&amp;pergo={$pergo}&amp;current={$maxdone}&amp;max={$max}";
			$link .= "&amp;starter={$starter}&amp;state={$state}&amp;posts={$posts}&amp;dateline={$dateline}&amp;ignore_pin={$ignore_pin}";
			$link .= "&amp;auth_key=".$this->member->form_hash;
			$done  = $current + $num_rows;
			$text  = sprintf( $this->lang->words['cp_batch_done'], $done, $max - $done );
			
			$this->registry->output->redirectScreen( $text, $this->settings['base_url'] . $link );
		}
	}
	
	/**
	 * Prune delete topics
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _pruneFinish()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$this->_resetModerator( $this->topic['forum_id'] );
		
		$this->_genericPermissionCheck( 'mass_prune' );
		
		//-----------------------------------------
		// SET UP
		//-----------------------------------------
		
		$pergo		= intval( $this->request['pergo'] ) ? intval( $this->request['pergo'] ) : 50;
		$max		= intval( $this->request['max'] );
		$current	= intval($this->request['current']);
		$maxdone	= $pergo + $current;
		$tid_array	= array();
		$starter	= trim( $this->request['starter'] );
		$state		= trim( $this->request['state'] );
		$posts		= intval( $this->request['posts'] );
		$dateline	= intval( $this->request['dateline'] );
		$date		= 0;
		$ignore_pin	= intval( $this->request['ignore_pin'] );
		
		if( $dateline )
		{
			$date	= time() - $dateline*60*60*24;			
		}
		
		//-----------------------------------------
		// Carry on...
		//-----------------------------------------
		
		$dbPruneWhere = $this->modLibrary->sqlPruneCreate( $this->forum['id'], $starter, $state, $posts, $date, $ignore_pin );

		$this->DB->build( array(
									'select'	=> 'tid',
									'from'		=> 'topics',
									'where'		=> $dbPruneWhere,
									'limit'		=> array( 0, $pergo ),
							)		);
		$batch	= $this->DB->execute();
		
		//-----------------------------------------
		// Got anything?
		//-----------------------------------------
		$num_rows = $this->DB->getTotalRows($batch);
		
		if ( ! $num_rows )
		{
			if ( !$current )
			{
				$this->_showError( 'mod_no_prune_topics', 103103 ); 
			}
		}
		
		//-----------------------------------------
		// Get tiddles
		//-----------------------------------------
		
		while ( $tid = $this->DB->fetch($batch) )
		{
			$tid_array[] = $tid['tid'];
		}
		
		$this->modLibrary->topicDelete($tid_array);
		
		//-----------------------------------------
		// Show results or refresh..
		//-----------------------------------------
		
		if ( ! $num_rows )
		{
			//-----------------------------------------
			// Done...
			//-----------------------------------------
			
			$this->_addModeratorLog( $this->lang->words['acp_pruned_forum'] );
			
			$this->request[ 'check'] =  0 ;
			
			$this->_pruneStart( $this->registry->getClass('output')->getTemplate('mod')->simplePage( $this->lang->words['cp_results'], $this->lang->words['cp_result_del'] . ($max)  ) );
		}
		else
		{
			$link  = "app=forums&amp;module=moderate&amp;section=moderate&amp;f={$this->forum['id']}&amp;do=prune_finish&amp;pergo={$pergo}&amp;current={$maxdone}&amp;max={$max}";
			$link .= "&amp;starter={$starter}&amp;state={$state}&amp;posts={$posts}&amp;dateline={$dateline}&amp;ignore_pin={$ignore_pin}";
			$link .= "&amp;auth_key=".$this->member->form_hash;
			$done  = $current + $num_rows;
			$text  = sprintf( $this->lang->words['cp_batch_done'], $done, $max - $done );
			
			$this->registry->output->redirectScreen( $text, $this->settings['base_url'] . $link );
		}
	}
	
	/**
	 * Prune popup form
	 *
	 * @access	private
	 * @param	string		HTML output
	 * @return	void		[Outputs to screen]
	 */
	private function _pruneStart( $complete_html="" )
	{
		//-----------------------------------------
		// Check permissions
		//-----------------------------------------
		
		$this->_resetModerator( $this->topic['forum_id'] );
		
		$this->_genericPermissionCheck( 'mass_prune' );
		
		$confirm_data = array( 'show' => '' );
		
		//-----------------------------------------
		// Check per go
		//-----------------------------------------
		
		$this->request[ 'pergo'		] =  $this->request['pergo']		? intval( $this->request['pergo'] )	: 50;
		$this->request[ 'posts'		] =  $this->request['posts']		? intval( $this->request['posts'] )	: '';
		$this->request[ 'member'	] =  $this->request['member']		? $this->request['member']			: '' ;
		$this->request[ 'determine'	] =  $this->request['determine']	? $this->request['determine']		: '' ;
		$this->request[ 'dateline'	] =  $this->request['dateline']		? $this->request['dateline']		: '' ;
		
		//-----------------------------------------
		// Are we checking first?
		//-----------------------------------------
		
		if ( $this->request['check'] AND $this->request['check'] == 1 )
		{
			$link		= "&amp;pergo=" . $this->request['pergo'];
			$link_text	= $this->lang->words['cp_prune_dorem'];
			
			$tcount = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as tcount', 'from' => 'topics', 'where' => "forum_id={$this->forum['id']}" ) );
			
			$db_query = "";
			
			//-----------------------------------------
			// date...
			//-----------------------------------------
		
			if ($this->request['dateline'])
			{
				$date		= time() - $this->request['dateline']*60*60*24;
				$db_query	.= " AND last_post < $date";
				
				$link		.= "&amp;dateline={$this->request['dateline']}";
			}
			
			//-----------------------------------------
			// Member...
			//-----------------------------------------
			
			if ( $this->request['member'] )
			{
				$mem = $this->DB->buildAndFetch( array( 'select' => 'member_id', 'from' => 'members', 'where' => "members_display_name='" . $this->request['member'] . "'" ) );

				if ( !$mem['member_id'] )
				{
					$this->_showError( 'mod_no_prune_member', 103104 );
				}
				else
				{
					$db_query	.= " AND starter_id=" . $mem['member_id'];
					$link		.= "&amp;starter={$mem['member_id']}";
				}
			}
			
			//-----------------------------------------
			// Posts / Topic type
			//-----------------------------------------
			
			if ($this->request['posts'])
			{
				$db_query	.= " AND posts < " . intval($this->request['posts']);
				$link		.= "&amp;posts=" . $this->request['posts'];
			}
			
			if ($this->request['topic_type'] != 'all')
			{
				$db_query	.= " AND state='".$this->request['topic_type']."'";
				$link		.= "&amp;state=" . $this->request['topic_type'];
			}
			
			if ($this->request['ignore_pin'] == 1)
			{
				$db_query	.= " AND pinned <> 1";
				$link		.= "&amp;ignore_pin=1";
			}
			
			$count = $this->DB->buildAndFetch( array( 'select'	=> 'COUNT(*) as count',
																'from'	=> 'topics',
																'where'	=> "forum_id=" . $this->forum['id'] . $db_query ) );
			
			//-----------------------------------------
			// Prune?
			//-----------------------------------------

			if ( $this->request['df'] == 'prune' )
			{
				$link = "app=forums&amp;module=moderate&amp;section=moderate&amp;f={$this->forum['id']}&amp;do=prune_finish&amp;" . $link;
			}
			else
			{
				if ( $this->request['df'] == $this->forum['id'] )
				{
					$this->_showError( 'mod_no_move_save', 103105 );
				}
				else if ( $this->request['df'] == -1 )
				{
					$this->_showError( 'mod_no_move_forum', 103106 );
				}
				
				$link		= "app=forums&amp;module=moderate&amp;section=moderate&amp;f={$this->forum['id']}&amp;do=prune_move&amp;df=" . $this->request['df'] . $link;
				$link_text	= $this->lang->words['cp_prune_domove'];
			}
			
			//-----------------------------------------
			// Build data
			//-----------------------------------------
			
			$confirm_data = array( 'tcount'		=> $tcount['tcount'],
								   'count'		=> $count['count'],
								   'link'		=> $link . '&amp;max=' . $count['count'],
								   'link_text'	=> $link_text,
								   'show'		=> 1 );
		}

		$html_forums .= $this->registry->getClass('class_forums')->buildForumJump(0,1,1);

		//-----------------------------------------
		// Make current destination forum this one if selected
		// before
		//-----------------------------------------
		
		if ( $this->request['df'] )
		{
			$html_forums = str_replace( '<option value="' . intval($this->request['df']) . '"', '<option value="' . intval($this->request['df']) . '" selected="selected"', $html_forums );
		}
		
		$this->output .= $this->registry->getClass('output')->getTemplate('mod')->pruneSplash( $this->forum, $html_forums, $confirm_data, $complete_html );
		
		$this->registry->getClass('output')->setTitle( $this->settings['board_name'] );
		$this->registry->output->addContent( $this->output );
		$this->registry->getClass('output')->sendOutput();
	}
	
	/**
	 * Topic multi-moderation
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _multiTopicModify()
	{
		$this->tids  = $this->_getIds();

		if( count( $this->tids ) )
		{
			switch ( $this->request['tact'] )
			{
				case 'close':
					$this->_multiAlterTopics('close_topic', "state='closed'");
				break;
				case 'open':
					$this->_multiAlterTopics('open_topic', "state='open'");
				break;
				case 'pin':
					$this->_multiAlterTopics('pin_topic', "pinned=1");
				break;
				case 'unpin':
					$this->_multiAlterTopics('unpin_topic', "pinned=0");
				break;
				case 'approve':
					$this->_multiAlterTopics('topic_q', "approved=1");
				break;
				case 'unapprove':
					$this->_multiAlterTopics('topic_q', "approved=0");
				break;
				case 'delete':
					$this->_multiAlterTopics('delete_topic');
				break;
				case 'move':
					$this->_multiStartCheckedMove();
				return;
				break;
				case 'domove':
					$this->_multiCompleteCheckedMove();
				break;
				case 'merge':
					$this->_multiTopicMerge();
				break;
				default:
					$this->_multiTopicMmod();
				break;
			}
		}

		IPSCookie::set( 'modtids', '', 0 );
		
		if ( $this->forum['id'] )
		{
			$url = "showforum=" . $this->forum['id'];
			$url = ( $this->request['st'] ) ? "showforum=" . $this->forum['id'] . '&st=' . $this->request['st'] : $url;
			
			$this->registry->output->redirectScreen( $this->lang->words['cp_redirect_topics'], $this->settings['base_url'] . $url, $this->forum['name_seo']  );
		}
	}
	
	/**
	 * Merge two or more topics
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _multiTopicMerge()
	{
		$this->_genericPermissionCheck( 'split_merge' );
		
		if ( count($this->tids) < 2 )
		{
			$this->_showError( 'mod_topics_merge_two', 103107 );
		}
		
		//-----------------------------------------
		// Get the topics in ascending date order
		//-----------------------------------------
		
		$topics		= array();
		$merge_ids	= array();
		
		$this->DB->build( array( 'select' => 'tid, forum_id', 'from' => 'topics', 'where' => 'tid IN (' . implode( ",", $this->tids ) . ')', 'order' => 'start_date asc' ) );
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			$topics[] = $r;
		}
		
		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( count($topics) < 2 )
		{
			$this->_showError( 'mod_topics_merge_two', 103108 );
		}
		
		//-----------------------------------------
		// Make sure we can moderate EACH topic
		//-----------------------------------------
		
		foreach( $topics as $topic )
		{
			$this->_resetModerator( $topic['forum_id'] );
			
			$this->_genericPermissionCheck( 'split_merge' );
		}
		
		//-----------------------------------------
		// Get topic ID for first topic 'master'
		//-----------------------------------------
		
		$first_topic	= array_shift( $topics );
		$main_topic_id	= $first_topic['tid'];

		foreach( $topics as $t )
		{
			/* Clear the search index */
			$this->registry->class_forums->removePostFromSearchIndex( $t['tid'], 0, 1 );
			
			$merge_ids[] = $t['tid'];
		}
		
		//-----------------------------------------
		// Update the posts, remove old polls, subs and topic
		//-----------------------------------------
		
		$this->DB->update( 'posts', array( 'topic_id' => $main_topic_id ), 'topic_id IN (' . implode( ",", $merge_ids ) . ")" );
		
		$this->DB->delete( 'polls', "tid IN (" . implode( ",", $merge_ids ) . ")" );
		
		$this->DB->delete( 'voters', "tid IN (" . implode( ",", $merge_ids ) . ")" );
		
		$this->DB->delete( 'tracker', "topic_id IN (" . implode( ",", $merge_ids ) . ")" );
		
		$this->DB->delete( 'topics', "tid IN (" . implode( ",", $merge_ids ) . ")" );
		
		//-----------------------------------------
		// Update the newly merged topic
		//-----------------------------------------
		
		$this->modLibrary->rebuildTopic( $main_topic_id );

		$this->registry->class_forums->allForums[ $this->forum['id'] ]['_update_deletion'] = 1;
		$this->modLibrary->forumRecount( $this->forum['id'] );
		$this->modLibrary->statsRecount();
		
		/* Log */
		$this->_addModeratorLog( sprintf( $this->lang->words['multi_topic_merge_mod_log'], count( $topics ) ) );
	}
	
	/**
	 * Alter multiple topics at once
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _multiAlterTopics( $mod_action="", $sql="" )
	{
		if ( ! $mod_action )
		{
			$this->_showError( 'mod_um_what_now', 103109 );
		}
	
		$this->_genericPermissionCheck( $mod_action );
		
		//-----------------------------------------
		// Make sure we can moderate EACH topic
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => 'tid, forum_id', 'from' => 'topics', 'where' => 'tid IN (' . implode( ",", $this->tids ) . ')', 'order' => 'start_date asc' ) );
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			$topics[] = $r;
		}
		
		foreach( $topics as $topic )
		{
			$this->_resetModerator( $topic['forum_id'] );
			
			$this->_genericPermissionCheck( $mod_action );
		}

		if ( $mod_action != 'delete_topic' )
		{
			$this->DB->buildAndFetch( array( 'update' => 'topics', 'set' => $sql, 'where' => "tid IN(" . implode( ",", $this->tids ) . ") AND state!='link'" ) );
			
			$this->_addModeratorLog( sprintf( $this->lang->words['acp_altered_topics'], $sql, implode( ",", $this->tids) ) );
			
			if( $mod_action == 'topic_q' AND $sql == 'approved=1' )
			{
				$this->modLibrary->clearModQueueTable( 'topic', $this->tids, true );
			}
		}
		else
		{
			if ( $this->trash_forum and $this->trash_forum != $this->forum['id'] )
			{
				//-----------------------------------------
				// Move, don't delete
				//-----------------------------------------
				
				$this->registry->class_forums->allForums[ $this->forum['id'] ]['_update_deletion'] = 1;
				
				$this->modLibrary->topicMove( $this->tids, $this->forum['id'], $this->trash_forum );
				$this->modLibrary->forumRecount($this->trash_forum);
				$this->_addModeratorLog( $this->lang->words['acp_trashcan_topics']." ".implode(",",$this->tids) );
			}
			else
			{
				$this->modLibrary->topicDelete( $this->tids );
				$this->_addModeratorLog( sprintf( $this->lang->words['acp_deleted_topics'], implode( ",", $this->tids ) ) );
			}
			
			$this->modLibrary->clearModQueueTable( 'topic', $this->tids );
		}
		
		if ( $mod_action == 'delete_topic' or $mod_action == 'topic_q' )
		{
			$this->registry->class_forums->allForums[ $this->forum['id'] ]['_update_deletion'] = 1;
			
			$this->modLibrary->forumRecount( $this->forum['id'] );
			$this->modLibrary->statsRecount();
		}
	}
	
	/**
	 * Show the form to move topics
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _multiStartCheckedMove()
	{
		$this->_genericPermissionCheck( 'move_topic' );

		$jump_html	= $this->registry->getClass('class_forums')->buildForumJump(0,1,1);
		$topics		= array();

		$this->DB->build( array( 'select' => 'title, tid, forum_id', 'from' => 'topics', 'where' => "forum_id=" . $this->forum['id'] . " AND tid IN(" . implode( ",", $this->tids ) . ")" ) );
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$topics[] = $row;
		}
		
		//-----------------------------------------
		// Make sure we can moderate EACH topic
		//-----------------------------------------

		foreach( $topics as $topic )
		{
			$this->_resetModerator( $topic['forum_id'] );
			
			$this->_genericPermissionCheck( 'move_topic' );
		}
		
		$this->output .= $this->registry->getClass('output')->getTemplate('mod')->moveTopicsForm( $this->forum, $jump_html, $topics );

		$this->registry->getClass('output')->addNavigation( $this->forum['name'], $this->settings['base_url'] . "showforum={$this->forum['id']}" );
		$this->registry->getClass('output')->setTitle( $this->lang->words['cp_ttitle'] );
	}
	
	/**
	 * Complete the topic move
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _multiCompleteCheckedMove()
	{
		$this->_genericPermissionCheck( 'move_topic' );

		$add_link	= $this->request['leave'] == 'y' ? 1 : 0;
		$dest_id	= intval($this->request['df']);
		$source_id	= $this->forum['id'];
 		
		//-----------------------------------------
		// Make sure we can moderate EACH topic
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => 'tid, forum_id', 'from' => 'topics', 'where' => 'tid IN (' . implode( ",", $this->tids ) . ')', 'order' => 'start_date asc' ) );
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			$topics[] = $r;
		}
		
		foreach( $topics as $topic )
		{
			$this->_resetModerator( $topic['forum_id'] );
			
			$this->_genericPermissionCheck( 'move_topic' );
		}
 			
		//-----------------------------------------
		// Check for input..
		//-----------------------------------------
		
		if ( !$source_id OR !$dest_id OR $dest_id == -1 )
		{
			$this->_showError( 'mod_no_forum_move', 103110 );
		}

		if ( $source_id == $dest_id )
		{
			$this->_showError( 'mod_no_move_save', 103111 );
		}
		
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => 'id, sub_can_post, name, redirect_on', 'from' => 'forums', 'where' => "id IN(" . $source_id . ',' . $dest_id . ")" ) );
		$this->DB->execute();
		
		if ( $this->DB->getTotalRows() != 2 )
		{
			$this->_showError( 'mod_no_move_save', 103112 );
		}
		
		$source_name	= "";
		$dest_name		= "";
		
		//-----------------------------------------
		// Check for an attempt to move into a subwrap forum
		//-----------------------------------------
		
		while ( $f = $this->DB->fetch() )
		{
			if ( $f['id'] == $source_id )
			{
				$source_name = $f['name'];
			}
			else
			{
				$dest_name = $f['name'];
			}
			
			if ( ( $f['sub_can_post'] != 1 ) OR $f['redirect_on'] == 1 )
			{
				$this->_showError( 'mod_forum_no_posts', 103113 );
			}
		}
		
		$this->modLibrary->topicMove( $this->tids, $source_id, $dest_id, $add_link );
		
		if( $dest_id == $this->settings['forum_trash_can_id'] )
		{
			$this->modLibrary->clearModQueueTable( 'topic', $this->tids );
		}
		
		//-----------------------------------------
		// Resync the forums..
		//-----------------------------------------
		
		$this->registry->class_forums->allForums[ $source_id ]['_update_deletion'] = 1;
		$this->registry->class_forums->allForums[  $dest_id  ]['_update_deletion'] = 1;
		
		$this->modLibrary->forumRecount($source_id);
		$this->modLibrary->forumRecount($dest_id);
		
		$this->_addModeratorLog( sprintf( $this->lang->words['acp_moved_topics'], $source_name, $dest_name ) );
	}
	
	/**
	 * Topic multi-moderation
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _multiTopicMmod()
	{
		//-----------------------------------------
		// It's tea time
		//-----------------------------------------
		
		if ( ! strstr( $this->request['tact'], 't_' ) OR !count($this->tids) )
		{
			$this->_showError( 'mod_stupid_beggar', 103114 );
		}
		
		$mm_id	= intval( str_replace( 't_', '', $this->request['tact'] ) );
		
		//-----------------------------------------
		// Init modfunc module
		//-----------------------------------------
		
		$this->modLibrary->init( $this->forum, "", $this->moderator );
		
		//-----------------------------------------
		// Do we have permission?
		//-----------------------------------------
		
		if ( $this->modLibrary->mmAuthorize() != TRUE )
		{
			$this->_showError( 'mod_no_multimod', 103115 );
		}

		$mm_data	= $this->caches['multimod'][ $mm_id ];

		
		if ( ! $mm_data )
		{
			$this->_showError( 'mod_no_mm_id', 103116 );
		}
		
		//-----------------------------------------
		// Does this forum have this mm_id
		//-----------------------------------------
		
		if ( $this->modLibrary->mmCheckIdInForum( $this->forum['id'], $mm_data ) != TRUE )
		{
			$this->_showError( 'mod_no_multimod', 103117 );
		}
		
		//-----------------------------------------
		// Still here? We're damn good to go sir!
		//-----------------------------------------
		
		$this->modLibrary->stmInit();
		
		//-----------------------------------------
		// Open close?
		//-----------------------------------------
		
		if ( $mm_data['topic_state'] != 'leave' )
		{
			if ( $mm_data['topic_state'] == 'close' )
			{
				$this->modLibrary->stmAddClose();
			}
			else if ( $mm_data['topic_state'] == 'open' )
			{
				$this->modLibrary->stmAddOpen();
			}
		}
		
		//-----------------------------------------
		// pin no-pin?
		//-----------------------------------------
		
		if ( $mm_data['topic_pin'] != 'leave' )
		{
			if ( $mm_data['topic_pin'] == 'pin' )
			{
				$this->modLibrary->stmAddPin();
			}
			else if ( $mm_data['topic_pin'] == 'unpin' )
			{
				$this->modLibrary->stmAddUnpin();
			}
		}
		
		//-----------------------------------------
		// Approve / Unapprove
		//-----------------------------------------
		
		if ( $mm_data['topic_approve'] )
		{
			if ( $mm_data['topic_approve'] == 1 )
			{
				$this->modLibrary->stmAddApprove();
			}
			else if ( $mm_data['topic_approve'] == 2 )
			{
				$this->modLibrary->stmAddUnapprove();
			}
		}
		
		//-----------------------------------------
		// Update what we have so far...
		//-----------------------------------------
		
		$this->modLibrary->stmExec( $this->tids );
		
		//-----------------------------------------
		// Topic title (1337 - I am!)
		//-----------------------------------------

		if( $mm_data['topic_title_st'] OR $mm_data['topic_title_end'] )
		{
			$this->DB->update( 'topics', 'title=' . $this->DB->buildConcat( array( 
																							array( $mm_data['topic_title_st'], 'string' ), 
																							array( 'title' ), 
																							array( $mm_data['topic_title_end'], 'string' ) 
																				)	 ),
								"tid IN(" . implode( ',', $this->tids ) . ")", false, true
							);
		}

		//-----------------------------------------
		// Add reply?
		//-----------------------------------------
		
		if ( $mm_data['topic_reply'] and $mm_data['topic_reply_content'] )
		{
	   		$move_ids	= array();
	   		
	   		foreach( $this->tids as $tid )
	   		{
	   			$move_ids[]	= array( $tid, $this->forum['id'] );
	   		}
	   		
			$this->modLibrary->auto_update = FALSE;  // Turn off auto forum re-synch, we'll manually do it at the end
			
			IPSText::getTextClass('bbcode')->parse_smilies			= 1;
			IPSText::getTextClass('bbcode')->parse_bbcode			= 1;
			IPSText::getTextClass( 'bbcode' )->parsing_section		= 'topics';
			
			$this->modLibrary->topicAddReply( 
											 IPSText::getTextClass('bbcode')->preDbParse( $mm_data['topic_reply_content'] )
											, $move_ids
											, $mm_data['topic_reply_postcount']
										   );
		}
		
		//-----------------------------------------
		// Move topic?
		//-----------------------------------------
		
		if ( $mm_data['topic_move'] )
		{
			//-----------------------------------------
			// Move to forum still exist?
			//-----------------------------------------
			
			$r = $this->registry->class_forums->allForums[ $mm_data['topic_move'] ];

			if( $r['id'] )
			{
				if ( $r['sub_can_post'] != 1 )
				{
					$this->DB->update( 'topic_mmod', array( 'topic_move' => 0 ), "mm_id=" . $mm_id );
				}
				else
				{
					if ( $r['id'] != $this->forum['id'] )
					{
						$this->modLibrary->topicMove( $this->tids, $this->forum['id'], $r['id'], $mm_data['topic_move_link'] );
						$this->modLibrary->forumRecount( $r['id'] );
					}
				}
			}
			else
			{
				$this->DB->update( 'topic_mmod', array( 'topic_move' => 0 ), "mm_id=" . $mm_id );
			}
		}
		
		//-----------------------------------------
		// Recount root forum
		//-----------------------------------------
		
		$this->registry->class_forums->allForums[ $this->forum['id'] ]['_update_deletion'] = 1;
		
		$this->modLibrary->forumRecount( $this->forum['id'] );
		
		$this->_addModeratorLog( sprintf( $this->lang->words['acp_multi_mod'], $mm_data['mm_title'], $this->forum['name'] ) );
	}
	
	/**
	 * Grabs post ids for multi-mod
	 *
	 * @access	private
	 * @param	string		Field to look in
	 * @return	void		Cleaned array of post ids
	 */
	private function _getIds( $field='selectedtids' )
	{
		$ids = array();
 		$ids = is_array( $this->request[ $field ] ) ? $this->request[ $field ] : explode( ',', $this->request[ $field ] );
		
		if ( count( $ids ) < 1 )
 		{
 			$this->_showError( 'mod_no_tid', 103118 );
 		}
 		
 		$ids = IPSLib::cleanIntArray( $ids );
 		$ids = array_diff( $ids, array(0) );

 		return $ids;
	}
	
	/**
	 * Takes an input url and extracts the topic id
	 *
	 * @access	private
	 * @return	integer		Topic id
	 */
	private function _getTidFromUrl()
	{
		/* Try to intval the url */
		if( ! intval( $this->request['topic_url'] ) )
		{
			/* Friendly URL */
			if( $this->settings['use_friendly_urls'] )
			{
				$templates	= IPSLib::buildFurlTemplates();
				
				preg_match( $templates['showtopic']['in']['regex'], $this->request['topic_url'], $match );
				$old_id = intval( trim( $match[1] ) );
			}
			/* Normal URL */
			else
			{
				preg_match( "/(\?|&amp;)(t|showtopic)=(\d+)($|&amp;)/", $this->request['topic_url'], $match );
				$old_id = intval( trim( $match[3] ) );
			}			
		}
		else
		{
			$old_id = intval($this->request['topic_url']);
		}

		return $old_id;
	}

	/**
	 * Show an error as a result of a moderator request
	 * Abstracted so that we can expand this, i.e. for logging
	 *
	 * @access	private
	 * @param 	string		Error message language key
	 * @param 	integer		Error code
	 * @return	void		Outputs error screen
	 */
	private function _showError( $msg = 'moderate_no_permission', $level = 10367 )
	{
		$this->registry->output->showError( $msg, $level, true );
	}
	
	/**
	 * Shortcut for adding a moderator log
	 *
	 * @access	private
	 * @param 	string		Error message language key
	 * @return	void
	 */
	private function _addModeratorLog( $title = 'unknown' )
	{
		$this->modLibrary->addModerateLog( $this->request['f'], $this->request['t'], $this->request['p'], $this->topic['title'], $title );
	}
	
	/**
	 * Generic permission checking
	 *
	 * @access	private
	 * @param 	string		Key to check from 'moderator' aray
	 * @param	string		[Optional] error language key to use if check fails
	 * @param	string		[Optional] pass if "trash_inuse" is set
	 * @param	boolean		Return (vs output)
	 * @return	mixed		Boolean true | Displays error screen
	 */
	private function _genericPermissionCheck( $key='', $error='moderate_no_permission', $checkTrash=0, $return=false )
	{
		$pass = 0;
	
		if ( $this->memberData['g_is_supmod'] == 1 )
		{
			$pass = 1;
		}
		else if ( $key AND $this->moderator[ $key ] == 1 )
		{
			$pass = 1;
		}
		else if( $checkTrash AND $this->trash_inuse == true )
		{
			$pass = 1;
		}

		if ( $pass == 0 )
		{
			if( $return )
			{
				return false;
			}

			$this->_showError( $error, 103119 );
		}
		
		return true;
	}
	
	/**
	 * Setup the trash can forum
	 *
	 * @access	private
	 * @author	Brandon Farber
	 * @return	void
	 */
	private function _takeOutTrash()
	{
		if ( $this->settings['forum_trash_can_enable'] and $this->settings['forum_trash_can_id'] )
		{
			if ( $this->registry->class_forums->allForums[ $this->settings['forum_trash_can_id'] ]['sub_can_post'] )
			{
				if ( $this->memberData['g_access_cp'] )
				{
					$this->trash_forum = $this->settings['forum_trash_can_use_admin'] ? $this->settings['forum_trash_can_id'] : 0;
				}
				else if ( $this->memberData['g_is_supmod'] )
				{
					$this->trash_forum = $this->settings['forum_trash_can_use_smod'] ? $this->settings['forum_trash_can_id'] : 0;
				}
				else if ( $this->memberData['is_mod'] )
				{
					$this->trash_forum = $this->settings['forum_trash_can_use_mod'] ? $this->settings['forum_trash_can_id'] : 0;
				}
				else
				{
					$this->trash_forum = $this->settings['forum_trash_can_id'];
				}
			}
		}
	}
	
	/**
	 * Check the input
	 * 1) Checks against CSRF attacks
	 * 2) Checks submissions to ensure auth_key is valid
	 * 3) Determines if you have permission to access the moderator action
	 * 4) Sets up $this->forum and $this->moderator
	 *
	 * @access	private
	 * @author	Brandon Farber
	 * @return	void
	 */
	private function _setupAndCheckInput()
	{
		$post_array			= array( '04', '02', '20', '22', 'resync', 'prune_start', 'prune_finish', 'prune_move', 'editmember' );
		$not_forum_array	= array( 'editmember', 'doeditmember' );
		
		//-----------------------------------------
		// Make sure this is a POST request
		//-----------------------------------------
		
		if ( ! in_array( $this->request['do'], $post_array ) )
		{
			if ( !$this->request['auth_key'] ) // Changed from $_POST to enable linking to mod functions
			{
				$this->_showError( 'mod_no_authorization_key', 5030 );
			}
		}
		
		//-----------------------------------------
		// Nawty, Nawty!
		//-----------------------------------------
		
		if ( $this->request['do'] != '02' and $this->request['do'] != '05' )
		{
			if ($this->request['auth_key'] != $this->member->form_hash )
			{
				$this->_showError( 'mod_no_authorization_key', 5031 );
			}
		}

		//-----------------------------------------
		// Check the input
		//-----------------------------------------
		
		if ( ! in_array( $this->request['do'], $not_forum_array ) )
		{
			//-----------------------------------------
			// t
			//-----------------------------------------
			
			if ( $this->request['t'] )
			{
				$this->request['t'] = intval($this->request['t']);
				
				if ( ! $this->request['t'] )
				{
					$this->_showError( 'mod_bad_tid', 5032 );
				}
				else
				{
					$this->topic = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'topics', 'where' => 'tid=' . $this->request['t'] ) );

					if ( empty($this->topic['tid']) )
					{
						$this->_showError( 'mod_no_tid', 103120 );
					}
					
					if ( $this->request['f'] AND ( $this->topic['forum_id'] != $this->request['f'] ) ) 
					{ 
						$this->_showError( 'mod_no_tid', 3033 );
					}
				}
			}
			
			//-----------------------------------------
			// p
			//-----------------------------------------
			
			if ( $this->request['p'] )
			{
				$this->request['p'] = intval($this->request['p']);
				
				if ( !$this->request['p'] )
				{
					$this->_showError( 'mod_bad_pid', 5033 );
				}
			}
			
			//-----------------------------------------
			// F?
			//-----------------------------------------
			
			$this->request['f'] =  intval($this->request['f']);
			
			if ( !$this->request['f'] AND $this->request['do'] != 'setAsSpammer' )
			{
				$this->_showError( 'mod_bad_fid', 4030 );
			}
			
			$this->request['st'] =  intval($this->request['st'] > 0 ? intval($this->request['st']) : 0);
			
			//-----------------------------------------
			// Get the forum info based on the forum ID,
			//-----------------------------------------
			
			if( $this->request['f'] )
			{
				$this->forum = $this->registry->class_forums->allForums[ $this->request['f'] ];
			}

			//-----------------------------------------
			// Are we a moderator?
			//-----------------------------------------
			
			if ( $this->request['f'] AND isset( $this->memberData['forumsModeratorData'][ $this->request['f'] ]) AND $this->memberData['forumsModeratorData'][ $this->request['f'] ] )
			{
				$this->moderator = $this->memberData['forumsModeratorData'][ $this->request['f'] ];
			}
		}
	}
	
	/**
	 * Reset the moderator array to be sure we check correct permissions
	 *
	 * @access	private
	 * @param	integer		Forum ID
	 * @author	Brandon Farber
	 * @return	void
	 */
	private function _resetModerator( $forumId )
	{
		$this->moderator	= array();

		//-----------------------------------------
		// Are we a moderator?
		//-----------------------------------------
		
		if ( isset( $this->memberData['forumsModeratorData'][ $forumId ]) AND $this->memberData['forumsModeratorData'][ $forumId ] )
		{
			$this->moderator = $this->memberData['forumsModeratorData'][ $forumId ];
		}
	}
	
}