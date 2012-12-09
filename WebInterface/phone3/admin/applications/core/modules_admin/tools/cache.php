<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * ACP : Cache management
 * Last Updated: $Date: 2009-08-10 17:08:53 -0400 (Mon, 10 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Core
 * @link		http://www.
 * @since		26th January 2004
 * @version		$Revision: 5009 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class admin_core_tools_cache extends ipsCommand
{
	/**
	 * HTML skin object
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $html;
	
	/**
	 * URL for form code
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $form_code;
	
	/**
	 * URL for form code (javascript)
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $form_code_js;
	
	/**
	 * Class entry point
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Load language
		//-----------------------------------------
		
		$this->registry->class_localization->loadLanguageFile( array( 'admin_tools' ) );
		
		//-----------------------------------------
		// Load skin
		//-----------------------------------------
		
		$this->html	= $this->registry->output->loadTemplate('cp_skin_tools');
		
		//-----------------------------------------
		// Set some vars
		//-----------------------------------------
		
		$this->form_code	= $this->html->form_code	= '&amp;module=tools&amp;section=cache&amp;';
		$this->form_code_js	= $this->html->form_code_js	= '&module=tools&section=cache&';
		
		$this->registry->getClass('class_permissions')->checkPermissionAutoMsg( 'cache_manage' );
		
		//-----------------------------------------
		// And then?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			default:
			case 'cache_overview':
				$this->request['do'] = 'cache_overview';
				$this->cacheOverview();
			break;
			
			case 'cache_view':
				$this->cacheView();
			break;
			
			case 'cache_recache':
				$this->cacheRecache();
			break;
			
			case 'cache_update_all_process':
				$this->cacheUpdateAllProcess();
			break;
			
			case 'contentCache':
				$this->_contentCache();
			break;
		}
		
		//-----------------------------------------
		// Pass to CP output hander
		//-----------------------------------------
		
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}
	
	/**
	 * View an individual cache store contents
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
	private function _contentCache()
	{
		$type   = $this->request['type'];
		$method = $this->request['method'];
		
		/* What are we doing? */
		if ( $method == 'all' )
		{
			$removed = IPSContentCache::truncate( $type );
		}
		else
		{
			$removed = IPSContentCache::prune( $type );
		}
		
		$this->registry->output->global_message = sprintf( $this->lang->words['cc_processed'], $removed );
		$this->cacheOverview();
	}
	
	/**
	 * View an individual cache store contents
	 *
	 * @access	public
	 * @return	void		[Outputs to screen]
	 */
	public function cacheView()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$id = trim( $this->request['id'] );
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( ! $id )
		{
			$this->registry->output->global_message = $this->lang->words['c_noid'];
			$this->cacheOverview();
			return false;
		}
		
		//-----------------------------------------
		// Get cache...
		//-----------------------------------------

		if( $this->request['cache_app'] AND !in_array( $this->request['cache_app'], array( 'core', 'global' ) ) )
		{
			ipsRegistry::_loadAppCoreVariables( $this->request['cache_app'] );
		}

		$db_cache = $this->cache->getCache( $id );
		
		//-----------------------------------------
		// Build HTML and output...
		//-----------------------------------------
		
		ob_start();
		
		print_r( $db_cache );

		$out = ob_get_contents();
		
		ob_end_clean();
		
		$this->registry->output->html = $this->html->cache_pop_up( $id, htmlspecialchars($out) );
		
		$this->registry->output->printPopupWindow();
	}
	
	/**
	 * Rebuild all caches
	 *
	 * @access	public
	 * @return	void		[Outputs to screen]
	 */
	public function cacheUpdateAllProcess()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$id			= intval( $this->request['id'] );
		$cache_name	= '';
		$cache_data	= '';
		$count		= 0;
		$img		= '<img src="' . $this->settings['skin_acp_url'] . '/images/loading_anim.gif" alt="-" /> ';
		$_caches	= array();
		
		//-----------------------------------------
		// Get core cache list
		//-----------------------------------------
				
		$_caches = array_merge( $_caches, $this->registry->_fetchCoreVariables( 'cache' ) );

		//-----------------------------------------
		// Get all application's cache lists
		//-----------------------------------------
		
		foreach( ipsRegistry::$applications as $app_dir => $app_data )
		{
			$_file = IPSLib::getAppDir( $app_dir ) . '/extensions/coreVariables.php';
		
			if ( file_exists( $_file ) )
			{
				$CACHE = array();
				require( $_file );
				
				foreach( $CACHE as $k => $v )
				{
					$CACHE[ $k ]['cache_app']	= $app_dir;
				}
			
				$_caches = array_merge( $_caches, $CACHE );
			}
		}

		//-----------------------------------------
		// Get cache data
		//-----------------------------------------

		foreach( $_caches as $_cache_name => $_cache_data )
		{
			if ( $count == $id )
			{
				$cache_name = $_cache_name;
				$cache_data = $_cache_data;
				break;
			}
			
			$count++;
		}

		//-----------------------------------------
		// Do what, now?
		//-----------------------------------------
		
		$id++;
		
		if ( $cache_name )
		{
			$this->cache->rebuildCache( $cache_name, $cache_data['cache_app'] ? $cache_data['cache_app'] : 'global' );

			$this->registry->output->multipleRedirectHit( $this->settings['base_url'] . '&' . $this->form_code_js . '&do=cache_update_all_process&id=' . $id, $img . $_cache_name . $this->lang->words['c_processed'] );
		}
		else
		{
			$this->registry->output->multipleRedirectFinish();
		}
	}

	/**
	 * Recache an individual cache
	 *
	 * @access	public
	 * @return	void		[Outputs to screen]
	 */
	public function cacheRecache()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$id	 = trim( $this->request['id'] );
		$app = IPSText::alphanumericalClean( $this->request['cacheapp'] );
		
		if ( $id == '__all__' )
		{
			$this->registry->output->multipleRedirectInit( $this->settings['base_url'] . '&' . $this->form_code_js . '&do=cache_update_all_process&id=0' );
			return false;
		}
		
		$this->cache->rebuildCache( $id, $app );
		
		$this->registry->output->global_message = $this->lang->words['c_recachethecachegetitgotitgood'];
		$this->cacheOverview();
	}
	
	/**
	 * List all of the current caches
	 *
	 * @access	public
	 * @return	void		[Outputs to screen]
	 */
	public function cacheOverview()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$content	  = "";
		$db_caches    = array();
		$lib_caches	  = array();
		$cacheContent = array();
		$total		  = 0;
		
		//-----------------------------------------
		// Get stored caches
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'cache_store' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$db_caches[ $r['cs_key'] ] = $r;
		}
		
		//-----------------------------------------
		// Get core cache list
		//-----------------------------------------
		
		foreach( $this->registry->_fetchCoreVariables( 'cache' ) as $cache_name => $cache_data )
		{
			$cache_data['cache_name']		= $cache_name;
			$cache_data['_cache_size']		= IPSLib::sizeFormat( IPSLib::strlenToBytes( strlen( $db_caches[ $cache_name ]['cs_value'] ) ) );
			$cache_data['_cs_init_load']	= $db_caches[ $cache_name ]['cs_init_load'];
			
			$total += IPSLib::strlenToBytes( strlen( $db_caches[ $cache_name ]['cs_value'] ) );
			
			$lib_caches['global'][ $cache_name ] = $cache_data;
		}
		
		//-----------------------------------------
		// Get all application's cache lists
		//-----------------------------------------
		
		foreach( ipsRegistry::$applications as $app_dir => $app_data )
		{
			$_file = IPSLib::getAppDir(  $app_dir ) . '/extensions/coreVariables.php';
		
			if ( file_exists( $_file ) )
			{
				$CACHE = array();
				require( $_file );
			
				foreach( $CACHE as $cache_name => $cache_data )
				{
					$cache_data['cache_name']		= $cache_name;
					$cache_data['_cache_size']		= IPSLib::sizeFormat( IPSLib::strlenToBytes( strlen( $db_caches[ $cache_name ]['cs_value'] ) ) );
					$cache_data['_cs_init_load']	= $db_caches[ $cache_name ]['cs_init_load'];

					$total += IPSLib::strlenToBytes( strlen( $db_caches[ $cache_name ]['cs_value'] ) );
					
					$lib_caches[ $app_dir ][ $cache_name ] = $cache_data;
				}
			}
		}
		
		foreach ( $lib_caches as $app => $data )
		{
			ksort( $lib_caches[ $app ] );
		}
	
		$total = IPSLib::sizeFormat( $total );
		
		/* Content Cache Stuffs */
		if ( IPSContentCache::isEnabled() )
		{
			/* Monitor? */
			if ( $this->settings['cc_monitor'] )
			{
				$cache = $this->cache->getCache('ccMonitor');
			
				$cacheContent['m_posts'] = sprintf( $this->lang->words['served_data'], intval( $cache['post']['raw'] ), intval( $cache['post']['cached'] ) );
				$cacheContent['m_sigs']  = sprintf( $this->lang->words['served_data'], intval( $cache['sig']['raw'] ), intval( $cache['sig']['cached'] ) );
			}
			else
			{
				$cacheContent['m_posts'] = $cacheContent['m_sigs'] = $this->lang->words['no_monitoring_data'];
			}
			
			/* Get all posts */
			$cacheContent['posts'] = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count',
													  				  'from'   => 'posts' ) );
													
			/* Get all members */
			$cacheContent['members'] = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count',
																		'from'   => 'members' ) );
														
			/* Get cached post count */
			$cacheContent['cachedPosts'] = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count',
																			'from'   => 'content_cache_posts' ) );
			
			/* Get cached sig count */
			$cacheContent['cachedSigs']  = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count',
																			'from'   => 'content_cache_sigs' ) );
																			
			/* Work out percentages */
			$cacheContent['postPercent'] = ( $cacheContent['posts']['count'] AND $cacheContent['cachedPosts']['count'] )
			 							?  sprintf( "%.0f", ( $cacheContent['cachedPosts']['count'] / $cacheContent['posts']['count'] ) * 100 ) : 0;
			
			$cacheContent['sigPercent']  = ( $cacheContent['members']['count'] AND $cacheContent['cachedSigs']['count'] )
			 							?  sprintf( "%.0f", ( $cacheContent['cachedSigs']['count'] / $cacheContent['members']['count'] ) * 100 ) : 0;
			
			
		}
		
		$this->registry->output->html .= $this->html->cache_entry_wrapper( $lib_caches, $total, $cacheContent );
	}
	
	/**
	 * Rebuild the RSS cache for output
	 *
	 * @access	public
	 * @param	string	[Optional app to rebuild]
	 * @return	array
	 */
	public function rebuildRssCache( $app='' )
	{
		$retrieved	= array( time() + 60*60 );
		
		foreach( ipsRegistry::$applications as $app_dir => $app_data )
		{
			if ( ( ! empty( $app ) AND $app == $app_dir ) || empty( $app ) )
			{
				//-----------------------------------------
				// Retrieve the RSS links for the header
				//-----------------------------------------
		
				if( file_exists( IPSLib::getAppDir( $app_dir ) . '/extensions/rssOutput.php' ) )
				{
					require_once( IPSLib::getAppDir( $app_dir ) . '/extensions/rssOutput.php' );
					$className = "rss_output_{$app_dir}";

					if( class_exists( $className ) )
					{
						$rss = new $className( $this->registry );
					
						if( method_exists( $rss, "getRssLinks" ) )
						{
							$rssData = $rss->getRssLinks();
						
							if( count($rssData) )
							{
								foreach( $rssData as $data )
								{
									if( !$data['url'] )
									{
										continue;
									}
								
									$retrieved[] = $data['title'] . ':|:' . $data['url'];
								}
							}
						}
					}
				}
			}
		}

		$this->cache->setCache( 'rss_output_cache', $retrieved, array( 'array' => 1, 'deletefirst' => 0 ) );
		
		return $retrieved;
	}

}