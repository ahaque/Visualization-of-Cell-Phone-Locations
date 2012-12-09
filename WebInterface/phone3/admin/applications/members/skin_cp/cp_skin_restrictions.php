<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * ACP restrictions skin file
 * Last Updated: $Date: 2009-03-27 11:41:38 -0400 (Fri, 27 Mar 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Members
 * @link		http://www.
 * @since		20th February 2002
 * @version		$Rev: 4333 $
 *
 */
 
class cp_skin_restrictions extends output
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
 * Form to add a new restricted member
 *
 * @access	public
 * @return	string		HTML
 */
public function restrictionsMemberForm() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['r_title']}</h2>
</div>

<form id='postingform' action="{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=acpperms-member-add-complete" method="post" name="REPLIER">
<input type='hidden' name='_admin_auth_key' value='{$this->registry->getClass('adminFunctions')->_admin_auth_key}' />
<div class='tableborder'>
 <div class='tableheaderalt'>{$this->lang->words['r_findadmin']}</div>
 <div class='tablesubheader'>&nbsp;</div>
  <table width='100%' cellspacing='0' cellpadding='5' align='center' border='0'>
  <tr>
    <td class='tablerow1'  width='50%'  valign='middle'>{$this->lang->words['r_displayname']}<div style='color:gray'>{$this->lang->words['r_displayname_info']}</div></td>
    <td class='tablerow2'  width='50%'  valign='middle'><input type="text" id='entered_name' name="entered_name" size="30" autocomplete='off' style='width:210px' value="" tabindex="1" /></td>
  </tr>
  <tr>
  <td align='center' class='tablesubheader' colspan='2' ><input type='submit' value='{$this->lang->words['r_proceed']}' class='realbutton' accesskey='s'></td>
  </tr>
  </table>
</div>
<script type="text/javascript">
document.observe("dom:loaded", function(){
	var search = new ipb.Autocomplete( $('entered_name'), { multibox: false, url: acp.autocompleteUrl, templates: { wrap: acp.autocompleteWrap, item: acp.autocompleteItem } } );
});
</script>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Form to add a new restricted group
 *
 * @access	public
 * @return	string		HTML
 */
public function restrictionsGroupForm() {

$IPBHTML = "";
//--starthtml--//

$all_groups 		= array();

foreach( $this->cache->getCache('group_cache') as $group_data )
{
	if( $group_data['g_access_cp'] )
	{
		$all_groups[]	= array( $group_data['g_id'], $group_data['g_title'] );
	}
}

$dropDown	= $this->registry->output->formDropdown( "entered_group", $all_groups );

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['r_title']}</h2>
</div>

<form id='postingform' action="{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=acpperms-group-add-complete" method="post" name="REPLIER">
<input type='hidden' name='_admin_auth_key' value='{$this->registry->getClass('adminFunctions')->_admin_auth_key}' />
<div class='tableborder'>
 <div class='tableheaderalt'>{$this->lang->words['r_selectgroup']}</div>
 <div class='tablesubheader'>&nbsp;</div>
  <table width='100%' cellspacing='0' cellpadding='5' align='center' border='0'>
  <tr>
    <td class='tablerow1'  width='50%'  valign='middle'>{$this->lang->words['r_whatgroup']}</td>
    <td class='tablerow2'  width='50%'  valign='middle'>{$dropDown}</td>
  </tr>
  <tr>
  <td align='center' class='tablesubheader' colspan='2' ><input type='submit' value='{$this->lang->words['r_proceed']}' class='realbutton' accesskey='s'></td>
  </tr>
  </table>
</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}


/**
 * ACP restrictions overview
 *
 * @access	public
 * @param	array 		Members
 * @param	array 		Groups
 * @return	string		HTML
 */
public function acpPermsOverview( $members, $groups ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['r_title']}</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['r_memberrestrict']}</h3>
	
	<table class='alternate_rows double_pad' width='100%'>
		<tr>
			<th width='35%'>{$this->lang->words['r_member']}</th>
			<th width='20%'>{$this->lang->words['r_primary']}</th>
			<th width='20%'>{$this->lang->words['r_secondary']}</th>
			<th width='20%'>{$this->lang->words['r_updated']}</th>
			<th width='5%'>&nbsp;</th>
		</tr>
		{$members}
	</table>
	<div class='acp-actionbar'>
		<div class='rightaction'><input type='button' value='{$this->lang->words['r_findadmin']}' onclick="window.location='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=acpperms-member-add'" class='button primary' accesskey='s'></div>
	</div>	
</div>
<br />
<div class='acp-box'>
	<h3>{$this->lang->words['r_grouprestrict']}</h3>
	
	<table cellpadding='0' cellspacing='0' width='100%' class='alternate_rows double_pad'>
		<tr>
			<th width='55%'>{$this->lang->words['r_group']}</th>
			<th width='20%' align='center'>{$this->lang->words['r_totalmem']}</th>
			<th width='20%' align='center'>{$this->lang->words['r_updated']}</th>
			<th width='5%'>&nbsp;</th>
		</tr>
		{$groups}
	</table>
	<div class='acp-actionbar'>
		<div class='rightaction'><input type='button' value='{$this->lang->words['r_findgroup']}' onclick="window.location='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=acpperms-group-add'" class='button primary' accesskey='s'></div>
	</div>
</div>
<br />
<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=accperms-xml-import' method='post' name='uploadform'  enctype='multipart/form-data' id='uploadform'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->getClass('adminFunctions')->_admin_auth_key}' />
	<input type='hidden' name='MAX_FILE_SIZE' value='10000000000'>
	
	<div class='acp-box'>
	<h3>{$this->lang->words['r_importxml']}</h3>
	<table width='100%' class='alternate_rows'>
		<tr>
			<td width='50%'>{$this->lang->words['r_uploadxml']}<br /><span class='desctext'>{$this->lang->words['r_uploadxml_info']}</span></td>
			<td width='50%'><input class='textinput' type='file' size='30' name='FILE_UPLOAD'></td>
		</tr>
		<tr>
			<td width='50%'>{$this->lang->words['r_filexml']}<br /><span class='desctext'>{$this->lang->words['r_filexml_info']}</span></td>
			<td width='50%'><input type='text' name='file_location' value='ipb_acpperms.xml' size='30' class='textinput'></td>
		</tr>
	</table>
	<div class='acp-actionbar'>
		<div class='centeraction'><input type='submit' value='{$this->lang->words['r_xmlbutton']}' class='button primary' accesskey='s'></div>
	</div>
</div>
</form>

HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Restricted member
 *
 * @access	public
 * @param	array 		Member data
 * @return	string		HTML
 */
public function acpMemberRow( $data ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<tr>
	<td>
		<img src='{$this->settings['skin_acp_url']}/images/lock_close.gif' border='0' alt='@' style='vertical-align:top' />
		<strong>{$data['members_display_name']}</strong>
	</td>
	<td>{$data['_group_name']}</td>
	<td>{$data['_other_groups']}</td>
	<td>{$data['_date']}</td>
	<td>
		<img class='ipbmenu' id="menumember{$data['member_id']}" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' border='0' alt='{$this->lang->words['r_options']}' />
		<ul class='acp-menu' id='menumember{$data['member_id']}_menucontent'>
			<li class='icon edit'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=accperms-member-edit&amp;mid={$data['member_id']}'>{$this->lang->words['r_managerestrict']}</a></li>
			<li class='icon delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=accperms-member-remove&amp;mid={$data['member_id']}");'>{$this->lang->words['r_removeall']}</a></li>
		</ul>
	</td>
</tr>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Restricted group
 *
 * @access	public
 * @param	array 		Group data
 * @return	string		HTML
 */
public function acpGroupRow( $data ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<tr>
 <td class='tablerow1'>
   <img src='{$this->settings['skin_acp_url']}/images/lock_close.gif' border='0' alt='@' style='vertical-align:top' />
   <strong>{$data['_group_name']}</strong>
 </td>
 <td class='tablerow2' align='center'>{$data['_total']}</td>
 <td class='tablerow2' align='center'>{$data['_date']}</td>
 <td class='tablerow1'>
 	<img id="menugroup{$data['row_id']}" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' border='0' alt='{$this->lang->words['r_options']}' class='ipbmenu' />
	<ul class='acp-menu' id='menugroup{$data['row_id']}_menucontent'>
		<li class='icon edit'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=accperms-group-edit&amp;gid={$data['row_id']}'>{$this->lang->words['r_managerestrictg']}</a></li>
		<li class='icon delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=accperms-group-remove&amp;gid={$data['row_id']}");'>{$this->lang->words['r_removeallg']}</a></li>
	</ul>
 </td>
</tr>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Add new restrictions form
 *
 * @access	public
 * @param	int			Role id
 * @param	string		Role type
 * @param	array 		Permissions
 * @param	array 		Access capabilities
 * @return	string		HTML
 */
public function restrictionsForm( $role_id=0, $role_type='member', $permissions=array(), $access=array() ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML

<script type="text/javascript" src='{$this->settings['js_main_url']}acp.permissions.js'></script>
<script type="text/javascript">
//<![CDATA[
document.observe("dom:loaded",function() 
{
ipbAcpTabStrips.register('tab_restrictions');
});
 //]]>
</script>
<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=acpperms-save&amp;secure_key={$secure_key}' method='post' id='adform' name='adform'>
<input type='hidden' name='id' value='{$role_id}' />
<input type='hidden' name='type' value='{$role_type}' />
<ul id='tab_restrictions' class='tab_bar no_title'>
HTML;

foreach( ipsRegistry::$applications as $application )
{
	$IPBHTML .= "<li id='tabtab-TOP|{$application['app_id']}' class=''>{$application['app_title']}</li>\n";
}

$IPBHTML .= <<<HTML
</ul>
<div class='acp-box'>

HTML;

foreach( ipsRegistry::$applications as $app_dir => $application )
{
	$checked	= in_array( $application['app_id'], $access['applications'] ) ? 1 : 0;
	$form		= $this->registry->output->formCheckbox( 'app_' . $application['app_id'], $checked );
	
	$IPBHTML .= <<<HTML
	
	<div id='tabpane-TOP|{$application['app_id']}'>
		<div class='tablesubheader'>{$form} {$this->lang->words['r_grantto']} {$application['app_title']} {$this->lang->words['application_bit']}?</div>		
HTML;

	if( !is_array(ipsRegistry::$modules[ $app_dir ]) OR !count(ipsRegistry::$modules[ $app_dir ]) )
	{
		continue;
	}
			
	foreach( ipsRegistry::$modules[ $app_dir ] as $module )
	{
		$checked	= in_array( $module['sys_module_id'], $access['modules'] ) ? 1 : 0;
		$form		= $this->registry->output->formCheckbox( 'module_' . $module['sys_module_id'], $checked );

		$IPBHTML .= <<<HTML
		<div class='tablesubheader'>{$form} {$this->lang->words['r_grantto']} {$module['sys_module_title']} {$this->lang->words['module_bit']}?</div>		
HTML;

		if( is_array( $permissions ) AND is_array($permissions[ $application['app_id'] ][ $module['sys_module_id'] ]) AND count($permissions[ $application['app_id'] ][ $module['sys_module_id'] ]) )
		{
			// Call me lazy if you wish :P
			$shorten = $permissions[ $application['app_id'] ][ $module['sys_module_id'] ];
			
			foreach( $shorten as $group => $shorter )
			{
				if( !is_array($shorter) OR !count($shorter) )
				{
					continue;
				}

				$IPBHTML .= <<<HTML
				<div class='tablesubheader'>{$shorter['title']}</div>
				<ul class='acp-form alternate_rows'>
HTML;
				if( is_array($shorter['items']) AND count($shorter['items']) )
				{
					foreach( $shorter['items'] as $item_key => $item_text )
					{
						$checked	= (is_array($access['items'][ $module['sys_module_id'] ]) AND in_array($item_key, $access['items'][ $module['sys_module_id'] ] )) ? 1 : 0;
						$form		= $this->registry->output->formCheckbox( 'item_' . $module['sys_module_id'] . '_' . $item_key, $checked );
						
						$IPBHTML .= <<<HTML
							<li><label>{$item_text}</label>{$form}</li>
HTML;
					}
				}
			
				$IPBHTML .= <<<HTML
				</ul>
				<br />
HTML;
			}
		}
	}

	$IPBHTML .= <<<HTML
	</div>

HTML;
}

$IPBHTML .= <<<HTML
</div>
<div class='tablesubheader' style='text-align:center;'><input type='submit' value=' {$this->lang->words['r_savebutton']} ' /></div>
</form>

HTML;

//--endhtml--//
return $IPBHTML;
}

}