<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Skin Functions
 * Last Updated: $Date: 2009-08-05 03:32:42 -0400 (Wed, 05 Aug 2009) $
 *
 * Owner: Matt
 * @author 		$Author: matt $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @since		9th March 2005 11:03
 * @version		$Revision: 4980 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class skinFunctions
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
	 * Tier Class
	 *
	 * @access	public
	 * @var		object
	 */
	public $recursiveTiers;

	/**
	 * Template count
	 * Maintains an array: [skin_set_id][group_name][count]
	 *
	 * @access private
	 * @var    array
	 */
	private $_templateCount;

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
	 * IN_DEV remap data
	 *
	 * @access	public
	 * @var		array
	 */
	public $remapData = array();
	
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
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache	  =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
		
		//-----------------------------------------
		// IN_DEV on?
		//-----------------------------------------
		
		if ( $this->registry->isClassLoaded( 'output' ) )
		{
			$this->remapData = $this->registry->output->buildRemapData( TRUE );
		}
		
		//-----------------------------------------
		// Load tier class and init it
		//-----------------------------------------

		require_once( IPS_ROOT_PATH . 'sources/classes/tiers/recursiveTiers.php' );

		$this->recursiveTiers = new recursiveTiers( $registry, array(   'sqlPrimaryID' => 'set_id',
																		'sqlParentID'  => 'set_parent_id',
																		'sqlTitle'     => 'set_name',
																		'sqlOrder'     => 'set_parent_id, set_id',
																		'sqlTable'     => 'skin_collections' ) );
	}

	/**
	 * Return recursive tiers handle
	 *
	 * @access public
	 * @return object
	 */
	public function getTiersFunction()
	{
		return $this->recursiveTiers;
	}
	
	/**
	 * Search template bits
	 *
	 * @access		public
	 * @param		int			Template set id to search
	 * @param		string		String to search
	 * @param		boolean		Is regex
	 * @param		boolean		Search all parents including master template set
	 * @return		array 		array( 'searchCount' => int, 'matchCount' => int, 'matches' => array )
	 * Exception Codes
	 * REGEX_INCORRECT			Regex is not valid
	 */
	public function searchTemplates( $setID, $searchString, $isRegex=FALSE, $searchParents=FALSE )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$searchString = IPSText::stripslashes( $searchString );
		$regexString  = str_replace( '#', '\#', str_replace( '"', '\"', $searchString ) );
		$_templates   = array();
		$_matches     = array();
		$_matchCount  = 0;
		$return		  = array( 'searchCount' => 0, 'matchCount' => 0, 'matches' => array() );
		
		/* Test Regex */
		if ( $isRegex )
		{
			ob_start();
			eval( "preg_match( \"#{$regexString}#i\", 'sometexthere' );");
			$return = ob_get_contents();
			ob_end_clean();
			
			if ( $return )
			{
				throw new Exception("REGEX_INCORRECT");
			}
		}
		
		/* Grab templates to search in */
		if ( $searchParents )
		{
			$_templates = $this->fetchTemplates( $setID );
		}
		else
		{
			
			$this->DB->build( array( 'select' => '*',
									 'from'   => 'skin_templates',
									 'where'  => 'template_set_id=' . $setID ) );
									
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$_templates[ $row['template_group'] ][ strtolower( $row['template_name'] ) ] = $row;
			}
		}
		
		/* Got anything? */
		if ( ! count( $_templates ) )
		{
			return $return;
		}
	
		/* You may continue... */
		foreach( $_templates as $_group => $_gdata )
		{
			foreach( $_gdata as $_name => $_data )
			{
				if ( $isRegex )
				{
					if ( preg_match( "#{$regexString}#i", $_data['template_content'] ) )
					{
						$_matches[ $_group ][ $_name ] = $_data;
						$_matchCount++;
					}
				}
				else if ( stristr( $_data['template_content'], $searchString ) )
				{
					$_matches[ $_group ][ $_name ] = $_data;
					$_matchCount++;
				}
				
				$_templateCount++;
			}
		}
		
		/* Return to sender */
		return array( 'searchCount' => $_templateCount, 'matchCount' => $_matchCount, 'matches' => $_matches );
	}
	
	/**
	 * Returns no. of modified template bits for
	 * the skin (and or group)
	 *
	 * @access	public
	 * @param	int		Template Set ID
	 * @param	int		[Optional: Template Group]
	 * @return	array 	Array of data
	 */
	public function fetchModifiedTemplateCount( $setID, $groupName='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$count = 0;

		//-----------------------------------------
		// Check...
		//-----------------------------------------

		if ( ! $setID )
		{
			return 0;
		}

		//-----------------------------------------
		// Get 'em
		//-----------------------------------------

		if ( $groupName )
		{
			if ( count( $this->_templateCount ) )
			{
				return intval( $this->_templateCount[ $setID ][ $groupName ]['count'] );
			}
			else
			{
				$count = $this->DB->buildAndFetch( array( 'select' => 'count(*) as count',
																 'from'   => 'skin_templates',
																 'where'  => 'template_set_id=' . $setID . ' AND template_group=\''. $groupName . '\'' ) );

				return intval( $count['count'] );
			}
		}
		else
		{
			if ( count( $this->_templateCount ) )
			{
				foreach( $this->_templateCount[ $setID ] as $group => $data )
				{
					$count += intval( $data['count'] );
				}

				return intval( $count );
			}
			else
			{
				$count = $this->DB->buildAndFetch( array( 'select' => 'count(*) as count',
																 'from'   => 'skin_templates',
																 'where'  => 'template_set_id=' . $setID ) );

				return intval( $count['count'] );
			}
		}
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
	 * @access 	public
	 * @param	boolean		Always return an array
	 * @return 	mixed 		Array of messages or (FALSE/empty array)
	 */
	public function fetchErrorMessages( $alwaysAnArray=FALSE )
	{
		return ( count( $this->_errorMsgs ) ) ? $this->_errorMsgs : ( ($alwaysAnArray === TRUE) ? array() :  FALSE );
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
	 * @access 	public
	 * @param	boolean		Always return an array
	 * @return 	mixed 		Array of messages or (FALSE/empty array)
	 */
	public function fetchMessages( $alwaysAnArray=FALSE )
	{
		return ( count( $this->_generalMsgs ) ) ? $this->_generalMsgs : ( ($alwaysAnArray === TRUE) ? array() :  FALSE );
	}

	/**
	 * Load a 'master_skin' template file. Used when developing
	 *
	 * @access	public
	 * @param	string		Name of skin file
	 * @return	void		Evals the skin to add the class in memory
	 */
	public function loadMasterSkinTemplate( $name, $id )
	{
		if ( ! count( $this->remapData ) )
		{
			$this->remapData = $this->registry->output->buildRemapData();
		}
		
		$_dir = $this->remapData['templates'][ $id ];
	
		if( ! file_exists(IPS_CACHE_PATH."cache/skin_cache/" . $_dir . "/".$name.".php") )
		{
			return;
		}
		
		//-----------------------------------------
		// Get data...
		//-----------------------------------------

		$data = implode( '', file( IPS_CACHE_PATH."cache/skin_cache/" . $_dir . "/".$name.".php" ) );

		//-----------------------------------------
		// Get template class
		//-----------------------------------------

		$toeval = $this->registry->templateEngine->convertCacheToEval( $data, $name . '_' . $id );
		$showme = ( strstr( $data, '{{{SHOWME}}}' ) ) ? $name : '';

		if( !class_exists( $name . '_' . $id ) )
		{
			ob_start();
			eval($toeval);
			$result = ob_get_contents();
		 	ob_end_clean();
	
			if ( strstr( $result, "Parse error" ) OR strstr( $result, 'Catchable fatal error:' ) OR $showme == $name )
			{
				IPSDebug::showTemplateError( $result, $toeval );
			}
		}
	}

	/**
	 * Returns whether or not a skin set is suitable for removal
	 *
	 * @access	public
	 * @param	int		Skin set ID
	 * @return	boolean	(True OK to remove, false NOT OK to remove)
	 */
	public function canRemoveSet( $setID )
	{
		$skinSetData  = $this->fetchSkinData( $setID );

		if ( $skinSetData['set_is_default'] )
		{
			return FALSE;
		}

		$allSkinCount = $this->DB->buildAndFetch( array( 'select' => 'count(*) as count',
													     'from'   => 'skin_collections',
													     'where'  => 'set_parent_id=' . $setID ) );

		if ( $allSkinCount['count'] >= 1 )
		{
			return FALSE;
		}
		
		/* Root skin? */
		if ( in_array( $setID, array_values( $this->remapData['export'] ) ) )
		{
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Removes a skin set
	 *
	 * @access	public
	 * @param	int		Skin set ID
	 * @return	boolean	(True OK to remove, false NOT OK to remove)
	 */
	public function removeSet( $setID )
	{
		$skinSetData  = $this->fetchSkinData( $setID );

		if ( $this->canRemoveSet( $setID ) !== TRUE )
		{
			return FALSE;
		}

		/* Update any children */
		$this->DB->update( 'skin_collections', array( 'set_parent_id' => 0 ), 'set_parent_id=' . $setID );

		/* Update any members */
		$this->DB->update( 'members', array( 'skin' => 0 ), 'skin=' . $setID );

		/* Update any forums */
		$this->DB->update( 'forums', array( 'skin_id' => 0 ), 'skin_id=' . $setID );

		/* Delete skin set */
		$this->DB->delete( 'skin_collections', 'set_id=' . $setID );

		/* Delete Templates */
		$this->DB->delete( 'skin_templates', 'template_set_id=' . $setID );

		/* Delete Replacements */
		$this->DB->delete( 'skin_replacements', 'replacement_set_id=' . $setID );

		/* Delete CSS */
		$this->DB->delete( 'skin_css', 'css_set_id=' . $setID );

		/* Delete Caches */
		$this->DB->delete( 'skin_templates_cache', 'template_set_id=' . $setID );

		/* Delete URL Mapping */
		$this->DB->delete( 'skin_url_mapping', 'map_skin_set_id=' . $setID );

		/* Delete CSS Files */
		$this->registry->adminFunctions->removeDirectory( IPS_CACHE_PATH  . PUBLIC_DIRECTORY . '/style_css/css_' . $setID );

		/* Delete PHP Cache Files */
		$this->registry->adminFunctions->removeDirectory( IPS_CACHE_PATH . 'cache/skin_cache/cacheid_' . $setID );

		/* Rebuild Tree Information */
		$this->rebuildTreeInformation();

		/* Rebuild Skin Set Caches */
		$this->rebuildSkinSetsCache();
		$this->rebuildURLMapCache();

		return TRUE;
	}

	/**
	 * Rebuild parent and child tree information
	 *
	 * @access	public
	 * @param	int		[Optional Skin set id to rebuild. If no value given, all skin sets are rebuilt]
	 * @return	boolean
	 */
	public function rebuildTreeInformation( $setID=NULL )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$_where = ( $setID !== NULL ) ? 'set_id=' . $setID : '';
		
		/* Rebuild tiers to ensure cache isn't stale after a new set has been added */
		$this->recursiveTiers->rebuildTiers();
		
		//-----------------------------------------
		// Grab skins and loop through 'em
		//-----------------------------------------

		$this->DB->build( array( 'select' => '*',
								 'from'   => 'skin_collections',
								 'where'  => $_where,
								 'order'  => 'set_id ASC' ) );

		$outer = $this->DB->execute();

		while( $row = $this->DB->fetch( $outer ) )
		{
			$_parents  = $this->recursiveTiers->fetchItemParents( $row['set_id'] );
			$_children = $this->recursiveTiers->fetchItemChildren( $row['set_id'] );

			$this->DB->update( 'skin_collections', array( 'set_parent_array' => serialize( ( is_array( $_parents ) ) ? $_parents : array() ),
														  'set_child_array'  => serialize( ( is_array( $_children ) ) ? $_children : array() ) ), 'set_id=' . $row['set_id'] );

			if( count($_parents) AND $setID )
			{
				foreach( $_parents as $parent )
				{
					$this->rebuildTreeInformation( $parent );
				}
			}
		}

		return TRUE;
	}

	/**
	 * Fetch replacements from the tree
	 * Very simple little function to return the replacements for a particular skin set
	 * Figures out all the inheritence and stuff for you. It's good like that
	 *
	 * @access	public
	 * @param	int			Skin set ID
	 * @return	array 		Array of css data: array[ 'replacement_key' => [ 'replacement_id' => ..., 'replacement_key' => ... , 'replacement_content' => ... 'replacement_set_id' => ..., 'replacement_added_to' => .... ]
	 * <code>
	 * Usage:
	 * $replacementData = $skinFunctions->fetchReplacements( 1 );
	 *
	 * foreach( $replacementData as $key => $data )
	 * {
	 *	print $key . ' contains: ' . $data['replacement_content'];
	 * }
	 * </code>
	 */
	public function fetchReplacements( $setID )
	{
		//-----------------------------------------
		// Try and get the skin from the cache
		//-----------------------------------------

		$skinSetData  = $this->fetchSkinData( $setID );
		$replacements = array();

		//-----------------------------------------
		// Push root ID onto the END of the parent array
		//-----------------------------------------

		array_push( $skinSetData['_parentTree'], 0 );

		//-----------------------------------------
		// Push the current skin set ID onto the beginnging
		//-----------------------------------------

		array_unshift( $skinSetData['_parentTree'], $setID );

		//-----------------------------------------
		// Geddit
		//-----------------------------------------

		$this->DB->build( array( 'select'	=> '*,' .
											       $this->DB->buildInstring( "," . implode( ",", $skinSetData['_parentTree'] ) . ",",
												   $this->DB->buildConcat( array( array( ',', 'string' ), array( 'replacement_set_id' ), array( ',', 'string' ) ) ) ) . ' as theorder',
										'from'	=> 'skin_replacements',
										'where'	=> "replacement_set_id IN (" . implode( ",", $skinSetData['_parentTree'] ) .")",
										'order'	=> 'replacement_key ASC, theorder DESC' ) );

		$i = $this->DB->execute();

		while( $row = $this->DB->fetch( $i ) )
		{
			$row['SAFE_replacement_content'] = IPSText::textToForm( $row['replacement_content'] );
			$replacements[ $row['replacement_key'] ] = $row;
			// Matt, the above is not consistent with the .inc file, so I'm changing it for now'
			// RIkki: There is loads more data needed, like set_id, original set ID, etc, so changing it back :p
			//$replacements[ $row['replacement_key'] ] = $row['replacement_content'];
		}

		return $replacements;
	}

	/**
	 * Save replacement after edit
	 *
	 * @access	public
	 * @param	int			Replacement ID
	 * @param	int			Replacement Set ID
	 * @param	string		Replacement content
	 * @param	string		Replacement key
	 * @return	array 		..of data
	 * <code>
	 * Exception Codes:
	 * NO_SUCH_REPLACEMENT:		Could not locate replacement
	 * REPLACEMENT_EXISTS: 		Could not rename replacement
	 * </code>
	 */
	public function saveReplacementFromEdit( $replacementID, $setID, $replacement_content, $replacement_key )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$replacementID       = intval( $replacementID );
		$replacement_key     = IPSText::alphanumericalClean( $replacement_key );
		$replacement_content = IPSText::formToText( $replacement_content );

		//-----------------------------------------
		// Fetch replacement data
		//-----------------------------------------

		$replacement = $this->DB->buildAndFetch( array( 'select' => '*',
													   		   'from'   => 'skin_replacements',
													   		   'where'  => 'replacement_id=' . $replacementID ) );

		if ( ! $replacement['replacement_id'] )
		{
			throw new Exception( 'NO_SUCH_REPLACEMENT' );
		}

		//-----------------------------------------
		// Group?
		//-----------------------------------------

		if ( $replacement['replacement_added_to'] == $setID )
		{
			if ( $replacement['replacement_key'] != $replacement_key )
			{
				$replacementTest = $this->DB->buildAndFetch( array( 'select' => '*',
															   			   'from'   => 'skin_replacements',
																		   'where'  => 'replacement_set_id=' . $setID . ' AND replacement_key=\'' . $replacement_key . '\'' ) );

				if ( $replacementTest['replacement_id'] )
				{
					throw new Exception( "REPLACEMENT_EXISTS" );
				}
			}
		}
		else
		{
			$replacement_group = $replacement['replacement_group'];
		}

		//-----------------------------------------
		// Get skin data
		//-----------------------------------------

		$skinSetData = $this->registry->output->allSkins[ $setID ];

		//-----------------------------------------
		// Same set?
		//-----------------------------------------

		if ( $setID == $replacement['replacement_set_id'] )
		{
			$this->DB->update( 'skin_replacements', array( 'replacement_content'  => $replacement_content,
												     		  'replacement_key'      => $replacement_key,
														     ), 'replacement_id=' . $replacementID );

			$replacement_id_new = $replacementID;
		}
		else
		{
			$this->DB->insert( 'skin_replacements', array( 'replacement_set_id'   => $setID,
												     		  'replacement_content'  => $replacement_content,
												     		  'replacement_key'      => $replacement_key,
												     		  'replacement_added_to' => $replacement['replacement_added_to'] ) );

			$replacement_id_new = $this->DB->getInsertId();
		}

		//-----------------------------------------
		// Recache
		//-----------------------------------------

		$this->rebuildReplacementsCache( $setID );

		//-----------------------------------------
		// Return
		//-----------------------------------------

		return $replacement_id_new;
	}

	/**
	 * Save replacement after add
	 *
	 * @access	public
	 * @param	int			Replacement Set ID
	 * @param	string		Replacement content
	 * @param	string		Replacement key
	 * @return	array 		..of data
	 * <code>
	 * Exception Codes:
	 * REPLACEMENT_EXISTS: 		Could not rename replacement
	 * </code>
	 */
	public function saveReplacementFromAdd( $setID, $replacement_content, $replacement_key )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$replacementID       = intval( $replacementID );
		$replacement_key     = IPSText::alphanumericalClean( $replacement_key );
		$replacement_content = IPSText::formToText( $replacement_content );

		//-----------------------------------------
		// Goddit?
		//-----------------------------------------

		$replacementTest = $this->DB->buildAndFetch( array( 'select' => '*',
															   	   'from'   => 'skin_replacements',
																   'where'  => 'replacement_set_id=' . $setID . ' AND replacement_key=\'' . $replacement_key . '\'' ) );

		if ( $replacementTest['replacement_id'] )
		{
			throw new Exception( "REPLACEMENT_EXISTS" );
		}

		//-----------------------------------------
		// Get skin data
		//-----------------------------------------

		$skinSetData = $this->registry->output->allSkins[ $setID ];

		//-----------------------------------------
		// Save set?
		//-----------------------------------------

		$this->DB->insert( 'skin_replacements', array( 'replacement_set_id'   => $setID,
											     		  'replacement_content'  => $replacement_content,
											     		  'replacement_key'      => $replacement_key,
											     		  'replacement_added_to' => $setID ) );

		$replacement_id_new = $this->DB->getInsertId();


		//-----------------------------------------
		// Recache
		//-----------------------------------------

		$this->rebuildReplacementsCache( $setID );

		//-----------------------------------------
		// Return
		//-----------------------------------------

		return $replacement_id_new;
	}

	/**
	 * Reverts / Removes Replacement
	 *
	 * @access	public
	 * @param	int			Replacement ID
	 * @param	int			Template Set ID
	 * @return	array 		All replacements for this skin set
	 * <code>
	 * Exception Codes:
	 * NO_SUCH_REPLACEMENT:			Could not locate replacement id#
	 * CANNOT_REMOVE: 				Cannot revert / remove the css
	 * </code>
	 */
	public function revertReplacement( $replacementID, $setID )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$replacementID = intval( $replacementID );

		//-----------------------------------------
		// Fetch replacement data
		//-----------------------------------------

		$replacement = $this->DB->buildAndFetch( array( 'select' => '*',
													   		   'from'   => 'skin_replacements',
													   		   'where'  => 'replacement_id=' . $replacementID ) );

		if ( ! $replacement['replacement_id'] )
		{
			throw new Exception( 'NO_SUCH_REPLACEMENT' );
		}

		//-----------------------------------------
		// Is this a master skin bit?
		//-----------------------------------------

		if ( $replacement['replacement_set_id'] == 0 AND $replacement['replacement_added_to'] == 0 )
		{
			throw new Exception( "CANNOT_REMOVE");
		}

		//-----------------------------------------
		// Remove it...
		//-----------------------------------------

		if ( $replacement['replacement_added_to'] == $setID  )
		{
			# remove it from ALL template sets
			$this->DB->delete( 'skin_replacements', "replacement_key='" . $replacement['replacement_key'] . "'" );
		}
		else
		{
			$this->DB->delete( 'skin_replacements', 'replacement_id=' . $replacementID );
		}

		//-----------------------------------------
		// Recache
		//-----------------------------------------

		$this->rebuildReplacementsCache( $setID );

		//-----------------------------------------
		// Grab the next template bit inline...
		//-----------------------------------------

		$replacements = $this->fetchReplacements( $setID );

		//-----------------------------------------
		// Reeee-turn
		//-----------------------------------------

		return $replacements;
	}

	/**
	 * Fetch CSS from the tree
	 * Very simple little function to return the CSS for a particular skin set
	 * Figures out all the inheritence and stuff for you. It's good like that
	 *
	 * @access	public
	 * @param	int			Skin set ID
	 * @param	boolean 	Parse data (TRUE is default). Adds elements into the array, such as '_cssSize', etc
	 * @param	boolean		Skip any 'removed' CSS files.
	 * @return	array 		Array of css data: array[ 'css_group' => [ 'css_id' => ..., 'css_group' => ... , 'css_content' => ... 'css_position' => ..., 'css_set_id' => .... ]
	 * <code>
	 * Usage:
	 * $cssData = $skinFunctions->fetchCSS( 1 );
	 *
	 * foreach( $cssData as $group_name => $data )
	 * {
	 *	print $data['css_group'] . ' contains: ' . $data['css_content'];
	 * }
	 * </code>
	 */
	public function fetchCSS( $setID, $parse=TRUE, $skipRemoved=TRUE )
	{
		//-----------------------------------------
		// Try and get the skin from the cache
		//-----------------------------------------

		$skinSetData 				= $this->fetchSkinData( $setID );
		$css						= array();
		$skip						= array();

		//-----------------------------------------
		// Push root ID onto the END of the parent array
		//-----------------------------------------

		array_push( $skinSetData['_parentTree'], 0 );

		//-----------------------------------------
		// Push the current skin set ID onto the beginnging
		//-----------------------------------------

		array_unshift( $skinSetData['_parentTree'], $setID );

		//-----------------------------------------
		// Geddit
		//-----------------------------------------

		$this->DB->build( array( 'select'	=> '*,' .
									       		$this->DB->buildInstring( "," . implode( ",", $skinSetData['_parentTree'] ) . ",",
										   		$this->DB->buildConcat( array( array( ',', 'string' ), array( 'css_set_id' ), array( ',', 'string' ) ) ) ) . ' as theorder',
								 'from'		=> 'skin_css',
								 'where'	=> "css_set_id IN (" . implode( ",", $skinSetData['_parentTree'] ) .")",
							 	 'order'	=> 'css_group, theorder DESC' ) );

		$i = $this->DB->execute();

		while( $row = $this->DB->fetch( $i ) )
		{
			/* skipping this group? */
			if ( in_array( $row['css_group'], $skip ) )
			{
				continue;
			}
			
			if ( $parse === TRUE )
			{
				$row['_cssSize'] = IPSLib::sizeFormat( IPSLib::strlenToBytes( strlen( $row['css_content'] ) ) );
			}
			
			/* CSS has been removed up the tree? */
			if ( $row['css_removed'] AND $skipRemoved === TRUE )
			{
				unset( $css[ $row['css_group'] ] );
				$skip[] = $row['css_group'];
				continue;
			}
			
			$css[ $row['css_group'] ] = $row;
		}

		return $css;
	}

	/**
	 * Fetch CSS for editing
	 *
	 * @access	public
	 * @param	int			CSS ID
	 * @param	int			Template Set ID
	 * @return	array 		..of data
	 * <code>
	 * Exception Codes:
	 * NO_SUCH_CSS:		Could not locate CSS id#
	 * </code>
	 */
	public function fetchCSSForEdit( $cssID, $setID )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$cssID = intval( $cssID );

		//-----------------------------------------
		// Fetch template data
		//-----------------------------------------

		$css = $this->DB->buildAndFetch( array( 'select' => '*',
												'from'   => 'skin_css',
												'where'  => 'css_id=' . $cssID ) );

		if ( ! $css['css_id'] )
		{
			throw new Exception( 'NO_SUCH_CSS' );
		}

		//-----------------------------------------
		// Get skin data
		//-----------------------------------------

		$skinSetData = $this->registry->output->allSkins[ $setID ];

		//-----------------------------------------
		// Update data
		//-----------------------------------------

		$css['_actualSetID']      = $css['css_set_id'];
		$css['_belongsSetID']     = $setID;
		//$css['_css_content']      = $css['css_content'];

		//-----------------------------------------
		// Update content for editing
		//-----------------------------------------

		$css['css_content'] = IPSText::textToForm( $css['css_content'] );

		//-----------------------------------------
		// Return it
		//-----------------------------------------

		return $css;
	}

	/**
	 * Save template bit after edit
	 *
	 * @access	public
	 * @param	int			CSS ID
	 * @param	int			CSS content
	 * @param	string		CSS group
	 * @param	string		CSS position
	 * @param	string		CSS attributes (media="Screen")
	 * @param	string		APP key to tie CSS to
	 * @param	int			If APP key, hide when APP key doesn't match viewing app
	 * @param	string		Modules to tie CSS to
	 * @return	int			New CSS id
	 * <code>
	 * Exception Codes:
	 * CSS_EXISTS:		CSS by that name already exists
	 * NO_SUCH_SET:		Set ID doesn't match anything in the DB
	 * </code>
	 */
	public function saveCSSFromAdd( $setID, $css_content, $css_group, $css_position=0, $css_attributes='', $css_app='', $css_app_hide=0, $css_modules='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$setID        = intval( $setID );
		$css_group    = str_replace( '.css', '', IPSText::alphanumericalClean( $css_group ) );
		$css_position = intval( $css_position );

		//-----------------------------------------
		// Get skin data
		//-----------------------------------------

		$skinSetData = $this->registry->output->allSkins[ $setID ];

		//-----------------------------------------
		// Got it?
		//-----------------------------------------

		if ( ! $skinSetData['set_id'] )
		{
			throw new Exception( 'NO_SUCH_SET' );
		}

		//-----------------------------------------
		// Make sure we don't have one already
		//-----------------------------------------

		$css = $this->DB->buildAndFetch( array( 'select' => '*',
												'from'   => 'skin_css',
												'where'  => 'css_set_id=' . $setID . ' AND css_group=\'' . $css_group . '\'' ) );

		if ( $css['css_id'] )
		{
			throw new Exception( 'CSS_EXISTS' );
		}

		//-----------------------------------------
		// Fix up content
		//-----------------------------------------

		$css_content = IPSText::formToText( $css_content );

		//-----------------------------------------
		// Save CSS?
		//-----------------------------------------

		$this->DB->insert( 'skin_css', array(    'css_set_id'     => $setID,
												 'css_content'    => $css_content,
											     'css_group'      => $css_group,
											     'css_position'   => $css_position,
											     'css_attributes' => $css_attributes,
												 'css_app'		  => $css_app,
												 'css_app_hide'	  => $css_app_hide,
												 'css_modules'	  => str_replace( ' ', '', $css_modules ),
											     'css_added_to'   => $setID,
											     'css_updated'    => time() ) );

		$css_id_new = $this->DB->getInsertId();

		//-----------------------------------------
		// Recache
		//-----------------------------------------

		$this->rebuildCSS( $setID );

		//-----------------------------------------
		// Return
		//-----------------------------------------

		return $css_id_new;
	}

	/**
	 * Save template bit after edit
	 *
	 * @access	public
	 * @param	int			CSS ID
	 * @param	int			CSS Set ID
	 * @param	string		CSS content
	 * @param	string		CSS name (css_group)
	 * @param	int			CSS position
	 * @param	string		CSS attributes (media="Screen")
	 * @param	string		APP key to tie CSS to
	 * @param	int			If APP key, hide when APP key doesn't match viewing app
	 * @param	string		Modules to tie CSS to
	 * @return	int 		New CSS id
	 * <code>
	 * Exception Codes:
	 * NO_SUCH_CSS:		Could not locate CSS
	 * CSS_EXISTS: 		Could not rename CSS file
	 * </code>
	 */
	public function saveCSSFromEdit( $cssID, $setID, $css_content, $css_group, $css_position=0, $css_attributes='', $css_app='', $css_app_hide=0, $css_modules='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$cssID        = intval( $cssID );
		$css_group    = str_replace( '.css', '', IPSText::alphanumericalClean( $css_group ) );
		$css_position = intval( $css_position );
		$_goClean     = 0;

		//-----------------------------------------
		// Fetch template data
		//-----------------------------------------

		$css = $this->DB->buildAndFetch( array( 'select' => '*',
												'from'   => 'skin_css',
												'where'  => 'css_id=' . $cssID ) );

		if ( ! $css['css_id'] )
		{
			throw new Exception( 'NO_SUCH_CSS' );
		}

		//-----------------------------------------
		// Group?
		//-----------------------------------------

		if ( $css['css_added_to'] == $setID )
		{
			if ( $css['css_group'] != $css_group )
			{
				$cssTest = $this->DB->buildAndFetch( array( 'select' => '*',
															'from'   => 'skin_css',
															'where'  => 'css_set_id=' . $setID . ' AND css_group=\'' . $css_group . '\'' ) );

				if ( $cssTest['css_id'] )
				{
					throw new Exception( "CSS_EXISTS" );
				}

				$_goClean = 1;
			}
		}
		else
		{
			$css_group = $css['css_group'];
		}

		//-----------------------------------------
		// Get skin data
		//-----------------------------------------

		$skinSetData = $this->registry->output->allSkins[ $setID ];

		//-----------------------------------------
		// Fix up content
		//-----------------------------------------

		$css_content = IPSText::formToText( $css_content );
		$css_attributes = IPSText::formToText( $css_attributes );

		//-----------------------------------------
		// Same CSS?
		//-----------------------------------------

		if ( $setID == $css['css_set_id'] )
		{
			$this->DB->update( 'skin_css', array( 'css_content'  	=> $css_content,
												  'css_group'    	=> $css_group,
												  'css_position' 	=> $css_position,
												  'css_attributes' 	=> $css_attributes,
												  'css_app'			=> $css_app,
												  'css_app_hide'	=> $css_app_hide,
												  'css_modules'	    => str_replace( ' ', '', $css_modules ),
												  'css_updated'  	=> time() ), 'css_id=' . $cssID );

			$css_id_new = $cssID;
		}
		else
		{
			$this->DB->insert( 'skin_css', array( 	'css_set_id'   	=> $setID,
												    'css_content'  	=> $css_content,
												    'css_group'    	=> $css_group,
												    'css_position' 	=> $css_position   ? $css_position   : $css['css_position'],
													'css_attributes'=> $css_attributes ? $css_attributes : $css['css_attributes'],
													'css_app'		=> $css_app        ? $css_app        : $css['css_app'],
													'css_app_hide'	=> $css_app_hide   ? $css_app_hide   : $css['css_app_hide'],
													'css_modules'   => str_replace( ' ', '', $css_modules ),
												    'css_added_to' 	=> $css['css_added_to'],
												    'css_updated'  	=> time() ) );

			$css_id_new = $this->DB->getInsertId();
		}

		//-----------------------------------------
		// Recache
		//-----------------------------------------

		$this->rebuildCSS( $setID );

		//-----------------------------------------
		// Remove dead-uns?
		//-----------------------------------------

		if ( $_goClean == 1 )
		{
			$this->removeDeadCSSCaches( $setID );
		}

		//-----------------------------------------
		// Return
		//-----------------------------------------

		return $css_id_new;
	}

	/**
	 * Reverts / Removes CSS
	 *
	 * @access	public
	 * @param	int			CSS ID
	 * @param	int			Template Set ID
	 * @param	int			From a delete request
	 * @return	array 		Data from "next in line" CSS (parent, or root)
	 * <code>
	 * Exception Codes:
	 * NO_SUCH_CSS:			Could not locate css id#
	 * CANNOT_REMOVE: 		Cannot revert / remove the css
	 * </code>
	 */
	public function revertCSS( $cssID, $setID, $fromDelete=0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$cssID   = intval( $cssID );
		$setData = $this->fetchSkinData( $setID );

		//-----------------------------------------
		// Fetch CSS data
		//-----------------------------------------

		$css = $this->DB->buildAndFetch( array( 'select' => '*',
												'from'   => 'skin_css',
												'where'  => 'css_id=' . $cssID ) );

		if ( ! $css['css_id'] )
		{
			throw new Exception( 'NO_SUCH_CSS' );
		}

		/* Inherited from master */
		if ( $css['css_added_to'] == 0 )
		{
			/* Did we want to delete? */
			if ( $fromDelete )
			{
				/* Inherited from master css */
				if ( $css['css_set_id'] > 0 )
				{
					/* Clear any existing CSS if it's been modified from set 0 */
					$this->DB->delete( 'skin_css', 'css_id=' . $cssID );
				}
			
				/* Add one as 'removed' */
				$this->DB->insert( 'skin_css', array( 'css_set_id'   => $setID,
													  'css_updated'  => time(),
													  'css_group'    => $css['css_group'],
													  'css_added_to' => $setID,
													  'css_removed'  => 1 ) );
			}
			else
			{
				/* We're reverting - just make sure it's not a master CSS file */
				if ( $css['css_set_id'] > 0 )
				{
					$this->DB->delete( 'skin_css', 'css_id=' . $cssID );
				}
			}
		}
		/* Modified or added to this skin set or a parent
		   Remove and revert do the same thing here... */
		else
		{
			/* Fix up child array */
			array_unshift( $setData['_childTree'], $setID );
			
			/* This is CSS unique to this skin set, so remove it from this set and all children */
			$this->DB->delete( 'skin_css', "css_group='" . $css['css_group'] . "' AND css_set_id IN(" . implode( ',', $setData['_childTree'] ) . ")" );

			# Remove dead caches
			$this->removeDeadCSSCaches( $setID );
		}

		//-----------------------------------------
		// Recache
		//-----------------------------------------

		$this->rebuildCSS( $setID );
		
		//-----------------------------------------
		// Grab the next template bit inline...
		//-----------------------------------------

		$cssData = $this->fetchCSS( $setID, TRUE );
			
		//-----------------------------------------
		// Reeee-turn
		//-----------------------------------------

		return $cssData;
	}

	/**
	 * Fetch Templates from the tree
	 *
	 * @access	public
	 * @param	int			Skin set ID
	 * @param	string		Type of data to return: 'allTemplates' will return the data [template_group][template_name], 'allNoContent' the same as 'allTemplates' minus the actual template content, 'groupNames'; [template_group] or groupTemplates, just that groups templates [template_name], groupTemplatesNoContent is the same as groupTemplates but template_content is removed
	 * @param	string		Which group to use
	 * @return	array 		Array of data depending on the params
	 * <code>
	 * Usage:
	 * # To return all skin 'groups' (eg, skin_global, skin_topics, etc)
	 * $groups = $skinFunctions->fetchTemplates( 1, 'groupNames' );
	 * # To return all templates within group 'skin_global'
	 * $templates = $skinFunctions->fetchTemplates( 1, 'groupTemplates', 'skin_global' );
	 * # To return all templates in all groups
	 * $templates = $skinFunctions->fetchTemplates( 1, 'allTemplates');
	 * </code>
	 */
	public function fetchTemplates( $setID, $type='allTemplates', $group='')
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$templates     = array();
		
		//-----------------------------------------
		// Try and get the skin from the cache
		//-----------------------------------------

		$skinSetData = $this->fetchSkinData( $setID );

		//-----------------------------------------
		// Push root ID onto the END of the parent array
		//-----------------------------------------

		array_push( $skinSetData['_parentTree'], 0 );

		//-----------------------------------------
		// Push the current skin set ID onto the beginnging
		//-----------------------------------------

		array_unshift( $skinSetData['_parentTree'], $setID );

		//-----------------------------------------
		// Ok, what to return?
		//-----------------------------------------

		if ( $type == 'groupNames' )
		{
			# Just return group titles
			$this->DB->build( array( 'select'	=> 'template_group, template_set_id, template_id, template_name, template_data,' .
														$this->DB->buildInstring( "," . implode( ",", $skinSetData['_parentTree'] ) . ",",
														$this->DB->buildConcat( array( array( ',', 'string' ), array( 'template_set_id' ), array( ',', 'string' ) ) ) ) . ' as theorder',
										   'from'	=> 'skin_templates',
										   'where'	=> "template_set_id IN (" . implode( ",", $skinSetData['_parentTree'] ) ." )",
										   'order'	=> 'template_group, theorder DESC'
								)		);
			$newq = $this->DB->execute();
		}
		else if ( ( $type == 'groupTemplates' OR $type == 'groupTemplatesNoContent' ) and $group != '' )
		{
			# Return group template bits
			$this->DB->build( array( 'select'	=> '*,' .
												       $this->DB->buildInstring( "," . implode( ",", $skinSetData['_parentTree'] ) . ",",
													   $this->DB->buildConcat( array( array( ',', 'string' ), array( 'template_set_id' ), array( ',', 'string' ) ) ) ) . ' as theorder',
											'from'	=> 'skin_templates',
											'where'	=> "template_set_id IN (" . implode( ",", $skinSetData['_parentTree'] ) . ") AND template_group='{$group}'",
											'order'	=> 'template_name, theorder DESC'
								)		);
			$newq = $this->DB->execute();
		}
		else
		{
			# Return all...
			$this->DB->build( array( 'select'	=> '*,' .
												       $this->DB->buildInstring( "," . implode( ",", $skinSetData['_parentTree'] ) . ",",
													   $this->DB->buildConcat( array( array( ',', 'string' ), array( 'template_set_id' ), array( ',', 'string' ) ) ) ) . ' as theorder',
											'from'	=> 'skin_templates',
											'where'	=> "template_set_id IN (" . implode( ",", $skinSetData['_parentTree'] ) .")",
											'order'	=> 'template_group, template_name, theorder DESC'
								)		);
			$newq = $this->DB->execute();
		}

		//-----------------------------------------
		// Get all results
		//-----------------------------------------

		while ( $r = $this->DB->fetch($newq) )
		{
			if ( isset( $r['template_name'] ) )
			{
				if ( substr( $r['template_name'], 0, 2 ) == '__' )
				{
					continue;
				}
			}

			//-----------------------------------------
			// Build counts
			//-----------------------------------------

			$this->_templateCount[ $r['template_set_id'] ] = isset($this->_templateCount[ $r['template_set_id'] ]) ? $this->_templateCount[ $r['template_set_id'] ] : array();
			$this->_templateCount[ $r['template_set_id'] ][ $r['template_group'] ] = isset($this->_templateCount[ $r['template_set_id'] ][ $r['template_group'] ]) ? $this->_templateCount[ $r['template_set_id'] ][ $r['template_group'] ] : array( 'count' => 0 );
			$this->_templateCount[ $r['template_set_id'] ][ $r['template_group'] ]['count']++;

			if ( $type == 'groupNames' )
			{
				$templates[ $r['template_group'] ] = $r;
			}
			else if ( $type == 'groupTemplates' OR $type == 'groupTemplatesNoContent' )
			{
				$r['_templateSize'] = IPSLib::sizeFormat( IPSLib::strlenToBytes( strlen( $r['template_content'] ) ) );

				if ( $type == 'groupTemplatesNoContent' )
				{
					unset( $r['template_data'] );
					unset( $r['template_content'] );
				}
				
				$templates[ strtolower($r['template_name']) ] = $r;
			}
			else
			{
				if ( $type == 'allNoContent' )
				{
					unset( $r['template_content'] );
				}
				
				$templates[ $r['template_group'] ][ strtolower($r['template_name']) ] = $r;
			}
		}

		ksort($templates);

		return $templates;
	}

	/**
	 * Fetch template bit for editing
	 *
	 * @access	public
	 * @param	int			Template ID
	 * @param	int			Template Set ID
	 * @return	array 		..of data
	 * <code>
	 * Exception Codes:
	 * NO_SUCH_TEMPLATE:		Could not locate template id#
	 * </code>
	 */
	public function fetchTemplateBitForEdit( $templateID, $setID )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$templateID = intval( $templateID );

		//-----------------------------------------
		// Fetch template data
		//-----------------------------------------

		$template = $this->DB->buildAndFetch( array( 'select' => '*',
															'from'   => 'skin_templates',
															'where'  => 'template_id=' . $templateID ) );

		if ( ! $template['template_id'] )
		{
			throw new Exception( 'NO_SUCH_TEMPLATE' );
		}

		//-----------------------------------------
		// Get skin data
		//-----------------------------------------

		$skinSetData = $this->registry->output->allSkins[ $setID ];

		//-----------------------------------------
		// Update data
		//-----------------------------------------

		$template['_actualSetID']      = $template['template_set'];
		$template['_belongsSetID']     = $setID;
		$template['_template_content'] = $template['template_content'];
		$template['_template_data']    = $template['template_data'];

		//-----------------------------------------
		// Update content for editing
		//-----------------------------------------

		$template['template_content'] = IPSText::textToForm( $template['template_content'] );

		//-----------------------------------------
		// Update function data for editing
		//-----------------------------------------

		$template['template_data'] = IPSText::textToForm( $template['template_data'] );

		//-----------------------------------------
		// Return it
		//-----------------------------------------

		return $template;
	}

	/**
	 * Reverts a template bit
	 *
	 * @access	public
	 * @param	int			Template ID
	 * @param	int			Template Set ID
	 * @return	array 		Data from "next in line" template bit (parent, or root)
	 * <code>
	 * Exception Codes:
	 * NO_SUCH_TEMPLATE:	Could not locate template id#
	 * CANNOT_REMOVE: 		Cannot revert / remove the template bit
	 * </code>
	 */
	public function revertTemplateBit( $templateID, $setID )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$templateID = intval( $templateID );
		$_goClean   = 0;

		//-----------------------------------------
		// Fetch template data
		//-----------------------------------------

		$template = $this->DB->buildAndFetch( array( 'select' => '*',
													 'from'   => 'skin_templates',
													 'where'  => 'template_id=' . $templateID ) );

		if ( ! $template['template_id'] )
		{
			throw new Exception( 'NO_SUCH_TEMPLATE' );
		}

		//-----------------------------------------
		// Is this a master skin bit?
		//-----------------------------------------

		if ( $template['template_set_id'] == 0 AND $template['template_added_to'] == 0 )
		{
			throw new Exception( "CANNOT_REMOVE");
		}

		//-----------------------------------------
		// Remove it...
		//-----------------------------------------

		if ( $template['template_added_to'] == $setID  )
		{
			/* Does it exist in set ID 0? */
			$test = $this->DB->buildAndFetch( array( 'select' => 'template_id',
											 		 'from'   => 'skin_templates',
											 		 'where'  => "template_set_id=0 AND template_user_added=0 AND template_name='" . $template['template_name'] . "' AND template_group='" . $template['template_group'] . "'" ) );
			
			/* Is from master skin? */
			if ( $test['template_id'] )
			{
				$this->DB->delete( 'skin_templates', 'template_id=' . $templateID );
			}
			else
			{
				# Remove it from ALL template sets
				$this->DB->delete( 'skin_templates', "template_name='" . $template['template_name'] . "' AND template_group='" . $template['template_group'] . "'" );
			}
			
			$_goClean = 1;
		}
		else
		{
			$this->DB->delete( 'skin_templates', 'template_id=' . $templateID );
		}

		//-----------------------------------------
		// Grab the next template bit inline...
		//-----------------------------------------

		$templates = $this->fetchTemplates( $setID, 'groupTemplates', $template['template_group'] );

		//-----------------------------------------
		// Recache
		//-----------------------------------------

		$this->rebuildPHPTemplates( $setID, $template['template_group'] );

		//-----------------------------------------
		// Clean up?
		//-----------------------------------------

		if ( $_goClean )
		{
			$this->removeDeadPHPCaches( $setID );
		}

		//-----------------------------------------
		// Reeee-turn
		//-----------------------------------------
		
		if ( $templates[ strtolower( $template['template_name'] ) ]['template_id'] )
		{
			return $this->fetchTemplateBitForEdit( $templates[ strtolower( $template['template_name'] ) ]['template_id'], $setID );
		}
		else
		{
			return array();
		}
	}

	/**
	 * Save template bit from 'add' form
	 *
	 * @access	public
	 * @param	int			Template Set ID
	 * @param	string		Template content
	 * @param	string		Template function data
	 * @param	string		Template group
	 * @param	string		Template name
	 * @return	array 		..of data
	 * <code>
	 * Exception Codes:
	 * TEMPLATE_EXISTS:		Template bit name already exists
	 * SYNTAX_INCORRECT:	The php syntax is invalid
	 * </code>
	 */
	public function saveTemplateBitFromAdd( $setID, $template_content, $template_data, $template_group, $template_name )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$template_group = IPSText::alphanumericalClean( $template_group );
		$template_name  = IPSText::alphanumericalClean( $template_name );

		//-----------------------------------------
		// Get skin data
		//-----------------------------------------

		$skinSetData = $this->registry->output->allSkins[ $setID ];

		//-----------------------------------------
		// Does this template bit already exist?
		//-----------------------------------------

		$template = $this->DB->buildAndFetch( array( 
													'select' => '*',
													'from'   => 'skin_templates',
													'where'  => "template_name='" . $template_name . "' AND template_group='" . $template_group . "' AND template_set_id=0" ) );

		if ( $template['template_id'] )
		{
			throw new Exception( 'TEMPLATE_EXISTS' );
		}

		//-----------------------------------------
		// Fix up content
		//-----------------------------------------

		$template_content = IPSText::formToText( $template_content );

		//-----------------------------------------
		// Fix up data
		//-----------------------------------------

		$template_data = IPSText::formToText( $template_data );

		//-----------------------------------------
		// Test it..
		//-----------------------------------------

		if ( $this->testTemplateBitSyntax( $template['template_name'], $template_data, $template_content ) !== TRUE )
		{
			throw new Exception( "SYNTAX_INCORRECT" );
		}

		//-----------------------------------------
		// Same template set?
		//-----------------------------------------

		$this->DB->insert( 'skin_templates', array( 'template_set_id'      => 0,
													'template_group'       => $template_group,
													'template_content'     => $template_content,
													'template_name'        => $template_name,
													'template_data'        => $template_data,
													'template_added_to'    => $setID,
													'template_user_edited' => 1,
													'template_user_added'  => 1,
													'template_removable'   => 1,
													'template_updated'     => time() ) );

		$template_id_new = $this->DB->getInsertId();

		//-----------------------------------------
		// Recache
		//-----------------------------------------

		$this->rebuildPHPTemplates( $setID, $template_group );

		//-----------------------------------------
		// Return
		//-----------------------------------------

		return $template_id_new;
	}

	/**
	 * Save template bit after edit
	 *
	 * @access	public
	 * @param	int			Template ID
	 * @param	int			Template Set ID
	 * @param	string		Template content
	 * @param	string		Template function data
	 * @return	array 		..of data
	 * <code>
	 * Exception Codes:
	 * NO_SUCH_TEMPLATE:		Could not locate template id#
	 * SYNTAX_INCORRECT:		The template bit syntax is incorrect and would cause a parse error if saved
	 * </code>
	 */
	public function saveTemplateBitFromEdit( $templateID, $setID, $template_content, $template_data )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$templateID = intval( $templateID );

		//-----------------------------------------
		// Fetch template data
		//-----------------------------------------

		$template = $this->DB->buildAndFetch( array( 'select' => '*',
													 'from'   => 'skin_templates',
													 'where'  => 'template_id=' . $templateID ) );

		if ( ! $template['template_id'] )
		{
			throw new Exception( 'NO_SUCH_TEMPLATE' );
		}

		//-----------------------------------------
		// Get skin data
		//-----------------------------------------

		$skinSetData = $this->registry->output->allSkins[ $setID ];

		//-----------------------------------------
		// Fix up content
		//-----------------------------------------

		$template_content = IPSText::formToText( $template_content );

		//-----------------------------------------
		// Fix up data
		//-----------------------------------------

		$template_data = IPSText::formToText( $template_data );

		//-----------------------------------------
		// Test it..
		//-----------------------------------------

		if ( $this->testTemplateBitSyntax( $template['template_name'], $template_data, $template_content ) !== TRUE )
		{
			throw new Exception( "SYNTAX_INCORRECT" );
		}

		//-----------------------------------------
		// Same template set?
		//-----------------------------------------

		if ( $setID == $template['template_set_id'] )
		{
			$this->DB->update( 'skin_templates', array( 'template_data'        => $template_data,
														'template_content'     => $template_content,
														'template_user_edited' => 1,
														'template_updated'     => time() ), 'template_id=' . $templateID );

			$template_id_new = $templateID;
		}
		else
		{
			$this->DB->insert( 'skin_templates', array( 'template_set_id'      => $setID,
													    'template_group'       => $template['template_group'],
													    'template_content'     => $template_content,
													    'template_name'        => $template['template_name'],
													    'template_data'        => $template_data,
													    'template_added_to'    => $template['template_added_to'],
														'template_user_added'  => $template['template_user_added'],
													    'template_user_edited' => 1,
													    'template_removable'   => 1,
													    'template_updated'     => time() ) );

			$template_id_new = $this->DB->getInsertId();

			# Update count
			$this->_templateCount[ $setID ][ $template['template_group'] ]['count']++;
		}

		//-----------------------------------------
		// Recache
		//-----------------------------------------

		$this->rebuildPHPTemplates( $setID, $template['template_group'] );

		//-----------------------------------------
		// Return
		//-----------------------------------------

		return $template_id_new;
	}

	/**
	 * Fetch image directories from the file system
	 *
	 * @access	public
	 * @return	array 	simple array of filenames
	 */
	public function fetchImageDirectories()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$dirs = array();
		
		try
		{
			foreach( new DirectoryIterator( DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/style_images' ) as $file )
			{
				if ( ! $file->isDot() AND $file->isDir() )
				{
					$_name = $file->getFileName();
        	
					/* Annoyingly, isDot doesn't match .svn, etc */
					if ( substr( $_name, 0, 1 ) == '.' )
					{
						continue;
					}
        	
					$dirs[] = $_name;
				}
			}
		} catch ( Exception $e ) {}

		return $dirs;
	}

	/**
	 * Fetch emoticon directories from the file system
	 *
	 * @access	public
	 * @return	array 	simple array of filenames
	 */
	public function fetchEmoticonDirectories()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$dirs = array();
		
		try
		{
			foreach( new DirectoryIterator( DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/style_emoticons' ) as $file )
			{
				if ( ! $file->isDot() AND $file->isDir() )
				{
					$_name = $file->getFileName();
        	
					/* Annoyingly, isDot doesn't match .svn, etc */
					if ( substr( $_name, 0, 1 ) == '.' )
					{
						continue;
					}
        	
					$dirs[] = $_name;
				}
			}
		} catch ( Exception $e ) {}

		return $dirs;
	}

	/**
	 * Returns the Image directory
	 *
	 * @access	public
	 * @param	string	Dir name
	 * @return	mixed	False, or directory path
	 */
	public function fetchImageDirectoryPath( $dir )
	{
		if ( $this->checkImageDirectoryExists( $dir ) === TRUE )
		{
			return DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/style_images/' . $dir;
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * Check Image directory exists
	 *
	 * @access	public
	 * @param	string	Dir name
	 * @return	boolean
	 */
	public function checkImageDirectoryExists( $dir )
	{
		if ( ! is_dir( DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/style_images/' . $dir ) )
		{
			return FALSE;
		}
		else
		{
			return TRUE;
		}
	}

	/**
	 * Creates a new image directory
	 *
	 * @access	public
	 * @param	string	Dir name
	 * @return	boolean
	 */
	public function createNewImageDirectory( $dir )
	{
		if ( @mkdir( DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/style_images/' . $dir ) )
		{
			@chmod( DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/style_images/' . $dir, 0777 );
			return TRUE;
		}
	}

	/**
	 * Check Emoticon directory exists
	 *
	 * @access	public
	 * @param	string	Dir name
	 * @return	boolean
	 */
	public function checkEmoticonDirectoryExists( $dir )
	{
		if ( ! is_dir( DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/style_emoticons/' . $dir ) )
		{
			return FALSE;
		}
		else
		{
			return TRUE;
		}
	}

	/**
	 * Fetch output formats
	 *
	 * @access	public
	 * @return	array 		array( 'key' => array( $confdata.... ) )
	 */
	public function fetchOutputFormats()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$formats = array();
		$path    = IPS_ROOT_PATH . 'sources/classes/output/formats';
		
		try
		{
			foreach( new DirectoryIterator( $path ) as $file )
			{
				if ( ! $file->isDot() AND $file->isDir() )
				{
					$_name = $file->getFileName();
        	
					/* Annoyingly, isDot doesn't match .svn, etc */
					if ( substr( $_name, 0, 1 ) == '.' )
					{
						continue;
					}
        	
					require( $path . '/' . $_name . '/conf.php' );
        	
					$formats[ $_name ] = $config;
				}
			}
		} catch ( Exception $e ) {}

		return $formats;
	}

	/**
	 * Fetch all template hooks
	 *
	 * @access	public
	 * @param	array 		[Array of skin groups to search in. Leave blank to search in all]
	 * @return	array 		Array of skin groups and their hooks
	 */
	public function fetchSkinHooks( $groups=array() )
	{
		/* INIT */
		$myHooks = array();
		
		/* Fetch groups? */
		if ( ! count( $groups ) )
		{
			$groups = array_keys( $this->fetchTemplates( 1, 'groupNames' ) );
		}
		
		/* Got a hooks cache? */
		if ( ! $this->cache->exists('hooks') )
		{
			$this->cache->rebuildCache('hooks', 'global' );
		}
		
		/* Loop through the cache */
    	$hooksCache = $this->cache->getCache( 'hooks' );
		
		if( is_array( $hooksCache['templateHooks'] ) AND count( $hooksCache['templateHooks'] ) )
		{
			foreach( $hooksCache['templateHooks'] as $hook )
			{
				foreach( $hook as $tplHook )
				{
					/* Check to see if the group is loaded */
					if( ! in_array( $tplHook['skinGroup'], $groups ) )
					{
						continue;
					}

					/* Check for hook file */
					if( file_exists( DOC_IPS_ROOT_PATH . 'hooks/' . $tplHook['filename'] ) )
					{
						$tplHook['_commentTag'] = $tplHook['type'] . '.' . $tplHook['skinGroup'] . '.' . $tplHook['skinFunction'] . '.' . $tplHook['id'] . '.' . $tplHook['position'];
						$myHooks[ $tplHook['skinGroup'] ][] = $tplHook;
					}
				}
			}
		}
		
		return $myHooks;
	}
	
	/**
	 * Flushes and reloads skin set data
	 *
	 * @access	public
	 * @return  void
	 */
	public function flushSkinData()
	{
		try
		{
			$skinSetData = $this->registry->output->reloadSkinData();
		}
		catch( Exception $error )
		{
			# We're here 'cos this was called during setup and
			# registry->getClass('output') isn't set up yet, so we
			# just carry on and let the DB get it
			
		}
	}
	
	/**
	 * Fetch skin set data
	 *
	 * @access	public
	 * @param	int		Skin set ID
	 * @return	array
	 */
	public function fetchSkinData( $setID )
	{
		//-----------------------------------------
		// Skin ID #0?
		//-----------------------------------------

		if ( $setID == 0 )
		{
			/* This is a master skin, so lets fake it */
			$skinSetData['set_id']			= 0;
			$skinSetData['set_key']			= 'root';
			$skinSetData['_isMaster']		= 1;
			$skinSetData['_parentTree']     = array();
			$skinSetData['_childTree']      = array();
			$skinSetData['_userAgents']     = array();
			$skinSetData['_cssGroupsArray'] = array();

			return $skinSetData;
		}

		//-----------------------------------------
		// Try and get the skin from the cache
		//-----------------------------------------

		try
		{
			$skinSetData = $this->registry->output->allSkins[ $setID ];
		}
		catch( Exception $error )
		{
			# We're here 'cos this was called during setup and
			# registry->getClass('output') isn't set up yet, so we
			# just carry on and let the DB get it
		}

		$skinSetData['_parentTree']     = $skinSetData['_parentTree'] ? $skinSetData['_parentTree'] : array();
		$skinSetData['_childTree']      = $skinSetData['_childTree'] ? $skinSetData['_childTree'] : array();
		$skinSetData['_userAgents']     = $skinSetData['_userAgents'] ? $skinSetData['_userAgents'] : array();
		$skinSetData['_cssGroupsArray'] = $skinSetData['_cssGroupsArray'] ? $skinSetData['_cssGroupsArray'] : array();
		
		if ( is_array( $this->remapData ) AND count( $this->remapData ) )
		{
			$skinSetData['_isMaster'] = ( $this->remapData['templates'][ $setID ] ) ? 1 : 0;
		}
		
		//-----------------------------------------
		// Got nothing?
		//-----------------------------------------

		if ( $setID > 0 AND ! is_array( $skinSetData ) )
		{
			$skinSetData = $this->DB->buildAndFetch( array( 'select' => '*',
															'from'   => 'skin_collections',
															'where'  => 'set_id=' . $setID ) );

			$skinSetData['_parentTree']     = unserialize( $skinSetData['set_parent_array'] );
			$skinSetData['_childTree']      = unserialize( $skinSetData['set_child_array'] );
			$skinSetData['_userAgents']     = unserialize( $skinSetData['set_locked_uagent'] );
			$skinSetData['_cssGroupsArray'] = unserialize( $skinSetData['set_css_groups'] );
			
			if ( is_array( $this->remapData ) AND count( $this->remapData ) )
			{
				$skinSetData['_isMaster'] = ( $this->remapData['templates'][ $setID ] ) ? 1 : 0;
			}
			
			if ( is_array( $skinSetData['_cssGroupsArray'] ) )
			{
				ksort( $skinSetData['_cssGroupsArray'], SORT_NUMERIC );
			}
			else
			{
				$skinSetData['_cssGroupsArray'] = array();
			}
		}

		return $skinSetData;
	}
	
	/**
	 * Removes customizations in a skin set
	 *
	 * @access	public
	 * @param	int			Skin set ID
	 * @param	array 		Items to revert ( 'templates' => TRUE/FALSE, 'css' => TRUE/FALSE, 'replacements' => TRUE/FALSE )
	 * @return	boolean
	 */
	public function removeCustomizations( $setId, $which=array() )
	{
		/* INIT */
		$setId  = intval( $setId );
		
		/* Templates */
		if ( isset( $which['templates'] ) AND $which['templates'] )
		{
			$this->DB->delete( 'skin_templates', 'template_set_id=' . $setId );
		}
		
		/* CSS */
		if ( isset( $which['css'] ) AND $which['css'] )
		{
			$this->DB->delete( 'skin_css', 'css_set_id=' . $setId );
		}
		
		/* Replacements */
		if ( isset( $which['replacements'] ) AND $which['replacements'] )
		{
			$this->DB->delete( 'skin_replacements', 'replacement_set_id=' . $setId );
		}
		
		/* Recache */
		if ( method_exists( $this, 'rebuildReplacementsCache' ) )
		{
			$this->rebuildReplacementsCache( $setId );
			$this->rebuildCSS( $setId );
			$this->rebuildPHPTemplates( $setId );
		}
		
		return TRUE;
	}
	
	
	/**
	 * Fetch number of customizations in a skin set
	 *
	 * @access	public
	 * @param	int			Skin set ID
	 * @return	array 		( 'templates' => x, 'css' => x, 'replacements' => x )
	 */
	public function fetchCustomizationCount( $setId )
	{
		/* INIT */
		$return = array( 'templates' => 0, 'css' => 0, 'replacements' => 0 );
		$setId  = intval( $setId );
		
		/* Templates.. */
		$c = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count',
											  'from'   => 'skin_templates',
											  'where'  => 'template_set_id=' . $setId ) );
											
		$return['templates'] = intval( $c['count'] );
		
		/* CSS */
		$c = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count',
											  'from'   => 'skin_css',
											  'where'  => 'css_set_id=' . $setId ) );
											
		$return['css'] = intval( $c['count'] );
		
		/* Replacements */
		$c = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count',
											  'from'   => 'skin_replacements',
											  'where'  => 'replacement_set_id=' . $setId ) );
											
		$return['replacements'] = intval( $c['count'] );
		
		return $return;
	}
	
	/**
	 * Test template bit syntax
	 *
	 * @access	public
	 * @param	string		Template name
	 * @param	string		Template function data
	 * @param	string		Template content
	 * @return	boolean		[ TRUE == OK ]
	 */
	public function testTemplateBitSyntax( $name, $data, $content )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$return = '';

		$this->_resetMessageHandle();

		//-----------------------------------------
		// Test...
		//-----------------------------------------

		ob_start();
		eval( $this->registry->templateEngine->convertHtmlToPhp( 'test__' . $name, $data, $content ) );
		$return = ob_get_contents();
		ob_end_clean();
	
		//-----------------------------------------
		// More data...
		//-----------------------------------------

		$this->_addMessage( $return );

		//-----------------------------------------
		// Return
		//-----------------------------------------

		return ($return) ? FALSE : TRUE;
	}
	
	/**
	 * Minify text
	 *
	 * @access	public
	 * @param	string		Text to minify
	 * @param	string		Type to minify (css/html). Default is css
	 * @return	string		Minified text
	 */
	
	public function minify( $text, $type='css' )
	{
		$type = strtolower( $type );
		
		/* What to do? */
		if ( $type == 'css' )
		{
			require_once( IPS_PUBLIC_PATH . 'min/lib/Minify/CSS/Compressor.php' );
			
			$text = Minify_CSS_Compressor::process( $text );
		}
		else if ( $type == 'html' )
		{
			require_once( IPS_PUBLIC_PATH . 'min/lib/Minify/HTML.php' );
			
			$text = Minify_HTML::minify( $text, array( 'xhtml' => 1 ) );
		}
		
		return $text;
	}
	
	/**
	 * Clean out templates DB
	 * Goes through and removes any 'dead' templates
	 *
	 * @access	public
	 * @return	array		( 'cached' => x, 'templates' => x ) Number of template bits removed
	 */
	public function cleanDbCss()
	{
		/* INIT */
		$sets     = array_keys( $this->registry->output->allSkins );
		$affected = 0;
		$cached   = 0;
		
		/* Remove from cache: sets */
		$this->DB->delete( 'skin_cache', 'cache_type=\'css\' AND cache_set_id NOT IN ( 0,' . implode( ',', $sets ) . ')' );
		
		$cached += intval( $this->DB->getAffectedRows() );
		
		/* Clean out any template bits no longer assigned to a valid skin set */
		$this->DB->delete( 'skin_css', 'css_set_id NOT IN ( 0,' . implode( ',', $sets ) . ')' );
		
		$affected += intval( $this->DB->getAffectedRows() );
		
		return array( 'cached' => intval( $cached ), 'templates' => intval( $affected ) );
	}	
	
	
	/**
	 * Clean out templates DB
	 * Goes through and removes any 'dead' templates
	 *
	 * @access	public
	 * @return	array		( 'cached' => x, 'templates' => x ) Number of template bits removed
	 */
	public function cleanDbTemplates()
	{
		/* INIT */
		$sets     = array_keys( $this->registry->output->allSkins );
		$groups   = $this->fetchTemplates( 0, 'groupNames' );;
		$affected = 0;
		$cached   = 0;
		
		/* Remove from cache: sets */
		$this->DB->delete( 'skin_cache', 'cache_type=\'phptemplate\' AND cache_set_id NOT IN ( 0,' . implode( ',', $sets ) . ')' );
		
		$cached += intval( $this->DB->getAffectedRows() );
		
		/* Remove from cache: non-existant groups */
		$this->DB->delete( 'skin_cache', "cache_type='phptemplate' AND cache_value_1 NOT IN ('" . implode( "','", array_keys( $groups ) ) . "')" );
		
		$cached += intval( $this->DB->getAffectedRows() );
		
		/* Clean out any template bits no longer assigned to a valid skin set */
		$this->DB->delete( 'skin_templates', 'template_set_id NOT IN ( 0,' . implode( ',', $sets ) . ')' );
		
		$affected += intval( $this->DB->getAffectedRows() );
		
		/* Loop through groups */
		foreach( $groups as $_group => $_data )
		{
			$templates = $this->fetchTemplates( 0, 'groupTemplatesNoContent', $_group );
			
			/* Clean out any template bits no longer part of this group */
			$this->DB->delete( 'skin_templates', "template_group='" . $_group . "' AND LOWER(template_name) NOT IN ('" . implode( "','", array_keys( $templates ) ) . "')" );

			$affected += intval( $this->DB->getAffectedRows() );
		}
		
		return array( 'cached' => intval( $cached ), 'templates' => intval( $affected ) );
	}

}