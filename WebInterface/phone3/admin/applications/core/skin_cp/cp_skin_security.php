<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Security skin file
 * Last Updated: $Date: 2009-05-21 16:47:25 -0400 (Thu, 21 May 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 4682 $
 */
 
class cp_skin_security extends output
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
 * List administrators
 *
 * @access	public
 * @param	array 		Rows
 * @return	string		HTML
 */
public function list_admin_overview($rows) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$this->lang->words['skin_header_admins']}</h3>
	<table class="alternate_rows" width='100%'>
		<tr>
			<th width='20%'>{$this->lang->words['skin_th_name']}</th>
			<th width='15%'>{$this->lang->words['skin_th_primary_group']}</th>
			<th width='15%'>{$this->lang->words['skin_th_secondary_group']}</th>
			<th width='15%'>{$this->lang->words['skin_th_ip']}</th>
			<th width='20%'>{$this->lang->words['skin_th_email']}</th>
			<th width='10%'>{$this->lang->words['skin_th_posts']}</th>
			<th width='5%'>&nbsp;</th>
		</tr>
HTML;

if( count( $rows ) AND is_array( $rows ) )
{
	foreach( $rows as $row )
	{
		$IPBHTML .= <<<HTML
		<tr>
			<td><strong>{$row['members_display_name']}</strong><div class='desctext'>{$row['_joined']}</div></td>
			<td>{$row['_mgroup']}</td>
			<td>{$row['_mgroup_others']}&nbsp;</td>
			<td><div class='desctext'>{$row['ip_address']}</div></td>
			<td>{$row['email']}</td>
			<td>{$row['posts']}</td>
			<td><a href='{$this->settings['_base_url']}&amp;app=members&amp;module=members&amp;section=members&amp;do=viewmember&amp;member_id={$row['member_id']}'>{$this->lang->words['skin_edit_member']}</a></td>
		</tr>
HTML;
	}
}
else
{
	$IPBHTML .= <<<HTML
		<tr>
			<td colspan='7' align='center'>{$this->lang->words['skin_log_noresults']}</td>
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

/**
 * Row for list administrator
 *
 * @access	public
 * @param	array 		Member data
 * @return	string		HTML
 * @deprecated	Seems this is handled by list_admin_overview, maybe?
 */
public function list_admin_row( $member ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
 <td class='tablerow1'>
	<strong>{$member['members_display_name']}</strong>
	<div class='desctext'>{$member['_joined']}</div>
 </td>
 <td class='tablerow1'>
	{$member['_mgroup']}
 </td>
 <td class='tablerow2'>
	{$member['_mgroup_others']}&nbsp;
 </td>
 <td class='tablerow1'>
	<div class='desctext'>{$member['ip_address']}</div>
 </td>
 <td class='tablerow2'>
	{$member['email']}
 </td>
 <td class='tablerow2'>
	{$member['posts']}
 </td>
 <td class='tablerow1'>
	<a href='{$this->settings['_base_url']}&amp;app=members&amp;module=members&amp;section=members&amp;do=viewmember&amp;member_id={$member['member_id']}'>{$this->lang->words['skin_edit_member']}</a>
 </td>
</tr>
EOF;

//--endhtml--//
return $IPBHTML;
}


/**
 * Wrapper to show files checked for anti-virus
 *
 * @access	public
 * @param	string		Content
 * @return	string		HTML
 */
public function anti_virus_checked_wrapper($content) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<br />
<div class='tableborder'>
 <div class='tableheaderalt'>{$this->lang->words['skin_header_virus']}</div>
 <table cellpadding='0' cellspacing='0' width='100%'>
	$content
 </table>
 <div align='center' class='tablefooter'>&nbsp;</div>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Anti-virus checked file row
 *
 * @access	public
 * @param	string		Path to file
 * @return	string		HTML
 */
public function anti_virus_checked_row( $file_path ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
 <td class='tablerow1'>
	<img src='{$this->settings['skin_acp_url']}/images/folder_components/security/checked_folder.png' border='0' alt='-' class='ipd' />
	$file_path
 </td>
</tr>
EOF;

//--endhtml--//
return $IPBHTML;
}



/**
 * Wrapper for bad files from AV checker
 *
 * @access	public
 * @param	string		Content
 * @return	string		HTML
 */
public function anti_virus_bad_files_wrapper($content) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='tableborder'>
 <div class='tableheaderalt'>{$this->lang->words['skin_suspicious_files']}</div>
 <div class='tablesubheader' style='padding-right:0px'>
  <div align='right' style='padding-right:10px'>
     {$this->lang->words['skin_th_filesize']}
    </div>
  </div>
 <table cellpadding='0' cellspacing='0' width='100%'>
	$content
 </table>
 <div align='center' class='tablefooter'>&nbsp;</div>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Row for bad files in AV checker
 *
 * @access	public
 * @param	string		File path
 * @param	string		File name
 * @param	array 		Data about the file
 * @return	string		HTML
 */
public function anti_virus_bad_files_row( $file_path, $file_name, $data ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
 <td class='tablerow1'>
	<div style='float:right'>
		<div class='desctext'>({$data['human']}k) &nbsp; {$data['mtime']}</div>
	</div>
	<img src='{$this->settings['skin_acp_url']}/images/folder_components/security/bad_file.png' border='0' alt='-' class='ipd' />
	<span style='border:1px solid #555;background-color:#FFFFFF'>
		<span style='width:{$data['left_width']}px;background-color:{$data['color']}'>
			<img src='{$this->settings['skin_acp_url']}/images/blank.gif' height='20' width='{$data['left_width']}' alt='' />
		</span>
		<img src='{$this->settings['skin_acp_url']}/images/blank.gif' height='20' width='{$data['right_width']}' alt='' />
	</span>
	&nbsp; <span class='desctext'>[ {$data['score']} ]</span> <a target='_blank' href='{$this->settings['board_url']}/{$file_path}'>{$file_name}</a>
 </td>
</tr>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Wrapper for files from deep scan
 *
 * @access	public
 * @param	string		Content
 * @return	string		HTML
 */
public function deep_scan_bad_files_wrapper($content) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=deep_scan' method='POST'>
<div style='padding-bottom:10px'>{$this->lang->words['sec_show']}: 
	<select name='filter'>
	    <option value='all'>{$this->lang->words['sec_show_all']}</option>
		<option value='score-5'>{$this->lang->words['sec_score_5_or_more']}</option>
		<option value='score-6'>{$this->lang->words['sec_score_6_or_more']}</option>
		<option value='score-7'>{$this->lang->words['sec_score_7_or_more']}</option>
		<option value='score-8'>{$this->lang->words['sec_score_8_or_more']}</option>
		<option value='score-9'>{$this->lang->words['sec_score_9_or_more']}</option>
		<option value='large'>{$this->lang->words['sec_files_55k_or_larger']}</option>
		<option value='recent'>{$this->lang->words['sec_modified_in_the_past_30_days']}</option>
	</select>
	<input type='submit' value=' Filter ' />
</div>
</form>
<div class='tableborder'>
 <div class='tableheaderalt'>{$this->lang->words['sec_executable_files']}</div>
 <div class='tablesubheader' style='padding-right:0px'>
  <div align='right' style='padding-right:10px'>
     {$this->lang->words['sec_file_size']} &nbsp; {$this->lang->words['sec_last_modified']}
    </div>
  </div>
 <table cellpadding='0' cellspacing='0' width='100%'>
	$content
 </table>
 <div align='center' class='tablefooter'>&nbsp;</div>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show the row for the bad file from deep scan
 *
 * @access	public
 * @param	string		File path
 * @param	string		File name
 * @param	array 		Data about the bad file record
 * @return	string		HTML
 */
public function deep_scan_bad_files_row( $file_path, $file_name, $data ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
 <td class='tablerow1'>
	<div style='float:right'>
		<div class='desctext'>({$data['human']}k) &nbsp; {$data['mtime']}</div>
	</div>
	<img src='{$this->settings['skin_acp_url']}/images/folder_components/security/bad_file.png' border='0' alt='-' class='ipd' />
	<span style='border:1px solid #555;background-color:#FFFFFF'>
		<span style='width:{$data['left_width']}px;background-color:{$data['color']}'>
			<img src='{$this->settings['skin_acp_url']}/images/blank.gif' height='20' width='{$data['left_width']}' alt='' />
		</span>
		<img src='{$this->settings['skin_acp_url']}/images/blank.gif' height='20' width='{$data['right_width']}' alt='' />
	</span>
	&nbsp; <span class='desctext'>[ {$data['score']} ]</span> <a target='_blank' href='{$this->settings['board_url']}/{$file_path}'>$file_path</a>
 </td>
</tr>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Security tools overview page
 *
 * @access	public
 * @param	string		Content
 * @return	string		HTML
 */
public function securityOverview( $content ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF

<div class='section_title'>
	<h2>{$this->lang->words['sec_ipboard_security_center']}</h2>
</div>
<div class='information-box'>
	{$this->lang->words['sec_the_security_center_is_a_centr']}<br />
	{$this->lang->words['sec_your_installation_is_checked_a']}
</div>
<br />
<div class='acp-box'>
	<h3>{$this->lang->words['h3_title_security']}</h3>
	<table cellpadding='0' cellspacing='0' border='0' class='alternate_rows triple_pad'>
		{$content['bad']}
		{$content['ok']}
		{$content['good']}
	</table>
</div>

EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * "Bad" security item
 *
 * @access	public
 * @param	string		Title
 * @param	string		Description
 * @param	string		Button text
 * @param	string		URL
 * @param	string		Unique key
 * @return	string		HTML
 */
public function security_item_bad( $title, $desc, $button, $url, $key ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
	<td style='width: 3%'>
		<img src='{$this->settings['skin_acp_url']}/_newimages/icons/exclamation.png' alt='{$this->lang->words['sec_information']}' />
	</td>
	<td style='width: 77%'>
		<strong>{$title}</strong><br />
		<span class='desctext'>{$desc}</span>
	</td>
	<td style='width: 20%'>
		<a href='{$this->settings['base_url']}&amp;$url' class='realbutton'>$button</a>
	</td>
</tr>

EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * "Good" security item
 *
 * @access	public
 * @param	string		Title
 * @param	string		Description
 * @param	string		Button text
 * @param	string		URL
 * @param	string		Unique key
 * @return	string		HTML
 */
public function security_item_good( $title, $desc, $button, $url, $key ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
	<td style='width: 3%'>
		<img src='{$this->settings['skin_acp_url']}/_newimages/icons/accept.png' alt='{$this->lang->words['sec_information']}' />
	</td>
	<td style='width: 77%'>
		<strong>{$title}</strong><br />
		<span class='desctext'>{$desc}</span>
	</td>
	<td style='width: 20%'>
		<a href='{$this->settings['base_url']}&amp;$url' class='realbutton'>$button</a>
	</td>
</tr>

EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * "Ok" security item
 *
 * @access	public
 * @param	string		Title
 * @param	string		Description
 * @param	string		Button text
 * @param	string		URL
 * @param	string		Unique key
 * @return	string		HTML
 */
public function security_item_ok( $title, $desc, $button, $url, $key ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
	<td style='width: 3%'>
		<img src='{$this->settings['skin_acp_url']}/_newimages/icons/warning.png' alt='{$this->lang->words['sec_information']}' />
	</td>
	<td style='width: 77%'>
		<strong>{$title}</strong><br />
		<span class='desctext'>{$desc}</span>
	</td>
	<td style='width: 20%'>
		<a href='{$this->settings['base_url']}&amp;$url' class='realbutton'>$button</a>
	</td>
</tr>

EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Form to add the htaccess file
 *
 * @access	public
 * @return	string		HTML
 */
public function htaccess_form() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<form id='mainform' method='post' action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=acphtaccess_do'>
<div class='information-box'>
	<table cellpadding='0' cellspacing='0'>
	<tr>
		<td width='1%' valign='top'>
 			<img src='{$this->settings['skin_acp_url']}/images/folder_components/security/id_card_ok.png' alt='{$this->lang->words['sec_information']}' />
		</td>
		<td width='100%' valig='top' style='padding-left:10px'>
 			<h2 style='margin:0px'>{$this->lang->words['sec_acp_htaccess_protection']}</h2>
			 <p style='margin:0px'>
			 	<br />
			 	{$this->lang->words['sec_invision_power_board_can_write']}
				<br />
				<br />
				<strong>{$this->lang->words['sec_please_note']}</strong>
				<br />
				{$this->lang->words['sec_using_this_tool_will_overwrite']}
				<br />
				<br />
				<fieldset>
					<legend><strong>{$this->lang->words['sec_username']}</strong></legend>
					<input type='text' name='name' size='40' value='{$_POST['name']}' />
				</fieldset>
				<br />
				<fieldset>
					<legend><strong>{$this->lang->words['sec_password']}</strong></legend>
					<input type='password' name='pass' size='40' value='{$_POST['pass']}' />
				</fieldset>
				<br />
				<input type='submit' value=' {$this->lang->words['sec_proceed']} ' />
			 </p>
		</td>
	</tr>
	</table>
</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show details to manually set htaccess file
 *
 * @access	public
 * @param	string		.htaccess password
 * @param	string		.htaccess auth
 * @return	string		HTML
 */
public function htaccess_data( $htaccess_pw, $htaccess_auth ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<form id='mainform'>
<div class='information-box'>
	<table cellpadding='0' cellspacing='0'>
	<tr>
		<td width='1%' valign='top'>
 			<img src='{$this->settings['skin_acp_url']}/images/folder_components/security/id_card_ok.png' alt='information' />
		</td>
		<td width='100%' valig='top' style='padding-left:10px'>
 			<h2 style='margin:0px'>{$this->lang->words['sec_cp_htaccess_protection']}</h2>
			 <p style='margin:0px'>
			 	<br />
			 	<strong>{$this->lang->words['sec_ipboard_is_unable_to_write_int']}</strong>
				<br />
				<br />
				{$this->lang->words['sec_please_create_a_file_called_ht']}
				<br />
				<textarea rows='5' cols='70' style='width:98%;height:100px'>$htaccess_pw</textarea>
				<br />
				<br />
				{$this->lang->words['sec_please_create_a_file_called_ht_1']}
				<br />
				<textarea rows='5' cols='70' style='width:98%;height:100px'>$htaccess_auth</textarea>
			 </p>
		</td>
	</tr>
	</table>
</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Rename admin directory details
 *
 * @access	public
 * @return	string		HTML
 */
public function rename_admin_dir() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<form id='mainform'>
<div class='information-box'>
	<table cellpadding='0' cellspacing='0'>
	<tr>
		<td width='1%' valign='top'>
 			<img src='{$this->settings['skin_acp_url']}/images/folder_components/security/id_card_ok.png' alt='{$this->lang->words['sec_information']}' />
		</td>
		<td width='100%' valig='top' style='padding-left:10px'>
 			<h2 style='margin:0px'>{$this->lang->words['sec_renaming_the_cp_sitecontrol_di']}</h2>
			 <p style='margin:0px'>
			 	<br />
			 	{$this->lang->words['sec_ipboard_has_a_dedicated_direct']}
				<br />
				<br />
				<strong>{$this->lang->words['sec_step_1']}</strong>
				<br />
				{$this->lang->words['sec_first_youll_need_to_physically']}
				<br/ >
				{$this->lang->words['sec_locate_the_admin_directory_cho']}
				<br />
				<br />
				<strong>Step 2:</strong>
				{$this->lang->words['sec_locate_the_initdataphp_file_th']}
				<br />{$this->lang->words['sec_near_the_top_of_this_file_youl']}
				<br />
				<pre>//--------------------------------------------------------------------------
// USER CONFIGURABLE ELEMENTS: PATHS & FOLDER NAMES
//--------------------------------------------------------------------------

/**
* CP_DIRECTORY
*
* The name of the CP directory
* @since 2.0.0.2005-01-01
*/
define( 'CP_DIRECTORY', 'admin' );</pre>
				
				<br />
				{$this->lang->words['sec_change_the_line_define_cp_dire']}
				<br />
				<br />
				<strong>{$this->lang->words['sec_your_cp_directory_has_now_been']}</strong>
			 </p>
		</td>
	</tr>
	</table>
</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

}