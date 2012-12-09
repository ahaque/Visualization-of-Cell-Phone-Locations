<?php
/**
 * Invision Power Services
 * Templates skin file
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

$title	= $type == 'add' ? $this->lang->words['add_template_cat__title'] : $this->lang->words['edit_template_cat__title'] . $category['container_name'];
$do 	= $type == 'add' ? 'doAddCategory' : 'doEditCategory';

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$title}</h2>
	<ul class='context_menu'>
		<li>
			<a href='{$this->settings['base_url']}&amp;module=templates&amp;section=pages' title='{$this->lang->words['cancel_edit_alt']}'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/delete.png' alt='{$this->lang->words['icon']}' />
				{$this->lang->words['button__cancel']}
			</a>
		</li>
	</ul>
</div>

<form action='{$this->settings['base_url']}&amp;module=templates&amp;section=pages&amp;do={$do}' method='post' id='adform' name='adform'>
<input type='hidden' name='id' value='{$category['container_id']}' />
<div class='acp-box'>
	<h3>{$this->lang->words['specify_cat_details']}</h3>
	<ul class='acp-form alternate_rows'>
		<li>
			<label>{$this->lang->words['template_cat__title']}</label>
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
 * Confirm template deletion
 *
 * @access	public
 * @param	integer		Template id
 * @param	array		Template data
 * @param	integer		Pages still using template
 * @return	string		HTML
 */
public function confirmDelete( $id, $template, $count )
{
$IPBHTML = "";
//--starthtml--//

$stillUsing	= sprintf( $this->lang->words['template_still_used'], $count );

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['confirm_template_delete']}</h2>
</div>
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=delete&amp;confirm=1' method='post'>
<input type='hidden' name='template' value='{$id}' />
<div class='acp-box page-template-form'>
	<h3>{$this->lang->words['confirm_to_continue_d']}</h3>
	<p>{$stillUsing}</p>
	<div class="acp-actionbar">
		<input type='submit' value=' {$this->lang->words['button__cd']} ' class="button primary" />
		<input type='button' value=' {$this->lang->words['button__canceld']} ' class="realbutton redbutton" onclick='return acp.redirect("{$this->settings['base_url']}{$this->form_code}&amp;section=pages");' />
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
 * @param	array		Current templates
 * @param	array 		Categories
 * @return	string		HTML
 */
public function listTemplates( $templates=array(), $categories=array() )
{
$IPBHTML = "";
//--starthtml--//

$template['pages']	= $this->registry->class_localization->formatNumber( intval($template['pages']) );

$IPBHTML .= <<<HTML
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>
<script type='text/javascript'>
	ipb.templates['cat_empty']	= new Template("<li class='no-records' id='record_00#{category}'><em>{$this->lang->words['no_templates_yet']}</em></li>");
</script>
<div class='section_title'>
	<h2>{$this->lang->words['page_templates_title']}</h2>
	<ul class='context_menu'>
		<li>
			<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=add' title='{$this->lang->words['add_template_button']}'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/add.png' alt='{$this->lang->words['icon']}' />
				{$this->lang->words['add_template_button']}
			</a>
		</li>
		<li>
			<a href='{$this->settings['base_url']}&amp;module=templates&amp;section=pages&amp;do=addCategory' title='{$this->lang->words['add_block_category']}'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/add.png' alt='{$this->lang->words['add_block_category']}' />
				{$this->lang->words['add_block_category']}
			</a>
		</li>
	</ul>
</div>
<div id='category-containers'>
HTML;

//-----------------------------------------
// Now we loop over cats and put blocks in,
// then put the rest in "Other blocks"
//-----------------------------------------

$categories[]	= array( 'container_id' => 0, 'container_name' => $this->lang->words['current_templates_header'], 'noEdit' => true );

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
		
		if( $category['container_id'] == 0 AND !(is_array($templates[ $category['container_id'] ]) AND count($templates[ $category['container_id'] ])) AND count($templates) )
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
						<li class='icon edit'><a href='{$this->settings['base_url']}&amp;module=templates&amp;section=pages&amp;do=editCategory&amp;id={$category['container_id']}'>{$this->lang->words['edit_template_category']}</a></li>
						<li class='icon delete'><a href='#' onclick="return acp.confirmDelete( '{$this->settings['base_url']}&amp;module=templates&amp;section=pages&amp;do=deleteCategory&amp;id={$category['container_id']}' );">{$this->lang->words['delete_template_category']}</a></li>
					</ul>
				</div>
HTML;
			}
			
			$IPBHTML .= <<<HTML
				 <div class='draghandle'><img src='{$this->settings['skin_acp_url']}/_newimages/drag_icon.png' alt=''></div> {$category['container_name']}
			</h3>
			<ul id='sortable_handle_{$category['container_id']}' class='alternate_rows filemanager'>
HTML;

		if( is_array($templates[ $category['container_id'] ]) AND count($templates[ $category['container_id'] ]) )
		{
			foreach( $templates[ $category['container_id'] ] as $template )
			{
				$pages_in_use	= sprintf( $this->lang->words['used_by_s_pages'], $template['pages'] );
				
				$IPBHTML .= <<<HTML
				<li class='record' id='record_{$template['template_id']}'>
					<div class='manage'>
						<img class='ipbmenu' id="menu_{$template['template_id']}" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt='{$this->lang->words['template_options__alt']}' />
						<ul class='acp-menu' id='menu_{$template['template_id']}_menucontent'>
							<li class='icon edit'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=edit&amp;template={$template['template_id']}'>{$this->lang->words['edit_template_link']}</a></li>
							<li class='icon delete'><a href='#' onclick="return acp.confirmDelete( '{$this->settings['base_url']}{$this->form_code}&amp;do=delete&amp;template={$template['template_id']}' );">{$this->lang->words['delete_template__link']}</a></li>
						</ul>
					</div>
					<div class="info">
						{$this->lang->words['last_modified_pre']} {$template['template_updated_formatted']}<br />
						{$pages_in_use}
					</div>
					<div class='draghandle'><img src='{$this->settings['skin_acp_url']}/_newimages/drag_icon.png' alt=''></div>
					<div>
						<strong><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=edit&amp;template={$template['template_id']}'>{$template['template_name']}</a></strong>
						<div class='desctext'>{$template['template_desc']}</div>
					</div>
				</li>
HTML;
			}
		}
		else
		{
			$IPBHTML .= <<<HTML
			<li class='no-records' id='record_00{$category['container_id']}'>
				<em>{$this->lang->words['no_templates_yet']} <a href='{$this->settings['base_url']}&amp;module=templates&amp;section=pages&amp;do=add'>Create a page template now?</a></em>
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
	acp.ccs.initTemplateCategorization( new Array('{$_dragNDrop_Categories}') );
</script>
HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Show the form to add or edit a template
 *
 * @access	public
 * @param	string		Type (add|edit)
 * @param	array		Current data
 * @param	array 		Form elements
 * @return	string		HTML
 */
public function templateForm( $type, $defaults, $form )
{
$IPBHTML = "";
//--starthtml--//

$title	= $type == 'add' ? $this->lang->words['adding_a_template'] : $this->lang->words['editing_a_template'];
$code	= $type == 'add' ? 'doAdd' : 'doEdit';

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$title}</h2>
</div>
<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do={$code}' method='post'>
<input type='hidden' name='template' value='{$defaults['template_id']}' />
<input type='hidden' name='step' value='3' />
<div class='acp-box page-template-form'>
	<h3>{$this->lang->words['template_form_header']}</h3>
	<table width='100%' cellpadding='0' cellspacing='0' class='form_table alternate_rows double_pad'>
		<tr>
			<td style='width: 40%'>
				<strong>{$this->lang->words['template_title']}</strong>
			</td>
			<td style='width: 60%'>
				{$form['name']}
			</td>
		</tr>
		<tr>
			<td>
				<strong>{$this->lang->words['template_key']}</strong><br /><span class='desctext'>{$this->lang->words['template_key_desc']}</span>
			</td>
			<td>
				{$form['key']}
			</td>
		</tr>
		<tr>
			<td>
				<strong>{$this->lang->words['template_description']}</strong><br /><span class='desctext'>{$this->lang->words['template_description_desc']}</span>
			</td>
			<td>
				{$form['description']}
			</td>
		</tr>
HTML;

if( $form['category'] )
{
	$IPBHTML .= <<<HTML
		<tr>
			<td>
				<strong>{$this->lang->words['select_category']}</strong>
			</td>
			<td>
				{$form['category']}
			</td>
		</tr>
HTML;
}

$IPBHTML .= <<<HTML
		<tr>
			<td colspan='2'>
				<strong>{$this->lang->words['template_html']}</strong><br /><span class='desctext'><a href='#' id='inline-template-tags-link'>{$this->lang->words['template_tag_help_link']}</a></span>
				<div class='clear' style='padding: 10px'><div id='content-label'>{$form['content']}</div></div>
			</td>
		</tr>
	</table>
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
 * Show the template tags
 *
 * @access	public
 * @param	array 		Categories
 * @param	array		Current template tags (blocks)
 * @return	string		HTML
 */
public function listTemplateTags( $categories, $blocks )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$this->lang->words['template_tag_header']}</h3>
	<ul class='acp-form templateTagRows'>
HTML;

if( $this->request['do'] == 'showtags' OR !$this->settings['ccs_use_ipb_wrapper'] )
{
	$IPBHTML .= <<<HTML
		<li class='template-tag-cat'>
			{$this->lang->words['special_tags_cat']}
		</li>
HTML;
}

if( $this->request['do'] == 'showtags' )
{
	$IPBHTML .= <<<HTML
		<li>
			<label>{$this->lang->words['tag__page_content']} <span class='desctext'>{$this->lang->words['tag__page_content_desc']}</span></label>
			<div class='template-tag'>{ccs special_tag="page_content"}</div>
		</li>
HTML;
}

if( !$this->settings['ccs_use_ipb_wrapper'] )
{
	$IPBHTML .= <<<HTML
		<li>
			<label>{$this->lang->words['tag__page_title']} <span class='desctext'>{$this->lang->words['tag__page_title_desc']}</span></label>
			<div class='template-tag'>{ccs special_tag="page_title"}</div>
		</li>
		<li>
			<label>{$this->lang->words['tag__meta_tags']} <span class='desctext'>{$this->lang->words['tag__meta_tags_desc']}</span></label>
			<div class='template-tag'>{ccs special_tag="meta_tags"}</div>
		</li>
HTML;
}

$categories[]	= array( 'container_id' => 0, 'container_name' => $this->lang->words['current_blocks_header'] );

if( is_array($categories) AND count($categories) )
{
	foreach( $categories as $category )
	{
		$IPBHTML .= <<<HTML
			<li class='template-tag-cat'>
				{$category['container_name']}
			</li>
HTML;
		if( is_array($blocks[ $category['container_id'] ]) AND count($blocks[ $category['container_id'] ]) )
		{
			foreach( $blocks[ $category['container_id'] ] as $block )
			{
				$tag	= '{parse block="' . $block['block_key'] . '"}';
				
				$IPBHTML .= <<<HTML
					<li>
						<label><a href='#' class='block-preview-link' id='{$block['block_id']}-blockPreview' title='{$this->lang->words['block_preview_alt']}'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/view.png' alt='{$this->lang->words['block_preview_alt']}' /></a> {$block['block_name']} <span class='desctext'>{$block['block_description']}</span></label>
						<div class='template-tag'>{$tag}</div>
					</li>
HTML;
			}
		}
	}
}


$IPBHTML .= <<<HTML
	</ul>
</div>
<script type='text/javascript'>
	$$('.block-preview-link').each( function(elem){
		$(elem).observe('click', acp.ccs.showBlockPreview );
	});
</script>
HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Show the template tags inline
 *
 * @access	public
 * @param	array 		Categories
 * @param	array		Current template tags (blocks)
 * @return	string		HTML
 */
public function inlineTemplateTags( $categories, $blocks )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='templateTags'>
	<h4>
		<div style='float:right'><img id='template-tags-link' style='cursor:pointer;' src='{$this->settings['skin_acp_url']}/_newimages/icons/help.png' alt='{$this->lang->words['template_tag_help_full']}' title='{$this->lang->words['template_tag_help_full']}' /> <img id='close-tags-link' style='cursor:pointer;' src='{$this->settings['skin_acp_url']}/_newimages/icons/cross.png' alt='{$this->lang->words['template_tag_help_close']}' title='{$this->lang->words['template_tag_help_close']}' /></div>
		{$this->lang->words['template_tag_header']}
	</h4>
	<ul>
HTML;

if( $this->request['do'] == 'showtags' OR !$this->settings['ccs_use_ipb_wrapper'] )
{
	$IPBHTML .= <<<HTML
		<li class='template-tag-cat'>
			{$this->lang->words['special_tags_cat']}
		</li>
HTML;
}

if( $this->request['do'] == 'showtags' )
{
	$IPBHTML .= <<<HTML
		<li>
			<label>{$this->lang->words['tag__page_content']}</label>
			<div>{ccs special_tag="page_content"}</div>
		</li>
HTML;
}

if( !$this->settings['ccs_use_ipb_wrapper'] )
{
	$IPBHTML .= <<<HTML
		<li>
			<label>{$this->lang->words['tag__page_title']}</label>
			<div>{ccs special_tag="page_title"}</div>
		</li>
		<li>
			<label>{$this->lang->words['tag__meta_tags']}</label>
			<div>{ccs special_tag="meta_tags"}</div>
		</li>
HTML;
}

$categories[]	= array( 'container_id' => 0, 'container_name' => $this->lang->words['current_blocks_header'] );

if( is_array($categories) AND count($categories) )
{
	foreach( $categories as $category )
	{
		$IPBHTML .= <<<HTML
			<li class='template-tag-cat'>
				{$category['container_name']}
			</li>
HTML;
		if( is_array($blocks[ $category['container_id'] ]) AND count($blocks[ $category['container_id'] ]) )
		{
			foreach( $blocks[ $category['container_id'] ] as $block )
			{
				$tag	= '{parse block="' . $block['block_key'] . '"}';
				
				$IPBHTML .= <<<HTML
					<li>
						<label><a href='#' class='block-preview-link' id='{$block['block_id']}-blockPreview' title='{$this->lang->words['block_preview_alt']}'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/view.png' alt='{$this->lang->words['block_preview_alt']}' /></a> {$block['block_name']}</label>
						<div>{$tag}</div>
					</li>
HTML;
			}
		}
	}
}

$IPBHTML .= <<<HTML
	</ul>
</div>
<script type='text/javascript'>
	if( $('template-tags-link') )
	{
HTML;

if( $this->request['do'] == 'showtags' )
{
	$IPBHTML .= <<<HTML
		$('template-tags-link').observe('click', acp.ccs.showTemplateTags );
HTML;
}
else
{
	$IPBHTML .= <<<HTML
		$('template-tags-link').observe('click', acp.ccs.showFullPageTags );
HTML;
}

$IPBHTML .= <<<HTML
	}
	
	if( $('close-tags-link') )
	{
		$('close-tags-link').observe('click', acp.ccs.closeInlineHelp );
	}
	
	$$('.block-preview-link').each( function(elem){
		$(elem).observe('click', acp.ccs.showBlockPreview );
	});
</script>
HTML;
//--endhtml--//
return $IPBHTML;
}

}