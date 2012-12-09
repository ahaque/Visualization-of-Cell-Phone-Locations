<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Library of shared methods for hooks installation/configuration
 * Last Updated: $LastChangedDate: 2009-06-30 12:06:12 -0400 (Tue, 30 Jun 2009) $
 *
 * @author 		$Author: josh $
 * @author		Based on original "Report Center" by Luke Scott
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Core
 * @link		http://www.
 * @version		$Rev: 4829 $
 *
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}

class hooksFunctions
{
	/**#@+
	 * Registry objects
	 *
	 * @access	protected
	 * @var		object
	 */	
	protected $registry;
	protected $DB;
	protected $settings;
	protected $request;
	protected $lang;
	protected $member;
	protected $memberData;
	protected $cache;
	protected $caches;
	/**#@-*/
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		Registry object
	 * @return	void
	 */
	public function __construct( ipsRegistry $registry )
	{
		//-----------------------------------------
		// Make object
		//-----------------------------------------
		
		$this->registry 	= $registry;
		$this->DB	    	= $this->registry->DB();
		$this->settings		=& $this->registry->fetchSettings();
		$this->request		=& $this->registry->fetchRequest();
		$this->member   	= $this->registry->member();
		$this->memberData	=& $this->registry->member()->fetchMemberData();
		$this->cache		= $this->registry->cache();
		$this->caches		=& $this->registry->cache()->fetchCaches();
		$this->lang 		= $this->registry->class_localization;
		
		$this->lang->loadLanguageFile( array( 'admin_applications' ) );
	}
	
	/**
	 * Get all the available hook ids in a function for a given type
	 *
	 * @access	public
	 * @param	string 		Skin Function
	 * @param	string		Hook type [foreach|if]
	 * @return	array 		Settings for dropdown
	 */
	public function getHookIds( $template='', $type='' )
	{
		if( !$template )
		{
			return array();
		}

		$type		= $type ? $type : 'foreach';

		$return		= array( array( 0, $this->lang->words['h_selectid'] ) );
		$seenids	= array();
		$presort	= array();
		
		$this->DB->build( array( 'select' => 'template_content', 'from' => 'skin_templates', 'where' => "template_name='{$template}'" ) );
		$this->DB->execute();

		while( $r = $this->DB->fetch() )
		{
			/* Use regex to try to find the ids */
			
			$matches	= array();
			
			if( $type == 'foreach' )
			{
				preg_match_all( "#\<foreach\s+loop=\"(.+?)\:(.+?)\"\>#i", $r['template_content'], $matches );
			}
			else
			{
				preg_match_all( "#\<if\s+test=\"(.+?)\:\|\:(.+?)\"\>#i", $r['template_content'], $matches );
			}
			
			if( is_array($matches[1]) AND count($matches[1]) )
			{
				foreach( $matches[1] as $match )
				{
					$presort[ $match ] = array( $match, $match );
				}
			}
		}
		
		/* Put the ids in alphabetical order, kinda hacky, oh well ;) */
		ksort( $presort );
		foreach( $presort as $k => $v )
		{
			$return[$k] = $v;
		}
				
		return $return;
	}
	
	/**
	 * Get all the available skin files
	 *
	 * @access	public
	 * @return	array 		Settings for dropdown
	 */
	public function getSkinGroups()
	{
		$return		= array( array( 0, $this->lang->words['h_selectfile'] ) );
		
		$this->DB->build( array( 'select' => $this->DB->buildDistinct( 'template_group' ), 'from' => 'skin_templates', 'order' => 'template_group ASC' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$return[] = array( $r['template_group'], $r['template_group'] );
		}
		
		return $return;
	}
	
	/**
	 * Get all the available skin templates in a skin group
	 *
	 * @access	public
	 * @param	string 		Skin Group
	 * @return	array 		Settings for dropdown
	 */
	public function getSkinMethods( $file='' )
	{
		if( !$file )
		{
			return array();
		}

		$return		= array( array( 0, $this->lang->words['h_selecttemp'] ) );
		
		$this->DB->build( array( 'select' => $this->DB->buildDistinct( 'template_name' ), 'from' => 'skin_templates', 'where' => "template_group='{$file}'", 'order' => 'template_name ASC' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$return[] = array( $r['template_name'], $r['template_name'] );
		}
		
		return $return;
	}
	
	/**
	 * Retrieve setting groups for multiselect
	 *
	 * @access	public
	 * @return	array 		Settings for multiselect
	 */
	public function getSettingGroups()
	{
		$return		= array();
		
		$this->DB->build( array( 'select' => 'conf_title_id, conf_title_title', 'from' => 'core_sys_settings_titles', 'order' => 'conf_title_title ASC' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$return[] = array( $r['conf_title_id'], $r['conf_title_title'] );
		}
		
		return $return;
	}
	
	/**
	 * Retrieve settings for multiselect
	 *
	 * @access	public
	 * @return	array 		Settings for multiselect
	 */
	public function getSettings()
	{
		$return		= array();
		
		$this->DB->build( array( 'select' => 'conf_id, conf_title', 'from' => 'core_sys_conf_settings', 'order' => 'conf_title ASC' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$return[] = array( $r['conf_id'], $r['conf_title'] );
		}
		
		return $return;
	}
	
	/**
	 * Retrieve available modules for export
	 *
	 * @access	public
	 * @return	array 		Settings for multiselect
	 */
	public function getModules()
	{
		$return		= array();
		
		$this->DB->build(
								array(
									'select'	=> 'm.sys_module_key, m.sys_module_title, m.sys_module_admin',
									'from'		=> array( 'core_sys_module' => 'm' ),
									'order'		=> 'a.app_title ASC, m.sys_module_title ASC',
									'add_join'	=> array(
														array(
																'select'	=> 'a.app_title',
																'from'		=> array( 'core_applications' => 'a' ),
																'where'		=> 'a.app_directory=m.sys_module_application',
																'type'		=> 'left'
															)
														)
									)
							);
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$admin = $r['sys_module_admin'] ? $this->lang->words['hook_admin_module'] : $this->lang->words['hook_public_module'];
			
			$return[] = array( $r['sys_module_key'], $r['app_title'] . ':: ' . $r['sys_module_title'] . $admin );
		}
		
		return $return;
	}
	
	/**
	 * Retrieve available Help Files for export
	 *
	 * @access	public
	 * @return	array 		Settings for multiselect
	 */
	public function getHelpFiles()
	{
		$return		= array();
		
		$this->DB->build( array( 'select' => 'id, title', 'from' => 'faq', 'order' => 'title ASC' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$return[] = array( $r['id'], $r['title'] );
		}
		
		return $return;
	}

	/**
	 * Retrieve available tasks for export
	 *
	 * @access	public
	 * @return	array 		Settings for multiselect
	 */
	public function getTasks()
	{
		$return		= array();
		
		$this->DB->build( array( 'select' => 'task_id, task_title', 'from' => 'task_manager', 'order' => 'task_title ASC' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$return[] = array( $r['task_id'], $r['task_title'] );
		}
		
		return $return;
	}
	
	/**
	 * Get all the available language files
	 *
	 * @access	public
	 * @return	array 		Settings for dropdown
	 */
	public function getLanguageFiles()
	{
		$return		= array( array( 0, 'Select a File' ) );
		
		$this->DB->build( array( 'select' => $this->DB->buildDistinct( 'word_pack' ), 'from' => 'core_sys_lang_words', 'order' => 'word_pack ASC' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$return[] = array( $r['word_pack'], $r['word_pack'] );
		}
		
		return $return;
	}
	
	/**
	 * Get all the available language strings in a given file
	 *
	 * @access	public
	 * @param	string 		Language file
	 * @return	array 		Settings for multiselect
	 */
	public function getStrings( $file='' )
	{
		if( !$file )
		{
			return array();
		}

		$return		= array();
		
		$this->DB->build( array( 'select' => $this->DB->buildDistinct( 'word_key' ), 'from' => 'core_sys_lang_words', 'where' => "word_pack='{$file}'", 'order' => 'word_key ASC' ) );
		$this->DB->execute();
		
		while( $r = $this->DB->fetch() )
		{
			$return[] = array( $r['word_key'], $r['word_key'] );
		}
		
		return $return;
	}
}
	