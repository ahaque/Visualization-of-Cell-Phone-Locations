<?php

/**
 * Invision Power Services
 * IP.CCS page caching and building
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

class pageBuilder
{
	/**
	 * Page template content
	 *
	 * @access	public
	 * @var		string
	 */
	public $pageTemplate		= '';
	
	/**#@+
	 * Registry objects
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $caches;
	protected $cache;
	/**#@-*/

	/**
	 * Cached templates...
	 *
	 * @access	public
	 * @var		array
	 */
	public $templates		= array();
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object	ipsRegistry
	 * @return	void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make object */
		$this->registry		= $registry;
		$this->DB			= $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->lang			= $this->registry->getClass('class_localization');
		$this->member		= $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		= $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
	}

	/**
	 * Builds a page "cache"
	 *
	 * @access	public
	 * @param	array		[$page]		Page data
	 * @return	void
	 */
	public function recachePage( $page=array() )
	{
		if( !count($page) )
		{
			return '';
		}

		//-----------------------------------------
		// Only need to parse if we actually have content
		//-----------------------------------------
		
		if( $page['page_content'] )
		{
			switch( $page['page_type'] )
			{
				case 'bbcode':
					IPSText::getTextClass( 'bbcode' )->bypass_badwords		= 1;
					IPSText::getTextClass( 'bbcode' )->parse_smilies		= 1;
					IPSText::getTextClass( 'bbcode' )->parse_html			= 1;
					IPSText::getTextClass( 'bbcode' )->parse_nl2br			= 1;
					IPSText::getTextClass( 'bbcode' )->parse_bbcode			= 1;
					IPSText::getTextClass( 'bbcode' )->parsing_section		= 'global';
					
					$content	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $page['page_content'] );
				break;
				
				case 'html':
					$content	= $page['page_content'];
				break;
				
				case 'php':
					ob_start();
					eval( $page['page_content'] );
					$content	= ob_get_contents();
					ob_end_clean();
				break;
			}
		}

		//-----------------------------------------
		// Is this inheriting from a template?
		//-----------------------------------------
		
		if( $page['page_content_only'] AND $page['page_template_used'] )
		{
			$this->loadSkinFile();
			$template	= $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'ccs_page_templates', 'where' => 'template_id=' . $page['page_template_used'] ) );
			
			if( !count($template) OR !$template['template_key'] )
			{
				return '';
			}
			
			$template_content	= $this->registry->output->getTemplate('ccs')->$template['template_key']();
			$content			= str_replace( '{ccs special_tag="page_content"}', $content, $template_content );
		}
		
		if( !$page['page_ipb_wrapper'] )
		{
			$_metaTags	= '';
			
			if( $page['page_meta_keywords'] )
			{
				$_metaTags .= "<meta name='keywords' content='{$page['page_meta_keywords']}' />\n";
			}

			if( $page['page_meta_description'] )
			{
				$_metaTags .= "<meta name='description' content='{$page['page_meta_description']}' />\n";
			}
			
			$content			= str_replace( '{ccs special_tag="meta_tags"}', $_metaTags, $content );
			$content			= str_replace( '{ccs special_tag="page_title"}', $page['page_name'], $content );
		}
		
		//-----------------------------------------
		// Parse out page blocks..
		//-----------------------------------------
		
		preg_match_all( "#\{parse block=\"(.+?)\"\}#", $content, $matches );
		
		if( count($matches) )
		{
			foreach( $matches[1] as $index => $key )
			{
				$content = str_replace( $matches[0][ $index ], $this->getBlock( $key ), $content );
			}
		}
		
		//-----------------------------------------
		// Return data
		//-----------------------------------------
		
		return $content;
	}
	
	/**
	 * Recache "skin" file
	 *
	 * @access	public
	 * @param	object		Template engine (if already loaded)
	 * @return	string		Skin file class
	 */
	public function recacheTemplateCache( $engine=null )
	{
		//-----------------------------------------
		// Make sure we got the engine
		//-----------------------------------------

		if( !$engine )
		{
			require_once( IPS_KERNEL_PATH . 'classTemplateEngine.php' );
			$engine					= new classTemplate( IPS_ROOT_PATH . 'sources/template_plugins' );
		}
		
		//-----------------------------------------
		// Recache the blocks
		//-----------------------------------------

		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_template_blocks' ) );
		$outer = $this->DB->execute();
		
		while( $r = $this->DB->fetch($outer) )
		{
			$cache	= array(
							'cache_type'	=> 'block',
							'cache_type_id'	=> $r['tpb_id'],
							);
	
			$cache['cache_content']	= $engine->convertHtmlToPhp( $r['tpb_name'], $r['tpb_params'], $r['tpb_content'], '', false, true );
			
			$hasIt	= $this->DB->buildAndFetch( array( 'select' => 'cache_id', 'from' => 'ccs_template_cache', 'where' => "cache_type='block' AND cache_type_id={$r['tpb_id']}" ) );
			
			if( $hasIt['cache_id'] )
			{
				$this->DB->update( 'ccs_template_cache', $cache, "cache_type='block' AND cache_type_id={$r['tpb_id']}" );
			}
			else
			{
				$this->DB->insert( 'ccs_template_cache', $cache );
			}
		}
		
		//-----------------------------------------
		// Get templates
		//-----------------------------------------
		
		$templateBits	= array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_template_cache', 'where' => "cache_type NOT IN('full','page')" ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$templateBits[]	= $r['cache_content'];
		}
		
		//-----------------------------------------
		// Now create the pseudo-code
		//-----------------------------------------
		
		$_fakeClass	= "<" . "?php\n\n";
		$_fakeClass	.= "class skin_ccs_1 {\n\n";

		$_fakeClass	.= "\n\n}";

		$fullFile	= $engine->convertCacheToEval( $_fakeClass, "skin_ccs", 1 );
		$fullFile	= str_replace( "\n\n}", implode( "\n\n", $templateBits ) . "\n\n}", $fullFile );
		
		$cache	= array(
						'cache_type'	=> 'full',
						'cache_type_id'	=> 0,
						'cache_content'	=> $fullFile,
						);
						
		$hasIt	= $this->DB->buildAndFetch( array( 'select' => 'cache_id', 'from' => 'ccs_template_cache', 'where' => "cache_type='full'" ) );
		
		if( $hasIt['cache_id'] )
		{
			$this->DB->update( 'ccs_template_cache', $cache, "cache_type='full'" );
		}
		else
		{
			$this->DB->insert( 'ccs_template_cache', $cache );
		}

		return $fullFile;
	}
	
	/**
	 * Load the skin file
	 *
	 * @access	public
	 * @return	void
	 */
	public function loadSkinFile()
	{
		if( !$this->registry->output->compiled_templates['skin_ccs'] )
		{
			$skinFile	= $this->DB->buildAndFetch( array( 'select' => 'cache_content', 'from' => 'ccs_template_cache', 'where' => "cache_type='full'" ) );
			
			if( !$skinFile['cache_content'] )
			{
				$skinFile['cache_content']	= $this->recacheTemplateCache();
			}
			//print nl2br(htmlspecialchars($skinFile['cache_content']));exit;
			//-----------------------------------------
			// And now we have a skin file..
			//-----------------------------------------
			
			eval( $skinFile['cache_content'] );
			
			$this->registry->output->compiled_templates['skin_ccs']	= new skin_ccs( $this->registry );
		}
	}

	/**
	 * Get the user's friends
	 *
	 * @access	public
	 * @return	array 		Friend ids
	 */
	public function getFriends()
	{
		$friends	= array();
		
		if( $this->memberData['member_id'] )
		{
			$this->DB->build( array( 'select' => 'friends_friend_id', 'from' => 'profile_friends', 'where' => 'friends_member_id=' . $this->memberData['member_id'] ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$friends[]	= $r['friends_friend_id'];
			}
		}
		
		return $friends;
	}
	
	/**
	 * Get and return block HTML
	 *
	 * @access	public
	 * @param	string		Block key
	 * @return	string		Block HTML
	 */
	public function getBlock( $blockKey )
	{
		static $parsedBlocks	= array();

		if( !$blockKey )
		{
			return '';
		}
		
		if( array_key_exists( $blockKey, $parsedBlocks ) )
		{
			return $parsedBlocks[ $blockKey ];
		}
		
		//-----------------------------------------
		// If we haven't already fetched blocks, do so now
		//-----------------------------------------
		
		if( !$this->caches['ccs_blocks'] )
		{
			$blocks	= array();
			
			$this->DB->build( array( 'select' => '*', 'from' => 'ccs_blocks', 'where' => 'block_active=1' ) );
			$this->DB->execute();
			
			while( $r = $this->DB->fetch() )
			{
				$blocks[ $r['block_key'] ] = $r;
			}
			
			$this->cache->updateCacheWithoutSaving( 'ccs_blocks', $blocks );
		}
		
		//-----------------------------------------
		// Get HTML
		//-----------------------------------------
		
		$_content	= '';

		if( $this->caches['ccs_blocks'][ $blockKey ] )
		{
			require_once( IPSLib::getAppDir('ccs') . '/sources/blocks/adminInterface.php' );
			require_once( IPSLib::getAppDir('ccs') . '/sources/blocks/' . $this->caches['ccs_blocks'][ $blockKey ]['block_type'] . '/admin.php' );
			$_class 	= "adminBlockHelper_" . $this->caches['ccs_blocks'][ $blockKey ]['block_type'];
			
			$_block		= new $_class( $this->registry );
			$_content	= $_block->getBlockContent( $this->caches['ccs_blocks'][ $blockKey ] );
			
			$parsedBlocks[ $blockKey ]	= $_content;
		}

		
		return $_content;
	}
}