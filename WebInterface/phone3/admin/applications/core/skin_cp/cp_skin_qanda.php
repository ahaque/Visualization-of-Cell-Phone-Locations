<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Question and answer skin file
 * Last Updated: $Date: 2009-04-02 11:17:56 -0400 (Thu, 02 Apr 2009) $
 *
 * @author 		$Author: matt $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 4393 $
 */
 
class cp_skin_qanda extends output
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
 * Show the q&a form
 *
 * @access	public
 * @param	string		Action
 * @param	int			ID
 * @param	array 		Form elements
 * @param	string		Button text
 * @return	string		HTML
 */
public function showForm( $do, $id, $form, $button )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['qa_help_title']}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm' id='postingform'>
	<input type='hidden' name='do' value='{$do}' />
	<input type='hidden' name='id' value='{$id}' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$button}</h3>
		<ul class='acp-form alternate_rows'>
			<li>
				<label>{$this->lang->words['qa_form_question']}</label>
				{$form['question']}
			</li>
			<li>
				<label>{$this->lang->words['qa_form_answers']}</label>
				{$form['answers']}
			</li>
		</ul>
		<div class='acp-actionbar'>
			<input type='submit' value='{$button}' class='realbutton' accesskey='s'>
		</div>		
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show the overview page
 *
 * @access	public
 * @param	array 		Rows
 * @return	string		HTML
 */
public function overview( $rows )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['qa_help_title']}</h2>
	<ul class='context_menu'>
		<li>
			<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=new' title='{$this->lang->words['qa_addlink']}'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/help_add.png' alt='' />
				{$this->lang->words['qa_addlink']}
			</a>
		</li>
	</ul>
</div>
HTML;

if ( count( $rows ) AND ! $this->settings['registration_qanda'] )
{
	$this->lang->words['qa_not_on_desc'] = sprintf( $this->lang->words['qa_not_on_desc'], $this->settings['base_url'] . $this->form_code .'&amp;do=switchOn' );
	
	$IPBHTML .= $this->registry->output->global_template->warning_box( $this->lang->words['qa_not_on_title'], $this->lang->words['qa_not_on_desc'] ) . "<br />";
}

$IPBHTML .= <<<HTML
	<div class='acp-box'>
		<h3>{$this->lang->words['qa_current']}</h3>
		<table class='alternate_rows'>
HTML;

if( count($rows) )
{		
$IPBHTML .= <<<HTML
			<tr>
				<th width='95%'>{$this->lang->words['qa_form_question']}</th>
				<th width='5%'>{$this->lang->words['qa_options']}</th>
			</tr>
HTML;
foreach( $rows as $r )
{
$IPBHTML .= <<<HTML
			<tr>
				<td>{$r['qa_question']}</td>
				<td>
					<img class='ipbmenu' id="menu-{$r['qa_id']}" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt='{$this->lang->words['a_options']}' />
					<ul class='acp-menu' id='menu-{$r['qa_id']}_menucontent'>
						<li class='icon edit'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=edit&amp;id={$r['qa_id']}'>{$this->lang->words['qa_edit']}</a></li>
						<li class='icon delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}&amp;do=remove&amp;id={$r['qa_id']}");'>{$this->lang->words['qa_delete']}</a></li>
					</ul>
				</td>
			</tr>
HTML;
}
}
else
{
	$IPBHTML .= <<<HTML
	<tr><td>{$this->lang->words['qa_none']}</td></tr>
HTML;
}

$IPBHTML .= <<<HTML
		</table>

	</div>
	<br />

HTML;

//--endhtml--//
return $IPBHTML;


}

}