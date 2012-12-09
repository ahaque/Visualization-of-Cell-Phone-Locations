//------------------------------------------------------------------------------
// IPS JS: Sytlistic effects
// (c) 2008 Invision Power Services, Inc.
// http://www.
// Dan Cryer - 8th September 08
//------------------------------------------------------------------------------

document.observe("dom:loaded",function() 
{
	doStriping();	
	resizeContent.defer();
});

function doStriping( )
{
	var TBL_ALT_ON  = 'acp-row-on';
	var TBL_ALT_OFF = 'acp-row-off';
	var TBL_ALT_RED = 'acp-row-red';
	var TBL_ALT_AMB = 'acp-row-amber';
	
	var ROW_ON_OFF  = true;
	
	$$('#main_content table.alternate_rows > tr, #main_content table.alternate_rows tbody > tr, .alternate_rows > li, .alternate_rows_force').each(function(tblRow) 
	{
		$(tblRow).removeClassName(TBL_ALT_ON);
		$(tblRow).removeClassName(TBL_ALT_OFF);

		if(ROW_ON_OFF)
		{
			 ROW_ON_OFF = false;
			 $(tblRow).addClassName(TBL_ALT_ON);
		}
		else
		{
			 ROW_ON_OFF = true;
			 $(tblRow).addClassName(TBL_ALT_OFF);
		}
		
		if ( $(tblRow).hasClassName('_red') )
		{
			$(tblRow).removeClassName(TBL_ALT_AMB);
			$(tblRow).removeClassName(TBL_ALT_ON);
			$(tblRow).removeClassName(TBL_ALT_OFF);
			
			$(tblRow).addClassName(TBL_ALT_RED);
		}
		else if ( $(tblRow).hasClassName('_amber') )
		{
			$(tblRow).removeClassName(TBL_ALT_RED);
			$(tblRow).removeClassName(TBL_ALT_ON);
			$(tblRow).removeClassName(TBL_ALT_OFF);
			
			$(tblRow).addClassName(TBL_ALT_AMB);
		}
	});	
}

function resizeContent()
{
	if( $('section_navigation') && $('main_content') )
	{
		var navSize = $('section_navigation').getHeight();
		var contentSize = $('main_content').getHeight();
		
		if( navSize > contentSize )
		{
			var height = navSize + 50;
			$('main_content').setStyle('min-height: ' + height + 'px;');
			
			
			if( $('copyright') )
			{
				$('copyright').setStyle('bottom: 0px;');
			}
		}
	}
}