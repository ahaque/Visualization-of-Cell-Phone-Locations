<?php
/**
 * Invision Power Services
 * IP.Board v3.0.3
 * Setup skin file
 * Last Updated: $Date: 2009-07-16 10:24:48 -0400 (Thu, 16 Jul 2009) $
 *
 * @author 		$Author: bfarber $
 * @copyright	(c) 2001 - 2009 Invision Power Services, Inc.
 * @package		Invision Power Board
 * @since		Friday 19th May 2006 17:33
 * @version		$Revision: 4900 $
 */
 
class skin_setup extends output
{

	/**
	 * Show no button
	 *
	 */
	 private $_showNoButtons = FALSE;
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
 * Show install complete page
 *
 * @access	public
 * @param	array
 * @return	string		HTML
 */
public function upgrade_complete( $options ) {

$IPBHTML = "";
//--starthtml--//

$_productName    = $this->registry->fetchGlobalConfigValue('name');

$IPBHTML .= <<<EOF
<div class='message unspecified'>
EOF;
	foreach( $options as $app => $_bleh )
	{
		foreach( $options[ $app ] as $num => $data )
		{
			if ( ! $data['out'] )
			{
				continue;
			}
			
			if ( $data['app']['key'] == 'core' )
			{
				$data['app']['name'] = 'IP.Board';
			}
			
			$IPBHTML .= <<<EOF
				<strong style='font-weight:bold; font-size:14px'>Messages</strong>
				<p>{$data['out']}</p>
EOF;

		}
	}

$IPBHTML .= <<<EOF
<p>Congratulations, <a href='../../index.php'>your upgrade is complete!</a></p>
</div>
<br />
<span class='done_text'>Upgrade complete!</span>
EOF;

$IPBHTML .= <<<EOF
    <h3>Useful Links</h3>
    <ul id='links'>
        <li><img src='{$this->registry->output->imageUrl}/link.gif' align='absmiddle' /> <a href='http://external./ipboard30/landing/?p=clientarea'>Client area</a></li>
        <li><img src='{$this->registry->output->imageUrl}/link.gif' align='absmiddle' /> <a href='http://external./ipboard30/landing/?p=docs-ipb'>Documentation</a></li>
        <li><img src='{$this->registry->output->imageUrl}/link.gif' align='absmiddle' /> <a href='http://external./ipboard30/landing/?p=forums'>IPS Company Forum</a></li>
    </ul>
EOF;

return $IPBHTML;
}

/**
 * Show the install start page
 *
 * @access	public
 * @return	string		HTML
 */
public function upgrade_manual_queries( $queries, $sourceFile='' ) {

$IPBHTML = "";
//--starthtml--//

$or = '';

$IPBHTML .= <<<EOF
<h3>Please run these queries before continuing</h3>
<div class='message unspecified'>
EOF;
	if ( $sourceFile )
	{
		$or = '<u>OR</u> ';
		
		$IPBHTML .= <<<EOF
		<strong>Run this source file</strong>
		<input type='text' size='100' style='width:98%' value='source {$sourceFile};' />
		<br />
EOF;
	}
$IPBHTML .= <<<EOF
	<strong>{$or}Individual Queries</strong>
	<textarea style="width:100%; height: 300px">
EOF;

if ( $queries )
{
	$IPBHTML .= "\n" . $queries;
}

$IPBHTML .= <<<EOF
	</textarea>
</div>
EOF;

return $IPBHTML;
}


/**
 * Show the install start page
 *
 * @access	public
 * @return	string		HTML
 */
public function upgrade_ready( $name, $current, $latest) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
The upgrader is now ready to start the upgrade of <strong>$name</strong>
<br />Current Version: v{$current}
<br />Latest Version: v{$latest}
<br />
<div class='message unspecified'>
	<strong>Upgrade Options</strong>
	<ul>
		<li>
			<input type='checkbox' name='man' value='1' />
			Show me manual upgrade steps for SQL queries to prevent PHP page timeouts. <b>WARNING:</b> If you select this option, you will be shown SQL queries that you must run at your mysql command line.  If you are not comfortable doing this, please submit a ticket and our technicians will assist you, or contact your webhost for assistance.
		</li>
		<li>
			<input type='checkbox' name='helpfile' value='1' checked="checked" />
			Update my help files if changes are found
		</li>
	</ul>
</div>
<br />

<div style='float: right'>
	<input type='submit' class='nav_button' value='Start Upgrade...'>
</div>
EOF;

return $IPBHTML;
}

/**
 * Show the upgrade app options
 *
 * @access	public
 * @return	string		HTML
 */
public function upgrade_appsOptions( $options ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
You have the following options:
<div class='message unspecified'>
EOF;
	foreach( $options as $app => $_bleh )
	{
		foreach( $options[ $app ] as $num => $data )
		{
			if ( $data['app']['key'] == 'core' )
			{
				$data['app']['name'] = 'IP.Board';
			}
			
			$IPBHTML .= <<<EOF
				<strong style='font-weight:bold; font-size:14px'>{$data['app']['name']} {$data['long']}</strong>
				{$data['out']}
EOF;

		}
	}

$IPBHTML .= <<<EOF
</div>
EOF;

return $IPBHTML;
}

/**
 * Show the upgrader applications page
 *
 * @access	public
 * @param	array 		Applications
 * @return	string		HTML
 */
public function upgrade_apps( $apps ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='message' style='margin-top: 4px;'>
	Please select the applications you wish to upgrade.
</div>
EOF;
	foreach( array( 'core', 'ips', 'other' ) as $type )
	{
		switch( $type )
		{
			case 'core':
				$title = "Default Applications";
			break;
			case 'ips':
				$title = "IPS Applications";
			break;
			case 'other':
				$title = "Third Party Applications";
			break;
		}
		
		if ( count( $apps[ $type ] ) )
		{
			$IPBHTML .= <<<EOF
			<fieldset>
                <legend>{$title}</legend>
EOF;
		
		
			foreach( $apps[ $type ] as $key => $data )
			{
				if ( $type == 'core' )
				{
					if ( $key == 'core' )
					{
						$data['name'] = 'IP.Board';
					}
					else
					{
						continue;
					}
				}
				
				$_upav    = ( $data['_vnumbers']['current'][0] >= $data['_vnumbers']['latest'][0] ) ? 0 : 1;
				$upgrade  = ( ! $_upav ) ? "Up To Date" : "Upgrade to {$data['_vnumbers']['latest'][1]}";
				$_checked = ( $type == 'core' AND $key == 'core' AND ( $_upav ) ) ? ' checked="checked" onclick="this.checked=true"' : '';
				$_style   = ( ! $data['_vnumbers']['current'][0] OR ( ! $_upav ) ) ? 'display:none' : '';
				
				/* Not installed? */
				if ( ! $data['_vnumbers']['current'][0] )
				{
					$upgrade = "Cannot upgrade. Not installed";
					$data['_vnumbers']['current'][1] = '';
				}

//-----------------------------------------
// Yes, I know this wouldn't work for "core"
// apps, but we can just use the global folder
// for them so it's irrelevant
//-----------------------------------------

$img = file_exists( IPSLib::getAppDir( $key ) . '/skin_cp/appIcon.png' ) ? $this->settings['base_url'] . '/' . CP_DIRECTORY . '/applications_addon/' . $type . '/' . $key . '/skin_cp/appIcon.png' : "../skin_cp/_newimages/applications/{$key}.png";

$IPBHTML .=  <<<EOF
					<table style='width: 100%; border: 0px; padding:0px' cellspacing='0'>
					<tr>
						<td width='7%' valign='top' style='padding:4px'>
							<input type='checkbox' name='apps[{$key}]' value='1' $_checked style="$_style" />
						</td>
						<td width='1%' valign='top' style='padding:4px'>
							<img src='{$img}' />
						</td>
       		 	        <td width='50%' class='content'>
                    		<strong style='font-size:12px'>{$data['name']}</strong> <span style='color:gray'>{$data['_vnumbers']['current'][1]}</span>
                    	</td>
						<td width='49%' style='padding:4px'>
							$upgrade
						</td>
                	</tr>
					</table>
EOF;
			}
		
		
		$IPBHTML .=  <<<EOF
		    </fieldset>
EOF;
		}
	}

	return $IPBHTML;
}

/**
 * Show the upgrade overview page
 *
 * @access	public
 * @param	bool		Files ok
 * @param	bool		Extensions ok
 * @param	array 		Extensions
 * @return	string		HTML
 */
public function upgrade_overview( $filesOK, $extensionsOK, $extensions=array()) {

$minPHP = IPSSetUp::minPhpVersion;
$minSQL = IPSSetUp::minDb_mysql;

$prefPHP = IPSSetUp::prefPhpVersion;
$prefSQL = IPSSetUp::prefDb_mysql;

$_filesOK      = ( $filesOK === NULL )       ? "<span style='color:gray'>Not yet checked</span>" : ( ( $filesOK === FALSE ) ? "<span style='color:red'>Failed</span>" : "<span style='color:green'>Passed</span>" );
$_extensionsOK = ( $extensionsOK === FALSE ) ? "<span style='color:red'>Failed</span>" : "<span style='color:green'>Passed</span>";

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='message unspecified'>
	<strong>System Requirements</strong>
	<br />
	<strong>PHP:</strong> v{$minPHP} or better<br />
	<strong>SQL:</strong> MySQL v$minSQL ($prefSQL or better preferred)
	<br />
	<br />
	<strong>Pre-Install Check: Files</strong>
	<br />
	<em>Required Files:</em> {$_filesOK}
	<br />
	<br />
	<strong>Pre-Install Check: PHP Extensions</strong>
	<br />
	<em>PHP Extensions Overview:</em> {$_extensionsOK}
EOF;
	
foreach( $extensions as $xt )
{
	if ( $xt['_ok'] !== TRUE )
	{
		$IPBHTML .= "<br />{$xt['prettyname']} ({$xt['extensionname']}): <span style='color:red'>FAILED</span> (<a href='{$xt['helpurl']}' target='_blank'>Click for more info</a>)";
	}
	else
	{
		$IPBHTML .= "<br />{$xt['prettyname']} ({$xt['extensionname']}): <span style='color:green'>Pass</span>";
	}
}

$IPBHTML .= <<<EOF
</div>
EOF;

return $IPBHTML;
}

/**
 * Log in page
 *
 * @access	public
 * @return	string		HTML
 */
public function upgrade_login_200plus( $loginType ) {

$IPBHTML = "";
//--starthtml--//

$label = ( $loginType == 'username' ) ? 'User Name' : 'Email Address';

$IPBHTML .= <<<EOF
	<input type='hidden' name='do' value='login' />
	<div class='message'>
		Welcome to the upgrade system. This wizard will guide you through the upgrade process.
	</div>
	<br />
	  <fieldset>
      <legend>Log In</legend>
      <table style='width: 100%; border: 0px; padding:0px' cellspacing='0'>
          <tr>
              <td width='30%' class='title'>{$label}:</td>
              <td width='70%' class='content'><input type='text' class='sql_form' name='username' value=''></td>
          </tr>

      	<tr>
              <td width='30%' class='title'>Password</td>
              <td width='70%' class='content'><input type='password' class='sql_form' name='password' value=''></td>
          </tr>
      </table>
  </fieldset>
EOF;

return $IPBHTML;
}

/**
 * Log in page
 *
 * @access	public
 * @return	string		HTML
 */
public function upgrade_login_300plus( $additional_data, $replace_form ) {

$IPBHTML = "";
//--starthtml--//

if( $replace_form )
{
	$IPBHTML .= $additional_data[0];
}
else
{
	$IPBHTML .= <<<EOF
	<input type='hidden' name='do' value='login' />
	<div class='message'>
		Welcome to the upgrade system. This wizard will guide you through the upgrade process.
	</div>
	<br />
	  <fieldset>
      <legend>Log In</legend>
		<div id='login_controls'>
			<label for='username'>Sign In Name</label>
			<input type='text' size='20' id='username' name='username' value=''>

			<label for='password'>Password</label>
			<input type='password' size='20' id='password' name='password' value=''>
EOF;

		if( count($additional_data) > 0 )
		{
			foreach( $additional_data as $form_html )
			{
				$IPBHTML .= $form_html;
			}
		}
		
$IPBHTML .= <<<EOF
      </div>
  </fieldset>
EOF;
}

return $IPBHTML;
}

/**
 * Show error page
 *
 * @access	public
 * @param	string		Error message
 * @return	string		HTML
 */
public function page_error($msg) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
	<div class='message error'>
		$msg
	</div>
EOF;

return $IPBHTML;
}

/**
 * Show locked page
 *
 * @access	public
 * @return	string		HTML
 */
public function page_locked() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
	<div class='message error'>
		INSTALLER LOCKED<br />Please delete the file "cache/installer_lock.php" to continue.
	</div>
EOF;

return $IPBHTML;
}

/**
 * Show install complete page
 *
 * @access	public
 * @param	bool		Installer was locked successfully
 * @return	string		HTML
 */
public function page_installComplete( $installLocked ) {

$IPBHTML = "";
//--starthtml--//

$_productName    = $this->registry->fetchGlobalConfigValue('name');

if ( ! $installLocked )
{
	$extra = "<div class='message error'>
				INSTALLER NOT LOCKED<br />Please disable or remove 'admin/install/index.php' immediately!
			  </div>";
}

$IPBHTML .= <<<EOF
	<br />

    <span class='done_text'>Installation complete!</span><Br /><Br />
    Congratulations, your <a href='../../index.php'>{$_productName}</a> is now installed and ready to use! Below are some 
    links you may find useful.<br /><br /><br />
    $extra
    <h3>Useful Links</h3>
    <ul id='links'>
        <li><img src='{$this->registry->output->imageUrl}/link.gif' align='absmiddle' /> <a href='http://external./ipboard30/landing/?p=clientarea'>Client area</a></li>
        <li><img src='{$this->registry->output->imageUrl}/link.gif' align='absmiddle' /> <a href='http://external./ipboard30/landing/?p=docs-ipb'>Documentation</a></li>
        <li><img src='{$this->registry->output->imageUrl}/link.gif' align='absmiddle' /> <a href='http://external./ipboard30/landing/?p=forums'>IPS Company Forum</a></li>
    </ul>
EOF;

return $IPBHTML;
}

/**
 * Show the install start page
 *
 * @access	public
 * @return	string		HTML
 */
public function page_install() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
	The installer is now ready to complete the installation of IP.Board. Click <strong>Start</strong> to 
	begin the automatic process!<br /><br />


	      <div style='float: right'>
           <input type='submit' class='nav_button' value='Start installation...'>
       </div>
EOF;

return $IPBHTML;
}

/**
 * Show the admin info page
 *
 * @access	public
 * @return	string		HTML
 */
public function page_admin() {

$IPBHTML = "";
//--starthtml--//

$username	= htmlspecialchars($_REQUEST['username']);
$email		= htmlspecialchars($_REQUEST['email']);

$IPBHTML .= <<<EOF
	<div class='message'>
		Please complete the form carefully.<br />The details you enter here will be used to log into the board and ACP.
	</div>
	<br />
	<fieldset>
	    <legend>Your administrative account</legend>
            <table style='width: 100%; border: 0px; padding:0px' cellspacing='0'>
                <tr>
                    <td width='30%' class='title'>Username:</td>

                    <td width='70%' class='content'><input type='text' class='sql_form' name='username' value='{$username}'></td>
                </tr>
                <tr>
                    <td class='title'>Password:</td>
                    <td class='content'><input type='password' class='sql_form' name='password'></td>
                </tr>
                <tr>
                    <td class='title'>Confirm Password:</td>

                    <td class='content'><input type='password' class='sql_form' name='confirm_password'></td>
                </tr>
                <tr>
                    <td class='title'>E-mail Address:</td>
                    <td class='content'><input type='text' class='sql_form' name='email' value='{$email}'></td>
                </tr>
            </table>
        </fieldset>
EOF;

return $IPBHTML;
}

/**
 * Show the DB override page
 *
 * @access	public
 * @return	string		HTML
 */
public function page_dbOverride() {

$IPBHTML = "";
//--starthtml--//

$url = IPSSetUp::getSavedData('install_url');

$IPBHTML .= <<<EOF
	<div class='message'>
		 The database (<em>{$this->request['db_name']}</em>) you are attempting to install into has existing tables using the same prefix (<em>{$this->request['db_pre']}</em>).
		<br />You can either select to overwrite or choose a new database or table prefix.
		<br /><span style='font-weight:bold'>Or</span> did you mean to <a class='color:gray' href='{$url}/admin/upgrade/index.php'>upgrade</a>
	</div>
	<br />
	<fieldset>
		<legend>Database Override</legend>
		<table style='width: 100%; border: 0px; padding:0px' cellspacing='0'>
			<tr>
               <td width='70%' class='title'>Overwrite current database with new installation</td>
               <td width='3 0%' class='content'><input type='checkbox' class='sql_form' value='1' name='overwrite' ></td>
           </tr>
		</table>
	</fieldset>
	<br />
	<fieldset>
		<legend>Or Modify Your Database Details</legend>
		<table style='width: 100%; border: 0px; padding:0px' cellspacing='0'>
			<tr>
	               <td width='30%' class='title'>SQL Host:</td>
	               <td width='70%' class='content'>
	               	<input type='text' class='sql_form' value='{$this->request['db_host']}' name='db_host'>
	               </td>
	           </tr>
			<tr>
	           <td class='title'>Database Name:</td>
               <td class='content'>
               	<input type='text' class='sql_form' name='db_name' value='{$this->request['db_name']}'>
               </td>
           </tr>
           <tr>
               <td class='title'>SQL Username:</td>
               <td class='content'>
               	<input type='text' class='sql_form' name='db_user' value='{$this->request['db_user']}'>
               </td>
           </tr>
           <tr>
               <td class='title'>SQL Password:</td>
               <td class='content'>
               	<input type='password' class='sql_form' name='db_pass' value='{$_REQUEST['db_pass']}'>
               </td>
           </tr>
           <tr>
               <td class='title'>SQL Table Prefix:</td>
               <td class='content'>
               	<input type='text' class='sql_form' name='db_pre' value='{$this->request['db_pre']}'>
               </td>
           </tr>
        <!--{EXTRA.SQL}-->
		</table>
	</fieldset>
EOF;

return $IPBHTML;
}


/**
 * Collect DB info
 *
 * @access	public
 * @return	string		HTML
 */
public function page_db() {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
	<div class='message'>
		     Ask your webhost if you are unsure about any of these settings. You must create the database before installing.
		  </div>
		<br />
		   <fieldset>
		       <legend>Database details</legend>
		       <table style='width: 100%; border: 0px; padding:0px' cellspacing='0'>
		           <tr>
		               <td width='30%' class='title'>SQL Host:</td>
		               <td width='70%' class='content'>
		               	<input type='text' class='sql_form' value='{$this->request['db_host']}' name='db_host'>
		               </td>
		           </tr>
		           <tr>
		               <td class='title'>Database Name:</td>
		               <td class='content'>
		               	<input type='text' class='sql_form' name='db_name' value='{$this->request['db_name']}'>
		               </td>
		           </tr>
		           <tr>
		               <td class='title'>SQL Username:</td>
		               <td class='content'>
		               	<input type='text' class='sql_form' name='db_user' value='{$this->request['db_user']}'>
		               </td>
		           </tr>
		           <tr>
		               <td class='title'>SQL Password:</td>
		               <td class='content'>
		               	<input type='password' class='sql_form' name='db_pass' value='{$this->request['db_pass']}'>
		               </td>
		           </tr>
		           <tr>
		               <td class='title'>SQL Table Prefix:</td>
		               <td class='content'>
		               	<input type='text' class='sql_form' name='db_pre' value='{$this->request['db_pre']}'>
		               </td>
		           </tr>
		<!--{EXTRA.SQL}-->
		       </table>
		   </fieldset>
EOF;

return $IPBHTML;
}


/**
 * Check the database to use
 *
 * @access	public
 * @param	array 		Available DB drivers
 * @return	string		HTML
 */
public function page_check_db( $drivers ) {

	$_drivers = '';

	foreach ($drivers as $k => $v)
	{
		$selected  = ($v == "Mysql") ? " selected='selected'" : "";
		$_drivers .= "<option value='".$v."'".$selected.">".strtoupper($v)."</option>\n";
	}


$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
	<div class='message'>
            Please select which database engine you wish to use.
        </div>
        <br />
        <fieldset>
            <legend>Database Engine</legend>
            <table style='width: 100%; border: 0px; padding:0px' cellspacing='0'>
			<tr>
                    <td width='30%' class='title'>SQL Driver:</td>
                    <td width='70%' class='content'>
                    	<select name='sql_driver' class='sql_form'>$_drivers</select>
                    </td>
                </tr>
            </table>
        </fieldset>
EOF;

return $IPBHTML;
}

/**
 * Show the EULA
 *
 * @access	public
 * @return	string		HTML
 */
public function page_eula() {

$_eula = nl2br( $this->registry->fetchGlobalConfigValue('license') );

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
	<script language='javascript'>
	check_eula = function()
	{
		if( document.getElementById( 'eula' ).checked == true )
		{
			return true;
		}
		else
		{
			alert( 'You must agree to the license before continuing' );
			return false;
		}
	}
	document.getElementById( 'install-form' ).onsubmit = check_eula;
	</script>

	Please read and agree to the End User License Agreement before continuing.<br /><br />


	<div class='eula'>
	    $_eula        		 	                
    </div>
    <input type='checkbox' name='eula' id='eula'> <strong><label for='eula'>I agree to the license agreement</label></strong>

EOF;

return $IPBHTML;
}

/**
 * Show the address info page
 *
 * @access	public
 * @param	string		Directory
 * @param	string		URL
 * @return	string		HTML
 */
public function page_address( $dir, $url ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
	  <fieldset>
      <legend>Address details</legend>

      <table style='width: 100%; border: 0px; padding:0px' cellspacing='0'>
          <tr>
              <td width='30%' class='title'>Install Directory:</td>
              <td width='70%' class='content'><input type='text' class='sql_form' name='install_dir' value='{$dir}'></td>
          </tr>

      	<tr>
              <td width='30%' class='title'>Install Address:</td>
              <td width='70%' class='content'><input type='text' class='sql_form' name='install_url' value='{$url}'></td>
          </tr>
      </table>
  </fieldset>
EOF;

return $IPBHTML;
}

/**
 * Show the applications page
 *
 * @access	public
 * @param	array 		Applications
 * @return	string		HTML
 */
public function page_apps( $apps ) {

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div class='message' style='margin-top: 4px;'>
	Please select the applications you wish to install.<br />The following applications have been detected:
</div>
EOF;
	foreach( array( 'core', 'ips', 'other' ) as $type )
	{
		switch( $type )
		{
			case 'core':
				$title = "Default Applications";
			break;
			case 'ips':
				$title = "IPS Applications";
			break;
			case 'other':
				$title = "Third Party Applications";
			break;
		}
		
		if ( count( $apps[ $type ] ) )
		{
			$IPBHTML .= <<<EOF
			<fieldset>
                <legend>{$title}</legend>
EOF;
		
		
			foreach( $apps[ $type ] as $key => $data )
			{
			$_checked = ( $type == 'core' OR $type == 'ips' ) ? ' checked="checked" ' : '';
			$_style   = ( $type == 'core' ) ? 'display:none' : '';

//-----------------------------------------
// Yes, I know this wouldn't work for "core"
// apps, but we can just use the global folder
// for them so it's irrelevant
//-----------------------------------------

$img = file_exists( IPSLib::getAppDir( $key ) . '/skin_cp/appIcon.png' ) ? '../applications_addon/' . $type . '/' . $key . '/skin_cp/appIcon.png' : "../skin_cp/_newimages/applications/{$key}.png";

$IPBHTML .=  <<<EOF
					<table style='width: 100%; border: 0px; padding:0px' cellspacing='0'>
					<tr>
       		 	        <td width='5%' class='title'>
							<input type='checkbox' name='apps[{$key}]' value='1' $_checked style="$_style" />
						</td>
						<td width='1%' valign='top' style='padding:4px'>
							<img src='{$img}' />
						</td>
       		 	        <td width='70%' class='content'>
                    		<strong>{$data['name']}</strong> <span style='color:gray'><em>By: {$data['author']}</em></span><div style='color:#777'>{$data['description']}</div>
                    	</td>
                	</tr>
					</table>
EOF;
			}
		
		
		$IPBHTML .=  <<<EOF
		    </fieldset>
EOF;
		}
	}

	return $IPBHTML;
}
	
/**
 * Show the requirements page
 *
 * @access	public
 * @param	bool		Files ok
 * @param	bool		Extensions ok
 * @param	array 		Extensions
 * @return	string		HTML
 */
public function page_requirements( $filesOK, $extensionsOK, $extensions=array(), $text='installation' ) {

$minPHP = IPSSetUp::minPhpVersion;
$minSQL = IPSSetUp::minDb_mysql;

$prefPHP = IPSSetUp::prefPhpVersion;
$prefSQL = IPSSetUp::prefDb_mysql;

$_filesOK      = ( $filesOK === NULL )       ? "<span style='color:gray'>Not yet checked</span>" : ( ( $filesOK === FALSE ) ? "<span style='color:red'>Failed</span>" : "<span style='color:green'>Passed</span>" );
$_extensionsOK = ( $extensionsOK === FALSE ) ? "<span style='color:red'>Failed</span>" : ( $extensionsOK === TRUE ? "<span style='color:green'>Passed</span>" : "<span style='color:orange;'>Warnings</span>" );

$IPBHTML = "";
//--starthtml--//

$IPBHTML .= <<<EOF
<div>
    <div>
        Welcome to the installer. This wizard will guide you through the {$text} process.
    </div>
    <div class='message unspecific note'>
    	If you need help using this installer, please see our <a href='http://external./ipboard30/landing/?p=installation-guide' target='_blank'><b>installation guide</b></a>.
    </div>
</div>
<br />
<div class='message unspecified'>
	<strong>System Requirements</strong>
	<br />
	<strong>PHP:</strong> v{$minPHP} or better<br />
	<strong>SQL:</strong> MySQL v$minSQL ($prefSQL or better preferred)
	<br />
	<br />
	<strong>Pre-Install Check: Files</strong>
	<br />
	<em>Required Files:</em> {$_filesOK}
	<br />
	<br />
	<strong>Pre-Install Check: PHP Extensions</strong>
	<br />
	<em>PHP Extensions Overview:</em> {$_extensionsOK}
EOF;
	
foreach( $extensions as $xt )
{
	if ( $xt['_ok'] !== TRUE )
	{
		if ( $xt['_ok'] !== 1 )
		{
			$IPBHTML .= "<br />{$xt['prettyname']} ({$xt['extensionname']}): <span style='color:red'>FAILED</span> (<a href='{$xt['helpurl']}' target='_blank'>Click for more info</a>)";
		}
		else
		{
			$IPBHTML .= "<br />{$xt['prettyname']} ({$xt['extensionname']}) <span style='font-style: italic;'>Recommended</span>: <span style='color:orange'>FAILED</span> (<a href='{$xt['helpurl']}' target='_blank'>Click for more info</a>)";
		}
	}
	else
	{
		$IPBHTML .= "<br />{$xt['prettyname']} ({$xt['extensionname']}): <span style='color:green'>Pass</span>";
	}
}

$IPBHTML .= <<<EOF
</div>
EOF;

return $IPBHTML;
}

/**
 * Global template/wrapper
 *
 * @access	public
 * @param	string		Title
 * @param	string		Page content
 * @param	array 		Data
 * @param	array 		Errors
 * @param	array 		Warnings
 * @param	array 		Install step info
 * @return	string		HTML
 */
public function globalTemplate( $title, $content, $data=array(), $errors=array(), $warnings=array(), $messages=array(), $installStep=array(), $version, $appData ) {

$IPBHTML = "";
//--starthtml--//

$_cssPath        = '../setup/public';
$_productVersion = $this->registry->fetchGlobalConfigValue('version');
$_productName    = $this->registry->fetchGlobalConfigValue('name');
$app			 = ( IPS_IS_UPGRADER ) ? 'upgrade' : 'install';
$extraUrl		 = ( IPS_IS_UPGRADER ) ? '&s=' . $this->request['s'] : '';
$extraUrl		.= ( IPS_IS_UPGRADER AND $this->request['workact'] ) ? '&workact=' . $this->request['workact'] : '';
$extraUrl		.= ( IPS_IS_UPGRADER AND isset( $this->request['st'] ) ) ? '&st=' . $this->request['st'] : '';
$extraInfo       = ( IPS_IS_UPGRADER AND $version ) ? 'This Module: ' . $version . '<br />(' . $appData['name'] . ')' : '';

$IPBHTML .= <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
		<title>IPS SetUp: $title</title>
		<style type='text/css' media='all'>
			@import url('{$_cssPath}/install.css');
		</style>	
	</head>
	<body>
		<p>&nbsp;</p>
		<p>&nbsp;</p>
		<p>&nbsp;</p>
		<form id='install-form' action='index.php?app={$app}{$extraUrl}&section={$this->registry->output->nextAction}' method='post'>
		<input type='hidden' name='_sd' value='{$data['savedData']}'>
		
		<div id='ipswrapper'>
		    <div class='main_shell'>
				<div id='branding'><img src='{$this->registry->output->imageUrl}/logo.png' align='absmiddle' /></div>
		 	    <div id='navigation'>
					<ul id='section_buttons'>
						<li class='active'><span>{$this->registry->output->sequenceData[$this->registry->output->currentPage]}</span></li>
					</ul>
EOF;
if ( ! IPS_IS_UPGRADER )
{
	$IPBHTML .= <<<EOF
					<p><a href='http://external./ipboard30/landing/?p=installation-guide' target='_blank'><b>Installation Guide</b></a> &gt;</p>
EOF;
}

$IPBHTML .= <<<EOF
				</div>
		 	    <div class='content_shell'>
		 	        <div class='package'>
		 	            <div>
		 	                <div class='install_info'>
    							<ul id='progress'>

EOF;

foreach( $data['progress'] as $p )
{
	$extra = '';
	
	if ( $installStep[0] > 0 )
	{
		 $extra = ( $p[0] == 'step_doing' ) ? "<p>Step {$installStep[0]}/{$installStep[1]}</p>" : '';
	}
	
	if ( $extraInfo )
	{
		 $extra .= ( $p[0] == 'step_doing' ) ? "<p>{$extraInfo}</p>" : '';
	}
	
	$IPBHTML .= <<<EOF
	<li class='{$p[0]}'>{$p[1]}{$extra}</li>
EOF;
}

$IPBHTML .= <<<EOF
    		 	                </ul>
    		 	                
    		 	                
    		 	            </div>
    		 	            		 	            
    		 	            <div class='content_wrap'>
    		 	                <div style='border-bottom: 1px solid #939393; padding-bottom: 4px; margin-bottom:6px;'>
    		 	                    <div style='vertical-align: middle'>
    		 	                        <h2>{$_productName} {$_productVersion}</h2>
    		 	                    </div>
    		 	                </div>
                <div style='clear:both'></div>
EOF;

	if ( count( $messages ) )
	{
		$IPBHTML .= <<<EOF
		<br />
		    <div class='message' style='overflow:auto;max-height:180px'>
EOF;

		foreach( $messages as $msg )
		{
			$IPBHTML .= "<p>$msg</p>\n";	
		}
		
 		$IPBHTML.= <<<EOF
		    </div><br />
EOF;
	}

	if ( count( $errors ) OR count( $warnings ) )
	{
		$IPBHTML .= <<<EOF
		<br />
		    <div class='message error' style='overflow:auto;max-height:180px'>
EOF;

		foreach( $errors as $msg )
		{
			$IPBHTML .= "<p>Error: $msg</p>\n";	
		}
		
		foreach( $warnings as $msg )
		{
			$IPBHTML .= "<p>Warning: $msg</p>\n";	
		}
		
		
 		$IPBHTML.= <<<EOF
		    </div><br />
EOF;
	}
								$IPBHTML .= <<<EOF
        		 	            {$content}
            		 	        <br />        		 	            
    		 	            </div>
		 	            </div>
		 	            <br clear='all' />
    
		 	            <div class='hr'></div>
		 	            <div style='padding-top: 17px; padding-right: 15px; padding-left: 15px'>
		 	                <div style='float: right'>
EOF;

if ( $data['hideButton'] !== TRUE AND $this->_showNoButtons !== TRUE )
{
	if ( $this->registry->output->nextAction == 'disabled' OR count( $errors ) )
	{
		$IPBHTML .= <<<EOF
		 	                    <input type='submit' class='nav_button' value='Install can not continue...' disabled='disabled' />
EOF;
	}
	else 
	{
		if( ! $this->registry->output->nextAction )
		{
			$back = my_getenv('HTTP_REFERER');
	
			$IPBHTML .= <<<EOF
	<input type='button' class='nav_button' value='< Back' onclick="window.location='{$back}';return false;" />
EOF;
		}
		$IPBHTML .= <<<EOF
		 	                    <input type='submit' class='nav_button' value='Next >' />
EOF;
	}
}

$date = date("Y");

$IPBHTML .= <<<EOF
						</div>
		 	            </div>
		 	            <div style='clear: both;'></div>
		 	            <div class='copyright'>
		 	                &copy; 
EOF;
$IPBHTML .= date("Y");
$IPBHTML .= <<<EOF
 Invision Power Services, Inc.
		 	            </div>
		 	        </div>

		 	    </div>
    		</div>
    	</div>
EOF;
/* Bit of a kludge */

if ( is_array( $errors ) AND count( $errors ) )
{
	$IPBHTML .= <<<EOF
		<script type='text/javascript'>
		//<![CDATA[

		function form_redirect()
		{
			return false;
		}
		//]]>
		</script>
EOF;
}

$IPBHTML .= <<<EOF
		</form>
	
	</body>
</html>
EOF;

return $IPBHTML;
}

/**
 * AJAX page refresh template
 *
 * @access	public
 * @param	string		Output
 * @return	string		HTML
 */
public function page_refresh( $output ) {

$this->_showNoButtons = TRUE;

$output = ( is_array( $output ) AND count( $output ) ) ? $output : array( 0 => 'Proceeding..' );
$errors = $this->registry->output->fetchWarnings();

$HTML = <<<EOF
<script type='text/javascript'>
//<![CDATA[
setTimeout("form_redirect()",2000);

function form_redirect()
{
	document.getElementById( 'install-form' ).submit();
}
//]]>
</script>
    		 	                <ul id='auto_progress'>
EOF;

if ( ! is_array( $errors ) OR ! count( $errors ) )
{
	foreach( $output as $l )
	{
		$HTML .= <<<EOF
    		 	                    <li><img src='{$this->registry->output->imageUrl}/check.gif' align='absmiddle' /> $l</li>
EOF;
	}
}

$HTML .= <<<EOF
    		 	                </ul>
								<br />
								<div style='float: right'>
									<input type='submit' class='nav_button' value='Click here if not forwarded' />
								</div>
EOF;

return $HTML;
}

}
