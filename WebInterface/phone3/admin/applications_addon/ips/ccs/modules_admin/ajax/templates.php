<?php

/**
 * Invision Power Services
 * IP.CCS template AJAX functions
 * Last Updated: $Date: 2009-08-11 10:01:08 -0400 (Tue, 11 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		IP.CCS
 * @link		http://www.
 * @since		1st March 2009
 * @version		$Revision: 42 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly.";
	exit();
}

class admin_ccs_ajax_templates extends ipsAjaxCommand
{
	/**
	 * HTML library
	 *
	 * @access	public
	 * @var		object
	 */
	public $html;

	/**
	 * Main class entry point
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// Load HTML
		//-----------------------------------------
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_templates' );

		//-----------------------------------------
		// Load Language
		//-----------------------------------------
		
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_lang' ) );

		//-----------------------------------------
		// What to do?
		//-----------------------------------------
		
		switch( $this->request['do'] )
		{
			case 'reorder':
				$this->_doReorder();
			break;
			
			case 'showtags':
			case 'taghelp':
			default:
				$this->_showTags();
			break;
		}
	}
	
	/**
	 * Reorder blocks within a container
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function _doReorder()
	{
		//-----------------------------------------
		// Get valid categories
		//-----------------------------------------
		
		$categories	= array();
		
		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_containers', 'where' => "container_type='template'" ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$categories[]	= $r['container_id'];
		}
		
 		//-----------------------------------------
 		// Save new position
 		//-----------------------------------------

 		$position		= 1;
 		$newCategory	= in_array( intval($this->request['category']), $categories ) ? intval($this->request['category']) : 0;
 		
 		if( is_array($this->request['template']) AND count($this->request['template']) )
 		{
 			foreach( $this->request['template'] as $template_id )
 			{
 				if( !$template_id )
 				{
 					continue;
 				}
 				
 				$this->DB->update( 'ccs_page_templates', array( 'template_position' => $position, 'template_category' => $newCategory ), 'template_id=' . $template_id );
 				
 				$position++;
 			}
 		}

 		$this->returnString( 'OK' );
 		exit();
	}
	
	/**
	 * Show the template tags
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function _showTags()
	{
		//-----------------------------------------
		// Get current block
		//-----------------------------------------
		
		$blocks	= array();

		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_blocks', 'order' => 'block_position ASC' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$blocks[ intval($r['block_category']) ][]	= $r;
		}
		
		//-----------------------------------------
		// Get block categories
		//-----------------------------------------
		
		$categories	= array();

		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_containers', 'where' => "container_type='block'", 'order' => 'container_order ASC' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$categories[]	= $r;
		}

		//-----------------------------------------
		// Output
		//-----------------------------------------
		
		$this->returnHtml( $this->request['inline'] ? $this->html->inlineTemplateTags( $categories, $blocks ) : $this->html->listTemplateTags( $categories, $blocks ) );
	}
}
