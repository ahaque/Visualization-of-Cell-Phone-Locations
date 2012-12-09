<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Installer: EULA file
 * Last Updated: $LastChangedDate: 2009-06-11 17:11:57 -0400 (Thu, 11 Jun 2009) $
 *
 * @author 		$Author: josh $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @link		http://www.
 * @version		$Rev: 4765 $
 *
 */


class install_address extends ipsCommand
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
		/* INIT */
		$error = false;
		
		/* Check input? */
		if ( $this->request['do'] == 'check' )
		{
			/* Check Directory */
			if ( ! ( is_dir( $this->request['install_dir'] ) ) )
			{
				$error = true;
				$this->registry->output->addWarning( 'The specified directory does not exist' );
			}
			
			/* Check URL */
			if ( ! $this->request['install_dir'] )
			{
				$error = true;
				$this->registry->output->addWarning( 'You did not specify a URL' );
			}

			if( ! $error )
			{
				/* Save Form Data */
				IPSSetUp::setSavedData('install_dir', preg_replace( "#(//)$#", "", str_replace( '\\', '/', $this->request['install_dir'] ) . '/' ) );
				IPSSetUp::setSavedData('install_url', preg_replace( "#(//)$#", "", str_replace( '\\', '/', $this->request['install_url'] ) . '/' ) );
				
				/* Next Action */
				$this->registry->autoLoadNextAction( 'db' );
			}
		}
		
		/* Guess at directory */
		
		$dir = str_replace( 'admin/install'  , '' , getcwd() );
		$dir = str_replace( 'admin\install'  , '' , $dir ); // Windows
		$dir = str_replace( '\\'       , '/', $dir );

		/* Guess at URL */
		$url = str_replace( "/admin/installer/index.php"   , "", $_SERVER['HTTP_REFERER'] );
		$url = str_replace( "/admin/installer/"            , "", $url);
		$url = str_replace( "/admin/installer"             , "", $url);
		$url = str_replace( "/admin/install/index.php"     , "", $_SERVER['HTTP_REFERER'] );
		$url = str_replace( "/admin/install/"              , "", $url);
		$url = str_replace( "/admin/install"              , "", $url);
		$url = str_replace( "/admin"              , "", $url);
		$url = str_replace( "index.php"              , "", $url);
		$url = preg_replace( "!\?(.+?)*!"            , "", $url );	
		$url = "{$url}/";
		
		/* Page Output */
		$this->registry->output->setTitle( "Paths and URLs" );
		$this->registry->output->setNextAction( "address&do=check" );
		$this->registry->output->addContent( $this->registry->output->template()->page_address( $dir, $url ) );
		$this->registry->output->sendOutput();
	}
	
}