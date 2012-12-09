<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Forum member form plugin
 * Last Updated: $LastChangedDate: 2009-08-28 17:15:27 -0400 (Fri, 28 Aug 2009) $
 *
 * @author 		$Author: rikki $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Forums
 * @link		http://www.
 * @since		14th May 2003
 * @version		$Rev: 5062 $
 */
 
class cp_skin_member_form extends output
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
 * Display/change avatar
 *
 * @access	public
 * @param	array 	Image data
 * @return	string	HTML
 */
public function inline_avatar_images( $images=array() )
{
$IPBHTML = "";

$default_url = ($member['avatar_type'] == 'url') ? $member['avatar_location'] : '';
													
$IPBHTML .= <<<EOF

		<select name='avatar_image' id='avatar_image' onChange='acp.members.updateAvatarPreview();'>
			<option value=''>{$this->lang->words['m_selectav']}</option>
EOF;
	foreach( $images as $image )
	{
		$IPBHTML .= "<option value='{$image}'>{$image}</option>";
	}
	
	$IPBHTML .= <<<EOF
		</select>
EOF;

return $IPBHTML;
}



/**
 * Ability to select an avatar from avatar galleries
 *
 * @access	public
 * @param	array 	Member data
 * @param	array 	Avatar gallery categories
 * @return	string	HTML
 */
public function inline_avatar_selector( $member, $categories=array() )
{
$IPBHTML = "";

$default_url = ($member['avatar_type'] == 'url') ? $member['avatar_location'] : '';
													
$IPBHTML .= <<<EOF
<form action='{$this->settings['_base_url']}&amp;app=forums&amp;module=tools&amp;section=tools&amp;do=new_avatar&amp;member_id={$member['member_id']}' method='post' enctype='multipart/form-data'>
	<div class='acp-box'>
		<h3>Change Avatar</h3>
		<div class='row1'>
			<p class='information-box' style='margin: 6px;'>
				{$this->lang->words['m_firstfield']}
			</p>
		</div>
		<ul class='acp-form'>
			<li><h4>{$this->lang->words['m_newav']}</h4></li>
			<li style='padding-left: 20px'>
				<input type='file' size='30' style='width: 95%' id='upload_avatar' name='upload_avatar' />
			</li>
			<li><h4>{$this->lang->words['m_avurl']}</h4></li>
			<li>
				<label for='avatar_url' style='font-weight: normal'>{$this->lang->words['m_avurl_label']}</label>
				<input type='text' size='30' id='avatar_url' name='avatar_url' value='{$default_url}' />
			</li>
EOF;
			if( $this->settings['disable_ipbsize'] )
			{
				list($p_width, $p_height)	= explode( "x", strtolower( $this->settings['avatar_dims'] ) );
				
				$IPBHTML .= <<<EOF
					<li><h4>{$this->lang->words['m_avdimen']}</h4></li>
					<li>
						<label for='avatar_url' style='font-weight: normal'>{$this->lang->words['m_avdimen']}</label>
						{$this->lang->words['m_width']} <input type='text' size='6' id='man_width' name='man_width' value='{$p_width}' /> x {$this->lang->words['m_height']} <input type='text' size='6' id='man_height' name='man_height' value='{$p_height}' />
					</li>
EOF;
			}
			
			if( count($categories) )
			{
				$IPBHTML .= <<<EOF
					<li><h4>{$this->lang->words['m_localav']}</h4></li>
					<li>
						<div style='float:right;margin-right:4px;margin-top:4px;'>
							<div style='border:1px solid #000;background:#FFF;width:auto; padding:2px;' id='avatarContainer'></div>
						</div>
						
						<select name='avatar_gallery' id='avatar_gallery'>
EOF;
						foreach( $categories as $category )
						{
							$IPBHTML .= "<option value='{$category[0]}'>{$category[1]}</option>";
						}
						
				$IPBHTML .= <<<EOF
						</select>
						<span style='display:none;' id='avatarGalleryContainer'></span>
					</li>
EOF;
			}
			
			$IPBHTML .= <<<EOF
		</ul>
		<div class='acp-actionbar' style='clear:both;'><input type='submit' value='{$this->lang->words['m_updateav']}' class='realbutton' /></div>
	</div>
</form>
EOF;

return $IPBHTML;
}

/**
 * Main ACP member form
 *
 * @access	public
 * @param	array 	Member data
 * @return	string	HTML
 */
public function acp_member_form_main( $member ) {

$masks = array();

ipsRegistry::DB()->build( array( 'select' => '*', 'from' => 'forum_perms', 'order' => 'perm_name' ) );
ipsRegistry::DB()->execute();

while( $data = ipsRegistry::DB()->fetch() )
{	
	$masks[] = array( $data['perm_id'], $data['perm_name'] );
}

$_restrict_tick		= '';
$_restrict_timespan	= '';
$_restrict_units	= '';
$units			= array( 0 => array( 'h', $this->lang->words['m_hours'] ), 1 => array( 'd', $this->lang->words['m_days'] ) );

if ( $member['restrict_post'] == 1 )
{
	$_restrict_tick = 'checked="checked"';
}
elseif ($member['restrict_post'] > 0)
{
	$mod_arr = IPSMember::processBanEntry( $member['restrict_post'] );

	$hours  = ceil( ( $mod_arr['date_end'] - time() ) / 3600 );

	if( $hours < 0 )
	{
		$mod_arr['units']		= '';
		$mod_arr['timespan']	= '';
	}
	else if ( $hours > 24 and ( ($hours / 24) == ceil($hours / 24) ) )
	{
		$mod_arr['units']		= 'd';
		$mod_arr['timespan']	= $hours / 24;
	}
	else
	{
		$mod_arr['units']		= 'h';
		$mod_arr['timespan']	= $hours;
	}
}

$_restrict_timespan		= ipsRegistry::getClass('output')->formSimpleInput('post_timespan', $mod_arr['timespan'] );
$_restrict_units		= ipsRegistry::getClass('output')->formDropdown('post_units', $units, $mod_arr['units'] );

$_mod_tick		= '';
$_mod_timespan	= '';
$_mod_units		= '';

if ( $member['mod_posts'] == 1 )
{
	$_mod_tick = 'checked="checked"';
}
elseif ($member['mod_posts'] > 0)
{
	$mod_arr = IPSMember::processBanEntry( $member['mod_posts'] );
	
	$hours  = ceil( ( $mod_arr['date_end'] - time() ) / 3600 );
		
	if( $hours < 0 )
	{
		$mod_arr['units']		= '';
		$mod_arr['timespan']	= '';
	}
	else if ( $hours > 24 and ( ($hours / 24) == ceil($hours / 24) ) )
	{
		$mod_arr['units']		= 'd';
		$mod_arr['timespan']	= $hours / 24;
	}
	else
	{
		$mod_arr['units']		= 'h';
		$mod_arr['timespan']	= $hours;
	}
}

$_mod_timespan			= ipsRegistry::getClass('output')->formSimpleInput('mod_timespan', $mod_arr['timespan'] );
$_mod_units				= ipsRegistry::getClass('output')->formDropdown('mod_units', $units, $mod_arr['units'] );

$form_override_masks	= ipsRegistry::getClass('output')->formMultiDropdown( "org_perm_id[]", $masks, explode( ",", $member['org_perm_id'] ), 8, 'org_perm_id' );
$form_posts				= ipsRegistry::getClass('output')->formInput( "posts", $member['posts'] );
$form_view_avs			= ipsRegistry::getClass('output')->formYesNo( "view_avs", $member['view_avs'] );
$form_view_img			= ipsRegistry::getClass('output')->formYesNo( "view_img", $member['view_img'] );

$IPBHTML = "";

$IPBHTML .= <<<EOF
	
	<div class='tablerow1' id='tabpane-MEMBERS|6'>
		<table class='alternate_rows double_pad' cellspacing='0'>
			
			<tr>
				<th colspan='2'>{$this->lang->words['sm_settings']}</th>
			</tr>
			<tr>
				<td><strong>Avatar</strong></td>
				<td>
					{$member['_avatar']}
					<br /><br />
					<input type='button' class='button' id='MF__avatarremove' value='{$this->lang->words['mem_remove_avatar']}' />
					<input type='button' class='button' id='MF__avatarchange' value='{$this->lang->words['m_changeav']}' />
					
					<script type='text/javascript'>
						$('MF__avatarremove').observe('click', acp.members.removeAvatar.bindAsEventListener( this, {$member['member_id']} ) );
						$('MF__avatarchange').observe('click', acp.members.changeAvatar.bindAsEventListener( this, "app=forums&amp;module=ajax&amp;section=member_editform&amp;do=show&amp;name=inline_avatar&amp;member_id={$member['member_id']}" ) );
					</script>
				</td>
			</tr>
			<tr>
				<td><strong>{$this->lang->words['mem_posts']}</strong></td>
				<td>
					<span id='MF__posts'>{$form_posts}</span>
				</td>
			</tr>
			<tr>
				<td><strong>{$this->lang->words['m_viewavs']}</strong></td>
				<td>
					<span id='MF__view_avs'>{$form_view_avs}</span>
				</td>
			</tr>
			<tr>
				<td><strong>{$this->lang->words['m_viewimgs']}</strong></td>
				<td>
					<span id='MF__view_img'>{$form_view_img}</span>
				</td>
			</tr>
		</table>
	</div>
	<div class='tablerow1' id='tabpane-MEMBERS|7'>
		<table class='alternate_rows double_pad' cellspacing='0'>
			<tr>
				<th colspan='2'>{$this->lang->words['sm_access']}</th>
			</tr>
			<tr>
				<td style='width: 35%'><strong>{$this->lang->words['m_overrride']}</strong></td>
				<td>
					<span id='MF__ogpm'>{$form_override_masks}</span>
				</td>
			</tr>
			<tr>
				<td><strong>{$this->lang->words['m_modprev']}</strong></td>
				<td>
					<input type='checkbox' name='mod_indef' id='mod_indef' value='1' {$_mod_tick}> {$this->lang->words['m_modindef']}
					<br />
					{$this->lang->words['m_orfor']}
					{$_mod_timespan} {$_mod_units}
				</td>
			</tr>
			<tr>
				<td><strong>{$this->lang->words['m_restrict']}</strong></td>
				<td>
					<input type='checkbox' name='post_indef' id='post_indef' value='1' {$_restrict_tick}> {$this->lang->words['m_restrictindef']}
					<br />
					{$this->lang->words['m_orfor']}
					{$_restrict_timespan} {$_restrict_units}
				</td>
			</tr>
		</table>
	</div>

EOF;

return $IPBHTML;
}

/**
 * Forums member tabs
 *
 * @access	public
 * @param	array 	Member data
 * @return	string	HTML
 */
public function acp_member_form_tabs( $member ) {

$IPBHTML = "";

$IPBHTML .= <<<EOF
	<li id='tabtab-MEMBERS|6' class=''>{$this->lang->words['m_details']}</li>
	<li id='tabtab-MEMBERS|7' class=''>{$this->lang->words['m_permrestrict']}</li>
EOF;

return $IPBHTML;
}

/**
 * Delete posts confirmation page
 *
 * @access	public
 * @param	array 	Member data
 * @param	integer	Number of topics
 * @param	integer	Number of posts
 * @return	string	HTML
 */
public function deletePostsStart( $member, $topics, $posts ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=deleteposts_process&amp;member_id={$member['member_id']}' method='POST'>
<input type='hidden' name='_admin_auth_key' value='{$this->registry->getClass('adminFunctions')->_admin_auth_key}' />
<div class='tableborder'>
 <div class='tableheaderalt'>{$this->lang->words['mem_delete_posts_title']} {$member['members_display_name']}</div>
 <table cellpadding='0' cellspacing='0' width='100%'>
 <tr>
  <td class='tablerow1' width='90%'><strong>{$this->lang->words['mem_delete_delete_posts']}</strong><div class='desctext'>{$this->lang->words['mem_delete_delete_posts_desc']}</div></td>
  <td class='tablerow2' width='10%'><input type='checkbox' value='1' name='dposts' /></td>
 </tr>
 <tr>
  <td class='tablerow1' width='90%'><strong>{$this->lang->words['mem_delete_delete_topics']}</strong><div class='desctext'>{$this->lang->words['mem_delete_delete_topics_desc']}</div></td>
  <td class='tablerow2' width='10%'><input type='checkbox' value='1' name='dtopics' /></td>
 </tr>
 <tr>
  <td class='tablerow1' width='90%'><strong>{$this->lang->words['mem_delete_posts_trash']}</strong><div class='desctext'>{$this->lang->words['mem_delete_posts_trash_desc']}</div></td>
  <td class='tablerow2' width='10%'><input type='checkbox' value='1' name='use_trash_can' /></td>
 </tr> 
 <tr>
  <td class='tablerow1' width='90%'><strong>{$this->lang->words['mem_delete_delete_pergo']}</strong><div class='desctext'>{$this->lang->words['mem_delete_delete_pergo_desc']}</div></td>
  <td class='tablerow2' width='10%'><input type='input' value='50' size='3' name='dpergo' /></td>
 </tr>
 </table>
 <div align='center' class='tablefooter'><input type='submit' class='realbutton' value='{$this->lang->words['mem_delete_process']}' /></div>
</div>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}

}