<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Member management
 * Last Updated: $Date: 2009-08-25 10:43:01 -0400 (Tue, 25 Aug 2009) $
 *
 * @author 		$Author: josh $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Members
 * @link		http://www.
 * @since		1st march 2002
 * @version		$Revision: 5043 $
 *
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}


class admin_members_members_members extends ipsCommand
{
	/**
	 * Skin object
	 *
	 * @access	private
	 * @var		object			Skin templates
	 */
	private $html;
	
	/**
	 * Shortcut for url
	 *
	 * @access	private
	 * @var		string			URL shortcut
	 */
	private $form_code;
	
	/**
	 * Shortcut for url (javascript)
	 *
	 * @access	private
	 * @var		string			JS URL shortcut
	 */
	private $form_code_js;

	/**
	 * Trash can forum id
	 *
	 * @access	private
	 * @var		integer			Trash can forum
	 */
	private $trash_forum		= 0;

	/**
	 * Main class entry point
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Load skin
		//-----------------------------------------
		
		$this->html			= $this->registry->output->loadTemplate('cp_skin_member');
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=members&amp;section=members';
		$this->form_code_js	= $this->html->form_code_js	= 'module=members&section=members';
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_member' ) );

		///-----------------------------------------
		// What to do...
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'member_edit':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'member_edit' );
				$this->_memberDoEdit();
			break;

			case 'unsuspend':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'member_suspend' );
				$this->_memberUnsuspend();
			break;

			case 'add':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'member_add' );
				$this->_memberAddForm();
			break;
			
			case 'doadd':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'member_add' );
				$this->_memberDoAdd();
			break;

			case 'doprune':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'member_prune' );
				$this->_memberDoPrune();
			break;
			
			case 'domove':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'member_move' );
				$this->_memberDoMove();
			break;
			
			case 'banmember':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'member_suspend' );
				$this->_memberSuspendStart();
			break;
			
			case 'ban_member':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'member_ban' );
				$this->_memberBanDo();
			break;
				
			case 'dobanmember':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'member_suspend' );
				$this->_memberSuspendDo();
			break;
			
			case 'toggleSpam':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'member_ban' );
				$this->_memberToggleSpam();
			break;
			
			case 'viewmember':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'member_edit' );
				$this->_memberView();
			break;

			case 'member_delete':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'member_delete' );
				$this->_memberDelete();
			break;
			
			case 'new_photo':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'member_photo' );
				$this->_memberNewPhoto();
			break;
			
			case 'view_rep':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'member_edit' );
				$this->_viewMemberRep();
			break;
			
			case 'remoteAvatarRedirect':
				$this->_viewMemberAvatar();
			break;
			
			case 'members_overview':
			case 'members_list':
			default:
				$this->_memberList();
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}
	
	/**
	 * View remote avatar without passing referrer
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function _viewMemberAvatar()
	{
		$id		= intval($this->request['member_id']);
		
		$this->registry->output->silentRedirect( "{$this->settings['board_url']}/interface/board/avatar_viewer.php?id={$id}" );
	}
	
	/**
	 * View a member's reputation level
	 *
	 * @access	private
	 * @return	void
	 */
	private function _viewMemberRep()
	{
		/* ID */
		$id   = intval( $this->request['id'] );
		$type = $this->request['type'] == 'given' ? 'given' : 'received';
		
		/* Get Name */
		$user = $this->DB->buildAndFetch( array( 'select' => 'members_display_name', 'from' => 'members', 'where' => "member_id={$id}" ) );
		
		/* Count the reps they've gotten */
		if( $type == 'received' )
		{
			$total = $this->DB->buildAndFetch( array(
														'select'   => 'count(*) as reps',
														'from'     => array( 'reputation_index' => 'r' ),
														'where'    => 'p.author_id=' . $id,
														'add_join' => array(
																			array(
																					'from'  => array( 'posts' => 'p' ),
																					'where' => 'r.type="pid" AND r.type_id=p.pid',
																					'type'  => 'left'
																				)	
																		)
														
											)	);
		}
		else
		{
			$total = $this->DB->buildAndFetch( array( 'select' => 'count(*) as reps', 'from' => 'reputation_index', 'where' => 'member_id=' . $id ) );
		}
		
		/* Pagination */
		$perpage = 25;
		$st      = intval( $this->request['st'] );
		
		$pages = $this->registry->output->generatePagination( array( 
																	'totalItems'         => $total['reps'],
																	'itemsPerPage'       => $perpage,
																	'currentStartValue'  => $st,
																	'baseUrl'            => "{$this->settings['base_url']}{$this->form_code}&do=view_rep&id={$id}&type={$type}",
															)		);
															
		/* Query the reps */
		if( $type == 'received' )
		{
			$title = "{$this->lang->words['rep_received']} {$user['members_display_name']}";
			
			$this->DB->build( array(
										'select'   => 'r.*',
										'from'     => array( 'reputation_index' => 'r' ),
										'where'    => 'p.author_id=' . $id,
										'limit'    => array( $st, $perpage ),
										'order'    => 'r.rep_date DESC',
										'add_join' => array(
															array(
																	'from'   => array( 'posts' => 'p' ),
																	'where'  => 'r.type="pid" AND r.type_id=p.pid',
																	'type'   => 'left'
																),
															array(
																	'select' => 't.title, t.tid',
																	'from'   => array( 'topics' => 't' ),
																	'where'  => 'p.topic_id=t.tid',
																	'type'   => 'left',																	
																),
															array(
																	'select' => 'm.members_display_name',
																	'from'   => array( 'members' => 'm' ),
																	'where'  => 'r.member_id=m.member_id'
																)
														)
										
							)	);
			$this->DB->execute();
		}
		else
		{
			$title = "{$this->lang->words['rep_given']} {$user['members_display_name']}";
			
			$this->DB->build( array(
										'select'   => 'r.*',
										'from'     => array( 'reputation_index' => 'r' ),
										'where'    => 'r.member_id=' . $id,
										'limit'    => array( $st, $perpage ),
										'order'    => 'r.rep_date DESC',
										'add_join' => array(
															array(
																	'from'   => array( 'posts' => 'p' ),
																	'where'  => 'r.type="pid" AND r.type_id=p.pid',
																	'type'   => 'left'
																),
															array(
																	'select' => 't.title, t.tid',
																	'from'   => array( 'topics' => 't' ),
																	'where'  => 'p.topic_id=t.tid',
																	'type'   => 'left'
																),
															array(
																	'select' => 'm.members_display_name',
																	'from'   => array( 'members' => 'm' ),
																	'where'  => 'p.author_id=m.member_id',
																	'type'   => 'left'
																)
														)
										
							)	);
			$this->DB->execute();			
		}
														
		/* Build Output Rows */
		$rows = array();
		
		while( $r = $this->DB->fetch() )
		{
			/* Format */
			$r['_date'] = $this->registry->class_localization->getDate( $r['rep_date'], 'LONG' );
			
			/* Add to output */	
			$rows[] = $r;
		}
		
		/* Output */
		$this->registry->output->html .= $this->html->memberRepLog( $title, $rows, $pages );
		$this->registry->output->printPopupWindow();
	}
	
	/**
	 * Determines if we should show the admin restrictions form stuff
	 *
	 * @access	private
	 * @param	array		Member information
	 * @param	array		Old member groups [primary and secondary]
	 * @return	mixed		False, or HTML [Outputs to screen]
	 * @author	Brandon Farber
	 */
	private function _showAdminForm( $member, $old_mgroups )
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------

		$groups			= array( $member['member_group_id'] );
		$old_mgroups	= is_array($old_mgroups) ? $old_mgroups : array();
		$is_admin		= false;
		$just_now		= false;
		$admins			= array();
		
		if( $member['mgroup_others'] )
		{
			$groups	= array_merge( $groups, explode( ',', IPSText::cleanPermString( $member['mgroup_others'] ) ) );
		}
		
		//-----------------------------------------
		// Are they an admin?
		//-----------------------------------------
		
		foreach( $groups as $group_id )
		{
			if( $this->caches['group_cache'][ $group_id ]['g_access_cp'] )
			{
				$is_admin				= true;
				$admins[ $group_id ]	= false;
			}
		}
		
		if( !$is_admin )
		{
			return false;
		}
		
		//-----------------------------------------
		// Were they before?
		//-----------------------------------------
		
		foreach( $admins as $admin_group_id => $restricted )
		{
			if( !in_array( $admin_group_id, $old_mgroups ) )
			{
				$just_now	= true;
			}
		}
		
		if( !$just_now )
		{
			return false;
		}
		
		//-----------------------------------------
		// Do they already have restrictions?
		//-----------------------------------------
		
		$test = $this->DB->buildAndFetch( array( 'select' => 'row_id', 'from' => 'admin_permission_rows', 'where' => "row_id_type='member' AND row_id=" . $member['member_id'] ) );
		
		if( $test['row_id'] )
		{
			return false;
		}
		
		//-----------------------------------------
		// Determine if they have group restrictions
		//-----------------------------------------

		$this->DB->build( array( 'select' => '*', 'from' => 'admin_permission_rows', 'where' => "row_id_type='group' AND row_id IN(" . implode( ',', array_keys( $admins ) ) . ")" ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$admins[ $r['row_id'] ] = true;
		}
		
		//-----------------------------------------
		// And show teh form.. o.O.o <-- three eyed monster from Lilo and Stitch
		//-----------------------------------------

		$this->registry->output->html .= $this->html->memberAdminConfirm( $member, $admins );
		
		return true;
	}
		
	/**
	 * Uploads a new photo for the member [process]
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _memberNewPhoto()
	{
		if ( !$this->request['member_id'] )
		{
			$this->registry->output->showError( $this->lang->words['m_specify'], 11224 );
		}
		
		$member = IPSMember::load( $this->request['member_id'] );
		
		//-----------------------------------------
		// Allowed to upload pics for administrators?
		//-----------------------------------------
		
		if( $member['g_access_cp'] AND !$this->registry->getClass('class_permissions')->checkPermission( 'member_photo_admin' ) )
		{
			$this->registry->output->global_message = $this->lang->words['m_noupload'];
			$this->_memberView();
			return;
		}
		
		$status = IPSMember::getFunction()->uploadPhoto( intval($this->request['member_id']) );

		if( $status['status'] == 'fail' )
		{
			switch( $status['error'] )
			{
				case 'upload_failed':
					$this->registry->output->showError( $this->lang->words['m_upfailed'], 11225 );
				break;
				
				case 'invalid_file_extension':
					$this->registry->output->showError( $this->lang->words['m_invfileext'], 11226 );
				break;
				
				case 'upload_to_big':
					$this->registry->output->showError( $this->lang->words['m_thatswhatshesaid'], 11227 );
				break;
			}
		}
		else
		{
			$bwOptions	= IPSBWOptions::thaw( $member['fb_bwoptions'], 'facebook' );
			$bwOptions['fbc_s_pic']	= 0;

			IPSMember::save( $this->request['member_id'], array( 'extendedProfile' => array( 'pp_main_photo'   => $status['final_location'],
													  				   	 	'pp_main_width'		=> $status['final_width'],
																		   	'pp_main_height'	=> $status['final_height'],
																			'pp_thumb_photo'	=> $status['t_final_location'],
																			'pp_thumb_width'	=> $status['t_final_width'],
																			'pp_thumb_height'	=> $status['t_final_height'],
																			'fb_photo'			=> '',
																			'fb_photo_thumb'	=> '',
																			'fb_bwoptions'		=> IPSBWOptions::freeze( $bwOptions, 'facebook' )
																		 ) ) );
																		 			
			//-----------------------------------------
			// Redirect
			//-----------------------------------------
	
			$this->registry->output->doneScreen( $this->lang->words['m_photoupdated'], $this->lang->words['m_search'], "{$this->form_code}&amp;do=viewmember&amp;member_id={$this->request['member_id']}", "redirect" );
		}
	}
	
	/**
	 * View a member's details
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 * @todo 	[Future] If PM disabled, remove the PM-related settings
	 * @todo 	[Future] Settings: joined, email_full, dst_in_use, view_prefs, coppa_user, auto_track, ignored_users, members_auto_dst, 
	 * 				members_editor_choice, members_created_remote, members_profile_views, failed_logins, failed_login_count, pp_profile_views,
	 *				fb_photo, fb_photo_thumb, fb_status
	 */
	private function _memberView()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$member_id	= intval( $this->request['member_id'] );
		$member		= array();
		$sidebar	= array();
		$blocks		= array();

		//-----------------------------------------
		// Get member data
		//-----------------------------------------
		
		$member = IPSMember::load( $member_id, 'all' );

		//-----------------------------------------
		// Allowed to ban administrators?
		//-----------------------------------------
		
		if( $member['member_id'] != $this->memberData['member_id'] AND $member['g_access_cp'] AND !$this->registry->getClass('class_permissions')->checkPermission( 'member_edit_admin') )
		{
			
			$this->registry->output->global_message = $this->lang->words['m_editadmin'];
			$this->_memberList();
			return;
		}

		$member['custom_fields'] = array();
		
		//-----------------------------------------
		// Just a safeguard to prevent admin mistake
		//-----------------------------------------
		
		if( !$member['member_group_id'] )
		{
			$member['member_group_id']	= $this->settings['member_group'];
		}

		//-----------------------------------------
		// Got a member?
		//-----------------------------------------
	
		if ( ! $member['member_id'] )
		{
			$this->registry->output->global_error = $this->lang->words['m_noid'];
			$this->_memberList();
			return;
		}
		
		if( $this->request['trigger'] )
		{
			if( $this->_showAdminForm( $member, explode( ',', $this->request['trigger'] ) ) )
			{
				// Decided to just show the message above the edit member page...works nicely without having to be a separate page
				// return;
			}
		}
		
		//-----------------------------------------
		// Ok? Load interface and child classes
		//-----------------------------------------
		
		IPSLib::loadInterface( 'admin/member_form.php' );
		
		foreach( ipsRegistry::$applications as $app_dir => $app_data )
		{
			if ( ! IPSLib::appIsInstalled( $app_dir ) )
			{
				continue;
			}
			
			if ( file_exists( IPSLib::getAppDir(  $app_dir ) . '/extensions/admin/member_form.php' ) )
			{
				require_once( IPSLib::getAppDir(  $app_dir ) . '/extensions/admin/member_form.php' );
				$_class  = 'admin_member_form__' . $app_dir;
				$_object = new $_class( $this->registry );
				
				$sidebar[ $app_dir ] = $_object->getSidebarLinks( $member );
				
				$data = $_object->getDisplayContent( $member );
				$blocks['area'][ $app_dir ]  = $data['content'];
				$blocks['tabs'][ $app_dir ]  = $data['tabs'];
			}
		}
		
		//-----------------------------------------
		// Format Member
		//-----------------------------------------

		$member['_joined']				= ipsRegistry::getClass( 'class_localization')->getDate( $member['joined'], 'LONG' );

		$member							= IPSMember::buildDisplayData( $member );

    	//-----------------------------------------
		// Editors
		//-----------------------------------------
		
		$sig_editor 						= $member['signature'];
		$ame_editor							= $member['pp_about_me'];

		if ( IPSText::getTextClass('editor')->method == 'rte' )
		{
			$sig_editor				= IPSText::getTextClass('bbcode')->convertForRTE( $sig_editor );
			$ame_editor				= IPSText::getTextClass('bbcode')->convertForRTE( $ame_editor );
		}
		else
		{
			IPSText::getTextClass('bbcode')->parse_html		= $this->settings['sig_allow_html'];
			IPSText::getTextClass('bbcode')->parse_nl2br	= 1;
			IPSText::getTextClass('bbcode')->parse_smilies	= 0;
			IPSText::getTextClass('bbcode')->parse_bbcode	= $this->settings['sig_allow_ibc'];
			IPSText::getTextClass('bbcode')->parsing_section		= 'signatures';
			
			$sig_editor				= IPSText::getTextClass('bbcode')->preEditParse( $sig_editor );
			
			IPSText::getTextClass('bbcode')->parse_html		= $this->settings['aboutme_html'];
			IPSText::getTextClass('bbcode')->parse_nl2br	= 1;
			IPSText::getTextClass('bbcode')->parse_smilies	= $this->settings['aboutme_emoticons'];
			IPSText::getTextClass('bbcode')->parse_bbcode	= $this->settings['aboutme_bbcode'];
			IPSText::getTextClass('bbcode')->parsing_section		= 'aboutme';
			
			$ame_editor				= IPSText::getTextClass('bbcode')->preEditParse( $ame_editor );
		}
		
		$member['signature_editor']	= IPSText::getTextClass('editor')->showEditor( $sig_editor, 'signature' );
		$member['aboutme_editor']	= IPSText::getTextClass('editor')->showEditor( $ame_editor, 'aboutme' );

    	//-----------------------------------------
		// Custom fields
		//-----------------------------------------
		
		require_once( IPS_ROOT_PATH . 'sources/classes/customfields/profileFields.php' );
		$custom_fields = new customProfileFields();
		
		$custom_fields->member_data = $member;
		$custom_fields->initData( 'edit' );
		$custom_fields->parseToEdit();
		
		$member['custom_fields'] = array();
		if ( count( $custom_fields->out_fields ) )
		{
			foreach( $custom_fields->out_fields as $id => $data )
	    	{
	    		if ( ! $data )
	    		{
	    			$data = $this->lang->words['gbl_no_info'];
	    		}
	    		
				$member['custom_fields'][ $id ] = array( 'name' => $custom_fields->field_names[ $id ], 'data' => $data );
	    	}
		}
	
		//-----------------------------------------
		// Get it printed!
		//-----------------------------------------
		
		$this->registry->output->extra_nav[] = array( '', 'Viewing Member ' . $member['members_display_name'] );

		$this->registry->output->html .= $this->html->member_view( $member, $blocks, $sidebar );
	}
	
	/**
	 * Toggle member spam [process]
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _memberToggleSpam()
	{
		/* INIT */
		$toSave = array();
		$this->request['member_id'] =  intval($this->request['member_id']);
		
		if ( !$this->request['member_id'] )
		{
			$this->registry->output->showError( $this->lang->words['m_specify'], 11228 );
		}
		
		$member = IPSMember::load( $this->request['member_id'] );

		if ( ! $member['member_id'] )
		{
			$this->registry->output->showError( $this->lang->words['m_noid'], 11229 );
		}
		
		//-----------------------------------------
		// Allowed to spam administrators?
		//-----------------------------------------
		
		if( $member['g_access_cp'] AND !$this->registry->getClass('class_permissions')->checkPermission( 'member_ban_admin') )
		{
			$this->registry->output->global_message = $this->lang->words['m_banadmin'];
			$this->_memberView();
			return;
		}
		
		/* Load mod lib */
		require( IPSLib::getAppDir( 'forums' ) . '/sources/classes/moderate.php');
		$this->modLibrary = new moderatorLibrary( $this->registry );
		
		/* Spam or not ? */
		if ( $member['bw_is_spammer'] )
		{
			$toSave['core']['bw_is_spammer']      = 0;
			$toSave['core']['restrict_post']      = 0;
			$toSave['core']['members_disable_pm'] = 0;
			
			/* Flag them as a spammer */
			IPSMember::save( $member['member_id'], $toSave );
		}
		else
		{
			$toSave['core']['bw_is_spammer']      = 1;
			
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
						$this->modLibrary->toggleApproveMemberContent( $member['member_id'], FALSE, 'all', intval( $this->settings['spm_post_days'] ) * 24 );
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
			
			/* Send Spammer to Spam Service */
			if( $this->settings['spam_service_send_to_ips'] && $this->settings['spam_service_api_key'] )
			{
				IPSMember::querySpamService( $member['email'], $member['ip_address'], 'markspam' );
			}
			
			/* Flag them as a spammer */
			IPSMember::save( $member['member_id'], $toSave );
		}
		
		//-----------------------------------------
		// Redirect
		//-----------------------------------------

		ipsRegistry::getClass('adminFunctions')->saveAdminLog(sprintf( $this->lang->words['t_log_spam'], $member['members_display_name'] ) );

		$this->registry->output->doneScreen($this->lang->words['t_log_spam'], $this->lang->words['m_search'], "{$this->form_code}&amp;do=viewmember&amp;member_id={$member['member_id']}", "redirect" );
	}
	
	/**
	 * Ban a member [process]
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _memberBanDo()
	{
		$this->request['member_id'] =  intval($this->request['member_id']);
		
		if ( !$this->request['member_id'] )
		{
			$this->registry->output->showError( $this->lang->words['m_specify'], 11228 );
		}
		
		$member = IPSMember::load( $this->request['member_id'] );

		if ( ! $member['member_id'] )
		{
			$this->registry->output->showError( $this->lang->words['m_noid'], 11229 );
		}
		
		//-----------------------------------------
		// Allowed to ban administrators?
		//-----------------------------------------
		
		if( $member['g_access_cp'] AND !$this->registry->getClass('class_permissions')->checkPermission( 'member_ban_admin') )
		{
			$this->registry->output->global_message = $this->lang->words['m_banadmin'];
			$this->_memberView();
			return;
		}
		
		//-----------------------------------------
		// Check ban settings...
		//-----------------------------------------

		$ban_filters 	= array( 'email' => array(), 'name' => array(), 'ip' => array() );
		$email_banned	= false;
		$ip_banned		= array();
		$name_banned	= false;
		
		//-----------------------------------------
		// Grab existing ban filters
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'banfilters' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$ban_filters[ $r['ban_type'] ][] = $r['ban_content'];
		}
		
		//-----------------------------------------
		// Check name and email address
		//-----------------------------------------
		
		if( in_array( $member['email'], $ban_filters['email'] ) )
		{
			$email_banned	= true;
		}
		
		if( in_array( $member['name'], $ban_filters['name'] ) )
		{
			$name_banned	= true;
		}
		
		if( $this->request['ban__email'] AND !$email_banned )
		{
			$this->DB->insert( 'banfilters', array( 'ban_type' => 'email', 'ban_content' => $member['email'], 'ban_date' => time() ) );
		}
		else if( !$this->request['ban__email'] AND $email_banned )
		{
			$this->DB->delete( 'banfilters', "ban_type='email' AND ban_content='{$member['email']}'" );
		}
		
		if( $this->request['ban__member'] AND !$member['member_banned'] )
		{
			IPSMember::save( $member['member_id'], array( 'core' => array( 'member_banned' => 1 ) ) );
		}
		else if( !$this->request['ban__member'] AND $member['member_banned'] )
		{
			IPSMember::save( $member['member_id'], array( 'core' => array( 'member_banned' => 0 ) ) );
		}
		
		if( $this->request['ban__name'] AND !$name_banned )
		{
			$this->DB->insert( 'banfilters', array( 'ban_type' => 'name', 'ban_content' => $member['name'], 'ban_date' => time() ) );
		}
		else if( !$this->request['ban__name'] AND $name_banned )
		{
			$this->DB->delete( 'banfilters', "ban_type='name' AND ban_content='{$member['name']}'" );
		}
		
		if( $this->request['ban__note'] AND $this->request['ban__note_field'] )
		{
			//-----------------------------------------
			// Format note
			//-----------------------------------------
		
			$save['wlog_notes']  = "<content>{$this->request['ban__note_field']}</content>";
			$save['wlog_notes'] .= "<mod></mod>";
			$save['wlog_notes'] .= "<post></post>";
			$save['wlog_notes'] .= "<susp></susp>";
		
			$save['wlog_mid']     = $member['member_id'];
			$save['wlog_addedby'] = $this->memberData['member_id'];
			$save['wlog_type']    = 'note';
			$save['wlog_date']    = time();
			
			//-----------------------------------------
			// Enter into warn loggy poos (eeew - poo)
			//-----------------------------------------
		
			$this->DB->insert( 'warn_logs', $save );
		}
		
		//-----------------------------------------
		// Retrieve IP addresses
		//-----------------------------------------
		
		$ip_addresses	= IPSMember::findIPAddresses( $member['member_id'] );

		//-----------------------------------------
		// What about IPs?
		//-----------------------------------------

		if( is_array($ip_addresses) AND count($ip_addresses) )
		{
			foreach( $ip_addresses as $ip_address => $count )
			{
				if( in_array( $ip_address, $ban_filters['ip'] ) )
				{
					if( !$this->request[ 'ban__ip_' . str_replace( '.', '_', $ip_address ) ] )
					{
						$this->DB->delete( 'banfilters', "ban_type='ip' AND ban_content='{$ip_address}'" );
					}
				}
				else
				{
					if( $this->request[ 'ban__ip_' . str_replace( '.', '_', $ip_address ) ] )
					{
						$this->DB->insert( 'banfilters', array( 'ban_type' => 'ip', 'ban_content' => $ip_address, 'ban_date' => time() ) );
					}
				}
			}
		}
		
		if( $this->request['ban__group'] AND $this->request['ban__group_change'] AND $this->request['ban__group'] != $member['member_group_id'] )
		{
			IPSMember::save( $member['member_id'], array( 'core' => array( 'member_group_id' => intval($this->request['ban__group']) ) ) );
		}
		
		//-----------------------------------------
		// Redirect
		//-----------------------------------------

		ipsRegistry::getClass('adminFunctions')->saveAdminLog(sprintf( $this->lang->words['m_bannedlog'], $member['members_display_name'] ) );

		$this->registry->output->doneScreen($this->lang->words['m_banned'], $this->lang->words['m_search'], "{$this->form_code}&amp;do=viewmember&amp;member_id={$member['member_id']}", "redirect" );
	}
	
	/**
	 * Suspend a member [form/confirmation]
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _memberSuspendStart()
	{
		$this->registry->output->extra_nav[] 		= array( '', $this->lang->words['m_suspend'] );
		
		if ( !$this->request['member_id'] )
		{
			$this->registry->output->showError( $this->lang->words['m_specify'], 11230 );
		}
		
		$member = IPSMember::load( intval($this->request['member_id']) );

		if ( ! $member['member_id'] )
		{
			$this->registry->output->showError( $this->lang->words['m_noid'], 11231 );
		}
		
		//-----------------------------------------
		// Allowed to suspend administrators?
		//-----------------------------------------
		
		if( $member['g_access_cp'] AND !$this->registry->getClass('class_permissions')->checkPermission( 'member_suspend_admin') )
		{
			$this->registry->output->global_message = $this->lang->words['m_suspadmin'];
			$this->_memberView();
			return;
		}
					     		
		$ban = IPSMember::processBanEntry( $member['temp_ban'] );
		$ban['contents'] = sprintf( $this->lang->words['m_yoursusp'], $this->settings['board_name'] ) . $this->settings['board_url'] . "/index.php";
		
		$this->registry->output->html = $this->html->memberSuspension( array_merge( $member, $ban ) );
	}
	
	/**
	 * Suspend a member [process]
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _memberSuspendDo()
	{
		$this->request[ 'member_id'] =  intval($this->request['member_id'] );
		
		if ( !$this->request['member_id'] )
		{
			$this->registry->output->showError( $this->lang->words['m_specify'], 11232 );
		}
		
		$member = IPSMember::load( $this->request['member_id'] );

		if ( ! $member['member_id'] )
		{
			$this->registry->output->showError( $this->lang->words['m_noid'], 11233 );
		}
		
		//-----------------------------------------
		// Allowed to suspend administrators?
		//-----------------------------------------
		
		if( $member['g_access_cp'] AND !$this->registry->getClass('class_permissions')->checkPermission( 'member_suspend_admin') )
		{
			$this->registry->output->global_message = $this->lang->words['m_suspadmin'];
			$this->_memberView();
			return;
		}	
		
		//-----------------------------------------
		// Work out end date
		//-----------------------------------------
		
		$this->request[ 'timespan'] =  intval($this->request['timespan'] );
		
		if ( $this->request['timespan'] == "" )
		{
			$new_ban = "";
		}
		else
		{
			$new_ban = IPSMember::processBanEntry( array( 'timespan' => intval($this->request['timespan']), 'unit' => $this->request['units'] ) );
		}
		
		$show_ban = IPSMember::processBanEntry( $new_ban );
			
		//-----------------------------------------
		// Update and show confirmation
		//-----------------------------------------

		IPSMember::save( $member['member_id'], array( 'core' => array( 'temp_ban' => $new_ban ) ) );

		// I say, did we choose to email 'dis member?
		
		if ( $this->request['send_email'] )
		{
			// By golly, we did!

			$msg = trim(IPSText::stripslashes($_POST['email_contents']));
			
			$msg = str_replace( "{membername}", $member['members_display_name']       , $msg );
			$msg = str_replace( "{date_end}"  , ipsRegistry::getClass('class_localization')->getDate( $show_ban['date_end'], 'LONG') , $msg );
			
			IPSText::getTextClass('email')->message	= stripslashes( IPSText::getTextClass('email')->cleanMessage($msg) );
			IPSText::getTextClass('email')->subject	= $this->lang->words['m_acctsusp'];
			IPSText::getTextClass('email')->to		= $member['email'];
			IPSText::getTextClass('email')->sendMail();
		}
		
		//-----------------------------------------
		// Redirect
		//-----------------------------------------

		ipsRegistry::getClass('adminFunctions')->saveAdminLog( sprintf( $this->lang->words['m_susplog'], $member['members_display_name'] ) );

		$this->registry->output->doneScreen($this->lang->words['m_suspended'], $this->lang->words['m_search'], "{$this->form_code}&amp;do=viewmember&amp;member_id={$member['member_id']}", "redirect" );
	}
	
	/**
	 * Unsuspend a member [process]
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _memberUnsuspend()
	{
		if ( !$this->request['member_id'] )
		{
			$this->registry->output->showError( $this->lang->words['m_specify'], 11234 );
		}
		
		$member = IPSMember::load( $this->request['member_id'] );
		
		//-----------------------------------------
		// Allowed to suspend administrators?
		//-----------------------------------------
		
		if( $member['g_access_cp'] AND !$this->registry->getClass('class_permissions')->checkPermission( 'member_suspend_admin') )
		{
			$this->registry->output->global_message = $this->lang->words['m_unsuspadmin'];
			$this->_memberView();
			return;
		}	
		
		if ( $this->request['member_id'] == 'all' )
		{
			$this->DB->update( 'members', array( 'temp_ban' => 0 ) );
			
			ipsRegistry::getClass('adminFunctions')->saveAdminLog( $this->lang->words['m_unsuspall'] );
		
			$msg = $this->lang->words['m_allunsusp'];
			
			//-----------------------------------------
			// Redirect
			//-----------------------------------------
	
			$this->registry->output->doneScreen( $msg, $this->lang->words['m_search'], "{$this->form_code}&amp;do=members_list", "redirect" );
		}
		else
		{
			$mid = intval($this->request['member_id']);
			
			IPSMember::save( $mid, array( 'core' => array( 'temp_ban' => 0 ) ) );
			
			$member = IPSMember::load( $mid );
			
			ipsRegistry::getClass('adminFunctions')->saveAdminLog(sprintf( $this->lang->words['m_unsusplog'], $member['members_display_name'] ) );
		
			$msg = sprintf( $this->lang->words['m_unsuspended'], $member['members_display_name'] );
			
			//-----------------------------------------
			// Redirect
			//-----------------------------------------
	
			$this->registry->output->doneScreen( $msg, $this->lang->words['m_search'], "{$this->form_code}&amp;do=viewmember&amp;member_id={$member['member_id']}", "redirect" );
		}
	}

	/**
	 * Prune members [confirmation]
	 *
	 * @access	private
	 * @param	integer		Number of members to prune
	 * @return	void		[Outputs to screen]
	 */
	private function _memberPruneForm( $count )
	{
		$this->registry->output->extra_nav[] = array( '', $this->lang->words['m_prune'] );
		
		//-----------------------------------------
		// Got members?
		//-----------------------------------------
		
		if ( !$count )
		{
			return;
		}

		$this->registry->output->html .= $this->html->pruneConfirm( $count );
	}
	
	/**
	 * Move members to another group [confirmation]
	 *
	 * @access	private
	 * @param	integer		Number of members to move
	 * @return	void		[Outputs to screen]
	 */
	private function _memberMoveForm( $count )
	{ 
		$this->registry->output->extra_nav[] = array( '', $this->lang->words['m_move'] );
		
		//-----------------------------------------
		// Got members?
		//-----------------------------------------
		
		if ( !$count )
		{
			return;
		}

		$this->registry->output->html .= $this->html->moveConfirm( $count );
	}

	/**
	 * Prune members [process]
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 * @todo 	[Future] Centralize SQL query formatting to a single method
	 */
	private function _memberDoPrune()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$data		= $this->_generateFilterBoxes();
		$_sql		= array();
		$ids		= array();
		$names		= array();
		
		//-----------------------------------------
		// Allowed to prune administrators?
		//-----------------------------------------
		
		if( !$this->registry->getClass('class_permissions')->checkPermission( 'member_prune_admin') )
		{
			$admin_group_ids = array();
			
			foreach( $this->cache->getCache( 'group_cache' ) as $group )
			{
				if( $group['g_access_cp'] )
				{
					$admin_group_ids[] = $group['g_id'];
					
					$_sql[] = "m.mgroup_others NOT LIKE '%," . $group['g_id'] . ",%'";
				}
			}
			
			$_sql[] = "m.member_group_id NOT IN(" . implode( ',', $admin_group_ids ) . ")";
		}	

		//-----------------------------------------
		// FILTERS
		//-----------------------------------------
		
		if ( $data['member_contains_text'] )
		{
			$_field = '';
			$_text  = $this->DB->addSlashes( $data['member_contains_text'] );

			switch( $data['member_contains'] )
			{
				default:
				case 'member_id':
					$_field = 'm.member_id';
				break;

				case 'name':
					$_field = 'm.name';
				break;

				case 'members_display_name':
					$_field = 'm.members_display_name';
				break;
				case 'email':
					$_field = 'm.email';
				break;
				case 'ip_address':
					$_field = 'm.ip_address';
				break;
				case 'signature':
					$_field = 'pp.signature';
				break;
			}

			switch( $data['member_contains_type'] )
			{
				default:
				case 'contains':
					$_sql[] = $_field . " LIKE '%" . $_text . "%'";
				break;
				case 'begins':
					$_sql[] = $_field . " LIKE '" . $_text . "%'";
				break;
				case 'ends':
					$_sql[] = $_field . " LIKE '%" . $_text . "'";
				break;
				case 'equals':
					$_sql[] = $_field . " = '" . $_text . "'";
				break;
			}
		}

		if ( $data['member_type'] )
		{
			switch( $data['member_type'] )
			{
				case 'suspended':
					$_sql[] = "m.temp_ban > 0";
				break;
				case 'notsuspended':
					$_sql[] = "( m.temp_ban < 1 or m.temp_ban='' or m.temp_ban " . $this->DB->buildIsNull( true ) . " )";
				break;
			}
		}
		
		/* Banned status */
		if ( $data['banned_type'] )
		{
			switch( $data['banned_type'] )
			{
				case 'banned':
					$_sql[] = "m.member_banned=1";
				break;
				case 'notbanned':
					$_sql[] = "m.member_banned=0";
				break;
			}
		}

		/* Spam status */
		if ( $data['spam_type'] )
		{
			switch( $data['spam_type'] )
			{
				case 'spam':
					$_sql[] = IPSBWOptions::sql( 'bw_is_spammer', 'm.members_bitoptions', 'members', 'global', 'has' );
				break;
				case 'notspam':
					$_sql[] = "NOT (" . IPSBWOptions::sql( 'bw_is_spammer', 'm.members_bitoptions', 'members', 'global', 'has' ) . ")";
				break;
			}
		}

		if ( $data['primary_group'] )
		{
			$_sql[] = "m.member_group_id=" . intval( $data['primary_group'] );
		}

		if ( $data['secondary_group'] )
		{
			$_sql[] = "( m.mgroup_others LIKE '%," . $data['secondary_group'] . ",%' OR " .
					  "m.mgroup_others LIKE '" . $data['secondary_group'] . ",%' OR " .
					  "m.mgroup_others LIKE '%," . $data['secondary_group'] . "' OR " .
					  "m.mgroup_others='" . $data['secondary_group'] . "' )";
		}
		
		if ( $data['post_count'] AND $data['post_count_type'] )
		{
			$_type	= '';

			if( $data['post_count_type'] == 'gt' )
			{
				$_type	= '>';
			}
			else if( $data['post_count_type'] == 'lt' )
			{
				$_type	= '<';
			}
			else if( $data['post_count_type'] == 'eq' )
			{
				$_type	= '=';
			}

			if( $_type )
			{
				$_sql[] = "m.posts" . $_type . intval( $data['post_count'] );
			}
		}
		
		foreach( array( 'reg', 'post', 'active' ) as $_bit )
		{
			foreach( array( 'from', 'to' ) as $_when )
			{
				$bit = 'date_' . $_bit . '_' . $_when;
				
				if ( $data[ $bit ] )
				{
					list( $month, $day, $year ) = explode( '-', $data[ $bit ] );

					if ( ! checkdate( $month, $day, $year ) )
					{
						$this->registry->output->global_message = sprintf($this->lang->words['m_daterange'], $month, $day, $year );
					}
					else
					{
						$time_int = mktime( 0, 0, 0, $month, $day, $year );
	
						switch( $_bit )
						{
							case 'reg':
								$field = 'joined';
							break;
							case 'post':
								$field = 'last_post';
							break;
							case 'active':
								$field = 'last_activity';
							break;
						}
	
						if ( $_when == 'from' )
						{
							$_sql[] = 'm.' . $field . ' > ' . $time_int;
						}
						else
						{
							$_sql[] = 'm.' . $field . ' < ' . $time_int;
						}
					}
				}
			}
		}
		
		//-----------------------------------------
		// Check we have correct fields
		//-----------------------------------------
		
		switch( $data['order_direction'] )
		{
			case 'asc':
				$order_direction = 'asc';
			break;
			default:
			case 'desc':
				$order_direction = 'desc';
			break;
		}
		
		switch( $data['order_by'] )
		{
			default:
			case 'joined':
				$order_by  = 'm.joined';
			break;
			case 'members_l_username':
				$order_by  = 'm.members_l_username';
			break;
			case 'members_l_display_name':
				$order_by  = 'm.members_l_display_name';
			break;
			case 'email':
				$order_by  = 'm.email';
			break;
		}
		
		//-----------------------------------------
		// Custom fields...
		//-----------------------------------------
		
		if( is_array($data['custom_fields']) AND count($data['custom_fields']) )
		{
			foreach ( $data['custom_fields'] as $id => $value )
	 		{
 				if ( $value )
 				{
 					$_sql[] = 'p.field_' . $id . " LIKE '%" . $value . "%'";
 				}
	 		}
 		}
		
		//-----------------------------------------
		// get 'owt?
		//-----------------------------------------
		
		$real_query = count($_sql) ? implode( " AND ", $_sql ) : '';

		//-----------------------------------------
		// Get the number of results
		//-----------------------------------------
		
		$count = $this->DB->buildAndFetch( array( 'select'	=> 'COUNT(*) as count',
														 'from'		=> array( 'members' => 'm' ),
														 'where'	=> $real_query,
														 'add_join'	=> array( 0 => array( 'from'   => array( 'profile_portal' => 'pp' ),
																						  'where'  => 'pp.pp_member_id=m.member_id',
																						  'type'   => 'left' ),
																			  1 => array( 'from'   => array( 'pfields_content' => 'p' ),
																						  'where'  => 'p.member_id=m.member_id',
																						  'type'   => 'left' ) 
																			) 
												) 		);

		if ( $count['count'] < 1 )
		{
			$this->registry->output->global_message = $this->lang->words['m_noprune'];
			
			// And reset the cookie so we don't get the message on every page view
			ipsRegistry::getClass('adminFunctions')->staffSaveCookie( 'memberFilter', array() );
			
			$this->_memberList();
			return;
		}

		//-----------------------------------------
		// Run the query
		//-----------------------------------------

		$this->DB->build( array( 'select'		=> 'm.member_id, m.members_display_name',
										'from'		=> array( 'members' => 'm' ),
										'where'		=> $real_query,
										'add_join'	=> array(
															  1 => array( 'select' => '',
																		  'from'   => array( 'pfields_content' => 'p' ),
																		  'where'  => 'p.member_id=m.member_id',
																		  'type'   => 'left' ),
															  2 => array( 'select' => '',
																		  'from'   => array( 'profile_portal' => 'pp' ),
																		  'where'  => 'pp.pp_member_id=m.member_id',
																		  'type'   => 'left' ) 
															) 
							) 		);
		$outer = $this->DB->execute();
		
		if ( $this->DB->getTotalRows() )
		{
			while ( $r = $this->DB->fetch($outer) )
			{
				$ids[]		= $r['member_id'];
				$names[]	= $r['members_display_name'];
			}
		}
		else
		{
			$this->registry->output->showError( $this->lang->words['m_noprune'], 11235 );
		}

		IPSMember::remove( $ids, true );

		ipsRegistry::getClass('adminFunctions')->saveAdminLog( sprintf( $this->lang->words['m_deletedlog'], implode( ",", $names ) ) );
		
		// And reset the cookie
		ipsRegistry::getClass('adminFunctions')->staffSaveCookie( 'memberFilter', array() );
			
		$this->registry->output->doneScreen($this->lang->words['m_deleted'], $this->lang->words['m_control'], "{$this->form_code}&amp;do=members_list", 'redirect' );
	}
	
	
	/**
	 * Move members [process]
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 * @todo 	[Future] Centralize SQL query formatting to a single method
	 */
	private function _memberDoMove()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$data		= $this->_generateFilterBoxes();
		$_sql		= array();
		$ids		= array();
		$names		= array();
		
		if( !$this->request['move_to_group'] )
		{
			$this->registry->output->showError( $this->lang->words['m_whatgroup'], 11236 );
		}
		
		//-----------------------------------------
		// Allowed to move to/from administrators?
		//-----------------------------------------
		
		if( !$this->registry->getClass('class_permissions')->checkPermission( 'member_move_admin2') )
		{
			if( $this->caches['group_cache'][ $this->request['move_to_group'] ]['g_access_cp'] )
			{
				$this->registry->output->global_message = $this->lang->words['m_adminpromote'];
				$this->_memberList();
				return;
			}
		}
		
		if( !$this->registry->getClass('class_permissions')->checkPermission( 'member_move_admin1') )
		{
			$admin_group_ids = array();
			
			foreach( $this->cache->getCache( 'group_cache' ) as $group )
			{
				if( $group['g_access_cp'] )
				{
					$admin_group_ids[] = $group['g_id'];
					
					$_sql[] = "m.mgroup_others NOT LIKE '%," . $group['g_id'] . ",%'";
				}
			}
			
			$_sql[] = "m.member_group_id NOT IN(" . implode( ',', $admin_group_ids ) . ")";
		}

		//-----------------------------------------
		// FILTERS
		//-----------------------------------------
		
		if ( $data['member_contains_text'] )
		{
			$_field = '';
			$_text  = $this->DB->addSlashes( $data['member_contains_text'] );

			switch( $data['member_contains'] )
			{
				default:
				case 'member_id':
					$_field = 'm.member_id';
				break;

				case 'name':
					$_field = 'm.name';
				break;

				case 'members_display_name':
					$_field = 'm.members_display_name';
				break;
				case 'email':
					$_field = 'm.email';
				break;
				case 'ip_address':
					$_field = 'm.ip_address';
				break;
				case 'signature':
					$_field = 'pp.signature';
				break;
			}

			switch( $data['member_contains_type'] )
			{
				default:
				case 'contains':
					$_sql[] = $_field . " LIKE '%" . $_text . "%'";
				break;
				case 'begins':
					$_sql[] = $_field . " LIKE '" . $_text . "%'";
				break;
				case 'ends':
					$_sql[] = $_field . " LIKE '%" . $_text . "'";
				break;
				case 'equals':
					$_sql[] = $_field . " = '" . $_text . "'";
				break;
			}
		}

		if ( $data['member_type'] )
		{
			switch( $data['member_type'] )
			{
				case 'suspended':
					$_sql[] = "m.temp_ban > 0";
				break;
				case 'notsuspended':
					$_sql[] = "( m.temp_ban < 1 or m.temp_ban='' or m.temp_ban " . $this->DB->buildIsNull( true ) . " )";
				break;
			}
		}

		/* Banned status */
		if ( $data['banned_type'] )
		{
			switch( $data['banned_type'] )
			{
				case 'banned':
					$_sql[] = "m.member_banned=1";
				break;
				case 'notbanned':
					$_sql[] = "m.member_banned=0";
				break;
			}
		}

		/* Spam status */
		if ( $data['spam_type'] )
		{
			switch( $data['spam_type'] )
			{
				case 'spam':
					$_sql[] = IPSBWOptions::sql( 'bw_is_spammer', 'm.members_bitoptions', 'members', 'global', 'has' );
				break;
				case 'notspam':
					$_sql[] = "NOT (" . IPSBWOptions::sql( 'bw_is_spammer', 'm.members_bitoptions', 'members', 'global', 'has' ) . ")";
				break;
			}
		}

		if ( $data['primary_group'] )
		{
			$_sql[] = "m.member_group_id=" . intval( $data['primary_group'] );
		}

		if ( $data['secondary_group'] )
		{
			$_sql[] = "( m.mgroup_others LIKE '%," . $data['secondary_group'] . ",%' OR " .
					  "m.mgroup_others LIKE '" . $data['secondary_group'] . ",%' OR " .
					  "m.mgroup_others LIKE '%," . $data['secondary_group'] . "' OR " .
					  "m.mgroup_others='" . $data['secondary_group'] . "' )";
		}

		if ( $data['post_count'] AND $data['post_count_type'] )
		{
			$_type	= '';

			if( $data['post_count_type'] == 'gt' )
			{
				$_type	= '>';
			}
			else if( $data['post_count_type'] == 'lt' )
			{
				$_type	= '<';
			}
			else if( $data['post_count_type'] == 'eq' )
			{
				$_type	= '=';
			}

			if( $_type )
			{
				$_sql[] = "m.posts" . $_type . intval( $data['post_count'] );
			}
		}
		
		foreach( array( 'reg', 'post', 'active' ) as $_bit )
		{
			foreach( array( 'from', 'to' ) as $_when )
			{
				$bit = 'date_' . $_bit . '_' . $_when;
				
				if ( $data[ $bit ] )
				{
					list( $month, $day, $year ) = explode( '-', $data[ $bit ] );

					if ( ! checkdate( $month, $day, $year ) )
					{
						$this->registry->output->global_message = sprintf( $this->lang->words['m_daterange'], $month, $day, $year );
					}
					else
					{
						$time_int = mktime( 0, 0, 0, $month, $day, $year );
	
						switch( $_bit )
						{
							case 'reg':
								$field = 'joined';
							break;
							case 'post':
								$field = 'last_post';
							break;
							case 'active':
								$field = 'last_activity';
							break;
						}
	
						if ( $_when == 'from' )
						{
							$_sql[] = 'm.' . $field . ' > ' . $time_int;
						}
						else
						{
							$_sql[] = 'm.' . $field . ' < ' . $time_int;
						}
					}
				}
			}
		}
		
		//-----------------------------------------
		// Check we have correct fields
		//-----------------------------------------
		
		switch( $data['order_direction'] )
		{
			case 'asc':
				$order_direction = 'asc';
			break;
			default:
			case 'desc':
				$order_direction = 'desc';
			break;
		}
		
		switch( $data['order_by'] )
		{
			default:
			case 'joined':
				$order_by  = 'm.joined';
			break;
			case 'members_l_username':
				$order_by  = 'm.members_l_username';
			break;
			case 'members_l_display_name':
				$order_by  = 'm.members_l_display_name';
			break;
			case 'email':
				$order_by  = 'm.email';
			break;
		}
		
		//-----------------------------------------
		// Custom fields...
		//-----------------------------------------
		
		if( is_array($data['custom_fields']) AND count($data['custom_fields']) )
		{
			foreach ( $data['custom_fields'] as $id => $value )
	 		{
 				if ( $value )
 				{
 					$_sql[] = 'p.field_' . $id . " LIKE '%" . $value . "%'";
 				}
	 		}
 		}
		
		//-----------------------------------------
		// get 'owt?
		//-----------------------------------------
		
		$real_query = count($_sql) ? implode( " AND ", $_sql ) : '';

		//-----------------------------------------
		// Get the number of results
		//-----------------------------------------
		
		$count = $this->DB->buildAndFetch( array( 'select'	=> 'COUNT(*) as count',
														 'from'		=> array( 'members' => 'm' ),
														 'where'	=> $real_query,
														 'add_join'	=> array( 0 => array( 'from'   => array( 'profile_portal' => 'pp' ),
																						  'where'  => 'pp.pp_member_id=m.member_id',
																						  'type'   => 'left' ),
																			  1 => array( 'from'   => array( 'pfields_content' => 'p' ),
																						  'where'  => 'p.member_id=m.member_id',
																						  'type'   => 'left' ) 
																			) 
												) 		);

		if ( $count['count'] < 1 )
		{
			$this->registry->output->global_message = $this->lang->words['m_nomembers'];
			
			// And reset the cookie so we don't get the message on every page view
			ipsRegistry::getClass('adminFunctions')->staffSaveCookie( 'memberFilter', array() );
			
			$this->_memberList();
			return;
		}

		//-----------------------------------------
		// Run the query
		//-----------------------------------------
		
		$this->DB->build( array( 'select'		=> 'm.member_id, m.members_display_name',
										'from'		=> array( 'members' => 'm' ),
										'where'		=> $real_query,
										'add_join'	=> array(
															  1 => array( 'select' => '',
																		  'from'   => array( 'pfields_content' => 'p' ),
																		  'where'  => 'p.member_id=m.member_id',
																		  'type'   => 'left' ),
															  2 => array( 'select' => '',
																		  'from'   => array( 'profile_portal' => 'pp' ),
																		  'where'  => 'pp.pp_member_id=m.member_id',
																		  'type'   => 'left' ) 
															) 
							) 		);
		$outer = $this->DB->execute();
		
		if ( $this->DB->getTotalRows() )
		{
			while ( $r = $this->DB->fetch($outer) )
			{
				$ids[]		= $r['member_id'];
				$names[]	= $r['members_display_name'];
			}
		}
		else
		{
			$this->registry->output->showError( $this->lang->words['m_nomembers'], 11237 );
		}

		$this->DB->update( 'members', array( 'member_group_id' => intval($this->request['move_to_group']) ), 'member_id IN(' . implode( ',', $ids ) . ')' );
		
		$group_name = $this->caches['group_cache'][ $this->request['move_to_group'] ]['g_title'];

		ipsRegistry::getClass('adminFunctions')->saveAdminLog( sprintf($this->lang->words['m_movedlog'], $group_name, implode( ",", $names )  ) );
		
		// And reset the cookie
		ipsRegistry::getClass('adminFunctions')->staffSaveCookie( 'memberFilter', array() );
			
		$this->registry->output->doneScreen($this->lang->words['m_moved'], $this->lang->words['m_control'], "{$this->form_code}&amp;do=members_list", 'redirect' );
	}
	

	/**
	 * Delete members [form+process]
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _memberDelete()
	{
		//-----------------------------------------
		// Check input
		//-----------------------------------------
		
		if ( ! $this->request['member_id'] )
		{
			$this->registry->output->global_message = $this->lang->words['m_nomember'];
			$this->request['do']	= 'members_list';
			$this->_memberList();
			return;
		}
		
		//-----------------------------------------
		// Single or more?
		//-----------------------------------------
		
		if ( strstr( $this->request['member_id'], ',' ) )
		{
			$ids = explode( ',', $this->request['member_id'] );
		}
		else
		{
			$ids = array( $this->request['member_id'] );
		}
		
		$ids = IPSLib::cleanIntArray( $ids );
		
		/* Don't delete our selves */
		if( in_array( $this->memberData['member_id'], $ids ) )
		{
			$this->registry->output->global_message = $this->lang->words['m_nodeleteslefr'];
			$this->request['do']	= 'members_list';
			$this->_memberList();
			return;
		}

		//-----------------------------------------
		// Get accounts
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => 'member_id, name, member_group_id, mgroup_others', 'from' => 'members', 'where' => 'member_id IN (' . implode( ",", $ids ) . ')' ) );
		$this->DB->execute();
		
		$names = array();
		
		while ( $r = $this->DB->fetch() )
		{
			//-----------------------------------------
			// r u trying to kill teh admin?
			//-----------------------------------------

			if( ! $this->registry->getClass('class_permissions')->checkPermission( 'member_delete_admin' ) )
			{
				if( $this->caches['group_cache'][ $r['member_group_id'] ]['g_access_cp'] )
				{
					continue;
				}
				else
				{
					$other_mgroups = explode( ',', IPSText::cleanPermString( $r['mgroup_others'] ) );
					
					if( count($other_mgroups) )
					{
						foreach( $other_mgroups as $other_mgroup )
						{
							if( $this->caches['group_cache'][ $other_mgroup ]['g_access_cp'] )
							{
								continue;
							}
						}
					}
				}
			}
			
			$names[] = $r['name'];
		}
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! count( $names ) )
		{
			$this->registry->output->global_message = $this->lang->words['m_nomember'];
			$this->request['do']	= 'members_list';
			$this->_memberList();
			return;
		}
		
		//-----------------------------------------
		// Delete
		//-----------------------------------------
		
		IPSMember::remove( $ids, true );
		
		//-----------------------------------------
		// Clear "cookies"
		//-----------------------------------------
		
		ipsRegistry::getClass('adminFunctions')->staffSaveCookie( 'memberFilter', array() );
		
		//-----------------------------------------
		// Redirect
		//-----------------------------------------
		
		$page_query = "";

		ipsRegistry::getClass('adminFunctions')->saveAdminLog( sprintf( $this->lang->words['m_deletedlog'], implode( ",", $names ) ) );
		
		$this->registry->output->global_message = sprintf( $this->lang->words['m_deletedlog'], implode( ",", $names ) );
		$this->request['do']	= 'members_list';
		$this->_memberList();
	}
		
	
	/**
	 * Add a member [form]
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _memberAddForm()
	{
		//-----------------------------------------
		// Page details
		//-----------------------------------------

		$this->registry->output->extra_nav[] 		= array( '', $this->lang->words['m_addmember'] );

		//-----------------------------------------
		// Groups
		//-----------------------------------------
		
		$mem_group		= array();

		foreach( $this->cache->getCache('group_cache') as $r )
		{
			if ( $r['g_access_cp'] AND !$this->registry->getClass('class_permissions')->checkPermission( 'member_add_admin') )
			{
				continue;
			}
			
			$mem_group[] = array( $r['g_id'] , $r['g_title'] );
		}

    	//-----------------------------------------
		// Custom fields
		//-----------------------------------------
		
		require_once( IPS_ROOT_PATH . 'sources/classes/customfields/profileFields.php' );
		$custom_fields = new customProfileFields();
		
		$custom_fields->member_data = $member;
		$custom_fields->initData( 'edit' );
		$custom_fields->parseToEdit();
	     						     
		$this->registry->output->html .= $this->html->memberAddForm( $mem_group, $custom_fields );
	}
	
	/**
	 * Add a member [process]
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _memberDoAdd()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$in_username 			= trim($this->request['name']);
		$in_password 			= trim($this->request['password']);
		$in_email    			= trim(strtolower($this->request['email']));
		$members_display_name	= trim($this->request['members_display_name'] );
		
		$this->registry->output->global_message = '';
		
		//-----------------------------------------
		// Check form
		//-----------------------------------------
	
		foreach( array('name', 'password', 'email', 'member_group_id') as $field )
		{
			if ( ! $_POST[ $field ] )
			{
				$this->registry->output->showError( $this->lang->words['m_completeform'], 11238 );
			}
		}
		
		//-----------------------------------------
		// Check
		//-----------------------------------------

		if( ! IPSText::checkEmailAddress( $in_email ) )
		{
			$this->registry->output->global_message = $this->lang->words['m_emailinv'];
		}
		
		$userName		= IPSMember::getFunction()->cleanAndCheckName( $in_username, array(), 'name' );
		$displayName	= IPSMember::getFunction()->cleanAndCheckName( $members_display_name, array(), 'members_display_name' );

		if( count($userName['errors']) )
		{
			$this->registry->output->global_message .= '<p>' . $this->lang->words['sm_loginname'] . ' ' . $userName['errors']['username'] . '</p>';
		}

		if( $this->settings['auth_allow_dnames'] AND count($displayName['errors']) )
		{
			$this->registry->output->global_message .= '<p>' . $this->lang->words['sm_display'] . ' ' . $displayName['errors']['dname'] . '</p>';
		}

		/* Errors? */
		if( $this->registry->output->global_message )
		{
			$this->_memberAddForm();
			return;
		}

        //-----------------------------------------
    	// Load handler...
    	//-----------------------------------------
    	
    	require_once( IPS_ROOT_PATH.'sources/handlers/han_login.php' );
    	$this->han_login           =  new han_login( $this->registry );
    	$this->han_login->init();
    	$this->han_login->emailExistsCheck( $in_email );

    	if( $this->han_login->return_code AND $this->han_login->return_code != 'METHOD_NOT_DEFINED' AND $this->han_login->return_code != 'EMAIL_NOT_IN_USE' )
    	{
			$this->registry->output->global_message = $this->lang->words['m_emailalready'];
			$this->_memberAddForm();
			return;
    	}

		//-----------------------------------------
		// Allowed to add administrators?
		//-----------------------------------------
		
		if( $this->caches['group_cache'][ intval($this->request['member_group_id']) ]['g_access_cp'] AND !$this->registry->getClass('class_permissions')->checkPermission( 'member_add_admin') )
		{
			$this->registry->output->global_message = $this->lang->words['m_addadmin'];
			$this->_memberAddForm();
			return;
		}

		$member = array(
						 'name'						=> $in_username,
						 'members_display_name'		=> $members_display_name ? $members_display_name : $in_username,
						 'email'					=> $in_email,
						 'member_group_id'			=> intval($this->request['member_group_id']),
						 'joined'					=> time(),
						 'ip_address'				=> $this->member->ip_address,
						 'time_offset'				=> $this->settings['time_offset'],
						 'coppa_user'				=> intval($this->request['coppa']),
						 'allow_admin_mails'		=> 1,
						 'password'					=> $in_password,
					   );

		//-----------------------------------------
		// Create the account
		//-----------------------------------------
		
		$member	= IPSMember::create( array( 'members' => $member, 'pfields_content' => $this->request ) );
		
		//-----------------------------------------
		// Login handler create account callback
		//-----------------------------------------

   		$this->han_login->createAccount( array(	'email'			=> $in_email,
   												'joined'		=> $member['joined'],
   												'password'		=> $in_password,
   												'ip_address'	=> $member['ip_address'],
   												'username'		=> $member['members_display_name'],
   										)		);

		/*if( $this->han_login->return_code AND $this->han_login->return_code != 'METHOD_NOT_DEFINED' AND $this->han_login->return_code != 'SUCCESS' )
		{
			$this->registry->output->global_message = sprintf( $this->lang->words['m_cantadd'], $this->han_login->return_code ) . $this->han_login->return_details;
			$this->_memberAddForm();
			return;
		}*/

		//-----------------------------------------
		// Restriction permissions stuff
		//-----------------------------------------
		
		if ( $this->memberData['row_perm_cache'] )
		{
			if ( $this->caches['group_cache'][ intval($this->request['member_group_id']) ]['g_access_cp'] )
			{
				//-----------------------------------------
				// Copy restrictions...
				//-----------------------------------------
				
				$this->DB->insert( 'admin_permission_rows', array( 
																	'row_member_id'  => $member_id,
																	'row_perm_cache' => $this->memberData['row_perm_cache'],
																	'row_updated'    => time() 
								)	 );
			}
		}
		
		//-----------------------------------------
		// Send teh email (I love 'teh' as much as !!11!!1)
		//-----------------------------------------
		
		if( $this->request['sendemail'] )
		{
			IPSText::getTextClass('email')->getTemplate("account_created");
			
			IPSText::getTextClass('email')->buildMessage( array(
												'NAME'         => $member['name'],
												'EMAIL'        => $member['email'],
												'PASSWORD'	   => $in_password
											  )
										);

			IPSText::getTextClass('email')->to		= $member['email'];
			IPSText::getTextClass('email')->sendMail();
		}
		
		//-----------------------------------------
		// Stats
		//-----------------------------------------
		
		$this->cache->rebuildCache( 'stats', 'global' );

		//-----------------------------------------
		// Log and bog?
		//-----------------------------------------
		             
		ipsRegistry::getClass('adminFunctions')->saveAdminLog( sprintf( $this->lang->words['m_createlog'], $this->request['name'] ) );
		
		$this->registry->output->global_message = $this->lang->words['m_memadded'];

		$this->request['member_id']	= $member['member_id'];
		
		$this->_showAdminForm( $member, array() );
		$this->_memberView();		
	}
	

	/**
	 * List members
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _memberList()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$data		= $this->_generateFilterBoxes();
		$_sql		= array();
		$st			= intval($this->request['st']);
		$members	= array();
		$perpage	= 20;

		//-----------------------------------------
		// FILTERS
		//-----------------------------------------

		if ( $data['member_contains_text'] )
		{
			$_field = '';
			$_text  = $this->DB->addSlashes( $data['member_contains_text'] );

			switch( $data['member_contains'] )
			{
				default:
				case 'member_id':
					$_field = 'm.member_id';
				break;

				case 'name':
					$_field = 'm.name';
				break;

				case 'members_display_name':
					$_field = 'm.members_display_name';
				break;
				case 'email':
					$_field = 'm.email';
				break;
				case 'ip_address':
					$_field = 'm.ip_address';
				break;
				case 'signature':
					$_field = 'pp.signature';
				break;
			}

			switch( $data['member_contains_type'] )
			{
				default:
				case 'contains':
					$_sql[] = $_field . " LIKE '%" . $_text . "%'";
				break;
				case 'begins':
					$_sql[] = $_field . " LIKE '" . $_text . "%'";
				break;
				case 'ends':
					$_sql[] = $_field . " LIKE '%" . $_text . "'";
				break;
				case 'equals':
					$_sql[] = $_field . " = '" . $_text . "'";
				break;
			}
		}

		if ( $data['member_type'] )
		{
			switch( $data['member_type'] )
			{
				case 'suspended':
					$_sql[] = "m.temp_ban > 0";
				break;
				case 'notsuspended':
					$_sql[] = "( m.temp_ban < 1 or m.temp_ban='' or m.temp_ban " . $this->DB->buildIsNull( true ) . " )";
				break;
			}
		}
		
		/* Banned status */
		if ( $data['banned_type'] )
		{
			switch( $data['banned_type'] )
			{
				case 'banned':
					$_sql[] = "m.member_banned=1";
				break;
				case 'notbanned':
					$_sql[] = "m.member_banned=0";
				break;
			}
		}
		
		/* Spam status */
		if ( $data['spam_type'] )
		{
			switch( $data['spam_type'] )
			{
				case 'spam':
					$_sql[] = IPSBWOptions::sql( 'bw_is_spammer', 'm.members_bitoptions', 'members', 'global', 'has' );
				break;
				case 'notspam':
					$_sql[] = "NOT (" . IPSBWOptions::sql( 'bw_is_spammer', 'm.members_bitoptions', 'members', 'global', 'has' ) . ")";
				break;
			}
		}

		if ( $data['primary_group'] )
		{
			$_sql[] = "m.member_group_id=" . intval( $data['primary_group'] );
		}
		
		if ( $data['post_count'] AND $data['post_count_type'] )
		{
			$_type	= '';
			
			if( $data['post_count_type'] == 'gt' )
			{
				$_type	= '>';
			}
			else if( $data['post_count_type'] == 'lt' )
			{
				$_type	= '<';
			}
			else if( $data['post_count_type'] == 'eq' )
			{
				$_type	= '=';
			}
			
			if( $_type )
			{
				$_sql[] = "m.posts" . $_type . intval( $data['post_count'] );
			}
		}

		if ( $data['secondary_group'] )
		{
			$_sql[] = "( m.mgroup_others LIKE '%," . $data['secondary_group'] . ",%' OR " .
					  "m.mgroup_others LIKE '" . $data['secondary_group'] . ",%' OR " .
					  "m.mgroup_others LIKE '%," . $data['secondary_group'] . "' OR " .
					  "m.mgroup_others='" . $data['secondary_group'] . "' )";
		}

		foreach( array( 'reg', 'post', 'active' ) as $_bit )
		{
			foreach( array( 'from', 'to' ) as $_when )
			{
				$bit = 'date_' . $_bit . '_' . $_when;
				
				if ( $data[ $bit ] )
				{
					//-----------------------------------------
					// mm/dd/yyyy instead of mm-dd-yyyy
					//-----------------------------------------
					
					$data[ $bit ]	= str_replace( '/', '-', $data[ $bit ] );
					
					list( $month, $day, $year ) = explode( '-', $data[ $bit ] );

					if ( ! checkdate( $month, $day, $year ) )
					{
						$this->registry->output->global_message = sprintf($this->lang->words['m_daterange'], $month, $day, $year );
					}
					else
					{
						$time_int = mktime( 0, 0, 0, $month, $day, $year );
	
						switch( $_bit )
						{
							case 'reg':
								$field = 'joined';
							break;
							case 'post':
								$field = 'last_post';
							break;
							case 'active':
								$field = 'last_activity';
							break;
						}
	
						if ( $_when == 'from' )
						{
							$_sql[] = 'm.' . $field . ' > ' . $time_int;
						}
						else
						{
							$_sql[] = 'm.' . $field . ' < ' . $time_int;
						}
					}
				}
			}
		}
		
		//-----------------------------------------
		// Check we have correct fields
		//-----------------------------------------
		
		switch( $data['order_direction'] )
		{
			case 'asc':
				$order_direction = 'asc';
			break;
			default:
			case 'desc':
				$order_direction = 'desc';
			break;
		}
		
		switch( $data['order_by'] )
		{
			default:
			case 'joined':
				$order_by  = 'm.joined';
			break;
			case 'members_l_username':
				$order_by  = 'm.members_l_username';
			break;
			case 'members_l_display_name':
				$order_by  = 'm.members_l_display_name';
			break;
			case 'email':
				$order_by  = 'm.email';
			break;
		}
		
		//-----------------------------------------
		// Custom fields...
		//-----------------------------------------

		if( is_array($data['custom_fields']) AND count($data['custom_fields']) )
		{
			foreach ( $data['custom_fields'] as $id => $value )
	 		{
 				if ( $value )
 				{
 					$_sql[] = 'p.field_' . $id . " LIKE '%" . $value . "%'";
 				}
	 		}
 		}

		//-----------------------------------------
		// get 'owt?
		//-----------------------------------------
		
		$real_query = count($_sql) ? implode( " AND ", $_sql ) : '';

		//-----------------------------------------
		// Get the number of results
		//-----------------------------------------
		
		$count = $this->DB->buildAndFetch( array( 'select'	=> 'COUNT(*) as count',
														 'from'		=> array( 'members' => 'm' ),
														 'where'	=> $real_query,
														 'add_join'	=> array( 0 => array( 'from'   => array( 'profile_portal' => 'pp' ),
																						  'where'  => 'pp.pp_member_id=m.member_id',
																						  'type'   => 'left' ),
																			  1 => array( 'from'   => array( 'pfields_content' => 'p' ),
																						  'where'  => 'p.member_id=m.member_id',
																						  'type'   => 'left' ) 
																			) 
												) 		);

		if ( $count['count'] < 1 )
		{
			$this->registry->output->global_message = $this->lang->words['m_nomembers'];
			
			// Reset the filter
			$real_query = '';

			// And reset the cookie so we don't get the message on every page view
			ipsRegistry::getClass('adminFunctions')->staffSaveCookie( 'memberFilter', array() );
			
			$count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'members' ) );
		}

		$pages = $this->registry->output->generatePagination( array( 'totalItems'			=> $count['count'],
																	 'itemsPerPage'			=> $perpage,
																	 'currentStartValue'	=> $st,
																	 'baseUrl'				=> $this->settings['base_url'] . "&{$this->form_code}&do=" . $this->request['do'],
														)		);
		
		//-----------------------------------------
		// Run the query
		//-----------------------------------------
		
		$this->DB->build( array( 'select'		=> 'm.*, m.member_id as mem_id',
										'from'		=> array( 'members' => 'm' ),
										'where'		=> $real_query,
										'order'		=> $order_by . ' ' . $order_direction,
										'limit'		=> array( $st, $perpage ),
										'add_join'	=> array(
															  1 => array( 'select' => 'p.*',
																		  'from'   => array( 'pfields_content' => 'p' ),
																		  'where'  => 'p.member_id=m.member_id',
																		  'type'   => 'left' ),
															  2 => array( 'select' => 'pp.*',
																		  'from'   => array( 'profile_portal' => 'pp' ),
																		  'where'  => 'pp.pp_member_id=m.member_id',
																		  'type'   => 'left' ) 
															) 
							) 		);
		$outer = $this->DB->execute();
		
		while ( $r = $this->DB->fetch($outer) )
		{
			$r['member_id']     = $r['mem_id'];
			$r['_joined']		= $this->registry->class_localization->getDate( $r['joined'], 'JOINED' );
			$r['group_title']	= $this->caches['group_cache'][ $r['member_group_id'] ]['g_title'];

			$members[] = IPSMember::buildDisplayData( $r );
		}
		
		//-----------------------------------------
		// Prune you fookers?
		//-----------------------------------------

		if ( $data['search_type'] == 'delete' )
		{
			$this->_memberPruneForm( $count['count'] );
			return;
		}
		else if( $data['search_type'] == 'move' )
		{
			$this->_memberMoveForm( $count['count'] );
			return;
		}
		
		$this->registry->output->extra_nav[] = array( '', $this->lang->words['m_viewlist'] );

		$this->registry->output->html .= $this->html->members_list( $members, $pages );
	}


	/**
	 * Edit a member [process]
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _memberDoEdit()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$this->request['member_id'] = intval($this->request['member_id']);
		
		//-----------------------------------------
		// Auth check...
		//-----------------------------------------
		
		ipsRegistry::getClass('adminFunctions')->checkSecurityKey( $this->request['secure_key'] );

		//-----------------------------------------
		// Load and config the std/rte editors
		//-----------------------------------------

		IPSText::getTextClass('editor')->from_acp         = 1;

        //-----------------------------------------
        // Get member
        //-----------------------------------------
		
        $member		= IPSMember::load( $this->request['member_id'], 'all' );

		//-----------------------------------------
		// Allowed to edit administrators?
		//-----------------------------------------
		
		if( $member['member_id'] != $this->memberData['member_id'] AND $member['g_access_cp'] AND !$this->registry->getClass('class_permissions')->checkPermission( 'member_edit_admin') )
		{
			$this->registry->output->global_message = $this->lang->words['m_editadmin'];
			$this->_memberView();
			return;
		}

		//-----------------------------------------
		// Allowed to change an admin's groups?
		//-----------------------------------------
		
		if( $member['g_access_cp'] AND !$this->registry->getClass('class_permissions')->checkPermission('member_move_admin1') )
		{
			$same		= false;
			
			if( $this->request['member_group_id'] == $member['member_group_id'] )
			{
				$omgroups	= explode( ',', IPSText::cleanPermString( $member['mgroup_others'] ) );
				$groups		= $_POST['mgroup_others'] ? $_POST['mgroup_others'] : array();
				
				if( !count( array_diff( $omgroups, $groups ) ) )
				{
					$same	= true;
				}
			}

			if( !$same )
			{
				$this->registry->output->global_message = $this->lang->words['m_admindemote'];
				$this->_memberView();
				return;
			}
		}

		//-----------------------------------------
		// What about promoting to admin?
		//-----------------------------------------
		
		if( !$member['g_access_cp'] AND !$this->registry->getClass('class_permissions')->checkPermission('member_move_admin2') )
		{
			$groups		= $_POST['mgroup_others'] ? $_POST['mgroup_others'] : array();
			$groups[]	= intval($this->request['member_group_id']);
			
			foreach( $groups as $group_id )
			{
				if( $this->caches['group_cache'][ $group_id ]['g_access_cp'] )
				{
					$this->registry->output->global_message = $this->lang->words['m_adminpromote'];
					$this->_memberView();
					return;
				}
			}
		}

		if( $this->request['identity_url'] )
		{
			$account	= $this->DB->buildAndFetch( array( 'select' => 'member_id', 'from' => 'members', 'where' => "identity_url='" . trim($this->request['identity_url']) . "' AND member_id<>" . $member['member_id'] ) );
			
			if( $account['member_id'] )
			{
				$this->registry->output->global_message = $this->lang->words['identity_url_inuse'];
				$this->_memberView();
				return;
			}
		}

		//-----------------------------------------
		// Convert sig
		//-----------------------------------------

		$signature 					= IPSText::getTextClass('editor')->processRawPost( 'signature' );
		IPSText::getTextClass('bbcode')->parse_smilies		= 0;
		IPSText::getTextClass('bbcode')->parse_bbcode		= $this->settings['sig_allow_ibc'];
		IPSText::getTextClass('bbcode')->parse_html			= $this->settings['sig_allow_html'];
		IPSText::getTextClass('bbcode')->parse_nl2br		= 1;
		IPSText::getTextClass('bbcode')->parsing_section			= 'signatures';
		
		$signature					= IPSText::getTextClass('bbcode')->preDbParse( $signature );
		$cacheSignature				= IPSText::getTextClass('bbcode')->preDisplayParse( $signature );
		
		//-----------------------------------------
		// And 'About Me'
		//-----------------------------------------

		$aboutme 					= IPSText::getTextClass('editor')->processRawPost( 'aboutme' );
		IPSText::getTextClass('bbcode')->parse_smilies		= $this->settings['aboutme_emoticons'];
		IPSText::getTextClass('bbcode')->parse_bbcode		= $this->settings['aboutme_bbcode'];
		IPSText::getTextClass('bbcode')->parse_html			= $this->settings['aboutme_html'];
		IPSText::getTextClass('bbcode')->parse_nl2br		= 1;
		IPSText::getTextClass('bbcode')->parsing_section			= 'aboutme';
		
		$aboutme					= IPSText::getTextClass('bbcode')->preDbParse( $aboutme );
		
		//-----------------------------------------
		// Ok? Load interface and child classes
		//-----------------------------------------
		
		$additionalCore		= array();
		$additionalExtended	= array();

		IPSLib::loadInterface( 'admin/member_form.php' );
		
		foreach( ipsRegistry::$applications as $app_dir => $app_data )
		{
			if ( ! IPSLib::appIsInstalled( $app_dir ) )
			{
				continue;
			}
			
			if ( file_exists( IPSLib::getAppDir(  $app_dir ) . '/extensions/admin/member_form.php' ) )
			{
				require_once( IPSLib::getAppDir(  $app_dir ) . '/extensions/admin/member_form.php' );
				$_class  = 'admin_member_form__' . $app_dir;
				$_object = new $_class( $this->registry );
				
				$remote = $_object->getForSave();

				$additionalCore		= array_merge( $remote['core'], $additionalCore );
				$additionalExtended	= array_merge( $remote['extendedProfile'], $additionalExtended );
			}
		}
		
		//-----------------------------------------
		// Fix custom title
		// @see	http://forums./index.php?app=tracker&showissue=17383
		//-----------------------------------------
		
		$memberTitle	= $this->request['title'];
		$rankCache		= ipsRegistry::cache()->getCache( 'ranks' );
		
		if ( is_array( $rankCache ) && count( $rankCache ) )
		{
			foreach( $rankCache as $k => $v)
			{
				if ( $member['posts'] >= $v['POSTS'] )
				{
					/* If this is the title passed to us from the form, we didn't have a custom title */
					if ( $v['TITLE'] == $memberTitle )
					{
						$memberTitle	= '';
					}

					break;
				}
			}
		}

		$newMember = array(
							'member_group_id'		=> intval($this->request['member_group_id']),
							'title'					=> $memberTitle,
							'time_offset'			=> floatval($this->request['time_offset']),
							'language'				=> $this->request['language'],
							'skin'					=> intval($this->request['skin']),
							'hide_email'			=> intval($this->request['hide_email']),
							'allow_admin_mails'		=> intval($this->request['allow_admin_mails']),
							'view_sigs'				=> intval($this->request['view_sigs']),
							'view_pop'				=> intval($this->request['view_pop']),
							'email_pm'				=> intval($this->request['email_pm']),
							'posts'					=> intval($this->request['posts']),
							'bday_day'				=> intval($this->request['bday_day']),
							'bday_month'			=> intval($this->request['bday_month']),
							'bday_year'				=> intval($this->request['bday_year']),
							'warn_level'			=> intval($this->request['warn_level']),
							'members_disable_pm'	=> intval($this->request['members_disable_pm']),
							'mgroup_others'			=> $_POST['mgroup_others'] ? ',' . implode( ",", $_POST['mgroup_others'] ) . ',' : '',
							'identity_url'			=> trim($this->request['identity_url']),
							);

		//-----------------------------------------
		// Throw to the DB
		//-----------------------------------------
		
		IPSMember::save( $this->request['member_id'],
						 array( 
							 	'core'				=> array_merge( $newMember, $additionalCore ),
							 	'extendedProfile'	=> array_merge( array(
															'pp_gender'						=> ( $this->request['pp_gender'] == 'male' ) ? 'male' : ( $this->request['pp_gender'] == 'female' ? 'female' : '' ),
															'pp_bio_content'				=> IPSText::mbsubstr( nl2br( $this->request['pp_bio_content'] ), 0, 300 ),
															'pp_about_me'					=> $aboutme,
															'signature'						=> $signature,
															'pp_reputation_points'			=> intval( $this->request['pp_reputation_points'] ),
															'pp_status'						=> $this->request['pp_status'],
															'pp_setting_count_visitors'		=> intval($this->request['pp_setting_count_visitors']),
															'pp_setting_count_comments'		=> intval($this->request['pp_setting_count_comments']),
															'pp_setting_count_friends'		=> intval($this->request['pp_setting_count_friends']),
															'pp_setting_notify_comments'	=> $this->request['pp_setting_notify_comments'],
															'pp_setting_notify_friend'		=> $this->request['pp_setting_notify_friend'],
															'pp_setting_moderate_comments'	=> intval($this->request['pp_setting_moderate_comments']),
															'pp_setting_moderate_friends'	=> intval($this->request['pp_setting_moderate_friends']),
															), $additionalExtended ),
						 	  )
						);
						
		if( $member['member_group_id'] != $newMember['member_group_id'] )
		{
			IPSLib::runMemberSync( 'onGroupChange', $this->request['member_id'], $newMember['member_group_id'] );
			
			//-----------------------------------------
			// Remove restrictions if member demoted
			// Commenting out as this may cause more problems than it's worth
			// e.g. if you had accidentally changed their group, you'd need to reconfigure all restrictions
			//-----------------------------------------

			/*if( !$this->caches['group_cache'][ $newMember['member_group_id'] ]['g_access_cp'] )
			{
				$this->DB->delete( 'admin_permission_rows', 'row_id=' . $member['member_id'] . " AND row_id_type='member'" );
			}*/
		}						
		
		//-----------------------------------------
		// Restriction permissions stuff
		//-----------------------------------------

		if ( is_array($this->registry->getClass('class_permissions')->restrictions_row) AND count($this->registry->getClass('class_permissions')->restrictions_row) )
		{
			$is_admin	= 0;
			$groups		= ipsRegistry::cache()->getCache('group_cache');
			
			if ( is_array( $this->request['mgroup_others'] ) AND count( $this->request['mgroup_others'] ) )
			{
				foreach( $this->request['mgroup_others'] as $omg )
				{
					if ( $groups[ intval($omg) ]['g_access_cp'] )
					{
						$is_admin	= 1;
						break;
					}
				}
			}
			
			if( $groups[ intval($this->request['member_group_id']) ]['g_access_cp'] )
			{
				$is_admin	= 1;
			}

			if ( $is_admin )
			{
				//-------------------------------------------------
				// Copy restrictions if they do not have any yet...
				//-------------------------------------------------
				
				$check = $this->DB->buildAndFetch( array( 'select' => 'row_updated', 'from' => 'admin_permission_rows', 'where' => "row_id_type='member' AND row_id=" . $this->request['member_id'] ) );
				
				if( !$check['row_updated'] )
				{
					$this->DB->replace( 'admin_permission_rows', array( 'row_id'			=> $this->request['member_id'],
																		'row_id_type'		=> 'member',
																		'row_perm_cache'	=> serialize($this->registry->getClass('class_permissions')->restrictions_row),
																		'row_updated'		=> time() ), array( 'row_id', 'row_id_type' ) );
				}
			}
		}	

		//-----------------------------------------
		// Moved from validating group?
		//-----------------------------------------
		
		if ( $member['member_group_id'] == $this->settings['auth_group'] )
		{
			if ( $this->request['member_group_id'] != $this->settings['auth_group'] )
			{
				//-----------------------------------------
				// Yes...
				//-----------------------------------------
				
				$this->DB->delete( 'validating', "member_id=" . $this->request['member_id'] );
			}
		}

		//-----------------------------------------
		// Custom profile field stuff
		//-----------------------------------------
		
		require_once( IPS_ROOT_PATH.'sources/classes/customfields/profileFields.php' );
    	$fields = new customProfileFields();

    	$fields->initData( 'edit' );
    	$fields->parseToSave( $_POST );
		
		//-----------------------------------------
		// Custom profile field stuff
		//-----------------------------------------
		
		if ( count( $fields->out_fields ) )
		{
			//-----------------------------------------
			// Do we already have an entry in
			// the content table?
			//-----------------------------------------
			
			$test = $this->DB->buildAndFetch( array( 'select' => 'member_id', 'from' => 'pfields_content', 'where' => 'member_id='.$this->request['member_id'] ) );
			
			if ( $test['member_id'] )
			{
				//-----------------------------------------
				// We have it, so simply update
				//-----------------------------------------
				
				$this->DB->force_data_type = array();
				
				foreach( $fields->out_fields as $_field => $_data )
				{
					$this->DB->force_data_type[ $_field ] = 'string';
				}
				
				$this->DB->update( 'pfields_content', $fields->out_fields, 'member_id='.$this->request['member_id'] );
			}
			else
			{
				$this->DB->force_data_type = array();
				
				foreach( $fields->out_fields as $_field => $_data )
				{
					$this->DB->force_data_type[ $_field ] = 'string';
				}
				
				$fields->out_fields['member_id'] = $this->request['member_id'];
				
				$this->DB->insert( 'pfields_content', $fields->out_fields );
			}
		}
				
		/* Update cache */
		IPSContentCache::update( $this->request['member_id'], 'sig', $cacheSignature );
		
		//-----------------------------------------
		// Redirect
		//-----------------------------------------
		
		ipsRegistry::getClass('adminFunctions')->saveAdminLog( sprintf( $this->lang->words['m_editedlog'], $member['members_display_name'] ) );
		
		$this->registry->output->global_message = $this->lang->words['m_edited'];
		
		$newMember['member_id']				= $this->request['member_id'];
		$newMember['members_display_name']	= $member['members_display_name'];

		$triggerGroups	= $member['mgroup_others'] ? implode( ',', array_merge( is_array($member['mgroup_others']) ? $member['mgroup_others'] : array(), array( $member['member_group_id'] ) ) ) : $member['member_group_id'];
		//$this->_memberView();
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code . '&do=viewmember&trigger=' . $triggerGroups . '&member_id=' . $this->request['member_id'] );
	}


	/**
	 * Generate context-menu filter boxes
	 * Pass &_nosave=1 to not store / read from a cookie
	 *
	 * @access	private
	 * @author	Matt Mecham
	 * @since	IPB 3.0.0
	 * @return	void		[Outputs to screen]
	 * @todo 	[Future] Allow multiple filter fields
	 */
	private function _generateFilterBoxes()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$form          = array();
		$custom_fields = array();
			
		# Filter: Customer contains
		$member_contains       = ( $this->request['f_member_contains'] )      ? trim( $this->request['f_member_contains'] )      : '';
		$member_contains_type  = ( $this->request['f_member_contains_type'] ) ? trim( $this->request['f_member_contains_type'] ) : '';
		$member_contains_text  = ( $this->request['f_member_contains_text'] ) ? trim( $this->request['f_member_contains_text'] ) : '';

		$_member_contains = array(	0  => array( 'members_display_name'	, $this->lang->words['m_f_display'] ),
									1  => array( 'name'					, $this->lang->words['m_f_login'] ),
									2  => array( 'member_id'			, $this->lang->words['m_f_id'] ),
									3  => array( 'email'				, $this->lang->words['m_f_email'] ),
									4  => array( 'ip_address'			, $this->lang->words['m_f_ip'] ),
									5  => array( 'signature'			, $this->lang->words['m_f_sig'] ) );

		$_member_contains_type = array( 0 => array( 'contains', $this->lang->words['m_f_contains'] ),
										1 => array( 'equals'  , $this->lang->words['m_f_equals'] ),
										2 => array( 'begins'  , $this->lang->words['m_f_begins']   ),
									    3 => array( 'ends'    , $this->lang->words['m_f_ends'] ) );

		# Order by
		$order_by        = '';

		$order_by        = ( $this->request['order_by'] ) ? $this->request['order_by'] : 'members_l_display_name';

		$_order_by       = array( 0 => array( 'joined'                 , $this->lang->words['m_f_joined'] ),
								  1 => array( 'members_l_username'     , $this->lang->words['m_f_slogin'] ),
								  2 => array( 'members_l_display_name' , $this->lang->words['m_f_sdisplay'] ),
								  3 => array( 'email'                  , $this->lang->words['m_f_email'] ) );

		# Order direction
		$order_direction = ( $this->request['order_direction'] ) ? $this->request['order_direction'] : 'ASC';

		$_order_direction = array( 0 => array( 'asc'   , $this->lang->words['m_f_orderaz'] ),
								   1 => array( 'desc'  , $this->lang->words['m_f_orderza'] ) );

		# Filter: member type
		$member_type         = ( $this->request['f_member_type'] ) ? trim( $this->request['f_member_type'] ) : '';

		$_member_type        = array( 0 => array( 'all'         , $this->lang->words['m_f_showall'] ),
									  1 => array( 'suspended'   , $this->lang->words['m_f_showsusp'] ),
									  2 => array( 'notsuspended', $this->lang->words['m_f_showunsusp'] ) );
														
		# Filter: banned type
		$banned_type         = ( $this->request['f_banned_type'] ) ? trim( $this->request['f_banned_type'] ) : '';

		$_banned_type        = array( 0 => array( 'all'         , $this->lang->words['m_f_showall'] ),
									  1 => array( 'banned'      , $this->lang->words['m_f_showbanned'] ),
									  2 => array( 'notbanned'   , $this->lang->words['m_f_shownotbanned'] ) );
									
		# Filter: SPAM status type
		$spam_type           = ( $this->request['f_spam_type'] ) ? trim( $this->request['f_spam_type'] ) : '';

		$_spam_type          = array( 0 => array( 'all'         , $this->lang->words['m_f_showall'] ),
									  1 => array( 'spam'        , $this->lang->words['m_f_showspam'] ),
									  2 => array( 'notspam'     , $this->lang->words['m_f_shownotspam'] ) );
															
		# Type of search
		$search_type      = ( $this->request['f_search_type'] ) ? $this->request['f_search_type'] : 'normal';

		$_search_type       = array( 0 => array( 'normal', $this->lang->words['m_f_toedit'] ) );
		
		if( $this->registry->getClass('class_permissions')->checkPermission( 'member_delete' ) )
		{
			$_search_type[1] = array( 'delete', $this->lang->words['m_f_todelete'] );
		}
		
		if( $this->registry->getClass('class_permissions')->checkPermission( 'member_move' ) )
		{
			$_search_type[2] = array( 'move', $this->lang->words['m_f_tomove'] );
		}
		
		# Date Ranges
		$date_reg_from     = ( $this->request['f_date_reg_from'] ) ? trim( $this->request['f_date_reg_from'] ) : '';
		$date_reg_to       = ( $this->request['f_date_reg_to'] )   ? trim( $this->request['f_date_reg_to'] ) : '';
		
		$date_post_from    = ( $this->request['f_date_post_from'] ) ? trim( $this->request['f_date_post_from'] ) : '';
		$date_post_to      = ( $this->request['f_date_post_to'] )   ? trim( $this->request['f_date_post_to'] ) : '';
	
		$date_active_from  = ( $this->request['f_date_active_from'] ) ? trim( $this->request['f_date_active_from'] ) : '';
		$date_active_to    = ( $this->request['f_date_active_to'] )   ? trim( $this->request['f_date_active_to'] ) : '';
		
		$primary_group      = ( $this->request['f_primary_group'] ) ? trim( $this->request['f_primary_group'] ) : 0;
		$secondary_group    = ( $this->request['f_secondary_group'] )   ? trim( $this->request['f_secondary_group'] ) : 0;
		
		$_primary_group     = array( 0 => array( '0', $this->lang->words['m_f_primany'] ) );
		$_secondary_group   = array( 0 => array( '0', $this->lang->words['m_f_secany'] ) );
		
		$post_count			= ( $this->request['f_post_count'] ) ? trim( $this->request['f_post_count'] ) : '';
		$post_count_type	= ( $this->request['f_post_count_type'] ) ? trim( $this->request['f_post_count_type'] ) : '';

		$_post_count_types	= array( 0 => array( 'lt'   , $this->lang->words['pc_lt'] ),
								   	 1 => array( 'gt'  , $this->lang->words['pc_gt'] ),
								   	 3 => array( 'eq'  , $this->lang->words['pc_eq'] ) );

		foreach( ipsRegistry::cache()->getCache('group_cache') as $_gid => $_gdata )
		{
			$_primary_group[]   = array( $_gdata['g_id'] , $_gdata['g_title'] );
			$_secondary_group[] = array( $_gdata['g_id'] , $_gdata['g_title'] );
		}
		
		/* Reset Fitlers */
		if( $this->request['reset_filters'] )
		{
			ipsRegistry::getClass('adminFunctions')->staffSaveCookie( 'memberFilter', array() );
		}
		
		//-----------------------------------------
		// Not posted, so er.. get the cookie
		//-----------------------------------------

		$custom_field_data = array();
		$filters_preset    = 0;

		if ( ! $this->request['__update'] AND ! $this->request['_nosave'] )
		{
			$_cookie_array = ipsRegistry::getClass('adminFunctions')->staffGetCookie( 'memberFilter' );

			if ( $_cookie_array )
			{
				if ( is_array( $_cookie_array ) AND count ( $_cookie_array ) )
				{
					$member_type            = substr( $_cookie_array['c_member_type'], 0,10 );
					$banned_type            = substr( $_cookie_array['c_banned_type'], 0,10 );
					$spam_type              = substr( $_cookie_array['c_spam_type'], 0,10 );
					$member_contains        = substr( $_cookie_array['c_member_contains'], 0,20 );
					$member_contains_type   = substr( $_cookie_array['c_member_contains_type'], 0,20 );
					$member_contains_text   = substr( $_cookie_array['c_member_contains_text'], 0,50 );
					$post_count				= trim( IPSText::alphanumericalClean( $_cookie_array['c_post_count'] ) );
					$post_count_type		= trim( IPSText::alphanumericalClean( $_cookie_array['c_post_count_type'] ) );
					$order_by             = trim( IPSText::alphanumericalClean( $_cookie_array['c_order_by'] ) );
					$order_direction      = trim( IPSText::alphanumericalClean( $_cookie_array['c_order_direction'] ) );
					$date_reg_from			= trim( IPSText::alphanumericalClean( $_cookie_array['c_date_reg_from'], '/-' ) );
					$date_reg_to			= trim( IPSText::alphanumericalClean( $_cookie_array['c_date_reg_to'], '/-' ) );
					$date_post_from			= trim( IPSText::alphanumericalClean( $_cookie_array['c_date_post_from'], '/-' ) );
					$date_post_to			= trim( IPSText::alphanumericalClean( $_cookie_array['c_date_post_to'], '/-' ) );
					$date_active_from		= trim( IPSText::alphanumericalClean( $_cookie_array['c_date_active_from'], '/-' ) );
					$date_active_to			= trim( IPSText::alphanumericalClean( $_cookie_array['c_date_active_to'], '/-' ) );
					$primary_group		    = trim( IPSText::alphanumericalClean( $_cookie_array['c_primary_group'], '/-' ) );
					$secondary_group	    = trim( IPSText::alphanumericalClean( $_cookie_array['c_secondary_group'], '/-' ) );
					$custom_field_cookie	= explode( '||', $_cookie_array['c_custom_fields'] );

					if( 
						$member_type || $member_contains || $member_contains_type || $member_contains_text || $order_by || $order_direction ||
						$date_reg_from || $date_reg_to || $date_post_from || $date_post_to || $date_active_from || $date_active_to || $primary_group ||
						$secondary_group  || $post_count || $post_count_type
						)
					{
						$filters_preset = 1;
					}

					if( is_array( $custom_field_cookie ) AND count($custom_field_cookie) )
					{
						foreach( $custom_field_cookie as $field )
						{
							$data = explode( '==', $field );
							$custom_field_data[ 'field_' . $data[0] ] = $data[1];
							ipsRegistry::$request[ 'field_' . $data[0] ] = $data[1];
							
							if( $data[1] )
							{
								$filters_preset = 1;
							}
						}
					}
				}
			}
		}

		$custom_field_data = count($custom_field_data) ? $custom_field_data : $_POST;
		
		foreach( $custom_field_data as $k => $v )
		{
			if( strpos( $k, 'ignore_field_') === 0 )
			{
				$key = substr( $k, 13 );
				
				$custom_field_data[ 'field_' . $key ] = '';
			}
		}

		//-----------------------------------------
    	// Get custom profile information
    	//-----------------------------------------

    	require_once( IPS_ROOT_PATH . 'sources/classes/customfields/profileFields.php' );
    	$fields = new customProfileFields();
    	
    	$fields->member_data = $custom_field_data;
    	$fields->initData( 'edit', 1 );
    	$fields->parseToEdit();
		
		//-----------------------------------------
		// Finish forms...
		//-----------------------------------------

		$form['_member_contains']		= $this->registry->output->formDropdown( 'f_member_contains'        , $_member_contains       , $member_contains  );
		$form['_member_contains_type']	= $this->registry->output->formDropdown( 'f_member_contains_type'   , $_member_contains_type  , $member_contains_type );
		$form['_member_contains_text']	= $this->registry->output->formSimpleInput('f_member_contains_text', $member_contains_text, 15 );
		$form['_member_type']			= $this->registry->output->formDropdown( 'f_member_type'            , $_member_type  , $member_type  );
		$form['_banned_type']			= $this->registry->output->formDropdown( 'f_banned_type'            , $_banned_type  , $banned_type  );
		$form['_spam_type']		    	= $this->registry->output->formDropdown( 'f_spam_type'              , $_spam_type  , $spam_type  );
		$form['_order_by']				= $this->registry->output->formDropdown( 'order_by'                 , $_order_by       , preg_replace( "#.*\.(.*)$#", "\\1", $order_by ) );
		$form['_order_direction']		= $this->registry->output->formDropdown( 'order_direction'          , $_order_direction, $order_direction );
		$form['_search_type']			= $this->registry->output->formDropdown( 'f_search_type'            , $_search_type, $search_type );
		$form['_post_count']			= $this->registry->output->formSimpleInput('f_post_count'          , $post_count, 10 );
		$form['_post_count_type']		= $this->registry->output->formDropdown( 'f_post_count_type'        , $_post_count_types, $post_count_type );
		$form['_date_reg_from']			= $this->registry->output->formSimpleInput('f_date_reg_from'       , $date_reg_from, 10 );
		$form['_date_reg_to']			= $this->registry->output->formSimpleInput('f_date_reg_to'         , $date_reg_to, 10 );
		$form['_date_post_from']		= $this->registry->output->formSimpleInput('f_date_post_from'      , $date_post_from, 10 );
		$form['_date_post_to']			= $this->registry->output->formSimpleInput('f_date_post_to'        , $date_post_to, 10 );
		$form['_date_active_from']		= $this->registry->output->formSimpleInput('f_date_active_from'    , $date_active_from, 10 );
		$form['_date_active_to']		= $this->registry->output->formSimpleInput('f_date_active_to'      , $date_active_to, 10 );
		$form['_primary_group']			= $this->registry->output->formDropdown( 'f_primary_group'          , $_primary_group    , $primary_group );
		$form['_secondary_group']		= $this->registry->output->formDropdown( 'f_secondary_group'        , $_secondary_group  , $secondary_group );

		//-----------------------------------------
		// Set custom field data for cookie
		//-----------------------------------------
		
		$custom_field_data_imploded	= array();

		foreach( $custom_field_data as $k => $v )
		{
			if( strpos( $k, 'field_' ) === 0 )
			{
				$custom_field_data_imploded[] = substr( $k, 6 ) . '==' . $v;
			}
		}
		
		$custom_field_data_imploded = implode( '||', $custom_field_data_imploded );

		//-----------------------------------------
		// Store the cooookie
		//-----------------------------------------
		
		if (  ! $this->request['_nosave'] )
		{
			$_cookie = array( 'c_member_type'			=> $member_type,
			 				  'c_banned_type'			=> $banned_type,
			 				  'c_spam_type'				=> $spam_type,
							  'c_member_contains'		=> $member_contains,
							  'c_member_contains_type'	=> $member_contains_type,
							  'c_member_contains_text'	=> $member_contains_text,
							  'c_order_by'				=> preg_replace( "#.*\.(.*)$#", "\\1", $__order_by ),
							  'c_order_direction'		=> $__order_direction,
							  'c_post_count'			=> $post_count,
							  'c_post_count_type'		=> $post_count_type,
							  'c_date_reg_from'			=> $date_reg_from,
							  'c_date_reg_to'			=> $date_reg_to,
							  'c_date_post_from'		=> $date_post_from,
							  'c_date_post_to'			=> $date_post_to,
							  'c_date_active_from'		=> $date_active_from,
						      'c_date_active_to'		=> $date_active_to,
							  'c_primary_group'			=> $primary_group,
							  'c_secondary_group'		=> $secondary_group,
							  'c_custom_fields'			=> $custom_field_data_imploded );
		
			ipsRegistry::getClass('adminFunctions')->staffSaveCookie( 'memberFilter', $_cookie );
		}
		
		//-----------------------------------------
		// Create filter boxes
		//-----------------------------------------

		$this->registry->output->html .= $this->html->member_list_context_menu_filters( $form, $fields, $filters_preset );
	
		//-----------------------------------------
		// Return data
		//-----------------------------------------
		
		$_return = array( 'custom_fields' => '' );
		
		if( is_array( $fields->out_fields ) AND count( $fields->out_fields ) )
		{
			foreach( $fields->out_fields as $id => $data )
			{
				$_return['custom_fields'][ $id ] = $fields->in_fields[ $id ];
			}
		}

		foreach( array_keys( $form ) as $_key )
		{
			$__key = substr( $_key, 1 );
			
			$_return[ $__key ] = ${ $__key };
		}

		return $_return;
	}

}