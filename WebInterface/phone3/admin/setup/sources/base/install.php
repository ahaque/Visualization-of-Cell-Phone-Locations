<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Static SETUP Classes for IP.Board 3
 *
 * These classes are not required as objects. 
 * Last Updated: $Date: 2009-05-20 07:39:47 -0400 (Wed, 20 May 2009) $
 *
 * @author 		Matt Mecham
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @since		1st December 2008
 * @version		$Revision: 4672 $
 *
 */

/**
 * Collection of methods to for installation alone
 *
 * @author	Matt
 */
class IPSInstall
{
	/**
	 * Create admin account
	 *
	 * @access	public
	 * @return	void
	 */
	static public function createAdminAccount()
	{
		/* Build Entry */
		$_mke_time	= ( ipsRegistry::$settings['login_key_expire'] ) ? ( time() + ( intval( ipsRegistry::$settings['login_key_expire'] ) * 86400 ) ) : 0;
		$salt     	= IPSMember::generatePasswordSalt( 5 );
		$passhash 	= IPSMember::generateCompiledPasshash( $salt, md5( IPSSetUp::getSavedData('admin_pass') ) );
		$_dname     = IPSSetUp::getSavedData('admin_user');
		
		$member = array(
						 'name'						=> $_dname,
						 'members_l_username'		=> strtolower( $_dname ),
						 'members_display_name'		=> $_dname,
						 'members_l_display_name'	=> strtolower( $_dname ),
						 'members_seo_name'			=> IPSText::makeSeoTitle( $_dname ),
						 'member_login_key'			=> IPSMember::generateAutoLoginKey(),
						 'member_login_key_expire'	=> $_mke_time,
						 'title'					=> 'Administrator',
						 'email'					=> IPSSetUp::getSavedData('admin_email') ,
						 'member_group_id'			=> 4,
						 'posts'					=> 1,
						 'joined'					=> time(),
						 'last_visit'               => time(),
						 'last_activity'			=> time(),
						 'ip_address'				=> my_getenv('REMOTE_ADDR'),
						 'view_sigs'				=> 1,
						 'email_pm'					=> 1,
						 'view_img'					=> 1,
						 'view_avs'					=> 1,
						 'restrict_post'			=> 0,
						 'msg_show_notification'	=> 1,
						 'msg_count_total'			=> 0,
						 'msg_count_new'			=> 0,
						 'coppa_user'				=> 0,
						 'language'					=> IPSLib::getDefaultLanguage(),
						 'members_auto_dst'			=> 1,
						 'members_editor_choice'	=> ipsRegistry::$settings['ips_default_editor'],
						 'allow_admin_mails'		=> 0,
						 'hide_email'				=> 1,
						 'members_pass_hash'		=> $passhash,
						 'members_pass_salt'		=> $salt,
					   );
	
		/* Insert: MEMBERS */
		ipsRegistry::DB()->force_data_type = array( 'name' => 'string', 'members_display_name' => 'string', 'members_l_username' => 'string', 'members_l_display_name' => 'string' );

		ipsRegistry::DB()->insert( 'members', $member );

		$member_id           = ipsRegistry::DB()->getInsertId();
		$member['member_id'] = $member_id;

		/* Insert into the custom profile fields DB */
		ipsRegistry::DB()->force_data_type = array();
		ipsRegistry::DB()->insert( 'pfields_content', array( 'member_id' => $member_id, 'updated' => time() ) );
		
		/* Insert into pp */
		ipsRegistry::DB()->insert( 'profile_portal', array( 
														'pp_member_id'				=> $member_id, 
														'pp_setting_count_friends'	=> 1, 
														'signature'					=> '',
														'pconversation_filters'		=> '',
														'pp_setting_count_comments'	=> 1 ) );
	}
	
	/**
	 * Writes out conf_global
	 *
	 * @access	public
	 * @return	bool	File written successfully
	 */	
	static public function writeConfiguration()
	{
		//-----------------------------------------
		// Safe mode?
		//-----------------------------------------
		
		$safe_mode = 0;

		if ( @get_cfg_var('safe_mode') )
		{
			$safe_mode = @get_cfg_var('safe_mode');
		}
		
		//-----------------------------------------
		// Set info array
		//-----------------------------------------
		
		$INFO = array( 
					   'sql_driver'     => IPSSetUp::getSavedData('sql_driver'),
					   'sql_host'       => IPSSetUp::getSavedData('db_host'),
					   'sql_database'   => IPSSetUp::getSavedData('db_name'),
					   'sql_user'       => IPSSetUp::getSavedData('db_user'),
					   'sql_pass'       => IPSSetUp::getSavedData('db_pass'),
					   'sql_tbl_prefix' => IPSSetUp::getSavedData('db_pre'),
					   'sql_debug'      => 1,
					   'sql_charset'    => '',
					
					   'board_start'    => time(),
					   'installed'      => 1,

					   'php_ext'        => 'php',
					   'safe_mode'      => $safe_mode,

					   //'base_url'       => IPSSetUp::getSavedData('install_url'),
					   'board_url'      => IPSSetUp::getSavedData('install_url'),
					   'banned_group'   => '5',
					   'admin_group'    => '4',
					   'guest_group'    => '2',
					   'member_group'   => '3',
					   'auth_group'		=> '1',
					   'use_friendly_urls' => 1,
					   '_jsDebug'          => 0
					 );
					
		//---------------------------------------------
		// Any "extra" configs required for this driver?
		//---------------------------------------------
		
		foreach( IPSSetUp::getSavedDataAsArray() as $k => $v )
		{
			if ( preg_match( "#^__sql__#", $k ) )
			{
				$k = str_replace( "__sql__", "", $k );
				
				$INFO[ $k ] = $v;
			}
		}
		
		//---------------------------------------------
		// Write to disk
		//---------------------------------------------

		$core_conf = "<"."?php\n";

		foreach( $INFO as $k => $v )
		{
			$core_conf .= '$INFO['."'".$k."'".']'."\t\t\t=\t'".$v."';\n";
		}
		
		$core_conf .= "\ndefine('IN_DEV', 0);";

		$core_conf .= "\n".'?'.'>';

		/* Write Configuration Files */
		$output[] = 'Writing configuration files...<br />';
		
		$ret = IPSSetUp::writeFile( IPSSetUp::getSavedData('install_dir') . '/conf_global.php'  , $core_conf );
		
		/* Now freeze data */
		IPSSetUp::freezeSavedData();
		
		return $ret;
	}
	
	/**
	 * Clean up conf global
	 * Removes data variables
	 *
	 * @access	public
	 * @return 	boolean
	 */
	static public function cleanConfGlobal()
	{
		if ( $contents = @file_get_contents( IPSSetUp::getSavedData('install_dir') . '/conf_global.php' ) )
		{
			if ( $contents )
			{
				$contents = preg_replace( "#/\*~~DATA~~\*/(.+?)\n/\*\*/#s", "", $contents );
			
				return IPSSetUp::writeFile( IPSSetUp::getSavedData('install_dir') . '/conf_global.php'  , $contents );
			}
		}
		
		return FALSE;
	}

}