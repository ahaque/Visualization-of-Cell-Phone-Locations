<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Global Search
 * Last Updated: $Date: 2009-07-20 19:34:24 -0400 (Mon, 20 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Core
 * @link		http://www.
 * @version		$Rev: 4915 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class searchPluginSphinxIndex implements iSearchIndexPlugin
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
	 * Sphinx client object
	 *
	 * @access	public
	 * @var		object
	 */		
	public $sphinxClient;
	
	/**
	 * Search plugin for the application
	 *
	 * @access	public
	 * @var		object
	 */	
	public $appSearchPlugin;

	/**
	 * Setup registry objects
	 *
	 * @access	public
	 * @param	object	ipsRegistry $registry
	 * @return	void
	 */
	public function __construct( ipsRegistry $registry )
	{
		$this->DB			=  $registry->DB();
		$this->member		=  $registry->member();
		$this->memberData	=& $registry->member()->fetchMemberData();
		$this->settings		=  $registry->settings();
		$this->request		=  $registry->request();
		
		/* Do we have the sphinxes? */
		if( ! file_exists( 'sphinxapi.php' ) )
		{
			$registry->output->showError( 'sphinx_api_missing', 10182 );	
		}
		
		/* Load Sphinx */
		require( 'sphinxapi.php' );
		$this->sphinxClient = new SphinxClient();
		
		$this->sphinxClient->SetServer( $this->settings['search_sphinx_server'], intval( $this->settings['search_sphinx_port'] ) );
		$this->sphinxClient->SetMatchMode( SPH_MATCH_EXTENDED );
		$this->sphinxClient->SetLimits( 0, 1000 );
		
		/* We're going to need the regular app index plugin also */
		require_once( IPSLib::getAppDir( ipsRegistry::$request[ 'search_app' ] ) . '/extensions/searchPlugin.php' );
		$searchApp = 'search' . ucfirst( $this->request['search_app'] ) . 'Plugin';
		$this->appSearchPlugin = new $searchApp( $registry );
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
		$results = $this->_searchQuery( $search_term, $limit_clause, $sort_by, $group_by, false, $content_title_only );
		
		/* Build result array */
		$rows = array();
	
		if( is_array( $results ) && count( $results ) )
		{
			$q = $this->appSearchPlugin->getResultsForSphinx( $results );
			
			while( $r = $this->DB->fetch( $q ) )
			{
				/* Reassign stuff to match the search_index */
				$rows[] = $this->appSearchPlugin->formatFieldsForIndex( $r );
			}			
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
		/* Return the count */
		return $this->_searchQuery( $search_term, array(), '', '', true, $content_title_only );
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
		$this->sphinxClient->SetFilterRange( $this->appSearchPlugin->getDateField(), $begin, $end );
	}
	
	/**
	 * Set search conditions for "View unread content"
	 *
	 * @access	public
	 * @return	void
	 */
	public function setUnreadConditions()
	{
		$this->setDateRange( intval( $this->memberData['last_visit'] ), time() );
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
		/* This is restricted by the indexes searched */
		if( $column == 'app' )
		{
			return;
		}
		
		$column = $this->appSearchPlugin->getConditionField( $column );

		if( !$column )
		{
			return;
		}

		/* Build the condition based on operator */
		switch( strtoupper( $operator ) )
		{
			case 'IN':		
				$this->sphinxClient->setFilter( $column, explode( ',', $value ) );
			break;
			
			case 'NOT IN':
				$this->sphinxClient->setFilter( $column, explode( ',', $value ), TRUE );
			break;
			
			case '=':
				$this->sphinxClient->setFilter( $column, array( $value ) );
			break;
			
			case '!=':
			case '<>':
				$this->sphinxClient->setFilter( $column, array( $value ), TRUE );
			break;
			
			default:
				echo "<b>Unhandled sphinx search operator: {$operator}</b><br />";
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
		//echo "<b>setMultiCondidion</b> should not be used in sphinx<br />";
		return;
	}
	
	/**
	 * Does search
	 *
	 * @access	private
	 * @param	string	$search_term
	 * @param	array	$limit_clause	The erray should be array( begin, end )
	 * @param	string	$sort_by		Either relevance or date
	 * @param	string	[$group_by]		Field to group on
	 * @param	bool	[$count_only]	Set to true for a count(*) query
	 * @param	bool	[$content_title_only]	Only search titles
	 * @return	array
	 **/
	private function _searchQuery( $search_term, $limit_clause, $sort_by, $group_by='', $count_only=false, $content_title_only=false )
	{
		/* Do we only need to count results? */
		if( ! $count_only )
		{
			if( $limit_clause[1] )
			{
				/* Limit Results */
				$this->sphinxClient->SetLimits( intval($limit_clause[0]), intval($limit_clause[1]) );
			}
			else if( $limit_clause[0] )
			{
				$this->sphinxClient->SetLimits( 0, intval($limit_clause[0]) );
			}
						
			/* Sort By */
			if( isset( $sort_by ) && in_array( $sort_by, array( 'date', 'relevance' ) ) )
			{
				if( $sort_by == 'date' )
				{
					if( $this->request['search_sort_order'] == 0 )
					{
						$this->sphinxClient->SetSortMode( SPH_SORT_ATTR_DESC, $this->appSearchPlugin->getDateField() /* Sigh */ );
					}
					else
					{
						$this->sphinxClient->SetSortMode( SPH_SORT_ATTR_ASC, $this->appSearchPlugin->getDateField() /* Sigh */ );
					}
				}
				else
				{
					$this->sphinxClient->SetSortMode( SPH_SORT_RELEVANCE );
				}
			}
			else
			{
				$this->sphinxClient->SetSortMode( SPH_SORT_RELEVANCE );
			}
		}
		
		/* Exclude Apps */
		if( count( $this->exclude_apps ) )
		{
			$app_id_exclude = array();
			foreach( $this->exclude_apps as $app_dir )
			{
				$app_id_exclude[] = ipsRegistry::$applications[$app_dir]['app_id'];
			}

			$this->sphinxClient->SetFilter( 'app', $app_id_exclude, TRUE );
		}
		
		/* Permissions */
		$perm_array = $this->member->perm_id_array;
		$perm_array[] = 0;
		
		/* Need to remove empty values... */
		$final_perms	= array();
		
		foreach( $perm_array as $perm_id )
		{
			if( is_numeric( $perm_id ) )
			{
				$final_perms[]	= $perm_id;
			}
		}

		$this->sphinxClient->SetFilter( 'perm_view', $final_perms );

		/* Exclude some items */
		if( ! $this->memberData['g_is_supmod'] )
		{
			/* Owner only */
			$this->sphinxClient->SetFilter( 'owner_only', array( 0, $this->memberData['member_id'] ) );
			
			/* Friend only */
			$this->DB->build( array(
									'select' => 'friends_member_id',
									'from'   => 'profile_friends',
									'where'  => "friends_friend_id={$this->memberData['member_id']}"
							)	);
			$this->DB->execute();
			
			$friends_ids = array( 0 );
			while( $r = $this->DB->fetch() )
			{
				$friends_ids[] = $r['friends_member_id'];
			}
			
			$this->sphinxClient->SetFilter( 'friend_only', $friends_ids );
			
			/* Authorized users only */
			$this->sphinxClient->SetFilter( 'authorized_users', array( 0, $this->memberData['member_id'] ) );
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
				
				$this->display_plugins[ $app['app_directory'] ]->search_plugin	= $this->appSearchPlugin;
				
				if( method_exists( $this->display_plugins[ $app['app_directory'] ], 'modifySearchQuery' ) )
				{
					/* Get the modified query */
					$this->display_plugins[ $app['app_directory'] ]->modifySearchQuery( $this->sphinxClient, $count_only );
				}
			}
		}

		$groupby	= $this->request['show_as_titles'] ? true : false;
		
		/* Perform the search */
		if( method_exists( $this->display_plugins[ $this->request['search_app'] ], 'runSearchQuery' ) )
		{
			$result = $this->display_plugins[ $this->request['search_app'] ]->runSearchQuery( $this->sphinxClient, $search_term, $groupby );
		}
		else
		{
			if( $groupby )
			{
				$this->sphinxClient->SetGroupBy( 'search_id', SPH_GROUPBY_ATTR, '@group DESC');
			}

			$result = $this->sphinxClient->Query( $search_term, $this->request['search_app'] . '_search_main,' . $this->request['search_app'] . '_search_delta' );
		}

		/* Return the total number of results */
		if( $count_only )
		{
			return $result['total'];
		}
		/* Return the results */
		else
		{
			$search_ids = array();
			
			if( is_array( $result['matches'] ) && count( $result['matches'] ) )
			{
				foreach( $result['matches'] as $res )
				{
					$search_ids[] = $res['attrs']['search_id'];
				}
			}

			return $search_ids;
		}
	}
	
	/**
	 * Reassigns fields in a way the index exepcts
	 *
	 * @param  array  $r
	 * @return array
	 **/
	public function formatFieldsForIndex( $r )
	{
		// Blank
	}
	
	/**
	 * This function grabs the actual results for display
	 *
	 * @param  array  $ids
	 * @return query result
	 **/
	public function getResultsForSphinx( $ids )
	{
		// Blank
	}
	
	/**
	 * Get whether or not we're showing as forum or not
	 *
	 * @param	public
	 * @return	bool
	 */
	public function getShowAsForum()
	{
		if( method_exists( $this->appSearchPlugin, 'getShowAsForum' ) )
		{
			return $this->appSearchPlugin->getShowAsForum();
		}
		else
		{
			return false;
		}
	}
}