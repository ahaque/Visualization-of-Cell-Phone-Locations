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
*/

if ( ! defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

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
		
		/* Set DB driver to return any errors */
		$this->DB->return_die = 1;
		
		//--------------------------------
		// What are we doing?
		//--------------------------------

		switch( $this->request['workact'] )
		{
			case 'sql':
				$this->upgrade_sql(1);
				break;
			case 'sql1':
				$this->upgrade_sql(1);
				break;
			case 'sql2':
				$this->upgrade_sql(2);
				break;
			case 'sql3':
				$this->upgrade_sql(3);
				break;
			case 'sql4':
				$this->upgrade_sql(4);
				break;
			case 'forums':
				$this->update_forums();
				break;
			case 'finish':
				$this->finish_up();
				break;
			case 'skin':
				$this->add_skin();
				break;
			case 'update_template_bits':
				$this->update_template_bits();
				break;
			
			default:
				$this->upgrade_sql(1);
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
	// SQL: 0
	/*-------------------------------------------------------------------------*/
	
	function upgrade_sql( $id=1 )
	{
		$man     = 0; // Manual upgrade ? intval( $this->install->ipsclass->input['man'] );
		$cnt     = 0;
		$SQL     = array();
		$file    = '_updates_'.$id.'.php';
		$output  = "";
		$path    = IPSLib::getAppDir( 'core' ) . '/setup/versions/upg_22005/' . strtolower( $this->registry->dbFunctions()->getDriverType() ) . $file;
		$prefix  = $this->registry->dbFunctions()->getPrefix();
		
		if ( file_exists( $path ) )
		{
			require_once( $path );
		
			$this->sqlcount 		= 0;
			$output					= "";
			
			$this->DB->return_die = 1;
			
			foreach( $SQL as $query )
			{
				$this->DB->allow_sub_select 	= 1;
				$this->DB->error				= '';
				
				$query = str_replace( "<%time%>", time(), $query );
				
				if( $this->settings['mysql_tbl_type'] )
				{
					if( preg_match( "/^create table(.+?)/i", $query ) )
					{
						$query = preg_replace( "/^(.+?)\);$/is", "\\1) TYPE={$this->settings['mysql_tbl_type']};", $query );
					}
				}					
				
				/* Need to tack on a prefix? */
				if ( $prefix )
				{
					$query = IPSSetUp::addPrefixToQuery( $query, $prefix );
				}
					
				if ( IPSSetUp::getSavedData('man') )
				{
					$output .= preg_replace( "/\s{1,}/", " ", $query ) ."\n\n";
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
		
			$this->registry->output->addMessage("$this->sqlcount queries run....");
		}
		
		//--------------------------------
		// Next page...
		//--------------------------------
		
		$this->request['st'] = 0;
		
		if ( $id != 4 )
		{
			$nextid = $id + 1;
			$this->request['workact'] = 'sql'.$nextid;	
		}
		else
		{
			$this->request['workact'] = 'forums';	
		}
		
		if ( IPSSetUp::getSavedData('man') AND $output )
		{
			$this->_output = $this->registry->output->template()->upgrade_manual_queries( $output );
		}
	}	
	
	
	/*-------------------------------------------------------------------------*/
	// Update forums
	/*-------------------------------------------------------------------------*/
	
	function update_forums()
	{
		//-----------------------------------------
		// Update latest news...
		//-----------------------------------------
	
		$this->DB->update( "forums", "newest_title=last_title, newest_id=last_id", 'last_title IS NOT NULL AND last_id IS NOT NULL', false, true );
		$this->DB->execute();
		
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$ignore_me = array( 'redirect_url', 'redirect_loc', 'rules_text', 'permission_custom_error', 'notify_modq_emails' );
		
		if ( isset($this->settings['forum_cache_minimum']) AND $this->settings['forum_cache_minimum'] )
		{
			$ignore_me[] = 'description';
			$ignore_me[] = 'rules_title';
		}
		
		$this->caches['forum_cache'] = array();
			
		$this->DB->build( array( 'select' => '*',
													  'from'   => 'forums',
													  'order'  => 'parent_id, position'
											   )      );
		$o = $this->DB->execute();
		
		while( $f = $this->DB->fetch( $o ) )
		{
			$fr = array();
			
			$perms = unserialize(stripslashes($f['permission_array']));
			
			//-----------------------------------------
			// Stuff we don't need...
			//-----------------------------------------
			
			if ( $f['parent_id'] == -1 )
			{
				$fr['id']				    = $f['id'];
				$fr['sub_can_post']         = $f['sub_can_post'];
				$fr['name'] 		        = $f['name'];
				$fr['parent_id']	        = $f['parent_id'];
				$fr['show_perms']	        = $perms['show_perms'];
				$fr['skin_id']		        = $f['skin_id'];
				$fr['permission_showtopic'] = $f['permission_showtopic'];
			}
			else
			{
				foreach( $f as $k => $v )
				{
					if ( in_array( $k, $ignore_me ) )
					{
						continue;
					}
					else
					{
						if ( $v != "" )
						{
							$fr[ $k ] = $v;
						}
					}
				}
				
				$fr['read_perms']   	= isset($perms['read_perms']) 		? $perms['read_perms'] 		: '';
				$fr['reply_perms']  	= isset($perms['reply_perms']) 		? $perms['reply_perms'] 	: '';
				$fr['start_perms']  	= isset($perms['start_perms']) 		? $perms['start_perms'] 	: '';
				$fr['upload_perms'] 	= isset($perms['upload_perms']) 	? $perms['upload_perms'] 	: '';
				$fr['download_perms'] 	= $perms['upload_perms'];
				$fr['show_perms']   	= isset($perms['show_perms']) 		? $perms['show_perms'] 		: '';
				
				unset($fr['permission_array']);
			}
			
			$this->caches['forum_cache'][ $fr['id'] ] = $fr;
			
			$perm_array = addslashes(serialize(array(
													   'start_perms'    => $fr['start_perms'],
													   'reply_perms'    => $fr['reply_perms'],
													   'read_perms'     => $fr['read_perms'],
													   'upload_perms'   => $fr['upload_perms'],
													   'download_perms' => $fr['download_perms'],
													   'show_perms'     => $fr['show_perms']
									 )		  )     );
									 
			//-----------------------------------------
			// Add to save array
			//-----------------------------------------
			
			$this->DB->update( 'forums', array( 'permission_array' => $perm_array ), 'id='.$fr['id'] );
			
		}
		
		$this->cache->setCache( 'forum_cache', $this->caches['forum_cache'], array( 'array' => 1 ) );
		
		$this->registry->output->addMessage("Download permissions added,  Converting template bit HTML logic...");
		$this->request['workact'] 	= 'update_template_bits';
	}
	
	/*-------------------------------------------------------------------------*/
	// Update template bits
	/*-------------------------------------------------------------------------*/
	
	function update_template_bits()
	{
		$this->registry->output->addMessage("Template bits skipped, finishing up...");
		$this->request['workact'] 	= 'finish';	
		return FALSE;						
	}

	/*-------------------------------------------------------------------------*/
	// Update forums
	/*-------------------------------------------------------------------------*/
	
	function finish_up()
	{
		//-----------------------------------------
		// Has gallery?
		//-----------------------------------------
		
		$this->DB->return_die = 1;
		$this->DB->error		 = '';
		
		$table = 'members';
		
		if ( ! $this->DB->checkForField( 'has_gallery', $table ) )
		{
			$this->DB->addField( 'members', 'has_gallery', 'INT(1)', '0' );
			
			if ( $this->DB->error )
			{
				$this->registry->output->addError( "ALTER TABLE {$this->DB->obj['sql_tbl_prefix']}members ADD has_gallery INT(1) default 0<br /><br />".$this->DB->error );
			}
		}
		
		if( $this->settings['conv_configured'] != 1 OR $this->settings['conv_chosen'] == "" )
		{
			if( $this->DB->checkForField( "legacy_password", "members" ) )
			{
				$this->DB->dropField( 'members', 'legacy_password' );
				
				if ( $this->DB->error )
				{
					$this->registry->output->addError( "ALTER TABLE {$this->DB->obj['sql_tbl_prefix']}members DROP legacy_password<br /><br />".$this->DB->error );
				}
			}
		}
		
		$test = $this->DB->buildAndFetch( array( 'select' 	=> 'count(*) as numrows',
																	'from'	=> 'cache_store',
																	'where'	=> "cs_key='calendars'"
														)		);

		if ( ! $test['numrows'] )
		{
			$this->DB->insert( 'cache_store', array( 'cs_key' => 'calendars' ) );
		}
		
		$this->registry->output->addMessage("Clean up performed,  Creating new IPB 2.2.0 skin...");
		$this->request['workact'] 	= 'skin';
	}
	
	/*-------------------------------------------------------------------------*/
	// Add new skin
	/*-------------------------------------------------------------------------*/
	
	function add_skin()
	{
		$this->registry->output->addMessage("2.2.0 skin skipped...");
		unset($this->request['workact']);

		return TRUE;	
	}
	
}
	
	
?>