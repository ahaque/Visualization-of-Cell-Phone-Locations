<?php

/**
 * Invision Power Services
 * IP.CCS block AJAX functions
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

class admin_ccs_ajax_blocks extends ipsAjaxCommand
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
		
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_blocks' );

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
			
			case 'reorderCats':
				$this->_doReorderCats();
			break;

			case 'fetchEncoding':
				$this->_fetchEncoding();
			break;
			
			case 'preview':
			default:
				$this->_showBlockPreview();
			break;
		}
	}
	
	/**
	 * Fetch an RSS feed's encoding, if possible
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function _fetchEncoding()
	{
		$url	= $this->request['url'];
		
		if( !$url )
		{
			$this->returnJsonError( $this->lang->words['ajax__no_url'] );
		}
		
		require_once( IPS_KERNEL_PATH . '/classFileManagement.php' );
		$fileManagement	= new classFileManagement();
		$xmlDocument	= $fileManagement->getFileContents( $url );
		
		if( !$xmlDocument )
		{
			$this->returnJsonError( $this->lang->words['ajax__no_doc'] );
		}
		
		if( preg_match( "#encoding=['\"](.+?)[\"']#i", $xmlDocument, $matches ) )
		{
			$this->returnJsonArray( array( 'encoding' => $matches[1] ) );
		}
		else
		{
			$this->returnJsonError( $this->lang->words['ajax__no_charset'] );
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
		
		$this->DB->build( array( 'select' => '*', 'from' => 'ccs_containers', 'where' => "container_type='block'" ) );
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
 		
 		if( is_array($this->request['block']) AND count($this->request['block']) )
 		{
 			foreach( $this->request['block'] as $block_id )
 			{
 				if( !$block_id )
 				{
 					continue;
 				}
 				
 				$this->DB->update( 'ccs_blocks', array( 'block_position' => $position, 'block_category' => $newCategory ), 'block_id=' . $block_id );
 				
 				$position++;
 			}
 		}

 		$this->returnString( 'OK' );
 		exit();
	}
	
	/**
	 * Reorder categories
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function _doReorderCats()
	{
 		//-----------------------------------------
 		// Save new position
 		//-----------------------------------------

 		$position	= 1;
 		
 		if( is_array($this->request['category']) AND count($this->request['category']) )
 		{
 			foreach( $this->request['category'] as $category_id )
 			{
 				if( !$category_id )
 				{
 					continue;
 				}
 				
 				$this->DB->update( 'ccs_containers', array( 'container_order' => $position ), 'container_id=' . $category_id );
 				
 				$position++;
 			}
 		}

 		$this->returnString( 'OK' );
 		exit();
	}
	
	/**
	 * Show the block preview
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function _showBlockPreview()
	{
		$id	= intval($this->request['id']);
		
		if( !$id )
		{
			$this->returnNull();
		}
		
		$block	= $this->DB->buildAndFetch( array( 'select' => 'block_name', 'from' => 'ccs_blocks', 'where' => 'block_id=' . $id ) );
		
		$this->returnHtml( $this->html->blockPreview( $id, $block['block_name'] ) );
	}
}
