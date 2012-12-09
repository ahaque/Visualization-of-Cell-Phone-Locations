<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Chat services
 * Last Updated: $Date: 2009-02-25 18:07:38 -0500 (Wed, 25 Feb 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Chat
 * @since		17 February 2003
 * @version		$Revision: 4106 $
 *
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}


class admin_chat_addonchat_chat extends ipsCommand
{
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
		// Load HTML
		//-----------------------------------------
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_chat' );
		
		$this->registry->class_localization->loadLanguageFile( array( 'admin_chat' ) );
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=addonchat&amp;section=chat';
		$this->form_code_js	= $this->html->form_code_js	= 'module=addonchat&section=chat';
		
		switch($this->request['do'])
		{
			case 'chatsettings':
				$this->_chatConfig();
			break;
			case 'chatsave':
				$this->_chatSave();
			break;
			
			default:
				$this->_chatSplash();
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}
	
	/**
	 * Chat splash page
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _chatSplash()
	{
		//-----------------------------------------
		// Do we have an order number
		//-----------------------------------------
		
		if ( $this->settings['chat_account_no'] )
		{
			$this->_chatConfig();
		}
		else
		{
			$this->registry->output->html 				= $this->html->addonchatKey();
			$this->registry->output->showInsideIframe( '' );
		}
	}

	/**
	 * Save your key to enable chat
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _chatSave()
	{
		$acc_number = $this->request['account_no'];
		
		if ( $acc_number == "" )
		{
			$this->registry->output->showError( $this->lang->words['addon_invalid_key'], 1190 );
		}

		//-----------------------------------------
		// Load libby-do-dah
		//-----------------------------------------
		
		require_once( IPSLib::getAppDir( 'core' ).'/modules_admin/tools/settings.php' );
		$settings				=  new admin_core_tools_settings();
		$settings->makeRegistryShortcuts( $this->registry );
		
		$settings->html			= $this->registry->output->loadTemplate( 'cp_skin_tools', 'core' );
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_tools' ), 'core' );
		$settings->form_code	= $settings->html->form_code    = 'module=tools&amp;section=settings';
		$settings->form_code_js	= $settings->html->form_code_js = 'module=tools&section=settings';

		$this->DB->update( 'core_sys_conf_settings', array( 'conf_value' => $acc_number ), "conf_key='chat_account_no'" );
		$this->DB->update( 'core_sys_conf_settings', array( 'conf_value' => '' )         , "conf_key='chat04_account_no'" );

		$settings->settingsRebuildCache();

		$this->DB->update( 'core_sys_module', array( 'sys_module_position' => 2 ), "sys_module_key='parachat'" );
		$this->DB->update( 'core_sys_module', array( 'sys_module_position' => 1 ), "sys_module_key='addonchat'" );
		
		$this->cache->rebuildCache( 'module_cache', 'global' );
		
		//-----------------------------------------
		// Show config
		//-----------------------------------------
		
		$this->_chatConfig();
	}
	
	/**
	 * Show the configuration page
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _chatConfig()
	{
		//-----------------------------------------
		// Load libby-do-dah
		//-----------------------------------------
		
		require_once( IPSLib::getAppDir( 'core' ).'/modules_admin/tools/settings.php' );
		$settings				=  new admin_core_tools_settings();
		$settings->makeRegistryShortcuts( $this->registry );
		
		$settings->html			= $this->registry->output->loadTemplate( 'cp_skin_tools', 'core' );
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_tools' ), 'core' );
		$settings->form_code	= $settings->html->form_code    = 'module=tools&amp;section=settings';
		$settings->form_code_js	= $settings->html->form_code_js = 'module=tools&section=settings';

		//-----------------------------------------
		// Did we reset the component?
		//-----------------------------------------
		
		if ( ! $this->settings['chat_account_no'] )
		{
			$this->_chatSplash();
		}
		
		$this->request['conf_title_keyword']	= 'chat';
		$settings->return_after_save			= $this->settings['base_url'] . $this->form_code . '&do=chatsettings';

		$settings->_viewSettings();
	}

}