<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Core variables file: defines caches, resets, etc.
 * Last Updated: $Date: 2009-07-28 20:26:37 -0400 (Tue, 28 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @version		$Rev: 4948 $
 * @since		3.0.0
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class coreVariables
{
	/**
	 * Fetch the caches array
	 *
	 * @access	public
	 * @return	array 		Caches
	 */
	public function fetchCaches()
	{
		//-----------------------------------------
		// Extension File: Registered Caches
		//-----------------------------------------

		$CACHE['systemvars']      = array( 'array'            => 1,
									 	   'allow_unload'     => 0,
										   'default_load'     => 1,
										   'recache_file'     => '',
										   'recache_function' => '' );
								   
		$CACHE['login_methods']      = array( 'array'            => 1,
		    							      'allow_unload'     => 0,
			    						      'default_load'     => 1,
				    					      'recache_file'     => IPSLib::getAppDir( 'core' ) . '/modules_admin/tools/login.php',
					    				      'recache_class'    => 'admin_core_tools_login',
						    			      'recache_function' => 'loginsRecache' );

		/* Apps and modules */
		$CACHE['vnums']          = array(  'array'            => 1,
									       'allow_unload'     => 0,
									       'default_load'     => 1,
									       'recache_file'     => IPSLib::getAppDir( 'core' ) . '/modules_admin/applications/applications.php',
										   'recache_class'    => 'admin_core_applications_applications',
									       'recache_function' => 'versionNumbersRecache' );
									
		$CACHE['app_cache']      = array( 'array'            => 1,
									       'allow_unload'     => 0,
									       'default_load'     => 1,
									       'recache_file'     => IPSLib::getAppDir( 'core' ) . '/modules_admin/applications/applications.php',
										   'recache_class'    => 'admin_core_applications_applications',
									       'recache_function' => 'applicationsRecache' );

		$CACHE['module_cache']    = array( 'array'            => 1,
									       'allow_unload'     => 0,
									       'default_load'     => 1,
									       'recache_file'     => IPSLib::getAppDir( 'core' ) . '/modules_admin/applications/applications.php',
										   'recache_class'    => 'admin_core_applications_applications',
									       'recache_function' => 'moduleRecache' );

		$CACHE['app_menu_cache']  = array( 'array'            => 1,
									       'allow_unload'     => 0,
									       'default_load'     => 1,
									       'recache_file'     => IPSLib::getAppDir( 'core' ) . '/modules_admin/applications/applications.php',
										   'recache_class'    => 'admin_core_applications_applications',
									       'recache_function' => 'applicationsMenuDataRecache',
									       'acp_only'         => 1 );
							       
		$CACHE['hooks']			  = array( 'array'            => 1,
									       'allow_unload'     => 0,
									       'default_load'     => 1,
									       'recache_file'     => IPSLib::getAppDir( 'core' ) . '/modules_admin/applications/hooks.php',
										   'recache_class'    => 'admin_core_applications_hooks',
									       'recache_function' => 'rebuildHooksCache' );

		/* User agents and skins */					
		$CACHE['useragents']      = array( 'array'            => 1,
									       'allow_unload'     => 0,
									       'default_load'     => 1,
									       'recache_file'     => IPS_ROOT_PATH . 'sources/classes/useragents/userAgentFunctions.php',
										   'recache_class'    => 'userAgentFunctions',
									       'recache_function' => 'rebuildUserAgentCaches' );

		$CACHE['useragentgroups'] = array( 'array'            => 1,
									       'allow_unload'     => 0,
									       'default_load'     => 1,
									       'recache_file'     => IPS_ROOT_PATH . 'sources/classes/useragents/userAgentFunctions.php',
										   'recache_class'    => 'userAgentFunctions',
									       'recache_function' => 'rebuildUserAgentGroupCaches' );							

		$CACHE['skinsets']        = array( 'array'            => 1,
									       'allow_unload'     => 0,
									       'default_load'     => 1,
									       'recache_file'     => IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php',
										   'recache_class'    => 'skinCaching',
									       'recache_function' => 'rebuildSkinSetsCache' );
							
		$CACHE['outputformats']   = array( 'array'            => 1,
									       'allow_unload'     => 0,
									       'default_load'     => 1,
									       'recache_file'     => IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php',
										   'recache_class'    => 'skinCaching',
									       'recache_function' => 'rebuildOutputFormatCaches' );

		$CACHE['skin_remap']      = array( 'array'            => 1,
									       'allow_unload'     => 0,
									       'default_load'     => 1,
									       'recache_file'     => IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php',
										   'recache_class'    => 'skinCaching',
									       'recache_function' => 'rebuildURLMapCache' );
							

		/* Basic caches */
		$CACHE['group_cache']     = array( 'array'            => 1,
									       'allow_unload'     => 0,
									       'default_load'     => 1,
									       'recache_file'     => IPSLib::getAppDir( 'members' ) . '/modules_admin/groups/groups.php',
									       'recache_class'    => 'admin_members_groups_groups',
									       'recache_function' => 'rebuildGroupCache' );
							
		$CACHE['settings']        = array( 'array'            => 1,
									       'allow_unload'     => 0,
									       'default_load'     => 1,
									       'recache_file'     => IPSLib::getAppDir( 'core' ) . '/modules_admin/tools/settings.php',
										   'recache_class'    => 'admin_core_tools_settings',
									       'recache_function' => 'settingsRebuildCache' );		
							       
		$CACHE['lang_data']       = array( 'array'            => 1,
							               'allow_unload'     => 0,
									       'default_load'     => 1,
									       'recache_file'     => IPS_ROOT_PATH . 'sources/classes/class_localization.php',
									       'recache_class'    => 'class_localization',
									       'recache_function' => 'rebuildLanguagesCache' );							       					
															


		$CACHE['banfilters']      = array( 'array'            => 1,
									       'allow_unload'     => 0,
									       'default_load'     => 1,
									       'recache_file'     => IPSLib::getAppDir( 'members' ) . '/modules_admin/members/banfilters.php',
										   'recache_class'    => 'admin_members_members_banfilters',
									       'recache_function' => 'rebuildBanCache' );


		$CACHE['stats']           = array( 'array'            => 1,
									       'allow_unload'     => 0,
									       'default_load'     => 1,
									       'recache_file'     => IPSLib::getAppDir( 'members' ) . '/modules_admin/members/tools.php',
										   'recache_class'    => 'admin_members_members_tools',
									       'recache_function' => 'rebuildStats' );

		/* Text handling */							
		$CACHE['emoticons'] = array( 
									'array'            => 1,
									'allow_unload'     => 0,
								    'default_load'     => 0,
								    'recache_file'     => IPSLib::getAppDir( 'core' ) . '/modules_admin/posts/emoticons.php',
									'recache_class'    => 'admin_core_posts_emoticons',
								    'recache_function' => 'emoticonsRebuildCache' 
								    );


		$CACHE['badwords'] = array( 
									'array'            => 1,
									'allow_unload'     => 0,
								    'default_load'     => 1,
								    'recache_file'     => IPSLib::getAppDir( 'core' ) . '/modules_admin/posts/badwords.php',
									'recache_class'    => 'admin_core_posts_badwords',
								    'recache_function' => 'badwordsRebuildCache' 
									);

		$CACHE['bbcode'] = array( 
									'array'            => 1,
									'allow_unload'     => 0,
									'default_load'     => 1,
									'recache_file'     => IPSLib::getAppDir( 'core' ) . '/modules_admin/posts/bbcode.php',
									'recache_class'    => 'admin_core_posts_bbcode',
									'recache_function' => 'bbcodeRebuildCache' 
								);

		$CACHE['mediatag'] = array( 
									'array'            => 1,
									'allow_unload'     => 0,
									'default_load'     => 1,
									'recache_file'     => IPSLib::getAppDir( 'core' ) . '/modules_admin/posts/media.php',
									'recache_class'    => 'admin_core_posts_media',
									'recache_function' => 'recacheMediaTag' 
								);
						
		$CACHE['profilefields'] = array( 
										'array'            => 1,
									    'allow_unload'     => 0,
									    'default_load'     => 1,
									    'recache_file'     => IPSLib::getAppDir( 'members' ) . '/modules_admin/members/customfields.php',
										'recache_class'    => 'admin_members_members_customfields',
									    'recache_function' => 'rebuildCache' 
									    );
							    
		$CACHE['ranks'] = array(
								'array'				=> 1,
								'allow_unload'		=> 0,
								'default_load'		=> 0,
								'recache_file'		=> IPSLib::getAppDir( 'members' ) . '/modules_admin/members/ranks.php',
								'recache_class'		=> 'admin_members_members_ranks',
								'recache_function'	=> 'titlesRecache' 
								);
								
		$CACHE['reputation_levels'] = array(
								'array'				=> 1,
								'allow_unload'		=> 0,
								'default_load'		=> 0,
								'recache_file'		=> IPSLib::getAppDir( 'members' ) . '/modules_admin/members/reputation.php',
								'recache_class'		=> 'admin_members_members_reputation',
								'recache_function'	=> 'rebuildReputationLevelCache' 
								);

		$CACHE['rss_output_cache'] = array(
								'array'				=> 1,
								'allow_unload'		=> 0,
								'default_load'		=> 1,
								'recache_file'		=> IPSLib::getAppDir( 'core' ) . '/modules_admin/tools/cache.php',
								'recache_class'		=> 'admin_core_tools_cache',
								'recache_function'	=> 'rebuildRssCache' 
								);

		if ( IN_DEV )
		{
			$CACHE['indev'] = array(
									'array'				=> 1,
									'allow_unload'		=> 0,
									'default_load'		=> 1,
									'recache_file'		=> '',
									'recache_class'		=> '',
									'recache_function'	=> '' 
									);
		}

		$_LOAD['report_cache']		= 1;
		$_LOAD['rss_output_cache']	= 1;
		
		return array( 'caches'    => $CACHE,
					  'cacheload' => $_LOAD );
	}
	
	/**
	 * Fetch the redirect mapping for short-hand urls
	 *
	 * @access	public
	 * @return	array 		Redirect mappings
	 */
	public function fetchRedirects()
	{
		$_RESET = array();

		###### New IPB 3.0.0 Redirects  ######

		if( isset( $_REQUEST['showannouncement'] ) && $_REQUEST['showannouncement'] )
		{
			$_RESET['app']         = 'forums';
			$_RESET['module']      = 'forums';
			$_RESET['section']     = 'announcements';
			$_RESET['announce_id'] = $_REQUEST['showannouncement'];
		}

		###### Redirect IPB 2.x to IPB 3.0 URLS ######

		# IDX
		if( isset( $_REQUEST['act'] ) && $_REQUEST['act'] == 'idx' )
		{
			$_RESET['app']     = 'forums';
			$_RESET['section'] = 'boards';
			$_RESET['module']  = 'forums';
		}

		# FORUM
		if( isset( $_REQUEST['showforum'] ) && $_REQUEST['showforum'] )
		{
			$_RESET['app']     = 'forums';
			$_RESET['module']  = 'forums';
			$_RESET['section'] = 'forums';
			$_RESET['f']       = $_REQUEST['showforum'];
		}

		# TOPIC
		if( isset( $_REQUEST['showtopic'] ) && $_REQUEST['showtopic'] )
		{
			$_RESET['app']     = 'forums';
			$_RESET['module']  = 'forums';
			$_RESET['section'] = 'topics';
			$_RESET['t']       = $_REQUEST['showtopic'];
		}

		if( ( isset( $_REQUEST['act'] ) && $_REQUEST['act'] == 'findpost' ) || isset( $_REQUEST['findpost'] ) && $_REQUEST['findpost'] )
		{
			$_RESET['pid']     = ( $_REQUEST['pid'] ) ? $_REQUEST['pid'] : $_REQUEST['findpost'];
			$_RESET['app']     = 'forums';
			$_RESET['module']  = 'forums';
			$_RESET['section'] = 'findpost';
		}

		# PROFILE
		if( isset( $_REQUEST['showuser'] ) && $_REQUEST['showuser'] )
		{
			$_RESET['app']		= 'members';
			$_RESET['module']	= 'profile';
			$_RESET['section']	= 'view';
			$_RESET['id']		= $_REQUEST['showuser'];
		}

		if( isset( $_REQUEST['act'] ) && $_REQUEST['act'] == 'members' )
		{
			$_RESET['app']		= 'members';
			$_RESET['module']	= 'list';
		}
		
		# RSS
		if ( isset( $_GET['act'] ) && $_GET['act'] == 'rss' && ! empty( $_GET['id'] ) )
		{
			$_RESET['app']		= 'core';
			$_RESET['module']	= 'global';
			$_RESET['section']	= 'rss';
			$_RESET['type']     = 'forums';
		}
		
		# SUBSMANAGER - Needed here to redirect stored IPN to new app
		if ( isset( $_REQUEST['act'] ) && $_REQUEST['act'] == 'paysubs' )
		{
			$_RESET['app'] = 'subscriptions';
			
			if ( isset( $_REQUEST['CODE'] ) )
			{
				if ( $_REQUEST['CODE'] == 'incoming' )
				{
					$_RESET['module']	= 'incoming';
					$_RESET['section']	= 'receive';
					$_RESET['do']       = 'validate';
					
					/* Brute force allow access */
					if ( ! defined( 'IPS_ENFORCE_ACCESS' ) )
					{
						define( 'IPS_ENFORCE_ACCESS', TRUE );
					}
					
				}
				else if ( $_REQUEST['CODE'] == 'paydone' )
				{
					$_RESET['module']	= 'incoming';
					$_RESET['section']	= 'receive';
					$_RESET['do']       = 'done';
					
					/* Brute force allow access */
					if ( ! defined( 'IPS_ENFORCE_ACCESS' ) )
					{
						define( 'IPS_ENFORCE_ACCESS', TRUE );
					}
				}
				else
				{
					$_RESET['do'] = $_REQUEST['CODE'];
				}
			}
			
		}
		
		# ALL
		if( ! isset( $_REQUEST['do'] ) AND ( isset( $_REQUEST['CODE'] ) OR isset( $_REQUEST['code'] ) ) )
		{
			$_RESET['do'] = ( $_REQUEST['CODE'] ) ? $_REQUEST['CODE'] : $_REQUEST['code'];
		}

		if( isset( $_REQUEST['autocom'] ) or isset( $_REQUEST['automodule'] ) )
		{
			$_RESET['app'] = $_REQUEST['autocom'] ? $_REQUEST['autocom'] : $_REQUEST['automodule'];
		}
		# Blog friendly urls
		else if( isset( $_GET['autocom'] ) or isset( $_GET['automodule'] ) )
		{
			$_RESET['app'] = $_GET['autocom'] ? $_GET['autocom'] : $_GET['automodule'];
		}
		
		if( isset( $_REQUEST['act'] ) && $_REQUEST['act'] == 'Print' )
		{
			$_RESET['app']     = 'forums';
			$_RESET['module']  = 'forums';
			$_RESET['section'] = 'printtopic';
		}
		
		return $_RESET;
	}

	/**
	 * SEO templates
	 *
	 * OUT FORMAT REGEX:
	 * First array element is a regex to run to see if we've a match for the URL
	 * The second array element is the template to use the results of the parenthesis capture
	 *
	 * Special variable #{__title__} is replaced with the $title data passed to output->formatUrl( $url, $title)
	 *
	 * IMPORTANT: Remember that when these regex are used, the output has not been fully parsed so you will get:
	 * showuser={$data['member_id']} NOT showuser=1 so do not try and match numerics only!
	 *
	 * IN FORMAT REGEX
	 *
	 * This allows the registry to piece back together a URL based on the template regex
	 * So, for example: "/user/(\d+?)/", 'matches' => array(  array( 'showuser' => '$1' ) )tells IP.Board to populate 'showuser' with the result
	 * of the parenthesis capture #1
	 *
	 * @access	public
	 * @return	array 		SEO templates
	 */
	public function fetchTemplates()
	{
		$templates = array(
			# SPECIAL TEMPLATE: Used when checking permalinks. {start}permalink{end}. If you changed these templates to use something like:
			# /forums/forum-12-my-forum.html then you would need to use start => '/', end => '.html'
			# varBlock is the bit that separates the FURL from other variables. varSep is the bit that separates the vars. So if you wanted to use:
			# /forums/forum-12-my-forum.html?st/20 or /forums/forum-12-my-forum.html?st-20-view-newpost
			# You'd use 'varBlock' = '?' and 'varSep' => ','
			
			'__data__'      => array( 'start'    => '-',
									  'end'      => '/',
									  'varBlock' => '/page__',
									  'varSep'   => '__' ),
			);
			
		return $templates;
	}
	
	/**
	 * Fetch bitwise mappings
	 * You can add to any of these arrays, but you cannot remove keys or re-order them. BAD THINGS WILL HAPPEN
	 *
	 * @access	public
	 * @return	array 	Bitwise mappings
	 */
	public function fetchBitwise()
	{
		$_BITWISE = array( 'facebook' => array( 'fbc_s_pic', 'fbc_s_avatar', 'fbc_s_status', 'fbc_s_aboutme' ), // facebook == profile_portal.fb_bwoptions
						   'members'  => array( 'bw_is_spammer',
						 						'bw_from_sfs',
						  						'bw_vnc_type',						 # 1 based on topic marking table, 0 based on last_visit
												'bw_forum_result_type' ),			 # 1 is list, 0 is forum
						   'groups'   => array( 
												'gbw_mod_post_unit_type'		, # 1 is days, 0 is posts
											    'gbw_ppd_unit_type'     		, # 1 is days, 0 is posts
											    'gbw_displayname_unit_type'     , # 1 is days, 0 is posts
											    'gbw_sig_unit_type'     		, # 1 is days, 0 is posts
											    'gbw_promote_unit_type'     	, # 1 is days, 0 is posts
											    'gbw_no_status_update'			, # 1 is blocked, 0 is not. Quite simple really
											  )
						);
											
		return $_BITWISE;
	}
}
