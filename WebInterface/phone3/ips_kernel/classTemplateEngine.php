<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Template Parser : Converts HTML "logic" into PHP code
 * Last Updated: $Date: 2009-05-14 14:21:47 -0400 (Thu, 14 May 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Services Kernel
 * @since		Wednesday 23rd February 2005 13:53
 * @version		$Revision: 263 $
 * 
 * @todo 		[Future] Explore converting this to an XSLT parser (ala Digi)
 */

define( 'IPS_TEMPLATE_DEBUG'     , 0 );
define( 'IPS_TEMPLATE_DEBUG_FILE', dirname( __FILE__ ) . '/../cache/template_debug.cgi' );

/**
 * INTERFACE: Template plugins
 * @author: Matt Mecham
 * @since: IPB 3.0.0
 */

interface interfaceTemplatePlugins
{
	/**
	* Return information about this modifier
	*
	* It MUST contain an array  of available options in 'options'. If there are no allowed options, then use an empty array.
	* Failure to keep this up to date will most likely break your template tag.
	* @return   array
	*/
	public function getPluginInfo();
	
	/**
	* Actually run the modifiers
	*
	* @param	mixed 		Data to pass to plugin
	* @param	mixed		Options to pass to plugin
	*/
	public function runPlugin( $data, $options );
}
 
class classTemplate
{
	/**
	* Root path
	*
	* @access	public
	* @var		string		Root path
	*/
	public $root_path		= './';
	
	/**
	* Skin cache files directory
	*
	* @access	public
	* @var		string		Directory name to put skin cache files in
	*/
	public $cache_dir		= 'skin_cache';
	
	/**
	* Current ID of skinset
	*
	* @access	public
	* @var		integer		Skin set id
	*/
	public $cache_id		= 1;
	
	/**
	* Skin set id in database
	*
	* @access	public
	* @var		integer		Skin set id
	*/
	public $database_id		= 1;
	
	/**
	* Cache path
	*
	* @access	public
	* @var		string		Cache path for skin sets
	*/
	public $IPS_CACHE_PATH  = '';
	
	/**
	* Foreach function blocks
	* Holds the foreach code
	*
	* @access	private
	* @var  	array 		Holds foreach blocks
	*/
	private $foreach_blocks = array();
	
	/**
	* Allow PHP code to execute
	*
	* @access	public
	* @var  	boolean 	Execute <php></php> php code
	*/
	public $allow_php_code	= true;
	
	/**
	* Modifiers path
	*
	* @access	private
	* @var  	string 		Path to modifiers directory
	*/
	static private $template_plugins_class_path;
	
	/**
	* Modifiers clases
	*
	* @access	private
	* @var  	array 		Array of modifier classes
	*/
	static private $template_plugin_classes = array();
	
	private $_skinGroup = '';
	
	/**
	* Constructor
	*
	* @access	public
	* @param	string		Path to template plugins directory
	* @return	boolean		Construction successful
	*/
	public function __construct( $template_plugins_class_path='' )
	{
		self::$template_plugins_class_path = $template_plugins_class_path;
		
		$this->IPS_CACHE_PATH = $this->root_path . $this->cache_dir . '/cacheid_' . $this->cache_id;
		
		return true;
	}
	
	public function setWorkingGroup( $group )
	{
		$this->_skinGroup = $group;
	} 

	/**
	* convert_cache_to_eval
	*
	* Takes raw PHP data (from a MASTER cache class) and formats it ready
	* for eval'ing in the scripts.
	*
	* @access	public
	* @param	string		The actual contents of the class, php tags 'an all.
	* @param	string		Full class name
	* @param	integer		ID to use
	* @return	string		Ready to eval PHP code
	*/
	public function convertCacheToEval($data='', $full_class_name='', $id=1)
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$this->_skinGroup = preg_replace( "#^(.*)_\d+?$#", "\\1", $full_class_name );
		
		$final_content = $this->_returnFunctionData( $data );
		
		//-----------------------------------------
		// Return the stuff...
		//-----------------------------------------
		
		$out  = "class {$full_class_name} {\n\n";
		$out .=
<<<EOF
/**
* Construct
*/
function __construct( ipsRegistry \$registry )
{
	/* Make object */
	\$this->registry   =  \$registry;
	\$this->DB         =  \$this->registry->DB();
	\$this->settings   =& \$this->registry->fetchSettings();
	\$this->request    =& \$this->registry->fetchRequest();
	\$this->lang       =  \$this->registry->getClass('class_localization');
	\$this->member     =  \$this->registry->member();
	\$this->memberData =& \$this->registry->member()->fetchMemberData();
	\$this->cache      =  \$this->registry->cache();
	\$this->caches     =& \$this->registry->cache()->fetchCaches();
}

EOF;
		$out .= $final_content;
		$out .= "\n\n}";

		return $out;
	}
	
	/**
	* _returnFunctionData
	*
	* Extracts function data
	*
	* @access	private
	* @param	string		The actual contents of the class, php tags 'an all.
	* @param	array 		Functions to find
	* @return	string		Converted content
	*/
	private function _returnFunctionData( $data='', $function_find=array() )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$final_content = '';
		
		//-----------------------------------------
		// Remove PHP tags
		//-----------------------------------------

		$data = preg_replace( "#<"."\?php\n+?(.+?)\n+?\?".">#is", "\\1", $data );

		//-----------------------------------------
		// Pick through and get all function names
		//-----------------------------------------
		
		$data = str_replace( "\r"  , "\n", $data );
		$data = str_replace( "\n\n", "\n", $data );
							
		//-----------------------------------------
		// Continue...
		//-----------------------------------------
		
		$farray = explode( "\n", $data );
		
		//-----------------------------------------
		// Functions...
		//-----------------------------------------
		
		$functions    = array();
		$script_token = 0;
		$flag         = 0;
		
		foreach( $farray as $f )
		{
			//-----------------------------------------
			// Skip javascript functions...
			//-----------------------------------------
			
			if ( preg_match( "/<script/i", $f ) )
			{
				$script_token = 1;
			}
			
			if ( preg_match( "/<\/script>/i", $f ) )
			{
				$script_token = 0;
			}
			
			//-----------------------------------------
			// NOT IN JS
			//-----------------------------------------
			
			if ( $script_token == 0 )
			{
				if ( preg_match( "/^function\s*([\w\_]+)\s*\((.*)\)/i", $f, $matches ) )
				{
					$functions[ $matches[1] ] = '';
					$config[ $matches[1] ]    = $matches[2];
					$flag                     = $matches[1];
					continue;
				}
			}
			
			if ( $flag )
			{
				$functions[ $flag ] .= $f."\n";
				continue;
			}
		}

		//-----------------------------------------
		// Build the file...
		//-----------------------------------------
		
		foreach( $functions as $fname => $ftext )
		{
			if ( is_array( $function_find ) AND count( $function_find ) )
			{
				if ( ! in_array( $fname, $function_find ) )
				{
					continue;
				}
			}
			
			$func_data = trim( $config[$fname] );
			
			# IPB 3.0 Addition:
			# Preserve magic methods
			if ( preg_match( "#^__(.*)$#", $fname ) )
			{
				$final_content .= "function {$fname}({$func_data})\n" .  $ftext;
			}
			else
			{
				preg_match( "/".'\$'."IPBHTML\s+?\.?=\s+?<<<EOF(.+?)EOF;\s?/si", $ftext, $matches );
				
				$final_content .= $this->convertHtmlToPhp( $fname, $func_data, isset( $matches[1] ) ? $matches[1] : '' );
			}
		}
		
		//-----------------------------------------
		// Return...
		//-----------------------------------------
		
		return $final_content;
	}
	
	/**
	* convertHtmlToPhp
	*
	* Converts HTML logic to PHP code
	*
	* @access	public
	* @param	string		Function name
	* @param	string 		Function parameters
	* @param	string 		Function HTML content
	* @param	string 		Function description
	* @param	boolean		Trigger skin update
	* @param	boolean 	Compile into php code
	* @return	string		Converted content
	*/
	public function convertHtmlToPhp($func_name, $func_data, $func_html, $func_desc="", $com_bit_update_trigger='', $compile=1)
	{
		//-------------------------------
		// INIT
		//-------------------------------
		
		$this->foreach_blocks = array();
		
		//-----------------------------------------
		// Debug?
		//-----------------------------------------
		
		$this->_addDebug( "Preparing to convert: $func_name" . "\n" . $func_html );
		
		//-------------------------------
		// Make sure we have ="" on each
		// func data
		//-------------------------------
		
		// or not...Bug 8570
		//$func_data = preg_replace( "#".'\$'."(\w+)(,|$)#i", "\$\\1=\"\"\\2", str_replace( " ", "", $func_data ) );
		
		if ( $compile )
		{
			$_func_code = '"' . $this->compileHtmlToPhp( trim( $func_html ), $func_data, $func_name ) . '"';
		}
		else
		{
			$_func_code = "<<<EOF\n" . $this->unconvertTags( $func_html ) . "\nEOF";
		}
		
		//-----------------------------------------
		// Plug in tags
		//-----------------------------------------
		
		$_func_code = $this->_processPluginTags( $_func_code );
		
		//-----------------------------------------
		// Fix up {IPSText::xxxxx}
		//-----------------------------------------
		
		$_func_code = preg_replace( "#\{IPS(Data|ActiveMember|Member|Lib|Text)\:\:([^\}]+?)\}#", '" . ' . "IPS\\1::\\2"      . ' . "', $_func_code );
		$_func_code = preg_replace( "#\{\\\$this->(settings|request)->([^\}]+?)\}#"     , '" . ' . "\$this->\\1->\\2" . ' . "', $_func_code );
		
		//-----------------------------------------
		// Make safe.. clear up any potential escapes
		//-----------------------------------------
		
		$_func_code = preg_replace( "#\\\{1,}\"#s", '\\"', $_func_code );
		
		//-----------------------------------------
		// Continue...
		//-----------------------------------------
		
		/*$top    = "//===========================================================================\n".
			      "// <ips:{$func_name}:desc:{$func_desc}:trigger:{$com_bit_update_trigger}>\n".
			      "//===========================================================================\n";*/
		$top = "/* -- {$func_name} --*/\n";
		$start  = "function {$func_name}($func_data) {\n\$IPBHTML = \"\";\n";
		$middle = '$IPBHTML .= '.$_func_code.';';
		
		$end    = "\nreturn \$IPBHTML;\n}\n";
		
		//-------------------------------
		// Add foreach blocks...
		//-------------------------------
		
		if ( count( $this->foreach_blocks ) )
		{
			$end .= "\n\n" . implode( "\n\n", $this->foreach_blocks ) . "\n";
			
			//-----------------------------------------
			// Plug in tags
			//-----------------------------------------

			$end = $this->_processPluginTags( $end );
			
			$end = preg_replace( "#\{IPS(Data|ActiveMember|Member|Lib|Text)\:\:([^\}]+?)\}#", '" . ' . "IPS\\1::\\2"      . ' . "', $end );
			$end = preg_replace( "#\{\\\$this->(settings|request)->([^\}]+?)\}#"     , '" . ' . "\$this->\\1->\\2" . ' . "', $end );
			
			//----------------------------------------
			// Check embedded foreach blocks
			//----------------------------------------
			
			if ( strstr( $end, "<xxforeach" ) )
			{
				$end = preg_replace( "#[\n\r]{0,}<xxforeach_([^>]+?)xx>(.+?)</xxforeach_\\1xx>[\n\r]{0,}#si", "\".\\2.\"", $end );
			}
			
			//-----------------------------------------
			// Remove raw PHP tags
			//-----------------------------------------

			$end = preg_replace( "#([\n\r]{0,})?<php>(.+?)</php>([\n\r]{0,})?#si", "", $end );
		}
		
		//-----------------------------------------
		// Sort out the rest of the PHP tags
		//-----------------------------------------
		
		$php_tags   = $this->_processPhpTags( $_func_code );
		
		//-----------------------------------------
		// Remove raw PHP tags
		//-----------------------------------------
		
		$middle = preg_replace( "#([\n\r]{0,})?<php>(.+?)</php>([\n\r]{0,})?#si", "", $middle );
		
		//-----------------------------------------
		// Empty if statements lead to a lot of newlines in the source
		// Try to clean em up a little
		//-----------------------------------------

		$middle = $this->_trimEmptyIfs( $middle );
		
		/* Clean up hook comments a little */
		//$middle = preg_replace( "#([\n\s]+)?(<!--hook\.([^\>]+?)-->)([\n\s]+)#is", "\\2", $middle );
		//$end    = preg_replace( "#([\n\s]+)?(<!--hook\.foreach\.([^\>]+?)\.inner\.post-->)([\n\s]+)?#is", "\\2", $end );
		
		//$end    = preg_replace( "#(<!--hook\.([^\>]+?)-->)(\s+?)(<!--hook\.)#is", "\\1\\4", $end );
		//$end    = preg_replace( "#(.pre.else-->)(\n{0,})?#", "\\1\n", $end );
		
		//-----------------------------------------
		// Debug?
		//-----------------------------------------
		
		$this->_addDebug( "FINISHED: $func_name" . "\n" . $top.$start.$php_tags.$middle.$end );
		
		//-------------------------------
		// Return
		//-------------------------------
		
		return $top.$start.$php_tags.$middle.$end;
	}
	
	/**
	* compileHtmlToPhp
	*
	* Compiles ready to cache PHP code
	*
	* @access	public
	* @param	string		Content
	* @param	string 		Normal function parameters
	* @return	string		Converted content
	*/
	public function compileHtmlToPhp( $text, $normal_func_data='', $func_name='' )
	{
		//----------------------------------------
		// INIT
		//----------------------------------------
		
		$do_foreach = 0;
		$do_if      = 0;
		
		//----------------------------------------
		// First pass...
		//----------------------------------------
		
		if ( strstr( $text, "<foreach loop=" ) )
		{
			$do_foreach = 1;
		}
		
		//----------------------------------------
		// Second pass...
		//----------------------------------------
		
		if ( strstr( $text, "<if test=" ) )
		{
			$do_if = 1;
		}
		
		//----------------------------------------
		// Add slashes if required
		//----------------------------------------
		
		if ( $do_if OR $do_foreach )
		{
			$text = addslashes( $text );
		}
		else
		{
			return str_replace( '"', '\\"', $text );
		}
		
		//----------------------------------------
		// HTML FOREACH logic...
		//----------------------------------------
			
		if ( $do_foreach )
		{
			$text = $this->_processForeachLogic( $text, $normal_func_data, $func_name );
		}
		
		//----------------------------------------
		// HTML IF/ELSE logic...
		//----------------------------------------
			
		if ( $do_if )
		{
			$text = $this->_processHtmlLogic( $text, $func_name );
		}
		
		//----------------------------------------
		// Last pass...
		//----------------------------------------
		
		if ( $do_foreach )
		{
			if ( strstr( $text, "<xxforeach" ) )
			{
				$text = preg_replace( "#[\n\r]{0,}<xxforeach_([^>]+?)xx>(.+?)</xxforeach_\\1xx>[\n\r]{0,}#si", "\".\\2.\"", $text );
			}
		}
		
		//----------------------------------------
		// Make code OK
		//----------------------------------------
		
		if ( $do_if OR $do_foreach )
		{
			$text = str_replace('\\\\$', '\\$', $text);
		}

		return $text;
	}
	
	/**
	* _processForeachLogic
	*
	* Process the HTML logic 'foreach' tags
	*
	* @access	private
	* @param	string		Content
	* @param	string 		Normal function parameters
	* @return	string		Converted content
	*/
	private function _processForeachLogic( $text, $normal_func_data='', $func_name='' )
	{
		//----------------------------------------
		// INIT
		//----------------------------------------
		
		$total_length = strlen( $text );
		$template     = $text;
		$statement    = "";
		$arg_true     = "";
		$arg_false    = "";
		
		# Tag specifics
		$tag_foreach       = '<foreach loop=';
		$found_foreach     = -1;
		$tag_end_foreach   = '</foreach>';
		$found_end_foreach = -1;
		
		$allow_delim  = array( '"', '\'' );
		
		$_tmp_func_data    = explode( ",", $normal_func_data );
		$_final            = array();
		$clean_func_data   = '';
		
		//----------------------------------------
		// Get function arguments
		//----------------------------------------
		
		foreach( $_tmp_func_data as $_i )
		{
			preg_match( "#".'\$'."(\w+)(=|,|$)#i", $_i, $match );
			
			if( count($match) )
			{
				$_final[] = '$'.$match[1];
			}
		}

		$clean_func_data = implode( ",", $_final );

		//----------------------------------------
		// Keep the server busy for a while
		//----------------------------------------
		
		while ( 1 == 1 )
		{
			$_end = 0;
			
			//----------------------------------------
			// Look for opening <if tag...
			//----------------------------------------
			
			$found_foreach = strpos( $template, $tag_foreach, $found_end_foreach + 1 );
			
			//----------------------------------------
			// No logic found? 
			//----------------------------------------
			
			if ( $found_foreach === FALSE )
			{
				break;
			}
			
			//----------------------------------------
			// Beginning of the logic...
			//----------------------------------------
			
			$_start = $found_foreach + strlen($tag_foreach) + 2;
			
			$delim  = $template[ $_start - 1 ];
			
			//----------------------------------------
			// Make sure we have statement wrapped in
			// either ' or "
			//----------------------------------------
			
			if ( ! in_array( $delim, $allow_delim ) )
			{
				$found_end_foreach = $found_foreach + 1;
				continue;
			}
			
			//----------------------------------------
			// End statement?
			//----------------------------------------
			
			$found_end_foreach = strpos($template, $tag_end_foreach, $_end + 3);
			
			//----------------------------------------
			// No end statement found
			//----------------------------------------
			
			if ( $found_end_foreach === FALSE )
			{ 
				return str_replace("\\'", '\'', $template);
			}
			
			//----------------------------------------
			// Find end of statement
			//----------------------------------------
			
			for ( $i = $_start; $i < $total_length; $i++ )
			{
				if ( $template[ $i ] == $delim AND $template[$i - 2] != '\\' AND $template[$i + 1] == '>' )
				{
					//----------------------------------------
					// Unescaped end delimiter
					//----------------------------------------
					
					$_end = $i - 1;
					break;
				}
			}
			
			//----------------------------------------
			// No end statement found
			//----------------------------------------
			
			if ( ! $_end )
			{
				return str_replace("\\'", '\'', $template);
			}
			
			//----------------------------------------
			// Get statement
			//----------------------------------------
			
			$statement = $this->unconvertTags( substr( $template, $_start, $_end - $_start ) );
			
			//----------------------------------------
			// Not got?
			//----------------------------------------
			
			if ( empty($statement) )
			{
				$found_end_foreach = $found_foreach + 1;
				continue;
			}
			
			//----------------------------------------
			// No closing > on logic?
			//----------------------------------------
	
			if ( $template[$_end + 2] != '>' )
			{
				$found_end_foreach = $found_foreach + 1;
				continue;
			}
	
			//----------------------------------------
			// Check recurse
			//----------------------------------------
			
			$if_found_recurse = $found_foreach;
			$__i_count        = 0;
			
			while ( 1 == 1 )
			{
				//----------------------------------------
				// Got an IF?
				//----------------------------------------
				
				$if_found_recurse = strpos( $template, $tag_foreach, $if_found_recurse + 1 );
				
				//----------------------------------------
				// None found...
				//----------------------------------------
				
				if ( $if_found_recurse === FALSE OR $if_found_recurse >= $found_end_foreach )
				{
					break;
				}
				
				$if_end_recurse      = $found_end_foreach;
				$found_end_foreach   = strpos( $template, $tag_end_foreach, $if_end_recurse + 1 );
				
				//----------------------------------------
				// None found...
				//----------------------------------------
				
				if ( $found_end_foreach === FALSE )
				{
					return str_replace("\\'", "'", $template);
				}
			}
			
			
			$rlen   = $found_end_foreach - strlen($tag_end_foreach) + 1 - $_end + 1;
			$block  = substr($template, $_end + 3, $rlen + 5);
			
			//----------------------------------------
			// Recurse
			//----------------------------------------
			
			if ( strpos( $block, $tag_foreach ) !== FALSE )
			{
				//----------------------------------------
				// Add in any extra new vars...
				//----------------------------------------
				
				$_normal_func_data = $normal_func_data;
				
				if ( strstr( strtolower($statement), 'as' ) )
				{
					# Get the last part of the argument
					list( $_trash, $keep ) = explode( ' as', $statement );
					$keep = trim($keep);
					
					if ( strstr( $keep, '=>' ) )
					{
						list( $one, $two ) = explode( '=>', $keep );
						$one = trim( $one );
						$two = trim( $two );
						
						$_normal_func_data .= ",{$one}='',{$two}=''";
					}
					else
					{
						$_normal_func_data .= ",{$keep}=''";
					}
				}
				
				$block = $this->_processForeachLogic($block, $_normal_func_data, $func_name);
			}
			
			//----------------------------------------
			// Clean up...
			//----------------------------------------
			
			$str_find    = array('\\"', '\\\\');
			$str_replace = array('"'  , '\\'  );
			
			$str_find[]    = "\\'";
			$str_replace[] = "'";
			
			$str_find[]    = '\\$delim';
			$str_replace[] =  $delim;
			
			//----------------------------------------
			// ...statement
			//----------------------------------------
			
			$statement = str_replace($str_find, $str_replace, $statement);
			$block     = str_replace($str_find, $str_replace, $block);
			
			//----------------------------------------
			// Create PHP statement
			//----------------------------------------
			$__i_count++;
			
			$function_name = '__f__'. md5( $__i_count . str_replace( '.', '_', uniqid( mt_rand(), TRUE ) ) );			
			$php_statement = '<xxforeach_'.$function_name.'xx>$this->'.$function_name.'('.$clean_func_data.')</xxforeach_'.$function_name.'xx>';
			$php_block     = $this->_processHtmlLogic( addslashes($block), $func_name );
			
			//-----------------------------------------
			// PHP tags?
			//-----------------------------------------
			
			$php_block = $this->_processPluginTags( $php_block );
			
			$php_tags  = $this->_processPhpTags( $php_block );
			
			/* Got a prefix? */
			$pre_foreach_hook  = '';
			$post_foreach_hook = '';
			if ( strstr( $statement, ':' ) )
			{
				list( $prefix, $statement ) = explode( ':', $statement );
				$prefix    = trim( $prefix );
				$statement = trim( $statement );
								
				$pre_foreach_hook  = "<!--hook.foreach.{$this->_skinGroup}.{$func_name}.{$prefix}.outer.pre-->";
				$post_foreach_hook = "<!--hook.foreach.{$this->_skinGroup}.{$func_name}.{$prefix}.outer.post-->";
				
				$php_block = "<!--hook.foreach.{$this->_skinGroup}.{$func_name}.{$prefix}.inner.pre-->" .
				             $php_block .
				             "<!--hook.foreach.{$this->_skinGroup}.{$func_name}.{$prefix}.inner.post-->";
			}
				 
			$this->foreach_blocks[] = <<<EOF
function {$function_name}($normal_func_data)
{
	\$_ips___x_retval = '{$pre_foreach_hook}';
	foreach( $statement )
	{
		{$php_tags}
		\$_ips___x_retval .= "{$php_block}\n";
	}
	\$_ips___x_retval .= '{$post_foreach_hook}';
	return \$_ips___x_retval;
}
EOF;
			$template = substr_replace( $template, $php_statement, $found_foreach, $found_end_foreach + strlen($tag_end_foreach) - $found_foreach);
			
			$found_end_foreach = $found_foreach + strlen($php_statement) - 1;
		}
	
		return str_replace("\\'", "'", $template);
	}
	
	/**
	* _processPhpTags
	*
	* Process the HTML logic 'php' tags
	*
	* @access	private
	* @param	string		Content
	* @return	string		Converted content
	*/
	private function _processPhpTags( $text )
	{
		//-----------------------------------------
		// INIT
		//-----------------------------------------
		
		$php = "";
		
		//-----------------------------------------
		// EXTRACT!
		//-----------------------------------------
		
		preg_match_all( "#<php>(.+?)</php>#si", $text, $match );
		
		for ( $i = 0; $i < count($match[0]); $i++ )
		{
			$php_code     = trim( $match[1][$i] );
			$complete_tag = $match[0][$i];
			
			$str_find    = array('\\"', '\\\\');
			$str_replace = array('"'  , '\\'  );
			
			$str_find[]    = "\\'";
			$str_replace[] = "'";
			
			$php_code = str_replace($str_find, $str_replace, $php_code);
			
			$php .= "\n".$php_code."\n";
		}
		
		return $php;
	}
	
	/**
	* _processPluginTags
	*
	* Process the template modifier tags
	* Convert {parse ...} tags
	*
	* @access	private
	* @param	string		Content
	* @return	string		Converted content
	*/
	private function _processPluginTags( $text )
	{
		$text = IPSText::replaceRecursively( $text, '{parse', '\"}', array( 'classTemplate', '_processPluginTagsCallback' ) );
		//print "\n\n========\n\n".$text."\n\n========\n\n";
		return $text;
	}
	
	/**
	* _processPluginTagsCallback
	*
	* Call back from the replaceRecursively function
	*
	* @access	public, static
	* @param	string		Matched text string
	* @param	string		Opening delimiter
	* @param	string		Closing delimiter
	* @return	string		Converted text
	*/
	static function _processPluginTagsCallback( $text, $textOpen, $textClose )
	{
		//-----------------------------------------
		// Ok, extract options
		//-----------------------------------------
		
		$raw_tag      = trim( $text ) . '"';
		$complete_tag = $textOpen . $text . $textClose;
		$options      = array();
		$return       = '';	
		
		$str_find     = array('\\"', '\\\\', "\\'");
		$str_replace  = array('"'  , '\\'  , "'"  );
		
		$raw_tag       = str_replace($str_find, $str_replace, $raw_tag);
		$_lowestKeyPos = strlen( $raw_tag );
		
		$_plugin = substr( $raw_tag, 0, strpos( $raw_tag, '=' ) );
		
		if ( ! isset( self::$template_plugin_classes[ $_plugin ] ) || ! is_object( self::$template_plugin_classes[ $_plugin ] ) )
		{
			$filepath  = self::$template_plugins_class_path . '/tp_' . $_plugin . '.php';
			$classname = 'tp_' . $_plugin;

			if ( file_exists( $filepath ) )
			{  
				require_once( $filepath );

				if ( class_exists( $classname ) )
				{
					$_class = new ReflectionClass( $classname );

					if ( $_class->implementsInterface( 'interfaceTemplatePlugins' ) )
					{
						self::$template_plugin_classes[ $_plugin ] = $_class->newInstance( ipsRegistry::instance() );
					}
					else
					{
						throw new Exception("$classname does not implement the correct interface");
					}
				}
			}
		}
		
		//-----------------------------------------
		// Not got a class? Then return
		//-----------------------------------------
		
		if ( ! is_object( self::$template_plugin_classes[ $_plugin ] ) )
		{
			return $text;
		}
		
		//-----------------------------------------
		// Get the allowed options
		//-----------------------------------------
		
		$_info          = self::$template_plugin_classes[ $_plugin ]->getPluginInfo();
		$allowedOptions = $_info['options'];
		
		//-----------------------------------------
		// Got any further parsing to do?
		//-----------------------------------------
		
		if ( ! is_array( $allowedOptions ) OR ! count( $allowedOptions ) )
		{
			# Data is what's between the first quote and the last
			$_first   = strpos( $raw_tag, '"' ) + 1;
			$mainData = substr( $raw_tag, $_first , strrpos( $raw_tag, '"' ) - $_first );
		}
		else
		{
			foreach( $allowedOptions as $key )
			{
				if ( stristr( $raw_tag, ' ' . $key . '=' ) )
				{
					# Get the position of this key
					$_keyPos = strpos( $raw_tag, ' ' . $key . '=' ) + 1;
					
					# Is this the lowest?
					if ( $_keyPos < $_lowestKeyPos )
					{
						$_lowestKeyPos = $_keyPos;
					}
					
					# Get character position of the quote (+1) of this key.
					# We use + 2 to get past the =" part of the string
					$_firstQuote     = $_keyPos + strlen( $key ) + 2;
					$_lastQuote      = strpos( $raw_tag, '"', $_firstQuote );
					$options[ $key ] = substr( $raw_tag, $_firstQuote, $_lastQuote - $_firstQuote );
				}
				
				# Now, to figure out the initial data...
				# Get the very first quote of this raw tag
				$_first = strpos( $raw_tag, '"' ) + 1;
				
				# Now, get the last quote BEFORE the lowest keyPos (ie the very first option in the raw tag)
				$_last  = strrpos( $raw_tag, '"', -( strlen( $raw_tag ) - $_lowestKeyPos ) );
				
				$mainData = substr( $raw_tag, $_first, ( $_last - $_first ) );
			}
		}
		
		/*print "\nRaw Tag = " . $raw_tag;
		print "\nPlug In = " . $_plugin;
		print "\ndata = " . $mainData;
		print "\n_last = "; print ( $_keyPos ) - strlen( $raw_tag );
		print "\nlowestKeyPos = "; print $_lowestKeyPos;
		print "\n";
		print_r( $options );*/
		
		$text = self::$template_plugin_classes[ $_plugin ]->runPlugin( $mainData, $options );
		
		return $text;
	}
	
	/**
	* _processHtmlLogic
	*
	* Process the normal HTML logic (if, else)
	*
	* @access	private
	* @param	string		Content
	* @param	string		Function name
	* 
	* @return	string		Converted content
	*/
	private function _processHtmlLogic( $text, $func_name='' )
	{
		//----------------------------------------
		// INIT
		//----------------------------------------
		
		$total_length = strlen( $text );
		$template     = $text;
		$statement    = "";
		$arg_true     = "";
		$arg_false    = "";
		
		# Tag specifics
		$tag_if       = '<if test=';
		$found_if     = -1;
		$tag_end_if   = '</if>';
		$found_end_if = -1;
		$tag_else     = '<else />';
		$found_else   = -1;
		
		$allow_delim  = array( '"', '\'' );
		
		//----------------------------------------
		// Keep the server busy for a while
		//----------------------------------------
		
		while ( 1 )
		{
			//-----------------------------------------
			// Update template length
			//-----------------------------------------
			
			$total_length = strlen( $template );
			
			$_end = 0;
			
			//----------------------------------------
			// Look for opening <if tag...
			//----------------------------------------
			
			$found_if = strpos( $template, $tag_if, $found_end_if + 1 );
			
			#$this->_addDebug( "Found <if>: $found_if" );
			
			//----------------------------------------
			// No logic found? 
			//----------------------------------------
			
			if ( $found_if === FALSE )
			{
				break;
			}
			
			//----------------------------------------
			// Beginning of the logic...
			//----------------------------------------
			
			$_start = $found_if + strlen($tag_if) + 2;
			
			$delim  = $template[ $_start - 1 ];
			
			//----------------------------------------
			// Make sure we have statement wrapped in
			// either ' or "
			//----------------------------------------
			
			if ( ! in_array( $delim, $allow_delim ) )
			{
				$found_end_if = $found_if + 1;
				continue;
			}
			
			//----------------------------------------
			// End statement?
			//----------------------------------------
			
			$found_end_if = strpos($template, $tag_end_if, $_start + 3);
			
			#$this->_addDebug( "Found </if>: $found_end_if" );
			
			//----------------------------------------
			// No end statement found
			//----------------------------------------
			
			if ( $found_end_if === FALSE )
			{ 
				return str_replace("\\'", '\'', $template);
			}
			
			//----------------------------------------
			// Find end of statement
			//----------------------------------------
			
			for ( $i = $_start; $i < $total_length; $i++ )
			{
				if ( $template[ $i ] == $delim AND $template[$i - 2] != '\\' AND $template[$i + 1] == '>' )
				{
					//----------------------------------------
					// Unescaped end delimiter
					//----------------------------------------
					
					$_end = $i - 1;
					break;
				}
			}
			
			//----------------------------------------
			// No end statement found
			//----------------------------------------
			
			if ( ! $_end )
			{
				return str_replace("\\'", '\'', $template);
			}
			
			//----------------------------------------
			// Get statement
			//----------------------------------------
			
			$statement = $this->unconvertTags( substr( $template, $_start, $_end - $_start ) );
			
			//----------------------------------------
			// Not got?
			//----------------------------------------
			
			if ( empty($statement) )
			{
				$found_end_if = $found_if + 1;
				continue;
			}
			
			//----------------------------------------
			// No closing > on logic?
			//----------------------------------------
	
			if ( $template[$_end + 2] != '>' )
			{
				$found_end_if = $found_if + 1;
				continue;
			}
	
			//----------------------------------------
			// Check recurse
			//----------------------------------------
			
			$if_found_recurse = $found_if;
			
			while ( 1 )
			{
				//----------------------------------------
				// Got an IF?
				//----------------------------------------
				
				$if_found_recurse = strpos( $template, $tag_if, $if_found_recurse + 1 );
				
				#$this->_addDebug( "Found recurse <if>: $if_found_recurse" );
				
				//----------------------------------------
				// None found...
				//----------------------------------------
				
				if ( $if_found_recurse === FALSE OR $if_found_recurse >= $found_end_if )
				{
					break;
				}
				
				$if_end_recurse = $found_end_if;
				$found_end_if   = strpos( $template, $tag_end_if, $if_end_recurse + 1 );
				
				//----------------------------------------
				// None found...
				//----------------------------------------
				
				#$this->_addDebug( "Found recurse </if>: $found_end_if" );
				
				if ( $found_end_if === FALSE )
				{
					return str_replace("\\'", "'", $template);
				}
			}
	
			$found_else = strpos($template, $tag_else, $_end + 3);
	
			//----------------------------------------
			// Handle the else tags
			//----------------------------------------
			
			while ( 1 )
			{
				//----------------------------------------
				// None found...
				//----------------------------------------
				
				if ( $found_else === FALSE OR $found_else >= $found_end_if )
				{
					$found_else = -1;
					break;
				}
	
				$tmp = substr($template, $_end + 3, $found_else - $_end + 3);
				
				//----------------------------------------
				// IF tag opened
				//----------------------------------------
				
				$opened_if = substr_count($tmp, $tag_if);
				
				//----------------------------------------
				// IF closed
				//----------------------------------------
				
				$closed_if = substr_count($tmp, $tag_end_if);
				
				if ( $opened_if == $closed_if )
				{
					break;
				}
				else
				{
					$found_else = strpos($template, $tag_else, $found_else + 1);
				}
			}
			
			//----------------------------------------
			// No else
			//----------------------------------------
			
			if ( $found_else == -1 )
			{ 
				$rlen   = $found_end_if - strlen($tag_end_if) + 1 - $_end + 1;
				$_true  = substr($template, $_end + 3, $rlen);
				$_false = '';
			}
			else
			{
				$rlen   = $found_else - $_end - 3;
				$_true  = substr($template, $_end + 3, $rlen);
	
				$rlen   = $found_end_if - strlen($tag_end_if) - $found_else - 3;
				$_false = substr($template, $found_else + strlen($tag_else), $rlen);
			}
			
			//----------------------------------------
			// Recurse
			//----------------------------------------
			
			if ( strpos( $_true, $tag_if ) !== FALSE )
			{
				$_true = trim( $this->_processHtmlLogic($_true, $func_name) );
			}
			if ( strpos( $_false, $tag_if ) !== FALSE )
			{
				$_false = trim( $this->_processHtmlLogic($_false, $func_name) );
			}
	
			//----------------------------------------
			// Clean up...
			//----------------------------------------
			
			$str_find    = array('\\"', '\\\\');
			$str_replace = array('"'  , '\\'  );
			
			if ( $delim == "'" )
			{
				$str_find[]    = "\\'";
				$str_replace[] = "'";
			}
	
			$str_find[]    = '\\$delim';
			$str_replace[] =  $delim;
			
			//----------------------------------------
			// ...statement
			//----------------------------------------
			
			$statement = str_replace($str_find, $str_replace, $statement);
			
			/* Got a prefix? */
			$hook_pre_startif  = '';
			$hook_post_startif = '';
			$hook_pre_else     = '';
			$hook_post_else    = '';
			$hook_pre_endif    = '';
			$hook_post_endif   = '';
		
			if ( strstr( $statement, ':|:' ) )
			{
				list( $prefix, $statement ) = explode( ':|:', $statement );
				$prefix    = trim( $prefix );
				$statement = trim( $statement );
								
				$hook_pre_startif   = "<!--hook.if.{$this->_skinGroup}.{$func_name}.{$prefix}.pre.startif-->";
				$hook_post_startif  = "<!--hook.if.{$this->_skinGroup}.{$func_name}.{$prefix}.post.startif-->";
				$hook_pre_else      = "<!--hook.if.{$this->_skinGroup}.{$func_name}.{$prefix}.pre.else-->";
				$hook_post_else     = "<!--hook.if.{$this->_skinGroup}.{$func_name}.{$prefix}.post.else-->";
				$hook_pre_endif     = "<!--hook.if.{$this->_skinGroup}.{$func_name}.{$prefix}.pre.endif-->";
				$hook_post_endif    = "<!--hook.if.{$this->_skinGroup}.{$func_name}.{$prefix}.post.endif-->";			
			}			
			
			//----------------------------------------
			// Create PHP statement
			//----------------------------------------
			
			/* Strip newlines */
			//$_true  = trim( $_true );
			//$_false = trim( $_false );
			
			$php_statement = "{$hook_pre_startif}\" . (($statement) ? (\"{$hook_post_startif}{$_true}{$hook_pre_else}\") : (\"{$hook_post_else}{$_false}{$hook_pre_endif}\")) . \"{$hook_post_endif}";
		
			$template = substr_replace( $template, $php_statement, $found_if, $found_end_if + strlen($tag_end_if) - $found_if);
			
			$found_end_if = $found_if + strlen($php_statement) - 1;
		}
	
		return str_replace("\\'", "'", $template);
	}
	
	
	/**
	* Convert special tags into HTML safe versions
	*
	* @access	public
	* @param	string		Content
	* @return	string		Converted content
	*/
	public function convertTags($t="")
	{
		//----------------------------------------
		// Make some tags safe..
		//----------------------------------------
		
		$t = preg_replace( "/\{ipb\.vars\[(['\"])?(sql_driver|sql_host|sql_database|sql_pass|sql_user|sql_port|sql_tbl_prefix|smtp_host|smtp_port|smtp_user|smtp_pass|html_dir|base_dir|upload_dir)(['\"])?\]\}/", "" , $t );
				
		return $t;
	}
	
	/**
	* UN-Convert special tags from the HTML safe versions
	*
	* @access	public
	* @param	string		Content
	* @return	string		Converted content
	*/
	public function unconvertTags($t="")
	{
		//----------------------------------------
		// Make some tags safe..
		//----------------------------------------
		
		$t = preg_replace( "/\{ips\.vars\[(['\"])?(sql_driver|sql_host|sql_database|sql_pass|sql_user|sql_port|sql_tbl_prefix|smtp_host|smtp_port|smtp_user|smtp_pass|html_dir|base_dir|upload_dir)(['\"])?\]\}/", "" , $t );
		
		# IPB 2.1+ Kernel
		$t = preg_replace( "/{ip(s|b|d)\.script_url}/i"           , ipsRegistry::$settings['base_url']  , $t);
		$t = preg_replace( "/{ip(s|b|d)\.session_id}/i"           , '{$this->member->session_id }', $t);
		//$t = preg_replace( "#ip(?:s|b|d)\.(member|vars|skin|lang|input)#i", '$this->ipsclass->\\1'         , $t );
		
		return $t;
	}
	
	/**
	* Convert PHP code to html logic
	*
	* @access	public
	* @param	string		Raw PHP content
	* @return	string		Converted HTML Logic content
	*/
	public function convertPhpToHtml($php)
	{
		$php = $this->_reverseIpsHtml( $this->convertTags( $php ) );
		
		return $php;
	}
	
	/**
	* Convert IPB Heredoc PHP code to html logic
	*
	* @access	private
	* @param	string		Raw PHP content
	* @return	string		Converted HTML Logic content
	*/
	private function _reverseIpsHtml( $code )
	{
		$code = $this->_trimSlashes($code);
		
		$code = preg_replace("/".'\$'."IPBHTML\s+?\.?=\s+?<<<EOF(.+?)EOF;\s?/si", "\\1", $code );
		
		$code = trim($code);
		$code = $this->_trimNewlines($code);
		
		return $code;
	}
	
	/**
	* Trim newlines
	*
	* @access	private
	* @param	string		Raw code
	* @return	string		Code with newlines trimmed
	*/
	private function _trimNewlines($code)
	{
		$code = preg_replace("/^\n{1,}/s", "", $code );
		$code = preg_replace("/\n{1,}$/s", "", $code );
		return $code;
	}
	
	/**
	* Trim empty if tag newlines
	*
	* @access	private
	* @param	string		Raw code
	* @return	string		Code with newlines trimmed
	*/
	private function _trimEmptyIfs($code)
	{
		return preg_replace("/\"\n\s+?\"/s", "\"\"", $code );
	}
	
	/**
	* Trim slashes
	*
	* @access	private
	* @param	string		Raw code
	* @return	string		Code with slashes trimmed
	*/
	private function _trimSlashes($code)
	{
		$code = str_replace( '\"' , '"', $code );
		$code = str_replace( "\\'", "'", $code );
		return $code;
	}
	
	/**
	* Add a debug message if debugging enabled
	*
	* @access	private
	* @param	string		Message
	* @return	mixed		boolean on success, void when not executed
	*/
	private function _addDebug( $msg )
	{
		if ( IPS_TEMPLATE_DEBUG AND IPS_TEMPLATE_DEBUG_FILE )
		{
			$full_msg = "==================================================================\n"
					   . "Date: " . gmdate( 'r' ) . ' - ' . $_SERVER['REMOTE_ADDR'] . "\n"
					   . $msg . "\n"
					   . "==================================================================\n";
			
			if ( $FH = @fopen( IPS_TEMPLATE_DEBUG_FILE, 'a+' ) )
			{
				fwrite( $FH, $full_msg, strlen( $full_msg ) );
				fclose( $FH );
			}
			
			return TRUE;
		}
	}
}
