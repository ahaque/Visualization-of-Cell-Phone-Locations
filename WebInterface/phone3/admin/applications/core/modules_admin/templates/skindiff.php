<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Skin diff report tools
 * Last Updated: $Date: 2009-08-07 02:24:38 -0400 (Fri, 07 Aug 2009) $
 *
 * @author 		$Author: matt $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @since		Who knows...
 * @version		$Revision: 4997 $
 *
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class admin_core_templates_skindiff extends ipsCommand
{
	/**
	 * Skin Functions Class
	 *
	 * @access	private
	 * @var		object
	 */
	private $skinFunctions;
	
	/**
	 * Recursive depth guide
	 *
	 * @access	private
	 * @var		array
	 */
	private $_depthGuide = array();
	
	/**
	 * Skin object
	 *
	 * @access	private
	 * @var		object			Skin templates
	 */
	private $html;
	
	/**
	 * Amount of items per go
	 *
	 * @access	private
	 * @var		int
	 */
	private $_bitsPerRound = 10;
	
	/**#@+
	 * URL bits
	 *
	 * @access	public
	 * @var		string
	 */
	public $form_code		= '';
	public $form_code_js	= '';
	/**#@-*/
	
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
		
		$this->html			= $this->registry->output->loadTemplate('cp_skin_templates');
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=templates&amp;section=skindiff';
		$this->form_code_js	= $this->html->form_code_js	= 'module=templates&section=skindiff';
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'admin_templates' ) );
		
		//-----------------------------------------
		// Load functions and cache classes
		//-----------------------------------------
	
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );
		require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinDifferences.php' );
		
		$this->skinFunctions = new skinDifferences( $registry );
		
		$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'skindiff_reports' );
		
		///----------------------------------------
		// What to do...
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'skinDiffStart':
				$this->_start();
			break;
			case 'viewReport':
				$this->_viewReport();
			break;
			case 'exportReport':
				$this->_exportReport();
			break;
			case 'skin_diff_view_diff':
				$this->skin_differences_view_diff();
			break;
			case 'skin_diff_from_skin':
				$this->skin_differences_start_from_skin();
			break;
			case 'removeReport':
				$this->_removeReport();
			break;
			default:
				$this->_list();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}

	
	/**
	 * Remove a report
	 *
	 * @access	private
	 * @return	void
	 */
	private function _removeReport()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$sessionID = intval( $this->request['sessionID'] );
		
		//-----------------------------------------
		// Get current session
		//-----------------------------------------
		
		$session = $this->skinFunctions->fetchSession( $sessionID );
		
		if ( $session === FALSE )
		{
			$this->registry->output->showError( $this->lang->words['sd_nosession'] );
		}
		
		//-----------------------------------------
		// Remove...
		//-----------------------------------------
		
		$this->skinFunctions->removeSession( $sessionID );
		
		//-----------------------------------------
		// Done...
		//-----------------------------------------
		
		$this->registry->output->global_message = $this->lang->words['sd_removed'];
		$this->_list();
	}
	
	/**
	 * Export a report
	 *
	 * @access	private
	 * @return	void
	 */
	private function _exportReport()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$sessionID = intval( $this->request['sessionID'] );
		
		//-----------------------------------------
		// Get current session
		//-----------------------------------------
		
		$session = $this->skinFunctions->fetchSession( $sessionID );
		
		if ( $session === FALSE )
		{
			$this->registry->output->showError( $this->lang->words['sd_nosession'] );
		}
		
		//-----------------------------------------
		// Get data
		//-----------------------------------------
		
		$data = $this->skinFunctions->fetchReport( $sessionID );
		
		$content = $this->html->skindiff_export( $session, $data['data'], $data['counts']['missing'], $data['counts']['changed'] );
		
		$this->registry->output->showDownload( $content, 'IPBDiff-' . IPSText::makeSeoTitle( $session['diff_session_title'] ) . '.html', "unknown/unknown", 0 );
	}
	
	/**
	 * View a diff report
	 *
	 * @access	private
	 * @return	void
	 */
	private function _viewReport()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$sessionID  = intval( $this->request['sessionID'] );
		$content    = '';
		$missing    = 0;
		$changed    = 0;
		$last_group = '';
		
		//-----------------------------------------
		// Get current session
		//-----------------------------------------
		
		$session = $this->skinFunctions->fetchSession( $sessionID );
		
		if ( $session === FALSE )
		{
			$this->registry->output->showError( $this->lang->words['sd_nosession'] );
		}
		
		//-----------------------------------------
		// Get data
		//-----------------------------------------
		
		$data = $this->skinFunctions->fetchReport( $sessionID );
		
		$this->registry->output->html = $this->html->skindiff_reportOverview( $session, $data['data'], $data['counts']['missing'], $data['counts']['changed'] );
	}

	/**
	 * Compare skin differences (XML files)
	 *
	 * @since	2.1.0.2005-07-22
	 * @access	public
	 * @return	void
	 */
	public function skin_differences_start_from_skin()
	{
		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$content = "";
		$skin_id = intval( $this->request['skin_id'] );
		
		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( ! $skin_id )
		{
			$this->registry->output->showError( $this->lang->words['sd_noid'] );
		}
		
		//-----------------------------------------
		// Get skin set...
		//-----------------------------------------
		
		$skin_set = $this->DB->buildAndFetch( array(  'select' => '*', 'from' => 'skin_collections', 'where' => 'set_id='.$skin_id ) );
		
		if ( ! $skin_set['set_id'] )
		{
			$this->registry->output->showError( $this->lang->words['sd_noid'] );
		}
		
		//-----------------------------------------
		// Get number template bits
		//-----------------------------------------
		
		$total_bits = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count', 'from' => 'skin_templates', 'where' => 'set_id=1' ) );
		
		//-----------------------------------------
		// Create session
		//-----------------------------------------
		
		$this->DB->insert( 'template_diff_session', array( 'diff_session_togo'    		  => intval( $total_bits['count'] ),
																		'diff_session_done'    		  => 0,
																		'diff_session_title'   		  => $this->lang->words['sd_title'].$skin_set['set_name'],
																		'diff_session_updated'        => time(),
																		'diff_session_ignore_missing' => 1 ) );
																		
		$diff_session_id = $this->DB->getInsertId();
		
		$seen_templates = array();
		
		//-----------------------------------------
		// Grab template bits from DB
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'skin_templates', 'where' => 'set_id='.$skin_id ) );
		$outer = $this->DB->execute();
		
		while( $entry = $this->DB->fetch( $outer ) )
		{
			$check = $this->DB->buildAndFetch( array( 'select' => 'diff_key', 'from' => 'templates_diff_import', 'where' => "diff_key='".$diff_session_id.':'.$entry[ 'group_name' ].':'.$entry[ 'func_name' ]."'" ) );
			
			if( $this->DB->getTotalRows() == 0 )
			{
				$this->DB->insert( 'templates_diff_import', array( 'diff_key'             => $diff_session_id.':'.$entry[ 'group_name' ].':'.$entry[ 'func_name' ],
																	  		'diff_func_group'      => $entry[ 'group_name' ],
																	  		'diff_func_data'	   => $entry[ 'func_data' ],
																	 		'diff_func_name'       => $entry[ 'func_name' ],
																	 		'diff_func_content'    => $entry[ 'section_content' ],
																	 		'diff_session_id'      => $diff_session_id ) );
				$seen_templates[ $entry[ 'group_name' ].':'.$entry[ 'func_name' ] ] = $entry['updated'];
			}
			else
			{
				if( $seen_templates[ $entry[ 'group_name' ].':'.$entry[ 'func_name' ] ] < $entry['updated'] )
				{
					$this->DB->update( 'templates_diff_import', array( 'diff_func_group'      => $entry[ 'group_name' ],
																	  		'diff_func_data'	   => $entry[ 'func_data' ],
																	 		'diff_func_name'       => $entry[ 'func_name' ],
																	 		'diff_func_content'    => $entry[ 'section_content' ],
																	 		'diff_session_id'      => $diff_session_id ), "diff_key='" . $diff_session_id.':'.$entry[ 'group_name' ].':'.$entry[ 'func_name' ] . "'" );
				}
			}
		}
		
		$this->registry->output->redirectinit( $this->settings['base_url'].'&'.$this->form_code_js."&do=skin_diff_process&diff_session_id={$diff_session_id}&pergo=10&secure_key=" . $this->member->form_hash );
	}
	
	/**
	 * Initiate a skin diff session
	 *
	 * @access	private
	 * @return	void
	 */
	private function _start()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$content            = "";
		$seen               = array();
		$diff_session_title = $this->request['diff_session_title'];
		$ignoreMissing      = ( $this->request['diff_session_ignore_missing'] ) ? TRUE : FALSE;
		$diffFolder			= trim( $this->request['diffFolder'] );
		
		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( ! $diff_session_title )
		{
			$this->registry->output->global_error = $this->lang->words['sd_entertitle'];
			return $this->_list();
		}
		
		if ( $diffFolder )
		{
			$files   = array();
			$content = '';
			
			/* Did we specify a folder? */
			if ( ! is_dir( DOC_IPS_ROOT_PATH . $diffFolder ) )
			{
				$this->registry->output->global_error = sprintf( $this->lang->words['diff_folder_error'], DOC_IPS_ROOT_PATH . $diffFolder );
				return $this->_list();
			}
			
			try
			{
				foreach( new DirectoryIterator( DOC_IPS_ROOT_PATH . $diffFolder ) as $file )
				{
					if ( ! $file->isDot() AND ! $file->isDir() )
					{
						$_name = $file->getFileName();

						if ( substr( $_name, -4 ) == '.xml' )
						{
							$files[ $_name ] = @file_get_contents( DOC_IPS_ROOT_PATH . $diffFolder . '/' . $_name );
						}
					}
				}
			} catch ( Exception $e ) {}
			
			if ( count( $files ) )
			{
				foreach( $files as $name => $xml )
				{
					if ( ! $content )
					{
						$content = $xml;
					}
					else
					{
						preg_match( "#<templategroup([^>]+?)?>.*</templategroup>#is", $xml, $match );
						
						if ( $match[0] )
						{
							$content = str_replace( '</templates>', $match[0] . "\n</templates>", $content );
						}
					}
				}
			}
		}
		else
		{
			/* fetch the upload then */
			$content = $this->registry->adminFunctions->importXml();
		}
		
		if ( ! $content )
		{
			$this->registry->output->global_error = $this->lang->words['sd_nocontent'];
			return $this->_list();
		}
	
		//-----------------------------------------
		// Create session...
		//-----------------------------------------
		
		$diffSessionID = $this->skinFunctions->createSession( $diff_session_title, $content );
		
		if ( $diffSessionID === FALSE )
		{
			$this->registry->output->global_error = $this->lang->words['sd_nocontent'];
			return $this->_list();
		}
		
		$this->registry->output->html = $this->html->skindiff_ajaxScreen( $diffSessionID, $this->skinFunctions->fetchNumberTemplateBits(), $this->_bitsPerRound );
	}

	/**
	 * List all current difference 'sets'
	 *
	 * @access	private
	 * @return	void
	 */
	private function _list()
	{
		//-----------------------------------------
		// Do it
		//-----------------------------------------
		
		$this->registry->output->html = $this->html->skindiff_overview( $this->skinFunctions->fetchSessions() );
	}
	
}