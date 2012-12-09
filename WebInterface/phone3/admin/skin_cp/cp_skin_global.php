<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Admin CP global skin templates
 * Last Updated: $Date: 2009-09-01 14:43:20 -0400 (Tue, 01 Sep 2009) $
 *
 * @author 		$Author: rikki $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @version		$Rev: 5073 $
 * @since		3.0.0
 *
 */
 
class cp_skin_global extends output
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
 * Done screen
 *
 * @access	public
 * @param	string 		Page title
 * @param	string		Link text
 * @param	string		Link url
 * @param	string		URL to redirect to
 * @return	string		HTML
 */
public function doneScreenView( $title, $link_text="", $link_url="", $redirect="" ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='tableborder'>
	<div class='tableheaderalt'>{$title}</div>

	<table width='100%' cellspacing='0' cellpadding='5' align='center' border='0'>
		<tr>
			<td class='tablesubheader' width='100%' align='center'>&nbsp;</td>
			<td class='tablesubheader' align='center'>&nbsp;</td>
		</tr>
		<tr>
			<td align='center' class='tablerow1' colspan='2' ><a href='{$this->settings['base_url']}&amp;{$link_url}'>{$link_text}</a></td>
		</tr>
		<tr>
			<td align='center' class='tablerow1' colspan='2' ><a href='{$this->settings['_base_url']}'>{$this->lang->words['gl_gotohome']}</a></td>
		</tr>
	</table>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Editor template for ACP
 *
 * @access	public
 * @param	string 		From field name
 * @param	string		Initial content for the editor
 * @param	string		Path to the images
 * @param	integer		Whether RTE is enabled (1) or not (0)
 * @param	string		Editor id
 * @param	string		Emoticon data
 * @return	string		HTML
 */
public function ips_editor($form_field="",$initial_content="",$images_path="",$rte_mode=0,$editor_id='ed-0',$smilies='') {

$IPBHTML = "";
//--starthtml--//

$this->settings['extraJsModules']	.= ",editor";
$bbcodes 							= IPSLib::fetchBbcodeAsJson();
$show_sidebar						= IPSCookie::get('emoticon_sidebar');
$show_sidebar_class 				= $show_sidebar && $this->settings['_remove_emoticons'] == 0 ? 'with_sidebar' : '';
$show_sidebar_style					= $show_sidebar && $this->settings['_remove_emoticons'] == 0 ? '' : "style='display:none'";
$show_sidebar_link					= $show_sidebar && $this->settings['_remove_emoticons'] == 0 ? 'true' : 'false';

$IPBHTML .= <<<EOF
	<!--top-->
	<input type='hidden' name='{$editor_id}_wysiwyg_used' id='{$editor_id}_wysiwyg_used' value='0' />
	<input type='hidden' name='editor_ids[]' value='{$editor_id}' />
	<div class='ips_editor {$show_sidebar_class}' id='editor_{$editor_id}'>
EOF;
	if( $this->settings['_remove_emoticons'] == 0 )
	{
		$IPBHTML .= <<<EOF
		<div class='sidebar row1 altrow' id='{$editor_id}_sidebar' {$show_sidebar_style}>
			<h4><img src='{$this->settings['img_url']}/close_popup.png' alt='{$this->lang->words['icon']}' id='{$editor_id}_close_sidebar' /><span>{$this->lang->words['emoticons_template_title']}</span></h4>
			<div id='{$editor_id}_emoticon_holder' class='emoticon_holder'></div>
			<div class='show_all_emoticons' id='{$editor_id}_showall_bar'>
				<input type='button' value='Show All' id='{$editor_id}_showall_emoticons' class='input_submit emoticons' />
			</div>
		</div>
EOF;
	}
	
	$IPBHTML .= <<<EOF
		<div id='{$editor_id}_controls' class='controls'>
			<ul id='{$editor_id}_toolbar_1' class='toolbar' style='display: none'>
				<li class='left'>
					<span id='{$editor_id}_cmd_removeformat' class='rte_control rte_button' title='{$this->lang->words['js_tt_noformat']}'><img src='{$this->settings['img_url']}/rte_icons/remove_formatting.png' alt='{$this->lang->words['js_tt_noformat']}' /></span>
				</li>
				<li class='left'>
					<span id='{$editor_id}_cmd_togglesource' class='rte_control rte_button' title='{$this->lang->words['js_tt_htmlsource']}'><img src='{$this->settings['img_url']}/rte_icons/toggle_source.png' alt='{$this->lang->words['js_tt_htmlsource']}' /></span>
				</li>
				<li class='left'>
					<span id='{$editor_id}_cmd_otherstyles' class='rte_control rte_menu rte_special' title='{$this->lang->words['box_other_desc']}' style='display: none'>{$this->lang->words['box_other']}</span>
				</li>
				<li class='left'>
					<span id='{$editor_id}_cmd_fontname' class='rte_control rte_menu rte_font' title='{$this->lang->words['box_font_desc']}'>{$this->lang->words['box_font']}</span>
				</li>
				<li class='left'>
					<span id='{$editor_id}_cmd_fontsize' class='rte_control rte_menu rte_fontsize' title='{$this->lang->words['box_size_desc']}'>{$this->lang->words['box_size']}</span>
				</li>
				<li class='left'>
					<span id='{$editor_id}_cmd_forecolor' class='rte_control rte_palette' title='{$this->lang->words['js_tt_font_col']}'><img src='{$this->settings['img_url']}/rte_icons/font_color.png' alt='{$this->lang->words['js_tt_font_col']}' /></span>
				</li>
				<!--<li class='left'>
					<span id='{$editor_id}_cmd_backcolor' class='rte_control rte_palette' title='{$this->lang->words['js_tt_back_col']}'><img src='{$this->settings['img_url']}/rte_icons/background_color.png' alt='{$this->lang->words['js_tt_back_col']}' /></span>
				</li>-->

				<li class='right'>
					<span id='{$editor_id}_cmd_spellcheck' class='rte_control rte_button' title='{$this->lang->words['js_tt_spellcheck']}'><img src='{$this->settings['img_url']}/rte_icons/spellcheck.png' alt='{$this->lang->words['js_tt_spellcheck']}' /></span>
				</li>
				<li class='right'>
					<span id='{$editor_id}_cmd_r_small' class='rte_control rte_button' title='{$this->lang->words['js_tt_resizesmall']}'><img src='{$this->settings['img_url']}/rte_icons/resize_small.png' alt='{$this->lang->words['js_tt_resizesmall']}' /></span>
				</li>
				<li class='right'>
					<span id='{$editor_id}_cmd_r_big' class='rte_control rte_button' title='{$this->lang->words['js_tt_resizebig']}'><img src='{$this->settings['img_url']}/rte_icons/resize_big.png' alt='{$this->lang->words['js_tt_resizebig']}' /></span>
				</li>
				<li class='right sep'>
					<span id='{$editor_id}_cmd_help' class='rte_control rte_button' title='{$this->lang->words['js_tt_help']}'><a href='{parse url="app=forums&amp;module=extras&amp;section=legends&amp;do=bbcode" base="public"}' title='{$this->lang->words['js_tt_help']}'><img src='{$this->settings['img_url']}/rte_icons/help.png' alt='{$this->lang->words['js_tt_help']}' /></a></span>
				</li>			
				<li class='right sep'>
					<span id='{$editor_id}_cmd_undo' class='rte_control rte_button' title='{$this->lang->words['js_tt_undo']}'><img src='{$this->settings['img_url']}/rte_icons/undo.png' alt='{$this->lang->words['js_tt_undo']}' /></span>
				</li>
				<li class='right'>
					<span id='{$editor_id}_cmd_redo' class='rte_control rte_button' title='{$this->lang->words['js_tt_redo']}'><img src='{$this->settings['img_url']}/rte_icons/redo.png' alt='{$this->lang->words['js_tt_redo']}' /></span>
				</li>
EOF;
			if( $this->settings['posting_allow_rte'] == 1 )
			{
$IPBHTML .= <<<EOF
				<li class='right'>
					<!--<span id='{$editor_id}_cmd_switcheditor' class='rte_control rte_button' title='{$this->lang->words['js_tt_switcheditor']}'><img src='{$this->settings['img_url']}/rte_icons/switch.png' alt='{$this->lang->words['js_tt_switcheditor']}' /></span>-->
				</li>
EOF;
			}
$IPBHTML .= <<<EOF
			</ul>
			<ul id='{$editor_id}_toolbar_2' class='toolbar' style='display: none'>
				<li>
					<span id='{$editor_id}_cmd_bold' class='rte_control rte_button' title='{$this->lang->words['js_tt_bold']}'><img src='{$this->settings['img_url']}/rte_icons/bold.png' alt='{$this->lang->words['js_tt_bold']}' /></span>
				</li>
				<li>
					<span id='{$editor_id}_cmd_italic' class='rte_control rte_button' title='{$this->lang->words['js_tt_italic']}'><img src='{$this->settings['img_url']}/rte_icons/italic.png' alt='{$this->lang->words['js_tt_italic']}' /></span>
				</li>
				<li>
					<span id='{$editor_id}_cmd_underline' class='rte_control rte_button' title='{$this->lang->words['js_tt_underline']}'><img src='{$this->settings['img_url']}/rte_icons/underline.png' alt='{$this->lang->words['js_tt_underline']}' /></span>
				</li>
				<li class='sep'>
					<span id='{$editor_id}_cmd_strikethrough' class='rte_control rte_button' title='{$this->lang->words['js_tt_strike']}'><img src='{$this->settings['img_url']}/rte_icons/strike.png' alt='{$this->lang->words['js_tt_strike']}' /></span>
				</li>
				<li>
					<span id='{$editor_id}_cmd_subscript' class='rte_control rte_button' title='{$this->lang->words['js_tt_sub']}'><img src='{$this->settings['img_url']}/rte_icons/subscript.png' alt='{$this->lang->words['js_tt_sub']}' /></span>
				</li>
				<li class='sep'>
					<span id='{$editor_id}_cmd_superscript' class='rte_control rte_button' title='{$this->lang->words['js_tt_sup']}'><img src='{$this->settings['img_url']}/rte_icons/superscript.png' alt='{$this->lang->words['js_tt_sup']}' /></span>
				</li>
				<li>
					<span id='{$editor_id}_cmd_insertunorderedlist' class='rte_control rte_button' title='{$this->lang->words['js_tt_list']}'><img src='{$this->settings['img_url']}/rte_icons/unordered_list.png' alt='{$this->lang->words['js_tt_list']}' /></span>
				</li>
				<li class='sep'>
					<span id='{$editor_id}_cmd_insertorderedlist' class='rte_control rte_button' title='{$this->lang->words['js_tt_list']}'><img src='{$this->settings['img_url']}/rte_icons/ordered_list.png' alt='{$this->lang->words['js_tt_list']}' /></span>
				</li>
EOF;

			if( $this->settings['_remove_emoticons'] == 0 )
			{
$IPBHTML .= <<<EOF
				<li>
					<span id='{$editor_id}_cmd_emoticons' class='rte_control rte_button' title='{$this->lang->words['js_tt_emoticons']}'><img src='{$this->settings['img_url']}/rte_icons/emoticons.png' alt='{$this->lang->words['js_tt_emoticons']}' /></span>
				</li>
EOF;
			}

$IPBHTML .= <<<EOF
				<li>
					<span id='{$editor_id}_cmd_link' class='rte_control rte_palette' title='{$this->lang->words['js_tt_link']}'><img src='{$this->settings['img_url']}/rte_icons/link.png' alt='{$this->lang->words['js_tt_link']}' /></span>
				</li>
				<li>
					<span id='{$editor_id}_cmd_image' class='rte_control rte_palette' title='{$this->lang->words['js_tt_image']}'><img src='{$this->settings['img_url']}/rte_icons/picture.png' alt='{$this->lang->words['js_tt_image']}' /></span>
				</li>
				<li>
					<span id='{$editor_id}_cmd_email' class='rte_control rte_palette' title='{$this->lang->words['js_tt_email']}'><img src='{$this->settings['img_url']}/rte_icons/email.png' alt='{$this->lang->words['js_tt_email']}' /></span>
				</li>
				<li>
					<span id='{$editor_id}_cmd_ipb_quote' class='rte_control rte_button' title='{$this->lang->words['js_tt_quote']}'><img src='{$this->settings['img_url']}/rte_icons/quote.png' alt='{$this->lang->words['js_tt_quote']}' /></span>
				</li>
				<li>
					<span id='{$editor_id}_cmd_ipb_code' class='rte_control rte_button' title='{$this->lang->words['js_tt_code']}'><img src='{$this->settings['img_url']}/rte_icons/code.png' alt='{$this->lang->words['js_tt_code']}' /></span>
				</li>
				<li>
					<span id='{$editor_id}_cmd_media' class='rte_control rte_palette' title='{$this->lang->words['js_tt_media']}'><img src='{$this->settings['img_url']}/rte_icons/media.png' alt='{$this->lang->words['js_tt_media']}' /></span>
				</li>
				<li class='right'>
					<span id='{$editor_id}_cmd_justifyright' class='rte_control rte_button' title='{$this->lang->words['js_tt_right']}'><img src='{$this->settings['img_url']}/rte_icons/align_right.png' alt='{$this->lang->words['js_tt_right']}' /></span>
				</li>
				<li class='right'>
					<span id='{$editor_id}_cmd_justifycenter' class='rte_control rte_button' title='{$this->lang->words['js_tt_center']}'><img src='{$this->settings['img_url']}/rte_icons/align_center.png' alt='{$this->lang->words['js_tt_center']}' /></span>
				</li>
				<li class='right'>
					<span id='{$editor_id}_cmd_justifyleft' class='rte_control rte_button' title='{$this->lang->words['js_tt_left']}'><img src='{$this->settings['img_url']}/rte_icons/align_left.png' alt='{$this->lang->words['js_tt_left']}' /></span>
				</li>
				<li class='right sep'>
					<span id='{$editor_id}_cmd_indent' class='rte_control rte_button' title='{$this->lang->words['js_tt_indent']}'><img src='{$this->settings['img_url']}/rte_icons/indent.png' alt='{$this->lang->words['js_tt_indent']}' /></span>
				</li>
				<li class='right'>
					<span id='{$editor_id}_cmd_outdent' class='rte_control rte_button' title='{$this->lang->words['js_tt_outdent']}'><img src='{$this->settings['img_url']}/rte_icons/outdent.png' alt='{$this->lang->words['js_tt_outdent']}' /></span>
				</li>
			</ul>
		</div>
		<div id='{$editor_id}_wrap' class='editor'>
			<textarea name="{$form_field}" class="input_rte" id="{$editor_id}_textarea" rows="10" cols="60" tabindex="0">{$initial_content}</textarea>
		</div>
	</div>

	<!-- Toolpanes -->
	<script type="text/javascript">
	//<![CDATA[
	$('{$editor_id}_toolbar_1').show();
	$('{$editor_id}_toolbar_2').show();
	// Rikki: Had to remove <form>... </form> because Opera would see </form> and not pass the topic icons / hidden fields properly. Tried "</" + "form>" but when it is parsed, it had the same affect
	ipb.editor_values.get('templates')['link'] = new Template("<label for='#{id}_url'>{$this->lang->words['js_template_url']}</label><input type='text' class='input_text' id='#{id}_url' value='http://' tabindex='10' /><label for='#{id}_urltext'>{$this->lang->words['js_template_link']}</label><input type='text' class='input_text _select' id='#{id}_urltext' value='{$this->lang->words['js_template_default']}' tabindex='11' /><input type='submit' class='input_submit' value='{$this->lang->words['js_template_insert_link']}' tabindex='12' />");

	ipb.editor_values.get('templates')['image'] = new Template("<label for='#{id}_img'>{$this->lang->words['js_template_imageurl']}</label><input type='text' class='input_text' id='#{id}_img' value='http://' tabindex='10' /><input type='submit' class='input_submit' value='{$this->lang->words['js_template_insert_img']}' tabindex='11' />");

	ipb.editor_values.get('templates')['email'] = new Template("<label for='#{id}_email'>{$this->lang->words['js_template_email_url']}</label><input type='text' class='input_text' id='#{id}_email' tabindex='10' /><label for='#{id}_emailtext'>{$this->lang->words['js_template_link']}</label><input type='text' class='input_text _select' id='#{id}_emailtext' value='{$this->lang->words['js_template_email_me']}' tabindex='11' /><input type='submit' class='input_submit' value='{$this->lang->words['js_template_insert_email']}' tabindex='12' />");

	ipb.editor_values.get('templates')['media'] = new Template("<label for='#{id}_media'>{$this->lang->words['js_template_media_url']}</label><input type='text' class='input_text' id='#{id}_media' value='http://' tabindex='10' /><input type='submit' class='input_submit' value='{$this->lang->words['js_template_insert_media']}' tabindex='11' />");

	ipb.editor_values.get('templates')['generic'] = new Template("<div class='rte_title'>#{title}</div><strong>{$this->lang->words['js_template_example']}</strong><pre>#{example}</pre><label for='#{id}_option' class='optional'>#{option_text}</label><input type='text' class='input_text optional' id='#{id}_option' tabindex='10' /><label for='#{id}_text' class='tagcontent'>#{value_text}</label><input type='text' class='input_text _select tagcontent' id='#{id}_text' tabindex='11' /><input type='submit' class='input_submit' value='{$this->lang->words['js_template_add']}' tabindex='12' />");

	ipb.editor_values.get('templates')['toolbar'] = new Template("<ul id='#{id}_toolbar_#{toolbarid}' class='toolbar' style='display: none'>#{content}</ul>");

	ipb.editor_values.get('templates')['button'] = new Template("<li><span id='#{id}_cmd_custom_#{cmd}' class='rte_control rte_button specialitem' title='#{title}'><img src='{$this->settings['img_url']}/rte_icons/#{img}' alt='{$this->lang->words['icon']}' /></span></li>");

	ipb.editor_values.get('templates')['menu_item'] = new Template("<li id='#{id}_cmd_custom_#{cmd}' class='specialitem clickable'>#{title}</li>");

	ipb.editor_values.get('templates')['togglesource'] = new Template("<fieldset id='#{id}_ts_controls' class='submit' style='text-align: left'><input type='button' class='input_submit' value='{$this->lang->words['js_template_update']}' id='#{id}_ts_update' />&nbsp;&nbsp;&nbsp; <a href='#' id='#{id}_ts_cancel' class='cancel'>{$this->lang->words['js_template_cancel_source']}</a></fieldset>");

	ipb.editor_values.get('templates')['emoticons_showall'] = new Template("<input class='input_submit emoticons' type='button' id='#{id}_all_emoticons' value='{$this->lang->words['show_all_emoticons']}' />");

	ipb.editor_values.get('templates')['emoticon_wrapper'] = new Template("<h4><span>{$this->lang->words['emoticons_template_title']}</span></h4><div id='#{id}_emoticon_holder' class='emoticon_holder'></div>");

	// Add smilies into the mix
	ipb.editor_values.set( 'show_emoticon_link', true );
	ipb.editor_values.set( 'emoticons', \$H({ $smilies }) );
	ipb.editor_values.set( 'bbcodes', \$H( $bbcodes ) );

	ipb.vars['emoticon_url'] = "{$this->settings['emoticons_url']}";

	Event.observe(window, 'load', function(e){
		ipb.editors[ '{$editor_id}' ] = new ipb.editor( '{$editor_id}', USE_RTE );
	});

	//]]>
	</script>

EOF;
//--endhtml--//
return $IPBHTML;
}

/**
 * Page wrapper for popup windows
 *
 * @access	public
 * @param	string		Document character set
 * @param	array 		CSS Files
 * @return	string		HTML
 */
public function global_main_popup_wrapper($IPS_DOC_CHAR_SET=IPS_DOC_CHAR_SET, $cssFiles=array() ) {

$IPBHTML = "";
//--starthtml--//

$_path		= IPS_PUBLIC_SCRIPT;
$_useRte	= ($this->memberData['members_editor_choice'] == 'rte' && $this->memberData['_canUseRTE']) === TRUE ? 1 : 0;
$boardurl = ($this->registry->output->isHTTPS) ? $this->settings['board_url_https'] : $this->settings['board_url'];

$IPBHTML .= <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xml:lang="en" lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="content-type" content="text/html; charset={$IPS_DOC_CHAR_SET}" />
	<meta http-equiv="Pragma" content="no-cache" />
	<meta http-equiv="Cache-Control" content="no-cache" />
	<meta http-equiv="Expires" content="Fri, 01 January 1999 01:00:00 GMT" />
	<link rel="shortcut icon" href='{$boardurl}/favicon.ico' />

	<title><%TITLE%></title>
	<script type='text/javascript'>
		jsDebug = 1;
		USE_RTE = {$_useRte};

	</script>
EOF;

/** CSS ----------------------------------------- */
if ( $this->settings['use_minify'] )
{
	$_basics  = CP_DIRECTORY . '/skin_cp/acp.css,' . CP_DIRECTORY . '/skin_cp/acp_content.css,' . CP_DIRECTORY . '/skin_cp/acp_editor.css';
	$_others  = '';

	if ( is_array( $cssFiles['import'] ) AND count( $cssFiles['import'] ) )
	{
		foreach( $cssFiles['import'] as $data )
		{
			$_others .= ',' . preg_replace( "#^(.*)/(" . CP_DIRECTORY . "/.*)$#", "$2", $data['content'] );
		}
	}

	$IPBHTML .= "\n\t<link rel=\"stylesheet\" type=\"text/css\" media='screen' href=\"{$this->settings['public_dir']}min/index.php?f={$_basics}{$_others}\">\n";
}
else
{
	$IPBHTML .= <<<HTML
	<style type='text/css' media='all'>
		@import url( "{$this->settings['skin_acp_url']}/acp.css" );
		@import url( "{$this->settings['skin_acp_url']}/acp_content.css" );
		@import url( "{$this->settings['skin_acp_url']}/acp_editor.css" );
	</style>
HTML;

	if( is_array($cssFiles['import']) AND count($cssFiles['import']) )
	{
		foreach( $cssFiles['import'] as $data )
		{
			$IPBHTML .= <<<EOF
			<link rel="stylesheet" type="text/css" {$data['attributes']} href="{$data['content']}" />
EOF;
		}
	}
}

$IPBHTML .= <<<HTML
	<!--[if IE]>
		<style type='text/css' media='all'>
			@import url( "{$this->settings['skin_acp_url']}/acp_ie_tweaks.css" );
		</style>
	<![endif]-->
HTML;

if( is_array($cssFiles['inline']) AND count($cssFiles['inline']) )
{
	$IPBHTML .= <<<EOF
		<style type='text/css' media="all">
EOF;

	foreach( $cssFiles['inline'] as $data )
	{
		$IPBHTML .= $data['content'];
	}

	$IPBHTML .= <<<EOF
		</style>
EOF;
}

/** JS ----------------------------------------- */
if ( $this->settings['use_minify'] )
{
	$_others = ',' . CP_DIRECTORY . '/js/acp.js,' . CP_DIRECTORY . '/js/acp.' . implode('.js,' . CP_DIRECTORY . '/js/acp.', array( 'menu', 'livesearch', 'styles', 'tabs' ) ) . '.js';

	$IPBHTML .= <<<HTML

	<script type='text/javascript' src='{$this->settings['public_dir']}min/index.php?g=js'></script>
HTML;

	$IPBHTML .= "\n\t<script type='text/javascript' src='{$this->settings['public_dir']}min/index.php?f=public/js/ipb.js" . $_others;

	if ( $this->settings['extraJsModules'] )
	{
		$_modules		= explode( ',', $this->settings['extraJsModules'] );
		$_loadModules	= '';
		$_seenModules	= array();

		foreach( $_modules as $_jsModule )
		{
			if( !$_jsModule )
			{
				continue;
			}

			if( in_array( $_jsModule, $_seenModules ) )
			{
				continue;
			}

			$_seenModules[] = $_jsModule;

			$_loadModules	.= ",public/js/ips." . $_jsModule . ".js";
		}

		$IPBHTML .= $_loadModules . "'></script>\n";
	}
	else
	{
		$IPBHTML .= "'></script>\n";
	}
}
else
{
	$IPBHTML .= <<<HTML
		<script type="text/javascript" src="{$this->settings['public_dir']}js/3rd_party/prototype.js"></script>
		<script type='text/javascript' src='{$this->settings['public_dir']}js/3rd_party/scriptaculous/scriptaculous-cache.js'></script>
		<script type="text/javascript" src='{$this->settings['public_dir']}js/ipb.js?load={$this->settings['extraJsModules']}'></script>
		<script type='text/javascript' src='{$this->settings['js_main_url']}acp.menu.js'></script>
		<script type='text/javascript' src='{$this->settings['js_main_url']}acp.js'></script>
		<script type="text/javascript" src='{$this->settings['js_main_url']}acp.livesearch.js'></script>
		<script type="text/javascript" src='{$this->settings['js_main_url']}acp.styles.js'></script>
		<script type="text/javascript" src='{$this->settings['js_main_url']}acp.tabs.js'></script>
HTML;
}

$IPBHTML .= <<<HTML
	<!--<script type='text/javascript' src='http://getfirebug.com/releases/lite/1.2/firebug-lite-compressed.js'></script>-->
	<script type='text/javascript' language='javascript'>
		Loader.boot();
	</script>

HTML;

if( $this->settings['acp_tutorial_mode'] )
{
	$IPBHTML .= "<script type='text/javascript' src='{$this->settings['js_main_url']}acp.help.js'></script>\n";
}

$IPBHTML .= <<<EOF
	<script type='text/javascript' language='javascript'>
	//<![CDATA[
		ipb.vars['st']	= "{$this->request['st']}";
		ipb.vars['base_url']	= "{$this->settings['_base_url']}";
		ipb.vars['front_url']	= "{$this->settings['board_url']}/index.php?";
		ipb.vars['app_url']		= "{$this->settings['base_url']}";
		ipb.vars['image_url'] 	= "{$this->settings['skin_app_url']}/images/";
		ipb.vars['md5_hash']	= "{$form_hash}";
		/* ---- cookies ----- */
		ipb.vars['cookie_id'] 			= '{$this->settings['cookie_id']}';
		ipb.vars['cookie_domain'] 		= '{$this->settings['cookie_domain']}';
		ipb.vars['cookie_path']			= '{$this->settings['cookie_path']}';
		ipb.templates['close_popup']	= "<img src='{$this->settings['img_url']}/close_popup.png' alt='x' />";
		ipb.templates['page_jump']		= new Template("<div id='#{id}_wrap' class='ipbmenu_content'><h3 class='bar'>{$this->lang->words['gl_pagejump']}</h3><input type='text' class='input_text' id='#{id}_input' size='8' /> <input type='submit' value='Go' class='input_submit add_folder' id='#{id}_submit' /></div>");
		ipb.templates['ajax_loading'] 	= "<div id='ajax_loading'>{$this->lang->words['gl_loading']}</div>";
		acp = new IPBACP;
	//]]>
	</script>

	<script type="text/javascript" src="{$this->settings['board_url']}/cache/lang_cache/{$this->lang->lang_id}/acp.lang.js" charset="{$IPS_DOC_CHAR_SET}"></script>
</head>
<body<%BODYEXTRA%> id='ipboard_body' class='popupwindow'>
<div id='loading-layer' style='display:none'>
	<div id='loading-layer-shadow'>
	   <div id='loading-layer-inner' >
		   <img src='{$this->settings['skin_acp_url']}/images/loading_anim.gif' style='vertical-align:middle' border='0' />
		   <span style='font-weight:bold' id='loading-layer-text'>{$this->lang->words['ajax_please_wait']}</span>
	   </div>
	</div>
</div>
<div id='main_content'>
	<div id='content_wrap'>
		<%CONTENT%>
	</div>
</div>
</body>
</html>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Page wrapper without the "fluff" - minimal wrapper
 *
 * @access	public
 * @param	string 		Document character set
 * @param	array 		CSS Files
 * @return	string		HTML
 */
public function global_main_wrapper_no_furniture($IPS_DOC_CHAR_SET=IPS_DOC_CHAR_SET, $cssFiles=array() ) {

$_useRte	= ($this->memberData['members_editor_choice'] == 'rte' && $this->memberData['_canUseRTE']) === TRUE ? 1 : 0;
$boardurl = ($this->registry->output->isHTTPS) ? $this->settings['board_url_https'] : $this->settings['board_url'];
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xml:lang="en" lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="content-type" content="text/html; charset={$IPS_DOC_CHAR_SET}" />
	<meta http-equiv="Pragma" content="no-cache" />
	<meta http-equiv="Cache-Control" content="no-cache" />
	<meta http-equiv="Expires" content="Fri, 01 January 1999 01:00:00 GMT" />
	<link rel="shortcut icon" href='{$boardurl}/favicon.ico' />

	<title><%TITLE%></title>
	<script type='text/javascript'>
		jsDebug = 1;
		USE_RTE = {$_useRte};
		
	</script>
EOF;

/** CSS ----------------------------------------- */
if ( $this->settings['use_minify'] )
{
	$_basics  = CP_DIRECTORY . '/skin_cp/acp.css,' . CP_DIRECTORY . '/skin_cp/acp_content.css,' . CP_DIRECTORY . '/skin_cp/acp_editor.css';
	$_others  = '';
	
	if ( is_array( $cssFiles['import'] ) AND count( $cssFiles['import'] ) )
	{
		foreach( $cssFiles['import'] as $data )
		{
			$_others .= ',' . preg_replace( "#^(.*)/(" . CP_DIRECTORY . "/.*)$#", "$2", $data['content'] );
		}
	}
	
	$IPBHTML .= "\n\t<link rel=\"stylesheet\" type=\"text/css\" media='screen' href=\"{$this->settings['public_dir']}min/index.php?f={$_basics}{$_others}\">\n";
}
else
{
	$IPBHTML .= <<<HTML
	<style type='text/css' media='all'>
		@import url( "{$this->settings['skin_acp_url']}/acp.css" );
		@import url( "{$this->settings['skin_acp_url']}/acp_content.css" );
		@import url( "{$this->settings['skin_acp_url']}/acp_editor.css" );
	</style>
HTML;

	if( is_array($cssFiles['import']) AND count($cssFiles['import']) )
	{
		foreach( $cssFiles['import'] as $data )
		{
			$IPBHTML .= <<<EOF
			<link rel="stylesheet" type="text/css" {$data['attributes']} href="{$data['content']}" />
EOF;
		}
	}
}

$IPBHTML .= <<<HTML
	<!--[if IE]>
		<style type='text/css' media='all'>
			@import url( "{$this->settings['skin_acp_url']}/acp_ie_tweaks.css" );
		</style>
	<![endif]-->
HTML;

if( is_array($cssFiles['inline']) AND count($cssFiles['inline']) )
{
	$IPBHTML .= <<<EOF
		<style type='text/css' media="all">
EOF;

	foreach( $cssFiles['inline'] as $data )
	{
		$IPBHTML .= $data['content'];
	}

	$IPBHTML .= <<<EOF
		</style>
EOF;
}

/** JS ----------------------------------------- */
if ( $this->settings['use_minify'] )
{
	$_others = ',' . CP_DIRECTORY . '/js/acp.js,' . CP_DIRECTORY . '/js/acp.' . implode('.js,' . CP_DIRECTORY . '/js/acp.', array( 'menu', 'livesearch', 'styles', 'tabs' ) ) . '.js';
	
	$IPBHTML .= <<<HTML
	
	<script type='text/javascript' src='{$this->settings['public_dir']}min/index.php?g=js'></script>
HTML;

	$IPBHTML .= "\n\t<script type='text/javascript' src='{$this->settings['public_dir']}min/index.php?f=public/js/ipb.js" . $_others;
	
	if ( $this->settings['extraJsModules'] )
	{
		$_modules		= explode( ',', $this->settings['extraJsModules'] );
		$_loadModules	= '';
		$_seenModules	= array();
		
		foreach( $_modules as $_jsModule )
		{
			if( !$_jsModule )
			{
				continue;
			}
			
			if( in_array( $_jsModule, $_seenModules ) )
			{
				continue;
			}
			
			$_seenModules[] = $_jsModule;
			
			$_loadModules	.= ",public/js/ips." . $_jsModule . ".js";
		}
		
		$IPBHTML .= $_loadModules . "'></script>\n";
	}
	else
	{
		$IPBHTML .= "'></script>\n";
	}
}
else
{
	$IPBHTML .= <<<HTML
		<script type="text/javascript" src="{$this->settings['public_dir']}js/3rd_party/prototype.js"></script>
		<script type='text/javascript' src='{$this->settings['public_dir']}js/3rd_party/scriptaculous/scriptaculous-cache.js'></script>
		<script type="text/javascript" src='{$this->settings['public_dir']}js/ipb.js?load={$this->settings['extraJsModules']}'></script>
		<script type='text/javascript' src='{$this->settings['js_main_url']}acp.menu.js'></script>
		<script type='text/javascript' src='{$this->settings['js_main_url']}acp.js'></script>
		<script type="text/javascript" src='{$this->settings['js_main_url']}acp.livesearch.js'></script>
		<script type="text/javascript" src='{$this->settings['js_main_url']}acp.styles.js'></script>
		<script type="text/javascript" src='{$this->settings['js_main_url']}acp.tabs.js'></script>
HTML;
}

$IPBHTML .= <<<HTML
	<!--<script type='text/javascript' src='http://getfirebug.com/releases/lite/1.2/firebug-lite-compressed.js'></script>-->
	<script type='text/javascript' language='javascript'>
		Loader.boot();
	</script>
		
HTML;

if( $this->settings['acp_tutorial_mode'] )
{
	$IPBHTML .= "<script type='text/javascript' src='{$this->settings['js_main_url']}acp.help.js'></script>\n";
}

$IPBHTML .= <<<HTML
	<script type='text/javascript' language='javascript'>
	//<![CDATA[
		ipb.vars['st']	= "{$this->request['st']}";
		ipb.vars['base_url']	= "{$this->settings['_base_url']}";
		ipb.vars['front_url']	= "{$this->settings['board_url']}/index.php?";
		ipb.vars['app_url']		= "{$this->settings['base_url']}";
		ipb.vars['image_url'] 	= "{$this->settings['skin_app_url']}/images/";
		ipb.vars['md5_hash']	= "{$form_hash}";
		/* ---- cookies ----- */
		ipb.vars['cookie_id'] 			= '{$this->settings['cookie_id']}';
		ipb.vars['cookie_domain'] 		= '{$this->settings['cookie_domain']}';
		ipb.vars['cookie_path']			= '{$this->settings['cookie_path']}';
		ipb.templates['close_popup']	= "<img src='{$this->settings['img_url']}/close_popup.png' alt='x' />";
		ipb.templates['page_jump']		= new Template("<div id='#{id}_wrap' class='ipbmenu_content'><h3 class='bar'>{$this->lang->words['gl_pagejump']}</h3><input type='text' class='input_text' id='#{id}_input' size='8' /> <input type='submit' value='Go' class='input_submit add_folder' id='#{id}_submit' /></div>");
		ipb.templates['ajax_loading'] 	= "<div id='ajax_loading'>{$this->lang->words['gl_loading']}</div>";
		acp = new IPBACP;
	//]]>
	</script>
	
	<script type="text/javascript" src="{$this->settings['board_url']}/cache/lang_cache/{$this->lang->lang_id}/acp.lang.js" charset="{$IPS_DOC_CHAR_SET}"></script>
</head>
<body id='ipboard_body'>
<div id='loading-layer' style='display:none'>
	<div id='loading-layer-shadow'>
	   <div id='loading-layer-inner' >
		   <img src='{$this->settings['skin_acp_url']}/images/loading_anim.gif' style='vertical-align:middle' border='0' />
		   <span style='font-weight:bold' id='loading-layer-text'>{$this->lang->words['ajax_please_wait']}</span>
	   </div>
	</div>
</div>
<%CONTENT%>
</body>
</html>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Primary page wrapper - used for all full pages
 *
 * @access	public
 * @param	string 		Document character set
 * @param	array 		CSS Files
 * @return	string		HTML
 */
public function global_main_wrapper($IPS_DOC_CHAR_SET=IPS_DOC_CHAR_SET, $cssFiles=array() ) {

$IPBHTML = "";
//--starthtml--//

//$_encoded = base64_encode( $this->settings['query_string_safe'] );
$_url = str_replace( '&amp;'   , '&', $this->settings['query_string_safe'] );
$_url = preg_replace( '#&{1,}#', ';', $_url );
$_url = preg_replace( '#={1,}#', ':', $_url );
$_url = ltrim( $_url, ';' );

$form_hash     = $this->member->form_hash;
$_path         = IPS_PUBLIC_SCRIPT;
ipsRegistry::$request[ 'st'] =  ( ipsRegistry::$request['st']  ? ipsRegistry::$request['st'] : 0 );

/* Open Tab */
$__tabs = ( is_array( $this->member->acp_tab_data ) and count( $this->member->acp_tab_data ) )
		? "'" . implode( "','", array_keys( $this->member->acp_tab_data ) ) . "'"
		: '';

$_apptitle	= ipsRegistry::$applications[ ipsRegistry::$current_application ]['app_title'];
$_useRte	= ($this->memberData['members_editor_choice'] == 'rte' && $this->memberData['_canUseRTE']) === TRUE ? 1 : 0;

$defaultFakeApp    = '';
$defaultFakeModule = '';

$curApp         = array();
switch( ipsRegistry::$current_application )
{
	case 'forums':
		$curApp['forums'] = 'active';
		break;
	case 'core':
		$curApp['core']	= 'active';
		break;
	case 'members':
		$curApp['members'] = 'active';
		break;
	default:
		$curApp['other'] = 'active';
		break;
}

$fakeApps = $this->registry->output->fetchFakeApps();

foreach( $fakeApps as $fa => $data )
{
	foreach( $data as $appData )
	{
		if ( ! $defaultFakeApp )
		{
			$defaultFakeApp    = $appData['app'];
			$defaultFakeModule = $appData['module'];
		}
		
		if ( $appData['app'] == ipsRegistry::$current_application && $appData['module'] == ipsRegistry::$current_module )
		{
			$curApp = array();
			$curApp[ $fa ] = 'active';
			break 2;
		}
	}
}

$boardurl = ($this->registry->output->isHTTPS) ? $this->settings['board_url_https'] : $this->settings['board_url'];

$IPBHTML .= <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xml:lang="en" lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="content-type" content="text/html; charset={$IPS_DOC_CHAR_SET}" />
	<meta http-equiv="Pragma" content="no-cache" />
	<meta http-equiv="Cache-Control" content="no-cache" />
	<meta http-equiv="Expires" content="Fri, 01 January 1999 01:00:00 GMT" />
	<link rel="shortcut icon" href='{$boardurl}/favicon.ico' />

	<title><%TITLE%></title>
	<script type='text/javascript'>
		jsDebug = 1;
		USE_RTE = {$_useRte};
		inACP   = true;
	</script>
HTML;

/** CSS ----------------------------------------- */
if ( $this->settings['use_minify'] )
{
	$_basics  = CP_DIRECTORY . '/skin_cp/acp.css,' . CP_DIRECTORY . '/skin_cp/acp_content.css,' . CP_DIRECTORY . '/skin_cp/acp_editor.css';
	$_others  = '';
	
	if ( is_array( $cssFiles['import'] ) AND count( $cssFiles['import'] ) )
	{
		foreach( $cssFiles['import'] as $data )
		{
			$_others .= ',' . preg_replace( "#^(.*)/(" . CP_DIRECTORY . "/.*)$#", "$2", $data['content'] );
		}
	}
	
	$IPBHTML .= "\n\t<link rel=\"stylesheet\" type=\"text/css\" media='screen' href=\"{$this->settings['public_dir']}min/index.php?f={$_basics}{$_others}\">\n";
}
else
{
	$IPBHTML .= <<<HTML
	<style type='text/css' media='all'>
		@import url( "{$this->settings['skin_acp_url']}/acp.css" );
		@import url( "{$this->settings['skin_acp_url']}/acp_content.css" );
		@import url( "{$this->settings['skin_acp_url']}/acp_editor.css" );
	</style>
HTML;

	if( is_array($cssFiles['import']) AND count($cssFiles['import']) )
	{
		foreach( $cssFiles['import'] as $data )
		{
			$IPBHTML .= <<<EOF
			<link rel="stylesheet" type="text/css" {$data['attributes']} href="{$data['content']}" />
EOF;
		}
	}
}

$IPBHTML .= <<<HTML
	<!--[if IE]>
		<style type='text/css' media='all'>
			@import url( "{$this->settings['skin_acp_url']}/acp_ie_tweaks.css" );
		</style>
	<![endif]-->
HTML;

if( is_array($cssFiles['inline']) AND count($cssFiles['inline']) )
{
	$IPBHTML .= <<<EOF
		<style type='text/css' media="all">
EOF;

	foreach( $cssFiles['inline'] as $data )
	{
		$IPBHTML .= $data['content'];
	}

	$IPBHTML .= <<<EOF
		</style>
EOF;
}

/** JS ----------------------------------------- */
if ( $this->settings['use_minify'] )
{
	$_others = ',' . CP_DIRECTORY . '/js/acp.js,' . CP_DIRECTORY . '/js/acp.' . implode('.js,' . CP_DIRECTORY . '/js/acp.', array( 'menu', 'livesearch', 'styles', 'tabs' ) ) . '.js';
	
	$IPBHTML .= <<<HTML
	
	<script type='text/javascript' src='{$this->settings['public_dir']}min/index.php?g=js'></script>
HTML;

	$IPBHTML .= "\n\t<script type='text/javascript' src='{$this->settings['public_dir']}min/index.php?f=public/js/ipb.js" . $_others;
	
	if ( $this->settings['extraJsModules'] )
	{
		$_modules		= explode( ',', $this->settings['extraJsModules'] );
		$_loadModules	= '';
		$_seenModules	= array();
		
		foreach( $_modules as $_jsModule )
		{
			if( !$_jsModule )
			{
				continue;
			}
			
			if( in_array( $_jsModule, $_seenModules ) )
			{
				continue;
			}
			
			$_seenModules[] = $_jsModule;
			
			$_loadModules	.= ",public/js/ips." . $_jsModule . ".js";
		}
		
		$IPBHTML .= $_loadModules . "'></script>\n";
	}
	else
	{
		$IPBHTML .= "'></script>\n";
	}
}
else
{
	$IPBHTML .= <<<HTML
		<script type="text/javascript" src="{$this->settings['public_dir']}js/3rd_party/prototype.js"></script>
		<script type='text/javascript' src='{$this->settings['public_dir']}js/3rd_party/scriptaculous/scriptaculous-cache.js'></script>
		<script type="text/javascript" src='{$this->settings['public_dir']}js/ipb.js?load={$this->settings['extraJsModules']}'></script>
		<script type='text/javascript' src='{$this->settings['js_main_url']}acp.menu.js'></script>
		<script type='text/javascript' src='{$this->settings['js_main_url']}acp.js'></script>
		<script type="text/javascript" src='{$this->settings['js_main_url']}acp.livesearch.js'></script>
		<script type="text/javascript" src='{$this->settings['js_main_url']}acp.styles.js'></script>
		<script type="text/javascript" src='{$this->settings['js_main_url']}acp.tabs.js'></script>
HTML;
}

$IPBHTML .= <<<HTML
	<!--<script type='text/javascript' src='http://getfirebug.com/releases/lite/1.2/firebug-lite-compressed.js'></script>-->
	<script type='text/javascript' language='javascript'>
		Loader.boot();
	</script>
		
HTML;

if( $this->settings['acp_tutorial_mode'] )
{
	$IPBHTML .= "<script type='text/javascript' src='{$this->settings['js_main_url']}acp.help.js'></script>\n";
}

$IPBHTML .= <<<HTML
	<script type='text/javascript' language='javascript'>
	//<![CDATA[
		ipb.vars['st']	= "{$this->request['st']}";
		ipb.vars['base_url']	= "{$this->settings['_base_url']}";
		ipb.vars['front_url']	= "{$this->settings['board_url']}/index.php?";
		ipb.vars['app_url']		= "{$this->settings['base_url']}";
		ipb.vars['image_url'] 	= "{$this->settings['skin_app_url']}/images/";
		ipb.vars['md5_hash']	= "{$form_hash}";
		/* ---- cookies ----- */
		ipb.vars['cookie_id'] 			= '{$this->settings['cookie_id']}';
		ipb.vars['cookie_domain'] 		= '{$this->settings['cookie_domain']}';
		ipb.vars['cookie_path']			= '{$this->settings['cookie_path']}';
		ipb.templates['close_popup']	= "<img src='{$this->settings['img_url']}/close_popup.png' alt='x' />";
		ipb.templates['page_jump']		= new Template("<div id='#{id}_wrap' class='ipbmenu_content'><h3 class='bar'>{$this->lang->words['gl_pagejump']}</h3><input type='text' class='input_text' id='#{id}_input' size='8' /> <input type='submit' value='Go' class='input_submit add_folder' id='#{id}_submit' /></div>");
		ipb.templates['ajax_loading'] 	= "<div id='ajax_loading'>{$this->lang->words['gl_loading']}</div>";
		acp = new IPBACP;
	//]]>
	</script>
	
	<script type="text/javascript" src="{$this->settings['board_url']}/cache/lang_cache/{$this->lang->lang_id}/acp.lang.js" charset="{$IPS_DOC_CHAR_SET}"></script>
</head>
<body id='ipboard_body'>
<!-- Inline Form Box -->
<div id='inlineFormWrap' style='display: none;'>
	<div id='inlineFormInnerWrap'>
		<div id='inlineFormInnerClose' onclick="Effect.Fade( 'inlineFormWrap', { duration: .5 } );"></div>
		<div id='inlineFormInnerTitle'></div>
		<div id='inlineErrorBox'>
			<img src='{$this->settings['skin_acp_url']}/images/stopLarge.png' border='0' />
			<strong>{$this->lang->words['gl_error']}</strong>
			<div id='inlineErrorText'></div>
		</div>
		<div id='inlineFormInnerContent'></div>
		<div id='inlineFormLoading'>
			{$this->lang->words['gl_pleasewait']}...
			<br /><br />
			<img src='{$this->settings['skin_acp_url']}/_newimages/loading_big.gif' alt='loading' id='search_loading' />
		</div>
	</div>
</div>
<!-- / Inline Form Box -->
	<p id='admin_bar'>
		<span id='logged_in'>{$this->lang->words['gl_loggedinas']} <strong>{$this->memberData['members_display_name']}</strong> (<a href='{$this->settings['_base_url']}&amp;module=login&amp;do=login-out'>{$this->lang->words['gbl_log_out']}</a>)</span>
		<a href='../'>&lt; {$this->lang->words['gbl_view_site']}</a>
	</p>
	<div id='header'>
		<div id='branding'>
			<a href='{$this->settings['_base_url']}' title='{$this->lang->words['home']}'><img src='{$this->settings['skin_acp_url']}/_newimages/logo.png' alt='{$this->lang->words['gl_logo']}' /></a>
		</div>
		<h1>{$this->lang->words['gl_ipbadminarea']}</h1>
		<div id='navigation'>
			<ul id='section_buttons'>
			<!--	<li class='{$curApp['core']}'><a href='{$this->settings['_base_url']}app=core'><img src='{$this->settings['skin_acp_url']}/_newimages/applications/core.png' alt='{$this->lang->words['gl_icon']}' /> {$this->lang->words['gl_system']}</a></li>
				<li class='{$curApp['forums']}'><a href='{$this->settings['_base_url']}app=forums'><img src='{$this->settings['skin_acp_url']}/_newimages/applications/forums.png' alt='{$this->lang->words['gl_icon']}' /> {$this->lang->words['gl_forums']}</a></li>
				<li class='{$curApp['members']}'><a href='{$this->settings['_base_url']}app=members'><img src='{$this->settings['skin_acp_url']}/_newimages/applications/members.png' alt='{$this->lang->words['gl_icon']}' /> {$this->lang->words['gl_members']}</a></li>
				<li class='{$curApp['lookfeel']}'><a href='{$this->settings['_base_url']}app={$defaultFakeApp}&amp;module={$defaultFakeModule}'><img src='{$this->settings['skin_acp_url']}/_newimages/applications/palette.png' alt='{$this->lang->words['gl_icon']}' /> {$this->lang->words['gl_lookandfeel']}</a></li>
				<li class='{$curApp['support']}'><a href='{$this->settings['_base_url']}app=core&amp;module=diagnostics'><img src='{$this->settings['skin_acp_url']}/_newimages/applications/help.png' alt='{$this->lang->words['gl_icon']}' /> {$this->lang->words['gl_support']}</a></li> -->
				<li class='{$curApp['other']}'><a href='#' id='app_menu' class='ipbmenu'><img src='{$this->settings['skin_acp_url']}/_newimages/applications/brick.png' alt='{$this->lang->words['gl_icon']}' />Content</a></li>
			</ul>
			<form action='#' method='get' onsubmit='return false;'>
				{$this->lang->words['gl_livesearch']}: <input type='text' name='acpSearchKeyword' id='acpSearchKeyword' value='' />
			</form>
		</div>
		<div id='app_menu_menucontent' style='display: none'>
			<ul>
HTML;
		$IPBHTML .= $this->global_app_menu_html();
		$IPBHTML .= <<<HTML
			</ul>
		</div>
		<script type='text/javascript'>
			//var appmenu = new ipb.Menu( $('open_menu'), $('app_menu') );
		</script>
		<!--<div id='secondary_navigation'>
			<h2>{$_apptitle} Menu</h2>
		</div>-->
	</div>
	
	<div id='page_body'>
		<div id='section_navigation'>
			<%MENU%>
		</div>
		<div id='main_content'>
			<div id='content_wrap'>
HTML;

if( $this->settings['acp_tutorial_mode'] )
{
	$IPBHTML .= <<<HTML
				<a href='#' id='help_link' title='{$this->lang->words['get_help_title']}'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/help.png' alt='{$this->lang->words['icon']}' /> {$this->lang->words['get_help']}</a>
				<a href='#' id='help_nw' class='showing' style='display: none' title='{$this->lang->words['help_new_window']}'><img src='{$this->settings['skin_acp_url']}/_newimages/icons/application_double.png' alt='{$this->lang->words['help_new_window']}' /></a>
HTML;
}

$IPBHTML .= <<<HTML
				<%NAV%>
				
				<%CONTENT%>
			</div>
			<br style='clear: both' />
		</div>
		
	</div>
	<div id='copyright'>
		<a href='http://www.'>IPBoard 3</a> &copy; {$year} IPS, Inc. &nbsp;&nbsp;|&nbsp;&nbsp; <a href='http://www./customer/index.html' title='{$this->lang->words['gl_getsupport_title']}'>{$this->lang->words['gl_getsupport']}</a> &nbsp;&nbsp;|&nbsp;&nbsp; <a href='http://resources.' title='{$this->lang->words['gl_resources_title']}'>{$this->lang->words['gl_resources']}</a>
HTML;

if ( IN_DEV )
{
$count = count( $this->DB->obj['cached_queries'] );
$files = count( get_included_files() );

$IPBHTML .= <<<HTML
	&nbsp;&nbsp;|&nbsp;&nbsp; <a href='#' onclick="$('acpQueries').show()">$count Queries and $files Included Files</a>
	
HTML;
}

$IPBHTML .= <<<HTML
	</div>

</body>
</html>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * HTML to show when there is no context menu
 *
 * @access	public
 * @return	string		HTML
 */
public function no_context_menu(){

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF

EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Global page primary template - fits in content area
 *
 * @access	public
 * @return	string		HTML
 */
public function global_frame_wrapper() {

$year = date('Y');

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<%CONTEXT_MENU%>

<%HELP%>
<%MSG%>
<%SECTIONCONTENT%>

<div id='acpQueries' style='display:none'>
	<%QUERIES%>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Generate the application menu HTML
 *
 * @access	public
 * @return	string		HTML
 */
public function global_app_menu_html() {

$IPBHTML = "";
//--starthtml--//

$applications	= ipsRegistry::$applications;
$count			= 0;
$this->registry->getClass('class_permissions')->return = 1;

foreach( $applications as $app_dir => $app_data )
{
	$class = '';
	$tag = '';

	//if ( $app_data['app_directory'] == 'core' )
	if ( $app_data['app_location'] == 'root' || $this->registry->getClass('class_permissions')->checkForAppAccess( $app_data['app_directory'] ) !== TRUE || ! $applications[ $app_dir ]['app_enabled'] )
	{
		//$_extraCSS = 'display:none';
		continue;
	}

	if( $app_data['app_location'] == 'ips' )
	{
		$class = 'ips_app';
		$tag = $this->lang->words['gl_ipsapp'];
	}
	
	$img = file_exists( IPSLib::getAppDir( $app_data['app_directory'] ) . '/skin_cp/appIcon.png' ) ? $this->settings['base_acp_url'] . '/' . IPSLib::getAppFolder( $app_data['app_directory'] ) . '/' . $app_data['app_directory'] . '/skin_cp/appIcon.png' : "{$this->settings['skin_acp_url']}/_newimages/applications/{$app_dir}.png";
	
	$IPBHTML .= <<<EOF

	<li id='app_{$app_dir}' class='{$class}'>
		<a href='{$this->settings['_base_url']}app={$app_data['app_directory']}'><img src='$img' alt='{$app_dir}' />
		<strong>{$app_data['app_title']}</strong>
		<span class='tagline'>{$tag}</span>
		</a>
	</li>
EOF;
	$count++;
}

if( !$count )
{
	$IPBHTML .= <<<EOF

	<li id='app_manageapps' class='ips_app'>
		<a href='{$this->settings['_base_url']}app=core&amp;module=applications&amp;section=applications&amp;do=applications_overview'><img src='{$this->settings['skin_acp_url']}/_newimages/applications/{$app_dir}.png' alt='{$app_dir}' />
		<strong>{$this->lang->words['gl_manageapps']}</strong></a>
	</li>
EOF;
}

//--endhtml--//
return $IPBHTML;
}

/**
 * Show the information box on the page
 *
 * @access	public
 * @param	string 		Box title
 * @param	string		Box content
 * @return	string		HTML
 */
public function information_box($title="", $content="") {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$title}</h2>
</div>
<div class='section_info'>{$content}</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show a warning box
 *
 * @access	public
 * @param	string 		Title
 * @param	string		Content
 * @return	string		HTML
 */
public function warning_box($title="", $content="") {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='warning'>
 <h4>{$title}</h4>
 {$content}
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Shows the debug query output at the bottom of the page
 *
 * @access	public
 * @param	string 		Queries to show
 * @return	string		HTML
 */
public function global_query_output($queries="") {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<br /><br />
<div align='center' style='margin-left:auto;margin-right:auto'>
<div class='tableborder' style='vertical-align:bottom;text-align:left;width:75%;color:#555'>
 <div class='tableheader'><b>{$this->lang->words['gbl_queries']}</b></div>
 <div class='tablerow2' style='overflow:auto'>$queries</div>
</div>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show the login form
 *
 * @access	public
 * @param	string 		Query string to remember
 * @param	string		Message to show
 * @param	bool		Replace the form (deprecated)
 * @param	array 		Additional data to add to the form
 * @return	string		HTML
 */
public function log_in_form( $query_string="", $message="", $replace_form=false, $additional_data=array() ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<script type='text/javascript'>
if ( top != self )
{
	top.location.href = window.location.href;
}

Event.observe( window, 'load', function(e){
	$('username').focus();
});

</script>
EOF;

if( $replace_form )
{
	$IPBHTML .= $additional_data[0];
}
else
{
	$IPBHTML .= <<<EOF
<form action='{$this->settings['_base_url']}app=core&amp;module=login&amp;do=login-complete' method='post'>
<input type='hidden' name='qstring' id='qstring' value='$query_string' />
<div id='login'>
EOF;
if ( $message )
{
	$IPBHTML .= <<<EOF
		<div id='login_error'>$message</div>
EOF;
}

$IPBHTML .= <<<EOF
	<div id='login_controls'>
		<label for='username'>{$this->lang->words['gl_signinname']}</label>
		<input type='text' size='20' id='username' name='username' value=''>
		
		<label for='password'>{$this->lang->words['gl_password']}</label>
		<input type='password' size='20' id='password' name='password' value=''>
EOF;

		if( count($additional_data) > 0 )
		{
			foreach( $additional_data as $form_html )
			{
				$IPBHTML .= $form_html;
			}
		}
		
$IPBHTML .= <<<EOF
	</div>
	<div id='login_submit'>
		<input type='submit' class='button' value="{$this->lang->words['gl_signin']}" />
	</div>
</div>
</form>
EOF;

$IPBHTML .= <<<EOF
		</div>
</div>
</form>
EOF;
}

//--endhtml--//
return $IPBHTML;
}

/**
 * Redirect hit for auto-redirecting pages (e.g. "recache all caches")
 *
 * @access	public
 * @param	string 		URL to send to
 * @param	string		Text to show
 * @param	integer		Number of seconds to wait
 * @return	string		HTML
 */
public function global_redirect_hit($url, $text="", $time=1) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<script type='text/javascript'>
	jsDebug = 0;
	USE_RTE = 0;
</script>
<script type="text/javascript" src="{$this->settings['public_dir']}js/3rd_party/prototype.js"></script>
<script type='text/javascript' src='{$this->settings['public_dir']}js/3rd_party/scriptaculous/scriptaculous-cache.js'></script>
<script type="text/javascript" src='{$this->settings['public_dir']}js/ipb.js?load={$this->settings['extraJsModules']}'></script>
<script type='text/javascript' src='{$this->settings['js_main_url']}acp.js'></script>
<script type="text/javascript" src='{$this->settings['js_main_url']}acp.livesearch.js'></script>
EOF;

if( $this->settings['acp_tutorial_mode'] )
{
	$IPBHTML .= "<script type='text/javascript' src='{$this->settings['js_main_url']}acp.help.js'></script>";
}

$IPBHTML .= <<<EOF
<script type="text/javascript" src='{$this->settings['js_main_url']}acp.styles.js'></script>
<script type="text/javascript" src='{$this->settings['js_main_url']}acp.tabs.js'></script>
<script type="text/javascript">
//<![CDATA[
Loader.boot();

ipb.vars['st']	= "{$this->request['st']}";

ipb.vars['base_url']	= "{$this->settings['_base_url']}";
ipb.vars['front_url']	= "{$this->settings['board_url']}/index.php?";
ipb.vars['app_url']	= "{$this->settings['base_url']}";
ipb.vars['image_url']	= "{$this->settings['skin_app_url']}/images/";
ipb.vars['md5_hash']	= "{$form_hash}";
/* ---- cookies ----- */
ipb.vars['cookie_id'] 			= '{$this->settings['cookie_id']}';
ipb.vars['cookie_domain'] 		= '{$this->settings['cookie_domain']}';
ipb.vars['cookie_path']			= '{$this->settings['cookie_path']}';
acp = new IPBACP;
//]]>
</script>

<style type='text/css' media='all'>
	@import url( "{$this->settings['skin_acp_url']}/acp.css" );
	@import url( "{$this->settings['skin_acp_url']}/acp_content.css" );
</style>

<meta http-equiv='refresh' content='{$time}; url=$url' />

<div class='information-box'>
	<h4>{$this->lang->words['gbl_page_redirecting']}</h4>
	{$this->lang->words['page_will_refresh']} <a href='$url'>{$this->lang->words['refresh_dont_wait']}</a>
</div>
<br />
<div class='redirector'>
	<div class='info'>{$text}</div>	
</div>

EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Initialize global redirection javascript for AJAX redirecting
 *
 * @access	public
 * @param	string 		URL to redirect to
 * @param	string		Text to show
 * @param	string		Additional text to add
 * @return	string		HTML
 */
public function global_ajax_redirect_init($url='', $text='', $addtotext='') {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='redirector'>
	<div class='info' id='refreshbox'>{$this->lang->words['gbl_initializing']}</div>	
</div>
<script type='text/javascript'>
//<![CDATA[
acp.ajaxRefresh( '$url', '$text', $addtotext );
//]]>
</script>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Global redirection completed page
 *
 * @access	public
 * @param	string 		Text to show
 * @return	string		HTML
 */
public function global_redirect_done($text='This function has now finished executing') {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<style type='text/css' media='all'>
	@import url( "{$this->settings['skin_acp_url']}/acp.css" );
	@import url( "{$this->settings['skin_acp_url']}/acp_content.css" );
</style>

<div class='redirector complete'>
	<div class='info'>{$text}</div>
</div>

EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * General redirect page with message
 *
 * @access	public
 * @param	string 		URL to send to
 * @param	integer		Number of seconds to wait before redirecting
 * @param	string		Text to display
 * @return	string		HTML
 */
public function global_redirect($url, $time=2, $text="") {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<meta http-equiv='refresh' content='{$time}; url=$url' />
<div id='redirect'>
	<h2>{$this->lang->words['redirect_page_text']}</h2>
	<p>
		{$text}
	</p>
	
	<a href='$url'>{$this->lang->words['refresh_dont_wait']}</a>
</div>

EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * General redirect page with message
 *
 * @access	public
 * @param	string 		URL to send to
 * @param	integer		Number of seconds to wait before redirecting
 * @param	string		Text to display
 * @return	string		HTML
 */
public function global_redirect_halt($url) {

$IPBHTML = "";
//--starthtml--//


$IPBHTML .= <<<EOF
<div class='warning'>
 <h4>{$this->lang->words['redirect_halt_title']}</h4>
 	<p><strong>{$this->registry->output->global_error}</strong></p>
	<br />
	<ul>
		<li style='font-weight:bold'><a href='$url'>{$this->lang->words['redirect_halt_continue']}</a></a>
		<li><a href='{$this->settings['this_url']}'>{$this->lang->words['redirect_repeat_step']}</a>
	</ul>
</div>
EOF;

$this->registry->output->global_error = '';

//--endhtml--//
return $IPBHTML;
}

/**
 * Generate sub navigation menu
 *
 * @access	public
 * @param	array 		Menu data
 * @return	string		HTML
 */
public function menu_sub_navigation( $menu ) {

$main_html = array();
$IPBHTML   = "";
//--starthtml--//

if( is_array($menu[ ipsRegistry::$current_application ]) AND count($menu[ ipsRegistry::$current_application ]) )
{
foreach( $menu[ ipsRegistry::$current_application ] as $id => $data )
{
	$links = "";
	$_id   = preg_replace( '/^\d+?_(.*)$/', "\\1", $id );

	if ( $_id != ipsRegistry::$current_module )
	{
		continue;
	}

	foreach( $data['items'] as $_id => $_data )
	{
		$_url   = ( $_data['url'] ) ? "&amp;{$_data['url']}" : "";
$links .= <<<EOF
		<div class='menulinkwrapBlock'>
			<a href="{$this->settings['base_url']}module={$_data['module']}&amp;section={$_data['section']}{$_url}" style='text-decoration:none'>{$_data['title']}</a>
		</div>
EOF;
	}

	if ( $links )
	{
$main_html[] = <<<EOF
<!-- MENU FOR {$data['title']}-->
<div class='menuouterwrap'>
  <div class='menucatwrapBlock'>{$data['title']}</div>
  $links
</div>
<!-- / MENU FOR {$data['title']}-->
EOF;
	}
}
}

if ( is_array( $main_html ) AND count( $main_html ) )
{
$IPBHTML .= <<<EOF
<div id='subMenuWrap'>
EOF;
$IPBHTML .= implode( "<br />", $main_html );
$IPBHTML .= <<<EOF
</div>
EOF;
}

//--endhtml--//
return $IPBHTML;
}

/**
 * Menu category wrapper for "categories"
 *
 * @access	public
 * @param	array 		Links to show
 * @param	string		Module (cleaned)
 * @param	array 		Menu items to show
 * @return	string		HTML
 */
public function menu_cat_wrap( $links=array(), $clean_module="", $menu=array() ) {

$IPBHTML = "";
$seen    = 0;
$titles  = 0;

//--starthtml--//

	$IPBHTML .= "<ul>\n";

	foreach( $links as $app => $module )
	{
		foreach( $module as $data )
		{
			$class = '';

			if ( $app == ipsRegistry::$current_application AND $clean_module == $data['module'] )
			{
				$class = 'active';
			}

			if( isset( $menu[ $app ] ) && is_array( $menu[ $app ] ) )
			{
				foreach( $menu[ $app ] as $id => $__data )
				{
					preg_match( '/^(\d+?)_(.*)$/', $id, $result );

					if ( $result[2] != $data['module'] )
					{
						continue;
					}

					/* Heres where we check whether this is a single item */
					if( intval($result[1]) === 0 )
					{
						$_temp = '1_' . $result[2];

						if( !isset( $menu[ $app ][ $_temp ] ) )
						{
							$_url   = ( $__data['items'][0]['url'] ) ? "&amp;{$__data['items'][0]['url']}" : "";

							$_single_item = true;
							$_MENU .=  <<<EOF
								<!-- MENU FOR {$data['title']}-->
								<li class='{$class}'>
									<a href='{$this->settings['base_url']}module={$__data['items'][0]['module']}&amp;section={$__data['items'][0]['section']}{$_url}'>{$__data['items'][0]['title']}</a>
								</li>
EOF;
							continue(2);
						}
					}
					/* /end */

					if ( count( $__data['items'] ) > 1 )
					{
	$_CHILD .= <<<EOF
					<li>
						<a href='{$this->settings['base_url']}module={$__data['items'][0]['module']}&amp;section={$__data['items'][0]['section']}&amp;{$__data['items'][0]['url']}'>{$__data['title']}</a>
						<ul>

EOF;
						$seen_in_this_group = 0;
						foreach( $__data['items'] as $_id => $_data )
						{
							if( $seen_in_this_group == 0 ){ $seen_in_this_group++; continue; }
							
							$_seen++;
							$_seen_in_this_group++;
							
							$_class = '';
							$_url   = ( $_data['url'] ) ? "&amp;{$_data['url']}" : "";

							if ( $_seen == count( $__data['items'] ) )
							{
								$_class = 'last';
							}

	$_CHILD .= <<<EOF
							<li class='{$_class}'><a href="{$this->settings['base_url']}module={$_data['module']}&amp;section={$_data['section']}{$_url}">{$_data['title']}</a></li>
EOF;
						}

	$_CHILD .= <<<EOF
						</ul>
					</li>
EOF;
					####### / MORE THAN 1 CHILD ITEM	#######
					}
					else
					{
						$_url = ( $__data['items'][0]['url'] ) ? "&amp;{$__data['items'][0]['url']}" : "";
	$_CHILD .= <<<EOF
					<li>
						<a href="{$this->settings['base_url']}module={$__data['items'][0]['module']}&amp;section={$__data['items'][0]['section']}{$_url}">{$__data['title']}</a>
					</li>

EOF;
					}
				}
				
				if( $_CHILD )
				{
				$_MENU .= <<<EOF
						<!-- MENU FOR {$data['title']}-->
						<li class='{$class} has_sub'>
							{$data['title']}
							<ul>
								{$_CHILD}
							</ul>
						</li>
EOF;
				}
				$_CHILD = '';

			}
		}

		$IPBHTML .= <<<EOF

		{$_MENU}
		</ul>
EOF;

	}

//--endhtml--//
	return $IPBHTML;
}

/**
 * Navigation HTML wrapper
 *
 * @access	public
 * @param	string 		Menu content
 * @return	string		HTML
 */
public function wrap_nav($content="") {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<ol id='breadcrumb'>
	$content
</ol>
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Global informational message to display
 *
 * @access	public
 * @return	string		HTML
 */
public function global_message() {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='information-box'>
 <h4><img src='{$this->settings['skin_acp_url']}/_newimages/icons/information.png' alt='' />&nbsp; {$this->lang->words['ipb_message']}</h4>
EOF;
 	$IPBHTML .= $this->registry->getClass('output')->global_message;

$IPBHTML .= <<<EOF
</div>
<br />
EOF;

//--endhtml--//
return $IPBHTML;
}


/**
 * Global error message to display
 *
 * @access	public
 * @return	string		HTML
 */
public function global_error_message() {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='warning'>
 <h4>{$this->lang->words['ipb_message']}</h4>
 	{$this->registry->output->global_error}
</div>
<br />
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show the ACP tutorial mode help box
 *
 * @access	public
 * @param	string 		Page key to call
 * @return	string		HTML
 */
public function help_box( $key='' ) {

$IPBHTML = "";
//--starthtml--//

$domain		= parse_url( $this->settings['base_url'], PHP_URL_HOST );
$version	= '3.0.3';

$IPBHTML .= <<<EOF
<div class='help-box' id='main_help' style='display: none'>
	<div id='acp-help-contents' style='display:block;'>Loading...</div>
</div>
<!--<script type='text/javascript' src='http://acpdocs./retrieve.php?pageKey={$key}&amp;domain={$domain}&amp;version={$version}' defer='defer'></script>-->
<script type='text/javascript'>

	var acpHelp = {};
	
	acpHelp['pageKey'] = '{$key}';
	acpHelp['domain'] = '{$domain}';
	acpHelp['version'] = '{$version}';
	
	acpHelp['close_help'] = "<img src='{$this->settings['skin_acp_url']}/_newimages/icons/cross.png' alt='{$this->lang->words['icon']}' /> {$this->lang->words['close_help']}";
	acpHelp['open_help'] = "<img src='{$this->settings['skin_acp_url']}/_newimages/icons/help.png' alt='{$this->lang->words['icon']}' /> {$this->lang->words['get_help']}";
	
	acpHelp['popup_template'] = new Template("<h3>#{title}</h3><div style='padding: 10px'>#{content}</div>");
</script>
<br />
EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Pagination wrapper
 *
 * @access	public
 * @param	array 		Work data
 * @param	array 		Pagination data
 * @return	string		HTML
 */
public function paginationTemplate( $work, $data ) {
$IPBHTML = "";
//--starthtml--//

if( $work['pages'] > 1 )
{
$IPBHTML .= <<<EOF
	<ul class='pagination'>
		<li class='total'>({$work['pages']} {$this->lang->words['tpl_pages']})</li>
EOF;

	if( !$data['noDropdown'] )
	{
		$IPBHTML .= <<<EOF
		<li class='pagejump pj{$data['uniqid']}'>
			<img src='{$this->settings['skin_acp_url']}/_newimages/dropdown.png' alt='+' />
			<script type='text/javascript'>
				ipb.global.registerPageJump( '{$data['uniqid']}', { url: "{$data['baseUrl']}", stKey: '{$data['startValueKey']}', perPage: {$data['itemsPerPage']}, totalPages: {$work['pages']} } );
			</script>
		</li>
EOF;
	}

if( 1 < ($work['current_page'] - $data['dotsSkip']) )
{
$IPBHTML .= <<<EOF
	<li class='first'><a href='{$data['baseUrl']}&amp;{$data['startValueKey']}=0' title='{$this->lang->words['tpl_gotofirst']}' rel='start'>&laquo; {$this->lang->words['tpl_isfirst']}</a></li>
EOF;
}

if( $work['current_page'] > 1 )
{
	$stkey = intval( $data['currentStartValue'] - $data['itemsPerPage'] );
$IPBHTML .= <<<EOF
	<li class='prev'><a href="{$data['baseUrl']}&amp;{$data['startValueKey']}={$stkey}" title="{$this->lang->words['tpl_prev']}" rel='prev'>&larr;</a></li>
EOF;
}

if( count($work['_pageNumbers']) AND is_array($work['_pageNumbers']) )
{
	foreach( $work['_pageNumbers'] as $_real => $_page )
	{
		if( $_real == $data['currentStartValue'] )
		{
		$IPBHTML .= <<<EOF
			<li class='active'>{$_page}</li>
EOF;
		}
		else
		{
		$IPBHTML .= <<<EOF
			<li><a href="{$data['baseUrl']}&amp;{$data['startValueKey']}={$_real}" title="$_page">{$_page}</a></li>
EOF;
		}
	}
}

if( $work['current_page'] < $work['pages'] )
{
	$stkey = intval( $data['currentStartValue'] + $data['itemsPerPage'] );
$IPBHTML .= <<<EOF
	<li class='next'><a href="{$data['baseUrl']}&amp;{$data['startValueKey']}={$stkey}" title="{$this->lang->words['tpl_next']}" rel='next'>&rarr;</a></li>
EOF;
}

if( isset( $work['_showEndDots'] ) && $work['_showEndDots'] )
{
	$stkey = intval( ( $work['pages'] - 1 ) * $data['itemsPerPage'] );
$IPBHTML .= <<<EOF
	<li class='last'><a href="{$data['baseUrl']}&amp;{$data['startValueKey']}={$stkey}" title="{$this->lang->words['tpl_gotolast']}" rel='last'>{$this->lang->words['tpl_islast']} &raquo;</a></li>
EOF;
}


$IPBHTML .= <<<EOF
	</ul>
EOF;
}
else
{
	$IPBHTML .= <<<EOF
	<span class='pagination no_pages'>{$this->lang->words['page_1_of_1']}</span>
EOF;
}
//--endhtml--//
return $IPBHTML;
}


/**
 * System error page
 *
 * @access	public
 * @param	string 		Error message to show
 * @param	integer		Error code
 * @param	string		Error title
 * @param	string		Document character set
 * @return	string		HTML
 */
public function system_error( $msg, $code=0, $title='', $IPS_DOC_CHAR_SET=IPS_DOC_CHAR_SET )
{
$title = ( isset( $title ) && $title ) ? $title : $this->lang->words['gbl_system_error'];

if( $code )
{
	$finalMessage = "[#{$code}] " . ( is_array( $msg ) ? implode( "<br />", $msg ) : $msg );
}
else
{
	$finalMessage = is_array( $msg ) ? implode( "<br />", $msg ) : $msg;
}

$HTML .= <<<EOF
<div class='warning'>
 <h4>{$title}</h4>
 	<p><strong>{$finalMessage}</strong></p>
	<br />
	<ul>
		<li><a href='javascript:history.go(-1)'>{$this->lang->words['gbl_go_back']}</a>
		<li><a href='{$this->settings['_base_url']}'>{$this->lang->words['gbl_go_to_dashboard']}</a>
		<li><a href='{$this->settings['base_url']}'>{$this->lang->words['gbl_go_to_module_home']}</a>
	</ul>
</div>
EOF;

return $HTML;
}

/**
 * User interface content tabs
 *
 * @access	public
 * @param	array 		Tabs
 * @param	array 		Content blocks to show
 * @param	string		Default tab
 * @return	string		HTML
 */
public function ui_content_tabs( $tabs, $contents, $default_tab='' )
{
$HTML = <<<EOF

<!-- Tab Buttons -->
<a name='#tabpane'></a>
<table width='100%' align='center' border='0' cellspacing='0' cellpadding='0'>
	<tr>
		<td>
<script type="text/javascript">
//<![CDATA[
document.observe("dom:loaded",function() 
{
ipbAcpTabStrips.register('tabstrip');
});
 //]]>
</script>
<ul id='tabstrip' class='tab_bar no_title'>
EOF;

foreach( $tabs as $t )
{
$HTML .= <<<EOF
			<li id='tabtab-{$t['id']}' class='{$t['class']}' {$t['js']}>
				{$t['text']}
			</li>
EOF;

}

$HTML .= <<<EOF
		</td>
	</tr>
</table>
<!-- End Tab Buttons -->

<!-- Begin Tab Content Pane -->
<table width='100%' align='center' border='0' cellspacing='0' cellpadding='0'>
	<tr>
		<td>
			<div id='tab_contents'>
EOF;

foreach( $contents as $c )
{
$HTML .= <<<EOF
				<!-- Begin Tab Pane {$c['id']} -->
				<div id='tabpane-{$c['id']}' class='tabpane-system'>
					{$c['content']}
				</div>
				<!-- End Tab Pane {$c['id']} -->
EOF;
}

$HTML .= <<<EOF

			</div>
		</td>
	</tr>
</table>
<!-- End Tab Content Pane -->


EOF;

return $HTML;

}


/**
 * Generate an image tag
 *
 * @access	public
 * @param	string 		Image URL
 * @param	integer		Width
 * @param	integer		Height
 * @param	string		Alt text
 * @return	string		HTML
 */
public function image_tag( $img, $width=0, $height=0, $alt='' )
{
$size = ( $width && $height ) ? "height='$height' width='$width' " : '';
$alt  = ( $alt ) ? "title='$alt' alt='$alt" : '';
$alt  = ( $alt ) ? $alt : "title='$img' alt='$img'";
return <<<EOF
<img src='{$this->settings['board_url']}/admin/skin_cp/images/{$img}' class='ipd' $size $alt />
EOF;
}

/**
 * HTML for quick help popup boxes
 *
 * @access	public
 * @param	string 		Title
 * @param	string		Help contents
 * @return	string		HTML
 */
public function quickHelp( $title, $body ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<!--SKINNOTE: Not yet skinned-->
<div class='tableborder'>
 <div class='tableheader'>{$title}</div>
	<div class='tablerow1'>{$body}</div>
</div>
EOF;

//--endhtml--//
return $IPBHTML;
}

}