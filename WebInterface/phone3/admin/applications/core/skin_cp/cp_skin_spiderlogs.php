<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Spiderlogs skin file
 * Last Updated: $Date: 2009-02-04 15:03:36 -0500 (Wed, 04 Feb 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 3887 $
 */
 
class cp_skin_spiderlogs extends output
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
 * Spider logs wrapper
 *
 * @access	public
 * @param	array 		Spider log rows
 * @return	string		HTML
 */
public function spiderlogsWrapper( $rows ) {

$form				= array();

$form['search_for']	= $this->registry->output->formInput( "search_string" );

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['slog_title']}</h2>
</div>

<div class="acp-box">
	<h3>{$this->lang->words['slog_thelogs']}</h3>
	<table class="alternate_rows" width='100%'>
		<tr>
			<th width='20%'>{$this->lang->words['slog_botname']}</th>
			<th width='20%'>{$this->lang->words['slog_hits']}</th>
			<th width='20%'>{$this->lang->words['slog_last']}</th>
			<th width='20%'>{$this->lang->words['slog_viewall']}</th>
			<th width='20%'>{$this->lang->words['slog_removeall']}</th>
		</tr>
HTML;

if( count($rows) AND is_array($rows) )
{
	foreach( $rows as $row )
	{
		$IPBHTML .= <<<HTML
		<tr>
			<td>{$row['_bot_name']}</td>
			<td>{$row['cnt']}</td>
			<td>{$row['_time']}</td>
			<td><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=view&amp;bid={$row['_bot_url']}'>{$this->lang->words['slog_view']}</a></td>
			<td><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=remove&amp;bid={$row['_bot_url']}'>{$this->lang->words['slog_remove']}</a></td>
		</tr>
HTML;
	}
}
else
{
	$IPBHTML .= <<<HTML
		<tr>
			<td colspan='6' align='center'>{$this->lang->words['slog_noresults']}</td>
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
		<h3>{$this->lang->words['slog_search']}</h3>
		<p align="center">{$this->lang->words['slog_searchfor']} {$form['search_for']}{$this->lang->words['slog_searchquery']}</p>
		<div class="acp-actionbar">
			<input value="{$this->lang->words['slog_searchbutton']}" class="button primary" accesskey="s" type="submit" />
		</div>
	</div>
</form>

HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * View a single spider's log entry
 *
 * @access	public
 * @param	array 		Rows
 * @param	string		Page links
 * @return	string		HTML
 */
public function spiderlogsView( $rows, $pages ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['slog_title']}</h2>
</div>

<div class="acp-box">
	<h3>{$this->lang->words['slog_thelogs']}</h3>
	<table class="alternate_rows" width='100%'>
		<tr>
			<th width='25%'>{$this->lang->words['slog_botname']}</th>
			<th width='25%'>{$this->lang->words['slog_querystring']}</th>
			<th width='25%'>{$this->lang->words['slog_time']}</th>
			<th width='25%'>{$this->lang->words['slog_ip']}</th>
		</tr>
HTML;

if( count($rows) AND is_array($rows) )
{
	foreach( $rows as $row )
	{
		$IPBHTML .= <<<HTML
		<tr>
			<td>{$row['_bot_name']}</td>
			<td>{$row['_query_string']}</td>
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
			<td colspan='6' align='center'>{$this->lang->words['slog_noresults']}</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
	<div class='acp-actionbar'>
		<div class="actionleft">{$pages}</div>
	</div>
</div>
<br />
HTML;

//--endhtml--//
return $IPBHTML;
}

}