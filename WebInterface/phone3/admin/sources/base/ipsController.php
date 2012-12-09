<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Primary controller
 * Last Updated: $LastChangedDate: 2009-08-03 18:02:42 -0400 (Mon, 03 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @since		14th May 2003
 * @version		$Rev: 4966 $
 */

/**
* Class "Controller"
* A very simple public facing interface to resolve incoming data into
* a command class
*
* @author	Matt Mecham
* @since	Wednesday 14th May 2008
* @package	Invision Power Board
*/
class ipsController
{
	/**
	 * Registry reference
	 *
	 * @access	private
	 * @var		object
	 */
	private $registry;
	
	/**
	 * Command
	 *
	 * @access	public
	 * @var		string
	 */
	static public $cmd;

	/**
	 * Constructor
	 *
	 * @access	private
	 * @return	void
	 */
	private function __construct() { }

	/**
	 * Public facing function to run the controller
	 *
	 * @access	public
	 * @return	void
	 */
	static public function run()
	{
		$instance = new ipsController();
		$instance->init();
		$instance->handleRequest();
	}

	/**
	 * Initialize ipsRegistry and this class
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function init()
	{
		$this->registry = ipsRegistry::instance();
		$this->registry->init();
	}

	/**
	 * Handle the incoming request using the command resolver
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function handleRequest()
	{
		$cmd_r     = new ipsController_CommandResolver();
		self::$cmd = $cmd_r->getCommand( $this->registry );
		
		IPSDebug::setMemoryDebugFlag( "Everything up until execute call", 0 );
		
		self::$cmd->execute( $this->registry );
	}
}

/**
* Class "Command Resolver"
* Resolves the incoming data
*
* @author	Matt Mecham
* @since	Wednesday 14th May 2008
* @package	Invision Power Board
*/
class ipsController_CommandResolver
{
	/**#@+
	 * Internal strings to remember
	 *
	 * @access	private
	 * @var		string
	 */
	private static $base_cmd;
	private static $ajax_cmd;
	private static $default_cmd;
	private static $modules_dir  = 'modules_public';
	private static $class_dir    = 'public';
	/**#@-*/
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	void
	 */
	public function __construct()
	{
		if ( ! self::$base_cmd )
		{
			self::$base_cmd    = ipsRegistry::$current_module == 'ajax' ? new ReflectionClass( 'ipsAjaxCommand' ) : new ReflectionClass( 'ipsCommand' );
			self::$default_cmd = new ipsCommand_default();
			self::$modules_dir = ( IPS_AREA != 'admin' ) ? 'modules_public' : 'modules_admin';
			self::$class_dir   = ( IPS_AREA != 'admin' ) ? 'public'         : 'admin';
		}
	}

	/**
	 * Retreive the command
	 *
	 * @access	public
	 * @param	object		ipsRegistry reference
	 * @return	object
	 */
	public function getCommand( ipsRegistry $registry )
	{
		$_NOW = IPSDebug::getMemoryDebugFlag();
		
		$module    = ipsRegistry::$current_module;
		$section   = ipsRegistry::$current_section;
		$filepath  = IPSLib::getAppDir( IPS_APP_COMPONENT ) . '/' . self::$modules_dir . '/' . $module . '/';

		/* Got a section? */
		if ( ! $section )
		{
			if ( file_exists( $filepath . 'defaultSection.php' ) )
			{
				$DEFAULT_SECTION = '';
				require( $filepath . 'defaultSection.php' );

				if ( $DEFAULT_SECTION )
				{
					$section = $DEFAULT_SECTION;
				}
			}
		}

		$classname = self::$class_dir . '_' .  IPS_APP_COMPONENT . '_' . $module . '_' . $section;

		if ( file_exists( $filepath . 'manualResolver.php' ) )
		{
			require_once( $filepath . 'manualResolver.php' );
			$classname = self::$class_dir . '_' .  IPS_APP_COMPONENT . '_' . $module . '_manualResolver';
		}
		else if ( file_exists( $filepath . $section . '.php' ) )
		{
			require_once( $filepath . $section . '.php' );
		}

		/* Hooks: Are we overloading this class? */

		$hooksCache	= ipsRegistry::cache()->getCache('hooks');

		if( isset( $hooksCache['commandHooks'] ) AND is_array( $hooksCache['commandHooks'] ) AND count( $hooksCache['commandHooks'] ) )
		{
			foreach( $hooksCache['commandHooks'] as $hook )
			{
				foreach( $hook as $classOverloader )
				{
					/* Hooks: Do we have a hook that extends this class? */
	
					if( $classOverloader['classToOverload'] == $classname )
					{
						if( file_exists( DOC_IPS_ROOT_PATH . 'hooks/' . $classOverloader['filename'] ) )
						{
							/* Hooks: Do we have the hook file? */
	
							require_once( DOC_IPS_ROOT_PATH . 'hooks/' . $classOverloader['filename'] );
	
							if( class_exists( $classOverloader['className'] ) )
							{
								/* Hooks: We have the hook file and the class exists - reset the classname to load */
	
								$classname = $classOverloader['className'];
							}
						}
					}
				}
			}
		}
		
		IPSDebug::setMemoryDebugFlag( "Controller getCommand executed", $_NOW );

		if ( class_exists( $classname ) )
		{
			$cmd_class = new ReflectionClass( $classname );

			if ( $cmd_class->isSubClassOf( self::$base_cmd ) )
			{
				return $cmd_class->newInstance();
			}
			else
			{
				throw new Exception( "$section in $module does not exist!" );
			}
		}

		# Fudge it to return just the default object
		return clone self::$default_cmd;
	}
}


abstract class ipsCommand
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
	protected $memberData;
	protected $cache;
	protected $caches;
	/**#@-*/

	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	void
	 */
	final public function __construct()
	{
	}

	/**
	 * Make the registry shortcuts
	 *
	 * @access	public
	 * @param	object	ipsRegistry reference
	 * @return	void
	 */
	public function makeRegistryShortcuts( ipsRegistry $registry )
	{
		/* Make registry objects */
		$this->registry   =  $registry;
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang       =  $this->registry->getClass('class_localization');
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
	}

	/**
	 * Execute the command (call doExecute)
	 *
	 * @access	public
	 * @param	object	ipsRegistry reference
	 * @return	void
	 */
	public function execute( ipsRegistry $registry )
	{
		$this->makeRegistryShortcuts( $registry );
		$this->doExecute( $registry );
	}

	/**
	 * Do execute method (must be overriden)
	 *
	 * @access	protected
	 * @param	object	ipsRegistry reference
	 * @return	void
	 */
	protected abstract function doExecute( ipsRegistry $registry );
}

/**
 * Abstract class for handling ajax requests
 **/


abstract class ipsAjaxCommand
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
	protected $cache;
	protected $ajax;
	/**#@-*/
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @return	void
	 */
	final public function __construct()
	{
	}

	/**
	 * Magic function to catch all the ajax methods
	 *
	 * @access	public
	 * @param	string  $func       Function name being called
	 * @param	array   $arguments  Array of parameters
	 * @return	mixed
	 **/
	public function __call( $func, $arguments )
	{
		if( method_exists( $this->ajax, $func ) )
		{
			foreach( $arguments as $k => $v )
			{
				if( is_string( $v ) )
				{
					/* Remove unused hook comments */
					$arguments[$k] = preg_replace( "#<!--hook\.([^\>]+?)-->#", '', $arguments[$k] );
				}
			}
			
			return call_user_func_array( array( $this->ajax, $func ), $arguments );
		}
	}

	/**
	 * Creates all the registry shorctus
	 *
	 * @access	public
	 * @param	object	ipsRegistry reference
	 * @return	void
	 **/
	public function makeRegistryShortcuts( ipsRegistry $registry )
	{
		/* Make registry objects */
		$this->registry   =  $registry;
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang       =  $this->registry->getClass('class_localization');
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();

		require_once( IPS_KERNEL_PATH . 'classAjax.php' );
		$this->ajax = new classAjax();
		
		IPSDebug::fireBug( 'registerExceptionHandler' );
		IPSDebug::fireBug( 'registerErrorHandler' );
	}

	/**
	 * Executes the ajax request, checks secure key
	 *
	 * @access	public
	 * @param	object	ipsRegistry reference
	 * @return	void
	 **/
	public function execute( ipsRegistry $registry )
	{
		/* Setup Shortcuts First */
		$this->makeRegistryShortcuts( $registry );
		
		/* Check the secure key */
		$this->request['secure_key'] = $this->request['secure_key'] ? $this->request['secure_key'] : $this->request['md5check'];

		//if( $this->request['secure_key'] && $this->request['secure_key'] != $this->member->form_hash )
		if( $this->request['secure_key'] != $this->member->form_hash )
		{
			IPSDebug::fireBug( 'error', array( "The security key did not match the member's form hash" ) );

			$this->returnString( 'nopermission' );
		}
		
		$this->doExecute( $registry );
	}

	/**
	 * Do execute method (must be overriden)
	 *
	 * @access	protected
	 * @param	object	ipsRegistry reference
	 * @return	void
	 */
	protected abstract function doExecute( ipsRegistry $registry );
}


class ipsCommand_default extends ipsCommand
{
	/**
	 * Do execute method
	 *
	 * @access	protected
	 * @param	object	ipsRegistry reference
	 * @return	void
	 */
	protected function doExecute( ipsRegistry $registry )
	{
		$modules_dir = ( IPS_AREA != 'admin' ) ? 'modules_public' : 'modules_admin';
		$filepath    = IPSLib::getAppDir(  IPS_APP_COMPONENT ) . '/' . $modules_dir . '/' . ipsRegistry::$current_module . '/' . ipsRegistry::$current_section . '.php';
		$filepath	 = str_replace( DOC_IPS_ROOT_PATH, '', $filepath );

		//-----------------------------------------
		// Redirect to board index
		//-----------------------------------------

		if ( ! (IPS_APP_COMPONENT == 'forums' AND ipsRegistry::$current_module == 'forums' AND ipsRegistry::$current_section == 'boards') )
		{
			if( IPB_THIS_SCRIPT == 'admin' )
			{
				$registry->output->silentRedirect( ipsRegistry::$settings['_base_url'] );
			}
			else
			{
				$registry->output->silentRedirect( ipsRegistry::$settings['_original_base_url'] );
			}
		}

		//-----------------------------------------
		// Uh oh, this is a big one.. (no forums app)
		//-----------------------------------------

		if ( ! file_exists( $filepath ) )
		{
			$this->registry->getClass('output')->showError( array( 'command_file_missing', $filepath ), 401100 );
		}
		else
		{
			$this->registry->getClass('output')->showError( array( 'command_class_incorrect', $filepath ), 401200 );
		}
	}
}