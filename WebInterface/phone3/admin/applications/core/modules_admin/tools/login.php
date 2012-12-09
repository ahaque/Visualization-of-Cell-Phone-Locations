<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Login Manager Administration
 * Last Updated: $Date: 2009-08-10 21:52:10 -0400 (Mon, 10 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Core
 * @link		http://www.
 * @since		Thursday 26th January 2006 (11:03)
 * @version		$Revision: 5010 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class admin_core_tools_login extends ipsCommand
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
	 * Main class entry point
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Load language and skin
		//-----------------------------------------
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_tools' ) );
		$this->html = ipsRegistry::getClass('output')->loadTemplate('cp_skin_tools');
		
		//-----------------------------------------
		// Set URL shortcuts
		//-----------------------------------------
		
		$this->form_code    = $this->html->form_code	= 'module=tools&amp;section=login';
		$this->form_code_js = $this->html->form_code_js	= 'module=tools&section=login';
		
		$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'login_manage' );
		
		//-----------------------------------------
		// What are we doing now?
		//-----------------------------------------
		
		switch(ipsRegistry::$request['do'])
		{
			case 'manage':
			default:
				ipsRegistry::$request[ 'do'] =  'manage' ;
				$this->_loginList();
			break;
			
			case 'login_toggle':
				$this->_loginToggle();
			break;
			
			case 'login_uninstall':
				$this->_loginUninstall();
			break;
			
			case 'login_install':
				$this->_loginInstall();
			break;
			
		  	case 'login_reorder':
			 	$this->_loginReorder();
		  	break;
			
			case 'login_add':
				$this->_loginForm('add');
			break;

			case 'login_add_do':
				$this->_loginSave('add');
			break;
				
			case 'login_edit_details':
				$this->_loginForm('edit');
			break;

			case 'login_edit_do':
				$this->_loginSave('edit');
			break;
			
			case 'login_acp_conf':
				$this->_loginACPConf();
			break;
			
			case 'login_save_conf':
				$this->_loginSaveConf();
			break;
		
			case 'master_xml_export':
				$this->_masterXmlExport();
			break;
			
			case 'login_export':
				$this->_masterXmlExport( ipsRegistry::$request['login_id'] );
			break;
					
			case 'login_diagnostics':
				$this->_loginDiagnostics();
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}

	/**
	 * Save login method configuration details
	 *
	 * @access	private
	 * @return	boolean	Saved or not
	 */
	private function _loginSaveConf()
	{
		//-----------------------------------------
		// Init Vars
		//-----------------------------------------
		
		$login_id = intval($this->request['login_id']);
		
		//-----------------------------------------
		// Check (please?)
		//-----------------------------------------
		
		$login = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'login_methods', 'where' => 'login_id=' . $login_id ) );
			
		if ( ! $login['login_id'] )
		{
			ipsRegistry::getClass('output')->global_message = $this->lang->words['l_404'];
			$this->_loginList();
			return false;
		}
		
		//-----------------------------------------
		// Generate file path
		//-----------------------------------------
		
		$mypath = IPS_PATH_CUSTOM_LOGIN . '/' . $login['login_folder_name'];
		
		//-----------------------------------------
		// Check (still waiting)
		//-----------------------------------------
		
		if( !file_exists( $mypath . '/acp.php' ) )
		{
			ipsRegistry::getClass('output')->global_message = $this->lang->words['l_noconfig'];
			$this->_loginList();
			return false;
		}
		
		if( !file_exists( $mypath . '/conf.php' ) )
		{
			ipsRegistry::getClass('output')->global_message = $this->lang->words['l_noconfigfile'];
			$this->_loginList();
			return false;
		}
		
		if( !is_writable( $mypath . '/conf.php' ) )
		{
			ipsRegistry::getClass('output')->global_message = $mypath . $this->lang->words['l_confwrite'];
			$this->_loginList();
			return false;
		}
		
		//-----------------------------------------
		// We all good?
		//-----------------------------------------
		
		require_once( $mypath . '/acp.php' );
		require_once( $mypath . '/conf.php' );
		
		if( !is_array($config) OR !count($config) )
		{
			ipsRegistry::getClass('output')->global_message = $this->lang->words['l_noconfig'];
			$this->_loginList();
			return false;
		}
		
		//-----------------------------------------
		// Teh form
		//-----------------------------------------
		
		$save = array();
		
		foreach( $config as $option )
		{
			if( $option['key'] )
			{
				$save[ $option['key'] ] = str_replace( '&#092;', '\\', $_POST[ $option['key'] ] );
			}
		}
		
		$conf_file = '<' . '?php' . "\n\n";
		
		foreach( $save as $k => $v )
		{
			$conf_file .= '$LOGIN_CONF[' . "'" . $k . "']	= " . '"' . addslashes( $v ) . '";' . "\n";
		}
		
		$conf_file .= "\n\n?" . '>';
		
		if( $fh = @fopen( $mypath . '/conf.php', 'w' ) )
		{
			fwrite( $fh, $conf_file );
			fclose( $fh );
		}
		else
		{
			ipsRegistry::getClass('output')->global_message = $mypath . $this->lang->words['l_confwrite'];
			$this->_loginACPConf();
			return false;
		}
		
		ipsRegistry::getClass('output')->global_message = sprintf($this->lang->words['l_confup'], $login['login_title'] );
		$this->_loginList();
		return true;
	}
	
	/**
	 * Configure details specific to a login method
	 *
	 * @access	private
	 * @return	mixed		Outputs, or return false
	 */
	private function _loginACPConf()
	{
		//-----------------------------------------
		// Init Vars
		//-----------------------------------------
		
		$login_id = intval(ipsRegistry::$request['login_id']);
		
		//-----------------------------------------
		// Check (please?)
		//-----------------------------------------
		
		$login = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'login_methods', 'where' => 'login_id=' . $login_id ) );
			
		if ( ! $login['login_id'] )
		{
			ipsRegistry::getClass('output')->global_message = $this->lang->words['l_404'];
			$this->_loginList();
			return false;
		}
		
		//-----------------------------------------
		// Generate file path
		//-----------------------------------------
		
		$mypath = IPS_PATH_CUSTOM_LOGIN . '/' . $login['login_folder_name'];
		
		//-----------------------------------------
		// Check (still waiting)
		//-----------------------------------------
		
		if( !file_exists( $mypath . '/acp.php' ) )
		{
			ipsRegistry::getClass('output')->global_message = $this->lang->words['l_noconfig'];
			$this->_loginList();
			return false;
		}
		
		if( !file_exists( $mypath . '/conf.php' ) )
		{
			ipsRegistry::getClass('output')->global_message = $this->lang->words['l_noconfigfile'];
			$this->_loginList();
			return false;
		}
		
		if( !is_writable( $mypath . '/conf.php' ) )
		{
			ipsRegistry::getClass('output')->global_message = $mypath . $this->lang->words['l_confwrite'];
			$this->_loginList();
			return false;
		}
		
		//-----------------------------------------
		// We all good?
		//-----------------------------------------
		
		require_once( $mypath . '/acp.php' );
		require_once( $mypath . '/conf.php' );
		
		if( !is_array($config) OR !count($config) )
		{
			ipsRegistry::getClass('output')->global_message = $this->lang->words['l_noconfig'];
			$this->_loginList();
			return false;
		}
		
		//-----------------------------------------
		// Teh form
		//-----------------------------------------
		
		$form = array();
		
		foreach( $config as $option )
		{
			$form_control = '';
			
			if( $option['type'] == 'yesno' )
			{
				$form_control = ipsRegistry::getClass('output')->formYesNo( $option['key'], $_POST[ $option['key'] ] ? $_POST[ $option['key'] ] : $LOGIN_CONF[ $option['key'] ] );
			}
			else if( $option['type'] == 'select' )
			{
				$form_control = ipsRegistry::getClass('output')->formDropdown( $option['key'], $option['options'], $_POST[ $option['key'] ] ? $_POST[ $option['key'] ] : $LOGIN_CONF[ $option['key'] ] );
			}
			else
			{
				$form_control = ipsRegistry::getClass('output')->formInput( $option['key'], $_POST[ $option['key'] ] ? $_POST[ $option['key'] ] : $LOGIN_CONF[ $option['key'] ] );
			}
			
			$form[] = array(
							'title'			=> $option['title'],
							'description'	=> $option['description'],
							'control'		=> $form_control
							);
		}
		
		ipsRegistry::getClass('output')->html .= $this->html->login_conf_form( $login, $form );
	}
	
	
	/**
	 * Build XML file from array of data
	 *
	 * @access	private
	 * @param	array 		Entries to add
	 * @return	string		XML Document
	 */
	private function _buildXML( $data=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$entry = array();
		
		//-----------------------------------------
		// Get XML class
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH . 'class_xml.php' );
		$xml = new class_xml();
		$xml->doc_type = IPS_DOC_CHAR_SET;

		$xml->xml_set_root( 'export', array( 'exported' => time() ) );
		
		//-----------------------------------------
		// Set group
		//-----------------------------------------
		
		$xml->xml_add_group( 'group' );
		
		foreach( $data as $thisentry => $r )
		{
			$content = array();
			
			if ( $r['login_folder_name'] == 'internal' )
			{
				$r['login_enabled']			= 1;
			}
			else if ( $r['login_folder_name'] == 'ipconverge' )
			{
				$r['login_maintain_url']	= '';
				$r['login_register_url']	= '';
				$r['login_login_url']		= '';
				$r['login_logout_url']		= '';
				$r['login_enabled']			= 0;
			}
			else
			{
				$r['login_enabled']			= 0;
			}
			
			//-----------------------------------------
			// Sort the fields...
			//-----------------------------------------
			
			foreach( $r as $k => $v )
			{
				if( in_array( $k, array( 'login_id', 'login_date' ) ) )
				{
					continue;
				}
				
				$content[] = $xml->xml_build_simple_tag( $k, $v );
			}
			
			$entry[] = $xml->xml_build_entry( 'row', $content );
		}
		
		$xml->xml_add_entry_to_group( 'group', $entry );
		
		$xml->xml_format_document();
		
		return $xml->xml_document;
	}
	
	/**
	 * Export master XML file for installer
	 *
	 * @access	private
	 * @param	integer		[Optional] Login ID
	 * @return	void		[Outputs to screen]
	 */
	private function _masterXmlExport( $login_id=0 )
	{		
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$entries	= array();
		$where		= '';
		
		if( $login_id )
		{
			$where = 'login_id=' . intval($login_id);
		}

		//-----------------------------------------
		// Get login methods
		//-----------------------------------------
	
		$this->DB->build( array( 'select'	=> '*',
										'from'	=> 'login_methods',
										'where'	=> $where
								) 		);
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			$entries[] = $r;
		}

		$document = $this->_buildXML( $entries );

		//-----------------------------------------
		// Print to browser
		//-----------------------------------------
		
		$filename = $login_id ? 'loginauth_install.xml' : 'loginauth.xml';
		
		ipsRegistry::getClass('output')->showDownload( $document, $filename, '', 0 );
	}
	
	/**
	 * Shows the login 'diagnostics' screen
	 *
	 * @access	private
	 * @return	mixed		Outputs, or returns false
	 */
	private function _loginDiagnostics()
	{
		//-----------------------------------------
		// Init Vars
		//-----------------------------------------
		
		$login_id = intval(ipsRegistry::$request['login_id']);
		
		//-----------------------------------------
		// Check (please?)
		//-----------------------------------------
		
		$login = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'login_methods', 'where' => 'login_id=' . $login_id ) );
			
		if ( ! $login['login_id'] )
		{
			ipsRegistry::getClass('output')->global_message = $this->lang->words['l_404'];
			$this->_loginList();
			return false;
		}
		
		//-----------------------------------------
		// Generate file
		//-----------------------------------------
		
		$mypath = IPS_PATH_CUSTOM_LOGIN . '/' . $login['login_folder_name'];
		
		//-----------------------------------------
		// Generate General Info
		//-----------------------------------------
		
		$login['_enabled_img']   = $login['login_enabled']   ? 'tick.png' : 'cross.png';
		$login['_installed_img'] = $login['login_installed'] ? 'tick.png' : 'cross.png';
		$login['_has_settings']  = $login['login_settings']  ? 'tick.png' : 'cross.png';
		
		//-----------------------------------------
		// File based info
		//-----------------------------------------
		
		$login['_file_auth_exists'] = @file_exists( $mypath . '/auth.php' )	? 'tick.png' : 'cross.png';
		$login['_file_conf_exists'] = @file_exists( $mypath . '/conf.php' )	? 'tick.png' : 'cross.png';
		$login['_file_acp_exists']  = @file_exists( $mypath . '/acp.php' )	? 'tick.png' : 'cross.png';
		
		$login['_file_conf_write']  = @is_writable( $mypath.'/conf.php' )	? 'tick.png' : 'cross.png';
		
		ipsRegistry::getClass('output')->html .= $this->html->login_diagnostics( $login );
	}
	
	/**
	 * Saves the login method to the database [add,edit]
	 *
	 * @access	private
	 * @param	string		Add or Edit flag
	 * @return	void		[Outputs to screen]
	 */
	private function _loginSave($type='add')
	{
		//--------------------------------------------
		// INIT
		//--------------------------------------------
		
		$login_id				= intval(ipsRegistry::$request['login_id']);
		$login_title			= trim( ipsRegistry::$request['login_title'] );
		$login_description		= trim( IPSText::stripslashes( IPSText::UNhtmlspecialchars($_POST['login_description'])) );
		$login_folder_name		= trim( ipsRegistry::$request['login_folder_name'] );
		$login_maintain_url		= trim( ipsRegistry::$request['login_maintain_url'] );
		$login_register_url		= trim( ipsRegistry::$request['login_register_url'] );
		$login_alt_login_html	= trim( IPSText::stripslashes( IPSText::UNhtmlspecialchars($_POST['login_alt_login_html'])) );
		$login_alt_acp_html		= trim( IPSText::stripslashes( IPSText::UNhtmlspecialchars($_POST['login_alt_acp_html'])) );
		$login_enabled			= intval(ipsRegistry::$request['login_enabled']);
		$login_settings			= intval(ipsRegistry::$request['login_settings']);
		$login_replace_form		= intval(ipsRegistry::$request['login_replace_form']);
		$login_safemode			= intval(ipsRegistry::$request['login_safemode']);
		$login_login_url		= trim( ipsRegistry::$request['login_login_url'] );
		$login_logout_url		= trim( ipsRegistry::$request['login_logout_url'] );
		$login_complete_page	= trim( ipsRegistry::$request['login_complete_page'] );
		$login_user_id			= in_array( ipsRegistry::$request['login_user_id'], array( 'username', 'email' ) ) ? ipsRegistry::$request['login_user_id'] : 'username';
		
		//--------------------------------------------
		// Checks...
		//--------------------------------------------
		
		if ( $type == 'edit' )
		{
			if ( ! $login_id )
			{
				ipsRegistry::getClass('output')->global_message = $this->lang->words['l_404'];
				$this->_loginList();
				return;
			}
		}
		
		if ( ! $login_title OR ! $login_folder_name )
		{
			ipsRegistry::getClass('output')->global_message = $this->lang->words['l_form'];
			$this->_loginForm( $type );
			return;
		}
		
		//--------------------------------------------
		// Save...
		//--------------------------------------------
		
		$array = array( 'login_title'			=> $login_title,
						'login_description'		=> $login_description,
						'login_folder_name'		=> $login_folder_name,
						'login_maintain_url'	=> $login_maintain_url,
						'login_register_url'	=> $login_register_url,
						'login_alt_login_html'	=> $login_alt_login_html,
						'login_alt_acp_html'	=> $login_alt_acp_html,
						'login_enabled'			=> $login_enabled,
						'login_settings'		=> $login_settings,
						'login_replace_form'	=> $login_replace_form,
						'login_logout_url'		=> $login_logout_url,
						'login_login_url'		=> $login_login_url,
						'login_user_id'			=> $login_user_id
					 );
		
		//--------------------------------------------
		// In DEV?
		//--------------------------------------------
		
		if ( IN_DEV )
		{
			$array['login_safemode']  = $login_safemode;
		}
		
		//--------------------------------------------
		// Nike.. do it
		//--------------------------------------------
		
		if ( $type == 'add' )
		{
			$array['login_date'] = time();
			
			$this->DB->insert( 'login_methods', $array );
			
			ipsRegistry::getClass('output')->global_message = $this->lang->words['l_added'];
		}
		else
		{
			$this->DB->update( 'login_methods', $array, 'login_id='.$login_id );
			
			ipsRegistry::getClass('output')->global_message = $this->lang->words['l_edited'];
		}
		
		if( $login_folder_name == 'ipconverge' )
		{
			IPSLib::updateSettings( array( 'ipconverge_enabled' => $login_enabled ) );
		}

		//-----------------------------------------
		// Recache
		//-----------------------------------------
		
		$this->loginsRecache();

		$this->_loginList();
	}
	
	/**
	 * Shows the login method form [add,edit]
	 *
	 * @access	private
	 * @param	string		Add or Edit flag
	 * @return	void		[Outputs to screen]
	 */
	private function _loginForm( $type='add' )
	{
		//-----------------------------------------
		// Init Vars
		//-----------------------------------------
		
		$login_id = intval(ipsRegistry::$request['login_id']);
		
		//-----------------------------------------
		// Check (please?)
		//-----------------------------------------
		
		if ( $type == 'add' )
		{
			$formcode = 'login_add_do';
			$title    = $this->lang->words['l_registernew'];
			$button   = $this->lang->words['l_registernew'];
		}
		else
		{
			$login = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'login_methods', 'where' => 'login_id='.$login_id ) );
			
			if ( ! $login['login_id'] )
			{
				ipsRegistry::getClass('output')->global_message = $this->lang->words['l_404'];
				$this->_loginList();
				return;
			}
			
			$formcode = 'login_edit_do';
			$title    = "Edit Log In Method " . $login['login_title'];
			$button   = "Save Changes";
		}
		
		//-----------------------------------------
		// Form elements
		//-----------------------------------------
		
		$valid_types	= array( array( 'username', $this->lang->words['l_username'] ), array( 'email', $this->lang->words['l_email'] ) );
		$form 			= array();
		
		$form['login_title']			= ipsRegistry::getClass('output')->formInput(		'login_title'			, $_POST['login_title']			? $_POST['login_title']			: $login['login_title'] );
		$form['login_description']		= ipsRegistry::getClass('output')->formInput(		'login_description'		, IPSText::htmlspecialchars( $_POST['login_description'] ? $_POST['login_description'] : $login['login_description'] ) );
		$form['login_folder_name']		= ipsRegistry::getClass('output')->formInput(		'login_folder_name'		, $_POST['login_folder_name']	? $_POST['login_folder_name']	: $login['login_folder_name'] );
		$form['login_maintain_url']		= ipsRegistry::getClass('output')->formInput(		'login_maintain_url'	, $_POST['login_maintain_url']	? $_POST['login_maintain_url']	: $login['login_maintain_url'] );
		$form['login_register_url']		= ipsRegistry::getClass('output')->formInput(		'login_register_url'	, $_POST['login_register_url']	? $_POST['login_register_url']	: $login['login_register_url'] );
		$form['login_login_url']		= ipsRegistry::getClass('output')->formInput(		'login_login_url'		, $_POST['login_login_url'] 	? $_POST['login_login_url']		: $login['login_login_url'] );
		$form['login_logout_url']		= ipsRegistry::getClass('output')->formInput(		'login_logout_url'		, $_POST['login_logout_url']	? $_POST['login_logout_url']	: $login['login_logout_url'] );
		$form['login_enabled']			= ipsRegistry::getClass('output')->formYesNo(		'login_enabled'			, $_POST['login_enabled']		? $_POST['login_enabled']		: $login['login_enabled'] );
		$form['login_settings']			= ipsRegistry::getClass('output')->formYesNo(		'login_settings'		, $_POST['login_settings']		? $_POST['login_settings']		: $login['login_settings'] );
		$form['login_register_url']		= ipsRegistry::getClass('output')->formInput(		'login_register_url'	, $_POST['login_register_url']	? $_POST['login_register_url']	: $login['login_register_url'] );
		$form['login_replace_form']		= ipsRegistry::getClass('output')->formYesNo(		'login_replace_form'	, $_POST['login_replace_form']	? $_POST['login_replace_form']	: $login['login_replace_form'] );
		$form['login_alt_login_html']	= ipsRegistry::getClass('output')->formTextarea(	'login_alt_login_html'	, IPSText::htmlspecialchars( $_POST['login_alt_login_html'] ? $_POST['login_alt_login_html'] : $login['login_alt_login_html'] ) );
		$form['login_alt_acp_html']		= ipsRegistry::getClass('output')->formTextarea(	'login_alt_acp_html'	, IPSText::htmlspecialchars( $_POST['login_alt_acp_html'] ? $_POST['login_alt_acp_html'] : $login['login_alt_acp_html'] ) );
		$form['login_user_id']			= ipsRegistry::getClass('output')->formDropdown(	'login_user_id'			, $valid_types, $_POST['login_user_id']	? $_POST['login_user_id']	: $login['login_user_id'] );
		
		if ( IN_DEV )
		{
			$form['login_safemode']  = ipsRegistry::getClass('output')->formYesNo( 'login_safemode' , $_POST['login_safemode']  ? $_POST['login_safemode'] : $login['login_safemode'] );
		}
		
		ipsRegistry::getClass('output')->html .= $this->html->login_form( $form, $title, $formcode, $button, $login );
	}
	
	/**
	 * Lists the login method overview screen
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _loginList()
	{
		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		ipsRegistry::getClass('output')->nav[] = array( $this->form_code, $this->lang->words['l_nav'] );
				
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH . 'class_xml.php' );
		$xml			= new class_xml();
		$xml->doc_type	= IPS_DOC_CHAR_SET;
		$content		= "";
		$db_methods		= array();
		$dir_methods	= array();
		$installed		= array();
		
		//-----------------------------------------
		// Get login methods from database
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'login_methods' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$r['login_installed'] 				= 1;
			$db_methods[ $r['login_order'] ]	= $r;
			$installed[]						= $r['login_folder_name'];
		}

		ksort( $db_methods );
		
		//-----------------------------------------
		// Now get the available login methods
		//-----------------------------------------
		
		$dh = opendir( IPS_PATH_CUSTOM_LOGIN );
		
		if ( $dh !== false )
		{
			while ( false !== ($file = readdir($dh) ) )
			{
				if( is_dir( IPS_PATH_CUSTOM_LOGIN . '/' . $file ) AND !in_array( $file, array( '.', '..', '.svn', '.DS_Store' ) ) )
				{
					$data = array( 'login_title' => $file, 'login_folder_name' => $file );
					
					if( file_exists( IPS_PATH_CUSTOM_LOGIN . '/' . $file . '/loginauth_install.xml' ) )
					{
						$file_content = file_get_contents( IPS_PATH_CUSTOM_LOGIN . '/' . $file . '/loginauth_install.xml' );
						
						$xml->xml_parse_document( $file_content );

						if( is_array($xml->xml_array['export']['group']['row']) )
						{
							foreach( $xml->xml_array['export']['group']['row'] as $f => $entry )
							{
								if( is_array($entry) )
								{
									foreach( $entry as $k => $v )
									{
										if ( $f == 'VALUE' or $f == 'login_id' )
										{
											continue;
										}
										
										$data[ $f ] = $v;
									}
								}
							}
						}
					}
					
					$data['acp_plugin']	= 0;

					if( file_exists( IPS_PATH_CUSTOM_LOGIN . '/' . $file . '/acp.php' ) AND file_exists( IPS_PATH_CUSTOM_LOGIN . '/' . $file . '/conf.php' ) )
					{
						$data['acp_plugin']	= 1;
					}

					$dir_methods[ $file ] = $data;
				}
			}
			
			closedir( $dh );
		}

		//-----------------------------------------
		// First...we show installed methods
		//-----------------------------------------
		
		$content 	.= $this->html->login_subheader( $this->lang->words['l_installed'] );
		$dbm_count	= 0;
		
		if( count($db_methods ) )
		{
			foreach( $db_methods as $r )
			{
				$dbm_count++;
				
				$r['_enabled_img']		= $r['login_enabled']   ? 'tick.png' : 'cross.png';
				$r['acp_plugin']		= $dir_methods[ $r['login_folder_name'] ]['acp_plugin'];
				
				$content .= $this->html->login_row($r);
			}
		}
		
		if( !$dbm_count )
		{
			$content .= $this->html->login_norow( "installed" );
		}
		
		//-----------------------------------------
		// Then the ones not installed
		//-----------------------------------------
		
		$content 	.= $this->html->login_subheader( $this->lang->words['l_others'] );
		$dm_count	= 0;
		
		if( count( $dir_methods ) )
		{
			foreach( $dir_methods as $r )
			{
				if( in_array( $r['login_folder_name'], $installed ) )
				{
					continue;
				}
				
				$dm_count++;
				
				// Need to set a bogus login id to ensure HTML ids are unique for the javascript
				$r['login_id']			= str_replace( '.', '', uniqid( 'abc', true ) );
				
				$r['_enabled_img']		= 'cross.png';
				
				$content .= $this->html->login_row($r);
			}
		}
		
		if( !$dm_count )
		{
			$content .= $this->html->login_norow( "uninstalled" );
		}

		ipsRegistry::getClass('output')->html .= $this->html->login_overview( $content );
	}
	
	/**
	 * Toggle login method enabled/disabled
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _loginToggle()
	{
		//--------------------------------------------
		// INIT
		//--------------------------------------------
		
		$login_id	= intval(ipsRegistry::$request['login_id']);
		
		$login		= $this->DB->buildAndFetch( array( 'select' => 'login_id, login_enabled, login_folder_name', 'from' => 'login_methods', 'where' => 'login_id=' . $login_id ) );
		
		if( !$login['login_id'] )
		{
			ipsRegistry::getClass('output')->global_message = $this->lang->words['l_404'];
			$this->_loginList();
			return;
		}
		
		if( $login['login_enabled'] )
		{
			$toggle = $this->lang->words['l_disabled'];
			
			$this->DB->update( 'login_methods', array( 'login_enabled' => 0 ), 'login_id=' . $login_id );
			
			if( $login['login_folder_name'] == 'ipconverge' )
			{
				IPSLib::updateSettings( array( 'ipconverge_enabled' => 0 ) );
			}
		}
		else
		{
			$toggle = $this->lang->words['l_enabled'];
			
			$this->DB->update( 'login_methods', array( 'login_enabled' => 1 ), 'login_id=' . $login_id );
			
			if( $login['login_folder_name'] == 'ipconverge' )
			{
				IPSLib::updateSettings( array( 'ipconverge_enabled' => 1 ) );
			}
		}

		//-----------------------------------------
		// Recache
		//-----------------------------------------
		
		$this->loginsRecache();
		
		ipsRegistry::getClass('output')->global_message = $this->lang->words['l_successfully'] . $toggle;
		
		$this->_loginList();
	}
	
	/**
	 * Uninstall a login method
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _loginUninstall()
	{
		//--------------------------------------------
		// INIT
		//--------------------------------------------
		
		$login_id	= intval(ipsRegistry::$request['login_id']);
		
		$login		= $this->DB->buildAndFetch( array( 'select' => 'login_id, login_enabled, login_folder_name', 'from' => 'login_methods', 'where' => 'login_id=' . $login_id ) );
		
		if( !$login['login_id'] )
		{
			ipsRegistry::getClass('output')->global_message = $this->lang->words['l_404'];
			$this->_loginList();
			return;
		}
		
		$this->DB->delete( 'login_methods', 'login_id=' . $login_id );
		
		if( $login['login_folder_name'] == 'ipconverge' )
		{
			IPSLib::updateSettings( array( 'ipconverge_enabled' => 0 ) );
		}

		//-----------------------------------------
		// Recache
		//-----------------------------------------
		
		$this->loginsRecache();

		ipsRegistry::getClass('output')->global_message = $this->lang->words['l_uninstalled'];
		
		$this->_loginList();
	}
	
	/**
	 * Install a login method
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _loginInstall()
	{
		//--------------------------------------------
		// INIT
		//--------------------------------------------
		
		require_once( IPS_KERNEL_PATH . 'class_xml.php' );
		$xml			= new class_xml();
		$xml->doc_type	= IPS_DOC_CHAR_SET;
		
		$login_id	= basename(ipsRegistry::$request['login_folder']);
		
		//-----------------------------------------
		// Now get the XML data
		//-----------------------------------------
		
		$dh = opendir( IPS_PATH_CUSTOM_LOGIN );
		
		if ( $dh !== false )
		{
			while ( false !== ($file = readdir($dh) ) )
			{
				if( is_dir( IPS_PATH_CUSTOM_LOGIN . '/' . $file ) AND $file == $login_id )
				{
					if( file_exists( IPS_PATH_CUSTOM_LOGIN . '/' . $file . '/loginauth_install.xml' ) )
					{
						$file_content = file_get_contents( IPS_PATH_CUSTOM_LOGIN . '/' . $file . '/loginauth_install.xml' );
						
						$xml->xml_parse_document( $file_content );

						if( is_array($xml->xml_array['export']['group']['row']) )
						{
							foreach( $xml->xml_array['export']['group']['row'] as $f => $entry )
							{
								if( is_array($entry) )
								{
									foreach( $entry as $k => $v )
									{
										if ( $f == 'VALUE' or $f == 'login_id' )
										{
											continue;
										}
										
										$data[ $f ] = $v;
									}
								}
							}
						}
					}
					else
					{
						closedir( $dh );

						ipsRegistry::getClass('output')->global_message = $this->lang->words['l_installer404'];
						$this->_loginList();
						return;
					}
					
					$dir_methods[ $file ] = $data;
					
					break;
				}
			}
			
			closedir( $dh );
		}

		if( !is_array($dir_methods) OR !count($dir_methods) )
		{
			ipsRegistry::getClass('output')->global_message = $this->lang->words['l_installer404'];
			$this->_loginList();
			return;
		}

		//-----------------------------------------
		// Now verify it isn't installed
		//-----------------------------------------
		
		$login		= $this->DB->buildAndFetch( array( 'select' => 'login_id', 'from' => 'login_methods', 'where' => "login_folder_name='" . $login_id . "'" ) );
		
		if( $login['login_id'] )
		{
			ipsRegistry::getClass('output')->global_message = $this->lang->words['l_already'];
			$this->_loginList();
			return;
		}
		
		//-----------------------------------------
		// Get the highest order and insert method
		//-----------------------------------------
		
		$max = $this->DB->buildAndFetch( array( 'select' => 'MAX(login_order) as highest_order', 'from' => 'login_methods' ) );
		
		$dir_methods[ $login_id ]['login_order'] = $max['highest_order'] + 1;
		
		$this->DB->insert( 'login_methods', $dir_methods[ $login_id ] );

		//-----------------------------------------
		// Recache
		//-----------------------------------------
		
		$this->loginsRecache();

		ipsRegistry::getClass('output')->global_message = $this->lang->words['l_yesinstalled'];
		
		$this->_loginList();
	}
	
	/**
	 * Reorder a login method
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _loginReorder()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		require_once( IPS_KERNEL_PATH . 'classAjax.php' );
		$ajax			= new classAjax();
		
		//-----------------------------------------
		// Checks...
		//-----------------------------------------

		if( $this->registry->adminFunctions->checkSecurityKey( $this->request['md5check'], true ) === false )
		{
			$ajax->returnString( $this->lang->words['postform_badmd5'] );
			exit();
		}
 		
 		//-----------------------------------------
 		// Save new position
 		//-----------------------------------------

 		$position	= 1;
 		
 		if( is_array($this->request['logins']) AND count($this->request['logins']) )
 		{
 			foreach( $this->request['logins'] as $this_id )
 			{
 				$this->DB->update( 'login_methods', array( 'login_order' => $position ), 'login_id=' . $this_id );
 				
 				$position++;
 			}
 		}
 		
 		$this->loginsRecache();

 		$ajax->returnString( 'OK' );
 		exit();
	}

	/**
	 * Updates cache store record
	 *
	 * @access	public
	 * @return	boolean		Cache store updated successfully
	 */
	public function loginsRecache()
	{
		$cache	= array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'login_methods', 'where' => 'login_enabled=1', 'order' => 'login_order ASC' ) );
		$this->DB->execute();
	
		while ( $r = $this->DB->fetch() )
		{	
			$cache[ $r['login_id'] ] = $r;
		}
		
		ipsRegistry::cache()->setCache( 'login_methods', $cache, array( 'array' => 1, 'deletefirst' => 1 ) );
	}
		
}
