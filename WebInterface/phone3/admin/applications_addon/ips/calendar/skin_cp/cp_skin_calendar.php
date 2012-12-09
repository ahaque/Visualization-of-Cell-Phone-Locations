<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Calendar skin file
 * Last Updated: $Date: 2009-06-30 12:06:12 -0400 (Tue, 30 Jun 2009) $
 *
 * @author 		$Author: josh $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Calendar
 * @link		http://www.
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 4829 $
 */
 
class cp_skin_calendar extends output
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
 * Form to add/edit a calendar
 *
 * @access	public
 * @param	array 		Form elements
 * @param	string		Form title
 * @param	string		Action code
 * @param	string		Button text
 * @param	array 		Calendar data
 * @return	string		HTML
 */
public function calendarForm($form, $title, $formcode, $button, $calendar) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['c_title']}</h2>
</div>


<form id='adminform' action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=$formcode&amp;cal_id={$calendar['cal_id']}' method='post'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->getClass('adminFunctions')->_admin_auth_key}' />
	
	<div class='acp-box'>
		<h3>$title</h3>
		
		<ul class='acp-form alternate_rows'>
			<li>
				<label>{$this->lang->words['c_block_title']}</label>
				{$form['cal_title']}
			</li>
			<li>
				<label>{$this->lang->words['c_block_mod']}<span class='desctext'>{$this->lang->words['c_block_mod_info']}</span></label>
				{$form['cal_moderate']}
			</li>
			<li>
				<label>{$this->lang->words['c_block_limit']}<span class='desctext'>{$this->lang->words['c_block_limi_info']}</span></label>
				{$form['cal_event_limit']}
			</li>
			<li>
				<label>{$this->lang->words['c_block_bday']}<span class='desctext'>{$this->lang->words['c_block_bday_info']}</span></label>
				{$form['cal_bday_limit']}
			</li>
			
			<li><label class='head'>{$this->lang->words['c_block_rss']}</label></li>
     
			<li>
				<label>{$this->lang->words['c_block_enable']}<span class='desctext'>{$this->lang->words['c_block_enabled_info']}</span></label>
				<td width='60%' class='tablerow2'>{$form['cal_rss_export']}</td>
			</li>
			<li>
				<label>{$this->lang->words['c_block_forthcoming']}</label>
				{$form['cal_rss_export_days']}
			</li>
			<li>
				<label>{$this->lang->words['c_block_max']}<span class='desctext'>{$this->lang->words['c_block_max_info']}</span></label>
				{$form['cal_rss_export_max']}
			</li>
			<li>
				<label>{$this->lang->words['c_block_freq']}<span class='desctext'>{$this->lang->words['c_block_freq_info']}</span></label>
				{$form['cal_rss_update']}
			</li>
 		</ul>
 		
 		{$form['perm_matrix']}
 		
 		<div class='acp-actionbar'>
 			<div class='centeraction'>
 				<input type='submit' class='button primary' value='$button' />
 			</div>
 		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Calendar overview screen
 *
 * @access	public
 * @param	array 		Calendars
 * @return	string		HTML
 */
public function calendarOverviewScreen( $rows ) {

$IPBHTML = "";

//--starthtml--//
$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['c_title']}</h2>
	<!-- ACPNOTE: Rikki - need these two icons -->
	<ul class='context_menu'>
		<li>
			<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=calendar_add' title='{$this->lang->words['c_addcal']}'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/add.png' alt='' />
				{$this->lang->words['c_addcal']}
			</a>
		</li>
		<li>
			<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=calendar_rebuildcache' title='{$this->lang->words['c_recachecal']}'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/arrow_refresh.png' alt='' />
				{$this->lang->words['c_recachecal']}
			</a>
		</li>
	</ul>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['c_calendars']}</h3>

		<ul id='sortable_handle' class='alternate_rows'>
HTML;

foreach( $rows as $r )
{
$IPBHTML .= <<<HTML
			<li id='calendar_{$r['cal_id']}'>
				<table style='width: 100%'>
					<tr>
						<td style='width: 3%'>
							<div class='draghandle'><img src='{$this->settings['skin_acp_url']}/_newimages/drag_icon.png' alt=''></div>
						</td>
						<td style='width: 93%'>
							<strong>{$r['cal_title']}</strong><br />
						</td>
						<td style='width: 4%'>
							<img id="menu{$r['cal_id']}" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' border='0' alt='{$this->lang->words['c_options']}' class='ipbmenu' />
							<ul class='acp-menu' id='menu{$r['cal_id']}_menucontent'>
								<li class='icon edit'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=calendar_edit&amp;cal_id={$r['cal_id']}'>{$this->lang->words['c_editcal']}</a></li>
								<li class='icon refresh'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=calendar_rss_cache&amp;cal_id={$r['cal_id']}'>{$this->lang->words['c_rebuildcal']}</a></li>
								<li class='icon delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}&amp;do=calendar_delete&amp;cal_id={$r['cal_id']}");'>{$this->lang->words['c_deletecal']}</a></li>
							</ul>
						</td>
					</tr>
				</table>
			</li>
HTML;
}

$IPBHTML .= <<<HTML
		</ul>

</div>
<script type='text/javascript'>

window.onload = function() {
	Sortable.create( 'sortable_handle', { revert: true, format: 'calendar_([0-9]+)', onUpdate: dropItLikeItsHot, handle: 'draghandle' } );
};

dropItLikeItsHot = function( draggableObject, mouseObject )
{
	var options = {
					method : 'post',
					parameters : Sortable.serialize( 'sortable_handle', { tag: 'li', name: 'calendars' } )
				};
 
	new Ajax.Request( "{$this->settings['base_url']}&{$this->form_code_js}&do=calendar_move&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' ), options );

	return false;
};
</script>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * RSS recurring event
 *
 * @access	public
 * @param	array 		Event data
 * @return	string		HTML
 */
public function calendar_rss_recurring( $event ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<p>{$event['event_content']}</p>
<br />
<p>{$this->lang->words['c_recurring']}
<br />{$this->lang->words['c_fromcolon']} {$event['_from_month']}/{$event['_from_day']}/{$event['_from_year']}
<br />{$this->lang->words['c_tocolon']} {$event['_to_month']}/{$event['_to_day']}/{$event['_to_year']}</p>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * RSS ranged event
 *
 * @access	public
 * @param	array 		Event data
 * @return	string		HTML
 */
public function calendar_rss_range( $event ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<p>{$event['event_content']}</p>
<br />
<p>{$this->lang->words['c_ranged']}
<br />{$this->lang->words['c_fromcolon']} {$event['_from_month']}/{$event['_from_day']}/{$event['_from_year']}
<br />{$this->lang->words['c_tocolon']} {$event['_to_month']}/{$event['_to_day']}/{$event['_to_year']}</p>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * RSS single day event
 *
 * @access	public
 * @param	array 		Event data
 * @return	string		HTML
 */
public function calendar_rss_single( $event ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<p>{$event['event_content']}</p>
<br />
<p>{$this->lang->words['c_singleday']} {$event['_from_month']}/{$event['_from_day']}/{$event['_from_year']}</p>
HTML;

//--endhtml--//
return $IPBHTML;
}

}
