<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Facilitates reputation plugins
 *
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @link		http://www.
 * @since		Wednesday 14th May 2008 14:00
 */

class classReputationCache
{
	/**
	 * Variable that determines if the reputation system is activated
	 *
	 * @access	public
	 * @var		boolean
	 */
	public $rep_system_on;
	
	/**
	 * Error string
	 *
	 * @access	public
	 * @var		string
	 */
	public $error_message;
	
	/**
	 * CONSTRUCTOR
	 *
	 * @access	public
	 * @return	void
	 */
	public function __construct()
	{
		$this->rep_system_on = ipsRegistry::$settings['reputation_enabled'];
	}
	
	/**
	 * Retuns an array for use in a join statement
	 *
	 * @access	public
	 * @param  	string		$type		Type of content, ex; Post
	 * @param	integer		$type_id	ID of the type, ex: pid
	 * @param  	string		[$app]		App for this content, by default the current application
	 * @return	array
	 */
	public function getTotalRatingJoin( $type, $type_id, $app='' )
	{
		/* Online? */
		if( ! $this->rep_system_on )
		{
			return array();
		}
		
		/* INIT */
		$app = ( $app ) ? $app : ipsRegistry::$current_application;
		
		/* Return the join array */
		return array(
						'select' => 'rep_cache.rep_points',
						'from'   => array( 'reputation_cache' => 'rep_cache' ),
						'where'  => "rep_cache.app='{$app}' AND rep_cache.type='{$type}' AND rep_cache.type_id={$type_id}",
						'type'   => 'left',
					);
	}
	
	/**
	 * Retuns an array for use in a join statement
	 *
	 * @access	public
	 * @param	string		$type		Type of content, ex; Post
	 * @param	integer		$type_id	ID of the type, ex: pid
	 * @param	string		[$app]		App for this content, by default the current application
	 * @return	array
	 */	
	public function getUserHasRatedJoin( $type, $type_id, $app='' )
	{
		/* Online? */
		if( ! $this->rep_system_on )
		{
			return array();
		}
		
		/* INIT */
		$app = ( $app ) ? $app : ipsRegistry::$current_application;
		
		/* Return the join array */
		return array(
						'select' => 'rep_index.rep_rating as has_given_rep',
						'from'   => array( 'reputation_index' => 'rep_index' ),
						'where'  => "rep_index.app='{$app}' AND 
						             rep_index.type='{$type}' AND 
						             rep_index.type_id={$type_id} AND 
						             rep_index.member_id=" . ipsRegistry::member()->getProperty( 'member_id' ),
						'type'   => 'left',
					);
	}
	
	/**
	 * Adds a rating to the index and updates caches
	 *
	 * @access	public
	 * @param	string		$type		Type of content, ex; Post
	 * @param	integer		$type_id	ID of the type, ex: pid
	 * @param	integer		$rating		Either 1 or -1
	 * @param	string		$message	Message associated with this rating
	 * @param	integer		$member_id	Id of the owner of the content being rated
	 * @param	string		[$app]		App for this content, by default the current application
	 * @return	bool
	 */
	public function addRate( $type, $type_id, $rating, $message='', $member_id=0, $app='' )
	{
		ipsRegistry::instance()->getClass('class_localization')->loadLanguageFile( array( 'public_global' ), 'core' );
		
		/* Online? */
		if( ! $this->rep_system_on )
		{
			$this->error_message = ipsRegistry::instance()->getClass( 'class_localization' )->words['reputation_offline'];
			return false;
		}
		
		/* INIT */
		$app       = ( $app ) ? $app : ipsRegistry::$current_application;
		$rating    = intval( $rating );
		
		if( ! ipsRegistry::member()->getProperty( 'member_id' ) )
		{
			$this->error_message = ipsRegistry::instance()->getClass( 'class_localization' )->words['reputation_guest'];
			return false;
		}
		
		if( $rating != -1 && $rating != 1 )
		{
			$this->error_message = ipsRegistry::instance()->getClass( 'class_localization' )->words['reputation_invalid'];
			return false;
		}
		
		/* Check the point types */
		if( $rating == -1 && ipsRegistry::$settings['reputation_point_types'] == 'positive' )
		{
			$this->error_message = ipsRegistry::instance()->getClass( 'class_localization' )->words['reputation_invalid'];
			return false;
		}
		
		if( $rating == 1 && ipsRegistry::$settings['reputation_point_types'] == 'negative' )
		{
			$this->error_message = ipsRegistry::instance()->getClass( 'class_localization' )->words['reputation_invalid'];
			return false;
		}
		
		/* Day Cutoff */
		$day_cutoff = time() - 86400;

		/* Check Max Positive Votes */
		if( $rating == 1 )
		{
			if( intval( ipsRegistry::member()->getProperty( 'g_rep_max_positive' ) ) === 0 )
			{
				$this->error_message = ipsRegistry::instance()->getClass( 'class_localization' )->words['reputation_quota_pos'];
				return false;				
			}
			
			$total = ipsRegistry::DB()->buildAndFetch( array( 
																'select' => 'count(*) as votes', 
																'from'   => 'reputation_index', 
																'where'  => 'member_id=' . ipsRegistry::member()->getProperty( 'member_id' ) . ' AND rep_rating=1 AND rep_date > ' . $day_cutoff
															)	);
					
			if( $total['votes'] >= ipsRegistry::member()->getProperty( 'g_rep_max_positive' ) )
			{
				$this->error_message = ipsRegistry::instance()->getClass( 'class_localization' )->words['reputation_quota_pos'];
				return false;				
			}
		}
		
		/* Check Max Negative Votes */
		if( $rating == -1 )
		{
			if( intval( ipsRegistry::member()->getProperty( 'g_rep_max_negative' ) ) === 0 )
			{
				$this->error_message = ipsRegistry::instance()->getClass( 'class_localization' )->words['reputation_quota_neg'];
				return false;				
			}
			
			$total = ipsRegistry::DB()->buildAndFetch( array( 
																'select' => 'count(*) as votes', 
																'from'   => 'reputation_index', 
																'where'  => 'member_id=' . ipsRegistry::member()->getProperty( 'member_id' ) . ' AND rep_rating=-1 AND rep_date > ' . $day_cutoff
														)	);
													
			if( $total['votes'] >= ipsRegistry::member()->getProperty( 'g_rep_max_negative' ) )
			{
				$this->error_message = ipsRegistry::instance()->getClass( 'class_localization' )->words['reputation_quota_neg'];
				return false;				
			}
		}		
		
		/* If no member id was passted in, we have to query it using the config file */
		if( ! $member_id )
		{
			/* Reputation Config */
			if( file_exists( IPSLib::getAppDir( $app ) . '/extensions/reputation.php' ) )
			{
				require( IPSLib::getAppDir( $app ) . '/extensions/reputation.php' );
			}
			else
			{
				$this->error_message = ipsRegistry::instance()->getClass( 'class_localization' )->words['reputation_config'];
				return false;
			}
			
			if( ! $rep_author_config[$type]['column'] || ! $rep_author_config[$type]['table'] )
			{
				$this->error_message = ipsRegistry::instance()->getClass( 'class_localization' )->words['reputation_config'];
				return false;
			}
			
			/* Query the content author */
			$content_author = ipsRegistry::DB()->buildAndFetch( array(
																		'select' => "{$rep_author_config[$type]['column']} as id",
																		'from'   => $rep_author_config[$type]['table'],
																		'where'  => "{$type}={$type_id}"
															)	);
			
			$member_id = $content_author['id'];
		}
		
		if( ! ipsRegistry::$settings['reputation_can_self_vote'] && $member_id == ipsRegistry::member()->getProperty( 'member_id' ) )
		{
			$this->error_message = ipsRegistry::instance()->getClass( 'class_localization' )->words['reputation_yourown'];
			return false;
		}
		
		/* Query the member group */
		if( ipsRegistry::$settings['reputation_protected_groups'] )
		{
			$member_group = ipsRegistry::DB()->buildAndFetch( array( 'select' => 'member_group_id', 'from' => 'members', 'where' => "member_id={$member_id}" ) );
			
			if( in_array( $member_group['member_group_id'], explode( ',', ipsRegistry::$settings['reputation_protected_groups'] ) ) )
			{
				$this->error_message = ipsRegistry::instance()->getClass( 'class_localization' )->words['reputation_protected'];
				return false;			
			}
		}
		
		/* Build the insert array */
		$db_insert = array(
							'member_id'  => ipsRegistry::member()->getProperty( 'member_id' ),
							'app'        => $app,
							'type'       => $type,
							'type_id'    => $type_id,
							'misc'       => '',
							'rep_date'   => time(),
							'rep_msg'    => $message,
							'rep_rating' => $rating,
						);								
		
		/* Check for existing rating */
		$current_rating = ipsRegistry::DB()->buildAndFetch( array( 
																			'select' => '*', 
																			'from'   => 'reputation_index', 
																			'where'  => "app='{$app}' AND type='{$type}' AND type_id={$type_id} AND member_id=".ipsRegistry::member()->getProperty( 'member_id' ),
																	) 	);

		/* Insert */
		if( $current_rating )
		{
			ipsRegistry::DB()->update( 'reputation_index', $db_insert, "app='{$app}' AND type='{$type}' AND type_id={$type_id} AND member_id=".ipsRegistry::member()->getProperty( 'member_id' ) );
		}
		else
		{
			ipsRegistry::DB()->insert( 'reputation_index', $db_insert );
		}
		
		/* Update type cache */
		$this->_updateTypeCache( $app, $type, $type_id, $rating, $current_rating );

		/* Get authors current rep */
		$author_points = ipsRegistry::DB()->buildAndFetch( array( 
																		'select' => 'pp_reputation_points', 
																		'from'   => 'profile_portal',
																		'where'  => "pp_member_id={$member_id}" 
																)	 );
		
		/* Figure out new rep */
		if( $current_rating['rep_rating'] == -1 )
		{
			$author_points['pp_reputation_points'] += 1;
		}
		else if( $current_rating['rep_rating'] == 1 )
		{
			$author_points['pp_reputation_points'] -= 1;
		}
		$author_points['pp_reputation_points'] += $rating;

		ipsRegistry::DB()->update( 'profile_portal', array( 'pp_reputation_points' => $author_points['pp_reputation_points'] ), "pp_member_id={$member_id}" );
		
		return true;		
	}
	
	/**
	 * Returns an array of reputation information based on the points passed in
	 *
	 * @access	public
	 * @param	integer		$points		Number of points to base the repuation information on
	 * @return	array 					'text' and 'image'
	 */
	public function getReputation( $points )
	{
		/* INIT */
		$cache  = ipsRegistry::cache()->getCache( 'reputation_levels' );
		$points = intval( $points );

		if( count($cache) AND is_array($cache) )
		{
			foreach( $cache as $k => $r )
			{
				if( $r['level_points'] == 0 )
				{
					if( $points >= 0 && $points < intval( $cache[ $k -1 ]['level_points'] ) )
					{
						return array( 'text' => $r['level_title'], 'image' => $r['level_image'] ? ipsRegistry::$settings['public_dir'] . 'style_extra/reputation_icons/' . $r['level_image'] : '' );
					}
					else if( $points <= 0 && $points > intval( $cache[ $k + 1 ]['level_points'] ) )
					{
						return array( 'text' => $r['level_title'], 'image' => $r['level_image'] ? ipsRegistry::$settings['public_dir'] . 'style_extra/reputation_icons/' . $r['level_image'] : '' );
					}
					else if( $points == 0 )
					{
						return array( 'text' => $r['level_title'], 'image' => $r['level_image'] ? ipsRegistry::$settings['public_dir'] . 'style_extra/reputation_icons/' . $r['level_image'] : '' );
					}
				}
				else if( $r['level_points'] > 0 )
				{
					if( $points >= intval( $r['level_points'] ) )
					{
						return array( 'text' => $r['level_title'], 'image' => $r['level_image'] ? ipsRegistry::$settings['public_dir'] . 'style_extra/reputation_icons/' . $r['level_image'] : '' );
					}
				}
				else
				{
					if( $points <= intval( $r['level_points'] ) && $points > intval( $cache[ $k + 1 ]['level_points'] ) )
					{
						return array( 'text' => $r['level_title'], 'image' => $r['level_image'] ? ipsRegistry::$settings['public_dir'] . 'style_extra/reputation_icons/' . $r['level_image'] : '' );	
					}
				}
			}
		}
		
		/* Return the lowest rep, if we're still here */
		$r = array_pop( $cache );
		return array( 'text' => $r['level_title'], 'image' => $r['level_image'] ? ipsRegistry::$settings['public_dir'] . 'style_extra/reputation_icons/' . $r['level_image'] : '' );
	}
	
	/**
	 * Handles updating and creating new caches
	 *
	 * @access	private
	 * @param	string	$app		App for this content
	 * @param	string	$type		Type of content, ex; Post
	 * @param	integer	$type_id	ID of the type, ex: pid
	 * @param	integer	$rating		Either 1 or -1
	 * @param	array 	$old_record	If this is a revote, then this array contains the previous vote
	 * @return	void
	 */
	private function _updateTypeCache( $app, $type, $type_id, $rating, $old_record )
	{		
		/* Update type cache */
		$type_cache = ipsRegistry::DB()->buildAndFetch( array( 
																	'select' => '*', 
																	'from'   => 'reputation_cache', 
																	'where'  => "app='{$app}' AND type='$type' AND type_id='$type_id'",
															)	);
													
		/* Update cache */
		if( $type_cache )
		{
			/* Previous rating? */
			if( $old_record['rep_rating'] == -1 )
			{
				$type_cache['rep_points'] += 1;
			}
			else if( $old_record['rep_rating'] == 1 )
			{
				$type_cache['rep_points'] -= 1;
			}
			
			ipsRegistry::DB()->update( 'reputation_cache', array( 'rep_points' => $type_cache['rep_points'] + $rating ), "app='{$app}' AND type='$type' AND type_id='$type_id'" );
		}
		/* Insert Cache */
		else
		{
			ipsRegistry::DB()->insert( 'reputation_cache', array( 'app' => $app, 'type' => $type, 'type_id' => $type_id, 'rep_points' => $rating ) );
		}		
	}
}