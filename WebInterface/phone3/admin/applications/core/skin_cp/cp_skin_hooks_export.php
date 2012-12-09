<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Hooks export skin file
 * Last Updated: $Date: 2009-03-29 22:18:15 -0400 (Sun, 29 Mar 2009) $
 *
 * @author 		$Author: rikki $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Core
 * @link		http://www.
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 4343 $
 */
 
class cp_skin_hooks_export extends output
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
 * Inline settings dhtml box
 *
 * @access	public
 * @param	array 		Hook data
 * @param	array 		Form elements
 * @return	string		HTML
 */
public function inline_settings( $hook, $form=array() )
{
$IPBHTML = "";
													
$IPBHTML .= <<<EOF
<script type='text/javascript'>
	acp.hooks.fields['MF__settings'] = {};
	acp.hooks.fields['MF__settings']['fields'] = $A(['setting_groups', 'settings']);
	acp.hooks.fields['MF__settings']['url']	 = "app=core&amp;module=ajax&amp;section=hooks&amp;do=save&amp;name=settings&amp;id={$hook['hook_id']}";
	acp.hooks.fields['MF__settings']['callback'] = function( t, json ){
		$('MF__settings').innerHTML = json['message'];
		new Effect.Pulsate( $('MF__settings'), { pulses: 3 } );
	}
</script>

<div class='acp-box'>
	<h3>Add Settings</h3>
	<ul class='acp-form'>
		<li>
			Select one or more setting groups to export (all settings in the group will be exported)
		</li>
		<li style='padding-left: 15px'>
			{$form['groups']}
		</li>
		<li>
			Select one or more individual settings to export (any necessary group data will be included).  If you need to export all settings in a group, use the previous option instead.
		</li>
		<li style='padding-left: 15px'>
			{$form['settings']}
		</li>
	</ul>
	<div class='acp-actionbar'>
		<input type='submit' value=' Save ' class='realbutton' id='MF__settings_save' />
	</div>
</div>

EOF;

return $IPBHTML;
}

/**
 * Inline modules dhtml box
 *
 * @access	public
 * @param	array 		Hook data
 * @param	array 		Form elements
 * @return	string		HTML
 */
public function inline_modules( $hook, $form=array() )
{
$IPBHTML = "";
													
$IPBHTML .= <<<EOF
<script type='text/javascript'>
	acp.hooks.fields['MF__modules'] = {};
	acp.hooks.fields['MF__modules']['fields'] = $A(['modules']);
	acp.hooks.fields['MF__modules']['url']	 = "app=core&amp;module=ajax&amp;section=hooks&amp;do=save&amp;name=modules&amp;id={$hook['hook_id']}";
	acp.hooks.fields['MF__modules']['callback'] = function( t, json ){
		$('MF__modules').innerHTML = json['message'];
		new Effect.Pulsate( $('MF__modules'), { pulses: 3 } );
	}
</script>

<div class='acp-box'>
	<h3>Add Settings</h3>
	<ul class='acp-form'>
		<li>
			Select one or more modules to export
		</li>
		<li style='padding-left: 15px'>
			{$form['modules']}
		</li>
	</ul>
	<div class='acp-actionbar'>
		<input type='submit' value=' Save ' class='realbutton' id='MF__modules_save' />
	</div>
</div>

EOF;

return $IPBHTML;
}

/**
 * Inline custom script dhtml box
 *
 * @access	public
 * @param	array 		Hook data
 * @param	array 		Form elements
 * @return	string		HTML
 */
public function inline_custom( $hook, $form=array() )
{
$IPBHTML = "";
													
$IPBHTML .= <<<EOF
<script type='text/javascript'>
	acp.hooks.fields['MF__custom'] = {};
	acp.hooks.fields['MF__custom']['fields'] = $A(['custom']);
	acp.hooks.fields['MF__custom']['url']	 = "app=core&amp;module=ajax&amp;section=hooks&amp;do=save&amp;name=custom&amp;id={$hook['hook_id']}";
	acp.hooks.fields['MF__custom']['callback'] = function( t, json ){
		$('MF__custom').innerHTML = json['message'];
		new Effect.Pulsate( $('MF__custom'), { pulses: 3 } );
	}
</script>

<div class='acp-box'>
	<h3>Add Custom Script</h3>
	<ul class='acp-form'>
		<li>
			Enter in the filename of the custom install/uninstall script.  The file must be prefixed with "install_" and be placed in the hooks/ directory, but you should not enter this prefix into the form field.
		</li>
		<li style='padding-left: 15px'>
			install_ {$form['custom']}
		</li>
	</ul>
	<div class='acp-actionbar'>
		<input type='submit' value=' Save ' class='realbutton' id='MF__custom_save' />
	</div>
</div>
EOF;

return $IPBHTML;
}

/**
 * Inline help files dhtml box
 *
 * @access	public
 * @param	array 		Hook data
 * @param	array 		Form elements
 * @return	string		HTML
 */
public function inline_help( $hook, $form=array() )
{
$IPBHTML = "";
													
$IPBHTML .= <<<EOF
<script type='text/javascript'>
	acp.hooks.fields['MF__help'] = {};
	acp.hooks.fields['MF__help']['fields'] = $A(['help']);
	acp.hooks.fields['MF__help']['url']	 = "app=core&amp;module=ajax&amp;section=hooks&amp;do=save&amp;name=help&amp;id={$hook['hook_id']}";
	acp.hooks.fields['MF__help']['callback'] = function( t, json ){
		$('MF__help').innerHTML = json['message'];
		new Effect.Pulsate( $('MF__help'), { pulses: 3 } );
	}
</script>

<div class='acp-box'>
	<h3>Add Settings</h3>
	<ul class='acp-form'>
		<li>
			Select one or more help files to export
		</li>
		<li style='padding-left: 15px'>
			{$form['help']}
		</li>
	</ul>
	<div class='acp-actionbar'>
		<input type='submit' value=' Save ' class='realbutton' id='MF__help_save' />
	</div>
</div>
EOF;

return $IPBHTML;
}

/**
 * Inline tasks dhtml box
 *
 * @access	public
 * @param	array 		Hook data
 * @param	array 		Form elements
 * @return	string		HTML
 */
public function inline_tasks( $hook, $form=array() )
{
$IPBHTML = "";
													
$IPBHTML .= <<<EOF
<script type='text/javascript'>
	acp.hooks.fields['MF__tasks'] = {};
	acp.hooks.fields['MF__tasks']['fields'] = $A(['tasks']);
	acp.hooks.fields['MF__tasks']['url']	 = "app=core&amp;module=ajax&amp;section=hooks&amp;do=save&amp;name=tasks&amp;id={$hook['hook_id']}";
	acp.hooks.fields['MF__tasks']['callback'] = function( t, json ){
		$('MF__tasks').innerHTML = json['message'];
		new Effect.Pulsate( $('MF__tasks'), { pulses: 3 } );
	}
</script>

<div class='acp-box'>
	<h3>Add Tasks</h3>
	<ul class='acp-form'>
		<li>
			Select one or more tasks to export (do not forget to include the task PHP file for the user to upload)
		</li>
		<li style='padding-left: 15px'>
			{$form['tasks']}
		</li>
	</ul>
	<div class='acp-actionbar'>
		<input type='submit' value=' Save ' class='realbutton' id='MF__tasks_save' />
	</div>
</div>

EOF;

return $IPBHTML;
}

/**
 * Inline language bits dhtml box
 *
 * @access	public
 * @param	array 		Hook data
 * @param	array 		Form elements
 * @param	int			Current index
 * @return	string		HTML
 */
public function inline_languages( $hook, $form=array(), $i=0 )
{
$IPBHTML = "";
													
$IPBHTML .= <<<EOF
<script type='text/javascript'>
	acp.hooks.fields['MF__language'] = {};
	acp.hooks.fields['MF__language']['fields'] = $A([]);
	acp.hooks.fields['MF__language']['url']	 = "app=core&amp;module=ajax&amp;section=hooks&amp;do=save&amp;name=language&amp;id={$hook['hook_id']}";
	acp.hooks.fields['MF__language']['callback'] = function( t, json ){
		$('MF__language').innerHTML = json['message'];
		new Effect.Pulsate( $('MF__language'), { pulses: 3 } );
	}
	
	ipb.templates['lang_row'] = new Template("<li><table width='100%' cellpadding='0' cellspacing='0'><tr><td style='width: 30%; vertical-align: top;'>#{control}<br /><span class='desctext langdesc' id='container_desc_#{containerid}' style='display: none'>Now select strings to add &rarr;</span></td><td style='vertical-align: top;'><div id='container_#{containerid}' style='display: none'></div></td></tr></table></li>");
	
	acp.hooks.languageMax = $i;
</script>
<style type='text/css'>
	.langdesc {
		font-size: 13px;
		padding: 5px;
	}
</style>

<div class='acp-box'>
	<h3>Add Language</h3>
	<div style='max-height: 350px; overflow: auto'>
	<ul class='acp-form alternate_rows sep_rows' id='language_wrap'>
EOF;
	if( $i )
	{
		for( $k=1; $k<$i; $k++ )
		{
			$IPBHTML .= <<<EOF
			<li>
				<table width='100%' cellpadding='0' cellspacing='0'>
					<tr>
						<td style='width: 30%; vertical-align: top;'>
							{$form['language_file_' . $k ]}<br />
							<span class='desctext langdesc' id='container_desc_{$k}'>Now select strings to add &rarr;</span>
						</td>
						<td style='vertical-align: top;'>
							<div id='container_{$k}'>
								{$form['language_strings_' . $k ]}
								<script type='text/javascript'>
									acp.hooks.fields['MF__language']['fields'].push("language_{$k}");
									acp.hooks.fields['MF__language']['fields'].push("strings_{$k}");
								</script>
							</div>
						</td>
					</tr>
				</table>
			</li>
EOF;
		}
	}
	
	$IPBHTML .= <<<EOF
		<li>
			<table width='100%' cellpadding='0' cellspacing='0'>
				<tr>
					<td style='width: 30%; vertical-align: top;'>
						{$form['language_file_' . $i ]}<br />
						<span class='desctext langdesc' id='container_desc_{$i}' style='display: none'>Now select strings to add &rarr;</span>
					</td>
					<td style='vertical-align: top;'>
						<div id='container_{$i}' style='display: none'>
							{$form['language_strings_' . $i ]}
							<script type='text/javascript'>
								acp.hooks.fields['MF__language']['fields'].push("language_{$i}");
								acp.hooks.fields['MF__language']['fields'].push("strings_{$i}");
							</script>
						</div>
					</td>
				</tr>
			</table>
		</li>
	</ul>
	</div>
	<div class='acp-actionbar'>
		<input type='submit' value='Add Another File' class='realbutton' id='addLanguage' /> <input type='submit' value=' Save ' class='realbutton' id='MF__language_save' />
		<script type='text/javascript'>
			$('addLanguage').observe('click', acp.hooks.addAnotherLanguage);
		</script>
	</div>
</div>
EOF;

return $IPBHTML;
}

/**
 * Inline skin bits dhtml box
 *
 * @access	public
 * @param	array 		Hook data
 * @param	array 		Form elements
 * @param	int			Current index
 * @return	string		HTML
 */
public function inline_skins( $hook, $form=array(), $i=0 )
{
$IPBHTML = "";
													
$IPBHTML .= <<<EOF
<script type='text/javascript'>
	acp.hooks.fields['MF__templates'] = {};
	acp.hooks.fields['MF__templates']['fields'] = $A([]);
	acp.hooks.fields['MF__templates']['url']	 = "app=core&amp;module=ajax&amp;section=hooks&amp;do=save&amp;name=skins&amp;id={$hook['hook_id']}";
	acp.hooks.fields['MF__templates']['callback'] = function( t, json ){
		$('MF__templates').innerHTML = json['message'];
		new Effect.Pulsate( $('MF__templates'), { pulses: 3 } );
	}
	
	ipb.templates['skin_row'] = new Template("<li><table width='100%' cellpadding='0' cellspacing='0'><tr><td style='width: 35%; vertical-align: top;'>#{control}<br /><span class='desctext skindesc' id='s_container_desc_#{containerid}' style='display: none'>Now select bits to add &rarr;</span></td><td style='vertical-align: top;'><div id='s_container_#{containerid}' style='display: none'></div></td></tr></table></li>");
	
	acp.hooks.skinMax = $i;
</script>
<style type='text/css'>
	.skindesc {
		font-size: 13px;
		padding: 5px;
	}
</style>

<div class='acp-box'>
	<h3>Add Templates</h3>
	<div style='max-height: 350px; overflow: auto'>
	<ul class='acp-form alternate_rows sep_rows' id='skin_wrap'>
EOF;

if( $i )
{
	for( $k=1; $k<$i; $k++ )
	{
		$IPBHTML .= <<<EOF
		<li>
			<table width='100%' cellpadding='0' cellspacing='0'>
				<tr>
					<td style='width: 35%; vertical-align: top;'>
						{$form['skin_file_' . $k ]}<br />
						<span class='desctext skindesc' id='s_container_desc_{$k}'>Now select templates to add &rarr;</span>
					</td>
					<td style='vertical-align: top;'>
						<div id='s_container_{$k}'>
							{$form['skin_method_' . $k ]}
							<script type='text/javascript'>
								acp.hooks.fields['MF__templates']['fields'].push("templates_{$k}");
								acp.hooks.fields['MF__templates']['fields'].push("skin_{$k}");
							</script>
						</div>
					</td>
				</tr>
			</table>
		</li>
		
EOF;
	}
}
	$IPBHTML .= <<<EOF
		<li>
			<table width='100%' cellpadding='0' cellspacing='0'>
				<tr>
					<td style='width: 35%; vertical-align: top;'>
						{$form['skin_file_' . $i ]}<br />
						<span class='desctext skindesc' id='s_container_desc_{$i}' style='display: none'>Now select templates to add &rarr;</span>
					</td>
					<td style='vertical-align: top;'>
						<div id='s_container_{$i}' style='display: none'>
							{$form['language_strings_' . $i ]}
							<script type='text/javascript'>
								acp.hooks.fields['MF__templates']['fields'].push("templates_{$i}");
								acp.hooks.fields['MF__templates']['fields'].push("skin_{$i}");
							</script>
						</div>
					</td>
				</tr>
			</table>
		</li>
	</ul>
	</div>
	<div class='acp-actionbar'>
		<input type='submit' value='Add Another Template' class='realbutton' id='addTemplates' /> <input type='submit' value=' Save ' class='realbutton' id='MF__templates_save' />
		<script type='text/javascript'>
			$('addTemplates').observe('click', acp.hooks.addAnotherTemplate);
		</script>
	</div>
</div>
EOF;

return $IPBHTML;
}

/**
 * Inline database changes dhtml box
 *
 * @access	public
 * @param	array 		Hook data
 * @param	array 		Form elements
 * @param	int			Current index
 * @return	string		HTML
 */
public function inline_database( $hook, $form=array(), $i=0 )
{
$IPBHTML = "";
													
$IPBHTML .= <<<EOF
<script type='text/javascript'>
	acp.hooks.fields['MF__database'] = {};
	acp.hooks.fields['MF__database']['fields'] = $A([]);
	acp.hooks.fields['MF__database']['url']	 = "app=core&amp;module=ajax&amp;section=hooks&amp;do=save&amp;name=database&amp;id={$hook['hook_id']}";
	acp.hooks.fields['MF__database']['callback'] = function( t, json ){
		$('MF__database').innerHTML = json['message'];
		new Effect.Pulsate( $('MF__database'), { pulses: 3 } );
	}
	
	ipb.templates['db_row'] = new Template("<li><table width='100%' cellpadding='0' cellspacing='0'><tr><td style='width: 35%; vertical-align: top;'><select name='type_#{id}' onchange='acp.hooks.generateFields(#{id});' id='type_#{id}' class='dropdown'><option value='0'>Select One</option><option value='create'>Create Table</option><option value='alter'>Alter Table</option><option value='update'>Update Data</option><option value='insert'>Insert Data</option></select><br /><span class='desctext dbdesc' id='d_container_desc_#{id}' style='display: none'>Now select strings to add &rarr;</span></td><td style='vertical-align: top;'><div id='d_container_#{id}' style='display: none' class='dbcontainer'></div></td></tr></table></li>");
	
	ipb.templates['db_create'] = new Template("Specify the new table name (no prefix)<br /><input name='name_#{id}' id='name_#{id}' type='input' /><br /><br />Specify the fields and indexes to add to the table, just as they appear in a SQL create table statement<br /><textarea name='fields_#{id}' id='fields_#{id}'></textarea><br /><br />Specify the table type (e.g. myisam or innodb)<br /><input name='tabletype_#{id}' id='tabletype_#{id}' type='input' />");
	
	ipb.templates['db_alter'] = new Template("Specify the type of alter statement<br /><select name='altertype_#{id}' id='altertype_#{id}'><option value='add'>Add New Column</option><option value='change'>Change a Column</option><option value='remove'>Remove a Column</option></select><br /><br />Specify the table name (no prefix)<br /><input name='table_#{id}' id='table_#{id}' type='text' /><br /><br />Specify the field name<br /><input name='field_#{id}' id='field_#{id}' type='text'><br /><br />(Only if this is a change alter) specify the new field name<br /><input name='newfield_#{id}' id='newfield_#{id}' type='text'><br /><br />Specify the field definition (e.g. varchar(255))<br /><input name='fieldtype_#{id}' id='fieldtype_#{id}' type='text'><br /><br />Specify the default value<br /><input name='default_#{id}' id='default_#{id}' type='text' />");
	
	ipb.templates['db_update'] = new Template("Specify the table name (no prefix)<br /><input name='table_#{id}' id='table_#{id}' type='text'><br /><br />Specify the field name<br /><input name='field_#{id}' id='field_#{id}' type='text'><br /><br />Specify the new value<br /><input name='newvalue_#{id}' id='newvalue_#{id}' type='text'><br /><br />Specify the old value (useful to revert data during uninstall)<br /><input name='oldvalue_#{id}' id='oldvalue_#{id}' type='text'><br /><br />Specify an optional where clause if needed<br /><input name='where_#{id}' id='where_#{id}' type='text'>");
	
	ipb.templates['db_insert'] = new Template("Specify the table name (no prefix)<br /><input name='table_#{id}' id='table_#{id}' type='text' /><br /><br />Specify data to insert in format of column=value, column=value<br /><textarea name='updates_#{id}' id='updates_#{id}'></textarea><br /><br />Specify a column=value pair to look for on delete (e.g. uniqueKeyColumn='myvalue')<br /><input name='fordelete_#{id}' id='fordelete_#{id}' type='text' />");
	
	acp.hooks.dbMax = $i;
</script>

<style type='text/css'>
	.dbdesc {
		font-size: 13px;
		padding: 5px;
	}
	
	.dbcontainer input,
	.dbcontainer select,
	.dbcontainer textarea {
		margin: 4px 0px 4px 10px;
	}
</style>


<div class='acp-box'>
	<h3>Add Database Changes</h3>
	<div style='max-height: 350px; overflow: auto'>
	<ul class='acp-form alternate_rows sep_rows' id='database_wrap'>
EOF;

if( $i )
{
	for( $k=1; $k<$i; $k++ )
	{	
		$IPBHTML .= <<<EOF
		<li>
			<table width='100%' cellpadding='0' cellspacing='0'>
				<tr>
					<td style='width: 35%; vertical-align: top;'>
						{$form['type_' . $k ]}<br />
						<span class='desctext dbdesc' id='d_container_desc_{$k}'>Now modify the settings &rarr;</span>
					</td>
					<td style='vertical-align: top;'>
						<div id='d_container_{$k}' class='dbcontainer'>
EOF;
						if( $form['field_1_' . $k ] )
						{
							$IPBHTML .= <<<EOF
							{$form['description_1_' . $k ]}<br />
							{$form['field_1_' . $k ]}<br /><br />
EOF;
						}
						
						if( $form['field_2_' . $k ] )
						{
							$IPBHTML .= <<<EOF
							{$form['description_2_' . $k ]}<br />
							{$form['field_2_' . $k ]}<br /><br />
EOF;
						}
						
						if( $form['field_3_' . $k ] )
						{
							$IPBHTML .= <<<EOF
							{$form['description_3_' . $k ]}<br />
							{$form['field_3_' . $k ]}<br /><br />
EOF;
						}
						
						if( $form['field_4_' . $k ] )
						{
							$IPBHTML .= <<<EOF
							{$form['description_4_' . $k ]}<br />
							{$form['field_4_' . $k ]}<br /><br />
EOF;
						}
						
						if( $form['field_5_' . $k ] )
						{
							$IPBHTML .= <<<EOF
							{$form['description_5_' . $k ]}<br />
							{$form['field_5_' . $k ]}<br /><br />
EOF;
						}
						
						if( $form['field_6_' . $k ] )
						{
							$IPBHTML .= <<<EOF
							{$form['description_6_' . $k ]}<br />
							{$form['field_6_' . $k ]}<br /><br />
EOF;
						}
						
						$IPBHTML .= <<<EOF
							<script type='text/javascript'>
								 acp.hooks.fields['MF__database']['fields'].push( "type_{$k}" );
								 acp.hooks.fields['MF__database']['fields'].push( "name_{$k}" );
								 acp.hooks.fields['MF__database']['fields'].push( "fields_{$k}" );
								 acp.hooks.fields['MF__database']['fields'].push( "tabletype_{$k}" );
								 acp.hooks.fields['MF__database']['fields'].push( "altertype_{$k}" );
								 acp.hooks.fields['MF__database']['fields'].push( "table_{$k}" );
								 acp.hooks.fields['MF__database']['fields'].push( "field_{$k}" );
								 acp.hooks.fields['MF__database']['fields'].push( "newfield_{$k}" );
								 acp.hooks.fields['MF__database']['fields'].push( "fieldtype_{$k}" );
								 acp.hooks.fields['MF__database']['fields'].push( "default_{$k}" );
								 acp.hooks.fields['MF__database']['fields'].push( "where_{$k}" );
								 acp.hooks.fields['MF__database']['fields'].push( "newvalue_{$k}" );
								 acp.hooks.fields['MF__database']['fields'].push( "oldvalue_{$k}" );
								 acp.hooks.fields['MF__database']['fields'].push( "updates_{$k}" );
								 acp.hooks.fields['MF__database']['fields'].push( "fordelete_{$k}" );
								
								acp.hooks.dbMax = {$k};
							</script>
						</div>
					</td>
				</tr>
			</table>
		</li>
EOF;
	}
}
$IPBHTML .= <<<EOF
		<li>
			<table width='100%' cellpadding='0' cellspacing='0'>
				<tr>
					<td style='width: 35%; vertical-align: top;'>
						{$form['type_' . $i ]}<br />
						<span class='desctext dbdesc' id='d_container_desc_{$i}' style='display: none'>Now modify the settings &rarr;</span>
					</td>
					<td style='vertical-align: top;'>
						<div id='d_container_{$i}' style='display: none' class='dbcontainer'>
							{$form['language_strings_' . $i ]}
							<script type='text/javascript'>
								acp.hooks.fields['MF__database']['fields'].push("type_{$i}");
							</script>
						</div>
					</td>
				</tr>
			</table>
		</li>
	</ul>
	</div>
	<div class='acp-actionbar'>
		<input type='submit' value='Add Another Change' class='realbutton' id='addDB' /> <input type='submit' value=' Save ' class='realbutton' id='MF__database_save' />
		<script type='text/javascript'>
			$('addDB').observe('click', acp.hooks.addAnotherDB);
		</script>
	</div>
</div>


EOF;

return $IPBHTML;
}

}