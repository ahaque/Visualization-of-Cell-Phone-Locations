<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Login handler abstraction : MSN Live Method
 * Last Updated: $Date: 2009-02-04 15:03:59 -0500 (Wed, 04 Feb 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @link		http://www.
 * @since		Monday 10th August 2009 (5:41)
 * @version		$Revision: 3887 $
 *
 */

/**
 * Turn off strict error reporting for openid
 */
error_reporting( E_ALL ^ E_NOTICE ^ E_STRICT );

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class login_live extends login_core implements interface_login
{
	/**
	 * Windows Live Library
	 *
	 * @access	private
	 * @var		object
	 */
	private $live;

	/**
	 * Temporary data store
	 *
	 * @access	private
	 * @var		array
	 */
	private $data_store	= array();
	
	/**
	 * Login method configuration
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $method_config	= array();
	
	/**
	 * Windows Live configuration
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $live_config	= array();
	
	/**
	 * Missing required extensions
	 *
	 * @access	protected
	 * @var		bool
	 */
	protected $missingExtensions	= false;
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @param	array 		Configuration info for this method
	 * @param	array 		Custom configuration info for this method
	 * @return	void
	 */
	public function __construct( ipsRegistry $registry, $method, $conf=array() )
	{
		$this->method_config	= $method;
		$this->live_config		= $conf;
		
		parent::__construct( $registry );
		
		//-----------------------------------------
		// Check for necessary extensions
		//-----------------------------------------
		
		if( !function_exists('mhash') OR !function_exists('mcrypt_decrypt') )
		{
			$this->missingExtensions	= true;
			return false;
		}

		//-----------------------------------------
		// And grab libs
		//-----------------------------------------
		
		require_once IPS_ROOT_PATH . "sources/loginauth/live/lib/windowslivelogin.php";
		
		$this->live	= WindowsLiveLogin::initFromXml( $this->live_config['key_file_location'] );
		$this->live->setDebug( FALSE );
	}
	
	/**
	 * Authenticate the request
	 *
	 * @access	public
	 * @param	string		Username
	 * @param	string		Email Address
	 * @param	string		Password
	 * @return	boolean		Authentication successful
	 */
	public function authenticate( $username, $email_address, $password )
	{
		//-----------------------------------------
		// Check admin authentication request
		//-----------------------------------------
		
		if ( $this->is_admin_auth )
		{
			$this->adminAuthLocal( $username, $email_address, $password );
			
  			if ( $this->return_code == 'SUCCESS' )
  			{
  				return true;
  			}
		}
		
		if( $this->missingExtensions )
		{
			return false;
		}

		//-----------------------------------------
		// Reset array
		//-----------------------------------------
		
		$this->auth_errors = array();

		//-----------------------------------------
		// OK?
		//-----------------------------------------
		
		$user = $this->live->processLogin( $_REQUEST );
		
		if( !$user AND $this->request['use_live'] )
		{
			$this->registry->output->silentRedirect( $this->live_config['login_url'] . '?appid=' . $this->live->getAppId() );
		}
		
		$userToken	= $user->getToken();
		
		$user		= $this->live->processToken( $userToken );
		
		if ($user)
		{
			$userId	= $user->getId();
		}
		else
		{
			$this->return_code = 'NO_USER';
			return false;
		}

		if ( count($this->auth_errors) )
		{
			$this->return_code = $this->return_code ? $this->return_code : 'NO_USER';
			return false;
		}

		$this->_loadMember( $userId );

		if ( $this->member_data['member_id'] )
		{
			$this->return_code = 'SUCCESS';
		}
		else
		{
			$email	= $name	= '';
			
			$this->member_data = $this->createLocalMember( array(
															'members'			=> array(
																						 'email'					=> $email,
																						 'name'						=> $name,
																						 'members_l_username'		=> strtolower($name),
																						 'members_display_name'		=> $name,
																						 'members_l_display_name'	=> strtolower($name),
																						 'joined'					=> time(),
																						 'members_created_remote'	=> 1,
																						 'live_id'					=> $userId,
																						),
															'profile_portal'	=> array(
																						),
													)		);

			$this->return_code = 'SUCCESS';
		}

		if( $user->usePersistentCookie() )
		{
			$this->request['rememberMe'] =  $this->data_store['cookiedate'] ;
		}

		return true;
	}
	
	/**
	 * Load a member from an MSN Live user token
	 *
	 * @access	private
	 * @param	string 		Token
	 * @return	void
	 */
	private function _loadMember( $userToken )
	{
		$check = $this->DB->buildAndFetch( array( 'select'	=> 'member_id',
														  'from'	=> 'members',
														  'where'	=> "live_id='" . $this->DB->addSlashes( $userToken ) . "'"
												)		);

		if( $check['member_id'] )
		{
			$this->member_data = IPSMember::load( $check['member_id'], 'extendedProfile,groups' );
		}
	}
}