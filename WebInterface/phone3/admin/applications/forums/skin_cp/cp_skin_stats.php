<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Stats skin functions
 * Last Updated: $LastChangedDate: 2009-06-17 22:28:03 -0400 (Wed, 17 Jun 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Forums
 * @link		http://www.
 * @since		14th May 2003
 * @version		$Rev: 4785 $
 */
 
class cp_skin_stats extends output {

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
 * Show the stats results
 *
 * @access	public
 * @param	string	Title
 * @param	array 	Stats rows
 * @param	integer	Total
 * @return	string	HTML
 */
public function statResultsScreen( $title, $rows, $total ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['stats_title_results']}</h2>
</div>

<div class='acp-box'>
	<h3>{$title}</h3>
	
	<table class='alternate_rows' width='100%'>
		<tr>
			<th width='20%'>{$this->lang->words['stats_date']}</th>
			<th width='70%'>{$this->lang->words['stats_results']}</th>
			<th width='10%'>{$this->lang->words['stats_count']}</th>
		</tr>
HTML;

foreach( $rows as $r )
{
$IPBHTML .= <<<HTML
		<tr>
			<td width='20%'>{$r['_name']}</td>
			<td width='70%'>
				<img src='{$this->settings['skin_acp_url']}/images/bar_left.gif' border='0' width='4' height='11' align='middle' alt=''><img src='{$this->settings['skin_acp_url']}/images/bar.gif' border='0' width='{$r['_width']}' height='11' align='middle' alt=''><img src='{$this->settings['skin_acp_url']}/images/bar_right.gif' border='0' width='4' height='11' align='middle' alt=''>				
			</td>
			<td width='10%'><center>{$r['result_count']}</center></td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
		<tr>
			<td width='20%'>&nbsp;</td>
			<td width='70%'><div align='right'>{$this->lang->words['stats_total']}</div></td>
			<td width='10%'><center><b>{$total}</b></center></td>
		</tr>
	</table>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show the stats main screen
 *
 * @access	public
 * @param	string	Type
 * @param	string	Title
 * @param	array 	Form fields
 * @return	string	HTML
 */
public function statMainScreeen( $type, $title, $form ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['stats_title']}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm'  id='theAdminForm'>
	<input type='hidden' name='do' value='{$type}' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
	
	<div class='acp-box'>
		<h3>{$title}</h3>

		<ul class="acp-form alternate_rows">
			<li>
				<label>{$this->lang->words['stats_datefrom']}</label>
				{$form['from_month']}&nbsp;&nbsp;{$form['from_day']}&nbsp;&nbsp;{$form['from_year']}
			</li>
			
			<li>
				<label>{$this->lang->words['stats_dateto']}</label>
				{$form['to_month']}&nbsp;&nbsp;{$form['to_day']}&nbsp;&nbsp;{$form['to_year']}
			</li>
HTML;

//-----------------------------------------
// Time scale is irrelevant to topic views
//-----------------------------------------

if( $type != 'statsShowTopicViews' )
{
	$IPBHTML .= <<<HTML
			<li>
				<label>{$this->lang->words['stats_timescale']}</label>
				{$form['timescale']}
			</li>
HTML;
}

$IPBHTML .= <<<HTML
			
			<li>
				<label>{$this->lang->words['stats_sorting']}</label>
				{$form['sortby']}
			</li>
		</table>
		
		<div class='acp-actionbar'>
			<div class='centeraction'>
				<input type='submit' value='{$this->lang->words['stats_show']}' class='button primary' accesskey='s'>
			</div>
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

}