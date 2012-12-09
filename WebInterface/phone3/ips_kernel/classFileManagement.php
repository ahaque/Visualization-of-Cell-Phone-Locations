<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Wrapper for retrieving file contents.  Methods available for removing files and directories as well.
 * Last Updated: $Date: 2009-07-23 05:48:16 -0400 (Thu, 23 Jul 2009) $
 *
 * @author 		$Author: matt $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Services Kernel
 * @since		Tuesday 22nd February 2005 (16:55)
 * @version		$Revision: 300 $
 */
 
if ( ! defined( 'IPS_CLASSES_PATH' ) )
{
	/**
	 * Define classes path
	 */
	define( 'IPS_CLASSES_PATH', dirname(__FILE__) );
}

class classFileManagement
{
	/**
	 * Use sockets flag
	 *
	 * @access	public
	 * @var 		integer
	 */
	public $use_sockets = 0;
	
	/**
	 * Error array
	 *
	 * @access	public
	 * @var		array
	 */
	public $errors	 = array();
	
	/**
	 * HTTP Status Code/Text
	 *
	 * @access	public
	 * @var		integer		HTTP Status code
	 * @var		string		HTTP Status text
	 */	
	public $http_status_code	= 0;
	public $http_status_text	= "";
	
	/**#@+
	 * Set Authentication
	 *
	 * @access	public
	 * @var 		string 
	 * @var 		string 
	 * @var 		string 
	 */
	public $auth_req		= 0;
	public $auth_user;
	public $auth_pass;
	/**#@-*/
	
	/**
	 * Timeout setting
	 *
	 * @access	public
	 * @var		int
	 */
	public $timeout			= 15;
	
	/**
	 * Get file contents (accepts URL or path)
	 *
	 * @access	public
	 * @param	string		URI / File path
	 * @param	string		HTTP User
	 * @param	string		HTTP Pass
	 * @return	string		File data
	 */
	public function getFileContents( $file_location, $http_user='', $http_pass='' )
	{
		//-------------------------------
		// INIT
		//-------------------------------
		
		$contents	 = "";
		$file_location = str_replace( '&amp;', '&', $file_location );
		
		//-----------------------------------------
		// Inline user/pass?
		//-----------------------------------------
		
		if ( $http_user and $http_pass )
		{
			$this->auth_req  = 1;
			$this->auth_user = $http_user;
			$this->auth_pass = $http_pass;
		}
		
		//-------------------------------
		// Hello
		//-------------------------------
		
		if ( ! $file_location )
		{
			return FALSE;
		}
		
		if ( ! stristr( $file_location, 'http://' ) AND ! stristr( $file_location, 'https://' ) )
		{
			//-------------------------------
			// It's a path!
			//-------------------------------
			
			if ( ! file_exists( $file_location ) )
			{
				$this->errors[] = "File '{$file_location}' does not exist, please check the path.";
				return;
			}
			
			$contents = $this->_getContentsWithFopen( $file_location );
		}
		else
		{
			//-------------------------------
			// Is URL, try curl and then fall back
			//-------------------------------
			
			if( ($contents = $this->_getContentsWithCurl( $file_location )) === false )
			{
				if ( $this->use_sockets )
				{
					$contents = $this->_getContentsWithSocket( $file_location );
				}
				else
				{
					$contents = $this->_getContentsWithFopen( $file_location );
				}
			}
		}
		
		return $contents;
	}
	
	/**
	 * Get file contents (with PHP's fopen)
	 *
	 * @access	private
	 * @param	string		File location
	 * @return	string		File data
	 */
	private function _getContentsWithFopen( $file_location )
	{
		//-------------------------------
		// INIT
		//-------------------------------
		
		$buffer = "";
		
		@clearstatcache();
			
		if ( $FILE = fopen( $file_location, "r" ) )
		{
			@stream_set_timeout( $FILE, $this->timeout );
			$status = @stream_get_meta_data($FILE);
			
			while ( ! feof( $FILE ) && ! $status['timed_out'] )
			{
			   $buffer .= fgets( $FILE, 4096 );
			   
			   $status = stream_get_meta_data($FILE);
			}

			fclose($FILE);
		}
		
		return $buffer;
	}
	
	/**
	 * Get file contents (with sockets)
	 *
	 * @access	private
	 * @param	string		File location
	 * @return	string		File data
	 * @todo 	[Future] Support chunked HTTP content
	 */
	private function _getContentsWithSocket( $file_location )
	{
		//-------------------------------
		// INIT
		//-------------------------------
		
		$data				= null;
		$fsocket_timeout	= $this->timeout;
		
		//-------------------------------
		// Parse URL
		//-------------------------------
		
		$url_parts = @parse_url($file_location);
		
		if ( ! $url_parts['host'] )
		{
			$this->errors[] = "No host found in the URL '{$file_location}'!";
			return FALSE;
		}
		
		//-------------------------------
		// Finalize
		//-------------------------------
		
		$host = $url_parts['host'];
	 	$port = ( isset($url_parts['port']) ) ? $url_parts['port'] : ( $url_parts['scheme'] == 'https' ? 443 : 80 );
	 	
	 	//-------------------------------
	 	// Tidy up path
	 	//-------------------------------
	 	
	 	if ( !empty( $url_parts["path"] ) )
		{
			$path = $url_parts["path"];
		}
		else
		{
			$path = "/";
		}
 
		if ( !empty( $url_parts["query"] ) )
		{
			$path .= "?" . $url_parts["query"];
		}
	 	
	 	//-------------------------------
	 	// Open connection
	 	//-------------------------------
	 	
	 	if ( ! $fp = @fsockopen( $host, $port, $errno, $errstr, $fsocket_timeout ) )
	 	{
			$this->errors[] = "Could not establish a connection with {$host}";
			return FALSE;
		
		}
		else
		{
			$final_carriage = "";
			
			if ( ! $this->auth_req )
			{
				$final_carriage = "\r\n";
			}
			
			if ( ! fputs( $fp, "GET $path HTTP/1.0\r\nHost:$host\r\nConnection: Keep-Alive\r\n{$final_carriage}" ) )
			{
				$this->errors[] = "Unable to send request to $host!";
				return FALSE;
			}
			
			if ( $this->auth_req )
			{
				if ( $this->auth_user && $this->auth_pass )
				{
					$header = "Authorization: Basic ".base64_encode("{$this->auth_user}:{$this->auth_pass}")."\r\n\r\n";
					
					if ( ! fputs( $fp, $header ) )
					{
						$this->errors[] = "Authorization Failed!";
						return FALSE;
					}
				}
			}
		}

		@stream_set_timeout($fp, $fsocket_timeout);
		
		$status = @stream_get_meta_data($fp);
		
		while( ! feof($fp) && ! $status['timed_out'] )		
		{
		  $data .= fgets ($fp,8192);
		  $status = stream_get_meta_data($fp);
		}
		
		fclose ($fp);
		
		//-------------------------------
		// Strip headers
		//-------------------------------
		
		// HTTP/1.1 ### ABCD
		$this->http_status_code = substr( $data, 9, 3 );
		$this->http_status_text = substr( $data, 13, ( strpos( $data, "\r\n" ) - 13 ) );

		$tmp = split("\r\n\r\n", $data, 2);
		$data = $tmp[1];

 		return $data;
	}
	
	/**
	 * Get file contents (with cURL)
	 *
	 * @access	private
	 * @param	string		File location
	 * @return	string		File data
	 */
	private function _getContentsWithCurl( $file_location )
	{
		if ( function_exists( 'curl_init' ) AND function_exists("curl_exec") )
		{
			$ch = curl_init( $file_location );
			
			curl_setopt( $ch, CURLOPT_HEADER		, 0);
			curl_setopt( $ch, CURLOPT_TIMEOUT		, $this->timeout );
			curl_setopt( $ch, CURLOPT_POST			, 0 );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 ); 
			curl_setopt( $ch, CURLOPT_FAILONERROR	, 1 ); 
			curl_setopt( $ch, CURLOPT_MAXREDIRS		, 10 );
			
			/**
			 * Cannot set this when safe_mode or open_basedir is enabled
			 * @link http://forums./index.php?autocom=tracker&showissue=11334
			 */
			if( !ini_get('open_basedir') AND !ini_get('safe_mode') )
			{
				curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 ); 
			}
			
			if( $this->auth_req )
			{
				curl_setopt( $ch, CURLOPT_USERPWD	, "{$this->auth_user}:{$this->auth_pass}" );
			}
			
			$data = curl_exec($ch);
			curl_close($ch);
			
			return $data;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Copies contents of one directory to another, creating if necessary
	 *
	 * @access	public
	 * @param	string		File location [from]
	 * @param	string		File location [destination]
	 * @param	string		[Optional] CHMOD mode to set
	 * @return	boolean		Copy successful
	 */
	public function copyDirectory($from_path, $to_path, $mode = 0777)
	{
		$this->errors = array();
		
		//-----------------------------------------
		// Strip off trailing slashes...
		//-----------------------------------------
		
		$from_path = rtrim( $from_path, '/' );
		$to_path   = rtrim( $to_path, '/' );
	
		if ( ! is_dir( $from_path ) )
		{
			$this->errors[] = "Could not locate directory '{$from_path}'";
			return false;
		}
	
		if ( ! is_dir( $to_path ) )
		{
			if ( ! @mkdir( $to_path, $mode ) )
			{
				$this->errors[] = "Could not create directory '{$to_path}' please check the CHMOD permissions and re-try";
				return FALSE;
			}
			else
			{
				@chmod( $to_path, $mode );
			}
		}
		
		if ( is_dir( $from_path ) )
		{
			$handle = opendir($from_path);
			
			while ( ($file = readdir($handle)) !== false )
			{
				if ( ($file != ".") && ($file != "..") )
				{
					if ( is_dir( $from_path."/".$file ) )
					{
						$this->copyDirectory( $from_path."/".$file, $to_path."/".$file );
					}
					
					if ( is_file( $from_path."/".$file ) )
					{
						copy( $from_path."/".$file, $to_path."/".$file );
						@chmod( $to_path."/".$file, 0777 );
					} 
				}
			}

			closedir($handle); 
		}
		
		if ( ! count( $this->errors ) )
		{
			return true;
		}
	}
	
	/**
	 * Removes a directory
	 *
	 * @access	public
	 * @param	string		File location [from]
	 * @return	boolean		Removal successful
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
			
			@chmod( $file, 0777 );
			
			if ( is_dir( $file ) )
			{
				$handle = opendir( $file );
				
				while ( ($filename = readdir($handle)) !== false )
				{
					if ( ($filename != ".") && ($filename != "..") )
					{
						$this->removeDirectory( $file."/".$filename );
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
		
		if( $errors == 0 )
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Fetches the files within a directory
	 *
	 * @access	public
	 * @param	string		Directory location
	 * @param	array		List of files gathered recursively
	 * @return	array		Array of files
	 */
	public function fetchDirectoryContents( $path, $files=array() )
	{
		//-----------------------------------------
		// Remove trailing slashes..
		//-----------------------------------------
		
		$file = rtrim( $file, '/' );
		
		if ( file_exists($file) )
		{
			//-----------------------------------------
			// Attempt CHMOD
			//-----------------------------------------
			
			if ( is_dir( $file ) )
			{
				$handle = opendir( $file );
				
				while ( ($filename = readdir($handle)) !== false )
				{
					if ( ($filename != ".") && ($filename != "..") )
					{
						$this->fetchDirectoryContents( $file."/".$filename, $files );
					}
				}
				
				closedir($handle);
			}
			else
			{
				$files[] = $file;
			}
		}
		
		return $files;
	}
	
	/**
	 * Empties a directory
	 *
	 * @access	public
	 * @param	string		File location [from]
	 * @param	int			In a loop (not root folder)
	 * @return	boolean		Empty successful
	 */
	public function emptyDirectory($file, $inLoop=0)
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
			
			@chmod( $file, 0777 );
			
			if ( is_dir( $file ) )
			{
				$handle = opendir( $file );
				
				while ( ($filename = readdir($handle)) !== false )
				{
					if ( ($filename != ".") && ($filename != "..") )
					{
						$this->emptyDirectory( $file."/".$filename, 1 );
					}
				}
				
				closedir($handle);
				
				if ( $inLoop )
				{
					if ( ! @rmdir($file) )
					{
						$errors++;
					}
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
		
		if( $errors == 0 )
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	
}