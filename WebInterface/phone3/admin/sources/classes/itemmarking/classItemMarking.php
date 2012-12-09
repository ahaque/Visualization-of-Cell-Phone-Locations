<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Item Marking
 * Owner: Matt "Oh Lord, why did I get assigned this?" Mecham
 * Last Updated: $Date: 2009-07-31 11:24:21 -0400 (Fri, 31 Jul 2009) $
 *
 * @author 		$Author: matt $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @since		9th March 2005 11:03
 * @version		$Revision: 4959 $
 *
 * @todo 	[Future] Perhaps find more ways to reduce the size of items_read
 * @todo 	[Future] Maybe add an IN_DEV tool to warn when markers have been cancelled out / size drastically reduced
 */

class classItemMarking
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
	protected $cache;
	protected $caches;
	/**#@-*/
	
	/**
	 * Dead session data
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $_deadSessionData	= array();
	
	/**
	 * Module apps
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $_moduleApps		= array();
	
	/**
	 * Cache for internal use
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $_cache				= array();
	
	/**
	 * Cookie Data
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $_cookie			= array();
	
	/**
	 * Item Markers Data
	 *
	 * @access	protected
	 * @var		array
	 */
	protected $_itemMarkers		= array();
	
	/**
	 * Last cleared time for key
	 *
	 * @access	protected
	 * @var		int
	 */
	protected $_keyLastCleared	= 0;
	
	/**
	 * DB cut off time stamp
	 *
	 * @access	protected
	 * @var		int
	 */
	protected $_dbCutOff			= 0;
	
	/**
	 * Cookie counter
	 *
	 * @access	protected
	 * @var		int
	 */
	protected $_cookieCounter		= 0;
	
	/**
	 * Last save time
	 *
	 * @access	protected
	 * @var		int
	 */
	protected $_lastSaved			= 0;
	
	/**
	 * Saved session already?
	 * 
	 * @access	protected
	 * @var		bool
	 */
	protected $_savedStorage     = false;
	
	/**
	 * Instant save on/off
	 * 
	 * @access	protected
	 * @var		bool
	 */
	protected $_instantSave     = true;
	
	/**
	 * Changes made flag
	 * 
	 * @access	protected
	 * @var		bool
	 */
	protected $_changesMade     = false;
	
	/**
	 * Enable cookies?
	 *
	 */
	const ALLOWCOOKIES			 = true;
	
	/**
	 * Method constructor
	 *
	 * @access	public
	 * @param	object		Registry Object
	 * @return	void
	 */
	public function __construct( ipsRegistry $registry )
	{
		$_NOW = IPSDebug::getMemoryDebugFlag();
	
		/* Make object */
		$this->registry   =  $registry;
		$this->DB	      =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache	  =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
		
		/* Task? */
		if ( IPS_IS_TASK === TRUE )
		{
			return;
		}
		
		/* Search engine? */
		if ( $this->member->is_not_human === TRUE )
		{
			return;
		}
	
		/* Use cookie marking for guests */
		if ( ! $this->memberData['member_id'] )
		{
			$this->settings['topic_marking_enable'] = 0;
		}
		
		/* Build */
		$this->memberData['item_markers'] = $this->_generateIncomingItemMarkers();

		/* Generate last saved time */
		$this->_lastSaved = time();

		/* Set Up */
		if ( is_array( $this->memberData['item_markers'] ) )
		{
			foreach( $this->memberData['item_markers'] as $app => $key )
			{
				foreach( $this->memberData['item_markers'][ $app ] as $key => $data )
				{
					if ( $app AND $key )
					{
						if ( $data['item_last_saved'] )
						{
							$data['item_last_saved'] = intval( $data['item_last_saved'] );
							
							if ( $data['item_last_saved'] < $this->_lastSaved )
							{
								$this->_lastSaved = $data['item_last_saved'];
							}
						}
						
						$this->_itemMarkers[ $app ][ $key ] = $data;
					}
				}
			}
		}
	
		/* Fetch cookies */
		$apps = new IPSApplicationsIterator();
		
		foreach( $apps as $app )
		{
			if ( $apps->isActive() )
			{
				$_app    = $apps->fetchAppDir();
				$_value  = IPSCookie::get( 'itemMarking_' . $_app );
				$_value2 = IPSCookie::get( 'itemMarking_' . $_app . '_items' );
				
				$this->_cookie[ 'itemMarking_' . $_app ]           = ( is_array( $_value ) )  ? $_value  : array();
				$this->_cookie[ 'itemMarking_' . $_app . '_items'] = ( is_array( $_value2 ) ) ? $_value2 : array();
				
				/* Clean up  */
				if ( is_array( $this->_cookie[ 'itemMarking_' . $_app . '_items'] ) )
				{
					$_items = ( is_array( $this->_cookie[ 'itemMarking_' . $_app . '_items'] ) ) ? $this->_cookie[ 'itemMarking_' . $_app . '_items'] : unserialize( $this->_cookie[ 'itemMarking_' . $_app . '_items'] );
				
					$this->_cookieCounter = 0;
					arsort( $_items, SORT_NUMERIC );
					
					$_newData = array_filter( $_items, array( $this, 'arrayFilterCookieItems' ) );
					
					$this->_cookie[ 'itemMarking_' . $_app . '_items'] = $_newData;
					
					IPSDebug::addMessage( 'Cookie loaded: itemMarking_' . $_app . '_items' . ' - ' . serialize( $_newData ) );
					IPSDebug::addMessage( 'Cookie loaded: itemMarking_' . $_app . ' - ' . serialize( $_value ) );
				}
			}
		}
		
		IPSDebug::setMemoryDebugFlag( "Topic markers initialized", $_NOW );
	}
	
	/**
	 * Disable instant save
	 *
	 * @access	public
	 * @return	void
	 */
	public function disableInstantSave()
	{
		$this->_instantSave = false;
	}
	
	/**
	 * Enable instant save (Default mode anyway)
	 *
	 * @access	public
	 * @return	void
	 */
	public function enableInstantSave()
	{
		$this->_instantSave = true;
	}
	
	/**
	 * Fetch status
	 *
	 * @access	public
	 * @return	void
	 */
	public function fetchInstantSaveStatus()
	{
		return ( $this->_instantSave === true ) ? true : false;
	}
	
	/**
	 * Add to cookie
	 *
	 * @access	protected
	 * @param	string 		Key
	 * @param	array
	 * @return	void
	 */
	protected function _updateCookieData( $key, $array )
	{
		$this->_cookie[ $key ] = $array;
	}
	
	/**
	 * Fetch cookie data
	 *
	 * @access	protected
	 * @param	key
	 * @return	array
	 */
	protected function _fetchCookieData( $key )
	{
		if ( ! self::ALLOWCOOKIES )
		{
			return array();
		}
		
		if ( is_array( $this->_cookie[ $key ] ) )
		{
			return $this->_cookie[ $key ];
		}
		else
		{
			return array();
		}
	}
	
	/**
	 * Save cookie
	 *
	 * @access	protected
	 * @param	string		Key name (leave blank to save out all cookies)
	 * @return	void
	 */
	protected function _saveCookie( $key='' )
	{
		if ( ! self::ALLOWCOOKIES )
		{
			return;
		}
		
		if ( $key AND is_array( $this->_cookie[ $key ] ) )
		{
			IPSCookie::set( $key, $this->_cookie[ $key ], 1 );
		}
		else
		{
			foreach( $this->_cookie as $k => $v )
			{
				/* We don't want to set empty arrays, so.. */
				if ( ! $v OR ( is_array( $v ) AND ! count( $v ) ) )
				{
					/* Do we have a cookie? */
					$test = IPSCookie::get( $k );
					
					if ( $test )
					{
						/* set a blank, non sticky cookie */
						IPSCookie::set( $k, '-', 0, -1 );
					}
					else
					{
						continue;
					}
				}
				else
				{
					IPSCookie::set( $k, $v, 1 );
				}
			}
		}
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
		$app         = ( $app ) ? $app : IPS_APP_COMPONENT;
		$data        = $this->_fetchModule( $app )->convertData( $data );
		$_data       = $data;
		$times       = array();
		$cookie      = $this->_fetchCookieData( 'itemMarking_' . $app );
		$cookieItems = $this->_fetchCookieData( 'itemMarking_' . $app . '_items');

		unset( $_data['itemLastUpdate'] );
	
		$times[] = $this->fetchTimeLastMarked( $_data, $app );
		
		if ( $data['itemID'] )
		{
			if ( isset( $cookieItems[ $data['itemID'] ] ) )
			{
				$times[] = intval( $cookieItems[ $data['itemID'] ] );
			}
			
			/* Update the main row? */
			if ( $this->settings['topic_marking_enable'] AND isset( $cookieItems[ $data['itemID'] ] ) AND $cookieItems[ $data['itemID'] ] > $times[1] )
			{
				$mainKey = $this->_findMainRowByKey( $data, $app );
				
				if ( $mainKey )
				{
					/* Mark markers as having changed */
					$this->_changesMade = TRUE;
					
					$this->_itemMarkers[ $app ][ $mainKey ]['item_read_array'][ $data['itemID'] ] = $cookieItems[ $data['itemID'] ];
				}
			}
		}
	
		return ( IPSLib::fetchHighestNumber( $times ) >= $data['itemLastUpdate'] ) ? TRUE : FALSE; 
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
		$app         = ( $app ) ? $app : IPS_APP_COMPONENT;
		$origData    = $data;
		$data        = $this->_fetchModule( $app )->convertData( $data );
		$cookie      = $this->_fetchCookieData( 'itemMarking_' . $app );
		$cookieItems = $this->_fetchCookieData( 'itemMarking_' . $app . '_items');
		$mainKey     = $this->_findMainRowByKey( $data, $app );
		
		/* Search engine? */
		if ( $this->member->is_not_human === TRUE )
		{
			return;
		}
		
		/* Reset flag */
		$this->_savedStorage = FALSE;
		
		/* Mark markers as having changed */
		$this->_changesMade = TRUE;
		
		if ( $data['itemID'] )
		{
			/* Cookie */
			$cookieItems[ $data['itemID'] ] = time();
			$this->_updateCookieData( 'itemMarking_' . $app . '_items', $cookieItems );
			
			$_cookieGreset = isset( $cookie['greset'][ $this->_makeKey( $data, TRUE ) ] ) ? intval( $cookie['greset'][ $this->_makeKey( $data, TRUE ) ] ) : 0;
			$_readItems    = $cookieItems; 
			
			/* Do we need to clean up? */
			if ( $this->settings['topic_marking_enable'] )
			{
				/* DB */
				$this->_itemMarkers[ $app ][ $mainKey ]['item_read_array'][ $data['itemID'] ] = time();
				
				/* Check the cookie again.. */
				if ( $_cookieGreset AND $_cookieGreset > $this->_itemMarkers[ $app ][ $mainKey ]['item_global_reset'] )
				{
					$this->_itemMarkers[ $app ][ $mainKey ]['item_global_reset'] = $_cookieGreset;
				}
				
				/* Overwrite read items */
				$_readItems = $this->_itemMarkers[ $app ][ $mainKey ]['item_read_array'];
			}
				
			/* Check last reset time */
			$_lastReset = IPSLib::fetchHighestNumber( array( $_cookieGreset, isset( $cookie['global']) ? $cookie['global'] : 0, intval( $this->_itemMarkers[ $app ][ $mainKey ]['item_global_reset'] ) ) );
			
			/* Fetch unread items */
			$_unreadCount = $this->_fetchModule( $app )->fetchUnreadCount( $origData, $_readItems, $_lastReset );
		
			if ( $_unreadCount['count'] > 0 )
			{
				if ( $this->settings['topic_marking_enable'] )
				{
					$this->_itemMarkers[ $app ][ $mainKey ]['item_unread_count'] = $_unreadCount['count'];
					
					if ( $_unreadCount['lastItem'] )
					{
						//$this->_itemMarkers[ $app ][ $mainKey ]['item_global_reset'] = $_unreadCount['lastItem'];// - 2;
					}
				}
			}
			else
			{
				if ( $this->settings['topic_marking_enable'] )
				{
					/* Update the last global reset time and clear the read array */
					$this->_itemMarkers[ $app ][ $mainKey ]['item_read_array']   = array();
					$this->_itemMarkers[ $app ][ $mainKey ]['item_global_reset'] = time();
					$this->_itemMarkers[ $app ][ $mainKey ]['item_unread_count'] = 0;
				}
				
				/* Cookie */
				$cookie['greset'][ $this->_makeKey( $data, TRUE ) ] = time();
				$this->_updateCookieData( 'itemMarking_' . $app, $cookie );
				$this->_updateCookieData( 'itemMarking_' . $app . '_items', array() );
			}
		}
		else
		{
			if ( $this->settings['topic_marking_enable'] )
			{
				/* Update the last global reset time and clear the read array */
				$this->_itemMarkers[ $app ][ $mainKey ]['item_read_array']   = array();
				$this->_itemMarkers[ $app ][ $mainKey ]['item_global_reset'] = time();
				$this->_itemMarkers[ $app ][ $mainKey ]['item_unread_count'] = 0;
			}
			
			/* Cookie */
			$cookie['greset'][ $this->_makeKey( $data, TRUE ) ] = time();
			$this->_updateCookieData( 'itemMarking_' . $app, $cookie );
			$this->_updateCookieData( 'itemMarking_' . $app . '_items', array() );
		}
		
		/* Add in global update */
		$this->_itemMarkers[ $app ][ $mainKey ]['item_last_update'] = time();
		
		/* Save cookie */
		$this->_saveCookie();
		
		/* Save markers */
		if ( $this->fetchInstantSaveStatus() )
		{
			$this->_saveStorage();
		}
	}
	
	/**
	 * Marks everything within an app as read
	 *
	 * @access	public
	 * @param	string	[App Optional. If ommited, all apps are marked as read]
	 * @return	void
	 */
	public function markAppAsRead( $app='' )
	{
		/* Search engine? */
		if ( $this->member->is_not_human === TRUE )
		{
			return;
		}
		
		/* Cookie */
		$cookie['global'] = time();
		$cookie['greset'] = array();
		
		/* Reset flag */
		$this->_savedStorage = FALSE;
		
		/* Mark markers as having changed */
		$this->_changesMade = TRUE;
		
		/* One app or all? */
		if ( $app )
		{
			/* Reset member cache */
			IPSMember::packMemberCache( $this->memberData['member_id'], array('gb_mark__' . $app => time() ) );
			
			/* Delete permanent rows */
			$this->DB->delete( 'core_item_markers', "item_app='" . $app . "' AND item_member_id=" . intval( $this->memberData['member_id'] ) );
			
			/* Update the last global reset time and clear the read array */
			$this->_itemMarkers[ $app ][ $_key ]['item_read_array']   = array();
			$this->_itemMarkers[ $app ][ $_key ]['item_global_reset'] = time();
			$this->_itemMarkers[ $app ][ $_key ]['item_unread_count'] = 0;
			$this->_itemMarkers[ $app ][ $_key ]['item_last_update']  = time();
			
			/* Save markers */
			if ( $this->fetchInstantSaveStatus() )
			{
				$this->_saveStorage();
			}
			
			/* Update cookies */
			$this->_updateCookieData( 'itemMarking_' . $app . '_items', array() );
			$this->_updateCookieData( 'itemMarking_' . $app, $cookie );
		}
		else
		{
			/* Do 'em all */
			$cache = array();
			
			foreach( $this->_itemMarkers as $app => $data )
			{
				$cache[ 'gb_mark__' . $app ] = time();
				
				/* Update cookies */
				$this->_updateCookieData( 'itemMarking_' . $app . '_items', array() );
				$this->_updateCookieData( 'itemMarking_' . $app, $cookie );
			}
			
			if ( count( $cache ) )
			{
				/* Reset member cache */
				IPSMember::packMemberCache( $this->memberData['member_id'], $cache );
			}
			
			/* Delete temporary storage */
			$this->DB->delete( 'core_item_markers_storage', 'item_member_id=' . intval( $this->memberData['member_id'] ) );
			
			/* Delete permanent rows */
			$this->DB->delete( 'core_item_markers', "item_member_id=" . intval( $this->memberData['member_id'] ) );
			
			/* Reset internal array */
			$this->_itemMarkers = array();
			
			/* Reset flag */
			$this->_savedStorage = TRUE;
		}
		
		/* Save cookie */
		$this->_saveCookie();
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
		$app   = ( $app ) ? $app : IPS_APP_COMPONENT;
		$data  = $this->_fetchModule( $app )->convertData( $data );

		$times = array();
		
		$times[] = intval( $this->_findLatestGlobalReset( $data, $app ) );

		if ( isset( $data['itemID'] ) )
		{
			$times[] = intval( $this->_findLatestItemReset( $data, $app ) );
		}

		return IPSLib::fetchHighestNumber( $times );
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
		$app   = ( $app ) ? $app : IPS_APP_COMPONENT;
		$data  = $this->_fetchModule( $app )->convertData( $data );

		$times = array();
		
		if( isset( $data['item_app_key_1'] ) )
		{
			$mainKey  = $this->_findMainRowByKey( $data, $app );
			
			return intval( $this->_itemMarkers[ $app ][ $mainKey ]['item_unread_count'] );
		}

		return 0;
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
		$app      = ( $app ) ? $app : IPS_APP_COMPONENT;
		$origData = $data;
		$data     = $this->_fetchModule( $app )->convertData( $data );
		$mainKey  = $this->_findMainRowByKey( $data, $app );
		
		if ( isset( $this->_itemMarkers[ $app ][ $mainKey ]['item_read_array'] ) AND is_array($this->_itemMarkers[ $app ][ $mainKey ]['item_read_array']) )
		{
			return array_keys( $this->_itemMarkers[ $app ][ $mainKey ]['item_read_array'] );
		}
		else
		{
			return array();
		}
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
		if ( ! $this->settings['topic_marking_enable'] )
		{
			return 0;
		}
		
		/* Reset flag */
		$this->_savedStorage = FALSE;
		
		/* Mark markers as having changed */
		$this->_changesMade = TRUE;
		
		$_saved = 0;
		
		if ( $this->memberData['member_id'] AND is_array( $this->_itemMarkers ) AND $this->settings['topic_marking_enable'] )
		{
			foreach( $this->_itemMarkers as $app => $data )
			{
				foreach( $this->_itemMarkers[ $app ] as $_key => $_data )
				{
					/* Update internal marker array */
					$this->_itemMarkers[ $app ][ $_key ]['item_last_saved'] = time();
					
					$this->_save( $this->memberData['member_id'], $_key, $_data, $app );
					$_saved++;
				}
			}
		}
	
		return $_saved;
	}
		
	/**
	 * Find latest Global reset
	 *
	 * @access	protected
	 * @param	array 		Array of data (DB field names)
	 * @param	string 		App to check
	 * @return	int			Timestamp
	 */
	protected function _findLatestItemReset( $data, $app )
	{
		$cookie      = $this->_fetchCookieData( 'itemMarking_' . $app );
		$cookieItems = $this->_fetchCookieData( 'itemMarking_' . $app . '_items');
		$_times      = array();
	
		$mainKey = $this->_findMainRowByKey( $data, $app );
		$mainRow = $this->_itemMarkers[ $app ][ $mainKey ];
		
		/* Got a DB field? */
		if ( isset( $mainRow['item_read_array'] ) AND is_array( $mainRow['item_read_array'] ) )
		{
			if( isset( $mainRow['item_read_array'][ $data['itemID'] ] ) )
			{
				$_times[] = intval( $mainRow['item_read_array'][ $data['itemID'] ] );
			}
		}
		
		/* Got a cookie field? */
		if ( isset( $cookieItems[ $data['itemID'] ] ) )
		{
			$_times[] = $cookieItems[ $data['itemID'] ];
		}
		
		$_time = IPSLib::fetchHighestNumber( $_times );
		
		return $_time;
	}
		
	/**
	 * Find latest Global reset
	 *
	 * @access	protected
	 * @param	array 		Array of data (DB field names)
	 * @param	string 		App to check
	 * @return	int			Timestamp
	 */
	protected function _findLatestGlobalReset( $data, $app )
	{
		$_time       = 0;
		$_times      = array();
		$_key        = $this->_makeKey( $data );
		$cookie      = $this->_fetchCookieData( 'itemMarking_' . $app );
		$cookieItems = $this->_fetchCookieData( 'itemMarking_' . $app . '_items');
		
		$mainKey = $this->_findMainRowByKey( $data, $app );
			
		/* Got all fields? */
		if ( isset( $this->_itemMarkers[ $app ][ $mainKey ]['item_global_reset'] ) )
		{
			$_times[] = $this->_itemMarkers[ $app ][ $mainKey ]['item_global_reset'];
		}
		
		if ( isset( $cookie['greset'][ $this->_makeKey( $data, TRUE ) ] ) )
		{
			$_times[] = intval( $cookie['greset'][ $this->_makeKey( $data, TRUE ) ] );
		}
		
		if ( isset( $cookie['global'] ) )
		{
			$_times[] = intval( $cookie['global'] );
		}
		
		if ( isset( $this->memberData['_cache']['gb_mark__'. $app ] ) )
		{
			$_times[] = $this->memberData['_cache']['gb_mark__'.  $app ];
		}
		
		/* Get most recent time */
		$_time = IPSLib::fetchHighestNumber( $_times );
		
		return $_time;
	}
	
	/**
	 * Find a particular row
	 *
	 * @access	protected
	 * @param	array 		Array of data
	 * @param	string		App
	 * @return	array 		Array of data: core_item_marking row, effectively
	 */
	protected function _findMainRowByKey( $data, $app )
	{
		/* Not interested in this for the main row */
		unset( $data['itemID'], $data['itemLastUpdate'] );
		
		$_key  = $this->_makeKey( $data );
		
		if ( ! is_array( $this->_itemMarkers[ $app ] ) )
		{
			/* Mark markers as having changed */
			$this->_changesMade = TRUE;
			
			/* Add in extra items */
			$data['item_app']		 = $app;
			$data['item_key']		 = $_key;
			$data['item_member_id']	 = $this->memberData['member_id'];
			$data['item_read_array'] = array();
			$this->_itemMarkers[ $app ]          = array();
			$this->_itemMarkers[ $app ][ $_key ] = $data;
			
			IPSDebug::addMessage( "Item Marking Key Created! $_key" );
			
			return $_key;
		}
		
		if ( is_array( $this->_itemMarkers[ $app ][ $_key ] ) )
		{
			/* Make sure it contains the app & key */
			$this->_itemMarkers[ $app ][ $_key ]['item_app']		= $app;
			$this->_itemMarkers[ $app ][ $_key ]['item_key']		= $_key;
			$this->_itemMarkers[ $app ][ $_key ]['item_member_id']	= $this->memberData['member_id'];
			
			/* Make sure read IDs are unserialized */
			if ( isset( $this->_itemMarkers[ $app ][ $_key ]['item_read_array'] ) AND ! is_array( $this->_itemMarkers[ $app ][ $_key ]['item_read_array'] ) )
			{
				$this->_itemMarkers[ $app ][ $_key ]['item_read_array'] = unserialize( $this->_itemMarkers[ $app ][ $_key ]['item_read_array'] );
			}
			
			return $_key;
		}
		else
		{
			/* Mark markers as having changed */
			$this->_changesMade = TRUE;
			
			/* Make sure it contains the app & key */
			$this->_itemMarkers[ $app ][ $_key ]['item_app']		= $app;
			$this->_itemMarkers[ $app ][ $_key ]['item_key']		= $_key;
			$this->_itemMarkers[ $app ][ $_key ]['item_member_id']	= $this->memberData['member_id'];
			$this->_itemMarkers[ $app ][ $_key ]['item_read_array'] = array();
			$this->_itemMarkers[ $app ][ $_key ] = $data;
			
			IPSDebug::addMessage( "Item Marking Key returned! $_key" );

			return $_key;
		}
		
		/* Mark markers as having changed */
		$this->_changesMade = TRUE;
		
		/* Create a new key ... */
		$data['item_app']		 = $app;
		$data['item_key']		 = $_key;
		$data['item_member_id']	 = $this->memberData['member_id'];
		$data['item_read_array'] = array();
		
		$this->_itemMarkers[ $app ]          = array();
		$this->_itemMarkers[ $app ][ $_key ] = $data;
		
		return $_key;
	}
	
	/**
	 * Fetch module class
	 *
	 * @access	protected
	 * @param	string			App to fetch
	 * @return	object
	 */
	protected function _fetchModule( $app )
	{
		$app = ( $app ) ? $app : IPS_APP_COMPONENT;
		
		if ( isset( $this->_moduleApps[ $app ] ) && is_object( $this->_moduleApps[ $app ] ) )
		{
			return $this->_moduleApps[ $app ];
		}
		else
		{
			$_file = IPSLib::getAppDir( $app ) . '/extensions/coreExtensions.php';
			$_class = 'itemMarking__' . $app;
		
			if ( file_exists( $_file ) )
			{
				require_once( $_file );

				if( class_exists( $_class ) )
				{
					$this->_moduleApps[ $app ] = new $_class( $this->registry );
					
					return $this->_moduleApps[ $app ];
				}
				else
				{
					throw new Exception( "No itemMarking class available for $app" );
				}
			}
			else
			{
				throw new Exception( "No itemMarking class available for $app" );
			}
		}
	}

	/**
	 * Make a new key
	 *
	 * @access	protected
	 * @param   array
	 * @param	bool		Is cookie?
	 * @return	string
	 */
	protected function _makeKey( $data, $isCookie=FALSE )
	{
		/* Not interested in this for the main row */
		unset( $data['itemID'], $data['itemLastUpdate'] );
		
		return ( $isCookie === TRUE ) ? $data['item_app_key_1'] : md5( serialize( $data ) );
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
		$sessions   = array();
		$markers    = array();
		$_hoursAgo  = time() - ( 3600 * intval( $hours ) );
		$_limit	    = ( $limit ) ? array( 0, $limit ) : '';
		
		/* If we're not DB tracking, ignore... */
		if ( ! $this->settings['topic_marking_enable'] )
		{
			return FALSE;
		}
		
		/* Grab all active sessions */
		if ( $checkSessions === TRUE )
		{
			$this->DB->build( array( 'select' => 'id, member_id, running_time',
									 'from'   => 'sessions' ) );
								
			$this->DB->execute();
		
			while( $row = $this->DB->fetch() )
			{
				$sessions[ $row['member_id'] ] = $row;
			}
		}
		else if ( $this->memberData['member_id'] )
		{
			/* Make sure our row isn't interupted */
			$sessions[ $this->memberData['member_id'] ] = $this->memberData['member_id'];
		}
		
		/* Now grab 100 inactive markers */
		$this->DB->build( array( 'select' => 'item_member_id, item_last_updated, item_last_saved',
								 'from'   => 'core_item_markers_storage',
								 'where'  => 'item_last_saved < ' . $_hoursAgo,
								 'order'  => 'item_last_saved ASC',
								 'limit'  => $_limit ) );
								
		$this->DB->execute();
		
		while( $row = $this->DB->fetch() )
		{
			/* Not a current session? */
			if ( ! $sessions[ $row['item_member_id'] ] )
			{
				$markers[] = $row['item_member_id'];
			}
		}
		
		/* Now save 'em */
		if ( count( $markers ) )
		{
			$this->_save( $markers );
		}

		return intval( count( $markers ) );
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
		if ( ! $this->settings['topic_marking_enable'] )
		{
			return FALSE;
		}
		
		$tmpMarkers  = array();
		$markingData = array();
		
		if ( is_array( $memberIDs ) AND count( $memberIDs ) )
		{
			/* Load temp markers */
			$this->DB->build( array( 'select' => '*',
								 	 'from'   => 'core_item_markers_storage',
								     'where'  => 'item_member_id IN (' . implode( ',', $memberIDs ) . ')' ) );
								
			$this->DB->execute();
			
			while( $row = $this->DB->fetch() )
			{
				$markingData[ $row['item_member_id'] ] = $row;
			}
			
			/* Now loop through and process */
			foreach( $markingData as $memberID => $dbData )
			{
				$itemMarkers = unserialize( $dbData['item_markers'] );
				
				if ( is_array( $itemMarkers ) AND count( $itemMarkers ) )
				{
					foreach( $itemMarkers as $app => $data )
					{
						foreach( $itemMarkers[ $app ] as $key => $data )
						{
							if ( $key AND $app AND $memberID )
							{
								$row = array( 'item_key'          => $key,
											  'item_member_id'    => $memberID,
											  'item_app'	      => $app,
											  'item_last_saved'   => time(),
											  'item_read_array'   => ( is_array( $data['item_read_array'] ) ) ? serialize( $data['item_read_array'] ) : $data['item_read_array'],
											  'item_global_reset' => intval( $data['item_global_reset'] ),
											  'item_last_update'  => intval( $data['item_last_update'] )  );
			
								/* Overwrite $data with the values in $row */
								$new_array = array_merge( $data, $row );
								
								/* Somehow "member_id" column ends up in here... */
								if ( isset( $new_array['member_id'] ) )
								{
									unset( $new_array['member_id'] );
								}
								
								/* Weed out useless rows */
								if ( ! $new_array['item_global_reset'] AND ! $new_array['item_unread_count'] AND ! $new_array['item_read_array'] )
								{
									$this->DB->delete( 'core_item_markers', "item_key='{$key}' AND item_app='{$app}' AND item_member_id=" . $memberID );
									IPSDebug::addLogMessage( "Item Markers NOT saved", 'marker_skipped-' . $memberID, $new_array );
								}
								else
								{
									$this->DB->replace( 'core_item_markers', $new_array, array( 'item_key', 'item_app', 'item_member_id' ) );
								}
							}
						}
					}
				}
			}
			
			/* Now remove them */
			$this->DB->delete( 'core_item_markers_storage', 'item_member_id IN (' . implode( ',', $memberIDs ) . ')' );
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
		/* Task? */
		if ( IPS_IS_TASK === TRUE )
		{
			return;
		}
		
		/* Search engine? */
		if ( $this->member->is_not_human === TRUE )
		{
			return;
		}
		
		/* Save the storage table if not done so already */
		$this->_saveStorage();
		
		/* Grab sessions to be removed */
		if ( $this->settings['topic_marking_enable'] )
		{
			/* Check every 5 mins */
			$cache  = $this->caches['systemvars'];
			$update = 0;
			
			/* Fail safe */
			if ( ! $cache['itemMarkerClean'] )
			{
				$cache['itemMarkerClean'] = 0;
				$update                   = 1;
			}

			if ( time() - 300 > $cache['itemMarkerClean'] )
			{
				/* session_expiration is seconds */
				$_time = ceil( $this->settings['session_expiration'] / 3600 );
			
				/* less than an hour? */
				if ( $_time < 1 )
				{
					$_time = 1;
				}
				
				/* Clean 'em up */
				$_c = $this->manualCleanUp( 50, ++$_time, FALSE );
			
				if ( $_c )
				{
					IPSDebug::addLogMessage( "Manually cleaned up: " . $_c . ' hit by ID ' . $this->memberData['member_id'], 'markerClean' );
				}
				
				$update = 1;
			}
			
			/* Update */
			if ( $update )
			{
				/* Save Cache */
				$cache['itemMarkerClean'] = time();
				$this->cache->setCache( 'systemvars', $cache, array( 'array' => 1, 'donow' => 1, 'deletefirst' => 0 ) );
			}
		}
	}
	
	/**
	 * Update markers storage table
	 *
	 * @access	protected
	 * @return	bool
	 */
	protected function _saveStorage()
	{
		/* Already stored and no further updates? */
		if ( $this->_savedStorage )
		{
			return false;
		}
		
		/* Mark markers changed? */
		if ( $this->_changesMade !== TRUE )
		{
			return false;
		}
		
		/* Update storage table */
		if( ( $this->memberData['member_id'] AND $this->settings['topic_marking_enable'] ) AND count($this->_itemMarkers) AND is_array($this->_itemMarkers) )
		{ 
			$this->DB->replace( 'core_item_markers_storage', array( 'item_member_id'    => $this->memberData['member_id'],
																	'item_markers'      => serialize( $this->_itemMarkers ),
																	'item_last_saved'   => time() ), array( 'item_member_id' ) );
		}
		
		$this->_savedStorage = TRUE;
		
		return true;
	}
	
	/**
	 * Check to see if we need to save our markers. Or not.
	 * And then write them. If we need too. I'm sure you get what
	 * I mean.
	 * 
	 * @access	protected
	 * @return 	boolean		If writing was required
	 */
	protected function _checkThenWriteMarkers()
	{
		if ( ! $this->settings['topic_marking_enable'] )
		{
			return FALSE;
		}
		
		$_write = FALSE;
		
		if ( $this->settings['topic_marking_save_freq'] == 'page' )
		{
			$_write = TRUE;
		}
		else if ( strstr( $this->settings['topic_marking_save_freq'], 'min-' ) )
		{
			$_min = str_replace( 'min-', '', $this->settings['topic_marking_save_freq'] );
			
			if ( ( time() - $this->_lastSaved ) > ( $_min * 60 ) )
			{
				$_write = TRUE;
			}
		}
		
		/* Write, right? */
		if ( $_write === TRUE )
		{
			$this->writeMyMarkersToDB();
		}
		
		return $_write;
	}
	
	/**
	 * Build item markers.
	 * Ok, this function attempts to gather the corret item markers. 
	 * The function also loads data from the markers DB and attempts to build a 'merged' set of markers
	 * based on all the data it has.
	 *
	 * @access	protected
	 * @return	array
	 */
	protected function _generateIncomingItemMarkers()
	{
		$items = NULL;
		
		/* Not playing? */
		if ( ! $this->settings['topic_marking_enable'] )
		{
			return array();
		}
		
		/* Not a member */
		if ( ! $this->memberData['member_id'] )
		{
			return array();
		}
		
		/* Already got some data? */
		if ( $this->memberData['item_markers'] )
		{
			$items = ( is_array( $this->memberData['item_markers'] ) ) ? $this->memberData['item_markers'] : unserialize( $this->memberData['item_markers'] );
		}
		
		if ( ! is_array( $items ) OR ! count( $items ) )
		{
			/* Ensure they are written back correctly */
			$this->_changesMade = TRUE;
			
			$this->DB->build( array( 'select' => '*',
									 'from'   => 'core_item_markers',
									 'where'  => 'item_member_id=' . $this->memberData['member_id'] ) );

			$itemMarking = $this->DB->execute();

			while( $row = $this->DB->fetch( $itemMarking ) )
			{
				$items[ $row['item_app'] ][ $row['item_key'] ] = $row;
			}
		}
		
		/* Check data */
		if ( is_array( $items ) AND count( $items ) )
		{
			foreach( $items as $app => $item )
			{
				foreach( $item as $key => $_data )
				{
					if ( $app AND $key )
					{
						/* Ensure INT values */
						$items[ $app ][ $key ]['item_last_update']  = intval( $_data['item_last_update'] );
						$items[ $app ][ $key ]['item_global_reset'] = intval( $_data['item_global_reset'] );
						$items[ $app ][ $key ]['item_unread_count'] = intval( $_data['item_unread_count'] );
						$items[ $app ][ $key ]['item_member_id']    = intval( $_data['item_member_id'] );
						$items[ $app ][ $key ]['item_last_saved']   = intval( $_data['item_last_saved'] );
					
						/* Ensure read array is unserialized correctly */
						if ( isset( $_data['item_read_array'] ) AND ! is_array( $_data['item_read_array'] ) )
						{
							$items[ $app ][ $key ]['item_read_array'] = unserialize( $_data['item_read_array'] );
						}
						
						/* Now clean it up */
						if ( isset( $_data['item_read_array'] ) AND is_array( $items[ $app ][ $key ]['item_read_array'] ) )
						{
							/* Remove items that are older than the last marked time */
							if ( $items[ $app ][ $key ]['item_global_reset'] )
							{
								$this->_keyLastCleared = intval( $_data['item_global_reset'] );
								$items[ $app ][ $key ]['item_read_array'] = array_filter( $items[ $app ][ $key ]['item_read_array'], array( $this, 'arrayFilterRemoveAlreadyClearedItems' ) );
							}
						}
						else
						{
							$items[ $app ][ $key ]['item_read_array'] = array();
						}
					}
				}
			}
		}
		else
		{
			return array();
		}

		return $items;
	}
	
	/**
	 * Array sort Used to remove out of date topic marking entries
	 *
	 * @access	public
	 * @param	mixed
	 * @return	mixed
	 * @since	2.0
	 */
    public function arrayFilterRemoveAlreadyClearedItems( $var )
	{
		return $var > $this->_keyLastCleared;
	}
	
	/**
	 * Array sort Used to remove out of date topic marking entries
	 *
	 * @access	public
	 * @param	mixed
	 * @return	mixed
	 * @since	2.0
	 */
    public function arrayFilterCleanReadItems( $var )
	{
		return $var > $this->_dbCutOff;
	}
		
	/**
	 * Array sort Used to make sure there are no more than 50 last read items
	 *
	 * @access	public
	 * @param	mixed
	 * @return	mixed
	 * @since	2.0
	 */
    public function arrayFilterCookieItems( $var )
	{
		$this->_cookieCounter++;
		
		return ( $this->_cookieCounter <= 50 ) ? TRUE : FALSE;
	}

}