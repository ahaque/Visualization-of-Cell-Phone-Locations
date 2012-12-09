<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Converge Handler
 * Last Updated: $Date: 2009-05-29 23:46:26 -0400 (Fri, 29 May 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	IP.Converge
 * @since		2.1.0
 * @version		$Revision: 4707 $
 *
 */

/**
* Script type
*
*/
define( 'IPB_THIS_SCRIPT', 'api' );

/**
* Matches IP address of requesting API
* Set to 0 to not match with IP address
*/
define( 'CVG_IP_MATCH', 1 );

require_once( '../initdata.php' );

//===========================================================================
// MAIN PROGRAM
//===========================================================================

require_once( IPS_ROOT_PATH . 'sources/base/ipsRegistry.php' );
require_once( IPS_ROOT_PATH . 'sources/base/ipsController.php' );

//-----------------------------------------
// Set up cookie stuff
//-----------------------------------------

$registry = ipsRegistry::instance();
$registry->init();

//--------------------------------
//  Initialize the FUNC
//--------------------------------

if ( ! ipsRegistry::$settings['ipconverge_enabled'] )
{
	@header( "Content-type: text/xml" );
	print "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
			<methodResponse>
			   <fault>
			      <value>
			         <struct>
			            <member>
			               <name>faultCode</name>
			               <value>
			                  <int>1</int>
			                  </value>
			               </member>
			            <member>
			               <name>faultString</name>
			               <value>
			                  <string>IP.Converge is not enabled from your ACP Control Panel. Log into your IP.Board ACP and visit: System -&gt; Log In Management, and click the red x icon for the IP.Converge login method to enable it.</string>
			               </value>
			               </member>
			            </struct>
			         </value>
			            </fault>
			   </methodResponse>";
	exit();
}

//===========================================================================
// Define Service
//===========================================================================

require_once( IPS_KERNEL_PATH . 'classApiServer.php' );

//===========================================================================
// Create the XML-RPC Server
//===========================================================================

$server     = new classApiServer();
$webservice = new handshake_server( $registry );
$webservice->classApiServer =& $server;
$api        = $server->decodeRequest();

$server->addObjectMap( $webservice, 'UTF-8' );

//-----------------------------------------
// Saying "info" or actually doing some
// work? Info is used by converge app to
// ensure this file exists and to grab the
// apps name.
// Codes:
// IPB : Invision Power Board
// IPD : Invision Power Dynamic
// IPN : Invision Power Nexus
//-----------------------------------------

if ( $_REQUEST['info'] )
{
	@header( "Content-type: text/plain" );
	print "<info>\n";
	print "\t<productname>" . htmlspecialchars( ipsRegistry::$settings['board_name'] ) . "</productname>\n";
	print "\t<productcode>IPB</productcode>\n";
	print "</info>";
	exit();
}
//-----------------------------------------
// Post log in:
// This is hit after a successful converge
// log in has been made. It's up the the local
// app to check the incoming data, and set
// cookies (optional)
//-----------------------------------------
else if ( $_REQUEST['postlogin'] )
{
	//-----------------------------------------
	// INIT
	//-----------------------------------------
	
	$session_id  = addslashes( substr( trim( $_GET['session_id'] ), 0, 32 ) );
	$key         = substr( trim( $_GET['key'] ), 0, 32 );
	$member_id   = intval( $_GET['member_id'] );
	$product_id  = intval( $_GET['product_id'] );
	$set_cookies = intval( $_GET['cookies'] );
	
	//-----------------------------------------
	// Get converge
	//-----------------------------------------
	
	$converge = $registry->DB()->buildAndFetch( array( 'select' => '*',
															'from'   => 'converge_local',
															'where'  => "converge_active=1 AND converge_product_id=" . $product_id ) );
	//-----------------------------------------
	// Get member....
	//-----------------------------------------

	$session = $registry->DB()->buildAndFetch( array( 'select' => '*',
														   'from'   => 'sessions',
														   'where'  => "id='" . $session_id . "' AND member_id=" . $member_id ) );

	if ( $session['member_id'] )
	{
		$member = IPSMember::load( $member_id );
																	
		if ( md5( $member['member_login_key'] . $converge['converge_api_code'] ) == $key )
		{
			if ( $set_cookies )
			{
				IPSCookie::set( "member_id" , $member['member_id']       , 1 );
				IPSCookie::set( "pass_hash" , $member['member_login_key'], 1 );
			}
			
			IPSCookie::set( "session_id", $session_id                , -1);
		}
		
		//-----------------------------------------
		// Update session
		//-----------------------------------------
		
		$registry->DB()->update( 'sessions', array( 'browser'    => $registry->member()->user_agent,
													 'ip_address' => $registry->member()->ip_address ), "id='" . $session_id . "'" );
	}
	
	//-----------------------------------------
	// Is this a partial member?
	// Not completed their sign in?
	//-----------------------------------------
	
	if ( $member['members_created_remote'] )
	{
		$pmember = $registry->DB()->buildAndFetch( array( 'select' => '*', 'from' => 'members_partial', 'where' => "partial_member_id={$member['member_id']}" ) );
		
		if ( $pmember['partial_member_id'] )
		{
			ipsRegistry::getClass('output')->silentRedirect( ipsRegistry::$settings['board_url'].'/index.'.ipsRegistry::$settings['php_ext'] . '?act=reg&do=complete_login&mid='.$member['member_id'].'&key='.$pmember['partial_date'] );
			exit();
		}
		else
		{
			//-----------------------------------------
			// Redirect...
			//-----------------------------------------
	
			ipsRegistry::getClass('output')->silentRedirect( ipsRegistry::$settings['board_url'].'/index.'.ipsRegistry::$settings['php_ext'] );
		}
	}
	else
	{
		//-----------------------------------------
		// Redirect...
		//-----------------------------------------

		ipsRegistry::getClass('output')->silentRedirect( ipsRegistry::$settings['board_url'].'/index.'.ipsRegistry::$settings['php_ext'] );
	}
}
else
{
	$server->getXmlRpc();
}


exit;


class handshake_server
{
   /**
    * Defines the service for WSDL
    *
    * @access	public
    * @var 		array
    */			
	public $__dispatch_map		= array();
	
   /**
    * Global registry
    *
    * @access 	private
    * @var 		object
    */
	private $registry;
	
	/**
	* IPS API SERVER Class
	*
    * @access	public
    * @var 		object
    */
	public $classApiServer;
	
	/**
	 * CONSTRUCTOR
	 * 
	 * @access	public
	 * @return 	void
	 **/		
	public function __construct( $registry ) 
    {
		//-----------------------------------------
		// Store registry reference
		//-----------------------------------------
		
		$this->registry =  $registry;
		$this->lang     =  $this->registry->getClass('class_localization');
		$this->settings =& $this->registry->fetchSettings();
		
    	//-----------------------------------------
    	// Build dispatch list
    	//-----------------------------------------
    
		$this->__dispatch_map[ 'handshakeStart' ] = array(
														   'in'  => array(
																		'reg_id'           => 'int',
																		'reg_code'         => 'string',
																		'reg_date'         => 'string',
																		'reg_product_id'   => 'int',
																		'converge_url'     => 'string',
																		'acp_email'        => 'string',
																		'acp_md5_password' => 'string',
																		'http_user'        => 'string',
																		'http_pass' 	   => 'string' ),
														   'out' => array( 'response' => 'xmlrpc' )
														 );
														
		$this->__dispatch_map[ 'handshakeEnd' ] = array(
														   'in'  => array(
																		'reg_id'              => 'int',
																		'reg_code'            => 'string',
																		'reg_date'            => 'string',
																		'reg_product_id'      => 'int',
																		'converge_url'        => 'string',
																		'handshake_completed' => 'int' ),
														   'out' => array( 'response' => 'xmlrpc' )
														 );
														
		$this->__dispatch_map[ 'handshakeRemove' ] = array(
														   'in'  => array(
																		'reg_product_id'      => 'int',
																		'reg_code'            => 'string' ),
														   'out' => array( 'response' => 'xmlrpc' )
														 );
			
		
	}
	
	/**
	 * handshake_server::handshake_start()
	 *
	 * Returns all data...
	 * 
	 * @access	public
	 * @param	integer		$reg_id			Converge reg ID
	 * @param	string		$reg_code		Converge API Code (MUST BE PRESENT IN ALL RETURNED API REQUESTS).
	 * @param	integer		$reg_date		Unix stamp of converge request start time
	 * @param	integer		$reg_product_id	Converge product ID (MUST BE PRESENT IN ALL RETURNED API REQUESTS)
	 * @param	string		$converge_url	Converge application base url (no slashes or paths)
	 * @return	mixed		xml / boolean false
	 **/	
	public function handshakeStart( $reg_id='', $reg_code='', $reg_date='', $reg_product_id='', $converge_url='', $acp_email='', $acp_md5_password='', $http_user='', $http_pass='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$reg_id			  = intval( $reg_id );
		$reg_code         = IPSText::md5Clean( $reg_code );
		$reg_date	      = intval( $reg_date );
		$reg_product_id	  = intval( $reg_product_id );
		$converge_url	  = IPSText::parseCleanValue( $converge_url );
		$acp_email	      = IPSText::parseCleanValue( $acp_email );
		$acp_md5_password = IPSText::md5Clean( $acp_md5_password );
		
		$this->registry->getClass( 'class_localization' )->loadLanguageFile( array( 'api_langbits' ), 'core' );
		
		//-----------------------------------------
		// Check ACP user
		//-----------------------------------------
		
		if ( ! $acp_email AND ! $acp_md5_password )
		{
			$this->classApiServer->apiSendError( 500, $this->lang->words['missing_email'] );
			return false;
		}
		else
		{
			$member = IPSMember::load( $acp_email, 'extendedProfile,groups' );
		
			if ( ! $member['member_id'] )
			{
				$this->classApiServer->apiSendError( 501, $this->lang->words['bad_email'] );
				return false;
			}
			else
			{
				//-----------------------------------------
				// Are we an admin?
				//-----------------------------------------
				
				if ( $member['g_access_cp'] != 1 )
				{
					$this->classApiServer->apiSendError( 501, $this->lang->words['no_acp_access'] );
					return false;
				}

				//-----------------------------------------
				// Check password...
				//-----------------------------------------

				if ( IPSMember::authenticateMember( $member['member_id'], $acp_md5_password ) != true )
				{ 
					$this->classApiServer->apiSendError( 501, $this->lang->words['bad_email'] );
					return false;
				}
			}
		}
		
		//-----------------------------------------
		// Just send it all back and start
		// A row in the converge_local table with
		// the info, but don't flag as active...
		//-----------------------------------------
		
		$reply = array( 'master_response' => 1,
						'reg_id'          => $reg_id,
						'reg_code'        => $reg_code,
						'reg_date'        => $reg_date,
						'reg_product_id'  => $reg_product_id,
						'converge_url'    => $converge_url );
						
		//-----------------------------------------
		// Add into DB
		//-----------------------------------------
		
		$this->registry->DB()->insert( 'converge_local', array( 'converge_api_code'   => $reg_code,
															 	 'converge_product_id' => $reg_product_id,
																 'converge_added'      => $reg_date,
																 'converge_ip_address' => my_getenv('REMOTE_ADDR'),
																 'converge_url'        => $converge_url,
																 'converge_active'	   => 0,
																 'converge_http_user'  => $http_user,
																 'converge_http_pass'  => $http_pass ) );
			
		//-----------------------------------------
		// Send reply...
		//-----------------------------------------
		
						
		$this->classApiServer->apiSendReply( $reply );
	}
	
	/**
	 * handshake_server::handshake_end()
	 *
	 * Returns all data...
	 * 
	 * @access	public
	 * @param	integer		$reg_id					Converge reg ID
	 * @param	string		$reg_code				Converge API Code (MUST BE PRESENT IN ALL RETURNED API REQUESTS).
	 * @param	integer		$reg_date				Unix stamp of converge request start time
	 * @param	integer		$reg_product_id			Converge product ID (MUST BE PRESENT IN ALL RETURNED API REQUESTS)
	 * @param	string		$converge_url			Converge application base url (no slashes or paths)
	 * @param	integer		$handshake_completed	All done flag
	 * @return	mixed		xml / boolean false
	 **/	
	public function handshakeEnd( $reg_id='', $reg_code='', $reg_date='', $reg_product_id='', $converge_url='', $handshake_completed='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$reg_id			     = intval( $reg_id );
		$reg_code            = IPSText::md5Clean( $reg_code );
		$reg_date	         = intval( $reg_date );
		$reg_product_id	     = intval( $reg_product_id );
		$converge_url	     = IPSText::parseCleanValue( $converge_url );
		$handshake_completed = intval( $handshake_completed );
		
		$this->registry->getClass( 'class_localization' )->loadLanguageFile( array( 'api_langbits' ), 'core' );
		
		//-----------------------------------------
		// Grab data from the DB
		//-----------------------------------------
		
		$converge = $this->registry->DB()->buildAndFetch( array( 'select' => '*',
																	  'from'   => 'converge_local',
																	  'where'  => "converge_api_code='" . $reg_code . "' AND converge_product_id=" . $reg_product_id ) );
		
		//-----------------------------------------
		// Got it?
		//-----------------------------------------
															
		if ( $converge['converge_api_code'] )
		{
			$this->registry->DB()->update( 'converge_local', array( 'converge_active' => 0 ) );
			$this->registry->DB()->update( 'converge_local', array( 'converge_active' => 1 ), "converge_api_code = '" . $reg_code . "'" );

			//-----------------------------------------
			// Update log in methods
			//-----------------------------------------

			$this->registry->DB()->update( "login_methods", array( "login_enabled"      => 1,
																	"login_login_url"    => '',
			 														"login_maintain_url" => '',
			 														'login_user_id'		 => 'email',
																	"login_logout_url"	 => '',
																	"login_register_url" => '' ), "login_folder_name='ipconverge'" );

			$cache	= array();
			
			$this->registry->DB()->build( array( 'select' => '*', 'from' => 'login_methods', 'where' => 'login_enabled=1' ) );
			$this->registry->DB()->execute();
		
			while ( $r = $this->registry->DB()->fetch() )
			{	
				$cache[ $r['login_id'] ] = $r;
			}
			
			ipsRegistry::cache()->setCache( 'login_methods', $cache, array( 'array' => 1, 'deletefirst' => 1 ) );

			$this->classApiServer->apiSendReply( array( 'handshake_updated' => 1 ) );
		}
		else
		{
			$this->classApiServer->apiSendError( 500, $this->lang->words['no_handshake'] );
			return false;
		}
	}
	
	/**
	 * handshake_server::handshake_remove()
	 *
	 * Unconverges an application
	 * 
	 * @access	public
	 * @param	integer		$reg_id			Converge reg ID
	 * @param	string		$reg_code		Converge API Code (MUST BE PRESENT IN ALL RETURNED API REQUESTS).
	 * @return	mixed		xml / boolean false
	 **/	
	public function handshakeRemove( $reg_product_id='', $reg_code='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$reg_product_id = intval( $reg_product_id );
		$reg_code       = IPSText::md5Clean( $reg_code );
		
		//-----------------------------------------
		// Grab data from the DB
		//-----------------------------------------
		
		$converge = $this->registry->DB()->buildAndFetch( array( 'select' => '*',
																	  'from'   => 'converge_local',
																	  'where'  => "converge_api_code='" . $reg_code . "' AND converge_product_id=" . $reg_product_id ) );
		
		//-----------------------------------------
		// Check
		//-----------------------------------------
		
		if ( $converge['converge_active'] )
		{
			//-----------------------------------------
			// Remove app stuff
			//-----------------------------------------
															
			$this->registry->DB()->delete( 'converge_local', 'converge_product_id=' . intval( $reg_product_id ) );
		
			//-----------------------------------------
			// Switch over log in methods
			//-----------------------------------------
		
			$this->registry->DB()->update( "login_methods", array( "login_enabled"      => 0 ), "login_folder_name='ipconverge'" );

			$cache	= array();
			
			$this->registry->DB()->build( array( 'select' => '*', 'from' => 'login_methods', 'where' => 'login_enabled=1' ) );
			$this->registry->DB()->execute();
		
			while ( $r = $this->registry->DB()->fetch() )
			{	
				$cache[ $r['login_id'] ] = $r;
			}
			
			ipsRegistry::cache()->setCache( 'login_methods', $cache, array( 'array' => 1, 'deletefirst' => 1 ) );
																
			$this->classApiServer->apiSendReply( array( 'handshake_removed' => 1 ) );
		}
		else
		{
			$this->classApiServer->apiSendReply( array( 'handshake_removed' => 0 ) );
		}
	
	}

}