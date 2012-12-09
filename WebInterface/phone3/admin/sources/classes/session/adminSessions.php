<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Admin session handler
 * Last Updated: $Date: 2009-08-24 20:56:22 -0400 (Mon, 24 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @since		Who knows...
 * @version		$Revision: 5041 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class adminSessions extends ips_MemberRegistry
{
	/**
	 * Variable for session validated
	 *
	 * @access	private
	 * @var		array
	 */
	protected $session_data	= array();
	
	/**
	 * Timeout variable
	 *
	 * @access	private
	 * @var		int
	 */
	private $_time_out_mins	= 120;
	
	/**
	 * Admin session
	 *
	 * @access	private
	 * @var		string
	 */
	private $_adsess        = '';
	
	/**
	 * Whether they are validated or not
	 *
	 * @access	private
	 * @var		bool
	 */
	private $_validated		= false;
	
	/**
	 * Message to display
	 *
	 * @access	private
	 * @var		string
	 */
	private $_message		= '';
	
	/**
	 * Authorize
	 *
	 * @access	public
	 * @return	void
	 */
	public function __construct()
    {
		/* Make object */
		$this->registry = ipsRegistry::instance();
		$this->DB       = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		
    	//--------------------------------------------
		// Got a cookie wookey?
		//--------------------------------------------

		$_adsess                    = ipsRegistry::$request['adsess'];
		$_time_out_mins				= ( defined( 'IPB_ACP_SESSION_TIME_OUT' ) ) ? IPB_ACP_SESSION_TIME_OUT : 60;

		//-----------------------------------------
		// If the cookie doesn't match URL... use URL?
		//-----------------------------------------
		
		if ( $_adsess )
		{
			$this->session_type  = 'url';
			ipsRegistry::$request['adsess'] = $_adsess;
		}
		
		//--------------------------------------------
		// Continue...
		//--------------------------------------------
		
		if ( ! ipsRegistry::$request['adsess'] )
		{
			//--------------------------------------------
			// No URL adsess found, lets log in.
			//--------------------------------------------
			
			return $this->_response( 0, '' );
		}
		else
		{
			//--------------------------------------------
			// We have a URL adsess, lets verify...
			//--------------------------------------------
			
			$this->DB->build( array( 'select' => '*',
											  'from'   => 'core_sys_cp_sessions',
											  'where'  => "session_id='" . IPSText::md5clean( ipsRegistry::$request['adsess'] ) . "'" ) );
													
			$this->DB->execute();
			
			$session_data = $this->DB->fetch();

			$_tab_data = unserialize( $session_data['session_app_data'] );
			$_tab_data = ( is_array( $_tab_data ) ) ? $_tab_data : array();
			
			if ( $session_data['session_id'] == "" )
			{
				//--------------------------------------------
				// Fail-safe, no DB record found, lets log in..
				//--------------------------------------------
				
				return $this->_response( 0, '' );
			}
			else if ($session_data['session_member_id'] == "")
			{
				//--------------------------------------------
				// No member ID is stored, log in!
				//--------------------------------------------
				
				return $this->_response( 0, 'session_nomemberid' );
			}
			else
			{
				//--------------------------------------------
				// Key is good, check the member details
				//--------------------------------------------
			
				$this->DB->build( array(
														  'select'   => 'm.*',
														  'from'     => array( 'members' => 'm' ),
														  'where'    => "member_id=".intval($session_data['session_member_id']),
														  'add_join' => array( 0 => array( 'select' => 'g.*',
																						   'from'   => array( 'groups' => 'g' ),
																						   'where'  => 'm.member_group_id=g.g_id',
																						   'type'   => 'left'
																						 ),
																			   1 => array( 'select' => 's.*',
																						   'from'   => array( 'core_sys_login' => 's' ),
																						   'where'  => 's.sys_login_id = m.member_id',
																						   'type'   => 'left'
																						 )
																			)
												 )     );
														 
				$this->DB->execute();
		
				self::$data_store = $this->DB->fetch();
				
				self::$data_store = self::instance()->setUpSecondaryGroups( self::$data_store );
			
				//--------------------------------------------
				// Get perms
				//--------------------------------------------
				
				if ( self::$data_store['member_id'] == "" )
				{
					//--------------------------------------------
					// Ut-oh, no such member, log in!
					//--------------------------------------------
					
					return $this->_response( 0, 'session_invalidmid' );
				}
				else
				{
					//--------------------------------------------
					// Member found, check passy
					//--------------------------------------------
					
					//if ( $session_data['session_member_login_key'] != self::$data_store['member_login_key'] )
					//{
					//	//--------------------------------------------
					//	// Passys don't match..
					//	//--------------------------------------------
					//	
					//	return $this->_response( 0, 'Session member password mismatch' );
					//}
					//else
					//{
						//--------------------------------------------
						// Do we have admin access?
						//--------------------------------------------
					
						if (self::$data_store['g_access_cp'] != 1)
						{
							return $this->_response( 0, 'session_noaccess' );
						}
						else
						{
							$this->_validated = TRUE;
						}
					//}
				}
			}
		}
		
		//--------------------------------------------
		// If we're here, we're valid...
		//--------------------------------------------
		
		if ( $this->_validated === TRUE )
		{
			if ( $session_data['session_running_time'] < ( time() - $_time_out_mins * 60 ) )
			{
				$this->_validated = FALSE;
				return $this->_response( 0, 'session_timeout' );
			}
			
			//------------------------------
			// Are we checking IP's?
			//------------------------------
			
			else if ( IPB_ACP_IP_MATCH )
			{
				$first_ip  = preg_replace( "/^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})/", "\\1.\\2.\\3", $session_data['session_ip_address'] );
				$second_ip = preg_replace( "/^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})/", "\\1.\\2.\\3", self::instance()->ip_address               );
				
				if ( $first_ip != $second_ip )
				{
					$this->_validated = FALSE;
					return $this->_response( 0, 'session_mismatchip' );
				}
			}

			self::setMember( self::$data_store['member_id'] );
			
			//-----------------------------------------
			// Fix up secondary groups
			//-----------------------------------------
			
			if ( self::$data_store['mgroup_others'] )
			{
				$groups_id = explode( ',', self::$data_store['mgroup_others'] );
				$masks     = array();
				$cache     = ipsRegistry::cache()->getCache('group_cache');
				
				if ( count( $groups_id ) )
				{
					foreach( $groups_id as $pid )
					{
						if ( ! isset($cache[ $pid ]['g_id']) OR ! $cache[ $pid ]['g_id'] )
						{
							continue;
						}

						//-----------------------------------------
						// Got masks?
						//-----------------------------------------

						if ( $cache[ $pid ]['g_perm_id'] )
						{
							self::$data_store['g_perm_id'] .= ',' . $cache[ $pid ]['g_perm_id'];
						}
					}
					
				}
			}
			
			//-----------------------------------------
			// Current Location, used for online list
			//-----------------------------------------
			
			$module		= ipsRegistry::$request['module'] != 'ajax' ? ipsRegistry::$request['module'] : $session_data['session_location'];
			$location	= $session_data['session_url'];
			
			if ( ( IPS_APP_COMPONENT ) && ipsRegistry::$request['module'] != 'ajax' )
			{
				$location = str_ireplace( "login=yes"						, "" , ipsRegistry::$settings['query_string_safe'] );
				$location = ltrim( $location								, '?' );
				$location = preg_replace( "!adsess=(\w){32}!"				, "" , $location );
				$location = preg_replace( "!&mshow=(.+?)*!i"				, "" , $location );
				$location = preg_replace( "!&st=(.+?)*!i"					, "" , $location );
				$location = preg_replace( "!&messageinabottleacp=(.+?)*!i"	, "" , $location );
			}
			
			/* Compare user-agent stuff */
			$session_data['_session_app_data'] = unserialize( $session_data['session_app_data'] );
		
			if ( is_array( $session_data['_session_app_data']  ) AND $session_data['_session_app_data']['uagent_key'] )
			{
				if ( $session_data['_session_app_data']['uagent_raw'] != self::instance()->user_agent )
				{
					$session_data['_session_app_data']               = self::_processUserAgent();
					$session_data['_session_app_data']['uagent_raw'] = self::instance()->user_agent;
				}
			}
			else
			{
				$session_data['_session_app_data']               = self::_processUserAgent();
				$session_data['_session_app_data']['uagent_raw'] = self::instance()->user_agent;
			}
			
			//-----------------------------------------
			// Done...
			//-----------------------------------------
			
			$this->DB->update( 'core_sys_cp_sessions',
													  array( 'session_running_time' => time(),
															 'session_location'     => $module,
															 'session_url'          => $location,
															 'session_app_data'     => serialize( $session_data['_session_app_data'] ),
															 'session_member_name'  => self::$data_store['members_display_name'],																 
														   ),
													  'session_member_id='.intval(self::$data_store['member_id'])." and session_id='".ipsRegistry::$request['adsess']."'" );
				
			
			return $this->_response( 1, '', $session_data['_session_app_data'] );
		}
    }
	
	/**
	 * Get the validation status
	 *
	 * @access	public
	 * @return	bool
	 */
	public function getStatus()
	{
		return $this->_validated;
	}
	
	/**
	 * Get the validation message
	 *
	 * @access	public
	 * @return	string
	 */
	public function getMessage()
	{
		return $this->registry->class_localization->words[ $this->_message ];
	}
	
	/**
	 * Grab the user agent from the DB if required
	 *
	 * @access	protected
	 * @return	array 		Array of user agent info from the DB
	 */
	protected function _processUserAgent()
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
		// Get useragent stuff
		//-----------------------------------------

		if ( ! $this->registry->isClassLoaded( 'userAgentFunctions' ) )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/useragents/userAgentFunctions.php' );
			$this->registry->setClass( 'userAgentFunctions', new userAgentFunctions( $this->registry ) );
		}

		$uAgent = $this->registry->getClass( 'userAgentFunctions' )->findUserAgentID( self::instance()->user_agent );

		if ( $uAgent['uagent_key'] === NULL )
		{
			$uAgent = array( 'uagent_key'     => '__NONE__',
							 'uagent_version' => 0,
							 'uagent_name'    => '',
							 'uagent_type'    => '',
							 'uagent_bypass'  => 0 );
		}

		return $uAgent;
	}
	
	/**
	 * Set the response
	 *
	 * @access	protected
	 * @param	bool		Authenticated or not
	 * @param	string		Message
	 * @param	array 		Array of user agent data
	 * @return	void
	 */
	protected function _response( $validated, $message, $userAgentData=array() )
	{
		$this->_validated   = $validated;
		$this->_message     = $message;
		$this->session_data = $userAgentData;
		
		return;
	}
}