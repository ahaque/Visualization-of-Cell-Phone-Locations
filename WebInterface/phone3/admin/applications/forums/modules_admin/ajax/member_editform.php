<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Member property updater (AJAX)
 * Last Updated: $Date: 2009-08-18 16:46:02 -0400 (Tue, 18 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Forums
 * @link		http://www.
 * @since		1st march 2002
 * @version		$Revision: 5027 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class admin_forums_ajax_member_editform extends ipsAjaxCommand 
{
	/**
	* Main class entry point
	*
	* @access	public
	* @param	object		ipsRegistry reference
	* @return	void		[Outputs to screen]
	*/
	public function doExecute( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Load skin
		//-----------------------------------------
		
		$this->html = ipsRegistry::getClass('output')->loadTemplate('cp_skin_member_form');
		
		//-----------------------------------------
		// Load lang
		//-----------------------------------------
				
		ipsRegistry::getClass('class_localization')->loadLanguageFile( array( 'admin_forums' ) );
		
    	switch( $this->request['do'] )
    	{
			case 'remove_avatar':
				$this->_removeAvatar();
			break;
			
			case 'get_avatar_images':
				$this->_getAvatarImages();
			break;
			
			case 'show':
			default:
				$this->show();
			break;
    	}
	}
	
	
	/**
	* Get avatar images in a directory
	*
	* @access	protected
	* @return	void		[Outputs to screen]
	*/
	protected function _getAvatarImages()
	{
		$dir	= IPSText::alphanumericalClean( urldecode( $this->request['cat'] ), ' ' );
		$images	= IPSMember::getFunction()->getHostedAvatarsFromCategory( $dir );
		
		IPSDebug::fireBug( 'info', array( 'Directory: ' . $dir ) );
		
		if ( $images === FALSE )
		{
			$this->returnJsonError($this->lang->words['m_nodir']);
			exit();
		}
		else
		{
			$output = $this->html->inline_avatar_images( $images );
		
			$this->returnJsonArray( array('html' => $output) );
		}
	}
		
	/**
	* Remove user's avatar
	*
	* @access	protected
	* @return	void		[Outputs to screen]
	*/
	protected function _removeAvatar()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$member_id		= intval( $this->request['member_id'] );
		
		try
		{
			IPSMember::getFunction()->removeAvatar( $member_id );
			
			$_string = <<<EOF
			{
				'success'       : true,
			}

EOF;
			$this->returnString( $_string );
		}
		catch( Exception $error )
		{
			switch ( $error->getMessage() )
			{
				case 'NO_MEMBER_ID':
					$this->registry->output->showError( $this->lang->words['t_noid'], 1130 );
				break;
				case 'NO_PERMISSION':
					$this->registry->output->showError( $this->lang->words['t_permav'], 2130, true );
				break;
			}
		}
	}
	

	/**
	* Show the form
	*
	* @access	protected
	* @return	void		[Outputs to screen]
	*/
	protected function show()
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$name      = trim( IPSText::alphanumericalClean( ipsRegistry::$request['name'] ) );
		$member_id = intval( ipsRegistry::$request['member_id'] );
		$output    = '';
		
		//-----------------------------------------
		// Get member data
		//-----------------------------------------
		
		$member = IPSMember::load( $member_id, 'extendedProfile,customFields' );
		
		//-----------------------------------------
		// Got a member?
		//-----------------------------------------
		
		if ( ! $member['member_id'] )
		{
			$this->returnJsonError( $this->lang->words['t_noid'] );
		}
		
		//-----------------------------------------
		// Return the form
		//-----------------------------------------
		
		if ( method_exists( $this->html, $name ) )
		{
			$output = $this->html->$name( $member );
		}
		else
		{
			$save_to		= '';
			$div_id			= '';
			$form_field		= '';
			$text			= '';
			$description	= '';
			$method			= '';


			switch( $name )
			{	
				/*case 'inline_warn_level':
					$method			= 'inline_form_generic';
					$save_to		= 'save_generic&amp;field=warn_level';
					$div_id			= 'warn_level';
					$form_field		= ipsRegistry::getClass('output')->formInput( "generic__field", $member['warn_level'] );
					$text			= "Member Warn Level";
					$description	= "Make adjustments to the member's overall warn level.  This does NOT add a warn log record - you should do so manually using the 'Add New Note' link if you wish to store a log of this adjustment";
				break;*/
				
				case 'inline_avatar':
					if( !$this->registry->getClass('class_permissions')->checkPermission( 'member_photo', 'members', 'members' ) )
					{
						$this->returnJsonError($this->lang->words['m_nopermban']);
					}
					
					$form				= array();
					$form['avatar_url']	= ipsRegistry::getClass('output')->formInput( "avatar_url", $member['avatar_type'] == 'url' ? $member['avatar_location'] : '' );
					
			 		$av_categories = array_merge( array( 0 => array( 0, $this->lang->words['m_selectcat'] ) ), IPSMember::getFunction()->getHostedAvatarCategories() );

					$output = $this->html->inline_avatar_selector( $member, $av_categories );
				break;
			}
			
			if ( ! $output AND $method AND method_exists( $html, $method ) )
			{
				$output = $html->$method( $member, $save_to, $div_id, $form_field, $text, $description );
			}
		}
		
		//-----------------------------------------
		// Print...
		//-----------------------------------------
		
		$this->returnHtml( $output );
	}
}