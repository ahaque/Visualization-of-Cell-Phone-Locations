<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Multimods skin functions
 * Last Updated: $LastChangedDate: 2009-04-27 12:22:53 -0400 (Mon, 27 Apr 2009) $
 *
 * @author 		$Author: rikki $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Forums
 * @link		http://www.
 * @since		14th May 2003
 * @version		$Rev: 4557 $
 */
 
class cp_skin_multimods extends output
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
 * Form to add/edit multimods
 *
 * @access	public
 * @param	integer	MM ID
 * @param	string	Action
 * @param	string	Description
 * @param	array 	Form fields
 * @param	string	Button text
 * @return	string	HTML
 */
public function multiModerationForm( $id, $do, $description, $form, $button ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['mm_title']}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm' id='theAdminForm'>
	<input type='hidden' name='id' value='{$id}' />
	<input type='hidden' name='do' value='{$do}' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->_admin_auth_key}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['mm_title']}</h3>

		<ul class="acp-form alternate_rows">
			<li><label class='head'>{$description}</label>
			<li>
				<label>{$this->lang->words['mm_titlefor']}</label>
				{$form['mm_title']}
			</li>

			<li>
				<label>{$this->lang->words['mm_activein']}<span class='desctext'>{$this->lang->words['mm_activein_desc']}</span></label>				
				{$form['forums']}
			</tr>
			
			<li>
				<label class='head'>{$this->lang->words['mm_modoptions']}</label>				
			</li>

			<li>
				<label>{$this->lang->words['mm_start']}</label>
 				{$form['topic_title_st']}
			</li>

			<li>
				<label>{$this->lang->words['mm_end']}</label>
				{$form['topic_title_end']}
			</li>

			<li>
				<label>{$this->lang->words['mm_state']}</label>
				{$form['topic_state']}
			</li>

			<li>
				<label>{$this->lang->words['mm_pinned']}</label>
				{$form['topic_pin']}
			</li>

			<li>
				<label>{$this->lang->words['mm_approved']}</label>
				{$form['topic_approve']}
			</li>

			<li>
				<label>{$this->lang->words['mm_move']}</label>
				{$form['topic_move']}
			</li>
			
			<li>
				<label>{$this->lang->words['mm_link']}</label>
				{$form['topic_move_link']}
			</li>
			
			<li><label class='head'>{$this->lang->words['mm_postoptions']}</label></li>

			<li>
				<label>{$this->lang->words['mm_addreply']}<span class='desctext'>{$this->lang->words['mm_addreply_desc']}</span></label>				
				{$form['topic_reply']}<br />
				{$form['topic_reply_content']}			
			</li>	
			
			<li>
				<label>{$this->lang->words['mm_postcount']}</label>
				{$form['topic_reply_postcount']}
			</li>
		</table>
		
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
 * Show multimod overview page
 *
 * @access	public
 * @param	array 	MM Rows
 * @return	string	HTML
 */
public function multiModerationOverview( $rows ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['mm_title']}</h2>
	<ul class='context_menu'>
		<li>
			<a href='{$this->settings['base_url']}{$this->form_code}do=new' title='{$this->lang->words['mm_addnew']}'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/lightning_add.png' alt='' />
				{$this->lang->words['mm_addnew']}
			</a>
		</li>
	</ul>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['mm_current']}</h3>

	<table class='alternate_rows' width='100%'>
		<tr>
			<th width='95%'>{$this->lang->words['mm_wordtitle']}</th>
			<th width='5%'>{$this->lang->words['a_options']}</th>
		</tr>
HTML;

if( ! count( $rows ) )
{
$IPBHTML .= <<<HTML
		<tr><td colspan='3'><center>{$this->lang->words['mm_none']}</center></td></tr>
HTML;
}
else
{
	foreach( $rows as $r )
	{
$IPBHTML .= <<<HTML
		<tr>
			<td><strong>{$r['mm_title']}</strong></td>
			<td>
				<img class='ipbmenu' id="menu{$r['mm_id']}" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt='{$this->lang->words['a_options']}' />
				<ul class='acp-menu' id='menu{$r['mm_id']}_menucontent'>
					<li class='icon edit'><a href='{$this->settings['base_url']}{$this->form_code}do=edit&amp;id={$r['mm_id']}'>{$this->lang->words['mm_wordedit']}</a></li>
					<li class='icon delete'><a href='{$this->settings['base_url']}{$this->form_code}do=delete&amp;id={$r['mm_id']}'>{$this->lang->words['mm_remove']}</a></li>
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

}