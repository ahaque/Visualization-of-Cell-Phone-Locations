<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Custom bbcode plugin interfaces
 *
 * @author 		$author$
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @link		http://www.
 * @version		$Rev: 3890 $ 
 **/

interface bbcodePlugin
{
	/**
	 * Method that is run before the content is stored in the database
	 * You are responsible for ensuring you mark the replaced text appropriately so that you
	 *	are able to unparse it, if you wish to have bbcode parsed on save
	 *
	 * @access	public
	 * @param	string		$txt	BBCode text from submission to be stored in database
	 * @return	string				Formatted content, ready for display
	 */
	public function preDbParse( $txt );
	
	/**
	 * Method that is run before the content is displayed to the user
	 * This is the safest method of parsing, as the original submitted text is left in tact.
	 *	No markers are necessary if you use parse on display.
	 *
	 * @access	public
	 * @param	string		$txt	BBCode/parsed text from database to be displayed
	 * @return	string				Formatted content, ready for display
	 */
	public function preDisplayParse( $txt );
	
	/**
	 * Method that is run before the content is placed into an editor for editing
	 * If you use "parse on display" you may simply return $txt
	 *
	 * @access	public
	 * @param	string		$txt	Parsed text from database to be edited
	 * @return	string				BBCode content, ready for editing
	 */
	public function preEditParse( $txt );
}