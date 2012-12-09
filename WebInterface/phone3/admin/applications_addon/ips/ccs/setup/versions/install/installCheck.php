<?php

/**
 * Invision Power Services
 * IP.CCS installation checker
 * Last Updated: $Date: 2009-08-11 10:01:08 -0400 (Tue, 11 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		IP.CCS
 * @link		http://www.
 * @since		11th May 2009
 * @version		$Revision: 42 $
 */
 
class ccs_installCheck
{
	/**
	 * Check for any problems and report errors if any exist
	 *
	 * @access	public
	 * @return	array
	 */
	public function checkForProblems()
	{
		$info  = array( 'notexist' => array(), 'notwrite' => array(), 'other' => array() );
		
		if( !file_exists( DOC_IPS_ROOT_PATH . 'media_path.php' ) )
		{
			if( file_exists( DOC_IPS_ROOT_PATH . '_media_path.php' ) )
			{
				if( !@rename( DOC_IPS_ROOT_PATH . 'media_path.dist.php', DOC_IPS_ROOT_PATH . 'media_path.php' ) )
				{
					$info['other'][]	= "You must rename 'media_path.dist.php' to 'media_path.php'.  The file will be found in the 'root' of your IP.Board installation (the same folder where initdata.php is located).";
				}
			}
			else
			{
				$info['other'][]	= "You must upload 'media_path.dist.php' and rename it to 'media_path.php'.  The file should be uploaded to the 'root' of your IP.Board installation (the same folder where initdata.php is located).";
			}
		}
		
		return $info;
	}
}