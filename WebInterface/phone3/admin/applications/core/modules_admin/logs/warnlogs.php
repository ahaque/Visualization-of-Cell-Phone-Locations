<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Warn logs
 * Last Updated: $LastChangedDate: 2009-08-18 03:26:21 -0400 (Tue, 18 Aug 2009) $
 *
 * @author 		$Author: matt $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @since		27th January 2004
 * @version		$Rev: 5023 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_core_logs_warnlogs extends ipsCommand 
{
	/**
	 * Skin object
	 *
	 * @access	private
	 * @var		object			Skin templates
	 */
	private $html;

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
		
		$this->html			= $this->registry->output->loadTemplate('cp_skin_warnlogs');
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=logs&amp;section=warnlogs';
		$this->form_code_js	= $this->html->form_code_js	= 'module=logs&section=warnlogs';
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_logs' ) );
		
		//-----------------------------------------
		// Fix up navigation bar
		//-----------------------------------------
		
		$this->registry->output->core_nav		= array();
		$this->registry->output->ignoreCoreNav	= true;
		$this->registry->output->core_nav[]		= array( $this->settings['base_url'] . 'module=tools', $this->lang->words['nav_toolsmodule'] );
		$this->registry->output->core_nav[]		= array( $this->settings['base_url'] . 'module=tools&section=logsSplash', $this->lang->words['nav_logssplash'] );
		$this->registry->output->core_nav[]		= array( $this->settings['base_url'] . 'module=logs&section=warnlogs', $this->lang->words['wlog_warn_logs'] );
		
		///----------------------------------------
		// What to do...
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'view':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'warnlogs_view' );
				$this->_view();
			break;
			
			case 'viewcontact':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'warnlogs_view' );
				$this->_viewContact();
			break;
			
			case 'viewnote':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'warnlogs_view' );
				$this->_viewNote();
			break;
				
			case 'remove':
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'warnlogs_delete' );
				$this->_remove();
			break;

			default:
				$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'warnlogs_view' );
				$this->_listCurrent();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();	
	}
	
	/**
	 * View all logs for a given moderator
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _view()
	{
		///----------------------------------------
		// Basic init
		//-----------------------------------------
		
		$start = intval($this->request['st']) >= 0 ? intval($this->request['st']) : 0;

		///----------------------------------------
		// No mid or search string?
		//-----------------------------------------
		
		if ( !$this->request['search_string'] AND !$this->request['mid'] )
		{
			$this->registry->output->global_message = $this->lang->words['wlog_nostring'];
			$this->_listCurrent();
			return;
		}
		
		///----------------------------------------
		// mid?
		//-----------------------------------------
		
		if ( !$this->request['search_string'] )
		{
			$row = $this->DB->buildAndFetch( array( 'select' => 'COUNT(wlog_id) as count', 'from' => 'warn_logs', 'where' => "wlog_mid=".intval($this->request['mid']) ) );

			$row_count = $row['count'];
			
			$query = "&amp;{$this->form_code}&amp;mid=" . $this->request['mid'] . "&amp;do=view";
			
			$this->DB->build( array( 'select'		=> 'l.*',
											'from'		=> array( 'warn_logs' => 'l' ),
											'where'		=> 'l.wlog_mid=' . intval($this->request['mid']),
											'order'		=> 'l.wlog_date DESC',
											'limit'		=> array( $start, 30 ),
											'add_join'	=> array(
																array( 'select'	=> 'm.member_id as a_id, m.members_display_name as a_name',
																		'from'	=> array( 'members' => 'm' ),
																		'where'	=> 'm.member_id=l.wlog_mid',
																		'type'	=> 'left'
																	),
																array( 'select'	=> 'p.member_id as p_id, p.members_display_name as p_name',
																		'from'	=> array( 'members' => 'p' ),
																		'where'	=> 'p.member_id=l.wlog_addedby',
																		'type'	=> 'left'
																	),
																)
								)		);
			$this->DB->execute();
		}
		else
		{
			$this->request[ 'search_string'] =  IPSText::parseCleanValue( urldecode($this->request['search_string'] ) );
			
			if ( ($this->request['search_type'] == 'notes') )
			{
				$dbq = "l.wlog_notes LIKE '%" . $this->request['search_string'] . "%'";
			}
			else
			{
				$dbq = "l.wlog_contact_content LIKE '%" . $this->request['search_string'] . "%'";
			}
			
			$row = $this->DB->buildAndFetch( array( 'select' => 'COUNT(l.wlog_id) as count', 'from' => 'warn_logs l', 'where' => $dbq ) );

			$row_count = $row['count'];
			
			$query = "&amp;{$this->form_code}&amp;do=view&amp;search_type=" . $this->request['search_type'] . "&amp;search_string=" . urlencode($this->request['search_string']);
			
			$this->DB->build( array( 'select'		=> 'l.*',
											'from'		=> array( 'warn_logs' => 'l' ),
											'where'		=> $dbq,
											'order'		=> 'l.wlog_date DESC',
											'limit'		=> array( $start, 30 ),
											'add_join'	=> array(
																array( 'select'	=> 'm.member_id as a_id, m.members_display_name as a_name',
																		'from'	=> array( 'members' => 'm' ),
																		'where'	=> 'm.member_id=l.wlog_mid',
																		'type'	=> 'left'
																	),
																array( 'select'	=> 'p.member_id as p_id, p.members_display_name as p_name',
																		'from'	=> array( 'members' => 'p' ),
																		'where'	=> 'p.member_id=l.wlog_addedby',
																		'type'	=> 'left'
																	),
																)
								)		);
			$this->DB->execute();
		}
		
		///----------------------------------------
		// Page links
		//-----------------------------------------
		
		$links = $this->registry->output->generatePagination( array( 'totalItems'			=> $row_count,
																	 'itemsPerPage'			=> 20,
																	 'currentStartValue'	=> $start,
																	 'baseUrl'				=> $this->settings['base_url'] . $query,
														)
												 );

		///----------------------------------------
		// Get teh results
		//-----------------------------------------
		
		$days = array( 'd' => $this->lang->words['wlog_days'], 'h' => $this->lang->words['wlog_hours'] );

		while ( $row = $this->DB->fetch() )
		{
			///----------------------------------------
			// Basics
			//-----------------------------------------

			$row['_a_name']		= $row['a_name'] ? $row['a_name'] : sprintf( $this->lang->words['wlog_deleted'], $row['wlog_mid'] );
			$row['_date']		= $this->registry->class_localization->getDate( $row['wlog_date'], 'LONG' );

			$row['_type']		= ( $row['wlog_type'] == 'pos' )		? '<span style="color:green;font-weight:bold">-</span>' : '<span style="color:red;font-weight:bold">+</span>';
			$row['_cont']		= ( $row['wlog_contact'] !=  'none' )	? "<a href='#' onclick='return acp.openWindow(\"{$this->settings['base_url']}&{$this->form_code}&do=viewcontact&id={$row['wlog_id']}\", 400,400, \"{$this->lang->words['wlog_log']}\"); return false;'>{$this->lang->words['wlog_view']}</a>" : '&nbsp;';
			
			///----------------------------------------
			// Mod Q
			//-----------------------------------------
			
			$mod				= preg_match( "#<mod>(.+?)</mod>#is"        , $row['wlog_notes'], $mm );
			$mod				= trim($mm[1]);
			list($v, $u, $i)	= explode( ',', $mod);
			
			$row['_mod']		= ( $i == 1 ) ? 'INDEF' : ( $v == "" ? 'None' : $v . ' ' . $days[$u] );
			
			///----------------------------------------
			// Susp
			//-----------------------------------------
			
			$susp				= preg_match( "#<susp>(.+?)</susp>#is"      , $row['wlog_notes'], $sm );
			$susp				= trim($sm[1]);
			list($v, $u)		= explode( ',', $susp );
			$row['_susp']		= $v == '' ? 'None' : $v . ' ' . $days[$u];

			///----------------------------------------
			// Posting Susp
			//-----------------------------------------

			$post				= preg_match( "#<post>(.+?)</post>#is"      , $row['wlog_notes'], $pm );
			$post				= trim($pm[1]);
			list($v, $u, $i)	= explode( ',', $post );
			
			$row['_post']		= ( $i == 1 ) ? 'INDEF' : ( $v == "" ? 'None' : $v . ' ' . $days[$u] );

			//-----------------------------------------
			
			$rows[] 			= $row;
		}
		
		///----------------------------------------
		// And output
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->warnlogsView( $rows, $links );
	}
	
	/**
	 * Remove logs by a moderator
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _remove()
	{
		if ( $this->request['mid'] == "" )
		{
			$this->registry->output->showError( $this->lang->words['wlog_nofind'], 11133 );
		}
		
		$this->DB->delete( 'warn_logs', "wlog_mid=" . intval($this->request['mid']) );
		
		$this->registry->getClass('adminFunctions')->saveAdminLog( sprintf( $this->lang->words['wlog_removelogs'], $this->request['mid'] ) );
		
		$this->registry->output->silentRedirect( $this->settings['base_url']."&{$this->form_code}" );	
	}
	
	/**
	 * List the current logs with links to view per-admin
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _listCurrent()
	{
		$rows			= array();
		$members		= array();

		//-----------------------------------------
		// VIEW LAST 5
		//-----------------------------------------
		
		$this->DB->build( array( 'select'		=> 'l.*',
										'from'		=> array( 'warn_logs' => 'l' ),
										'order'		=> 'l.wlog_date DESC',
										'limit'		=> array( 0, 10 ),
										'add_join'	=> array(
															array( 'select'	=> 'm.member_id as a_id, m.members_display_name as a_name',
																	'from'	=> array( 'members' => 'm' ),
																	'where'	=> 'm.member_id=l.wlog_mid',
																	'type'	=> 'left'
																),
															array( 'select'	=> 'p.member_id as p_id, p.members_display_name as p_name',
																	'from'	=> array( 'members' => 'p' ),
																	'where'	=> 'p.member_id=l.wlog_addedby',
																	'type'	=> 'left'
																),
															)
							)		);
		$this->DB->execute();

		while ( $row = $this->DB->fetch() )
		{
			$row['_a_name']		= $row['a_name'] ? $row['a_name'] : sprintf( $this->lang->words['wlog_deleted'], $row['wlog_mid'] );
			$row['_date']		= $this->registry->class_localization->getDate( $row['wlog_date'], 'LONG' );
			$row['_type']		= ( $row['wlog_type'] == 'pos' ) ? '<span style="color:green;font-weight:bold">-</span>' : '<span style="color:red;font-weight:bold">+</span>';
			$row['_cont']		= ( $row['wlog_contact'] !=  'none' ) ? "<a title='{$this->lang->words['wlog_showmessage']}' href='#' onclick='return acp.openWindow(\"{$this->settings['base_url']}&{$this->form_code}&do=viewcontact&id={$row['wlog_id']}\",400,400,\"{$this->lang->words['wlog_log']}\"); return false;'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/tick.png' border='0' alt='{$this->lang->words['wlog_contacted']}'></a>" : '&nbsp;';
			
			$rows[]				= $row;
		}

		//-----------------------------------------
		// All members
		//-----------------------------------------
		
		$this->DB->build( array( 'select'		=> 'l.*, count(l.wlog_mid) as act_count',
										'from'		=> array( 'warn_logs' => 'l' ),
										'order'		=> 'act_count DESC',
										'group'		=> 'l.wlog_mid',
										'add_join'	=> array(
															array( 'select'	=> ' m.members_display_name',
																	'from'	=> array( 'members' => 'm' ),
																	'where'	=> 'm.member_id=l.wlog_mid',
																	'type'	=> 'left'
																),
															)
							)		);
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			$r['members_display_name'] = $r['members_display_name'] ? $r['members_display_name'] : sprintf( $this->lang->words['wlog_deleted'], $r['wlog_mid'] );
			
			$members[] = $r;
		}

		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->warnlogsWrapper( $rows, $members );
	}
	
	
	/**
	 * Popup window to view a note
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _viewNote()
	{
		if ( $this->request['id'] == "" )
		{
			$this->registry->output->showError( $this->lang->words['wlog_nologs'], 11134 );
		}

		$id = intval($this->request['id']);
		
		$row = $this->DB->buildAndFetch( array( 'select'		=> 'l.*',
														'from'		=> array( 'warn_logs' => 'l' ),
														'where'		=> 'l.wlog_id=' . $id,
														'add_join'	=> array(
																			array( 'select'	=> 'm.member_id as a_id, m.members_display_name as a_name',
																					'from'	=> array( 'members' => 'm' ),
																					'where'	=> 'm.member_id=l.wlog_mid',
																					'type'	=> 'left'
																				),
																			array( 'select'	=> 'p.member_id as p_id, p.members_display_name as p_name, p.member_group_id, p.mgroup_others',
																					'from'	=> array( 'members' => 'p' ),
																					'where'	=> 'p.member_id=l.wlog_addedby',
																					'type'	=> 'left'
																				),
																			)
											)		);

		if ( ! $row['wlog_id'] )
		{
			$this->registry->output->showError( $this->lang->words['wlog_cantresolve'], 11135 );
		}

		$content = preg_match( "#<content>(.+?)</content>#is", $row['wlog_notes'], $cont );

		$row['_date']		= $this->registry->class_localization->getDate( $row['wlog_date'], 'LONG' );
		$row['a_name']		= $row['a_name'] ? $row['a_name'] : 'Deleted Member (ID:' . $row['wlog_mid'] . ')';
		
		IPSText::getTextClass('bbcode')->parse_bbcode				= 1;
		IPSText::getTextClass('bbcode')->parse_smilies				= 1;
		IPSText::getTextClass('bbcode')->parse_html					= 0;
		IPSText::getTextClass('bbcode')->parse_nl2br				= 1;
		IPSText::getTextClass('bbcode')->parsing_section			= 'warn';
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $row['member_group_id'];
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $row['mgroup_others'];
		
		$row['_text']		= IPSText::getTextClass('bbcode')->preDisplayParse( $cont[1] );

		$this->registry->output->html .= $this->html->warnlogsNote( $row );
		
		$this->registry->output->printPopupWindow();
	}
	
	/**
	 * Popup window to view a contact
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _viewContact()
	{
		if ( $this->request['id'] == "" )
		{
			$this->registry->output->showError( $this->lang->words['wlog_nologs'], 11136 );
		}
		
		$id = intval($this->request['id']);
		
		$row = $this->DB->buildAndFetch( array( 'select'		=> 'l.*',
														'from'		=> array( 'warn_logs' => 'l' ),
														'where'		=> 'l.wlog_id=' . $id,
														'add_join'	=> array(
																			array( 'select'	=> 'm.member_id as a_id, m.members_display_name as a_name',
																					'from'	=> array( 'members' => 'm' ),
																					'where'	=> 'm.member_id=l.wlog_mid',
																					'type'	=> 'left'
																				),
																			array( 'select'	=> 'p.member_id as p_id, p.members_display_name as p_name, p.member_group_id, p.mgroup_others',
																					'from'	=> array( 'members' => 'p' ),
																					'where'	=> 'p.member_id=l.wlog_addedby',
																					'type'	=> 'left'
																				),
																			)
											)		);

		if ( ! $row['wlog_id'] )
		{
			$this->registry->output->showError( $this->lang->words['wlog_nologs'], 11137 );
		}
		
		$subject = preg_match( "#<subject>(.+?)</subject>#is", $row['wlog_contact_content'], $subj );
		$content = preg_match( "#<content>(.+?)</content>#is", $row['wlog_contact_content'], $cont );
		
		$row['_type']		= $row['wlog_contact'] == 'pm' ? $this->lang->words['wlog_pm'] : $this->lang->words['wlog_email'];
		$row['_subject']	= $subj[1];
		$row['_date']		= $this->registry->class_localization->getDate( $row['wlog_date'], 'LONG' );
		$row['a_name']		= $row['a_name'] ? $row['a_name'] : sprintf( $this->lang->words['wlog_deleted'], $row['wlog_mid'] );

		$row['_text']		= $cont[1];
		
		IPSText::getTextClass( 'bbcode' )->parse_smilies			= 1;
		IPSText::getTextClass( 'bbcode' )->parse_html				= 0;
		IPSText::getTextClass( 'bbcode' )->parse_bbcode				= 1;
		IPSText::getTextClass( 'bbcode' )->parsing_section			= 'warn';
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $row['member_group_id'];
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $row['mgroup_others'];
		
		$row['_text']		= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $row['_text'] );
		$row['_text']		= $this->registry->output->replaceMacros( $row['_text'] );

		$this->registry->output->html .= $this->html->warnlogsContact( $row );
		
		$this->registry->output->printPopupWindow();
	}
	
}