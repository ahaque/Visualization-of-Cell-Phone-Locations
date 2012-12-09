<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Portal plugin: portal
 * Last Updated: $Date: 2009-02-04 15:03:36 -0500 (Wed, 04 Feb 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Portal
 * @since		1st march 2002
 * @version		$Revision: 3887 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class ppi_portal extends public_portal_portal_portal 
{
	/**
	 * Initialize module
	 *
	 * @access	public
	 * @return	void
	 */
	public function init()
 	{
 	}
 	
	/**
	 * Show the site navgiational block
	 *
	 * @access	public
	 * @return	string		HTML content to replace tag with
	 */
	public function portal_sitenav()
	{
 		if ( ! $this->settings['csite_nav_show'] )
 		{
 			return;
 		}
 		
 		$links		= array();
 		$raw_nav	= $this->settings['csite_nav_contents'];
 		
 		foreach( explode( "\n", $raw_nav ) as $l )
 		{
 			$l = str_replace( "&#039;", "'", $l );
 			$l = str_replace( "&quot;", '"', $l );
 			$l = str_replace( '{board_url}', $this->settings['base_url'], $l );
 			
 			preg_match( "#^(.+?)\[(.+?)\]$#is", trim($l), $matches );
 			
 			$matches[1] = trim($matches[1]);
 			$matches[2] = trim($matches[2]);
 			
 			if ( $matches[1] and $matches[2] )
 			{
	 			$matches[1] = str_replace( '&', '&amp;', str_replace( '&amp;', '&', $matches[1] ) );
	 			
	 			$links[] = $matches;
 			}
 		}
 		
 		if( !count($links) )
 		{
 			return;
 		}

 		return $this->registry->getClass('output')->getTemplate('portal')->siteNavigation( $links );
  	}
  	
	/**
	 * Show the affiliates block
	 *
	 * @access	public
	 * @return	string		HTML content to replace tag with
	 */
	public function portal_affiliates()
	{
 		if ( ! $this->settings['csite_fav_show'] )
 		{
 			return;
 		}
 		
		$this->settings['csite_fav_contents'] = str_replace( "&#039;", "'", $this->settings['csite_fav_contents'] );
		$this->settings['csite_fav_contents'] = str_replace( "&quot;", '"', $this->settings['csite_fav_contents'] );
		$this->settings['csite_fav_contents'] = str_replace( '{board_url}', $this->settings['base_url'], $this->settings['csite_fav_contents'] );
 		
 		return $this->registry->getClass('output')->getTemplate('portal')->affiliates();
 	}

}