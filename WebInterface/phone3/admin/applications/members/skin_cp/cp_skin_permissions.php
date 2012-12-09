<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * ACP permissions skin file
 * Last Updated: $Date: 2009-06-22 07:08:23 -0400 (Mon, 22 Jun 2009) $
 *
 * @author 		$Author: matt $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Members
 * @link		http://www.
 * @since		20th February 2002
 * @version		$Rev: 4801 $
 *
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
 * Confirmation page that perm set was removed
 *
 * @access	public
 * @param	string	Name
 * @return	string	HTML
 */
public function permissionSetRemoveDone( $name ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='tableheaderalt'>{$this->lang->words['per_result']}</div>
	<table width='100%' cellspacing='0' cellpadding='5' align='center' border='0'>
		<tr>
			<td class='tablerow1'  width='100%'  valign='middle'>{$this->lang->words['per_removecustom']}<b>{$name}</b>.</td>
		</tr>
	</table>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show users assigned to the perm mask
 *
 * @access	public
 * @param	integer	Perm mask id
 * @param	string	Permission set name
 * @param	array 	Users with this mask
 * @return	string	HTML
 */
public function permissionSetUsers( $perm_id, $set, $rows ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='tableborder'>
	<div class='tableheaderalt'>{$this->lang->words['per_membersusing']}{$set}</div>

	<table width='100%' cellspacing='0' cellpadding='5' align='center' border='0'>
		<tr>
			<td class='tablesubheader' width='50%' align='center'>{$this->lang->words['per_userdetail']}</td>
			<td class='tablesubheader' width='50%' align='center'>{$this->lang->words['per_action']}</td>
		</tr>
HTML;

foreach( $rows as $r )
{
	$also_using = '';
	
	if ( $r['_extra'] )
	{
		$also_using = "<br />\n&#149;&nbsp;{$this->lang->words['per_alsousing']}{$r['_extra']}\n";
	}
	
$IPBHTML .= <<<HTML
		<tr>
			<td class='tablerow1'  width='50%'  valign='middle'>
				<div style='font-weight:bold;font-size:11px;padding-bottom:6px;margin-bottom:3px;border-bottom:1px solid #000'>{$r['members_display_name']}</div>
				&#149;&nbsp;{$this->lang->words['per_posts']}{$r['posts']}<br />
				&#149;&nbsp;{$this->lang->words['per_email']}{$r['email']}
				{$also_using}
			</td>

			<td class='tablerow2'  width='50%'  valign='middle'>
				&#149;&nbsp;<a href='{$this->settings['base_url']}{$this->form_code}do=remove_mask&amp;id={$r['member_id']}&amp;pid={$perm_id}' title='{$this->lang->words['per_removethis']}'>{$this->lang->words['per_removethisset']}</a><br />
				&#149;&nbsp;<a href='{$this->settings['base_url']}{$this->form_code}do=remove_mask&amp;id={$r['member_id']}&amp;pid=all' title='{$this->lang->words['per_removeall']}'>{$this->lang->words['per_removeall']}</a><br /><br />
			</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>	
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Permissions matrix
 *
 * @access	public
 * @param	array 		Permission names
 * @param	array		Compiled grids
 * @param	array 		Permissions checked
 * @param	array 		Colors
 * @return	string		HTML
 */
public function permissionMatrix( $perm_names, $perm_matrix, $perm_checked, $colors )
{
$IPBHTML = "";
//--starthtml--//

$cols = count( $perm_names ) + 1;
$width = ceil( 83 / count( $perm_names ) );

$IPBHTML .= <<<HTML

<div class='acp-box'>
	<h3>{$this->lang->words['frm_enterthematrix']}</h3>
	<script type='text/javascript' src='{$this->settings['js_main_url']}/acp.permissions2.js'></script>
	
	<table cellpadding='0' cellspacing='0' border='0' class='permission_table' id='perm_matrix'>
		<tr>
			<th style='width: 13%'>&nbsp;</th>
HTML;
			foreach( $perm_names as $k => $v )
			{
			$IPBHTML .= <<<HTML
				<th style='width: {$width}%; text-align: center;'>{$v}</td>
HTML;
			}
		
		$IPBHTML .= <<<HTML
		</tr>
		<tr>
			<td class='section' colspan='{$cols}'>{$this->lang->words['frm_global']}</td>
		</tr>
		<tr>
			<td class='off'>&nbsp;</td>
HTML;
		$col_num = 0;
		foreach( $perm_names as $k => $v )
		{
			$col_num++;
			$IPBHTML .= <<<HTML
					<!-- Check an entire column -->
					<td style='background-color:{$colors[$k]}' class='perm column' id='column_{$col_num}'>
						{$v}<br />
						<input type='checkbox' name='perms[$k][*]' id='col_{$col_num}' value='1'{$perm_checked['*'][$k]}>
					</td>
HTML;
		}
		$IPBHTML .= <<<HTML
		</tr>
HTML;
		$row_num = 0;
		
		foreach( $perm_matrix as $set => $row )
		{
			$set = explode( '%', $set );
			$row_num++;
			$col_num = 0;
			$IPBHTML .= <<<HTML
			<tr>	
				<td class='section' colspan='{$cols}'><strong>{$set[1]}</strong></td>
			</tr>
			<tr id='forum_row_{$row_num}'>
				<td class='off'>
					<input type='button' id='forum_select_row_1_{$row_num}' value=' + ' class='select_row' />&nbsp;
					<input type='button' id='forum_select_row_0_{$row_num}' value=' &ndash; ' class='select_row' />
				</td>
HTML;
				foreach( $row as $key => $perm )
				{
					$col_num++;
					$IPBHTML .= <<<HTML
						<td class='perm' id='clickable_{$col_num}' style='background-color:{$colors[$key]}'>
							{$perm}<br />
							<input type='checkbox' name='perms[{$key}][{$set[0]}]' id='perm_{$row_num}_{$col_num}' value='1'{$perm_checked[$set[0]][$key]}>
						</td>
HTML;
				}
			
		$IPBHTML .= <<<HTML
			</tr>
HTML;
		}
		
		$IPBHTML .= <<<HTML
	</table>
</div>

<script type='text/javascript'>
	var permissions = new acp.permissions( { 'form': 'adminform', 'table': 'perm_matrix', 'app': 'forum' } );
</script>

HTML;

//--endhtml--//
return $IPBHTML;	
}

/*

				$IPBHTML .= <<<HTML


		<div class='tableborder' id='perm-matrix'>
			<div class='tableheaderalt' id='perm-header'>{$this->lang->words['frm_enterthematrix']}</div>
			<table cellpadding='4' cellspacing='0' border='0' width='100%'>
				<tr>
					<td class='tablesubheader' width='13%'>&nbsp;</td>
	HTML;

	foreach( $perm_names as $k => $v )
	{
	$IPBHTML .= <<<HTML
		<td class='tablesubheader' align='center'>{$v}</td>
	HTML;
	}

	$IPBHTML .= <<<HTML
				</tr>

				<tr>
					<td colspan='7' class='tablerow1'>
						<fieldset>
							<legend>{$this->lang->words['frm_global']}</legend>

							<table cellpadding='4' cellspacing='0' border='0' class='tablerow1' width='100%'>
								<tr>
									<td class='tablerow2' width='14%'>&nbsp;</td>
	HTML;

	$col_num = 0;
	foreach( $perm_names as $k => $v )
	{
		$col_num++;
	$IPBHTML .= <<<HTML
									<!-- Check an entire column -->
									<td style='text-align:center;background-color:{$colors[$k]}' class='column_header'>
										<strong>{$v}</strong><br />
										<input type='checkbox' name='perms[$k][*]' id='col_{$col_num}' value='1'{$perm_checked['*'][$k]}>
									</td>
	HTML;
	}
	$IPBHTML .= <<<HTML
								</tr>
							</table>
						</fieldset>
					</td>
				</tr>
	HTML;

	$row_num = 0;
	foreach( $perm_matrix as $set => $row )
	{
		$set = explode( ':', $set );
		$row_num++;
	$IPBHTML .= <<<HTML
				<tr>
					<td colspan='7' class='tablerow1'>
						<fieldset>
							<legend><strong>{$set[1]}</strong></legend>

							<table cellpadding='4' cellspacing='0' border='0' class='tablerow1' width='100%'>
								<tr>
									<td class='tablerow2' width='14%'>
										<input type='button' id='button' value='+' onclick='ACPPermissions.checkRow( $row_num, 1 )' />&nbsp;
										<input type='button' id='button' value='-' onclick='ACPPermissions.checkRow( $row_num, 0 )' />
									</td>
	HTML;

	$col_num = 0;
	foreach( $row as $key => $perm )
	{
		$col_num++;

	$IPBHTML .= <<<HTML
									<td class='tablerow1' id='clickable_{$i}' style='text-align:center;background-color:{$colors[$key]}'>
										<strong>{$perm}</strong><br />
										<input type='checkbox' name='perms[{$key}][{$set[0]}]' id='perm_{$row_num}_{$col_num}' value='1'{$perm_checked[$set[0]][$key]}>
									</td>
	HTML;
	}

	$IPBHTML .= <<<HTML
								</tr>
							</table>
						</fieldset>
					</td>
				</tr>
	HTML;
	}

	$IPBHTML .= <<<HTML
			</table>
		</div>
	HTML;
	
	*/
/**
 * Permission set matrix
 *
 * @access	public
 * @param	array 		Perm names
 * @param	array 		Perm grids
 * @param	array 		Boxes to check
 * @param	array 		Colors
 * @param	string		Application
 * @param	string		Type
 * @return	string		HTML
 */
public function permissionSetMatrix( $perm_names, $perm_matrix, $perm_checked, $colors, $app, $type )
{
$IPBHTML = "";
//--starthtml--//

if( !is_array( $perm_matrix ) || !count( $perm_matrix ) ){ return ''; }

$IPBHTML .= <<<HTML
		<table cellpadding='0' cellspacing='0' border='0' class='permission_table' id='perm_matrix_{$app}'>
HTML;

if( is_array( $perm_names ) && count( $perm_names ) )
{
	$col_num = 0;
	$col_width = floor( 87 / count( $perm_names ) );
	$IPBHTML .= <<<HTML
		<tr>
			<td style='padding: 0px;'>
				<table cellpadding='0' cellspacing='0' border='0' width='100%'>
					<tr>
						<td style='width: 13%'>&nbsp;</td>						
HTML;

	foreach( $perm_names as $key => $text )
	{
		$col_num++;
		$IPBHTML .= <<<HTML
						<td class='perm' style='background-color: {$colors[$key]}; width: {$col_width}%'>
							
							<input type='button' id='{$app}_select_col_1_{$col_num}' value=' + ' class='select_col' />&nbsp;
							<input type='button' id='{$app}_select_col_0_{$col_num}' value=' &ndash; ' class='select_col' />
						</td>
HTML;
	}
	
	$IPBHTML .= <<<HTML
					</tr>
				</table>
			</td>
		</tr>
HTML;
}

$row_num = 0;
foreach( $perm_matrix as $set => $row )
{
	$set = explode( '%', $set );
	$row_num++;
$IPBHTML .= <<<HTML
			<tr>
				<td class='section'><strong>{$set[1]}</strong></td>
			</tr>
			<tr>
				<td style='padding: 0px;'>	
						<table cellpadding='0' cellspacing='0' border='0' width='100%'>
							<tr id='{$app}_row_{$row_num}'>
								<td class='off' style='width: 13%; text-align: right;'>
									<input type='button' id='{$app}_select_row_1_{$row_num}' value=' + ' class='select_row' />&nbsp;
									<input type='button' id='{$app}_select_row_0_{$row_num}' value=' &ndash; ' class='select_row' />
								</td>
HTML;

$col_num = 0;
//$col_width = floor( 87 / count( $row ) );

foreach( $perm_names as $key => $perm )
{
	$col_num++;
	
	if( isset( $row[ $key ] ) )
	{
		$IPBHTML .= <<<HTML
										<td class='perm' style='background-color:{$colors[$key]}; width: {$col_width}%'>
											{$perm}<br />
											<input type='checkbox' id='{$app}_{$col_num}_{$row_num}' name='perms[{$app}][{$type}][{$key}][{$set[0]}]' value='1'{$perm_checked[$set[0]][$key]}>
										</td>
HTML;
	}
	else
	{
		$IPBHTML .= <<<HTML
										<td class='perm' style='background-color:{$colors[$key]}; width: {$col_width}%'>
											<em class='desctext'>{$this->lang->words['per_notused']}</em>
										</td>
HTML;
	
	}
}

$IPBHTML .= <<<HTML
							</tr>
						</table>				
				</td>
			</tr>
HTML;
}

$IPBHTML .= <<<HTML
		</table>
		<script type='text/javascript'>
			perms["{$app}"] = new acp.permissions( { 'form': 'adminform', 'table': 'perm_matrix_{$app}', 'app': '{$app}' } );
		</script>
HTML;

//--endhtml--//
return $IPBHTML;	
}

/**
 * Show dialog to advise user to apply group restrictions
 *
 * @access	public
 * @return	string		HTML
 */
public function groupAdminConfirm( $group_id ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<!--SKINNOTE: Not yet skinned-->
<div class='tableborder'>
 <div class='tableheaderalt'>{$this->lang->words['sm_configrest']}</div>
	<div class='tablerow1'>
	{$this->lang->words['sm_detectacp']}
 		<br /><br />
 		{$this->lang->words['sm_setrestrict']}
 	</div>
  	<div class='tablesubheader' align='center'>
  		<span class='fauxbutton'><a href='{$this->settings['base_url']}&amp;{$this->form_code}'>{$this->lang->words['sm_nothanks']}</a></span>&nbsp;&nbsp;
  		<span class='fauxbutton'><a href='{$this->settings['base_url']}&amp;module=restrictions&amp;section=restrictions&amp;do=acpperms-group-add-complete&amp;entered_group={$group_id}'>{$this->lang->words['sm_yesplease']}</a></span>
	</div>
</div>
<br />
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Edit permissions mask
 *
 * @access	public
 * @param	string		Set name
 * @param	array 		Perm grids
 * @param	integer		Id
 * @param	array 		Applications
 * @return	string		HTML
 */
public function permissionEditor( $set_name, $perm_matricies, $id, $apps ) {
$IPBHTML = "";
//--starthtml--//

$i			= 1;

$IPBHTML .= <<<HTML
<script type="text/javascript">
//<![CDATA[
document.observe("dom:loaded",function() 
{
ipbAcpTabStrips.register('tab_perms');
});
 //]]>
</script>

<div class='section_title'>
	<h2>{$set_name}</h2>
</div>
<script type='text/javascript' src='{$this->settings['js_main_url']}/acp.permissions2.js'></script>
<script type='text/javascript'>
	var perms = {};
</script>

<form action='{$this->settings['base_url']}{$this->form_code}do=do_edit_set&amp;id={$this->request['id']}' method='post' id='adminform'>
	<input type='hidden' name='_from' value='{$this->request['_from']}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['per_editset']}</h3>
		
		<table class='alternate_rows' width='100%'>
			<tr>
				<td>{$this->lang->words['per_setname']}</td>
				<td><input type='text' class='input' size='30' name='perm_name' value='{$set_name}' /></td>
			</tr>
		</table>
	</div>
	<br />
HTML;

foreach( $apps as $app => $type )
{
	$IPBHTML .= <<<HTML
	<input type='hidden' name='apps[{$app}][{$type}]' value='1' />
HTML;
}

$IPBHTML .= <<<HTML
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
		<ul id='tab_perms' class='tab_bar no_title'>
HTML;
	foreach( $perm_matricies as $type => $matrix )
	{
		if( !$matrix ){ continue; }
		$IPBHTML .= "				<li id='tabtab-MEMBERS|" . $i ."' class=''>" . $type . "</li>";
		$i++;
	}
	
$IPBHTML .= <<<HTML
</ul>
HTML;

$i = 1;
	
foreach( $perm_matricies as $type => $matrix )
{
	if( !$matrix ){ continue; }
$IPBHTML .= <<<HTML

	<div id='tabpane-MEMBERS|{$i}'>
		{$matrix}
		
	</div>
HTML;
	$i++;
}

$IPBHTML .= <<<HTML
	<div class='acp-actionbar'><input type='submit' value='{$this->lang->words['frm_savechanges']}' class='realbutton' /></div>
	<br style='clear: both' />
</form>
<br /><br /><br /><br /><br />
HTML;

//--endhtml--//
return $IPBHTML;
}


/**
 * Mask splash page
 *
 * @access	public
 * @param	array 		Masks
 * @param	string		Dropdown lsit
 * @return	string		HTML
 */
public function permissionsSplash( $rows, $dlist ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['per_title']}</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['per_setname']}</h3>
	
	<table class='alternate_rows' width='100%'>
		<tr>
			<th width='20%'>{$this->lang->words['per_name']}</th>
			<th width='15%'>{$this->lang->words['per_usedgroups']}</th>
			<th width='20%'>{$this->lang->words['per_usedmembers']}</th>
			<th width='1%'>&nbsp;</th>
		</tr>
HTML;

/* ROW */
foreach( $rows as $r )
{
$IPBHTML .= <<<HTML
		<tr>
  			<td><strong>{$r['name']}</strong></td>
  			<td>{$r['groups']}</td>
  			<td align='right'>
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
		<li class='icon edit'><a href='{$this->settings['base_url']}{$this->form_code}do=edit_set_form&amp;id={$r['id']}'>{$this->lang->words['per_editset']}</a></li>
HTML;

	if ( ! $r['isactive'] )
	{
$IPBHTML .= <<<HTML
		<li class='icon delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=delete_set&amp;id={$r['id']}");'>{$this->lang->words['per_deleteset']}</a></li>
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
<form name='theAdminForm' id='adminform' action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='do_create_set' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->_admin_auth_key}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['per_createnew']}</h3>
		
		<table class='alternate_rows' width='100%'>
			<tr>
				<td>{$this->lang->words['per_setname']}</td>
				<td><input type='text' class='input' size='30' name='new_perm_name' /></td>
			</tr>
			<tr>
				<td>{$this->lang->words['per_baseon']}</td>
				<td><select name='new_perm_copy' class='dropdown'>{$dlist}</select></td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<div class='centeraction'>
				<input type='submit' value='{$this->lang->words['per_createbutton']}' class='button primary' />
			</div>
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}


}