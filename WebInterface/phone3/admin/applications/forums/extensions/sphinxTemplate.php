<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Sphinx template file
 * Last Updated: $Date: 2009-08-12 18:07:52 -0400 (Wed, 12 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Forums
 * @link		http://www.
 * @version		$Rev: 5013 $
 * @since		3.0.0
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

$appSphinxTemplate	= <<<EOF

################################# --- FORUM --- ##############################
source forums_search_posts_main : ipb_source_config
{
	# Set our forum PID counter
	sql_query_pre	= REPLACE INTO <!--SPHINX_DB_PREFIX-->cache_store VALUES( 'sphinx_forums_counter_posts', (SELECT max(pid) FROM <!--SPHINX_DB_PREFIX-->posts), '', 0, UNIX_TIMESTAMP() )
	
	# Query posts for the main source
	sql_query		= SELECT p.pid, p.pid as search_id, p.author_id, p.post_date, p.post, p.topic_id, p.queued, \
							 t.tid, t.forum_id, t.approved, \
							 REPLACE( pi.perm_view, '*', 0 ) as perm_view, \
							 CASE WHEN pi.authorized_users IS NULL OR pi.authorized_users='' THEN 0 ELSE pi.authorized_users END AS authorized_users, \
							 CASE WHEN pi.friend_only=0 THEN 0 ELSE p.author_id END AS friend_only, \
							 CASE WHEN pi.owner_only=0 THEN 0 ELSE p.author_id END AS owner_only, \
							 CASE WHEN f.password <> '' THEN 1 ELSE 0 END AS password \
					  FROM <!--SPHINX_DB_PREFIX-->posts p \
					  LEFT JOIN <!--SPHINX_DB_PREFIX-->topics t ON ( p.topic_id=t.tid ) \
					  LEFT JOIN <!--SPHINX_DB_PREFIX-->forums f ON ( t.forum_id=f.id ) \
					  LEFT JOIN <!--SPHINX_DB_PREFIX-->permission_index pi ON ( pi.perm_type_id=t.forum_id AND pi.perm_type='forum' )
	
	# Fields	
	sql_attr_bool			= queued
	sql_attr_bool			= approved
	sql_attr_uint			= search_id
	sql_attr_uint			= friend_only
	sql_attr_uint			= forum_id
	sql_attr_uint			= owner_only
	sql_attr_timestamp		= post_date
	sql_attr_multi			= uint perm_view from field
	sql_attr_multi			= uint authorized_users from field
	sql_attr_bool			= password
	sql_attr_uint			= author_id
	sql_attr_uint			= tid
	
	sql_ranged_throttle	= 0
}

source forums_search_topics_main : ipb_source_config
{
	# Set our forum PID counter
	sql_query_pre	= REPLACE INTO <!--SPHINX_DB_PREFIX-->cache_store VALUES( 'sphinx_forums_counter_topics', (SELECT max(tid) FROM <!--SPHINX_DB_PREFIX-->topics), '', 0, UNIX_TIMESTAMP() )
	
	# Query posts for the main source
	sql_query		= SELECT t.tid, t.tid as search_id, t.forum_id, t.approved, t.title, t.last_post, t.last_poster_id as author_id, \
							 REPLACE( pi.perm_view, '*', 0 ) as perm_view, \
							 CASE WHEN pi.authorized_users IS NULL OR pi.authorized_users='' THEN 0 ELSE pi.authorized_users END AS authorized_users, \
							 CASE WHEN pi.friend_only=0 THEN 0 ELSE t.last_poster_id END AS friend_only, \
							 CASE WHEN pi.owner_only=0 THEN 0 ELSE t.last_poster_id END AS owner_only, \
							 CASE WHEN f.password <> '' THEN 1 ELSE 0 END AS password \
					  FROM <!--SPHINX_DB_PREFIX-->topics t \
					  LEFT JOIN <!--SPHINX_DB_PREFIX-->forums f ON ( t.forum_id=f.id ) \
					  LEFT JOIN <!--SPHINX_DB_PREFIX-->permission_index pi ON ( pi.perm_type_id=t.forum_id AND pi.perm_type='forum' )
	
	# Fields	
	sql_attr_bool			= approved
	sql_attr_uint			= search_id
	sql_attr_uint			= friend_only
	sql_attr_uint			= forum_id
	sql_attr_uint			= owner_only
	sql_attr_timestamp		= last_post
	sql_attr_multi			= uint perm_view from field
	sql_attr_multi			= uint authorized_users from field
	sql_attr_bool			= password
	sql_attr_uint			= author_id
	
	sql_ranged_throttle	= 0
}

source forums_search_posts_delta : forums_search_posts_main
{
	# Override the base sql_query_pre
	sql_query_pre = 
	
	# Query posts for the delta source
	sql_query		= SELECT p.pid, p.pid as search_id, p.author_id, p.post_date, p.post, p.topic_id, p.queued, \
							 t.tid, t.forum_id, t.approved, t.title, \
							 REPLACE( pi.perm_view, '*', 0 ) as perm_view, \
							 CASE WHEN pi.authorized_users IS NULL OR pi.authorized_users='' THEN 0 ELSE pi.authorized_users END AS authorized_users, \
							 CASE WHEN pi.friend_only=0 THEN 0 ELSE p.author_id END AS friend_only, \
							 CASE WHEN pi.owner_only=0 THEN 0 ELSE p.author_id END AS owner_only, \
							 CASE WHEN f.password <> '' THEN 1 ELSE 0 END AS password \
					  FROM <!--SPHINX_DB_PREFIX-->posts p \
					  LEFT JOIN <!--SPHINX_DB_PREFIX-->topics t ON ( p.topic_id=t.tid ) \
					  LEFT JOIN <!--SPHINX_DB_PREFIX-->forums f ON ( t.forum_id=f.id ) \
					  LEFT JOIN <!--SPHINX_DB_PREFIX-->permission_index pi ON ( pi.perm_type_id=t.forum_id AND pi.perm_type='forum' ) \
					  WHERE p.pid > ( SELECT cs_value FROM <!--SPHINX_DB_PREFIX-->cache_store WHERE cs_key='sphinx_forums_counter_posts' )
}

source forums_search_topics_delta : forums_search_topics_main
{
	# Override the base sql_query_pre
	sql_query_pre = 
	
	# Query posts for the delta source
	sql_query		= SELECT t.tid, t.tid as search_id, t.forum_id, t.approved, t.title, t.last_post, t.last_poster_id as author_id, \
							 REPLACE( pi.perm_view, '*', 0 ) as perm_view, \
							 CASE WHEN pi.authorized_users IS NULL OR pi.authorized_users='' THEN 0 ELSE pi.authorized_users END AS authorized_users, \
							 CASE WHEN pi.friend_only=0 THEN 0 ELSE t.last_poster_id END AS friend_only, \
							 CASE WHEN pi.owner_only=0 THEN 0 ELSE t.last_poster_id END AS owner_only, \
							 CASE WHEN f.password <> '' THEN 1 ELSE 0 END AS password \
					  FROM <!--SPHINX_DB_PREFIX-->topics t \
					  LEFT JOIN <!--SPHINX_DB_PREFIX-->forums f ON ( t.forum_id=f.id ) \
					  LEFT JOIN <!--SPHINX_DB_PREFIX-->permission_index pi ON ( pi.perm_type_id=t.forum_id AND pi.perm_type='forum' ) \
					  WHERE t.tid > ( SELECT cs_value FROM <!--SPHINX_DB_PREFIX-->cache_store WHERE cs_key='sphinx_forums_counter_topics' )
}

index forums_search_posts_main
{
	source			= forums_search_posts_main
	path			= <!--SPHINX_BASE_PATH-->/forums_search_posts_main
	
	docinfo			= extern
	mlock			= 0
	morphology		= none
	min_word_len	= 2
	charset_type	= sbcs
	html_strip		= 0	
}

index forums_search_posts_delta : forums_search_posts_main
{
   source			= forums_search_posts_delta
   path				= <!--SPHINX_BASE_PATH-->/forums_search_posts_delta
}

index forums_search_topics_main
{
	source			= forums_search_topics_main
	path			= <!--SPHINX_BASE_PATH-->/forums_search_topics_main
	
	docinfo			= extern
	mlock			= 0
	morphology		= none
	min_word_len	= 2
	charset_type	= sbcs
	html_strip		= 0	
}

index forums_search_topics_delta : forums_search_topics_main
{
   source			= forums_search_topics_delta
   path				= <!--SPHINX_BASE_PATH-->/forums_search_topics_delta
}


EOF;
