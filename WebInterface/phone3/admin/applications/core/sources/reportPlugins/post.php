<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Report Center :: Posts plugin
 * Last Updated: $LastChangedDate: 2009-07-09 22:20:10 -0400 (Thu, 09 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @author		Based on original "Report Center" by Luke Scott
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Core
 * @link		http://www.
 * @version		$Rev: 4864 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class post_plugin
{
	/**#@+
	 * Registry objects
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $cache;
	/**#@-*/
	
	/**
	 * Holds extra data for the plugin
	 *
	 * @access	private
	 * @var		array			Data specific to the plugin
	 */
	public $_extra;
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @return	void
	 */
	public function __construct( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Make object
		//-----------------------------------------
		
		$this->registry = $registry;
		$this->DB	    = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache	= $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
		$this->lang		= $this->registry->class_localization;
		
		require_once( IPSLib::getAppDir( 'forums' ) . '/app_class_forums.php' );
		$app_class_forums		= new app_class_forums( $this->registry );
	}
	
	/**
	 * Display the form for extra data in the ACP
	 *
	 * @access	public
	 * @param	array 		Plugin data
	 * @param	object		HTML object
	 * @return	string		HTML to add to the form
	 */
	public function displayAdminForm( $plugin_data, &$html )
	{
		return $html->addRow(	$this->lang->words['r_supermod'],
								sprintf(  $this->lang->words['r_supermod_info'], $this->settings['_base_url'] ),
								$this->registry->output->formYesNo('report_supermod', (!isset( $plugin_data['report_supermod'] )) ? 1 : $plugin_data['report_supermod'] )
							);
	}
	
	/**
	 * Process the plugin's form fields for saving
	 *
	 * @access	public
	 * @param	array 		Plugin data for save
	 * @return	string		Error message
	 */
	public function processAdminForm( &$save_data_array )
	{
		$save_data_array['report_supermod'] = intval($this->request['report_supermod']);
		
		return '';
	}
	
	/**
	 * Update timestamp for report
	 *
	 * @access	public
	 * @param	array 		New reports
	 * @param 	array 		New members cache
	 * @return	boolean
	 */
	public function updateReportsTimestamp( $new_reports, &$new_members_cache )
	{
		$nmc =& $new_members_cache['report_temp']['post_marker'];

		foreach( $new_reports as $report )
		{
			if( $report['is_new'] == 1 || $report['is_active'] == 1 )
			{
				$nmc['forum'][ $report['exdat1'] ]['info']	= array( 'id' => $report['id'], 'title' => $report['title'], 'com_id' => $report['com_id'] );
				$nmc['topic'][ $report['exdat2'] ]['info']	= array( 'id' => $report['id'], 'title' => $report['title'], 'com_id' => $report['com_id'] );
				$nmc['post'][ $report['exdat3'] ]['info']	= array( 'id' => $report['id'], 'title' => $report['title'], 'com_id' => $report['com_id'] );
			}
			if( $report['is_new'] == 1 )
			{
				$nmc['forum'][ $report['exdat1'] ]['gfx']	= 1;
				$nmc['topic'][ $report['exdat2'] ]['gfx']	= 1;
				$nmc['post'][ $report['exdat3'] ]['gfx']	= 1;
			}
			elseif( $report['is_active'] == 1 )
			{
				$nmc['forum'][ $report['exdat1'] ]['gfx']	= 2;
				$nmc['topic'][ $report['exdat2'] ]['gfx']	= 2;
				$nmc['post'][ $report['exdat3'] ]['gfx']	= 2;
			}
		}
	}
		
	/**
	 * Get report permissions
	 *
	 * @access	public
	 * @param	string 		Type of perms to check
	 * @param 	array 		Permissions data
	 * @param 	array 		group ids
	 * @param 	string		Special permissions
	 * @return	boolean
	 */
	public function getReportPermissions( $check, $com_dat, $group_ids, &$to_return )
	{
		if( ( $this->memberData['g_is_supmod'] == 1 && ( ! isset($this->_extra['report_supermod']) || $this->_extra['report_supermod'] == 1 ) ) )
		{
			return true;
		}
		else
		{
			$forum_ids = array();

			foreach( $this->caches['moderators'] as $mod )
			{
				if( $mod['member_id'] == $this->memberData['member_id'] )
				{
					$these_forums = explode( ',', IPSText::cleanPermString( $mod['forum_id'] ) );
					
					foreach( $these_forums as $forum_id )
					{
						$forum_ids['exdat1'][] = $forum_id;
					}
				}
				elseif( $mod['is_group'] == 1 && in_array( $mod['group_id'], $group_ids ) == true )
				{
					$these_forums = explode( ',', IPSText::cleanPermString( $mod['forum_id'] ) );
					
					foreach( $these_forums as $forum_id )
					{
						$forum_ids['exdat1'][] = $forum_id;
					}
				}
			}
			if( count( $forum_ids ) > 0 )
			{
				$to_return = $forum_ids;
				return true;
			}
			else
			{
				return false;
			}
		}	
	}
	
	/**
	 * Show the report form for this module
	 *
	 * @access	public
	 * @param 	array 		Application data
	 * @return	string		HTML form information
	 */
	public function reportForm( $com_dat )
	{		
		$this->lang->words['report_basic_title']		= $this->lang->words['report_post_title'];
		$this->lang->words['report_basic_url_title']	= $this->lang->words['report_post_url_title'];
		$this->lang->words['report_basic_enter']		= $this->lang->words['report_post_enter'];
		
		$this->registry->output->setTitle( $this->lang->words['report_basic_title'] );
		$this->registry->output->addNavigation( $this->lang->words['report_basic_title'], '' );
	
		$topic_id = intval($this->request['tid']);
		$this->_checkAccess( $topic_id );
		
		$extra_data = array(
							'topic_id'	=> intval($topic_id),
							'post_id'	=> intval($this->request['pid']),
							'forum_id'	=> intval($this->topic['forum_id']),
							);
		
		$url = $this->registry->output-> buildSEOUrl( "showtopic={$extra_data['topic_id']}&amp;view=findpost&amp;p={$extra_data['post_id']}", 'public', $this->topic['title_seo'], 'showtopic' );
		
		//-----------------------------------------
		// Title, URL Extra Data (Array)
		//-----------------------------------------
		
		return $this->registry->getClass('reportLibrary')->showReportForm( $this->topic['title'], $url, $extra_data );
	}
	
	/**
	 * Get section and link
	 *
	 * @access	public
	 * @param 	array 		Report data
	 * @return	array 		Section/link
	 */
	public function giveSectionLinkTitle( $report_row )
	{
		return array(
					'title'			=> $this->registry->class_forums->forum_by_id[ $report_row['exdat1'] ]['name'],
					'url'			=> '/index.php?showforum=' . $report_row['exdat1'],
					'seo_title'		=> $this->registry->class_forums->forum_by_id[ $report_row['exdat1'] ]['name_seo'],
					'seo_template'	=> 'showforum',
					);
	}
	
	/**
	 * Process a report and save the data appropriate
	 *
	 * @access	public
	 * @param 	array 		Report data
	 * @return	array 		Data from saving the report
	 */
	public function processReport( $com_dat )
	{
		$topic_id	= intval($this->request['topic_id']);
		$post_id	= intval($this->request['post_id']);
		$forum_id	= intval($this->request['forum_id']);
		
		if( ! $topic_id || ! $post_id )
		{
			$this->registry->output->showError( 'reports_missing_tidpid', 10168 );
		}
		
		$uid = md5( 'topic_' . $topic_id . '_' . $post_id . '_' . $forum_id . '_' . $com_dat['com_id'] );
		
		$url		= 'showtopic=' . intval($topic_id) . '&view=findpost&p=' . intval($post_id);
		$save_url	= str_replace( '&', '&amp;', $url);
		
		$status = array();
		
		$this->DB->build( array( 'select' 	=> 'status, is_new, is_complete', 
										 'from'		=> 'rc_status', 
										 'where'	=> "is_new=1 OR is_complete=1",
								) 		);
		$this->DB->execute();

		while( $row = $this->DB->fetch() )
		{
			if( $row['is_new'] == 1 )
			{
				$status['new'] = $row['status'];
			}
			elseif( $row['is_complete'] == 1 )
			{
				$status['complete'] = $row['status'];
			}
		}
		
		$data = $this->DB->buildAndFetch( array(
												'select'	=> 'p.post',
												'from'		=> array( 'posts' => 'p' ),
												'where'		=> 'p.pid=' . $post_id,
												'add_join'	=> array(
																	array(
																		'select'	=> 't.title_seo',
																		'from'		=> array( 'topics' => 't' ),
																		'where'		=> 't.tid=p.topic_id',
																		'type'		=> 'left',
																		),
																	array(
																		'select'	=> 'mem.member_id, mem.members_display_name',
																		'from'		=> array( 'members' => 'mem' ),
																		'where'		=> 'mem.member_id=p.author_id',
																		'type'		=> 'left',
																		),
																	)
										)		);

		IPSText::getTextClass('bbcode')->parse_bbcode		= 1;
		IPSText::getTextClass('bbcode')->parse_html			= 0;
		IPSText::getTextClass('bbcode')->parse_emoticons	= 1;
		IPSText::getTextClass('bbcode')->parse_nl2br		= 0;
		IPSText::getTextClass('bbcode')->parsing_section	= 'global';
		
		$message	= "[quote name='" . IPSText::getTextClass('bbcode')->makeQuoteSafe( $data['members_display_name'] ) . "']";
		$message	.= IPSText::getTextClass('bbcode')->preEditParse( $data['post'] );
		$message	.= "[/quote]\n\n";
		$message	.= $this->request['message'];
		
		$this->DB->build( array( 'select' => 'id', 'from' => 'rc_reports_index', 'where' => "uid='{$uid}'" ) );
		$this->DB->execute();
		
		if( $this->DB->getTotalRows() == 0 )
		{			
			$this->_checkAccess( $topic_id );
			
			$built_report_main = array(
										'uid'			=> $uid,
										'title'			=> $this->topic['title'],
										'status'		=> $status['new'],
										'url'			=> '/index.php?' . $save_url,
										'seoname'		=> $data['title_seo'],
										'seotemplate'	=> 'showtopic',
										'rc_class'		=> $com_dat['com_id'],
										'updated_by'	=> $this->memberData['member_id'],
										'date_updated'	=> time(),
										'date_created'	=> time(),
										'exdat1'		=> $forum_id,
										'exdat2'		=> $topic_id,
										'exdat3'		=> $post_id,
									);
			
			$this->DB->insert( 'rc_reports_index', $built_report_main );
			$rid = $this->DB->getInsertId();
		}
		else
		{
			$the_report	= $this->DB->fetch();
			$rid		= $the_report['id'];
			$this->DB->update( 'rc_reports_index', array( 'date_updated' => time(), 'status' => $status['new'], 'seoname' => $data['title_seo'], 'seotemplate' => 'showtopic' ), "id='{$rid}'" );
		}
		
		IPSText::getTextClass('bbcode')->parse_bbcode		= 1;
		IPSText::getTextClass('bbcode')->parse_html			= 0;
		IPSText::getTextClass('bbcode')->parse_emoticons	= 1;
		IPSText::getTextClass('bbcode')->parse_nl2br		= 1;
		IPSText::getTextClass('bbcode')->parsing_section	= 'reports';
		
		$build_report = array(
							'rid'			=> $rid,
							'report'		=> IPSText::getTextClass('bbcode')->preDbParse( $message ),
							'report_by'		=> $this->memberData['member_id'],
							'date_reported'	=> time(),
							);
		$this->DB->insert( 'rc_reports', $build_report );
		
		$reports = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as total', 'from' => 'rc_reports', 'where' => "rid='{$rid}'" ) );
		
		$this->DB->update( 'rc_reports_index', array( 'num_reports' => $reports['total'] ), "id='{$rid}'" );
		
		$return_data = array(
							'REDIRECT_URL'	=> $url,
							'FORUM_ID'		=> $forum_id,
							'REPORT_INDEX'	=> $rid,
							'SAVED_URL'		=> '/index.php?' . $save_url,
							'REPORT'		=> $build_report['report'],
							'SEOTITLE'		=> $data['title_seo'],
							'TEMPLATE'		=> 'showtopic',
							);
		
		return $return_data;
	}
	
	/**
	 * Where to send user after report is submitted
	 *
	 * @access	public
	 * @param 	array 		Report data
	 * @return	void
	 */
	public function reportRedirect( $report_data )
	{
		$this->registry->output->redirectScreen( $this->lang->words['report_sending'], $this->settings['base_url'] . $report_data['REDIRECT_URL'], $report_data['SEOTITLE'], $report_data['TEMPLATE'] );
	}
	
	/**
	 * Retrieve list of users to send notifications to
	 *
	 * @access	public
	 * @param 	string 		Group ids
	 * @param 	array 		Report data
	 * @return	array 		Array of users to PM/Email
	 */
	public function getNotificationList( $group_ids, $report_data )
	{
		//-----------------------------------------
		// Build where for secondary member groups
		//-----------------------------------------
		
		$secondaryWhere	= array();
		
		if( is_array($group_ids) AND count($group_ids) )
		{
			foreach( $group_ids as $group_id )
			{
				$secondaryWhere[]	= "m.mgroup_others LIKE '%,{$group_id},%'";
			}
		}
		
		$this->DB->build( array(
									'select'	=> 'noti.*',
									'from'		=> array( 'rc_modpref' => 'noti' ),
									'where'		=> "(m.member_group_id IN(" . $group_ids . ") " . ( count($secondaryWhere) ? "OR " . implode( ' OR ', $secondaryWhere ) : '' ) . ") AND moderator.forum_id LIKE '%,{$report_data['FORUM_ID']},%'",
									'add_join'	=> array(
														array(
															'select'	=> 'm.member_id, m.members_display_name as name, m.language, m.members_disable_pm, m.email, m.member_group_id',
															'from'		=> array( 'members' => 'm' ),
															'where'		=> 'm.member_id=noti.mem_id',
															),
														array(
															'select'	=> 'moderator.member_id, moderator.group_id',
															'from'		=> array( 'moderators' => 'moderator' ),
															'where'		=> 'moderator.member_id=m.member_id OR moderator.group_id=m.member_group_id',
															),
														)
							)		);
		$this->DB->execute();

		$mods		= array();
		$notify		= array();

		if ( $this->DB->getTotalRows() )
		{
			while( $r = $this->DB->fetch() )
			{
				$r['member_id']	= $r['mem_id'];
				
				$mods[] = $r;
			}
		}
		else
		{
			$this->DB->build( array(
										'select'	=> 'noti.*',
										'from'		=> array( 'rc_modpref' => 'noti' ),
										'where'		=> "(m.member_group_id IN(" . $group_ids . ") " . ( count($secondaryWhere) ? "OR " . implode( ' OR ', $secondaryWhere ) : '' ) . ") AND g.g_is_supmod=1",
										'add_join'	=> array(
															array(
																'select'	=> 'm.member_id, m.members_display_name as name, m.language, m.members_disable_pm, m.email, m.member_group_id',
																'from'		=> array( 'members' => 'm' ),
																'where'		=> 'm.member_id=noti.mem_id',
																'type'		=> 'left',
																),
															array(
																'from'		=> array( 'groups' => 'g' ),
																'where'		=> 'g.g_id=m.member_group_id',
																'type'		=> 'left',
																),
															)
								)		);
			$this->DB->execute();

			if ( $this->DB->getTotalRows() )
			{
				while( $r = $this->DB->fetch() )
				{
					$mods[] = $r;
				}
			}
			else
			{
				$this->DB->build( array(
											'select'	=> 'noti.*',
											'from'		=> array( 'rc_modpref' => 'noti' ),
											'where'		=> "(m.member_group_id IN(" . $group_ids . ") " . ( count($secondaryWhere) ? "OR " . implode( ' OR ', $secondaryWhere ) : '' ) . ") AND g.g_access_cp=1",
											'add_join'	=> array(
																array(
																	'select'	=> 'm.member_id, m.members_display_name as name, m.language, m.members_disable_pm, m.email, m.member_group_id',
																	'from'		=> array( 'members' => 'm' ),
																	'where'		=> 'm.member_id=noti.mem_id',
																	'type'		=> 'left',
																	),
																array(
																	'from'		=> array( 'groups' => 'g' ),
																	'where'		=> 'g.g_id=m.member_group_id',
																	'type'		=> 'left',
																	),
																)
									)		);
				$this->DB->execute();

				while( $r = $this->DB->fetch() )
				{
					$mods[] = $r;
				}
			}
		}
		
		foreach( $mods as $row )
		{
			if( $row['by_pm'] == 1 )
			{
				$notify['PM'][] = $row;
			}
			if( $row['by_email'] == 1 )
			{
				$notify['EMAIL'][] = $row;
			}
			
			$notify['RSS'][] = $row;
		}
		
		return $notify;
	}
	
	/**
	 * Check access to report the topic
	 *
	 * @access	private
	 * @param 	integer 	Topic id
	 * @return	void
	 */
	private function _checkAccess( $tid )
    {
		if ( ! $this->memberData['member_id'] )
		{
			$this->registry->output->showError( 'reports_must_be_member', 10169 );
		}
		
		//-----------------------------------------
		// Needs silly a. alias to keep oracle
		// happy
		//-----------------------------------------
		
		$this->topic = $this->DB->buildAndFetch( array( 'select' => 'a.*,a.title as topic_title', 'from' => 'topics a', 'where' => "a.tid=" . $tid ) );
        
		if( ipsRegistry::isClassLoaded('class_forums') !== TRUE )
		{
			require_once( IPSLib::getAppDir( 'forums' ) . "/sources/classes/forums/class_forums.php" );
			$this->registry->setClass( 'class_forums', new class_forums( $this->registry ) );
			$this->registry->getClass( 'class_forums' )->forumsInit();
		}
		
        $this->registry->getClass('class_forums')->forumsCheckAccess( $this->topic['forum_id'], 0, 'topic', $this->topic );
	}
}