<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Report Center :: Gallery plugin
 * Last Updated: $LastChangedDate: 2009-03-29 16:35:41 -0400 (Sun, 29 Mar 2009) $
 *
 * @author 		$Author: bfarber $
 * @author		Based on original "Report Center" by Luke Scott
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	IP.Gallery
 * @link		http://www.
 * @version		$Rev: 4342 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class gallery_plugin
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
		$return = '';
		
		$return .= $html->addRow(	$this->lang->words['r_supermod'],
									sprintf(  $this->lang->words['r_supermod_info'], $this->settings['_base_url'] ),
									$this->registry->output->formYesNo('report_supermod', (!isset( $plugin_data['report_supermod'] )) ? 1 : $plugin_data['report_supermod'] )
								);
							
		$return .= $html->addRow(	$this->lang->words['r_galmod'],
									"",
									$this->registry->output->formYesNo('report_bypass', (!isset( $plugin_data['report_bypass'] )) ? 1 : $plugin_data['report_bypass'] )
								);

		return $return;
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
		$save_data_array['report_supermod']	= intval($this->request['report_supermod']);
		$save_data_array['report_bypass']	= intval($this->request['report_bypass']);
		
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
		return true;
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
		if( $this->_extra['report_bypass'] == 0
			|| ( $this->memberData['g_is_supmod'] == 1 && ( ! isset($this->_extra['report_supermod']) || $this->_extra['report_supermod'] == 1 ) ) )
		{
			return true;
		}
		else
		{
			$this->DB->build( array(
										'select'	=> 'g.g_id',
										'from'		=> array( 'groups' => 'g' ),
										'where'		=> "(g.g_mod_albums=1 OR g.g_is_supmod=1) AND m.member_id=" . $this->memberData['member_id'],
										'add_join'	=> array(
															array(
																'select'	=> 'm.members_display_name, m.member_id, m.email',
																'from'		=> array( 'members' => 'm' ),
																'where'		=> "m.member_group_id = g.g_id OR m.mgroup_others LIKE " . $this->DB->buildConcat( array( array( '%', 'string' ), array( 'g.g_id' ), array( '%', 'string' ) ) ) ,
																)
															)
								)		);
			$res = $this->DB->execute();

			if( $this->DB->getTotalRows($res) > 0 )
			{
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
		define( 'GALLERY_LIBS', IPS_ROOT_PATH . '/applications_addon/ips/gallery/sources/libs/' );
		require_once( GALLERY_LIBS . 'lib_gallery.php' );
		$this->registry->setClass( 'glib', new lib_gallery( $this->registry ) );

		$this->registry->class_localization->loadLanguageFile( array( 'public_gallery' ), 'gallery' );
		
		if( $this->request['ctyp'] == 'comment' )
		{
			$comment_id = intval($this->request['comment_id']);
			
			$comment	= $this->DB->buildAndFetch( array( 'select' => 'img_id', 'from' => 'gallery_comments', 'where' => "pid={$comment_id}" ) );
			$data		= $this->registry->getClass('glib')->getImageInfo( $comment['img_id'] );
			
			$ex_form_data = array(
									'img_id'		=> $comment['img_id'],
									'comment_id'	=> $comment_id,
									'ctyp'			=> 'comment',
									'st'			=> intval($this->request['st']),
									'title' 		=> $data['caption'] . $this->lang->words['report_gallery_comment_suffix'],
								);
			
			$this->registry->output->setTitle( $this->lang->words['report_comment_page'] );
			$this->registry->output->addNavigation( $this->lang->words['gallery'], "app=gallery" );
			$this->registry->output->addNavigation( $this->lang->words['report_comment_page'], '' );
			
			$this->lang->words['report_basic_title']		= $this->lang->words['report_comment_page'];
			$this->lang->words['report_basic_url_title']	= $this->lang->words['report_comment_title'];
			$this->lang->words['report_basic_enter']		= $this->lang->words['report_comment_msg'];
			
			return $this->registry->getClass('reportLibrary')->showReportForm( '', '', $ex_form_data );
		}
		elseif( $this->request['ctyp'] == 'image' )
		{
			if( $this->settings['gallery_disable_report_images'] )
			{
				$this->registry->output->showError( 'reports_images_disabled', 10162 );
			}
			
			$img_id = intval($this->request['img_id']);
			
			if( ! $img_id )
			{
				$this->registry->output->showError( 'reports_no_imageid', 10163 );
			}
			
			$data		= $this->registry->getClass('glib')->getImageInfo( $img_id );
			$thumb		= $this->registry->getClass('glib')->makeImageLink( $data, 1 );

			$ex_form_data = array(
									'img_id'	=> $img_id,
									'ctyp'		=> 'image',
									//'thumb'		=> $thumb,
									'title'		=> $data['caption'] . $this->lang->words['report_gallery_image_suffix']
								);
			
			$this->registry->output->setTitle( $data['caption'] . $this->lang->words['report_gallery_image_suffix'] );
			$this->registry->output->addNavigation( $this->lang->words['gallery'], "app=gallery" );
			$this->registry->output->addNavigation( $data['caption'] . $this->lang->words['report_gallery_image_suffix'], '' );
			
			$this->lang->words['report_basic_title']		= $this->lang->words['report_img_page'];
			$this->lang->words['report_basic_url_title']	= $this->lang->words['report_img_title'];
			$this->lang->words['report_basic_enter']		= $this->lang->words['report_img_msg'];
			
			$url = $this->settings['base_url'] . "app=gallery&amp;module=images&amp;section=viewimage&amp;img=" . $ex_form_data['img_id'];
			
			return $this->registry->getClass('reportLibrary')->showReportForm( $data['caption'], $url, $ex_form_data );
		}
		else
		{
			$this->registry->output->showError( 'reports_invalid_gtype', 10164 );
		}
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
					'title'	=> $this->lang->words['report_section_title_site_gallery'],
					'url'	=> "/index.php?app=gallery",
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
		$con_type = $this->request['ctyp'];
		
		if( $con_type == 'image' )
		{
			$img		= $this->request['img_id'];
			$url		= 'app=gallery&module=images&section=viewimage&img=' . $img;
		}
		elseif( $con_type == 'comment' )
		{
			$img		= $this->request['img_id'];
			$comment	= $this->request['comment_id'];
			$st			= intval($this->request['st']);
			$url		= 'app=gallery&module=images&section=viewimage&img=' . $img . '&st=' . $st . '#' . $comment;
		}
		
		if( $img < 1 )
		{
			$this->registry->output->showError( 'reports_no_imageid', 10165 );
		}

		$return_data	= array();
		$a_url			= str_replace("&", "&amp;", $url);
		$uid			= md5(  'gallery_' . $con_type . '_' . $img . '_' . $comment . '_' . $com_dat['com_id'] );
		$status			= array();
		
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
		
		$this->DB->build( array( 'select' => 'id', 'from' => 'rc_reports_index', 'where' => "uid='{$uid}'" ) );
		$this->DB->execute();
		
		if( $this->DB->getTotalRows() == 0 )
		{	
			$built_report_main = array(
										'uid'			=> $uid,
										'title'			=> $this->request['title'],
										'status'		=> $status['new'],
										'url'			=> '/index.php?' . $a_url,
										'rc_class'		=> $com_dat['com_id'],
										'updated_by'	=> $this->memberData['member_id'],
										'date_updated'	=> time(),
										'date_created'	=> time(),
										'img_preview'	=> $this->request['thumb'],
										'exdat1'		=> $img,
										'exdat2'		=> $comment,
										'exdat3'		=> $st
									);

			$this->DB->insert( 'rc_reports_index', $built_report_main );
			$rid = $this->DB->getInsertId();
		}
		else
		{
			$the_report	= $this->DB->fetch();
			$rid		= $the_report['id'];
			$this->DB->update( 'rc_reports_index', array( 'date_updated' => time(), 'status' => $status['new'] ), "id='{$rid}'" );
		}
		
		IPSText::getTextClass('bbcode')->parse_bbcode		= 1;
		IPSText::getTextClass('bbcode')->parse_html			= 0;
		IPSText::getTextClass('bbcode')->parse_emoticons	= 1;
		IPSText::getTextClass('bbcode')->parse_nl2br		= 1;
		IPSText::getTextClass('bbcode')->parsing_section	= 'reports';
		
		$build_report = array(
							'rid'			=> $rid,
							'report'		=> IPSText::getTextClass('bbcode')->preDbParse( $this->request['message'] ),
							'report_by'		=> $this->memberData['member_id'],
							'date_reported'	=> time(),
							);
		
		$this->DB->insert( 'rc_reports', $build_report );
		
		$reports = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as total', 'from' => 'rc_reports', 'where' => "rid='{$rid}'" ) );
		
		$this->DB->update( 'rc_reports_index', array( 'num_reports' => $reports['total'] ), "id='{$rid}'" );
		
		$return_data = array( 
							'REDIRECT_URL'	=> $a_url,
							'REPORT_INDEX'	=> $rid,
							'SAVED_URL'		=> '/index.php?' . $url,
							'REPORT'		=> $build_report['report']
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
		$this->registry->output->redirectScreen( $this->lang->words['report_sending'], $this->settings['base_url'] . $report_data['REDIRECT_URL'] );
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
		$notify = array();
		
		$this->DB->build( array(
									'select'	=> 'noti.*',
									'from'		=> array( 'rc_modpref' => 'noti' ),
									'where'		=> 'mem.member_group_id IN(' . $group_ids . ')',
									'add_join'	=> array(
														array(
															'select'	=> 'mem.member_id, mem.members_display_name as name, mem.language, mem.members_disable_pm, mem.email, mem.member_group_id',
															'from'		=> array( 'members' => 'mem' ),
															'where'		=> 'mem.member_id=noti.mem_id',
															)
														)
							)		);
		$this->DB->execute();

		if( $this->DB->getTotalRows() > 0 )
		{
			while( $row = $this->DB->fetch() )
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
		}
		
		return $notify;
	}
}