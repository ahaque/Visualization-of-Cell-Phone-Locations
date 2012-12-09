<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * ACP ban filters skin file
 * Last Updated: $Date: 2009-03-04 17:43:25 -0500 (Wed, 04 Mar 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Members
 * @link		http://www.
 * @since		20th February 2002
 * @version		$Rev: 4140 $
 *
 */
 
class cp_skin_banfilters extends output
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
 * Ban filter overview screen
 *
 * @access	public
 * @param	array 		IPs
 * @param	array 		Emails
 * @param	array 		Usernames
 * @return	string		HTML
 */
public function banOverview( $ips, $emails, $names ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['ban_title']}</h2>
</div>

<form method='post' action='{$this->settings['base_url']}{$this->form_code}&amp;do=ban_add'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->_admin_auth_key}' />
	<input type='text' size='30' class='input_text' value='' name='bantext' />
	<select class='input_select' name='bantype' style='vertical-align:middle;'>
		<option value='ip'>{$this->lang->words['ban_ip']}</option>
		<option value='email'>{$this->lang->words['ban_email']}</option>
		<option value='name'>{$this->lang->words['ban_name']}</option>						
	</select>
	<input type='submit' value='{$this->lang->words['ban_addnew']}' class='button primary' />						
</form>

<div class='acp-box' style='margin-top: 4px;'>
	<h3>{$this->lang->words['ban_bancontrol']}</h3>
	<form method='post' id='ban-delete' action='{$this->settings['base_url']}{$this->form_code}&amp;do=ban_delete'>
		<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->_admin_auth_key}' />
			<table width='100%' class='alternate_rows double_pad'>
				<tr>
					<th colspan='3'>{$this->lang->words['ban_ips']}</th>			
				</tr>
HTML;

if( is_array( $ips ) && count( $ips ) )
{
	foreach( $ips as $r )
	{
$IPBHTML .= <<<HTML
		<tr>
			<td width='1%'>{$r['_checkbox']}</td>
			<td width='80%'>{$r['ban_content']}</td>
			<td width='20%'>{$r['_date']}</td>
		</tr>
HTML;
	}
}
else
{
$IPBHTML .= <<<HTML
		<tr>
			<td colspan='3'>{$this->lang->words['ban_ips_none']}</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
		<tr>
			<th colspan='3'>{$this->lang->words['ban_emails']}</th>			
		</tr>
		
HTML;

if( is_array( $emails ) && count( $emails ) )
{
	foreach( $emails as $r )
	{
$IPBHTML .= <<<HTML
		<tr>
			<td width='1%'>{$r['_checkbox']}</td>
			<td width='80%'>{$r['ban_content']}</td>
			<td width='20%'>{$r['_date']}</td>
		</tr>
HTML;
	}
}
else
{
$IPBHTML .= <<<HTML
		<tr>
			<td colspan='3'>{$this->lang->words['ban_emails_none']}</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
		<tr>
			<th colspan='3'>{$this->lang->words['ban_names']}</th>			
		</tr>
HTML;

if( is_array( $names ) && count( $names ) )
{
	foreach( $names as $r )
	{
$IPBHTML .= <<<HTML
		<tr>
			<td width='1%'>{$r['_checkbox']}</td>
			<td width='80%'>{$r['ban_content']}</td>
			<td width='20%'>{$r['_date']}</td>
		</tr>
HTML;
	}
}
else
{
$IPBHTML .= <<<HTML
		<tr>
			<td colspan='3'>{$this->lang->words['ban_names_none']}</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
			</form>
		</tr>
	</table>
	<div class='acp-actionbar'>
		<input type='submit' value='{$this->lang->words['ban_deletebutton']}' class='realbutton redbutton' />
	</div>
</div>
<script type='text/javascript'>
document.observe("dom:loaded", function(){
	Event.observe( 
					'ban-delete', 
					'submit', 
					function( e ) 
					{ 
						var checkboxes = $('ban-delete').getElementsByTagName( 'input' );
						
						for( var i = 0; i < checkboxes.length; i++ )
						{
							if( checkboxes[i].checked )
							{
								return true;
							}
						}
						
						alert( '{$this->lang->words['no_ban_to_delete']}' );

						Event.stop( e );
					} 					
				);
});

</script>
HTML;

//--endhtml--//
return $IPBHTML;
}

}