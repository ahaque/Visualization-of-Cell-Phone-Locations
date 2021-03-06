<?php
/*--------------------------------------------------*/
/* FILE GENERATED BY INVISION POWER BOARD 3         */
/* CACHE FILE: Skin set id: 3               */
/* CACHE FILE: Generated: Sun, 06 Sep 2009 04:32:25 GMT */
/* DO NOT EDIT DIRECTLY - THE CHANGES WILL NOT BE   */
/* WRITTEN TO THE DATABASE AUTOMATICALLY            */
/*--------------------------------------------------*/

class skin_topic_3 {

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
	/* -- announcement_show --*/
function announcement_show($announce="",$author="") {
$IPBHTML = "";
$IPBHTML .= "" . $this->registry->getClass('output')->addJSModule("topic", "0" ) . "
<h2 class='maintitle'>{$this->lang->words['announce_title']}: {$announce['announce_title']}</h2>
<div class='generic_bar'></div>
<div class='post_block first hentry announcement' id='announce_id_{$announce['announce_id']}'>
	<div class='post_wrap'>
		" . (($author['member_id']) ? ("
			<h3>
		") : ("
			<h3 class='guest'>
		")) . "
				" . (($author['member_id']) ? ("" . (($author['_online']) ? ("
						" . $this->registry->getClass('output')->getReplacement("user_online") . "
					") : ("
						" . $this->registry->getClass('output')->getReplacement("user_offline") . "
					")) . " &nbsp;
							<address class=\"author vcard\"><a class=\"url fn\" href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "showuser={$author['member_id']}", 'public','' ), "{$author['members_seo_name']}", "showuser" ) . "'>{$author['members_display_name']}</a>" . $this->registry->getClass('output')->getTemplate('global')->user_popup($author['member_id'],$author['members_seo_name']) . "</address>") : ("
					{$author['members_display_name']}
				")) . "
			</h3>
		<div class='author_info'>
			" . $this->registry->getClass('output')->getTemplate('global')->userInfoPane($author, 'announcement', array()) . "
		</div>
		<div class='post_body'>
			<div class='post entry-content'>
				{$announce['announce_post']}
			</div>
		</div>
		<ul class='post_controls'></ul>
	</div>			
</div>";
return $IPBHTML;
}

/* -- build_threaded --*/
function build_threaded($post, $child) {
$IPBHTML = "";
$IPBHTML .= "";
return $IPBHTML;
}

/* -- pollDisplay --*/
function pollDisplay($poll, $topicData, $forumData, $pollData, $showResults) {
$IPBHTML = "";
$IPBHTML .= "";
return $IPBHTML;
}

/* -- quickEditPost --*/
function quickEditPost($post) {
$IPBHTML = "";
$IPBHTML .= "";
return $IPBHTML;
}

/* -- show_attachment_title --*/
function show_attachment_title($title="",$data="",$type="") {
$IPBHTML = "";
$IPBHTML .= "<br />
<div id='attach_wrap' class='rounded clearfix'>
	<h4>$title</h4>
	<ul>
		".$this->__f__8f7f8cd72737556cff3653911e707f41($title,$data,$type)."	</ul>
</div>";
return $IPBHTML;
}


function __f__8f7f8cd72737556cff3653911e707f41($title="",$data="",$type="")
{
	$_ips___x_retval = '';
	foreach( $data as $file )
	{
		
		$_ips___x_retval .= "
			<li class='" . (($type == 'attach') ? ("clear") : ("")) . "'>
				{$file}
			</li>
		
";
	}
	$_ips___x_retval .= '';
	return $_ips___x_retval;
}

/* -- Show_attachments --*/
function Show_attachments($data="") {
$IPBHTML = "";
$IPBHTML .= "<a href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=core&amp;module=attach&amp;section=attach&amp;attach_id={$data['attach_id']}", 'public','' ), "", "" ) . "\" title=\"{$this->lang->words['attach_dl']}\"><img src=\"{$this->settings['public_dir']}{$data['mime_image']}\" alt=\"{$this->lang->words['attached_file']}\" /></a>
&nbsp;<a href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=core&amp;module=attach&amp;section=attach&amp;attach_id={$data['attach_id']}", 'public','' ), "", "" ) . "\" title=\"{$this->lang->words['attach_dl']}\">{$data['attach_file']}</a> <span class='desc'><strong>({$data['file_size']})</strong></span>
<br /><span class=\"desc info\">{$this->lang->words['attach_hits']}: {$data['attach_hits']}</span>";
return $IPBHTML;
}

/* -- Show_attachments_img --*/
function Show_attachments_img($file_name="") {
$IPBHTML = "";
$IPBHTML .= "<img src=\"{$this->settings['upload_url']}/$file_name\" class='linked-image' alt=\"{$this->lang->words['pic_attach']}\" />";
return $IPBHTML;
}

/* -- Show_attachments_img_thumb --*/
function Show_attachments_img_thumb($data=array()) {
$IPBHTML = "";
$IPBHTML .= "<a class='resized_img' rel='lightbox[{$data['attach_rel_id']}]' id='ipb-attach-url-{$data['_attach_id']}' href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "app=core&amp;module=attach&amp;section=attach&amp;attach_rel_module={$data['type']}&amp;attach_id={$data['attach_id']}", 'public','' ), "", "" ) . "\" title=\"{$data['location']} - {$this->lang->words['attach_size']} {$data['file_size']}, {$this->lang->words['attach_ahits']} {$data['attach_hits']}\"><img src=\"{$this->settings['upload_url']}/{$data['t_location']}\" id='ipb-attach-img-{$data['_attach_id']}' style='width:{$data['t_width']};height:{$data['t_height']}' class='attach' width=\"{$data['t_width']}\" height=\"{$data['t_height']}\" alt=\"{$this->lang->words['pic_attach']}\" /></a>
<br />";
return $IPBHTML;
}

/* -- topicViewTemplate --*/
function topicViewTemplate($forum, $topic, $post_data, $displayData) {
$IPBHTML = "";
$IPBHTML .= "" . (($displayData['threaded_mode_enabled'] == 0) ? ("<div class='topic_controls'>
		{$topic['SHOW_PAGES']}
		<ul class='topic_buttons'>
			" . (($forum['_user_can_post']) ? ("
				<li><a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "module=post&amp;section=post&amp;do=new_post&amp;f={$forum['id']}", 'publicWithApp','' ), "", "" ) . "' title='{$this->lang->words['start_new_topic']}' accesskey='n'>" . $this->registry->getClass('output')->getReplacement("topic_icon") . " {$this->lang->words['start_new_topic']}</a></li>
			") : ("
				<li class='disabled'><span>{$this->lang->words['top_cannot_start']}</span></li>
			")) . "
			" . (($topic['state'] == 'closed') ? ("<li class='closed'>
					" . (($displayData['reply_button']['url']) ? ("
						<a href='{$displayData['reply_button']['url']}' accesskey='r'>" . $this->registry->getClass('output')->getReplacement("lock_icon") . " {$this->lang->words['top_locked_reply']}</a>
					") : ("
						<span>" . $this->registry->getClass('output')->getReplacement("lock_icon") . " {$this->lang->words['top_locked']}</span>
					")) . "
				</li>") : ("" . (($displayData['reply_button']['image']) ? ("" . (($displayData['reply_button']['url']) ? ("
						<li><a href='{$displayData['reply_button']['url']}' title='{$this->lang->words['topic_add_reply']}' accesskey='r'>" . $this->registry->getClass('output')->getReplacement("reply_icon") . " {$this->lang->words['topic_add_reply']}</a></li>
					") : ("
						<li class='disabled'><span>{$this->lang->words['top_cannot_reply']}</span></li>
					")) . "") : ("")) . "")) . "
		</ul>	
	</div>") : ("")) . "
<div class='topic hfeed'>
	<h2 class='maintitle'>
		<span class='main_topic_title'>
			{$topic['title']}
			" . (($topic['description']) ? ("
				<span class='desc main_topic_desc'>{$topic['description']}</span>
			") : ("")) . "
		</span>
	</h2>
	
	{$displayData['poll_data']}
	
	" . (($displayData['mod_links']) ? ("" . (($this->memberData['is_mod']) ? ("
			<form id=\"modform\" method=\"post\" action=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "", 'public','' ), "", "" ) . "\">
				<fieldset>
	 				<input type=\"hidden\" name=\"app\" value=\"forums\" />
		 			<input type=\"hidden\" name=\"module\" value=\"moderate\" />
		 			<input type=\"hidden\" name=\"section\" value=\"moderate\" />
		 			<input type=\"hidden\" name=\"do\" value=\"postchoice\" />
		 			<input type=\"hidden\" name=\"f\" value=\"{$topic['forum_id']}\" />
		 			<input type=\"hidden\" name=\"t\" value=\"{$topic['tid']}\" />
		 			<input type=\"hidden\" name=\"auth_key\" value=\"{$this->member->form_hash}\" />
		 			<input type=\"hidden\" name=\"st\" value=\"{$this->request['st']}\" />
		 			<input type=\"hidden\" value=\"{$this->request['selectedpids']}\" name=\"selectedpidsJS\" id='selectedpidsJS' />
				</fieldset>
		") : ("")) . "") : ("")) . "
	
" . ((is_array( $post_data ) AND count( $post_data )) ? ("
<!-- skinnote: Posts by ignored users are not hidden, check _ignored -->
	".$this->__f__e03bb308f709e4f86dccc60a5e08dcec($forum,$topic,$post_data,$displayData)."") : ("")) . "

<ul class='topic_jump right clear'>
	<li class='previous'><a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "showtopic={$topic['tid']}&amp;view=old", 'public','' ), "{$topic['title_seo']}", "showtopic" ) . "'>&larr; {$this->lang->words['previous_topic']}</a></li>
	<li><strong><a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "showforum={$forum['id']}", 'public','' ), "{$forum['name_seo']}", "showforum" ) . "' title='Return to {$forum['name']}'>{$forum['name']}</a></strong></li>
	<li class='next'><a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "showtopic={$topic['tid']}&amp;view=new", 'public','' ), "{$topic['title_seo']}", "showtopic" ) . "'>{$this->lang->words['next_topic']} &rarr;</a></li>
</ul>

<!-- BOTTOM BUTTONS -->
<div class='topic_controls clear'>
	{$topic['SHOW_PAGES']}
	
	<ul class='topic_buttons'>
		" . (($forum['_user_can_post']) ? ("
			<li><a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "module=post&amp;section=post&amp;do=new_post&amp;f={$forum['id']}", 'publicWithApp','' ), "", "" ) . "' title='{$this->lang->words['start_new_topic']}'>" . $this->registry->getClass('output')->getReplacement("topic_icon") . " {$this->lang->words['start_new_topic']}</a></li>
		") : ("
			<li class='disabled'><span>{$this->lang->words['top_cannot_start']}</span></li>
		")) . "
		" . (($topic['state'] == 'closed') ? ("<li class='closed'>
				" . (($displayData['reply_button']['url']) ? ("
					<a href='{$displayData['reply_button']['url']}' accesskey='r'>" . $this->registry->getClass('output')->getReplacement("lock_icon") . " {$this->lang->words['top_locked_reply']}</a>
				") : ("
					<span>" . $this->registry->getClass('output')->getReplacement("lock_icon") . " {$this->lang->words['top_locked']}</span>
				")) . "
			</li>") : ("" . (($displayData['reply_button']['image']) ? ("" . (($displayData['reply_button']['url']) ? ("
					<li><a href='{$displayData['reply_button']['url']}' title='Add a reply' accesskey='r'>" . $this->registry->getClass('output')->getReplacement("reply_icon") . " {$this->lang->words['topic_add_reply']}</a></li>
				") : ("
					<li class='disabled'><span>{$this->lang->words['top_cannot_reply']}</span></li>
				")) . "") : ("")) . "")) . "
	</ul>
</div>
<hr />
<!-- Close topic -->
</div>";
return $IPBHTML;
}


function __f__e03bb308f709e4f86dccc60a5e08dcec($forum, $topic, $post_data, $displayData)
{
	$_ips___x_retval = '';
	foreach( $post_data as $pid => $post )
	{
		
		$_ips___x_retval .= "
		<!--Begin Msg Number {$post['post']['pid']}-->
		<div class='post_block hentry clear " . (($this->settings['reputation_highlight'] AND $post['post']['rep_points'] >= $this->settings['reputation_highlight']) ? ("rep_highlight") : ("")) . " " . (($post['post']['queued']==1) ? ("moderated") : ("")) . " " . (($this->settings['reputation_enabled']) ? ("with_rep") : ("")) . "' id='post_id_{$post['post']['pid']}'>
			<a id='entry{$post['post']['pid']}'></a>
			" . (($post['post']['_ignored'] == 1) ? ("
				<div class='post_ignore'>
					This post is hidden because you have chosen to ignore posts by <a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "showuser={$post['author']['member_id']}", 'public','' ), "", "" ) . "'>{$post['author']['members_display_name']}</a> 
				</div>
			") : ("<div class='post_wrap'>
					" . (($post['author']['member_id']) ? ("
						<h3>
					") : ("
						<h3 class='guest'>
					")) . "
							" . (($post['author']['member_id']) ? ("
									<address class=\"author vcard\"><a class=\"url fn\" href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "showuser={$post['author']['member_id']}", 'public','' ), "{$post['author']['members_seo_name']}", "showuser" ) . "'>{$post['author']['members_display_name']}</a>" . $this->registry->getClass('output')->getTemplate('global')->user_popup($post['author']['member_id'], $post['author']['members_seo_name']) . "</address>
							") : ("
								{$post['author']['members_display_name']}
							")) . "
						</h3>
					<p class='posted_info'>
						Posted <abbr class=\"published\" title=\"" . date( 'c', $post['post']['post_date'] ) . "\">" . $this->registry->getClass('class_localization')->getDate($post['post']['post_date'],"long", 0) . "</abbr>
					</p>
					<div class='post_body'>
						<div class='post entry-content'>
							{$post['post']['post']}
							{$post['post']['attachmentHtml']}
							<br />
							" . (($post['post']['edit_by']) ? ("<p class='edit'>
									{$post['post']['edit_by']}
									" . (($post['post']['post_edit_reason'] != '') ? ("
										<br />
										<span class='reason'>{$this->lang->words['reason_for_edit']}: {$post['post']['post_edit_reason']}</span>
									") : ("")) . "
								</p>") : ("")) . "
						</div>
						" . (($post['post']['signature']) ? ("
							{$post['post']['signature']}
						") : ("")) . "
					</div>
					<ul class='post_controls'>
						<li class='top hide'><a href='#top' class='top' title='{$this->lang->words['back_top']}'>{$this->lang->words['back_top']}</a></li>
						<li><a href=\"" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "module=post&amp;section=post&amp;do=reply_post&amp;f={$this->request['f']}&amp;t={$this->request['t']}&amp;qpid={$post['post']['pid']}", 'publicWithApp','' ), "", "" ) . "\" title=\"{$this->lang->words['tt_reply_to_post']}\">{$this->lang->words['post_reply']}</a></li>
						" . (($post['post']['_can_edit'] === TRUE) ? ("
							<li class='post_edit'><a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "module=post&amp;section=post&amp;do=edit_post&amp;f={$forum['id']}&amp;t={$topic['tid']}&amp;p={$post['post']['pid']}&amp;st={$this->request['st']}", 'publicWithApp','' ), "", "" ) . "' title='{$this->lang->words['post_edit_title']}' class='edit_post' id='edit_post_{$post['post']['pid']}'>{$this->lang->words['post_edit']}</a></li>
						") : ("")) . "
					</ul>
				</div>")) . "			
		</div>
		
		<hr />
		" . (($post['post']['_end_first_post']) ? ("<!-- END OF FIRST POST IN LINEAR+, SHOW BUTTONS AND NEW TITLE -->
			<br />
			<div class='topic_controls'>
				{$topic['SHOW_PAGES']}
				<ul class='topic_buttons'>
					" . (($forum['_user_can_post']) ? ("
						<li><a href='" . $this->registry->getClass('output')->formatUrl( $this->registry->getClass('output')->buildUrl( "module=post&amp;section=post&amp;do=new_post&amp;f={$forum['id']}", 'publicWithApp','' ), "", "" ) . "' title='{$this->lang->words['start_new_topic']}' accesskey='n'>" . $this->registry->getClass('output')->getReplacement("topic_icon") . " {$this->lang->words['start_new_topic']}</a></li>
					") : ("
						<li class='disabled'><span>{$this->lang->words['top_cannot_start']}</span></li>
					")) . "
					" . (($topic['state'] == 'closed') ? ("<li class='closed'>
							" . (($displayData['reply_button']['url']) ? ("
								<a href='{$displayData['reply_button']['url']}' accesskey='r'>" . $this->registry->getClass('output')->getReplacement("lock_icon") . " {$this->lang->words['top_locked_reply']}</a>
							") : ("
								<span>" . $this->registry->getClass('output')->getReplacement("lock_icon") . " {$this->lang->words['top_locked']}</span>
							")) . "
						</li>") : ("" . (($displayData['reply_button']['image']) ? ("" . (($displayData['reply_button']['url']) ? ("
								<li><a href='{$displayData['reply_button']['url']}' title='{$this->lang->words['topic_add_reply']}' accesskey='r'>" . $this->registry->getClass('output')->getReplacement("reply_icon") . " {$this->lang->words['topic_add_reply']}</a></li>
							") : ("
								<li class='disabled'><span>{$this->lang->words['top_cannot_reply']}</span></li>
							")) . "") : ("")) . "")) . "
				</ul>
			</div>
			<h2 class='maintitle'>{$this->lang->words['topic_other_replies']}</h2>
			<div class='generic_bar'></div>") : ("")) . "
	
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