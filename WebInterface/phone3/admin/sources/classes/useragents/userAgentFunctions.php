<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Skin Functions
 * Last Updated: $Date: 2009-06-08 08:56:58 -0400 (Mon, 08 Jun 2009) $
 *
 * Owner: Matt
 * @author 		$Author: matt $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @since		9th March 2005 11:03
 * @version		$Revision: 4734 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class userAgentFunctions
{
	/**#@+
	 * Registry object
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
	protected $cache;
	/**#@-*/
		
	/**
	 * Error handle
	 *
	 * @access	private
	 * @var		array
	 */
	private $_errorMsgs = array();
	
	/**
	 * Message handle
	 *
	 * @access	private
	 * @var		array
	 */
	private $_generalMsgs = array();
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @return	void
	 */
	public function __construct( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Make object
		//-----------------------------------------
		
		$this->registry   =  $registry;
		$this->DB	      =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->member     =  $this->registry->member();
		$this->cache	  =  $this->registry->cache();
	}
	
	/**
	 * Reset error handle
	 *
	 * @access	protected
	 * @return	void
	 */
	final protected function _resetErrorHandle()
	{
		$this->_errorMsgs = array();
	}
	
	/**
	 * Add an error message
	 *
	 * @access	private
	 * @param	string		Error message to add
	 * @return	void
	 */
	final protected function _addErrorMessage( $error )
	{
		$this->_errorMsgs[] = $error;
	}
	
	/**
	 * Fetch error messages
	 *
	 * @access	public
	 * @return	array	Array of messages or FALSE
	 */
	public function fetchErrorMessages()
	{
		return ( count( $this->_errorMsgs ) ) ? $this->_errorMsgs : FALSE;
	}
	
	/**
	 * Reset error handle
	 *
	 * @access	protected
	 * @return	void
	 */
	final protected function _resetMessageHandle()
	{
		$this->_generalMsgs = array();
	}
	
	/**
	 * Add an error message
	 *
	 * @access	protected
	 * @param	string		Error message to add
	 * @return	void
	 */
	final protected function _addMessage( $error )
	{
		$this->_generalMsgs[] = $error;
	}
	
	/**
	 * Fetch error messages
	 *
	 * @access	public
	 * @return	array	Array of messages or FALSE
	 */
	public function fetchMessages()
	{
		return ( count( $this->_generalMsgs ) ) ? $this->_generalMsgs : FALSE;
	}
	
	/**
	 * Rebuilds the master user agents
	 *
	 * @access	public
	 * @return	bool	true
	 */
	public function rebuildMasterUserAgents()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$userAgents = array();
		$names      = array();
		$count      = 0;
		
		//-----------------------------------------
		// Get file...
		//-----------------------------------------
		
		require_once( IPS_ROOT_PATH . 'extensions/userAgents.php' );
		
		//-----------------------------------------
		// Build names
		//-----------------------------------------
		
		foreach( $BROWSERS as $key => $data )
		{
			$names[] = "'" . $key . "'";
		}
		
		foreach( $ENGINES as $key => $data )
		{
			$names[] = "'" . $key . "'";
		}
		
		//-----------------------------------------
		// Delete old 'uns
		//-----------------------------------------
		
		$this->DB->delete( 'core_uagents', 'uagent_key IN (' . implode( ",", $names ) . ")" );
		
		//-----------------------------------------
		// Add new 'uns
		//-----------------------------------------
		
		foreach( $ENGINES as $key => $data )
		{
			foreach( $data['b_regex'] as $k => $d )
			{
				$_regex   = $k;
				$_capture = $d;
			}
			
			$this->DB->insert( 'core_uagents', array( 'uagent_name'          => $data['b_title'],
														 'uagent_key'			=> $key,
														 'uagent_regex'         => $_regex,
														 'uagent_regex_capture' => intval( $_capture ),
														 'uagent_position'      => $count,
														 'uagent_type'          => 'search' ) );
														
			$count++;
		}
		
		/* Reset count */
		$count = 1000;
		
		foreach( $BROWSERS as $key => $data )
		{
			foreach( $data['b_regex'] as $k => $d )
			{
				$_regex   = $k;
				$_capture = $d;
			}
			
			if ( $data['b_position'] )
			{
				$count = $data['b_position'];
			}
			
			$this->DB->insert( 'core_uagents', array( 'uagent_name'          => $data['b_title'],
														 'uagent_key'			=> $key,
														 'uagent_regex'         => $_regex,
														 'uagent_regex_capture' => intval( $_capture ),
														 'uagent_position'      => $count,
														 'uagent_type'          => 'browser' ) );
			$count++;
		}
		
		$this->rebuildUserAgentCaches();
		
		return TRUE;
	}
	
	/**
	 * Saves a user agent group
	 *
	 * @access	public
	 * @param	string 		Group Title
	 * @param	array 		Array of raw useragent data (array( key => array( 'uagent_id', ..etc ) )
	 * @param	int			[Optional: Group ID. If passed, we update, if not, we add]
	 * @return	int 		User group id
	 */
	public function saveUserAgentGroup( $ugroup_title, $ugroupData, $ugroup_id=0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$data = array();
		
		//-----------------------------------------
		// Fix up data
		//-----------------------------------------
		
		if ( is_array( $ugroupData ) )
		{
			foreach( $ugroupData as $key => $array )
			{
				$data[ $key ] = array( 'uagent_id'       => $array['uagent_id'],
									   'uagent_key'      => $array['uagent_key'],
									   'uagent_type'     => $array['uagent_type'],
									   'uagent_versions' => $array['uagent_versions'] );
			}
		}
		
		//-----------------------------------------
		// Updating or what?
		//-----------------------------------------
		
		if ( $ugroup_id )
		{
			$this->DB->update( 'core_uagent_groups', array( 'ugroup_title' => $ugroup_title,
															   'ugroup_array' => serialize( $data ) ), 'ugroup_id=' . $ugroup_id );
		}
		else
		{
			$this->DB->insert( 'core_uagent_groups', array( 'ugroup_title' => $ugroup_title,
															   'ugroup_array' => serialize( $data ) ) );
															
			$ugroup_id = $this->DB->getInsertId();
		}
		
		$this->rebuildUserAgentGroupCaches();
		
		return $ugroup_id;
	}
	
	/**
	 * Fetch all agent groups
	 *
	 * @access	public
	 * @return	array 		Array of data
	 */
	public function fetchGroups()
	{
		//-----------------------------------------
		// Try and get the skin from the cache
		//-----------------------------------------
		
		$userAgentGroups = array();
		
		//-----------------------------------------
		// Get em!!
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*',
									   'from'   => 'core_uagent_groups',
									   'order'  => 'ugroup_title ASC'
							  )      );
							
		$this->DB->execute();
	
		while( $row = $this->DB->fetch() )
		{
			/* Unpack data */
			$row['_groupArray'] = ( $row['ugroup_array'] ) ? unserialize( $row['ugroup_array'] ) : $row['_groupArray'];
			$row['_arrayCount'] = count( $row['_groupArray'] );
			
			$userAgentGroups[ $row['ugroup_id'] ] = $row;
		}
		
		
		return $userAgentGroups;
	}
	
	/**
	 * Fetch agents from the DB
	 *
	 * @access	public
	 * @param	int			[Optional: Group ID of agents to return]
	 * @return	array 		Array of agents
	 */
	public function fetchAgents( $groupID=0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$userAgents = array();
		
		//-----------------------------------------
		// Get em!!
		//-----------------------------------------
		
		if ( ! $groupID )
		{
			$this->DB->build( array( 'select' => '*',
									 'from'   => 'core_uagents',
								     'order'  => 'uagent_position ASC, uagent_key ASC'
								  )      );
								
			$this->DB->execute();
		
			while( $row = $this->DB->fetch() )
			{
				$userAgents[ $row['uagent_id'] ] = $row;
			}
		}
		else
		{
			$uGroup = $this->DB->buildAndFetch( array( 'select' => '*',
													   'from'   => 'core_uagent_groups',
													   'where'  => 'ugroup_id=' . intval( $groupID ) ) );
			
			$userAgents = ( $uGroup['ugroup_array'] ) ? unserialize( $uGroup['ugroup_array'] ) : array();
		}
		
		return $userAgents;
	}
	
	/**
	 * Recaches the user-agents where-ever it needs recaching!
	 *
	 * @access	public
	 * @return	void
	 */
	public function rebuildUserAgentCaches()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$cache = array();
		
		//-----------------------------------------
		// Get rows...
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*',
									   'from'   => 'core_uagents',
									   'order'  => 'uagent_position ASC, uagent_key ASC'
							  )      );
							
		$this->DB->execute();
	
		while( $row = $this->DB->fetch() )
		{
			$cache[ $row['uagent_key'] ] = $row;
		}
		
		$this->cache->setCache( 'useragents', $cache, array( 'array' => 1, 'deletefirst' => 1 ) );
		
		//-----------------------------------------
		// Now rebuild groups
		//-----------------------------------------
		
		$this->rebuildUserAgentGroupCaches();
	}
	
	/**
	 * Recaches the user-agents where-ever it needs recaching!
	 *
	 * @access	public
	 * @return	void
	 */
	public function rebuildUserAgentGroupCaches()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$cache = array();
		
		//-----------------------------------------
		// Get rows...
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*',
									   'from'   => 'core_uagent_groups',
									   'order'  => 'ugroup_id ASC'
							  )      );
							
		$this->DB->execute();
	
		while( $row = $this->DB->fetch() )
		{
			$cache[ $row['ugroup_id'] ] = $row;
		}
		
		$this->cache->setCache( 'useragentgroups', $cache, array( 'array' => 1, 'deletefirst' => 1 ) );
		
		//-----------------------------------------
		// Rebuild Skin Map
		//-----------------------------------------
		
		$this->rebuildUserAgentSkinMap();
	}
	
	/**
	 * Recaches user agent to skin set map
	 * NOT CURRENTLY USED
	 *
	 * @access	public
	 * @return	void
	 */
	public function rebuildUserAgentSkinMap()
	{
		
	}
	
	/**
	 * Removes a user agent group
	 *
	 * @access	public
	 * @param	int		UA Group ID
	 * @return	void
	 */
	public function removeUserAgentGroup( $ugroup_id )
	{
		//-----------------------------------------
		// Remove it
		//-----------------------------------------
		
		$this->DB->delete( 'core_uagent_groups', 'ugroup_id=' . intval( $ugroup_id ) );
		
		//-----------------------------------------
		// Recache
		//-----------------------------------------
		
		$this->rebuildUserAgentGroupCaches();
	}
	
	/**
	 * Save user agents after edit
	 *
	 * @access	public
	 * @param	int			User Agent ID
	 * @param	string		User Agent "Key"
	 * @param	string		User Agent Name (Human title)
	 * @param	string		User Agent Regex
	 * @param	int			User Agent Regex Capture parenthesis #
	 * @param	string		User Agent Type (browser, search engine, other)
	 * @param	int			User Agent Position
	 * @return	array 		..of data
	 * <code>
	 * Exception Codes:
	 * NO_SUCH_UAGENT:		Could not locate user agent
	 * UAGENT_EXISTS: 		Could not rename user agent key
	 * MISSING_DATA:		Fields are missing
	 * REGEX_INCORRECT: 	The regex is incorrect
	 * </code>
	 */
	public function saveUserAgentFromEdit( $uagent_id, $uagent_key, $uagent_name, $uagent_regex, $uagent_regex_capture, $uagent_type, $uagent_position )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$uagent_id				= intval( $uagent_id );
		$uagent_key				= strtolower( IPSText::alphanumericalClean( $uagent_key ) );
		$uagent_name			= $uagent_name;
		$uagent_regex			= $uagent_regex;
		$uagent_regex_capture	= intval( $uagent_regex_capture );
		$uagent_type			= IPSText::alphanumericalClean( $uagent_type );
		$uagent_position        = intval( $uagent_position );
		
		if ( ! $uagent_id OR ! $uagent_key OR ! $uagent_name OR ! $uagent_regex OR ! $uagent_type )
		{
			throw new Exception( 'MISSING_DATA' );
	    }
	
		//-----------------------------------------
		// Fetch user agent data
		//-----------------------------------------
		
		$useragent = $this->DB->buildAndFetch( array( 'select' => '*',
													   		 'from'   => 'core_uagents',
													   		 'where'  => 'uagent_id=' . $uagent_id ) );
															
		if ( ! $useragent['uagent_id'] )
		{
			throw new Exception( 'NO_SUCH_UAGENT' );
		}
		
		//-----------------------------------------
		// Did we change the key?
		//-----------------------------------------
		
		if ( $useragent['uagent_key'] != $uagent_key )
		{
			$useragentTest = $this->DB->buildAndFetch( array( 'select' => '*',
														   			 'from'   => 'core_uagents',
																	 'where'  => 'uagent_key=\'' . $uagent_key . '\'' ) );
														
			if ( $useragentTest['uagent_id'] )
			{
				throw new Exception( "UAGENT_EXISTS" );
			}
		}
		
		//-----------------------------------------
		// Test syntax
		//-----------------------------------------
		
		if ( $this->testRegex( $uagent_regex ) !== TRUE )
		{
			throw new Exception( "REGEX_INCORRECT" );
		}
		
		//-----------------------------------------
		// Update
		//-----------------------------------------
		
		$this->DB->update( 'core_uagents', array( 'uagent_key'           => $uagent_key,
												     'uagent_name'          => $uagent_name,
												     'uagent_regex'         => $uagent_regex,
												     'uagent_regex_capture' => $uagent_regex_capture,
												     'uagent_position'		=> $uagent_position,
												     'uagent_type'			=> $uagent_type ), 'uagent_id=' . $uagent_id );
														
		//-----------------------------------------
		// Recache
		//-----------------------------------------
		
		$this->rebuildUserAgentCaches();
		
		//-----------------------------------------
		// Return
		//-----------------------------------------
		
		return $uagent_id;
	}
	
	/**
	 * Save replacement after add
	 *
	 * @access	public
	 * @param	string		User Agent "Key"
	 * @param	string		User Agent Name (Human title)
	 * @param	string		User Agent Regex
	 * @param	int			User Agent Regex Capture parenthesis #
	 * @param	string		User Agent Type (browser, search engine, other)
	 * @param	int			User Agent Position
	 * @return	array 		..of data
	 * <code>
	 * Exception Codes:
	 * UAGENT_EXISTS: 		Could not rename user agent key
	 * MISSING_DATA:		Fields are missing
	 * </code>
	 */
	public function saveUserAgentFromAdd( $uagent_key, $uagent_name, $uagent_regex, $uagent_regex_capture, $uagent_type, $uagent_position )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$uagent_key				= strtolower( IPSText::alphanumericalClean( $uagent_key ) );
		$uagent_name			= $uagent_name;
		$uagent_regex			= $uagent_regex;
		$uagent_regex_capture	= intval( $uagent_regex_capture );
		$uagent_type			= IPSText::alphanumericalClean( $uagent_type );
		$uagent_position		= intval( $uagent_position );
		
		if ( ! $uagent_key OR ! $uagent_name OR ! $uagent_regex OR ! $uagent_type )
		{
			throw new Exception( 'MISSING_DATA' );
	    }
	
		//-----------------------------------------
		// Check for an existing user agent
		//-----------------------------------------
		
		$useragentTest = $this->DB->buildAndFetch( array( 'select' => '*',
														   		 'from'   => 'core_uagents',
																 'where'  => 'uagent_key=\'' . $uagent_key . '\'' ) );
													
		if ( $useragentTest['uagent_id'] )
		{
			throw new Exception( "UAGENT_EXISTS" );
		}
		
		
		//-----------------------------------------
		// Update
		//-----------------------------------------
		
		$this->DB->insert( 'core_uagents', array( 'uagent_key'           => $uagent_key,
												     'uagent_name'          => $uagent_name,
												     'uagent_regex'         => $uagent_regex,
												     'uagent_regex_capture' => $uagent_regex_capture,
													 'uagent_position'		=> $uagent_position,
												     'uagent_type'			=> $uagent_type ) );
		
		$uagent_id = $this->DB->getInsertId();
		
		//-----------------------------------------
		// Recache
		//-----------------------------------------
		
		$this->rebuildUserAgentCaches();
		
		//-----------------------------------------
		// Return
		//-----------------------------------------
		
		return $uagent_id;
	}
	
	/**
	 * Reverts / Removes Replacement
	 *
	 * @access	public
	 * @param	int			User Agent ID
	 * @return	array 		All user agents for this skin set
	 * <code>
	 * Exception Codes:
	 * NO_SUCH_UAGENT:		Could not locate user agent
	 * </code>
	 */
	public function removeUserAgent( $uagent_id )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$uagent_id = intval( $uagent_id );

		//-----------------------------------------
		// Fetch replacement data
		//-----------------------------------------
		
		$useragentTest = $this->DB->buildAndFetch( array( 'select' => '*',
														   		 'from'   => 'core_uagents',
																 'where'  => 'uagent_id=' . $uagent_id ) );
													
		if ( ! $useragentTest['uagent_id'] )
		{
			throw new Exception( "NO_SUCH_UAGENT" );
		}
		
		//-----------------------------------------
		// Remove it...
		//-----------------------------------------
		
		$this->DB->delete( 'core_uagents', 'uagent_id=' . $uagent_id );
		
		//-----------------------------------------
		// Recache
		//-----------------------------------------
		
		$this->rebuildUserAgentCaches();
		
		//-----------------------------------------
		// Grab the adjusted user agents
		//-----------------------------------------
		
		$useragents = $this->fetchAgents();
		
		//-----------------------------------------
		// Reeee-turn
		//-----------------------------------------
		
		return $useragents;
	}
	
	/**
	 * Test the user's user-agent for a match in the database
	 *
	 * @access		public
	 * @param		string		[Optional: user agent raw string]
	 * @return		array 		array[ 'uagent_id' => int, 'uagent_key' => string, 'uagent_name' => string, 'uagent_version' => int ]
	 */
	public function findUserAgentID( $userAgent='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$userAgent    = ( $userAgent ) ? $userAgent : $this->member->user_agent;
		$uagentReturn = array( 'uagent_id'      => 0,
		 					   'uagent_key'     => NULL,
							   'uagent_name'    => NULL,
							   'uagent_type'    => NULL,
							   'uagent_version' => 0 );
		 						
		//-----------------------------------------
		// Test in the DB
		//-----------------------------------------
	
		$userAgentCache = $this->cache->getCache('useragents');

		foreach( $userAgentCache as $key => $data )
		{
			$regex = str_replace( '#', '\\#', $data['uagent_regex'] );
			
			if ( ! preg_match( "#{$regex}#i", $userAgent, $matches ) )
			{
				continue;
			}
			else
			{
				//-----------------------------------------
				// Okay, we got a match - finalize
				//-----------------------------------------
				
				if ( $data['uagent_regex_capture'] )
				{
					 $version = $matches[ $data['uagent_regex_capture'] ];
				}
				else
				{
					$version = 0;
				}
				
				$uagentReturn = array( 'uagent_id'      => $data['uagent_id'],
									   'uagent_key'     => $data['uagent_key'],
									   'uagent_name'    => $data['uagent_name'],
									   'uagent_type'	=> $data['uagent_type'],
									   'uagent_version' => intval( $version ) );
									
				break;
			}
		}
		
		return $uagentReturn;
	}
			
	/**
	 * Test regex for errors
	 *
	 * @access		public
	 * @param		string		Regex
	 * @return		boolean		(True is OK)
	 */
	public function testRegex( $regex )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$return = '';
		$regex  = str_replace( '#', '\\#', $regex );
		$this->_resetMessageHandle();
		
		//-----------------------------------------
		// Test...
		//-----------------------------------------
		
		ob_start();
		eval( "preg_match( '#" . $regex . "#', 'this is just a test' );" );
		$return = ob_get_contents();
		ob_end_clean();
		
		//-----------------------------------------
		// More data...
		//-----------------------------------------
		
		$this->_addMessage( $return );
	
		//-----------------------------------------
		// Return
		//-----------------------------------------
		
		return $return ? FALSE : TRUE;
	}

}