<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * ipsRegistry:: Registry file controlls handling of objects needed throughout IPB
 * Last Updated: $Date: 2009-09-01 07:56:41 -0400 (Tue, 01 Sep 2009) $
 *
 * @author 		$Author: matt $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @since		Tue. 17th August 2004
 * @version		$Rev: 5068 $
 *
 */

/**
* Base registry class
*/
class ipsRegistry
{
	/**
	 * Holds instance of registry (singleton implementation)
	 *
	 * @access	private
	 * @var		object
	 */
	private static $instance;

	/**
	 * Registry initialized yet?
	 *
	 * @access	private
	 * @var		boolean
	 */
	private static $initiated				= FALSE;

	/**
	 * SEO templates
	 *
	 * @access	private
	 * @var		array
	 */
	private static $_seoTemplates			= array();

	/**
	 * Incoming URI - used in SEO / fURL stuffs
	 *
	 * @access	private
	 * @var		string
	 */
	private static $_uri = '';

	/**#@+
	 * Holds data for app / coreVariables
	 *
	 * @access	private
	 * @var		array
	 */
	private static $_coreVariables			= array();
	private static $_masterCoreVariables	= array();
	/**#@-*/

	/**
	 * Handles for other singletons
	 *
	 * @access	private
	 * @var		array
	 */
	private static $handles					= array();

	/**
	 * Handles for other classes
	 *
	 * @access	private
	 * @var		array
	 */
	private static $classes					= array();

	/**
	 * Word array
	 *
	 * @access	public
	 * @var		array
	 */
	static public $words					= array();

	/**
	 * URLs
	 *
	 * @access	public
	 * @var		array
	 */
	static public $urls						= array();

	/**
	 * Our processed URL
	 *
	 * @access	public
	 * @var		string
	 */
	static public $processed_url			= '';

	/**
	 * Server load
	 *
	 * @access	public
	 * @var		string
	 */
	static public $server_load;

	/**
	 * Do not print HTTP headers
	 *
	 * @access	public
	 * @var		bool
	 */
	static public $no_print_header			= false;

	/**#@+
	 * Query string information
	 *
	 * @access	public
	 * @var		string
	 */
	static public $query_string_safe;
	static public $query_string_real;
	static public $query_string_formatted;
	/**#@-*/

	/**#@+
	 * Version information
	 *
	 * @access	public
	 * @var		string
	 */
	static public $version         = null;
	static public $acpversion      = null;
	static public $vn_full         = '';
	static public $vn_build_date   = '';
	static public $vn_build_reason = '';
	/**#@-*/

	/**#@+
	 * Version information
	 *
	 * @access	public
	 * @var		mixed	Strings and arrays
	 */
	static public $applications        = array();
	static public $modules             = array();
	static public $modules_by_section  = array();
	static public $current_application = '';
	static public $current_module      = '';
	static public $current_section     = '';
	/**#@-*/

	/**
	 * Application class vars
	 *
	 * @access	public
	 * @var		array
	 */
	static public $app_class = array();

	/**
	 * Settings
	 *
	 * @access	public
	 * @var		array
	 */
	static public $settings	= array();

	/**
	 * Input parameters
	 *
	 * @access	public
	 * @var		array
	 */
	static public $request	= array();

	/**
	 * Template striping
	 *
	 * @access	public
	 * @var		array
	 */
	public $templateStriping = array();

	/**
	 * Initialize singleton
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
	 * Ok, we need a guaranteed way to perform some clean up
	 * before __destruct() is called on other classes. For example
	 * using the DB connection isn't possible if class_db::__destruct() runs
	 * before ipsRegistry::__destruct. PHP 5 - 5.2.5 ran the __destruct functions
	 * in the order the classes are created, so we could bank on ipsRegistry::__destruct
	 * being called first as it's created first. Happy days
	 * But as of 5.2.5 the order is __reversed__ so that ipsRegistry would be called last
	 * making it all but useless. 
	 * So, we use a register_shutdown_function instead which is always called before any
	 * __destruct() destructors.  Happy days again. We hope
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

		//-----------------------------------------
		// Process any pending emails to go out...
		//-----------------------------------------

		self::processMailQueue();
	}

	/**
	 * Process the mail queue
	 *
	 * @access	public
	 * @return	void
	 */
	static public function processMailQueue()
	{
		//-----------------------------------------
		// SET UP
		//-----------------------------------------

		$doReset								= 0;
		$cache									= self::$handles['caches']->getCache('systemvars');
		self::$settings['mail_queue_per_blob']	= isset(self::$settings['mail_queue_per_blob']) ? self::$settings['mail_queue_per_blob'] : 10;

		if ( ! isset( $cache['mail_queue'] ) OR $cache['mail_queue'] < 0 )
		{
			$mailQueue		     = self::DB()->buildAndFetch( array( 'select' => 'COUNT(*) as total', 'from' => 'mail_queue' ) );
			$mailQueueCount	     = intval($mailQueue['total']);
			$cache['mail_queue'] = $mailQueueCount;
			$doReset		     = 1;
		}
		else
		{
			$mailQueueCount = intval($cache['mail_queue']);
		}

		$sent_ids = array();

		if ( $mailQueueCount > 0 )
		{
			//-----------------------------------------
			// Get the mail stuck in the queue
			//-----------------------------------------

			self::DB()->build( array( 'select' => '*', 'from' => 'mail_queue', 'order' => 'mail_id', 'limit' => array( 0, self::$settings['mail_queue_per_blob'] ) ) );
			self::DB()->execute();

			while ( $r = self::DB()->fetch() )
			{
				$data[]     = $r;
				$sent_ids[] = $r['mail_id'];
			}

			if ( count($sent_ids) )
			{
				//-----------------------------------------
				// Delete sent mails and update count
				//-----------------------------------------

				$mailQueueCount = $mailQueueCount - count($sent_ids);

				self::DB()->delete( 'mail_queue', 'mail_id IN (' . implode( ",", $sent_ids ) . ')' );

				foreach( $data as $mail )
				{
					if ( $mail['mail_to'] and $mail['mail_subject'] and $mail['mail_content'] )
					{
						IPSText::getTextClass('email')->to		= $mail['mail_to'];
						IPSText::getTextClass('email')->from	= $mail['mail_from'] ? $mail['mail_from'] : self::$settings['email_out'];
						IPSText::getTextClass('email')->subject	= $mail['mail_subject'];
						IPSText::getTextClass('email')->message	= $mail['mail_content'];

						if ( $mail['mail_html_on'] )
						{
							IPSText::getTextClass('email')->html_email	= 1;
						}
						else
						{
							IPSText::getTextClass('email')->html_email	= 0;
						}

						IPSText::getTextClass('email')->sendMail();

						IPSDebug::addLogMessage('Email Sent: ' . $mail['mail_to'], 'bulkemail' );
					}
				}
			}
			else
			{
				//-----------------------------------------
				// No mail after all?
				//-----------------------------------------

				$mailQueueCount = 0;
			}

			//-----------------------------------------
			// Set new mail_queue count
			//-----------------------------------------

			$cache['mail_queue']	= $mailQueueCount;
		}
		
		//-----------------------------------------
		// Update cache with remaning email count
		//-----------------------------------------
		
		if ( $mailQueueCount > 0 OR $doReset )
		{
			self::$handles['caches']->setCache( 'systemvars', $cache, array( 'array' => 1, 'donow' => 1, 'deletefirst' => 0 ) );
		}
	}

	/**
	 * Initiate the registry
	 *
	 * @access	public
	 * @return	mixed	false or void
	 */
	static public function init()
	{
		if ( self::$initiated === TRUE )
		{
			return FALSE;
		}

		self::$initiated = TRUE;
	
		/* Load static classes */
		require IPS_ROOT_PATH . "sources/base/core.php";

		$_MASTER	= IPSDebug::getMemoryDebugFlag();
		
		/* Debugging notices? */
		if ( defined( 'IPS_ERROR_CAPTURE' ) AND IPS_ERROR_CAPTURE !== FALSE )
		{
			@error_reporting( E_ALL | E_NOTICE );
			@set_error_handler("IPSDebug::errorHandler");
		}
		
		/* Load core variables */
		self::_loadCoreVariables();

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

		/* Make sure we're installed */
		if ( ! isset( $INFO['sql_database'] ) OR ! $INFO['sql_database'] )
		{
			/* Quick PHP version check */
			if ( ! version_compare( MIN_PHP_VERS, PHP_VERSION, '<=' ) )
			{
				print "You must be using PHP " . MIN_PHP_VERS . " or better. You are currently using: " . PHP_VERSION;
				exit();
			}

			$host = $_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : @getenv('HTTP_HOST');
			$self = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : @getenv('PHP_SELF');

			if( IPS_AREA == 'admin' )
			{
				@header("Location: http://".$host.rtrim(dirname($self), '/\\')."/install/index.php" );
			}
			else
			{
				@header("Location: http://".$host.rtrim(dirname($self), '/\\')."/admin/install/index.php" );
			}
		}

		/* Switch off dev mode you idjit */
		if ( ! defined( 'IN_DEV' ) )
		{
			define( 'IN_DEV', 0 );
		}

		/* Shell defined? */
		if ( ! defined( 'IPS_IS_SHELL' ) )
		{
			define( 'IPS_IS_SHELL', FALSE );
		}

		/* If this wasn't defined in the gateway file... */
		if ( ! defined( 'ALLOW_FURLS' ) )
		{
			define( 'ALLOW_FURLS', ( ipsRegistry::$settings['use_friendly_urls'] ) ? TRUE : FALSE );
		}

		/* Set it again incase a gateway turned it off */
		ipsRegistry::$settings['use_friendly_urls'] = ALLOW_FURLS;

		/* Start timer */
		IPSDebug::startTimer();

		/* Cookies... */
		IPSCookie::$sensitive_cookies = array( 'session_id', 'admin_session_id', 'member_id', 'pass_hash' );

		/* INIT DB */ 
		self::$handles['db'] = ips_DBRegistry::instance();

		/* Set DB */
		self::$handles['db']->setDB( ipsRegistry::$settings['sql_driver'] );

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
		
		/* Define some constants */
		define( 'IPS_IS_TASK', ( isset( self::$request['module'] ) AND self::$request['module'] == 'task' AND self::$request['app'] == 'core' ) ? TRUE : FALSE );
		define( 'IPS_IS_AJAX', ( isset( self::$request['module'] ) AND self::$request['module'] == 'ajax' ) ? TRUE : FALSE );
		
		/* First pass of app set up. Needs to be BEFORE caches and member are set up */
		self::_fUrlInit();

		self::_manageIncomingURLs();

		/* _manageIncomingURLs MUST be called first!!! */
		self::_setUpAppData();

		/* Load app / coreVariables.. must be called after app Data */
		self::_loadAppCoreVariables( IPS_APP_COMPONENT );

		/* Must be called after _manageIncomingURLs */
		self::$handles['db']->getDB()->setDebugMode( ( IPS_SQL_DEBUG_MODE ) ? ( isset($_GET['debug']) ? intval($_GET['debug']) : 0 ) : 0 );

		/* Get caches */
		self::$handles['caches']   = ips_CacheRegistry::instance();

		/* Make sure all is well before we proceed */
		self::instance()->setUpSettings();
		
		/* Bah, now let's go over any input cleaning routines that have settings *sighs* */
		self::$request = IPSLib::postParseIncomingRecursively( self::$request );
		
		/* Set up dummy member class to prevent errors if cache rebuild required */
		self::$handles['member']   = ips_MemberRegistryDummy::instance();
		
		/* Build module and application caches */
		self::instance()->checkCaches();
		
		/* Set up app specific redirects. Must be called before member/sessions setup */
		self::_parseAppResets();
		
		/* Re-assign member */
		unset( self::$handles['member'] );
		self::$handles['member']   = ips_MemberRegistry::instance();
		
		/* Load other classes */
		require_once( IPS_ROOT_PATH    . 'sources/classes/class_localization.php' );
		require_once( IPS_ROOT_PATH    . 'sources/classes/class_public_permissions.php' );

		self::instance()->setClass( 'class_localization', new class_localization( self::instance() ) );
		self::instance()->setClass( 'permissions'       , new classPublicPermissions( self::instance() ) );

		/* Must be called before output initiated */
		self::_getAppClass();
		
		if ( IPS_AREA == 'admin' )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/output/publicOutput.php' );
			require_once( IPS_ROOT_PATH . 'sources/classes/output/adminOutput.php' );
			require_once( IPS_ROOT_PATH . "sources/classes/class_admin_functions.php" );
			require_once( IPS_ROOT_PATH . 'sources/classes/class_permissions.php' );

			self::instance()->setClass( 'output'            , new adminOutput( self::instance() ) );
			self::instance()->setClass( 'adminFunctions'    , new adminFunctions( self::instance() ) );
			self::instance()->setClass( 'class_permissions' , new class_permissions( self::instance() ) );
		}
		else
		{
			require_once( IPS_ROOT_PATH  . 'sources/classes/output/publicOutput.php' );
			self::instance()->setClass( 'output', new output( self::instance(), TRUE ) );
			
			register_shutdown_function( array( 'ipsRegistry', '__myDestruct' ) );
		}

		/* Add SEO templates to the output system */
		self::instance()->getClass('output')->seoTemplates = self::$_seoTemplates;

		//-----------------------------------------
		// Sort out report center early, so counts
		// and cache is right
		//-----------------------------------------

		$memberData	=& self::$handles['member']->fetchMemberData();
		$memberData['showReportCenter']	= false;

		$member_group_ids	= array( $memberData['member_group_id'] );
		$member_group_ids	= array_diff( array_merge( $member_group_ids, explode( ',', $memberData['mgroup_others'] ) ), array('') );
		$report_center		= array_diff( explode( ',', ipsRegistry::$settings['report_mod_group_access'] ), array('') );

		foreach( $report_center as $groupId )
		{
			if( in_array( $groupId, $member_group_ids ) )
			{
				$memberData['showReportCenter']	= true;
			}
		}

		if( $memberData['showReportCenter'] OR $memberData['g_is_supmod'] )
		{
			$memberData['access_report_center']	= true;

			$memberCache	= $memberData['_cache'];
			$reportsCache	= self::$handles['caches']->getCache('report_cache');

			if( ! $memberCache['report_last_updated'] || $memberCache['report_last_updated'] < $reportsCache['last_updated'] )
			{
				require_once( IPSLib::getAppDir('core') . '/sources/classes/reportLibrary.php' );
				$reports = new reportLibrary( ipsRegistry::instance() );
				$totalReports = $reports->rebuildMemberCacheArray();

				$memberCache['report_num']	= $totalReports;
				$memberData['_cache']	= $memberCache;
			}
		}

		/* More set up */
		self::_finalizeAppData();

		/* Finish fURL stuffs */
		self::_fUrlComplete();

		self::instance()->getClass('class_localization')->loadLanguageFile( array( 'public_global' ), 'core' );

		if ( IPS_AREA == 'admin' )
		{
			$validationStatus  = self::member()->sessionClass()->getStatus();
			$validationMessage = self::member()->sessionClass()->getMessage();

			if ( ( ipsRegistry::$request['module'] != 'login' ) AND ( ! $validationStatus ) )
			{
				//-----------------------------------------
				// Force log in
				//-----------------------------------------

				if ( ipsRegistry::$request['module'] == 'ajax' )
				{
					print "{
							'error' : \"Your ACP session has expired. Please refresh the page to log back in.\"
					  	   }";
					exit();
				}
				else
				{
					ipsRegistry::$request['module'] = 'login';
					ipsRegistry::$request['core']   = 'login';

					require_once( IPSLib::getAppDir( 'core' ) . "/modules_admin/login/manualResolver.php" );
					$runme           = new admin_core_login_manualResolver( self::instance() );
					$runme->doExecute( self::instance() );

					exit();
				}
			}
		}
		else if ( IPS_AREA == 'public' )
		{
			/* Set up member */
			self::$handles['member']->finalizePublicMember();

			/* Are we banned: Via IP Address? */
			if ( IPSMember::isBanned( 'ipAddress', self::$handles['member']->ip_address ) === TRUE )
			{
				self::instance()->getClass('output')->showError( 'you_are_banned', 2000, true );
			}
			
			/* Are we banned: By DB */
			if ( self::$handles['member']->getProperty('member_banned') == 1 )
			{
				self::getClass('output')->showError( 'no_view_board', 1000 );
			}

			/* Check temporary ban status */
			if( self::$handles['member']->getProperty( 'temp_ban' ) )
			{
				$ban_arr = IPSMember::processBanEntry( self::$handles['member']->getProperty( 'temp_ban' ) );

				/* No longer banned */
				if( time() >= $ban_arr['date_end'] )
				{
					self::DB()->update( 'members', array( 'temp_ban' => '' ), 'member_id=' . self::$handles['member']->getProperty( 'member_id' ) );
				}
				/* Still banned */
				else
				{
					self::getClass('output')->showError( array( 'account_susp', self::getClass( 'class_localization' )->getDate( $ban_arr['date_end'], 'LONG', 1 ) ), 1001 );
				}
			}

			/* Check server load */
			if ( ipsRegistry::$settings['load_limit'] > 0 )
			{
				$server_load	= IPSDebug::getServerLoad();

				if ( $server_load )
				{
					$loadinfo = explode( "-", $server_load );

					if ( count($loadinfo) )
					{
						self::$server_load = $loadinfo[0];

						if ( self::$server_load > ipsRegistry::$settings['load_limit'] )
						{
							self::instance()->getClass('output')->showError( 'server_too_busy', 2001 );
						}
					}
				}
			}

			if ( IPB_THIS_SCRIPT == 'public' and
				IPS_ENFORCE_ACCESS				   === FALSE AND (
				ipsRegistry::$request['section']   != 'login'  and
				ipsRegistry::$request['section']   != 'lostpass'  and
				ipsRegistry::$request['module']    != 'ajax' and
				ipsRegistry::$request['section']   != 'rss' and
				ipsRegistry::$request['section']   != 'attach' and
				ipsRegistry::$request['module']    != 'task'   and
				ipsRegistry::$request['section']   != 'captcha' ) )
			{
				//-----------------------------------------
				// Permission to see the board?
				//-----------------------------------------
				
				if ( self::$handles['member']->getProperty('g_view_board') != 1 )
				{
					self::getClass('output')->showError( 'no_view_board', 1000 );
				}
				
				//--------------------------------
				//  Is the board offline?
				//--------------------------------

				if (ipsRegistry::$settings['board_offline'] == 1 AND ! IPS_IS_SHELL )
				{
					if ( self::$handles['member']->getProperty('g_access_offline') != 1 )
					{
						ipsRegistry::$settings['no_reg'] = 1;
						self::getClass('output')->showBoardOffline();
					}
				}

				//-----------------------------------------
				// Do we have a display name?
				//-----------------------------------------

				if( !( ipsRegistry::$request['section'] == 'register' AND ( ipsRegistry::$request['do'] == 'complete_login' OR ipsRegistry::$request['do'] == 'complete_login_do' ) ) )
				{
					if ( ! self::$handles['member']->getProperty('members_display_name') AND self::$handles['member']->getProperty('members_created_remote') )
					{
						$pmember = self::DB()->buildAndFetch( array( 'select' => '*', 'from' => 'members_partial', 'where' => "partial_member_id=" . self::$handles['member']->getProperty('member_id') ) );
	
						if ( $pmember['partial_member_id'] )
						{
							self::instance()->getClass('output')->silentRedirect( ipsRegistry::$settings['base_url'] . 'app=core&module=global&section=register&do=complete_login&mid='.self::$handles['member']->getProperty('member_id').'&key='.$pmember['partial_date'] );
						}
					}
				}

				//--------------------------------
				//  Is log in enforced?
				//--------------------------------

				if ( ! ( defined( 'IPS_IS_SHELL' ) && IPS_IS_SHELL === TRUE ) && ( ( ! self::$handles['member']->getProperty('member_id') ) and (ipsRegistry::$settings['force_login'] == 1) && ipsRegistry::$request['section'] != 'register' ) )
				{
					ipsRegistry::$request['app']	= 'core';
					ipsRegistry::$request['module'] = 'login';
					ipsRegistry::$request['core']   = 'login';

					ipsRegistry::getClass('output')->addToDocumentHead( 'importcss', ipsRegistry::$settings['public_dir'] . 'style_css/' . ipsRegistry::getClass('output')->skin['_csscacheid'] . '/ipb_login_register.css' );
					require_once( IPSLib::getAppDir( 'core' ) . "/modules_public/global/login.php" );
					$runme           = new public_core_global_login( self::instance() );
					$runme->doExecute( self::instance() );
					exit;
				}
			}
		}

		IPSDebug::setMemoryDebugFlag( "Registry initialized", $_MASTER );
	}

	/**
	 * Loads application's core variables (if required)
	 *
	 * @access	public
	 * @return	void
	 */
	static public function _loadCoreVariables()
	{
		if ( ! ( isset( self::$_masterCoreVariables['__CLASS__'] ) AND is_object( self::$_masterCoreVariables['__CLASS__'] ) ) )
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
	 * @param	string		App dir
	 * @return	string		Core variable
	 */
	static public function _fetchCoreVariables( $type )
	{
		if ( ! isset( self::$_masterCoreVariables[ $type ] ) OR ! is_array( self::$_masterCoreVariables[ $type ] ) )
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
	 * @access	public
	 * @param	string		App key (dir.. that's directory, not duuhr)
	 * @return	void
	 */
	static public function _loadAppCoreVariables( $appDir )
	{
		if ( ! isset( self::$_coreVariables[ $appDir ] ) )
		{
			$file = IPSLib::getAppDir( $appDir ) . '/extensions/coreVariables.php';
			
			if( file_exists( $file ) )
			{
				require( $file );

				/* Add caches */
				self::$_coreVariables[ $appDir ]['cache']     = ( isset($CACHE) AND is_array( $CACHE ) ) ? $CACHE : array();
				self::$_coreVariables[ $appDir ]['cacheload'] = ( isset($_LOAD) AND is_array( $_LOAD ) ) ? $_LOAD : array();

				/* Add redirect */
				self::$_coreVariables[ $appDir ]['redirect'] = ( isset($_RESET) AND is_array( $_RESET ) ) ? $_RESET : array();

				/* Add bitwise */
				self::$_coreVariables[ $appDir ]['bitwise']  = ( isset($_BITWISE) AND is_array( $_BITWISE ) ) ? $_BITWISE : array();
			}
		}
	}

	/**
	* Fetches apps core variable data
	*
	* @access	public
	* @param	string		App dir
	* @param	string		Type of variable to return
	* @return	string		Core variable
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
	 * Grabs apps bitwise array
	 *
	 * @access	public
	 * @param	string	$appDir
	 * @return	string	Core variables
	 */
	static public function fetchBitWiseOptions( $appDir )
	{
		if ( $appDir == 'global' )
		{
			return self::_fetchCoreVariables( 'bitwise' );
		}
		else
		{
			return self::_fetchAppCoreVariables( $appDir, 'bitwise' );
		}
	}

	/**
	 * Function for storing class handles to
	 * prevent having to re-initialize them constantly
	 *
	 * @access	public
	 * @param	string		Key
	 * @param	object		Object to store
	 */
	static public function setClass( $key='', $value='' )
	{
		self::checkForInit();

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
	 * @param	object		Retrieved object
	 */
	static public function getClass( $key )
	{		
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
				
				case 'classItemMarking':
					if ( defined( 'IPS_TOPICMARKERS_DEBUG' ) AND IPS_TOPICMARKERS_DEBUG === TRUE )
					{
						require_once( IPS_ROOT_PATH . 'sources/classes/itemmarking/classItemMarking_debug.php' );
						self::$classes['classItemMarking'] = new classItemMarking_debug( self::instance() );
					}
					else
					{
						require_once( IPS_ROOT_PATH . 'sources/classes/itemmarking/classItemMarking.php' );
						self::$classes['classItemMarking'] = new classItemMarking( self::instance() );
					}
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
	 * @return	mixed 		Array of items or false if IN_DEV is off
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
	 * Shortcut function for accessing the registry
	 *
	 * @access	public
	 * @param	string	Key
	 * @return	object	Stored object
	 */
	public function __get( $key )
	{
		self::checkForInit();

		$_class = self::getClass( $key );

		if ( is_object( $_class ) )
		{
			return $_class;
		}
	}

	/**
	 * See if a class is loaded
	 *
	 * @access	public
	 * @param	string	Key
	 * @return	bool	Loaded or not
	 */
	static public function isClassLoaded( $key )
	{
		return ( isset( self::$classes[ $key ] ) && is_object( self::$classes[ $key ] ) ) ? TRUE : FALSE;
	}

	/**
	 * Get DB object
	 *
	 * @access	public
	 * @param	string	Key
	 * @return	object
	 */
	static public function DB( $key='' )
	{
		self::checkForInit();
		return self::$handles['db']->getDB( $key );
	}

	/**
	 * Get DB functions object
	 *
	 * @access	public
	 * @param	string	Key
	 * @return	object
	 */
	static public function dbFunctions( $key='' )
	{
		self::checkForInit();
		return self::$handles['db'];
	}

	/**
	 * Get Cache object
	 *
	 * @access	public
	 * @return	object
	 */
	static public function cache()
	{
		self::checkForInit();
		return self::$handles['caches'];
	}

	/**
	 * Get settings array
	 *
	 * @access	public
	 * @return	array
	 */
	static public function settings()
	{
		self::checkForInit();
		return ipsRegistry::$settings;
	}

	/**
	 * Fetch all the settings as a reference
	 *
	 * @access	public
	 * @return	array
	 */
	static public function &fetchSettings()
	{
		return ipsRegistry::$settings;
	}

	/**
	 * Fetch all request items as reference
	 *
	 * @access	public
	 * @return	array
	 */
	static public function &fetchRequest()
	{
		return ipsRegistry::$request;
	}

	/**
	 * Get Request array
	 *
	 * @access	public
	 * @return	array
	 */
	static public function request()
	{
		self::checkForInit();
		return ipsRegistry::$request;
	}

	/**
	 * Get Member object
	 *
	 * @access	public
	 * @return	object
	 */
	static public function member()
	{
		self::checkForInit();

		if( isset( self::$handles['member'] ) )
		{
			return self::$handles['member'];
		}
	}

	/**
	 * Get current application
	 *
	 * @access	public
	 * @return	string
	 */
	static public function getCurrentApplication()
	{
		return self::$current_application;
	}

	/**
	 * Get all applications
	 *
	 * @access	public
	 * @return	array
	 */
	public function getApplications()
	{
		return self::$applications;
	}

	/**
	 * Check to see if we've initialized
	 *
	 * @access	protected
	 * @return	void
	 */
	static protected function checkForInit()
	{
		if ( self::$initiated !== TRUE )
		{
			throw new Exception('ipsRegistry has not been initiated. Do so by calling ipsRegistry::init()' );
		}
	}

	/**
	 * Grab the app class file
	 *
	 * @return	void
	 * @author	MattMecham
	 * @access	private
	 */
	private static function _getAppClass()
	{
		# Load app class
		if ( ! isset( self::$app_class[ IPS_APP_COMPONENT ] ) )
		{
			$_file = IPSLib::getAppDir(  IPS_APP_COMPONENT ) . '/app_class_' . IPS_APP_COMPONENT . '.php';
			$_name = 'app_class_' . IPS_APP_COMPONENT;

			if ( file_exists( $_file ) )
			{
				require_once( $_file );

				self::$app_class[ IPS_APP_COMPONENT ] = new $_name( self::instance() );
			}
		}
	}

	/**
	 * INIT furls
	 * Performs set up and figures out any incoming links
	 *
	 * @access	private
	 * @return	void
	 */
	private static function _fUrlInit()
	{
		if ( ipsRegistry::$settings['use_friendly_urls'] )
		{
			/* Grab and store accessing URL */
			self::$_uri = preg_replace( "/s=(&|$)/", '', str_replace( '/?', '/index.php?', $_SERVER['REQUEST_URI'] ) );
			
			$_urlBits = array();
			
			/* Grab FURL data... */
			if ( file_exists( DOC_IPS_ROOT_PATH . 'cache/furlCache.php' ) )
			{
				require( DOC_IPS_ROOT_PATH . 'cache/furlCache.php' );
				self::$_seoTemplates = $templates;
			}
			else
			{
				/* Attempt to write it */
				self::$_seoTemplates = IPSLib::buildFurlTemplates();
				
				try
				{
					IPSLib::cacheFurlTemplates();
				}
				catch( Exception $e )
				{
				}
			}

			if ( is_array( self::$_seoTemplates ) AND count( self::$_seoTemplates ) AND IPS_IS_TASK !== TRUE AND IPS_IS_AJAX !== TRUE )
			{ 
				$qs  = $_SERVER['QUERY_STRING'] ? $_SERVER['QUERY_STRING'] : @getenv('QUERY_STRING');
				$uri = $_SERVER['REQUEST_URI']  ? $_SERVER['REQUEST_URI']  : @getenv('REQUEST_URI');

				$_toTest = ( $qs ) ? $qs : $uri;

				foreach( self::$_seoTemplates as $key => $data )
				{
					if ( ! isset( $data['in']['regex'] ) OR ! $data['in']['regex'] )
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

								$_GET[ $k ]     = $v;
								$_POST[ $k ]    = $v;
								$_REQUEST[ $k ] = $v;
								$_urlBits[ $k ] = $v;

								ipsRegistry::$request[ $k ]	= $v;
							}
						}

						if ( strstr( $_toTest, self::$_seoTemplates['__data__']['varBlock'] ) )
						{ 
							$_parse = substr( $_toTest, strpos( $_toTest, self::$_seoTemplates['__data__']['varBlock'] ) + strlen( self::$_seoTemplates['__data__']['varBlock'] ) );

							$_data = explode( self::$_seoTemplates['__data__']['varSep'], $_parse );
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

									$_GET[ $k ]     = $v;
									$_POST[ $k ]    = $v;
									$_REQUEST[ $k ] = $v;
									$_urlBits[ $k ] = $v;

									ipsRegistry::$request[ $k ]	= $v;
								}
							}
						}
						
						break;
					}
				}
			}
			
			/* Reformat basic URL */
			if ( is_array( $_urlBits ) )
			{
				ipsRegistry::$settings['query_string_real'] = '';
				
				foreach( $_urlBits as $k => $v )
				{
					ipsRegistry::$settings['query_string_real'] .= '&' . $k . '=' . $v;
				}
				
				ipsRegistry::$settings['query_string_real'] = trim( ipsRegistry::$settings['query_string_real'], '&' );
			}
		}
	}

	/**
	 * Complete furls
	 * Redirects if settings permit
	 *
	 * @access	private
	 * @return	void
	 */
	private static function _fUrlComplete()
	{
		/* INIT */
		$_template = FALSE;
		
		if ( IPS_IS_TASK === TRUE AND IPS_IS_AJAX === TRUE )
		{
			return;
		}
		
		if ( ipsRegistry::$settings['use_friendly_urls'] AND ipsRegistry::$settings['seo_r_on'] AND is_array( self::$_seoTemplates ) AND self::$_uri )
		{
			/* Quick check */
			if ( strstr( self::$_uri, '=' ) )
			{
				/* Got a template? */
				foreach( self::$_seoTemplates as $key => $data )
				{
					if ( stristr( self::$_uri, $key ) )
					{
						$_template = $key;
						break;
					}
				}

				/* Goddit? */
				if ( $_template !== FALSE )
				{
					if ( self::$_seoTemplates[ $_template ]['app'] AND self::$_seoTemplates[ $_template ]['allowRedirect'] )
					{
						/* Load information file */
						$_class = 'furlRedirect_' . self::$_seoTemplates[ $_template ]['app'];

						if( file_exists( IPSLib::getAppDir( self::$_seoTemplates[ $_template ]['app'] ) . '/extensions/furlRedirect.php' ) )
						{
							require_once( IPSLib::getAppDir( self::$_seoTemplates[ $_template ]['app'] ) . '/extensions/furlRedirect.php' );
							$_furl = new $_class( ipsRegistry::instance() );

							$_furl->setKeyByUri( self::$_uri );
							$_seoTitle = $_furl->fetchSeoTitle();

							if ( $_seoTitle )
							{
								/* redirect... */
								$_send301 = ( ipsRegistry::$settings['seo_r301'] ) ? TRUE : FALSE;

								ipsRegistry::getClass('output')->silentRedirect( self::$_uri, $_seoTitle, $_send301 );
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Rebuild URL data from incoming sources.
	 * Called after _fUrlInit
	 *
	 * @access	protected
	 * @return	void
	 */
	protected static function _manageIncomingURLs()
	{
		//-----------------------------------------
		// Build master load requests
		//-----------------------------------------

		$_RESET = self::_fetchCoreVariables( 'redirect' );

		if ( is_array( $_RESET ) )
		{
			foreach( $_RESET as $k => $v )
			{
				$_GET[ $k ]  = $v;
				$_POST[ $k ] = $v;
				$_REQUEST[ $k ] = $v;
				ipsRegistry::$request[ $k ]	= $v;
			}
		}
	}

	/**
	 * Set up request redirect stuff
	 *
	 * @return	void
	 * @author	MattMecham
	 * @access	protected
	 * @todo 	[Future] Allow admin to set the default app (we default to forums presently)
	 */
	protected static function _setUpAppData()
	{
		# Finalize the app component constants
		$_appcomponent = preg_replace( "/[^a-zA-Z0-9\-\_]/", "" , ( isset( $_REQUEST['app'] ) && trim( $_REQUEST['app'] ) ? $_REQUEST['app'] : IPS_DEFAULT_APP ) );
		define( 'IPS_APP_COMPONENT', ( $_appcomponent ) ? $_appcomponent : IPS_DEFAULT_APP );
	}

	/**
	 * Parse any current app redirects
	 *
	 * @return void
	 */
	protected static function _parseAppResets()
	{
		//-----------------------------------------
		// Build app data and APP specific load requests
		//-----------------------------------------

		if ( IPS_AREA != 'admin' )
		{
			$_RESET = self::_fetchAppCoreVariables( IPS_APP_COMPONENT, 'redirect' );

			if ( is_array( $_RESET ) )
			{
				if ( is_array( $_RESET ) AND count( $_RESET ) )
				{
					foreach( $_RESET as $k => $v )
					{
						$_GET[ $k ]  = $v;

						self::$request[ $k ]	= $v;
					}
				}
			}
		}
	}

	/**
	 * Set up application data, etc.
	 *
	 * @return	void
	 * @author	MattMecham
	 * @access	protected
	 */
	protected static function _finalizeAppData()
	{
		//-----------------------------------------
		// Run the app class post output func
		//-----------------------------------------

		if ( isset( self::$app_class[ IPS_APP_COMPONENT ] ) && is_object( self::$app_class[ IPS_APP_COMPONENT ] ) && method_exists( self::$app_class[ IPS_APP_COMPONENT ], 'afterOutputInit' ) )
		{
			self::$app_class[ IPS_APP_COMPONENT ]->afterOutputInit( self::instance() );
		}

		//-----------------------------------------
		// Version numbers
		//-----------------------------------------

		if ( strstr( self::$acpversion , '.' ) )
		{
			list( $n, $b, $r ) = explode( ".", self::$acpversion );
		}
		else
		{
			$n = $b = $r = '';
		}

		self::$vn_full         = self::$acpversion;
		self::$acpversion      = $n;
		self::$vn_build_date   = $b;
		self::$vn_build_reason = $r;

		# Figure out default modules, etc
		$_module = IPSText::alphanumericalClean( ipsRegistry::$request['module'] );
		$_first  = '';

		# Set up other constants
		define( 'IPS_APPLICATION_PATH'  , IPSLib::getAppDir(  IPS_APP_COMPONENT ) . "/" );
		define( 'IPS_APP_CLASS_PATH'    , IPSLib::getAppDir(  IPS_APP_COMPONENT ) . '/sources/classes/' );

		//-----------------------------------------
		// Set up some defaults
		//-----------------------------------------

		ipsRegistry::$current_application  = IPS_APP_COMPONENT;

		if ( IPS_AREA == 'admin' )
		{
			//-----------------------------------------
			// Application: Do we have permission?
			//-----------------------------------------
			
			if( ipsRegistry::$request['module'] != 'login' )
			{
				ipsRegistry::getClass('class_permissions')->return = 0;
				ipsRegistry::getClass('class_permissions')->checkForAppAccess( IPS_APP_COMPONENT );
				ipsRegistry::getClass('class_permissions')->return = 1;
			}

			//-----------------------------------------
			// Got a module
			//-----------------------------------------

			if ( ipsRegistry::$request['module'] == 'ajax' )
			{
				$_module = 'ajax';
			}
			else
			{
				$fakeApps	= ipsRegistry::getClass('output')->fetchFakeApps();

				foreach( ipsRegistry::$modules as $app => $items )
				{
					if ( is_array( $items ) )
					{
						foreach( $items as $data )
						{
							if ( $data['sys_module_admin'] AND ( $data['sys_module_application'] == ipsRegistry::$current_application ) )
							{
								if ( ! $_first )
								{
									# Got permission for this one?
									ipsRegistry::getClass('class_permissions')->return = 1;

									if ( ipsRegistry::getClass('class_permissions')->checkForModuleAccess( $data['sys_module_application'], $data['sys_module_key'] ) === TRUE )
									{
										if ( is_dir( IPSLib::getAppDir( $data['sys_module_application'] ) . "/modules_admin/{$data['sys_module_key']}" ) === TRUE )
										{
											$isFakeApp	= false;

											foreach( $fakeApps as $tab => $apps )
											{
												foreach( $apps as $thisApp )
												{
													if( $thisApp['app'] == $app AND $thisApp['module'] == $data['sys_module_key'] )
													{
														$isFakeApp	= true;
													}
												}
											}

											if( !$isFakeApp )
											{
												$_first = $data['sys_module_key'];
											}
										}
									}

									ipsRegistry::getClass('class_permissions')->return = 0;
								}

								if ( ipsRegistry::$request['module'] == $data['sys_module_key'] )
								{
									$_module = $data['sys_module_key'];
									break;
								}
							}
						}
					}
				}
			}
		}
		else
		{
			//-----------------------------------------
			// Got a module?
			//-----------------------------------------

			if ( $_module == 'ajax' )
			{
				$_module = 'ajax';
			}
			else
			{
				foreach( ipsRegistry::$modules as $app => $items )
				{
					if ( is_array( $items ) )
					{
						foreach( $items as $data )
						{
							if ( ! $data['sys_module_admin'] AND ( $data['sys_module_application'] == ipsRegistry::$current_application ) )
							{
								if ( ! $_first )
								{
									$_first = $data['sys_module_key'];
								}

								if ( $_module == $data['sys_module_key'] )
								{
									$_module = $data['sys_module_key'];
									break;
								}
							}
						}
					}
				}
			}
		}

		//-----------------------------------------
		// Finish off...
		//-----------------------------------------

		ipsRegistry::$current_module  = ( $_module ) ? $_module : $_first;
		ipsRegistry::$current_section = ( ipsRegistry::$request['section'] ) ? ipsRegistry::$request['section'] : '';

		IPSDebug::addMessage( "Setting current module to: " . ipsRegistry::$current_module . " and current section to: " . ipsRegistry::$current_section );

		if ( IPS_AREA == 'admin' )
		{
			//-----------------------------------------
			// Module: Do we have permission?
			//-----------------------------------------

			ipsRegistry::getClass('class_permissions')->return = 0;
			ipsRegistry::getClass('class_permissions')->checkForModuleAccess( ipsRegistry::$current_application, ipsRegistry::$current_module );
			ipsRegistry::getClass('class_permissions')->return = 1;
		}
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
			throw new Exception( "Could not initiate the registry, the settings cache is empty or missing" );
		}

		foreach( $settings_cache as $k => $v )
		{
			ipsRegistry::$settings[$k] = $v;
		}

		//-----------------------------------------
		// Back up base URL
		//-----------------------------------------

		ipsRegistry::$settings['base_url']           = ( ipsRegistry::$settings['board_url'] ) ? ipsRegistry::$settings['board_url'] : ipsRegistry::$settings['base_url'];
		ipsRegistry::$settings['_original_base_url'] = ipsRegistry::$settings['base_url'];
		
		//-----------------------------------------
		// Fetch correct current URL
		//-----------------------------------------
		
		ipsRegistry::$settings['this_url']		 	= 'http://' . my_getenv('HTTP_HOST') . my_getenv('REQUEST_URI');
		
		//-----------------------------------------
		// Make a safe query string (we build the query string in furlinit)
		//-----------------------------------------

		ipsRegistry::$settings['query_string_safe'] = str_replace( '&amp;amp;', '&amp;', IPSText::parseCleanValue( urldecode( ipsRegistry::$settings['query_string_real'] ? ipsRegistry::$settings['query_string_real'] : my_getenv('QUERY_STRING') ) ) );
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

		if( ! defined( 'IPS_DOC_CHAR_SET' ) )
		{
			define( 'IPS_DOC_CHAR_SET', strtoupper( ipsRegistry::$settings['gb_char_set'] ) );
		}
		
		# Define cache path 
		define( 'IPS_CACHE_PATH', ( ! empty( ipsRegistry::$settings['ipb_cache_path'] ) ) ? ipsRegistry::$settings['ipb_cache_path'] : DOC_IPS_ROOT_PATH );
		
		# Make sure ENFORCE ACCESS is defined
		if ( ! defined( 'IPS_ENFORCE_ACCESS' ) )
		{
			define( 'IPS_ENFORCE_ACCESS', FALSE );
		}
		
		# If htaccess mod rewrite is on, enforce path_info usage
		ipsRegistry::$settings['url_type'] = ( ipsRegistry::$settings['htaccess_mod_rewrite'] ) ? 'path_info' : ipsRegistry::$settings['url_type'];

		# If captcha is set to 'none', switch off the main flag also. Saves checking throughout the code
		ipsRegistry::$settings['bot_antispam'] = ( ipsRegistry::$settings['bot_antispam_type'] == 'none' ) ? 0 : ipsRegistry::$settings['bot_antispam'];

		# Facebook receiver location
		ipsRegistry::$settings['fbc_xdlocation'] = ipsRegistry::$settings['_original_base_url'] . '/interface/facebook/xd_receiver.php';
		
		# If we can't write to our /cache/tmp directory, turn off minify., It would still work, but be horribly inefficient.
		if ( ipsRegistry::$settings['use_minify'] AND ! is_writeable( IPS_CACHE_PATH . 'cache/tmp' ) )
		{
			ipsRegistry::$settings['_use_minify'] = ipsRegistry::$settings['use_minify'];
			ipsRegistry::$settings['use_minify']  = 0;
		}
		
		# If we've turned on 301 redirects, then ensure headers are printed
		if ( ! ipsRegistry::$settings['print_headers'] )
		{
			if ( ipsRegistry::$settings['use_friendly_urls'] AND ipsRegistry::$settings['seo_r_on'] AND ipsRegistry::$settings['seo_r301'] )
			{
				ipsRegistry::$settings['print_headers'] = 1;
			}
		}
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

		# Apps
		$app_cache = self::$handles['caches']->getCache('app_cache');

		# Modules...
		self::$modules = self::$handles['caches']->getCache('module_cache');

		if ( ! count( $app_cache ) OR ! count( self::$modules ) )
		{
			self::$handles['caches']->rebuildCache( 'app_cache', 'global' );
			self::$handles['caches']->rebuildCache( 'module_cache', 'global' );

			$app_cache     = self::$handles['caches']->getCache('app_cache');
			self::$modules = self::$handles['caches']->getCache('module_cache');
		}

		//-----------------------------------------
		// Build app data and APP specific load requests
		//-----------------------------------------

		foreach( $app_cache as $_app_dir => $_app_data )
		{
			if ( IPS_AREA == 'public' && ! $_app_data['app_public_title'] )
			{
				continue;
			}

			$_app_data['app_public_title'] = ( $_app_data['app_public_title'] ) ? $_app_data['app_public_title'] : $_app_data['app_title'];
			self::$applications[ $_app_dir ] = $_app_data;
		}

		# Modules by section...
		foreach( self::$modules as $_app_dir => $_modules )
		{
			foreach( $_modules as $_data )
			{
				self::$modules_by_section[ $_app_dir ][ $_data['sys_module_key'] ] = $_data;
			}
		}

		//-----------------------------------------
		// System vars and group
		//-----------------------------------------

		$systemvars_cache = self::$handles['caches']->getCache('systemvars');

		if ( ! isset( $systemvars_cache ) OR ! isset( $systemvars_cache['task_next_run']) )
		{
			$update 						= array( 'task_next_run' => time() );
			$update['loadlimit'] 			= ( $systemvars_cache['loadlimit'] )           ? $systemvars_cache['loadlimit'] : 0;
			$update['mail_queue'] 			= ( $systemvars_cache['mail_queue'] )          ? $systemvars_cache['mail_queue'] : 0;
			$update['last_virus_check'] 	= ( $systemvars_cache['last_virus_check'] )    ? $systemvars_cache['last_virus_check'] : 0;
			$update['last_deepscan_check'] 	= ( $systemvars_cache['last_deepscan_check'] ) ? $systemvars_cache['last_deepscan_check'] : 0;

			self::$handles['caches']->setCache( 'systemvars', $update, array( 'deletefirst' => 1, 'donow' => 1, 'array' => 1 ) );
		}

		$group_cache = self::$handles['caches']->getCache('group_cache');

		if ( ! is_array( $group_cache ) OR ! count( $group_cache ) )
		{
			$this->cache()->rebuildCache( 'group_cache', 'global' );
		}

		//-----------------------------------------
		// User agent caches
		//-----------------------------------------

		$useragent_cache = $this->cache()->getCache('useragents');

		if ( ! is_array( $useragent_cache ) OR ! count( $useragent_cache ) )
		{
			$this->cache()->rebuildCache( 'useragents', 'global' );
		}

		//-----------------------------------------
		// Output formats
		//-----------------------------------------

		$outputformats_cache = $this->cache()->getCache('outputformats');

		if ( ! is_array( $outputformats_cache ) OR ! count( $outputformats_cache ) )
		{
			$this->cache()->rebuildCache( 'outputformats', 'global' );
		}

		//-----------------------------------------
		// Hooks cache
		//-----------------------------------------

		if ( $this->cache()->exists('hooks') !== TRUE )
		{
			$this->cache()->rebuildCache( 'hooks', 'global' );
		}
		
		//-----------------------------------------
		// Version numbers
		//-----------------------------------------

		$version_numbers = $this->cache()->getCache('vnums');

		if ( ! is_array( $version_numbers ) OR ! count( $version_numbers ) )
		{
			$this->cache()->rebuildCache( 'vnums', 'global' );
			
			$version_numbers = $this->cache()->getCache('vnums');
		}
		
		/* Set them */
		if ( ! defined('IPB_VERSION' ) )
		{
			define( 'IPB_VERSION'     , $version_numbers['human'] );
			ipsRegistry::$version	= IPB_VERSION;
		}
		
		if( !defined('IPB_LONG_VERSION') )
		{
			define( 'IPB_LONG_VERSION', $version_numbers['long'] );
			ipsRegistry::$acpversion	= IPB_LONG_VERSION;
			ipsRegistry::$vn_full		= IPB_LONG_VERSION;
		}
	}
}


/**
* Base Database class
*/
class ips_DBRegistry
{
	/**
	 * Database instance
	 *
	 * @access	private
	 * @var		object
	 **/
	private static $instance;

	/**
	 * Generic data storage for each class that extends ips_base_Registry
	 *
	 * @access	protected
	 * @var		array
	 **/
	protected static $data_store = array();

	/**
	 * Variables array
	 *
	 * @access	private
	 * @var		array
	 **/
	private static $vars = array();

	/**
	 * DB Objects
	 *
	 * @access	private
	 * @var		array
	 **/
	private static $dbObjects = array();

	/**
	 * DB Prefixes
	 *
	 * @access	private
	 * @var		array
	 **/
	private static $dbPrefixes = array( '__default__' => '' );

	/**
	 * DB Drivers
	 *
	 * @access	private
	 * @var		array
	 **/
	private static $dbDrivers = array( '__default__' => '' );

	/**
	 * DB key
	 *
	 * @access	private
	 * @var		string
	 **/
	private static $defaultKey = '__default__';
	
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
	 * Loaded query files
	
	/**
	 * Initialize singleton
	 *
	 * @access	public
	 * @return	object
	 **/
	static public function instance()
	{
		if ( ! self::$instance )
		{
			self::$instance = new self();
		}

		return self::$instance;
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
	
	/**
	 * Get DB reference based on key
	 *
	 * @access	public
	 * @param	string	Key
	 * @return	object
	 **/
	static public function getDB( $key='' )
	{
		$key = ( $key ) ? $key : self::$defaultKey;

		return self::$data_store[ $key ];
	}

	/**
	 * Get the prefix for this connection
	 *
	 * @access	public
	 * @param	string	Key
	 * @return	string	Prefix
	 **/
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
	 * @param	string  $key  Key of the db connection to check
	 * @return	string
	 **/
	static public function getDriverType( $key= '' )
	{
		/* Set Key */
		$key = ( $key ) ? $key : self::$defaultKey;

		return self::$dbDrivers[ $key ];
	}

	/**
	 * Set a database object
	 *
	 * @access	public
	 * @param	string	DB driver
	 * @param	string	Key
	 * @param	array   Array of settings( sql_database, sql_user, sql_pass, sql_host, sql_charset, sql_tbl_prefix ) and any other connection vars
	 * @return	void
	 **/
	static public function setDB( $db_driver, $key='', $settings=array() )
	{
		/* INIT */
		$db_driver        = strtolower( $db_driver );
		$query_file_extra = ( IPS_AREA == 'admin' ) ? '_admin' : '';
		$key              = ( $key ) ? $key : self::$defaultKey;
		
		/* Fix up settings */
		foreach( array( 'sql_database', 'sql_user', 'sql_pass', 'sql_host', 'sql_charset', 'sql_tbl_prefix' ) as $_s )
		{
			if ( ! isset( $settings[ $_s ] ) )
			{
				$settings[ $_s ] = isset( ipsRegistry::$settings[ $_s ] ) ? ipsRegistry::$settings[ $_s ] : '';
			}
		}
		
		/* Load main class core */
		if ( ! class_exists( 'db_driver_' . $db_driver ) )
		{
			require_once ( IPS_KERNEL_PATH . 'classDb' . ucwords($db_driver) . ".php" );
		}

		$classname = "db_driver_" . $db_driver;
		
		/* INIT Object */
		self::$dbObjects[ $key ] = new $classname;

		self::$dbObjects[ $key ]->obj['sql_database']         = $settings['sql_database'];
		self::$dbObjects[ $key ]->obj['sql_user']		      = $settings['sql_user'];
		self::$dbObjects[ $key ]->obj['sql_pass']             = $settings['sql_pass'];
		self::$dbObjects[ $key ]->obj['sql_host']             = $settings['sql_host'];
		self::$dbObjects[ $key ]->obj['sql_charset']          = $settings['sql_charset'];
		self::$dbObjects[ $key ]->obj['sql_tbl_prefix']       = $settings['sql_tbl_prefix'] ? $settings['sql_tbl_prefix'] : '';
		self::$dbObjects[ $key ]->obj['force_new_connection'] = ( $key != self::$defaultKey ) ? 1 : 0;
		self::$dbObjects[ $key ]->obj['use_shutdown']         = IPS_USE_SHUTDOWN;
		
		# Error log
		self::$dbObjects[ $key ]->obj['error_log']            = DOC_IPS_ROOT_PATH . 'cache/sql_error_log_'.date('m_d_y').'.cgi';
		self::$dbObjects[ $key ]->obj['use_error_log']        = IN_DEV ? 0 : 1;
		
		# Debug log - Don't use this on a production board!
		self::$dbObjects[ $key ]->obj['debug_log']            = DOC_IPS_ROOT_PATH . 'cache/sql_debug_log_'.date('m_d_y').'.cgi';
		self::$dbObjects[ $key ]->obj['use_debug_log']        = IN_DEV ? 0 : 0;

		/* Required vars? */
		if ( is_array( self::$dbObjects[ $key ]->connect_vars ) and count( self::$dbObjects[ $key ]->connect_vars ) )
		{
			foreach( self::$dbObjects[ $key ]->connect_vars as $k => $v )
			{
				self::$dbObjects[ $key ]->connect_vars[ $k ] = ( isset( $settings[ $k ] ) ) ? $settings[ $k ] : ipsRegistry::$settings[ $k ];
			}
		}

		/* Backwards compat */
		if ( ! self::$dbObjects[ $key ]->connect_vars['mysql_tbl_type'] )
		{
			self::$dbObjects[ $key ]->connect_vars['mysql_tbl_type'] = 'myisam';
		}

		/* Update settings */
		self::$dbPrefixes[ $key ] = self::$dbObjects[ $key ]->obj['sql_tbl_prefix'];
		self::$dbDrivers[ $key ]  = $db_driver;

		/* Get a DB connection */
		self::$dbObjects[ $key ]->connect();

		self::$data_store[ $key ] = self::$dbObjects[ $key ];
	}
}

/**
* Base application class
*/
class ips_CacheRegistry
{
	/**
	 * Database instance
	 *
	 * @access	private
	 * @var		object
	 **/
	private static $instance;

	/**
	 * Database instance
	 *
	 * @access	private
	 * @var		array
	 **/
	private $save_options  = array();

	/**
	* Static var for cache library
	*/
	private static $cacheLib;

	/**
	 * Debug information
	 *
	 * @access	private
	 * @var		array
	 **/
	public $debugInfo = array();

	/**
	 * Initialized flag
	 *
	 * @access	private
	 * @var		bool
	 **/
	private static $initiated = FALSE;

	/**
	 * Generic data storage
	 *
	 * @access	protected
	 * @var		array
	 **/
	protected static $data_store = array();

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
			self::$instance->init();
		}

		return self::$instance;
	}

	/**
	 * Initiate class
	 *
	 * @access	private
	 * @return	void
	 */
	private function init()
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

			else if( isset( ipsRegistry::$settings['use_diskcache'] ) AND ipsRegistry::$settings['use_diskcache'] == 1 )
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

		$requestedCaches	= $caches;
		$_seenKeys			= array();
		
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
						self::$data_store[ $key ] = unserialize( $temp_cache[$key] );
						
						//-----------------------------------------
						// Fallback fix if unserialize fails
						//-----------------------------------------
						
						if( !is_array(self::$data_store[ $key ]) OR !count(self::$data_store[ $key ]) )
						{
							$new_cache_array[] = $key;
							continue;
						}
					}
					else if( $temp_cache[$key] == "EMPTY" )
					{
						self::$data_store[ $key ] = NULL;
					}
					else
					{
						self::$data_store[ $key ] = $temp_cache[$key];
					}
					
					$_seenKeys[ $key ]	= $key;
				}
			}

			$caches = $new_cache_array;

			unset($new_cache_array, $temp_cache);
		}

		if( count($caches) )
		{
			//--------------------------------
			// Get from DB...
			//--------------------------------
	
			ipsRegistry::DB()->build( array( 'select' => '*', 'from' => 'cache_store', 'where' => "cs_key IN ( '" . implode( "','", $caches ) . "' )" ) );
			ipsRegistry::DB()->execute();

			while ( $r = ipsRegistry::DB()->fetch() )
			{
				$_seenKeys[ $r['cs_key'] ] = $r['cs_key'];
	
				if ( IN_DEV )
				{
					self::instance()->debugInfo[ $r['cs_key'] ] = array( 'size' => IPSLib::strlenToBytes( strlen($r['cs_value']) ) );
				}
	
				if ( $r['cs_array'] OR substr( $r['cs_value'], 0, 2 ) == "a:" )
				{
					self::$data_store[ $r['cs_key'] ] = unserialize( $r['cs_value'] );
	
					if ( ! is_array( self::$data_store[ $r['cs_key'] ] ) )
					{
						self::$data_store[ $r['cs_key'] ] = array();
					}
				}
				else
				{
					self::$data_store[ $r['cs_key'] ] = ( $r['cs_value'] ) ? $r['cs_value'] : NULL;
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

		foreach( $requestedCaches as $_cache )
		{
			if ( ! in_array( $_cache, $_seenKeys ) )
			{
				self::$data_store[ $_cache ] = NULL;
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
		self::$data_store[ $key ] = $val;

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
					$value = serialize(self::$data_store[ $key ]);
				}
				else
				{
					$value = self::$data_store[ $key ];
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

			if ( is_object( self::$cacheLib ) )
			{
				if ( ! $val )
				{
					$val = "EMPTY";
				}

				self::$cacheLib->updateInCache( $key, $val );
			}
		}

		/* Reest... */
		$this->save_options = array();
	}

	/**
	 * Grab item from cache library
	 *
	 * @access	public
	 * @param	string	Key
	 * @return	mixed	false, or cached value
	 */
	static public function getWithCacheLib( $key )
	{
		if ( is_object( self::$cacheLib ) )
		{
			return self::$cacheLib->getFromCache( $key );
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * Store into cache library
	 *
	 * @access	public
	 * @param	string	Key
	 * @param	mixed	Item to cache
	 * @param	int		Time to live
	 * @return	mixed
	 */
	static public function putWithCacheLib( $key, $value, $ttl=0 )
	{
		if ( is_object( self::$cacheLib ) )
		{
			return self::$cacheLib->putInCache( $key, $value, $ttl );
		}
		else
		{
			return FALSE;
		}
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
		return ( in_array( $key, array_keys( self::$data_store ) ) AND ( self::$data_store[ $key ] !== NULL ) ) ? TRUE : FALSE;
	}

	/**
 	 * Get the cache
	 * If the cache has not been loaded during init(), then it'll load the
	 * cache.
	 *
	 * @access	public
	 * @param	mixed	Cache key, or array of cache keys
	 * @return	mixed	Cache value if $keys is a string, else boolean (loaded successfully or not)
	 */
	static public function getCache( $keys )
	{
		if ( is_string( $keys ) )
		{
			if ( ! in_array( $keys, array_keys( self::$data_store ) ) )
			{
				self::_loadCaches( array( $keys ) );
			}
		
			return self::$data_store[ $keys ];
		}
		elseif ( is_array( $keys ) && count( $keys ) )
		{
			$toLoad = array();
			
			foreach( $keys as $key )
			{
				if ( ! in_array( $key, array_keys( self::$data_store ) ) )
				{
					$toLoad[] = $key;
				}
			}
			
			self::_loadCaches( $toLoad );
			
			return TRUE;
		}
		
		return FALSE;
	}

	/**
	 * Fetch all the caches as a reference
	 *
	 * @access	public
	 * @return	array
	 */
	static public function &fetchCaches()
	{
		return self::$data_store;
	}

	/**
 	 * Update a cache key without saving it to the db
	 *
	 * @access	public
	 * @param	string	Cache key
	 * @param	mixed	Value
	 * @return	void
	 */
	static public function updateCacheWithoutSaving( $key, $value )
	{
		self::$data_store[ $key ] = $value;
	}

	/**
 	 * Store an updated cache value
	 *
	 * @access	public
	 * @param	string	Cache key
	 * @param	mixed	Value
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
 	 * Rebuild a cache using defined $CACHE settings in it's extensions file
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
			/* Get all caches from all apps */
			$_caches = ipsRegistry::_fetchCoreVariables( 'cache' );
			
			foreach( ipsRegistry::$applications as $appDir => $appData )
			{
				$CACHE = ipsRegistry::_fetchAppCoreVariables( $appDir, 'cache' );
				
				if ( is_array( $CACHE ) )
				{
					$_caches = array_merge( $_caches, $CACHE );
				}
				
				/* Now loop.. */
				foreach( $_caches as $_name => $_data )
				{
					if ( $_name == $key )
					{
						$file_to_check = $_caches[ $key ]['recache_file'];

						if ( $file_to_check AND file_exists( $file_to_check ) )
						{
							$_class = $_caches[ $key ]['recache_class'];
							$_func  = $_caches[ $key ]['recache_function'];

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
	}
}

/**
* Dummy class to prevent errors if cache()->rebuild is required during init()
*/
class ips_MemberRegistryDummy
{
	/**
	 * Database instance
	 *
	 * @access	private
	 * @var		object
	 **/
	private static $instance;
	
	static public function instance()
	{
		if ( ! self::$instance )
		{
			self::$instance = new self();
		}

		return self::$instance;
	}
	
	/**
	 * Fetch all the member data as a reference
	 *
	 * @access	public
	 * @return	array
	 */
	static public function &fetchMemberData()
	{
		return array();
	}
}

/**
* Base application class
*/
class ips_MemberRegistry
{
	/**
	 * Database instance
	 *
	 * @access	private
	 * @var		object
	 **/
	private static $instance;

	/**
	 * Member settings
	 *
	 * @access	private
	 * @var		array
	 **/
	private $settings = array();

	/**
	 * Member info
	 *
	 * @access	protected
	 * @var		array
	 **/
	protected static $member   = array();

	/**
	 * Member ID
	 *
	 * @access	public
	 * @var		integer
	 **/
	public $member_id;

	/**
	 * Perm mask ID
	 *
	 * @access	public
	 * @var		integer
	 **/
	public $perm_id;

	/**
	 * Array of perm masks
	 *
	 * @access	public
	 * @var		array
	 **/
	public $perm_id_array = array();

	/**
	 * Unique form hash
	 *
	 * @access	public
	 * @var		string
	 **/
	public $form_hash     = '';

	/**
	 * Language ID to use
	 *
	 * @access	public
	 * @var		integer
	 **/
	public $language_id   = '1';

	/**
	 * Skin ID to use
	 *
	 * @access	public
	 * @var		integer
	 **/
	public $skin_id       = '';

	/**
	 * Preferences
	 *
	 * @access	public
	 * @var		array
	 **/
	public $preferences   = array();

	/**#@+
	 * Member's session data
	 *
	 * @access	public
	 * @var		mixed	integer, string, array
	 */
	public $session_id   = 0;
	public $session_type = '';
	public $ip_address   = '';
	public $last_click   = 0;
	public $location     = '';
	public $acp_tab_data = array();
	/**#@-*/

	/**#@+
	 * Environment/browser data
	 *
	 * @access	public
	 * @var		mixed	integer, string, array
	 */
	public $can_use_fancy_js = 0;
	public $user_agent;
	public $browser          = array();
	public $operating_system = 'unknown';
	public $is_not_human     = 0;
	/**#@-*/

	/**
	 * Ignored users
	 *
	 * @access	public
	 * @var		array
	 **/
	public $ignored_users	= array();

	/**
	 * Sessions class
	 *
	 * @access	private
	 * @var		object
	 **/
	private static $session_class;

	/**
	 * Initiated
	 *
	 * @access	private
	 * @var		false
	 **/
	private static $initiated    = FALSE;
	protected static $data_store = array();


	/**
	 * Initialization method
	 *
	 * @access	public
	 * @return	object
	 **/
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
	 **/
	public function __myDestruct()
	{
		/* Item marking clean up */
		ipsRegistry::getClass('classItemMarking')->__myDestruct();

		/* Session clean up */
		self::sessionClass()->__myDestruct();
	}

	/**
	 * Singleton init method
	 *
	 * @access	protected
	 * @return	void
	 **/
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

			//-----------------------------------------
			// Get user-agent, browser and OS
			//-----------------------------------------

			self::instance()->user_agent       = IPSText::parseCleanValue( my_getenv('HTTP_USER_AGENT') );
			self::instance()->operating_system = self::_fetch_os();

			if ( IPS_AREA == 'admin' )
			{
				require_once( IPS_ROOT_PATH . "sources/classes/session/adminSessions.php" );

				/**
				 * Support for extending the session class
				 */
				if( file_exists( IPS_ROOT_PATH . "sources/classes/session/ssoAdminSessions.php" ) )
				{
					require_once( IPS_ROOT_PATH . "sources/classes/session/ssoAdminSessions.php" );

					/**
					 * Does the ssoAdminSessions class exist?
					 */
					if( class_exists( 'ssoAdminSessions' ) )
					{
						$parent = get_parent_class( 'ssoAdminSessions' );

						/**
						 * Is it a child of adminSessions
						 */
						if( $parent == 'adminSessions' )
						{
							self::$session_class = new ssoAdminSessions();
						}
						else
						{
							self::$session_class = new adminSessions();
						}
					}
				}
				else
				{
					self::$session_class = new adminSessions();
				}
			}
			else
			{
				require_once( IPS_ROOT_PATH . 'sources/classes/session/publicSessions.php' );

				/**
				 * Support for extending the session class
				 */
				if( file_exists( IPS_ROOT_PATH . "sources/classes/session/ssoPublicSessions.php" ) )
				{
					require_once( IPS_ROOT_PATH . "sources/classes/session/ssoPublicSessions.php" );

					/**
					 * Does the ssoPublicSessions class exist?
					 */
					if( class_exists( 'ssoPublicSessions' ) )
					{
						$parent = get_parent_class( 'ssoPublicSessions' );

						/**
						 * Is it a child of publicSessions
						 */
						if( $parent == 'publicSessions' )
						{
							self::$session_class = new ssoPublicSessions();
						}
						else
						{
							self::$session_class = new publicSessions();
						}
					}
				}
				else
				{
					self::$session_class = new publicSessions();
				}

				//-----------------------------------------
				// Set other
				//-----------------------------------------

				self::$data_store['publicSessionID']  = self::$session_class->session_data['id'];
			}
			
			//-----------------------------------------
			// Set user agent
			//-----------------------------------------
			
			$_cookie = IPSCookie::get("uagent_bypass");
			
			self::$data_store['userAgentKey']     = self::$session_class->session_data['uagent_key'];
			self::$data_store['userAgentType']    = self::$session_class->session_data['uagent_type'];
			self::$data_store['userAgentVersion'] = self::$session_class->session_data['uagent_version'];
			self::$data_store['userAgentBypass']  = ( $_cookie ) ? true : self::$session_class->session_data['uagent_bypass'];
			
			//-----------------------------------------
			// Can use RTE?
			//-----------------------------------------

			self::$data_store['_canUseRTE'] = FALSE;
			
			if ( self::$data_store['userAgentKey'] == 'explorer' AND self::$data_store['userAgentVersion'] >= 6 )
			{
				self::$data_store['_canUseRTE'] = TRUE;
			}
			else if ( self::$data_store['userAgentKey'] == 'opera' AND self::$data_store['userAgentVersion'] >= 9.00 )
			{
				self::$data_store['_canUseRTE'] = TRUE;
			}
			else if ( self::$data_store['userAgentKey'] == 'firefox' AND self::$data_store['userAgentVersion'] >= 2 )
			{
				self::$data_store['_canUseRTE'] = TRUE;
			}
			
			if( !ipsRegistry::$settings['posting_allow_rte'] )
			{
				self::$data_store['_canUseRTE'] = FALSE;
			}
	
    		//-----------------------------------------
    		// Can use FBC?
    		//-----------------------------------------
			
			if ( ipsRegistry::$settings['fbc_enable'] )
			{
				ipsRegistry::$settings['fbc_enable'] = 0;
				
				if ( self::$data_store['userAgentKey'] == 'explorer' AND self::$data_store['userAgentVersion'] >= 6 )
				{
					ipsRegistry::$settings['fbc_enable'] = 1;
				}
				else if ( self::$data_store['userAgentKey'] == 'safari' AND self::$data_store['userAgentVersion'] >= 3 )
				{
					ipsRegistry::$settings['fbc_enable'] = 1;
				}
				else if ( self::$data_store['userAgentKey'] == 'firefox' AND self::$data_store['userAgentVersion'] >= 3 )
				{
					ipsRegistry::$settings['fbc_enable'] = 1;
				}
			}
		}
	}

	/**
	 * Sessions class interface
	 *
	 * @access	public
	 * @return	object
	 **/
	public static function sessionClass()
	{
		return self::$session_class;
	}

	/**
	 * Get a member property
	 *
	 * @access	public
	 * @param	string	Key
	 * @return	mixed	Member data
	 **/
	static public function getProperty( $key )
	{
		return self::$data_store[ $key ];
	}

	/**
	 * Set a member property.  DOES NOTE SAVE IT TO DB.
	 *
	 * @access	public
	 * @param	string	Key
	 * @param	mixed	Value
	 * @return	mixed	Member data
	 **/
	static public function setProperty( $key, $value )
	{
		return self::$data_store[ $key ] = $value;
	}

	/**
	 * Fetch all the member data as a reference
	 *
	 * @access	public
	 * @return	array
	 */
	static public function &fetchMemberData()
	{
		return self::$data_store;
	}

	/**
	 * Sets up a search engine's data and permissions
	 *
	 * @access	public
	 * @param	array 		array of useragent information
	 * @return	void
	 */
	static public function setSearchEngine( $uAgent )
	{
		$cache = ipsRegistry::cache()->getCache('group_cache');
		$group = $cache[ intval( ipsRegistry::$settings['spider_group'] ) ];

		foreach ( $group as $k => $v )
		{
			self::$data_store[ $k ] = $v;
		}

		/* Fix up member and group data */
		self::$data_store['members_display_name']  = $uAgent['uagent_name'];
		self::$data_store['_members_display_name'] = $uAgent['uagent_name'];
		self::$data_store['name'] 				   = $uAgent['uagent_name'];
		self::$data_store['member_group_id']	      = self::$data_store['g_id'];
		self::$data_store['restrict_post']         = 1;
		self::$data_store['g_use_search']	      = 0;
		self::$data_store['g_email_friend']	      = 0;
		self::$data_store['g_edit_profile']	      = 0;
		self::$data_store['g_use_pm']		      = 0;
		self::$data_store['g_is_supmod']		      = 0;
		self::$data_store['g_access_cp']		      = 0;
		self::$data_store['g_access_offline']      = 0;
		self::$data_store['g_avoid_flood']	      = 0;
		self::$data_store['g_post_new_topics']     = 0;
		self::$data_store['g_reply_own_topics']    = 0;
		self::$data_store['g_reply_other_topics']  = 0;
		self::$data_store['member_id']		      = 0;
		self::$data_store['_cache']			      = array();
		self::$data_store['_cache']['friends']     = array();

		/* Fix up permission strings */
		self::instance()->perm_id       = $group['g_perm_id'];
        self::instance()->perm_id_array = explode( ",", $group['g_perm_id'] );

		/* It's allliiiiiiveeeeee */
		self::instance()->is_not_human  = true;

		/* Logging? */
		if ( ipsRegistry::$settings['spider_visit'] )
		{
			ipsRegistry::DB()->insert( 'spider_logs', array (
																'bot'		   => $uAgent['uagent_key'],
																'query_string' => htmlentities( strip_tags( str_replace( '\\', '',  str_replace( "'", "", my_getenv('QUERY_STRING')) ) ) ),
																'ip_address'   => self::instance()->ip_address,
																'entry_date'   => time(),
													), true		);
		}
	}

	/**
	 * Set current member to the member ID specified
	 *
	 * @access	public
	 * @param	integer	Member ID
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
			self::$data_store = IPSMember::load( $member_id, 'extendedProfile,customFields,groups,itemMarkingStorage' );

			if ( self::$data_store['member_id'] )
			{
				self::setUpMember();

				# Form hash
				self::instance()->form_hash = md5( self::$data_store['email'].'&'.self::$data_store['member_login_key'].'&'.self::$data_store['joined'] );
			}
			else
			{
				self::$data_store = IPSMember::setUpGuest();

				self::instance()->perm_id       = ( isset(self::$data_store['org_perm_id']) AND self::$data_store['org_perm_id'] ) ? self::$data_store['org_perm_id'] : self::$data_store['g_perm_id'];
				self::instance()->perm_id_array = explode( ",", self::instance()->perm_id );

				# Form hash
				self::instance()->form_hash = md5("this is only here to prevent it breaking on guests");
			}

			/* Get the ignored users */
			/* @todo [Future] Cache this into the column in the members table? */

			if( IPS_AREA == 'public' )
			{
				/* Ok, Fetch ignored users */
				self::instance()->ignored_users = IPSMember::fetchIgnoredUsers( self::$data_store );
			}
		}
		else
		{
			self::$data_store = IPSMember::setUpGuest();

			self::instance()->perm_id       = ( isset(self::$data_store['org_perm_id']) AND self::$data_store['org_perm_id'] ) ? self::$data_store['org_perm_id'] : self::$data_store['g_perm_id'];
			self::instance()->perm_id_array = explode( ",", self::instance()->perm_id );

			# Form hash
			self::instance()->form_hash = md5("this is only here to prevent it breaking on guests");

			//IPSCookie::set( "member_id" , "0", -1  );
			//IPSCookie::set( "pass_hash" , "0", -1  );
		}

		if( self::$data_store['member_id'] )
		{
			self::instance()->language_id	= self::$data_store['language'];
		}
		else if( IPSCookie::get('language') )
		{
			self::instance()->language_id	= IPSCookie::get('language');
		}

		//-----------------------------------------
		// Set member data
		//-----------------------------------------

		self::instance()->member_id = $member_id;
	}

	/**
	 * Fetches the user's operating system
	 *
	 * @access	private
	 * @return	string
	 */
	static private function _fetch_os()
	{
		$useragent = strtolower(my_getenv('HTTP_USER_AGENT'));

		if ( strstr( $useragent, 'mac' ) )
		{
			return 'mac';
		}

		if ( preg_match( '#wi(n|n32|ndows)#', $useragent ) )
		{
			return 'windows';
		}

		return 'unknown';
	}

	/**
	 * Set up a member's secondary groups
	 *
	 * @access	public
	 * @param	array 	Member data
	 * @return	array 	Member data with secondary group perms set properly
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
	 * Update the member's session
	 *
	 * @access	public
	 * @param	array 		Array of data to update
	 * @return	void
	 */
	static public function updateMySession( $data )
	{
		self::$session_class->updateSession( self::$data_store['publicSessionID'], self::$data_store['member_id'], $data );
	}

	/**
	 * Finalize public member
	 *
	 * Now that everything has loaded, lets do the final set up
	 *
	 * @access	public
	 * @return	void
	 */
	static public function finalizePublicMember()
	{
		/* Build profile picture */
		self::$data_store = IPSMember::buildProfilePhoto( self::$data_store );

		/* SEO Name */
		if ( ! self::$data_store['members_seo_name'] )
		{
			self::$data_store['members_seo_name'] = IPSMember::fetchSeoName( self::$data_store );
		}
	}

	/**
	 * Set up a member
	 *
	 * @access	private
	 * @return	void
	 */
	static private function setUpMember()
    {
		//-----------------------------------------
        // INIT
        //-----------------------------------------

        $cache = ipsRegistry::cache()->getCache('group_cache');

		//-----------------------------------------
		// Unpack cache
		//-----------------------------------------

		if ( isset(self::$data_store['members_cache']) )
		{
			self::$data_store['_cache'] = IPSMember::unpackMemberCache( self::$data_store['members_cache'] );
		}
		else
		{
			self::$data_store['_cache'] = array();
		}

		if ( ! isset( self::$data_store['_cache']['friends'] ) or ! is_array( self::$data_store['_cache']['friends'] ) )
		{
			self::$data_store['_cache']['friends'] = array();
		}

		//-----------------------------------------
        // Set up main 'display' group
        //-----------------------------------------

        if( is_array( $cache[ self::$data_store['member_group_id'] ] ) )
        {
        	self::$data_store = array_merge( self::$data_store, $cache[ self::$data_store['member_group_id'] ] );
    	}

		//-----------------------------------------
		// Work out permissions
		//-----------------------------------------

		self::$data_store = self::instance()->setUpSecondaryGroups( self::$data_store );

		self::instance()->perm_id       = ( isset(self::$data_store['org_perm_id']) AND self::$data_store['org_perm_id'] ) ? self::$data_store['org_perm_id'] : self::$data_store['g_perm_id'];
        self::instance()->perm_id_array = explode( ",", self::instance()->perm_id );

        //-----------------------------------------
        // Synchronise the last visit and activity times if
        // we have some in the member profile
        //-----------------------------------------

        if ( ! self::$data_store['last_activity'] )
       	{
       		self::$data_store['last_activity'] = IPS_UNIX_TIME_NOW;
       	}

		//-----------------------------------------
		// If there hasn't been a cookie update in 2 hours,
		// we assume that they've gone and come back
		//-----------------------------------------

		if ( ! self::$data_store['last_visit'] )
		{
			//-----------------------------------------
			// No last visit set, do so now!
			//-----------------------------------------

			ipsRegistry::DB()->update( 'members', array( 'last_visit' => self::$data_store['last_activity'], 'last_activity' => IPS_UNIX_TIME_NOW ), "member_id=".self::$data_store['member_id'], true );
			self::$data_store['last_visit'] = self::$data_store['last_activity'];

		}
		else if ( ( IPS_UNIX_TIME_NOW  - self::$data_store['last_activity']) > 300 )
		{
			//-----------------------------------------
			// If the last click was longer than 5 mins ago and this is a member
			// Update their profile.
			//-----------------------------------------

			list( $be_anon, $loggedin ) = explode( '&', self::$data_store['login_anonymous'] );

			ipsRegistry::DB()->update( 'members', array( 'login_anonymous' => "{$be_anon}&1", 'last_activity' => IPS_UNIX_TIME_NOW ), 'member_id=' . self::$data_store['member_id'], true );
		}
		
		//-----------------------------------------
		// Group promotion based on time since joining
		//-----------------------------------------
		
		/* Are we checking for auto promotion? */
		if ( self::$data_store['g_promotion'] != '-1&-1' )
		{
			/* Are we checking for post based auto incrementation? 0 is post based, 1 is date based, so...  */
			if ( self::$data_store['gbw_promote_unit_type'] )
			{
				list($gid, $gdate) = explode( '&', self::$data_store['g_promotion'] );
			
				if ( $gid > 0 and $gdate > 0 )
				{
					if ( self::$data_store['joined'] <= ( time() - ( $gdate * 86400 ) ) )
					{
						IPSMember::save( self::$data_store['member_id'], array( 'core' => array( 'member_group_id' => $gid ) ) );
						
						/* Now reset the members group stuff */
						self::$data_store = array_merge( self::$data_store, $cache[ $gid ] );
						
						self::$data_store = self::instance()->setUpSecondaryGroups( self::$data_store );

						self::instance()->perm_id       = ( isset(self::$data_store['org_perm_id']) AND self::$data_store['org_perm_id'] ) ? self::$data_store['org_perm_id'] : self::$data_store['g_perm_id'];
				        self::instance()->perm_id_array = explode( ",", self::instance()->perm_id );
					}
				}
			}
		}
	
		//-----------------------------------------
		// Fix up some preferences
		//-----------------------------------------

		$ppu = 0;
		$tpu = 0;

		if ( isset( self::$data_store['view_prefs'] ) )
		{
			list($ppu,$tpu) = explode( "&", self::$data_store['view_prefs'] );
		}

		ipsRegistry::$settings[ 'display_max_topics'] =  ($tpu > 0 ? $tpu : ipsRegistry::$settings['display_max_topics'] );
		ipsRegistry::$settings[ 'display_max_posts' ] =  ($ppu > 0 ? $ppu : ipsRegistry::$settings['display_max_posts'] );

		//-----------------------------------------
		// Knock out some large text fields we don't need
		// on a day-to-day basis.
		//-----------------------------------------

		unset( self::$data_store['notes'], self::$data_store['bio'], self::$data_store['links'] );
    }
}