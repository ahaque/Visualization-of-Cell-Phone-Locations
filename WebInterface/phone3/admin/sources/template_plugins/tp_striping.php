<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Template plugin: Perform row striping
 * Last Updated: $Date: 2009-05-26 18:02:08 -0400 (Tue, 26 May 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @link		http://www.
 * @since		6/24/2008
 * @version		$Revision: 4690 $
 */

/**
* Main loader class
*/
class tp_striping extends output implements interfaceTemplatePlugins
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
		
		$phpCode = '';
		
		if ( ! isset( $options['classes'] ) )
		{
			return '" .  IPSLib::next( $this->registry->templateStriping["'.$data.'"] ) . "';
		}
		else
		{
			$_classes = explode( ",", trim( $options['classes'] ) );
		
			$phpCode .= "\n" . '$this->registry->templateStriping[\'' . $data . '\'] = array( FALSE, "' . implode( '","', $_classes ) . '");';
		}
		
		//-----------------------------------------
		// Process the tag and return the data
		//-----------------------------------------

		return ( $phpCode ) ? "<php>" . $phpCode . "</php>" : '';
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
		return array( 'name'    => 'striping',
					  'author'  => 'IPS, Inc.',
					  'usage'   => '{parse striping="someKey" classes="row1, row2"}',
					  'options' => array( 'classes' ) );
	}
}