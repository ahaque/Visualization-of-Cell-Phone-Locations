<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Applications skin file
 * Last Updated: $Date: 2009-08-31 20:32:10 -0400 (Mon, 31 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 5066 $
 */
 
class cp_skin_applications extends output
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
 * Add/edit module form
 *
 * @access	public
 * @param	array 		Form elements
 * @param	string		Form title
 * @param	string		Action code
 * @param	string		Button text
 * @param	array 		Module information
 * @param	array 		Application information
 * @return	string		HTML
 */
public function module_form( $form, $title, $formcode, $button, $module, $application ) {

$IPBHTML = "";
//--starthtml--//

$title	= $formcode == module_edit_do ? $this->lang->words['module_form_edit_title'] : $this->lang->words['module_form_add_title'];

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$title}</h2>
</div>

<form id='mainform' action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=$formcode&amp;app_id={$application['app_id']}&amp;sys_module_id={$module['sys_module_id']}' method='POST'>
	<div class='acp-box'>
	<h3>{$this->lang->words['a_modules']}</h3>
 
 	<ul class='acp-form alternate_rows'>
		<li>
			<label>{$this->lang->words['a_modtype']}</label>
			{$form['sys_module_admin']}
		</li>
		<li>
			<label>{$this->lang->words['a_modtitle']}<span class='desctext'>{$this->lang->words['a_modtitle_info']}</span></label>
			{$form['sys_module_title']}
		</li>
		<li>
			<label>{$this->lang->words['a_moddesc']}<span class='desctext'>{$this->lang->words['a_moddesc_info']}</span></label>
			{$form['sys_module_description']}
		</li>
		<li>
			<label>{$this->lang->words['a_modkey']}<span class='desctext'>{$this->lang->words['a_modkey_info']}</span></label>
			{$form['sys_module_key']}
		</li>
		<li>
			<label>{$this->lang->words['a_modver']}<span class='desctext'>{$this->lang->words['a_modver_info']}</span></label>
			{$form['sys_module_version']}
		</li>
		<!--<li>
			<label>{$this->lang->words['a_modpar']}<span class='desctext'>{$this->lang->words['a_modpar_info']}</span></label>
			{$form['sys_module_parent']}
		</li>-->
		<li>
			<label>{$this->lang->words['a_moden']}<span class='desctext'>{$this->lang->words['a_moden_info']}</span></label>
			{$form['sys_module_visible']}
		</li>
EOF;
if ( IN_DEV )
{
$IPBHTML .= <<<EOF
			<li>
				<label>{$this->lang->words['a_modprot']}<span class='desctext'>{$this->lang->words['a_modprot_info']}</span></label>
				{$form['sys_module_protected']}
			</li>
EOF;
}
$IPBHTML .= <<<EOF
		</ul>
		<div class='acp-actionbar'>
			<div class='centeraction'>
				<input type='submit' value=' $button ' class='button primary' />
			</div>
		</div>
	</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * No up/down arrow
 *
 * @access	public
 * @param	int 		Module id
 * @return	string		HTML
 * @deprecated	Don't think this is used any longer
 */
public function module_position_blank($sys_module_id) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<img src='{$this->settings['skin_acp_url']}/images/spacer.gif' width='12' height='12' border='0' style='vertical-align:middle' />
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * List the modules
 *
 * @access	public
 * @param	array 		Modules
 * @param	array 		Application
 * @param	boolean		Is an admin module?
 * @return	string		HTML
 */
public function modules_list( $modules, $application, $sys_module_admin=true ) {

$IPBHTML = "";
//--starthtml--//

$_type = ( $sys_module_admin ) ? strtolower( $this->lang->words['a_admin'] ) : strtolower( $this->lang->words['a_public'] );

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$_type} {$application['app_title']} {$this->lang->words['a_modules']}</h2>
	
	<ul class='context_menu'>
		<li><a href='{$this->settings['base_url']}&amp;core&amp;module=applications&amp;section=applications&amp;do=module_add&amp;app_id={$this->request['app_id']}&amp;sys_module_admin={$this->request['sys_module_admin']}' style='text-decoration:none'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/plugin_add.png' alt='' /> {$this->lang->words['add_new_mod_' . $_type]}</a></li>
		<li><a href='{$this->settings['base_url']}&amp;core&amp;module=applications&amp;section=applications&amp;do=module_export&amp;app_id={$this->request['app_id']}&amp;sys_module_admin={$this->request['sys_module_admin']}' style='text-decoration:none'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/export.png' alt='' /> {$this->lang->words['export_mods_as_xml']}</a></li>
	</ul>
</div>

<div class='acp-box'>
	<h3>{$application['app_title']} &gt; {$_type} {$this->lang->words['a_modules']}</h3>
 	<ul id='sortable_handle' class='alternate_rows'>
EOF;

if( count( $modules['root'] ) )
{
	foreach( $modules['root'] as $module )
	{
		$IPBHTML .= <<<EOF
		<li id='module_{$module['sys_module_id']}' class='isDraggable'>
			<table width='100%' border='0' cellpadding='0' cellspacing='0' class='double_pad'>
				<tr>
					<td style='width: 3%' style='text-align: center;'>
						<div class='draghandle'><img src='{$this->settings['skin_acp_url']}/_newimages/drag_icon.png' alt='drag' /></div>
					</td>
					<td style='width: 3%' style='text-align: center'>
						<img src='{$this->settings['skin_acp_url']}/_newimages/icons/plugin.png' alt='' />
					</td>
					<td style='width: 70%'>
						<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=module_edit&amp;sys_module_id={$module['sys_module_id']}&amp;app_id={$application['app_id']}&amp;sys_module_admin=$sys_module_admin'><strong>{$module['sys_module_title']}</strong></a>
EOF;
					if( $module['sys_module_description'] )
					{
						$IPBHTML .= <<<EOF
							<br /><span class='desctext'>{$module['sys_module_description']}</span>
EOF;
					}
			
					$IPBHTML .= <<<EOF
					</td>
					<td style='width: 10%'>
						{$module['sys_module_version']}
					</td>
					<td style='width: 10%'>
						<img src='{$this->settings['skin_acp_url']}/_newimages/icons/{$module['_sys_module_visible']}' border='0' />
					</td>
					<td style='width: 3%'>
						<img class='ipbmenu' id="menu_{$module['sys_module_id']}" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt='{$this->lang->words['a_options']}' />
						<ul class='acp-menu' id='menu_{$module['sys_module_id']}_menucontent'>
EOF;
				if ( $module['sys_module_protected'] != 1 OR IN_DEV )
				{
					$IPBHTML .= <<<EOF
							<li class='icon edit'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=module_edit&amp;sys_module_id={$module['sys_module_id']}&amp;app_id={$application['app_id']}&amp;sys_module_admin=$sys_module_admin'>{$this->lang->words['a_editmod']}</a></li>
							<li class='icon delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=module_remove&amp;sys_module_id={$module['sys_module_id']}&amp;app_id={$application['app_id']}&amp;sys_module_admin=$sys_module_admin");'>{$this->lang->words['a_removemod']}</a></li>
EOF;
				}
				else
				{
					$IPBHTML .= <<<EOF
							<li class='icon view'>{$this->lang->words['a_protectedmod']}</li>
EOF;
				}

				$IPBHTML .= <<<EOF
						</ul>
					</td>
				</tr>
			</table>
		</li>
EOF;
	}
}
else 
{
	$IPBHTML .= <<<EOF
		<li class='no_items'>
			{$this->lang->words['a_nomods']}
		</li>
EOF;
}

$IPBHTML .= <<<EOF
 </ul>
</div>
<br />
<script type="text/javascript">
window.onload = function() {
	Sortable.create( 'sortable_handle', { only: 'isDraggable', revert: true, format: 'module_([0-9]+)', onUpdate: dropItLikeItsHot, handle: 'draghandle' } );
};

dropItLikeItsHot = function( draggableObject, mouseObject )
{
	var options = {
					method : 'post',
					parameters : Sortable.serialize( 'sortable_handle', { tag: 'li', name: 'modules' } )
				};

	new Ajax.Request( "{$this->settings['base_url']}&{$this->form_code_js}&do=module_manage_position&app_id={$application['app_id']}&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' ), options );

	return false;
};

</script>
<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=module_import' enctype='multipart/form-data' method='post'>
	<div class='acp-box'>
		<h3>{$this->lang->words['a_importxml']}</h3>
		
		<ul class='acp-form alternate_rows'>
			<li>
				<label>{$this->lang->words['a_uploadxml']}<span class='desctext'>{$this->lang->words['a_uploadxml_info']}</span></label>
				<input class='textinput' type='file' size='30' name='FILE_UPLOAD' />
			</li>
			<li>
				<label>{$this->lang->words['a_filexml']}<span class='desctext'>{$this->lang->words['a_filexml_info']}</span></label>
				<input class='textinput' type='text' size='30' name='file_location' />
			</li>
		</ul>
		
		<div class='acp-actionbar'>
			<div class='centeraction'>
				<input type='submit' class='button primary' value='{$this->lang->words['a_import']}' />
			</div>
		</div>
	</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Form to add/edit an application
 *
 * @access	public
 * @param	array 		Form elements
 * @param	string		Form title
 * @param	string		Action code
 * @param	string		Button text
 * @param	array 		Application information
 * @return	string		HTML
 */
public function application_form( $form, $title, $formcode, $button, $application ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['a_apps']}</h2>
</div>

<form id='mainform' action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=$formcode&amp;app_id={$application['app_id']}' method='POST'>
	<div class='acp-box'>
		<h3>$title</h3>
		<table class='form_table double_pad alternate_rows' cellspacing='0' cellpadding='0'>
			<tr>
		 		<td style='width: 40%;'>
					<label>{$this->lang->words['a_apptitle']}</label><br />
					<span class='desctext'>{$this->lang->words['a_apptitle_info']}</span>
				</td>
		 		<td style='width: 60%'>
		 			{$form['app_title']}
		 		</td>
		 	</tr>
			<tr>
		 		<td>
					<label>{$this->lang->words['a_appptitle']}</label><br />
					<span class='desctext'>{$this->lang->words['a_appptitle_info']}</span>
				</td>
		 		<td>
		 			{$form['app_public_title']}
		 		</td>
		 	</tr>
			<tr>
		 		<td>
					<label>{$this->lang->words['a_appphide']}</label><br />
					<span class='desctext'>{$this->lang->words['a_appphide_desc']}</span>
				</td>
		 		<td>
		 			{$form['app_hide_tab']}
		 		</td>
		 	</tr>
			<tr>
		 		<td>
					<label>{$this->lang->words['a_appdesc']}</label><br />
					<span class='desctext'>{$this->lang->words['a_appdesc_info']}</span>
				</td>
		 		<td>
		 			{$form['app_description']}
		 		</td>
		 	</tr>
			<tr>
		 		<td>
					<label>{$this->lang->words['a_appauthor']}</label>
				</td>
		 		<td>
		 			{$form['app_author']}
		 		</td>
		 	</tr>
			<tr>
		 		<td>
					<label>{$this->lang->words['a_appver']}</label>
				</td>
		 		<td>
		 			{$form['app_version']}
		 		</td>
		 	</tr>
			<tr>
		 		<td>
					<label>{$this->lang->words['a_appdir']}</label><br />
					<span class='desctext'>{$this->lang->words['a_appdir_info']}</span>
				</td>
		 		<td>
		 			{$form['app_directory']}
		 		</td>
		 	</tr>
			<tr>
		 		<td>
					<label>{$this->lang->words['a_appen']}</label>
				</td>
		 		<td>
		 			{$form['app_enabled']}
		 		</td>
		 	</tr>		
EOF;
if ( IN_DEV )
{
$IPBHTML .= <<<EOF
			<tr>
				<td>
					<label>{$this->lang->words['a_appprot']}</label><br />
					<span class='desctext'>{$this->lang->words['a_appprot_info']}</span>
				</td>
		 		<td>
		 			{$form['app_protected']}
		 		</td>
			</tr>
EOF;
}
$IPBHTML .= <<<EOF
		</table>
		<div class='acp-actionbar'>
				<input type='submit' value=' $button ' class='realbutton' />
		</div>
	</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}



/**
 * List the applications
 *
 * @access	public
 * @param	array 		Application
 * @param	array 		Uninstalled applications
 * @return	string		HTML
 */
public function applications_list( $applications, $uninstalled=array() ) {

$IPBHTML = "";
//--starthtml--//

if( !IPSLib::appIsInstalled('blog') OR !IPSLib::appIsInstalled('gallery') OR !IPSLib::appIsInstalled('downloads') OR !$this->settings['ips_cp_purchase'] )
{
	$IPBHTML .= <<<EOF
<div class='section_title'>
 	<h2>{$this->lang->words['purchase_additional']}</h2>
</div>
EOF;

	if( !IPSLib::appIsInstalled('blog') )
	{
		$IPBHTML .= <<<EOF
<div class='menulinkwrap'>
	<img src='{$this->settings['skin_acp_url']}/images/icon_components/blog/blog.png' border='0' alt='' style='vertical-align:bottom' />
	<a href='{$this->settings['base_url']}&amp;core&amp;module=applications&amp;section=blog' style='text-decoration:none'>IP.Blog</a>
</div>
EOF;
	}
	
	if( !IPSLib::appIsInstalled('gallery') )
	{
		$IPBHTML .= <<<EOF
<div class='menulinkwrap'>
	<img src='{$this->settings['skin_acp_url']}/images/icon_components/gallery/gallery.png' border='0' alt='' style='vertical-align:bottom' />
	<a href='{$this->settings['base_url']}&amp;core&amp;module=applications&amp;section=gallery' style='text-decoration:none'>IP.Gallery</a>
</div>
EOF;
	}

	if( !IPSLib::appIsInstalled('downloads') )
	{
		$IPBHTML .= <<<EOF
<div class='menulinkwrap'>
	<img src='{$this->settings['skin_acp_url']}/images/icon_components/downloads/downloads.png' border='0' alt='' style='vertical-align:bottom' />
	<a href='{$this->settings['base_url']}&amp;core&amp;module=applications&amp;section=downloads' style='text-decoration:none'>IP.Downloads</a>
</div>
EOF;
	}
	
	if( !$this->settings['ips_cp_purchase'] )
	{
		$IPBHTML .= <<<EOF
<div class='menulinkwrap'>
	<img src='{$this->settings['skin_acp_url']}/_newimages/icons/package.png' border='0' alt='' style='vertical-align:bottom' />
	<a href='{$this->settings['base_url']}&amp;core&amp;module=applications&amp;section=copyright' style='text-decoration:none'>Copyright Removal</a>
</div>
EOF;
	}
}

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['a_apps']}</h2>
	<ul class='context_menu'>
		<li><a href='{$this->settings['base_url']}&amp;core&amp;module=applications&amp;section=applications&amp;do=application_add'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/application_add.png' /> {$this->lang->words['a_addnewapp']}</a></li>
		<li><a href='{$this->settings['base_url']}&amp;core&amp;module=applications&amp;section=applications&amp;do=module_recache_all'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/arrow_refresh.png' /> {$this->lang->words['recache_link']}</a></li>
		<li><a href='{$this->settings['base_url']}&amp;core&amp;module=applications&amp;section=applications&amp;do=seoRebuild' title='{$this->lang->words['rebuild_furl_title']}'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/arrow_refresh.png' /> {$this->lang->words['rebuild_furl_link']}</a></li>
		<li><a href='{$this->settings['base_url']}&amp;core&amp;module=applications&amp;section=applications&amp;do=application_export'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/page_white_code.png' /> {$this->lang->words['export_xml']}</a></li>
EOF;

if( $this->settings['search_method'] == 'sphinx' )
{
	$IPBHTML .= <<<EOF
		<li><a href='{$this->settings['base_url']}&amp;core&amp;module=applications&amp;section=applications&amp;do=build_sphinx'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/cog.png' /> {$this->lang->words['build_sphinx_link']}</a></li>
EOF;
}

$IPBHTML .= <<<EOF
	</ul>
</div>

<div class='acp-box'>
 	<h3>{$this->lang->words['a_installedapps']}</h3>
	<div>
		<table width='100%' border='0' cellspacing='0' cellpadding='0'>
			<tr>
				<td class='tablesubheader' style='width: 2%'>&nbsp;</td>
				<td class='tablesubheader' style='width: 2%'>&nbsp;</td>
				<td class='tablesubheader' style='width: 38%'>{$this->lang->words['a_app']}</td>
				<td class='tablesubheader' style='width: 20%; text-align: center;'>{$this->lang->words['a_status']}</td>
				<td class='tablesubheader' style='width: 18%; text-align: center;'>{$this->lang->words['a_version']}</td>
				<td class='tablesubheader' style='width: 5%; text-align: center;'>{$this->lang->words['a_enabled']}</td>
				<td class='tablesubheader' style='width: 5%; text-align: center;'>&nbsp;</td>
			</tr>
		</table>
	</div>
EOF;

$incrementer	= 1;

foreach( $applications as $local => $apps )
{
	if( ! count( $apps ) )
	{
		continue;
	}
	
	if ( $local == 'ips' )
	{
		$app['titlePrefix'] = $this->lang->words['a_ips'];
	}
	else if ( $local == 'other' )
	{
		$app['titlePrefix'] = $this->lang->words['a_thirdparty'];
	}
	else
	{
		$app['titlePrefix'] = $this->lang->words['a_rootapps'];
	}


$IPBHTML .= <<<EOF
	<ul id='handle_{$incrementer}' class='alternate_rows'>
		<li class='tablesubsubheader'>
			<strong>{$app['titlePrefix']}</strong>
		</li>

EOF;

	foreach( $apps as $app )
	{
		$img = file_exists( IPSLib::getAppDir( $app['app_directory'] ) . '/skin_cp/appIcon.png' ) ? $this->settings['base_acp_url'] . '/' . IPSLib::getAppFolder( $app['app_directory'] ) . '/' . $app['app_directory'] . '/skin_cp/appIcon.png' : "{$this->settings['skin_acp_url']}/_newimages/applications/{$app['app_directory']}.png";
		
$IPBHTML .= <<<EOF
		<li class='isDraggable' style='width:100%;' id='app_{$app['app_id']}'>
			<table width='100%' cellpadding='0' cellspacing='0' class='double_pad'>
				<tr>
					<td style='width: 2%'>
						<div class='draghandle'><img src='{$this->settings['skin_acp_url']}/_newimages/drag_icon.png' alt='drag' /></div>
					</td>
					<td style='width: 2%'>
						<img src='{$img}' />
					</td>
					<td style='width: 38%'>
						<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=modules_overview&amp;app_id={$app['app_id']}&amp;sys_module_admin=1'><strong>{$app['titlePrefix']}{$app['app_title']}</strong></a>
					</td>
					<td style='width: 20%; text-align: center;'>
EOF;

	if ( isset( $app['_long_version'] ) && $app['_long_version'] > $app['_long_current'] )
	{
		$IPBHTML .= "<a href='{$this->settings['board_url']}/" . CP_DIRECTORY . "/upgrade/' style='color:green; font-weight: bold;'>{$this->lang->words['a_upgradeavail']}</a>";
	}
	else
	{
		$IPBHTML .= "<span class='desctext'>{$this->lang->words['a_oh_kay']}</span>";
	}
	
$IPBHTML .= <<<EOF
					</td>
					<td style='width: 18%; text-align: center;'>
						{$app['_human_current']}
					</td>
					<td style='width: 5%; text-align: center;'>
						<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=toggle_app&amp;app_id={$app['app_id']}' title='{$this->lang->words['toggle_app_enabled']}'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/{$app['_app_enabled']}' class='ipd' /></a>
					</td>
					<td style='width: 5%; text-align: center;'>
						<img class='ipbmenu' id="menu_{$app['app_id']}" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt='{$this->lang->words['a_options']}' />
						<ul class='acp-menu' id='menu_{$app['app_id']}_menucontent'>
EOF;
	if ( $app['app_protected'] != 1 OR IN_DEV )
	{
$IPBHTML .= <<<EOF
							<li class='icon delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=application_remove_splash&amp;app_id={$app['app_id']}");'>{$this->lang->words['a_removeapp']}</a></li>
							<li class='icon edit'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=application_edit&amp;app_id={$app['app_id']}'>{$this->lang->words['a_editapp']}</a></li>
EOF;
	}

$IPBHTML .= <<<EOF
							<li class='icon view'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=modules_overview&amp;app_id={$app['app_id']}&amp;sys_module_admin=1'>{$this->lang->words['a_manageadmin']}</a></li>
							<li class='icon view'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=modules_overview&amp;app_id={$app['app_id']}&amp;sys_module_admin=0'>{$this->lang->words['a_managepublic']}</a></li>
						</ul>
   					</td>
				</tr>
			</table>
		</li>
EOF;

	}


	$IPBHTML .= <<<EOF
</ul>
		<script type="text/javascript">
		dropItLikeItsHot{$incrementer} = function( draggableObject, mouseObject )
		{
			var options = {
							method : 'post',
							parameters : Sortable.serialize( 'handle_{$incrementer}', { tag: 'li', name: 'apps' } )
						};

			new Ajax.Request( "{$this->settings['base_url']}&{$this->form_code_js}&do=application_manage_position&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' ), options );

			return false;
		};

		Sortable.create( 'handle_{$incrementer}', { only: 'isDraggable', revert: true, format: 'app_([0-9]+)', onUpdate: dropItLikeItsHot{$incrementer}, handle: 'draghandle' } );

		</script>
EOF;

		$incrementer++;

}
$IPBHTML .= <<<EOF
 <div align='center' class='tablefooter'>&nbsp;</div>
</div>
EOF;

if ( is_array( $uninstalled ) AND count( $uninstalled ) )
{
$IPBHTML .= <<<EOF
<br />
<div class='acp-box'>
	<h3>{$this->lang->words['a_unapps']}</h3>
 	<table cellpadding='0' cellspacing='0' border='0' width='100%'>
 		<tr>
   			<td width='1%' class='tablesubheader'>&nbsp;</td>
   			<td width='44%' class='tablesubheader'>{$this->lang->words['a_app']}</td>
   			<td width='30%' align='center' class='tablesubheader'>{$this->lang->words['a_author']}</td>
   			<td width='15%' align='center' class='tablesubheader'>&nbsp;</td>
 		</tr>
EOF;

foreach( $uninstalled as $app )
{
	if ( strstr( $app['path'], 'applications_addon/ips' ) )
	{
		$app['titlePrefix'] = $this->lang->words['a__ips'];
		$app['_location']   = 'ips';
	}
	else if ( strstr( $app['path'], 'applications_addon/other' ) )
	{
		$app['titlePrefix'] = $this->lang->words['a__thidparty'];
		$app['_location']   = 'other';
	}
	else
	{
		$app['titlePrefix'] = $this->lang->words['a__rootapp'];
		$app['_location']   = 'root';
	}

	if ( $app['okToGo'] )
	{
		$warning = '';
		$install = <<<EOF
		<a href='{$this->settings['base_url']}&amp;module=applications&amp;section=setup&amp;do=install&amp;app_directory={$app['directory']}&amp;app_location={$app['_location']}' style='color:red'>{$this->lang->words['a_install']}</a>
EOF;
	}
	else
	{
		$install = $this->lang->words['a_cannotinstall'];
		$warning = <<<EOF
				<div class='ok-box' style='margin-top:3px'>{$this->lang->words['a_cantinstall_info']}</div>
EOF;
	}

$IPBHTML .= <<<EOF
 <tr>
   <td align='center' class='tablerow1'><img src='{$this->settings['skin_acp_url']}/images/folder_components/applications/app_row_uninstalled.png' /></td>
   <td class='tablerow1'><strong>{$app['titlePrefix']}{$app['title']}</strong>{$warning}</td>
   <td align='center' class='tablerow2'>{$app['author']}</td>
   <td align='center' class='tablerow2'>{$install}</td>
 </tr>
EOF;
}

$IPBHTML .= <<<EOF
 </table>
 <div align='center' class='tablefooter'>&nbsp;</div>
</div>
EOF;

if ( IN_DEV )
{
	$IPBHTML .= <<<EOF
<ul>
<li><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=inDevExportAll'>In Dev: EXPORT All Module XML</a></li>
	<li><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=inDevRebuildAll'>In Dev: IMPORT All Module XML</a></li>
</ul>
	
EOF;
}

}
//--endhtml--//
return $IPBHTML;
}

/**
 * Splash screen to remove an application
 *
 * @access	public
 * @param	array 		Application
 * @return	string		HTML
 */
public function application_remove_splash( $data )
{
return <<<EOF
<div class='acp-box'>
	<h3>{$this->lang->words['a_remove']} {$data['app_title']} {$this->lang->words['a_app']}</h3>

	<table class='alternate_rows' width='100%'>
		<tr>
			<td width='40%'>
				<strong>{$this->lang->words['a_currentver']}:</strong>
			</td>
			<td width='60%'>
				{$data['app_version']}
			</td>
	 	</tr>
	 	<tr>
			<td width='40%'>
				<strong>{$this->lang->words['a_author']}:</strong>
			</td>
			<td width='60%'>
				{$data['app_author']}
			</td>
	 	</tr>
	 	
	 	<tr>
	 		<th colspan='2'>{$this->lang->words['a_warning']}</th>
	 	</tr>
	 	
	 	<tr>
	 		<td colspan='2'>{$this->lang->words['a_warning_info']}</td>
	 	</tr>
	 </table>

	 <div class='acp-actionbar'>
	 	<div class='rightaction'>
			<img src='{$this->settings['skin_acp_url']}/_newimages/icons/tick.png' border='0' alt='{$this->lang->words['a_continue']}'  title='{$this->lang->words['a_continue']}' class='ipd-alt' />
			<strong><a href='{$this->settings['base_url']}{$this->form_code}&do=application_remove&app_id={$data['app_id']}'>{$this->lang->words['a_clickremove']}</a></strong>	 		
	 	</div>
	 </div>
</div>
EOF;
}

/**
 * List the hooks
 *
 * @access	public
 * @param	array 		Installed hooks
 * @param	array 		Uninstalled hooks
 * @return	string		HTML
 */
public function hooksOverview( $installedHooks, $uninstalledHooks )
{
$HTML = "";

$HTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['a_hooks']}</h2>
	
	<ul class='context_menu'>
EOF;

if ( IN_DEV )
{
	$HTML .= <<<EOF
		<li><a href='{$this->settings['base_url']}&amp;core&amp;module=applications&amp;section=hooks&amp;do=removeDeadCaches' style='text-decoration:none'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/delete.png' alt='' /> {$this->lang->words['remove_dead_caches']}</a></li>
EOF;
}


$HTML .= <<<EOF
		<li><a href='{$this->settings['base_url']}&amp;core&amp;module=applications&amp;section=hooks&amp;do=create_hook' style='text-decoration:none'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/add.png' alt='' /> {$this->lang->words['create_hook_link']}</a></li>
		<li><a href='{$this->settings['base_url']}&amp;core&amp;module=applications&amp;section=hooks&amp;do=reimport_apps' style='text-decoration:none'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/cog.png' alt='' /> {$this->lang->words['rebuild_app_hooks']}</a></li>
	</ul>
</div>

<div class='acp-box'>
 	<h3>{$this->lang->words['a_installedhooks']}</h3>
	<div>
		<table width='100%' border='0' cellspacing='0' cellpadding='0' class='double_pad'>
			<tr>
				<td class='tablesubheader' style='width: 2%'>&nbsp;</td>
				<td class='tablesubheader' style='width: 25%'>{$this->lang->words['a_hook']}</td>
				<td class='tablesubheader' style='width: 25%'>{$this->lang->words['a_author']}</td>
				<td class='tablesubheader' style='width: 25%'>{$this->lang->words['a_lastupdated']}</td>
				<td class='tablesubheader' style='width: 18%;'>{$this->lang->words['a_version']}</td>
				<td class='tablesubheader' style='width: 5%; text-align: center;'>&nbsp;</td>
			</tr>
		</table>
	</div>
	<ul id='sortable_handle' class='alternate_rows'>
EOF;

if( count( $installedHooks ) )
{
	foreach( $installedHooks as $r )
	{
		$HTML .= <<<EOF
			<li class='isDraggable' style='width:100%;' id='hook_{$r['hook_id']}'>
				<table width='100%' cellpadding='0' cellspacing='0' class='double_pad'>
					<tr>
						<td style='width: 2%'>
							<div class='draghandle'><img src='{$this->settings['skin_acp_url']}/_newimages/drag_icon.png' alt='drag' /></div>
						</td>
						<td style='width: 25%'>
EOF;
						if( IN_DEV )
						{
							$HTML .= <<<EOF
							<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=edit_hook&amp;id={$r['hook_id']}'>
								<strong>{$r['hook_name']}</strong>
							</a>
EOF;
						}
						else
						{
							$HTML .= <<<EOF
							<strong>{$r['hook_name']}</strong>
EOF;
						}
							
						if( $r['hook_desc'] )
						{
							$HTML .= <<<EOF
								<br /><span class='desctext'>{$r['hook_desc']}</span>
EOF;
						}
						
						$HTML .= <<<EOF
						</td>
						<td style='width: 25%'>
							{$r['hook_author']}
						</td>
						<td style='width: 25%'>
							{$r['_updated']}<br />
							{$r['hook_update_available']}
						</td>
						<td style='width: 18%'>
							{$r['hook_version_human']}
						</td>
						<td style='width: 5%'>
							<img class='ipbmenu' id="menu_{$r['hook_id']}" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt='{$this->lang->words['a_options']}' />
							<ul class='acp-menu' id='menu_{$r['hook_id']}_menucontent'>
								<li class='icon view'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=view_details&id={$r['hook_id']}'>{$this->lang->words['a_viewhook']}</a></li>
								<li class='icon delete'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=disable_hook&id={$r['hook_id']}'>{$this->lang->words['a_disablehook']}</a></li>
								<li class='icon delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=uninstall_hook&id={$r['hook_id']}");'>{$this->lang->words['a_uninstallhook']}</a></li>
EOF;

		if( IN_DEV )
		{
			$HTML .= <<<EOF
								<li class='icon edit'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=edit_hook&id={$r['hook_id']}'>{$this->lang->words['a_edithook']}</a></li>
								<li class='icon export'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=export_hook&id={$r['hook_id']}'>{$this->lang->words['a_exporthook']}</a></li>
EOF;
		}

		$HTML .= <<<EOF
								<li class='icon manage'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=check_requirements&id={$r['hook_id']}'>{$this->lang->words['a_checkhook']}</a></li>
							</ul>
						</td>
					</tr>
				</table>
			</li>
EOF;
	}
}
else
{
	$HTML .= <<<EOF
	<li class='no_items'>{$this->lang->words['a_nohooks']}</li>
EOF;
}

$HTML .= <<<EOF
	</ul>
</div>
<br />
<script type="text/javascript">
dropItLikeItsHot = function( draggableObject, mouseObject )
{
	var options = {
					method : 'post',
					parameters : Sortable.serialize( 'sortable_handle', { tag: 'li', name: 'hooks' } )
				};

	new Ajax.Request( "{$this->settings['base_url']}&{$this->form_code_js}&do=reorder&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' ), options );

	return false;
};

Sortable.create( 'sortable_handle', { only: 'isDraggable', revert: true, format: 'hook_([0-9]+)', onUpdate: dropItLikeItsHot, handle: 'draghandle' } );

</script>

<div class="acp-box" style='clear:both;'>
	<h3>{$this->lang->words['a_disabledhook']}</h3>
	<table class='alternate_rows' width="100%">
		<tr>
			<th width="50%">{$this->lang->words['a_hook']}</th>
			<th align="center" width="15%">{$this->lang->words['a_author']}</th>
			<th align="center" width="10%">{$this->lang->words['a_uptodate']}</th>
			<th align="center" width="10%">{$this->lang->words['a_version']}</th>
			<th align="left" width="10%">&nbsp;</th>
		</tr>
EOF;

if( count( $uninstalledHooks ) )
{
	foreach( $uninstalledHooks as $r )
	{
		$HTML .= <<<EOF
				<tr>
					<td width="35%">
						<strong>{$r['hook_name']}</strong>
						<div class='desctext'>{$r['hook_desc']}</div>
					</td>
					<td align="center" width="15%">{$r['hook_author']}</td>
					<td align="center" width="10%">{$r['hook_update_available']}</td>
					<td align="center" width="15%">{$r['hook_version_human']}</td>
					<td align="center" width="10%">
						<img class='ipbmenu' id="menu_{$r['hook_id']}" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt='{$this->lang->words['a_options']}' />
						<ul class='acp-menu' id='menu_{$r['hook_id']}_menucontent'>
							<li class='icon add'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=enable_hook&id={$r['hook_id']}'>{$this->lang->words['a_enablehook']}</a></li>
							<li class='icon view'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=view_details&id={$r['hook_id']}'>{$this->lang->words['a_viewhook']}</a></li>
							<li class='icon delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=uninstall_hook&id={$r['hook_id']}");'>{$this->lang->words['a_uninstallhook']}</a></li>
EOF;

	if( IN_DEV )
	{
		$HTML .= <<<EOF
							<li class='icon export'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=export_hook&id={$r['hook_id']}'>{$this->lang->words['a_exporthook']}</a></li>
EOF;
	}

	$HTML .= <<<EOF
							<li class='icon manage'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=check_requirements&id={$r['hook_id']}'>{$this->lang->words['a_checkhook']}</a></li>
						</ul>
					</td>
				</tr>
EOF;
	}
}
else
{
	$HTML .= <<<EOF
	<tr>
		<td colspan='7' class='no_items'>{$this->lang->words['a_nodishooks']}</td>
	</tr>
EOF;
}

$HTML .= <<<EOF
	</table>
</div>
<br />
<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=install_hook' method='post' enctype='multipart/form-data'>
	<div class="acp-box">
		<h3>{$this->lang->words['a_installhook']}</h3>
		
		<ul class='acp-form alternate_rows'>
			<li>
				<label>{$this->lang->words['a_hookxml']}</label>
				<input type='file' name='FILE_UPLOAD' />
			</li>
		<ul>
		
		<div class='acp-actionbar'>
			<div class='centeraction'>
				<input type='submit' value='{$this->lang->words['a_install']}' class='button primary' />
			</div>
		</div>
	</div>
</form>
EOF;

return $HTML;
}


/**
 * Form to add/edit a hook
 *
 * @access	public
 * @param	string		Action code
 * @param	array 		Hook data
 * @param	array 		Files in this hook
 * @return	string		HTML
 */
public function hookForm( $action, $hookData, $files=array() )
{
$HTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['a_setuphook']}</h2>
</div>

<script type='text/javascript' src='{$this->settings['js_app_url']}ipb3Hooks.js'></script>
<form name='theForm' method='post' action='{$this->settings['base_url']}{$this->form_code}' id='mainform'>
	<input type='hidden' name='do' value='{$action}' />
	<input type='hidden' name='hook_id' value='{$hookData['hook_id']}' />
	<input type='hidden' name='secure_key' value='{$this->member->form_hash}' />

		<div class='acp-box'>
			<h3>{$this->lang->words['hook_form_info']}</h3>

			<ul class='acp-form alternate_rows'>

				<li>
					<label>{$this->lang->words['hook_form_title']}<span class='desctext'>{$this->lang->words['hook_form_title_help']}</span></label>
					<input type='text' name='hook_name' value='{$hookData['hook_name']}' size='50' class='inputtext' />
		 		</li>
		 		
				<li>
					<label>{$this->lang->words['hook_form_desc']}<span class='desctext'>{$this->lang->words['hook_form_desc_help']}</span></label>
					<input type='text' name='hook_desc' value='{$hookData['hook_desc']}' size='50' class='inputtext' />
		 		</li>

				<li>
					<label>{$this->lang->words['hook_form_key']}<span class='desctext'>{$this->lang->words['hook_form_key_desc']}</span></label>
					<input type='text' name='hook_key' value='{$hookData['hook_key']}' size='50' class='inputtext' />
		 		</li>

				<li>
					<label>{$this->lang->words['hook_form_version']}<span class='desctext'>{$this->lang->words['hook_form_version_help']}</span></label>
					<input type='text' name='hook_version_human' value='{$hookData['hook_version_human']}' size='50' class='inputtext' />
		 		</li>
		 		
				<li>
					<label>{$this->lang->words['a_hookversion']}<span class='desctext'>{$this->lang->words['a_hookversion_info']}</span></label>
					<input type='text' name='hook_version_long' value='{$hookData['hook_version_long']}' size='50' class='inputtext' />
		 		</li>
		 		
				<li>
					<label>{$this->lang->words['a_hookauthor']}<span class='desctext'>{$this->lang->words['a_hookauthor_info']}</span></label>
					<input type='text' name='hook_author' value='{$hookData['hook_author']}' size='50' class='inputtext' />
		 		</li>			 		

				<li>
					<label>{$this->lang->words['a_hookemail']}<span class='desctext'>{$this->lang->words['a_hookemail_info']}</span></label>
					<input type='text' name='hook_email' value='{$hookData['hook_email']}' size='50' class='inputtext' />
		 		</li>
		 		
				<li>
					<label>{$this->lang->words['a_hooksite']}<span class='desctext'>{$this->lang->words['a_hooksite_info']}</span></label>
					<input type='text' name='hook_website' value='{$hookData['hook_website']}' size='50' class='inputtext' />
		 		</li>			 		

				<li>
					<label>{$this->lang->words['a_hookurl']}<span class='desctext'>{$this->lang->words['a_hookurl_info']}</span></label>
					<input type='text' name='hook_update_check' value='{$hookData['hook_update_check']}' size='50' class='inputtext' />
		 		</li>
	 		</ul>
		</div>
		<br />
		<div class='acp-box'>
			<h3>{$this->lang->words['a_hookrequirements']}</h3>

			<ul class='acp-form alternate_rows'>
				<li>
					<label>{$this->lang->words['a_boardversion']}<span class='desctext'>{$this->lang->words['a_enterzero']}  {$this->lang->words['a_longversionid']}</span></label>
					{$this->lang->words['a_min']}: <input type='text' name='hook_ipb_version_min' value='{$hookData['hook_requirements']['hook_ipb_version_min']}' size='50' class='inputtext' /><br />
					{$this->lang->words['a_max']}: <input type='text' name='hook_ipb_version_max' value='{$hookData['hook_requirements']['hook_ipb_version_max']}' size='50' class='inputtext' />
		 		</li>
				<li>
					<label>{$this->lang->words['a_phpver']}<span class='desctext'>{$this->lang->words['a_enterzero']}  {$this->lang->words['a_phpminmax']}</span></label>
					{$this->lang->words['a_min']}: <input type='text' name='hook_php_version_min' value='{$hookData['hook_requirements']['hook_php_version_min']}' size='50' class='inputtext' /><br />
					{$this->lang->words['a_max']}: <input type='text' name='hook_php_version_max' value='{$hookData['hook_requirements']['hook_php_version_max']}' size='50' class='inputtext' />
		 		</li>
	 		</ul>
		</div>
		<br />
		<div class='acp-box'>
			<h3>{$this->lang->words['a_hookfiles']}</h3>
			<div id='fileTableContainer'>
EOF;

			$latestIndex	= 0;

			if( count( $files ) )
			{
				foreach( $files as $index => $file )
				{
					$hook_type_command		= $file['hook_type']	== 'commandHooks' ? " selected='selected'" : '';
					$hook_type_skin			= $file['hook_type']	== 'skinHooks' ? " selected='selected'" : '';
					$hook_type_template		= $file['hook_type']	== 'templateHooks' ? " selected='selected'" : '';

					$latestIndex			= $index > $latestIndex ? $index : $latestIndex;

					$HTML .= <<<EOF
			<ul class='acp-form alternate_rows' id='fileTable_{$index}'>
				<li>
					<label>{$this->lang->words['a_filenamedir']}</label>
					<input type='text' name='file[{$index}]' value='{$file['hook_file_real']}' size='50' class='inputtext' />
				</li>

				<li>
					<label>{$this->lang->words['a_fileclassname']}</label>
					<input type='text' name='hook_classname[{$index}]' value='{$file['hook_classname']}' size='50' class='inputtext' />
				</li>

				<li>
					<label>{$this->lang->words['a_filehooktype']}</label>
					<select name='hook_type[{$index}]' id='hook_type[{$index}]' onchange='selectHookType({$index});'>
						<option value='0'>{$this->lang->words['a_selectone']}</option>
						<option value='commandHooks'{$hook_type_command}>{$this->lang->words['a_aoverloader']}</option>
						<option value='skinHooks'{$hook_type_skin}>{$this->lang->words['a_soverloader']}</option>
						<option value='templateHooks'{$hook_type_template}>Template hook</option>
					</select>
				</li>
EOF;
				if( $file['hook_type'] != 'templateHooks' )
				{
					$HTML .= <<<EOF
				<li id='tr_classToOverload[{$index}]'>
					<label>{$this->lang->words['a_classextend']}</label>							
					<input type='text' name='classToOverload[{$index}]' value='{$file['hook_data']['classToOverload']}' size='50' class='inputtext' />
				</li>
EOF;
				}
				else
				{
			 		$HTML .= <<<EOF
				<li id='tr_skinGroup[{$index}]'>
					<label>{$this->lang->words['a_skingroup']}</label>
					{$file['_skinDropdown']}
				</li>

				<li id='tr_skinFunction[{$index}]'>
					<label>{$this->lang->words['a_skinfunc']}</label>
					{$file['_templateDropdown']}
				</li>

				<li id='tr_type[{$index}]'>
					<label>{$this->lang->words['a_typeoftemp']}</label>
					{$file['_hookTypeDropdown']}
				</li>

				<li id='tr_id[{$index}]'>
					<label>{$this->lang->words['a_hookid']}</label>
					{$file['_hookIdsDropdown']}
				</li>

				<li id='tr_position[{$index}]'>
					<label>{$this->lang->words['a_hookloc']}</label>
					{$file['_hookEPDropdown']}
				</li>
EOF;
				}

				$HTML .= <<<EOF
			</ul>
EOF;
				}
			}
			$HTML .= <<<EOF
			</div>
			<div class='acp-actionbar'>
				<div class='centeraction'>
					<input type='button' value=' {$this->lang->words['a_addanother']} ' onclick='addAnotherFile()' class='button primary' />
				</div>
			</div>
			<script type='text/javascript'>
				elementIndex = {$latestIndex};
			</script>
			
			<br />
			<div class='acp-actionbar'>
				<div class='centeraction'>
					<input type='submit' value='{$this->lang->words['hook_form_button']}' class='button primary' />
				</div>
			</div>
		</div>
</form>

EOF;

return $HTML;
}


/**
 * Show the hook details
 *
 * @access	public
 * @param	array 		Hook data
 * @param	array 		Files in this hook
 * @return	string		HTML
 */
public function hookDetails( $hookData, $files=array() )
{
$HTML .= <<<EOF

<div class='acp-box'>
	<h3>{$hookData['hook_name']}</h3>
	<table class='alternate_rows' width='100%'>
		<tr>
			<th colspan='2'>{$hookData['hook_desc']}</th>
		</tr>
		<tr>
			<td width='40%' valign='top'>
				<b>{$this->lang->words['a_hookver']}</b>
			</td>
			<td width='60%' valign='top'>
				{$hookData['hook_version_human']}
			</td>
 		</tr>
		<tr>
			<td width='40%' valign='top'>
				<b>{$this->lang->words['a_hookauthor']}</b>
			</td>
			<td width='60%' valign='top'>
				{$hookData['hook_author']}
			</td>
 		</tr>
EOF;

	if( $hookData['hook_email'] )
	{
		$HTML .= <<<EOF
		<tr>
			<td width='40%' valign='top'>
				<b>{$this->lang->words['a_hookemail']}</b>
			</td>
			<td width='60%' valign='top'>
				<a href='mailto:{$hookData['hook_email']}'>{$hookData['hook_email']}</a>
			</td>
 		</tr>
EOF;
	}

	if( $hookData['hook_website'] )
	{
		$HTML .= <<<EOF
		<tr>
			<td width='40%' valign='top'>
				<b>{$this->lang->words['a_authorsite']}</b>
			</td>
			<td width='60%' valign='top'>
				<a href='{$hookData['hook_website']}'>{$hookData['hook_website']}</a>
			</td>
 		</tr>
EOF;
	}

	$HTML .= <<<EOF
		</table>
	</div>
	<br />
EOF;

	if( $hookData['hook_requirements']['hook_ipb_version_min'] > 0 OR $hookData['hook_requirements']['hook_ipb_version_max'] > 0
		OR $hookData['hook_requirements']['hook_php_version_min'] OR $hookData['hook_requirements']['hook_php_version_max'] )
	{
		$HTML .= <<<EOF
	<div class='acp-box'>
		<h3>{$this->lang->words['a_hookrequirements']}</h3>

		<table class='acp-form alternate_rows' width='100%'>
EOF;

		if( $hookData['hook_requirements']['hook_ipb_version_min'] > 0 OR $hookData['hook_requirements']['hook_ipb_version_max'] > 0 )
		{
			$hookData['hook_requirements']['hook_ipb_version_min'] = $hookData['hook_requirements']['hook_ipb_version_min'] ? $hookData['hook_requirements']['hook_ipb_version_min'] : 'No minimum';
			$hookData['hook_requirements']['hook_ipb_version_max'] = $hookData['hook_requirements']['hook_ipb_version_max'] ? $hookData['hook_requirements']['hook_ipb_version_max'] : 'No maximum';

			$HTML .= <<<EOF
			<tr>
				<td width='40%' valign='top'>
					<b>{$this->lang->words['a_boardversion']}</b>
				</td>
				<td width='60%' valign='top'>
					{$this->lang->words['a_minimum']}: {$hookData['hook_requirements']['hook_ipb_version_min']}<br />
					{$this->lang->words['a_maximum']}: {$hookData['hook_requirements']['hook_ipb_version_max']}
				</td>
	 		</tr>
EOF;
		}

		if( $hookData['hook_requirements']['hook_php_version_min'] OR $hookData['hook_requirements']['hook_php_version_max'] )
		{
			$hookData['hook_requirements']['hook_php_version_min'] = $hookData['hook_requirements']['hook_php_version_min'] ? $hookData['hook_requirements']['hook_php_version_min'] : 'No minimum';
			$hookData['hook_requirements']['hook_php_version_max'] = $hookData['hook_requirements']['hook_php_version_max'] ? $hookData['hook_requirements']['hook_php_version_max'] : 'No maximum';

			$HTML .= <<<EOF
			<tr>
				<td width='40%' valign='top'>
					<b>{$this->lang->words['a_phpver']}</b>
				</td>
				<td width='60%' valign='top'>
					{$this->lang->words['a_minimum']}: {$hookData['hook_requirements']['hook_php_version_min']}<br />
					{$this->lang->words['a_maximum']}: {$hookData['hook_requirements']['hook_php_version_max']}
				</td>
	 		</tr>
EOF;
		}

		$HTML .= <<<EOF
 		</table>
	</div>
	<br />
EOF;
	}

	$HTML .= <<<EOF
	<div class='acp-box'>
		<h3>{$this->lang->words['a_fileuses']}</h3>
		<table class='alternate_rows' width='100%'>
			<tr>
				<th width='20%'>{$this->lang->words['a_realfile']}</th>
				<th width='20%'>{$this->lang->words['a_storedfile']}</th>
				<th width='10%'>{$this->lang->words['a_filehooktype']}</th>
				<th width='50%'>{$this->lang->words['a_wherehook']}</th>
			</tr>
EOF;

		foreach( $files as $index => $data )
		{
			$showsAt	= "";

			if( $data['hook_type'] == 'templateHooks' )
			{
				$showsAt = $this->lang->words['a_showsin'] . $data['hook_data']['skinGroup'] . ' -&gt; ' . $data['hook_data']['skinFunction'] . ' ';

				if( $data['hook_data']['type'] == 'if' )
				{
					switch( $data['hook_data']['position'] )
					{
						case 'pre.startif':
							$showsAt .= $this->lang->words['a_prestartif'];
						break;

						case 'post.startif':
							$showsAt .= $this->lang->words['a_poststartif'];
						break;

						case 'pre.else':
							$showsAt .= $this->lang->words['a_preelse'];
						break;

						case 'post.else':
							$showsAt .= $this->lang->words['a_postelse'];
						break;

						case 'pre.endif':
							$showsAt .= $this->lang->words['a_preendif'];
						break;

						case 'post.endif':
							$showsAt .= $this->lang->words['a_postendif'];
						break;
					}
				}
				else
				{
					switch( $data['hook_data']['position'] )
					{
						case 'outer.pre':
							$showsAt .= $this->lang->words['a_outerpre'];
						break;

						case 'inner.pre':
							$showsAt .= $this->lang->words['a_innerpre'];
						break;

						case 'inner.post':
							$showsAt .= $this->lang->words['a_innerpost'];
						break;

						case 'outer.post':
							$showsAt .= $this->lang->words['a_outerpost'];
						break;
					}
				}

				$showsAt .= $this->lang->words['a_labeled'] . $data['hook_data']['id'];
			}
			else
			{
				$showsAt = $this->lang->words['a_willoverload'] . $data['hook_data']['classToOverload'];
			}

			switch( $data['hook_type'] )
			{
				case 'templateHooks':
					$hookType = $this->lang->words['a_templatehook'];
				break;
				case 'commandHooks':
					$hookType = $this->lang->words['a_aoverloader'];
				break;
				case 'skinHooks':
					$hookType = $this->lang->words['a_soverloader'];
				break;
			}

			$HTML .= <<<EOF
			<tr>
				<td>{$data['hook_file_real']}</td>
				<td>{$data['hook_file_stored']}</td>
				<td>{$hookType}</td>
				<td>{$showsAt}</td>
	 		</tr>
EOF;
		}
		$HTML .= <<<EOF
 	</table>
</div>

EOF;

return $HTML;
}

/**
 * Show the hook requirements
 *
 * @access	public
 * @param	array 		Hook data
 * @param	boolean		True if you don't meet IPB min requirements, otherwise false
 * @param	boolean		True if you don't meet PHP min requirements, otherwise false
 * @return	string		HTML
 */
public function hookRequirements( $hookData, $hookIpbBad, $hookPhpBad )
{
	if( $hookIpbBad OR $hookPhpBad )
	{
		$HTML .= $this->registry->output->global_template->warning_box( $hookData['hook_name'], $this->lang->words['a_noyoucant'] ) . '<br />';
	}

$HTML .= <<<EOF

<div class='acp-box'>
	<h3>{$hookData['hook_name']}</h3>
	<table class='alternate_rows' width='100%'>
		<tr><th colspan='2'>&nbsp;</th></tr>
EOF;

	if( $hookData['hook_requirements']['hook_ipb_version_min'] )
	{
		$HTML .= <<<EOF
		<tr>
			<td><strong>{$this->lang->words['a_minipb']}</strong></td>
			<td>{$hookData['hook_requirements']['hook_ipb_version_min']}</td>
		</tr>
EOF;
	}

	if( $hookData['hook_requirements']['hook_ipb_version_max'] )
	{
		$HTML .= <<<EOF
		<tr>
			<td><strong>{$this->lang->words['a_maxipb']}</strong></td>
			<td>{$hookData['hook_requirements']['hook_ipb_version_max']}</td>
		</tr>
EOF;
	}

	if( $hookData['hook_requirements']['hook_ipb_version_min'] OR $hookData['hook_requirements']['hook_ipb_version_max'] )
	{
		$HTML .= <<<EOF
		<tr>
			<td><strong>{$this->lang->words['a_youripb']}</strong></td>
			<td>
EOF;

$HTML .= IPB_VERSION;

$hookIpbBad	= $hookIpbBad ? "<span style='color:red;font-weight:bold;'>" . $hookIpbBad . "</span>" : "<span style='color:green;'>{$this->lang->words['a_willworkipb']}</span>";

$HTML .= <<<EOF
			</td>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['a_ipbstatus']}</strong></td>
			<td>{$hookIpbBad}</td>
		</tr>
EOF;
	}

	if( $hookData['hook_requirements']['hook_php_version_min'] )
	{
		$HTML .= <<<EOF
		<tr>
			<td><strong>{$this->lang->words['a_minphp']}</strong></td>
			<td>{$hookData['hook_requirements']['hook_php_version_min']}</td>
		</tr>
EOF;
	}

	if( $hookData['hook_requirements']['hook_php_version_max'] )
	{
		$HTML .= <<<EOF
		<tr>
			<td><strong>{$this->lang->words['a_maxphp']}</strong></td>
			<td>{$hookData['hook_requirements']['hook_php_version_max']}</td>
		</tr>
EOF;
	}

	if( $hookData['hook_requirements']['hook_php_version_min'] OR $hookData['hook_requirements']['hook_php_version_max'] )
	{
		$HTML .= <<<EOF
		<tr>
			<td><strong>{$this->lang->words['a_yourphp']}</strong></td>
			<td>
EOF;

$HTML .= PHP_VERSION;

$hookPhpBad	= $hookPhpBad ? "<span style='color:red;font-weight:bold;'>" . $hookPhpBad . "</span>" : "<span style='color:green;'>{$this->lang->words['a_willworkphp']}</span>";

$HTML .= <<<EOF
			</td>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['a_phpstatus']}</strong></td>
			<td>{$hookPhpBad}</td>
		</tr>
EOF;
	}

	$HTML .= <<<EOF
 	</table>
</div>

EOF;

return $HTML;
}

/**
 * Page to export a hook
 *
 * @access	public
 * @param	array 		Hook data
 * @return	string		HTML
 */
public function hooksExport( $hookData )
{

$hookData['hook_extra_data']['display']['settings']		= $hookData['hook_extra_data']['display']['settings'] ? $hookData['hook_extra_data']['display']['settings'] : $this->lang->words['a_nosettings'];
$hookData['hook_extra_data']['display']['modules']		= $hookData['hook_extra_data']['display']['modules'] ? $hookData['hook_extra_data']['display']['modules'] : $this->lang->words['a_nomodules'];
$hookData['hook_extra_data']['display']['help']			= $hookData['hook_extra_data']['display']['help'] ? $hookData['hook_extra_data']['display']['help'] : $this->lang->words['a_nohelp'];
$hookData['hook_extra_data']['display']['acphelp']		= $hookData['hook_extra_data']['display']['acphelp'] ? $hookData['hook_extra_data']['display']['acphelp'] : $this->lang->words['a_noacp'];
$hookData['hook_extra_data']['display']['tasks']		= $hookData['hook_extra_data']['display']['tasks'] ? $hookData['hook_extra_data']['display']['tasks'] : $this->lang->words['a_notasks'];
$hookData['hook_extra_data']['display']['database']		= $hookData['hook_extra_data']['display']['database'] ? $hookData['hook_extra_data']['display']['database'] : $this->lang->words['a_nodbchanges'];
$hookData['hook_extra_data']['display']['custom']		= $hookData['hook_extra_data']['display']['custom'] ? $hookData['hook_extra_data']['display']['custom'] : $this->lang->words['a_noinun'];

$hookData['hook_extra_data']['display']['language']		= $hookData['hook_extra_data']['display']['language'] ? $hookData['hook_extra_data']['display']['language'] : $this->lang->words['a_nolang'];
$hookData['hook_extra_data']['display']['templates']	= $hookData['hook_extra_data']['display']['templates'] ? $hookData['hook_extra_data']['display']['templates'] : $this->lang->words['a_noskin'];

$HTML .= <<<EOF

<h2>Exporting {$hookData['hook_name']}</h2>
<script type='text/javascript' src='{$this->settings['js_main_url']}acp.inlineforms.js'></script>
<script type='text/javascript' src='{$this->settings['js_main_url']}acp.hooks.js'></script>

<script type='text/javascript'>
	acp.hooks.hookID = {$hookData['hook_id']};
</script>

<form name='theForm' method='post' action='{$this->settings['base_url']}{$this->form_code}' id='mainform'>
	<input type='hidden' name='do' value='do_export_hook' />
	<input type='hidden' name='id' value='{$hookData['hook_id']}' />
	<input type='hidden' name='secure_key' value='{$this->member->form_hash}' />

	<div class='acp-box'>
		<h3>{$this->lang->words['a_exportsettings']}</h3>
		
		<table class='alternate_rows' width='100%'>
			<tr><th>{$this->lang->words['a_settings_info']}</th></tr>
			<tr>
				<td>
					<p id='MF__settings'>{$hookData['hook_extra_data']['display']['settings']}</p>
					<div id='MF__settings_popup' class='form_help' style='width:auto;text-align:center;cursor:pointer'>{$this->lang->words['a_addsettings']}</div>
				</td>
 			</tr>
 		</table>
		<script type='text/javascript'>
			$('MF__settings_popup').observe('click', acp.hooks.exportHook.bindAsEventListener( this, 'MF__settings', "{$this->lang->words['a_addsettings']}", "app=core&amp;module=ajax&amp;section=hooks&amp;do=show&amp;name=settings&amp;id={$hookData['hook_id']}" ) );
		</script>
 	</div>
 	<br />

	<div class='acp-box'>
		<h3>{$this->lang->words['a_exportlang']}</h3>
		
		<table class='alternate_rows' width='100%'>
			<tr><th>{$this->lang->words['a_lang_info']}</th></tr>
			<tr>
				<td valign='top'>
					<p id='MF__language'>{$hookData['hook_extra_data']['display']['language']}</p>
					<div id='MF__language_popup' class='form_help' style='width:auto;text-align:center;cursor:pointer'>{$this->lang->words['a_addlang']}</div>
				</td>
	 		</tr>
	 	</table>
		<script type='text/javascript'>
			$('MF__language_popup').observe('click', acp.hooks.exportHook.bindAsEventListener( this, 'MF__language', "{$this->lang->words['a_addlang']}", "app=core&amp;module=ajax&amp;section=hooks&amp;do=show&amp;name=languages&amp;id={$hookData['hook_id']}" ) );
		</script>
	 </div>
	 <br />

	<div class='acp-box'>
		<h3>{$this->lang->words['a_exportmod']}</h3>
		
		<table class='alternate_rows' width='100%'>
			<tr><th>{$this->lang->words['a_mod_info']}</th></tr>
			<tr>
				<td valign='top'>
					<p id='MF__modules'>{$hookData['hook_extra_data']['display']['modules']}</p>
					<div id='MF__modules_popup' class='form_help' style='width:auto;text-align:center;cursor:pointer'>{$this->lang->words['a_addmod']}</div>
				</td>
	 		</tr>
	 	</table>
		<script type='text/javascript'>
			$('MF__modules_popup').observe('click', acp.hooks.exportHook.bindAsEventListener( this, 'MF__modules', "{$this->lang->words['a_addmod']}", "app=core&amp;module=ajax&amp;section=hooks&amp;do=show&amp;name=modules&amp;id={$hookData['hook_id']}" ) );
		</script>
	 </div>
	 <br />

	<div class='acp-box'>
		<h3>{$this->lang->words['a_exporthelp']}</h3>
		
		<table class='alternate_rows' width='100%'>
			<tr><th>{$this->lang->words['a_help_info']}</th></tr>
			<tr>
				<td valign='top'>
					<p id='MF__help'>{$hookData['hook_extra_data']['display']['help']}</p>
					<div id='MF__help_popup' class='form_help' style='width:auto;text-align:center;cursor:pointer'>{$this->lang->words['a_addhelp']}</div>
				</td>
	 		</tr>
	 	</table>
		<script type='text/javascript'>
			$('MF__help_popup').observe('click', acp.hooks.exportHook.bindAsEventListener( this, 'MF__help', "{$this->lang->words['a_addhelp']}", "app=core&amp;module=ajax&amp;section=hooks&amp;do=show&amp;name=help&amp;id={$hookData['hook_id']}" ) );
		</script>
	 </div>
	 <br />


	<div class='acp-box'>
		<h3>{$this->lang->words['a_exportskin']}</h3>
		
		<table class='alternate_rows' width='100%'>
			<tr><th>{$this->lang->words['a_skin_info']}</th></tr>
			<tr>
				<td valign='top'>
					<p id='MF__templates'>{$hookData['hook_extra_data']['display']['templates']}</p>
					<div id='MF__templates_popup' class='form_help' style='width:auto;text-align:center;cursor:pointer'>{$this->lang->words['a_addskin']}</div>
				</td>
	 		</tr>
	 	</table>
		<script type='text/javascript'>
			$('MF__templates_popup').observe('click', acp.hooks.exportHook.bindAsEventListener( this, 'MF__templates', "{$this->lang->words['a_addskin']}", "app=core&amp;module=ajax&amp;section=hooks&amp;do=show&amp;name=skins&amp;id={$hookData['hook_id']}" ) );
		</script>
	 </div>
	 <br />

	<div class='acp-box'>
		<h3>{$this->lang->words['a_exporttasks']}</h3>
		
		<table class='alternate_rows' width='100%'>
			<tr><th>{$this->lang->words['a_tasks_info']}</th></tr>
			<tr>
				<td valign='top'>
					<p id='MF__tasks'>{$hookData['hook_extra_data']['display']['tasks']}</p>
					<div id='MF__tasks_popup' class='form_help' style='width:auto;text-align:center;cursor:pointer'>{$this->lang->words['a_addtasks']}</div>
				</td>
	 		</tr>
	 	</table>
		<script type='text/javascript'>
			$('MF__tasks_popup').observe('click', acp.hooks.exportHook.bindAsEventListener( this, 'MF__tasks', "{$this->lang->words['a_addtasks']}", "app=core&amp;module=ajax&amp;section=hooks&amp;do=show&amp;name=tasks&amp;id={$hookData['hook_id']}" ) );
		</script>
	 </div>
	 <br />

	<div class='acp-box'>
		<h3>{$this->lang->words['a_exportdb']}</h3>
		
		<table class='alternate_rows' width='100%'>
			<tr><th>{$this->lang->words['a_db_info']}</th></tr>
			<tr>
				<td valign='top'>
					<p id='MF__database'>{$hookData['hook_extra_data']['display']['database']}</p>
					<div id='MF__database_popup' class='form_help' style='width:auto;text-align:center;cursor:pointer'>{$this->lang->words['a_adddb']}</div>
				</td>
	 		</tr>
	 	</table>
		<script type='text/javascript'>
			$('MF__database_popup').observe('click', acp.hooks.exportHook.bindAsEventListener( this, 'MF__database', "{$this->lang->words['a_adddb']}", "app=core&amp;module=ajax&amp;section=hooks&amp;do=show&amp;name=database&amp;id={$hookData['hook_id']}" ) );
		</script>
	 </div>
	 <br />

	<div class='acp-box'>
		<h3>{$this->lang->words['a_exportscript']}</h3>
		
		<table class='alternate_rows' width='100%'>
			<tr><th>{$this->lang->words['a_script_info']}</th></tr>
			<tr>
				<td valign='top'>
					<p id='MF__custom'>{$hookData['hook_extra_data']['display']['custom']}</p>
					<div id='MF__custom_popup' class='form_help' style='width:auto;text-align:center;cursor:pointer'>{$this->lang->words['a_addscript']}</div>
				</td>
	 		</tr>
	 	</table>
		<script type='text/javascript'>
			$('MF__custom_popup').observe('click', acp.hooks.exportHook.bindAsEventListener( this, 'MF__custom', "{$this->lang->words['a_addscript']}", "app=core&amp;module=ajax&amp;section=hooks&amp;do=show&amp;name=custom&amp;id={$hookData['hook_id']}" ) );
		</script>
	 </div>
	 <br />
	 <div class='acp-box'>
		<div class='acp-actionbar'>
			<div class='centeraction'>
				<input type='submit' value='{$this->lang->words['a_export']}' class='button primary'/>
			</div>
		</div>
	</div>
</form>
EOF;

return $HTML;
}

}