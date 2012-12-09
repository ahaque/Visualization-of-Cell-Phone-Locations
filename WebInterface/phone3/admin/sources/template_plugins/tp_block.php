<?php

/**
 * Invision Power Services
 * IP.Board vVERSION_NUMBER
 * Template Pluging: CCS block parsing
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
class tp_block extends output implements interfaceTemplatePlugins
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

		if( !$data )
		{
			return;	
		}

		$_phpCode	= '<php>if( !( $this->registry->isClassLoaded(\'pageBuilder\') ) )' . "\n{\n";
		$_phpCode	.= "\t" . 'require_once( IPSLib::getAppDir(\'ccs\') . \'/sources/pages.php\' );' . "\n";
		$_phpCode	.= "\t" . '$this->registry->setClass(\'pageBuilder\', new pageBuilder( $this->registry ) );' . "\n}</php>";
		$_phpCode	.= '" . $this->registry->getClass(\'pageBuilder\')->getBlock(\'' . $data . '\')  . "';

		//-----------------------------------------
		// Process the tag and return the data
		//-----------------------------------------

		return $_phpCode;
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
		
		return array( 'name'    => 'block',
					  'author'  => 'Invision Power Services, Inc.',
					  'usage'   => '{parse block="a_block_key"}',
					  'options' => array() );
	}
}