<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Badwords skin file
 * Last Updated: $Date: 2009-05-26 18:02:08 -0400 (Tue, 26 May 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 4690 $
 */
 
class cp_skin_badwords extends output
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
 * Edit badwords
 *
 * @access	public
 * @param	int			ID
 * @param	array 		Form elements
 * @return	string		HTML
 */
public function badwordEditForm( $id, $form ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['bwl_title']}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm' id='theAdminForm'>
	<input type='hidden' name='do' value='badword_doedit' />
	<input type='hidden' name='id' value='{$id}' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />

<div class="acp-box">
	<h3>{$this->lang->words['bwl_edit_filter']}</h3>
	<ul class="acp-form alternate_rows">
		<li>
			<label>{$this->lang->words['bwl_before']}</label>
			{$form['before']}
		</li>
		<li>
			<label>{$this->lang->words['bwl_after']}</label>
			{$form['after']}
		</li>
		<li>
			<label>{$this->lang->words['bwl_method']}</label>
			{$form['match']}
		</li>
	</ul>
	<div class="acp-actionbar">
		<input type='submit' value='{$this->lang->words['bwl_edit_filter']}' class='button primary' accesskey='s'>
	</div>
</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}


/**
 * Badwords wrapper
 *
 * @access	public
 * @param	array 		Badword rows
 * @return	string		HTML
 */
public function badwordsWrapper( $rows ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['bwl_title']}</h2>
	<ul class='context_menu'>
		<li>
			<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=badword_export'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/exclamation_go.png' alt='' />
				{$this->lang->words['bwl_export']}
			</a>
		</li>
	</ul>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm'  id='theAdminForm'>
	<input type='hidden' name='do' value='badword_add' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />

<div class="acp-box">
	<h3>{$this->lang->words['bwl_current']}</h3>
	<table class="alternate_rows">
		<tr>
			<th width='40%'>{$this->lang->words['bwl_before']}</th>
			<th width='40%'>{$this->lang->words['bwl_after']}</th>
			<th width='15%'>{$this->lang->words['bwl_method']}</th>
			<th width='5%'>{$this->lang->words['bwl_options']}</th>
		</tr>
HTML;

foreach( $rows as $row )
{
$IPBHTML .= <<<HTML
			<tr>
				<td>{$row['type']}</td>
				<td>{$row['replace']}</td>
				<td>{$row['method']}</td>
				<td style='text-align: center'>
					<img class='ipbmenu' id="menu_{$row['wid']}" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt='{$this->lang->words['bwl_options']}' />
					<ul class='acp-menu' id='menu_{$row['wid']}_menucontent'>
						<li class='icon edit'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=badword_edit&id={$row['wid']}'>{$this->lang->words['bwl_filter_edit']}</a></li>
						<li class='icon delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=badword_remove&id={$row['wid']}");'>{$this->lang->words['bwl_filter_remove']}</a></li>
					</ul>
				</td>
			</tr>
HTML;
}

$IPBHTML .= <<<HTML
		</table>
	</div>
	<br />
	
<div class="acp-box">
	<h3>{$this->lang->words['bwl_filter_add']}</h3>
	<table class="alternate_rows">
		<tr>
			<th width="40%">{$this->lang->words['bwl_before']}</th>
			<th width="40%">{$this->lang->words['bwl_after']}</th>
			<th width="20%">{$this->lang->words['bwl_method']}</th>
		</tr>
		<tr>
			<td width="40%"><input name="before" value="" size="30" class="textinput" type="text"></td>
			<td width="40%"><input name="after" value="" size="30" class="textinput" type="text"></td>
			<td width="20%">
				<select name="match" class="dropdown">
					<option value="1">{$this->lang->words['bwl_exact']}</option>
					<option value="0">{$this->lang->words['bwl_loose']}</option>
				</select>
			</td>
		</tr>
	</table>
	<div class="acp-actionbar">
		<input value="{$this->lang->words['bwl_filter_add']}" class="button primary" accesskey="s" type="submit">
	</div>	
</div>
</form>

<br />

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='uploadform'  enctype='multipart/form-data' id='uploadform'>
	<input type='hidden' name='do' value='badword_import' />
	<input type='hidden' name='MAX_FILE_SIZE' value='10000000000' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
<div class="acp-box">
	<h3>{$this->lang->words['bwl_import']}</h3>
		<ul class="acp-form alternate_rows">
			<li>
				<label>{$this->lang->words['bwl_import_upload']}<span class="desctext">{$this->lang->words['bwl_import_info']}</span></label>
				<input class='textinput' type='file' size='30' name='FILE_UPLOAD'>
			</li>
		</ul>
	<div class="acp-actionbar">
		<input type='submit' value='{$this->lang->words['bwl_import']}' class='button primary' accesskey='s'>
	</div>
</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}


}