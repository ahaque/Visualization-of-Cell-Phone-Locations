/************************************************/
/* IPB3 Javascript								*/
/* -------------------------------------------- */
/* ips.friends.js - Friends management code		*/
/* (c) IPS, Inc 2008							*/
/* -------------------------------------------- */
/* Author: Rikki Tissier						*/
/************************************************/

var _friends = window.IPBoard;

_friends.prototype.friends = {
	init: function()
	{
		Debug.write("Initializing ips.friends.js");
		
		document.observe("dom:loaded", function(){
			ipb.friends.initEvents();
		});
	},
	
	initEvents: function()
	{
		document.observe("ipb:friendRemoved", ipb.friends.removedFriend);
	},
	
	// Event handler for event fired when a friend is removed
	removedFriend: function(e)
	{
		if( e.memo && e.memo.friendID )
		{
			if( $('member_id_' + e.memo.friendID ) )
			{
				new Effect.Fade( $('member_id_' + e.memo.friendID), { duration: 0.5 } );
			}
		}
		Event.stop(e);
	}
}
ipb.friends.init();
