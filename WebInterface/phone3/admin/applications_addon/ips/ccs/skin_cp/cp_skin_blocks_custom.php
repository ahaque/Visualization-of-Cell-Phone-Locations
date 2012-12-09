<?php
/**
 * Invision Power Services
 * Custom block type
 * Last Updated: $Date: 2009-08-11 10:01:08 -0400 (Tue, 11 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	IP.CCS
 * @link		http://www.
 * @since		1st March 2009
 * @version		$Revision: 42 $
 */
 
class cp_skin_blocks_custom extends output
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
 * Wizard: Step 2 (Custom)
 *
 * @access	public
 * @param	array 		Session data
 * @return	string		HTML
 */
public function custom__wizard_2( $session )
{
$IPBHTML = "";
//--starthtml--//

$sessionId	= $session['wizard_id'];
$customType	= $this->registry->output->formDropdown( 'custom_type', array( array( 'basic', $this->lang->words['block_custom_basic'] ), array( 'html', $this->lang->words['block_custom_html'] ), array( 'php', $this->lang->words['block_custom_php'] ) ), $session['config_data']['type'] );

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2><img src='{$this->settings['skin_acp_url']}/_newimages/ccs/wizard.png' alt='{$this->lang->words['wizard_alt']}' /> {$this->lang->words['gbl__step_2']}</h2>
	<ul class='context_menu'>
		<!--<li>
			<a href='{$this->settings['base_url']}&amp;module=blocks&amp;section=blocks' title='{$this->lang->words['pause_session']}'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/plugin.png' alt='{$this->lang->words['icon']}' />
				{$this->lang->words['button__pause']}
			</a>
		</li>-->
		<li>
			<a href='{$this->settings['base_url']}&amp;module=blocks&amp;section=blocks&amp;do=delete&amp;type=wizard&amp;block={$sessionId}' title='{$this->lang->words['cancel_block_session']}'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/delete.png' alt='{$this->lang->words['icon']}' />
				{$this->lang->words['button__cancel']}
			</a>
		</li>
	</ul>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=continue&amp;wizard_session={$sessionId}' method='post'>
<input type='hidden' name='step' value='2' />
<div class='acp-box'>
	<h3>{$this->lang->words['specify_custom_type']}</h3>
	<table width='100%' cellpadding='0' cellspacing='0' class='form_table alternate_rows double_pad'>
		<tr>
			<td style='width: 40%'>
				<strong>{$this->lang->words['custom_block_type']}</strong>
			</td>
			<td style='width: 60%'>
				{$customType}
			</td>
		</tr>
	</table>
	<div class="acp-actionbar">
		<input type='submit' value=' {$this->lang->words['button__continue']} ' class="button primary" />
	</div>
</div>	
</form>
<br />
<div class='acp-box'>
	<h3>{$this->lang->words['custom_block_type_desc']}</h3>
	<ul class='acp-form alternate_rows'>
		<li>
			<strong>{$this->lang->words['block_custom_basic']}:</strong> {$this->lang->words['block_custom_basic_desc']}
		</li>
		
		<li>
			<strong>{$this->lang->words['block_custom_html']}:</strong> {$this->lang->words['block_custom_html_desc']}
		</li>
		
		<li>
			<strong>{$this->lang->words['block_custom_php']}:</strong> {$this->lang->words['block_custom_php_desc']}
		</li>
	</ul>
</div>
HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Wizard: Step 3 (Custom)
 *
 * @access	public
 * @param	array 		Session data
 * @param	array 		Categories
 * @return	string		HTML
 */
public function custom__wizard_3( $session, $categories )
{
$IPBHTML = "";
//--starthtml--//

$sessionId		= $session['wizard_id'];
$_hide			= $this->registry->output->formYesNo( 'hide_empty', $session['config_data']['hide_empty'] );
$_categories	= '';

if( count($categories) )
{
	$_categories	= $this->registry->output->formDropdown( 'category', $categories, $session['config_data']['category'] );
}

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2><img src='{$this->settings['skin_acp_url']}/_newimages/ccs/wizard.png' alt='{$this->lang->words['wizard_alt']}' /> {$this->lang->words['gbl__step_3']}</h2>
	<ul class='context_menu'>
		<!--<li>
			<a href='{$this->settings['base_url']}&amp;module=blocks&amp;section=blocks' title='{$this->lang->words['pause_session']}'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/plugin.png' alt='{$this->lang->words['icon']}' />
				{$this->lang->words['button__pause']}
			</a>
		</li>-->
		<li>
			<a href='{$this->settings['base_url']}&amp;module=blocks&amp;section=blocks&amp;do=delete&amp;type=wizard&amp;block={$sessionId}' title='{$this->lang->words['cancel_block_session']}'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/delete.png' alt='{$this->lang->words['icon']}' />
				{$this->lang->words['button__cancel']}
			</a>
		</li>
	</ul>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=continue&amp;wizard_session={$sessionId}' method='post'>
<input type='hidden' name='step' value='3' />
<div class='acp-box'>
	<h3>{$this->lang->words['block_title_desc']}</h3>
	<table width='100%' cellpadding='0' cellspacing='0' class='form_table alternate_rows double_pad'>
		<tr>
			<td style='width: 40%'>
				<strong>{$this->lang->words['form__block_title']}</strong>
			</td>
			<td style='width: 60%'>
				<input type='text' class='text' name='custom_title' value='{$session['config_data']['title']}' />
			</td>
		</tr>
		<tr>
			<td>
				<strong>{$this->lang->words['form__block_key']}</strong><br />
				<span class='desctext'>{$this->lang->words['form__block_key_desc']}</span>
			</td>
			<td>
				<input type='text' class='text' name='custom_key' value='{$session['config_data']['key']}' />
			</td>
		</tr>
		<tr>
			<td>
				<strong>{$this->lang->words['form__block_desc']}</strong>
			</td>
			<td>
				<input type='text' class='text' name='custom_description' value='{$session['config_data']['description']}' />
			</td>
		</tr>
HTML;

if( $_categories )
{
	$IPBHTML .= <<<HTML
		<tr>
			<td>
				<strong>{$this->lang->words['select_category']}</strong>
			</td>
			<td>
				{$_categories}
			</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
		<tr>
			<td>
				<strong>{$this->lang->words['hide_block_no_content']}</strong>
			</td>
			<td>
				{$_hide}
			</td>
		</tr>
	</table>
	<div class="acp-actionbar">
		<input type='submit' value=' {$this->lang->words['button__continue']} ' class="button primary" />
	</div>
</div>	
</form>
HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Wizard: Step 4 (Custom)
 *
 * @access	public
 * @param	array 		Session data
 * @return	string		HTML
 */
public function custom__wizard_4( $session )
{
$IPBHTML = "";
//--starthtml--//

$sessionId	= $session['wizard_id'];

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2><img src='{$this->settings['skin_acp_url']}/_newimages/ccs/wizard.png' alt='{$this->lang->words['wizard_alt']}' /> {$this->lang->words['gbl__step_4']}</h2>
	<ul class='context_menu'>
		<!--<li>
			<a href='{$this->settings['base_url']}&amp;module=blocks&amp;section=blocks' title='{$this->lang->words['pause_session']}'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/plugin.png' alt='{$this->lang->words['icon']}' />
				{$this->lang->words['button__pause']}
			</a>
		</li>-->
		<li>
			<a href='{$this->settings['base_url']}&amp;module=blocks&amp;section=blocks&amp;do=delete&amp;type=wizard&amp;block={$sessionId}' title='{$this->lang->words['cancel_block_session']}'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/delete.png' alt='{$this->lang->words['icon']}' />
				{$this->lang->words['button__cancel']}
			</a>
		</li>
	</ul>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=continue&amp;wizard_session={$sessionId}' method='post'>
<input type='hidden' name='step' value='4' />
<div class='acp-box'>
	<h3>{$this->lang->words['specify_caching_op']}</h3>
	<table width='100%' cellpadding='0' cellspacing='0' class='form_table alternate_rows double_pad'>
		<tr>
			<td style='width: 40%'>
				<strong>{$this->lang->words['cache_ttl_opt']}</strong><br /><span class='desctext'>{$this->lang->words['cache_ttl_desc']}</span>
			</td>
			<td style='width: 60%'>
				<input type='text' class='text' name='custom_cache_ttl' value='{$session['config_data']['cache_ttl']}' />
			</td>
		</tr>
	</table>
	<div class="acp-actionbar">
		<input type='submit' value=' {$this->lang->words['button__continue']} ' class="button primary" />
	</div>
</div>	
</form>
HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Wizard: Step 5 (Custom)
 *
 * @access	public
 * @param	array 		Session data
 * @param	string		Editor HTML
 * @return	string		HTML
 */
public function custom__wizard_5( $session, $editor_area )
{
$IPBHTML = "";
//--starthtml--//

$sessionId	= $session['wizard_id'];

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2><img src='{$this->settings['skin_acp_url']}/_newimages/ccs/wizard.png' alt='{$this->lang->words['wizard_alt']}' /> {$this->lang->words['gbl__step_5']}</h2>
	<ul class='context_menu'>
		<!--<li>
			<a href='{$this->settings['base_url']}&amp;module=blocks&amp;section=blocks' title='{$this->lang->words['pause_session']}'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/plugin.png' alt='{$this->lang->words['icon']}' />
				{$this->lang->words['button__pause']}
			</a>
		</li>-->
		<li>
			<a href='{$this->settings['base_url']}&amp;module=blocks&amp;section=blocks&amp;do=delete&amp;type=wizard&amp;block={$sessionId}' title='{$this->lang->words['cancel_block_session']}'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/delete.png' alt='{$this->lang->words['icon']}' />
				{$this->lang->words['button__cancel']}
			</a>
		</li>
	</ul>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=continue&amp;wizard_session={$sessionId}' method='post'>
<input type='hidden' name='step' value='5' />
<div class='acp-box'>
	<h3>{$this->lang->words['edit_block_template']}</h3>
	<ul class='acp-form alternate_rows'>
		<li style='padding: 10px'>
			{$editor_area}
		</li>
	</ul>
	<div class="acp-actionbar">
		<input type='submit' value=' {$this->lang->words['button__continue']} ' class="button primary" />
	</div>
</div>	
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Wizard: Step 6 (Custom)
 *
 * @access	public
 * @param	array 		Block data
 * @return	string		HTML
 */
public function custom__wizard_DONE( $block )
{
$IPBHTML = "";
//--starthtml--//

$key		= '{parse block="' . $block['block_key'] . '"}';

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2><img src='{$this->settings['skin_acp_url']}/_newimages/ccs/wizard.png' alt='{$this->lang->words['wizard_alt']}' /> {$this->lang->words['gbl__step_7']} {$this->lang->words['gbl__finished']}</h2>
	<ul class='context_menu'>
		<li>
			<a href='{$this->settings['base_url']}&amp;module=blocks&amp;section=blocks' title='{$this->lang->words['return_block_overview']}'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/tick.png' alt='{$this->lang->words['icon']}' />
				{$this->lang->words['button__finished']}
			</a>
		</li>
	</ul>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['congrats_block_done']}</h3>
	<ul class='acp-form alternate_rows'>
		<li>
			{$this->lang->words['custom_block_done_1']}
		</li>
		<li>
			{$key}
		</li>
		<li>
			{$this->lang->words['custom_block_done_2']}
		</li>		
	</ul>
	<div class="acp-actionbar">
		<input type='button' value=' {$this->lang->words['button__finished']} ' class="button primary" onclick='acp.redirect("{$this->settings['base_url']}&amp;module=blocks&amp;section=blocks", 1 );' />
	</div>
</div>
HTML;
//--endhtml--//
return $IPBHTML;
}

}