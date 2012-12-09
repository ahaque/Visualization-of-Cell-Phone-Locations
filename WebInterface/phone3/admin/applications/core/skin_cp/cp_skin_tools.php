<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * ACP Tools skin file
 * Last Updated: $Date: 2009-08-17 20:55:28 -0400 (Mon, 17 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 5022 $
 */
 
class cp_skin_tools extends output
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
 * User agents group form
 *
 * @access	public
 * @param	array 		Form elements
 * @param	string		Form title
 * @param	string		Action code
 * @param	string		Button text
 * @param	array 		User agent group data
 * @param	array 		User agents
 * @return	string		HTML
 */
public function uagents_groupForm($form, $title, $formcode, $button, $ugroup, $userAgents) {

$IPBHTML = "";
//--starthtml--//

$_json      = json_encode( array( 'uagents' => $userAgents ) );
$_groupJSON = ( is_array( $ugroup['_groupArray'] ) AND count( $ugroup['_groupArray'] ) ) ? json_encode( $ugroup['_groupArray'] ) : '{}';

$IPBHTML .= <<<EOF
<!--SKINNOTE: CSS directly in skin file here-->
<style type='text/css'>
/*
ALL THESE ARE USED WITHIN ipb3uAgents.js
*/

/*.uAgentsGHover
{
	background-color: #D3DAE4;
	background-image: url("{$this->settings['skin_acp_url']}/images/acpMenuMore.png");
	background-repeat: no-repeat;
	background-position: center right;
	cursor: pointer;
	padding:8px;
}

.uAgentsGRow
{
	padding:8px;
	border-bottom:1px solid #ddd;
}

.uAgentsGRHover
{
	background-color: #D3DAE4;
	cursor: pointer;
	padding:8px;
}

.uAgentsGRRow
{
	padding:8px;
	border-bottom:1px solid #ddd;
}*/
</style>
<script type="text/javascript" src="{$this->settings['js_app_url']}ipb3uAgents.js"></script>
<form id='uAgentsForm' action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=$formcode&amp;ugroup_id={$ugroup['ugroup_id']}' method='post'>
<input id='uAgentsData' type='hidden' name='uAgentsData' value='$_groupJSON' />
<div class='acp-box'>
	<h3>$title</h3>
	<ul class='acp-form alternate_rows'>
		<li>
			<label>{$this->lang->words['t_uatitle']}</label>
			{$form['ugroup_title']}
		</li>
	</ul>
	<div class='acp-row-off' id='uagents_groups'>
		<table width='100%' cellpadding='0' cellspacing='0'>
			<tr>
				<th style='width: 50%'>{$this->lang->words['t_uaallavail']}</th>
				<th style='width: 50%'>{$this->lang->words['t_uagroups']}</th>
			</tr>
			<tr>
				<td style='padding: 8px'>
					<div id='tplate_agentsList' class='uagent_list'></div>
				</td>
				<td>
					<div id='tplate_groupList' class='uagent_list'></div>
				</td>
			</tr>
		</table>
	</div>
	<div class='acp-actionbar'>
	 	<input type='button'  class="button primary" value=' $button ' onclick='IPB3UAgents.saveGroupForm()' />
	</div>
</div>
</form>
<!-- templates -->
<div style='display:none'> 
	<div id='tplate_agentRow'>
		<div id='tplate_agentrow_#{uagent_id}' onmouseover='IPB3UAgents.groupMouseEvent(event)' onmouseout='IPB3UAgents.groupMouseEvent(event)' onclick='IPB3UAgents.groupMouseEvent(event)' class='#{_cssClass}'>
			<div>
				<img id='tplate_agentimg_#{uagent_id}' src="{$this->settings['skin_acp_url']}/images/folder_components/uagents/" class='ipd' />
				<span style='font-weight:bold'>#{uagent_name}</span>
			</div>
		</div>	
	</div>
	<div id='tplate_groupRow'>
		<div id='tplate_grouprow_#{uagent_id}' onmouseover='IPB3UAgents.groupUsedMouseEvent(event)' onmouseout='IPB3UAgents.groupUsedMouseEvent(event)' onclick='IPB3UAgents.groupUsedMouseEvent(event)' class='#{_cssClass}'>
			<div id='tplate_grouprow_#{uagent_id}_remove' style='float:right;margin-right:10px;cursor:pointer'>[ {$this->lang->words['t_uaremove']} ]</div>
			<div id='tplate_grouprow_#{uagent_id}_configure' style='float:right;margin-right:10px;cursor:pointer;display:none;font-size:10px'>{$this->lang->words['t_uaversions']} #{uagent_versions} [ {$this->lang->words['t_uaconfigure']} ]</div>
			<div>
				<img id='tplate_groupimg_#{uagent_id}' src="{$this->settings['skin_acp_url']}/images/folder_components/uagents/" class='ipd' />
				<span style='font-weight:bold'>#{uagent_name}</span>
			</div>
		</div>	
	</div>
	<div id='tplate_versionsEditor'>
		<div id='tplate_versions_#{uagent_id}' class='tableborder' style='width:500px'>
			<div class='tableheaderalt'>{$this->lang->words['t_uaediting']} #{uagent_name}</div>
			<div class='tablerow2' style='padding:10px'>
				<div>
{$this->lang->words['t_ua_info']}
				</div>
				Versions:
				<input type='text' id='tplate_versionsBox_#{uagent_id}' value='#{uagent_versions}' style='width:100%;' />
			</div>
			<div class='tablerow2' style='text-align:right;'>
				<input type='button'  class="button primary" value=' {$this->lang->words['t_uasave']} ' onclick='IPB3UAgents.saveAgentVersion(#{uagent_id})' />
				&nbsp;
				<input type='button'  class="button primary" value=' {$this->lang->words['t_uaclose']} ' onclick='IPB3UAgents.cancelAgentVersion(#{uagent_id})' />
			</div>
		</div>
	</div>
</div>
<!-- /templates -->
<script type='text/javascript'>
	var IPB3UAgents              = new IPBUAgents();
	IPB3UAgents.uAgentsData      = $_json;
	IPB3UAgents.uAgentsGroupData = $_groupJSON;
	IPB3UAgents.groupFormInit();
 //]]>
</script>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * User agents groups wrapper
 *
 * @access	public
 * @param	array 		User agent groups
 * @return	string		HTML
 */
public function uagents_listUagentGroups( $userAgentGroups ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['t_ua_groups']}</h2>
	
	<ul class='context_menu'>
		<li><a href='{$this->settings['base_url']}&amp;module=tools&amp;section=uagents&amp;do=groupAdd' style='text-decoration:none'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/add.png' alt='' /> {$this->lang->words['ua_addnewbutton']}</a></li>
	</ul>
</div>
<div class='acp-box'>
	<h3>{$this->lang->words['t_ua_groups']}</h3>
	<table class='alternate_rows'>
		
EOF;

if ( is_array( $userAgentGroups ) AND count( $userAgentGroups ) )
{
	foreach( $userAgentGroups as $ugroup_id => $data )
	{
		$IPBHTML .= <<<EOF
		<tr>
			<td>
				<div style='float:left;padding-top:2px;'>
					<img src='{$this->settings['skin_acp_url']}/images/folder_components/uagents/group.png' class='ipd' />
					<strong>{$data['ugroup_title']}</strong>
				</div>
				
				<div align='right' style='height:20px;padding:0px 5px 0px 0px;'>
					<span class='desctext'>{$data['_arrayCount']}</span> &nbsp;
					&nbsp; 
					<img class="ipbmenu" id="menu{$data['ugroup_id']}" src="{$this->settings['skin_acp_url']}/_newimages/menu_open.png" alt="">
					<ul style="position: absolute; display: none; z-index: 9999;" class="acp-menu" id='menu{$data['ugroup_id']}_menucontent'>
						<li class='icon edit' style="z-index: 10000;" class="acp-row-off"><a style="z-index: 10000;" href='{$this->settings['base_url']}{$this->form_code}&amp;do=groupEdit&amp;ugroup_id={$data['ugroup_id']}'>{$this->lang->words['t_ua_editg']}</a></li>
						<li class='icon delete' style="z-index: 10000;" class="acp-row-off"><a style="z-index: 10000;" href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=groupRemove&amp;ugroup_id={$data['ugroup_id']}");'>{$this->lang->words['t_ua_removeg']}</a></li>
					</ul>
				</div>
			</td>
		</tr>
EOF;
	}
}
else
{
 	$nonesetup = sprintf( $this->lang->words['t_ua_none'], $this->settings['base_url'] );
	$IPBHTML .= <<<EOF
		<tr>
			<td>{$nonesetup}</td>
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
 * List the user agents
 *
 * @access	public
 * @param	array 		User agents
 * @return	string		HTML
 */
public function uagents_listUagents( $userAgents ) {

$IPBHTML = "";
//--starthtml--//
$_json    = json_encode( array( 'uagents' => $userAgents ) );

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['t_uamanagement']}</h2>
	<ul class='context_menu'>
		<li>
			<a href='#' id='add_uagent' title='{$this->lang->words['t_uaaddnew']}'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/world_add.png' alt='' />
				{$this->lang->words['t_uaaddnew']}
			</a>
		</li>
	</ul>
</div>

EOF;


$IPBHTML .= <<<EOF
	<script tyle='text/javascript' src='{$this->settings['js_main_url']}acp.uagents.js'></script>
	<div class='acp-box'>
		<h3>{$this->lang->words['t_uamanagement']}</h3>
EOF;
		if( !count( $userAgents ) )
		{
			$none = sprintf( $this->lang->words['t_ua_nonein'], $this->settings['base_url'], $this->form_code );
			$IPBHTML .= <<<EOF
			<div class='no_items'>
				{$none}
			</div>
EOF;
		}
		else
		{
			//print_r( $userAgents );
			$IPBHTML .= <<<EOF
				<ul class='sortable_handle alternate_rows' id='sortable_handle'>
EOF;
			foreach( $userAgents as $agent )
			{
			$IPBHTML .= <<<EOF
				<li id='uagent_{$agent['uagent_id']}' class='isDraggable'>
					<table width='100%' cellpadding='0' cellspacing='0' border='0' class='double_pad'>
						<tr>
							<td style='width: 2%; text-align: center'>
								<div class='draghandle'><img src='{$this->settings['skin_acp_url']}/_newimages/drag_icon.png' alt='' /></div>
							</td>
							<td style='width: 2%; text-align: center'>
								<img src='{$this->settings['skin_acp_url']}/images/folder_components/uagents/type_{$agent['uagent_type']}.png' alt='Icon' />
							</td>
							<td style='width: 78%'>
								<strong>{$agent['uagent_name']}</strong>
							</td>
							<td style='width: 18%; text-align: right'>
								<span class='dropdown-button' title='{$this->lang->words['t_uaedit']}: {$agent['uagent_name']}' id='agent_{$agent['uagent_id']}_edit'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/pencil.png' alt='{$this->lang->words['sk_icon']}' /></span>
								<span class='dropdown-button' title='{$this->lang->words['t_uaremove']}: {$agent['uagent_name']}' id='agent_{$agent['uagent_id']}_delete'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/cross.png' alt='{$this->lang->words['sk_icon']}' /></span>
							</td>
						</tr>
					</table>
					<script type='text/javascript'>
						$('agent_{$agent['uagent_id']}_edit').observe('click', acp.uagents.editAgent.bindAsEventListener( this, {$agent['uagent_id']} ) );
						$('agent_{$agent['uagent_id']}_delete').observe('click', acp.uagents.deleteAgent.bindAsEventListener( this, {$agent['uagent_id']} ) );
					</script>
				</li>
EOF;
			}
			
			$IPBHTML .= <<<EOF
				</ul>
				<script type='text/javascript'>
					acp.uagents.updateURL = "{$this->settings['base_url']}&{$this->form_code_js}&do=reorder&md5check={$this->registry->adminFunctions->getSecurityKey()}";
					acp.uagents.json = {$_json};
					
					ipb.templates['add_uagent'] = new Template("<div class='acp-box'><h3>#{box_title}</h3><ul class='acp-form'><li><label for='uagent_name_#{id}'>{$this->lang->words['t_uaname']}</label><input type='text' class='input_text' id='uagent_name_#{id}' value='#{a_name}' /><br /><span class='desctext'>{$this->lang->words['t_uaname_desc']}</span></li><li><label for='uagent_key_#{id}'>{$this->lang->words['t_uakey']}</label><input type='text' class='input_text' id='uagent_key_#{id}' value='#{a_key}' /><br /><span class='desctext'>{$this->lang->words['t_uakey_desc']}</span></li><li><label for='uagent_type_#{id}'>{$this->lang->words['t_uatype']}</label><select id='uagent_type_#{id}'><option value='browser' #{type_browser}>{$this->lang->words['t_uabrowser']}</option><option value='search' #{type_search}>{$this->lang->words['t_uasearchengine']}</option><option value='other' #{type_other}>{$this->lang->words['t_uaother']}</option></select></li><li><label for='uagent_regex_#{id}'>{$this->lang->words['t_uaregex']}</label><textarea id='uagent_regex_#{id}' class='input_text' style='width: 40%' rows='5'>#{a_regex}</textarea></li><li><label for='uagent_capture_#{id}'>{$this->lang->words['t_uacapture']}</label><input type='text' class='input_text' id='uagent_capture_#{id}' value='#{a_capture}' /><br /><span class='desctext'>{$this->lang->words['t_uacapture_desc']}</span></li></ul><div class='acp-actionbar'><input type='hidden' id='uagent_position_#{id}' value='#{a_position}' /><input type='submit' class='realbutton' value='{$this->lang->words['t_uasave']}' id='uagent_#{id}_save' /></div></div>");
					
					ipb.templates['agent_row'] = new Template("<li id='uagent_#{id}' class='isDraggable'><table width='100%' cellpadding='0' cellspacing='0' border='0' class='double_pad'><tr><td style='width: 2%; text-align: center'><div class='draghandle'><img src='{$this->settings['skin_acp_url']}/_newimages/drag_icon.png' alt='' /></div></td><td style='width: 2%; text-align: center'><img src='{$this->settings['skin_acp_url']}/images/folder_components/uagents/type_#{type}.png' alt='Icon' /></td><td style='width: 78%'><strong>#{name}</strong></td><td style='width: 18%; text-align: right;'><span class='dropdown-button' title='{$this->lang->words['t_uaedit']}' id='agent_#{id}_edit'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/pencil.png' alt='{$this->lang->words['sk_icon']}' /></span> <span class='dropdown-button' title='{$this->lang->words['t_uaremove']}' id='agent_#{id}_delete'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/cross.png' alt='{$this->lang->words['sk_icon']}' /></span></td></tr></table></li>");
				</script>
EOF;
		}
		
		$IPBHTML .= <<<EOF
	</div>
EOF;

if ( IN_DEV )
{
	$IPBHTML .= <<<EOF
	<div>
		<a href="{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=rebuildMaster">{$this->lang->words['ua_rebuild_master']}</a>
	</div>
EOF;
}

//--endhtml--//
return $IPBHTML;
}


/**
 * Form to configure login method 
 *
 * @access	public
 * @param	array 		Login method
 * @param	array 		Form elements
 * @return	string		HTML
 */
public function login_conf_form( $login, $form ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<form id='mainform' action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=login_save_conf&amp;login_id={$login['login_id']}' method='post'>
<div class='acp-box'>
 <h3>{$this->lang->words['t_configdetails']} {$login['login_title']}</h3>
 <ul class='acp-form alternate_rows'>
EOF;

foreach( $form as $form_entry )
{
	$IPBHTML .= <<<EOF
 <li>
   <label>{$form_entry['title']}
EOF;

	if( $form_entry['description'] )
	{
		$IPBHTML .= "<span class='desctext'>{$form_entry['description']}</span>";
	}
	
$IPBHTML .= <<<EOF
</label>
   {$form_entry['control']}
</li>
EOF;
}

$IPBHTML .= <<<EOF
 </ul>
	<div class='acp-actionbar'>
 		<input type='submit' value=' Save ' class='button primary' /></div>
	</div>
</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Login methods overview page
 *
 * @access	public
 * @param	string		Login method rows
 * @return	string		HTML
 */
public function login_overview( $content ) {

$IPBHTML = "";
//--starthtml--//
$IPBHTML .= <<<EOF
<script type="text/javascript">
window.onload = function() {
	Sortable.create( 'sortable_handle', { only: 'isDraggable', revert: true, format: 'login_([0-9]+)', onUpdate: dropItLikeItsHot, handle: 'draghandle' } );
};

dropItLikeItsHot = function( draggableObject, mouseObject )
{
	var options = {
					method : 'post',
					parameters : Sortable.serialize( 'sortable_handle', { tag: 'li', name: 'logins' } )
				};
 
	new Ajax.Request( "{$this->settings['base_url']}&{$this->form_code_js}&do=login_reorder&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' ), options );

	return false;
};

</script>

<div class='section_title'>
	<h2>{$this->lang->words['l_title']}</h2>
	
	<ul class='context_menu'>
		<li><a href='{$this->settings['base_url']}&amp;module=tools&amp;section=login&amp;do=login_add' style='text-decoration:none'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/add.png' alt='' /> {$this->lang->words['tol_register_new_log_in_method']}</a></li>
	</ul>	
</div>

<div class='acp-box'>
    <h3>{$this->lang->words['tol_registered_log_in_authenticati']}</h3>
    <ul id='sortable_handle' class='alternate_rows'>
    	$content
    </ul>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Login method diagnostic results
 *
 * @access	public
 * @param	array 		Login method
 * @return	string		HTML
 */
public function login_diagnostics( $login=array() ) {

$IPBHTML = "";
//--starthtml--//
$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['tol_diagnostics_title']}</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['tol_diagnostics_for']}: {$login['login_title']}</h3>
	<table class='alternate_rows'>
  		<tr>
  			<th></th>
  			<th class='right-border'></th>
  			<th>{$this->lang->words['tol_file_name']}</th>
  			<th>{$this->lang->words['tol_exists']}</th>
  			<th>{$this->lang->words['tol_writeable']}</th>
		</tr>
		<tr>      
            <td><strong>{$this->lang->words['tol_log_in_enabled']}</strong></td>
            <td class='right-border'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/{$login['_enabled_img']}' border='0' alt='*' class='ipd' /></td>
            <td><strong>./sources/loginauth/{$login['login_folder_name']}/auth.php</strong></td>
            <td><img src='{$this->settings['skin_acp_url']}/_newimages/icons/{$login['_file_auth_exists']}' border='0' alt='*' class='ipd' /></td>
            <td>-</td>           
        </tr>
        <tr>
            <td><strong>{$this->lang->words['tol_log_in_installed']}</strong></td>
            <td class='right-border'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/{$login['_installed_img']}' border='0' alt='*' class='ipd' /></td>
            <td><strong>./sources/loginauth/{$login['login_folder_name']}/acp.php</strong></td>
            <td><img src='{$this->settings['skin_acp_url']}/_newimages/icons/{$login['_file_conf_exists']}' border='0' alt='*' class='ipd' /></td>
            <td>-</td>
        </tr>
        <tr>
            <td><strong>{$this->lang->words['tol_log_in_has_settings']}</strong></td>
            <td class='right-border'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/{$login['_has_settings']}' border='0' alt='*' class='ipd' /></td>
            <td><strong>./sources/loginauth/{$login['login_folder_name']}/conf.php</strong></td>
            <td><img src='{$this->settings['skin_acp_url']}/_newimages/icons/{$login['_file_conf_exists']}' border='0' alt='*' class='ipd' /></td>
            <td><img src='{$this->settings['skin_acp_url']}/_newimages/icons/{$login['_file_conf_write']}' border='0' alt='*' class='ipd' /></td>
        </tr>
    </table>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Form to add/edit a login method
 *
 * @access	public
 * @param	array 		Form elements
 * @param	string		Form title
 * @param	string		Action code
 * @param	string		Button text
 * @param	array 		Login method
 * @return	string		HTML
 */
public function login_form($form, $title, $formcode, $button, $login) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<form id='mainform' action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=$formcode&amp;login_id={$login['login_id']}' method='post'>
	<div class='acp-box'>
		<h3>$title</h3>
		
		<ul class='acp-form alternate_rows'>
			<li>
				<label>{$this->lang->words['tol_log_in_title']}</label>
				{$form['login_title']}
			</li>
			<li>
				<label>{$this->lang->words['tol_log_in_description']}<span class='desctext'>{$this->lang->words['tol_a_short_description_for_this_l']}</span></label>
				{$form['login_description']}
			</li>
			<li>
				<label>{$this->lang->words['tol_log_in_files_folder_name']}<span class='desctext'>{$this->lang->words['tol_the_main_folder_the_php_files_']}</span></label>
				{$form['login_folder_name']}
			</li>
			<li>
				<label>{$this->lang->words['tol_log_in_enabled']}<span class='desctext'>{$this->lang->words['tol_if_yes_this_log_in_will_be_ena']}</span></label>
				{$form['login_enabled']}
			</li>
			<li>
				<label>{$this->lang->words['t_l_type']}</label>
				{$form['login_user_id']}
			</li>
			<li>
				<label>{$this->lang->words['t_l_html']}<span class='desctext'>{$this->lang->words['t_l_html_info']}</span></label>
				{$form['login_alt_login_html']}
			</li>
			<li>
				<label>{$this->lang->words['t_l_html2']}<span class='desctext'>{$this->lang->words['t_l_html2_info']}</span></label>
				{$form['login_alt_acp_html']}
			</li>
			<li>
				<label>{$this->lang->words['t_l_html3']}<span class='desctext'>{$this->lang->words['t_l_html3_info']}</span></label>
				{$form['login_replace_form']}
			</li>
			<li>
				<label>{$this->lang->words['tol_log_in_user_maintenance_url']}<span class='desctext'>{$this->lang->words['tol_the_url_for_the_place_they_can']}</span></label>
				{$form['login_maintain_url']}
			</li>
			<li>
				<label>{$this->lang->words['tol_log_in_user_register_url']}<span class='desctext'>{$this->lang->words['tol_the_url_for_the_place_to_regis']}</span></label>
				{$form['login_register_url']}
			</li>
			<li>
				<label>{$this->lang->words['tol_log_in_user_log_in_url']}<span class='desctext'>{$this->lang->words['tol_the_url_for_the_place_to_log_i']}</span></label>
				{$form['login_login_url']} <div class='desctext'></div>
			</li>
			<li>
				<label>{$this->lang->words['tol_log_in_user_log_out_url']}<span class='desctext'>{$this->lang->words['tol_the_url_for_the_place_to_log_o']}</span></label>
				{$form['login_logout_url']} <div class='desctext'></div>
			</li>
EOF;
//startif
if ( $form['login_safemode'] != '' )
{		
$IPBHTML .= <<<EOF
			<li>
				<label>{$this->lang->words['tol_enable_safemode']}<span class='desctext'>{$this->lang->words['tol_cannot_be_deleted_or_edited_by']}</span></label>
				{$form['login_safemode']}
			</li>
EOF;
}//endif
$IPBHTML .= <<<EOF
		</ul>
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

/**
 * Sub header for login methods
 *
 * @access	public
 * @param	string		Subheader label
 * @return	string		HTML
 */
public function login_subheader( $label ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<li class='notDraggable'>
	<table width='100%' cellpadding='0' cellspacing='0'>
		<th style='width: 83%'>
			{$label}
		</th>
		<th style='width: 17%'>
			{$this->lang->words['login_manage_enabled']}
		</th>
	</table>
</li>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * No login methods of this type row
 *
 * @access	public
 * @param	string		Row label
 * @return	string		HTML
 */
public function login_norow( $label ) {

$IPBHTML = "";
//--starthtml--//
$nomethods = sprintf( $this->lang->words['t_l_nomethods'], $label ); 
$IPBHTML .= <<<EOF
<li class='notDraggable'>
	<table width='100%' cellpadding='0' cellspacing='0'>
		<tr>
			<td>{$nomethods}</td>
		</tr>
	</table>
</li>
EOF;

//--endhtml--//
return $IPBHTML;
}
	
/**
 * Login method row
 *
 * @access	public
 * @param	array 		Login method data
 * @return	string		HTML
 */
public function login_row( $data ) {

$IPBHTML = "";
//--starthtml--//

$className	= '';

if( $data['login_installed'] )
{
	$className = "class='isDraggable'";
}

$IPBHTML .= <<<EOF
<li id='login_{$data['login_id']}' {$className}>
	<table width='100%' cellspacing='0' class='double_pad' cellpadding='0' border='0'>
		<tr>
			<td style='width: 3%'>
EOF;
		if( $data['login_installed'] )
		{
			$IPBHTML .= <<<EOF
			 		<div class='draghandle'><img src='{$this->settings['skin_acp_url']}/_newimages/drag_icon.png' alt='' /></div>
EOF;
		}
		else
		{
			$IPBHTML .= <<<EOF
					<img src='{$this->settings['skin_acp_url']}/_newimages/icons/lock.png' alt='--' class='ipd' />
EOF;
		}
		
		$IPBHTML .= <<<EOF
			</td>
			<td style='width: 80%'>
				<strong>{$data['login_title']}</strong>
EOF;
			if( $data['login_description'] )
			{
				$IPBHTML .= <<<EOF
					<br /><span class='desctext'>{$data['login_description']}</span>
EOF;
			}
		
		$IPBHTML .= <<<EOF
			</td>
			<td style='width: 14%'>
EOF;
			if( $data['login_installed'] )
			{
				$toggle_text = $data['login_enabled'] ? $this->lang->words['t_l_disable'] : $this->lang->words['t_l_enable'];

				$IPBHTML .= "<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=login_toggle&amp;login_id={$data['login_id']}' title='{$toggle_text}'>";
			}

			$IPBHTML .= "<img src='{$this->settings['skin_acp_url']}/_newimages/icons/{$data['_enabled_img']}' border='0' alt='YN' class='ipd' />";

			if( $data['login_installed'] )
			{
				$IPBHTML .= "</a>";
			}
			
			$IPBHTML .= <<<EOF
			</td>
			<td style='width: 3%'>
							<img class="ipbmenu" id="menu{$data['login_id']}" src="{$this->settings['skin_acp_url']}/_newimages/menu_open.png" alt="">	 		
							<ul style="position: absolute; display: none; z-index: 9999;" class="acp-menu" id="menu{$data['login_id']}_menucontent">
EOF;

				//startif
				if ( $data['login_installed'] )
				{		
				$IPBHTML .= <<<EOF
								<li style="z-index: 10000;"class='icon edit'><a style="z-index: 10000;" href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=login_edit_details&amp;login_id={$data['login_id']}'>{$this->lang->words['tol_edit_details']}</a></li>
EOF;

					if( $data['acp_plugin'] )
					{
						$IPBHTML .= <<<EOF
								<li style="z-index: 10000;"class='icon edit'><a style="z-index: 10000;" href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=login_acp_conf&amp;login_id={$data['login_id']}'>{$this->lang->words['t_l_configdetails']}</a></li>
EOF;
					}

				$IPBHTML .= <<<EOF
								<li style="z-index: 10000;"class='icon manage'><a style="z-index: 10000;" href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=login_export&amp;login_id={$data['login_id']}'>{$this->lang->words['t_l_export']}</a></li>
								<li style="z-index: 10000;"class='icon manage'><a style="z-index: 10000;" href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=login_uninstall&amp;login_id={$data['login_id']}'>{$this->lang->words['t_l_uninstall']}</a></li>
								<li style="z-index: 10000;"class='icon view'><a style="z-index: 10000;" href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=login_diagnostics&amp;login_id={$data['login_id']}'>{$this->lang->words['tol_diagnostics']}</a></li>
EOF;
				}//endif
				//startif
				if ( $data['login_installed'] != 1 )
				{		
				$IPBHTML .= <<<EOF
								<li style="z-index: 10000;" class='icon manage'><a style="z-index: 10000;" href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=login_install&amp;login_folder={$data['login_folder_name']}'>{$this->lang->words['tol_install']}</a></li>
EOF;
				}//endif
				$IPBHTML .= <<<EOF
							</ul>
			</td>
		</tr>
	</table>
</li>
EOF;

//--endhtml--//
return $IPBHTML;
}


/**
 * Cache manager popup screen
 *
 * @access	public
 * @param	string		Title
 * @param	string		Cache content
 * @return	string		HTML
 */
public function cache_pop_up($title, $content) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div style='padding:4px'>
<h2>$title</h2>
<div class='acp-row-off' style='padding: 10px'>
	<pre>$content</pre>
</div>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Wrapper for a cache entry
 *
 * @access	public
 * @param	array 		Caches
 * @param	int			Total caches
 * @return	string		HTML
 */
public function cache_entry_wrapper( $caches, $total, $cacheContent=array() ) {

$IPBHTML = "";
//--starthtml--//

$_applications = array_merge( array( 'global' => array( 'app_title' => $this->lang->words['tol_global_caches'] ) ), ipsRegistry::$applications );
$__default_tab = 'global';

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['tol_cache_management']}</h2>
	<ul class='context_menu'>
		<li>
			<a href='{$this->settings['base_url']}module=tools&amp;section=cache&amp;do=cache_recache&amp;id=__all__&amp;__notabsave=1'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/database_refresh.png' alt='' />
				{$this->lang->words['tol_recache_all']}
			</a>
		</li>
	</ul>
</div>
EOF;

/* CONTENT CACHE */
if ( count( $cacheContent ) )
{
	$this->lang->words['cc_remove_x_posts'] = sprintf( $this->lang->words['cc_remove_seven'], $this->settings['cc_posts'] );
	$this->lang->words['cc_remove_y_posts'] = sprintf( $this->lang->words['cc_remove_seven'], $this->settings['cc_sigs'] );
	
	$IPBHTML .= <<<EOF
	<div class='acp-box alternate_rows'>
	<table class='double_pad alternate_rows'>
	<tr>
		<th colspan='4'>{$this->lang->words['cc_header']}</th>
	</tr>
	<tr>
		<td width='20%'>
			<strong>{$this->lang->words['cc_posts']}</strong>
		</td>
		<td width='35%'>
			<strong>{$cacheContent['cachedPosts']['count']}</strong>{$this->lang->words['cache__of']}<strong>{$cacheContent['posts']['count']}</strong>{$this->lang->words['cache__posts']}({$cacheContent['postPercent']}%)
		</td>
		<td width='40%'>
		  	{$cacheContent['m_posts']}
		</td>
		<td style='text-align: center'>
			<img class='ipbmenu' id="menu-cc_posts" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt='{$this->lang->words['frm_options']}' />
			<ul class='acp-menu' id='menu-cc_posts_menucontent'>
				<li class='icon delete'><a href='{$this->settings['base_url']}{$this->form_code}do=contentCache&amp;type=post&amp;method=seven'>{$this->lang->words['cc_remove_x_posts']}</a></li>
				<li class='icon delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}do=contentCache&amp;type=post&amp;method=all")'>{$this->lang->words['cc_remove_all']}...</a></li>
			</ul>
		</td>
	</tr>
	<tr>
		<td width='20%'>
			<strong>{$this->lang->words['cc_sigs']}</strong>
		</td>
		<td>
			<strong>{$cacheContent['cachedSigs']['count']}</strong> of <strong>{$cacheContent['members']['count']}</strong> signatures ({$cacheContent['sigPercent']}%)
		</td>
		<td width='40%'>
		  	{$cacheContent['m_sigs']}
		</td>
		<td style='text-align: center'>
			<img class='ipbmenu' id="menu-cc_sigs" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt='{$this->lang->words['frm_options']}' />
			<ul class='acp-menu' id='menu-cc_sigs_menucontent'>
				<li class='icon delete'><a href='{$this->settings['base_url']}{$this->form_code}do=contentCache&amp;type=sig&amp;method=seven'>{$this->lang->words['cc_remove_y_posts']}</a></li>
				<li class='icon delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}do=contentCache&amp;type=sig&amp;method=all")'>{$this->lang->words['cc_remove_all']}...</a></li>
			</ul>
		</td>
	</tr>
	</table>
	</div>
	<br />
EOF;
}

$IPBHTML .= <<<EOF
	<ul id='tabstrip_settings' class='tab_bar no_title'>
EOF;

foreach( $_applications as $app_dir => $app_data )
{
	if ( ipsRegistry::$request['cacheapp'] AND $app_dir == ipsRegistry::$request['cacheapp'] )
	{
		$_default_tab = $app_dir;
	}
	
	if ( is_array( $caches[ $app_dir ] ) and count( $caches[ $app_dir ] ) )
	{
$IPBHTML .= <<<EOF
	<li id='tabtab-{$app_dir}' class=''>{$app_data['app_title']}</li>
	
EOF;
	}
}

$IPBHTML .= <<<EOF
</ul>

<script type="text/javascript">
//<![CDATA[
document.observe("dom:loaded",function() 
{
ipbAcpTabStrips.register('tabstrip_settings');
ipbAcpTabStrips.doToggle($('tabtab-{$_default_tab}'));
});
 //]]>
</script>

<div class='acp-box alternate_rows'>

EOF;

foreach( $_applications as $app_dir => $app_data )
{
	if ( is_array( $caches[ $app_dir ] ) and count( $caches[ $app_dir ] ) )
	{
$IPBHTML .= <<<EOF
	<div id='tabpane-{$app_dir}'>
		<table class='double_pad alternate_rows'>
		<tr>
			<th width='1px'></th>
			<th>Cache</th>
			<th style='width: 60px'>{$this->lang->words['tol_size']}</th>
			<th style='width: 80px'>{$this->lang->words['tol_init_state']}</th>
			<th width='1px'>{$this->lang->words['tol_options']}</th>
		</tr>
			
EOF;
		foreach( $caches[ $app_dir ] as $data )
		{
$IPBHTML .= <<<EOF
<tr>
	<td><img src='{$this->settings['skin_acp_url']}/images/folder_components/cache/cache-row.png' class='ipd' /></td>
	<td><strong>{$data['cache_name']}</strong></td>
	<td>{$data['_cache_size']}</td>
	<td>
EOF;
if ( $data['_cs_init_load'] AND $data['allow_unload'] )
{
$IPBHTML .= <<<EOF
<img src='{$this->settings['skin_acp_url']}/images/folder_components/cache/cache-loadtime.png' title='{$this->lang->words['tol_loaded_on_initialization']}' class='ipd' />
EOF;
}
else if ( $data['_cs_init_load'] AND ! $data['allow_unload'] )
{
$IPBHTML .= <<<EOF
<img src='{$this->settings['skin_acp_url']}/images/folder_components/cache/cache-loadtime-set.png' title='{$this->lang->words['tol_loaded_on_initialization_canno']}' class='ipd' />
EOF;
}
else
{
$IPBHTML .= <<<EOF
<img src='{$this->settings['skin_acp_url']}/images/folder_components/cache/cache-loadtime-none.gif' title='{$this->lang->words['tol_not_loaded_on_initialization']}' class='ipd' />
EOF;
}
$IPBHTML .= <<<EOF

	 </td>
	<td style='text-align: center'>
		<img class='ipbmenu' id="menu-{$data['cache_name']}" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt='{$this->lang->words['frm_options']}' />
		<ul class='acp-menu' id='menu-{$data['cache_name']}_menucontent'>
			<li class='icon edit'><a href='{$this->settings['base_url']}{$this->form_code}do=cache_recache&amp;id={$data['cache_name']}&amp;cacheapp={$app_dir}'>{$this->lang->words['tol_recache_cache']}</a></li>
			<li class='icon view'><a href='#' onclick='return acp.openWindow("{$this->settings['base_url']}{$this->form_code}do=cache_view&amp;id={$data['cache_name']}&amp;cache_app={$app_dir}", 400, 600)'>{$this->lang->words['tol_view_cache']}.</a></li>
		</ul>
	</td>
 </tr>
 

EOF;
		}
$IPBHTML .= <<<EOF
		</table>
	</div>
	
EOF;
	}
}

$IPBHTML .= <<<EOF
	<div class='acp-actionbar'>
	{$this->lang->words['tol_total_cache_size']}: $total
	</div>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Setting titles wrapper
 *
 * @access	public
 * @param	string		Title
 * @param	array 		Setting groups
 * @param	string		Application tab to start on
 * @return	string		HTML
 */
public function settings_titles_wrapper($title, $settings, $start_app='') {

$IPBHTML = "";
//--starthtml--//

$_default_tab = ( isset( $this->request['_dtab'] ) && $this->request['_dtab'] ) ? $this->request['_dtab'] : 'System';

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['tol_settings']}</h2>
	<ul class='context_menu'>
		<li>
			<a href='{$this->settings['base_url']}module=tools&amp;section=settings&amp;do=settinggroup_new' style='text-decoration:none'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/table_add.png' alt='' /> {$this->lang->words['tol_add_new_setting_group']}</a>
		</li>
	</ul>
</div>
<br />
<ul id='tabstrip_settings' class='tab_bar no_title'>
EOF;

foreach( $settings as $tab => $group )
{
	if ( ipsRegistry::$request['app'] AND $tab == ipsRegistry::$request['app'] )
	{
		$_default_tab = $tab;
	}
	
	$_tab	= IPSText::md5Clean( $tab );
	
$IPBHTML .= <<<EOF
	<li id='tabtab-{$_tab}'>{$tab}</li>
	
EOF;
}

$IPBHTML .= <<<EOF
</ul>

<script type="text/javascript">
//<![CDATA[
document.observe("dom:loaded",function() 
{
ipbAcpTabStrips.register('tabstrip_settings');
ipbAcpTabStrips.doToggle($('tabtab-{$_default_tab}'));
});
 //]]>
</script>

<div class='acp-box'>

EOF;


foreach( $settings as $tab => $app_data )
{
	$_tab	= IPSText::md5Clean( $tab );
$IPBHTML .= <<<EOF
	<div id='tabpane-{$_tab}'>
		<table width='100%' class='alternate_rows double_pad' cellpadding='0' cellspacing='0' border='0'>
		
EOF;
		foreach( $app_data as $r )
		{
			
			if(IN_DEV)
			{
				$export_settings_group = "<li><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=MOD_export_setting&amp;conf_group={$r['conf_title_id']}' title='{$this->lang->words['export_group']}'><img src='{$this->settings['skin_acp_url']}/images/options_menu/export_settings_group.png' alt='Icon' /> {$this->lang->words['export_group']}</a>
				</li>";
			}
			
			$img = file_exists( IPSLib::getAppDir( $r['conf_title_app'] ) . '/skin_cp/appIcon.png' ) ? $this->settings['base_acp_url'] . '/' . IPSLib::getAppFolder( $r['conf_title_app'] ) . '/' . $r['conf_title_app'] . '/skin_cp/appIcon.png' : "{$this->settings['skin_acp_url']}/_newimages/applications/{$r['conf_title_app']}.png";
			
$IPBHTML .= <<<EOF
		<tr>
		 	<td width='3%' style='text-align: center'><img src='{$img}' alt='{$this->lang->words['tol_folder']}' /></td>
		 	<td width='90%'>
				<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=setting_view&amp;conf_group={$r['conf_title_id']}'><b>{$r['conf_title_title']}</b></a>
				<span style='color:gray'>({$r['conf_title_count']} settings)</span><br />
				<span class='desctext'>{$r['conf_title_desc']}</span>
			</td>
			<td style='width: 3%'>
				<img class='ipbmenu' id="menu{$r['conf_title_id']}" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt='{$this->lang->words['frm_options']}' />
				<ul class='acp-menu' id='menu{$r['conf_title_id']}_menucontent'>
					<li><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=setting_view&amp;conf_group={$r['conf_title_id']}' title='{$this->lang->words['tol_manage_settings']}'><img src='{$this->settings['skin_acp_url']}/images/options_menu/manage_settings.png' alt='Icon' /> {$this->lang->words['tol_manage_settings']}</a></li>

					<li><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=settinggroup_showedit&amp;id={$r['conf_title_id']}' title='{$this->lang->words['tol_edit_settings_group']}'><img src='{$this->settings['skin_acp_url']}/images/options_menu/edit_settings_group.png' alt='Icon' /> {$this->lang->words['tol_edit_settings_group']}</a></li>

					<li><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=settinggroup_delete&amp;id={$r['conf_title_id']}' title='{$this->lang->words['tol_delete_settings_group']}'><img src='{$this->settings['skin_acp_url']}/images/options_menu/delete_settings_group.png' alt='Icon' /> {$this->lang->words['tol_delete_settings_group']}</a></li>

					<li><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=settinggroup_resync&amp;id={$r['conf_title_id']}' title='{$this->lang->words['tol_recount_settings_group']}'><img src='{$this->settings['skin_acp_url']}/images/options_menu/rebuild_settings_group.png' alt='Icon' /> {$this->lang->words['tol_recount_settings_group']}</a></li>

					{$export_settings_group}
				</ul>
			</td>
		</tr>
EOF;
		}
$IPBHTML .= <<<EOF
		</table>
	</div>
	
EOF;

}
	
$IPBHTML .= <<<EOF
</div>

<br />

<form action='{$this->settings['base_url']}&{$this->form_code}&do=settings_do_import' enctype='multipart/form-data' method='post'>
	<div class='acp-box'>
		<h3>{$this->lang->words['tol_import_xml_settings']}</h3>
		<ul class='acp-form alternate_rows'>
			<li>
				<label>{$this->lang->words['tol_upload_xml_settings_file_from_']}<span class='desctext'>{$this->lang->words['tol_duplicate_entries_will_not_be_']}</span></label>
				<input class='textinput' type='file' size='30' name='FILE_UPLOAD' />
			</li>
			<li>
				<label>{$this->lang->words['tol_or_enter_the_filename_of_the_x']}<span class='desctext'>{$this->lang->words['tol_the_file_must_be_uploaded_into']}</span></label>
				<td class='tablerow2'><input class='textinput' type='text' size='30' name='file_location' /></td>
			</li>
		</ul>
		<div class='acp-actionbar'>
			<div class='centeraction'>
				<input type='submit' class='button primary' value='{$this->lang->words['t_import']}' />
			</div>
		</div>
	</div>
</form>

EOF;
//startif
if ( IN_DEV != 0 )
{		
$IPBHTML .= <<<EOF
<br />
<div align='center'>
 <ul>
	<li><a href='{$this->settings['base_url']}&{$this->form_code}&do=settingsImportApps'>Import all APP XML Settings</a></li>
	<li><a href='{$this->settings['base_url']}&{$this->form_code}&do=settingsExportApps'>Export all APP XML Settings</a></li>
 </ul>
EOF;
}//endif
$IPBHTML .= <<<EOF

EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Search box at the top of the settings
 *
 * @access	public
 * @return	string		HTML
 * @deprecated	Livesearch handles this now
 */
public function settings_titles_top_searchbox() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div style="float:right; margin: 3px 5px 0 0">
<form method='post' action='{$this->settings['base_url']}{$this->form_code}&do=setting_view'><input type='text' size='25' onclick='this.value=""' value='{$this->lang->words['tol_search_settings']}' name='search' class='realbutton' />&nbsp;<input type='submit' class='realbutton' value='{$this->lang->words['tol_go']}' /></form>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}


/**
 * Form to add/edit a setting group
 *
 * @access	public
 * @param	array 		Form elements
 * @param	string		Form title
 * @param	string		Action code
 * @param	string		Button text
 * @return	string		HTML
 */
public function settings_title_form($form, $title, $formcode, $button) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['s_title']}</h2>
</div>

<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=$formcode&amp;id={$this->request['id']}' method='post'>
	<div class='acp-box'>
		<h3>$title</h3>
		<ul class='acp-form alternate_rows'>
			<li>
				<label>{$this->lang->words['tol_setting_group_title']}</label>
				{$form['conf_title_title']}
			</li>
			<li>
				<label>{$this->lang->words['tol_setting_group_application']}</label>
				{$form['conf_title_app']}
			</li>
			<li>
				<label>{$this->lang->words['tol_setting_group_tab']}</label>
				{$form['conf_title_tab']}
			</li>
			<li>
				<label>{$this->lang->words['tol_setting_group_description']}</label>
				{$form['conf_title_desc']}
			</li>
EOF;
//startif
if ( $form['conf_title_keyword'] != '' )
{		
$IPBHTML .= <<<EOF
			<li>
				<label>{$this->lang->words['tol_setting_group_keyword']}<span class='desctext'>{$this->lang->words['tol_used_to_pull_this_from_the_db_']}</span></label>
				{$form['conf_title_keyword']}
			</li>
			<li>
				<label>{$this->lang->words['tol_hide_from_main_settings_list']}</label>
				{$form['conf_title_noshow']}
			</li>
EOF;
}//endif
$IPBHTML .= <<<EOF
		</ul>
		<div class='acp-actionbar'>
			<div class='centeraction'>
				<input type='submit' class='button primary' value='$button' />
			</div>
		</div>
	</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * View settings wrapper
 *
 * @access	public
 * @param	string		Page title
 * @param	string		Content
 * @param	string		Search button
 * @param	string		Bounceback URL
 * @return	string		HTML
 */
public function settings_view_wrapper($title, $content, $searchbutton, $bounceback='' ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['settings_h_prefix']} $title</h2>
EOF;

	if ( ipsRegistry::$request['search'] == '' AND $bounceback == '' )
	{
		if( $searchbutton )
		{		
	$IPBHTML .= <<<EOF
		<ul class='context_menu'>
			<li>
				<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=settingnew&amp;conf_group={$this->request['conf_group']}' title='{$this->lang->words['add_setting_button']}'>
					<img src='{$this->settings['skin_acp_url']}/_newimages/icons/add.png' alt='' />
					{$this->lang->words['add_setting_button']}
				</a>
			</li>
		</ul>
EOF;
		}
	}//endif
	
$IPBHTML .= <<<EOF
</div>
<br />
<form action='{$this->settings['_base_url']}app=core&amp;module=tools&amp;section=settings&amp;do=setting_update&amp;id={$this->request['conf_group']}&amp;search={$this->request['search']}' method='post'>
	<!--HIDDEN.FIELDS-->
	<input type='hidden' name='bounceback' value='$bounceback' />
	<div class='acp-box'>
		<h3>{$title}</h3>
		<ul class='alternate_rows' id='sortable_handle'>
			{$content}
		</ul>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['tol_update_settings']}' class='realbutton' />
		</div>
	</div>
</form>
<script type="text/javascript">

dropItLikeItsHot = function( draggableObject, mouseObject )
{
	var options = {
					method : 'post',
					parameters : Sortable.serialize( 'sortable_handle', { tag: 'li', name: 'settings' } )
				};
 
	new Ajax.Request( "{$this->settings['base_url']}&{$this->form_code_js}&do=reorder&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' ), options );

	return false;
};

Sortable.create( 'sortable_handle', { tag: 'li', only: 'isDraggable', revert: true, format: 'setting_([0-9]+)', onUpdate: dropItLikeItsHot, handle: 'draghandle' } );

</script>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Setting that starts a setting group
 *
 * @access	public
 * @param	array 		Setting
 * @return	string		HTML
 */
public function settings_row_start_group($r) {

$IPBHTML = "";
//--starthtml--//

if( $this->settings['acp_tutorial_mode'] )
{
	$settingGroup			= preg_replace( "/[^a-zA-Z0-9&;]/", '', $r['conf_start_group'] );
	$r['conf_start_group']	= "<span id='{$settingGroup}_title'>{$r['conf_start_group']}</span> <a href='#' id='{$settingGroup}' class='acp-help-settings'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/help.png' alt='Icon' style='vertical-align: middle' /></a>";
}

$IPBHTML .= <<<EOF
<li class='isDraggable' id='setting_{$r['conf_id']}'>
	<table width='100%' cellpadding='0' cellspacing='0'>
		<tr>
			<th>{$r['conf_start_group']}</th>
		</tr>
	</table>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * End a group of settings
 *
 * @access	public
 * @return	string		HTML
 */
public function settings_row_end_group() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * End a normal setting row
 *
 * @access	public
 * @return	string		HTML
 */
public function settings_row_end_normal() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF

EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Start a normal setting row
 *
 * @access	public
 * @return	string		HTML
 */
public function settings_row_start_normal() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF

EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * View a setting row
 *
 * @access	public
 * @param	array 		Setting
 * @param	string		Edit button
 * @param	string		Delete button
 * @param	string		Form element
 * @param	string		Revert button
 * @return	string		HTML
 */
public function settings_view_row($r, $edit, $delete, $form_element, $revert_button, $elem_type) {

$IPBHTML = "";
//--starthtml--//

if( !$r['conf_start_group'] )
{
	$IPBHTML .= <<<EOF
	<li class='isDraggable' id='setting_{$r['conf_id']}'>
EOF;
}
else
{
	$IPBHTML .= <<<EOF
	<!--<div class='{$tdrow1}'>-->
EOF;
}

$IPBHTML .= <<<EOF
	<table cellpadding='0' class='double_pad' cellspacing='0' border='0' width='100%'>
  		<tr>
  			<td style='width: 3%'><div class='draghandle'><img src='{$this->settings['skin_acp_url']}/_newimages/drag_icon.png' alt='Drag' /></div></td>
  			<td style='width: 50%; padding-right: 35px;'><b>{$r['conf_title']}</b><div class='desctext'>{$r['conf_description']}{$r['help_key']}</div></td>
EOF;
			if( $elem_type != 'rte' )
			{
				$IPBHTML .= <<<EOF
				<td style='width: 40%'>
					{$form_element}
				</td>
EOF;
			}
			else
			{
				$IPBHTML .= <<<EOF
				<td style='width: 40%'>
					&nbsp;
				</td>
EOF;
			}
			
			$IPBHTML .= <<<EOF
				<td style='width: 7%; text-align: right' nowrap="true">
EOF;
				//startif
				if ( $edit or $delete or $revert_button )
				{		
				$IPBHTML .= <<<EOF

								{$revert_button}
				   				{$edit}
								{$delete}
EOF;
				}//endif
$IPBHTML .= <<<EOF
			</td>
		</tr>
EOF;
		if( $elem_type == 'rte' )
		{
			$IPBHTML .= <<<EOF
				<tr>
					<td colspan='4'>
						{$form_element}
					</td>
				</tr>
EOF;
		}
		
		$IPBHTML .= <<<EOF
	</table>
</li>
EOF;

/*if( $r['conf_start_group'] )
{
	$IPBHTML .= <<<EOF
	</div>
EOF;
}*/


//--endhtml--//
return $IPBHTML;
}

/**
 * Form to export single settings
 *
 * @access	public
 * @param	array 		Settings
 * @return	string		HTML
 */
public function settings_exportsingle( $settings ) {

$per_row  = 3;
$td_width = 100 / $per_row;
$count    = 0;
		
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['export_helptitle']}</h2>
</div>

<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=setting_someexport_complete' method='post'>
<div class='tableborder'>
 <div class='tableheaderalt'>{$this->lang->words['choose_export']}</div>
 <table width='100%' cellspacing='1' cellpadding='4' border='0'>
 	<tr align='center'>
EOF;

foreach( $settings as $r )
{
	$count++;
	$class = $count == 2 ? 'tablerow2' : 'tablerow1';
	
	$IPBHTML .= <<<EOF
		<td width='{$td_width}%' align='left' class='$class'>
			<input type='checkbox' style='checkbox' value='1' name='id_{$r['conf_id']}' /> <strong>{$r['conf_key']}</strong> - {$r['conf_id']}
		</td>
EOF;

	if ($count == $per_row )
	{
		$IPBHTML .= "</tr>\n\n<tr align='center'>";
		$count   = 0;
	}
}

if ( $count > 0 and $count != $per_row )
{
	for ($i = $count ; $i < $per_row ; ++$i)
	{
		$IPBHTML .= "<td class='tablerow2'>&nbsp;</td>\n";
	}

	$IPBHTML .= "</tr>";
}

$IPBHTML .= <<<EOF
	</table>
	<div class='tablesubheader' align='center'>
		<input type='submit' class='realbutton' value='{$this->lang->words['export_selected']}' />
	</div>
</div>
</form>
EOF;

return $IPBHTML;
}

/**
 * Form to add/edit a setting
 *
 * @access	public
 * @param	array 		Setting
 * @param	string		Form title
 * @param	string		Action code
 * @param	string		Button text
 * @return	string		HTML
 */
public function settings_form($form, $title, $formcode, $button) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['add_setting_button']}</h2>
</div>

<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=$formcode&amp;id={$this->request['id']}' method='post'>
	<div class='acp-box'>
		<h3>$title</h3>
		
		<ul class='acp-form alternate_rows'>
			<li>
				<label>{$this->lang->words['tol_setting_title']}</label>
				{$form['conf_title']}
			</li>
			<li>
				<label>{$this->lang->words['tol_setting_position']}</label>
				{$form['conf_position']}
			</li>
			<li>
				<label>{$this->lang->words['tol_setting_description']}</label>
				{$form['conf_description']}
			</li>
			<li>
				<label>{$this->lang->words['tol_setting_group']}</label>
				{$form['conf_group']}
			</li>
			<li>
				<label>{$this->lang->words['tol_setting_type']}</label>
				{$form['conf_type']}
			</li>
			<li>
				<label>{$this->lang->words['tol_setting_key']}</label>
				{$form['conf_key']}
			</li>
			<li>
				<label>{$this->lang->words['tol_setting_current_value']}</label>
				{$form['conf_value']}
			</li>
			<li>
				<label>{$this->lang->words['tol_setting_default_value']}</label>
				{$form['conf_default']}
			</li>
			<li>
				<label>{$this->lang->words['tol_setting_extra']}<span class='desctext'>{$this->lang->words['tol_use_for_creating_form_element_']}</span></label>
				{$form['conf_extra']}
			</li>
			<li>
				<label>{$this->lang->words['tol_raw_php_code_to_eval_before_sh']}<span class='desctext'>{$this->lang->words['tol_036show_1_is_set_when_showing_']}</span></label>
				{$form['conf_evalphp']}
			</li>
			<li>
				<label>{$this->lang->words['setting_keywords']}<span class='desctext'>{$this->lang->words['setting_keywords_desc']}</span></label>
				{$form['conf_keywords']}
			</li>
			<li>
				<label>{$this->lang->words['tol_start_setting_group']}<span class='desctext'>{$this->lang->words['tol_enter_title_here_or_leave_blan']}</span></label>
				{$form['conf_start_group']}
			</li>
			<li>
				<label>{$this->lang->words['tol_end_setting_group']}<span class='desctext'>{$this->lang->words['tol_end_an_opened_setting_group']}</span></label>
				{$form['conf_end_group']}
			</li>
EOF;
//startif
if ( $form['conf_protected'] != '' )
{		
$IPBHTML .= <<<EOF
			<li>
				<label>{$this->lang->words['tol_make_a_default_setting_cannot_']}</label>
				{$form['conf_protected']}
			</li>
EOF;
}//endif
$IPBHTML .= <<<EOF
			<li>
				<label>{$this->lang->words['tol_add_this_option_into_the_setti']}</label>
				{$form['conf_add_cache']}
			</li>
		</ul>
		<div class='acp-actionbar'>
			<div class='centeraction'>
				<input type='submit' class='button primary' value='$button' />
			</div>
		</div>
	</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}


}