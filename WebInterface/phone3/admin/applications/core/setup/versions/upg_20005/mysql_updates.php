<?php

/*
+--------------------------------------------------------------------------
|   IP.Board v3.0.3
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2004 Invision Power Services
|   http://www.
|   ========================================
|   Web: http://www.
|   Email: matt@
|   Licence Info: http://www./?license
+---------------------------------------------------------------------------
|
|   > IPB UPGRADE MODULE:: IPB 2.0.0 PDR1 -> PDR 2
|   > Script written by Matt Mecham
|   > Date started: 23rd April 2004
|   > "So what, pop is dead - it's no great loss.
	   So many facelifts, it's face flew off"
+--------------------------------------------------------------------------
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

$SQL = array();

$SQL[] = "INSERT INTO ibf_task_manager (task_title, task_file, task_next_run, task_week_day, task_month_day, task_hour, task_minute, task_cronkey, task_log, task_description, task_enabled, task_key, task_safemode) VALUES ('Send Bulk Mail', 'bulkmail.php', 1086706080, -1, -1, -1, -1, '61359ac93eb93ebbd935a4e275ade2db', 0, 'Dynamically assigned, no need to edit or change', 0, 'bulkmail', 1);";
$SQL[] = "INSERT INTO ibf_task_manager (task_title, task_file, task_next_run, task_week_day, task_month_day, task_hour, task_minute, task_cronkey, task_log, task_description, task_enabled, task_key, task_safemode) VALUES ('Daily Topic &amp; Forum Digest', 'dailydigest.php', 1086912600, -1, -1, 0, 10, '723cab2aae32dd5d04898b1151038846', 1, 'Emails out daily topic &amp; forum digest emails', 1, 'dailydigest', 0);";
$SQL[] = "INSERT INTO ibf_task_manager (task_title, task_file, task_next_run, task_week_day, task_month_day, task_hour, task_minute, task_cronkey, task_log, task_description, task_enabled, task_key, task_safemode) VALUES ('Weekly Topic &amp; Forum Digest', 'weeklydigest.php', 1087096200, 0, -1, 3, 10, '7e7fccd07f781bdb24ac108d26612931', 1, 'Emails weekly topic &amp; forum digest emails', 1, 'weeklydigest', 0);";

	
	
?>