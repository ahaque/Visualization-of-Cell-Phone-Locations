<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Performance mode skin file
 * Last Updated: $Date: 2009-04-14 08:05:32 -0400 (Tue, 14 Apr 2009) $
 *
 * @author 		$Author: rikki $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Core
 * @link		http://www.
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 4455 $
 */
 
class cp_skin_performance extends output
{

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
 * Show the results from toggling the mode
 *
 * @access	public
 * @param	bool		Currently on or off
 * @param	array 		Actions that were taken
 * @return	string		HTML
 */
public function toggleResults( $current, $actions=array() )
{
$IPBHTML = "";
//--starthtml--//

$text	= $current ? "<div style='font-weight: bold; color: red; font-size: 22px;'>" . $this->lang->words['perf_on'] . "</div>" : "<div style='font-weight: bold; color: green; font-size: 22px;'>" . $this->lang->words['perf_off'] . "</div>";

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['perf_help_title']}</h2>
</div>

<div class='section_info'>
	{$this->lang->words['perf_help_information']}
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['perf_current']}</h3>
	
	<div class='acp-row_off' style='padding: 10px;'>
		{$text}
	</div>
HTML;

if( count($actions) )
{
	$IPBHTML .= "<ul style='list-style: disc; margin-left: 40px'>";
	foreach( $actions as $action )
	{
		$IPBHTML .= <<<HTML
				<li style='padding: 3px 5px'>
				 	{$action}
				</li>
HTML;
	}
	$IPBHTML .= "</ul>";
}

$IPBHTML .= <<<HTML
	<div class='acp-actionbar' style='padding-top: 12px'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=toggle' class='button'>{$this->lang->words['perf_toggle']}</a></div>
</div>
<br />
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show the overview page
 *
 * @access	public
 * @param	string		Content
 * @return	string		HTML
 */
public function overview( $current )
{
$IPBHTML = "";
//--starthtml--//

$text	= $current ? "<div style='font-weight: bold; color: red; font-size: 22px;'>" . $this->lang->words['perf_on'] . "</div>" : "<div style='font-weight: bold; color: green; font-size: 22px;'>" . $this->lang->words['perf_off'] . "</div>";


$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['perf_help_title']}</h2>
</div>

<div class='section_info'>
	{$this->lang->words['perf_help_information']}
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['perf_current']}</h3>
	
	<div class='acp-row_off' style='padding: 10px;'>
		{$text}
	</div>
	
	<div class='acp-actionbar' style='padding-top: 12px'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=toggle' class='button'>{$this->lang->words['perf_toggle']}</a></div>
</div>
<br />
HTML;

//--endhtml--//
return $IPBHTML;


}

}