/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.rating.js - Rating class					*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/

var _rating = window.IPBoard;

_rating.prototype.rating = Class.create({
	options: 		{},
	suffix: 		'',
	_prevRateText: 	'',
	events: 		{over: [], out: [], click: []},

	/* ------------------------------ */
	/**
	 * Constructor
	 * 
	 * @param	{string}	id			The ID suffix of rating elements
	 * @param	{object}	options		Hash of options available
	*/
	initialize: function( id, options )
	{
		this.options = Object.extend({
			img_off: 			ipb.vars['rate_img_off'],
			img_on: 			ipb.vars['rate_img_on'],
			img_rated:			ipb.vars['rate_img_rated'],
			max_rate: 			5,
			cur_rating: 		0,
			show_cur_rating: 	true,
			text: 				{
									values:	[ipb.lang['rtg_poor'], ipb.lang['rtg_ok'], ipb.lang['rtg_nbad'], ipb.lang['rtg_good'], ipb.lang['rtg_awesome']],
									save: 	ipb.lang['vote_success'] || 'Vote saved!',
									update: ipb.lang['vote_updated'] || 'Vote updated!'
								},
			show_rate_text: 	true,
			rate_text_id: 		'rating_text',
			rate_text_hits: 	'rating_hits',
			rate_text_vote: 	'your_rate',
			allow_rate: 		1,
			multi_rate: 		1,
			rated: 				null
		}, arguments[1] || {});
		
		if( !options.allow_rate )
		{
			return;
		}
		
		if( !this.options['url'] )
		{
			Debug.error("Cannot initialize rating object, no polling URL supplied");
		}
		
		this.options['url']	= this.options['url'].replace( /&amp;/g, '&' );
		
		this.suffix = id;
		
		if( this.options.show_rate_text && $(this.options.rate_text_id) )
		{
			this._prevRateText = $( this.options.rate_text_id ).innerHTML;
		}
		 
		this._setUpImages();
	},
	
	/* ------------------------------ */
	/**
	 * Sets up event handlers on rating images
	*/
	_setUpImages: function()
	{
		for( i = 1; i <= this.options.max_rate; i++ )
		{
			if( !$(this.suffix + i) ){ 
				Debug.warn("No rating element found for value " + i + ". Please check the params passed to the rating object, and the HTML code to ensure appropriate elements exist.");
				break;
			}
			
			this.events['over'][i] = this._rateMouseOver.bindAsEventListener( this );
			this.events['out'][i] = this._rateMouseOut.bindAsEventListener( this );
			this.events['click'][i] = this._rateClick.bindAsEventListener( this );
			
			$(this.suffix + i).observe('mouseover', this.events['over'][i] );
			$(this.suffix + i).observe('mouseout', this.events['out'][i] );
			$(this.suffix + i).observe('click', this.events['click'][i] );
		}
	},
	
	/* ------------------------------ */
	/**
	 * Stops event handlers
	*/
	_stopTrackingEvents: function()
	{
		for( i = 1; i <= this.options.max_rate; i++ )
		{
			if( !$( this.suffix + i ) ){
				break;
			}
			
			$(this.suffix + i).stopObserving( 'mouseover', this.events['over'][i] );
			$(this.suffix + i).stopObserving( 'mouseout', this.events['out'][i] );
			$(this.suffix + i).stopObserving( 'click', this.events['click'][i] );
		}
	},
	
	/* ------------------------------ */
	/**
	 * Mouse over event
	 * 
	 * @param	{event}		e	The event
	*/
	_rateMouseOver: function(e)
	{
		img = Event.findElement(e, 'a');
		rateValue = this._getRateValue( img.id );
			
		this._highlightImages( rateValue, this.options.rated );
	},
	
	/* ------------------------------ */
	/**
	 * Mouse out event
	 * 
	 * @param	{event}		e	The event
	*/
	_rateMouseOut: function(e)
	{		
		this._highlightImages( this.options.cur_rating, false, true );
	},
	
	/* ------------------------------ */
	/**
	 * Click event event
	 * 
	 * @param	{event}		e	The event
	*/
	_rateClick: function(e)
	{
		img = Event.findElement(e, 'a');
		rateValue = this._getRateValue( img.id );
		
		this.sendRating( rateValue );
		Event.stop(e);
	},
	
	/* ------------------------------ */
	/**
	 * Sends the rating to the server
	*/
	sendRating: function( value )
	{
		if( value < 0 || value > this.options.max_rate )
		{
			Debug.error( "Invalid rating supplied" );
			return false;
		}
		
		url = this.options.url + "&rating=" + parseInt( value );		
		
		/*this.options.rated = 1;
		this._beenRated = 1;
		this._stopTrackingEvents();*/
		
		new Ajax.Request( url, {
			method: 'post',
			evalJSON: 'force',
			onSuccess: function(t)
			{
				if( Object.isUndefined( t.responseJSON ) )
				{
					if( t.responseText == 'nopermission' )
					{
						alert( ipb.lang['no_permission'] );
					}
					else
					{
						alert( ipb.lang['action_failed'] );
					}
					
					return;
				}
				
				//Debug.dir( t.responseJSON );
				if( t.responseJSON['error_key'] )
				{
					switch( t.responseJSON['error_key'] )
					{
						case 'topic_rate_no_perm':
							alert( ipb.lang['no_permission'] );
						break;
						case 'topics_no_tid':
							alert( ipb.lang['no_permission'] );
						break;
						case 'topic_rate_locked':
							alert( ipb.lang['rtg_topic_locked'] );
						break;
						case 'topic_rated_already':
							alert( ipb.lang['rtg_already'] );
						break;
						case 'user_rate_no_perm':
							alert( ipb.lang['no_permission'] );
						break;
					}
					
					return;
				}
				else
				{
					this._highlightImages( value, true, 0 );
					
					// Set the rated property dynamically
					if( this.options.rated == null ){
						if( t.responseJSON['rated'] == 'update' )
						{
							this.options.rated = 1;
						} else {
							this.options.rated = 0;
						}
					}
					
					if( this.options.show_rate_text )
					{
						if( this.options.rated ){
							$( this.options.rate_text_id ).update( this.options.text.update );
						} else {
							$( this.options.rate_text_id ).update( this.options.text.save );
						}
						
						var run_later = function(){
							$( this.options.rate_text_id ).update( this._prevRateText );
							
							if( $( this.options.rate_text_hits ) && t.responseJSON['topic_rating_hits'] ){
								$( this.options.rate_text_hits ).update( t.responseJSON['topic_rating_hits'] );
							}
							
							if( $( this.options.rate_text_vote ) ){
								$( this.options.rate_text_vote ).update( value );
							}
						}.bind( this );
						
						run_later.delay( 4 );							
					}
					
					this.options.cur_rating = value;
					this.options.rated = 1;
					this._beenRated = 1;
					
					Debug.write( this.options.multi_rate );
					
					if( !this.options.multi_rate )
					{
						this._stopTrackingEvents();
					}
				}
			}.bind(this)
		});
	},
	
	/* ------------------------------ */
	/**
	 * Gets the rating value of a rating element
	 * 
	 * @param	{string}	id		The raw id of the rating element
	 * @return	{int}				Integer value of the rating element
	*/
	_getRateValue: function( id )
	{
		id = id.sub( this.suffix, '' );
		return parseInt( id );	
	},
	
	/* ------------------------------ */
	/**
	 * Highlights rating elements as appropriate based on supplied value
	 * 
	 * @param	{int}		highlightTo		The value up to which should be highlighted
	 * @param	{boolean}	rated			Using the Rated images?
	 * @param	{boolean}	restore			Are we restoring the rating control to the previous condition?
	*/
	_highlightImages: function( highlightTo, rated, restore )
	{
		if( restore && !this.options.show_cur_rating )
		{
			highlightTo = 0;
		}
		
		for( i = 1; i <= this.options.max_rate; i++ )
		{
			if( i <= highlightTo )
			{
				if( rated )
				{
					$( this.suffix + i ).select('.rate_img')[0].src = this.options.img_rated;
				}
				else
				{
					$( this.suffix + i ).select('.rate_img')[0].src = this.options.img_on;
				}
			}
			else
			{
				$( this.suffix + i ).select('.rate_img')[0].src = this.options.img_off;
			}
		}
		
		/*if( this.options.show_rate_text && !restore )
		{
			$( this.options.rate_text_id ).update( this.options.text.values[ highlightTo - 1 ] );
		}
		else if( this.options.show_rate_text && restore )
		{
			$( this.options.rate_text_id ).update( this._prevRateText );
		}*/
	}
});
