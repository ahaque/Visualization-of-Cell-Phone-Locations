<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Compare and highlight differences between two strings or files
 * Last Updated: $Date: 2009-03-24 17:32:58 -0400 (Tue, 24 Mar 2009) $
 *
 * @author 		$Author: josh $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Services Kernel
 * @since		Thursday 10th February 2005 (10:47)
 * @version		$Revision: 249 $
 */

if ( ! defined( 'IPS_CLASSES_PATH' ) )
{
	/**
	* Define classes path
	*/
	define( 'IPS_CLASSES_PATH', dirname(__FILE__) );
}

class classDifference
{
	# Globals
	
	/**
	* Shell command
	*
	* @access	public
	* @var 		string
	*/
	public $diff_command = 'diff';
	
	/**
	* Type of diff to use
	*
	* @access	public
	* @var 		string	[EXEC, CGI, PHP]
	*/
	public $method       = 'EXEC';
	
	/**
	* Differences found?
	*
	* @access	public
	* @var 		integer
	*/
	public $diff_found   = 0;
	
	/**
	* Post process DIFF result?
	*
	* @access	public
	* @var 		integer
	*/
	public $post_process = 1;
	
	/**
	* Constructor
	*
	* @access	public
	* @return	void
	*/
	public function __construct()
	{
		//-------------------------------
		// Server?
		//-------------------------------
		
		if( strpos( strtolower( PHP_OS ), 'win' ) === 0 OR ( ! function_exists('exec') ) )
		{
			$this->method = 'CGI';
		}
	}
	
	/**
	* Wrapper function to get differences
	*
	* @access	public
	* @param	string		Original string
	* @param	string		New string
	* @return	string		Diff data
	*/
	public function getDifferences( $str1, $str2 )
	{
		$this->diff_found = 0;
		
		$str1       = $this->_diffTagSpace($str1);
		$str2       = $this->_diffTagSpace($str2);
		$str1_lines = $this->_diffExplodeStringIntoWords($str1);
		$str2_lines = $this->_diffExplodeStringIntoWords($str2);
		
		if ( $this->method == 'CGI' )
		{
			$diff_res   = $this->_getCgiDiff( implode( chr(10), $str1_lines ) . chr(10), implode( chr(10), $str2_lines ) . chr(10) );
		}
		else if ( $this->method == 'PHP' )
		{
			$diff_res   = $this->_getPhpDiff( implode( chr(10), $str1_lines ) . chr(10), implode( chr(10), $str2_lines ) . chr(10) );
		}
		else
		{
			$diff_res   = $this->_getExecDiff( implode( chr(10), $str1_lines ) . chr(10), implode( chr(10), $str2_lines ) . chr(10) );
		}
		
		//-------------------------------
		// Post process?
		//-------------------------------
		
		if ( $this->post_process )
		{
			if ( is_array($diff_res) )
			{
				reset($diff_res);
				$c              = 0;
				$diff_res_array = array();
				
				foreach( $diff_res as $l_val )
				{
					if ( intval($l_val) )
					{
						$c = intval($l_val);
						$diff_res_array[$c]['changeInfo'] = $l_val;
					}
					
					if (substr($l_val,0,1) == '<')
					{
						$diff_res_array[$c]['old'][] = substr($l_val,2);
					}
					
					if (substr($l_val,0,1) == '>')
					{
						$diff_res_array[$c]['new'][] = substr($l_val,2);
					}
				}
	
				$out_str    = '';
				$clr_buffer = '';
				
				for ( $a = -1; $a < count($str1_lines); $a++ )
				{
					if ( is_array( $diff_res_array[$a+1] ) )
					{
						if ( strstr( $diff_res_array[$a+1]['changeInfo'], 'a') )
						{
							$this->diff_found = 1;
							$clr_buffer .= htmlspecialchars($str1_lines[$a]).' ';
						}
	
						$out_str     .= $clr_buffer;
						$clr_buffer   = '';
						
						if (is_array($diff_res_array[$a+1]['old']))
						{
							$this->diff_found = 1;
							$out_str.='<del style="-ips-match:1">'.htmlspecialchars(implode(' ',$diff_res_array[$a+1]['old'])).'</del> ';
						}
						
						if (is_array($diff_res_array[$a+1]['new']))
						{
							$this->diff_found = 1;
							$out_str.='<ins style="-ips-match:1">'.htmlspecialchars(implode(' ',$diff_res_array[$a+1]['new'])).'</ins> ';
						}
						
						$cip = explode(',',$diff_res_array[$a+1]['changeInfo']);
						
						if ( ! strcmp( $cip[0], $a + 1 ) )
						{
							$new_line = intval($cip[1])-1;
							
							if ( $new_line > $a )
							{
								$a = $new_line;
							}
						}
					} 
					else
					{
						$clr_buffer .= htmlspecialchars($str1_lines[$a]).' ';
					}
				}
				
				$out_str .= $clr_buffer;
	
				$out_str  = str_replace('  ',chr(10),$out_str);
				
				$out_str  = $this->_diffTagSpace($out_str,1);
				
				return $out_str;
			}
		}
		else
		{
			return $diff_res;
		}
	}

	/**
	* Adds space character after HTML tags
	*
	* @access	private
	* @param	string		String
	* @param	integer		[Optional][0=reverse, 1=normal]
	* @return	string		Converted string
	*/
	private function _diffTagSpace( $str, $rev=0 )
	{
		if ( $rev )
		{
			return str_replace(' &lt;','&lt;',str_replace('&gt; ','&gt;',$str) );
		}
		else
		{
			return str_replace('<',' <',str_replace('>','> ',$str) );
		}
	}
	
	/**
	* Explodes input string into words
	*
	* @access	private
	* @param	string		Input string
	* @return	array 		Array of words in string
	*/
	private function _diffExplodeStringIntoWords( $str )
	{ 
		$str_array = $this->_explodeTrim( chr(10), $str );
		$out_array = array();

		reset($str_array);
		
		foreach( $str_array as $low )
		{
			$all_words   = $this->_explodeTrim( ' ', $low, 1 );
			$out_array   = array_merge($out_array, $all_words);
			$out_array[] = '';
			$out_array[] = '';
		}
		
		return $out_array;
	}
	
	/**
	* Explode into array and trim
	*
	* @access	private
	* @param	string 		Delimiter
	* @param	string		String to check
	* @param	integer		[Optional] Remove blank lines
	* @return	array 		Array of lines in string
	*/
	private function _explodeTrim( $delim, $str, $remove_blank=0 )
	{
		$tmp   = explode( $delim, trim($str) );
		$final = array();
	
		foreach( $tmp as $i )
		{
			if ( $remove_blank AND ( $i === '' OR $i === NULL ) ) //!$i AND $i !== 0 )
			{
				continue;
			}
			else
			{
				$final[] = trim($i);
			}
		}

		return $final;
	}
	
	/**
	* Produce differences using PHP
	*
	* @access	private
	* @param	string		comapre string 1
	* @param	string		comapre string 2
	* @return	string
	*/
    private function _getPhpDiff( $str1 , $str2 )
    {
    	$str1 = explode( "\n", str_replace( "\r\n", "\n", $str1 ) );
    	$str2 = explode( "\n", str_replace( "\r\n", "\n", $str2 ) );
    	
		/* Set include path.. */
		@set_include_path( IPS_KERNEL_PATH . 'PEAR/' );
		
		/* OMG.. too many PHP 5 errors under strict standards */
		$oldReportLevel = error_reporting( 0 );
		error_reporting( $oldReportLevel ^ E_STRICT );
		
    	require_once 'Text/Diff.php';
		require_once 'Text/Diff/Renderer.php';
		require_once 'Text/Diff/Renderer/inline.php';
		
		$diff = new Text_Diff( 'auto', array( $str1, $str2 ) );
		
		$renderer = new Text_Diff_Renderer_inline();
		$result = $renderer->render($diff);
		
		/* Go back to old reporting level */
		error_reporting( $oldReportLevel | E_STRICT );
		
		$result = str_replace( "<ins>", '<ins style="-ips-match:1">', $result );
		$result = str_replace( "<del>", '<del style="-ips-match:1">', $result );
		
		# Got a match?
		if ( strstr( $result, 'style="-ips-match:1"' ) )
		{
			$this->diff_found = 1;
		}
		
		# No post processing please
		$this->post_process = 0;
		
		# Convert lines to a space, and two spaces to a single line
		$result = str_replace('  ', chr(10), str_replace( "\n", " ", $result ) );
		$result = $this->_diffTagSpace($result,1);
		
		return $result;
    }

	/**
	* Produce differences using unix diff
	*
	* @access	private
	* @param	string		comapre string 1
	* @param	string		comapre string 2
	* @return	string
	*/
	private function _getExecDiff( $str1, $str2 )
	{
		//-------------------------------
		// Write the tmp files
		//-------------------------------
		
		$file1 = IPS_ROOT_PATH . 'uploads/'.time().'-1';
		$file2 = IPS_ROOT_PATH . 'uploads/'.time().'-2';
		
		if ( $FH1 = @fopen( $file1, 'w' ) )
		{
			@fwrite( $FH1, $str1, strlen($str1) );
			@fclose( $FH1 );
		}
		
		if ( $FH2 = @fopen( $file2, 'w' ) )
		{
			@fwrite( $FH2, $str2, strlen($str2) );
			@fclose( $FH2 );
		}
		
		//-------------------------------
		// Check
		//-------------------------------
		
		if ( file_exists( $file1 ) and file_exists( $file2 ) )
		{
			exec( $this->diff_command.' '.$file1.' '.$file2, $result );
			
			@unlink( $file1 );
			@unlink( $file2 );
			
			return $result;
		}
		else
		{
			return "Error, files not written to disk";
		}
	}
	
	/**
	* Produce differences using CGI
	*
	* @access	private
	* @param	string		comapre string 1
	* @param	string		comapre string 2
	* @return	string
	*/
	private function _getCgiDiff( $str1, $str2 )
	{
		//-----------------------------------------
		// Load file management class
		//-----------------------------------------
		
		require_once( IPS_KERNEL_PATH . '/classFileManagement.php' );
		$this->classFileManagement = new classFileManagement();
		$this->classFileManagement->use_sockets = ipsRegistry::$settings['enable_sockets'];
		
		//-------------------------------
		// Write the tmp files
		//-------------------------------
		
		$file1 = 'tmp-1';
		$file2 = 'tmp-2';
		
		if ( $FH1 = @fopen( IPS_ROOT_PATH.'uploads/'.$file1, 'w' ) )
		{
			@fwrite( $FH1, $str1, strlen($str1) );
			@fclose( $FH1 );
		}
		
		if ( $FH2 = @fopen( IPS_ROOT_PATH.'uploads/'.$file2, 'w' ) )
		{
			@fwrite( $FH2, $str2, strlen($str2) );
			@fclose( $FH2 );
		}
		
		//-------------------------------
		// Check
		//-------------------------------
		
		if ( file_exists( IPS_ROOT_PATH . 'uploads/' . $file1 ) AND file_exists( IPS_ROOT_PATH . 'uploads/' . $file2 ) )
		{
			$result = $this->classFileManagement->getFileContents( ipsRegistry::$settings['board_url'] . "/" . CP_DIRECTORY . "/" . IPS_CGI_DIRECTORY . "/cgi_getdifference.cgi" );
			
			@unlink( IPS_ROOT_PATH . 'uploads/' . $file1 );
			@unlink( IPS_ROOT_PATH . 'uploads/' . $file2 );
			
			return explode( "\n", $result );
		}
		else
		{
			return "Error, files not written to disk";
		}
	}
	
}
?>