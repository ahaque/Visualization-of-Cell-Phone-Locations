<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Profile View
 * Last Updated: $Date: 2009-07-20 09:13:18 -0400 (Mon, 20 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Members
 * @link		http://www.
 * @since		20th February 2002
 * @version		$Revision: 4911 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_members_profile_view extends ipsCommand
{
	/**
	 * Custom fields object
	 *
	 * @access	public
	 * @var		object
	 */
	public $custom_fields;
	
	/**
	 * Temporary stored output HTML
	 *
	 * @access	public
	 * @var		string
	 */
	public $output;
	
	/**
	 * Member name
	 *
	 * @access	public
	 * @var		string
	 */
	private $member_name;

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
		// Get HTML and skin
		//-----------------------------------------

		$this->registry->class_localization->loadLanguageFile( array( 'public_profile' ), 'members' );
		$this->registry->class_localization->loadLanguageFile( array( 'public_online' ), 'members' );

		//-----------------------------------------
		// Can we access?
		//-----------------------------------------
		
		if ( !$this->memberData['g_mem_info'] )
 		{
 			$this->registry->output->showError( 'profiles_off', 10245 );
		}

		$this->_viewModern();

		//-----------------------------------------
		// Push to print handler
		//-----------------------------------------
		
		$this->registry->output->addContent( $this->output );
		$this->registry->output->setTitle( $this->member_name . ' - ' . $this->lang->words['page_title_pp'] );
		$this->registry->output->addNavigation( $this->lang->words['page_title_pp'], '' );
		$this->registry->output->sendOutput();
 	}

	/**
	 * Modern profile
	 *
	 * @access	private
	 * @return	void		[Outputs to screen]
	 */
 	private function _viewModern()
 	{
 		//-----------------------------------------
 		// INIT
 		//-----------------------------------------
		
		$member_id			= intval( $this->request['id'] ) ? intval( $this->request['id'] ) : intval( $this->request['MID'] );
		$member_id			= $member_id ? $member_id : $this->memberData['member_id'];
		$tab				= substr( IPSText::alphanumericalClean( str_replace( '..', '', trim( $this->request['tab'] ) ) ), 0, 20 );
		$firsttab			= '';
		$member				= array();
		$comments			= array();
		$comments_html		= "";
		$friends			= array();
		$visitors			= array();
		$comment_perpage	= 5;
		$pips				= 0;
		$tabs				= array();
		$_tabs				= array();
		$_positions			= array( 0 => 0 );
		$custom_path		= IPSLib::getAppDir( 'members' ) . '/sources/tabs';
		$_member_ids		= array();
		$sql_extra			= '';
		$pass				= 0;
		$mod				= 0;
		$_todays_date		= getdate();
		
		$time_adjust		= $this->settings['time_adjust'] == "" ? 0 : $this->settings['time_adjust'];
		$board_posts		= $this->caches['stats']['total_topics'] + $this->caches['stats']['total_replies'];

 		//-----------------------------------------
		// Check input..
		//-----------------------------------------

		if ( ! $member_id )
		{
			$this->registry->output->silentRedirect( $this->settings['base_url'] );
		}

		//-----------------------------------------
		// Configure tabs
		//-----------------------------------------
		
		if( is_dir( $custom_path ) )
		{
			foreach( new DirectoryIterator( $custom_path ) as $f )
			{
				if ( ! $f->isDot() && ! $f->isDir() )
				{
					$file = $f->getFileName();
					
					if( $file[0] == '.' )
					{
						continue;
					}
								
					if ( preg_match( "#\.conf\.php$#i", $file ) )
					{
						$classname = str_replace( ".conf.php", "", $file );
						
						require( $custom_path . '/' . $file );
						
						//-------------------------------
						// Allowed to use?
						//-------------------------------
					
						if ( $CONFIG['plugin_enabled'] )
						{
							if( in_array( $this->settings['search_method'], array( 'traditional', 'sphinx' ) ) && $CONFIG['plugin_key'] == 'recentActivity' )
							{
								continue;
							}
							
							$_position					= ( in_array( $CONFIG['plugin_order'], $_positions ) ) ? count( $_positions ) + 1 : $CONFIG['plugin_order'];
							$_tabs[ $_position ]		= $CONFIG;
							$_positions[ $_position ]	= $_position;
						}
					}
				}
			}			
		}
		
		ksort( $_tabs );
		
		foreach( $_tabs as $_pos => $data )
		{
			if( !$firsttab )
			{
				$firsttab = $data['plugin_key'];
			}
			
			$data['_lang']					= isset($this->lang->words[ $data['plugin_lang_bit'] ]) ? $this->lang->words[ $data['plugin_lang_bit'] ] : $data['plugin_name'];
			$tabs[ $data['plugin_key'] ]	= $data;
		}
		
		if( $tab != 'comments' AND $tab != 'settings' AND !file_exists( $custom_path . '/' . $tab . '.php' ) )
		{
			$tab = $firsttab;
		}

		//-----------------------------------------
		// Grab all data...
		//-----------------------------------------
		
		$member = IPSMember::load( $member_id, 'profile_portal,pfields_content,sessions,groups', 'id' );

		if ( !$member['member_id'] )
		{
			$this->registry->output->showError( 'profiles_no_member', 10246 );
		}
		
		/* Check USER permalink... */
		$this->registry->getClass('output')->checkPermalink( ( $member['members_seo_name'] ) ? $member['members_seo_name'] : IPSText::makeSeoTitle( $member['members_display_name'] ) );
		
		/* Build data */
		$member = IPSMember::buildDisplayData( $member, array( 'customFields' => 1, 'cfSkinGroup' => 'profile', 'checkFormat' => 1, 'cfGetGroupData' => 1, 'signature' => 1 ) );

		//-----------------------------------------
		// Recent visitor?
		//-----------------------------------------
		
		if ( $member['member_id'] != $this->memberData['member_id'] )
		{
			list( $be_anon, $loggedin ) = explode( '&', $this->memberData['login_anonymous'] );
			
			if ( ! $be_anon )
			{
				$this->_addRecentVisitor( $member, $this->memberData['member_id'] );
			}
		}

		//-----------------------------------------
		// DST?
		//-----------------------------------------
		
		if ( $member['dst_in_use'] == 1 )
		{
			$member['time_offset'] += 1;
		}

		//-----------------------------------------
		// Format extra user data
		//-----------------------------------------
		
		$member['_age']			 = ( $member['bday_year'] ) ? date( 'Y' ) - $member['bday_year'] : 0;
		
		if( $member['bday_month'] > date( 'n' ) )
		{
			$member['_age'] -= 1;
		}
		else if( $member['bday_month'] == date( 'n' ) )
		{
			if( $member['bday_day'] > date( 'j' ) )
			{
				$member['_age'] -= 1;
			}
		}

		$member['_local_time']		= $member['time_offset'] != "" ? gmstrftime( $this->settings['clock_long'], time() + ($member['time_offset']*3600) + ($time_adjust * 60) ) : '';
		$member['g_title']			= IPSLib::makeNameFormatted( $member['g_title'], $member['g_id'], $member['prefix'], $member['suffix'] );
		$member['_posts_day']		= 0;
		$member['_total_pct']		= 0;
		$member['_bday_month']		= $member['bday_month'] ? $this->lang->words['M_' . $member['bday_month'] ] : 0;
				
		//-----------------------------------------
		// BIO
		//-----------------------------------------

		$member['pp_bio_content']	= IPSText::getTextClass('bbcode')->stripBadWords( $member['pp_bio_content'] );
		$member['pp_bio_content']	= IPSText::wordwrap( $member['pp_bio_content'], '25', ' ' );

		if( !$this->settings['disable_profile_stats'] )
		{
			$posts	= $this->DB->buildAndFetch( array(
													'select'	=> "COUNT(*) as total_posts",
													'from'		=> "posts",
													'where'		=> "author_id=" . $member['member_id'],
												)		);

			$member['posts']	= $posts['total_posts'];

			//-----------------------------------------
			// Total posts
			//-----------------------------------------
			
			if ( $member['posts'] and $board_posts  )
			{
				$member['_posts_day'] = round( $member['posts'] / ( ( time() - $member['joined']) / 86400 ), 2 );
		
				# Fix the issue when there is less than one day
				$member['_posts_day'] = ( $member['_posts_day'] > $member['posts'] ) ? $member['posts'] : $member['_posts_day'];
				$member['_total_pct'] = sprintf( '%.2f', ( $member['posts'] / $board_posts * 100 ) );
			}
			
			$member['_posts_day'] = floatval($member['_posts_day']);
			
			//-----------------------------------------
			// Most active in
			//-----------------------------------------
		

			$favorite	= $this->DB->buildAndFetch( array(
														'select'	=> 'COUNT(p.author_id) as f_posts',
														'from'		=> array( 'posts' => 'p' ),
														'where'		=> 'p.author_id=' . $member['member_id'] . ' AND ' . $this->registry->permissions->buildPermQuery('i'),
														'order'		=> 'f_posts DESC',
														'group'		=> 't.forum_id',
														'add_join'	=> array(
																			array(
																				'select'	=> 't.forum_id',
																				'from'		=> array( 'topics' => 't' ),
																				'where'		=> 't.tid=p.topic_id',
																				),
																			array(
																				'from'		=> array( 'permission_index' => 'i' ),
																				'where'		=> "i.perm_type='forum' AND i.perm_type_id=t.forum_id",
																				),
																			)
												)		);
			
			$member['favorite_id']	= $favorite['forum_id'];
			$member['_fav_posts']	= $favorite['f_posts'];
			
			if( $member['posts'] )
			{
				$member['_fav_percent']	= round( $favorite['f_posts'] / $member['posts'] * 100 );
			}
		}

		//-----------------------------------------
		// Comments
		//-----------------------------------------
		
		if( $member['pp_setting_count_comments'] )
		{
			require_once( IPSLib::getAppDir( 'members' ) . '/sources/comments.php' );
			$comment_lib	= new profileCommentsLib( $this->registry );
			$comment_html	= $comment_lib->buildComments( $member );
		}

		//-----------------------------------------
		// Visitors
		//-----------------------------------------
		
		if ( $member['pp_setting_count_visitors'] )
		{
			$_pp_last_visitors	= unserialize( $member['pp_last_visitors'] );
			$_visitor_info		= array();
			$_count				= 1;
		
			if ( is_array( $_pp_last_visitors ) )
			{
				krsort( $_pp_last_visitors );
			
				$_members = IPSMember::load( array_values( $_pp_last_visitors ), 'extendedProfile' );
	
				foreach( $_members as $_id => $_member )
				{ 
					$_visitor_info[ $_id ] = IPSMember::buildDisplayData( $_member, 0 );
				}
				
				foreach( $_pp_last_visitors as $_time => $_id )
				{
					if ( $_count > $member['pp_setting_count_visitors'] )
					{
						break;
					}
				
					$_count++;
				
					if( !$_visitor_info[ $_id ]['members_display_name_short'] )
					{
						$_visitor_info[ $_id ] = IPSMember::setUpGuest();
					}
					
					$_visitor_info[ $_id ]['_visited_date'] 				= ipsRegistry::getClass( 'class_localization')->getDate( $_time, 'TINY' );
					$_visitor_info[ $_id ]['members_display_name_short']	= $_visitor_info[ $_id ]['members_display_name_short'] ? $_visitor_info[ $_id ]['members_display_name_short'] : $this->lang->words['global_guestname'];

					$visitors[] = $_visitor_info[ $_id ];
				}
			}
		}

		//-----------------------------------------
		// Friends
		//-----------------------------------------
		
		# Get random number from member's friend cache... grab 10 random. array_rand( array, no.)
		# also fall back on last 10 if no cache
		
		if ( $member['pp_setting_count_friends'] > 0 && $this->settings['friends_enabled'] )
		{
			$member['_cache'] = IPSMember::unpackMemberCache( $member['members_cache'] );
		
			if ( is_array( $member['_cache']['friends'] ) AND count( $member['_cache']['friends'] ) )
			{
				foreach( $member['_cache']['friends'] as $id => $approved )
				{
					$id = intval( $id );
				
					if ( $approved AND $id )
					{
						$_member_ids[] = $id;
					}
				}
				
				$member['_total_approved_friends'] = count( $_member_ids );

				if ( is_array( $_member_ids ) AND count( $_member_ids ) )
				{
					$_max		= count( $_member_ids ) > 50 ? 50 : count( $_member_ids );
					$_rand		= array_rand( $_member_ids, $_max );
					$_final		= array();
					
					# If viewing member is in list, let's show em
					if( in_array( $this->memberData[ 'member_id' ], $_member_ids ) )
					{						
						$_final[]	= $this->memberData[ 'member_id' ];
						
						$new_mids	= array();
						
						foreach( $_member_ids as $mid )
						{
							if( $mid == $this->memberData[ 'member_id' ] )
							{
								continue;
							}
							
							$new_mids[] = $mid;
						}
												
						$_member_ids = $new_mids;
						unset( $new_mids );
						
						if( is_array( $_rand ) )
						{
							if( count( $_rand ) >= 50 )
							{
								array_pop( $_rand );
							}
						}
					}
				
					if ( is_array( $_rand ) AND count( $_rand ) )
					{
						foreach( $_rand as $_id )
						{
							$_final[] = $_member_ids[ $_id ];
						}
					}
				
					if ( count( $_final ) )
					{
						$sql_extra = ' AND pf.friends_friend_id IN (' . IPSText::cleanPermString( implode( ',', $_final ) ) . ')';
					}
				}
			}

			$this->DB->build( array( 
									'select'   => 'pf.*',
									'from'	   => array( 'profile_friends' => 'pf' ),
									'where'	   => 'pf.friends_member_id=' . $member_id . ' AND pf.friends_approved=1' . $sql_extra,
									'limit'	   => array( 0, 50 ),
									'order'    => 'm.members_display_name ASC',
									'add_join' => array( 
														array( 
															  'select' => 'm.*',
															  'from'   => array( 'members' => 'm' ),
															  'where'  => 'm.member_id=pf.friends_friend_id',
															  'type'   => 'left' 
															),
														array( 
															  'select' => 'pp.*',
															  'from'   => array( 'profile_portal' => 'pp' ),
															  'where'  => 'pp.pp_member_id=m.member_id',
															  'type'   => 'left'
															),
													) 
							)	 );
																
			$outer = $this->DB->execute();
		
			while( $row = $this->DB->fetch($outer) )
			{
				$row['_friends_added']		= ipsRegistry::getClass( 'class_localization')->getDate( $row['friends_added'], 'SHORT' );
				$row['_location']			= $row['location'] ? $row['location'] : $this->lang->words['no_info'];
			
				$row = IPSMember::buildProfilePhoto( $row );
			
				$row['members_display_name_short'] = IPSText::truncate( $row['members_display_name'], 13 );
				
				$friends[] = $row;
			}
		}
		
		$member['_total_displayed_friends'] = count( $friends );

		//-----------------------------------------
		// Online location
		//-----------------------------------------
		
		$member = IPSMember::getLocation( $member );
		
		//-----------------------------------------
		// Add profile view
		//-----------------------------------------
		
		$this->DB->insert( 'profile_portal_views', array( 'views_member_id' => $member['member_id'] ), true );
		
		//-----------------------------------------
		// Grab default tab...
		//-----------------------------------------
		
		$tab_html = '';
		
		if ( $tab != 'comments' AND $tab != 'settings' )
		{
			if( file_exists( $custom_path . '/' . $tab . '.php' ) )
			{
				require( $custom_path . '/pluginParentClass.php' );
				require( $custom_path . '/' . $tab . '.php' );
				$_func_name		= 'profile_' . $tab;
				$plugin			=  new $_func_name( $this->registry );
				$tab_html		= $plugin->return_html_block( $member );
			}
		}
		
		//-----------------------------------------
		// Set description tag
		//-----------------------------------------
		
		$_desc = ( $member['pp_about_me'] ) ? $member['pp_about_me'] : $member['signature'];
		
		if ( $_desc )
		{
			$this->registry->output->addMetaTag( 'description', $member['members_display_name'] . ': ' . IPSText::getTextClass('bbcode')->stripAllTags( $_desc ) );
		}
		
		//-----------------------------------------
		// Add to output
		//-----------------------------------------
		
		$this->member_name	= $member['members_display_name'];
		$this->output		= $this->registry->getClass('output')->getTemplate('profile')->profileModern( $tabs, $member, $comment_html, $friends, $visitors, $tab, $tab_html, $fields );
	}
 	
 	/**
	 * Adds a recent visitor to ones profile
	 *
	 * @access	private
	 * @param	array 				Member information
	 * @param	integer				Member id to add
	 * @return	boolean
	 * @since	IPB 2.2.0.2006-7-31
	 */
 	private function _addRecentVisitor( $member=array(), $member_id_to_add=0 )
 	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$member_id_to_add	= intval( $member_id_to_add );
		$found				= 0;
		$_recent_visitors	= array();
		
		//-----------------------------------------
		// Check...
		//-----------------------------------------
		
		if ( ! $member_id_to_add )
		{
			return false;
		}
		
		//-----------------------------------------
		// Sort out data...
		//-----------------------------------------
		
		$recent_visitors = unserialize( $member['pp_last_visitors'] );
		
		if ( ! is_array( $recent_visitors ) OR ! count( $recent_visitors ) )
		{
			$recent_visitors = array();
		}
		
		foreach( $recent_visitors as $_time => $_id )
		{
			if ( $_id == $member_id_to_add )
			{
				$found  = 1;
				continue;
			}
			else
			{
				$_recent_visitors[ $_time ] = $_id;
			}
		}
		
		$recent_visitors = $_recent_visitors;
	
		krsort( $recent_visitors );
	
		//-----------------------------------------
		// No more than 10
		//-----------------------------------------
	
		if ( ! $found )
		{
			if ( count( $recent_visitors ) > 10 )
			{
				$_tmp = array_pop( $recent_visitors );
			}
		}
		
		//-----------------------------------------
		// Add the visit
		//-----------------------------------------
			
		$recent_visitors[ time() ] = $member_id_to_add;
		
		krsort( $recent_visitors );
		
		//-----------------------------------------
		// Update profile...
		//-----------------------------------------
	
		if ( $member['pp_member_id'] )
		{
			$this->DB->update( 'profile_portal ', array( 'pp_last_visitors' => serialize( $recent_visitors ) ), 'pp_member_id=' . $member['member_id'], true );
		}
		else
		{
			$this->DB->insert( 'profile_portal ', array( 'pp_member_id'		=> $member['member_id'],
															'pp_profile_update'	=> time(),
															'pp_last_visitors'	=> serialize( $recent_visitors ) 
								), true					);
		}
		
		return true;
	}
}