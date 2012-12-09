<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Board Rules
 * Last Updated: $Date: 2009-07-16 10:24:48 -0400 (Thu, 16 Jul 2009) $
 *
 * @author 		$Author $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Forums
 * @since		20th February 2002
 * @version		$Rev: 4900 $
 */
if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_forums_extras_boardrules extends ipsCommand
{
	/**
	 * Temporary stored output
	 *
	 * @access	public
	 * @var		string
	 */
	public $output	= "";
	
	/**
	* Class entry point
	*
	* @access	public
	* @param	object		Registry reference
	* @return	void		[Outputs to screen/redirects]
	*/
	public function doExecute( ipsRegistry $registry )
	{
		/* Get board rule (not cached) */
		$row = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'core_sys_conf_settings', 'where' => "conf_key='gl_guidelines'" ) );

		IPSText::getTextClass('bbcode')->parse_bbcode		= 1;
		IPSText::getTextClass('bbcode')->parse_html			= 1;
		IPSText::getTextClass('bbcode')->parse_nl2br		= 1;
		IPSText::getTextClass('bbcode')->parse_emoticons	= 1;
		IPSText::getTextClass('bbcode')->parsing_section	= 'global';
		
		$row['conf_value']	= IPSText::getTextClass('bbcode')->preDbParse( $row['conf_value'] );
		$row['conf_value']	= IPSText::getTextClass('bbcode')->preDisplayParse( $row['conf_value'] );

		/* Hacky fix for bug #15632 */
		//$row['conf_value'] = str_replace( '<ul><br />'          , '<ul>', $row['conf_value'] );
		//$row['conf_value'] = str_replace( '<ul'                 , '<ul class="bbc"', $row['conf_value'] );
		//$row['conf_value'] = str_replace( '</li><br />'         , '</li>', $row['conf_value'] );
		//$row['conf_value'] = str_replace( '</ul><br />'         , '</ul>', $row['conf_value'] );
		//$row['conf_value'] = preg_replace( '#<li([^\n]*)<br />#', '<li$1', $row['conf_value'] );
		
		$this->registry->output->addNavigation( $this->settings['gl_title'], '' );
		$this->registry->output->setTitle( $this->settings['gl_title'] );
		$this->registry->output->addContent( $this->registry->output->getTemplate('emails')->boardRules( $this->settings['gl_title'], $row['conf_value'] ) );
		$this->registry->output->sendOutput();
	}
}