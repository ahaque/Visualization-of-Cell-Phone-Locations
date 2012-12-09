<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Skin Functions
 * Last Updated: $Date: 2009-02-04 15:03:36 -0500 (Wed, 04 Feb 2009) $
 *
 * Owner: Matt
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @since		9th March 2005 11:03
 * @version		$Revision: 3887 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class skinDifferences extends skinCaching
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
	protected $cache;
	/**#@-*/
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @return	void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make object */
		parent::__construct( $registry );
	}
	
	/**
	 * Fetch all skin difference sessions
	 *
	 * @access	public
	 * @return	array		Array of skin difference sessions
	 */
	public function fetchSessions()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$sessions = array();
		
		//-----------------------------------------
		// Get 'em
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'template_diff_session',
								 'order'  => 'diff_session_updated DESC' ) );
		
		
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			/* Prep dates */
			$row['_date'] = ipsRegistry::getClass( 'class_localization')->getDate( $row['diff_session_updated'], 'TINY' );
			$sessions[  $row['diff_session_id'] ] = $row;
		}
		
		return $sessions;
	}
	
	/**
	 * Remove a session
	 *
	 * @access	public
	 * @param	int			Session ID
	 * @return	bool		True
	 */
	public function removeSession( $sessionID )
	{
		/* Delete 'em */
		$this->DB->delete('templates_diff_import', 'diff_session_id='.$sessionID );
		
		$this->DB->delete( 'template_diff_session', 'diff_session_id='.$sessionID );

		$this->DB->delete( 'template_diff_changes', 'diff_session_id='.$sessionID );
		
		return TRUE;
	}
	
	/**
	 * Create new session
	 *
	 * @access	public
	 * @param	string		Diff session title
	 * @param	string		Compare HTML
	 * @param	boolean		Ignore new bits
	 * @return	int			New session ID
	 */
	public function createSession( $title, $content, $ignoreBits=FALSE )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$templateBits = array();
		
		//-----------------------------------------
		// Get number for missing template bits
		//-----------------------------------------
		
		$_bits = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count',
												  'from'   => 'skin_templates',
												  'where'  => 'template_set_id=0' ) );
		
		//-----------------------------------------
		// Create session
		//-----------------------------------------
		
		$this->DB->allow_sub_select = 1;
		
		$this->DB->insert( 'template_diff_session', array( 'diff_session_togo'    		 => intval( $_bits['count'] ),
														   'diff_session_done'    		 => 0,
														   'diff_session_title'   		 => $title,
														   'diff_session_updated'        => time(),
														   'diff_session_ignore_missing' => ( $ignoreBits === TRUE ) ? 1 : 0 ) );
																		
		$diffSesssionID = $this->DB->getInsertId();
		
		//-----------------------------------------
		// XML
		//-----------------------------------------

		require_once( IPS_KERNEL_PATH . 'classXML.php' );
		$xml = new classXML( IPS_DOC_CHAR_SET );
		
		//-----------------------------------------
		// Check to see if its an archive...
		//-----------------------------------------
		
		if ( strstr( $content, "<xmlarchive" ) )
		{
			/* It's an archive... */
			require( IPS_KERNEL_PATH . 'classXMLArchive.php' );
			$xmlArchive = new classXMLArchive( IPS_KERNEL_PATH );
		
			$xmlArchive->readXML( $content );
			
			/* We just want the templates.. */
			foreach( $xmlArchive->asArray() as $path => $fileData )
			{
				if ( $fileData['path'] == 'templates' )
				{
					$xml->loadXML( $fileData['content']);

					foreach( $xml->fetchElements( 'template' ) as $xmlelement )
					{
						$data = $xml->fetchElementsFromRecord( $xmlelement );

						if ( is_array( $data ) )
						{
							$templateBits[] = $data;
						}
					}
				}
			}
		}
		else
		{
			$xml->loadXML( $content );

			foreach( $xml->fetchElements( 'template' ) as $xmlelement )
			{
				$data = $xml->fetchElementsFromRecord( $xmlelement );

				if ( is_array( $data ) )
				{
					$templateBits[] = $data;
				}
			}
		}
		
		//-----------------------------------------
		// Got anything?
		//-----------------------------------------
		
		if ( ! count( $templateBits ) )
		{
			return FALSE;
		}
	
		//-----------------------------------------
		// Build session data
		//-----------------------------------------
	
		foreach( $templateBits as $bit )
		{
			$diffKey = $diffSesssionID . ':' . $bit['template_group'] . ':' . $bit['template_name'];
			
			if ( ! $seen[ $diffKey ] )
			{
				$this->DB->allow_sub_select = 1;
				
				$this->DB->insert( 'templates_diff_import', array( 'diff_key'          => $diffKey,
																   'diff_func_group'   => $bit['template_group'],
																   'diff_func_data'	   => $bit['template_data'],
																   'diff_func_name'    => $bit['template_name'],
																   'diff_func_content' => $bit['template_content'],
																   'diff_session_id'   => $diffSesssionID ) );
																				
				$seen[ $diffKey ] = 1;
			}
		}
		
		return $diffSesssionID;
	}
	
	/**
	 * Fetch a report
	 *
	 * @access	public
	 * @param	int			Session ID
	 * @return	array
	 */
	public function fetchReport( $diffSessionID )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$return = array( 'counts' => array( 'missing' => 0, 'changed' => 0 ), 'data' => array() );
		
		//-----------------------------------------
		// Get data
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*',
								 'from'   => 'template_diff_changes',
								 'where'  => 'diff_session_id='.$diffSessionID,
								 'order'  => 'diff_change_func_group ASC, diff_change_func_name ASC' ) );
		
		
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			//-----------------------------------------
			// Gen data
			//-----------------------------------------
			
			$row['_key']  = $diffSessionID.':'.$row['diff_change_func_group'].':'.$row['diff_change_func_name'];
			$row['_size'] = IPSLib::sizeFormat( IPSLib::strlenToBytes( IPSText::mbstrlen( $row['diff_change_content'] ) ) );
			
			//-----------------------------------------
			// Diff type
			//-----------------------------------------
			
			if ( ! $row['diff_change_type'] )
			{
				$row['_is'] = 'new';
				$return['counts']['missing']++;
			}
			else
			{
				$row['_is'] = 'changed';
				$return['counts']['changed']++;
			}
			
			//-----------------------------------------
			// Add data...
			//-----------------------------------------
			
			$return['data'][ $row['diff_change_func_group'] ][ $row['_key'] ] = $row;
		}
		
		return $return;
	}
	
	/**
	 * Fetch a session
	 *
	 * @access	public
	 * @param	int			Session ID
	 * @return	mixed 		Array of data, or false
	 */
	public function fetchSession( $diffSessionID )
	{
		$session = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'template_diff_session', 'where' => 'diff_session_id='.$diffSessionID ) );
		
		return ( $session['diff_session_id'] ) ? $session : FALSE;
	}
	
	/**
	 * Return the total number of bits to process
	 *
	 * @access	public
	 * @param	int			Session ID
	 * @return	int			Number of bits
	 */
	public function fetchNumberSessionTemplateBits( $diffSesssionID )
	{
		$count = $this->DB->buildAndFetch( array( 'select' => 'count(*) as count',
												  'from'   => 'templates_diff_import',
												  'where'  => 'diff_session_id=' . intval( $diffSesssionID ) ) );
												
		return intval( $count['count'] );
	}
	
	/**
	 * Return the total number of master template bits
	 *
	 * @access	public
	 * @return	int			Number of bits
	 */
	public function fetchNumberTemplateBits()
	{
		$count = $this->DB->buildAndFetch( array( 'select' => 'count(*) as count',
												  'from'   => 'skin_templates',
												  'where'  => 'template_set_id=0' ) );
												
		return intval( $count['count'] );
	}
	
}