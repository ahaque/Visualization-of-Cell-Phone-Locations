<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Moderator log skin file
 * Last Updated: $Date: 2009-03-27 11:41:38 -0400 (Fri, 27 Mar 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 4333 $
 */
 
class cp_skin_modlogs extends output
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
 * Moderator logs wrapper
 *
 * @access	public
 * @param	array 		Rows
 * @param	array 		Moderators
 * @return	string		HTML
 */
public function modlogsWrapper( $rows, $admins ) {

$form_array 		= array(
							0 => array( 'topic_title'	, $this->lang->words['mlog_topictitle'] ),
							1 => array( 'ip_address'	, $this->lang->words['mlog_ip'] ),
							2 => array( 'member_name'	, $this->lang->words['mlog_name'] ),
							3 => array( 'topic_id'		, $this->lang->words['mlog_tid'] ),
							4 => array( 'forum_id'		, $this->lang->words['mlog_fid'] )
						);
$form				= array();

$form['search_for']	= $this->registry->output->formInput( "search_string" );
$form['search_in']	= $this->registry->output->formDropdown( "search_type", $form_array );

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['mlog_title']}</h2>
</div>

<div class="acp-box">
	<h3>{$this->lang->words['mlog_last5']}</h3>
	<table class="alternate_rows" width='100%'>
		<tr>
			<th width='15%'>{$this->lang->words['mlog_member']}</th>
			<th width='15%'>{$this->lang->words['mlog_action']}</th>
			<th width='15%'>{$this->lang->words['mlog_forum']}</th>
			<th width='25%'>{$this->lang->words['mlog_topictitle']}</th>
			<th width='20%'>{$this->lang->words['mlog_date']}</th>
			<th width='10%'>{$this->lang->words['mlog_ip']}</th>
		</tr>
HTML;

if( count($rows) AND is_array($rows) )
{
	foreach( $rows as $row )
	{
		$IPBHTML .= <<<HTML
		<tr>
			<td>{$row['members_display_name']}</td>
			<td><span style='font-weight:bold;color:red'>{$row['action']}</span></td>
			<td><b>{$row['name']}</b></td>
			<td>{$row['topic_title']}{$row['topic']}</td>
			<td>{$row['_time']}</td>
			<td>{$row['ip_address']}</td>
		</tr>
HTML;
	}
}
else
{
	$IPBHTML .= <<<HTML
		<tr>
			<td colspan='6' align='center'>{$this->lang->words['mlog_noresults']}</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
</div>
<br />

<div class="acp-box">
	<h3>{$this->lang->words['mlog_thelogs']}</h3>
	<table class="alternate_rows" width='100%'>
		<tr>
			<th width='30%'>{$this->lang->words['mlog_member']}</th>
			<th width='20%'>{$this->lang->words['mlog_action']}</th>
			<th width='20%'>{$this->lang->words['mlog_viewall']}</th>
			<th width='30%'>{$this->lang->words['mlog_removeall']}</th>
		</tr>
HTML;

if( count($admins) AND is_array($admins) )
{
	foreach( $admins as $row )
	{
		$IPBHTML .= <<<HTML
		<tr>
			<td>{$row['members_display_name']}</td>
			<td>{$row['act_count']}</td>
			<td><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=view&amp;mid={$row['member_id']}'>{$this->lang->words['mlog_view']}</a></td>
			<td><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=remove&amp;mid={$row['member_id']}'>{$this->lang->words['mlog_remove']}</a></td>
		</tr>
HTML;
	}
}
else
{
	$IPBHTML .= <<<HTML
		<tr>
			<td colspan='4' align='center'>{$this->lang->words['mlog_noresults']}</td>
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
		<h3>{$this->lang->words['mlog_search']}</h3>
	
		<ul class="acp-form alternate_rows">
			<li>
				<label>{$this->lang->words['mlog_searchfor']}</label>
				{$form['search_for']}
			</li>
			<li>
				<label>{$this->lang->words['mlog_searchin']}</label>
				{$form['search_in']}
			</li>
		</ul>	

		<div class="acp-actionbar">
			<input value="{$this->lang->words['mlog_searchbutton']}" class="button primary" accesskey="s" type="submit" />
		</div>
	</div>
</form>

HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * View a single moderator log entry
 *
 * @access	public
 * @param	array 		Records
 * @param	string		Page links
 * @return	string		HTML
 */
public function modlogsView( $rows, $pages ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['mlog_title']}</h2>
</div>

<div class="acp-box">
	<h3>{$this->lang->words['mlog_thelogs']}</h3>
	<table class="alternate_rows" width='100%'>
		<tr>
			<th width='15%'>{$this->lang->words['mlog_member']}</th>
			<th width='15%'>{$this->lang->words['mlog_action']}</th>
			<th width='15%'>{$this->lang->words['mlog_forum']}</th>
			<th width='25%'>{$this->lang->words['mlog_topictitle']}</th>
			<th width='20%'>{$this->lang->words['mlog_date']}</th>
			<th width='10%'>{$this->lang->words['mlog_ip']}</th>
		</tr>
HTML;

if( count($rows) AND is_array($rows) )
{
	foreach( $rows as $row )
	{
		$IPBHTML .= <<<HTML
		<tr>
			<td>{$row['members_display_name']}</td>
			<td><span style='font-weight:bold;color:red'>{$row['action']}</span></td>
			<td><b>{$row['name']}</b></td>
			<td>{$row['topic_title']}{$row['topic']}</td>
			<td>{$row['_time']}</td>
			<td>{$row['ip_address']}</td>
		</tr>
HTML;
	}
}
else
{
	$IPBHTML .= <<<HTML
		<tr>
			<td colspan='6' align='center'>{$this->lang->words['mlog_noresults']}</td>
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

}