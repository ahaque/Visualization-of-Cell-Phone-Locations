<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Template Pluging: Include
 * Last Updated: $Date: 2009-06-24 23:14:22 -0400 (Wed, 24 Jun 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @link		http://www.
 * @version		$Rev: 4818 $
 */

/**
* Main loader class
*/
class tp_include extends output implements interfaceTemplatePlugins
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
		
		$return = '';
		
		//-----------------------------------------
		// Make life easier...
		//-----------------------------------------
		
		$data = str_replace( '#IPS_ROOT_PATH#'    , IPS_ROOT_PATH    , $data );
		$data = str_replace( '#IPS_DOC_ROOT_PATH#', IPS_DOC_ROOT_PATH, $data );
		$data = str_replace( '#ROOT#'             , IPS_DOC_ROOT_PATH, $data );
		
		//-----------------------------------------
		// Attempt to get data
		//-----------------------------------------
		
		$return = <<<EOF
ob_start();
include( "$data" );
\$data = ob_get_contents();
ob_end_clean();
EOF;
		
		return "<!--included content-->\n\";\n {$return} \$IPBHTML .= \$data . \"\n<!--/ included content-->";
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
		
		return array( 'name'    => 'addtohead',
					  'author'  => 'IPS, Inc.',
					  'usage'   => '{parse include="/path/to/file"}',
					  'options' => array() );
	}
}