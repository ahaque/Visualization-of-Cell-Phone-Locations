<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Recurse Tier Management
 * Owner: Matt Mecham
 * Last Updated: $Date: 2009-02-04 15:03:36 -0500 (Wed, 04 Feb 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @since		9th March 2005 11:03
 * @version		$Revision: 3887 $
 */

class recursiveTiers
{
	/**
	 * Registry object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $registry;
	
	/**
	 * Database object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $DB;
	
	/**
	 * Settings object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $settings;
	
	/**
	 * Request object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $request;
	
	/**
	 * Language object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $lang;
	
	/**
	 * Member object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $member;
	
	/**
	 * Cache object
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $cache;
	
	/**
	 * Item Cache
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $_itemCache = array();
	
	/**
	 * Item By ID
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $_itemByID = array();
	
	/**
	 * FIELD: item ID
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_sqlPrimaryID  = '';
	
	/**
	 * FIELD: parent ID
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_sqlParentID = '';
	
	/**
	 * FIELD: Is folder flag
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_sqlFolderFlag = '';
	
	/**
	 * FIELD: Item name field
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_sqlTitle     = '';
	
	/**
	 * FIELD: Order on field
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_sqlOrder     = '';
	
	/**
	 * SQL Field: From
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_sqlTable        = '';
	
	/**
	 * SQL Fields: Additional 'where' information
	 *
	 * @access	protected
	 * @var		string
	 */
	protected $_sqlWhere       = '';
	
	/**
	 * Method constructor
	 *
	 * @access	public
	 * @param	object		Registry Object
	 * @param	array 		Array of settings:
	 *						[ 'sqlPrimaryID'	(the SQL table field for the item ID)
	 *						  'sqlParentID'  	(the SQL table field for the parent ID)
	 *						  'sqlFolderFlag'	(the SQL table field that tells if the row is a folder or not *Optional)
	 *						  'sqlTitle'		(the SQL table field for the item title)
	 *						  'sqlOrder'		(the SQL table field(s) to order on or by)
	 *						  'sqlTable'		(the SQL table name)
	 *						  'sqlWhere'		(Any additional 'where' information *Optional) ]
	 * @return	void
	 */
	public function __construct( ipsRegistry $registry, $settings )
	{
		/* Make object */
		$this->registry   =  $registry;
		$this->DB	      =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache	  =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
		
		/* Sort out settings */
		$this->_sqlPrimaryID  = isset( $settings['sqlPrimaryID']  ) ? $settings['sqlPrimaryID']  : '';
		$this->_sqlParentID   = isset( $settings['sqlParentID']   ) ? $settings['sqlParentID']   : '';
		$this->_sqlFolderFlag = isset( $settings['sqlFolderFlag'] ) ? $settings['sqlFolderFlag'] : '';
		$this->_sqlTitle      = isset( $settings['sqlTitle']      ) ? $settings['sqlTitle']      : '';
		$this->_sqlOrder      = isset( $settings['sqlOrder']      ) ? $settings['sqlOrder']      : '';
		$this->_sqlTable      = isset( $settings['sqlTable']      ) ? $settings['sqlTable']      : '';
		$this->_sqlWhere      = isset( $settings['sqlWhere']      ) ? $settings['sqlWhere']      : '';
		
		/* Build the tiers */
		$this->_buildTiers();
	}
	
	/**
	 * Build the in-memory information tiers
	 *
	 * @access 	private
	 * @return	void
	 */
	private function _buildTiers()
	{
		if ( ! count( $this->_itemCache ) )
		{
			//-----------------------------------------
			// Get pages from db
			//-----------------------------------------
			
			$this->_sqlOrder = $this->_sqlOrder ? $this->_sqlOrder : $this->_sqlTitle;
			
			$this->DB->build( array( 'select' => '*',
									 'where'  => $this->_sqlWhere,
									 'from'   => $this->_sqlTable,
									 'order'  => $this->_sqlOrder ) );
			$this->DB->execute();
			
			while( $item = $this->DB->fetch() )
			{
				if ( $item[ $this->_sqlParentID ] < 1 )
				{
					$item[ $this->_sqlParentID ] = 'root';
				}
				
				$this->_itemCache[ $item[$this->_sqlParentID] ][ $item[$this->_sqlPrimaryID] ] = $item;
				$this->_itemByID[ $item[$this->_sqlPrimaryID] ] = $item[$this->_sqlParentID];
			}
		}
	}
	
	/**
	 * Rebuild the in-memory information
	 *
	 * @access	public
	 * @return	void
	 */
	public function rebuildTiers()
	{
		$this->_itemCache = array();
		$this->_buildTiers();
	}
	
	/**
	 * Returns data
	 *
	 * @access	public
	 * @param	string
	 * @return	mixed
	 */
	public function getData( $key )
	{
		if ( isset( $this->_itemCache[ $key ] ) )
		{
			return $this->_itemCache[ $key ];
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * Return all tier data as an array
	 *
	 * @access	public
	 * @return	array
	 */
	public function asArray()
	{
		return is_array( $this->_itemCache ) ? $this->_itemCache : array();
	}
	
	/**
	 * Return a data entry by primary ID
	 *
	 * @access 	public
	 * @param	int			ID of the data row to return
	 * @return	mixed
	 */
	public function fetchItemByID( $id )
	{
		return $this->_itemCache[ $this->_itemByID[ $id ] ][ $id ];
	}
	
	/**
	 * Fetches all parents of a particular ID
	 *
	 * @access	public
	 * @param	int			ID of the data row to retreive parents
	 * @param	array 		Array of IDs discovered. Used when recursing.
	 * @return	array 		Simple array of IDs
	 */
	public function fetchItemParents( $root_id, $ids=array() )
	{
		$item = $this->fetchItemByID( $root_id );
		
		if ( $item[ $this->_sqlParentID ] and $item[ $this->_sqlParentID ] != 'root' )
		{
			$ids[] = $item[$this->_sqlParentID];
			
			$ids = $this->fetchItemParents( $item[$this->_sqlParentID], $ids );
		}
		
		return $ids;
	}
	
	/**
	 * Fetches all children of a particular ID
	 *
	 * @access	public
	 * @param	int			ID of the data row to retreive children
	 * @param	array 		Array of IDs discovered. Used when recursing.
	 * @return	array 		Simple array of IDs
	 */
	public function fetchItemChildren( $root_id, $ids=array() )
	{
		if ( is_array( $this->_itemCache[ $root_id ] ) )
		{
			foreach( $this->_itemCache[ $root_id ] as $id => $item_data )
			{
				$ids[] = $id;
				
				$ids = $this->fetchItemChildren($id, $ids);
			}
		}
		
		return $ids;
	}
	
	/**
	 * Return a HTML option list of directories
	 *
	 * @access	public
	 * @param	int			Selected ID (shows that item by default in the drop down list)
	 * @param	array		Array of primary IDs to skip (miss from the listing
	 * @param	string 		Text to use at the top of the listing (optional)
	 * @param	int 		ID to use at the top of the listing
	 * @return	array
	 */
	public function fetchDirectoriesDropDown($selected_id, $skip_id=array(), $nodir_text='--Select A Directory--', $nodir_id=0 )
	{
		//-----------------------------------------
		// Got an array?
		//-----------------------------------------
		
		if ( ! is_array( $selected_id ) )
		{
			$_selected_id = $selected_id;
			$selected_id  = array( 0 => $_selected_id );
		}
		
		if ( $nodir_text )
		{
			$jump_string .= "<option value=\"".$nodir_id."\">".$nodir_text."</option>\n";
		}
		
		if ( is_array( $this->_itemCache['root'] ) and count( $this->_itemCache['root'] ) )
		{
			foreach( $this->_itemCache['root'] as $id => $item_data )
			{
				if ( in_array( $item_data[$this->_sqlPrimaryID], $skip_id ) )
				{
					continue;
				}
			
				if ( (IN_DEV AND $item_data[$this->_sqlFolderFlag] ) OR $item_data[$this->_sqlFolderFlag] == 1 )
				{
					$selected = '';
					
					if ( in_array( $item_data[ $this->_sqlPrimaryID ], $selected_id ) )
					{
						$selected = ' selected="selected"';
					}
				
					$jump_string .= "<option value=\"{$item_data[$this->_sqlPrimaryID]}\"".$selected.">".$item_data[$this->_sqlTitle]."</option>\n";
				
					$depth_guide = '--';
				
					if ( is_array( $this->_itemCache[ $item_data[$this->_sqlPrimaryID] ] ) )
					{
						foreach( $this->_itemCache[ $item_data[$this->_sqlPrimaryID] ] as $id => $item_data )
						{
							if ( in_array( $item_data[$this->_sqlPrimaryID], $skip_id ) )
							{
								continue;
							}
			
							if ( (IN_DEV AND $item_data[$this->_sqlFolderFlag] ) OR $item_data[$this->_sqlFolderFlag] == 1 )
							{
								$selected = "";
							
								if ( in_array( $item_data[ $this->_sqlPrimaryID ], $selected_id ) )
								{
									$selected = ' selected="selected"';
								}
							
								$jump_string .= "<option value=\"{$item_data[$this->_sqlPrimaryID]}\"".$selected.">&nbsp;&nbsp;&#0124;".$depth_guide." ".$item_data[$this->_sqlTitle]."</option>\n";
							}
						
							$jump_string = $this->_fetchDirectoriesDropDown( $item_data[$this->_sqlPrimaryID], $jump_string, $depth_guide . '--', $selected_id, $skip_id );
						}
					}
				}
			}
		}
		
		return $jump_string;
	}
	
	/**
	 * Return a HTML option list of directories: Internal version
	 *
	 * @access	private
	 * @param	int			Primary ID of entry to drill down from
	 * @param	string		Currently built option list
	 * @param	string 		Current depth guage
	 * @param	int 		Primary ID selected
	 * @param	array 		Array of IDs to skip
	 * @return	array
	 */
	private function _fetchDirectoriesDropDown($root_id, $jump_string="", $depth_guide="",$selected_id, $skip_id=array())
	{
		//-----------------------------------------
		// Got an array?
		//-----------------------------------------
		
		if ( ! is_array( $selected_id ) )
		{
			$_selected_id = $selected_id;
			$selected_id  = array( $_selected_id => $_selected_id );
		}
		
		if ( is_array( $this->_itemCache[ $root_id ] ) )
		{
			foreach( $this->_itemCache[ $root_id ] as $id => $item_data )
			{
				if ( in_array( $item_data[$this->_sqlPrimaryID], $skip_id ) )
				{
					continue;
				}
						
				if ( (IN_DEV AND $item_data[$this->_sqlFolderFlag] ) OR $item_data[$this->_sqlFolderFlag] == 1 )
				{
					$selected = "";
					
					if ( in_array( $item_data[ $this->_sqlPrimaryID ], $selected_id ) )
					{
						$selected = ' selected="selected"';
					}
					
					$jump_string .= "<option value=\"{$item_data[$this->_sqlPrimaryID]}\"".$selected.">&nbsp;&nbsp;&#039;".$depth_guide." ".$item_data[$this->_sqlTitle]."</option>\n";
				}
					
				$jump_string = $this->_fetchDirectoriesDropDown( $item_data[$this->_sqlPrimaryID], $jump_string, $depth_guide . '--', $html, $selected_id, $skip_id );
			}
		}
		
		return $jump_string;
	}
	
	/**
	 * Return a HTML option list of all items: Internal version
	 *
	 * @access	public
	 * @param	array		Array of selected IDs
	 * @param	array 		Array of IDs to skip
	 * @param	array 		[Optional: Default row array (value, name). Eg: array( '0', 'None' )
	 * @return	string
	 */
	public function fetchAllsItemDropDown($selected_id=array(), $skip_id=array(), $defaultRow=array())
	{
		//-----------------------------------------
		// Multi selections?
		//-----------------------------------------
		
		if ( ! is_array( $selected_id ) )
		{
			$selected_id = array( $selected_id );
		}
		
		//-----------------------------------------
		// Default row?
		//-----------------------------------------
		
		if ( count( $defaultRow ) )
		{
			if ( in_array( $defaultRow[0], $selected_id ) )
			{
				$selected = ' selected="selected"';
			}
			
			$jump_string .= "<option value=\"{$defaultRow[0]}\"".$selected.">".$defaultRow[1]."</option>\n";
		}
		
		//-----------------------------------------
		// Here we go...
		//-----------------------------------------
		
		foreach( $this->_itemCache['root'] as $id => $item_data )
		{
			if ( in_array( $item_data[$this->_sqlPrimaryID], $skip_id ) )
			{
				continue;
			}
			
			$selected = '';
			
			if ( in_array( $item_data[$this->_sqlPrimaryID], $selected_id ) )
			{
				$selected = ' selected="selected"';
			}
			
			$jump_string .= "<option value=\"{$item_data[$this->_sqlPrimaryID]}\"".$selected.">".$item_data[$this->_sqlTitle]."</option>\n";
			
			$depth_guide = '--';
			
			if ( is_array( $this->_itemCache[ $item_data[$this->_sqlPrimaryID] ] ) )
			{
				foreach( $this->_itemCache[ $item_data[$this->_sqlPrimaryID] ] as $id => $item_data )
				{
					if ( in_array( $item_data[$this->_sqlPrimaryID], $skip_id ) )
					{
						continue;
					}
		
					$selected = "";
					
					if ( is_array( $selected_id ) AND in_array( $item_data[$this->_sqlPrimaryID], $selected_id ) )
					{
						$selected = ' selected="selected"';
					}
					
					//-----------------------------------------
					// Skip system folder
					//-----------------------------------------
					
					if ( ! IN_DEV AND $item_data[$this->_sqlFolderFlag] == 2 )
					{
						continue;
					}
					
					$jump_string .= "<option value=\"{$item_data[$this->_sqlPrimaryID]}\"".$selected.">&nbsp;&nbsp;&#039;".$depth_guide." ".$item_data[$this->_sqlTitle]."</option>\n";
				
					$jump_string = $this->_fetchAllsItemDropDown( $item_data[$this->_sqlPrimaryID], $jump_string, $depth_guide . '--', $selected_id, $skip_id );
				}
			}
		}
		
		return $jump_string;
	}
	
	/**
	 * Return a HTML option list of all items: Internal version
	 *
	 * @access	private
	 * @param	int			Primary ID of entry to drill down from
	 * @param	string		Currently built option list
	 * @param	string 		Current depth guage
	 * @param	int 		Primary ID selected
	 * @param	array 		Array of IDs to skip
	 * @return	string
	 */
	private function _fetchAllsItemDropDown($root_id, $jump_string="", $depth_guide="",$selected_id, $skip_id=array())
	{
		if ( is_array( $this->_itemCache[ $root_id ] ) )
		{
			foreach( $this->_itemCache[ $root_id ] as $id => $item_data )
			{
				if ( in_array( $item_data[$this->_sqlPrimaryID], $skip_id ) )
				{
					continue;
				}
				
				$selected = "";
				
				if ( is_array( $selected_id ) AND in_array( $item_data[ $this->_sqlPrimaryID ], $selected_id ) )
				{
					$selected = ' selected="selected"';
				}
				
				//-----------------------------------------
				// Skip system folder
				//-----------------------------------------
				
				if ( ! IN_DEV AND $item_data[$this->_sqlFolderFlag] == 2 )
				{
					continue;
				}
				
				$jump_string .= "<option value=\"{$item_data[$this->_sqlPrimaryID]}\"".$selected.">&nbsp;&nbsp;&#0124;".$depth_guide." ".$item_data[$this->_sqlTitle]."</option>\n";
					
				$jump_string = $this->_fetchAllsItemDropDown( $item_data[$this->_sqlPrimaryID], $jump_string, $depth_guide . '--', $html, $selected_id, $skip_id );
			}
		}
		
		return $jump_string;
	}
}