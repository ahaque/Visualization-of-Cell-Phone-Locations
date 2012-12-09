<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Login handler abstraction : LDAP method
 * Last Updated: $Date: 2009-05-18 22:05:12 -0400 (Mon, 18 May 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @since		2.1.0
 * @version		$Revision: 4668 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class login_ldap extends login_core implements interface_login
{
	/**
	 * LDAP Connection resource
	 *
	 * @access	private
	 * @var		resource
	 */
	private $connection_id;
	
	/**
	 * LDAP result set resource
	 *
	 * @access	private
	 * @var		resource
	 */
	private $result;

	/**
	 * LDAP Bind resource
	 *
	 * @access	private
	 * @var		resource
	 */
	private $bind_id;

	/**
	 * LDAP Fields to retrieve
	 *
	 * @access	private
	 * @var		array
	 */
	private $fields;

	/**
	 * LDAP DN resource
	 *
	 * @access	private
	 * @var		resource
	 */
	private $dn;
	
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
		$this->ldap_config		= $conf;
		
		parent::__construct( $registry );
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
				
		//-----------------------------------------
		// Get LDAP connection
		//-----------------------------------------
		
		$this->auth_errors	= array();
		
		$this->_ldapConnect();
		
		//-----------------------------------------
		// OK?
		//-----------------------------------------
		
		if ( count($this->auth_errors) )
		{
			$this->return_code = 'FAIL';
			return false;
		}
		
		//-----------------------------------------
		// Fix password
		//-----------------------------------------

		$password			= html_entity_decode($password, ENT_QUOTES);
		$html_entities		= array( "&#33;", "&#036;", "&#092;" );
		$replacement_char	= array( "!", "$", "\\" );
		$password 			= str_replace( $html_entities, $replacement_char, $password );
		
		//-----------------------------------------
		// Add suffix
		//-----------------------------------------
		
		if ( $this->ldap_config['ldap_username_suffix'] )
		{
			$real_username = $username . $this->ldap_config['ldap_username_suffix'];
		}
		else
		{
			$real_username = $username;
		}
		
		//-----------------------------------------
		// Add filter
		// - Donated by iCCT - thx!
		// concatenate the search for uid with the filter
		// string if the string is not empty - logical AND
		// as we are searching for uid match
		//-----------------------------------------
		
		if ( $this->ldap_config['ldap_filter'] )
		{
			$filter = '(&(' . $this->ldap_config['ldap_uid_field']. '=' . $real_username . ')(' . $this->ldap_config['ldap_filter'] . '))';
		}
		else
		{
			$filter = $this->ldap_config['ldap_uid_field']. '=' . $real_username;
		}
		
		//-----------------------------------------
		// Pulling any additional fields?
		//-----------------------------------------
		
		$fields_to_pull = array( $this->ldap_config['ldap_uid_field'] );
		
		if( $this->ldap_config['ldap_display_name'] )
		{
			$fields_to_pull[] = $this->ldap_config['ldap_display_name'];
		}
		
		if( $this->ldap_config['ldap_email_field'] )
		{
			$fields_to_pull[] = $this->ldap_config['ldap_email_field'];
		}
		
		$this->ldap_config['additional_fields'] = explode( ',', trim($this->ldap_config['additional_fields']) );

		if( is_array( $this->ldap_config['additional_fields'] ) and count( $this->ldap_config['additional_fields'] ) )
		{
			$fields_to_pull = array_merge( $fields_to_pull, $this->ldap_config['additional_fields'] );
		}
		
		//-----------------------------------------
		// Throw search to bind
		//-----------------------------------------
		
		$search = @ldap_search( $this->connection_id,
								$this->ldap_config['ldap_base_dn'],
								$filter,
								$fields_to_pull
							  );
		//$result = ldap_get_entries($this->connection_id, $search);		print "<pre>"; print_r( $result );	 
		
		$this->result = @ldap_first_entry( $this->connection_id, $search );
		
		if ( ! $this->result )
		{
			$this->return_code = 'WRONG_AUTH';
			return false;
		}
		
		$this->fields = @ldap_get_attributes( $this->connection_id, $this->result );
		$this->dn     = @ldap_get_dn( $this->connection_id, $this->result );
		
		//-----------------------------------------
		// Got something?
		//-----------------------------------------
		
		if ( is_array( $this->fields ) AND count( $this->fields ) > 0 )
		{
			if ( ! $this->ldap_config['ldap_user_requires_pass'] )
			{
				$real_password = "";
			}
			else
			{
				$real_password = $password;
			}
			
			//-----------------------------------------
			// Test bind
			//-----------------------------------------
			
			if ( @ldap_bind( $this->connection_id, $this->dn, $real_password) )
			{
				$this->_loadMember( $username );
				
				if ( $this->member_data['member_id'] )
				{
					$this->return_code = 'SUCCESS';
				}
				else
				{
					//-----------------------------------------
					// Got no member - but auth passed - create?
					//-----------------------------------------
					
					$this->member_data = $this->createLocalMember( array( 'members' => array( 'name' 				=> $this->fields[$this->ldap_config['ldap_display_name']][0] ? $this->fields[$this->ldap_config['ldap_display_name']][0] : $username, 
																		 'members_display_name' => $this->fields[$this->ldap_config['ldap_display_name']][0] ? $this->fields[$this->ldap_config['ldap_display_name']][0] : $username, 
																		 'password' 			=> $password,
																		 'email'				=> $this->fields[$this->ldap_config['ldap_email_field']][0] ? $this->fields[$this->ldap_config['ldap_email_field']][0] : $email_address, 
																		)
											) 		);

					$this->return_code = 'SUCCESS';
				}
			}
			else
			{
				$this->return_code = 'WRONG_AUTH';
			}
		}
		
		return true;
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
	
	/**
	 * Connect to LDAP server
	 *
	 * @access	private
	 * @return	boolean		Connection successful
	 */
	private function _ldapConnect()
	{
		//-----------------------------------------
		// LDAP compiled in PHP?
		//-----------------------------------------
		
		if ( ! extension_loaded('ldap') )
		{
			$this->auth_errors[] = 'LDAP extension not available';
			return false;
		}
		
		//-----------------------------------------
		// Get connection
		//-----------------------------------------
		
		if ( $this->ldap_config['ldap_port'] )
		{
			$this->connection_id = ldap_connect( $this->ldap_config['ldap_server'], $this->ldap_config['ldap_port'] );
		}
		else
		{
			$this->connection_id = ldap_connect( $this->ldap_config['ldap_server'] );
		}
		
		if ( ! $this->connection_id  )
		{
			$this->auth_errors[] = 'LDAP could not connect';
			return false;
		}
		
		//-----------------------------------------
		// Server version
		//-----------------------------------------
		
		if ( $this->ldap_config['ldap_server_version'] )
		{
			@ldap_set_option( $this->connection_id, LDAP_OPT_PROTOCOL_VERSION, $this->ldap_config['ldap_server_version'] );
		}
		
		//-----------------------------------------
		// Win2K3 AD with root DN
		//-----------------------------------------
		
		if ( $this->ldap_config['ldap_opt_referrals'] )
		{
			@ldap_set_option( $this->connection_id, LDAP_OPT_REFERRALS, true );
		}
		
		//-----------------------------------------
		// Bind
		//-----------------------------------------
		
		if ( $this->ldap_config['ldap_server_username'] AND $this->ldap_config['ldap_server_password'] )
		{
			$this->bind_id = @ldap_bind( $this->connection_id, $this->ldap_config['ldap_server_username'], $this->ldap_config['ldap_server_password'] );
		}
		else
		{
			# Anonymous bind
			
			$this->bind_id = @ldap_bind( $this->connection_id );
		}
		
		if ( ! $this->bind_id )
		{
			$this->auth_errors[] = 'LDAP could not bind to the server';
			return false;
		}
		
		return true;
	}
	
	/**
	 * Destructor
	 *
	 * @access	public
	 * @return	void
	 */
	public function __destruct()
	{
		if( $this->connection_id )
		{
			ldap_unbind( $this->connection_id );
		}
	}
	
}