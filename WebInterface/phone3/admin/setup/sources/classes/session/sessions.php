<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Setup sessions class
 * Last Updated: $Date: 2009-08-07 07:52:33 -0400 (Fri, 07 Aug 2009) $
 *
 * @author 		Matt Mecham
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @since		1st December 2008
 * @version		$Revision: 4999 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

define( 'IPB_UPGRADER_IP_MATCH', FALSE );

class sessions extends ips_MemberRegistry
{
	/**
	 * Variable for session validated
	 *
	 * @access	private
	 * @var		array
	 */
	private $_data	= array();

	/**
	 * Time out seconds
	 *
	 * @access	private
	 * @var		int
	 */
	private $_time_out_secs	= 86400;

	/**
	 * ACP session id
	 *
	 * @access	private
	 * @var		string
	 */
	private $_adsess		= '';

	/**
	 * Validated?
	 *
	 * @access	private
	 * @var		bool
	 */
	private $_validated		= false;

	/**
	 * Message to pass
	 *
	 * @access	private
	 * @var		string
	 */
	private $_message;

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

    	/* Grab session */
		$_s = IPSText::md5Clean( $this->request['s'] );

		/* Got a session? */
		if ( ! $_s )
		{
			return $this->_response( 0, '' );
		}
		else
		{
			$this->DB->build( array( 'select' => '*',
									 'from'   => 'upgrade_sessions',
									 'where'  => "session_id='" . $_s . "'" ) );

			$this->DB->execute();

			$_data = $this->DB->fetch();

			if ( ! $_data['session_id'] )
			{
				/* No record found */
				return $this->_response( 0, '' );
			}
			else if ( ! $_data['session_member_id'] )
			{
				/* No member ID found */
				return $this->_response( 0, 'Could not retrieve a valid member id' );
			}
			else
			{
				/* Load member */
				self::instance()->data_store = $this->registry->getClass('legacy')->loadMemberData( $_data['session_member_id'] );

				/* Member exists? */
				if ( ! self::instance()->data_store['email'] )
				{
					return $this->_response( 0, 'Member ID invalid' );
				}
				else
				{
					/* Authenticate */
					if ( $_data['session_member_key'] != $this->registry->getClass('legacy')->fetchAuthKey() )
					{
						return $this->_response( 0, 'Session not authenticated' );
					}
					else
					{
						/* ACP access? */
						if ( self::instance()->data_store['g_access_cp'] != 1)
						{
							return $this->_response( 0, 'You do not have access to the administrative CP' );
						}
						else
						{
							$this->_validated = TRUE;
						}
					}
				}
			}
		}

		//--------------------------------------------
		// If we're here, we're valid...
		//--------------------------------------------

		if ( $this->_validated === TRUE )
		{
			self::setUpMember();

			if ( $_data['session_current_time'] < ( time() - $this->_time_out_secs ) )
			{
				return $this->_response( 0, '' );
			}

			/* Check IPs? */
			else if ( IPB_UPGRADER_IP_MATCH )
			{
				$first_ip  = preg_replace( "/^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})/", "\\1.\\2.\\3", $_data['session_ip_address'] );
				$second_ip = preg_replace( "/^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})/", "\\1.\\2.\\3", self::instance()->ip_address );

				if ( $first_ip != $second_ip )
				{
					return $this->_response( 0, 'Your current IP address does not match the one in our records' );
				}
			}

			/* Still here? Lets update the session, then */
			$this->DB->update( 'upgrade_sessions', array( 'session_current_time' => time(),
														  'session_section'		 => $this->request['section'],
														  'session_post'		 => serialize( $_POST ),
														  'session_get'			 => serialize( $_GET ) ), 'session_id=\'' . $this->request['s'] . '\'' );


			/* If we're hitting the index and we have a valid session, go right to overview */
			if ( ! $this->request['section'] OR $this->request['section'] == 'index' )
			{
				$this->request['section'] = 'overview';
			}

			return $this->_response( 1, '' );
		}
    }

	/**
	 * Create a session
	 *
	 * @access	public
	 * @param	array 		Array of member Data
	 * @param	string 		Auth Key
	 * @return	nufink
	 */
	public function createSession( $member, $authKey )
	{
		$_bash = time() - $this->_time_out_secs;
		$_s    = md5( uniqid( microtime(), true ) . self::instance()->ip_address );

		if ( $member['member_id'] AND $authKey )
		{
			$this->DB->delete( 'upgrade_sessions', 'session_current_time < ' . $_bash );
			$this->DB->insert( 'upgrade_sessions', array( 'session_id' 		     => $_s,
														  'session_member_id'    => $member['member_id'],
														  'session_member_key'   => $authKey,
														  'session_start_time'   => time(),
														  'session_current_time' => time(),
														  'session_ip_address'   => self::instance()->ip_address,
														  'session_section'		 => 'index',
														  'session_post' 		 => serialize( array() ),
														  'session_get' 		 => serialize( array() ),
														  'session_data' 		 => serialize( array() ),
														  'session_extra'		 => '' ) );

			return $_s;
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * Get the validation code
	 *
	 * @access	public
	 * @return	mixed
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
		return $this->_message;
	}

	/**
	 * Set the response
	 *
	 * @access	protected
	 * @param	bool	Validated
	 * @param	string	Message
	 * @return	mixed
	 */
	protected function _response( $validated, $message )
	{
		$this->_validated = $validated;
		$this->_message   = $message;

		return;
	}
}
