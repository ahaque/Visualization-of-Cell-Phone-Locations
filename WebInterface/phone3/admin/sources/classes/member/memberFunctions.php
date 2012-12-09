<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Extended Member Functions. Disparate functions that are not required
 * on every page view.
 * Last Updated: $Date: 2009-08-25 16:41:08 -0400 (Tue, 25 Aug 2009) $
 *
 * @author 		$Author: bfarber $ (Original: MattMecham)
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @since		Tuesday 1st March 2005 (11:52)
 * @version		$Revision: 5045 $
 *
 */


class memberFunctions
{
	/**#@+
	 * Registry objects
	 *
	 * @access	protected
	 * @var		object
	 */
	public $registry;
	public $DB;
	public $settings;
	public $request;
	public $lang;
	public $member;
	public $memberData	= array( 'member_id' => 0 );
	/**#@-*/
	
	/**
	 * Image class
	 *
	 * @access	public
	 * @var		object
	 */
	public $classImage;
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Main Registry  Object
	 * @return	void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make objects */
		$this->registry = $registry;
		$this->DB       = $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->lang     = $this->registry->getClass('class_localization');
		$this->member   = $this->registry->member();

		if( is_object($this->registry->member()) )
		{
			$this->memberData =& $this->registry->member()->fetchMemberData();
		}
		
		$this->cache    = $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();
	}
	
	/**
	 * Updates member's DB row name or members_display_name
	 *
	 * @todo 	[Future] Separate out forum specific stuff (moderators, etc) and move into hooks 
	 * @access	public
	 * @param	string		Member id
	 * @param	string		New name
	 * @param	string		Field to update (name or display name)
	 * @return	mixed		True if update successful, otherwise exception or false
	 * Error Codes:
	 * NO_USER				Could not load the user
	 * NO_PERMISSION		This user cannot change their display name at all
	 * NO_MORE_CHANGES		The user cannot change their display name again in this time period
	 * NO_NAME				No display name (or shorter than 3 chars was given)
	 * ILLEGAL_CHARS		The display name contains illegal characters
	 * USER_NAME_EXISTS		The username already exists
	 */
	public function updateName( $member_id, $name, $field='members_display_name' )
	{
		//-----------------------------------------
		// Load the member
		//-----------------------------------------
		
		$member   = IPSMember::load( $member_id );
		$_seoName = IPSText::makeSeoTitle( $name );
		
		if ( ! $member['member_id'] )
		{
			throw new Exception( "NO_USER" );
		}
		
		//-----------------------------------------
		// Make sure name does not exist
		//-----------------------------------------
		
		try
		{
			if ( $this->checkNameExists( $name, $member, $field ) === TRUE )
			{
				throw new Exception( "USER_NAME_EXISTS" );
			}
			else
			{
				if ( $field == 'members_display_name' )
				{
		    		$this->DB->force_data_type = array( 'dname_previous'	=> 'string',
			    										'dname_current'		=> 'string' );

			    	$this->DB->insert( 'dnames_change', array( 'dname_member_id'		=> $member_id,
			    												  'dname_date'		=> time(),
			    												  'dname_ip_address'	=> $member['ip_address'],
			    												  'dname_previous'	=> $member['members_display_name'],
			    												  'dname_current'		=> $name ) );

					//-----------------------------------------
					// Still here? Change it then
					//-----------------------------------------

					IPSMember::save( $member['member_id'], array( 'core' => array( 'members_display_name' => $name, 'members_l_display_name' => strtolower( $name ), 'members_seo_name' => $_seoName ) ) );

					$this->DB->force_data_type = array( 'last_poster_name' => 'string', 'seo_last_name' => 'string' );
					$this->DB->update( 'forums', array( 'last_poster_name' => $name, 'seo_last_name' => $_seoName ), "last_poster_id=" . $member['member_id'] );

					$this->DB->force_data_type = array( 'member_name' => 'string', 'seo_name' => 'string' );
					$this->DB->update( 'sessions', array( 'member_name' => $name, 'seo_name' => $_seoName ), "member_id=" . $member['member_id'] );

					$this->DB->force_data_type = array( 'starter_name' => 'string', 'seo_first_name' => 'string' );
					$this->DB->update( 'topics', array( 'starter_name' => $name, 'seo_first_name' => $_seoName ), "starter_id=" . $member['member_id'] );

					$this->DB->force_data_type = array( 'last_poster_name' => 'string', 'seo_last_name' => 'string' );
					$this->DB->update( 'topics', array( 'last_poster_name' => $name, 'seo_last_name' => $_seoName ), "last_poster_id=" . $member['member_id'] );
				}
				else
				{
					//-----------------------------------------
					// If one gets here, one can assume that the new name is correct for one, er...one.
					// So, lets do the converteroo
					//-----------------------------------------

					IPSMember::save( $member['member_id'], array( 'core' => array( 'name' => $name, 'members_l_username' => strtolower( $name ), 'members_seo_name' => $_seoName ) ) );

					$this->DB->force_data_type = array( 'member_name' => 'string' );
					$this->DB->update( 'moderators', array( 'member_name' => $name ), "member_id=" . $member['member_id'] );

					if ( ! $this->settings['auth_allow_dnames'] )
					{
						//-----------------------------------------
						// Not using sep. display names?
						//-----------------------------------------

						IPSMember::save( $member['member_id'], array( 'core' => array( 'members_display_name' => $name, 'members_l_display_name' => strtolower( $name ), 'members_seo_name' => $_seoName ) ) );

						$this->DB->force_data_type = array( 'last_poster_name' => 'string', 'seo_last_name' => 'string' );
						$this->DB->update( 'forums', array( 'last_poster_name' => $name, 'seo_last_name' => $_seoName ), "last_poster_id=" . $member['member_id'] );

						$this->DB->force_data_type = array( 'member_name' => 'string', 'seo_name' => 'string' );
						$this->DB->update( 'sessions', array( 'member_name' => $name, 'seo_name' => $_seoName ), "member_id=" . $member['member_id'] );

						$this->DB->force_data_type = array( 'starter_name' => 'string', 'seo_first_name' => 'string' );
						$this->DB->update( 'topics', array( 'starter_name' => $name, 'seo_first_name' => $_seoName ), "starter_id=" . $member['member_id'] );

						$this->DB->force_data_type = array( 'last_poster_name' => 'string', 'seo_last_name' => 'string' );
						$this->DB->update( 'topics', array( 'last_poster_name' => $name, 'seo_last_name' => $_seoName ), "last_poster_id=" . $member['member_id'] );
					}
				}

				//-----------------------------------------
				// Recache moderators
				//-----------------------------------------

				$this->registry->cache()->rebuildCache( 'moderators', 'forums' );

				//-----------------------------------------
				// Recache announcements
				//-----------------------------------------

				$this->registry->cache()->rebuildCache( 'announcements', 'forums' );

				//-----------------------------------------
				// Stats to Update?
				//-----------------------------------------

				$this->registry->cache()->rebuildCache( 'stats', 'core' );
				
				IPSLib::runMemberSync( 'onNameChange', $member['member_id'], $name );

				return TRUE;
			}
		}
		catch( Exception $error )
		{
			throw new Exception( $error->getMessage() );
		}
	}
	
	/**
	 * Cleans a username or display name, also checks for any errors
	 *
	 * @access	public
	 * @param	string  $name			Username or display name to clean and check
	 * @param	array	$member			[ Optional Member Array ]
	 * @param	string  $field			name or members_display_name
	 * @return	array   Returns an array with 2 keys: 'username' OR 'members_display_name' => the cleaned username, 'errors' => Any errors found
	 **/
	public function cleanAndCheckName( $name, $member=array(), $field='members_display_name' )
	{
		//-----------------------------------------
		// Clean the name first
		//-----------------------------------------
		
		$cleanedName	= $this->_cleanName( $name, $field );

		if( count($cleanedName['errors']) )
		{
			if( $field == 'members_display_name' )
			{
				return array( 'members_display_name' => $cleanedName['name'], 'errors' => array( 'dname' => $cleanedName['errors'][0] ) );
			}
			else
			{
				return array( 'username' => $cleanedName['name'], 'errors' => array( 'username' => $cleanedName['errors'][0] ) );
			}
		}

		//-----------------------------------------
		// Name is clean, make sure it doesn't exist
		//-----------------------------------------
		
		try
		{
			if( !$this->checkNameExists( $cleanedName['name'], $member, $field, true, true ) )
			{
				if( $field == 'members_display_name' )
				{
					return array( 'members_display_name' => $cleanedName['name'], 'errors' => array() );
				}
				else
				{
					return array( 'username' => $cleanedName['name'], 'errors' => array() );
				}
			}
			else
			{
				if( $field == 'members_display_name' )
				{
					return array( 'members_display_name' => $cleanedName['name'], 'errors' => array( 'dname' => ipsRegistry::getClass( 'class_localization' )->words['reg_error_username_taken'] ) );
				}
				else
				{
					return array( 'username' => $cleanedName['name'], 'errors' => array( 'username' => ipsRegistry::getClass( 'class_localization' )->words['reg_error_username_taken'] ) );
				}
			}
		}
		catch( Exception $e )
		{
			//-----------------------------------------
			// Name exists, let's return appropriately
			//-----------------------------------------

			if( $field == 'members_display_name' )
			{
				switch( $e->getMessage() )
				{
					case 'NO_NAME':
						return array( 'members_display_name' => $cleanedName['name'], 'errors' => array( 'dname' => ipsRegistry::getClass( 'class_localization' )->words['reg_error_no_name'] ) );
					break;
					
					case 'ILLEGAL_CHARS':
						return array( 'members_display_name' => $cleanedName['name'], 'errors' => array( 'dname' => ipsRegistry::getClass( 'class_localization' )->words['reg_error_chars'] ) );
					break;
				}
			}
			else
			{
				switch( $e->getMessage() )
				{
					case 'NO_NAME':
						return array( 'username' => $cleanedName['name'], 'errors' => array( 'username' => ipsRegistry::getClass( 'class_localization' )->words['reg_error_username_none'] ) );
					break;
					
					case 'ILLEGAL_CHARS':
						return array( 'username' => $cleanedName['name'], 'errors' => array( 'username' => ipsRegistry::getClass( 'class_localization' )->words['reg_error_chars'] ) );
					break;
				}
			}
		}
	}
	
	/**
	 * Check for an existing display or user name
	 *
	 * @access	public
	 * @param	string	Name to check
	 * @param	array	[ Optional Member Array ]
	 * @param	string	name or members_display_name
	 * @param	bool	Ignore display name changes check (e.g. for registration)
	 * @param	bool	Do not clean name again (e.g. coming from cleanAndCheckName)
	 * @return	mixed	Either an exception or ( true if name exists. False if name DOES NOT exist )
	 * Error Codes:
	 * NO_PERMISSION		This user cannot change their display name at all
	 * NO_MORE_CHANGES		The user cannot change their display name again in this time period
	 * NO_NAME				No display name (or shorter than 3 chars was given)
	 * ILLEGAL_CHARS		The display name contains illegal characters
	 */
	public function checkNameExists( $name, $member=array(), $field='members_display_name', $ignore=false, $cleaned=false )
	{
		if( ! $cleaned )
		{
			$cleanedName	= $this->_cleanName( $name, $field );
			$name			= $cleanedName['name'];

			if( count($cleanedName['errors']) )
			{
				throw new Exception( $cleanedName['errors'][0] );
			}
		}

		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$error        = "";
		$banFilters   = array();
		$_timeCheck   = time() - 86400 * $this->memberData['g_dname_date'];
		$member       = ( isset( $member['member_id'] ) ) ? $member : $this->memberData;
		$checkField   = ( $field == 'members_display_name' ) ? 'members_l_display_name' : 'members_l_username';
		
		//-----------------------------------------
		// Public checks
		//-----------------------------------------
		
		if ( IPS_AREA != 'admin' AND $ignore != true )
		{
			if ( ! $this->settings['auth_allow_dnames'] OR $member['g_dname_changes'] < 1 OR $member['g_dname_date'] < 1 )
			{
				throw new Exception( "NO_PERMISSION" );
			}
			
			/* Check new permissions */
			$_g = $this->caches['group_cache'][ $member['member_group_id'] ];
		
			if ( $_g['g_displayname_unit'] )
			{
				if ( $_g['gbw_displayname_unit_type'] )
				{
					/* days */
					if ( $member['joined'] > ( time() - ( 86400 * $_g['g_displayname_unit'] ) ) )
					{
						throw new Exception( "NO_PERMISSION" );
					}
				}
				else
				{
					/* Posts */
					if ( $member['posts'] < $_g['g_displayname_unit'] )
					{
						throw new Exception( "NO_PERMISSION" );
					}
				}
			}
			
			//-----------------------------------------
			// Grab # changes > 24 hours
			//-----------------------------------------

			$name_count = $this->DB->buildAndFetch( array( 'select' => 'COUNT(*) as count, MIN(dname_date) as min_date', 'from' => 'dnames_change', 'where' => "dname_member_id=" . $member['member_id'] . " AND dname_date > $_timeCheck" ) );

			$name_count['count']    = intval( $name_count['count'] );
			$name_count['min_date'] = intval( $name_count['min_date'] ) ? intval( $name_count['min_date'] ) : $_timeCheck;

			if ( intval( $name_count['count'] ) >= $member['g_dname_changes'] )
			{
				throw new Exception( "NO_MORE_CHANGES" );
			}
		}

		//-----------------------------------------
		// Load ban filters
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'banfilters' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$banFilters[ $r['ban_type'] ][] = $r['ban_content'];
		}

		//-----------------------------------------
		// Are they banned [NAMES]?
		//-----------------------------------------
		
		if ( IPS_AREA != 'admin' )
		{
			if ( is_array( $banFilters['name'] ) and count( $banFilters['name'] ) )
			{
				foreach ( $banFilters['name'] as $n )
				{
					if ( $n == "" )
					{
						continue;
					}
					
					$n = str_replace( '\*', '.*' ,  preg_quote($n, "/") );
					
					if ( preg_match( "/^{$n}$/i", $name ) )
					{
						return TRUE;
						break;
					}
				}
			}
		}
		
		//-----------------------------------------
		// Check for existing name.
		//-----------------------------------------
		
		$this->DB->build( array( 
									'select' => "{$field}, member_id",
									'from'   => 'members',
									'where'  => $checkField . "='". $this->DB->addSlashes( strtolower($name) )."' AND member_id != ".$member['member_id'],
									'limit'  => array( 0,1 ) ) );

    	$this->DB->execute();
    	
    	//-----------------------------------------
    	// Got any results?
    	//-----------------------------------------
    	
    	if ( $this->DB->getTotalRows() )
 		{
    		return TRUE;
    	}
    	
		//-----------------------------------------
    	// Not allowed to select another's log in name
    	//-----------------------------------------
    	
    	if ( $field == 'members_display_name' AND $this->settings['auth_dnames_nologinname'] )
    	{ 
    		$check_name = $this->DB->buildAndFetch( array( 'select' => "{$field}, member_id",
																	'from'   => 'members',
																	'where'  => "members_l_username='" . $this->DB->addSlashes( strtolower($name) ) . "'",
																	'limit'  => array( 0,1 ) ) );
    											 
    		if ( $this->DB->getTotalRows() )
    		{
    			if ( $member['member_id'] AND $check_name['member_id'] != $member['member_id'] )
    			{
    				return TRUE;
				}
			}
    	}
    	
    	if ( $field == 'name' AND $this->settings['auth_dnames_nologinname'] )
    	{ 
    		$check_name = $this->DB->buildAndFetch( array( 'select' => "{$field}, member_id",
																	'from'   => 'members',
																	'where'  => "members_l_display_name='" . $this->DB->addSlashes( strtolower($name) ) . "'",
																	'limit'  => array( 0,1 ) ) );
    											 
    		if ( $this->DB->getTotalRows() )
    		{
    			if ( $member['member_id'] AND $check_name['member_id'] != $member['member_id'] )
    			{
    				return TRUE;
				}
			}
    	}

    	//-----------------------------------------
    	// Test for unicode name
    	//-----------------------------------------
    	
    	$unicodeName	= $this->_getUnicodeName( $name );
    	
    	if ( $unicodeName != $name )
		{
			//-----------------------------------------
			// Check for existing name.
			//-----------------------------------------
			
			$this->DB->build( array( 'select' => "members_display_name, member_id, email",
										   'from'   => 'members',
										   'where'  => $checkField . "='". $this->DB->addSlashes( strtolower($unicodeName) )."' AND member_id != ".$member['member_id'],
										   'limit'  => array( 0,1 ) ) );
													 
			$this->DB->execute();
			
			//-----------------------------------------
			// Got any results?
			//-----------------------------------------
			
			if ( $this->DB->getTotalRows() )
			{
				return TRUE;
			}
		}
    	
    	return FALSE;
	}

	/**
	 * Clean a username or display name
	 *
	 * @access	protected
	 * @param	string		Name
	 * @param	string		Field (name or members_display_name)
	 * @return	array		array( 'name' => $cleaned_name, 'errors' => array() )
	 */
	protected function _cleanName( $name, $field='members_display_name' )
	{
		$original	= $name;
		$name		= trim($name);
		
		if( $field == 'name' )
		{
			// Commented out for bug report #15354
			//$name	= str_replace( '|', '&#124;' , $name );
			
			/* Remove multiple spaces */
			$name	= preg_replace( "/\s{2,}/", " ", $name );
		}
		
		//-----------------------------------------
		// Remove line breaks
		//-----------------------------------------
		
		if( ipsRegistry::$settings['usernames_nobr'] )
		{
			$name = IPSText::br2nl( $name );
			$name = str_replace( "\n", "", $name );
			$name = str_replace( "\r", "", $name );
		}
		
		//-----------------------------------------
		// Remove sneaky spaces
		//-----------------------------------------
		
		if ( ipsRegistry::$settings['strip_space_chr'] )
    	{
    		/* use hexdec to convert between '0xAD' and chr */
			$name          = IPSText::removeControlCharacters( $name );
		}

		//-----------------------------------------
		// Trim after above ops
		//-----------------------------------------
		
		$name = trim( $name );

		//-----------------------------------------
		// Test unicode name
		//-----------------------------------------
		
		$unicode_name	= $this->_getUnicodeName( $name );

		//-----------------------------------------
		// Do we have a name?
		//-----------------------------------------
		
		if( $field == 'name' OR ( $field == 'members_display_name' AND ipsRegistry::$settings['auth_allow_dnames'] ) )
		{
			if( ! $name OR IPSText::mbstrlen( $name ) < 3  OR IPSText::mbstrlen( $name ) > ipsRegistry::$settings['max_user_name_length'] )
			{
				ipsRegistry::getClass( 'class_localization' )->loadLanguageFile( array( 'public_register' ), 'core' );
				
				$key	= $field == 'members_display_name' ? 'reg_error_no_name' : 'reg_error_username_none';

				$text	= sprintf( ipsRegistry::getClass( 'class_localization' )->words[ $key ], ipsRegistry::$settings['max_user_name_length'] );
				
				//-----------------------------------------
				// Only show note about special chars when relevant
				//-----------------------------------------
				
				if( strpos( $name, '&' ) !== false )
				{
					$text	.= ipsRegistry::getClass( 'class_localization' )->words['reg_error_no_name_spec'];
				}
				
				return array( 'name' => $original, 'errors' => array( $text ) );
			}
		}

		//-----------------------------------------
		// Blocking certain chars in username?
		//-----------------------------------------
		
		if( ipsRegistry::$settings['username_characters'] )
		{
			$check_against = preg_quote( ipsRegistry::$settings['username_characters'], "/" );

			if( !preg_match( "/^[" . $check_against . "]+$/i", $name ) )
			{
				return array( 'name' => $original, 'errors' => array( str_replace( '{chars}', ipsRegistry::$settings['username_characters'], ipsRegistry::$settings['username_errormsg'] ) ) );
			}
		}
		
		//-----------------------------------------
		// Manually check against bad chars
		//-----------------------------------------
		
		if( strpos( $unicode_name, '&#92;' ) !== false OR 
			strpos( $unicode_name, '&#quot;' ) !== false OR 
			strpos( $unicode_name, '&#036;' ) !== false OR
			strpos( $unicode_name, '&#lt;' ) !== false OR
			strpos( $unicode_name, '$' ) !== false OR
			strpos( $unicode_name, ']' ) !== false OR
			strpos( $unicode_name, '[' ) !== false OR
			strpos( $unicode_name, ',' ) !== false OR
			strpos( $unicode_name, '|' ) !== false OR
			strpos( $unicode_name, '&#gt;' ) !== false )
		{
			ipsRegistry::getClass( 'class_localization' )->loadLanguageFile( array( 'public_register' ), 'core' );
			
			return array( 'name' => $original, 'errors' => array( ipsRegistry::getClass( 'class_localization' )->words['reg_error_chars'] ) );
		}

		return array( 'name' => $name, 'errors' => array() );
	}
	
	/**
	 * Get unicode version of name
	 *
	 * @access	protected
	 * @param	string		Name
	 * @return	string		Unicode Name
	 */
	protected function _getUnicodeName( $name )
	{
		$unicode_name  = preg_replace_callback( '/&#([0-9]+);/si', create_function( '$matches', 'return chr($matches[1]);' ), $name );
		$unicode_name  = str_replace( "'" , '&#39;', $name );
		$unicode_name  = str_replace( "\\", '&#92;', $name );
		
		return $unicode_name;
	}

	/**
	 * Upload personal photo function
	 * Assumes all security checks have been performed by this point
	 *
	 * @access	public
	 * @param	integer		[Optional] member id instead of current member
	 * @return 	array  		[ error (error message), status (status message [ok/fail] ) ]
	 */
	public function uploadPhoto( $member_id = 0 )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$return		      = array( 'error'            => '',
								   'status'           => '',
								   'final_location'   => '',
								   'final_width'      => '',
								   'final_height'     => '',
								   't_final_location' => '',
								   't_final_width'    => '',
								   't_final_height'   => ''  );
		$delete_photo     = intval( $_POST['delete_photo'] );
		$member_id        = $member_id ? intval($member_id) : intval( $this->memberData['member_id'] );
		$real_name        = '';
		$upload_dir       = '';
		$final_location   = '';
		$final_width      = '';
		$final_height     = '';
		$t_final_location = '';
		$t_final_width    = '';
		$t_final_height   = '';
		$t_real_name      = '';
		$t_height		  = 50;
		$t_width          = 50;
		
		if( !$member_id )
		{
			return array( 'status' => 'cannot_find_member' );
		}
				
		list($p_max, $p_width, $p_height) = explode( ":", $this->memberData[ 'g_photo_max_vars' ] );
		
		$this->settings[ 'disable_ipbsize'] =  0 ;
		
		//-----------------------------------------
		// Sort out upload dir
		//-----------------------------------------

		/* Fix for bug 5075 */
		$this->settings['upload_dir'] = str_replace( '&#46;', '.', $this->settings['upload_dir'] );		

		$upload_path  = $this->settings['upload_dir'];
		
		# Preserve original path
		$_upload_path = $this->settings['upload_dir'];
		
		//-----------------------------------------
		// Already a dir?
		//-----------------------------------------
		
		if ( ! file_exists( $upload_path . "/profile" ) )
		{
			if ( @mkdir( $upload_path . "/profile", 0777 ) )
			{
				@file_put_contents( $upload_path . '/profile/index.html', '' );
				@chmod( $upload_path . "/profile", 0777 );
				
				# Set path and dir correct
				$upload_path .= "/profile";
				$upload_dir   = "profile/";
			}
			else
			{
				# Set path and dir correct
				$upload_dir   = "";
			}
		}
		else
		{
			# Set path and dir correct
			$upload_path .= "/profile";
			$upload_dir   = "profile/";
		}
		
		//-----------------------------------------
		// Deleting the photo?
		//-----------------------------------------
		
		if ( $delete_photo )
		{
			$memberData	= IPSMember::load( $member_id );
			$bwOptions	= IPSBWOptions::thaw( $memberData['fb_bwoptions'], 'facebook' );
			$bwOptions['fbc_s_pic']	= 0;
		
			$this->removeUploadedPhotos( $member_id, $upload_path );
			
			IPSMember::save( $member_id, array( 'extendedProfile' => array( 'pp_main_photo'		=> '',
													  				   	 	'pp_main_width'		=> 0,
																		   	'pp_main_height'	=> 0,
																			'pp_thumb_photo'	=> '',
																			'pp_thumb_width'	=> 0,
																			'pp_thumb_height'	=> 0,
																			'fb_photo'			=> '',
																			'fb_photo_thumb'	=> '',
																			'fb_bwoptions'		=> IPSBWOptions::freeze( $bwOptions, 'facebook' )
																		 ) ) );
			$return['status'] = 'deleted';
			return $return;
		}
		
		//-----------------------------------------
		// Lets check for an uploaded photo..
		//-----------------------------------------

		if ( $_FILES['upload_photo']['name'] != "" and ($_FILES['upload_photo']['name'] != "none" ) )
		{
			//-----------------------------------------
			// Are we allowed to upload this photo?
			//-----------------------------------------
			
			if ( $p_max < 0 )
			{
				$return['status'] = 'fail';
				$return['error']  = 'no_photo_upload_permission';
			}
			
			//-----------------------------------------
			// Remove any uploaded photos...
			//-----------------------------------------
			
			$this->removeUploadedPhotos( $member_id, $upload_path );
			
			$real_name = 'photo-'.$member_id;
			
			//-----------------------------------------
			// Load the library
			//-----------------------------------------
			
			require_once( IPS_KERNEL_PATH.'classUpload.php' );
			$upload    = new classUpload();

			//-----------------------------------------
			// Set up the variables
			//-----------------------------------------

			$upload->out_file_name     = 'photo-'.$member_id;
			$upload->out_file_dir      = $upload_path;
			$upload->max_file_size     = ($p_max * 1024) * 8;  // Allow xtra for compression
			$upload->upload_form_field = 'upload_photo';
			
			//-----------------------------------------
			// Populate allowed extensions
			//-----------------------------------------

			if ( is_array( $this->cache->getCache('attachtypes') ) and count( $this->cache->getCache('attachtypes') ) )
			{
				foreach( $this->cache->getCache('attachtypes') as $data )
				{
					if ( $data['atype_photo'] )
					{
						if( $data['atype_extension'] == 'swf' AND $this->settings['disable_flash'] )
						{
							continue;
						}

						$upload->allowed_file_ext[] = $data['atype_extension'];
					}
				}
			}
			
			//-----------------------------------------
			// Upload...
			//-----------------------------------------
			
			$upload->process();
			
			//-----------------------------------------
			// Error?
			//-----------------------------------------
			
			if ( $upload->error_no )
			{
				switch( $upload->error_no )
				{
					case 1:
						// No upload
						$return['status'] = 'fail';
						$return['error']  = 'upload_failed';
					break;
					case 2:
						// Invalid file ext
						$return['status'] = 'fail';
						$return['error']  = 'invalid_file_extension';
					break;
					case 3:
						// Too big...
						$return['status'] = 'fail';
						$return['error']  = 'upload_to_big';
					break;
					case 4:
						// Cannot move uploaded file
						$return['status'] = 'fail';
						$return['error']  = 'upload_failed';
					break;
					case 5:
						// Possible XSS attack (image isn't an image)
						$return['status'] = 'fail';
						$return['error']  = 'upload_failed';
					break;
				}
				
				return $return;
			}
						
			//-----------------------------------------
			// Still here?
			//-----------------------------------------
			
			$real_name   = $upload->parsed_file_name;
			$t_real_name = $upload->parsed_file_name;

			//-----------------------------------------
			// Check image size...
			//-----------------------------------------
			
			if ( ! $this->settings['disable_ipbsize'] )
			{
				$imageDimensions = getimagesize( $upload_path . '/' . $real_name );
				
				if( $imageDimensions[0] > $p_width OR $imageDimensions[1] > $p_height )
				{
					//-----------------------------------------
					// Main photo
					//-----------------------------------------
					
					require_once( IPS_KERNEL_PATH."classImage.php" ); 
					require_once( IPS_KERNEL_PATH."classImageGd.php" );
					$image = new classImageGd();
					
					$image->init( array( 
					                         'image_path'     => $upload_path, 
					                         'image_file'     => $real_name, 
					               )          );
	
					$return = $image->resizeImage( $p_width, $p_height );
					$image->writeImage( $upload_path . '/' . 'photo-'.$member_id . '.' . $upload->file_extension );
	
					$t_real_name = $return['thumb_location'] ? $return['thumb_location'] : $real_name;
	
					$im['img_width']  = $return['newWidth'] ? $return['newWidth'] : $image->cur_dimensions['width'];
					$im['img_height'] = $return['newHeight'] ? $return['newHeight'] : $image->cur_dimensions['height'];
	
					//-----------------------------------------
					// MINI photo
					//-----------------------------------------
					
					$image->init( array( 
					                         'image_path'     => $upload_path, 
					                         'image_file'     => $t_real_name, 
					               )          );
	
					$return = $image->resizeImage( $t_width, $t_height );
					$image->writeImage( $upload_path . '/' . 'photo-thumb-'.$member_id . '.' . $upload->file_extension );
	
					$t_im['img_width']    = $return['newWidth'];
					$t_im['img_height']   = $return['newHeight'];
					$t_im['img_location'] = count($return) ? 'photo-thumb-'.$member_id . '.' . $upload->file_extension : $real_name;
				}
				else
				{
					$im['img_width']  = $imageDimensions[0];
					$im['img_height'] = $imageDimensions[1];
					
					//-----------------------------------------
					// Mini photo
					//-----------------------------------------
					
					$_data = IPSLib::scaleImage( array( 'max_height' => $t_height,
																  'max_width'  => $t_width,
																  'cur_width'  => $im['img_width'],
																  'cur_height' => $im['img_height'] ) );
					
					$t_im['img_width']  	= $_data['img_width'];
					$t_im['img_height']		= $_data['img_height'];
					$t_im['img_location']	= $real_name;
				}
			}
			else
			{
				//-----------------------------------------
				// Main photo
				//-----------------------------------------
				
				$w = intval($this->request['man_width'])  ? intval($this->request['man_width'])  : $p_width;
				$h = intval($this->request['man_height']) ? intval($this->request['man_height']) : $p_height;
				$im['img_width']  = $w > $p_width  ? $p_width  : $w;
				$im['img_height'] = $h > $p_height ? $p_height : $h;
				
				//-----------------------------------------
				// Mini photo
				//-----------------------------------------
				
				$_data = IPSLib::scaleImage( array( 'max_height' => $t_height,
															  'max_width'  => $t_width,
															  'cur_width'  => $im['img_width'],
															  'cur_height' => $im['img_height'] ) );
				
				$t_im['img_width']  	= $_data['img_width'];
				$t_im['img_height']		= $_data['img_height'];
				$t_im['img_location']	= $real_name;
			}
			
			//-----------------------------------------
			// Check the file size (after compression)
			//-----------------------------------------
			
			if ( filesize( $upload_path . "/" . $real_name ) > ( $p_max * 1024 ) )
			{
				@unlink( $upload_path . "/" . $real_name );
				
				// Too big...
				$return['status'] = 'fail';
				$return['error']  = 'upload_to_big';
				return $return;
			}
			
			//-----------------------------------------
			// Main photo
			//-----------------------------------------
			
			$final_location = $upload_dir . $real_name;
			$final_width    = $im['img_width'];
			$final_height   = $im['img_height'];
			
			//-----------------------------------------
			// Mini photo
			//-----------------------------------------
			
			$t_final_location = $upload_dir . $t_im['img_location'];
			$t_final_width    = $t_im['img_width'];
			$t_final_height   = $t_im['img_height'];
		}
		else
		{
			$return['status'] = 'ok';
			return $return;
		}
		
		//-----------------------------------------
		// Return...
		//-----------------------------------------
		
		$return['final_location']   = $final_location;
		$return['final_width']      = $final_width;
		$return['final_height']     = $final_height;
		
		$return['t_final_location'] = $t_final_location;
		$return['t_final_width']    = $t_final_width;
		$return['t_final_height']   = $t_final_height;
		
		$return['status'] = 'ok';
		return $return;
	}
	
	
	/**
	 * Remove member uploaded photos
	 *
	 * @access	public
	 * @param	integer		Member ID
	 * @param	string		[Optional] Directory to check
	 * @return 	array  		[ error (error message), status (status message [ok/fail] ) ]
	 */
	public function removeUploadedPhotos( $id, $upload_path='' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$upload_path = $upload_path ? $upload_path : $this->settings['upload_dir'];
		
		# Preserve original path
		$_upload_path = $this->settings['upload_dir'];
		
		//-----------------------------------------
		// Already a dir?
		//-----------------------------------------
		
		if ( ! file_exists( $upload_path . "/profile" ) )
		{
			if ( @mkdir( $upload_path . "/profile", 0777 ) )
			{
				@file_put_contents( $upload_path . '/index.html', '' );
				@chmod( $upload_path . "/profile", 0777 );
				
				# Set path and dir correct
				$upload_path .= "/profile";
				$upload_dir   = "profile/";
			}
			else
			{
				# Set path and dir correct
				$upload_dir   = "";
			}
		}
		else
		{
			# Set path and dir correct
			$upload_path .= "/profile";
			$upload_dir   = "profile/";
		}
		
		//-----------------------------------------
		// Go...
		//-----------------------------------------
		
		foreach( array( 'swf', 'jpg', 'jpeg', 'gif', 'png' ) as $ext )
		{
			if ( @file_exists( $upload_path."/photo-".$id.".".$ext ) )
			{
				@unlink( $upload_path."/photo-".$id.".".$ext );
			}
			
			if ( @file_exists( $upload_path."/photo-thumb-".$id.".".$ext ) )
			{
				@unlink( $upload_path."/photo-thumb-".$id.".".$ext );
			}
		}
	}
	
	/**
	 * Remove member's avatar
	 *
	 * @access	public
	 * @param	int			Member's ID
	 * @return	mixed		Exception or true
	 * <code>
	 * Exception Codes:
	 * NO_MEMBER_ID:					A valid member ID was not passed.
	 * NO_PERMISSION:				You do not have permission to change the avatar
	 * </code>
	 */
	public function removeAvatar( $member_id )
	{
		//-----------------------------------------
		// Get member
		//-----------------------------------------
		
		$member = IPSMember::load( $member_id, 'extendedProfile, groups' );
																	
		if ( ! $member['member_id'] )
		{
			throw new Exception( "NO_MEMBER_ID" );
		}

		//-----------------------------------------
		// Allowed to upload pics for administrators?
		//-----------------------------------------
		
		if ( IPS_AREA != 'public' )
		{
			if ( $member['g_access_cp'] AND !$this->registry->getClass('class_permissions')->checkPermission( 'member_photo_admin', 'members', 'members' ) )
			{
				throw new Exception( "NO_PERMISSION" );
			}
		}
		
		//-----------------------------------------
		// Actaully remove it..
		//-----------------------------------------

		if ( $avatar['avatar_type'] == 'upload' )
		{
			foreach( array( 'swf', 'jpg', 'jpeg', 'gif', 'png' ) as $ext )
			{
				if ( @file_exists( $this->settings['upload_dir'] . "/av-" . $member_id . "." . $ext ) )
				{
					@unlink( $this->settings['upload_dir'] . "/av-" . $member_id . "." . $ext );
				}
			}
		}
		
		IPSMember::save( $member_id, array( 'extendedProfile' => array( 'avatar_type' => '', 'avatar_location' => '', 'avatar_size' => '' ) ) );
		
		if ( IPS_AREA != 'public' )
		{
			$this->registry->getClass('adminFunctions')->saveAdminLog("Member's avatar removed ( member_id: {$member_id} )");
		}
		
		return TRUE;
	}
	/**
	 * Saves the member's avatar
	 *
	 * @param		INT			Member's ID to save
	 * @param		string		Upload field name [Default is "upload_avatar"]
	 * @param		string		Avatar URL Field [Default is "avatar_url"]
	 * @param		string		Gallery Avatar Directory Field [Default is "avatar_gallery"]
	 * @param		string		Gallery Avatar Image Field [Default is "avatar_image"]
	 * @author		Brandon Farber, Stolen By Matt 'Haxor' Mecham
	 * <code>
	 * Excepton Codes:
	 * NO_MEMBER_ID:				A valid member ID was not passed.
	 * NO_PERMISSION:				You do not have permission to change the avatar
	 * UPLOAD_NO_IMAGE:				Nothing to upload
	 * UPLOAD_INVALID_FILE_EXT:		Incorrect file extension (not an image)
	 * UPLOAD_TOO_LARGE:			Upload is larger than allowed
	 * UPLOAD_CANT_BE_MOVED:		Upload cannot be moved into the uploads directory
	 * UPLOAD_NOT_IMAGE:			Upload is not an image, despite what the file extension says!
	 * NO_AVATAR_TO_SAVE:			Nothing to save!
	 * </code>
	 */
	public function saveNewAvatar( $member_id, $uploadFieldName='upload_avatar', $urlFieldName='avatar_url', $galleryFieldName='avatar_gallery', $avatarGalleryImage='avatar_image', $gravatarFieldName='gravatar_email' )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$avatar						= array();
		list($p_width, $p_height)	= explode( "x", strtolower( $this->settings['avatar_dims'] ) );

		if ( ! $member_id )
		{
			throw new Exception( "NO_MEMBER_ID" );
		}

		$member = IPSMember::load( $member_id, 'extendedProfile,groups' );

		if ( ! $member['member_id'] )
		{
			throw new Exception( "NO_MEMBER_ID" );
		}

		//-----------------------------------------
		// Allowed to upload pics for administrators?
		//-----------------------------------------
		
		if ( IPS_AREA != 'public' )
		{
			if ( $member['g_access_cp'] AND !$this->registry->getClass('class_permissions')->checkPermission( 'member_photo_admin', 'members', 'members' ) )
			{
				throw new Exception( "NO_PERMISSION" );
			}
		}

		//-----------------------------------------
		// Upload?
		//-----------------------------------------

		if( $_FILES[$uploadFieldName]['name'] != "" AND $_FILES[$uploadFieldName]['name'] != "none" )
		{
			$this->settings[ 'upload_dir'] =  str_replace( '&#46;', '.', $this->settings['upload_dir']  );

			$real_name = 'av-' . $member_id;

			require_once( IPS_KERNEL_PATH.'classUpload.php' );
			$upload = new classUpload();

			$upload->out_file_name     = $real_name;
			$upload->out_file_dir      = $this->settings['upload_dir'];
			$upload->max_file_size     = ($this->settings['avup_size_max'] * 1024) * 8;  // Allow xtra for compression
			$upload->upload_form_field = $uploadFieldName;

			//-----------------------------------------
			// Populate allowed extensions
			//-----------------------------------------

			if ( is_array( $this->cache->getCache('attachtypes') ) and count( $this->cache->getCache('attachtypes') ) )
			{
				foreach( $this->cache->getCache('attachtypes') as $data )
				{
					if ( $data['atype_photo'] )
					{
						if( $data['atype_extension'] == 'swf' AND $this->settings['disable_flash'] )
						{
							continue;
						}

						$upload->allowed_file_ext[] = $data['atype_extension'];
					}
				}
			}

			//-----------------------------------------
			// Upload...
			//-----------------------------------------

			$upload->process();

			//-----------------------------------------
			// Error?
			//-----------------------------------------

			if ( $upload->error_no )
			{
				switch( $upload->error_no )
				{
					case 1:
						// No upload
						throw new Exception("UPLOAD_NO_IMAGE");
					break;
					case 2:
						// Invalid file ext
						throw new Exception("UPLOAD_INVALID_FILE_EXT");
					break;
					case 3:
						// Too big...
						throw new Exception("UPLOAD_TOO_LARGE");
					break;
					case 4:
						// Cannot move uploaded file
						throw new Exception("UPLOAD_CANT_BE_MOVED");
					break;
					case 5:
						// Possible XSS attack (image isn't an image)
						throw new Exception("UPLOAD_NOT_IMAGE");
					break;
				}
			}

			$real_name	= $upload->parsed_file_name;
			$im			= array();

			if ( ! $this->settings['disable_ipbsize'] and $upload->file_extension != '.swf' )
			{
				$imageDimensions = getimagesize( $this->settings['upload_dir'] . '/' . $real_name );
				
				if( $imageDimensions[0] > $p_width OR $imageDimensions[1] > $p_height )
				{
					require_once( IPS_KERNEL_PATH."classImage.php" ); 
					require_once( IPS_KERNEL_PATH."classImageGd.php" );
					$image = new classImageGd();
	
					$image->init( array( 
					                         'image_path'     => $this->settings['upload_dir'], 
					                         'image_file'     => $real_name, 
					               )          );
	
					$return = $image->resizeImage( $p_width, $p_height );
					$image->writeImage( $this->settings['upload_dir'] . '/' . $real_name );
	
					$im['img_width']  = $return['newWidth'] ? $return['newWidth'] : $image->cur_dimensions['width'];
					$im['img_height'] = $return['newHeight'] ? $return['newHeight'] : $image->cur_dimensions['height'];
				}
				else
				{
					$im['img_width']  = $imageDimensions[0];
					$im['img_height'] = $imageDimensions[1];
				}
			}
			else
			{	
				$w 					= intval($this->request['man_width'])  ? intval($this->request['man_width'])  : $p_width;
				$h 					= intval($this->request['man_height']) ? intval($this->request['man_height']) : $p_height;
				$im['img_width']	= $w > $p_width  ? $p_width  : $w;
				$im['img_height']	= $h > $p_height ? $p_height : $h;
			}

			//-----------------------------------------
			// Set the "real" avatar..
			//-----------------------------------------

			$avatar['avatar_location']		= $real_name;
			$avatar['avatar_size']			= $im['img_width'].'x'.$im['img_height'];
			$avatar['avatar_type']			= 'upload';
		}

		//-----------------------------------------
		// URL?
		//-----------------------------------------

		else if( $this->request[ $urlFieldName ] AND IPSText::xssCheckUrl( $this->request[ $urlFieldName ] ) === true )
		{
			$ext 		= explode ( ",", $this->settings['avatar_ext'] );
			$checked 	= 0;
			$av_ext 	= preg_replace( "/^.*\.(\S+)$/", "\\1", $this->request[$urlFieldName] );

			foreach( $ext as $v  )
			{
				if( strtolower( $v ) == strtolower( $av_ext ) )
				{
					if( $v == 'swf' AND $this->settings['disable_flash'] )
					{
						throw new Exception("INVALID_FILE_EXT");
					}
					
					$checked = 1;
					break;
				}
			}
			
			if( $checked != 1 )
			{
				throw new Exception("INVALID_FILE_EXT");
			}
			
			if ( ! $this->settings['disable_ipbsize'] )
			{
				if ( ! $img_size = @getimagesize( $this->request[$urlFieldName] ) )
				{
					$img_size[0] = $p_width;
					$img_size[1] = $p_height;
				}

				$im = IPSLib::scaleImage( array(
												'max_width'  => $p_width,
												'max_height' => $p_height,
												'cur_width'  => $img_size[0],
												'cur_height' => $img_size[1]
										)		);
			}
			else
			{	
				$w					= intval($this->request['man_width'])  ? intval($this->request['man_width'])  : $p_width;
				$h					= intval($this->request['man_height']) ? intval($this->request['man_height']) : $p_height;
				$im['img_width']	= $w > $p_width  ? $p_width  : $w;
				$im['img_height']	= $h > $p_height ? $p_height : $h;
			}

			$avatar['avatar_location']		= trim( $this->request[ $urlFieldName ] );
			$avatar['avatar_size']			= $im['img_width'].'x'.$im['img_height'];
			$avatar['avatar_type']			= 'url';
		}

		//-----------------------------------------
		// Local image?
		//-----------------------------------------

		else if( isset($this->request[ $galleryFieldName ]) AND $this->request[ $avatarGalleryImage ] )
		{
			$directory	= '';

			if( $this->request[$galleryFieldName] )
			{
				$directory						= preg_replace( "/[^\s\w_-]/", "", urldecode( $this->request[$galleryFieldName] ) );
				
				if( $directory )
				{
					$directory .= '/';
				}
			}

			$filename						= preg_replace( "/[^\s\w\._\-\[\]\(\)]/", "", urldecode( $this->request[$avatarGalleryImage] ) );

			if( file_exists( DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/style_avatars/' . $directory . $filename ) )
			{
				$avatar['avatar_location']		= $directory . $filename;
				$avatar['avatar_size']			= '';
				$avatar['avatar_type']			= 'local';
			}
		}
		
		else if( $this->request[ $gravatarFieldName ] && $this->request[ $gravatarFieldName ] && $this->settings['allow_gravatars'] )
		{
			$avatar['avatar_location']          = strtolower($this->request[ $gravatarFieldName ]);
			$avatar['avatar_type']              = 'gravatar';
		}

		//-----------------------------------------
		// No avatar image?
		//-----------------------------------------

		if ( ! count($avatar) )
		{
			throw new Exception("NO_AVATAR_TO_SAVE");
		}

		//-----------------------------------------
		// Remove old uploaded avatars if needed
		//-----------------------------------------

		else if( $avatar['avatar_type'] != 'upload' )
		{
			foreach( array( 'swf', 'jpg', 'jpeg', 'gif', 'png' ) as $ext )
			{
				if ( @file_exists( $this->settings['upload_dir'] . "/av-" . $member_id . "." . $ext ) )
				{
					@unlink( $this->settings['upload_dir'] . "/av-" . $member_id . "." . $ext );
				}
			}
		}

		//-----------------------------------------
		// Store and redirect
		//-----------------------------------------

		IPSMember::save( $member_id, array( 'extendedProfile' => $avatar ) );

		return TRUE;
	}
	
	/**
	 * Grab all images within a particular avatar gallery directory
	 *
	 * @access	public
	 * @param	string		Selected category name
	 * @return	array 		Array of image names
	 */
	public function getHostedAvatarsFromCategory( $catName )
	{
		//$catName = IPSText::alphanumericalClean( $catName ); // Commented out because alphanumericalClean removes spaces
		$images	 = array();
		$path    = '';
		
		if ( ! $catName )
		{
			$path = DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/style_avatars';
		}
		else if ( $catName == 'none' )
		{
			return '';
		}
		else
		{
			$path = DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/style_avatars/' . $catName;
		}

		if ( ! is_dir( $path ) )
		{
			return FALSE;
		}
		try
		{
			foreach( new DirectoryIterator( $path ) as $file )
			{
				if ( ! $file->isDot() AND $file->isFile() AND strpos( $file->getFilename(), '.' ) !== 0 )
				{
					$_name = $file->getFilename();
					
					$image_properties = @getimagesize( $path . '/' . $_name );
					
					if( is_array($image_properties) AND count($image_properties) AND isset($image_properties[2]) )
					{
						$images[] = $_name;
					}
				}
			}
		} catch ( Exception $e ) {}
		
		natcasesort($images);
		
 		//usort( $images, array( $this, '_sortAvatars' ) );
 		reset( $images );

		return $images;
	}
	
	/**
	 * Grab all hosted avatar gallery directories
	 *
	 * @access	public
	 * @author	Brandon Farber, Matt Mecham
	 * @return	array 	Array of hosted avatar cats
	 */
	public function getHostedAvatarCategories()
	{
		$av_categories = array();
		
		if( is_dir( DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/style_avatars' ) )
		{
			try
			{
				foreach( new DirectoryIterator( DOC_IPS_ROOT_PATH . PUBLIC_DIRECTORY . '/style_avatars' ) as $file )
				{
					if ( ! $file->isDot() AND $file->isDir() )
					{
						$_name = $file->getFileName();
						
						if ( substr( $_name, 0, 1 ) != '.' )
						{
							$av_categories[] = array( $_name, str_replace( "_", " ", $_name ) );
						}
					}
				}
			} catch ( Exception $e ) {}
			
			usort( $av_categories, array( $this, '_sortAvatars' ) );
			reset( $av_categories );
		}

		return $av_categories;
	}
	
	/**
	 * usort method for avatar categories
	 *
	 * @access	private
	 * @param	string		A string to compare
	 * @param	string		B string to compare
	 * @return	void		Sort order algorithm
	 */
	private function _sortAvatars( $a, $b )
 	{
 		$aa = strtolower($a[1]);
 		$bb = strtolower($b[1]);
 		
 		if ( $aa == $bb ) return 0;
 		
 		return ( $aa > $bb ) ? 1 : -1;
 	}
	
}