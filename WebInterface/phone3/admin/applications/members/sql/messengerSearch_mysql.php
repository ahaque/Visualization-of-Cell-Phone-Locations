<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Generates a search and returns the results [ MySQL ]
 * Last Updated: $Date: 2009-03-25 16:02:23 -0400 (Wed, 25 Mar 2009) $
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Members
 * @link		http://www.
 * @version		$Rev: 4309 $ 
 **/

class messengerSearch
{
	/**
	 * Results
	 *
	 * @access	private
	 * @var		array
	 */
	private $_results = array();
	
	/**
	 * Total rows
	 *
	 * @access	private
	 * @var		int
	 */
	private $_rows = 0;
	
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
		$this->registry = $registry;
		$this->DB       = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->lang     = $this->registry->getClass('class_localization');
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache    = $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
	}
	
	/**
	 * Search
	 *
	 * @access	public
	 * @param	int			Member ID who is searching
	 * @param	string		Words to search (probably tainted at this point, so be careful!)
	 * @param	int			Offset start
	 * @param	int			Number of results to return
	 * @param	array 		Array of folders to search (send nothing to search all)
	 * @return 	boolean
	 */
	public function execute( $memberID, $words, $start=0, $end=50, $folders=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$ids      = array();
		$words    = addslashes( $words );
		$start    = intval( $start );
		$end      = intval( $end );
		$memberID = intval( $memberID );
		$results  = array();
		$dbpre    = ips_DBRegistry::getPrefix();
		
		/* Do it... */
		if ( $words )
		{
			$this->DB->allow_sub_select = 1;
			
			$this->DB->query( "SELECT SQL_CALC_FOUND_ROWS mt_id, mt_first_msg_id FROM ( ( SELECT t.mt_id, t.mt_first_msg_id
									FROM {$dbpre}message_topics t, {$dbpre}message_topic_user_map m
									WHERE (t.mt_id=m.map_topic_id AND m.map_user_id=" . $memberID . " AND m.map_user_banned=0) AND MATCH( t.mt_title ) AGAINST( '$words' IN BOOLEAN MODE )
									ORDER BY t.mt_last_post_time DESC )
								UNION
								( SELECT p.msg_topic_id, p.msg_id
									FROM {$dbpre}message_posts p, {$dbpre}message_topic_user_map m
									WHERE (p.msg_topic_id=m.map_topic_id AND m.map_user_id=" . $memberID . " AND m.map_user_banned=0) AND MATCH( p.msg_post ) AGAINST( '$words' IN BOOLEAN MODE )
									ORDER BY p.msg_date DESC ) ) as tbl
								GROUP BY mt_id
								LIMIT $start, $end" );
								
			while( $row = $this->DB->fetch() )
			{
				$ids[] = $row['mt_id'];
			}
			
			$this->DB->query( "SELECT FOUND_ROWS() as row_your_boat" );
			$row = $this->DB->fetch();
			
			/* Set rows var */
			$this->_rows = intval( $row['row_your_boat'] ); // comic genius
			
			$this->DB->allow_sub_select = 0;
			
			/* Now fetch some actual data! */
			if ( count( $ids ) )
			{
				$this->DB->build( array( 'select'   => 't.*',
										 'from'     => array( 'message_topics' => 't' ),
										 'where'    => 'mt_id IN (' . implode( ",", $ids ) . ')',
										 'order'    => 't.mt_last_post_time DESC',
										 'add_join' => array( array( 'select' => 'map.*',
																	 'from'   => array( 'message_topic_user_map' => 'map' ),
																	 'where'  => 'map.map_topic_id=t.mt_id',
																	 'type'   => 'left' ),
															  array( 'select' => 'p.*',
																	 'from'   => array( 'message_posts' => 'p' ),
																	 'where'  => 'p.msg_id=t.mt_first_msg_id',
																	 'type'   => 'left' ) ) ) );
				$this->DB->execute();
				
				while ( $row = $this->DB->fetch() )
				{
					$results[ $row['mt_id'] ] = $row;
				}
				
				$this->_results = $results;
			}
		}
	}

	/**
	 * Fetch results
	 *
	 * @access	public
	 * @return	array
	 */
	public function fetchResults()
	{
		return ( is_array( $this->_results ) ) ? $this->_results : array();
	}
	
	/**
	 * Fetch total result row count
	 *
	 * @access	public
	 * @return	int
	 */
	public function fetchTotalRows()
	{
		return intval( $this->_rows );
	}
}