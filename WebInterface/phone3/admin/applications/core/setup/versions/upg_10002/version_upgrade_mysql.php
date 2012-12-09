<?php

/*
+--------------------------------------------------------------------------
|  IP.Board v3.0.3
|  ========================================
|  by Matthew Mecham
|  (c) 2001 - 2004 Invision Power Services
|  http://www.
|  ========================================
|  Web: http://www.
|  Email: matt@
|  Licence Info: http://www./?license
+---------------------------------------------------------------------------
|
|  > IPB UPGRADE 1.1 -> 2.0 SQL STUFF!
|  > Script written by Matt Mecham
|  > Date started: 21st April 2004
|  > Interesting fact: Turin Brakes are also good
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
			case 'step_22':
				$this->step_22();
				break;
			case 'step_23':
				$this->step_23();
				break;	
			case 'step_24':
				$this->step_24();
				break;				
			
			default:
				$this->step_1();
				break;
		}
		if ( $this->request['workact'] )
		{
			IPSSetUp::getSavedData('vid') = $this->install->current_version;
			
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
		
		$SQL[] = "DROP TABLE if exists ".ipsRegistry::dbFunctions()->getPrefix()."forums_perms;";
		
		$SQL[]="CREATE TABLE ".ipsRegistry::dbFunctions()->getPrefix()."forum_perms(
			perm_id int(10) auto_increment NOT NULL,
			perm_name varchar(250) NOT NULL,
			PRIMARY KEY(perm_id)
		);";
		
		$SQL[] = "DROP TABLE if exists ".ipsRegistry::dbFunctions()->getPrefix()."forums_bak;";
		
		$table = $this->DB->getTableSchematic( 'forums' );
		
		$SQL[] = str_replace( ipsRegistry::dbFunctions()->getPrefix()."forums", ipsRegistry::dbFunctions()->getPrefix()."forums_bak", $table['Create Table'] );
				
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."forums_bak SELECT * FROM ".ipsRegistry::dbFunctions()->getPrefix()."forums";
		
		
		$this->sqlcount 		= 0;
		$output					= '';
		
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
		
		$oq = $this->DB->query("SELECT g_id, g_title FROM ".ipsRegistry::dbFunctions()->getPrefix()."groups ORDER BY g_id");

		while( $r = $this->DB->fetch($oq) )
		{
			$nq = $this->DB->query("REPLACE INTO ".ipsRegistry::dbFunctions()->getPrefix()."forum_perms SET perm_id={$r['g_id']}, perm_name='{$r['g_title']} Mask'");
			$bq = $this->DB->query("UPDATE ".ipsRegistry::dbFunctions()->getPrefix()."groups SET g_perm_id={$r['g_id']} WHERE g_id={$r['g_id']}");
		}
	
		$this->DB->query("update ".ipsRegistry::dbFunctions()->getPrefix()."messages SET read_state=1 where vid='sent'");
	
		$this->DB->query("DROP TABLE if exists ".ipsRegistry::dbFunctions()->getPrefix()."attachments");
		
		$this->registry->output->addMessage("Forums table backed up - creating new tables next....<br /><br />$this->sqlcount queries run....");
		
		$this->request['workact'] = 'step_2';
		
		if ( IPSSetUp::getSavedData('man') AND $output )
		{
			$this->_output = $this->registry->output->template()->upgrade_manual_queries( $output );
		}
	}
	
	
	/*-------------------------------------------------------------------------*/
	// STEP 2: DROP FORUMS TABLE, CREATE NEW TABLES
	/*-------------------------------------------------------------------------*/
	
	function step_2()
	{
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
		 msg_date int(10) default NULL,
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
		   task_next_run int(10) NOT NULL default 0,
		   task_week_day tinyint(1) NOT NULL default '-1',
		   task_month_day smallint(2) NOT NULL default '-1',
		   task_hour smallint(2) NOT NULL default '-1',
		   task_minute smallint(2) NOT NULL default '-1',
		   task_cronkey varchar(32) NOT NULL default '',
		   task_log tinyint(1) NOT NULL default '0',
		   task_description text NULL,
		   task_enabled tinyint(1) NOT NULL default '1',
		   task_key varchar(30) NOT NULL default '',
		   task_safemode tinyint(1) NOT NULL default 0,
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
		   bbcode_useoption tinyint(1) NOT NULL default 0,
		   bbcode_example text NULL,
		   PRIMARY KEY (bbcode_id)
	   );";
	   
	   $SQL[] = "CREATE TABLE ".ipsRegistry::dbFunctions()->getPrefix()."conf_settings (
		   conf_id int(10) NOT NULL auto_increment,
		   conf_title varchar(255) NOT NULL default '',
		   conf_description text NULL,
		   conf_group smallint(3) NOT NULL default 0,
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
		   converge_joined int(10) NOT NULL default 0,
		   members_pass_hash varchar(32) NOT NULL default '',
		   members_pass_salt varchar(5) NOT NULL default '',
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
	   
	   $SQL[] = "CREATE TABLE ".ipsRegistry::dbFunctions()->getPrefix()."upgrade_history (
		   upgrade_id int(10) NOT NULL auto_increment,
		   upgrade_version_id int(10) NOT NULL default 0,
		   upgrade_version_human varchar(200) NOT NULL default '',
		   upgrade_date int(10) NOT NULL default '0',
		   upgrade_mid int(10) NOT NULL default '0',
		   upgrade_notes text NULL,
		   PRIMARY KEY (upgrade_id)
	   );";
	   
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
		
		$SQL[] = "CREATE TABLE ".ipsRegistry::dbFunctions()->getPrefix()."subscriptions (
		 sub_id smallint(5) NOT NULL auto_increment,
		 sub_title varchar(250) NOT NULL default '',
		 sub_desc text NULL,
		 sub_new_group mediumint(8) NOT NULL default 0,
		 sub_length smallint(5) NOT NULL default '1',
		 sub_unit varchar(2) NOT NULL default 'm',
		 sub_cost decimal(10,2) NOT NULL default '0.00',
		 sub_run_module varchar(250) NOT NULL default '',
		 PRIMARY KEY (sub_id)
		);";
		
		$SQL[] = "CREATE TABLE ".ipsRegistry::dbFunctions()->getPrefix()."subscription_extra (
		 subextra_id smallint(5) NOT NULL auto_increment,
		 subextra_sub_id smallint(5) NOT NULL default '0',
		 subextra_method_id smallint(5) NOT NULL default '0',
		 subextra_product_id varchar(250) NOT NULL default '0',
		 subextra_can_upgrade tinyint(1) NOT NULL default '0',
		 subextra_recurring tinyint(1) NOT NULL default '0',
		 subextra_custom_1 text NULL,
		 subextra_custom_2 text NULL,
		 subextra_custom_3 text NULL,
		 subextra_custom_4 text NULL,
		 subextra_custom_5 text NULL,
		 PRIMARY KEY(subextra_id)
		);";
		
		
		$SQL[] = "CREATE TABLE ".ipsRegistry::dbFunctions()->getPrefix()."subscription_trans (
		 subtrans_id int(10) NOT NULL auto_increment,
		 subtrans_sub_id smallint(5) NOT NULL default '0',
		 subtrans_member_id mediumint(8) NOT NULL default '0',
		 subtrans_old_group smallint(5) NOT NULL default '0',
		 subtrans_paid decimal(10,2) NOT NULL default '0.00',
		 subtrans_cumulative decimal(10,2) NOT NULL default '0.00',
		 subtrans_method varchar(20) NOT NULL default '',
		 subtrans_start_date int(11) NOT NULL default '0',
		 subtrans_end_date int(11) NOT NULL default '0',
		 subtrans_state varchar(200) NOT NULL default '',
		 subtrans_trxid varchar(200) NOT NULL default '',
		 subtrans_subscrid varchar(200) NOT NULL default '',
		 subtrans_currency varchar(10) NOT NULL default 'USD',
		 PRIMARY KEY (subtrans_id)
		);";
		
		$SQL[] = "CREATE TABLE ".ipsRegistry::dbFunctions()->getPrefix()."subscription_logs (
		 sublog_id int(10) NOT NULL auto_increment,
		 sublog_date int(10) NOT NULL default '0',
		 sublog_member_id mediumint(8) NOT NULL default '0',
		 sublog_transid int(10) NOT NULL default '0',
		 sublog_ipaddress varchar(16) NOT NULL default '',
		 sublog_data text NULL,
		 sublog_postdata text NULL,
		 PRIMARY KEY (sublog_id)
		);";
		
		$SQL[] = "CREATE TABLE ".ipsRegistry::dbFunctions()->getPrefix()."subscription_methods (
		 submethod_id smallint(5) NOT NULL auto_increment,
		 submethod_title varchar(250) NOT NULL default '',
		 submethod_name varchar(20) NOT NULL default '',
		 submethod_email varchar(250) NOT NULL default '',
		 submethod_sid text NULL,
		 submethod_custom_1 text NULL,
		 submethod_custom_2 text NULL,
		 submethod_custom_3 text NULL,
		 submethod_custom_4 text NULL,
		 submethod_custom_5 text NULL,
		 submethod_is_cc tinyint(1) NOT NULL default '0',
		 submethod_is_auto tinyint(1) NOT NULL default '0',
		 submethod_desc text NULL,
		 submethod_logo text NULL,
		 submethod_active tinyint(1) NOT NULL default '0',
		 submethod_use_currency varchar(10) NOT NULL default 'USD',
		 PRIMARY KEY (submethod_id)
		);";
		
		$SQL[] = "CREATE TABLE ".ipsRegistry::dbFunctions()->getPrefix()."subscription_currency (
		 subcurrency_code varchar(10) NOT NULL,
		 subcurrency_desc varchar(250) NOT NULL default '',
		 subcurrency_exchange decimal(16, 8) NOT NULL,
		 subcurrency_default tinyint(1) NOT NULL default '0',
		 PRIMARY KEY(subcurrency_code)
		);";
		
		$SQL[]="CREATE TABLE ".ipsRegistry::dbFunctions()->getPrefix()."validating (
		vid varchar(32) NOT NULL,
		member_id mediumint(8) NOT NULL,
		real_group smallint(3) NOT NULL default '0',
		temp_group smallint(3) NOT NULL default '0',
		entry_date int(10) NOT NULL default '0',
		coppa_user tinyint(1) NOT NULL default '0',
		lost_pass tinyint(1) NOT NULL default '0',
		new_reg tinyint(1) NOT NULL default '0',
		email_chg tinyint(1) NOT NULL default '0',
		ip_address varchar(16) NOT NULL default '0',
		PRIMARY KEY(vid),
		KEY new_reg(new_reg)
		);";
		
		$SQL[]="create table ".ipsRegistry::dbFunctions()->getPrefix()."cache_store (
		  cs_key varchar(255) NOT NULL default '',
		  cs_value text NULL,
		  cs_extra varchar(255) NOT NULL default '',
		  PRIMARY KEY(cs_key)
		);";
		
		$SQL[]="create table ".ipsRegistry::dbFunctions()->getPrefix()."email_logs (
		email_id int(10) NOT NULL auto_increment,
		email_subject varchar(255) NOT NULL,
		email_content text NULL,
		email_date int(10) NOT NULL default '0',
		from_member_id mediumint(8) NOT NULL default '0',
		from_email_address varchar(250) NOT NULL,
		from_ip_address varchar(16) NOT NULL default '127.0.0.1',
		to_member_id mediumint(8) NOT NULL default '0',
		to_email_address varchar(250) NOT NULL,
		topic_id int(10) NOT NULL default '0',
		PRIMARY KEY(email_id),
		KEY from_member_id(from_member_id),
		KEY email_date(email_date)
		);";
		
		$SQL[]="CREATE TABLE ".ipsRegistry::dbFunctions()->getPrefix()."topic_mmod (
		mm_id smallint(5) NOT NULL auto_increment,
		mm_title varchar(250) NOT NULL,
		mm_enabled tinyint(1) NOT NULL default '0',
		topic_state varchar(10) NOT NULL default 'leave',
		topic_pin varchar(10) NOT NULL default 'leave',
		topic_move smallint(5) NOT NULL default '0',
		topic_move_link tinyint(1) NOT NULL default '0',
		topic_title_st varchar(250) NOT NULL default '',
		topic_title_end varchar(250) NOT NULL default '',
		topic_reply tinyint(1) NOT NULL default '0',
		topic_reply_content text NULL,
		topic_reply_postcount tinyint(1) NOT NULL default '0',
		PRIMARY KEY(mm_id)
		);";
		
		$SQL[]="create table ".ipsRegistry::dbFunctions()->getPrefix()."spider_logs (
		sid int(10) NOT NULL auto_increment,
		bot varchar(255) NOT NULL default '',
		query_string text NULL,
		entry_date int(10) NOT NULL default '0',
		ip_address varchar(16) NOT NULL default '',
		PRIMARY KEY(sid)
		);";
		
		
		
		$SQL[]="CREATE TABLE ".ipsRegistry::dbFunctions()->getPrefix()."warn_logs (
		wlog_id int(10) auto_increment NOT NULL,
		wlog_mid mediumint(8) NOT NULL default '0',
		wlog_notes text NULL,
		wlog_contact varchar(250) NOT NULL default 'none',
		wlog_contact_content text NULL,
		wlog_date int(10) NOT NULL default '0',
		wlog_type varchar(6) NOT NULL default 'pos',
		wlog_addedby mediumint(8) NOT NULL default '0',
		PRIMARY KEY(wlog_id)
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
		
		$oq = $this->DB->query("SELECT * FROM ".ipsRegistry::dbFunctions()->getPrefix()."rules");
	
		if( !$this->DB->failed )
		{
			while ( $r = $this->DB->fetch($oq) )
			{
				$nq = $this->DB->query("UPDATE ".ipsRegistry::dbFunctions()->getPrefix()."forums SET rules_title='{$r['title']}', rules_text='{$r['body']}' WHERE id={$r['fid']}");
			}
		}
		
		$this->DB->query("drop table if exists ".ipsRegistry::dbFunctions()->getPrefix()."rules");
		
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
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."topics change posts posts int(10) default null, change views views int(10) default '0';";
		
		
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
		$SQL[] = "ALTER TABLE ".ipsRegistry::dbFunctions()->getPrefix()."members ADD org_supmod TINYINT(1) DEFAULT '0';";
		$SQL[] = "ALTER TABLE ".ipsRegistry::dbFunctions()->getPrefix()."members ADD org_perm_id varchar(255) DEFAULT '';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."members drop validate_key;";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."members drop new_pass;";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."members drop prev_group;";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."members add temp_ban varchar(100);";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."members change allow_post restrict_post varchar(100) NOT NULL default '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."members change mod_posts mod_posts varchar(100) NOT NULL default '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."members add warn_lastwarn int(10) not null default '0' after warn_level;";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."member_extra add photo_type varchar(10) default '';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."member_extra add photo_location varchar(255) default '';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."member_extra add photo_dimensions varchar(200) default '';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."members add integ_msg varchar(250) default '';";
		
		
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
		
		$SQL[] = "ALTER TABLE ".ipsRegistry::dbFunctions()->getPrefix()."groups ADD g_perm_id varchar(255) NOT NULL;";
		$SQL[] = "ALTER TABLE ".ipsRegistry::dbFunctions()->getPrefix()."groups ADD g_photo_max_vars VARCHAR(200) DEFAULT '100:250:250';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."groups add g_dohtml tinyint(1) NOT NULL default '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."groups add g_edit_topic tinyint(1) NOT NULL DEFAULT '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."groups add g_email_limit varchar(15) NOT NULL DEFAULT '10:15';";
	
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."calendar_events add end_day int(2), add end_month int(2), add end_year int(4);";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."calendar_events add end_unix_stamp int(10);";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."calendar_events add event_ranged tinyint(1) NOT NULL default '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."calendar_events add event_repeat tinyint(1) NOT NULL default '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."calendar_events add repeat_unit char(2) NOT NULL default '';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."calendar_events add event_bgcolor varchar(32) NOT NULL default '';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."calendar_events add event_color varchar(32) NOT NULL default '';";
	
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."skins add css_method varchar(100) default 'inline';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."css add updated int(10) default '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."search_results add query_cache text NULL;";

		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."moderators add can_mm tinyint(1) NOT NULL default '0';";
		
		
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
		
		if ( $this->DB->getTotalRows() )
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
													  'attach_filesize'  => @filesize( IPS_ROOT_PATH.'uploads/'.$r['attach_id'] ),
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
		
		$o = $this->DB->query( $this->sql_members( $start, $end ) );
	
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
				$this->DB->execute();

				while( $r = $this->DB->fetch() )
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
		
		$o = $this->DB->query( $this->sql_members_converge( $start, $end ) );
	
		$found = 0;
	
		//-----------------------------------------
		// Do it...
		//-----------------------------------------
		
		while ( $r = $this->DB->fetch($o) )
		{
			if ( ! $r['cid'] or ! $r['id'] )
			{
				$r['password'] = $r['password'] ? $r['password'] : $r['legacy_password'];
				
				$salt = $this->install->ipsclass->converge->generate_password_salt(5);
				$salt = str_replace( '\\', "\\\\", $salt );
				
				$this->DB->insert( 'members_converge',
								array( 'converge_id'        => $r['id'],
									   'converge_email'     => strtolower($r['email']),
									   'converge_joined'    => $r['joined'],
									   'members_pass_hash' => md5( md5($salt) . $r['password'] ),
									   'members_pass_salt' => $salt
							 )       );
							 
				$member_login_key = $this->install->ipsclass->converge->generate_auto_log_in_key();
				
				$this->DB->update( 'members', array( 'member_login_key' => $member_login_key, 'email' => strtolower($r['email']) ), 'id='.$r['id'] );
				
				if( $r['id'] == IPSSetUp::getSavedData('mid') )
				{
					// Reset loginkey
					
					IPSSetUp::getSavedData('loginkey') 					= $member_login_key;
					$this->install->ipsclass->member['member_login_key']	= $member_login_key;
					IPSSetUp::getSavedData('securekey') 				= $this->install->ipsclass->return_md5_check();
				}
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
		$SQL[] = "ALTER TABLE ".ipsRegistry::dbFunctions()->getPrefix()."topics DROP INDEX forum_id;";
		$SQL[] = "ALTER TABLE ".ipsRegistry::dbFunctions()->getPrefix()."topics ADD INDEX forum_id(forum_id,approved,pinned);";
		$SQL[] = "ALTER TABLE ".ipsRegistry::dbFunctions()->getPrefix()."topics ADD INDEX(last_post);";
	
		
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
		$SQL[] = "ALTER TABLE ".ipsRegistry::dbFunctions()->getPrefix()."members add has_blog TINYINT(1) NOT NULL default '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."members add sub_end int(10) NOT NULL default '0';";
		$SQL[] = "ALTER TABLE ".ipsRegistry::dbFunctions()->getPrefix()."members DROP msg_from_id, DROP msg_msg_id;";
		$SQL[] = "ALTER TABLE ".ipsRegistry::dbFunctions()->getPrefix()."members DROP org_supmod, DROP integ_msg;";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."members DROP aim_name, DROP icq_number, DROP website, DROP yahoo, DROP interests,
				  DROP msnname, DROP vdirs, DROP signature, DROP location, DROP avatar, DROP avatar_size;";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."members change auto_track auto_track varchar(50) default '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."members add subs_pkg_chosen smallint(3) NOT NULL default '0';";
		$SQL[] = "ALTER TABLE ".ipsRegistry::dbFunctions()->getPrefix()."members change temp_ban temp_ban varchar(100) default '0';";
		$SQL[] = "ALTER TABLE ".ipsRegistry::dbFunctions()->getPrefix()."members change msg_total msg_total smallint(5) default '0';";

		
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

		$SQL[] = "insert into ".ipsRegistry::dbFunctions()->getPrefix()."subscription_currency SET subcurrency_code='USD', subcurrency_desc='United States Dollars', subcurrency_exchange='1.00', subcurrency_default=1;";
		$SQL[] = "insert into ".ipsRegistry::dbFunctions()->getPrefix()."subscription_currency SET subcurrency_code='GBP', subcurrency_desc='United Kingdom Pounds', subcurrency_exchange=' 0.630776', subcurrency_default=0;";
		$SQL[] = "insert into ".ipsRegistry::dbFunctions()->getPrefix()."subscription_currency SET subcurrency_code='CAD', subcurrency_desc='Canada Dollars', subcurrency_exchange='1.37080', subcurrency_default=0;";
		$SQL[] = "insert into ".ipsRegistry::dbFunctions()->getPrefix()."subscription_currency SET subcurrency_code='EUR', subcurrency_desc='Euro', subcurrency_exchange='0.901517', subcurrency_default=0;";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."subscription_methods (submethod_title, submethod_name, submethod_email, submethod_sid, submethod_custom_1, submethod_custom_2, submethod_custom_3, submethod_custom_4, submethod_custom_5, submethod_is_cc, submethod_is_auto, submethod_desc, submethod_logo, submethod_active, submethod_use_currency) VALUES ('PayPal', 'paypal', '', '', '', '', '', '', '', 0, 1, 'All major credit cards accepted. See <a href=\"https://www.paypal.com/affil/pal=9DJEWQQKVB6WL\" target=\"_blank\">PayPal</a> for more information.', '', 1, 'USD');";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."subscription_methods (submethod_title, submethod_name, submethod_email, submethod_sid, submethod_custom_1, submethod_custom_2, submethod_custom_3, submethod_custom_4, submethod_custom_5, submethod_is_cc, submethod_is_auto, submethod_desc, submethod_logo, submethod_active, submethod_use_currency) VALUES ('NOCHEX', 'nochex', '', '', '', '', '', '', '', 0, 1, 'UK debit and credit cards, such as Switch, Solo and VISA Delta. All prices will be convereted into GBP (UK Pounds) upon ordering.', NULL, 1, 'GBP');";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."subscription_methods (submethod_title, submethod_name, submethod_email, submethod_sid, submethod_custom_1, submethod_custom_2, submethod_custom_3, submethod_custom_4, submethod_custom_5, submethod_is_cc, submethod_is_auto, submethod_desc, submethod_logo, submethod_active, submethod_use_currency) VALUES ('Post Service', 'manual', '', '', '', '', '', '', '', 0, 0, 'You can use this method if you wish to send us a check, postal order or international money order.', NULL, 1, 'USD');";
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."subscription_methods (submethod_title, submethod_name, submethod_email, submethod_sid, submethod_custom_1, submethod_custom_2, submethod_custom_3, submethod_custom_4, submethod_custom_5, submethod_is_cc, submethod_is_auto, submethod_desc, submethod_logo, submethod_active, submethod_use_currency) VALUES ('2CheckOut', '2checkout', '', '', '', '', '', '', '', 1, 1, 'All major credit cards accepted. See <a href=\'http://www.2checkout.com/cgi-bin/aff.2c?affid=28376\' target=\'_blank\'>2CheckOut</a> for more information.', NULL, 1, 'USD');";
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
		$SQL[] = "INSERT INTO ".ipsRegistry::dbFunctions()->getPrefix()."cache_store (cs_key, cs_value, cs_extra, cs_array) VALUES ('group_cache', '', '', 1);";
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
		
	
		$SQL[] = "REPLACE INTO ".ipsRegistry::dbFunctions()->getPrefix()."faq VALUES (1, 'Registration benefits', 'To be able to use all the features on this board, the administrator will probably require that you register for a member account. Registration is free and only takes a moment to complete.\r<br>\r<br>During registration, the administrator requires that you supply a valid email address. This is important as the administrator may require that you validate your registration via an email. If this is the case, you will be notified when registering. If your e-mail does not arrive, then on the member bar at the top of the page, there will be a link that will allow you to re-send the validation e-mail. \r<br>\r<br>In some cases, the administrator will need to approve your registration before you can use your member account fully. If this is the case you will be notified during registration.\r<br>\r<br>Once you have registered and logged in, you will have access to your personal messenger and your control panel.\r<br>\r<br>For more information on these items, please see the relevant sections in this documentation.', 'How to register and the added benefits of being a registered member.');";
		$SQL[] = "REPLACE INTO ".ipsRegistry::dbFunctions()->getPrefix()."faq VALUES (2, 'Cookies and cookie usage', 'Using cookies is optional, but strongly recommended. Cookies are used to track topics, showing you which topics have new replies since your last visit and to automatically log you in when you return.\r<br>\r<br>If your computer is unable to use the cookie system to browse the board correctly, then the board will automatically add in a session ID to each link to track you around the board.\r<br>\r<br><b>Clearing Cookies</b>\r<br>\r<br>You can clear the cookies at any time by clicking on the link found at the bottom of the main board page (the first page you see when returning to the board). If this does not work for you, you may need to remove the cookies manually.\r<br>\r<br><u>Removing Cookies in Internet Explorer for Windows</u>\r<br>\r<br><ul>\r<br><li> Close all open Internet Explorer Windows\r<br><li> Click on the \'start\' button\r<br><li> Move up to \'Find\' and click on \'Files and Folders\'\r<br><li> When the new window appears, type in the domain name of the board you are using into the \'containing text\' field. (If the boards address was \'http://www./forums/index.php\' you would enter \'\' without the quotes)\r<br><li> In the \'look in\' box, type in <b>C:WindowsCookies</b> and press \'Find Now\'\r<br><li> After it has finished searching, highlight all files (click on a file then press CTRL+A) and delete them.\r<br></ul>\r<br>\r<br><u>Removing Cookies in Internet Explorer for Macintosh</u>\r<br>\r<br><ul>\r<br><li> With Internet Explorer active, choose \'Edit\' and then \'Preferences\' from the Macintosh menu bar at the top of the screen\r<br><li> When the preferences panel opens, choose \'Cookies\' found in the \'Receiving Files\' section.\r<br><li> When the cookie pane loads, look for the domain name of the board (If the boards address was \'http://www./forums/index.php\' look for \'\' or \'www.\'\r<br><li> For each cookie, click on the entry and press the delete button.\r<br></ul>\r<br>\r<br>Your cookies should now be removed. In some cases you may need to restart your computer for the changes to take effect.', 'The benefits of using cookies and how to remove cookies set by this board.');";
		$SQL[] = "REPLACE INTO ".ipsRegistry::dbFunctions()->getPrefix()."faq VALUES (3, 'Recovering lost or forgotten passwords', 'Security is a big feature on this board, and to that end, all passwords are encrypted when you register.\r<br>This means that we cannot email your password to you as we hold no record of your \'uncrypted\' password. You can however, apply to have your password reset.\r<br>\r<br>To do this, click on the <a href=\'index.php?act=Reg&do=10\'>Lost Password link</a> found on the log in page.\r<br>\r<br>Further instruction is available from there.', 'How to reset your password if you\'ve forgotton it.');";
		$SQL[] = "REPLACE INTO ".ipsRegistry::dbFunctions()->getPrefix()."faq VALUES (4, 'Your Control Panel (My Controls)', 'Your control panel is your own private board console. You can change how the board looks and feels as well as your own information from here.\r<br>\r<br><b>Subscriptions</b>\r<br>\r<br>This is where you manage your topic and forums subscriptions. Please see the help file \'Email Notification of new messages\' for more information on how to subscribe to topics.\r<br>\r<br><b>Edit Profile Info</b>\r<br>\r<br>This section allows you to add or edit your contact information and enter some personal information if you choose.\r<br>\r<br><b>Edit Signature</b>\r<br>\r<br>A board \'signature\' is very similar to an email signature. This signature is attached to the foot of every message you post unless you choose to check the box that allows you to ommit the signature in the message you are posting. You may use BB Code if available and in some cases, pure HTML (if the board administrator allows it).\r<br>\r<br><b>Edit Avatar Settings</b>\r<br>\r<br>An avatar is a little image that appears under your username when you view a topic or post you authored. If the administrator allows, you may either choose from the board gallery, enter a URL to an avatar stored on your server or upload an avatar to use. You may also set the width of the avatar to ensure that it\'s sized in proportion.\r<br>\r<br><b>Change Personal Photo</b>\r<br>\r<br>This section will allow you to add a photograph to your profile. This will be displayed when a user clicks to view your profile, on the mini-profile screen and will also be linked to from the member list.\r<br>\r<br><b>Email Settings</b>\r<br>\r<br><u>Hide my email address</u> allows you to deny the ability for other users to send you an email from the board.\r<br><u>Send me updates sent by the board administrator</u> will allow the administrator to include your email address in any mailings they send out - this is used mostly for important updates and community information.\r<br><u>Include a copy of the post when emailing me from a subscribed topic</u>, this allows you to have the new post included in any reply to topic notifications.\r<br><u>Send a confirmation email when I receive a new private message</u>, this will send you an e-mail notification to your registered e-mail address each time you receive a private message on the board.\r<br><u>Enable \'Email Notification\' by default?</u>, this will automatically subscribe you to any topic that you make a reply to. You may unsubscribe from the \'Subscriptions\' section of My Controls if you wish.\r<br>\r<br><b>Board Settings</b>\r<br>\r<br>From this section, you can set your time zone, choose to not see users signatures, avatars and posted images.\r<br>You can choose to get a pop up window informing you when you have a new message and choose to show or hide the \'Fast Reply\' box where it is enabled.\r<br>You are also able to choose display preferences for the number of topics/posts shown per page on the board.\r<br>\r<br><b>Skins and Languages</b>\r<br>\r<br>If available, you can choose a skin style and language choice. This affects how the board is displayed so you may wish to preview the skin before submitting the form.\r<br>\r<br><b>Change Email Address</b>\r<br>\r<br>At any time, you can change the email address that is registered to your account. In some cases, you will need to revalidate your account after changing your email address. If this is the case, you will be notified before your email address change is processed.\r<br>\r<br><b>Change Password</b>\r<br>\r<br>You may change your password from this section. Please note that you will need to know your current password before you can change your password.', 'Editing contact information, personal information, avatars, signatures, board settings, languages and style choices.');";
		$SQL[] = "REPLACE INTO ".ipsRegistry::dbFunctions()->getPrefix()."faq VALUES (5, 'Email Notification of new messages', 'This board can notify you when a new reply is added to a topic. Many users find this useful to keep up to date on topics without the need to view the board to check for new messages.\r<br>\r<br>There are three ways to subscribe to a topic:\r<br>\r<br><li>Click the \'Track This Topic\' link at the top of the topic that you wish to track\r<br><li> On the posting screen when replying to or creating a topic, check the \'Enable email notification of replies?\' checkbox\r<br><li> From the E-Mail settings section of your User CP (My Controls) check the \'Enable Email Notification by default?\' option, this will automatically subscribe you to any topic that you make a reply to\r<br>\r<br>Please note that to avoid multiple emails being sent to your email address, you will only get one e-mail for each topic you are subscribed to until the next time you visit the board.\r<br>\r<br>You are also able to subscribe to each individual forum on the board, to be notified when a new topic is created in that particular forum. To enable this, click the \'Subscribe to this forum\' link at the bottom of the forum that you wish to subscribe to.\r<br>\r<br>To unsubscribe from any forums or topics that you are subscribed to - just go to the \'Subscriptions\' section of \'My Controls\' and you can do this from there.', 'How to get emailed when a new reply is added to a topic.');";
		$SQL[] = "REPLACE INTO ".ipsRegistry::dbFunctions()->getPrefix()."faq VALUES (6, 'Your Personal Messenger', 'Your personal messenger acts much like an email account in that you can send and receive messages and store messages in folders.\r<br>\r<br><b>Send a new PM</b>\r<br>\r<br>This will allow you to send a message to another member. If you have names in your contact list, you can choose a name from it - or you may choose to enter a name in the relevant form field. This will be automatically filled in if you clicked a \'PM\' button on the board (from the member list or a post). If allowed, you may also be able to enter in multiple names in the box provided, will need to add one username per line.\r<br>If the administrator allows, you may use BB Code and HTML in your private message. If you choose to check the \'Add a copy of this message to you sent items folder\' box, a copy of the message will be saved for you for later reference. If you tick the \'Track this message?\' box, then the details of the message will be available in your \'Message Tracker\' where you will be able to see if/when it has been read.\r<br>\r<br><b>Go to Inbox</b>\r<br>\r<br>Your inbox is where all new messages are sent to. Clicking on the message title will show you the message in a similar format to the board topic view. You can also delete or move messages from your inbox.\r<br>\r<br><b>Empty PM Folders</b>\r<br>\r<br>This option provides you with a quick and easy way to clear out all of your PM folders.\r<br>\r<br><b>Edit Storage Folders</b>\r<br>\r<br>You may rename, add or remove folders to store messages is, allowing you to organise your messages to your preference. You cannot remove \'Sent Items\' or \'Inbox\'.\r<br>\r<br><b>PM Buddies/Block List</b>\r<br>\r<br>You may add in users names in this section, or edit any saved entries. You can also use this as a ban list, denying the named member the ability to message you.\r<br>Names entered in this section will appear in the drop down list when sending a new PM, allowing you to quickly choose the members name when sending a message.\r<br>\r<br><b>Archive Messages</b>\r<br>\r<br>If your messenger folders are full and you are unable to receive new messages, you can archive them off. This compiles the messages into a single HTML page or Microsoft © Excel Format. This page is then emailed to your registered email address for your convenience.\r<br>\r<br><b>Saved (Unsent) PMs</b>\r<br>\r<br>This area will allow you to go back to any PM\'s that you have chosen to save to be sent later.\r<br>\r<br><b>Message Tracker</B>\r<br>\r<br>This is the page that any messages that you have chosen to track will appear. Details of if and when they have been read by the recipient will appear here. This also gives you the chance to delete any messages that you have sent and not yet been read by the intended recipient.', 'How to send personal messages, track them, edit your messenger folders and archive stored messages.');";
		$SQL[] = "REPLACE INTO ".ipsRegistry::dbFunctions()->getPrefix()."faq VALUES (7, 'Contacting the moderating team & reporting posts', '<b>Contacting the moderating team</b>\r<br>\r<br>If you need to contact a moderator or simply wish to view the complete administration team, you can click the link \'The moderating team\' found at the bottom of the main board page (the first page you see when visiting the board), or from \'My Assistant\'.\r<br>\r<br>This list will show you administrators (those who have administration control panel access), global moderators (those who can moderate in all forums) and the moderators of the individual forums.\r<br>\r<br>If you wish to contact someone about your member account, then contact an administrator - if you wish to contact someone about a post or topic, contact either a global moderator or the forum moderator.\r<br>\r<br><b>Reporting a post</b>\r<br>\r<br>If the administrator has enabled this function on the board, you\'ll see a \'Report\' button in a post, next to the \'Quote\' button. This function will let you report the post to the forum moderator (or the administrator(s), if there isn\'t a specific moderator available). You can use this function when you think the moderator(s) should be aware of the existance of that post. However, <b>do not use this to chat with the moderator(s)!</b>. You can use the email function or the Personal Messenger function for that.', 'Where to find a list of the board moderators and administrators.');";
		$SQL[] = "REPLACE INTO ".ipsRegistry::dbFunctions()->getPrefix()."faq VALUES (8, 'Viewing members profile information', 'You can view a members profile at any time by clicking on their name when it is underlined (as a link) or by clicking on their name in a post within a topic.\r<br>\r<br>This will show you their profile page which contains their contact information (if they have entered some) and their \'active stats\'.\r<br>\r<br>You can also click on the \'Mini Profile\' button underneath their posts, this will show up a mini \'e-card\' with their contact information and a photograph if they have chosen to have one.', 'How to view members contact information.');";
		$SQL[] = "REPLACE INTO ".ipsRegistry::dbFunctions()->getPrefix()."faq VALUES (9, 'Viewing active topics and new posts', 'You can view which new topics have new replies today by clicking on the \'Today\'s Active Topics\' link found at the bottom of the main board page (the first page you see when visiting the board). You can set your own date criteria, choosing to view all topics  with new replies during several date choices.\r<br>\r<br>The \'View New Posts\' link in the member bar at the top of each page, will allow you to view all of the topics which have new replies in since your last visit to the board.', 'How to view all the topics which have a new reply today and the new posts made since your last visit.');";
		$SQL[] = "REPLACE INTO ".ipsRegistry::dbFunctions()->getPrefix()."faq VALUES (10, 'Searching Topics and Posts', 'The search feature is designed to allow you to quickly find topics and posts that contain the keywords you enter.\r<br>\r<br>There are two types of search form available, simple search and advanced search. You may switch between the two using the \'More Options\' and \'Simple Mode\' buttons.\r<br>\r<br><b>Simple Mode</b>\r<br>\r<br>All you need to do here is enter in a keyword into the search box, and select a forum(s) to search in. (to select multiple forums, hold down the control key on a PC, or the Shift/Apple key on a Mac) choose a sorting order and search.\r<br>\r<br><b>Advanced Mode</b>\r<br>\r<br>The advanced search screen, will give you a much greater range of options to choose from to refine your search. In addition to searching by keyword, you are able to search by a members username or a combination of both. You can also choose to refine your search by selecting a date range, and there are a number of sorting options available. There are also two ways of displaying the search results, can either show the post text in full or just show a link to the topic, can choose this using the radio buttons available.\r<br>\r<br>If the administrator has enabled it, you may have a minimum amount of time to wait between searches, this is known as search flood control.\r<br>\r<br>There are also search boxes available at the bottom of each forum, to allow you to carry out a quick search of all of the topics within that particular forum.', 'How to use the search feature.');";
		$SQL[] = "REPLACE INTO ".ipsRegistry::dbFunctions()->getPrefix()."faq VALUES (11, 'Logging in and out', 'If you have chosen not to remember your log in details in cookies, or you are accessing the board on another computer, you will need to log into the board to access your member profile and post with your registered name.\r<br>\r<br>When you log in, you have the choice to save cookies that will log you in automatically when you return. Do not use this option on a shared computer for security.\r<br>\r<br>You can also choose to hide - this will keep your name from appearing in the active users list.\r<br>\r<br>Logging out is simply a matter of clicking on the \'Log Out\' link that is displayed when you are logged in. If you find that you are not logged out, you may need to manually remove your cookies. See the \'Cookies\' help file for more information.', 'How to log in and out from the board and how to remain anonymous and not be shown on the active users list.');";
		$SQL[] = "REPLACE INTO ".ipsRegistry::dbFunctions()->getPrefix()."faq VALUES (13, 'My Assistant', 'This feature is sometimes referred to as a \'Browser Buddy\'. \r<br>\r<br>At the top it tells you how many posts have been made since you last visited the board.. Also underneath this the number of posts with replies that have been made in topics that the individual has also posted in.\r<br>Click on the \'View\' link on either of the two sentences to see the posts.\r<br>\r<br>The next section is five links to useful features:\r<br>\r<br><li>The link to the moderating team is basically a quick link to see all those that either administrate or moderate certain forums on the message board.\r<br><li> The link to \'Today\'s Active Topics\' shows you all the topics that have been created in the last 24 hours on the board.\r<br><li>Today\'s Top 10 Posters link shows you exactly as the name suggests. It shows you the amount of posts by the members and also their total percentage of the total posts made that day.\r<br><li>The overall Top 10 Posters link shows you the top 10 posters for the whole time that the board has been installed.\r<br><li>My last 10 posts links to the latest topics that you have made on the board. These are shortened on the page, to save space, and are linked to if you require to read more of them.\r<br>\r<br>The two search features allow you to search the whole board for certain words in a whole topic. It isn\'t as featured as the normal search option so it is not as comprehensive.\r<br>\r<br>The Help Search is just as comprehensive as the normal help section\'s search function and allows for quick searching of all the help topics on the board.', 'A comprehensive guide to use this handy little feature.');";
		$SQL[] = "REPLACE INTO ".ipsRegistry::dbFunctions()->getPrefix()."faq VALUES (12, 'Posting', 'There are three different posting screens available. The new topic button, visible in forums and in topics allows you to add a new topic to that particular forum. The new poll button (is the admin has enabled it) will also be viewable in topics and forums allowing you to create a new poll in the forum. When viewing a topic, there will be an add reply button, allowing you to add a new reply onto that particular topic. \r\n<br>\r\n<br><b>Posting new topics and replying</b>\r\n<br>\r\n<br>When making a post, you will most likely have the option to use IBF code when posting. This will allow you to add various types of formatting to your messages. For more information on this, click the \'BB Code Help\' link under the emoticon box to launch the help window.\r\n<br>\r\n<br>On the left of the text entry box, there is the clickable emoticons box - you can click on these to add them to the content of your message (these are sometimes known as \'smilies\').\r\n<br>\r\n<br>There are three options available when making a post or a reply. \'Enable emoticons?\' if this is unchecked, then any text that would normally be converted into an emoticon will not be. \'Enable signature?\' allows you to choose whether or not you would like your signature to appear on that individual post. \'Enable email notification of replies?\' ticking this box will mean that you will receive e-mail updates to the topic, see the \'Email Notification of new messages\' help topic for more information on this.\r\n<br>\r\n<br>You also have the option to choose a post icon for the topic/post when creating one. This icon will appear next to the topic name on the topic listing in that forum, or will appear next to the date/time of the message if making a reply to a topic.\r\n<br>\r\n<br>If the admin has enabled it, you will also see a file attachments option, this will allow you to attach a file to be uploaded when making a post. Click the browse button to select a file from your computer to be uploaded. If you upload an image file, it may be shown in the content of the post, all other file types will be linked to.\r\n<br>\r\n<br><b>Poll Options</b>\r\n<br>\r\n<br>If you have chosen to post a new poll, there will be an extra two option boxes at the top of the help screen. The first input box will allow you to enter the question that you are asking in the poll. The text field underneath is where you will input the choices for the poll. Simply enter a different option on each line. The maximum number of choices is set by the board admin, and this figure is displayed on the left.\r\n<br>\r\n<br><b>Quoting Posts</b>\r\n<br>\r\n<br>Displayed above each post in a topic, there is a \'Quote\' button. Pressing this button will allow you to reply to a topic, and have the text from a particular reply quoted in your own reply. When you choose to do this, an extra text field will appear below the main text input box to allow you to edit the content of the post being quoted.\r\n<br>\r\n<br><b>Editing Posts</b>\r\n<br>\r\n<br>Above any posts that you have made, you may see an \'Edit\' button. Pressing this will allow you to edit the post that you had previously made. \r\n<br>\r\n<br>When editing you may see an option to \'Add the \'Edit by\' line in this post?\'. If you tick this then it will show up in the posts that it has been edited and the time at which it was edited. If this option does not appear, then the edit by line will always be added to the post.\r\n<br>\r\n<br>If you are unable to see the edit button displayed on each post that you have made, then the administrator may have prevented you from editing posts, or the time limit for editing may have expired.\r\n<br>\r\n<br><b>Fast Reply</b>\r\n<br>\r\n<br>Where it has been enabled, there will be a fast reply button on each topic. Clicking this will open up a posting box on the topic view screen, cutting down on the time required to load the main posting screen. Click the fast reply button to expand the reply box and type the post inside of there. Although the fast reply box is not expanded by default, you can choose the option to have it expanded by default, from the board settings section of your control panel. Pressing the \'More Options\' button will take you to the normal posting screen.', 'A guide to the features avaliable when posting on the boards.');";
		$SQL[] = "REPLACE INTO ".ipsRegistry::dbFunctions()->getPrefix()."faq VALUES (14, 'Member List', 'The member list, accessed via the \'Members\' link at the top of each page, is basically a listing of all of the members that have registered on the board. \r\n<br>\r\n<br>If you are looking to search for a particular member by all/part of their username, then in the drop down box at the bottom of the page, change the selection from \'Search All Available\' to \'Name Begins With\' or \'Name Contains\' and input all/part of their name in the text input field and press the \'Go!\' button. \r\n<br>\r\n<br>Also, at the bottom of the member list page, there are a number of sorting options available to alter the way in which the list is displayed. \r\n<br>\r\n<br>If a member has chosen to add a photo to their profile information, then a camera icon will appear next to their name, and you may click this to view the photo.', 'Explaining the different ways to sort and search through the list of members.');";
		$SQL[] = "REPLACE INTO ".ipsRegistry::dbFunctions()->getPrefix()."faq VALUES (15, 'Topic Options', 'At the bottom of each topic, there is a \'Topic Options\' button. Pressing this button will expand the topic options box. \r\n<br>\r\n<br>From this box, you can select from the following options: \r\n<br>\r\n<br><li>Track this topic - this option will allow you to receive e-mail updates for the topic, see the \'Email Notification of new messages\' help file for more information on this \r\n<br><li>Subscribe to this forum - will allow you to receive e-mail updates for any new topics posted in the forum, see the Notification of new messages\' help file for more information on this \r\n<br><li>Download / Print this Topic - will show the topic in a number of different formats. \'Printer Friendly Version\' will display a version of the topic that is suitable for printing out. \'Download HTML Version\' will download a copy of the topic to your hard drive, and this can then be viewed in a web browser, without having to visit the board. \'Download Microsoft Word Version\' will allow you to download the file to your hard drive and open it up in the popular word processing application, Microsoft Word, for viewing offline.', 'A guide to the options avaliable when viewing a topic.');";
		$SQL[] = "REPLACE INTO ".ipsRegistry::dbFunctions()->getPrefix()."faq VALUES (16, 'Calendar', 'This board features it\'s very own calendar feature, which can be accessed via the calendar link at the top of the board.\r\n<br>\r\n<br>You are able to add your own personal events to the calendar - and these are only viewable by yourself. To add a new event, use the \'Add New Event\' button to be taken to the event posting screen. There are three types of events that you can now add:\r\n<br>\r\n<br><li>A single day/one off event can be added using the first option, by just selecting the date for it to appear on.\r\n<br><li>Ranged Event - is an event that spans across multiple days, to do this in addition to selecting the start date as above, will need to add the end date for the event. There are also options available  to highlight the message on the calendar, useful if there is more than one ranged event being displayed at any one time.\r\n<br><li>Recurring Event - is a one day event, that you can set to appear at set intervals on the calendar, either weekly, monthly or yearly.\r\n<br>\r\n<br>If the admistrator allows you, you may also be able to add a public event, that will not just be shown to yourself, but will be viewable by everyone.\r\n<br>\r\n<br>Also, if the admistrator has chosen,  there will be a link to all the birthdays happening on a particular day displayed on the calendar, and your birthday will appear if you have chosen to enter a date of birth in the Profile Info section of your control panel.', 'More information on the boards calendar feature.');";


		
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
		
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."calendar_events change eventid eventid mediumint(8) NOT NULL auto_increment;";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."calendar_events change userid userid mediumint(8) NOT NULL default '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."contacts change id id mediumint(8) NOT NULL auto_increment;";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."contacts change contact_id contact_id mediumint(8) NOT NULL;";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."contacts change member_id member_id mediumint(8) NOT NULL;";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."faq change id id mediumint(8) NOT NULL auto_increment;";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."forum_tracker change frid frid mediumint(8) NOT NULL auto_increment;";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."forum_tracker change forum_id forum_id smallint(5) NOT NULL default '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."forums change last_poster_id last_poster_id mediumint(8) NOT NULL DEFAULT '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."languages change lid lid mediumint(8) NOT NULL auto_increment;";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."member_extra change id id mediumint(8) NOT NULL;";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."members change id id mediumint(8) NOT NULL;";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."members change member_group_id mgroup smallint(3) NOT NULL;";

		
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."moderator_logs change id id int(10) NOT NULL auto_increment;";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."moderator_logs change topic_id topic_id int(10) NOT NULL default '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."moderator_logs change post_id post_id int(10);";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."moderator_logs change member_id member_id mediumint(8) NOT NULL default '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."moderator_logs change ip_address ip_address VARCHAR(16) NOT NULL default '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."moderators change mid mid mediumint(8) NOT NULL auto_increment;";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."moderators change member_id member_id mediumint(8) NOT NULL default '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."pfields_content change member_id member_id mediumint(8) NOT NULL default '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."polls change pid pid mediumint(8) NOT NULL auto_increment;";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."polls change tid tid int(10) NOT NULL default '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."polls change starter_id starter_id mediumint(8) NOT NULL default '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."polls change votes votes smallint(5) NOT NULL default '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."polls change forum_id forum_id smallint(5) NOT NULL default '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."posts change pid pid int(10) NOT NULL auto_increment;";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."posts change author_id author_id mediumint(8) NOT NULL default '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."posts change topic_id topic_id int(10) NOT NULL default '0';";

		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."posts change ip_address ip_address varchar(16) NOT NULL DEFAULT '';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."posts change use_sig use_sig tinyint(1) NOT NULL DEFAULT '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."posts change use_emo use_emo tinyint(1) NOT NULL DEFAULT '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."sessions change member_id member_id mediumint(8) NOT NULL default '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."sessions change in_forum in_forum smallint(5) NOT NULL default '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."stats change TOTAL_REPLIES TOTAL_REPLIES int(10) NOT NULL default '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."stats change TOTAL_TOPICS TOTAL_TOPICS int(10) NOT NULL default '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."stats change LAST_MEM_ID LAST_MEM_ID mediumint(8) NOT NULL default '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."stats change MEM_COUNT MEM_COUNT mediumint(8) NOT NULL default '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."topics change tid tid int(10) NOT NULL auto_increment;";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."topics change starter_id starter_id mediumint(8) NOT NULL default '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."topics change last_poster_id last_poster_id mediumint(8) NOT NULL DEFAULT '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."tracker change trid trid mediumint(8) NOT NULL auto_increment;";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."tracker change member_id member_id mediumint(8) NOT NULL default '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."voters change vid vid int(10) NOT NULL auto_increment;";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."voters change tid tid int(10) NOT NULL default '0';";
		$SQL[] = "alter table ".ipsRegistry::dbFunctions()->getPrefix()."voters change forum_id forum_id smallint(5) NOT NULL default '0';";
		
		$SQL[] = "UPDATE ".ipsRegistry::dbFunctions()->getPrefix()."members SET language='';";
		
		
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
		
		$SQL[] = "update ".ipsRegistry::dbFunctions()->getPrefix()."members SET restrict_post=0";
		
		
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
		
		$this->registry->output->addMessage("Optimization completed, new skins import next...<br /><br />$this->sqlcount queries run....");
		$this->request['workact'] = 'step_22';	
		
		if ( IPSSetUp::getSavedData('man') AND $output )
		{
			$this->_output = $this->registry->output->template()->upgrade_manual_queries( $output );
		}
	}
	
	
	/*-------------------------------------------------------------------------*/
	// STEP 22: IMPORT SKINS & SETTINGS
	/*-------------------------------------------------------------------------*/
	
	function step_22()
	{
		//-----------------------------------------
		// Get old skins data
		//-----------------------------------------
		
		$this->DB->build( array( 'select' => '*', 'from' => 'skins' ) );
		$outer = $this->DB->execute();
		
		while( $r = $this->DB->fetch( $outer ) )
		{
			//-----------------------------------------
			// Get CSS
			//-----------------------------------------
			
			$css = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'css', 'where' => 'cssid='.$r['css_id'] ) );
			
			//-----------------------------------------
			// Get Wrapper
			//-----------------------------------------
			
			$wrapper = $this->DB->buildAndFetch( array( 'select' => '*', 'from' => 'templates', 'where' => 'tmid='.$r['tmpl_id'] ) );
			
			//-----------------------------------------
			// Insert...
			//-----------------------------------------
			
			$this->DB->insert( 'skin_sets', array(
												'set_name'            => $r['sname'],
												'set_image_dir'       => $r['img_dir'],
												'set_hidden'          => 1,
												'set_default'         => 0,
												'set_css_method'      => 0,
												'set_skin_set_parent' => 3,
												'set_author_email'    => '',
												'set_author_name'     => 'IPB 2.0 Import',
												'set_author_url'      => '',
												'set_css'             => stripslashes($css['css_text']),
												'set_wrapper'         => stripslashes($wrapper['template']),
												'set_emoticon_folder' => 'default',
						 )                    );
			
			$new_id = $this->DB->getInsertId();
			 
			//-----------------------------------------
			// Update templates
			//-----------------------------------------
			
			$this->DB->update( 'skin_templates', array( 'set_id' => $new_id ), 'set_id='.$r['set_id'] );
			
			//-----------------------------------------
			// Update macros
			//-----------------------------------------
			
			$this->DB->update( 'skin_macro', array( 'macro_set' => $new_id ), 'macro_set='.$r['set_id'] );
		}
		
		//-----------------------------------
		// Get XML
		//-----------------------------------
		
		$xml = new class_xml();
		$xml->lite_parser = 1;		
		
		$this->registry->output->addMessage("Skins imported, importing settings...");
		$this->request['workact'] = 'step_24';		
	}
	
	
	/*-------------------------------------------------------------------------*/
	// STEP 23: IMPORT SETTINGS
	/*-------------------------------------------------------------------------*/
	
	function step_23()
	{
		global $INFO;
		

		$this->registry->output->addMessage("Settings imported, recache & rebuild next...");
		$this->request['workact'] = 'step_24';			
	}	

	/*-------------------------------------------------------------------------*/
	// STEP 24: RECACHE & REBUILD
	/*-------------------------------------------------------------------------*/
	
	function step_24()
	{
		//-------------------------------------------------------------
		// Forum cache
		//-------------------------------------------------------------
		
		$this->install->ipsclass->updateForumCache();
	
		//-------------------------------------------------------------
		// Group Cache
		//-------------------------------------------------------------
		
		$this->caches['group_cache'] = array();
	
		$this->DB->build( array( 'select' => "*",
									  'from'   => 'groups'
							 )      );
		
		$this->DB->execute();
		
		while ( $i = $this->DB->fetch() )
		{
			$this->caches['group_cache'][ $i['g_id'] ] = $i;
		}
		
		$this->install->ipsclass->update_cache( array( 'name' => 'group_cache', 'array' => 1, 'deletefirst' => 1 ) );
		
		//-------------------------------------------------------------
		// Systemvars
		//-------------------------------------------------------------
		
		$this->caches['systemvars'] = array();
		
		$result = $this->DB->buildAndFetch( array( 'select' => 'count(*) as cnt', 'from' => 'mail_queue' ) );
		
		$this->caches['systemvars']['mail_queue'] = intval( $result['cnt'] );
		$this->caches['systemvars']['task_next_run'] = time() + 3600;
		
		$this->install->ipsclass->update_cache( array( 'name' => 'systemvars', 'array' => 1, 'deletefirst' => 1 ) );
			
		//-------------------------------------------------------------
		// Stats
		//-------------------------------------------------------------
		
		$this->caches['stats'] = array();
		
		$this->DB->build( array( 'select' => 'count(pid) as posts', 'from' => 'posts', 'where' => "queued <> 1" ) );
		$this->DB->execute();
	
		$r = $this->DB->fetch();
		$stats['total_replies'] = intval($r['posts']);
		
		$this->DB->build( array( 'select' => 'count(tid) as topics', 'from' => 'topics', 'where' => "approved = 1" ) );
		$this->DB->execute();
		
		$r = $this->DB->fetch();
		$stats['total_topics']   = intval($r['topics']);
		$stats['total_replies'] -= $stats['total_topics'];
		
		$this->DB->build( array( 'select' => 'count(id) as members', 'from' => 'members', 'where' => "member_group_id <> '".$this->settings['auth_group']."'" ) );
		$this->DB->execute();
		
		$r = $this->DB->fetch();
		$stats['mem_count'] = intval($r['members']);
			
		$this->caches['stats']['total_replies'] = $stats['total_replies'];
		$this->caches['stats']['total_topics']  = $stats['total_topics'];
		$this->caches['stats']['mem_count']     = $stats['mem_count'];
		
		$r = $this->DB->buildAndFetch( array( 'select' => 'id, name',
											'from'   => 'members',
											'order'  => 'id DESC',
											'limit'  => '0,1'
								   )      );
								   
		$this->caches['stats']['last_mem_name'] = $r['name'];
		$this->caches['stats']['last_mem_id']   = $r['id'];
		
		$this->install->ipsclass->update_cache( array( 'name' => 'stats', 'array' => 1, 'deletefirst' => 1 ) );
			
		//-------------------------------------------------------------
		// Ranks
		//-------------------------------------------------------------
		
		$this->caches['ranks'] = array();
	
		$this->DB->build( array( 'select' => 'id, title, pips, posts',
									  'from'   => 'titles',
									  'order'  => "posts DESC",
							)      );
							
		$this->DB->execute();
					
		while ($i = $this->DB->fetch())
		{
			$this->caches['ranks'][ $i['id'] ] = array(
														  'TITLE' => $i['title'],
														  'PIPS'  => $i['pips'],
														  'POSTS' => $i['posts'],
														);
		}
		
		$this->install->ipsclass->update_cache( array( 'name' => 'ranks', 'array' => 1, 'deletefirst' => 1 ) );
			
		
		//-------------------------------------------------------------
		// SETTINGS
		//-------------------------------------------------------------
		
		$this->caches['settings'] = array();
	
		$this->DB->build( array( 'select' => '*', 'from' => 'conf_settings', 'where' => 'conf_add_cache=1' ) );
		$info = $this->DB->execute();
	
		while ( $r = $this->DB->fetch($info) )
		{
			$this->caches['settings'][ $r['conf_key'] ] = $r['conf_value'] != "" ? $r['conf_value'] : $r['conf_default'];
		}
		
		$this->install->ipsclass->update_cache( array( 'name' => 'settings', 'array' => 1, 'deletefirst' => 1 ) );
			
		//-------------------------------------------------------------
		// EMOTICONS
		//-------------------------------------------------------------
		
		$this->caches['emoticons'] = array();
				
		$this->DB->build( array( 'select' => 'typed,image,clickable,emo_set', 'from' => 'emoticons' ) );
		$this->DB->execute();
	
		while ( $r = $this->DB->fetch() )
		{
			$this->caches['emoticons'][] = $r;
		}
		
		$this->install->ipsclass->update_cache( array( 'name' => 'emoticons', 'array' => 1, 'deletefirst' => 1 ) );
		
		//-------------------------------------------------------------
		// LANGUAGES
		//-------------------------------------------------------------
		
		$this->caches['languages'] = array();
	
		$this->DB->build( array( 'select' => 'ldir,lname', 'from' => 'languages' ) );
		$this->DB->execute();
		
		while ( $r = $this->DB->fetch() )
		{
			$this->caches['languages'][] = $r;
		}
		
		$this->install->ipsclass->update_cache( array( 'name' => 'languages', 'array' => 1, 'deletefirst' => 1 ) );
			
		//-------------------------------------------------------------
		// ATTACHMENT TYPES
		//-------------------------------------------------------------
			
		$this->caches['attachtypes'] = array();
	
		$this->DB->build( array( 'select' => 'atype_extension,atype_mimetype,atype_post,atype_photo,atype_img', 'from' => 'attachments_type', 'where' => "atype_photo=1 OR atype_post=1" ) );
		$this->DB->execute();
	
		while ( $r = $this->DB->fetch() )
		{
			$this->caches['attachtypes'][ $r['atype_extension'] ] = $r;
		}
		
		$this->install->ipsclass->update_cache( array( 'name' => 'attachtypes', 'array' => 1, 'deletefirst' => 1 ) );
		
		$this->registry->output->addMessage("Data recached...");
		unset($this->request['workact']);
		unset($this->request['st']);
		unset(IPSSetUp::getSavedData('vid'));
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