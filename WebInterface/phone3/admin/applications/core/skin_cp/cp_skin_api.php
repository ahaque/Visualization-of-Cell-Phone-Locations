<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * API users skin file
 * Last Updated: $Date: 2009-08-18 16:46:02 -0400 (Tue, 18 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 5027 $
 */
 
class cp_skin_api extends output
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
 * View api log details
 *
 * @access	public
 * @param	array 		Log record
 * @return	string		HTML
 */
public function api_log_detail( $log ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='acp-box'>
 <h3>{$this->lang->words['a_detail']}</h3>
	<table class='alternate_rows'>
		<tr>
		<td>
			<fieldset>
				<legend>{$this->lang->words['a_basics']}</legend>
				<table width='100%' cellpadding='4' cellspacing='0'>
				 <tr>
					<td width='30%' class='tablerow1'>{$this->lang->words['a_key']}</td>
					<td width='70%' class='tablerow1'><strong>{$log['api_log_key']}</strong></td>
				</tr>
				<tr>
					<td class='tablerow1'>{$this->lang->words['a_ip']}</td>
					<td class='tablerow1'>{$log['api_log_ip']}</td>
				</tr>
				<tr>
					<td class='tablerow1'>{$this->lang->words['a_time']}</td>
					<td class='tablerow1'>{$log['_api_log_date']}</td>
				</tr>
				<tr>
					<td class='tablerow1'>{$this->lang->words['a_success']}</td>
					<td class='tablerow1'><img src='{$this->settings['skin_acp_url']}/images/{$log['_api_log_allowed']}' border='0' alt='-' class='ipd' /></td>
				</tr>
				</table>
			</fieldset>
		<br />
		<fieldset>
			<legend>{$this->lang->words['a_formdata']}</legend>
			<div style='border:1px solid black;background-color:#FFF;padding:4px;white-space:pre;height:400px;overflow:auto'>
				{$log['_api_log_query']}
			</div>
		</fieldset>
	</td>
</tr>
</table>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * View API login logs
 *
 * @access	public
 * @param	array 		Rows
 * @param	string 		Page links
 * @return	string		HTML
 */
public function api_login_view( $logs, $links ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['a_title']}</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['a_requestlog']}</h3>
	<table class='alternate_rows'>
		<tr>
			<th width='1%'>&nbsp;</th>
			<th width='30%'>{$this->lang->words['a_key']}</th>
			<th width='20%'>{$this->lang->words['a_ip']}</th>
			<th width='44%' align='center'>{$this->lang->words['a_date']}</th>
			<th width='5%' align='center'>{$this->lang->words['a_status']}</th>
			<th width='5%' align='center'>{$this->lang->words['a_log']}</th>
		</tr>
EOF;

if ( is_array( $logs ) AND count( $logs ) )
{
	foreach( $logs as $r )
	{
$IPBHTML .= <<<EOF
		<tr>
			<td width='1' valign='middle'>
				<img src='{$this->settings['skin_acp_url']}/images/folder_components/xmlrpc/log_row.png' border='0' alt='-' class='ipd' />
			</td>
			<td><strong>{$r['api_log_key']}</strong></td>
			<td><div class='desctext'>{$r['api_log_ip']}</div></td>
			<td>{$r['_api_log_date']}</td>
			<td><img src='{$this->settings['skin_acp_url']}/images/{$r['_api_log_allowed']}' border='0' alt='-' class='ipd' /></td>
			<td width='1' valign='middle'>
				<a href='#' onclick="return acp.openWindow('{$this->settings['base_url']}{$this->form_code}&amp;do=log_view_detail&amp;api_log_id={$r['api_log_id']}', 800, 600)" title='{$this->lang->words['a_viewdetails']}'><img src='{$this->settings['skin_acp_url']}/images/folder_components/index/view.png' border='0' alt='-' class='ipd' /></a>
			</td>
		</tr>
EOF;
	}
}
$IPBHTML .= <<<EOF
	</table>
	<div class='acp-actionbar'>$links</div>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * List the api users
 *
 * @access	public
 * @param	array 		Rows
 * @return	string		HTML
 */
public function api_list( $api_users ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['a_users']}</h2>
	
	<ul class='context_menu'>
		<li><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=api_add' style='text-decoration:none'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/add.png' alt='' /> {$this->lang->words['a_create']}</a></li>
	</ul>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['a_users']}</h3>
	
	<table class='alternate_rows'>
	<tr>
		<th width='1%'>&nbsp;</th>
		<th width='40%'>{$this->lang->words['a_user']}</th>
		<th width='30%'>{$this->lang->words['a_key']}</th>
		<th width='20%'>{$this->lang->words['a_ip']}</th>
		<th width='5%'>{$this->lang->words['a_options']}</th>
	</tr>
EOF;

if ( count( $api_users ) )
{
	foreach( $api_users as $user )
	{
$IPBHTML .= <<<EOF
 <tr>
	<td><img src='{$this->settings['skin_acp_url']}/images/folder_components/xmlrpc/api_user.png' class='ipb' /></td>
	<td><strong>{$user['api_user_name']}</strong>
	<td><strong style='font-size:14px'>{$user['api_user_key']}</strong>
	<td><strong>{$user['api_user_ip']}</strong>
	<td width='5%'>
		<img class='ipbmenu' id="menu_{$user['api_user_id']}" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt='{$this->lang->words['a_options']}' />
		<ul class='acp-menu' id='menu_{$user['api_user_id']}_menucontent'>
			<li class='icon edit'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=api_edit&amp;api_user_id={$user['api_user_id']}'>{$this->lang->words['a_edit']}</a></li>
			<li class='icon delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=api_remove&amp;api_user_id={$user['api_user_id']}");'>{$this->lang->words['a_remove']}</a></li>
		</ul>
	</td>
</tr>
EOF;
	}
}
else
{
$IPBHTML .= <<<EOF
 <tr>
	<td colspan='5' align='center'>{$this->lang->words['a_nousers']}<br /><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=api_add'>{$this->lang->words['a_createone']}</a></td>
 </tr>
EOF;
}

$IPBHTML .= <<<EOF
 	</table>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Form to add/edit an API user
 *
 * @access	public
 * @param	array 		Form elements
 * @param	string		Form title
 * @param	string		Action code
 * @param	string		Button text
 * @param	array 		API user record
 * @param	string		Type (add|edit)
 * @param	array 		Permission types
 * @return	string		HTML
 */
public function api_form( $form, $title, $formcode, $button, $api_user, $type, $permissions ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF

<form id='mainform' action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=$formcode&amp;api_user_id={$api_user['api_user_id']}' method='post'>
	<div class='acp-box'>
 		<h3>$title</h3>
		
 		<ul class='acp-form alternate_rows'>
			<li><label class='head'>{$this->lang->words['a_userbasics']}</label></li>
EOF;
if ( $type == 'add' )
{
$IPBHTML .= <<<EOF
			<li>
				<label>{$this->lang->words['a_userkey']}<span class='desctext'>{$this->lang->words['a_key_info']}</span></label>
				<input type='hidden' name='api_user_key' value='{$form['_api_user_key']}' />
				{$form['_api_user_key']}
			</li>
EOF;
}

$IPBHTML .= <<<EOF
			<li>
				<label>{$this->lang->words['a_usertitle']}<span class='desctext'>{$this->lang->words['a_usertitle_info']}</span></label>
				{$form['api_user_name']}
			</li>
			<li>
				<label>{$this->lang->words['a_restrictip']}<span class='desctext'>{$this->lang->words['a_restrictip_info']}</span></label>
				{$form['api_user_ip']}
			</li>
		</ul>
	</div>
	<br />
	<div class='acp-box'>		
		<ul id='tabstrip_apiperms' class='tab_bar no_title'>
EOF;

if ( is_array( $permissions ) AND count( $permissions ) )
{
	foreach( $permissions as $key => $data )
	{
$IPBHTML .= <<<EOF
			<li id='tabtab-{$key}'>{$data['title']}</li>
EOF;
	}
}

$IPBHTML .= <<<EOF
		</ul>
		
		<script type="text/javascript" defer="defer">
		//<![CDATA[
			document.observe("dom:loaded",function() 
			{
			ipbAcpTabStrips.register('tabstrip_apiperms');
			ipbAcpTabStrips.doToggle($('tabtab-ipb'));
			});
		 //]]>
		</script>
		
EOF;

if ( is_array( $permissions ) AND count( $permissions ) )
{
	foreach( $permissions as $key => $data )
	{
$IPBHTML .= <<<EOF
		<div id='tabpane-{$key}'>
			<ul class='acp-form alternate_rows'>
EOF;
		if ( is_array( $permissions[ $key ]['form_perms'] ) AND ( $permissions[ $key ]['form_perms'] ) )
		{
			foreach( $permissions[ $key ]['form_perms'] as $perm => $_data )
			{
$IPBHTML .= <<<EOF
				<li>
					<label>{$this->lang->words['a_allowaccess']} {$_data['title']}</label>
					{$_data['form']}
				</li>
EOF;
			}
		}
$IPBHTML .= <<<EOF
			</ul>
		</div>
EOF;
	}
}

$IPBHTML .= <<<EOF

		<div class='acp-actionbar'>
			<div class='centeraction'>
				<input type='submit' value=' $button ' class='button primary' />
			</div>
		</div>
	</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

}