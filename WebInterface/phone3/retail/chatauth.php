<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Parachat remote authentication file
 * Last Updated: $Date: 2009-07-08 21:23:44 -0400 (Wed, 08 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Chat
 * @version		$Rev: 4856 $
 *
 */

/**
* Script type
*
*/
define( 'IPB_THIS_SCRIPT', 'chat' );

require_once( '../initdata.php' );

/**
 * IPB registry
 */
require_once( IPS_ROOT_PATH . 'sources/base/ipsRegistry.php' );
require_once( IPS_ROOT_PATH . 'sources/base/ipsController.php' );
$ipbRegistry	= ipsRegistry::instance();
$ipbRegistry->init();

//===========================================================================
// AUTHORIZE...
//===========================================================================

$reply_success = 'Result=Success';
$reply_nouser  = 'Result=UserNotFound';
$reply_nopass  = 'Result=WrongPassword';
$reply_error   = 'Result=Error';

$in_user       = IPSText::parseCleanValue(urldecode(trim($_GET['user'])));
$in_pass       = IPSText::parseCleanValue(urldecode(trim($_GET['pass'])));
$in_cookie     = IPSText::parseCleanValue(urldecode(trim($_GET['cookie'])));
$access_groups = ipsRegistry::$settings['chat04_access_groups'];
$in_md5_pass   = "";
$nametmp       = "";
$in_userid     = 0;
$query         = 0;

if ( preg_match( "/^md5pass\((.+?)\)(.+?)$/", $in_pass, $match ) )
{
	$in_md5_pass = $match[1];
	$in_userid   = intval($match[2]);
}
	
//----------------------------------------------
// Did we pass a user ID?
//----------------------------------------------

if ( $in_userid )
{
	$query   = "member_id=" . $in_userid;
	$in_user = 1;
}
else
{
	$in_user = str_replace( '-', '_', $in_user );
	$timeoff = time() - 3600;
	$query   = "members_display_name LIKE '" . addslashes($in_user) . "' AND last_activity > {$timeoff}";
}

//----------------------------------------------
// Continue..
//----------------------------------------------

if ( $in_user and ! $in_pass )
{
	show_message( $reply_nopass );
}

if ( $in_user and $in_pass )
{
	//------------------------------------------
	// Attempt to get member...
	//------------------------------------------
	
	ipsRegistry::DB()->build( array(	'select' 	=> '*',
										'from'		=> 'members',
										'where'		=> $query,
										'limit'		=> array( 0, 1 ),
								)		);
	ipsRegistry::DB()->execute();

	$member = ipsRegistry::DB()->fetch();
	
	if ( ! $member['member_id'] )
	{
		//--------------------------------------
		// Guest...
		//--------------------------------------
		
		test_for_guest();
	}
	
	//------------------------------------------
	// Test for MD5 (future proof)
	//------------------------------------------
	
	if ( ! $in_md5_pass )
	{
		$in_md5_pass = md5( md5( $member['members_pass_salt'] ) . md5($in_pass) );
	}
	
	//------------------------------------------
	// PASSWORD?
	//------------------------------------------
	
	if ( $in_md5_pass == $member['members_pass_hash'] )
	{
		//--------------------------------------
		// Check for access
		//--------------------------------------
		
		if ( ! preg_match( "/(^|,)".$member['member_group_id']."(,|$)/", $access_groups ) )
		{
			show_message( $reply_error );
			//## EXIT ##
		}
		else
		{
			show_message( $reply_success );
			//## EXIT ##
		}
	}
	else
	{
		show_message( $reply_nopass );
		//## EXIT ##
	}
}
else
{
	//------------------------------------------
	// Guest...
	//------------------------------------------
	
	test_for_guest();
}

//===========================================================================
// YES TO GUEST OR NO TO PASS GO
//===========================================================================

function test_for_guest()
{
	global $reply_nouser, $reply_error;
	
	if ( preg_match( "/(^|,)".ipsRegistry::$settings['guest_group']."(,|$)/", $access_groups ) )
	{
		show_message( $reply_nouser );
		//## EXIT ##
	}
	else
	{
		show_message( $reply_error );
		//## EXIT ##
	}
}

//===========================================================================
// SHOW MESSAGE
//===========================================================================

function show_message($msg="Result=Error")
{
	@flush();
	echo $msg;
	exit;
}