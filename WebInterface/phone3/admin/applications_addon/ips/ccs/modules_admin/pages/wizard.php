<?php

/**
 * Invision Power Services
 * IP.CCS page creation wizard
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

class admin_ccs_pages_wizard extends ipsCommand
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
	 * HTML object
	 *
	 * @access	public
	 * @var		object
	 */
	public $html;

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
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_pages' );
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=pages&amp;section=wizard';
		$this->form_code_js	= $this->html->form_code_js	= 'module=pages&section=wizard';

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
			case 'quickEdit':
				$this->_quickEditPage();
			break;
			
			case 'saveQuickEdit':
				$this->_saveQuickEdit();
			break;
			
			case 'editPage':
				$this->_preLaunchWizard( 'edit' );
			break;
			
			case 'completePage':
				$this->_preLaunchWizard( 'complete' );
			break;
			
			case 'saveEasyPage':
				$this->saveEasyForm();
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
	 * Saves the quick edit form
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function _saveQuickEdit()
	{
		//-----------------------------------------
		// Get page data
		//-----------------------------------------
		
		$id		= intval($this->request['page']);
		$page	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_pages', 'where' => 'page_id=' . $id ) );

		if( !$page['page_id'] )
		{
			$this->registry->output->showError( $this->lang->words['page_not_found_edit'] );
		}
		
		//-----------------------------------------
		// Prepare array for save
		//-----------------------------------------
		
		$this->request['page_seo_name']		= $this->_cleanSEOName( $this->request['page_seo_name'] );
		
		$_save	= array(
						'page_name'				=> $this->request['page_name'],
						'page_description'		=> trim($_POST['page_description']),
						'page_seo_name'			=> $this->request['page_seo_name'],
						'page_folder'			=> $this->request['page_folder'],
						'page_meta_keywords'	=> $this->request['page_meta_keywords'],
						'page_meta_description'	=> $this->request['page_meta_description'],
						'page_cache_ttl'		=> $this->request['page_cache_ttl'],
						'page_ipb_wrapper'		=> intval($this->request['page_ipb_wrapper']),
						);

		if( $this->request['all_masks'] )
		{
			$_save['page_view_perms']	= '*';
		}
		else if( is_array($this->request['masks']) )
		{
			$_save['page_view_perms']	= implode( ',', $this->request['masks'] );
		}

		//-----------------------------------------
		// Make sure name is unique
		//-----------------------------------------
		
		$_where	= " AND page_id<>{$page['page_id']}";
		
		$check	= $this->DB->buildAndFetch( array( 'select' => 'page_id', 'from' => 'ccs_pages', 'where' => "page_seo_name='{$_save['page_seo_name']}' AND page_folder='{$_save['page_folder']}'{$_where}" ) );
		
		if( $check['page_id'] )
		{
			$this->registry->output->showError( $this->lang->words['wizard_page_exists'] );
		}

		//-----------------------------------------
		// Checks passed - get page content
		//-----------------------------------------
		
		if( $page['page_type'] == 'bbcode' )
		{
			IPSText::getTextClass( 'bbcode' )->bypass_badwords		= 1;
			IPSText::getTextClass( 'bbcode' )->parse_smilies		= 1;
			IPSText::getTextClass( 'bbcode' )->parse_html			= 1;
			IPSText::getTextClass( 'bbcode' )->parse_nl2br			= 1;
			IPSText::getTextClass( 'bbcode' )->parse_bbcode			= 1;
			IPSText::getTextClass( 'bbcode' )->parsing_section		= 'global';

			$_save['page_content']	= IPSText::getTextClass( 'editor' )->processRawPost( 'page_content' );
			$_save['page_content']	= IPSText::getTextClass( 'bbcode' )->preDbParse( $_save['page_content'] );
		}
		else
		{
			$_save['page_content']	= $_POST['page_content'];
		}
		
		$_save['page_content']	= str_replace( '&#46;&#46;/', '../', trim($_save['page_content']) );
		
		//-----------------------------------------
		// Have names?
		//-----------------------------------------
		
		if( !$_save['page_name'] OR !$_save['page_seo_name'] )
		{
			//$this->registry->output->showError( $this->lang->words['missing_page_details'] );
			$this->_quickEditPage( $this->lang->words['missing_page_details'], $_save );
			return;
		}
		
		//-----------------------------------------
		// PHP page with <?php tag?
		//-----------------------------------------
		
		if( $page['page_type'] == 'php' )
		{
			if( strpos( $_save['page_content'], '<?php' ) !== false )
			{
				$this->_quickEditPage( $this->lang->words['php_page_php_tag'], $_save );
				return;
			}
		}

		//-----------------------------------------
		// Update cache
		//-----------------------------------------

		if( $_save['page_cache_ttl'] == '*' OR intval($_save['page_cache_ttl']) > 0 )
		{
			require_once( IPSLib::getAppDir('ccs') . '/modules_admin/pages/pages.php' );
			$_pagesLib	= new admin_ccs_pages_pages( $this->registry );
			$_pagesLib->makeRegistryShortcuts( $this->registry );
			$_save['page_cache']		= $_pagesLib->recachePage( array_merge( $page, $_save ), true );
			$_save['page_cache_last']	= time();
		}

		//-----------------------------------------
		// Update database
		//-----------------------------------------
		
		$this->DB->update( 'ccs_pages', $_save, 'page_id='. $page['page_id'] );

		$this->registry->output->global_message = $this->lang->words['quick_edit_saved'];

		//-----------------------------------------
		// Show form again?
		//-----------------------------------------
		
		if( $this->request['save_and_reload'] )
		{
			$this->_quickEditPage();
			return;
		}

		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . '&module=pages&section=list&do=viewdir&dir=' . urlencode($_save['page_folder']) );
	}
	
	/**
	 * Edit basic page details without launching wizard
	 *
	 * @access	protected
	 * @param	string		Error message
	 * @param	array 		Defaults (if there was an error saving)
	 * @return	void
	 */
	protected function _quickEditPage( $error='', $defaults=array() )
	{
		//-----------------------------------------
		// Get page data
		//-----------------------------------------
		
		$id		= intval($this->request['page']);
		$page	= $this->DB->buildAndFetch( array(
												'select'	=> 'p.*',
												'from'		=> array( 'ccs_pages' => 'p' ), 
												'where'		=> 'p.page_id=' . $id,
												'add_join'	=> array(
																	array(
																		'select'	=> 't.template_name as page_template_title',
																		'from'		=> array( 'ccs_page_templates' => 't' ),
																		'where'		=> 't.template_id=p.page_template_used',
																		'type'		=> 'left',
																		)
																	)
										)		);

		if( !$page['page_id'] )
		{
			$this->registry->output->showError( $this->lang->words['page_not_found_edit'] );
		}
		
		//-----------------------------------------
		// Coming from a save?
		//-----------------------------------------
		
		if( $error )
		{
			$this->registry->output->global_error	= $error;
		}

		if( is_array($defaults) AND count($defaults) )
		{
			$page	= array_merge( $page, $defaults );
		}
		
		//-----------------------------------------
		// Is this a different file type?
		//-----------------------------------------
		
		if( $page['page_content_type'] != 'page' )
		{
			$this->easyForm( 'edit', $page['page_content_type'] );
			return;
		}
		
		//-----------------------------------------
		// Sort out editor
		//-----------------------------------------
		
		if( $page['page_type'] == 'bbcode' )
		{
			IPSText::getTextClass( 'bbcode' )->bypass_badwords		= 1;
			IPSText::getTextClass( 'bbcode' )->parse_smilies		= 1;
			IPSText::getTextClass( 'bbcode' )->parse_html			= 1;
			IPSText::getTextClass( 'bbcode' )->parse_nl2br			= 1;
			IPSText::getTextClass( 'bbcode' )->parse_bbcode			= 1;
			IPSText::getTextClass( 'bbcode' )->parsing_section		= 'global';

			if ( IPSText::getTextClass( 'editor' )->method == 'rte' )
			{
				$content = IPSText::getTextClass( 'bbcode' )->convertForRTE( $page['page_content'] );
			}
			else
			{
				$content = IPSText::getTextClass( 'bbcode' )->preEditParse( $page['page_content'] );
			}
			
			$editor_area	= IPSText::getTextClass( 'editor' )->showEditor( $content, 'page_content' );
		}
		else
		{
			$editor_area	= $this->registry->output->formTextarea( "page_content", htmlspecialchars($page['page_content']), 100, 30, "page_content", "style='width:100%;'" );
		}
		
		//-----------------------------------------
		// Folders
		//-----------------------------------------
		
		$folders		= array();

		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_folders' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$folders[]	= array( $r['folder_path'], $r['folder_path'] );
		}
		
		//-----------------------------------------
		// Permission masks
		//-----------------------------------------
		
		$masks	= array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'forum_perms', 'order' => 'perm_name ASC' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$masks[]	= array( $r['perm_id'], $r['perm_name'] );
		}
		
		//-----------------------------------------
		// And output
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->page_edit( $page, $editor_area, $folders, $masks );
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
				$id		= intval($this->request['page']);
				$page	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_pages', 'where' => 'page_id=' . $id ) );

				if( !$page['page_id'] )
				{
					$this->registry->output->showError( $this->lang->words['page_not_found_edit'] );
				}

				$session	= array(
									'wizard_id'					=> md5( uniqid( microtime(), true ) ),
									'wizard_step'				=> 0,
									'wizard_name'				=> $page['page_name'],
									'wizard_description'		=> $page['page_description'],
									'wizard_folder'				=> $page['page_folder'],
									'wizard_type'				=> $page['page_type'],
									'wizard_template'			=> $page['page_template_used'],
									'wizard_content'			=> $page['page_content'],
									'wizard_cache_ttl'			=> $page['page_cache_ttl'],
									'wizard_perms'				=> $page['page_view_perms'],
									'wizard_seo_name'			=> $page['page_seo_name'],
									'wizard_content_only'		=> $page['page_content_only'],
									'wizard_edit_id'			=> $page['page_id'],
									'wizard_meta_keywords'		=> $page['page_meta_keywords'],
									'wizard_meta_description'	=> $page['page_meta_description'],
									'wizard_ipb_wrapper'		=> $page['page_ipb_wrapper'],
									'wizard_started'			=> time(),
									);

				$this->DB->insert( 'ccs_page_wizard', $session );
				
				$this->registry->output->silentRedirect( $this->settings['base_url'] . '&module=pages&section=wizard&continuing=1&wizard_session=' . $session['wizard_id'] );
			break;
			
			case 'complete':
				if( !$this->request['wizard_session'] )
				{
					$this->registry->output->showError( $this->lang->words['page_cannotfind_session'] );
				}
				
				$session	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_page_wizard', 'where' => "wizard_id='{$this->request['wizard_session']}'" ) );
				
				$this->registry->output->silentRedirect( $this->settings['base_url'] . '&module=pages&section=wizard&continuing=1&wizard_session=' . $session['wizard_id'] . '&step=' . ( $session['wizard_step'] -1 ) );
			break;
		}
	}
	
	/**
	 * Easy form - used for CSS and JS content types
	 *
	 * @access	protected
	 * @param	string		Type of form (add|edit)
	 * @param	string		Content type (css|js)
	 * @return	void
	 */
	protected function easyForm( $formType, $contentType )
	{
		$page	= array();

		if( $formType == 'edit' )
		{
			$id		= intval($this->request['page']);
			
			$page	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_pages', 'where' => 'page_id=' . $id ) );
			
			if( !$page['page_id'] )
			{
				$this->registry->output->showError( $this->lang->words['pagefile_not_found_edit'] );
			}
			
			//-----------------------------------------
			// Hopefully they don't name something "file.css.css" on purpose
			//-----------------------------------------
			
			$page['page_seo_name']	= str_replace( array( '.js', '.css' ), '', $page['page_seo_name'] );
		}
		
		//-----------------------------------------
		// Get folders
		//-----------------------------------------
		
		$folders		= array();

		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_folders' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$folders[]	= array( $r['folder_path'], $r['folder_path'] );
		}
		
		$this->registry->output->html	= $this->html->easyPageForm( $formType, $contentType, $page, $folders );
	}
	
	/**
	 * Save the easy form (css or javascript)
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function saveEasyForm()
	{
		$page	= array();

		if( $this->request['type'] == 'edit' )
		{
			$id		= intval($this->request['page']);
			
			$page	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_pages', 'where' => 'page_id=' . $id ) );
			
			if( !$page['page_id'] )
			{
				$this->registry->output->showError( $this->lang->words['pagefile_not_found_edit'] );
			}
		}
		
		//-----------------------------------------
		// Prepare array for save
		//-----------------------------------------
		
		$this->request['page_seo_name']		= $this->_cleanSEOName( $this->request['page_seo_name'] );
		
		$_save	= array(
						'page_name'				=> $this->request['page_seo_name'] . '.' . $this->request['content_type'],
						'page_description'		=> $_POST['page_description'],
						'page_seo_name'			=> $this->request['page_seo_name'] . '.' . $this->request['content_type'],
						'page_folder'			=> $this->request['page_folder'],
						'page_view_perms'		=> '*',
						'page_cache_ttl'		=> '*',
						'page_content'			=> str_replace( '&#46;&#46;/', '../', trim($_POST['content']) ),
						'page_cache'			=> str_replace( '&#46;&#46;/', '../', trim($_POST['content']) ),
						'page_content_type'		=> $this->request['content_type'],
						'page_last_edited'		=> time(),
						);

		//-----------------------------------------
		// Have names?
		//-----------------------------------------
		
		if( !$_save['page_seo_name'] )
		{
			$this->registry->output->showError( $this->lang->words['missing_pagefile_details'] );
		}

		//-----------------------------------------
		// Make sure name is unique
		//-----------------------------------------
		
		if( $page['page_id'] )
		{
			$_where	= " AND page_id<>{$page['page_id']}";
		}
		
		$check	= $this->DB->buildAndFetch( array( 'select' => 'page_id', 'from' => 'ccs_pages', 'where' => "page_seo_name='{$_save['page_seo_name']}' AND page_folder='{$_save['page_folder']}'{$_where}" ) );
		
		if( $check['page_id'] )
		{
			$this->registry->output->showError( $this->lang->words['wizard_pagefile_exists'] );
		}

		//-----------------------------------------
		// Update database
		//-----------------------------------------
		
		if( $this->request['type'] == 'edit' )
		{
			$this->DB->update( 'ccs_pages', $_save, 'page_id='. $page['page_id'] );
		}
		else
		{
			$this->DB->insert( 'ccs_pages', $_save );
			
			$this->request['page']	= $this->DB->getInsertId();
		}

		$this->registry->output->global_message = $this->lang->words['pagefile_saved'];

		//-----------------------------------------
		// Show form again?
		//-----------------------------------------
		
		if( $this->request['save_and_reload'] )
		{
			$this->easyForm( 'edit', $this->request['content_type'] );
			return;
		}

		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . '&module=pages&section=list&do=viewdir&dir=' . urlencode($_save['page_folder']) );
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
		// If it's a different type - proxy there
		//-----------------------------------------
		
		if( $this->request['fileType'] == 'css' OR $this->request['fileType'] == 'js' )
		{
			return $this->easyForm( 'add', $this->request['fileType'] );
		}
		
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$sessionId	= $this->request['wizard_session'] ? IPSText::md5Clean( $this->request['wizard_session'] ) : md5( uniqid( microtime(), true ) );
		
		$session	= array( 'wizard_step' => 0, 'wizard_id' => $sessionId, 'wizard_started' => time(), 'wizard_ipb_wrapper' => $this->settings['ccs_use_ipb_wrapper'] );
		
		if( $this->request['wizard_session'] )
		{
			$session	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_page_wizard', 'where' => "wizard_id='{$sessionId}'" ) );
		}
		else
		{
			$this->DB->insert( 'ccs_page_wizard', $session );
		}

		$session['wizard_step']	= $this->request['step'] ? $this->request['step'] : 0;

		//-----------------------------------------
		// Got stuff to save?
		//-----------------------------------------
		
		if( $session['wizard_step'] > 0 AND !$this->request['continuing'] )
		{
			$session	= $this->_storeSubmittedData( $session );
		}

		//-----------------------------------------
		// Proxy off to appropriate function
		//-----------------------------------------
		
		$step		= $session['wizard_step'] + 1;
		$step		= $step > 0 ? $step : 1;
		$_func 		= "wizard_step_" . $step;
		$additional	= array();

		switch( $step )
		{
			//-----------------------------------------
			// Step 1: Grab folders and templates for form
			//-----------------------------------------
			
			case 1:
				$additional['folders']		= array();
				$additional['templates']	= array();
				
				//-----------------------------------------
				// Get templates
				//-----------------------------------------
				
				$this->DB->build( array( 'select' => '*', 'from' => 'ccs_page_templates', 'order' => 'template_name ASC' ) );
				$this->DB->execute();
				
				while( $r = $this->DB->fetch() )
				{
					$additional['templates'][]	= array( $r['template_id'], $r['template_name'] );
				}
				
				//-----------------------------------------
				// Get folders
				//-----------------------------------------
				
				$folders		= array();
		
				$this->DB->build( array( 'select' => '*', 'from' => 'ccs_folders' ) );
				$this->DB->execute();
				
				while( $r = $this->DB->fetch() )
				{
					$additional['folders'][]	= array( $r['folder_path'], $r['folder_path'] );
				}
				
				//-----------------------------------------
				// Edit content only by default
				//-----------------------------------------
				
				if( !$session['wizard_edit_id'] )
				{
					$session['wizard_content_only']	= 1;
				}
			break;
			
			//-----------------------------------------
			// Step 2: Show the appropriate editor
			//-----------------------------------------
			
			case 2:
				//-----------------------------------------
				// If we are not editing content only, not
				//	editing an existing page, and have a 
				//	template id, get it as default content
				//-----------------------------------------
				
				if( !$session['wizard_content_only'] AND !$session['wizard_edit_id'] AND $session['wizard_template'] )
				{
					$template	= $this->DB->buildAndFetch( array( 'select' => 'template_content', 'from' => 'ccs_page_templates', 'where' => 'template_id=' . intval($session['wizard_template']) ) );

					$session['wizard_content']	= $template['template_content'];
				}

				//-----------------------------------------
				// Sort parse for editor
				//-----------------------------------------
				
				if( $session['wizard_type'] == 'bbcode' )
				{
					IPSText::getTextClass( 'bbcode' )->bypass_badwords		= 1;
					IPSText::getTextClass( 'bbcode' )->parse_smilies		= 1;
					IPSText::getTextClass( 'bbcode' )->parse_html			= 1;
					IPSText::getTextClass( 'bbcode' )->parse_nl2br			= 1;
					IPSText::getTextClass( 'bbcode' )->parse_bbcode			= 1;
					IPSText::getTextClass( 'bbcode' )->parsing_section		= 'global';

					if( $session['wizard_previous_type'] != 'bbcode' )
					{
						$session['wizard_content']	= IPSText::getTextClass( 'bbcode' )->preDbParse( $session['wizard_content'] );
					}

					if ( IPSText::getTextClass( 'editor' )->method == 'rte' )
					{
						$content = IPSText::getTextClass( 'bbcode' )->convertForRTE( $session['wizard_content'] );
					}
					else
					{
						$content = IPSText::getTextClass( 'bbcode' )->preEditParse( $session['wizard_content'] );
					}
					
					$editor_area	= IPSText::getTextClass( 'editor' )->showEditor( $content, 'content' );
				}
				else
				{
					if( $session['wizard_previous_type'] == 'bbcode' )
					{
						$session['wizard_content']	= html_entity_decode( $session['wizard_content'], ENT_QUOTES );
					}
					
					$editor_area	= $this->registry->output->formTextarea( "content", htmlspecialchars( $session['wizard_content'] ), 100, 30, "content", "style='width:100%;'" );
				}
				
				$additional['editor']	= $editor_area;
			break;
			
			//-----------------------------------------
			// Step 4: Permissions
			//-----------------------------------------
			
			case 4:
				if( $session['wizard_perms'] == '*' OR !$session['wizard_edit_id'] )
				{
					$additional['all_masks']	= 1;
				}
				else
				{
					$additional['masks']		= explode( ',', $session['wizard_perms'] );
				}
				
				$additional['avail_masks']	= array();
				
				$this->DB->build( array( 'select' => '*', 'from' => 'forum_perms', 'order' => 'perm_name ASC' ) );
				$this->DB->execute();
				
				while( $r = $this->DB->fetch() )
				{
					$additional['avail_masks'][]	= array( $r['perm_id'], $r['perm_name'] );
				}
			break;
			
			//-----------------------------------------
			// Step 5: Save to DB, destroy wizard session,
			//	show complete page
			//-----------------------------------------
			
			case 5:
				$page	= array(
								'page_name'				=> $session['wizard_name'],
								'page_seo_name'			=> $session['wizard_seo_name'],
								'page_description'		=> $session['wizard_description'],
								'page_folder'			=> $session['wizard_folder'],
								'page_type'				=> $session['wizard_type'],
								'page_last_edited'		=> time(),
								'page_template_used'	=> $session['wizard_template'],
								'page_content'			=> $session['wizard_content'],
								'page_view_perms'		=> $session['wizard_perms'],
								'page_cache_ttl'		=> $session['wizard_cache_ttl'],
								'page_content_only'		=> $session['wizard_content_only'],
								'page_meta_keywords'	=> $session['wizard_meta_keywords'],
								'page_meta_description'	=> $session['wizard_meta_description'],
								'page_content_type'		=> 'page',
								'page_ipb_wrapper'		=> $session['wizard_ipb_wrapper'],
								);

				if( $page['page_cache_ttl'] )
				{
					require_once( IPSLib::getAppDir('ccs') . '/sources/pages.php' );
					$pageBuilder	= new pageBuilder( $this->registry );;
					
					$page['page_cache']			= $pageBuilder->recachePage( $page );
					$page['page_cache_last']	= time();
				}
				
				if( $session['wizard_edit_id'] )
				{
					$this->DB->update( 'ccs_pages', $page, 'page_id=' . $session['wizard_edit_id'] );
					$page['page_id']	= $session['wizard_edit_id'];
				}
				else
				{
					$this->DB->insert( 'ccs_pages', $page );
					$page['page_id']	= $this->DB->getInsertId();
				}

				$this->DB->delete( 'ccs_page_wizard', "wizard_id='{$session['wizard_id']}'" );
				
				$session	= array_merge( $session, $page );
			break;
		}
		
		$this->registry->output->html .= $this->html->$_func( $session, $additional );
	}
	
	/**
	 * Store the data submitted for the last step
	 *
	 * @access	protected
	 * @param	array 		Session data
	 * @return	array 		Session data (updated)
	 */
	protected function _storeSubmittedData( $session )
	{
		switch( $session['wizard_step'] )
		{
			case 1:
				//-----------------------------------------
				// Clean the SEO name...
				//-----------------------------------------
				
				$this->request['page_name']		= $this->_cleanSEOName( $this->request['page_name'] );

				$dataToSave	= array(
									'wizard_name'				=> $this->request['name'],
									'wizard_description'		=> $_POST['description'],
									'wizard_seo_name'			=> $this->request['page_name'],
									'wizard_folder'				=> $this->request['folder'],
									'wizard_type'				=> ( $this->request['type'] AND in_array( $this->request['type'], array( 'bbcode', 'html', 'php' ) ) ) ? $this->request['type'] : 'bbcode',
									'wizard_template'			=> intval($this->request['template']),
									'wizard_content_only'		=> intval($this->request['content_only']),
									'wizard_meta_keywords'		=> $this->request['meta_keywords'],
									'wizard_meta_description'	=> $this->request['meta_description'],
									'wizard_previous_type'		=> $session['wizard_type'],
									'wizard_ipb_wrapper'		=> intval($this->request['ipb_wrapper']),
									);

				//-----------------------------------------
				// Have names?
				//-----------------------------------------
				
				if( !$dataToSave['wizard_name'] OR !$dataToSave['wizard_seo_name'] )
				{
					$session['wizard_step']--;

					$this->registry->output->global_error	= $this->lang->words['missing_page_details'];
					return array_merge( $session, $dataToSave );
				}

				//-----------------------------------------
				// Make sure name is unique
				//-----------------------------------------
				
				$_where	= $session['wizard_edit_id'] ? " AND page_id<>{$session['wizard_edit_id']}" : '';
				
				$check	= $this->DB->buildAndFetch( array( 'select' => 'page_id', 'from' => 'ccs_pages', 'where' => "page_seo_name='{$dataToSave['wizard_seo_name']}' AND page_folder='{$dataToSave['wizard_folder']}'{$_where}" ) );
				
				if( $check['page_id'] )
				{
					$session['wizard_step']--;

					$this->registry->output->global_error	= $this->lang->words['wizard_page_exists'];
					return array_merge( $session, $dataToSave );
				}

				$this->_saveToDb( $session['wizard_id'], $session['wizard_step'], $dataToSave );
			break;
			
			case 2:
				if( $session['wizard_type'] == 'bbcode' )
				{
					IPSText::getTextClass( 'bbcode' )->bypass_badwords		= 1;
					IPSText::getTextClass( 'bbcode' )->parse_smilies		= 1;
					IPSText::getTextClass( 'bbcode' )->parse_html			= 1;
					IPSText::getTextClass( 'bbcode' )->parse_nl2br			= 1;
					IPSText::getTextClass( 'bbcode' )->parse_bbcode			= 1;
					IPSText::getTextClass( 'bbcode' )->parsing_section		= 'global';

					$dataToSave['wizard_content']	= IPSText::getTextClass( 'editor' )->processRawPost( 'content' );
					$dataToSave['wizard_content']	= IPSText::getTextClass( 'bbcode' )->preDbParse( $dataToSave['wizard_content'] );
				}
				else
				{
					$dataToSave['wizard_content']	= $_POST['content'];
				}
				
				$dataToSave['wizard_content']	= str_replace( '&#46;&#46;/', '../', trim($dataToSave['wizard_content']) );
				
				//-----------------------------------------
				// PHP page with <?php tag?
				//-----------------------------------------
				
				if( $session['wizard_type'] == 'php' )
				{
					if( strpos( $dataToSave['wizard_content'], '<?php' ) !== false )
					{
						//-----------------------------------------
						// Reset wizard step
						//-----------------------------------------
						
						$session['wizard_step']--;

						$this->registry->output->global_error	= $this->lang->words['php_page_php_tag'];
						return array_merge( $session, $dataToSave );
					}
				}
				
				$this->_saveToDb( $session['wizard_id'], $session['wizard_step'], $dataToSave );
			break;
			
			case 3:
				$dataToSave['wizard_cache_ttl']		= $this->request['cache_ttl'];
				
				$this->_saveToDb( $session['wizard_id'], $session['wizard_step'], $dataToSave );
			break;
			
			case 4:
				if( $this->request['all_masks'] )
				{
					$dataToSave['wizard_perms']	= '*';
				}
				else if( is_array($this->request['masks']) )
				{
					$dataToSave['wizard_perms']	= implode( ',', $this->request['masks'] );
				}

				$this->_saveToDb( $session['wizard_id'], $session['wizard_step'], $dataToSave );
			break;
		}
		
		return array_merge( $session, $dataToSave );
	}
	
	/**
	 * Run DB update query
	 *
	 * @access	protected
	 * @param	string		Session ID
	 * @param	integer		Current step
	 * @param	array 		Config data
	 * @return	bool
	 */
	protected function _saveToDb( $sessionId, $currentStep, $dataToSave )
	{
		$dataToSave['wizard_step']	= $currentStep + 1;
		
		$this->DB->update( 'ccs_page_wizard', $dataToSave, "wizard_id='{$sessionId}'" );
		return true;
	}
	
	/**
	 * Clean the SEO name
	 *
	 * @access	protected
	 * @param	string		SEO name
	 * @return	string		Cleaned SEO name
	 */
	protected function _cleanSEOName( $seo_title )
	{
		$seo_title	= str_replace( "/", '', $seo_title );
		$seo_title	= str_replace( "\\", '', $seo_title );
		$seo_title	= str_replace( "$", '', $seo_title );
		$seo_title	= str_replace( "..", '', $seo_title );
		
		return $seo_title;
	}
}
