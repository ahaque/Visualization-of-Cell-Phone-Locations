<?php

/**
 * Invision Power Services
 * IP.CCS page templates
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

class admin_ccs_templates_pages extends ipsCommand
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
	 * HTML library
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
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_templates' );
		
		//-----------------------------------------
		// Set up stuff
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= 'module=templates&amp;section=pages';
		$this->form_code_js	= $this->html->form_code_js	= 'module=templates&section=pages';

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
			case 'add':
			case 'edit':
				$this->_form( $this->request['do'] );
			break;			

			case 'doAdd':
			case 'doEdit':
				$this->_save( strtolower( str_replace( 'do', '', $this->request['do'] ) ) );
			break;
			
			case 'delete':
				$this->_delete();
			break;
			
			case 'addCategory':
				$this->_categoryForm( 'add' );
			break;
			case 'editCategory':
				$this->_categoryForm( 'edit' );
			break;
			
			case 'doAddCategory':
				$this->_categorySave( 'add' );
			break;
			case 'doEditCategory':
				$this->_categorySave( 'edit' );
			break;
			
			case 'deleteCategory':
				$this->_deleteCategory();
			break;

			default:
				$this->_list();
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->getClass('output')->html_main .= $this->registry->getClass('output')->global_template->global_frame_wrapper();
		$this->registry->getClass('output')->sendOutput();
	}
	
	/**
	 * Delete a category
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function _deleteCategory()
	{
		$id	= intval($this->request['id']);
		
		$this->DB->update( 'ccs_page_templates', array( 'template_category' => 0 ), 'template_category=' . $id );
		$this->DB->delete( 'ccs_containers', 'container_id=' . $id );
		
		$this->registry->output->global_message = $this->lang->words['template_cat_deleted'];

		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . '&module=templates&section=pages' );
	}

	/**
	 * Save a category
	 *
	 * @access	protected
	 * @param	string		Type (add or edit)
	 * @return	void
	 */
	protected function _categorySave( $type='add' )
	{
		$category	= array();
		
		if( $type == 'edit' )
		{
			$id			= intval($this->request['id']);
			$category	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_containers', 'where' => "container_type='template' AND container_id=" . $id ) );
			
			if( !$category['container_id'] )
			{
				$this->registry->output->showError( $this->lang->words['category__cannotfind'] );
			}
		}
		
		$save	= array( 'container_name' => $this->request['category_title'] );
		
		if( $type == 'add' )
		{
			$save['container_type']		= 'template';
			$save['container_order']	= 100;
			
			$this->DB->insert( 'ccs_containers', $save );
		}
		else
		{
			$this->DB->update( 'ccs_containers', $save, "container_id=" . $category['container_id'] );
		}
		
		$this->registry->output->global_message = $this->lang->words['template_cat_save__good'];

		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . '&module=templates&section=pages' );
	}
	
	/**
	 * Form to add/edit a category
	 *
	 * @access	protected
	 * @param	string		Type (add or edit)
	 * @return	void
	 */
	protected function _categoryForm( $type='add' )
	{
		$category	= array();
		
		if( $type == 'edit' )
		{
			$id			= intval($this->request['id']);
			$category	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_containers', 'where' => "container_type='template' AND container_id=" . $id ) );
			
			if( !$category['container_id'] )
			{
				$this->registry->output->showError( $this->lang->words['category__cannotfind'] );
			}
		}
		
		$this->registry->output->html .= $this->html->categoryForm( $type, $category );
	}

	/**
	 * List the current page templates
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function _list()
	{
		//-----------------------------------------
		// Get current templates
		//-----------------------------------------
		
		$templates	= array();

		$this->DB->build( array(
								'select'	=> 't.*', 
								'from'		=> array( 'ccs_page_templates' => 't' ), 
								'order'		=> 't.template_position ASC',
								'group'		=> 't.template_id',
								'add_join'	=> array(
													array(
														'select'	=> 'COUNT(p.page_id) as pages',
														'from'		=> array( 'ccs_pages' => 'p' ),
														'where'		=> 'p.page_template_used=t.template_id',
														'type'		=> 'left',
														)
													)
						)		);
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$r['template_updated_formatted']	= $this->lang->getDate( $r['template_updated'], 'SHORT', 1 );
			
			$templates[ intval($r['template_category']) ][]	= $r;
		}
		
		//-----------------------------------------
		// Get template categories
		//-----------------------------------------
		
		$categories	= array();

		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_containers', 'where' => "container_type='template'", 'order' => 'container_order ASC' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$categories[]	= $r;
		}

		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->listTemplates( $templates, $categories );
	}
	
	/**
	 * Delete a template
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function _delete()
	{
		$id	= intval($this->request['template']);
		
		//-----------------------------------------
		// If template is used, warn user
		//-----------------------------------------
		
		if( !$this->request['confirm'] )
		{
			$template	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_page_templates', 'where' => 'template_id=' . $id ) );
			$check		= $this->DB->buildAndFetch( array( 'select' => "COUNT(*) as total", 'from' => 'ccs_pages', 'where' => 'page_template_used=' . $id ) );
			
			if( $check['total'] )
			{
				$this->registry->output->html .= $this->html->confirmDelete( $id, $template, $check['total'] );
				return;
			}
		}

		//-----------------------------------------
		// Deletes
		//-----------------------------------------
		
		$this->DB->delete( 'ccs_page_templates', 'template_id=' . $id );
		$this->DB->delete( 'ccs_template_cache', "cache_type='template' AND cache_type_id=" . $id );
		
		//-----------------------------------------
		// Recache the "skin" file
		//-----------------------------------------
		
		require_once( IPSLib::getAppDir( 'ccs' ) . '/sources/pages.php' );
		$_pagesClass	= new pageBuilder( $this->registry );
		$_pagesClass->recacheTemplateCache();
		
		//-----------------------------------------
		// Also clear any cache using this template..
		//-----------------------------------------
		
		$this->DB->update( 'ccs_pages', array( 'page_cache' => null ), 'page_template_used=' . $id );
		
		$this->registry->output->global_message = $this->lang->words['template_deleted'];

		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . $this->form_code );
	}

	/**
	 * Show the form to add or edit a page template
	 *
	 * @access	protected
	 * @param	string		[$type]		Type of form (add|edit)
	 * @return	void
	 */
	protected function _form( $type='add' )
	{
		$defaults	= array( 'template_content' => '{ccs special_tag="page_content"}' );
		$form		= array();
		
		if( $type == 'edit' )
		{
			$id	= intval($this->request['template']);
			
			if( !$id )
			{
				$this->registry->output->showError( $this->lang->words['ccs_no_template_id'] );
			}
			
			$defaults	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_page_templates', 'where' => 'template_id=' . $id ) );
		}
		
		$form['name']			= $this->registry->output->formInput( 'template_name', $defaults['template_name'] );
		$form['key']			= $this->registry->output->formInput( 'template_key', $defaults['template_key'] );
		$form['description']	= $this->registry->output->formTextarea( 'template_desc', $defaults['template_desc'], 75, 3 );
		$form['content']		= $this->registry->output->formTextarea( 'template_content', htmlspecialchars( $defaults['template_content'] ), 75, 30, 'template_content', "style='width:100%;'" );
		
		//-----------------------------------------
		// Category, if available
		//-----------------------------------------
		
		$form['category']		= '';
		$categories				= array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_containers', 'where' => "container_type='template'", 'order' => 'container_order ASC' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$categories[]	= array( $r['container_id'], $r['container_name'] );
		}
		
		if( count($categories) )
		{
			array_unshift( $categories, array( '0', $this->lang->words['no_selected_cat'] ) );
			
			$form['category']	= $this->registry->output->formDropdown( 'template_category', $categories, $defaults['template_category'] );
		}
		
		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->templateForm( $type, $defaults, $form );
	}
	
	/**
	 * Save the edits to a template
	 *
	 * @access	protected
	 * @param	string		[$type]		Saving of form (add|edit)
	 * @return	void
	 */
	protected function _save( $type='add' )
	{
		$id	= 0;

		if( $type == 'edit' )
		{
			$id	= intval($this->request['template']);
			
			if( !$id )
			{
				$this->registry->output->showError( $this->lang->words['ccs_no_template_id'] );
			}
		}
		
		$save	= array(
						'template_name'		=> trim($this->request['template_name']),
						'template_desc'		=> trim($this->request['template_desc']),
						'template_key'		=> trim($this->request['template_key']),
						'template_content'	=> str_replace( '&#46;&#46;/', '../', trim($_POST['template_content']) ),
						'template_updated'	=> time(),
						'template_category'	=> intval($this->request['template_category']),
						);

		//-----------------------------------------
		// Make sure key is unique
		//-----------------------------------------
		
		if( !$save['template_key'] )
		{
			$this->registry->output->showError( $this->lang->words['template_key_missing'] );
		}
		
		$check	= $this->DB->buildAndFetch( array( 'select' => 'template_id', 'from' => 'ccs_page_templates', 'where' => "template_key='{$save['template_key']}' AND template_id<>{$id}" ) );
		
		if( $check['template_id'] )
		{
			$this->registry->output->showError( $this->lang->words['template_key_used'] );
		}

		//-----------------------------------------
		// Save
		//-----------------------------------------
		
		if( $type == 'edit' )
		{
			$this->DB->update( 'ccs_page_templates', $save, 'template_id=' . $id );
			
			$this->DB->update( 'ccs_pages', array( 'page_cache' => null ), 'page_template_used=' . $id );
			
			$this->registry->output->global_message = $this->lang->words['template_edited'];
		}
		else
		{
			$this->DB->insert( 'ccs_page_templates', $save );
			
			$id	= $this->DB->getInsertId();
			
			$this->registry->output->global_message = $this->lang->words['template_added'];
			
			$this->request['template']	= $id;
		}
		
		//-----------------------------------------
		// Recache the template
		//-----------------------------------------
		
		$cache	= array(
						'cache_type'	=> 'template',
						'cache_type_id'	=> $id,
						);

		require_once( IPS_KERNEL_PATH . 'classTemplateEngine.php' );
		$engine					= new classTemplate( IPS_ROOT_PATH . 'sources/template_plugins' );
		$cache['cache_content']	= $engine->convertHtmlToPhp( $save['template_key'], '', $save['template_content'], '', false, true );
		
		$hasIt	= $this->DB->buildAndFetch( array( 'select' => 'cache_id', 'from' => 'ccs_template_cache', 'where' => "cache_type='template' AND cache_type_id={$id}" ) );
		
		if( $hasIt['cache_id'] )
		{
			$this->DB->update( 'ccs_template_cache', $cache, "cache_type='template' AND cache_type_id={$id}" );
		}
		else
		{
			$this->DB->insert( 'ccs_template_cache', $cache );
		}

		//-----------------------------------------
		// Recache the "skin" file
		//-----------------------------------------
		
		require_once( IPSLib::getAppDir( 'ccs' ) . '/sources/pages.php' );
		$_pagesClass	= new pageBuilder( $this->registry );
		$_pagesClass->recacheTemplateCache( $engine );
		
		//-----------------------------------------
		// Show form again?
		//-----------------------------------------
		
		if( $this->request['save_and_reload'] )
		{
			$this->_form( 'edit' );
			return;
		}
		
		//-----------------------------------------
		// Output
		//-----------------------------------------

		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . '&module=templates&section=pages' );
	}
}
