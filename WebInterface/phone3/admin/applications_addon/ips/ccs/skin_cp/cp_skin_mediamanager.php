<?php
/**
 * Invision Power Services
 * Media manager skin file
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
 
class cp_skin_mediamanager extends output
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
 * Show the main screen
 *
 * @access	public
 * @param	string		Current path
 * @param	array 		Folders in the path
 * @param	array 		Files in the path
 * @return	string		HTML
 */
public function overview( $path, Array $folders, Array $files )
{
$IPBHTML = "";
//--starthtml--//

$urlencodePath	= urlencode($path);

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['media_manager']}</h2>
	<ul class='context_menu'>
		<li>
			<a href='{$this->settings['base_url']}&amp;module=media&amp;section=manage&amp;do=newDir&amp;in={$urlencodePath}' title='{$this->lang->words['add_folder_alt']}'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/folder_add.png' alt='{$this->lang->words['icon']}' />
				{$this->lang->words['add_folder_alt']}
			</a>
		</li>
		<li>
			<a href='#' id='uploadTrigger'>
				<img src='{$this->settings['skin_acp_url']}/_newimages/icons/add.png' alt='{$this->lang->words['icon']}' />
				{$this->lang->words['media_upload_file']}
			</a>
		</li>
	</ul>
</div>
<div id="uploadForm">
	<form action='{$this->settings['base_url']}&amp;module=media&amp;section=manage&amp;do=upload&amp;in={$urlencodePath}' method='post' enctype="multipart/form-data">
	<div class="acp-box">
		<h3>{$this->lang->words['upload_file_h3']}</h3>
		<ul class="acp-form alternate_rows">
		<ul class="acp-form alternate_rows">
			<li>
				<label>{$this->lang->words['select_to_upload']}</label>
				<input name="FILE_UPLOAD" type="file" />
				<span class='desctext' style='float:right;'>{$this->lang->words['media_allowed_files']}</span>
			</li>
		<ul>
		
		<div class="acp-actionbar">
			<div class="centeraction">
				<input value="{$this->lang->words['upload__button']}" class="button primary" type="submit" />
			</div>
		</div>
	</div>
	</form>
</div>


<script type='text/javascript' src='{$this->settings['js_app_url']}acp.ccs.js'></script>
<script type='text/javascript'>
function confirmsubmit()
{
	var isOk = false;
	
	$$('#multi-action option').each( function( elem ) {
		Debug.write( "Found option: " + elem.value );
		
		if( elem.selected && elem.value == 'delete' )
		{
			if( confirm( "{$this->lang->words['multi_submit_confirm']}" ) )
			{
				isOk	= true;
			}
		}
		
		if( elem.selected && elem.value != 'delete' )
		{
			isOk	= true;
		}
	});
	
	return isOk;
}

</script>
<div class='acp-box'>
	<h3>{$this->lang->words['viewing_h_prefix']} {$path}</h3>
	<form action='{$this->settings['base_url']}&amp;module=media&amp;section=manage&amp;do=multi' method='post'>
	<input type='hidden' name='return' value='{$urlencodePath}' />
	<table width='100%' cellpadding='0' cellspacing='0' class='alternate_rows double_pad'>
		<tr>
			<th style='width: 2%'>&nbsp;</th>
			<th style='width: 46%'>{$this->lang->words['row_name']}</th>
			<th style='width: 30%'>{$this->lang->words['row_modified']}</th>
			<th style='width: 20%'>{$this->lang->words['row_size']}</th>
			<th style='width: 2%'>&nbsp;</th>
		</tr>
HTML;

if( !count( $folders ) && !count( $files ) )
{
	$IPBHTML .= <<<HTML
		<tr>
			<td colspan='5'>
				<div style='padding: 20px; text-align: center'>
					<em>{$this->lang->words['no_media_uploaded']}</em>
				</div>
			</td>
		</tr>
HTML;
}
else
{
	foreach( $folders as $object )
	{
		$path	= urlencode( $object['full_path'] );
		$mtime	= $this->registry->getClass('class_localization')->getDate( $object['last_modified'], 'SHORT', 1 );
		$id		= md5( $path );
		$size	= IPSLib::sizeFormat( $object['size'] );
		
		$name	= $object['name'] != '..' ?
					"<a href='{$this->settings['base_url']}&amp;module=media&amp;section=manage&amp;do=editFolder&amp;dir={$path}'><img src='{$this->settings['skin_acp_url']}/_newimages/ccs/folder.png' alt='{$this->lang->words['folder_alt']}' /></a>" :
					"<img src='{$this->settings['skin_acp_url']}/_newimages/ccs/folder.png' alt='{$this->lang->words['folder_alt']}' />";
					
	$IPBHTML .= <<<HTML
		<tr>			
HTML;

	if( $object['name'] != '..' )
	{
		$IPBHTML .= <<<HTML
			<td><input type='checkbox' name='folders[]' value='{$path}' /></td>
			<td>
				{$name}&nbsp;<strong><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=viewdir&amp;dir={$path}'>{$object['name']}</a></strong>
			</td>
			<td class='page_date'>
				<span class='desctext'>{$mtime}</span>
			</td>
			<td class='page_size'>
				<span class='desctext'>{$size}</span>
			</td>
			<td>
				<div class='manage'>
					<img class='ipbmenu' id="menu_{$id}" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt='{$this->lang->words['folder_options_alt']}' />
					<ul class='acp-menu' id='menu_{$id}_menucontent'>
						<li class='icon edit'><a href='{$this->settings['base_url']}&amp;module=media&amp;section=manage&amp;do=editFolder&amp;dir={$path}'>{$this->lang->words['edit_folder_name']}</a></li>
						<li class='icon delete'><a href='#' onclick="return acp.confirmDelete( '{$this->settings['base_url']}&amp;module=media&amp;section=manage&amp;do=emptyFolder&amp;dir={$path}' );">{$this->lang->words['empty_folder']}</a></li>
						<li class='icon delete'><a href='#' onclick="return acp.confirmDelete( '{$this->settings['base_url']}&amp;module=media&amp;section=manage&amp;do=deleteFolder&amp;dir={$path}' );">{$this->lang->words['delete_folder_link']}</a></li>
						<li class='icon export'><a href='{$this->settings['base_url']}&amp;module=media&amp;section=manage&amp;do=multi&amp;action=move&amp;folders[]={$path}'>{$this->lang->words['move_folder_link']}</a></li>
					</ul>
				</div>
			</td>
HTML;
	}
	else
	{
		$IPBHTML .= <<<HTML
			<td>&nbsp;</td>
			<td colspan='4'>{$name}&nbsp;<strong><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=viewdir&amp;dir={$path}'>{$object['name']}</a></strong></td>
HTML;
	}

	$IPBHTML .= <<<HTML
		</tr>
HTML;
	}

	foreach( $files as $object )
	{
		$path	= urlencode( $object['full_path'] );
		$icon	= $object['icon'] ? $object['icon'] : 'file.png';
		$size	= IPSLib::sizeFormat( $object['size'] );
		$mtime	= $this->registry->getClass('class_localization')->getDate( $object['last_modified'], 'SHORT', 1 );
		$id		= md5( $path );

		$url	= CCS_MEDIA_URL . str_replace( CCS_MEDIA, '', $object['full_path'] );

	$IPBHTML .= <<<HTML
		<tr>
			<td><input type='checkbox' name='pages[]' value='{$path}' /></td>
			<td>
				<a href='{$url}'><img src='{$this->settings['skin_acp_url']}/_newimages/ccs/{$icon}' alt='{$this->lang->words['file_alt']}' /></a>
				<strong><a href='{$url}' target='_blank'>{$object['name']}</a></strong>
			</td>
			<td>
				<span class='desctext'>{$mtime}</span>
			</td>
			<td>
				<span class='desctext'>{$size}</span>
			</td>
			<td>		
				<div class='manage'>
					<img class='ipbmenu' id="menu_{$id}" src='{$this->settings['skin_acp_url']}/_newimages/menu_open.png' alt='{$this->lang->words['file_options_alt']}' />
					<ul class='acp-menu' id='menu_{$id}_menucontent'>
						<li class='icon delete'><a href='#' onclick="return acp.confirmDelete( '{$this->settings['base_url']}&amp;module=media&amp;section=manage&amp;do=delete&amp;file={$path}' );">{$this->lang->words['media_delete_link']}</a></li>
						<li class='icon export'><a href='{$this->settings['base_url']}&amp;module=media&amp;section=manage&amp;do=multi&amp;action=move&amp;pages[]={$path}'>{$this->lang->words['media_move_link']}</a></li>
					</ul>
				</div>
			</td>
		</tr>
HTML;
	}
}

$IPBHTML .= <<<HTML
	</table>
	<div class="acp-actionbar">
		<div>
			{$this->lang->words['with_selected__form']} 
			<select name='action' id='multi-action'>
				<option value='move'>{$this->lang->words['form__move_items']}</option>
				<option value='delete'>{$this->lang->words['form__delete_items']}</option>
			</select>
			<input type="submit" value="{$this->lang->words['form__go']}" class="button primary" onclick="return confirmsubmit();" />
		</div>
	</div>
	</form>
</div>
HTML;
//--endhtml--//
return $IPBHTML;
}


/**
 * Form to specify where to move items to
 *
 * @access	public
 * @param	string		Start point for items we are moving
 * @param	array 		Folders we can omit as option to move to
 * @param	array 		Folders we can move to
 * @param	array 		Pages we are moving
 * @return	string		HTML
 */
public function moveToForm( $startPoint, $ignorable, $folders, $pages )
{
$IPBHTML = "";
//--starthtml--//

$defaultPath	= strtolower( str_replace( '\\', '/', realpath( CCS_MEDIA ) ) );

$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$this->lang->words['move_to_form_header']}</h3>
	<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=multi&amp;action=move' method='post'>
	<input type='hidden' name='return' value='{$this->request['return']}' />
	<ul class='acp-form alternate_rows'>
HTML;

	foreach( $folders as $folder )
	{
		if( $folder == $startPoint )
		{
			continue;
		}
		else
		{
			//-----------------------------------------
			// Can't move a folder into itself, or a child folder
			//-----------------------------------------
			
			foreach( $ignorable as $ignoreMe )
			{
				if( strpos( $folder, $ignoreMe ) !== false )
				{
					continue 2;
				}
			}
		}

		$display	= str_replace( $defaultPath . '/', '/', $folder );
			
		$IPBHTML .= <<<HTML
	<li>
		<input type='radio' name='moveto' value='{$folder}' />
		<img src='{$this->settings['skin_acp_url']}/_newimages/ccs/folder.png' alt='{$this->lang->words['folder_alt']}' /> {$display}
	</li>
HTML;
	}

$IPBHTML .= <<<HTML
	</ul>
	<div class="acp-actionbar">
		<input type='submit' value=' {$this->lang->words['move__button']} ' class="button primary" />
	</div>
	<h3>{$this->lang->words['moved_files_summary']}</h3>
	<ul class='alternate_rows filemanager'>
HTML;

if( is_array($this->request['folders']) AND count($this->request['folders']) )
{
	foreach( $this->request['folders'] as $folder )
	{
		$paths	= explode( '/', urldecode($folder) );
		$path	= array_pop( $paths );
		
		$IPBHTML .= <<<HTML
	<li class='record'>
		<div>
			<input type='checkbox' checked='checked' name='folders[]' value='{$folder}' />
			<img src='{$this->settings['skin_acp_url']}/_newimages/ccs/folder.png' alt='{$this->lang->words['folder_alt']}' />
			{$path}
		</div>
	</li>
HTML;

	}
}

if( is_array($pages) AND count($pages) )
{
	foreach( $pages as $page )
	{
		$paths	= explode( '/', urldecode($page) );
		$path	= array_pop( $paths );
		
		
		$IPBHTML .= <<<HTML
	<li class='record'>
		<div>
			<input type='checkbox' checked='checked' name='pages[]' value='{$page}' />
			<img src='{$this->settings['skin_acp_url']}/_newimages/ccs/file.png' alt='{$this->lang->words['file_alt']}' />
			{$path}
		</div>
	</li>
HTML;

	}
}
	
$IPBHTML .= <<<HTML
	</ul>
</div>	
</form>
HTML;
//--endhtml--//
return $IPBHTML;
}

/**
 * Create or edit directory
 *
 * @access	public
 * @param	string		Add/edit
 * @return	string		HTML
 */
public function directoryForm( $type )
{
$IPBHTML = "";
//--starthtml--//

$text	= $type == 'add' ? $this->lang->words['adding_a_folder'] : $this->lang->words['renaming_a_folder'];

$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$text}</h3>
	<form action='{$this->settings['base_url']}{$this->form_code}' method='post'>
HTML;

if( $type == 'add' )
{
	$formField		= $this->registry->output->formInput( 'folder_name' );
	
	$IPBHTML .= "		<input type='hidden' name='do' value='doCreateFolder' />
		<input type='hidden' name='parent' value='{$this->request['in']}' />";
}
else
{
	$folders		= explode ( '/', urldecode($this->request['dir']) );
	$folderName		= array_pop( $folders );
	$formField		= $this->registry->output->formInput( 'folder_name', $folderName );

	$IPBHTML .= "		<input type='hidden' name='do' value='doRenameFolder' />
		<input type='hidden' name='current' value='{$this->request['dir']}' />";
}

$IPBHTML .= <<<HTML
	<ul class='acp-form alternate_rows'>
		<li>
			<label>{$this->lang->words['set_folder_name']}</label>
			{$formField}
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

}