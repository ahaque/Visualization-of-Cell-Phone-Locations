<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Emoticon manager skin file
 * Last Updated: $Date: 2009-07-08 21:23:44 -0400 (Wed, 08 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Core
 * @link		http://www.
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 4856 $
 */
 
class cp_skin_emoticons extends output
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
 * Emoticon pack splash page
 *
 * @access	public
 * @param	array 		Form elements
 * @return	string		HTML
 */
public function emoticonsPackSplash( $form )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['emo_title']}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm' id='theAdminForm'>
	<input type='hidden' name='do' value='emo_packexport' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->_admin_auth_key}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['emote_export']}</h3>

		<ul class='acp-form alternate_rows'>
			<li>
				<label>{$this->lang->words['emote_export_which']}<span class='desctext'>{$this->lang->words['emote_xml_pack']}</span></label>
				{$form['emo_set']}
			</li>
		</ul>
		
		<div class='acp-actionbar'>
			<div class='centeraction'>
				<input type='submit' value='{$this->lang->words['emote_export_button']}' class='button primary' accesskey='s'>
			</div>
		</div>
	</div>
</form><br />

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='uploadform'  enctype='multipart/form-data' id='uploadform'>
	<input type='hidden' name='do' value='emo_packimport' />
	<input type='hidden' name='MAX_FILE_SIZE' value='10000000000' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->_admin_auth_key}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['emote_import']}</h3>

		<table width='100%' class='alternate_rows'>
			<tr>
				<td width='60%' valign='middle'>
					{$this->lang->words['emote_import_which']}<br />
					<span class='desctext'>{$this->lang->words['emote_xml_export']}</span>					
				</td>
				<td width='40%' valign='middle'>{$form['emo_set']}</td>
			</tr>
			
			<tr>
				<td  width='60%' valign='middle'>
					{$this->lang->words['emote_import_newgroup']}<br />
					<span class='desctext'>{$this->lang->words['emote_import_name']}</span>					
				</td>
				<td width='40%' valign='middle'>{$form['new_emo_set']}</td>
			</tr>
			<tr>
				<td width='60%' valign='middle'>
					{$this->lang->words['emote_import_over']}<br />
					<span class='desctext'>{$this->lang->words['emote_import_replace']}</span>					
				</td>
				<td width='40%' valign='middle'>{$form['overwrite']}</td>
			</tr>
			<tr>
				<td width='60%' valign='middle'>{$this->lang->words['emote_import_upload']}<br /><span class='desctext'>{$this->lang->words['emote_import_browse']}</span></td>
				<td width='40%' valign='middle'><input class='textinput' type='file' size='30' name='FILE_UPLOAD'></td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<div class='centeraction'>
				<input type='submit' value='{$this->lang->words['emote_import_button']}' class='button primary' accesskey='s'>
			</div>
		</div>		
	</div>
</form>
HTML;

return $IPBHTML;
}

/**
 * Show the splash screen for the logs
 *
 * @access	public
 * @param	array 		Db records
 * @param	array 		File records
 * @param	string		Width for table cells
 * @param	int			Number of emoticons per row
 * @return	string		HTML
 */
public function emoticonsDirectoryManagement( $db_rows, $file_rows, $td_width, $per_row )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['emo_control']}</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['emote_assigned']}'{$this->request['id']}'</h3>
	
	<form action='{$this->settings['base_url']}{$this->form_code}do=emo_doedit&id={$this->request['id']}' method='post'>
		<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->_admin_auth_key}' />
		
		<table class='alternate_rows' width='100%'>
			<tr align='center'>
HTML;

/* Loop through the database emoticons */
$count = 0;

foreach( $db_rows as $r )
{
	$count++;
	
$IPBHTML .= <<<HTML
				<td width='{$td_width}%' align='center'>
					<fieldset>
						<legend><strong>{$r['image']}</strong></legend>
						
						<input type='hidden' name='emo_id_{$r['id']}' value='{$r['id']}' />					
						<img src='../public/style_emoticons/{$this->request['id']}/{$r['image']}' border='0' />&nbsp;&nbsp;&nbsp;&nbsp;
HTML;
			if( $this->request['id'] == 'default' )
			{
$IPBHTML .= <<<HTML
						<a href='{$this->settings['base_url']}{$this->form_code}do=emo_remove&eid={$r['id']}&id={$this->request['id']}' title='{$this->lang->words['emote_delete_title']}'>
							<img src='{$this->settings['skin_acp_url']}/images/emo_delete.gif' border='0' alt='{$this->lang->words['emote_delete_title']}' />						
						</a>
						<br />
						<input type='textinput' class='realbutton' size='10' name='emo_type_{$r['id']}' value='{$r['typed']}' />
HTML;
			}
			else
			{
$IPBHTML .= <<<HTML
						<br /><br /><span style='font-family:Verdana,Arial;font-size:10px;font-weight:bold;'>{$r['typed']}</span>
HTML;
			}

$IPBHTML .= <<<HTML
						<br /><br />{$this->lang->words['emote_clickable']} <input type='checkbox'  name='emo_click_{$r['id']}' value='1' {$r['_click']} />
					</fieldset>
				</td>
HTML;

	if( $count == $per_row )
	{
$IPBHTML .= <<<HTML
			</tr>
			
			<tr align='center'>
HTML;
		$count = 0;
	}

}

/* Finish off a row in progress */
if( $count > 0 and $count != $per_row )
{
	for( $i = $count ; $i < $per_row ; ++$i )
	{
$IPBHTML .= <<<HTML
				<td>&nbsp;</td>
HTML;
	}
	
$IPBHTML .= <<<HTML
			</tr>
HTML;

}

$IPBHTML .= <<<HTML
		</table>
		<div class='acp-actionbar'>
			<div class='centeraction'>
				<input type='submit' class='button primary' value='{$this->lang->words['emote_update_button']}' />
			</div>
		</div>
	</form>
</div><br />
HTML;

/* Display unassigned emoticons */
if( is_array( $file_rows ) && count( $file_rows ) )
{
$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$this->lang->words['emote_unassigned']}'{$this->request['id']}'</h3>
	
	<form action='{$this->settings['base_url']}{$this->form_code}do=emo_doadd&id={$this->request['id']}' method='post'>
		<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->_admin_auth_key}' />
		
		<table class='alternate_rows' width='100%'>
			<tr>
HTML;
	
	/* Loop through the unassigned emoticons */
	$master_count = 0;
	$count        = 0;

	foreach( $file_rows as $r )
	{
		$master_count++;
		$count++;
		
$IPBHTML .= <<<HTML
				<td width='{$td_width}%' align='center'>
					<fieldset>
						<legend><strong>{$r['image']}</strong></legend>
						<img src='../public/style_emoticons/{$this->request['id']}/{$r['image']}' border='0' />&nbsp;&nbsp;
						{$this->lang->words['emote_add']}<input name='emo_add_{$master_count}' type='checkbox' value='1' /><br />
						{$this->lang->words['emote_text']}<input type='textinput' class='realbutton' size='10' name='emo_type_{$master_count}' value='{$r['poss_name']}' /><br /><br />
						{$this->lang->words['emote_clickable']} <input type='checkbox' name='emo_click_{$master_count}' value='1' />
						<input type='hidden' name='emo_image_{$master_count}' value='{$r['image']}' />
					</fieldset>
				</td>
HTML;

		if( $count == $per_row )
		{
$IPBHTML .= <<<HTML
			</tr>
			
			<tr align='center'>
HTML;
		$count = 0;
		}
	}
	
	/* Finish off a row in progress */
	if( $count > 0 and $count != $per_row )
	{
		for( $i = $count ; $i < $per_row ; ++$i )
		{
$IPBHTML .= <<<HTML
				<td>&nbsp;</td>
HTML;
		}
		
$IPBHTML .= <<<HTML
			</tr>
HTML;
	
	}
	
$IPBHTML .= <<<HTML
		</table>
			
		<div class='acp-actionbar'>
			<div class='centeraction'>
				<input type='submit' class='button primary' value='{$this->lang->words['emote_add_button']}' />&nbsp;&nbsp;
				<input type='submit' name='addall' class='button primary' value='{$this->lang->words['emote_addall_button']}' />	
			</div>
		</div>
	</form>			
</div>
HTML;

}


return $IPBHTML;
}

/**
 * Overview of emoticon packs
 *
 * @access	public
 * @param	array 		Records
 * @return	string		HTML
 */
public function emoticonsOverview( $rows )
{
	
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<script type="text/javascript" src="{$this->settings['js_main_url']}acp.emoticons.js"></script>
<script type='text/javascript'>
	
	ipb.templates['emo_manage'] = new Template( "<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>" +
												"<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->_admin_auth_key}' />" +
												"<input type='hidden' name='do' value='#{form_do}' />" +
												"<input type='hidden' name='id' value='#{form_id}' />" +
												"<div class='acp-box'><h3>{$this->lang->words['emoticon_folder']}</h3><ul class='acp-form'><li><label for='name_#{form_id}'>{$this->lang->words['emoticon_folder_name']}</label><input type='text' size='30' class='input_text' id='name_#{form_id}' value='#{folder_name}' name='emoset'></li></ul><div class='acp-actionbar'><input type='submit' value='#{form_value}' class='realbutton' id='save_folder_#{id}' /></div></div>" + 
												"</form>" );
	
	ipb.lang['emoticons'] = [];								
	ipb.lang['emoticons']['add'] = "{$this->lang->words['emote_addfolder']}";
	ipb.lang['emoticons']['edit'] = "{$this->lang->words['emote_editfolder']}";
	
</script>

<div class='section_title'>
	<h2>{$this->lang->words['emo_control']}</h2>
	<ul class='context_menu'>
		<li><a href='#' id='folder_edit_0' return false;'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/table_add.png' alt='' /> {$this->lang->words['emote_createnew']}</a></li>
	</ul>
	<script type='text/javascript'>
		$('folder_edit_0').observe('click', acp.emoticons.folder.bindAsEventListener( this, 0 ) );
	</script>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='uploadform'  enctype='multipart/form-data' id='uploadform'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->_admin_auth_key}' />
	<input type='hidden' name='do' value='emo_upload'>
	<input type='hidden' name='MAX_FILE_SIZE' value='10000000000'>
	<input type='hidden' name='dir_default' value='1'>

	<div class='acp-box'>
		<h3>{$this->lang->words['emote_current']}</h3>
		
		<table width='100%' class='alternate_rows'>
			<tr>
				<th width='50%' align='center'>{$this->lang->words['emote_emofolder']}</th>
				<th width='5%' align='center'>{$this->lang->words['emote_upload']}</th>
				<th width='20%' align='center'>{$this->lang->words['emote_num_disk']}</th>
				<th width='20%' align='center'>{$this->lang->words['emote_num_group']}</th>
				<th width='5%' align='center'>{$this->lang->words['emote_options']}</th>
			</tr>
HTML;

foreach( $rows as $data )
{
$IPBHTML .= <<<HTML
			<tr>
	 			<td valign='middle'>
					<div style='width:auto;float:right;'><img src='{$this->settings['skin_acp_url']}/images/{$data['icon']}' title='{$data['title']}' alt='{$data['icon']}' /></div>
					{$data['line_image']}<img src='{$this->settings['skin_acp_url']}/images/emoticon_folder.gif' border='0'>&nbsp;
					<a href='{$this->settings['base_url']}{$this->form_code}do=emo_manage&amp;id={$data['dir']}' title='{$this->lang->words['emote_manageset']}'><b>{$data['dir']}</b></a>
				</td>

				<td valign='middle'><center>{$data['checkbox']}</center></td>
				<td valign='middle'><center>{$data['count']}</center></td>
				<td valign='middle'><center>{$data['dir_count']}</td>
				<td align='right'>
					<img class="ipbmenu" id="menu{$data['dir']}" src="{$this->settings['skin_acp_url']}/_newimages/menu_open.png" alt="">
					<ul style="position: absolute; display: none; z-index: 9999;" class="acp-menu" id='menu{$data['dir']}_menucontent'>
						<li style="z-index: 10000;" class='icon view'><a style="z-index: 10000;" href='{$this->settings['base_url']}{$this->form_code_js}do=emo_manage&amp;id={$data['dir']}'>{$data['link_text']}</a></li>
HTML;

if( $data['dir'] != 'default' OR IN_DEV == 1 )
{
$IPBHTML .= <<<HTML
						<li style="z-index: 10000;" class='icon edit'>
							<a style="z-index: 10000;" href='#' id="folder_edit_{$data['dir']}">{$this->lang->words['emote_editfolder']}</a>
							<script type='text/javascript'>
								$("folder_edit_{$data['dir']}").observe('click', acp.emoticons.folder.bindAsEventListener( this, "{$data['dir']}" ) );
							</script>
						</li>
						<li style="z-index: 10000;" class='icon delete'><a style="z-index: 10000;" href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code_js}do=emo_setremove&amp;id={$data['dir']}");'>{$this->lang->words['emote_deletefolder']}</a></li>
HTML;
}
else
{
$IPBHTML .= <<<HTML
						<li style="z-index: 10000;" class='icon delete'>{$this->lang->words['emote_dontdeletedef']}</li>
HTML;
}

$IPBHTML .= <<<HTML
					</ul>
				</td>
			</tr>
HTML;
}

$IPBHTML .= <<<HTML
		</table>
	</div><br />
HTML;

if( SAFE_MODE_ON )
{
$IPBHTML .= <<<HTML
</form>
HTML;
}
else
{
$IPBHTML .= <<<HTML
	<div class='acp-box'>
		<h3>{$this->lang->words['emote_uploademos']}</h3>
		
		<table width='100%' class='alternate_rows'>
			<tr>
				<td width='50%' align='center'><input type='file' value='' class='realbutton' name='upload_1' size='30' /></td>
				<td width='50%' align='center'><input type='file' class='realbutton' name='upload_2' size='30' /></td>
			</tr>
			<tr>
				<td width='50%' align='center'><input type='file' class='realbutton' name='upload_3' size='30' /></td>
				<td width='50%' align='center'><input type='file' class='realbutton' name='upload_4' size='30' /></td>
			</tr>
		</table>
		
		<div class='acp-actionbar'>
			<div class='centeraction'>
				<input type='submit' value='{$this->lang->words['emote_uploadtofolders']}' class='button primary' />
			</div>
		</div>
	</div>
</form>
HTML;
}


return $IPBHTML;
}

}