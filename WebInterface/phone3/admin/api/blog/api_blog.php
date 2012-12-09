<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Blog API file
 * Last Updated: $Date: 2009-02-04 15:03:59 -0500 (Wed, 04 Feb 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	IP.Gallery
 * @link		http://www.
 * @version		$Rev: 3887 $
 * @since		2.2.0
 *
 */

if ( ! class_exists( 'apiCore' ) )
{
	require_once( IPS_ROOT_PATH . 'api/api_core.php' );
}

class apiBlog extends apiCore
{
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
	* Retrieve the Blog ID of a member
	*
	* @param	int		Member ID
	* @return 	int		Blog ID ( 0 if no Blog found )
	*/
	public function getBlogID( $member_id=0 )
	{
		$member_id = intval( $member_id );

		//-----------------------------------------
		// We entered here on member id, find blog_id and redirect them to here
		//-----------------------------------------
		$blog = $this->DB->buildAndFetch( array( 
												'select'	=> 'blog_id, blog_name',
												'from'		=> 'blog_blogs',
												'where'		=> "member_id={$member_id}" 
										)	);
		if( ! $blog['blog_id'] or ! $blog['blog_name'] )
		{
			return 0;
   		}
		else
		{
			return $blog['blog_id'];
		}
	}

	/**
	* Retrieve the Blog ID of a member
	*
	* @param	int		Blog ID
	* @return 	string	Blog URL
	*/
	public function getBlogUrl( $blog_id=0 )
	{
        //-----------------------------------------
		// Load the Blog functions library
        //-----------------------------------------
		if( ! ipsRegistry::isClassLoaded( 'blog_std' ) )
		{
			/* Load the Blog functions library */
			require_once( IPSLib::getAppDir( 'blog' ) . '/sources/lib/lib_blogfunctions.php' );
			$this->registry->setClass( 'blog_std', new blogFunctions( $this->registry ) );
		}

		$blog_id = intval( $blog_id );

		//-----------------------------------------
		// We entered here on member id, find blog_id and redirect them to here
		//-----------------------------------------
		$blog = $this->DB->buildAndFetch( array ( 
												'select'	=> 'blog_id, blog_name',
												'from'		=> 'blog_blogs',
												'where'		=> "blog_id={$blog_id}" 
										)	);
		if( ! $blog['blog_id'] or ! $blog['blog_name'] )
		{
			return '';
   		}
		else
		{
			return $this->registry->blog_std->getBlogUrl( $blog_id );
		}
	}

	/**
	* Retrieve the last x entries of a Blog
	* Only public publishes entries are returned
	*
	* @param	string	Either 'member' or 'blog'
	* @param	int		Blog ID or Member ID
	* @param	int		Number of entries (to return)
	* @return 	array	Array of last X entries
	*/
	public function lastXEntries( $type, $id, $number_of_entries=10 )
	{
		/* INIT */
		$id = intval( $id );
	
        //-----------------------------------------
		// Load the Blog functions library
        //-----------------------------------------
		if( ! ipsRegistry::isClassLoaded( 'blog_std' ) )
		{
			/* Load the Blog functions library */
			require_once( IPSLib::getAppDir( 'blog' ) . '/sources/lib/lib_blogfunctions.php' );
			$this->registry->setClass( 'blog_std', new blogFunctions( $this->registry ) );
		}

        //-----------------------------------------
		// Build the permissions
        //-----------------------------------------
		$this->registry->blog_std->buildPerms();

		if( $this->memberData['g_blog_settings']['g_blog_allowview'] )
		{
			//-----------------------------------------
			// Private Club Authed?
			//-----------------------------------------
			$extra = "";
			$allowguests = "";
			
			if( ! $this->memberData['member_id'] )
			{
				$allowguests .= " AND b.blog_allowguests = 1";
			}
			
			if( ! $this->memberData['_blogmod']['moderate_can_view_private'] )
			{
				$extra = " AND ( ( ( p.owner_only=1 AND b.member_id={$this->memberData['member_id']} ) OR p.owner_only=0 ) AND ( p.authorized_users LIKE '%,{$this->memberData['member_id']},%' OR p.authorized_users IS NULL ) ) ";
			}
			
			$qtype = $type == 'member' ? "b.member_id={$id}" : "b.blog_id={$id}";

			$this->DB->build( array( 
										'select'	=> "e.*",
								        'from'		=> array('blog_entries' => 'e'),
							            'add_join'=> array( 
															array( 
																	'select' => 'b.blog_name',
																	'from'   => array( 'blog_blogs' => 'b' ),
																	'where'  => "e.blog_id=b.blog_id",
																	'type'   => 'left'
																),
															array(
																	'from'	=> array( 'permission_index' => 'p' ),
																	'where'	=> "p.perm_type_id=b.blog_id AND p.perm_type='blog'",
																	'type'	=> 'left'
																)
														),
										'where'	=> "{$qtype} AND b.blog_type='local' AND e.entry_status='published'".$allowguests.$extra,
										'order'	=> 'e.entry_date DESC',
										'limit'	=> array( 0, intval( $number_of_entries ) )
							  )	);
			$outer = $this->DB->execute();

			$return_array = array();
			while( $entry = $this->DB->fetch($outer) )
			{
				$entry['blog_url'] = $this->registry->blog_std->getBlogUrl( $entry['blog_id'], $entry['blog_friendly_url'] );
				$entry['entry_url'] = $entry['blog_url'].'showentry='.$entry['entry_id'];
				$return_array[] = $entry;
			}
			return $return_array;
	  	}
		else
		{
			return array();
		}
	}

}

?>