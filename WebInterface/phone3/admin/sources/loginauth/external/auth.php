<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Login handler abstraction : Internal Method
 * Last Updated: $Date: 2009-07-24 23:26:45 -0400 (Fri, 24 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @since		Tuesday 1st March 2005 (11:52)
 * @version		$Revision: 4940 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class login_external extends login_core implements interface_login
{
	/**
	 * Login method configuration
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $method_config	= array();
	
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
		$this->external_conf	= $conf;
		
		parent::__construct( $registry );
	}

	/**
	 * Compare passwords
	 *
	 * @access	private
	 * @param	string		Plain text password
	 * @param	array 		Record from the remote table
	 * @return	boolean
	 */
	private function _comparePasswords( $password, $remote_member )
	{
		$check_pass = $password;
		
		switch( REMOTE_PASSWORD_SCHEME )
		{
			case 'md5':
				$check_pass = md5($password);
			break;
			
			case 'sha1':
				$check_pass = sha1($password);
			break;
		}
		
		if ( $check_pass == $remote_member[ REMOTE_FIELD_PASS ] )
		{
			return true;
		}
		else
		{
			return false;
		}
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

		/*-------------------------------------------------------------------------*/
		// SET UP: Edit DB details to suit
		/*-------------------------------------------------------------------------*/

		define( 'REMOTE_DB_SERVER'  , $this->external_conf['REMOTE_DB_SERVER']   );
		define( 'REMOTE_DB_PORT'	, $this->external_conf['REMOTE_DB_PORT']	 );
		define( 'REMOTE_DB_DATABASE', $this->external_conf['REMOTE_DB_DATABASE'] );
		define( 'REMOTE_DB_USER'	, $this->external_conf['REMOTE_DB_USER']	 );
		define( 'REMOTE_DB_PASS'	, $this->external_conf['REMOTE_DB_PASS']	 );

		/*-------------------------------------------------------------------------*/
		// SET UP: Edit these DB tables to suit
		/*-------------------------------------------------------------------------*/

		define( 'REMOTE_TABLE_NAME'  , $this->external_conf['REMOTE_TABLE_NAME']  );
		define( 'REMOTE_FIELD_NAME'  , $this->external_conf['REMOTE_FIELD_NAME']  );
		define( 'REMOTE_FIELD_PASS'  , $this->external_conf['REMOTE_FIELD_PASS']  );
		define( 'REMOTE_EXTRA_QUERY' , $this->external_conf['REMOTE_EXTRA_QUERY'] );
		define( 'REMOTE_TABLE_PREFIX', $this->external_conf['REMOTE_TABLE_PREFIX'] );
		define( 'REMOTE_PASSWORD_SCHEME', $this->external_conf['REMOTE_PASSWORD_SCHEME'] );

		//-----------------------------------------
		// GET DB object
		//-----------------------------------------

		if ( ! class_exists( 'dbMain' ) )
		{
			require_once( IPS_KERNEL_PATH.'classDb.php' );
			require_once( IPS_KERNEL_PATH.'classDb' . ucwords($this->settings['sql_driver']) . ".php" );
		}

		$classname = "db_driver_" . $this->settings['sql_driver'];

		$RDB = new $classname;

		$RDB->obj['sql_database']			= REMOTE_DB_DATABASE;
		$RDB->obj['sql_user']				= REMOTE_DB_USER;
		$RDB->obj['sql_pass']				= REMOTE_DB_PASS;
		$RDB->obj['sql_host']				= REMOTE_DB_SERVER;
		$RDB->obj['sql_port']				= REMOTE_DB_PORT;  
		$RDB->obj['sql_tbl_prefix']			= REMOTE_TABLE_PREFIX;
		$RDB->obj['use_shutdown']			= 0;
		$RDB->obj['force_new_connection']	= 1;

		//--------------------------------
		// Get a DB connection
		//--------------------------------

		$RDB->connect();

		//-----------------------------------------
		// Get member from remote DB
		//-----------------------------------------

		$remote_member = $RDB->buildAndFetch( array( 'select' => '*',
															'from'   => REMOTE_TABLE_NAME,
															'where'  => REMOTE_FIELD_NAME."='".$RDB->addSlashes($username)."' ".REMOTE_EXTRA_QUERY ) );

		$RDB->disconnect();

		//-----------------------------------------
		// Check
		//-----------------------------------------

		if ( ! $remote_member[ REMOTE_FIELD_NAME ] )
		{
			$this->return_code = 'NO_USER';
			return false;
		}

		//-----------------------------------------
		// Check password
		//-----------------------------------------

		$password			= html_entity_decode($password, ENT_QUOTES);
		$html_entities		= array( "&#33;", "&#036;", "&#092;" );
		$replacement_char	= array( "!", "$", "\\" );
		$password 			= str_replace( $html_entities, $replacement_char, $password );

		if ( ! $this->_comparePasswords( $password, $remote_member ) )
		{
			$this->return_code = 'WRONG_AUTH';
			return false;
		}

		//-----------------------------------------
		// Still here? Then we have a username
		// and matching password.. so get local member
		// and see if there's a match.. if not, create
		// one!
		//-----------------------------------------

		$this->_loadMember( $username );

		if ( $this->member_data['member_id'] )
		{
			$this->return_code = 'SUCCESS';
			return false;
		}
		else
		{
			//-----------------------------------------
			// Got no member - but auth passed - create?
			//-----------------------------------------

			$this->return_code = 'SUCCESS';

			$this->member_data = $this->createLocalMember( array( 'members' => array( 'name' => $username, 'password' => $password ) ) );
			
			return true;
		}
	}

	/**
	 * Load a member
	 *
	 * @access	private
	 * @param	string		Username
	 * @return	void
	 */
	private function _loadMember( $username )
	{
		$member = $this->DB->buildAndFetch( array( 'select' => 'member_id', 'from' => 'members', 'where' => "members_l_username='" . strtolower($username) . "'" ) );
		
		if( $member['member_id'] )
		{
			$this->member_data = IPSMember::load( $member['member_id'], 'extendedProfile,groups' );
		}
	}
}