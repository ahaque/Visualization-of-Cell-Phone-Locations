<?php

/**
 * Invision Power Services
 * IP.CCS manage settings
 * Last Updated: $Date: 2009-08-11 10:01:08 -0400 (Tue, 11 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		IP.CCS
 * @link		http://www.
 * @since		1st March 2009
 * @version		$Revision: 42 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}


class admin_ccs_settings_settings extends ipsCommand
{
	/**
	 * Settings gateway
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $settingsClass;
	
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
		// Downloading htaccess file?
		//-----------------------------------------
		
		if( $this->request['do'] == 'download' )
		{
			$this->_downloadHtaccess();
		}
		
		//-----------------------------------------
		// Load settings controller
		//-----------------------------------------
		
		$this->registry->class_localization->loadLanguageFile( array( 'admin_tools' ), 'core' );
		$this->registry->class_localization->loadLanguageFile( array( 'admin_lang' ), 'ccs' );
		
		require_once( IPSLib::getAppDir( 'core' ) . '/modules_admin/tools/settings.php' );
		$this->settingsClass		= new admin_core_tools_settings( $this->registry );
		$this->settingsClass->makeRegistryShortcuts( $this->registry );
		$this->settingsClass->html				= $this->registry->output->loadTemplate( 'cp_skin_tools', 'core' );
		$this->settingsClass->form_code			= $this->settingsClass->html->form_code		= 'module=tools&amp;section=settings';
		$this->settingsClass->form_code_js		= $this->settingsClass->html->form_code_js	= 'module=tools&section=settings';
		$this->settingsClass->return_after_save	= $this->settings['base_url'] . '&module=settings';

		//-----------------------------------------
		// Show settings form
		//-----------------------------------------
		
		if( $this->request['do'] == 'advanced' )
		{
			$this->registry->output->global_message	= $this->lang->words['advanced_settings_help'];
			
			$this->request['conf_title_keyword']	= 'ccs_advanced';
			
			$this->settingsClass->return_after_save .= "&do=advanced";
		}
		else
		{
			$this->request['conf_title_keyword']	= 'ccs';
		}

		//-----------------------------------------
		// View settings
		//-----------------------------------------
		
		$this->settingsClass->_viewSettings();
		
		//-----------------------------------------
		// Add download button
		//-----------------------------------------
		
		if( $this->request['do'] == 'advanced' )
		{
			$html = $this->registry->output->loadTemplate( 'cp_skin_filemanager' );
			
			$this->registry->getClass('output')->html	= preg_replace( "/(<div class='section_title'>(\s+?)<h2>.+?<\/h2>)/is", "\\1" . $html->downloadHtaccess(), $this->registry->getClass('output')->html );
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}
	
	/**
	 * Download the .htaccess file
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function _downloadHtaccess()
	{
		$_parse	= parse_url( $this->settings['ccs_root_url'] );
		$_root	= preg_replace( "#/$#", "", $_parse['path'] );
		$_root	= str_replace( $this->settings['ccs_root_filename'], '', $_root );
		$_root	= $_root ? $_root : '/';
		$_path	= str_replace( '//', '/', $_root . '/' . $this->settings['ccs_root_filename'] );
		
		$htaccess	= <<<EOF
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase {$_root}
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . {$_path} [L]
</IfModule>
EOF;
		
		$this->registry->output->showDownload( $htaccess, '.htaccess', '', 0 );
		
		exit();
	}
}