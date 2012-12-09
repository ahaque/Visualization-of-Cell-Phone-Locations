<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Dashboard skin file
 * Last Updated: $Date: 2009-08-20 18:20:40 -0400 (Thu, 20 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 5035 $
 */
 
class cp_skin_mycp extends output
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
 * Main dashboard template
 *
 * @access	public
 * @param	array 		Content blocks
 * @param	array 		Forums
 * @param	array 		Groups
 * @param	array 		URLs
 * @return	string		HTML
 */
public function mainTemplate( $content, $forums, $groups, $urls=array() ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['cp_welcomeipb3']}</h2>
</div>
<!--in_dev_notes-->
<!--in_dev_check-->
<!--warninginstaller-->
<!--warningupgrade-->
<!--boardoffline-->
<!--fulltext-->
<div id='dashboard'>
	<div id='quick_start'>	
		<table width='100%' cellpadding='0' cellspacing='0'>
			<tr>
				<td>
					<a href='{$this->settings['_base_url']}app=members&amp;module=members&amp;section=members' title='{$this->lang->words['cp_managemembers']}'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/user_edit.png' border='0' alt='{$this->lang->words['cp_managemembers']}' /> {$this->lang->words['cp_managemembers']}</a>
				</td>
				<td>
					<a href='{$this->settings['_base_url']}app=core&amp;module=tools&amp;section=settings&amp;do=settingsview' title='{$this->lang->words['cp_editsettings']}'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/cog_edit.png' border='0' alt='{$this->lang->words['cp_editsettings']}' /> {$this->lang->words['cp_editsettings']}</a>
				</td>
				<td>
					<a href='{$this->settings['_base_url']}app=core&amp;module=templates&amp;section=skinsets' title='{$this->lang->words['cp_skinmanager']}'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/folder_palette.png' border='0' alt='{$this->lang->words['cp_skinmanager']}' /> {$this->lang->words['cp_skinmanager']}</a>
				</td>
			</tr>
			<tr>
				<td>
					<a href='{$this->settings['_base_url']}app=members&amp;module=groups&amp;section=groups&amp;do=groups_overview' title='{$this->lang->words['cp_managegroups']}'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/group.png' border='0' alt='{$this->lang->words['cp_managegroups']}' /> {$this->lang->words['cp_managegroups']}</a>
				</td>
				<td>
					<a href='{$this->settings['_base_url']}app=members&amp;module=members&amp;section=tools&amp;do=validating' title='{$this->lang->words['cp_managevalidating']}'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/user_warn.png' border='0' alt='{$this->lang->words['cp_managevalidating']}' /> {$this->lang->words['cp_managevalidating']}</a>
				</td>
				<td>
					<a href='{$this->settings['_base_url']}app=core&amp;module=languages&amp;section=manage_languages' title='{$this->lang->words['cp_langmanager']}'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/book_next.png' border='0' alt='{$this->lang->words['cp_langmanager']}' /> {$this->lang->words['cp_langmanager']}</a>
				</td>
			</tr>
			<tr>
				<td>
					<a href='{$this->settings['_base_url']}app=forums&amp;module=forums&amp;section=forums' title='{$this->lang->words['cp_manageforums']}'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/comments.png' border='0' alt='{$this->lang->words['cp_manageforums']}' /> {$this->lang->words['cp_manageforums']}</a>
				</td>
				<td>
					<a href='{$this->settings['_base_url']}app=members&amp;module=bulkmail&amp;section=bulkmail&amp;do=bulk_mail' title='{$this->lang->words['cp_bulkmailer']}'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/email_add.png' border='0' alt='{$this->lang->words['cp_bulkmailer']}' /> {$this->lang->words['cp_bulkmailer']}</a>
				</td>
				<td>
					<a href='{$this->settings['_base_url']}app=core&amp;module=posts&amp;section=emoticons' title='{$this->lang->words['cp_emoticonmanager']}'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/emoticon_grin.png' border='0' alt='{$this->lang->words['cp_emoticonmanager']}' /> {$this->lang->words['cp_emoticonmanager']}</a>
				</td>
			</tr>
		</table>
	</div>
	
	<br />
	
	<div id='search_and_stats'>
		<div id='quick_search' class='acp-box'>
			<h3>{$this->lang->words['cp_quicksearch']}</h3>
			<table width='100%' cellpadding='0' cellspacing='0' border='0' class='alternate_rows'>
				<tr>
					<td style='width: 40%'>{$this->lang->words['cp_find']} <strong>{$this->lang->words['cp_members']}</strong>:</td>
					<td style='width: 60%'>
						<form action='{$this->settings['_base_url']}&amp;app=members&amp;module=members&amp;section=members&amp;do=members_list&amp;__update=1&amp;f_member_contains_type=contains&amp;f_member_contains=members_display_name' method='post'><input type='text' size='20' class='textinput' id='members_display_name' name='f_member_contains_text' value='' /> <input type='submit' value='{$this->lang->words['cp_go']}' class='realbutton' onclick='return ACPHomepage.editMember()' /></form>
					</td>
				</tr>
				<tr>
					<td>{$this->lang->words['cp_find']} <strong>{$this->lang->words['cp_systemsettings']}</strong>:</td>
					<td>
						<form name='settingform' id='settingform' action='{$this->settings['_base_url']}&amp;app=core&amp;module=tools&amp;section=settings&amp;do=setting_view' method='post'><input type='text' size='20' class='textinput' name='search' value='' /> <input type='submit' value='{$this->lang->words['cp_go']}' class='realbutton' /></form>
					</td>
				</tr>
				<tr>
					<td>{$this->lang->words['cp_find']} <strong>{$this->lang->words['cp_ipaddresses']}</strong>:</td>
					<td>
						<form name='ipform' id='ipform' action='{$this->settings['_base_url']}&amp;app=members&amp;module=members&amp;section=tools&amp;do=learn_ip' method='post'><input type='text' size='20' class='textinput' name='ip' value='' /> <input type='submit' value='{$this->lang->words['cp_go']}' class='realbutton' /></form>
					</td>
				</tr>
				<tr>
					<td>{$this->lang->words['cp_edit']} <strong>{$this->lang->words['cp_usergroup']}</strong>:</td>
					<td>
						<form name='newmem' id='newmem' action='{$this->settings['base_url']}&amp;app=members&amp;module=groups&amp;section=groups&amp;do=edit' method='post'><select name='id'>{$groups}</select> <input type='submit' value='{$this->lang->words['cp_go']}' class='realbutton' /></form>
					</td>
				</tr>
			</table>
		</div>
		
		<div id='stats_overview' class='acp-box'>
			<h3>{$this->lang->words['cp_systemstats']}</h3>
			<table width='100%' cellpadding='0' cellspacing='0' class='alternate_rows'>
				{$content['stats']}
			</table>
		</div>
	</div>
	
	<br />
	
	<div id='notes_and_news'>
		
		<div id='admin_notes' class='acp-box'>
			<h3>{$this->lang->words['cp_adminnotes']}</h3>
			<form action='{$this->settings['base_url']}&amp;app=core&amp;module=mycp&amp;section=dashboard&amp;save=1' method='post'>
				<table width='100%' cellpadding='0' cellspacing='0' border='0'>
					{$content['ad_notes']}
				</table>
			</form>
		</div>
	
		<!-- Version Check -->
		<div id='ips_update_wrapper' style='display:none'>
			<!-- Security Update -->
			<div id='ips_update_security' class='acp-box' style='display:none'>
				<h3>{$this->lang->words['cp_securityupdate']}</h3>
				<p style='text-align:center'>
					{$this->lang->words['cp_version']} <strong><span id='acp-version-security'></span></strong> {$this->lang->words['cp_securityupdate']}!
					<br />
					<input type='button' onclick='upgradeMoreInfo()' class='button' value='{$this->lang->words['cp_moreinformation']}' />
					<input type='button' onclick='resetContinue()' class='button' value='{$this->lang->words['cp_resetwarning']}' />
				</p>
			</div>
			<!-- Normal Version Upgrade -->
			<div id='ips_update_update' class='acp-box' style='display:none'>
				<h3>{$this->lang->words['cp_newversion']}</h3>
				<p style='text-align:center'>
					{$this->lang->words['cp_version']} <strong><span id='acp-version-update'></span></strong> {$this->lang->words['cp_updateavailable']}!
					<br />
					<input type='button' onclick='upgradeMoreInfo()' class='button' value='{$this->lang->words['cp_moreinformation']}' />
				</p>
			</div>
			<!-- Normal Version Upgrade -->
			<div id='ips_update_normal' class='acp-box' style='display:none'>
				<h3>{$this->lang->words['cp_newversion']}</h3>
				<p style='text-align:center'>
					{$this->lang->words['cp_version']} <strong><span id='acp-version-normal'></span></strong> {$this->lang->words['cp_availablenow']}!
					<br />
					<input type='button' onclick='upgradeMoreInfo()' class='button' value='{$this->lang->words['cp_moreinformation']}' />
				</p>
			</div>
		</div>
		
		<!--IPS WIDGETS-->
		<div id='ips_news' class='acp-box'>
			<h3>{$this->lang->words['cp_ipslatestnews']}</h3>
			<div id='ips_news_content'></div>
		</div>
	</div>
	
	<div id='blog_and_bulletin'>
		<div id='ips_blog' class='acp-box'>
			<h3>{$this->lang->words['cp_ipsblogs']}</h3>
			<div id='ips_blog_content'></div>
		</div>
		
		<div id='ips_supportbox' class='acp-box'>
			<h3>{$this->lang->words['cp_ipsbulletin']}</h3>
			<p id='ips_supportbox_content'></p>
		</div>
		<!--IPS WIDGETS-->
	</div>
	
	<div id='admin_boxes'>
		<div id='active_admins' class='acp-box'>
			<h3>{$this->lang->words['cp_activeadmins']}</h3>
			{$content['acp_online']}
		</div>
		<!--acplogins-->
	</div>
</div>

<br />
<script type="text/javascript" src='{$this->settings['js_app_url']}acp.homepage.js'></script>

<!-- HIDDEN "INFORMATION" DIV -->
<div id='acp-update-info-wrapper' style='display:none'>
	<h3>{$this->lang->words['cp_noticeupdate']}</h3>
	<div class='acp-box'>
		<p style='text-align: center;padding:6px;padding-top:24px'>
			{$this->lang->words['cp_update_info']}
			<br />
			<br />
			<input type='button' value='{$this->lang->words['cp_visitcc']}' onclick='upgradeContinue()' class='button' />
		</p>
	</div>
</div>
<!-- / HIDDEN "INFORMATION" DIV -->


<script type='text/javascript'>
function upgradeMoreInfo()
{
	curPop = new ipb.Popup( 'acpVersionInfo', {
							type: 'pane',
							modal: true,
							initial: $('acp-update-info-wrapper').innerHTML,
							hideAtStart: false,
							w: '400px',
							h: '150px'
						});
						
	return false;
}

function upgradeContinue()
{
	acp.openWindow( IPSSERVER_download_link, 800, 600 );
}

/* Warning CONTINUE / CANCEL */
function resetContinue()
{
	if ( confirm( "{$this->lang->words['cp_wannareset']}" ) )
	{
		acp.redirect( ipb.vars['base_url'] + "&amp;app=core&amp;module=mycp&amp;section=dashboard&amp;reset_security_flag=1&amp;new_build=" + IPSSERVER_download_ve + "&amp;new_reason=" + IPSSERVER_download_vt, 1 );
	}
}


/* Set up global vars */
var _newsFeed     = null;
var _blogFeed     = null;
var _versionCheck = null;
var _keithFeed    = null;
/* ---------------------- */
/* ONLOAD: IPS widgets    */
/* ---------------------- */

function onload_ips_widgets()
{
	var head = $$('head')[0];
	
	/* Grab files */
	head.insert( new Element('script', { src: "{$urls['news']}", 'type': 'text/javascript' } ) );
	head.insert( new Element('script', { src: "{$urls['blogs']}", 'type': 'text/javascript' } ) );
	head.insert( new Element('script', { src: "{$urls['version_check']}", 'type': 'text/javascript' } ) );
	head.insert( new Element('script', { src: "{$urls['keiths_bits']}", 'type': 'text/javascript' } ) );
	
	/* ---------------------- */
	/* Feeds                  */
	/* ---------------------- */
	
	_newsFeed = setTimeout( '_newsFeedFunction()', 1000 );
	_blogFeed = setTimeout( '_blogFeedFunction()', 1000 );
	
	/* ---------------------- */
	/* Update boxes           */
	/* ---------------------- */
	
	_versionCheck = setTimeout( '_versionCheckFunction()', 1000 );
	
	/* ---------------------- */
	/* Load Keith             */
	/* ---------------------- */
	
	_keithFeed = setTimeout( '_keithFeedFunction()', 1000 );
}

/* ---------------------- */
/* Keith Feed YumYum      */
/* ---------------------- */

function _keithFeedFunction()
{
	if ( typeof( IPS_KEITH_CONTENT ) != 'undefined' )
	{
		clearTimeout( _keithFeed );
		
		if ( IPS_KEITH_CONTENT && IPS_KEITH_CONTENT != 'none' )
		{
			/* Show version numbers */
			$( 'ips_supportbox_content' ).innerHTML = IPS_KEITH_CONTENT.replace( /&#0039;/g, "'" );
		}
	}
	else
	{
		_keithFeed = setTimeout( '_keithFeedFunction()', 1000 );
	}
}

/* ---------------------- */
/* Version Check          */
/* ---------------------- */

function _versionCheckFunction()
{
	if ( typeof( IPSSERVER_update_type ) != 'undefined' )
	{
		clearTimeout( _versionCheck );
		
		if ( IPSSERVER_update_type && IPSSERVER_update_type != 'none' )
		{
			var _show = '';
			var _text = '';

			switch( IPSSERVER_update_type )
			{
				case 'security':
					_show = 'ips_update_security';
					_text = 'acp-version-security';
				break;
				case 'update':
					_show = 'ips_update_update';
					_text = 'acp-version-update';
				break;
				case 'normal':
					_show = 'ips_update_normal'
					_text = 'acp-version-normal';
				break;
			}
			$( _show ).style.display                = '';
			$( 'ips_update_wrapper' ).style.display = '';

			/* Show version numbers */
			$( _text ).innerHTML = IPSSERVER_download_vh;
		}
	}
	else
	{
		_versionCheck = setTimeout( '_versionCheckFunction()', 1000 );
	}
}

/* ---------------------- */
/* BLOG FEED              */
/* ---------------------- */

function _blogFeedFunction()
{
	if ( typeof( ipsBlogFeed ) != 'undefined' )
	{
		clearTimeout( _blogFeed );
	
		eval( ipsBlogFeed );
		var finalString = '';
		var _len        = ipsBlogFeed['items'].length;
	
		if( typeof( ipsBlogFeed['error'] ) == 'undefined' )
		{
			for( i = 0; i < _len; i++ )
			{
				var _style   = ( i + 1 < _len ) ? 'padding:2px;border-bottom:1px dotted black' : 'padding:2px';
				var _title   = ( ipsBlogFeed['items'][i]['title'].length > 50 ) ? ipsBlogFeed['items'][i]['title'].substr( 0, 47 ) + '...' : ipsBlogFeed['items'][i]['title'];
				finalString += "<div style='" + _style + "'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/ipsnews_item.gif' border='0' /> <a href='" + ipsBlogFeed['items'][i]['link'] + "' target='_blank' style='text-decoration:none'title='" + ipsBlogFeed['items'][i]['title'] + "'>" + _title + "</a></div>\\n";
			}
		}
	
		if ( finalString )
		{
			$( 'ips_blog_content' ).innerHTML = finalString;
		}
		else
		{
			$( 'ips_blog' ).style.display = 'none';
		}
	}
	else
	{
		_blogFeed = setTimeout( '_blogFeedFunction()', 1000 );
	}
}

/* ---------------------- */
/* NEWS FEED              */
/* ---------------------- */

function _newsFeedFunction()
{
	if ( typeof( ipsNewsFeed ) != 'undefined' )
	{
		clearTimeout( _newsFeed );
		
		eval( ipsNewsFeed );
		var finalString = '';
		var _len        = ipsNewsFeed['items'].length;

		if( typeof( ipsNewsFeed['error'] ) == 'undefined' )
		{
			for( i = 0; i < _len; i++ )
			{
				var _style   = ( i + 1 < _len ) ? 'padding:2px;border-bottom:1px dotted black' : 'padding:2px';
				var _title   = ( ipsNewsFeed['items'][i]['title'].length > 50 ) ? ipsNewsFeed['items'][i]['title'].substr( 0, 47 ) + '...' : ipsNewsFeed['items'][i]['title'];
				finalString += "<div style='" + _style + "'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/ipsnews_item.gif' border='0' /> <a href='" + ipsNewsFeed['items'][i]['link'] + "' target='_blank' style='text-decoration:none' title='" + ipsNewsFeed['items'][i]['title'] + "'>" + _title + "</a></div>\\n";
			}
		}
		
		if ( finalString )
		{
			$( 'ips_news_content' ).innerHTML = finalString;
		}
		else
		{
			$( 'ips_news' ).style.display = 'none';
		}
	}
	else
	{
		_newsFeed = setTimeout( '_newsFeedFunction()', 1000 );
	}
}


/* Set up onload event */
Event.observe( window, 'load', onload_ips_widgets );
//]]>
</script>
EOF;

//--endhtml--//
return $IPBHTML;
}


/**
 * Wrapper for validating users
 *
 * @access	public
 * @param	string		Content
 * @return	string		HTML
 */
public function acp_validating_wrapper($content) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='dashboard_border'>
	<div class='dashboard_header'>{$this->lang->words['cp_adminvalidationqueue']}</div>
	{$content}
	<div align='right'>
	   <a href='{$this->settings['base_url']}&amp;app=members&amp;module=members&amp;section=tools&amp;do=validating' style='text-decoration:none'>{$this->lang->words['cp_more']} &raquo;</a>
	 </div>
</div>
<br />
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Validating users row
 *
 * @access	public
 * @param	array 		Data
 * @return	string		HTML
 */
public function acp_validating_block( $data ) {

$IPBHTML = "";
//--starthtml--//

$data['url']	= $this->registry->output->buildSEOUrl( $this->settings['board_url'] . '/index.php?showuser=' . $data['member_id'], 'none', $data['members_seo_name'], 'showuser' );

$IPBHTML .= <<<EOF
<div class='dashboard_sub_row_alt'>
 <div style='float:right;'>
  <a href='{$this->settings['_base_url']}&amp;app=members&amp;module=members&amp;section=tools&amp;do=domod&amp;_admin_auth_key={$this->registry->getClass('adminFunctions')->_admin_auth_key}&amp;mid_{$data['member_id']}=1&amp;type=approve'><img src='{$this->settings['skin_acp_url']}/images/aff_tick.png' alt='{$this->lang->words['cp_yes']}' class='ipd' /></a>&nbsp;
  <a href='{$this->settings['_base_url']}&amp;app=members&amp;module=members&amp;section=tools&amp;do=domod&amp;_admin_auth_key={$this->registry->getClass('adminFunctions')->_admin_auth_key}&amp;mid_{$data['member_id']}=1&amp;type=delete'><img src='{$this->settings['skin_acp_url']}/images/aff_cross.png' alt='{$this->lang->words['cp_no']}' class='ipd' /></a>
 </div>
 <div>
  <strong><a href='{$data['url']}' target='_blank'>{$data['members_display_name']}</a></strong>{$data['_coppa']}<br />
  &nbsp;&nbsp;{$data['email']}</a><br />
  <div class='desctext'>&nbsp;&nbsp;{$this->lang->words['cp_ip']}: <a href='{$this->settings['base_url']}&amp;app=members&amp;module=members&amp;section=toolsdo=learn_ip&amp;ip={$data['ip_address']}'>{$data['ip_address']}</a></div>
  <div class='desctext'>&nbsp;&nbsp;{$this->lang->words['cp_registered']} {$data['_entry']}</div>
 </div>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show warning that converter is still present
 *
 * @access	public
 * @param	int			Converter flag
 * @return	string		HTML
 */
public function warning_converter( $converter ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
	{$this->lang->words['cp_warning_converter']}
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show the ACP notes block
 *
 * @access	public
 * @param	string		Current notes
 * @return	string		HTML
 */
public function acp_notes($notes) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
	<td class='notes acp-row-on'>
		<textarea name='notes' class="dashboard_notes" rows='8' cols='25'>{$notes}</textarea>
	</td>
</tr>
<tr>
	<td class='acp-actionbar'>
		<input type='submit' value='{$this->lang->words['cp_savenotes']}' class='realbutton' />
	</td>
</tr>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show a latest login record
 *
 * @access	public
 * @param	array 		Record
 * @return	string		HTML
 */
public function acp_last_logins_row( $r ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
	<td width='1' valign='middle'>
		<img src='{$this->settings['skin_acp_url']}/images/{$r['_admin_img']}' border='0' alt='-' class='ipd' />
	</td>
 	<td class=''>
		<strong>{$r['admin_username']}</strong>
		<div class='desctext'>
			{$r['_admin_time']}
		</div>
 	</td>
 	<td class=''>
 		<a href='#' onclick="return acp.openWindow('{$this->settings['base_url']}module=system&amp;section=loginlog&amp;do=view_detail&amp;detail={$r['admin_id']}', 400, 400)" title='View Details'><img src='{$this->settings['skin_acp_url']}/images/folder_components/index/view.png' border='0' alt='-' class='ipd' title='{$this->lang->words['cp_view']}' /></a>
    </td>
</tr>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Wrapper for latest ACP logins
 *
 * @access	public
 * @param	string		Content
 * @return	string		HTML
 */
public function acp_last_logins_wrapper($content) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div id='admin_logins' class="acp-box">
    <h3>{$this->lang->words['cp_latestadminlogins']}</h3>
	<table cellspacing='0' cellpadding='0' border='0' width='100%'>
		{$content}
	</table>
	<div class="more">
		<a href='{$this->settings['base_url']}&amp;app=core&amp;module=system&amp;section=loginlog' style='text-decoration:none'>{$this->lang->words['cp_more']} &raquo;</a>
	</div>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}


/**
 * Show the admins online record
 *
 * @access	public
 * @param	array 		Record
 * @return	string		HTML
 */
public function acp_onlineadmin_row( $r ) {

$IPBHTML = "";
//--starthtml--//

$r['url']	= $this->registry->output->buildSEOUrl( $this->settings['board_url'] . '/index.php?showuser=' . $r['session_member_id'], 'none', $r['members_seo_name'], 'showuser' );

$IPBHTML .= <<<EOF
<tr>
    <td class=''>
    	<strong style='font-size:12px'><a href='{$r['url']}' target='_blank'>{$r['members_display_name']}</a></strong>
    	<div class='desctext'>{$r['session_location']} {$this->lang->words['cp_from']} {$r['session_ip_address']}</div>
    </td> 
	<td class=''>
	 	<img src='{$r['pp_thumb_photo']}' width='{$r['pp_thumb_width']}' height='{$r['pp_thumb_height']}' />
 	</td>
</tr>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Admins online wrapper
 *
 * @access	public
 * @param	string		Content
 * @return	string		HTML
 */
public function acp_onlineadmin_wrapper($content) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
 <table width='100%' cellpadding='4' cellspacing='0'>
  {$content}
 </table>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show latest actions record
 *
 * @access	public
 * @param	array 		Record
 * @return	string		HTML
 */
public function acp_lastactions_row( $rowb ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
 <td class='tablerow1' width='1' valign='middle'>
	<img src='{$this->settings['skin_acp_url']}/images/folder_components/index/user.png' border='0' alt='-' class='ipd' />
 </td>
 <td class='tablerow1'>
	 <b>{$rowb['members_display_name']}</b>
	<div class='desctext'>{$this->lang->words['cp_ip']}: {$rowb['ip_address']}</div>
</td>
 <td class='tablerow2'>{$rowb['_ctime']}</td>
</tr>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Latest actions wrapper
 *
 * @access	public
 * @param	string		Content
 * @return	string		HTML
 */
public function acp_lastactions_wrapper($content) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='tableborder'>
 <div class='tableheaderalt'>{$this->lang->words['cp_lastacpactions']}</div>
 <table width='100%' cellpadding='4' cellspacing='0'>
 <tr>
  <td class='tablesubheader' width='1%'>&nbsp;</td>
  <td class='tablesubheader' width='44'>{$this->lang->words['cp_membername']}</td>
  <td class='tablesubheader' width='55%'>{$this->lang->words['cp_timeofaction']}</td>
 </tr>
 $content
 </table>
 <div class='tablefooter' align='right'>
   <a href='{$this->settings['base_url']}&amp;app=core&amp;module=logs&amp;section=adminlogs' style='text-decoration:none'>{$this->lang->words['cp_more']} &raquo;</a>
 </div>
</div>

EOF;

//--endhtml--//
return $IPBHTML;
}


/**
 * ACP Statistics wrapper
 *
 * @access	public
 * @param	array		Content
 * @return	string		HTML
 */
public function acp_stats_wrapper($content) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
	<tr>
		<td>
			<strong>{$this->lang->words['cp_totalmembers']}</strong>
			<div class='sub desctext'>
				{$this->lang->words['cp_onlinenow']}<br />
				{$this->lang->words['cp_awaitingvalidation']}<br />
				{$this->lang->words['cp_lockedaccounts']}<br />
				{$this->lang->words['cp_coppaaccounts']}<br />
				{$this->lang->words['cp_spammeraccounts']}
			</div>				
		</td>
		<td>
			{$content['members']}
			<div class='desctext'>
				<a href='{$this->settings['board_url']}/index.php?app=members&amp;section=online&amp;module=online' target='_blank' title='{$this->lang->words['cp_onlinenow_info']}'>{$this->lang->words['cp_view']}</a> ({$content['sessions']})<br />
				<a href='{$this->settings['base_url']}&amp;app=members&amp;module=members&amp;section=tools&amp;do=validating'>{$this->lang->words['cp_manage']}</a> ({$content['validate']})<br />
				<a href='{$this->settings['base_url']}&amp;app=members&amp;module=members&amp;section=tools&amp;do=locked'>{$this->lang->words['cp_manage']}</a> ({$content['locked']})<br />
				<a href='{$this->settings['base_url']}&amp;app=members&amp;module=members&amp;section=tools&amp;do=validating&amp;filter=coppa'>{$this->lang->words['cp_manage']}</a> ({$content['coppa']})<br />
				<a href='{$this->settings['base_url']}&amp;app=members&amp;module=members&amp;section=tools&amp;do=spam'>{$this->lang->words['cp_manage']}</a> ({$content['spammer'][0]})
			</div>
		</td>
	</tr>
	<tr>
		<td>
			<strong>{$this->lang->words['cp_topics']}</strong>
			<div class='sub desctext'>
				{$this->lang->words['cp_awaitingmoderation']}
			</div>
		</td>
		<td>{$content['topics']}<br /><span class='desctext'>{$content['topics_mod']}</span></td>
	</tr>
	<tr>
		<td>
			<strong>{$this->lang->words['cp_posts']}</strong>
			<div class='sub desctext'>
				{$this->lang->words['cp_awaitingmoderation']}
			</div>
		</td>
		<td>{$content['replies']}<br /><span class='desctext'>{$content['posts_mod']}</span></td>
	</tr>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show a warning about the PHP version
 *
 * @access	public
 * @param	string		PHP version
 * @return	string		HTML
 */
public function acp_php_version_warning( $phpversion ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
	{$this->lang->words['cp_php_warning']}
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show a warning box
 *
 * @access	public
 * @param	string		Title
 * @param	string		Content
 * @return	string		HTML
 */
public function warning_box($title, $content) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='warning'>
	<h4><img src='{$this->settings['skin_acp_url']}/_newimages/icons/bullet_error.png' border='0' alt='{$this->lang->words['cp_error']}' /> {$title}</h4>
	{$content}
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show warning that unlocked installer is present
 *
 * @access	public
 * @return	string		HTML
 */
public function warning_unlocked_installer() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
	{$this->lang->words['cp_unlocked_warning']}
EOF;

//--endhtml--//
return $IPBHTML;
}


/**
 * Show unfinished upgrade warning
 *
 * @access	public
 * @return	string		HTML
 */
public function warning_upgrade() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
	{$this->lang->words['cp_upgrade_warning']}
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show locked installer present warning
 *
 * @access	public
 * @return	string		HTML
 */
public function warning_installer() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
	{$this->lang->words['cp_installer_warning']}
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show a warning that an emergency skin rebuild has occurred
 *
 * @access	public
 * @return	string		HTML
 * @deprecated	Don't think this is done/called anymore
 */
public function warning_rebuild_emergency() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
	{$this->lang->words['cp_emergency']}
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show a warning that the rebuild following the upgrade hasn't been completed
 *
 * @access	public
 * @return	string		HTML
 */
public function warning_rebuild_upgrade() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
   {$this->lang->words['cp_warning_rebuild']}
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show form to change details
 *
 * @access	public
 * @return	string		HTML
 */
public function showChangeForm() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<form id='mainform' action='{$this->settings['base_url']}&amp;module=mycp&amp;section=details&amp;do=save' method='post'>
	<div class='acp-box'>
 		<h3>{$this->lang->words['mycp_change_details']}</h3>
		
 		<ul class='acp-form'>
 			<li class='head'><label>{$this->lang->words['change_email_details']}</label></li>
			<li class='acp-row-on'>
				<label>{$this->lang->words['change__email']}</label>
				<input class='textinput' type='text' name='email' size='30' />
			</li>
			<li class='acp-row-off'>
				<label>{$this->lang->words['change__email_confirm']}</label>
				<input class='textinput' type='text' name='email_confirm' size='30' />
			</li>
			
			<li class='head'><label>{$this->lang->words['change_pass_details']}</label></li>
			<li class='acp-row-on'>
				<label>{$this->lang->words['change__pass']}<span class='desctext'>{$this->lang->words['pw_will_logout']}</span></label>
				<input class='textinput' type='password' name='password' size='30' />
			</li>
			<li class='acp-row-off'>
				<label>{$this->lang->words['change__pass_confirm']}</label>
				<input class='textinput' type='password' name='password_confirm' size='30' />
			</li>
		</ul>

		<div class='acp-actionbar'>
			<div class='centeraction'>
				<input type='submit' value=' {$this->lang->words['change__confirm']} ' class='button primary' />
			</div>
		</div>
	</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}
}