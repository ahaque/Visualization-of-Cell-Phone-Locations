<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Member list
 * Last Updated: $Date: 2009-09-01 05:04:14 -0400 (Tue, 01 Sep 2009) $
 *
 * @author 		$Author: matt $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Members
 * @link		http://www.
 * @since		20th February 2002
 * @version		$Revision: 5067 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_members_list_view extends ipsCommand
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
	 * DB result start point
	 *
	 * @access	private
	 * @var		integer
	 */
	private $first			= 0;

	/**
	 * Maximum results per page
	 *
	 * @access	private
	 * @var		integer
	 */
	private $max_results	= 20;

	/**
	 * Key to sort on
	 *
	 * @access	private
	 * @var		string
	 */
	private $sort_key 		= 'members_display_name';

	/**
	 * Sort order (desc or asc)
	 *
	 * @access	private
	 * @var		string
	 */
	private $sort_order		= 'asc';

	/**
	 * Filter
	 *
	 * @access	private
	 * @var		string
	 */
	private $filter			= 'ALL';
	
	/**
	 * Member titles
	 *
	 * @access	private
	 * @var		array
	 */
	private $mem_titles		= array();

	/**
	 * Member groups
	 *
	 * @access	private
	 * @var		array
	 */
	private $mem_groups		= array();

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

		$this->registry->class_localization->loadLanguageFile( array( 'public_list' ), 'members' );
		
		//-----------------------------------------
		// Can we access?
		//-----------------------------------------
		
		if ( !$this->memberData['g_mem_info'] )
 		{
 			$this->registry->output->showError( 'cannot_view_memberlist', 10221 );
		}

		//-----------------------------------------
		// Init variables
		//-----------------------------------------
		
		$see_groups			= array();
		$the_filter			= array( 'ALL' => $this->lang->words['show_all'] );
		$the_members		= array();
		$query				= array();
		$url				= array();
		$pp_rating_real		= intval( $this->request['pp_rating_real'] );
		$pp_gender			= substr( trim( $this->request['pp_gender'] ), 0, 10 );
		$this->first		= intval($this->request['st']);
		$this->max_results	= ( $this->request['max_results'] ) ? $this->request['max_results'] : '20';
		$this->sort_key		= ( $this->request['sort_key'] )	? $this->request['sort_key']	: 'members_display_name';
		$this->sort_order	= ( $this->request['sort_order'] )  ? $this->request['sort_order']  : 'asc';
		$this->filter		= ( $this->request['filter'] )		? ( $this->request['filter'] == 'ALL' ? 'ALL' : intval( $this->request['filter'] ) ) : 'ALL';

		$this->request['showall']  = intval( $this->request['showall'] );
		$this->request['name_box'] = $this->request['name_box'] ? $this->request['name_box'] : '';

		//-----------------------------------------
		// Set some of the URL params
		//-----------------------------------------
		
		if ( $this->request['quickjump'] )
		{
			$this->request['name_box'] = 'begins';
			$this->request['name']     = $this->request['quickjump'];
		}

		$url['showall']	    = 'showall=' . $this->request['showall'];
		$url['sort_key']	= "sort_key={$this->sort_key}";
		$url['sort_order']	= "sort_order={$this->sort_order}";
		$url['max_results']	= "max_results={$this->max_results}";
		$url['app']	        = "app=members&amp;section=view&amp;module=list";
		$url['quickjump']	= "quickjump={$this->request['quickjump']}";
		$url['name_box']	= 'name_box=' . $this->request['name_box'];
		$url['name']	    = "name={$this->request['name']}";
		
		//-----------------------------------------
		// Sort the member group info
		//-----------------------------------------

		foreach( $this->caches['group_cache'] as $row )
		{
			if ( $row['g_hide_from_list'] )
			{
				if ( ! ( $this->memberData['g_access_cp'] AND $this->request['showall'] ) )
				{
					$hide_ids[] = $row['g_id'];

					continue;
				}
			}
			
			$see_groups[] = $row['g_id'];

			$this->mem_groups[ $row['g_id'] ] = array( 'TITLE'  => $row['g_title'],
													   'ICON'   => $row['g_icon'] );
													   
			if ( $row['g_id'] == $this->settings['guest_group'] )
			{
				continue;
			}
			
			$the_filter[ $row['g_id'] ] = $row['g_title'];
		}
		
		//-----------------------------------------
		// Init some arrays
		//-----------------------------------------
		
		$the_sort_key		= array(
										'members_l_display_name'	=> 'sort_by_name',
										'posts'						=> 'sort_by_posts',
										'joined'					=> 'sort_by_joined',
										'members_profile_views'		=> 'm_dd_views',
								 );
							 
		$the_max_results	= array( 
										10							=> '10',
										20  						=> '20',
										40  						=> '40',
										60  						=> '60',
								);
								
		$the_sort_order		= array(  
										'desc' 						=> 'descending_order',
										'asc'  						=> 'ascending_order',
								   );
							   
		$dropdowns			= array(
										'filter'					=> $the_filter,
										'sort_key'					=> $the_sort_key,
										'sort_order'				=> $the_sort_order,
										'max_results'				=> $the_max_results,
									);

		$defaults			= array(
										'filter'					=> $this->filter,
										'sort_key'					=> $this->sort_key,
										'sort_order'				=> $this->sort_order,
										'max_results'				=> $this->max_results,
										'photoonly'					=> $this->request['photoonly'] == 1 ? 1 : 0,
									);

		//-----------------------------------------
		// Final vars for query
		//-----------------------------------------
		
		$this->sort_key		= isset($the_sort_key[ $this->sort_key ])		? $this->sort_key		: 'members_l_display_name';
		$this->sort_order	= isset($the_sort_order[ $this->sort_order ])	? $this->sort_order		: 'asc';
		$this->filter		= isset($the_filter[ $this->filter ])			? $this->filter			: 'ALL';
		$this->max_results	= isset($the_max_results[ $this->max_results ])	? $this->max_results	: 20;
		
		//-----------------------------------------
		// Get custom profile information
		//-----------------------------------------
		
		require_once( IPS_ROOT_PATH . 'sources/classes/customfields/profileFields.php' );
		$this->custom_fields					= new customProfileFields();		
		$this->custom_fields->initData( 'edit', 1 );
		$this->custom_fields->parseToEdit();		
		
		//-----------------------------------------
		// Member Groups...
		//-----------------------------------------
		
		if ( $this->filter != 'ALL' )
		{
			if ( ! in_array( $this->filter, $see_groups ) )
			{
				$query[] = 'm.member_group_id IN(' . implode( ',', $see_groups ) . ')';
			}
			else
			{
				$query[] = 'm.member_group_id=' . $this->filter;
			}
			
			$url['filter'] = 'filter='.$this->filter;
		}

		//-----------------------------------------
		// NOT IN Member Groups...
		//-----------------------------------------
		
		if ( count( $hide_ids ) )
		{
			$query[] = "m.member_group_id NOT IN(" . implode( ",", $hide_ids ) . ")";
		}
		
		//-----------------------------------------
		// Build query
		//-----------------------------------------
		
		$dates = array( 'lastpost', 'lastvisit', 'joined' );
		
		$mapit = array( 'posts'		=> 'm.posts',
						'joined'	=> 'm.joined',
						'lastpost'	=> 'm.last_post',
						'lastvisit'	=> 'm.last_visit',
						'signature'	=> 'pp.signature',
						'name'		=> 'm.members_display_name',
						'photoonly'	=> 'pp.pp_main_photo',
					  );

		//-----------------------------------------
		// Do search
		//-----------------------------------------
		
		foreach( $mapit as $in => $tbl )
		{
			$this->request[$in] = $this->request[ $in ] ? $this->request[ $in ] : '';
	 		$inbit = IPSText::parseCleanValue( trim( urldecode( IPSText::stripslashes( $this->request[ $in ] ) ) ) );
			
			$url[ $in ] = $in . '=' . $this->request[ $in ];
			
			//-----------------------------------------
			// Name...
			//-----------------------------------------
			
			if ( $in == 'name' and $inbit != "" )
			{
				if ( $this->request['name_box'] == 'begins' )
				{
					$query[] = "m.members_l_display_name LIKE '" . $inbit . "%'";
				}
				else
				{
					$query[] = "m.members_l_display_name LIKE '%" . $inbit . "%'";
				}
			}
			else if ( $in == 'posts' and is_numeric($inbit) and intval($inbit) > -1 )
			{
				$ltmt		= $this->request[ $in .'_ltmt' ] == 'lt' ? '<' : '>';
				$query[]	= $tbl . ' ' . $ltmt . ' ' . intval($inbit);
				$url[ $in ]	= $in . '_ltmt=' . $this->request[ $in .'_ltmt' ] . '&posts=' .  intval($inbit);
			}
			else if ( in_array( $in, $dates ) and $inbit )
			{
				if( preg_match( "/\d{2}-\d{2}-\d{4}/", $this->request[ $in ] ) )
				{	
					$_tmp = explode( '-', $this->request[ $in ] );
					
					$time_int = mktime( 23, 59, 59, $_tmp[0], $_tmp[1], $_tmp[2] );
				}
				else
				{
					$time_int 	= strtotime( $this->request[ $in ] );
				}

				if( $time_int )
				{
					$ltmt 		= $this->request[ $in . '_ltmt' ] == 'lt' ? '<' : '>';
					$query[]	= $tbl . ' ' . $ltmt . ' ' . $time_int;
					$url[ $in ]	= $in . '_ltmt=' . $this->request[ $in . '_ltmt' ];
				}
			}
			else if ( $in == 'photoonly' )
			{
				if ( $this->request['photoonly'] == 1 )
				{
					$query[] = $tbl . "<> ''";
				}
			}
			else if ( $inbit != "" AND $in != 'posts' )
			{
				$query[] = $tbl . " LIKE '%{$inbit}%'";
			}	
		}

		//-----------------------------------------
		// Custom fields?
		//-----------------------------------------
		
		if ( count( $this->custom_fields->out_fields ) )
		{
			foreach( $this->custom_fields->out_fields as $id => $data )
			{
				if ( $this->request[ 'field_' . $id ] AND $this->request[ 'field_' . $id ] )
				{
					if( $this->custom_fields->cache_data[ $id ]['pf_type'] == 'drop' )
					{
						$query[]	= "p.field_{$id}='" . $this->request[ 'field_' . $id ] . "'";
					}
					else
					{
						$query[]	= "p.field_{$id} LIKE '" . $this->request[ 'field_' . $id ] . "%'";
					}
					
					$url[ $in ]		= "field_{$id}=" . $this->request[ 'field_' . $id ];
				}
			}
		}

		//-----------------------------------------
		// Rating..
		//-----------------------------------------
		
		if ( $pp_rating_real )
		{
			$query[]	           = "pp.pp_rating_real > " . $pp_rating_real;
			$url['pp_rating_real'] = "pp_rating_real=" . $pp_rating_real;
		}
		
		//-----------------------------------------
		// Finish query
		//-----------------------------------------
		
		//$query[] = "m.members_l_display_name != ''";
		
		$joins		= array();
		$joins[] 	= array( 'from' => array( 'pfields_content' => 'p' ), 'where' => 'p.member_id=m.member_id', 'type' => 'left' );
		$joins[] 	= array( 'from' => array( 'profile_portal' => 'pp' ), 'where' => 'pp.pp_member_id=m.member_id', 'type' => 'left' );
		
		//-----------------------------------------
		// Reputation
		//-----------------------------------------
		
		if( ! ipsRegistry::isClassLoaded( 'repCache' ) )
		{
			require_once( IPS_ROOT_PATH . 'sources/classes/class_reputation_cache.php' );
			ipsRegistry::setClass( 'repCache', new classReputationCache() );			
		}
		
		//-----------------------------------------
		// START THE LISTING
		//-----------------------------------------
		
		/* Get the count */
		if ( count( $query ) > 1 OR $this->sort_key != 'members_l_display_name' OR $this->request['request_method'] == 'post' OR $this->request['st'] > 0 )
		{
			$_max = $this->DB->buildAndFetch( array( 'select'	 => 'COUNT( * ) as cnt',
									 				 'from'		 => array( 'members' => 'm' ),
													 'where'	 => implode( " AND ", $query ),
													 'add_join'	 => $joins ) );
													 
			$this->DB->build( array( 'select'	 =>' m.member_id',
									 'from'		 => array( 'members' => 'm' ),
									 'where'	 => implode( " AND ", $query ),
									 'order'	 => 'm.' . $this->sort_key . ' ' . $this->sort_order,
									 'limit'	 => array( $this->first, $this->max_results ),
									 'add_join'	 => $joins ) );
		}
		else
		{
			$_max = $this->DB->buildAndFetch( array( 'select'	 => 'COUNT( * ) as cnt',
									 				 'from'		 => 'members m',
													 'where'	 => implode( " AND ", $query ) ) );
													 
			$this->DB->build( array( 'select'	 => 'm.member_id',
									 'from'		 => array( 'members' => 'm' ),
									 'where'	 => implode( " AND ", $query ),
									 'order'	 => ( $this->sort_key == 'pp_profile_views' ? 'pp.' : 'm.' ) . $this->sort_key . ' ' . $this->sort_order,
									 'limit'	 => array( $this->first, $this->max_results ) ) );
		}
								
		/* Fetch IDs */
		$mids  = array();
		$this->DB->execute();

		while( $m = $this->DB->fetch() )
		{
			if( $m['member_id'] )
			{
				$mids[] = $m['member_id'];
			}
		}
	
		if ( count( $mids ) )
		{
			$members = array();
			$_members = IPSMember::load( $mids, 'all' );
			
			/* Make sure that we keep the ordering from the query */
			foreach( $mids as $id )
			{
				$members[$id] = $_members[$id];
			}
		}
		
		$max = $_max['cnt'];

		if( is_array($members) AND count($members) )
		{
			foreach( $members as $id => $member )
			{
				/* Damn SQL thing with member_id */
				if ( ! $member['member_id'] )
				{
					$member['member_id']		= $member['member_table_id'];
				}
	
				$member['members_display_name']	= $member['members_display_name'] ? $member['members_display_name'] : $member['name'];
				$member['members_seo_name']     = IPSMember::fetchSeoName( $member );
				$member['group']				= $this->mem_groups[ $member['member_group_id'] ]['TITLE'];
				$member							= IPSMember::buildProfilePhoto( $member );
				$member['pp_reputation_points'] = $member['pp_reputation_points'] ? $member['pp_reputation_points'] : 0;
				$member['author_reputation']	= ipsRegistry::getClass( 'repCache' )->getReputation( $member['pp_reputation_points'] );		
	
				$the_members[] = $member;
			}
		}

		/* make sure URL doesn't contain empty params */
		$_url = $url;
		$url  = array();
		foreach( $_url as $key => $bit )
		{
			if ( strrpos( $bit, '=' ) + 1 == strlen( $bit ) )
			{
				continue;
			}
			
			$url[] = $bit;
		}

		$pages = $this->registry->output->generatePagination(  array( 'totalItems'			=> $max,
														   			  'itemsPerPage'		=> $this->max_results,
														   			  'currentStartValue'	=> $this->first,
														   			  'baseUrl'				=> implode( '&amp;', $url ) ) );
		
		//-----------------------------------------
		// Print...
		//-----------------------------------------
		
		$this->output .= $this->registry->getClass('output')->getTemplate('mlist')->member_list_show( $the_members, $pages, $dropdowns, $defaults, $this->custom_fields, implode( '&amp;', $url ) );

		//-----------------------------------------
		// Push to print handler
		//-----------------------------------------
		
		$this->registry->output->addContent( $this->output );
		$this->registry->output->setTitle( $this->lang->words['page_title'] );
		$this->registry->output->addNavigation( $this->lang->words['page_title'], 'app=members&amp;module=list' );
		$this->registry->output->sendOutput();
 	}	
}