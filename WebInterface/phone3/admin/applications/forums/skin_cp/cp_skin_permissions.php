<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Permissions masks skin functions
 * Last Updated: $LastChangedDate: 2009-03-26 19:05:57 -0400 (Thu, 26 Mar 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Forums
 * @link		http://www.
 * @since		14th May 2003
 * @version		$Rev: 4324 $
 */
 
class cp_skin_permissions extends output
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
 * Preview forum permissions
 *
 * @access	public
 * @param	integer	Mask id
 * @param	string	Mask title
 * @param	string	HTML for type dropdown
 * @param	array 	Forum rows
 * @param	string	Type human-understandable text
 * @return	string	HTML
 */
public function previewForumPermissions( $id, $title, $type_drop, $rows, $human_type )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='tableborder'>
	<div class='tableheaderalt'>{$this->lang->words['per_preview']}{$title}</div>

	<table width='100%' cellspacing='0' cellpadding='5' align='center' border='0'>
		<tr>
			<td class='tablesubheader' width='100%' align='center' colspan='2'>{$human_type}</td>
		</tr>
		
		<tr>
			<td class='tablerow1'  width='100%'  valign='middle'>
HTML;

foreach( $rows as $r )
{
$IPBHTML .= <<<HTML
				<span style='{$r['css']}'>{$r['name']}</span><br />
HTML;
}

$IPBHTML .= <<<HTML
			</td>
		</tr>
	</table>
</div><br />

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm'  id='theAdminForm'>
	<input type='hidden' name='do' value='preview_forums' />
	<input type='hidden' name='id' value='{$id}' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->_admin_auth_key}' />
	
	<div class='tableborder'>
		<div class='tableheaderalt'>{$this->lang->words['per_legend']}</div>

		<table width='100%' cellspacing='0' cellpadding='5' align='center' border='0'>
			<tr>
				<td class='tablerow1' width='60%' valign='middle'>{$this->lang->words['per_can']}{$human_type}</td>
				<td class='tablerow2' width='40%' valign='middle'><input type='text' readonly='readonly' style='border:1px solid black;background-color:green;size=30px' name='blah'></td>
			</tr>
			<tr>
				<td class='tablerow1' width='60%' valign='middle'>{$this->lang->words['per_cannot']}{$human_type}</td>
				<td class='tablerow2' width='40%' valign='middle'><input type='text' readonly='readonly' style='border:1px solid gray;background-color:red;size=30px' name='blah'></td>
			</tr>
			<tr>
				<td class='tablerow1' width='60%' valign='middle'>{$this->lang->words['per_category']}</td>
				<td class='tablerow2' width='40%' valign='middle'><input type='text' readonly='readonly' style='border:1px solid gray;background-color:grey;size=30px' name='blah'></td>
			</tr>
			<tr>
				<td class='tablerow1' width='60%' valign='middle'>{$this->lang->words['per_testwith']}</td>
				<td class='tablerow2' width='40%' valign='middle'>{$type_drop}</select>
				</td>
			</tr>
			<tr>
				<td align='center' class='tablesubheader' colspan='2' ><input type='submit' value='{$this->lang->words['per_updatebutton']}' class='realbutton' accesskey='s'></td>			
			</tr>
		</table>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Permissions editor
 *
 * @access	public
 * @param	integer	Mask id
 * @param	string	Mask title
 * @param	array 	Rows
 * @return	string	HTML
 */
public function permissionsForum( $id, $title, $rows ){

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['per_title_edit']}</h2>
</div>

<!-- RENAME PERMISSION SET -->
<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='nameForm'  id='nameForm'>
	<input type='hidden' name='do' value='donameedit' />
	<input type='hidden' name='id' value='{$id}' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->_admin_auth_key}' />
	
	<div class='tableborder'>
		<div class='tableheaderalt'>{$this->lang->words['per_rename']}{$title}</div>
	
		<table width='100%' cellspacing='0' cellpadding='5' align='center' border='0'>
			<tr>
				<td class='tablerow1'  width='40%'  valign='middle'>{$this->lang->words['per_setname']}</td>
				<td class='tablerow2'  width='60%'  valign='middle'><input type='text' name='perm_name' id='perm_name' value='{$title}' size='30' class='textinput' /></td>
			</tr>
			<tr>
				<td align='center' class='tablesubheader' colspan='2' ><input type='submit' value='{$this->lang->words['per_editbutton']}' class='realbutton' accesskey='s'></td>
			</tr>
		</table>
	</div>
</form>	
<br />
<!-- / RENAME PERMISSION SET -->
<!-- PERMISSION MATRIX -->
<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm'  id='theAdminForm'>
	<input type='hidden' name='do' value='dofedit' />
	<input type='hidden' name='id' value='{$id}' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->_admin_auth_key}' />
	
	<div class='tableborder'>
		<div class='tableheaderalt'>Forum Access Permissions for {$title}</div>

		<table width='100%' cellspacing='0' cellpadding='5' align='center' border='0'>
			<tr>
				<td class='tablesubheader' width='25%' align='center'>{$this->lang->words['per_forumname']}</td>
				<td class='tablesubheader' width='10%' align='center'>{$this->lang->words['per_show_c']}<br /><input id='show' type='checkbox' onclick='checkcol("show", this.checked );' /></td>
				<td class='tablesubheader' width='10%' align='center'>{$this->lang->words['per_read_c']}<br /><input id='read' type='checkbox' onclick='checkcol("read", this.checked );' /></td>
				<td class='tablesubheader' width='10%' align='center'>{$this->lang->words['per_reply_c']}<br /><input id='reply' type='checkbox' onclick='checkcol("reply", this.checked );' /></td>
				<td class='tablesubheader' width='10%' align='center'>{$this->lang->words['per_start_c']}<br /><input id='start' type='checkbox' onclick='checkcol("start", this.checked );' /></td>
				<td class='tablesubheader' width='10%' align='center'>{$this->lang->words['per_upload_c']}<br /><input id='upload' type='checkbox' onclick='checkcol("upload", this.checked );' /></td>
				<td class='tablesubheader' width='10%' align='center'>{$this->lang->words['per_download_c']}<br /><input id='download' type='checkbox' onclick='checkcol("download", this.checked );' /></td>
			</tr>
HTML;

foreach( $rows as $r )
{
$IPBHTML .= <<<HTML
			<tr>
				<td class='{$r['css']}' width='25%' valign='middle'>
					<div style='float:right;width:auto;'>
						<input type='button' id='button' value='+' onclick='checkrow( {$row['id']},true )' />&nbsp;
						<input type='button' id='button' value='-' onclick='checkrow( {$row['id']},false )' />
					</div>
					<b>{$r['depthed_name']}</b>					
				</td>
				<td class='{$r['css']}' width='10%' valign='middle'><div style='background-color:#ecd5d8; padding:4px;'>{$this->_permission_check_box( $r['id'], 'view', $r['_perms'] )}</td>
				<td class='{$r['css']}' width='10%' valign='middle'><div style='background-color:#dbe2de; padding:4px;'>{$this->_permission_check_box( $r['id'], 'read', $r['_perms'] )}</div></td>
				<td class='{$r['css']}' width='10%' valign='middle'><div style='background-color:#dbe6ea; padding:4px;'>{$this->_permission_check_box( $r['id'], 'reply', $r['_perms'] )}</div></td>
				<td class='{$r['css']}' width='10%' valign='middle'><div style='background-color:#d2d5f2; padding:4px;'>{$this->_permission_check_box( $r['id'], 'start', $r['_perms'] )}</div></td>
				<td class='{$r['css']}' width='10%' valign='middle'><div style='background-color:#ece6d8; padding:4px;'>{$this->_permission_check_box( $r['id'], 'upload', $r['_perms'] )}</div></td>
				<td class='{$r['css']}' width='10%' valign='middle'><div style='background-color:#dfdee9; padding:4px;'>{$this->_permission_check_box( $r['id'], 'download', $r['_perms'] )}</div></td>
			</tr>
HTML;
}

$IPBHTML .= <<<HTML
			<tr>
				<td align='center' class='tablesubheader' colspan='7' ><input type='submit' value='{$this->lang->words['per_updatebutton']}' class='realbutton' accesskey='s'></td>				
			</tr>
		</table>		
	</div>
</form>
<!-- / PERMISSION MATRIX -->

HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Check box generator helper
 *
 * @access	private
 * @param	integer	Mask id
 * @param	array 	Mask data
 * @param	array 	Current selections
 * @return	string	HTML
 */
private function _permission_check_box( $id, $perm, $r )
{
$IPBHTML = "";
//--starthtml--//

if( in_array( $r[ $perm ], array( $this->lang->words['per_global'], $this->lang->words['per_notused'] ) ) )
{
$IPBHTML .= <<<HTML
<center><i>{$r[$perm]}</i></center>
HTML;
}
else if( $r[ $perm ] == 'checked' )
{
$IPBHTML .= <<<HTML
<center><input type='checkbox' name='{$perm}[{$id}]' id='{$perm}_{$id}' onclick="obj_checked('{$perm}', {$id} );" value='1' checked></center>
HTML;
}
else
{
$IPBHTML .= <<<HTML
<center><input type='checkbox' name='{$perm}[{$id}]' id='{$perm}_{$id}' onclick="obj_checked('{$perm}', {$id} );" value='1'></center>
HTML;
}
//--endhtml--//
return $IPBHTML;
}

/**
 * Perm mask splash page
 *
 * @access	public
 * @param	array 	Mask rows
 * @param	string	Dropdown list
 * @return	string	HTML
 */
public function permissionsSplash( $rows, $dlist ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['per_title']}</h2>
</div>

<div class='tableborder'>
	<div class='tableheaderalt'>{$this->lang->words['per_setname']}</div>
	
	<table cellpadding='4' cellspacing='0' width='100%'>
		<tr>
			<td class='tablesubheader' width='20%'>{$this->lang->words['per_name']}</td>
			<td class='tablesubheader' width='15%'>{$this->lang->words['per_usedgroups']}</td>
			<td class='tablesubheader' width='20%' align='center'>{$this->lang->words['per_usedmembers']}</td>
			<td class='tablesubheader' width='1%'>&nbsp;</td>
		</tr>
HTML;

/* ROW */
foreach( $rows as $r )
{
$IPBHTML .= <<<HTML
<tr>
  <td class='tablerow2'><strong>{$r['name']}</strong></td>
  <td class='tablerow1'>{$r['groups']}</td>
  <td class='tablerow1' align='center'>
HTML;
if ( $r['mems'] > 0 )
{
$IPBHTML .= <<<HTML
{$r['mems']} (<a href='#' onclick='return acp.openWindow("{$this->settings['base_url']}{$this->form_code_js}do=view_perm_users&amp;id={$r['id']}", "{$this->lang->words['per_user']}", "500","350");' title='{$this->lang->words['per_viewnames']}'>{$this->lang->words['per_view']}</a>)
HTML;
}
else
{
$IPBHTML .= <<<HTML
{$this->lang->words['per_nomember']}
HTML;
}
$IPBHTML .= <<<HTML
  </td>															
  <td class='tablerow1' align='center'>
  	<img id="menu{$r['id']}" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' border='0' alt='{$this->lang->words['per_options']}' class='ipbmenu' />
	<ul class='acp-menu' id='menu{$r['id']}_menucontent'>
		<li class='icon view'><a href='#' onclick='return acp.openWindow(\"{$this->settings['base_url']}{$this->form_code_js}do=preview_forums&amp;id={$r['id']}&amp;t=read\", \"{$this->lang->words['per_preview']}\", \"400\",\"350\");' title='{$this->lang->words['per_previewtext']}'>{$this->lang->words['per_previewset']}</a></li>
		<li class='icon edit'><a href='{$this->settings['base_url']}{$this->form_code}do=fedit&amp;id={$r['id']}'>{$this->lang->words['per_editset']}</a></li>
HTML;

	if ( ! $r['isactive'] )
	{
$IPBHTML .= <<<HTML
		<li class='icon delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=pdelete&amp;id={$r['id']}");'>{$this->lang->words['per_deleteset']}</a></li>
HTML;
	}
	else
	{
$IPBHTML .= <<<HTML
		<li class='icon delete'>{$this->lang->words['per_inuse']}</li>
HTML;
	}
$IPBHTML .= <<<HTML
	</ul>
  </td>
</tr>
HTML;
}
/* / ROW */

$IPBHTML .= <<<HTML
	</table>
</div>
<br />
<form name='theAdminForm' id='adminform' action='{$this->settings['base_url']}{$this->form_code}&amp;do=dopermadd' method='post'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->_admin_auth_key}' />
	
	<div class='tableborder'>
		<div class='tableheaderalt'>{$this->lang->words['per_createnew']}</div>
		
		<table cellpadding='4' cellspacing='0' width='100%'>
			<tr>
				<td class='tablerow1'>{$this->lang->words['per_setname']}</td>
				<td class='tablerow2'><input type='text' class='input' size='30' name='new_perm_name' /></td>
			</tr>
			<tr>
				<td class='tablerow1'>{$this->lang->words['per_baseon']}</td>
				<td class='tablerow2'><select name='new_perm_copy' class='dropdown'>{$dlist}</select></td>
			</tr>
		</table>
		
		<div class='tablefooter' align='center'><input type='submit' value='{$this->lang->words['per_createbutton']}' class='realbutton' /></div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

}