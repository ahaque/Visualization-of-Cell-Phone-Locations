<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Error log skin file
 * Last Updated: $Date$
 *
 * @author 		$Author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision$
 */
 
class cp_skin_spamlogs extends output
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

public function spamServiceTest( $result )
{
$IPBHTML = "";

if( $result == 'DEBUG_MODE_SUCCESFUL_API_TEST' )
{
$IPBHTML .= <<<HTML
<div class='information-box'>
	<b>{$this->lang->words['slog_api_connected']}</b>
</div>
HTML;
}
else
{
$IPBHTML .= <<<HTML
<div class='information-box warning'>
	<b>{$this->lang->words['slog_api_failed']}: {$result}</b>
</div>
HTML;
}

return $IPBHTML;
}

/**
 * Spam log wrapper
 *
 * @access	public
 * @param	array 		Rows
 * @param	string		Page links
 * @return	string		HTML
 */
public function spamlogsWrapper( $rows, $links ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<script type='text/javascript' src='{$this->settings['js_main_url']}acp.forms.js'></script>
<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm'  id='theAdminForm'>
<input type='hidden' name='do' value='remove' />
<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
<div class="acp-box">
	<h3>{$this->lang->words['slog_spamlogs']}</h3>
	<table class='alternate_rows' width='100%'>
		<tr>
			<th width='5%'>{$this->lang->words['slog_list_id']}</th>
			<th width='25%'>{$this->lang->words['slog_list_date']}</th>
			<th width='15%'>{$this->lang->words['slog_list_code']}</th>
			<th width='25%'>{$this->lang->words['slog_list_msg']}</th>
			<th width='15%'>{$this->lang->words['slog_list_email']}</th>
			<th width='10%'>{$this->lang->words['slog_list_ip']}</th>
			<th width='3%'><input type='checkbox' title="{$this->lang->words['my_checkall']}" id='checkAll' /></th>
		</tr>
HTML;

if( count( $rows ) AND is_array( $rows ) )
{
	foreach( $rows as $row )
	{
		$IPBHTML .= <<<HTML
		<tr>
			<td>{$row['id']}</td>
			<td>{$row['_time']}</td>
			<td>{$row['log_code']}: {$this->lang->words['slog_response_'.$row['log_code']]}</td>
			<td>{$row['log_msg']}</td>
			<td>{$row['email_address']}</td>
			<td>{$row['ip_address']}</td>
			<td><input type='checkbox' name='id_{$row['id']}' value='1' class='checkAll' /></td>
		</tr>
HTML;
	}
}
else
{
	$IPBHTML .= <<<HTML
		<tr>
			<td colspan='7' align='center'>{$this->lang->words['slog_noresults']}</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
	<div class='acp-actionbar'>
        <div class='leftaction'>
			{$links}
        </div>
        <div class='rightaction'>
			<input type="checkbox" id="checkbox" name="type" value="all" />&nbsp;{$this->lang->words['erlog_removeall']}&nbsp;<input type="submit" value="{$this->lang->words['erlog_removechecked']}" class="button primary" />
        </div>
	</div>
</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

}