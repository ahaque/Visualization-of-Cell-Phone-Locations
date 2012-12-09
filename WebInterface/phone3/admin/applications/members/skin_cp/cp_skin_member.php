<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * ACP members skin file
 * Last Updated: $Date: 2009-08-10 17:08:53 -0400 (Mon, 10 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Members
 * @link		http://www.
 * @since		20th February 2002
 * @version		$Rev: 5009 $
 *
 */
 
class cp_skin_member extends output
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
 * Member reputation log
 *
 * @access	public
 * @param	string		Title
 * @param	array 		Rows
 * @param	string		Page links
 * @return	string		HTML
 */
public function memberRepLog( $title, $rows, $pages ) {
$IPBHTML = "";
//--starthtml--//

$lang_str = $this->request['type'] == 'given' ? 'rep_for' : 'rep_by';

$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$title}</h3>
		
	<table class='alternate_rows' width='100%'>
		<tr>
			<th>{$this->lang->words['rep_rep']}</th>
			<th>{$this->lang->words[$lang_str]}</th>
			<th>{$this->lang->words['rep_on']}</th>
			<th>{$this->lang->words['rep_post']}</th>
		</tr>
HTML;

foreach( $rows as $r )
{
$IPBHTML .= <<<HTML
		<tr>
			<td>{$r['rep_rating']}</td>
			<td>{$r['members_display_name']}</td>
			<td>{$r['_date']}</td>
			<td><a href='{$this->settings['board_url']}/index.php?showtopic={$r['tid']}&view=findpost&p={$r['type_id']}' target='_blank'>{$r['title']}</a></td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
	</table>
	
	<div class='acp-actionbar'>
		<div class='rightaction'>
			{$pages}
		</div>
	</div>		
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Member editing screen
 *
 * @access	public
 * @param	array 		Member data
 * @param	array 		Content
 * @param	string		Menu
 * @return	string		HTML
 */
public function member_view( $member, $content=array(), $menu ) {

// Let's get to work..... :/
$_m_groups = array();
$_m_groups_others = array();

foreach( ipsRegistry::cache()->getCache('group_cache') as $id => $data )
{
	// If we are viewing our own profile, don't show non admin groups as a primary group option to prevent the user from
	// accidentally removing their own ACP access.  Groups without ACP access can still be selected as secondary groups.
	if ( $member['member_id'] == $this->memberData['member_id'] )
	{
		//-----------------------------------------
		// If we can't access cp via primary group
		//-----------------------------------------
		
		if( !$this->caches['group_cache'][ $member['member_group_id'] ]['g_access_cp'] )
		{
			//-----------------------------------------
			// Can this group access cp?
			//-----------------------------------------
			
			if ( ! $data['g_access_cp'] )
			{
				$_m_groups[] = array( $data['g_id'], $data['g_title'] );
				$_m_groups_others[] = array( $data['g_id'], $data['g_title'] );
			}
			
			//-----------------------------------------
			// It can?
			//-----------------------------------------
			
			else
			{
				$_m_groups_others[] = array( $data['g_id'], $data['g_title'] );
			}	
		}
		
		//-----------------------------------------
		// We can access acp, so whatever
		//-----------------------------------------
		
		else
		{
			if ( ! $data['g_access_cp'] )
			{
				$_m_groups_others[] = array( $data['g_id'], $data['g_title'] );
			}
			else
			{
				$_m_groups[] = array( $data['g_id'], $data['g_title'] );
				$_m_groups_others[] = array( $data['g_id'], $data['g_title'] );
			}	
		}
	}
	else
	{
		$_m_groups[] = array( $data['g_id'], $data['g_title'] );
		$_m_groups_others[] = array( $data['g_id'], $data['g_title'] );
	}
}

$years		= array( array( 0, '----' ) );
$months		= array( array( 0, '--------' ) );
$days		= array( array( 0, '--' ) );

foreach( range( 1, 31 ) as $_day )
{
	$days[] = array( $_day, $_day );
}

foreach( array_reverse( range( date( 'Y' ) - 100, date('Y') ) ) as $_year )
{
	$years[] = array( $_year, $_year );
}

foreach( range( 1, 12 ) as $_month )
{
	$months[] = array( $_month, $this->lang->words['M_' . $_month ] );
}

$time_zones = array();

foreach( $this->lang->words as $k => $v )
{
	if( strpos( $k, 'time_' ) === 0 )
	{
		if( preg_match( "/[\-0-9]/", substr( $k, 5 ) ) )
		{
			$offset = floatval( substr( $k, 5 ) );

			$time_zones[] = array( $offset, $v );
		}
	}
}

$languages	= array();

foreach( ipsRegistry::cache()->getCache( 'lang_data' ) as $language )
{
	$languages[] = array( $language['lang_id'], $language['lang_title'] );
}

$_skin_list					= $this->registry->output->generateSkinDropdown();
array_unshift( $_skin_list, array( 0, $this->lang->words['sm_skinnone'] ) );

$skinList					= ipsRegistry::getClass('output')->formDropdown( "skin", $_skin_list, $member['skin'] );

$form_member_group_id		= ipsRegistry::getClass('output')->formDropdown( "member_group_id", $_m_groups, $member['member_group_id'] );
$form_mgroup_others			= ipsRegistry::getClass('output')->formMultiDropdown( "mgroup_others[]", $_m_groups_others, explode( ",", $member['mgroup_others'] ), 8, 'mgroup_others' );
$form_title					= ipsRegistry::getClass('output')->formInput( "title", $member['title'] );
$form_warn					= ipsRegistry::getClass('output')->formInput( "warn_level", $member['warn_level'] );
$_form_year					= ipsRegistry::getClass('output')->formDropdown( "bday_year", $years, $member['bday_year'] );
$_form_month				= ipsRegistry::getClass('output')->formDropdown( "bday_month", $months, $member['bday_month'] );
$_form_day					= ipsRegistry::getClass('output')->formDropdown( "bday_day", $days, $member['bday_day'] );
$form_time_offset			= ipsRegistry::getClass('output')->formDropdown( "time_offset", $time_zones, $member['time_offset'] ? floatval( $member['time_offset'] ) : 0 );
$form_language				= ipsRegistry::getClass('output')->formDropdown( "language", $languages, $member['language'] );
$form_hide_email			= ipsRegistry::getClass('output')->formYesNo( "hide_email", $member['hide_email'] );
$form_allow_admin_mails		= ipsRegistry::getClass('output')->formYesNo( "allow_admin_mails", $member['allow_admin_mails'] );
$form_email_pm				= ipsRegistry::getClass('output')->formYesNo( "email_pm", $member['email_pm'] );
$form_members_disable_pm	= ipsRegistry::getClass('output')->formYesNo( "members_disable_pm", $member['members_disable_pm'] );
$form_view_sig				= ipsRegistry::getClass('output')->formYesNo( "view_sigs", $member['view_sigs'] );
$form_view_pop				= ipsRegistry::getClass('output')->formYesNo( "view_pop", $member['view_pop'] );
$form_pp_bio_content		= ipsRegistry::getClass('output')->formTextarea( "pp_bio_content", $member['pp_bio_content'] );
$form_identity_url			= ipsRegistry::getClass('output')->formInput( "identity_url", $member['identity_url'] );
$form_reputation_points     = ipsRegistry::getClass('output')->formInput( 'pp_reputation_points', $member['pp_reputation_points'] );
$pp_status					= ipsRegistry::getClass('output')->formInput( 'pp_status', $member['pp_status'] );

$secure_key = ipsRegistry::getClass('adminFunctions')->getSecurityKey();

$ban_member_css		= $member['member_banned'] ? "background-color: #FF0033;font-weight:bold;color:#000;" : '';
$ban_member_text	= $member['member_banned'] ? $this->lang->words['sm_unban'] : $this->lang->words['sm_ban'];

$spam_member_css	= $member['bw_is_spammer'] ? "background-color: #FF0033;font-weight:bold;color:#000;" : '';
$spam_member_text	= $member['bw_is_spammer'] ? $this->lang->words['sm_unspam'] : $this->lang->words['sm_spam'];

//-----------------------------------------
// Comments and friends..
//-----------------------------------------
$pp_visitors					= ipsRegistry::getClass('output')->formDropdown( "pp_setting_count_visitors", array( array( 0, 0 ), array( 3, 3 ), array( 5, 5 ), array( 10, 10 ) ), $member['pp_setting_count_visitors'] );
$pp_enable_comments				= ipsRegistry::getClass('output')->formYesNo( "pp_setting_count_comments", $member['pp_setting_count_comments'] );
$pp_enable_friends				= ipsRegistry::getClass('output')->formYesNo( "pp_setting_count_friends", $member['pp_setting_count_friends'] );

$_commentsNotify	= array(
						array( 'none', $this->lang->words['sm_comment_notify_no'] ),
						array( 'email', $this->lang->words['sm_comment_notify_email'] ),
						array( 'pm', $this->lang->words['sm_comment_notify_pm'] ),
						);
						
$_friendsNotify		= array(
						array( 'none', $this->lang->words['sm_friends_notify_no'] ),
						array( 'email', $this->lang->words['sm_friends_notify_email'] ),
						array( 'pm', $this->lang->words['sm_friends_notify_pm'] ),
						);

$_commentsApprove	= array(
						array( '0', $this->lang->words['sm_comments_app_none'] ),
						array( '1', $this->lang->words['sm_comments_app_on'] ),
						);
						
$_friendsApprove	= array(
						array( '0', $this->lang->words['sm_friends_app_none'] ),
						array( '1', $this->lang->words['sm_friends_app_on'] ),
						);

$pp_comments_notify				= ipsRegistry::getClass('output')->formDropdown( "pp_setting_notify_comments", $_commentsNotify, $member['pp_setting_notify_comments'] );
$pp_comments_approve			= ipsRegistry::getClass('output')->formDropdown( "pp_setting_moderate_comments", $_commentsApprove, $member['pp_setting_moderate_comments'] );
$pp_friends_notify				= ipsRegistry::getClass('output')->formDropdown( "pp_setting_notify_friend", $_friendsNotify, $member['pp_setting_notify_friend'] );
$pp_friends_approve				= ipsRegistry::getClass('output')->formDropdown( "pp_setting_moderate_friends", $_friendsApprove, $member['pp_setting_moderate_friends'] );

$suspend_date		= '';

if( $member['temp_ban'] )
{
	$s_ban			= IPSMember::processBanEntry( $member['temp_ban'] );
	$suspend_date	= "<div style='float:right;'>" . $this->lang->words['member_supsended_til'] . ' ' . ipsRegistry::getClass('class_localization')->getDate( $s_ban['date_end'], 'LONG', 1 ) . "</div>";
}
			
$IPBHTML = "";

$IPBHTML .= <<<HTML
<script type="text/javascript" src="{$this->settings['js_main_url']}acp.members.js"></script>

<div class='section_title'>
	<h2>{$this->lang->words['editing_member']} <a href='{$this->settings['board_url']}/index.php?showuser={$member['member_id']}'>{$member['members_display_name']}</a></h2>
	<ul class='context_menu'>
		<li class='closed'>
			<a href="#" title='{$this->lang->words['title_delete']}' onclick="return acp.confirmDelete( '{$this->settings['base_url']}app=members&amp;module=members&amp;section=members&amp;do=member_delete&amp;member_id={$member['member_id']}')">
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/delete.png' alt='{$this->lang->words['title_delete']}' />
				{$this->lang->words['form_deletemember']}
			</a>
		</li>
		<li>
			<a id='MF__spam' href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=toggleSpam&amp;member_id={$member['member_id']}&amp;secure_key={$secure_key}' title='{$this->lang->words['title_spam']}'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/flag_red.png' alt='{$spam_member_text}' /> {$spam_member_text}</a>
		</li>
HTML;

if( $member['member_group_id'] == $this->settings['auth_group'] )
{
	$IPBHTML .= <<<HTML
		<li>
			<a href="{$this->settings['base_url']}app=members&amp;module=members&amp;section=tools&amp;do=do_validating&amp;mid_{$member['member_id']}=1&amp;type=approve&amp;_return={$member['member_id']}" title='{$this->lang->words['title_validate']}'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/tick.png' alt='{$this->lang->words['title_validate']}' />
				{$this->lang->words['button_validate']}
			</a>
		</li>
HTML;
}

$IPBHTML .= <<<HTML
		<li>
			<a href='#' id='MF__ban2' title='{$this->lang->words['title_ban']}'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/user_warn.png' alt='{$this->lang->words['title_ban']}' />
				{$ban_member_text}
			</a>
			
			<script type='text/javascript'>
				$('MF__ban2').observe('click', acp.members.banManager.bindAsEventListener( this, "app=members&amp;module=ajax&amp;section=editform&amp;do=show&amp;name=inline_ban_member&amp;member_id={$member['member_id']}" ) );
			</script>
		</li>
		<li>
			<a href='#' class='ipbmenu' id='member_tasks' title='{$this->lang->words['title_tasks']}'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/cog.png' /> {$this->lang->words['mem_tasks']} <img src='{$this->settings['skin_acp_url']}/_newimages/useropts_arrow.png' /></a>
		</li>		
	</ul>
</div>

<ul class='ipbmenu_content' id='member_tasks_menucontent' style='display: none'>
HTML;

if( is_array($menu) AND count($menu) )
{
	foreach( $menu as $app => $link )
	{
		if( is_array($link) AND count($link) )
		{
			$apptitle = ucwords($app);
			
			foreach( $link as $alink )
			{
				$img = $alink['img'] ? $alink['img'] : $this->settings[ 'skin_acp_url' ] . '/_newimages/icons/user.png';
				
				$thisLink = $alink['js'] ? 'href="#" onclick="' . $alink['url'] . '"' : "href='{$this->settings[ '_base_url' ]}app={$app}&amp;{$alink['url']}&amp;member_id={$member['member_id']}'";
				
				$IPBHTML .= <<<HTML
					<li><img src='{$img}' alt='-' /> <a {$thisLink} style='text-decoration: none' >{$alink['title']}</a></li>
HTML;
			}
		}
	}
}

$IPBHTML .= <<<HTML
</ul>
{$suspend_date}
<br style='clear: both' />
HTML;

$IPBHTML .= <<<HTML
<form style='display:block' action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=member_edit&amp;member_id={$member['member_id']}&amp;secure_key={$secure_key}' method='post'>
<script type='text/javascript'>
	ipb.vars['public_avatar_url'] = "{$this->settings['_original_base_url']}/public/style_avatars/";
</script>

	<script type="text/javascript">
	//<![CDATA[
	//_go_go_gadget_editor_hack = true;
	
	document.observe("dom:loaded",function() 
	{
	ipbAcpTabStrips.register('tab_member');
	});
	 //]]>
	</script>

<ul id='tab_member' class='tab_bar no_title'>
	<li id='tabtab-MEMBERS|1' class=''>{$this->lang->words['mem_tab_basics']}</li>
	<li id='tabtab-MEMBERS|2' class=''>{$this->lang->words['mem_tab_profile']}</li>
	<li id='tabtab-MEMBERS|3' class=''>{$this->lang->words['mem_tab_custom_fields']}</li>
	<li id='tabtab-MEMBERS|4' class=''>{$this->lang->words['sm_sigtab']}</li>
	<li id='tabtab-MEMBERS|5' class=''>{$this->lang->words['sm_abouttab']}</li>
HTML;

// Got blocks from other apps?
$IPBHTML .= implode( "\n", $content['tabs'] );

if ( $this->settings['auth_allow_dnames'] )
{
	$display_name = <<<HTML
			<tr>
				<td><strong>{$this->lang->words['mem_display_name']}</strong></td>
				<td>
					<span class='member_detail' id='MF__member_display_name'>{$member['members_display_name']}</span>
					<a class='change_icon' id='MF__member_display_name_popup' href='' style='cursor:pointer' title='{$this->lang->words['title_display_name']}'>{$this->lang->words['mem_change_button']}</a>
					
					<script type='text/javascript'>
						$('MF__member_display_name_popup').observe('click', acp.members.editField.bindAsEventListener( this, 'MF__member_display_name', "{$this->lang->words['sm_display']}", "app=members&amp;module=ajax&amp;section=editform&amp;do=show&amp;name=inline_form_display_name&amp;member_id={$member['member_id']}" ) );
					</script>
				</td>
			</tr>
HTML;
}
else
{
	$display_name = '';
}

$openIdEnabled	= false;
$openid = '';
foreach( $this->cache->getCache('login_methods') as $method )
{
	if( $method['login_folder_name'] == 'openid' )
	{
		$openIdEnabled	= true;
	}
}

if ( $openIdEnabled )
{
	$openid = <<<HTML
			<tr>
				<td><strong>{$this->lang->words['mem_identity_url']}</strong></td>
				<td>
					<span class='member_detail' id='MF__identity_url'>{$form_identity_url}</span>
				</td>
			</tr>
HTML;
}

/* Facebook doesn't pass a size, so in IPB we get around that by passing * as a width. This confuses the form here, so.. */
$_pp_box_width = ( $member['pp_main_width'] == '*' OR $member['pp_main_width'] < 100 ) ? '100' : $member['pp_main_width'];

$IPBHTML .= <<<HTML
 </ul>
<div class='acp-box member_form with_bg'>
	<div id='tabpane-MEMBERS|1'>
		<div style='float: left; width: 70%'>
			<table class='alternate_rows double_pad top_align'>
				{$display_name}
				<tr>
					<td><strong>{$this->lang->words['mem_login_name']}</strong></td>
					<td>
						<span class='member_detail' id='MF__name'>{$member['name']}</span>
						<a href='' class='change_icon' style='cursor:pointer' id='MF__name_popup' title='{$this->lang->words['title_login_name']}'>{$this->lang->words['mem_change_button']}</a>
						<script type='text/javascript'>
							$('MF__name_popup').observe('click', acp.members.editField.bindAsEventListener( this, 'MF__name', "{$this->lang->words['sm_loginname']}", "app=members&amp;module=ajax&amp;section=editform&amp;do=show&amp;name=inline_form_name&amp;member_id={$member['member_id']}" ) );
						</script>
					</td>
				</tr>
				<tr>
					<td><strong>{$this->lang->words['mem_password']}</strong></td>
					<td>
						
						<span class='member_detail' id='MF__password'>************</span> 
						<a href='' class='change_icon' style='cursor:pointer' id='MF__password_popup' title='{$this->lang->words['title_password']}'>{$this->lang->words['mem_change_button']}</a>
						<script type='text/javascript'>
							$('MF__password_popup').observe('click', acp.members.editField.bindAsEventListener( this, 'MF__password', "{$this->lang->words['sm_password']}", "app=members&amp;module=ajax&amp;section=editform&amp;do=show&amp;name=inline_password&amp;member_id={$member['member_id']}" ) );
						</script>
					</td>
				</tr>
				<tr>
					<td><strong>{$this->lang->words['mem_email']}</strong></td>
					<td>
						<span class='member_detail' id='MF__email'>{$member['email']}</span> 
						<a href='' class='change_icon' style='cursor:pointer' id='MF__email_popup' title='{$this->lang->words['title_email']}'>{$this->lang->words['mem_change_button']}</a>
						<script type='text/javascript'>
							$('MF__email_popup').observe('click', acp.members.editField.bindAsEventListener( this, 'MF__email', "{$this->lang->words['sm_email']}", "app=members&amp;module=ajax&amp;section=editform&amp;do=show&amp;name=inline_email&amp;member_id={$member['member_id']}" ) );
						</script>
					</td>
				</tr>
				{$openid}
				<tr>
					<td><strong>{$this->lang->words['mem_form_title']}</strong></td>
					<td>
						<span id='MF__title'>{$form_title}</span>
					</td>
				</tr>
				<tr>
					<td><strong>{$this->lang->words['mem_p_group']}</strong></td>
					<td>
						<span id='MF__member_group_id'>{$form_member_group_id}</span>
					</td>
				</tr>
				<tr>
					<td><strong>{$this->lang->words['mem_s_group']}</strong></td>
					<td>
						<span id='MF__mgroup_others'>{$form_mgroup_others}</span>
					</td>
				</tr>
				<tr>
					<td><strong>{$this->lang->words['mem_warn_level']}</strong></td>
					<td>
								
						<span id='MF__warn_level'>{$form_warn}</span>&nbsp;&nbsp;<a href='#' onclick="return acp.openWindow('{$this->settings['board_url']}/index.php?app=members&amp;module=warn&amp;section=warn&amp;mid={$member['member_id']}&amp;do=view&amp;popup=1','980','600'); return false;" title='{$this->lang->words['sm_viewnotes']}'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/view.png' alt='{$this->lang->words['sm_viewnotes']}' /></a>
						 <a href='#' onclick="return acp.openWindow('{$this->settings['board_url']}/index.php?app=members&amp;module=warn&amp;section=warn&amp;mid={$member['member_id']}&amp;do=add_note&amp;popup=1','500','450'); return false;" title='{$this->lang->words['sm_addnote']}'><img src='{$this->settings['skin_acp_url']}/images/note_add.png' alt='{$this->lang->words['sm_addnote']}' /></a>
					</td>
				 </tr>
			</table>
		</div>
		<div style='float: left; width: 30%' class='acp-sidebar'>
			<div style='border:1px solid #369;background:#FFF;width:{$_pp_box_width}px; padding:5px; margin: 10px auto;' id='MF__pp_photo_container'>
				<img id='MF__pp_photo' src="{$member['pp_main_photo']}" width='{$member['pp_main_width']}' height='{$member['pp_main_height']}' />
				<br />
				<ul class='photo_options'>
HTML;

if( $member['_has_photo'] )
{
$IPBHTML .= <<<HTML
				<li><a class='' style='float:none;width:auto;text-align:center;cursor:pointer' id='MF__removephoto' title='{$this->lang->words['mem_remove_photo']}'><img src='{$this->settings['skin_acp_url']}/images/picture_delete.png' alt='{$this->lang->words['mem_remove_photo']}' /></a></li>
				<li><a class='' style='float:none;width:auto;text-align:center;cursor:pointer' id='MF__newphoto' title='{$this->lang->words['sm_uploadnew']}'><img src='{$this->settings['skin_acp_url']}/images/picture_add.png' alt='{$this->lang->words['sm_uploadnew']}' /></a></li>
HTML;
}
else
{
$IPBHTML .= <<<HTML
				<li><a class='' style='float:none;width:auto;text-align:center;cursor:pointer' id='MF__newphoto' title='{$this->lang->words['sm_uploadnew']}'><img src='{$this->settings['skin_acp_url']}/images/picture_add.png' alt='{$this->lang->words['sm_uploadnew']}' /></a></li>
HTML;
}

$IPBHTML .= <<<HTML
				</ul>
				<script type='text/javascript'>
					$('MF__newphoto').observe('click', acp.members.newPhoto.bindAsEventListener( this, "app=members&amp;module=ajax&amp;section=editform&amp;do=show&amp;name=inline_form_new_photo&amp;member_id={$member['member_id']}" ) );
				</script>
			</div>
			
			<div class='sidebar_box'>
				<strong>{$this->lang->words['mem_joined']}:</strong> {$member['_joined']}<br /><br />
				<strong>{$this->lang->words['mem_ip_address_f']}:</strong> <a href='{$this->settings['base_url']}&amp;module=members&amp;section=tools&amp;do=learn_ip&amp;ip={$member['ip_address']}' title='{$this->lang->words['mem_ip_title']}'>{$member['ip_address']}</a>
			</div>
		</div>
		<div style='clear: both;'></div>
	</div>
HTML;



$IPBHTML .= <<<HTML
	<!-- PROFILE PANE-->
	<div id='tabpane-MEMBERS|2' class='tablerow1'>
	<table class='alternate_rows double_pad' cellspacing='0'>
	
					<tr>
						<th colspan='2'>{$this->lang->words['sm_settings']}</th>
					</tr>
					<tr>
						<td><strong>{$this->lang->words['sm_timeoffset']}</strong></td>
						<td>
							<span id='MF__time_offset'>{$form_time_offset}</span>
						</td>
					</tr>
					<tr>
						<td><strong>{$this->lang->words['sm_langchoice']}</strong></td>
						<td>
							<span id='MF__language'>{$form_language}</span>
						</td>
					</tr>
					<tr>
						<td><strong>{$this->lang->words['sm_skinchoice']}</strong></td>
						<td>
							<span id='MF__skin'>
								{$skinList}
							</span>
						</td>
					</tr>
					<tr>
						<td><strong>{$this->lang->words['sm_hideemail']}</strong></td>
						<td>
							<span id='MF__hide_email'>{$form_hide_email}</span>
						</td>
					</tr>
					<tr>
						<td><strong>{$this->lang->words['sm_allowadmin']}</strong></td>
						<td>
							<span id='MF__allow_admin_mails'>{$form_allow_admin_mails}</span>
						</td>
					</tr>
					<tr>
						<td><strong>{$this->lang->words['sm_emailpm']}</strong></td>
						<td>
							<span id='MF__email_pm'>{$form_email_pm}</span>
						</td>
					</tr>
					<tr>
						<td><strong>{$this->lang->words['sm_disablepm']}</strong></td>
						<td>
							<span id='MF__members_disable_pm'>{$form_members_disable_pm}</span>
						</td>
					</tr>
					<tr>
						<td><strong>{$this->lang->words['sm_viewsig']}</strong></td>
						<td>
							<span id='MF__view_sig'>{$form_view_sig}</span>
						</td>
					</tr>
					<tr>
						<td><strong>{$this->lang->words['sm_viewpm']}</strong></td>
						<td>
							<span id='MF__view_pop'>{$form_view_pop}</span>
						</td>
					</tr>
			
					<tr>
						<th colspan='2'>{$this->lang->words['sm_profile']}</th>
					</tr>
				
					<tr>
						<td><strong>{$this->lang->words['sm_bday']}</strong></td>
						<td>
							<span id='MF__birthday'>{$_form_month} {$_form_day} {$_form_year}</span>
						</td>
					</tr>
					<tr>
						<td><strong>{$this->lang->words['sm_status']}</strong></td>
						<td>
							<span id='MF__status'>{$pp_status}</span>
						</td>
					</tr>
					<tr>
						<td><strong>{$this->lang->words['sm_statement']}</strong></td>
						<td>
							<span id='MF__pp_bio_content'>{$form_pp_bio_content}</span>
						</td>						
					</tr>
					<tr>
						<td><strong>{$this->lang->words['sm_reputation']}</strong></td>
						<td>
							<span id='MF__pp_reputation_points'>
								{$form_reputation_points} 
								<a href='#' onclick="return acp.openWindow('{$this->settings['base_url']}{$this->form_code}&amp;do=view_rep&amp;id={$this->request['member_id']}&amp;type=received','750','550'); return false;" title='{$this->lang->words['sm_rep_view_r']}'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/rep_received.png' alt='{$this->lang->words['sm_rep_view_r']}' /></a> 
								<a href='#' onclick="return acp.openWindow('{$this->settings['base_url']}{$this->form_code}&amp;do=view_rep&amp;id={$this->request['member_id']}&amp;type=given','750','550'); return false;" title='{$this->lang->words['sm_rep_view_g']}'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/rep_given.png' alt='{$this->lang->words['sm_rep_view_g']}' /></a>
							</span>
						</td>						
					</tr>
					
					<tr>
						<td><strong>{$this->lang->words['sm_latest_visitors']}</strong></td>
						<td>
							<span id='MF__visitors'>{$pp_visitors}</span>
						</td>
					</tr>
					<tr>
						<td><strong>{$this->lang->words['sm_enable_comments']}</strong></td>
						<td>
							<span id='MF__profile_comments'>{$pp_enable_comments}</span>
						</td>						
					</tr>
					<tr>
						<td><strong>{$this->lang->words['sm_comment_notify']}</strong></td>
						<td>
							<span id='MF__comments_notify'>{$pp_comments_notify}</span>
						</td>						
					</tr>
					<tr>
						<td><strong>{$this->lang->words['sm_approve_comments']}</strong></td>
						<td>
							<span id='MF__comments_approve'>{$pp_comments_approve}</span>
						</td>						
					</tr>
					<tr>
						<td><strong>{$this->lang->words['sm_friends_profile']}</strong></td>
						<td>
							<span id='MF__profile_friends'>{$pp_enable_friends}</span>
						</td>						
					</tr>
					<tr>
						<td><strong>{$this->lang->words['sm_friends_notify']}</strong></td>
						<td>
							<span id='MF__friends_notify'>{$pp_friends_notify}</span>
						</td>						
					</tr>
					<tr>
						<td><strong>{$this->lang->words['sm_approve_friends']}</strong></td>
						<td>
							<span id='MF__friends_approve'>{$pp_friends_approve}</span>
						</td>						
					</tr>
				</table>
	</div>
	<!-- / PROFILE PANE -->
		
	<!-- SIGNATURE-->
	<div id='tabpane-MEMBERS|4' class='tablerow1 has_editor'>
		<div class='editor'>
			{$member['signature_editor']}
		</div>
	</div>
	<!-- / SIGNATURE-->
		
	<!-- ABOUT ME-->
	<div id='tabpane-MEMBERS|5' class='tablerow1 has_editor'>
		<div class='editor'>
			{$member['aboutme_editor']}
		</div>
	</div>
	<!-- / ABOUT ME-->
	
	<!-- CUSTOM FIELDS PANE-->
	<div id='tabpane-MEMBERS|3' class='tablerow1'>
HTML;
	if ( is_array( $member['custom_fields'] ) AND count( $member['custom_fields'] ) )
	{
		$IPBHTML .= <<<HTML
		<table class='alternate_rows double_pad' cellspacing='0'>
			<tr>
				<th colspan='2'>{$this->lang->words['sm_custom']}</th>
			</tr>
HTML;
		foreach( $member['custom_fields'] as $_id => $_data )
		{
			$IPBHTML .= <<<HTML
				<tr>
					<td><strong>{$_data['name']}</strong></td>
					<td>
						<span id='custom_fields_{$_id}'>{$_data['data']}</span>
					</td>
				</tr>
HTML;
		}
		
		$IPBHTML .= <<<HTML
		</table>
HTML;
	}
	else
	{
$IPBHTML .= <<<HTML
		<div>{$this->lang->words['sm_nofields']}</div>
HTML;
	}

$IPBHTML .= <<<HTML
		
		
		
	</div>
	<!-- / CUSTOM FIELDS PANE -->
HTML;

// Got blocks from other apps?
$IPBHTML .= implode( "\n", $content['area'] );

$IPBHTML .= <<<HTML
<div class='acp-actionbar'><input class='button primary' type='submit' value=' {$this->lang->words['sm_savebutton']} ' /></div>
</div>

</form>

<script type='text/javascript'>
	try {
		/*$('tabpane-MEMBERS|2').hide();
		$('tabpane-MEMBERS|3').hide();
		$('tabpane-MEMBERS|4').hide();
		$('tabpane-MEMBERS|5').hide();*/
	} catch(err ){
		Debug.write( err );
	}
	
	if( $('MF__removephoto') )
	{
		$('MF__removephoto').observe( 'click', acp.members.removePhoto.bindAsEventListener( this, '{$member['member_id']}' ) );
	}
</script>
HTML;

return $IPBHTML;
}

/**
 * List of members
 *
 * @access	public
 * @param	array 		Members
 * @param	string		Pages
 * @return	string		HTML
 */
public function members_list( $members, $pages='' ) {

$IPBHTML = "";
//--starthtml--//


//-----------------------------------------
// BADGE STYLEE
//-----------------------------------------

$IPBHTML .= <<<HTML
{$pages}
<br style='clear: both' />
<div class='acp-box'>
	<h3>{$this->lang->words['sm_members']}</h3>
<table class='alternate_rows double_pad'>
	<tr>
		<th style='width: 5%'></th>
		<th style='width: 30%'>{$this->lang->words['list__dn']}</th>
		<th style='width: 25%'>{$this->lang->words['list__email']}</th>
		<th style='width: 20%'>{$this->lang->words['list__group']}</th>
		<th style='width: 20%'>{$this->lang->words['list__ip']}</th>
	</tr>
HTML;

if( count( $members ) )
{
	foreach( $members as $member )
	{
		$member['group_title'] = IPSLib::makeNameFormatted( $member['group_title'], $member['member_group_id'] );
		
		if( trim($member['members_display_name']) == '' )
		{
			$member['members_display_name'] = "<em class='desctext'>{$this->lang->words['sm_nodisplayname']}</em>";
		}
		
		$_extraStyle = ( $member['member_banned'] ) ? ' class="_red"' : ( $member['bw_is_spammer'] ? ' class="_amber"' : '' );
		$_extraText  = ( $member['member_banned'] ) ? '(' . $this->lang->words['m_f_showbanned'] . ')' : ( $member['bw_is_spammer'] ? '(' . $this->lang->words['m_f_showspam'] . ')' : '' );
		
		$IPBHTML .= <<<HTML
			<tr id='member-{$member['member_id']}'{$_extraStyle}>
				<td><a href='{$this->settings['base_url']}&amp;module=members&amp;section=members&amp;do=viewmember&amp;member_id={$member['member_id']}'><img src='{$member['pp_thumb_photo']}' style='width: 30px; height: 30px; border: 1px solid #d8d8d8' /></a></td>			
				<td class='member_name'><a href='{$this->settings['base_url']}&amp;module=members&amp;section=members&amp;do=viewmember&amp;member_id={$member['member_id']}'>{$member['members_display_name']}</a></td>
				<td>{$member['email']}</td>
				<td>{$member['group_title']} <span style='color:gray;font-size:0.8em'>$_extraText</span></td>
				<td><a href='{$this->settings['base_url']}&amp;app=members&amp;module=members&amp;section=tools&amp;do=learn_ip&amp;ip={$member['ip_address']}'>{$member['ip_address']}</a></td>
			</tr>
HTML;

	}
}
else 
{
$IPBHTML .= <<<HTML
<td style='padding: 10px;' colspan='8'>
		{$this->lang->words['sm_nomemfound']}
</td>
	
HTML;
}

$IPBHTML .= <<<HTML
</table>
 </table>
</div>
<br style='clear: both' />
{$pages}
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Member list context menu
 *
 * @access	public
 * @param	array 		Form elements
 * @param	object		Custom fields
 * @param   bool        Preset Fitlers
 * @return	string		HTML
 */
public function member_list_context_menu_filters( $form=array(), $fields=null, $filters_preset=0 ) {

$IPBHTML = "";
//--starthtml--//
$cur = false;
$left = $right = '';

if ( is_array( $fields->out_fields ) AND count( $fields->out_fields ) )
{
	foreach( $fields->out_fields as $id => $data )
	{
		$ignore = '';
		
		if( $fields->cache_data[ $id ]['type'] == 'radio' )
		{
			$ignore = "<div style='float:right;'> <input type='checkbox' name='ignore_field_{$id}' value='1' /> {$this->lang->words['sm_ignorehuh']}</div>";
		}
		
		if( $cur == true )
		{
			$right .= <<<HTML
				<li>
					<label for='{$id}'>{$fields->field_names[ $id ]}</label>
					{$ignore}{$data}
				</li>
HTML;
			$cur = false;
		}
		else
		{
			$left .= <<<HTML
				<li>
					<label for='{$id}'>{$fields->field_names[ $id ]}</label>
					{$ignore}{$data}
				</li>
HTML;
			$cur = true;
		} 
	}
}
	
$IPBHTML .= <<<HTML
<script type="text/javascript" src="{$this->settings['js_main_url']}acp.members.js"></script>

<div class='section_title'>
	<h2>{$this->lang->words['member_management_h2']}</h2>
	<ul class='context_menu'>
		<li>
			<a href='{$this->settings['base_url']}app=members&amp;module=members&amp;section=members&amp;do=add'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/user_add.png' alt='{$this->lang->words['add_new_member_button']}' />
				{$this->lang->words['add_new_member_button']}
			</a>
		</li>
	</ul>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['member_search_h3']}</h3>
	<div class='member_search'>
		<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=members_list' method='post' id='memberListForm'>
		    <input type='hidden' name='f_search_type' value='' id='f_search_type' />
			<input type='hidden' name='__update' value='1' />
			<div class='simple'>
				<span class='desctext'>{$this->lang->words['search_member_type']} &nbsp;</span> 
				{$form['_member_contains']}
				{$form['_member_contains_type']}
				{$form['_member_contains_text']}
			</div>
			<div class='acp-actionbar' id='m_simple'>
				<div class='centeraction'>
					 <input type='submit' class='realbutton' value=' {$this->lang->words['mem_update_member_list']} ' /> <span class='desctext'>{$this->lang->words['or']}</span> <input type='button' class='realbutton' value=' {$this->lang->words['use_advanced_search']} ' id='show_advanced' />
				</div>
			</div>			
			<div id='m_advanced' style='display: none'>
				<div class='tablesubheader'>{$this->lang->words['other_search_filters']}</div>
				<table width='100%' border='0' cellpadding='0' cellspacing='0'>
					<tr>
						<td width='45%' style='vertical-align: top'>
							<ul>
								<li>
									<label for='f_member_type'>{$this->lang->words['suspend_status']}</label>
									{$form['_member_type']}
								</li>
								<li>
									<label for='f_member_type'>{$this->lang->words['banned_status']}</label>
									{$form['_banned_type']}
								</li>
								<li>
									<label for='f_member_type'>{$this->lang->words['spam_status']}</label>
									{$form['_spam_type']}
								</li>
								<li>
									<label for='f_primary_group'>{$this->lang->words['search_prim_group']}</label>
									{$form['_primary_group']}
								</li>
								<li>
									<label for='f_secondary_group'>{$this->lang->words['search_secon_group']}</label>
									{$form['_secondary_group']}
								</li>
								{$left}
							</ul>
						</td>
						<td width='55%' style='vertical-align: top'>
							<ul>
								<li>
									<label for='f_date_reg_from'>{$this->lang->words['registered_between']}</label>
									{$form['_date_reg_from']} {$this->lang->words['and']} {$form['_date_reg_to']} <span class='desctext'>(MM-DD-YYYY)</span>
								</li>
								<li>
									<label for='f_date_post_from'>{$this->lang->words['last_posted_between']}</label>
									{$form['_date_post_from']} {$this->lang->words['and']} {$form['_date_post_to']} <span class='desctext'>(MM-DD-YYYY)</span>
								</li>
								<li>
									<label for='f_date_active_from'>{$this->lang->words['last_active_between']}</label>
									{$form['_date_active_from']} {$this->lang->words['and']} {$form['_date_active_to']} <span class='desctext'>(MM-DD-YYYY)</span>
								</li>
								<li>
									<label for='f_post_count'>{$this->lang->words['post_count_is']}</label>
									{$form['_post_count_type']} &nbsp;&nbsp;{$form['_post_count']}
								</li>
								{$right}
							</ul>
						</td>
					</tr>
					<tr>
						<td style='text-align: center' colspan='2'>
							<span class='desctext'>{$this->lang->words['search_sort_by']}</span> {$form['_order_by']} <span class='desctext'>{$this->lang->words['in']}</span> {$form['_order_direction']} <span class='desctext'>{$this->lang->words['search_sort_order']}</span>
						</td>
					</tr>
				</table>
				<div class='acp-actionbar'>
					<div class='centeraction'>
						 <input type='submit' class='button primary' value=' {$this->lang->words['mem_update_member_list']} ' /> <span class='desctext'>{$this->lang->words['or']}</span> <input type='button' class='button secondary' value=' {$this->lang->words['use_simple_search']} ' id='show_simple' />
					</div>
				</div>
			</div>
		</form>
	</div>
</div>

<script type='text/javascript'>
	$('show_advanced').observe('click', acp.members.switchSearch.bindAsEventListener( this, 'advanced' ) );
	$('show_simple').observe('click', acp.members.switchSearch.bindAsEventListener( this, 'simple' ) );
</script>

<br />
HTML;

if( ( $filters_preset && !$this->request['f_search_type'] ) || $this->request['__update'] == 1 )
{
	$this->lang->words['mem_results_filtered'] = sprintf( $this->lang->words['mem_results_filtered'], "{$this->settings['base_url']}{$this->form_code}&amp;reset_filters=1" );
$IPBHTML .= <<<HTML
<div class='information-box'>
{$this->lang->words['mem_results_filtered']}
</div>
<br />

<ul class='context_menu'>
	<li class='closed'>
		<a href='#' id='memberList__prune'>
			<img src='{$this->settings['skin_acp_url']}/_newimages/icons/user_delete.png' alt='' />
			{$this->lang->words['prune_all_members']}
		</a>
	</li>
	<li>
		<a href='#' id='memberList__move'>
			<img src='{$this->settings['skin_acp_url']}/_newimages/icons/user_assign.png' alt='' />
			{$this->lang->words['move_all_members']}
		</a>
	</li>
</ul>
<script type='text/javascript'>
	$('memberList__prune').observe('click', acp.members.movePruneAction.bindAsEventListener( this, 'delete' ) );
	$('memberList__move').observe('click', acp.members.movePruneAction.bindAsEventListener( this, 'move' ) );
</script>
	
<br />
HTML;
}

//--endhtml--//
return $IPBHTML;
}

/**
 * Member view context menu
 *
 * @access	public
 * @param	array 		Links
 * @param	int			Member ID
 * @return	string		HTML
 */
public function member_view_context_menu( $links=array(), $member_id=0 ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['editing_member']}</h2>
	<ul class='context_menu'>
		<li class='closed'>
			<a href="#" onclick="return acp.confirmDelete( '{$this->settings['base_url']}app=members&amp;app=members&amp;module=members&amp;section=members&amp;module=members&amp;do=member_delete&amp;member_id={$member_id}')">
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/delete.png' alt='{$this->lang->words['icon']}' />
				{$this->lang->words['form_deletemember']}
			</a>
		</li>
		<li>
			<a href='#' onclick='return ipsInlineForm.loadForm( "{$this->lang->words['sm_banmanagement']}", "app=members&amp;module=ajax&amp;section=editform&amp;do=show&amp;name=inline_ban_member&amp;member_id={$member_id}");'>
				
				{$ban_member_text} {$this->lang->words['sm_member']}
			</a>
		</li>				
	</ul>
</div>

<div class='menuouterwrap-dark'>
<div class='menucatwrap'><img src='{$this->settings['skin_acp_url']}/images/menu_title_bullet.gif' style='vertical-align:middle' border='0' />{$this->lang->words['sm_memoptions']}</div>
HTML;

if( is_array($links) AND count($links) )
{
	foreach( $links as $app => $link )
	{
		if( is_array($link) AND count($link) )
		{
			$apptitle = ucwords($app);
	
			$IPBHTML .= <<<HTML
		<fieldset style='padding:3px;margin:3px'>
	<legend><img src='{$this->settings['skin_acp_url']}/_newimages/icons/cog.png' border='0' alt='' style='vertical-align:bottom' /> <strong>{$apptitle}</strong></legend>			
HTML;
			foreach( $link as $alink )
			{
				$img = $alink['img'] ? $alink['img'] : $this->settings[ 'skin_acp_url' ] . '/images/menu-right.gif';
				
				$thisLink = $alink['js'] ? 'href="#" onclick="' . $alink['url'] . '"' : "href='{$this->settings[ '_base_url' ]}app={$app}&amp;{$alink['url']}&amp;member_id={$member_id}'";
				
				$IPBHTML .= <<<HTML
					<div class='menulinkwrap'><img src='{$img}' alt='-' /> <a {$thisLink}>{$alink['title']}</a></div>
HTML;
			}
			
			$IPBHTML .= <<<HTML
				</fieldset>
HTML;
		}
	}
}

$IPBHTML .= <<<HTML
</div>
<br />
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Member suspension form
 *
 * @access	public
 * @param	array 		Member data
 * @return	string		HTML
 */
public function memberSuspension( $member ) {

$IPBHTML = "";
//--starthtml--//

$dropDown	= ipsRegistry::getClass('output')->formDropdown( 'units', array( array( 'h', $this->lang->words['dunit_hours'] ), array( 'd', $this->lang->words['dunit_days'] ) ), $member['units'] );
$yesNo		= ipsRegistry::getClass('output')->formYesNo( 'send_email', 0 );
$email		= ipsRegistry::getClass('output')->formTextarea( 'email_contents', $member['contents'] );
$susp_for = sprintf( $this->lang->words['sm_suspfor'], $member['members_display_name'] );

$IPBHTML .= <<<HTML
<div class='tableborder'>
 <div class='tableheaderalt'>{$this->lang->words['sm_acctsusp']}</div>
 	<div class='tablesubheader'>{$this->lang->words['sm_suspnote']}</div>
 <form name='theAdminForm' id='adminform' action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=dobanmember' method='post'>
 <input type='hidden' name='member_id' value='{$member['member_id']}' />
 <input type='hidden' name='_admin_auth_key' value='{$this->registry->getClass('adminFunctions')->_admin_auth_key}' />
 <table cellpadding='4' cellspacing='0' width='100%'>
			 <tr>
				<td width='40%' class='tablerow1'>{$susp_for}</td>
				<td width='60%' class='tablerow2'><input type='text' size='5' name='timespan' value='{$member['timespan']}' /> 
			 		{$dropDown}
 				</td>
			 </tr>
			 <tr>
				<td width='40%' class='tablerow1'>{$this->lang->words['sm_suspnotify']}</td>
				<td width='60%' class='tablerow2'>{$yesNo}</td>
			 </tr>
			 <tr>
				<td width='40%' class='tablerow1'>{$this->lang->words['sm_suspemailnotify']}</td>
				<td width='60%' class='tablerow2'>{$email}</td>
			 </tr>
 <tr>
  <td class='tablesubheader' colspan='2' align='center'>
   <input type='submit' class='realbutton' value=' {$this->lang->words['sm_suspendbutton']} ' />
  </td>
 </tr>
 </table>
 </form>
</div>

HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Should this admin get ACP restrictions?
 *
 * @access	public
 * @param	array 		Member data
 * @param	array 		Admin groups
 * @return	string		HTML
 */
public function memberAdminConfirm( $member, $admins ) {

$wedectectedthis = sprintf( $this->lang->words['sm_detectacp'], $member['members_display_name'] );

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<!--SKINNOTE: Not yet skinned-->
<div class='acp-box'>
	<h3>{$this->lang->words['sm_configrest']}</h3>
HTML;
	if( $wedetectedthis ){
		$IPBHTML .= "<strong>{$wedetectedthis}</strong><br /><br />";
	}
	
$IPBHTML .= <<<HTML
	<div style='padding: 15px' class='acp-row-on'>
		{$this->lang->words['sm_belongsto']}<br />
		<ul style='padding: 8px 8px 8px 15px'>
HTML;

	foreach( $admins as $group_id => $restricted )
	{
		$restrict_text	= $restricted ? $this->lang->words['sm_is'] : $this->lang->words['sm_isnot'];
		$group_title	= $this->caches['group_cache'][ $group_id ]['g_title'];
		$thisgroupisorisnot = sprintf ( $this->lang->words['sm_thisgroup'], $group_title, $restrict_text );
		$IPBHTML .= <<<HTML
			<li>{$thisgroupisorisnot}</li>
HTML;
	}
	
	$IPBHTML .= <<<HTML
		</ul>
		<br /><br />
		{$this->lang->words['sm_setrestrict']}
	</div>
	<div class='acp-actionbar' style='padding-top: 12px;'>
		<a class='button' href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=viewmember&amp;member_id={$member['member_id']}'>{$this->lang->words['sm_nothanks']}</a>
		<a class='button' href='{$this->settings['base_url']}&amp;module=restrictions&amp;section=restrictions&amp;do=acpperms-member-add-complete&amp;entered_name={$member['members_display_name']}'>{$this->lang->words['sm_yesplease']}</a>
	</div>
</div>
<br />
HTML;

//--endhtml--//
return $IPBHTML;
}


/**
 * Form to add a member
 *
 * @access	public
 * @param	array 		Groups
 * @param	object		Custom fields
 * @return	string		HTML
 */
public function memberAddForm( $groups, $fields ) {

$IPBHTML = "";
//--starthtml--//

//-----------------------------------------
// Got admin restrictions?
//-----------------------------------------

if ( $this->memberData['row_perm_cache'] )
{
	$IPBHTML .= "<div class='input-warn-content' style='color:black'>{$this->lang->words['sm_acpresinfo']}</div><br />";
}

$group		= ipsRegistry::getClass('output')->formDropdown( 'member_group_id', $groups, isset($_POST['member_group_id']) ? $_POST['member_group_id'] : $this->settings['member_group'] );
$email		= ipsRegistry::getClass('output')->formInput( 'email', isset($_POST['email']) ? IPSText::stripslashes($_POST['email']) : '' );
$name		= ipsRegistry::getClass('output')->formInput( 'name', isset($_POST['name']) ? IPSText::stripslashes($_POST['name']) : '' );
$password	= ipsRegistry::getClass('output')->formInput( 'password', isset($_POST['password']) ? IPSText::stripslashes($_POST['password']) : '', 'password', 30, 'password' );
$coppa		= ipsRegistry::getClass('output')->formYesNo( 'coppa', isset($_POST['coppa']) ? $_POST['coppa'] : 0 );
$send_email	= ipsRegistry::getClass('output')->formYesNo( 'sendemail', isset($_POST['sendemail']) ? $_POST['sendemail'] : 1 );

if( $this->settings['auth_allow_dnames'] )
{
	$display_name	= ipsRegistry::getClass('output')->formInput( 'members_display_name', isset($_POST['members_display_name']) ? IPSText::stripslashes($_POST['members_display_name']) : '' );
}


$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['add_new_member_button']}</h2>
</div>

<form name='theAdminForm' id='adminform' action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=doadd' method='post'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->getClass('adminFunctions')->_admin_auth_key}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['sm_registernew']}</h3>
 
 		<ul class='acp-form alternate_rows'>
			<li>
				<label>{$this->lang->words['sm_loginname']}</label>
				{$name}
			</li>
HTML;

if( $this->settings['auth_allow_dnames'] )
{
	$IPBHTML .= <<<HTML
			<li>
				<label>{$this->lang->words['sm_display']}</label>
				{$display_name}
			</li>
HTML;
}

$IPBHTML .= <<<HTML
			<li>
				<label>{$this->lang->words['sm_password']}</label>
				{$password}
			</li>
			<li>
				<label>{$this->lang->words['sm_email']}</label>
				{$email}
			</li>
			<li>
				<label>{$this->lang->words['sm_group']}</label>
				{$group}
			</li>
			<li>
				<label>{$this->lang->words['sm_coppauser']}</label>
				{$coppa}
			</li>
			<li>
				<label>{$this->lang->words['sm_sendconf']}<span class='desctext'>{$this->lang->words['sm_sendconf_info']}</span></label>
				{$send_email}
			</li>
HTML;

// Custom Fields
if ( count( $fields->out_fields ) )
{
	$IPBHTML .= <<<HTML
			<li>
				<label class='head'>{$this->lang->words['sm_custfields']}</label>
			</li>
HTML;

	foreach( $fields->out_fields as $id => $data )
	{
		$class = '';
		$req   = '';
		
		if ( $fields->cache_data[ $id ]['pf_admin_only'] )
		{
			$class = " class='_amber'";
			$req   = '<span style="color:orange">*<br />' . $this->lang->words['add_cf_admin'] . '</span>';
		}
		
		$req   .= ( $fields->cache_data[ $id ]['pf_not_null'] ) ? '<span style="color:red">*<br />' . $this->lang->words['add_cf_required'] . '</span>' : '';
		$class  = ( $fields->cache_data[ $id ]['pf_not_null'] ) ? " class='_red'" : '';
		
		$IPBHTML .= <<<HTML
			<li{$class}>
				<label>{$fields->field_names[ $id ]}{$req}<span class='desctext'>{$fields->field_desc[ $id ]}</span></label>
				{$data}
			</li>
HTML;
	}
}

$IPBHTML .= <<<HTML
		</ul>
		
		<div class='acp-actionbar'>
			<div class='centeraction'>
				 <input type='submit' class='button primary' value=' {$this->lang->words['sm_regbutton']} ' />
			</div>
		</div>
	</div>
 </form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Confirm member pruning
 *
 * @access	public
 * @param	int			Total count
 * @return	string		HTML
 */
public function pruneConfirm( $count ) {

$prune_button = sprintf( $this->lang->words['sm_prunebutton'], $count );

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<!--SKINNOTE: Not yet skinned-->

<form name='theAdminForm' id='adminform' action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=doprune' method='post'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->getClass('adminFunctions')->_admin_auth_key}' />
	<div class='warning'>
		<h4>{$this->lang->words['about_to_prune']}</h4>
		{$this->lang->words['sm_prunemem_info']}
	</div>
	<br />
	<div class='acp-box'>
		<h3>{$this->lang->words['sm_prunemem']}</h3>
		<table class='alternate_rows double_pad'>
			<tr>
				<td style='width: 30%'><strong>{$this->lang->words['sm_prunenum']}</strong></td>
				<td style='width: 70%'>
					{$count}
				</td>
			</tr>
		</table>
		<div class='acp-actionbar'><input type='submit' class='realbutton redbutton' value=' {$prune_button} ' /></div>
	</div>
</form>
<br />

HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Confirm moving members
 *
 * @access	public
 * @param	int			Total count
 * @return	string		HTML
 */
public function moveConfirm( $count ) {

$IPBHTML = "";
//--starthtml--//

$member_groups = array();

foreach( ipsRegistry::cache()->getCache( 'group_cache' ) as $k => $v )
{
	$member_groups[] = array( $v['g_id'], $v['g_title'] );
}

$group		= ipsRegistry::getClass('output')->formDropdown( 'move_to_group', $member_groups, isset($_POST['member_group_id']) ? $_POST['member_group_id'] : $this->settings['member_group'] );
$move_button = sprintf( $this->lang->words['sm_movebutton'], $count );
$IPBHTML .= <<<HTML

<form name='theAdminForm' id='adminform' action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=domove' method='post'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->getClass('adminFunctions')->_admin_auth_key}' />
	<div class='warning'>
		<h4>{$this->lang->words['about_to_move']}</h4>
		{$this->lang->words['sm_movemem_info']}
	</div>
	<br />
	<div class='acp-box'>
		<h3>{$this->lang->words['sm_movemem']}</h3>
		<table class='alternate_rows double_pad'>
			<tr>
				<td style='width: 30%'><strong>{$this->lang->words['sm_prunenum']}</strong></td>
				<td style='width: 70%'>
					{$count}
				</td>
			</tr>
			<tr>
				<td style='width: 30%'><strong>{$this->lang->words['sm_movegroup']}</strong></td>
				<td style='width: 70%'>
					{$group}
				</td>
			</tr>
		</table>
		<div class='acp-actionbar'><input type='submit' class='realbutton redbutton' value=' {$move_button} ' /></div>
	</div>
</form>
<br />

HTML;

//--endhtml--//
return $IPBHTML;
}


}