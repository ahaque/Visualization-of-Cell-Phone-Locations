<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * RSS skin functions
 * Last Updated: $LastChangedDate: 2009-03-27 11:41:38 -0400 (Fri, 27 Mar 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Forums
 * @link		http://www.
 * @since		14th May 2003
 * @version		$Rev: 4333 $
 */
 
class cp_skin_rss extends output {

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
 * Export overview page
 *
 * @access	public
 * @param	string	Rows html
 * @param	string	Page links
 * @return	string	HTML
 */
public function rssExportOverview( $content, $page_links ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['ex_title']}</h2>
</div>

HTML;

if( $page_links != "" )
{
	$IPBHTML .= <<<HTML
	{$page_links}
	<br style='clear: both' />
HTML;
}

$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$this->lang->words['rss_ex_streams']}</h3>
	
	<table class='alternate_rows double_pad' width='100%'>
		<tr>
			<th width='90%'>{$this->lang->words['rss_title']}</th>
			<th width='5%' align='center'>{$this->lang->words['rss_enabled']}</th>
			<th width='5%' align='right'>
				<img id="menumainone" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' border='0' alt='{$this->lang->words['rss_options']}' class='ipbmenu' />
				<ul class='acp-menu' id='menumainone_menucontent'>
					<li class='icon add'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=rssexport_add'>{$this->lang->words['rss_ex_create']}</a></li>
					<li class='icon refresh'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=rssexport_recache&amp;rss_export_id=all'>{$this->lang->words['rss_ex_update']}</a></li>
				</ul>
			</th>
		</tr>
HTML;

foreach( $content as $data )
{
$IPBHTML .= <<<HTML
		<tr>
			<td>
				<a target='_blank' href='{$this->settings['board_url']}/index.php?app=core&amp;module=global&amp;section=rss&amp;type=forums&amp;id={$data['rss_export_id']}'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/feed.png' border='0' alt='RSS' style='vertical-align:top' /></a>
				<strong>{$data['rss_export_title']}</strong>
			</td>
			<td align='center'><img src='{$this->settings['skin_acp_url']}/images/{$data['_enabled_img']}' border='0' alt='YN' class='ipd' /></td>
			<td align='right'>
				<img id="menu{$data['rss_export_id']}" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' border='0' alt='{$this->lang->words['rss_']}' class='ipbmenu' />
				<ul class='acp-menu' id='menu{$data['rss_export_id']}_menucontent'>
					<li class='icon edit'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=rssexport_edit&amp;rss_export_id={$data['rss_export_id']}'>{$this->lang->words['rss_ex_edit']}</a></li>
					<li class='icon delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=rssexport_delete&amp;rss_export_id={$data['rss_export_id']}");'>{$this->lang->words['rss_ex_delete']}</a></li>
					<li class='icon refresh'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=rssexport_recache&amp;rss_export_id={$data['rss_export_id']}'>{$this->lang->words['rss_ex_recache']}</a></li>
				</ul>
			</td>
		</tr>

HTML;
}

$IPBHTML .= <<<HTML
	</table>
</div>

HTML;

if( $page_links != "" )
{
	$IPBHTML .= <<<HTML
	{$page_links}
	<br style='clear: both' />
HTML;
}


//--endhtml--//
return $IPBHTML;
}

/**
 * Export form
 *
 * @access	public
 * @param	array 	Form fields
 * @param	string	Title
 * @param	string	Action code
 * @param	string	Button text
 * @param	array	RSS Stream info
 * @return	string	HTML
 */
public function rssExportForm( $form, $title, $formcode, $button, $rssstream ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['ex_title']}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=$formcode&amp;rss_export_id={$rssstream['rss_export_id']}' method='post'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->_admin_auth_key}' />
	
	<div class='acp-box'>
		<h3>$title</h3>
		
		<ul class="acp-form alternate_rows">
			<li>
				<label>{$this->lang->words['rss_ex_title']}</label>
				{$form['rss_export_title']}
			</li>
			<li>
				<label>{$this->lang->words['rss_desc']}</label>
				{$form['rss_export_desc']}
			</li>
			<li>
				<label>{$this->lang->words['rss_ex_img']}<span class='desctext'>{$this->lang->words['rss_ex_img_info']}</span></label>
				{$form['rss_export_image']}
			</li>
			<li>
				<label>{$this->lang->words['rss_ex_enabled']}</label>
				{$form['rss_export_enabled']}
			</li>
			<li>
				<label>{$this->lang->words['rss_ex_firstpost']}</label>
				{$form['rss_export_include_post']}
			</li>
			<li>
				<label>{$this->lang->words['rss_ex_numitem']}<span class='desctext'>{$this->lang->words['rss_ex_numitem_info']}</span></label>
				{$form['rss_export_count']}
			</li>
			<li>
				<label>{$this->lang->words['rss_ex_order']}</label>
				{$form['rss_export_order']}
			</li>
			<li>
				<label>{$this->lang->words['rss_ex_sort']}</label>
				{$form['rss_export_sort']}
			</li>
			<li>
				<label>{$this->lang->words['rss_ex_forums']}<span class='desctext'>{$this->lang->words['rss_ex_forums_info']}</span></label>
				<td width='60%' class='tablerow2'>{$form['rss_export_forums']}</td>
			</li>
			<li>
				<label>{$this->lang->words['rss_ex_cache']}<span class='desctext'>{$this->lang->words['rss_ex_cache_info']}</span></label>
				{$form['rss_export_cache_time']}
			</li>
		</ul>
	</div>
	
	<div class='acp-actionbar'>
		<div class='rightaction'>
			<input type='submit' class='button primary' value='{$button}' />
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Splash page for removing imported items
 *
 * @access	public
 * @param	array 	RSS Record
 * @param	integer	Remove article count
 * @return	string	HTML
 */
public function rssImportRemoveArticlesForm( $rssstream, $article_count ) {

$article_count_text = sprintf( $this->lang->words['rss_im_articlecount'], $article_count ); 
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['im_title']}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=rssimport_remove_complete&amp;rss_import_id={$rssstream['rss_import_id']}' method='post'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->_admin_auth_key}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['rss_im_removetopics']} {$rssstream['rss_import_title']}</h3>
		<ul class='acp-form alternate_rows'>
			<li>
				<label>{$article_count_text}</label>
			</li>
			<li>
				<label>{$this->lang->words['rss_im_removelast']}<span class='desctext'>{$this->lang->words['rss_im_blankall']}</span></label>
				<input type='text' name='remove_count' value='10' />
			</li>
		</ul>
		
		<div class='acp-actionbar'>
			<div class='rightaction'>
				<input type='submit' class='button primary' value='{$this->lang->words['rss_im_removenoconf']}' />
			</div>
		</div>		
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * RSS Import form
 *
 * @access	public
 * @param	array 	Form fields
 * @param	string	Title
 * @param	string	Action code
 * @param	string	Button text
 * @param	array 	RSS Record
 * @return	string	HTML
 */
public function rssImportForm( $form, $title, $formcode, $button, $rssstream ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['im_title']}</h2>
</div>

<script type="text/javascript" src='{$this->settings['js_app_url']}acp.rss.js'></script>
<form id='rssimport_form' action='{$this->settings['base_url']}{$this->form_code}&amp;do={$formcode}&amp;rss_import_id={$rssstream['rss_import_id']}' method='post'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->_admin_auth_key}' />
	<input id='rssimport_validate' type='hidden' name='rssimport_validate' value='0' />
	
	<div class='acp-box'>
		<h3>{$title}</h3>
		
		<ul class='acp-form alternate_rows'>
			<li><label class='head'>{$this->lang->words['rss_im_basics']}</label></li>
						
			<li>
				<label>{$this->lang->words['rss_im_title']}</label>
				{$form['rss_import_title']}
	 		</li>
			<li>
				<label>{$this->lang->words['rss_im_url']}<span class='desctext'>{$this->lang->words['rss_im_url_info']}</span></label>
				{$form['rss_import_url']}
			</li>
			<li>
				<label>{$this->lang->words['rss_im_charset']}<span class='desctext'>{$this->lang->words['rss_im_charset_info']}</span></label>
				{$form['rss_import_charset']}
			</li>
			<li>
				<label>{$this->lang->words['rss_im_enabled']}</label>
				{$form['rss_import_enabled']}
			</li>

			<li><label class='head'>{$this->lang->words['rss_im_htaccess']}</label></li>
	
			<li>
				<label>{$this->lang->words['rss_im_ht_require']}<span class='desctext'>{$this->lang->words['rss_im_ht_info']}</span></label>
				{$form['rss_import_auth']}
			</li>
			
			<span id='rss_import_auth_userinfo' {$form['rss_div_show']}>
				<li>
					<label>{$this->lang->words['rss_im_ht_user']}</label>
					{$form['rss_import_auth_user']}
				</li>
				
				<li>
					<label>{$this->lang->words['rss_im_ht_pass']}</label>
					{$form['rss_import_auth_pass']}
				</li>
			</span>

			<li><label class='head'>{$this->lang->words['rss_im_content']}</label></li>
						
			<li>
				<label>{$this->lang->words['rss_im_forum']}<span class='desctext'>{$this->lang->words['rss_im_forum_info']}</span></label>
				{$form['rss_import_forum_id']}
			</li>
			<li>
				<label>{$this->lang->words['rss_im_html']}<span class='desctext'>{$this->lang->words['rss_im_html_info']}</span></label>
				{$form['rss_import_allow_html']}
			</li>
			<li>
				<label>{$this->lang->words['rss_im_poster']}<span class='desctext'>{$this->lang->words['rss_im_poster_info']}</span></label>
				{$form['rss_import_mid']}
			</li>
			<li>
				<label>{$this->lang->words['rss_im_incpost']}<span class='desctext'>{$this->lang->words['rss_im_incpost_info']}</span></label>
				{$form['rss_import_inc_pcount']}
			</li>
			<li>
				<label>{$this->lang->words['rss_im_link']}<span class='desctext'>{$this->lang->words['rss_im_link_info']}</span></label>
				{$form['rss_import_showlink']}
			</li>
			<li>
				<label>{$this->lang->words['rss_im_open']}<span class='desctext'>{$this->lang->words['rss_im_open_info']}</span></label>
				{$form['rss_import_topic_open']}
			</li>
			<li>
				<label>{$this->lang->words['rss_im_hidden']}<span class='desctext'>{$this->lang->words['rss_im_hidden_info']}</span></label>
				{$form['rss_import_topic_hide']}
			</li>
			<li>
				<label>{$this->lang->words['rss_im_prefix']}<span class='desctext'>{$this->lang->words['rss_im_prefix_info']}</span></label>
				{$form['rss_import_topic_pre']}
			</li>

  			<li><label class='head'>{$this->lang->words['rss_im_settings']}</label></li>
						
			<li>
				<label>{$this->lang->words['rss_im_pergo']}<span class='desctext'>{$this->lang->words['rss_im_pergo_info']}</span></label>
				{$form['rss_import_pergo']}
			</li>
			
			<li>
				<label>{$this->lang->words['rss_im_refresh']}<span class='desctext'>{$this->lang->words['rss_im_refresh_info']}</span></label>
				{$form['rss_import_time']}
			</li>
		</ul>
		
		<div class='acp-actionbar'>
			<div class='rightaction'>
				<input type='submit' class='button primary' value='$button' /> &nbsp;&nbsp;&nbsp;
				<input type='button' class='button primary' value='{$this->lang->words['rss_im_valbutton']}' onclick='ACPRss.validate();' /></div>
			</div>
		</div>		
	</div>
</form>

HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * RSS Import stream overview
 *
 * @access	public
 * @param	string	HTML content
 * @param	string	Page links
 * @return	string	HTML
 */
public function rssImportOverview( $content, $page_links ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['im_title']}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=rssimport_validate' method='post'>
	<div class='acp-box'>
		<h3>{$this->lang->words['rss_im_quickval']}</h3>
	
		<ul class='acp-form alternate_rows'>
			<li>
				<label>{$this->lang->words['rss_im_enturl']}</label>
				<input type='text' size='50' name='rss_url' value='http://' /> 
				<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->_admin_auth_key}' />				
			</li>
		</ul>
		
		<div class='acp-actionbar'>
			<div class='rightaction'>
				<input type='submit' class='button primary' value='{$this->lang->words['rss_im_valbutton']}' />
			</div>
		</div>
	</div>
</form>
<br />

{$page_links}
<br style='clear: both' />

<div class='acp-box'>
	<h3>{$this->lang->words['rss_im_thefeeds']}</h3>
	
	<table class='alternate_rows' width='100%'>
		<tr>
			<th width='90%'>{$this->lang->words['rss_title']}</th>
			<th width='5%' align='center'>{$this->lang->words['rss_enabled']}</th>
			<th width='5%' align='right'>
				<img id="menumainone" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' border='0' alt='{$this->lang->words['rss_options']}' class='ipbmenu' />
				<ul class='acp-menu' id='menumainone_menucontent'>
					<li class='icon add'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=rssimport_add'>{$this->lang->words['rss_im_create']}</a></li>
					<li class='icon refresh'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=rssimport_recache&amp;rss_import_id=all'>{$this->lang->words['rss_im_updateall']}</a></li>
				</ul>
			</th>
		</tr>	
HTML;

foreach( $content as $data )
{
$IPBHTML .= <<<HTML
		<tr>
			<td>
				<!--ACPNOTE: Missing rss.png -->
				<a target='_blank' href='{$data['rss_import_url']}'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/feed.png' border='0' alt='{$this->lang->words['rss_rss']}' style='vertical-align:top' /></a>
				<strong>{$data['rss_import_title']}</strong>
			</td>
			<td align='center'><img src='{$this->settings['skin_acp_url']}/images/{$data['_enabled_img']}' border='0' alt='***' class='ipd' /></td>
			<td align='right'>
				<img id="menu{$data['rss_import_id']}" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' border='0' alt='{$this->lang->words['rss_options']}' class='ipbmenu' />
				<ul class='acp-menu' id='menu{$data['rss_import_id']}_menucontent'>
					<li class='icon edit'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=rssimport_edit&amp;rss_import_id={$data['rss_import_id']}'>{$this->lang->words['rss_im_edit']}</a></li>
					<li class='icon delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=rssimport_delete&amp;rss_import_id={$data['rss_import_id']}");'>{$this->lang->words['rss_im_delete']}</a></li>
					<li class='icon delete'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=rssimport_remove&amp;rss_import_id={$data['rss_import_id']}'>{$this->lang->words['rss_im_remove']}</a></li>
					<li class='icon refresh'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=rssimport_recache&amp;rss_import_id={$data['rss_import_id']}'>{$this->lang->words['rss_im_update']}</a></li>
					<li class='icon manage'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=rssimport_validate&amp;rss_id={$data['rss_import_id']}'>{$this->lang->words['rss_im_validate']}</a></li>
				</ul>
			</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>	
</div>

{$page_links}

HTML;

//--endhtml--//
return $IPBHTML;
}


/**
 * Validation message
 *
 * @access	public
 * @param	array 	Validation info
 * @return	string	HTML
 */
public function rssValidateMsg( $info ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
  <span class='{$info['class']}'>{$info['msg']}</span>
EOF;

//--endhtml--//
return $IPBHTML;
}



}