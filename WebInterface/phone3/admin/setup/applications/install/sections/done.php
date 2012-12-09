<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Installer: EULA file
 * Last Updated: $LastChangedDate: 2009-05-21 08:29:40 -0400 (Thu, 21 May 2009) $
 *
 * @author 		$Author: matt $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @link		http://www.
 * @version		$Rev: 4681 $
 *
 */


class install_done extends ipsCommand
{	
	/**
	 * Execute selected method
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @return	void
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		$installLocked = FALSE;
		
		/* Lock the page */
		if ( @file_put_contents( DOC_IPS_ROOT_PATH . 'cache/installer_lock.php', 'Just out of interest, what did you expect to see here?' ) )
		{
			$installLocked = TRUE;
		}
		
		/* Clean conf global */
		IPSInstall::cleanConfGlobal();
		
		/* Simply return the EULA page */
		$this->registry->output->setTitle( "Complete!" );
		$this->registry->output->setHideButton( TRUE );
		$this->registry->output->addContent( $this->registry->output->template()->page_installComplete( $installLocked ) );
		$this->registry->output->sendOutput( FALSE );
	}
}