<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * BBcode skin file
 * Last Updated: $Date: 2009-07-09 22:20:10 -0400 (Thu, 09 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 4864 $
 */
 
class cp_skin_bbcode extends output
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
 * BBCode wrapper
 *
 * @access	public
 * @param	string		Content (compiled HTML rows)
 * @return	string		HTML
 */
public function bbcodeWrapper($content) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF

<style type="text/css">
	@import url('{$this->settings['public_dir']}style_css/prettify.css');
</style>
<script type="text/javascript" src="{$this->settings['public_dir']}js/3rd_party/prettify/prettify.js"></script>
<!-- By default we load generic code, php, css, sql and xml/html; load others here if desired -->
<script type="text/javascript">
	Event.observe( window, 'load', function(e){ prettyPrint() });
</script>
	
<div class='section_title'>
	<h2>{$this->lang->words['bbcode_header']}</h2>
	<ul class='context_menu'>
		<li>
			<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=bbcode_add'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/style_add.png' alt='' />
				{$this->lang->words['addnew_bbcode']}
			</a>
		</li>
		<li>
			<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=bbcode_export'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/style_go.png' alt='' />
				{$this->lang->words['export_bbcode']}
			</a>
		</li>
		<li>
			<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=bbcode_import_all'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/style_add.png' alt='' />
				{$this->lang->words['import_bbcode_all']}
			</a>
		</li>
		<li>
			<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=bbcode_export_all'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/style_go.png' alt='' />
				{$this->lang->words['export_bbcode_all']}
			</a>
		</li>
	</ul>
</div>

<div class="acp-box">
	<h3>{$this->lang->words['your_bbcodes']}</h3>
	<table class="alternate_rows" width='100%'>
		<tr>
			<th width='45%'>{$this->lang->words['bbcode_title']}</th>
			<th width='50%'>{$this->lang->words['bbcode_tag']}</th>
			<th width='5%'>{$this->lang->words['bbcode_options']}</th>
		</tr>
		{$content}
	</table>
</div>
<br />

<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=bbcode_test' method='post'>
<div class="acp-box">
	<h3>{$this->lang->words['test_parse']}</h3>
	<p align="center"><textarea name='bbtest' rows='10' cols='70'>
EOF;

$IPBHTML .= isset($_POST['bbtest']) ? $_POST['bbtest'] : $this->lang->words['enter_test_parse'];

$IPBHTML .= <<<EOF
</textarea>
	</p>
	<div class="acp-actionbar">
		<input type='submit' value='{$this->lang->words['test_parse']}' class="button primary"/>
	</div>
</div>
</form>
<br />

<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=bbcode_import' method='post' enctype='multipart/form-data'>
<div class="acp-box">
	<h3>{$this->lang->words['import_new_bbcode']}</h3>
			<ul class="acp-form alternate_rows">
				<li>
					<label>{$this->lang->words['upload_bbcode_xml']}<span class='desctext'>{$this->lang->words['upload_bbcode_dupe']}</span></label>
					<input type='file' name='FILE_UPLOAD' />
				</li>
			<ul>
	<div class="acp-actionbar">
		<input type='submit' value='{$this->lang->words['bbcode_import']}' class="button primary" />
	</div>
</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * BBCode record
 *
 * @access	public
 * @param	array		BBCode info
 * @return	string		HTML
 */
public function bbcodeRow( $row ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
 <td>{$row['bbcode_title']}</td>
 <td><pre>{$row['bbcode_fulltag']}</pre></td>
 <td style='text-align: center'>
	<img class='ipbmenu' id="menu_{$row['bbcode_id']}" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt='{$this->lang->words['bbcode_options']}' />
	<ul class='acp-menu' id='menu_{$row['bbcode_id']}_menucontent'>
		<li class='icon edit'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=bbcode_edit&id={$row['bbcode_id']}'>{$this->lang->words['edit_bbcode']}</a></li>
		<li class='icon export'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=bbcode_export&id={$row['bbcode_id']}'>{$this->lang->words['export_bbcode']}</a></li>
EOF;

if( IN_DEV OR !$row['bbcode_protected'] )
{
$IPBHTML .= <<<EOF
		<li class='icon delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=bbcode_delete&id={$row['bbcode_id']}");'>{$this->lang->words['delete_bbcode']}</a></li>
EOF;
}

$IPBHTML .= <<<EOF
	</ul>
 </td>
</tr>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * BBCode add/edit form
 *
 * @access	public
 * @param	string		Type (add|edit)
 * @param	array 		BBcode info
 * @param	array 		Sections to edit in
 * @return	string		HTML
 */
public function bbcodeForm( $type, $bbcode, $sections ) {

//-----------------------------------------
// Set some of the form variables
//-----------------------------------------

$form_code			= $type == 'edit' ? 'bbcode_doedit' : 'bbcode_doadd';
$button				= $type == 'edit' ? $this->lang->words['edit_bbcode'] : $this->lang->words['addnew_bbcode'];
$secure_key			= ipsRegistry::getClass('adminFunctions')->getSecurityKey();

$all_groups 		= array( 0 => array ( 'all', $this->lang->words['all_groups'] ) );

foreach( $this->cache->getCache('group_cache') as $group_data )
{
	$all_groups[]	= array( $group_data['g_id'], $group_data['g_title'] );
}

$ss_dropdown		= array( 0 => array ( 'all', $this->lang->words['available_sections'] ) );

if( is_array($sections) AND count($sections) )
{
	foreach( $sections as $sect_key => $sect_value )
	{
		$ss_dropdown[]	= array( $sect_key, $sect_value );
	}
}

$form								= array();
$form['bbcode_title']				= $this->registry->output->formInput( 'bbcode_title', $this->request['bbcode_title'] ? $this->request['bbcode_title'] : $bbcode['bbcode_title'] );
$form['bbcode_desc']				= $this->registry->output->formTextarea( 'bbcode_desc', $this->request['bbcode_desc'] ? $this->request['bbcode_desc'] : $bbcode['bbcode_desc'] );
$form['bbcode_example']				= $this->registry->output->formTextarea( 'bbcode_example', $this->request['bbcode_example'] ? $this->request['bbcode_example'] : $bbcode['bbcode_example'] );
$form['bbcode_tag']					= '[ ' . $this->registry->output->formSimpleInput( 'bbcode_tag', $this->request['bbcode_tag'] ? $this->request['bbcode_tag'] : $bbcode['bbcode_tag'], 10) . ' ]';
$form['bbcode_useoption']			= $this->registry->output->formYesNo( 'bbcode_useoption', $this->request['bbcode_useoption'] ? $this->request['bbcode_useoption'] : $bbcode['bbcode_useoption'] );
$form['bbcode_switch_option']		= $this->registry->output->formYesNo( 'bbcode_switch_option', $this->request['bbcode_switch_option'] ? $this->request['bbcode_switch_option'] : $bbcode['bbcode_switch_option'] );
$form['bbcode_replace']				= $this->registry->output->formTextarea( 'bbcode_replace', htmlspecialchars($_POST['bbcode_replace'] ? $_POST['bbcode_replace'] : $bbcode['bbcode_replace']) );
$form['bbcode_menu_option_text']	= $this->registry->output->formSimpleInput( 'bbcode_menu_option_text', $this->request['bbcode_menu_option_text'] ? $this->request['bbcode_menu_option_text'] : $bbcode['bbcode_menu_option_text'], 50);
$form['bbcode_menu_content_text']	= $this->registry->output->formSimpleInput( 'bbcode_menu_content_text', $this->request['bbcode_menu_content_text'] ? $this->request['bbcode_menu_content_text'] : $bbcode['bbcode_menu_content_text'], 50);
$form['bbcode_single_tag']			= $this->registry->output->formYesNo( 'bbcode_single_tag', $this->request['bbcode_single_tag'] ? $this->request['bbcode_single_tag'] : $bbcode['bbcode_single_tag'] );
$form['bbcode_groups']				= $this->registry->output->formMultiDropdown( "bbcode_groups[]", $all_groups, $this->request['bbcode_groups'] ? $this->request['bbcode_groups'] : explode( ",", $bbcode['bbcode_groups'] ) );
$form['bbcode_sections']			= $this->registry->output->formMultiDropdown( "bbcode_sections[]", $ss_dropdown, $this->request['bbcode_sections'] ? $this->request['bbcode_sections'] : explode( ",", $bbcode['bbcode_sections'] ) );
$form['bbcode_php_plugin']			= $this->registry->output->formInput( 'bbcode_php_plugin', $this->request['bbcode_php_plugin'] ? $this->request['bbcode_php_plugin'] : $bbcode['bbcode_php_plugin'] );
$form['bbcode_no_parsing']			= $this->registry->output->formYesNo( 'bbcode_no_parsing', $this->request['bbcode_no_parsing'] ? $this->request['bbcode_no_parsing'] : $bbcode['bbcode_no_parsing'] );
$form['bbcode_protected']			= $this->registry->output->formYesNo( 'bbcode_protected', $this->request['bbcode_protected'] ? $this->request['bbcode_protected'] : $bbcode['bbcode_protected'] );
$form['bbcode_strip_search']		= $this->registry->output->formYesNo( 'bbcode_strip_search', $this->request['bbcode_strip_search'] ? $this->request['bbcode_strip_search'] : $bbcode['bbcode_strip_search'] );

$apps     = array();

/* Application drop down options */
foreach( ipsRegistry::$applications as $app_dir => $app_data )
{
	$apps[] = array( $app_dir, $app_data['app_title'] );
}
		
$form['bbcode_app']					= $this->registry->output->formDropdown( 'bbcode_app', $apps, $this->request['bbcode_app'] ? $this->request['bbcode_app'] : $bbcode['bbcode_app'] );

$form['bbcode_optional_option']		= $this->registry->output->formYesNo( 'bbcode_optional_option', $this->request['bbcode_optional_option'] ? $this->request['bbcode_optional_option'] : $bbcode['bbcode_optional_option'] );
$form['bbcode_aliases']				= $this->registry->output->formTextarea( 'bbcode_aliases', $this->request['bbcode_aliases'] ? $this->request['bbcode_aliases'] : $bbcode['bbcode_aliases'] );
$form['bbcode_image']				= $this->registry->output->formInput( 'bbcode_image', $this->request['bbcode_image'] ? $this->request['bbcode_image'] : $bbcode['bbcode_image'] );

/* Content cache is enabled? */
if ( $type == 'edit' AND IPSContentCache::isEnabled() )
{
	$_cacheCount        = IPSContentCache::count();
	$form['drop_cache'] = $this->registry->output->formYesNo( 'drop_cache', $this->request['drop_cache'] );
	
	$this->lang->words['bbcache_action'] = sprintf( $this->lang->words['bbcache_action'], $_cacheCount );
}

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['custom_bbcode_head']}</h2>
</div>

<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do={$form_code}&amp;secure_key={$secure_key}' method='post'>
<input type='hidden' name='id' value='{$bbcode['bbcode_id']}' />
EOF;

if ( $form['drop_cache'] )
{
	$IPBHTML .= <<<EOF
		<div class='warning'>
		 <h4>{$this->lang->words['bbcache_title']}</h4>
		 {$this->lang->words['bbcache_desc']}
		<p><strong>{$this->lang->words['bbcache_action']}</strong> {$form['drop_cache']}</p>
		</div>
		<br />
EOF;
}

$IPBHTML .= <<<EOF
<div class="acp-box">
	<h3>{$button}</h3>
	<ul class="acp-form alternate_rows">
		<li>
			<label>{$this->lang->words['bbcode_title']}</label>
			{$form['bbcode_title']}
		</li>
		<li>
			<label>{$this->lang->words['bbcode_description']}<span class='desctext'>{$this->lang->words['bbcode_usedinguide']}</span></label>
			{$form['bbcode_desc']}
		</li>
		<li>
			<label>{$this->lang->words['bbcode_example']}<span class='desctext'>{$this->lang->words['bbcode_usedinguide']}<br />{$this->lang->words['bbcode_example_info']}</span></label>
			{$form['bbcode_example']}
		</li>
		<li>
			<label>{$this->lang->words['bbcode_tag']}<span class='desctext'>{$this->lang->words['bbcode_tag_info']}</span></label>
			{$form['bbcode_tag']}
		</li>
		<li>
			<label>{$this->lang->words['bbcode_aliases']}<span class='desctext'>{$this->lang->words['bbcode_aliases_info']}</span></label>
			{$form['bbcode_aliases']}
		</li>
		<li>
			<label>{$this->lang->words['bbcode_singletag']}<span class='desctext'>{$this->lang->words['bbcode_singletag_info']}</span></label>
			{$form['bbcode_single_tag']}
		</li>
		<li>
			<label>{$this->lang->words['bbcode_useoption']}<span class='desctext'>{$this->lang->words['bbcode_useoption_info']}</span></label>
			{$form['bbcode_useoption']}
		</li>
		<li>
			<label>{$this->lang->words['bbcode_optional']}<span class='desctext'>{$this->lang->words['bbcode_optional_info']}</span></label>
			{$form['bbcode_optional_option']}
		</li>
		<li>
			<label>{$this->lang->words['bbcode_switch']}<span class='desctext'>{$this->lang->words['bbcode_switch_info']}</span></label>
			{$form['bbcode_switch_option']}
		</li>
		<li>
			<label>{$this->lang->words['bbcode_noparse']}<span class='desctext'>{$this->lang->words['bbcode_noparse_info']}</span></label>
			{$form['bbcode_no_parsing']}
		</li>
		<li>
			<label>{$this->lang->words['bbcode_stripsearch']}<span class='desctext'>{$this->lang->words['bbcode_stripsearch_info']}</span></label>
			{$form['bbcode_strip_search']}
		</li>
		<li>
			<label>{$this->lang->words['bbcode_replace']}<span class='desctext'>{$this->lang->words['bbcode_replace_info']}</span></label>
			{$form['bbcode_replace']}
		</li>
		<li>
			<label>{$this->lang->words['bbcode_php']}<span class='desctext'>{$this->lang->words['bbcode_php_info']}</span></label>
			{$form['bbcode_php_plugin']}<br />
			{$this->lang->words['bbcode_php_info_loc']}
		</li>
		<li>
			<label>{$this->lang->words['bbcode_groups']}<span class='desctext'>{$this->lang->words['bbcode_groups_info']}</span></label>
			{$form['bbcode_groups']}
		</li>
		<li>
			<label>{$this->lang->words['bbcode_whereused']}<span class='desctext'>{$this->lang->words['bbcode_whereused_info']}</span></label>
			{$form['bbcode_sections']}
		</li>
		<li>
			<label>{$this->lang->words['bbcode_assoc_app']}</label>
			{$form['bbcode_app']}
		</li>
		<li>
			<label>{$this->lang->words['bbcode_image']}<span class='desctext'>{$this->lang->words['bbcode_image_info']}</span></label>
			{$form['bbcode_image']}<br />
			{$this->lang->words['bbcode_image_info_loc']}
		</li>
		<li>
			<label>{$this->lang->words['bbcode_optdial']}<span class='desctext'>{$this->lang->words['bbcode_optdial_info']}</span></label>
			{$form['bbcode_menu_option_text']}
		</li>
		<li>
			<label>{$this->lang->words['bbcode_contdial']}<span class='desctext'>{$this->lang->words['bbcode_contdial_infp']}</span></label>
			{$form['bbcode_menu_content_text']}
		</li>
EOF;

if( IN_DEV )
{
	$IPBHTML .= <<<EOF
		<li>
			<label>{$this->lang->words['bbcode_protected']}<span class='desctext'>{$this->lang->words['bbcode_protected_info']}</span></label>
			{$form['bbcode_protected']}
		</li>
EOF;
}

	$IPBHTML .= <<<EOF
	</ul>
	<div class="acp-actionbar">
		<input type='submit' value=' {$button} ' class="button primary" />
	</div>
</div>	
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Media tag add/edit form
 *
 * @access	public
 * @param	string		Type (add|edit)
 * @param	array 		Tag info
 * @param	array 		Errors
 * @return	string		HTML
 */
public function mediaTagForm( $type, $data, $errors=array() ) {

//-----------------------------------------
// Set some of the form variables
//-----------------------------------------

$form_code			= $type == 'edit' ? 'domediatagedit' : 'domediatagadd';
$button				= $type == 'edit' ? $this->lang->words['media_edit'] : $this->lang->words['media_add'];
$title				= $type == 'edit' ? $this->lang->words['media_edit_replace'] : $this->lang->words['media_add_replace'];
$secure_key			= ipsRegistry::getClass('adminFunctions')->getSecurityKey();

$form								= array();
$form['mediatag_name']				= $this->registry->output->formInput( 'mediatag_name', $data['mediatag_name'], 'mediatag_name', 50  );
$form['mediatag_match']				= $this->registry->output->formInput( 'mediatag_match', $data['mediatag_match'], 'mediatag_match', 50 );
$form['mediatag_replace']			= $this->registry->output->formTextarea( 'mediatag_replace', $data['mediatag_replace']  );


$IPBHTML = "";
//--starthtml--//

if( is_array($errors) AND count($errors) )
{
	$error_string	= implode( '', $errors );
	
	$IPBHTML .= <<<EOF
	<div class='warning'><h4>{$this->lang->words['media_error']}</h4><ul>{$error_string}</ul></div><br />
EOF;
}

$IPBHTML .= <<<EOF
<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do={$form_code}&amp;secure_key={$secure_key}' method='post'>
<input type='hidden' name='id' value='{$data['mediatag_id']}' />
<div class="acp-box">
	<h3>{$title}</h3>
	<ul class="acp-form alternate_rows">
		<li>
			<label>{$this->lang->words['media_title']}<span class='desctext'>{$this->lang->words['media_title_info']}</span></label>			
			{$form['mediatag_name']}
		</li>
		<li>
			<label>{$this->lang->words['media_match']}<span class='desctext'>{$this->lang->words['media_match_info']}</span></label>
			{$form['mediatag_match']}
		</li>
		<li>
			<label>{$this->lang->words['media_html']}<span class='desctext'>{$this->lang->words['media_html_info']}</span></label>
			{$form['mediatag_replace']}
		</li>
	</ul>
	<div class='acp-actionbar'>
		<input type='submit' value=' {$button} ' class="button primary" />
	</div>
</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Media tags wrapper
 *
 * @access	public
 * @param	string		Content (compiled HTML rows)
 * @return	string		HTML
 */
public function mediaTagWrapper($content) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['media_tag_title']}</h2>
	
	<ul class='context_menu'>
		<li><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=form_add'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/add.png' alt='' /> {$this->lang->words['media_add']}</a></li>
		<li><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=mediatag_export'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/arrow_switch.png' alt='' /> {$this->lang->words['media_exports']}</a></li>
	</ul>
</div>

<div class="acp-box">
	<h3>{$this->lang->words['media_current']}</h3>
	<table class="alternate_rows" width='100%'>
		<tr>
			<th width='95%'>{$this->lang->words['media_name']}</th>
			<th width='5%'>{$this->lang->words['bbcode_options']}</th>
		</tr>
		{$content}
	</table>
</div>
<br />

<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=mediatag_import' method='post' enctype='multipart/form-data'>
<div class="acp-box">
	<h3>{$this->lang->words['media_import']}</h3>
	<ul class="acp-form alternate_rows">
		<li>
			<label>{$this->lang->words['media_upload']}<span class='desctext'>{$this->lang->words['media_upload_desc']}</span></label>
			<input type='file' name='FILE_UPLOAD' />
		</li>
	</ul>
	<div class="acp-actionbar">
		<input type='submit' value='{$this->lang->words['media_import_button']}' class="button primary" />
	</div>
</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Media tag record
 *
 * @access	public
 * @param	array		Row
 * @return	string		HTML
 */
public function mediaTagRow( $row ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
 <td>{$row['mediatag_name']}</td>
 <td align='right'>
	<img class="ipbmenu" id="menu{$row['mediatag_id']}" src="{$this->settings['skin_acp_url']}/_newimages/menu_open.png" alt="">
	<ul style="position: absolute; display: none; z-index: 9999;" class="acp-menu" id='menu{$row['mediatag_id']}_menucontent'>
		<li style="z-index: 10000;" class='icon edit'><a style="z-index: 10000;" href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=form_edit&id={$row['mediatag_id']}'>{$this->lang->words['media_edit']}</a></li>
		<li style="z-index: 10000;" class='icon export'><a style="z-index: 10000;" href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=mediatag_export&id={$row['mediatag_id']}'>{$this->lang->words['media_export']}</a></li>
		<li style="z-index: 10000;" class='icon delete'><a style="z-index: 10000;" href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=do_del&amp;id={$row['mediatag_id']}")'>{$this->lang->words['media_delete']}</a></li>
	</ul>
 </a>
</td>
</tr>
EOF;

//--endhtml--//
return $IPBHTML;
}


}