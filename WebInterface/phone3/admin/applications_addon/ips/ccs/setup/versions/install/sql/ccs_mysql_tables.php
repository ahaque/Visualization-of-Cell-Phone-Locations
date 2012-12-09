<?php

$TABLE[]	= "CREATE TABLE ccs_blocks (
  block_id mediumint(9) NOT NULL auto_increment,
  block_active tinyint(1) NOT NULL default '0',
  block_name varchar(255) NOT NULL,
  block_description text,
  block_key varchar(255) NOT NULL,
  block_type varchar(32) NOT NULL,
  block_config text,
  block_content mediumtext,
  block_cache_ttl varchar(13) NOT NULL default '0',
  block_cache_last int(11) NOT NULL default '0',
  block_cache_output mediumtext,
  block_position mediumint(9) NOT NULL default '0',
  block_category mediumint(9) NOT NULL default '0',
  PRIMARY KEY  (block_id),
  KEY block_cache_ttl (block_cache_ttl),
  KEY block_active (block_active),
  KEY block_key (block_key),
  KEY block_category (block_category)
);";

$TABLE[]	= "CREATE TABLE ccs_block_wizard (
  wizard_id varchar(32) NOT NULL,
  wizard_step smallint(6) NOT NULL default '0',
  wizard_type varchar(32) default NULL,
  wizard_name varchar(255) NOT NULL,
  wizard_config longtext,
  wizard_started VARCHAR( 13 ) NOT NULL DEFAULT '0',
  PRIMARY KEY  (wizard_id)
);";

$TABLE[]	= "CREATE TABLE ccs_containers (
  container_id int(11) NOT NULL auto_increment,
  container_name varchar(255) default NULL,
  container_type varchar(32) NOT NULL,
  container_order mediumint(9) NOT NULL default '0',
  PRIMARY KEY  (container_id)
);";

$TABLE[]	= "CREATE TABLE ccs_folders (
  folder_path text,
  last_modified varchar(13) NOT NULL default '0'
);";

$TABLE[]	= "CREATE TABLE ccs_pages (
  page_id int(11) NOT NULL auto_increment,
  page_name varchar(255) default NULL,
  page_seo_name varchar(255) default NULL,
  page_description text,
  page_folder varchar(255) default NULL,
  page_type varchar(32) default NULL,
  page_last_edited varchar(13) NOT NULL default '0',
  page_template_used int(11) NOT NULL default '0',
  page_content mediumtext,
  page_cache mediumtext,
  page_view_perms text,
  page_cache_ttl varchar(13) NOT NULL default '0',
  page_cache_last varchar(13) NOT NULL default '0',
  page_content_only tinyint(1) NOT NULL default '0',
  page_meta_keywords text,
  page_meta_description text,
  page_content_type varchar(32) NOT NULL default 'page',
  page_template mediumtext,
  page_ipb_wrapper TINYINT( 1 ) NOT NULL DEFAULT '0',
  PRIMARY KEY  (page_id),
  KEY page_seo_name (page_seo_name),
  KEY page_template_used (page_template_used),
  KEY page_folder (page_folder)
);";

$TABLE[]	= "CREATE TABLE ccs_page_templates (
  template_id int(11) NOT NULL auto_increment,
  template_name varchar(255) default NULL,
  template_desc text,
  template_key varchar(32) NOT NULL,
  template_content mediumtext NOT NULL,
  template_updated varchar(13) NOT NULL default '0',
  template_position mediumint(9) NOT NULL default '0',
  template_category mediumint(9) NOT NULL default '0',
  PRIMARY KEY  (template_id),
  UNIQUE KEY template_key (template_key),
  KEY template_category (template_category)
);";

$TABLE[]	= "CREATE TABLE ccs_page_wizard (
  wizard_id varchar(32) NOT NULL,
  wizard_step smallint(6) NOT NULL default '0',
  wizard_edit_id int(11) NOT NULL default '0',
  wizard_name varchar(255) default NULL,
  wizard_description text,
  wizard_folder varchar(255) default NULL,
  wizard_type varchar(32) default NULL,
  wizard_template int(11) NOT NULL default '0',
  wizard_content mediumtext,
  wizard_cache_ttl varchar(13) NOT NULL default '0',
  wizard_perms text,
  wizard_seo_name varchar(255) default NULL,
  wizard_content_only tinyint(1) NOT NULL default '0',
  wizard_meta_keywords text,
  wizard_meta_description text,
  wizard_started VARCHAR( 13 ) NOT NULL DEFAULT '0',
  wizard_previous_type VARCHAR( 32 ) NULL,
  wizard_ipb_wrapper TINYINT( 1 ) NOT NULL DEFAULT '0',
  PRIMARY KEY  (wizard_id)
);";

$TABLE[]	= "CREATE TABLE ccs_template_blocks (
  tpb_id int(11) NOT NULL auto_increment,
  tpb_name varchar(255) default NULL,
  tpb_params text,
  tpb_content text,
  PRIMARY KEY  (tpb_id)
);";

$TABLE[]	= "CREATE TABLE ccs_template_cache (
  cache_id int(11) NOT NULL auto_increment,
  cache_type varchar(16) NOT NULL,
  cache_type_id int(11) NOT NULL default '0',
  cache_content mediumtext,
  PRIMARY KEY  (cache_id),
  KEY cache_type (cache_type,cache_type_id)
);";