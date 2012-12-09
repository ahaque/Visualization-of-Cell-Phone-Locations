/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.poll.js - Poll code						*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/

var _poll = window.IPBoard;

_poll.prototype.poll = {
	maxQuestions: 0,
	maxChoices: 0,
	
	questions: $H(),
	choices: $H(),
	_choices: $H(),
	votes: $H(),
	multi: $H(),
	
	effectDuration: 0.3,
	
	/*------------------------------*/
	/* Constructor 					*/
	init: function()
	{
		Debug.write("Initializing ips.poll.js");
		
		document.observe("dom:loaded", function(){
			ipb.poll.initPoll();
		});
	},
	
	/* ------------------------------ */
	/**
	 * Initialize the poll, mainly to check
	 * for an existing poll
	*/
	initPoll: function()
	{
		// Are there any questions so far?
		if( ipb.poll.questions.size() > 0 )
		{
			// Build the choices array
			ipb.poll.choices.each( function( c )
			{
				var match = c.key.match(/([0-9]+)_([0-9]+)/);
				
				if( Object.isUndefined( match[1] ) || Object.isUndefined( match[2] ) ){
					return false;
				}
				
				if( !Object.isHash( ipb.poll._choices.get( match[1] ) ) ){
					ipb.poll._choices.set( parseInt( match[1] ), $H() );
				}
				
				var votes = ipb.poll.votes.get( match[0] ) || 0;
				
				ipb.poll._choices.get( parseInt( match[1] ) ).set( parseInt( match[2] ), $H({ value: c.value, votes: votes }) );
			});
			
			// Now parse questions
			ipb.poll.questions.each( function( q )
			{
				var qid = q.key;
				var question = q.value;
				
				// Get the choices
				
				var html = ipb.templates['poll_question'].evaluate( { qid: qid, value: question } );
				$( 'poll_container' ).insert( html );
				
				// Allowing multiple choice?
				if( ipb.poll.multi.get( qid ) == 1 ){
					$('multi_' + qid).checked = true;
				}
				
				var choices = ipb.poll._choices.get( qid );
							
				choices.each( function( c )
				{
					var item = ipb.templates['poll_choice'].evaluate( { qid: qid, cid: c.key, choice: c.value.get('value'), votes: c.value.get('votes') } );
					
					$('choices_for_' + qid).insert( item );
					
					// Add Events
					$('remove_' + qid + '_' + c.key).observe('click', ipb.poll.removeChoice.bindAsEventListener( this, qid, c.key ));
					
					if( !ipb.poll.isMod || ipb.poll.isPublicPoll ){
						$('poll_' + qid + '_' + c.key + '_votes').remove();
					}
					
					// And display
					$('poll_' + qid + '_' + c.key + '_wrap').show();
				});
				
				// Add events on the question wrap
				if( $('add_choice_' + qid ) )
				{
					if( choices.size() >= ipb.poll.maxChoices ){
						$('add_choice_' + qid).hide();
					} else {
						$('add_choice_' + qid).observe('click', ipb.poll.addChoice.bindAsEventListener( this, qid ) );
					}
				}
				
				if( $('remove_question_' + qid) )
				{
					$('remove_question_' + qid).observe('click', ipb.poll.removeQuestion.bindAsEventListener( this, qid ) );
				}
				
				$('question_' + qid + '_wrap').show();
			});
		}
		
		// Update our text
		ipb.poll.updateStatus();
		
		if( $('add_new_question') ){
			$('add_new_question').observe('click', ipb.poll.addQuestion );
		}
		
		// What should we show?
		if( ipb.poll.showOnLoad ){
			$('add_poll').hide();
			$('poll_wrap').show();
		} else {
			$('poll_wrap').hide();
			$('add_poll').show();
		}
		
		$('add_poll').observe('click', ipb.poll.toggleForm);
		$('close_poll').observe('click', ipb.poll.toggleForm);
		
		// We need to hook an event onto the form for this poll,
		// so we can make sure they have entered enough choices
		var form = $('poll_form').up('form');
		
		if( !Object.isUndefined( form ) )
		{
			$( form ).observe('submit', ipb.poll.submitCheckPoll );
		}
	},
	
	/* ------------------------------ */
	/**
	 * Checks poll pre-submit to ensure proper choices
	 * are entered.
	 * 
	 * @param	{event}		e		The event
	*/
	submitCheckPoll: function(e)
	{
		var stop = false;
			
		if( ipb.poll._choices.size() > 1 )
		{
			ipb.poll._choices.each( function( q ){
				if( q.value.size() < 2 ){
					stop = true;
					return;
				}
			});
			
			if( stop == true )
			{
				alert( ipb.lang['poll_not_enough_choices'] );
				Event.stop(e);
				return false;
			}
		}
	},
	
	/* ------------------------------ */
	/**
	 * Updates the status text
	*/
	updateStatus: function()
	{
		if( !$('poll_stats') ){ return; }
		if( !ipb.lang['poll_stats'] ){ $('poll_stats').remove(); }
		
		var questions = parseInt( ipb.poll.maxQuestions ) - ipb.poll._choices.size();
		var choices = parseInt( ipb.poll.maxChoices );
		
		var string = ipb.lang['poll_stats'].gsub(/\[\q\]/, questions).gsub(/\[c\]/, choices);
		
		$('poll_stats').update( string );		
	},
		
	/* ------------------------------ */
	/**
	 * Toggle the poll form
	*/
	toggleForm: function(e)
	{
		Event.stop(e);
		
		if( $('poll_wrap').visible() ){
			$('add_poll').show();
			
			// No choices, so destroy poll
			/*if( ipb.poll._choices.size() == 0 )
			{
				ipb.poll.destroyPoll( e );
			}*/
		} else {
			$('add_poll').hide();
			
			// Add a default question
			if( ipb.poll._choices.size() == 0 )
			{
				ipb.poll.addQuestion( e, 1 );
			}
		}
		
		Effect.toggle( $('poll_wrap'), 'blind', { duration: ipb.poll.effectDuration } );
	},
	
	/* ------------------------------ */
	/**
	 * Destroy poll
	*/
	destroyPoll: function( e )
	{
		//ipb.poll.removeQuestion
	},
	
	
	/* ------------------------------ */
	/**
	 * Add a new choice
	 * 
	 * @param	{event}		event		The event
	 * @param	{int}		qid			The question ID
	 * @param	{boolean}	instant		Slide down or show instantly?
	*/
	addChoice: function(e, qid, instant)
	{
		Event.stop(e);
		
		if( !qid || !$('choices_for_' + qid) ){ return; }
		
		var newid = ipb.poll.getNextID( 'c', qid );
		
		if( ipb.poll._choices.get( qid ).size() >= ipb.poll.maxChoices ){
			alert( ipb.lang['poll_no_more_choices'] );
			return;
		}
		
		var choice = ipb.templates['poll_choice'].evaluate( { qid: qid, cid: newid, choice: '', votes: 0 } );
		$('choices_for_' + qid ).insert( choice );
		
		// Remove votes
		if( !ipb.poll.isMod || ipb.poll.isPublicPoll )
		{
			$('poll_' + qid + '_' + newid + '_votes').remove();
		}
		
		// Time to show
		if( instant ){
			$('poll_' + qid + '_' + newid + '_wrap').show();
		} else {
			new Effect.BlindDown( $('poll_' + qid + '_' + newid + '_wrap'), { duration: ipb.poll.effectDuration } );
		}
		
		// Add event
		if( $('remove_' + qid + '_' + newid) ){
			$('remove_' + qid + '_' + newid ).observe('click', ipb.poll.removeChoice.bindAsEventListener( this, qid, newid ) );
		}
		
		// Add to array
		ipb.poll._choices.get( qid ).set( newid, $H({ value: '', votes: 0 }) );
		
		ipb.poll.updateStatus();
	},
	
	/* ------------------------------ */
	/**
	 * Removes a choice
	 * 
	 * @param	{event}		e		The event
	 * @param	{int}		qid		The question ID
	 * @param	{int}		cid		The choice ID	
	*/
	removeChoice: function(e, qid, cid)
	{
		Event.stop(e);
		
		if( !qid || Object.isUndefined( cid ) || !$('poll_' + qid + '_' + cid) ){ return; }
		
		// If theres a value, check they want to delete
		if( !$F('poll_' + qid + '_' + cid).blank() )
		{
			if( !confirm( ipb.lang['delete_confirm'] ) )
			{
				return;
			}
		}			
		
		// Hide it
		new Effect.BlindUp( $('poll_' + qid + '_' + cid + '_wrap' ), { duration: ipb.poll.effectDuration, afterFinish: function(){ $('poll_' + qid + '_' + cid + '_wrap').remove(); } } );
		
		// remove it from array
		ipb.poll._choices.get( qid ).unset( cid );
		
		ipb.poll.updateStatus();
	},
	
	/* ------------------------------ */
	/**
	 * Add a new question
	 * 
	 * @param	{event}		e			The event
	 * @param	{boolean}	instant		Show instantly?
	*/
	addQuestion: function(e, instant)
	{
		Event.stop(e);
		
		if( ipb.poll._choices.size() >= ipb.poll.maxQuestions ){
			alert( ipb.lang['poll_no_more_q'] )
			return;
		}
		
		var newid = ipb.poll.getNextID('q');

		var item = ipb.templates['poll_question'].evaluate( { qid: newid, value: '' } );
		$( 'poll_container' ).insert( item );
		
		if( $('remove_question_' + newid) ){
			$('remove_question_' + newid).observe('click', ipb.poll.removeQuestion.bindAsEventListener( this, newid ) );
		}
		
		// Show it
		if( instant ){
			$('question_' + newid + '_wrap').show();
		} else {
			new Effect.BlindDown( $('question_' + newid + '_wrap'), { duration: ipb.poll.effectDuration } );
		}
		
		// Add it to array
		ipb.poll._choices.set( newid, $H() );
		
		// Lets add a choice to start them off
		ipb.poll.addChoice(e, newid, 1);
		
		// Add events on the question wrap
		if( $('add_choice_' + newid ) )
		{
			$('add_choice_' + newid).observe('click', ipb.poll.addChoice.bindAsEventListener( this, newid ) );
		}
		
		ipb.poll.updateStatus();
	},
	
	/* ------------------------------ */
	/**
	 * Removes a question
	 * 
	 * @param	{event}		e		The event
	 * @param	{int}		qid		
	*/
	removeQuestion: function(e, qid, force)
	{
		Event.stop(e);
		
		if( !$('question_' + qid + '_wrap') ){ return; }
		
		if( !force )
		{
			// Confirm it
		 	if( !confirm( ipb.lang['delete_confirm'] ) )
			{
				return;
			}
		
			// Well, ok then...
			new Effect.BlindUp( $('question_' + qid + '_wrap'), { duration: ipb.poll.effectDuration, afterFinish: function(){
				$('question_' + qid + '_wrap').remove();
			} } );
		}
		else
		{
			$('question_' + qid + '_wrap').remove();
		}
		
		// Remove from array
		ipb.poll._choices.unset( qid );
		
		ipb.poll.updateStatus();
	},
	
	/* ------------------------------ */
	/**
	 * Returns the next highest ID
	 * 
	 * @param	{string}	type		Type of ID, either q for question or c for choice
	 * @param	{int}		qid			Question ID if type is c
	*/
	getNextID: function( type, qid )
	{
		//Debug.dir( ipb.poll._choices );
		
		if( type == 'q' )
		{
			if( Object.isUndefined( ipb.poll._choices ) ){
				var max = 1;
			}
			else
			{
				var max = parseInt( ipb.poll._choices.max( function(q){
						return parseInt( q.key );
					}) ) + 1;
				
				if ( isNaN( max ) )
				{
					var max = 1;
				}
			}
		}
		else
		{
			if( Object.isUndefined( qid ) ){ return false; }
			
			if( Object.isUndefined( ipb.poll._choices.get( qid ) ) ){
				var max = 0;
			}
			else
			{
				var max = parseInt( ipb.poll._choices.get( qid ).max( function(c){
					return parseInt( c.key );
				}) ) + 1;
				
				if( isNaN( max ) ){
					max = 1;
				}
			}
		}
		
		Debug.write( max );
		return max;
	}
	
}
ipb.poll.init();