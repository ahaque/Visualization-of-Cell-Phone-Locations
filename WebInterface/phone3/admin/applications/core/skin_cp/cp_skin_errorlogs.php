<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Error log skin file
 * Last Updated: $Date: 2009-05-28 13:24:24 -0400 (Thu, 28 May 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 4699 $
 */
 
class cp_skin_errorlogs extends output
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
 * Error log wrapper
 *
 * @access	public
 * @param	array 		Rows
 * @param	string		Page links
 * @return	string		HTML
 */
public function errorlogsWrapper( $rows, $links ) {

$form_array 		= array(
							0 => array( 'log_error'      , $this->lang->words['error_log_error']),
							2 => array( 'log_error_code' , $this->lang->words['error_log_code'] ),
							3 => array( 'log_request_uri', $this->lang->words['error_log_uri'] ),
							4 => array( 'members_display_name', $this->lang->words['error_log_member'] ),
							);
$type_array 		= array(
							0 => array( 'exact'	, $this->lang->words['erlog_exact'] ),
							1 => array( 'loose'	, $this->lang->words['erlog_loose'] ),
						 	);
$form				= array();

$form['type']		= $this->registry->output->formDropdown( "type" , $form_array, $this->request['type'] );
$form['match']		= $this->registry->output->formDropdown( "match", $type_array, $this->request['match'] );
$form['string']		= $this->registry->output->formInput( "string", $this->request['string'] );

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<script type='text/javascript' src='{$this->settings['js_main_url']}acp.forms.js'></script>
<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm'  id='theAdminForm'>
<input type='hidden' name='do' value='remove' />
<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
<div class="acp-box">
	<h3>{$this->lang->words['error_log_thelogs']}</h3>
	<table class='alternate_rows' width='100%'>
		<tr>
			<th width='10%'>{$this->lang->words['error_log_code']}</th>
			<th width='30%'>{$this->lang->words['error_log_error']}</th>
			<th width='15%'>{$this->lang->words['error_log_ip']}</th>
			<th width='20%'>{$this->lang->words['error_log_uri']}</th>
			<th width='10%'>{$this->lang->words['error_log_member']}</th>
			<th width='10%'>{$this->lang->words['error_log_date']}</th>
			<th width='3%'><input type='checkbox' title="{$this->lang->words['my_checkall']}" id='checkAll' /></th>
		</tr>
HTML;

if( count( $rows ) AND is_array( $rows ) )
{
	foreach( $rows as $row )
	{
		$IPBHTML .= <<<HTML
		<tr>
			<td>{$row['log_error_code']}</td>
			<td>{$row['log_error']}</td>
			<td>{$row['log_ip_address']}</td>
			<td>{$row['log_request_uri']}</td>
			<td>{$row['members_display_name']}</td>
			<td>{$row['_date']}</td>
			<td><input type='checkbox' name='id_{$row['log_id']}' value='1' class='checkAll' /></td>
		</tr>
HTML;
	}
}
else
{
	$IPBHTML .= <<<HTML
		<tr>
			<td colspan='7' align='center'>{$this->lang->words['error_log_noresults']}</td>
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
<br />

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm'  id='theAdminForm'>
	<input type='hidden' name='do' value='list' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	<div class='acp-box'>
		<h3>{$this->lang->words['error_log_search']}</h3>
		<p align="center">
			<label>{$this->lang->words['erlog_searchwhere']}</label>
			{$form['type']} {$form['match']} {$form['string']}
		</p>
		</ul>
		<div class='acp-actionbar'>
			<input value="{$this->lang->words['erlog_searchbutton']}" class="button primary" accesskey="s" type="submit" />
		</div>
	</div>
</form>

HTML;

//--endhtml--//
return $IPBHTML;
}

}