<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * ACP tools skin file
 * Last Updated: $Date: 2009-08-28 12:09:31 -0400 (Fri, 28 Aug 2009) $
 *
 * @author 		$Author: matt $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Members
 * @link		http://www.
 * @since		20th February 2002
 * @version		$Rev: 5059 $
 *
 */
 
class cp_skin_tools extends output
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
 * Merge start form
 *
 * @access	public
 * @param	array 		Member data
 * @return	string		HTML
 */
public function mergeStart( $member ){
$IPBHTML = "";

$title	= sprintf( $this->lang->words['merge_users_title'], $member['members_display_name'] );
$desc	= sprintf( $this->lang->words['merge_explanation'], $member['members_display_name'], $member['members_display_name'] );

$IPBHTML .= <<<HTML
<div class='information-box'>
{$desc}
</div>
<br />
<form name='theAdminForm' id='adminform' action='{$this->settings['base_url']}&amp;module=members&amp;section=tools&amp;do=doMerge' method='post'>
	<input type='hidden' name='member_id' value='{$member['member_id']}' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->getClass('adminFunctions')->_admin_auth_key}' />
	
	<div class='acp-box'>
		<h3>{$title}</h3>
 
 		<ul class='acp-form alternate_rows'>
			<li>
				<label>{$this->lang->words['merge_find_name']}</label>
				<input type='text' class='textinput' id='members_display_name' name='members_display_name' value='' />
			</li>
		</ul>
		
		<div class='acp-actionbar'>
			<div class='centeraction'>
				 <input type='submit' class='button primary' value=' {$this->lang->words['merge_continue_button']} ' />
			</div>
		</div>
	</div>
</form>
<script type='text/javascript'>
document.observe("dom:loaded", function(){
	var autoComplete = new ipb.Autocomplete( $('members_display_name'), { multibox: false, url: acp.autocompleteUrl, templates: { wrap: acp.autocompleteWrap, item: acp.autocompleteItem } } );
});
</script>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Confirm merge of two members
 *
 * @access	public
 * @param	array 		Member data
 * @param	array 		Member data
 * @return	string		HTML
 */
public function mergeConfirm( $member, $member2 ){
$IPBHTML = "";

$desc	= sprintf( $this->lang->words['merge_confirmation'], $member2['members_display_name'], $member['members_display_name'], $member2['members_display_name'] );

$IPBHTML .= <<<HTML
<div class='information-box'>
{$desc}
</div>
<br />
<form name='theAdminForm' id='adminform' action='{$this->settings['base_url']}&amp;module=members&amp;section=tools&amp;do=doMerge' method='post'>
	<input type='hidden' name='member_id' value='{$member['member_id']}' />
	<input type='hidden' name='member_id2' value='{$member2['member_id']}' />
	<input type='hidden' name='confirm' value='1' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->getClass('adminFunctions')->_admin_auth_key}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['merge_confirm_title']}</h3>
 
 		<ul class='acp-form alternate_rows'>
			<li>
				<label>{$this->lang->words['merge_remove']}</label>
				<a href='{$this->settings['_original_base_url']}/index.php?showuser={$member2['member_id']}'>{$member2['members_display_name']}</a>
			</li>
			<li>
				<label>{$this->lang->words['merge_keep']}</label>
				<a href='{$this->settings['_original_base_url']}/index.php?showuser={$member['member_id']}'>{$member['members_display_name']}</a>
			</li>
		</ul>
		
		<div class='acp-actionbar'>
			<div class='centeraction'>
				 <input type='submit' class='button primary' value=' {$this->lang->words['merge_submit']} ' />
			</div>
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Member tools splash page
 *
 * @access	public
 * @param	string		Message
 * @param	array 		Form elements
 * @return	string		HTML
 */
public function toolsIndex( $message, $form=array() ) {

$IPBHTML = "";
//--starthtml--//

if( $message )
{
	$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$this->lang->words['t_message']}</h3>
	<p>{$message}</p>
</div>
<br />
HTML;
}

$form_ip	= ipsRegistry::getClass('output')->formInput( "ip", isset($_POST['ip']) ? IPSText::stripslashes($_POST['ip']) : '' );

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['t_iptoolstitle']}</h2>
</div>
<form action='{$this->settings['base_url']}&amp;module=members&amp;section=tools&amp;do=show_all_ips' method='post' name='theAdminForm' id='theAdminForm'>
	<div class='acp-box'>
		<h3>{$this->lang->words['t_showallip']}</h3>
		
		<ul class='acp-form alternate_rows'>
			<li>
				<label>{$form['text']}</label>
				{$form['form']}
			</li>
		</ul>
		
		<div class='acp-actionbar'>
				<input type='submit' class='button primary' value=' {$this->lang->words['t_showallbutton']} ' />
		</div>
	</div>
</form>
<br />

<form action='{$this->settings['base_url']}&amp;module=members&amp;section=tools&amp;do=learn_ip' method='post'>
	<div class='acp-box'>
		<h3>{$this->lang->words['t_ipmulti']}</h3>
		
		<ul class='acp-form alternate_rows'>
			<li>
	 			<label>{$this->lang->words['t_showme']}</label>
				{$form_ip}
			</li>
		</ul>
		
		<div class='acp-actionbar'>
				<input type='submit' class='button primary' value=' {$this->lang->words['t_showmebutton']} ' />
		</div>
	</div>
</form>

<script type='text/javascript'>
document.observe("dom:loaded", function(){
	var search = new ipb.Autocomplete( $('name'), { multibox: false, url: acp.autocompleteUrl, templates: { wrap: acp.autocompleteWrap, item: acp.autocompleteItem } } );
});
</script>
HTML;

//--endhtml--//
return $IPBHTML;
}


/**
 * Delete PM wrapper
 *
 * @access	public
 * @param	array		Member
 * @param	int 		Total topics
 * @param	int			Total replies
 * @return	string		HTML
 */
public function deleteMessagesWrapper( $member, $countTopics, $countReplies )
{
$IPBHTML = "";
//--starthtml--//

$countTopics['total'] = intval($countTopics['total']);
$countReplies['total'] = intval($countReplies['total']);

$this->lang->words['total_pms_topics']	= sprintf( $this->lang->words['total_pms_topics'], $countTopics['total'] );
$this->lang->words['total_pms_replies']	= sprintf( $this->lang->words['total_pms_replies'], $countReplies['total'] );

$topicsYesNo	= $this->registry->output->formYesNo( 'topics', 0 );
$repliesYesNo	= $this->registry->output->formYesNo( 'replies', 0 );

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>Member Management</h2>
</div>

<form action='{$this->settings['base_url']}&amp;module=members&amp;section=tools&amp;do=deleteMessages&amp;member_id={$member['member_id']}&amp;confirm=1' method='post'>
	<div class='acp-box'>
		<h3>Deleting all private messages sent by {$member['members_display_name']}</h3>
		<table class='form_table alternate_rows double_pad'>
			<tr>
				<td style='width: 40%'>
					<strong>{$this->lang->words['total_pms_topics']}</strong>
				</td>
				<td style='width: 60%'>
					{$topicsYesNo}
				</td>
			</tr>
			<tr>
				<td>
					<strong>{$this->lang->words['total_pms_replies']}</strong>
				</td>
				<td>
					{$repliesYesNo}
				</td>
			</tr>
		</table>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['confirm_pm_button']}' class='realbutton' />
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show all of a member's IP addresses
 *
 * @access	public
 * @param	array 		Member data
 * @param	array 		All IPs
 * @param	string		Page links
 * @param	array 		Member's registering with IP
 * @return	string		HTML
 */
public function showAllIPs( $member, $allips, $links, $reg=array() ) {

$IPBHTML = "";
//--starthtml--//

$count = count($allips);
$counttxt = sprintf( $this->lang->words['t_counttxt'], $member['members_display_name'], $count );
$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$counttxt}</h3>
	
	<table class='alternate_rows' width='100%'>
		<tr>
			<th width='20%'>{$this->lang->words['t_ipaddy']}</th>
			<th width='10%'>{$this->lang->words['t_timesused']}</th>
			<th width='25%'>{$this->lang->words['t_lastused']}</th>
			<th width='20%'>{$this->lang->words['t_usedotherreg']}</th>
			<th width='25%'>{$this->lang->words['t_iptool']}</th>
	 	</tr>
HTML;

if( is_array($allips) AND count($allips) )
{
	foreach( $allips as $ip_address => $use_info )
	{
		$date = $use_info[1] ? ipsRegistry::getClass( 'class_localization')->getDate( $use_info[1], 'SHORT' ) : 'No Info';
		$others	= intval( count( $reg[ $ip_address ] ) );

		$IPBHTML .= <<<HTML
		<tr>
			<td><strong>{$ip_address}</strong></td>
			<td>{$use_info[0]}</td>
			<td>{$date}</td>
			<td>{$others}</td>
			<td><a href='{$this->settings['base_url']}&amp;module=members&amp;section=tools&amp;do=learn_ip&amp;ip={$ip_address}'>{$this->lang->words['t_learnmore']}</a></td>
		</tr>
HTML;
	}
}
else
{
	$IPBHTML .= <<<HTML
		<tr>
			<th colspan='5' align='center'>{$this->lang->words['t_noips']}</th>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
	<div class='acp-actionbar'>
		<div class='rightaction'>
			{$links}
		</div>
	</div>
</div>
<br />

HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Learn about an IP
 *
 * @access	public
 * @param	string		Host address
 * @param	array 		All registering members
 * @param	array 		All posting members
 * @param	array 		All voting members
 * @param	array 		All emailing members
 * @param	array 		All validating members
 * @param	array 		All other instances of IP
 * @return	string		HTML
 */
public function learnIPResults( $hostAddr, $registered=array(), $posted=array(), $voted=array(), $emailed=array(), $validating=array(), $results=array() ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$this->lang->words['hostaddress_for']} {$this->request['ip']}</h3>
 	
	<table class='alternate_rows' width='100%'>
		<tr>
			<td width='40%'>{$this->lang->words['t_ipresolves']}</td>
			<td width='60%'>{$hostAddr}</td>
		</tr>
	</table>
</div>
<br />

<div class='acp-box'>
	<h3>{$this->lang->words['t_ipreg']}</h3>
	<table class='alternate_rows' width='100%'>
	<tr>
		<th width='30%'>{$this->lang->words['t_ipname']}</th>
		<th width='20%'>{$this->lang->words['t_ipemail']}</th>
		<th width='10%'>{$this->lang->words['t_ipposts']}</th>
		<th width='20%'>{$this->lang->words['t_ipip']}</th>
		<th width='20%'>{$this->lang->words['t_ipregistered']}</th>
	 </tr>
HTML;

if( is_array($registered) AND count($registered) )
{
	foreach( $registered as $member )
	{
		$IPBHTML .= <<<HTML
	 <tr>
		<td><strong>{$member['members_display_name']}</strong></td>
		<td>{$member['email']}</td>
		<td>{$member['posts']}</td>
		<td>{$member['ip_address']}</td>
		<td>{$member['_joined']}</td>
	 </tr>
HTML;
	}
}
else
{
	$IPBHTML .= <<<HTML
	 <tr>
		<td colspan='5' align='center'>{$this->lang->words['t_nomatches']}</td>
	 </tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
</div>
<br />

<div class='acp-box'>
	<h3>{$this->lang->words['t_ipposting']}</h3>
	<table class='alternate_rows' width='100%'>
		<tr>
			<th width='30%'>{$this->lang->words['t_ipname']}</th>
			<th width='20%'>{$this->lang->words['t_ipemail']}</th>
			<th width='20%'>{$this->lang->words['t_ipip']}</th>
			<th width='20%'>{$this->lang->words['t_ipposted']}</th>
			<th width='10%'>{$this->lang->words['t_ipview']}</th>
		</tr>
HTML;

if( is_array($posted) AND count($posted) )
{
	foreach( $posted as $member )
	{
		$IPBHTML .= <<<HTML
		<tr>
			<td><strong>{$member['members_display_name']}</strong></td>
			<td>{$member['email']}</td>
			<td>{$member['ip_address']}</td>
			<td>{$member['_post_date']}</td>
			<td align='center'><a href='{$this->settings['board_url']}/index.php?app=forums&amp;module=forums&amp;section=findpost&amp;pid={$member['pid']}' target='_blank'>{$this->lang->words['t_ipviewpost']}</a></td>
		</tr>
HTML;
	}
}
else
{
	$IPBHTML .= <<<HTML
	 <tr>
		<td colspan='5' align='center'>{$this->lang->words['t_nomatches']}</td>
	 </tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
</div>
<br />

<div class='acp-box'>
	<h3>{$this->lang->words['t_ipvoting']}</h3>
	<table class='alternate_rows' width='100%'>
		<tr>
			<th width='30%'>{$this->lang->words['t_ipname']}</th>
			<th width='20%'>{$this->lang->words['t_ipemail']}</th>
			<th width='20%'>{$this->lang->words['t_ipip']}</th>
			<th width='20%'>{$this->lang->words['t_ipfirstused']}</th>
			<th width='10%'>{$this->lang->words['t_ipview']}</th>
		</tr>
HTML;

if( is_array($voted) AND count($voted) )
{
	foreach( $voted as $member )
	{
		$IPBHTML .= <<<HTML
		<tr>
			<td><strong>{$member['members_display_name']}</strong></td>
			<td>{$member['email']}</td>
			<td>{$member['ip_address']}</td>
			<td>{$member['_vote_date']}</td>
			<td align='center'><a href='{$this->settings['board_url']}/index.php?showtopic={$member['tid']}' target='_blank'>{$this->lang->words['t_ipviewpoll']}</a></td>
		</tr>
HTML;
	}
}
else
{
	$IPBHTML .= <<<HTML
	 <tr>
		<td colspan='5' align='center'>{$this->lang->words['t_nomatches']}</td>
	 </tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
</div>
<br />

<div class='acp-box'>
	<h3>{$this->lang->words['t_ipemailing']}</h3>
	
	<table class='alternate_rows' width='100%'>
		<tr>
			<th width='30%'>{$this->lang->words['t_ipname']}</th>
			<th width='30%'>{$this->lang->words['t_ipemail']}</th>
			<th width='20%'>{$this->lang->words['t_ipip']}</th>
			<th width='20%'>{$this->lang->words['t_ipfirstused']}</th>
		</tr>
HTML;

if( is_array($emailed) AND count($emailed) )
{
	foreach( $emailed as $member )
	{
		$IPBHTML .= <<<HTML
		<tr>
			<td><strong>{$member['members_display_name']}</strong></td>
			<td>{$member['email']}</td>
			<td>{$member['from_ip_address']}</td>
			<td>{$member['_email_date']}</td>
		</tr>
HTML;
	}
}
else
{
	$IPBHTML .= <<<HTML
	 <tr>
		<td colspan='5' align='center'>{$this->lang->words['t_nomatches']}</td>
	 </tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
</div>
<br />

<div class='acp-box'>
	<h3>{$this->lang->words['t_ipvalidating']}</h3>
	<table class='alternate_rows' width='100%'>
		<tr>
			<th width='30%'>{$this->lang->words['t_ipname']}</th>
			<th width='30%'>{$this->lang->words['t_ipemail']}</th>
			<th width='20%'>{$this->lang->words['t_ipip']}</th>
			<th width='20%'>{$this->lang->words['t_ipfirstused']}</th>
		</tr>
HTML;

if( is_array($validating) AND count($validating) )
{
	foreach( $validating as $member )
	{
		$IPBHTML .= <<<HTML
		<tr>
			<td><strong>{$member['members_display_name']}</strong></td>
			<td>{$member['email']}</td>
			<td>{$member['ip_address']}</td>
			<td>{$member['_entry_date']}</td>
		</tr>
HTML;
	}
}
else
{
	$IPBHTML .= <<<HTML
	 <tr>
		<td colspan='5' align='center'>{$this->lang->words['t_nomatches']}</td>
	 </tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
</div>
<br />

<div class='acp-box'>
	<h3>{$this->lang->words['t_ipvalidating']}</h3>
	<table class='alternate_rows' width='100%'>
		<tr>
			<th width='30%'>{$this->lang->words['t_ipname']}</th>
			<th width='30%'>{$this->lang->words['t_ip_table']}</th>
			<th width='20%'>{$this->lang->words['t_ipip']}</th>
			<th width='20%'>{$this->lang->words['t_ipfirstused']}</th>
		</tr>
HTML;

if( is_array($results) AND count($results) )
{
	foreach( $results as $table => $result )
	{
		if( count($result) )
		{
			foreach( $result as $member )
			{
				$date	 	= $member['date'] ? ipsRegistry::getClass( 'class_localization')->getDate( $member['date'], 'SHORT' ) : '';
				$member['members_display_name']	= $member['members_display_name'] ? $member['members_display_name'] : $this->lang->words['t_guest'];
				
				$IPBHTML .= <<<HTML
				<tr>
					<td><strong>{$member['members_display_name']}</strong></td>
					<td>{$table}</td>
					<td>{$member['ip_address']}</td>
					<td>{$date}</td>
				</tr>
HTML;
			}
		}
	}
}
else
{
	$IPBHTML .= <<<HTML
	 <tr>
		<td colspan='5' align='center'>{$this->lang->words['t_nomatches']}</td>
	 </tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
</div>
<br />
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Validation queue wrapper
 *
 * @access	public
 * @param	string		Type of queue
 * @param	string		Queue HTML
 * @param	int			Page start
 * @param	string		New order
 * @param	string		Page links
 * @return	string		HTML
 */
public function queueWrapper( $type, $content, $st, $new_ord, $links ) {

$IPBHTML = "";
//--starthtml--//

$table_text			= ucwords($type);
$this->form_code	= 'module=members&amp;section=tools';
$memberq			= sprintf( $this->lang->words['t_memberq'], $table_text );
$_do 				= $this->request['do'];

if( $_do == 'do_validating' )
{
	$_do = 'validating';
}
else if( $_do == 'do_locked' )
{
	$_do = 'locked';
}
else if( $_do == 'do_banned' )
{
	$_do = 'banned';
}
else if( $_do == 'do_spam' )
{
	$_do = 'spam';
}

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$memberq}</h2>
</div>
		
<script type="text/javascript" src='{$this->settings['js_app_url']}ipsMemberQueue.js'></script>
HTML;

	if ( $_do == 'validating' )
	{
$IPBHTML .= <<<HTML
<form name='selectform' id='selectform' action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do={$_do}' method='post'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->getClass('adminFunctions')->_admin_auth_key}' />
	<select name='filter'>
		<option value='all'>{$this->lang->words['t_qshowall']}</option>
		<option value='reg_user_validate'>{$this->lang->words['t_qshowreg']}</option>
		<option value='reg_admin_validate'>{$this->lang->words['t_qshowadmin']}</option>
		<option value='email_chg'>{$this->lang->words['t_qemailchange']}</option>
		<option value='coppa'>{$this->lang->words['t_qcoppa']}</option>
	</select>
	<input type='submit' class='realbutton' value=' {$this->lang->words['t_qgobutton']} ' />
</form>
HTML;
	}

$IPBHTML .= <<<HTML
<form name='theAdminForm' id='adminform' action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=do_{$type}' method='post'>
<input type='hidden' name='_admin_auth_key' value='{$this->registry->getClass('adminFunctions')->_admin_auth_key}' />
{$links}
<br style='clear: both' />

<div class='acp-box'>
	<h3>{$memberq}</h3>
	<table class='alternate_rows double_pad' cellspacing='0' cellpadding='0'>
		<tr>
			<th>
				{$this->lang->words['t_qdisplayname']}
				<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do={$type}&amp;st={$st}&amp;sort=mem&amp;ord=desc' title='{$this->lang->words['sort_desc']}'><img src='{$this->settings['skin_acp_url']}/_newimages/sort_desc.png' /></a>
				<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do={$type}&amp;st={$st}&amp;sort=mem&amp;ord=asc' title='{$this->lang->words['sort_asc']}'><img src='{$this->settings['skin_acp_url']}/_newimages/sort_asc.png' /></a>
			</th>
			<th>
				{$this->lang->words['t_qaddress']}
				<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do={$type}&amp;st={$st}&amp;sort=email&amp;ord=desc' title='{$this->lang->words['sort_desc']}'><img src='{$this->settings['skin_acp_url']}/_newimages/sort_desc.png' /></a>
				<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do={$type}&amp;st={$st}&amp;sort=email&amp;ord=asc' title='{$this->lang->words['sort_asc']}'><img src='{$this->settings['skin_acp_url']}/_newimages/sort_asc.png' /></a>
			</th>
			<th>
				{$this->lang->words['t_qposts']}
				<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do={$type}&amp;st={$st}&amp;sort=posts&amp;ord=desc' title='{$this->lang->words['sort_desc']}'><img src='{$this->settings['skin_acp_url']}/_newimages/sort_desc.png' /></a>
				<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do={$type}&amp;st={$st}&amp;sort=posts&amp;ord=asc' title='{$this->lang->words['sort_asc']}'><img src='{$this->settings['skin_acp_url']}/_newimages/sort_asc.png' /></a>			
			</th>
			<th>
HTML;
	switch( $type )
	{
		case 'validating':
			$IPBHTML .= <<<HTML
				{$this->lang->words['t_qsent']}
				<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do={$type}&amp;st={$st}&amp;sort=sent&amp;ord=desc' title='{$this->lang->words['sort_desc']}'><img src='{$this->settings['skin_acp_url']}/_newimages/sort_desc.png' /></a>
				<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do={$type}&amp;st={$st}&amp;sort=sent&amp;ord=asc' title='{$this->lang->words['sort_asc']}'><img src='{$this->settings['skin_acp_url']}/_newimages/sort_asc.png' /></a>
HTML;
		break;

		case 'locked':
			$IPBHTML .= <<<HTML
				{$this->lang->words['t_qfailed']}
				<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do={$type}&amp;st={$st}&amp;sort=failed&amp;ord=desc' title='{$this->lang->words['sort_desc']}'><img src='{$this->settings['skin_acp_url']}/_newimages/sort_desc.png' /></a>
				<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do={$type}&amp;st={$st}&amp;sort=failed&amp;ord=asc' title='{$this->lang->words['sort_asc']}'><img src='{$this->settings['skin_acp_url']}/_newimages/sort_asc.png' /></a>
HTML;

		case 'banned':
			$IPBHTML .= <<<HTML
				{$this->lang->words['t_qgroup']}
				<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do={$type}&amp;st={$st}&amp;sort=group&amp;ord=desc' title='{$this->lang->words['sort_desc']}'><img src='{$this->settings['skin_acp_url']}/_newimages/sort_desc.png' /></a>
				<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do={$type}&amp;st={$st}&amp;sort=group&amp;ord=asc' title='{$this->lang->words['sort_asc']}'><img src='{$this->settings['skin_acp_url']}/_newimages/sort_asc.png' /></a>
HTML;
		break;
		case 'spam':
			$IPBHTML .= <<<HTML
				{$this->lang->words['t_qgroup']}
				<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do={$type}&amp;st={$st}&amp;sort=group&amp;ord=desc' title='{$this->lang->words['sort_desc']}'><img src='{$this->settings['skin_acp_url']}/_newimages/sort_desc.png' /></a>
				<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do={$type}&amp;st={$st}&amp;sort=group&amp;ord=asc' title='{$this->lang->words['sort_asc']}'><img src='{$this->settings['skin_acp_url']}/_newimages/sort_asc.png' /></a>
HTML;
		break;
		}
		
		$IPBHTML .= <<<HTML
			</th>
			<th>
				{$this->lang->words['t_qjoined']}
				<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do={$type}&amp;st={$st}&amp;sort=joined&amp;ord=desc' title='{$this->lang->words['sort_desc']}'><img src='{$this->settings['skin_acp_url']}/_newimages/sort_desc.png' /></a>
				<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do={$type}&amp;st={$st}&amp;sort=joined&amp;ord=asc' title='{$this->lang->words['sort_asc']}'><img src='{$this->settings['skin_acp_url']}/_newimages/sort_asc.png' /></a>
			</th>
			<th style='text-align: center'><input type='checkbox' id='maincheckbox' onclick='check_boxes()' /></th>
		</tr>
		{$content}
	</table>
	<div class='acp-actionbar' style='text-align: right'>
		<select name='type' id='manage_type' class='dropdown'>
HTML;

switch( $type )
{
	case 'validating':
	$IPBHTML .= <<<HTML
			<option value='approve'>{$this->lang->words['t_qapprove']}</option>
			<option value='ban'>{$this->lang->words['t_qban']}</option>
			<option value='spam'>{$this->lang->words['ts_spam_mark']}</option>
			<option value='delete'>{$this->lang->words['t_qdelete']}</option>
			<option value='resend'>{$this->lang->words['t_qresend']}</option>
HTML;
break;

	case 'locked':
	$IPBHTML .= <<<HTML
			<option value='approve'>{$this->lang->words['t_qban']}</option>
			<option value='delete'>{$this->lang->words['t_qdelete']}</option>
			<option value='unlock'>{$this->lang->words['t_qunlock']}</option>
HTML;
break;

	case 'banned':
	$IPBHTML .= <<<HTML
			<option value='unban'>{$this->lang->words['t_qunban']}</option>
			<option value='delete'>{$this->lang->words['t_qdelete']}</option>
HTML;
break;

	case 'spam':
	$IPBHTML .= <<<HTML
			<option value='unspam'>{$this->lang->words['ts_spam_un']}</option>
			<option value='unspam_posts'>{$this->lang->words['ts_spam_un_posts']}</option>
			<option value='ban'>{$this->lang->words['ts_spam_ban']}</option>
			<option value='ban_blacklist'>{$this->lang->words['ts_spam_blacklist']}</option>
HTML;
break;
}

	$IPBHTML .= <<<HTML
		</select>
		<input type='submit' class='realbutton' value=' {$this->lang->words['t_processbutton']} ' />
	</div>
</div>
{$links}
</form>

HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * No rows in queue
 *
 * @access	public
 * @param	string		Language bit
 * @return	string		HTML
 */
public function queueNoRows( $lang ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<tr>
  <td class='tablerow2' colspan='7'><strong>{$lang}</strong></td>
</tr>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Validating row
 *
 * @access	public
 * @param	array 		Record
 * @return	string		HTML
 */
public function validatingRow( $r="" ) {
$IPBHTML = "";
//--starthtml--//
$daysandhours = sprintf( $this->lang->words['t_daysandhours'], $r['_days'], $r['_rhours'] );
$IPBHTML .= <<<HTML
<tr>
  <td class='tablerow2'><a href='{$this->settings['base_url']}&amp;module=members&amp;section=members&amp;do=viewmember&amp;member_id={$r['member_id']}'><strong>{$r['members_display_name']}</strong></a>{$r['_coppa']}<div class='desctext'>{$this->lang->words['t_ipcolon']} <a href='{$this->settings['base_url']}&amp;module=members&amp;section=tools&amp;do=learn_ip&amp;ip={$r['ip_address']}'>{$r['ip_address']}</a></div></td>
  <td class='tablerow1'>{$r['email']}</td>
  <td class='tablerow1' align='center'>{$r['posts']}</td>
  <td class='tablerow1'><span style='color:green'>{$r['_where']}</span><br />{$r['_entry']}<div class='desctext'>{$daysandhours}</div></td>
  <td class='tablerow1'>{$r['_joined']}</td>																
  <td class='tablerow1' align='center'><input type='checkbox' id="mid_{$r['member_id']}" name='mid_{$r['member_id']}' value='1' /></td>
</tr>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Locked row
 *
 * @access	public
 * @param	array 		Record
 * @return	string		HTML
 */
public function lockedRow( $r="" ) {

$failuresatlife = sprintf( $this->lang->words['t_failures'], $r['oldest_fail'], $r['newest_fail'], $r['failed_login_count'] );

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<tr>
  <td class='tablerow2'><a href='{$this->settings['base_url']}&amp;module=members&amp;section=members&amp;do=viewmember&amp;member_id={$r['member_id']}'><strong>{$r['members_display_name']}</strong></a><div class='desctext'>{$this->lang->words['t_groupcolon']} {$r['group_title']}</div><div class='desctext'>{$r['ip_addresses']}</div></td>
  <td class='tablerow1'>{$r['email']}</td>
  <td class='tablerow1' align='center'>{$r['posts']}</td>
  <td class='tablerow1'>{$failuresatlife}</td>
  <td class='tablerow1'>{$r['_joined']}</td>																
  <td class='tablerow1' align='center'><input type='checkbox' id="mid_{$r['member_id']}" name='mid_{$r['member_id']}' value='1' /></td>
</tr>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Banned row
 *
 * @access	public
 * @param	array 		Record
 * @return	string		HTML
 */
public function bannedRow( $r="" ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<tr>
  <td class='tablerow2'><a href='{$this->settings['base_url']}&amp;module=members&amp;section=members&amp;do=viewmember&amp;member_id={$r['member_id']}'><strong>{$r['members_display_name']}</strong></a></td>
  <td class='tablerow1'>{$r['email']}</td>
  <td class='tablerow1' align='center'>{$r['posts']}</td>
  <td class='tablerow1'>{$r['group_title']}</td>
  <td class='tablerow1'>{$r['_joined']}</td>																
  <td class='tablerow1' align='center'><input type='checkbox' id="mid_{$r['member_id']}" name='mid_{$r['member_id']}' value='1' /></td>
</tr>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Banned row
 *
 * @access	public
 * @param	array 		Record
 * @return	string		HTML
 */
public function spamRow( $r="" ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<tr>
  <td class='tablerow2'><a href='{$this->settings['base_url']}&amp;module=members&amp;section=members&amp;do=viewmember&amp;member_id={$r['member_id']}'><strong>{$r['members_display_name']}</strong></a></td>
  <td class='tablerow1'>{$r['email']}</td>
  <td class='tablerow1' align='center'>{$r['posts']}</td>
  <td class='tablerow1'>{$r['group_title']}</td>
  <td class='tablerow1'>{$r['_joined']}</td>																
  <td class='tablerow1' align='center'><input type='checkbox' id="mid_{$r['member_id']}" name='mid_{$r['member_id']}' value='1' /></td>
</tr>
HTML;

//--endhtml--//
return $IPBHTML;
}


}