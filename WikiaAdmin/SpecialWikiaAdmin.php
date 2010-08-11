<?php
if ( !defined('MEDIAWIKI' ) ) die( "Not an entry point." );
/**
 * WikiaAdmin extension - allows management of multiple wikis from the
 *                        same master DB and LocalSettings file
 * 
 * @package MediaWiki
 * @subpackage Extensions
 * @author Aran Dunkley [http://www.organicdesign.co.nz/nad User:Nad]
 * @copyright © 2007-2010 Aran Dunkley
 * @licence GNU General Public Licence 2.0 or later
 * 
 * Version 2.0 started on 2010-06-24
 */
define( 'WIKIAADMIN_VERSION', "2.0.10, 2010-08-05" );

# WikiaAdmin uses $wgWikiaSettingsDir/wgDBname to store the LocalSettings for
# the wikis in this DB. It reads in the settings files and determines
# which settings file to apply based on the domain of the request.
if( !isset( $wgWikiaSettingsDir ) ) die( "\$wgWikiaSettingsDir is not set!" );
if( !is_dir( $wgWikiaSettingsDir ) ) die( "The \$wgWikiaSettingsDir (\"$wgWikiaSettingsDir\") doesn't exist!" );
if( !is_writable( $wgWikiaSettingsDir ) ) die( "Unable to write to the \$wgWikiaSettingsDir directory!" );

# Set this if only specific domains are allowed
$wgWikiaAdminDomains = false;

# Array of subdomains which are implied by naked domain usage
$wgWikiaImpliedSubdomains = array( 'www', 'wiki' );

# This must contain at least one database dump in the form of description => file
$wgWikiaDatabaseDumps = array();

# The settings for all wikis under this master wiki are stored in
# a persistent array which is stored in of name $wgWikiaSettingsFile
# - note the array must be for all wikis so that the wiki that
#   corresponds to the domain of the request can be established
$wgWikiaSettingsFile = "$wgWikiaSettingsDir/$wgDBname.$wgDBprefix";

# The directory that backups should go into
$wgWikiaBackupDir = dirname( __FILE__ );

$wgExtensionMessagesFiles['WikiaAdmin'] = dirname( __FILE__ ) . "/WikiaAdmin.i18n.php";
$wgExtensionFunctions[] = "wfSetupWikiaAdmin";
$wgSpecialPages['WikiaAdmin'] = "WikiaAdmin";
$wgSpecialPageGroups['WikiaAdmin'] = "od";
$wgExtensionCredits['specialpage'][] = array(
	'name'        => "WikiaAdmin",
	'author'      => "[http://www.organicdesign.co.nz/nad User:Nad]",
	'description' => "Manage the wikis in this wikia",
	'url'         => "http://www.organicdesign.co.nz/Extension:WikiaAdmin.php",
	'version'     => WIKIAADMIN_VERSION
);

require_once( "$IP/includes/SpecialPage.php" );

/**
 * Define a new class based on the SpecialPage class
 */
class WikiaAdmin extends SpecialPage {

	# LocalSettings hash for all sub-wikis
	var $settings  = array();
	
	# The current wiki (based on the domain of the request)
	var $wiki      = false;

	# Posted form data
	var $action    = '';
	var $curid     = '';
	var $newid     = '';
	var $sitename  = '';
	var $dump      = '';
	var $domains   = '';
	var $domain    = '';
	var $subdomain = '';
	var $user      = '';
	var $pass      = '';
	var $pass2     = '';
	var $delete    = '';

	# Form processing results
	var $error     = '';
	var $result    = '';

	function __construct() {

		# Load the localsettings array for this master wiki (by DB.prefix)
		$this->loadSettings();

		# Apply any localsettings relating to this sub-wiki (by domain)
		# - also sets $this->wiki
		$this->applySettings();

		# Only add the special page to the environment if not a sub-wiki
		if( !$this->wiki ) {
			SpecialPage::SpecialPage( 'WikiaAdmin', 'sysop', true, false, false, false );
		}

	}

	/**
	 * Override SpecialPage::execute()
	 */
	function execute() {
		global $wgOut, $wgRequest, $wgWikiaDatabaseDumps;
		$this->setHeaders();

		# Die if there are no database dumps to use for new wikis
		if( count( $wgWikiaDatabaseDumps ) < 1 ) die( wfMsg( 'wa-no-dumps' ) );

		# A form was submitted
		if( $wgRequest->getText( 'wpSubmit' ) ) {

			# Read in posted values
			$this->action    = $wgRequest->getText( 'wpAction' );
			$this->curid     = $wgRequest->getText( 'wpCurId' );
			$this->newid     = preg_replace( "|[^.a-z0-9]+|i", "", strtolower( $wgRequest->getText( 'wpNewId' ) ) );
			$this->sitename  = $wgRequest->getText( 'wpSitename' );
			$this->domains   = $wgRequest->getText( 'wpDomains' );
			$this->domain    = $wgRequest->getText( 'wpDomain' );
			$this->subdomain = $wgRequest->getText( 'wpSubdomain' );
			$this->user      = ucfirst( $wgRequest->getText( 'wpUser', "WikiSysop" ) );
			$this->pass      = $wgRequest->getText( 'wpPass' );
			$this->pass2     = $wgRequest->getText( 'wpPass2' );
			$this->dump      = $wgRequest->getText( 'wpDump' );
			$this->delete    = $wgRequest->getText( 'wpDelete' );

			# Process the form
			$this->processForm();

			# Render any errors or results set during processing
			if( !empty( $this->error ) )  $wgOut->addHtml( "<div class='errorbox'>{$this->error}</div>" );
			if( !empty( $this->result ) ) $wgOut->addHtml( "<div class='successbox'>{$this->result}</div>" );
			$wgOut->addHtml( "<div style=\"clear: both\"></div>" );
		}

		# Render the form
		$this->renderForm();

	}

	/**
	 * Render the special page form and populate with posted data or defaults
	 */
	function renderForm() {
		global $wgOut, $wgDBname, $wgJsMimeType, $wgWikiaAdminDomains, $wgWikiaDatabaseDumps;
		$url = Title::newFromText( 'WikiaAdmin', NS_SPECIAL )->getLocalUrl();
		$this->addJavaScript( $wgOut );
		$wgOut->addHtml( "<h2>" . wfMsg( 'wa-title', $wgDBname ) . "</h2>\n" );
		$wgOut->addHtml( "<form action=\"$url\" method=\"POST\" enctype=\"multipart/form-data\">\n" );

		# Wiki ID
		$wgOut->addHtml( wfMsg( 'wa-id' ) . ": " );
		$options = "<option value=\"new\">" . wfMsg( 'wa-new' ) . "...</option>\n";
		foreach( $this->settings as $id => $settings ) {
			$selected = $this->newid == $id ? " selected" : "";
			$wiki = $settings['wgSitename'];
			$options .= "<option$selected value=\"$id\">$wiki</option>\n";
		}
		$wgOut->addHtml( "<select id=\"wa-id-select\" onchange=\"wikia_id_select()\" name=\"wpCurId\">$options</select><br />\n" );

		# New wiki ID - revealed if Wiki ID set to "new"
		$wgOut->addHtml( "<div id=\"wa-new-id\">" );
		$wgOut->addHtml( "<br />" . wfMsg( 'wa-id-new' ) . "<br />" );
		$wgOut->addHtml( "<input name=\"wpNewId\" value=\"\" /><br />\n" );
		$wgOut->addHtml( "</div>" );

		# Action - revealed only if ID set to an existing wiki
		$wgOut->addHtml( "<div id=\"wa-action\">" );
		$wgOut->addHtml( "<br />" . wfMsg( 'wa-action', $id ) . "<br />" );
		$wgOut->addHtml( "<select id=\"wa-action-select\" onchange=\"wikia_action_select()\" name=\"wpAction\">
				<option value=\"1\">" . wfMsg( 'wa-update-wiki' ) . "</option>
				<option value=\"2\">" . wfMsg( 'wa-backup-wiki' ) . "</option>
				<option value=\"3\">" . wfMsg( 'wa-delete-wiki' ) . "</option>
			</select><br />\n");
		$wgOut->addHtml( "</div>" );

		# Site name
		$wgOut->addHtml( "<div id=\"wa-create-update\"><br />" . wfMsg( 'wa-sitename' ) . "<br />" );
		$wgOut->addHtml( "<input name=\"wpSitename\" value=\"{$this->sitename}\" /><br />\n" );

		# Database dump list
		$wgOut->addHtml( "<br />" . wfMsg( 'wa-dumps' ) . "<br />" );
		$options = '';
		foreach ( $wgWikiaDatabaseDumps as $name => $file ) {
			$selected = $this->dump == $name ? " selected" : "";
			$options .= "<option$selected>$name</option>\n";
		}
		$wgOut->addHtml( "<select name=\"wpDump\">$options</select><br />\n" );

		# Domain selection
		$wgOut->addHtml( "<br />" . wfMsg( 'wa-domains' ) . "<br />" );
		if ( $wgWikiaAdminDomains === false ) {
			$wgOut->addHtml( "<textarea name=\"wpDomains\">{$this->domains}</textarea><br />" );
		} else {
			foreach ( $wgWikiaAdminDomains as $domain ) {
					$selected = $this->domain == $domain ? " selected" : "";
					$options .= "<option$selected>$domain</option>\n";
			}
			$wgOut->addHtml( "<select name=\"wpDomain\">$options</select><br />\n" );
			$wgOut->addHtml( "<input name=\"wpSubdomain\" value=\"{$this->subdomain}\" /><br />\n" );
		}
		$wgOut->addHtml( "<i>(" . wfMsg( 'wa-domain-naked' ) . ")</i><br />" );

		# Sysop details
		$wgOut->addHtml( "<div id=\"wa-sysop\">" );
		$wgOut->addHtml( "<br /><table cellpadding=\"0\" cellspacing=\"0\">\n" );
		$wgOut->addHtml( "<tr><td>" . wfMsg( 'wa-user' ) . ":</td>" );
		$wgOut->addHtml( "<td><input name=\"wpUser\" value=\"{$this->user}\" /></td></tr>" );
		$wgOut->addHtml( "<tr><td>" . wfMsg( 'wa-pwd' ) . ":</td> " );
		$wgOut->addHtml( "<td><input type=\"password\" name=\"wpPass\" /></td></tr>" );
		$wgOut->addHtml( "<tr><td>" . wfMsg( 'wa-pwd-confirm' ) . ":</td> " );
		$wgOut->addHtml( "<td><input type=\"password\" name=\"wpPass2\" /></td></tr>" );
		$wgOut->addHtml( "</table></div></div>" );

		# Backup filename (revealed if action is backup)
		$wgOut->addHtml( "<div id=\"wa-backup\"></div>\n" );

		# Delete? (revealed if action is delete)
		$wgOut->addHtml( "<div id=\"wa-delete\"><br />" );
		$wgOut->addHtml( "<input name=\"wpDelete\" type=\"checkbox\" value=\"delete\" />&nbsp;" . wfMsg( 'wa-delete-confirm' ) . "</div>\n" );

		# Form submit
		$wgOut->addHtml( "<br /><input type=\"submit\" name=\"wpSubmit\" value=\"" . wfMsg( 'wa-submit' ) . "\" />" );
		$wgOut->addHtml( "</form>" );
		$wgOut->addHtml( "<script type='$wgJsMimeType'>wikia_id_select()</script>" );
	}

	/**
	 * Create or update a wiki in accord with submitted data
	 */
	function processForm() {

		# Can't do any action without a wiki id
		if( empty( $this->newid ) && empty( $this->curid ) ) return $this->error = wfMsg( 'wa-id-missing' );

		# Create a new wiki
		if( $id = $this->newid ) {
			global $wgDBname, $wgWikiaDatabaseDumps;

			# Validation (should use friendly JS instead)
			if( !empty( $this->user ) && empty( $this->pass ) )  return $this->error = wfMsg( 'wa-pwd-missing' );
			if( $this->pass !== $this->pass2 )                   return $this->error = wfMsg( 'wa-pwd-mismatch' );
			if( preg_match( "|[^a-z0-9_]|i", $this->newid ) )    return $this->error = wfMsg( 'wa-id-invalid' );
			if( in_array( $this->newid, $this->settings ) )      return $this->error = wfMsg( 'wa-id-exists' );
			if( empty( $this->domains ) )                        return $this->error = wfMsg( 'wa-domain-missing' );
			if( empty( $this->sitename ) )                       return $this->error = wfMsg( 'wa-sitename-missing' );
			if( !empty( $this->user ) && preg_match( "|[^a-z0-9_]|i", $this->user ) ) return $this->error = wfMsg( 'wa-user-invalid' );

			# Create/Update settings for the selected wiki
			$this->settings[$id]['wgShortName'] = $id;
			$this->settings[$id]['wgDBprefix']  = $id . '_';
			$this->settings[$id]['wgSitename']  = $this->sitename;
			$this->saveSettings();

			# Add the database template to the "wikia" DB
			$sysop = $this->user ? '--sysop=' . $this->user . ':' . $this->pass : '';
			$file = $wgWikiaDatabaseDumps[$this->dump];
			$cmd = "/var/www/tools/add-db $sysop $file $wgDBname.{$id}_";
			$result = shell_exec( "$cmd 2>&1" );
			if ( strpos( $result, 'successfully' ) ) $this->result = wfMsg( 'wa-success', $this->sitename, $id );
			else return $this->error = $result;
		}

		# Update wiki properties
		elseif( $this->action == 1 ) {
			# TODO
			$this->saveSettings();
		}

		# Backup wiki
		elseif( $this->action == 2 ) {
			$this->backupWiki( $this->curid );
		}

		# Delete wiki
		elseif( $this->action == 3 ) {
			if( $this->delete == 'delete' ) $this->deleteWiki( $this->curid );
			else $this->error = wfMsg( 'wa-delete-cancelled' );
		}

	}


	/**
	 * The form requires some JavaScript for chained selects
	 */
	function addJavaScript( $out ) {
		global $wgJsMimeType;
		$out->addScript( "<script type='$wgJsMimeType'>
			function wikia_id_select() {
				if ($('#wa-id-select').val() == 'new') {
					$('#wa-new-id').show();
					$('#wa-action').hide();
				} else {
					$('#wa-new-id').hide();
					$('#wa-action').show();
				}
				wikia_action_select();
			}
			function wikia_action_select() {
				if ($('#wa-action-select').val() == 1) {
					$('#wa-create-update').show();
					$('#wa-backup').hide();
					$('#wa-delete').hide();
				}
				if ($('#wa-action-select').val() == 2) {
					$('#wa-create-update').hide();
					$('#wa-backup').show();
					$('#wa-delete').hide();
				}
				if ($('#wa-action-select').val() == 3) {
					$('#wa-create-update').hide();
					$('#wa-backup').hide();
					$('#wa-delete').show();
				}
			}
		</script>" );
		
		# TODO: the JS should add the settings array so that form inputs can be
		#       pre-filled when existing wiki's selected
		
	}


	/**
	 * Load the settings array from persistent storage
	 * TODO: change to human readable format
	 */
	function loadSettings() {
		global $wgWikiaSettingsFile;
		if ( file_exists( $wgWikiaSettingsFile ) ) {
			$this->settings = unserialize( file_get_contents( $wgWikiaSettingsFile ) ); 
		}
	}


	/**
	 * Save the settings array to persistent storage
	 * TODO: change to human readable format
	 */
	function saveSettings() {
		global $wgWikiaSettingsFile;
		file_put_contents( $wgWikiaSettingsFile, serialize( $this->settings ) );
	}


	/**
	 * Return the settings for a given wiki in specified format
	 */
	function getSettings( $wiki, $format = false ) {
		$settings = '';
		switch( $format ) {

			# Returns the settings as LocalSettings.php includable PHP text
			case 'PHP':
				if( is_array( $this->settings[$wiki] ) ) {
					foreach( $this->settings[$wiki] as $k => $v ) $settings .= "\$$k = \"$v\";\n";
				}
				$settings = "wikiaSettings = \{$settings\};\n";
			break;

			# Returns the settings as a JavaScript array statement
			case 'JS':
				if( is_array( $this->settings[$wiki] ) ) {
					$c = "\n";
					foreach( $this->settings[$wiki] as $k => $v ) {
						$settings .= "$c\t\"$k\": \"$v\"";
						$c = ",\n";
					}
				}
				$settings = "wikiaSettings = \{$settings\n\};\n";
			break;

			# By default just return the array as is
			default: $settings = $this->settings;
		}
		return $settings;
	}


	/**
	 * Update the settings array (for use by other extensions)
	 */
	function setSettings( &$settings ) {
		$this->settings = $settings;
	}


	/**
	 * Apply any of the settings relating to the current request
	 * - also sets $this->wiki
	 */
	function applySettings() {

		# Get domain of this request
		global $wgWikiaImpliedSubdomains;
		$pattern = "/^(" . join( '|', $wgWikiaImpliedSubdomains ) . ")\\./";
		$domain  = preg_replace( $pattern, "", $_SERVER['HTTP_HOST'] );

		# Get sub-wiki ID from domain
		foreach( $this->settings as $wiki => $settings ) {
			if( in_array( 'domains', $settings ) ) {
				foreach( split( "\n", $settings['domains'] ) as $d ) {
					$d = preg_replace( $pattern, "", $d );
					if( $d === $domain ) $this->wiki = $wiki;
				}
			}
			if( in_array( 'domain', $settings ) ) {
				$d = $settings['subdomain'] . '.' . $settings['domain'];
				$d = preg_replace( $pattern, "", $domain );
				if( $d === $domain ) $this->wiki = $wiki;
			}
		}

		# If this is a sub-wiki...
		if ( $this->wiki ) {

			# Apply its local settings
			foreach( $this->settings[$this->wiki] as $setting => $value ) {
				global $$setting;
				$$setting = $value;
			}

			# Make the uploadpath a sub-directory and fallback on master's images
			global $wgUploadPath, $wgUseSharedUploads, $wgSharedUploadDirectory, $wgSharedUploadPath;
			$wgSharedUploadDirectory = $wgUploadPath;
			$wgUploadPath .= '/' . $this->wiki;
			if( !is_dir( $wgUploadPath ) ) mkdir( $wgUploadPath );
			$wgUseSharedUploads = true;
			$wgSharedUploadPath = "http://" . $_SERVER['HTTP_HOST'] . "/files";

		}
	}

	/**
	 * Backup the specified wiki to a sql.7z file
	 */
	function backupWiki( $wiki ) {
		global $wgDBuser, $wgDBpassword, $wgWikiaBackupDir, $wgDBname;

		# Create a unique filename for the backup
		$date = strftime( "%Y%m%d" );
		$n = 0;
		do {
			$m = $n ? ".$n" : "";
			$file = "$wgWikiaBackupDir/$wgDBname-$wiki-$date$m.sql.7z";
			$n++;
		} while( file_exists( $file ) );

		# List the tables with this prefix
		$tables = '';
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->query( "SHOW TABLES LIKE '{$wiki}_%';"  );
		while ( $row = $dbr->fetchRow( $res ) ) $tables .= ' ' . $row[0];
		$dbr->freeResult( $res );

		# Dump the tables and compress
		shell_exec( "mysqldump -u $wgDBuser --password='$wgDBpassword' --add-drop-database $wgDBname $tables > $file.tmp" );
		shell_exec( "7za a $file $file.tmp" );
		unlink( "$file.tmp" );

	}

	/**
	 * Delete a wiki
	 */
	function deleteWiki( $wiki ) {
		# TODO
		return $this->error = "Ooops deleting wikis hasn't been done yet!";
		$this->backupWiki( $wiki );
	}
}

/*
 * Initialise the new special page
 */
function wfSetupWikiaAdmin() {
	global $wgWikiaAdmin;
	$wgWikiaAdmin = new WikiaAdmin();
	SpecialPage::addPage( $wgWikiaAdmin );
}

