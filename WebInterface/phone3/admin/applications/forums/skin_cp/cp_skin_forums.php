<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Forums skin functions
 * Last Updated: $LastChangedDate: 2009-08-25 16:41:08 -0400 (Tue, 25 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Forums
 * @link		http://www.
 * @since		14th May 2003
 * @version		$Rev: 5045 $
 */
 
class cp_skin_forums extends output
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
 * Forum wrapper
 *
 * @access	public
 * @param	string	Content
 * @param	array 	Forum data
 * @param	bool	Show buttons or not
 * @return	string	HTML
 */
public function forumWrapper( $content, $r, $show_buttons=1 ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='tableborder isDraggable' id='cat_{$r['id']}' style='margin-bottom: 10px;'>
 	<div class='tableheaderalt'>
HTML;
$IPBHTML .= <<<HTML
<table cellpadding='0' cellspacing='0' border='0' class='header'>
	<tr>
  		<td align='left' width='95%' title='{$this->lang->words['frm_id']}{$r['id']}'>
HTML;

	if( !$this->request['showall'] )
	{
		$IPBHTML .= <<<HTML
  			<div class='draghandle'><img src='{$this->settings['skin_acp_url']}/_newimages/drag_icon.png' alt='drag' /></div>
HTML;
	}

$IPBHTML .= <<<HTML
		  	{$r['name']}
  		</td>
  		<td align='right' width='5%' nowrap='nowrap'>
HTML;
		if ( $show_buttons )
		{
			$IPBHTML .= <<<HTML
			<img class='ipbmenu' id="menum-{$r['id']}" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt='{$this->lang->words['frm_options']}' /> &nbsp;
			<ul class='acp-menu' id='menum-{$r['id']}_menucontent'>
				<li class='icon add'><a href='{$this->settings['base_url']}{$this->form_code}do=forum_add&amp;p={$r['id']}'>{$this->lang->words['frm_newforum']}</a></li>
				<li class='icon edit'><a href='{$this->settings['base_url']}{$this->form_code}do=edit&amp;f={$r['id']}'>{$this->lang->words['frm_editsettings']}</a></li>
				<li class='icon delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}{$this->form_code}do=delete&amp;f={$r['id']}");'>{$this->lang->words['frm_deletecat']}</a></li>
				<li class='icon view'><a href='{$this->settings['base_url']}{$this->form_code}do=skinedit&amp;f={$r['id']}'>{$this->lang->words['frm_skinopt']}</a></li>
			</ul>
HTML;
		}
		$IPBHTML .= <<<HTML
 		</td>
 	</tr>
</table>
 </div>
 	<div id='cat_{$r['id']}_container'>
 		{$content}
 	</div>
	<script type="text/javascript">
		dropItLikeItsHot{$r['id']} = function( draggableObject, mouseObject )
		{
			var options = {
							method : 'post',
							parameters : Sortable.serialize( 'cat_{$r['id']}_container', { tag: 'div', name: 'forums' } )
						};
 
			new Ajax.Request( "{$this->settings['base_url']}&{$this->form_code_js}&do=doreorder&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' ), options );

			return false;
		};

		Sortable.create( 'cat_{$r['id']}_container', { tag: 'div', only: 'isDraggable', revert: true, format: 'forum_([0-9]+)', onUpdate: dropItLikeItsHot{$r['id']}, handle: 'draghandle' } );
	</script>
</div>

HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Display forum header
 *
 * @access	public
 * @return	string	HTML
 */
public function renderForumHeader( $nav ) {

$IPBHTML = "";
//--starthtml--//

if( is_array( $nav ) && count( $nav ) )
{
$IPBHTML .= <<<HTML
<div class='navstrip'>
	<a href='{$this->settings['base_url']}{$this->form_code}'>{$this->lang->words['for_forumscap']}</a>
HTML;

	foreach( $nav as $n )
	{
$IPBHTML .= <<<HTML
	 &gt; <a href='{$this->settings['base_url']}{$this->form_code}{$n[1]}'>{$n[0]}</a>
HTML;
	}

$IPBHTML .= <<<HTML
</div><br />
HTML;
}

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['for_title']}</h2>
	<ul class='context_menu'>
		<li>
			<a href='{$this->settings['base_url']}{$this->form_code}module=forums&amp;do=forum_add&amp;forum_id={$forum_id}&amp;type=forum'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/add.png' alt='' />
				{$this->lang->words['forums_context_add_forum']}
			</a>
		</li>
		<li>
			<a href='{$this->settings['base_url']}{$this->form_code}module=forums&amp;do=forum_add&amp;forum_id={$forum_id}&amp;type=category'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/folder_add.png' alt='' />
				{$this->lang->words['forums_context_add_category']}
			</a>
		</li>
	</ul>
</div>

<script type='text/javascript' src='{$this->settings['js_app_url']}acp.forums.js'></script>
<div class='taboff'>
HTML;
if ( ipsRegistry::$request['showall'] )
{
$IPBHTML .= <<<HTML
<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;showall=0'>{$this->lang->words['frm_showtier']}</a>
HTML;
}
else
{
$IPBHTML .= <<<HTML
<a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;showall=1'>{$this->lang->words['frm_showall']}</a>
HTML;
}
$IPBHTML .= <<<HTML
</div>
<div class='taboff'><a href='#' onclick='return ACPForums.toggleModOptions();' id='togglemod'>{$this->lang->words['frm_modshow']}</a></div>
<br clear='all' />
<div class='acp-box' id='forum_wrapper'>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Display single forum moderator entry
 *
 * @access	public
 * @param	array 	Moderator data
 * @param	integer	Forum ID
 * @return	string	HTML
 */
public function renderModeratorEntry( $data='', $forum_id=0 ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='ipbmenu realbutton' style='float:left;width:auto;white-space:nowrap;margin-right:3px;' id="modmenu{$data['randId']}">{$data['_fullname']} <img src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt='{$this->lang->words['frm_options']}' /></div>
<ul class='acp-menu' id='modmenu{$data['randId']}_menucontent'>
	<li class='icon delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}section=moderator&amp;act=mod&amp;do=remove&amp;mid={$data['mid']}&amp;fid=all");'>{$this->lang->words['frm_modremoveall']}</a></li>
	<li class='icon delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}section=moderator&amp;act=mod&amp;do=remove&amp;mid={$data['mid']}&amp;fid={$forum_id}");'>{$this->lang->words['frm_modremove']}</a></li>
	<li class='icon edit'><a href='{$this->settings['base_url']}section=moderator&amp;act=mod&amp;do=edit&amp;mid={$data['mid']}'>{$this->lang->words['frm_modedit']}</a></li>
</ul>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Display forum footer
 *
 * @access	public
 * @param	string	Forum dropdown
 * @param	string	Options HTML for member groups
 * @return	string	HTML
 */
public function renderForumFooter( $choose="", $mem_group ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
</div>
<script type="text/javascript">
dropItLikeItsHot = function( draggableObject, mouseObject )
{
	var options = {
					method : 'post',
					parameters : Sortable.serialize( 'forum_wrapper', { tag: 'div', name: 'forums' } )
				};
 
	new Ajax.Request( "{$this->settings['base_url']}&{$this->form_code_js}&do=doreorder&md5check={$this->registry->adminFunctions->getSecurityKey()}".replace( /&amp;/g, '&' ), options );

	return false;
};

Sortable.create( 'forum_wrapper', { tag: 'div', only: 'isDraggable', revert: true, format: 'cat_([0-9]+)', onUpdate: dropItLikeItsHot, handle: 'draghandle' } );
</script>
<form method='post' action='{$this->settings['base_url']}module=forums&amp;section=moderator&amp;do=add' onsubmit='return ACPForums.submitModForm()'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
	<input type='hidden' name='modforumids' id='modforumids' />
	
	<div class='tableborder'>
		<table cellpadding='2' cellspacing='0' width='100%' border='0' class='tablerow1'>
			<tr>
				<td valign='middle'>{$this->lang->words['frm_modaddtxt']}</td>
				<td>{$this->lang->words['frm_modname']}<input class='realbutton nohand' type='text' name='name' id='modUserName' size='20' value='' /> {$this->lang->words['frm_modorgroup']} {$mem_group}</td>
				<td width='1%' valign='middle'><input type='submit' class='realbutton' value='{$this->lang->words['frm_gogogadgetflow']}' /></td>
			</tr>
		</table>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Render a forum row
 *
 * @access	public
 * @param	string	Description
 * @param	array 	Forum data
 * @param	string	Depth guide
 * @param	string	Skin used
 * @return	string	HTML
 */
public function renderForumRow( $desc, $r, $depth_guide, $skin ) {

$IPBHTML = "";
//--starthtml--//

if( $depth_guide )
{
	$IPBHTML .= <<<HTML
	<div class='isDraggable forum_row subforum' id='forum_{$r['id']}'>
HTML;
}
else
{
	$IPBHTML .= <<<HTML
	<div class='isDraggable forum_row' id='forum_{$r['id']}'>
HTML;
}

$IPBHTML .= <<<HTML
<table style='width: 100%' cellspacing='0' class='double_pad'>
	<tr>
HTML;

if( !$this->request['showall'] )
{
	$IPBHTML .= <<<HTML
		<td style='width: 2%'>
			<div class='draghandle'><img src='{$this->settings['skin_acp_url']}/_newimages/drag_icon.png' alt='drag' /></div>
		</td>
HTML;
}

	if( $depth_guide )
	{
		$IPBHTML .= <<<HTML
		<td style='padding-left: 14px'>
			{$depth_guide}
		</td>
HTML;
	}
	
	$IPBHTML .= <<<HTML
 		<td style='width: 90%'>
HTML;
if ( $r['id'] == $this->settings['forum_trash_can_id'] )
{
$IPBHTML .= <<<HTML
 			<img src='{$this->settings['skin_acp_url']}/images/acp_trashcan.gif' border='0' title='{$this->lang->words['frm_thistrash']}' />
HTML;
}

$IPBHTML .= <<<HTML
<strong class='forum_name'>{$r['name']}</strong>
HTML;

if( $desc )
{
	$IPBHTML .= <<<HTML
		<br /><span class='forum_desc'>{$desc}</span>
HTML;
}

if ( ($r['skin_id'] != "") and ($r['skin_id'] > 0) )
{
$IPBHTML .= <<<HTML
<br />[ Using Skin Set: {$skin} ]
HTML;
}

$IPBHTML .= <<<HTML
HTML;


if ( $r['_modstring'] != "" )
{
$IPBHTML .= <<<HTML
<div style='display:none' class='moddiv'><fieldset style='padding:4px;height:45px'><legend>{$this->lang->words['frm_moderators']}</legend>{$r['_modstring']}</fieldset></div>
HTML;
}

$IPBHTML .= <<<HTML
 </td>
 <td style='width:5%' nowrap='nowrap'>
 	<input type='checkbox' title='{$this->lang->words['frm_modcheck']}' id='id_{$r['id']}' value='1' /> 
 	<img class='ipbmenu' id="menu{$r['id']}" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt='{$this->lang->words['frm_options']}' />
	<ul class='acp-menu' id='menu{$r['id']}_menucontent'>
		<li class='icon edit'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=edit&amp;f={$r['id']}'>{$this->lang->words['frm_editsettings']}</a></li>
		<li class='icon info'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=pedit&amp;f={$r['id']}'>{$this->lang->words['frm_permissions']}</a></li>
		<li class='icon delete'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=empty&amp;f={$r['id']}'>{$this->lang->words['frm_emptyforum']}</a></li>
		<li class='icon delete'><a href='#' onclick='return acp.confirmDelete("{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=delete&amp;f={$r['id']}");'>{$this->lang->words['frm_deleteforum']}</a></li>
		<li class='icon edit'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=frules&amp;f={$r['id']}'>{$this->lang->words['frm_forumrules']}</a></li>
		<li class='icon edit'><a href='{$this->settings['base_url']}&amp;module=tools&amp;section=tools&amp;do=clearforumsubs&amp;f={$r['id']}'>{$this->lang->words['m_clearsubs']}</a></li>
		<li class='icon view'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=skinedit&amp;f={$r['id']}'>{$this->lang->words['frm_skinopt']}</a></li>
		<li class='icon info'><a href='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=recount&amp;f={$r['id']}'>{$this->lang->words['frm_resync']}</a></li>
	</ul>
 </td>
</tr>
</table>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Display "no forums" row
 *
 * @access	public
 * @param	integer	Parent ID
 * @return	string	HTML
 */
public function renderNoForums( $parent_id ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<tr>
 <td class='tablerow1' width='100%' colspan='2'>
	{$this->lang->words['frm_noforums']}
	<div class='graytext'><a href='{$this->settings['base_url']}&{$this->form_code}&do=forum_add&p={$parent_id}'>{$this->lang->words['frm_noforumslink']}</a></div>
 </td>
</tr>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Display forum permissions matrix
 *
 * @access	public
 * @param	array 	Forum data
 * @param	array 	Relative links
 * @param	string	Matrix HTML
 * @param	array 	..of Forum Data
 * @return	string	HTML
 */
public function forumPermissionForm( $forum, $relative, $perm_matrix, $forumData=array() ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['head_forum_permissions']} {$forumData['name']}</h2>
</div>

<form name='theAdminForm' id='adminform' action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=pdoedit&amp;f={$this->request['f']}&amp;name={$forum['name']}&amp;nextid={$relative['next']}&amp;previd={$relative['previous']}' method='post'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
	{$perm_matrix }
	
	<div class='acp-box'>
		<div class='acp-actionbar'>
			<div class='centeraction'>

HTML;
if ( $relative['next'] > 0 )
{
$IPBHTML .= <<<HTML
				<input type='submit' name='donext' value='{$this->lang->words['frm_savenext']}' class='button primary' /> 
HTML;
}
$IPBHTML .= <<<HTML
				<input type='submit' value='{$this->lang->words['frm_saveonly']}' class='button primary' /> 
				<input type='submit' name='reload' value='{$this->lang->words['frm_savereload']}' class='button primary' /> 
HTML;
if ( $relative['next'] > 0 )
{
$IPBHTML .= <<<HTML
				<input type='submit' name='doprevious' value='{$this->lang->words['frm_saveprev']}' class='button primary' />
HTML;
}
$IPBHTML .= <<<HTML
			</div>
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Display forum rules form
 *
 * @access	public
 * @param	integer	Forum ID
 * @param	array 	Forum data
 * @return	string	HTML
 */
public function forumRulesForm( $id, $data )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['forum_rules_head']} '{$data['name']}'</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm' onclick='return ValidateForm();' id='postingform'>
	<input type='hidden' name='do' value='dorules' />
	<input type='hidden' name='f' value='{$id}' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
	<div class='acp-box'>
		<h3>{$this->lang->words['frm_rulessetup']}</h3>
		
		<ul class='acp-form alternate_rows'>
			<li>
				<label>{$this->lang->words['frm_rulesdisplay']}</label>
				{$data['_show_rules']}
			</li>
			<li>
				<label>{$this->lang->words['frm_rulestitle']}</label>
				{$data['_title']}
			</li>
			<li>
				<label>{$this->lang->words['frm_rulestext']}</label>
			</li>
			<li>{$data['_editor']}</li>
		</ul>
		
		<div class='acp-actionbar'>
			<div class='centeraction'>
				<input type='submit' value='{$this->lang->words['frm_rulesbutton']}' class='button primary' accesskey='s'>
			</div>
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Display forum skin options form
 *
 * @access	public
 * @param	integer	Forum id
 * @param	array 	Forum data
 * @return	string	HTML
 */
public function forumSkinOptions( $id, $data )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['modify_skin_head']}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm' id='theAdminForm'>
	<input type='hidden' name='do' value='doskinedit' />
	<input type='hidden' name='f' value='{$id}' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['frm_skinchoice']}{$data['name']}</h3>

		<ul class='acp-form alternate_rows'>
			<li>
				<label>{$this->lang->words['frm_skinapply']}</label>
				{$data['fsid']}
			</li>
			<li>
				<label>{$this->lang->words['frm_skinsub']}</label>
				{$data['apply_to_children']}
			</li>
		</ul>
		
		<div class='acp-actionbar'>
			<div class='centeraction'>
				<input type='submit' value='{$this->lang->words['frm_skinbutton']}' class='button primary' accesskey='s'>
			</div>
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
	
}

/**
 * Display form to empty a forum
 *
 * @access	public
 * @param	integer	Forum id
 * @param	array 	Forum data
 * @return	string	HTML
 */
public function forumEmptyForum( $id, $forum )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['frm_emptytitle']} '{$forum['name']}'</h2>
</div>
<p class='message error'>
	{$this->lang->words['for_empty_msg']}
</p>
<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm'  id='theAdminForm'>
	<input type='hidden' name='do' value='doempty' />
	<input type='hidden' name='f' value='{$id}' />
	<input type='hidden' name='name' value='{$forum['name']}' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['frm_emptytitle']}'{$forum['name']}'</h3>

		<ul class='acp-form alternate_rows'>
			<li>
				<label>{$this->lang->words['frm_emptywhich']}</label>
				{$forum['name']}
			</li>
		</ul>
		
		<div class='acp-actionbar'>
			<div class='centeraction'>
				<input type='submit' value='{$this->lang->words['frm_emptybutton']}' class='button primary' accesskey='s'>
			</div>
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Display form to delete a forum
 *
 * @access	public
 * @param	integer	Forum ID
 * @param	string	Name
 * @param	string	Options HTML for move to dropdown
 * @return	string	HTML
 */
public function forumDeleteForm( $id, $name, $move )
{
$IPBHTML = "";
//--starthtml--//

$text	= $is_cat ? $this->lang->words['for_iscat_y'] : $this->lang->words['for_iscat_n'];
$title	= sprintf( $this->lang->words['for_removing'], $text, $name );

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$title}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm'  id='theAdminForm'>
	<input type='hidden' name='do' value='dodelete' />
	<input type='hidden' name='f' value='{$id}' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['frm_deletetitle']}{$name}</h3>

		<ul class='acp-form alternate_rows'>
			<li>
				<label>{$this->lang->words['frm_deletewhich']}</label>
				{$name}
			</li>
HTML;

if( $move )
{
	$IPBHTML .= <<<HTML
			<li>
				<label>{$this->lang->words['frm_deletemove']}</label>
				{$move}
			</li>
HTML;
}

$IPBHTML .= <<<HTML
		</ul>
		
		<div class='acp-actionbar'>
			<div class='centeraction'>
				<input type='submit' value='{$this->lang->words['frm_deletebutton']}' class='button primary' accesskey='s'>
			</div>
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;	
}

/**
 * Display form to add/edit a forum
 *
 * @access	public
 * @param	array 	Form fields
 * @param	string	Button text
 * @param	string	Action code
 * @param	string	Title
 * @param	string	Button text (again?)
 * @param	array 	Forum data
 * @param	string	Permissions matrix
 * @return	string	HTML
 */
public function forumForm( $form, $button, $code, $title, $forum, $perm_matrix ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>$title</h2>
</div>

<script type='text/javascript' src='{$this->settings['js_app_url']}acp.forums.js'></script>
<form name='theAdminForm' id='adminform' action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do={$code}&amp;f={$this->request['f']}&amp;name={$forum['name']}' method='post'>
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
	<input type='hidden' name='convert' id='convert' value='0' />
	<input type='hidden' name='type' value='{$this->request['type']}' />
	
	<div class='acp-box'>
		<h3>$title</h3>
		
 		<ul class='acp-form alternate_rows'>
			<li><label class='head'>{$this->lang->words['frm_f_basic']}</label></li>
    
    		<li>
   				<label>{$this->lang->words['frm_f_name_' . $form['addnew_type'] ]}</label>
   				{$form['name']}
 			</li>
HTML;

if( $form['addnew_type'] != 'category' )
{
$IPBHTML .= <<<HTML
		 	<li>
		   		<label>{$this->lang->words['frm_f_desc']}<span class='desctext'>{$this->lang->words['frm_f_desc_info']}</span></label>
		   		{$form['description']}
		 	</li>
		
		 	<li>
		   		<label>{$this->lang->words['frm_f_parent']}</label>
		   		{$form['parent_id']}
		 	</li>
		 	<li>
		   		<label>{$this->lang->words['frm_f_state']}</label>
		   		{$form['status']}
		 	</li> 	
		 	<li>
		   		<label>{$this->lang->words['frm_f_ascat']}<span class='desctext'>{$this->lang->words['frm_f_ascat_info']}</span></label>
		   		{$form['sub_can_post']}
		 	</li>

			<li><label class='head'>{$this->lang->words['frm_f_redirect']}</label></li>
    
			<li>
				<label>{$this->lang->words['frm_f_redirect_url']}</label>
				{$form['redirect_url']}
			</li>
			<li>
				<label>{$this->lang->words['frm_f_redirect_en']}<span class='desctext'>{$this->lang->words['frm_f_redirect_en_info']}</span></label>
				{$form['redirect_on']}
			</li>
			<li>
				<label>{$this->lang->words['frm_f_redirect_num']}</label>
				{$form['redirect_hits']}
			</li>

			<li><label class='head'>{$this->lang->words['frm_f_perm_title']}</label></li>
    
			<li>
				<label>{$this->lang->words['frm_f_perm_hide']}<span class='desctext'>{$this->lang->words['frm_f_perm_hide_info']}</span></label>
				{$form['hide_last_info']}
			</li>
			<li>
				<label>{$this->lang->words['frm_f_perm_list']}<span class='desctext'>{$this->lang->words['frm_f_perm_list_info']}</span></label>
				{$form['permission_showtopic']}
			</li>
			<li>
				<label>{$this->lang->words['frm_f_perm_cust']}<span class='desctext'>{$this->lang->words['frm_f_perm_cust_info']}</span></label>
				{$form['permission_custom_error']}
			</li>
			
			<li><label class='head'>{$this->lang->words['frm_f_post_title']}</label></li>
			
			<li>
				<label>{$this->lang->words['frm_f_post_html']}<span class='desctext'>{$this->lang->words['frm_f_post_html_info']}</span></label>
				{$form['use_html']}
			</li>
			<li>
				<label>{$this->lang->words['frm_f_post_bb']}</label>
				{$form['use_ibc']}
			</li>
			<li>
				<label>{$this->lang->words['frm_f_post_qreply']}</label>
				{$form['quick_reply']}
			</li>
			<li>
				<label>{$this->lang->words['frm_f_post_poll']}</label>
				{$form['allow_poll']}
			</li>
			<li>
				<label>{$this->lang->words['frm_f_post_bump']}<span class='desctext'>{$this->lang->words['frm_f_post_bump_info']}</span></label>
				{$form['allow_pollbump']}
			</li>
			<li>
				<label>{$this->lang->words['frm_f_post_rate']}</label>
				{$form['forum_allow_rating']}
			</li>
			<li>
				<label>{$this->lang->words['frm_f_post_inc']}<span class='desctext'>{$this->lang->words['frm_f_post_inc_info']}</span></label>
				{$form['inc_postcount']}
			</li>
			<li>
				<label>{$this->lang->words['frm_f_min_posts_post']}<span class='desctext'>{$this->lang->words['frm_f_min_posts_post_info']}</span></label>
				{$form['min_posts_post']}
			</li>
			<li>
				<label>{$this->lang->words['frm_f_min_posts_view']}<span class='desctext'>{$this->lang->words['frm_f_min_posts_view_info']}</span></label>
				{$form['min_posts_view']}
			</li>
			<li>
				<label>{$this->lang->words['frm_canviewothers']}</label>
				{$form['can_view_others']}
			</li>
			
			<li><label class='head'>{$this->lang->words['frm_f_mod_title']}</label></li>
			    
			<li>
				<label>{$this->lang->words['frm_f_mod_en']}<span class='desctext'>{$this->lang->words['frm_f_mod_en_info']}</span></label>
				{$form['preview_posts']}
			</li>
			<li>
				<label>{$this->lang->words['frm_f_mod_email']}<span class='desctext'>{$this->lang->words['frm_f_mod_email_info']}</span></label>
				{$form['notify_modq_emails']}
			</li>
			<li>
				<label>{$this->lang->words['frm_f_mod_pass']}<span class='desctext'>{$this->lang->words['frm_f_mod_pass_info']}</span></label>
				{$form['password']}
			</li>
			<li>
				<label>{$this->lang->words['frm_f_mod_exempt']}<span class='desctext'>{$this->lang->words['frm_f_mod_exempt_info']}</span></label>
				{$form['password_override']}
			</li> 	
    		
			<li><label class='head'>{$this->lang->words['frm_f_sort_title']}</label></li>
    
			<li>
				<label>{$this->lang->words['frm_f_sort_cutoff']}</label>
				{$form['prune']}
			</li>
			<li>
				<label>{$this->lang->words['frm_f_sort_key']}</label>
				{$form['sort_key']}
			</li>
			<li>
				<label>{$this->lang->words['frm_f_sort_order']}</label>
				{$form['sort_order']}
			</li>
			<li>
				<label>{$this->lang->words['frm_f_sort_filter']}</label>
				{$form['topicfilter']}
			</li>
HTML;
}
else
{
$IPBHTML .= <<<HTML
		</ul>
	</div>
	
	<input type='hidden' name='parent_id' value='-1' />
	<input type='hidden' name='sub_can_post' value='0' />
	<input type='hidden' name='permission_showtopic' value='1' />
HTML;
}

if ( $perm_matrix )
{
$IPBHTML .= <<<HTML
<br />
$perm_matrix
<br />
HTML;
}
$IPBHTML .= <<<HTML
	<div class='acp-actionbar'>
 		<div class='rightaction'>
 			<input type='submit' class='button primary' value='$button' /> {$this->lang->words['frm_or']}&nbsp;&nbsp;{$form['convert_button']} 			
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Select a moderator gateway page
 *
 * @access	public
 * @param	integer	Forum ID
 * @param	string	Dropdown options of members
 * @return	string	HTML
 */
public function moderatorSelectForm( $fid, $member_drop ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm'  id='theAdminForm'>
	<input type='hidden' name='do' value='add_final' />
	<input type='hidden' name='fid' value='{$fid}' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['frm_m_search']}</h3>

		<ul class='acp-form alternate_rows'>
			<li>
				<label>{$this->lang->words['frm_m_choose']}</label>
				{$member_drop}
			</li>
		</ul>		
		
		<div class='acp-actionbar'>
			<div class='centeraction'>
				<input type='submit' value='{$this->lang->words['frm_m_choosebutton']}' class='button primary' accesskey='s'>
			</div>
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Display moderator add/edit form
 *
 * @access	public
 * @param	array 	Form fields
 * @param	string	Action code
 * @param	integer	Member ID
 * @param	string	Searched member text
 * @param	string	Type
 * @param	integer	Group ID
 * @param	string	Group name
 * @param	string	Button text
 * @return	string	HTML
 */
public function moderatorPermissionForm( $form, $form_code, $mid, $mem, $type, $gid, $gname, $button ) {

$IPBHTML = "";
//--starthtml--//

if( $form_code == 'doedit' )
{
	$title	= $this->lang->words['mod_edit'];
}
else if ( $this->request['group'] )
{
	$title	= $this->lang->words['mod_addgroup'];
}
else
{
	$title	= $this->lang->words['mod_add'];
}

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$title}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm' id='theAdminForm'>
	<input type='hidden' name='do' value='{$form_code}' />
	<input type='hidden' name='mid' value='{$mid}' />
	<input type='hidden' name='mem' value='{$mem}' />
	<input type='hidden' name='mod_type' value='{$type}' />
	<input type='hidden' name='gid' value='{$gid}' />
	<input type='hidden' name='gname' value='{$gname}' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->getSecurityKey()}' />
	
	<div class='acp-box'>	
		<h3>{$this->lang->words['frm_m_genset']}</h3>

		<ul class='acp-form alternate_rows'>
			<li>
				<label>{$this->lang->words['frm_mod_forums']}</label>
				{$form['forums']}
			</li>
			<li>
				<label>{$this->lang->words['frm_m_edit']}</label>
				{$form['edit_post']}
			</li>
			
			<li>
				<label>{$this->lang->words['frm_m_topic']}</label>
				{$form['edit_topic']}
			</li>
	
			<li>
				<label>{$this->lang->words['frm_m_delete']}</label>
				{$form['delete_post']}
			</li>
	
			<li>
				<label>{$this->lang->words['frm_m_deletetop']}</label>
				{$form['delete_topic']}
			</li>
	
			<li>
				<label>{$this->lang->words['frm_m_ip']}</label>
				{$form['view_ip']}
			</li>
			
			<li>
				<label>{$this->lang->words['frm_m_open']}</label>
				{$form['open_topic']}
			</li>
	
			<li>
				<label>{$this->lang->words['frm_m_close']}</label>
				{$form['close_topic']}
			</li>
			
			<li>
				<label>{$this->lang->words['frm_m_move']}</label>
				{$form['move_topic']}
			</li>
			
			<li>
				<label>{$this->lang->words['frm_m_pin']}</label>
				{$form['pin_topic']}
			</li>
			
			<li>
				<label>{$this->lang->words['frm_m_unpin']}</label>
				{$form['unpin_topic']}
			</li>
			
			<li>
				<label>{$this->lang->words['frm_m_split']}</b></label>
				{$form['split_merge']}
			</li>
			
			<li>
				<label>{$this->lang->words['frm_m_opentime']}</label>
				{$form['mod_can_set_open_time']}
			</li>
			
			<li>
				<label>{$this->lang->words['frm_m_closetime']}</label>
				{$form['mod_can_set_close_time']}
			</li>
		</ul>
	</div>
	<br />
	<div class='acp-box'>
		<h3>{$this->lang->words['frm_m_msettings']}</h3>

		<ul class='acp-form alternate_rows'>
			<li>
				<label>{$this->lang->words['frm_m_massmove']}</label>
				{$form['mass_move']}
			</li>
			
			<li>
				<label>{$this->lang->words['frm_m_massprune']}</label>
				{$form['mass_prune']}
			</li>
			
			<li>
				<label>{$this->lang->words['frm_m_visible']}</label>
				{$form['topic_q']}
			</li>
			
			<li>
				<label>{$this->lang->words['frm_m_visiblepost']}</label>
				{$form['post_q']}
			</li>
		</ul>
	</div>
	<br />
	<div class='acp-box'>
		<h3>{$this->lang->words['frm_m_asettings']}</h3>

		<ul class='acp-form alternate_rows'>
			<li>
				<label>{$this->lang->words['frm_m_warn']}<span class='desctext'>{$this->lang->words['frm_m_warn_info']}</span></label>
				{$form['allow_warn']}
			</li>
			<li>
				<label>{$this->lang->words['frm_m_spam']}</label>
				{$form['bw_flag_spammers']}
			</li>
			<li>
				<label>{$this->lang->words['frm_m_mm']}<span class='desctext'>( <a href='#' onClick="window.open('{$this->settings['_base_url']}app=core&amp;module=help&amp;id=mod_mmod','Help','width=250,height=400,resizable=yes,scrollbars=yes'); return false;">{$this->lang->words['frm_m_mm_info']}</a> )</span></label>
				{$form['can_mm']}
			</li>
		</table>
		
		<div class='acp-actionbar'>
			<div class='centeraction'>
				<input type='submit' value='{$button}' class='button primary' accesskey='s'>
			</div>
		</div>	
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}
}