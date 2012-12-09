<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Report center skin file
 * Last Updated: $Date: 2009-05-21 16:47:25 -0400 (Thu, 21 May 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 4682 $
 */
 
class cp_skin_reports extends output
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
 * Show the plugins overview page
 *
 * @access	public
 * @param	string		Content
 * @return	string		HTML
 */
public function report_plugin_overview($content) {

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['r_plugmanager']}</h2>
	<ul class='context_menu'>
		<li>
			<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;code=create_plugin' title='{$this->lang->words['r_regnew']}'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/plugin_add.png' alt='' />
				{$this->lang->words['r_regnew']}
			</a>
		</li>
	</ul>				
</div>

<div class="acp-box">
	<h3>{$this->lang->words['r_regplugins']}</h3>
	<table class="alternate_rows">
		<tr>
			<th width='35%'>{$this->lang->words['r_name']}</th>
			<th width='20%'>{$this->lang->words['r_author']}</th>
			<th width='5%' style='text-align: center'>{$this->lang->words['r_enabled']}</th>
			<th width='5%' style='text-align: center'>{$this->lang->words['r_options']}</th>
		</tr>
 		{$content}
	</table>
</div>
EOF;

return $IPBHTML;
}

/**
 * Show a report plugin row
 *
 * @access	public
 * @param	array 		Data for the plugin record
 * @return	string		HTML
 */
public function report_plugin_row( $data ) {

$IPBHTML .= <<<EOF
<tr>
	<td><strong>{$data['class_title']}
EOF;
if( $data['pversion'] )
{
$IPBHTML .= ' ' . $data['pversion'];
}
$IPBHTML .= <<<EOF
</strong><div class='desctext'>{$data['class_desc']}</td>
 <td>
EOF;
if( $data['author_url'] != '' )
{
$IPBHTML .= "<a href=\"{$data['author_url']}\" target=\"_blank\">{$data['author']}</a>";
}
else
{
$IPBHTML .= $data['author'];
}
$IPBHTML .= <<<EOF
</td>
 <td style='text-align: center'>
  <a href="{$this->settings['base_url']}&amp;{$this->form_code}&amp;code=plugin_toggle&amp;plugin_id={$data['com_id']}" title='{$this->lang->words['r_toggleendis']}'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/{$data['_enabled_img']}' border='0' alt='YN' class='ipd' /></a>
 </td>
 <td style='text-align: center'>
	<img class='ipbmenu' id="menu{$data['com_id']}" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt='{$this->lang->words['r_options']}' />
	<ul class='acp-menu' id='menu{$data['com_id']}_menucontent'>
		<li class='icon edit'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;code=edit_plugin&amp;com_id={$data['com_id']}'>{$this->lang->words['r_editsettings']}</a></li>
EOF;
if( $data['lockd'] != 1 || IN_DEV == 1 )
{
$IPBHTML .= <<<EOF
		<li class='icon edit'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;code=change_plugin&amp;com_id={$data['com_id']}'>{$this->lang->words['r_plugindetails']}</a></li>
		<li class='icon delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}&amp;{$this->form_code}&amp;code=remove_plugin&amp;com_id={$data['com_id']}");'>{$this->lang->words['r_removeplugin']}</a></li>
EOF;
}
$IPBHTML .= <<<EOF
	</ul>
 </td>
</tr>
EOF;

return $IPBHTML;
}

/**
 * Show the status overview page
 *
 * @access	public
 * @param	string		Content
 * @return	string		HTML
 */
public function report_status_overview($content) {

$IPBHTML .= <<<EOF
<script type="text/javascript">
window.onload = function() {
	Sortable.create( 'sortable_handle', { revert: true, format: 'status_([0-9]+)', onUpdate: dropItLikeItsHot, handle: 'draghandle' } );
};

dropItLikeItsHot = function( draggableObject, mouseObject )
{
	var options = {
					method : 'post',
					parameters : Sortable.serialize( 'sortable_handle', { tag: 'li', name: 'status' } )
				};
 
	new Ajax.Request( "{$this->settings['base_url']}&{$this->form_code_js}&do=reorder&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' ), options );

	return false;
};

</script>

<div class='section_title'>
	<h2>{$this->lang->words['r_statmanager']}</h2>
	
	<ul class='context_menu'>
		<li><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;code=create_status'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/page_add.png' alt=''>{$this->lang->words['r_createnew']}</a></li>
	</ul>
</div>

<div class="acp-box">
	<h3>{$this->lang->words['r_repstatsev']}</h3>
    <ul id='sortable_handle'>
    	{$content}
    </ul>
</div>
EOF;

return $IPBHTML;
}

/**
 * Show a status row
 *
 * @access	public
 * @param	array 		Status data
 * @return	string		HTML
 */
public function report_status_row( $data ) {

$IPBHTML .= <<<EOF
<li id='status_{$data['status']}'>
		<table width='100%' cellpadding='0' cellspacing='0' border='0' class='hierarchy double_pad'>
			<tr class='parent'>
				<td style='width: 2%; text-align: center'>
					<div class='draghandle'><img src='{$this->settings['skin_acp_url']}/_newimages/drag_icon.png' alt='' /></div>
				</td>
				<td style='width: 61%'>
					<strong>{$data['title']}</strong>
				</td>
				<td style='width: 34%; text-align: right'>
					<a href="{$this->settings['base_url']}&amp;{$this->form_code}&amp;code=set_status&amp;status=new&amp;id={$data['status']}" title="{$this->lang->words['r_new_report']}"><img src='{$this->settings['skin_acp_url']}/_newimages/report_new_{$data['_is_new']}.png' border='0' alt='{$this->lang->words['r_new_report']}' /></a>
					 <a href="{$this->settings['base_url']}&amp;{$this->form_code}&amp;code=set_status&amp;status=complete&amp;id={$data['status']}" title="{$this->lang->words['r_complete_report']}"><img src='{$this->settings['skin_acp_url']}/_newimages/report_complete_{$data['_is_complete']}.png' border='0' alt='{$this->lang->words['r_complete_report']}' /></a>
					 <a href="{$this->settings['base_url']}&amp;{$this->form_code}&amp;code=set_status&amp;status=active&amp;id={$data['status']}" title="{$this->lang->words['r_active_report']}"><img src='{$this->settings['skin_acp_url']}/_newimages/report_active_{$data['_is_active']}.png' border='0' alt='{$this->lang->words['r_active_report']}' /></a>
				</td>
				<td style='width: 3%'>
					<img class='ipbmenu' id="stat_menu{$data['status']}" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt='{$this->lang->words['a_options']}' />
					<ul class='acp-menu' id='stat_menu{$data['status']}_menucontent'>
						<li class='icon add'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;code=add_image&amp;id={$data['status']}'>{$this->lang->words['r_addimg']}</a></li>
						<li class='icon edit'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;code=edit_status&amp;id={$data['status']}'>{$this->lang->words['r_editstatus']}</a></li>
						<li class='icon delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}&amp;{$this->form_code}&amp;code=remove_status&amp;id={$data['status']}");'>{$this->lang->words['r_removestatus']}</a></li>
					</ul>
				</td>
			</tr>
			{$data['status_images']}
		</table>
</li>
EOF;

return $IPBHTML;
}

/**
 * Show a report status image
 *
 * @access	public
 * @param	array 		Status image data
 * @return	string		HTML
 */
public function report_status_image( $data ) {

$IPBHTML .= <<<EOF
<tr class='child'>
	<td>&nbsp;</td>
	<td style='padding-left: 20px;' colspan='3'>
		<img class='ipbmenu' id="statimg{$data['id']}" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt='{$this->lang->words['a_options']}' />
		<img src='{$this->settings['public_dir']}{$data['img']}' border='0' alt='{$data['points']}' title="{$data['points']}{$this->lang->words['r_points_sufix']}" width="{$data['width']}" height="{$data['height']}" /> {$data['points']} Points
		<ul class='acp-menu' id='statimg{$data['id']}_menucontent'>
			<li class='icon edit'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;code=edit_image&amp;id={$data['id']}'>{$this->lang->words['r_editimg']}</a></li>
			<li class='icon delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}&amp;{$this->form_code}&amp;code=remove_image&amp;id={$data['id']}");'>{$this->lang->words['r_removeimg']}</a></li>
		</ul>
	</td>
</tr>

EOF;

return $IPBHTML;
}

/**
 * Show the main overview template
 *
 * @access	public
 * @param	string		Content
 * @return	string		HTML
 */
public function overview_main_template($content) {

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['r_overview_title']}</h2>
	<ul class='context_menu'>
		<li>
			<a href='{$this->settings['_base_url']}&amp;app=core&amp;module=tools&amp;section=settings&amp;do=setting_view&amp;conf_title_keyword=warnsetup'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/cog.png' alt='' />
				{$this->lang->words['config_rc_settings']}
			</a>
		</li>
	</ul>
</div>
<div class="acp-box">
	<h3>{$this->lang->words['r_overview']}</h3>
    <table class="alternate_rows">
        <tr>
            <td class='tablerow1' width='40%'>{$this->lang->words['r_totalreports']}</td>
            <td class='tablerow2'>{$content['reports_total']}</td>
        </tr>
        <tr>
            <td class='tablerow1'>{$this->lang->words['r_totalcomments']}</td>
            <td class='tablerow2'>{$content['comments_total']}</td>
        </tr>
        <tr>
            <td class='tablerow1'>{$this->lang->words['r_activeplugins']}</td>
            <td class='tablerow2'>{$content['active_plugins']} / {$content['total_plugins']}</td>
        </tr>
    </table>
</div>
<br />
<div class="acp-box">
	<h3>{$this->lang->words['r_graphstats']}</h3>
    <table class="alternate_rows">
        <tr>
            <td>
                <img src='{$this->settings['base_url']}&amp;{$this->form_code}&amp;code=chart_report_stats' />
                <img src='{$this->settings['base_url']}&amp;{$this->form_code}&amp;code=chart_top_moderator' />
            </td>
        </tr>
    </table>
</div>
EOF;

return $IPBHTML;
}

/**
 * Show the form to add/edit a plugin
 *
 * @access	public
 * @param	array 		Plugin data
 * @return	string		HTML
 */
public function pluginForm( $plug_data=array() ) {

//-----------------------------------------
// Set some of the form variables
//-----------------------------------------

$secure_key			= ipsRegistry::getClass('adminFunctions')->getSecurityKey();

if( $plug_data['pversion'] )
{
	$plug_data['pversion'] = substr($plug_data['pversion'], 1);
}

$code = 'create_plugin';

if( $plug_data['class_title'] )
{
	$code = 'change_plugin';
}

$plug_data			= (is_array($plug_data) AND count($plug_data)) ? $plug_data : $this->request;

$form								= array();
$form['plugi_title']				= $this->registry->output->formInput('plugi_title', $plug_data['class_title']);
$form['plugi_version']				= $this->registry->output->formInput('plugi_version', $plug_data['pversion']);
$form['plugi_desc']					= $this->registry->output->formInput('plugi_desc', htmlspecialchars($plug_data['class_desc'], ENT_QUOTES));
$form['plugi_author_url']			= $this->registry->output->formInput('plugi_author_url', $plug_data['author_url']);
$form['plugi_author']				= $this->registry->output->formInput('plugi_author', $plug_data['author']);
$form['plugi_file']					= $this->registry->output->formInput('plugi_file', ( $plug_data['my_class'] ? $plug_data['my_class'] : 'default' ));

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>Report Center Plugins</h2>
</div>

<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do={$code}&amp;secure_key={$secure_key}' method='post'>
<input type='hidden' name='finish' value='1' />
<input type='hidden' name='com_id' value='{$plug_data['com_id']}' />
<div class="acp-box">
	<h3>{$this->lang->words['r_registernew']}</h3>
	<ul class='acp-form alternate_rows'>
		<li>
			<label>{$this->lang->words['r_plugintitle']}<span class="desctext">{$this->lang->words['r_whatcall']}</span></label>
			{$form['plugi_title']}
		</li>
		<li>
			<label>{$this->lang->words['r_version']}</label>
			{$form['plugi_version']}
		</li>
		<li>
			<label>{$this->lang->words['r_description']}</label>
			{$form['plugi_desc']}
		</li>
		<li>
			<label>{$this->lang->words['r_authorname']}</label>
			{$form['plugi_author']}
		</li>
		<li>
			<label>{$this->lang->words['r_authorurl']}</label>
			{$form['plugi_author_url']}
		</li>
		<li>
			<label>{$this->lang->words['r_pluginfile']}</label>
			/admin/applications/core/sources/reportPlugins/&nbsp;{$form['plugi_file']}&nbsp;.php
		</li>
	</ul>
	<div class="acp-actionbar">
    	<input type='submit' value=' {$this->lang->words['r_save']} ' class="button primary" />
	</div>
</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Add a row
 *
 * @access	public
 * @param	string		Title
 * @param	string		Description
 * @param	string		Form field
 * @return	string		HTML
 */
public function addRow( $title, $desc='', $form_field='' ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
		<li>
			<label>{$title}
EOF;

if( $desc )
{
$IPBHTML .= <<<EOF
	<span class='desctext'>{$desc}</span>
EOF;
}

$IPBHTML .= <<<EOF
        </label>
        {$form_field}
	</li>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show the form to "finish" adding the plugin
 *
 * @access	public
 * @param	array 		Plugin data
 * @param	array 		Groups that can report
 * @param	array 		Groups that can moderate
 * @param	string		Extra form data
 * @return	string		HTML
 */
public function finishPluginForm( $plug_data, $sel_can_report, $sel_group_perm, $extraForm ) {

//-----------------------------------------
// Set some of the form variables
//-----------------------------------------

$secure_key			= ipsRegistry::getClass('adminFunctions')->getSecurityKey();

foreach( $this->cache->getCache('group_cache') as $g )
{
	$groups[] = array( $g['g_id'], $g['g_title'] );
}
		
$form								= array();
$form['plugi_can_report']			= $this->registry->output->formMultiDropdown('plugi_can_report[]', $groups, $sel_can_report );
$form['plugi_gperm']				= $this->registry->output->formMultiDropdown('plugi_gperm[]', $groups, $sel_group_perm);
$form['plugi_onoff']				= $this->registry->output->formYesNo('plugi_onoff', $plug_data['onoff']);
$form['plugi_lockd']				= $this->registry->output->formYesNo('plugi_lockd', $plug_data['lockd']);
$form['plugi_enabled']				= $this->registry->output->formYesNo('plugi_onoff', $plug_data['onoff']);

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=edit_plugin&amp;secure_key={$secure_key}&amp;com_id={$plug_data['com_id']}' method='post'>
<input type='hidden' name='finish' value='1' />
<div class="acp-box">
	<h3>{$this->lang->words['r_editplugin']}</h3>
	<ul class='acp-form alternate_rows'>
		<li>
			<label>{$this->lang->words['r_groups_submit']}<span class="desctext">{$this->lang->words['r_groups_submit_info']}</span></label>
			{$form['plugi_can_report']}
		</li>
		<li>
			<label>{$this->lang->words['r_groups']}<span class="desctext">{$this->lang->words['r_groups_info']}</span></label>
			{$form['plugi_gperm']}
		</li>
		<li>
			<label>{$this->lang->words['r_pluginenabled']}</label>
			{$form['plugi_enabled']}
		</li>
EOF;

		if( IN_DEV == 1 )
		{
			$IPBHTML .= <<<EOF
		<li>
			<label>{$this->lang->words['r_safemode']}</label>
			{$form['plugi_lockd']}
		</li>
EOF;
		}
		
		$IPBHTML .= <<<EOF
		{$extraForm}
	</ul>
    <div class='acp-actionbar'>
        <input type='submit' value=' {$this->lang->words['r_save']} ' class="button primary" />
    </div>
</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Form to add/edit a status image
 *
 * @access	public
 * @param	string		Code
 * @param	array 		Status record
 * @param	array 		Image data
 * @return	string		HTML
 */
public function imageForm( $code, $status, $image_data=array() ) {

//-----------------------------------------
// Set some of the form variables
//-----------------------------------------

$secure_key			= ipsRegistry::getClass('adminFunctions')->getSecurityKey();

$image_data			= ( is_array( $image_data ) && count( $image_data ) ) ? $image_data : $this->request;

$form								= array();
$form['img_filename']				= $this->registry->output->formInput('img_filename',str_replace("#", "&#35;", $image_data['img'] ));
$form['img_width']					= $this->registry->output->formInput('img_width',$image_data['width']);
$form['img_height']					= $this->registry->output->formInput('img_height',$image_data['height']);
$form['img_points']					= $this->registry->output->formInput('img_points', $image_data['points']);

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do={$code}&amp;secure_key={$secure_key}' method='post'>
<input type='hidden' name='finish' value='1' />
<input type='hidden' name='id' value='{$image_data['id']}' />
<div class="acp-box">
	<h3>{$status['title']} : {$this->lang->words['r_confimage']}</h3>
	<ul class='acp-form alternate_rows'>
		<li>
			<label>{$this->lang->words['r_image']}<span class='desctext'>{$this->lang->words['r_image_info']}</span></label>
			{$form['img_filename']}
		</li>
		<li>
			<label>{$this->lang->words['r_width']}</label>
			{$form['img_width']}
		</li>
		<li>
			<label>{$this->lang->words['r_height']}</label>
			{$form['img_height']}
		</li>
		<li>
			<label>{$this->lang->words['r_points']}<span class='desctext'>{$this->lang->words['r_points_info']}</span></label>
			{$form['img_points']}
		</li>
	</ul>
	<div class='acp-actionbar'>
    	<input type='submit' value=' {$this->lang->words['r_save']} ' class='button primary' />
	</div>
</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Form to add/edit a status
 *
 * @access	public
 * @param	string		Code
 * @param	array 		Status data
 * @return	string		HTML
 */
public function statusForm( $code='create_status', $status=array() ) {

//-----------------------------------------
// Set some of the form variables
//-----------------------------------------

$secure_key			= ipsRegistry::getClass('adminFunctions')->getSecurityKey();

$status				= ( is_array( $status ) AND count( $status ) ) ? $status : $this->request;

$form				= array();
$form['stat_title']	= $this->registry->output->formInput( 'stat_title' ,$status['title'] );
$form['stat_ppr']	= $this->registry->output->formInput( 'stat_ppr'   ,$status['points_per_report'] );
$form['stat_pph']	= $this->registry->output->formInput( 'stat_pph'   ,$status['minutes_to_apoint'] );

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do={$code}&amp;secure_key={$secure_key}' method='post'>
<input type='hidden' name='finish' value='1' />
<input type='hidden' name='id' value='{$status['status']}' />
<div class="acp-box">
	<h3>{$this->lang->words['r_' . $code ]}</h3>
	<ul class='acp-form alternate_rows'>
		<li>
			<label>{$this->lang->words['r_name']}<span class='desctext'>{$this->lang->words['r_name_info']}</span></label>
			{$form['stat_title']}
		</li>
		<li>
			<label>{$this->lang->words['r_pointsper']}<span class='desctext'>{$this->lang->words['r_pointsper_info']}</span></label>
			{$form['stat_ppr']}
		</li>
		<li>
			<label>{$this->lang->words['r_minutes']}<span class='desctext'>{$this->lang->words['r_minutes_info']}</span></label>
			{$form['stat_pph']}
		</li>
	</ul>
	<div class='acp-actionbar'>
    	<input type='submit' value=' {$this->lang->words['r_save']} ' class="button primary"/>
	</div>
</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

}