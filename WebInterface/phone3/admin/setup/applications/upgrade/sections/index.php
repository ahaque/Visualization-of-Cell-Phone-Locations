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

class upgrade_index extends ipsCommand
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
		if ( ! $this->request['do'] OR $this->request['do'] == 'form' )
		{
			/* Simply return the log in page */
			$this->_showForm();
		}
		else
		{
			/* Error: No input */
			if ( ! $_POST['username'] OR ! $_POST['password'] )
			{
				$this->registry->output->addWarning( "You must enter a username and password" );
				$this->_showForm();
			}

			/* Now authenticate against legacy file */
			$result = $this->registry->legacy->authenticateLogIn( $this->request['username'], $this->request['password'] );

			if ( $result !== TRUE )
			{
				/* .. then it contains an error message */
				$this->registry->output->addWarning( $result );
				$this->_showForm();
			}

			/* Still here? We're good to go. Create session and forward */
			$this->request['s'] = $this->member->sessionClass()->createSession( $this->registry->legacy->fetchMemberData(), $this->registry->legacy->fetchAuthKey() );

			$this->registry->autoLoadNextAction( 'overview' );
			return;
		}
	}

	/**
	 * Shows the log in form
	 *
	 * @access	private
	 * @return 	void
	 */
	public function _showForm()
	{
		$this->registry->output->setTitle( "Log In" );
		$this->registry->output->setNextAction( 'index&do=login' );
		$this->registry->output->addContent( $this->registry->legacy->fetchLogInForm() );
		$this->registry->output->sendOutput();
		exit();
	}
}

?>