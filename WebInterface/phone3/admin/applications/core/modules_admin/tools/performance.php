<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Toggle performance mode on/off
 * Last Updated: $LastChangedDate: 2009-02-04 15:03:36 -0500 (Wed, 04 Feb 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Core
 * @link		http://www.
 * @version		$Rev: 3887 $
 */

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}


class admin_core_tools_performance extends ipsCommand
{
	/**
	 * Settings to ENABLE
	 *
	 * @access	private
	 * @var		array
	 */
	private $settingsEnable		= array( 'live_search_disable', 'no_au_forum', 'no_au_topic', 'disable_summary' );
	
	/**
	 * Settings to DISABLE
	 *
	 * @access	private
	 * @var		array
	 */
	private $settingsDisable	= array( 'spider_visit', 'custom_profile_topic', 'update_topic_views_immediately', 'show_user_posted',
										 'show_active', 'allow_search', 'topic_marking_enable' );
	
	/**
	 * Group settings to ENABLE
	 *
	 * @access	private
	 * @var		array
	 */
	private $groupEnable		= array();
	
	/**
	 * Group settings to DISABLE
	 *
	 * @access	private
	 * @var		array
	 */
	private $groupDisable		= array( 'g_mem_info', 'g_can_add_friends', 'g_email_friend' );
	
	/**
	 * Other stuff to do...(custom coded)
	 *
	 * @access	private
	 * @var		array
	 */
	private $otherDisable		= array( 'module__disable_pms', 'module__disable_online', 'disable_calendar', 'disable_hooks' );
	
	/**
	 * HTML object
	 *
	 * @access	private
	 * @var		object
	 **/
	private $html;
	
	/**
	 * Current performance settings
	 *
	 * @access	private
	 * @var		array
	 */
	private $current		= array();

	/**#@+
	 * URL bits
	 *
	 * @access	public
	 * @var		string
	 */
	public $form_code		= '';
	public $form_code_js	= '';
	/**#@-*/	
	
	/**
	 * Main entry point
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	void
	 */
	public function doExecute( ipsRegistry $registry )
	{
		/* Load lang and skin */
		$this->registry->class_localization->loadLanguageFile( array( 'admin_tools' ) );
		$this->html = $this->registry->output->loadTemplate( 'cp_skin_performance' );
				
		/* URLs */
		$this->form_code    = $this->html->form_code    = 'module=tools&amp;section=performance';
		$this->form_code_js = $this->html->form_code_js = 'module=tools&section=performance';
		
		/* Get current */
		$this->getSettings();
		
		/* What to do */
		switch( $this->request['do'] )
		{
			case 'toggle':
				$this->toggle();
			break;

			case 'overview':
			default:
				$this->overview();
			break;
		}
		
		/* Output */
		$this->registry->output->html_main .= $this->registry->output->global_template->global_frame_wrapper();
		$this->registry->output->sendOutput();
	}

	/**
	 * Toggle on/off
	 *
	 * @access	public
	 * @return	void
	 * @author	Brandon
	 */
	public function toggle()
	{
		$nowEnabled	= count($this->current) ? false : true;
		$results	= array();
		$stored		= array();

		/**
		 * Are we turning it ON
		 */
		if( $nowEnabled )
		{
			/**
			 * Enable any settings after storing current value
			 */
			foreach( $this->settingsEnable as $settingKey )
			{
				$stored['settingsEnable'][ $settingKey ]	= $this->settings[ $settingKey ];
				
				if( !$this->settings[ $settingKey ] )
				{
					$results[]	= sprintf( $this->lang->words['perf_enabled_setting'], $settingKey );
				}
				
				$this->DB->update( 'core_sys_conf_settings', array( 'conf_value' => 1 ), "conf_key='" . $settingKey . "'" );
			}
			
			/**
			 * Disable any settings after storing current value
			 */
			foreach( $this->settingsDisable as $settingKey )
			{
				$stored['settingsDisable'][ $settingKey ]	= $this->settings[ $settingKey ];
				
				if( $this->settings[ $settingKey ] )
				{
					$results[]	= sprintf( $this->lang->words['perf_disabled_setting'], $settingKey );
				}
				
				$this->DB->update( 'core_sys_conf_settings', array( 'conf_value' => 0 ), "conf_key='" . $settingKey . "'" );
			}
			
			/**
			 * Enable appropriate group settings
			 */
			if( count($this->groupEnable) )
			{
				foreach( $this->groupEnable as $settingKey )
				{
					foreach( $this->cache->getCache('group_cache') as $gid => $group )
					{
						$results[]	= sprintf( $this->lang->words['perf_enabled_group'], $settingKey, $group['g_title'] );
						
						$stored['groupEnable'][ $gid ][ $settingKey ]	= $group[ $settingKey ];
						
						$this->DB->update( 'groups', array( $settingKey => 1 ), "g_id=" . $gid );
					}
				}
			}
			
			/**
			 * Disable appropriate group settings
			 */
			if( count($this->groupDisable) )
			{
				foreach( $this->groupDisable as $settingKey )
				{
					foreach( $this->cache->getCache('group_cache') as $gid => $group )
					{
						$results[]	= sprintf( $this->lang->words['perf_disabled_group'], $settingKey, $group['g_title'] );
						
						$stored['groupDisable'][ $gid ][ $settingKey ]	= $group[ $settingKey ];
						
						$this->DB->update( 'groups', array( $settingKey => 0 ), "g_id=" . $gid );
					}
				}
			}
			
			/**
			 * And anything else..
			 */
			foreach( $this->otherDisable as $doIt )
			{
				switch( $doIt )
				{
					case 'module__disable_pms':
						foreach( ipsRegistry::$modules['members'] as $module )
						{
							if( $module['sys_module_key'] == 'messaging' )
							{
								$stored['other']['module__disable_pms']	= $module['sys_module_visible'];
								break;
							}
						}
						
						$this->DB->update( 'core_sys_module', array( 'sys_module_visible' => 0 ), "sys_module_key='messaging'" );
						$results[]	= $this->lang->words['perf_dispms'];
					break;
					
					case 'module__disable_online':
						foreach( ipsRegistry::$modules['members'] as $module )
						{
							if( $module['sys_module_key'] == 'online' )
							{
								$stored['other']['module__disable_online']	= $module['sys_module_visible'];
								break;
							}
						}
						
						$this->DB->update( 'core_sys_module', array( 'sys_module_visible' => 0 ), "sys_module_key='online'" );
						$results[]	= $this->lang->words['perf_disonline'];
					break;
					
					case 'disable_calendar':
						foreach( ipsRegistry::$applications as $app )
						{
							if( $app['app_directory'] == 'calendar' )
							{
								$stored['other']['disable_calendar']	= $app['app_enabled'];
								break;
							}
						}
						
						$this->DB->update( 'core_applications', array( 'app_enabled' => 0 ), "app_directory='calendar'" );
						$results[]	= $this->lang->words['perf_discal'];
					break;
					
					case 'disable_hooks':
						$this->DB->build( array( 'select' => 'hook_id, hook_enabled', 'from' => 'core_hooks' ) );
						$this->DB->execute();
						
						while( $hook = $this->DB->fetch() )
						{
							$stored['other']['disable_hooks'][ $hook['hook_id'] ]	= $hook['hook_enabled'];
						}

						$this->DB->update( 'core_hooks', array( 'hook_enabled' => 0 ) );
						$results[]	= $this->lang->words['perf_dishooks'];
					break;
				}
			}

			/**
			 * And finally store in cache
			 */
			$this->cache->setCache( 'performanceCache', $stored, array( 'array' => 1, 'deletefirst' => 1 ) );
		}
		/**
		 * We're turning it off
		 */
		else
		{
			/**
			 * Reset the "enable settings" value
			 */
			foreach( $this->current['settingsEnable'] as $k => $v )
			{
				if( !$v )
				{
					$this->DB->update( 'core_sys_conf_settings', array( 'conf_value' => 0 ), "conf_key='" . $k . "'" );
					
					$results[]	= sprintf( $this->lang->words['perf_disabled_setting'], $k );
				}
			}

			/**
			 * Reset the "disable settings" value
			 */
			foreach( $this->current['settingsDisable'] as $k => $v )
			{
				if( $v )
				{
					$this->DB->update( 'core_sys_conf_settings', array( 'conf_value' => 1 ), "conf_key='" . $k . "'" );
					
					$results[]	= sprintf( $this->lang->words['perf_enabled_setting'], $k );
				}
			}

			/**
			 * Reset the "enable group setting" value
			 */
			if( count($this->current['groupEnable']) )
			{
				foreach( $this->current['groupEnable'] as $gid => $data )
				{
					foreach( $data as $k => $v )
					{
						if( !$v )
						{
							$this->DB->update( 'groups', array( $k => 0 ), "g_id=" . $gid );
							
							$results[]	= sprintf( $this->lang->words['perf_disabled_group'], $k, $this->caches['group_cache'][ $gid ]['g_title'] );
						}
					}
				}
			}

			/**
			 * Reset the "disable settings" value
			 */
			if( count($this->current['groupDisable']) )
			{
				foreach( $this->current['groupDisable'] as $gid => $data )
				{
					foreach( $data as $k => $v )
					{
						if( $v )
						{
							$this->DB->update( 'groups', array( $k => 1 ), "g_id=" . $gid );
							
							$results[]	= sprintf( $this->lang->words['perf_enabled_group'], $k, $this->caches['group_cache'][ $gid ]['g_title'] );
						}
					}
				}
			}

			/**
			 * And anything else..
			 */
			foreach( $this->otherDisable as $doIt )
			{
				switch( $doIt )
				{
					case 'module__disable_pms':
						$previous	= $this->current['other']['module__disable_pms'];
						
						if( $previous )
						{
							$this->DB->update( 'core_sys_module', array( 'sys_module_visible' => 1 ), "sys_module_key='messaging'" );
							$results[]	= $this->lang->words['perf_enpms'];
						}
					break;
					
					case 'module__disable_online':
						$previous	= $this->current['other']['module__disable_online'];
						
						if( $previous )
						{
							$this->DB->update( 'core_sys_module', array( 'sys_module_visible' => 1 ), "sys_module_key='online'" );
							$results[]	= $this->lang->words['perf_enonline'];
						}
					break;
					
					case 'disable_calendar':
						$previous	= $this->current['other']['disable_calendar'];
						
						if( $previous )
						{
							$this->DB->update( 'core_applications', array( 'app_enabled' => 1 ), "app_directory='calendar'" );
							$results[]	= $this->lang->words['perf_encal'];
						}
					break;
					
					case 'disable_hooks':
						if( count($this->current['other']['disable_hooks']) )
						{
							foreach( $this->current['other']['disable_hooks'] as $hookId => $hookEnabled )
							{
								if( !$hookId )
								{
									continue;
								}

								$this->DB->update( 'core_hooks', array( 'hook_enabled' => $hookEnabled ), "hook_id=" . $hookId );
							}
						}

						$results[]	= $this->lang->words['perf_enhooks'];
					break;
				}
			}
			
			/**
			 * And finally remove from DB
			 */
			$this->DB->delete( 'cache_store', "cs_key='performanceCache'" );
		}
		
		/**
		 * Need to rebuild some caches now...
		 */
		$this->cache->rebuildCache( 'settings', 'global' );
		$this->cache->rebuildCache( 'group_cache', 'global' );
		$this->cache->rebuildCache( 'app_cache', 'global' );
		$this->cache->rebuildCache( 'module_cache', 'global' );
		$this->cache->rebuildCache( 'hooks', 'global' );
			
		/* Output */	
		$this->registry->output->html           .= $this->html->toggleResults( $nowEnabled, $results );
	}	
	
	/**
	 * List current status/data
	 *
	 * @access	public
	 * @return	void
	 */
	public function overview()
	{	
		/* Output */	
		$this->registry->output->html           .= $this->html->overview( count($this->current) ? true : false );
	}
	
	/**
	 * Retrieve current cached performance settings. 
	 * Leaves array blank if currently "off"
	 *
	 * @access	private
	 * @return	void
	 */
	private function getSettings()
	{
		$record	= $this->cache->getCache('performanceCache');
		
		if( is_array($record) AND count($record) )
		{
			$this->current	= $record;
		}
	}
}