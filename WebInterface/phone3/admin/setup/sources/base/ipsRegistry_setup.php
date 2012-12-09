<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Registry class for setup
 * Last Updated: $Date: 2009-07-27 11:47:47 -0400 (Mon, 27 Jul 2009) $
 *
 * @author 		Matt Mecham
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @since		1st December 2008
 * @version		$Revision: 4944 $
 *
 */

class ipsRegistry
{
	/**
	 * Stored values
	 *
	 * @access	private
	 * @var		array
	 */
	private $values				= array();

	/**
	 * Registry instance
	 *
	 * @access	private
	 * @var		object
	 */
	private static $instance;

	/**
	 * Are we initiated
	 *
	 * @access	private
	 * @var		bool
	 */
	private static $initiated	= FALSE;

	/**
	 * Singleton handles
	 *
	 * @access	private
	 * @var		array
	 */
	private static $handles		= array();

	/**#@+
	 * Holds core variable data
	 *
	 * @access	private
	 * @var		array
	 */
	private static $_coreVariables			= array();
	private static $_masterCoreVariables	= array();
	/**#@-*/

	/**
	 * Stored object references
	 *
	 * @access	private
	 * @var		array
	 */
	private static $classes	= array();

	/**
	 * Global config data
	 *
	 * @access	public
	 * @var		array
	 */
	static public $_config	= array();

	/**#@+
	 * Stored URLs
	 *
	 * @access	public
	 * @var		mixed	Array, string
	 */
	static public $urls				= array();
	static public $processed_url	= '';
	/**#@-*/

	/**
	 * Server load
	 *
	 * @access	public
	 * @var		string
	 */
	static public $server_load;

	/**#@+
	 * Version numbers
	 *
	 * @access	public
	 * @var		string
	 */
	static public $version			= IPB_VERSION;
	static public $acpversion		= IPB_LONG_VERSION;
	static public $vn_full			= '';
	static public $vn_build_date	= '';
	static public $vn_build_reason	= '';
	/**#@-*/

	/**#@+
	 * Applications and modules
	 *
	 * @access	public
	 * @var		mixed		Array, string
	 */
	static public $applications			= array();
	static public $modules				= array();
	static public $modules_by_section	= array();
	static public $current_application	= '';
	static public $current_module		= '';
	static public $current_section		= '';
	/**#@-*/

	/**
	 * Application class vars
	 *
	 * @access	public
	 * @var		array
	 */
	static public $app_class	= array();

	/**
	 * Settings
	 *
	 * @access	public
	 * @var		array
	 */
	static public $settings		= array();

	/**
	 * Request params
	 *
	 * @access	public
	 * @var		array
	 */
	static public $request		= array();

	/**
	 * Singleton init
	 *
	 * @access	public
	 * @return	object		Reference to ourself
	 */
	static public function instance()
	{
		if ( ! self::$instance )
		{
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Custom destructor
	 *
	 * @access	public
	 * @return	void
	 */
	static public function __myDestruct()
	{
		foreach( self::$handles as $name => $obj )
		{
			if ( method_exists( $obj, '__myDestruct' ) )
			{
				$obj->__myDestruct();
			}
		}
	}

	/**
	 * Initiate the registry
	 *
	 * @access	public
	 * @return	void
	 */
	public static function init()
	{
		if ( self::$initiated === TRUE )
		{
			return FALSE;
		}

		self::$initiated = TRUE;

		/* Load static classes */
		require IPS_ROOT_PATH . "sources/base/core.php";
		require IPS_ROOT_PATH . "setup/sources/base/setup.php";

		/* Load conf global and set up DB */
		if ( IPS_IS_UPGRADER )
		{
			if ( ! file_exists( DOC_IPS_ROOT_PATH . "conf_global.php" ) )
			{
				print "Cannot locate: " . DOC_IPS_ROOT_PATH . "conf_global.php";
				exit();
			}

			self::loadConfGlobal();

			/* Got settings? */
			if ( ! ipsRegistry::$settings['sql_driver'] )
			{
				print "Settings not loaded from: " . DOC_IPS_ROOT_PATH . "conf_global.php - did you mean to install?";
				exit();
			}

			self::setDBHandle();
		}
		else
		{
			/* Ensure char set is defined */
			if( ! defined( 'IPS_DOC_CHAR_SET' ) )
			{
				define( 'IPS_DOC_CHAR_SET', strtoupper( IPSSetUp::charSet ) );
			}
			
			if ( ! defined('IPS_CACHE_PATH') )
			{
				define( 'IPS_CACHE_PATH', DOC_IPS_ROOT_PATH );
			}

			require IPS_ROOT_PATH . "setup/sources/base/install.php";
		}

		/* Input set up... */
		if ( is_array( $_POST ) and count( $_POST ) )
		{
			foreach( $_POST as $key => $value )
			{
				# Skip post arrays
				if ( ! is_array( $value ) )
				{
					$_POST[ $key ] = IPSText::stripslashes( $value );
				}
			}
		}

    	//-----------------------------------------
    	// Clean globals, first.
    	//-----------------------------------------

		IPSLib::cleanGlobals( $_GET );
		IPSLib::cleanGlobals( $_POST );
		IPSLib::cleanGlobals( $_COOKIE );
		IPSLib::cleanGlobals( $_REQUEST );

		# GET first
		$input = IPSLib::parseIncomingRecursively( $_GET, array() );

		# Then overwrite with POST
		self::$request = IPSLib::parseIncomingRecursively( $_POST, $input );

		# Assign request method
		self::$request['request_method'] = strtolower( my_getenv('REQUEST_METHOD') );

		self::_setUpAppData();

		/* Get caches */
		self::$handles['caches']   = ips_CacheRegistry::instance();

		if ( IPS_IS_UPGRADER )
		{
			/* Make sure all is well before we proceed */
			self::instance()->setUpSettings();

			/* Build module and application caches */
			self::instance()->checkCaches();

			/* Load 'legacy' systems */
			$file = '';

			if ( IPSSetUp::is300plus() === TRUE )
			{
				$file = '3xx.php';
			}
			else if ( IPSSetUp::is200plus() === TRUE )
			{
				$file = '2xx.php';
			}
			else
			{
				$file = '1xx.php';
			}

			require_once( IPS_ROOT_PATH . 'setup/sources/legacy/' . $file );
			self::instance()->setClass( 'legacy', new upgradeLegacy( self::instance() ) );
		}

		/* Set up member */
		self::$handles['member']   = ips_MemberRegistry::instance();

		# Thaw saved data
		IPSSetUp::thawSavedData();

		/* Gather other classes */
		require_once( IPS_ROOT_PATH . 'setup/sources/classes/output/output.php' );

		self::instance()->setClass( 'output', new output( self::instance(), TRUE ) );

		# Fetch global config
		if ( self::readGlobalConfig() === FALSE )
		{
			self::getClass('output')->addError( "Could not load config.xml" );
		}

		if ( IPS_IS_UPGRADER )
		{
			/* Check session status */
			$validationStatus  = self::member()->sessionClass()->getStatus();
			$validationMessage = self::member()->sessionClass()->getMessage();

			if ( ( self::$request['section'] AND self::$request['section'] != 'index' ) AND ( ! $validationStatus ) )
			{
				/* Force log in */
				self::getClass('output')->setTitle( "Upgrader: Error" );
				self::getClass('output')->setNextAction( '' );
				self::getClass('output')->addContent( self::getClass('output')->template()->page_error( $validationMessage ) );
				self::getClass('output')->sendOutput();
				exit();
			}
		}
		else
		{
			# Installer locked?
			if ( file_exists( DOC_IPS_ROOT_PATH . 'cache/installer_lock.php' ) )
			{
				self::getClass('output')->setTitle( "Installer: Error" );
				self::getClass('output')->setNextAction( '' );
				self::getClass('output')->addContent( self::getClass('output')->template()->page_locked() );
				self::getClass('output')->sendOutput();
				exit();
			}
		}
	}

	/**
	 * Load conf_global
	 *
	 * @access	public
	 * @return	void
	 */
	static public function loadConfGlobal()
	{
		/* Load config file */
		if ( file_exists( DOC_IPS_ROOT_PATH . "conf_global.php" ) )
		{
			require DOC_IPS_ROOT_PATH . "conf_global.php";

			if ( is_array( $INFO ) )
			{
				foreach( $INFO as $key => $val )
				{
					ipsRegistry::$settings[ $key ]	= $val;
				}
			}
		}
	}

	/**
	 * Set database handle
	 *
	 * @access	public
	 * @return	void
	 */
	static public function setDBHandle()
	{
		self::$handles['db'] = ips_DBRegistry::instance();
		self::$handles['db']->setDB( ipsRegistry::$settings['sql_driver'] );
	}

	/**
	 * Auto loads next action
	 *
	 * @access	public
	 * @param	string		Section key
	 * @return	void
	 */
	static public function autoLoadNextAction( $section )
	{
		self::getClass('output')->currentPage = $section;
		self::$request['do'] = '';

		$_class = IPS_APP_COMPONENT . '_' . $section;

		require_once( IPS_ROOT_PATH . 'setup/applications/' . IPS_APP_COMPONENT . '/sections/' . $section . '.php' );
		$action = new $_class();
		$action->makeRegistryShortcuts( self::instance() );
		$action->doExecute( self::instance() );
		return;
	}

	/**
	 * Read conf_global
	 *
	 * @access	public
	 * @return	bool
	 */
	static public function readGlobalConfig()
	{
		/* Fetch core writeable files */
		require_once( IPS_KERNEL_PATH . 'classXML.php' );
		$xml    = new classXML( IPSSetUp::charSet );

		try
		{
			$xml->load( IPS_ROOT_PATH . 'setup/xml/config.xml' );

			foreach( $xml->fetchElements( 'package' ) as $xmlelement )
			{
				$data = $xml->fetchElementsFromRecord( $xmlelement );

				self::$_config = $data;
			}

			return TRUE;
		}
		catch( Exception $error )
		{
			return FALSE;
		}
	}

	/**
	 * Dummy function to keep other code quiet
	 */
	static public function fetchBitWiseOptions()
	{
		return FALSE;
	}

	/**
	 * Fetch global config data as array
	 *
	 * @access	public
	 * @return	array
	 */
	static public function fetchGlobalConfigAsArray()
	{
		return is_array( self::$_config ) ? self::$_config : array();
	}

	/**
	 * Fetch global config value
	 *
	 * @access	public
	 * @param	string		Key
	 * @return	string
	 */
	static public function fetchGlobalConfigValue( $key )
	{
		return isset( self::$_config[ $key ] ) ? self::$_config[ $key ] : FALSE;
	}

	/**
	 * Loads application's core variables (if required)
	 *
	 * @access	public
	 * @param	string		App key (dir.. that's directory, not duuhr)
	 * @return	void
	 */
	static public function _loadCoreVariables()
	{
		if ( ! is_object( self::$_masterCoreVariables['__CLASS__'] ) )
		{
			require_once( IPS_ROOT_PATH . 'extensions/coreVariables.php' );

			/* Add handle */
			self::$_masterCoreVariables['__CLASS__'] = new coreVariables();
		}
	}

	/**
	 * Fetches apps core variable data
	 *
	 * @access	public
	 * @param	string		Type of variable to return
	 * @return	string
	 */
	public static function _fetchCoreVariables( $type )
	{
		if ( ! is_array( self::$_masterCoreVariables[ $type ] ) )
		{
			self::_loadCoreVariables();

			switch( $type )
			{
				case 'cache':
				case 'cacheload':
					$return = self::$_masterCoreVariables['__CLASS__']->fetchCaches();
					self::$_masterCoreVariables['cache']     = is_array( $return['caches'] )    ? $return['caches'] : array();
					self::$_masterCoreVariables['cacheload'] = is_array( $return['cacheload'] ) ? $return['cacheload'] : array();
				break;
				case 'redirect':
					$return = self::$_masterCoreVariables['__CLASS__']->fetchRedirects();
					self::$_masterCoreVariables['redirect'] = is_array( $return ) ? $return : array();
				break;
				case 'templates':
					$return = self::$_masterCoreVariables['__CLASS__']->fetchTemplates();
					self::$_masterCoreVariables['templates'] = is_array( $return ) ? $return : array();
				break;
				case 'bitwise':
					$return = self::$_masterCoreVariables['__CLASS__']->fetchBitwise();
					self::$_masterCoreVariables['bitwise'] = is_array( $return ) ? $return : array();
				break;
			}
		}

		return self::$_masterCoreVariables[ $type ];
	}

	/**
	 * Loads application's core variables (if required)
	 *
	 * @access	private
	 * @param	string		App key (dir.. that's directory, not duuhr)
	 * @return	void
	 */
	static private function _loadAppCoreVariables( $appDir )
	{
		if ( ! isset( self::$_coreVariables[ $appDir ] ) )
		{
			if( file_exists( IPSLib::getAppDir( $appDir ) . '/extensions/coreVariables.php' ) )
			{
				require( IPSLib::getAppDir( $appDir ) . '/extensions/coreVariables.php' );

				/* Add caches */
				self::$_coreVariables[ $appDir ]['cache']     = is_array( $CACHE ) ? $CACHE : array();
				self::$_coreVariables[ $appDir ]['cacheload'] = is_array( $_LOAD ) ? $_LOAD : array();

				/* Add redirect */
				self::$_coreVariables[ $appDir ]['redirect'] = is_array( $_RESET ) ? $_RESET : array();

				/* Add bitwise */
				self::$_coreVariables[ $appDir ]['bitwise']  = is_array( $_BITWISE ) ? $_BITWISE : array();
			}
		}
	}

	/**
	 * Fetches apps core variable data
	 *
	 * @access	public
	 * @param	string		App dir
	 * @param	string		Type of variable to return
	 * @return	string
	 */
	static public function _fetchAppCoreVariables( $appDir, $type )
	{
		if ( ! is_array( self::$_coreVariables[ $appDir ][ $type ] ) )
		{
			self::_loadAppCoreVariables( $appDir );
		}

		return self::$_coreVariables[ $appDir ][ $type ];
	}

	/**
	 * Store object handles so we don't need to reinit them
	 *
	 * @access	public
	 * @param	string		Key
	 * @param	object		Value
	 * @return	void
	 */
	static public function setClass( $key='', $value='' )
	{
		self::instance()->checkForInit();

		if ( ! $key OR ! $value )
		{
			throw new Exception( "Missing a key or value" );
		}
		else if ( ! is_object( $value ) )
		{
			throw new Exception( "$value is not an object" );
		}

		self::$classes[ $key ] = $value;
	}

	/**
	 * Function for retrieving class handles
	 * AUTOLOADED classes are as follows:
	 * KERNEL -> class_captcha
	 * KERNEL -> classTemplateEngine (templateEngine)
	 * CORE   -> localization class
	 *
	 * @access	public
	 * @param	string		Key
	 * @return	object
	 */
	static public function getClass( $key )
	{
		self::instance()->checkForInit();

		/* Do some magic here to retreive common classes without
		   having to initialize them first */
		if ( ! isset( self::$classes[ $key ] ) || ! is_object( self::$classes[ $key ] ) )
		{
			switch( $key )
			{
				default:
					throw new Exception( "$key is not an object" );
				break;
				case 'class_captcha':
					require_once( IPS_KERNEL_PATH . 'classCaptcha.php' );
					self::$classes['class_captcha'] = new classCaptcha( self::instance() );
				break;
				case 'templateEngine':
					require_once( IPS_KERNEL_PATH . 'classTemplateEngine.php' );
					self::$classes['templateEngine'] = new classTemplate( IPS_ROOT_PATH . 'sources/template_plugins' );
				break;
				case 'class_localization':
					require_once( IPS_ROOT_PATH    . 'sources/classes/class_localization.php' );
					self::$classes['class_localization'] = new class_localization( self::instance() );
				break;

				/* Yes I know this is forums app and not global, but it's still called so much this is a good idea */
				case 'class_forums':
					require_once( IPSLib::getAppDir( 'forums' ) . "/sources/classes/forums/class_forums.php" );
					self::$classes['class_forums'] = new class_forums( self::instance() );
					self::$classes['class_forums']->strip_invisible	= true;
					self::$classes['class_forums']->forumsInit();
				break;
			}

			return self::$classes[ $key ];
		}
		else
		{
			return self::$classes[ $key ];
		}
	}

	/**
	 * Return a list of classes
	 * IN_DEV ONLY
	 *
	 * @access	public
	 * @return	mixed 		Array of items, or false
	 */
	public static function getLoadedClassesAsArray()
	{
		if ( ! IN_DEV )
		{
			return FALSE;
		}
		else
		{
			return array_keys( self::$classes );
		}
	}

	/**
	 * Shortcut for accessing registry objects
	 *
	 * @access	public
	 * @param	string		Key
	 * @return	object
	 */
	public function __get( $key )
	{
		self::instance()->checkForInit();

		$_class = self::getClass( $key );

		if ( is_object( $_class ) )
		{
			return $_class;
		}
	}

	/**
	 * Find out if a class is loaded
	 *
	 * @access	public
	 * @param	string	Key
	 * @return	bool
	 */
	static public function isClassLoaded( $key )
	{
		return ( isset( self::$classes[ $key ] ) && is_object( self::$classes[ $key ] ) ) ? TRUE : FALSE;
	}

	/**
	 * Get database object
	 *
	 * @access	public
	 * @param	string	DB connection key
	 * @return	object
	 */
	static public function DB( $key='' )
	{
		if ( self::$settings['sql_user'] )
		{
			self::instance()->checkForInit();
			return self::$handles['db']->getDB( $key );
		}
	}

	/**
	 * Get database functions object
	 *
	 * @access	public
	 * @param	string	DB connection key
	 * @return	object
	 */
	static public function dbFunctions( $key='' )
	{
		self::instance()->checkForInit();
		return self::$handles['db'];
	}

	/**
	 * Get cache object
	 *
	 * @access	public
	 * @return	object
	 */
	static public function cache()
	{
		self::instance()->checkForInit();
		return self::$handles['caches'];
	}

	/**
	 * Get cache object
	 *
	 * @access	public
	 * @return	array
	 * @see		fetchSettings()
	 */
	static public function settings()
	{
		self::instance()->checkForInit();
		return ipsRegistry::$settings;
	}

	/**
	 * Fetch all the settings
	 *
	 * @access	public
	 * @return	array
	 */
	static public function &fetchSettings()
	{
		return ipsRegistry::$settings;
	}

	/**
	 * Fetch request items
	 *
	 * @access	public
	 * @return	array
	 */
	static public function &fetchRequest()
	{
		return ipsRegistry::$request;
	}

	/**
	 * Fetch request items
	 *
	 * @access	public
	 * @return	array
	 * @see		fetchRequest()
	 */
	static public function request()
	{
		self::instance()->checkForInit();
		return ipsRegistry::$request;
	}

	/**
	 * Fetch member object
	 *
	 * @access	public
	 * @return	object
	 */
	static public function member()
	{
		self::instance()->checkForInit();

		if( isset( self::$handles['member'] ) )
		{
			return self::$handles['member'];
		}
	}

	/**
	 * Returns the current application
	 *
	 * @access	public
	 * @return	string
	 */
	static public function getCurrentApplication()
	{
		return self::$current_application;
	}

	/**
	 * Returns an array of applications
	 *
	 * @access	public
	 * @return	array
	 */
	public function getApplications()
	{
		return self::$applications;
	}

	/**
	 * Check to see if we are initialized
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function checkForInit()
	{
		if ( self::$initiated !== TRUE )
		{
			throw new Exception('ipsRegistry has not been initiated. Do so by calling ipsRegistry::init()' );
		}
	}



	/**
	 * Set up request redirect stuff
	 *
	 * @return	void
	 * @author	MattMecham
	 * @access	protected
	 */
	protected static function _setUpAppData()
	{
		$_default = ( IPS_IS_UPGRADER ) ? 'upgrade' : 'install';

		# Finalize the app component constants
		$_appcomponent = preg_replace( "/[^a-zA-Z0-9\-\_]/", "" , ( isset( $_REQUEST['app'] ) && trim( $_REQUEST['app'] ) ? $_REQUEST['app'] : $_default ) );
		define( 'IPS_APP_COMPONENT', ( $_appcomponent ) ? $_appcomponent : $_default );

		self::$current_section = IPSText::alphanumericalClean( $_REQUEST['section'] );
	}

	/**
	 * Set up settings
	 *
	 * @return	void
	 * @author	MattMecham
	 * @access	protected
	 */
	protected function setUpSettings()
	{
		$settings_cache = self::$handles['caches']->getCache('settings');

		if ( ! is_array( $settings_cache ) OR ! count( $settings_cache ) )
		{
			/* Ok, if we're an upgrader and we're 1.3... */
			if ( IPS_IS_UPGRADER )
			{
				$settings_cache = array();
			}
			else
			{
				throw new Exception( "Could not initiate the registry, the settings cache is empty or missing" );
			}
		}

		foreach( $settings_cache as $k => $v )
		{
			ipsRegistry::$settings[$k] = $v;
		}
		
		if ( ! defined('IPS_CACHE_PATH') )
		{
			define( 'IPS_CACHE_PATH', ( IPS_IS_UPGRADER ) ? ( ! empty( ipsRegistry::$settings['ipb_cache_path'] ) ? ipsRegistry::$settings['ipb_cache_path'] : DOC_IPS_ROOT_PATH ) :  DOC_IPS_ROOT_PATH );
		}

		//-----------------------------------------
		// Back up base URL
		//-----------------------------------------

		ipsRegistry::$settings['base_url']           = ( ipsRegistry::$settings['board_url'] ) ? ipsRegistry::$settings['board_url'] : ipsRegistry::$settings['base_url'];
		ipsRegistry::$settings['_original_base_url'] = ipsRegistry::$settings['base_url'];

		//-----------------------------------------
		// Make a safe query string
		//-----------------------------------------

		ipsRegistry::$settings['query_string_safe'] = str_replace( '&amp;amp;', '&amp;', IPSText::parseCleanValue( urldecode( my_getenv('QUERY_STRING') ) ) );
		ipsRegistry::$settings['query_string_real'] = str_replace( '&amp;'    , '&'    , ipsRegistry::$settings['query_string_safe'] );

		//-----------------------------------------
		// Format it..
		//-----------------------------------------

		ipsRegistry::$settings['query_string_formatted'] = str_replace( ipsRegistry::$settings['board_url'] . '/index.'.ipsRegistry::$settings['php_ext'].'?', '', ipsRegistry::$settings['query_string_safe'] );
		ipsRegistry::$settings['query_string_formatted'] = preg_replace( "#s=([a-z0-9]){32}#", '', ipsRegistry::$settings['query_string_formatted'] );

		//-----------------------------------------
		// Default settings
		//-----------------------------------------

		ipsRegistry::$settings['_admin_link'] = ipsRegistry::$settings['base_url'] . '/' . CP_DIRECTORY . '/index.php';
		ipsRegistry::$settings['max_user_name_length'] = ipsRegistry::$settings['max_user_name_length'] ? ipsRegistry::$settings['max_user_name_length'] : 26;

		# Upload
		ipsRegistry::$settings['upload_dir'] = ipsRegistry::$settings['upload_dir'] ? ipsRegistry::$settings['upload_dir'] : DOC_IPS_ROOT_PATH.'uploads';
		ipsRegistry::$settings['upload_url'] = ipsRegistry::$settings['upload_url'] ? ipsRegistry::$settings['upload_url'] :ipsRegistry::$settings['base_url']  . '/uploads';

		# Char set
		ipsRegistry::$settings['gb_char_set'] = ipsRegistry::$settings['gb_char_set'] ? ipsRegistry::$settings['gb_char_set'] : 'UTF-8';

		if( !defined( 'IPS_DOC_CHAR_SET' ) )
		{
			define( 'IPS_DOC_CHAR_SET', ipsRegistry::$settings['gb_char_set'] );
		}

		# If htaccess mod rewrite is on, enforce path_info usage
		ipsRegistry::$settings['url_type'] = ( ipsRegistry::$settings['htaccess_mod_rewrite'] ) ? 'path_info' : ipsRegistry::$settings['url_type'];
	}

	/**
	 * Check caches
	 *
	 * @return	void
	 * @author	MattMecham
	 * @access	protected
	 */
	protected function checkCaches()
	{
		//-----------------------------------------
		// Check app cache data
		//-----------------------------------------

		$group_cache = self::$handles['caches']->getCache('group_cache');

		if ( ! is_array( $group_cache ) OR ! count( $group_cache ) )
		{
			/* Another catch for IPB 1.3 */
			if ( IPS_IS_UPGRADER )
			{
				if ( IPSSetUp::is200plus() !== TRUE )
				{
					/* Er.. don't actually think we need 'em to be honest */
					self::DB()->delete( 'cache_store', 'cs_key=\'group_cache\'' );

					self::DB()->build( array( 'select' => '*',
											  'from'	 => 'groups' ) );

					self::DB()->execute();

					$groups = array();

					while( $row = self::DB()->fetch() )
					{
						$groups[ $row['g_id'] ] = $row;
					}

					self::DB()->insert( 'cache_store', array( 'cs_key'   => 'group_cache',
															  'cs_value' => serialize( $groups ) ) );
				}
				else
				{
					//$this->cache()->rebuildCache( 'group_cache', 'global' );
				}
			}
		}
	}
}

/**
 * ips_base_Registry
 *
 **/
abstract class ips_base_Registry
{
	/**
	 * Generic data storage for each class that extends ips_base_Registry
	 *
	 * @access	protected
	 * @var		array
	 **/
	protected $data_store = array();

	/**
	 * get()
	 *
	 * Returns the value for the specified key from the data_store, can be overriden by child classes
	 *
	 * @access	public
	 * @param	mixed $key
	 * @return	mixed
	 **/
	protected function get( $key )
	{
		if( isset( $this->data_store[ $key ] ) )
		{
			return $this->data_store[ $key ];
		}
		else
		{
			return '';
		}
	}

	/**
	 * set()
	 *
	 * Sets a value in the data_store
	 *
	 * @access	public
	 * @param	mixed $key
	 * @param	mixed $val
	 * @return	mixed
	 **/
	protected function set( $key, $val )
	{
		$this->data_store[ $key ] = $val;
	}
}

/**
 * Base Database class
 */
class ips_DBRegistry extends ips_base_Registry
{
	/**
	 * DB instance
	 *
	 * @access	private
	 * @var		object
	 */
	private static $instance;

	/**
	 * Static array for variables
	 *
	 * @access	private
	 * @var		array
	 */
	private static $vars		= array();

	/**
	 * Static DB prefixes
	 *
	 * @access	private
	 * @var		array
	 */
	private static $dbPrefixes	= array( '__default__' => '' );

	/**
	 * Static DB driver
	 *
	 * @access	private
	 * @var		array
	 */
	private static $dbDrivers	= array( '__default__' => '' );

	/**
	 * Static default DB object
	 *
	 * @access	private
	 * @var		string
	 */
	private static $defaultKey	= '__default__';

	/**
	 * Cache files tried to load
	 *
	 * @access	private
	 * @var		array
	 */
	private static $_queryFilesTriedToLoad = array();

	/**
	 * Cache file parsed names
	 *
	 * @access	private
	 * @var		array
	 */
	private static $_queryFilesNames = array();

	/**
	 * Singleton init
	 *
	 * @access	public
	 * @return	object
	 */
	static public function instance()
	{
		if ( ! self::$instance )
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get DB instance
	 *
	 * @access	public
	 * @param	string	Key
	 * @return	object
	 */
	static public function getDB( $key='' )
	{
		$key = ( $key ) ? $key : self::$defaultKey;

		return self::instance()->get( $key );
	}

	/**
	 * Return the prefix for this connection
	 *
	 * @access	public
	 * @param	string	Key
	 * @return	string
	 */
	static public function getPrefix( $key='' )
	{
		$key = ( $key ) ? $key : self::$defaultKey;

		if ( isset(self::$dbPrefixes[$key]) )
		{
			return self::$dbPrefixes[ $key ];
		}
		else
		{
			throw new Exception( "Database connection key $key does not exist" );
		}
	}

	/**
	 * Returns the driver type currently in use, ex: mysql
	 *
	 * @access	public
	 * @param	string	$key	Key of the db connection to check
	 * @return	object
	 */
	static public function getDriverType( $key= '' )
	{
		/* Set Key */
		$key = ( $key ) ? $key : self::$defaultKey;

		return self::$dbDrivers[ $key ];
	}

	/**
	 * Sets the DB instance
	 *
	 * @access	public
	 * @param	object	$db_driver	DB instance
	 * @param	string	$key		Key of the db connection to check
	 * @return	void
	 */

	static function setDB( $db_driver, $key='' )
	{
		/* INIT */
		$db_driver        = strtolower( $db_driver );
		$query_file_extra = ( IPS_AREA == 'admin' ) ? '_admin' : '';
		$key              = ( $key ) ? $key : self::$defaultKey;

		/* Fix up settings */
		if ( ! class_exists( 'dbMain' ) )
		{
			require_once ( IPS_KERNEL_PATH . 'classDb' . ucwords($db_driver) . ".php" );
		}

		$classname = "db_driver_" . $db_driver;

		/* INIT Object */
		self::instance()->dbObjects[ $key ] = new $classname;

		self::instance()->dbObjects[ $key ]->obj['sql_database']         = ipsRegistry::$settings['sql_database'];
		self::instance()->dbObjects[ $key ]->obj['sql_user']		     = ipsRegistry::$settings['sql_user'];
		self::instance()->dbObjects[ $key ]->obj['sql_pass']             = ipsRegistry::$settings['sql_pass'];
		self::instance()->dbObjects[ $key ]->obj['sql_host']             = ipsRegistry::$settings['sql_host'];
		self::instance()->dbObjects[ $key ]->obj['sql_charset']          = ipsRegistry::$settings['sql_charset'];
		self::instance()->dbObjects[ $key ]->obj['sql_tbl_prefix']       = ipsRegistry::$settings['sql_tbl_prefix'] ? ipsRegistry::$settings['sql_tbl_prefix'] : '';
		self::instance()->dbObjects[ $key ]->obj['force_new_connection'] = ( $key != self::$defaultKey ) ? 1 : 0;
		self::instance()->dbObjects[ $key ]->obj['use_shutdown']         = IPS_USE_SHUTDOWN;
		# Error log
		self::instance()->dbObjects[ $key ]->obj['error_log']            = DOC_IPS_ROOT_PATH . 'cache/sql_error_log_'.date('m_d_y').'.cgi';
		self::instance()->dbObjects[ $key ]->obj['use_error_log']        = IN_DEV ? 0 : 1;
		# Debug log - Don't use this on a production board!
		self::instance()->dbObjects[ $key ]->obj['debug_log']            = DOC_IPS_ROOT_PATH . 'cache/sql_debug_log_'.date('m_d_y').'.cgi';
		self::instance()->dbObjects[ $key ]->obj['use_debug_log']        = IN_DEV ? 0 : 0;

		//---------------------------------------------
		// Required vars?
		//---------------------------------------------

		if ( is_array( self::instance()->dbObjects[ $key ]->connect_vars ) and count( self::instance()->dbObjects[ $key ]->connect_vars ) )
		{
			foreach( self::instance()->dbObjects[ $key ]->connect_vars as $k => $v )
			{
				self::instance()->dbObjects[ $key ]->connect_vars[ $k ] = ipsRegistry::$settings[ $k ];
			}
		}

		//------------------------------------------
		// Backwards compat
		//------------------------------------------

		if ( ! self::instance()->dbObjects[ $key ]->connect_vars['mysql_tbl_type'] )
		{
			self::instance()->dbObjects[ $key ]->connect_vars['mysql_tbl_type'] = 'myisam';
		}

		//------------------------------------------
		// Update settings
		//------------------------------------------

		self::$dbPrefixes[ $key ] = self::instance()->dbObjects[ $key ]->obj['sql_tbl_prefix'];
		self::$dbDrivers[ $key ]  = $db_driver;

		//------------------------------------------
		// Get a DB connection
		//------------------------------------------

		self::instance()->dbObjects[ $key ]->connect();

		self::instance()->set( $key, self::instance()->dbObjects[ $key ] );
	}

	/**
	 * Fetch query file, if available
	 *
	 * @access	public
	 * @param	string		'public' or 'admin'
	 * @param	string		App dir
	 * @param	string		Key
	 * @return	boolean
	 */
	static public function loadQueryFile( $where, $app, $key='' )
	{
		$key    = ( $key ) ? $key : self::$defaultKey;
		$where  = ( $where == 'admin' ) ? 'admin' : 'public';
		$driver = self::getDriverType( $key );

		/* Already tried to load? */
		if ( isset( self::$_queryFilesTriedToLoad[ $app . '-' . $driver ] ) )
		{
			return;
		}
		else
		{
			self::$_queryFilesTriedToLoad[ $app . '-' . $driver ] = 1;
		}

		$file   = self::fetchQueryFileName( $where, $app, $key );
		$class  = self::fetchQueryFileClassName( $where, $app, $key );

		IPSDebug::addMessage( "* Checking for query cache file: " . $file );

		if ( file_exists( $file ) )
		{
			self::getDB( $key )->loadCacheFile( $file, $class, TRUE );
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * Fetch query file, if available
	 *
	 * @access	public
	 * @param	string		'public' or 'admin'
	 * @param	string		App dir
	 * @param	string		Key
	 * @return	File name
	 */
	static public function fetchQueryFileClassName( $where, $app, $key='' )
	{
		$key    = ( $key ) ? $key : self::$defaultKey;
		$where  = ( $where == 'admin' ) ? 'admin' : 'public';

		return $where . '_' . $app . '_sql_queries';
	}

	/**
	 * Fetch query file, if available
	 *
	 * @access	public
	 * @param	string		'public' or 'admin'
	 * @param	string		App dir
	 * @param	string		Key
	 * @return	File name
	 */
	static public function fetchQueryFileName( $where, $app, $key='' )
	{
		$key    = ( $key ) ? $key : self::$defaultKey;
		$where  = ( $where == 'admin' ) ? 'admin' : 'public';
		$driver = self::getDriverType( $key );

		/* Already tried to load? */
		if ( ! isset( self::$_queryFilesNames[ $app . '-' . $driver ] ) )
		{
			self::$_queryFilesNames[ $app . '-' . $driver ] = IPSLib::getAppDir( $app ) . '/sql/' . $driver . '_' . $where . '.php';
		}

		return self::$_queryFilesNames[ $app . '-' . $driver ];
	}
}

/**
 * Stub class to prevent other classes from breaking
 */
class ips_CacheRegistry extends ips_base_Registry
{
	/**
	 * Instance
	 *
	 * @access	private
	 * @var		object
	 */
	private static $instance;

	/**
	 * Save options
	 *
	 * @access	private
	 * @var		array
	 */
	private $save_options		= array();

	/**
	 * Cache library
	 *
	 * @access	private
	 * @var		object
	 */
	private static $cacheLib;

	/**
	 * Holds debug info
	 *
	 * @access	public
	 * @var		array
	 */
	public $debugInfo			= array();

	/**
	 * Initialized flag
	 *
	 * @access	private
	 * @var		bool
	 */
	private static $initiated	= FALSE;

	/**
	 * Singleton instance
	 *
	 * @access	public
	 * @return	object
	 */
	static public function instance()
	{
		if ( ! self::$instance )
		{
			self::$instance = new self();
			self::$instance->init();
		}

		return self::$instance;
	}

	/**
	 * Initialize class
	 *
	 * @access	private
	 * @return	void
	 */
	private function init()
	{
		if ( IPS_IS_UPGRADER )
		{
			if ( self::$initiated !== TRUE )
			{
				//--------------------------------
				// Eaccelerator...
				//--------------------------------

				if( function_exists('eaccelerator_get') AND ipsRegistry::$settings['use_eaccelerator'] == 1 )
				{
					require IPS_KERNEL_PATH.'interfaces/interfaceCache.php';
					require IPS_KERNEL_PATH.'classCacheEaccelerator.php';
					self::$cacheLib = new classCacheEaccelerator( ipsRegistry::$settings['board_url'] );
				}

				//--------------------------------
				// Memcache
				//--------------------------------

				else if( function_exists('memcache_connect') AND ipsRegistry::$settings['use_memcache'] == 1 )
				{
					require IPS_KERNEL_PATH.'interfaces/interfaceCache.php';
					require IPS_KERNEL_PATH.'classCacheMemcache.php';
					self::$cacheLib = new classCacheMemcache( ipsRegistry::$settings['board_url'], ipsRegistry::$settings );
				}

				//--------------------------------
				// XCache...
				//--------------------------------

				else if( function_exists('xcache_get') AND ipsRegistry::$settings['use_xcache'] == 1 )
				{
					require IPS_KERNEL_PATH.'interfaces/interfaceCache.php';
					require IPS_KERNEL_PATH.'classCacheXcache.php';
					self::$cacheLib = new classCacheXcache( ipsRegistry::$settings['board_url'] );
				}

				//--------------------------------
				// APC...
				//--------------------------------

				else if( function_exists('apc_fetch') AND ipsRegistry::$settings['use_apc'] == 1 )
				{
					require IPS_KERNEL_PATH.'interfaces/interfaceCache.php';
					require IPS_KERNEL_PATH.'classCacheApc.php';
					self::$cacheLib = new classCacheApc( ipsRegistry::$settings['board_url'] );
				}

				//--------------------------------
				// Diskcache
				//--------------------------------

				else if( ipsRegistry::$settings['use_diskcache'] == 1 )
				{
					require IPS_KERNEL_PATH.'interfaces/interfaceCache.php';
					require IPS_KERNEL_PATH.'classCacheDiskcache.php';
					self::$cacheLib = new classCacheDiskcache( ipsRegistry::$settings['board_url'] );
				}

				if( is_object(self::$cacheLib) AND self::$cacheLib->crashed )
				{
					// There was a problem - not installed maybe?
					unset(self::$cacheLib);
					self::$cacheLib = NULL;
				}

				$caches         = array();
				$_caches		= array();
				$_load			= array();
				$_pre_load      = IPSDebug::getMemoryDebugFlag();

				//-----------------------------------------
				// Get default cache list
				//-----------------------------------------

				$CACHE = ipsRegistry::_fetchCoreVariables( 'cache' );
				$_LOAD = ipsRegistry::_fetchCoreVariables( 'cacheload' );

				if ( is_array( $CACHE ) )
				{
					foreach( $CACHE as $key => $data )
					{
						if ( isset($data['acp_only']) AND $data['acp_only'] AND IPS_AREA != 'admin' )
						{
							continue;
						}

						$_caches[ $key ]	= $CACHE;

						if ( $data['default_load'] )
						{
							$caches[ $key ] = $key;
						}
					}

					if( count($_LOAD) )
					{
						foreach( $_LOAD as $key => $one )
						{
							$_load[ $key ] = $key;
						}
					}
				}

				//-----------------------------------------
				// Get application cache list
				//-----------------------------------------

				if ( IPS_APP_COMPONENT )
				{
					$CACHE = ipsRegistry::_fetchAppCoreVariables( IPS_APP_COMPONENT, 'cache' );
					$_LOAD = ipsRegistry::_fetchAppCoreVariables( IPS_APP_COMPONENT, 'cacheload' );

					if ( is_array( $CACHE ) )
					{
						foreach( $CACHE as $key => $data )
						{
							if ( isset( $data['acp_only'] ) && $data['acp_only'] AND IPS_AREA != 'admin' )
							{
								continue;
							}

							$_caches[ $key ]	= $CACHE;

							if ( $data['default_load'] )
							{
								$caches[ $key ] = $key;
							}
						}

						if( count($_LOAD) )
						{
							foreach( $_LOAD as $key => $one )
							{
								$_load[ $key ] = $key;
							}
						}
					}
				}

				if( is_array($_load) AND count($_load) )
				{
					foreach( $_load as $key )
					{
						$caches[ $key ] = $key;
					}
				}

				//-----------------------------------------
				// Load 'em
				//-----------------------------------------

				self::_loadCaches( $caches );

			}

			self::$initiated = TRUE;
		}
		else
		{
			self::$initiated = TRUE;
			self::instance()->data_store = array();
		}
	}

	/**
	 * Load cache(s)
	 *
	 * @param	array 	Array of caches to load: array( 'group_cache', 'forum_cache' )
	 * @return	mixed	Loaded Cache
	 * @access 	private
	 * @author	MattMecham
	 */
	private static function _loadCaches( $caches=array() )
	{
		if ( ! is_array( $caches ) OR ! count( $caches ) )
		{
			return NULL;
		}

		//-----------------------------------------
		// Finalize
		//-----------------------------------------

		$cachelist = "'".implode( "','", $caches )."'";

		//--------------------------------
		// Eaccelerator...
		//--------------------------------

		if ( is_object( self::$cacheLib ) )
		{
			$temp_cache 	 = array();
			$new_cache_array = array();

			foreach( $caches as $key )
			{
				$temp_cache[$key] = self::$cacheLib->getFromCache( $key );

				if ( ! $temp_cache[$key] )
				{
					$new_cache_array[] = $key;
				}
				else
				{
					if ( strstr( $temp_cache[$key], "a:" ) )
					{
						self::instance()->data_store[ $key ] = unserialize( $temp_cache[$key] );
					}
					else if( $temp_cache[$key] == "EMPTY" )
					{
						self::instance()->data_store[ $key ] = NULL;
					}
					else
					{
						self::instance()->data_store[ $key ] = $temp_cache[$key];
					}
				}
			}

			$cachearray = $new_cache_array;

			unset($new_cache_array, $temp_cache);
		}

		//--------------------------------
		// Get from DB...
		//--------------------------------

		if ( $cachelist )
		{
			ipsRegistry::DB()->build( array( 'select' => '*', 'from' => 'cache_store', 'where' => "cs_key IN ( $cachelist )" ) );
			ipsRegistry::DB()->execute();

			$_seenKeys = array();

			while ( $r = ipsRegistry::DB()->fetch() )
			{
				$_seenKeys[ $r['cs_key'] ] = $r['cs_key'];

				self::instance()->debugInfo[ $r['cs_key'] ] = array( 'size' => IPSLib::strlenToBytes( strlen($r['cs_value']) ) );

				if ( $r['cs_array'] OR substr( $r['cs_value'], 0, 2 ) == "a:" )
				{
					self::instance()->data_store[ $r['cs_key'] ] = unserialize( $r['cs_value'] );

					if ( ! is_array( self::instance()->data_store[ $r['cs_key'] ] ) )
					{
						self::instance()->data_store[ $r['cs_key'] ] = array();
					}
				}
				else
				{
					self::instance()->data_store[ $r['cs_key'] ] = ( $r['cs_value'] ) ? $r['cs_value'] : NULL;
				}

				if ( is_object( self::$cacheLib ) )
				{
					if ( ! $r['cs_value'] )
					{
						$r['cs_value'] = "EMPTY";
					}

					self::$cacheLib->putInCache( $r['cs_key'], $r['cs_value'] );
				}
			}
		}

		//-----------------------------------------
		// Make sure each key is in data_store otherwise
		// repeated calls will keep trying to load it
		//-----------------------------------------

		foreach( $caches as $_cache )
		{
			if ( ! in_array( $_cache, $_seenKeys ) )
			{
				self::instance()->data_store[ $_cache ] = NULL;
			}
		}
	}

	/**
	 * Set a cache
	 *
	 * @access	protected
	 * @param	string	Cache Key
	 * @param	mixed	Cache value (typically an array)
	 * @return	void
	 */
	protected function cacheSet( $key, $val )
	{
		/* Update in_memory cache */
		$this->data_store[ $key ] = $val;

		$this->save_options['donow'] = isset($this->save_options['donow']) ? $this->save_options['donow'] : 0;

		//-----------------------------------------
		// Next...
		//-----------------------------------------

		if ( $key )
		{
			if ( ! isset($val) OR ! $val )
			{
				if ( isset($this->save_options['array']) AND $this->save_options['array'] )
				{
					$value = serialize($this->data_store[ $key ]);
				}
				else
				{
					$value = $this->data_store[ $key ];
				}
			}
			else
			{
				if ( isset($this->save_options['array']) AND $this->save_options['array'] )
				{
					$value = serialize($val);
				}
				else
				{
					$value = $val;
				}
			}

			ipsRegistry::DB()->no_escape_fields['cs_key'] = 1;

			if ( $this->save_options['donow'] )
			{
				ipsRegistry::DB()->replace( 'cache_store', array( 'cs_array' => intval($this->save_options['array']), 'cs_key' => $key, 'cs_value' => $value, 'cs_updated' => time() ), array( 'cs_key' ) );
			}
			else
			{
				ipsRegistry::DB()->replace( 'cache_store', array( 'cs_array' => intval($this->save_options['array']), 'cs_key' => $key, 'cs_value' => $value, 'cs_updated' => time() ), array( 'cs_key' ), true );
			}
		}

		/* Reest... */
		$this->save_options = array();
	}

	/**
	 * Check to see if the cache exists or not
	 *
	 * @access	public
	 * @param	string		Cache name
	 * @return	boolean
	 */
	static public function exists( $key )
	{
		return FALSE;
	}

	/**
	 * Override the get() ArrayAccess method for the cache lib
	 * The default method just pulls from data_store, but the cache library can dynamically load caches, so we need
	 * to properly use getCache so the cache is loaded if it's not already
	 *
	 * @access	protected
	 * @param	mixed	$key
	 * @return	string
	 */
	protected function get( $key )
	{
		if ( ! in_array( $key, array_keys( self::instance()->data_store ) ) )
		{
			$cache = self::instance()->getCache( $key );
		}
		else
		{
			$cache = self::instance()->data_store[ $key ];
		}

		return $cache;
	}

	/**
	 * Get the cache
	 * If the cache has not been loaded during init(), then it'll load the
	 * cache.
	 *
	 * @access	public
	 * @param	string	Cache key
	 * @param	mixed	The loaded cache [typically an array]
	 * @return	string
	 */
	static public function getCache( $key )
	{
		if ( ! in_array( $key, array_keys( self::instance()->data_store ) ) )
		{
			$cache = self::instance()->data_store[ $key ];
		}
		else
		{
			$cache = self::instance()->get( $key );
		}

		return $cache;
	}

	/**
	 * Fetch all the caches as a reference
	 *
	 * @access	public
	 * @return	array
	 */
	static public function &fetchCaches()
	{
		return self::instance()->data_store;
	}

	/**
	 * Update a cache temporarily (do not save to db)
	 *
	 * @access	public
	 * @param	string	Cache key
	 * @param	mixed	Cache value
	 * @return	void
	 */
	static public function updateCacheWithoutSaving( $key, $value )
	{
		self::instance()->data_store[ $key ] = $value;
	}

	/**
	 * Update a cache permanantly (save to db)
	 *
	 * @access	public
	 * @param	string	Cache key
	 * @param	mixed	Cache value
	 * @param	array 	Options
	 * @return	mixed
	 */
	static public function setCache( $key, $value, $options=array() )
	{
		if ( ! $key OR $value=='' )
		{
			throw new Exception( "Key or value missing in setCache" );
		}

		self::instance()->save_options = $options;
		return self::instance()->cacheSet( $key, $value );
	}

	/**
	 * Rebuild a cache
	 *
	 * @access	public
	 * @param	string	Cache key
	 * @param	string	Application
	 * @return	void
	 */
	static public function rebuildCache( $key, $app='' )
	{
		$app = IPSText::alphanumericalClean( $app );

		if( $app )
		{
			if ( $app == 'global' )
			{
				$CACHE = ipsRegistry::_fetchCoreVariables( 'cache' );
			}
			else
			{
				$CACHE = ipsRegistry::_fetchAppCoreVariables( $app, 'cache' );
			}

			if ( $CACHE[ $key ] )
			{
				$file_to_check = $CACHE[ $key ]['recache_file'];

				if ( $file_to_check AND is_file($file_to_check) AND file_exists( $file_to_check ) )
				{
					$_class = $CACHE[ $key ]['recache_class'];
					$_func  = $CACHE[ $key ]['recache_function'];

					require_once( $file_to_check );

					if( class_exists( $_class ) )
					{
						$recache =  new $_class( ipsRegistry::instance() );

						if( method_exists( $recache, 'makeRegistryShortcuts' ) )
						{
							$recache->makeRegistryShortcuts( ipsRegistry::instance() );
						}

						$recache->$_func();
					}
				}
			}
		}
		else
		{
			try
			{
				foreach( new DirectoryIterator( IPS_ROOT_PATH . 'applications' ) as $application )
				{
					if( $application->isDir() AND !$application->isDot() )
					{
						$CACHE = ipsRegistry::_fetchAppCoreVariables( IPS_APP_COMPONENT, 'cache' );
            	
						if ( is_array( $CACHE ) )
						{
							if ( $CACHE[ $key ] )
							{
								$file_to_check = $CACHE[ $key ]['recache_file'];
            	
								if ( $file_to_check AND file_exists( $file_to_check ) )
								{
									$_class = $CACHE[ $key ]['recache_class'];
									$_func  = $CACHE[ $key ]['recache_function'];
            	
									require_once( $file_to_check );
									$recache           =  new $_class( ipsRegistry::instance() );
            	
									if( method_exists( $recache, 'makeRegistryShortcuts' ) )
									{
										$recache->makeRegistryShortcuts( ipsRegistry::instance() );
									}
            	
									$recache->$_func();
								}
            	
								break;
							}
						}
					}
				}
			} catch ( Exception $e ) {}
		}
	}
}

/**
 * Base application class
 */
class ips_MemberRegistry extends ips_base_Registry
{
	/**
	 * Object instance
	 *
	 * @access	private
	 * @var		object
	 */
	private static $instance;

	/**
	 * Settings
	 *
	 * @access	private
	 * @var		array
	 */
	private $settings			= array();

	/**
	 * Member data
	 *
	 * @access	protected
	 * @var		array
	 */
	protected static $member	= array();

	/**
	 * Member id
	 *
	 * @access	public
	 * @var		int
	 */
	public $member_id		= 0;

	/**
	 * Perm mask id
	 *
	 * @access	public
	 * @var		int
	 */
	public $perm_id			= 0;

	/**
	 * Array of perm mask ids
	 *
	 * @access	public
	 * @var		array
	 */
	public $perm_id_array	= array();

	/**
	 * Hash to use in forms
	 *
	 * @access	public
	 * @var		string
	 */
	public $form_hash		= '';

	/**
	 * Language pack id
	 *
	 * @access	public
	 * @var		int
	 */
	public $language_id		= '1';

	/**
	 * Skin id
	 *
	 * @access	public
	 * @var		int
	 */
	public $skin_id			= 0;

	/**
	 * Member preferences
	 *
	 * @access	public
	 * @var		array
	 */
	public $preferences		= array();

	/**
	 * Member session id
	 *
	 * @access	public
	 * @var		string
	 */
	public $session_id		= 0;

	/**
	 * Session type
	 *
	 * @access	public
	 * @var		string
	 */
	public $session_type	= '';

	/**
	 * Member IP Address
	 *
	 * @access	public
	 * @var		string
	 */
	public $ip_address		= '';

	/**
	 * Member last click timestamp
	 *
	 * @access	public
	 * @var		int
	 */
	public $last_click		= 0;

	/**
	 * Member location
	 *
	 * @access	public
	 * @var		string
	 */
	public $location		= '';

	/**
	 * ACP tab data
	 *
	 * @access	public
	 * @var		array
	 */
	public $acp_tab_data	= array();

	/**
	 * Can use fancy js
	 *
	 * @access	public
	 * @var		bool
	 */
	public $can_use_fancy_js	= false;

	/**
	 * Member user agent
	 *
	 * @access	public
	 * @var		string
	 */
	public $user_agent			= '';

	/**
	 * Member browser info
	 *
	 * @access	public
	 * @var		array
	 */
	public $browser				= array();

	/**
	 * Member operating system
	 *
	 * @access	public
	 * @var		string
	 */
	public $operating_system	= 'unknown';

	/**
	 * Is this a bot?
	 *
	 * @access	public
	 * @var		bool
	 */
	public $is_not_human		= false;

	/**
	 * Users this member is ignoring
	 *
	 * @access	public
	 * @var		array
	 */
	public $ignored_users		= array();

	/**
	 * Session class
	 *
	 * @access	private
	 * @var		object
	 */
	private static $session_class;

	/**
	 * Initialized yet?
	 *
	 * @access	public
	 * @var		bool
	 */
	private static $initiated	= FALSE;

	/**
	 * Initialize this object
	 *
	 * @access	public
	 * @return	object
	 */
	static public function instance()
	{
		if ( ! self::$instance )
		{
			self::$instance = new self();
			self::init();
		}

		return self::$instance;
	}

	/**
	 * Destructor
	 *
	 * @access	public
	 * @return	void
	 */
	public function __myDestruct()
	{
	}

	/**
	 * Our singleton INIT function
	 *
	 * @access	protected
	 * @return	void
	 */
	protected static function init()
	{
		if ( self::$initiated !== TRUE )
		{
			//-----------------------------------------
			// IP Address
			//-----------------------------------------

			if ( ipsRegistry::$settings['xforward_matching'] )
			{
				foreach( array_reverse( explode( ',', my_getenv('HTTP_X_FORWARDED_FOR') ) ) as $x_f )
				{
					$x_f = trim($x_f);

					if ( preg_match( '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/', $x_f ) )
					{
						$addrs[] = $x_f;
					}
				}

				$addrs[] = my_getenv('HTTP_CLIENT_IP');
				$addrs[] = my_getenv('HTTP_X_CLUSTER_CLIENT_IP');
				$addrs[] = my_getenv('HTTP_PROXY_USER');
			}

			$addrs[] = my_getenv('REMOTE_ADDR');

			//-----------------------------------------
			// Do we have one yet?
			//-----------------------------------------

			foreach ( $addrs as $ip )
			{
				if ( $ip )
				{
					preg_match( "/^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})$/", $ip, $match );

					self::instance()->ip_address = $match[1].'.'.$match[2].'.'.$match[3].'.'.$match[4];

					if ( self::instance()->ip_address AND self::instance()->ip_address != '...' )
					{
						break;
					}
				}
			}

			//-----------------------------------------
			// Make sure we take a valid IP address
			//-----------------------------------------

			if ( ( ! self::instance()->ip_address OR self::instance()->ip_address == '...' ) AND ! isset( $_SERVER['SHELL'] ) AND $_SERVER['SESSIONNAME'] != 'Console' )
			{
				print "Could not determine your IP address";
				exit();
			}

			if ( IPS_IS_UPGRADER )
			{
				require_once( IPS_ROOT_PATH . "setup/sources/classes/session/sessions.php" );

				self::$session_class = new sessions();
			}
			else
			{
				self::setMember(0);
			}
		}
	}

	/**
	 * Interface to session class
	 *
	 * @access	public
	 * @return	object
	 */
	public static function sessionClass()
	{
		return self::$session_class;
	}

	/**
	 * Get a member property
	 *
	 * @access	public
	 * @param	string	Key
	 * @return	mixed	Member property
	 */
	static public function getProperty( $key )
	{
		return self::instance()->get( $key );
	}

	/**
	 * Set a member property
	 *
	 * @access	public
	 * @param	string	Key
	 * @param	mixed	Value
	 * @return	void
	 */
	static public function setProperty( $key, $value )
	{
		return self::instance()->set( $key, $value );
	}

	/**
	 * Fetch all the member data as a reference
	 *
	 * @access	public
	 * @return	array
	 */
	static public function &fetchMemberData()
	{
		return self::instance()->data_store;
	}

	/**
	 * Set the current member
	 *
	 * @access	public
	 * @param	int		Member ID
	 * @return	void
	 */
	static public function setMember( $member_id )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$member_id = intval( $member_id );
		$addrs     = array();

		//-----------------------------------------
		// If we have a member ID, set up the member
		//-----------------------------------------

		if ( $member_id )
		{
			self::instance()->data_store = IPSMember::load( $member_id, 'extendedProfile,customFields,groups,itemMarkingStorage' );

			if ( self::instance()->data_store['member_id'] )
			{
				self::setUpMember();

				# Form hash
				self::instance()->form_hash = md5( self::instance()->data_store['email'].'&'.self::instance()->data_store['member_login_key'].'&'.self::instance()->data_store['joined'] );
			}
			else
			{
				self::instance()->data_store = IPSMember::setUpGuest();

				self::instance()->perm_id       = ( isset(self::instance()->data_store['org_perm_id']) AND self::instance()->data_store['org_perm_id'] ) ? self::instance()->data_store['org_perm_id'] : self::instance()->data_store['g_perm_id'];
				self::instance()->perm_id_array = explode( ",", self::instance()->perm_id );

				# Form hash
				self::instance()->form_hash = md5("this is only here to prevent it breaking on guests");
			}
		}
		else
		{
			self::instance()->data_store = IPSMember::setUpGuest();

			self::instance()->perm_id       = ( isset(self::instance()->data_store['org_perm_id']) AND self::instance()->data_store['org_perm_id'] ) ? self::instance()->data_store['org_perm_id'] : self::instance()->data_store['g_perm_id'];
			self::instance()->perm_id_array = explode( ",", self::instance()->perm_id );

			# Form hash
			self::instance()->form_hash = md5("this is only here to prevent it breaking on guests");
		}

		//-----------------------------------------
		// Set member data
		//-----------------------------------------

		self::instance()->member_id = $member_id;
	}

	/**
	 * Set up a member's perms based on secondary groups
	 *
	 * @access	public
	 * @param	array 	Member data
	 * @return	array 	New member data
	 */
	static public function setUpSecondaryGroups( $data )
    {
    	if ( isset($data['mgroup_others']) AND $data['mgroup_others'] )
		{
			$cache			= ipsRegistry::cache()->getCache('group_cache');
			$groups_id		= explode( ',', $data['mgroup_others'] );
			$exclude		= array( 'g_title', 'g_icon', 'prefix', 'suffix', 'g_promotion', 'g_photo_max_vars' );
			$less_is_more	= array( 'g_search_flood' );
			$zero_is_best	= array( 'g_attach_max', 'g_attach_per_post', 'g_edit_cutoff', 'g_max_messages' );

			# Blog
			$zero_is_best = array_merge( $zero_is_best, array( 'g_blog_attach_max', 'g_blog_attach_per_entry', 'g_blog_preventpublish' ) );

			# Gallery
			$zero_is_best = array_merge( $zero_is_best, array( 'g_max_diskspace', 'g_max_upload', 'g_max_transfer', 'g_max_views', 'g_album_limit', 'g_img_album_limit', 'g_movie_size' ) );

			if ( count( $groups_id ) )
			{
				foreach( $groups_id as $pid )
				{
					if ( ! isset($cache[ $pid ]['g_id']) OR ! $cache[ $pid ]['g_id'] )
					{
						continue;
					}

					//-----------------------------------------
					// Loop through and mix
					//-----------------------------------------

					foreach( $cache[ $pid ] as $k => $v )
					{
						if ( ! in_array( $k, $exclude ) )
						{
							//-----------------------------------------
							// Add to perm id list
							//-----------------------------------------

							if ( $k == 'g_perm_id' )
							{
								$data['g_perm_id'] .= ','.$v;
							}
							else if ( in_array( $k, $zero_is_best ) )
							{
								if ( $data[ $k ] == 0 )
								{
									continue;
								}
								else if( $v == 0 )
								{
									$data[ $k ] = 0;
								}
								else if ( $v > $data[ $k ] )
								{
									$data[ $k ] = $v;
								}
							}
							else if ( in_array( $k, $less_is_more ) )
							{
								if ( $v < $data[ $k ] )
								{
									$data[ $k ] = $v;
								}
							}
							else
							{
								if ( $v > $data[ $k ] )
								{
									$data[ $k ] = $v;
								}
							}
						}
					}
				}
			}

			//-----------------------------------------
			// Tidy perms_id
			//-----------------------------------------

			$rmp = array();
			$tmp = explode( ',', IPSText::cleanPermString($data['g_perm_id']) );

			if ( count( $tmp ) )
			{
				foreach( $tmp as $t )
				{
					$rmp[ $t ] = $t;
				}
			}

			if ( count( $rmp ) )
			{
				$data['g_perm_id'] = implode( ',', $rmp );
			}
		}

		return $data;
	}

	/**
	 * Set up a member
	 *
	 * @access	private
	 * @return	void
	 */
	static public function setUpMember()
    {
		//-----------------------------------------
        // INIT
        //-----------------------------------------

        $cache = ipsRegistry::cache()->getCache('group_cache');

		//-----------------------------------------
        // Set up main 'display' group
        //-----------------------------------------

        self::instance()->data_store = is_array($cache[ self::instance()->data_store['member_group_id'] ]) ? array_merge( self::instance()->data_store, $cache[ self::instance()->data_store['member_group_id'] ] ) : self::instance()->data_store;

		//-----------------------------------------
		// Work out permissions
		//-----------------------------------------

		self::instance()->data_store = self::instance()->setUpSecondaryGroups( self::instance()->data_store );

		self::instance()->perm_id       = ( isset(self::instance()->data_store['org_perm_id']) AND self::instance()->data_store['org_perm_id'] ) ? self::instance()->data_store['org_perm_id'] : self::instance()->data_store['g_perm_id'];
        self::instance()->perm_id_array = explode( ",", self::instance()->perm_id );
    }
}
