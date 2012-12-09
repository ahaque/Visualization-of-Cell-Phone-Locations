<?php

/**
 * Graph class
 * Last Updated   : $Date: 2009-01-08 14:14:53 -0500 (Thu, 08 Jan 2009) $
 * Last Updated by: $Author: bfarber $
 *
 * @author 		Remco Wilting
 * @copyright	(c) 2008 Remco Wilting
 * @package		Display Graph Class
 * @since		Sunday 6th July 2008 16:35
 * @version		$Revision: 209 $
 */

//+---------------------------------------------------------------------------
// USAGE:
// $graph = new classGraph();
// $graph->options['title'] = 'test graph';
// $graph->options['width'] = 400;
// $graph->options['height'] = 300;
// $graph->options['style3D'] = 1;
// $graph->addLabels( array( 'alpha', 'beta', 'gamma' ) );
// $graph->addSeries( 'first bars', array( 5, 3, 0 ) );
// $graph->addSeries( 'second bars', array( 20, 3, 5 ) );
// $graph->options['charttype'] = 'Bar';
// $graph->display();
//+---------------------------------------------------------------------------


class classGraph
{
	/**
	* Graph options array
	*
	* @access	public
	* @var 		array 		Options array
	*/
    public $options			= array( 'titlecolor'		=> '#000000',			// The color of the title
									 'title'			=> 'Graph',				// The title text (no title of empty)
									 'titleshadow'		=> '#AAAAAA',			// The shadowcolor of the title
        							 'titlesize'		=> 16,					// The fontsize in points for the title
									 'width'			=> 600,					// The width of the graph image
									 'height'			=> 400,					// The height of the graph image
									 'charttype'		=> 'Pie',				// The type of graph
									 'style3D'			=> 1,					// 3D style graph (yes/no)
        							 'bgcolor'			=> '#FFFFFF',			// Background color of the graph image
									 'textcolor'		=> '#FFFFFF',			// Textcolor for data labels
									 'font'				=> 'fonts/xsuni.ttf',	// Font file used (if TTF)
									 'xaxistitle'		=> '',					// Title for the x-axis (no title if empty)
									 'yaxistitle'		=> '',					// Title for the y-axis (no title if empty)
									 'showdatalabels'	=> 1,					// Display the datalabels (yes/no)
									 'showlegend'		=> 1,					// Display the legend (yes/no)
									 'showgridlinesx'	=> 1,					// Show gridlines for the x-axis (yes/no)
									 'showgridlinesy'	=> 1,					// Show gridlines for the y-axis (yes/no)
									 'numticks'			=> 5					// Number of ticks used for numeric axes
								   );

	/**
	* Enable Debug Mode
	*
	* @access	protected
	* @var 		boolean 		Enable Debug Mode
	*/
	protected $debug		= 0;

	/**
	* Debug Timer
	*
	* @access	protected
	* @var 		int 			Debug Timer
	*/
	protected $starttime	= 0;

	/**
	* Use TTF Fonts
	*
	* @access	protected
	* @var 		boolean 		Use TTF fonts
	*/
    protected $use_ttf		= 0;

	/**
	* Graph data array
	*
	* @access	protected
	* @var 		boolean 		Graph data array
	*/
    protected $data			= array();

	/**
	* Number of Y-axis data array's
	*
	* @access	protected
	* @var 		int 		Holds the number of y-axis data array's in $data
	*/
	protected $yaxis_count	= 0;

	/**
	* Vertical size of the title
	*
	* @access	protected
	* @var 		int 		Holds the size of the title
	*/
	protected $titlesize	= 0;

	/**
	* Pre-set colors
	*
	* @access	protected
	* @var 		boolean 		Holds pre-set colors
	*/
    protected $colors		= array( '80,120,200',
    								 '160,80,160',
									 '0,120,80',
									 '240,160,60',
									 '40,160,240',
									 '200,100,100',
									 '100,200,100',
									 '240,200,100',
									 '60,60,160',
									 '240,240,100'
								   );

	/**
	* Used colors
	*
	* @access	protected
	* @var 		boolean 		Holds used colors
	*/
    protected $used_colors	= array( 0 => '0,0,0' );

	/**
	* Current chart colors
	*
	* @access	protected
	* @var 		boolean 		Current chart colors
	*/
    protected $color		= array();

	/**
	* Graph area positions
	*
	* @access	protected
	* @var 		array 			Positions of the grapharea (x0, x1, y0, y1)
	*/
	protected $grapharea	= array();

	/**
	* Legend data store
	*
	* @access	protected
	* @var 		array 			Holds the legend names
	*/
	protected $legend		= array();

	/**
	* X-Axis data store
	*
	* @access	protected
	* @var 		array 			Holds the specific data for the X-Axis
	*/
	protected $x_axis		= array();

	/**
	* Y-Axis data store
	*
	* @access	protected
	* @var 		array 			Holds the specific data for the Y-Axis
	*/
	protected $y_axis		= array();

	/**
	* Graph image resource
	*
	* @access	protected
	* @var 		resource		Holds the graph image resource id
	*/
	protected $image		= null;

	/**
	* Fontsize of non-TTF fonts
	*
	* @access	protected
	* @var 		int 			The fontsize of non-TTF fonts
	*/
    protected $fontsize		= 3;

	/**
	* Color resource for the color black
	*
	* @access	protected
	* @var 		resource		Color resource for the color black
	*/
    protected $black		= null;


    /**
	* Constructor
	*
	* @access	public
	* @param	boolean		[Optional] Enable Debug Mode
	* @return	void
	*/
	public function __construct( $debug = 0 )
	{
		$this->debug = ( $debug ? 1 : 0 );
		if ( $this->debug )
		{
	        $mtime = microtime ();
	        $mtime = explode (' ', $mtime);
	        $mtime = $mtime[1] + $mtime[0];
	        $this->starttime = $mtime;
	    }
	}


    /**
	* Add labels to the graph data
	*
	* @access	public
	* @param	array		Array of data labels
	* @return	void
	*/
	public function addLabels( $data = array() )
	{
		$this->data['xaxis'] = $data;
	}


    /**
	* Add a serie to the graph data
	*
	* @access	public
	* @param	string		Name of the data series
	* @param	array		Array of data values
	* @return	void
	*/
	public function addSeries( $name = '', $data = array(), $color = '' )
	{
		$this->data['yaxis'][$this->yaxis_count]['name'] = $name;
		$this->data['yaxis'][$this->yaxis_count]['data'] = $data;
		$this->data['yaxis'][$this->yaxis_count]['color'] = $color;
		$this->yaxis_count++;
	}


    /**
	* Output the graph as png image
	*
	* @access	public
	* @return	boolean		True if succesful; False if graph generation failed.
	*/
	public function display()
	{
        //-----------------------------------------
        // Check font
        //-----------------------------------------
        if ( $this->options['font'] and is_readable( $this->options['font'] ) )
        {
            $this->use_ttf = 1;
        }

        //-----------------------------------------
        // Check pre-requisites width, height
        //-----------------------------------------
		if ( $this->options['width'] < 400 or $this->options['height'] < 200 )
		{
			$this->_error( 'The minimum width of the graph is 400 and the minimum height is 200.' );
			return false;
		}

        //-----------------------------------------
        // Check supplied colors
        //-----------------------------------------
		if ( !$this->_checkColor( $this->options['titlecolor'] ) ||
			 !$this->_checkColor( $this->options['titleshadow'] ) ||
			 !$this->_checkColor( $this->options['bgcolor'] ) ||
			 !$this->_checkColor( $this->options['textcolor'] ) )
		{
			return false;
		}

		//-----------------------------------------
        // Determine graph area
        //-----------------------------------------
		$this->grapharea = array( 'x0' => 5, 'x1' => $this->options['width'] - 5, 'y0' => 5, 'y1' => $this->options['height'] - 5 );

        //-----------------------------------------
        // Start GD process
        //-----------------------------------------
        $this->image = imagecreatetruecolor( $this->options['width'], $this->options['height'] );

        if ( function_exists('imageantialias') )
        {
            @imageantialias( $this->image, TRUE );
        }

        //-----------------------------------------
        // Allocate BG color and black
        //-----------------------------------------
        $bgcolor = imagecolorallocate($this->image, hexdec(substr($this->options['bgcolor'],1,2)), hexdec(substr($this->options['bgcolor'],3,2)), hexdec(substr($this->options['bgcolor'],5,2)));
        imagefilledrectangle( $this->image, 0, 0, $this->options['width'], $this->options['height'], $bgcolor );

        $this->black = imagecolorallocate( $this->image, 0, 0, 0 );

        //-----------------------------------------
        // Draw the title
        //-----------------------------------------
		$this->_drawTitle();

        //-----------------------------------------
        // Draw the graph based on type
        //-----------------------------------------
		switch ( $this->options['charttype'] )
		{
			case 'Pie':
				if ( !$this->_checkSeries( 1, 1 ) ) return false;
				if ( !$this->_drawPie() ) return false;
				break;
			case 'Doughnut':
				if ( !$this->_checkSeries( 1, 1 ) ) return false;
				if ( !$this->_drawDoughnut() ) return false;
				break;
			case 'Bar':
				if ( !$this->_checkSeries( 1, 1 ) ) return false;
				if ( !$this->_drawBars() ) return false;
				break;
			case 'HBar':
				if ( !$this->_checkSeries( 1, 1 ) ) return false;
				if ( !$this->_drawHorizontalBars() ) return false;
				break;
			case 'Line':
				if ( !$this->_checkSeries( 1, 1 ) ) return false;
				if ( !$this->_drawLines() ) return false;
				break;
			case 'Area':
				if ( !$this->_checkSeries( 1, 1 ) ) return false;
				if ( !$this->_drawArea() ) return false;
				break;
			case 'XY':
				if ( !$this->_checkSeries( 2, 0 ) ) return false;
				if ( !$this->_drawXY() ) return false;
				break;
			case 'Bubble':
				if ( !$this->_checkSeries( 3, 0 ) ) return false;
				if ( !$this->_drawBubble() ) return false;
				break;
			case 'Funnel':
				if ( !$this->_checkSeries( 1, 1 ) ) return false;
				if ( !$this->_drawFunnel() ) return false;
				break;
			case 'Radar':
				if ( !$this->_checkSeries( 1, 1 ) ) return false;
				if ( !$this->_drawRadar() ) return false;
				break;
			default:
				$this->_error( 'Unknow graphtype \''.$this->options['charttype'].'\' supplied.' );
				return false;
		}

        //-----------------------------------------
        // Debug Mode?
        //-----------------------------------------
		if ( $this->debug )
		{
	        $mtime = microtime ();
	        $mtime = explode (' ', $mtime);
	        $mtime = $mtime[1] + $mtime[0];
	        $endtime = $mtime;
	        $totaltime = sprintf( "%.4f", round (($endtime - $this->starttime), 5) ) . ' sec';

            if ( $this->use_ttf )
            {
	            $txtsize	= imagettfbbox( '10', 0, $this->options['font'], $totaltime );
				$textx = $this->options['width'] - ( $txtsize[4] - $txtsize[0] ) - 5;
				$texty = 5 + ( $txtsize[1] - $txtsize[5] );
                imagettftext($this->image, "10", 0, $textx, $texty, $this->black  , $this->options['font'], $totaltime );
		        imagerectangle( $this->image, $textx-5, $texty+5, $this->options['width']-1, 0, $this->black );
            }
            else
            {
				$textx = $this->options['width'] - imagefontwidth($this->fontsize)*strlen($totaltime) - 5;
				$texty = 5;
                ImageString( $this->image, $this->fontsize, $textx, $texty, $totaltime, $this->black);
		        imagerectangle( $this->image, $textx-5, $texty+5+imagefontheight($this->fontsize), $this->options['width']-1, 0, $this->black );
            }
	    }

        //-----------------------------------------
        // Flush image
        //-----------------------------------------
        header('Content-type: image/png');
        imagepng($this->image);
        imagedestroy($this->image);

		return true;
	}


    /**
	* Draw the graph title
	*
	* @access	protected
	* @return	void
	*/
	protected function _drawTitle()
	{
		if ( $this->options['title'] != '' )
		{
			//-----------------------------------------
	        // Allocate text and shadow cols
	        //-----------------------------------------

	        $textcolor   = imagecolorallocate( $this->image, hexdec(substr($this->options['titlecolor'],1,2)), hexdec(substr($this->options['titlecolor'],3,2)), hexdec(substr($this->options['titlecolor'],5,2)));
	        $shadowcolor = imagecolorallocate( $this->image, hexdec(substr($this->options['titleshadow'],1,2)), hexdec(substr($this->options['titleshadow'],3,2)), hexdec(substr($this->options['titleshadow'],5,2)));

	        //-----------------------------------------
	        // Generate title w/shadow
	        //-----------------------------------------

	        if ( $this->use_ttf )
	        {
	            $txtsize	= imagettfbbox($this->options['titlesize'], 0, $this->options['font'], $this->options['title'] );
	            $textx		= round($this->options['width']/2,0) - round(($txtsize[2]-$txtsize[0])/2,0);
	            $texty		= $txtsize[1] - $txtsize[5] + $this->grapharea['y0'];

	            imagettftext($this->image, $this->options['titlesize'], 0, $textx+1, $texty+1, $shadowcolor, $this->options['font'], $this->options['title']);
	            imagettftext($this->image, $this->options['titlesize'], 0, $textx  , $texty  , $textcolor  , $this->options['font'], $this->options['title']);

				$this->grapharea['y0'] = $texty + 10;
	        }
	        else
	        {
	            $textx		= round($this->options['width']/2,0) - round((imagefontwidth(5)*strlen($this->options['title']))/2,0);
	            $texty		= $this->grapharea['y0'];

	            imagestring( $this->image, 5, $textx+1, $texty+1, $this->options['title'], $shadowcolor );
	            imagestring( $this->image, 5, $textx, $texty, $this->options['title'], $textcolor   );
				$this->grapharea['y0'] = $texty + imagefontheight(5) + 10;
	        }
		}
	}


    /**
	* Draw the legend
	*
	* @access	protected
	* @return	void
	*/
	protected function _drawLegend()
	{
		if ( $this->options['showlegend'] )
		{
	        //-----------------------------------------
	        // Work out legend position
	        //-----------------------------------------
	        $ci      = 0;
	        $legendx = 0;
	        $legendy = 0;
	        $maxtxty = 10;

	        foreach ( $this->legend as $name )
	        {
	            if ( $this->use_ttf )
	            {
	                $txtsize = imagettfbbox('10', 0, $this->options['font'], $name );
	                $legendx = ($txtsize[2]-$txtsize[0]) > $legendx ?  $txtsize[2]-$txtsize[0]  : $legendx;
	                $maxtxty = ($txtsize[1]-$txtsize[7]) > $maxtxty ? ($txtsize[1]-$txtsize[7]) : $maxtxty;
	            }
	            else
	            {
	                $txtsize = strlen( $name ) * imagefontwidth($this->fontsize);
	                $legendx = ($txtsize > $legendx) ?  $txtsize : $legendx;
	                $maxtxty = imagefontheight($this->fontsize);
	            }

	            $ci ++;

	        }
	        $legendx = $this->grapharea['x1'] - ($legendx + 25);
	        $legendy = round( ($this->options['height'] / 2) - ((($maxtxty+5) * $ci) / 2) );

	        //-----------------------------------------
	        // Do shade
	        //-----------------------------------------

	        $textcolor   = imagecolorallocate( $this->image, 0, 0, 0 );
	        $shadowcolor = imagecolorallocate( $this->image, 170, 170, 170);
	        $ypos        = $legendy;
	        $ci          = 0;
	        $color       = array();

	        //-----------------------------------------
	        // Draw legends
	        //-----------------------------------------

	        foreach ( $this->legend as $name )
	        {
	            //-----------------------------------------
	            // Get Pie slice colors
	            //-----------------------------------------
				if ( isset( $this->data['yaxis'][$ci]['color'] ) && $this->data['yaxis'][$ci]['color'] != '' &&
				     $this->options['charttype'] != 'Pie' && $this->options['charttype'] != 'Doughnut' && $this->options['charttype'] != 'Funnel' )
				{
					if ( !$this->_checkColor( $this->data['yaxis'][$ci]['color'] ) ) return false;
					if ( !isset( $this->color[ $ci ] ) ) $this->color[ $ci ] = explode( ",", $this->_getSliceColor( $this->data['yaxis'][$ci]['color'] ) );
				}
				else
				{
					if ( !isset( $this->color[ $ci ] ) ) $this->color[ $ci ] = explode( ",", $this->_getSliceColor() );
				}

	            $color = imagecolorallocate( $this->image, $this->color[$ci][0], $this->color[$ci][1], $this->color[$ci][2] );
	            imagefilledrectangle( $this->image, $legendx+5, $ypos+7  , $legendx+15 , $ypos+17, $color );

	            if ( $this->use_ttf )
	            {
	                $txty = $ypos + 5 + $maxtxty/2;
	                imagettftext($this->image, "10", 0, $legendx+20+1, $txty+5+1, $shadowcolor, $this->options['font'], $name );
	                imagettftext($this->image, "10", 0, $legendx+20  , $txty+5  , $textcolor  , $this->options['font'], $name );
	            }
	            else
	            {
	                ImageString( $this->image, $this->fontsize, $legendx+20 , $ypos+7, $name, $this->black);
	            }

	            $ypos = $ypos + $maxtxty + 5;

	            imagecolordeallocate( $this->image, $color );
	            $ci ++;
	        }

	        imagerectangle( $this->image, $legendx, $legendy, $this->grapharea['x1']-1, $ypos + 5, $textcolor );

			$this->grapharea['x1'] = $legendx - 5;
		}

		return true;
	}


    /**
	* Draw X and Y axes
	*
	* @access	protected
	* @return	void
	*/
	protected function _drawAxes()
	{
        //-----------------------------------------
        // Allocate text and shadow cols
        //-----------------------------------------
        $textcolor   = imagecolorallocate( $this->image, hexdec(substr($this->options['titlecolor'],1,2)), hexdec(substr($this->options['titlecolor'],3,2)), hexdec(substr($this->options['titlecolor'],5,2)));
        $shadowcolor = imagecolorallocate( $this->image, hexdec(substr($this->options['titleshadow'],1,2)), hexdec(substr($this->options['titleshadow'],3,2)), hexdec(substr($this->options['titleshadow'],5,2)));

        //-----------------------------------------
		// Do we have axes titles?
        //-----------------------------------------
		if ( $this->options['xaxistitle'] )
		{
            if ( $this->use_ttf )
            {
				$txtsize = imagettfbbox(10, 0, $this->options['font'], $this->options['xaxistitle'] );
				$textx = round( ( $this->grapharea['x1']-$this->grapharea['x0'] ) / 2, 0 ) - round( ( $txtsize[4]-$txtsize[0] ) / 2, 0 );
				$texty = $this->grapharea['y1'];
	            imagettftext($this->image, 10, 0, $textx+1, $texty+1, $shadowcolor, $this->options['font'], $this->options['xaxistitle'] );
	            imagettftext($this->image, 10, 0, $textx  , $texty  , $textcolor  , $this->options['font'], $this->options['xaxistitle'] );
				$this->grapharea['y1'] = $this->grapharea['y1'] - ( $txtsize[1] - $txtsize[5] ) - 5;
			}
			else
			{
                $txtwidth = imagefontwidth( $this->fontsize ) * strlen( $this->options['xaxistitle'] );
				$textx = round( ( $this->grapharea['x1']-$this->grapharea['x0'] ) / 2, 0 ) - round( $txtwidth / 2, 0 );
				$texty = $this->grapharea['y1'] - imagefontheight($this->fontsize);
                imagestring( $this->image, $this->fontsize, $textx+1, $texty+1, $this->options['xaxistitle'], $shadowcolor );
                imagestring( $this->image, $this->fontsize, $textx, $texty, $this->options['xaxistitle'], $textcolor   );
				$this->grapharea['y1'] = $this->grapharea['y1'] - imagefontheight($this->fontsize) - 5;
			}
		}

		if ( $this->options['yaxistitle'] )
		{
            if ( $this->use_ttf )
            {
				$txtsize = imagettfbbox(10, 0, $this->options['font'], $this->options['yaxistitle'] );
				$textx = $this->grapharea['x0'] + ( $txtsize[1] - $txtsize[5] );
				$texty = round( ( $this->grapharea['y1'] - $this->grapharea['y0'] ) / 2, 0 ) + ( $txtsize[4] - $txtsize[0] );
	            imagettftext($this->image, 10, 90, $textx+1, $texty+1, $shadowcolor, $this->options['font'], $this->options['yaxistitle'] );
	            imagettftext($this->image, 10, 90, $textx  , $texty  , $textcolor  , $this->options['font'], $this->options['yaxistitle'] );
				$this->grapharea['x0'] = $textx + 5;
			}
			else
			{
                $txtheight = imagefontwidth( $this->fontsize ) * strlen( $this->options['yaxistitle'] );
				$textx = $this->grapharea['x0'];
				$texty = round( ( $this->grapharea['y1'] - $this->grapharea['y0'] ) / 2, 0 ) + round( $txtheight / 2, 0 );
                imagestringup( $this->image, $this->fontsize, $textx+1, $texty+1, $this->options['yaxistitle'], $shadowcolor );
                imagestringup( $this->image, $this->fontsize, $textx, $texty, $this->options['yaxistitle'], $textcolor );
				$this->grapharea['x0'] = $this->grapharea['x0'] + imagefontheight($this->fontsize) + 5;
			}
		}

        //-----------------------------------------
		// Determine height of the x-axis
        //-----------------------------------------
		$xaxisheight = 0;
		if ( isset( $this->x_axis['type'] ) )
		{
			if ( $this->x_axis['type'] == 'numeric' )
			{
	            if ( $this->use_ttf )
	            {
		            $txtsize     = imagettfbbox(10, 0, $this->options['font'], $this->x_axis['max'] );
		            $xaxisheight = $txtsize[1]-$txtsize[5];
				}
				else
				{
		            $xaxisheight = imagefontheight($this->fontsize);
				}
			}
			else
			{
				$xaxisheight = 0;
				foreach( $this->x_axis['labels'] as $label )
				{
		            if ( $this->use_ttf )
		            {
		                $textsize = imagettfbbox(10, 45, $this->options['font'], $label );
		                if ( ($textsize[1] - $textsize[5]) > $xaxisheight ) $xaxisheight = $textsize[1] - $textsize[5];
		            }
		            else
		            {
		                $textsize = imagefontwidth( $this->fontsize ) * strlen( $label );
		                if ( $textsize > $xaxisheight ) $xaxisheight = $textsize;
		            }
		        }
		    }
			$xaxisheight += 8;
			$this->grapharea['y1'] -= $xaxisheight;
		}

        //-----------------------------------------
		// Determine width of the y-axis
        //-----------------------------------------
		$yaxiswidth = 0;
		if ( isset( $this->y_axis['type'] ) )
		{
			if ( $this->y_axis['type'] == 'numeric' )
			{
	            if ( $this->use_ttf )
	            {
		            $txtsize    = imagettfbbox(10, 0, $this->options['font'], $this->y_axis['max'] );
		            $yaxiswidth = $txtsize[2]-$txtsize[0];
				}
				else
				{
		            $yaxiswidth = imagefontwidth($this->fontsize) * strlen( $this->y_axis['max'] );
				}
			}
			else
			{
				$yaxiswidth = 0;
				foreach( $this->y_axis['labels'] as $label )
				{
		            if ( $this->use_ttf )
		            {
		                $textsize = imagettfbbox(10, 0, $this->options['font'], $label );
		                if ( ($textsize[2] - $textsize[0]) > $yaxiswidth ) $yaxiswidth = $textsize[2] - $textsize[0];
		            }
		            else
		            {
		                $textsize = imagefontwidth( $this->fontsize ) * strlen( $label );
		                if ( $textsize > $yaxiswidth ) $yaxiswidth = $textsize;
		            }
		        }
		    }
			$yaxiswidth += 8;
			$this->grapharea['x0'] += $yaxiswidth;
		}

        //-----------------------------------------
		// Determine 3D effect size (different for different graph types)
        //-----------------------------------------
		$effect3DSize = 0;
		$numdepth = 1;
		if ( $this->options['style3D'] == 1 )
		{
			if ( $this->options['charttype'] == 'Line' )
			{
				$effect3DSize = 20;
				$numdepth = count( $this->data['yaxis'] );
			}
			elseif ( $this->options['charttype'] == 'Area' )
			{
				$effect3DSize = 20;
			}
			elseif ( $this->options['charttype'] == 'HBar' )
			{
				//calculate 1 bar height
				$numbars = count( $this->data['yaxis'][0]['data'] );
				$stepsize = round( ( ( $this->grapharea['y1'] - $this->grapharea['y0'] - 20 ) / $numbars ) * 0.9, 0 );
				$barheight = round( $stepsize / count( $this->data['yaxis'] ), 0 );

				$effect3DSize = round( $barheight / 2, 0 ) < 20 ? round( $barheight / 2, 0 ) : 20;
			}
			elseif ( $this->options['charttype'] == 'Bar' )
			{
				//calculate 1 bar width
				$numbars = count( $this->data['yaxis'][0]['data'] );
				$stepsize = round( ( ( $this->grapharea['x1'] - $this->grapharea['x0'] - 20 ) / $numbars ) * 0.9, 0 );
				$barwidth = round( $stepsize / count( $this->data['yaxis'] ), 0 );

				$effect3DSize = round( $barwidth / 2, 0 ) < 20 ? round( $barwidth / 2, 0 ) : 20;
			}
			elseif ( $this->options['charttype'] == 'Funnel' )
			{
				$effect3DSize = 40;
			}
		}

		$this->grapharea['x1'] -= $effect3DSize*$numdepth;
		$this->grapharea['y0'] += $effect3DSize*$numdepth;

		$depth = $numdepth * $effect3DSize;

		//-----------------------------------------
		// Draw x-axis ticks & labels
        //-----------------------------------------
		if ( isset( $this->x_axis['type'] ) )
		{
			if ( $this->y_axis['type'] == 'labels' )
			{
				$numlabels = count( $this->y_axis['labels'] );
				$stepheight = floor( ( ( $this->grapharea['y1'] - $this->grapharea['y0'] ) / $numlabels ) );
				$this->grapharea['y0'] = $this->grapharea['y1'] - $numlabels * $stepheight;
			}

			if ( $this->x_axis['type'] == 'numeric' )
			{
				// 0 - Labels
	            if ( $this->use_ttf )
	            {
		            $txtsize    = imagettfbbox(10, 0, $this->options['font'], '0' );
		            $textx = $this->grapharea['x0'] - round( ( $txtsize[2]-$txtsize[0] ) / 2, 0);
		            $texty = $txtsize[1]-$txtsize[5] + $this->grapharea['y1'] + 8;
		            imagettftext($this->image, 10, 0, $textx+1, $texty+1, $shadowcolor, $this->options['font'], '0' );
		            imagettftext($this->image, 10, 0, $textx  , $texty  , $textcolor  , $this->options['font'], '0' );
		        }
		        else
		        {
	                $txtwidth = imagefontwidth( $this->fontsize ) * strlen( '0' );
		            $textx = $this->grapharea['x0'] - round( $txtwidth / 2, 0);
		            $texty = $this->grapharea['y1'] + 8;
	                imagestring( $this->image, $this->fontsize, $textx+1, $texty+1, '0', $shadowcolor );
	                imagestring( $this->image, $this->fontsize, $textx, $texty, '0', $textcolor   );
		        }

				$numticks = $this->options['numticks'] > $this->x_axis['max'] ? floor($this->x_axis['max']) : $this->options['numticks'];
				$step = floor( $this->x_axis['max'] / $numticks );
				$steps = ceil( $this->x_axis['max'] / $step );
				$stepwidth = ( $this->grapharea['x1'] - $this->grapharea['x0'] ) / $steps;
				for ( $i = 1; $i <= $steps; $i++ )
				{
					$value = $i*$step;
					$x = round( ( $value / $step ) * $stepwidth + $this->grapharea['x0'], 0);

					// Ticks
					imageline( $this->image, $x, $this->grapharea['y1'], $x, $this->grapharea['y1']+5, $this->black );
					if ( $depth )
					{
						imageline( $this->image, $x, $this->grapharea['y1'], $x+$depth, $this->grapharea['y1']-$depth, $this->black );
					}
					if ( $this->options['showgridlinesx'] )
					{
						imageline( $this->image, $x+$depth, $this->grapharea['y1']-$depth, $x+$depth, $this->grapharea['y0']-$depth, $this->black );
					}

					// Labels
		            if ( $this->use_ttf )
		            {
			            $txtsize    = imagettfbbox(10, 0, $this->options['font'], $value );
			            $textx = $x - round( ( $txtsize[2]-$txtsize[0] ) / 2, 0);
			            $texty = $txtsize[1]-$txtsize[5] + $this->grapharea['y1'] + 8;
			            imagettftext($this->image, 10, 0, $textx+1, $texty+1, $shadowcolor, $this->options['font'], $value );
			            imagettftext($this->image, 10, 0, $textx  , $texty  , $textcolor  , $this->options['font'], $value );
			        }
			        else
			        {
		                $txtwidth = imagefontwidth( $this->fontsize ) * strlen( $value );
			            $textx = $x - round( $txtwidth / 2, 0);
			            $texty = $this->grapharea['y1'] + 8;
		                imagestring( $this->image, $this->fontsize, $textx+1, $texty+1, $value, $shadowcolor );
		                imagestring( $this->image, $this->fontsize, $textx, $texty, $value, $textcolor   );
			        }
				}
			}
			elseif ( $this->x_axis['type'] == 'ticklabels' )
			{
				$numlabels = count( $this->x_axis['labels'] );
				$stepwidth = floor( ( ( $this->grapharea['x1'] - $this->grapharea['x0'] ) / ($numlabels - 1) ) );
				$this->grapharea['x1'] = $this->grapharea['x0'] + ($numlabels - 1) * $stepwidth;

				for ( $i = 0; $i < $numlabels; $i++ )
				{
					$label = $this->x_axis['labels'][$i];
					$x = $i * $stepwidth + $this->grapharea['x0'];

					// Ticks
					imageline( $this->image, $x, $this->grapharea['y1'], $x, $this->grapharea['y1']+5, $this->black );
					if ( $depth )
					{
						imageline( $this->image, $x, $this->grapharea['y1'], $x+$depth, $this->grapharea['y1']-$depth, $this->black );
					}
					if ( $this->options['showgridlinesx'] )
					{
						imageline( $this->image, $x+$depth, $this->grapharea['y1']-$depth, $x+$depth, $this->grapharea['y0']-$depth, $this->black );
					}

					// Labels
		            if ( $this->use_ttf )
		            {
			            $txtsize    = imagettfbbox(10, 45, $this->options['font'], $label );
			            $textx = $x - ( $txtsize[4] - $txtsize[0] );
			            $texty = $txtsize[1]-$txtsize[5] + $this->grapharea['y1'] + 8;
			            imagettftext($this->image, 10, 45, $textx+1, $texty+1, $shadowcolor, $this->options['font'], $label );
			            imagettftext($this->image, 10, 45, $textx  , $texty  , $textcolor  , $this->options['font'], $label );
			        }
			        else
			        {
		                $textx = $x - round( imagefontheight($this->fontsize) / 2, 0 );
		                $texty       = $this->grapharea['y1'] + 8 + imagefontwidth($this->fontsize)*strlen( $label );
		                imagestringup($this->image, $this->fontsize, $textx+1, $texty+1, $label , $shadowcolor);
		                imagestringup($this->image, $this->fontsize, $textx, $texty, $label , $this->black);
			        }
				}
			}
			else
			{
				$numlabels = count( $this->x_axis['labels'] );
				$stepwidth = floor( ( ( $this->grapharea['x1'] - $this->grapharea['x0'] ) / $numlabels ) );
				$this->grapharea['x1'] = $this->grapharea['x0'] + $numlabels * $stepwidth;

				for ( $i = 0; $i < $numlabels; $i++ )
				{
					$label = $this->x_axis['labels'][$i];
					$x = ($i + 1) * $stepwidth + $this->grapharea['x0'];

					// Ticks
					imageline( $this->image, $x, $this->grapharea['y1'], $x, $this->grapharea['y1']+5, $this->black );
					if ( $depth )
					{
						imageline( $this->image, $x, $this->grapharea['y1'], $x+$depth, $this->grapharea['y1']-$depth, $this->black );
					}
					if ( $this->options['showgridlinesx'] )
					{
						imageline( $this->image, $x+$depth, $this->grapharea['y1']-$depth, $x+$depth, $this->grapharea['y0']-$depth, $this->black );
					}

					// Labels
		            if ( $this->use_ttf )
		            {
			            $txtsize    = imagettfbbox(10, 45, $this->options['font'], $label );
			            $textx = $x - round( $stepwidth / 2 , 0 ) - ( $txtsize[4] - $txtsize[0] );
			            $texty = $txtsize[1]-$txtsize[5] + $this->grapharea['y1'] + 8;
			            imagettftext($this->image, 10, 45, $textx+1, $texty+1, $shadowcolor, $this->options['font'], $label );
			            imagettftext($this->image, 10, 45, $textx  , $texty  , $textcolor  , $this->options['font'], $label );
			        }
			        else
			        {
		                $textx = $x - round( $stepwidth / 2 , 0 ) - round( imagefontheight($this->fontsize) / 2, 0 );
		                $texty       = $this->grapharea['y1'] + 8 + imagefontwidth($this->fontsize)*strlen( $label );
		                imagestringup($this->image, $this->fontsize, $textx+1, $texty+1, $label , $shadowcolor);
		                imagestringup($this->image, $this->fontsize, $textx, $texty, $label , $this->black);
			        }
				}
			}
		}

        //-----------------------------------------
		// Draw y-axis ticks & labels
        //-----------------------------------------
		if ( isset ( $this->y_axis['type'] ) )
		{
			if ( $this->y_axis['type'] == 'numeric' )
			{
				// 0 - Labels
	            if ( $this->use_ttf )
	            {
		            $txtsize    = imagettfbbox(10, 0, $this->options['font'], '0' );
		            $textx = $this->grapharea['x0'] - ( $txtsize[2]-$txtsize[0] ) - 8;
		            $texty = $this->grapharea['y1'] + round( ( $txtsize[1] - $txtsize[5] ) / 2, 0 );
		            imagettftext($this->image, 10, 0, $textx+1, $texty+1, $shadowcolor, $this->options['font'], '0' );
		            imagettftext($this->image, 10, 0, $textx  , $texty  , $textcolor  , $this->options['font'], '0' );
		        }
		        else
		        {
	                $txtwidth = imagefontwidth( $this->fontsize ) * strlen( '0' );
		            $textx = $this->grapharea['x0'] - $txtwidth - 8;
		            $texty = $this->grapharea['y1'] - round ( imagefontheight( $this->fontsize ) / 2, 0);
	                imagestring( $this->image, $this->fontsize, $textx+1, $texty+1, '0', $shadowcolor );
	                imagestring( $this->image, $this->fontsize, $textx, $texty, '0', $textcolor   );
		        }

				$numticks = $this->options['numticks'] > $this->y_axis['max'] ? floor($this->y_axis['max']) : $this->options['numticks'];
				$step = floor( $this->y_axis['max'] / $numticks );
				$steps = ceil( $this->y_axis['max'] / $step );
				$stepheight = ( $this->grapharea['y1'] - $this->grapharea['y0'] ) / $steps;
				for ( $i = 1; $i <= $steps; $i++ )
				{
					$value = $i*$step;
					$y = round( $this->grapharea['y1'] - ( $value / $step ) * $stepheight, 0 );

					// Ticks
					imageline( $this->image, $this->grapharea['x0'], $y, $this->grapharea['x0']-5, $y, $this->black );
					if ( $depth )
					{
						imageline( $this->image, $this->grapharea['x0'], $y, $this->grapharea['x0']+$depth, $y-$depth, $this->black );
					}
					if ( $this->options['showgridlinesy'] )
					{
						imageline( $this->image, $this->grapharea['x0']+$depth, $y-$depth, $this->grapharea['x1']+$depth, $y-$depth, $this->black );
					}

					// Labels
		            if ( $this->use_ttf )
		            {
			            $txtsize    = imagettfbbox(10, 0, $this->options['font'], $value );
			            $textx = $this->grapharea['x0'] - ( $txtsize[2]-$txtsize[0] ) - 8;
			            $texty = $y + round( ( $txtsize[1] - $txtsize[5] ) / 2, 0 );
			            imagettftext($this->image, 10, 0, $textx+1, $texty+1, $shadowcolor, $this->options['font'], $value );
			            imagettftext($this->image, 10, 0, $textx  , $texty  , $textcolor  , $this->options['font'], $value );
			        }
			        else
			        {
		                $txtwidth = imagefontwidth( $this->fontsize ) * strlen( $value );
			            $textx = $this->grapharea['x0'] - $txtwidth - 8;
			            $texty = $y - round ( imagefontheight( $this->fontsize ) / 2, 0);
		                imagestring( $this->image, $this->fontsize, $textx+1, $texty+1, $value, $shadowcolor );
		                imagestring( $this->image, $this->fontsize, $textx, $texty, $value, $textcolor   );
			        }
				}
			}
			else
			{
				$numlabels = count( $this->y_axis['labels'] );
				$stepheight = floor( ( ( $this->grapharea['y1'] - $this->grapharea['y0'] ) / $numlabels ) );
				$this->grapharea['y0'] = $this->grapharea['y1'] - $numlabels * $stepheight;

				for ( $i = 0; $i < $numlabels; $i++ )
				{
					$label = $this->y_axis['labels'][$i];
					$y = $this->grapharea['y1'] - ($i + 1) * $stepheight;

					// Ticks
					imageline( $this->image, $this->grapharea['x0'], $y, $this->grapharea['x0']-5, $y, $this->black );
					if ( $depth )
					{
						imageline( $this->image, $this->grapharea['x0'], $y, $this->grapharea['x0']+$depth, $y-$depth, $this->black );
					}
					if ( $this->options['showgridlinesy'] )
					{
						imageline( $this->image, $this->grapharea['x0']+$depth, $y-$depth, $this->grapharea['x1']+$depth, $y-$depth, $this->black );
					}

					// Labels
		            if ( $this->use_ttf )
		            {
			            $txtsize    = imagettfbbox(10, 0, $this->options['font'], $label );
			            $textx = $this->grapharea['x0'] - ( $txtsize[4] - $txtsize[0] ) - 8;
			            $texty = $y + round( $stepheight / 2 , 0 );
			            imagettftext($this->image, 10, 0, $textx+1, $texty+1, $shadowcolor, $this->options['font'], $label );
			            imagettftext($this->image, 10, 0, $textx  , $texty  , $textcolor  , $this->options['font'], $label );
			        }
			        else
			        {
			            $texty = $y + round( $stepheight / 2 , 0 ) - round( imagefontheight($this->fontsize) / 2, 0 );
		                $textx = $this->grapharea['x0'] - imagefontwidth($this->fontsize)*strlen( $label ) - 8;
		                imagestring($this->image, $this->fontsize, $textx+1, $texty+1, $label , $shadowcolor);
		                imagestring($this->image, $this->fontsize, $textx, $texty, $label , $this->black);
			        }
				}
			}
		}

        //-----------------------------------------
		// Draw the lines
        //-----------------------------------------
        imageline( $this->image, $this->grapharea['x0'] - 5, $this->grapharea['y1'], $this->grapharea['x1'], $this->grapharea['y1'], $this->black );
        imageline( $this->image, $this->grapharea['x0'], $this->grapharea['y0'], $this->grapharea['x0'], $this->grapharea['y1'] + 5, $this->black );

		if ( $effect3DSize )
		{
			for ( $i = 1; $i <= $numdepth; $i++ )
			{
				$depth = $effect3DSize * $i;
				imageline( $this->image, $this->grapharea['x0'], $this->grapharea['y1'], $this->grapharea['x0']+$depth, $this->grapharea['y1']-$depth, $this->black );
				imageline( $this->image, $this->grapharea['x0']+$depth, $this->grapharea['y1']-$depth, $this->grapharea['x0']+$depth, $this->grapharea['y0']-$depth, $this->black );
				imageline( $this->image, $this->grapharea['x0']+$depth, $this->grapharea['y0']-$depth, $this->grapharea['x0'], $this->grapharea['y0'], $this->black );
				imageline( $this->image, $this->grapharea['x0']+$depth, $this->grapharea['y1']-$depth, $this->grapharea['x1']+$depth, $this->grapharea['y1']-$depth, $this->black );
				imageline( $this->image, $this->grapharea['x1']+$depth, $this->grapharea['y1']-$depth, $this->grapharea['x1'], $this->grapharea['y1'], $this->black );
			}
		}
	}


    /**
	* Generate Pie Chart
	*
	* @access	protected
	* @return	void
	*/
    protected function _drawPie()
    {
        //-----------------------------------------
        // Draw Legend
        //-----------------------------------------
		$this->legend = $this->data['xaxis'];
		if ( !$this->_drawLegend() ) return false;

        //-----------------------------------------
        // Map data into PIE array
        //-----------------------------------------
        if ( is_array( $this->data['yaxis'][0]['data'] ) && count( $this->data['yaxis'][0]['data'] ) > 0 )
        {
            $total  = array_sum( $this->data['yaxis'][0]['data'] );
            $start  = 0;
            $i = 0;
            foreach ( $this->data['yaxis'][0]['data'] as $key => $value )
            {
                $pies[] = array( 'start' => $start>360?360:$start,
                                 'end'   => $start + round(($value / $total) * 360, 0 ) > 360 ? 360 : $start + round(($value / $total) * 360, 0 ) ,
                                 'perc'  => round(($value / $total)*100,1),
                                 'name'  => isset( $this->data['xaxis'][$i] ) ? $this->data['xaxis'][$i] : $i
                               );

                $start = $start + round(($value / $total) * 360, 0 );

				if ( !isset( $this->color[ $i ] ) ) $this->color[ $i ] = explode( ",", $this->_getSliceColor() );

                $i++;
            }
            $pies[$i-1]['end'] = 360;

            //-----------------------------------------
            // Slice of pie, anyone?
            //-----------------------------------------

            $textcolor   = imagecolorallocate( $this->image, 0, 0, 0 );
            $shadowcolor = imagecolorallocate( $this->image, 170, 170, 170);
            $midx  = round( $this->grapharea['x1'] / 2, 0);
            $midy  = round( ( $this->options['height'] - $this->grapharea['y0'] ) / 2, 0) + $this->grapharea['y0'];

            if ( $this->options['style3D'] == 1 )
            {
	            $sizex = round( $this->grapharea['x1'] / 100 * 90, 0);
                $sizey = round( $this->grapharea['x1'] / 100 * 50, 0);

                //-----------------------------------------
                // Make the 3D effect
                //-----------------------------------------

                for ( $i = $midy+20; $i > $midy; $i-- )
                {
                    $ci = 0;

                    foreach ( $pies as $pie )
                    {
						if ( $pie['start'] > 180 or $pie['start'] == $pie['end'] )
                        {
	                        $ci++;
                            # Can't see shadow or nothing to draw, so don't bother
                            continue;
                        }


                        $shadowcolor = imagecolorallocate( $this->image, ($this->color[$ci][0]-50)<0?0:$this->color[$ci][0]-50, ($this->color[$ci][1]-50)<0?0:$this->color[$ci][1]-50, ($this->color[$ci][2]-50)<0?0:$this->color[$ci][2]-50 );
                        imagefilledarc($this->image, $midx, $i, $sizex, $sizey, $pie['start'], $pie['end'], $shadowcolor, IMG_ARC_PIE);
                        imagecolordeallocate( $this->image, $shadowcolor );
                        $ci++;
                    }
                }
            }
            else
            {
            	$maxsize = ( $this->grapharea['y1'] - $this->grapharea['y0'] < $this->grapharea['x1'] ) ? $this->grapharea['y1'] - $this->grapharea['y0'] : $this->grapharea['x1'];
	            $sizex = round($maxsize / 100 * 90, 0);
                $sizey = round($maxsize / 100 * 90, 0);
            }

            //-----------------------------------------
            // Slice
            //-----------------------------------------
            $ci = 0;

            foreach ( $pies as $pie )
            {
				if ( $pie['start'] == $pie['end'] )
                {
                    # Nothing to draw, so don't bother
	                $ci++;
                    continue;
                }
                $piecolor = imagecolorallocate( $this->image, $this->color[$ci][0], $this->color[$ci][1], $this->color[$ci][2] );
                imagefilledarc($this->image, $midx, $midy, $sizex, $sizey, $pie['start'], $pie['end'], $piecolor, IMG_ARC_PIE);
                imagecolordeallocate( $this->image, $piecolor );
                $ci++;
            }

            //-----------------------------------------
            // Data labels
            //-----------------------------------------
			if ( $this->options['showdatalabels'] )
			{
	            $textcolor  = ImageColorAllocate($this->image, hexdec(substr($this->options['textcolor'],1,2)), hexdec(substr($this->options['textcolor'],3,2)), hexdec(substr($this->options['textcolor'],5,2)));

	            $ci = 0;

	            foreach ( $pies as $key => $pie )
	            {
	                $textx = $midx + cos(deg2rad($pie['start']+($pie['end']-$pie['start'])/2))*($sizex/3);
	                $texty = $midy + sin(deg2rad($pie['start']+($pie['end']-$pie['start'])/2))*($sizey/3);

	                $shadowcolor = imagecolorallocate( $this->image, ($this->color[$ci][0]-50)<0?0:$this->color[$ci][0]-50, ($this->color[$ci][1]-50)<0?0:$this->color[$ci][1]-50, ($this->color[$ci][2]-50)<0?0:$this->color[$ci][2]-50 );

	                if ( $this->use_ttf )
	                {
	                    $txtsize     = imagettfbbox("10", 0, $this->options['font'], $pie['perc']."%" );
	                    $textx       = $textx - round(($txtsize[2]-$txtsize[0])/2,0);
	                    $texty       = $texty + round(($txtsize[3]-$txtsize[1])/2,0);

	                    imagettftext($this->image, "10", 0, $textx-1, $texty-1, $shadowcolor, $this->options['font'], $pie['perc']."%");
	                    imagettftext($this->image, "10", 0, $textx+1, $texty+1, $shadowcolor, $this->options['font'], $pie['perc']."%");
	                    imagettftext($this->image, "10", 0, $textx+2, $texty+2, $shadowcolor, $this->options['font'], $pie['perc']."%");
	                    imagettftext($this->image, "10", 0, $textx, $texty, $textcolor, $this->options['font'], $pie['perc']."%");
	                }
	                else
	                {
	                    $textx       = $textx - round(imagefontwidth($this->fontsize)*strlen($pie['perc']."%")/2,0);
	                    $texty       = $texty - round(imagefontheight($this->fontsize)/2,0);

	                    imagestring($this->image, $this->fontsize, $textx-1, $texty-1, $pie['perc']."%", $shadowcolor);
	                    imagestring($this->image, $this->fontsize, $textx+1, $texty+1, $pie['perc']."%", $shadowcolor);
	                    imagestring($this->image, $this->fontsize, $textx+2, $texty+2, $pie['perc']."%", $shadowcolor);
	                    imagestring($this->image, $this->fontsize, $textx, $texty, $pie['perc']."%", $textcolor);
	                }

	                imagecolordeallocate( $this->image, $shadowcolor );

	                $ci++;
				}
            }
		}

		return true;
    }


    /**
	* Generate Doughnut Chart
	*
	* @access	protected
	* @return	void
	*/
    protected function _drawDoughnut()
    {
        //-----------------------------------------
        // Draw Legend
        //-----------------------------------------
		$this->legend = $this->data['xaxis'];
		if ( !$this->_drawLegend() ) return false;

        //-----------------------------------------
        // Calculates display variables
        //-----------------------------------------
		$numrings = count( $this->data['yaxis'] );
        $textcolor   = imagecolorallocate( $this->image, 0, 0, 0 );
        $shadowcolor = imagecolorallocate( $this->image, 170, 170, 170);
        $bgcolor = imagecolorallocate($this->image, hexdec(substr($this->options['bgcolor'],1,2)), hexdec(substr($this->options['bgcolor'],3,2)), hexdec(substr($this->options['bgcolor'],5,2)));
        $midx  = round( $this->grapharea['x1'] / 2, 0);
        $midy  = round( ( $this->options['height'] - $this->grapharea['y0'] ) / 2, 0) + $this->grapharea['y0'];

        if ( $this->options['style3D'] == 1 )
        {
            $sizex = round( $this->grapharea['x1'] / 100 * 90, 0);
            $sizey = round( $this->grapharea['x1'] / 100 * 50, 0);
        }
        else
        {
        	$maxsize = ( $this->grapharea['y1'] - $this->grapharea['y0'] < $this->grapharea['x1'] ) ? $this->grapharea['y1'] - $this->grapharea['y0'] : $this->grapharea['x1'];
            $sizex = round($maxsize / 100 * 90, 0);
            $sizey = round($maxsize / 100 * 90, 0);
        }

		$holesizex = round( $sizex * 0.2, 0 );
		$holesizey = round( $sizey * 0.2, 0 );

		$ringsizex = ( $sizex - $holesizex ) / $numrings;
		$ringsizey = ( $sizey - $holesizey ) / $numrings;

        //-----------------------------------------
        // Lord of the Rings
        //-----------------------------------------
		for ( $ci = 0; $ci < $numrings; $ci++ )
		{
	        //-----------------------------------------
	        // Map data into PIE array
	        //-----------------------------------------
            $total  = array_sum( $this->data['yaxis'][$ci]['data'] );
            $start  = 0;
            $i = 0;
			$pies = array();
            foreach ( $this->data['yaxis'][$ci]['data'] as $key => $value )
            {
                $pies[] = array( 'start' => $start>360?360:$start,
                                 'end'   => $start + round(($value / $total) * 360, 0 ) > 360 ? 360 : $start + round(($value / $total) * 360, 0 ) ,
                                 'perc'  => round(($value / $total)*100,1),
                                 'name'  => isset( $this->data['xaxis'][$i] ) ? $this->data['xaxis'][$i] : $i
                               );

                $start = $start + round(($value / $total) * 360, 0 );

				if ( !isset( $this->color[ $i ] ) ) $this->color[ $i ] = explode( ",", $this->_getSliceColor() );

                $i++;
            }
            $pies[$i-1]['end'] = 360;

            //-----------------------------------------
            // Slice of pie, anyone?
            //-----------------------------------------
            if ( $this->options['style3D'] == 1 )
            {
				if ( $ci==0 )
				{
	                //-----------------------------------------
	                // Make the 3D effect
	                //-----------------------------------------

	                for ( $i = $midy+20; $i > $midy; $i-- )
	                {
	                    $pi = 0;

	                    foreach ( $pies as $pie )
	                    {
							if ( $pie['start'] > 180 or $pie['start'] == $pie['end'] )
	                        {
		                        $pi++;
	                            # Can't see shadow or nothing to draw, so don't bother
	                            continue;
	                        }


	                        $shadowcolor = imagecolorallocate( $this->image, ($this->color[$pi][0]-50)<0?0:$this->color[$pi][0]-50, ($this->color[$pi][1]-50)<0?0:$this->color[$pi][1]-50, ($this->color[$pi][2]-50)<0?0:$this->color[$pi][2]-50 );
	                        imagefilledarc($this->image, $midx, $i, $sizex-$ci*$ringsizex, $sizey-$ci*$ringsizey, $pie['start'], $pie['end'], $shadowcolor, IMG_ARC_PIE);
	                        imagecolordeallocate( $this->image, $shadowcolor );
	                        $pi++;
	                    }
	                }
	            }
            }

            //-----------------------------------------
            // Slice
            //-----------------------------------------
            $pi = 0;

            foreach ( $pies as $pie )
            {
				if ( $pie['start'] == $pie['end'] )
                {
                    # Nothing to draw, so don't bother
	                $pi++;
                    continue;
                }
                $piecolor = imagecolorallocate( $this->image, $this->color[$pi][0], $this->color[$pi][1], $this->color[$pi][2] );
                imagefilledarc($this->image, $midx, $midy, $sizex-$ci*$ringsizex, $sizey-$ci*$ringsizey, $pie['start'], $pie['end'], $piecolor, IMG_ARC_PIE);
                imagecolordeallocate( $this->image, $piecolor );

				if ( $ci > 0 )
				{
					# little dark line to distinguish between the series
	                $shadowcolor = imagecolorallocate( $this->image, ($this->color[$pi][0]-50)<0?0:$this->color[$pi][0]-50, ($this->color[$pi][1]-50)<0?0:$this->color[$pi][1]-50, ($this->color[$pi][2]-50)<0?0:$this->color[$pi][2]-50 );
	                imagearc($this->image, $midx, $midy, $sizex-$ci*$ringsizex, $sizey-$ci*$ringsizey, $pie['start'], $pie['end'], $shadowcolor );
	                imagecolordeallocate( $this->image, $shadowcolor );
	            }

                $pi++;
            }

            //-----------------------------------------
            // Data labels
            //-----------------------------------------
			if ( $this->options['showdatalabels'] )
			{
	            $textcolor  = ImageColorAllocate($this->image, hexdec(substr($this->options['textcolor'],1,2)), hexdec(substr($this->options['textcolor'],3,2)), hexdec(substr($this->options['textcolor'],5,2)));

	            $pi = 0;

	            foreach ( $pies as $key => $pie )
	            {
	                $textx = $midx + cos(deg2rad($pie['start']+($pie['end']-$pie['start'])/2))*($sizex/2-($ci+0.5)*$ringsizex/2);
	                $texty = $midy + sin(deg2rad($pie['start']+($pie['end']-$pie['start'])/2))*($sizey/2-($ci+0.5)*$ringsizey/2);

	                $textshadowcolor = imagecolorallocate( $this->image, ($this->color[$pi][0]-50)<0?0:$this->color[$pi][0]-50, ($this->color[$pi][1]-50)<0?0:$this->color[$pi][1]-50, ($this->color[$pi][2]-50)<0?0:$this->color[$pi][2]-50 );

	                if ( $this->use_ttf )
	                {
	                    $txtsize     = imagettfbbox("10", 0, $this->options['font'], $pie['perc']."%" );
	                    $textx       = $textx - round(($txtsize[2]-$txtsize[0])/2,0);
	                    $texty       = $texty + round(($txtsize[3]-$txtsize[1])/2,0);

	                    imagettftext($this->image, "10", 0, $textx-1, $texty-1, $textshadowcolor, $this->options['font'], $pie['perc']."%");
	                    imagettftext($this->image, "10", 0, $textx+1, $texty+1, $textshadowcolor, $this->options['font'], $pie['perc']."%");
	                    imagettftext($this->image, "10", 0, $textx+2, $texty+2, $textshadowcolor, $this->options['font'], $pie['perc']."%");
	                    imagettftext($this->image, "10", 0, $textx, $texty, $textcolor, $this->options['font'], $pie['perc']."%");
	                }
	                else
	                {
	                    $textx       = $textx - round(imagefontwidth($this->fontsize)*strlen($pie['perc']."%")/2,0);
	                    $texty       = $texty - round(imagefontheight($this->fontsize)/2,0);

	                    imagestring($this->image, $this->fontsize, $textx-1, $texty-1, $pie['perc']."%", $textshadowcolor);
	                    imagestring($this->image, $this->fontsize, $textx+1, $texty+1, $pie['perc']."%", $textshadowcolor);
	                    imagestring($this->image, $this->fontsize, $textx+2, $texty+2, $pie['perc']."%", $textshadowcolor);
	                    imagestring($this->image, $this->fontsize, $textx, $texty, $pie['perc']."%", $textcolor);
	                }

	                imagecolordeallocate( $this->image, $textshadowcolor );

	                $pi++;
				}
            }
            imagecolordeallocate( $this->image, $textcolor );
		}

        //-----------------------------------------
        // The hole
        //-----------------------------------------
        if ( $this->options['style3D'] == 1 )
        {
            //-----------------------------------------
            // Make the 3D effect for the hole
            //-----------------------------------------
	        $hole = imagecreatetruecolor( $holesizex, $holesizey );
			$holemidx = round( $holesizex / 2, 0 );
			$holemidy = round( $holesizey / 2, 0 );

	        if ( function_exists('imageantialias') )
	        {
	            @imageantialias( $hole, TRUE );
	        }

	        $bgcolor = imagecolorallocate($hole, hexdec(substr($this->options['bgcolor'],1,2)), hexdec(substr($this->options['bgcolor'],3,2)), hexdec(substr($this->options['bgcolor'],5,2)));
			$black = imagecolorallocate( $hole, 0, 0, 0 );

            for ( $i = 0; $i <= 20; $i++ )
            {
                $pi = 0;

                foreach ( $pies as $pie )
                {
					if ( $pie['end'] < 180 or $pie['start'] == $pie['end'] )
                    {
                        $pi++;
                        # Can't see shadow or nothing to draw, so don't bother
                        continue;
                    }
					if ( $pie['start'] < 180 ) $pie['start'] = 180;

                    $shadowcolor = imagecolorallocate( $hole, ($this->color[$pi][0]-50)<0?0:$this->color[$pi][0]-50, ($this->color[$pi][1]-50)<0?0:$this->color[$pi][1]-50, ($this->color[$pi][2]-50)<0?0:$this->color[$pi][2]-50 );
                    imagefilledarc($hole, $holemidx, $holemidy+$i, $holesizex, $holesizey, $pie['start'], $pie['end'], $shadowcolor, IMG_ARC_PIE );
                    imagecolordeallocate( $hole, $shadowcolor );
                    $pi++;
                }
            }
            imagefilledellipse($hole, $holemidx, $holemidy+21, $holesizex, $holesizey, $bgcolor );

			$maxlength = ceil( $holesizey / 2 );
			for ( $i = 1; $i <= $maxlength; $i++ )
			{
				imagearc( $hole, $holemidx, $holemidy+$i, $holesizex, $holesizey, 0, 180, $black );
				imagearc( $hole, $holemidx, $holemidy-$i, $holesizex, $holesizey, 180, 360, $black );
			}

			imagecolortransparent( $hole, $black );
			imagecopymerge( $this->image, $hole, $midx-$holemidx, $midy-$holemidy-1, 0, 0, $holesizex, $holesizey, 100 );
			imagedestroy( $hole );
        }
		else
		{
			// in 2D this is a breeze
            imagefilledellipse($this->image, $midx, $midy, $holesizex, $holesizey, $bgcolor );
		}

        //-----------------------------------------
        // Draw series labels
        //-----------------------------------------
        $textcolor   = imagecolorallocate( $this->image, 0, 0, 0 );
        $textshadowcolor = imagecolorallocate( $this->image, 170, 170, 170);
		$textx = floor( $midx + 0.8 * $sizex / 2 );
		$texty = $this->grapharea['y0'];
		for ( $ci = 0; $ci < $numrings; $ci++ )
		{
			// draw the label
			$label = $this->data['yaxis'][$ci]['name'];
            if ( $this->use_ttf )
            {
                $txtsize = imagettfbbox("10", 0, $this->options['font'], $label );
                $txtsizey   = $txtsize[1]-$txtsize[5];

                imagettftext($this->image, "10", 0, $textx+1, $texty+$txtsizey+1, $textshadowcolor, $this->options['font'], $label );
                imagettftext($this->image, "10", 0, $textx, $texty+$txtsizey, $textcolor, $this->options['font'], $label );
            }
            else
            {
				$txtsizey = imagefontheight($this->fontsize);

                imagestring($this->image, $this->fontsize, $textx+1, $texty+1, $label, $textshadowcolor);
                imagestring($this->image, $this->fontsize, $textx, $texty, $label, $textcolor);
            }

			// Draw the line to text
	        $x1 = $midx + cos(deg2rad(300))*($sizex/2-($ci+0.5)*$ringsizex/2);
	        $y1 = $midy + sin(deg2rad(300))*($sizey/2-($ci+0.5)*$ringsizey/2);
	        $x2 = $textx - 5;
	        $y2 = $texty + 0.5 * $txtsizey;
			imageline( $this->image, $x1, $y1, $x2, $y2, $this->black );

            $texty = $texty + $txtsizey + 5;
		}

        imagecolordeallocate( $this->image, $textshadowcolor );
        imagecolordeallocate( $this->image, $textcolor );

		return true;
    }


    /**
	* Generate Bar Chart
	*
	* @access	protected
	* @return	void
	*/
    protected function _drawBars()
    {
        //-----------------------------------------
        // Draw Legends and Axes
        //-----------------------------------------
		foreach ( $this->data['yaxis'] as $key => $series )
		{
			$this->legend[ $key ] = $series['name'];
		}
		if ( !$this->_drawLegend() ) return false;
		$this->x_axis = array( 'type'	=> 'labels',
							   'labels'	=> $this->data['xaxis']
							 );
		$this->y_axis = array( 'type'	=> 'numeric',
							   'min'	=> 0,
							   'max'	=> $this->_getMax( $this->data['yaxis'] )
							 );
		$this->_drawAxes();

        //-----------------------------------------
        // Allocate text and shadow cols
        //-----------------------------------------
        $textcolor   = imagecolorallocate( $this->image, hexdec(substr($this->options['titlecolor'],1,2)), hexdec(substr($this->options['titlecolor'],3,2)), hexdec(substr($this->options['titlecolor'],5,2)));
        $shadowcolor = imagecolorallocate( $this->image, hexdec(substr($this->options['titleshadow'],1,2)), hexdec(substr($this->options['titleshadow'],3,2)), hexdec(substr($this->options['titleshadow'],5,2)));

        //-----------------------------------------
		// Calculate bar display variables
        //-----------------------------------------
		$numybars = count( $this->data['yaxis'] );
        $numbars = count( $this->data['yaxis'][0]['data'] );
		$maxvalue = $this->_getMax( $this->data['yaxis'] );
		$stepwidth = floor( ( ( $this->grapharea['x1'] - $this->grapharea['x0'] ) / $numbars ) );
		$barwidth = floor( ( $stepwidth * 0.9 ) / $numybars );
		$ident = round ( ( $stepwidth - ( $numybars * $barwidth ) ) / 2, 0 ) + 1;
		$effect3DSize = round( $barwidth / 2, 0 ) < 20 ? round( $barwidth / 2, 0 ) : 20;

		$numticks = $this->options['numticks'] > $maxvalue ? floor($maxvalue) : $this->options['numticks'];
		$step = floor( $maxvalue / $numticks );
		$steps = ceil( $maxvalue / $step );
		$stepheight = ( $this->grapharea['y1'] - $this->grapharea['y0'] ) / $steps;

        //-----------------------------------------
        // Candybar?
        //-----------------------------------------
		for ( $i=0; $i<$numbars; $i++ )
		{
			for ( $ci=0; $ci<$numybars; $ci++ )
			{
				if ( !isset( $this->color[ $ci ] ) ) $this->color[ $ci ] = explode( ",", $this->_getSliceColor( $this->data['yaxis'][$ci]['color'] ) );

				$value = $this->data['yaxis'][$ci]['data'][$i];

	            //-----------------------------------------
	            // Find out the bar location and size
	            //-----------------------------------------
	            $x1 = $this->grapharea['x0'] + ($stepwidth * $i ) + ($ci * $barwidth ) + $ident;
	            $x2 = $x1 + $barwidth - 1;
	            $y1 = round( $this->grapharea['y1'] - $value * $stepheight/$step, 0);
	            $y2 = $this->grapharea['y1'];

	            if ( $this->options['style3D'] == 1 )
	            {
	                //-----------------------------------------
	                // Make the 3D effect
	                //-----------------------------------------
	                $shadowcolor = imagecolorallocate( $this->image, ($this->color[$ci][0]-50)<0?0:$this->color[$ci][0]-50, ($this->color[$ci][1]-50)<0?0:$this->color[$ci][1]-50, ($this->color[$ci][2]-50)<0?0:$this->color[$ci][2]-50 );
	                $shadowsize = ($x2-$x1)/2 > 20 ? 20 : Round(($x2-$x1)/2, 0);

	                for ( $j = $shadowsize-1; $j > 0; $j-- )
	                {
	                    imageline( $this->image, $x2 + $j, $y1 - $j, $x2 + $j, $y2 - $j, $shadowcolor );
	                }
	                imagecolordeallocate( $this->image, $shadowcolor );

	                $shadowcolor = imagecolorallocate( $this->image, ($this->color[$ci][0]-25)<0?0:$this->color[$ci][0]-25, ($this->color[$ci][1]-25)<0?0:$this->color[$ci][1]-25, ($this->color[$ci][2]-25)<0?0:$this->color[$ci][2]-25 );
	                for ( $j = $shadowsize; $j > 0; $j-- )
	                {
	                    imageline( $this->image, $x1 + $j, $y1 - $j, $x2 + $j, $y1 - $j, $shadowcolor );
	                }
	                imagecolordeallocate( $this->image, $shadowcolor );
	            }

	            //-----------------------------------------
	            // Bar
	            //-----------------------------------------
	            $barcolor = imagecolorallocate( $this->image, $this->color[$ci][0], $this->color[$ci][1], $this->color[$ci][2] );
	            imagefilledrectangle( $this->image, $x1, $y1, $x2, $y2-1, $barcolor );
	            imagecolordeallocate( $this->image, $barcolor );

	            //-----------------------------------------
	            // Datalabels
	            //-----------------------------------------
				if ( $this->options['showdatalabels'] )
				{
		            $textcolor  = ImageColorAllocate($this->image, hexdec(substr($this->options['textcolor'],1,2)), hexdec(substr($this->options['textcolor'],3,2)), hexdec(substr($this->options['textcolor'],5,2)));

		            $textx = $x1 + round( ($x2-$x1) /2, 0);
		            $texty = $y1 + round( ($y2-$y1) /2, 0);

		            if ( $this->use_ttf )
		            {
		                $txtsize     = imagettfbbox('10', 0, $this->options['font'], $value );
		                $textx       = $textx - round(($txtsize[2]-$txtsize[0])/2,0);
		                $texty       = $texty + round(($txtsize[1]-$txtsize[5])/2,0);
		                $texty         = ($texty > $this->grapharea['y1']-2) ? $this->grapharea['y1']-2 : $texty;

		                $shadowcolor = imagecolorallocate( $this->image, ($this->color[$ci][0]-50)<0?0:$this->color[$ci][0]-50, ($this->color[$ci][1]-50)<0?0:$this->color[$ci][1]-50, ($this->color[$ci][2]-50)<0?0:$this->color[$ci][2]-50 );
		                imagettftext($this->image, "10", 0, $textx-1, $texty-1, $shadowcolor, $this->options['font'], $value);
		                imagettftext($this->image, "10", 0, $textx+1, $texty+1, $shadowcolor, $this->options['font'], $value);
		                imagettftext($this->image, "10", 0, $textx+2, $texty+2, $shadowcolor, $this->options['font'], $value);
		                imagettftext($this->image, "10", 0, $textx, $texty, $textcolor, $this->options['font'], $value);

		                imagecolordeallocate( $this->image, $shadowcolor );
		            }
		            else
		            {
		                $textx       = $textx - round(imagefontwidth($this->fontsize)*strlen($value)/2,0);
		                $texty       = $texty - round(imagefontheight($this->fontsize)/2,0);
		                $texty         = ($texty > $texty-imagefontheight($this->fontsize)-2) ? $texty-imagefontheight($this->fontsize)-2 : $texty;

		                $shadowcolor = imagecolorallocate( $this->image, ($this->color[0][0]-50)<0?0:$this->color[0][0]-50, ($this->color[0][1]-50)<0?0:$this->color[0][1]-50, ($this->color[0][2]-50)<0?0:$this->color[0][2]-50 );
		                imagestring($this->image, $this->fontsize, $textx-1, $texty-1, $value, $shadowcolor);
		                imagestring($this->image, $this->fontsize, $textx+1, $texty+1, $value, $shadowcolor);
		                imagestring($this->image, $this->fontsize, $textx+2, $texty+2, $value, $shadowcolor);
		                imagestring($this->image, $this->fontsize, $textx, $texty, $value, $textcolor);

		                imagecolordeallocate( $this->image, $shadowcolor );
		            }
				}
	        }
		}

		return true;
    }


    /**
	* Generate Horizontal Bar Charts
	*
	* @access	protected
	* @return	void
	*/
    protected function _drawHorizontalBars()
    {
        //-----------------------------------------
        // Draw Legend & Axes
        //-----------------------------------------
		foreach ( $this->data['yaxis'] as $key => $series )
		{
			$this->legend[ $key ] = $series['name'];
		}
		if ( !$this->_drawLegend() ) return false;
		$this->x_axis = array( 'type'	=> 'numeric',
							   'min'	=> 0,
							   'max'	=> $this->_getMax( $this->data['yaxis'] )
							 );
		$this->y_axis = array( 'type'	=> 'labels',
							   'labels'	=> $this->data['xaxis']
							 );
		$this->_drawAxes();

        //-----------------------------------------
        // Allocate text and shadow cols
        //-----------------------------------------
        $textcolor   = imagecolorallocate( $this->image, hexdec(substr($this->options['titlecolor'],1,2)), hexdec(substr($this->options['titlecolor'],3,2)), hexdec(substr($this->options['titlecolor'],5,2)));
        $shadowcolor = imagecolorallocate( $this->image, hexdec(substr($this->options['titleshadow'],1,2)), hexdec(substr($this->options['titleshadow'],3,2)), hexdec(substr($this->options['titleshadow'],5,2)));

        //-----------------------------------------
		// Calculate bar display variables
        //-----------------------------------------
		$numybars = count( $this->data['yaxis'] );
        $numbars = count( $this->data['yaxis'][0]['data'] );
		$maxvalue = $this->_getMax( $this->data['yaxis'] );
		$stepheight = floor( ( ( $this->grapharea['y1'] - $this->grapharea['y0'] ) / $numbars ) );
		$barheight = floor( ( $stepheight * 0.9 ) / $numybars );
		$ident = round ( ( $stepheight - ( $numybars * $barheight ) ) / 2, 0 ) + 1;
		$effect3DSize = round( $barheight / 2, 0 ) < 20 ? round( $barheight / 2, 0 ) : 20;

		$numticks = $this->options['numticks'] > $maxvalue ? floor($maxvalue) : $this->options['numticks'];
		$step = floor( $maxvalue / $numticks );
		$steps = ceil( $maxvalue / $step );
		$stepwidth = ( $this->grapharea['x1'] - $this->grapharea['x0'] ) / $steps;

        //-----------------------------------------
        // Candybar?
        //-----------------------------------------
		for ( $i=0; $i<$numbars; $i++ )
		{
			for ( $ci=0; $ci<$numybars; $ci++ )
			{
                //-----------------------------------------
                // Get me a nice color will ya
                //-----------------------------------------
				if ( !isset( $this->color[ $ci ] ) ) $this->color[ $ci ] = explode( ",", $this->_getSliceColor( $this->data['yaxis'][$ci]['color'] ) );

				$value = $this->data['yaxis'][$ci]['data'][$i];

	            //-----------------------------------------
	            // Find out the bar location and size
	            //-----------------------------------------
                $x1 = $this->grapharea['x0'] + 1;
                $x2 = round( $this->grapharea['x0'] + $value * $stepwidth / $step, 0);
                $y1 = $this->grapharea['y1'] - ($stepheight * $i ) - ($ci * $barheight ) - $ident;
                $y2 = $y1 - $barheight + 1;

                //-----------------------------------------
                // Bar
                //-----------------------------------------
                $barcolor = imagecolorallocate( $this->image, $this->color[$ci][0], $this->color[$ci][1], $this->color[$ci][2] );
                imagefilledrectangle( $this->image, $x1, $y1, $x2, $y2, $barcolor );
                imagecolordeallocate( $this->image, $barcolor );

                if ( $this->options['style3D'] == 1 )
                {
                    //-----------------------------------------
                    // Make the 3D effect
                    //-----------------------------------------
                    $shadowcolor = imagecolorallocate( $this->image, ($this->color[$ci][0]-50)<0?0:$this->color[$ci][0]-50, ($this->color[$ci][1]-50)<0?0:$this->color[$ci][1]-50, ($this->color[$ci][2]-50)<0?0:$this->color[$ci][2]-50 );
                    $shadowsize = ($y1-$y2)/2 > 20 ? 20 : Round(($y1-$y2)/2, 0);
                    for ( $j = $shadowsize; $j > 0; $j-- )
                    {
                        imageline( $this->image, $x2 + $j, $y1 - $j + 1, $x2 + $j, $y2 - $j, $shadowcolor );
                    }
                    imagecolordeallocate( $this->image, $shadowcolor );

                    $shadowcolor = imagecolorallocate( $this->image, ($this->color[$ci][0]-25)<0?0:$this->color[$ci][0]-25, ($this->color[$ci][1]-25)<0?0:$this->color[$ci][1]-25, ($this->color[$ci][2]-25)<0?0:$this->color[$ci][2]-25 );
                    for ( $j = $shadowsize; $j > 0; $j-- )
                    {
                        imageline( $this->image, $x1 + $j, $y2 - $j, $x2 + $j, $y2 - $j, $shadowcolor );
                    }
                    imagecolordeallocate( $this->image, $shadowcolor );
                }

	            //-----------------------------------------
	            // Datalabels
	            //-----------------------------------------
				if ( $this->options['showdatalabels'] )
				{
		            $textcolor  = ImageColorAllocate($this->image, hexdec(substr($this->options['textcolor'],1,2)), hexdec(substr($this->options['textcolor'],3,2)), hexdec(substr($this->options['textcolor'],5,2)));

		            $textx = $x1 + round( ($x2-$x1) /2, 0);
		            $texty = $y1 + round( ($y2-$y1) /2, 0);

		            if ( $this->use_ttf )
		            {
		                $txtsize     = imagettfbbox('10', 0, $this->options['font'], $value );
		                $textx       = $textx - round(($txtsize[2]-$txtsize[0])/2,0);
						$textx		 = ( $textx < $this->grapharea['x0'] + 2 ) ? $this->grapharea['x0'] + 2 : $textx;
		                $texty       = $texty + round(($txtsize[1]-$txtsize[5])/2,0);

		                $shadowcolor = imagecolorallocate( $this->image, ($this->color[$ci][0]-50)<0?0:$this->color[$ci][0]-50, ($this->color[$ci][1]-50)<0?0:$this->color[$ci][1]-50, ($this->color[$ci][2]-50)<0?0:$this->color[$ci][2]-50 );
		                imagettftext($this->image, "10", 0, $textx-1, $texty-1, $shadowcolor, $this->options['font'], $value);
		                imagettftext($this->image, "10", 0, $textx+1, $texty+1, $shadowcolor, $this->options['font'], $value);
		                imagettftext($this->image, "10", 0, $textx+2, $texty+2, $shadowcolor, $this->options['font'], $value);
		                imagettftext($this->image, "10", 0, $textx, $texty, $textcolor, $this->options['font'], $value);

		                imagecolordeallocate( $this->image, $shadowcolor );
		            }
		            else
		            {
		                $textx       = $textx - round(imagefontwidth($this->fontsize)*strlen($value)/2,0);
		                $texty       = ($textx < $this->grapharea['x0']+2) ? $this->grapharea['x0']+2 : $textx;
		                $texty       = $texty - round(imagefontheight($this->fontsize)/2,0);

		                $shadowcolor = imagecolorallocate( $this->image, ($this->color[0][0]-50)<0?0:$this->color[0][0]-50, ($this->color[0][1]-50)<0?0:$this->color[0][1]-50, ($this->color[0][2]-50)<0?0:$this->color[0][2]-50 );
		                imagestring($this->image, $this->fontsize, $textx-1, $texty-1, $value, $shadowcolor);
		                imagestring($this->image, $this->fontsize, $textx+1, $texty+1, $value, $shadowcolor);
		                imagestring($this->image, $this->fontsize, $textx+2, $texty+2, $value, $shadowcolor);
		                imagestring($this->image, $this->fontsize, $textx, $texty, $value, $textcolor);

		                imagecolordeallocate( $this->image, $shadowcolor );
		            }
				}
            }
        }

		return true;
    }


    /**
	* Generate Line Chart
	*
	* @access	protected
	* @return	void
	*/
    protected function _drawLines()
    {
        //-----------------------------------------
        // Draw Legend and Axes
        //-----------------------------------------
		foreach ( $this->data['yaxis'] as $key => $series )
		{
			$this->legend[ $key ] = $series['name'];
		}
		if ( !$this->_drawLegend() ) return false;
		$this->x_axis = array( 'type'	=> 'labels',
							   'labels'	=> $this->data['xaxis']
							 );
		$this->y_axis = array( 'type'	=> 'numeric',
							   'min'	=> 0,
							   'max'	=> $this->_getMax( $this->data['yaxis'] )
							 );
		$this->_drawAxes();

        //-----------------------------------------
        // Allocate text and shadow cols
        //-----------------------------------------
        $textcolor   = imagecolorallocate( $this->image, hexdec(substr($this->options['titlecolor'],1,2)), hexdec(substr($this->options['titlecolor'],3,2)), hexdec(substr($this->options['titlecolor'],5,2)));
        $shadowcolor = imagecolorallocate( $this->image, hexdec(substr($this->options['titleshadow'],1,2)), hexdec(substr($this->options['titleshadow'],3,2)), hexdec(substr($this->options['titleshadow'],5,2)));

        //-----------------------------------------
		// Calculate bar display variables
        //-----------------------------------------
		$numybars = count( $this->data['yaxis'] );
        $numbars = count( $this->data['yaxis'][0]['data'] );
		$maxvalue = $this->_getMax( $this->data['yaxis'] );
		$stepwidth = floor( ( ( $this->grapharea['x1'] - $this->grapharea['x0'] ) / $numbars ) );

		$numticks = $this->options['numticks'] > $maxvalue ? floor($maxvalue) : $this->options['numticks'];
		$step = floor( $maxvalue / $numticks );
		$steps = ceil( $maxvalue / $step );
		$stepheight = ( $this->grapharea['y1'] - $this->grapharea['y0'] ) / $steps;

		$effect3DSize = 20;

        //-----------------------------------------
        // Lines
        //-----------------------------------------
		for ( $ci=0; $ci<$numybars; $ci++ )
		{
			for ( $i=0; $i<$numbars; $i++ )
			{
				// No need to draw a line on only the first value
				if ( $i > 0 )
				{
					if ( !isset( $this->color[ $ci ] ) ) $this->color[ $ci ] = explode( ",", $this->_getSliceColor( $this->data['yaxis'][$ci]['color'] ) );

					$value1 = $this->data['yaxis'][$ci]['data'][$i-1];
					$value2 = $this->data['yaxis'][$ci]['data'][$i];

		            //-----------------------------------------
		            // Find out the bar location and size
		            //-----------------------------------------
					$x1 = $this->grapharea['x0'] + round( $stepwidth * 0.50, 0 ) + ($i-1) * ( $stepwidth );
					$x2 = $this->grapharea['x0'] + round( $stepwidth * 0.50, 0 ) + ($i) * ( $stepwidth );
		            $y1 = round( $this->grapharea['y1'] - $value1 * $stepheight / $step, 0);
		            $y2 = round( $this->grapharea['y1'] - $value2 * $stepheight / $step, 0);

		            $linecolor = imagecolorallocate( $this->image, $this->color[$ci][0], $this->color[$ci][1], $this->color[$ci][2] );
	                $lineshadowcolor = imagecolorallocate( $this->image, ($this->color[$ci][0]-25)<0?0:$this->color[$ci][0]-25, ($this->color[$ci][1]-25)<0?0:$this->color[$ci][1]-25, ($this->color[$ci][2]-25)<0?0:$this->color[$ci][2]-25 );
	                $lineshadowcolor2 = imagecolorallocate( $this->image, ($this->color[$ci][0]-50)<0?0:$this->color[$ci][0]-50, ($this->color[$ci][1]-50)<0?0:$this->color[$ci][1]-50, ($this->color[$ci][2]-50)<0?0:$this->color[$ci][2]-50 );

		            if ( $this->options['style3D'] == 1 )
		            {
		                //-----------------------------------------
		                // Make the 3D effect
		                //-----------------------------------------

		                $shadowsize = 20;

						$x1 = $x1 + ($numybars-$ci-1) * $shadowsize;
						$x2 = $x2 + ($numybars-$ci-1) * $shadowsize;
						$y1 = $y1 - ($numybars-$ci-1) * $shadowsize;
						$y2 = $y2 - ($numybars-$ci-1) * $shadowsize;

						if ( ( $y1 - $y2 ) / ( $x2 - $x1 ) > 1 )
						{
							imagefilledpolygon( $this->image, array( $x1, $y1, $x2, $y2, $x2+$shadowsize, $y2-$shadowsize, $x1+$shadowsize, $y1-$shadowsize), 4, $lineshadowcolor2 );
						}
						else
						{
							imagefilledpolygon( $this->image, array( $x1, $y1, $x2, $y2, $x2+$shadowsize, $y2-$shadowsize, $x1+$shadowsize, $y1-$shadowsize), 4, $lineshadowcolor );
						}
		            }

		            //-----------------------------------------
		            // Line & Dots
		            //-----------------------------------------

		            imageline( $this->image, $x1, $y1, $x2, $y2, $linecolor );
		            if ( $this->options['style3D'] != 1 )
		            {
			            imageline( $this->image, $x1+1, $y1+1, $x2+1, $y2+1, $lineshadowcolor );
						imagefilledellipse( $this->image, $x1+1, $y1+1, 7, 7, $lineshadowcolor );
						imagefilledellipse( $this->image, $x1, $y1, 7, 7, $linecolor );
						if ( $i == $numbars - 1 )
						{
							imagefilledellipse( $this->image, $x2+1, $y2+1, 7, 7, $lineshadowcolor );
							imagefilledellipse( $this->image, $x2, $y2, 7, 7, $linecolor );
						}
					}

		            imagecolordeallocate( $this->image, $linecolor );
	                imagecolordeallocate( $this->image, $lineshadowcolor );
	                imagecolordeallocate( $this->image, $lineshadowcolor2 );
				}

	            //-----------------------------------------
	            // Datalabels
	            //-----------------------------------------
				if ( $this->options['showdatalabels'] )
				{
					// First value is not calculated yet
					if ( $i == 0 )
					{
						$value2 = $this->data['yaxis'][$ci]['data'][$i];
						$x2 = $this->grapharea['x0'] + round( $stepwidth * 0.50, 0 ) + ($i) * ( $stepwidth );
			            $y2 = round( $this->grapharea['y1'] - $value2 * $stepheight / $step, 0);
			            if ( $this->options['style3D'] == 1 )
			            {
							$x2 = $x2 + ($numybars-$ci-1) * 20;
							$y2 = $y2 - ($numybars-$ci-1) * 20;
						}
					}

		            $valuecolor  = ImageColorAllocate($this->image, hexdec(substr($this->options['textcolor'],1,2)), hexdec(substr($this->options['textcolor'],3,2)), hexdec(substr($this->options['textcolor'],5,2)));
	                $valshadecolor = imagecolorallocate( $this->image, ($this->color[$ci][0]-50)<0?0:$this->color[$ci][0]-50, ($this->color[$ci][1]-50)<0?0:$this->color[$ci][1]-50, ($this->color[$ci][2]-50)<0?0:$this->color[$ci][2]-50 );
		            if ( $this->use_ttf )
		            {
		                $txtsize     = imagettfbbox('10', 0, $this->options['font'], $value2 );
		                $textx       = $x2 - round(($txtsize[2]-$txtsize[0])/2,0);
		                $texty       = $y2 - 3;

		                imagettftext($this->image, "10", 0, $textx-1, $texty-1, $valshadecolor, $this->options['font'], $value2);
		                imagettftext($this->image, "10", 0, $textx+1, $texty+1, $valshadecolor, $this->options['font'], $value2);
		                imagettftext($this->image, "10", 0, $textx+2, $texty+2, $valshadecolor, $this->options['font'], $value2);
		                imagettftext($this->image, "10", 0, $textx, $texty, $valuecolor, $this->options['font'], $value2);
		            }
		            else
		            {
		                $textx       = $x2 - round(imagefontwidth($this->fontsize)*strlen($value2)/2,0);
		                $texty       = $y2 - imagefontheight($this->fontsize)-3;

		                imagestring($this->image, $this->fontsize, $textx-1, $texty-1, $value2, $valshadecolor );
		                imagestring($this->image, $this->fontsize, $textx+1, $texty+1, $value2, $valshadecolor );
		                imagestring($this->image, $this->fontsize, $textx+2, $texty+2, $value2, $valshadecolor );
		                imagestring($this->image, $this->fontsize, $textx, $texty, $value2, $valuecolor);
		            }
		            imagecolordeallocate( $this->image, $valuecolor );
		            imagecolordeallocate( $this->image, $valshadecolor );
		        }
	        }
		}

		return true;
    }


    /**
	* Generate Area Chart
	*
	* @access	protected
	* @return	void
	*/
    protected function _drawArea()
    {
        //-----------------------------------------
        // Draw Legend and Axes
        //-----------------------------------------
		foreach ( $this->data['yaxis'] as $key => $series )
		{
			$this->legend[ $key ] = $series['name'];
		}
		if ( !$this->_drawLegend() ) return false;
		$this->x_axis = array( 'type'	=> 'ticklabels',
							   'labels'	=> $this->data['xaxis']
							 );
		$maxarray = array();
		foreach ( $this->data['yaxis'] as $series )
		{
			foreach( $series['data'] as $id => $value )
			{
				if ( isset ( $maxarray[$id] ) )
				{
					$maxarray[$id] += $value;
				}
				else
				{
					$maxarray[$id] = $value;
				}
			}
		}

		$this->y_axis = array( 'type'	=> 'numeric',
							   'min'	=> 0,
							   'max'	=> $this->_getMax( $maxarray )
							 );
		$this->_drawAxes();

        //-----------------------------------------
        // Allocate text and shadow cols
        //-----------------------------------------
        $textcolor   = imagecolorallocate( $this->image, hexdec(substr($this->options['titlecolor'],1,2)), hexdec(substr($this->options['titlecolor'],3,2)), hexdec(substr($this->options['titlecolor'],5,2)));
        $shadowcolor = imagecolorallocate( $this->image, hexdec(substr($this->options['titleshadow'],1,2)), hexdec(substr($this->options['titleshadow'],3,2)), hexdec(substr($this->options['titleshadow'],5,2)));

        //-----------------------------------------
		// Calculate area display variables
        //-----------------------------------------
		$numareas = count( $this->data['yaxis'] );
        $numbars = count( $this->data['yaxis'][0]['data'] );
		$maxvalue = $this->_getMax( $maxarray );

		$stepwidth = floor( ( ( $this->grapharea['x1'] - $this->grapharea['x0'] ) / ( $numbars - 1 ) ) );

		$numticks = $this->options['numticks'] > $maxvalue ? floor($maxvalue) : $this->options['numticks'];
		$step = floor( $maxvalue / $numticks );
		$steps = ceil( $maxvalue / $step );
		$stepheight = ( $this->grapharea['y1'] - $this->grapharea['y0'] ) / $steps;

		$effect3DSize = 20;

        //-----------------------------------------
        // Areas
        //-----------------------------------------
		for ( $ci=0; $ci<$numareas; $ci++ )
		{
			for ( $i=0; $i<$numbars; $i++ )
			{
				// Calculate the positions of the area
				$value = $this->data['yaxis'][$ci]['data'][$i];

				$area[$ci][$i*2] = $this->grapharea['x0'] + ($i * $stepwidth);
				if ( isset( $area[$ci-1][$i*2+1] ) )
				{
		            $area[$ci][$i*2+1] = round( $area[$ci-1][$i*2+1] - $value * $stepheight/$step, 0);
				}
				else
				{
		            $area[$ci][$i*2+1] = round( $this->grapharea['y1'] - $value * $stepheight/$step, 0);
				}
			}
			// Make the lower positions
			if ( isset( $area[$ci-1] ) )
			{
				for ( $i=$numbars-1; $i>=0; $i-- )
				{
					$area[$ci][] = $area[$ci-1][$i*2];
					$area[$ci][] = $area[$ci-1][$i*2+1];
				}
			}
			else
			{
				$area[$ci][] = $area[$ci][($numbars-1)*2];
				$area[$ci][] = $this->grapharea['y1'];
				$area[$ci][] = $area[$ci][0];
				$area[$ci][] = $this->grapharea['y1'];
			}

            if ( $this->options['style3D'] == 1 )
            {
                //-----------------------------------------
                // Make the 3D effect
                //-----------------------------------------
				if ( !isset( $this->color[ $ci ] ) ) $this->color[ $ci ] = explode( ",", $this->_getSliceColor( $this->data['yaxis'][$ci]['color'] ) );
                $areashadowcolor = imagecolorallocate( $this->image, ($this->color[$ci][0]-50)<0?0:$this->color[$ci][0]-50, ($this->color[$ci][1]-50)<0?0:$this->color[$ci][1]-50, ($this->color[$ci][2]-50)<0?0:$this->color[$ci][2]-50 );

				$areashadow = array( $area[$ci][($numbars-1)*2], $area[$ci][($numbars-1)*2+1], $area[$ci][$numbars*2], $area[$ci][$numbars*2+1],
									 $area[$ci][$numbars*2]+20, $area[$ci][$numbars*2+1]-20, $area[$ci][($numbars-1)*2]+20, $area[$ci][($numbars-1)*2+1]-20 );
				imagefilledpolygon( $this->image, $areashadow, 4, $areashadowcolor );
			}
		}

        if ( $this->options['style3D'] == 1 )
        {
            //-----------------------------------------
            // Make the upper 3D effect
            //-----------------------------------------
            $areashadowcolor = imagecolorallocate( $this->image, ($this->color[$numareas-1][0]-25)<0?0:$this->color[$numareas-1][0]-25, ($this->color[$numareas-1][1]-25)<0?0:$this->color[$numareas-1][1]-25, ($this->color[$numareas-1][2]-25)<0?0:$this->color[$numareas-1][2]-25 );

			for ( $i=1; $i<$numbars; $i++ )
			{
				$areashadow = array( $area[$numareas-1][($i-1)*2], $area[$numareas-1][($i-1)*2+1],
									 $area[$numareas-1][$i*2], $area[$numareas-1][$i*2+1],
									 $area[$numareas-1][$i*2]+20, $area[$numareas-1][$i*2+1]-20,
									 $area[$numareas-1][($i-1)*2]+20, $area[$numareas-1][($i-1)*2+1]-20 );
				imagefilledpolygon( $this->image, $areashadow, count($areashadow)/2, $areashadowcolor );
			}
		}

        //-----------------------------------------
		// Draw the area's
        //-----------------------------------------
		foreach( $area as $ci => $series )
		{
			if ( !isset( $this->color[ $ci ] ) ) $this->color[ $ci ] = explode( ",", $this->_getSliceColor( $this->data['yaxis'][$ci]['color'] ) );
            $areacolor = imagecolorallocate( $this->image, $this->color[$ci][0], $this->color[$ci][1], $this->color[$ci][2] );
			imagefilledpolygon( $this->image, $series, count($series)/2, $areacolor );
			imagecolordeallocate( $this->image, $areacolor );
		}

        //-----------------------------------------
		// Fix the axes
        //-----------------------------------------
        imageline( $this->image, $this->grapharea['x0'], $this->grapharea['y1'], $this->grapharea['x1'], $this->grapharea['y1'], $this->black );
        imageline( $this->image, $this->grapharea['x0'], $this->grapharea['y0'], $this->grapharea['x0'], $this->grapharea['y1'], $this->black );

		if ( $this->options['style3D'] == 1 )
		{
			imageline( $this->image, $this->grapharea['x1']+20, $this->grapharea['y1']-20, $this->grapharea['x1'], $this->grapharea['y1'], $this->black );
			imageline( $this->image, $this->grapharea['x1']+20, $this->grapharea['y1']-20, $this->grapharea['x1']+20, $this->grapharea['y0']-20, $this->black );
		}
		else
		{
			imageline( $this->image, $this->grapharea['x1'], $this->grapharea['y1'], $this->grapharea['x1'], $this->grapharea['y0'], $this->black );
		}

        //-----------------------------------------
        // Datalabels
        //-----------------------------------------
		if ( $this->options['showdatalabels'] )
		{
			$value = array();
			for ( $ci=0; $ci<$numareas; $ci++ )
			{
				for ( $i=0; $i<$numbars; $i++ )
				{
					// Calculate the positions of the area
					if ( isset( $value[$i] ) )
					{
						$value[$i] += $this->data['yaxis'][$ci]['data'][$i];
					}
					else
					{
						$value[$i] = $this->data['yaxis'][$ci]['data'][$i];
					}

		            $valuecolor  = ImageColorAllocate($this->image, hexdec(substr($this->options['textcolor'],1,2)), hexdec(substr($this->options['textcolor'],3,2)), hexdec(substr($this->options['textcolor'],5,2)));
		            $valshadecolor = imagecolorallocate( $this->image, ($this->color[$ci][0]-50)<0?0:$this->color[$ci][0]-50, ($this->color[$ci][1]-50)<0?0:$this->color[$ci][1]-50, ($this->color[$ci][2]-50)<0?0:$this->color[$ci][2]-50 );
		            if ( $this->use_ttf )
		            {
		                $txtsize     = imagettfbbox('10', 0, $this->options['font'], $value[$i] );
		                $textx       = $area[$ci][$i*2] - round(($txtsize[2]-$txtsize[0])/2,0);
		                $texty       = $area[$ci][$i*2+1] - 3;

		                imagettftext($this->image, "10", 0, $textx-1, $texty-1, $valshadecolor, $this->options['font'], $value[$i] );
		                imagettftext($this->image, "10", 0, $textx+1, $texty+1, $valshadecolor, $this->options['font'], $value[$i] );
		                imagettftext($this->image, "10", 0, $textx+2, $texty+2, $valshadecolor, $this->options['font'], $value[$i] );
		                imagettftext($this->image, "10", 0, $textx, $texty, $valuecolor, $this->options['font'], $value[$i] );
		            }
		            else
		            {
		                $textx       = $area[$ci][$i*2] - round(imagefontwidth($this->fontsize)*strlen($value[$i])/2,0);
		                $texty       = $area[$ci][$i*2+1] - imagefontheight($this->fontsize) - 3;

		                imagestring($this->image, $this->fontsize, $textx-1, $texty-1, $value[$i], $valshadecolor );
		                imagestring($this->image, $this->fontsize, $textx+1, $texty+1, $value[$i], $valshadecolor );
		                imagestring($this->image, $this->fontsize, $textx+2, $texty+2, $value[$i], $valshadecolor );
		                imagestring($this->image, $this->fontsize, $textx, $texty, $value[$i], $valuecolor);
		            }
		            imagecolordeallocate( $this->image, $valuecolor );
		            imagecolordeallocate( $this->image, $valshadecolor );
		        }
		    }
        }

		return true;
    }


    /**
	* Generate Scatter Plot (XY Chart)
	*
	* @access	protected
	* @return	void
	*/
    protected function _drawXY()
    {
        //-----------------------------------------
        // Draw Legend and Axes
        //-----------------------------------------
		$this->legend = array ( $this->data['yaxis'][1]['name'] );
		if ( !$this->_drawLegend() ) return false;
		$this->x_axis = array( 'type'	=> 'numeric',
							   'min'	=> 0,
							   'max'	=> $this->_getMax( $this->data['yaxis'][0]['data'] )
							 );
		$this->y_axis = array( 'type'	=> 'numeric',
							   'min'	=> 0,
							   'max'	=> $this->_getMax( $this->data['yaxis'][1]['data'] )
							 );
		$this->_drawAxes();

        //-----------------------------------------
        // Allocate text and shadow cols
        //-----------------------------------------
        $textcolor   = imagecolorallocate( $this->image, hexdec(substr($this->options['titlecolor'],1,2)), hexdec(substr($this->options['titlecolor'],3,2)), hexdec(substr($this->options['titlecolor'],5,2)));
        $shadowcolor = imagecolorallocate( $this->image, hexdec(substr($this->options['titleshadow'],1,2)), hexdec(substr($this->options['titleshadow'],3,2)), hexdec(substr($this->options['titleshadow'],5,2)));
		if ( !isset( $this->color[ 0 ] ) ) $this->color[ 0 ] = explode( ",", $this->_getSliceColor( $this->data['yaxis'][$ci]['color'] ) );

        //-----------------------------------------
		// Calculate bar display variables
        //-----------------------------------------
        $numbars = count( $this->data['yaxis'][0]['data'] );
		$maxvalue1 = $this->_getMax( $this->data['yaxis'][0]['data'] );
		$maxvalue2 = $this->_getMax( $this->data['yaxis'][1]['data'] );

		$numticks = $this->options['numticks'] > $maxvalue1 ? floor($maxvalue1) : $this->options['numticks'];
		$step1 = floor( $maxvalue1 / $numticks );
		$steps = ceil( $maxvalue1 / $step1 );
		$stepwidth = ( $this->grapharea['x1'] - $this->grapharea['x0'] ) / $steps;

		$numticks = $this->options['numticks'] > $maxvalue2 ? floor($maxvalue2) : $this->options['numticks'];
		$step2 = floor( $maxvalue2 / $numticks );
		$steps = ceil( $maxvalue2 / $step2 );
		$stepheight = ( $this->grapharea['y1'] - $this->grapharea['y0'] ) / $steps;

        $dotcolor = imagecolorallocate( $this->image, $this->color[0][0], $this->color[0][1], $this->color[0][2] );
        $dotshadowcolor = imagecolorallocate( $this->image, ($this->color[0][0]-50)<0?0:$this->color[0][0]-50, ($this->color[0][1]-50)<0?0:$this->color[0][1]-50, ($this->color[0][2]-50)<0?0:$this->color[0][2]-50 );

        //-----------------------------------------
        // Dots
        //-----------------------------------------
		for ( $i=0; $i<$numbars; $i++ )
		{

			$value1 = $this->data['yaxis'][0]['data'][$i];
			$value2 = $this->data['yaxis'][1]['data'][$i];

            //-----------------------------------------
            // Find out the bar location and size
            //-----------------------------------------
			$x1 = round( $this->grapharea['x0'] + $value1 * $stepwidth/$step1, 0 );
            $y1 = round( $this->grapharea['y1'] - $value2 * $stepheight/$step2, 0 );

            //-----------------------------------------
            // Dot
            //-----------------------------------------

			imagefilledellipse( $this->image, $x1+1, $y1+1, 7, 7, $dotshadowcolor );
			imagefilledellipse( $this->image, $x1, $y1, 7, 7, $dotcolor );

	        //-----------------------------------------
	        // Datalabels
	        //-----------------------------------------
			if ( $this->options['showdatalabels'] )
			{
	            $valuecolor  = ImageColorAllocate($this->image, hexdec(substr($this->options['textcolor'],1,2)), hexdec(substr($this->options['textcolor'],3,2)), hexdec(substr($this->options['textcolor'],5,2)));
                $valshadecolor = imagecolorallocate( $this->image, ($this->color[0][0]-50)<0?0:$this->color[0][0]-50, ($this->color[0][1]-50)<0?0:$this->color[0][1]-50, ($this->color[0][2]-50)<0?0:$this->color[0][2]-50 );

				$datalabel = '('.$value1.','.$value2.')';

	            if ( $this->use_ttf )
	            {
	                $txtsize     = imagettfbbox('10', 0, $this->options['font'], $datalabel );
	                $textx       = $x1 - round(($txtsize[2]-$txtsize[0])/2,0);
	                $texty       = $y1 - 7;

	                imagettftext($this->image, "10", 0, $textx-1, $texty-1, $valshadecolor, $this->options['font'], $datalabel);
	                imagettftext($this->image, "10", 0, $textx+1, $texty+1, $valshadecolor, $this->options['font'], $datalabel);
	                imagettftext($this->image, "10", 0, $textx+2, $texty+2, $valshadecolor, $this->options['font'], $datalabel);
	                imagettftext($this->image, "10", 0, $textx, $texty, $valuecolor, $this->options['font'], $datalabel);
	            }
	            else
	            {
	                $textx       = $x1 - round(imagefontwidth($this->fontsize)*strlen($datalabel)/2,0);
	                $texty       = $y1 - imagefontheight($this->fontsize)-7;

	                imagestring($this->image, $this->fontsize, $textx-1, $texty-1, $datalabel, $valshadecolor);
	                imagestring($this->image, $this->fontsize, $textx+1, $texty+1, $datalabel, $valshadecolor);
	                imagestring($this->image, $this->fontsize, $textx+2, $texty+2, $datalabel, $valshadecolor);
	                imagestring($this->image, $this->fontsize, $textx, $texty, $datalabel, $valuecolor);
	            }

	            imagecolordeallocate( $this->image, $valuecolor );
	            imagecolordeallocate( $this->image, $valshadecolor );
	        }
		}
        imagecolordeallocate( $this->image, $dotcolor );
        imagecolordeallocate( $this->image, $shadowcolor );

		return true;
    }


    /**
	* Generate Bubble Plot
	*
	* @access	protected
	* @return	void
	*/
    protected function _drawBubble()
    {
        //-----------------------------------------
        // Draw Legend and Axes
        //-----------------------------------------
		$this->legend = array ( $this->data['yaxis'][2]['name'] );
		if ( !$this->_drawLegend() ) return false;
		$this->x_axis = array( 'type'	=> 'numeric',
							   'min'	=> 0,
							   'max'	=> $this->_getMax( $this->data['yaxis'][0]['data'] )
							 );
		$this->y_axis = array( 'type'	=> 'numeric',
							   'min'	=> 0,
							   'max'	=> $this->_getMax( $this->data['yaxis'][1]['data'] )
							 );
		$this->_drawAxes();

        //-----------------------------------------
        // Allocate text and shadow cols
        //-----------------------------------------
        $textcolor   = imagecolorallocate( $this->image, hexdec(substr($this->options['titlecolor'],1,2)), hexdec(substr($this->options['titlecolor'],3,2)), hexdec(substr($this->options['titlecolor'],5,2)));
        $shadowcolor = imagecolorallocate( $this->image, hexdec(substr($this->options['titleshadow'],1,2)), hexdec(substr($this->options['titleshadow'],3,2)), hexdec(substr($this->options['titleshadow'],5,2)));
		if ( !isset( $this->color[ 0 ] ) ) $this->color[ 0 ] = explode( ",", $this->_getSliceColor( $this->data['yaxis'][$ci]['color'] ) );

        //-----------------------------------------
		// Calculate bar display variables
        //-----------------------------------------
        $numbars = count( $this->data['yaxis'][0]['data'] );
		$maxvalue1 = $this->_getMax( $this->data['yaxis'][0]['data'] );
		$maxvalue2 = $this->_getMax( $this->data['yaxis'][1]['data'] );
		$maxvalue3 = $this->_getMax( $this->data['yaxis'][2]['data'] );

		$numticks = $this->options['numticks'] > $maxvalue1 ? floor($maxvalue1) : $this->options['numticks'];
		$step1 = floor( $maxvalue1 / $numticks );
		$steps = ceil( $maxvalue1 / $step1 );
		$stepwidth = ( $this->grapharea['x1'] - $this->grapharea['x0'] ) / $steps;

		$numticks = $this->options['numticks'] > $maxvalue2 ? floor($maxvalue2) : $this->options['numticks'];
		$step2 = floor( $maxvalue2 / $numticks );
		$steps = ceil( $maxvalue2 / $step2 );
		$stepheight = ( $this->grapharea['y1'] - $this->grapharea['y0'] ) / $steps;

		// Largest bubble size is 1 ticksize (largest x vs y)
		$maxbubblesize = ( $stepwidth > $stepheight ) ? $stepwidth : $stepheight;

        $dotcolor = imagecolorallocate( $this->image, $this->color[0][0], $this->color[0][1], $this->color[0][2] );
        $dotshadowcolor = imagecolorallocate( $this->image, ($this->color[0][0]-50)<0?0:$this->color[0][0]-50, ($this->color[0][1]-50)<0?0:$this->color[0][1]-50, ($this->color[0][2]-50)<0?0:$this->color[0][2]-50 );

        //-----------------------------------------
        // Dots
        //-----------------------------------------
		for ( $i=0; $i<$numbars; $i++ )
		{

			$value1 = $this->data['yaxis'][0]['data'][$i];
			$value2 = $this->data['yaxis'][1]['data'][$i];
			$value3 = $this->data['yaxis'][2]['data'][$i];

            //-----------------------------------------
            // Find out the bar location and size
            //-----------------------------------------
			$x1 = round( $this->grapharea['x0'] + $value1 * $stepwidth/$step1, 0 );
            $y1 = round( $this->grapharea['y1'] - $value2 * $stepheight/$step2, 0 );
			$bubblesize = round( ( $value3 / $maxvalue3 ) * $maxbubblesize, 0 );

            //-----------------------------------------
            // Bubble
            //-----------------------------------------
			imagefilledellipse( $this->image, $x1+1, $y1+1, $bubblesize, $bubblesize, $dotshadowcolor );
			imagefilledellipse( $this->image, $x1, $y1, $bubblesize, $bubblesize, $dotcolor );

	        //-----------------------------------------
	        // Datalabels
	        //-----------------------------------------
			if ( $this->options['showdatalabels'] )
			{
	            $valuecolor  = ImageColorAllocate($this->image, hexdec(substr($this->options['textcolor'],1,2)), hexdec(substr($this->options['textcolor'],3,2)), hexdec(substr($this->options['textcolor'],5,2)));
                $valshadecolor = imagecolorallocate( $this->image, ($this->color[0][0]-50)<0?0:$this->color[0][0]-50, ($this->color[0][1]-50)<0?0:$this->color[0][1]-50, ($this->color[0][2]-50)<0?0:$this->color[0][2]-50 );

				$datalabel = '('.$value1.','.$value2.','.$value3.')';

	            if ( $this->use_ttf )
	            {
	                $txtsize     = imagettfbbox('10', 0, $this->options['font'], $datalabel );
	                $textx       = $x1 - round(($txtsize[2]-$txtsize[0])/2,0);
	                $texty       = $y1 - ( $bubblesize + 5 ) / 2;

	                imagettftext($this->image, "10", 0, $textx-1, $texty-1, $valshadecolor, $this->options['font'], $datalabel);
	                imagettftext($this->image, "10", 0, $textx+1, $texty+1, $valshadecolor, $this->options['font'], $datalabel);
	                imagettftext($this->image, "10", 0, $textx+2, $texty+2, $valshadecolor, $this->options['font'], $datalabel);
	                imagettftext($this->image, "10", 0, $textx, $texty, $valuecolor, $this->options['font'], $datalabel);
	            }
	            else
	            {
	                $textx       = $x1 - round(imagefontwidth($this->fontsize)*strlen($datalabel)/2,0);
	                $texty       = $y1 - imagefontheight($this->fontsize)-7;

	                imagestring($this->image, $this->fontsize, $textx-1, $texty-1, $datalabel, $valshadecolor);
	                imagestring($this->image, $this->fontsize, $textx+1, $texty+1, $datalabel, $valshadecolor);
	                imagestring($this->image, $this->fontsize, $textx+2, $texty+2, $datalabel, $valshadecolor);
	                imagestring($this->image, $this->fontsize, $textx, $texty, $datalabel, $valuecolor);
	            }

	            imagecolordeallocate( $this->image, $valuecolor );
	            imagecolordeallocate( $this->image, $valshadecolor );
	        }
		}
        imagecolordeallocate( $this->image, $dotcolor );
        imagecolordeallocate( $this->image, $shadowcolor );

		return true;
    }


    /**
	* Generate Funnel Chart
	*
	* @access	protected
	* @return	void
	*/
    protected function _drawFunnel()
    {
        //-----------------------------------------
        // Draw Axes
        //-----------------------------------------
		$numcats = count( $this->data['xaxis'] );
		for ( $i = $numcats - 1; $i >= 0; $i-- )
		{
			$labels[$i] = $this->data['xaxis'][$numcats-1-$i];
		}
		$this->y_axis = array( 'type'	=> 'labels',
							   'labels'	=> $labels
							 );
		$this->_drawAxes();

        //-----------------------------------------
        // Allocate text and shadow cols
        //-----------------------------------------
        $textcolor   = imagecolorallocate( $this->image, hexdec(substr($this->options['titlecolor'],1,2)), hexdec(substr($this->options['titlecolor'],3,2)), hexdec(substr($this->options['titlecolor'],5,2)));
        $shadowcolor = imagecolorallocate( $this->image, hexdec(substr($this->options['titleshadow'],1,2)), hexdec(substr($this->options['titleshadow'],3,2)), hexdec(substr($this->options['titleshadow'],5,2)));

        //-----------------------------------------
		// Calculate bar display variables
        //-----------------------------------------
        $numbars = count( $this->data['yaxis'][0]['data'] );
		$maxvalue = $this->_getMax( $this->data['yaxis'][0]['data'] );
		$stepheight = floor( ( $this->grapharea['y1'] - $this->grapharea['y0'] ) / $numbars );

		$numticks = $this->options['numticks'] > $maxvalue ? floor($maxvalue) : $this->options['numticks'];
		$step = floor( $maxvalue / $numticks );
		$steps = ceil( $maxvalue / $step );
		$stepwidth = ( $this->grapharea['x1'] - $this->grapharea['x0'] ) / $steps;

		$xmid = $this->grapharea['x0'] + floor( ( $this->grapharea['x1'] - $this->grapharea['x0'] ) / 2 );

		// Get the colors
		for ( $i = $numcats - 1; $i >= 0; $i-- )
		{
			if ( !isset( $this->color[ $i ] ) ) $this->color[ $i ] = explode( ",", $this->_getSliceColor() );
		}


		for ( $i = 0; $i < $numcats; $i++ )
		{
			$value = $this->data['yaxis'][0]['data'][$numcats-1-$i];

			$x0 = $xmid - round( ( $value * $stepwidth / $step ) / 2, 0 );
			$x2 = $xmid + round( ( $value * $stepwidth / $step ) / 2, 0 );
			$y1 = $this->grapharea['y1'] - $i * $stepheight - 1;

			if ( isset( $this->data['yaxis'][0]['data'][$numcats-2-$i] ) )
			{
				$nextval = $this->data['yaxis'][0]['data'][$numcats-2-$i];
			}
			else
			{
				$nextval = $maxvalue;
			}

			$x1 = $xmid - round( ( $nextval * $stepwidth/$step ) / 2, 0 );
			$x3 = $xmid + round( ( $nextval * $stepwidth/$step ) / 2, 0 );
			$y0 = $y1 - $stepheight + 1;

            $linecolor  = ImageColorAllocate($this->image, $this->color[$i][0], $this->color[$i][1], $this->color[$i][2] );

			if ( $this->options['style3D'] != 1 )
			{
				// 2D style
				$points = array ( $x0, $y1, $x1, $y0, $x3, $y0, $x2, $y1 );
				imagefilledpolygon( $this->image, $points, 4, $linecolor );
			}
			else
			{
				//3D Style
				for ( $j = 0; $j < $stepheight; $j++ )
				{
					$width = ( $x2 - $x0 ) + round( $j * (( $x3 - $x1 ) - ( $x2 - $x0 )) / $stepheight, 0 );
					$height = floor ( ( ( $value + ( $j * ( $nextval - $value ) / $stepheight ) ) / $maxvalue ) * 40 );
					$x = $xmid + 20;
					$y = $y1 - 20 - $j;
					imagefilledarc( $this->image, $x, $y, $width, $height, 0, 180, $linecolor, IMG_ARC_PIE );
				}
			}

            //-----------------------------------------
            // Datalabels
            //-----------------------------------------
			if ( $this->options['showdatalabels'] )
			{
	            $textcolor  = ImageColorAllocate($this->image, hexdec(substr($this->options['textcolor'],1,2)), hexdec(substr($this->options['textcolor'],3,2)), hexdec(substr($this->options['textcolor'],5,2)));
                $shadowcolor = imagecolorallocate( $this->image, ($this->color[$i][0]-50)<0?0:$this->color[$i][0]-50, ($this->color[$i][1]-50)<0?0:$this->color[$i][1]-50, ($this->color[$i][2]-50)<0?0:$this->color[$i][2]-50 );

				if ( $nextval == 0 )
				{
					$label = $value.' (100%)';
				}
				else
				{
					$label = $value.' ('.round(($value/$nextval)*100,1).'%)';
				}
	            if ( $this->use_ttf )
	            {
	                $txtsize     = imagettfbbox('10', 0, $this->options['font'], $label );
	                $textx       = $xmid - round(($txtsize[2]-$txtsize[0])/2,0);
	                $texty       = $y0 + floor( $stepheight / 2 ) + floor(($txtsize[1]-$txtsize[5])/2);

					if ( $this->options['style3D'] )
					{
						$correction = floor ( ( ( $nextval - (($nextval - $value) / 2 ) ) / $maxvalue ) * 20 );
						$textx = $textx + 20 - $correction;
						$texty = $texty - 20 + $correction;
					}

	                imagettftext($this->image, "10", 0, $textx-1, $texty-1, $shadowcolor, $this->options['font'], $label);
	                imagettftext($this->image, "10", 0, $textx+1, $texty+1, $shadowcolor, $this->options['font'], $label);
	                imagettftext($this->image, "10", 0, $textx+2, $texty+2, $shadowcolor, $this->options['font'], $label);
	                imagettftext($this->image, "10", 0, $textx, $texty, $textcolor, $this->options['font'], $label);
	            }
	            else
	            {
	                $textx       = $xmid - round(imagefontwidth($this->fontsize)*strlen($label)/2,0);
	                $texty       = ($textx < $this->grapharea['x0']+2) ? $this->grapharea['x0']+2 : $textx;
	                $texty       = $y0 +  + floor( $stepheight / 2 ) - floor(imagefontheight($this->fontsize)/2);

					if ( $this->options['style3D'] )
					{
						$correction = floor ( ( ( $nextval - (($nextval - $value) / 2 ) ) / $maxvalue ) * 20 );
						$textx = $textx + 20 - $correction;
						$texty = $texty - 20 + $correction;
					}

	                $shadowcolor = imagecolorallocate( $this->image, ($this->color[$i][0]-50)<0?0:$this->color[$i][0]-50, ($this->color[$i][1]-50)<0?0:$this->color[$i][1]-50, ($this->color[$i][2]-50)<0?0:$this->color[$i][2]-50 );
	                imagestring($this->image, $this->fontsize, $textx-1, $texty-1, $label, $shadowcolor);
	                imagestring($this->image, $this->fontsize, $textx+1, $texty+1, $label, $shadowcolor);
	                imagestring($this->image, $this->fontsize, $textx+2, $texty+2, $label, $shadowcolor);
	                imagestring($this->image, $this->fontsize, $textx, $texty, $label, $textcolor);
	            }

                imagecolordeallocate( $this->image, $shadowcolor );
                imagecolordeallocate( $this->image, $textcolor );
			}
		}

		if ( $this->options['style3D'] )
		{
			$insidecolor =  imagecolorallocate( $this->image, ($this->color[$numcats - 1][0]-50)<0?0:$this->color[$numcats - 1][0]-50, ($this->color[$numcats - 1][1]-50)<0?0:$this->color[$numcats - 1][1]-50, ($this->color[$numcats - 1][2]-50)<0?0:$this->color[$numcats - 1][2]-50 );
			imagefilledarc( $this->image, $xmid + 20, $y0 - 20, $x3 - $x1, 40, 0, 360, $insidecolor, IMG_ARC_PIE );
            $linecolor  = ImageColorAllocate($this->image, $this->color[$numcats - 1][0], $this->color[$numcats - 1][1], $this->color[$numcats - 1][2] );
			imagearc( $this->image, $xmid + 20, $y0 - 20, $x3 - $x1, 40, 0, 360, $linecolor );
		}

		return true;
	}


    /**
	* Generate Radar Chart
	*
	* @access	protected
	* @return	void
	*/
    protected function _drawRadar()
    {
        //-----------------------------------------
        // Draw Legend & Axes
        //-----------------------------------------
		foreach ( $this->data['yaxis'] as $key => $series )
		{
			$this->legend[ $key ] = $series['name'];
		}
		if ( !$this->_drawLegend() ) return false;

        //-----------------------------------------
		// Figure out center position
        //-----------------------------------------
        $xmid = $this->grapharea['x0'] + round( ( $this->grapharea['x1'] - $this->grapharea['x0'] ) / 2, 0);
        $ymid = $this->grapharea['y0'] + round( ( $this->grapharea['y1'] - $this->grapharea['y0'] ) / 2, 0);

        //-----------------------------------------
		// Figure out size
        //-----------------------------------------
		$maxlabelwidth = 0;
		$maxlabelheight = 0;
		foreach( $this->data['xaxis'] as $label )
		{
            if ( $this->use_ttf )
            {
                $textsize = imagettfbbox(10, 0, $this->options['font'], $label );
                if ( ($textsize[2] - $textsize[0]) > $maxlabelwidth ) $maxlabelwidth = $textsize[2] - $textsize[0];
                if ( ($textsize[1] - $textsize[5]) > $maxlabelheight ) $maxlabelheight = $textsize[1] - $textsize[5];
            }
            else
            {
                $textsize = imagefontwidth( $this->fontsize ) * strlen( $label );
                if ( $textsize > $maxlabelwidth ) $maxlabelwidth = $textsize;
                $maxlabelheight = imagefontheight( $this->fontsize );
            }
        }

		$lengthx = floor( ( $this->grapharea['x1'] - $this->grapharea['x0'] ) / 2 - 5 - $maxlabelwidth );
		$lengthy = floor( ( $this->grapharea['y1'] - $this->grapharea['y0'] ) / 2 - 5 - $maxlabelheight );
		$armlength = $lengthx > $lengthy ? $lengthy : $lengthx;
		$numaxes = count( $this->data['yaxis'][0]['data'] );
		$maxvalue = $this->_getMax( $this->data['yaxis'] );
		$numticks = $this->options['numticks'] > $maxvalue ? floor($maxvalue) : $this->options['numticks'];
		$maxvalue = ceil ( $maxvalue / $numticks ) * $numticks;

        //-----------------------------------------
        // Allocate text and shadow cols
        //-----------------------------------------
        $textcolor   = imagecolorallocate( $this->image, hexdec(substr($this->options['titlecolor'],1,2)), hexdec(substr($this->options['titlecolor'],3,2)), hexdec(substr($this->options['titlecolor'],5,2)));
        $shadowcolor = imagecolorallocate( $this->image, hexdec(substr($this->options['titleshadow'],1,2)), hexdec(substr($this->options['titleshadow'],3,2)), hexdec(substr($this->options['titleshadow'],5,2)));

        //-----------------------------------------
        // A spider draws a web
        //-----------------------------------------
		$webxy = array();
		$webtick = array();
		$textxy = array();
		for ( $i = 0; $i < $numaxes; $i++ )
		{
			$rotation = -90 + round( $i * 360 / $numaxes, 0 );

			// Web axes
            $webxy[$i*2] = $xmid + cos(deg2rad( $rotation ) ) * $armlength;
	        $webxy[$i*2+1] = $ymid + sin(deg2rad( $rotation ) ) * $armlength;
			imageline( $this->image, $xmid, $ymid, $webxy[$i*2], $webxy[$i*2+1], $this->black );

			// Calculate web tick
			for ( $j = 0; $j < $this->options['numticks']; $j++ )
			{
	            $webtickxy[$j][$i*2] = $xmid + cos(deg2rad( $rotation ) ) * ( ( $j + 1  )* $armlength / ( $this->options['numticks'] ) );
		        $webtickxy[$j][$i*2+1] = $ymid + sin(deg2rad( $rotation ) ) * ( ( $j + 1 ) * $armlength / ( $this->options['numticks'] ) );
			}

			// Calculate the label positions
			$label = $this->data['xaxis'][$i];
            if ( $this->use_ttf )
            {
	            $textsize = imagettfbbox(10, 0, $this->options['font'], $label );
	            $textwidth = $textsize[4] - $textsize[0];
	            $textheight = $textsize[1] - $textsize[5];
	        }
	        else
	        {
                $textwidth = imagefontwidth($this->fontsize)*strlen($label);
            	$textheight = imagefontheight($this->fontsize);
			}

			if ( $rotation > -90 && $rotation < 90 )
			{
				$textxy[] = $webxy[$i*2] + 5;
				$textxy[] = $webxy[$i*2+1] + floor( ( $textheight ) / 2 );
            }
            elseif ( $rotation == -90 )
            {
				$textxy[] = $webxy[$i*2] - floor( ( $textwidth ) / 2 );
				$textxy[] = $webxy[$i*2+1] - 5;
			}
            elseif ( $rotation == 90 )
            {
				$textxy[] = $webxy[$i*2] - floor( ( $textwidth ) / 2 );
				$textxy[] = $webxy[$i*2+1] + 5 + $textheight;
			}
			else
			{
				$textxy[] = $webxy[$i*2] - 5 - ( $textwidth );
				$textxy[] = $webxy[$i*2+1] + floor( ( $textheight ) / 2 );
			}

			// Draw the labels
            if ( $this->use_ttf )
            {
	            imagettftext($this->image, "10", 0, $textxy[$i*2]+1, $textxy[$i*2+1]+1, $shadowcolor, $this->options['font'], $label );
	            imagettftext($this->image, "10", 0, $textxy[$i*2]  , $textxy[$i*2+1]  , $textcolor  , $this->options['font'], $label );
			}
			else
			{
                imagestring($this->image, $this->fontsize, $textxy[$i*2]+1, $textxy[$i*2+1]+1-imagefontheight($this->fontsize), $label, $shadowcolor);
                imagestring($this->image, $this->fontsize, $textxy[$i*2], $textxy[$i*2+1]-imagefontheight($this->fontsize), $label, $textcolor);
			}
		}

        //-----------------------------------------
		// Draw the web lines
        //-----------------------------------------
		foreach( $webtickxy as $tickxy )
		{
			imagepolygon( $this->image, $tickxy, $numaxes, $this->black );
		}

        //-----------------------------------------
        // Draw the axes value text
        //-----------------------------------------
		$numticks = $this->options['numticks'] > $maxvalue ? floor($maxvalue) : $this->options['numticks'];
		$tick = $maxvalue / $numticks;
		for ( $j = 0; $j < $this->options['numticks']; $j++ )
		{
			$value = ($j + 1) * $tick;
	        if ( $this->use_ttf )
	        {
	            $textsize = imagettfbbox(10, 0, $this->options['font'], $value );
				$textheight = $textsize[1] - $textsize[5];
	            imagettftext($this->image, "10", 0, $webtickxy[$j][0]+6, $webtickxy[$j][1]+floor($textheight/2)+1, $shadowcolor, $this->options['font'], $value );
	            imagettftext($this->image, "10", 0, $webtickxy[$j][0]+5, $webtickxy[$j][1]+floor($textheight/2)  , $textcolor  , $this->options['font'], $value );
			}
			else
			{
	            imagestring($this->image, $this->fontsize, $webtickxy[$j][0]+6, $webtickxy[$j][1]-floor(imagefontheight($this->fontsize)/2)+1, $value, $shadowcolor );
	            imagestring($this->image, $this->fontsize, $webtickxy[$j][0]+5, $webtickxy[$j][1]-floor(imagefontheight($this->fontsize)/2)  , $value, $textcolor );
			}
		}

        //-----------------------------------------
        // Draw the series
        //-----------------------------------------
		$numseries = count( $this->data['yaxis'] );
		for ( $ci = 0; $ci < $numseries; $ci++ )
		{
			if ( !isset( $this->color[ $ci ] ) ) $this->color[ $ci ] = explode( ",", $this->_getSliceColor( $this->data['yaxis'][$ci]['color'] ) );
	        $linecolor   = ImageColorAllocate( $this->image, $this->color[$ci][0], $this->color[$ci][1], $this->color[$ci][2] );
	        $dotcolor = imagecolorallocate( $this->image, $this->color[$ci][0], $this->color[$ci][1], $this->color[$ci][2] );
	        $dotshadowcolor = imagecolorallocate( $this->image, ($this->color[$ci][0]-50)<0?0:$this->color[$ci][0]-50, ($this->color[$ci][1]-50)<0?0:$this->color[$ci][1]-50, ($this->color[$ci][2]-50)<0?0:$this->color[$ci][2]-50 );

	        //-----------------------------------------
	        // Get the x and y's for the lines
	        //-----------------------------------------
			$linesxy = array();
			$lineshadowxy = array();
			$textxy = array();
			for ( $i = 0; $i < $numaxes; $i++ )
			{
				$value = $this->data['yaxis'][$ci]['data'][$i];

				$rotation = -90 + round( $i * 360 / $numaxes, 0 );
	            $linesxy[$i*2] = $xmid + cos(deg2rad( $rotation ) ) * floor( ( $value / $maxvalue ) * $armlength );
		        $linesxy[$i*2+1] = $ymid + sin(deg2rad( $rotation ) ) * floor( ( $value / $maxvalue ) * $armlength );
	            $lineshadowxy[$i*2] = $linesxy[$i*2] + 1;
		        $lineshadowxy[$i*2+1] = $linesxy[$i*2+1] + 1;

				if ( $this->options['showdatalabels'] )
				{
					// Calculate the datalabel positions
					$textxy[$i*2] = $xmid + cos(deg2rad( $rotation ) ) * floor( ( $value / $maxvalue ) * $armlength + 5 );
			        $textxy[$i*2+1] = $ymid + sin(deg2rad( $rotation ) ) * floor( ( $value / $maxvalue ) * $armlength + 5 );

		            if ( $this->use_ttf )
		            {
			            $textsize = imagettfbbox(10, 0, $this->options['font'], $value );
			            $textwidth = $textsize[4] - $textsize[0];
			            $textheight = $textsize[1] - $textsize[5];
			        }
			        else
			        {
		                $textwidth = imagefontwidth($this->fontsize)*strlen($value);
	                	$textheight = imagefontheight($this->fontsize);
					}

					if ( $rotation > -90 && $rotation < 90 )
					{
						$textxy[$i*2] = $textxy[$i*2] + 5;
						$textxy[$i*2+1] = $textxy[$i*2+1] + floor( ( $textheight ) / 2 );
		            }
		            elseif ( $rotation == -90 )
		            {
						$textxy[$i*2] = $textxy[$i*2] - floor( ( $textwidth ) / 2 );
						$textxy[$i*2+1] = $textxy[$i*2+1] - 5;
					}
		            elseif ( $rotation == 90 )
		            {
						$textxy[$i*2] = $textxy[$i*2] - floor( ( $textwidth ) / 2 );
						$textxy[$i*2+1] = $textxy[$i*2+1] + 5 + $textheight;
					}
					else
					{
						$textxy[$i*2] = $textxy[$i*2] - 5 - ( $textwidth );
						$textxy[$i*2+1] = $textxy[$i*2+1] + floor( ( $textheight ) / 2 );
					}
				}
			}

	        //-----------------------------------------
	        // Draw the lines, dots and text
	        //-----------------------------------------
			imagepolygon( $this->image, $linesxy, $numaxes, $linecolor );
			imagepolygon( $this->image, $lineshadowxy, $numaxes, $dotshadowcolor );

		    $valuecolor  = ImageColorAllocate($this->image, hexdec(substr($this->options['textcolor'],1,2)), hexdec(substr($this->options['textcolor'],3,2)), hexdec(substr($this->options['textcolor'],5,2)));
            $valshadecolor = imagecolorallocate( $this->image, ($this->color[$ci][0]-50)<0?0:$this->color[$ci][0]-50, ($this->color[$ci][1]-50)<0?0:$this->color[$ci][1]-50, ($this->color[$ci][2]-50)<0?0:$this->color[$ci][2]-50 );

			for( $i = 0; $i < $numaxes; $i++ )
			{
				imagefilledellipse( $this->image, $linesxy[$i*2]+1, $linesxy[$i*2+1]+1, 7, 7, $dotshadowcolor );
				imagefilledellipse( $this->image, $linesxy[$i*2], $linesxy[$i*2+1], 7, 7, $dotcolor );

				if ( $this->options['showdatalabels'] )
				{
					$value = $this->data['yaxis'][$ci]['data'][$i];
		            if ( $this->use_ttf )
		            {
			            imagettftext($this->image, "10", 0, $textxy[$i*2]-1, $textxy[$i*2+1]-1, $valshadecolor, $this->options['font'], $value );
			            imagettftext($this->image, "10", 0, $textxy[$i*2]+1, $textxy[$i*2+1]+1, $valshadecolor, $this->options['font'], $value );
			            imagettftext($this->image, "10", 0, $textxy[$i*2]+2, $textxy[$i*2+1]+2, $valshadecolor, $this->options['font'], $value );
			            imagettftext($this->image, "10", 0, $textxy[$i*2]  , $textxy[$i*2+1]  , $valuecolor  , $this->options['font'], $value );
			        }
			        else
			        {
		                imagestring($this->image, $this->fontsize, $textx-1, $texty-1-imagefontheight($this->fontsize), $label, $valshadecolor);
		                imagestring($this->image, $this->fontsize, $textx+1, $texty+1-imagefontheight($this->fontsize), $label, $valshadecolor);
		                imagestring($this->image, $this->fontsize, $textx+2, $texty+2-imagefontheight($this->fontsize), $label, $valshadecolor);
		                imagestring($this->image, $this->fontsize, $textx, $texty-imagefontheight($this->fontsize), $label, $valuecolor);
		            }
		        }
			}

			imagecolordeallocate( $this->image, $linecolor );
			imagecolordeallocate( $this->image, $dotcolor );
			imagecolordeallocate( $this->image, $dotshadowcolor );
			imagecolordeallocate( $this->image, $valshadecolor );
			imagecolordeallocate( $this->image, $valuecolor );
		}

		return true;
	}


    /**
	* Get a random color
	*
	* @access	protected
	* @return	string		String with RGB values for the color (format 'R,G,B')
	*/
    protected function _getSliceColor( $force_color='' )
    {
        # Remove 0,0,0 from count
        $used_count = count( $this->used_colors ) - 1;
        $return     = "";

        # Used all std cols
		if ( $force_color )
		{
			$r = hexdec( substr( $force_color, 1, 2 ) );
			$g = hexdec( substr( $force_color, 3, 2 ) );
			$b = hexdec( substr( $force_color, 5, 2 ) );
            $return = "$r,$g,$b";
			$this->used_colors[] = $return;
		}
        elseif ( $used_count < count( $this->colors ) )
        {
            $this->used_colors[] = $this->colors[ $used_count ];
            $return = $this->colors[ $used_count ];
        }
        else
        {
            # 0-12 for each RGB bit == 1728 poss col. combinations
           for ( $i = 0 ; $i <= 1728 ; $i++ )
           {
               $r = mt_rand( 0, 12 ) * 20;
               $g = mt_rand( 0, 12 ) * 20;
               $b = mt_rand( 0, 12 ) * 20;

               $return = "$r,$g,$b";

               if ( ! in_array( $return, $this->used_colors ) )
               {
                   $this->used_colors[] = $return;
                   break;
               }
               else
               {
                   continue;
               }
           }
        }

        return $return;
    }


    /**
	* Get maximum value from the y-axis data array's
	*
	* @access	protected
	* @param	array		Array of Y-axis data serie(s)
	* @return	numeric		Maximum value from the input array
	*/
	protected function _getMax( $data=array() )
	{
		$maxval = 0;
		foreach( $data as $series )
		{
			if ( is_array( $series ) )
			{
				foreach( $series['data'] as $value )
				{
					if ( $value > $maxval ) $maxval = $value;
				}
			}
			else
			{
				if ( $series > $maxval ) $maxval = $series;
			}
		}
		return $maxval;
	}


    /**
	* Check the series required for graph
	*
	* @access	protected
	* @param	int			Minimum number of series
	* @param	boolean		Require labels
	* @return	boolean		True if all checks pass; false otherwise
	*/
	protected function _checkSeries( $minseries=0, $labelsRequired=0 )
	{
        //-----------------------------------------
        // Check series
        //-----------------------------------------
		if ( !isset( $this->data['yaxis'][$minseries-1]['data'] ) )
		{
			$this->_error( 'There has to be at least '.$minseries.' series added to draw the \''.$this->options['charttype'].'\' graph type.' );
			return false;
		}
		if ( $labelsRequired && ( !isset( $this->data['xaxis'] ) or !is_array( $this->data['xaxis'] ) ) )
		{
			$this->_error( 'This graph requires labels to be added.' );
			return false;
		}
		$cntseries = count( $this->data['yaxis'][0]['data'] );
		foreach( $this->data['yaxis'] as $series )
		{
			if ( $cntseries != count( $series['data'] ) )
			{
				$this->_error( 'Not all series have the same number of values.' );
				return false;
			}
		}
		if ( $labelsRequired && count( $this->data['xaxis'] ) != count( $this->data['yaxis'][0]['data'] ) )
		{
			$this->_error( 'The number of labels is not the same as the number of data values in the series.' );
			return false;
		}
		foreach( $this->data['yaxis'] as $series )
		{
			foreach( $series['data'] as $value )
			{
				if ( !is_numeric( $value ) )
				{
					$this->_error( 'Non-numeric value found. All values of a series need to be numeric.' );
					return false;
				}
			}
		}
		return true;
	}


    /**
	* Check if a color string is valid
	*
	* @access	protected
	* @param	int			Minimum number of series
	* @param	boolean		Require labels
	* @return	boolean		True if all checks pass; false otherwise
	*/
	protected function _checkColor( $color='' )
	{
		if ( strlen( $color ) != 7 )
		{
			$this->_error( 'Invalid color code supplied. A color needs to be supplied as HTML hex color code. Example #FFFFFF for white.' );
			return false;
		}
		elseif ( !preg_match( "#[0-9A-F]{6}#i", substr( $color, 1 ) ) )
		{
			$this->_error( 'Invalid color code supplied. A color needs to be supplied as HTML hex color code. Example #FFFFFF for white.' );
			return false;
		}

		return true;
	}


    /**
	* Error routine, display the error message in debug mode
	*
	* @access	protected
	* @param	string		Error message
	* @return	void
	*/
	protected function _error( $msg='' )
	{
        //-----------------------------------------
        // Destroy the original image
        //-----------------------------------------
        if ( $this->image )
        {
        	imagedestroy( $this->image );
        }

        //-----------------------------------------
        // Start GD process
        //-----------------------------------------
		$width = $this->options['width'] < 400 ? 400 : $this->options['width'];
		$height = $this->options['height'] < 200 ? 200 : $this->options['height'];
        $errorimage = imagecreatetruecolor( $width, $height );
        $bgcolor = imagecolorallocate($errorimage, 255, 255, 255);
        imagefilledrectangle( $errorimage, 0, 0, $width, $height, $bgcolor );

        if ( function_exists('imageantialias') )
        {
            @imageantialias( $errorimage, TRUE );
        }

        //-----------------------------------------
        // Allocate black and grey
        //-----------------------------------------
        $black = imagecolorallocate( $errorimage, 0, 0, 0 );
        $grey = imagecolorallocate( $errorimage, 170, 170, 170);

		$messageheight = 0;
		if ( $this->debug )
		{
	        //-----------------------------------------
	        // Wordwrap the error message
	        //-----------------------------------------
			$errormessage = '';
			while ( strlen( $msg ) > 44 )
			{
				$lastspace = 0;
				for ( $j = 0; $j < 44; $j++ )
				{
					if ( substr( $msg, $j, 1 ) == ' ' )
					{
						$lastspace = $j;
					}
				}
				$errormessage .= substr( $msg, 0, $lastspace )."\r\n";
				$msg = substr( $msg, $lastspace+1 );
			}
			$errormessage .= $msg;

			$errormesssage = "Error message:\r\n".$errormessage;

	        //-----------------------------------------
	        // Calculate vertical size
	        //-----------------------------------------
			if ( $this->use_ttf )
			{
	            $txtsize	= imagettfbbox( '10', 0, $this->options['font'], $errormessage );
				$messageheight = $txtsize[1] - $txtsize[5];
			}
			else
			{
				$msgarray = explode( "\r\n", $errormessage );
				foreach( $msgarray as $msgline )
				{
		            $messageheight = $messageheight + imagefontheight( 3 ) + 10;
		        }
		        $messageheight -= 10;
			}

			$messageheight += 40;
		}

        //-----------------------------------------
        // Get the error icon
        //-----------------------------------------
		$errorIconData = base64_decode( 'R0lGODlhKQAlAPf/AP34o/75s6qGFOTi2/32jbKSIvXfTPHqy/Hx8fz3mfz2kvTcQfLx6dTDdf78ybaZNcStXPfjWqF8Df35uv'.
										'7+3fz2kMWsRf34nf//+f33lPn5+cKnO/Hr1NHR0dnGTKyMJOzlzeDe1vvxb/rsedPMabmdN/TcRvjnababQfrrdvLXNbusRPzt'.
										'f/vycv31nP32jsu1V83b6P795LOVNPzzefvydfz3l72kRuLXq+/v7/36renp6amGHXBtOpqOOO3ktPjlY/PaPvPZO/TeSfz0hP'.
										'jmZvX19f78xvnqc/z1iPzwfdfGfcq1XqyLHVhULu3lvuHUlPz0i/31h6eCDJt1Bf76qf32iPz0gtPHmvzzdayJFf31fuHSjf37'.
										'9Pzybvj15vfz4lZPHvHqwv3zkVhUKqB6Bvj13MauTvv57vj27vj168++Sv34mv31pP32iv31gfzzgPvzerStW9XCaP7911dRIv'.
										'32hvz1jO3l0fvzeN3RptG+esOsU7iaMIeER/PYOP///fnobPnpb/LXM/bgT/bgU/vtfPjkXvHWMPzugfHVLvDULvzvg////PDU'.
										'Lf3whffjV/v7+/DTLf//+v39+uzs7Pf39/3xh+Tk5NvWwf360f/+9ePZt9vb28y6df33jIeCP/biTvnsYPvte/XhTv32g9vMiL'.
										'GPHXlwKse1Znt5QvzzfmJbI/3xiaSWN/fmV/rwgLufOvHXM9C5XP7+6/nparaXJreZL1ZPH2RiN3luKfzufvz8/P79/dzZxtfQ'.
										'sbOsT766afvzmPrsc+vr6/ruafzzcqJ+Eb6hMd3bz/z1jvTbQPjoV/nugMS0Rc68QvHYOdfX18i2cOTZpvPfTv376f377oR6MP'.
										'790P31hPjqYPbz3/joZ/rvZuLWm/DaS/3zhNfQrfnpcPnpcuPj4+3lxuLXpv33kvjtffPbQPvybMjGvfXfR/Dka/DUL/77w/37'.
										'xv3yjf78wuDf2ubcsefn5/vwetnIdtzc3K+iQMGoStLDg9fFes/Pz5pzAMnY5iH5BAEAAP8ALAAAAAApACUAAAj/AP8JHEiwoE'.
										'AOKKigAKHBoMOHEAmq+cCpFyceAzTEiMiR4w09RwAc0YNiR6SOKA3Sm7EJAIALm2ZgQZCy5j9KH/AEuHAhgQ48H+Y1tNlxHwQK'.
										'PNkksEEBgrRKRDmiOaYGgFIbChQkSCMhmZGoEJnswXY1g4IKyuDt4WMSrMEDPLr0NFvhBYEoUbrw+EXTLcEHOCbYoPviTpIkRK'.
										'rgmIFpqN9pD2gNzlDhjmEpRK7AkfFgXw6/Nz+AqJLh3ItguZz4IQJnVZxz5ZrMs+S3AQQ6pe32cMKbhOs4eRzwSVWJF1gwEtKw'.
										'MU3AjSreTkjQyFOjRRyuvr5GPbNEXt3mUvw4/yFDhgaN6iJE6Ghwo61NuNYyECBgRUo2OeR7ZMnSQoQ6dV5UI0A4fdXUhzk6fO'.
										'JGfdm8gR8ZoGRhjBfqdCMKM7BAUUBjNUFRAgWffJKEfW9sQQJ5wBSjzSuhsBPEHypMYIsnn6FEiQAcXPCJFVaUUsoWNLRTBy7O'.
										'EDKECQssE4QQf5AixhSzodQAH9jsaAcc9gxTCxCH4BKGB4QYcOQCS8LYxgZ8QMURcmqcA44SI4gTyAlFcHlNGIWEOWaZKqRjRh'.
										'mZaAeRBUsEsMsIKYwjyJx1HuIDKnmKiSSfgzQzRx/uOfSEFjKwMAqiSCxKJ5f5+BDpnkyqMIgsmggwE0S2eP/Txi6GgCpqo2us'.
										'cOqkqQ6CCDdcnEKOYwRx0QcdidBqK6NcRgDJrmT2iogiMtJoUBcCHDBGsrWmECqzh3iwBrSUTjvEkyHQRtAsMLzDCLfLjuqDl7'.
										'p8I2m0MPqqyCLCWKAPMQRtU8YX8byrrLe3AoGLl2E8c2+5+wrxZ6ADbcBPAI8Y3O23o67AShiu6MlrvtMu4gg6l9Zz0g9NyNBK'.
										'xvAiDK6z5Eq7ryPutPoqMqYEcAnMB3PcKM0i46tqyY5IgsQ9tmDyz8Bj/KxxvEM/WzTEJksCzZ/RPI2GC1LHLHSzVj9sc9bU/N'.
										'nBPxsw8QQgcMctt9yN1G33JHhjoPfefP8tAMMtazNwhhb+FG744YgnrjjiU8SyTicC5YBPB/1UbvnlmGeuOeYdQA6aWwEBADs=' );
		$errorIcon = imagecreatefromstring( $errorIconData );

        //-----------------------------------------
        // Draw the error box
        //-----------------------------------------
		$ysize = 80 + $messageheight;
		$x = ( $width - 400 ) / 2;
		$y = ( $height - $ysize ) / 2;
		imagecopymerge( $errorimage, $errorIcon, $x + 20, $y + 20, 0, 0, imagesx( $errorIcon ), imagesy( $errorIcon ), 100 );
		imagedestroy( $errorIcon );

		imagerectangle( $errorimage, $x+1, $y+1, $x+400, $y+$ysize+1, $grey );
		imagerectangle( $errorimage, $x, $y, $x+399, $y+$ysize, $black );

        //-----------------------------------------
        // Display the error text
        //-----------------------------------------
		$message = 'Error generating graph';
		if ( $this->use_ttf )
		{
	        imagettftext( $errorimage, "20", 0, $x+101, $y+48, $grey, $this->options['font'], $message );
	        imagettftext( $errorimage, "20", 0, $x+100, $y+47, $black, $this->options['font'], $message );
			if ( $this->debug )
			{
		        imagettftext( $errorimage, "14", 0, $x+21, $y+101, $grey, $this->options['font'], $errormessage );
		        imagettftext( $errorimage, "14", 0, $x+20, $y+100, $black, $this->options['font'], $errormessage );
			}
		}
		else
		{
            ImageString( $errorimage, 5, $x+101, $y+31, $message, $grey );
            ImageString( $errorimage, 5, $x+100, $y+30, $message, $black );
			if ( $this->debug )
			{
				$msgarray = explode( "\r\n", $errormessage );
				$line = $y+90;
				foreach( $msgarray as $msgline )
				{
		            ImageString( $errorimage, 3, $x+21, $line+1, $msgline, $grey );
		            ImageString( $errorimage, 3, $x+20, $line, $msgline, $black );
		            $line = $line + imagefontheight( 3 ) + 10;
		        }
			}
		}

        //-----------------------------------------
        // Flush image
        //-----------------------------------------
        header('Content-type: image/png');
        imagepng($errorimage);
        imagedestroy($errorimage);
	}
}

?>