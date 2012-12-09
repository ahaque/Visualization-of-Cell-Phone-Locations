<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Public session handler
 * Last Updated: $Date: 2009-08-18 16:46:02 -0400 (Tue, 18 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @since		26th January 2004
 * @version		$Revision: 5027 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class publicSessions extends ips_MemberRegistry
{
	/**
	 * SSO object (if present)
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $sso;

	/**
	 * User agent trimmed
	 *
	 * @access	private
	 * @var		string
	 */
	private $_userAgent;

	/**
	 * Session recorded flag
	 *
	 * @var		boolean
	 * @access	public
	 */
	public $session_recorded		= FALSE;

	/**
	 * Current time
	 *
	 * @var		integer
	 * @access	public
	 */
	public $time_now				= 0;

	/**
	 * Dead session id
	 *
	 * @var		string
	 * @access	public
	 */
	public $session_dead_id			= null;

	/**
	 * Current session id
	 *
	 * @var		string
	 * @access	public
	 */
	public $session_id				= null;

	/**
	 * Current session data
	 *
	 * @var		array
	 * @access	public
	 */
	public $session_data            = array();

	/**
	 * Type of session (either url or cookie)
	 *
	 * @var		string
	 * @access	public
	 */
	public $session_type			= 'cookie';

	/**
	 * Session member id
	 *
	 * @var		integer
	 * @access	public
	 */
	public $session_user_id			= 0;

	/**
	 * Session member password
	 *
	 * @var		string
	 * @access	public
	 */
	public $session_user_pass		= "";

	/**
	 * Update the session
	 *
	 * @var		boolean
	 * @access	public
	 */
	public $do_update				= true;

	/**
	 * Spider ua mapping
	 *
	 * @var		array
	 * @access	public
	 */
	public $bot_map					= array();

	/**
	 * Current application
	 *
	 * @var		string
	 * @access	public
	 */
  	public $current_appcomponent;

	/**
	 * Current module
	 *
	 * @var		string
	 * @access	public
	 */
	public $current_module;

	/**
	 * Current section
	 *
	 * @var		string
	 * @access	public
	 */
	public $current_section;

	/**
	 * Registry object
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $registry;

	/**
	 * Database object
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $DB;

	/**
	 * Settings object
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $settings;

	/**
	 * Request object
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $request;

	/**
	 * Language object
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $lang;

	/**
	 * Cache object
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $cache;

	/**
	 * Sessions to be destroyed
	 *
	 * @access	private
	 * @var		array
	 */
	private $_sessionsToKill = array();

	/**
	 * Sessions to be updated
	 *
	 * @access	private
	 * @var		array
	 */
	private $_sessionsToSave = array();

	/**
	 * Session data of the session that didn't authorize
	 *
	 * @access	private
	 * @var		array
	 */
	private $_failedAuthorizationSessionData = array();
	
	/**
	 * Delete sessions immediately?
	 *
	 * @access	private
	 * @var		boolean
	 */
	private $_deleteNow;

	/**
	 * Session Query Override Keys
	 * Allows one to override a session value
	 * Useful for overriding variables that are not set up when the session -> _getLocationSettings is run
	 *
	 * So to populate 'location_key_1' later in script execution:
	 * You'd use:
	 * ipsRegistry::member()->sessionClass()->addQueryKey( 'location_key_1', ipsRegistry::$request['f'] );
	 *
	 * @access	private
	 * @var		array
	 */
	private $_queryOverride = array();
	
	/**
	 * Constructor :: Authorizes the session
	 *
	 * @access	public
	 * @return	mixed		Void normally, but can print error message
	 */
	public function __construct()
	{
		/* Make object */
		$this->registry    =  ipsRegistry::instance();
		$this->DB	       =  $this->registry->DB();
		$this->settings    =& $this->registry->fetchSettings();
		$this->request     =& $this->registry->fetchRequest();
		$this->cache	   =  $this->registry->cache();
		$this->caches      =& $this->registry->cache()->fetchCaches();
		$this->_member     =  self::instance();
		$this->_memberData =& self::instance()->fetchMemberData();
		
		/* Delete immediately */
		$this->_deleteNow = true;
		
		/**
		 * If the sso.php file is present in this folder, we'll load it.
		 * This file can be used to easily integrate single-sign on in
		 * situations where you need to check session data
		 */
		if( file_exists( IPS_ROOT_PATH . '/sources/classes/session/sso.php' ) )
		{
			require_once( IPS_ROOT_PATH . '/sources/classes/session/sso.php' );

			if( class_exists( "ssoSessionExtension" ) )
			{
				$this->sso	= new ssoSessionExtension( $this->registry );
			}
		}

		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$cookie = array();

		$this->_userAgent = substr( $this->_member->user_agent, 0, 200 );

		//-----------------------------------------
		// Fix up app / section / module
		//-----------------------------------------

		$this->current_appcomponent	= IPS_APP_COMPONENT;
		$this->current_module		= IPSText::alphanumericalClean( $this->request['module'] );
		$this->current_section		= IPSText::alphanumericalClean( $this->request['section'] );

		$this->settings['session_expiration'] = ( $this->settings['session_expiration'] ) ? $this->settings['session_expiration'] : 3600;

		//-----------------------------------------
		// Return as guest if running a task
		//-----------------------------------------
	
		if ( IPS_IS_TASK )
		{
			self::$data_store	   = IPSMember::setUpGuest();
			self::$data_store['last_activity'] =  time();
			self::$data_store['last_visit']    =  time();

			return true;
		}

		//-----------------------------------------
		// no new headers if we're simply viewing an attachment..
		//-----------------------------------------

		if ( $this->request['section'] == 'attach' )
		{
			$this->settings['no_print_header'] =  1 ;
		}

		//-----------------------------------------
		// no new headers if we're updating chat
		//-----------------------------------------

		if ( ( IPS_IS_AJAX && $this->request['section'] != 'login' )
				OR $this->request['section'] == 'attach'
				OR $this->request['section'] == 'captcha' )
		{
			$this->settings['no_print_header'] =  1 ;
			$this->do_update		= 0;
		}

		//-----------------------------------------
		// Continue!
		//-----------------------------------------

		$cookie['session_id']	= IPSCookie::get('session_id');
		$cookie['member_id']	= IPSCookie::get('member_id');
		$cookie['pass_hash']	= IPSCookie::get('pass_hash');

		if ( $cookie['session_id'] )
		{
			$this->getSession($cookie['session_id']);
			$this->session_type = 'cookie';
		}
		elseif ( isset( $this->request['s'] ) AND $this->request['s'] )
		{
			$this->getSession($this->request['s']);
			$this->session_type = 'url';
		}
		else
		{
			$this->session_id = 0;
		}

		//-----------------------------------------
		// Do we have a valid session ID?
		//-----------------------------------------

		if ( $this->session_id )
		{
			//-----------------------------------------
			// We've checked the IP addy and browser, so we can assume that this is
			// a valid session.
			//-----------------------------------------

			if ( ($this->session_user_id != 0) and ( ! empty($this->session_user_id) ) )
			{
				//-----------------------------------------
				// It's a member session, so load the member.
				//-----------------------------------------

				self::setMember( $this->session_user_id );

				//-----------------------------------------
				// Did we get a member?
				//-----------------------------------------

				if ( ( ! self::$data_store['member_id']) or (self::$data_store['member_id'] == 0) )
				{
					$this->_updateGuestSession();

					/**
					 * If we have an SSO object, run it for the update guest session call
					 */
					if( is_object( $this->sso ) AND method_exists( $this->sso, 'checkSSOForGuest' ) )
					{
						$this->sso->checkSSOForGuest( 'update' );
					}
				}
				else
				{
					$this->_updateMemberSession();

					/**
					 * If we have an SSO object, run it for the update member call
					 */
					if( is_object( $this->sso ) AND method_exists( $this->sso, 'checkSSOForMember' ) )
					{
						$this->sso->checkSSOForMember( 'update' );
					}
				}
			}
			else
			{
				$this->_updateGuestSession();

				/**
				 * If we have an SSO object, run it for the update guest call
				 */
				if( is_object( $this->sso ) AND method_exists( $this->sso, 'checkSSOForGuest' ) )
				{
					$this->sso->checkSSOForGuest( 'update' );
				}
			}
		}
		else
		{
			//-----------------------------------------
			// We didn't have a session, or the session didn't validate
			// Do we have cookies stored?
			//-----------------------------------------

			if ( $cookie['member_id'] != "" and $cookie['pass_hash'] != "" )
			{
				//-----------------------------------------
				// Load member
				//-----------------------------------------

				self::setMember( $cookie['member_id'] );

				//-----------------------------------------
				// INIT log in key stuff
				//-----------------------------------------

				$_ok		= 1;
				$_days		= 0;
				$_sticky	= 1;
				$_time		= ( $this->settings['login_key_expire'] ) ? ( time() + ( intval($this->settings['login_key_expire']) * 86400 ) ) : 0;

				if ( ( ! self::$data_store['member_id']) or (self::$data_store['member_id'] == 0) )
				{
					$this->_createGuestSession();

					/**
					 * If we have an SSO object, run it for the create guest call
					 */
					if( is_object( $this->sso ) AND method_exists( $this->sso, 'checkSSOForGuest' ) )
					{
						$this->sso->checkSSOForGuest( 'create' );
					}
				}
				else
				{
					if ( self::$data_store['member_login_key'] == $cookie['pass_hash'] )
					{

						//-----------------------------------------
						// Key expired?
						//-----------------------------------------

						if ( $this->settings['login_key_expire'] )
						{
							$_sticky	= 0;
							$_days		= $this->settings['login_key_expire'];

							if ( time() > self::$data_store['member_login_key_expire'] )
							{
								$_ok	= 0;
							}
						}

						if ( $_ok == 1 )
						{
							$this->_createMemberSession();

							/**
							 * If we have an SSO object, run it for the create member call
							 */
							if( is_object( $this->sso ) AND method_exists( $this->sso, 'checkSSOForMember' ) )
							{
								$this->sso->checkSSOForMember( 'create' );
							}

							//-----------------------------------------
							// Change the log in key to make each authentication
							// use a unique token. This means that if a cookie is
							// stolen, the hacker can only use the auth once.
							//-----------------------------------------

							if ( $this->settings['login_change_key'] )
							{
								self::$data_store['member_login_key'] = IPSMember::generateAutoLoginKey();

								IPSMember::save( self::$data_store['member_id'], array( 'core' => array( 'member_login_key' => self::$data_store['member_login_key'], 'member_login_key_expire' => $_time ) ) );

								IPSCookie::set( "pass_hash", self::$data_store['member_login_key'], $_sticky, $_days );
							}
						}
						else
						{
							self::setMember(0);
							$this->_createGuestSession();

							/**
							 * If we have an SSO object, run it for the create guest call
							 */
							if( is_object( $this->sso ) AND method_exists( $this->sso, 'checkSSOForGuest' ) )
							{
								$this->sso->checkSSOForGuest( 'create' );
							}
						}
					}
					else
					{
						self::setMember(0);
						$this->_createGuestSession();

						/**
						 * If we have an SSO object, run it for the create guest call
						 */
						if( is_object( $this->sso ) AND method_exists( $this->sso, 'checkSSOForGuest' ) )
						{
							$this->sso->checkSSOForGuest( 'create');
						}
					}
				}
			}
			else
			{
				$this->_createGuestSession();

				/**
				 * If we have an SSO object, run it for the create guest call
				 */
				if( is_object( $this->sso ) AND method_exists( $this->sso, 'checkSSOForGuest' ) )
				{
					$this->sso->checkSSOForGuest( 'create' );
				}
			}
		}

		//-----------------------------------------
		// Knock out Google Web Accelerator
		//-----------------------------------------

		if( ipsRegistry::$settings['disable_prefetching'] )
		{
			if ( my_getenv('HTTP_X_MOZ') AND strstr( strtolower(my_getenv('HTTP_X_MOZ')), 'prefetch' ) AND self::$data_store['member_id'] )
			{
				if ( IPB_PHP_SAPI == 'cgi-fcgi' OR IPB_PHP_SAPI == 'cgi' )
				{
					@header('Status: 403 Forbidden');
				}
				else
				{
					@header('HTTP/1.1 403 Forbidden');
				}
				
				@header("Cache-Control: no-cache, must-revalidate, max-age=0");
				@header("Expires: 0");
				@header("Pragma: no-cache");
				
				print "Prefetching or precaching is not allowed. If you have Google Accelerator enabled, please disable";
				exit();
			}
		}

		//-----------------------------------------
		// Still no member id and not a bot?
		//-----------------------------------------

		if ( ! self::$data_store['member_id'] AND ! $this->_member->is_not_human )
		{
			self::setMember(0);

			self::$data_store['last_activity']	= time();
			$this->request['last_visit']		= time();
		}

		//-----------------------------------------
		// Set a session ID cookie
		//-----------------------------------------

		$this->_member->session_type	= $this->session_type;
		$this->_member->session_id	= $this->session_id;

		IPSCookie::set("session_id", $this->session_id, -1);
	}

	/**
	 * Assign a key with a value
	 *
	 * @access	public
	 * @param	string		Key
	 * @param	string		Value
	 * @param	string		[Session ID, will default to current session if none found]
	 * @return	void
	 */
	public function addQueryKey( $key, $value, $sessionID='' )
	{
		$sessionID = ( $sessionID ) ? IPSText::md5Clean( $sessionID ) : $this->session_id;
		
		if ( $key )
		{
			$this->_queryOverride[ $sessionID ][ $key ] = $value;
		}
	}
	
	/**
	 * Update a member's session
	 *
	 * @access	protected
	 * @return	boolean		Updated successfully
	 */
	protected function _updateMemberSession()
	{
		//-----------------------------------------
		// Make sure we have a session id.
		//-----------------------------------------

		if ( ! $this->session_id )
		{
			$this->_createMemberSession();
			return true;
		}

		if ( ! self::$data_store['member_id'] )
		{
			self::setMember(0);
			$this->_createGuestSession();
			return false;
		}

		if ( ( time() - self::$data_store['last_activity'] ) > $this->settings['session_expiration'] )
		{
			// Session is expired - create new session

			$this->_createMemberSession();
			return true;
		}

		//-----------------------------------------
		// Get module settings
		//-----------------------------------------

		$vars = $this->_getLocationSettings();

		//-----------------------------------------
		// Still update?
		//-----------------------------------------

		if ( ! $this->do_update )
		{
			return true;
		}

		IPSDebug::addMessage( "Updating MEMBER session: " . $this->session_data['id'] );

		$uAgent = $this->_processUserAgent( 'update' );

		IPSCookie::set( "pass_hash", self::$data_store['member_login_key'], ( $this->settings['login_key_expire'] ? 0 : 1 ), $this->settings['login_key_expire'] );

		/* Save the last click */
		self::$data_store['last_click'] = $this->session_data['running_time'];

		//-----------------------------------------
		// Set up data
		//-----------------------------------------

		$sessionData = array(
							'member_name'			=> self::$data_store['members_display_name'],
							'seo_name'				=> IPSMember::fetchSeoName( self::$data_store ),
							'member_id'				=> intval(self::$data_store['member_id']),
							'member_group'			=> self::$data_store['member_group_id'],
							'login_type'			=> intval(substr(self::$data_store['login_anonymous'],0, 1)),
							'running_time'			=> IPS_UNIX_TIME_NOW,
							'location'				=> isset( $vars['location'] ) ? $vars['location'] : '',
							'in_error'				=> 0,
							'current_appcomponent'	=> $this->current_appcomponent,
							'current_module'		=> $this->current_module,
							'current_section'		=> $this->current_section,
							'location_1_type'		=> isset($vars['location_1_type']) ? $vars['location_1_type'] : '',
							'location_1_id'			=> isset($vars['location_1_id']) ? intval($vars['location_1_id']) : 0,
							'location_2_type'		=> isset($vars['location_2_type']) ? $vars['location_2_type'] : '',
							'location_2_id'			=> isset($vars['location_2_id']) ? intval($vars['location_2_id']) : 0,
							'location_3_type'		=> isset($vars['location_3_type']) ? $vars['location_3_type'] : '',
							'location_3_id'			=> isset($vars['location_3_id']) ? intval($vars['location_3_id']) : 0,
							'uagent_key'			=> $uAgent['uagent_key'],
							'uagent_version'		=> $uAgent['uagent_version'],
							'uagent_type'			=> $uAgent['uagent_type'],
						  );

		/* Before this function is called, a guest is set up via ipsRegistry::setMember(0)
		   We want to override this now to provide search engine settings for the 'member' */

		if ( $uAgent['uagent_type'] == 'search' )
		{
			self::setSearchEngine( $uAgent );

			/* Reset some data */
			$this->session_type = 'cookie';
			$this->session_id   = "";
		}
		
		/* Set type */
		self::$data_store['_sessionType'] = 'update';

		$this->_sessionsToSave[ $this->session_id ] = $sessionData;

		return true;
	}

	/**
	 * Update a guest's session
	 *
	 * @access	protected
	 * @return	boolean		Updated successfully
	 */
	protected function _updateGuestSession()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$memberName  = '';
		$memberGroup = $this->settings['guest_group'];
		$loginType   = intval($this->caches['group_cache'][ $this->settings['guest_group'] ]['g_hide_online_list']);

		//-----------------------------------------
		// Make sure we have a session id.
		//-----------------------------------------

		if ( ! $this->session_id )
		{
			$this->_createGuestSession();
			return false;
		}

		//-----------------------------------------
		// Get module settings
		//-----------------------------------------

		$vars = $this->_getLocationSettings();

		//-----------------------------------------
		// Still update?
		//-----------------------------------------

		if ( ! $this->do_update )
		{
			return false;
		}

		$uAgent = $this->_processUserAgent( 'update' );

		//-----------------------------------------
		// Is it a search engine?
		//-----------------------------------------

		if ( $uAgent['uagent_type'] == 'search' )
		{
			$this->session_id = substr( $uAgent['uagent_key'] . '=' . str_replace( '.', '', $this->_member->ip_address ) . '_session', 0, 60 );
			$memberName       = $uAgent['uagent_name'];
			$memberGroup      = $this->settings['spider_group'];
			$loginType        = intval( $this->settings['spider_anon'] );

			IPSDebug::addMessage( "Updating SEARCH ENGINE session: " . $this->session_data['id'] );
		}
		else
		{
			IPSDebug::addMessage( "Updating GUEST session: " . $this->session_data['id'] );
		}

		$this->DB->force_data_type = array( 'member_name' => 'string' );

		$sessionData	= array(
								'member_name'			=> $memberName,
								'member_id'				=> 0,
								'member_group'			=> $memberGroup,
								'login_type'			=> $loginType,
								'running_time'			=> IPS_UNIX_TIME_NOW,
								'location'				=> $vars['location'],
								'in_error'				=> 0,
								'current_appcomponent'	=> $this->current_appcomponent,
								'current_module'		=> $this->current_module,
								'current_section'		=> $this->current_section,
								'location_1_type'		=> isset($vars['location_1_type']) ? $vars['location_1_type'] : '',
								'location_1_id'			=> isset($vars['location_1_id']) ? intval($vars['location_1_id']) : 0,
								'location_2_type'		=> isset($vars['location_2_type']) ? $vars['location_2_type'] : '',
								'location_2_id'			=> isset($vars['location_2_id']) ? intval($vars['location_2_id']) : 0,
								'location_3_type'		=> isset($vars['location_3_type']) ? $vars['location_3_type'] : '',
								'location_3_id'			=> isset($vars['location_3_id']) ? intval($vars['location_3_id']) : 0,
								'uagent_key'			=> $uAgent['uagent_key'],
								'uagent_version'		=> $uAgent['uagent_version'],
								'uagent_type'			=> $uAgent['uagent_type'],
								);

		/* Before this function is called, a guest is set up via ipsRegistry::setMember(0)
		   We want to override this now to provide search engine settings for the 'member' */

		if ( $uAgent['uagent_type'] == 'search' )
		{
			self::setSearchEngine( $uAgent );

			/* Reset some data */
			$this->session_type = 'cookie';
			$this->session_id   = "";
		}

		/* Set type */
		self::$data_store['_sessionType'] = 'update';

		$this->_sessionsToSave[ $this->session_id ] = $sessionData;

		return true;
	}

	/**
	 * Create a member session
	 *
	 * @access	protected
	 * @return	boolean		Created successfully
	 */
	protected function _createMemberSession()
	{
		if (self::$data_store['member_id'])
		{
			//-----------------------------------------
			// Remove the defunct sessions
			//-----------------------------------------

			$this->_destroySessions( "member_id=" . self::$data_store['member_id'] );

			$this->session_id  = md5( uniqid( microtime(), true ) . $this->_member->ip_address . $this->_userAgent );

			//-----------------------------------------
			// Get module settings
			//-----------------------------------------

			$vars = $this->_getLocationSettings();

			//-----------------------------------------
			// Still update?
			//-----------------------------------------

			if ( ! $this->do_update )
			{
				return false;
			}

			IPSDebug::addMessage( "Creating MEMBER session: " . $this->session_id );

			//-----------------------------------------
			// Get useragent stuff
			//-----------------------------------------

			$uAgent = $this->_processUserAgent( 'create' );

			//-----------------------------------------
			// Insert the new session
			//-----------------------------------------

			$data = array(
							'id'					=> $this->session_id,
							'member_name'			=> self::$data_store['members_display_name'],
							'seo_name'				=> IPSMember::fetchSeoName( self::$data_store ),
							'member_id'				=> intval(self::$data_store['member_id']),
							'member_group'			=> self::$data_store['member_group_id'],
							'login_type'			=> intval(substr(self::$data_store['login_anonymous'],0, 1)),
							'running_time'			=> IPS_UNIX_TIME_NOW,
							'ip_address'		 	=> $this->_member->ip_address,
							'browser'				=> $this->_userAgent,
							'location'				=> isset( $vars['location'] ) ? $vars['location'] : '',
							'in_error'				=> 0,
							'current_appcomponent'	=> $this->current_appcomponent,
							'current_module'		=> $this->current_module,
							'current_section'		=> $this->current_section,
							'location_1_type'		=> isset($vars['location_1_type']) ? $vars['location_1_type'] : '',
							'location_1_id'			=> isset($vars['location_1_id']) ? intval($vars['location_1_id']) : 0,
							'location_2_type'		=> isset($vars['location_2_type']) ? $vars['location_2_type'] : '',
							'location_2_id'			=> isset($vars['location_2_id']) ? intval($vars['location_2_id']) : 0,
							'location_3_type'		=> isset($vars['location_3_type']) ? $vars['location_3_type'] : '',
							'location_3_id'			=> isset($vars['location_3_id']) ? intval($vars['location_3_id']) : 0,
							'uagent_key'			=> $uAgent['uagent_key'],
							'uagent_version'		=> $uAgent['uagent_version'],
							'uagent_type'			=> $uAgent['uagent_type'],
							'uagent_bypass'			=> intval( $uAgent['uagent_bypass'] ) );

			$this->DB->force_data_type = array( 'member_name' => 'string' );

			$this->DB->insert( 'sessions', $data, true );

			//-----------------------------------------
			// Force data
			//-----------------------------------------

			$this->session_data = array( 'uagent_key'	  => $uAgent['uagent_key'],
										 'uagent_version' => $uAgent['uagent_version'],
										 'uagent_type'	  => $uAgent['uagent_type'],
										 'uagent_bypass'  => $uAgent['uagent_bypass'],
										 'id'             => $data['id'] );

			//-----------------------------------------
			// If this is a member, update their last visit times, etc.
			//-----------------------------------------

			if ( time() - self::$data_store['last_activity'] > $this->settings['session_expiration'] )
			{
				//-----------------------------------------
				// Reset the topics read cookie..
				//-----------------------------------------

				list( $be_anon, $loggedin ) = explode( '&', self::$data_store['login_anonymous'] );

				$update = array( 'login_anonymous'	=> "{$be_anon}&1",
								 'last_visit'		=> self::$data_store['last_activity'],
								 'last_activity'	=> IPS_UNIX_TIME_NOW
								);

				//-----------------------------------------
				// Fix up the last visit/activity times.
				//-----------------------------------------

				self::$data_store['last_visit']    = self::$data_store['last_activity'];
				self::$data_store['last_activity'] = time();
			}

			IPSCookie::set( "pass_hash", self::$data_store['member_login_key'], ( $this->settings['login_key_expire'] ? 0 : 1 ), $this->settings['login_key_expire'] );

			$update['member_login_key_expire']	= time() + ( $this->settings['login_key_expire'] * 86400 );

			IPSMember::save( self::$data_store['member_id'], array( 'core' => $update ) );
		}
		else
		{
			$this->_createGuestSession();
		}
		
		/* Before this function is called, a guest is set up via ipsRegistry::setMember(0)
		   We want to override this now to provide search engine settings for the 'member' */

		if ( $uAgent['uagent_type'] == 'search' )
		{
			self::setSearchEngine( $uAgent );

			/* Reset some data */
			$this->session_type = 'cookie';
			$this->session_id   = "";
		}

		/* Set type */
		self::$data_store['_sessionType'] = 'create';

		return true;
	}

	/**
	 * Create a guest session
	 *
	 * @access	protected
	 * @return	boolean		Created successfully
	 */
	protected function _createGuestSession()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$query       = array();
		$memberName  = '';
		$memberGroup = $this->settings['guest_group'];
		$loginType   = 0;

		//-----------------------------------------
		// Remove the defunct sessions
		//-----------------------------------------

		if ( $this->session_dead_id )
		{
			$query[] = "id='" . $this->session_dead_id . "'";
		}

		if ( $this->settings['match_ipaddress'] == 1 )
		{
			$query[] = "ip_address='" . $this->_member->ip_address . "'";
		}

		$this->session_id  = md5( uniqid( microtime(), true ) . $this->_member->ip_address . $this->_userAgent );

		//-----------------------------------------
		// Get module settings
		//-----------------------------------------

		$vars = $this->_getLocationSettings();

		//-----------------------------------------
		// Still update?
		//-----------------------------------------

		if ( ! $this->do_update )
		{
			return false;
		}

		$uAgent = $this->_processUserAgent( 'create' );

		//-----------------------------------------
		// Is it a search engine?
		//-----------------------------------------

		if ( $uAgent['uagent_type'] == 'search' )
		{
			$this->session_id = substr( $uAgent['uagent_key'] . '=' . str_replace( '.', '', $this->_member->ip_address ) . '_session', 0, 60 );
			$memberName       = $uAgent['uagent_name'];
			$memberGroup      = $this->settings['spider_group'];
			$loginType        = intval( $this->settings['spider_anon'] );

			IPSDebug::addMessage( "Creating SEARCH ENGINE session: " . $this->session_id );

			$query[] = "id='" . $this->session_id . "'";
		}
		else
		{
			IPSDebug::addMessage( "Creating GUEST session: " . $this->session_id );
		}

		//-----------------------------------------
		// Got anything to remove?
		//-----------------------------------------

		if ( count( $query ) )
		{
			$this->_destroySessions( implode( " OR ", $query ) );
		}

		//-----------------------------------------
		// Insert the new session
		//-----------------------------------------

		$data = array(
						'id'					=> $this->session_id,
						'member_name'			=> $memberName,
						'member_id'				=> 0,
						'member_group'			=> $memberGroup,
						'login_type'			=> $loginType,
						'running_time'			=> IPS_UNIX_TIME_NOW,
						'ip_address'			=> $this->_member->ip_address,
						'browser'				=> substr( $this->_member->user_agent, 0, 200 ),
						'location'				=> $vars['location'],
						'in_error'				=> 0,
						'current_appcomponent'	=> $this->current_appcomponent,
						'current_module'		=> $this->current_module,
						'current_section'		=> $this->current_section,
						'location_1_type'		=> isset($vars['location_1_type']) ? $vars['location_1_type'] : '',
						'location_1_id'			=> isset($vars['location_1_id']) ? intval($vars['location_1_id']) : 0,
						'location_2_type'		=> isset($vars['location_2_type']) ? $vars['location_2_type'] : '',
						'location_2_id'			=> isset($vars['location_2_id']) ? intval($vars['location_2_id']) : 0,
						'location_3_type'		=> isset($vars['location_3_type']) ? $vars['location_3_type'] : '',
						'location_3_id'			=> isset($vars['location_3_id']) ? intval($vars['location_3_id']) : 0,
						'uagent_key'			=> $uAgent['uagent_key'],
						'uagent_version'		=> $uAgent['uagent_version'],
						'uagent_type'			=> $uAgent['uagent_type'],
						'uagent_bypass'			=> intval( $uAgent['uagent_bypass'] ) );

		$this->DB->force_data_type = array( 'member_name' => 'string' );

		$this->DB->insert( 'sessions', $data, true );

		//-----------------------------------------
		// Force data
		//-----------------------------------------

		$this->session_data = array( 'uagent_key'	  => $uAgent['uagent_key'],
									 'uagent_version' => $uAgent['uagent_version'],
									 'uagent_type'	  => $uAgent['uagent_type'],
									 'uagent_bypass'  => $uAgent['uagent_bypass'],
									 'id'             => $data['id'] );

		/* Before this function is called, a guest is set up via ipsRegistry::setMember(0)
		   We want to override this now to provide search engine settings for the 'member' */

		if ( $uAgent['uagent_type'] == 'search' )
		{
			self::setSearchEngine( $uAgent );

			/* Reset some data */
			$this->session_type = 'cookie';
			$this->session_id   = "";
		}

		/* Set type */
		self::$data_store['_sessionType'] = 'create';

		return true;
	}

	/**
	 * Converts a guest session to a member session
	 *
	 * @access	public
	 * @param	array 		Array of incoming data (member_id, member_name, member_group, login_type)
	 * @return	string 		Current session ID
	 */
	public function convertGuestToMember( $data )
	{
		/* Delete old sessions */
		$this->_destroySessions( "ip_address='" . $this->_member->ip_address . "' AND id != '{$this->session_id}'" );
		
		/* Fetch member */
		$member = IPSMember::load( $data['member_id'], 'core' );
		
		/* Update this session directly */
		$this->DB->update( 'sessions', array(
												'member_name'			=> $data['member_name'],
												'seo_name'				=> IPSText::makeSeoTitle( $data['member_name'] ),
												'member_id'				=> $data['member_id'],
												'running_time'			=> time(),
												'member_group'			=> $data['member_group'],
												'login_type'			=> $data['login_type']  ), "id='" . $this->session_id . "'", TRUE );

		/* Remove from update and delete array */
		unset( $this->_sessionsToSave[ $this->session_id ] );
		unset( $this->_sessionsToKill[ $this->session_id ] );
		
		$update	= array( 'last_activity' => time() );
		
		if ( $member['last_activity'] )
		{
			$update['last_visit'] = $member['last_activity'];
		}
		else
		{
			$update['last_visit'] = time();
		}

		/* Make sure last activity and last visit are up to date */
		IPSMember::save( $data['member_id'], array( 'core' => $update ) );

		/* Set cookie */
		IPSCookie::set( "session_id", $this->session_id, -1 );

		IPSDebug::addLogMessage( "convertGuestToMember: {$data['member_id']}, {$this->session_id} " . serialize( $data ), 'sessions-' . $this->_memberData['member_id'] );

		/* Set type */
		self::$data_store['_sessionType'] = 'update';

		return $this->session_id;
	}

	/**
	 * Converts a member session to a guest session
	 *
	 * @access	public
	 * @return	string 		Current session ID
	 */
	public function convertMemberToGuest()
	{
		/* Delete old sessions */
		$this->_destroySessions( "ip_address='" . $this->_member->ip_address . "' AND id != '{$this->session_id}'" );

		/* Update this session directly */
		$this->DB->update( 'sessions', array(
												'member_name'			=> '',
												'seo_name'				=> '',
												'member_id'				=> 0,
												'running_time'			=> time(),
												'member_group'			=> $this->settings['guest_group'] ) , "id='" . $this->session_id . "'", TRUE );

		/* Remove from update and delete array */
		unset( $this->_sessionsToSave[ $this->session_id ] );
		unset( $this->_sessionsToKill[ $this->session_id ] );

		/* Set cookie */
		IPSCookie::set( "session_id", $this->session_id, -1 );

		/* Save markers... */
		$this->registry->classItemMarking->writeMyMarkersToDB();

		IPSDebug::addLogMessage( "convertMemberToGuest: {$this->session_id} " . serialize( $data ), 'sessions-' . $this->_memberData['member_id'] );

		/* Set type */
		self::$data_store['_sessionType'] = 'update';

		return $this->session_id;
	}

	/**
	 * Kill sessions
	 *
	 * @access	protected
	 * @param	string		Any extra WHERE stuff
	 * @return	void
	 */
	protected function _destroySessions( $where='' )
	{
		$where .= ( $where ) ? ' OR ' : '';
		$where .= 'running_time < ' . ( IPS_UNIX_TIME_NOW - $this->settings['session_expiration'] );

		//-----------------------------------------
		// Grab session data to delete on destruct
		//-----------------------------------------
		
		if ( $this->_deleteNow )
		{
			$this->DB->delete( 'sessions', $where );
		}
		else
		{
			$this->DB->build( array( 'select' => '*',
									 'from'   => 'sessions',
									 'where'  => $where ) );

			$this->DB->execute();

			while( $row = $this->DB->fetch() )
			{
				$this->_sessionsToKill[ $row['id'] ] = $row;
			}

			IPSDebug::addLogMessage( "_destroySessions: $where", 'sessions-' . $this->_memberData['member_id'] );
		}
	}

	/**
	 * Retrive a session based on a session id
	 *
	 * @access	public
	 * @param	string		Session id
	 * @return	boolean		Retrieved successfully
	 */
	public function getSession( $session_id="" )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$result		= array();
		$session_id	= IPSText::md5Clean( $session_id );

		if ( $session_id )
		{
			$_session = $this->DB->buildAndFetch( array( 'select'	=> '*',
										   				 'from'	    => 'sessions',
														 'where'    => "id='" . $session_id . "'") );

			if ( $_session['id'] )
			{
				/* Kill any long running search thread... */
				if( $this->settings['kill_search_after'] )
				{
					if( $_session['search_thread_id'] AND $_session['search_thread_time'] AND $_session['search_thread_time'] < (time() - $this->settings['kill_search_after']) )
					{
						$this->DB->return_die	= true;
						$this->DB->kill( $_session['search_thread_id'] );
						$this->DB->return_die	= false;
						
						$this->DB->update( 'sessions', array( 'search_thread_id' => 0, 'search_thread_time' => 0 ), "id='" . $session_id . "'" );
					}
				}

				/* Test for browser.... */
				if ( $this->settings['match_browser'] AND !$this->request['section'] == 'attach' )
				{
					if ( $_session['browser'] != $this->_userAgent )
					{
						return $this->_killAuthorizeAttempt( $session_id, $_session );
					}
				}

				/* Test for IP Address... */
				if ( $this->settings['match_ipaddress'] )
				{
					if ( $_session['ip_address'] != $this->_member->ip_address )
					{
						return $this->_killAuthorizeAttempt( $session_id, $_session );
					}
				}

				/* Still here? */
				return $this->_allowAuthorizeAttempt( $session_id, $_session );
			}
			else
			{
				return $this->_killAuthorizeAttempt( $session_id, array() );
			}
		}
		else
		{
			return $this->_killAuthorizeAttempt( '', array() );
		}
	}

	/**
	 * Kill This Session
	 *
	 * @access	private
	 * @param	string		Session ID
	 * @param	bool
	 */
	private function _killAuthorizeAttempt( $session_id, $session_data )
	{
		$this->_failedAuthorizationSessionData = $session_data;
		$this->session_dead_id	= $session_id;
		$this->session_id		= 0;
		$this->session_user_id	= 0;
		$this->session_data     = array();

		IPSDebug::addLogMessage( "Authorization attempt FAILED: Session ID={$session_id}, member ID=0", 'sessions-failed' );

		return false;
	}

	/**
	 * Allow This Session
	 *
	 * @access	private
	 * @param	string		Session ID
	 * @param	bool
	 */
	private function _allowAuthorizeAttempt( $session_id, $session_data )
	{
		$this->session_data		= $session_data;
		$this->session_id		= $this->session_data['id'];
		$this->session_user_id	= $this->session_data['member_id'];
		$this->last_click		= $this->session_data['running_time'];
		$this->location			= $this->session_data['location'];

		IPSDebug::addLogMessage( "Authorization attempt successful: Session ID={$session_id}, member ID={$this->session_data['member_id']} ", 'sessions-' . $this->session_data['member_id'] );

		return true;
	}


	/**
	 * Grab the user agent from the DB if required
	 *
	 * @access	protected
	 * @param	string		Type of session (update/create)
	 * @return	array 		Array of user agent info from the DB
	 */
	protected function _processUserAgent( $type='update' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$uAgent = array( 'uagent_key'     => '__NONE__',
						 'uagent_version' => 0,
						 'uagent_name'    => '',
						 'uagent_type'    => '',
						 'uagent_bypass'  => 0 );

		//-----------------------------------------
		// Do we need to update?
		//-----------------------------------------

		if ( empty( $this->session_data['uagent_key'] ) OR $this->session_data['uagent_key'] == '__NONE__' OR ( $this->_userAgent != $this->session_data['browser'] ) )
		{
			IPSDebug::addMessage( "Retreiving user agent information from the DB" );

			//-----------------------------------------
			// Get useragent stuff
			//-----------------------------------------

			if ( ! $this->registry->isClassLoaded( 'userAgentFunctions' ) )
			{
				require_once( IPS_ROOT_PATH . 'sources/classes/useragents/userAgentFunctions.php' );
				$this->registry->setClass( 'userAgentFunctions', new userAgentFunctions( $this->registry ) );
			}

			$uAgent = $this->registry->getClass( 'userAgentFunctions' )->findUserAgentID( $this->_member->user_agent );

			if ( $uAgent['uagent_key'] === NULL )
			{
				$uAgent = array( 'uagent_key'     => '__NONE__',
								 'uagent_version' => 0,
								 'uagent_name'    => '',
								 'uagent_type'    => '',
								 'uagent_bypass'  => 0 );
			}
			else
			{
				$uAgent['uagent_bypass'] = 0;
			}
		}
		else
		{
			$uAgent['uagent_key']     = $this->session_data['uagent_key'];
			$uAgent['uagent_version'] = $this->session_data['uagent_version'];
			$uAgent['uagent_type']    = $this->session_data['uagent_type'];
			$uAgent['uagent_bypass']  = $this->session_data['uagent_bypass'];
			/* For search engines only */
			$uAgent['uagent_name']    = $this->session_data['member_name'];
		}

		return $uAgent;
	}


	/**
	 * Retrive session location settings from plugin files
	 *
	 * @access	protected
	 * @return	array		Location settings
	 */
	protected function _getLocationSettings()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$return = array();

		//-----------------------------------------
		// MODULE?
		//-----------------------------------------

		if ( IPS_APP_COMPONENT )
		{
			$filename = IPSLib::getAppDir(  IPS_APP_COMPONENT ) . '/extensions/coreExtensions.php';
			$toload  = 'publicSessions__' . IPS_APP_COMPONENT;

			if ( file_exists( $filename ) )
			{
				require_once( $filename );

				if ( class_exists( $toload ) )
				{
					$loader = new $toload;

					$return = $loader->getSessionVariables();
				}
			}
		}
		
		if( defined('NO_SESSION_UPDATE') AND NO_SESSION_UPDATE )
		{
			$this->do_update	= 0;
		}

		return $return;
	}

	/**
	 * Fetch session data that is to be destroyed
	 *
	 * @access	public
	 * @return	array
	 */
	public function fetchDestroyedSessionsAsArray()
	{
		return $this->_sessionsToKill;
	}

	/**
	 * Updates a session
	 *
	 * @access	public
	 * @param	string		Session id
	 * @param	int			Member ID
	 * @param	array 		Array of information to update
	 * @return	void		Updates session_data array and member data array
	 */
	public function updateSession( $sessionID, $memberID, $data )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$memberID  = intval( $memberID );
		$sessionID = IPSText::md5Clean( $sessionID );
		$_data     = array();
		$_ignore   = array( 'id',
							'member_id',
							'ip_address',
							'browser' );

		$remap     = array( 'uagent_bypass' => 'userAgentBypass',
							'item_markers'  => 'itemMarkers' );

		//-----------------------------------------
		// Remove what we can't update...
		//-----------------------------------------

		foreach( $data as $key => $value )
		{
			if ( in_array( $key, $_ignore ) )
			{
				continue;
			}

			$_data[ $key ] = $value;

			/* Update our own session? */
			if ( $memberID == $this->_memberData['member_id'] )
			{
				$this->session_data[ $key ] = $value;

				if ( in_array( $key, array_keys( $remap ) ) )
				{
					$this->_memberData[ $remap[ $key ] ] = $value;

				}
				else
				{
					$this->_memberData[ $key ] = $value;
				}
			}
		}

		/* Now, do we have a session already saved ready for.. er..saving? */
		if ( isset( $this->_sessionsToSave[ $sessionID ] ) AND $this->_sessionsToSave[ $sessionID ]['member_id'] == $memberID )
		{
			/* Just update the keys, then...*/
			foreach( $_data as $k => $v )
			{
				$this->_sessionsToSave[ $sessionID ][ $k ] = $v;
			}
		}
		else
		{
			/* Add to the list.. */
			$this->_sessionsToSave[ $sessionID ] = $_data;
		}

		IPSDebug::addLogMessage( "Session updated - " . $sessionID . " - " . serialize( $this->_sessionsToSave ), 'sessions-' . $this->_memberData['member_id'] );
	}

	/**
	 * Manual destructor called by ips_MemberRegistry::__myDestruct()
	 * Gives us a chance to do anything we need to do before other
	 * classes are culled
	 *
	 * @access	public
	 * @return	void
	 */
	public function __myDestruct()
	{
		$_updated = 0;
		$_deleted = 0;
		
		/* Update sessions... */
		if ( is_array( $this->_sessionsToSave ) AND count( $this->_sessionsToSave ) )
		{
			foreach( $this->_sessionsToSave as $sessionID => $data )
			{
				if ( $sessionID )
				{
					if ( isset( $this->_queryOverride[ $sessionID ] ) AND is_array( $this->_queryOverride[ $sessionID ] ) AND count( $this->_queryOverride[ $sessionID ] ) )
					{
						foreach( $data as $field => $value )
						{
							if ( isset( $this->_queryOverride[ $sessionID ][ $field ] ) )
							{
								$data[ $field ] = $this->_queryOverride[ $sessionID ][ $field ];
							}
						}
					}

					$this->DB->force_data_type = array( 'member_name' => 'string' );
					$this->DB->update( 'sessions', $data, "id='" . $sessionID . "'", true );
				}
			}
		}

		/* Remove sessions */
		if ( is_array( $this->_sessionsToKill ) AND count( $this->_sessionsToKill ) )
		{
			$_c = count( $this->_sessionsToKill );

			$this->DB->delete( 'sessions', "id IN('" . implode( "','", array_keys( $this->_sessionsToKill ) ) . "')" );
		}

		IPSDebug::addLogMessage( get_class( $this ) . ": " . count( $this->_sessionsToSave ) . " sessions updated, " . count( $this->_sessionsToKill ) . " sessions deleted", 'sessions-' . $this->_memberData['member_id'] );
	}

}