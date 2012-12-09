<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Reports content central management
 * Last Updated: $LastChangedDate: 2009-08-31 20:32:10 -0400 (Mon, 31 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @author		Based on original "Report Center" by Luke Scott
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Core
 * @link		http://www.
 * @version		$Rev: 5066 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class public_core_reports_reports extends ipsCommand
{	
	/**
	 * Execute selected method
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @return	void
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Load basic things
		//-----------------------------------------

		$this->registry->class_localization->loadLanguageFile( array( 'public_reports' ) );

		$this->DB->loadCacheFile( IPSLib::getAppDir('core') . '/sql/' . ips_DBRegistry::getDriverType() . '_report_queries.php', 'report_sql_queries' );
		
		require_once( IPSLib::getAppDir('core') .'/sources/classes/reportLibrary.php' );
		$this->registry->setClass( 'reportLibrary', new reportLibrary( $this->registry ) );

		//-----------------------------------------
		// Check permissions...
		//-----------------------------------------
		
		$showReportCenter	= false;
		
		$this->member_group_ids	= array( $this->memberData['member_group_id'] );
		$this->member_group_ids	= array_diff( array_merge( $this->member_group_ids, explode( ',', $this->memberData['mgroup_others'] ) ), array('') );
		$report_center		= array_diff( explode( ',', $this->settings['report_mod_group_access'] ), array('') );

		foreach( $report_center as $groupId )
		{
			if( in_array( $groupId, $this->member_group_ids ) )
			{
				$showReportCenter	= true;
			}
		}
		
		if( ($this->request['do'] AND $this->request['do'] != 'report') AND !$showReportCenter )
		{
			$this->registry->output->showError( 'no_reports_permission', 2018, true );
		}
		
		$this->registry->output->setTitle( $this->lang->words['main_title'] );

		//-----------------------------------------
		// Which road are we going to take?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			default:
			case 'report':
				$this->_initReportForm();
			break;
			
			case 'showMessage':
				$this->_viewReportedMessage();
			break;
			
			case 'index':
				$this->_displayReportCenter();
			break;
			
			case 'process':
				$this->_processReports();
			break;
			
			case 'findfirst':
				$this->findFirstReport();
			break;
			
			case 'show_methods':
				$this->_showNotificationMethods();
			break;
			case 'save_methods':
				$this->_saveNotificationMethods();
			break;
			
			case 'show_report':
				$this->_displayReport();
			break;
			
			case 'save_comment':
				$this->_saveComment();
			break;
		}

		//-----------------------------------------
		// Output
		//-----------------------------------------

		$this->registry->getClass('output')->addContent( $this->output );
		$this->registry->output->sendOutput();
	}
	
	/**
	 * View a reported private message as it shows in the messenger
	 *
	 * @access	private
	 * @return	void
	 */
	private function _viewReportedMessage()
	{
		//-----------------------------------------
		// Do we have permission?
		//-----------------------------------------
		
		$this->registry->getClass('reportLibrary')->buildQueryPermissions();

		if( !in_array( $this->memberData['member_group_id'], explode( ',', $this->registry->getClass('reportLibrary')->plugins['messages']->_extra['plugi_messages_add'] ) ) )
		{
			$this->registry->getClass('output')->showError( 'no_permission_addreport', 20115 );
		}

		//-----------------------------------------
		// First see if we are already in map...
		//-----------------------------------------
		
		$topicId	= intval($this->request['topicID']);
		
		$mapRecord	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'message_topic_user_map', 'where' => "map_user_id={$this->memberData['member_id']} AND map_topic_id={$topicId}" ) );
		
		//-----------------------------------------
		// Doesn't exist?
		//-----------------------------------------
		
		if( !$mapRecord['map_user_id'] )
		{
			define( 'FROM_REPORT_CENTER', true );
			
			require_once( IPSLib::getAppDir( 'members' ) . '/sources/classes/messaging/messengerFunctions.php' );
			$messengerFunctions = new messengerFunctions( $this->registry );

			//-----------------------------------------
			// Add ourselves
			//-----------------------------------------
			
			try
			{
				$messengerFunctions->addTopicParticipants( $topicId, array( $this->memberData['members_display_name'] ), $this->memberData['member_id'] );
			}
			
			//-----------------------------------------
			// Must already be in there
			//-----------------------------------------
			
			catch( Exception $e )
			{
				
			}
		}
		
		//-----------------------------------------
		// Already a participant, make sure we're active
		//-----------------------------------------
		
		else
		{
			$update	= array();
			
			if( !$mapRecord['map_user_active'] )
			{
				$update['map_user_active']	= 1;
			}
			
			if( $mapRecord['map_folder_id'] == 'finished' )
			{
				$update['map_folder_id']	= 'myconvo';
			}
			
			if( $mapRecord['map_user_banned'] )
			{
				$update['map_user_banned']	= 0;
			}
			
			if( count($update) )
			{
				$this->DB->update( 'message_topic_user_map', $update, "map_user_id={$this->memberData['member_id']} AND map_topic_id={$topicId}" );
			}
		}

		$this->registry->output->silentRedirect( $this->settings['base_url'] . "app=members&amp;module=messaging&amp;section=view&amp;do=showConversation&amp;topicID=" . $topicId . "&amp;st=" . $this->request['st'] . "#msg" . $this->request['msg'] );
	}
	
	/**
	 * Main function for displaying reports in a list
	 *
	 * @access	private
	 * @return	void
	 */
	private function _displayReportCenter()
	{
		//-----------------------------------------
		// Check for rss key and if none make one
		//-----------------------------------------
		
		$this->registry->getClass('reportLibrary')->checkMemberRSSKey();

		//-----------------------------------------
		// Basic title and nav routine..
		//-----------------------------------------
	
		$this->registry->output->addNavigation( $this->lang->words['main_title'], 'app=core&amp;module=reports&amp;do=index' );
		
		//-----------------------------------------
		// We need some extra permisisons sql..
		//-----------------------------------------
		
		$COM_PERM = $this->registry->getClass('reportLibrary')->buildQueryPermissions();
		
		$reports		= array();

		//-----------------------------------------
		// Show me the money! err.. Reports!
		//-----------------------------------------
		
		$total = $this->DB->buildAndFetch( array(
														'select'	=> 'COUNT(*) as reports',
														'from'		=> array( 'rc_reports_index' => 'rep' ),
														'where'		=> $COM_PERM,
														'add_join'	=> array(
																			array(
																				'from'	=> array( 'rc_classes' => 'rcl' ),
																				'where'	=> 'rcl.com_id=rep.rc_class'
																				)
																			)
												)		);


		
		$this->DB->buildFromCache( 'reports_index', array( 'WHERE' => $COM_PERM, 'START' => intval($this->request['st']), 'LIMIT' => 10 ), 'report_sql_queries' );
		$res = $this->DB->execute();
		
		while( $row = $this->DB->fetch($res) )
		{
			$sec_data			= $this->registry->getClass('reportLibrary')->plugins[$row['my_class']]->giveSectionLinkTitle( $row );
			$sec_data['url']	= $this->registry->getClass('reportLibrary')->processUrl( $sec_data['url'], $sec_data['seo_title'], $sec_data['seo_template'] );
			$row['points']		= isset( $row['points'] ) ? $row['points'] :  $this->settings['_tmpPoints'][ $row['id'] ];
			$row['section']		= $sec_data;
			$row['status_icon']	= $this->_buildStatusIcon( $row );
			
			$reports[ $row['id'] ]	= $row;
		}
		
		//-----------------------------------------
		// Statuses
		//-----------------------------------------
		
		$stats	= array();
		$_tmp	= $this->registry->getClass('reportLibrary')->flag_cache;
		
		// Manually build array get just the statuses, not severities
		foreach( $_tmp as $sid => $sta )
		{
			if( is_array( $sta ) && count( $sta ) )
			{
				foreach( $sta as $points => $info )
				{
					if( $stats[ $sid ] )
					{
						break;
					}
					
					$stats[ $sid ] = $info;
				}
			}
		}

		//-----------------------------------------
		// Display Page Navigation
		//-----------------------------------------

		$pages = $this->registry->output->generatePagination( array( 'totalItems'			=> $total['reports'],
																		'itemsPerPage'		=> 10,
																		'currentStartValue'	=> $this->request['st'],
																		'baseUrl'			=> 'app=core&amp;module=reports&amp;do=index'
									  )
							   );
		
		$this->output .= $this->registry->getClass('output')->getTemplate('reports')->reportsIndex( $reports, $this->registry->getClass('reportLibrary')->buildStatuses(), $pages, $this->_getStats( 1 ), $stats );
	}
	
	/**
	 * Basic functions for processing actions on 'Report Index' page (Drop Down)
	 *
	 * @access	private
	 * @return	void
	 */
	private function _processReports()
	{
		//-----------------------------------------
		// Check form key
		//-----------------------------------------

        if ( $this->request['k'] != $this->member->form_hash )
        {
        	$this->registry->getClass('output')->showError( 'no_permission', 20112 );
        }

		//-----------------------------------------
		// Are we pruning?
		//-----------------------------------------

		if( is_numeric($this->request['pruneDays']) && $this->request['newstatus'] == 'p' )
		{
			if( !$this->memberData['g_access_cp'] )
			{
				$this->registry->output->showError( 'no_report_prune_perm', 2019, true );
			}

			//-----------------------------------------
			// Let's prune those reports.. if we can
			//-----------------------------------------
		
			$prune_time		= ceil(time() - (intval($this->request['pruneDays']) * 86400));
			$total_pruned	= $this->_pruneReports( $prune_time );
			
			if( $total_pruned )
			{
				$this->registry->output->redirectScreen( $this->lang->words['report_prune_message_done'],  $this->settings['base_url'] . "app=core&module=reports&do=index&st=" . $this->request['st'] );
			}
			else
			{
				$this->registry->output->redirectScreen( $this->lang->words['report_prune_message_none'],  $this->settings['base_url'] . "app=core&module=reports&do=index&st=" . $this->request['st'] );
			}
		}
		
		//-----------------------------------------
		// Either deleting or updating status?
		//-----------------------------------------
		
		elseif( $this->request['report_ids'] && is_array($this->request['report_ids']) )
		{
			$ids	= implode( ',', IPSLib::cleanIntArray( $this->request['report_ids'] ) );

			if( strlen($ids) > 0 && ( ! preg_match( "/[^0-9,]/", $ids ) ) )
			{
				if( $this->request['newstatus'] == 'd' )
				{
					if( !$this->memberData['g_access_cp'] )
					{
						$this->registry->output->showError( 'no_report_prune_perm', 20110, true );
					}

					//-----------------------------------------
					// Time to delete some stuff!
					//-----------------------------------------
		
					$this->_deleteReports( $ids, true );
					$this->registry->getClass('reportLibrary')->updateCacheTime();
					
					$this->registry->output->redirectScreen( $this->lang->words['redirect_delete_report'],  $this->settings['base_url'] . "app=core&module=reports&do=index&st=" . $this->request['st'] );
				}
				else
				{
					//----------------------------------------------
					// Change the status of these reports...
					//----------------------------------------------
		
					$build_update = array(
										'status'		=> intval($this->request['newstatus']),
										'date_updated'	=> time(),
										'updated_by'	=> $this->memberData['member_id'],
										);
					
					$this->DB->update( 'rc_reports_index', $build_update, "id IN({$ids})" );
					
					$this->registry->getClass('reportLibrary')->updateCacheTime();
					
					$this->registry->output->redirectScreen( $this->lang->words['redirect_mark_status'],  $this->settings['base_url'] . "app=core&module=reports&do=index&st=" . $this->request['st'] );
				}
			}
		}
		
		//-----------------------------------------
		// If we're still here show an error
		//-----------------------------------------
		
		if( !$this->memberData['g_access_cp'] )
		{
			$this->registry->output->showError( 'no_report_none_perm', 10131 );
		}
		else
		{
			$this->registry->output->silentRedirect( $this->settings['base_url'] . "app=core&module=reports&do=index" );
		}
	}
	
	/**
	 * Finds first post reported using topic-id
	 *
	 * @access	public
	 * @return	void
	 */
	public function findFirstReport()
	{
		$this->registry->getClass('reportLibrary')->buildStatuses( true );
		
		$tid = intval($this->request['tid']);
		$cid = intval($this->request['cid']);
		
		if( $tid < 1 || $cid < 1 )
		{
			$this->registry->output->showError( 'reports_need_tidcid', 10132 );
		}
		
		$row = $this->DB->buildAndFetch( array( 'select' => 'exdat2, exdat3', 'from' => 'rc_reports_index', 'where' => "exdat2={$tid} AND rc_class={$cid} AND status!={$this->registry->getClass('reportLibrary')->report_is_complete}", 'order' => "exdat2 asc", 'limit' => 1 ) );
		
		if( !$row['exdat2'] )
		{
			$this->registry->output->showError( 'reports_no_topic', 10133 );
		}

		$this->registry->output->silentRedirect( $this->settings['base_url'] . "showtopic={$row['exdat2']}&view=findpost&p={$row['exdat3']}" );
	}
	
	/**
	 * Main function for making reports and uses the custom plugins
	 *
	 * @access	private
	 * @return	void
	 */
	private function _initReportForm()
	{
		//-----------------------------------------
		// Make sure we have an rcom
		//-----------------------------------------
		
		$rcom = IPSText::alphanumericalClean($this->request['rcom']);

		if( !$rcom )
		{
			$this->registry->output->showError( 'reports_what_now', 10134 );
		}
		
		//-----------------------------------------
		// Request plugin info from database
		//-----------------------------------------

		$row = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'rc_classes', 'where' => "my_class='{$rcom}' AND onoff=1" ) );
		
		if( !$row['com_id'] )
		{
			$this->registry->output->showError( 'reports_what_now', 10135 );
		}
		else
		{
			//-----------------------------------------
			// Can this group report this type of page?
			//-----------------------------------------
			
			if( $row['my_class'] == '' || count( array_diff($this->member_group_ids, explode(',', $row['group_can_report'])) ) >= count( $this->member_group_ids ) )
			{
				$this->registry->output->showError( 'reports_cant_report', 10136 );
			}
			
			require_once( IPSLib::getAppDir('core') . '/sources/classes/reportNotifications.php' );
			
			$notify = new reportNotifications( $this->registry );
			
			//-----------------------------------------
			// Let's get cooking! Load the plugin
			//-----------------------------------------
			
			$this->registry->getClass('reportLibrary')->loadPlugin( $row['my_class'] );
			
			//-----------------------------------------
			// Process 'extra data' for the plugin
			//-----------------------------------------
			
			if( $row['extra_data'] && $row['extra_data'] != 'N;' )
			{
				$this->registry->getClass('reportLibrary')->plugins[ $row['my_class'] ]->_extra = unserialize( $row['extra_data'] );
			}
			else
			{
				$this->registry->getClass('reportLibrary')->plugins[ $row['my_class'] ]->_extra = array();
			}
			
			$send_code = intval($this->request['send']);
			
			if( $send_code == 0 )
			{
				//-----------------------------------------
				// Request report form from plugin
				//-----------------------------------------
				
				$this->output .= $this->registry->getClass('reportLibrary')->plugins[ $row['my_class'] ]->reportForm( $row );
			}
			else
			{
				//-----------------------------------------
				// Form key not valid
				//-----------------------------------------
				
				if ( $this->request['k'] != $this->member->form_hash )
				{
					$this->registry->getClass('output')->showError( 'no_permission', 20114 );
				}

				//-----------------------------------------
				// Empty report
				//-----------------------------------------
				
				if( !trim(strip_tags($this->request['message'])) )
				{
					$this->registry->output->showError( 'reports_cant_empty', 10181 );
				}

				//-----------------------------------------
				// Sending report... do necessary things
				//-----------------------------------------
				
				$report_data = $this->registry->getClass('reportLibrary')->plugins[ $row['my_class'] ]->processReport( $row );
				
				$this->registry->getClass('reportLibrary')->updateCacheTime();
				
				//-----------------------------------------
				// Send out notfications...
				//-----------------------------------------
				
				$notify->initNotify( $this->registry->getClass('reportLibrary')->plugins[ $row['my_class'] ]->getNotificationList( substr( $row['mod_group_perm'], 1, strlen($row['mod_group_perm']) - 2), $report_data ), $report_data );
				$notify->sendNotifications();
				
				//-----------------------------------------
				// Redirect...
				//-----------------------------------------
				
				$this->registry->getClass('reportLibrary')->plugins[ $row['my_class'] ]->reportRedirect( $report_data );
			}
		}
	}
	
	/**
	 * Displays notification options
	 *
	 * @access	private
	 * @return	void
	 */
	private function _showNotificationMethods()
	{
		//-----------------------------------------
		// Basic title and nav routine..
		//-----------------------------------------
	
		$this->registry->output->addNavigation( $this->lang->words['main_title'], 'app=core&amp;module=reports&amp;do=index' );

		$row = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'rc_modpref', 'where' => "mem_id='{$this->memberData['member_id']}'" ) );

		$this->output .= $this->registry->getClass('output')->getTemplate('reports')->config_notif( $row );
	}
	
	/**
	 * Saves notification options
	 *
	 * @access	private
	 * @return	void
	 */
	private function _saveNotificationMethods()
	{
		//-----------------------------------------
		// Check key
		//-----------------------------------------
		
        if ( $this->request['k'] != $this->member->form_hash )
        {
        	$this->registry->getClass('output')->showError( 'no_permission', 20113 );
        }
        
		$mod_prefs['by_pm']		= intval( $this->request['notmet_pm'] );
		$mod_prefs['by_email']	= intval( $this->request['notmet_email'] );
		$mod_prefs['by_alert']	= intval( $this->request['notmet_alert'] );

		$this->DB->build( array( 'select' => '*', 'from' => 'rc_modpref', 'where' => "mem_id='{$this->memberData['member_id']}'" ) );
		$this->DB->execute();
			
		if( $this->DB->getTotalRows() == 0 )
		{
			$mod_prefs['mem_id'] = $this->memberData['member_id'];

			$this->DB->insert( 'rc_modpref', $mod_prefs );
		}
		else
		{
			$this->DB->update( 'rc_modpref', $mod_prefs, "mem_id='{$this->memberData['member_id']}'" );
		}
		
		$this->registry->output->redirectScreen( $this->lang->words['reports_methods_saved'], $this->settings['base_url'] . "app=core&amp;module=reports&do=index" );
	}
	
	/**
	 * Handles ajax/non-ajax window for reports and comments linked from reports
	 *
	 * @access	private
	 * @return	boolean
	 */
	private function _displayReport()
	{
		//-----------------------------------------
		// Lets make sure this report exists...
		//-----------------------------------------
		
		$rid		= intval($this->request['rid']);
		$options	= array(
							'rid'	=> $rid
							);
		$reports	= array();
		$comments	= array();

		if( !$rid )
		{
			$this->registry->output->showError( 'reports_no_rid', 10137 );
		}
		
		$this->registry->class_localization->loadLanguageFile( array( 'public_editors' ) );
		
		$report_index = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'rc_reports_index', 'where' => "id=" . $rid ) );
		
		//-----------------------------------------
		// Basic title and nav routine..
		//-----------------------------------------

		$this->registry->output->addNavigation( $this->lang->words['main_title'], 'app=core&amp;module=reports&amp;do=index' );
		$this->registry->output->addNavigation( $report_index['title'], '' );

		if( $this->DB->getTotalRows() == 0 )
		{
			$this->registry->output->showError( 'reports_no_rid', 10138 );
		}
		
		$COM_PERM = $this->registry->getClass('reportLibrary')->buildQueryPermissions();
		
		IPSText::getTextClass('bbcode')->parse_bbcode		= 1;
		IPSText::getTextClass('bbcode')->parse_html			= 0;
		IPSText::getTextClass('bbcode')->parse_emoticons	= 1;
		IPSText::getTextClass('bbcode')->parse_nl2br		= 0;
		IPSText::getTextClass('bbcode')->parsing_section	= 'global';

		//-----------------------------------------
		// Get reports
		//-----------------------------------------

		$this->DB->buildFromCache( 'grab_report', array( 'COM' => $COM_PERM, 'rid' => $rid ), 'report_sql_queries' );
		$outer = $this->DB->execute();

		while( $row = $this->DB->fetch($outer) )
		{
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $row['member_group_id'];
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $row['mgroup_others'];
			
			$row['points']		= isset( $row['points'] ) ? $row['points'] :  $this->settings['_tmpPoints'][ $row['id'] ];
			
			if( !$options['url'] && $row['url'] )
			{
				$options['url'] = $this->registry->getClass('reportLibrary')->processUrl( $row['url'], $row['seoname'], $row['seotemplate'] );
			}
			
			if( !$options['class'] && $row['my_class'] )
			{
				$options['class'] = $row['my_class'];
			}

			if( $row['my_class'] == 'messages' && !$options['topicID'] && $row['exdat1'] )
			{
				$options['topicID'] = intval($row['exdat1']);
			}
			
			$options['title'] = $row['title'];
			$options['status_id'] = $row['status'];
			
			if( !$options['image'] && $row['img_preview'] )
			{
				$options['image'] = $this->registry->getClass('reportLibrary')->processUrl( $row['img_preview'], $row['seoname'], $row['seotemplate'] );
			}
			
			if( !$options['status_icon'] )
			{
				$options['status_icon']	= $this->_buildStatusIcon( $row );
				$options['status_text']	= $this->registry->getClass('reportLibrary')->flag_cache[ $row['status'] ][ $row['points'] ]['title'];
			}
			
			$row['report']	= IPSText::getTextClass('bbcode')->preDisplayParse( $row['report'] );
			$row['report']	= IPSText::getTextClass( 'bbcode' )->memberViewImages( $row['report'] );

			$reports[ $row['id'] ]	= $row;
		}
		
		if( !$options['class'] )
		{
			$this->registry->output->showError( 'reports_no_rid', 10138 );
		}

		$_tmp	= $this->registry->getClass('reportLibrary')->flag_cache;
		
		// Manually build array get just the statuses, not severities
		foreach( $_tmp as $sid => $sta )
		{
			if( is_array( $sta ) && count( $sta ) )
			{
				foreach( $sta as $points => $info )
				{
					if( $options['statuses'][ $sid ] )
					{
						break;
					}
					
					$options['statuses'][ $sid ] = $info;
				}
			}
		}
		
		//-----------------------------------------
		// Get comments
		//-----------------------------------------
		
		$ids = array( 0 => 0 );
		
		$this->DB->build( array( 'select'	=> 'id',
								 'from'		=> 'rc_comments',
								 'where'	=> 'rid=' . $rid,
								 'group'	=> 'id' ) );
		
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			$ids[ $row['id'] ] = $row['id'];
		}		 
				
		$this->DB->build( array(
									'select'	=> 'comm.*',
									'from'		=> array( 'rc_comments' => 'comm' ),
									'where'		=> 'comm.id IN (' . implode( ',', $ids ) . ')',
									'order'		=> 'comm.comment_date ASC',
									'add_join'	=> array(
														array(
															'select'	=> 'mem.*',
															'from'		=> array( 'members' => 'mem' ),
															'where'		=> 'mem.member_id=comm.comment_by',
															),
														array(
															'select'	=> 'grop.*,grop.g_is_supmod as iscop',
															'from'		=> array( 'groups' => 'grop' ),
															'where'		=> 'grop.g_id=mem.member_group_id',
															),
														array(
															'select'	=> 'pp.*',
															'from'		=> array( 'profile_portal' => 'pp' ),
															'where'		=> 'pp.pp_member_id=mem.member_id',
															),
														)
							)		);
		$outer = $this->DB->execute();

		while($row = $this->DB->fetch($outer))
		{
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $row['member_group_id'];
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $row['mgroup_others'];

			$row['author'] = IPSMember::buildDisplayData( $row['member_id'] );
			$row['comment']	= IPSText::getTextClass('bbcode')->preDisplayParse( $row['comment'] );
			$row['comment']	= IPSText::getTextClass( 'bbcode' )->memberViewImages( $row['comment'] );
			
			$comments[ $row['id'] ]	= $row;
		}
		
		//-----------------------------------------
		// And output
		//-----------------------------------------
		
		$this->output .= $this->registry->getClass('output')->getTemplate('reports')->viewReport( $options, $reports, $comments );
	}
	
	/**
	 * Recieves comment, submits to database, and redirects user
	 *
	 * @access	private
	 * @return	void
	 */
	private function _saveComment()
	{
		//-----------------------------------------
		// Make sure we have a report id...
		//-----------------------------------------
		
		$rid = intval($this->request['rid']);
		
		if( $rid < 1 )
		{
			$this->registry->output->showError( 'reports_no_comment', 10139 );
		}
		
		//-----------------------------------------
		// Lets make sure we even have this report
		//-----------------------------------------
		
		$report = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'rc_reports_index', 'where' => "id=" . $rid ) );

		if( $report['id'] )
		{
			$postContent = IPSText::getTextClass( 'editor' )->processRawPost( $_POST['comment'] );
			
			IPSText::getTextClass('bbcode')->parse_bbcode		= 1;
			IPSText::getTextClass('bbcode')->parse_html			= 0;
			IPSText::getTextClass('bbcode')->parse_emoticons	= 1;
			IPSText::getTextClass('bbcode')->parse_nl2br		= 1;
			IPSText::getTextClass('bbcode')->parsing_section	= 'reports';

			$postContent = IPSText::getTextClass( 'bbcode' )->preDbParse( $postContent );

			if( !trim( IPSText::br2nl($postContent) ) )
			{
				$this->registry->output->showError( 'reports_no_comment_text', 10188 );
			}

			$build_comment = array(
									'rid'			=> $rid,
									'comment'		=> $postContent,
									'comment_by'	=> $this->memberData['member_id'],
									'comment_date'	=> time()
								);
			
			$this->DB->insert( 'rc_comments', $build_comment );
			$this->DB->update( 'rc_reports_index', array( 'num_comments' => $report['num_comments'] + 1, 'date_updated' => time() ), "id=" . $report['id'] );
		}
		
		$this->registry->output->redirectScreen( $this->lang->words['report_comment_saved'], $this->settings['base_url'] . "app=core&amp;module=reports&rid={$report['id']}&do=show_report" );
	}

	/**
	 * Responsible for pruning reports. Uses the delete reports function to finish
	 *
	 * @access	private
	 * @param	integer   seconds used for pruning reports
	 * @return	void
	 */
	private function _pruneReports( $stamp )
	{
		$ids	= array();

		//--------------------------------------------------
		// Let's grab a list of reports and check stuff...
		//--------------------------------------------------
		
		$this->DB->build( array(
									'select'	=> 'rep.id',
									'from'		=> array( 'rc_reports_index' => 'rep' ),
									'where'		=> $this->registry->getClass('reportLibrary')->buildQueryPermissions() . ' AND stat.is_complete=1 And rep.date_updated<' . $stamp,
									'add_join'	=> array(
														array(
															'from'	=> array( 'rc_classes' => 'rcl' ),
															'where'	=> 'rcl.com_id=rep.rc_class'
															),
														array(
															'from'	=> array( 'rc_status' => 'stat' ),
															'where'	=> 'stat.status=rep.status'
															),
														)
							)		);
		$this->DB->execute();

		while( $row = $this->DB->fetch() )
		{
			$ids[] = $row['id'];
		}
		
		//-----------------------------------------
		// OK lets delete them! I love OOP
		//-----------------------------------------
		
		if( count($ids) )
		{
			$this->_deleteReports( implode( ',', $ids ), false );
		}
		
		return count($ids);
	}
	
	/**
	 * Responsible for deleting reports
	 *
	 * @access	private
	 * @param	string   Report IDS (#,#,#,...)
	 * @param	boolean  Security check?
	 * @return	boolean
	 */
	private function _deleteReports( $rids='', $toCheck=false )
	{
		if( $this->memberData['g_access_cp'] != 1 )
		{
			return false;
		}
		
		//-----------------------------------------
		// Lets make sure we got this right...
		//-----------------------------------------
		
		if( ! $rids || ! preg_match("/[0-9,]+/", $rids ) )
		{
			return false;
		}
		
		//-----------------------------------------
		// Are we checking security now?
		//-----------------------------------------
		
		if( $toCheck == true )
		{
			$this->DB->build( array(
										'select'	=> 'rep.id, rep.status',
										'from'		=> array( 'rc_reports_index' => 'rep' ),
										'where'		=> $this->registry->getClass('reportLibrary')->buildQueryPermissions() . ' AND rep.id IN(' . $rids . ')',
										'add_join'	=> array(
															array(
																'from'	=> array( 'rc_classes' => 'rcl' ),
																'where'	=> 'rcl.com_id=rep.rc_class'
																)
															)
								)		);
			$res = $this->DB->execute();
			$num = $this->DB->getTotalRows();

			if( count( explode( ',' , $rids ) ) != $this->DB->getTotalRows() )
			{
				$this->registry->output->showError( 'reports_like_whoa', 20111, true );
			}
		}
		
		//-----------------------------------------
		// Time to call for the good ol' shredder
		//-----------------------------------------
		
		$this->DB->delete( 'rc_reports_index', 'id IN(' . $rids . ')' );
		$this->DB->delete( 'rc_reports', 'rid IN(' . $rids . ')' );
		$this->DB->delete( 'rc_comments', 'rid IN(' . $rids . ')' );
		
		//-----------------------------------------
		// I think we should update the numbers..
		//-----------------------------------------
		
		$this->registry->getClass('reportLibrary')->updateCacheTime();
		
		return true;
	}

	/**
	 * Returns the correct status icon / flag to display for a report row
	 *
	 * @access	private
	 * @param	array    Report Row
	 * @return	string
	 */
	private function _buildStatusIcon( $row )
	{
		$this->registry->getClass('reportLibrary')->buildStatuses( true );
		
		//-----------------------------------------
		// Pick the right flag.. or else!
		//-----------------------------------------

		$row['img']		= str_replace( '<#IMG_DIR#>', $this->registry->output->skin['set_image_dir'], $this->registry->getClass('reportLibrary')->flag_cache[ $row['status'] ][ $row['points'] ]['img'] );
		$row['width']	= $this->registry->getClass('reportLibrary')->flag_cache[ $row['status'] ][ $row['points'] ]['width'];
		$row['height']	= $this->registry->getClass('reportLibrary')->flag_cache[ $row['status'] ][ $row['points'] ]['height'];
		$row['is_png']	= $this->registry->getClass('reportLibrary')->flag_cache[ $row['status'] ][ $row['points'] ]['is_png'];

		//-----------------------------------------
		// Image? PNG? Using 'Is-Evil' machine?
		//-----------------------------------------
		
		if( $row['img'] != '' )
		{
			return $this->registry->getClass('output')->getTemplate('reports')->statusIcon( $row['img'], $row['width'], $row['height'] );
		}
		else
		{
			return '&nbsp;';
		}
	}

	/**
	 * Builds the "who's viewing" strip
	 *
	 * @access	private
	 * @param	integer  Section ID
	 * @return	string
	 */
	private function _getStats( $id=1 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$ar_time = time();
		$cached = array();
		$guests = array();
		$active = array( 'guests' => 0, 'anon' => 0, 'members' => 0, 'names' => array() );
		$rows   = array( $ar_time => array( 'login_type'   => substr($this->memberData['login_anonymous'],0, 1),
											'running_time' => $ar_time,
											'id'		   => $this->member->session_id,
											'seo_name'     => $this->memberData['members_seo_name'],
											'member_id'	   => $this->memberData['member_id'],
											'member_name'  => $this->memberData['members_display_name'],
											'member_group' => $this->memberData['member_group_id'] ) );
		
		//-----------------------------------------
		// Process users active in this forum
		//-----------------------------------------
		
		if ($this->settings['no_au_topic'] != 1)
		{	
			//-----------------------------------------
			// Get the users
			//-----------------------------------------
			
			$cut_off = ($this->settings['au_cutoff'] != "") ? $this->settings['au_cutoff'] * 60 : 900;

			$this->DB->build( array( 
									'select' => 's.member_id, s.member_name, s.member_group, s.id, s.login_type, s.location, s.running_time, s.uagent_type, s.current_module, s.seo_name',
									'from'	 => 'sessions s',
									'where'	=> "current_appcomponent='core' AND current_module='reports' AND running_time > $cut_off",
						)		);	 
			$this->DB->execute();
					   
			//-----------------------------------------
			// FETCH...
			//-----------------------------------------
			
			while ($r = $this->DB->fetch() )
			{
				$rows[ $r['running_time'].'.'.$r['id'] ] = $r;
			}
			
			krsort( $rows );

			//-----------------------------------------
			// PRINT...
			//-----------------------------------------
			
			foreach( $rows as $result )
			{
				$result['member_name'] = IPSLib::makeNameFormatted( $result['member_name'], $result['member_group'] );
				
				$last_date = $this->registry->class_localization->getTime( $result['running_time'] );
				
				if ( $result['member_id'] == 0 OR ! $result['member_name'] )
				{
					if ( in_array( $result['id'], $guests ) )
					{
						continue;
					}
					
					//-----------------------------------------
					// Bot?
					//-----------------------------------------

					if ( $result['uagent_type'] == 'search' )
					{
						//-----------------------------------------
						// Seen bot of this type yet?
						//-----------------------------------------

						if ( ! $cached[ $result['member_name'] ] )
						{
							if ( $this->settings['spider_anon'] )
							{
								if ( $this->memberData['g_access_cp'] )
								{
									$active['names'][] = array( 'id' => $result['member_id'], 'name' => $result['member_name'], 'seo' => $result['seo_name'] );
								}
							}
							else
							{
								$active['names'][] = array( 'id' => $result['member_id'], 'name' => $result['member_name'], 'seo' => $result['seo_name'] );
							}

							$cached[ $result['member_name'] ] = 1;
						}
						else
						{
							$active['guests']++;
							$guests[] = $result['id'];
						}
					}
					else
					{
						$active['guests']++;
						$guests[] = $result['id'];
					}
				}
				else
				{
					if (empty( $cached[ $result['member_id'] ] ) )
					{
						$cached[ $result['member_id'] ] = 1;
						
						$p_start = "";
						$p_end   = "";
						$p_title = sprintf( $this->lang->words['au_reading'], $last_date );
						
						if ( strstr( $result['current_module'], 'post' ) and $result['member_id'] != $this->memberData['member_id'] )
						{
							$p_start = "<span class='activeuserposting'>";
							$p_end   = "</span>";
							$p_title = sprintf( $this->lang->words['au_posting'], $last_date );
						}
						
						if ( ! $this->settings['disable_anonymous'] AND $result['login_type'] )
						{
							if ( $this->memberData['g_access_cp'] and ($this->settings['disable_admin_anon'] != 1) )
							{
								$active['names'][] = array( 'id' => $result['member_id'], 'name' => $result['member_name'] . '*', 'p_start' => $p_start, 'p_title' => $p_title, 'p_end' => $p_end, 'seo' => $result['seo_name'] );
								$active['anon']++;
							}
							else
							{
								$active['anon']++;
							}
						}
						else
						{
							$active['members']++;
							$active['names'][] = array( 'id' => $result['member_id'], 'name' => $result['member_name'], 'p_start' => $p_start, 'p_title' => $p_title, 'p_end' => $p_end, 'seo' => $result['seo_name'] );
						}
					}
				}
			}
						
			//$active['active_users_title']   = sprintf( $this->lang->words['active_users_title']  , ($active['members'] + $active['guests'] + $active['anon'] ) );
			//$active['active_users_detail']  = sprintf( $this->lang->words['active_users_detail'] , $active['guests'],$active['anon'] );
			$active['active_users_members'] = sprintf( $this->lang->words['active_users_members'], $active['members'] );
			
			return $active;
		}
	}
}