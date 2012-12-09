<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Portal skin file
 * Last Updated: $Date: 2009-06-30 12:06:12 -0400 (Tue, 30 Jun 2009) $
 *
 * @author 		$Author: josh $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Portal
 * @link		http://www.
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 4829 $
 */
 
class cp_skin_portal extends output 
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
 * Portal tag details
 *
 * @access	public
 * @param	string		Page title
 * @param	array 		Available tags
 * @return	string		HTML
 */
public function portal_pop_overview( $title, $tags ) {

$IPBHTML = "";
//--starthtml--//
$IPBHTML .= <<<EOF
<div class='acp-box'>
	<h3>{$this->lang->words['portal_pop_tags']} {$title}</h3>
	<table class='alternate_rows' width='100%'>
		<tr>
			<th width='30%'>{$this->lang->words['portal_pop_name']}</th>
			<th width='70%'>{$this->lang->words['portal_pop_desc']}</th>
		</tr>
EOF;

if ( is_array( $tags ) AND count( $tags ) )
{
	foreach( $tags as $tag => $tag_data )
	{
		$IPBHTML .= <<<EOF
		<tr>
			<td>&lt;!--::<strong>{$tag}</strong>::--&gt;</td>
			<td><div class='desctext'>{$tag_data[1]}</td>
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
 * Portal overview
 *
 * @access	public
 * @param	array 		Available portal objects
 * @return	string		HTML
 */
public function portal_overview( $objects ) {

$IPBHTML = "";
//--starthtml--//
$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['main_title']}</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['portal_main_title']}</h3>
	
	<table class='alternate_rows' width='100%'>
		<tr>
			<th width='1%'>&nbsp;</th>
			<th width='94%'>{$this->lang->words['portal_main_key']}</th>
			<th width='5%'>&nbsp;</th>
		</tr>
EOF;

if ( is_array( $objects ) AND count( $objects ) )
{
	foreach( $objects as $key => $data )
	{
		$IPBHTML .= <<<EOF
		<tr>
			<td><img src='{$this->settings['skin_acp_url']}/images/menu.png' border='0' alt='{$this->lang->words['a_options']}' class='ipd' /></td>
			<td><strong>{$data['pc_title']}</strong><div class='desctext'>{$data['pc_desc']}</td>
			<td>
				<img id="menu{$data['pc_key']}" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' border='0' alt='{$this->lang->words['a_options']}' class='ipbmenu' />
				<ul class='acp-menu' id='menu{$data['pc_key']}_menucontent'>
EOF;
//startif
if ( $data['pc_settings_keyword'] )
{
$IPBHTML .= <<<EOF
					<li class='icon edit'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=portal_settings&amp;pc_key={$data['pc_key']}'>{$this->lang->words['portal_row_menu_settings']}</a></li>
EOF;
}//endif
$IPBHTML .= <<<EOF
					<li class='icon export'><a href='#' onclick="return acp.openWindow('{$this->settings['base_url']}&{$this->form_code}&amp;do=portal_viewtags&amp;pc_key={$data['pc_key']}', '{$data['pc_key']}', 400,200)">{$this->lang->words['portal_row_menu_view_tags']}</a></li>
				</ul>
			</td>
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


}