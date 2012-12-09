<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Cleanup and rebuild tools skin file
 * Last Updated: $Date: 2009-08-31 20:32:10 -0400 (Mon, 31 Aug 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @subpackage	Core
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 5066 $
 */
 
class cp_skin_rebuild extends output
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
 * Splash screen of available tools
 *
 * @access	public
 * @return	string		HTML
 */
public function toolsSplashScreen()
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['re_230to300']}</h2>	
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm' id='theAdminForm'>
	<input type='hidden' name='do' value='300pms' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	<div class='acp-box'>
		<h3>{$this->lang->words['re_300pms']}</h3>

		<ul class='acp-form alternate_rows'>
			<li>{$this->lang->words['re_300pms_info']}</li>
		</ul>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['re_runtool']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form>
	
<div class='section_title'>
	<h2>{$this->lang->words['re_20to21']}</h2>	
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm' id='theAdminForm'>
	<input type='hidden' name='do' value='210tool_settings' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	<div class='acp-box'>
		<h3>{$this->lang->words['re_dupe2']}</h3>

		<ul class='acp-form alternate_rows'>
			<li>{$this->lang->words['re_dupe2_info']}</li>
		</ul>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['re_runtool']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form><br />

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm' id='theAdminForm'>
	<input type='hidden' name='do' value='210calevents' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['re_events']}</h3>

		<ul class='acp-form alternate_rows'>
			<li>{$this->lang->words['re_events_info']}</li>
		</ul>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['re_runtool']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form><br />

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm' id='theAdminForm'>
	<input type='hidden' name='do' value='210polls' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['re_polls']}</h3>

		<ul class='acp-form alternate_rows'>
			<li>{$this->lang->words['re_polls_info']}</li>
		</ul>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['re_runtool']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form><br />

<div class='section_title'>
	<h2>{$this->lang->words['re_1xto20']}</h2>	
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm' id='theAdminForm'>
	<input type='hidden' name='do' value='tool_settings' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['re_dupe1']}</h3>

		<ul class='acp-form alternate_rows'>
			<li>{$this->lang->words['re_dupe1_info']}</li>
		</ul>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['re_runtool']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form><br />

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm' id='theAdminForm'>
	<input type='hidden' name='do' value='tool_converge' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['re_converge']}</h3>

		<ul class='acp-form alternate_rows'>
			<li>{$this->lang->words['re_converge_info']}</li>
		</ul>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['re_runtool']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form><br />

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm' id='theAdminForm'>
	<input type='hidden' name='do' value='tool_bansettings' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['re_ban']}</h3>

		<ul class='acp-form alternate_rows'>
			<li>{$this->lang->words['re_ban_info']}</li>
		</ul>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['re_runtool']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form>
HTML;

//--endhtml//
return $IPBHTML;

}

/**
 * Rebuild content splash screen
 *
 * @access	public
 * @param	array 		Form elements
 * @param	array 		Sections we can rebuild
 * @param	array 		Sections we have rebuilt
 * @return	string		HTML
 */
public function rebuildSplashScreen( $form, $rebuildSections, $rebuiltSections )
{
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$this->lang->words['re_title']}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm'  id='theAdminForm'>
	<input type='hidden' name='do' value='docount' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['re_stats']}</h3>

		<ul class='acp-form alternate_rows'>
			
			<li>
				<label>{$this->lang->words['re_s_total']}</label>
				{$form['posts']}
			</li>

			<li>
				<label>{$this->lang->words['re_s_members']}</label>
				{$form['members']}
			</li>

			<li>
				<label>{$this->lang->words['re_s_last']}</label>
				{$form['lastreg']}
			</li>

			<li>
				<label>{$this->lang->words['re_s_most']}</label>
				{$form['online']}
			</li>
		</ul>		
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['re_stats']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form><br />

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm'  id='theAdminForm'>
	<input type='hidden' name='do' value='doresyncforums' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['re_forums']}</h3>

		<ul class='acp-form alternate_rows'>
			<li>
				<label>{$this->lang->words['re_forums']}<span class='desctext'>{$this->lang->words['re_forums_info']}</span></label>
				{$form['pergo']}&nbsp;{$this->lang->words['re_percycle']}
			</li>
		</ul>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['re_forums']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form><br />

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm'  id='theAdminForm'>
	<input type='hidden' name='do' value='doresynctopics' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['re_topics']}</h3>

		<ul class='acp-form alternate_rows'>
			<li>
				<label>{$this->lang->words['re_topics']}<span class='desctext'>{$this->lang->words['re_topics_info']}</span></label>
				{$form['pergo']}&nbsp;{$this->lang->words['re_percycle']}
			</li>
		</ul>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['re_topics']}' class='button primary' accesskey='s'>
		</div>	
	</div>
</form><br />
HTML;

if( count($rebuildSections) )
{
	$IPBHTML .= <<<HTML
<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm'  id='theAdminForm'>
	<input type='hidden' name='do' value='doposts' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['re_rebuild']}</h3>

		<ul class='acp-form alternate_rows'>
			<li>
				<label>{$this->lang->words['re_rebuildbutton']}<span class='desctext'>{$this->lang->words['re_rebuild_info']}</span></label>
				<div style='overflow:auto'>
				<ul>
					<li><input type='radio' name='type' id='type_none' checked='checked' value='0' /> <label style='float:none;' for='type_none'>{$this->lang->words['remenu_none']}</label></li>
HTML;

				foreach( $rebuildSections as $section )
				{
					$description	= '';
					
					if( in_array( $section[0], $rebuiltSections ) )
					{
						$description	= "<div class='desctext' style='color:red; margin-left: 28px;'>{$this->lang->words['noneed_rebuild_again']}</div>";
					}
					
					$IPBHTML .= <<<HTML
					<li><input type='radio' name='type' id='type_{$section[0]}' value='{$section[0]}' /> <label style='float:none;' for='type_{$section[0]}'>{$section[1]}</label>{$description}</li>
HTML;
				}
				
				$IPBHTML .= <<<HTML
				</ul>&nbsp;&nbsp;{$form['pergo']}&nbsp;{$this->lang->words['re_percycle']}</div>

			</li>
		</ul>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['re_rebuildbutton']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form><br />
HTML;
}

$IPBHTML .= <<<HTML
<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm'  id='theAdminForm'>
	<input type='hidden' name='do' value='dopostnames' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['re_user']}</h3>

		<ul class='acp-form alternate_rows'>
			<li>
				<label>{$this->lang->words['re_user']}<span class='desctext'>{$this->lang->words['re_user_info']}</span></label>
				{$form['pergo']}&nbsp;{$this->lang->words['re_percycle']}
			</li>
		</ul>		
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['re_user']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form><br />

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm'  id='theAdminForm'>
	<input type='hidden' name='do' value='doseousernames' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['re_seouser']}</h3>

		<ul class='acp-form alternate_rows'>
			<li>
				<label>{$this->lang->words['re_seouser']}<span class='desctext'>{$this->lang->words['re_seouser_info']}</span></label>
				{$form['pergo']}&nbsp;{$this->lang->words['re_percycle']}
			</li>
		</ul>		
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['re_seouser']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form><br />

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm'  id='theAdminForm'>
	<input type='hidden' name='do' value='domsgcounts' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['re_msgcount']}</h3>
	
		<ul class='acp-form alternate_rows'>
			<li>
				<label>{$this->lang->words['re_msgcount']}<span class='desctext'>{$this->lang->words['re_msgcount_info']}</span></label>
				{$form['pergo']}&nbsp;{$this->lang->words['re_percycle']}
			</li>
		</ul>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['re_msgcount']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form><br />

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm'  id='theAdminForm'>
	<input type='hidden' name='do' value='dopostcounts' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['re_count']}</h3>
	
		<ul class='acp-form alternate_rows'>
			<li>
				<label>{$this->lang->words['re_count']}<span class='desctext'>{$this->lang->words['re_count_info']}</span></label>
				{$form['pergo']}&nbsp;{$this->lang->words['re_percycle']}
			</li>
		</ul>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['re_count']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form><br />

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm'  id='theAdminForm'>
	<input type='hidden' name='do' value='dophotos' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['re_pphoto']}</h3>
	
		<ul class='acp-form alternate_rows'>
			<li>
				<label>{$this->lang->words['re_pphoto']}<span class='desctext'>{$this->lang->words['re_pphoto_info']}</span></label>
				{$form['pergo']}&nbsp;{$this->lang->words['re_percycle']}
			</li>
		</ul>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['re_pphoto']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form><br />

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm'  id='theAdminForm'>
	<input type='hidden' name='do' value='dothumbnails' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['re_thumb']}</h3>
	
		<ul class='acp-form alternate_rows'>
			<li>
				<label>{$this->lang->words['re_thumb']}<span class='desctext'>{$this->lang->words['re_thumb_info']}</span></label>
				{$form['pergo']}&nbsp;{$this->lang->words['re_percycle']}
			</li>
		</ul>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['re_thumb']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form><br />

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm'  id='theAdminForm'>
	<input type='hidden' name='do' value='doattachdata' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['re_data']}</h3>
	
		<ul class='acp-form alternate_rows'>
			<li>
				<label>{$this->lang->words['re_data']}<span class='desctext'>{$this->lang->words['re_data_info']}</span></label>
				{$form['pergo']}&nbsp;{$this->lang->words['re_percycle']}
			</li>
		</ul>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['re_data']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form><br />

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm'  id='theAdminForm'>
	<input type='hidden' name='do' value='cleanattachments' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['re_orph']}</h3>
	
		<ul class='acp-form alternate_rows'>
			<li>
				<label>{$this->lang->words['re_orph']}<span class='desctext'>{$this->lang->words['re_orph_info']}</span></label>
				{$form['pergo']}&nbsp;{$this->lang->words['re_percycle']}
			</li>
		</ul>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['re_orph']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form><br />

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm'  id='theAdminForm'>
	<input type='hidden' name='do' value='cleanavatars' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['re_av']}</h3>
	
		<ul class='acp-form alternate_rows'>
			<li>
				<label>{$this->lang->words['re_av']}<span class='desctext'>{$this->lang->words['re_av_info']}</span></label>
				{$form['pergo']}&nbsp;{$this->lang->words['re_percycle']}
			</li>
		</ul>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['re_av']}' class='button primary' accesskey='s'>
		</div>
	</div>
</form><br />

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm'  id='theAdminForm'>
	<input type='hidden' name='do' value='cleanphotos' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['re_uphoto']}</h3>

		<ul class='acp-form alternate_rows'>
			<li>
				<label>{$this->lang->words['re_uphoto']}<span class='desctext'>{$this->lang->words['re_uphoto_info']}</span></label>
				{$form['pergo']}&nbsp;{$this->lang->words['re_percycle']}
			</li>
		</ul>
		<div class='acp-actionbar'>
			<input type='submit' value='{$this->lang->words['re_uphoto']}' class='button primary' accesskey='s'>
		</div>	
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}


}