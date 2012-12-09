<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Chat skin file
 * Last Updated: $Date: 2009-02-10 18:10:57 -0500 (Tue, 10 Feb 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Chat
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 3926 $
 */
 
class cp_skin_chat extends output 
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
 * Enter parachat key
 *
 * @access	public
 * @return	string		HTML
 */
public function parachatKey() {

$IPBHTML = "";
//--starthtml--//
$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['para_help_title']}</h2>
</div>

<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=chatsave' method='post'>
<table style='background:#005' width='100%' cellpadding=4 cellspacing=0 border=0 align='center'>
	<tr>
		<td valign='middle' align='left' width='45%'>
			<b style='color:white'>Ordered IP Chat?</b>
			<input type='text' size=35 name='account_no' value='enter your Site ID here...' onclick="this.value='';">
			<input type='submit' class='realdarkbutton' value='Continue...'>
		</td>
		<td valign='middle' align='left'><a style='color:white' href='https://secure./evaluation/chat.html' target='_blank'>Free Instant Evaluation</a></td>
	</tr>
</table>
</form>

EOF;

//--endhtml--//
return $IPBHTML;
}

/**
 * Enter addonchat key
 *
 * @access	public
 * @return	string		HTML
 */
public function addonchatKey() {

$IPBHTML = "";
//--starthtml--//
$IPBHTML .= <<<EOF
<div class='section_title'>
	<h2>{$this->lang->words['addon_help_title']}</h2>
</div>

<form action='{$this->settings['base_url']}&amp;{$this->form_code}&amp;do=chatsave' method='post'>
<table style='background:#005' width='100%' cellpadding=4 cellspacing=0 border=0 align='center'>
	<tr>
		<td valign='middle' align='left'><b style='color:white'>Own AddOnChat?</b></td>
		<td valign='middle' align='left'><input type='text' size=35 name='account_no' value='enter your AddonChat Account ID here...' onclick="this.value='';"></td>
		<td valign='middle' align='left'><input type='submit' class='realdarkbutton' value='Continue...'></td>
	</tr>
</table>
</form>
EOF;

//--endhtml--//
return $IPBHTML;
}



}
