<?php

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Forums Class
 * Last Updated: $Date: 2009-08-19 18:15:10 -0400 (Wed, 19 Aug 2009) $
 *
 * @author 		$Author: josh $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Forums
 * @since		26th January 2004
 * @version		$Rev: 5031 $
 *
 */
class class_forums
{
	/**
	 * Cache of visible forums
	 *
	 * @access	public
	 * @var		array
	 */
	public $forum_cache		= array();
	
	/**
	 * Cache of all forums, regardless of perms
	 *
	 * @access	public
	 * @var		array
	 */
	public $allForums		= array();
	
	/**
	 * Cache of visible forums mapped by ID
	 *
	 * @access	public
	 * @var		array
	 */
	public $forum_by_id		= array();
	
	/**
	 * Depth guide
	 *
	 * @access	public
	 * @var		string
	 */
	public $depth_guide		= "--";
	
	/**
	 * Strip invisible forums?
	 *
	 * @access	public
	 * @var		bool
	 */
	public $strip_invisible	= false;
	
	/**
	 * Cache of moderators
	 *
	 * @access	public
	 * @var		array
	 */
	public $mod_cache		= array();
	
	/**
	 * Mod cache loaded
	 *
	 * @access	public
	 * @var		bool
	 */
	public $mod_cache_got	= false;
	
	/**
	 * Is a read topic only forum
	 *
	 * @access	public
	 * @var		bool
	 */
	public $read_topic_only	= false;

	/**#@+
	 * Registry Object Shortcuts
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
	protected $cache;
	/**#@-*/
	
	/**
	 * Posts per day status string
	 *
	 * @access public
	 * @var	   string
	 */
	public $ppdStatusMessage = '';
	
	/**
	 * CONSTRUCTOR
	 *
	 * @access	public
	 * @param	object	ipsRegistry  $registry
	 * @return	void
	 **/
	public function __construct( ipsRegistry $registry )
	{
		/* Make objects */
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
	 * Grab all forums and stuff into array
	 *
	 * @access	public
	 * @return	void
	 **/
	public function forumsInit()
	{
		/* Query Forum Data */
		$forum_list = $this->getForumList();
		
		$hide_parents = ',';
		
		$this->forum_cache = array();
		$this->forum_by_id = array();
		
		foreach( $forum_list as $f )
		{
			if( $this->strip_invisible )
			{
				/* Don't show any children of hidden parents */
				if( strstr( $hide_parents, ','. $f['parent_id'] .',' ) )
				{
					$hide_parents .= $f['id'].',';
					continue;
				}
				
				/* Don't show forums that we do not have view permissions for */
				if( $f['perm_view'] != '*' )
				{ 
					if ( $this->registry->permissions->check( 'view', $f ) != TRUE )
					{
						$hide_parents .= $f['id'].',';
						continue;
					}
				}
				
				/* Don't show forums that we can not see based on the minimum posts to view setting */
				if( isset( $f['min_posts_view'] ) && $f['min_posts_view'] && ( $this->memberData['posts'] < $f['min_posts_view'] ) )
				{
					continue;
				}		
			}
			
			/* Set the aprent id for root categories */
			if( $f['parent_id'] < 1 )
			{
				$f['parent_id'] = 'root';
			}
			
			$f['fid'] = $f['id'];
			
			/* Store the forum arrays */
			$this->forum_cache[ $f['parent_id'] ][ $f['id'] ] = $f;
			$this->forum_by_id[ $f['id'] ] = $this->forum_cache[ $f['parent_id'] ][ $f['id'] ];
		}
	}
	
	/**
	* Returns a list of all forums
	*
	* @access	public
	* @return	array
	*/
	public function getForumList()
	{
		/* Get the forums */			
		$this->DB->build( array( 
								'select'   => 'f.*',
								'from'     => array( 'forums' => 'f' ),
							//	'order'    => 'f.parent_id, f.position',
								'add_join' => array(
													array(
															'select' => 'p.*',
															'from'   => array( 'permission_index' => 'p' ),
															'where'  => "p.perm_type='forum' AND p.app='forums' AND p.perm_type_id=f.id",
															'type'   => 'left',
				
													)
												)
						)	);
		$q = $this->DB->execute();
		
		/* Loop through and build an array of forums */
		$forums_list	= array();
		$update_seo		= array();
		$tempForums     = array();
		
		while( $f = $this->DB->fetch( $q ) )
		{
			$tempForums[ $f['parent_id'] . '.' . $f['position'] . '.' . $f['id'] ] = $f;
		}
		
		/* Sort in PHP */
		$tempForums = IPSLib::knatsort( $tempForums );
		
		foreach( $tempForums as $posData => $f )
		{
			$fr = array();
			
			/**
			 * This is here in case the SEO name isn't stored for some reason.
			 * We'll parse it and then update the forums table - should only happen once
			 */
			if ( ! $f['name_seo'] )
			{
				/* SEO name */
				$f['name_seo'] = IPSText::makeSeoTitle( $f['name'] );
				
				$update_seo[ $f['id'] ]	= $f['name_seo'];
			}
			
			/* Reformat the array for a category */
			if ( $f['parent_id'] == -1 )
			{
				$fr['id']				    = $f['id'];
				$fr['sub_can_post']         = $f['sub_can_post'];
				$fr['name'] 		        = $f['name'];
				$fr['name_seo'] 			= $f['name_seo'];
				$fr['parent_id']	        = $f['parent_id'];
				$fr['skin_id']		        = $f['skin_id'];
				$fr['permission_showtopic'] = $f['permission_showtopic'];
				$fr['forums_bitoptions']    = $f['forums_bitoptions'];
			}
			else
			{
				$fr = $f;

				$fr['description'] = isset( $f['description'] ) ? $f['description'] : '';
			}
			
			$fr = array_merge( $fr, $this->registry->permissions->parse( $f ) );
			
			/* Unpack bitwise fields */
			$_tmp = IPSBWOptions::thaw( $fr['forums_bitoptions'], 'forums', 'forums' );

			if ( count( $_tmp ) )
			{
				foreach( $_tmp as $k => $v )
				{
					/* Trigger notice if we have DB field */
					if ( isset( $fr[ $k ] ) )
					{
						trigger_error( "Thawing bitwise options for FORUMS: Bitwise field '$k' has overwritten DB field '$k'", E_USER_WARNING );
					}

					$fr[ $k ] = $v;
				}
			}
			
			/* Add... */
			$forums_list[ $fr['id'] ] = $fr;
		}
	
		$this->allForums	= $forums_list;
		
		/**
		 * Update forums table if SEO name wasn't cached yet
		 */
		if( count($update_seo) )
		{
			foreach( $update_seo as $k => $v )
			{
				$this->DB->update( 'forums', array( 'name_seo' => $v ), 'id=' . $k );
			}
		}
		
		return $forums_list;
	}
	
	/**
	* Set up moderator, populate moderator functions
	*
	* @access	public
	* @param	array 		Array of member data
	* @return	array 		Array of member data populated with moderator details
	*/
	public function setUpModerator( $member )
	{
		$other_mgroups = array();
		
		if ( $member['member_group_id'] != $this->settings['guest_group'] )
		{
			//-----------------------------------------
			// Sprinkle on some moderator stuff...
			//-----------------------------------------

			if ( $member['g_is_supmod'] == 1 )
			{
				$member['is_mod'] = 1;
			}
			else if ( is_array($this->registry->cache()->getCache('moderators')) AND count($this->registry->cache()->getCache('moderators')) )
			{
				$other_mgroups = array();

				if ( $member['mgroup_others'] )
				{
					$other_mgroups = explode( ",", IPSText::cleanPermString( $member['mgroup_others'] ) );
				}
			}
			
			if( is_array($this->registry->cache()->getCache('moderators')) AND count($this->registry->cache()->getCache('moderators')) )
			{
				$_mod_forums = isset( $member['forumsModeratorData'] ) && is_array( $member['forumsModeratorData'] ) ? $member['forumsModeratorData'] : array();
				
				foreach( $this->registry->cache()->getCache('moderators') as $r )
				{
					$modForumIds = explode( ',', IPSText::cleanPermString( $r['forum_id'] ) );
					
					if ( $r['member_id'] == $member['member_id'] )
					{
						foreach( $modForumIds as $modForumId )
						{
							$_mod_forums[ $modForumId ] = $r;
						}

						$member['is_mod'] = 1;
					}
					else if( $r['group_id'] == $member['member_group_id'] )
					{
						// Individual mods override group mod settings
						// If array is set, don't override it

						foreach( $modForumIds as $modForumId )
						{
							if( !is_array($_mod_forums[ $modForumId ]) OR !count($_mod_forums[ $modForumId ]) )
							{
								$_mod_forums[ $modForumId ] = $r;
							}
						}

						$member['is_mod'] = 1;
					}
					else if( count( $other_mgroups ) AND in_array( $r['group_id'], $other_mgroups ) )
					{
						// Individual mods override group mod settings
						// If array is set, don't override it
	
						if( !is_array($_mod_forums[ $r['forum_id'] ]) OR !count($_mod_forums[ $r['forum_id'] ]) )
						{
							$_mod_forums[ $r['forum_id'] ] = $r;
						}
	
						$member['is_mod'] = 1;
					}						
				}
				
				$member['forumsModeratorData'] = $_mod_forums;
			}
		}
		
		return $member;
	}
	
	/**
	 * Fetch a status message about post moderation status
	 *
	 * @access	public
	 * @param	array 		Array of member data
	 * @param	array 		Array of forum data
	 * @param	array 		Array of topic data
	 * @param	string		reply/new (Type of message)
	 * @return	string
	 */
	public function fetchPostModerationStatusMessage( $memberData, $forum, $topic, $type='reply' )
	{
		if ( is_array( $memberData ) AND is_array( $forum ) AND is_array( $topic ) )
		{
			$group = $this->caches['group_cache'][ $memberData['member_group_id'] ];
			
			/* If our group can avoid the Q... */
			if ( $group['g_avoid_q'] )
			{
				return '';
			}
			
			/* Fetch correct language */
			if ( ! isset( $this->lang->words['status_ppd_posts'] ) )
			{
				$this->registry->class_localization->loadLanguageFile( array( 'public_topic' ), 'forums' );
			}
			
			/* First - are we mod queued? */
			if ( $memberData['mod_posts'] )
			{
				if ( $memberData['mod_posts'] == 1 )
				{
					return $this->lang->words['ms_mod_q'];
				}
				else
				{
					/* Do we need to remove the mod queue for this user? */
					$mod_arr = IPSMember::processBanEntry( $memberData['mod_posts'] );

					/* Yes, they are ok now */
					if ( time() >= $mod_arr['date_end'] )
					{
						IPSMember::save( $memberData['member_id'], array( 'core' => array( 'mod_posts' => 0 ) ) );
						
						return FALSE;
					}
					/* Nope, still don't want to see them */
					else
					{
						return sprintf( $this->lang->words['ms_mod_q'] . ' ' . $this->lang->words['ms_mod_q_until'], $this->lang->getDate( $mod_arr['date_end'], 'long' ) );
					}
				}
			}
			
			/* Next, try to see if this is forum specific */
			switch( intval( $forum['preview_posts'] ) )
			{
				case 1:
					return $this->lang->words['ms_mod_q'];
				break;
				case 2:
					if ( $type == 'new' )
					{
						return $this->lang->words['ms_mod_q'];
					}
				break;
				case 3:
					if ( $type == 'reply' )
					{
						return $this->lang->words['ms_mod_q'];
					}
				break;
			}
			
			/* Next, see if our group has moderator posties on */
			
			if ( $group['g_mod_preview'] )
			{
				/* Do we only limit for x posts/days? */
				if ( $group['g_mod_post_unit'] )
				{
					if ( $group['gbw_mod_post_unit_type'] )
					{
						/* Days.. .*/
						if ( $memberData['joined'] > ( time() - ( 86400 * $group['g_mod_post_unit'] ) ) )
						{
							return sprintf( $this->lang->words['ms_mod_q'] . ' ' . $this->lang->words['ms_mod_q_until'], $this->lang->getDate( $memberData['joined'] + ( 86400 * $group['g_mod_post_unit'] ), 'long' ) );
						}
					}
					else
					{
						/* Posts */
						if ( $memberData['posts'] < $group['g_mod_post_unit'] )
						{
							return sprintf( $this->lang->words['ms_mod_q'] . ' ' . $this->lang->words['ms_mod_q_until_posts'], $group['g_mod_post_unit'] - $memberData['posts'] );
						}
					}
				}
				else
				{
					/* No limit, but still checking moderating */
					return $this->lang->words['ms_mod_q'];
				}
			}
		}
		
		return '';
	}
	
	/**
	 * Check to see if we have any group restrictions on whether we can post or not
	 * Naturally, this would be better off in the classPost class but this means we'd have
	 * to load it in topic view for the fast reply box.
	 *
	 * Optionally populates a status message in ppdStatusMessage
	 *
	 * @access	public
	 * @param	array 		[Member data (assumes $this->memberData if nothing passed )]
	 * @param	boolean		Populate ppdStatusMessage?
	 * @return	boolean		TRUE = ok to post, FALSE cannot post
	 */
	public function checkGroupPostPerDay( $memberData = array(), $setStatus=FALSE )
	{
		$memberData = ( is_array( $memberData ) and count( $memberData ) ) ? $memberData : $this->memberData;
		$group      = $this->caches['group_cache'][ $memberData['member_group_id'] ];
		$_data      = explode( ',', $memberData['members_day_posts'] );
		$_count     = intval( $_data[0] );
		$_time      = intval( $_data[1] );
		
		/* Ok? */
		if ( is_array( $group ) AND is_array( $memberData ) )
		{
			/* Check posts per day */
			if ( $group['g_ppd_limit'] )
			{
				/* Check to see if we're past our 24hrs */
				if ( ( time() - 86400 ) >= $_time AND ( $_time ) )
				{
					$_count = 0;
					$_time  = 0;
					
					/* Update member immediately */
					IPSMember::save( $memberData['member_id'], array( 'core' => array( 'members_day_posts' => '0,0' ) ) );
				}
				
				/* Grab the correct lang file */
				if ( $setStatus )
				{
					if ( ! isset( $this->lang->words['status_ppd_posts'] ) )
					{
						$this->registry->class_localization->loadLanguageFile( array( 'public_topic' ), 'forums' );
					}
				}
				
				/* Do we only limit for x posts/days? */
				if ( $group['g_ppd_unit'] )
				{
					if ( $group['gbw_ppd_unit_type'] )
					{
						/* Days.. .*/
						if ( $memberData['joined'] > ( time() - ( 86400 * $group['g_ppd_unit'] ) ) )
						{
							if ( $_count >= $group['g_ppd_limit'] )
							{
								return FALSE;
							}
							else if ( $setStatus )
							{
								if ( $_time )
								{
									$this->ppdStatusMessage = sprintf( $this->lang->words['status_ppd_posts_joined'], $group['g_ppd_limit'] - $_count, $this->lang->getDate( $_time + 86400, 'long' ), $this->lang->getDate( $memberData['joined'] + ( 86400 * $group['g_ppd_unit'] ), 'long' ) );
								}
								else
								{
									$this->ppdStatusMessage = sprintf( $this->lang->words['status_ppd_posts_joined_no_time'], $group['g_ppd_limit'] - $_count, $this->lang->getDate( $memberData['joined'] + ( 86400 * $group['g_ppd_unit'] ), 'long' ) );
								}
							}
						}
					}
					else
					{
						/* Posts */
						if ( $memberData['posts'] < $group['g_ppd_unit'] )
						{
							if ( $_count >= $group['g_ppd_limit'] ) 
							{
								return FALSE;
							}
							else
							{ 
								if ( $_time )
								{
									$this->ppdStatusMessage = sprintf( $this->lang->words['status_ppd_posts'], $group['g_ppd_limit'] - $_count, $this->lang->getDate( $_time + 86400, 'long' ), ( $group['g_ppd_unit'] - $memberData['posts'] ) );
								}
								else
								{
									$this->ppdStatusMessage = sprintf( $this->lang->words['status_ppd_posts_no_time'], $group['g_ppd_limit'] - $_count, ( $group['g_ppd_unit'] - $memberData['posts'] ) );
								}
							}
						}
					}
				}
				else
				{
					/* No PPD limit, but still checking PPD */
					if ( $_count >= $group['g_ppd_limit'] )
					{
						return FALSE;
					}
					else
					{
						if ( $_time )
						{
							$this->ppdStatusMessage = sprintf( $this->lang->words['status_ppd_posts_nolimit'], $group['g_ppd_limit'] - $_count, $this->lang->getDate( $_time + 86400, 'long' ) );
						}
						else
						{
							$this->ppdStatusMessage = sprintf( $this->lang->words['status_ppd_posts_nolimit_no_time'], $group['g_ppd_limit'] - $_count );
						}
					}
				}
			}
			
			/* Still here? */
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	* Fetch forum data
	*
	* @access	public
	* @param	int			Forum ID
	* @return	array 		Forum Data
	*/
	public function forumsFetchData( $id )
	{
		return is_array( $this->forum_by_id[ $id ] ) ? $this->forum_by_id[ $id ] : array( 'id' => 0 );
	}
	
	/**
	 * Grab all mods innit
	 *
	 * @access	public
	 * @return	void
	 **/
	public function forumsGetModeratorCache()
	{
		$this->can_see_queued = array();
		
		if ( ! is_array( $this->caches['moderators'] ) )
		{
			$this->cache->rebuildCache( 'moderators', 'forums' );
		}
		
		/* Set Up */
		if ( count( $this->caches['moderators'] ) )
		{
			foreach( $this->caches['moderators'] as $r )
			{
				$forumIds = explode( ',', IPSText::cleanPermString( $r['forum_id'] ) );
				
				foreach( $forumIds as $forumId )
				{
					$this->mod_cache[ $forumId ][ $r['mid'] ] = array( 
																		'name'    => $r['members_display_name'],
																		'seoname' => $r['members_seo_name'],
																		'memid'   => $r['member_id'],
																		'id'      => $r['mid'],
																		'isg'     => $r['is_group'],
																		'gname'   => $r['group_name'],
																		'gid'     => $r['group_id'],
																	);	
				}
			}
		}
		
		$this->mod_cache_got = 1;
	}
	
	/**
	 * Get Moderators
	 *
	 * @access	public
	 * @param	integer	$forum_id
	 * @return	string
	 **/
	public function forumsGetModerators( $forum_id="" )
	{
		if ( ! $this->mod_cache_got )
		{
			$this->forumsGetModeratorCache();
		}
		
		$mod_string = array();
		
		if ( $forum_id == "" )
		{
			return $mod_string;
		}
		
		if (isset($this->mod_cache[ $forum_id ] ) )
		{
			if (is_array($this->mod_cache[ $forum_id ]) )
			{
				foreach ($this->mod_cache[ $forum_id ] as $moderator)
				{
					if ($moderator['isg'] == 1)
					{
						$mod_string[] = array( "{$this->settings['base_url']}app=members&amp;section=view&amp;module=list&amp;max_results=30&amp;filter={$moderator['gid']}&amp;sort_order=asc&amp;sort_key=members_display_name&amp;st=0&amp;b=1", $moderator['gname'], 0 );
					}
					else
					{
						if ( ! $moderator['name'] )
						{
							continue;
						}
						$mod_string[] = array( $this->registry->getClass("output")->buildSEOUrl( "showuser={$moderator['memid']}", "public", $moderator['seoname'], 'showuser' ), $moderator['name'], $moderator['memid'] );
					}
				}
			}
			else
			{
				if ($this->mods[$forum_id]['isg'] == 1)
				{
					$mod_string[] = array( "{$this->settings['base_url']}app=members&amp;section=view&amp;module=list&amp;max_results=30&amp;filter={$this->mods[$forum_id]['gid']}&amp;sort_order=asc&amp;sort_key=name&amp;st=0&amp;b=1", $this->mods[$forum_id]['gname'], 0 );
				}
				else
				{
					$mod_string[] =array( $this->registry->getClass("output")->buildSEOUrl( "showuser={$this->mods[$forum_id]['memid']}", "public", $this->mods[$forum_id]['seoname'], 'showuser' ), $this->mods[$forum_id]['name_seo'], $this->mods[$forum_id]['memid'] );
				}
			}
		}
		
		return $mod_string;
		
	}

	/**
	 * Check Forum Access
	 *
	 * @access	public
	 * @param	integer	$fid			Forum id
	 * @param	bool	$prompt_login	Prompt login/show error
	 * @param	string	$in				[topic|forum]
	 * @param	array 	$topic			Topic data
	 * @param	bool	$return			Return instead of displaying an error
	 * @return	bool
	 **/
	public function forumsCheckAccess( $fid, $prompt_login=0, $in='forum', $topic=array(), $return=false )
	{
		$fid         = intval( $fid );
		$deny_access = 1;
		
		if ( $this->registry->permissions->check( 'view', $this->forum_by_id[$fid] ) == TRUE )
		{
			if ( $this->registry->permissions->check( 'read', $this->forum_by_id[$fid] ) == TRUE )
			{
				$deny_access = 0;
			}
			else
			{
				//-----------------------------------------
				// Can see topics?
				//-----------------------------------------
		
				if ( $this->forum_by_id[$fid]['permission_showtopic'] )
				{
					$this->read_topic_only = 1;
					
					if ( $in == 'forum' )
					{
						$deny_access = 0;
					}
					else
					{
						if( $return )
						{
							return false;
						}
						
						$this->forumsCustomError( $fid );
						
						$deny_access = 1;						
					}
				}
				else
				{
					if( $return )
					{
						return false;
					}
						
					$this->forumsCustomError( $fid );
					
					$deny_access = 1;
				}
			}
		}
		else
		{
			if( $return )
			{
				return false;
			}
						
			$this->forumsCustomError( $fid );
			
			$deny_access = 1;
		}
		
		//-----------------------------------------
		// Do we have permission to even see the password page?
		//-----------------------------------------
		
		if ( $deny_access == 0 )
		{
			$group_exempt = 0;
			
			if ( isset( $this->forum_by_id[$fid]['password'] ) AND $this->forum_by_id[$fid]['password'] AND $this->forum_by_id[$fid]['sub_can_post'] )
			{
				if ( isset( $this->forum_by_id[$fid]['password_override'] ) )
				{
					if( in_array( $this->memberData['member_group_id'], explode( ",", $this->forum_by_id[$fid]['password_override'] ) ) )
					{
						$group_exempt = 1;
						$deny_access = 0;
					}
				}
				
				if ( $group_exempt == 0 )
				{
					if ( $this->forumsComparePassword( $fid ) == TRUE )
					{
						$deny_access = 0;
					}
					else
					{
						$deny_access = 1;
						
						if ( $prompt_login == 1 )
						{
							if( $return )
							{
								return false;
							}

							$this->forumsShowLogin( $fid );
						}
					}
				}
			}
		}
		
		if( is_array( $topic ) && count( $topic ) )
		{
			if ( ( ! $this->memberData['g_other_topics'] ) AND ( $topic['starter_id'] != $this->memberData['member_id'] ) )
			{
				if( $return )
				{
					return false;
				}
				
				$this->registry->getClass('output')->showError( 'forums_no_view_topic', 103136 );
			}
			else if( (!$this->forum_by_id[$fid]['can_view_others'] AND !$this->memberData['is_mod'] ) AND ( $topic['starter_id'] != $this->memberData['member_id'] ) )
			{
				if( $return )
				{
					return false;
				}
				
				$this->registry->getClass('output')->showError( 'forums_no_view_topic', 103137 );
			}
		}

		if( $this->forum_by_id[$fid]['min_posts_view'] && $this->forum_by_id[$fid]['min_posts_view'] > $this->memberData['posts'] )
		{
			if( $return )
			{
				return false;
			}

			$this->registry->getClass('output')->showError( 'forums_not_enough_posts', 103138 );
		}
		
		if ( $deny_access == 1 )
        {
        	if( $return )
        	{
        		return false;
        	}

        	$this->registry->getClass('output')->showError( 'forums_no_permission', 103139 );
        }
        else
        {
	        return TRUE;
        }
	}

	/**
	 * Compare forum pasword
	 *
	 * @access	public
	 * @param	integer	$fid	Forum ID
	 * @return	bool
	 **/
	public function forumsComparePassword( $fid )
	{
		$cookie_pass = IPSCookie::get( 'ipbforumpass_'.$fid );
		
		if ( trim( $cookie_pass ) == md5( $this->forum_by_id[$fid]['password'] ) )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * Forums custom error
	 *
	 * @access	public
	 * @param	integer	$fid	Forum ID
	 * @return	void
	 **/
	public function forumsCustomError( $fid )
	{
		$tmp = $this->DB->buildAndFetch( array( 'select' => 'permission_custom_error', 'from' => 'forums', 'where' => "id=".$fid ) );
		
		if ( $tmp['permission_custom_error'] )
		{
			$this->registry->output->showError( $tmp['permission_custom_error'], 103149 );
		}
	}
		
	/**
	 * Forums log in screen
	 *
	 * @access	public
	 * @param	integer	$fid	Forum ID	 
	 * @return	void
	 **/
	public function forumsShowLogin( $fid )
	{
		/* Lang */
		$this->registry->class_localization->loadLanguageFile( array( 'public_forums' ), 'forums' );
		
		/* Output */
		$content = $this->registry->getClass('output')->getTemplate('forum')->forumPasswordLogIn( $fid );
		$nav     = $this->forumsBreadcrumbNav( $fid );
		
		if( is_array( $nav ) AND count( $nav ) )
		{
			foreach( $nav as $_id => $_nav )
			{
				$this->registry->getClass('output')->addNavigation( $_nav[0], $_nav[1], $_nav[2], $_nav[3] );
			}
		}
		
		$this->registry->getClass('output')->setTitle( $this->settings['board_name'] . ' -> ' . $this->forum_by_id[$fid]['name'] );
		$this->registry->getClass('output')->addContent( $content );
		
        $this->registry->getClass('output')->sendOutput();

	}
	
	/**
	 * Find all the parents of a child without getting the nice lady to 
	 * use the superstore tannoy to shout "Small ugly boy in tears at reception"
	 *
	 * @access	public
	 * @param	integer	$root_id
	 * @param	array 	$ids
	 * @return	array
	 **/
	public function forumsGetParents( $root_id, $ids=array() )
	{
		if ( $this->forum_by_id[ $root_id ]['parent_id'] and $this->forum_by_id[ $root_id ]['parent_id'] != 'root' )
		{
			$ids[] = $this->forum_by_id[ $root_id ]['parent_id'];
			
			// Stop endless loop setting cat as it's own parent?
			if ( in_array( $root_id, $ids ) )
			{
				//return $ids;
			}
			
			$ids = $this->forumsGetParents( $this->forum_by_id[ $root_id ]['parent_id'], $ids );
		}
	
		return $ids;
	}

	/**
	 * Get all the children
	 *
	 * @access	public
	 * @param	integer	$root_id
	 * @param	array 	$ids
	 * @return	array
	 **/
	public function forumsGetChildren( $root_id, $ids=array() )
	{
		if ( isset( $this->forum_cache[ $root_id ]) AND is_array( $this->forum_cache[ $root_id ] ) )
		{
			foreach( $this->forum_cache[ $root_id ] as $forum_data )
			{
				$ids[] = $forum_data['id'];
				
				$ids = $this->forumsGetChildren($forum_data['id'], $ids);
			}
		}
		
		return $ids;
	}
	
	/**
	 * Gets cumulative posts/topics - sets new post marker and last topic id
	 *
	 * @access	public
	 * @param	integer	$root_id
	 * @param	array 	$forum_data
	 * @param	bool	$done_pass
	 * @return	array
	 **/
	public function forumsCalcChildren( $root_id, $forum_data=array(), $done_pass=0 )
	{
		//-----------------------------------------
		// Markers
		//-----------------------------------------

		$rtime = $this->registry->classItemMarking->fetchTimeLastMarked( array( 'forumID' => $forum_data['id'] ), 'forums' );

		if( !isset($forum_data['_has_unread']) )
		{
			$forum_data['_has_unread'] = ( $forum_data['last_post'] && $forum_data['last_post'] > $rtime ) ? 1 : 0;
		}

		if ( isset( $this->forum_cache[ $root_id ]) AND is_array( $this->forum_cache[ $root_id ] ) )
		{
			foreach( $this->forum_cache[ $root_id ] as $data )
			{
				if ( $data['last_post'] > $forum_data['last_post'] )
				{
					$forum_data['last_post']			= $data['last_post'];
					$forum_data['fid']					= $data['id'];
					$forum_data['last_id']				= $data['last_id'];
					$forum_data['last_title']			= $data['last_title'];
					$forum_data['seo_last_title']		= $data['seo_last_title'];
					$forum_data['password']				= isset( $data['password'] ) ? $data['password'] : '';
					$forum_data['password_override']	= isset( $data['password_override'] ) ? $data['password_override'] : '';
					$forum_data['last_poster_id']		= $data['last_poster_id'];
					$forum_data['last_poster_name']		= $data['last_poster_name'];
					$forum_data['seo_last_name']		= $data['seo_last_name'];
					$forum_data['_has_unread']          = 0;
					
					# Bug http://forums./index.php?autocom=bugtracker&do=show_bug&bug_title_id=3931&bug_cat_id=3
					#$forum_data['status']            = $data['status'];
				}
				
				//-----------------------------------------
				// Markers.  We never set false from inside loop.
				//-----------------------------------------
				
				$rtime	             = $this->registry->classItemMarking->fetchTimeLastMarked( array( 'forumID' => $data['id'] ), 'forums' );
				$data['_has_unread'] = 0;
				
				if( $data['last_post'] && $data['last_post'] > $rtime )
				{
					$forum_data['_has_unread']		= 1;
					$data['_has_unread']            = 1;
				}

				//-----------------------------------------
				// Topics and posts
				//-----------------------------------------
				
				$forum_data['posts']  += $data['posts'];
				$forum_data['topics'] += $data['topics'];
				
				$_mod = $this->memberData['forumsModeratorData'];
				
				if ( $this->memberData['g_is_supmod'] or ( $_mod && isset( $_mod[ $data['id'] ]['post_q'] ) AND $_mod[ $data['id'] ]['post_q'] == 1 ) )
				{
					$forum_data['queued_posts']  += $data['queued_posts'];
					$forum_data['queued_topics'] += $data['queued_topics'];
				}
				
				if ( ! $done_pass )
				{
					$forum_data['subforums'][ $data['id'] ] = array($data['id'], $data['name'], $data['name_seo'], intval( $data['_has_unread']  ) );
				}
				
				$forum_data = $this->forumsCalcChildren( $data['id'], $forum_data, 1 );
			}
		}

		return $forum_data;
	}
	
	/**
	 * Create forum breadcrumb nav
	 *
	 * @access	public
	 * @param	integer	$root_id
	 * @param	string	$url
	 * @return	array
	 **/
	public function forumsBreadcrumbNav($root_id, $url='showforum=')
	{
		$nav_array[] = array( $this->forum_by_id[$root_id]['name'], $url . $root_id, $this->forum_by_id[$root_id]['name_seo'], 'showforum' );
	
		$ids = $this->forumsGetParents( $root_id );
		
		if ( is_array($ids) and count($ids) )
		{
			foreach( $ids as $id )
			{
				$data = $this->forum_by_id[$id];
				
				$nav_array[] = array( $data['name'], $url . $data['id'], $data['name_seo'], 'showforum' );
			}
		}
	
		return array_reverse( $nav_array );
	}
	
	/**
	 * Builds the forum jump
	 *
	 * @access	public
	 * @param	bool	$html
	 * @param	bool	$override
	 * @param	bool	$remove_redirects
	 * @return	string
	 **/
	public function forumsForumJump( $html=0, $override=0, $remove_redirects=0 )
	{
		$jump_string = "";
		
		if( is_array( $this->forum_cache['root'] ) AND count( $this->forum_cache['root'] ) )
		{
			foreach( $this->forum_cache['root'] as $forum_data )
			{
				if ( $forum_data['sub_can_post'] or ( isset( $this->forum_cache[ $forum_data['id'] ] ) AND is_array( $this->forum_cache[ $forum_data['id'] ] ) AND count( $this->forum_cache[ $forum_data['id'] ] ) ) )
				{
					$forum_data['redirect_on'] = isset( $forum_data['redirect_on'] ) ? $forum_data['redirect_on'] : 0;
					
					if( $remove_redirects == 1 AND $forum_data['redirect_on'] == 1 )
					{
						continue;
					}
					
					$selected = "";
					
					if ($html == 1 or $override == 1)
					{
						if( $this->request['f'] and $this->request['f'] == $forum_data['id'] )
						{
							$selected = ' selected="selected"';
						}
					}
					
					$jump_string .= "<option value=\"{$forum_data['id']}\"".$selected.">".$forum_data['name']."</option>\n";
					
					$depth_guide = $this->depth_guide;
					
					if ( isset($this->forum_cache[ $forum_data['id'] ]) AND is_array( $this->forum_cache[ $forum_data['id'] ] ) )
					{
						foreach( $this->forum_cache[ $forum_data['id'] ] as $forum_data )
						{
							if( $remove_redirects == 1 AND $forum_data['redirect_on'] == 1 )
							{
								continue;
							}						
							
							if ($html == 1 or $override == 1)
							{
								$selected = "";
								
								if ( $this->request['f'] and $this->request['f'] == $forum_data['id'])
								{
									$selected = ' selected="selected"';
								}
							}
							
							$jump_string .= "<option value=\"{$forum_data['id']}\"".$selected.">&nbsp;&nbsp;&#0124;".$depth_guide." ".$forum_data['name']."</option>\n";
							
							if( $this->settings['short_forum_jump'] == 0 OR $override == 1 )
							{
								$jump_string = $this->_forumsForumJumpInternal( $forum_data['id'], $jump_string, $depth_guide . $this->depth_guide, $html, $override, $remove_redirects );
							}
						}
					}
				}
			}
		}
		
		return $jump_string;
	}
	
	/**
	 * Internal helper function for forumsForumJump
	 *
	 * @access	private
	 * @param	integer	$root_id
	 * @param	string	$jump_string
	 * @param	string	$depth_guide
	 * @param	bool	$html
	 * @param	bool	$override
	 * @param	bool	$remove_redirects
	 * @return	string
	 **/
	private function _forumsForumJumpInternal( $root_id, $jump_string="", $depth_guide="",$html=0, $override=0, $remove_redirects=0 )
	{
		if ( isset($this->forum_cache[ $root_id ]) AND is_array( $this->forum_cache[ $root_id ] ) )
		{
			foreach( $this->forum_cache[ $root_id ] as $forum_data )
			{
				if( $remove_redirects == 1 AND $forum_data['redirect_on'] == 1 )
				{
					continue;
				}
				
				$selected = "";
								
				if ($html == 1 or $override == 1)
				{
					if ( $this->request['f'] and $this->request['f'] == $forum_data['id'])
					{
						$selected = ' selected="selected"';
					}
				}
					
				$jump_string .= "<option value=\"{$forum_data['id']}\"".$selected.">&nbsp;&nbsp;&#0124;".$depth_guide." ".$forum_data['name']."</option>\n";
				
				$jump_string = $this->_forumsForumJumpInternal( $forum_data['id'], $jump_string, $depth_guide . $this->depth_guide, $html, $override );
			}
		}
		
		
		return $jump_string;
	}
	
	/**
	 * Sorts out the last poster, etc
	 *
	 * @access	public
	 * @param	array 	$forum_data
	 * @return	array
	 **/
	public function forumsFormatLastinfo( $forum_data )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$show_subforums = 1;
		$this->request['f'] = isset( $this->request['f'] ) ? intval( $this->request['f'] ) : 0;
		$this->settings['disable_subforum_show'] = intval( $this->settings['disable_subforum_show'] );
		
		if ( $this->registry->permissions->check( 'read', $this->forum_by_id[ $forum_data['id'] ] ) !== TRUE )
		{
			$show_subforums = 0;
		}
		
		$forum_data['img_new_post'] = $this->forumsNewPosts( $forum_data );
		
		$forum_data['last_post'] = $this->registry->getClass('class_localization')->getDate( $forum_data['last_post'], 'LONG' );
					
		$forum_data['last_topic_title'] = $this->lang->words['f_none'];
		$forum_data['last_topic_id']    = 0;
		
		$forum_data['full_last_title'] = isset( $forum_data['last_title'] ) ? $forum_data['last_title'] : '';
		
		$forum_data['_hide_last_date'] = false;
		
		if (isset($forum_data['last_title']) and $forum_data['last_id'])
		{
			$forum_data['last_title'] = strip_tags( $forum_data['last_title'] );
			$forum_data['last_title'] = str_replace( "&#33;" , "!", $forum_data['last_title'] );
			$forum_data['last_title'] = str_replace( "&quot;", '"', $forum_data['last_title'] );
			
			$forum_data['last_title'] = IPSText::truncate($forum_data['last_title'], 30);
			
			if ( ( isset($forum_data['password']) AND $forum_data['password'] ) OR ( $this->registry->permissions->check( 'read', $this->forum_by_id[ $forum_data['fid'] ] ) != TRUE AND $this->forum_by_id[ $forum_data['fid'] ]['permission_showtopic'] == 0 ) )
			{
				$forum_data['last_topic_title'] = $this->lang->words['f_protected'];
				$forum_data['_hide_last_date'] = true;
			}
			else if( $forum_data['hide_last_info'] )
			{
				$forum_data['last_topic_title'] = $this->lang->words['f_protected'];
				$forum_data['_hide_last_date'] = true;
			}
			else
			{
				$forum_data['last_topic_id']     = $forum_data['last_id'];
				$forum_data['last_topic_title']  = "<a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "showtopic={$forum_data['last_id']}&amp;view=getnewpost", 'public' ), $forum_data['seo_last_title'], 'showtopic' ) . "' title='" . $this->lang->words['tt_gounread'] . ": {$forum_data['full_last_title']}'>{$forum_data['last_title']}</a>";
			}
		}
		
		$forum_data['posts']  = $this->registry->getClass('class_localization')->formatNumber( $forum_data['posts'] );
		$forum_data['topics'] = $this->registry->getClass('class_localization')->formatNumber( $forum_data['topics'] );
		
		if ( $this->settings['disable_subforum_show'] == 0 AND $show_subforums == 1 )
		{
			if ( isset($forum_data['subforums']) and is_array( $forum_data['subforums'] ) and count( $forum_data['subforums'] ) )
			{
				$forum_data['show_subforums'] = 1;
			}
		}
		
		$_mod = $this->memberData['forumsModeratorData'];
		
		if ( $this->memberData['g_is_supmod'] or ( isset($_mod[ $forum_data['id'] ]['post_q']) AND $_mod[ $forum_data['id'] ]['post_q'] == 1 ) )
		{
			if ( $forum_data['queued_posts'] or $forum_data['queued_topics'] )
			{
				$forum_data['_has_queued_and_can_see_icon'] = 1;
				$forum_data['queued_posts']                 = intval($forum_data['queued_posts']);
				$forum_data['queued_topics']                = intval($forum_data['queued_topics']);
			}
		}
		
		return $forum_data;
	}
	
	/**
	 * Generate the appropriate folder icon for a forum
	 *
	 * @access	public
	 * @param	array 	$forum_data
	 * @return	string
	 **/
	public function forumsNewPosts( $forum_data )
	{
		if ( ! $forum_data['status'] )
		{
			return "f_locked";
		}

		$sub = 0;
        
        if ( isset($forum_data['subforums']) AND count($forum_data['subforums']) )
        {
        	$sub = 1;
        }

        //-----------------------------------------
        // Sub forum?
        //-----------------------------------------
        
        if ($sub ==  0)
        {
			$sub_cat_img = '';
        }
        else
        {
        	$sub_cat_img = '_cat';
        }

        if ( isset($forum_data['password']) AND $forum_data['password'] AND $sub == 0 )
        {
            return $forum_data['_has_unread'] ? "f_pass_unread" : "f_pass_read";
        }
        
        return $forum_data['_has_unread'] ? "f".$sub_cat_img."_unread" : "f".$sub_cat_img."_read";
    }
	
	/**
	 * Locate the category of any forum
	 *
	 * @access	public
	 * @param	int		Forum ID
	 * @return	int		Category ID (root forum ID)
	 */
	public function fetchTopParentID( $forumID )
	{
		$ids = $this->forumsGetParents( $forumID );
	
		return array_pop( $ids );
	}
	
	/**
	 * Generate the appropriate folder icon for a topic
	 *
	 * @access	public
	 * @param	array	Topic data array
	 * @param	string	Dot flag
	 * @param	bool	Whether item is read or not
	 * @return	string	Parsed macro
	 */
	public function fetchTopicFolderIcon( $topic, $dot="", $is_read=false )
	{
		//-----------------------------------------
		// Sort dot
		//-----------------------------------------
		
		if ($dot != "")
		{
			$dot = "_dot";
		}
		
		if ($topic['state'] == 'closed')
		{
			return "t_closed";
		}
		
		if ($topic['poll_state'])
		{
			if ( ! $this->memberData['member_id'] )
			{
				return "t_poll_read".$dot;
			}

			if ( !$is_read )
			{
				return "t_poll_unread".$dot;
			}
			
			return "t_poll_read".$dot;
		}
		
		
		if ($topic['state'] == 'moved' or $topic['state'] == 'link')
		{
			return "t_moved";
		}

		if (($topic['posts'] + 1 >= $this->settings['hot_topic']) and $is_read )
		{
			return "t_hot_read".$dot;
		}
		
		if ( !$is_read )
		{
			if ($topic['posts'] + 1 >= $this->settings['hot_topic'])
			{
				return "t_hot_unread".$dot;
			}
			else
			{
				return "t_unread".$dot;
			}
		}

		return "t_read".$dot;
	}
	
	/**
	 * Build <select> jump menu
	 * $html = 0 means don't return the select html stuff
	 * $html = 1 means return the jump menu with select and option stuff
	 *
	 * @access	public
	 * @param	integer	HTML flag (see above)
	 * @param	integer	Override flag
	 * @param	integer
	 * @return	string	Parsed HTML
	 */
	public function buildForumJump( $html=1, $override=0, $remove_redirects=0 )
	{
		$the_html = "";
		$the_html = $this->forumsForumJump( $html, $override, $remove_redirects );
		
		if ($html == 1)
		{
			$the_html = $this->registry->getClass('output')->getTemplate('global')->forum_jump( $the_html );
		}

		/*$the_html .= $this->forumsForumJump( $html, $override, $remove_redirects );

		if ($html == 1)
		{
			$the_html .= "</optgroup>\n</select>&nbsp;<input type='submit' value='" . $this->lang->words['jmp_go'] . "' class='button' /></form>";
		}*/

		return $the_html;
	}
	
	/**
	 * Determine if this user / forum combo can manage mod queue
	 *
	 * @access	public
	 * @param	integer	Forum ID
	 * @return	integer Boolean
	 */
	public function canQueuePosts( $fid=0 )
	{
		$return = 0;
		$_mod   = $this->memberData['forumsModeratorData'];
		
		if ( $this->memberData['g_is_supmod'] )
		{
			$return = 1;
		}
		else if ( $fid AND $this->memberData['is_mod'] AND $this->memberData['is_mod'] AND isset($_mod[ $fid ]['post_q']) AND $_mod[ $fid ]['post_q'] )
		{
			$return = 1;
		}
		
		return $return;
	}
	
	/**
	 * Determine if this user / forum combo can manage multi moderation tasks
	 * and return mm_array of allowed tasks
	 *
	 * @access	public
	 * @param	integer	Forum ID
	 * @return	array	Allowed tasks
	 */
	public function getMultimod( $fid )
	{
		$mm_array = array();
		$_mod     = $this->memberData['forumsModeratorData'];
		$pass_go  = FALSE;
		
		if ( $this->memberData['member_id'] )
		{
			if ( $this->memberData['g_is_supmod'] )
			{
				$pass_go = TRUE;
			}
			else if ( isset($_mod[ $fid ]['can_mm']) AND $_mod[ $fid ]['can_mm'] == 1 )
			{
				$pass_go = TRUE;
			}
		}
		
		if ( $pass_go != TRUE )
		{
			return $mm_array;
		}
		
		if ( ! is_array( $this->caches['multimod'] ) )
        {
        	$cache = array();
        	
			$this->DB->build( array( 'select' => '*', 'from' => 'topic_mmod', 'order' => 'mm_title' ) );
			$this->DB->execute();
						
			while ($i = $this->DB->fetch())
			{
				$cache[ $i['mm_id'] ] = $i;
			}
			
			$this->cache->setCache( 'multimod', $cache,  array( 'name' => 'multimod', 'array' => 1, 'deletefirst' => 1 ) );
        }
		
		//-----------------------------------------
		// Get the topic mod thingies
		//-----------------------------------------
		
		if( count( $this->caches['multimod'] ) AND is_array( $this->caches['multimod'] ) )
		{
			foreach( $this->caches['multimod'] as $r )
			{
				if ( $r['mm_forums'] == '*' OR strstr( ",".$r['mm_forums'].",", ",".$fid."," ) )
				{
					$mm_array[] = array( $r['mm_id'], $r['mm_title'] );
				}
			}
		}
		
		return $mm_array;
	}
	
	/**
	 * Removes the specified post from the search index
	 *
	 * @access	public
	 * @param	integer	$topic_id	Topic ID of the post
	 * @param	integer	$post_id	ID of the post
	 * @param	bool	$first_post	Set to 1 if this is the first post in a topic
	 * @return	void
	 **/
	public function removePostFromSearchIndex( $topic_id, $post_id, $first_post=0 )
	{
		/* Delete the first post in a topic */
		if( $first_post )
		{
			/* Delete all the entries for this topic */
			$this->DB->delete( 'search_index', "type='forum' AND type_2='topic' AND type_id_2={$topic_id}" );
		}
		else
		{
			/* PID Condition */
			$pid  = '%s:3:"pid";s:'. strlen( $post_id ) . ':"' . $post_id . '"%';
			$pid2 = '%s:3:"pid";i:'. $post_id . '%';

			/* Delete the single post */
			$this->DB->delete( 'search_index', "type='forum' AND type_2='topic' AND type_id_2='{$topic_id}' AND ( misc LIKE '{$pid}' OR misc LIKE '{$pid2}' )" );
		}
	}
	
	/**
	 * Determine if user can still access forum
	 *
	 * @access	public
	 * @param	array 		Topic/member/forum data
	 * @param	boolean		Check topic access
	 * @return	boolean
	 */
	public function checkEmailAccess( $t, $checkTopic=true )
	{
		//-----------------------------------------
		// Test for group permissions
		//-----------------------------------------
		
		$member_groups	= array( $t['member_group_id'] );
		$mgroup_others	= "";
		$temp_mgroups	= array();
		$mgroup_perms	= array();
		
		if( $t['mgroup_others'] )
		{
			$t['mgroup_others']	= IPSText::cleanPermString( $t['mgroup_others'] );
			$temp_mgroups		= explode( ",", $t['mgroup_others'] );
			
			if( count($temp_mgroups) )
			{
				foreach( $temp_mgroups as $other_mgroup )
				{
					$member_groups[]	= $other_mgroup;
					$mgroup_perms[]		= $this->caches['group_cache'][ $other_mgroup ]['g_perm_id'];
				}
			}
			
			if( count($mgroup_perms) )
			{
				$mgroup_others = "," . implode( ",", $mgroup_perms ) . ",";
			}
		}

		$perm_id = ( $t['org_perm_id'] ) ? $t['org_perm_id'] : $this->caches['group_cache'][ $t['member_group_id'] ]['g_perm_id'] . $mgroup_others;

		//-----------------------------------------
		// Can they view forum?
		//-----------------------------------------

		if ( $this->registry->permissions->check( 'view', $this->allForums[ $t['forum_id'] ], explode( ',', $perm_id ) ) !== TRUE )
		{
			return false;
		}

		//-----------------------------------------
		// Can they read topics in the forum?
		//-----------------------------------------
		
		if ( $this->registry->permissions->check( 'read', $this->allForums[ $t['forum_id'] ], explode( ',', $perm_id ) ) !== TRUE )
		{
			return false;
		}
		
		//-----------------------------------------
		// Can view others topics
		//-----------------------------------------
		
		if( $checkTopic )
		{
			$canViewOthers	= false;
			$isSupermod		= false;
			
			foreach( $member_groups as $mgroup )
			{
				if( $this->caches['group_cache'][ $mgroup ]['g_other_topics'] )
				{
					$canViewOthers	= true;
				}
				
				if( $this->caches['group_cache'][ $mgroup ]['g_is_supmod'] )
				{
					$isSupermod		= true;
				}
			}
			
			if( ! $canViewOthers AND $t['starter_id'] != $t['member_id'] )
			{
				return false;
			}
			else if( !$this->allForums[ $t['forum_id'] ]['can_view_others'] AND $t['starter_id'] != $t['member_id'] AND !$isSupermod )
			{
				return false;
			}
		}
		
		//-----------------------------------------
		// Minimum posts to view
		//-----------------------------------------
		
		if( $this->allForums[ $t['forum_id'] ]['min_posts_view'] && $this->allForums[ $t['forum_id'] ]['min_posts_view'] > $t['posts'] )
		{
			return false;
		}
		
		return true;
	}
	
	/**
	 * Determine if topic is approved (or if they are a mod)
	 *
	 * @access	public
	 * @param	array 		Topic/member/forum data
	 * @return	boolean
	 */
	public function checkEmailApproved( $t )
	{
		//-----------------------------------------
		// Test for approved/approve perms
		//-----------------------------------------
		
		if( $t['approved'] == 0 )
		{
			$mod = false;
			
			$memberGroups	= $t['member_group_id'];
			
			if( $t['mgroup_others'] )
			{
				$memberGroups		= array_merge( $memberGroups, explode( ",", IPSText::cleanPermString( $t['mgroup_others'] ) ) );
			}
			
			foreach( $memberGroups as $groupId )
			{
				if( $this->caches['group_cache'][ $groupId ]['g_is_supmod'] == 1 )
				{
					$mod = true;
					break;
				}
			}
			
			if( !$mod )
			{
				if ( count($this->cache->getCache('moderators')) )
				{
					$other_mgroups = array();
					
					if( $t['mgroup_others'] )
					{
						$other_mgroups = explode( ",", IPSText::cleanPermString( $t['mgroup_others'] ) );
					}
					
					foreach( $this->cache->getCache('moderators') as $moderators )
					{
						if ( $moderators['member_id'] == $t['member_id'] or $moderators['group_id'] == $t['member_group_id'] )
						{
							if( $moderators['forum_id'] == $t['forum_id'] )
							{
								$mod = true;
							}
						}
						else if( count($other_mgroups) AND in_array( $moderators['group_id'], $other_mgroups ) )
						{
							if( $moderators['forum_id'] == $t['forum_id'] )
							{
								$mod = true;
							}
						}
					}
				}
			}
			
			if( !$mod )
			{
				return false;
			}
		}
		
		return true;
	}
	
	/**
	* Recache the user's watched forums
	*
	* @access	public
	* @param	int			Member ID
	* @return	boolean
	* @author	Matt
	*/
	public function recacheWatchedForums( $memberID )
	{
		/* INIT */
		$forums   = array();
		$memberID = intval( $memberID );
		
		/* Grab SQL */
		$this->DB->build( array( 'select' => 'forum_id',
								 'from'	  => 'forum_tracker',
								 'where'  => 'member_id=' . $memberID ) );
								
		$this->DB->execute();
	
		while( $row = $this->DB->fetch() )
		{
			if ( $this->registry->permissions->check( 'view', $this->forum_by_id[ $row['forum_id'] ] ) !== TRUE )
			{
				/* Could grab the IDs and remove from the table at some point? */
				continue;
			}
			
			$forums[] = $row['forum_id'];
		}
		
		IPSMember::packMemberCache( $memberID, array( 'watchedForums' => $forums ) );
		
		return TRUE;
	}
	
	/**
	* Build (and optionally save) the last X topic IDs from a forum
	*
	* @access	public
	* @param	int			Forum ID to save
	* @param	boolean		TRUE = SAVE, FALSE = Return Array of IDs
	* @param	int			No. topics to save ( Default is 5 )
	* @return	array 		Array of topic IDs 
	*/
	public function buildLastXTopicIds( $forumID, $save=TRUE, $limit=5 )
	{
		$ids   = array();
		$forum = $this->forum_by_id[ $forumID ];
		
		if ( ! $forumID )
		{
			return array();
		}
		
		//-----------------------------------------
		// Make sure this forum has topics...
		// This causes a problem where the first post
		// in a new forum won't get added as latest topic.
		// Didn't want to try to rejig the process as it's
		// fine otherwise, so just commenting this out.
		//-----------------------------------------
		
		/*if ( ! $forum['topics'] )
		{
			return array();
		}*/
		
		//-----------------------------------------
		// Grab the topics
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => 'tid, start_date',
								 'from'   => 'topics',
								 'where'  => 'forum_id=' . $forumID . ' AND approved=1',
								 'order'  => 'start_date DESC',
								 'limit'  => array( 0, $limit ) ) );
		$o = $this->DB->execute();
		
		while( $row = $this->DB->fetch( $o ) )
		{
			$ids[ $row['tid'] ] = $row['start_date'];
		}
		
		if ( $save === TRUE )
		{
			if ( count( $ids ) )
			{
				$this->DB->update( 'forums', array( 'last_x_topic_ids' => $this->lastXFreeze( $ids ) ), 'id=' . $forumID );
			}
		}
		
		return $ids;
	}
	
	/**
	* Format last X topics string for saving
	*
	* @access	public
	* @param	array 		Array of IDs
	* @return	string		Frozen string format
	*/
	public function lastXFreeze( $ids=array() )
	{
		return serialize( $ids );
	}
	
	/**
	* Format last X topics string for using
	*
	* @access	public
	* @param	array 		Array of IDs
	* @return	string		Thawed string format
	*/
	public function lastXThaw( $idString=array() )
	{
		return unserialize( $idString );
	}
	
}