<?php
/**
 * Invision Power Services
 * Blocks skin file
 * Last Updated: $Date: 2009-08-11 10:01:08 -0400 (Tue, 11 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	IP.CCS
 * @link		http://www.
 * @since		1st March 2009
 * @version		$Revision: 42 $
 */
 
class cp_skin_blocks extends output
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
 * Show category form
 *
 * @access	public
 * @param	string		Add or edit
 * @param	array 		Block data for edit
 * @return	string		HTML
 */
public function categoryForm( $type, $category=array() )
{
$IPBHTML = "";
//--starthtml--//

$title	= $type == 'add' ? $this->lang->words['add_block_cat__title'] : $this->lang->words['edit_block_cat__title'] . $category['container_name'];
$do 	= $type == 'add' ? 'doAddCategory' : 'doEditCategory';

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$title}</h2>
	<ul class='context_menu'>
		<li>
			<a href='{$this->settings['base_url']}&amp;module=blocks&amp;section=blocks' title='{$this->lang->words['cancel_edit_alt']}'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/delete.png' alt='{$this->lang->words['icon']}' />
				{$this->lang->words['button__cancel']}
			</a>
		</li>
	</ul>
</div>

<form action='{$this->settings['base_url']}&amp;module=blocks&amp;section=blocks&amp;do={$do}' method='post' id='adform' name='adform'>
<input type='hidden' name='id' value='{$category['container_id']}' />
<div class='acp-box'>
	<h3>{$this->lang->words['specify_cat_details']}</h3>
	<ul class='acp-form alternate_rows'>
		<li>
			<label>{$this->lang->words['block_cat__title']}</label>
			<input type='text' class='text' name='category_title' />
		</li>
	</ul>

	<div class="acp-actionbar">
		<input type='submit' value=' {$this->lang->words['button__save']} ' class="button primary" />
	</div>
</div>	
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Draw iframe for block preview
 *
 * @access	public
 * @param	integer		Block ID
 * @param	string		Block name
 * @return	string		HTML
 */
public function blockPreview( $id, $name )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$this->lang->words['block_preview_header']} {$name}</h3>
	<iframe src='{$this->settings['board_url']}/index.php?app=ccs&amp;module=pages&amp;section=pages&amp;do=blockPreview&amp;id={$id}' class='blockPreview'></iframe>
</div>
HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Edit block template
 *
 * @access	public
 * @param	array		Block data
 * @param	string 		Editor area
 * @return	string		HTML
 */
public function block_edit( $block=array(), $editor='' )
{
$IPBHTML = "";
//--starthtml--//

$infoBox = <<<HTML
<div class='information-box'>
	<strong>{$this->lang->words['block_tag_sample']}</strong> {parse block="{$block['block_key']}"}
</div>
HTML;

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['editing_block_pre']} {$block['block_name']}</h2>
	<ul class='context_menu'>
		<li>
			<a href='{$this->settings['base_url']}&amp;module=blocks&amp;section=wizard&amp;do=editBlock&amp;block={$block['block_id']}' title='{$this->lang->words['use_wizard_alt']}'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/ccs/wizard_small.png' alt='{$this->lang->words['icon']}' />
				{$this->lang->words['use_wizard_alt']}
			</a>
		</li>
		<li>
			<a href='{$this->settings['base_url']}&amp;module=blocks&amp;section=blocks' title='{$this->lang->words['cancel_edit_alt']}'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/delete.png' alt='{$this->lang->words['icon']}' />
				{$this->lang->words['button__cancel']}
			</a>
		</li>
	</ul>
</div>

<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>

<form action='{$this->settings['base_url']}&amp;module=blocks&amp;section=wizard&amp;do=saveBlockTemplate&amp;block={$block['block_id']}' method='post' id='adform' name='adform'>
<div class='acp-box'>
	<h3>{$this->lang->words['editing_block_content']}</h3>
	{$infoBox}
	<ul class='acp-form alternate_rows'>
		<li>
			{$editor}
		</li>
	</ul>

	<div class="acp-actionbar">
		<input type='submit' value=' {$this->lang->words['button__save']} ' class="button primary" /> <input type='submit' name='save_and_reload' value=' {$this->lang->words['button__reload']} ' class="button primary" />
	</div>
</div>	
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show the listing
 *
 * @access	public
 * @param	array		Current blocks
 * @param	array		Unfinished blocks
 * @param	array 		Categories
 * @param	array 		Exportable blocks
 * @return	string		HTML
 */
public function listBlocks( $blocks=array(), $unfinished=array(), $categories=array(), $exportable=array() )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>
<script type='text/javascript'>
	ipb.templates['cat_empty']	= new Template("<li class='no-records' id='record_00#{category}'><em>{$this->lang->words['no_blocks_yet']}</em></li>");
</script>
<div class='section_title'>
	<h2>{$this->lang->words['blocks_h2_header']}</h2>
	<ul class='context_menu'>
		<li>
			<a href='{$this->settings['base_url']}&amp;module=blocks&amp;section=wizard' title='{$this->lang->words['add_block']}'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/add.png' alt='{$this->lang->words['add_block']}' />
				{$this->lang->words['add_block']}
			</a>
		</li>
		<li>
			<a href='{$this->settings['base_url']}&amp;module=blocks&amp;section=blocks&amp;do=addCategory' title='{$this->lang->words['add_block_category']}'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/add.png' alt='{$this->lang->words['add_block_category']}' />
				{$this->lang->words['add_block_category']}
			</a>
		</li>
HTML;

if( IN_DEV )
{
	$IPBHTML .= <<<HTML
		<li>
			<a href='#' title='{$this->lang->words['export_block']}' class='ipbmenu' id="menu_export_block">
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/plugin.png' alt='{$this->lang->words['export_block']}' />
				{$this->lang->words['export_block']}
			</a>
		</li>
HTML;
}

$IPBHTML .= <<<HTML
		<li>
			<a href='{$this->settings['base_url']}&amp;module=blocks&amp;section=blocks&amp;do=recacheAll' title='{$this->lang->words['recache_all_block']}'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/arrow_refresh.png' alt='{$this->lang->words['recache_all_block']}' />
				{$this->lang->words['recache_all_block']}
			</a>
		</li>
	</ul>
</div>
<!--Content has to be outside section_title div or it gets styled differently-->
<ul class='acp-menu' id='menu_export_block_menucontent'>
HTML;

if( IN_DEV )
{
	foreach( $exportable as $block )
	{
		$IPBHTML .= "<li class='icon export'><a href='{$this->settings['base_url']}&amp;module=blocks&amp;section=blocks&amp;do=export&amp;block={$block['key']}'>{$block['name']}</a></li>";
	}
	
	$IPBHTML .= "<li class='icon manage' style='border-top: 3px solid #e1e1e1;'><a href='{$this->settings['base_url']}&amp;module=blocks&amp;section=blocks&amp;do=export&amp;block=_all_'><span style='font-weight: bold;'>{$this->lang->words['export_templates_release']}</span></a></li>";
	$IPBHTML .= "<li class='icon manage'><a href='{$this->settings['base_url']}&amp;module=blocks&amp;section=blocks&amp;do=import&amp;dev=1'><span style='font-weight: bold;'>{$this->lang->words['import_templates_release']}</span></a></li>";
}

$IPBHTML .= <<<HTML
</ul>
HTML;

/*if( count($unfinished) )
{
	$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$this->lang->words['unfinished_blocks_header']}</h3>
	<div class='information-box'>{$this->lang->words['unfinished_block_desc']}</div>
	<ul class='alternate_rows filemanager'>
HTML;

foreach( $unfinished as $block )
{
$IPBHTML .= <<<HTML
	<li class='record'>
		<div class='manage'>
			<img class='ipbmenu' id="menu_{$block['wizard_id']}" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt='{$this->lang->words['block_options']}' />
			<ul class='acp-menu' id='menu_{$block['wizard_id']}_menucontent'>
				<li class='icon edit'><a href='{$this->settings['base_url']}&amp;module=blocks&amp;section=wizard&amp;do=completeBlock&amp;wizard_session={$block['wizard_id']}'>{$this->lang->words['continue_config_block']}</a></li>
				<li class='icon delete'><a href='#' onclick="return acp.confirmDelete( '{$this->settings['base_url']}&amp;module=blocks&amp;section=blocks&amp;do=delete&amp;type=wizard&amp;block={$block['wizard_id']}' );">{$this->lang->words['delete_block']}</a></li>
			</ul>
		</div>
		<div>
			<strong>{$block['wizard_name']}</strong>
			<div class='desctext'>({$this->lang->words['current_step_pre']} {$block['wizard_step']})</div>
		</div>
	</li>
HTML;
}

$IPBHTML .= <<<HTML
	</ul>
</div>
<br />
HTML;
}*/

$IPBHTML .= <<<HTML
<div id='category-containers'>
HTML;

$types	= array(
				'plugin'	=> $this->lang->words['block_type__plugin'],
				'feed'		=> $this->lang->words['block_type__feed'],
				'custom'	=> $this->lang->words['block_type__custom']
				);

//-----------------------------------------
// Now we loop over cats and put blocks in,
// then put the rest in "Other blocks"
//-----------------------------------------

$categories[]	= array( 'container_id' => 0, 'container_name' => $this->lang->words['current_blocks_header'], 'noEdit' => true );

if( is_array($categories) AND count($categories) )
{
	$dragNDrop	= array();
	
	foreach( $categories as $category )
	{
		$dragNDrop[]	= "sortable_handle_{$category['container_id']}";
		
		//-----------------------------------------
		// Don't show "other blocks" category if it's
		// empty, but we have blocks in other cats
		//-----------------------------------------
		
		if( $category['container_id'] == 0 AND !(is_array($blocks[ $category['container_id'] ]) AND count($blocks[ $category['container_id'] ])) AND count($blocks) )
		{
			continue;
		}

		$IPBHTML .= <<<HTML
		<div id='container_{$category['container_id']}'>
		<div class='acp-box'>
			<h3>
HTML;

			if( !$category['noEdit'] )
			{
				$IPBHTML .= <<<HTML
				<div class='category-manage'>
					<img class='ipbmenu' id="menu_cat{$category['container_id']}" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt='{$this->lang->words['block_options']}' />
					<ul class='acp-menu' id='menu_cat{$category['container_id']}_menucontent'>
						<li class='icon edit'><a href='{$this->settings['base_url']}&amp;module=blocks&amp;section=blocks&amp;do=editCategory&amp;id={$category['container_id']}'>{$this->lang->words['edit_block_category']}</a></li>
						<li class='icon delete'><a href='#' onclick="return acp.confirmDelete( '{$this->settings['base_url']}&amp;module=blocks&amp;section=blocks&amp;do=deleteCategory&amp;id={$category['container_id']}' );">{$this->lang->words['delete_block_category']}</a></li>
					</ul>
				</div>
HTML;
			}
			
			$IPBHTML .= <<<HTML
				 <div class='draghandle' style='margin-top: -5px;'><img src='{$this->settings['skin_acp_url']}/_newimages/drag_icon.png' alt=''></div> {$category['container_name']}
			</h3>
			<ul id='sortable_handle_{$category['container_id']}' class='alternate_rows filemanager'>
HTML;

		if( is_array($blocks[ $category['container_id'] ]) AND count($blocks[ $category['container_id'] ]) )
		{
			foreach( $blocks[ $category['container_id'] ] as $block )
			{
			$IPBHTML .= <<<HTML
			<li class='record' id='record_{$block['block_id']}'>
				<table width='100%' cellpadding='0' cellspacing='0'>
					<tr>
						<td style='width: 2%'>
							<div class='draghandle'><img src='{$this->settings['skin_acp_url']}/_newimages/drag_icon.png' alt=''></div>
						</td>
						<td style='width: 76%'>
							<a href='#' class='block-preview-link' id='{$block['block_id']}-blockPreview' title='{$this->lang->words['block_preview_alt']}'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/view.png' alt='{$this->lang->words['block_preview_alt']}' /></a> 
							<strong><a href='{$this->settings['base_url']}&amp;module=blocks&amp;section=wizard&amp;do=editBlockTemplate&amp;block={$block['block_id']}'>{$block['block_name']}</a></strong>
							<div class='desctext clear'>{$block['block_description']}</div>
						</td>
						<td style='width: 20%'>
							<div class='info'>
								<div class='block {$block['block_type']}'>{$types[ $block['block_type'] ]}</div>
							</div>
						</td>
						<td style='width: 2%'>			
							<div class='manage'>
								<img class='ipbmenu' id="menu_{$block['block_id']}" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt='{$this->lang->words['block_options']}' />
								<ul class='acp-menu' id='menu_{$block['block_id']}_menucontent'>
									<!--<li class='icon edit'><a href='{$this->settings['base_url']}&amp;module=blocks&amp;section=wizard&amp;do=editBlock&amp;block={$block['block_id']}'>{$this->lang->words['edit_block']}</a></li>-->
									<li class='icon edit'><a href='{$this->settings['base_url']}&amp;module=blocks&amp;section=wizard&amp;do=editBlockTemplate&amp;block={$block['block_id']}'>{$this->lang->words['edit_block']}</a></li>
									<li class='icon delete'><a href='#' onclick="return acp.confirmDelete( '{$this->settings['base_url']}&amp;module=blocks&amp;section=blocks&amp;do=delete&amp;block={$block['block_id']}' );">{$this->lang->words['delete_block']}</a></li>
									<li class='icon export'><a href='{$this->settings['base_url']}&amp;module=blocks&amp;section=blocks&amp;do=exportBlock&amp;block={$block['block_id']}'>{$this->lang->words['export_single_block']}</a></li>
									<li class='icon refresh'><a href='{$this->settings['base_url']}&amp;module=blocks&amp;section=blocks&amp;do=recache&amp;block={$block['block_id']}'>{$this->lang->words['recache_block']}</a></li>
								</ul>
							</div>
						</td>
					</tr>
				</table>
			</li>
HTML;
			}
		}
		else
		{
			$IPBHTML .= <<<HTML
			<li class='no-records' id='record_00{$category['container_id']}'>
				<em>{$this->lang->words['no_blocks_yet']}</em>
			</li>
HTML;
		}

		$IPBHTML .= <<<HTML
			</ul>
		</div>
		<br />
		</div>
HTML;
	}
}

$_dragNDrop_Categories	= implode( "', '", $dragNDrop );

$IPBHTML .= <<<HTML
</div>
<script type='text/javascript'>
	acp.ccs.initBlockCategorization( new Array('{$_dragNDrop_Categories}') );
</script>
<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=import' method='post' enctype='multipart/form-data'>
<div class="acp-box">
	<h3>{$this->lang->words['import_new_block']}</h3>
	<ul class="acp-form alternate_rows">
		<li>
			<label>{$this->lang->words['upload_block_xml']}<span class='desctext'>{$this->lang->words['upload_block_desc']}</span></label>
			<input type='file' name='FILE_UPLOAD' />
		</li>
	<ul>
	<div class="acp-actionbar">
		<input type='submit' value='{$this->lang->words['block_install']}' class="button primary" />
	</div>
</div>
</form>
HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Wizard: Step 1
 *
 * @access	public
 * @param	string		Session ID
 * @param	array 		Block types
 * @return	string		HTML
 */
public function wizard_step_1( $sessionId, $_blockTypes )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2><img src='{$this->settings['skin_acp_url']}/_newimages/ccs/wizard.png' alt='{$this->lang->words['wizard_alt']}' /> {$this->lang->words['gbl__step_1']}</h2>
	<ul class='context_menu'>
		<!--<li>
			<a href='{$this->settings['base_url']}&amp;module=blocks&amp;section=blocks' title='{$this->lang->words['pause_session']}'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/plugin.png' alt='{$this->lang->words['icon']}' />
				{$this->lang->words['button__pause']}
			</a>
		</li>-->
		<li>
			<a href='{$this->settings['base_url']}&amp;module=blocks&amp;section=blocks&amp;do=delete&amp;type=wizard&amp;block={$sessionId}' title='{$this->lang->words['cancel_block_session']}'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/delete.png' alt='{$this->lang->words['icon']}' />
				{$this->lang->words['button__cancel']}
			</a>
		</li>
	</ul>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=process&amp;wizard_session={$sessionId}' method='post'>
<input type='hidden' name='step' value='1' />
<div class='acp-box'>
	<h3>{$this->lang->words['block_create_header']}</h3>
	<table width='100%' cellpadding='0' cellspacing='0' class='form_table alternate_rows double_pad'>
		<!--<tr>
			<td style='width: 40%'>
				<strong>{$this->lang->words['block__session_name']}</strong><br /><span class='desctext'>{$this->lang->words['block__session_name_desc']}</span>
			</td>
			<td style='width: 60%'>
				<input type='text' class='text' name='name' class='input_text' />
			</td>
		</tr>-->
		<tr>
			<td style='width: 40%'>
				<strong>{$this->lang->words['block__type']}</strong>
			</td>
			<td style='width: 60%'>
				<select name='type' id='block-type'>
HTML;

	foreach( $_blockTypes as $type )
	{
		$IPBHTML .= <<<HTML
			<option value='{$type[0]}'>{$type[1]}</option>
HTML;
	}
	$IPBHTML .= <<<HTML
				</select>
			</td>
		</tr>
	</table>
	<div class="acp-actionbar">
		<input type='submit' value=' {$this->lang->words['button__continue']} ' class="button primary" />
	</div>
</div>	
</form>
<br />
<div class='acp-box'>
	<h3>{$this->lang->words['block_type_description_h']}</h3>
	<ul class='acp-form alternate_rows'>
		<li><strong>{$this->lang->words['block__types_feed']}</strong> {$this->lang->words['block__types_feed_basic']}
			<div class='desctext'>{$this->lang->words['block__types_feed_full']}</div>
		</li>
		
		<li><strong>{$this->lang->words['block__types_plugin']}</strong> {$this->lang->words['block__types_plugin_basic']}
			<div class='desctext'>{$this->lang->words['block__types_plugin_full']}</div>
		</li>
		
		<li><strong>{$this->lang->words['block__types_custom']}</strong> {$this->lang->words['block__types_custom_basic']}
			<div class='desctext'>{$this->lang->words['block__types_custom_full']}</div>
		</li>
	</ul>
</div>
HTML;
//--endhtml--//
return $IPBHTML;
}

}