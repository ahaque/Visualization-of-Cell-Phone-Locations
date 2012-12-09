<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Forum Viewing
 * Last Updated: $Date: 2009-08-31 20:32:10 -0400 (Mon, 31 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage  Forums 
 * @version		$Rev: 5066 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_forums_forums_forums extends ipsCommand
{
	/**
	 * Array of form data
	 *
	 * @access	private
	 * @var		array
	 */
	private $forum	= array();
	
	/**
	 * Array of topic ids to open
	 *
	 * @access	private
	 * @var		array
	 */
	private $update_topics_open	= array();
	
	/**
	 * Array of topic ids to close
	 *
	 * @access	private
	 * @var		array
	 */
	private $update_topics_close	= array();
	
	/**
	 * Class entry point
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	void		[Outputs to screen/redirects]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
        // Are we doing anything with "site jump?"
        //-----------------------------------------

        switch( $this->request[ 'f' ] )
        {
        	case 'sj_home':
        		$this->registry->getClass('output')->silentRedirect( $this->settings['base_url'] );
        		break;
        	case 'sj_search':
        		$this->registry->getClass('output')->silentRedirect( $this->settings['base_url'] . 'app=core&amp;module=search' );
        		break;
        	case 'sj_help':
        		$this->registry->getClass('output')->silentRedirect( $this->settings['base_url'] . 'app=core&amp;module=help' );
        		break;
        	default:
        		$this->request['f'] =  intval($this->request['f']  );
        		break;
        }
        
        $this->initForums();
        
        //-----------------------------------------
        // Get the forum info based on the forum ID,
        // and get the category name, ID, etc.
        //-----------------------------------------
        
        $this->forum = $this->registry->getClass('class_forums')->forum_by_id[ $this->request['f'] ]; 
        
        //-----------------------------------------
        // Error out if we can not find the forum
        //-----------------------------------------
        
        if( ! $this->forum['id'] )
        {
        	$this->registry->getClass('output')->showError( 'forums_no_id', 10333 );
        }
        
        //-----------------------------------------
		// Build permissions
		//-----------------------------------------
		
		$this->_buildPermissions();
		
        //-----------------------------------------
        // Is it a redirect forum?
        //-----------------------------------------
        
        if( isset( $this->forum['redirect_on'] ) AND $this->forum['redirect_on'] )
        {
        	$redirect = $this->DB->buildAndFetch( array( 'select' => 'redirect_url', 'from' => 'forums', 'where' => "id=".$this->forum['id']) );

        	if( $redirect['redirect_url'] )
        	{
        		//-----------------------------------------
				// Update hits:
				//-----------------------------------------
				
				$this->DB->buildAndFetch( array( 'update' => 'forums', 'set' => 'redirect_hits=redirect_hits+1', 'where' => "id=".$this->forum['id']) );
				
				//-----------------------------------------
				// Boink!
				//-----------------------------------------
				
				$this->registry->getClass('output')->silentRedirect( $redirect['redirect_url'] );
				
				// Game over man!
        	}
        }
        
        //-----------------------------------------
        // If this is a sub forum, we need to get
        // the cat details, and parent details
        //-----------------------------------------
        
        $this->nav = $this->registry->getClass('class_forums')->forumsBreadcrumbNav( $this->forum['id'] );
        
		$this->forum['FORUM_JUMP'] = $this->registry->getClass('class_forums')->buildForumJump( 1, 0, 0 );
		
		//-----------------------------------------
		// Check forum access perms
		//-----------------------------------------
		
		if( empty( $this->request['L'] ) )
		{
			$this->registry->getClass('class_forums')->forumsCheckAccess( $this->forum['id'], 1 );
		}
		
		//-----------------------------------------
        // Are we viewing the forum, or viewing the forum rules?
        //-----------------------------------------
        
		$subforum_data  = array();
		$data           = array();

		if( $this->registry->getClass('class_forums')->forumsGetChildren( $this->forum['id'] ) )
		{
			$subforum_data = $this->showSubForums();
		}
		
		if ( $this->forum['sub_can_post'] )
		{ 
			$data = $this->showForum();
		}
		else
		{
			//-----------------------------------------
			// No forum to show, just use the HTML in $this->sub_output
			// or there will be no HTML to use in the str_replace!
			//-----------------------------------------
			
			$subforum_data = $subforum_data ? $subforum_data : $this->showSubForums();
		}
		
		/* Posting Allowed? */
		$this->forum['_user_can_post'] = 1;
		
		if( ! $this->registry->permissions->check( 'start', $this->forum ) )
		{
			$this->forum['_user_can_post'] = 0;
		}

		if( ! $this->forum['sub_can_post'] )
		{
			$this->forum['_user_can_post'] = 0;
		}

		if( $this->forum['min_posts_post'] && $this->forum['min_posts_post'] > $this->memberData['posts'] )
		{
			$this->forum['_user_can_post'] = 0;
		}
		
		if( ! $this->forum['status'] )
		{
			$this->forum['_user_can_post'] = 0;
		}
		
		if( ! $this->memberData['g_post_new_topics'] )
		{
			$this->forum['_user_can_post'] = 0;
		}
		
		/* Rules */
		if( $this->forum['show_rules'] == 2)
		{
			IPSText::getTextClass( 'bbcode' )->parse_smilies			= 1;
			IPSText::getTextClass( 'bbcode' )->parse_html				= 1;
			IPSText::getTextClass( 'bbcode' )->parse_nl2br				= 1;
			IPSText::getTextClass( 'bbcode' )->parse_bbcode				= 1;
			IPSText::getTextClass( 'bbcode' )->parsing_section			= 'topics';
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup			= $this->memberdata['member_group_id'];
			IPSText::getTextClass( 'bbcode' )->parsing_mgroup_others	= $this->memberData['mgroup_others'];

			$this->forum['rules_text'] = IPSText::getTextClass( 'bbcode' )->preDisplayParse( $this->forum['rules_text'] );
		}

		//-----------------------------------------
		// Show the template
		//-----------------------------------------
	
		$template = $this->registry->getClass('output')->getTemplate('forum')->forumIndexTemplate( 
																									$this->forum,
																									$data['announce_data'],
		 																							$data['topic_data'],
																									$data['other_data'],
																									$data['multi_mod_data'],
																									$subforum_data,
																									$data['footer_filter'],
																									$data['active_users'],
																									$this->registry->getClass('class_forums')->forumsGetModerators( $this->forum['id'] )
																								);
		
		$this->registry->getClass('output')->setTitle( $this->settings['board_name'] . ' -> ' . $this->forum['name'] );
		$this->registry->getClass('output')->addContent( $template );

		if( is_array( $this->nav ) AND count( $this->nav ) )
		{
			foreach( $this->nav as $_id => $_nav )
			{
				$this->registry->getClass('output')->addNavigation( $_nav[0], $_nav[1], $_nav[2], $_nav[3] );
			}
		}

		if( $this->forum['parent_id'] == 0 )
		{
			$this->registry->output->addToDocumentHead( 'raw', "<link rel='up' href='{$this->settings['base_url']}' />" );
		}
		else
		{
			$this->registry->output->addToDocumentHead( 'raw', "<link rel='up' href='" . $this->registry->output->buildSEOUrl( 'showforum=' . $this->forum['parent_id'], 'public', $this->registry->getClass('class_forums')->forum_by_id[ $this->forum['parent_id'] ]['name_seo'], 'showforum' ) . "' />" );
		}
		
		$this->registry->output->addMetaTag( 'description', $this->forum['name'] . ': ' . $this->forum['description'] );
        $this->registry->getClass('output')->sendOutput();
	}
	
	/**
	 * Setup for the forum controller
	 *
	 * @access	public
	 * @return	void
	 **/
	public function initForums()
	{
		$this->registry->getClass( 'class_localization' )->loadLanguageFile( array( 'public_forums', 'public_boards' ) );
		
		//-----------------------------------------
		// Multi TIDS?
		// If st is not defined then kill cookie
		// st will always be defined across pages
		//-----------------------------------------
		
		if( !array_key_exists( 'st', $this->request ) AND !array_key_exists( 'prune_day', $this->request ) )
		{
			IPSCookie::set('modtids', ',', 0);
			$this->request['selectedtids'] = '';
		}
		else
		{
			$this->request['selectedtids'] = IPSCookie::get('modtids');
		}
	}
	
	/**
	 * Builds permissions for the forum controller
	 *
	 * @access	private
	 * @return	void
	 **/
	private function _buildPermissions()
	{
		$mod = $this->memberData['forumsModeratorData'];
		
		if( $this->memberData['g_is_supmod'] )
		{
			$this->can_edit_topics  = 1;
			$this->can_close_topics = 1;
			$this->can_open_topics  = 1;
		}
		else if( isset($mod[ $this->forum['id'] ]) AND is_array( $mod[ $this->forum['id'] ] ) )
		{
			if ( $mod[ $this->forum['id'] ]['edit_topic'] )
			{
				$this->can_edit_topics = 1;
			}
			
			if ( $mod[ $this->forum['id'] ]['close_topic'] )
			{
				$this->can_close_topics = 1;
			}
			
			if ( $mod[ $this->forum['id'] ]['open_topic'] )
			{
				$this->can_open_topics  = 1;
			}
		}
	}
	
	/**
	 * Builds output array for sub forums
	 *
	 * @access	public
	 * @return	array
	 **/
	public function showSubForums()
	{	
		require_once( IPSLib::getAppDir( 'forums' ) . '/modules_public/forums/boards.php' );
		$boards = new public_forums_forums_boards();
		$boards->makeRegistryShortcuts( $this->registry );
		
		return $boards->showSubForums( $this->request['f'] );
    }

	/**
	 * Forum view check for authentication
	 *
	 * @access	public
	 * @return	string		HTML
	 **/
	public function showForum()
	{
		// are we checking for user authentication via the log in form
		// for a private forum w/password protection?
	
		if( isset( $this->request['L'] ) AND $this->request['L'] > 1 )
		{
			$this->registry->getClass('output')->showError( 'forums_why_l_gt_1', 10336 );
		}
		
		return ( isset( $this->request['L'] ) AND $this->request['L'] == 1 ) ? $this->authenticateUser() : $this->renderForum();
	}
	
	/**
	 * Authenicate the log in for a password protected forum
	 *
	 * @access	public
	 * @return	void
	 **/
	public function authenticateUser()
	{
		if( $this->request['f_password'] == "" )
		{
			$this->registry->getClass('output')->showError( 'forums_pass_blank', 10337 );
		}
		
		if( $this->request['f_password'] != $this->forum['password'] )
		{
			$this->registry->getClass('output')->showError( 'forums_wrong_pass', 10338 );
		}
		
		IPSCookie::set( "ipbforumpass_".$this->forum['id'], md5( $this->request['f_password'] ) );
		
		$this->registry->getClass('output')->redirectScreen( $this->lang->words['logged_in'] , "{$this->settings['base_url']}showforum={$this->forum['id']}", $this->forum['name_seo'] );
	}
	
	/**
	 * Builds an array of forum data for use in the output template
	 *
	 * @access	public
	 * @return	array
	 **/
	public function renderForum()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$this->request['st'] =  $this->request['changefilters'] ? 0 : ( isset($this->request['st']) ? intval($this->request['st']) : 0 );
		$announce_data  = array();
		$topic_data     = array();
		$other_data     = array();
		$multi_mod_data = array();
		$footer_filter  = array();
		
		//-----------------------------------------
		// Show?
		//-----------------------------------------
		
		if ( isset(  $this->request['show'] ) AND $this->request['show'] == 'sinceLastVisit' )
		{
			$this->request['prune_day'] = 200;
		}
		
		//-----------------------------------------
	    // Are we actually a moderator for this forum?
	    //-----------------------------------------
	    
		$mod = $this->memberData['forumsModeratorData'];
		
	    if ( ! $this->memberData['g_is_supmod'] )
	    {
	    	if( ! isset( $mod[ $this->forum['id'] ] ) OR ! is_array( $mod[ $this->forum['id'] ] ) )
	    	{
	    		$this->memberData['is_mod'] = 0;
	    	}
	    }
	    
		//-----------------------------------------
		// Announcements
		//-----------------------------------------
		
		if( is_array( $this->registry->cache()->getCache('announcements') ) and count( $this->registry->cache()->getCache('announcements') ) )
		{
			$announcements = array();
			
			foreach( $this->registry->cache()->getCache('announcements') as $announce )
			{
				$order = $announce['announce_start'] ? $announce['announce_start'].','.$announce['announce_id'] : $announce['announce_id'];
				
				if(  $announce['announce_forum'] == '*' )
				{
					$announcements[ $order ] = $announce;
				}
				else if( strstr( ','.$announce['announce_forum'].',', ','.$this->forum['id'].',' ) )
				{
					$announcements[ $order ] = $announce;
				}
			}
			
			if( count( $announcements ) )
			{
				//-----------------------------------------
				// sort by start date
				//-----------------------------------------
				
				krsort( $announcements );
				
				foreach( $announcements as $announce )
				{
					if ( $announce['announce_start'] )
					{
						$announce['announce_start'] = gmstrftime( '%x', $announce['announce_start'] );
					}
					else
					{
						$announce['announce_start'] = '--';
					}
					
					$announce['announce_title'] = IPSText::stripslashes($announce['announce_title']);
					$announce['forum_id']       = $this->forum['id'];
					$announce['announce_views'] = intval($announce['announce_views']);
					$announce_data[] = $announce;
				}
				
				$this->forum['_showAnnouncementsBar'] = 1;
			}
		}
		
		//-----------------------------------------
		// Read topics
		//-----------------------------------------
		
		$First   = intval($this->request['st']);
		
		//-----------------------------------------
		// Sort options
		//-----------------------------------------
		
		$cookie_prune = IPSCookie::get( $this->forum['id']."_prune_day" );
		$cookie_sort  = IPSCookie::get( $this->forum['id']."_sort_key" );
		$cookie_sortb = IPSCookie::get( $this->forum['id']."_sort_by" );
		$cookie_fill  = IPSCookie::get( $this->forum['id']."_topicfilter" );
		
		$prune_value	= $this->selectVariable( array( 
												1 => ! empty( $this->request['prune_day'] ) ? $this->request['prune_day'] : NULL,
												2 => !empty($cookie_prune) ? $cookie_prune : NULL,
												3 => $this->forum['prune']        ,
												4 => '100' )
									    );

		$sort_key		= $this->selectVariable( array(
												1 => ! empty( $this->request['sort_key'] ) ? $this->request['sort_key'] : NULL,
												2 => !empty($cookie_sort) ? $cookie_sort : NULL,
												3 => $this->forum['sort_key'],
												4 => 'last_post'            )
									   );

		$sort_by		= $this->selectVariable( array(
												1 => ! empty( $this->request['sort_by'] ) ? $this->request['sort_by'] : NULL,
												2 => !empty($cookie_sortb) ? $cookie_sortb : NULL,
												3 => $this->forum['sort_order'] ,
												4 => 'Z-A'                      )
									   );
									 
		$topicfilter	= $this->selectVariable( array(
												1 => ! empty( $this->request['topicfilter'] ) ? $this->request['topicfilter'] : NULL,
												2 => !empty($cookie_fill) ? $cookie_fill : NULL,
												3 => $this->forum['topicfilter'] ,
												4 => 'all'                      )
									   );
//print_r($this->request);exit;
//print($cookie_sort);exit;
		if( ! empty( $this->request['remember'] ) )
		{
			if( $this->request['prune_day'] )
			{
				IPSCookie::set( $this->forum['id']."_prune_day", $this->request['prune_day'] );
			}
			
			if( $this->request['sort_key'] )
			{
				IPSCookie::set( $this->forum['id']."_sort_key", $this->request['sort_key'] );
			}	
			
			if( $this->request['sort_by'] )
			{
				IPSCookie::set( $this->forum['id']."_sort_by", $this->request['sort_by'] );
			}	
			
			if( $this->request['topicfilter'] )
			{
				IPSCookie::set( $this->forum['id']."_topicfilter", $this->request['topicfilter'] );
			}
		}
		//print $sort_key;exit;
		//-----------------------------------------
		// Figure out sort order, day cut off, etc
		//-----------------------------------------
		
		$Prune = $prune_value < 100 ? (time() - ($prune_value * 60 * 60 * 24)) : ( ( $prune_value == 200 AND $this->memberData['member_id'] ) ? $this->memberData['last_visit'] : 0 );

		$sort_keys   =  array( 'last_post'         => 'sort_by_date',
							   'last_poster_name'  => 'sort_by_last_poster',
							   'title'             => 'sort_by_topic',
							   'starter_name'      => 'sort_by_poster',
							   'start_date'        => 'sort_by_start',
							   'topic_hasattach'   => 'sort_by_attach',
							   'posts'             => 'sort_by_replies',
							   'views'             => 'sort_by_views',
							   
							 );

		$prune_by_day = array( '1'    => 'show_today',
							   '5'    => 'show_5_days',
							   '7'    => 'show_7_days',
							   '10'   => 'show_10_days',
							   '15'   => 'show_15_days',
							   '20'   => 'show_20_days',
							   '25'   => 'show_25_days',
							   '30'   => 'show_30_days',
							   '60'   => 'show_60_days',
							   '90'   => 'show_90_days',
							   '100'  => 'show_all',
							   '200'  => 'show_last_visit'
							 );

		$sort_by_keys = array( 'Z-A'  => 'descending_order',
                         	   'A-Z'  => 'ascending_order',
                             );
                             
        $filter_keys  = array( 'all'    => 'topicfilter_all',
        					   'open'   => 'topicfilter_open',
        					   'hot'    => 'topicfilter_hot',
        					   'poll'   => 'topicfilter_poll',
        					   'locked' => 'topicfilter_locked',
        					   'moved'  => 'topicfilter_moved',
        					 );
        					 
        if( $this->memberData['member_id'] )
        {
        	$filter_keys['istarted'] = 'topicfilter_istarted';
        	$filter_keys['ireplied'] = 'topicfilter_ireplied';
        }

        //-----------------------------------------
        // check for any form funny business by wanna-be hackers
		//-----------------------------------------
		
		if( ( ! isset( $filter_keys[$topicfilter] ) ) or ( ! isset( $sort_keys[$sort_key] ) ) or ( ! isset( $prune_by_day[$prune_value] ) ) or ( ! isset( $sort_by_keys[$sort_by] ) ) )
		{
			$this->registry->getClass('output')->showError( 'forums_bad_filter', 10339 );
	    }
	    
	    $r_sort_by = $sort_by == 'A-Z' ? 'ASC' : 'DESC';
	    
        //-----------------------------------------
        // If sorting by starter, add secondary..
		//-----------------------------------------
		$sort_key_chk = $sort_key;
		
		if( $sort_key == 'starter_name' )
		{			
			$sort_key	= "starter_name {$r_sort_by}, t.last_post DESC";
			$r_sort_by	= '';
		}
	    
	    //-----------------------------------------
	    // Additional queries?
	    //-----------------------------------------
	    
	    $add_query_array = array();
	    $add_query       = "";
	    
	    switch( $topicfilter )
	    {
	    	case 'all':
	    		break;
	    	case 'open':
	    		$add_query_array[] = "t.state='open'";
	    		break;
	    	case 'hot':
	    		$add_query_array[] = "t.state='open' AND t.posts + 1 >= ".intval($this->settings['hot_topic']);
	    		break;
	    	case 'locked':
	    		$add_query_array[] = "t.state='closed'";
	    		break;
	    	case 'moved':
	    		$add_query_array[] = "t.state='link'";
	    		break;
	    	case 'poll':
	    		$add_query_array[] = "(t.poll_state='open' OR t.poll_state=1)";
	    		break;
	    	default:
	    		break;
	    }
	    
	    if( ! $this->memberData['g_other_topics'] or $topicfilter == 'istarted' OR ( ! $this->forum['can_view_others'] AND ! $this->memberData['is_mod'] ) )
		{
            $add_query_array[] = "t.starter_id='".$this->memberData['member_id']."'";
		}
		
		$_SQL_EXTRA		= '';
		$_SQL_APPROVED	= '';
		$_SQL_AGE_PRUNE	= '';
		
		if( count($add_query_array) )
		{
			$_SQL_EXTRA	= ' AND '. implode( ' AND ', $add_query_array );
		}
		
		//-----------------------------------------
		// Moderator?
		//-----------------------------------------
		
		if( ! $this->memberData['is_mod'] )
		{
			$_SQL_APPROVED	= ' AND t.approved=1';
		}
		else
		{
			$_SQL_APPROVED	= '';		//' AND t.approved IN (0,1)';	If you are an admin, it's not needed and eliminates a filesort in some cases
		}
		
		if ( $Prune )
		{
			if ( $prune_value == 200 )
			{
				/* Just new content, don't show pinned, please */
				$_SQL_AGE_PRUNE	= " AND (t.last_post > {$Prune})";
			}
			else
			{
				$_SQL_AGE_PRUNE	= " AND (t.pinned=1 or t.last_post > {$Prune})";
			}
		}
		
		//-----------------------------------------
		// Query the database to see how many topics there are in the forum
		//-----------------------------------------
		
		if( $topicfilter == 'ireplied' )
		{
			//-----------------------------------------
			// Checking topics we've replied to?
			//-----------------------------------------

			$this->DB->build( array(
										'select'	=> 'COUNT(' . $this->DB->buildDistinct( 'p.topic_id' ) . ') as max',
										'from'		=> array( 'topics' => 't' ),
										'where'		=> " t.forum_id={$this->forum['id']} AND p.author_id=".$this->memberData['member_id'] . " AND p.new_topic=0" . $_SQL_APPROVED . $_SQL_AGE_PRUNE,
										'add_join'	=> array(
															array(
																'from'	=> array( 'posts' => 'p' ),
																'where'	=> 'p.topic_id=t.tid',
																)
															)
								)		);
			$this->DB->execute();
			
			$total_possible = $this->DB->fetch();
		}
		else if ( ( $_SQL_EXTRA or $_SQL_AGE_PRUNE ) and ! $this->request['modfilter'] )
		{
			$this->DB->build( array(  'select' => 'COUNT(*) as max',
									  'from'   => 'topics t',
									  'where'  => "t.forum_id=" . $this->forum['id'] . $_SQL_APPROVED . $_SQL_AGE_PRUNE . $_SQL_EXTRA ) );

			$this->DB->execute();
			
			$total_possible = $this->DB->fetch();
		}
		else 
		{
			$total_possible['max'] = $this->memberData['is_mod'] ? $this->forum['topics'] + $this->forum['queued_topics'] : $this->forum['topics'];
			$Prune = 0;
		}
		
		//-----------------------------------------
		// Generate the forum page span links
		//-----------------------------------------
		
		$this->forum['SHOW_PAGES'] = $this->registry->getClass('output')->generatePagination( array( 
																									'totalItems'        => $total_possible['max'],
																									'itemsPerPage'      => $this->settings['display_max_topics'],
																									'currentStartValue' => $this->request['st'],
																									'seoTitle'			=> $this->forum['name_seo'],
																									'baseUrl'           => "showforum=".$this->forum['id']."&amp;prune_day={$prune_value}&amp;sort_by={$sort_by}&amp;sort_key={$sort_key_chk}&amp;topicfilter={$topicfilter}",
																							)	);

		//-----------------------------------------
		// Start printing the page
		//-----------------------------------------

		$other_data = array( 'forum_data'       => $this->forum,
							 'can_edit_topics'  => $this->can_edit_topics,
							 'can_open_topics'  => $this->can_open_topics,
							 'can_close_topics' => $this->can_close_topics );
				
		$total_topics_printed = 0;
		
		//-----------------------------------------
		// Get main topics
		//-----------------------------------------
		
		$topic_array = array();
		$topic_ids   = array();
		$topic_sort  = "";
        
        //-----------------------------------------
        // Mod filter?
        //-----------------------------------------
        
        $this->request['modfilter'] = isset( $this->request['modfilter'] ) ? $this->request['modfilter'] : '';
        
		if( $this->request['modfilter'] == 'invisible_topics' and $this->memberData['is_mod'] )
		{
			$topic_sort = 't.approved asc,';
		}
		else if( $this->request['modfilter'] == 'invisible_posts' and $this->memberData['is_mod'] )
		{
			$topic_sort = 't.topic_queuedposts desc,';
		}
		else if( $this->request['modfilter'] == 'all' and $this->memberData['is_mod'] )
		{
			$topic_sort = 't.approved asc, t.topic_queuedposts desc,';
		}
		
		//-----------------------------------------
		// Cut off?
		//-----------------------------------------
		
		$parse_dots = 1;
		
		if( $topicfilter == 'ireplied' )
		{
			//-----------------------------------------
			// Checking topics we've replied to?
			// No point in getting dots again...
			//-----------------------------------------
			
			$parse_dots = 0;
			
			// For some reason, mySQL doesn't like the distinct + t.* being in reverse order...
			$this->DB->build( array(
										'select'	=> $this->DB->buildDistinct( 'p.author_id' ),
										'from'		=> array( 'topics' => 't' ),
										'where'		=> "t.forum_id=" . $this->forum['id'] . " AND t.pinned IN (0,1)" . $_SQL_APPROVED . $_SQL_AGE_PRUNE . " AND p.new_topic=0",
										'order'		=> "t.pinned desc,{$topic_sort} t.{$sort_key} {$r_sort_by}",
										'limit'		=> array( intval($First), intval($this->settings['display_max_topics']) ),
										'add_join'	=> array(
															array(
																'select'	=> 't.*',
																'from'		=> array( 'posts' => 'p' ),
																'where'		=> 'p.topic_id=t.tid AND p.author_id=' . $this->memberData['member_id'],
																)
															)
								)		);
			$this->DB->execute();
		}
		else
		{
			$this->DB->build( array( 
											'select' => '*',
											'from'   => 'topics t',
											'where'  =>  "t.forum_id=" . $this->forum['id'] . " AND t.pinned IN (0,1)" . $_SQL_APPROVED . $_SQL_AGE_PRUNE . $_SQL_EXTRA,
											'order'  => 't.pinned DESC, '.$topic_sort.' t.'.$sort_key .' '. $r_sort_by,
											'limit'  => array( intval($First), $this->settings['display_max_topics'] )
									)	);
			$this->DB->execute();
		}
		
		while( $t = $this->DB->fetch() )
		{
			$topic_array[ $t['tid'] ] = $t;
			$topic_ids[ $t['tid'] ]   = $t['tid'];
		}
			
		ksort( $topic_ids );
		
		//-----------------------------------------
		// Are we dotty?
		//-----------------------------------------
		
		if( ( $this->settings['show_user_posted'] == 1 ) and ( $this->memberData['member_id'] ) and ( count($topic_ids) ) and ( $parse_dots ) )
		{
			$this->DB->build( array( 
									'select' => 'author_id, topic_id',
									'from'   => 'posts',
									'where'  => 'author_id=' . $this->memberData['member_id'] . ' AND topic_id IN(' . implode( ',', $topic_ids ) . ')',
							)	);
									  
			$this->DB->execute();
			
			while( $p = $this->DB->fetch() )
			{
				if ( is_array( $topic_array[ $p['topic_id'] ] ) )
				{
					$topic_array[ $p['topic_id'] ]['author_id'] = $p['author_id'];
				}
			}
		}
		
		//-----------------------------------------
		// Are we tracking watched stuff
		//-----------------------------------------
		
		if( ( $this->settings['cpu_watch_update'] == 1 ) and ( $this->memberData['member_id'] ) and ( count($topic_ids) ) and ( $parse_dots ) )
		{
			$this->DB->build( array( 
									'select' => 'topic_id, trid as trackingTopic',
									'from'   => 'tracker',
									'where'  => 'member_id=' . $this->memberData['member_id'] . ' AND topic_id IN(' . implode( ',', $topic_ids ) . ')',
							)	);
									  
			$this->DB->execute();
			
			while( $p = $this->DB->fetch() )
			{
				if ( is_array( $topic_array[ $p['topic_id'] ] ) )
				{
					$topic_array[ $p['topic_id'] ]['trackingTopic'] = 1;
				}
			}
		}
		
		//-----------------------------------------
		// Show meh the topics!
		//-----------------------------------------
		
		foreach( $topic_array as $topic )
		{
			if ( $topic['pinned'] )
			{
				$this->pinned_topic_count++;
			}
			
			$topic_data[ $topic['tid'] ] = $this->renderEntry( $topic );
			
			$total_topics_printed++;
		}
		
		//-----------------------------------------
		// Finish off the rest of the page  $filter_keys[$topicfilter]))
		//-----------------------------------------
		
		$sort_by_html	= "";
		$sort_key_html	= "";
		$prune_day_html	= "";
		$filter_html	= "";
		
		foreach( $sort_by_keys as $k => $v )
		{
			$sort_by_html   .= $k == $sort_by      ? "<option value='$k' selected='selected'>{$this->lang->words[ $sort_by_keys[ $k ] ]}</option>\n"
											       : "<option value='$k'>{$this->lang->words[ $sort_by_keys[ $k ] ]}</option>\n";
		}
	
		foreach( $sort_keys as  $k => $v )
		{
			$sort_key_html  .= $k == $sort_key_chk ? "<option value='$k' selected='selected'>{$this->lang->words[ $sort_keys[ $k ] ]}</option>\n"
											       : "<option value='$k'>{$this->lang->words[ $sort_keys[ $k ] ]}</option>\n";
		}
		
		foreach( $prune_by_day as  $k => $v )
		{
			$prune_day_html .= $k == $prune_value  ? "<option value='$k' selected='selected'>{$this->lang->words[ $prune_by_day[ $k ] ]}</option>\n"
												   : "<option value='$k'>{$this->lang->words[ $prune_by_day[ $k ] ]}</option>\n";
		}
		
		foreach( $filter_keys as  $k => $v )
		{
			$filter_html    .= $k == $topicfilter  ? "<option value='$k' selected='selected'>{$this->lang->words[ $filter_keys[ $k ] ]}</option>\n"
												   : "<option value='$k'>{$this->lang->words[ $filter_keys[ $k ] ]}</option>\n";
		}
	
		$footer_filter['sort_by']      = $sort_key_html;
		$footer_filter['sort_order']   = $sort_by_html;
		$footer_filter['sort_prune']   = $prune_day_html;
		$footer_filter['topic_filter'] = $filter_html;
		
		if( $this->memberData['is_mod'] )
		{
			$count = 0;
			$other_pages = 0;
			
			if( $this->request['selectedtids'] != "" )
			{
				$tids = explode( ",",$this->request['selectedtids'] );
				
				if( is_array( $tids ) AND count( $tids ) )
				{
					foreach( $tids as $tid )
					{
						if( $tid != '' )
						{
							if( ! isset($topic_array[ $tid ]) )
							{
								$other_pages++;
							}
							
							$count++;
						}
					}
				}
			}
			
			$this->lang->words['f_go'] .= " ({$count})";
			
			if( $other_pages )
			{
				$this->lang->words['f_go'] .= " ({$other_pages} " . $this->lang->words['jscript_otherpage'] . ")";
			}
		}
	
		//-----------------------------------------
		// Multi-moderation?
		//-----------------------------------------
		
		if( $this->memberData['is_mod'] )
		{
			$mm_array = $this->registry->getClass('class_forums')->getMultimod( $this->forum['id'] );
			
			if ( is_array( $mm_array ) and count( $mm_array ) )
			{
				foreach( $mm_array as $m )
				{
					$multi_mod_data[] = $m;
				}
			}
		}
		
		//-----------------------------------------
		// Need to update topics?
		//-----------------------------------------
		
		if( count( $this->update_topics_open ) )
		{
			$this->DB->update( 'topics', array( 'state' => 'open' ), 'tid IN ('.implode( ",", $this->update_topics_open ) .')' );
		}
		
		if( count( $this->update_topics_close ) )
		{
			$this->DB->update( 'topics', array( 'state' => 'closed' ), 'tid IN ('.implode( ",", $this->update_topics_close ) .')' );
		}
		
		return array( 'announce_data'  => $announce_data,
					  'topic_data'     => $topic_data,
					  'other_data'     => $other_data,
					  'multi_mod_data' => $multi_mod_data,
					  'footer_filter'  => $footer_filter,
					  'active_users'   => ( $this->settings['no_au_forum'] ) ? array( '_done' => 0 ) : $this->_generateActiveUserData() );
    }
    
	/**
	 * Generate active user data
	 *
	 * @access	private
	 * @return	array 	Array of data (not the most helpful description ever. Sorry
	 * @author	Matt
	 */
	private function _generateActiveUserData()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$ar_time = time();
		$cached  = array();
		$guests  = array();
		$cut_off = ($this->settings['au_cutoff'] != "") ? $this->settings['au_cutoff'] * 60 : 900;
		$time    = time() - $cut_off;
		$active  = array( 'guests' => 0, 'anon' => 0, 'members' => 0, 'names' => array() );
		$rows    = array( $ar_time => array( 'login_type'   => substr($this->memberData['login_anonymous'],0, 1),
											 'id'		   => $this->member->session_id,
											 'location'	   => 'sf',
											 'running_time' => $ar_time,
											 'seo_name'     => $this->memberData['members_seo_name'],
											 'member_id'    => $this->memberData['member_id'],
											 'member_name'  => $this->memberData['members_display_name'],
											 'member_group' => $this->memberData['member_group_id'] ) );
											
		//-----------------------------------------
		// Get the users
		//-----------------------------------------
		
		$this->DB->build( array( 
									'select'	=> 's.member_id, s.member_name, s.member_group, s.id, s.login_type, s.location, s.running_time, s.uagent_type, s.seo_name',
									 'from'		=> array( 'sessions' => 's' ),
									 'where'	=> "s.location_2_type='forum' AND s.location_2_id={$this->forum['id']} AND s.running_time > {$time}	AND s.in_error=0",
									 'add_join'	=> array(
									 					array(
									 							'type'		=> 'left',
									 							'select'	=> 't.forum_id',
									 							'where'		=> 't.tid=s.location_1_id',
									 							'from'		=> array( 'topics' => 't' ),
									 						),
									 					),
							)		);
		$this->DB->execute();
		
		//-----------------------------------------
		// FETCH...
		//-----------------------------------------
		
		while ($r = $this->DB->fetch() )
		{
			$rows[ $r['running_time'].'.'.$r['id'] ] = $r;
		}
		
		krsort( $rows );

		//-----------------------------------------
		// PRINT...
		//-----------------------------------------
		
		foreach( $rows as $result )
		{
			$result['member_name'] = IPSLib::makeNameFormatted( $result['member_name'], $result['member_group'] );
			
			$last_date = $this->registry->getClass( 'class_localization')->getTime( $result['running_time'] );
			
			if ( $result['member_id'] == 0 OR ! $result['member_name'] )
			{
				if ( in_array( $result['id'], $guests ) )
				{
					continue;
				}
				
				//-----------------------------------------
				// Bot?
				//-----------------------------------------
				
				if ( $result['uagent_type'] == 'search' )
				{
					/* Skipping bot? */
					if ( ! $this->settings['spider_active'] )
					{
						continue;
					}
					
					//-----------------------------------------
					// Seen bot of this type yet?
					//-----------------------------------------
					
					if ( ! $cached[ $result['member_name'] ] )
					{
						if ( $this->settings['spider_anon'] )
						{
							if ( $this->memberData['g_access_cp'] )
							{
								$active['names'][] = array( 'id' => $result['member_id'], 'name' => $result['member_name'], 'seo' => $result['seo_name'] );
							}
						}
						else
						{
							$active['names'][] = array( 'id' => $result['member_id'], 'name' => $result['member_name'], 'seo' => $result['seo_name'] );
						}
						
						$cached[ $result['member_name'] ] = 1;
					}
					else
					{
						$active['guests']++;
						$guests[] = $result['id'];
					}
				}
				else
				{ 
					$active['guests']++;
					$guests[] = $result['id'];
				}
			}
			else
			{
				if (empty( $cached[ $result['member_id'] ] ) )
				{
					$cached[ $result['member_id'] ] = 1;
					
					$p_start = "";
					$p_end   = "";
					$p_title = sprintf( $this->lang->words['au_reading'], $last_date );
					
					if ( strstr( $result['current_module'], 'post' ) and $result['member_id'] != $this->memberData['member_id'] )
					{
						$p_start = "<span class='activeuserposting'>";
						$p_end   = "</span>";
						$p_title = sprintf( $this->lang->words['au_posting'], $last_date );
					}
					
					if ( ! $this->settings['disable_anonymous'] AND $result['login_type'] )
					{
						if ( $this->memberData['g_access_cp'] and ($this->settings['disable_admin_anon'] != 1) )
						{
							$active['names'][] = array( 'id' => $result['member_id'], 'name' => $result['member_name'] . '*', 'p_start' => $p_start, 'p_title' => $p_title, 'p_end' => $p_end, 'seo' => $result['seo_name'] );
							$active['anon']++;
						}
						else
						{
							$active['anon']++;
						}
					}
					else
					{
						$active['members']++;
						$active['names'][] = array( 'id' => $result['member_id'], 'name' => $result['member_name'], 'p_start' => $p_start, 'p_title' => $p_title, 'p_end' => $p_end, 'seo' => $result['seo_name'] );
					}
				}
			}
		}
		
		$active['_done'] = 1;
		
		return $active;
	}
	
	/**
	 * Parase Topic Data
	 *
	 * @access	public
	 * @param	array	$topic				Topic data
	 * @param	bool	$last_time_default	Use default "last read time"
	 * @return	array
	 **/
	public function parseTopicData( $topic, $last_time_default=true )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$topic['real_tid']   = $topic['tid'];
		$topic['_last_post'] = $topic['last_post'];
		
		//-----------------------------------------
		// Do we have an SEO title?
		//-----------------------------------------
		
		$_hasSEOTitle	= false;
		
		if( $topic['title_seo'] )
		{
			$_hasSEOTitle	= true;
		}
		
		$topic['title_seo'] = ( $topic['title_seo'] ) ? $topic['title_seo'] : IPSText::makeSeoTitle( $topic['title'] );
		
		/**
		 * Here we'll take the one query hit to update in order to speed it up in the future
		 */
		if ( ! $_hasSEOTitle AND $this->settings['use_friendly_urls'] )
		{
			$this->DB->update( 'topics', array( 'title_seo' => ( $topic['title_seo'] ) ? $topic['title_seo'] : '-' ), 'tid=' . $topic['tid'] );
		}
	
		//-----------------------------------------
		// Need to update this topic?
		//-----------------------------------------
		
		if ( $topic['state'] == 'open' )
		{
			if( !$topic['topic_open_time'] OR $topic['topic_open_time'] < $topic['topic_close_time'] )
			{
				if ( $topic['topic_close_time'] AND ( $topic['topic_close_time'] <= time() AND ( time() >= $topic['topic_open_time'] OR !$topic['topic_open_time'] ) ) )
				{
					$topic['state'] = 'closed';
					
					$this->update_topics_close[] = $topic['real_tid'];
				}
			}
			else if( $topic['topic_open_time'] OR $topic['topic_open_time'] > $topic['topic_close_time'] )
			{
				if ( $topic['topic_close_time'] AND ( $topic['topic_close_time'] <= time() AND time() <= $topic['topic_open_time'] ) )
				{
					$topic['state'] = 'closed';
					
					$this->update_topics_close[] = $topic['real_tid'];
				}
			}				
		}
		else if ( $topic['state'] == 'closed' )
		{
			if( !$topic['topic_close_time'] OR $topic['topic_close_time'] < $topic['topic_open_time'] )
			{
				if ( $topic['topic_open_time'] AND ( $topic['topic_open_time'] <= time() AND ( time() >= $topic['topic_close_time'] OR !$topic['topic_close_time'] ) ) )
				{
					$topic['state'] = 'open';
					
					$this->update_topics_open[] = $topic['real_tid'];
				}
			}
			else if( $topic['topic_close_time'] OR $topic['topic_close_time'] > $topic['topic_open_time'] )
			{
				if ( $topic['topic_open_time'] AND ( $topic['topic_open_time'] <= time() AND time() <= $topic['topic_close_time'] ) )
				{
					$topic['state'] = 'open';
					
					$this->update_topics_open[] = $topic['real_tid'];
				}
			}					
		}

		//-----------------------------------------
		// For polls we check last vote instead
		// @todo [Future] Show a diff icon for new vote + new reply, new vote + no new reply, etc.
		// Bug 16598: Need separate checks for icon vs getnewpost link
		//-----------------------------------------
		
		if( $topic['poll_state'] AND ( $topic['last_vote'] > $topic['last_post'] ) )
		{
			$is_read		= $this->registry->classItemMarking->isRead( array( 'forumID' => $topic['forum_id'], 'itemID' => $topic['tid'], 'itemLastUpdate' => $topic['last_vote'] ), 'forums' );
			$gotonewpost	= $this->registry->classItemMarking->isRead( array( 'forumID' => $topic['forum_id'], 'itemID' => $topic['tid'], 'itemLastUpdate' => $topic['last_post'] ), 'forums' );
		}
		else
		{
			$is_read		= $this->registry->classItemMarking->isRead( array( 'forumID' => $topic['forum_id'], 'itemID' => $topic['tid'], 'itemLastUpdate' => $topic['last_post'] ), 'forums' );
			$gotonewpost	= $is_read;
		}
		
		//-----------------------------------------
		// Yawn
		//-----------------------------------------

		$topic['last_poster'] = $topic['last_poster_id'] ? IPSLib::makeProfileLink( $topic['last_poster_name'], $topic['last_poster_id'], $topic['seo_last_name'] ) : $this->settings['guest_name_pre'] . $topic['last_poster_name'] . $this->settings['guest_name_suf'];

		$topic['starter']     = $topic['starter_id']     ? IPSLib::makeProfileLink( $topic['starter_name'], $topic['starter_id'], $topic['seo_first_name'] ) : $this->settings['guest_name_pre'] . $topic['starter_name'] . $this->settings['guest_name_suf'];
	 
		$topic['prefix']  = $topic['poll_state'] ? $this->registry->getClass('output')->getTemplate('forum')->topicPrefixWrap( $this->settings['pre_polls'] ) : '';
		
		$show_dots = "";
		
		if ( $this->memberData['member_id'] and ( isset($topic['author_id']) AND $topic['author_id'] ) )
		{
			$show_dots = 1;
		}
	
		$topic['folder_img']     = $this->registry->getClass('class_forums')->fetchTopicFolderIcon( $topic, $show_dots, $is_read );
		
		/* SKINNOTE: Change these so that the link is built in the skin, not here */
		$topic['topic_icon']     = $topic['icon_id']  ? '<img src="' . $this->settings['mime_img'] . '/style_extra/post_icons/icon' . $topic['icon_id'] . '.gif" border="0" alt="" />'
													  : '&nbsp;';

		$topic['topic_icon'] = $topic['pinned'] ? '<{B_PIN}>' : $topic['topic_icon'];
		
		$topic['start_date'] = $this->registry->getClass( 'class_localization')->getDate( $topic['start_date'], 'LONG' );
	
		//-----------------------------------------
		// Pages 'n' posts
		//-----------------------------------------
		
		$pages = 1;
		$topic['PAGES'] = "";
		
		if ( $this->memberData['is_mod'] )
		{
			$topic['posts'] += intval($topic['topic_queuedposts']);
		}
		
		if ($topic['posts'])
		{
			$mode = IPSCookie::get( 'topicmode' );
			
			if( $mode == 'threaded' )
			{
				$this->settings['display_max_posts'] =  $this->settings['threaded_per_page'] ;
			}
			
			if ( (($topic['posts'] + 1) % $this->settings['display_max_posts']) == 0 )
			{
				$pages = ($topic['posts'] + 1) / $this->settings['display_max_posts'];
			}
			else
			{
				$number = ( ($topic['posts'] + 1) / $this->settings['display_max_posts'] );
				$pages = ceil( $number);
			}
		}
		
		if ( $pages > 1 )
		{
			for ( $i = 0 ; $i < $pages ; ++$i )
			{
				$real_no = $i * $this->settings['display_max_posts'];
				$page_no = $i + 1;
				
				if ( $page_no == 4 and $pages > 4 )
				{
					$topic['pages'][] = array( 'last'   => 1,
					 					       'st'     => ($pages - 1) * $this->settings['display_max_posts'],
					  						   'page'   => $pages );
					break;
				}
				else
				{
					$topic['pages'][] = array( 'last' => 0,
											   'st'   => $real_no,
											   'page' => $page_no );
				}
			}
		}
		
		//-----------------------------------------
		// Format some numbers
		//-----------------------------------------
		
		$topic['posts']  = $this->registry->getClass('class_localization')->formatNumber( intval($topic['posts']) );
		$topic['views']	 = $this->registry->getClass('class_localization')->formatNumber( intval($topic['views']) );
		
		//-----------------------------------------
		// Jump to latest post / last time stuff...
		//-----------------------------------------
		
		if ( !$gotonewpost )
		{
			$topic['go_new_post']  = true;
		}
		else
		{
			$topic['go_new_post']  = false;
		}
	
		$topic['last_post']  = $this->registry->getClass( 'class_localization')->getDate( $topic['last_post'], 'SHORT' );
		
		//-----------------------------------------
		// Linky pinky!
		//-----------------------------------------
			
		if ($topic['state'] == 'link')
		{
			$t_array = explode("&", $topic['moved_to']);
			$topic['tid']       = $t_array[0];
			$topic['forum_id']  = $t_array[1];
			$topic['title']     = $topic['title'];
			$topic['views']     = '--';
			$topic['posts']     = '--';
			$topic['prefix']    = $this->registry->getClass('output')->getTemplate('forum')->topicPrefixWrap( $this->settings['pre_moved'] );
			$topic['go_new_post'] = false;
		}
		else
		{
			$topic['_posts'] = $topic['posts'];
			$topic['posts'] = $this->registry->getClass('output')->getTemplate('forum')->who_link($topic['tid'], $topic['posts']);
		}
		
		$topic['_hasqueued'] = 0;
		$mod = $this->memberData['forumsModeratorData'];
		$mod = $mod ? $mod : array();
		
		if ( ( $this->memberData['g_is_supmod'] or
				($mod[ $topic['forum_id'] ]['post_q'] AND $mod[ $topic['forum_id'] ]['post_q'] == 1) ) and 
				( $topic['topic_queuedposts'] ) 
			)
		{
			$topic['_hasqueued'] = 1;
		}
		
		//-----------------------------------------
		// Topic rating
		//-----------------------------------------
		
	    $topic['_rate_img']   = '';
	    
	    if ( isset($this->forum['forum_allow_rating']) AND $this->forum['forum_allow_rating'] )
		{
			if ( $topic['topic_rating_total'] )
			{
				$topic['_rate_int'] = round( $topic['topic_rating_total'] / $topic['topic_rating_hits'] );
			}
			
			//-----------------------------------------
			// Show image?
			//-----------------------------------------
			
			if ( ( $topic['topic_rating_hits'] >= $this->settings['topic_rating_needed'] ) AND ( $topic['_rate_int'] ) )
			{
				$topic['_rate_img']  = $this->registry->getClass('output')->getTemplate('forum')->topic_rating_image( $topic['_rate_int'] );
			}
		}
		
		//-----------------------------------------
		// Already switched on?
		//-----------------------------------------
		
		if ( $this->memberData['is_mod'] )
		{
			if ( $this->request['selectedtids'] )
			{
				if ( strstr( ','.$this->request['selectedtids'].',', ','.$topic['tid'].',' ) )
				{
					$topic['tidon'] = 1;
				}
				else
				{
					$topic['tidon'] = 0;
				}
			}
		}
		
		return $topic;
	}
	
	/**
	 * Returns an array of topic data
	 *
	 * @access	public
	 * @param	array 	Topic entry
	 * @return	array
	 **/
	public function renderEntry( $topic )
	{
		$topic = $this->parseTopicData( $topic );
		
		$topic['PAGES']			     = isset($topic['PAGES']) 		? $topic['PAGES'] 		: '';
		$topic['prefix']		     = isset($topic['prefix'])		? $topic['prefix']		: '';
		$topic['attach_img'] 	     = isset($topic['attach_img']) 	? $topic['attach_img'] 	: '';
		$topic['_hasqueued'] 	     = isset($topic['_hasqueued']) 	? $topic['_hasqueued'] 	: '';
		$topic['tidon']		  	     = isset($topic['tidon'])		? $topic['tidon']		: 0;
		$topic['_show_announce_bar'] = 0;
		$topic['_show_pin_bar']      = 0;
		$topic['_show_topics_bar']   = 0;
			
		$p_start    = "";
		$p_end      = "";
		
		$this->forum['_pinBarShown'] = 0;
		
		if( $topic['pinned'] == 1 )
		{
			$topic['prefix'] = $this->registry->getClass('output')->getTemplate('forum')->topicPrefixWrap( $this->settings['pre_pinned'] );
			
			if ( $this->forum['_pinBarShown'] == 0 )
			{
				$topic['_show_pin_bar']      = 1;
				$this->forum['_pinBarShown'] = 1;
			}
			
			return $topic;
		}
		else
		{
			//-----------------------------------------
			// This is not a pinned topic, so lets check to see if we've
			// printed the footer yet.
			//-----------------------------------------
			
			if( $this->forum['_pinBarShown'] == 1 OR $this->forum['_showAnnouncementsBar'] )
			{
				//-----------------------------------------
				// Nope, so..
				//-----------------------------------------
				
				$topic['_show_topics_bar']            = 1;
				$this->forum['_showAnnouncementsBar'] = 0;
				$this->forum['_pinBarShown']          = 0;
			}
			
			return $topic;
		}
	}
	
	/**
	 * Given an array of possible variables, the first one found is returned
	 *
	 * @access	public
	 * @param	array 	Mixed variables
	 * @return	mixed 	First variable from the array
	 * @since	2.0
	 */
    public static function selectVariable($array)
    {
    	if ( !is_array($array) ) return -1;

    	ksort($array);

    	$chosen = -1;

    	foreach ($array as $v)
    	{
    		if ( isset($v) )
    		{
    			$chosen = $v;
    			break;
    		}
    	}

    	return $chosen;
    }
}