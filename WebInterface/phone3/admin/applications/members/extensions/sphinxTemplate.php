<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Sphinx template file
 * Last Updated: $Date: 2009-07-28 20:26:37 -0400 (Tue, 28 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Members
 * @link		http://www.
 * @version		$Rev: 4948 $
 * @since		3.0.0
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

$appSphinxTemplate	= <<<EOF

############################## --- MEMBERS --- ##############################

source members_search_main : ipb_source_config
{
	# Set our forum PID counter
	sql_query_pre	= REPLACE INTO <!--SPHINX_DB_PREFIX-->cache_store VALUES( 'sphinx_members_counter', (SELECT max(member_id) FROM <!--SPHINX_DB_PREFIX-->members), '', 0, UNIX_TIMESTAMP() )
	
	# Query posts for the main source
	sql_query		= SELECT m.member_id, m.member_id as search_id, m.member_group_id, m.email, m.joined, m.members_display_name, m.name, \
							 REPLACE( pi.perm_view, '*', 0 ) as perm_view, \
							 CASE WHEN pi.authorized_users IS NULL THEN 0 ELSE pi.authorized_users END AS authorized_users, \
							 CASE WHEN pi.friend_only=0 THEN 0 ELSE m.member_id END AS friend_only, \
							 CASE WHEN pi.owner_only=0 THEN 0 ELSE m.member_id END AS owner_only \
					  FROM <!--SPHINX_DB_PREFIX-->members m \
					  LEFT JOIN <!--SPHINX_DB_PREFIX-->permission_index pi ON ( pi.perm_type_id=1 AND pi.perm_type='profile_view' ) \
					  WHERE m.member_id <= ( SELECT cs_value FROM <!--SPHINX_DB_PREFIX-->cache_store WHERE cs_key='sphinx_members_counter' )
	
	# Fields	
	sql_attr_uint			= search_id
	sql_attr_uint			= friend_only
	sql_attr_uint			= owner_only
	sql_attr_timestamp		= joined
	sql_attr_multi			= uint perm_view from field
	sql_attr_multi			= uint authorized_users from field
	
	sql_ranged_throttle	= 0
}

source members_search_delta : members_search_main
{
	# Override the base sql_query_pre
	sql_query_pre	=
	
	# Query posts for the main source
	sql_query		= SELECT m.member_id, m.member_id as search_id, m.member_group_id, m.email, m.joined, m.members_display_name, m.name, \
							 REPLACE( pi.perm_view, '*', 0 ) as perm_view, \
							 CASE WHEN pi.authorized_users IS NULL THEN 0 ELSE pi.authorized_users END AS authorized_users, \
							 CASE WHEN pi.friend_only=0 THEN 0 ELSE m.member_id END AS friend_only, \
							 CASE WHEN pi.owner_only=0 THEN 0 ELSE m.member_id END AS owner_only \
					  FROM <!--SPHINX_DB_PREFIX-->members m \
					  LEFT JOIN <!--SPHINX_DB_PREFIX-->permission_index pi ON ( pi.perm_type_id=1 AND pi.perm_type='profile_view' ) \
					  WHERE m.member_id > ( SELECT cs_value FROM <!--SPHINX_DB_PREFIX-->cache_store WHERE cs_key='sphinx_members_counter' )
}

index members_search_main
{
	source			= members_search_main
	path			= <!--SPHINX_BASE_PATH-->/members_search_main
	
	docinfo			= extern
	mlock			= 0
	morphology		= none
	min_word_len	= 2
	charset_type	= sbcs
	html_strip		= 0	
}

index members_search_delta : members_search_main
{
   source			= members_search_delta
   path				= <!--SPHINX_BASE_PATH-->/members_search_delta
}


EOF;
