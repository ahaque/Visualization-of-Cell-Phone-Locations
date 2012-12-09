<?php
/**
 * Invision Power Services
 * Pages skin file
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
 
class cp_skin_pages extends output
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
 * Easy pages form (like CSS and JS)
 *
 * @access	public
 * @param	string		Form type (add|edit)
 * @param	string		Content type (css|js)
 * @param	array 		Page data
 * @return	string		HTML
 */
public function easyPageForm( $formType, $contentType, $page, $folders )
{
$IPBHTML = "";
//--starthtml--//

$title			= $formType == 'edit' ? sprintf( $this->lang->words['edit_content_type_title'], $page['page_folder'], $page['page_seo_name'] ) : $this->lang->words['add_content_type_title'];
$extension		= $formType == 'edit' ? $page['page_content_type'] : $this->request['fileType'];

$seoName		= $this->registry->output->formInput( 'page_seo_name', $page['page_seo_name'] );
$desc			= $this->registry->output->formTextarea( 'page_description', $page['page_description'], 50, 4 );

$folders		=  array_merge( array( array( '', '/' ) ), $folders );
$folder			= $this->registry->output->formDropdown( 'page_folder', $folders, $page['page_folder'] ? $page['page_folder'] : $this->request['in'] );
$editor_area	= $this->registry->output->formTextarea( "content", htmlspecialchars( $page['page_content'] ), 100, 30, "content", "style='width:100%;'" );

$url			= $this->registry->ccsFunctions->returnPageUrl( $page );

$IPBHTML .= <<<HTML
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>

<div class='section_title'>
	<h2>{$title} <span class='view-page'><a href='{$url}' target='_blank' title='{$this->lang->words['view_page_title']}'><img src='{$this->settings['skin_acp_url']}/_newimages/ccs/page_go.png' alt='{$this->lang->words['view_page_title']}' /></a></span></h2>
	<ul class='context_menu'>
		<li>
			<a href='{$this->settings['base_url']}&amp;module=pages&amp;section=list' title='{$this->lang->words['cancel_edit_alt']}'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/delete.png' alt='{$this->lang->words['icon']}' />
				{$this->lang->words['button__cancel']}
			</a>
		</li>
	</ul>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=saveEasyPage' method='post' id='adform' name='adform'>
<input type='hidden' name='type' value='{$formType}' />
<input type='hidden' name='content_type' value='{$contentType}' />
<input type='hidden' name='page' value='{$page['page_id']}' />

<div class='acp-box'>
	<h3>{$this->lang->words['config_content_header']}</h3>

	<ul class='acp-form alternate_rows'>
		<li>
			<label>{$this->lang->words['enter_ct_filename']} <span class='desctext'>{$this->lang->words['page_filename_desc']}</span></label>
			{$seoName} .{$extension}
		</li>
		<li>
			<label>{$this->lang->words['folder_for_ct']}</label>
			{$folder}
		</li>
		<li>
			<label>{$this->lang->words['supply_ct_desc']} <span class='desctext'>{$this->lang->words['supply_ct_desc_desc']}</span></label>
			{$desc}
		</li>
		<li>
			<label>{$this->lang->words['editor_content_for_ct']}</label>
			<div id='content-label'>{$editor_area}</div>
		</li>
	</ul>

	<div class="acp-actionbar">
		<input type='submit' value=' {$this->lang->words['button__save']} ' class="button primary" /> <input type='submit' name='save_and_reload' value=' {$this->lang->words['button__reload']} ' class="button primary" />
	</div>
</div>	
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Quick edit a page
 *
 * @access	public
 * @param	array		Page data
 * @param	string		HTML for content editor
 * @param	array 		Folders
 * @param	array 		Permission masks
 * @return	string		HTML
 */
public function page_edit( $page, $editor, $folders, $masks )
{
$IPBHTML = "";
//--starthtml--//

$pageName		= $this->registry->output->formInput( 'page_name', $page['page_name'] );
$seoName		= $this->registry->output->formInput( 'page_seo_name', $page['page_seo_name'] );
$desc			= $this->registry->output->formTextarea( 'page_description', $page['page_description'], 50, 4 );
$metak			= $this->registry->output->formTextarea( 'page_meta_keywords', $page['page_meta_keywords'], 50, 2 );
$metad			= $this->registry->output->formTextarea( 'page_meta_description', $page['page_meta_description'], 50, 2 );

$folders		=  array_merge( array( array( '', '/' ) ), $folders );
$folder			= $this->registry->output->formDropdown( 'page_folder', $folders, $page['page_folder'] );

$contentTypes	= array(
						'bbcode'	=> $this->lang->words['pages__bbcode'],
						'html'		=> $this->lang->words['pages__html'],
						'php'		=> $this->lang->words['pages__php'],
						);

$selectedMasks	= array();

if( $page['page_view_perms'] != '*' )
{
	$selectedMasks	= explode( ',', $page['page_view_perms'] );
}

$template		= $page['page_template_title'] ? $page['page_template_title'] : $this->lang->words['generic__none'];
$content 		= $page['page_content_only'] ? $this->lang->words['qedit_content_only'] : $this->lang->words['qedit_content_not'];
$ipbwrapper		= $this->registry->output->formYesNo( 'page_ipb_wrapper', $page['page_ipb_wrapper'] );

$allMasks 		= $this->registry->output->formYesNo( 'all_masks', $page['page_view_perms'] == '*' ? 1 : 0 );
$masks			= $this->registry->output->formMultiDropdown( 'masks[]', $masks, $selectedMasks, 8 );

$url	= $this->registry->ccsFunctions->returnPageUrl( $page );

if( $page['page_content_only'] )
{
	$text		= sprintf( $this->lang->words['info__content_only'], $this->settings['base_url'] );
	$infoBox	= <<<HTML
	<div class='information-box'>
		{$text}
	</div>
HTML;
}
else
{
	$infoBox = <<<HTML
	<div class='information-box'>
		{$this->lang->words['info__no_content_only']}
	</div>
HTML;
}

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['editing_page_pre']} {$page['page_folder']}/{$page['page_seo_name']} <span class='view-page'><a href='{$url}' target='_blank' title='{$this->lang->words['view_page_title']}'><img src='{$this->settings['skin_acp_url']}/_newimages/ccs/page_go.png' alt='{$this->lang->words['view_page_title']}' /></a></span></h2>
	<ul class='context_menu'>
		<li>
			<a href='{$this->settings['base_url']}&amp;module=pages&amp;section=wizard&amp;do=editPage&amp;page={$page['page_id']}' title='{$this->lang->words['use_wizard_alt']}'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/ccs/wizard_small.png' alt='{$this->lang->words['icon']}' />
				{$this->lang->words['use_wizard_alt']}
			</a>
		</li>
		<li>
			<a href='{$this->settings['base_url']}&amp;module=pages&amp;section=pages&amp;do=delete&amp;type=wizard&amp;page={$sessionId}' title='{$this->lang->words['cancel_edit_alt']}'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/delete.png' alt='{$this->lang->words['icon']}' />
				{$this->lang->words['button__cancel']}
			</a>
		</li>
	</ul>
</div>

<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=saveQuickEdit&amp;page={$page['page_id']}' method='post' id='adform' name='adform'>
<ul id='tabstrip_ccs_page' class='tab_bar no_title'>
	<li id='tabtab-CCSPAGE|1' class=''>{$this->lang->words['tab__content']}</li>
	<li id='tabtab-CCSPAGE|2' class=''>{$this->lang->words['tab__details']}</li>
	<li id='tabtab-CCSPAGE|3' class=''>{$this->lang->words['tab__caching']}</li>
	<li id='tabtab-CCSPAGE|4' class=''>{$this->lang->words['tab__permissions']}</li>
</ul>

<script type="text/javascript">
//<![CDATA[
document.observe("dom:loaded",function() 
{
ipbAcpTabStrips.register('tabstrip_ccs_page');
});
 //]]>
</script>
	
<div class='acp-box'>
	<div id='tabpane-CCSPAGE|1'>
		<h2 class='tablesubheader' style='font-weight: bold;'>{$this->lang->words['edit_page_content_header']}</h2>
		{$infoBox}
		<ul class='acp-form alternate_rows'>
			<li>
				<div id='content-label'>{$editor}</div>
			</li>
		</ul>
	</div>
	
	<div id='tabpane-CCSPAGE|2'>
		<h2 class='tablesubheader' style='font-weight: bold;'>{$this->lang->words['page_details_header']}</h2>
		<ul class='acp-form alternate_rows'>
			<li>
				<label>{$this->lang->words['give_page_name']} <span class='desctext'>{$this->lang->words['give_page_name_desc']}</span></label>
				{$pageName}
			</li>
			<li>
				<label>{$this->lang->words['enter_page_filename']} <span class='desctext'>{$this->lang->words['page_filename_desc']}</span></label>
				{$seoName}
			</li>
			<li>
				<label>{$this->lang->words['folder_for_page']}</label>
				{$folder}
			</li>
			<li>
				<label>{$this->lang->words['supply_page_desc']} <span class='desctext'>{$this->lang->words['supply_page_desc_desc']}</span></label>
				{$desc}
			</li>
			<li>
				<label>{$this->lang->words['qe_content_type']} <span class='desctext'>{$this->lang->words['to_change_use_wizard']}</span></label>
				{$contentTypes[ $page['page_type'] ]}
			</li>
			<li>
				<label>{$this->lang->words['qe_template_used']} <span class='desctext'>{$this->lang->words['to_change_use_wizard']}</span></label>
				{$template}
			</li>
			<li>
				<label>{$this->lang->words['qe_editing_method']}  <span class='desctext'>{$this->lang->words['to_change_use_wizard']}</span></label>
				{$content}
			</li>
			<li>
				<label>{$this->lang->words['use_ipb_wrapper']}  <span class='desctext'>{$this->lang->words['use_ipb_wrapper_desc']}</span></label>
				{$ipbwrapper}
			</li>
			<li>
				<label>{$this->lang->words['meta_keywords_form']}</label>
				{$metak}
			</li>
			<li>
				<label>{$this->lang->words['meta_description_form']}</label>
				{$metad}
			</li>
		</ul>
	</div>
	
	<div id='tabpane-CCSPAGE|3'>
		<h2 class='tablesubheader' style='font-weight: bold;'>{$this->lang->words['specify_caching_op']}</h2>
		<div class='information-box'>
			{$this->lang->words['page_caching_desc']}
		</div>
		<ul class='acp-form alternate_rows'>
			<li>
				<label>{$this->lang->words['cache_ttl_opt']} <span class='desctext'>{$this->lang->words['page_cache_ttl_desc']}</span></label>
				<input type='text' class='text' name='page_cache_ttl' value='{$page['page_cache_ttl']}' />
			</li>
		</ul>
	</div>
	
	<div id='tabpane-CCSPAGE|4'>
		<h2 class='tablesubheader' style='font-weight: bold;'>{$this->lang->words['perm_masks_header']}</h2>
		<ul class='acp-form alternate_rows'>
			<li>
				<label>{$this->lang->words['all_current_future_masks']} <span class='desctext'>{$this->lang->words['all_masks_desc']}</span></label>
				{$allMasks}
			</li>
			<li>
				<label>{$this->lang->words['specific_perm_masks']}</label>
				{$masks}
			</li>
		</ul>
	</div>

	<div class="acp-actionbar">
		<input type='submit' value=' {$this->lang->words['button__save']} ' class="button primary" /> <input type='submit' name='save_and_reload' value=' {$this->lang->words['button__reload']} ' class="button primary" />
	</div>
</div>	
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}


/**
 * Wizard: Step 1
 *
 * @access	public
 * @param	array		Session data
 * @param	array 		Additional data
 * @return	string		HTML
 */
public function wizard_step_1( $session, $additional )
{
$IPBHTML = "";
//--starthtml--//

$sessionId	= $session['wizard_id'];
$pageName	= $this->registry->output->formInput( 'name', $session['wizard_name'] );
$seoName	= $this->registry->output->formInput( 'page_name', $session['wizard_seo_name'] );
$desc		= $this->registry->output->formTextarea( 'description', $session['wizard_description'], 50, 4 );
$metak		= $this->registry->output->formTextarea( 'meta_keywords', $session['wizard_meta_keywords'], 50, 2 );
$metad		= $this->registry->output->formTextarea( 'meta_description', $session['wizard_meta_description'], 50, 2 );

$folders	=  array_merge( array( array( '', '/' ) ), $additional['folders'] );
$folder		= $this->registry->output->formDropdown( 'folder', $folders, $this->request['in'] ? urldecode($this->request['in']) : $session['wizard_folder'] );

$pageTypes	= array( array( 'bbcode', $this->lang->words['pages__bbcode'] ), array( 'html', $this->lang->words['pages__html'] ), array( 'php', $this->lang->words['pages__php'] ) );
$pageType	= $this->registry->output->formDropdown( 'type', $pageTypes, $session['wizard_type'] ? $session['wizard_type'] : 'html' );

$templates	= array_merge( array( array( 'none', $this->lang->words['generic__none'] ) ), $additional['templates'] );
$template	= $this->registry->output->formDropdown( 'template', $templates, $session['wizard_template'] );

$content 	= $this->registry->output->formYesNo( 'content_only', $session['wizard_content_only'] );

$ipbwrapper	= $this->registry->output->formYesNo( 'ipb_wrapper', $session['wizard_ipb_wrapper'] );

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2><img src='{$this->settings['skin_acp_url']}/_newimages/ccs/wizard.png' alt='{$this->lang->words['wizard_alt']}' /> {$this->lang->words['gbl__step_1']}</h2>
	<ul class='context_menu'>
		<!--<li>
			<a href='{$this->settings['base_url']}&amp;module=pages&amp;section=list' title='{$this->lang->words['pause_session']}'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/plugin.png' alt='{$this->lang->words['icon']}' />
				{$this->lang->words['button__pause']}
			</a>
		</li>-->
		<li>
			<a href='{$this->settings['base_url']}&amp;module=pages&amp;section=pages&amp;do=delete&amp;type=wizard&amp;page={$sessionId}' title='{$this->lang->words['cancel_page_session']}'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/delete.png' alt='{$this->lang->words['icon']}' />
				{$this->lang->words['button__cancel']}
			</a>
		</li>
	</ul>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=process&amp;wizard_session={$sessionId}' method='post'>
<input type='hidden' name='step' value='1' />
<div class='acp-box'>
	<h3>{$this->lang->words['create_page_header']}</h3>
	<table width='100%' cellpadding='0' cellspacing='0' class='form_table alternate_rows double_pad'>
		<tr>
			<th colspan='2'>{$this->lang->words['tab__details']}</th>
		</tr>
		<tr>
			<td style='width: 40%'><strong>{$this->lang->words['give_page_name']}</strong><br /><span class='desctext'>{$this->lang->words['page_filename_desc']}</span></td>
			<td style='width: 60%'>{$pageName}</td>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['enter_page_filename']}</strong><br /><span class='desctext'>{$this->lang->words['page_filename_desc']}</span></td>
			<td>{$seoName}</td>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['folder_for_page']}</strong></td>
			<td>{$folder}</td>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['supply_page_desc']}</strong><br /><span class='desctext'>{$this->lang->words['supply_page_desc_desc']}</span></td>
			<td>{$desc}</td>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['meta_keywords_form']}</strong></td>
			<td>{$metak}</td>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['meta_description_form']}</strong></td>
			<td>{$metad}</td>
		</tr>
		<tr>
			<th colspan='2'>{$this->lang->words['tab__editing_options']}</th>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['how_to_edit_page']}</strong></td>
			<td>{$pageType}</td>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['template_to_start_with']}</strong></td>
			<td>{$template}</td>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['only_edit_content']}</strong><br /><span class='desctext'>{$this->lang->words['only_edit_content_desc']}</span></td>
			<td>{$content}</td>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['use_ipb_wrapper']}</strong><br /><span class='desctext'>{$this->lang->words['use_ipb_wrapper_desc']}</span></td>
			<td>{$ipbwrapper}</td>
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
 * Wizard: Step 2
 *
 * @access	public
 * @param	array		Session data
 * @param	array 		Additional data
 * @return	string		HTML
 */
public function wizard_step_2( $session, $additional )
{
$IPBHTML = "";
//--starthtml--//

$sessionId	= $session['wizard_id'];
$infoBox	= '';

if( $session['wizard_content_only'] )
{
	$text		= sprintf( $this->lang->words['info__content_only'], $this->settings['base_url'] );
	$infoBox	= <<<HTML
	<div class='information-box'>
		{$text}
	</div>
	<br />
HTML;
}
else
{
	$infoBox = <<<HTML
	<div class='information-box'>
		{$this->lang->words['info__no_content_only_2']}
	</div>
	<br />
HTML;
}

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2><img src='{$this->settings['skin_acp_url']}/_newimages/ccs/wizard.png' alt='{$this->lang->words['wizard_alt']}' /> {$this->lang->words['gbl__step_2']}</h2>
	<ul class='context_menu'>
		<!--<li>
			<a href='{$this->settings['base_url']}&amp;module=pages&amp;section=list' title='{$this->lang->words['pause_session']}'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/plugin.png' alt='{$this->lang->words['icon']}' />
				{$this->lang->words['button__pause']}
			</a>
		</li>-->
		<li>
			<a href='{$this->settings['base_url']}&amp;module=pages&amp;section=pages&amp;do=delete&amp;type=wizard&amp;page={$sessionId}' title='{$this->lang->words['cancel_page_session']}'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/delete.png' alt='{$this->lang->words['icon']}' />
				{$this->lang->words['button__cancel']}
			</a>
		</li>
	</ul>
</div>

<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>

{$infoBox}

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=process&amp;wizard_session={$sessionId}' method='post'>
<input type='hidden' name='step' value='2' />
<div class='acp-box'>
	<h3>{$this->lang->words['edit_page_content_header']}</h3>
	<ul class='acp-form alternate_rows'>
		<li style='padding: 10px'>
			<div id='content-label'>{$additional['editor']}</div>
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
 * Wizard: Step 3
 *
 * @access	public
 * @param	array		Session data
 * @param	array 		Additional data
 * @return	string		HTML
 */
public function wizard_step_3( $session, $additional )
{
$IPBHTML = "";
//--starthtml--//

$sessionId	= $session['wizard_id'];

$session['wizard_cache_ttl']	= $session['wizard_cache_ttl'] ? $session['wizard_cache_ttl'] : '0';

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2><img src='{$this->settings['skin_acp_url']}/_newimages/ccs/wizard.png' alt='{$this->lang->words['wizard_alt']}' /> {$this->lang->words['gbl__step_3']}</h2>
	<ul class='context_menu'>
		<!--<li>
			<a href='{$this->settings['base_url']}&amp;module=pages&amp;section=list' title='{$this->lang->words['pause_session']}'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/plugin.png' alt='{$this->lang->words['icon']}' />
				{$this->lang->words['button__pause']}
			</a>
		</li>-->
		<li>
			<a href='{$this->settings['base_url']}&amp;module=pages&amp;section=pages&amp;do=delete&amp;type=wizard&amp;page={$sessionId}' title='{$this->lang->words['cancel_page_session']}'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/delete.png' alt='{$this->lang->words['icon']}' />
				{$this->lang->words['button__cancel']}
			</a>
		</li>
	</ul>
</div>
<div class='information-box'>
	{$this->lang->words['page_caching_desc']}
</div>
<br />

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=process&amp;wizard_session={$sessionId}' method='post'>
<input type='hidden' name='step' value='3' />
<div class='acp-box'>
	<h3>{$this->lang->words['specify_caching_op']}</h3>
	<table width='100%' cellpadding='0' cellspacing='0' class='form_table alternate_rows double_pad'>
		<tr>
			<td style='width: 40%'><strong>{$this->lang->words['cache_ttl_opt']}</strong><br /><span class='desctext'>{$this->lang->words['page_cache_ttl_desc']}</span></td>
			<td style='width: 60%'><input type='text' class='text' name='cache_ttl' value='{$session['wizard_cache_ttl']}' /></td>
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
 * Wizard: Step 4
 *
 * @access	public
 * @param	array		Session data
 * @param	array 		Additional data
 * @return	string		HTML
 */
public function wizard_step_4( $session, $additional )
{
$IPBHTML = "";
//--starthtml--//

$sessionId	= $session['wizard_id'];

$allMasks 	= $this->registry->output->formYesNo( 'all_masks', $additional['all_masks'] );
$masks		= $this->registry->output->formMultiDropdown( 'masks[]', $additional['avail_masks'], $additional['masks'], 8 );

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2><img src='{$this->settings['skin_acp_url']}/_newimages/ccs/wizard.png' alt='{$this->lang->words['wizard_alt']}' /> {$this->lang->words['gbl__step_4']}</h2>
	<ul class='context_menu'>
		<!--<li>
			<a href='{$this->settings['base_url']}&amp;module=pages&amp;section=list' title='{$this->lang->words['pause_session']}'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/plugin.png' alt='{$this->lang->words['icon']}' />
				{$this->lang->words['button__pause']}
			</a>
		</li>-->
		<li>
			<a href='{$this->settings['base_url']}&amp;module=pages&amp;section=pages&amp;do=delete&amp;type=wizard&amp;page={$sessionId}' title='{$this->lang->words['cancel_page_session']}'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/delete.png' alt='{$this->lang->words['icon']}' />
				{$this->lang->words['button__cancel']}
			</a>
		</li>
	</ul>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=process&amp;wizard_session={$sessionId}' method='post'>
<input type='hidden' name='step' value='4' />
<div class='acp-box'>
	<h3>{$this->lang->words['perm_masks_header']}</h3>
	<table width='100%' cellpadding='0' cellspacing='0' class='form_table alternate_rows double_pad'>
		<tr>
			<td style='width: 40%'><strong>{$this->lang->words['all_current_future_masks']}</strong><br /><span class='desctext'>{$this->lang->words['all_masks_desc']}</span></td>
			<td style='width: 60%'>{$allMasks}</td>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['specific_perm_masks']}</strong></td>
			<td>{$masks}</td>
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
 * Wizard: Step 5 (Finished)
 *
 * @access	public
 * @param	array		Session data
 * @param	array 		Additional data
 * @return	string		HTML
 */
public function wizard_step_5( $session, $additional )
{
$IPBHTML = "";
//--starthtml--//

$sessionId	= $session['wizard_id'];

$url	= $this->registry->ccsFunctions->returnPageUrl( array( 'page_folder' => $session['wizard_folder'], 'page_seo_name' => $session['wizard_seo_name'], 'page_id' => $session['page_id'] ) );

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2><img src='{$this->settings['skin_acp_url']}/_newimages/ccs/wizard.png' alt='{$this->lang->words['wizard_alt']}' /> {$this->lang->words['gbl__step_5']} {$this->lang->words['gbl__finished']}</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['congrats_block_done']}</h3>
	<ul class='acp-form alternate_rows'>
		<li>
			{$this->lang->words['page_done_visit_1']}
		</li>
		<li>
			<a href='{$url}' target='_blank'>{$url}</a>
		</li>
		<li>
			{$this->lang->words['page_done_visit_2']}
		</li>		
	</ul>
	<div class="acp-actionbar">
		<input type='button' value=' {$this->lang->words['button__finished']} ' class="button primary" onclick='acp.redirect("{$this->settings['base_url']}&amp;module=pages&amp;section=list&amp;do=viewdir&amp;dir={$session['wizard_folder']}", 1 );' />
	</div>
</div>

HTML;
//--endhtml--//
return $IPBHTML;
}

}