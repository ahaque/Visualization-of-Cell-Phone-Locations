/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.forms.js - Javascript for forms			*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Brandon Farber (mostly)				*/
/************************************************/

var formHelper = {
	init: function()
	{
		Debug.write("Initializing acp.forms.js");
		
		document.observe("dom:loaded", function(){
			formHelper.initEvents();
		});
	},
	
	initEvents: function()
	{
		if( $('checkAll') )
		{
			$('checkAll').observe( 'click', formHelper.checkAll );
		}
		
		$$('.checkAll').each( function(check){
			check.observe( 'click', formHelper.unCheckAll );
		} )
	},
	
	checkAll: function(e)
	{
		check = Event.findElement(e, 'input');
		toCheck = $F(check);

		$$('.checkAll').each( function(check){
			if( toCheck != null )
			{
				check.checked = true;
			}
			else
			{
				check.checked = false;
			}
		});
		return false;
	},
	
	unCheckAll: function(e)
	{
		if( $('checkAll') )
		{
			var isAnyUnchecked	= false;
			
			$$('.checkAll').each( function(check){
				if( !check.checked )
				{
					isAnyUnchecked	= true;
				}
			});
			
			if( isAnyUnchecked )
			{
				$('checkAll').checked	= false;
			}
			else
			{
				$('checkAll').checked	= true;
			}
		}
		
		return false;
	}
};
	
formHelper.init();