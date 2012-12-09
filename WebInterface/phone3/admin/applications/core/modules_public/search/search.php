<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Global Search
 * Last Updated: $Date: 2009-09-01 11:00:43 -0400 (Tue, 01 Sep 2009) $
 *
 * @author 		$Author: matt $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Core
 * @link		http://www.
 * @version		$Rev: 5071 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_core_search_search extends ipsCommand
{
	/**
	 * Generated output
	 *
	 * @access	private
	 * @var		string
	 */		
	private $output			= '';
	
	/**
	 * Page Title
	 *
	 * @access	private
	 * @var		string
	 */		
	private $title			= '';
	
	/**
	 * Object to handle searches
	 *
	 * @access	private
	 * @var		string
	 */	
	private $search_plugin	= '';
	
	/**
	 * Topics array
	 *
	 * @access	private
	 * @var		array
	 */
	private	$_topicArray	= array();

	/**
	 * Class entry point
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	void		[Outputs to screen/redirects]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Basic Search */
		if( isset( $this->request['search_filter_app'] ) && is_array( $this->request['search_filter_app'] ) )
		{
			foreach( $this->request['search_filter_app'] as $app => $checked )
			{
				/* Bypass the all filter */
				if( $app == 'all' )
				{
					$this->request['search_app'] 					= 'forums';
					$this->request['search_filter_app']['forums']	= 1;
					break;
				}
				
				/* Add to the array */
				if( $checked )
				{
					$this->request['search_app'] = $app;
					break;
				}
			}
		}

		$this->request['search_app'] = $this->request['search_app'] ? $this->request['search_app'] : 'forums';

		/* Load Search Plugin */
		try
		{
			/* If it's not a search operation, like new content or user post for example, we use mysql instead of sphinx */
			if( $this->request['do'] != 'quick_search' )
			{
				$this->search_plugin = IPSSearchIndex::getSearchPlugin( 'index' );
			}
			else
			{
				$this->search_plugin = IPSSearchIndex::getSearchPlugin();				
			}
		}
		catch( Exception $error )
		{
			switch( $error->getMessage() )
			{
				case 'INVALID_BASIC_SEARCH_PLUGIN_FILE':
					$this->registry->output->showError( 'search_invalid_plugin', 10140 );
				break;
				
				case 'INVALID_BASIC_SEARCH_PLUGIN_CLASS':
					$this->registry->output->showError( 'search_plugin_class', 10141, true );
				break;
				
				case 'INVALID_INDEX_PLUGIN_FILE':
					$this->registry->output->showError( 'search_plugin_file', 10142 );
				break;
				
				case 'INVALID_INDEX_PLUGIN_CLASS':
					$this->registry->output->showError( 'search_plugin_class', 10143, true );
				break;
			}
		}
		
		/* Check Access */
		$this->_canSearch();		
		
		/* Load language */
		$this->registry->class_localization->loadLanguageFile( array( 'public_search' ), 'core' );
		$this->registry->class_localization->loadLanguageFile( array( 'public_forums' ), 'forums' );
		
		/* What to do */
		switch( $this->request['do'] )
		{
			case 'active':
				$this->activeContent();
			break;
			
			case 'user_posts':
				$this->viewUserContent();
			break;

			case 'new_posts':
				$this->viewNewPosts();
			break;
			
			case 'quick_search':
				$this->searchResults();
			break;
			
			default:
			case 'search_form':	
				$this->searchAdvancedForm();
			break;
		}
		
		/* If we have any HTML to print, do so... */
		$this->registry->output->setTitle( $this->title );
		$this->registry->output->addContent( $this->output );
		$this->registry->output->sendOutput();
	}
	
	/**
	 * Builds the advanced search form
	 *
	 * @access	public
	 * @param	string	Message
	 * @return	void
	 */
	public function searchAdvancedForm( $msg='', $removed_search_terms=array() )
	{
		/* Get any application specific filters */
		$filters_html = '';
		
		foreach( ipsRegistry::$applications as $app )
		{
			if( IPSSearchIndex::appisSearchable( $app['app_directory'] ) )
			{
				require_once( IPSLib::getAppDir( $app['app_directory'] ) . '/extensions/searchDisplay.php' );
				$_class = $app['app_directory'] . 'SearchDisplay';
				
				if( class_exists( $_class ) )
				{
					$search_display_plugin = new $_class();				
					$filters_html .= $search_display_plugin->getFilterHTML();
				}
			}
		}
		
		/* Output */
		$this->title   = $this->lang->words['search_form'];
		$this->registry->output->addNavigation( $this->lang->words['search_form'], '' );
		$this->output .= $this->registry->output->getTemplate( 'search' )->searchAdvancedForm( $filters_html, $msg, $removed_search_terms );
	}
	
	/**
	 * Processes a search request
	 *
	 * @access	public
	 * @return	void
	 */
	public function searchResults()
	{
		/* Search Term */
		$asForum		= ( method_exists( $this->search_plugin, 'getShowAsForum' ) ) ? $this->_getShowAsForum() : false;
		$search_term	= str_replace( "&quot;", '"',  IPSText::parseCleanValue( rawurldecode( $this->request['search_term'] ) ) );
		$search_term	= str_replace( "&amp;", '&',  $search_term );
		$removedTerms	= array();
		
		/* Did we come in off a post request? */
		if ( $this->request['request_method'] == 'post' )
		{
			/* Set a no-expires header */
			$this->registry->getClass('output')->setCacheExpirationSeconds( 30 * 60 );
		}
		
		/* Sort some form elements out */
		$this->request['search_sort_by']    = ( $this->request['search_sort_by']    && $this->request['search_sort_by']    != 'date' ) ? 'relevance' : 'date';
		$this->request['search_sort_order'] = ( $this->request['search_sort_order'] && $this->request['search_sort_order'] != 'desc' ) ? 'asc' : 'desc';
		
		/* Check for disallowed search terms */
		while( preg_match_all( "/(?:^|\s+)(img|quote|code|html|javascript|a href|color|span|div|border|style)(?:\s+|$)/", $search_term, $removed_search_terms ) )
		{
			$removedTerms[]	= $removed_search_terms[0][0];
			$search_term	= preg_replace( "/(?:^|\s+)(?:img|quote|code|html|javascript|a href|color|span|div|border|style)(?:\s+|$)/", '', $search_term );
		}		
		
		/* Remove some formatting */
		$search_term = str_replace( array( '|', '\\', '/' ), '', $search_term );
		
		if( ( $this->settings['min_search_word'] && strlen( $search_term ) < $this->settings['min_search_word'] ) && ! $this->request['search_author'] )
		{
			$this->searchAdvancedForm( sprintf( $this->lang->words['search_term_short'], $this->settings['min_search_word'] ), $removedTerms );
			return;
		}
		
		/* Save date for the form */
		$this->request['_search_date_start'] = $this->request['search_date_start'];
		$this->request['_search_date_end']   = $this->request['search_date_end'];

		/* Default End Date */
		if( $this->request['search_date_start'] && ! $this->request['search_date_end'] )
		{
			$this->request['search_date_end'] = 'now';
		}

		/* Do some date checking */
		if( strtotime( $this->request['search_date_start'] ) > strtotime( $this->request['search_date_end'] ) )
		{
			$this->searchAdvancedForm( $this->lang->words['search_invalid_date_range'] );
			return;	
		}
		
		/*if( strtotime( $this->request['search_date_start'] ) > time() || strtotime( $this->request['search_date_end'] ) > time() )
		{
			$this->searchAdvancedForm( $this->lang->words['search_invalid_date_future'] );
			return;	
		}*/
		
		if( strtotime( $this->request['search_date_start'] ) > time() )
		{
			$this->request['search_date_start']	= 'now';
		}
		
		if( strtotime( $this->request['search_date_end'] ) > time() )
		{
			$this->request['search_date_end']	= 'now';
		}

		/* Cleanup */
		$this->request['search_higlight'] = str_replace( '.', '', $this->request['search_term'] );

		/* Search Flood Check */
		if( $this->memberData['g_search_flood'] )
		{
			/* Check for a cookie */
			$last_search = IPSCookie::get( 'sfc' );
			$last_term	= str_replace( "&quot;", '"', IPSCookie::get( 'sfct' ) );
			$last_term	= str_replace( "&amp;", '&',  $last_term );			
			
			/* If we have a last search time, check it */
			if( $last_search && $last_term )
			{
				if( ( time() - $last_search ) <= $this->memberData['g_search_flood'] && $last_term != $search_term )
				{
					$this->searchAdvancedForm( sprintf( $this->lang->words['xml_flood'], $this->memberData['g_search_flood'] ) );
					return;					
				}
				else
				{
					/* Reset the cookie */
					IPSCookie::set( 'sfc', time() );
					IPSCookie::set( 'sfct', $search_term );
				}
			}
			/* Set the cookie */
			else
			{
				IPSCookie::set( 'sfc', time() );
				IPSCookie::set( 'sfct', $search_term );
			}
		}
		
		/**
		 * Ok this is an upper limit.
		 * If you needed to change this, you could do so via conf_global.php by adding:
		 * $INFO['max_search_word'] = #####;
		 */
		$this->settings['max_search_word'] = $this->settings['max_search_word'] ? $this->settings['max_search_word'] : 300;
		
		if( $this->settings['max_search_word'] && strlen( $search_term ) > $this->settings['max_search_word'] )
		{
			$this->searchAdvancedForm( sprintf( $this->lang->words['search_term_long'], $this->settings['max_search_word'] ) );
			return;
		}
		
		/* Search titles only? */
		$content_titles_only = isset( $this->request['content_title_only'] ) && $this->request['content_title_only'] ? true : false;
		
		/* Show as titles? */
		if( ( $this->request['show_as_titles'] AND $this->settings['enable_show_as_titles'] ) OR ( $content_titles_only ) )
		{
			$this->search_plugin->onlyTitles = true;
		}

		/* Check for application restriction */
		$search_app_filter	= array();
		$traditionalkey		= '';

		if( isset( $this->request['search_filter_app'] ) && is_array( $this->request['search_filter_app'] ) )
		{
			if( ! in_array( $this->settings['search_method'], array( 'traditional', 'sphinx' ) ) )
			{
				/* Bypass all this if we are searching all apps */
				if( $this->request['search_filter_app']['all'] != 1 )
				{
					foreach( $this->request['search_filter_app'] as $app => $checked )
					{
						/* Bypass the all filter */
						if( $app == 'all' )
						{
							continue;
						}

						/* Add to the array */
						if( $checked )
						{
							$search_app_filter[] = "'$app'";
						}
					}

					/* Add this condition to the search */
					$this->search_plugin->setCondition( 'app', 'IN', implode( ',', $search_app_filter ) );				
				}
			}
			else
			{
				foreach( $this->request['search_filter_app'] as $app => $checked )
				{
					$traditionalKey	= $app;
				}
			}
		}

		/* Check for an author filter */
		if( isset( $this->request['search_author'] ) && $this->request['search_author'] )
		{
			/* Query the member id */
			$mem = $this->DB->buildAndFetch( array( 
													'select' => 'member_id', 
													'from'   => 'members', 
													'where'  => "members_display_name='{$this->request['search_author']}'" 
											)	 );
			
			$this->search_plugin->searchAuthor = true;
			
			/* Add the condition to our search */
			$this->search_plugin->setCondition( 'member_id', '=', $mem['member_id'] ? $mem['member_id'] : -1 );
		}

		/* Check for application specific filters */
		if( isset( $this->request['search_app_filters'] ) && is_array( $this->request['search_app_filters'] ) )
		{
			foreach( $this->request['search_app_filters'] as $app => $filter_data )
			{
				if( ! isset( $this->search_plugin->display_plugins[ $app ] ) )
				{
					$this->search_plugin->display_plugins[ $app ] = IPSSearchIndex::getSearchDisplayPlugin( $app );
					$this->search_plugin->display_plugins[ $app ]->search_plugin	= $this->search_plugin;
				}

				$filter_data = $this->search_plugin->display_plugins[ $app ]->buildFilterSQL( $filter_data );

				if( $filter_data )
				{
					if ( isset( $filter_data[0] ) )
					{
						foreach( $filter_data as $_data )
						{
							$this->search_plugin->setCondition( $_data['column'], $_data['operator'], $_data['value'], 'AND' );
						}
					}
					else
					{
						$this->search_plugin->setCondition( $filter_data['column'], $filter_data['operator'], $filter_data['value'], 'OR' );
					}
				}
			}
		}

		/* Check Date Range */
		if( isset( $this->request['search_date_start'] ) && $this->request['search_date_start'] || isset( $this->request['search_date_end'] ) && $this->request['search_date_end'] )
		{
			/* Start Range Date */
			$search_date_start = 0;

			if( $this->request['search_date_start'] )
			{
				$search_date_start = strtotime( $this->request['search_date_start'] );
			}

			/* End Range Date */
			$search_date_end = 0;

			if( $this->request['search_date_end'] )
			{
				$search_date_end = strtotime( $this->request['search_date_end'] );
			}
						
			/* Correct for timezone...hopefully */
			$search_date_start += abs( $this->registry->class_localization->getTimeOffset() );
			$search_date_end   += abs( $this->registry->class_localization->getTimeOffset() );

			/* If the times are exactly equaly, we're going to assume they are trying to search all posts from one day */
			if( ( $search_date_start && $search_date_end ) && $search_date_start == $search_date_end )
			{
				$search_date_end += 86400;
			}

			$this->search_plugin->setDateRange( $search_date_start, $search_date_end );
		}
		
		/* If we're display results as a forum*/
		/* Count the number of results */
		$total_results = $this->search_plugin->getSearchCount( $search_term, '', $content_titles_only, array( $st, $per_page ), $this->request['search_sort_by'], $this->request['search_sort_order'] );

		/* Do Pagination Stuff */
		$st       = isset( $this->request['st'] ) ? intval( $this->request['st'] ) : 0;
		$per_page = $this->settings['search_per_page'] ? $this->settings['search_per_page'] : 25;
		
		$links = $this->registry->output->generatePagination( array( 
																	'totalItems'		=> $total_results,
																	'itemsPerPage'		=> $per_page,
																	'currentStartValue'	=> $st,
																	'baseUrl'			=> $this->_buildURLString() . '&amp;search_filter_app[' . $traditionalKey . ']=1',
															)	);

		/* Showing */
		$showing = array( 'start' => $st + 1, 'end' => ( $st + $per_page ) > $total_results ? $total_results : $st + $per_page );
		
		/* Loop through the search results and build the output */
		$search_entries = array();
		$search_results = array();
		$topic_ids      = array();
		
		/**
		 * If we've already run a search and it's not clear, kill it now
		 */
		if( $this->member->sessionClass()->session_data['search_thread_id'] )
		{
			$this->DB->return_die	= true;
			$this->DB->kill( $this->member->sessionClass()->session_data['search_thread_id'] );
			$this->DB->return_die	= false;
		}

		/**
		 * Store the process id
		 */
		$processId	= $this->DB->getThreadId();
		
		if( $processId )
		{
			$this->DB->update( 'sessions', array( 'search_thread_id' => $processId, 'search_thread_time' => time() ), "id='" . $this->member->session_id . "'" );
		}
		
		/* Do the search */
		foreach( $this->search_plugin->getSearchResults( $search_term, array( $st, $per_page ), $this->request['search_sort_by'], '', $content_titles_only, $this->request['search_sort_order'] ) as $r )
		{
			/* Hack Job */
			if( $r['app'] == 'forums' && $r['type_2'] == 'topic' && $r['type_id_2'] )
			{
				$topic_ids[] = $r['type_id_2'];
			}
			
			/* Add to the entries array */
			$search_entries[] = $r;
		}
		
		/**
		 * And kill that process ID
		 */
		if( $processId )
		{
			$this->DB->update( 'sessions', array( 'search_thread_id' => 0, 'search_thread_time' => 0 ), "id='" . $this->member->session_id . "'" );
		}
		
		/* Get dots */
		$this->_retrieveTopics( $topic_ids );

		/* Parse results */
		foreach( $search_entries as $r )
		{
			$search_results[] = $this->_parseSearchResult( $r, $search_term );
		}

		/* Output */
		$this->title   = $this->lang->words['search_results'];
		$this->output .= $this->registry->output->getTemplate( 'search' )->searchResults( $search_results, $links, $total_results, $showing, $search_term, $this->_buildURLString(), $traditionalKey, $removed_search_terms[0], $asForum );
	}
	
	/**
	 * Displays the active topics screen
	 *
	 * @access	public
	 * @return	void
	 */
	public function activeContent()
	{
		$this->search_plugin->onlyTitles = true;
		
		/* Check for application restriction */
		$search_app_filter = array();
		
		if( isset( $this->request['search_filter_app'] ) && is_array( $this->request['search_filter_app'] ) )
		{
			if( ! in_array( $this->settings['search_method'], array( 'traditional', 'sphinx' ) ) )
			{
				/* Bypass all this if we are searching all apps */
				if( $this->request['search_filter_app']['all'] != 1 )
				{
					foreach( $this->request['search_filter_app'] as $app => $checked )
					{
						/* Bypass the all filter */
						if( $app == 'all' )
						{
							continue;
						}
						
						/* Add to the array */
						if( $checked )
						{
							$search_app_filter[] = "'$app'";
						}
					}
					
					/* Add this condition to the search */
					$this->search_plugin->setCondition( 'app', 'IN', implode( ',', $search_app_filter ) );				
				}
			}
		}
		
		/* Do we have a period? */
		switch( $this->request['period'] )
		{
			case 'today':
			default:
				$date	= 86400;		// 24 hours
				
				$this->request['period']	= empty($this->request['period']) ? 'today' : $this->request['period'];
			break;
			
			case 'week':
				$date	= 604800;		// 1 week
			break;
			
			case 'weeks':
				$date	= 1209600;		// 2 weeks
			break;
			
			case 'month':
				$date	= 2592000;		// 30 days
			break;
			
			case 'months':
				$date	= 15552000;		// 6 months
			break;
			
			case 'year':
				$date	= 31536000;		// 365 days
			break;
		}
		
		/* Set Date Range */
		$this->search_plugin->setDateRange( time() - $date, time() );

		/* Count the number of results */
		$total_results = $this->search_plugin->getSearchCount( '', '', true );

		/* Do Pagination Stuff */
		$st       = isset( $this->request['st'] ) ? intval( $this->request['st'] ) : 0;
		$per_page = $this->settings['search_per_page'] ? $this->settings['search_per_page'] : 25;
		
		/* Add in application filter url bit */
		$urlbit = '';
		
		if( isset( $this->request['search_filter_app'] ) && is_array( $this->request['search_filter_app'] ) && count( $this->request['search_filter_app'] ) )
		{
			foreach( $this->request['search_filter_app'] as $app => $checked )
			{
				$urlbit .= "&amp;search_filter_app[{$app}]={$checked}";
			}
		}
		
		$urlbit .= "&amp;period={$this->request['period']}";
		
		$links = $this->registry->output->generatePagination( array( 
																	'totalItems'		=> $total_results,
																	'itemsPerPage'		=> $per_page,
																	'currentStartValue'	=> $st,
																	'baseUrl'			=> 'app=core&amp;module=search&amp;do=active' . $urlbit,
															)	);

		/* Showing */
		$showing = array( 'start' => $st + 1, 'end' => ( $st + $per_page ) > $total_results ? $total_results : $st + $per_page );

		/* Loop through the search results and build the output */
		$search_entries = array();
		$search_results = array();
		$topic_ids      = array();
		
		foreach( $this->search_plugin->getSearchResults( '', array( $st, $per_page ), 'date', '', true ) as $r )
		{
			/* Hack Job */
			if( $r['app'] == 'forums' && $r['type_2'] == 'topic' )
			{
				$topic_ids[] = $r['type_id_2'];
			}
			
			/* Add to the entries array */
			$search_entries[] = $r;
		}
		
		/* Get dots */
		$this->_retrieveTopics( $topic_ids );

		/* Parse results */
		foreach( $search_entries as $r )
		{
			$search_results[] = $this->_parseSearchResult( $r );
		}

		/* Output */
		$this->title   = $this->lang->words['active_posts_title'];
		$this->registry->output->addNavigation( $this->lang->words['active_posts_title'], '' );
		$this->output .= $this->registry->output->getTemplate( 'search' )->activePostsView( $search_results, $links, $total_results, ( method_exists( $this->search_plugin, 'getShowAsForum' ) ) ? $this->_getShowAsForum() : false );
	}
	
	/**
	 * Displays latest user content
	 *
	 * @access	public
	 * @return	void
	 */
	public function viewUserContent()
	{
		/* INIT */
		$id 	= intval( $this->request['mid'] );
		$member	= IPSMember::load( $id, 'core' );
		
		/* Content Title Only? */
		$this->search_plugin->onlyTitles = $this->request['view_by_title'] == 1 ? true : false;		
		
		/* Set flag for viewing author content */
		$this->search_plugin->searchAuthor = true;
		
		/* Set the member_id */
		$this->search_plugin->setCondition( 'member_id', '=', $id );		
		
		/* Check for application restriction */
		$search_app_filter = array();
		
		if( isset( $this->request['search_filter_app'] ) && is_array( $this->request['search_filter_app'] ) )
		{
			if( ! in_array( $this->settings['search_method'], array( 'traditional', 'sphinx' ) ) )
			{
				/* Bypass all this if we are searching all apps */
				if( $this->request['search_filter_app']['all'] != 1 )
				{
					foreach( $this->request['search_filter_app'] as $app => $checked )
					{
						/* Bypass the all filter */
						if( $app == 'all' )
						{
							continue;
						}
						
						/* Add to the array */
						if( $checked )
						{
							$search_app_filter[] = "'$app'";
						}
					}
					
					/* Add this condition to the search */
					$this->search_plugin->setCondition( 'app', 'IN', implode( ',', $search_app_filter ) );				
				}
			}
		}
		
		
		/* Count the number of results */
		$total_results = $this->search_plugin->getSearchCount( '', '', $this->search_plugin->onlyTitles );

		/* Do Pagination Stuff */
		$st       = isset( $this->request['st'] ) ? intval( $this->request['st'] ) : 0;
		$per_page = $this->settings['search_per_page'] ? $this->settings['search_per_page'] : 25;
		
		/* Add in application filter url bit */
		$urlbit = '';
		if( isset( $this->request['search_filter_app'] ) && is_array( $this->request['search_filter_app'] ) && count( $this->request['search_filter_app'] ) )
		{
			foreach( $this->request['search_filter_app'] as $app => $checked )
			{
				$urlbit .= "&amp;search_filter_app[{$app}]={$checked}";
			}
		}	
		if( $this->request['view_by_title'] == 1 )
		{
			$urlbit .= '&amp;view_by_title=1';			
		}	
		
		$links = $this->registry->output->generatePagination( array( 
																	'totalItems'		=> $total_results,
																	'itemsPerPage'		=> $per_page,
																	'currentStartValue'	=> $st,
																	'baseUrl'			=> 'app=core&amp;module=search&amp;do=user_posts&amp;mid=' . $id . $urlbit,
															)	);

		/* Showing */
		$showing = array( 'start' => $st + 1, 'end' => ( $st + $per_page ) > $total_results ? $total_results : $st + $per_page );

		/* Loop through the search results and build the output */
		$search_entries = array();
		$search_results = array();
		$topic_ids      = array();
		
		foreach( $this->search_plugin->getSearchResults( '', array( $st, $per_page ), 'date', '', $this->search_plugin->onlyTitles ) as $r )
		{
			/* Hack Job */
			if( $r['app'] == 'forums' && $r['type_2'] == 'topic' )
			{
				$topic_ids[] = $r['type_id_2'];
			}
			
			/* Add to the entries array */
			$search_entries[] = $r;
		}
		
		/* Get dots */
		$this->_retrieveTopics( $topic_ids );

		/* Parse results */
		foreach( $search_entries as $r )
		{
			$search_results[] = $this->_parseSearchResult( $r );
		}

		/* Output */
		$this->lang->words['user_posts_title']	= sprintf( $this->lang->words['user_posts_title'] , ( ($this->request['view_by_title'] && $this->request['search_app'] == 'forums') ? $this->lang->words['user_posts_title_topics'] : $this->lang->words['user_posts_title_posts'] ), $member['members_display_name'] );
		
		$this->title   = $this->lang->words['user_posts_title'];
		$this->registry->output->addNavigation( $this->lang->words['user_posts_title'], '' );
		$this->output .= $this->registry->output->getTemplate( 'search' )->userPostsView( $search_results, $links, $total_results, $member, ( method_exists( $this->search_plugin, 'getShowAsForum' ) ) ? $this->_getShowAsForum() : false );
	}
	
	/**
	 * View new posts since your last visit
	 *
	 * @access	public
	 * @return	void
	 */
	public function viewNewPosts()
	{
		$this->search_plugin->onlyTitles	= true;
		$_METHOD                            = ( method_exists( $this->search_plugin, 'viewNewPosts_count' ) && method_exists( $this->search_plugin, 'viewNewPosts_fetch' ) ) ? 'custom' : 'standard';
		$asForum							= ( method_exists( $this->search_plugin, 'getShowAsForum' ) ) ? $this->_getShowAsForum() : false;
		
		/* Do we have a manual method? */
		if ( $_METHOD == 'custom' )
		{
			$total_results = $this->search_plugin->viewNewPosts_count();
		}
		else
		{
			/* Call the unread items function */
			$this->search_plugin->setUnreadConditions();

			/* Check for application restriction */
			$search_app_filter = array();
		
			if( isset( $this->request['search_filter_app'] ) && is_array( $this->request['search_filter_app'] ) )
			{
				if( ! in_array( $this->settings['search_method'], array( 'traditional', 'sphinx' ) ) )
				{
					/* Bypass all this if we are searching all apps */
					if( $this->request['search_filter_app']['all'] != 1 )
					{
						foreach( $this->request['search_filter_app'] as $app => $checked )
						{
							/* Bypass the all filter */
							if( $app == 'all' )
							{
								continue;
							}
						
							/* Add to the array */
							if( $checked )
							{
								$search_app_filter[] = "'$app'";
							}
						}
					
						/* Add this condition to the search */
						$this->search_plugin->setCondition( 'app', 'IN', implode( ',', $search_app_filter ) );				
					}
				}
			}
		
			/* Exclude forums */
			if( $this->settings['vnp_block_forums'] )
			{
				if( $this->request['search_app'] == 'forums' )
				{				
					$this->search_plugin->setCondition( 't.forum_id', 'NOT IN', $this->settings['vnp_block_forums'] );
				}
			}
		
			/* Only Titles */
			//$this->search_plugin->setCondition( 'content_title', '<>', "''" );
			$group_by = '';
		
			if( $this->request['search_app'] == 'forums' )
			{
				$group_by = 'topic_id';
			}

			if( !$this->search_plugin->removeMe )
			{
				/* Count the number of results */
				$total_results	= $this->search_plugin->getSearchCount( '', '', true );
			}
			else
			{
				$total_results	= 0;
			}
		}

		/* Do Pagination Stuff */
		$st       = isset( $this->request['st'] ) ? intval( $this->request['st'] ) : 0;
		$per_page = $this->settings['search_per_page'] ? $this->settings['search_per_page'] : 25;
		
		/* Add in application filter url bit */
		$urlbit = '';
		if( isset( $this->request['search_filter_app'] ) && is_array( $this->request['search_filter_app'] ) && count( $this->request['search_filter_app'] ) )
		{
			foreach( $this->request['search_filter_app'] as $app => $checked )
			{
				$urlbit .= "&amp;search_filter_app[{$app}]={$checked}";
			}
		}		
		
		$links = $this->registry->output->generatePagination( array( 
																	'totalItems'		=> $total_results,
																	'itemsPerPage'		=> $per_page,
																	'currentStartValue'	=> $st,
																	'baseUrl'			=> 'app=core&amp;module=search&amp;do=new_posts' . $urlbit,
															)	);

		/* Showing */
		$showing = array( 'start' => $st + 1, 'end' => ( $st + $per_page ) > $total_results ? $total_results : $st + $per_page );

		/* Loop through the search results and build the output */
		$search_entries = array();
		$search_results = array();
		$topic_ids      = array();
		
		/* Do we have a manual method? */
		if ( $_METHOD == 'custom' )
		{
			$search_entries = $this->search_plugin->viewNewPosts_fetch( array( $st, $per_page ) );
			
			foreach( $search_entries as $data )
			{
				$topic_ids[] = $data['tid'];
			}
				
		}
		else
		{
			if( !$this->search_plugin->removeMe )
			{
				foreach( $this->search_plugin->getSearchResults( '', array( $st, $per_page ), 'date', '', true ) as $r )
				{
					/* Hack Job */
					if( $r['app'] == 'forums' && $r['type_2'] == 'topic' )
					{
						$topic_ids[] = $r['type_id_2'];
					}
				
					/* Add to the entries array */
					$search_entries[] = $r;
				}
			}
		}
		
		/* Get dots */
		$this->_retrieveTopics( $topic_ids );

		/* Parse results */
		foreach( $search_entries as $r )
		{
			$search_results[] = $this->_parseSearchResult( $r, '', true );
		}

		/* Output */
		$this->title   = $this->lang->words['new_posts_title'];
		$this->registry->output->addNavigation( $this->lang->words['new_posts_title'], '' );
		$this->output .= $this->registry->output->getTemplate( 'search' )->newPostsView( $search_results, $links, $total_results, $asForum );
	}	
	
	/**
	 * Retrieve the topic array
	 *
	 * @access	private
	 * @param	array 		Topic ids
	 * @return	void
	 */
	private function _retrieveTopics( $ids )
	{
		/* Query posts - this is so the stupid "you have posted" dot shows up on topic icons */
		$this->_topicArray = array();
		
		if( ! $this->settings['show_user_posted'] )
		{
			return;
		}		
		
		if( count( $ids ) )
		{
			$this->DB->build( array( 
									'select' => 'author_id, topic_id',
									'from'   => 'posts',
									'where'  => 'author_id=' . $this->memberData['member_id'] . ' AND topic_id IN(' . implode( ',', $ids ) . ')',
							)	);
									  
			$this->DB->execute();
			
			while( $p = $this->DB->fetch() )
			{
				$this->_topicArray[ $p['topic_id'] ] = $p['author_id'];
			}			
		}
	}

	/**
	 * Parse a common search result
	 *
	 * @access	private
	 * @param	array 	$r				Search result
	 * @param	string	$search_term	Keywords searched for
	 * @param	bool	$isVnc			Is from view new content
	 * @return	array 	$search_result	Search result for template
	 */
	private function _parseSearchResult( $r, $search_term='', $isVnc=false )
	{
		/* Forum stuff */
		$sub               = false;
		$r['_topic_array'] = $this->_topicArray;
		
		/* If basic search, strip the content */
		IPSText::getTextClass( 'bbcode' )->parse_wordwrap			= 0;
		IPSText::getTextClass( 'bbcode' )->parse_bbcode				= 0;
		IPSText::getTextClass( 'bbcode' )->strip_quotes				= 1;
		IPSText::getTextClass( 'bbcode' )->parsing_section			= 'topics';
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $r['member_group_id'];
		IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $r['mgroup_others'];

		$r['content'] = strip_tags( IPSText::getTextClass( 'bbcode' )->stripAllTags( $r['content'] ) );
		$r['content'] = str_replace( array( '&lt;br&gt;', '&lt;br /&gt;' ), '', $r['content'] );
		$r['content'] = trim( str_replace( array( "\n\n\n", "\n\n" ), "\n", str_replace( "\r", '', $r['content'] ) ) );

		/* Highlight */
		$r['content']       = IPSText::searchHighlight( $this->_searchTruncate( $r['content'], $search_term ), $search_term );
		$r['content_title'] = ( ! $this->search_plugin->onlyPosts AND $this->_getShowAsForum() === false ) ? IPSText::searchHighlight( $r['content_title'], $search_term ) : $r['content_title'];

		/* Check to see if the display plugin is loaded */
		if( ! isset( $this->search_plugin->display_plugins[ $r['app'] ] ) )
		{
			$this->search_plugin->display_plugins[ $r['app'] ] = IPSSearchIndex::getSearchDisplayPlugin( $r['app'] );
			$this->search_plugin->display_plugins[ $r['app'] ]->search_plugin	= $this->search_plugin;
		}
		
		/* Return the formatted result */
		if( isset( $this->search_plugin->display_plugins[ $r['app'] ] ) && is_object( $this->search_plugin->display_plugins[ $r['app'] ] ) )
		{
			$return = $this->search_plugin->display_plugins[ $r['app'] ]->formatContent( $r, $isVnc );
			
			if( is_array( $return ) )
			{
				$html = $return[0];
				$sub = $return[1];
			}
			else
			{
				$html = $return;
			}
		}
		else
		{
			$html = $this->registry->output->getTemplate( 'search' )->searchRowGenericFormat( $r );
		}
		
		return array( 'html' => $html, 'app' => $r['app'], 'type' => $r['type'], 'sub' => $sub );
	}

	/**
	 * Function to trim the search result display around the the hit
	 *
	 * @access	private
	 * @param	string	$haystack	Full search result
	 * @param	string	$needle		The search term
	 * @return	string
	 **/
	private function _searchTruncate( $haystack, $needle )
	{
		/* Base on words */
		$haystack = explode( " ", $haystack );

		if( count( $haystack ) > 21 )
		{
			$_term_at = $this->searchInArray( $needle, $haystack );

			if( $_term_at - 11 > 0 )
			{
				$begin = array_splice( $haystack, 0, $_term_at - 11 );
				
				/* The term position will have changed now */
				$_term_at = $this->searchInArray( $needle, $haystack );
			}

			if( $_term_at + 11 < count( $haystack ) )
			{
				$end   = array_splice( $haystack, $_term_at + 11, count( $haystack ) );
			}
		}
		else
		{
			$begin = array();
			$end   = array();
		}

		$haystack = implode( " ", $haystack );
		
		if( is_array( $begin ) && count( $begin ) )
		{
			$haystack = '...' . $haystack;
		}
		
		if( is_array( $end ) && count( $end ) )
		{
			$haystack = $haystack . '...';
		}
		
		return $haystack;
	}
	
	/**
	 * Search array (array_search only finds exact instances)
	 *
	 * @access	protected
	 * @param	string		"Needle"
	 * @param	array 		Array of entries to search
	 * @return	mixed		Key of array, or false on failure
	 */
	protected function searchInArray( $needle, $haystack )
	{
		if( !is_array( $haystack ) OR !count($haystack) OR ! $needle )
		{
			return false;
		}
		
		foreach( $haystack as $k => $v )
		{
			if( $v AND strpos( $v, $needle ) !== false )
			{
				return $k;
			}
		}
		
		return false;
	}
	
	/**
	 * Returns a url string that will maintain search results via links
	 *
	 * @access	private
	 * @return	string
	 */
	private function _buildURLString()
	{
		/* INI */
		$url_string = 'app=core&amp;module=search&amp;do=quick_search';
		
		/* Add author name */
		if( isset( $this->request['search_author'] ) )
		{
			$url_string .= "&amp;search_author={$this->request['search_author']}";
		}
		
		/* Add titles only */
		if( isset( $this->request['show_as_titles'] ) )
		{
			$url_string .= "&amp;show_as_titles={$this->request['show_as_titles']}";
		}
		
		/* Search Range */
		if( isset( $this->request['search_date_start'] ) )
		{
			$url_string .= "&amp;search_date_start={$this->request['search_date_start']}";
		}
		
		if( isset( $this->request['search_date_end'] ) )
		{
			$url_string .= "&amp;search_date_end={$this->request['search_date_end']}";
		}
	
		/* Search app filters */
		if( isset( $this->request['search_app_filters'] ) && count( $this->request['search_app_filters'] ) )
		{
			foreach( $this->request['search_app_filters'] as $app => $filter_data )
			{
				if( is_array( $filter_data ) )
				{					
					foreach( $filter_data as $k => $v )
					{
						if ( is_array( $v ) )
						{
							foreach( $v as $_k => $_v )
							{
								$url_string .= "&amp;search_app_filters[{$app}][{$k}][$_k]={$_v}";
							}
						}
						else
						{
							$url_string .= "&amp;search_app_filters[{$app}][{$k}]={$v}";
						}
					}
				}
				else
				{
					$url_string .= "&amp;search_app_filters[{$app}]={$v}";
				}
			}
		}
		
		/* Search sort by */
		if( isset( $this->request['search_sort_by'] ) )
		{
			$url_string .= "&amp;search_sort_by={$this->request['search_sort_by']}";
		}
		
		if( isset( $this->request['search_sort_order'] ) )
		{
			$url_string .= "&amp;search_sort_order={$this->request['search_sort_order']}";
		}
		
		/* Add in application filter */
		/*if( isset( $this->request['search_filter_app'] ) && is_array( $this->request['search_filter_app'] ) && count( $this->request['search_filter_app'] ) )
		{
			foreach( $this->request['search_filter_app'] as $app => $checked )
			{
				$url_string .= "&amp;search_filter_app[{$app}]={$checked}";
			}
		}*/

		if( isset( $this->request['content_title_only'] ) && $this->request['content_title_only'] )
		{
			$url_string .= "&amp;content_title_only=1";
		}
		
		if( isset( $this->request['type'] ) && isset( $this->request['type_id'] ) )
		{
			$url_string .= "&amp;type={$this->request['type']}&amp;type_id={$this->request['type_id']}";
		}
		
		if( isset( $this->request['type_2'] ) && isset( $this->request['type_id_2'] ) )
		{
			$url_string .= "&amp;type_2={$this->request['type_2']}&amp;type_id_2={$this->request['type_id_2']}";
		}		

		$url_string .= '&amp;search_term=' . urlencode( str_replace( '&quot;', '"', str_replace( '&amp;', '&', $this->request['search_term'] ) ) );

		return $url_string;		
	}
	
	/**
	 * Checks to see if the logged in user is allowed to use the search system
	 *
	 * @access	private
	 * @return	void
	 */
	private function _canSearch()
	{
		/* Check the search setting */
		if( ! $this->settings['allow_search'] )
		{
			if( $this->xml_out )
			{
				@header( "Content-type: text/html;charset={$this->settings['gb_char_set']}" );
				print $this->lang->words['search_off'];
				exit();
			}
			else
			{
				$this->registry->output->showError( 'search_off', 10145 );
			}
		}
		
		/* Check the member authorization */
		if( ! isset( $this->memberData['g_use_search'] ) || ! $this->memberData['g_use_search'] )
		{
			if( $this->xml_out )
			{
				@header( "Content-type: text/html;charset={$this->settings['gb_char_set']}" );
				print $this->lang->words['no_xml_permission'];
				exit();
			}
			else
			{
				$this->registry->output->showError( 'no_permission_to_search', 10146 );
			}
		}		
	}
	
	/**
	 * Wrapper function to prevent fatal errors if method does not support this function
	 *
	 * @access	private
	 * @return	boolean
	 */
	private function _getShowAsForum()
	{
		if ( method_exists( $this->search_plugin, 'getShowAsForum' ) )
		{
			return $this->search_plugin->getShowAsForum();
		}
		else
		{
			return false;
		}
	}
}