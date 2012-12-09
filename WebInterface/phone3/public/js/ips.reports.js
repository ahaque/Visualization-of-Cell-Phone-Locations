/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.reports.js - Topic view code				*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/

var _report = window.IPBoard;

_report.prototype.reports = {
	
	init: function()
	{
		Debug.write("Initializing ips.reports.js");
		
		document.observe("dom:loaded", function(){
			if( $('report_actions') )
			{
				$('report_actions').observe('change', function( e ){
					if( $F('report_actions') == 'p' )  // Prune
					{
						$('pruneDayLabel').show();
						$('pruneDayBox').show();
						$('pruneDayLang').show();
					}
					else
					{
						$('pruneDayLabel').hide();
						$('pruneDayBox').hide();
						$('pruneDayLang').hide();
					}
				});
			}
			
			if( $('delete_report' ))
			{
				$('delete_report').observe('click', function(e){
					if( !confirm( ipb.lang['delete_confirm'] ) )
					{
						Event.stop(e);
						return false;
					}
				});
			}
			
			if( $('report_actions') && $('report_mod') )
			{
				$('report_mod').observe('click', function(e){
					if( $F('report_actions') == 'd' )
					{
						if( !confirm( ipb.lang['delete_confirm'] ) )
						{
							Event.stop(e);
							return false;
						}
					}
				});
			}
			
			$$('.status-selected').each( function(e) {
				e.hide();
			} );
			
			ipb.delegate.register('.change-status', ipb.reports.changeStatus);
		});
	},
	
	changeStatus: function(e, elem)
	{
		Event.stop(e);
		
		var reportData = elem.id.split( ':' );

		var url = ipb.vars['base_url'] + 'app=core&module=ajax&section=reports&do=change_status&id=' + reportData[0] + '&status=' + reportData[1];

		new Ajax.Request(	url,
							{
								method: 'post',
								evalJSON: 'force',
								parameters: {
									md5check: 	ipb.vars['secure_hash']
								},
								onSuccess: function(t)
								{
									if( Object.isUndefined( t.responseJSON ) )
									{
										if( t.responseText != '&nbsp;' )
										{
											alert( ipb.lang['action_failed'] );
											return;
										}
										else
										{
											return;
										}
									}
									
									try {
										$('rstat-' + reportData[0]).update( t.responseJSON['img'] );
										ipb.menus.closeAll( e );
										$('change_status-' + reportData[0] + '_menucontent').select('li').invoke('show');
										$('change_status-' + reportData[0] + '_menucontent').select('.' + reportData[1] ).invoke('hide');
									} catch(err) {
										Debug.error( err );
									}
								}
							}
						);
	}
}

ipb.reports.init();