<?php

/**
 * Invision Power Services
 * IP.CCS feeds block type admin plugin
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

class adminBlockHelper_feed implements adminBlockHelperInterface
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
			
			$this->html = $this->registry->output->loadTemplate( 'cp_skin_blocks_feed' );
			
			//-----------------------------------------
			// Set up stuff
			//-----------------------------------------
			
			$this->form_code	= $this->html->form_code	= 'module=blocks&amp;section=wizard';
			$this->form_code_js	= $this->html->form_code_js	= 'module=blocks&section=wizard';
		}
		
		//-----------------------------------------
		// Get interface
		//-----------------------------------------
		
		require_once( IPSLib::getAppDir( 'ccs' ) . '/sources/blocks/feed/feedInterface.php' );
	}
	
	/**
	 * Get configuration data.  Allows for automatic block types.
	 *
	 * @access	public
	 * @return	array 			Array( key, text )
	 */
	public function getBlockConfig() 
	{
		return array( 'feed', $this->registry->class_localization->words['block_type__feed'] );
	}

	/**
	 * Wizard launcher.  Should determine the next step necessary and act appropriately.
	 *
	 * @access	public
	 * @param	array 				Session data
	 * @return	string				HTML to output to screen
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
			require_once( IPSLib::getAppDir('ccs') . '/sources/blocks/feed/data_sources/' . $session['config_data']['feed_type'] . '.php' );
			$_className		= "feed_" . $session['config_data']['feed_type'];
			$_class 		= new $_className( $this->registry );
			$_feedConfig	= $_class->returnFeedInfo();
		}

		switch( $newStep )
		{
			//-----------------------------------------
			// Step 2: Select feed type
			//-----------------------------------------
			
			case 2:
				$_feedTypes	= array();

				foreach( new DirectoryIterator( IPSLib::getAppDir('ccs') . '/sources/blocks/feed/data_sources' ) as $object )
				{
					if( !$object->isDir() AND !$object->isDot() )
					{
						$_key			= str_replace( IPSLib::getAppDir('ccs') . '/sources/blocks/feed/data_sources/', '', str_replace( '\\', '/', $object->getPathname() ) );
						$_key			= str_replace( '.php', '', $_key );

						require_once( $object->getPathname() );
						$_className		= "feed_" . $_key;
						$_class 		= new $_className( $this->registry );
						$_feedInfo		= $_class->returnFeedInfo();
						
						if( $_feedInfo['key'] )
						{
							$_feedTypes[]	= $_feedInfo;
						}
					}
				}

				$html	= $this->html->feed__wizard_2( $session, $_feedTypes );
			break;
			
			//-----------------------------------------
			// Step 3: Fill in name and description
			//-----------------------------------------
			
			case 3:
				$session['config_data']['title']		= $session['config_data']['title'] ? $session['config_data']['title'] : $session['wizard_name']; //$this->lang->words["feed_name__{$session['config_data']['feed_type']}"];
				$session['config_data']['description']	= $session['config_data']['description'] ? $session['config_data']['description'] : $this->lang->words["feed_description__{$session['config_data']['feed_type']}"];
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

				$html	= $this->html->feed__wizard_3( $session, $categories );
			break;
			
			//-----------------------------------------
			// Step 4: Content types available from the feed
			//-----------------------------------------
			
			case 4:
				$formData	= $_class->returnContentTypes( $session );
				$html		= $this->html->feed__wizard_4( $session, $formData );
			break;

			//-----------------------------------------
			// Step 5: Filter types available
			//-----------------------------------------
			
			case 5:
				$formData	= $_class->returnFilters( $session );
				$html		= $this->html->feed__wizard_5( $session, $formData );
			break;
			
			//-----------------------------------------
			// Step 6: Ordering data
			//-----------------------------------------
			
			case 6:
				$formData	= $_class->returnOrdering( $session );
				$html		= $this->html->feed__wizard_6( $session, $formData );
			break;
			
			//-----------------------------------------
			// Step 7: Caching options
			//-----------------------------------------
			
			case 7:
				$html		= $this->html->feed__wizard_7( $session );
			break;
			
			//-----------------------------------------
			// Step 8: Edit the HTML template
			//-----------------------------------------
			
			case 8:
				$template		= array();
				
				if( $session['config_data']['block_id'] )
				{
					$template	= $this->DB->buildAndFetch( array( 
																'select'	=> '*', 
																'from'		=> 'ccs_template_blocks', 
																'where'		=> "tpb_name='{$_feedConfig['templateBit']}_{$session['config_data']['block_id']}'" 
														)		);
				}

				if( !$template['tpb_content'] )
				{
					$template	= $this->DB->buildAndFetch( array( 
																'select'	=> '*', 
																'from'		=> 'ccs_template_blocks', 
																'where'		=> "tpb_name='{$_feedConfig['templateBit']}_{$session['config_data']['content_type']}'" 
														)		);

					if( !$template['tpb_content'] )
					{
						$template	= $this->DB->buildAndFetch( array( 
																	'select'	=> '*', 
																	'from'		=> 'ccs_template_blocks', 
																	'where'		=> "tpb_name='{$_feedConfig['templateBit']}'" 
															)		);
					}
				}

				$editor_area	= $this->registry->output->formTextarea( "custom_template", $template['tpb_content'], 100, 30, "custom_template", "style='width:100%;'" );
				
				$html	= $this->html->feed__wizard_8( $session, $editor_area );
			break;

			//-----------------------------------------
			// Step 9: Save to DB final, and show code to add to pages
			//-----------------------------------------
			
			case 9:
				$block	= array(
								'block_active'		=> 1,
								'block_name'		=> $session['config_data']['title'],
								'block_key'			=> $session['config_data']['key'],
								'block_description'	=> $session['config_data']['description'],
								'block_type'		=> 'feed',
								'block_content'		=> '',
								'block_cache_ttl'	=> $session['config_data']['cache_ttl'],
								'block_category'	=> $session['config_data']['category'],
								'block_config'		=> serialize( 
																array( 
																	'feed'			=> $session['config_data']['feed_type'],
																	'content'		=> $session['config_data']['content_type'],
																	'filters'		=> $session['config_data']['filters'],
																	'sortby'		=> $session['config_data']['sortby'],
																	'sortorder'		=> $session['config_data']['sortorder'],
																	'offset_a'		=> $session['config_data']['offset_start'],
																	'offset_b'		=> $session['config_data']['offset_end'],
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
															'where'		=> "tpb_name='{$_feedConfig['templateBit']}_{$session['config_data']['block_id']}'" 
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
																'where'		=> "tpb_name='{$_feedConfig['templateBit']}_{$session['config_data']['content_type']}'" 
														)		);

					if( !$template['tpb_id'] )
					{
						$template	= $this->DB->buildAndFetch( array( 
																	'select'	=> '*', 
																	'from'		=> 'ccs_template_blocks', 
																	'where'		=> "tpb_name='{$_feedConfig['templateBit']}'" 
															)		);
					}

					$this->DB->insert( 'ccs_template_blocks', 
										array( 
											'tpb_name'		=> "{$_feedConfig['templateBit']}_{$block['block_id']}",
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
				$cache['cache_content']	= $engine->convertHtmlToPhp( "{$_feedConfig['templateBit']}_{$block['block_id']}", $template['tpb_params'], $templateHTML, '', false, true );
				
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
				
				$html	= $this->html->feed__wizard_DONE( $block );
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
		require_once( IPSLib::getAppDir('ccs') . '/sources/blocks/feed/data_sources/' . $config['feed'] . '.php' );
		$_className		= "feed_" . $config['feed'];
		$_class 		= new $_className( $this->registry );
		$_feedConfig	= $_class->returnFeedInfo();
			
		$template		= array();
		
		if( $block['block_id'] )
		{
			$template	= $this->DB->buildAndFetch( array( 
														'select'	=> '*', 
														'from'		=> 'ccs_template_blocks', 
														'where'		=> "tpb_name='{$_feedConfig['templateBit']}_{$block['block_id']}'" 
												)		);
		}

		if( !$template['tpb_content'] )
		{
			$template	= $this->DB->buildAndFetch( array( 
														'select'	=> '*', 
														'from'		=> 'ccs_template_blocks', 
														'where'		=> "tpb_name='{$_feedConfig['templateBit']}_{$config['content']}'" 
												)		);

			if( !$template['tpb_content'] )
			{
				$template	= $this->DB->buildAndFetch( array( 
															'select'	=> '*', 
															'from'		=> 'ccs_template_blocks', 
															'where'		=> "tpb_name='{$_feedConfig['templateBit']}'" 
													)		);
			}
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
		require_once( IPSLib::getAppDir('ccs') . '/sources/blocks/feed/data_sources/' . $config['feed'] . '.php' );
		$_className		= "feed_" . $config['feed'];
		$_class 		= new $_className( $this->registry );
		$_feedConfig	= $_class->returnFeedInfo();
		
		//-----------------------------------------
		// Save the template
		//-----------------------------------------
		
		$templateHTML	= $_POST['content'];

		$template		= $this->DB->buildAndFetch( array( 
													'select'	=> '*', 
													'from'		=> 'ccs_template_blocks', 
													'where'		=> "tpb_name='{$_feedConfig['templateBit']}_{$block['block_id']}'" 
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
														'where'		=> "tpb_name='{$_feedConfig['templateBit']}_{$config['content']}'" 
												)		);

			if( !$template['tpb_id'] )
			{
				$template	= $this->DB->buildAndFetch( array( 
															'select'	=> '*', 
															'from'		=> 'ccs_template_blocks', 
															'where'		=> "tpb_name='{$_feedConfig['templateBit']}'" 
													)		);
			}

			$this->DB->insert( 'ccs_template_blocks', 
								array( 
									'tpb_name'		=> "{$_feedConfig['templateBit']}_{$block['block_id']}",
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
		$cache['cache_content']	= $engine->convertHtmlToPhp( "{$_feedConfig['templateBit']}_{$block['block_id']}", $template['tpb_params'], $templateHTML, '', false, true );
		
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

		require_once( IPSLib::getAppDir('ccs') . '/sources/blocks/feed/data_sources/' . $config['feed'] . '.php' );
		$_className		= "feed_" . $config['feed'];
		$_class 		= new $_className( $this->registry );
		$content		= $_class->executeFeed( $block );

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
							'wizard_type'	=> 'feed',
							'wizard_name'	=> $this->lang->words['block_editing_title'] . $block['block_name'],
							'wizard_config'	=> serialize(
														array(
															'feed_type'		=> $config['feed'],
															'content_type'	=> $config['content'],
															'filters'		=> $config['filters'],
															'sortby'		=> $config['sortby'],
															'sortorder'		=> $config['sortorder'],
															'offset_start'	=> $config['offset_a'],
															'offset_end'	=> $config['offset_b'],
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
				$session['config_data']['feed_type']	= $this->request['feed_type'];
				
				$this->_saveToDb( $session['wizard_id'], $session['wizard_step'], $session['config_data'] );
			break;
			
			case 3:
				$session['config_data']['title']		= $this->request['feed_title'];
				$session['config_data']['category']		= intval($this->request['category']);
				$session['config_data']['key']			= preg_replace( "/[^a-zA-Z0-9_\-]/", "", $this->request['feed_key'] );
				$session['config_data']['description']	= $this->request['feed_description'];
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
				require_once( IPSLib::getAppDir('ccs') . '/sources/blocks/feed/data_sources/' . $session['config_data']['feed_type'] . '.php' );
				$_className		= "feed_" . $session['config_data']['feed_type'];
				$_class 		= new $_className( $this->registry );
				$validateResult	= $_class->checkFeedContentTypes( $this->request );
				
				if( !$validateResult[0] )
				{
					$this->registry->output->showError( $this->lang->words['feed_not_validated'] );
				}

				$session['config_data']['content_type']	= $validateResult[1];
				
				$this->_saveToDb( $session['wizard_id'], $session['wizard_step'], $session['config_data'] );
			break;
			
			case 5:
				require_once( IPSLib::getAppDir('ccs') . '/sources/blocks/feed/data_sources/' . $session['config_data']['feed_type'] . '.php' );
				$_className		= "feed_" . $session['config_data']['feed_type'];
				$_class 		= new $_className( $this->registry );
				$validateResult	= $_class->checkFeedFilters( $this->request );
				
				if( !$validateResult[0] )
				{
					$this->registry->output->showError( $this->lang->words['feed_not_validated'] );
				}

				$session['config_data']['filters']		= $validateResult[1];
				
				$this->_saveToDb( $session['wizard_id'], $session['wizard_step'], $session['config_data'] );
			break;
			
			case 6:
				require_once( IPSLib::getAppDir('ccs') . '/sources/blocks/feed/data_sources/' . $session['config_data']['feed_type'] . '.php' );
				$_className		= "feed_" . $session['config_data']['feed_type'];
				$_class 		= new $_className( $this->registry );
				$validateResult	= $_class->checkFeedOrdering( $this->request );
				
				if( !$validateResult[0] )
				{
					$this->registry->output->showError( $this->lang->words['feed_not_validated'] );
				}

				$session['config_data']['sortby']		= $validateResult[1]['sortby'];
				$session['config_data']['sortorder']	= $validateResult[1]['sortorder'];
				$session['config_data']['offset_start']	= $validateResult[1]['offset_start'];
				$session['config_data']['offset_end']	= $validateResult[1]['offset_end'];
				
				$this->_saveToDb( $session['wizard_id'], $session['wizard_step'], $session['config_data'] );
			break;

			case 7:
				$session['config_data']['cache_ttl']		= trim($this->request['feed_cache_ttl']);
				
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
