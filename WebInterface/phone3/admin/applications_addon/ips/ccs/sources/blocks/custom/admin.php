<?php

/**
 * Invision Power Services
 * IP.CCS custom block type admin plugin
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

class adminBlockHelper_custom implements adminBlockHelperInterface
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
			
			$this->html = $this->registry->output->loadTemplate( 'cp_skin_blocks_custom' );
			
			//-----------------------------------------
			// Set up stuff
			//-----------------------------------------
			
			$this->form_code	= $this->html->form_code	= 'module=blocks&amp;section=wizard';
			$this->form_code_js	= $this->html->form_code_js	= 'module=blocks&section=wizard';
		}
	}

	/**
	 * Get configuration data.  Allows for automatic block types.
	 *
	 * @access	public
	 * @return	array 			Array( key, text )
	 */
	public function getBlockConfig() 
	{
		return array( 'custom', $this->registry->class_localization->words['block_type__custom'] );
	}
	
	/**
	 * Store data to initiate a wizard session based on given block table data
	 *
	 * @access	public
	 * @param	array 				Block data
	 * @return	array 				Data to store for wizard session
	 */
	public function createWizardSession( $block )
	{
		$config		= unserialize( $block['block_config'] );
		$session	= array(
							'wizard_id'		=> md5( uniqid( microtime(), true ) ),
							'wizard_step'	=> 1,
							'wizard_type'	=> 'custom',
							'wizard_name'	=> $this->lang->words['block_editing_title'] . $block['block_name'],
							'wizard_config'	=> serialize(
														array(
															'type'			=> $config['type'],
															'hide_empty'	=> $config['hide_empty'],
															'title'			=> $block['block_name'],
															'key'			=> $block['block_key'],
															'description'	=> $block['block_description'],
															'content'		=> $block['block_content'],
															'cache_ttl'		=> $block['block_cache_ttl'],
															'block_id'		=> $block['block_id'],
															'category'		=> $block['block_category'],
															)
														)
							);

		return $session;
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

		switch( $newStep )
		{
			//-----------------------------------------
			// Step 2: Allow to select block type
			//-----------------------------------------
			
			case 2:
				$session['config_data']['type']	= $session['config_data']['type'] ? $session['config_data']['type'] : 'html';
				
				$html	= $this->html->custom__wizard_2( $session );
			break;
			
			//-----------------------------------------
			// Step 3: Fill in name and description
			//-----------------------------------------
			
			case 3:
				$session['config_data']['hide_empty']	= $session['config_data']['hide_empty'] ? $session['config_data']['hide_empty'] : 0;
				$session['config_data']['title']		= $session['config_data']['title'] ? $session['config_data']['title'] : $session['wizard_name'];

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
				
				$html	= $this->html->custom__wizard_3( $session, $categories );
			break;

			//-----------------------------------------
			// Step 4: Configure the caching options
			//-----------------------------------------
			
			case 4:
				$html	= $this->html->custom__wizard_4( $session );
			break;
			
			//-----------------------------------------
			// Step 5: Edit the HTML template
			//-----------------------------------------
			
			case 5:
				if( $session['config_data']['type'] == 'basic' )
				{
					IPSText::getTextClass( 'bbcode' )->bypass_badwords		= 1;
					IPSText::getTextClass( 'bbcode' )->parse_smilies		= 1;
					IPSText::getTextClass( 'bbcode' )->parse_html			= 1;
					IPSText::getTextClass( 'bbcode' )->parse_nl2br			= 1;
					IPSText::getTextClass( 'bbcode' )->parse_bbcode			= 1;
					IPSText::getTextClass( 'bbcode' )->parsing_section		= 'global';

					if ( IPSText::getTextClass( 'editor' )->method == 'rte' )
					{
						$session['config_data']['content'] = IPSText::getTextClass( 'bbcode' )->convertForRTE( $session['config_data']['content'] );
					}
					else
					{
						$session['config_data']['content'] = IPSText::getTextClass( 'bbcode' )->preEditParse( $session['config_data']['content'] );
					}
					
					$editor_area	= IPSText::getTextClass( 'editor' )->showEditor( $session['config_data']['content'], 'custom_content' );
					 
				}
				else
				{
					$editor_area	= $this->registry->output->formTextarea( "custom_content", IPSText::br2nl( $session['config_data']['content'] ), 100, 30, "custom_content", "style='width:100%;'" );
				}
				
				$html	= $this->html->custom__wizard_5( $session, $editor_area );
			break;

			//-----------------------------------------
			// Step 6: Save to DB final, and show code to add to pages
			//-----------------------------------------
			
			case 6:
				$block	= array(
								'block_active'		=> 1,
								'block_name'		=> $session['config_data']['title'],
								'block_key'			=> $session['config_data']['key'],
								'block_description'	=> $session['config_data']['description'],
								'block_type'		=> 'custom',
								'block_content'		=> $session['config_data']['content'],
								'block_category'	=> $session['config_data']['category'],
								'block_cache_ttl'	=> $session['config_data']['cache_ttl'],
								'block_config'		=> serialize( array( 'type' => $session['config_data']['type'], 'hide_empty' => $session['config_data']['hide_empty'], ) )
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
				// Recache block
				//-----------------------------------------

				$block['block_cache_output']	= $this->recacheBlock( $block );
				$block['block_cache_last']		= time();
				
				//-----------------------------------------
				// Delete wizard session and show done screen
				//-----------------------------------------
				
				$this->DB->delete( 'ccs_block_wizard', "wizard_id='{$session['wizard_id']}'" );
				
				$html	= $this->html->custom__wizard_DONE( $block );
			break;
		}
		
		return $html;
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
	 * Parse block
	 *
	 * @access	protected
	 * @param	array 		Block data
	 * @return	string		Parsed block content
	 */
	protected function _parseBlock( $block )
	{
		$config		= unserialize( $block['block_config'] );
		$content	= '';
		
		switch( $config['type'] )
		{
			case 'basic':
				IPSText::getTextClass( 'bbcode' )->bypass_badwords		= 1;
				IPSText::getTextClass( 'bbcode' )->parse_smilies		= 1;
				IPSText::getTextClass( 'bbcode' )->parse_html			= 1;
				IPSText::getTextClass( 'bbcode' )->parse_nl2br			= 1;
				IPSText::getTextClass( 'bbcode' )->parse_bbcode			= 1;
				IPSText::getTextClass( 'bbcode' )->parsing_section		= 'global';
				
				$content	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $block['block_content'] );
			break;
			
			case 'html':
				$content	= $block['block_content'];
			break;
			
			case 'php':
				//-----------------------------------------
				// We need to push the raw PHP code into the
				// template system or it'll cache and be
				// one page load behind
				// @see http://forums./tracker/issue-17502-block-caching/
				//-----------------------------------------
				
				$content	= "<php>ob_start();\n" . $block['block_content'] . "\n\$IPBHTML .= ob_get_contents();\nob_end_clean();</php>";
				//ob_start();
				//eval( $block['block_content'] );
				//$content	= ob_get_contents();
				//ob_end_clean();
			break;
		}

		return $content;
	}

	/**
	 * Recache this block to the database based on content type and cache settings.
	 *
	 * @access	public
	 * @param	array 				Block data
	 * @param	bool				Return data instead of saving to database
	 * @return	string				New cached content
	 */
	public function recacheBlock( $block, $return=false )
	{
		//-----------------------------------------
		// Save the template
		//-----------------------------------------
		
		$templateHTML	= $this->_parseBlock( $block );

		$template		= $this->DB->buildAndFetch( array( 
													'select'	=> '*', 
													'from'		=> 'ccs_template_blocks', 
													'where'		=> "tpb_name='block__custom_{$block['block_id']}'" 
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
														'where'		=> "tpb_name='block__custom'" 
												)		);

			$this->DB->insert( 'ccs_template_blocks', 
								array( 
									'tpb_name'		=> 'block__custom_' . $block['block_id'],
									'tpb_content'	=> $templateHTML,
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
		$cache['cache_content']	= $engine->convertHtmlToPhp( 'block__custom_' . $block['block_id'], $template['tpb_params'], $templateHTML, '', false, true );
		
		$hasIt	= $this->DB->buildAndFetch( array( 'select' => 'cache_id', 'from' => 'ccs_template_cache', 'where' => "cache_type='block' AND cache_type_id={$template['tpb_id']}" ) );
		//print_r($cache);exit;
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
		$_pagesClass->loadSkinFile();

		$func 		= 'block__custom_' . $block['block_id'];
		$content	= $this->registry->output->getTemplate('ccs')->$func( $block['block_name'], $templateHTML );
		
		if( !$return AND $block['block_cache_ttl'] )
		{
			$this->DB->update( 'ccs_blocks', array( 'block_cache_output' => $content, 'block_cache_last' => time() ), 'block_id=' . intval($block['block_id']) );
		}
		
		return $content;
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
		switch( $config['type'] )
		{
			case 'basic':
				IPSText::getTextClass( 'bbcode' )->bypass_badwords		= 1;
				IPSText::getTextClass( 'bbcode' )->parse_smilies		= 1;
				IPSText::getTextClass( 'bbcode' )->parse_html			= 1;
				IPSText::getTextClass( 'bbcode' )->parse_nl2br			= 1;
				IPSText::getTextClass( 'bbcode' )->parse_bbcode			= 1;
				IPSText::getTextClass( 'bbcode' )->parsing_section		= 'global';
	
				if ( IPSText::getTextClass( 'editor' )->method == 'rte' )
				{
					$content = IPSText::getTextClass( 'bbcode' )->convertForRTE( $block['block_content'] );
				}
				else
				{
					$content = IPSText::getTextClass( 'bbcode' )->preEditParse( $block['block_content'] );
				}
				
				$editor_area	= IPSText::getTextClass( 'editor' )->showEditor( $content, 'content' );
			break;
			
			default:
				$editor_area	= $this->registry->output->formTextarea( "content", htmlspecialchars($block['block_content']), 100, 30, "content", "style='width:100%;'" );
			break;
		}
		
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
		if( $config['type'] == 'basic' )
		{
			IPSText::getTextClass( 'bbcode' )->bypass_badwords		= 1;
			IPSText::getTextClass( 'bbcode' )->parse_smilies		= 1;
			IPSText::getTextClass( 'bbcode' )->parse_html			= 1;
			IPSText::getTextClass( 'bbcode' )->parse_nl2br			= 1;
			IPSText::getTextClass( 'bbcode' )->parse_bbcode			= 1;
			IPSText::getTextClass( 'bbcode' )->parsing_section		= 'global';

			$content	= IPSText::getTextClass( 'editor' )->processRawPost( 'content' );
			$content	= IPSText::getTextClass( 'bbcode' )->preDbParse( $content );
		}
		else
		{
			$content	= $_POST['content'];
		}
		
		//-----------------------------------------
		// PHP page with <?php tag?
		//-----------------------------------------
		
		if( $config['type'] == 'php' )
		{
			if( strpos( $content, '<?php' ) !== false )
			{
				$this->registry->output->global_error = $this->lang->words['php_page_php_tag'];
				
				return false;
			}
		}
		
		$this->DB->update( 'ccs_blocks', array( 'block_content' => $content ), 'block_id=' . $block['block_id'] );
		
		$block['block_content']	= $content;
		$content				= $this->recacheBlock( $block );
		
		return true;
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
			case 2:
				$validTypes	= array( 'basic', 'html', 'php' );
				
				if( !in_array( $this->request['custom_type'], $validTypes ) )
				{
					$session['wizard_step']--;

					$this->registry->output->global_error	= $this->lang->words['block_invalid_custom_type'];
					
					return $session;
				}
				
				$session['config_data']['type']		= $this->request['custom_type'];
				
				$this->_saveToDb( $session['wizard_id'], $session['wizard_step'], $session['config_data'] );
			break;
			
			case 3:
				$session['config_data']['title']		= $this->request['custom_title'];
				$session['config_data']['category']		= intval($this->request['category']);
				$session['config_data']['key']			= preg_replace( "/[^a-zA-Z0-9_\-]/", "", $this->request['custom_key'] );
				$session['config_data']['description']	= $this->request['custom_description'];
				$session['config_data']['hide_empty']	= $this->request['hide_empty'];
				
				//-----------------------------------------
				// Make sure block key isn't taken
				//-----------------------------------------
				
				if( !$session['config_data']['key'] )
				{
					$session['wizard_step']--;
					
					$this->registry->output->global_error = $this->registry->class_localization->words['block_key_is_required'];
					
					return $session;
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
					
					$this->registry->output->global_error = $this->registry->class_localization->words['block_key_in_use'];
					
					return $session;
				}

				$this->_saveToDb( $session['wizard_id'], $session['wizard_step'], $session['config_data'] );
			break;
			
			case 4:
				$session['config_data']['cache_ttl']		= trim($this->request['custom_cache_ttl']);
				
				$this->_saveToDb( $session['wizard_id'], $session['wizard_step'], $session['config_data'] );
			break;

			case 5:
				if( $session['config_data']['type'] == 'basic' )
				{
					IPSText::getTextClass( 'bbcode' )->bypass_badwords		= 1;
					IPSText::getTextClass( 'bbcode' )->parse_smilies		= 1;
					IPSText::getTextClass( 'bbcode' )->parse_html			= 1;
					IPSText::getTextClass( 'bbcode' )->parse_nl2br			= 1;
					IPSText::getTextClass( 'bbcode' )->parse_bbcode			= 1;
					IPSText::getTextClass( 'bbcode' )->parsing_section		= 'global';

					$session['config_data']['content']	= IPSText::getTextClass( 'editor' )->processRawPost( 'custom_content' );
					$session['config_data']['content']	= IPSText::getTextClass( 'bbcode' )->preDbParse( $session['config_data']['content'] );
				}
				else
				{
					$session['config_data']['content']	= $_POST['custom_content'];
				}
				
				//-----------------------------------------
				// PHP page with <?php tag?
				//-----------------------------------------
				
				if( $session['config_data']['type'] == 'php' )
				{
					if( strpos( $session['config_data']['content'], '<?php' ) !== false )
					{
						$session['wizard_step']--;
						
						$this->registry->output->global_error = $this->lang->words['php_page_php_tag'];
						
						return $session;
					}
				}
				
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
	 */
	protected function _saveToDb( $sessionId, $currentStep, $configData )
	{
		$this->DB->update( 'ccs_block_wizard', array( 'wizard_config' => serialize($configData), 'wizard_step' => ($currentStep + 1) ), "wizard_id='{$sessionId}'" );
		return true;
	}
}
