<?php

/**
 * Invision Power Services
 * IP.CCS block creation wizard
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

class admin_ccs_blocks_wizard extends ipsCommand
{
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
	 * Uploader library
	 *
	 * @access	public
	 * @var		object
	 */
	public $upload;

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
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_blocks' );
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=blocks&amp;section=wizard';
		$this->form_code_js	= $this->html->form_code_js	= 'module=blocks&section=wizard';

		//-----------------------------------------
		// Load Language
		//-----------------------------------------
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_lang' ) );

		//-----------------------------------------
		// Grab extra CSS
		//-----------------------------------------
		
		$this->registry->output->addToDocumentHead( 'importcss', $this->settings['skin_app_url'] . 'css/ccs.css' );
		
		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'editBlockTemplate':
				$this->_editTemplate();
			break;
			
			case 'saveBlockTemplate':
				$this->_saveTemplate();
			break;
			
			case 'editBlock':
				$this->_preLaunchWizard( 'edit' );
			break;
			
			case 'completeBlock':
				$this->_preLaunchWizard( 'complete' );
			break;

			case 'process':
				$this->_saveStep1();
			break;
			
			case 'continue':
			default:
				$this->_wizardProxy();
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}
	
	/**
	 * Saves the block template edits
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function _saveTemplate()
	{
		//-----------------------------------------
		// Get block data
		//-----------------------------------------
		
		$id		= intval($this->request['block']);
		$block	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_blocks', 'where' => 'block_id=' . $id ) );
		
		if( !$block['block_id'] )
		{
			$this->registry->output->showError( $this->lang->words['block_not_found_edit'] );
		}
		
		$config	= unserialize( $block['block_config'] );

		require_once( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/adminInterface.php' );
		require_once( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/' . $block['block_type'] . '/admin.php' );
		
		$className		= "adminBlockHelper_" . $block['block_type'];
		$extender		= new $className( $this->registry );
		
		if( $extender->saveTemplateEdits( $block, $config ) )
		{
			//-----------------------------------------
			// Clear page caches
			//-----------------------------------------
	
			$this->DB->update( 'ccs_pages', array( 'page_cache' => null ) );
				
			$this->registry->output->global_message = $this->lang->words['block_q_edit_saved'];
			
			//-----------------------------------------
			// Show form again?
			//-----------------------------------------
			
			if( $this->request['save_and_reload'] )
			{
				$this->_editTemplate();
				return;
			}
		}
		else
		{
			//-----------------------------------------
			// We expect the helper plugin to set page title
			//-----------------------------------------
			
			$this->_editTemplate( $_POST['content'] );
			return;
		}

		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . '&module=blocks&section=blocks' );
	}
	
	/**
	 * Edit just the block template
	 *
	 * @access	protected
	 * @param	string		Default content (if error)
	 * @return	void
	 */
	protected function _editTemplate( $defaultContent='' )
	{
		//-----------------------------------------
		// Get block data
		//-----------------------------------------
		
		$id		= intval($this->request['block']);
		$block	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_blocks', 'where' => 'block_id=' . $id ) );
		
		if( !$block['block_id'] )
		{
			$this->registry->output->showError( $this->lang->words['block_not_found_edit'] );
		}
		
		$config	= unserialize( $block['block_config'] );
		
		//-----------------------------------------
		// Resetting content?
		//-----------------------------------------
		
		if( $defaultContent )
		{
			$block['block_content']	= $defaultContent;
		}

		require_once( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/adminInterface.php' );
		require_once( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/' . $block['block_type'] . '/admin.php' );
		
		$className		= "adminBlockHelper_" . $block['block_type'];
		$extender		= new $className( $this->registry );
		$editor_area	= $extender->getTemplateEditor( $block, $config );

		//-----------------------------------------
		// And output
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->block_edit( $block, $editor_area );
	}
	
	/**
	 * Wrapper function for the proxy method to load any necessary data
	 *
	 * @access	protected
	 * @param	string		Type of pre-launch [edit|complete]
	 * @return	void
	 */
	protected function _preLaunchWizard( $type='edit' )
	{
		switch( $type )
		{
			case 'edit':
				$id		= intval($this->request['block']);
				$block	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_blocks', 'where' => 'block_id=' . $id ) );

				if( !$block['block_id'] )
				{
					$this->registry->output->showError( $this->lang->words['block_not_found_edit'] );
				}
				
				if( $block['block_type'] AND file_exists( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/' . $block['block_type'] . '/admin.php' ) )
				{
					require_once( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/adminInterface.php' );
					require_once( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/' . $block['block_type'] . '/admin.php' );
					
					$className	= "adminBlockHelper_" . $block['block_type'];
					$extender	= new $className( $this->registry );
					$session	= $extender->createWizardSession( $block );
				}
				else
				{
					$this->registry->output->showError( $this->lang->words['block_not_found_edit'] );
				}
				
				$session['wizard_started']	= time();
				
				$this->DB->insert( 'ccs_block_wizard', $session );
				
				$this->registry->output->silentRedirect( $this->settings['base_url'] . '&module=blocks&section=wizard&wizard_session=' . $session['wizard_id'] . '&continuing=1&step=1' );
			break;
			
			case 'complete':
				if( !$this->request['wizard_session'] )
				{
					$this->registry->output->showError( $this->lang->words['block_cannotfind_session'] );
				}
				
				$session	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_block_wizard', 'where' => "wizard_id='{$this->request['wizard_session']}'" ) );
				
				$this->registry->output->silentRedirect( $this->settings['base_url'] . '&module=blocks&section=wizard&wizard_session=' . $session['wizard_id'] . '&continuing=1&step=' . ( $session['wizard_step'] - 1 ) );
			break;
		}
	}
	
	/**
	 * Save step 1 and continue
	 * We need a special step for step 1 because we don't have the 'type' yet saved
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function _saveStep1()
	{
		$types	= array( 'feed', 'plugin', 'custom' );

		if( !in_array( $this->request['type'], $types ) )
		{
			$this->registry->output->showError( $this->lang->words['block_invalid_type'] );
		}
		
		$sessionId	= $this->request['wizard_session'] ? IPSText::md5Clean( $this->request['wizard_session'] ) : '';

		$session	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_block_wizard', 'where' => "wizard_id='{$sessionId}'" ) );
		
		if( !$session['wizard_id'] )
		{
			$this->registry->output->showError( $this->lang->words['block_invalid_session'] );
		}
		
		$this->DB->update( 'ccs_block_wizard', array( 'wizard_step' => 1, 'wizard_type' => $this->request['type'], 'wizard_name' => $this->request['name'] ), "wizard_id='{$sessionId}'" );
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . '&module=blocks&section=wizard&wizard_session=' . $sessionId );
	}
	
	/**
	 * This is a proxy function.  It determines what step of the wizard we are on and acts appropriately
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function _wizardProxy()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$sessionId	= $this->request['wizard_session'] ? IPSText::md5Clean( $this->request['wizard_session'] ) : md5( uniqid( microtime(), true ) );
		
		$session	= array();
		
		if( $this->request['wizard_session'] )
		{
			$session	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_block_wizard', 'where' => "wizard_id='{$sessionId}'" ) );
		}
		else
		{
			$this->DB->insert( 'ccs_block_wizard', array( 'wizard_id' => $sessionId, 'wizard_step' => 0, 'wizard_started' => time() ) );
		}

		//-----------------------------------------
		// Proxy off to appropriate function
		//-----------------------------------------
		
		if( $session['wizard_type'] AND file_exists( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/' . $session['wizard_type'] . '/admin.php' ) )
		{
			require_once( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/adminInterface.php' );
			require_once( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/' . $session['wizard_type'] . '/admin.php' );
			
			$className	= "adminBlockHelper_" . $session['wizard_type'];
			$extender	= new $className( $this->registry );
			
			$this->registry->output->html .= $extender->returnNextStep( $session );
		}
		else
		{
			$_blockTypes	= array();
			
			require_once( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/adminInterface.php' );
			
			foreach( new DirectoryIterator( IPSLib::getAppDir('ccs') . '/sources/blocks' ) as $object )
			{
				if( $object->isDir() AND !$object->isDot() )
				{
					if( file_exists( $object->getPathname() . '/admin.php' ) )
					{
						$_folder	= str_replace( IPSLib::getAppDir('ccs') . '/sources/blocks/', '', str_replace( '\\', '/', $object->getPathname() ) );

						require_once( $object->getPathname() . '/admin.php' );
						$_className		= "adminBlockHelper_" . $_folder;
						$_class 		= new $_className( $this->registry );
						$_blockTypes[]	= $_class->getBlockConfig();
					}
				}
			}

			$this->registry->output->html .= $this->html->wizard_step_1( $sessionId, $_blockTypes );
		}
	}
}
