<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Control Panel Functions
 * Last Updated: $Date: 2009-06-18 19:43:48 -0400 (Thu, 18 Jun 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @since		Tue. 17th August 2004
 * @version		$Rev: 4790 $
 *
 */
class adminFunctions
{
	/**#@+
	 * Registry Object Shortcuts
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
	/**#@-*/
	
	/**#@+
	 * Security keys
	 *
	 * @access	public
	 * @var		string
	 */
	public $generated_acp_hash;
	public $_admin_auth_key;
	/**#@-*/
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	void
	 */
	public function __construct( ipsRegistry $registry )
	{
		/* Make object */
		$this->registry				=  $registry;
		$this->DB					=  $this->registry->DB();
		$this->settings				=& $this->registry->fetchSettings();
		$this->request				=& $this->registry->fetchRequest();
		$this->lang					=  $this->registry->getClass('class_localization');
		$this->member				=  $this->registry->member();
		$this->memberData			=& $this->registry->member()->fetchMemberData();
		$this->generated_acp_hash	=  $this->generateSecureHash();
		$this->_admin_auth_key		=  $this->getSecurityKey();
		
		$this->registry->output->global_template = $this->registry->output->loadRootTemplate('cp_skin_global');

		$this->lang->loadLanguageFile( array( 'admin_global' ) );
		
		//------------------------------------------
		// Message in a bottle?
		//------------------------------------------

		if( $this->request['messageinabottleacp'] )
		{
			$this->request['messageinabottleacp'] = IPSText::getTextClass('bbcode')->xssHtmlClean( IPSText::UNhtmlspecialchars( urldecode( $this->request['messageinabottleacp'] ) ) );
			$this->registry->output->global_message = $this->request['messageinabottleacp'];
		}
	}
	
	/**
	 * Fetch mod_rewrite rules
	 *
	 * @access	public
	 * @return	string
	 */
	public function fetchModRewrite()
	{
		$rules  = '';
		$_parse = parse_url( $this->settings['base_url'] );
		$_root  = preg_replace( "#/$#", "", $_parse['path'] );
		$_root  = str_replace( '/' . CP_DIRECTORY, '', $_root );
		$_root  = str_replace( 'index.php', '', $_root );
		
		$rules  = "&lt;IfModule mod_rewrite.c&gt;\n";
		$rules .= "Options -MultiViews\n";
		$rules .= "RewriteEngine On\n";
		$rules .= "RewriteBase $_root\n";
		$rules .= "RewriteCond %{REQUEST_FILENAME} !-f\n" .
				  "RewriteCond %{REQUEST_FILENAME} !-d\n" .
				  "RewriteCond %{REQUEST_URI} !\\..+$\n" .
				  "RewriteRule . {$_root}index.php [L]\n";
		$rules .= "&lt;/IfModule&gt;\n";
		
		return $rules;
	}
	
	/**
	 * Get the staff member's "cookie"
	 *
	 * @access	public
	 * @param	string		Key
	 * @param	integer		Member ID [defaults to current member]
	 * @return	mixed		Stored cookie
	 */
	public function staffGetCookie( $key, $id=0 )
	{
		//-----------------------------------------
		// INIT, Yes, it is.
		//-----------------------------------------
		
		$id = ( $id ) ? $id : $this->memberData['member_id'];
		
		$_test = $this->DB->buildAndFetch( array( 
														'select' => 'sys_login_id, sys_cookie',
														'from'   => 'core_sys_login',
														'where'  => 'sys_login_id=' . intval( $id ) 
												)	);
		
		$cookie = ( $_test['sys_cookie'] ) ? unserialize( $_test['sys_cookie'] ) : array();

		return $cookie[ $key ];
	}
	
	/**
	 * Update the member's "cookie"
	 *
	 * @access	public
	 * @param	string		Key
	 * @param	mixed		Data
	 * @param	integer		Member id [defaults to current member]
	 * @return	boolean
	 */
	public function staffSaveCookie( $key, $data, $id=0 )
	{
		//-----------------------------------------
		// INIT, Yes, it is.
		//-----------------------------------------
		
		$id = ( $id ) ? $id : $this->memberData['member_id'];
		
		$_test = $this->DB->buildAndFetch( array( 
														'select' => 'sys_login_id, sys_cookie',
														'from'   => 'core_sys_login',
														'where'  => 'sys_login_id=' . intval( $id ) 
												)	);
		
		$cookie         = ( $_test['sys_cookie'] ) ? unserialize( $_test['sys_cookie'] ) : array();
		$cookie[ $key ] = $data;
										
		if ( $_test['sys_login_id'] )
		{
			$this->DB->update( 'core_sys_login', array( 'sys_cookie' => serialize( $cookie ) ), 'sys_login_id=' . intval( $id ) );
		}
		else
		{
			$this->DB->insert( 'core_sys_login', array( 'sys_cookie' => serialize( $cookie ), 'sys_login_id' => intval( $id ) ) );
		}
		
		return TRUE;
	}
	
	/**
 	 * Generate a md5 hash, used for authenticating forms
 	 *
 	 * @access	public
 	 * @return	string		MD5 secure hash
 	 * @deprecated	Don't think we are using this anywhere now
 	 * @see		getSecurityKey()
 	 **/	
	public function generateSecureHash()
	{
		/* Generate Secure Hash */
		$ip_octets  = explode( ".", $this->ip_address );
        $crypt_salt = md5( $this->settings['sql_user'].$this->settings['sql_database'].$this->settings['sql_pass'] );
        $key        = md5( crypt( $this->memberData['member_joined'] . "(&)" . $ip_octets[0] . '(&)' . $ip_octets[1] . '(&)' . $this->memberData['member_login_key'], $crypt_salt ) );

		return $key;
	}
	
	/**
 	 * Generate a md5 hash, used for authenticating forms
 	 *
 	 * @access	public
 	 * @return	string		MD5 secure hash
 	 * @see		checkSecurityKey()
 	 * @see		getSecurityKey()
 	 **/	
	public function getSecurityKey()
	{
		return md5( $this->memberData['email'] . '^' . $this->memberData['joined'] . '^' . $this->memberData['ip_address'] . md5( $this->settings['sql_pass'] ) );
	}

	/**
	 * Checks the security key
	 *
	 * @access	public
	 * @param	string		md5 auth key [defaults to $_POST['_admin_auth_key']]
	 * @param	boolean		return and not die?
	 * @return	mixed		boolean false or outputs error on failure, else true
	 * @see		getSecurityKey()
	 */
	public function checkSecurityKey( $auth_key='', $return_and_not_die=false )
	{
		$auth_key = ( $auth_key ) ? $auth_key : trim( $_POST['_admin_auth_key'] );
		
		if ( $auth_key != $this->getSecurityKey() )
		{
			if ( $return_and_not_die )
			{
				return FALSE;
			}
			else
			{
				$this->registry->output->showError( $this->lang->words['func_security_mismatch'], 2100 );
				exit();
			}
		}
		
		return true;
	}
	
	/**
	 * Save an entry to the admin logs
	 *
	 * @access	public
	 * @param	string		Action
	 * @return	boolean
	 */
	public function saveAdminLog( $action="" )
	{
		$this->DB->insert( 'admin_logs', array(
													'appcomponent' => $this->request['app'],
													'module'       => $this->request['module'],
													'section'      => $this->request['section'],
													'do'           => $this->request['do'],
													'member_id'    => $this->memberData['member_id'],
													'ctime'        => time(),
													'note'         => $action,
													'ip_address'   => $this->member->ip_address,
												)
							);
		
		return true;
	}
	
	/**
	 * Import an XML file either from a fixed server location
	 * or via the upload fields. Upload fields are checked first
	 *
	 * @access	public
	 * @param	string		File location
	 * @return	string		XML contents
	 */
	public function importXml( $location='' )
	{
		//-----------------------------------------
		// Upload
		//-----------------------------------------
		
		$FILE_NAME = $_FILES['FILE_UPLOAD']['name'];
		$FILE_TYPE = $_FILES['FILE_UPLOAD']['type'];
		
		//-----------------------------------------
		// Naughty Opera adds the filename on the end of the
		// mime type - we don't want this.
		//-----------------------------------------
		
		$FILE_TYPE = preg_replace( "/^(.+?);.*$/", "\\1", $FILE_TYPE );
		$content   = "";

		if ( $FILE_NAME AND ( $FILE_NAME != 'none' ) )
		{
			if ( move_uploaded_file( $_FILES[ 'FILE_UPLOAD' ]['tmp_name'], DOC_IPS_ROOT_PATH . "uploads/" . $FILE_NAME ) )
			{
				$location = DOC_IPS_ROOT_PATH . "uploads/" . $FILE_NAME;
			}
		}

		/* Now load it... $location could have been set by the upload check...*/
		if ( file_exists( $location ) )
		{
			if ( substr( $location, -3 ) == '.gz' )
			{
				if ( $FH = @gzopen( $location, 'rb' ) )
				{
				 	while ( ! @gzeof( $FH ) )
				 	{
				 		$content .= @gzread( $FH, 1024 );
				 	}
				 	
					@gzclose( $FH );
				}
			}
			else if ( substr( $location, -4 ) == '.xml' )
			{
				$content = file_get_contents( $location );
			}
		}

		/* Unlink the tmp file if it exists */
		if ( file_exists( DOC_IPS_ROOT_PATH . "uploads/" . $FILE_NAME ) )
		{
			@unlink( DOC_IPS_ROOT_PATH . "uploads/" . $FILE_NAME );
		}
		
		return $content;
	}
	
	/**
	 * Copy a directory
	 *
	 * @access	public
	 * @param	string		From path
	 * @param	string		To path
	 * @param	integer		Octal permissions value
	 * @return	boolean
	 * @see		classFileManagement::copyDirectory()
	 * @deprecated	With the presence of the kernel class, we should deprecate the usage of this method
	 */
	public function copyDirectory($from_path, $to_path, $mode = 0777)
	{
		$this->errors = "";
		
		//-----------------------------------------
		// Strip off trailing slashes...
		//-----------------------------------------
		
		$from_path = rtrim( $from_path, '/' );
		$to_path   = rtrim( $to_path, '/' );
	
		if ( ! is_dir($from_path) )
		{
			$this->errors = "Could not locate directory '$from_path'";
			return FALSE;
		}
	
		if ( ! is_dir($to_path) )
		{
			if ( ! @mkdir($to_path, $mode) )
			{
				$this->errors = "Could not create directory '$to_path' please check the CHMOD permissions and re-try";
				return FALSE;
			}
			else
			{
				@chmod($to_path, $mode);
			}
		}
		
		if (is_dir($from_path))
		{
			$handle = opendir($from_path);
			
			while (($file = readdir($handle)) !== false)
			{
				if (($file != ".") && ($file != ".."))
				{
					if ( is_dir( $from_path."/".$file ) )
					{
						$this->copyDirectory($from_path."/".$file, $to_path."/".$file);
					}
					
					if ( is_file( $from_path."/".$file ) )
					{
						copy($from_path."/".$file, $to_path."/".$file);
						@chmod($to_path."/".$file, 0777);
					} 
				}
			}
			closedir($handle); 
		}
		
		if ($this->errors == "")
		{
			return TRUE;
		}
	}
	
	/**
	 * Remove a directory (and all of its contents)
	 *
	 * @access	public
	 * @param	string		Directory or filename
	 * @return	boolean
	 * @see		classFileManagement::removeDirectory()
	 * @deprecated	With the presence of the kernel class, we should deprecate the usage of this method
	 */
	public function removeDirectory($file)
	{
		$errors = 0;
		
		//-----------------------------------------
		// Remove trailing slashes..
		//-----------------------------------------
		
		$file = rtrim( $file, '/' );
		
		if ( file_exists($file) )
		{
			//-----------------------------------------
			// Attempt CHMOD
			//-----------------------------------------
			
			@chmod($file, 0777);
			
			if ( is_dir($file) )
			{
				$handle = opendir($file);
				
				while (($filename = readdir($handle)) !== false)
				{
					if (($filename != ".") && ($filename != ".."))
					{
						$this->removeDirectory($file."/".$filename);
					}
				}
				
				closedir($handle);
				
				if ( ! @rmdir($file) )
				{
					$errors++;
				}
			}
			else
			{
				if ( ! @unlink($file) )
				{
					$errors++;
				}
			}
		}
		
		if ($errors == 0)
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
}