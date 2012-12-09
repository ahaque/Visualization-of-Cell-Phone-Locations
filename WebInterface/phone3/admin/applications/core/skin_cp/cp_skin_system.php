<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * System tools skin file
 * Last Updated: $Date: 2009-08-27 08:23:24 -0400 (Thu, 27 Aug 2009) $
 *
 * @author 		$Author: josh $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 5050 $
 */
 
class cp_skin_system extends output
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
 * View task manager logs
 *
 * @access	public
 * @param	array 		Rows
 * @return	string		HTML
 */
public function taskManagerLogsShowWrapper( $rows ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='acp-box'>
 <h3>{$this->lang->words['sys_task_manager_logs']}</h3>
 <table class='alternate_rows'>
 <tr>
  <th>{$this->lang->words['sys_task_run']}</th>
  <th>{$this->lang->words['sys_date_run']}</th>
  <th>{$this->lang->words['sys_log_info']}</th>
 </tr>
HTML;

foreach( $rows as $data )
{
$IPBHTML .= <<<HTML
<tr>
 <td><strong>{$data['log_title']}</strong></td>
 <td>{$data['log_date']}</td>
 <td>{$data['log_desc']}</td>
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
 * Task manager logs overview
 *
 * @access	public
 * @param	array 		Last 5 log rows
 * @param	array 		Form elements
 * @return	string		HTML
 */
public function taskManagerLogsOverview( $last5, $form ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$this->lang->words['sys_last_5_run_tasks']}</h3>
	<table class='alternate_rows'>
		<tr>
			<th>{$this->lang->words['sys_task_run']}</th>
			<th>{$this->lang->words['sys_date_run']}</th>
			<th>{$this->lang->words['sys_log_info']}</th>
		</tr>
HTML;

foreach( $last5 as $data )
{
$IPBHTML .= <<<HTML
		<tr>
			 <td><strong>{$data['log_title']}</strong></td>
			 <td>{$data['log_date']}</td>
			 <td>{$data['log_desc']}</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
 	</table>
</div>

<br />

<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=task_log_show' method='post'>
<div class="acp-box">
	<h3>{$this->lang->words['sys_view_task_manager_logs']}</h3>
	<ul class="acp-form alternate_rows">
		<li>
			<label>{$this->lang->words['sys_view_logs_for_task']}</label>
			{$form['task_title']}
		</li>
		<li>
			<label>{$this->lang->words['sys_show_n_log_entries']}</label>
			{$form['task_count']}
		</li>
	</ul>
	<div class="acp-actionbar">
		<input class='button primary' type='submit' value='{$this->lang->words['sys_view_logs']}' />
	</div>
</div>
</form>

<br />

<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=task_log_delete' method='post'>
<div class='acp-box'>
	<h3>{$this->lang->words['sys_delete_task_manager_logs']}</h3>
	<ul class='acp-form alternate_rows'>
		<li>
			<label>{$this->lang->words['sys_delete_logs_for_task']}</label>
			{$form['task_title_delete']}
		</li>
		<li>
			<label>{$this->lang->words['sys_delete_logs_older_than_n_days']}</label>
			{$form['task_prune']}
		</li>
	</ul>
	<div class="acp-actionbar">
		<input class='button primary' type='submit' value='{$this->lang->words['sys_delete_logs']}' />
	</div>
</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Task manager last 5 row entry
 *
 * @access	public
 * @param	array 		Log data
 * @return	string		HTML
 */
public function task_manager_last5_row( $data ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
	<tr>
		 <td width='25%'><strong>{$data['log_title']}</strong></td>
		 <td width='15%'>{$data['log_date']}</td>
		 <td width='45%'>{$data['log_desc']}</td>
	</tr>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Form to add/edit a task
 *
 * @access	public
 * @param	array 		Form elements
 * @param	string		Button text
 * @param	string		Form action
 * @param	string		Type (add|edit)
 * @param	string		Form title
 * @param	array 		Task data
 * @return	string		HTML
 */
public function taskManagerForm( $form, $button, $formbit, $type, $title, $task ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<script type='text/javascript' language='javascript'>
function updatepreview()
{
	var formobj  = document.adminform;
	var dd_wday  = new Array();
	
	dd_wday[0]   = '{$this->lang->words['sys_sunday']}';
	dd_wday[1]   = '{$this->lang->words['sys_monday']}';
	dd_wday[2]   = '{$this->lang->words['sys_tuesday']}';
	dd_wday[3]   = '{$this->lang->words['sys_wednesday']}';
	dd_wday[4]   = '{$this->lang->words['sys_thursday']}';
	dd_wday[5]   = '{$this->lang->words['sys_friday']}';
	dd_wday[6]   = '{$this->lang->words['sys_saturday']}';
	
	var output       = '';
	
	chosen_min   = formobj.task_minute.options[formobj.task_minute.selectedIndex].value;
	chosen_hour  = formobj.task_hour.options[formobj.task_hour.selectedIndex].value;
	chosen_wday  = formobj.task_week_day.options[formobj.task_week_day.selectedIndex].value;
	chosen_mday  = formobj.task_month_day.options[formobj.task_month_day.selectedIndex].value;
	
	var output_min   = '';
	var output_hour  = '';
	var output_day   = '';
	var timeset      = 0;
	
	if ( chosen_mday == -1 && chosen_wday == -1 )
	{
		output_day = '';
	}
	
	if ( chosen_mday != -1 )
	{
		output_day = '{$this->lang->words['sys_on_day']} '+chosen_mday+'.';
	}
	
	if ( chosen_mday == -1 && chosen_wday != -1 )
	{
		output_day = '{$this->lang->words['sys_on']} ' + dd_wday[ chosen_wday ]+'.';
	}
	
	if ( chosen_hour != -1 && chosen_min != -1 )
	{
		output_hour = '{$this->lang->words['sys_at']} '+chosen_hour+':'+formatnumber(chosen_min)+'.';
	}
	else
	{
		if ( chosen_hour == -1 )
		{
			if ( chosen_min == 0 )
			{
				output_hour = '{$this->lang->words['sys_on_every_hour']}';
			}
			else
			{
				if ( output_day == '' )
				{
					if ( chosen_min == -1 )
					{
						output_min = '{$this->lang->words['sys_every_minute']}';
					}
					else
					{
						output_min = '{$this->lang->words['sys_every']} '+chosen_min+' {$this->lang->words['sys_minutes']}.';
					}
				}
				else
				{
					output_min = '{$this->lang->words['sys_at']} '+formatnumber(chosen_min)+' {$this->lang->words['sys_minutes_past_the_first_availab']}';
				}
			}
		}
		else
		{
			if ( output_day != '' )
			{
				output_hour = '{$this->lang->words['sys_at']} ' + chosen_hour + ':00';
			}
			else
			{
				output_hour = '{$this->lang->words['sys_every']} ' + chosen_hour + ' {$this->lang->words['sys_hours']}';
			}
		}
	}
	
	output = output_day + ' ' + output_hour + ' ' + output_min;
	
	$('handy_hint').update( output );
}
							
function formatnumber(num)
{
	if ( num == -1 )
	{
		return '00';
	}
	if ( num < 10 )
	{
		return '0'+num;
	}
	else
	{
		return num;
	}
}

</script>
<form name='adminform' action='{$this->settings['base_url']}{$this->form_code}&amp;do=$formbit&amp;task_id={$task['task_id']}&amp;type=$type&amp;app_dir={$task['task_application']}' method='post' id='task_manager'>
<input type='hidden' name='task_cronkey' value='{$task['task_cronkey']}' />
<div class="acp-box">
	<h3>$title</h3>
  	
	<ul class="acp-form alternate_rows">
		<li>
			<label>{$this->lang->words['sys_task_title']}</label>
			{$form['task_title']}
		</li>
		<li>
			<label>{$this->lang->words['sys_task_short_description']}</label>
			{$form['task_description']}
		</li>
		<li>
			<label>{$this->lang->words['sys_task_application']}</label>
			{$form['task_application']}
		</li>
		<li>
			<label>{$this->lang->words['sys_task_php_file_to_run']}<span class="desctext">{$this->lang->words['sys_this_is_the_php_file_that_is_r']}</span></label>
			/admin/applications/{task_application}/tasks/{$form['task_file']}
		</li>
		<li>
   			<label class='head'>{$this->lang->words['sys_time_options']}</label>
		</li>
	    <li>
			<label>{$this->lang->words['sys_task_time_minutes']}<span class="desctext">{$this->lang->words['sys_choose_every_minute_to_run_eac']}</span></label>
			{$form['task_minute']}
		</li>
		<li>
			<label>{$this->lang->words['sys_task_time_hours']}<span class="desctext">{$this->lang->words['sys_choose_every_hour_to_run_each_']}</span></label>
			{$form['task_hour']}
		</li>
		<li>
			<label>{$this->lang->words['sys_task_time_week_day']}<span class="desctext">{$this->lang->words['sys_choose_every_day_to_run_each_d']}</span></label>
			{$form['task_week_day']}
		</li>
		<li>
			<label>{$this->lang->words['sys_task_time_month_day']}<span class="desctext">{$this->lang->words['sys_choose_every_day_to_run_each_d_1']}</span></label>
			{$form['task_month_day']}
		</li>
		<li>
			<label>Task will therefore run: </label><em id='handy_hint'><span style='color: gray;'>Select time units above</span></em>
		</li>
		<li>
			<label>{$this->lang->words['sys_enable_task_logging']}<span class="desctext">{$this->lang->words['sys_will_write_to_the_task_log_eac']}</span></label>
			{$form['task_log']}
		</li>
		<li>
			<label>{$this->lang->words['sys_enable_task']}<span class="desctext">{$this->lang->words['sys_if_you_are_using_cron_you_migh']}</span></label>
			{$form['task_enabled']}
		</li>
		<li>
			<label>{$this->lang->words['sys_task_key']}<span class="desctext">{$this->lang->words['sys_this_is_used_to_call_a_task_wh']}</span></label>
			{$form['task_key']}
		</li>
HTML;
//startif
if ( IN_DEV )
{		
$IPBHTML .= <<<HTML
		<li>
			<label>{$this->lang->words['sys_task_safe_mode']}<span class="desctext">{$this->lang->words['sys_if_set_to_yes_this_will_not_be']}</span></label>
			{$form['task_safemode']}
		</li>
HTML;
}//endif
$IPBHTML .= <<<HTML
	</ul>
	<div class='acp-actionbar'>
		<input type='submit' class='button primary' value='$button' />
	</div>
</div>
</form>

<script type='text/javascript'>
	updatepreview( );
</script>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Task manager overview
 *
 * @access	public
 * @param	array 		Tasks
 * @param	string		Current date
 * @return	string		HTML
 */
public function taskManagerOverview( $tasks, $date ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['sys_system_schedular']}</h2>
	<ul class='context_menu'>
		<li><a href='{$this->settings['base_url']}module=system&amp;section=taskmanager&amp;do=task_add' style='text-decoration:none'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/add.png' alt='' /> {$this->lang->words['sym_add_new_task']}</a></li>
		<li><a href='{$this->settings['base_url']}module=system&amp;section=taskmanager&amp;do=task_export_xml'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/export.png' alt='' /> {$this->lang->words['sym_export_tasksxml']}</a></li>
		<li><a href='{$this->settings['base_url']}module=system&amp;section=taskmanager&amp;do=task_rebuild_xml'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/import.png' alt='' /> {$this->lang->words['sym_import_tasksxml']}</a></li>
	</ul>
</div>

<ul id='tab_taskmanager' class='tab_bar no_title'>

HTML;

foreach( ipsRegistry::$applications as $app_dir => $app_data )
{
	if ( ipsRegistry::$request['tab'] AND $app_dir == ipsRegistry::$request['tab'] )
	{
		$_default_tab = $app_dir;
	}
	
	if ( isset( $tasks[ $app_dir ] ) && is_array( $tasks[ $app_dir ] ) and count( $tasks[ $app_dir ] ) )
	{
$IPBHTML .= <<<HTML
	<li id='tabtab-{$app_dir}' class=''>{$app_data['app_title']}</li>
	
HTML;
	}
}

$IPBHTML .= <<<HTML
</ul>

<script type="text/javascript">
//<![CDATA[
document.observe("dom:loaded",function() 
{
ipbAcpTabStrips.register('tab_taskmanager');
ipbAcpTabStrips.doToggle($('tabtab-{$_default_tab}'));
});
 //]]>
</script>

<div class='acp-box alternate_rows'>

HTML;

foreach( ipsRegistry::$applications as $app_dir => $app_data )
{
	if ( isset( $tasks[ $app_dir ] ) && is_array( $tasks[ $app_dir ] ) and count( $tasks[ $app_dir ] ) )
	{
$IPBHTML .= <<<HTML
	<div id='tabpane-{$app_dir}'>
		<table class='double_pad alternate_rows'>
		 <tr>
		  <th>{$this->lang->words['sys_title']}</th>
		  <th>{$this->lang->words['sys_next_run']}</th>
		  <th width='5%'>{$this->lang->words['sys_min']}</th>
		  <th width='5%'>{$this->lang->words['sys_hour']}</th>
		  <th width='5%'>{$this->lang->words['sys_mday']}</th>
		  <th width='5%'>{$this->lang->words['sys_wday']}</th>
		  <th width='1%'>{$this->lang->words['sys_options']}</th>
		 </tr>
		
HTML;
		foreach( $tasks[ $app_dir ] as $row )
		{
			$row['_class'] = isset( $row['_class'] ) ? $row['_class'] : '';
			$row['_title'] = isset( $row['_title'] ) ? $row['_title'] : '';
			
$IPBHTML .= <<<HTML
		<tr>
		 <td>
			
		  	<strong{$row['_class']}>{$row['task_title']}{$row['_title']}</strong>
		
			<div style='float: right'>
				<a href='#' onclick="$('pop{$row['task_id']}').toggle();return false;" title='{$this->lang->words['sys_how_curl_to_use_in_a_cron']}'><img src='{$this->settings['skin_acp_url']}/images/folder_components/tasks/task_cron.gif' border='0' alt='Cron' /></a>
				<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=task_run_now&amp;task_id={$row['task_id']}&amp;tab={$app_dir}' title='{$this->lang->words['run_task_now']}{$row['task_id']}'><img src='{$this->settings['skin_acp_url']}/images/folder_components/tasks/{$row['_image']}'  border='0' alt='{$this->lang->words['sys_run']}' /></a>
			</div>
			 <div style='color:gray'><em>{$row['task_description']}</em></div>
			   <div align='center' style='position:absolute;width:auto;display:none;text-align:center;background:#EEE;border:2px outset #555;padding:4px' id='pop{$row['task_id']}'>
				curl -s -o /dev/null "{$this->settings['board_url']}/index.{$this->settings['php_ext']}?app=core&amp;module=task&amp;ck={$row['task_cronkey']}"
			   </div>
		
			
		 </td>
		 <td>{$row['_next_run']}</td>
		 <td>{$row['task_minute']}</td>
		 <td>{$row['task_hour']}</td>
		 <td>{$row['task_month_day']}</td>
		 <td>{$row['task_week_day']}</td>
		 <td>
		 	<img class='ipbmenu' id="menu{$row['task_id']}" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt='{$this->lang->words['sys_options']}' />
			<ul class='acp-menu' id='menu{$row['task_id']}_menucontent'>
				<li class='icon edit'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=task_edit&amp;task_id={$row['task_id']}'>{$this->lang->words['sys_edit_task']}</a></li>
				<li class='icon manage'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=task_unlock&amp;task_id={$row['task_id']}'>{$this->lang->words['sys_unlock_task']}</a></li>
				<li class='icon delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}&amp;do=task_delete&amp;task_id={$row['task_id']}");'>{$this->lang->words['sys_delete_task']}</a></li>
				<li class='icon export'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=task_export&amp;task_id={$row['task_id']}'>{$this->lang->words['t_export_single']}</a></li>
			</ul>
		 </td>
		</tr>
HTML;
		}
$IPBHTML .= <<<HTML
		</table>
	</div>
	
HTML;
	}
}
	
$IPBHTML .= <<<HTML
</div>
<br />
<div align='center' class='desctext'><em>{$this->lang->words['sys_all_times_gmt_gmt_time_now_is']} $date</em></div>
<br />

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=task_import' method='post' enctype='multipart/form-data'>
<div class="acp-box">
	<h3>{$this->lang->words['t_import_single']}</h3>
			<ul class="acp-form alternate_rows">
				<li>
					<label>{$this->lang->words['upload_task_xml']}<span class='desctext'>{$this->lang->words['upload_task_dupe']}</span></label>
					<input type='file' name='FILE_UPLOAD' />
				</li>
			<ul>
	<div class="acp-actionbar">
		<input type='submit' value='{$this->lang->words['task_import_button']}' class="button primary" />
	</div>
</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * ACP latest logins row
 *
 * @access	public
 * @param	array 		Log records
 * @return	string		HTML
 */
public function acp_last_logins_row( $r ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<tr>
    <td>
    	<img src='{$this->settings['skin_acp_url']}/_newimages/icons/user.png' border='0' alt='-' class='ipd' />
    </td>
	<td>
		<strong>{$r['admin_username']}</strong>
		<div class='desctext'>IP: {$r['admin_ip_address']}</div>
	</td>
    <td>{$r['_admin_time']}</td>
    <td>
    	<img src='{$this->settings['skin_acp_url']}/images/{$r['_admin_img']}' border='0' alt='-' class='ipd' />
    </td>
    <td>
		<a href='#' onclick="return acp.openWindow('{$this->settings['base_url']}module=system&amp;section=loginlog&amp;do=view_detail&amp;detail={$r['admin_id']}', 700, 500)" title='{$this->lang->words['sys_view_details']}'><img src='{$this->settings['skin_acp_url']}/images/folder_components/index/view.png' border='0' alt='-' class='ipd' /></a>
	</td>
</tr>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * ACP latest logins wrapper
 *
 * @access	public
 * @param	string		Content
 * @return	string		HTML
 */
public function acp_last_logins_wrapper($content,$links) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class="acp-box">
	<h3>{$this->lang->words['sys_last_5_acp_log_in_attempts']}</h3>
    <table class="alternate_rows" width='100%'>
        <tr>
            <th width='1%'>&nbsp;</th>
            <th width='40%'>{$this->lang->words['sys_name']}</th>
            <th width='49%'>{$this->lang->words['sys_date']}</th>
            <th width='5%'>{$this->lang->words['sys_status']}</th>
            <th width='5%'>{$this->lang->words['sys_log']}</th>
        </tr>
    	$content
    </table>
	<div class='acp-actionbar'>
    	<div class="rightaction">
			{$links}
        </div>
	</div>
</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Latest logins detail
 *
 * @access	public
 * @param	array 		Log data
 * @return	string		HTML
 */
public function acp_last_logins_detail( $log ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>Log in Details</h3>
	<table width='100%' class='alternate_rows'>
		<tr>
			<th colspan='2'>{$this->lang->words['logindetail_basic']}</th>
		</tr>
		<tr>
			<td width='30%'>{$this->lang->words['logindetail_username']}</td>
			<td width='70%'>{$log['admin_username']}</td>
		</tr>
		<tr>
			<td>{$this->lang->words['logindetail_ip']}</td>
			<td>{$log['admin_ip_address']}</td>
		</tr>
		<tr>
			<td>{$this->lang->words['logindetail_time']}</td>
			<td>{$log['_admin_time']}</td>
		</tr>
		<tr>
			<td>{$this->lang->words['logindetail_success']}</td>
			<td><img src='{$this->settings['skin_acp_url']}/images/{$log['_admin_img']}' alt='-' /></td>
		</tr>
		<tr>
			<th colspan='2'>{$this->lang->words['logindetail_post']}</th>
		</tr>
HTML;
		if ( is_array( $log['_admin_post_details']['post'] ) AND count( $log['_admin_post_details']['post'] ) )
		{
			foreach( $log['_admin_post_details']['post'] as $k => $v )
			{
				$IPBHTML .= "<tr>
								<td width='30%'>{$k}</td>
								<td width='70%'>{$v}</td>
							</tr>";
			}
		}
$IPBHTML .= <<<HTML
		<tr>
			<th colspan='2'>{$this->lang->words['logindetail_get']}</th>
		</tr>
HTML;
		if ( is_array( $log['_admin_post_details']['get'] ) AND count( $log['_admin_post_details']['get'] ) )
		{
			foreach( $log['_admin_post_details']['get'] as $k => $v )
			{
				$IPBHTML .= "<tr>
								<td width='30%'>{$k}</td>
								<td width='70%'>{$v}</td>
							</tr>";
			}
		}
$IPBHTML .= <<<HTML
	</table>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * System module welcome page
 *
 * @access	public
 * @param	array 		Log data
 * @param	string		ACP latest logins
 * @return	string		HTML
 * @deprecated	 Don't think this is used, mycp is used now instead
 */
public function system_welcome_page($data, $acplogins) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class="acp-box">
	<h3>{$this->lang->words['sys_system_overview']}</h3>
	<table class='alternate_rows' width='100%'>
		<tr>
			<td>
				<strong>{$this->lang->words['sys_php_version']}</strong>
			</td>
			<td>
			<a href='#' onclick='return acp.openWindow( "{$this->settings['base_url']}module=palette&amp;section=system&amp;do=phpinfo", 800, 600 );'>{$data['phpversion']} ({$data['phpsapi']})</a>
			</td>
			<td>
				<strong>{$this->lang->words['sys_sql_version']}</strong>
			</td>
			<td>
				{$data['sqldriver']} {$data['sqlversion']}
			</td>		
		</tr>
	</table>
</div>
 <br />
 $acplogins
HTML;

if ( IN_DEV )
{
$IPBHTML .= <<<HTML
<br />
<div class='tableborder'>
 <div class='tableheader'>{$this->lang->words['sy_devexport']}</div>
 	<div class='tablepad'>
		<a href='{$this->settings['base_url']}&amp;module=content&amp;section=tools&amp;do=tools-email-export'>{$this->lang->words['sy_devemail']}</a>
		&middot; <a href='{$this->settings['base_url']}&amp;module=content&amp;section=tools&amp;do=tools-export-xml'>{$this->lang->words['sy_devtemplate']}</a>
		&middot; <a href='{$this->settings['base_url']}&amp;module=content&amp;section=tools&amp;do=tools-export-content-xml'>{$this->lang->words['sy_devcontent']}</a>
		&middot; <a href='{$this->settings['base_url']}&amp;module=content&amp;section=tools&amp;do=tools-export-masterskin'>{$this->lang->words['sy_devskin']}</a>
		&middot; <a href='{$this->settings['base_url']}&amp;module=system&amp;section=filetypes&amp;do=master_xml_export'>{$this->lang->words['sy_devfile']}</a>
		&middot; <a href='{$this->settings['base_url']}&amp;module=content&amp;section=pages&amp;do=master_xml_export'>{$this->lang->words['sy_devpages']}</a>
		&middot; <a href='{$this->settings['base_url']}&amp;module=system&amp;section=staff&amp;do=master_xml_export'>{$this->lang->words['sy_devgroups']}</a>
		&middot; <a href='{$this->settings['base_url']}&amp;module=tools&amp;section=login&amp;do=master_xml_export'>{$this->lang->words['sy_devlogin']}</a>
		&middot; <a href='{$this->settings['base_url']}&amp;module=system&amp;section=taskmanager&amp;do=master_xml_export'>{$this->lang->words['sy_devtasks']}</a>
		&middot; <a href='{$this->settings['base_url']}&amp;module=system&amp;section=components&amp;do=master_xml_export'>{$this->lang->words['sy_devcomponents']}</a>
	</div>
</div>
HTML;
}

//--endhtml--//
return $IPBHTML;
}

/**
 * Online user record entry
 *
 * @access	public
 * @param	string		Name
 * @param	string		IP address
 * @param	string		Login time/date
 * @param	string		Last click time/date
 * @param	string		Current location
 * @return	string		HTML
 */
public function online_user_row($name, $ip_address, $log_in, $click, $location) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<tr>
 <td width='20%'>$name</td>
 <td width='20%'>$ip_address</td>
 <td width='15%' align='center'>$log_in</td>
 <td width='15%' align='center'>$click</td>
 <td width='20%'>$location</td>
</tr>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Translation Session
 *
 * @access	public
 * @param	array 		Languages
 * @return	string		HTML
 */
public function languages_translateExt( $data, $lang )
{
$HTML = <<<HTML
<script type="text/javascript" src="{$this->settings['js_main_url']}acp.languages.js"></script>
<div class='information-box'>
 <h4><img src='{$this->settings['skin_acp_url']}/_newimages/icons/information.png' alt='' />&nbsp; {$this->lang->words['ext_top_title']}</h4>
 {$this->lang->words['ext_top_desc']}
</div>
<br />
<div class='section_title'>
	<h2>{$this->lang->words['ext_title_for']} {$data['lang_title']}</h2>
	<ul class='context_menu'>
		<li class='closed'>
			<a id='langKill' href='#'> <img src='{$this->settings['skin_acp_url']}/_newimages/icons/cross.png' alt='' />{$this->lang->words['ext_button_finish']}</a>
		</li>
		<li>
			<a id='sel__none' href='#'> <img src='{$this->settings['skin_acp_url']}/_newimages/icons/template.png' alt='' /> {$this->lang->words['ext_button_unselect']}</a>
		</li>
		<li>
			<a id='sel__all' href='#'> <img src='{$this->settings['skin_acp_url']}/_newimages/icons/page_add.png' alt='' /> {$this->lang->words['ext_button_selectall']}</a>
		</li>
		<li>
			<a id='sel__modified' href='#'> <img src='{$this->settings['skin_acp_url']}/_newimages/icons/pencil.png' alt='' />{$this->lang->words['ext_button_smodified']}</a>
		</li>
	</ul>
</div>
<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=translateImport' method='post'>
<div class='acp-box'>
	<h3>Current Files</h3>
	<table class="alternate_rows" width='100%'>
		<tr>
			<th width='1%'>&nbsp;</th>
			<th width='1%'>&nbsp;</th>
			<th width='40%'>{$this->lang->words['ext_tbl_file']}</th>
			<th width='30%'>{$this->lang->words['ext_tbl_local']}</th>
			<th width='30%'>{$this->lang->words['ext_tbl_db']}</th>
			
		</tr>
HTML;

foreach( $data['files'] as $name => $data )
{
	$mtime  = $this->registry->class_localization->getDate( $data['mtime'], 'long' );
	$dbtime = $this->registry->class_localization->getDate( $data['dbtime'], 'long' );
	$style  = ( $data['mtime'] > $data['dbtime'] ) ? ' class="_amber"' : '';
	$class  = ( $data['mtime'] > $data['dbtime'] ) ? ' selected' : '';
	$jsname = str_replace( '.', '-', $name );
	
$HTML .= <<<HTML
		<tr{$style} id='tr-$jsname'>
			<td><img src='{$this->settings['skin_acp_url']}/_newimages/icons/template.png' alt='' /></td>
			<td><input type='checkbox' name='cb[$name]' value='1' id='cb-$jsname' class='cbox{$class}' /></td>
			<td><div>{$name}</div>
			<td>{$mtime}</td>
			<td>{$dbtime}</td>
			
 		</tr>
HTML;
}
$HTML .= <<<HTML
 	</table>
 	<div class='acp-actionbar'>
 		<input class='button primary right' type='submit' value=' {$this->lang->words['ext_tbl_submit']} ' />
 	</div>
</div>
</form>
HTML;

return $HTML;
}


/**
 * List installed languages
 *
 * @access	public
 * @param	array 		Languages
 * @return	string		HTML
 */
public function languages_list( $rows, $hasTranslate )
{

if ( $hasTranslate )
{
	$this->lang->words['ext_translation_detected'] = sprintf( $this->lang->words['ext_translation_detected'], "{$this->settings['base_url']}&{$this->form_code}&do=translateExtSplash" );
	
$HTML .= <<<HTML
<div class='information-box'>
 <h4><img src='{$this->settings['skin_acp_url']}/_newimages/icons/information.png' alt='' />&nbsp; {$this->lang->words['ext_top_title']}</h4>
 {$this->lang->words['ext_translation_detected']}
</div>
<br />
HTML;
}

$HTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['language_list_page_title']}</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['language_list_page_title']}</h3>
	<table class="alternate_rows" width='100%'>
		<tr>
			<th width='30%' align=''>{$this->lang->words['language_list_title']}</th>
			<th width='10%' align='center'>{$this->lang->words['language_list_local']}</th>
			<th width='20%' align='center'>{$this->lang->words['language_list_date']}</th>
			<th width='20%' align='center'>{$this->lang->words['language_list_money']}</th>
			<th width='10%' style='text-align: center'>{$this->lang->words['language_list_default']}</th>
			<th width='10%' align='center'>&nbsp;</th>
		</tr>
HTML;

foreach( $rows as $r )
{
$HTML .= <<<HTML
		<tr>
			<td width='30%'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=list_word_packs&amp;id={$r['id']}'>{$r['title']}</a></td>
			<td width='10%'>{$r['local']}</td>
			<td width='20%'>{$r['date']}</td>
			<td width='20%'>{$r['money']}</td>
			<td width='10%' style='text-align: center'>{$r['default']}</td>			
			<td width='10%' align="right">{$r['menu']}</td>
 		</tr>
HTML;
}
$HTML .= <<<HTML
 	</table>
</div>
<br />
<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=language_do_import' enctype='multipart/form-data' method='post'>
<div class='acp-box'>
	<h3>{$this->lang->words['sys_import_language_xml']}</h3>
    <ul class='acp-form alternate_rows'>
        <li>
            <label>{$this->lang->words['sys_upload_xml_language_file_from_']}<span class='desctext'>{$this->lang->words['sys_duplicate_entries_will_not_be_']}</span></label>
            <input class='textinput' type='file' size='30' name='FILE_UPLOAD' />
        </li>
        <li>
            <label>{$this->lang->words['sys_or_enter_the_filename_of_the_x']}<span class='desctext'>{$this->lang->words['sys_the_file_must_be_uploaded_into']}</span></label>
            <input class='textinput' type='text' size='30' name='file_location' />
        </li>
    </ul>
    <div class="acp-actionbar">
        <input type='submit' class='button primary' value='{$this->lang->words['sys_import']}' />
    </div>
</div>
</form>
HTML;

if ( IN_DEV )
{
$HTML .= <<<HTML
<br />
<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=language_do_indev_import' method='post'>
<div class='acp-box'>
	<h3>{$this->lang->words['sys_developers_language_cache_impo']}</h3>
    <ul class='acp-form alternate_rows'>
        <ul>
        	<li>
        		<label>{$this->lang->words['sys_indev_export']}</label>
        		<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=language_do_indev_export'>{$this->lang->words['sys_indev_export_go']}</a>
        	</li>
        	<li>
				<label>{$this->lang->words['sys_select_the_application_languag']}<span class='desctext'>{$this->lang->words['sys_this_will_examine_the_corecach']}</span></label>
	            <select name='apps[]' multiple='multiple' size=5>
HTML;
foreach( ipsRegistry::$applications as $app => $data )
{
    $HTML .= "<option value='$app'>{$data['app_title']}</option>\n";
}

$HTML .= <<<HTML
            	</select>
        	</li>
		</ul>
	<div class="acp-actionbar">
    	<input type='submit' class='button primary' value='{$this->lang->words['sys_import']}' />
	</div>
</div>
</form>
HTML;
}

return $HTML;
}

/**
 * List word packs in a language
 *
 * @access	public
 * @param	int			Language id
 * @param	array 		Word packs
 * @param	string		Page title
 * @return	string		HTML
 */
public function languageWordPackList( $id, $packs, $title='' )
{
$HTML = <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['sym_manage_languages']}</h2>
	
	<ul class='context_menu'>
		<li><a href='{$this->settings['base_url']}module=languages&amp;section=manage_languages&amp;do=list_word_packs&id={$this->request['id']}' style='text-decoration:none'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/view.png' alt='' /> {$this->lang->words['sym_view_word_packs']}</a></li>
		<li><a href='{$this->settings['base_url']}module=languages&amp;section=manage_languages&amp;do=edit_lang_info&id={$this->request['id']}' style='text-decoration:none'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/pencil.png' alt='' /> {$this->lang->words['sym_edit_language_pack_information']}</a></li>
		<li><a href='{$this->settings['base_url']}module=languages&amp;section=manage_languages&amp;do=add_word_entry&id={$this->request['id']}' style='text-decoration:none'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/add.png' alt='' /> {$this->lang->words['sym_add_new_language_entry']}</a></li>
	</ul>
</div>

<form name='theForm' method='post' action='{$this->settings['base_url']}module=languages&amp;section=manage_languages' id='searchform' enctype='multipart/form-data'>
	<input type='hidden' name='do' value='edit_word_pack' />
	<input type='hidden' name='id' value='{$this->request['id']}' />
	<input type='hidden' name='secure_key' value='{this->registry->adminFunctions->generated_acp_hash}' />

	{$this->lang->words['sym_find']}: <input type='text' name='search' value='{$this->request['search']}' class='inputtext'> <input type='image' src='{$this->settings['skin_acp_url']}/images/search_icon.gif' value='{$this->lang->words['sym_submit']}' alt='{$this->lang->words['sym_find']}' class='ipd'>
</form>	

{$packs}
HTML;

return $HTML;
}

/**
 * Edit a word pack
 *
 * @access	public
 * @param	int			Word pack id
 * @param	array 		Language bits
 * @param	string		Page links
 * @return	string		HTML
 */
public function languageWordPackEdit( $id, $lang, $pages='' )
{
	$title	= $this->request['search'] ? $this->lang->words['lang_search_results'] : $this->lang->words['language_word_pack_edit'] . ': "' . $this->request['word_pack'] . '"';
	
$HTML = <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['sym_manage_languages']}</h2>
	
	<ul class='context_menu'>
		<li><a href='{$this->settings['base_url']}module=languages&amp;section=manage_languages&amp;do=list_word_packs&amp;id={$this->request['id']}' style='text-decoration:none'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/view.png' alt='' /> {$this->lang->words['sym_view_word_packs']}</a></li>
		<li><a href='{$this->settings['base_url']}module=languages&amp;section=manage_languages&amp;do=edit_lang_info&amp;id={$this->request['id']}' style='text-decoration:none'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/pencil.png' alt='' /> {$this->lang->words['sym_edit_language_pack_information']}</a></li>
		<li><a href='{$this->settings['base_url']}module=languages&amp;section=manage_languages&amp;do=add_word_entry&amp;id={$this->request['id']}&amp;word_pack={$this->request['word_pack']}' style='text-decoration:none'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/add.png' alt='' /> {$this->lang->words['sym_add_new_language_entry']}</a></li>
		<li><a href='{$this->settings['base_url']}module=languages&amp;section=manage_languages&amp;do=edit_word_pack&amp;id={$this->request['id']}&amp;word_pack={$this->request['word_pack']}&amp;filter=outofdate' style='text-decoration:none'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/cog.png' alt='' /> {$this->lang->words['sym_word_out_of_date_check']}</a></li>
	</ul>
</div>
<form name='theForm' method='post' action='{$this->settings['base_url']}{$this->form_code}' id='mainform' enctype='multipart/form-data'>
	<input type='hidden' name='do' value='do_edit_word_pack' />
	<input type='hidden' name='id' value='{$id}' />
	<input type='hidden' name='pack' value='{$this->request['word_pack']}'/>
	<input type='hidden' name='st' value='{$this->request['st']}'/>
	<input type='hidden' name='search' value='{$this->request['search']}'/>
	<input type='hidden' name='filter' value='{$this->request['filter']}'/>
	<input type='hidden' name='secure_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	{$pages}
	<br style='clear: both' />
	<div class='acp-box'>		
		<h3>{$title}</h3>
		<table class='form_table triple_pad alternate_rows' cellpadding='0' cellspacing='0'>
			<tr>
				<th style='width: 42%'>{$this->lang->words['language_word_pack_current']}</th>
				<th style='width: 42%'>{$this->lang->words['language_word_pack_new']}</th>
				<th style='width: 16%'>&nbsp;</th>
			</tr>		
HTML;

$tabIndex	= 1;

foreach( $lang as $l )
{
	$css    = ( $l['custom'] ) ? 'tablerow2queued' : 'tablerow2';
	$revert = ( $l['custom'] ) ? "<a href='{$this->settings['base_url']}{$this->form_code}&do=revert&word_id={$l['id']}&word_pack={$l['pack']}&id={$id}' class='dropdown-button'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/arrow_rotate_anticlockwise.png' /></a>&nbsp;" : '';
	$edit   = IN_DEV ? "<a href='{$this->settings['base_url']}{$this->form_code}&do=edit_word_entry&&word_id={$l['id']}&word_pack={$l['pack']}&id={$id}' class='dropdown-button'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/pencil.png' /></a>&nbsp;" : '';
	$pack	= $this->request['search'] ? 
				"<span class='desctext'>{$this->lang->words['l_wordpack_prefix']}<a href='{$this->settings['base_url']}{$this->form_code}&amp;word_pack={$l['pack']}&amp;do=edit_word_pack&amp;id={$id}'><strong>{$l['pack']}</strong></a></span>" : 
				'';
	
$HTML .= <<<HTML
			<tr class='language_editor'>
				<td>
					<div class='information-box'>
						<h4>{$l['key']}</h4>
						{$l['default']}
					</div>
					{$pack}
				</td>
				<td>
					<br />
					<textarea tabindex='{$tabIndex}' name='lang[{$l['id']}]' cols='50' id='word_{$l['id']}_new' class='new_lang'>{$l['custom']}</textarea>
				</td>
				<td style='text-align: right'>
					{$revert}
					{$edit}
					<a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}&amp;do=remove_word_entry&amp;word_id={$l['id']}&amp;word_pack={$l['pack']}&amp;id={$id}&amp;st={$this->request['st']}");' class='dropdown-button'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/delete.png' /></a>
				</td>
			</tr>
HTML;
	
	$tabIndex++;
}
	 	
$HTML .= <<<HTML
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value=' {$this->lang->words['language_word_pack_go']} ' class='button primary' />
		</div>		
	</div>
	<br />
	{$pages}	
</form>
HTML;

return $HTML;
}

/**
 * Language information form
 *
 * @access	public
 * @param	string		Action code
 * @param	int			Language pack id
 * @param	string		Form title
 * @param	string		Form header
 * @param	array 		Language pack id
 * @param	string		Button text
 * @return	string		HTML
 */
public function languageInformationForm( $op, $id, $title, $header, $data, $button )
{
$HTML = <<<HTML
<div class='information-box'>
	$header		
</div>
<br />

<form name='theForm' method='post' action='{$this->settings['base_url']}{$this->form_code}' id='mainform' enctype='multipart/form-data'>
	<input type='hidden' name='do' value='{$op}' />
	<input type='hidden' name='id' value='{$id}' />
	<input type='hidden' name='secure_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$title}</h3>
		
		<ul class='acp-form alternate_rows'>
			<li>
				<label>{txt.language_form_title}</label>
				<input type='text' name='lang_title' value='{$data['lang_title']}' size='50' class='inputtext' />
		 	</li>
			<li>
				<label>{txt.language_form_locale}</label>
				<input type='text' name='lang_short' value='{$data['lang_short']}' size='50' class='inputtext' />
		 	</li>
			<li>
				<label>{txt.language_is_rtl}</label>
				{$data['lang_isrtl']}
		 	</li>	
			<li>
				<label>{txt.language_form_default}</label>
				{$data['lang_default']}
		 	</li>		 		
		</ul>
		<div class='acp-actionbar'>
			<div class='centeraction'>
				<input type='submit' value=' $button ' class='button primary' />
			</div>
		</div>
	</div>	 	
</form>
HTML;

return $HTML;
}

/**
 * Show the application languages list
 *
 * @access	public
 * @param	string		Application
 * @param	array 		Packs
 * @param	array 		Stats
 * @param	array 		Menus
 * @return	string		HTML
 */
public function languageAppPackList( $app, $packs, $stats, $menus )
{
$HTML = <<<HTML
<div class='acp-box'>
	<table class='alternate_rows' width='100%'>
		<tr>
			<th width='30%' align=''>{txt.language_pack_name}</th>
			<th width='25%' align='center'>{txt.language_total_entries}</th>
			<th width='25%' align='center'>{txt.language_customized_entries}</th>
			<th width='30%' align='center'>{txt.language_out_of_date_entries}</th>
			<th width='10%' align='center'>&nbsp;</th>
		</tr>
HTML;

if( count( $packs ) )
{
	foreach( $packs as $r )
	{
		$stats[$r]['custom'] = isset( $stats[$r]['custom'] ) ? $stats[$r]['custom'] : '&nbsp;';
$HTML .= <<<HTML
		<tr>
			<td width='30%' valign='middle'><a href='{$this->settings['base_url']}{$this->form_code}&word_pack={$app}/{$r}&do=edit_word_pack&id={$this->request['id']}'>{$r}</a></td>
			<td width='25%' valign='middle' align='center'>{$stats[$r]['total']}</td>
			<td width='25%' valign='middle' align='center'>{$stats[$r]['custom']}</td>
			<td width='30%' valign='middle' align='center'><a href='{$this->settings['base_url']}{$this->form_code}&word_pack={$app}/{$r}&do=edit_word_pack&id={$this->request['id']}&filter=1'>{$stats[$r]['outofdate']}</a></td>
			<td width='10%' align='center' valign='middle'>{$menus[$r]}</td>
 		</tr>
HTML;
	}
}
else 
{
$HTML .= <<<HTML
	<tr>
		<td colspan='4'><i>There are no word packs for this application</i></td>
	</tr>
HTML;
}
$HTML .= <<<HTML
 	</table>
</div>
HTML;

return $HTML;
}

/**
 * Add/edit a word in a language pack
 *
 * @access	public
 * @param	string		Action
 * @param	int			Word id
 * @param	int			Language pack id
 * @param	string		Form title
 * @param	string		Form header
 * @param	array 		Word data
 * @param	string		Button text
 * @return	string		HTML
 */
public function languageWordEntryForm( $op, $word_id, $lang_id, $title, $header, $data, $button )
{
$HTML = <<<HTML
<form name='theForm' method='post' action='{$this->settings['base_url']}{$this->form_code}' id='mainform' enctype='multipart/form-data'>
	<input type='hidden' name='do' value='{$op}' />
	<input type='hidden' name='id' value='{$lang_id}' />
	<input type='hidden' name='word_id' value='{$word_id}' />
	<input type='hidden' name='secure_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>$title</h3>
		
		<ul class='acp-form alternate_rows'>
			<li>
				<label>Application</label>
				<select name='word_app' class='inputtext'>{$data['word_app']}</select>
	 		</li>
			<li>
				<label>{txt.language_pack_name}</label>
				<input type='text' name='word_pack_db' value='{$data['word_pack']}' size='50' class='inputtext' />
	 		</li>
			<li>
				<label>{txt.language_key}</label>
				<input type='text' name='word_key' value='{$data['word_key']}' size='50' class='inputtext' />
	 		</li>
			<li>
				<label>{txt.language_default}</label>
				<textarea name='word_default' class='inputtext' cols='50'>{$data['word_default']}</textarea>
	 		</li>  			
		</ul>
		
		<div class='acp-actionbar'>
			<input type='submit' value=' $button ' class='button primary' />
		</div>
	</div>	 	
</form>
HTML;

return $HTML;
}


}