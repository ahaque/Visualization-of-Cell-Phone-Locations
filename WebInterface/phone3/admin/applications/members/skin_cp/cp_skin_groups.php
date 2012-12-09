<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * ACP groups skin file
 * Last Updated: $Date: 2009-07-31 10:40:13 -0400 (Fri, 31 Jul 2009) $
 *
 * @author 		$Author: josh $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Members
 * @link		http://www.
 * @since		20th February 2002
 * @version		$Rev: 4958 $
 *
 */
 
class cp_skin_groups extends output
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
 * Should this admin group get ACP restrictions?
 *
 * @access	public
 * @return	string		HTML
 */
public function groupAdminConfirm( $group_id ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<!--SKINNOTE: Not yet skinned-->
<div class='tableborder'>
 <div class='tableheaderalt'>{$this->lang->words['sm_configrest']}</div>
	<div class='tablerow1'>
	{$this->lang->words['sm_detectacp']}
 		<br /><br />
 		{$this->lang->words['sm_setrestrict']}
 	</div>
  	<div class='tablesubheader' align='center'>
  		<span class='fauxbutton'><a href='{$this->settings['base_url']}&amp;{$this->form_code}'>{$this->lang->words['sm_nothanks']}</a></span>&nbsp;&nbsp;
  		<span class='fauxbutton'><a href='{$this->settings['base_url']}&amp;module=restrictions&amp;section=restrictions&amp;do=acpperms-group-add-complete&amp;entered_group={$group_id}'>{$this->lang->words['sm_yesplease']}</a></span>
	</div>
</div>
<br />
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Group form
 *
 * @access	public
 * @param	string		Type (add|edit)
 * @param	array 		Group data
 * @param	array 		Permission masks
 * @param	array 		Extra tabs
 * @return	string		HTML
 */
public function groupsForm( $type, $group, $permission_masks, $content=array() ) {

//-----------------------------------------
// Format some of the data
//-----------------------------------------

list($group['g_promotion_id'], $group['g_promotion_posts'])	= explode( '&', $group['g_promotion'] );
list($p_max, $p_width, $p_height) 							= explode( ":", $group['g_photo_max_vars'] );
list( $limit, $flood ) 										= explode( ":", $group['g_email_limit'] );

if ($group['g_promotion_posts'] < 1)
{
	$group['g_promotion_posts'] = '';
}

if( $type == 'edit' AND $group['g_attach_max'] == 0 )
{
	$group['g_attach_maxdis'] = $this->lang->words['g_unlimited'];
}
else if( $type == 'edit' AND $group['g_attach_max'] == -1 )
{
	$group['g_attach_maxdis'] = $this->lang->words['g_disabled'];
}
else
{
	$group['g_attach_maxdis'] = IPSLib::sizeFormat( $group['g_attach_max'] * 1024 );
}

if( $type == 'edit' AND $group['g_attach_per_post'] == 0 )
{
	$group['g_attach_per_postdis'] = $this->lang->words['g_unlimited'];
}
else if( $type == 'edit' AND $group['g_attach_per_post'] == -1 )
{
	$group['g_attach_per_postdis'] = $this->lang->words['g_disabled'];
}
else
{
	$group['g_attach_per_postdis'] = IPSLib::sizeFormat( $group['g_attach_per_post'] * 1024 );
}
		
//-----------------------------------------
// Set some of the form variables
//-----------------------------------------

$form_code			= $type == 'edit' ? 'doedit' : 'doadd';
$button				= $type == 'edit' ? $this->lang->words['g_compedit'] : $this->lang->words['g_addgroup'];
$ini_max			= @ini_get( 'upload_max_filesize' ) ? @ini_get( 'upload_max_filesize' ) : $this->lang->words['g_cannotobt'];
$guest_legend		= $group['g_id'] == $this->settings['guest_group'] ? $this->lang->words['g_appguests'] : '';
$secure_key			= ipsRegistry::getClass('adminFunctions')->getSecurityKey();

//-----------------------------------------
// Start off the form fields
//-----------------------------------------

$all_groups 		= array( 0 => array ( 'none', $this->lang->words['g_dontprom'] ) );

foreach( $this->cache->getCache('group_cache') as $group_data )
{
	$all_groups[]	= array( $group_data['g_id'], $group_data['g_title'] );
}

$gbw_unit_type      = array(
							 0 => array( 0, $this->lang->words['g_dd_apprp'] ),
							 1 => array( 1, $this->lang->words['g_dd_days'] ) );


$form							= array();
$form['g_title']				= $this->registry->output->formInput( "g_title", $group['g_title'] );
$form['permid']					= $this->registry->output->formMultiDropdown( "permid[]", $permission_masks, explode( ",", $group['g_perm_id'] ) );
$form['g_icon']					= $this->registry->output->formTextarea( "g_icon", htmlspecialchars( $group['g_icon'], ENT_QUOTES ) );
$form['prefix']					= $this->registry->output->formInput( "prefix", htmlspecialchars( $group['prefix'], ENT_QUOTES ) );
$form['suffix']					= $this->registry->output->formInput( "suffix", htmlspecialchars( $group['suffix'], ENT_QUOTES ) );
$form['g_hide_from_list']		= $this->registry->output->formYesNo( "g_hide_from_list", $group['g_hide_from_list'] );
$form['g_attach_max']			= $this->registry->output->formInput( "g_attach_max", $group['g_attach_max'] );
$form['g_attach_per_post']		= $this->registry->output->formInput( "g_attach_per_post", $group['g_attach_per_post'] );
$form['p_max']					= $this->registry->output->formInput( "p_max", $p_max );
$form['p_width']				= $this->registry->output->formSimpleInput( "p_width", $p_width, 3 );
$form['p_height']				= $this->registry->output->formSimpleInput( "p_height", $p_height, 3 );
$form['g_avatar_upload']		= $this->registry->output->formYesNo( "g_avatar_upload", $group['g_avatar_upload'] );
$form['g_can_msg_attach']		= $this->registry->output->formYesNo( "g_can_msg_attach", $group['g_can_msg_attach'] );
$form['g_view_board']			= $this->registry->output->formYesNo( "g_view_board", $group['g_view_board'] );
$form['g_access_offline']		= $this->registry->output->formYesNo( "g_access_offline", $group['g_access_offline'] );
$form['g_mem_info']				= $this->registry->output->formYesNo( "g_mem_info", $group['g_mem_info'] );
$form['g_can_add_friends']		= $this->registry->output->formYesNo( "g_can_add_friends", $group['g_can_add_friends'] );
$form['g_hide_online_list']		= $this->registry->output->formYesNo( "g_hide_online_list", $group['g_hide_online_list'] );
$form['g_use_search']			= $this->registry->output->formYesNo( "g_use_search", $group['g_use_search'] );
$form['g_search_flood']			= $this->registry->output->formInput( "g_search_flood", $group['g_search_flood'] );
$form['g_email_friend']			= $this->registry->output->formYesNo( "g_email_friend", $group['g_email_friend'] );
$form['join_limit']				= $this->registry->output->formSimpleInput( "join_limit", $limit, 2 );
$form['join_flood']				= $this->registry->output->formSimpleInput( "join_flood", $flood, 2 );
$form['g_edit_profile']			= $this->registry->output->formYesNo( "g_edit_profile", $group['g_edit_profile'] );
$form['g_use_pm'] 				= $this->registry->output->formYesNo( "g_use_pm", $group['g_use_pm'] );
$form['g_max_mass_pm']			= $this->registry->output->formInput( "g_max_mass_pm", $group['g_max_mass_pm'] );
$form['g_max_messages']			= $this->registry->output->formInput( "g_max_messages", $group['g_max_messages'] );
$form['g_pm_perday']			= $this->registry->output->formSimpleInput( "g_pm_perday", $group['g_pm_perday'], 4 );
$form['g_pm_flood_mins']		= $this->registry->output->formSimpleInput( "g_pm_flood_mins", $group['g_pm_flood_mins'], 3 );

$form['g_dohtml']				= $this->registry->output->formYesNo( "g_dohtml", $group['g_dohtml'] );
$form['g_bypass_badwords']		= $this->registry->output->formYesNo( "g_bypass_badwords", $group['g_bypass_badwords'] );
$form['g_dname_date']			= $this->registry->output->formSimpleInput( "g_dname_date", $group['g_dname_date'], 3 );
$form['g_dname_changes']		= $this->registry->output->formSimpleInput( "g_dname_changes", $group['g_dname_changes'], 3 );
$form['g_is_supmod']			= $this->registry->output->formYesNo( "g_is_supmod", $group['g_is_supmod'] );
$form['g_access_cp']			= $this->registry->output->formYesNo( "g_access_cp", $group['g_access_cp'] );

$form['g_promotion_id']			= $this->registry->output->formDropdown( "g_promotion_id", $all_groups, $group['g_promotion_id'] );
$form['g_promotion_posts']		= $this->registry->output->formSimpleInput( 'g_promotion_posts', $group['g_promotion_posts'] );

$form['g_new_perm_set']			= $this->registry->output->formInput( "g_new_perm_set", '' );
$form['g_rep_max_positive']		= $this->registry->output->formInput( "g_rep_max_positive", $group['g_rep_max_positive'] );
$form['g_rep_max_negative']		= $this->registry->output->formInput( "g_rep_max_negative", $group['g_rep_max_negative'] );

$sig_limits						= explode( ':', $group['g_signature_limits'] );

$form['use_signatures']			= $this->registry->output->formYesNo( "use_signatures", $sig_limits[0] );
$form['max_images']				= $this->registry->output->formInput( "max_images", $sig_limits[1] );
$form['max_dims']				= $this->registry->output->formSimpleInput( "max_dims_x", $sig_limits[2] ) . ' x ' . $this->registry->output->formSimpleInput( "max_dims_y", $sig_limits[3] );
$form['max_urls']				= $this->registry->output->formInput( "max_urls", $sig_limits[4] );
$form['max_lines']				= $this->registry->output->formInput( "max_lines", $sig_limits[5] );

$form['g_displayname_unit']		= $this->registry->output->formSimpleInput( "g_displayname_unit", $group['g_displayname_unit'], 3 );
$form['gbw_displayname_unit_type']	= $this->registry->output->formDropdown( "gbw_displayname_unit_type", $gbw_unit_type, $group['gbw_displayname_unit_type'] );
$form['g_sig_unit']				= $this->registry->output->formSimpleInput( "g_sig_unit", $group['g_sig_unit'], 3 );
$form['gbw_sig_unit_type']		= $this->registry->output->formDropdown( "gbw_sig_unit_type", $gbw_unit_type, $group['gbw_sig_unit_type'] );

$form['gbw_promote_unit_type']	= $this->registry->output->formDropdown( "gbw_promote_unit_type", $gbw_unit_type, $group['gbw_promote_unit_type'] );

$form['gbw_no_status_update']	= $this->registry->output->formYesNo( "gbw_no_status_update", $group['gbw_no_status_update'] );

if( $type == 'edit' )
{
	$title	= $this->lang->words['g_editing'] . $group['g_title'];
}
else
{
	$title	= $this->lang->words['g_adding'];
}

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$title}</h2>
</div>

<script type='text/javascript' src='{$this->settings['js_app_url']}ipsEditGroup.js'></script>
<script type='text/javascript'>
HTML;

foreach( $permission_masks as $d )
{
	$IPBHTML .= "	perms_{$d[0]} = '{$d[1]}';\n";
}

$IPBHTML .= <<<HTML
</script>
<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do={$form_code}&amp;secure_key={$secure_key}' method='post' id='adform' name='adform' onsubmit='return checkform();'>
<input type='hidden' name='id' value='{$group['g_id']}' />

<ul id='tabstrip_group' class='tab_bar no_title'>
	<li id='tabtab-GROUPS|1' class=''>{$this->lang->words['g_globalsett']}</li>
	<li id='tabtab-GROUPS|2' class=''>{$this->lang->words['g_globalperm']}</li>
HTML;

// Got blocks from other apps?
$IPBHTML .= implode( "\n", $content['tabs'] );

$semaillimit = sprintf( $this->lang->words['g_semail_limit'], $form['join_limit'] );
$semailflood = sprintf( $this->lang->words['g_semail_flood'], $form['join_flood'] );

$pmlimit = sprintf( $this->lang->words['g_pm_limit'], $form['g_pm_perday'] );
$pmflood = sprintf( $this->lang->words['g_pm_flood'], $form['g_pm_flood_mins'] );


$IPBHTML .= <<<HTML
</ul>

<script type="text/javascript">
//<![CDATA[
document.observe("dom:loaded",function() 
{
ipbAcpTabStrips.register('tabstrip_group');
});
 //]]>
</script>

<div class='acp-box'>
	<div id='tabpane-GROUPS|1'>
		<table width='100%' cellpadding='0' cellspacing='0' class='form_table alternate_rows double_pad'>
			<tr>
				<th colspan='2'>{$this->lang->words['gt_details']}</th>
			</tr>
			<tr>
				<td style='width: 40%'>
					<label>{$this->lang->words['g_gtitle']}</label>
				</td>
				<td style='width: 60%'>
					{$form['g_title']}
				</td>
			</tr>
			<tr>
		 		<td>
					<label>{$this->lang->words['g_gicon']}</label><br />
					<span class='desctext'>{$this->lang->words['g_gicon_info']}</span>
				</td>
		 		<td>
					{$form['g_icon']}
		 		</td>
		 	</tr>
		 	
		 	<tr>
		 		<td>
					<label>{$this->lang->words['g_gformpre']}</label><br />
					<span class='desctext'>{$this->lang->words['g_gformpre_info']}</span>
				</td>
		 		<td>
					{$form['prefix']}
		 		</td>
		 	</tr>
		 	
		 	<tr>
		 		<td>
					<label>{$this->lang->words['g_gformsuf']}</label><br />
					<span class='desctext'>{$this->lang->words['g_gformsuf_info']}</span>
				</td>
		 		<td>
					{$form['suffix']}
		 		</td>
		 	</tr>
			<tr>
				<th colspan='2'>{$this->lang->words['gt_permissions']}</th>
			</tr>
			<tr>
		 		<td>
					<label>{$this->lang->words['g_permset']}</label><br />
					<span class='desctext'>{$this->lang->words['g_permset_info']}</span>
				</td>
		 		<td>
					{$form['permid']}
		 		</td>
		 	</tr>
		 	<tr>
		 		<td>
					<label>{$this->lang->words['g_newpermset']}</label><br />
					<span class='desctext'>{$this->lang->words['g_newpermset_info']}</span>
				</td>
		 		<td>
					{$form['g_new_perm_set']}
		 		</td>
		 	</tr>
		 	
		 	<tr class='guest_legend'>
		 		<td>
		 			<label>{$this->lang->words['g_hide']}</label>
		 			{$guest_legend}
		 		</td>
		 		<td>
		 			{$form['g_hide_from_list']}
		 		</td>
		 	</tr>
		 	
		 	<tr class='guest_legend'>
		 		<td>
		 			<label>{$this->lang->words['g_hide_online']}</label>
		 			{$guest_legend}
		 		</td>
		 		<td>
		 			{$form['g_hide_online_list']}
		 		</td>
		 	</tr>
		 	<tr>
				<th colspan='2'>{$this->lang->words['gt_display_name']}</th>
			</tr>
			<tr>
		 		<td>
					<label>{$this->lang->words['g_dmax']}</label><br />
					<span class='desctext'>{$this->lang->words['g_dmax_info']}</span>
				</td>
		 		<td>
					<p>{$form['g_dname_changes']} &nbsp; {$this->lang->words['g_when']} {$form['g_displayname_unit']} {$form['gbw_displayname_unit_type']}</p>
					<p style='color:gray;font-size:0.8em'>{$this->lang->words['g_limit_dd']}</p>
		 		</td>
		 	</tr>
		 	<tr>
		 		<td>
					<label>{$this->lang->words['g_dlimit']}</label><br />
					<span class='desctext'>{$this->lang->words['g_dlimit_info']}</span>
				</td>
		 		<td>
		 			{$form['g_dname_date']}
		 		</td>
		 	</tr>
		 	<tr>
				<th colspan='2'>{$this->lang->words['gt_access_control']}</th>
			</tr>
		 	<tr class='guest_legend'>
		 		<td>
		 			<label>{$this->lang->words['g_msup']}</label>
		 			{$guest_legend}
		 		</td>
		 		<td>
		 			{$form['g_is_supmod']}
		 		</td>
		 	</tr>
		 	
		 	<tr class='guest_legend'>
		 		<td>
		 			<label>{$this->lang->words['g_macp']}</label>
		 			{$guest_legend}
		 		</td>
		 		<td>
		 			{$form['g_access_cp']}
		 		</td>
		 	</tr>
		 	<tr>
				<th colspan='2'>{$this->lang->words['gt_promotion']}</th>
			</tr>
		 	<tr class='guest_legend'>
		 		<td>
					<label>{$this->lang->words['g_mpromote']}</label><br />
					{$guest_legend}
					<span class='desctext'>{$this->registry->output->javascriptHelpLink('mg_promote')}</span>
				</td>
		 		<td>		 		
HTML;
				if( $group['g_access_cp'] )
				{
					$IPBHTML .= "{$this->lang->words['g_mpromote_no']}";
				}
				else
				{
				 	$promotegrouptxt = sprintf( $this->lang->words['g_mpromote_to'], $form['g_promotion_id'], $form['g_promotion_posts'], $form['gbw_promote_unit_type'] );
					$IPBHTML .= "{$promotegrouptxt}";
				}
		$IPBHTML .= <<<HTML
		 		</td>
		 	</tr>
		</table>
 	</div>

 <div id='tabpane-GROUPS|2'>
	 <table class='form_table alternate_rows double_pad' cellspacing='0'>
		<tr>
			<th colspan='2'>{$this->lang->words['gt_access_permissions']}</th>
		</tr>
	 	<tr>
	 		<td style='width: 40%'>
				<label>{$this->lang->words['g_ssite']}</label>
			</td>
	 		<td style='width: 60%'>
	 			{$form['g_view_board']}
	 		</td>
	 	</tr>
	 	
	 	<tr>
	 		<td>
				<label>{$this->lang->words['g_soffline']}</label>
			</td>
	 		<td>
	 			{$form['g_access_offline']}
	 		</td>
	 	</tr>
	 	
	 	<tr>
	 		<td>
				<label>{$this->lang->words['g_sprofile']}</label>
			</td>
	 		<td>
	 			{$form['g_mem_info']}
	 		</td>
	 	</tr>
	 	
	 	<tr class='guest_legend'>
	 		<td>
				<label>{$this->lang->words['g_addfriends']}{$guest_legend}</label>
			</td>
	 		<td>
	 			{$form['g_can_add_friends']}
	 		</td>
	 	</tr>
	 	
	 	<tr class='guest_legend'>
	 		<td>
	 			<label>{$this->lang->words['g_editprofile']}</label>
	 			{$guest_legend}
	 		</td>
	 		<td>
	 			{$form['g_edit_profile']}
	 		</td>
	 	</tr>
	 	
	 	<tr class='guest_legend'>
	 		<td>
	 			<label>{$this->lang->words['g_shtml']}</label><br />
	 			{$guest_legend}
				<span class='desctext'>{$this->registry->output->javascriptHelpLink('mg_dohtml')}</span>
	 		</td>
	 		<td>
	 			{$form['g_dohtml']}
	 		</td>
	 	</tr>
		<tr class='guest_legend'>
	 		<td>
	 			<label>{$this->lang->words['g_semail']}</label><br />
	 			{$guest_legend}
				<span class='desctext'>{$this->lang->words['g_semail_info']}</span>
	 		</td>
	 		<td>
	 			{$form['g_email_friend']}
	 			<br />{$semaillimit}
	 			<br />{$semailflood}
	 		</td>
	 	</tr>
		<tr class='guest_legend'>
	 		<td>
	 			<label>{$this->lang->words['g_uav']}</label>
	 			{$guest_legend}
	 		</td>
	 		<td>
	 			{$form['g_avatar_upload']}
	 		</td>
	 	</tr>
		<tr class='guest_legend'>
	 		<td>
	 			<label>{$this->lang->words['g_sbadword']}</label>
	 			{$guest_legend}
	 		</td>
	 		<td>
	 			{$form['g_bypass_badwords']}
	 		</td>
	 	</tr>
		<tr class='guest_legend'>
	 		<td>
	 			<label>{$this->lang->words['g_no_status']}</label>
	 			{$guest_legend}
	 		</td>
	 		<td>
	 			{$form['gbw_no_status_update']}
	 		</td>
	 	</tr>
	 	<tr>
			<th colspan='2'>{$this->lang->words['gt_search']}</th>
		</tr>
	 	<tr>
	 		<td>
				<label>{$this->lang->words['g_ssearch']}</label>
			</td>
	 		<td>
	 			{$form['g_use_search']}
	 		</td>
	 	</tr>
	 	
	 	<tr>
	 		<td>
				<label>{$this->lang->words['g_sflood']}</label><br />
				<span class='desctext'>{$this->lang->words['g_sflood_info']}</span>
			</td>
	 		<td>
	 			{$form['g_search_flood']}
	 		</td>
	 	</tr>
	 	
	 	<tr>
			<th colspan='2'>{$this->lang->words['gt_pms']}</th>
		</tr>
	 	<tr class='guest_legend'>
	 		<td>
	 			<label>{$this->lang->words['g_spm']}</label><br />
	 			{$guest_legend}
				<span class='desctext'>{$this->lang->words['g_spmperday_info']}</span>
	 		</td>
	 		<td>
	 			{$form['g_use_pm']}
				<br />{$pmlimit}
	 			<br />{$pmflood}
	 		</td>
	 	</tr>
	
	 	<tr class='guest_legend'>
	 		<td>
	 			<label>{$this->lang->words['g_spmmax']}</label><br />
	 			{$guest_legend}
				<span class='desctext'>{$this->lang->words['g_spmmax_info']}</span>
	 		</td>
	 		<td>
	 			{$form['g_max_mass_pm']}
	 		</td>
	 	</tr>
	 	
	 	<tr class='guest_legend'>
	 		<td>
	 			<label>{$this->lang->words['g_spmmaxstor']}</label>
	 			{$guest_legend}
	 		</td>
	 		<td>
	 			{$form['g_max_messages']}
	 		</td>
	 	</tr>
		<tr class='guest_legend'>
	 		<td>
	 			<label>{$this->lang->words['g_upm']}</label>
	 			{$guest_legend}
	 		</td>
	 		<td>
	 			{$form['g_can_msg_attach']}
	 		</td>
	 	</tr>
	
		<tr>
			<th colspan='2'>{$this->lang->words['gt_reps']}</th>
		</tr>
	 	<tr>
	 		<td>
	 			<label>{$this->lang->words['g_repmaxpos']}</label><br />
				<span class='desctext'>{$this->lang->words['g_repnum_info']}</span>
	 		</td>
	 		<td>
	 			{$form['g_rep_max_positive']}
	 		</td>
	 	</tr>
	 	<tr>
	 		<td>
	 			<label>{$this->lang->words['g_repmaxneg']}</label><br />
				<span class='desctext'>{$this->lang->words['g_repnum_info']}</span>
	 		</td>
	 		<td>
	 			{$form['g_rep_max_negative']}
	 		</td>
	 	</tr>
		<tr>
			<th colspan='2'>{$this->lang->words['gt_sigs']}</th>
		</tr>
	 	<tr>
	 		<td>
	 			<label>{$this->lang->words['g_usesigs']}</label>
	 		</td>
	 		<td>
			<p>{$form['use_signatures']} &nbsp; {$this->lang->words['g_until']} {$form['g_sig_unit']} {$form['gbw_sig_unit_type']}</p>
			<p style='color:gray;font-size:0.8em'>{$this->lang->words['g_limit_dd']}</p>
	 		</td>
	 	</tr>
	 	<tr>
	 		<td>
	 			<label>{$this->lang->words['g_sigmaximages']}</label>
	 		</td>
	 		<td>
	 			{$form['max_images']}
	 		</td>
	 	</tr>
	 	<tr>
	 		<td>
	 			<label>{$this->lang->words['g_sigmaxdims']}</label>
	 		</td>
	 		<td>
	 			{$form['max_dims']}
	 		</td>
	 	</tr>
	 	<tr>
	 		<td>
	 			<label>{$this->lang->words['g_sigmaxurls']}</label>
	 		</td>
	 		<td>
	 			{$form['max_urls']}
	 		</td>
	 	</tr>
	 	<tr>
	 		<td>
	 			<label>{$this->lang->words['g_sigmaxtext']}</label>
	 		</td>
	 		<td>
	 			{$form['max_lines']}
	 		</td>
	 	</tr>
		<tr>
			<th colspan='2'>{$this->lang->words['gt_uploads']}</th>
		</tr>
	 	<tr>
	 		<td>
				<label>{$this->lang->words['g_uglobal']}</label><br />
				<span class='desctext'>{$this->registry->output->javascriptHelpLink('mg_upload')}<br />{$this->lang->words['g_uglobal_info']}</span>
			</td>
	 		<td>
	 			{$form['g_attach_max']}
	 			{$this->lang->words['g_inkb']} ({$this->lang->words['g_ucurrently']}{$group['g_attach_maxdis']})
	 			<br />{$this->lang->words['g_usingle']}{$ini_max}
	 		</td>
	 	</tr>
	 	
	 	<tr>
	 		<td>
				<label>{$this->lang->words['g_upost']}</label><br />
				<span class='desctext'>{$this->registry->output->javascriptHelpLink('mg_upload')}<br />{$this->lang->words['g_upost_info']}</span>
			</td>
	 		<td>
	 			{$form['g_attach_per_post']}
	 			{$this->lang->words['g_inkb']} ({$this->lang->words['g_ucurrently']}{$group['g_attach_per_postdis']})
	 			<br />{$this->lang->words['g_usingle']}{$ini_max}
	 		</td>
	 	</tr>
	 	
	 	<tr class='guest_legend'>
	 		<td>
	 			<label>{$this->lang->words['g_upersonalpho']}</label><br />
	 			{$guest_legend}
				<span class='desc'>{$this->lang->words['g_upersonalpho_l']}</span>
	 		</td>
	 		<td>
	 			{$form['p_max']}{$this->lang->words['g_inkb']}
	 			<br />{$this->lang->words['g_upersonalpho_w']}{$form['p_width']} x {$this->lang->words['g_upersonalpho_h']}{$form['p_height']}
	 		</td>
	 	</tr>
	</table>
 </div>
HTML;

// Got blocks from other apps?
$IPBHTML .= implode( "\n", $content['area'] );

$IPBHTML .= <<<HTML
<div class='acp-actionbar'>
	<input type='submit' value=' {$button} ' class='realbutton' />
</div>
</div>

</form>

<script type="text/javascript">
HTML;

if( $group['g_id'] == $this->settings['guest_group'] )
{
	$IPBHTML .= "stripGuestLegend();";
}

$IPBHTML .= <<<HTML
</script>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Overview of groups
 *
 * @access	public
 * @param	string		Groups HTML
 * @param	array 		Groups
 * @return	string		HTML
 */
public function groupsOverviewWrapper( $content, $g_array ) {

$new_dd = $this->registry->output->formDropdown( "id", $g_array, 3 );

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['g_title']}</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['g_usergroupman']}</h3>
	<table class='alternate_rows double_pad' width='100%'>
		<tr>
			<th width='1%'>&nbsp;</th>
			<th width='30%'>{$this->lang->words['g_grouptitle']}</th>
			<th width='15%' align='center' style='text-align:center'>{$this->lang->words['g_canaccessacp']}</th>
			<th width='15%' align='center' style='text-align:center'>{$this->lang->words['g_issupermod']}</th>
			<th width='10%' align='center' style='text-align:center'>{$this->lang->words['g_membercount']}</th>
			<th width='1%'>&nbsp;</th>
		</tr>
		{$content}
	</table>
</div>
<br />
<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=add' method='POST' >
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->getClass('adminFunctions')->_admin_auth_key}' />
	<div class='acp-box'>
		<h3>{$this->lang->words['g_createnew']}</h3>
		
		<table cellpadding='4' cellspacing='0' width='100%'>
			<tr>
				<th width='40%'>{$this->lang->words['g_basenewon']}</th>
				<th width='60%'>{$new_dd}</th>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<div class='centeraction'>
				<input type='submit' value='{$this->lang->words['g_createbutton']}' class='button primary' />
			</div>
		</div>		
	</div>
</form>
HTML;
if ( IN_DEV )
{
	$IPBHTML .= "<br /><div><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=master_xml_export'>Export XML</a></div>";
}

//--endhtml--//
return $IPBHTML;
}

/**
 * Group row
 *
 * @access	public
 * @param	array 		Group
 * @return	string		HTML
 */
public function groupsOverviewRow( $r="" ) {
$IPBHTML = "";
//--starthtml--//

$r['_can_acp_img']		= $r['_can_acp']    ? 'accept.png' : 'cross.png';
$r['_can_supmod_img']	= $r['_can_supmod'] ? 'accept.png' : 'cross.png';
$r['_title']			= IPSLib::makeNameFormatted( $r['g_title'], $r['g_id'] );

$IPBHTML .= <<<HTML
<tr>
  <td><img src='{$this->settings['skin_acp_url']}/_newimages/icons/group.png' border='0' class='ipbmenu' /></td>
  <td style='font-weight:bold'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=edit&amp;id={$r['g_id']}'>{$r['_title']}</a></td>
  <td align='center'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/{$r['_can_acp_img']}' border='0' alt='-' class='ipd' /></td>
  <td align='center'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/{$r['_can_supmod_img']}' border='0' alt='-' class='ipd' /></td>
  <td align='center'>
HTML;
if ( $r['g_id'] != $this->settings['auth_group'] and $r['g_id'] != $this->settings['guest_group'] )
{
$IPBHTML .= <<<HTML
	<a href='{$this->settings['_base_url']}app=members&amp;section=members&amp;module=members&amp;__update=1&amp;f_primary_group={$r['g_id']}' title='{$this->lang->words['g_listusers']}'>{$r['count']}</a>
HTML;
}
else
{
$IPBHTML .= <<<HTML
    {$r['count']}
HTML;
}
$IPBHTML .= <<<HTML
  </td>												
  <td align='right'>
  	<img id="menu{$r['g_id']}" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' border='0' alt='{$this->lang->words['a_options']}' class='ipbmenu' />
	<ul class='acp-menu' id='menu{$r['g_id']}_menucontent'>
		<li class='icon edit'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=edit&amp;id={$r['g_id']}'>{$this->lang->words['g_editg']}</a></li>
HTML;
if ( ! in_array( $r['g_id'], array( $this->settings['auth_group'], $this->settings['guest_group'], $this->settings['member_group'] ) )  )
{
$IPBHTML .= <<<HTML
		<li class='icon delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=delete&amp;_admin_auth_key={$this->registry->getClass('adminFunctions')->_admin_auth_key}&amp;id={$r['g_id']}");'>{$this->lang->words['g_deleteg']}</a></li>
HTML;
}
else
{
$IPBHTML .= <<<HTML
		<li class='icon delete'>{$this->lang->words['g_cannotdel']}</li>
HTML;
}
$IPBHTML .= <<<HTML
		
	</ul>
  </td>
</tr>
HTML;

//--endhtml--//
return $IPBHTML;
}


/**
 * Delete group confirmation
 *
 * @access	public
 * @param	array 		Group
 * @param	int			Members with this group as primary group
 * @param	int			Members with this group as secondary group
 * @return	string		HTML
 */
public function groupDelete( $group, $primary=0, $secondary=0 ) {

$IPBHTML = "";
//--starthtml--//

//-----------------------------------------
// Grab group, and other groups
//-----------------------------------------

$mem_groups				= array();

foreach( $this->caches['group_cache'] as $g_id => $r )
{
	if( $g_id == $group['g_id'] )
	{
		continue;
	}

	$mem_groups[] = array( $r['g_id'], $r['g_title'] );
}
		
$dropDown	= $this->registry->output->formDropdown( "to_id", $mem_groups, $this->settings['member_group'] );

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['g_deleting']}</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['g_removeconf']}{$group['g_title']}</h3>
	
	<form name='theAdminForm' id='adminform' action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=dodelete' method='post'>
		<input type='hidden' name='id' value='{$group['g_id']}' />
		<input type='hidden' name='_admin_auth_key' value='{$this->registry->getClass('adminFunctions')->_admin_auth_key}' />
		
		<table class='alternate_rows' width='100%'>
			<tr>
				<td width='40%'>{$this->lang->words['g_numusers']}</td>
				<td width='60%'>
					<a href='{$this->settings['_base_url']}&amp;app=members&amp;module=members&amp;section=members&amp;__update=1&amp;f_primary_group={$group['g_id']}'>{$primary}</a>
				</td>
			</tr>
			<tr>
				<td width='40%'>{$this->lang->words['g_numusers_sec']}</td>
				<td width='60%'>
					<a href='{$this->settings['_base_url']}&amp;app=members&amp;module=members&amp;section=members&amp;__update=1&amp;f_secondary_group={$group['g_id']}'>{$secondary}</a>
				</td>
			</tr>
			<tr>
				<td width='40%'>{$this->lang->words['g_moveusersto']}</td>
				<td width='60%'>{$dropDown}</td>
			</tr>			
		</table>
		<div class='acp-actionbar'>
			<div class='centeraction'>
				<input type='submit' class='button primary' value=' {$this->lang->words['g_deletebutton']} ' />
			</div>
		</div>
	</form>
</div>

HTML;

//--endhtml--//
return $IPBHTML;
}


}