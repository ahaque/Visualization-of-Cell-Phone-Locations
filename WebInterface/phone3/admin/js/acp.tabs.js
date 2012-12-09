//------------------------------------------------------------------------------
// IPS JS: Tab handler
// (c) 2008 Invision Power Services, Inc.
// http://www.
// Dan Cryer - 8th September 08
//------------------------------------------------------------------------------


var Ipb3AcpTabStrip = Class.create();

Ipb3AcpTabStrip.prototype = {
	
	tabstrips: $H(),
	
	initialize: function()
	{
		Debug.write('IPB3 ACP Tabs: Initialize');
	},
	
	register: function(tabstripId) 
	{
		Debug.write('IPB3 ACP Tabs: Register ' + tabstripId);
		
		try {
			if( Object.isUndefined( _go_go_gadget_editor_hack ) ){
				fix_hack = true;
			} else {
				fix_hack = false;
			}
		} catch(err) { fix_hack = false; }
		
		Debug.write( "Fix hack is " + fix_hack );
		
		var first      = true;
		var firsttab   = null;
		this.tabstrips[tabstripId] = $(tabstripId);
		this.tabstrips[tabstripId].tabs  = new Array();
		
		var tabitems = $$('#main_content #' + tabstripId + ' li');
															
		for(var i = 0; i < tabitems.length; i++)											
		{
			var tabId = tabitems[i].id.replace( /^(.*)-(\S+)$/, "$2" );
			
			Debug.write('IPB3 ACP Tabs: Adding ' + tabId + ' to tabstrip ' + this.tabstrips[tabstripId].id);
			
			this.tabstrips[tabstripId].tabs[i] = tabitems[i];
			this.tabstrips[tabstripId].tabs[i].tabStrip = this.tabstrips[tabstripId];	
			this.tabstrips[tabstripId].tabs[i].tabPane = $('tabpane-' + tabId);
			
			this.tabstrips[tabstripId].tabs[i].style.cursor = 'normal';
			Event.observe(this.tabstrips[tabstripId].tabs[i], 'click', this.toggle.bindAsEventListener( this ));
			
			if(first){
				first = false;
				firsttab = this.tabstrips[tabstripId].tabs[i];
			}
			
			this.tabstrips[tabstripId].tabs[i].initSizeVals = { 'width' : this.tabstrips[tabstripId].tabs[i].tabPane.getWidth(), 'height' : this.tabstrips[tabstripId].tabs[i].tabPane.getHeight() };
		}
		
		Debug.write('IPB3 ACP Tabs: Registered ' + this.tabstrips[tabstripId].tabs.length + ' tabs');
			
		this.doToggle(firsttab);
	},
	
	toggle: function(e)
	{
		var clickedTab = $(Event.findElement(e,'li'));
		this.doToggle(clickedTab);
	},
	
	doToggle: function(clickedTab)
	{
		Debug.write('IPB3 ACP Tabs: Toggling tab strip ' + clickedTab.tabStrip.id + ' - ' + clickedTab.id);
		
		/* Reset stuff */
		if( Prototype.Browser.Gecko && fix_hack )
		{
			//Debug.dir( clickedTab );
			/* Grab pane wrapper */
			//_paneWrap = $( $$('ul#tab_member')[0].down().id.replace( 'tabtab-', 'tabpane-' ) ).up();
			_paneWrap = $( clickedTab.tabPane ).up();
			
			/* Reset pane height */
			_paneWrap.style.height = 'auto';
			
			/* Reset save button */
			_paneWrap.down('div.acp-actionbar').relativize();
			_paneWrap.down('div.acp-actionbar').setStyle( { top: '0px', left: '0px', width: 'auto', height: 'auto' } );
		}
		
		for(var i = 0; i < clickedTab.tabStrip.tabs.length; i++)
		{
			var tabitm = clickedTab.tabStrip.tabs[i];
			
			if ( typeof( tabitm ) != 'undefined' && typeof( tabitm ) != 'function' )
			{
				if(clickedTab.id == tabitm.id)
				{
					tabitm.className = 'active';
					
					if( tabitm.tabPane != null )
					{
						if( tabitm.tabPane.hasClassName('has_editor') && Prototype.Browser.Gecko && fix_hack )
						{
							/* Goodness, what a kludge */
							tabitm.tabPane.style.visibility = 'visible'; // Hack to make sure our editor works proper like
							tabitm.tabPane.style.height = tabitm.initSizeVals['height'] + 'px';
							tabitm.tabPane.style.width  = tabitm.initSizeVals['width'] + 'px';
							tabitm.tabPane.style.clip   = "auto";
							
							/* Update size of parent to prevent background not reaching to the bottom*/
							tabitm.tabPane.down('div.editor').style.height = tabitm.initSizeVals['height'] + 'px';
							tabitm.tabPane.up('div.acp-box').style.height  = tabitm.initSizeVals['height'] + 50 + 'px';
							
							/* Now try and get the save button to place correctly */
							buttonEl = tabitm.tabPane.up().down('div.acp-actionbar');
							
							buttonEl.absolutize();
							
							/* It is offset slightly, so tweak to make it look 'normal' */
							buttonEl.style.width  = parseInt( buttonEl.style.width ) - 20 + 'px';
							buttonEl.style.height = parseInt( buttonEl.style.height ) - 20 + 'px';
							buttonEl.style.top    = parseInt( $('tab_member').positionedOffset()[1] ) + tabitm.initSizeVals['height'] + 75 + 'px';
						}
						else
						{
							tabitm.tabPane.style.display = 'block';
						}
					}
				}
				else
				{
					tabitm.className = '';
					
					if( tabitm.tabPane != null )
					{
						if( tabitm.tabPane.hasClassName('has_editor') && Prototype.Browser.Gecko && fix_hack )
						{
							tabitm.tabPane.style.visibility = 'hidden';
							tabitm.tabPane.style.height     = '0px';
							tabitm.tabPane.style.width      = '0px';
							tabitm.tabPane.style.position   = 'absolute';
							tabitm.tabPane.style.clip       = "rect(0px,0px,0px,0px)";
						}
						else
						{
							tabitm.tabPane.style.display = 'none';
						}
					}
				}
			}
		}	
	}
};

var ipbAcpTabStrips = new Ipb3AcpTabStrip;