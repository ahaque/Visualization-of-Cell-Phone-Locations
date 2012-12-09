<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Help File System
 * Last Updated: $Date: 2009-04-24 08:15:53 -0400 (Fri, 24 Apr 2009) $
 *
 * @author 		$Author: matt $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @version		$Rev: 4544 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_core_help_help extends ipsCommand
{
	/**
	 * HTML to output
	 *
	 * @access	private
	 * @var		string			HTML
	 */
	public $output		= "";

	/**
	 * Class entry point
	 *
	 * @access	public
	 * @param	object		Registry reference
	 * @return	void		[Outputs to screen/redirects]
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Load Language */
		$this->registry->class_localization->loadLanguageFile( array( 'public_help' ) );

		/* What to do? */
		switch( $this->request['do'] )
		{
			case '01':
				$this->helpShowSection();
			break;

			default:
				$this->helpShowTitles();
			break;
		}
				
		/* Output */
		$this->registry->output->addContent( $this->output );
		$this->registry->output->sendOutput();
	}

	/**
	 * Show help topics
	 *
	 * @access	public
	 * @return	void
	 */
 	public function helpShowTitles()
 	{
		/* INI */
		$seen = array();
		
		
		/* Query the help topics */
		$this->DB->build( array( 'select' => 'id, title, description', 'from' => 'faq', 'order'  => 'position ASC' ) );
		$this->DB->execute();
		
		/* Loop through topics */		
		$rows = array();		

		while( $row = $this->DB->fetch() )
		{
			if( isset( $seen[ $row['title'] ] ) )
			{
				continue;
			}
			else
			{
				$seen[ $row['title'] ] = 1;
			}

			$rows[] = $row;
			
		}
		
		/* Output */
		$this->output .= $this->registry->output->getTemplate( 'help' )->helpShowTopics( 
																						$this->lang->words['page_title'], 
																						$this->lang->words['help_txt'], 
																						$this->lang->words['choose_file'], 
																						$rows 
																					);
																					
		/* Navigation */
		$this->registry->output->setTitle( $this->lang->words['page_title'] );
		$this->registry->output->addNavigation( $this->lang->words['page_title'], '' );
	}
	 
	/**
	 * Displays a help file
	 *
	 * @access	public
	 * @return	void
	 */
 	public function helpShowSection()
 	{
 		/* Check ID */
 		$id = $this->request['HID'] ? intval( $this->request['HID'] ) : 0;
 		
 		if ( ! $id )
 		{
 			$this->helpShowTitles();
 			return;
 		}
 		
 		/* Query the hel topic */
 		$topic = $this->DB->buildAndFetch( array( 'select' => 'id, title, text', 'from' => 'faq', 'where' => 'id=' . $id ) );

		if ( ! $topic['id'] )
		{
			$this->registry->output->showError( 'help_no_id', 10128 );
		}
		
		/* Load the Parser */
		IPSText::getTextClass( 'bbcode' )->bypass_badwords	= 1;
		IPSText::getTextClass( 'bbcode' )->parse_smilies	= 1;
		IPSText::getTextClass( 'bbcode' )->parse_html		= 1;
		IPSText::getTextClass( 'bbcode' )->parse_bbcode		= 1;
		IPSText::getTextClass( 'bbcode' )->parsing_section	= 'help';
		
		$topic['text']	= IPSText::getTextClass( 'bbcode' )->preDisplayParse( $topic['text'] );
		
		/* Parse out board URL */
		$topic['text'] = str_replace( '{board_url}', $this->settings['base_url'], $topic['text'] );
		
		if ( $this->request['hl'] )
		{
			$topic['text'] = IPSText::searchHighlight( $topic['text'], $this->request['hl'] );
			$topic['title'] = IPSText::searchHighlight( $topic['title'], $this->request['hl'] );
		}
		
		/* Output */
		$this->output .= $this->registry->output->getTemplate( 'help' )->helpShowSection( 
																							$this->lang->words['help_topic'], 
																							$this->lang->words['topic_text'], 
																							$topic['title'], 
																							$topic['text']
																						);
		
		/* Navigation */
		$this->registry->output->setTitle( $this->lang->words['help_topic'] );
		$this->registry->output->addNavigation( $this->lang->words['help_topics'], "app=core&amp;module=help" );
		$this->registry->output->addNavigation( $this->lang->words['help_topic'], '' );	
		
		if( $this->request['xml'] == 1 )	
		{
			require_once( IPS_KERNEL_PATH . 'classAjax.php' );
			$classAjax = new classAjax();
			$classAjax->returnHtml( $this->output );
		}
 	} 
}