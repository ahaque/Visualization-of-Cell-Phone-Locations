<?php

/**
 * Invision Power Services
 * IP.CCS blocks
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

class admin_ccs_blocks_blocks extends ipsCommand
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
		
		$this->form_code	= $this->html->form_code	= 'module=blocks&amp;section=blocks';
		$this->form_code_js	= $this->html->form_code_js	= 'module=blocks&section=blocks';

		//-----------------------------------------
		// Load Language
		//-----------------------------------------
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_lang' ) );

		//-----------------------------------------
		// Grab extra CSS
		//-----------------------------------------
		
		$this->registry->output->addToDocumentHead( 'importcss', $this->settings['skin_app_url'] . 'css/ccs.css' );
		
		//-----------------------------------------
		// Setting removed from ACP
		//-----------------------------------------
		
		$this->settings['ccs_prune_blocks']	= 6;
		
		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'delete':
				$this->_deleteBlock();
			break;
			
			case 'recache':
				$this->_recacheBlock();
			break;

			case 'recacheAll':
				$this->_recacheAllBlocks();
			break;
			
			case 'export':
				$this->_exportBlock();
			break;
			
			case 'exportBlock':
				$this->_exportSingleBlock();
			break;
			
			case 'import':
				$this->_importBlock();
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
		
		$this->DB->update( 'ccs_blocks', array( 'block_category' => 0 ), 'block_category=' . $id );
		$this->DB->delete( 'ccs_containers', 'container_id=' . $id );
		
		$this->registry->output->global_message = $this->lang->words['block_cat_deleted'];

		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . '&module=blocks&section=blocks' );
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
			$category	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_containers', 'where' => "container_type='block' AND container_id=" . $id ) );
			
			if( !$category['container_id'] )
			{
				$this->registry->output->showError( $this->lang->words['category__cannotfind'] );
			}
		}
		
		$save	= array( 'container_name' => $this->request['category_title'] );
		
		if( $type == 'add' )
		{
			$save['container_type']		= 'block';
			$save['container_order']	= 100;
			
			$this->DB->insert( 'ccs_containers', $save );
		}
		else
		{
			$this->DB->update( 'ccs_containers', $save, "container_id=" . $category['container_id'] );
		}
		
		$this->registry->output->global_message = $this->lang->words['block_cat_save__good'];

		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . '&module=blocks&section=blocks' );
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
			$category	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_containers', 'where' => "container_type='block' AND container_id=" . $id ) );
			
			if( !$category['container_id'] )
			{
				$this->registry->output->showError( $this->lang->words['category__cannotfind'] );
			}
		}
		
		$this->registry->output->html .= $this->html->categoryForm( $type, $category );
	}

	/**
	 * Import skin templates for a plugin block
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function _importBlock()
	{
		//-----------------------------------------
		// Developer reimporting templates?
		//-----------------------------------------
		
		if( $this->request['dev'] )
		{
			$templates	= array();
			
			$this->DB->build( array( 'select' => '*', 'from' => 'ccs_template_blocks' ) );
			$outer	= $this->DB->execute();
			
			while( $r = $this->DB->fetch($outer) )
			{
				if( !preg_match( "/_(\d+)$/", $r['tpb_name'] ) )
				{
					$templates[ $r['tpb_name'] ]	= $r;
				}
			}
			
			$content	= file_get_contents( IPSLib::getAppDir('ccs') . '/xml/block_templates.xml' );
			
			require_once( IPS_KERNEL_PATH.'classXML.php' );
	
			$xml = new classXML( IPS_DOC_CHAR_SET );
			$xml->loadXML( $content );
			
			foreach( $xml->fetchElements('template') as $template )
			{
				$_template	= $xml->fetchElementsFromRecord( $template );

				if( $_template['tpb_name'] )
				{
					unset($_template['tpb_id']);
					
					if( array_key_exists( $_template['tpb_name'], $templates ) )
					{
						$this->DB->update( "ccs_template_blocks", $_template, "tpb_id={$templates[ $_template['tpb_name'] ]['tpb_id']}" );
					}
					else
					{
						$this->DB->insert( "ccs_template_blocks", $_template );
					}
				}
			}
			
			$this->registry->output->global_message = $this->lang->words['block_import_devgood'];

			$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . '&module=blocks&section=blocks' );
		}
		
		$content = $this->registry->getClass('adminFunctions')->importXml();

		//-----------------------------------------
		// Get xml class
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH.'classXML.php' );

		$xml = new classXML( IPS_DOC_CHAR_SET );
		$xml->loadXML( $content );

		//-----------------------------------------
		// First, found out if this is just a plugin
		//-----------------------------------------
		
		$_fullBlock	= false;
		$_block		= array();
		$_blockId	= 0;
		
		foreach( $xml->fetchElements('block') as $block )
		{
			$_block	= $xml->fetchElementsFromRecord( $block );
		}
		
		if( count($_block) )
		{
			$_fullBlock	= true;
		}

		//-----------------------------------------
		// If full block, insert block first to get id
		//-----------------------------------------
		
		if( $_fullBlock )
		{
			unset($_block['block_id']);
			unset($_block['block_cache_last']);
			unset($_block['block_position']);
			unset($_block['block_category']);
			
			$check	= $this->DB->buildAndFetch( array( 'select' => 'block_id', 'from' => 'ccs_blocks', 'where' => "block_key='{$_block['block_key']}'" ) );
			
			//-----------------------------------------
			// Instead of updating, just change key to prevent
			// overwriting someone's configured block
			//-----------------------------------------
			
			if( $check['block_id'] )
			{
				$_block['block_key']	= $_block['block_key'] . md5( uniqid( microtime() ) );
			}
			
			$this->DB->insert( 'ccs_blocks', $_block );
			$_blockId	= $this->DB->getInsertId();
		}

		//-----------------------------------------
		// Do the template regardless
		//-----------------------------------------
		
		$tpbId	= 0;

		foreach( $xml->fetchElements('template') as $template )
		{
			$entry  = $xml->fetchElementsFromRecord( $template );

			if( !$entry['tpb_name'] )
			{
				continue;
			}
			
			$templatebit	= array(
									'tpb_name'		=> $entry['tpb_name'],
									'tpb_params'	=> $entry['tpb_params'],
									'tpb_content'	=> $entry['tpb_content'],
									);

			//-----------------------------------------
			// Fix name if full block
			//-----------------------------------------
			
			if( $_fullBlock )
			{
				$templatebit['tpb_name']	= preg_replace( "/^(.+?)_(\d+)$/", "\\1_{$_blockId}", $templatebit['tpb_name'] );
			}
			
			$check	= $this->DB->buildAndFetch( array( 'select' => 'tpb_id', 'from' => 'ccs_template_blocks', 'where' => "tpb_name='{$entry['tpb_name']}'" ) );
			
			if( $check['tpb_id'] )
			{
				$this->DB->update( 'ccs_template_blocks', $templatebit, 'tpb_id=' . $check['tpb_id'] );
				$tpbId	= $check['tpb_id'];
			}
			else
			{
				$this->DB->insert( 'ccs_template_blocks', $templatebit );
				$tpbId	= $this->DB->getInsertId();
			}
		}
		
		//-----------------------------------------
		// Recache skin if full block
		//-----------------------------------------
		
		if( $_fullBlock AND $tpbId )
		{
			$cache	= array(
							'cache_type'	=> 'block',
							'cache_type_id'	=> $tpbId,
							);
	
			require_once( IPS_KERNEL_PATH . 'classTemplateEngine.php' );
			$engine					= new classTemplate( IPS_ROOT_PATH . 'sources/template_plugins' );
			$cache['cache_content']	= $engine->convertHtmlToPhp( "{$templatebit['tpb_name']}", $templatebit['tpb_params'], $templatebit['tpb_content'], '', false, true );
			
			$hasIt	= $this->DB->buildAndFetch( array( 'select' => 'cache_id', 'from' => 'ccs_template_cache', 'where' => "cache_type='block' AND cache_type_id={$tpbId}" ) );
			
			if( $hasIt['cache_id'] )
			{
				$this->DB->update( 'ccs_template_cache', $cache, "cache_type='block' AND cache_type_id={$tpbId}" );
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
		}
		
		if( $_fullBlock )
		{
			$this->registry->output->global_message = $this->lang->words['block_full_import_good'];
		}
		else
		{
			$this->registry->output->global_message = $this->lang->words['block_import_good'];
		}

		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . '&module=blocks&section=blocks' );
	}
	
	/**
	 * Export a single block record
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function _exportSingleBlock()
	{
		$id		= intval($this->request['block']);
		
		if( !$id )
		{
			$this->registry->output->showError( $this->lang->words['noblock_export'] );
		}
		
		$block	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_blocks', 'where' => 'block_id=' . $id ) );
		$config	= unserialize($block['block_config']);
		
		if( !$block['block_id'] )
		{
			$this->registry->output->showError( $this->lang->words['noblock_export'] );
		}

		$template	= array();
		
		switch( $block['block_type'] )
		{
			case 'custom':
				$templateName	= "block__custom_{$block['block_id']}";
				$template		= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_template_blocks', 'where' => "tpb_name='{$templateName}'" ) );
			break;
			
			case 'feed':
				require_once( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/feed/feedInterface.php' );
				require_once( IPSLib::getAppDir('ccs') . '/sources/blocks/feed/data_sources/' . $config['feed'] . '.php' );
				$_className		= "feed_" . $config['feed'];
				$_class 		= new $_className( $this->registry );
				$_feedConfig	= $_class->returnFeedInfo();
				
				$template		= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_template_blocks',  'where' => "tpb_name='{$_feedConfig['templateBit']}_{$block['block_id']}'" ) );
			break;
			
			case 'plugin':
				require_once( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/plugin/pluginInterface.php' );
				require_once( IPSLib::getAppDir('ccs') . '/sources/blocks/plugin/' . $config['plugin'] . '/plugin.php' );
				$_className		= "plugin_" . $config['plugin'];
				$_class 		= new $_className( $this->registry );
				$_pluginConfig	= $_class->returnPluginInfo();
				
				$template		= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_template_blocks',  'where' => "tpb_name='{$_pluginConfig['templateBit']}_{$block['block_id']}'" ) );
			break;
		}

		if( !$template['tpb_id'] )
		{
			$this->registry->output->showError( $this->lang->words['block_export_notemplate'] );
		}
		
		require_once( IPS_KERNEL_PATH . 'classXML.php' );
		$xml = new classXML( IPS_DOC_CHAR_SET );
		$xml->newXMLDocument();
		$xml->addElement( 'blockexport' );
		$xml->addElement( 'blockdata', 'blockexport' );
		
		$xml->addElementAsRecord( 'blockdata', 'block', $block );
		
		$xml->addElement( 'blocktemplate', 'blockexport' );
		
		$xml->addElementAsRecord( 'blocktemplate', 'template', $template );
		
		$this->registry->output->showDownload( $xml->fetchDocument(), 'block_' . $block['block_key'] . '.xml', '', 0 );
	}
		
	/**
	 * Export skin templates from a plugin block
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function _exportBlock()
	{
		$id		= preg_replace( "#[^a-zA-Z0-9_\-]#", "", $this->request['block'] );
		
		if( !$id )
		{
			$this->registry->output->showError( $this->lang->words['noblock_export'] );
		}
		
		//-----------------------------------------
		// Build default block templates for release
		//-----------------------------------------
		
		if( $id == '_all_' )
		{
			require_once( IPS_KERNEL_PATH . 'classXml.php' );
			$xml = new classXML( IPS_DOC_CHAR_SET );
			$xml->newXMLDocument();
			$xml->addElement( 'blockexport' );
			$xml->addElement( 'blocktemplate', 'blockexport' );
			
			$this->DB->build( array( 'select' => '*', 'from' => 'ccs_template_blocks' ) );
			$outer	= $this->DB->execute();
			
			while( $r = $this->DB->fetch($outer) )
			{
				if( !preg_match( "/_(\d+)$/", $r['tpb_name'] ) )
				{
					$xml->addElementAsRecord( 'blocktemplate', 'template', $r );
				}
			}

			$this->registry->output->showDownload( $xml->fetchDocument(), 'block_templates.xml', '', 0 );
			
			exit;
		}
		
		//-----------------------------------------
		// Allow exporting of single block templates
		//-----------------------------------------
		
		require_once( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/plugin/pluginInterface.php' );
		require_once( IPSLib::getAppDir('ccs') . '/sources/blocks/plugin/' . $id . '/plugin.php' );
		$_className		= "plugin_" . $id;
		$_class 		= new $_className( $this->registry );
		$_pluginConfig	= $_class->returnPluginInfo();
		
		if( !$_pluginConfig['templateBit'] )
		{
			$this->registry->output->showError( $this->lang->words['nothingto_export'] );
		}
		
		$template	= $this->DB->buildAndFetch( array( 
													'select'	=> '*', 
													'from'		=> 'ccs_template_blocks', 
													'where'		=> "tpb_name='{$_pluginConfig['templateBit']}'" 
											)		);

		if( !$template['tpb_id'] )
		{
			$this->registry->output->showError( $this->lang->words['couldnot_find_export'] );
		}
		
		require_once( IPS_KERNEL_PATH . 'classXml.php' );
		$xml = new classXML( IPS_DOC_CHAR_SET );
		$xml->newXMLDocument();
		$xml->addElement( 'blockexport' );
		$xml->addElement( 'blocktemplate', 'blockexport' );
		
		$xml->addElementAsRecord( 'blocktemplate', 'template', $template );
		
		$this->registry->output->showDownload( $xml->fetchDocument(), 'block_' . $_pluginConfig['key'] . '.xml', '', 0 );
	}
	
	/**
	 * List the current blocks
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function _list()
	{
		//-----------------------------------------
		// Get current blocks
		//-----------------------------------------
		
		$blocks	= array();

		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_blocks', 'order' => 'block_position ASC' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$blocks[ intval($r['block_category']) ][]	= $r;
		}
		
		//-----------------------------------------
		// Get unfinished block
		//-----------------------------------------
		
		$unfinished	= array();
		$toRemove	= array();

		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_block_wizard', 'order' => 'wizard_name ASC' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			//-----------------------------------------
			// If step 0 and older than 30 seconds
			//-----------------------------------------
			
			if( $r['wizard_step'] == 0 AND $r['wizard_started'] < (time() - 30) )
			{
				$toRemove[]	= $r['wizard_id'];
				continue;
			}
			else if( $this->settings['ccs_prune_blocks'] AND $r['wizard_started'] < ( time() - ( $this->settings['ccs_prune_blocks'] * 60 * 60 ) ) )
			{
				$toRemove[]	= $r['wizard_id'];
				continue;
			}

			$unfinished[]	= $r;
		}
		
		//-----------------------------------------
		// Remove any stale blocks
		//-----------------------------------------
		
		if( count($toRemove) )
		{
			$this->DB->delete( 'ccs_block_wizard', "wizard_id IN('" . implode( "','", $toRemove ) . "')" );
		}
		
		//-----------------------------------------
		// Get block categories
		//-----------------------------------------
		
		$categories	= array();

		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_containers', 'where' => "container_type='block'", 'order' => 'container_order ASC' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$categories[]	= $r;
		}
		
		//-----------------------------------------
		// Get plugin blocks we can "export"
		//-----------------------------------------
		
		require_once( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/plugin/pluginInterface.php' );
		
		$exportable	= array();

		foreach( new DirectoryIterator( IPSLib::getAppDir('ccs') . '/sources/blocks/plugin' ) as $object )
		{
			if( $object->isDir() AND !$object->isDot() )
			{
				if( file_exists( $object->getPathname() . '/plugin.php' ) )
				{
					$_folder	= str_replace( IPSLib::getAppDir('ccs') . '/sources/blocks/plugin/', '', str_replace( '\\', '/', $object->getPathname() ) );

					require_once( $object->getPathname() . '/plugin.php' );
					$_className		= "plugin_" . $_folder;
					$_class 		= new $_className( $this->registry );
					$exportable[]	= $_class->returnPluginInfo();
				}
			}
		}

		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->registry->output->html .= $this->html->listBlocks( $blocks, $unfinished, $categories, $exportable );
	}
	
	/**
	 * Delete a block
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function _deleteBlock()
	{
		if( $this->request['type'] == 'wizard' )
		{
			$id	= IPSText::md5Clean( $this->request['block'] );
			
			$this->DB->delete( 'ccs_block_wizard', "wizard_id='{$id}'" );
			
			$this->registry->output->global_message = $this->lang->words['wsession_deleted'];
		}
		else
		{
			$id		= intval($this->request['block']);
			$block	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_blocks', 'where' => 'block_id=' . $id ) );
			$config	= unserialize( $block['block_config'] );
			
			$this->DB->delete( 'ccs_blocks', 'block_id=' . $id );
			
			$template	= array();
			
			switch( $block['block_type'] )
			{
				case 'custom':
					$templateName	= "block__custom_{$block['block_id']}";
					$template		= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_template_blocks', 'where' => "tpb_name='{$templateName}'" ) );
				break;
				
				case 'feed':
					require_once( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/feed/feedInterface.php' );
					require_once( IPSLib::getAppDir('ccs') . '/sources/blocks/feed/data_sources/' . $config['feed'] . '.php' );
					$_className		= "feed_" . $config['feed'];
					$_class 		= new $_className( $this->registry );
					$_feedConfig	= $_class->returnFeedInfo();
					
					$template		= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_template_blocks',  'where' => "tpb_name='{$_feedConfig['templateBit']}_{$block['block_id']}'" ) );
				break;
				
				case 'plugin':
					require_once( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/plugin/pluginInterface.php' );
					require_once( IPSLib::getAppDir('ccs') . '/sources/blocks/plugin/' . $config['plugin'] . '/plugin.php' );
					$_className		= "plugin_" . $config['plugin'];
					$_class 		= new $_className( $this->registry );
					$_pluginConfig	= $_class->returnPluginInfo();
					
					$template		= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_template_blocks',  'where' => "tpb_name='{$_pluginConfig['templateBit']}_{$block['block_id']}'" ) );
				break;
			}
	
			if( $template['tpb_id'] )
			{
				$this->DB->delete( 'ccs_template_blocks', 'tpb_id=' . $template['tpb_id'] );
				
				$this->DB->delete( 'ccs_template_cache', "cache_type='block' AND cache_type_id=" . $template['tpb_id'] );
			}
			
			//-----------------------------------------
			// Clear page caches
			//-----------------------------------------

			$this->DB->update( 'ccs_pages', array( 'page_cache' => null ) );
					
			$this->registry->output->global_message = $this->lang->words['block_deleted'];
			
			//-----------------------------------------
			// Recache the "skin" file
			//-----------------------------------------
			
			require_once( IPSLib::getAppDir( 'ccs' ) . '/sources/pages.php' );
			$_pagesClass	= new pageBuilder( $this->registry );
			$_pagesClass->recacheTemplateCache();
		}

		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . '&module=blocks&section=blocks' );
	}
	
	/**
	 * Recache a block
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function _recacheBlock()
	{
		//-----------------------------------------
		// Get skin
		//-----------------------------------------
		
		require_once( IPSLib::getAppDir('ccs') . '/sources/pages.php' );
		$pageBuilder	= new pageBuilder( $this->registry );
		$pageBuilder->recacheTemplateCache();
		$pageBuilder->loadSkinFile();
		
		$id		= intval($this->request['block']);
		$block	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_blocks', 'where' => 'block_id=' . $id ) );
			
		if( $block['block_id'] )
		{
			if( $block['block_type'] AND file_exists( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/' . $block['block_type'] . '/admin.php' ) )
			{
				require_once( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/adminInterface.php' );
				require_once( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/' . $block['block_type'] . '/admin.php' );
				
				$className	= "adminBlockHelper_" . $block['block_type'];
				$extender	= new $className( $this->registry );
				$extender->recacheBlock( $block );
			}
		}
		
		//-----------------------------------------
		// Clear page caches
		//-----------------------------------------

		$this->DB->update( 'ccs_pages', array( 'page_cache' => null ) );
		
		$this->registry->output->global_message = $this->lang->words['block_recached'];
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . '&module=blocks&section=blocks' );
	}
	
	/**
	 * Recache a block
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function _recacheAllBlocks()
	{
		//-----------------------------------------
		// Get skin
		//-----------------------------------------
		
		require_once( IPSLib::getAppDir('ccs') . '/sources/pages.php' );
		$pageBuilder	= new pageBuilder( $this->registry );
		$pageBuilder->recacheTemplateCache();
		$pageBuilder->loadSkinFile();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_blocks' ) );
		$outer = $this->DB->execute();
		
		while( $block = $this->DB->fetch($outer) )
		{
			if( $block['block_type'] AND file_exists( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/' . $block['block_type'] . '/admin.php' ) )
			{
				require_once( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/adminInterface.php' );
				require_once( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/' . $block['block_type'] . '/admin.php' );
				
				$className	= "adminBlockHelper_" . $block['block_type'];
				$extender	= new $className( $this->registry );
				$extender->recacheBlock( $block );
			}
		}
		
		//-----------------------------------------
		// Clear page caches
		//-----------------------------------------

		$this->DB->update( 'ccs_pages', array( 'page_cache' => null ) );
		
		$this->registry->output->global_message = $this->lang->words['all_blocks_recached'];
		
		$this->registry->output->silentRedirectWithMessage( $this->settings['base_url'] . '&module=blocks&section=blocks' );
	}
}
