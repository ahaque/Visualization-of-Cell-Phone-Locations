<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Template Pluging: Date
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
class tp_date extends output implements interfaceTemplatePlugins
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
	 * @author	Matt Mecham
	 * @param	string	The initial data from the tag
	 * @param	array	Array of options
	 * @return	string	Processed HTML
	 */
	public function runPlugin( $data, $options )
	{
		//-----------------------------------------
		// Process the tag and return the data
		//-----------------------------------------
		
		$return = '';
		$_format      = 'LONG';
		$_relative    = 1;
		$timestamp    = $data ? $data : "'{custom:now}'";

		# Fix up string style dates
		/*if ( ! preg_match( "#^[0-9]{10}$#", $timestamp ) AND ( substr( $timestamp, 0, 1 ) != '$' ) )
		{
			$_time = strtotime( $timestamp );

			if ( $_time === FALSE OR $_time == -1 )
			{
				$timestamp = 0;
			}
			else
			{
				$timestamp = $_time;
			}
		}*/

		$_relative = ( isset( $options['relative'] ) && $options['relative'] == 'false' ) ? 1 : 0;

		$return = '$this->registry->getClass(\'class_localization\')->getDate(' . $timestamp . ',"' .  $options['format'] . '", ' . $_relative . ')';
		
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
	 * @return	array
	 */
	public function getPluginInfo()
	{
		//-----------------------------------------
		// Return the data, it's that simple...
		//-----------------------------------------
		
		return array( 'name'    => 'date',
					  'author'  => 'IPS, Inc.',
					  'usage'   => '{parse date="now" format="long" relative="false"}',
					  'options' => array( 'format', 'relative' ) );
	}
}