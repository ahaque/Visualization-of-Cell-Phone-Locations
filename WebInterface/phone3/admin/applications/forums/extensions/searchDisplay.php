<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Formats forum search results
 * Last Updated: $Date: 2009-08-19 20:17:18 -0400 (Wed, 19 Aug 2009) $
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Forums
 * @link		http://www.
 * @version		$Rev: 5032 $ 
 **/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class forumsSearchDisplay implements iSearchDisplay
{
	/**
	 * Last topic id completed
	 *
	 * @access	private
	 * @var		integer	Topic id
	 */
	private $last_topic = 0;
	
	/**
	 * The search plugin for this app
	 *
	 * @access	public
	 * @var		object
	 */
	public $search_plugin;

	
	/**
	 * Formats the forum search result for display
	 *
	 * @access	public
	 * @param	array   $search_row		Array of data from search_index
	 * @param	bool	$isVnc			Is from view new content
	 * @return	mixed	Formatted content, ready for display, or array containing a $sub section flag, and content
	 **/	
	public function formatContent( $search_row, $isVnc=false )
	{
		/* Get class forums, used for displaying forum names on results */
		if( ipsRegistry::isClassLoaded('class_forums') !== TRUE )
		{
			require_once( IPSLib::getAppDir( 'forums' ) . "/sources/classes/forums/class_forums.php" );
			ipsRegistry::setClass( 'class_forums', new class_forums( ipsRegistry::instance() ) );
			ipsRegistry::getClass( 'class_forums' )->forumsInit();
		}

		/* Array */
		$search_row = $this->_buildOutputArray( $search_row );

		/* Indent */
		$indent = ( $this->last_topic == $search_row['type_id_2'] );
						
		$this->last_topic = $search_row['type_id_2'];
		
		/* Various data */
		$search_row['_last_post']  = $search_row['last_post'];
		$search_row['_longTitle']  = $search_row['content_title'];
		$search_row['_shortTitle'] = IPSText::truncate( $search_row['content_title'], 60 );
		$search_row['last_poster'] = $search_row['last_poster_id'] ? IPSLib::makeProfileLink( $search_row['last_poster_name'], $search_row['last_poster_id'], $search_row['seo_last_name'] ) : ipsRegistry::$settings['guest_name_pre'] . $search_row['last_poster_name'] . ipsRegistry::$settings['guest_name_suf'];
		$search_row['starter']     = $search_row['starter_id']     ? IPSLib::makeProfileLink( $search_row['starter_name'], $search_row['starter_id'], $search_row['seo_first_name'] ) : $this->settings['guest_name_pre'] . $search_row['starter_name'] . $this->settings['guest_name_suf'];
		//$search_row['posts']  	   = ipsRegistry::getClass('class_localization')->formatNumber( intval($search_row['posts']) );
		//$search_row['views']	   = ipsRegistry::getClass('class_localization')->formatNumber( intval($search_row['views']) );
		$search_row['last_post']   = ipsRegistry::getClass( 'class_localization')->getDate( $search_row['last_post'], 'SHORT' );

		if ( isset( $search_row['post_date'] ) )
		{
			$search_row['_post_date']	= $search_row['post_date'];
			$search_row['post_date']	= ipsRegistry::getClass( 'class_localization')->getDate( $search_row['post_date'], 'SHORT' );
		}

		if ( $this->memberData['is_mod'] )
		{
			$search_row['posts'] += intval($search_row['topic_queuedposts']);
		}

		if ($search_row['posts'])
		{
			if ( (($search_row['posts'] + 1) % ipsRegistry::$settings['display_max_posts']) == 0 )
			{
				$pages = ($search_row['posts'] + 1) / ipsRegistry::$settings['display_max_posts'];
			}
			else
			{
				$number = ( ($search_row['posts'] + 1) / ipsRegistry::$settings['display_max_posts'] );
				$pages = ceil( $number);
			}
		}

		if ( $pages > 1 )
		{
			for ( $i = 0 ; $i < $pages ; ++$i )
			{
				$real_no = $i * ipsRegistry::$settings['display_max_posts'];
				$page_no = $i + 1;

				if ( $page_no == 4 and $pages > 4 )
				{
					$search_row['pages'][] = array( 'last'   => 1,
					 					       'st'     => ($pages - 1) * ipsRegistry::$settings['display_max_posts'],
					  						   'page'   => $pages );
					break;
				}
				else
				{
					$search_row['pages'][] = array( 'last' => 0,
											   'st'   => $real_no,
											   'page' => $page_no );
				}
			}
		}
		
		/* Format as a topic */
		if( $search_row['type_2'] == 'topic' )
		{
			/* Forum Breadcrum */
			$search_row['_forum_trail'] = ipsRegistry::getClass( 'class_forums' )->forumsBreadcrumbNav( $search_row['forum_id'] );

			/* Is it read?  We don't support last_vote in search. */
			$is_read	= ipsRegistry::getClass( 'classItemMarking' )->isRead( array( 'forumID' => $search_row['forum_id'], 'itemID' => $search_row['type_id_2'], 'itemLastUpdate' => $search_row['lastupdate'] ? $search_row['lastupdate'] : $search_row['updated'] ), 'forums' );
			
			/* Has posted dot */
			$show_dots = '';
			
			if( ipsRegistry::$settings['show_user_posted'] )
			{
				if( ipsRegistry::member()->getProperty('member_id') && ( isset( $search_row['_topic_array'][$search_row['type_id_2']] ) && $search_row['_topic_array'][$search_row['type_id_2']] ) )
				{
					$show_dots = 1;
				}
			}
			
			/* Icon */
			$search_row['_icon'] = ipsRegistry::getClass( 'class_forums' )->fetchTopicFolderIcon( $search_row, $show_dots, $is_read );
			
			/* Display type */
			if ( $this->search_plugin->getShowAsForum() !== true )
			{
				return array( ipsRegistry::getClass( 'output' )->getTemplate( 'search' )->topicPostSearchResult( $search_row, $indent, ( $this->search_plugin->onlyTitles || $this->search_plugin->noPostPreview ) ? 1 : 0 ), $indent );
			}
			else
			{
				return array( ipsRegistry::getClass( 'output' )->getTemplate( 'search' )->topicPostSearchResultAsForum( $search_row, $indent, ( $this->search_plugin->onlyTitles || $this->search_plugin->noPostPreview ) ? 1 : 0 ), $indent );
			}
		}
		/* Format as a forum */
		else
		{
			return ipsRegistry::getClass( 'output' )->getTemplate( 'search' )->forumSearchResult( $search_row, ( $this->search_plugin->onlyTitles || $this->search_plugin->noPostPreview ) ? 1 : 0 );
		}
	}

	/**
	 * Internal function, used to sort out the pid and title
	 *
	 * @access	private
	 * @param	array 	$r	Array of data
	 * @return	array 	Formatted data, ready for output
	 **/
	private function _buildOutputArray( $r )
	{
		if( $r['misc'] )
		{
			$_data              = unserialize( $r['misc'] );
			$r['misc']          = isset( $_data['pid'] )   && $_data['pid']   ? $_data['pid']   : $r['misc'];
			$r['content_title'] = isset( $_data['title'] ) && $_data['title'] ? $_data['title'] : $r['content_title'];
		}
		
		return $r;
	}
	
	/**
	 * Retuns the html for displaying the forum category filter on the advanced search page
	 *
	 * @access	public
	 * @return	string	Filter HTML
	 **/
	public function getFilterHTML()
	{
		/* Make sure class_forums is setup */
		if( ipsRegistry::isClassLoaded('class_forums') !== TRUE )
		{
			require_once( IPSLib::getAppDir( 'forums' ) . "/sources/classes/forums/class_forums.php" );
			ipsRegistry::setClass( 'class_forums', new class_forums( ipsRegistry::instance() ) );
			ipsRegistry::getClass( 'class_forums' )->strip_invisible = 1;			
			ipsRegistry::getClass( 'class_forums' )->forumsInit();
		}
				
		return ipsRegistry::getClass( 'output' )->getTemplate( 'search' )->forumAdvancedSearchFilters( ipsRegistry::getClass( 'class_forums' )->buildForumJump( 0, 1 ) );
	}

	/**
	 * Returns an array used in the searchplugin's setCondition method
	 *
	 * @access	public
	 * @param	array 	$data	Array of forums to view
	 * @return	array 	Array with column, operator, and value keys, for use in the setCondition call
	 **/
	public function buildFilterSQL( $data )
	{
		$return = array();
		
		if( isset( $data ) && is_array( $data ) && count( $data ) )
		{
			/* Load class_forums so that we can search sub forums automatically */
			if( ipsRegistry::isClassLoaded('class_forums') !== TRUE )
			{
				require_once( IPSLib::getAppDir( 'forums' ) . '/sources/classes/forums/class_forums.php' );
				ipsRegistry::setClass( 'class_forums', new class_forums( ipsRegistry::instance() ) );
				ipsRegistry::getClass( 'class_forums' )->forumsInit();
			}
			
			foreach( $data as $field => $_data )
			{
				/* FORUMS */
				if ( $field == 'forums' && count( $data['forums'] ) )
				{
					/* Get a list of child ids */
					foreach( $data['forums'] as $forum_id )
					{
						$children = ipsRegistry::getClass( 'class_forums' )->forumsGetChildren( $forum_id );
				
						foreach( $children as $kid )
						{
							if( ! in_array( $kid, $data['forums'] ) )
							{
								$data['forums'][] = $kid;
							}
						}
					}
					
					# Handled in searchPlugin::buildWhereStatement
					//$return[] = array( 'column' => 'type_id', 'operator' => 'IN', 'value' => implode( ',', $data['forums'] ) );
				}
				
				/* CONTENT ONLY */
				if ( $field == 'noPreview' AND $data['noPreview'] == 1 )
				{
					$this->search_plugin->noPostPreview = true;
				}
					
				/* CONTENT ONLY */
				if ( $field == 'contentOnly' AND $data['contentOnly'] == 1 )
				{
					$this->search_plugin->onlyPosts = true;
				}
				
				/* POST COUNT */
				if ( $field == 'pCount' AND intval( $data['pCount'] ) > 0 )
				{
					$return[] = array( 'column' => 't.posts', 'operator' => '>=', 'value' => intval( $data['pCount'] ) );
				}
				
				/* TOPIC VIEWS */
				if ( $field == 'pViews' AND intval( $data['pViews'] ) > 0 )
				{
					$return[] = array( 'column' => 't.views', 'operator' => '>=', 'value' => intval( $data['pViews'] ) );
				}
			}
			
			return $return;
		}
		else
		{
			return '';
		}
	}
	
	/**
	 * Modify the search query
	 *
	 * @access	public
	 * @param	array 	$query			The current unmodified query
	 * @param	bool 	[$count_only]	Set to true if this is a count(*) query
	 * @return	array 	Search query, modified by the plugin
	 **/
	public function modifySearchQuery( $query, $count_only=false )
	{
		if( ipsRegistry::$settings['search_method'] == 'sphinx' )
		{
			$query->SetFilter( 'approved', array( 1 ) );
			$query->SetFilter( 'queued'  , array( 0 ) );
			$query->SetFilter( 'password', array( 0 ) );

			if( isset( ipsRegistry::$request['search_app_filters']['forums']['forums'] ) && is_array( ipsRegistry::$request['search_app_filters']['forums']['forums'] ) && count( ipsRegistry::$request['search_app_filters']['forums']['forums'] ) )
			{
				/* Load class_forums so that we can search sub forums automatically */
				if( ipsRegistry::isClassLoaded('class_forums') !== TRUE )
				{
					require_once( IPSLib::getAppDir( 'forums' ) . '/sources/classes/forums/class_forums.php' );
					ipsRegistry::setClass( 'class_forums', new class_forums( ipsRegistry::instance() ) );
					ipsRegistry::getClass( 'class_forums' )->forumsInit();
				}
				
				$forum_ids	= array();

				/* Get a list of child ids */
								foreach(  ipsRegistry::$request['search_app_filters']['forums']['forums'] as $forum_id )
				{
					if( $forum_id )
					{
						$forum_ids[]	= $forum_id;
						
						//$children = ipsRegistry::getClass( 'class_forums' )->forumsGetChildren( $forum_id );
						
						//foreach( $children as $kid )
						//{
						//	if( ! in_array( $kid, ipsRegistry::$request['search_app_filters']['forums'] ) )
						//	{
						//		 $forum_ids[]	= $kid;
						//	}
						//}
					}
				}

				if( is_array($forum_ids) AND count($forum_ids) )
				{
					$query->SetFilter( 'forum_id', $forum_ids );
				}
			}
			
			/* Limit by forum */
			$type		= ipsRegistry::$request['type'];
			$type_id	= intval( ipsRegistry::$request['type_id'] );
	
			if( $type && $type_id )
			{
				$query->SetFilter( 'forum_id', array( $type_id ) );
			}
			
			/* Limit by topic */
			$type_2		= ipsRegistry::$request['type_2'];
			$type_id_2	= intval( ipsRegistry::$request['type_id_2'] );
			
			if( $type_2 && $type_id_2 )
			{
				$query->SetFilter( 'tid', array( $type_id_2 ) );
			}
		}
		else
		{
			return $query;
		}
	}

	/**
	 * Run the search query. 
	 * This allows us to set different indexes
	 *
	 * @access	public
	 * @param	object	$sphinxClient	The sphinx client reference
	 * @param	string 	$search_term	The search term
	 * @param	bool	$group_results	Group posts by topic
	 * @return	array 	Sphinx result array
	 **/
	public function runSearchQuery( $sphinxClient, $search_term='', $group_results=false )
	{
		if( ipsRegistry::$settings['search_method'] == 'sphinx' )
		{
			if( ipsRegistry::$request['content_title_only'] == 1 )
			{
				return $sphinxClient->Query( $search_term, 'forums_search_topics_main,forums_search_topics_delta' );
			}
			else
			{
				if( $group_results )
				{
					$sphinxClient->SetGroupBy( 'tid', SPH_GROUPBY_ATTR, '@group DESC');
				}
				
				if( ipsRegistry::$request['f_content_only'] )
				{
					return $sphinxClient->Query( $search_term, 'forums_search_posts_main,forums_search_posts_delta' );
				}
				else
				{
					return $sphinxClient->Query( $search_term, 'forums_search_posts_main,forums_search_topics_main,forums_search_posts_delta,forums_search_topics_delta' );
				}
			}
		}
		else
		{
			return array();
		}
	}
}