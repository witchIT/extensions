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
define( 'WIKIAADMIN_VERSION', "2.0.7, 2010-07-03" );

# WikiaAdmin uses $wgWikiaSettingsDir/wgDBname to store the LocalSettings for
# the wikis in this DB. It reads in the settings files and determines
# which settings file to apply based on the domain of the request.
if ( !isset( $wgWikiaSettingsDir ) ) die( "\$wgWikiaSettingsDir is not set!" );
if ( !is_dir( $wgWikiaSettingsDir ) ) die( "The \$wgWikiaSettingsDir (\"$wgWikiaSettingsDir\") doesn't exist!" );
if ( !is_writable( $wgWikiaSettingsDir ) ) die( "Unable to write to the \$wgWikiaSettingsDir directory!" );

# The domain names available for wikia use to appear in the domain dropdown
# - it is assumed that all domains are already configured in the web-server
#   environment to arrive at this wiki
$wgWikiaAdminDomains = array();

# Populate the domains array from /var/www/domains if it exists
# - in an OD environment that means the domains in this list should all have
#   a wildcard A record pointing to the server, and their own symlinks in
#   /var/www/domains to direct any requests under them to this master wiki
if ( is_dir( '/var/www/domains' ) && isset( $shortname ) ) {
	
}

# The settings for all wikis under this master wiki are stored in
# a persistent array which is stored in of name $wgWikiaSettingsFile
$wgWikiaSettingsFile = "$wgWikiaSettingsDir/$wgDBname.$wgDBprefix";

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

	var $error    = '';
	var $result   = '';
	var $settings = array();
	var $newid    = '';
	var $sitename = '';
	var $domains  = '';
	var $user     = '';

	function __construct() {
		global $wgWikiaSettingsFile, $wgDBname, $wgDBprefix;

		SpecialPage::SpecialPage( 'WikiaAdmin', 'sysop', true, false, false, false );

		
		
		
		
	}

	/**
	 * Override SpecialPage::execute()
	 */
	function execute() {
		global $wgOut, $wgRequest;
		$this->setHeaders();

		# Load the localsettings array for this master wiki (DB.prefix)
		$this->loadSettings();

		# A form was submitted
		if ( $wgRequest->getText( 'wpSubmit' ) ) {

			# Read in posted values
			$this->namespace = $wgRequest->getText( 'wpNamespace' );
			$this->action    = $wgRequest->getText( 'wpAction' );
			$this->sitename  = $wgRequest->getText( 'wpSitename' );
			$this->domains   = $wgRequest->getText( 'wpDomains' );
			$this->codebase  = $wgRequest->getText( 'wpCodebase' );
			$this->user      = ucfirst( $wgRequest->getText( 'wpUser', "WikiSysop" ) );
			$this->pass      = $wgRequest->getText( 'wpPass' );
			$this->pass2     = $wgRequest->getText( 'wpPass2' );
			$this->file      = $wgRequest->getText( 'wpLoad' ) ? $wgRequest->getText( 'wpLoad' ) : $wgRequest->getText( 'wpSave' );
			if ( $this->file ) $this->file = "$wgNamespacePackagesDir/{$this->file}.xml";

			# Process the form
			$this->processForm();

			# Render any errors or results set during processing
			if ( !empty( $this->error ) ) $wgOut->addHtml( "<div class='errorbox'>{$this->error}</div>" );
			if ( !empty( $this->result ) ) $wgOut->addHtml( "<div class='successbox'>{$this->result}</div>" );
			$wgOut->addHtml( "<div style=\"clear: both\"></div>" );
		}


		# Render the form
		$this->renderForm();

	}

	/**
	 * Render the special page form and populate with posted data or defaults
	 */
	function renderForm() {
		global $wgOut, $wgJsMimeType, $wgWikiaAdminDomains, $wgDBname;
		$url = Title::newFromText( 'WikiaAdmin', NS_SPECIAL )->getLocalUrl();
		$this->addJavaScript( $wgOut );
		$wgOut->addHtml( "<h2>" . wfMsg( 'wa-title', $wgDBname ) . "</h2>\n" );
		$wgOut->addHtml( "<form action=\"$url\" method=\"POST\" enctype=\"multipart/form-data\">\n" );

		$wgOut->addHtml( "<script type='$wgJsMimeType'>
			function wikia_id_select() {
				
				// Make the new-id textbox visible if 'new' wiki selected
				
				// Make load/save visible depending on 'new' or not
				
				// Update the option groups and their fields and values
				
			}
		</script>" );

		# Wiki ID
		$wgOut->addHtml( wfMsg( 'wa-id' ) . ': ' );
		$options = "<option value=\"new\">" . wfMsg( 'wa-new' ) . "...</option>\n";
		foreach ( glob( "/var/www/wikis/*" ) as $wiki ) {
			if ( preg_match( "|([^/]+)$|", $wiki, $m ) ) {
				$selected = $this->newid == $m[1] ? " selected" : "";
				$options .= "<option$selected>$m[1]</option>\n";
			}
		}
		$wgOut->addHtml( "<select id=\"wa-id-select\" onchange=\"wikia_id_select()\" name=\"wpWikiaID\">$options</select><br />\n" );

		# New wiki ID - revealed if Wiki ID set to "new"
		$wgOut->addHtml( "<div id=\"wa-new-id\">" );
		$wgOut->addHtml( wfMsg( 'wa-id-new' ) . "<br />" );
		$wgOut->addHtml( "<input name=\"wa-new-id\" value=\"\" /><br />\n" );
		$wgOut->addHtml( "</div>" );

		# Site name
		$wgOut->addHtml( "<br />" . wfMsg( 'wa-sitename' ) . "<br />" );
		$wgOut->addHtml( "<input name=\"wpSitename\" value=\"{$this->sitename}\" /><br />\n" );

		# Domain selection
		$wgOut->addHtml( "<br />" . wfMsg( 'wa-domains' ) . "<br />" );
		$wgOut->addHtml( "<textarea name=\"wpDomains\">{$this->domains}</textarea><br />" );
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
		$wgOut->addHtml( "</table></div>" );

		# Load content from file
		$wgOut->addHtml( "<div id=\"wa-load\">" );
		$options = "<option />\n";
		foreach ( glob( "/var/www/wikis/*" ) as $wiki ) {
			if ( preg_match( "|([^/]+)$|", $wiki, $m ) ) {
				$selected = $this->newid == $m[1] ? " selected" : "";
				$options .= "<option$selected>$m[1]</option>\n";
			}
		}

		# Save content from file
		$wgOut->addHtml( "<div id=\"wa-save\">" );
		$wgOut->addHtml( "</div>" );

		# Render all the option groups

		$wgOut->addHtml( "<br /><input type=\"submit\" name=\"wpSubmit\" value=\"" . wfMsg( 'wa-submit' ) . "\" />" );
		$wgOut->addHtml( "</form>" );
	}

	/**
	 * Create or update a wiki in accord with submitted data
	 */
	function processForm() {
		$dir = "/var/www/wikis/{$this->newid}";
		$mw = "/var/www/mediawiki-{$this->codebase}";

		# Validation (should use friendly JS instead)
		if ( empty( $this->pass ) )                           return $this->error = wfMsg( 'wa_pwd_missing' );
		if ( $this->pass !== $this->pass2 )                   return $this->error = wfMsg( 'wa_pwd_mismatch' );
		if ( empty( $this->newid ) && empty( $this->curid ) ) return $this->error = wfMsg( 'wa_id_missing' );
		if ( ereg( '[^a-zA-Z0-9_]', $this->newid ) )          return $this->error = wfMsg( 'wa_id_invalid' );
		if ( is_dir( $dir ) )                                 return $this->error = wfMsg( 'wa_id_exists' );
		if ( empty( $this->domains ) )                        return $this->error = wfMsg( 'wa_domain_missing' );
		if ( empty( $this->sitename ) )                       return $this->error = wfMsg( 'wa_sitename_missing' );
		if ( empty( $this->user ) || preg_match( "|[^a-z0-9_]|i", $this->user ) ) return $this->error = wfMsg( 'wa_user_invalid' );

		if ( !empty( $this->newid ) ) {
			$id = $this->newid;

			# Create initial LocalSettings.php file content
			$ls = "<?php\n";
			$ls .= "\$wgSitename          = '{$this->sitename}';\n";
			$ls .= "\$wgShortName         = '$id';\n";
			$ls .= "\$wgDBname            = 'wikia';\n";
			$ls .= "\$wgDBprefix          = '{$id}_';\n";
			
			# Add the database template to the "wikia" DB
			$cmd = "/var/www/tools/add-db --sysop={$this->user}:{$this->pass} /var/www/empty-{$this->codebase}.sql wikia.{$id}_";
			$result = shell_exec( "$cmd 2>&1" );
			if ( ereg( 'successfully', $result ) ) $this->result = wfMsg( 'wa_success', $this->sitename, $id );
			else return $this->error = $result;

			# Add a wiki organisation
			# - options: source site
			# - content: extensions, articles, portals, images, RA css rules
			
			# Write new settings to this master wiki's settins file (DB.prefix)
			$this->saveSettingsArray();
		}
	}


	/**
	 * The form requires some JavaScript for chained selects
	 */
	function addJavaScript( $out ) {
		global $wgJsMimeType;
		$out->addScript( "<script type='$wgJsMimeType'>
			function wikia_id_select() {
				if ($('#wa-id-select').val() == 'new') $('#wa-new-id').show(); else $('#wa-new-id').hide();
			}
		</script>" );
	}


	/**
	 * Load the settings array from persistent storage
	 */
	function loadSettings() {
		global $wgWikiaSettingsFile;
		if ( file_exists( $wgWikiaSettingsFile ) ) {
			$this->settings = unserialize( file_get_contents( $wgWikiaSettingsFile ) ); 
		}
	}


	/**
	 * Save the settings array to persistent storage
	 */
	function saveSettings() {
		global $wgWikiaSettingsFile;
		file_put_contents( $wgWikiaSettingsFile, serialize( $this->settings ) );
	}


	/**
	 * Return the settings array (for use by other extensions)
	 */
	function getSettings() {
		return $this->settings;
	}


	/**
	 * Update the settings array (for use by other extensions)
	 */
	function setSettings( &$settings ) {
		$this->settings = $settings;
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

