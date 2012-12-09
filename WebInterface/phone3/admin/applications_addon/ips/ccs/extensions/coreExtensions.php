<?php

/**
 * Invision Power Services
 * Library: Handle public session data
 * Last Updated: $Date: 2009-08-11 10:01:08 -0400 (Tue, 11 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		IP.CCS
 * @link		http://www.
 * @since		1st March 2009
 * @version		$Revision: 42 $
 *
 */

class publicSessions__ccs
{
	/**
	 * Return session variables for this application
	 *
	 * current_appcomponent, current_module and current_section are automatically
	 * stored. This function allows you to add specific variables in.
	 *
	 * @access	public
	 * @author	Matt Mecham
	 * @return	array
	 */
	public function getSessionVariables()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$type	= '';
		$key	= '';
		
		//-----------------------------------------
		// Load CCS functions
		//-----------------------------------------
		
		$registry	= ipsRegistry::instance();
		
		if( !$registry->isClassLoaded( 'ccsFunctions' ) )
		{
			require_once( IPSLib::getAppDir('ccs') . '/sources/functions.php' );
			$registry->setClass( 'ccsFunctions', new ccsFunctions( $registry ) );
		}

		$folderName	= $registry->ccsFunctions->getFolder();
		$pageName	= $registry->ccsFunctions->getPageName();
		
		if( $pageName )
		{
			$type	= 'page';
			$key	= $pageName;
			$folder	= $folderName;
			
			//-----------------------------------------
			// We don't want to have to run an extra
			// query on every page, so let's just try
			// checking the URL...
			//-----------------------------------------
			
			if( strpos( $key, '.css' ) !== false OR strpos( $key, '.js' ) !== false )
			{
				define( 'NO_SESSION_UPDATE', true );
			}
		}
		else if( ipsRegistry::$request['id'] )
		{
			$type	= 'id';
			$key	= ipsRegistry::$request['id'];
		}

		$array = array( 'location_1_type'   => $type,
						'location_1_id'	 	=> 0,
						'location_2_type'   => $key,
						'location_2_id'	 	=> 0,
						'location_3_type'	=> $folder, );

		return $array;
	}
	
	
	/**
	 * Parse/format the online list data for the records
	 *
	 * @access	public
	 * @author	Brandon Farber
	 * @param	array 			Online list rows to check against
	 * @return	array 			Online list rows parsed
	 */
	public function parseOnlineEntries( $rows )
	{
		//-----------------------------------------
		// No rows
		//-----------------------------------------
		
		if( !is_array($rows) OR !count($rows) )
		{
			return $rows;
		}
		
		//-----------------------------------------
		// Offline
		//-----------------------------------------
		
		if( !ipsRegistry::$settings['ccs_online'] )
		{
			return $rows;
		}

		//-----------------------------------------
		// Init
		//-----------------------------------------
		
		$final	= array();
		$pages	= array();
		$keys	= array();
		$ids	= array();
		
		//-----------------------------------------
		// Extract the page data
		//-----------------------------------------
		
		foreach( $rows as $row )
		{
			if( $row['current_appcomponent'] == 'ccs' )
			{
				if( $row['location_1_type'] == 'page' )
				{
					$keys[ $row['location_2_type'] ]	= $row['location_2_type'];
				}
				else if( $row['location_1_type'] == 'id' )
				{
					$ids[ $row['location_2_type'] ]		= $row['location_2_type'];
				}
			}
		}
		
		//-----------------------------------------
		// Get page library
		//-----------------------------------------
		
		if( count($keys) OR count($ids) )
		{
			require_once( IPSLib::getAppDir('ccs') . '/modules_public/pages/pages.php' );
			$pageLibrary	= new public_ccs_pages_pages( ipsRegistry::instance() );
			$pageLibrary->makeRegistryShortcuts( ipsRegistry::instance() );
		}
		
		//-----------------------------------------
		// Get from DB
		//-----------------------------------------

		if( count($keys) )
		{
			ipsRegistry::DB()->build( array( 'select' => '*', 'from' => 'ccs_pages', 'where' => "page_seo_name IN('" . implode( "','", $keys ) . "')" ) );
			ipsRegistry::DB()->execute();
			
			while( $r = ipsRegistry::DB()->fetch() )
			{
				if( $pageLibrary->canView( $r ) )
				{
					$pages[ md5($r['page_seo_name'] . $r['page_folder']) ]	= $r;
				}
			}
		}
		
		if( count($ids) )
		{
			ipsRegistry::DB()->build( array( 'select' => '*', 'from' => 'ccs_pages', 'where' => "page_id IN(" . implode( ",", $ids ) . ")" ) );
			ipsRegistry::DB()->execute();
			
			while( $r = ipsRegistry::DB()->fetch() )
			{
				if( $pageLibrary->canView( $r ) )
				{
					$pages[ md5($r['page_seo_name'] . $r['page_folder']) ]	= $r;
				}
			}
		}
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'public_lang' ), 'ccs' );

		foreach( $rows as $row )
		{
			if( $row['current_appcomponent'] == 'ccs' )
			{
				if( $row['location_1_type'] == 'page' )
				{
					$_md5Key	= md5($row['location_2_type'] . $row['location_3_type']);
					
					if( $pages[ $_md5Key ] )
					{
						$row['where_line']		= ipsRegistry::getClass( 'class_localization' )->words['session__viewing'] . $pages[ $_md5Key ]['page_name'];
						$row['where_link']		= 'app=ccs&amp;module=pages&amp;section=pages&amp;do=redirect&amp;page=' . $pages[ $_md5Key ]['page_id'];
					}
				}
				else if( $row['location_1_type'] == 'id' )
				{
					$page	= array();
					
					foreach( $pages as $key => $pageData )
					{
						if( $pageData['page_id'] == $row['location_2_type'] )
						{
							$page	= $pageData;
							break;
						}
					}
					
					if( $page['page_id'] )
					{
						$row['where_line']		= ipsRegistry::getClass( 'class_localization' )->words['session__viewing'] . $page['page_name'];
						$row['where_link']		= 'app=ccs&amp;module=pages&amp;section=pages&amp;do=redirect&amp;page=' . $pages[ $row['location_2_type'] ]['page_id'];
					}
				}
			}
			
			$final[ $row['id'] ] = $row;
		}

		return $final;
	}
}
