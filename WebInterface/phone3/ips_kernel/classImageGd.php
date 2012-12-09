<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Image Handler: GD2 Library
 * Last Updated: $Date: 2009-08-12 18:08:03 -0400 (Wed, 12 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Services Kernel
 * @link		http://www.
 * @since		Monday 5th May 2008 14:00
 * @version		$Revision: 312 $
 *
 * GD Example
 * <code>
 * $image = new classImageGd();
 * $image->init( array(
 * 					'image_path'	=> "/path/to/images/",
 * 					'image_file'	=> "image_filename.jpg",
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

class classImageGd extends classImage implements interfaceImage
{
	/**
	 * Image resource
	 *
	 * @access	private
	 * @var		resource	Image resource
	 */
	private $image			= null;
	
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
	 * @param	array 		Necessary options to init module [image_path, image_file]
	 * @return	boolean		Initiation successful
	 */
	public function init( $opts=array() )
	{
	 	//---------------------------------------------------------
	 	// Verify input
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
		
	 	//---------------------------------------------------------
	 	// Store paths
	 	//---------------------------------------------------------
	 			
		$this->image_path		= $this->_cleanPaths( $opts['image_path'] );
		$this->image_file		= $opts['image_file'];
		$this->image_full		= $this->image_path . '/' . $this->image_file;
		
	 	//---------------------------------------------------------
	 	// Get extension
	 	//---------------------------------------------------------
	 	
		$this->image_extension	= strtolower(pathinfo( $this->image_file, PATHINFO_EXTENSION ));
		
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
	 	// Get and remember dimensions
	 	//---------------------------------------------------------
	 
		$dimensions = getimagesize( $this->image_full );
		
		$this->orig_dimensions	= array( 'width' => $dimensions[0], 'height' => $dimensions[1] );
		$this->cur_dimensions	= $this->orig_dimensions;
		
	 	//---------------------------------------------------------
	 	// Create image resource
	 	//---------------------------------------------------------
	 	
		switch( $this->image_extension )
		{
			case 'gif':
				$this->image = @imagecreatefromgif( $this->image_full );
			break;
			
			case 'jpeg':
			case 'jpg':
			case 'jpe':
				$this->image = @imagecreatefromjpeg( $this->image_full );
			break;
			
			case 'png':
				$this->image = @imagecreatefrompng( $this->image_full );
			break;
		}
		
		if( !$this->image )
		{
			//-----------------------------------------
			// Fallback
			// @see http://forums./index.php?app=tracker&showissue=17836
			//-----------------------------------------
			
			if( $this->image = @imagecreatefromstring( file_get_contents( $this->image_full ) ) )
			{
				return true;
			}

			return false;
		}
		else
		{
			return true;
		}
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
	 	// Get proportionate dimensions and store
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

	 	//---------------------------------------------------------
	 	// Create new image resource
	 	//---------------------------------------------------------
	 	
		$new_img	= imagecreatetruecolor( $new_dims['img_width'], $new_dims['img_height'] );

	 	if( !$new_img )
	 	{
	 		$this->error		= 'image_creation_failed';
		 	return array();
	 	}
	 	
	 	//---------------------------------------------------------
	 	// Apply alpha blending
	 	//---------------------------------------------------------
	 	
		switch( $this->image_extension )
		{
			case 'jpeg':
			case 'jpg':
			case 'jpe':
				imagealphablending( $new_img, TRUE );
			break;
			
			case 'png':
				imagealphablending( $new_img, FALSE );
				imagesavealpha( $new_img, TRUE );
			break;
		}	

	 	//---------------------------------------------------------
	 	// Copy image resampled
	 	//---------------------------------------------------------
	 	
	 	@imagecopyresampled( $new_img, $this->image, 0, 0, 0 ,0, $new_dims['img_width'], $new_dims['img_height'], $this->cur_dimensions['width'], $this->cur_dimensions['height'] ); 
	 	
	 	$this->cur_dimensions = array( 'width' => $new_dims['img_width'], 'height' => $new_dims['img_height'] );
	 	
	 	//---------------------------------------------------------
	 	// Don't forget the alpha blending
	 	//---------------------------------------------------------
	 	
		switch( $this->image_extension )
		{
			case 'png':
				imagealphablending( $new_img, FALSE );
				imagesavealpha( $new_img, TRUE );
			break;
		}	 

	 	//---------------------------------------------------------
	 	// Destroy original resource and store new resource
	 	//---------------------------------------------------------

	 	@imagedestroy( $this->image );
	 	
	 	$this->image	= $new_img;

	 	return array( 'originalWidth'  => $this->orig_dimensions['width'],
					  'originalHeight' => $this->orig_dimensions['height'],
					  'newWidth'       => $new_dims['img_width'],
					  'newHeight'      => $new_dims['img_height'] );
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
	 	// Write file and verify
	 	//---------------------------------------------------------
	 	
		switch( $this->image_extension )
		{
			case 'gif':
				@imagegif( $this->image, $path );
			break;
			
			case 'jpeg':
			case 'jpg':
			case 'jpe':
				@imagejpeg( $this->image, $path, $this->quality['jpg'] );
			break;
			
			case 'png':
				@imagepng( $this->image, $path, $this->quality['png'] );
			break;
		}
		
		if( !file_exists( $path ) )
		{
	 		$this->error		= 'unable_to_write_image';
		 	return false;
	 	}
		
	 	//---------------------------------------------------------
	 	// Chmod 777
	 	//---------------------------------------------------------
	 	
	 	@chmod( $path, 0777 );
	 	
	 	//---------------------------------------------------------
	 	// Destroy image resource
	 	//---------------------------------------------------------
	 	
	 	@imagedestroy( $this->image );
	 	
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
	 	// Send appropriate header and output image
	 	//---------------------------------------------------------
	 	
		switch( $this->image_extension )
		{
			case 'gif':
				@header('Content-type: image/gif');
				@imagegif( $this->image );
			break;
			
			case 'jpeg':
			case 'jpg':
			case 'jpe':
				@header('Content-Type: image/jpeg' );
				@imagejpeg( $this->image, null, $this->quality['jpg'] );
			break;
			
			case 'png':
				@header('Content-Type: image/png' );
				@imagepng( $this->image, null, $this->quality['png'] );
			break;
		}
		
	 	//---------------------------------------------------------
	 	// Destroy image resource
	 	//---------------------------------------------------------
	 	
	 	@imagedestroy( $this->image );
		
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
	 	// Create resource from watermark and verify
	 	//---------------------------------------------------------
	 	
		switch( $type )
		{
			case 'gif':
				$mark = imagecreatefromgif( $path );
			break;
			
			case 'jpeg':
			case 'jpg':
			case 'jpe':
				$mark = imagecreatefromjpeg( $path );
			break;
			
			case 'png':
				$mark = imagecreatefrompng( $path );
			break;
		}
		
		if( !$mark )
		{
	 		$this->error		= 'image_creation_failed';
		 	return false;
	 	}
	 	
	 	//---------------------------------------------------------
	 	// Alpha blending..
	 	//---------------------------------------------------------
	 	
		switch( $this->image_extension )
		{
			case 'jpeg':
			case 'jpg':
			case 'jpe':
			case 'png':
				@imagealphablending( $this->image, TRUE );
			break;
		}
		
	 	//---------------------------------------------------------
	 	// Get dimensions of watermark
	 	//---------------------------------------------------------
	 	
	 	$img_info		= @getimagesize( $path );
	 	$locate_x		= $this->cur_dimensions['width'] - $img_info[0];
	 	$locate_y		= $this->cur_dimensions['height'] - $img_info[1];

	 	//---------------------------------------------------------
	 	// Merge watermark image onto original image
	 	//---------------------------------------------------------
	 	
	 	if( $type == 'png' )
 		{
	 		@imagecopy( $this->image, $mark, $locate_x, $locate_y, 0, 0, $img_info[0], $img_info[1] );
 		}
 		else
		{
			@imagecopymerge( $this->image, $mark, $locate_x, $locate_y, 0, 0, $img_info[0], $img_info[1], $opacity );
		}
	 	
	 	//---------------------------------------------------------
	 	// And alpha blending again...
	 	//---------------------------------------------------------
	 	
		switch( $this->image_extension )
		{
			case 'png':
				@imagealphablending( $this->image, FALSE );
				@imagesavealpha( $this->image, TRUE );
			break;
		}	 	

	 	//---------------------------------------------------------
	 	// Destroy watermark image resource and return
	 	//---------------------------------------------------------
	 	
	 	imagedestroy( $mark );

		$this->force_resize	= true;
		
	 	return true;
	}
	
    /**
	 * Add copyright text to image
	 *
	 * @access	public
	 * @param	string 		Copyright text to add
	 * @param	array		[Optional] Text options (color, halign, valign, padding, font [1-5])
	 * @return	boolean		Watermark addition successful
	 */
	public function addCopyrightText( $text, $textOpts=array() )
	{
	 	//---------------------------------------------------------
	 	// Verify input
	 	//---------------------------------------------------------
	 	
		if( !$text )
		{
	 		$this->error		= 'no_text_for_copyright';
		 	return false;
	 	}
	 	
		$font	= $textOpts['font'] 	? $textOpts['font'] 			: 3;
		
	 	//---------------------------------------------------------
	 	// Colors input as hex...convert to rgb
	 	//---------------------------------------------------------
	 	
		$color	= $textOpts['color']	? array(
												hexdec( substr( ltrim( $textOpts['color'], '#' ), 0, 2 ) ),
												hexdec( substr( ltrim( $textOpts['color'], '#' ), 2, 2 ) ),
												hexdec( substr( ltrim( $textOpts['color'], '#' ), 5, 2 ) )
												)						: array( 255, 255, 255 );
		$width		= $this->cur_dimensions['width'] - 10;
		$halign		= ( isset($textOpts['halign']) AND in_array( $textOpts['halign'], array( 'right', 'center', 'left' ) ) )
										? $textOpts['halign']			: 'right';
		$valign		= ( isset($textOpts['valign']) AND in_array( $textOpts['valign'], array( 'top', 'middle', 'bottom' ) ) )
										? $textOpts['valign']			: 'bottom';
		$padding	= $textOpts['padding'] 	? $textOpts['padding'] 			: 5;
		
	 	//---------------------------------------------------------
	 	// Get some size info and set properties
	 	//---------------------------------------------------------
	 	
		$fontwidth	= imagefontwidth($font);
		$fontheight	= imagefontheight($font);

		$margin 	= floor($padding / 2 ); 

		if ( $width > 0 )
		{
			$maxcharsperline	= floor( ($width - ($margin * 2)) / $fontwidth);
			$text 				= wordwrap( $text, $maxcharsperline, "\n", 1 );
		}

		$lines 					= explode( "\n", $text );

	 	//---------------------------------------------------------
	 	// Top, middle or bottom?
	 	//---------------------------------------------------------
	 	
		switch( $valign )
		{
			case "middle":
				$y = ( imagesy($this->image) - ( $fontheight * count($lines) ) ) / 2;
				break;

			case "bottom":
				$y = imagesy($this->image) - ( ( $fontheight * count($lines) ) + $margin );
				break;

			default:
				$y = $margin;
				break;
		}
		
	 	//---------------------------------------------------------
	 	// Allocate colors for text/bg
	 	//---------------------------------------------------------
	 	
		$color		= imagecolorallocate( $this->image, $color[0], $color[1], $color[2] );
		$rect_back	= imagecolorallocate( $this->image, 0,0,0 );
		
	 	//---------------------------------------------------------
	 	// Switch on horizontal position and write text lines
	 	//---------------------------------------------------------
	 	
		switch( $halign )
		{
			case "right":
				while( list($numl, $line) = each($lines) ) 
				{
					imagefilledrectangle( $this->image, ( imagesx($this->image) - $fontwidth * strlen($line) ) - $margin, $y, imagesx($this->image) - 1, imagesy($this->image) - 1, $rect_back );
					imagestring( $this->image, $font, ( imagesx($this->image) - $fontwidth * strlen($line) ) - $margin, $y, $line, $color );
					$y += $fontheight;
				}
				break;

			case "center":
				while( list($numl, $line) = each($lines) ) 
				{
					imagefilledrectangle( $this->image, floor( ( imagesx($this->image) - $fontwidth * strlen($line) ) / 2 ), $y, imagesx($this->image), imagesy($this->image), $rect_back );
					imagestring( $this->image, $font, floor( ( imagesx($this->image) - $fontwidth * strlen($line) ) / 2 ), $y, $line, $color );
					$y += $fontheight;
				}
			break;

			default:
				while( list($numl, $line) = each($lines) ) 
				{
					imagefilledrectangle( $this->image, $margin, $y, imagesx($this->image), imagesy($this->image), $rect_back );
					imagestring( $this->image, $font, $margin, $y, $line, $color );
					$y += $fontheight;
				}
			break;
		}
		
		$this->force_resize	= true;
		
		return true;
	}

}