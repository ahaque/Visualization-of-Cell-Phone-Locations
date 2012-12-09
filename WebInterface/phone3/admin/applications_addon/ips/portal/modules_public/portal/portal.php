<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * IP.Portal
 * Last Updated: $Date: 2009-07-28 20:26:37 -0400 (Tue, 28 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Portal
 * @since		1st April 2004
 * @version		$Revision: 4948 $
 */

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class public_portal_portal_portal extends ipsCommand
{
	/**
	 * Array of portal objects
	 *
	 * @access	protected
	 * @var 	array 				Registered portal objects
	 */
	protected $portal_object		= array();

	/**
	 * Array of replacement tags
	 *
	 * @access	protected
	 * @var 	array 				Replacement tags
	 */
	protected $replace_tags			= array();
	
	/**
	 * Array of tags to module...
	 *
	 * @access	protected
	 * @var 	array 				Tags => Modules mapping
	 */
	protected $remap_tags_module 	= array();
	
	/**
	 * Array of tags to function...
	 *
	 * @access	protected
	 * @var 	array 				Tags => Function mapping
	 */
	protected $remap_tags_function	= array();
	
	/**
	 * Array of module objects
	 *
	 * @access	protected
	 * @var 	array 				Module objects
	 */
	protected $module_objects		= array();
	
	/**
	 * Basic template, replaced as needed
	 *
	 * @access	protected
	 * @var 	string 				Basic skin template to replace
	 */
	protected $template				= array();

	/**
	 * Main class entry point
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	void		[Outputs to screen]
	 */
	public function doExecute( ipsRegistry $registry ) 
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$conf_groups		= array();
		$found_tags			= array();
		$found_modules		= array();
		
		//-----------------------------------------
		// Make sure the portal is installed an enabled
		//-----------------------------------------
		
		if( ! IPSLib::appIsInstalled( 'portal' ) )
		{
			$this->registry->output->showError( 'no_permission', 1076 );
		}
		
		//-----------------------------------------
		// Get settings...
		//-----------------------------------------
		
		foreach( $this->cache->getCache('portal') as $portal_data )
		{
			if ( $portal_data['pc_settings_keyword'] )
			{
				$conf_groups[]	= "'" . $portal_data['pc_settings_keyword'] . "'";
			}
			
			//-----------------------------------------
			// Remap tags
			//-----------------------------------------
			
			if ( is_array( $portal_data['pc_exportable_tags'] ) AND count( $portal_data['pc_exportable_tags'] ) )
			{
				foreach( $portal_data['pc_exportable_tags'] as $tag => $tag_data )
				{
					$this->remap_tags_function[ $tag ]	= $tag_data[0];
					$this->remap_tags_module[ $tag ]	= $portal_data['pc_key'];
				}
			}
		}
		
		//-----------------------------------------
		// Now really get them...
		//-----------------------------------------
		
		$_where	= (is_array($conf_groups) AND count($conf_groups)) ? 't.conf_title_keyword IN(' . implode( ",", $conf_groups ) . ") OR " : '';
		
		$this->DB->build( array( 	'select'	=> 'c.conf_key, c.conf_value, c.conf_default',
										'from'		=> array( 'core_sys_conf_settings' => 'c' ),
										'where'		=> $_where . "conf_key LIKE 'csite%'",
										'add_join'	=> array( 
															array(
																'select'	=> 't.conf_title_id, t.conf_title_keyword',
								  								'from'		=> array( 'core_sys_settings_titles' => 't' ),
								  								'where'		=> 'c.conf_group=t.conf_title_id',
								  								'type'		=> "left"
								  								)
								  							),
								)		);
		$this->DB->execute();
		
		//-----------------------------------------
		// Set 'em up
		//-----------------------------------------
		
		while( $r = $this->DB->fetch() )
		{
			$this->settings[ $r['conf_key']] =  $r['conf_value'] != "" ? $r['conf_value'] : $r['conf_default'] ;
		}

		//-----------------------------------------
		// Get global skin and language files
		//-----------------------------------------
		
		ipsRegistry::getClass( 'class_localization')->loadLanguageFile( array( 'public_portal' ) );

		//-----------------------------------------
		// Assign skeletal template ma-doo-bob
		//-----------------------------------------
		
		$this->template = $this->registry->getClass('output')->getTemplate('portal')->skeletonTemplate();
		
		//-----------------------------------------
		// Grab all special tags
		//-----------------------------------------
		
		preg_match_all( "#<!--\:\:(.+?)\:\:-->#", $this->template, $match );
		
		//-----------------------------------------
		// Assign functions
		//-----------------------------------------
		
		for ( $i=0, $m=count($match[0]); $i < $m; $i++ )
		{
			$tag = $match[1][$i];
			
			if ( $this->remap_tags_module[ $tag ] )
			{
				$found_tags[ $tag ] = 1;
				
				if ( $this->remap_tags_module[ $tag ])
				{
					$found_modules[ $this->remap_tags_module[ $tag ] ] = 1;
				}
			}
		}
			
		//-----------------------------------------
		// Require modules...
		//-----------------------------------------

		if ( is_array( $found_modules ) AND count( $found_modules ) )
		{
			foreach( $found_modules as $mod_name => $pointless )
			{
				if ( ! is_object( $this->module_objects[ $mod_name ] ) )
				{
					if ( file_exists( $this->caches['portal'][ $mod_name ]['_file_location'] ) )
					{
						require_once( $this->caches['portal'][ $mod_name ]['_file_location'] );
						$constructor = 'ppi_' . $mod_name;
						$this->module_objects[ $mod_name ]				= new $constructor;
						$this->module_objects[ $mod_name ]->makeRegistryShortcuts( $this->registry );
						$this->module_objects[ $mod_name ]->init();
					}
				}
			}
		}
		
		//-----------------------------------------
		// Get the tag replacements...
		//-----------------------------------------
		
		if ( is_array( $found_tags ) AND count( $found_tags ) )
		{
			foreach( $found_tags as $tag_name => $even_more_pointless )
			{
				$mod_obj	= $this->remap_tags_module[ $tag_name ];
				$fun_obj	= $this->remap_tags_function[ $tag_name ];
				
				if ( method_exists( $this->module_objects[ $mod_obj ], $fun_obj ) )
				{
					$this->replace_tags[ $tag_name ] = $this->module_objects[ $mod_obj ]->$fun_obj();
					continue;
				}
			}
		}
		
		$this->_do_output();
 	}
 	
 	/**
 	 * Internal do output method.  Extend class and overwrite method if you need to modify this functionality.
 	 *
 	 * @access	protected
 	 * @return	void
 	 */
 	protected function _do_output()
 	{
 		//-----------------------------------------
		// SITE REPLACEMENTS
		//-----------------------------------------
		
		foreach( $this->replace_tags as $sbk => $sbv )
		{
			$this->template = str_replace( "<!--::" . $sbk . "::-->", $sbv, $this->template );
		}
 		
 		//-----------------------------------------
 		// Pass to print...
 		//-----------------------------------------
 		
 		$this->registry->output->addContent( $this->template );
 		$this->registry->output->setTitle( $this->settings['csite_title'] );
 		$this->registry->output->addNavigation( $this->settings['csite_title'], 'app=portal' );
 		
 		$this->registry->output->sendOutput();

		exit();
 	}

}