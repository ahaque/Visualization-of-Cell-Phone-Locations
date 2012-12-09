<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Board Index View
 * Last Updated: $Date: 2009-08-30 23:34:46 -0400 (Sun, 30 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage  Forums 
 * @version		$Rev: 5064 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_forums_forums_boards extends ipsCommand
{
	/**
	 * Main Execution Function
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	void		[Outputs to screen/redirects]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------

		$chat_html = '';
		$news_data = array();
		
		$this->registry->getClass('class_localization')->loadLanguageFile( array( 'public_boards' ) );
		
		if (! $this->memberData['member_id'] )
		{
			$this->request['last_visit'] = time();
		}
		
		//-----------------------------------------
		// What are we doing?
		//-----------------------------------------
		
		$cat_data = $this->processAllCategories();
		
		//-----------------------------------------
		// Add in show online users
		//-----------------------------------------
		
		$active = $this->getActiveUserDetails();
		
		//-----------------------------------------
		// Check for news forum.
		//-----------------------------------------
		
		if ( isset( $this->registry->getClass('class_forums')->forum_by_id[ $this->settings['news_forum_id'] ]['newest_id']) AND $this->registry->getClass('class_forums')->forum_by_id[ $this->settings['news_forum_id'] ]['newest_id'] AND $this->settings['index_news_link'] )
		{
			$news_data = array( 
								'forum_id'	=> $this->settings['news_forum_id'],
								'title'		=> stripslashes($this->registry->getClass('class_forums')->forum_by_id[ $this->settings['news_forum_id'] ]['newest_title']),
								'seo_title' => IPSText::makeSeoTitle( $this->registry->getClass('class_forums')->forum_by_id[ $this->settings['news_forum_id'] ]['newest_title'] ),
								'id'		=> $this->registry->getClass('class_forums')->forum_by_id[ $this->settings['news_forum_id'] ]['newest_id'] 
							);
		}
	
		/* Check for sidebar hooks */
		$show_sidebar = false;
		
		if( is_array( $this->caches['hooks']['templateHooks'] ) )
		{
			foreach( $this->caches['hooks']['templateHooks'] as $hook )
			{
				foreach( $hook as $c )
				{
					if( $c['id'] == 'side_blocks' && $c['skinGroup'] == 'skin_boards' && $c['skinFunction'] == 'boardIndexTemplate' )
					{
						$show_sidebar = true;
						break 2;
					}
				}
			}
		}

		//-----------------------------------------
		// Show the template
		//-----------------------------------------
		
		$stats_info = $this->getTotalTextString();
		
		$template = $this->registry->getClass('output')->getTemplate('boards')->boardIndexTemplate(
																									$this->registry->getClass( 'class_localization')->getDate( $this->memberData['last_visit'], 'LONG' ),
																									array_merge( $active, array( 'text'    => $this->lang->words['total_word_string'],
																										   						 'posts'   => $this->total_posts,
																																 'active'  => $this->users_online,
																																 'members' => $this->total_members,
																																 'cut_off' => $this->settings['au_cutoff'],
																																 'info'	   => $stats_info ) ),
																									$this->getCalendarEvents(),
																									$this->getBirthdays(),
																									$chat_html,
																									$news_data,
																									$cat_data,
																									$show_sidebar );
		
		//-----------------------------------------
		// Print as normal
		//-----------------------------------------
		
		$this->registry->getClass('output')->setTitle( $this->settings['board_name'] );
		$this->registry->getClass('output')->addContent( $template );
        $this->registry->getClass('output')->sendOutput();
	}

	/**
	 * Builds an array of category data
	 *
	 * @access	public
	 * @param	integer		$fid	ID of the forum to get sub forums for
	 * @return	array
	 **/
	public function showSubForums( $fid )
	{
		/* INIT */
		$temp_html 	     = "";
		$sub_output      = "";
		$return_cat_data = array();
		$temp_cat_data   = array();

		if ( isset( $this->registry->getClass('class_forums')->forum_cache[ $fid ] ) AND is_array( $this->registry->getClass('class_forums')->forum_cache[ $fid ] ) )
		{
			$cat_data = $this->registry->getClass('class_forums')->forum_by_id[ $fid ];
			
			foreach( $this->registry->getClass('class_forums')->forum_cache[ $fid ] as $forum_data )
			{
				$forum_data['_queued_img'] 		= isset($forum_data['_queued_img'] ) 	? $forum_data['_queued_img'] 	: '';
				$forum_data['_queued_info']		= isset($forum_data['_queued_info'] ) 	? $forum_data['_queued_info'] 	: '';
				$forum_data['show_subforums'] 	= isset($forum_data['show_subforums'] ) ? $forum_data['show_subforums'] : '';
				$forum_data['last_unread'] 		= isset($forum_data['last_unread'] ) 	? $forum_data['last_unread'] 	: '';
				
				//-----------------------------------------
				// Get all subforum stats
				// and calculate
				//-----------------------------------------
				
				if ( $this->settings['forum_cache_minimum'] AND $this->settings['forum_cache_minimum'] )
				{
					$forum_data['description'] = "<!--DESCRIPTION:{$forum_data['id']}-->";
					$need_desc[] = $forum_data['id'];
				}
					
				if ( $forum_data['redirect_on'] )
				{
					$forum_data['redirect_hits']	= $this->registry->getClass('class_localization')->formatNumber( $forum_data['redirect_hits'] );
					
					$forum_data['redirect_target'] 	= isset($forum_data['redirect_target']) ? $forum_data['redirect_target'] : '_parent';
					$temp_cat_data[ $forum_data['id'] ] = $forum_data;
				}
				else
				{
					$temp_cat_data[ $forum_data['id'] ] = $this->registry->getClass('class_forums')->forumsFormatLastinfo( $this->registry->getClass('class_forums')->forumsCalcChildren( $forum_data['id'], $forum_data ) );
				}
			}
		}
		
		if ( count( $temp_cat_data ) )
		{
			$return_cat_data[] = array( 'cat_data'   => $cat_data,
										'forum_data' => $temp_cat_data );
		}
		
		return $return_cat_data;
    }
    
	/**
	 * Builds an array of category data for output
	 *
	 * @access	public
	 * @return	array
	 **/
	public function processAllCategories()
	{
		/* INIT */
		$return_cat_data = array();
		$need_desc       = array();
		$root            = array();
		$parent          = array();
		
		//-----------------------------------------
		// Want to view categories?
		//-----------------------------------------
		
		if ( ! empty( $this->request['c'] ) )
		{
			foreach( explode( ",", $this->request['c'] ) as $c )
			{
				$c = intval( $c );
				$i = $this->registry->getClass('class_forums')->forum_by_id[ $c ]['parent_id'];
				
				$root[ $i ]   = $i;
				$parent[ $c ] = $c;
			}
		}
		
		if ( ! count( $root ) )
		{
			$root[] = 'root';
		}
		
		foreach( $root as $root_id )
		{
			if( is_array( $this->registry->class_forums->forum_cache[ $root_id ] ) and count( $this->registry->class_forums->forum_cache[ $root_id ] ) )
			{
				foreach( $this->registry->class_forums->forum_cache[ $root_id ] as $id => $forum_data )
				{
					$temp_cat_data = array();
					
					//-----------------------------------------
					// Only showing certain root forums?
					//-----------------------------------------
					
					if( count( $parent ) )
					{
						if( ! in_array( $id, $parent ) )
						{
							continue;
						}
					}
					
					$cat_data = $forum_data;
					
					if( isset( $this->registry->class_forums->forum_cache[ $forum_data['id'] ] ) AND is_array( $this->registry->class_forums->forum_cache[ $forum_data['id'] ] ) )
					{						
						foreach( $this->registry->class_forums->forum_cache[ $forum_data['id'] ] as $forum_data )
						{
							$forum_data['show_subforums'] 	= isset($forum_data['show_subforums']) 	? $forum_data['show_subforums'] : '';
							$forum_data['last_unread']		= isset($forum_data['last_unread'])		? $forum_data['last_unread']	: '';
							
							//-----------------------------------------
							// Get all subforum stats
							// and calculate
							//-----------------------------------------						
							
							if ( $forum_data['redirect_on'] )
							{
								$forum_data['redirect_target'] = isset($forum_data['redirect_target']) ? $forum_data['redirect_target'] : '_parent';
								
								$temp_cat_data[ $forum_data['id'] ] = $forum_data;
							}
							else
							{
								$temp_cat_data[ $forum_data['id'] ] = $this->registry->class_forums->forumsFormatLastinfo( $this->registry->class_forums->forumsCalcChildren( $forum_data['id'], $forum_data ) );
							}
							
						}
					}
					
					if ( count( $temp_cat_data ) )
					{
						$return_cat_data[] = array( 'cat_data'   => $cat_data,
													'forum_data' => $temp_cat_data );
					}
					
					$temp_cat_data = array();
				}
			}
		}

		return $return_cat_data;
	}

	/**
	 * Returns an array of active users
	 *
	 * @access	public
	 * @return	array
	 **/
	public function getActiveUserDetails()
	{
		$active = array( 'TOTAL'   => 0 ,
						 'NAMES'   => array(),
						 'GUESTS'  => 0 ,
						 'MEMBERS' => 0 ,
						 'ANON'    => 0 ,
					   );
		
		if ( $this->settings['show_active'] )
		{
			if( !$this->settings['au_cutoff'] )
			{
				$this->settings['au_cutoff'] = 15;
			}
			
			//-----------------------------------------
			// Get the users from the DB
			//-----------------------------------------
			
			$cut_off = $this->settings['au_cutoff'] * 60;
			$time    = time() - $cut_off;
			$rows    = array();
			$ar_time = time();
			
			if ( $this->memberData['member_id'] )
			{
				$rows = array( $ar_time.'.'.md5( microtime() ) => array( 
																		'id'           => 0,
																		'login_type'   => substr( $this->memberData['login_anonymous'], 0, 1),
																		'running_time' => $ar_time,
																		'seo_name'     => $this->memberData['members_seo_name'],
																		'member_id'    => $this->memberData['member_id'],
																		'member_name'  => $this->memberData['members_display_name'],
																		'member_group' => $this->memberData['member_group_id'] 
																		) 
							);
			}
			
			$this->DB->build( array( 
											'select' => 'id, member_id, member_name, seo_name, login_type, running_time, member_group, uagent_type',
											'from'   => 'sessions',
											'where'  => "running_time > $time",
								)	);
			$this->DB->execute();
			
			//-----------------------------------------
			// FETCH...
			//-----------------------------------------
			
			while ( $r = $this->DB->fetch() )
			{
				$rows[ $r['running_time'].'.'.$r['id'] ] = $r;
			}
			
			krsort( $rows );

			//-----------------------------------------
			// cache all printed members so we
			// don't double print them
			//-----------------------------------------
			
			$cached = array();
			
			foreach ( $rows as $result )
			{
				$last_date = $this->registry->getClass('class_localization')->getTime( $result['running_time'] );
				
				//-----------------------------------------
				// Bot?
				//-----------------------------------------
				
				if ( isset( $result['uagent_type'] ) && $result['uagent_type'] == 'search' )
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
								$active['NAMES'][] = IPSLib::makeNameFormatted( $result['member_name'], $result['member_group'] );
							}
						}
						else
						{
							$active['NAMES'][] = IPSLib::makeNameFormatted( $result['member_name'], $result['member_group'] );
						}
						
						$cached[ $result['member_name'] ] = 1;
					}
					else
					{
						//-----------------------------------------
						// Yup, count others as guest
						//-----------------------------------------
						
						$active['GUESTS']++;
					}
				}
				
				//-----------------------------------------
				// Guest?
				//-----------------------------------------
				
				else if ( ! $result['member_id'] OR ! $result['member_name'] )
				{
					$active['GUESTS']++;
				}
				
				//-----------------------------------------
				// Member?
				//-----------------------------------------
				
				else
				{
					if ( empty( $cached[ $result['member_id'] ] ) )
					{
						$cached[ $result['member_id'] ] = 1;

						$result['member_name'] = IPSLib::makeNameFormatted( $result['member_name'], $result['member_group'] );
						
						if ( ! $this->settings['disable_anonymous'] AND $result['login_type'] )
						{
							if ( $this->memberData['g_access_cp'] and ($this->settings['disable_admin_anon'] != 1) )
							{
								$active['NAMES'][] = "<a href='" . $this->registry->getClass('output')->buildSEOUrl( "showuser={$result['member_id']}", 'public', $result['seo_name'], 'showuser' ) . "' title='$last_date'>{$result['member_name']}</a>*";
								$active['ANON']++;
							}
							else
							{
								$active['ANON']++;
							}
						}
						else
						{
							$active['MEMBERS']++;
							$active['NAMES'][] = "<a href='" . $this->registry->getClass('output')->buildSEOUrl( "showuser={$result['member_id']}", 'public', $result['seo_name'], 'showuser' ) ."' title='$last_date'>{$result['member_name']}</a>";
						}
					}
				}
			}

			$active['TOTAL'] = $active['MEMBERS'] + $active['GUESTS'] + $active['ANON'];
			
			$this->users_online = $active['TOTAL'];
		}
		
		$this->lang->words['active_users'] = sprintf( $this->lang->words['active_users'], $this->settings['au_cutoff'] );
		
		return $active;
	}
	
	/**
	 * Returns a string of calendar events
	 *
	 * @access	public
	 * @return	mixed		HTML string, or false
	 **/
	public function getCalendarEvents()
	{
		//-----------------------------------------
		// Are we viewing the calendar?
		//-----------------------------------------
		
		if( $this->settings['show_calendar'] AND IPSLib::appIsInstalled('calendar') )
		{
			/* Get the current day, month, and year */
			$a = explode( ',', gmdate( 'Y,n,j,G,i,s', time() + $this->registry->class_localization->getTimeOffset() ) );
		
			$day   = $a[2];
			$month = $a[1];
			$year  = $a[0];
			
			/* Check the calendar limit */
			$this->settings['calendar_limit'] = intval( $this->settings['calendar_limit'] ) < 2 ? 1 : intval( $this->settings['calendar_limit'] );
			
			$our_unix    = gmmktime( 0, 0, 0, $month, $day, $year);
			$max_date    = $our_unix + ($this->settings['calendar_limit'] * 86400);
			$events      = array();
			$show_events = array();
			
			if( $this->memberData['org_perm_id'] )
			{
				$member_permission_groups = explode( ",", IPSText::cleanPermString( $this->memberData['org_perm_id'] ) );
			}
			else
			{
				$cache                    = $this->caches['group_cache'];
				$member_permission_groups = explode( ",", IPSText::cleanPermString( $this->memberData['g_perm_id'] ) );
				
				if( $this->memberData['mgroup_others'] )
				{
					$this->memberData['mgroup_others'] = IPSText::cleanPermString($this->memberData['mgroup_others']);
					
					$mgroup_others = explode( ",", $this->memberData['mgroup_others'] );
					
					if( count($mgroup_others) )
					{
						foreach( $mgroup_others as $member_group_id )
						{
							if( $member_group_id )
							{
								$member_permission_groups = array_merge( $member_permission_groups, explode( ",", IPSText::cleanPermString( $cache[$member_group_id]['g_perm_id'] ) ) );
							}
						}
					}
				}
			}
			
			if( is_array( $this->caches['calendar'] ) AND count( $this->caches['calendar'] ) )
			{
				foreach( $this->caches['calendar'] as $u )
				{
					$set_offset = 0;

					if( $u['event_timeset'] && !($u['event_recurring'] == 0 AND $u['event_unix_to']) )
					{
						$set_offset = $this->memberData['time_offset'] ? $this->memberData['time_offset'] * 3600 : 0;
					}
					
					$u['_unix_from'] = $u['event_unix_from'] - $set_offset;
					$u['_unix_to']   = $u['event_unix_to'] - $set_offset;
					
					//-----------------------------------------
					// Private?
					//-----------------------------------------
					
					if ( $u['event_private'] == 1 and $this->memberData['member_id'] != $u['event_member_id'] )
					{
						continue;
					}
					
					//-----------------------------------------
					// Got perms?
					//-----------------------------------------
					
					if( $u['event_perms'] != "*" )
					{
						$event_perms = explode( ",", IPSText::cleanPermString( $u['event_perms'] ) );
						
						$check = 0;
						
						if( count($event_perms) )
						{
							foreach( $event_perms as $mgroup_perm )
							{
								if( in_array( $mgroup_perm, $member_permission_groups ) )
								{
									$check = 1;
								}
							}
						}
						
						if( !$check )
						{
							continue;
						}
					}
						
					//-----------------------------------------
					// Got calendar perms?
					//-----------------------------------------
					
					if( $u['_perm_read'] != "*" )
					{
						$read_perms = explode( ",", IPSText::cleanPermString( $u['_perm_read'] ) );
						
						$check = 0;
						
						if( count( $read_perms ) )
						{
							foreach( $read_perms as $mgroup_perm )
							{
								if( in_array( $mgroup_perm, $member_permission_groups ) )
								{
									$check = 1;
								}
							}
						}
						
						if( !$check )
						{
							continue;
						}
					}
					
					//-----------------------------------------
					// In range?
					//-----------------------------------------
				
					if ( $u['event_recurring'] == 0 AND ( ( $u['event_unix_to'] >= $our_unix AND $u['event_unix_from'] <= $max_date )
						OR ( $u['event_unix_to'] == 0 AND $u['event_unix_from'] >= $our_unix AND $u['event_unix_from'] <= $max_date ) ) )
					{
						$u['event_activetime'] = $u['_unix_from'];
						$events[ str_pad( $u['event_unix_from'].$u['event_id'], 15, "0" ) ] = $u;
					}
					elseif( $u['event_recurring'] > 0 )
					{
						$cust_range_s = $u['event_unix_from'];

						while( $cust_range_s < $u['event_unix_to'])
						{
							if( $cust_range_s >= $our_unix AND $cust_range_s <= $max_date )
							{
								/* Special case for months, to take into account different numbers of days */
								if ( $u['event_recurring'] == "2" )
								{
									$u['event_activetime'] = gmmktime( 1, 1, 1, gmdate( 'n', $cust_range_s ), gmdate( 'j', $u['event_unix_from'] ), gmdate( 'Y', $cust_range_s ) );
								}
								else
								{
									$u['event_activetime'] = $cust_range_s;
								}
								$events[ str_pad( $cust_range_s.$u['event_id'], 15, "0" ) ] = $u;
							}

							if( $u['event_recurring'] == "1" )
							{
								$cust_range_s += 604800;
							}
							elseif ( $u['event_recurring'] == "2" )
							{
								$cust_range_s += 2628000;
							}
							else
							{
								$cust_range_s += 31536000;
							}
						}								
					}
				}
			}
			
			//-----------------------------------------
			// Print...
			//-----------------------------------------
			
			ksort($events);
			
			foreach( $events as $event )
			{
				//-----------------------------------------
				// Recurring?
				//-----------------------------------------

				$c_time = '';
				$c_time = gmstrftime( '%x', $event['event_activetime'] );
				$url    = $this->registry->output->buildSEOUrl( "app=calendar&amp;module=calendar&amp;cal_id={$event['event_calendar_id']}&amp;do=showevent&amp;event_id={$event['event_id']}", 'public', $event['event_title'], 'event' );
				
				$show_events[] = "<a href='{$url}' title='$c_time'>".$event['event_title']."</a>";
			}
			
			$this->lang->words['calender_f_title'] = sprintf( $this->lang->words['calender_f_title'], $this->settings['calendar_limit'] );
			
			if ( count($show_events) > 0 )
			{
				$event_string = $show_events; // Change in 3.0 by rikki: Just pass the array for the template to deal with
			}
			else
			{
				if ( ! $this->settings['autohide_calendar'] )
				{
					$event_string = $this->lang->words['no_calendar_events'];
				}
			}
			
			return $event_string;
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * Returns an array of birthday information
	 *
	 * @access	public
	 * @return	mixed		Array of birthday information, or false
	 **/
	public function getBirthdays()
	{
		if ($this->settings['show_birthdays'] or $this->settings['show_calendar'] )
		{
			$a = explode( ',', gmdate( 'Y,n,j,G,i,s', time() + $this->registry->getClass( 'class_localization')->getTimeOffset() ) );
		
			$day   = $a[2];
			$month = $a[1];
			$year  = $a[0];
			
			$birthstring = "";
			$count       = 0;
			$users       = array();
			
			if ( $this->settings['show_birthdays'] )
			{
				if ( is_array($this->caches['birthdays']) AND count( $this->caches['birthdays'] ) )
				{
					foreach( $this->caches['birthdays'] as $u )
					{
						if ( $u['bday_day'] == $day and $u['bday_month'] == $month )
						{
							$users[] = $u;
						}
						else if( $day == 28 && $month == 2 && !date("L") )
						{
							if ( $u['bday_day'] == "29" and $u['bday_month'] == $month )
							{
								$users[] = $u;
							}
						}
					}
				}
				
				//-----------------------------------------
				// Spin and print...
				//-----------------------------------------
				
				foreach ( $users as $user )
				{
					/* Age */
					$pyear = 0;
					
					if( $user['bday_year'] && $user['bday_year'] > 0 )
					{
						$pyear = $year - $user['bday_year'];
					}
					
					$birthstring[] = $this->registry->getClass('output')->getTemplate('boards')->birthday( $user, $pyear );

					$count++;
				}
				
				//-----------------------------------------
				// Fix up string...
				//-----------------------------------------

				$lang = $this->lang->words['no_birth_users'];
				
				if ($count > 0)
				{
					$lang = ($count > 1) ? $this->lang->words['birth_users'] : $this->lang->words['birth_user'];
				}
				else
				{
					return FALSE;
				}
			}
			
			return array( 'count' => $count, 'lang' => $lang, 'users' => $birthstring );
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * Returns an array of board stats
	 *
	 * @access	public
	 * @return	string		Stats string
	 **/
	public function getTotalTextString()
	{
		/* INIT */
		$stats_output = array();
		
		if ( $this->settings['show_totals'] )
		{
			if ( ! is_array( $this->caches['stats'] ) )
			{
				$this->cache->setCache( 'stats', array(), array( 'array' => 1, 'deletefirst' => 1 ) );
			}
			
			$stats = $this->caches['stats'];
			
			//-----------------------------------------
			// We need to determine if we have the most users ever online if we aren't
			// showing active users in the stats block
			//-----------------------------------------
			
			if( !$this->users_online )
			{
				$cut_off = $this->settings['au_cutoff'] * 60;
				$time    = time() - $cut_off;
				$total	 = $this->DB->buildAndFetch( array( 'select'	=> 'count(*) as users_online', 'from' => 'sessions', 'where' => "running_time > $time" ) );

				$this->users_online = $total['users_online'];
			}
			
			//-----------------------------------------
			// Update the most active count if needed
			//-----------------------------------------
			
			if ($this->users_online > $stats['most_count'])
			{
				$stats['most_count'] = $this->users_online;
				$stats['most_date']  = time();
				
				$this->cache->setCache( 'stats', $stats, array( 'array' => 1, 'deletefirst' => 1 ) );
			}
			
			$stats_output['most_time'] = $this->registry->getClass( 'class_localization')->getDate( $stats['most_date'], 'LONG' );
			$stats_output['most_online'] = $this->registry->getClass('class_localization')->formatNumber($stats['most_count']);
			
			$this->lang->words['most_online'] = str_replace( "<#NUM#>" ,  $stats_output['most_online']	, $this->lang->words['most_online'] );
			$this->lang->words['most_online'] = str_replace( "<#DATE#>",  $stats_output['most_time']	, $this->lang->words['most_online'] );

			$stats_output['total_posts'] = $stats['total_replies'] + $stats['total_topics'];
			
			$stats_output['total_posts'] = $this->registry->getClass('class_localization')->formatNumber($stats_output['total_posts']);
			$stats_output['mem_count'] = $this->registry->getClass('class_localization')->formatNumber($stats['mem_count']);
			
			$this->total_posts    = $stats_output['total_posts'];
			$this->total_members  = $stats_output['mem_count'];
			
			$stats_output['last_mem_seo']	= IPSText::makeSeoTitle( $stats['last_mem_name'] );
			$stats_output['last_mem_link']	= $this->registry->output->formatUrl( $this->registry->output->buildUrl( "showuser=".$stats['last_mem_id'], 'public' ), $stats_output['last_mem_seo'], 'showuser' );
			$stats_output['last_mem_name']	= $stats['last_mem_name'];
			$stats_output['last_mem_id']	= $stats['last_mem_id'];
	
			$this->lang->words['total_word_string'] = str_replace( "<#posts#>" , $stats_output['total_posts']   , $this->lang->words['total_word_string'] );
			$this->lang->words['total_word_string'] = str_replace( "<#reg#>"   , $stats_output['mem_count']     , $this->lang->words['total_word_string'] );
			$this->lang->words['total_word_string'] = str_replace( "<#mem#>"   , $stats_output['last_mem_name'] , $this->lang->words['total_word_string'] );
			$this->lang->words['total_word_string'] = str_replace( "<#link#>"  , $stats_output['last_mem_link'] , $this->lang->words['total_word_string'] ); 
		}

		return $stats_output;
	}
        
}