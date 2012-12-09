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
 * @package		Calendar
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

############################# --- CALENDAR --- ##############################

source calendar_search_main : ipb_source_config
{
	# Set our forum PID counter
	sql_query_pre	= REPLACE INTO <!--SPHINX_DB_PREFIX-->cache_store VALUES( 'sphinx_calendar_counter', (SELECT max(event_id) FROM <!--SPHINX_DB_PREFIX-->cal_events), '', 0, UNIX_TIMESTAMP() )
	
	# Query posts for the main source
	sql_query		= SELECT e.*, e.event_id as search_id, \
							 REPLACE( pi.perm_view, '*', 0 ) as perm_view, \
							 CASE WHEN pi.authorized_users IS NULL THEN 0 ELSE pi.authorized_users END AS authorized_users, \
							 CASE WHEN pi.friend_only=0 THEN 0 ELSE e.event_member_id END AS friend_only, \
							 CASE WHEN pi.owner_only=0 THEN 0 ELSE e.event_member_id END AS owner_only \
					  FROM <!--SPHINX_DB_PREFIX-->cal_events e \
					  LEFT JOIN <!--SPHINX_DB_PREFIX-->permission_index pi ON ( pi.perm_type_id=e.event_calendar_id AND pi.perm_type='calendar' ) \
					  WHERE e.event_id <= ( SELECT cs_value FROM <!--SPHINX_DB_PREFIX-->cache_store WHERE cs_key='sphinx_calendar_counter' )
	
	# Fields	
	sql_attr_uint			= search_id
	sql_attr_uint			= friend_only
	sql_attr_uint			= owner_only
	sql_attr_timestamp		= event_unix_from
	sql_attr_multi			= uint perm_view from field
	sql_attr_multi			= uint authorized_users from field
	sql_attr_uint			= event_member_id
	
	sql_ranged_throttle	= 0
}

source calendar_search_delta : calendar_search_main
{
	# Override the base sql_query_pre
	sql_query_pre	= 
	
	# Query posts for the main source
	sql_query		= SELECT e.*, e.event_id as search_id, \
							 REPLACE( pi.perm_view, '*', 0 ) as perm_view, \
							 CASE WHEN pi.authorized_users IS NULL THEN 0 ELSE pi.authorized_users END AS authorized_users, \
							 CASE WHEN pi.friend_only=0 THEN 0 ELSE e.event_member_id END AS friend_only, \
							 CASE WHEN pi.owner_only=0 THEN 0 ELSE e.event_member_id END AS owner_only \
					  FROM <!--SPHINX_DB_PREFIX-->cal_events e \
					  LEFT JOIN <!--SPHINX_DB_PREFIX-->permission_index pi ON ( pi.perm_type_id=e.event_calendar_id AND pi.perm_type='calendar' ) \
					  WHERE e.event_id > ( SELECT cs_value FROM <!--SPHINX_DB_PREFIX-->cache_store WHERE cs_key='sphinx_calendar_counter' )
}

index calendar_search_main
{
	source			= calendar_search_main
	path			= <!--SPHINX_BASE_PATH-->/calendar_search_main
	
	docinfo			= extern
	mlock			= 0
	morphology		= none
	min_word_len	= 2
	charset_type	= sbcs
	html_strip		= 0	
}

index calendar_search_delta : calendar_search_main
{
   source			= calendar_search_delta
   path				= <!--SPHINX_BASE_PATH-->/calendar_search_delta
}



EOF;
