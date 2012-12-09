<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * ACP member forms skin file
 * Last Updated: $Date: 2009-04-03 05:25:46 -0400 (Fri, 03 Apr 2009) $
 *
 * @author 		$Author: matt $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Members
 * @link		http://www.
 * @since		20th February 2002
 * @version		$Rev: 4398 $
 *
 */
 
class cp_skin_member_form extends output
{

/**
 * Prevent our main destructor being called by this class
 *
 * @access	public
 * @return	void
 */
public function __destruct()
{
}

/**
 * Ban member dhtml window
 *
 * @access	public
 * @param	array 		Member data
 * @param	array 		Form data
 * @return	string		HTML
 */
public function inline_ban_member_form( $member, $form )
{
$IPBHTML = "";
																	
$IPBHTML .= <<<EOF

<form action='{$this->settings['base_url']}&amp;module=members&amp;section=members&amp;do=ban_member&amp;member_id={$member['member_id']}' method='post'>
	<div class='acp-box'>
		<h3>{$this->lang->words['sm_banmanage']}</h3>
		<ul class='acp-form'>
			<li>
				{$form['member']}
				{$this->lang->words['mf_banperm']}
			</li>
			<li>
				{$form['groups_confirm']}
				{$this->lang->words['mf_movefrom']} '{$member['_group_title']}' {$this->lang->words['mf_to']}<br />
				<span style='margin-left: 25px;'>{$form['groups']}</span>
			</li>
			<li>
				{$form['email']}
				{$this->lang->words['mf_banemail']} '{$member['email']}'
			</li>
			<li>
				{$form['name']}
				{$this->lang->words['mf_banname']} '{$member['name']}'
			</li>
EOF;
			if( $form['ips'] && count( $form['ips'] ) )
			{
				$IPBHTML .= "<li><label class='head'>IP Addresses</label></li>";
				
				foreach( $form['ips'] as $ip => $form_field )
				{
					$IPBHTML .= <<<EOF
					<li style='padding-left: 25px;'>
						{$form_field}
						{$this->lang->words['mf_banip']} '{$ip}'
					</li>
EOF;
				}
			}
			
			$IPBHTML .= <<<EOF
			<li>
				{$form['note']}
				{$this->lang->words['mf_addnote']} <span style='font-weight:normal;'>[<a href='#' onclick="return acp.openWindow('{$this->settings['board_url']}/index.php?app=members&amp;module=warn&amp;section=warn&amp;mid={$member['member_id']}&amp;do=view&amp;popup=1','980','600'); return false;">{$this->lang->words['mf_viewnotes']}</a>]</span>
				<br />
				<div style='margin-top: 5px'>{$form['note_field']}</div>
			</li>
			<li>
				<a href='{$this->settings['base_url']}&amp;module=members&amp;section=members&amp;do=banmember&amp;member_id={$member['member_id']}'>{$this->lang->words['mf_clickhere']}</a> {$this->lang->words['mf_tosuspend']}<br />
				{$this->lang->words['mf_justor']} <a href='#' onclick="new Effect.Fade( $('inlineFormWrap'), {duration: 0.3} ); acp.members.goToTab( 'tabtab-MEMBERS|7' ); return false;">{$this->lang->words['mf_clickhere']}</a> {$this->lang->words['mf_topostrestrict']}
			</li>
		</ul>
		<div class='acp-actionbar'>
			<input type='submit' value=' {$this->lang->words['mf_alterban']} ' class='realbutton' />
		</div>
	</div>
</form>
	
	
EOF;

return $IPBHTML;
}

/**
 * Edit email dhtml window
 *
 * @access	public
 * @param	array 		Member data
 * @return	string		HTML
 */
public function inline_email( $member )
{
$IPBHTML = "";
																	
$IPBHTML .= <<<EOF
<script type='text/javascript'>
	acp.members.fields['MF__email'] = {};
	acp.members.fields['MF__email']['fields'] = $A(['email']);
	acp.members.fields['MF__email']['url']	 = "app=members&amp;module=ajax&amp;section=editform&amp;do=save_email&amp;member_id={$member['member_id']}";
	acp.members.fields['MF__email']['callback'] = function( t, json ){
		$('MF__email').innerHTML = json['email'];
		new Effect.Pulsate( $('MF__email'), { pulses: 3 } );
	}
</script>

<div class='acp-box'>
	<h3>{$this->lang->words['mem_ajfo_email']}</h3>
	<ul class='acp-form'>
		<li>
			<label for='email'>{$this->lang->words['mem_ajfo_email1']}</label>
			<input type='text' size='30' id='email' name='email' value="{$member['email']}" class='input_text' />
		</li>
	</ul>
	<div class='acp-actionbar'>
		<input type='submit' value='{$this->lang->words['mf_save']}' class='realbutton' id='MF__email_save' />
	</div>
</div>

EOF;

return $IPBHTML;
}


/**
 * Upload photo dhtml window
 *
 * @access	public
 * @param	array 		Member data
 * @return	string		HTML
 */
public function inline_form_new_photo( $member )
{
$IPBHTML = "";
																	
$IPBHTML .= <<<EOF

<form action='{$this->settings['base_url']}&amp;module=members&amp;section=members&amp;do=new_photo&amp;member_id={$member['member_id']}' method='post' enctype='multipart/form-data'>
	<div class='acp-box'>
		<h3>{$this->lang->words['mem_ajfo_photo']}</h3>
		<ul class='acp-form'>
			<li>
				<label for='upload_photo'>{$this->lang->words['mf_newphoto']}</label>
				<input type='file' size='30' id='upload_photo' name='upload_photo' />
			</li>
		</ul>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['mf_save']}' class='realbutton' />
		</div>
	</div>
</form>

<!--
<div class='inlineFormEntry'>
	<div class='inlineFormLabel'>
		{$this->lang->words['mf_newphoto']}
	</div>
	<div class='inlineFormInput'>
		<input type='file' size='30' id='upload_photo' name='upload_photo' />
	</div>
</div>
<div class='inlineFormSubmit'><input type='submit' value=' {$this->lang->words['mf_upload']} ' /></div>
</form>-->
EOF;

return $IPBHTML;
}


/**
 * Edit password dhtml window
 *
 * @access	public
 * @param	array 		Member data
 * @return	string		HTML
 */
public function inline_password( $member )
{
$IPBHTML = "";
																	
$_form_new_salt       = ipsRegistry::getClass('output')->formYesNo( "new_salt", 1 );
$_form_new_pepper     = ipsRegistry::getClass('output')->formYesNo( "new_key" , 1 );

$IPBHTML .= <<<EOF

<script type='text/javascript'>
	acp.members.fields['MF__password'] = {};
	acp.members.fields['MF__password']['fields'] = $A(['password', 'password2', 'new_salt', 'new_key']);
	acp.members.fields['MF__password']['url']	 = "app=members&amp;module=ajax&amp;section=editform&amp;do=save_password&amp;member_id={$member['member_id']}";
	acp.members.fields['MF__password']['callback'] = function( t, json ){
		$('MF__password').innerHTML = json['password'];
		new Effect.Pulsate( $('MF__password'), { pulses: 3 } );
	}
</script>

<div class='acp-box'>
	<h3>{$this->lang->words['mem_ajfo_password']}</h3>
	<ul class='acp-form'>
		<li>
			<label for='password'>{$this->lang->words['mem_ajfo_password1']}</label>
			<input type='password' size='30' id='password' name='password' class='input_text' />
		</li>
		<li>
			<label for='password2'>{$this->lang->words['mem_ajfo_password2']}</label>
			<input type='password' size='30' id='password2' name='password2' class='input_text' />
		</li>
		<li>
			<label for='new_key'>{$this->lang->words['mem_afjo_new_key']}<br /><span class='desctext'>{$this->lang->words['mem_afjo_new_key_desc']}</span></label>
			{$_form_new_pepper}
			
		</li>
		<li>
			<label for='new_key'>{$this->lang->words['mem_afjo_new_salt']}</label>
			{$_form_new_salt}
		</li>
	</ul>
	<div class='acp-actionbar'>
		<input type='submit' value='{$this->lang->words['mf_save']}' class='realbutton' id='MF__password_save' />
	</div>
</div>

EOF;

return $IPBHTML;
}

/**
 * Change name dhtml window
 *
 * @access	public
 * @param	array 		Member data
 * @return	string		HTML
 */
public function inline_form_name( $member )
{
$IPBHTML = "";

$_form_send_email     = ipsRegistry::getClass('output')->formYesNo( "send_email", 1 );
$_form_email_contents = ipsRegistry::getClass('output')->formTextarea( "email_contents", $this->lang->words['mem_afjo_email_contents'] );

$IPBHTML .= <<<EOF
<script type='text/javascript'>
	acp.members.fields['MF__name'] = {};
	acp.members.fields['MF__name']['fields'] = $A(['name', 'send_email', 'email_contents']);
	acp.members.fields['MF__name']['url']	 = "app=members&amp;module=ajax&amp;section=editform&amp;do=save_name&amp;member_id={$member['member_id']}";
	acp.members.fields['MF__name']['callback'] = function( t, json ){
		$('MF__name').innerHTML = json['display_name'];
		new Effect.Pulsate( $('MF__name'), { pulses: 3 } );
	}
</script>

<div class='acp-box'>
	<h3>{$this->lang->words['mem_edit_login_name']}</h3>
	<ul class='acp-form'>
		<li>
			<label for='name'>{$this->lang->words['mem_ajfo_name']}</label>
			<input type='text' size='30' id='name' name='name' value='{$member['name']}' />
		</li>
		<li>
			<label for=''>{$this->lang->words['mem_afjo_send_email']}</label>
			{$_form_send_email}
		</li>
		<li style='text-align: center; padding: 10px;'>
			{$_form_email_contents}
			<br /><span class='desctext'>{$this->lang->words['mem_afjo_send_email_desc']}</span>
		</li>
	</ul>
	<div class='acp-actionbar'>
		<input type='submit' value='{$this->lang->words['mf_save']}' class='realbutton' id='MF__name_save' />
	</div>
</div>

EOF;

return $IPBHTML;
}


/**
 * Change display name dhtml window
 *
 * @access	public
 * @param	array 		Member data
 * @return	string		HTML
 */
public function inline_form_display_name( $member )
{
$IPBHTML = "";

$IPBHTML .= <<<EOF

<script type='text/javascript'>
	acp.members.fields['MF__member_display_name'] = {};
	acp.members.fields['MF__member_display_name']['fields'] = $A(['display_name']);
	acp.members.fields['MF__member_display_name']['url']	 = "app=members&amp;module=ajax&amp;section=editform&amp;do=save_display_name&amp;member_id={$member['member_id']}";
	acp.members.fields['MF__member_display_name']['callback'] = function( t, json ){
		$('MF__member_display_name').innerHTML = json['display_name'];
		new Effect.Pulsate( $('MF__member_display_name'), { pulses: 3 } );
	}
</script>

<div class='acp-box'>
	<h3>{$this->lang->words['mem_edit_display_name']}</h3>
	<ul class='acp-form'>
		<li>
			<label for='name'>{$this->lang->words['mem_display_name']}</label>
			<input type='text' size='30' id='display_name' name='display_name' value='{$member['members_display_name']}' />
		</li>
	</ul>
	<div class='acp-actionbar'>
		<input type='submit' value='{$this->lang->words['mf_save']}' class='realbutton' id='MF__member_display_name_save' />
	</div>
</div>

EOF;

return $IPBHTML;
}


}