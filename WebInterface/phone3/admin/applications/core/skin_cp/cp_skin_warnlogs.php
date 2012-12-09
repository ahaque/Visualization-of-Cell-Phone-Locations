<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Warn log skin file
 * Last Updated: $Date: 2009-03-27 11:41:38 -0400 (Fri, 27 Mar 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 4333 $
 */
 
class cp_skin_warnlogs extends output
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
 * Warn logs wrapper
 *
 * @access	public
 * @param	array 		Records
 * @param	array 		Members
 * @return	string		HTML
 */
public function warnlogsWrapper( $rows, $members ) {

$form_array 		= array(
							0 => array( 'notes'		, $this->lang->words['wlog_notes'] ),
							1 => array( 'contact'	, $this->lang->words['wlog_emailpm']  ),
							);
$form				= array();

$form['search_for']	= $this->registry->output->formInput( "search_string" );
$form['search_in']	= $this->registry->output->formDropdown( "search_type", $form_array );

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['wlog_title']}</h2>
</div>

<div class="acp-box">
	<h3>{$this->lang->words['wlog_last10']}</h3>
	<table class="alternate_rows" width='100%'>
		<tr>
			<th width='10%'>{$this->lang->words['wlog_type']}</th>
			<th width='25%'>{$this->lang->words['wlog_warned']}</th>
			<th width='15%' style='text-align: center'>{$this->lang->words['wlog_wascontacted']}</th>
			<th width='25%'>{$this->lang->words['wlog_date']}</th>
			<th width='25%'>{$this->lang->words['wlog_warnedby']}</th>
		</tr>
HTML;

if( count($rows) AND is_array($rows) )
{
	foreach( $rows as $row )
	{
		$IPBHTML .= <<<HTML
		<tr>
			<td>{$row['_type']}</td>
			<td>{$row['_a_name']}</td>
			<td style='text-align: center'>{$row['_cont']}</td>
			<td>{$row['_date']}</td>
			<td>{$row['p_name']}</td>
		</tr>
HTML;
	}
}
else
{
	$IPBHTML .= <<<HTML
		<tr>
			<td colspan='6' align='center'>{$this->lang->words['wlog_noresults']}</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
</div>
<br />

<div class="acp-box">
	<h3>{$this->lang->words['wlog_thelogs']}</h3>
	<table class="alternate_rows" width='100%'>
		<tr>
			<th width='30%'>{$this->lang->words['wlog_member']}</th>
			<th width='20%'>{$this->lang->words['wlog_times']}</th>
			<th width='20%'>{$this->lang->words['wlog_viewall']}</th>
			<th width='30%'>{$this->lang->words['wlog_removeall']}</th>
		</tr>
HTML;

if( count($members) AND is_array($members) )
{
	foreach( $members as $row )
	{
		$IPBHTML .= <<<HTML
		<tr>
			<td>{$row['members_display_name']}</td>
			<td>{$row['act_count']}</td>
			<td><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=view&amp;mid={$row['wlog_mid']}'>{$this->lang->words['wlog_viewlog']}</a></td>
			<td><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=remove&amp;mid={$row['wlog_mid']}'>{$this->lang->words['wlog_remove']}</a></td>
		</tr>
HTML;
	}
}
else
{
	$IPBHTML .= <<<HTML
		<tr>
			<td colspan='4' align='center'>{$this->lang->words['wlog_noresults']}</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
</div>
<br />

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm'  id='theAdminForm'>
	<input type='hidden' name='do' value='view' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />

	<div class="acp-box">
		<h3>{$this->lang->words['wlog_search']}</h3>
		<ul class="acp-form alternate_rows">
			<li>
				<label>{$this->lang->words['wlog_searchfor']}</label>
				{$form['search_for']}
			</li>
			<li>
				<label>{$this->lang->words['wlog_searchin']}</label>
				{$form['search_in']}
			</li>
		</ul>	
		<div class="acp-actionbar">
			<input value="{$this->lang->words['wlog_searchbutton']}" class="button primary" accesskey="s" type="submit" />
		</div>
	</div>
</form>

HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * View an individual member's log
 *
 * @access	public
 * @param	array 		Log rows
 * @param	string		Page links
 * @return	string		HTML
 */
public function warnlogsView( $rows, $pages ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['wlog_title']}</h2>
</div>

<div class="acp-box">
	<h3>{$this->lang->words['wlog_thelogs']}</h3>
	<table class="alternate_rows" width='100%'>
		<tr>
			<th width='5%'>{$this->lang->words['wlog_type']}</th>
			<th width='15%'>{$this->lang->words['wlog_member']}</th>
			<th width='10%'>{$this->lang->words['wlog_wascontacted']}</th>
			<th width='10%'>{$this->lang->words['wlog_modq']}</th>
			<th width='10%'>{$this->lang->words['wlog_susp']}</th>
			<th width='10%'>{$this->lang->words['wlog_nopost']}</th>
			<th width='15%'>{$this->lang->words['wlog_date']}</th>
			<th width='15%'>{$this->lang->words['wlog_warnedby']}</th>
			<th width='10%'>{$this->lang->words['wlog_viewnotes']}</th>
		</tr>
HTML;

if( count($rows) AND is_array($rows) )
{
	foreach( $rows as $row )
	{
		$IPBHTML .= <<<HTML
		<tr>
			<td>{$row['_type']}</td>
			<td>{$row['_a_name']}</td>
			<td>{$row['_cont']}</td>
			<td>{$row['_mod']}</td>
			<td>{$row['_susp']}</td>
			<td>{$row['_post']}</td>
			<td>{$row['_date']}</td>
			<td>{$row['p_name']}</td>
			<td><a href='#' onclick='return acp.openWindow("{$this->settings['base_url']}&{$this->form_code}&do=viewnote&id={$row['wlog_id']}",400,400,"{$this->lang->words['wlog_log']}"); return false;'>{$this->lang->words['wlog_viewlog']}</a></td>
		</tr>
HTML;
	}
}
else
{
	$IPBHTML .= <<<HTML
		<tr>
			<td colspan='9' align='center'>{$this->lang->words['wlog_noresults']}</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
	<div class='acp-actionbar'>
		<div class="leftaction">{$pages}</div>
	</div>
</div>
<br />
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * View a member's warn log note
 *
 * @access	public
 * @param	array 		Note data
 * @return	string		HTML
 */
public function warnlogsNote( $row ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class="acp-box">
	<h3>{$this->lang->words['wlog_warnnotes']}</h3>
	<table class="alternate_rows" width='100%'>
		<tr>
			<td width='15%'>{$this->lang->words['wlog_log_from']}</td>
			<td>{$row['p_name']}</td>
		</tr>
		<tr>
			<td width='15%'>{$this->lang->words['wlog_log_to']}</td>
			<td>{$row['a_name']}</td>
		</tr>
		<tr>
			<td width='15%'>{$this->lang->words['wlog_log_sent']}</td>
			<td>{$row['_date']}</td>
		</tr>
		<tr>
			<td colspan='2'>{$row['_text']}</td>
		</tr>
	</table>
</div>
<br />
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * View contact information for a warn log
 *
 * @access	public
 * @param	array 		Warn log details
 * @return	string		HTML
 */
public function warnlogsContact( $row ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class="acp-box">
	<h3>{$row['_type']}: {$row['_subject']}</h3>
	<table class="alternate_rows" width='100%'>
		<tr>
			<td width='15%'><strong>{$this->lang->words['wlog_log_from']}</strong></td>
			<td>{$row['p_name']}</td>
		</tr>
		<tr>
			<td width='15%'>{$this->lang->words['wlog_log_to']}</td>
			<td>{$row['a_name']}</td>
		</tr>
		<tr>
			<td width='15%'>{$this->lang->words['wlog_log_sent']}</td>
			<td>{$row['_date']}</td>
		</tr>
		<tr>
			<td width='15%'>{$this->lang->words['wlog_log_subject']}</td>
			<td>{$row['_subject']}</td>
		</tr>		
		<tr>
			<td colspan='2'>{$row['_text']}</td>
		</tr>
	</table>
</div>
<br />
HTML;

//--endhtml--//
return $IPBHTML;
}

}