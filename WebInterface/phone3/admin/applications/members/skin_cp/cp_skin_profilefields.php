<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * ACP profile fields skin file
 * Last Updated: $Date: 2009-07-06 03:32:52 -0400 (Mon, 06 Jul 2009) $
 *
 * @author 		$Author: matt $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Members
 * @link		http://www.
 * @since		20th February 2002
 * @version		$Rev: 4840 $
 *
 */
 
class cp_skin_profilefields extends output
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
 * Add/edit a group
 *
 * @access	public
 * @param	int			ID
 * @param	array 		Group data
 * @param	string		Page title
 * @param	string		Action
 * @return	string		HTML
 */
public function groupForm( $id, $data, $title, $do ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$title}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm' id='theAdminForm'>
	<input type='hidden' name='do' value='{$do}' />
	<input type='hidden' name='id' value='{$id}' />
	<input type='hidden' name='_admin_auth_key' value='{$this->memberData['form_hash']}' />
	
	<div class='acp-box'>
		<h3>{$title}</h3>
		
		<ul class='acp-form alternate_rows'>
			<li>
				<label>{$this->lang->words['cf_g_name']}</label>
				<input type='text' name='pf_group_name' value="{$data['pf_group_name']}" size='30' class='textinput'>
			</li>
			<li>
				<label>{$this->lang->words['cf_g_key']}<span class='desctext'>{$this->lang->words['cf_g_key_desc']}</span></label>
				<input type='text' name='pf_group_key' value="{$data['pf_group_key']}" size='30' class='textinput'>
			</li>
		</ul>
		
		<div class='acp-actionbar'>
			<div class='centeraction'>
				<input type='submit' value='{$this->lang->words['cf_g_save']}' class='button primary' accesskey='s'>
			</div>
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * List all the gropus
 *
 * @access	public
 * @param	array 		Groups
 * @return	string		HTML
 */
public function groupList( $rows )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['cf_g_groups']}</h2>
	<ul class='context_menu'>
		<li><a href='{$this->settings['base_url']}{$this->form_code}do=group_form_add'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/add.png' alt='' /> {$this->lang->words['cf_g_add']}</a></li>
	</ul>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['cf_g_groups']}</h3>
	
	<table class='alternate_rows' width='100%'>
HTML;

if( ! count( $rows ) )
{
$IPBHTML .= <<<HTML
		<tr>
			<td>{$this->lang->words['cf_nonefound']}</td>
		</tr>		
HTML;
}
else
{
	foreach( $rows as $r )
	{
$IPBHTML .= <<<HTML
		<tr>
			<td width='95%'><strong>{$r['pf_group_name']}</strong><div class='graytext'>({$r['pf_group_key']})</div></td>
			<td width='5%'>
				<img class='ipbmenu' id="menu{$r['pf_group_id']}" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt='{$this->lang->words['a_options']}' />
				<ul class='acp-menu' id='menu{$r['pf_group_id']}_menucontent'>
					<li class='icon edit'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=group_form_edit&id={$r['pf_group_id']}'>{$this->lang->words['cf_g_edit']}</a></li>
					<li class='icon delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}&amp;do=group_form_delete&id={$r['pf_group_id']}")'>{$this->lang->words['cf_g_delete']}</a></li>
				</ul>
			</td>
		</tr>
HTML;
	}		
}

$IPBHTML .= <<<HTML
	</table>	
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * List custom profile fields
 *
 * @access	public
 * @param	array 		Fields
 * @return	string		HTML
 */
public function customProfileFieldsList( $rows )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['cf_management']}</h2>
	<ul class='context_menu'>
		<li><a href='{$this->settings['base_url']}{$this->form_code}do=add'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/add.png' alt='' /> {$this->lang->words['cf_addbutton']}</a></li>
	</ul>
</div>

<div class='acp-box'>
 	<h3>{$this->lang->words['cf_management']}</h3>
	<div>
		<table width='100%' border='0' cellspacing='0' cellpadding='0'>
			<tr>
				<td class='tablesubheader' style='width: 2%'>&nbsp;</td>
				<td class='tablesubheader' style='width: 24%'>{$this->lang->words['cf_title']}</td>
				<td class='tablesubheader' style='width: 20%'>{$this->lang->words['cf_type']}</td>
				<td class='tablesubheader' style='width: 13%; text-align: center;'>{$this->lang->words['cf_required']}</td>
				<td class='tablesubheader' style='width: 13%; text-align: center;'>{$this->lang->words['cf_notpublic']}</td>
				<td class='tablesubheader' style='width: 13%; text-align: center;'>{$this->lang->words['cf_showreg']}</td>
				<td class='tablesubheader' style='width: 13%; text-align: center;'>{$this->lang->words['cf_adminonly']}</td>
				<td class='tablesubheader' style='width: 2%; text-align: center;'>&nbsp;</td>
			</tr>
		</table>
	</div>
HTML;

if( ! count( $rows ) )
{
$IPBHTML .= <<<HTML
		<li style='width:100%; clear:both;'>
			<div style='width:100%;'>{$this->lang->words['cf_nonefound']}</div>
		</li>
	</ul>
HTML;
}
else
{
	$IPBHTML .= <<<HTML
	</ul>
HTML;

	$incrementer	= 1;
	
	foreach( $rows as $group => $fields )
	{
$IPBHTML .= <<<HTML
		<ul id='handle_{$incrementer}' class='alternate_rows'>
			<li class='tablesubsubheader'>
				<strong>{$group}</strong>
			</li>
HTML;

		foreach( $fields as $r )
		{
$IPBHTML .= <<<HTML
		<li class='isDraggable' style='width:100%;' id='field_{$r['pf_id']}'>
			<table width='100%' cellpadding='0' cellspacing='0' class='double_pad'>
				<tr>
					<td style='width: 2%'>
						<div class='draghandle'><img src='{$this->settings['skin_acp_url']}/_newimages/drag_icon.png' alt='drag' /></div>
					</td>
					<td style='width: 24%'>
HTML;
					if( $r['pf_icon'] )
					{
						$IPBHTML .= "<img src='{$this->settings['public_dir']}{$r['pf_icon']}' alt='Icon' />&nbsp;";
					}
					
					$IPBHTML .= <<<HTML
						<a href="{$this->settings['base_url']}{$this->form_code}do=edit&amp;id={$r['pf_id']}"><strong>{$r['pf_title']}</strong></a>
HTML;
					if( $r['pf_desc'] )
					{
						$IPBHTML .= <<<HTML
						<br /><span class='desctext'>{$r['pf_desc']}</span>
HTML;
					}
					
					$IPBHTML .= <<<HTML
					</td>
					<td style='width: 20%'>
						{$r['pf_type']}
					</td>
					<td style='width: 13%; text-align: center;'>
						<img src='{$this->settings['skin_acp_url']}/_newimages/icons/{$r['_req']}' alt='Icon' />
					</td>
					<td style='width: 13%; text-align: center;'>
						<img src='{$this->settings['skin_acp_url']}/_newimages/icons/{$r['_hide']}' alt='Icon' />
					</td>
					<td style='width: 13%; text-align: center;'>
						<img src='{$this->settings['skin_acp_url']}/_newimages/icons/{$r['_regi']}' alt='Icon' />
					</td>
					<td style='width: 13%; text-align: center;'>
						<img src='{$this->settings['skin_acp_url']}/_newimages/icons/{$r['_admin']}' alt='Icon' />
					</td>
					<td style='width: 2%'>
						<img class='ipbmenu' id="menu{$r['pf_id']}" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt='{$this->lang->words['a_options']}' />
						<ul class='acp-menu' id='menu{$r['pf_id']}_menucontent'>
							<li class='icon edit'><a href="{$this->settings['base_url']}{$this->form_code}do=edit&amp;id={$r['pf_id']}">{$this->lang->words['cf_edit']}</a></li>
							<li class='icon delete'><a href="{$this->settings['base_url']}{$this->form_code}do=delete&amp;id={$r['pf_id']}">{$this->lang->words['cf_delete']}</a></li>
						</ul>
					</td>
				</tr>
			</table>
		</li>
HTML;
		}
		
	$IPBHTML .= <<<HTML
	</ul>
		<script type="text/javascript">
		dropItLikeItsHot{$incrementer} = function( draggableObject, mouseObject )
		{
			var options = {
							method : 'post',
							parameters : Sortable.serialize( 'handle_{$incrementer}', { tag: 'li', name: 'fields' } )
						};
		 
			new Ajax.Request( "{$this->settings['base_url']}&{$this->form_code_js}&do=reorder&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' ), options );
		
			return false;
		};
		
		Sortable.create( 'handle_{$incrementer}', { only: 'isDraggable', revert: true, format: 'field_([0-9]+)', onUpdate: dropItLikeItsHot{$incrementer}, handle: 'draghandle' } );
		
		</script>
HTML;

	$incrementer++;
	}		
}

$IPBHTML .= <<<HTML
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Add/edit profile field form
 *
 * @access	public
 * @param	int			ID
 * @param	string		Action
 * @param	string		Button text
 * @param	array 		Field data
 * @param	string		Page title
 * @return	string		HTML
 */
public function customProfileFieldForm( $id, $do, $button, $data, $title )
{
$IPBHTML = "";

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$title}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm' id='theAdminForm'>
	<input type='hidden' name='do' value='{$do}' />
	<input type='hidden' name='id' value='{$id}' />
	<input type='hidden' name='_admin_auth_key' value='' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['cf_settings']}</h3>
		
		<table class='form_table double_pad alternate_rows' cellspacing='0' cellpadding='0'>
			<tr>
		 		<td style='width: 40%;'>
					<label>{$this->lang->words['cf_f_title']}</label><br />
					<span class='desctext'>{$this->lang->words['cf_f_title_info']}</span>
				</td>
		 		<td style='width: 60%'>
		 			<input type='text' name='pf_title' value="{$data['pf_title']}" size='30' class='textinput'>
		 		</td>
		 	</tr>
			<tr>
		 		<td>
					<label>{$this->lang->words['cf_f_desc']}</label><br />
					<span class='desctext'>{$this->lang->words['cf_f_desc_info']}</span>
				</td>
		 		<td>
		 			<input type='text' name='pf_desc' value="{$data['pf_desc']}" size='30' class='textinput'>
		 		</td>
		 	</tr>
			<tr>
		 		<td>
					<label>{$this->lang->words['cf_f_type']}</label>
				</td>
		 		<td>
		 			{$data['pf_type']}
		 		</td>
		 	</tr>
			<tr>
		 		<td>
					<label>{$this->lang->words['cf_f_group']}</label>
				</td>
		 		<td>
		 			{$data['pf_group_id']}
		 		</td>
		 	</tr>
			<tr>
		 		<td>
					<label>{$this->lang->words['cf_f_icon']}</label>
				</td>
		 		<td>
		 			{$data['pf_icon']}
		 		</td>
		 	</tr>
			<tr>
		 		<td>
					<label>{$this->lang->words['cf_f_key']}</label><br />
					<span class='desctext'>{$this->lang->words['cf_f_key_info']}</span>
				</td>
		 		<td>
		 			{$data['pf_key']}
		 		</td>
		 	</tr>
			<tr>
		 		<td>
					<label>{$this->lang->words['cf_f_max']}</label><br />
					<span class='desctext'>{$this->lang->words['cf_f_max_info']}</span>
				</td>
		 		<td>
		 			<input type='text' name='pf_max_input' value="{$data['pf_max_input']}" size='30' class='textinput'>
		 		</td>
		 	</tr>
			<tr>
		 		<td>
					<label>{$this->lang->words['cf_f_order']}</label><br />
					<span class='desctext'>{$this->lang->words['cf_f_order_info']}</span>
				</td>
		 		<td>
		 			<input type='text' name='pf_position' value="{$data['pf_position']}" size='30' class='textinput'>
		 		</td>
		 	</tr>
			<tr>
		 		<td>
					<label>{$this->lang->words['cf_f_form']}</label><br />
					<span class='desctext'>{$this->lang->words['cf_f_form_info']}</span>
				</td>
		 		<td>
		 			<input type='text' name='pf_input_format' value="{$data['pf_input_format']}" size='30' class='textinput'>
		 		</td>
		 	</tr>
			<tr>
		 		<td>
					<label>{$this->lang->words['cf_f_option']}</label><br />
					<span class='desctext'>{$this->lang->words['cf_f_option_info']}</span>
				</td>
		 		<td>
		 			<textarea name='pf_content' cols='45' rows='5' wrap='soft' id='pf_content' class='multitext'>{$data['pf_content']}</textarea>
		 		</td>
		 	</tr>
			<tr>
		 		<td>
					<label>{$this->lang->words['cf_f_reg']}</label><br />
					<span class='desctext'>{$this->lang->words['cf_f_reg_info']}</span>
				</td>
		 		<td>
		 			{$data['pf_show_on_reg']}
		 		</td>
		 	</tr>
			<tr>
		 		<td>
					<label>{$this->lang->words['cf_f_must']}</label><br />
					<span class='desctext'>{$this->lang->words['cf_f_must_info']}</span>
				</td>
		 		<td>
		 			{$data['pf_not_null']}
		 		</td>
		 	</tr>
			<tr>
		 		<td>
					<label>{$this->lang->words['cf_f_edit']}</label><br />
					<span class='desctext'>{$this->lang->words['cf_f_edit_info']}</span>
				</td>
		 		<td>
		 			{$data['pf_member_edit']}
		 		</td>
		 	</tr>
			<tr>
		 		<td>
					<label>{$this->lang->words['cf_f_priv']}</label><br />
					<span class='desctext'>{$this->lang->words['cf_f_priv_info']}</span>
				</td>
		 		<td>
		 			{$data['pf_member_hide']}
		 		</td>
		 	</tr>
			<tr>
		 		<td>
					<label>{$this->lang->words['cf_f_admin']}</label><br />
					<span class='desctext'>{$this->lang->words['cf_f_admin_info']}</span>
				</td>
		 		<td>
		 			{$data['pf_admin_only']}
		 		</td>
		 	</tr>
			<tr>
		 		<td>
					<label>{$this->lang->words['cf_f_view']}</label><br />
					<span class='desctext'>{$this->lang->words['cf_f_view_info']}</span>
				</td>
		 		<td>
		 			<textarea name='pf_topic_format' cols='60' rows='5' wrap='soft' id='pf_topic_format' class='multitext'>{$data['pf_topic_format']}</textarea>
		 		</td>
		 	</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value=' {$button} ' class='realbutton' accesskey='s' />
		</div>
	</div>
</form>
	
HTML;

return $IPBHTML;		
}

/**
 * Delete field confirmation
 *
 * @access	public
 * @param	int			ID
 * @param	string		Title
 * @return	string		HTML
 */
public function customProfileFieldDelete( $id, $title )
{
$IPBHTML = "";

$IPBHTML .= <<<HTML
<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm' id='theAdminForm'>
	<input type='hidden' name='do' value='dodelete' />
	<input type='hidden' name='id' value='{$id}' />
	<input type='hidden' name='_admin_auth_key' value='' />
	
	<div class='tableborder'>
		<div class='tableheaderalt'>{$this->lang->words['cf_removeconf']}</div>
		
		<table width='100%' cellspacing='0' cellpadding='5' align='center' border='0'>
			<tr>
				<td class='tablerow1'  width='40%'  valign='middle'>{$this->lang->words['cf_removeto']}</td>
				<td class='tablerow2'  width='60%'  valign='middle'><b>{$title}</b></td>
			</tr>

			<tr>
				<td align='center' class='tablesubheader' colspan='2' ><input type='submit' value='{$this->lang->words['cf_deletebutton']}' class='realbutton' accesskey='s'></td>
			</tr>
		</table>
	</div>
</form>
HTML;

return $IPBHTML;		
}

}