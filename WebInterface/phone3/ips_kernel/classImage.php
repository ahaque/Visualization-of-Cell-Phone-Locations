<?php

/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Image Handler: create thumbnails, apply watermarks and copyright tests, save or display final image
 * Last Updated: $Date: 2009-06-12 22:35:26 -0400 (Fri, 12 Jun 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Services Kernel
 * @since		Monday 5th May 2008 14:00
 * @version		$Revision: 282 $
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

/**
 * Image interface
 *
 */
interface interfaceImage
{
    /**
	 * Initiate image handler, perform any necessary setup
	 *
	 * @param	array 		Necessary options to init module
	 * @return	boolean		Initiation successful
	 */
	public function init( $opts=array() );
	
    /**
	 * Add a supported image type (assumes you have properly extended the class to add the support)
	 *
	 * @param	string 		Image extension type to add support for
	 * @return	boolean		Addition successful
	 */
	public function addImagetype( $ext );
	
    /**
	 * Resize image proportionately
	 *
	 * @param	integer 	Maximum width
	 * @param	integer 	Maximum height
	 * @return	array		Dimensons of the original image and the resized dimensions
	 */
	public function resizeImage( $width, $height );
	
    /**
	 * Write image to file
	 *
	 * @param	string 		File location (including file name)
	 * @return	boolean		File write successful
	 */
	public function writeImage( $path );
	
    /**
	 * Print image to screen
	 *
	 * @return	void		Image printed and script exits
	 */
	public function displayImage();
	
    /**
	 * Add watermark to image
	 *
	 * @param	string 		Watermark image path
	 * @param	integer		[Optional] Opacity 0-100
	 * @return	boolean		Watermark addition successful
	 */
	public function addWatermark( $path, $opacity=100 );
	
    /**
	 * Add copyright text to image
	 *
	 * @param	string 		Copyright text to add
	 * @param	array		[Optional] Text options (color, background color, font [1-5])
	 * @return	boolean		Watermark addition successful
	 */
	public function addCopyrightText( $text, $textOpts=array() );

}

/**
 * Image abstract class
 *
 */
abstract class classImage
{
	/**
	 * Error encountered
	 *
	 * @access	public
	 * @var		string		Error Message
	 */
	public $error				= '';
	
	/**
	 * Image Path
	 *
	 * @access	protected
	 * @var		string		Path to image
	 */
	protected $image_path		= '';
	
	/**
	 * Image File
	 *
	 * @access	protected
	 * @var		string		Image file to work with
	 */
	protected $image_file		= '';
	
	/**
	 * Image path + file
	 *
	 * @access	protected
	 * @var		string		Full image path and filename
	 */
	protected $image_full		= '';
	
	/**
	 * Image dimensions
	 *
	 * @access	protected
	 * @var		array		Original Image Dimensions
	 */
	protected $orig_dimensions	= array( 'width' => 0, 'height' => 0 );
	
	/**
	 * Image current dimensions
	 *
	 * @access	public
	 * @var		array		Curernt/New Image Dimensions
	 */
	public $cur_dimensions		= array( 'width' => 0, 'height' => 0 );
	
	/**
	 * Image Types Supported
	 *
	 * @access	protected
	 * @var		array		Image types we can work with
	 */
	protected $image_types		= array( 'gif', 'jpeg', 'jpg', 'jpe', 'png' );
	
	/**
	 * Extension of image
	 *
	 * @access	public
	 * @var		string		Image extension
	 */
	public $image_extension		= '';
	
	/**
	 * Resize image anyways (e.g. if we have added watermark)
	 *
	 * @access	public
	 * @var		bool
	 */
	public $force_resize		= false;

    /**
	 * Image handler constructor
	 *
	 * @access	public
	 * @return	boolean		Construction successful
	 */
	public function __construct()
	{
		return true;
	}
	
    /**
	 * Image handler desctructor
	 *
	 * @access	public
	 * @return	void
	 */
	public function __destruct()
	{
	}

	
	/**
	 * Cleans up paths, generates var $in_file_complete
	 *
	 * @access	protected
	 * @param	string		Path to clean
	 * @return 	string		Cleaned path
	 */
	protected function _cleanPaths( $path='' )
	{
	 	//---------------------------------------------------------
	 	// Remove trailing slash
	 	//---------------------------------------------------------
	 	
		return rtrim( $path, '/' );
	}
	
    /**
	 * Add a supported image type (assumes you have properly extended the class to add the support)
	 *
	 * @access	public
	 * @param	string 		Image extension type to add support for
	 * @return	boolean		Addition successful
	 */
	public function addImagetype( $ext )
	{
	 	//---------------------------------------------------------
	 	// Add a supported image extension
	 	//---------------------------------------------------------
	 	
		if( !in_array( $ext, $this->image_types ) )
		{
			$this->image_types[] = $ext;
		}
		
		return true;
	}
	
    /**
	 * Get new dimensions for resizing
	 *
	 * @access	protected
	 * @param	integer 	Maximum width
	 * @param	integer 	Maximum height
	 * @return	array		[img_width,img_height]
	 */
	protected function _getResizeDimensions( $width, $height )
	{
	 	//---------------------------------------------------------
	 	// Verify width and height are valid and > 0
	 	//---------------------------------------------------------
	 	
		$width	= intval($width);
		$height	= intval($height);

		if( !$width OR !$height )
		{
			$this->error		= 'bad_dimensions';
			return false;
		}
		
	 	//---------------------------------------------------------
	 	// Is the current image already smaller?
	 	//---------------------------------------------------------
	 	
		if( $width > $this->cur_dimensions['width'] AND $height > $this->cur_dimensions['height'] )
		{
			$this->error		= 'already_smaller';
			return false;
		}
		
	 	//---------------------------------------------------------
	 	// Return new dimensions
	 	//---------------------------------------------------------

		return $this->_scaleImage( array(
										'cur_height'	=> $this->cur_dimensions['height'],
										'cur_width'		=> $this->cur_dimensions['width'],
										'max_height'	=> $height,
										'max_width'		=> $width,
								)		);
	}
	
	
	/**
	 * Return proportionate image dimensions based on current and max dimension settings
	 *
	 * @access	protected
	 * @param	array 		[ cur_height, cur_width, max_width, max_height ]
	 * @return	array		[ img_height, img_width ]
	 */
	protected function _scaleImage( $arg )
	{
		$ret = array(
					  'img_width'  => $arg['cur_width'],
					  'img_height' => $arg['cur_height']
					);
		
		if ( $arg['cur_width'] > $arg['max_width'] )
		{
			$ret['img_width']  = $arg['max_width'];
			$ret['img_height'] = ceil( ( $arg['cur_height'] * ( ( $arg['max_width'] * 100 ) / $arg['cur_width'] ) ) / 100 );
			$arg['cur_height'] = $ret['img_height'];
			$arg['cur_width']  = $ret['img_width'];
		}
		
		if ( $arg['cur_height'] > $arg['max_height'] )
		{
			$ret['img_height']  = $arg['max_height'];
			$ret['img_width']   = ceil( ( $arg['cur_width'] * ( ( $arg['max_height'] * 100 ) / $arg['cur_height'] ) ) / 100 );
		}

		return $ret;
	}
}