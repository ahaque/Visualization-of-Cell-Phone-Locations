<?php

/**
 * Invision Power Services
 * IP.CCS plugins block type admin plugin
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

class adminBlockHelper_plugin implements adminBlockHelperInterface
{
	/**#@+
	 * Registry Object Shortcuts
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $DB;
	protected $settings;
	protected $lang;
	protected $member;
	protected $cache;
	protected $registry;
	protected $caches;
	protected $request;
	/**#@-*/
	
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
	 * Current session
	 *
	 * @access	public
	 * @var		array
	 */
	public $session;
	
	/**
	 * HTML object
	 *
	 * @access	public
	 * @var		object
	 */
	public $html;

	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	void
	 */
	public function __construct( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Make shortcuts
		//-----------------------------------------
		
		$this->registry		= $registry;
		$this->DB			= $registry->DB();
		$this->settings		= $registry->fetchSettings();
		$this->member		= $registry->member();
		$this->cache		= $registry->cache();
		$this->caches		=& $registry->cache()->fetchCaches();
		$this->request		= $registry->fetchRequest();
		$this->lang 		= $registry->class_localization;

		if( IN_ACP )
		{
			//-----------------------------------------
			// Load HTML
			//-----------------------------------------
			
			$this->html = $this->registry->output->loadTemplate( 'cp_skin_blocks_plugin' );
			
			//-----------------------------------------
			// Set up stuff
			//-----------------------------------------
			
			$this->form_code	= $this->html->form_code	= 'module=blocks&amp;section=wizard';
			$this->form_code_js	= $this->html->form_code_js	= 'module=blocks&section=wizard';
		}
		
		//-----------------------------------------
		// Get interface
		//-----------------------------------------
		
		require_once( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/plugin/pluginInterface.php' );
	}
	
	/**
	 * Get configuration data.  Allows for automatic block types.
	 *
	 * @access	public
	 * @return	array 			Array( key, text )
	 */
	public function getBlockConfig() 
	{
		return array( 'plugin', $this->registry->class_localization->words['block_type__plugin'] );
	}

	/**
	 * Wizard launcher.  Should determine the next step necessary and act appropriately.
	 *
	 * @access	public
	 * @param	array 				Session data
	 * @return	string				HTML to output to screen
	 * @todo 	Finish this
	 */
	public function returnNextStep( $session ) 
	{
		$session['config_data']	= unserialize( $session['wizard_config'] );
		$session['wizard_step']	= $this->request['step'] ? $this->request['step'] : 1;

		if( $session['wizard_step'] > 1 AND !$this->request['continuing'] )
		{
			$session	= $this->_storeSubmittedData( $session );
		}

		$newStep	= $session['wizard_step'] + 1;
		$html		= '';
		
		if( $newStep > 2 )
		{
			require_once( IPSLib::getAppDir('ccs') . '/sources/blocks/plugin/' . $session['config_data']['plugin_type'] . '/plugin.php' );
			$_className		= "plugin_" . $session['config_data']['plugin_type'];
			$_class 		= new $_className( $this->registry );
			$_pluginConfig	= $_class->returnPluginInfo();
		}

		switch( $newStep )
		{
			//-----------------------------------------
			// Step 2: Allow to select plugin type
			//-----------------------------------------
			
			case 2:
				$_blockTypes	= array();

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
							$_blockTypes[]	= $_class->returnPluginInfo();
						}
					}
				}

				$html	= $this->html->plugin__wizard_2( $session, $_blockTypes );
			break;
			
			//-----------------------------------------
			// Step 3: Fill in name and description
			//-----------------------------------------
			
			case 3:
				$session['config_data']['title']		= $session['config_data']['title'] ? $session['config_data']['title'] : $session['wizard_name']; //$this->lang->words["plugin_name__{$session['config_data']['plugin_type']}"];
				$session['config_data']['description']	= $session['config_data']['description'] ? $session['config_data']['description'] : $this->lang->words["plugin_description__{$session['config_data']['plugin_type']}"];
				$session['config_data']['hide_empty']	= $session['config_data']['hide_empty'] ? $session['config_data']['hide_empty'] : 0;

				//-----------------------------------------
				// Category, if available
				//-----------------------------------------

				$categories				= array();
				
				$this->DB->build( array( 'select' => '*', 'from' => 'ccs_containers', 'where' => "container_type='block'", 'order' => 'container_order ASC' ) );
				$this->DB->execute();
				
				while( $r = $this->DB->fetch() )
				{
					$categories[]	= array( $r['container_id'], $r['container_name'] );
				}
				
				if( count($categories) )
				{
					array_unshift( $categories, array( '0', $this->lang->words['no_selected_cat'] ) );
				}
				
				$html	= $this->html->plugin__wizard_3( $session, $categories );
			break;
			
			//-----------------------------------------
			// Step 4: Custom configuration options
			//-----------------------------------------
			
			case 4:
				$formData	= $_class->returnPluginConfig( $session );
				$html		= $this->html->plugin__wizard_4( $session, $formData );
			break;

			//-----------------------------------------
			// Step 5: Configure the caching options
			//-----------------------------------------
			
			case 5:
				$html	= $this->html->plugin__wizard_5( $session );
			break;
			
			//-----------------------------------------
			// Step 6: Edit the HTML template
			//-----------------------------------------
			
			case 6:
				$template		= array();
				
				if( $session['config_data']['block_id'] )
				{
					$template	= $this->DB->buildAndFetch( array( 
																'select'	=> '*', 
																'from'		=> 'ccs_template_blocks', 
																'where'		=> "tpb_name='{$_pluginConfig['templateBit']}_{$session['config_data']['block_id']}'" 
														)		);
				}

				if( !$template['tpb_content'] )
				{
					$template	= $this->DB->buildAndFetch( array( 
																'select'	=> '*', 
																'from'		=> 'ccs_template_blocks', 
																'where'		=> "tpb_name='{$_pluginConfig['templateBit']}'" 
														)		);
				}

				$editor_area	= $this->registry->output->formTextarea( "custom_template", $template['tpb_content'], 100, 30, "custom_template", "style='width:100%;'" );
				
				$html	= $this->html->plugin__wizard_6( $session, $editor_area );
			break;

			//-----------------------------------------
			// Step 7: Save to DB final, and show code to add to pages
			//-----------------------------------------
			
			case 7:
				$block	= array(
								'block_active'		=> 1,
								'block_name'		=> $session['config_data']['title'],
								'block_key'			=> $session['config_data']['key'],
								'block_description'	=> $session['config_data']['description'],
								'block_type'		=> 'plugin',
								'block_content'		=> '',
								'block_cache_ttl'	=> $session['config_data']['cache_ttl'],
								'block_category'	=> $session['config_data']['category'],
								'block_config'		=> serialize( 
																array( 
																	'plugin'		=> $session['config_data']['plugin_type'],
																	'custom'		=> $session['config_data']['custom_config'],
																	'hide_empty'	=> $session['config_data']['hide_empty'],
																	) 
																)
								);

				if( $session['config_data']['block_id'] )
				{
					$this->DB->update( 'ccs_blocks', $block, 'block_id=' . $session['config_data']['block_id'] );
					$block['block_id']	= $session['config_data']['block_id'];
					
					//-----------------------------------------
					// Clear page caches
					//-----------------------------------------
					
					$this->DB->update( 'ccs_pages', array( 'page_cache' => null ) );
				}
				else
				{
					$this->DB->insert( 'ccs_blocks', $block );
					$block['block_id']	= $this->DB->getInsertId();
				}

				//-----------------------------------------
				// Save the template
				//-----------------------------------------
				
				$templateHTML	= $_POST['custom_template'];

				$template		= $this->DB->buildAndFetch( array( 
															'select'	=> '*', 
															'from'		=> 'ccs_template_blocks', 
															'where'		=> "tpb_name='{$_pluginConfig['templateBit']}_{$block['block_id']}'" 
													)		);

				if( $template['tpb_id'] )
				{
					$this->DB->update( 'ccs_template_blocks', array( 'tpb_content' => $templateHTML ), 'tpb_id=' . $template['tpb_id'] );
				}
				else
				{
					$template	= $this->DB->buildAndFetch( array( 
																'select'	=> '*', 
																'from'		=> 'ccs_template_blocks', 
																'where'		=> "tpb_name='{$_pluginConfig['templateBit']}'" 
														)		);

					$this->DB->insert( 'ccs_template_blocks', 
										array( 
											'tpb_name'		=> "{$_pluginConfig['templateBit']}_{$block['block_id']}",
											'tpb_content'	=> $templateHTML ,
											'tpb_params'	=> $template['tpb_params'],
											)
									);
					$template['tpb_id']	= $this->DB->getInsertId();
				}

				$cache	= array(
								'cache_type'	=> 'block',
								'cache_type_id'	=> $template['tpb_id'],
								);
		
				require_once( IPS_KERNEL_PATH . 'classTemplateEngine.php' );
				$engine					= new classTemplate( IPS_ROOT_PATH . 'sources/template_plugins' );
				$cache['cache_content']	= $engine->convertHtmlToPhp( "{$_pluginConfig['templateBit']}_{$block['block_id']}", $template['tpb_params'], $templateHTML, '', false, true );
				
				$hasIt	= $this->DB->buildAndFetch( array( 'select' => 'cache_id', 'from' => 'ccs_template_cache', 'where' => "cache_type='block' AND cache_type_id={$template['tpb_id']}" ) );
				
				if( $hasIt['cache_id'] )
				{
					$this->DB->update( 'ccs_template_cache', $cache, "cache_type='block' AND cache_type_id={$template['tpb_id']}" );
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
				// Recache block
				//-----------------------------------------
				
				if( $block['block_cache_ttl'] )
				{
					$block['block_cache_output']	= $this->recacheBlock( $block );
					$block['block_cache_last']		= time();
				}
				
				//-----------------------------------------
				// Delete wizard session and show done screen
				//-----------------------------------------
				
				$this->DB->delete( 'ccs_block_wizard', "wizard_id='{$session['wizard_id']}'" );
				
				$html	= $this->html->plugin__wizard_DONE( $block );
			break;
		}
		
		return $html;
	}
	
	/**
	 * Get editor for block template
	 *
	 * @access	public
	 * @param	array 		Block data
	 * @param	array 		Block config
	 * @return	string		Editor HTML
	 */
	public function getTemplateEditor( $block, $config )
	{
		require_once( IPSLib::getAppDir('ccs') . '/sources/blocks/plugin/' . $config['plugin'] . '/plugin.php' );
		$_className		= "plugin_" . $config['plugin'];
		$_class 		= new $_className( $this->registry );
		$_pluginConfig	= $_class->returnPluginInfo();
			
		$template		= array();
		
		if( $block['block_id'] )
		{
			$template	= $this->DB->buildAndFetch( array( 
														'select'	=> '*', 
														'from'		=> 'ccs_template_blocks', 
														'where'		=> "tpb_name='{$_pluginConfig['templateBit']}_{$block['block_id']}'" 
												)		);
		}

		if( !$template['tpb_content'] )
		{
			$template	= $this->DB->buildAndFetch( array( 
														'select'	=> '*', 
														'from'		=> 'ccs_template_blocks', 
														'where'		=> "tpb_name='{$_pluginConfig['templateBit']}'" 
												)		);
		}

		$editor_area	= $this->registry->output->formTextarea( "content", $template['tpb_content'], 100, 30, "content", "style='width:100%;'" );
		
		return $editor_area;
	}
	
	/**
	 * Saves the edits to the template
	 *
	 * @access	public
	 * @param	array 		Block data
	 * @param	array 		Block config
	 * @return	bool		Saved
	 */
	public function saveTemplateEdits( $block, $config )
	{
		require_once( IPSLib::getAppDir('ccs') . '/sources/blocks/plugin/' . $config['plugin'] . '/plugin.php' );
		$_className		= "plugin_" . $config['plugin'];
		$_class 		= new $_className( $this->registry );
		$_pluginConfig	= $_class->returnPluginInfo();
		
		//-----------------------------------------
		// Save the template
		//-----------------------------------------
		
		$templateHTML	= $_POST['content'];

		$template		= $this->DB->buildAndFetch( array( 
													'select'	=> '*', 
													'from'		=> 'ccs_template_blocks', 
													'where'		=> "tpb_name='{$_pluginConfig['templateBit']}_{$block['block_id']}'" 
											)		);

		if( $template['tpb_id'] )
		{
			$this->DB->update( 'ccs_template_blocks', array( 'tpb_content' => $templateHTML ), 'tpb_id=' . $template['tpb_id'] );
		}
		else
		{
			$template	= $this->DB->buildAndFetch( array( 
														'select'	=> '*', 
														'from'		=> 'ccs_template_blocks', 
														'where'		=> "tpb_name='{$_pluginConfig['templateBit']}'" 
												)		);

			$this->DB->insert( 'ccs_template_blocks', 
								array( 
									'tpb_name'		=> "{$_pluginConfig['templateBit']}_{$block['block_id']}",
									'tpb_content'	=> $templateHTML ,
									'tpb_params'	=> $template['tpb_params'],
									)
							);
			$template['tpb_id']	= $this->DB->getInsertId();
		}

		$cache	= array(
						'cache_type'	=> 'block',
						'cache_type_id'	=> $template['tpb_id'],
						);

		require_once( IPS_KERNEL_PATH . 'classTemplateEngine.php' );
		$engine					= new classTemplate( IPS_ROOT_PATH . 'sources/template_plugins' );
		$cache['cache_content']	= $engine->convertHtmlToPhp( "{$_pluginConfig['templateBit']}_{$block['block_id']}", $template['tpb_params'], $templateHTML, '', false, true );
		
		$hasIt	= $this->DB->buildAndFetch( array( 'select' => 'cache_id', 'from' => 'ccs_template_cache', 'where' => "cache_type='block' AND cache_type_id={$template['tpb_id']}" ) );
		
		if( $hasIt['cache_id'] )
		{
			$this->DB->update( 'ccs_template_cache', $cache, "cache_type='block' AND cache_type_id={$template['tpb_id']}" );
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
		// Recache block
		//-----------------------------------------
		
		if( $block['block_cache_ttl'] )
		{
			$block['block_cache_output']	= $this->recacheBlock( $block );
			$block['block_cache_last']		= time();
		}
		
		return true;
	}

	/**
	 * Return the block content to display.  Checks cache and updates cache if needed.
	 *
	 * @access	public
	 * @param	array 	Block data
	 * @return	string 	Content to output
	 */
	public function getBlockContent( $block )
	{
		$config	= unserialize($block['block_config']);
		
		if( $block['block_cache_ttl'] )
		{
			if( $block['block_cache_ttl'] == '*' AND $block['block_cache_output'] )
			{
				return $block['block_cache_output'];
			}
			
			$expired	= time() - ( $block['block_cache_ttl'] * 60 );
			
			if( $block['block_cache_last'] > $expired )
			{
				if( $block['block_cache_output'] )
				{
					return $block['block_cache_output'];
				}
			}
		}
		
		return $this->recacheBlock( $block );
	}

	/**
	 * Recache this block to the database based on content type and cache settings.
	 *
	 * @access	public
	 * @param	array 				Block data
	 * @param	bool				Return data instead of saving to database
	 * @return	bool				Cache done successfully
	 * @todo 	Finish this
	 */
	public function recacheBlock( $block, $return=false )
	{
		//-----------------------------------------
		// Load skin in case it's needed
		//-----------------------------------------
		
		require_once( IPSLib::getAppDir('ccs') . '/sources/pages.php' );
		$pageBuilder	= new pageBuilder( $this->registry );
		$pageBuilder->loadSkinFile();
		
		$config		= unserialize( $block['block_config'] );
		$content	= '';

		require_once( IPSLib::getAppDir('ccs') . '/sources/blocks/plugin/' . $config['plugin'] . '/plugin.php' );
		$_className		= "plugin_" . $config['plugin'];
		$_class 		= new $_className( $this->registry );
		$content		= $_class->executePlugin( $block );

		if( !$return )
		{
			$this->DB->update( 'ccs_blocks', array( 'block_cache_output' => $content, 'block_cache_last' => time() ), 'block_id=' . intval($block['block_id']) );
		}
		
		return $content;
	}
	
	/**
	 * Store data to initiate a wizard session based on given block table data
	 *
	 * @access	public
	 * @param	array 				Block data
	 * @return	array 				Data to store for wizard session
	 * @todo 	Finish this
	 */
	public function createWizardSession( $block )
	{
		$config		= unserialize( $block['block_config'] );
		$session	= array(
							'wizard_id'		=> md5( uniqid( microtime(), true ) ),
							'wizard_step'	=> 1,
							'wizard_type'	=> 'plugin',
							'wizard_name'	=> $this->lang->words['block_editing_title'] . $block['block_name'],
							'wizard_config'	=> serialize(
														array(
															'plugin_type'	=> $config['plugin'],
															'custom_config'	=> $config['custom'],
															'hide_empty'	=> $config['hide_empty'],
															'title'			=> $block['block_name'],
															'key'			=> $block['block_key'],
															'description'	=> $block['block_description'],
															'cache_ttl'		=> $block['block_cache_ttl'],
															'block_id'		=> $block['block_id'],
															'category'		=> $block['block_category'],
															)
														)
							);

		return $session;
	}
	
	/**
	 * Store the data submitted for the last step
	 *
	 * @access	protected
	 * @param	array 		Session data
	 * @return	array 		Session data (updated)
	 * @todo 	Finish this
	 */
	protected function _storeSubmittedData( $session )
	{
		switch( $session['wizard_step'] )
		{
			case 2:
				$session['config_data']['plugin_type']	= $this->request['plugin_type'];
				
				$this->_saveToDb( $session['wizard_id'], $session['wizard_step'], $session['config_data'] );
			break;
			
			case 3:
				$session['config_data']['title']		= $this->request['plugin_title'];
				$session['config_data']['category']		= intval($this->request['category']);
				$session['config_data']['key']			= preg_replace( "/[^a-zA-Z0-9_\-]/", "", $this->request['plugin_key'] );
				$session['config_data']['description']	= $this->request['plugin_description'];
				$session['config_data']['hide_empty']	= $this->request['hide_empty'];

				//-----------------------------------------
				// Make sure block key isn't taken
				//-----------------------------------------
				
				if( !$session['config_data']['key'] )
				{
					$session['wizard_step']--;
					
					$this->registry->output->global_message = $this->registry->class_localization->words['block_key_is_required'];
				}
				
				$where	= "block_key='{$session['config_data']['key']}'";
				
				if( $session['config_data']['block_id'] )
				{
					$where .= " AND block_id<>" . $session['config_data']['block_id'];
				}
				
				$check	= $this->DB->buildAndFetch( array( 'select' => 'block_id', 'from' => 'ccs_blocks', 'where' => $where ) );
				
				if( $check['block_id'] )
				{
					$session['wizard_step']--;
					
					$this->registry->output->global_message = $this->registry->class_localization->words['block_key_in_use'];
				}
				
				$this->_saveToDb( $session['wizard_id'], $session['wizard_step'], $session['config_data'] );
			break;
			
			case 4:
				require_once( IPSLib::getAppDir('ccs') . '/sources/blocks/plugin/' . $session['config_data']['plugin_type'] . '/plugin.php' );
				$_className		= "plugin_" . $session['config_data']['plugin_type'];
				$_class 		= new $_className( $this->registry );
				$validateResult	= $_class->validatePluginConfig( $this->request );
				
				if( !$validateResult[0] )
				{
					$this->registry->output->showError( $this->lang->words['plugin_not_validated'] );
				}

				$session['config_data']['custom_config']	= $validateResult[1];
				
				$this->_saveToDb( $session['wizard_id'], $session['wizard_step'], $session['config_data'] );
			break;

			case 5:
				$session['config_data']['cache_ttl']		= trim($this->request['custom_cache_ttl']);
				
				$this->_saveToDb( $session['wizard_id'], $session['wizard_step'], $session['config_data'] );
			break;
		}
		
		return $session;
	}
	
	/**
	 * Run DB update query
	 *
	 * @access	protected
	 * @param	string		Session ID
	 * @param	integer		Current step
	 * @param	array 		Config data
	 * @return	bool
	 * @todo 	Finish this
	 */
	protected function _saveToDb( $sessionId, $currentStep, $configData )
	{
		$this->DB->update( 'ccs_block_wizard', array( 'wizard_config' => serialize($configData), 'wizard_step' => ($currentStep + 1) ), "wizard_id='{$sessionId}'" );
		return true;
	}
}
