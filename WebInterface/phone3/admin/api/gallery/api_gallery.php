<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Gallery API file
 * Last Updated: $Date: 2009-04-24 10:18:56 -0400 (Fri, 24 Apr 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	IP.Gallery
 * @version		$Rev: 4545 $
 * @since		2.2.0
 *
 */

if ( ! class_exists( 'apiCore' ) )
{
	require_once( IPS_ROOT_PATH . 'api/api_core.php' );
}

class apiGallery extends apiCore
{
	/**
	 * Gallery library object
	 *
	 * @access	public
	 * @var		object
	 */
	public $glib;
	
	/**
	 * Gallery category object
	 *
	 * @access	public
	 * @var		object
	 */
	public $category;
	
	/**
	 * Total images found for result
	 *
	 * @access	public
	 * @var		integer
	 */
	public $total		= 0;
	
	/**
	 * Database result resource
	 *
	 * @access	public
	 * @var		resource
	 */
	public $res;
	
	/**
	 * Constructor.  Calls parent init() method
	 *
	 * @access	public
	 * @return	void
	 */
	public function __construct()
	{
		$this->init();
	}
	
	/**
	* Returns an array of gallery data
	*
	* @access	public
	* @param	integer	Member id
	* @param	integer	Limit
	* @param	integer	true pulls the image results and returns them, false leaves the result set as $this->res
	* @return   mixed	Array of gallery data, or void
	*/
	function return_gallery_data( $member_id=0, $limit=5, $do_pull=true )
	{
		if( !$member_id )
		{
			return array();
		}
		
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$images = array();
		
		$member_id = intval($member_id);
		
		
		$categories = $this->category->getAllowedCats( 0 );
		
 		if( $this->settings['gallery_cache_albums'] )
 		{
 			$albums		= $this->glib->getAllowedAlbums();
		}
		else
		{
			$albums		= true;
		}

		if( !count($categories) AND !count($albums) )
		{
			return array();
		}
		
		$where 				= array();
		$where_statement 	= "";
		
		if( count($categories) )
		{
			$where[] = "i.category_id IN (".implode( ',', $categories ).")";
		}
		
		if( is_array($albums) AND count($albums) )
		{
			$where[] = "i.album_id IN (".implode( ",", $albums ).")";
		}
		else if( $albums == true AND count($categories) )
		{
			$dewhere .= " ( i.album_id > 0 AND a.category_id IN ( ".implode( ",", $categories )." ) ";
			
			if( !$this->memberData['g_mod_albums'] )
			{
				$dewhere .= " AND ( a.public_album=1 OR a.member_id={$this->memberData['member_id']} ) ";
			}
			
			$dewhere .= ")";
			
			$where[] = $dewhere;
		}
		
		if( !count($where) )
		{
			return array();
		}
		else
		{
			$where_statement = implode( " OR ", $where );
		}
		
		
		$this->DB->build( array( 
								'select'	=> 'i.*',
								'from'		=> array( 'gallery_images' => 'i' ),
								'where'		=> "i.approved=1 AND i.member_id={$member_id} AND ({$where_statement})",
								'add_join'	=> array( 
									 				array( 
									 							'type'		=> 'left',
												 				'select'	=> 'a.name as album_name, a.public_album',
												 				'from'		=> array( 'gallery_albums' => 'a' ),
												 				'where'		=> 'a.id=i.album_id',
												 		),
												 	array( 
												 				'type'		=> 'left',
												 				'select'	=> 'c.name as category_name',
												 				'from'		=> array( 'gallery_categories' => 'c' ),
												 				'where'		=> "c.id=i.category_id",
												 		),
												 	array( 
												 				'type'		=> 'left',
												 				'select'	=> 'm.members_display_name',
												 				'from'		=> array( 'members' => 'm' ),
												 				'where'		=> "m.member_id=i.member_id",
												 		),
												 	array( 
												 				'type'		=> 'left',
												 				'select'	=> 'r.id as rated, r.rate as my_rate',
												 				'from'		=> array( 'gallery_ratings' => 'r' ),
												 				'where'		=> "r.img_id=i.id AND r.member_id={$this->memberData['member_id']}",
												 		 )
												 	),												 					
								'order'	=> 'i.idate DESC',
								'limit'	=> array( 0, $limit )
						)	);
										
		$this->res = $this->DB->execute();
		
		$this->total = $this->DB->getTotalRows( $this->res );
		
		if( $do_pull )
		{
			while( $r = $this->DB->fetch() )
			{
				$r['_my_rate'] = $r['my_rate'];
				
				$images[] = $r;
			}
			
			return $images;
		}
		
	}
	
}