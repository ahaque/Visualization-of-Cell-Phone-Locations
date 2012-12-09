<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Live Search
 * Last Updated: $Date: 2009-08-17 10:10:50 -0400 (Mon, 17 Aug 2009) $
 *
 * @author 		$Author: matt $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Core
 * @link		http://www.
 * @version		$Rev: 5019 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class admin_core_ajax_livesearch extends ipsAjaxCommand 
{
	/**
	 * HTML to output
	 *
	 * @access	private
	 * @var		string
	 */	
	private $output;

	/**
	 * Skin object
	 *
	 * @access	private
	 * @var		object			Skin templates
	 */
	private $html;
	
	/**
	 * Class entry point
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Load skin
		//-----------------------------------------
		$this->registry->class_localization->loadLanguageFile( array( 'admin_ajax' ) );
		$this->html = $this->registry->output->loadTemplate('cp_skin_livesearch');
		
		/* What to do */
		switch( $this->request['do'] )
		{
			case 'search':
				$this->doSearchRequest();
			break;
			
			case 'template':
			default:
				$this->getTemplate();
			break;
		}
		
		/* Output */
		$this->returnHtml( $this->output );		
	}
	
	/**
	 * Fetches the live search template
	 *
	 * @access	public
	 * @return	void
	 */
	public function getTemplate()
	{
		$this->output .= $this->html->liveSearchTemplate();
	}
	
	/**
	 * Handles the live search
	 *
	 * @access	public
	 * @return	void
	 */
	public function doSearchRequest()
	{
		/* INI */
		$search_term = $this->request['search_term'];
		
		$results	= array();
		$return		= array( 'settings' => null, 'location' => null );
		$max		= 20;
		$count		= 0;
		$secCount	= 0;
		
		/* Do search here */
		$results	= $this->_getSettings( $search_term, $results );
		
		$results	= $this->_getFromXML( $search_term, $results );
		if( isset( $results['settings'] ) AND is_array( $results['settings'] ) AND count($results['settings']) )
		{
			foreach( $results['settings'] as $setting )
			{
				$count++;
				$secCount++;
				$return['settings'] .= $this->html->searchRowSetting( $setting, $count, $secCount );
				
				/*if( $count >= $max )
				{
					break;
				}*/
			}
		}

		$secCount = 0;
		
		if( isset( $results['location'] ) AND is_array( $results['location'] ) AND count($results['location']))
		{
			foreach( $results['location'] as $location )
			{
				$count++;
				$secCount++;
				$return['location'] .= $this->html->searchRowLocation( $location, $count, $secCount );
				
				/*if( $count >= $max )
				{
					break;
				}*/
			}
		}

		/* Output */
		$this->output .= $this->html->liveSearchDisplay( $return, $search_term );
	}
	
	/**
	 * Searches the settings table
	 *
	 * @access	private
	 * @param	string		Search term
	 * @param	array 		Existing search results
	 * @return	array 		New search results
	 */
	private function _getSettings( $term, $results )
	{
		$this->DB->build( array(
									'select'	=> 'conf_group, conf_title, conf_description, conf_keywords',
									'from'		=> 'core_sys_conf_settings',
									'where'		=> $this->DB->buildLower('conf_title') . " LIKE '%{$term}%' OR ". $this->DB->buildLower('conf_description') . " LIKE '%{$term}%' OR " . $this->DB->buildLower('conf_keywords') . " LIKE '%{$term}%'"
							)		);
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$results['settings'][] = $r;
		}
	
		return $results;
	}
	
	/**
	 * Searches the XML Files
	 *
	 * @access	private
	 * @param	string		Search term
	 * @param	array 		Existing search results
	 * @return	array 		New search results
	 */
	private function _getFromXML( $term, $results )
	{
		foreach( $this->cache->getCache('app_menu_cache') as $app => $cache )
		{
			foreach( $cache as $entry )
			{
				if( count($entry['items']) )
				{
					foreach( $entry['items'] as $item )
					{
						if( $item['section'] )
						{
							$item['url']	= "section={$item['section']}&amp;" . $item['url'];
						}

						if( isset($item['keywords']) AND stripos( $item['keywords'], $term ) !== false )
						{
							$item['url'] = "&amp;app={$app}&amp;module={$item['module']}&amp;{$item['url']}";
							$results['location'][] = $item;
						}
						else if( stripos( $item['title'], $term ) !== false )
						{
							$item['fullurl'] = "&amp;app={$app}&amp;module={$item['module']}&amp;{$item['url']}";
							$results['location'][] = $item;
						}
					}
				}
			}
		}
		
		return $results;
	}
}