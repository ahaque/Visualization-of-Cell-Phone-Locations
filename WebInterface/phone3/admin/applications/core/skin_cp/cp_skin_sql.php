<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * SQL Toolbox skin file
 * Last Updated: $Date: 2009-05-20 21:25:47 -0400 (Wed, 20 May 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @license		http://www./community/board/license.html
 * @package		Invision Power Board
 * @subpackage	Core
 * @link		http://www.
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 4680 $
 */
 
class cp_skin_sql extends output
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
 * Search index splash page
 *
 * @access	public
 * @param	array 		Table data
 * @param	array 		Searchable applications
 * @return	string		HTML
 */
public function sqlSearchIndex( $table_data, $searchable_apps ){
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$this->lang->words['my_si_data']}</h3>

	<ul class="acp-form alternate_rows">
		<li>
			<label>{$this->lang->words['my_si_rows']}</label>
			{$table_data['Rows']}
		</li>
		
		<li>
			<label>{$this->lang->words['my_si_datasize']}</label>
			{$table_data['Data_length']}
		</li>
		
		<li>
			<label>{$this->lang->words['my_si_indexsize']}</label>
			{$table_data['Index_length']}
		</li>
	</ul>	
</div>
<br />
<div class='acp-box'>
	<h3>{$this->lang->words['my_si_searchable']}</h3>

	<ul class="acp-form alternate_rows">
HTML;

foreach( $searchable_apps as $key => $data )
{
	$data['entries'] = intval($data['entries']);
	
$IPBHTML .= <<<HTML
		<li>
			<label>{$data['title']} <span class='desctext'>{$data['entries']} {$this->lang->words['my_si_entries']}</span></label>
			<a href='{$this->settings['base_url']}{$this->form_code}&amp;do=rebuild_app_search_index&amp;app_key={$key}'>{$this->lang->words['my_si_clickrebuild']}</a>
		</li>
HTML;
}

$IPBHTML .= <<<HTML
	</ul>
</div>
HTML;

//--endhtml--//
return $IPBHTML;

}

/**
 * Result from running an SQL tool
 *
 * @access	public
 * @param	string		Page title
 * @param	array 		Columns
 * @param	array 		Rows
 * @return	string		HTML
 */
public function sqlToolResult( $title, $columns, $rows ) {
$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<HTML
<div class='acp-box'>
	<h3>{$title}</h3>

	<table class='alternate_rows real-table'>
		<tr>
HTML;

foreach( $columns as $r )
{
$IPBHTML .= <<<HTML
			<th width='*'>{$r}</th>
HTML;
}

$IPBHTML .= <<<HTML
		</tr>
		
		<tr>
HTML;

foreach( $rows as $r )
{
	$num = $num == 1 ? 2 : 1;
$IPBHTML .= <<<HTML
			<td>{$r}</td>
HTML;
}
		
$IPBHTML .= <<<HTML
		</tr>
	</table>	
</div>
<br />
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show the header for an SQL tool result
 *
 * @access	public
 * @return	string		HTML
 */
public function sqlToolResultHeader() {
$IPBHTML = "";
//--starthtml--//

$title = sprintf( $this->lang->words['my_title'], $this->true_version );

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$title}</h2>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Splash screen to create an SQL backup
 *
 * @access	public
 * @return	string		HTML
 */
public function sqlSafeBackupSplashScreen() {
$IPBHTML = "";
//--starthtml--//

$title	= sprintf( $this->lang->words['my_backuptitle'], $this->true_version );

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$title}</h2>
</div>

<div class='acp-box with_bg'>
	<h3>{$this->lang->words['my_simple_backup']}</h3>

	<p>{$this->lang->words['my_simple_backup']}</p>
	<p>{$this->lang->words['my_simple_text']}</p>
	<p><b><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=dosafebackup&amp;create_tbl={$this->request['create_tbl']}&amp;addticks={$this->request['addticks']}&amp;skip={$this->request['skip']}&amp;enable_gzip={$this->request['enable_gzip']}'>{$this->lang->words['my_start_backup']}</a></b></p>
</div>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * Show the form to create an SQL backup
 *
 * @access	public
 * @param	array 		Form elements
 * @return	string		HTML
 */
public function sqlBackupForm( $form ) {
$IPBHTML = "";
//--starthtml--//

$title	= sprintf( $this->lang->words['my_backuptitle'], $this->true_version );

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$title}</h2>
</div>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm'  id='theAdminForm'>
	<input type='hidden' name='do' value='safebackup' />
	<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['my_simple_backup']}</h3>

		<ul class="acp-form alternate_rows">
			<li>
				<label>{$this->lang->words['my_createtable']}</label>
				{$form['create_tbl']}
			</li>
			
			<li>
				<label>{$this->lang->words['my_backticks']} <span class='desctext'>{$this->lang->words['my_iferror']}</span></label>
				<input type='checkbox' name='addticks' value='1' />
			</li>
			
			<li>
				<label>{$this->lang->words['my_skinnoness']} <span class='desctext'>{$this->lang->words['my_skinnoness_notes']}</span></label>
				{$form['skip']}
			</li>
			
			<li>
				<label>{$this->lang->words['my_gzipcontent']} <span class='desctext'>{$this->lang->words['my_gzipnotes']}</span></label>
				{$form['enable_gzip']}
			</li>
		</ul>
		
		<div class="acp-actionbar"><input type='submit' value='{$this->lang->words['my_start_backup']}' class='realbutton' accesskey='s' /></div>		
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

/**
 * View results from executing an SQL query
 *
 * @access	public
 * @param	array 		Queries
 * @param	array 		Headers for the queries
 * @param	array 		Rows from the queries
 * @param	string		Page links
 * @return	string		HTML
 */
public function sqlViewResults( $queries, $headers, $rows, $pages ) {
$IPBHTML = "";
//--starthtml--//

$query	= implode( ";\n", $queries );

$title	= sprintf( $this->lang->words['my_title'], $this->true_version );

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$title}</h2>
</div>
HTML;

//-----------------------------------------
// When coming from db checker/index checker, won't be set
//-----------------------------------------

if( $this->form_code )
{
	$IPBHTML .= <<<HTML
	<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm'  id='theAdminForm'>
		<input type='hidden' name='do' value='runsql' />
		<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
		
		<div class='acp-box with_bg'>
			<h3>{$this->lang->words['my_run']}</h3>
			<textarea name='query' cols='40' rows='5'>{$query}</textarea>
			<div class='acp-actionbar'><input type='submit' value='{$this->lang->words['my_runnew']}' class='realbutton' accesskey='s' /></div>	
		</div>
	</form>
	<br />
HTML;
}

foreach( $queries as $index => $aQuery )
{
	$IPBHTML .= <<<HTML
<div>{$pages}</div>

<div class='acp-box clear'>
	<h3>{$this->lang->words['my_results']}</h3>

	<table class='alternate_rows real-table'>
		<tr>
HTML;

if( count($headers[ $index ]) )
{
	foreach( $headers[ $index ] as $r )
	{
	$IPBHTML .= <<<HTML
			<th width='*'>{$r}</th>
HTML;
	}
}

$IPBHTML .= <<<HTML
		</tr>
HTML;

if( count($rows[ $index ]) )
{
	foreach( $rows[ $index ] as $r )
	{
	$IPBHTML .= <<<HTML
		<tr>
HTML;

	foreach( $r as $_r )
	{
		$num = ( $num == 1 ) ? 2 : 1;
$IPBHTML .= <<<HTML
			<td>{$_r}</td>
HTML;
	}
	
$IPBHTML .= <<<HTML
		</tr>
HTML;
	}
}

$IPBHTML .= <<<HTML
		</tr>
	</table>
</div>
<div style='margin-top: 2px;'>{$pages}</div>
<br />
HTML;
}

//--endhtml--//
return $IPBHTML;
}

/**
 * Show the database tables (SQL Toolbox homepage)
 *
 * @access	public
 * @param	array 		Tables
 * @return	string		HTML
 */
public function sqlListTables( $rows ) {
$IPBHTML = "";
//--starthtml--//

$title	= sprintf( $this->lang->words['my_title'], $this->true_version );

$IPBHTML .= <<<HTML
<div class='section_title'>
	<h2>{$title}</h2>
</div>

<script type='text/javascript' src='{$this->settings['js_main_url']}acp.forms.js'></script>

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theForm' id='theForm'>
<input type='hidden' name='do' value='dotool' />
<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['my_tables']}</h3>

		<table class='alternate_rows'>
			<tr>
				<th width='20%'>{$this->lang->words['my_table']}</th>
				<th width='10%'>{$this->lang->words['my_rows']}</th>
				<th width='10%'>{$this->lang->words['my_export']}</th>
				<th width='10%'><input id='checkAll' type="checkbox" title="{$this->lang->words['my_checkall']}" /></th>
			</tr>
HTML;

foreach( $rows as $r )
{
$IPBHTML .= <<<HTML
			<tr>
				<td>
					<b><span style='font-size:12px'><a href='{$this->settings['base_url']}{$this->form_code}&amp;do=runsql&query={$r['query']}'>{$r['table']}</a></span></b>					
				</td>
				<td>{$r['rows']}</td>
				<td><a href='{$this->settings['base_url']}{$this->form_code}&do=export_tbl&tbl={$r['table']}'>{$this->lang->words['my_export']}</a></b></td>
				<td><input name="tbl_{$r['table']}" value='1' type='checkbox' class='checkAll' /></td>
			</tr>
HTML;
}

$IPBHTML .= <<<HTML
		</table>
		<div class='acp-actionbar'>
			<div class='rightaction'>
				<select name='tool'>
					<option value='optimize'>{$this->lang->words['my_optimize']}</option>
					<option value='repair'>{$this->lang->words['my_repair']}</option>
					<option value='check'>{$this->lang->words['my_check']}</option>
					<option value='analyze'>{$this->lang->words['my_analyze']}</option>
				</select>
				<input type='submit' value='{$this->lang->words['my_go']}' class='realbutton' />
			</div>
		</div>	
	</div>
</form>

<br />

<form action='{$this->settings['base_url']}{$this->form_code}' method='post' name='theAdminForm'  id='theAdminForm'>
<input type='hidden' name='do' value='runsql' />
<input type='hidden' name='_admin_auth_key' value='{$this->registry->adminFunctions->generated_acp_hash}' />
	
	<div class='acp-box'>
		<h3>{$this->lang->words['my_run']}</h3>

		<ul class="acp-form alternate_rows">
			<li>
				<label>{$this->lang->words['my_manadvanced']}</label>
				<textarea name='query' cols='40' rows='5'></textarea>
			</li>
		</ul>		
		
		<div class='acp-actionbar'><input type='submit' value='{$this->lang->words['my_run']}' class='realbutton' accesskey='s' /></div>
	</div>
</form>
HTML;

//--endhtml--//
return $IPBHTML;
}

}