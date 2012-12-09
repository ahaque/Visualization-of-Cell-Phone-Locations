<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Image Handler: ImageMagick Library
 * Last Updated: $Date: 2009-07-29 21:58:46 -0400 (Wed, 29 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Services Kernel
 * @link		http://www.
 * @since		Monday 5th May 2008 14:00
 * @version		$Revision: 303 $
 *
 * ImageMagick Example
 * <code>
 * $image = new classImageImagemagick();
 * $image->init( array(
 * 					'image_path'	=> "/path/to/images/",
 * 					'image_file'	=> "image_filename.jpg",
 *					'im_path'		=> '/path/to/imagemagick/folder/',
 *					'temp_path'		=> '/tmp/',
 * 			)		);
 * 
 * if( $image->error )
 * {
 * 	print $image->error;exit;
 * }
 * 
 * Set max width and height
 * $image->resizeImage( 600, 480 );
 * // Add a watermark
 * $image->addWatermark( "/path/to/watermark/trans.png" );
 * //$image->addCopyrightText( "Hello World!", array( 'color' => '#ffffff', 'font' => 3 ) );
 * $image->displayImage();
 * </code>
 */

class classImageImagemagick extends classImage implements interfaceImage
{
	/**
	 * ImageMagick Path
	 *
	 * @access	private
	 * @var		string		Path to imagemagick binary (folder, no trailing slash)
	 */
	private $im_path		= null;
	
	/**
	 * Temp directory (must be writable)
	 *
	 * @access	private
	 * @var		string		Path to a temp directory
	 */
	private $temp_path		= '';
	
	/**
	 * Temp file (directory, name, .temp)
	 *
	 * @access	private
	 * @var		string		Full path to the temp file
	 */
	private $temp_file		= '';
	
	/**
	 * Image quality settings
	 *
	 * @access	public
	 * @var		array 		Image quality settings
	 */
	public $quality			= array( 'png' => 8, 'jpg' => 75 );
	
    /**
	 * Initiate image handler, perform any necessary setup
	 *
	 * @access	public
	 * @param	array 		Necessary options to init module [image_path, image_file, im_path, temp_path]
	 * @return	boolean		Initiation successful
	 */
	public function init( $opts=array() )
	{
	 	//---------------------------------------------------------
	 	// Verify params
	 	//---------------------------------------------------------
	 	
		if( !isset($opts['image_path']) OR !$opts['image_path'] )
		{
			$this->error		= 'no_image_path';
			return false;
		}
		
		if( !isset($opts['image_file']) OR !$opts['image_file'] )
		{
			$this->error		= 'no_image_file';
			return false;
		}
		
		if( !isset($opts['im_path']) OR !$opts['im_path'] )
		{
			$this->error		= 'no_imagemagick_path';
			return false;
		}
		
	 	//---------------------------------------------------------
	 	// Store params
	 	//---------------------------------------------------------
	 	
		$this->image_path		= $this->_cleanPaths( $opts['image_path'] );
		$this->image_file		= basename( $opts['image_file'] );
		$this->image_full		= $this->image_path . '/' . $this->image_file;
		$this->im_path			= $this->_cleanPaths( $opts['im_path'] );
		$this->temp_path		= $this->_cleanPaths( $opts['temp_path'] );
		$this->temp_file		= $this->temp_path . '/' . $this->image_file . '.temp';
		
	 	//---------------------------------------------------------
	 	// Check paths and files
	 	//---------------------------------------------------------
	 	
		if( !is_dir( $this->im_path ) )
		{
			$this->error		= 'bad_imagemagick_path';
			return false;
		}
		
		if( !is_dir( $this->temp_path ) )
		{
			$this->error		= 'bad_temp_path';
			return false;
		}
		
		if( !is_writable( $this->temp_path ) )
		{
			$this->error		= 'temp_path_not_writable';
			return false;
		}
		
		if( file_exists( $this->temp_file ) )
		{
			@unlink( $this->temp_file );
		}
		
	 	//---------------------------------------------------------
	 	// Get image extension
	 	//---------------------------------------------------------
	 			
		$this->image_extension	= strtolower( pathinfo( $this->image_file, PATHINFO_EXTENSION ) );
		
	 	//---------------------------------------------------------
	 	// Verify this is a valid image type
	 	//---------------------------------------------------------
	 	
		if( !in_array( $this->image_extension, $this->image_types ) )
		{
			$this->error		= 'image_not_supported';
			return false;
		}
		
		//---------------------------------------------------------
		// Quality values
		//---------------------------------------------------------
		
		if( isset($opts['jpg_quality']) AND $opts['jpg_quality'] )
		{
			$this->quality['jpg']	= $opts['jpg_quality'];
		}
		
		if( isset($opts['png_quality']) AND $opts['png_quality'] )
		{
			$this->quality['png']	= $opts['png_quality'];
		}
		
	 	//---------------------------------------------------------
	 	// Get and store dimensions
	 	//---------------------------------------------------------
	 	
		$dimensions = getimagesize( $this->image_full );
		
		$this->orig_dimensions	= array( 'width' => $dimensions[0], 'height' => $dimensions[1] );
		$this->cur_dimensions	= $this->orig_dimensions;
		
		return true;
	}
	
    /**
	 * Resize image proportionately
	 *
	 * @access	public
	 * @param	integer 	Maximum width
	 * @param	integer 	Maximum height
	 * @return	array		Dimensons of the original image and the resized dimensions
	 */
	public function resizeImage( $width, $height )
	{
	 	//---------------------------------------------------------
	 	// Grab proportionate dimensions and remember
	 	//---------------------------------------------------------
	 	
	 	$new_dims = $this->_getResizeDimensions( $width, $height );

		if( !is_array($new_dims) OR !count($new_dims) OR !$new_dims['img_width'] )
		{
			if( !$this->force_resize )
			{
				return array();
			}
			else
			{
				$new_dims['width']	= $width;
				$new_dims['height']	= $height;
			}
		}

		$this->cur_dimensions = array( 'width' => $new_dims['img_width'], 'height' => $new_dims['img_height'] );

		//---------------------------------------------------------
		// Need image type for quality setting
		//---------------------------------------------------------
		
		$type		= strtolower( pathinfo( basename($this->image_full), PATHINFO_EXTENSION ) );
		$quality	= '';
		
		if( $type == 'jpg' OR $type == 'jpeg' )
		{
			$quality	= " -quality {$this->quality['jpg']}";
		}
		else if( $type == 'png' )
		{
			$quality	= " -quality {$this->quality['png']}";
		}
		
	 	//---------------------------------------------------------
	 	// Resize image to temp file
	 	//---------------------------------------------------------
	 	
	 	system("{$this->im_path}/convert{$quality} -geometry {$new_dims['img_width']}x{$new_dims['img_height']} {$this->image_full} {$this->temp_file}" );

	 	//---------------------------------------------------------
	 	// Successful?
	 	//---------------------------------------------------------
	 	
	 	if( file_exists( $this->temp_file ) )
	 	{
		 	return array( 'originalWidth'  => $this->orig_dimensions['width'],
						  'originalHeight' => $this->orig_dimensions['height'],
						  'newWidth'       => $new_dims['img_width'],
						  'newHeight'      => $new_dims['img_height'] );
	 	}
	 	else
	 	{
		 	return array();			
	 	}
	}
	
    /**
	 * Write image to file
	 *
	 * @access	public
	 * @param	string 		File location (including file name)
	 * @return	boolean		File write successful
	 */
	public function writeImage( $path )
	{
	 	//---------------------------------------------------------
	 	// Remove image if it exists
	 	//---------------------------------------------------------
	 	
		if( file_exists( $path ) )
		{
			@unlink( $path );
		}
	
	 	//---------------------------------------------------------
	 	// Temp file doesn't exist
	 	//---------------------------------------------------------
	 	
		if( !file_exists( $this->temp_file ) )
		{
	 		$this->error		= 'temp_image_not_exists';
		 	return false;
		}
		
	 	//---------------------------------------------------------
	 	// Rename temp file to final destination
	 	//---------------------------------------------------------
	 	
		rename( $this->temp_file, $path );
		
		if( !file_exists( $path ) )
		{
	 		$this->error		= 'unable_to_write_image';
		 	return false;
	 	}
		
	 	//---------------------------------------------------------
	 	// Chmod 777 and return
	 	//---------------------------------------------------------
	 		 	
	 	@chmod( $path, 0777 );
	 	
	 	return true;
	}
	
    /**
	 * Print image to screen
	 *
	 * @access	public
	 * @return	void		Image printed and script exits
	 */
	public function displayImage()
	{
	 	//---------------------------------------------------------
	 	// Print appropriate header
	 	//---------------------------------------------------------
	 	
		switch( $this->image_extension )
		{
			case 'gif':
				@header('Content-type: image/gif');
			break;
			
			case 'jpeg':
			case 'jpg':
			case 'jpe':
				@header('Content-Type: image/jpeg' );
			break;
			
			case 'png':
				@header('Content-Type: image/png' );
			break;
		}
		
	 	//---------------------------------------------------------
	 	// Print file contents and exit
	 	//---------------------------------------------------------
	 	
		print file_get_contents( $this->temp_file );
		
		exit;
	}

	
    /**
	 * Add watermark to image
	 *
	 * @access	public
	 * @param	string 		Watermark image path
	 * @param	integer		[Optional] Opacity 0-100
	 * @return	boolean		Watermark addition successful
	 */
	public function addWatermark( $path, $opacity=100 )
	{
	 	//---------------------------------------------------------
	 	// Verify input
	 	//---------------------------------------------------------
	 	
		if( !$path )
		{
			$this->error		= 'no_watermark_path';
			return false;
		}
		
		$type		= strtolower( pathinfo( basename($path), PATHINFO_EXTENSION ) );
		$opacity	= $opacity > 100 ? 100 : ( $opacity < 0 ? 1 : $opacity );
		
		if( !in_array( $type, $this->image_types ) )
		{
			$this->error		= 'bad_watermark_type';
			return false;
		}
		
	 	//---------------------------------------------------------
	 	// Get dimensions
	 	//---------------------------------------------------------
	 	
	 	$img_info	= @getimagesize( $path );
	 	$locate_x	= $this->cur_dimensions['width'] - $img_info[0];
	 	$locate_y	= $this->cur_dimensions['height'] - $img_info[1];

	 	//---------------------------------------------------------
	 	// Working with original file or temp file?
	 	//---------------------------------------------------------
	 	
		$file 		= file_exists( $this->temp_file ) ? $this->temp_file : $this->image_full;
		
	 	//---------------------------------------------------------
	 	// Apply watermark and verify
	 	//---------------------------------------------------------
	 	
		system("{$this->im_path}/composite -geometry +{$locate_x}+{$locate_y} {$path} {$file} {$this->temp_file}" );

	 	if( file_exists( $this->temp_file ) )
	 	{
	 		$this->force_resize	= true;
	 		
		 	return true;
	 	}
	 	else
	 	{
		 	return false;			
	 	}
	}
	
    /**
	 * Add copyright text to image
	 *
	 * @access	public
	 * @param	string 		Copyright text to add
	 * @param	array		[Optional] Text options (color, halign, valign, font [1-5])
	 * @return	boolean		Watermark addition successful
	 */
	public function addCopyrightText( $text, $textOpts=array() )
	{
	 	//---------------------------------------------------------
	 	// Have text?
	 	//---------------------------------------------------------
	 	
		if( !$text )
		{
	 		$this->error		= 'no_text_for_copyright';
		 	return false;
	 	}
	 	
	 	//---------------------------------------------------------
	 	// @ causes IM to try to read text from file specified by @
	 	//---------------------------------------------------------
	 	$text	= ltrim( $text, '@' );
	 	
	 	//---------------------------------------------------------
	 	// Verify options
	 	//---------------------------------------------------------
	 		 	
		$font	= $textOpts['font'] 	? $textOpts['font'] 			: 3;
		$color	= $textOpts['color']	? $textOpts['color']			: '#ffffff';
		$width	= $this->cur_dimensions['width'] - 10;
		$halign	= ( isset($textOpts['halign']) AND in_array( $textOpts['halign'], array( 'right', 'center', 'left' ) ) )
										? $textOpts['halign']			: 'right';
		$valign	= ( isset($textOpts['valign']) AND in_array( $textOpts['valign'], array( 'top', 'middle', 'bottom' ) ) )
										? $textOpts['valign']			: 'bottom';
		
	 	//---------------------------------------------------------
	 	// Working with orig file or temp file?
	 	//---------------------------------------------------------
	 	
		$file 		= file_exists( $this->temp_file ) ? $this->temp_file : $this->image_full;
		
	 	//---------------------------------------------------------
	 	// Set gravity (location of text)
	 	//---------------------------------------------------------
	 	
		$gravity	= "";
		
		switch( $valign )
		{
			case 'top':
				$gravity = "North";
			break;
			
			case 'bottom':
				$gravity = "South";
			break;
		}
		
		if( $valign == 'middle' AND $halign == 'center' )
		{
			$gravity = "Center";
		}
		
		switch( $halign )
		{
			case 'right':
				$gravity .= "East";
			break;
			
			case 'left':
				$gravity .= "West";
			break;
		}
		
	 	//---------------------------------------------------------
	 	// Apply annotation to image and verify
	 	//---------------------------------------------------------
	 			
		system("{$this->im_path}/composite {$file} -fill {$color} -undercolor #000000 -gravity {$gravity} -annotate +0+5 '{$text}' {$this->temp_file}" );

	 	if( file_exists( $this->temp_file ) )
	 	{
	 		$this->force_resize	= true;
	 		
		 	return true;
	 	}
	 	else
	 	{
		 	return false;			
	 	}
	}
	
    /**
	 * Image handler desctructor
	 *
	 * @access	public
	 * @return	void
	 */
	public function __destruct()
	{
	 	//---------------------------------------------------------
	 	// Remove temp file if it hasn't been saved
	 	//---------------------------------------------------------
	 	
		if( file_exists( $this->temp_file ) )
		{
			@unlink( $this->temp_file );
		}
		
		parent::__destruct();
	}

}
