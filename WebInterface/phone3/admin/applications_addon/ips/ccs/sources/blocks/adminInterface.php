<?php
/**
 * Admin block plugin interface
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		IP.CCS
 * @link		http://www.
 * @version		$Rev: 42 $ 
 * @since		1st March 2009
 **/

interface adminBlockHelperInterface
{
	/**
	 * Wizard launcher.  Should determine the next step necessary and act appropriately.
	 *
	 * @access	public
	 * @param	array 				Session data
	 * @return	string				HTML to output to screen
	 */
	public function returnNextStep( $session );
	
	/**
	 * Get configuration data.  Allows for automatic block types.
	 *
	 * @access	public
	 * @return	array 			Array( key, text )
	 */
	public function getBlockConfig();
	
	/**
	 * Get editor for block template
	 *
	 * @access	public
	 * @param	array 		Block data
	 * @param	array 		Block config
	 * @return	string		Editor HTML
	 */
	public function getTemplateEditor( $block, $config );
	
	/**
	 * Saves the edits to the template
	 *
	 * @access	public
	 * @param	array 		Block data
	 * @param	array 		Block config
	 * @return	bool		Saved
	 */
	public function saveTemplateEdits( $block, $config );
	
	/**
	 * Return the block content to display.  Checks cache and updates cache if needed.
	 *
	 * @access	public
	 * @param	array 	Block data
	 * @return	string 	Content to output
	 */
	public function getBlockContent( $block );
	
	/**
	 * Recache this block to the database based on content type and cache settings.
	 *
	 * @access	public
	 * @param	array 				Block data
	 * @param	bool				Return data instead of saving to database
	 * @return	bool				Cache done successfully
	 */
	public function recacheBlock( $block, $return=false );
	
	/**
	 * Store data to initiate a wizard session based on given block table data
	 *
	 * @access	public
	 * @param	array 				Block data
	 * @return	array 				Data to store for wizard session
	 */
	public function createWizardSession( $block );
}