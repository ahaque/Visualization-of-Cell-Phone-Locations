<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Manage help files skin file
 * Last Updated: $Date: 2009-06-04 21:02:40 -0400 (Thu, 04 Jun 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Core
 * @link		http://www.
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 4726 $
 */
 
class cp_skin_help_files extends output
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
 * Form to add/edit a help file
 *
 * @access	public
 * @param	string		Action
 * @param	int			ID
 * @param	array 		Form elements
 * @param	string		Button text
 * @return	string		HTML
 */
public function helpFileForm( $do, $id, $form, $button )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['h_title']}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm' id='postingform'>
	<input type='hidden' name='do' value='{$do}' />
	<input type='hidden' name='id' value='{$id}' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$button}</h3>

		<ul class='acp-form alternate_rows'>
			<li>
				<label>{$this->lang->words['h_filetitle']}</label>
				{$form['title']}
			</li>

			<li>
				<label>{$this->lang->words['h_filedesc']}</label>
				{$form['description']}
			</li>
			
			<li>
				<label>{$this->lang->words['h_fileapp']}</label>
				{$form['appDir']}
			</li>
		
			<li>
				<label>{$this->lang->words['h_filetext']}</label>
				<div class='clear'><textarea id='editor_main' name='editor_main' style='width: 100%; height: 500px;'>{$form['text']}</textarea></div>
			</li>
		</ul>
		<div class='acp-actionbar'>
			<div class='centeraction'>
				<input type='submit' value='{$button}' class='button primary' accesskey='s'>
			</div>
		</div>	
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * List the current help files
 *
 * @access	public
 * @param	array 		Rows
 * @return	string		HTML
 */
public function helpFilesList( $rows )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['h_title']}</h2>
	<ul class='context_menu'>
		<li>
			<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=new' title='{$this->lang->words['h_addnew']}'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/help_add.png' alt='' />
				{$this->lang->words['h_addnew']}
			</a>
		</li>
		<li>
			<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=exportXml' title='{$this->lang->words['h_addnew']}'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/export.png' alt='' />
				{$this->lang->words['h_export']}
			</a>
		</li>
		<li>
			<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=importXml' title='{$this->lang->words['h_addnew']}'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/import.png' alt='' />
				{$this->lang->words['h_import']}
			</a>
		</li>
	</ul>
</div>

<script type="text/javascript">
window.onload = function() {
	Sortable.create( 'sortable_handle', { revert: true, format: 'faq_([0-9]+)', onUpdate: dropItLikeItsHot, handle: 'draghandle' } );
};

dropItLikeItsHot = function( draggableObject, mouseObject )
{
	var options = {
					method : 'post',
					parameters : Sortable.serialize( 'sortable_handle', { tag: 'li', name: 'faq' } )
				};
 
	new Ajax.Request( "{$this->settings['base_url']}&{$this->form_code_js}&do=doreorder&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' ), options );

	return false;
};

</script>

	<div class='acp-box'>
		<h3>{$this->lang->words['h_current']}</h3>

		<ul id='sortable_handle' class='alternate_rows'>
HTML;

foreach( $rows as $r )
{
$IPBHTML .= <<<HTML
			<li id='faq_{$r['id']}'>
				<table style='width: 100%'>
					<tr>
						<td style='width: 3%'>
							<div class='draghandle'><img src='{$this->settings['skin_acp_url']}/_newimages/drag_icon.png' alt=''></div>
						</td>
						<td style='width: 93%'>
							<strong><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=edit&amp;id={$r['id']}'>{$r['title']}</a></strong><br />
							<span class='desctext'>{$r['description']}</span>
						</td>
						<td style='width: 4%'>
							<img class='ipbmenu' id="menu-{$r['id']}" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt='{$this->lang->words['a_options']}' />
							<ul class='acp-menu' id='menu-{$r['id']}_menucontent'>
								<li class='icon edit'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=edit&amp;id={$r['id']}'>{$this->lang->words['h_edit']}</a></li>
								<li class='icon delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}&amp;do=remove&amp;id={$r['id']}");'>{$this->lang->words['h_remove']}</a></li>
							</ul>
						</td>
					</tr>
				</table>
			</li>
HTML;
}

$IPBHTML .= <<<HTML
		</ul>

	</div>
	<br />
</form>
HTML;

//--endhtml--//
return $IPBHTML;


}

}