<?php

/**
 * Invision Power Services
 * IP.Board vVERSION_NUMBER
 * Template Pluging: CCS menu highlighting
 * Last Updated: $Date: 2009-08-11 10:01:08 -0400 (Tue, 11 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		IP.CCS
 * @link		http://www.
 * @version		$Rev: 42 $
 */

/**
* Main loader class
*/
class tp_menu_highlight extends output implements interfaceTemplatePlugins
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
	 * Run the plug-in
	 *
	 * @access	public
	 * @author	Brandon Farber
	 * @param	string	The initial data from the tag
	 * @param	array	Array of options
	 * @return	string	Processed HTML
	 */
	public function runPlugin( $data, $options )
	{
		//-----------------------------------------
		// Process the tag and return the data
		//-----------------------------------------
		
		$return		= '';
		$onClass	= isset($options['onclass']) ? $options['onclass'] : "menu-on";
		$offClass	= isset($options['offclass']) ? $options['offclass'] : "menu-off";

		if( !$data )
		{
			return;	
		}
		
		//-----------------------------------------
		// Normalize URL - relative path
		//-----------------------------------------
		
		$data			= preg_replace( "/http:\/\/(.+?)\//i", "/", $data );
		
		$_ifCondition	= 'strpos( $_SERVER[\'REQUEST_URI\'], "' . $data . '" ) !== false';
		$_phpCode		= "\" . (( {$_ifCondition} ) ? '{$onClass}' : '{$offClass}' ) . \"";

		//-----------------------------------------
		// Process the tag and return the data
		//-----------------------------------------

		return $_phpCode ? $_phpCode : '';
	}
	
	/**
	 * Return information about this modifier
	 *
	 * It MUST contain an array  of available options in 'options'. If there are no allowed options, then use an empty array.
	 * Failure to keep this up to date will most likely break your template tag.
	 *
	 * @access	public
	 * @author	Brandon Farber
	 * @return	array
	 */
	public function getPluginInfo()
	{
		//-----------------------------------------
		// Return the data, it's that simple...
		//-----------------------------------------
		
		return array( 'name'    => 'menu_highlight',
					  'author'  => 'Invision Power Services, Inc.',
					  'usage'   => '{parse menu_highlight="http://example.com/folder/subfolder/file.html" onclass="menu-on" offclass="menu-off"}',
					  'options' => array( 'onclass', 'offclass' ) );
	}
}