<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * ACP reputation skin file
 * Last Updated: $Date: 2009-04-27 16:12:12 -0400 (Mon, 27 Apr 2009) $
 *
 * @author 		$Author: josh $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Members
 * @link		http://www.
 * @since		20th February 2002
 * @version		$Revision: 4559 $
 *
 */
 
class cp_skin_reputation extends output
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
 * Reputation form
 *
 * @access	public
 * @param	int		ID
 * @param	string	Action
 * @param	string	Title
 * @param	array 	Form elements
 * @param	array 	Errors
 * @return	string	HTML
 */
public function reputationForm( $id, $do, $title, $form, $errors ) {
$IPBHTML = "";
//--starthtml--//

if( count( $errors ) )
{
$IPBHTML .= <<<HTML
<h2>{$this->lang->words['errors']}</h2>
<ul>
HTML;

	foreach( $errors as $err )
	{
$IPBHTML .= <<<HTML
	<li>$err</li>
HTML;
	}
	
$IPBHTML .= <<<HTML
</ul>
HTML;
}

$IPBHTML .= <<<HTML
<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
	<input type='hidden' name='do' value='{$do}'>
	<input type='hidden' name='id' value='{$id}'>
	
	<div class='acp-box'>
		<h3>{$title}</h3>
		<ul class='acp-form alternate_rows'>
			<li>
				<label>{$this->lang->words['rep_form_title']}<span class='desctext'>{$this->lang->words['rep_form_title_help']}</span></label>
				{$form['level_title']}
			</li>
			<li>
				<label>{$this->lang->words['rep_form_image']}<span class='desctext'>{$this->lang->words['rep_form_image_help']}{$this->settings['public_dir']}style_extra/reputation_icons/</span></label>
				{$form['level_image']}
			</li>
			<li>
				<label>{$this->lang->words['rep_form_points']}<span class='desctext'>{$this->lang->words['rep_form_points_help']}</span></label>
				{$form['level_points']}
			</li>
		</ul>
		
		<div class='acp-actionbar'>
			<div class='centeraction'>
				<input type='submit' value='Save Changes' class='button primary'/>
			</div>
		</div>			
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Reputation overview screen
 *
 * @access	public
 * @param	array 	Rep levels
 * @return	string	HTML
 */
public function reputationOverview( $levels=array() ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['rep_lvl_manage']}</h2>
	
	<ul class='context_menu'>
		<li><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=add_level_form'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/add.png' alt='' /> {$this->lang->words['rep_level_new']}</a></li>
	</ul>
</div>
<div class='acp-box'>
	<h3>{$this->lang->words['rep_lvl_manage']}</h3>
	
	<table class='alternate_rows' width='100%'>
		<tr>
			<th width='40%'>{$this->lang->words['rep_form_title']}</th>
			<th width='20%'>{$this->lang->words['rep_form_image']}</th>
			<th width='30%'>{$this->lang->words['rep_form_points']}</th>
			<th width='10%'>&nbsp;</th>
		</tr>
HTML;

if( is_array( $levels ) && count( $levels ) )
{
	foreach( $levels as $r )
	{
$IPBHTML .= <<<HTML
		<tr>
			<td>{$r['level_title']}</td>
			<td>{$r['level_image']}</td>
			<td>{$r['level_points']}</td>
			<td align='right'>
				<img class="ipbmenu" id="menu{$r['level_id']}" src="{$this->settings['skin_acp_url']}/_newimages/menu_open.png" alt="">
				<ul style="position: absolute; display: none; z-index: 9999;" class="acp-menu" id='menu{$r['level_id']}_menucontent'>
					<li style="z-index: 10000;" class='icon edit'><a style="z-index: 10000;" href='{$this->settings['base_url']}{$this->form_code}&amp;do=edit_level_form&amp;id={$r['level_id']}'>{$this->lang->words['edit']}</a></li>
					<li style="z-index: 10000;" class='icon delete'><a style="z-index: 10000;" href='#' onclick='return acp.confirmDelete( "{$this->settings['base_url']}{$this->form_code}&amp;do=delete_level&amp;id={$r['level_id']}" );'>{$this->lang->words['delete']}</a></li>
				</ul>
			</td>
		</tr>
HTML;
	}
}
else
{
$IPBHTML .= <<<HTML
		<tr>
			<td colspan='4'><em>{$this->lang->words['rep_no_levels']}</em></td>
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


}