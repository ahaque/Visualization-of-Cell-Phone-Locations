<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Installer: DONE file
 * Last Updated: $LastChangedDate: 2009-02-04 20:03:59 +0000 (Wed, 04 Feb 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @link		http://www.
 * @version		$Rev: 3887 $
 *
 */


class upgrade_done extends ipsCommand
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
		/* Remove the FURL cache */
		@unlink( IPS_CACHE_PATH . 'cache/furlCache.php' );
		
		/* Got anything to show? */
		$apps    = explode( ',', IPSSetUp::getSavedData('install_apps') );
		$vNums   = IPSSetUp::getSavedData('version_numbers');
		$output = array();
		
		if ( is_array( $apps ) and count( $apps ) )
		{
			foreach( $apps as $app )
			{
				/* Grab version numbers */
				$numbers = IPSSetUp::fetchAppVersionNumbers( $app );
				
				/* Grab all numbers */
				$nums[ $app ] = IPSSetUp::fetchXmlAppVersions( $app );
				
				/* Grab app data */
				$appData[ $app ] = IPSSetUp::fetchXmlAppInformation( $app );
				
				$appClasses[ $app ] = IPSSetUp::fetchVersionClasses( $app, $vNums[ $app ], $numbers['latest'][0] );
			}
			
			/* Got anything? */
			if ( count( $appClasses ) )
			{
				foreach( $appClasses as $app => $data )
				{
					foreach( $data as $num )
					{
						if ( file_exists( IPSLib::getAppDir( $app ) . '/setup/versions/upg_' . $num . '/version_class.php' ) )
						{
							$_class = 'version_class_' . $num;
							require_once( IPSLib::getAppDir( $app ) . '/setup/versions/upg_' . $num . '/version_class.php' );
							
							$_tmp = new $_class( $this->registry );
							
							if ( method_exists( $_tmp, 'postInstallNotices' ) )
							{
								$_t = $_tmp->postInstallNotices();
								
								if ( is_array( $_t ) AND count( $_t ) )
								{
									$output[ $app ][ $num ] = array( 'long' => $nums[ $app ][ $num ],
																	 'app'  => $appData[ $app ],
																	 'out'  => implode( "<br />", $_t ) );
								}
							}
						}
					}
				}
			}
		}
		
		/* Remove any SQL source files */
		IPSSetUp::removeSqlSourceFiles();
		
		/* Simply return the Done page */
		$this->registry->output->setTitle( "Complete!" );
		$this->registry->output->setHideButton( TRUE );
		$this->registry->output->addContent( $this->registry->output->template()->upgrade_complete( $output ) );
		$this->registry->output->sendOutput();
	}
}