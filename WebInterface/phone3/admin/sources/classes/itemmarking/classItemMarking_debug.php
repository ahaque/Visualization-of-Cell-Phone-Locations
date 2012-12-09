<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Item Marking
 * Owner: Matt "Oh Lord, why did I get assigned this?" Mecham
 * Last Updated: $Date: 2009-05-08 13:53:41 +0100 (Fri, 08 May 2009) $
 *
 * @author 		$Author: matt $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @since		9th March 2005 11:03
 * @version		$Revision: 4620 $
 *
 * @todo 	[Future] Perhaps find more ways to reduce the size of items_read
 * @todo 	[Future] Maybe add an IN_DEV tool to warn when markers have been cancelled out / size drastically reduced
 */

require_once( dirname(__FILE__) . '/classItemMarking.php' );
	
class classItemMarking_debug extends classItemMarking
{
	/**
	 * Tracing this user?
	 * @var	bool
	 */
	private $TRACE = false;
	
	/**
	 * Marking session key
	 * @var string
	 */
	private $SESSION_KEY = '';
	
	/**
	 * Marking URL
	 * @var string
	 */
	private $URL = '';
	
	/**
	 * Method constructor
	 *
	 * @access	public
	 * @param	object		Registry Object
	 * @return	void
	 */
	public function __construct( ipsRegistry $registry )
	{
		parent::__construct( $registry );
		
		/* Are we tracing this user? */
		list( $_groups, $_ids ) = explode( '&', trim( IPS_TOPICMARKERS_TRACE ) );
			
		/* Groups */
		list( $_t, $groups ) = explode( '=', trim( $_groups ) );
		list( $_t, $ids )    = explode( '=', trim( $_ids ) );
	
		if ( $groups )
		{
			foreach( explode( ',', $groups ) as $gid )
			{
				if ( $this->memberData['member_group_id'] == $gid )
				{
					$this->TRACE = TRUE;
					break;
				}
			}
		}
		
		if ( $ids AND ! $this->TRACE )
		{
			foreach( explode( ',', $ids ) as $id )
			{
				if ( $this->memberData['member_id'] == trim( $id ) )
				{
					$this->TRACE = TRUE;
					break;
				}
			}
		}
		
		/* Create session key */
		$this->SESSION_KEY = md5( uniqid( microtime() . 'tms', true ) );
		
		/* Create URL */
		$this->URL = my_getenv('HTTP_HOST') . my_getenv('REQUEST_URI');
		
		
		/* Got a table? */
		//$this->DB->dropTable( 'core_topicmarker_debug' );
		if ( ! $this->DB->checkForTable( 'core_topicmarker_debug' ) )
		{
			$prefix = $this->registry->dbFunctions()->getPrefix();
			
			$this->DB->query( "CREATE TABLE " . $prefix . "core_topicmarker_debug (
								marker_member_id	INT(10) NOT NULL default 0,
								marker_session_key	VARCHAR(32) NOT NULL default '',
								marker_message		VARCHAR(255) NOT NULL default '',
								marker_data_freezer	MEDIUMTEXT,
								marker_data_storage	MEDIUMTEXT,
								marker_data_memory  MEDIUMTEXT,
								marker_timestamp	INT(10) NOT NULL default 0,
								marker_microtime	VARCHAR(200) NOT NULL default '0',
								marker_url			TEXT,
								marker_data_1		TEXT,
								marker_data_2		TEXT,
								marker_data_3		TEXT,
								marker_data_4		TEXT,
								marker_data_5		TEXT,
								KEY marker_member_id (marker_member_id),
								KEY marker_microtime (marker_microtime),
								KEY marker_session_key (marker_session_key),
								KEY marker_timestamp (marker_timestamp ) )" );
		}
		
		/* Kick start the session off */
		$this->_addEntry( 'Marker session INIT done' );
	}
	
	/**
	 * Check the read status of an item
	 *
	 * Forum example (forumID is mapped to :item_app_key_1). itemID is the topic ID
	 *
	 * @example	$read = $itemMarking->isRead( array( 'forumID' => 2, 'itemID' => 99, 'itemLastUpdate' => 1989098989 ) );
	 *
	 * @access	public
	 * @param	array 		Array of data
	 * @param	string		Optional app
	 * @return	boolean     TRUE is read / FALSE is unread
	 */
	public function isRead( $data, $app='' )
	{
		return parent::isRead( $data, $app );
	}
	
	/**
	 * Mark as read
	 *
	 * Forum example (forumID is mapped to :item_app_key_1). itemID is the topic ID
	 *
	 * @example $itemMarking->markAsRead( array( 'forumID' => 34, 'itemID' => 99 ) )
	 * @example $itemMarking->markAsRead( array( 'forumID' => 34 ) )
	 *
	 * @access	public
	 * @param	array
	 * @param 	string	[App]
	 * @return	void
	 */
	public function markRead( $data, $app='' )
	{
		/* Add Debug entry */
		$this->_addEntry( 'Marked item as read ', array( 'marker_data_1' => $app, 'marker_data_2' => $data ) );
		
		parent::markRead( $data, $app );
	}
	
	/**
	 * Marks everything within an app as read
	 *
	 * @access	public
	 * @param	string	App
	 * @return	void
	 */
	public function markAppAsRead( $app )
	{
		parent::markAppAsRead( $app );
		
		/* Add Debug entry */
		$this->_addEntry( 'Marked APP as read: ' . $app, array( 'marker_data_1' => $app ) );
	}
	
	/**
	 * Fetch the last time an item was marked
	 * checks app reset... then the different key resets.. then an individual key
	 *
	 * In this example: itemID is within 'item_read_array'.
	 * $lastReset = $this->fetchTimeLastMarked( array( 'forumID' => 2, 'itemID' => '99' ) );
	 *
	 * @access	public
	 * @param	array 		Data
	 * @param	string		Optional app
	 * @return	int 		Timestamp item was last marked or 0 if unread
	 */
	public function fetchTimeLastMarked( $data, $app='' )
	{
		return parent::fetchTimeLastMarked( $data, $app );
	}
	
	/**
	 * Fetch the unread count
	 *
	 * In this example we are retrieving the number of unread items for a forum id 2
	 * $unreadCount = $this->fetchUnreadCount( array( 'forumID' => 2 ) );
	 *
	 * @access	public
	 * @param	array 		Data
	 * @param	string		App
	 * @return	int 		Number of unread items left
	 */
	public function fetchUnreadCount( $data, $app='' )
	{
		$num = parent::fetchUnreadCount( $data, $app );
		
		/* Add Debug entry */
		$this->_addEntry( 'fetchUnreadCount - ' . $num, array( 'marker_data_1' => $app, 'marker_data_2' => $data ) );
		
		return $num;
	}
	
	/**
	 * Fetch read IDs since last global reset
	 * Returns IDs read based on incoming data
	 *
	 * @example		$readIDs = $itemMarking->fetchReadIds( array( 'forumID' => 2 ) );
	 * @access		public
	 * @param		array 		Array of data
	 * @param		strng		[App]
	 * @return		array 		Array of read item IDs
	 */
	public function fetchReadIds( $data, $app='' )
	{
		parent::fetchReadIds( $data, $app );
	}
	
	/**
	 * Save my markers to DB
	 *
	 * Saves the current values in $this->_itemMarkers back to the DB
	 *
	 * @access	public
	 * @return	int		Number of rows saved
	 */
	public function writeMyMarkersToDB()
	{
		parent::writeMyMarkersToDB();
	}
	
	/**
	 * Manual clean up
	 * Currently run via a task and in the __myDestruct method, but can be run from anywhere
	 *
	 * @access	public
	 * @param	int 		Number of items to limit
	 * @param	int			Number of hours (ago) to check
	 * @param	boolean		Check for active sessions first
	 * @return	int			Number of items cleaned
	 */
	public function manualCleanUp( $limit=0, $hours=6, $checkSessions=TRUE )
	{
		$num = parent::manualCleanUp( $limit, $hours, $checkSessions );
		
		if ( $num !== FALSE )
		{
			$this->_addEntry( 'manualCleanUp called. Cleaned: ' . $num );
		}
	}
	
	/**
	 * Update topic markers
	 *
	 * @access	protected
	 * @param	array		Array of Member IDs
	 * @return	void
	 */
	protected function _save( $memberIDs )
	{
		parent::_save( $memberIDs );
	}
	
	/**
	 * Update markers storage table
	 *
	 * @access	protected
	 * @return	bool
	 */
	protected function _saveStorage()
	{
		$result = parent::_saveStorage();
		
		if ( $result )
		{
			$this->_addEntry( 'Storage table updated' );
		}
	}
	
	/**
	 * Manual destructor called by ips_MemberRegistry::__myDestruct()
	 * Gives us a chance to do anything we need to do before other
	 * classes are culled
	 *
	 * @access	public
	 * @return	void
	 */
	public function __myDestruct()
	{
		/* Add Debug entry */
		$this->_addEntry( 'Destructor Run' );
		
		parent::__myDestruct();
	}
	
	/**
	 * Add entry
	 *
	 * @access	private
	 * @param	string		Message
	 * @param	array 		Array of data
	 * @return	bool
	 */
	private function _addEntry( $message, $data=array() )
	{
		if ( $this->TRACE AND $this->memberData['member_id'] AND ! IPS_IS_TASK)
		{
			/* Clean up data */
			foreach( array( 1,2,3,4,5 ) as $n )
			{
				if ( isset( $data['marker_data_' . $n ] ) )
				{
					if ( is_array( $data['marker_data_' . $n ] ) )
					{
						$data['marker_data_' . $n ] = serialize( $data['marker_data_' . $n ] );
					}
				}
			}
			
			/* Add it.. */
			$this->DB->insert( 'core_topicmarker_debug', array( 'marker_member_id'    => $this->memberData['member_id'],
																'marker_session_key'  => $this->SESSION_KEY,
																'marker_message'	  => $message,
																'marker_data_freezer' => serialize( ( isset( $data['marker_data_freezer'] ) ) ? $data['marker_data_freezer'] : $this->_fetchFromFreezer() ),
																'marker_data_storage' => serialize( ( isset( $data['marker_data_storage'] ) ) ? $data['marker_data_storage'] : $this->_fetchFromCache()   ),
																'marker_data_memory'  => serialize( ( isset( $data['marker_data_memory'] ) )  ? $data['marker_data_memory']  : $this->_fetchFromMemory()  ),
																'marker_timestamp'    => time(),
																'marker_microtime'    => microtime(),
																'marker_url'		  => $this->URL,
																'marker_data_1'		  => isset( $data['marker_data_1'] ),
																'marker_data_2'		  => isset( $data['marker_data_2'] ),
																'marker_data_3'		  => isset( $data['marker_data_3'] ),
																'marker_data_4'		  => isset( $data['marker_data_4'] ),
																'marker_data_5'		  => isset( $data['marker_data_5'] ) ) );
			return TRUE;
		}
	}
	
	/**
	 * Fetch freezer markers 
	 *
	 * @access	private
	 * @return	array
	 */
	private function _fetchFromFreezer()
	{
		$items = array();
		
		if ( $this->memberData['member_id'] )
		{
			$this->DB->build( array( 'select' => '*',
									 'from'   => 'core_item_markers',
									 'where'  => 'item_member_id=' . $this->memberData['member_id'] ) );

			$itemMarking = $this->DB->execute();

			while( $row = $this->DB->fetch( $itemMarking ) )
			{
				$items[ $row['item_app'] ][ $row['item_key'] ] = $row;
			}
		}
		
		return $items;
	}
	
	/**
	 * Fetch "hot" cache markers 
	 *
	 * @access	private
	 * @return	array
	 */
	private function _fetchFromCache()
	{
		$items = array();
		
		if ( $this->memberData['member_id'] )
		{
			$items = $this->DB->buildAndFetch( array( 'select' => '*',
									 				  'from'   => 'core_item_markers_storage',
													  'where'  => 'item_member_id=' . $this->memberData['member_id'] ) );

			$items = unserialize( $items['item_markers'] );
		}
		
		return $items;
	}
	
	/**
	 * Fetch in memory cache markers 
	 *
	 * @access	private
	 * @return	array
	 */
	private function _fetchFromMemory()
	{
		return ( is_array( $this->_itemMarkers ) ) ? $this->_itemMarkers : array();
	}
	

}