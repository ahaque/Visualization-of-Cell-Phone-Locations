<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Diagnostics skin file
 * Last Updated: $Date: 2009-08-17 20:55:28 -0400 (Mon, 17 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 5022 $
 */
 
class cp_skin_diagnostics extends output
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
 * DB Index checker
 *
 * @access	public
 * @param	array 		Errors
 * @param	array 		Tables
 * @return	string		HTML
 */
public function indexChecker( $errors=array(), $tables=array() ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['d_ititle']}</h2>
</div>
HTML;

if( count( $errors ) )
{
$IPBHTML .= <<<HTML
<div class='acp-box alternate_rows'>
	<h3>{$app}</h3>
	<table width='100%'>
		<th>{$this->lang->words['d_ierrors']}</th>
		<tr>
			<td><span class='rss-feed-invalid'>{$this->lang->words['d_ifixall']}<a href='{$this->settings['base_url']}{$this->form_code}&amp;section=diagnostics&amp;do=dbindex&amp;fix=all'>{$this->lang->words['d_ifixall_link']}</a></span></td>
		</tr>
	</table>
</div><br />
HTML;
}

$IPBHTML .= <<<HTML
<div class='acp-box alternate_rows'>
	<h3>{$this->lang->words['d_dnav']}</h3>

	<table width='100%'>
		<tr>
			<th>{$this->lang->words['chckr_table']}</th>
			<th>{$this->lang->words['chckr_status']}</th>
			<th>{$this->lang->words['chckr_fix']}</th>
		</tr>
HTML;

if( is_array( $tables ) && count( $tables ) )
{
	$i = 0;
	foreach( $tables as $app_title => $_tables )
	{
$IPBHTML .= <<<HTML
		<tr>
			<th colspan='3'>{$app_title}</th>
		</tr>
HTML;
		foreach( $_tables as $r )
		{
			if( $r['status'] == 'ok' )
			{
$IPBHTML .= <<<HTML
		<tr>
			<td><span style='color:green'>{$r['table']}</span></td>
			<td>
				<span style='color:green'>
				<ul class='bullets'>
					<li>
HTML;

$IPBHTML .= implode( "</li><li>", $r['index'] );

$IPBHTML .= <<<HTML
					</li>
				</ul>
				</span>
			</td>
			<td>&nbsp;</td>
		</tr>
HTML;
			}
			else
			{
$IPBHTML .= <<<HTML
		<tr>
			<td><span style='color:red'>{$r['table']}</span></td>
			<td>
				<ul class='bullets'>
HTML;

foreach( $r['index'] as $index )
{
	if( in_array( $index, $r['missing'] ) )
	{
		$IPBHTML .= "<li><span style='color:red'>Missing index: {$index}</span></li>";
	}
	else
	{
		$IPBHTML .= "<li><span style='color:green'>{$index}</li>";
	}
}
$IPBHTML .= <<<HTML
				</ul>
			</td>
			<td>
				<a href='{$this->settings['base_url']}{$this->form_code}&amp;section=diagnostics&amp;do=dbindex&amp;fix={$r['table']}'>{$this->lang->words['d_inauto']}</a>{$this->lang->words['d_iman']}
				<div>
					<ul class='bullets'>
						<li>
HTML;

$IPBHTML .= implode( "</li><li>", $r['fixsql'] );
$IPBHTML .= <<<HTML
						</li>
					</ul>
				</div>
			</td>
		</tr>
HTML;
			}
		}
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
 * DB checker
 *
 * @access	public
 * @param	array 		Errors
 * @param	array 		Tables
 * @return	string		HTML
 */
public function dbChecker( $errors=array(), $tables=array() ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['d_dtitle']}</h2>
</div>
HTML;

if( count( $errors ) )
{
$IPBHTML .= <<<HTML
<div class='acp-box alternate_rows'>
	<h3>{$app}</h3>
	<table width='100%'>
		<th>{$this->lang->words['d_ierrors']}</th>
		<tr>
			<td><span class='rss-feed-invalid'>{$this->lang->words['d_ifixall']}<a href='{$this->settings['base_url']}{$this->form_code}&amp;section=diagnostics&amp;do=dbchecker&amp;fix=all'>{$this->lang->words['d_ifixall_link']}</a></span></td>
		</tr>
	</table>
</div><br />
HTML;
}

$IPBHTML .= <<<HTML
<div class='acp-box alternate_rows'>
	<h3>{$this->lang->words['d_dnav']}</h3>

	<table width='100%'>
		<tr>
			<th>{$this->lang->words['d_dtbl']}</th>
			<th>{$this->lang->words['d_dstatus']}</th>
			<th>{$this->lang->words['d_dfix']}</th>
		</tr>
HTML;

if( count( $tables ) )
{
	$i = 0;
	foreach( $tables as $app_title => $_tables )
	{
$IPBHTML .= <<<HTML
		<tr>
			<th colspan='3'>{$app_title}</th>
		</tr>
HTML;
		if( !is_array($_tables) OR !count($_tables) )
		{
			continue;
		}
		
		foreach( $_tables as $r )
		{
			if( $r['status'] == 'ok' )
			{
$IPBHTML .= <<<HTML
		<tr>
			<td><span style='color:green'>{$r['table']}</span></td>
			<td><img src='{$this->settings['skin_acp_url']}/images/aff_tick.png' border='0' alt='YN' class='ipd' /></td>
			<td>&nbsp;</td>
		</tr>
HTML;
			}
			else
			{
$IPBHTML .= <<<HTML
		<tr>
			<td><span style='color:red'>{$r['table']}</span></td>
			<td><img src='{$this->settings['skin_acp_url']}/images/aff_cross.png' border='0' alt='YN' class='ipd' /></td>
			<td>
				<a href='{$this->settings['base_url']}{$this->form_code}&amp;section=diagnostics&amp;do=dbchecker&amp;fix={$r['key']}'>{$this->lang->words['d_iauto']}</a>{$this->lang->words['d_iman']}
				<div>
					<ul class='bullets'>
						<li>
HTML;

$IPBHTML .= implode( "</li><li>", $r['fixsql'] );
$IPBHTML .= <<<HTML
						</li>
					</ul>
				</div>
			</td>
		</tr>
HTML;
			}
		}
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
 * Version history record
 *
 * @access	public
 * @param	array 		Version info
 * @return	string		HTML
 */
public function acp_version_history_row( $r ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<tr>
 <td class='tablerow1'>{$r['upgrade_version_human']} ({$r['upgrade_version_id']})</td>
 <td class='tablerow2'>{$r['_date']}</td>
</tr>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Version history wrapper
 *
 * @access	public
 * @param	string		Content
 * @return	string		HTML
 */
public function acp_version_history_wrapper($content) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<a name='versions'></a>
<div class='tableborder'>
 <div class='tableheaderalt'>{$this->lang->words['last_5_version_history']}</div>
 <table width='100%' cellpadding='4' cellspacing='0'>
 {$content}
 </table>
</div>
<br />
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Diagnostics overview
 *
 * @access	public
 * @param	array 		Data
 * @return	string		HTML
 */
public function diagnosticsOverview( $data=array() ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['d_atitle']}</h2>
</div>

<div class="acp-box">
	<h3>{$this->lang->words['sys_system_overview']}</h3>
	<table class='alternate_rows' width='100%'>
		<tr>
			<td><strong>{$this->lang->words['sys_ipboard_version']}</strong></td>
			<td>{$data['version']} (ID:{$data['version_full']})</td>
		</tr>
		<tr>
			<td><strong>{$data['driver_type']} {$this->lang->words['sys_version']}</strong></td>
			<td>{$data['version_sql']}</td>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['sys_php_version']}</strong></td>
			<td>{$data['version_php']}</td>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['sys_disabled_funcs']}</strong></td>
			<td>{$data['disabled']}</td>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['sys_loaded_ext']}</strong></td>
			<td>{$data['extensions']}</td>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['sys_safe_mod']}</strong></td>
			<td>{$data['safe_mode']}</td>
		</tr>
EOF;
	if ( defined( 'IPS_TOPICMARKERS_DEBUG' ) and IPS_TOPICMARKERS_DEBUG === TRUE )
	{
		$IPBHTML .= <<<EOF
		<tr>
			<td><strong>Topic Marking Debug</strong></td>
			<td>ON ( <a href='{$this->settings['base_url']}{$this->form_code}&amp;do=tm_index'>View Logs</a> )</td>
		</tr>
EOF;
	}

$IPBHTML .= <<<EOF
		<tr>
			<td><strong>{$this->lang->words['sys_sys_software']}</strong></td>
			<td>{$data['server']}</td>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['sys_current_load']}</strong></td>
			<td>{$data['load']}</td>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['sys_total_mem']}</strong></td>
			<td>{$data['total_memory']}</td>
		</tr>
		<tr>
			<td><strong>{$this->lang->words['sys_avail_mem']}</strong></td>
			<td>{$data['avail_memory']}</td>
		</tr>
	</table>
</div>
<br />
<div class="tableborder">
	<div class="tableheaderalt">{$this->lang->words['system_processes']}</div>
	<table cellpadding='0' cellspacing='0' width='100%'>
		<tr>
			<td class='tablerow1' width='100%'>{$data['tasks']}</td>
		</tr>
	</table>
</div>
<br />
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Version checker
 *
 * @access	public
 * @param	array 		Versions
 * @param	string		History info
 * @param	array 		Results
 * @return	string		HTML
 */
public function versionCheckerResults( $versions=array(), $history='', $results=array() ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['d_vtitle']}</h2>
</div>

<div class="acp-box">
	<h3>{$this->lang->words['d_vinfo']}</h3>
	<table class='alternate_rows' width='100%'>
		<tr>
			<td class='tablerow1' width='30%'>{$this->lang->words['sys_ipboard_version']}</td>
			<td class='tablerow2' width='70%'>{$versions['version']} (ID:{$versions['version_full']})</td>
		</tr>
EOF;

if( !$history )
{
	$IPBHTML .= <<<EOF
		<tr>
			<td class='tablerow1' width='30%'>Upgrade History</td>
			<td class='tablerow2' width='70%'><i>None Available</i></td>
		</tr>
EOF;
}
else
{
	$IPBHTML .= $history;
}

$IPBHTML .= <<<EOF
	</table>
</div>
<br />
<div class="acp-box">
	<h3>{$this->lang->words['d_vnav']}</h3>
	<table class='alternate_rows' width='100%'>
EOF;

if( count($results) AND is_array($results) )
{
	foreach( $results as $file => $version )
	{
		if( trim($version) == $versions['version'] )
		{
			$version = "<span style='color:green;'>{$version}</span>";
		}
		else
		{
			$version = "<span style='font-weight:bold; color:red;'>{$version}</span>";
		}

		$IPBHTML .= <<<EOF
			<tr>
				<td width="90%">{$file}</td>
				<td>{$version}</td>
			</tr>
EOF;
	}
}

$IPBHTML .= <<<EOF
	</table>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}


/**
 * Permission checker results
 *
 * @access	public
 * @param	array 		Results from permission checking
 * @return	string		HTML
 */
public function permissionsResults( $results=array() ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['d_ptitle']}</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['perm_check_results']}</h3>
	<table class='alternate_rows'>
EOF;

if( count($results) AND is_array($results) )
{
	foreach( $results as $result )
	{
		$IPBHTML .= <<<EOF
			<tr>
				<td>{$result}</td>
			</tr>
EOF;
	}
}

$IPBHTML .= <<<EOF
	</table>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Whitespace checker
 *
 * @access	public
 * @param	array 		Results from checking for whitespace
 * @return	string		HTML
 */
public function whitespaceResults( $results=array() ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['d_wtitle']}</h2>
</div>

<div class="acp-box">
	<h3>{$this->lang->words['d_wnav']}</h3>
	<table class='alternate_rows'>
EOF;

if( count($results) AND is_array($results) )
{
	foreach( $results as $result )
	{
		$IPBHTML .= <<<EOF
			<tr>
				<td>{$result} {$this->lang->words['d_wfound']}</td>
			</tr>
EOF;
	}
}
else
{
	$IPBHTML .= <<<EOF
	<tr>
		<td>{$this->lang->words['d_wclear']}</td>
	</tr>
EOF;
}

$IPBHTML .= <<<EOF
	</table>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * view log entry in pop-up window
 */
function topicMarkers_viewLog( $data ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='acp-box'>
<h3>{$data['marker_message']} - Member ID: {$data['marker_member_id']}</h3>
<table class='alternate_rows' width='100%'>
	<thead>
		<tr>
			<th width='15%'>Key</th>
			<th width='85%'>Value</th>
			</tr>
		</thead>
		<tbody>
		<tr>
			<td><strong>Time</strong></td>
			<td>{$data['_marker_timestamp']}</td>
	    </tr>
		<tr>
			<td><strong>URL</strong></td>
			<td>{$data['marker_url']}</td>
	    </tr>
		<tr>
			<td><strong>Session</strong></td>
			<td>{$data['marker_session_key']}</td>
	    </tr>
EOF;
	
	foreach( array( 1,2,3,4,5 ) as $n )
	{
		if ( $data['marker_data_' . $n ] )
		{
			$IPBHTML .= <<<EOF
			<tr>
				<td valign='top'><strong>marker_data_{$n}</strong></td>
				<td>{$this->topicMarkers_arrayLoop($data['marker_data_' . $n ])}</td>
		    </tr>
EOF;
		}
	}
	
	foreach( array( 'storage', 'memory', 'freezer' ) as $n )
	{
		if ( $data['marker_data_' . $n ] )
		{
			$IPBHTML .= <<<EOF
			<tr>
				<td valign='top' onclick="$('tm__{$n}').toggle();"><strong><a href='javascript:void(0)' title='Click to hide/show'>marker_data_{$n}</a></strong></td>
				<td id='tm__{$n}'>{$this->topicMarkers_arrayLoop($data['marker_data_' . $n ])}</td>
		    </tr>
EOF;
		}
	}

$IPBHTML .=<<<EOF
		<tbody>
	</table>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Topic marker array loop
 *
 * @access	public
 * @param	array 		Results
 * @return	string		HTML
 */
/**
 * view log entry in pop-up window
 */
function topicMarkers_arrayLoop( $data ) {

$IPBHTML = "";
//--starthtml--//

if ( ! is_array( $data ) AND substr( $data, 0, 2 ) == 'a:' )
{
	$data = unserialize( $data );
}

$this->_indexLoop++;

$IPBHTML .= <<<EOF
<table class='alternate_rows' width='100%'>
	<thead>
		<tr>
			<th width='20%'>Key</th>
			<th width='80%'>Value</th>
			</tr>
		</thead>
		<tbody>
EOF;
	if ( is_array( $data ) AND count( $data ) )
	{
		foreach( $data as $k => $v )
		{
			if ( ! is_array( $v ) AND substr( $v, 0, 2 ) == 'a:' )
			{
				$v = unserialize( $v );
			}
			
			if ( is_array( $v ) and count( $v ) )
			{
				$_key = md5( $k . serialize( $v ) . '__' . $this->_indexLoop );
				
				$IPBHTML .= "
				<tr>
					<td valign='top' onclick=\"$('tmi__{$_key}').toggle();\"><strong><a href='javascript:void(0)' title='Click to hide/show'>{$k}</a></strong></td>
					<td id='tmi__{$_key}'>" . $this->topicMarkers_arrayLoop( $v ) . "</td>
			    </tr>\n";
			}
			else
			{
				if ( is_int( $v ) AND strlen( $v ) == 10 )
				{
					$_t = $this->registry->class_localization->getDate( $v, 'long' );
				}
				
				$IPBHTML .= <<<EOF
					<tr>
						<td valign='top'><strong>{$k}</strong></td>
						<td><span title='$_t'>{$v}</span></td>
				    </tr>
EOF;
			}
		}
	}
$IPBHTML .=<<<EOF
		<tbody>
	</table>
EOF;

//--endhtml--//
return $IPBHTML;
}
/**
 * Topic marker main index
 *
 * @access	public
 * @param	array 		Results
 * @return	string		HTML
 */
public function topicMarkers_membersIndex( $member, $markers, $pages ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
{$pages}
<br style='clear: both' />
<div class='section_title'>
	<h2><img src='{$member['pp_thumb_photo']}' style='width: 30px; height: 30px; border: 1px solid #d8d8d8' /> Topic Markers: {$member['members_display_name']}'s logs</h2>
</div>

<div class='acp-box'>
	<h3>Topic Marker Member Sessions</h3>
	<table class='alternate_rows'>
	<tr>
		<th style='width: 5%'></th>
		<th style='width: 20%'>Message</th>
		<th style='width: 30%'>Time</th>
		<th style='width: 45%'>URL</th>
		<th style='width: 20%'>Options</th>
	</tr>
EOF;

if( count($markers) AND is_array($markers) )
{
	$lastSessionKey = '';
	
	foreach( $markers as $marker )
	{
		$style = ( $lastSessionKey AND $lastSessionKey != $marker['marker_session_key'] ) ? 'border-top:1px dotted #999' : '';
		$time  = $this->registry->class_localization->getDate( $marker['marker_timestamp'], 'long' );
		$url   = str_replace( str_replace( 'http://', '', $this->settings['_original_base_url'] ), '', $marker['marker_url'] );
		$_key  = base64_encode( $marker['marker_microtime'] );
		
		$IPBHTML .= <<<EOF
			<tr style='$style'>
				<td><img src='{$this->settings['skin_acp_url']}/_newimages/icons/disk.png' alt='' /></td>
				<td><strong>{$marker['marker_message']}</strong></td>
				<td>{$time}</td>
				<td><span style='font-size:0.9em;color:gray'>{$url}</span></td>
				<td>
					<img class='ipbmenu' id="menu-{$_key}" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt='{$this->lang->words['a_options']}' />
					<ul class='acp-menu' id='menu-{$_key}_menucontent'>
						<li class='icon view'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=tm_memberindex&amp;member_id={$marker['marker_member_id']}&amp;marker_session_key={$marker['marker_session_key']}'>View this session only</a></li>
						<li class='icon view'><a href='#' onclick="return acp.openWindow('{$this->settings['base_url']}{$this->form_code}&amp;do=tm_viewLog&amp;member_id={$marker['marker_member_id']}&amp;_id={$_key}', 800, 800, 'log-{$_key}');">View full log</a></li>
					</ul>
				</td>
			</tr>
EOF;

		$lastSessionKey = $marker['marker_session_key'];
	}
}

$IPBHTML .= <<<EOF
	</table>
</div>
<br style='clear: both' />
{$pages}
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Topic marker main index
 *
 * @access	public
 * @param	array 		Results
 * @return	string		HTML
 */
public function topicMarkers_index( $results=array() ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>Topic Markers: Index</h2>
</div>

<div class='acp-box'>
	<h3>Topic Marker Member Sessions</h3>
	<table class='alternate_rows'>
EOF;

if( count($results) AND is_array($results) )
{
	foreach( $results as $result )
	{
		$time = $this->registry->class_localization->getDate( $result['max_ts'], 'short' );
		$IPBHTML .= <<<EOF
			<tr>
				<td width='5%'>
					<a href='{$this->settings['base_url']}&amp;module=members&amp;section=members&amp;do=viewmember&amp;member_id={$result['_memberData']['member_id']}'><img src='{$result['_memberData']['pp_thumb_photo']}' style='width: 30px; height: 30px; border: 1px solid #d8d8d8' /></a>
				</td>			
				<td width='40%' class='member_name'>
					<a href='{$this->settings['base_url']}&amp;module=members&amp;section=members&amp;do=viewmember&amp;member_id={$result['_memberData']['member_id']}'>{$result['_memberData']['members_display_name']}</a>
					<div style='font-weight:normal'>{$result['_memberData']['_group_formatted']}</div>
				</td>
				<td width='30%'>Last Hit: {$time}</td>
				<td width='25%' align='right'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=tm_memberindex&amp;member_id={$result['_memberData']['member_id']}'>View Logs ({$result['count']})</a></td>
			</tr>
EOF;
	}
}

$IPBHTML .= <<<EOF
	</table>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * phpinfo() output
 *
 * @access	public
 * @param	string		Content from running phpinfo()
 * @return	string		HTML
 */
public function phpInfo( $content ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<style type='text/css'>
.center {text-align: center;}
.center table { margin-left: auto; margin-right: auto; text-align: left; }
.center th { text-align: center; }
h1 {font-size: 150%;}
h2 {font-size: 125%;}
.p {text-align: left;}
.e {background-color: #ccccff; font-weight: bold;}
.h {background-color: #9999cc; font-weight: bold;}
.v {background-color: #cccccc; white-space: normal;}
</style>

{$content}
EOF;

//--endhtml--//
return $IPBHTML;
}

}