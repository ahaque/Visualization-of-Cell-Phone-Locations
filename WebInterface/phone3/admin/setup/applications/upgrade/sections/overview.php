<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Upgrader: Index file - Shows log in page
 * Last Updated: $LastChangedDate: 2009-01-14 08:31:37 +0000 (Wed, 14 Jan 2009) $
 *
 * @author 		$Author: matt $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @link		http://www.
 * @version		$Rev: 3671 $
 *
 */

class upgrade_overview extends ipsCommand
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
		$filesOK       = NULL;
		$extensions    = get_loaded_extensions();
		$extensionsOK  = TRUE;
		$extensionData = array();
		
		/* Test Extensions */
		include( IPS_ROOT_PATH . 'setup/xml/requiredextensions.php' );
		
		if ( is_array( $INSTALLDATA ) )
		{
			foreach( $INSTALLDATA as $data )
			{
				if ( ! in_array( $data['testfor'], $extensions ) )
				{
					//-----------------------------------------
					// Added 'nohault' key which will show a
					// warning but not prohibit installation
					//-----------------------------------------
					
					if( $data['nohault'] )
					{
						$data['_ok']	= 1;		// Anything but true or false
						$extensionsOK	= 1;		// Anything but true or false
					}
					else
					{
						$extensionsOK = FALSE;
					}
				}
				else
				{
					$data['_ok'] = TRUE;
				}
				
				$extensionData[] = $data;
			}
		}
		
		/* All extensions loaded OK? */
		if ( $extensionsOK == TRUE AND $filesOK === NULL )
		{
			$filesOK = FALSE;
		
			/* Fetch core writeable files */
			require_once( IPS_KERNEL_PATH . 'classXML.php' );
			$xml    = new classXML( IPSSetUp::charSet );
		
			try
			{
				$xml->load( IPS_ROOT_PATH . 'setup/xml/writeablefiles.xml' );
			
				foreach( $xml->fetchElements( 'file' ) as $xmlelement )
				{
					$data = $xml->fetchElementsFromRecord( $xmlelement );

					if ( $data['path'] )
					{
						$_path = DOC_IPS_ROOT_PATH . $data['path'];
					
						if ( ! file_exists( $_path ) )
						{
							if ( $data['dir'] )
							{
								if ( ! @mkdir( $_path, 0777, TRUE ) )
								{
									$this->registry->output->addError( 'Directory does not exist: "' . $data['path'] . '", please create it via FTP' );
								}
							}
							else
							{
								$this->registry->output->addError( 'File does not exist: "' . $data['path'] . '", please create it via FTP' );
							}
						}
					
						if ( ! is_writeable( $_path ) )
						{
							if ( ! @chmod( $_path, 0777 ) )
							{
								if ( is_dir( $_path ) )
								{
									$this->registry->output->addError( 'Can not write to directory: "' . $data['path'] . '", please CHMOD to 777' );
								}
								else
								{
									$this->registry->output->addError( 'Can not write to file: "' . $data['path'] . '", please CHMOD to 777' );
								}
							}
						}
					}
				}
			
				if ( ! count( $this->registry->output->fetchErrors() ) )
				{
					$filesOK = TRUE;
				}
			}
			catch( Exception $error )
			{
				$filesOK = FALSE;
				$this->registry->output->addError( "Cannot locate: " . IPS_ROOT_PATH . 'setup/xml/writeablefiles.xml' );
			}
		}
		
		/* Set next action */
		$this->registry->output->setNextAction( 'apps' );
		
		/* Hide buttons? */
		if ( $filesOK !== TRUE OR $extensionsOK != TRUE )
		{
			$this->registry->output->setNextAction( '' );
			$this->registry->output->setHideButton( TRUE );
		}

		/* Simply return the requirements page */
		$this->registry->output->setTitle( "Requirements" );
		$this->registry->output->addContent( $this->registry->output->template()->page_requirements( $filesOK, $extensionsOK, $extensionData, 'upgrade' ) );
		$this->registry->output->sendOutput();
	}
}

?>