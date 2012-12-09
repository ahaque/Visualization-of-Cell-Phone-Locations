<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * ACP ranks skin file
 * Last Updated: $Date: 2009-07-06 03:32:52 -0400 (Mon, 06 Jul 2009) $
 *
 * @author 		$Author: matt $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Members
 * @link		http://www.
 * @since		20th February 2002
 * @version		$Rev: 4840 $
 *
 */
 
class cp_skin_ranks extends output
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
 * Ranks overview page
 *
 * @access	public
 * @param	array 		Rows
 * @return	string		HTML
 */
public function titlesOverview( $rows ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['rnk_titles']}</h2>
</div>

<div class='acp-box'>
	<h3>{$this->lang->words['rnk_titles']}</h3>
	<table class='alternate_rows' width='100%'>
		<tr>
			<th width='40%'>{$this->lang->words['rnk_title']}</th>
			<th width='20%'>{$this->lang->words['rnk_minposts']}</th>
			<th width='30%'>{$this->lang->words['rnk_pips']}</th>
			<th width='10%'>&nbsp;</th>
		</tr>
HTML;

if( count($rows) )
{
	foreach( $rows as $rank )
	{
		$rank['img'] = "";
		
		if( preg_match( "/[a-zA-Z]{1,}/", $rank['pips'] ) )
		{
			$rank['img'] = "<img src='" . $this->settings['public_dir'] . "style_extra/team_icons/{$rank['pips']}' border='0'>";
		}
		else
		{
			for ( $i = 1; $i <= $rank['pips']; $i++ )
			{
				$rank['img'] .= $rank['A_STAR'];
			}
		}

		$IPBHTML .= <<<HTML
		<tr>
			<td><strong>{$rank['title']}</strong></td>
			<td>{$rank['posts']}</td>
			<td>{$rank['img']}</td>
			<td align='right'>
				<img class="ipbmenu" id="menu{$rank['id']}" src="{$this->settings['skin_acp_url']}/_newimages/menu_open.png" alt="">
				<ul style="position: absolute; display: none; z-index: 9999;" class="acp-menu" id='menu{$rank['id']}_menucontent'>
					<li style="z-index: 10000;" class='icon edit'><a style="z-index: 10000;" href='{$this->settings['base_url']}{$this->form_code}&do=rank_edit&id={$rank['id']}'>{$this->lang->words['rnk_editlink']}</a></li>
					<li style="z-index: 10000;" class='icon delete'><a style="z-index: 10000;" href='#' onclick='return acp.confirmDelete( "{$this->settings['base_url']}{$this->form_code}&do=rank_delete&id={$rank['id']}" )'>{$this->lang->words['rnk_deletelink']}</a></li>
				</ul>								
			</td>
		</tr>		
HTML;
	}
}


$IPBHTML .= <<<HTML
	</table>
</div>
<br />
<form action='{$this->settings['base_url']}{$this->form_code}&amp;do=do_add_rank' method='post'>
	<div class='acp-box'>
		<h3>{$this->lang->words['rnk_addarank']}</h3>
		
		<ul class='acp-form alternate_rows'>
			<li>
				<label>{$this->lang->words['rnk_ranktitle']}</label>
				<input type='text' name='title' />
			</li>
			<li>
				<label>{$this->lang->words['rnk_minpostsneeded']}</label>
				<input type='text' name='posts' />
			</li>
			<li>
				<label>{$this->lang->words['rnk_numpips']}<span class='desctext'>{$this->lang->words['rnk_numpips_info']}</span></label>
				<input type='text' name='pips' />
			</li>
		</ul>
		
		<div class='acp-actionbar'>
			<div class='centeraction'>
				<input type='submit' value='{$this->lang->words['rnk_addrank']}' class='button primary'/>
			</div>
		</div>		
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Ranks form
 *
 * @access	public
 * @param	array 		Rank data
 * @param	string		Action code
 * @param	string		Button text
 * @return	string		HTML
 */
public function titlesForm( $rank, $action, $button ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$button}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}&amp;do={$action}' method='post'>
	<input type='hidden' name='id' value='{$rank['id']}' />
	
	<div class='acp-box'>
		<h3>{$button}</h3>
 		
		<ul class='acp-form alternate_rows'>
			<li>
				<label>{$this->lang->words['rnk_ranktitle']}</label>
				<input type='text' name='title' value='{$rank['title']}' />
			</li>
			<li>
				<label>{$this->lang->words['rnk_minpostsneeded']}</label>
				<input type='text' name='posts' value='{$rank['posts']}' />
			</li>
			<li>
				<label>{$this->lang->words['rnk_numpips']}<span class='desctext'>{$this->lang->words['rnk_numpips_info']}</span></label>
				<input type='text' name='pips' value='{$rank['pips']}' />
			</li>
		</ul>
		
		<div class='acp-actionbar'>
			<div class='centeraction'>
				<input type='submit' value='{$button}' class='button primary' />
			</div>
		</div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}


}