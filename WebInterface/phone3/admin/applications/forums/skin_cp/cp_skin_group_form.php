<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Group plugin skin functions
 * Last Updated: $LastChangedDate: 2009-08-27 07:27:49 -0400 (Thu, 27 Aug 2009) $
 *
 * @author 		$Author: josh $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Forums
 * @link		http://www.
 * @since		14th May 2003
 * @version		$Rev: 5049 $
 */
 
class cp_skin_group_form extends output
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
 * Show forums group form
 *
 * @access	public
 * @param	array 	Group data
 * @param	string	Tab ID
 * @return	string	HTML
 */
public function acp_group_form_main( $group, $tabId ) {

$guest_legend		= $group['g_id'] == $this->settings['guest_group'] ? $this->lang->words['g_applyguest'] : '';

$gbw_unit_type      = array(
							 0 => array( 0, $this->lang->words['g_dd_apprp'] ),
							 1 => array( 1, $this->lang->words['g_dd_days'] ) );
$dd_topic_rate 		= array( 
						0 => array( 0, $this->lang->words['g_no'] ), 
						1 => array( 1, $this->lang->words['g_yes1'] ), 
						2 => array( 2, $this->lang->words['g_yes2'] ) 
					);

$form							= array();
$form['g_other_topics']			= $this->registry->output->formYesNo( "g_other_topics", $group['g_other_topics'] );
$form['g_post_new_topics']		= $this->registry->output->formYesNo( "g_post_new_topics", $group['g_post_new_topics'] );
$form['g_topic_rate_setting']	= $this->registry->output->formDropdown( "g_topic_rate_setting", $dd_topic_rate, $group['g_topic_rate_setting'] );
$form['g_reply_own_topics']		= $this->registry->output->formYesNo( "g_reply_own_topics", $group['g_reply_own_topics'] );
$form['g_reply_other_topics']	= $this->registry->output->formYesNo( "g_reply_other_topics", $group['g_reply_other_topics'] );
$form['g_edit_posts']			= $this->registry->output->formYesNo( "g_edit_posts", $group['g_edit_posts'] );
$form['g_edit_cutoff']			= $this->registry->output->formInput( "g_edit_cutoff", $group['g_edit_cutoff'] );
$form['g_append_edit']			= $this->registry->output->formYesNo( "g_append_edit", $group['g_append_edit'] );
$form['g_delete_own_posts']		= $this->registry->output->formYesNo( "g_delete_own_posts", $group['g_delete_own_posts'] );
$form['g_open_close_posts']		= $this->registry->output->formYesNo( "g_open_close_posts", $group['g_open_close_posts'] );
$form['g_edit_topic']			= $this->registry->output->formYesNo( "g_edit_topic", $group['g_edit_topic'] );
$form['g_delete_own_topics']	= $this->registry->output->formYesNo( "g_delete_own_topics", $group['g_delete_own_topics'] );
$form['g_post_polls']			= $this->registry->output->formYesNo( "g_post_polls", $group['g_post_polls'] );
$form['g_vote_polls']			= $this->registry->output->formYesNo( "g_vote_polls", $group['g_vote_polls'] );
$form['g_avoid_flood']			= $this->registry->output->formYesNo( "g_avoid_flood", $group['g_avoid_flood'] );
$form['g_avoid_q']				= $this->registry->output->formYesNo( "g_avoid_q", $group['g_avoid_q'] );
$form['g_post_closed']			= $this->registry->output->formYesNo( "g_post_closed", $group['g_post_closed'] );
$form['g_mod_preview']			= $this->registry->output->formYesNo( "g_mod_preview", $group['g_mod_preview'] );
$form['g_mod_post_unit']		= $this->registry->output->formSimpleInput( "g_mod_post_unit", $group['g_mod_post_unit'], 3 );
$form['gbw_mod_post_unit_type']	= $this->registry->output->formDropdown( "gbw_mod_post_unit_type", $gbw_unit_type, $group['gbw_mod_post_unit_type'] );
$form['g_ppd_limit']			= $this->registry->output->formSimpleInput( "g_ppd_limit", $group['g_ppd_limit'], 3 );
$form['g_ppd_unit']				= $this->registry->output->formSimpleInput( "g_ppd_unit", $group['g_ppd_unit'], 3 );
$form['gbw_ppd_unit_type']		= $this->registry->output->formDropdown( "gbw_ppd_unit_type", $gbw_unit_type, $group['gbw_ppd_unit_type'] );

$IPBHTML = "";

$IPBHTML .= <<<EOF
<div id='tabpane-GROUPS|{$tabId}'>
	<div>
		<table class='form_table alternate_rows double_pad' cellspacing='0'>
			<tr>
				<th colspan='2' class='head'><strong>{$this->lang->words['gt_rating']}</strong></th>
			</tr>
			<tr>
		 		<td>
					<label>{$this->lang->words['g_topic_rate_setting']}</label>
				</td>
				<td>
		 			{$form['g_topic_rate_setting']}
				</td>
		 	</tr>
			<tr>
				<th colspan='2' class='head'><strong>{$this->lang->words['gt_viewing']}</strong></th>
			</tr>
			<tr>
				<td style='width: 40%'>
					<label>{$this->lang->words['g_other_topics']}</label>
				</td>
				<td style='width: 60%'>
		 			{$form['g_other_topics']}
				</td>
		 	</tr>
			<tr>
				<th colspan='2' class='head'><strong>{$this->lang->words['gt_posting']}</strong></th>
			</tr>
			<tr>
				<td>
					<label>{$this->lang->words['g_post_new_topics']}</label>
				</td>
				<td>
		 			{$form['g_post_new_topics']}
				</td>
		 	</tr>
		 	
		 	<tr>
		 		<td>
					<label>{$this->lang->words['g_reply_own_topics']}</label>
				</td>
				<td>
		 			{$form['g_reply_own_topics']}
				</td>
		 	</tr>
		 	
		 	<tr>
		 		<td>
					<label>{$this->lang->words['g_reply_other_topics']}</label>
				</td>
				<td>
		 			{$form['g_reply_other_topics']}
				</td>
		 	</tr>
		 	<tr>
				<th colspan='2' class='head'><strong>{$this->lang->words['gt_editing']}</strong></th>
			</tr>
		 	<tr>
		 		<td>
					<label>{$this->lang->words['g_edit_posts']}</label>
				</td>
				<td>
					{$form['g_edit_posts']}
				</td>
		 	</tr>
		 	
		 	<tr>
		 		<td>
					<label>{$this->lang->words['g_edit_cutoff']}</label>
					<span class='desctext'>{$this->lang->words['g_edit_cutoff_info']}</span>
				</td>
				<td>
		 			{$form['g_edit_cutoff']}
				</td>
		 	</tr>
		 	
		 	<tr>
				<td>
		 			<label>{$this->lang->words['g_append_edit']}</label>
				</td>
				<td>
		 			{$form['g_append_edit']}
				</td>
		 	</tr>
		 	
			<tr>
		 		<td>
					<label>{$this->lang->words['g_edit_topic']}</label>
				</td>
				<td>
					{$form['g_edit_topic']}
				</td>
		 	</tr>
			<tr>
				<th colspan='2' class='head'><strong>{$this->lang->words['gt_deleting']}</strong></th>
			</tr>
		 	<tr>
		 		<td>
					<label>{$this->lang->words['g_delete_own_posts']}</label>
				</td>
		 		<td>
					{$form['g_delete_own_posts']}
				</td>
		 	</tr>
		 	<tr>
		 		<td>
					<label>{$this->lang->words['g_delete_own_topics']}</label>
				</td>
				<td>
		 			{$form['g_delete_own_topics']}
				</td>
		 	</tr>
			<tr>
				<th colspan='2' class='head'><strong>{$this->lang->words['gt_openclose']}</strong></th>
			</tr>
		 	<tr>
		 		<td>
					<label>{$this->lang->words['g_open_close_posts']}</label>
				</td>
				<td>
		 			{$form['g_open_close_posts']}
				</td>
		 	</tr>
			<tr>
				<td>
					<label>{$this->lang->words['g_post_closed']}</label>
				</td>
				<td>
					{$form['g_post_closed']}
				</td>
		 	</tr>
		 	<tr>
				<th colspan='2' class='head'><strong>{$this->lang->words['gt_polling']}</strong></th>
			</tr>
		 	<tr>
		 		<td>
					<label>{$this->lang->words['g_post_polls']}</label>
				</td>
				<td>
					{$form['g_post_polls']}
				</td>
		 	</tr>
		 	
		 	<tr>
				<td>
					<label>{$this->lang->words['g_vote_polls']}</label>
				</td>
				<td>
					{$form['g_vote_polls']}
				</td>
		 	</tr>
		 	<tr>
				<th colspan='2' class='head'><strong>{$this->lang->words['gt_avoidance']}</strong></th>
			</tr>
		 	<tr>
		 		<td>
					<label>{$this->lang->words['g_avoid_flood']}</label>
				</td>
				<td>
					{$form['g_avoid_flood']}
				</td>
		 	</tr>
		 	
		 	<tr>
		 		<td>
					<label>{$this->lang->words['g_avoid_q']}</label>
				</td>
				<td>
					{$form['g_avoid_q']}
				</td>
		 	</tr>
		 	
			<tr>
				<th colspan='2' class='head'><strong>{$this->lang->words['gt_restrictions']}</strong></th>
			</tr>
		 	<tr>
		 		<td>
					<label>{$this->lang->words['g_mod_preview']}</label>
				</td>
				<td>
					<p>{$form['g_mod_preview']} &nbsp; {$this->lang->words['g_until']} {$form['g_mod_post_unit']} {$form['gbw_mod_post_unit_type']}</p>
					<p style='color:gray;font-size:0.8em'>{$this->lang->words['g_limit_dd']}</p>
				</td>
		 	</tr>
			<tr>
		 		<td>
					<label>
							{$this->lang->words['g_ppd_limit']}
						   <p style='color:gray;font-size:0.8em'>{$this->lang->words['g_limit_no']}</p>
					</label>
				</td>
				<td>
					<p>{$this->lang->words['g_max']} {$form['g_ppd_limit']} {$this->lang->words['g_ppd']} {$form['g_ppd_unit']} {$form['gbw_ppd_unit_type']}</p>
					<p style='color:gray;font-size:0.8em'>{$this->lang->words['g_limit_dd']}</p>
				</td>
		 	</tr>
		</table>
	</div>
</div>

EOF;

return $IPBHTML;
}

/**
 * Display forum group form tabs
 *
 * @access	public
 * @param	array 	Group data
 * @param	string	Tab id
 * @return	string	HTML
 */
public function acp_group_form_tabs( $group, $tabId ) {

$IPBHTML = "";

$IPBHTML .= <<<EOF
	<li id='tabtab-GROUPS|{$tabId}' class=''>{$this->lang->words['g_forperm']}</li>
EOF;

return $IPBHTML;
}

}