<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Template Pluging: URL
 * Last Updated: $Date: 2009-05-15 12:27:08 -0400 (Fri, 15 May 2009) $
 *
 * @author 		$Author: josh $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @link		http://www.
 * @version		$Rev: 4659 $
 */

/**
* Main loader class
*/
class tp_url extends output implements interfaceTemplatePlugins
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
		// INIT
		//-----------------------------------------

		$return              = '';
		$base                = str_replace( "'", "\\'", $options['base'] );
		$seotitle            = isset( $options['seotitle'] ) ? str_replace( '"', '\\"', $options['seotitle'] ) : '';
		$template            = isset( $options['template'] ) ? str_replace( '"', '\\"', $options['template'] ) : '';
		$options['httpauth'] = isset( $options['httpauth'] ) ? $options['httpauth'] : '';

		$return = '$this->registry->getClass(\'output\')->formatUrl( $this->registry->getClass(\'output\')->buildUrl( "' . $data . '", \'' . $base . '\',\'' . $options['httpauth'] . '\' ), "' . $seotitle . '", "' . $template . '" )';

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
		
		return array( 'name'    => 'url',
					  'author'  => 'IPS, Inc.',
					  'usage'   => '{parse url="this=that" base="public"}',
					  'options' => array( 'base', 'seotitle', 'template', 'httpauth' ) );
	}
}