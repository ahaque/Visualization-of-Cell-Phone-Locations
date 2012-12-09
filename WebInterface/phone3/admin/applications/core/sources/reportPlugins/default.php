<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Report Center :: Default plugin
 * Last Updated: $LastChangedDate: 2009-06-08 16:27:31 -0400 (Mon, 08 Jun 2009) $
 *
 * @author 		$Author: bfarber $
 * @author		Based on original "Report Center" by Luke Scott
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Core
 * @link		http://www.
 * @version		$Rev: 4736 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class default_plugin
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
		$req_input	= '';
		$return		= '';
		
		if( is_array($plugin_data['required_input']) && count($plugin_data['required_input']) > 0 )
		{
			foreach( $plugin_data['required_input'] as $key => $value )
			{
				if( $req_input != '' )
				{
					$req_input .= "\r\n";
				}

				$req_input .= $key . ' ' . $value;
			}
		}
		
		$return		.= $html->addRow(	"Section Title",
										"This helps you distinguish generally what part of your site a report came from.",
										$this->registry->output->formInput('section_title', $plugin_data['section_title'])
									);
									
		$return		.= $html->addRow(	"Section URL",
										"This helps you distinguish generally what part of your site a report came from.",
										$this->registry->output->formInput('section_url', $plugin_data['section_url'])
									);
									
		$desc = <<<EOF
		Here you can specify what inputs are required in order for a report to be made.
		As you can see by the example each input is broken up by line. Each line contains an input id, 
		a single space, and then a regex pattern. If the regex pattern is matched an error is displayed.<br /><br />Example:<br />
		<div style="border: 1px solid #999; padding: 3px; margin-top: 2px;">content_id [^0-9]<br />title [^A-Za-z0-9]</div>
EOF;

		$return		.= $html->addRow(	"Required Input",
										$desc,
										$this->registry->output->formTextarea('required_input', $req_input, 50, 9)
									);
									
		$return		.= $html->addRow(	"URL String",
										"The formatted URL that is used to point to the reported content. If the URL does not contain 'http://' the board's base url will be inserted. You may also include input data: {input_id}",
										$this->registry->output->formInput('string_url', $plugin_data['string_url'])
									);
									
		$return		.= $html->addRow(	"Title String",
										"The formatted title that is shown on the reporting page and when reports are listed. You may include input data: {input_id}. If you would like the report center to pull the page title, use #PAGE_TITLE#",
										$this->registry->output->formInput('string_title', $plugin_data['string_title'])
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
		if( ! $_POST['string_url'] )
		{
			return "You need to at least have the 'URL String'";
		}
		
		if( ! $_POST['string_title'] )
		{
			return "You need to have a 'Title String'. If not sure, just use #PAGE_TITLE#";
		}
		
		$req_in_a = array();

		if( $_POST['required_input'] )
		{
			$req_i = explode( "<br />", $this->request['required_input'] );

			foreach( $req_i as $line )
			{
				$line_inp					= explode( ' ', $line, 2 );
				$req_in_a[ $line_inp[0] ]	= $line_inp[1];
			}
		}
		
		$save_data_array['required_input']	= $req_in_a;
		$save_data_array['string_url']		= str_replace("&amp;", "&", $this->request['string_url']);
		$save_data_array['string_title']	= $this->request['string_title'];
		$save_data_array['section_title']	= $this->request['section_title'];
		$save_data_array['section_url']		= $this->request['section_url'];	
		
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
		if( $this->_extra['report_bypass'] == 0 || $this->memberData['g_is_supmod'] == 1 )
		{
			return true;
		}
		else
		{
			return false;
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
		$this->registry->output->setTitle( $this->lang->words['report_basic_title'] );
		$this->registry->output->addNavigation( $this->lang->words['report_basic_title'], '' );

		$url			= '';
		$title			= '';
		$ex_form_data	= array();
		
		$this->processBasicForumData( $com_dat, $url, $title, $ex_form_data );
		
		//-----------------------------------------
		// Title, URL Extra Data (Array)
		//-----------------------------------------
		
		return $this->registry->getClass('reportLibrary')->showReportForm( $title, $url, $ex_form_data );
	}
	
	/**
	 * Process the basic forum data
	 *
	 * @access	public
	 * @param 	array 		Application data
	 * @param 	string		URL
	 * @param 	string		Title
	 * @param	array 		Extra form data
	 * @return	mixed		Could show page error, or set $url, $title and $ex_form_data
	 */
	public function processBasicForumData( $com_dat, &$url, &$title, &$ex_form_data )
	{
		//-----------------------------------------
		// Process required input information
		//-----------------------------------------
		
		if( is_array($this->_extra['required_input']) && count($this->_extra['required_input']) > 0 )
		{
			foreach( $this->_extra['required_input'] as $key => $regex )
			{
				if( ! $this->request[ $key ] || ( trim($regex) != '' && preg_match( "/{$regex}/" , $this->request[ $key ] ) ) )
				{
					$this->registry->output->showError( 'reports_input_not_match', 10157 );
				}
				else
				{
					$ex_form_data[ $key ] = $this->request[ $key ];
				}
			}
		}
		
		//-----------------------------------------
		// Format URL String with inputs
		//-----------------------------------------
		
		if( ! $this->_extra['string_url'] )
		{
			$this->registry->output->showError( 'reports_input_not_match', 10158 );
		}
		else
		{
			$url = $this->_extra['string_url'];
			
			while( preg_match("/\{([a-z0-9_\-]+)\}/i", $url, $matched) )
			{
				$url = str_replace( '{' . $matched[1] . '}', $ex_form_data[$matched[1]], $url);
			}
		}
		
		if( strpos( $url, 'http://' ) !== 0 )
		{
			$url	= $this->settings['base_url'] . $url;
		}
		
		//-----------------------------------------
		// Format Title String with inputs, etc..
		//-----------------------------------------

		if( ! $this->_extra['string_title'] )
		{
			$this->registry->output->showError( 'reports_input_not_match', 10159 );
		}
		else
		{
			$title = $this->_extra['string_title'];
			
			while( preg_match("/\{([a-z0-9_\-]+)\}/i", $title, $matched) )
			{
				$title = str_replace( '{' . $matched[1] . '}', $ex_form_data[$matched[1]], $title);
			}
			
			if( strpos( $title, "#PAGE_TITLE#" ) !== false )
			{
				$page_title	= $this->_getPageTitle( $url );
				$title		= str_replace( "#PAGE_TITLE#", $page_title, $title );
			}
		}
		
		$this->registry->output->setTitle( $title );
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
					'title' => $this->_extra['section_title'],
					'url' => $this->_extra['section_url'],
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
		$this->processBasicForumData( $com_dat, $url, $title, $ex_form_data );

		$return_data	= array();
		$a_url			= str_replace( "&", "&amp;", $url );
		$uid			= md5( $url . '_' . $com_dat['com_id'] );
		
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
		
		$this->DB->build( array( 'select' => 'id', 'from' => 'rc_reports_index', 'where' => "uid='{$uid}'" ) );
		$this->DB->execute();
		
		if( $this->DB->getTotalRows() == 0 )
		{	
			$built_report_main = array(
										'uid'			=> $uid,
										'title'			=> $title,
										'status'		=> $status['new'],
										'url'			=> $a_url,
										'rc_class'		=> $com_dat['com_id'],
										'updated_by'	=> $this->memberData['member_id'],
										'date_updated'	=> time(),
										'date_created'	=> time(),
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
		
		$return_data['REDIRECT_URL']	= $a_url;
		$return_data['REPORT_INDEX']	= $rid;
		$return_data['SAVED_URL']		= $url;
		$return_data['REPORT']			= $build_report['report'];
		
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
	 * Loads an HTML page and grabs its title
	 *
	 * @access	private
	 * @param	string   Web URL
	 * @return	string
	 */
	private function _getPageTitle( $URL )
	{
		$html_code = file_get_contents( $URL );
		
		# I just hope the page is html...
		if( preg_match("/\<title\>(.+?)\<\/title\>/i", $html_code, $match ) )
		{
			return htmlentities( $match[1] );
		}
		else
		{
			return $this->lang->words['report_page_title_unknown'];
		}
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