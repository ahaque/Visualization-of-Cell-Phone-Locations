<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Global Search
 * Last Updated: $Date: 2009-03-04 07:36:56 -0500 (Wed, 04 Mar 2009) $
 *
 * @author 		$Author: matt $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Core
 * @link		http://www.
 * @version		$Rev: 4135 $
 */ 

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class searchPluginIndexIndex implements iSearchIndexPlugin
{
	/**
	 * Database object
	 *
	 * @access	private
	 * @var		object
	 */			
	private $DB;
	
	/**
	 * Date range restriction start
	 *
	 * @access	private
	 * @var		integer
	 */		
	private $search_begin_timestamp = 0;
	
	/**
	 * Date range restriction end
	 *
	 * @access	private
	 * @var		integer
	 */		
	private $search_end_timestamp   = 0;

	/**
	 * Array of conditions for this search
	 *
	 * @access	private
	 * @var		array
	 */		
	private $whereConditions        = array();

	/**
	 * Apps to exclude
	 *
	 * @access	public
	 * @var		array
	 */		
	public $exclude_apps            = array();

	/**
	 * Setup registry objects
	 *
	 * @access	public
	 * @param	object	ipsRegistry $registry
	 * @return	void
	 */
	public function __construct( ipsRegistry $registry )
	{
		$this->DB     = $registry->DB();
		$this->member = $registry->member();
		$this->memberData =& $registry->member()->fetchMemberData();
	}
	
	/**
	 * Performs search and returns an array of results
	 *
	 * @access	public
	 * @param	string	$search_term
	 * @param	array	$limit_clause	The erray should be array( begin, end )
	 * @param	string	$sort_by		Column to sort by
	 * @param	string	$group_by		Column to group by
	 * @param	bool	$content_title_only	Only search title records
	 * @return	array
	 */	
	public function getSearchResults( $search_term, $limit_clause, $sort_by, $group_by='', $content_title_only=false )
	{				
		/* Do the search */
		$this->DB->build( $this->_buildSearchQueryArray( $search_term, $limit_clause, $sort_by, $group_by, false, $content_title_only ) );
		$this->DB->execute();
		
		/* Build result array */
		$rows = array();
		
		while( $r = $this->DB->fetch() )
		{
			$rows[] = $r;			
		}
		
		return $rows;
	}
	
	/**
	 * Performs live search and returns an array of results
	 * NOT AVAILABLE IN BASIC SEARCH
	 *
	 * @access	public
	 * @param	string	$search_term
	 * @return	array
	 */		
	public function getLiveSearchResults( $search_term )
	{
		if( ipsRegistry::$settings['live_search_disable'] )
		{
			return array();
		}
		
		/* Do the search */
		$this->DB->build( $this->_buildSearchQueryArray( $search_term, array( 0, 10 ), 'date' ) );
		$this->DB->execute();
		
		/* Build result array */
		$rows = array();
		
		while( $r = $this->DB->fetch() )
		{
			$rows[] = $r;			
		}
		
		return $rows;
	}	
	
	/**
	 * Returns the total number of results the search will return
	 *
	 * @access	public
	 * @param	string	$search_term		Search term
	 * @param	string	$group_by			Column to group by
	 * @param	bool	$content_title_only	Only search title records
	 * @return	integer
	 */	
	public function getSearchCount( $search_term, $group_by='', $content_title_only=false )
	{
		/* Query the count */
		$this->DB->build( $this->_buildSearchQueryArray( $search_term, array(), '', $group_by, true, $content_title_only ) );
		$this->DB->execute();
		
		/* Return the count */
		if( $group_by )
		{
			return $this->DB->getTotalRows();
		}
		else
		{
			$r = $this->DB->fetch();
			return $r['total_results'];
		}
	}
	
	/**
	 * Restrict the date range that the search is performed on
	 *
	 * @access	public
	 * @param	int		$begin	Start timestamp
	 * @param	int		[$end]	End timestamp
	 * @return	void
	 */
	public function setDateRange( $begin, $end=0 )
	{
		$this->search_begin_timestamp = $begin;
		$this->search_end_timestamp   = $end;
	}

	/**
	 * mySQL function for adding special search conditions
	 *
	 * @access	public
	 * @param	string	$column		sql table column for this condition
	 * @param	string	$operator	Operation to perform for this condition, ex: =, <>, IN, NOT IN
	 * @param	mixed	$value		Value to check with
	 * @param	string	$comp		Comparison type
	 * @return	void
	 */
	public function setCondition( $column, $operator, $value, $comp='AND' )
	{
		/* Build the condition based on operator */
		switch( strtoupper( $operator ) )
		{
			case 'IN':
			case 'NOT IN':
				$this->whereConditions[$comp][] = "i.{$column} {$operator} ( {$value} )";
			break;
			
			default:
				$this->whereConditions[$comp][] = "i.{$column} {$operator} {$value}";
			break;
		}
	}
	
	/**
	 * Allows you to specify multiple conditions that are chained together
	 *
	 * @access	public
	 * @param	array	$conditions	Array of conditions, each element has 3 keys: column, operator, value, see the setCondition function for information on each
	 * @param	string	$inner_comp	Comparison operator to use inside the chain
	 * @param	string	$outer_comp	Comparison operator to use outside the chain
	 * @return	void
	 */
	public function setMultiConditions( $conditions, $inner_comp='OR', $outer_comp='AND' )
	{
		/* Loop through the conditions to build the statement */
		$_temp = array();
		
		foreach( $conditions as $r )
		{
			switch( strtoupper( $r['operator'] ) )
			{
				case 'IN':
				case 'NOT IN':
					$_temp[] = "i.{$r['column']} {$r['operator']} ( {$r['value']} )";
				break;
				
				default:
					$_temp[] = "i.{$r['column']} {$r['operator']} {$r['value']}";
				break;
			}
		}
		
		$this->whereConditions[$outer_comp][] = '( ' . implode( $_temp, ' ' . $inner_comp . ' ' ) . ' ) ';
	}
	
	/**
	 * Reassigns fields in a way the index exepcts
	 *
	 * @param  array  $r
	 * @return array
	 **/
	public function formatFieldsForIndex( $r )
	{
		
	}
	
	/**
	 * This function grabs the actual results for display
	 *
	 * @param  array  $ids
	 * @return query result
	 **/
	public function getResultsForSphinx( $ids )
	{
		
	}
	
	/**
	 * Builds an array that can be used in build
	 *
	 * @access	public
	 * @param	string	$search_term
	 * @param	array	$limit_clause			The erray should be array( begin, end )
	 * @param	string	$sort_by				Either relevance or date
	 * @param	string	[$group_by]				Field to group on
	 * @param	bool	[$count_only]			Set to true for a count(*) query
	 * @param	bool	[$content_title_only]	Set to true to only search titles
	 * @return	array
	 */
	private function _buildSearchQueryArray( $search_term, $limit_clause, $sort_by, $group_by='', $count_only=false, $content_title_only=false )
	{
		/* Do we only need to count results? */
		if( $count_only )
		{
			if( $group_by )
			{
				$group_by = 'i.' . $group_by;	
			}
						
			$search_query_array = array(
										'select'   => 'COUNT(*) as total_results',
										'from'     => array( 'search_index' => 'i' ),
										'where'    => $this->_buildWhereStatement( $search_term, $content_title_only ),
										'group'    => $group_by,
										'add_join' => array(
															array(
																	'from'   => array( 'permission_index' => 'p' ),
																	'where'  => 'p.perm_type_id=i.type_id AND p.perm_type=i.type',
																	'type'   => 'left',
																),
															array(
																	'from'   => array( 'profile_friends' => 'friend' ),
																	'where'  => 'friend.friends_member_id=i.member_id AND friend.friends_friend_id=' . $this->memberData['member_id'],
																	'type'   => 'left',
																),																
															)
										);
		}
		else
		{
			/* Sort By */
			if( isset( $sort_by ) && in_array( $sort_by, array( 'date', 'relevance', 'ascdate' ) ) )
			{
				$_ord  = strstr( $sort_by, 'asc' ) ? 'ASC' : 'DESC';
				$order = $sort_by == 'date'        ? 'i.updated ' . $_ord : 'ranking' . $_ord;
			}
			else
			{
				$order = 'ranking DESC';
			}
			
			/* If there is no search term, we need to force search by updated */
			if( ! $search_term )
			{
				$order = 'i.updated DESC';
			}
			
			if( $group_by )
			{
				$group_by = 'i.' . $group_by;	
			}
			
			if( $content_title_only )
			{
				$ranking_select = $search_term ? ", MATCH( i.content_title ) AGAINST( '{$search_term}' IN BOOLEAN MODE ) as ranking" : '';
			}
			else
			{
				$ranking_select = $search_term ? ", MATCH( i.content, i.content_title ) AGAINST( '{$search_term}' IN BOOLEAN MODE ) as ranking" : '';
			}
			
			/* Build the search query array */
			$search_query_array = array(
											'select'   => "i.*{$ranking_select}",
											'from'     => array( 'search_index' => 'i' ),
											'where'    => $this->_buildWhereStatement( $search_term, $content_title_only ),
											'limit'    => $limit_clause,
											'group'    => $group_by,
											'order'    => $order,
											'add_join' => array(
																array(
																		'select' => 'p.perm_view',
																		'from'   => array( 'permission_index' => 'p' ),
																		'where'  => 'p.perm_type_id=i.type_id AND p.perm_type=i.type',
																		'type'   => 'left',
																	),
																array(
																		'select' => 'm.members_display_name, m.member_group_id, m.mgroup_others',
																		'from'   => array( 'members' => 'm' ),
																		'where'  => 'i.member_id=m.member_id',
																		'type'   => 'left',
																	),
																array(
																		'from'   => array( 'profile_friends' => 'friend' ),
																		'where'  => 'friend.friends_member_id=i.member_id AND friend.friends_friend_id=' . $this->memberData['member_id'],
																		'type'   => 'left',
																	),
																)
									);
		}
		
		/* Loop through all the search plugins and let them modify the search query */
		foreach( ipsRegistry::$applications as $app )
		{
			if( IPSSearchIndex::appisSearchable( $app['app_directory'] ) )
			{
				if( ! isset( $this->display_plugins[ $app['app_directory'] ] ) || ! $this->display_plugins[ $app['app_directory'] ] )
				{
					require_once( IPSLib::getAppDir( $app['app_directory'] ) . '/extensions/searchDisplay.php' );
					$_class = $app['app_directory'] . 'SearchDisplay';
					
					$this->display_plugins[ $app['app_directory'] ] = new $_class();
				}
				
				if( method_exists( $this->display_plugins[ $app['app_directory'] ] , 'modifySearchQuery' ) )
				{
					/* Get the modified query */
					$new_query = $this->display_plugins[ $app['app_directory'] ]->modifySearchQuery( $search_query_array, $count_only );
					
					/* Simple check to prevent accidentaly breaking the query, clearly not fool proof :) */
					$search_query_array = is_array( $new_query ) && count( $new_query ) ? $new_query : $search_query_array;
				}
			}
		}
		
		return $search_query_array;		
	}
	
	/**
	 * Builds the where portion of a search string
	 *
	 * @access	private
	 * @param	string	$search_term		The string to use in the search
	 * @param	bool	$content_title_only	Search only title records
	 * @return	string
	 **/
	private function _buildWhereStatement( $search_term, $content_title_only=false )
	{		
		/* INI */
		$where_clause = array();
		if( $search_term )
		{
			/* Main Search Statement */
			if( $content_title_only )
			{
				$where_clause[] = "MATCH( i.content_title ) AGAINST( '$search_term' IN BOOLEAN MODE )";
			}
			else
			{
				$where_clause[] = "MATCH( i.content, i.content_title ) AGAINST( '$search_term' IN BOOLEAN MODE )";
			}
		}		
		
		/* Exclude Apps */
		if( count( $this->exclude_apps ) )
		{
			$where_clause[] = 'i.app NOT IN ( ' . implode( ',', $this->exclude_apps ) . ' )';
		}

		/* Exclude some items */
		if( !$this->memberData['g_is_supmod'] )
		{
			/* Owner only */
			$where_clause[] = '(p.owner_only=0 OR i.member_id=' . $this->memberData['member_id'] . ')';
			
			/* Friend only */
			$where_clause[] = '(p.friend_only=0 OR friend.friends_id ' . $this->DB->buildIsNull( false ) . ')';
			
			/* Authorized users only */
			$where_clause[] = '(p.authorized_users ' . $this->DB->buildIsNull() . ' OR i.member_id=' . $this->memberData['member_id'] . " OR p.authorized_users LIKE '%," . $this->memberData['member_id'] . ",%')";
		}

		/* Date Restrict */
		if( $this->search_begin_timestamp && $this->search_end_timestamp )
		{
			$where_clause[] = "i.updated BETWEEN {$this->search_begin_timestamp} AND {$this->search_end_timestamp}";
		}
		else
		{
			if( $this->search_begin_timestamp )
			{
				$where_clause[] = "i.updated > {$this->search_begin_timestamp}";
			}
			
			if( $this->search_end_timestamp )
			{
				$where_clause[] = "i.updated < {$this->search_end_timestamp}";
			}
		}
		
		/* Add in AND where conditions */
		if( isset( $this->whereConditions['AND'] ) && count( $this->whereConditions['AND'] ) )
		{
			$where_clause = array_merge( $where_clause, $this->whereConditions['AND'] );
		}
		
		/* ADD in OR where conditions */
		if( isset( $this->whereConditions['OR'] ) && count( $this->whereConditions['OR'] ) )
		{
			$where_clause[] = '( ' . implode( ' OR ', $this->whereConditions['OR'] ) . ' )';
		}

		/* Permissions */
		$where_clause[] = $this->DB->buildRegexp( "p.perm_view", $this->member->perm_id_array );
			
		/* Build and return the string */
		return implode( " AND ", $where_clause );
	}
}