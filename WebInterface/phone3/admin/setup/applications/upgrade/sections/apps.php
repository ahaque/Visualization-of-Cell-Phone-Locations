<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Installer: EULA file
 * Last Updated: $LastChangedDate: 2009-01-05 22:21:54 +0000 (Mon, 05 Jan 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @link		http://www.
 * @version		$Rev: 3572 $
 *
 */


class upgrade_apps extends ipsCommand
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
		/* Save data */
		if ( $this->request['do'] == 'save' )
		{
			$apps   = explode( ',', IPSSetUp::getSavedData('install_apps') );
			$toSave = array();
			$vNums  = array();
			
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
					
					$appClasses[ $app ] = IPSSetUp::fetchVersionClasses( $app, $numbers['current'][0], $numbers['latest'][0] );
					
					/* Store starting vnums */
					$vNums[ $app ] = $numbers['current'][0];
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
								
								if ( method_exists( $_tmp, 'preInstallOptionsSave' ) )
								{
									$_t = $_tmp->preInstallOptionsSave();
									
									if ( is_array( $_t ) AND count( $_t ) )
									{
										$toSave[ $app ][ $num ] = $_t;
									}
								}
							}
						}
					}
					
					/* Save it */
					if ( count( $toSave ) )
					{
						IPSSetUp::setSavedData('custom_options', $toSave );
					}
					
					if ( count( $vNums ) )
					{
						IPSSetUp::setSavedData('version_numbers', $vNums );
					}
					
				}
			}
			
			/* Next Action */
			$this->registry->autoLoadNextAction( 'upgrade' );
		}
		/* Check input? */
		else if ( $this->request['do'] == 'check' )
		{
			/* Check Directory */
			if ( ! is_array( $_POST['apps'] ) OR ! count( $_POST['apps'] ) )
			{
				$this->registry->output->addError( 'You must select to upgrade at least one application' );
			}
			else 
			{
				/* If it's lower than 3.0.0, then add in the removed apps */
				if ( IPSSetUp::is300plus() !== TRUE )
				{
					$_POST['apps']['forums']        = 1;
					$_POST['apps']['members']       = 1;
					$_POST['apps']['calendar']      = 1;
					$_POST['apps']['chat']          = 1;
					$_POST['apps']['portal']        = 1;
					//$_POST['apps']['subscriptions'] = 1;
				}
				else
				{
					if( $_POST['apps']['core'] )
					{
						$_POST['apps']['forums']        = 1;
						$_POST['apps']['members']       = 1;
					}
				}
				
				/* Save Form Data */
				IPSSetUp::setSavedData('install_apps', implode( ',', array_keys( $_POST['apps'] ) ) );
				
				/* Got any app-version classes? */
				$appClasses = array();
				$output     = array();
				$nums		= array();
				$appData    = array();
				
				foreach( $_POST['apps'] as $app => $val )
				{
					/* Grab version numbers */
					$numbers = IPSSetUp::fetchAppVersionNumbers( $app );
					
					/* Grab all numbers */
					$nums[ $app ] = IPSSetUp::fetchXmlAppVersions( $app );
					
					/* Grab app data */
					$appData[ $app ] = IPSSetUp::fetchXmlAppInformation( $app );
					
					$appClasses[ $app ] = IPSSetUp::fetchVersionClasses( $app, $numbers['current'][0], $numbers['latest'][0] );
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
								
								if ( method_exists( $_tmp, 'preInstallOptionsForm' ) )
								{
									$_t = $_tmp->preInstallOptionsForm();
									
									if ( $_t )
									{
										$output[ $app ][ $num ] = array( 'long' => $nums[ $app ][ $num ],
																		 'app'  => $appData[ $app ],
																		 'out'  => $_t );
									}
								}
							}
						}
					}
				}
			
				/* Finally... */
				if ( count( $output ) )
				{
					$this->registry->output->setTitle( "Applications" );
					$this->registry->output->setNextAction( 'apps&do=save' );
					//$this->registry->output->setHideButton( TRUE );
					$this->registry->output->addContent( $this->registry->output->template()->upgrade_appsOptions( $output ) );
					$this->registry->output->sendOutput();
				}
				else
				{
					/* Next Action */
					$this->registry->autoLoadNextAction( 'upgrade' );
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
		
		/* Now get version numbers */
		foreach( $apps as $type => $app )
		{
			foreach( $apps[ $type ] as $app => $data )
			{
				if ( $type == 'core' and ( $app == 'forums' OR $app == 'members' ) )
				{
					/* Skip forums and members and just count core for now */
					continue;
				}
				
				/* Grab version numbers */
				$numbers = IPSSetUp::fetchAppVersionNumbers( $app );
				
				$apps[ $type ][ $app ]['_vnumbers'] = $numbers;
			}
		}

		/* If it's lower than 3.0.0, then remove some apps and make them part of 'core' */
		if ( IPSSetUp::is300plus() !== TRUE )
		{
			unset( $apps['ips']['calendar'] );
			unset( $apps['ips']['chat'] );
			unset( $apps['ips']['portal'] );
			unset( $apps['other']['subscriptions'] );
		}

		/* Page Output */
		$this->registry->output->setTitle( "Applications" );
		$this->registry->output->setNextAction( 'apps&do=check' );
		//$this->registry->output->setHideButton( TRUE );
		$this->registry->output->addContent( $this->registry->output->template()->upgrade_apps( $apps ) );
		$this->registry->output->sendOutput();
	}
}