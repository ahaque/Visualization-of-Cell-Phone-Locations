<?php
/**
 * Invision Power Services
 * Basic CCS Pages Search
 * Last Updated: $Date: 2009-08-11 10:01:08 -0400 (Tue, 11 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	IP.CCS
 * @link		http://www.
 * @since		1st March 2009
 * @version		$Rev: 42 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class searchCcsPlugin implements iSearchIndexPlugin
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
	 * Remoe self from search results
	 *
	 * @access	public
	 * @var		boolean
	 */		
	public $removeSelf				= false;
	
	/**
	 * Only display title
	 *
	 * @access	public
	 * @var		boolean
	 */		
	public $onlyTitles				= false;

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
		$this->DB->build( array( 
								'select'   => "*",
								'from'	   => 'ccs_pages',
 								'where'	   => $this->_buildWhereStatement( $search_term, $content_title_only ),
 								'group'    => $group_by,
								'limit'    => $limit_clause,
								'order'    => "page_last_edited DESC",												
						)	);
		$this->DB->execute();

		/* Build result array */
		$rows = array();

		while( $r = $this->DB->fetch() )
		{
			/* Reassign stuff to match the search_index */
			$rows[] = $this->formatFieldsForIndex( $r );
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
		return array();
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
		$this->DB->build( array( 
								'select'   => 'COUNT(*) as total_results',
								'from'	   => 'ccs_pages',
 								'where'	   => $this->_buildWhereStatement( $search_term, $content_title_only ),
 								'group'    => $group_by,													
						)		);
		$this->DB->execute();

		$search = $this->DB->fetch();
		
		/* Return the count */
		return $search['total_results'];
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
		/* Remap */
		$column = $column == 'content_title'	? 'page_name'		: $column;
		
		if( $column == 'app' )
		{
			return;
		}
				
		/* Build the condition based on operator */
		switch( strtoupper( $operator ) )
		{
			case 'IN':
			case 'NOT IN':
				$this->whereConditions[$comp][] = "{$column} {$operator} ( {$value} )";
			break;
			
			default:
				$this->whereConditions[$comp][] = "{$column} {$operator} {$value}";
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
			/* REMAP */
			$r['column'] = ( $r['column'] == 'content_title' ) ? 'page_name' : $r['column'];
			
			if( $r['column'] == 'app' )
			{
				continue;
			}
						
			switch( strtoupper( $r['operator'] ) )
			{
				case 'IN':
				case 'NOT IN':
					$_temp[] = "{$r['column']} {$r['operator']} ( {$r['value']} )";
				break;
				
				default:
					$_temp[] = "{$r['column']} {$r['operator']} {$r['value']}";
				break;
			}
		}
		
		$this->whereConditions[$outer_comp][] = '( ' . implode( $_temp, ' ' . $inner_comp . ' ' ) . ' ) ';
	}	
	
	/**
	 * Set search conditions for "View unread content"
	 *
	 * @access	public
	 * @return	void
	 */
	public function setUnreadConditions()
	{
		$this->whereConditions['AND'][]	= "page_last_edited > {$this->memberData['last_visit']}";
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
			if( $content_title_only )
			{
				$where_clause[] = "page_name LIKE '%{$search_term}%'";
			}
			else
			{
				$where_clause[] = "page_name LIKE '%{$search_term}%' || page_content LIKE '%{$search_term}%'";
			}
		}
		
		/* Permissions */
		$where_clause[]	= "(page_view_perms='*' OR " . $this->DB->buildRegexp( "page_view_perms", $this->member->perm_id_array ) . ")";
		
		/* Date Restrict */
		if( $this->search_begin_timestamp && $this->search_end_timestamp )
		{
			$where_clause[] = $this->DB->buildBetween( "page_last_edited", $this->search_begin_timestamp, $this->search_end_timestamp );
		}
		else
		{
			if( $this->search_begin_timestamp )
			{
				$where_clause[] = "page_last_edited > {$this->search_begin_timestamp}";
			}
			
			if( $this->search_end_timestamp )
			{
				$where_clause[] = "page_last_edited < {$this->search_end_timestamp}";
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

		/* Build and return the string */
		return implode( " AND ", $where_clause );
	}	
	
	/**
	 * Reassigns fields in a way the index exepcts
	 *
	 * @param  array  $r
	 * @return array
	 **/
	public function formatFieldsForIndex( $r )
	{
		$r['app']                 = 'ccs';
		$r['content']             = $r['page_content'];
		$r['content_title']       = $r['page_name'];
		$r['updated']             = $r['page_last_edited'];
		$r['type_2']              = 'page';
		$r['type_id_2']           = $r['page_id'];
		$r['misc']                = serialize(array());

		return $r;
	}
	
	/**
	 * This function grabs the actual results for display
	 *
	 * @param  array  $ids
	 * @return query result
	 **/
	public function getResultsForSphinx( $ids )
	{
		$this->DB->build( array( 
								'select'   => "*",
								'from'	   => 'ccs_pages',
 								'where'	   => 'page_id IN(' . implode( ',', $ids ) . ')',
								'order'    => "page_last_edited DESC",												
						)	);

		return $this->DB->execute();
	}
	
	/**
	 * Gets the name of the field this search index uses for dates.... >_<
	 *
	 * @return string
	 **/
	public function getDateField()
	{
		return 'page_last_edited';
	}	
}