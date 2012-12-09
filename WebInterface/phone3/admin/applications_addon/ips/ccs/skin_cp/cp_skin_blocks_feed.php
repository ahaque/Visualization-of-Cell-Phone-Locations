<?php
/**
 * Invision Power Services
 * Feed block type
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
 
class cp_skin_blocks_feed extends output
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
 * Wizard: Step 2 (Feed)
 *
 * @access	public
 * @param	array 		Session data
 * @param	array 		Feed types to choose from
 * @return	string		HTML
 */
public function feed__wizard_2( $session, $_feedTypes )
{
$IPBHTML = "";
//--starthtml--//

$sessionId	= $session['wizard_id'];

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
	<h3>{$this->lang->words['feed_type']}</h3>
	<table width='100%' cellpadding='0' cellspacing='0' class='form_table alternate_rows double_pad'>
HTML;

			foreach( $_feedTypes as $_feed )
			{
				if( $_feed['app'] AND !IPSLib::appIsInstalled( $_feed['app'] ) )
				{
					continue;
				}

				$IPBHTML .= "<tr>
				<td style='width: 40%'>
					<strong>{$_feed['name']}</strong>" . ( $_feed['description'] ? "<br /><span class='desctext'>{$_feed['description']}</span>" : '' ) . "</td>
				</td>
				<td style='width: 60%'>
					<input type='radio' name='feed_type' value='{$_feed['key']}' " .
					( $session['config_data']['feed_type'] == $_feed['key'] ? "checked='checked' " : '' ) . "/>
				</td>
				</tr>";
			}
			
$IPBHTML .= <<<HTML
	</table>
	<div class="acp-actionbar">
		<input type='submit' value=' {$this->lang->words['button__continue']} ' class="button primary" />
	</div>
</div>	
</form>
<br />
HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Wizard: Step 3 (Feed)
 *
 * @access	public
 * @param	array 		Session data
 * @param	array 		Categories
 * @return	string		HTML
 */
public function feed__wizard_3( $session, $categories )
{
$IPBHTML = "";
//--starthtml--//

$sessionId	= $session['wizard_id'];
$_hide		= $this->registry->output->formYesNo( 'hide_empty', $session['config_data']['hide_empty'] );
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
				<input type='text' class='text' name='feed_title' value='{$session['config_data']['title']}' />
			</td>
		</tr>
		<tr>
			<td>
				<strong>{$this->lang->words['form__block_key']}</strong><br /><span class='desctext'>{$this->lang->words['form__block_key_desc']}</span>
			</td>
			<td>
				<input type='text' class='text' name='feed_key' value='{$session['config_data']['key']}' />
			</td>
		</tr>
		<tr>
			<td>
				<strong>{$this->lang->words['form__block_desc']}</strong>
			</td>
			<td>
				<input type='text' class='text' name='feed_description' value='{$session['config_data']['description']}' />
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
 * Wizard: Step 4 (Feed)
 *
 * @access	public
 * @param	array 		Session data
 * @param	array 		Form data
 * @return	string		HTML
 */
public function feed__wizard_4( $session, $form_data=array() )
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
HTML;

if( count($form_data) )
{
	$IPBHTML .= <<<HTML
<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=continue&amp;wizard_session={$sessionId}' method='post'>
<input type='hidden' name='step' value='4' />
<div class='acp-box'>
	<h3>{$this->lang->words['feed_content_type']}</h3>
	<table width='100%' cellpadding='0' cellspacing='0' class='form_table alternate_rows double_pad'>
HTML;

		foreach( $form_data as $_formBit )
		{
			$IPBHTML .= <<<HTML
			<tr>
				<td style='width: 40%'>
					<strong>{$_formBit['label']}</strong><br /><span class='desctext'>{$_formBit['description']}</span>
				</td>
				<td style='width: 60%'>
					{$_formBit['field']}
				</td>
			</tr>
HTML;
		}
		
		$IPBHTML .= <<<HTML
	</table>
	<div class="acp-actionbar">
		<input type='submit' value=' {$this->lang->words['button__continue']} ' class="button primary" />
	</div>
</div>	
</form>
HTML;
}
else
{
	$IPBHTML .= <<<HTML
<div class='acp-box with_bg'>
	<h3>{$this->lang->words['configure_plugin_no']}</h3>
	<p><em>{$this->lang->words['feed_one_content_type']}</em></p>
	<meta http-equiv="refresh" content="3;url={$this->settings['base_url']}{$this->form_code}&amp;do=continue&amp;wizard_session={$sessionId}&amp;step=4" /> 
</div>
HTML;
}

//--endhtml--//
return $IPBHTML;
}

/**
 * Wizard: Step 5 (Feed)
 *
 * @access	public
 * @param	array 		Session data
 * @param	array 		Form data
 * @return	string		HTML
 */
public function feed__wizard_5( $session, $form_data=array() )
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
<script type='text/javascript'>
ipb.lang['no_rss_feed_url']	= '{$this->lang->words['no_rss_feed_url']}';
</script>
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>
HTML;

if( count($form_data) )
{
	$IPBHTML .= <<<HTML
<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=continue&amp;wizard_session={$sessionId}' method='post'>
<input type='hidden' name='step' value='5' />
<div class='acp-box'>
	<h3>{$this->lang->words['select_feed_filters']}</h3>
	<table width='100%' cellpadding='0' cellspacing='0' class='form_table alternate_rows double_pad'>
HTML;

		foreach( $form_data as $_formBit )
		{
			$IPBHTML .= <<<HTML
			<tr>
				<td style='width: 40%'>
					<strong>{$_formBit['label']}</strong><br /><span class='desctext'>{$_formBit['description']}</span>
				</td>
				<td style='width: 60%'>
					{$_formBit['field']}
				</td>
			</tr>
HTML;
		}
		
		$IPBHTML .= <<<HTML
	</table>
	<div class="acp-actionbar">
		<input type='submit' value=' {$this->lang->words['button__continue']} ' class="button primary" />
	</div>
</div>	
</form>
HTML;
}
else
{
	$IPBHTML .= <<<HTML
<div class='acp-box with_bg'>
	<h3>{$this->lang->words['configure_plugin_no']}</h3>
	<p><em>{$this->lang->words['no_feed_filters']}</em></p>
	<meta http-equiv="refresh" content="3;url={$this->settings['base_url']}{$this->form_code}&amp;do=continue&amp;wizard_session={$sessionId}&amp;step=5" /> 
</div>
HTML;
}

//--endhtml--//
return $IPBHTML;
}

/**
 * Wizard: Step 6 (Feed)
 *
 * @access	public
 * @param	array 		Session data
 * @param	array 		Form data
 * @return	string		HTML
 */
public function feed__wizard_6( $session, $form_data=array() )
{
$IPBHTML = "";
//--starthtml--//

$sessionId	= $session['wizard_id'];

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2><img src='{$this->settings['skin_acp_url']}/_newimages/ccs/wizard.png' alt='{$this->lang->words['wizard_alt']}' /> {$this->lang->words['gbl__step_6']}</h2>
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
HTML;

if( count($form_data) )
{
	$IPBHTML .= <<<HTML
<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=continue&amp;wizard_session={$sessionId}' method='post'>
<input type='hidden' name='step' value='6' />
<div class='acp-box'>
	<h3>{$this->lang->words['select_ordering_opts']}</h3>
	<table width='100%' cellpadding='0' cellspacing='0' class='form_table alternate_rows double_pad'>
HTML;

		foreach( $form_data as $_formBit )
		{
			$IPBHTML .= <<<HTML
			<tr>
				<td style='width: 40%'>
					<strong>{$_formBit['label']}</strong><br /><span class='desctext'>{$_formBit['description']}</span>
				</td>
				<td style='width: 60%'>
					{$_formBit['field']}
				</td>
			</tr>
HTML;
		}
		
		$IPBHTML .= <<<HTML
	</table>
	<div class="acp-actionbar">
		<input type='submit' value=' {$this->lang->words['button__continue']} ' class="button primary" />
	</div>
</div>	
</form>
HTML;
}
else
{
	$IPBHTML .= <<<HTML
<div class='acp-box with_bg'>
	<h3>{$this->lang->words['configure_plugin_no']}</h3>
	<p><em>{$this->lang->words['no_ordering_opts']}</em></p>
	<meta http-equiv="refresh" content="3;url={$this->settings['base_url']}{$this->form_code}&amp;do=continue&amp;wizard_session={$sessionId}&amp;step=6" /> 
</div>
HTML;
}

//--endhtml--//
return $IPBHTML;
}

/**
 * Wizard: Step 7 (Feed)
 *
 * @access	public
 * @param	array 		Session data
 * @return	string		HTML
 */
public function feed__wizard_7( $session )
{
$IPBHTML = "";
//--starthtml--//

$sessionId	= $session['wizard_id'];

if( $session['config_data']['feed_type'] == 'rss' AND !isset($session['config_data']['cache_ttl']) )
{
	$session['config_data']['cache_ttl']	= '15';
}

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2><img src='{$this->settings['skin_acp_url']}/_newimages/ccs/wizard.png' alt='{$this->lang->words['wizard_alt']}' /> {$this->lang->words['gbl__step_7']}</h2>
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


<div class='information-box'>{$this->lang->words['feed_caching_rec']}</div><br />

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=continue&amp;wizard_session={$sessionId}' method='post'>
<input type='hidden' name='step' value='7' />
<div class='acp-box'>
	<h3>{$this->lang->words['specify_caching_op']}</h3>
	<table width='100%' cellpadding='0' cellspacing='0' class='form_table double_pad alternate_rows'>
		<tr>
			<td style='width: 40%'>
				<strong>{$this->lang->words['cache_ttl_opt']}</strong><br /><span class='desctext'>{$this->lang->words['cache_ttl_desc']}</span>
			</td>
			<td style='width: 60%'>
				<input type='text' class='text' name='feed_cache_ttl' value='{$session['config_data']['cache_ttl']}' />
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
 * Wizard: Step 8 (Feed)
 *
 * @access	public
 * @param	array 		Session data
 * @param	string		Editor HTML
 * @return	string		HTML
 */
public function feed__wizard_8( $session, $editor_area )
{
$IPBHTML = "";
//--starthtml--//

$sessionId	= $session['wizard_id'];

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2><img src='{$this->settings['skin_acp_url']}/_newimages/ccs/wizard.png' alt='{$this->lang->words['wizard_alt']}' /> {$this->lang->words['gbl__step_8']}</h2>
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
<input type='hidden' name='step' value='8' />
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
 * Wizard: Step 9 (Feed)
 *
 * @access	public
 * @param	array 		Block data
 * @return	string		HTML
 */
public function feed__wizard_DONE( $block )
{
$IPBHTML = "";
//--starthtml--//

$key		= '{parse block="' . $block['block_key'] . '"}';

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2><img src='{$this->settings['skin_acp_url']}/_newimages/ccs/wizard.png' alt='{$this->lang->words['wizard_alt']}' /> {$this->lang->words['gbl__step_9']} {$this->lang->words['gbl__finished']}</h2>
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