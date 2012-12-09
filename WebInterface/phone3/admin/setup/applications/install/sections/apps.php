<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Installer: EULA file
 * Last Updated: $LastChangedDate: 2009-05-11 19:24:23 -0400 (Mon, 11 May 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @link		http://www.
 * @version		$Rev: 4629 $
 *
 */


class install_apps extends ipsCommand
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
		/* Check input? */
		if( $this->request['do'] == 'check' )
		{
			/* Check Directory */
			if ( ! is_array( $_POST['apps'] ) OR ! count( $_POST['apps'] ) )
			{
				$this->registry->output->addError( 'You must select to install at least one application' );
			}
			else 
			{
				/* Save Form Data */
				IPSSetUp::setSavedData('install_apps', implode( ',', array_keys( $_POST['apps'] ) ) );
				
				/* Check writeable files */
				foreach( array_keys( $_POST['apps'] ) as $appDir )
				{
					$info = IPSSetUp::fetchXmlAppWriteableFiles( $appDir );
					
					if ( count( $info['notexist'] ) )
					{
						foreach( $info['notexist'] as $path )
						{
							$this->registry->output->addWarning( 'File or directory does not exist: "' . $path . '", please create it via FTP' );
						}
					}
					
					if ( count( $info['notwrite'] ) )
					{
						foreach( $info['notwrite'] as $path )
						{
							$this->registry->output->addWarning( 'File or directory is not writeable: "' . $path . '", please CHMOD via FTP' );
						}
					}
					
					/**
					 * Custom errors
					 */
					if ( count( $info['other'] ) )
					{
						foreach( $info['other'] as $error )
						{
							$this->registry->output->addWarning( $error );
						}
					}
				}
				
				/* Do we have errors? */
				if ( ! count( $this->registry->output->fetchWarnings() ) )
				{
					/* Next Action */
					$this->registry->autoLoadNextAction( 'address' );
				}
			}
		}
						
		/* Generate apps... */
		$apps   = array( 'core' => array(), 'ips' => array(), 'other' => array() );
		
		foreach( array( 'applications', 'applications_addon/ips', 'applications_addon/other' ) as $_pBit )
		{
			$path   = IPS_ROOT_PATH . $_pBit;
			$handle = opendir( $path );
		
			while ( ( $file = readdir( $handle ) ) !== FALSE )
			{
				if ( ! preg_match( "#^\.#", $file ) )
				{
					if ( is_dir( $path . '/' . $file ) )
					{
						//-----------------------------------------
						// Get it!
						//-----------------------------------------
					
						if ( ! file_exists( IPS_ROOT_PATH . $_pBit . '/' . $file . '/xml/information.xml' ) )
						{
							continue;		
						}
						
						$data = IPSSetUp::fetchXmlAppInformation( $file );
						
						switch( $_pBit )
						{
							case 'applications':
								$apps['core'][ $file ] = $data;
							break;
							case 'applications_addon/ips':
								$apps['ips'][ $file ] = $data;
							break;
							case 'applications_addon/other':
								$apps['other'][ $file ] = $data;
							break;
						}
					}
				}
			}
		
			closedir( $handle );
		}
		
		/* Reorder the array so that core is first */
		$new_array = array();
		$new_array['core'] = $apps['core']['core'];
		
		foreach( $apps['core'] as $app => $data )
		{
			if( $app == 'core' )
			{
				continue;
			}
			
			$new_array[$app] = $data;
		}
		
		$apps['core'] = $new_array;
		
		/* Page Output */
		$this->registry->output->setTitle( "Applications" );
		$this->registry->output->setNextAction( 'apps&do=check' );
		$this->registry->output->addContent( $this->registry->output->template()->page_apps( $apps ) );
		$this->registry->output->sendOutput();
	}
}