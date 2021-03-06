<?php
/*--------------------------------------------------*/
/* FILE GENERATED BY INVISION POWER BOARD 3         */
/* CACHE FILE: Skin set id: 3               */
/* CACHE FILE: Generated: Sun, 06 Sep 2009 04:32:25 GMT */
/* DO NOT EDIT DIRECTLY - THE CHANGES WILL NOT BE   */
/* WRITTEN TO THE DATABASE AUTOMATICALLY            */
/*--------------------------------------------------*/

class skin_global_other_3 {

/**
* Construct
*/
function __construct( ipsRegistry $registry )
{
	/* Make object */
	$this->registry   =  $registry;
	$this->DB         =  $this->registry->DB();
	$this->settings   =& $this->registry->fetchSettings();
	$this->request    =& $this->registry->fetchRequest();
	$this->lang       =  $this->registry->getClass('class_localization');
	$this->member     =  $this->registry->member();
	$this->memberData =& $this->registry->member()->fetchMemberData();
	$this->cache      =  $this->registry->cache();
	$this->caches     =& $this->registry->cache()->fetchCaches();
}
	/* -- captchaGD --*/
function captchaGD($captcha_unique_id) {
$IPBHTML = "";
$IPBHTML .= "<input type=\"hidden\" name=\"captcha_unique_id\" value=\"{$captcha_unique_id}\" />
<fieldset class=\"GLB-table-row\">
" . (($this->request['section'] == 'register') ? ("" . (($this->cache->getCache('_hasStep3')) ? ("
		<h3 class='bar'>{$this->lang->words['reg_step4_spam']}</h3>
	") : ("
		<h3 class='bar'>{$this->lang->words['reg_step3_spam']}</h3>
	")) . "") : ("")) . "
<legend><strong>{$this->lang->words['gbl_captcha_title']}</strong></legend>
	<table class='ipbtable' cellspacing=\"0\">
		<tr>
			<td width=\"30%\"><strong>{$this->lang->words['glb_captcha_image']}</strong><br />{$this->lang->words['gbl_captcha_image_desc']}</td>
			<td width=\"70%\"><strong>{$this->lang->words['glb_captcha_enter']}</strong><br />{$this->lang->words['glb_captcha_text']}</td>
		</tr>
		<tr>
			<td style='padding-right:10px'>
				<img id='gd-antispam' class='antispam_img' src='{$this->settings['base_url']}app=core&amp;module=global&amp;section=captcha&amp;do=showimage&amp;captcha_unique_id={$captcha_unique_id}' class='ipd' />
				<br />
				<a href='#' id='gd-image-link'>{$this->lang->words['captcah_new']}</a>
			</td>
			<td>
				<input type=\"text\" style='font-size:18px' size=\"20\" maxlength=\"20\" value=\"\" name=\"captcha_string\" tabindex='0' />
			</td>
		</tr>
	</table>
</fieldset>
<script type='text/javascript'>
	ipb.global.initGD( 'gd-antispam' );
</script>";
return $IPBHTML;
}

/* -- captchaRecaptcha --*/
function captchaRecaptcha($html="") {
$IPBHTML = "";
$IPBHTML .= "<fieldset>
" . (($this->request['section'] == 'register') ? ("" . (($this->cache->getCache('_hasStep3')) ? ("
		<h3 class='bar'>{$this->lang->words['reg_step4_spam']}</h3>
	") : ("
		<h3 class='bar'>{$this->lang->words['reg_step3_spam']}</h3>
	")) . "") : ("")) . "
<legend><strong>{$this->lang->words['glb_captcha_image']}</strong></legend>
	<table class='ipbtable' cellspacing=\"0\">
		<tr>
			<td style='padding:10px'>
				{$html}
			</td>
		</tr>
	</table>
</fieldset>";
return $IPBHTML;
}

/* -- displayBoardOffline --*/
function displayBoardOffline($message="") {
$IPBHTML = "";
$IPBHTML .= "<h2>{$this->lang->words['board_offline']}</h2>
<p class='message error'><strong>{$this->lang->words['board_offline_desc']}</strong><br /><br />{$message}</p>
" . ((!$this->memberData['member_id']) ? ("
	<br />
	<p class='submit'><a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=core&amp;module=global&amp;section=login", 'public','' ), "", "" ) . "' title='{$this->lang->words['attempt_login']}'><strong>{$this->lang->words['click_login']}</strong></a></p>
") : ("")) . "";
return $IPBHTML;
}

/* -- displayPopUpWindow --*/
function displayPopUpWindow($documentHeadItems, $css, $jsLoaderItems, $title="", $output="") {
$IPBHTML = "";

$this->minify = array();
$IPBHTML .= "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\"> 
<html xml:lang=\"en\" lang=\"en\" xmlns=\"http://www.w3.org/1999/xhtml\"> 
	<head>
		<meta http-equiv=\"content-type\" content=\"text/html; charset={$this->settings['gb_char_set']}\" />
		<title>{$title}</title>
		<link rel=\"shortcut icon\" href=\"favicon.ico\" />
		" . ((is_array( $css['import'] )) ? ("" . (($this->settings['use_minify']) ? ("
							") : ("")) . "
			".$this->__f__0560d3da31e50d5a09c7122bf22a4da0($documentHeadItems,$css,$jsLoaderItems,$title,$output)."			" . (($this->settings['use_minify'] AND count($this->minify)) ? ("
				<link rel=\"stylesheet\" type=\"text/css\" media='screen' href=\"{$this->settings['public_dir']}min/index.php?f=" . str_replace( $this->settings['public_dir'], 'public/', implode( ',', $this->minify ) ) . "\" />
			") : ("")) . "") : ("")) . "
		" . ((is_array( $css['inline'] ) AND count( $css['inline'] )) ? ("
			".$this->__f__454285451d0173286868fec066c1deca($documentHeadItems,$css,$jsLoaderItems,$title,$output)."		") : ("")) . "
		<!--[if lte IE 7]>
			<link rel=\"stylesheet\" type=\"text/css\" title='Main' media=\"screen\" href=\"{$this->settings['public_dir']}style_css/{$this->registry->output->skin['_csscacheid']}/ipb_ie.css\" />
		<![endif]-->
		" . ((count($documentHeadItems)) ? ("
			".$this->__f__8d585fd6e463704b84a5e53cca0d48be($documentHeadItems,$css,$jsLoaderItems,$title,$output)."		") : ("")) . "
		
		" . $this->registry->getClass('output')->getTemplate('global')->includeRTL() . "
	</head>
	<body id='ipboard_body' style='padding: 20px'>
		<div id='ipbwrapper'>
			{$output}
		</div>
	</body>
</html>";
return $IPBHTML;
}


function __f__0560d3da31e50d5a09c7122bf22a4da0($documentHeadItems, $css, $jsLoaderItems, $title="", $output="")
{
	$_ips___x_retval = '';
	foreach( $css['import'] as $data )
	{
		
if( $this->settings['use_minify'] AND stripos( $data['attributes'], 'screen' ) )
					{
						$this->minify[] = "{$data['content']}";
					}

		$_ips___x_retval .= "
								" . ((!$this->settings['use_minify']) ? ("
					<link rel=\"stylesheet\" type=\"text/css\" {$data['attributes']} href=\"{$data['content']}\" />
				") : ("" . ((!stripos( $data['attributes'], 'screen' )) ? ("
						<link rel=\"stylesheet\" type=\"text/css\" {$data['attributes']} href=\"{$this->settings['public_dir']}min/index.php?f=" . str_replace( $this->settings['public_dir'], 'public/', $data['content'] ) . "\" />
					") : ("")) . "")) . "
			
";
	}
	$_ips___x_retval .= '';
	return $_ips___x_retval;
}

function __f__454285451d0173286868fec066c1deca($documentHeadItems, $css, $jsLoaderItems, $title="", $output="")
{
	$_ips___x_retval = '';
	foreach( $css['inline'] as $data )
	{
		
		$_ips___x_retval .= "
			<style type=\"text/css\" {$data['attributes']}>
				/* Inline CSS */
				{$data['content']}
			</style>
			
";
	}
	$_ips___x_retval .= '';
	return $_ips___x_retval;
}

function __f__e99bb2e6ca8b1006966aac0bca7322d7($documentHeadItems, $css, $jsLoaderItems, $title="", $output="",$type='',$idx='')
{
	$_ips___x_retval = '';
	foreach( $documentHeadItems[ $type ] as $idx => $data )
	{
		
		$_ips___x_retval .= "
					" . (($type == 'javascript') ? ("
						<script type=\"text/javascript\" src=\"{$data}\" charset=\"<% CHARSET %>\"></script>
					") : ("")) . "
					" . (($type == 'rss') ? ("
						<link rel=\"alternate feed\" type=\"application/rss+xml\" title=\"{$data['title']}\" href=\"{$data['url']}\" />
					") : ("")) . "
					" . (($type == 'rsd') ? ("
						<link rel=\"EditURI\" type=\"application/rsd+xml\" title=\"{$data['title']}\" href=\"{$data['url']}\" />
					") : ("")) . "
					" . (($type == 'raw') ? ("
						{$data}
					") : ("")) . "
				
";
	}
	$_ips___x_retval .= '';
	return $_ips___x_retval;
}

function __f__8d585fd6e463704b84a5e53cca0d48be($documentHeadItems, $css, $jsLoaderItems, $title="", $output="")
{
	$_ips___x_retval = '';
	foreach( $documentHeadItems as $type => $idx )
	{
		
		$_ips___x_retval .= "
				".$this->__f__e99bb2e6ca8b1006966aac0bca7322d7($documentHeadItems,$css,$jsLoaderItems,$title,$output,$type,$idx)."			
";
	}
	$_ips___x_retval .= '';
	return $_ips___x_retval;
}

/* -- Error --*/
function Error($message="",$code=0,$ad_email_one="",$ad_email_two="",$show_top_msg=0, $login="", $post="") {
$IPBHTML = "";
$IPBHTML .= "<h2>{$this->lang->words['error_title']}</h2>
" . (($show_top_msg == 1) ? ("
	<p>{$this->lang->words['exp_text']}</p>
	<br />
") : ("")) . "
<div class='message error'>
	 " . (($code) ? ("[#{$code}] ") : ("")) . "{$message}
</div>
<br />
{$login}
" . (($post) ? ("
	<div class='general_box alt'>
		{$post}
	</div>
<br />
") : ("")) . "
<div class='general_box alt'>	
	<h3>{$this->lang->words['er_useful_links']}</h3>
	<br />
	<ul class='bullets'>
		<li><a href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=core&amp;module=global&amp;section=lostpass&amp;do=10", 'public','' ), "", "" ) . "\" title='{$this->lang->words['er_lost_pass']}'>{$this->lang->words['er_lost_pass']}</a></li>
		<li><a href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=core&amp;module=global&amp;section=register&amp;do=00", 'public','' ), "", "" ) . "\" title='{$this->lang->words['er_register']}'>{$this->lang->words['er_register']}</a></li>
		<li><a href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=core&module=help", 'public','' ), "", "" ) . "\" rel=\"help\" title='{$this->lang->words['er_help_files']}'>{$this->lang->words['er_help_files']}</a></li>
	</ul>
	<br />
	<p class=\"submit\">
		<strong><a href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "", 'public','' ), "", "" ) . "\">{$this->lang->words['error_back']}</a></strong>
	</p>
</div>";
return $IPBHTML;
}

/* -- error_log_in --*/
function error_log_in($q_string="") {
$IPBHTML = "";
$IPBHTML .= "<div class='general_box alt'>
	<h3>{$this->lang->words['not_signed_in']}</h3>
	<p class='field'>" . $this->registry->getClass('output')->getReplacement("signin_icon") . " <a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=core&amp;module=global&amp;section=login", 'public','' ), "", "" ) . "' title='{$this->lang->words['submit_li']}'>{$this->lang->words['click_login']}</a>.</p>
</div>
<br />";
return $IPBHTML;
}

/* -- error_post_textarea --*/
function error_post_textarea($post="") {
$IPBHTML = "";
$IPBHTML .= "<h3>{$this->lang->words['err_title']}</h3>
<p>{$this->lang->words['err_expl']}</p>
<div class=\"fieldwrap\">
	<h4>{$this->lang->words['err_title']}</h4>
	<form name=\"mehform\">
		<textarea cols=\"70\" rows=\"5\" name=\"saved\" tabindex=\"2\">{$post}</textarea>
	</form>
	<p class=\"formbuttonrow1\"><input class=\"button\" type=\"button\" tabindex=\"1\" value=\"{$this->lang->words['err_select']}\" onclick=\"document.mehform.saved.select()\" /></p>
</div>";
return $IPBHTML;
}

/* -- questionAndAnswer --*/
function questionAndAnswer($data) {
$IPBHTML = "";
$IPBHTML .= "<input type=\"hidden\" name=\"qanda_id\" value=\"{$data['qa_id']}\" />
<fieldset class=\"GLB-table-row\">
<legend><strong>{$this->lang->words['qa_title']}</strong></legend>
	<table class='ipbtable' cellspacing=\"0\">
		<tr>
			<td width=\"30%\"><strong>{$this->lang->words['qa_question_title']}</strong></td>
			<td width=\"70%\">{$this->lang->words['qa_question_desc']}</td>
		</tr>
		<tr>
			<td style='padding-right:10px'>
				{$data['qa_question']}
			</td>
			<td>
				<input type=\"text\" style='font-size:18px' size=\"20\" maxlength=\"20\" value=\"\" name=\"qa_answer\" tabindex='0' />
			</td>
		</tr>
	</table>
</fieldset>";
return $IPBHTML;
}

/* -- redirectTemplate --*/
function redirectTemplate($documentHeadItems, $css, $jsLoaderItems, $text="",$url="", $full=false) {
$IPBHTML = "";
$IPBHTML .= "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\"> 
<html xml:lang=\"en\" lang=\"en\" xmlns=\"http://www.w3.org/1999/xhtml\"> 
	<head>
	    <meta http-equiv=\"content-type\" content=\"text/html; charset=<% CHARSET %>\" /> 
		<title>{$this->lang->words['stand_by']}</title>
		" . (($full==true) ? ("
			<meta http-equiv=\"refresh\" content=\"2; url=" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "$url", 'none','' ), "", "" ) . "\" />
		") : ("
			<meta http-equiv=\"refresh\" content=\"2; url=" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "$url", 'public','' ), "", "" ) . "\" />
		")) . "
		<link rel=\"shortcut icon\" href='" . (($this->registry->output->isHTTPS) ? ("{$this->settings['board_url_https']}") : ("{$this->settings['board_url']}")) . "/favicon.ico' />
		" . ((is_array( $css['import'] )) ? ("
			".$this->__f__6d6b7d9e030844c3452ed3caf6938492($documentHeadItems,$css,$jsLoaderItems,$text,$url,$full)."		") : ("")) . "
		" . ((is_array( $css['inline'] ) AND count( $css['inline'] )) ? ("
			".$this->__f__09d8b69bc2bdbe50fc84b3ae21fa5cf0($documentHeadItems,$css,$jsLoaderItems,$text,$url,$full)."		") : ("")) . "
		<!--[if lte IE 7]>
			<link rel=\"stylesheet\" type=\"text/css\" title='Main' media=\"screen\" href=\"{$this->settings['public_dir']}style_css/{$this->registry->output->skin['_csscacheid']}/ipb_ie.css\" />
		<![endif]-->
		" . $this->registry->getClass('output')->getTemplate('global')->includeRTL() . "
		" . ((count($documentHeadItems)) ? ("
			".$this->__f__1fb93358670b9f11cc9f3d6a68a87a81($documentHeadItems,$css,$jsLoaderItems,$text,$url,$full)."		") : ("")) . "
		<!--/CSS-->
	</head>
	<body  id='ipboard_body' class='redirector'>
		<div id='ipbwrapper'>
			<h1>{$this->settings['site_name']}</h1>
			<h2>{$this->lang->words['thanks']}</h2>
			<p class='message'>
				<strong>{$text}</strong>
				<br /><br />
				{$this->lang->words['transfer_you']}
				<br />
				<span class='desc'>(<a href=\"" . (($full==true) ? ("" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "{$url}", 'none','' ), "", "" ) . "") : ("" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "{$url}", 'public','' ), "", "" ) . "")) . "\">{$this->lang->words['dont_wait']}</a>)</span>	
			</p>
		</div>
	</body>
</html>";
return $IPBHTML;
}


function __f__6d6b7d9e030844c3452ed3caf6938492($documentHeadItems, $css, $jsLoaderItems, $text="",$url="", $full=false)
{
	$_ips___x_retval = '';
	foreach( $css['import'] as $data )
	{
		
		$_ips___x_retval .= "
				<link rel=\"stylesheet\" type=\"text/css\" {$data['attributes']} href=\"{$data['content']}\">
			
";
	}
	$_ips___x_retval .= '';
	return $_ips___x_retval;
}

function __f__09d8b69bc2bdbe50fc84b3ae21fa5cf0($documentHeadItems, $css, $jsLoaderItems, $text="",$url="", $full=false)
{
	$_ips___x_retval = '';
	foreach( $css['inline'] as $data )
	{
		
		$_ips___x_retval .= "
				<style type=\"text/css\" {$data['attributes']}>
					/* Inline CSS */
					{$data['content']}
				</style>
			
";
	}
	$_ips___x_retval .= '';
	return $_ips___x_retval;
}

function __f__0460545fbede79b32f526c8c91b1b935($documentHeadItems, $css, $jsLoaderItems, $text="",$url="", $full=false,$type='',$idx='')
{
	$_ips___x_retval = '';
	foreach( $documentHeadItems[ $type ] as $idx => $data )
	{
		
		$_ips___x_retval .= "
					" . (($type == 'javascript') ? ("
						<script type=\"text/javascript\" src=\"{$data}\" charset=\"<% CHARSET %>\"></script>
					") : ("")) . "
					" . (($type == 'rss') ? ("
						<link rel=\"alternate\" type=\"application/rss+xml\" title=\"{$data['title']}\" href=\"{$data['url']}\" />
					") : ("")) . "
					" . (($type == 'rsd') ? ("
						<link rel=\"EditURI\" type=\"application/rsd+xml\" title=\"{$data['title']}\" href=\"{$data['url']}\" />
					") : ("")) . "
					" . (($type == 'raw') ? ("
						$data
					") : ("")) . "
				
";
	}
	$_ips___x_retval .= '';
	return $_ips___x_retval;
}

function __f__1fb93358670b9f11cc9f3d6a68a87a81($documentHeadItems, $css, $jsLoaderItems, $text="",$url="", $full=false)
{
	$_ips___x_retval = '';
	foreach( $documentHeadItems as $type => $idx )
	{
		
		$_ips___x_retval .= "
				".$this->__f__0460545fbede79b32f526c8c91b1b935($documentHeadItems,$css,$jsLoaderItems,$text,$url,$full,$type,$idx)."			
";
	}
	$_ips___x_retval .= '';
	return $_ips___x_retval;
}



}

/*--------------------------------------------------*/
/* END OF FILE                                      */
/*--------------------------------------------------*/

?>