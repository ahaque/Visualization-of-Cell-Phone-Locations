<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Installer: ADMIN file
 * Last Updated: $LastChangedDate: 2009-04-22 14:33:03 -0400 (Wed, 22 Apr 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @link		http://www.
 * @version		$Rev: 4532 $
 *
 */


class install_admin extends ipsCommand
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
		$_e = 0;
		
		/* Check input? */
		if( $this->request['do'] == 'check' )
		{
			if( ! $this->request['username'] )
			{
				$_e = 1;
				$this->registry->output->addWarning( 'You must specify a display name for the admin account' );
			}
		
			if( ! $this->request['password'] )
			{
				$_e = 1;
				$this->registry->output->addWarning( 'You must specify a password for the admin account' );	
			}
			else 
			{
				if( $this->request['password'] != $this->request['confirm_password']	)
				{
					$_e = 1;
					$this->registry->output->addWarning( 'The admin passwords did not match' );	
				}
			}
			
			if( ! $this->request['email'] )
			{
				$_e = 1;
				$this->registry->output->addWarning( 'You must specify an email address for the admin account' );	
			}
			
			if ( $_e )
			{
				$this->registry->output->setTitle( "Admin: Errors" );
				$this->registry->output->setNextAction( 'admin&do=check' );
				$this->registry->output->addContent( $this->registry->output->template()->page_admin() );
				$this->registry->output->sendOutput();	
			}
			else 
			{
				/* Save Form Data */
				IPSSetUp::setSavedData('admin_user',       $this->request['username'] );
				IPSSetUp::setSavedData('admin_pass',       $this->request['password'] );
				IPSSetUp::setSavedData('admin_email',      $this->request['email'] );

				/* Next Action */
				$this->registry->autoLoadNextAction( 'install' );
				return;				
			}		
		}

		/* Output */
		$this->registry->output->setTitle( "Admin Account Creation" );
		$this->registry->output->setNextAction( 'admin&do=check' );
		$this->registry->output->addContent( $this->registry->output->template()->page_admin() );
		$this->registry->output->sendOutput();
	}
}