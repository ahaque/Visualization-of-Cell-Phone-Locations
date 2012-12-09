<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Template Pluging: Format Number
 * Last Updated: $Date: 2009-02-04 15:03:36 -0500 (Wed, 04 Feb 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @link		http://www.
 * @version		$Rev: 3887 $
 */

/**
* Main loader class
*/
class tp_format_number extends output implements interfaceTemplatePlugins
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
	 * @param	array	Array of options [N/A]
	 * @return	string	Processed HTML
	 */
	public function runPlugin( $data, $options )
	{
		//-----------------------------------------
		// Process the tag and return the data
		//-----------------------------------------
		
		$return = '$this->registry->getClass(\'class_localization\')->formatNumber( ' . $data . ' )';

		return '" . ' . $return . ' . "';
	}
	
	/**
	 * Return information about this modifier
	 *
	 * It MUST contain an array  of available options in 'options'. If there are no allowed options, then use an empty array.
	 * Failure to keep this up to date will most likely break your template tag.
	 *
	 * @access	public
	 * @author	Matt Mecham
	 * @return   array
	 */
	public function getPluginInfo()
	{
		//-----------------------------------------
		// Return the data, it's that simple...
		//-----------------------------------------
		
		return array( 'name'    => 'format_number',
					  'author'  => 'IPS, Inc.',
					  'usage'   => '{parse format_number="123456"}',
					  'options' => array() );
	}
}