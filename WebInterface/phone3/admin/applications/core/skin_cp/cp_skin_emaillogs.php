<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Email logs skin file
 * Last Updated: $Date: 2009-05-28 13:24:24 -0400 (Thu, 28 May 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 4699 $
 */
 
class cp_skin_emaillogs extends output
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
 * Email logs wrapper
 *
 * @access	public
 * @param	array 		Email log rows
 * @param	string		Page links
 * @return	string		HTML
 */
public function emaillogsWrapper( $rows, $links ) {

$form_array = array(
					  0 => array( 'subject'    , $this->lang->words['elog_subject']    ),
					  1 => array( 'content'    , $this->lang->words['elog_body'] ),
					  2 => array( 'email_from' , $this->lang->words['elog_from'] ),
					  3 => array( 'email_to'   , $this->lang->words['elog_to']   ),
					  4 => array( 'name_from'  , $this->lang->words['elog_from_name'] ),
					  5 => array( 'name_to'    , $this->lang->words['elog_to_name'] ),
				   );
				   
$type_array = array(
					  0 => array( 'exact'      , $this->lang->words['elog_exactly'] ),
					  1 => array( 'loose'      , $this->lang->words['elog_loose']   ),
				   );
$form				= array();

$form['type']		= $this->registry->output->formDropdown( "type" , $form_array, $this->request['type'] );
$form['match']		= $this->registry->output->formDropdown( "match", $type_array, $this->request['match'] );
$form['string']		= $this->registry->output->formInput( "string", $this->request['string'] );

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<script type='text/javascript' src='{$this->settings['js_main_url']}acp.forms.js'></script>

<div class='section_title'>
	<h2>{$this->lang->words['elog_title']}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm'  id='theAdminForm'>
<input type='hidden' name='do' value='remove' />
<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
<div class="acp-box">
	<h3>{$this->lang->words['elog_theemails']}</h3>
	<table class="alternate_rows" width='100%'>
		<tr>
			<th width='25%'>{$this->lang->words['elog_from_name']}</th>
			<th width='25%'>{$this->lang->words['elog_subject']}</th>
			<th width='27%'>{$this->lang->words['elog_to_name']}</th>
			<th width='20%'>{$this->lang->words['elog_date']}</th>
			<th width='3%'><input type='checkbox' title="{$this->lang->words['my_checkall']}" id='checkAll' /></th>
		</tr>
HTML;

if( count($rows) AND is_array($rows) )
{
	foreach( $rows as $row )
	{
		$IPBHTML .= <<<HTML
		<tr>
			<!--ACPNOTE: Missing acp_search.gif -->
			<td><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=list&amp;type=fromid&amp;id={$row['member_id']}' title='{$this->lang->words['elog_show_title']}'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/view.png' border='0' alt='{$this->lang->words['elog_byid']}'></a>&nbsp;<b><a href='{$this->settings['board_url']}/index.{$this->settings['php_ext']}?showuser={$row['member_id']}' title='{$this->lang->words['elog_profile']}' target='_blank'>{$row['members_display_name']}</a></b></td>
			<td><a href='#' onclick='return acp.openWindow("{$this->settings['base_url']}&{$this->form_code_js}&do=viewemail&id={$row['email_id']}",400,350,"{$row['email_id']}")' title='{$this->lang->words['elog_read']}'>{$row['email_subject']}</a></td>
			<td><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=list&amp;type=toid&amp;id={$row['to_id']}' title='{$this->lang->words['elog_show_title']}'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/view.png' border='0' alt='{$this->lang->words['elog_byid']}'></a>&nbsp;<b><a href='{$this->settings['board_url']}/index.{$this->settings['php_ext']}?showuser={$row['to_id']}' title='{$this->lang->words['elog_profile']}' target='_blank'>{$row['to_name']}</a></b></td>
			<td>{$row['_date']}</td>
			<td><input type='checkbox' name='id_{$row['email_id']}' value='1' class='checkAll' /></td>
		</tr>
HTML;
	}
}
else
{
	$IPBHTML .= <<<HTML
		<tr>
			<td colspan='5' align='center'>{$this->lang->words['elog_noresults']}</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
	<div class="acp-actionbar">
		<div class="leftaction">{$links}</div>
		<div class="rightaction">
			<input type="checkbox" id="checkbox" name="type" value="all" />&nbsp;{$this->lang->words['elog_removeall']}&nbsp;<input type="submit" value="{$this->lang->words['elog_removechecked']}" class="button primary" />
		</div>
	</div>
</div>
</form>
<br />

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm'  id='theAdminForm'>
	<input type='hidden' name='do' value='list' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />

	<div class="acp-box">
		<h3>{$this->lang->words['elog_search']}</h3>
		<p align="center">{$this->lang->words['elog_searchwhere']} {$form['type']} {$form['match']} {$form['string']}</p>
		<div class="acp-actionbar">
			<input value="{$this->lang->words['elog_searchbutton']}" class="button primary" accesskey="s" type="submit" />
		</div>
	</div>
</form>

HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Email log entry
 *
 * @access	public
 * @param	array 		Email log entry
 * @return	string		HTML
 */
public function emaillogsEmail( $row ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class="acp-box">
	<h3>{$row['email_subject']}</h3>
	<table class="alternate_rows" width='100%'>
		<tr>
			<td width='15%'>{$this->lang->words['elog_log_from']}</td>
			<td>{$row['members_display_name']} &lt;{$row['from_email_address']}&gt;</td>
		</tr>
		<tr>
			<td width='15%'>{$this->lang->words['elog_log_to']}</td>
			<td>{$row['to_name']} &lt;{$row['to_email_address']}&gt;</td>
		</tr>
		<tr>
			<td width='15%'>{$this->lang->words['elog_log_sent']}</td>
			<td>{$row['_date']}</td>
		</tr>
		<tr>
			<td width='15%'>{$this->lang->words['elog_log_ip']}</td>
			<td>{$row['from_ip_address']}</td>
		</tr>
		<tr>
			<td width='15%'>{$this->lang->words['elog_log_subject']}</td>
			<td>{$row['email_subject']}</td>
		</tr>	
		<tr>
			<td colspan='2'>{$row['email_content']}</td>
		</tr>
	</table>
</div>
<br />
HTML;

//--endhtml--//
return $IPBHTML;
}

}