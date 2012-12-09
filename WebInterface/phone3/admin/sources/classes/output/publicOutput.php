<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Public output methods
 * Last Updated: $Date: 2009-08-31 20:32:10 -0400 (Mon, 31 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @since		Who knows...
 * @version		$Revision: 5066 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class output
{
	/**#@+
	 * Registry Object Shortcuts
	 *
	 * @access	public
	 * @var		object
	 */
	public $registry;
	public $DB;
	public $settings;
	public $request;
	public $lang;
	public $member;
	public $cache;
	/**#@-*/
	
	/**
	 * SEO templates
	 *
	 * @access	public
	 * @var		array
	 */
	public $seoTemplates		= array();
	
	/**
	 * URLs array
	 *
	 * @access	public
	 * @var		array
	 */
	public $urls				= array();
	
	/**
	 * Compiled templates
	 *
	 * @access	public
	 * @var		array
	 */
	public $compiled_templates	= array();
	
	/**
	 * Loaded templates
	 *
	 * @access	public
	 * @var		array
	 */
    public $loaded_templates	= array();
	
	/**
	 * HTML variable
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_html			= '';
	
	/**
	 * Page title
	 *
	 * @access	protected
	 * @var		string
	 */	
	protected $_title			= '';
	
	/**
	 * Basic navigation elements
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $__navigation		= array();
	
	/**
	 * Is this an error page?
	 *
	 * @access	protected
	 * @var		bool
	 */
	protected $_isError			= FALSE;
	
	/**
	 * Is this a page we should use SSL for?
	 *
	 * @access	public
	 * @var		bool
	 */
	public $isHTTPS				= FALSE;
	
	/**
	 * Custom navigation elements
	 *
	 * @access	protected
	 * @var		array
	 */
	public $_navigation			= array();
	
	/**
	 * Skin array
	 *
	 * @access	public
	 * @var		array
	 */
	public $skin				= array();
	
	/**
	 * All skins
	 *
	 * @access	public
	 * @var		array
	 */
	public $allSkins = array();
	
	/**
	 * Offline message
	 *
	 * @access	public
	 * @var		string
	 */
	public $offlineMessage = '';
	
	/**
	 * Add content to the document <head>
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $_documentHeadItems = array();
	
	/**
	 * Holds the JS modules to be loaded
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $_jsLoader = array();
	
	/**
	 * CSS array to be passed to main library
	 *
	 * @access	protected
	 * @var 	array
	 */
	protected $_css = array( 'import' => array(), 'inline' => array() );	
	
	/**
	 * Do not load skin_global
	 *
	 * @access	protected
	 * @var		boolean
	 */
	protected $_noLoadGlobal = FALSE;
	
	/**
	 * Maintain an array of seen template bits to prevent
	 * infinite recursion when dealing with parse template tags
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $_seenTemplates = array();
	
	/**
	 * Output format class
	 *
	 * @access	public
	 * @var		object
	 */
	public $outputFormatClass;

	/**
	 * Skin functions class, if needed
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $_skinFunctions;

	/**
	 * Are we using safe mode?
	 *
	 * @access	protected
	 * @var		bool
	 */
	protected $_usingSafeModeSkins = FALSE;
	
	
	/**
	 * Trap skin calls that could have incorrect names
	 *
	 * @access	public
	 * @param	string
	 * @param	mixed		void, or an array of arguments
	 * @return	mixed		string, or an error
	 */
	public function __call( $funcName, $args )
	{
		/* Output format stuff.. */
		switch ( $funcName )
		{
			case 'setCacheExpirationSeconds':
				if ( is_object( $this->outputFormatClass ) )
				{
					return $this->outputFormatClass->$funcName( $args[0] );
				}
			break;
			case 'setHeaderCode':
				if ( is_object( $this->outputFormatClass ) )
				{
					return $this->outputFormatClass->$funcName( $args[0], $args[1] );
				}
			break;
			case 'addMetaTag':
				if ( is_object( $this->outputFormatClass ) )
				{
					return $this->outputFormatClass->$funcName( $args[0], $args[1], (boolean)$args[2] );
				}
			break;
			case 'addCanonicalTag':
				if ( is_object( $this->outputFormatClass ) )
				{
					return $this->outputFormatClass->$funcName( $args[0], $args[1], $args[2] );
				}
			break;
		}
		
		$className = get_class( $this );
		
		if ( strstr( $className, 'skin_' ) )
		{
			preg_match( "#^skin_(.*)_(\d+?)$#", $className, $matches );
			$skinName = $matches[1];
			$skinID   = $matches[2];
			
			/* If we're here it's because the template bit doesn't exist, so... */
			return "<div style='background-color:white;color:black;font-size:14px;font-weight:bold'>Error: Could not load template '$funcName' from group '$skinName'</div>";
		}
		
		/* Still here... */
		trigger_error( "Method $funcName does not exist in $className", E_USER_ERROR );
	}
	
   	/**
	 * Construct
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @param	bool		Whether to init or not
	 * @return	void
	 */
	public function __construct( ipsRegistry $registry, $initialize=FALSE )
	{
		/* Make object */
		$this->registry   =  $registry;
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang       =  $this->registry->getClass('class_localization');
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
		
		/* Is this a task? */
		// No longer just returns, so this breaks links by building the urls twice for emails (anything using board_url)
		//if ( IPS_IS_TASK )
		//{
		//	$this->_buildUrls();
		//}
		
		/* Safe mode skins... */
		$this->_usingSafeModeSkins = ( ( $this->settings['safe_mode_skins'] == 0 AND $this->settings['safe_mode'] == 0 ) OR IN_DEV ) ? FALSE : TRUE;
	
		if ( $initialize === TRUE )
		{
			//-----------------------------------------
	    	// INIT
	    	//-----------------------------------------
	    	
			$_outputFormat    = 'html';
			$_outputClassName = 'htmlOutput';
			
			$this->allSkins = $this->_fetchAllSkins();
			$skinSetID      = $this->_fetchUserSkin();
			$this->skin     = $this->allSkins[ $skinSetID ];

			//-----------------------------------------
			// Get the skin caches
			//-----------------------------------------
   	
			$skinCaches = $this->cache->getWithCacheLib( 'Skin_Store_' . $skinSetID );
	
			if ( ! is_array($skinCaches) OR ! count($skinCaches) )
			{
				$_grab = ( IPS_IS_AJAX ) ? "'replacements'" : "'css', 'replacements'";
				
				$this->DB->build( array( 'select' => '*',
										 'from'   => 'skin_cache',
										 'where'  => "cache_set_id=" . $skinSetID . " AND cache_type IN (" . $_grab . ")" ) );
				$this->DB->execute();
			
				while( $row = $this->DB->fetch() )
				{
					$skinCaches[ $row['cache_value_2'] . '.' . $row['cache_id'] ] = $row;
				}
				
				/* Put skin cache back if needed */
				$this->cache->putWithCacheLib( 'Skin_Store_' . $skinSetID, $skinCaches, 86400 );
			}
			
			/* Avoid SQL filesort */
			ksort( $skinCaches );
			
			/* Loop and build */
			foreach( $skinCaches as $row )
			{
				switch( $row['cache_type'] )
				{
					default:
					break;
					case 'css':
						$appDir  = '';
						$appHide = 0;
						if ( strstr( $row['cache_value_4'], '-' ) )
						{
							list( $appDir, $appHide ) = explode( '-', $row['cache_value_4'] );
							
							if ( ( $appDir ) AND $appDir != IPS_APP_COMPONENT AND $appHide )
							{
								continue;
							}
						}
						
						/* Tied to specific modules within the app? */
						if ( $row['cache_value_6'] AND $this->request['module'] )
						{
							if ( ! in_array( $this->request['module'], explode( ',', str_replace( ' ', '', $row['cache_value_6'] ) ) ) )
							{
								continue;
							}
						}
					
						$skinCaches['css'][ $row['cache_value_1'] ] = array( 'content' => $row['cache_content'], 'attributes' => $row['cache_value_5'] );
					break;
					case 'replacements':
						$skinCaches['replacements'] = $row['cache_content'];
					break;
				}
			}
				
			$this->skin['_css']          = is_array( $skinCaches['css'] ) ? $skinCaches['css'] : array();
	    	$this->skin['_replacements'] = unserialize($skinCaches['replacements']);
	    	$this->skin['_skincacheid']  = $this->skin['set_id'];
			$this->skin['_csscacheid']   = 'css_' . $this->skin['set_id'];

			/* IN_DEV Stuff */
	    	if ( IN_DEV )
	    	{
				$this->skin['_css'] = array();
				
				if ( file_exists( DOC_IPS_ROOT_PATH . 'cache/skin_cache/masterMap.php' ) )
				{
					$REMAP = $this->buildRemapData();
					
					$_setId = intval( $REMAP['inDevDefault'] );
					$_dir   = $REMAP['templates'][ $REMAP['inDevDefault'] ];
					$_cdir  = $REMAP['css'][ $REMAP['inDevDefault'] ];
				}
				else
				{
					$_setId = 0;
					$_dir   = 'master_skin';
					$_cdir  = 'master_css';
				}
				
				/* Using a custom master skin */
				if ( $_setId )
				{
					$this->skin = $this->allSkins[ $_setId ];
					
					$this->skin['_replacements'] = unserialize( $skinCaches['replacements'] );
				}
				
				/* Sort out CSS */
				if ( ! isset( $this->_skinFunctions ) || ! is_object( $this->_skinFunctions ) )
				{
					require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );
					require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );

					$this->_skinFunctions = new skinCaching( $this->registry );
				}
				
				$css = $this->_skinFunctions->fetchDirectoryCSS( $_cdir );
				$tmp = array();
				$ord = array();
				
				foreach( $css as $name => $data )
				{
					/* Tied to app? */
					if ( ( $data['css_app'] ) AND $data['css_app'] != IPS_APP_COMPONENT AND $data['css_app_hide'] )
					{
						continue;
					}
				
					/* Tied to specific modules within the app? */
					if ( $data['css_modules'] AND ( ! in_array( $this->request['module'], explode( ',', str_replace( ' ', '', $data['css_modules'] ) ) ) ) )
					{
						continue;
					}
					
					$tmp[ $data['css_position'] . '.' . $data['css_group'] ][ $name ] = array( 'content' => $data['css_content'], 'attributes' => $data['css_attributes'] );
				}
				
				ksort( $tmp );
				
				foreach( $tmp as $blah => $data )
				{
					foreach( $data as $name => $data )
					{
						$ord[ $blah ] = array( 'css_group' => $name, 'css_position' => 1 );
						$this->skin['_css'][ $name ] = $data;
					}
				}
				
				/* Other data */
				$this->skin['_cssGroupsArray'] = $ord;
				$this->skin['_skincacheid']    = is_dir( IPS_CACHE_PATH . 'cache/skin_cache/' . $_dir ) ? $_setId : $this->skin['set_id'];
				$this->skin['_csscacheid']     = $_cdir;
				$this->skin['set_css_inline']  = ( $this->skin['set_css_inline'] AND is_dir( IPS_PUBLIC_PATH . 'style_css/' . $_cdir ) ) ? 1 : 0;
		
				if ( file_exists( IPS_CACHE_PATH . 'cache/skin_cache/' . $_dir . '/_replacements.inc' ) )
				{
					include_once( IPS_CACHE_PATH . 'cache/skin_cache/' . $_dir . '/_replacements.inc' );
					
					$this->skin['_replacements'] = $replacements;
				}
	    	}
	
			//-----------------------------------------
			// Which output engine?
			//-----------------------------------------
			
			if ( $this->skin['set_output_format'] )
			{
				if ( file_exists( IPS_ROOT_PATH . 'sources/classes/output/formats/' . $this->skin['set_output_format'] ) )
				{
					$_outputFormat    = $this->skin['set_output_format'];
					$_outputClassName = $this->skin['set_output_format'] . 'Output';
				}
			}
		
			require_once( IPS_ROOT_PATH . 'sources/classes/output/formats/coreOutput.php' );
			require_once( IPS_ROOT_PATH . 'sources/classes/output/formats/' . $_outputFormat. '/' . $_outputClassName. '.php' );
		
			$this->outputFormatClass = new $_outputClassName( $this );
			
			
			/* Build URLs */
			$this->_buildUrls();
		}
	}
	
	/**
	 * Reload skin set data
	 * Some applications need to ensure they get 'fresh' skin data not just the data loaded during INIT
	 *
	 * @access public
	 */
	public function reloadSkinData()
	{
		/* Whack the cache */
		$this->caches['skinsets'] = array();
		
		$this->allSkins = $this->_fetchAllSkins();
		$skinSetID      = $this->_fetchUserSkin();
		$this->skin     = $this->allSkins[ $skinSetID ];
	}
	
	/**
	 * Build URLs
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function _buildUrls()
	{
		//-----------------------------------------
		// Should we use HTTPS on this page?
		//-----------------------------------------
		
		$this->_setHTTPS();
		
		//-----------------------------------------
		// Board URLs and such
		//-----------------------------------------

		$this->settings['board_url']		= $this->settings['base_url'];
		$this->settings['js_main']		    = $this->settings['base_url'] . '/' . CP_DIRECTORY . '/js/';

		$this->settings['public_dir']		= $this->settings['base_url'] . '/' . PUBLIC_DIRECTORY . '/';
		$this->settings['cache_dir']		= $this->settings['base_url'] . '/cache/';

		$this->settings['base_url']		    = $this->settings['base_url'] .'/'.IPS_PUBLIC_SCRIPT.'?';
		$this->settings['base_url_ns']	    = $this->settings['base_url'] .'/'.IPS_PUBLIC_SCRIPT.'?';
		
		if ( $this->member->session_type != 'cookie' )
		{
			$this->settings['base_url']	.= 's='.$this->member->session_id.'&amp;';
		}
		/* Create new URL */
		$this->settings['base_url_with_app'] = $this->settings['base_url'] . 'app=' . IPS_APP_COMPONENT . '&amp;';

		$this->settings['js_base']		    = $this->settings['_original_base_url'].'/index.'.$this->settings['php_ext'].'?s='.$this->member->session_id.'&';

		$this->settings['img_url']		    = $this->settings['ipb_img_url'] ? $this->settings['ipb_img_url'] . PUBLIC_DIRECTORY . '/style_images/' . $this->skin['set_image_dir'] : $this->settings['_original_base_url'] . '/' . PUBLIC_DIRECTORY . '/style_images/' . $this->skin['set_image_dir'];
		$this->settings['img_url_no_dir']	= $this->settings['ipb_img_url'] ? $this->settings['ipb_img_url'] . PUBLIC_DIRECTORY . '/style_images/' : $this->settings['_original_base_url'] . '/' . PUBLIC_DIRECTORY . '/style_images/';
		
		/* HTTPS fixes */
		if( $this->isHTTPS )
		{
			$this->settings['board_url_https'] = str_replace( 'http://', 'https://', $this->settings['board_url'] );
			$this->settings['base_url_https']  = str_replace( 'http://', 'https://', $this->settings['base_url'] );
			$this->settings['public_dir']      = str_replace( 'http://', 'https://', $this->settings['public_dir'] );
			$this->settings['cache_dir']       = str_replace( 'http://', 'https://', $this->settings['cache_dir'] );
			$this->settings['img_url']         = str_replace( 'http://', 'https://', $this->settings['img_url'] );
			$this->settings['img_url_no_dir']  = str_replace( 'http://', 'https://', $this->settings['img_url_no_dir'] );
			$this->settings['fbc_xdlocation']  = str_replace( 'http://', 'https://', str_replace( 'xd_receiver.php', 'xd_receiver_ssl.php', $this->settings['fbc_xdlocation'] ) );
		}
		
		$this->settings['avatars_url']    = $this->settings['ipb_img_url'] ? $this->settings['ipb_img_url'] . PUBLIC_DIRECTORY . '/style_avatars' : $this->settings['_original_base_url'] . '/' . PUBLIC_DIRECTORY . '/style_avatars';
		$this->settings['emoticons_url']  = $this->settings['ipb_img_url'] ? $this->settings['ipb_img_url'] . PUBLIC_DIRECTORY . '/style_emoticons/<#EMO_DIR#>' : $this->settings['_original_base_url'] . '/' . PUBLIC_DIRECTORY . '/style_emoticons/<#EMO_DIR#>';
		$this->settings['mime_img']       = $this->settings['ipb_img_url'] ? $this->settings['ipb_img_url'] . PUBLIC_DIRECTORY : $this->settings['_original_base_url'] . '/' . PUBLIC_DIRECTORY;
	}
	
	/**
	 * Sets the isHTTPS class variable
	 *
	 * @access	private
	 * @return	void
	 * @todo 	[Future] Explore moving the https section definitions to app coreVariables.php
	 */
	private function _setHTTPS()
	{
		$this->isHTTPS = false;
		
		if( (ipsRegistry::$request['section'] == 'login' OR ipsRegistry::$request['section'] == 'register' or ipsRegistry::$request['module'] == 'usercp') AND $this->settings['logins_over_https'] )
		{
			/* Configure where we want HTTPS */
			$sectionsForHttps	= array(
										'core'	=> array(
														'global'	=> array(
																			'login'		=> array(),
																			'register'	=> array(),
																			'lostpass'	=> array(),
																			),
														'usercp'	=> array(
																			'core'	=> array( 'email', 'password' )
																			),
														),
										);

			foreach( $sectionsForHttps as $app => $modules )
			{
				if( $app == ipsRegistry::$request['app'] )
				{
					foreach( $modules as $module => $sections )
					{
						if( $module == ipsRegistry::$request['module'] )
						{
							foreach( $sections as $section => $areas )
							{
								//-----------------------------------------
								// User cp is "special"
								//-----------------------------------------
								
								if( $module == 'usercp' )
								{
									if( ipsRegistry::$request['tab'] == $section )
									{
										foreach( $areas as $area )
										{
											if( ipsRegistry::$request['area'] == $area )
											{
												$this->isHTTPS	= true;
												break 4;
											}
										}
									}
								}
								else
								{
									if( ipsRegistry::$request['section'] == $section )
									{
										$this->isHTTPS	= true;
										break 3;
									}
								}
							}
						}
					}
				}
			}
		}
	}
	
	/**
	 * Return all skin sets from the cache and expand them
	 *
	 * @access	protected
	 * @return	Array if skin (array [id] => data
	 */
	protected function _fetchAllSkins()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$gatewayFile = '';
		
		//-----------------------------------------
		// Check skin caches
		//-----------------------------------------

		if ( ! is_array( $this->caches['skinsets'] ) OR ! count( $this->caches['skinsets'] ) )
		{
			$this->cache->rebuildCache( 'skinsets', 'global' );
		}
		
		//-----------------------------------------
		// Did we come in via a gateway file?
		//-----------------------------------------
	
		foreach( $this->caches['outputformats'] as $key => $conf )
		{
			if ( $conf['gateway_file'] == IPS_PUBLIC_SCRIPT )
			{
				IPSDebug::addMessage( "Gateway file confirmed: " . $key );
				
				$gatewayFile = $key;
				break;
			}
		}
		
		//-----------------------------------------
		// Get 'em
		//-----------------------------------------
		
		$_skinSets = $this->caches['skinsets'];

		if ( is_array( $_skinSets ) )
		{
			foreach( $_skinSets as $id => $data )
			{
				$_skinSets[ $id ]['_parentTree']      = unserialize( $_skinSets[ $id ]['set_parent_array'] );
				$_skinSets[ $id ]['_childTree']       = unserialize( $_skinSets[ $id ]['set_child_array'] );
				$_skinSets[ $id ]['_userAgents']      = unserialize( $_skinSets[ $id ]['set_locked_uagent'] );
				$_skinSets[ $id ]['_cssGroupsArray']  = unserialize( $_skinSets[ $id ]['set_css_groups'] );
				$_skinSets[ $id ]['_youCanUse']       = FALSE;
				$_skinSets[ $id ]['_gatewayExclude']  = FALSE;
			   
				/* Can we see it? */
				if ( $_skinSets[ $id ]['set_permissions'] == '*' )
				{
					$_skinSets[ $id ]['_youCanUse'] = TRUE;
				}
				else if ( $_skinSets[ $id ]['set_permissions'] )
				{
					$_perms = explode( ',', $_skinSets[ $id ]['set_permissions'] );
				
					if ( in_array( $this->memberData['member_group_id'], $_perms ) )
					{
						$_skinSets[ $id ]['_youCanUse'] = TRUE;
					}
					else if ( $this->memberData['mgroup_others'] )
					{
						$_others = explode( ',', $this->memberData['mgroup_others'] );
					
						if ( count( array_intersect( $_others, $_perms ) ) )
						{
							$_skinSets[ $id ]['_youCanUse'] = TRUE;
						}
					}
				}
				
				/* Limit to output format? */
				if ( $gatewayFile AND ! IN_ACP )
				{
					if ( $_skinSets[ $id ]['set_output_format'] != $gatewayFile )
					{
						$_skinSets[ $id ]['_youCanUse']      = FALSE;
						$_skinSets[ $id ]['_gatewayExclude'] = TRUE;
					}
				}
			
				/* Array groups */
				if ( is_array( $_skinSets[ $id ]['_cssGroupsArray'] ) )
				{
					ksort( $_skinSets[ $id ]['_cssGroupsArray'], SORT_NUMERIC );
				}
				else
				{
					$_skinSets[ $id ]['_cssGroupsArray'] = array();
				}
			}
		}
	
		return $_skinSets;
	}
	
	/**
	 * Fetch a skin based on user's incoming data (user-agent, URL) or via other params
	 *
	 * The priority chain goes like this:
	 *
	 * Incoming Gateway file (index.php / xml.php / rss.php, etc) filters out some skins, then:
	 * - User Agent
	 * - URL Remap
	 * - App Specific
	 * - Member specific
	 * - Default skin
	 *
	 * @access	protected
	 * @return	int			ID of skin to use
	 */
	protected function _fetchUserSkin()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$useSkinID = FALSE;

		//-----------------------------------------
		// Unlocking a user-agent?
		//-----------------------------------------
		
		if ( isset( $this->request['unlockUserAgent'] ) AND $this->request['unlockUserAgent'] )
		{
			$this->member->updateMySession( array( 'uagent_bypass' => 1 ) );
			
			/* Set cookie */
			IPSCookie::set("uagent_bypass", 1, -1);
		}
		
		//-----------------------------------------
		// Changing a skin?
		//-----------------------------------------

		if ( isset( $this->request['settingNewSkin'] ) AND $this->request['settingNewSkin'] AND $this->settings['allow_skins'] AND $this->request['k'] == $this->member->form_hash )
		{
			$_id = intval( $this->request['settingNewSkin'] );
			
			/* Rudimentaty check */
			if ( $this->allSkins[ $_id ]['_youCanUse'] AND $this->allSkins[ $_id ]['_gatewayExclude'] !== TRUE )
			{
				if ( $this->memberData['member_id'] )
				{
					/* Update... */
					IPSMember::save( $this->memberData['member_id'], array( 'core' => array( 'skin' => $_id ) ) );
				}
				else
				{
					IPSCookie::set( 'guestSkinChoice', $_id );
				}
				
				/* Update member row */
				$this->memberData['skin'] = $_id;
			}
		}
		
		//-----------------------------------------
		// Ok, lets get a skin!
		//-----------------------------------------
		
		foreach( array( '_fetchSkinByUserAgent', '_fetchSkinByURLMap', '_fetchSkinByApp', '_fetchSkinByMemberPrefs', '_fetchSkinByDefault' ) as $function )
		{
			$useSkinID = $this->$function();
			
			if ( $useSkinID !== FALSE )
			{
				break;
			}
		}
		
		//-----------------------------------------
		// Return it...
		//-----------------------------------------

		return $useSkinID;
	}
	
	/**
	 * Attempt to get a skin choice based on user-agent
	 *
	 * @access	private
	 * @return	mixed		INT of a skin, FALSE if no skin found
	 */
	private function _fetchSkinByUserAgent()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$useSkinID = FALSE;
		
		if ( $this->memberData['userAgentKey'] AND ! $this->memberData['userAgentBypass'] )
		{ 
			foreach( $this->allSkins as $id => $data )
			{
				/* Got perms? */
				if ( $data['_youCanUse'] !== TRUE )
				{
					continue;
				}
				
				/* Can use with this output format? */
				if ( $data['_gatewayExclude'] !== FALSE )
				{
					continue;
				}
				
				/* Check user agents first */
				if ( is_array( $data['_userAgents']['uagents'] ) )
				{ 
					foreach( $data['_userAgents']['uagents'] as $_key => $_version )
					{
						if ( $this->memberData['userAgentKey'] == $_key )
						{
							if ( $_version )
							{
								$_versions = explode( ',', $_version );
							
								foreach( $_versions as $_v )
								{
									if ( strstr( $_v, '+' ) )
									{
										if ( $this->memberData['userAgentVersion'] >= intval( $_v ) )
										{
											$useSkinID = $id;
											break 3;
										}
									}
									else if ( strstr( $_v, '-' ) )
									{
										if ( $this->memberData['userAgentVersion'] <= intval( $_v ) )
										{
											$useSkinID = $id;
											break 3;
										}
									}
									else
									{
										if ( $this->memberData['userAgentVersion'] == intval( $_v ) )
										{
											$useSkinID = $id;
											break 3;
										}
									}
								}
							}
							else
							{
								/* We don't care about versions.. */
								$useSkinID = $id;
								break 2;
							}
						}
					}
				}
			
				/* Still here? */
				if ( is_array( $data['_userAgents']['groups'] ) AND $useSkinID === FALSE )
				{ 
					foreach( $data['_userAgents']['groups'] as $groupID )
					{
						$_group = $this->caches['useragentgroups'][ $groupID ];
						$_gData = unserialize( $_group['ugroup_array'] );
						
						if ( is_array( $_gData ) )
						{
							foreach( $_gData as $__key => $__data )
							{
								if ( $this->memberData['userAgentKey'] == $__key )
								{
									if ( $__data['uagent_versions'] )
									{
										$_versions = explode( ',', $__data['uagent_versions'] );
									
										foreach( $_versions as $_v )
										{
											if ( strstr( $_v, '+' ) )
											{
												if ( $this->memberData['userAgentVersion'] >= intval( $_v ) )
												{
													$useSkinID = $id;
													break 4;
												}
											}
											else if ( strstr( $_v, '-' ) )
											{
												if ( $this->memberData['userAgentVersion'] <= intval( $_v ) )
												{
													$useSkinID = $id;
													break 4;
												}
											}
											else
											{
												if ( $this->memberData['userAgentVersion'] == intval( $_v ) )
												{
													$useSkinID = $id;
													break 4;
												}
											}
										}
									}
									else
									{
										/* We don't care about versions.. */
										$useSkinID = $id;
										break 3;
									}
								}
							}
						}
					}
				}
			}
		}
		
		if ( $useSkinID !== FALSE )
		{
			$this->memberData['userAgentLocked'] = TRUE;
			IPSDebug::addMessage( "Skin set found via user agent. Using set #" . $useSkinID );
		}
		
		return $useSkinID;
	}
	
	/**
	 * Attempt to fetch a skin based on URL remap
	 *
	 * @access	private
	 * @return	mixed		INT skin ID or FALSE if none found
	 */
	private function _fetchSkinByURLMap()
	{
		$useSkinID = FALSE;
		
		//-----------------------------------------
		// Geddit?
		//-----------------------------------------
		
		if ( $this->caches['skin_remap'] and is_array( $this->caches['skin_remap'] ) AND count( $this->caches['skin_remap'] ) )
		{
			foreach( $this->caches['skin_remap'] as $id => $data )
			{
				if ( $data['map_match_type'] == 'exactly' )
				{
					if ( strtolower( $data['map_url'] ) == strtolower( $this->settings['query_string_real'] ) )
					{
						$useSkinID = $data['map_skin_set_id'];
						break;
					}
				}
				else if ( $data['map_match_type'] == 'contains' )
				{
					if ( stristr( $this->settings['query_string_real'], $data['map_url'] ) )
					{ 
						$useSkinID = $data['map_skin_set_id'];
						break;
					}
				}
			}
		}
		
		/* Can use with this output format? */
		if ( $useSkinID !== FALSE )
		{
			if ( $this->allSkins[ $useSkinID ]['_gatewayExclude'] !== FALSE )
			{
				$useSkinID = FALSE;
			}
		}
		
		if ( $useSkinID !== FALSE )
		{
			IPSDebug::addMessage( "Skin set found via URL remap. Using set #" . $useSkinID );
		}
		
		return $useSkinID;
	}
	
	/**
	 * Attempt to fetch a skin based on APPlication
	 *
	 * @access	private
	 * @return	mixed		INT skin ID or FALSE if none found
	 */
	private function _fetchSkinByApp()
	{
		$useSkinID = FALSE;
		$file      = IPSLib::getAppDir( IPS_APP_COMPONENT ) . '/extensions/coreExtensions.php';
		$class     = 'fetchSkin__' . IPS_APP_COMPONENT;
		 
		if ( file_exists( $file ) )
		{
			require_once( $file );
			
			if ( class_exists( $class ) )
			{
				$_grabber  = new $class( $this->registry );
				$useSkinID = $_grabber->fetchSkin();
			}
		}
		
		/* Can use with this output format? */
		if ( $useSkinID !== FALSE )
		{
			if ( $this->allSkins[ $useSkinID ]['_gatewayExclude'] !== FALSE )
			{
				$useSkinID = FALSE;
			}
		}
			
		if ( $useSkinID !== FALSE )
		{
			IPSDebug::addMessage( "Skin set found via APP. Using set #" . $useSkinID );
		}

		return $useSkinID;
	}
	
	/**
	 * Attempt to fetch a skin based on member's preferences
	 *
	 * @access	private
	 * @return	mixed		INT skin ID or FALSE if none found
	 */
	private function _fetchSkinByMemberPrefs()
	{
		$useSkinID = ( $this->memberData['member_id'] ) ? intval( $this->memberData['skin'] ) : intval( IPSCookie::get( 'guestSkinChoice' ) );
		
		if( !$useSkinID )
		{
			$useSkinID  = false;
		}
		
		/* Make sure it's legal */
		if ( $useSkinID )
		{
			$_test = $this->allSkins[ $useSkinID ];
			
			if ( $_test['_youCanUse'] !== TRUE )
			{
				$useSkinID = FALSE;
			}
		}
		
		if( ! $useSkinID )
		{
			$useSkinID = FALSE;
		}
			
		if ( $useSkinID !== FALSE )
		{
			IPSDebug::addMessage( "Skin set found via member's preferences. Using set #" . $useSkinID );
		}
		
		return $useSkinID;
	}
    
	/**
	 * Attempt to fetch a skin based on default settings
	 *
	 * @access	private
	 * @return	mixed		INT skin ID or FALSE if none found
	 */
	private function _fetchSkinByDefault()
	{
		$useSkinID = FALSE;
		
		/* Got one set by default for this gateway? */
		foreach( $this->allSkins as $data )
		{
			/* Can use with this output format? */
			if ( $data['_gatewayExclude'] !== FALSE )
			{
				continue;
			}
			
			if ( $data['set_is_default'] )
			{
				$useSkinID = $data['set_id'];
				break;
			}
		}
		
		/* Did we get anything? */
		if ( $useSkinID === FALSE )
		{
			foreach( $this->allSkins as $data )
			{
				/* Can use with this output format? */
				if ( $data['_gatewayExclude'] !== FALSE )
				{
					continue;
				}
				
				/* Grab the first one */
				$useSkinID = $data['set_id'];
				break;
			}
		}
		
		IPSDebug::addMessage( "Skin set not found, setting default. Using set #" . $useSkinID );
		
		return $useSkinID;
	}
	
	/**
	 * Returns a template class; loading if required
	 *
	 * @access	public
	 * @param	string	template name
	 * @param	boolean	[Test only, TRUE for yes, FALSE for no]
	 * @return	mixed	Object, or null
	 */
	public function getTemplate( $groupName )
	{
		if ( ! isset( $this->compiled_templates[ 'skin_' . $groupName ] ) || ! is_object( $this->compiled_templates[ 'skin_' . $groupName ] ) )
		{
			//-----------------------------------------
			// Using self:: so that we can load public
			//	skins inside ACP when necessary
			//-----------------------------------------
			
			self::loadTemplate( 'skin_' . $groupName );
		}
		
		return isset( $this->compiled_templates[ 'skin_' . $groupName ] ) ? $this->compiled_templates[ 'skin_' . $groupName ] : NULL;
	}
	
	/**
	 * Returns a replacement (aka macro)
	 *
	 * @access	public
	 * @param	string 		Replacement key
	 * @return	string		Replacement value
	 */
	public function getReplacement( $key )
	{	
		if( is_array($this->skin['_replacements']) AND count($this->skin['_replacements']) )
		{
			if ( isset($this->skin['_replacements'][ $key ]) )
			{
				$value = $this->skin['_replacements'] [ $key ];
				
				if ( strstr( $value, '{lang:' ) )
				{
					$value = preg_replace_callback( '#\{lang:([^\}]+?)\}#', create_function( '$key', 'return ipsRegistry::getClass(\'class_localization\')->words[$key[1]];' ), $value );
				}
				
				return $value;
			}
		}
	}
	
	/**
	 * Load a normal template file from either cached PHP file or
	 * from the DB. Populates $this->compiled_templates[ _template_name_ ]
	 *
	 * @access	public
	 * @param	string	Template name
	 * @param	integer	Template set ID
	 * @return	void
	 */
	public function loadTemplate( $name, $id='' )
	{
		$tags 	= 1;
		$loaded	= 0;
		
		//-----------------------------------------
		// Select ID
		//-----------------------------------------
		
		if ( ! $id )
		{
			$id = $this->skin['_skincacheid'];
		}
	
		//-----------------------------------------
		// Full name
		//-----------------------------------------
		
		$full_name        = $name.'_'.intval($id);
		$skin_global_name = 'skin_global_'.$id;
		$_name            = $name;
		
		//-----------------------------------------
		// Already got this template loaded?
		//-----------------------------------------
	
		if ( isset( $this->loaded_templates[ $full_name ] ) && $this->loaded_templates[ $full_name ] )
		{
			return;
		}

		//-----------------------------------------
		// Not running safemode skins?
		//-----------------------------------------
		
		if ( $this->_usingSafeModeSkins === FALSE )
		{
			//-----------------------------------------
			// Simply require and return
			//-----------------------------------------
			
			if ( $name != 'skin_global')
			{
				if ( ! ( isset( $this->loaded_templates[ $skin_global_name ] ) && $this->loaded_templates[ $skin_global_name ] ) AND $this->_noLoadGlobal === FALSE )
				{
					//-----------------------------------------
					// Suck in skin global..
					//-----------------------------------------
					
					if ( $this->load_template_from_php( 'skin_global', 'skin_global_'.$id, $id ) )
					{
						$loaded = 1;
					}
					
					//-----------------------------------------
					// Suck in normal file...
					//-----------------------------------------
					
					if ( ! $this->load_template_from_php( $_name, $name.'_'.$id, $id ) )
					{
						$loaded = 0;
					}
				}
				else
				{
					//-----------------------------------------
					// Suck in normal file...
					//-----------------------------------------
					
					if ( $this->load_template_from_php( $_name, $name.'_'.$id, $id ) )
					{
						$loaded = 1;
					}
				}
			}
			else
			{
				if ( $name == 'skin_global' )
				{
					//-----------------------------------------
					// Suck in skin global..
					//-----------------------------------------
					
					if ( $this->load_template_from_php( 'skin_global', 'skin_global_'.$id, $id ) )
					{
						$loaded = 1;
					}
					
					return;
				}
				else
				{
					//-----------------------------------------
					// Suck in normal file...
					//-----------------------------------------
					
					if ( $this->load_template_from_php( $_name, $name.'_'.$id, $id ) )
					{
						$loaded = 1;
					}
				}
			}
		}
		
		//-----------------------------------------
		// safe_mode_skins OR flat file load failed
		//-----------------------------------------
		
		if ( ! $loaded )
		{
			//-----------------------------------------
			// We're using safe mode skins, yippee
			// Load the data from the DB
			//-----------------------------------------
			
			$skin_global = "";
			$other_skin  = "";
			$this->skin['_type'] = 'Database Skins';
			
			if ( $this->loaded_templates[ $skin_global_name ] == "" and $name != 'skin_global'  AND $this->_noLoadGlobal === FALSE )
			{
				//-----------------------------------------
				// Skin global not loaded...
				//-----------------------------------------
				
				$this->DB->build( array( 'select' => '*',
										 'from'   => 'skin_cache',
										 'where'  => "cache_set_id=".$id." AND cache_value_1 IN ('skin_global', '$name')" ) );
									 
				$this->DB->execute();
				
				while ( $r = $this->DB->fetch() )
				{
					if ( $r['cache_value_1'] == 'skin_global' )
					{
						$skin_global = $r['cache_content'];
					}
					else
					{
						$other_skin  = $r['cache_content'];
					}
				}

				if( !class_exists( $new_skin_global_name ) )
				{
					eval($skin_global);
				}
				
				$new_skin_global_name	= $this->_getSkinHooks( 'skin_global', $skin_global_name, $id );
				
				$this->compiled_templates['skin_global'] =  new $new_skin_global_name( $this->registry );
				
				# Add to loaded templates
				$this->loaded_templates[ $skin_global_name ] = $new_skin_global_name;
			}
			else
			{
				//-----------------------------------------
				// Skin global is loaded..
				//-----------------------------------------
				
				if ( $name == 'skin_global' and in_array( $skin_global_name, $this->loaded_templates ) )
				{
					return;
				}
				
				//-----------------------------------------
				// Load the skin, man
				//-----------------------------------------
				
				$template   = $this->DB->buildAndFetch( array( 'select' => '*',
										 					   'from'   => 'skin_cache',
										 					   'where'  => "cache_set_id=".$id." AND cache_value_1='$name'" ) );
									 
				$other_skin = $template['cache_content'];
				
			}
			
			eval($other_skin);
			
			if ( $name == 'skin_global' )
			{
				$new_skin_global_name = $this->_getSkinHooks( 'skin_global', $skin_global_name, $id );
				
				$this->compiled_templates['skin_global']           =  new $new_skin_global_name( $this->registry );
				
				# Add to loaded templates
				$this->loaded_templates[ $skin_global_name ] = $new_skin_global_name;
			}
			else
			{
				$new_full_name = $this->_getSkinHooks( $name, $full_name, $id );

				if( class_exists( $new_full_name ) )
				{
					$this->compiled_templates[ $name ]           =  new $new_full_name( $this->registry );
					
					# Add to loaded templates
					$this->loaded_templates[ $full_name ] = $new_full_name;
				}
			}
		}
	}

    /**
	 * Load the template bit from the PHP file      
	 *
	 * @access	public
	 * @param	string	Name of the PHP file (sans .php)
	 * @param	string	Name of the class
	 * @param	int		Skin ID
	 * @return	boolean
	 */
	public function load_template_from_php( $name='skin_global', $full_name='skin_global_0', $id=0 )
	{
		$_NOW = IPSDebug::getMemoryDebugFlag();
		
		//-----------------------------------------
		// IN_DEV?
		//-----------------------------------------

		if ( IN_DEV )
		{
			//-----------------------------------------
			// Load functions and cache classes
			//-----------------------------------------
			
			if ( ! isset( $this->_skinFunctions ) || ! is_object( $this->_skinFunctions ) )
			{
				require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinFunctions.php' );
				require_once( IPS_ROOT_PATH . 'sources/classes/skins/skinCaching.php' );

				$this->_skinFunctions = new skinCaching( $this->registry );
			}
			
			# Load the master skin template
			$this->_skinFunctions->loadMasterSkinTemplate( $name, $id );
		}
		else
		{
			//-----------------------------------------
			// File exist?
			//-----------------------------------------

			if ( ! file_exists( IPS_CACHE_PATH."cache/skin_cache/cacheid_".$id."/".$name.".php" ) )
			{
				return FALSE;
			}
			
			require_once( IPS_CACHE_PATH."cache/skin_cache/cacheid_".$id."/".$name.".php" );
		}
		
		$new_full_name = $this->_getSkinHooks( $name, $full_name, $id );
		
		if( class_exists( $new_full_name ) )
		{
			$this->compiled_templates[ $name ] =  new $new_full_name( $this->registry );
		
			# Add to loaded templates
			$this->loaded_templates[ $full_name ] = $new_full_name;
		}
	
		IPSDebug::setMemoryDebugFlag( "publicOutput: Loaded skin file - $name", $_NOW );
		
		return TRUE;
	}
	
	/**
	 * Builds a URL
	 *
	 * Example: $this->registry->output->buildUrl( 'showtopic=1', 'public' );
	 * Generates: 'http://www.board.com/forums/index.php?showtopic=1'
	 *
	 * @access	public
	 * @param	string		URL bit
	 * @param	string		Type of URL
	 * @param	string		Whether to apply http auth to the URL
	 * @return	string		Formatted URL
	 */
	public function buildUrl( $url, $urlBase='public', $httpauth="false" )
	{
		/* INIT */
		$base = '';
		
		//-----------------------------------------
		// Caching
		//-----------------------------------------
		
		static $cached		= array();
		$_md5				= md5( $url . $urlBase . intval($httpauth) );
		
		if( array_key_exists( $_md5, $cached ) )
		{
			//print "Returned cache for: " . $url . "<br />";
			return $cached[ $_md5 ];
		}

		if ( $urlBase )
		{
			switch ( $urlBase )
			{
				default:
				case 'none':
					$base = '';
				break;
				case 'public':
					if ( IN_ACP )
					{
						$base = $this->settings['public_url'];
					}
					else
					{
						$base = $this->settings['base_url'];
					}
				break;
				case 'publicWithApp':
					$base = $this->settings['base_url_with_app'];
				break;
				case 'admin':
					$base = $this->settings['base_url'];
				break;
				case 'public_dir':
					$base = $this->settings['public_dir'];
					
					if( $this->isHTTPS )
					{
						$base = str_replace( 'http://', 'https://', $base );
					}
				break;
				case 'img_url':
					$base = $this->settings['img_url'];
					
					if( $this->isHTTPS )
					{
						$base = str_replace( 'http://', 'https://', $base );
					}
				break;
				case 'avatars':
					$base = $this->settings['avatars'];
				break;
				case 'emoticons':
					$base = $this->settings['emoticons'];
				break;
				case 'mime':
					$base = $this->settings['mime'];
				break;
				case 'upload':
					$base = $this->settings['upload_url'];
				break;
			}
		}
		
		if ( strtolower( $httpauth ) == "true" AND ( $this->settings['http_auth_username'] AND $this->settings['http_auth_password'] ) )
		{
			$base = str_replace( 'http://', 'http://' . $this->settings['http_auth_username'] . ':' . $this->settings['http_auth_password'] . '@', $base );
		}
			
		if ( 
			stripos( $url, 'section=login' ) !== false OR 
			stripos( $url, 'section=register' ) !== false OR 
			stripos( $url, 'section=lostpass' ) !== false OR
			( stripos( $url, 'module=usercp' ) !== false AND stripos( $url, 'tab=core' ) !== false AND ( stripos( $url, 'area=password' ) !== false OR stripos( $url, 'area=email' ) !== false ) )
			)
		{
			if( $this->settings['logins_over_https'] )
			{
				$base = str_replace( 'http://', 'https://', $base );
			}
		}
		
		$cached[ $_md5 ]	= $base . $url;
		return $base . $url;
	}
	
	/**
	 * Formats the URL (.htaccess SEO, etc)
	 *
	 * @access	public
	 * @param	string	Raw URL
	 * @param	string	Any special SEO title passed
	 * @param	string	Any special SEO template to use. If none is passed but SEO is enabled, IPB will search all templates for a match
	 * @return	string	Formatted  URL
	 */
	public function formatUrl( $url, $seoTitle='', $seoTemplate='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$_template		= FALSE;
		static $cached	= array();
		$_md5			= md5($url.$seoTitle.$seoTemplate);
		
		if( array_key_exists( $_md5, $cached ) )
		{
			//print "Returned cache for: " . $url . "<br />";
			return $cached[ $_md5 ];
		}
		
		//if ( $this->settings['use_friendly_urls'] AND is_array( $this->seoTemplates ) )
		if ( $this->settings['use_friendly_urls'] AND $seoTitle )
		{	
			if ( $seoTemplate AND isset($this->seoTemplates[ $seoTemplate ]) )
			{
				$_template = $seoTemplate;
			}
			
			/* Need to search for one - fast? */
			if ( $_template === FALSE )
			{
				/* Search for one, then. Possibly a bit slower than we'd like! */
				foreach( $this->seoTemplates as $key => $data )
				{
					if ( stristr( $url, $key ) )
					{ 
						$_template = $key;
						break;
					}
				}
			}

			// Switched this off for efficiency
			/* Need to search for one? */
			/*if ( $_template === FALSE )
			{
				/ * Search for one, then. Possibly a bit slower than we'd like! * /
				foreach( $this->seoTemplates as $key => $data )
				{
					$regex = str_replace( '#', '\\#', $data['out'][0] );
					
					if ( preg_match( "#{$regex}#i", $url ) )
					{
						$_template = $key;
						break;
					}
				}
			}*/

			/* Got one to work with? */
			if ( $_template !== FALSE )
			{
				if ( substr( $seoTitle, 0, 2 ) == '%%' AND substr( $seoTitle, -2 ) == '%%' )
				{
					$seoTitle = IPSText::makeSeoTitle( substr( $seoTitle, 2, -2 ) );
				}
				
				/* Do we need to encode? */
				if ( IPS_DOC_CHAR_SET != 'UTF-8' )
				{
					$seoTitle = urlencode( $seoTitle );
				}
				
				$replace    = str_replace( '#{__title__}', $seoTitle, $this->seoTemplates[ $_template ]['out'][1] );
				
				$url     = preg_replace( $this->seoTemplates[ $_template ]['out'][0], $replace, $url );
				$_anchor = '';
				$__url   = $url;

				/* Protect html entities */
				$url = str_replace( '&#', '~|~', $url );
			
				if ( strstr( $url, '&' ) )
				{
					$restUrl = substr( $url, strpos( $url, '&' ) );
					$url     = substr( $url, 0, strpos( $url, '&' ) );
				}
				else
				{
					$restUrl = '';
				}
				
				/* Anchor */
				if ( strstr( $restUrl, '#' ) )
				{
					$_anchor = substr( $restUrl, strpos( $restUrl, '#' ) );
					$restUrl = substr( $restUrl, 0, strpos( $restUrl, '#' ) );
				}
				
				switch ( $this->settings['url_type'] )
				{
					case 'path_info':
						if ( $this->settings['htaccess_mod_rewrite'] )
						{
							$url = str_replace( IPS_PUBLIC_SCRIPT . '?', '', $url );
						}
						else
						{
							$url = str_replace( IPS_PUBLIC_SCRIPT . '?', IPS_PUBLIC_SCRIPT . '/', $url );
						}
					break;
					default:
					case 'query_string':
						$url = str_replace( IPS_PUBLIC_SCRIPT . '?', IPS_PUBLIC_SCRIPT . '?/', $url );
					break;
				}
				
				/* Ensure that if the seoTitle is missing there is no double slash */
				# http://localhost/invisionboard3/user/1//
				# http://localhost/invisionboard3/user/1/mattm/
				if ( substr( $url, -2 ) == '//' )
				{
					$url = substr( $url, 0, -1 );
				}

				/* Others... */
				if ( $restUrl )
				{
					$_url  = str_replace( '&amp;', '&', str_replace( '?', '', $restUrl ) );
					$_data = explode( "&", $_url );
					$_add  = array();
				
					foreach( $_data as $k )
					{
						if ( strstr( $k, '=' ) )
						{
							list( $kk, $vv ) = explode( '=', $k );
						
							if ( $kk and $vv )
							{
								$_add[] = $kk . $this->seoTemplates['__data__']['varSep'] . $vv;
							}
						}
					} 
						
					/* Got anything to add?... */
					if ( count( $_add ) )
					{
						if ( strrpos( $url, $this->seoTemplates['__data__']['end'] ) + strlen( $this->seoTemplates['__data__']['end'] ) == strlen( $url ) )
						{
							$url = substr( $url, 0, -1 );
						}

						$url .= $this->seoTemplates['__data__']['varBlock'] . implode( $this->seoTemplates['__data__']['varSep'], $_add );
					}
				}
			
				/* anchor? */
				if ( $_anchor )
				{
					$url .= $_anchor;
				}
				
				/* Protect html entities */
				$url = str_replace( '~|~', '&#', $url );
				
				$cached[ $_md5 ]	= $url;
				return $url;
			} # / template
			else
			{
				$cached[ $_md5 ]	= $url;
				return $url;
			}
		} # / furl on
		else
		{
			$cached[ $_md5 ]	= $url;
			return $url;
		}
	}
	
	/**
	 * Builds a fURL
	 * Wrapper of formatUrl and  buildUrl
	 *
	 * @example  buildSEOUrl( 'section=foo&module=bar', 'public', 'Matts Link', 'showuser' );
	 * @access	public
	 * @param	string		URL (typically, part of; without the 'base_url')
	 * @param	string		URL Type (corresponds with buildUrl, so 'public', 'publicWithApp', etc
	 * @param	string		SEO Title
	 * @param	string		SEO Template
	 * @return	string		SEO URL
	 */
	public function buildSEOUrl( $url, $urlType='public', $seoTitle='', $seoTemplate='' )
	{
		return $this->formatUrl( $this->buildUrl( $url, $urlType ), $seoTitle, $seoTemplate );
	}
	
	/**
	 * Check to ensure a permalink is correct
	 * Accepts a second value of TRUE to simply return a boolean (TRUE means permalink is OK, false means it is not)
	 * By default, it takes action based on your settings
	 *
	 * @access	public
	 * @param	string		Correct SEO title (app_dir)
	 * @param	boolean		[TRUE, return a boolean (true for OK, false for not). FALSE {default} simply take action based on settings]
	 * @return	boolean
	 */
	public function checkPermalink( $seoTitle, $return=FALSE )
	{
		/* Only serve GET requests */
		if ( $this->request['request_method'] != 'get' )
		{
			return FALSE;
		}
		
		if ( ! $this->settings['use_friendly_urls'] OR ! $seoTitle OR ! $this->settings['seo_bad_url'] OR $this->settings['seo_bad_url'] == 'nothing' )
		{
			return FALSE;
		}
		
		$_st  = $this->seoTemplates['__data__']['start'];
		$_end = $this->seoTemplates['__data__']['end'];
		$_sep = $this->seoTemplates['__data__']['varSep'];
		$_blk = $this->seoTemplates['__data__']['varBlock'];
		$_qs  = $_SERVER['QUERY_STRING'] ? $_SERVER['QUERY_STRING'] : @getenv('QUERY_STRING');
		$_uri = $_SERVER['REQUEST_URI']  ? $_SERVER['REQUEST_URI']  : @getenv('REQUEST_URI');
		
		$_toTest = ( $_qs ) ? $_qs : $_uri;

		/* Shouldn't need to check this, but feel better for doing it: Friendly URL? */
		if ( ! strstr( $_toTest, $_end ) )
		{
			return FALSE;
		}
		
		/* Try original */
		if ( ! preg_match( "#" . $_st . preg_quote( $seoTitle, '#' ) . '(' . $_end . "$|" . preg_quote( $_blk, '#' ) . ")#",  $_toTest ) )
		{
			/* Do we need to encode? */
			$_toTest = urldecode( $_toTest );
		}
		
		if ( ! preg_match( "#" . $_st . preg_quote( $seoTitle, '#' ) . '(' . $_end . "$|" . preg_quote( $_blk, '#' ) . ")#",  $_toTest ) )
		{ 
			if ( $return === TRUE )
			{
				return FALSE;
			}
			
			/* Still here? */
			switch( $this->settings['seo_bad_url'] )
			{
				default:
				case 'meta':
					$this->addMetaTag( 'robots', 'noindex,nofollow' );
				break;
				case 'redirect':
					$uri  = array();
					
					foreach( $this->seoTemplates as $key => $data )
					{
						if ( ! $data['in']['regex'] )
						{
							continue;
						}

						if ( preg_match( $data['in']['regex'], $_toTest, $matches ) )
						{
							if ( is_array( $data['in']['matches'] ) )
							{
								foreach( $data['in']['matches'] as $_replace )
								{
									$k = IPSText::parseCleanKey( $_replace[0] );

									if ( strstr( $_replace[1], '$' ) )
									{
										$v = IPSText::parseCleanValue( $matches[ intval( str_replace( '$', '', $_replace[1] ) ) ] );
									}
									else
									{
										$v = IPSText::parseCleanValue( $_replace[1] );
									}

									$uri[] = $k . '=' . $v;
								}
							}

							if ( strstr( $_toTest, $_blk ) )
							{
								$_parse = substr( $_toTest, strrpos( $_toTest, $_blk ) + strlen( $_blk ) );

								$_data = explode( $_sep, $_parse );
								$_c    = 0;

								foreach( $_data as $_v )
								{
									if ( ! $_c )
									{
										$k = IPSText::parseCleanKey( $_v );
										$v = '';
										$_c++;
									}
									else
									{
										$v  = IPSText::parseCleanValue( $_v );
										$_c = 0;

										$uri[] = $k . '=' . $v;
									}
								}
							}
							
							break;
						}
					}
					
					/* Got something? */
					if ( count( $uri ) )
					{
						$newurl	= $this->registry->getClass( 'output' )->formatUrl( $this->registry->getClass( 'output' )->buildUrl( implode( '&', $uri ), 'public' ), $seoTitle, $key );
						
						if ( $this->settings['base_url'] . $_toTest != $newurl )
						{
							$this->registry->getClass('output')->silentRedirect( $newurl, $seoTitle, TRUE );
						}
					}
					else
					{
						return FALSE;
					}
					
					
				break;
			}
		}
		
		return TRUE;
	}
	
	/**
	 * Clear any loaded CSS
	 *
	 * @access	public
	 * @return	void
	 */
	public function clearLoadedCss()
	{
		$this->_css	= array(
							'inline'	=> array(),
							'import'	=> array(),
							);
	}
	
	/**
	 * Add content to the document <head>
	 *
	 * @access	public
	 * @param	string		Type of data to add: inlinecss, importcss, js, raw, rss, rsd, etc
	 * @param	string		Data to add
	 * @return	void
	 */
	public function addToDocumentHead( $type, $data )
	{
		if( $type == 'inlinecss' )
		{
			$this->_css['inline'][]	= array(
											'content'	=> $data,
											);
		}
		else if( $type == 'importcss' )
		{
			//-----------------------------------------
			// Use $data as key to prevent CSS being
			// included more than once (breaks Minify)
			//-----------------------------------------

			$this->_css['import'][$data]	= array(
											'content'	=> $data,
											);
		}
		else
		{
			$this->_documentHeadItems[ $type ][] = $data;
		}
	}
	
	/**
	 * Passes a module name to the IPB JS loader script
	 *
	 * @access	public
	 * @param	string		Name of module to load
	 * @param	integer		High Priority
	 * @return	void
	 */
	public function addJSModule( $data, $priority )
	{
		$this->_jsLoader[$data] = $priority;
	}
	
	/**
	 * Add content
	 *
	 * @access	public
	 * @param	string		content to add
	 * @param	boolean		Prepend instead of append
	 * @return	void
	 */
	public function addContent( $content, $prepend=false )
	{
		if( $prepend )
		{
			$this->_html = $content . $this->_html;
		}
		else
		{
			$this->_html .= $content;
		}
	}
	
	/**
	 * Set the title of the document
	 *
	 * @access	public
	 * @param	string		Title
	 * @return	void
	 */
	public function setTitle( $title )
	{
		$this->_title = $title;
	}
	
	/**
	 * Get the currently set page title
	 *
	 * @access	public
	 * @return	string	Page title
	 */
	public function getTitle()
	{
		return $this->_title;
	}
	
	/**
	 * Add navigational elements
	 *
	 * @access	public
	 * @param	string		Title
	 * @param	string		URL
	 * @param	string		SEO Title
	 * @param	string		SEO Template
	 * @return	void
	 */
	public function addNavigation( $title, $url, $seoTitle='', $seoTemplate='' )
	{
		$this->_navigation[] = array( $title, $url, $seoTitle, $seoTemplate );
	}
	
	/**
	 * Set the is error flag
	 *
	 * @access	public
	 * @param	bool	Set it to true/false
	 * @return	void
	 */
    public function setError( $boolean )
	{
		$this->_isError = $boolean;
	}

	/**
	 * Global set up stuff
	 * Sorts the JS module array, calls initiate on the output engine, etc
	 *
	 * @access	private
	 * @param	string		Type of output (normal/popup/redirect)
	 * @return	void
	 */
	private function _sendOutputSetUp( $type )
	{
		//-----------------------------------------
        // INIT
        //-----------------------------------------
        
		$this->outputFormatClass->core_initiate();
		
		//-----------------------------------------
		// Type...
		//-----------------------------------------
		
		$this->outputFormatClass->core_setOutputType( $type );
				
		//----------------------------------------
		// Sort JS Modules
		//----------------------------------------
		
		arsort( $this->_jsLoader, SORT_NUMERIC );

		//-----------------------------------------
        // NAVIGATION
        //-----------------------------------------
        
        if ( $this->_isError === TRUE )
        {
			$this->_navigation = array();
        }

        //-----------------------------------------
        // Check for IPS report
        //-----------------------------------------
        
		$this->_checkIPSReport();

		//-----------------------------------------
		// Board offline?
		//-----------------------------------------
		
 		if ( $this->settings['board_offline'] == 1 )
 		{
 			$this->_title = $this->lang->words['warn_offline'] . " " . $this->_title;
 		}
 		
		//-----------------------------------------
        // Extra head items
        //-----------------------------------------
        
        $this->outputFormatClass->addHeadItems();
        
		//-----------------------------------------
        // And finally send the extra CSS
        //-----------------------------------------

		if( count($this->_css['import'] ) )
		{
			foreach( $this->_css['import'] as $data )
			{
				$this->outputFormatClass->addCSS( 'import', $data['content'] );
			}
		}

		if( count($this->_css['inline'] ) )
		{
			foreach( $this->_css['inline'] as $data )
			{
				$this->outputFormatClass->addCSS( 'inline', $data['content'] );
			}
		}
        
        //-----------------------------------------
        // Easter egg?  Or is it...mwahaha
        //-----------------------------------------
        
        if( isset( $this->request[ base64_decode('eWVhcg==') ] ) AND $this->request[ base64_decode('eWVhcg==') ] == base64_decode('aSZsdDszMTk5OQ==') )
        {
        	$this->_jsLoader['misc'] = 0;
        	$this->addToDocumentHead( 'raw', "<style type='text/css'>#content{ background-image: url(public/style_captcha/captcha_backgrounds/captcha3.jpg); background-repeat: repeat; } *{ font-family: 'Comic Sans MS'; color: #ff9900; font-size: 1.05em; cursor: crosshair; }</style>" );
        }
        
	}
    
    /**
	 * Main output function
	 *
	 * @access	public
	 * @return	void 
	 */
    public function sendOutput()
    {
        //-----------------------------------------
        // INIT
        //-----------------------------------------
        
		$_NOW = IPSDebug::getMemoryDebugFlag();

		$this->_sendOutputSetUp( 'normal' );
		
		//-----------------------------------------
		// Gather output
		//-----------------------------------------

        $output = $this->outputFormatClass->fetchOutput( $this->_html, $this->_title, $this->_navigation, $this->_documentHeadItems, $this->_jsLoader );
		
		$output = $this->templateHooks( $output );
		
        //-----------------------------------------
        // Check for SQL Debug
        //-----------------------------------------
        
        $this->_checkSQLDebug();
		
		//-----------------------------------------
		// Print it...
		//-----------------------------------------
		
		$this->outputFormatClass->printHeader();
		
		/* Remove unused hook comments */
		$output = preg_replace( "#<!--hook\.([^\>]+?)-->#", '', $output );
		
		/* Insert stats */
		$output = str_replace( '<!--DEBUG_STATS-->', $this->outputFormatClass->html_showDebugInfo(), $output );
		
		print $output;
		
		IPSDebug::setMemoryDebugFlag( "Output sent", $_NOW );
		
		$this->outputFormatClass->finishUp();
		
        exit;
    }

	/**
	 * Replace macros
	 * Left here as a reference 'cos other functions
	 * call it.. Must fix all that up at some point
	 *
	 * @access	public
	 * @param	string		Text
	 * @param	string		Parsed text
	 * @see		parseIPSTags
	 */
	public function replaceMacros( $text )
	{
		return $this->outputFormatClass->parseIPSTags( $text );
	}
    
    /**
	 * Print a redirect screen
	 * Wrapper function, really
	 *
	 * @access	public
	 * @param	string		Text to display on the redirect screen
	 * @param	string		URL to direct to
	 * @param	string		SEO Title
	 * @return	string		HTML to browser and exits
	 */
    public function redirectScreen( $text="", $url="", $seoTitle="" )
    {
		//-----------------------------------------
        // INIT
        //-----------------------------------------

		$this->_sendOutputSetUp( 'redirect' );
		
		//-----------------------------------------
		// Forcing silent redirects?
		//-----------------------------------------
		
		if ( $this->settings['ipb_remove_redirect_pages'] == 1 )
    	{
    		$this->silentRedirect( $url );
    	}

		//-----------------------------------------
		// Gather output
		//-----------------------------------------
		
        $output = $this->outputFormatClass->fetchOutput( $this->_html, $this->_title, $this->_navigation, $this->_documentHeadItems, $this->_jsLoader, array( 'url' => $url, 'text' => $text, 'seoTitle' => $seoTitle ) );
		
		$output = $this->templateHooks( $output );
		
        //-----------------------------------------
        // Check for SQL Debug
        //-----------------------------------------
        
        $this->_checkSQLDebug();
		
		//-----------------------------------------
		// Print it...
		//-----------------------------------------
		
		$this->outputFormatClass->printHeader();
		
		/* Remove unused hook comments */
		$output = preg_replace( "#<!--hook\.([^\>]+?)-->#", '', $output );		
		
		print $output;
		
		$this->outputFormatClass->finishUp();
		
        exit;
    }
    
    /**
	 * Displays a pop up window
	 *
	 * @access	public
	 * @param	string		Data to output (HTML, for example)
	 * @return	void		Prints data to browser and exits
	 */
	public function popUpWindow( $output )
    {
		//-----------------------------------------
        // INIT
        //-----------------------------------------
        
		$this->_sendOutputSetUp( 'popup' );

		//-----------------------------------------
		// Gather output
		//-----------------------------------------

        $output = $this->outputFormatClass->fetchOutput( $output, $this->_title, $this->_navigation, $this->_documentHeadItems, $this->_jsLoader );

		$output = $this->templateHooks( $output );
		
        //-----------------------------------------
        // Check for SQL Debug
        //-----------------------------------------
        
        $this->_checkSQLDebug();
		
		//-----------------------------------------
		// Print it...
		//-----------------------------------------
		
		$this->outputFormatClass->printHeader();
		
		/* Remove unused hook comments */
		$output = preg_replace( "#<!--hook\.([^\>]+?)-->#", '', $output );			
		
		print $output;
		
		$this->outputFormatClass->finishUp();
		
        exit;
    } 
    
	/**
	 * Immediate redirect
	 *
	 * @access	public
	 * @param	string		URL to redirect to
	 * @param	string		SEO Title
	 * @param	boolean		Send a 301 header first (Moved Permanently)
	 * @return	mixed
	 */
	public function silentRedirect( $url, $seoTitle='', $send301=FALSE )
	{
		return $this->outputFormatClass->silentRedirect( $url, $seoTitle, $send301 );
	}
	
	
	/**
	 * Build up page span links
	 * Example:
	 * <code>
	 *	$pages = $this->generatePagination( array( 'totalItems'         => ($this->topic['posts']+1),					# The total number of items (posts, topics, etc)
	 *											   'itemsPerPage'       => $this->settings['display_max_posts'],		# Number of items per page
	 *											   'currentStartValue'  => $this->request['start'],						# The current 'start' value (usually 'st')
	 *											   'baseUrl'            => "showtopic=".$this->topic['tid'].$hl,		# The URL to which the st= is attached
	 * 											   'dotsSkip'           => 2,											# Number of pages to show per section( either side of current), IE: 1 ... 4 5 [6] 7 8 ... 10
	 *											   'noDropdown'         => true,										# Don't add the 'jump to page' dropdown
	 *											   'startValueKey'      => 'start') );									# The st=x element if not 'st'.
	 * </code>
	 *
	 * @access	public
	 * @param	array	Page data
	 * @return	string	Parsed page links HTML
	 * @since	2.0
	 */
	public function generatePagination($data)
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		$work = array();
		
		$data['dotsSkip']			= isset($data['dotsSkip'])       ? $data['dotsSkip'] : '';
		$data['noDropdown']			= isset($data['noDropdown'])     ? intval( $data['noDropdown'] ) : 0;
		$data['startValueKey']		= isset($data['startValueKey'])	 ? $data['startValueKey']	 : '';
		$data['currentStartValue']	= isset( $data['currentStartValue'] ) ? $data['currentStartValue'] : $this->request['st'];
		$data['dotsSkip']			= ! $data['dotsSkip']            ? 2    : $data['dotsSkip'];
		$data['startValueKey']		= ! $data['startValueKey']       ? 'st' : $data['startValueKey'];
		$data['seoTitle']			= isset( $data['seoTitle'] )     ? $data['seoTitle'] : '';
		$data['uniqid']				= substr( str_replace( array( ' ', '.' ), '', uniqid( microtime(), true ) ), 0, 10 );
		
		//-----------------------------------------
		// Get the number of pages
		//-----------------------------------------
		
		if ( $data['totalItems'] > 0 )
		{
			$work['pages'] = ceil( $data['totalItems'] / $data['itemsPerPage'] );
		}
		
		$work['pages'] = isset( $work['pages'] ) ? $work['pages'] : 1;
		
		//-----------------------------------------
		// Set up
		//-----------------------------------------
		
		$work['total_page']   = $work['pages'];
		$work['current_page'] = $data['currentStartValue'] > 0 ? ($data['currentStartValue'] / $data['itemsPerPage']) + 1 : 1;
		
		//-----------------------------------------
		// Loppy loo
		//-----------------------------------------
		$work['_pageNumbers'] = array();
		
		if ($work['pages'] > 1)
		{
			for( $i = 0, $j = $work['pages'] - 1; $i <= $j; ++$i )
			{
				$RealNo = $i * $data['itemsPerPage'];
				$PageNo = $i+1;
				
				if ( $PageNo < ($work['current_page'] - $data['dotsSkip']) )
				{
					# Instead of just looping as many times as necessary doing nothing to get to the next appropriate number, let's just skip there now
					$i = $work['current_page'] - $data['dotsSkip'] - 2;
					continue;
				}
				
				if ( $PageNo > ($work['current_page'] + $data['dotsSkip']) )
				{
					$work['_showEndDots'] = 1;
					# Page is out of range... 
					break;
				}
				
				$work['_pageNumbers'][ $RealNo ] = ceil( $PageNo );
			}
		}
		
		if ( $work['pages'] > 1 AND $work['current_page'] > 1 )
		{
			$this->outputFormatClass->_current_page_title = $work['current_page'];
		}
		
		/**
		 * Meta data for certain browsers
		 */
		if( $work['current_page'] > 1 )
		{
			$this->addToDocumentHead( 'raw', "<link rel='first' href='" . $this->buildSEOUrl( $data['baseUrl'] . '&amp;' . $data['startValueKey'] . '=0', 'public', $data['seoTitle'], $data['seoTemplate'] ) . "' />" );
			$this->addToDocumentHead( 'raw', "<link rel='prev' href='" . $this->buildSEOUrl( $data['baseUrl'] . '&amp;' . $data['startValueKey'] . '=' . (intval( $data['currentStartValue'] - $data['itemsPerPage'] )), 'public', $data['seoTitle'], $data['seoTemplate'] ) . "' />" );
		}
		
		if( $work['current_page'] < $work['pages'] )
		{
			$this->addToDocumentHead( 'raw', "<link rel='next' href='" . $this->buildSEOUrl( $data['baseUrl'] . '&amp;' . $data['startValueKey'] . '=' . (intval( $data['currentStartValue'] + $data['itemsPerPage'] )), 'public', $data['seoTitle'], $data['seoTemplate'] ) . "' />" );
			$this->addToDocumentHead( 'raw', "<link rel='last' href='" . $this->buildSEOUrl( $data['baseUrl'] . '&amp;' . $data['startValueKey'] . '=' . (intval( ( $work['pages'] - 1 ) * $data['itemsPerPage'] )), 'public', $data['seoTitle'], $data['seoTemplate'] ) . "' />" );
		}
		
		return $this->getTemplate('global')->paginationTemplate( $work, $data );
	}
	
	/**
	 * Process remap data
	 * For use with IN_DEV
	 *
	 * @access	public
	 * @param	boolean		Override IN_DEV flag and load anyway
	 * @return 	array 		Array of remap data
	 */
	public function buildRemapData( $FORCE=FALSE )
	{
		$remapData = array();
		
		if ( ( IN_DEV or $FORCE ) and file_exists( DOC_IPS_ROOT_PATH . 'cache/skin_cache/masterMap.php' ) )
		{
			require( DOC_IPS_ROOT_PATH . 'cache/skin_cache/masterMap.php' );
			
			if ( is_array( $REMAP ) )
			{
				/* Master skins */
				foreach( array( 'templates', 'css' ) as $type )
				{
					foreach( $REMAP[ $type ] as $id => $dir )
					{
						if ( preg_match( "#^[a-zA-Z]#", $id ) )
						{
							/* we're using a key */
							$_skin = $this->_fetchSkinByKey( $id );
							
							$remapData[ $type ][ $_skin['set_id'] ] = $dir;
						}
						else
						{
							/* ID */
							$remapData[ $type ][ $id ] = $dir;
						}
					}
				}
				
				/* IN DEV default */
				if ( preg_match( "#^[a-zA-Z]#", $REMAP['inDevDefault'] ) )
				{
					$_skin = $this->_fetchSkinByKey( $REMAP['inDevDefault'] );
					
					$remapData['inDevDefault'] = $_skin['set_id'];
				}
				else
				{
					$remapData['inDevDefault'] = $REMAP['inDevDefault'];
				}
				
				/* IN DEV export */
				foreach( $REMAP['export'] as $id => $key )
				{
					if ( preg_match( "#^[a-zA-Z]#", $key ) )
					{
						$_skin = $this->_fetchSkinByKey( $key );
					
						$remapData['export'][ $id ] = $_skin['set_id'];
					}
					else
					{
						$remapData['export'][ $id ] = $id;
					}
				}
			}
		}
		else
		{
			$remapData = array( 'templates'    => array( 0 => 'master_skin' ),
								'css'	       => array( 0 => 'master_css' ),
								'inDevDefault' => 0 );
		}
		
		return $remapData;
	}
	
	/**
	 * Fetch a skin set via a key
	 *
	 * @access	private
	 * @param	string		Skin set key
	 * @return	array 		Array of skin data
	 */
	public function _fetchSkinByKey( $key )
	{
		foreach( $this->allSkins as $_id => $_data )
		{
			if ( $_data['set_key'] == $key )
			{
				return $_data;
			}
		}
		
		return array();
	}
	
	/**
	 * Show error message
	 *
	 * @example	$this->registry->output->showError( 'no_permission' );
	 * @example	$this->registry->output->showError( 'hack_attempt', 505, TRUE );
	 * @example	$this->registry->output->showError( array( 'Registration Error: %s', 'No password' ), 0, TRUE );
	 * @access	public
	 * @param	mixed		Array if there is data to replace in the message string, or string message or key for error lang file
	 * @param	integer		Error code
	 * @param	boolean		Log error (use for possible hack attempts, fiddling, etc )
	 * @param   string      Additional data to log, but not display to the user
	 * @return	void
	 * @since	3.0.0
	 */
    public function showError( $message, $code=0, $logError=FALSE, $logExtra='' )
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
		$msg   = "";
		$extra = "";
		$this->registry->getClass('class_localization')->loadLanguageFile( array( "public_error" ), 'core' );

    	//-----------------------------------------
    	// Error Message
    	//-----------------------------------------
		
		if ( is_array( $message ) )
		{
			$msg	= $message[0];
			$extra	= $message[1];
		}
		else
		{
			$msg	= $message;
		}
		
    	$msg = ( isset($this->lang->words[ $msg ]) ) ? $this->lang->words[ $msg ] : $msg;
    		
    	if ( $extra )
    	{
    		$msg = sprintf( $msg, $extra );
    	}
		
		//-----------------------------------------
    	// Update session
    	//-----------------------------------------
		
		$this->member->updateMySession( array( 'in_error' => 1 ) );
		
		//-----------------------------------------
    	// Log all errors above set level?
    	//-----------------------------------------
    	
    	if( $code )
    	{
    		if( $this->settings['error_log_level'] )
    		{
    			$level = substr( $code, 0, 1 );

				if( $this->settings['error_log_level'] == 1 )
				{
					$logError = true;
				}
				else if( $level > 1 )
				{
					if( $level >= $this->settings['error_log_level'] - 1 )
					{
						$logError = true;
					}
				}
			}
    	}

		//-----------------------------------------
    	// Log the error, if needed
    	//-----------------------------------------
    	
		if( $logError )
		{
			$this->logErrorMessage( $msg . '<br /><br />' . $logExtra, $code );
		}
		
		//-----------------------------------------
    	// Send notification if needed
    	//-----------------------------------------
    	
    	$this->sendErrorNotification( $msg, $code );
		
		//-----------------------------------------
		// Send to output engine
		//-----------------------------------------

        $this->addContent( $this->outputFormatClass->displayError( $msg, $code ) );
		$this->setTitle( $this->lang->words['board_offline_title'] );
		$this->sendOutput();
		
        exit;
    }

	/**
	 * Show board offline message
	 *
	 * @access	public
	 * @return	void
	 * @since	2.0
	 */
    public function showBoardOffline()
    {
    	//-----------------------------------------
    	// Get offline message (not cached)
    	//-----------------------------------------
    	
    	if( !$this->offlineMessage )
    	{
	    	$row = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_sys_conf_settings', 'where' => "conf_key='offline_msg'" ) );
	    	
	    	$this->registry->getClass( 'class_localization')->loadLanguageFile( array( "public_error" ), 'core' );
	    	
	    	$this->offlineMessage = $row['conf_value'];
    	}
    	
    	//-----------------------------------------
    	// Parse the bbcode
    	//-----------------------------------------
    	
		IPSText::getTextClass('bbcode')->parse_bbcode		= 1;
		IPSText::getTextClass('bbcode')->parse_html			= 0;
		IPSText::getTextClass('bbcode')->parse_emoticons	= 1;
		IPSText::getTextClass('bbcode')->parse_nl2br		= 1;
		IPSText::getTextClass('bbcode')->parsing_section	= 'global';
		
		$this->offlineMessage = IPSText::getTextClass('bbcode')->preDisplayParse( $this->offlineMessage );

		//-----------------------------------------
		// Send to output engine
		//-----------------------------------------

        $this->addContent( $this->outputFormatClass->displayBoardOffline( $this->offlineMessage ) );
		$this->setTitle( $this->lang->words['board_offline_title'] );
		$this->sendOutput();
		
        exit;
    }
	
	/**
	 * Check if SQL debug is on, if so add the SQL debug data
	 *
	 * @access	protected
	 * @return	void
	 * @since	2.0
	 */
    protected function _checkSQLDebug()
    {
    	if ($this->DB->obj['debug'])
        {
        	flush();
        	print "<html><head><title>SQL Debugger</title><body bgcolor='white'><style type='text/css'> TABLE, TD, TR, BODY { font-family: verdana,arial, sans-serif;color:black;font-size:11px }</style>";
        	print "<h1 align='center'>SQL Total Time: {$this->DB->sql_time} for {$this->DB->query_count} queries</h1><br />".$this->DB->debug_html;
        	print "<br /><div align='center'><strong>Total SQL Time: {$this->DB->sql_time}</div></body></html>";
        	
			print "<br />SQL Fetch Total Memory: " . IPSLib::sizeFormat( $this->DB->_tmpT, TRUE );
			$this->outputFormatClass->finishUp();
			exit();
        }
    }
    
	/**
	 * Check for IPS Report.  Prints XML file if called properly.  No longer used, really.
	 *
	 * @access	protected
	 * @return	void
	 * @since	2.0
	 * @deprecated	Consider removing this.  We no longer use this.
	 */
    protected function _checkIPSReport()
    {
    	//-----------------------------------------
		// Note, this is designed to allow IPS validate boards
		// who've purchased copyright removal / registration.
		// The order number is the only thing shown and the
		// order number is unique to the person who paid and
		// is no good to anyone else.
		// Showing the order number poses no risk at all -
		// the information is useless to anyone outside of IPS.
		//-----------------------------------------
		
		$pass		     = 0;
		$key 		     = isset( $this->request['key'] ) ? trim( $this->request['key'] ) : '';
		$cust_number     = 0;
		$acc_number  	 = 0;
		$cust_number_tmp = '0,0';
		
		if ( (isset($this->request['ipsreport']) AND $this->request['ipsreport']) or (isset( $this->request['ipscheck'] ) AND $this->request['ipscheck']) )
		{
			if ( $this->settings['ipb_copy_number'] )
			{
				$cust_number_tmp = preg_replace( "/^(\d+?)-(\d+?)-(\d+?)-(\S+?)$/", "\\2,\\3", $this->settings['ipb_copy_number'] );
			}
			else if ( $this->settings['ipb_reg_number'] )
			{
				$cust_number_tmp = preg_replace( "/^(\d+?)-(\d+?)-(\d+?)-(\d+?)-(\S+?)$/", "\\2,\\4", $this->settings['ipb_reg_number'] );
			}
			
			if ( md5($key) == '23f2554a507f6d52b8f27934d3d2a88d' )
			{
				$latest_version = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'upgrade_history', 'order' => 'upgrade_version_id DESC', 'limit' => array(0, 1) ) );
   				
   				if ( ipsRegistry::$version == 'v<{%dyn.down.var.human.version%}>' )
				{
					ipsRegistry::$version = 'v'.$latest_version['upgrade_version_human'];
				}
				
				if ( ipsRegistry::$acpversion == '<{%dyn.down.var.long_version_id%}>' )
				{
					ipsRegistry::$acpversion = $latest_version['upgrade_version_id'];
				}
		
				list( $cust_number, $acc_number ) = explode( ',', $cust_number_tmp );
				
				@header( "Content-type: text/xml" );
				$out  = '<?xml version="1.0" encoding="ISO-8859-1"?'.'>';
				$out .= "\n<ipscheck>\n\t<result>1</result>\n\t<customer_id>$cust_number</customer_id>\n\t<account_id>$acc_number</account_id>\n\t"
					 .  "<version_id>{ipsRegistry::$acpversion}</version_id>\n\t<version_string>{ipsRegistry::$version}</version_string>\n\t<release_hash><![CDATA[<{%dyn.down.var.md5%}]]>></release_hash>"
					 .  "\n</ipscheck>";
				print $out;
				exit();
			}
			else if( md5($key) == 'd66ab5043c553f1f1fd5fad3ece252e3' )
			{
				$latest_version = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'upgrade_history', 'order' => 'upgrade_version_id DESC', 'limit' => array(0, 1) ) );
   				
   				if ( ipsRegistry::$version == 'v<{%dyn.down.var.human.version%}>' )
				{
					ipsRegistry::$version = 'v'.$latest_version['upgrade_version_human'];
				}
				
				if ( ipsRegistry::$acpversion == '<{%dyn.down.var.long_version_id%}>' )
				{
					ipsRegistry::$acpversion = $latest_version['upgrade_version_id'];
				}
				
				@header( "Content-type: text/plain" );
				print ipsRegistry::$version.' (ID:'.ipsRegistry::$acpversion.')';
				exit();				
			}				
			else
			{
				@header( "Content-type: text/plain" );
				print "<result>0</result>\nYou do not have permission to view this page.";
				exit();
			}
        }	
    }
    
	/**
     * Runs all the registered hooks for the loaded template groups
     *
     * @access	public
     * @param	string		$text
     * @return	string
     */
    public function templateHooks( $text )
    {
    	/* Hook Output */
    	$hook_output = array();
    	
    	/* Get a list of skin groups */
    	$skin_groups = array();
    	
    	foreach( $this->compiled_templates as $group => $tpl )
    	{
    		$skin_groups[] = $group;
    	}
    	
    	/* Loop through the cache */
    	$hooksCache = ipsRegistry::cache()->getCache( 'hooks' );

		if( is_array( $hooksCache['templateHooks'] ) AND count( $hooksCache['templateHooks'] ) )
		{
			foreach( $hooksCache['templateHooks'] as $hook )
			{
				foreach( $hook as $tplHook )
				{
					/* Check to see if the group is loaded */
					if( ! in_array( $tplHook['skinGroup'], $skin_groups ) )
					{
						continue;
					}
	
					/* Check for hook file */
					if( file_exists( DOC_IPS_ROOT_PATH . 'hooks/' . $tplHook['filename'] ) )
					{
						/* Check for hook class */
						require_once( DOC_IPS_ROOT_PATH . 'hooks/' . $tplHook['filename'] );
						
						if( class_exists( $tplHook['className'] ) )
						{
							/* INIT */
							$arr_key = $tplHook['type'] . '.' . $tplHook['skinGroup'] . '.' . $tplHook['skinFunction'] . '.' . $tplHook['id'] . '.' . $tplHook['position'];
							
							if( ! isset( $hook_output[ $arr_key ] ) )
							{
								$hook_output[ $arr_key ] = '';
							}
							
							/* Create and run the hook */
							$_hook = new $tplHook['className'];
							
							$hook_output[ $arr_key ] .= $_hook->getOutput();
						}
					}
				}
			}
		}
		
		if( count( $hook_output ) )
		{
			foreach( $hook_output as $hook_location => $hook_content )
			{
				$text = str_replace( '<!--hook.' . $hook_location . '-->', '<!--hook.' . $hook_location . '-->' . $this->replaceMacros( $hook_content ), $text );
			}
		}
		
		return $text;
    }

    /**
	 * Check if there is a skin hook registered here and 
	 * if so overload the skin file with this hook
	 *
	 * @access	protected
	 * @param	string		Skin group name
	 * @param	string		Class name
	 * @param	integer		Skin ID
	 * @return	string		Class name to instantiate
	 */
    protected function _getSkinHooks( $name, $classname, $id )
    {
		/* Hooks: Are we overloading this class? */
		$hooksCache	= ipsRegistry::cache()->getCache('hooks');
		
		if( isset( $hooksCache['skinHooks'] ) && is_array( $hooksCache['skinHooks'] ) && count( $hooksCache['skinHooks'] ) )
		{
			foreach( $hooksCache['skinHooks'] as $hook )
			{
				foreach( $hook as $classOverloader )
				{
					/* Hooks: Do we have a hook that extends this class? */
					
					if( $classOverloader['classToOverload'] == $name )
					{
						if( file_exists( DOC_IPS_ROOT_PATH . 'hooks/' . $classOverloader['filename'] ) )
						{
							if( !class_exists( $classOverloader['className'] ) )
							{
								/* Hooks: Do we have the hook file? */
								
								$thisContents = file_get_contents( DOC_IPS_ROOT_PATH . 'hooks/' . $classOverloader['filename'] );
								$thisContents = str_replace( "(~id~)", "_{$id}", $thisContents );
								
								ob_start();
								eval( $thisContents );
								ob_end_clean();
							}

							if( class_exists( $classOverloader['className'] ) )
							{
								/* Hooks: We have the hook file and the class exists - reset the classname to load */
								
								$classname = $classOverloader['className'];
							}
						}
					}
				}
			}
		}

		return $classname;
    }

	/**
	 * Destruct
	 *
	 * @access	public
	 * @return	void
	 */
	public function __destruct()
	{
		//-----------------------------------------
		// Make sure only this class calls this
		//-----------------------------------------
		
		if ( get_class( $this ) != 'output' )
		{
			return;
		}
	}
	
	/**
	 * Log error messages to the error logs table
	 *
	 * @access	protected
	 * @param	string		Error message
	 * @param	integer		Error code
	 * @return	void
	 */
	protected function logErrorMessage( $message, $code=0 )
	{
		$toInsert	= array(
							'log_member'		=> $this->member->getProperty('member_id'),
							'log_date'			=> time(),
							'log_error'			=> $message,
							'log_error_code'	=> $code,
							'log_ip_address'	=> $this->member->ip_address,
							'log_request_uri'	=> my_getenv('REQUEST_URI'),
							);

		$this->DB->insert( 'error_logs', $toInsert );
	}
	
	/**
	 * Determine if notification needs to be sent, and send it
	 *
	 * @access	protected
	 * @param	string		Error message
	 * @param	integer		Error code
	 * @return	boolean		Email sent or not
	 */
	protected function sendErrorNotification( $message, $code=0 )
	{
		if( !$this->settings['error_log_notify'] )
		{
			return false;
		}
		
		if( $this->settings['error_log_notify'] > 1 )
		{
			$level = substr( $code, 0, 1 );
	
			if( $this->settings['error_log_notify'] > 1 )
			{
				if( $level < $this->settings['error_log_notify'] - 1 )
				{
					return false;
				}
			}
		}
		
		//-----------------------------------------
		// Still here?  Send email then.
		//-----------------------------------------
		
		IPSText::getTextClass( 'email' )->getTemplate( "error_log_notification" );

		IPSText::getTextClass( 'email' )->buildMessage( array( 
																'CODE'			=> $code,
																'MESSAGE'		=> $message,
																'VIEWER'		=> $this->member->getProperty('member_id') ? $this->member->getProperty('members_display_name') : $this->lang->words['global_guestname'],
																'IP_ADDRESS'	=> $this->member->ip_address,
														)		);

		IPSText::getTextClass( 'email' )->to		= $this->settings['email_in'];
		IPSText::getTextClass( 'email' )->from		= $this->settings['email_out'];
		IPSText::getTextClass( 'email' )->sendMail();
		
		return true;
	}
	
        
}