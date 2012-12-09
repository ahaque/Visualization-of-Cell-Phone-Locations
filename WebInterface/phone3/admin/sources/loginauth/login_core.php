<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Login handler abstraction
 * Last Updated: $Date: 2009-08-25 16:41:08 -0400 (Tue, 25 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @since		Tuesday 1st March 2005 (11:52)
 * @version		$Revision: 5045 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class login_core
{
	/**#@+
	 * Registry objects
	 *
	 * @access	protected
	 * @var		object
	 */
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $memberData;
	/**#@-*/
	
	/**
	 * Authentication errors
	 *
	 * @access	public
	 * @var		array
	 */
	public $auth_errors 	= array();
	
	/**
	 * Return code
	 *
	 * @access	public
	 * @var		string
	 */
	public $return_code 	= "";
	
	/**
	 * Member information
	 *
	 * @access	public
	 * @var		array
	 */
	public $member_data  	= array();
	
	/**
	 * Flag : Admin authentication
	 *
	 * @access	public
	 * @var		boolean
	 */
	public $is_admin_auth	= false;
	
	/**
	 * Unlock account time left
	 *
	 * @access	public
	 * @var		integer
	 */
	public $account_unlock	= 0;

	/**
	 * Force email check
	 *
	 * @access private
	 * @var		boolean
	 */
	private $_forceEmailCheck = FALSE;
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	void
	 */
	public function __construct( ipsRegistry $registry )
	{
		$this->registry = $registry;
		$this->DB       = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->lang     = $this->registry->getClass('class_localization');
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
	}
	
	/**
	 * Force email check flag, currently used for facebook
	 *
	 * @access	public
	 * @param 	boolean
	 * @return  null
	 */
	public function setForceEmailCheck( $boolean )
	{
		$this->_forceEmailCheck = ( $boolean ) ? TRUE : FALSE;
	}
	
	/**
	 * Local authentication
	 *
	 * @access	public
	 * @param	string		Username
	 * @param	string		Email Address
	 * @param	string		Password
	 * @return	boolean		Authentication successful
	 */
	public function authLocal( $username, $email_address, $password )
	{
		$password = md5( $password );
		
		//-----------------------------------------
		// Type of login
		//-----------------------------------------
		
		$type	= 'username';
		
		if( is_array($this->method_config) AND $this->method_config['login_folder_name'] == 'internal' )
		{
			$type = $this->method_config['login_user_id'];
		}
		
		if( $this->_forceEmailCheck === TRUE OR ( $email_address AND ! $username ) )
		{
			$type = 'email';
		}

		switch( $type )
		{
			case 'username':
				if( IPSText::mbstrlen( $username ) > 32 )
				{
					$this->return_code = 'NO_USER';
					return false;
				}

				$this->member_data = IPSMember::load( $username, 'groups', 'username' );
			break;
			
			case 'email':
				$this->member_data = IPSMember::load( $email_address, 'groups', 'email' );
			break;
		}

		//-----------------------------------------
		// Got an account
		//-----------------------------------------
		
		if ( ! $this->member_data['member_id'] )
		{
			$this->return_code = 'NO_USER';
			return false;
		}
	
		//-----------------------------------------
		// Verify it is not blocked
		//-----------------------------------------
		
		if( !$this->_checkFailedLogins() )
		{
			return false;
		}
	
		//-----------------------------------------
		// Check password...
		//-----------------------------------------
		
		if ( IPSMember::authenticateMember( $this->member_data['member_id'], $password ) != true )
		{ 
			if( !$this->_appendFailedLogin() )
			{
				return false;
			}
			
			$this->return_code = 'WRONG_AUTH';
			return false;
		}
		else
		{
			$this->return_code = 'SUCCESS';
			return false;
		}
	}
	
	/**
	 * Admin authentication
	 *
	 * @access	public
	 * @param	string		Username
	 * @param	string		Email Address
	 * @param	string		Password
	 * @return	boolean		Authentication successful
	 */
	public function adminAuthLocal( $username, $email_address, $password )
	{
		return $this->authLocal( $username, $email_address, $password );
	}

	/**
	 * Create a local member account [public interface]
	 *
	 * @access	public
	 * @param	array		Member Information [members,pfields,profile_portal]
	 * @return	array		New member information
	 * @deprecated			Just redirects to IPSMember::create
	 */
	public function createLocalMember( $member )
	{
		$member['members']['members_created_remote']	= true;
		
		return IPSMember::create( $member );
	}

	/**
	 * Check failed logins
	 *
	 * @access	private
	 * @return	boolean		Account ok or not
	 */
	private function _checkFailedLogins()	
	{
		if ( $this->settings['ipb_bruteforce_attempts'] > 0 )
		{
			$failed_attempts = explode( ",", IPSText::cleanPermString( $this->member_data['failed_logins'] ) );
			$failed_count	 = 0;
			$total_failed	 = 0;
			$thisip_failed	 = 0;
			$non_expired_att = array();
			
			if( is_array($failed_attempts) AND count($failed_attempts) )
			{
				foreach( $failed_attempts as $entry )
				{
					if ( ! strpos( $entry, "-" ) )
					{
						continue;
					}
					
					list ( $timestamp, $ipaddress ) = explode( "-", $entry );
					
					if ( ! $timestamp )
					{
						continue;
					}
					
					$total_failed++;
					
					if ( $ipaddress != $this->member->ip_address )
					{
						continue;
					}
					
					$thisip_failed++;
					
					if ( $this->settings['ipb_bruteforce_period'] AND
						$timestamp < time() - ($this->settings['ipb_bruteforce_period']*60) )
					{
						continue;
					}
					
					$non_expired_att[] = $entry;
					$failed_count++;
				}
				
				sort($non_expired_att);
				$oldest_entry  = array_shift( $non_expired_att );
				list($oldest,) = explode( "-", $oldest_entry );
			}

			if( $thisip_failed >= $this->settings['ipb_bruteforce_attempts'] )
			{
				if( $this->settings['ipb_bruteforce_unlock'] )
				{
					if( $failed_count >= $this->settings['ipb_bruteforce_attempts'] )
					{
						$this->account_unlock	= $oldest;
						$this->return_code		= 'ACCOUNT_LOCKED';
						
						return false;
					}
				}
				else
				{
					$this->return_code = 'ACCOUNT_LOCKED';
					
					return false;
				}
			}
		}
		
		return true;
	}
	
	/**
	 * Append a failed login
	 *
	 * @access	private
	 * @return	boolean		Account ok or not
	 */
	private function _appendFailedLogin()
	{
		if( $this->settings['ipb_bruteforce_attempts'] > 0 )
		{
			$failed_logins 	 = explode( ",", $this->member_data['failed_logins'] );
			$failed_logins[] = time() . '-' . $this->member->ip_address;
			
			$failed_count	 = 0;
			$total_failed	 = 0;
			$non_expired_att = array();

			foreach( $failed_logins as $entry )
			{
				list($timestamp,$ipaddress) = explode( "-", $entry );
				
				if( !$timestamp )
				{
					continue;
				}
				
				$total_failed++;
				
				if( $ipaddress != $this->member->ip_address )
				{
					continue;
				}
				
				if( $this->settings['ipb_bruteforce_period'] > 0
					AND $timestamp < time() - ($this->settings['ipb_bruteforce_period']*60) )
				{
					continue;
				}
				
				$failed_count++;
				$non_expired_att[] = $entry;
			}

			if( $this->member_data['member_id'] AND !$this->settings['failed_done'] )
			{
				IPSMember::save( $this->member_data['email'], array( 
																	'core' => array(
																					'failed_logins' => implode( ",", $non_expired_att ), 
																					'failed_login_count' => $total_failed 
																					) 
																	)		);

				$this->settings['failed_done']	= true;
			}

			if( $failed_count >= $this->settings['ipb_bruteforce_attempts'] )
			{
				if( $this->settings['ipb_bruteforce_unlock'] )
				{
					sort($non_expired_att);
					$oldest_entry  = array_shift( $non_expired_att );
					list($oldest,) = explode( "-", $oldest_entry );
					
					$this->account_unlock = $oldest;
				}

				$this->return_code = 'ACCOUNT_LOCKED';
				return false;
			}
		}
		
		return true;
	}
}