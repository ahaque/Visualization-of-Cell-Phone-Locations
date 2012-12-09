<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * ACP live search skin file
 * Last Updated: $Date: 2009-06-15 20:38:43 -0400 (Mon, 15 Jun 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Core
 * @link		http://www.
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 4777 $
 */
 
class cp_skin_livesearch extends output
{
	/**
	 * Currently hiding settings
	 *
	 * @access	private
	 * @var		bool
	 */
	private $startedHideSettings	= false;
	
	/**
	 * Currently hiding page links
	 *
	 * @access	private
	 * @var		bool
	 */
	private $startedHidePages		= false;
	
/**
 * Prevent our main destructor being called by this class
 *
 * @access	public
 * @return	void
 */
public function __destruct()
{
}

/**
 * Display the live search results
 *
 * @access	public
 * @param	array 		Results
 * @param	string		Current search term
 * @return	string		HTML
 */
public function liveSearchDisplay( $results, $search_term ) {
	
$IPBHTML = "";
//--starthtml--//
$IPBHTML .= <<<EOF
	<ul>
EOF;

if( count($results) )
{
	foreach( $results as $key => $output )
	{
		if( $key == 'settings' )
		{
			$text	= $this->lang->words['ls_settings'];
		}
		else if( $key == 'location' )
		{
			$text	= $this->lang->words['ls_acppages'];
		}
		
		if( !$output )
		{
			continue;
		}
		
		$IPBHTML .= <<<EOF
		<li>
			<span class='section'>{$text} <img src='{$this->settings['skin_acp_url']}/_newimages/icons_livesearch/{$key}.png' alt='{$this->lang->words['icon']}' /></span>
			<ol>
				{$output}
			</ol>
EOF;

// If we started hidding settings, we need to stop hiding them now...
if( $key == 'settings' AND $this->startedHideSettings )
{
	$this->startedHideSettings = false;
	$IPBHTML .= <<<EOF
	</div>
	<div style='text-align: center;' id='hideSettingsShow'>
		<a href='#' onclick="$('hideSettingsShow').hide();$('hideSettings').show();clearTimeout( ACPLiveSearch.searchTimer['hide'] );return false;">{$this->lang->words['ls_view_more_settings']}</a>
	</div>
EOF;
}
// If we started hidding pages, we need to stop hiding them now...
else if( $key == 'location' AND $this->startedHidePages )
{
	$this->startedHidePages = false;
	$IPBHTML .= <<<EOF
	</div>
	<div style='text-align: center;' id='hidePagesShow'>
		<a href='#' onclick="$('hidePagesShow').hide();$('hidePages').show();clearTimeout( ACPLiveSearch.searchTimer['hide'] );return false;">{$this->lang->words['ls_view_more_acp']}</a>
	</div>
EOF;
}


$IPBHTML .= <<<EOF
		</li>
EOF;
	}
}
else
{
	$IPBHTML .= <<<EOF
	<li><em>No results</em></li>
EOF;
}

$IPBHTML .= <<<EOF
	</ul>
EOF;

//--endhtml--//
return $IPBHTML;
}
	
/**
 * Result for settings
 *
 * @access	public
 * @param	array 		Result
 * @param	int			Total count
 * @param	int			Section count
 * @return	string		HTML
 */
public function searchRowSetting( $r, $count, $secCount ) {
$IPBHTML = "";
//--starthtml--//

if( $secCount > 10 )
{
	if( !$this->startedHideSettings )
	{
		$this->startedHideSettings = true;
		
		$IPBHTML .= <<<EOF
		</ol>
		<div id='hideSettings' style='display:none;'>
		<ol style='margin-top:0px;'>
EOF;
	}
}
$IPBHTML .= <<<EOF
<li><a href='{$this->settings['_base_url']}&amp;app=core&amp;module=tools&amp;section=settings&amp;do=setting_view&amp;conf_group={$r['conf_group']}&amp;search={$this->request['search_term']}'>{$r['conf_title']}</a></li>
EOF;
//--endhtml--//
return $IPBHTML;
}

/**
 * Result for acp pages
 *
 * @access	public
 * @param	array 		Result
 * @param	int			Total count
 * @param	int			Section count
 * @return	string		HTML
 */
public function searchRowLocation( $r, $count, $secCount ) {
$IPBHTML = "";

if( $secCount > 10 )
{
	if( !$this->startedHidePages )
	{
		$this->startedHidePages = true;
		
		$IPBHTML .= <<<EOF
		</ol>
		<div id='hidePages' style='display:none;'>
		<ol style='margin-top:0px;'>
EOF;
	}
}

//--starthtml--//
$IPBHTML .= <<<EOF
<li><a href='{$this->settings['_base_url']}{$r['fullurl']}'>{$r['title']}</a></li>
EOF;
//--endhtml--//
return $IPBHTML;
}

/**
 * Live search template
 *
 * @access	public
 * @return	string		HTML
 */
public function liveSearchTemplate() {
$IPBHTML = "";
//--starthtml--//
$IPBHTML .= <<<EOF
	<div id='search_stem'></div>
	<div id='search_inner'>
		<h3 class='bar'>{$this->lang->words['ls_quick_search']}</h3>
		<div id='ajax_result'></div>
	</div>
EOF;
//--endhtml--//
return $IPBHTML;
}

}