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
|   > IPB UPGRADE 1.1 -> 2.0 SQL STUFF!
|   > Script written by Matt Mecham
|   > Date started: 21st April 2004
|   > Interesting fact: Turin Brakes are also good
+--------------------------------------------------------------------------
*/

class version_upgrade
{
	/**
	 * Custom HTML to show
	 *
	 * @access	private
	 * @var		string
	 */
	private $_output = '';

	/**
	* fetchs output
	*
	* @access	public
	* @return	string
	*/
	public function fetchOutput()
	{
		return $this->_output;
	}

	/**
	 * Execute selected method
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @return	void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Make object */
		$this->registry =  $registry;
		$this->DB       =  $this->registry->DB();
		$this->settings =& $this->registry->fetchSettings();
		$this->request  =& $this->registry->fetchRequest();
		$this->cache    =  $this->registry->cache();
		$this->caches   =& $this->registry->cache()->fetchCaches();

		//--------------------------------
		// What are we doing?
		//--------------------------------

		switch( $this->request['workact'] )
		{
			case 'step_1':
				$this->step_1();
				break;
			case 'step_2':
				$this->step_2();
				break;
			case 'step_3':
				$this->step_3();
				break;
			case 'step_4':
				$this->step_4();
				break;
			case 'step_5':
				$this->step_5();
				break;
			case 'step_6':
				$this->step_6();
				break;
			case 'step_7':
				$this->step_7();
				break;
			case 'step_8':
				$this->step_8();
				break;
			case 'step_9':
				$this->step_9();
				break;
			case 'step_10':
				$this->step_10();
				break;
			case 'step_11':
				$this->step_11();
				break;
			case 'step_12':
				$this->step_12();
				break;
			case 'step_13':
				$this->step_13();
				break;
			case 'step_14':
				$this->step_14();
				break;
			case 'step_15':
				$this->step_15();
				break;
			case 'step_16':
				$this->step_16();
				break;
			case 'step_17':
				$this->step_17();
				break;
			case 'step_18':
				$this->step_18();
				break;
			case 'step_19':
				$this->step_19();
				break;
			case 'step_20':
				$this->step_20();
				break;
			case 'step_21':
				$this->step_21();
				break;

			default:
				$this->step_1();
				break;
		}
		if ( $this->request['workact'] )
		{
			return false;
		}
		else
		{
			return true;
		}
	}

	/*-------------------------------------------------------------------------*/
	// STEP 1: COPY AND POPULATE BACK UP FORUMS TABLE
	/*-------------------------------------------------------------------------*/

	function step_1()
	{
		$this->request['st'] = 0;

		$table = $this->DB->getTableSchematic( 'forums' );

		$SQL[] = str_replace( ipsRegistry::dbFunctions()->getPrefix()."forums", ipsRegistry::dbFunctions()->getPrefix()."forums_bak", $table['Create Table'] );

		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."forums_bak SELECT * FROM ".ipsRegistry::dbFunctions()->getPrefix()."forums";

		$this->error   = array();
		$this->sqlcount 		= 0;

		$this->DB->return_die = 1;

		foreach( $SQL as $query )
		{
			$this->DB->allow_sub_select 	= 1;
			$this->DB->error				= '';

			$this->DB->query( $query );

			if ( $this->DB->error )
			{
				$this->registry->output->addError( $query."<br /><br />".$this->DB->error );
			}
			else
			{
				$this->sqlcount++;
			}
		}

		//-----------------------------------------
		// Check...
		//-----------------------------------------

		$this->DB->query( "SELECT COUNT(*) as count FROM ".ipsRegistry::dbFunctions()->getPrefix()."forums_bak" );
		$count = $this->DB->fetch();

		if ( intval( $count['count'] ) < 1 )
		{
			$this->registry->output->addError( "The back-up forums table has not been populated successfully. Continuing this convert WILL delete all forums permanently. Contact technical support immediately." );
		}

		$this->registry->output->addMessage("Forums table backed up - creating new tables next....<br /><br />$this->sqlcount queries run....");
		$this->request['workact'] = 'step_2';
	}


	/*-------------------------------------------------------------------------*/
	// STEP 2: DROP FORUMS TABLE, CREATE NEW TABLES
	/*-------------------------------------------------------------------------*/

	function step_2()
	{
		$SQL[] = "DROP TABLE if exists ".ipsRegistry::dbFunctions()->getPrefix()."attachments;";
		$SQL[] = "DROP TABLE if exists ".ipsRegistry::dbFunctions()->getPrefix()."announcements;";

		$SQL[] = "CREATE TABLE ".ipsRegistry::dbFunctions()->getPrefix()."attachments (
		   attach_id int(10) NOT NULL auto_increment,
		   attach_ext varchar(10) NOT NULL default '',
		   attach_file varchar(250) NOT NULL default '',
		   attach_location varchar(250) NOT NULL default '',
		   attach_thumb_location varchar(250) NOT NULL default '',
		   attach_thumb_width smallint(5) NOT NULL default '0',
		   attach_thumb_height smallint(5) NOT NULL default '0',
		   attach_is_image tinyint(1) NOT NULL default '0',
		   attach_hits int(10) NOT NULL default '0',
		   attach_date int(10) NOT NULL default '0',
		   attach_temp tinyint(1) NOT NULL default '0',
		   attach_pid int(10) NOT NULL default '0',
		   attach_post_key varchar(32) NOT NULL default '0',
		   attach_msg int(10) NOT NULL default '0',
		   attach_member_id mediumint(8) NOT NULL default '0',
		   attach_approved int(10) NOT NULL default '1',
		   attach_filesize int(10) NOT NULL default '0',
		PRIMARY KEY (attach_id),
		KEY attach_pid (attach_pid),
		KEY attach_msg (attach_msg),
		KEY attach_post_key (attach_post_key),
		KEY attach_mid_size (attach_member_id, attach_filesize)
	   );";

	   $SQL[] = "CREATE TABLE ".ipsRegistry::dbFunctions()->getPrefix()."message_text (
		 msg_id int(10) NOT NULL auto_increment,
		 msg_date int(10) default 0,
		 msg_post text NULL,
		 msg_cc_users text NULL,
		 msg_sent_to_count smallint(5) NOT NULL default '0',
		 msg_deleted_count smallint(5) NOT NULL default '0',
		 msg_post_key varchar(32) NOT NULL default '0',
		 msg_author_id mediumint(8) NOT NULL default '0',
		PRIMARY KEY (msg_id),
		KEY msg_date (msg_date),
		KEY msg_sent_to_count (msg_sent_to_count),
		KEY msg_deleted_count (msg_deleted_count)
		);";

	   $SQL[] = "CREATE TABLE ".ipsRegistry::dbFunctions()->getPrefix()."message_topics (
		   mt_id int(10) NOT NULL auto_increment,
		   mt_msg_id int(10) NOT NULL default '0',
		   mt_date int(10) NOT NULL default '0',
		   mt_title varchar(255) NOT NULL default '',
		   mt_from_id mediumint(8) NOT NULL default '0',
		   mt_to_id mediumint(8) NOT NULL default '0',
		   mt_owner_id mediumint(8) NOT NULL default '0',
		   mt_vid_folder varchar(32) NOT NULL default '',
		   mt_read tinyint(1) NOT NULL default '0',
		   mt_hasattach smallint(5) NOT NULL default '0',
		   mt_hide_cc tinyint(1) default '0',
		   mt_tracking tinyint(1) default '0',
		   mt_user_read int(10) default '0',
		PRIMARY KEY (mt_id),
		KEY mt_from_id (mt_from_id),
		KEY mt_owner_id (mt_owner_id, mt_to_id, mt_vid_folder)
		);";

	   $SQL[] = "CREATE TABLE ".ipsRegistry::dbFunctions()->getPrefix()."skin_sets (
		   set_skin_set_id int(10) NOT NULL auto_increment,
		   set_name varchar(150) NOT NULL default '',
		   set_image_dir varchar(200) NOT NULL default '',
		   set_hidden tinyint(1) NOT NULL default '0',
		   set_default tinyint(1) NOT NULL default '0',
		   set_css_method varchar(100) NOT NULL default 'inline',
		   set_skin_set_parent smallint(5) NOT NULL default '-1',
		   set_author_email varchar(255) NOT NULL default '',
		   set_author_name varchar(255) NOT NULL default '',
		   set_author_url varchar(255) NOT NULL default '',
		   set_css mediumtext NULL,
		   set_wrapper mediumtext NULL,
		   set_css_updated int(10) NOT NULL default '0',
		   set_cache_css mediumtext NULL,
		   set_cache_macro mediumtext NULL,
		   set_cache_wrapper mediumtext NULL,
		   set_emoticon_folder varchar(60) NOT NULL default 'default',
		   PRIMARY KEY(set_skin_set_id)
	   );";

	   $SQL[] = "CREATE TABLE ".ipsRegistry::dbFunctions()->getPrefix()."skin_templates_cache (
		   template_id varchar(32) NOT NULL default '',
		   template_group_name varchar(255) NOT NULL default '',
		   template_group_content mediumtext NULL,
		   template_set_id int(10) NOT NULL default '0',
		   primary key (template_id),
		   KEY template_set_id (template_set_id),
		   KEY template_group_name (template_group_name)
	   );";


	   $SQL[] = "CREATE TABLE ".ipsRegistry::dbFunctions()->getPrefix()."mail_queue(
		   mail_id int(10) auto_increment NOT NULL,
		   mail_date int(10) NOT NULL default '0',
		   mail_to varchar(255) NOT NULL default '',
		   mail_from varchar(255) NOT NULL default '',
		   mail_subject text NULL,
		   mail_content text NULL,
		   mail_type varchar(200) NOT NULL default '',
		   PRIMARY KEY (mail_id)
	   );";

	   $SQL[] = "CREATE TABLE ".ipsRegistry::dbFunctions()->getPrefix()."task_manager (
		   task_id int(10) auto_increment NOT NULL,
		   task_title varchar(255) NOT NULL default '',
		   task_file varchar(255) NOT NULL default '',
		   task_next_run int(10) NOT NULL default '0',
		   task_week_day tinyint(1) NOT NULL default '-1',
		   task_month_day smallint(2) NOT NULL default '-1',
		   task_hour smallint(2) NOT NULL default '-1',
		   task_minute smallint(2) NOT NULL default '-1',
		   task_cronkey varchar(32) NOT NULL default '',
		   task_log tinyint(1) NOT NULL default '0',
		   task_description text NULL,
		   task_enabled tinyint(1) NOT NULL default '1',
		   task_key varchar(30) NOT NULL default '',
		   task_safemode tinyint(1) NOT NULL default '0',
		   PRIMARY KEY(task_id),
		   KEY task_next_run (task_next_run)
	   );";

	   $SQL[] = "CREATE TABLE ".ipsRegistry::dbFunctions()->getPrefix()."task_logs (
		   log_id int(10) auto_increment NOT NULL,
		   log_title varchar(255) NOT NULL default '',
		   log_date int(10) NOT NULL default '0',
		   log_ip varchar(16) NOT NULL default '0',
		   log_desc text NULL,
		   PRIMARY KEY(log_id)
	   );";

	   $SQL[] = "CREATE TABLE ".ipsRegistry::dbFunctions()->getPrefix()."custom_bbcode (
		   bbcode_id int(10) NOT NULL auto_increment,
		   bbcode_title varchar(255) NOT NULL default '',
		   bbcode_desc text NULL,
		   bbcode_tag varchar(255) NOT NULL default '',
		   bbcode_replace text NULL,
		   bbcode_useoption tinyint(1) NOT NULL default '0',
		   bbcode_example text NULL,
		   PRIMARY KEY (bbcode_id)
	   );";

	   $SQL[] = "CREATE TABLE ".ipsRegistry::dbFunctions()->getPrefix()."conf_settings (
		   conf_id int(10) NOT NULL auto_increment,
		   conf_title varchar(255) NOT NULL default '',
		   conf_description text NULL,
		   conf_group smallint(3) NOT NULL default '0',
		   conf_type varchar(255) NOT NULL default '',
		   conf_key varchar(255) NOT NULL default '',
		   conf_value text NULL,
		   conf_default text NULL,
		   conf_extra text NULL,
		   conf_evalphp text NULL,
		   conf_protected tinyint(1) NOT NULL default '0',
		   conf_position smallint(3) NOT NULL default '0',
		   conf_start_group varchar(255) NOT NULL default '',
		   conf_end_group tinyint(1) NOT NULL default '0',
		   conf_help_key varchar(255) NOT NULL default '0',
		   conf_add_cache tinyint(1) NOT NULL default '1',
		   PRIMARY KEY (conf_id)
	   );";

	   $SQL[] = "CREATE TABLE ".ipsRegistry::dbFunctions()->getPrefix()."conf_settings_titles (
		   conf_title_id smallint(3) NOT NULL auto_increment,
		   conf_title_title varchar(255) NOT NULL default '',
		   conf_title_desc text NULL,
		   conf_title_count smallint(3) NOT NULL default '0',
		   conf_title_noshow tinyint(1) NOT NULL default '0',
		   conf_title_keyword varchar(200) NOT NULL default '',
		   PRIMARY KEY(conf_title_id)
	   );";

	   $SQL[] = "CREATE TABLE ".ipsRegistry::dbFunctions()->getPrefix()."topics_read (
		 read_tid int(10) NOT NULL default '0',
		 read_mid mediumint(8) NOT NULL default '0',
		 read_date int(10) NOT NULL default '0',
		 UNIQUE KEY read_tid_mid( read_tid, read_mid )
	   );";

	   $SQL[] = "CREATE TABLE ".ipsRegistry::dbFunctions()->getPrefix()."banfilters (
		   ban_id int(10) NOT NULL auto_increment,
		   ban_type varchar(10) NOT NULL default 'ip',
		   ban_content text NULL,
		   ban_date int(10) NOT NULL default '0',
		   PRIMARY KEY (ban_id)
	   );";

	   $SQL[] = "CREATE TABLE ".ipsRegistry::dbFunctions()->getPrefix()."attachments_type (
		   atype_id int(10) NOT NULL auto_increment,
		   atype_extension varchar(18) NOT NULL default '',
		   atype_mimetype varchar(255) NOT NULL default '',
		   atype_post tinyint(1) NOT NULL default '1',
		   atype_photo tinyint(1) NOT NULL default '0',
		   atype_img text NULL,
		   PRIMARY KEY (atype_id)
	   );";

	   $SQL[] = "CREATE TABLE ".ipsRegistry::dbFunctions()->getPrefix()."members_converge (
		   converge_id int(10) auto_increment NOT NULL,
		   converge_email varchar(250) NOT NULL default '',
		   converge_joined int(10) NOT NULL default '0',
		   converge_pass_hash varchar(32) NOT NULL default '',
		   converge_pass_salt varchar(5) NOT NULL default '',
		   PRIMARY KEY( converge_id )
	   );";

	   $SQL[] = "CREATE TABLE ".ipsRegistry::dbFunctions()->getPrefix()."announcements (
		announce_id int(10) UNSIGNED NOT NULL auto_increment,
		announce_title varchar(255) NOT NULL default '',
		announce_post text NULL,
		announce_forum text NULL,
		announce_member_id mediumint(8) UNSIGNED NOT NULL default '0',
		announce_html_enabled tinyint(1) NOT NULL default '0',
		announce_views int(10) UNSIGNED NOT NULL default '0',
		announce_start int(10) UNSIGNED NOT NULL default '0',
		announce_end int(10) UNSIGNED NOT NULL default '0',
		announce_active tinyint(1) NOT NULL default '1',
	   PRIMARY KEY (announce_id)
	   );";

	   $SQL[] = "CREATE TABLE ".ipsRegistry::dbFunctions()->getPrefix()."mail_error_logs (
		   mlog_id int(10) auto_increment NOT NULL,
		   mlog_date int(10) NOT NULL default '0',
		   mlog_to varchar(250) NOT NULL default '',
		   mlog_from varchar(250) NOT NULL default '',
		   mlog_subject varchar(250) NOT NULL default '',
		   mlog_content varchar(250) NOT NULL default '',
		   mlog_msg text NULL,
		   mlog_code varchar(200) NOT NULL default '',
		   mlog_smtp_msg text NULL,
		   PRIMARY KEY (mlog_id)
	   );";

	   $SQL[] = "CREATE TABLE ".ipsRegistry::dbFunctions()->getPrefix()."bulk_mail (
		   mail_id int(10) NOT NULL auto_increment,
		   mail_subject varchar(255) NOT NULL default '',
		   mail_content mediumtext NULL,
		   mail_groups mediumtext NULL,
		   mail_honor tinyint(1) NOT NULL default '1',
		   mail_opts mediumtext NULL,
		   mail_start int(10) NOT NULL default '0',
		   mail_updated int(10) NOT NULL default '0',
		   mail_sentto int(10) NOT NULL default '0',
		   mail_active tinyint(1) NOT NULL default '0',
		   mail_pergo smallint(5) NOT NULL default '0',
		   PRIMARY KEY (mail_id)
	   );";

	   /*$SQL[] = "CREATE TABLE ".ipsRegistry::dbFunctions()->getPrefix()."upgrade_history (
		   upgrade_id int(10) NOT NULL auto_increment,
		   upgrade_version_id int(10) NOT NULL default '0',
		   upgrade_version_human varchar(200) NOT NULL default '',
		   upgrade_date int(10) NOT NULL default '0',
		   upgrade_mid int(10) NOT NULL default '0',
		   upgrade_notes text NULL,
		   PRIMARY KEY (upgrade_id)
	   );";*/

	   $SQL[] = "DROP TABLE ".ipsRegistry::dbFunctions()->getPrefix()."forums;";

	   $SQL[] = "CREATE TABLE ".ipsRegistry::dbFunctions()->getPrefix()."forums (
				 id smallint(5) NOT NULL default '0',
				 topics mediumint(6) default '0',
				 posts mediumint(6) default '0',
				 last_post int(10) default NULL,
				 last_poster_id mediumint(8) NOT NULL default '0',
				 last_poster_name varchar(32) default NULL,
				 name varchar(128) NOT NULL default '',
				 description text NULL,
				 position tinyint(2) default NULL,
				 use_ibc tinyint(1) default NULL,
				 use_html tinyint(1) default NULL,
				 status varchar(10) default NULL,
				 password varchar(32) default NULL,
				 last_title varchar(128) default NULL,
				 last_id int(10) default NULL,
				 sort_key varchar(32) default NULL,
				 sort_order varchar(32) default NULL,
				 prune tinyint(3) default NULL,
				 show_rules tinyint(1) default NULL,
				 preview_posts tinyint(1) default NULL,
				 allow_poll tinyint(1) NOT NULL default '1',
				 allow_pollbump tinyint(1) NOT NULL default '0',
				 inc_postcount tinyint(1) NOT NULL default '1',
				 skin_id int(10) default NULL,
				 parent_id mediumint(5) default '-1',
				 sub_can_post tinyint(1) default '1',
				 quick_reply tinyint(1) default '0',
				 redirect_url varchar(250) default '',
				 redirect_on tinyint(1) NOT NULL default '0',
				 redirect_hits int(10) NOT NULL default '0',
				 redirect_loc varchar(250) default '',
				 rules_title varchar(255) NOT NULL default '',
				 rules_text text NULL,
				 topic_mm_id varchar(250) NOT NULL default '',
				 notify_modq_emails text NULL,
				 permission_custom_error text NULL,
				 permission_array mediumtext NULL,
				 permission_showtopic tinyint(1) NOT NULL default '0',
				 queued_topics mediumint(6) NOT NULL default '0',
				 queued_posts  mediumint(6) NOT NULL default '0',
				 PRIMARY KEY  (id),
				 KEY position (position, parent_id)
			   );";


		$this->sqlcount 		= 0;
		$output					= "";

		$this->DB->return_die = 1;

		foreach( $SQL as $query )
		{
			$this->DB->allow_sub_select 	= 1;
			$this->DB->error				= '';

			if( IPSSetUp::getSavedData('man') )
			{
				$output .= preg_replace("/\sibf_(\S+?)([\s\.,]|$)/", " ".$this->DB->obj['sql_tbl_prefix']."\\1\\2", preg_replace( "/\s{1,}/", " ", $query ) )."\n\n";
			}
			else
			{
				$this->DB->query( $query );

				if ( $this->DB->error )
				{
					$this->registry->output->addError( $query."<br /><br />".$this->DB->error );
				}
				else
				{
					$this->sqlcount++;
				}
			}
		}

		$this->registry->output->addMessage("New tables created. Altering tables (Part 1, section 1 - post table)<br /><br />$this->sqlcount queries run....");
		$this->request['workact'] = 'step_3';

		if ( IPSSetUp::getSavedData('man') AND $output )
		{
			$this->_output = $this->registry->output->template()->upgrade_manual_queries( $output );
		}
	}


	/*-------------------------------------------------------------------------*/
	// STEP 3: ALTER POST TABLE
	/*-------------------------------------------------------------------------*/

	function step_3()
	{
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."posts add post_parent int(10) NOT NULL default '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."posts ADD post_key varchar(32) NOT NULL default '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."posts add post_htmlstate smallint(1) NOT NULL default '0';";

		$this->error   = array();
		$this->sqlcount 		= 0;
		$output					= "";

		$this->DB->return_die = 1;

		foreach( $SQL as $query )
		{
			$this->DB->allow_sub_select 	= 1;
			$this->DB->error				= '';

			if( IPSSetUp::getSavedData('man') )
			{
				$output .= preg_replace("/\sibf_(\S+?)([\s\.,]|$)/", " ".$this->DB->obj['sql_tbl_prefix']."\\1\\2", preg_replace( "/\s{1,}/", " ", $query ) )."\n\n";
			}
			else
			{
				$this->DB->query( $query );

				if ( $this->DB->error )
				{
					$this->registry->output->addError( $query."<br /><br />".$this->DB->error );
				}
				else
				{
					$this->sqlcount++;
				}
			}
		}

		$this->registry->output->addMessage("Post table altered, altering topic table next...<br /><br />$this->sqlcount queries run....");
		$this->request['workact'] = 'step_4';

		if ( IPSSetUp::getSavedData('man') AND $output )
		{
			$this->_output = $this->registry->output->template()->upgrade_manual_queries( $output );
		}
	}


	/*-------------------------------------------------------------------------*/
	// STEP 4: ALTER TOPIC TABLE
	/*-------------------------------------------------------------------------*/

	function step_4()
	{
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."topics ADD topic_hasattach smallint(5) NOT NULL default '0';";

		$this->error   = array();
		$this->sqlcount 		= 0;
		$output					= "";

		$this->DB->return_die = 1;

		foreach( $SQL as $query )
		{
			$this->DB->allow_sub_select 	= 1;
			$this->DB->error				= '';

			if( IPSSetUp::getSavedData('man') )
			{
				$output .= preg_replace("/\sibf_(\S+?)([\s\.,]|$)/", " ".$this->DB->obj['sql_tbl_prefix']."\\1\\2", preg_replace( "/\s{1,}/", " ", $query ) )."\n\n";
			}
			else
			{
				$this->DB->query( $query );

				if ( $this->DB->error )
				{
					$this->registry->output->addError( $query."<br /><br />".$this->DB->error );
				}
				else
				{
					$this->sqlcount++;
				}
			}
		}

		$this->registry->output->addMessage("Topic table altered, altering members table next...<br /><br />$this->sqlcount queries run....");
		$this->request['workact'] = 'step_5';

		if ( IPSSetUp::getSavedData('man') AND $output )
		{
			$this->_output = $this->registry->output->template()->upgrade_manual_queries( $output );
		}
	}


	/*-------------------------------------------------------------------------*/
	// STEP 5: ALTER MEMBERS TABLE
	/*-------------------------------------------------------------------------*/

	function step_5()
	{
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."members add login_anonymous varchar(3) NOT NULL default '0&0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."members add ignored_users text NULL;";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."members add mgroup_others varchar(255) NOT NULL default '';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."member_extra
		ADD aim_name varchar(40) NOT NULL default '',
		ADD icq_number int(15) NOT NULL default '0',
		ADD website varchar(250) NOT NULL default '',
		ADD yahoo varchar(40) NOT NULL default '',
		ADD interests text NULL,
		ADD msnname varchar(200) NOT NULL default '',
		ADD vdirs text NULL,
		ADD location varchar(250) NOT NULL default '',
		ADD signature text NULL,
		ADD avatar_location varchar(128) NOT NULL default '',
		ADD avatar_size varchar(9) NOT NULL default '',
		ADD avatar_type varchar(15) NOT NULL default 'local';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."members add member_login_key varchar(32) NOT NULL default '';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."members change password legacy_password varchar(32) NOT NULL default '';";

		$this->error   = array();
		$this->sqlcount 		= 0;
		$output					= "";

		$this->DB->return_die = 1;

		foreach( $SQL as $query )
		{
			$this->DB->allow_sub_select 	= 1;
			$this->DB->error				= '';

			if( IPSSetUp::getSavedData('man') )
			{
				$output .= preg_replace("/\sibf_(\S+?)([\s\.,]|$)/", " ".$this->DB->obj['sql_tbl_prefix']."\\1\\2", preg_replace( "/\s{1,}/", " ", $query ) )."\n\n";
			}
			else
			{
				$this->DB->query( $query );

				if ( $this->DB->error )
				{
					$this->registry->output->addError( $query."<br /><br />".$this->DB->error );
				}
				else
				{
					$this->sqlcount++;
				}
			}
		}

		$this->registry->output->addMessage("Members table altered, other tables next...<br /><br />$this->sqlcount queries run....");
		$this->request['workact'] = 'step_6';

		if ( IPSSetUp::getSavedData('man') AND $output )
		{
			$this->_output = $this->registry->output->template()->upgrade_manual_queries( $output );
		}
	}


	/*-------------------------------------------------------------------------*/
	// STEP 6: ALTER OTHER TABLES
	/*-------------------------------------------------------------------------*/

	function step_6()
	{
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."macro rename ibf_skin_macro;";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."skin_macro change can_remove macro_can_remove tinyint(1) default '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."groups add g_bypass_badwords tinyint(1) NOT NULL default '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."cache_store change cs_value cs_value mediumtext NULL;";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."cache_store add cs_array tinyint(1) NOT NULL default '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."sessions add in_error tinyint(1) NOT NULL default '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."topic_mmod add mm_forums text NULL;";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."groups change g_icon g_icon text NULL;";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."emoticons add emo_set varchar(64) NOT NULL default 'default';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."admin_sessions change ID session_id varchar(32) NOT NULL default '';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."admin_sessions change IP_ADDRESS session_ip_address varchar(32) NOT NULL default '';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."admin_sessions change MEMBER_NAME session_member_name varchar(250) NOT NULL default '';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."admin_sessions change MEMBER_ID session_member_id mediumint(8) NOT NULL default '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."admin_sessions change SESSION_KEY session_member_login_key varchar(32) NOT NULL default '';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."admin_sessions change LOCATION session_location varchar(64) NOT NULL default '';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."admin_sessions change LOG_IN_TIME session_log_in_time int(10) NOT NULL default '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."admin_sessions change RUNNING_TIME session_running_time int(10) NOT NULL default '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."forum_tracker add forum_track_type varchar(100) NOT NULL default 'delayed';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."tracker add topic_track_type varchar(100) NOT NULL default 'delayed';";

		$SQL[] = "delete from ".ipsRegistry::dbFunctions()->getPrefix()."members where id=0 LIMIT 1;";
		$SQL[] = "delete from ".ipsRegistry::dbFunctions()->getPrefix()."member_extra where id=0 limit 1;";

		$this->error   = array();
		$this->sqlcount = 0;

		$this->DB->return_die = 1;

		foreach( $SQL as $query )
		{
			$this->DB->allow_sub_select 	= 1;
			$this->DB->error				= '';

			$this->DB->query( $query );

			if ( $this->DB->error )
			{
				$this->registry->output->addError( $query."<br /><br />".$this->DB->error );
			}
			else
			{
				$this->sqlcount++;
			}
		}

		$this->registry->output->addMessage("Other tables altered, converting forums next...<br /><br />$this->sqlcount queries run....");
		$this->request['workact'] = 'step_7';
	}


	/*-------------------------------------------------------------------------*/
	// STEP 7: IMPORT FORUMS
	/*-------------------------------------------------------------------------*/

	function step_7()
	{
		$this->DB->return_die = 1;

		//-----------------------------------------
		// Convert existing forums
		//-----------------------------------------

		$o = $this->DB->query("SELECT * FROM ".ipsRegistry::dbFunctions()->getPrefix()."forums_bak ORDER BY id");

		while( $r = $this->DB->fetch( $o ) )
		{
			$perm_array = addslashes(serialize(array(
													  'start_perms'  => $r['start_perms'],
													  'reply_perms'  => $r['reply_perms'],
													  'read_perms'   => $r['read_perms'],
													  'upload_perms' => $r['upload_perms'],
													  'show_perms'   => $r['read_perms']
									)		  )     );

			$this->DB->insert( 'forums', array (
											  'id'                      => $r['id'],
											  'position'                => $r['position'],
											  'topics'                  => $r['topics'],
											  'posts'                   => $r['posts'],
											  'last_post'               => $r['last_post'],
											  'last_poster_id'          => $r['last_poster_id'],
											  'last_poster_name'        => $r['last_poster_name'],
											  'name'                    => $r['name'],
											  'description'             => $r['description'],
											  'use_ibc'                 => $r['use_ibc'],
											  'use_html'                => $r['use_html'],
											  'status'                  => $r['status'],
											  'password'                => $r['password'],
											  'last_id'                 => $r['last_id'],
											  'last_title'              => $r['last_title'],
											  'sort_key'                => $r['sort_key'],
											  'sort_order'              => $r['sort_order'],
											  'prune'                   => $r['prune'],
											  'show_rules'              => $r['show_rules'],
											  'preview_posts'           => $r['preview_posts'],
											  'allow_poll'              => $r['allow_poll'],
											  'allow_pollbump'          => $r['allow_pollbump'],
											  'inc_postcount'           => $r['inc_postcount'],
											  'parent_id'               => $r['parent_id'],
											  'sub_can_post'            => $r['sub_can_post'],
											  'quick_reply'             => $r['quick_reply'],
											  'redirect_on'             => $r['redirect_on'],
											  'redirect_hits'           => $r['redirect_hits'],
											  'redirect_url'            => $r['redirect_url'],
											  'redirect_loc'		    => $r['redirect_loc'],
											  'rules_title'			    => $r['rules_title'],
  											  'rules_text'			    => $r['rules_text'],
											  'notify_modq_emails'      => $r['notify_modq_emails'],
											  'permission_array'        => $perm_array,
											  'permission_showtopic'    => '',
											  'permission_custom_error' => '',
									)       );
		}

		//-----------------------------------------
		// Convert categories
		//-----------------------------------------

		$this->DB->query("SELECT MAX(id) as max FROM ".ipsRegistry::dbFunctions()->getPrefix()."forums");
		$max = $this->DB->fetch();

		$fid = $max['max'];

		$o = $this->DB->query("SELECT * FROM ".ipsRegistry::dbFunctions()->getPrefix()."categories WHERE id > 0");

		while( $r = $this->DB->fetch( $o ) )
		{
			$fid++;

			$perm_array = addslashes(serialize(array(
													  'start_perms'  => '*',
													  'reply_perms'  => '*',
													  'read_perms'   => '*',
													  'upload_perms' => '*',
													  'show_perms'   => '*',
									)		  )     );

			$this->DB->insert( 'forums', array(
											 'id'               => $fid,
											 'position'         => $r['position'],
											 'name'             => $r['name'],
											 'sub_can_post'     => 0,
											 'permission_array' => $perm_array,
											 'parent_id'        => -1,
						  )                );

			//-----------------------------------------
			// Update old categories
			//-----------------------------------------

			$n = $this->DB->query("SELECT id FROM ".ipsRegistry::dbFunctions()->getPrefix()."forums_bak WHERE category={$r['id']} AND parent_id = -1");

			$ids = array();

			while( $c = $this->DB->fetch($n) )
			{
				$ids[] = $c['id'];
			}

			if ( count($ids) )
			{
				$this->DB->update( 'forums', array( 'parent_id' => $fid ), 'id IN ('.implode(',',$ids).')' );
			}
		}

		$this->registry->output->addMessage("Forums converted, converting attachments next...<br /><br />$fid forums converted....");
		$this->request['workact'] = 'step_8';
	}


	/*-------------------------------------------------------------------------*/
	// STEP 8: CONVERT ATTACHMENTS
	/*-------------------------------------------------------------------------*/

	function step_8()
	{
		$this->DB->return_die = 1;

		$start = intval($this->request['st']) ? intval($this->request['st']) : 0;
		$lend  = 300;
		$end   = $start + $lend;

		//-----------------------------------------
		// In steps...
		//-----------------------------------------

		$this->DB->build( array( "select" => '*',
									  'from'   => 'posts',
									  'where'  => "attach_file != ''",
									  'limit'  => array( $start, $lend ) ) );

		$outer = $this->DB->execute();

		//-----------------------------------------
		// Do it...
		//-----------------------------------------

		if ( $this->DB->getTotalRows( $outer ) )
		{
			//-----------------------------------------
			// Got some to convert!
			//-----------------------------------------

			while( $r = $this->DB->fetch( $outer ) )
			{
				$image   = 0;
				$ext     = strtolower( str_replace( ".", "", substr( $r['attach_file'], strrpos( $r['attach_file'], '.' ) ) ) );
				$postkey = md5( $r['post_date'].','.$r['pid'] );

				if ( in_array( $ext, array( 'gif', 'jpeg', 'jpg', 'png' ) ) )
				{
					$image = 1;
				}

				$this->DB->insert( 'attachments', array( 'attach_ext'       => $ext,
													  'attach_file'      => $r['attach_file'],
													  'attach_location'  => $r['attach_id'],
													  'attach_is_image'  => $image,
													  'attach_hits'      => $r['attach_hits'],
													  'attach_date'      => $r['post_date'],
													  'attach_pid'       => $r['pid'],
													  'attach_post_key'  => $postkey,
													  'attach_member_id' => $r['author_id'],
													  'attach_approved'  => 1,
													  'attach_filesize'  => @filesize( DOC_IPS_ROOT_PATH.'uploads/'.$r['attach_id'] ),
							 )                      );

				$this->DB->update( 'posts', array( 'post_key' => $postkey ), 'pid='.$r['pid'] );
				$this->DB->buildAndFetch( array( 'update' => 'topics', 'set' => 'topic_hasattach=topic_hasattach+1', 'where' => 'tid='.$r['topic_id'] ) );
			}

			$this->request['st'] = $end;
			$this->registry->output->addMessage("Attachments $start to $end completed....");
			$this->request['workact'] = 'step_8';
		}
		else
		{
			$this->registry->output->addMessage("Attachments converted, converting members...");
			$this->request['workact'] = 'step_9';
			$this->request['st'] 	  = 0;
		}
	}


	/*-------------------------------------------------------------------------*/
	// STEP 9: CONVERT MEMBERS
	/*-------------------------------------------------------------------------*/

	function step_9()
	{
		$this->DB->return_die = 1;

		$start = intval($this->request['st']) ? intval($this->request['st']) : 0;
		$lend  = 300;
		$end   = $start + $lend;

		//-----------------------------------------
		// In steps...
		//-----------------------------------------

		$this->DB->build( array( 'select'   => 'm.*',
								 'from'     => array( 'members' => 'm' ),
								 'limit'    => array( $start, $lend ),
								 'add_join' => array( array( 'select' => 'me.id as mextra',
															 'from'   => array( 'member_extra' => 'me' ),
															 'where'  => 'me.id=m.id',
															 'type'   => 'left' ) ) ) );


		$o = $this->DB->execute();

		//-----------------------------------------
		// Do it...
		//-----------------------------------------

		if ( $this->DB->getTotalRows( $o ) )
		{
			//-----------------------------------------
			// Got some to convert!
			//-----------------------------------------

			while ( $r = $this->DB->fetch($o) )
			{
				if ( $r['mextra'] )
				{
					$this->DB->update( 'member_extra',
									array( 'aim_name'        => $r['aim_name'],
										   'icq_number'      => $r['icq_number'],
										   'website'         => $r['website'],
										   'yahoo'           => $r['yahoo'],
										   'interests'       => $r['interests'],
										   'msnname'         => $r['msnname'],
										   'vdirs'           => $r['vdirs'],
										   'location'        => $r['location'],
										   'signature'       => $r['signature'],
										   'avatar_location' => $r['avatar'],
										   'avatar_size'     => $r['avatar_size'],
										   'avatar_type'     => preg_match( "/^upload\:/", $r['avatar'] ) ? 'upload' : ( preg_match( "#^http://#", $r['avatar'] ) ? 'url' : 'local' )
								 ), 'id='.$r['mextra']        );
				}
				else
				{
					$this->DB->insert( 'member_extra',
									array( 'id'              => $r['id'],
										   'aim_name'        => $r['aim_name'],
										   'icq_number'      => $r['icq_number'],
										   'website'         => $r['website'],
										   'yahoo'           => $r['yahoo'],
										   'interests'       => $r['interests'],
										   'msnname'         => $r['msnname'],
										   'vdirs'           => $r['vdirs'],
										   'location'        => $r['location'],
										   'signature'       => $r['signature'],
										   'avatar_location' => $r['avatar'],
										   'avatar_size'     => $r['avatar_size'],
										   'avatar_type'     => preg_match( "/^upload\:/", $r['avatar'] ) ? 'upload' : ( preg_match( "#^http://#", $r['avatar'] ) ? 'url' : 'local' )
								 )  );
				}
			}

			$this->request['st'] = $end;
			$this->registry->output->addMessage("Members adjusted $start to $end completed....");
			$this->request['workact'] = 'step_9';
		}
		else
		{
			$this->registry->output->addMessage("Members converted, making members email addresses safe for converge...");
			$this->request['workact'] = 'step_10';
			$this->request['st'] 	  = 0;
		}
	}


	/*-------------------------------------------------------------------------*/
	// STEP 10: CHECK EMAIL ADDRESSES
	/*-------------------------------------------------------------------------*/

	function step_10()
	{
		$this->DB->return_die = 1;

		$start = intval($this->request['st']) ? intval($this->request['st']) : 0;
		$lend  = 300;
		$end   = $start + $lend;

		//-----------------------------------------
		// In steps...
		//-----------------------------------------

		$o = $this->DB->query( $this->sql_members_email( $lend ) );

		//-----------------------------------------
		// Do it...
		//-----------------------------------------

		while ( $r = $this->DB->fetch($o) )
		{
			if ( $r['count'] < 2 )
			{
				break;
			}
			else
			{
				$dupe_emails[] = $r['email'];
			}
		}

		if ( count( $dupe_emails ) )
		{
			foreach( $dupe_emails as $email )
			{
				$first = 0;

				$this->DB->build( array( 'select' => 'id,name,email', 'from' => 'members', 'where' => "email='{$email}'", 'order' => 'joined' ) );
				$i = $this->DB->execute();

				while( $r = $this->DB->fetch( $i ) )
				{
					// First?

					if ( ! $first )
					{
						$first = 1;
						continue;
					}
					else
					{
						// later dupe..

						$push_auth[] = $r['id'];
					}
				}
			}

			if ( count( $push_auth ) )
			{
				$this->DB->update( 'member_extra', array( 'bio' => 'dupemail' ), 'id IN ('.implode(",", $push_auth).")" );
				$this->DB->query( $this->sql_members_email_update( $push_auth ) );
			}

			$this->request['st'] = $end;
			$this->registry->output->addMessage("Members email addresses checked $start to $end completed....");
			$this->request['workact'] = 'step_10';
		}
		else
		{
			$this->registry->output->addMessage("Members email addresses checked, adding to converge...");
			$this->request['workact'] = 'step_11';
			$this->request['st'] 	  = 0;
		}
	}


	/*-------------------------------------------------------------------------*/
	// STEP 11: CONVERGE
	/*-------------------------------------------------------------------------*/

	function step_11()
	{
		$this->DB->return_die = 1;

		$start = intval($this->request['st']) ? intval($this->request['st']) : 0;
		$lend  = 300;
		$end   = $start + $lend;

		$max = 0;

		$this->DB->build( array( 'select' => 'id', 'from' =>'members', 'where' => "id > {$end}" ) );
		$this->DB->execute();

		$max = $this->DB->fetch();

		$found = 0;

		/* Grab session user */
		$sessionUser = $this->DB->buildAndFetch( array( 'select' => '*',
													    'from'   => 'upgrade_sessions',
													    'where'  => 'session_id=\'' . addslashes( $this->request['s'] ) . '\'' ) );


		$o = $this->DB->query( $this->sql_members_converge( $start, $end ) );

		$this->DB->build( array( 'select' => 'm.*',
								 'from'   => array( 'members' => 'm' ),
								 'where'  => 'm.id >=' . $start . ' AND id < ' . $end,
								 'add_join' => array( array( 'select' => 'c.converge_id as cid',
															 'from'   => array( 'members_converge' => 'c' ),
															 'where'  => 'c.converge_id=m.id',
															 'type'   => 'left' ) ) ) );


		$o = $this->DB->execute();

		//-----------------------------------------
		// Do it...
		//-----------------------------------------

		while ( $r = $this->DB->fetch($o) )
		{
			if ( ! $r['cid'] or ! $r['id'] )
			{
				$r['password'] = $r['password'] ? $r['password'] : $r['legacy_password'];

				$salt = IPSMember::generatePasswordSalt();

				$this->DB->insert( 'members_converge',
								array( 'converge_id'        => $r['id'],
									   'converge_email'     => strtolower($r['email']),
									   'converge_joined'    => $r['joined'],
									   'converge_pass_hash' => md5( md5($salt) . $r['password'] ),
									   'converge_pass_salt' => $salt
							 )       );

				$member_login_key = IPSMember::generateAutoLoginKey();

				/* Current session user? */
				if ( $sessionUser['session_member_id'] AND $sessionUser['session_member_id'] == $r['id'] )
				{
					$member_login_key = $sessionUser['session_member_key'];
				}

				$this->DB->update( 'members', array( 'member_login_key' => $member_login_key, 'email' => strtolower($r['email']) ), 'id='.$r['id'] );
			}

			$found++;
		}

		if ( ! $found and ! $max['id'] )
		{
			$this->registry->output->addMessage("Converge completed, converting personal messages...");
			$this->request['workact'] = 'step_12';
			$this->request['st'] 	  = 0;
		}
		else
		{
			$this->request['st'] = $end;
			$this->registry->output->addMessage("Converge added: $start to $end completed....");
			$this->request['workact'] = 'step_11';
		}
	}


	/*-------------------------------------------------------------------------*/
	// STEP 12: CONVERT PMs
	/*-------------------------------------------------------------------------*/

	function step_12()
	{
		$this->DB->return_die = 1;

		$start = $start = intval($this->request['st']) ? intval($this->request['st']) : 0;
		$lend  = 300;
		$end   = $start + $lend;

		//-----------------------------------------
		// In steps...
		//-----------------------------------------

		$this->DB->build( array( 'select' => '*', 'from' => 'messages', 'limit' => array( $start, $lend ) ) );
		$o = $this->DB->execute();

		//-----------------------------------------
		// Do it...
		//-----------------------------------------

		if ( $this->DB->getTotalRows() )
		{
			//-----------------------------------------
			// Got some to convert!
			//-----------------------------------------

			while ( $r = $this->DB->fetch($o) )
			{
				if ( ! $r['msg_date'] )
				{
					$r['msg_date'] = $r['read_date'];
				}

				if ( $r['vid'] != 'sent' )
				{
					$this->DB->insert( 'message_text',
									array( 'msg_date'          => $r['msg_date'],
										   'msg_post'          => stripslashes($r['message']),
										   'msg_cc_users'      => $r['cc_users'],
										   'msg_author_id'     => $r['from_id'],
										   'msg_sent_to_count' => 1,
										   'msg_deleted_count' => 0,
								  )      );

					$msg_id = $this->DB->getInsertId();

					$this->DB->insert( 'message_topics',
									array( 'mt_msg_id'     => $msg_id,
										   'mt_date'       => $r['msg_date'],
										   'mt_title'      => $r['title'],
										   'mt_from_id'    => $r['from_id'],
										   'mt_to_id'      => $r['recipient_id'],
										   'mt_vid_folder' => $r['vid'],
										   'mt_read'       => $r['read_state'],
										   'mt_tracking'   => $r['tracking'],
										   'mt_owner_id'   => $r['recipient_id'],
								 )        );
				}
				else
				{
					$this->DB->insert( 'message_text',
									array( 'msg_date'          => $r['msg_date'],
										   'msg_post'          => stripslashes($r['message']),
										   'msg_cc_users'      => $r['cc_users'],
										   'msg_author_id'     => $r['from_id'],
										   'msg_sent_to_count' => 1,
										   'msg_deleted_count' => 0,
								  )      );

					$msg_id = $this->DB->getInsertId();

					$this->DB->insert( 'message_topics',
									array( 'mt_msg_id'     => $msg_id,
										   'mt_date'       => $r['msg_date'],
										   'mt_title'      => $r['title'],
										   'mt_from_id'    => $r['from_id'],
										   'mt_to_id'      => $r['recipient_id'],
										   'mt_vid_folder' => $r['vid'],
										   'mt_read'       => $r['read_state'],
										   'mt_tracking'   => $r['tracking'],
										   'mt_owner_id'   => $r['from_id'],
								 )        );
				}

			}

			$this->request['st'] = $end;
			$this->registry->output->addMessage("Personal messages: $start to $end completed....");
			$this->request['workact'] = 'step_12';
		}
		else
		{
			$this->registry->output->addMessage("Personal messages converted, proceeding to update topic multi-moderation...");
			$this->request['workact'] = 'step_13';
			$this->request['st'] 	  = 0;
		}
	}


	/*-------------------------------------------------------------------------*/
	// STEP 13: CONVERT TOPIC MULTI_MODS
	/*-------------------------------------------------------------------------*/

	function step_13()
	{
		$this->DB->return_die = 1;

		$this->DB->build( array( 'select' => '*', 'from' => 'forums' ) );
		$f = $this->DB->execute();

		$final = array();

		while ( $r = $this->DB->fetch($f) )
		{
			$mmids = preg_split( "/,/", $r['topic_mm_id'], -1, PREG_SPLIT_NO_EMPTY );

			if ( is_array( $mmids ) )
			{
				foreach( $mmids as $m )
				{
					$final[ $m ][] = $r['id'];
				}
			}
		}

		$real_final = array();

		foreach( $final as $id => $forums_ids )
		{
			$ff = implode( ",",$forums_ids );

			$this->DB->update( 'topic_mmod', array( 'mm_forums' => $ff ), 'mm_id='.$id );
		}

		$this->registry->output->addMessage("Topic multi-moderation converted, alterting tables, stage 2...");
		$this->request['workact'] = 'step_14';
	}

	/*-------------------------------------------------------------------------*/
	// STEP 14: ALTER POST TABLE II
	/*-------------------------------------------------------------------------*/

	function step_14()
	{
		$SQL[] = "ALTER TABLE ".ipsRegistry::dbFunctions()->getPrefix()."posts DROP attach_id, DROP attach_hits, DROP attach_type, DROP attach_file;";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."posts change queued queued tinyint(1) NOT NULL default '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."posts drop forum_id;";

		$this->error   = array();
		$this->sqlcount 		= 0;
		$output					= "";

		$this->DB->return_die = 1;

		foreach( $SQL as $query )
		{
			$this->DB->allow_sub_select 	= 1;
			$this->DB->error				= '';

			if( IPSSetUp::getSavedData('man') )
			{
				$output .= preg_replace("/\sibf_(\S+?)([\s\.,]|$)/", " ".$this->DB->obj['sql_tbl_prefix']."\\1\\2", preg_replace( "/\s{1,}/", " ", $query ) )."\n\n";
			}
			else
			{
				$this->DB->query( $query );

				if ( $this->DB->error )
				{
					$this->registry->output->addError( $query."<br /><br />".$this->DB->error );
				}
				else
				{
					$this->sqlcount++;
				}
			}
		}

		$this->registry->output->addMessage("Post table altered, altering topic table next...<br /><br />$this->sqlcount queries run....");
		$this->request['workact'] = 'step_15';

		if ( IPSSetUp::getSavedData('man') AND $output )
		{
			$this->_output = $this->registry->output->template()->upgrade_manual_queries( $output );
		}
	}


	/*-------------------------------------------------------------------------*/
	// STEP 15: ALTER TOPIC TABLE II
	/*-------------------------------------------------------------------------*/

	function step_15()
	{
		$SQL[] = "ALTER TABLE ".ipsRegistry::dbFunctions()->getPrefix()."topics add topic_firstpost int(10) NOT NULL default '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."topics add topic_queuedposts int(10) NOT NULL default '0';";

		$this->error   = array();
		$this->sqlcount 		= 0;
		$output					= "";

		$this->DB->return_die = 1;

		foreach( $SQL as $query )
		{
			$this->DB->allow_sub_select 	= 1;
			$this->DB->error				= '';

			if( IPSSetUp::getSavedData('man') )
			{
				$output .= preg_replace("/\sibf_(\S+?)([\s\.,]|$)/", " ".$this->DB->obj['sql_tbl_prefix']."\\1\\2", preg_replace( "/\s{1,}/", " ", $query ) )."\n\n";
			}
			else
			{
				$this->DB->query( $query );

				if ( $this->DB->error )
				{
					$this->registry->output->addError( $query."<br /><br />".$this->DB->error );
				}
				else
				{
					$this->sqlcount++;
				}
			}
		}

		$this->registry->output->addMessage("Topic table altered, altering members table next...<br /><br />$this->sqlcount queries run....");
		$this->request['workact'] = 'step_16';

		if ( IPSSetUp::getSavedData('man') AND $output )
		{
			$this->_output = $this->registry->output->template()->upgrade_manual_queries( $output );
		}
	}


	/*-------------------------------------------------------------------------*/
	// STEP 16: ALTER MEMBERS TABLE II
	/*-------------------------------------------------------------------------*/

	function step_16()
	{
		if ( ! ipsRegistry::DB()->checkForField( 'sub_end', 'has_blog' ) )
		{
			$SQL[] = "ALTER TABLE ".ipsRegistry::dbFunctions()->getPrefix()."members add has_blog TINYINT(1) NOT NULL default '0';";
		}

		if ( ! ipsRegistry::DB()->checkForField( 'sub_end', 'members' ) )
		{
			$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."members add sub_end int(10) NOT NULL default '0';";
		}

		$SQL[] = "ALTER TABLE ".ipsRegistry::dbFunctions()->getPrefix()."members DROP msg_from_id, DROP msg_msg_id;";
		$SQL[] = "ALTER TABLE ".ipsRegistry::dbFunctions()->getPrefix()."members DROP org_supmod, DROP integ_msg;";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."members DROP aim_name, DROP icq_number, DROP website, DROP yahoo, DROP interests,
				  DROP msnname, DROP vdirs, DROP signature, DROP location, DROP avatar, DROP avatar_size;";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."members change auto_track auto_track varchar(50) default '0';";
		$SQL[] = "ALTER TABLE ".ipsRegistry::dbFunctions()->getPrefix()."members change temp_ban temp_ban varchar(100) default '0';";
		$SQL[] = "ALTER TABLE ".ipsRegistry::dbFunctions()->getPrefix()."members change msg_total msg_total smallint(5) default '0';";

		if( !$this->DB->checkForField( "subs_pkg_chosen", "members" ) )
		{
			$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."members add subs_pkg_chosen smallint(3) NOT NULL default '0';";
		}

		$this->error   = array();
		$this->sqlcount 		= 0;
		$output					= "";

		$this->DB->return_die = 1;

		foreach( $SQL as $query )
		{
			$this->DB->allow_sub_select 	= 1;
			$this->DB->error				= '';

			if( IPSSetUp::getSavedData('man') )
			{
				$output .= preg_replace("/\sibf_(\S+?)([\s\.,]|$)/", " ".$this->DB->obj['sql_tbl_prefix']."\\1\\2", preg_replace( "/\s{1,}/", " ", $query ) )."\n\n";
			}
			else
			{
				$this->DB->query( $query );

				if ( $this->DB->error )
				{
					$this->registry->output->addError( $query."<br /><br />".$this->DB->error );
				}
				else
				{
					$this->sqlcount++;
				}
			}
		}

		$this->registry->output->addMessage("Members table altered, other tables next...<br /><br />$this->sqlcount queries run....");
		$this->request['workact'] = 'step_17';

		if ( IPSSetUp::getSavedData('man') AND $output )
		{
			$this->_output = $this->registry->output->template()->upgrade_manual_queries( $output );
		}
	}


	/*-------------------------------------------------------------------------*/
	// STEP 17: ALTER OTHERS TABLE II
	/*-------------------------------------------------------------------------*/

	function step_17()
	{
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."groups add g_attach_per_post int(10) NOT NULL default '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."topic_mmod add topic_approve tinyint(1) NOT NULL default '0';";

		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."groups add g_can_msg_attach tinyint(1) NOT NULL default '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."pfields_data
				change fid pf_id smallint(5) NOT NULL auto_increment,
				change ftitle pf_title varchar(250) NOT NULL default '',
				change fdesc pf_desc varchar(250) NOT NULL default '',
				change fcontent pf_content text NULL,
				change ftype pf_type varchar(250) NOT NULL default '',
				change freq pf_not_null tinyint(1) NOT NULL default '0',
				change fhide pf_member_hide tinyint(1) NOT NULL default '0',
				change fmaxinput pf_max_input smallint(6) NOT NULL default '0',
				change fedit pf_member_edit tinyint(1) NOT NULL default '0',
				change forder pf_position smallint(6) NOT NULL default '0',
				change fshowreg pf_show_on_reg tinyint(1) NOT NULL default '0',
				add pf_input_format text NULL,
				add pf_admin_only tinyint(1) NOT NULL default '0',
				add pf_topic_format text NULL;";

		$this->error   = array();
		$this->sqlcount = 0;

		$this->DB->return_die = 1;

		foreach( $SQL as $query )
		{
			$this->DB->allow_sub_select 	= 1;
			$this->DB->error				= '';

			$this->DB->query( $query );

			if ( $this->DB->error )
			{
				$this->registry->output->addError( $query."<br /><br />".$this->DB->error );
			}
			else
			{
				$this->sqlcount++;
			}
		}

		$this->registry->output->addMessage("Other tables altered, inserting data next...<br /><br />$this->sqlcount queries run....");
		$this->request['workact'] = 'step_18';
	}


	/*-------------------------------------------------------------------------*/
	// STEP 18: SAFE INSERTS
	/*-------------------------------------------------------------------------*/

	function step_18()
	{
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."task_manager VALUES (1, 'Hourly Clean Out', 'cleanout.php', 1076074920, -1, -1, -1, 59, '2a7d083832daa123b73a68f9c51fdb29', 1, 'Kill old sessions, reg images, searches',1,'',0);";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."task_manager VALUES (2, 'Daily Stats Rebuild', 'rebuildstats.php', 1076112000, -1, -1, 0, 0, '640b9a6c373ff207bc1b1100a98121af', 1, 'Rebuilds board statistics',1,'',0);";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."task_manager VALUES (3, 'Daily Clean Out', 'dailycleanout.php', 1076122800, -1, -1, 3, 0, 'e71b52f3ff9419abecedd14b54e692c4', 1, 'Prunes topic subscriptions',1,'',0);";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."task_manager VALUES (4, 'Birthday and Events Cache', 'calendarevents.php', 1076100960, -1, -1, 12, -1, '2c148c9bd754d023a7a19dd9b1535796', 1, 'Caches calendar events &amp; birthdays',1,'',0);";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."task_manager (task_id, task_title, task_file, task_next_run, task_week_day, task_month_day, task_hour, task_minute, task_cronkey, task_log, task_description, task_enabled) VALUES (9, 'Announcements Update', 'announcements.php', 1080747660, -1, -1, 4, -1, 'e82f2c19ab1ed57c140fccf8aea8b9fe', 1, 'Rebuilds cache and expires out of date announcements', 1);";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."task_manager (task_title, task_file, task_next_run, task_week_day, task_month_day, task_hour, task_minute, task_cronkey, task_log, task_description, task_enabled, task_key, task_safemode) VALUES ('Send Bulk Mail', 'bulkmail.php', 1086706080, -1, -1, -1, -1, '61359ac93eb93ebbd935a4e275ade2db', 0, 'Dynamically assigned, no need to edit or change', 0, 'bulkmail', 1);";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."task_manager (task_title, task_file, task_next_run, task_week_day, task_month_day, task_hour, task_minute, task_cronkey, task_log, task_description, task_enabled, task_key, task_safemode) VALUES ('Daily Topic &amp; Forum Digest', 'dailydigest.php', 1086912600, -1, -1, 0, 10, '723cab2aae32dd5d04898b1151038846', 1, 'Emails out daily topic &amp; forum digest emails', 1, 'dailydigest', 0);";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."task_manager (task_title, task_file, task_next_run, task_week_day, task_month_day, task_hour, task_minute, task_cronkey, task_log, task_description, task_enabled, task_key, task_safemode) VALUES ('Weekly Topic &amp; Forum Digest', 'weeklydigest.php', 1087096200, 0, -1, 3, 10, '7e7fccd07f781bdb24ac108d26612931', 1, 'Emails weekly topic &amp; forum digest emails', 1, 'weeklydigest', 0);";

		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."custom_bbcode (bbcode_title, bbcode_desc, bbcode_tag, bbcode_replace, bbcode_useoption, bbcode_example) VALUES ('Post Snap Back', 'This tag displays a little linked image which links back to a post - used when quoting posts from the board. Opens in same window by default.', 'snapback', '<a href=\"index.php?act=findpost&amp;pid={content}\"><{POST_SNAPBACK}></a>', 0, '[snapback]100[/snapback]');";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."custom_bbcode (bbcode_title, bbcode_desc, bbcode_tag, bbcode_replace, bbcode_useoption, bbcode_example) VALUES ('Right', 'Aligns content to the right of the posting area', 'right', '<div align=\'right\'>{content}</div>', 0, '[right]Some text here[/right]');";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."custom_bbcode (bbcode_title, bbcode_desc, bbcode_tag, bbcode_replace, bbcode_useoption, bbcode_example) VALUES ('Left', 'Aligns content to the left of the post', 'left', '<div align=\'left\'>{content}</div>', 0, '[left]Left aligned text[/left]');";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."custom_bbcode (bbcode_title, bbcode_desc, bbcode_tag, bbcode_replace, bbcode_useoption, bbcode_example) VALUES ('Center', 'Aligns content to the center of the posting area.', 'center', '<div align=\'center\'>{content}</div>', 0, '[center]Centered Text[/center]');";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."custom_bbcode (bbcode_title, bbcode_desc, bbcode_tag, bbcode_replace, bbcode_useoption, bbcode_example) VALUES ('Topic Link', 'This tag provides an easy way to link to a topic', 'topic', '<a href=\'index.php?showtopic={option}\'>{content}</a>', 1, '[topic=100]Click me![/topic]');";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."custom_bbcode (bbcode_title, bbcode_desc, bbcode_tag, bbcode_replace, bbcode_useoption, bbcode_example) VALUES ('Post Link', 'This tag provides an easy way to link to a post.', 'post', '<a href=\'index.php?act=findpost&pid={option}\'>{content}</a>', 1, '[post=100]Click me![/post]');";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."custom_bbcode (bbcode_title, bbcode_desc, bbcode_tag, bbcode_replace, bbcode_useoption, bbcode_example) VALUES ('CODEBOX', 'Use this BBCode tag to show a scrolling codebox. Useful for long sections of code.', 'codebox', '<div class=\'codetop\'>CODE</div><div class=\'codemain\' style=\'height:200px;white-space:pre;overflow:auto\'>{content}</div>', 0, '[codebox]long_code_here = '';[/codebox]');";



		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."subscription_methods (submethod_title, submethod_name, submethod_email, submethod_sid, submethod_custom_1, submethod_custom_2, submethod_custom_3, submethod_custom_4, submethod_custom_5, submethod_is_cc, submethod_is_auto, submethod_desc, submethod_logo, submethod_active, submethod_use_currency) VALUES ('Authorize.net', 'authorizenet', '', '', '', '', '', '', '', '1', '1', NULL, NULL, '1', 'USD');";

		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."attachments_type (atype_id, atype_extension, atype_mimetype, atype_post, atype_photo, atype_img) VALUES (1, 'pdf', 'application/pdf', 1, 0, 'folder_mime_types/pdf.gif');";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."attachments_type (atype_id, atype_extension, atype_mimetype, atype_post, atype_photo, atype_img) VALUES (2, 'png', 'image/png', 1, 1, 'folder_mime_types/quicktime.gif');";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."attachments_type (atype_id, atype_extension, atype_mimetype, atype_post, atype_photo, atype_img) VALUES (3, 'viv', 'video/vivo', 1, 0, 'folder_mime_types/win_player.gif');";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."attachments_type (atype_id, atype_extension, atype_mimetype, atype_post, atype_photo, atype_img) VALUES (4, 'wmv', 'video/x-msvideo', 1, 0, 'folder_mime_types/win_player.gif');";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."attachments_type (atype_id, atype_extension, atype_mimetype, atype_post, atype_photo, atype_img) VALUES (5, 'html', 'application/octet-stream', 1, 0, 'folder_mime_types/html.gif');";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."attachments_type (atype_id, atype_extension, atype_mimetype, atype_post, atype_photo, atype_img) VALUES (6, 'ram', 'audio/x-pn-realaudio', 1, 0, 'folder_mime_types/real_audio.gif');";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."attachments_type (atype_id, atype_extension, atype_mimetype, atype_post, atype_photo, atype_img) VALUES (7, 'gif', 'image/gif', 1, 1, 'folder_mime_types/gif.gif');";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."attachments_type (atype_id, atype_extension, atype_mimetype, atype_post, atype_photo, atype_img) VALUES (8, 'mpg', 'video/mpeg', 1, 0, 'folder_mime_types/quicktime.gif');";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."attachments_type (atype_id, atype_extension, atype_mimetype, atype_post, atype_photo, atype_img) VALUES (9, 'ico', 'image/ico', 1, 0, 'folder_mime_types/gif.gif');";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."attachments_type (atype_id, atype_extension, atype_mimetype, atype_post, atype_photo, atype_img) VALUES (10, 'tar', 'application/x-tar', 1, 0, 'folder_mime_types/zip.gif');";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."attachments_type (atype_id, atype_extension, atype_mimetype, atype_post, atype_photo, atype_img) VALUES (11, 'bmp', 'image/x-MS-bmp', 1, 0, 'folder_mime_types/gif.gif');";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."attachments_type (atype_id, atype_extension, atype_mimetype, atype_post, atype_photo, atype_img) VALUES (12, 'tiff', 'image/tiff', 1, 0, 'folder_mime_types/quicktime.gif');";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."attachments_type (atype_id, atype_extension, atype_mimetype, atype_post, atype_photo, atype_img) VALUES (13, 'rtf', 'text/richtext', 1, 0, 'folder_mime_types/rtf.gif');";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."attachments_type (atype_id, atype_extension, atype_mimetype, atype_post, atype_photo, atype_img) VALUES (14, 'hqx', 'application/mac-binhex40', 1, 0, 'folder_mime_types/stuffit.gif');";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."attachments_type (atype_id, atype_extension, atype_mimetype, atype_post, atype_photo, atype_img) VALUES (15, 'aiff', 'audio/x-aiff', 1, 0, 'folder_mime_types/quicktime.gif');";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."attachments_type (atype_id, atype_extension, atype_mimetype, atype_post, atype_photo, atype_img) VALUES (31, 'zip', 'application/zip', 1, 0, 'folder_mime_types/zip.gif');";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."attachments_type (atype_id, atype_extension, atype_mimetype, atype_post, atype_photo, atype_img) VALUES (17, 'ps', 'application/postscript', 1, 0, 'folder_mime_types/eps.gif');";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."attachments_type (atype_id, atype_extension, atype_mimetype, atype_post, atype_photo, atype_img) VALUES (18, 'doc', 'application/msword', 1, 0, 'folder_mime_types/doc.gif');";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."attachments_type (atype_id, atype_extension, atype_mimetype, atype_post, atype_photo, atype_img) VALUES (19, 'mov', 'video/quicktime', 1, 0, 'folder_mime_types/quicktime.gif');";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."attachments_type (atype_id, atype_extension, atype_mimetype, atype_post, atype_photo, atype_img) VALUES (20, 'ppt', 'application/powerpoint', 1, 0, 'folder_mime_types/ppt.gif');";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."attachments_type (atype_id, atype_extension, atype_mimetype, atype_post, atype_photo, atype_img) VALUES (21, 'wav', 'audio/x-wav', 1, 0, 'folder_mime_types/music.gif');";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."attachments_type (atype_id, atype_extension, atype_mimetype, atype_post, atype_photo, atype_img) VALUES (22, 'mp3', 'audio/x-mpeg', 1, 0, 'folder_mime_types/music.gif');";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."attachments_type (atype_id, atype_extension, atype_mimetype, atype_post, atype_photo, atype_img) VALUES (23, 'jpg', 'image/jpeg', 1, 1, 'folder_mime_types/gif.gif');";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."attachments_type (atype_id, atype_extension, atype_mimetype, atype_post, atype_photo, atype_img) VALUES (24, 'txt', 'text/plain', 1, 0, 'folder_mime_types/txt.gif');";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."attachments_type (atype_id, atype_extension, atype_mimetype, atype_post, atype_photo, atype_img) VALUES (25, 'xml', 'text/xml', 1, 0, 'folder_mime_types/script.gif');";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."attachments_type (atype_id, atype_extension, atype_mimetype, atype_post, atype_photo, atype_img) VALUES (26, 'css', 'text/css', 1, 0, 'folder_mime_types/script.gif');";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."attachments_type (atype_id, atype_extension, atype_mimetype, atype_post, atype_photo, atype_img) VALUES (27, 'swf', 'application/x-shockwave-flash', 0, 0, 'folder_mime_types/quicktime.gif');";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."attachments_type (atype_id, atype_extension, atype_mimetype, atype_post, atype_photo, atype_img) VALUES (32, 'php', 'application/octet-stream', 1, 0, 'folder_mime_types/php.gif');";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."attachments_type (atype_id, atype_extension, atype_mimetype, atype_post, atype_photo, atype_img) VALUES (28, 'htm', 'application/octet-stream', 1, 0, 'folder_mime_types/html.gif');";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."attachments_type (atype_id, atype_extension, atype_mimetype, atype_post, atype_photo, atype_img) VALUES (29, 'jpeg', 'image/jpeg', 1, 1, 'folder_mime_types/gif.gif');";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."attachments_type (atype_id, atype_extension, atype_mimetype, atype_post, atype_photo, atype_img) VALUES (33, 'gz', 'application/x-gzip', 1, 0, 'folder_mime_types/zip.gif');";

		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."cache_store (cs_key, cs_value, cs_extra, cs_array) VALUES ('skin_id_cache', '', '', 1);";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."cache_store (cs_key, cs_value, cs_extra, cs_array) VALUES ('bbcode', '', '', 1);";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."cache_store (cs_key, cs_value, cs_extra, cs_array) VALUES ('moderators', '', '', 1);";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."cache_store (cs_key, cs_value, cs_extra, cs_array) VALUES ('multimod', '', '', 1);";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."cache_store (cs_key, cs_value, cs_extra, cs_array) VALUES ('banfilters', '', '', 1);";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."cache_store (cs_key, cs_value, cs_extra, cs_array) VALUES ('attachtypes', '', '', 1);";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."cache_store (cs_key, cs_value, cs_extra, cs_array) VALUES ('emoticons', '', '', 1);";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."cache_store (cs_key, cs_value, cs_extra, cs_array) VALUES ('forum_cache', '', '', 1);";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."cache_store (cs_key, cs_value, cs_extra, cs_array) VALUES ('badwords', '', '', 1);";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."cache_store (cs_key, cs_value, cs_extra, cs_array) VALUES ('systemvars', '', '', 1);";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."cache_store (cs_key, cs_value, cs_extra, cs_array) VALUES ('ranks', '', '', 1);";
	//	$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."cache_store (cs_key, cs_value, cs_extra, cs_array) VALUES ('group_cache', '', '', 1);";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."cache_store (cs_key, cs_value, cs_extra, cs_array) VALUES ('stats', '', '', 1);";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."cache_store (cs_key, cs_value, cs_extra, cs_array) VALUES ('profilefields', 'a:0:{}', '', 1);";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."cache_store (cs_key, cs_value, cs_extra, cs_array) VALUES ('settings','', '', 1);";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."cache_store (cs_key, cs_value, cs_extra, cs_array) VALUES ('languages', '', '', 1);";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."cache_store (cs_key, cs_value, cs_extra, cs_array) VALUES ('birthdays', 'a:0:{}', '', 1);";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."cache_store (cs_key, cs_value, cs_extra, cs_array) VALUES ('calendar', 'a:0:{}', '', 1);";

		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."skin_sets (set_skin_set_id, set_name, set_image_dir, set_hidden, set_default, set_css_method, set_skin_set_parent, set_author_email, set_author_name, set_author_url, set_css, set_cache_macro, set_wrapper, set_css_updated, set_cache_css, set_cache_wrapper, set_emoticon_folder) VALUES (1, 'IPB Master Skin Set', '1', 0, 0, '0', -1, '', '', '', '', '', '', 1079109298, '', '', 'default');";
		//$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."skin_sets (set_skin_set_id, set_name, set_image_dir, set_hidden, set_default, set_css_method, set_skin_set_parent, set_author_email, set_author_name, set_author_url, set_css, set_cache_macro, set_wrapper, set_css_updated, set_cache_css, set_cache_wrapper, set_emoticon_folder) VALUES (2, 'IPB Default Skin', '1', 0, 1, '0', -1, 'ipbauto@', 'Invision Power Services', 'www.', '', '', '', 1074679074, '', '', 'default');";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."skin_sets (set_skin_set_id, set_name, set_image_dir, set_hidden, set_default, set_css_method, set_skin_set_parent, set_author_email, set_author_name, set_author_url, set_css, set_cache_macro, set_wrapper, set_css_updated, set_cache_css, set_cache_wrapper, set_emoticon_folder) VALUES (3, 'IPB Pre-2.0 Skins', '1', 0, 0, '0', -1, 'ipbauto@', 'Invision Power Services', 'www.', '', '', '', 1074679074, '', '', 'default');";


		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."conf_settings_titles (conf_title_id, conf_title_title, conf_title_desc, conf_title_count) VALUES (1, 'General Configuration', 'These settings control the basics of the board such as URLs and paths.', 17);";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."conf_settings_titles (conf_title_id, conf_title_title, conf_title_desc, conf_title_count) VALUES (2, 'CPU Saving &amp; Optimization', 'This section allows certain features to be limited or removed to get more performance out of your board.', 16);";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."conf_settings_titles (conf_title_id, conf_title_title, conf_title_desc, conf_title_count) VALUES (3, 'Date &amp; Time Formats', 'This section contains the date and time formats used throughout the board.', 7);";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."conf_settings_titles (conf_title_id, conf_title_title, conf_title_desc, conf_title_count) VALUES (4, 'User Profiles', 'This section allows you to adjust your member\'s global permissions and other options.', 22);";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."conf_settings_titles (conf_title_id, conf_title_title, conf_title_desc, conf_title_count) VALUES (5, 'Topics, Posts and Polls', 'These options control various elements when posting, reading topics and reading polls.', 32);";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."conf_settings_titles (conf_title_id, conf_title_title, conf_title_desc, conf_title_count) VALUES (6, 'Security and Privacy', 'These options allow you to adjust the security and privacy options for your board.', 18);";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."conf_settings_titles (conf_title_id, conf_title_title, conf_title_desc, conf_title_count) VALUES (7, 'Cookies', 'This section allows you to set the default cookie options.', 3);";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."conf_settings_titles (conf_title_id, conf_title_title, conf_title_desc, conf_title_count) VALUES (8, 'COPPA Set-up', 'This section allows you to comply with <a href=\'http://www.ftc.gov/ogc/coppa1.htm\'>COPPA</a>.', 3);";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."conf_settings_titles (conf_title_id, conf_title_title, conf_title_desc, conf_title_count) VALUES (9, 'Calendar &amp; Birthdays', 'This section will allow you to set up the board calendar and its related options.', 8);";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."conf_settings_titles (conf_title_id, conf_title_title, conf_title_desc, conf_title_count) VALUES (10, 'News Set-up', 'This section will allow you to specify the forum you wish to export news topics from to be used with ssi.php and IPDynamic Lite.', 2);";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."conf_settings_titles (conf_title_id, conf_title_title, conf_title_desc, conf_title_count) VALUES (11, 'Personal Message Set-up', 'This section allows you to control the global PM options.', 3);";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."conf_settings_titles (conf_title_id, conf_title_title, conf_title_desc, conf_title_count) VALUES (12, 'Email Set-up', 'This section will allow you to change the incoming and outgoing email addresses as well as the email method.', 7);";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."conf_settings_titles (conf_title_id, conf_title_title, conf_title_desc, conf_title_count) VALUES (13, 'Warn Set-up', 'This section will allow you to set up the warning system.', 15);";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."conf_settings_titles (conf_title_id, conf_title_title, conf_title_desc, conf_title_count) VALUES (14, 'Trash Can Set-up', 'The trashcan is a special forum in which topics are moved into instead of being deleted.', 6);";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."conf_settings_titles (conf_title_id, conf_title_title, conf_title_desc, conf_title_count) VALUES (15, 'Board Offline / Online', 'Use this setting to turn switch your board online or offline and leave a message for your visitors.', 2);";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."conf_settings_titles (conf_title_id, conf_title_title, conf_title_desc, conf_title_count) VALUES (16, 'Search Engine Spiders', 'This section will allow you to set up and maintain your search engine bot spider recognition settings.', 7);";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."conf_settings_titles (conf_title_id, conf_title_title, conf_title_desc, conf_title_count) VALUES (17, 'Board Guidelines', 'This section allows you to maintain your board guidelines. If enabled, a link will be added to the board header linking to the board guidelines.', 4);";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."conf_settings_titles (conf_title_id, conf_title_title, conf_title_desc, conf_title_count) VALUES (18, 'Converge Set Up', 'Converge is Invision Power Services central authentication method for all IPS applications. This allows you to have a single log-in for all your IPS products.', 1);";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."conf_settings_titles (conf_title_id, conf_title_title, conf_title_desc, conf_title_count) VALUES (19, 'Full Text Search Set-Up', 'Full text searching is a very fast and very efficient way of searching large amounts of posts without maintaining a manual index. This may not be available for your system.', 2);";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."conf_settings_titles (conf_title_id, conf_title_title, conf_title_desc, conf_title_count, conf_title_noshow, conf_title_keyword) VALUES (20,'Invision Chat Settings (Legacy Version)', 'This will allow you to customize your Invision Chat integration settings for the legacy edition.', 14, 1, 'chat');";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."conf_settings_titles (conf_title_id, conf_title_title, conf_title_desc, conf_title_count, conf_title_noshow, conf_title_keyword) VALUES (21,'Invision Chat Settings', 'This will allow you to customize your Invision Chat integration settings for the new 2004 edition', 10, 1, 'chat04');";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."conf_settings_titles (conf_title_id, conf_title_title, conf_title_desc, conf_title_count, conf_title_noshow, conf_title_keyword) VALUES (22,'IPB Portal', 'These settings enable you to enable or disable IPB Portal and control the options IPB Portal offers.', 20, 0, 'ipbportal');";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."conf_settings_titles (conf_title_id, conf_title_title, conf_title_desc, conf_title_count, conf_title_noshow, conf_title_keyword) VALUES (23,'Subscriptions Manager', 'These settings control various subscription manager features.', 3, 0, 'subsmanager');";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."conf_settings_titles (conf_title_id, conf_title_title, conf_title_desc, conf_title_count, conf_title_noshow, conf_title_keyword) VALUES (24,'IPB Registration', 'This section will allow you to edit your IPB registered licence settings.', 3, 1, 'ipbreg');";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."conf_settings_titles (conf_title_id, conf_title_title, conf_title_desc, conf_title_count, conf_title_noshow, conf_title_keyword) VALUES (25,'IPB Copyright Removal', 'This section allows you to manage your copyright removal key.', 2, 1, 'ipbcopyright');";


		$this->error   = array();
		$this->sqlcount = 0;

		$this->DB->return_die = 1;

		foreach( $SQL as $query )
		{
			$this->DB->allow_sub_select 	= 1;
			$this->DB->error				= '';

			$this->DB->query( $query );

			if ( $this->DB->error )
			{
				$this->registry->output->addError( $query."<br /><br />".$this->DB->error );
			}
			else
			{
				$this->sqlcount++;
			}
		}

		$this->registry->output->addMessage("Inserts completed, dropping old tables next...<br /><br />$this->sqlcount queries run....");
		$this->request['workact'] = 'step_19';
	}


	/*-------------------------------------------------------------------------*/
	// STEP 19: DROPPING TABLES
	/*-------------------------------------------------------------------------*/

	function step_19()
	{
		$SQL[] = "DROP TABLE ".ipsRegistry::dbFunctions()->getPrefix()."tmpl_names;";
		$SQL[] = "DROP TABLE ".ipsRegistry::dbFunctions()->getPrefix()."forums_bak;";
		$SQL[] = "DROP TABLE ".ipsRegistry::dbFunctions()->getPrefix()."categories;";
		$SQL[] = "DROP TABLE ".ipsRegistry::dbFunctions()->getPrefix()."messages;";

		$this->error   = array();
		$this->sqlcount = 0;

		$this->DB->return_die = 1;

		foreach( $SQL as $query )
		{
			$this->DB->allow_sub_select 	= 1;
			$this->DB->error				= '';

			$this->DB->query( $query );

			if ( $this->DB->error )
			{
				$this->registry->output->addError( $query."<br /><br />".$this->DB->error );
			}
			else
			{
				$this->sqlcount++;
			}
		}

		$this->registry->output->addMessage("Old tables dropped, optimization next...<br /><br />$this->sqlcount queries run....");
		$this->request['workact'] = 'step_20';
	}

	/*-------------------------------------------------------------------------*/
	// STEP 20: OPTIMIZATION
	/*-------------------------------------------------------------------------*/

	function step_20()
	{
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."tracker change topic_id topic_id int(10) NOT NULL default '0';";
		$SQL[] = "ALTER TABLE ".ipsRegistry::dbFunctions()->getPrefix()."tracker ADD INDEX(topic_id);";

		$SQL[] = "ALTER TABLE ".ipsRegistry::dbFunctions()->getPrefix()."topics CHANGE pinned pinned TINYINT( 1 ) DEFAULT '0' NOT NULL;";
		$SQL[] = "ALTER TABLE ".ipsRegistry::dbFunctions()->getPrefix()."topics CHANGE approved approved TINYINT( 1 ) DEFAULT '0' NOT NULL;";
		$SQL[] = "ALTER TABLE ".ipsRegistry::dbFunctions()->getPrefix()."topics ADD INDEX(topic_firstpost);";

		$SQL[] = "UPDATE ".ipsRegistry::dbFunctions()->getPrefix()."members SET language='';";

		$this->error   = array();
		$this->sqlcount 		= 0;
		$output					= "";

		$this->DB->return_die = 1;

		foreach( $SQL as $query )
		{
			$this->DB->allow_sub_select 	= 1;
			$this->DB->error				= '';

			if( IPSSetUp::getSavedData('man') )
			{
				$output .= preg_replace("/\sibf_(\S+?)([\s\.,]|$)/", " ".$this->DB->obj['sql_tbl_prefix']."\\1\\2", preg_replace( "/\s{1,}/", " ", $query ) )."\n\n";
			}
			else
			{
				$this->DB->query( $query );

				if ( $this->DB->error )
				{
					$this->registry->output->addError( $query."<br /><br />".$this->DB->error );
				}
				else
				{
					$this->sqlcount++;
				}
			}
		}

		$this->registry->output->addMessage("Optimization started...<br /><br />$this->sqlcount queries run....");
		$this->request['workact'] = 'step_21';

		if ( IPSSetUp::getSavedData('man') AND $output )
		{
			$this->_output = $this->registry->output->template()->upgrade_manual_queries( $output );
		}
	}


	/*-------------------------------------------------------------------------*/
	// STEP 21: OPTIMIZATION II
	/*-------------------------------------------------------------------------*/

	function step_21()
	{
		#$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."posts drop index topic_id;";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."posts drop index author_id;";
		#$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."posts add index topic_id (topic_id, queued, pid);";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."posts add index author_id( author_id, topic_id);";
		$SQL[] = "ALTER TABLE ".ipsRegistry::dbFunctions()->getPrefix()."posts DROP INDEX forum_id, ADD INDEX(post_date);";

		$this->error   = array();
		$this->sqlcount 		= 0;
		$output					= "";

		$this->DB->return_die = 1;

		foreach( $SQL as $query )
		{
			$this->DB->allow_sub_select 	= 1;
			$this->DB->error				= '';

			if( IPSSetUp::getSavedData('man') )
			{
				$output .= preg_replace("/\sibf_(\S+?)([\s\.,]|$)/", " ".$this->DB->obj['sql_tbl_prefix']."\\1\\2", preg_replace( "/\s{1,}/", " ", $query ) )."\n\n";
			}
			else
			{
				$this->DB->query( $query );

				if ( $this->DB->error )
				{
					$this->registry->output->addError( $query."<br /><br />".$this->DB->error );
				}
				else
				{
					$this->sqlcount++;
				}
			}
		}

		$this->registry->output->addMessage("Optimization completed");
		$this->request['workact'] = 'step_22';

		if ( IPSSetUp::getSavedData('man') AND $output )
		{
			$this->_output = $this->registry->output->template()->upgrade_manual_queries( $output );
		}

		unset($this->request['workact']);
		unset($this->request['st']);
	}


	//#------------------------------------------------------------------------
	// OTHER SQL WORK
	//#------------------------------------------------------------------------

	function sql_members($a, $b)
	{
		return "SELECT m.*, me.id as mextra FROM ibf_members m LEFT JOIN ibf_member_extra me ON ( me.id=m.member_id ) LIMIT $a, $b";
	}

	function sql_members_email( $a )
	{
		return "select id, name, email, count(email) as count from ibf_members group by email order by count desc LIMIT 0, $a";
	}

	function sql_members_email_update( $push_auth )
	{
		return "UPDATE ibf_members SET email=concat( id, '-', email ) where id IN(".implode(",", $push_auth).")";
	}

	function sql_members_converge( $start, $end )
	{
		return "SELECT m.*, c.converge_id as cid FROM ibf_members m LEFT JOIN ibf_members_converge c ON ( c.converge_id=m.member_id ) WHERE id >= $start and id < $end";
	}

}

?>