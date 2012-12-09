<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Look and feel skin file
 * Last Updated: $Date: 2009-08-24 20:56:22 -0400 (Mon, 24 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 5041 $
 */
 
class cp_skin_templates extends output
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
 * Show the skin differences result
 *
 * @access	public
 * @param	string 		Differences result
 * @return	string		HTML
 */
public function differenceResult( $difference )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<h3>{$this->lang->words['sk_temp_diff']}</h3>
<div style='background-color: #fff; width: 780px; height: 400px; overflow: auto; padding: 10px;'>
	{$difference}
</div>
<div style='padding: 4px;'><span class='diffred'>{$this->lang->words['sk_removedhtml']}</span> &middot; <span class='diffgreen'>{$this->lang->words['sk_addedhtml']}</span></div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Easy logo changer
 *
 * @access	public
 * @param	string		Warning
 * @param	string		Current URL
 * @param	int			Current id
 * @return	string		HTML
 */
public function easyLogo( $warning, $currentUrl, $currentId )
{
$IPBHTML = "";
//--starthtml--//

$_skin_list		= $this->registry->output->generateSkinDropdown();
array_unshift( $_skin_list, array( 0, $this->lang->words['sm_skinnone'] ) );

$skinList		= ipsRegistry::getClass('output')->formDropdown( "skin", $_skin_list );

$urlField		= $this->registry->output->formInput( 'logo_url', ( isset($_POST['logo_url']) AND $_POST['logo_url'] ) ? htmlspecialchars( $_POST['logo_url'], ENT_QUOTES ) : $currentUrl );
$uploadField	= $this->registry->output->formUpload();

$IPBHTML .= <<<EOF
<script type="text/javascript" src="{$this->settings['js_app_url']}acp.easylogo.js"></script>
<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=finish' method='post' enctype='multipart/form-data'>
<input type='hidden' name='replacementId' id='replacementId' value='{$currentId}' />
<div class='acp-box'>
	<h3>{$this->lang->words['sk_easylogochanger']}</h3>
EOF;

if( $warning )
{
	$IPBHTML .= <<<EOF
	<div class='redbox' style='padding:4px'>{$this->lang->words['sk_elc_warning']}</div>
EOF;
}

$IPBHTML .= <<<EOF
	<ul class="acp-form alternate_rows">
		<li>
			<label>
				{$this->lang->words['sk_applywhichset']}
				<span class="desctext">{$this->lang->words['sk_applywhichset_info']}</span>
			</label>
			{$skinList}
		</li>
		<li>
			<label>
				{$this->lang->words['sk_urlnewlogo']}
				<span class="desctext">{$this->lang->words['sk_urlnewlogo_info']}</span>
			</label>
			{$urlField}
		</li>
		<li>
			<label>
				{$this->lang->words['sk_uploadlogo']}
				<span class="desctext">{$this->lang->words['sk_uploadlogo_info']}</span>
			</label>
			{$uploadField}
		</li>
	</ul>
	<div class="acp-actionbar">
		<input type='submit' value='{$this->lang->words['sk_submit']}' class='realbutton' />
	</div>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}


/**
 * Search and replace list template groups
 *
 * @access	public
 * @param	array 		Template groups
 * @param	array 		Skin set data
 * @param	array 		Session data
 * @return	string		HTML
 */
public function searchandreplace_listTemplateGroups( $templateGroups, $setData, $sessionData ) {

$IPBHTML = "";
//--starthtml--//

$_keys        = array_keys( $templateGroups );
$_json        = json_encode( array( 'groups' => $templateGroups ) );
$_first       = array_shift( $_keys );
$_setData     = json_encode( $setData );
$_sessionData = json_encode( $sessionData );

$IPBHTML .= <<<EOF
<form id='sandrForm' name='sandrForm'>
<div class='acp-box'>
	<h3>{$this->lang->words['sandr_search_results_for']} {$setData['set_name']}</h3>
	<div class='triple_pad' id='tplate_groupList'></div>
EOF;

if ( ! $sessionData['sandr_search_only'] )
{
	$IPBHTML .=<<<EOF
		<div class='acp-actionbar' style='text-align:right'>
			<input type='button' value='{$this->lang->words['sk_replaceselected']}' id='replaceButton' onclick='IPB3TemplatesSandR.performReplacement()' class='button primary' />
		</div>
EOF;
}

$IPBHTML .=<<<EOF
</div>
</form>

<!-- templates -->
<div style='display:none'>
	<div id='tplate_groupRow' class='#{classname}'>
		<div id='groupRow_#{groupName}' class='acp-div-off'>
			<div id='groupRowCbox_#{groupName}' style='float:right;display:none'>
				<input type='checkbox' id='cbox_group_#{groupName}' value='1' name='groups[#{groupName}]' onclick="IPB3TemplatesSandR.toggleGroupBox('#{groupName}')" />
			</div>
			<img src='{$this->settings['skin_acp_url']}/_newimages/icons/folder.png' />
			<span style='font-weight:bold'><a href='javascript:void(0);' onclick="IPB3TemplatesSandR.toggleTemplates('#{groupName}')">#{groupName}</a></span> <span style='font-size:9px'>(#{_matches} matches)</span>
			<div id='groupRowTemplates_#{groupName}' style='display:none'></div>
		</div>
	</div>

	<div id='tplate_templateRow'>
		<div id='tplate_templaterow_#{template_id}' style='padding:10px;margin-left:20px'>
			<div id='templateRowCbox_#{template_id}' style='float:right;display:none'>
				<input type='checkbox' id='cbox_template_#{template_group}_#{template_id}' class='cboxGroup#{template_group}' onclick="IPB3TemplatesSandR.checkGroupBox('#{template_group}')" value='1' name='templates[#{template_id}]' />
			</div>
			<img src='{$this->settings['skin_acp_url']}/_newimages/icons/template.png' class='ipd' />
			<span style='font-weight:bold'><a href='javascript:void(0);' onclick="IPB3TemplatesSandR.loadTemplateEditor('#{template_id}')">#{template_name}</a></span>
		</div>
	</div>

	<div id='tplate_templateEditor'>
		<div id='tplate_editor_#{template_id}' class='tableborder' style='width:500px'>
			<div class='tableheaderalt'>Editing "#{template_name}" in "#{template_group}"</div>
			<div class='tablerow2' style='padding:10px'>
				<input type='text' id='tplate_dataBox_#{template_id}' value='#{template_data}' style='width:100%;' />
				<textarea id='tplate_editBox_#{template_id}' style='width:100%;height:400px'>#{template_content}</textarea>
			</div>
			<div class='tablerow2' style='text-align:right;'>
				<input type='button' value='{$this->lang->words['sk_save']}' onclick="IPB3TemplatesSandR.saveTemplateBit('#{template_id}')" />
				&nbsp;
				<input type='button' value=' {$this->lang->words['sk_close']} ' onclick="IPB3TemplatesSandR.cancelTemplateBit('#{template_id}')" />
			</div>
		</div>
	</div>
</div>
<!-- / templates -->
<script type="text/javascript" src="{$this->settings['js_app_url']}ipb3TemplateSandR.js"></script>
<script type='text/javascript'>
	var IPB3TemplatesSandR             = new IPBTemplateSandR();
	IPB3TemplatesSandR.templateGroups  = $_json;
	IPB3TemplatesSandR.setData         = $_setData;
	IPB3TemplatesSandR.sessionData	   = $_sessionData;
	document.observe("dom:loaded", function(){
		IPB3TemplatesSandR.init();
	} );
</script>
EOF;

//--endhtml--//
return $IPBHTML;
}


/**
 * Form to perform a search and replace
 *
 * @access	public
 * @param	string		Skin options list
 * @param	int			Number of bits
 * @param	int			Number to do per refresh
 * @return	string		HTML
 */
public function searchandreplace_form( $skinOptionList, $numberbits, $pergo ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='information-box'>
 {$this->lang->words['sk_searchreplaceinfo']}
</div>
<br />
<div class='acp-box'>
	<h3>{$this->lang->words['sk_searchandreplace']}</h3>
	<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=start' enctype='multipart/form-data' method='POST'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->_admin_auth_key}' />
	<table class='alternate_rows triple_pad'>
	<tr>
		<td width='30%'>{$this->lang->words['sk_selectskinset']}</td>
		<td width='70%'>
			<select name='setID'>{$skinOptionList}</select>
			<p><input type='checkbox' value='1' name='searchParents' /> {$this->lang->words['sk_searchininfo']}</p>
		</td>
	</tr>
	<tr>
		<td width='30%'>{$this->lang->words['sk_searchfor']}</td>
		<td width='70%'><textarea name='searchFor' id='searchFor' style='height:100px;width:100%'></textarea></td>
	</tr>
	<tr>
		<td width='30%'>{$this->lang->words['sk_replacewith']}<p>{$this->lang->words['sk_replacewith_info']}</p></td>
		<td width='70%'>
			<textarea name='replaceWith' id='replaceWith' style='height:100px;width:100%'></textarea>
			<p><input type='checkbox' value='1' id='isRegex' name='isRegex'> {$this->lang->words['sk_regularexpression']}</p>
		</td>
	</tr>
	<tr>
	</table>
	 <div class='acp-actionbar'>
		<input type='submit' value='{$this->lang->words['sk_continue']}' class='button primary' />
	 </div>
	</form>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Export a skin difference result
 *
 * @access	public
 * @param	array 		Session data
 * @param	array 		Reports
 * @param	array 		Missing parts
 * @param	array 		Changed parts
 * @return	string		HTML
 */
public function skindiff_export( $sessionData, $reports, $missing, $changed ) {

$date = gmdate('r');
$howmany = sprintf( $this->lang->words['sk_howmanytemps'], $missing, $changed );
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<html>
 <head>
  <title>$title {$this->lang->words['sk_title_export']}</title>
  <style type="text/css">
   BODY
   {
   	font-family: verdana;
   	font-size:11px;
   	color: #000;
   	background-color: #CCC;
   }
   
   del,
   .diffred
   {
	   background-color: #D7BBC8;
	   text-decoration:none;
   }
   
   ins,
   .diffgreen
   {
	   background-color: #BBD0C8;
	   text-decoration:none;
   }
   
   h1
   {
   	font-size: 18px;
   }
   
   h2
   {
   	font-size: 18px;
   }
  </style>
 </head>
<body>
  <div style='padding:4px;border:1px solid #000;background-color:#FFF;margin:4px;'>
  <h1>{$sessionData['diff_session_title']} ({$this->lang->words['sk_exported']}: $date)</h1>
  <strong>{$howmany}</strong>
  </div>
  <br />
EOF;
	if ( count( $reports ) )
	{
		foreach( $reports as $group => $key )
		{
			foreach( $reports[ $group ] as $key => $report )
			{
				$report['diff_change_content'] = str_replace( "\n", "<br>", $report['diff_change_content'] );
				$report['diff_change_content'] = str_replace( "&gt;&lt;", "&gt;\n&lt;" ,$report['diff_change_content']);
				$report['diff_change_content'] = preg_replace( "#(?<!(\<del|\<ins)) {1}(?!:style)#i", "&nbsp;" ,$report['diff_change_content']);
				$report['diff_change_content'] = str_replace( "\t", "&nbsp; &nbsp; ", $report['diff_change_content'] );
				
				$prefix = ( ! $report['diff_change_type'] ) ? "[NEW] " : '';
				
				$IPBHTML .= <<<EOF
					<div style='padding:4px;border:1px solid #000;background-color:#FFF;margin:4px;'>
						<h2>{$prefix}{$report['diff_change_func_group']} <span style='color:green'>&gt;</span> {$report['diff_change_func_name']}</h2>
						<hr>
						{$report['diff_change_content']}
					</div>
EOF;
			}
		}
	}

$IPBHTML .= <<<EOF
  <br />
  <div style='padding:4px;border:1px solid #000;background-color:#FFF;margin:4px;'>
   <span class='diffred'>{$this->lang->words['sk_removedhtml']}</span> &middot; <span class='diffgreen'>{$this->lang->words['sk_addedhtml']}</span>
  </div>
</body>
<html>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Overview of completed skin diff reports
 *
 * @access	public
 * @param	array 		Session data
 * @param	array 		Reports
 * @param	array 		Missing bits
 * @param	array 		Changed bits
 * @return	string		HTML
 */
public function skindiff_reportOverview( $sessionData, $reports, $missing, $changed ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='acp-box'>
	<h3>{$this->lang->words['sk_skindiffreport']} {$sessionData['diff_session_title']}</h3>
	<table class='alternate_rows triple_pad'>

EOF;

if ( count( $reports ) )
{
	foreach( $reports as $group => $key )
	{
		/* Group row */
		$IPBHTML .= <<<EOF
		<tr>
		 	<th class='sub' colspan='5'>
		   		<strong>{$group}</strong>
		 	</th>
		</tr>
EOF;
	
		foreach( $reports[ $group ] as $key => $report )
		{
			$_safe   = str_replace( ':', '-', $report['_key'] );
			$_diffIs = ( $report['_is'] == 'new' ) ? "<span style='color:green'>{$this->lang->words['sk_new']}</span>" : "<span style='color:red'>{$this->lang->words['sk_changed']}</span>";
			
			$IPBHTML .= <<<EOF
				<tr>
				<td width='1%'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/template.png' class='ipd' /></td>
				 <td width='84%'>
				   <strong>{$report['diff_change_func_name']}</strong>
				 </td>
				 <td width='5%' nowrap='nowrap' align='center'>{$_diffIs}</td>
				 <td width='5%' nowrap='nowrap' align='center'>{$report['_size']}</td>
				 <td width='5%'>
				 	<img class='ipbmenu' id="menu{$_safe}" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt='{$this->lang->words['a_options']}' />
					<ul class='acp-menu' id='menu{$_safe}_menucontent'>
						<li class='icon view'><a href='javascript:void(0);' onclick="return viewDiff('$key')">{$this->lang->words['sk_viewdiffs']}...</a></li>
					</ul>
				 </td>
				</tr>
EOF;
		}
	}
}
else
{
	$IPBHTML .= <<<EOF
		<tr>
		 <td colspan='4'><em>{$this->lang->words['sk_nodiffs']}</em></td>
		</tr>
EOF;
}


$IPBHTML .= <<<EOF
 </table>
 <div class='acp-actionbar'>&nbsp;</div>
</div>
<div id='viewWrap' style='background:white;display:none'>
	<div class='acp-box'>
		<h3 id='viewWrapTitle'></h3>
		<div id='viewWrapInner' style='padding:10px;border:1px solid black;height:400px;width:650px;white-space:pre;overflow:auto' name='viewDiff'></div>
		<h3 class='sub' style='padding:6px;text-align:right'><a href='javascript:void(0);' style='color:white' onclick="$('viewWrap').hide();">{$this->lang->words['sk_close']}</a></h3>
	</div>
</div>
<script type="text/javascript">
function viewDiff( key )
{
	/* Clear out current content */
	$('viewWrapInner').update('');
	$('viewWrapTitle').update('');
	
	/* Grab it via ajax */
	var _url = "{$this->settings['base_url']}&app=core&module=ajax&section=templatediff&do=viewDiff&key=" + key;
	
	new Ajax.Request( _url,
					  {
						method: 'get',
						onSuccess: function (t)
						{
							/* Not a JSON response? */
							if ( ! t.responseText.match( /^(\s+?)?\{/ ) )
							{
								alert( "{$this->lang->words['sk_error']}:" + t.responseText );
								return;
							}

							/* Process results */
							eval( "var json = " + t.responseText );

							if ( json['error'] )
							{
								alert( "{$this->lang->words['sk_error']}: " . json['error'] );
								return false;
							}
							
							/* otherwise... */
							if ( json['diff_change_content'] )
							{
								//menu_action_close();
								$('viewWrapInner').update( json['diff_change_content'] );
								$('viewWrapTitle').update( json['diff_change_func_group'] + ' &gt; ' + json['diff_change_func_name'] );
								$('viewWrap').show();
								
								var elem_s = $( 'viewWrap' ).getDimensions();
								var window_s = document.viewport.getDimensions();
								var window_offsets = document.viewport.getScrollOffsets();

								var center = { 	left: ((window_s['width'] - elem_s['width']) / 2),
											 	top: ((window_s['height'] - elem_s['height']) / 2)
											}

								$( 'viewWrap' ).setStyle('top: ' + center['top'] + 'px; left: ' + center['left'] + 'px; position: fixed;');
							}
						},
						onFailure: function()
						{
							return false;
						},
						onException: function()
						{
							return false;
						}
						
					  } );
}

</script>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show an ajax-style refresh screen for difference report
 *
 * @access	public
 * @param	string		Session ID
 * @param	int			Total skin bits
 * @param	int			Skin bits per refresh
 * @return	string		HTML
 */
public function skindiff_ajaxScreen( $sessionID, $totalBits, $perGo ) {

$IPBHTML = "";
//--starthtml--//
$bitsof = sprintf( $this->lang->words['sk_bitsof'], $totalBits );
$clickhere = sprintf( $this->lang->words['sk_clickviewreport'], $this->settings['base_url'], $this->form_code, $sessionID  );
$IPBHTML .= <<<EOF
<style type="text/css">
	@import url( "{$this->settings['skin_app_url']}skinDiff.css" );
</style>
<script type="text/javascript" src="{$this->settings['js_app_url']}ipb3TemplateDiff.js"></script>
<div class='acp-box'>
	<h3>{$this->lang->words['sk_skindifferences']}: {$this->lang->words['sk_processing']}</h3>
	<table class='alternate_rows triple_pad'>
	<tr>
		<td>
			<div id='diffLogDraw'>
				<div id='diffLowDrawInner'>
					<table cellspacing='0' cellpadding='0'>
					<tr>
						<td valign='top'>
							<div id='diffLogText'><span id='diffLogProcessed'></span> {$bitsof}</div>
							<div id='diffDone' style='display:none'>{$clickhere}</div>
						</td>
					</tr>
					</table>
					<div id='diffLogProgressWrap'>
						<img src='{$this->settings['skin_acp_url']}/images/folder_components/templates/diff/mini-wait.gif' id='diffStatusImage' border='0' />
						<div id='diffLogProgressBar'>
							<div id='diffLogProgressBarInner'></div>
						</div>
					</div>
				</div>
			</div>
		</td>
	</tr>
 </table>
 <div class='acp-actionbar'>&nbsp;</div>
</div>
<!-- Preload the status images -->
<div style='display:none'>
	<img src='{$this->settings['skin_acp_url']}/images/folder_components/templates/diff/receiving.png' border='0' />
	<img src='{$this->settings['skin_acp_url']}/images/folder_components/templates/diff/sending.png' border='0' />
	<img src='{$this->settings['skin_acp_url']}/images/folder_components/templates/diff/stop.png' border='0' />
	<img src='{$this->settings['skin_acp_url']}/images/folder_components/templates/diff/warning.png' border='0' />
	<img src='{$this->settings['skin_acp_url']}/images/folder_components/templates/diff/ready.png' border='0' />
	<img src='{$this->settings['skin_acp_url']}/images/folder_components/templates/diff/mini-wait.gif' border='0' />
</div>
<script type='text/javascript'>
/* Add to the applications array */
IPB3TemplateDiff.baseUrl   = "{$this->settings['base_url']}&app=core&module=ajax&section=templatediff&sessionID={$sessionID}&perGo={$perGo}&secure_key={$this->member->form_hash}";
IPB3TemplateDiff.imageUrl  = "{$this->settings['skin_acp_url']}/images/folder_components/templates/diff/";
IPB3TemplateDiff.totalBits = $totalBits;
IPB3TemplateDiff.init();
</script>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Overview screen for skin diff reports
 *
 * @access	public
 * @param	array 		Skin diff sessions
 * @return	string		HTML
 */
public function skindiff_overview( $sessions=array() ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='acp-box'>
	<h3>{$this->lang->words['sk_skindiffreports']}</h3>
	<table class='alternate_rows triple_pad'>
	<tr>
		<th width='1%'>&nbsp;</th>
		<th width='89%'><strong>{$this->lang->words['sk_difftitle']}</strong></th>
		<th width='5%'>{$this->lang->words['sk_created']}</th>
		<th width='5%'>&nbsp;</th>
	</tr>

EOF;

if ( count( $sessions ) )
{
	foreach( $sessions as $id => $data )
	{
		$IPBHTML .= <<<EOF
		<tr>
		 <td><img src='{$this->settings['skin_acp_url']}/_newimages/icons/folder.png' /></td>
		 <td><strong>{$data['diff_session_title']}</strong></td>
		 <td nowrap='nowrap' align='center'>{$data['_date']}</td>
		 <td width='5%'>
		 	<img class='ipbmenu' id="menu{$data['diff_session_id']}" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt='{$this->lang->words['a_options']}' />
			<ul class='acp-menu' id='menu{$data['diff_session_id']}_menucontent'>
				<li class='icon view'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=viewReport&amp;sessionID={$data['diff_session_id']}'>{$this->lang->words['sk_viewdiffresults']}...</a></li>
				<li class='icon delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}&amp;do=removeReport&amp;sessionID={$data['diff_session_id']}");'>{$this->lang->words['sk_removediffresults']}...</a></li>
				<li class='icon export'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=exportReport&amp;sessionID={$data['diff_session_id']}'>{$this->lang->words['sk_createhtmlexport']}...</a></li>
			</ul>
		 </td>
		</tr>
EOF;
	}
}
else
{
	$IPBHTML .= <<<EOF
		<tr>
		 <td colspan='5'><em>{$this->lang->words['sk_nodiffs']}</em></td>
		</tr>
EOF;
}


$IPBHTML .= <<<EOF
 </table>
 <div class='acp-actionbar'>&nbsp;</div>
</div>
<br />
<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=skinDiffStart' enctype='multipart/form-data' method='POST'>
<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->_admin_auth_key}' />
<div class='acp-box'>
	<h3>{$this->lang->words['sk_createnewskindiff']}</h3>
	 <table class='alternate_rows triple_pad'>
	 <tr>
	  <td><strong>{$this->lang->words['sk_enterdifftitle']}</strong><div class='desctext'>{$this->lang->words['sk_difftitle_info']}</div></td>
	  <td><input class='textinput' type='text' size='30' name='diff_session_title' /></td>
	 </tr>
	 <tr>
	  <td><strong>{$this->lang->words['sk_skipnewmiss']}</strong><div class='desctext'>{$this->lang->words['sk_skipnewmiss_info']}</div></td>
	  <td><input class='textinput' type='checkbox' value='1' name='diff_session_ignore_missing' /></td>
	 </tr>
	 <tr>
	  <td><strong>{$this->lang->words['sk_selectskinxml']}</strong><div class='desctext'>{$this->lang->words['sk_selectskinxml_info']}</div></td>
	  <td>
		<input class='textinput' type='file' size='30' name='FILE_UPLOAD' />
	 </td>
	 <tr>
	  <td><strong>{$this->lang->words['diff_folder_path']}</strong></td>
	  <td><input type='text' size='20' name='diffFolder' /></td>
	 </tr>
	 <tr>
	 </tr>
	 </table>
	 <div class='acp-actionbar'>
		<input type='submit' value='{$this->lang->words['sk_import']}' class='button primary' />
	 </div>
</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show form to add/edit a skin url mapping
 *
 * @access	public
 * @param	array 		Form bits
 * @param	string		Form title
 * @param	string		Action code
 * @param	string		Button text
 * @param	array		Remap data
 * @param	array 		Skin set data
 * @return	string		HTML
 */
public function urlmap_showForm( $form, $title, $formcode, $button, $remap, $setData ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['sk_urlmapfor']} {$setData['set_name']}</h2>
</div>
<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=$formcode&amp;map_id={$remap['map_id']}&amp;setID={$setData['set_id']}' id='mainform' method='POST'>
<div class='acp-box'>
	<h3>$title</h3>
	<table width='100%' border='0' cellpadding='0' cellspacing='0' class='form_table alternate_rows'>
		<tr>
			<th colspan='2'>{$this->lang->words['sk_generalsettings']}</th>
		</tr>
		<tr>
			<td style='width: 40%'>
				<label>{$this->lang->words['sk_title']}</label><br />
				<span class='desctext'>{$this->lang->words['sk_title_info']}</span>
			</td>
			<td style='width: 60%'>
				{$form['map_title']}
			</td>
		</tr>
		<tr>
			<td style='width: 40%'>
				<label>{$this->lang->words['sk_type']}</label><br />
				<span class='desctext'>{$this->lang->words['sk_type_info']}</span>
			</td>
			<td style='width: 60%'>
				{$form['map_match_type']}
			</td>
		</tr>
		<tr>
			<td style='width: 40%'>
				<label>{$this->lang->words['sk_url']}</label><br />
				<span class='desctext'>{$this->lang->words['sk_url_info']}</span>
			</td>
			<td style='width: 60%'>
				{$form['map_url']}
			</td>
		</tr>
	</table>
	<div class='acp-actionbar'>
 		<input type='submit' value=' $button ' class='button primary'/>
	</div>
</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * List current URL mappings
 *
 * @access	public
 * @param	array 		Current remaps
 * @param	array 		Skin set data
 * @return	string		HTML
 */
public function urlmap_showURLMaps( $remaps=array(), $skinSetData=array() ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['sk_urlremappingfor']} {$skinSetData['set_name']}</h2>
	<ul class='context_menu'>
		<li>
			<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=remapAdd&amp;setID={$skinSetData['set_id']}'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/add.png' alt='' /> {$this->lang->words['sk_addnewurl']}</a>
		</li>
	</ul>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['sk_mappedurls']}</h3>
	<table class='alternate_rows'>
		<tr>
            <th width='5%'>&nbsp;</th>
            <th width='45%'>{$this->lang->words['sk_title']}</th>
            <th width='45%'>{$this->lang->words['sk_added']}</th>
            <th width='5%'></th>
		</tr>
EOF;
if ( count( $remaps ) )
{
	foreach( $remaps as $data )
	{
$IPBHTML .= <<<EOF
        <tr>
            <td><img src='{$this->settings['skin_acp_url']}/images/folder_components/skinremap/remap_row.png' border='0' class='ipd' /></td>
            <td><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=remapEdit&amp;map_id={$data['map_id']}&amp;setID={$skinSetData['set_id']}'><strong>{$data['map_title']}</strong></a></td>
            <td>{$data['_date']}</td>
            <td>
            	<img class='ipbmenu' id="menu{$data['map_id']}" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt='{$this->lang->words['a_options']}' />
                <ul class='acp-menu' id='menu{$data['map_id']}_menucontent'>
                    <li class='icon edit'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=remapEdit&amp;map_id={$data['map_id']}&amp;setID={$skinSetData['set_id']}'>{$this->lang->words['sk_editmapping']}...</a></li>
                    <li class='icon delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=remapRemove&amp;map_id={$data['map_id']}&amp;setID={$skinSetData['set_id']}");'>{$this->lang->words['sk_removemapping']}...</a></li>
                </ul>
            </td>
        </tr>
EOF;
	}
}
else
{
$IPBHTML .= <<<EOF
        <tr>
            <td colspan='4' align='center'>{$this->lang->words['sk_noremapping']}</td>
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
 * User agent skin mappings
 *
 * @access	public
 * @param	array 		User agent configs
 * @param	array		User agent groups
 * @param	array 		Skin set data
 * @return	string		HTML
 */
public function useragents_showUserAgents( $userAgents, $userAgentGroups, $setData ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['uagent_mapping']}</h2>
</div>
<div class='section_info'>
	{$this->lang->words['sk_useragent_info']}
</div>
<form id='uAgentsForm' action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=saveAgents&amp;setID={$setData['set_id']}' method='post'>
	<div class='acp-box'>
		<h3>{$this->lang->words['sk_uagentmappingfor']} {$setData['set_name']}</h3>
		<table width='100%' cellpadding='0' cellspacing='0' class='alternate_rows double_pad'>
			<tr>
				<th colspan='4'>{$this->lang->words['sk_groups']}</th>
			</tr>
EOF;
			foreach( $userAgentGroups as $id => $data )
			{
				$_selected = ( ( is_array( $setData['_userAgents']['groups'] ) ) AND in_array( $data['ugroup_id'], array_values( $setData['_userAgents']['groups'] ) ) ) ? 'checked="checked"' : '';

				$IPBHTML .= <<<EOF
				<tr>
					<td style='width: 2%; text-align: center'><input type='checkbox' name='uGroups[{$data['ugroup_id']}]' value='1' {$_selected} /></td>
					<td style='width: 2%; text-align: center'><img src="{$this->settings['skin_acp_url']}/images/folder_components/uagents/group.png" class='ipd' /></td>
					<td style='width: 56%' colspan='2'><strong>{$data['ugroup_title']}</strong></td>
				</tr>
EOF;
			}
			
			$IPBHTML .= <<<EOF
			<tr>
				<th colspan='4'>{$this->lang->words['sk_useragents']}</th>
			</tr>
EOF;
	foreach( $userAgents as $id => $data )
	{
		$_selected = ( ( is_array( $setData['_userAgents']['uagents'] ) ) AND in_array( $data['uagent_key'], array_keys( $setData['_userAgents']['uagents'] ) ) ) ? 'checked="checked"' : '';
		
		$IPBHTML .= <<<EOF
			<tr>
				<td style='width: 2%; text-align: center'><input type='checkbox' name='uAgents[{$data['uagent_id']}]' value='1' {$_selected} /></td>
				<td style='width: 2%; text-align: center'><img src="{$this->settings['skin_acp_url']}/images/folder_components/uagents/type_{$data['uagent_type']}.png" class='ipd' /></td>
				<td style='width: 56%;'><strong>{$data['uagent_name']}</strong></td>
				<td style='width: 40%'>{$this->lang->words['sk_versions']}: <input type='text' name='uAgentVersion[{$data['uagent_id']}]' value='{$setData['_userAgents']['uagents'][ $data['uagent_key'] ]}' /></td>
			</tr>
EOF;
	}
$IPBHTML .= <<<EOF
		</table>
		<div class='acp-actionbar'>
		 	<input type='submit' value='{$this->lang->words['sk_save']}' class='realbutton' />
		</div>
	</div>
</div>
</form>
EOF;
//--endhtml--//
return $IPBHTML;
}

/**
 * Show form to add/edit skin set
 *
 * @access	public
 * @param	array 		Form bits
 * @param	string		Form title
 * @param	string		Action code
 * @param	string		Button text
 * @param	array 		Skin set data
 * @return	string		HTML
 */
public function skinsets_setForm( $form, $title, $formcode, $button, $skinSet ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>$title</h2>
</div>

<form id='uAgentsForm' action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=$formcode&amp;set_id={$skinSet['set_id']}' method='post'>
<div class='acp-box'>
	<h3>$title</h3>
	<table class='form_table double_pad alternate_rows'>
		<tr>
			<th colspan='2'>{$this->lang->words['sk_basics']}</th>
		</tr>
		<tr>
			<td style='width: 40%'>
				<label>{$this->lang->words['sk_settitle']}</label>
			</td>
			<td style='width: 60%'>
				{$form['set_name']}
			</td>
		</tr>
		<tr>
			<td>
				<label>{$this->lang->words['sk_setoutputformat']}</label>
			</td>
			<td>
				{$form['set_output_format']}
			</td>
		</tr>
		<tr>
			<th colspan='2'>{$this->lang->words['sk_setperms']}</th>
		</tr>
		<tr>
			<td>
				<label>{$this->lang->words['sk_selectallgroups']}</label>
			</td>
			<td>
				<input type='checkbox' onclick="checkPermTickBox()" id='setPermissionsAll' name='set_permissions_all' value='1' {$form['set_permissions_all']} />
			</td>
		</tr>
		<tr>
			<td>
				<label>{$this->lang->words['sk_selectwhichgroups']}</label>
				<br /><span class='desctext'>{$this->lang->words['sk_selectmorethanone']}</span>
			</td>
			<td>
				{$form['set_permissions']}
			</td>
		</tr>
EOF;
		if ( $skinSet['set_default'] )
		{
			$IPBHTML .= <<<EOF
	        <tr>
				<td>
					<label>{$this->lang->words['sk_setasdefault_info']}</label>
				</td>
				<td>
	            	<em>{$this->lang->words['sk_defaultalready']}</em>
				</td>
	        </tr>
EOF;
		}
		else
		{
			$IPBHTML .= <<<EOF
	        <tr>
				<td>
					<label>{$this->lang->words['sk_setasdefault_info']}</label>
				</td>
				<td>
	            	{$form['set_is_default']}
				</td>
	        </tr>
EOF;
		}
	
	$IPBHTML .= <<<EOF
	
		<tr>
			<td>
				<label>{$this->lang->words['sk_skinsetparent']}</label>
			</td>
			<td>
				<select name='set_parent_id'>{$form['set_parent_id']}</select>
			</td>
		</tr>
		<tr>
			<td>
				<label>{$this->lang->words['sk_skinsetkey']}</label>
				<br /><span class='desctext'>*{$this->lang->words['sk_optional']}</span>
			</td>
			<td>
	            {$form['set_key']}
			</td>
		</tr>
		<tr>
			<td>
				<label>{$this->lang->words['sk_hideskin']}</label>
				<br /><span class='desctext'>{$this->lang->words['sk_hideskin_info']}</span>
			</td>
			<td>
				{$form['set_hide_from_list']}
			</td>
		</tr>
		<tr>
			<th colspan='2'>{$this->lang->words['sk_cssoptions']}</th>
		</tr>
		<tr>
			<td>
				<label>{$this->lang->words['sk_cachecss']}</label>
				<br /><span class='desctext'>{$this->lang->words['sk_cachecss_info']}</span>
			</td>
			<td>
	            {$form['set_css_inline']}
			</td>
		</tr>
		<tr>
			<td>
				<label>{$this->lang->words['ss_minify']}</label>
				<br /><span class='desctext'>{$this->lang->words['ss_minify_desc']}</span>
			</td>
			<td>
	            {$form['set_minify']}
			</td>
		</tr>
		<tr>
			<th colspan='2'>{$this->lang->words['sk_imageoptions']}</th>
		</tr>
		<tr>
			<td>
				<label>{$this->lang->words['sk_useimgdir']}</label>
				<br /><span class='desctext'>{$this->lang->words['sk_useimgdir_info']}</span>
			</td>
			<td>
	            public/style_images/ {$form['set_image_dir']}
			</td>
		</tr>
		<tr>
			<td>
				<label>{$this->lang->words['sk_useemoset']}</label>
				<br /><span class='desctext'>{$this->lang->words['sk_useemoset_info']}</span>
			</td>
			<td>
	            public/style_emoticons/ {$form['set_emo_dir']}
			</td>
		</tr>
		<tr>
			<th colspan='2'>{$this->lang->words['sk_setauthor']}</th>
		</tr>
		<tr>
			<td>
				<label>{$this->lang->words['sk_setauthorname']}</label>
				<br /><span class='desctext'>*{$this->lang->words['sk_optional']}</span>
			</td>
			<td>
				{$form['set_author_name']}
			</td>
		</tr>
		<tr>
			<td>
				<label>{$this->lang->words['sk_setauthorurl']}</label>
				<br /><span class='desctext'>*{$this->lang->words['sk_optional']}</span>
			</td>
			<td>
				{$form['set_author_url']}
			</td>
		</tr>				
	</table>
	<div class='acp-actionbar'>
		<input type='submit' value=' $button ' class='button primary' />
    </div>
</div>
</form>
<script type='text/javascript'>
/* set it up */
checkPermTickBox();
checkMakeGlobal();

function checkMakeGlobal()
{
	var _val = $('setIsDefault').checked;

	if ( _val )
	{
		$('setPermissions').disabled   = true;
		$('setPermissionsAll').checked = true;
	}
	else
	{
		$('setPermissions').disabled = false;
	}
}

function checkPermTickBox()
{
	var _val  = $('setPermissionsAll').checked;
	var _val2 = $('setIsDefault').checked;
	
	if ( _val || _val2 )
	{
		$('setPermissions').disabled = true;
	}
	else
	{
		$('setPermissions').disabled = false;
	}
}
</script>
EOF;
//--endhtml--//
return $IPBHTML;
}

/**
 * Skin tools splash page
 *
 * @access	public
 * @param	string		Skin options dropdown
 * @param	array 		App data
 * @param	array 		IN_DEV remap data
 * @return	string		HTML
 */
public function tools_splash( $skinOptionList, $appData, $remapData=array() ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['to_templatetools']}</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['sk_skintools']}</h3>
	<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=toolsRecache' enctype='multipart/form-data' method='POST'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->_admin_auth_key}' />
	<table class='alternate_rows triple_pad'>
	<tr>
		<th colspan='2'>{$this->lang->words['sk_recacheskinsets']}</th>
	</tr>
	<tr>
		<td width='30%'>{$this->lang->words['sk_selectskinset']}</td>
		<td width='70%'><select name='setID'><option value='0'>&lt; {$this->lang->words['sk_allskinsets']}&gt;</option>{$skinOptionList}</select></td>
	</tr>
	</table>
	 <div class='acp-actionbar'>
		<input type='submit' value=' {$this->lang->words['sk_recacheskinsets']}' class='button primary' />
	 </div>
	</form>
	<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=toolsResetSkin' enctype='multipart/form-data' method='POST'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->_admin_auth_key}' />
	<table class='alternate_rows triple_pad'>
	<tr>
		<th colspan='2'>{$this->lang->words['sk_resetskinusage']}</th>
	</tr>
	<tr>
		<td width='30%' valign='top'>
			<strong>{$this->lang->words['reset_for']}</strong>
			<p>
				<p><input type='checkbox' value='1' name='resetMembers' /> {$this->lang->words['sk_members']}</p>
				<p><input type='checkbox' value='1' name='resetForums' /> {$this->lang->words['sk_forums']}</p>
			</p>
			<br />
			<strong>{$this->lang->words['sk_resetto']}:</strong>
			<p>
			<select name='resetSkinID'><option value='0'>&lt; {$this->lang->words['sk_usedefault']} &gt;</option>{$skinOptionList}</select>
			</p>
		</td>
		<td width='70%'>
			<strong>{$this->lang->words['sk_wheretheyuse']}:</strong>
			<p>
				<select name='setID[]' multiple="multiple" size="10">{$skinOptionList}</select>
			</p>
		</td>
	</tr>
	</table>
	 <div class='acp-actionbar'>
		<input type='submit' value='{$this->lang->words['sk_reset']}' class='button primary' />
	 </div>
	</form>
	<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=toolsRebuildMaster' enctype='multipart/form-data' method='POST'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->_admin_auth_key}' />
	<table class='alternate_rows triple_pad'>
	<tr>
		<th colspan='2'>{$this->lang->words['sk_rebuildmasterdata']}</th>
	</tr>
	<tr>
		<td width='30%' valign='top'>
			<strong>{$this->lang->words['sk_rebuild']}:</strong>
			<p>
				<p><input type='checkbox' value='1' name='rebuildHTML' /> {$this->lang->words['sk_rebuildhtml']}</p>
				<p><input type='checkbox' value='1' name='rebuildCSS' /> {$this->lang->words['sk_rebuildcss']}</p>
				<p><input type='checkbox' value='1' name='rebuildReplacements' /> {$this->lang->words['sk_rebuildreplacements']}</p>
			</p>
		</td>
		<td width='70%'>
			<strong>{$this->lang->words['sk_forapps']}:</strong>
			<p>
EOF;
	foreach( $appData as $appDir => $_appData )
	{
		$IPBHTML .= <<<EOF
				<p><input type='checkbox' value='1' name='apps[$appDir]'> <strong>{$_appData['app_title']}</strong> <span style='color:gray;font-size:0.9em'>({$this->lang->words['sk_templatexmllast']} - {$_appData['lastmTimeFormatted']})</span></p>
EOF;
	}
$IPBHTML .= <<<EOF
			</p>
		</td>
	</tr>
	</table>
	 <div class='acp-actionbar'>
		<input type='submit' value='{$this->lang->words['sk_rebuild']}' class='button primary' />
	 </div>
	</form>
	</table>
	<table class='alternate_rows triple_pad'>
	<tr>
		<th colspan='2'>{$this->lang->words['sk_cleanup_title']}</th>
	</tr>
	<tr>
		<td width='70%'>
			{$this->lang->words['sk_cleanup_templates']}
			<div style='color:gray;font-size:0.9em'>
				{$this->lang->words['sk_cleanup_templates_exp']}
			</div>
		</td>
		<td width='30%'><a href='{$this->settings['base_url']}module=templates&amp;section=tools&amp;do=templateDbClean'>{$this->lang->words['sk_run_tool']}</a></td>
	</tr>
	<tr>
		<td width='70%'>
			{$this->lang->words['sk_cleanup_css']}
			<div style='color:gray;font-size:0.9em'>
				{$this->lang->words['sk_cleanup_css_exp']}
			</div>
		</td>
		<td width='30%'><a href='{$this->settings['base_url']}module=templates&amp;section=tools&amp;do=cssDbClean'>{$this->lang->words['sk_run_tool']}</a></td>
	</tr>
	</table>
	
	<form action='{$this->settings['base_url']}module=templates&amp;section=tools&amp;do=toolCacheClean' method='POST'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->_admin_auth_key}' />
	<table class='alternate_rows triple_pad'>
	<tr>
		<td width='30%'>
			{$this->lang->words['sk_clean_caches']}
			<p>
				<p><input type='checkbox' name='cleanCss' value='1' /> {$this->lang->words['sk_clean_caches_cb_css']}</p>
				<p><input type='checkbox' name='cleanTemplates' value='1' /> {$this->lang->words['sk_clean_caches_cb_tem']}</p>
			</p>
		</td>
		<td width='70%'><select name='setID'>{$skinOptionList}</select> &nbsp; <input type='submit' value=' {$this->lang->words['sk_run_tool']}' class='button primary' /></td>
	</tr>
	</table>
	</form>
	
EOF;
/**** IN DEV ****/
if  (IN_DEV )
{
	$IPBHTML .= <<<EOF
	<!-- IN DEV -->
 	<table class='alternate_rows triple_pad'>
	<tr>
		<th colspan='2'>IPS Developer's Tools</th>
	</tr>
	<tr>
		<td width='70%'>
			<strong>Build</strong> Skin Files For Release
			<div style='color:gray;font-size:0.9em'>
				This tool:
				<ul>
				 	<li>Rebuilds your HTML/CSS/Replacements from the master_*/ disk files.</li>
					<li>Exports all template XML into the application directories</li>
					<li>Exports skinsData.xml, css.xml and replacements.xml for each default skin</li>
				</ul>
EOF;
	if ( ! is_writable( DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/resources/skins' ) )
	{
		$_file = DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/resources/skins';
		$IPBHTML .= <<<EOF
		<div style='color:red'>Cannot write to $_file</div>
EOF;
	}
	foreach( $appData as $appDir => $_appData )
	{
		if ( ! is_writable( IPSLib::getAppDir( $appDir ) . '/xml' ) )
		{
			$_file = IPSLib::getAppDir( $appDir ) . '/xml';
			
		$IPBHTML .= <<<EOF
				<div style='color:red'>Cannot write to $_file</div>
EOF;
		}
	}
$IPBHTML .= <<<EOF
			</div>
		</td>
		<td width='30%'><a href='{$this->settings['base_url']}module=templates&amp;section=tools&amp;do=rebuildForRelease'>{$this->lang->words['sk_run']}</a></td>
	</tr>
	<tr>
		<td width='70%'>
			Create Master <strong>PHP Templates</strong> Directory
			<div style='color:gray;font-size:0.9em'>
				Note, this tool is for editing master set 0, not your own skin sets. Use the per-skin tool instead (see /cache/skin_cache/masterMap.php)
			</div>
EOF;
	if ( is_dir( IPS_CACHE_PATH . 'cache/skin_cache/master_skin' ) )
	{
		$IPBHTML .= <<<EOF
			<div style='color:gray;font-size:0.9em'>
				You already have a master_skin directory. Using this tool will overwrite the contents. Use with caution!
			</div>
EOF;

	}

$IPBHTML .= <<<EOF
		</td>
		<td width='30%'>
			<a href='{$this->settings['base_url']}module=templates&amp;section=tools&amp;do=createMasterSkin&amp;set_id=0'>{$this->lang->words['sk_run']}</a>
		</td>
	</tr>
	<tr>
		<td width='70%'>Rebuild Master <strong>HTML</strong> From PHP Caches</td>
		<td width='30%'><a href='{$this->settings['base_url']}module=templates&amp;section=tools&amp;do=rebuildMasterSkin&amp;set_id=0'>{$this->lang->words['sk_run']}</a></td>
	</tr>
	<tr>
		<td width='70%'>Rebuild Master <strong>CSS</strong> From Disk Files</td>
		<td width='30%'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=inDevMasterCSS'>{$this->lang->words['sk_run']}</a></td>
	</tr>
	<tr>
		<td width='70%'>Rebuild Master <strong>Replacements</strong> From Disk File</td>
		<td width='30%'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=inDevMasterReplacements'>{$this->lang->words['sk_run']}</a></td>
	</tr>
	<tr>
		<td width='70%'><strong>Export</strong> HTML Templates Into Application Directories</td>
		<td width='30%'><a href='{$this->settings['base_url']}module=templates&amp;section=tools&amp;do=exportAPPTemplates'>{$this->lang->words['sk_run']}</a></td>
	</tr>
	<tr>
		<td width='70%'><strong>Export</strong> CSS Into Application Directories</td>
		<td width='30%'><a href='{$this->settings['base_url']}module=templates&amp;section=tools&amp;do=exportAPPCSS'>{$this->lang->words['sk_run']}</a></td>
	</tr>
	<tr>
		<td width='70%'><strong>Export</strong> Replacements To XML Files</td>
		<td width='30%'><a href='{$this->settings['base_url']}module=templates&amp;section=tools&amp;do=exportMasterReplacements'>{$this->lang->words['sk_run']}</a></td>
	</tr>
	<tr>
		<td width='70%'><strong>Import</strong> HTML Templates From Application Directories</td>
		<td width='30%'><a href='{$this->settings['base_url']}module=templates&amp;section=tools&amp;do=importAPPTemplates'>{$this->lang->words['sk_run']}</a></td>
	</tr>
	</table>
	 <div class='acp-actionbar'>
		&nbsp;
	 </div>
EOF;
}
$IPBHTML .= <<<EOF
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Results from running a skin tool
 *
 * @access	public
 * @param	string		Page title
 * @param	array		Ok messages
 * @param	array 		Error messages
 * @return	string		HTML
 */
public function tools_toolResults( $title, $okMessages, $errorMessages=array() ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='tableborder'>
	<div class='tableheaderalt'>$title</div>
	<table width='100%' cellpadding='0' cellspacing='0' class='triple_pad'>
	<tr class='acp-row-off'>
		<td>
EOF;
	if ( is_array( $errorMessages ) )
	{
		foreach( $errorMessages as $entry )
		{
			$IPBHTML .= <<<EOF
				<div class='input-warn-content' style='color:red'>$entry</div>
EOF;
		}
	}
	
	if ( is_array( $okMessages ) )
	{
		foreach( $okMessages as $entry )
		{
			$IPBHTML .= <<<EOF
				<div class='input-ok-content'>$entry</div>
EOF;
		}
	}
	
$IPBHTML .= <<<EOF
		</td>
	</tr>
	</table>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Splash page to remove customizations from a skin set
 *
 * @access	public
 * @param	array 		Skin set data
 * @return	string		HTML
 */
public function skinsets_revertSplash( $setData, $counts ) {

$IPBHTML = "";
//--starthtml--//
$IPBHTML .= <<<EOF
<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=setRevert&amp;setID={$setData['set_id']}&amp;authKey={$this->member->form_hash}' method='post'>
<div class='section_title'>
	<h2>{$this->lang->words['sk_revert_title']} '{$setData['set_name']}'</h2>
</div>
<div class='acp-box'>
	<h3>{$this->lang->words['sk_pleaseconfirm']}</h3>
	<table width='100%' cellpadding='0' cellspacing='0' class='triple_pad'>
		<tr class='acp-row-off'>
			<td>
				<p>
					<p>{$this->lang->words['sk_revert_desc']}</p>
					<p><input type='checkbox' name='templates' value='1' /> <strong>{$counts['templates']}</strong> {$this->lang->words['sk_revert_templates']}</p>
					<p><input type='checkbox' name='css' value='1' /> <strong>{$counts['css']}</strong> {$this->lang->words['sk_revert_css']}</p>
					<p><input type='checkbox' name='replacements' value='1' /> <strong>{$counts['replacements']}</strong> {$this->lang->words['sk_revert_replacements']}</p>
				</p>
			</td>
		</tr>
	</table>
	<div class='acp-actionbar'>
		<input type='submit' value=' {$this->lang->words['sk_revert_button']} ' class='realbutton redbutton' />
	</div>
</div>
</form>
	
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Splash page to remove a skin set
 *
 * @access	public
 * @param	array 		Skin set data
 * @return	string		HTML
 */
public function skinsets_removeSplash( $setData ) {

$IPBHTML = "";
//--starthtml--//
$pleaseconfirmthatyoureallywanttoremovethisskinset = sprintf( $this->lang->words['sk_pleaseconfirm_info'], $setData['set_name'] );
$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['sk_removingset']} '{$setData['set_name']}'</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['sk_pleaseconfirm']}</h3>
	<table width='100%' cellpadding='0' cellspacing='0' class='triple_pad'>
		<tr class='acp-row-off'>
			<td>
				{$pleaseconfirmthatyoureallywanttoremovethisskinset}
			</td>
		</tr>
	</table>
	<div class='acp-actionbar'>
		<input type='button' value=' {$this->lang->words['sk_removeskinset']} ' class='realbutton redbutton' onclick='acp.redirect( "{$this->settings['base_url']}{$this->form_code}&do=setRemove&set_id={$setData['set_id']}&authKey={$this->member->form_hash}", 1 )' />
	</div>
</div>
	
EOF;

//--endhtml--//
return $IPBHTML;
}
	
/**
 * Skin sets overview (tab homepage)
 *
 * @access	public
 * @param	array 		Skin sets
 * @param	array 		Caching data
 * @return	string		HTML
 */
public function skinsets_listSkinSets( $sets, $cacheData ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['sk_skinmanagement']}</h2>
	<ul class='context_menu'>
		<li><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=setAdd' style='text-decoration:none'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/add.png' alt='' /> {$this->lang->words['sk_addnewrootskin']}</a></li>
		<li><a href='{$this->settings['base_url']}module=templates&amp;section=importexport&amp;do=overview' style='text-decoration:none'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/add.png' alt='' /> {$this->lang->words['sk_importnewskin']}</a></li>
	</ul>
</div>

<div class='acp-box' id='forum_wrapper'>
	<h3>{$this->lang->words['sk_skinsets']}</h3>
	<table style='width: 100%' cellspacing='0' class='double_pad'>
		<tr>
			<th style='width: 85%'>{$this->lang->words['sk_setname']}</th>
			<th style='width: 3%'>&nbsp;</th>
			<th style='width: 12%; text-align: center;'>{$this->lang->words['sk_outputformat']}</th>
			<th style='width: 3%'>&nbsp;</th>
		</tr>
EOF;
	
	foreach( $sets as $idx => $data )
	{
		$subskin = ( $data['depthguide'] ) ? 'subforum' : '';
		
		/* on off stuffs */
		$preOFImage   = 'off_';
		$titleOFImage = $this->lang->words['tt_ss_of_off'];
		$formatImage = '';
		$hiddenImage = "<img title='{$this->lang->words['ss_canttoggle']}' src='{$this->settings['skin_acp_url']}/_newimages/skinset_canthide.png' />";
		
		if ( $data['set_is_default'] )
		{
			$formatImage = "<img title='{$this->lang->words['tt_ss_of']}' src='{$this->settings['skin_acp_url']}/_newimages/output_{$data['set_output_format']}.png' />";
		}
		else
		{
			$formatImage = "<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=makeDefault&amp;set_id={$data['set_id']}' title='{$this->lang->words['tt_ss_of_off']}'><img title='{$this->lang->words['tt_ss_of_off']}' src='{$this->settings['skin_acp_url']}/_newimages/output_off_{$data['set_output_format']}.png' /></a>";
		}
		
		if ( $data['set_hide_from_list'] )
		{
			$hiddenImage = "<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=toggleHidden&amp;set_id={$data['set_id']}' title='{$this->lang->words['ss_hidden']}'><img title='{$this->lang->words['ss_hidden']}' src='{$this->settings['skin_acp_url']}/_newimages/skinset_hidden.png' /></a>";
		}
		else if ( ! $data['set_is_default'] )
		{
			$hiddenImage = "<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=toggleHidden&amp;set_id={$data['set_id']}' title='{$this->lang->words['ss_not_hidden']}'><img title='{$this->lang->words['ss_not_hidden']}' src='{$this->settings['skin_acp_url']}/_newimages/skinset_visible.png' /></a>";
		}
		
		$IPBHTML .= <<<EOF
		<tr>
			 <td class='{$data['_cssClass']} forum_row {$subskin}'>
				{$data['depthguide']}
			 	<img src='{$this->settings['skin_acp_url']}/_newimages/icons/{$data['_setImg']}' />&nbsp;
			 	<strong><a title='{$data['bit_desc']}' href='{$this->settings['base_url']}&amp;module=templates&amp;section=templates&amp;do=list&amp;setID={$data['set_id']}'>{$data['set_name']}</a></strong>
EOF;
		if ( ! $cacheData[ $data['set_id'] ]['db'] AND ! $cacheData[ $data['set_id'] ]['php'] )
		{
			$_depth = ( $data['cssDepthGuide'] * 20 ) + 30;
			$IPBHTML .= <<<EOF
			<br />
				<span class='desctext' style='margin-left: {$_depth}px'>
					{$this->lang->words['sk_notempcache']} <a href='{$this->settings['base_url']}&amp;module=templates&amp;section=tools&amp;do=rebuildPHPTemplates&amp;setID={$data['set_id']}'>{$this->lang->words['sk_pleasebuildnow']}</a>
				</span>
EOF;
		}
		
		$IPBHTML .= <<<EOF
			</td>
			<td class='{$data['_cssClass']} forum_row {$subskin}' style='text-align: center'>
				$hiddenImage
			</td>
			<td class='{$data['_cssClass']} forum_row {$subskin}' style='text-align: center'>
				$formatImage
			</td>
			<td class='{$data['_cssClass']} forum_row {$subskin}'>
				<img class='ipbmenu' id="menubit{$data['set_id']}" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt='{$this->lang->words['a_options']}' />
				<ul class='acp-menu' id='menubit{$data['set_id']}_menucontent'>
					<li class='icon edit'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=setEdit&amp;set_id={$data['set_id']}'>{$this->lang->words['sk_editsettings']}</a></li>
					<li class='icon edit'><a href='{$this->settings['base_url']}&amp;module=templates&amp;section=templates&amp;do=list&amp;setID={$data['set_id']}'>{$this->lang->words['sk_managetempcss']}</a></li>
					<li class='icon edit'><a href='{$this->settings['base_url']}&amp;module=templates&amp;section=replacements&amp;do=list&amp;setID={$data['set_id']}'>{$this->lang->words['sk_managereplacements']}</a></li>
					<li class='icon edit'><a href='{$this->settings['base_url']}&amp;module=templates&amp;section=useragents&amp;do=show&amp;setID={$data['set_id']}'>{$this->lang->words['sk_manageuagentmapping']}</a></li>
					<li class='icon edit'><a href='{$this->settings['base_url']}&amp;module=templates&amp;section=urlmap&amp;do=show&amp;setID={$data['set_id']}'>{$this->lang->words['sk_manageurlmapping']}</a></li>
					<li class='icon delete'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=revertSplash&amp;setID={$data['set_id']}'>{$this->lang->words['sk_revert_customizations']}</a></li>
EOF;
			if ( $data['_canRemove'] )
			{		
			$IPBHTML .= <<<EOF
					<li class='icon delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=setRemoveSplash&amp;set_id={$data['set_id']}");'>{$this->lang->words['sk_removeskinset']}...</a></li>
EOF;
			}
			if ( $data['_canWriteMaster'] AND IN_DEV )
			{
			$IPBHTML .= <<<EOF
					<li class='icon export'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=setWriteMaster&amp;set_id={$data['set_id']}'>EXPORT Templates into 'master' directory...</a></li>
					<li class='icon add'><a href='{$this->settings['base_url']}module=templates&amp;section=tools&amp;do=rebuildMasterSkin&amp;set_id={$data['set_id']}'>IMPORT Templates from 'master' directory...</a></li>
EOF;
			}
			
			if ( $data['_canWriteMasterCss'] AND IN_DEV )
			{
			$IPBHTML .= <<<EOF
					<li class='icon export'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=setWriteMasterCss&amp;set_id={$data['set_id']}'>EXPORT CSS into 'master' directory...</a></li>
					<li class='icon add'><a href='{$this->settings['base_url']}module=templates&amp;section=tools&amp;do=rebuildMasterCss&amp;set_id={$data['set_id']}'>IMPORT CSS from 'master' directory...</a></li>
EOF;
			}

			$IPBHTML .= <<<EOF
				</ul>
			</td>
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
 * List CSS files ina  skin set
 *
 * @access	public
 * @param	array 		CSS data
 * @param	array 		Skin set data
 * @return	string		HTML
 */
public function css_listCSS( $cssData, $setData ) {

$IPBHTML = "";
//--starthtml--//

$_keys    = array_keys( $cssData );
$_json    = json_encode( array( 'css' => $cssData ) );
$_first   = array_shift( $_keys );
$_setData = json_encode( $setData );

$this->lang->words['sk_usestyleurl'] = sprintf( $this->lang->words['sk_usestyleurl'], "{$this->settings['img_url_no_dir']}{$setData['set_image_dir']}" );

$IPBHTML .= <<<EOF
<style type='text/css'>
/*
ALL THESE ARE USED WITHIN ipb3CSS.js
*/

.cssHover
{
	background-color: #D3DAE4;
	background-image: url("{$this->settings['skin_acp_url']}/images/acpMenuMore.png");
	background-repeat: no-repeat;
	background-position: center right;
	cursor: pointer;
}

/* Normal template row */
.tplateRow_def
{
	background: #fafafa;
}

/* Inherited template row */
.tplateRow_inh
{
	background: yellow;
}


/* Modified template row */
.tplateRow_mod
{
	background: red;
}

/* Modified template row */
.tplateRow_new
{
	background: lightgreen;
}

</style>
<script type="text/javascript" src="{$this->settings['js_app_url']}ipb3CSS.js"></script>
<div class='tablerow1' style='margin-left:auto;text-align:center;width:200px;padding:10px'><a href='javascript:void(0)' onclick='IPB3CSS.addCSSForm()'>{$this->lang->words['sk_addnewcss']}</a></div>
<div class='tableborder'>
	<div class='tableheaderalt'>{$this->lang->words['sk_cssfor']}: {$setData['set_name']}</div>
	<div id='tplate_wrapperDiv'>
		<div id='tplate_cssList'></div>
	</div>
</div>
<br />
<div class='tplateRow_def'>{$this->lang->words['sk_unmodifiedcss']}</div>
<div class='tplateRow_inh'>{$this->lang->words['sk_unmodifiedcss2']}</div>
<div class='tplateRow_mod'>{$this->lang->words['sk_modifiedcss']}</div>
<div class='tplateRow_new'>{$this->lang->words['sk_newcss']}</div>
<!-- templates -->
<div style='display:none'>

<div id='tplate_cssRow'>
	<div id='tplate_cssRow_#{css_id}' onmouseover='IPB3CSS.mouseEvent(event)' onmouseout='IPB3CSS.mouseEvent(event)' onclick='IPB3CSS.mouseEvent(event)' class='tablerow2 #{_cssClass}'>
		<div style='float:right'>
			#{_cssSize}
		</div>
		<div id='tplate_cssRow_#{css_id}_differences' style='float:right;margin-right:10px;cursor:pointer'>{$this->lang->words['sk_comparediff']}</div>
		<div>
			<img src='{$this->settings['skin_acp_url']}/_newimages/icons/template.png' class='ipd' />
			<span style='font-weight:bold'>#{css_group}</span>.css
		</div>
	</div>	
</div>
<div id='tplate_cssEditor'>
	<div id='tplate_editor_#{css_id}' class='tableborder' style='width:500px'>
		<div class='tableheaderalt'>{$this->lang->words['sk_editing']} "#{css_group}.css"</div>
		<div class='tablerow2' style='padding:10px'>
			<div style='padding:6px'>
				{$this->lang->words['sk_usestyleurl']}
			</div>
			<div id='tplate_groupBoxWrap_#{css_id}' style='display:none'>{$this->lang->words['sk_cssfilename']}: <input type='text' size='30' value='#{css_group}' id='tplate_groupBox_#{css_id}' />.css</div>
			<div>{$this->lang->words['sk_position']}: <input type='text' size='5' id='tplate_posBox_#{css_id}' value='#{css_position}' /></div>
			<textarea id='tplate_editBox_#{css_id}' style='width:100%;height:400px'>#{css_content}</textarea>
		</div>
		<div class='tablerow2' style='text-align:right;'>
			<input type='button' value='{$this->lang->words['sk_save']}' onclick='IPB3CSS.saveCSS(#{css_id})' />
			&nbsp;
			<input type='button' value='{$this->lang->words['sk_close']}' onclick='IPB3CSS.cancelCSS(#{css_id})' />
		</div>
	</div>
</div>

</div>
<!-- / templates -->
<script type='text/javascript'>
	var IPB3CSS                  = new IPBCSS();
	IPB3CSS.CSSData              = $_json;
	IPB3CSS.currentCSS           = '{$cssData[$_first]['css_group']}';
	IPB3CSS.currentSetData       = $_setData;
	IPB3CSS.init();
</script>

<br />
<textarea id='tplate_debug' style='font-family:"Courier New";font-size:12px;width:100%;height:500px'></textarea>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * List replacements in a skin set
 *
 * @access	public
 * @param	array 		Replacements data
 * @param	array 		Skin set data
 * @return	string		HTML
 */
public function replacements_listReplacements( $replacementsData, $setData ) {

$IPBHTML = "";
//--starthtml--//

$_keys    = array_keys( $replacementsData );
$_json    = json_encode( array( 'replacements' => $replacementsData ) );
$_first   = array_shift( $_keys );
$_setData = json_encode( $setData );

$IPBHTML .= <<<EOF
<script type="text/javascript" src="{$this->settings['js_main_url']}acp.replacements.js"></script>

<div class='section_title'>
	<h2>{$this->lang->words['sk_replaceinset']}: {$setData['set_name']}</h2>
	<ul class='context_menu'>
		<li><a href='#' id='add_replacement'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/add.png' alt='{$this->lang->words['sk_icon']}' /> {$this->lang->words['sk_replacementadd']}</a></li>
		<li><a href='{$this->settings['base_url']}app=core&amp;module=templates&amp;section=skinsets&amp;do=setEdit&amp;set_id={$setData['set_id']}'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/palette_edit.png' alt='{$this->lang->words['sk_icon']}' /> {$this->lang->words['sk_editskinsettings']}</a></li>
		<li><a href='{$this->settings['base_url']}app=core&amp;module=templates&amp;section=templates&amp;do=list&amp;setID={$setData['set_id']}'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/folder_palette.png' alt='{$this->lang->words['sk_icon']}' /> {$this->lang->words['sk_edittempcss']}</a></li>
	</ul>
</div>
<div class='section_info'>
	{$this->lang->words['sk_replace_info']}
</div>

<script type='text/javascript'>
	acp.replacements.allReplacements = $_json;
	acp.replacements.currentSetData = $_setData;
	acp.replacements.realImgDir  = '{$this->settings['public_dir']}style_images/{$setData['set_image_dir']}';
	acp.replacements.iconUrl     = '{$this->settings['skin_acp_url']}/_newimages/icons/';
	acp.replacements.templateUrl = '{$this->settings['skin_acp_url']}/_newimages/templates/';
	acp.replacements.icons       = { 'del-new'      : 'cross.png',
									 'del-modified' : 'arrow_rotate_anticlockwise.png',
									 'del-inherit'  : 'arrow_rotate_anticlockwise.png' };
									
	
	ipb.templates['edit_box'] = new Template("<textarea id='r_#{id}_textbox' class='input_text' style='width: 70%; font-family: arial; font-size: 12px;' rows='2'>#{content}</textarea><div class='replacement_save'><input type='submit' value='{$this->lang->words['sk_save']}' id='r_#{id}_save' class='realbutton' /> <input type='submit' value='{$this->lang->words['sk_cancel']}' id='r_#{id}_cancel' class='realbutton' /></div>");
	
	ipb.templates['add_replacement'] = "<div class='acp-box'><h3>Add Replacement</h3><ul class='acp-form'><li><label for='popup_key'>Replacement Key</label><input type='text' class='input_text' id='popup_key' /></li><li><label for'popup_content'>Replacement Content</label><textarea id='popup_content' style='width: 45%' rows='7' class='input_text'></textarea></li></ul><div class='acp-actionbar'><input type='submit' class='realbutton' value='Add Replacement' id='popup_submit' /></div></div>";
	
	ipb.templates['revert_button'] = new Template("<span class='dropdown-button' title='{$this->lang->words['sk_revertreplace']}' id='r_#{id}_revert'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/arrow_rotate_anticlockwise.png' alt='{$this->lang->words['sk_icon']}' /></span>");
	
	$('add_replacement').observe( 'click', acp.replacements.addReplacement );
</script>

<div class="acp-box">
	<h3>{$this->lang->words['sk_replacements']}</h3>
	
	<table class='alternate_rows double_pad' width='100%'>
		<tr>
			<th width='2%'>&nbsp;</td>
			<th width='20%'>{$this->lang->words['sk_replacekey']}</th>
			<th width='66%' style='text-align: center'>{$this->lang->words['sk_replacecontent']}</th>
			<th width='12%'>&nbsp;</th>
		</tr>
EOF;

foreach( $replacementsData as $replacement )
{
	$status = 'default';
	$revert = '';

	if ( ! $setData['_isMaster'] )
	{
		if( $replacement['replacement_added_to'] == $setData['set_id'] ){
			$status = 'new';
			$revert = "<span class='dropdown-button' title='{$this->lang->words['sk_removereplace']}' id='r_{$replacement['replacement_key']}_delete'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/cross.png' alt='{$this->lang->words['sk_icon']}' /></span>";
		}
		elseif( $replacement['replacement_set_id'] == $setData['set_id'] ){
			$status = 'modified';
			$revert = "<span class='dropdown-button' title='{$this->lang->words['sk_revertreplace']}' id='r_{$replacement['replacement_key']}_revert'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/arrow_rotate_anticlockwise.png' alt='{$this->lang->words['sk_icon']}' /></span>";
		}
		elseif( in_array( $replacement['replacement_set_id'], array_values( $setData['_parentTree'] ) ) ){
			$status = 'inherit';
			$revert = "<span class='dropdown-button' title='{$this->lang->words['sk_revertreplace']}' id='r_{$replacement['replacement_key']}_revert'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/arrow_rotate_anticlockwise.png' alt='{$this->lang->words['sk_icon']}' /></span>";
		}
	}
	
	$replacement['real_content'] = str_replace("{style_image_url}", $this->settings['public_dir'] . 'style_images/' . $setData['set_image_dir'], $replacement['replacement_content'] );
	
	$IPBHTML .= <<<EOF
	
		<tr>
			<td style='vertical-align: top'><img id='r_status_{$replacement['replacement_key']}' src='{$this->settings['skin_acp_url']}/_newimages/templates/{$status}.png' alt='{$this->lang->words['sk_icon']}' /></td>
			<td style='vertical-align: top'><strong>{$replacement['replacement_key']}</strong></td>
			<td style='text-align: center' id='r_{$replacement['replacement_key']}_content'>{$replacement['real_content']}</td>
			<td style='text-align: right; vertical-align: top;'>
				<span id='r_revert_wrap_{$replacement['replacement_key']}'>{$revert}</span>
				<span class='dropdown-button' title='{$this->lang->words['sk_editreplace']}' id='r_{$replacement['replacement_key']}_edit'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/pencil.png' alt='{$this->lang->words['sk_icon']}' /></span>
				<script type='text/javascript'>
					acp.replacements.register('{$replacement['replacement_key']}');
				</script>
			</td>
		</tr>
EOF;
}

$IPBHTML .= <<<EOF
	</table>
	<div id='template_footer'>
		<strong>{$this->lang->words['sk_legend']}:</strong> <img src='{$this->settings['skin_acp_url']}/_newimages/templates/default.png' alt='{$this->lang->words['sk_icon']}' title='{$this->lang->words['sk_l_default']}' />{$this->lang->words['sk_l_default_full']}&nbsp;&nbsp;&nbsp;
		<img src='{$this->settings['skin_acp_url']}/_newimages/templates/modified.png' alt='{$this->lang->words['sk_icon']}' title='{$this->lang->words['sk_l_modified']}' />{$this->lang->words['sk_l_modified_full']}&nbsp;&nbsp;&nbsp;
		<img src='{$this->settings['skin_acp_url']}/_newimages/templates/inherit.png' alt='{$this->lang->words['sk_icon']}' title='{$this->lang->words['sk_l_inherited']}' />{$this->lang->words['sk_l_inherited_full']}&nbsp;&nbsp;&nbsp;
		<img src='{$this->settings['skin_acp_url']}/_newimages/templates/new.png' alt='{$this->lang->words['sk_icon']}' title='{$this->lang->words['sk_l_new']}' />{$this->lang->words['sk_l_new_full']}
	</div>
</div>

EOF;

//--endhtml--//
return $IPBHTML;
}


/**
 * List template groups (skin files)
 *
 * @access	public
 * @param	array 		Template groups
 * @param	array		CSS files
 * @param	array 		Skin set data
 * @return	string		HTML
 */
public function templates_listTemplateGroups( $templateGroups, $css, $setData ) {

$IPBHTML = "";
//--starthtml--//

$_keys    = array_keys( $templateGroups );
$_json    = json_encode( array( 'groups' => $templateGroups ) );
$_first   = array_shift( $_keys );
$_setData = json_encode( $setData );
$_css 	  = json_encode( array( 'css' => $css ) );

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['sk_editingset']}: {$setData['set_name']}</h2>
	<ul class='context_menu'>
		<li><a href='{$this->settings['base_url']}app=core&amp;module=templates&amp;section=skinsets&amp;do=setEdit&amp;set_id={$setData['set_id']}'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/palette_edit.png' alt='{$this->lang->words['sk_icon']}' /> {$this->lang->words['sk_editskinsettings']}</a></li>
		<li><a href='{$this->settings['base_url']}app=core&amp;module=templates&amp;section=replacements&amp;do=list&amp;setID={$setData['set_id']}'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/arrow_switch.png' alt='{$this->lang->words['sk_icon']}' /> {$this->lang->words['sk_editreplacevar']}</a></li>
	</ul>
</div>

EOF;

if( IN_DEV )
{
	$IPBHTML .= <<<EOF
	<h3>Rikki's Magical Marvellously Devilish Debugging Bits</h3>
	<input type='button' id='debug_showArray' value='Log open file array' />
	<input type='button' id='debug_curFile' value='Log current file info' />
	<input type='button' id='debug_modMap' value='Log current modify map' />
	
	<script type='text/javascript'>
		$('debug_showArray').observe('click', function(e)
			{
				Debug.write( acp.template_editor.currentlyOpen.inspect() );
			});
			
		$('debug_curFile').observe('click', function(e)
			{
				Debug.dir( editAreaLoader.getAllFiles('editor_main') );
			});
			
		$('debug_modMap').observe('click', function(e)
			{
				Debug.dir( acp.template_editor.modifyMap );
			});			
	</script>
	
	<br /><br />
EOF;
}

$IPBHTML .= <<<EOF
<link rel="stylesheet" type="text/css" media='screen' href="{$this->settings['skin_acp_url']}/acp_templates.css" />
<script type="text/javascript" src="{$this->settings['js_main_url']}acp.templates.js"></script>
<script type="text/javascript" src="{$this->settings['js_main_url']}acp.tabbed_basic_editor.js"></script>
<!--<script type='text/javascript' src="{$this->settings['js_main_url']}3rd_party/edit_area/edit_area_compressor.php"></script>-->
<div class='acp-box' id='template_editor'>  
	<h3>{$this->lang->words['sk_editingset']} {$setData['set_name']}</h3>
	<div id='template_toolbar'>
		<ul id='editor_buttons'>
			<li id='e_templates' class='left active' title='{$this->lang->words['sk_edittemplates']}'>{$this->lang->words['sk_templates']}</li>
			<li id='e_css' class='left' title='{$this->lang->words['sk_editcss']}'>{$this->lang->words['sk_css']}</li>
		</ul>
		<ul id='document_buttons'>
			<li id='t_save' class='left disabled'>{$this->lang->words['sk_save']}</li>
			<li id='t_status' class='left'></li>
			<!--<li id='t_saveall' class='left disabled'>Save All</li>-->
			<li id='t_revert' class='right disabled'>{$this->lang->words['sk_revert']}</li>
			<li id='t_compare' class='right disabled'>{$this->lang->words['sk_comparediff']}</li>
			<li id='t_variables' class='right disabled'>{$this->lang->words['sk_variables']}</li>
			<li id='t_properties' class='right disabled' style='display: none'>{$this->lang->words['sk_cssprops']}</li>
		</ul>
	</div>
	<div id='left_pane' style='width: 19%; float: left'>
		<div id='template_list_wrap'>
			<div id='menu_template' class='template_menu'>
				<ul>
					<li id='t_add_bit'>{$this->lang->words['sk_addbit']}</li>
				</ul>
			</div>
			<ul id='template_list' class='parts_list'>
			</ul>
		</div>
		<div id='css_list_wrap' style='display: none'>
			<div id='menu_css' class='template_menu'>
				<ul>
					<li id='css_add_css'>{$this->lang->words['sk_addcssfile']}</li>
				</ul>
			</div>
			<ul id='css_list' class='parts_list'>
			</ul>
		</div>
	</div>
	<div id='right_pane' style='width: 80%; float: right'>
		<div id='template_editor'></div>
	</div>
	
	<div id='template_footer'>
		<strong>{$this->lang->words['sk_legend']}:</strong> <img src='{$this->settings['skin_acp_url']}/_newimages/templates/default.png' alt='{$this->lang->words['sk_icon']}' title='{$this->lang->words['sk_l_default']}' />{$this->lang->words['sk_l_default_full']}&nbsp;&nbsp;&nbsp;
		<img src='{$this->settings['skin_acp_url']}/_newimages/templates/modified.png' alt='{$this->lang->words['sk_icon']}' title='{$this->lang->words['sk_l_modified']}' />{$this->lang->words['sk_l_modified_full']}&nbsp;&nbsp;&nbsp;
		<img src='{$this->settings['skin_acp_url']}/_newimages/templates/inherit.png' alt='{$this->lang->words['sk_icon']}' title='{$this->lang->words['sk_l_inherited']}' />{$this->lang->words['sk_l_inherited_full']}&nbsp;&nbsp;&nbsp;
		<img src='{$this->settings['skin_acp_url']}/_newimages/templates/new.png' alt='{$this->lang->words['sk_icon']}' title='{$this->lang->words['sk_l_new']}' />{$this->lang->words['sk_l_new_full']}
	</div>
	
</div>

<script type='text/javascript'>
	ipb.templates['template_group'] = new Template("<li id='#{id}'>#{title}</li>");
	ipb.templates['template_bit'] = new Template("<li id='#{id}'><img src='{$this->settings['skin_acp_url']}/_newimages/templates/#{icon}.png' alt='{$this->lang->words['sk_icon']}' title='#{icon}' /> #{title} <img src='{$this->settings['skin_acp_url']}/_newimages/icons/bullet_delete.png' alt='{$this->lang->words['sk_icon']}' class='delete_icon' style='display: none' id='delete_bit_#{id}' /></li>");
	ipb.templates['css_file'] = new Template("<li id='#{id}'><img src='{$this->settings['skin_acp_url']}/_newimages/templates/#{icon}.png' alt='{$this->lang->words['sk_icon']}' title='#{icon}' /> #{name} <img src='{$this->settings['skin_acp_url']}/_newimages/icons/bullet_delete.png' alt='{$this->lang->words['sk_icon']}' class='delete_icon' style='display: none' id='delete_css_#{id}' /></li>");
	
	/* TEMPLATES FOR POPUPS */
	ipb.templates['form_add_bit'] = "<div class='acp-box'><h3>{$this->lang->words['sk_addbit']}</h3><ul class='acp-form'><li><label for='add_bit_name'>{$this->lang->words['sk_bitname']}:<br /><span class='desctext'>{$this->lang->words['sk_alphanumericonly']}</span></label><input type='text' class='input_text' id='add_bit_name'></li><li><label for='add_bit_group'>{$this->lang->words['sk_group']}:</label><select id='add_bit_group' class='input_select'></select></li><li><label for='add_bit_new_group'>{$this->lang->words['sk_newgroup']}:</label>skin_<input type='text' class='input_text' id='add_bit_new_group'></li><li><label for='add_bit_variables'>{$this->lang->words['sk_datavariables']}</label><input type='text' id='add_bit_variables' class='input_text' /></li></ul><div class='acp-actionbar'><input type='submit' value='{$this->lang->words['sk_add']}' class='realbutton' id='add_bit_submit' /></div></div>";
	
	ipb.templates['form_add_css'] = "<div class='acp-box'><h3>{$this->lang->words['sk_addcssfile']}</h3><ul class='acp-form'><li><label for='add_css_name'>{$this->lang->words['sk_cssname']}:<br /><span class='desctext'>{$this->lang->words['sk_alphanumericonly']}</span></label><input type='text' class='input_text' id='add_css_name' />.css</li></ul><div class='acp-actionbar'><input type='submit' value='{$this->lang->words['sk_add']}' class='realbutton' id='add_css_submit' /></div></div>";
	
	ipb.templates['edit_variables'] = new Template("<div class='acp-box'><h3>{$this->lang->words['sk_editvariables']}</h3><ul class='acp-form'><li><textarea class='input_text' id='variables_#{id}' rows='5' cols='30' style='width: 98%'>#{value}</textarea></li></ul><div class='acp-actionbar'><input type='submit' value='{$this->lang->words['um_savechanges']}' class='realbutton' id='edit_variables_#{id}' /></div></div>");
	
	ipb.templates['css_properties'] = new Template("<div class='acp-box'><h3>{$this->lang->words['sk_editcssprops']}</h3><ul class='acp-form'><li><label for='cssposition_#{id}'>{$this->lang->words['sk_cssposition']}</label><select id='cssposition_#{id}'>#{cssposition}</select><br /><span class='desctext' style='margin-left: 10px;'>{$this->lang->words['sk_cssposition_desc']}</span></li><li><label for='cssattributes_#{id}'>{$this->lang->words['sk_cssattributes']}</label><input type='text' class='input_text' size='35' id='cssattributes_#{id}' value='#{attributes}' /><br /><span class='desctext' style='margin-left: 10px;'>{$this->lang->words['sk_cssattributes_desc']}</span></li><li><label for='cssapp_#{id}'>{$this->lang->words['sk_cssapp']}</label><input type='text' class='input_text' size='15' id='cssapp_#{id}' value='#{app}' /><br /><span class='desctext' style='margin-left: 10px;'>{$this->lang->words['sk_cssapp_desc']}</span></li><li><label for='cssmodules_#{id}'>{$this->lang->words['sk_cssmodules']}</label><input type='text' class='input_text' size='15' id='cssmodules_#{id}' value='#{modules}' /><br /><span class='desctext' style='margin-left: 10px;'>{$this->lang->words['sk_cssmodules_desc']}</span></li><li style='padding-left: 15px;'><input type='checkbox' id='cssapphide_#{id}' value='1' #{apphide} /> &nbsp;<strong>{$this->lang->words['sk_cssapphide']}</strong><br /><span class='desctext'>{$this->lang->words['sk_cssapphide_desc']}</span></li></ul><div class='acp-actionbar'><input type='submit' value='{$this->lang->words['sk_updateproperties']}' class='realbutton' id='save_properties_#{id}' /></div></div>");
	
	acp.tabbedEditor.wrapId = 'right_pane';
	acp.tabbedEditor.callbacks['open']   = acp.template_editor.CALLBACK_editor_loaded;
	acp.tabbedEditor.callbacks['close']  = acp.template_editor.CALLBACK_template_closed;
	acp.tabbedEditor.callbacks['switch'] = acp.template_editor.CALLBACK_file_switch;
	acp.tabbedEditor.initialize();
	

	acp.template_editor.templateGroups       = $_json;
	acp.template_editor.currentTemplateGroup = '{$_first}';
	acp.template_editor.currentSetData       = $_setData;
	acp.template_editor.cssFiles			 = $_css;
	acp.template_editor.initialize();
</script>

<div style='clear: both'></div>
EOF;

//--endhtml--//
return $IPBHTML;
}


/**
 * Form to import/export skin sets, replacements, images
 *
 * @access	public
 * @param	array 		Skin sets
 * @param	array		Form data
 * @param	array 		Warnings
 * @return	string		HTML
 */
public function importexport_form( $sets, $form, $warnings ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF

<script type="text/javascript">
//<![CDATA[
document.observe("dom:loaded",function() 
{
ipbAcpTabStrips.register('tabstrip_importexport');
ipbAcpTabStrips.doToggle($('tabtab-1'));
});
 //]]>
</script>

<div class='section_title'>
	<h2>{$this->lang->words['ss_importexport']}</h2>
</div>

<ul id='tabstrip_importexport' class='tab_bar no_title'>
	<li id='tabtab-1'>{$this->lang->words['sk_import']}</li>
	<li id='tabtab-2'>{$this->lang->words['sk_export']}</li>
</ul>

<div id='tabpane-1'>
	<div class='acp-box'>
		<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=importSet' enctype="multipart/form-data" id='import1' method='POST'>
			<input name="MAX_FILE_SIZE" value="10000000000" type="hidden">
			<table width='100%' cellpadding='0' cellspacing='0' class='alternate_rows double_pad' style='margin-bottom: 10px;'>
				<tr>
					<th colspan='2'>{$this->lang->words['sk_importskinset']}</th>
				</tr>
EOF;
				if ( $warnings['importSkinCacheDir'] )
				{
					$IPBHTML .= <<<EOF
						<tr>
							<td colspan='2'><div class='warning'>{$this->lang->words['sk_fail_cache']}</div></td>
						</tr>
EOF;
				}
				
				$IPBHTML .= <<<EOF
				<tr>
					<td style='width: 40%'>
						<strong>{$this->lang->words['sk_uploadxmlarchive']}</strong><br />
						<span class='desctext'>{$this->lang->words['sk_xmlorxmlgz']}</span>
					</td>
					<td style='width: 60%'>
						{$form['uploadField']}
					</td>
				</tr>
				<tr>
					<td>
						<strong>{$this->lang->words['sk_ornamearchive']}</strong><br />
						<span class='desctext'>{$this->lang->words['sk_intoroot']}</span>
					</td>
					<td>
						{$form['importLocation']}
					</td>
				</tr>
				<tr>
					<td>
						<strong>{$this->lang->words['sk_newsetname']}</strong><br />
						<span class='desctext'>{$this->lang->words['sk_leaveblank']}</span>
					</td>
					<td>
						{$form['importName']}
					</td>
				</tr>
				<tr>
					<td>
						<strong>{$this->lang->words['sk_useimageset']}</strong><br />
						<span class='desctext'>{$this->lang->words['sk_useimageset_info']}</span>
					</td>
					<td>
						{$form['importImgDirs']}
					</td>
				</tr>
				<tr>
					<td colspan='2' class='acp-actionbar'><input type='submit' value='{$this->lang->words['sk_importskinset']}' class='realbutton' /></td>
				</tr>
			</table>
		</form>
		
		<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=importImages' enctype="multipart/form-data" id='import1' method='POST'>
			<input name="MAX_FILE_SIZE" value="10000000000" type="hidden">
			<table width='100%' cellpadding='0' cellspacing='0' class='alternate_rows double_pad' style='margin-bottom: 10px;'>
				<tr>
					<th colspan='2'>{$this->lang->words['sk_importimgset']}</th>
				</tr>
EOF;
				if ( $warnings['importImgDir'] )
				{
					$IPBHTML .= <<<EOF
						<tr>
							<td colspan='2'><div class='warning'>{$this->lang->words['sk_fail_images']}</div></td>
						</tr>
EOF;
				}
				
				$IPBHTML .= <<<EOF
				<tr>
					<td style='width: 40%'>
						<strong>{$this->lang->words['sk_uploadimgxml']}</strong><br />
						<span class='desctext'>{$this->lang->words['sk_xmlorxmlgz']}</span>
					</td>
					<td style='width: 60%'>
						{$form['uploadField']}
					</td>
				</tr>
				<tr>
					<td>
						<strong>{$this->lang->words['sk_ornamearchive']}</strong><br />
						<span class='desctext'>{$this->lang->words['sk_intoroot']}</span>
					</td>
					<td>
						{$form['importLocation']}
					</td>
				</tr>
				<tr>
					<td>
						<strong>{$this->lang->words['sk_newimgsetname']}</strong><br />
						<span class='desctext'>{$this->lang->words['sk_leaveblank']}</span>
					</td>
					<td>
						{$form['importName']}
					</td>
				</tr>
				<tr>
					<td>
						<strong>{$this->lang->words['sk_applytoskin']}</strong><br />
						<span class='desctext'>{$this->lang->words['sk_applytoskin_info']}</span>
					</td>
					<td>
						<select name='setID'><option value='0'>-{$this->lang->words['sk_none']}-</option>{$sets}</select>
					</td>
				</tr>
				<tr>
					<td colspan='2' class='acp-actionbar'><input type='submit' value='{$this->lang->words['sk_importimgset']}' class='realbutton'/></td>
				</tr>
			</table>
		</form>
		
		<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=importReplacements' enctype="multipart/form-data" id='import1' method='POST'>
			<input name="MAX_FILE_SIZE" value="10000000000" type="hidden">
			<table width='100%' cellpadding='0' cellspacing='0' class='alternate_rows double_pad'>
				<tr>
					<th colspan='2'>{$this->lang->words['sk_importreplacements']}</th>
				</tr>
				<tr>
					<td style='width: 40%'>
						<strong>{$this->lang->words['sk_uploadxmlreplace']}</strong><br />
						<span class='desctext'>{$this->lang->words['sk_xmlorxmlgz']}</span>
					</td>
					<td style='width: 60%'>
						{$form['uploadField']}
					</td>
				</tr>
				<tr>
					<td>
						<strong>{$this->lang->words['sk_ornamearchive']}</strong><br />
						<span class='desctext'>{$this->lang->words['sk_intoroot']}</span>
					</td>
					<td>
						{$form['importLocation']}
					</td>
				</tr>
				<tr>
					<td>
						<strong>{$this->lang->words['sk_applytoskin']}</strong>
					</td>
					<td>
						<select name='setID'>{$sets}</select>
					</td>
				</tr>
				<tr>
					<td colspan='2' class='acp-actionbar'><input type='submit' value='{$this->lang->words['sk_importreplacements']}' class='realbutton'/></td>
				</tr>
			</table>
		</form>
	</div>
</div>
	
<div id='tabpane-2' class='formmain-background'>
	<div class='acp-box'>
		<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=exportSet' id='export1' method='POST'>
			<table width='100%' cellpadding='0' cellspacing='0' class='alternate_rows double_pad' style='margin-bottom: 10px;'>
				<tr>
					<th colspan='2'>{$this->lang->words['sk_exporttemplates']}</th>
				</tr>
				<tr>
					<td style='width: 40%'>
						<strong>{$this->lang->words['sk_skinset']}</strong><br />
						<span class='desctext'>{$this->lang->words['sk_xs_info']}</span>
					</td>
					<td style='width: 60%'>
						<select name='setID'>{$sets}</select>
					</td>
				</tr>
				<tr>
					<td valign='top'>
						<strong>{$this->lang->words['sk_for_apps']}</strong><br />
						<span class='desctext'>{$this->lang->words['sk_for_apps_desc']}</span>
					</td>
					<td>
						<p><input type='checkbox' name='exportApps[core]' value='1' checked='checked' /> IP.Board <em>({$this->lang->words['export_inc_cal_etc']})</em></p>
EOF;

foreach( ipsRegistry::$applications as $appDir => $app_data )
{
	if ( $appDir != 'core' AND $appDir != 'forums' AND $appDir != 'members'  AND $appDir != 'calendar'  AND $appDir != 'portal' AND $appDir != 'chat' )
	{
		$IPBHTML .= "<p><input type='checkbox' name='exportApps[{$appDir}]' value='1'  checked='checked' /> {$app_data['app_title']}</p>\n";
	}
}

$IPBHTML .= <<<EOF
					</td>
				</tr>
				<tr>
					<td>
						<strong>{$this->lang->words['a_options']}</strong><br />
						<span class='desctext'>{$this->lang->words['sk_xs_info2']}</span>
					</td>
					<td>
						{$form['exportSetOptions']}
					</td>
				</tr>
				<tr>
					<td colspan='2' class='acp-actionbar'><input type='submit' value='{$this->lang->words['sk_exporttemplates']}' class='realbutton'/></td>
				</tr>
			</table>
		</form>
		
		<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=exportImages' id='export2' method='POST'>
			<table width='100%' cellpadding='0' cellspacing='0' class='alternate_rows double_pad' style='margin-bottom: 10px;'>
				<tr>
					<th colspan='2'>{$this->lang->words['sk_exportimages']}</th>
				</tr>
				<tr>
					<td style='width: 40%'>
						<strong>{$this->lang->words['sk_imageset']}</strong><br />
						<span class='desctext'>{$this->lang->words['sk_xr_info']}</span>
					</td>
					<td style='width: 60%'>
						<select name='setID'>{$sets}</select>
					</td>
				</tr>
				<tr>
					<td colspan='2' class='acp-actionbar'><input type='submit' value='{$this->lang->words['sk_exportimages']}' class='realbutton'/></td>
				</tr>
			</table>
		</form>
		
		<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=exportReplacements' id='export3' method='POST'>
			<table width='100%' cellpadding='0' cellspacing='0' class='alternate_rows double_pad'>
				<tr>
					<th colspan='2'>{$this->lang->words['sk_exportreplaces']}</th>
				</tr>
				<tr>
					<td style='width: 40%'>
						<strong>{$this->lang->words['sk_fromskinset']}</strong><br />
						<span class='desctext'>{$this->lang->words['sk_xr_info']}</span>
					</td>
					<td style='width: 60%'>
						<select name='setID'>{$sets}</select>
					</td>
				</tr>
				<tr>
					<td colspan='2' class='acp-actionbar'><input type='submit' value='{$this->lang->words['sk_exportreplaces']}' class='realbutton'/></td>
				</tr>
			</table>
		</form>
	</div>
</div>

EOF;

//--endhtml--//
return $IPBHTML;
}

}