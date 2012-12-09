<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Tagging Functions
 * Last Updated: $Date: 2009-09-01 17:01:18 -0400 (Tue, 01 Sep 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @link		http://www.
 * @since		9th March 2005 11:03
 * @version		$Revision: 5075 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class tagFunctions
{
	/**#@+
	 * Registry objects
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
	/**@#-*/
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @return	void
	 */
	public function __construct( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Make object
		//-----------------------------------------
		
		$this->registry = $registry;
		$this->DB	    = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->member   = $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache	= $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
	}
	
	/**
	 * Retrieve tags to parse out in the HTML output
	 *
	 * @access	public
	 * @param	string		HTML to search out replacements in
	 * @param	array 		Where clause information
	 * @param	string		URL to prepend to the tag
	 * @param	string		Replacement string to find (use ? and pass "watch_for" in $where clause)
	 * @return	string		HTML to use for tags
	 */
	public function parseTags( $html, $where, $url, $replacement )
	{
		if( !is_array($where) OR !count($where) OR !$url )
		{
			return;
		}
		
		$whereClause		= array();
		$output				= array();
		
		if( $where['app'] )
		{
			$whereClause[]	= "app='{$where['app']}'";
		}
		
		if( $where['type'] )
		{
			$whereClause[]	= "type='{$where['type']}'";
		}
		
		if( $where['type_id'] )
		{
			$whereClause[]	= "type_id" . ( strtolower($where['type_id_type']) == 'in' ? " IN(" : "=" ) . $where['type_id'] . ( strtolower($where['type_id_type']) == 'in' ? ")" : "" );
		}
		
		if( $where['type_2'] )
		{
			$whereClause[]	= "type_2='{$where['type_2']}'";
		}
		
		if( $where['type_id_2'] )
		{
			$whereClause[]	= "type_id_2" . ( strtolower($where['type_id_2_type']) == 'in' ? " IN(" : "=" ) . $where['type_id_2'] . ( strtolower($where['type_id_2_type']) == 'in' ? ")" : "" );
		}
		
		if( !count($whereClause) )
		{
			return '';
		}
		
		$this->DB->build( array(
									'select'	=> '*',
									'from'		=> 'tags_index',
									'where'		=> implode( ' AND ', $whereClause ),
							)		);
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$output[ $r[ $where['watch_for'] ] ][]     = "<a href='{$url}" . urlencode($r['tag']) . "'>{$r['tag']}</a>";
		}

		foreach( $output as $id => $foundTags )
		{
			$thisTags	= implode( ", ", $foundTags );
			
			$html		= str_replace( str_replace( '?', $id, $replacement), $thisTags, $html );
		}
		
		return $html;
	}
	
	/**
	 * Retrieve a tag cloud
	 *
	 * @access	public
	 * @param	array 		Where clause information
	 * @param	integer		Total number of items
	 * @param	string		URL to prepend to the tag
	 * @param	string		SEO Title
	 * @param	string		SEO Template
	 * @return	string		HTML to use for tags
	 */
	public function getTagCloud( $where, $items, $url, $seoTitle='', $seoTemplate='' )
	{
		if( !is_array($where) OR !count($where) OR !$url )
		{
			return;
		}
		
		$whereClause		= array();
		$output				= array();
		
		if( $where['app'] )
		{
			$whereClause[]	= "app='{$where['app']}'";
		}
		
		if( $where['type'] )
		{
			$whereClause[]	= "type='{$where['type']}'";
		}
		
		if( $where['type_id'] )
		{
			$whereClause[]	= "type_id" . ( strtolower($where['type_id_type']) == 'in' ? " IN(" : "=" ) . $where['type_id'] . ( strtolower($where['type_id_type']) == 'in' ? ")" : "" );
		}
		
		if( $where['type_2'] )
		{
			$whereClause[]	= "type_2='{$where['type_2']}'";
		}
		
		if( $where['type_id_2'] )
		{
			$whereClause[]	= "type_id_2" . ( strtolower($where['type_id_2_type']) == 'in' ? " IN(" : "=" ) . $where['type_id_2'] . ( strtolower($where['type_id_2_type']) == 'in' ? ")" : "" );
		}
		
		if( !count($whereClause) )
		{
			return '';
		}
		
		$this->DB->build( array(
									'select'	=> 'COUNT(tag) as times, tag',
									'from'		=> 'tags_index',
									'where'		=> implode( ' AND ', $whereClause ),
									'group'		=> 'tag',
									'order'		=> 'tag ASC',
							)		);
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$size		= round( $r['times'], $items );
			$size		= $size < 1 ? 1 : $size;
			$size		= $size > 6 ? 6 : $size;
			
			if( $seoTitle && $seoTemplate )
			{
				$_url = $this->registry->output->formatUrl( "{$url}{$r['tag']}", $seoTitle, $seoTemplate );
			}
			else
			{
				$_url = "{$url}{$r['tag']}";
			}
			
			$output[]	= "<li class='level{$size}'><a href='{$_url}' rel='tag'>{$r['tag']}</a></li>";
		}

		return "<ul class='tagList'>" . implode( "\n", $output ) . "</ul>";
	}
	
	/**
	 * Store tags
	 * Takes the tags and stores them in the database
	 *
	 * @access	public
	 * @param	string		Comma-separated tags list
	 * @param	array		Where clause array
	 * @param	integer		[Optional] Member id
	 * @return	integer		Number of tags stored
	 */
	public function storeTags( $tags, $where, $member_id=0 )
	{
		if( !is_array($where) OR !count($where) OR !$tags )
		{
			return 0;
		}
		
		$whereClause		= array();
		$tags				= explode( ',', $tags );
		$count				= 0;
		
		if( $where['app'] )
		{
			$whereClause[]	= "app='{$where['app']}'";
		}
		
		if( $where['type'] )
		{
			$whereClause[]	= "type='{$where['type']}'";
		}
		
		if( $where['type_id'] )
		{
			$whereClause[]	= "type_id" . ( strtolower($where['type_id_type']) == 'in' ? " IN(" : "=" ) . $where['type_id'] . ( strtolower($where['type_id_type']) == 'in' ? ")" : "" );
		}
		
		if( $where['type_2'] )
		{
			$whereClause[]	= "type_2='{$where['type_2']}'";
		}
		
		if( $where['type_id_2'] )
		{
			$whereClause[]	= "type_id_2" . ( strtolower($where['type_id_2_type']) == 'in' ? " IN(" : "=" ) . $where['type_id_2'] . ( strtolower($where['type_id_2_type']) == 'in' ? ")" : "" );
		}
		
		if( !count($whereClause) )
		{
			return 0;
		}

		$this->DB->delete( 'tags_index', implode( ' AND ', $whereClause ) );
		
		foreach( $tags as $tag )
		{
			$tag = trim( $tag );
			
			if( !$tag )
			{
				continue;
			}
			
			$insert	= array(
							'app'		=> $where['app'],
							'tag'		=> $tag,
							'updated'	=> time(),
							'member_id'	=> $member_id ? $member_id : $this->memberData['member_id'],
							'type'		=> $where['type'],
							'type_id'	=> $where['type_id'],
							'type_2'	=> $where['type_2'],
							'type_id_2'	=> $where['type_id_2'],
							'misc'		=> '',
							);
			
			$this->DB->insert( 'tags_index', $insert );
			
			$count++;
		}
		
		return $count;
	}
}