<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Login handler abstraction
 * Last Updated: $Date: 2009-07-29 21:58:31 -0400 (Wed, 29 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @since		Tuesday 1st March 2005 (11:52)
 * @version		$Revision: 4951 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class han_login
{
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
	 * Member object
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $member;
	
	/**
	 * Login module registry
	 *
	 * @access	private
	 * @var		array
	 */
	private $modules			= array();
	
	/**
	 * Flag :: ACP Login
	 *
	 * @access	public
	 * @var		integer
	 */
	public $is_admin_auth 		= 0;
	
	/**
	 * Login handler return code
	 *
	 * @access	public
	 * @var		string
	 */
	public $return_code   		= 'WRONG_AUTH';
	
	/**
	 * Login handler return details
	 *
	 * @access	public
	 * @var		string
	 */
	public $return_details		= "";
	
	/**
	 * Flag :: Account unlocked
	 *
	 * @access	public
	 * @var		integer
	 */
	public $account_unlock		= 0;
	
	/**
	 * Member data returned
	 *
	 * @access	public
	 * @var		array
	 */
	public $member_data  		= array( 'member_id' => 0 );
	
	/**
	 * Login methods
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $login_methods	= array();
	
	/**
	 * Login configuration details
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $login_confs		= array();
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make object */
		$this->registry = $registry;
		$this->DB       = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->lang     = $this->registry->getClass('class_localization');
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
	}
	
	/**
	 * loginWithoutCheckingCredentials: Just log the user in.
	 * DOES NOT CHECK FOR A USERNAME OR PASSWORD.
	 * << USE WITH CAUTION >>
	 *
	 * @access	public
	 * @param	int			Member ID to log in
	 * @param	boolean		Set cookies
	 * @return	mixed		FALSE on error or array [0=Words to show, 1=URL to send to] on success
	 */
	public function loginWithoutCheckingCredentials( $memberID, $setCookies=TRUE)
	{
		//-----------------------------------------
		// Load member
		//-----------------------------------------
		
		$member = IPSMember::load( $memberID, 'all' );
		
		if ( ! $member['member_id'] )
		{
			return FALSE;
		}
		
		//-----------------------------------------
		// Is this a partial member?
		// Not completed their sign in?
		//-----------------------------------------
		
		if ( $member['members_created_remote'] AND isset($member['full']) AND !$member['full'] )
		{
			//-----------------------------------------
			// If this is a resume, i.e. from Facebook,
			// timenow won't be set
			//-----------------------------------------
			
			if( !$member['timenow'] )
			{
				$partial	= $this->DB->buildAndFetch( array( 'select' => 'partial_date', 'from' => 'members_partial', 'where' => 'partial_member_id=' . $member['member_id'] ) );
				$member['timenow']	= $partial['partial_date'];
			}
			
			return array( $this->lang->words['partial_login'], $this->settings['base_url'] . 'app=core&amp;module=global&amp;section=register&amp;do=complete_login&amp;mid='.$member['member_id'].'&amp;key='.$member['timenow'] );
		}
		
		//-----------------------------------------
		// Generate a new log in key
		//-----------------------------------------
		
		$_ok     = 1;
		$_time   = ( $this->settings['login_key_expire'] ) ? ( time() + ( intval($this->settings['login_key_expire']) * 86400 ) ) : 0;
		$_sticky = $_time ? 0 : 1;
		$_days   = $_time ? $this->settings['login_key_expire'] : 365;
		
		if ( $this->settings['login_change_key'] OR !$member['member_login_key'] OR ( $this->settings['login_key_expire'] AND ( time() > $member['member_login_key_expire'] ) ) )
		{
			$member['member_login_key'] = IPSMember::generateAutoLoginKey();
			
			$core['member_login_key']			= $member['member_login_key'];
			$core['member_login_key_expire']	= $_time;
		}
	
		//-----------------------------------------
		// Cookie me softly?
		//-----------------------------------------
		
		if ( $setCookies )
		{
			IPSCookie::set( "member_id"   , $member['member_id']       , 1 );
			IPSCookie::set( "pass_hash"   , $member['member_login_key'], $_sticky, $_days );
		}
		else
		{
			IPSCookie::set( "member_id"   , $member['member_id'], 0 );
			IPSCookie::set( "pass_hash"   , $member['member_login_key'], 0 );
		}
		
		//-----------------------------------------
		// Remove any COPPA cookies previously set
		//-----------------------------------------
		
		IPSCookie::set("coppa", '0', 0);
		
		//-----------------------------------------
		// Update profile if IP addr missing
		//-----------------------------------------
		
		if ( $member['ip_address'] == "" OR $member['ip_address'] == '127.0.0.1' )
		{
			$core['ip_address']	= $this->member->ip_address;
		}
		
		//-----------------------------------------
		// Create / Update session
		//-----------------------------------------
		
		$privacy    = 0;
		
		if( $member['g_hide_online_list'] )
		{
			$privacy	= 1;
		}

		$session_id = $this->member->sessionClass()->convertGuestToMember( array( 'member_name'	    => $member['members_display_name'],
													   			     		 	  'member_id'		=> $member['member_id'],
																			      'member_group'	=> $member['member_group_id'],
																			      'login_type'		=> $privacy ) );
			
		
		if ( $this->request['referer'] AND $this->request['referer'] AND $this->request['section'] != 'register' )
		{
			if ( stripos( $this->request['referer'], 'section=register' ) OR stripos( $this->request['referer'], 'section=login' ) OR stripos( $this->request['referer'], 'section=lostpass' ) )
			{ 
				$url = $this->settings['base_url'];
			}
			else
			{ 
				$url = str_replace( '&amp;'		   , '&', $this->request['referer'] );
				//$url = str_replace( "{$this->settings['board_url']}/index.{$this->settings['php_ext']}", "", $url );
				//$url = str_replace( "{$this->settings['board_url']}/", "", $url );
				//$url = str_replace( "{$this->settings['board_url']}", "", $url );
				//$url = preg_replace( "#^(.+?)\?#", ""	, $url );
				$url = preg_replace( "#s=(\w){32}#", ""	, $url );
				$url = ltrim( $url, '?' );
			}
		}
		else
		{
			$url = $this->settings['base_url'];
		}

		//-----------------------------------------
		// Set our privacy status
		//-----------------------------------------
		
		$core['login_anonymous']		= intval($privacy) . '&1';
		$core['failed_logins']			= '';
		$core['failed_login_count']		= 0;

		IPSMember::save( $member['member_id'], array( 'core' => $core ) );

		//-----------------------------------------
		// Clear out any passy change stuff
		//-----------------------------------------
		
		$this->DB->delete( 'validating', 'member_id=' . $this->memberData['member_id'] . ' AND lost_pass=1' );

		//-----------------------------------------
		// Redirect them to either the board
		// index, or where they came from
		//-----------------------------------------

		if ( $this->request['return'] AND $this->request['return'] != "" )
		{
			$return = urldecode($this->request['return']);
			
			if ( strpos( $return, "http://" ) === 0 )
			{
				return array( $this->lang->words['partial_login'], $return );
			}
		}
		
		//-----------------------------------------
		// Still here?
		//-----------------------------------------
		
		/* Member Sync */
		IPSLib::runMemberSync( 'onLogin', $member );
		
		return array( $this->lang->words['partial_login'], str_replace( '?&', '?', $url . '&s=' . $session_id ) );
	}
	/**
	 * Wrapper for loginAuthenticate - returns more information
	 *
	 * @access	public
	 * @return	mixed		array [0=Words to show, 1=URL to send to, 2=error message language key]
	 */
	public function verifyLogin()
	{
    	$url		= "";
    	$member		= array();
    	$username	= '';
    	$email		= '';
		$password	= trim( $this->request['password'] );
		$errors		= '';
		$core		= array();

		//-----------------------------------------
		// Is this a username or email address?
		//-----------------------------------------
		
		if( IPSText::checkEmailAddress( $this->request['username'] ) )
		{
			$email		= $this->request['username'];
		}
		else
		{
			$username	= $this->request['username'];
		}
		
		//-----------------------------------------
		// Check auth
		//-----------------------------------------
		
		$this->loginAuthenticate( $username, $email, $password );
		
		$member = $this->member_data;

		//-----------------------------------------
		// Check return code...
		//-----------------------------------------

		if ( $this->return_code != 'SUCCESS' )
		{
			if( $this->return_code == 'MISSING_DATA' )
			{
				return array( null, null, 'complete_form' );
			}

			if ( $this->return_code == 'ACCOUNT_LOCKED' )
			{
				$extra = "<!-- -->";

				if( $this->settings['ipb_bruteforce_unlock'] )
				{
					if( $this->account_unlock )
					{
						$time = time() - $this->account_unlock;
						$time = ( $this->settings['ipb_bruteforce_period'] - ceil( $time / 60 ) > 0 ) ? $this->settings['ipb_bruteforce_period'] - ceil( $time / 60 ) : 1;
					}
				}
				
				return array( null, null, 'bruteforce_account_unlock', $time );
			}
			else if( $this->return_code == 'WRONG_OPENID' )
			{
				return array( null, null, 'wrong_openid' );
			}
			else if( $this->return_code == 'FLAGGED_REMOTE' )
			{
				return array( null, null, 'flagged_remote' );
			}
			else
			{
				return array( null, null, 'wrong_auth' );
			}
		}

		//-----------------------------------------
		// Is this a partial member?
		// Not completed their sign in?
		//-----------------------------------------
		
		if ( $member['members_created_remote'] AND isset($member['full']) AND !$member['full'] )
		{
			return array( $this->lang->words['partial_login'], $this->settings['base_url'] . 'app=core&amp;module=global&amp;section=register&amp;do=complete_login&amp;mid='.$member['member_id'].'&amp;key='.$member['timenow'] );
		}
		
		//-----------------------------------------
		// Generate a new log in key
		//-----------------------------------------
		
		$_ok     = 1;
		$_time   = ( $this->settings['login_key_expire'] ) ? ( time() + ( intval($this->settings['login_key_expire']) * 86400 ) ) : 0;
		$_sticky = $_time ? 0 : 1;
		$_days   = $_time ? $this->settings['login_key_expire'] : 365;
		
		if ( $this->settings['login_change_key'] OR !$member['member_login_key'] OR ( $this->settings['login_key_expire'] AND ( time() > $member['member_login_key_expire'] ) ) )
		{
			$member['member_login_key'] = IPSMember::generateAutoLoginKey();
			
			$core['member_login_key']			= $member['member_login_key'];
			$core['member_login_key_expire']	= $_time;
		}
	
		//-----------------------------------------
		// Cookie me softly?
		//-----------------------------------------
		
		if ( $this->request['rememberMe'] )
		{
			IPSCookie::set( "member_id"   , $member['member_id']       , 1 );
			IPSCookie::set( "pass_hash"   , $member['member_login_key'], $_sticky, $_days );
		}
		else
		{
			IPSCookie::set( "member_id"   , $member['member_id'], 0 );
			IPSCookie::set( "pass_hash"   , $member['member_login_key'], 0 );
		}
		
		//-----------------------------------------
		// Remove any COPPA cookies previously set
		//-----------------------------------------
		
		IPSCookie::set("coppa", '0', 0);
		
		//-----------------------------------------
		// Update profile if IP addr missing
		//-----------------------------------------
		
		if ( $member['ip_address'] == "" OR $member['ip_address'] == '127.0.0.1' )
		{
			$core['ip_address']	= $this->member->ip_address;
		}
		
		//-----------------------------------------
		// Create / Update session
		//-----------------------------------------
		
		$privacy    = $this->request['anonymous'] ? 1 : 0;
		
		if( $member['g_hide_online_list'] )
		{
			$privacy	= 1;
		}

		$session_id = $this->member->sessionClass()->convertGuestToMember( array( 'member_name'	    => $member['members_display_name'],
													   			     		 	  'member_id'		=> $member['member_id'],
																			      'member_group'	=> $member['member_group_id'],
																			      'login_type'		=> $privacy ) );
			
		
		if ( $this->request['referer'] AND $this->request['referer'] AND $this->request['section'] != 'register' )
		{
			if ( stripos( $this->request['referer'], 'section=register' ) OR stripos( $this->request['referer'], 'section=login' ) OR stripos( $this->request['referer'], 'section=lostpass' ) OR stripos( $this->request['referer'], CP_DIRECTORY . '/' ) )
			{ 
				$url = $this->settings['base_url'] . '?';
			}
			else
			{
				$url = str_replace( '&amp;'		   , '&', $this->request['referer'] );
				$url = preg_replace( "#s=(\w){32}#", ""	, $url );

				if( $this->member->session_type != 'cookie' )
				{
					$url	= $this->settings['board_url'] . '/index.php?s=' . $session_id;
				}
			}
		}
		else
		{
			$url = $this->settings['base_url'] . '?';
		}

		//-----------------------------------------
		// Set our privacy status
		//-----------------------------------------
		
		$core['login_anonymous']		= intval($privacy) . '&1';
		$core['failed_logins']			= '';
		$core['failed_login_count']		= 0;

		IPSMember::save( $member['member_id'], array( 'core' => $core ) );

		//-----------------------------------------
		// Clear out any passy change stuff
		//-----------------------------------------
		
		$this->DB->delete( 'validating', 'member_id=' . $this->memberData['member_id'] . ' AND lost_pass=1' );

		//-----------------------------------------
		// Redirect them to either the board
		// index, or where they came from
		//-----------------------------------------

		if ( $this->request['return'] )
		{
			$return = urldecode($this->request['return']);
			
			if ( strpos( $return, "http://" ) === 0 )
			{
				return array( $this->lang->words['partial_login'], $return );
			}
		}
		
		//-----------------------------------------
		// Still here?
		//-----------------------------------------
		
		/* Member Sync */
		IPSLib::runMemberSync( 'onLogin', $member );

		return array( $this->lang->words['partial_login'], $url );
	}
	
	/**
	 * Initialize class
	 *
	 * @access	public
	 * @return	void
	 */
    public function init()
    {
    	//-----------------------------------------
    	// INIT
    	//-----------------------------------------
    	
    	require_once( IPS_PATH_CUSTOM_LOGIN . '/login_core.php' );
    	require_once( IPS_PATH_CUSTOM_LOGIN . '/login_interface.php' );
    	
    	$classes	= array();
    	$configs	= array();
    	$methods	= array();
    	
    	//-----------------------------------------
    	// Do we have cache?
    	//-----------------------------------------
    	
    	$cache = $this->registry->cache()->getCache( 'login_methods' );
    	
    	if( is_array($cache) AND count($cache) )
		{
			foreach( $cache as $login_method )
			{
				if( $login_method['login_enabled'] )
				{
					if( file_exists( IPS_PATH_CUSTOM_LOGIN . '/' . $login_method['login_folder_name'] . '/auth.php' ) )
					{
						$classes[ $login_method['login_order'] ]				= IPS_PATH_CUSTOM_LOGIN . '/' . $login_method['login_folder_name'] . '/auth.php';
						$configs[ $login_method['login_order'] ]				= IPS_PATH_CUSTOM_LOGIN . '/' . $login_method['login_folder_name'] . '/conf.php';
						$this->login_methods[ $login_method['login_order'] ]	= $login_method;
						$this->login_confs[ $login_method['login_order'] ]		= array();
						
						if( file_exists( $configs[ $login_method['login_order'] ] ) )
						{
							$LOGIN_CONF	= array();
							
							require( $configs[ $login_method['login_order'] ] );
							$this->login_confs[ $login_method['login_order'] ]	= $LOGIN_CONF;
						}
						
						$classname	= "login_" . $login_method['login_folder_name'];

						require_once( $classes[ $login_method['login_order'] ] );
						$this->modules[ $login_method['login_order'] ]			= new $classname( $this->registry, $login_method, $this->login_confs[ $login_method['login_order'] ] );
					}
				}
			}
		}

    	//-----------------------------------------
    	// No cache info
    	//-----------------------------------------
    	
    	else
		{
    		$this->DB->build( array( 'select' => '*', 'from' => 'login_methods', 'where' => 'login_enabled=1' ) );
    		$this->DB->execute();
    		
			while( $login_method = $this->DB->fetch() )
			{
				if( file_exists( IPS_PATH_CUSTOM_LOGIN . '/' . $login_method['login_folder_name'] . '/auth.php' ) )
				{
					$classes[ $login_method['login_order'] ]				= IPS_PATH_CUSTOM_LOGIN . '/' . $login_method['login_folder_name'] . '/auth.php';
					$configs[ $login_method['login_order'] ]				= IPS_PATH_CUSTOM_LOGIN . '/' . $login_method['login_folder_name'] . '/conf.php';
					$this->login_methods[ $login_method['login_order'] ]	= $login_method;
					
					if( file_exists( $configs[ $login_method['login_order'] ] ) )
					{
						$LOGIN_CONF	= array();
						
						require( $configs[ $login_method['login_order'] ] );
						$this->login_confs[ $login_method['login_order'] ]	= $LOGIN_CONF;
					}
					
					$classname	= "login_" . $login_method['login_folder_name'];

					require_once( $classes[ $login_method['login_order'] ] );
					$this->modules[ $login_method['login_order'] ]			= new $classname( $this->registry, $login_method, $this->login_confs[ $login_method['login_order'] ] );
				}
			}
		}
    	
    	//-----------------------------------------
    	// Got nothing?
    	//-----------------------------------------
    	
    	if ( !count($classes) )
    	{
    		$login_method = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'login_methods', 'where' => "login_folder_name='internal'" ) );
    		
    		if( $login_method['login_id'] )
			{
	    		$classes[ 0 ]				= IPS_PATH_CUSTOM_LOGIN . '/internal/auth.php';
				$this->login_methods[ 0 ]	= $login_method;
				$this->login_confs[ 0 ]		= array();
				$classname					= "login_internal";
	
				require_once( $classes[ 0 ] );
				$this->modules[ 0 ]			= new $classname( $this->registry, $login_method, array() );
			}
		}
		
    	//-----------------------------------------
    	// If we're here, there is no enabled login
    	// handler and internal was deleted
    	//-----------------------------------------
    	
    	if( !count($this->modules) )
		{
			$this->registry->output->showError( $this->lang->words['no_login_methods'], 4000 );
		}

		//-----------------------------------------
		// Pass of some data
		//-----------------------------------------
		
		foreach( $this->modules as $k => $obj_reference )
		{
			$obj_reference->is_admin_auth	= $this->is_admin_auth;
			$obj_reference->login_method	= $this->login_methods[ $k ];
			$obj_reference->login_conf		= $this->login_confs[ $k ];
		}
    }
    
	/**
	 * Force email check flag in any modules, currently used for facebook
	 *
	 * @access	public
	 * @param 	boolean
	 * @return  null
	 */
	public function setForceEmailCheck( $boolean )
	{
		foreach( $this->modules as $k => $obj_reference )
		{
			if( method_exists( $obj_reference, 'setForceEmailCheck' ) )
			{
				$obj_reference->setForceEmailCheck( $boolean );
			}
		}
	}
	
	/**
	 * Checks if password authenticates
	 *
	 * @access	public
	 * @param	string		Username
	 * @param	string		Email Address
	 * @param	string		Password
	 * @return	boolean		Password check successful
	 */
  	public function loginPasswordCheck( $username, $email_address, $password )
  	{
		foreach( $this->modules as $k => $obj_reference )
		{
			$obj_reference->authenticate( $username, $email_address, $password );
			$this->return_code 		= ( $obj_reference->return_code == 'SUCCESS' ? 'SUCCESS' : 'FAIL' );
			$this->member_data		= $obj_reference->member_data;
			
			if( $this->return_code == 'SUCCESS' )
			{
				break;
			}
  		}
  		
  		return ( $this->return_code == 'SUCCESS' ) ? true : false;
  	}
    
	/**
	 * Authenticate the user - creates account if possible
	 *
	 * @access	public
	 * @param	string		Username
	 * @param	string		Email Address
	 * @param	string		Password
	 * @return	boolean		Authenticate successful
	 */
  	public function loginAuthenticate( $username, $email_address, $password )
  	{
		foreach( $this->modules as $k => $obj_reference )
		{
			$obj_reference->authenticate( $username, $email_address, $password );
			$this->return_code 		= ( $obj_reference->return_code == 'SUCCESS' ? 'SUCCESS' : $obj_reference->return_code );
			$this->member_data		= $obj_reference->member_data;
			$this->account_unlock 	= ( $obj_reference->account_unlock ) ? $obj_reference->account_unlock : $this->account_unlock;
			
			/* Locked */
			if( $this->return_code == 'ACCOUNT_LOCKED' )
			{
				return false;
			}
			
			if( $this->return_code == 'SUCCESS' )
			{
				break;
			}
			else
			{
				//-----------------------------------------
				// Want to redirect somewhere to login?
				//-----------------------------------------
				
				if( !$redirect AND $this->login_methods[ $k ]['login_login_url'] )
				{
					$redirect = $this->login_methods[ $k ]['login_login_url'];
				}
			}
  		}
  		
		//-----------------------------------------
		// If we found a login url, go to it now
		// but only if we aren't already logged in
		//-----------------------------------------
		
  		if( $this->return_code != 'SUCCESS' AND $redirect )
  		{
  			$this->registry->getClass('output')->silentRedirect( $redirect );
  		}
  		
  		return ( $this->return_code == 'SUCCESS' ) ? true : false;
  	}
  	
	/**
	 * Logout callback - called when a user logs out
	 *
	 * @access	public
	 * @return	mixed		Possible redirection based on login method config, else array of messages
	 */
  	public function logoutCallback()
  	{
  		$returns 	= array();
  		$redirect	= '';
  		
		foreach( $this->modules as $k => $obj_reference )
		{
			if( method_exists( $obj_reference, 'logoutCallback' ) )
			{
				$returns[] = $obj_reference->logoutCallback();
			}
			
			//-----------------------------------------
			// Grab first logout callback url found
			//-----------------------------------------

			if( !$redirect AND $this->login_methods[ $k ]['login_logout_url'] )
			{
				$redirect = $this->login_methods[ $k ]['login_logout_url'];
			}
  		}
  		
		//-----------------------------------------
		// If we found a logout url, go to it now
		//-----------------------------------------
		
  		if( $redirect )
  		{
  			$this->registry->getClass('output')->silentRedirect( $redirect );
  		}

  		return $returns;
  	}
  	
	/**
	 * Alternate login URL redirection
	 *
	 * @access	public
	 * @return	mixed		Possible redirection based on login method config, else false
	 */
  	public function checkLoginUrlRedirect()
  	{
  		$redirect	= '';
  		
		foreach( $this->modules as $k => $obj_reference )
		{
			if( method_exists( $obj_reference, 'checkLoginUrlRedirect' ) )
			{
				$obj_reference->checkLoginUrlRedirect();
			}
			
			//-----------------------------------------
			// Grab first logout callback url found
			//-----------------------------------------

			if( !$redirect AND $this->login_methods[ $k ]['login_login_url'] )
			{
				$redirect = $this->login_methods[ $k ]['login_login_url'];
			}
  		}
  		
		//-----------------------------------------
		// If we found a logout url, go to it now
		//-----------------------------------------
		
  		if( $redirect )
  		{
  			$this->registry->getClass('output')->silentRedirect( $redirect );
  		}

  		return false;
  	}
  	
	/**
	 * User maintenance callback
	 *
	 * @access	public
	 * @return	mixed		Possible redirection based on login method config, else false
	 */
  	public function checkMaintenanceRedirect()
  	{
  		$redirect	= '';
  		
		foreach( $this->modules as $k => $obj_reference )
		{
			if( method_exists( $obj_reference, 'checkMaintenanceRedirect' ) )
			{
				$obj_reference->checkMaintenanceRedirect();
			}
			
			//-----------------------------------------
			// Grab first logout callback url found
			//-----------------------------------------

			if( !$redirect AND $this->login_methods[ $k ]['login_maintain_url'] )
			{
				$redirect = $this->login_methods[ $k ]['login_maintain_url'];
			}
  		}
  		
		//-----------------------------------------
		// If we found a logout url, go to it now
		//-----------------------------------------
		
  		if( $redirect )
  		{
  			$this->registry->getClass('output')->silentRedirect( $redirect );
  		}

  		return false;
  	}
  	
  	
	/**
	 * Check if the email is already in use
	 *
	 * @access	public
	 * @param	string		Email address
	 * @return	boolean		Authenticate successful
	 */
  	public function emailExistsCheck( $email )
  	{
  		$this->return_code = 'METHOD_NOT_DEFINED';
  		
		foreach( $this->modules as $k => $obj_reference )
		{
			if( method_exists( $obj_reference, 'emailExistsCheck' ) )
			{
				$obj_reference->emailExistsCheck( $email );
				$this->return_code 		= $obj_reference->return_code;
				
				if( $this->return_code AND !in_array( $this->return_code, array( 'EMAIL_NOT_IN_USE', 'METHOD_NOT_DEFINED', 'WRONG_AUTH', 'WRONG_OPENID' ) ) )
				{
					break;
				}
			}
  		}
  		
		if( $this->return_code AND !in_array( $this->return_code, array( 'EMAIL_NOT_IN_USE', 'METHOD_NOT_DEFINED', 'WRONG_AUTH', 'WRONG_OPENID' ) ) )
		{
			return true;
		}
		else
		{
			return false;
		}
  	}
  	
	/**
	 * Change a user's email address
	 *
	 * @access	public
	 * @param	string		Old Email address
	 * @param	string		New Email address
	 * @return	boolean		Email changed successfully
	 */
  	public function changeEmail( $old_email, $new_email )
  	{
  		$this->return_code = 'METHOD_NOT_DEFINED';
  		
		foreach( $this->modules as $k => $obj_reference )
		{
			if( method_exists( $obj_reference, 'changeEmail' ) )
			{
				$obj_reference->changeEmail( $old_email, $new_email );
				$this->return_code 		= $obj_reference->return_code;
			}
  		}
  		
  		return ( $this->return_code == 'SUCCESS' ) ? true : false;
  	}
  	
	/**
	 * Change a user's password
	 *
	 * @access	public
	 * @param	string		Email address
	 * @param	string		New password
	 * @return	boolean		Password changed successfully
	 */
  	public function changePass( $email, $new_pass )
  	{
  		$this->return_code = 'METHOD_NOT_DEFINED';
  		
		foreach( $this->modules as $k => $obj_reference )
		{
			if( method_exists( $obj_reference, 'changePass' ) )
			{
				$obj_reference->changePass( $email, $new_pass );
				$this->return_code 		= $obj_reference->return_code;
			}
  		}
  		
  		return ( $this->return_code == 'SUCCESS' ) ? true : false;
  	}
  	
	/**
	 * Change a login name
	 *
	 * @access	public
	 * @param	string		Old Name
	 * @param	string		New Name
	 * @param	string		User's email address
	 * @return	boolean		Request was successful
	 */
	public function changeName( $old_name, $new_name, $email_address )
  	{
  		$this->return_code = 'METHOD_NOT_DEFINED';
  		
		foreach( $this->modules as $k => $obj_reference )
		{
			if( method_exists( $obj_reference, 'changeName' ) )
			{
				$obj_reference->changeName( $old_name, $new_name, $email_address );
				$this->return_code 		= $obj_reference->return_code;
			}
  		}
  		
  		return ( $this->return_code == 'SUCCESS' ) ? true : false;
  	}
  	
	/**
	 * Create a user's account
	 *
	 * @access	public
	 * @param	array		Array of member information
	 * @return	boolean		Account created successfully
	 */
  	public function createAccount( $member=array() )
  	{
	  	if( !is_array( $member ) )
	  	{
		  	$this->return_code = 'FAIL';
		  	return false;
	  	}
	  	
	  	$this->return_code = '';

		foreach( $this->modules as $k => $obj_reference )
		{
			if( method_exists( $obj_reference, 'createAccount' ) )
			{
				$obj_reference->createAccount( $member );
				$this->return_code 		= $obj_reference->return_code;
				$this->return_details  .= $obj_reference->return_details . '<br />';
			}
  		}
  	}
  	
	/**
	 * Determine email address or username login
	 *
	 * @access	public
	 * @return	integer		[1=Username, 2=Email, 3=Both]
	 */
  	public function emailOrUsername()
  	{
  		$username 	= false;
  		$email		= false;

		foreach( $this->login_methods as $k => $method )
		{
			if( $method['login_user_id'] == 'username' )
			{
				$username	= true;
			}
			else if( $method['login_user_id'] == 'email' )
			{
				$email		= true;
			}
  		}
  		
  		if( $username AND !$email )
  		{
  			return 1;
  		}
  		else if( !$username AND $email )
  		{
  			return 2;
  		}	
  		else if( $username AND $email )
  		{
  			return 3;
  		}

		//-----------------------------------------
		// If we're here, none of the methods
		//	want username or email, which is bad
		//-----------------------------------------
		
  		else
  		{
  			return 1;
  		}
  	}
  	
	/**
	 * Get additional login form HTML add/replace
	 *
	 * @access	public
	 * @return	mixed		Null or Array [0=Add or replace flag, 1=Array of HTML blocks to add/replace with]
	 */
  	public function additionalFormHTML()
  	{
  		$has_more_than_one	= false;
  		$additional_details	= array();
  		$add_or_replace		= null;
  		
  		if( count($this->login_methods) > 1 )
  		{
  			$has_more_than_one	= true;
  			$add_or_replace		= 'add';
  		}

		foreach( $this->login_methods as $k => $method )
		{
			if( !$has_more_than_one )
			{
				if( $method['login_replace_form'] == 1 )
				{
					$add_or_replace	= 'replace';
				}
				else
				{
					$add_or_replace	= 'add';
				}
			}
			
			if( $this->is_admin_auth )
			{
				if( $method['login_alt_acp_html'] )
				{
					$additional_details[]	= $method['login_alt_acp_html'];
				}
			}
			else
			{
				if( $method['login_alt_login_html'] )
				{
					$additional_details[]	= $method['login_alt_login_html'];
				}
			}
  		}
  		
		if( count($additional_details) )
		{
			return array( $add_or_replace, $additional_details );
		}
		else
		{
			return null;
		}
  	}
}