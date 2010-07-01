<?php
if ( !defined('MEDIAWIKI' ) ) die( 'Not an entry point.' );
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
define( 'WIKIAADMIN_VERSION', '2.0.4, 2010-07-01' );

# WikiaAdmin uses $wgWikiaSettingsDir/wgDBname to store the LocalSettings for
# the wikis in this DB. It reads in the settings files and determines
# which settings file to apply based on the domain of the request.
$wgWikiaSettingsDir = '';

# The domain names available for wikia use. It is assumed that all domains
# are already configured in the web-server environment to arrive at this wiki
# - in an OD environment that means the domains in this list should have
#   their own entries in vhost to ensure that all its sub-domains also
#   arrive at this master LocalSettings
$wgWikiaAdminDomains = array();

# The domain of the master wiki
$wgWikiaAdminMaster  = '';

$wgExtensionMessagesFiles['WikiaAdmin'] = dirname( __FILE__ ) . '/WikiaAdmin.i18n.php';
$wgExtensionFunctions[] = 'wfSetupWikiaAdmin';
$wgSpecialPages['WikiaAdmin'] = 'WikiaAdmin';
$wgSpecialPageGroups['WikiaAdmin'] = 'od';
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

	var $error = '';
	var $result = '';

	function WikiaAdmin() {
		SpecialPage::SpecialPage( 'WikiaAdmin', 'sysop', true, false, false, false );
	}

	/**
	 * Override SpecialPage::execute()
	 */
	function execute() {
		global $wgOut, $wgRequest;
		$this->setHeaders();

		# Retrieve any posted data or set defaults
		$this->newid    = $wgRequest->getText( 'wpNewid' );
		$this->curid    = $wgRequest->getText( 'wpCurid' );
		$this->sitename = $wgRequest->getText( 'wpSitename' );
		$this->domains  = $wgRequest->getText( 'wpDomains' );
		$this->codebase = $wgRequest->getText( 'wpCodebase' );
		$this->user     = ucfirst( $wgRequest->getText( 'wpUser', 'WikiSysop' ) );
		$this->pass     = $wgRequest->getText( 'wpPass' );
		$this->pass2    = $wgRequest->getText( 'wpPass2' );

		# Process request if form submitted
		if ( $wgRequest->getText( 'wpSubmit' ) ) $this->processForm();

		# Render any errors or results set during processing
		if ( !empty( $this->error ) ) $wgOut->addHtml( "<div class='errorbox'>{$this->error}</div>" );
		if ( !empty( $this->result ) ) $wgOut->addHtml( "<div class='successbox'>{$this->result}</div>" );
		$wgOut->addHtml( "<div style=\"clear: both\"></div>" );

		# Render the form
		$this->renderForm();

	}

	/**
	 * Render the special page form and populate with posted data or defaults
	 */
	function renderForm() {
		global $wgOut, $wgWikiaAdminDomains;
		$url = Title::newFromText( 'WikiaAdmin', NS_SPECIAL )->getLocalUrl();
		$wgOut->AddHtml( "<form action=\"$url\" method=\"POST\" enctype=\"multipart/form-data\">" );
		$wgOut->AddHtml( "<table cellpadding=\"4\" cellspacing=\"0\">" );

		# ID for a new wiki
		$wgOut->AddHtml( "<tr><td>" . wfMsg( 'wa_id_new' ) . ":</td> " );
		$wgOut->AddHtml( "<td><input name=\"wpNewid\" value=\"{$this->newid}\" /></td><td><i>(" . wfMsg( 'wa_id_desc' ) . ")</i></td></tr>" );

		# or an ID of an existing one to modify
		$wgOut->AddHtml( "<tr><td>" . wfMsg( 'wa_modexisting' ) . ":</td> " );
		$options = "<option />";
		foreach ( glob( '/var/www/wikis/*' ) as $wiki ) {
			if ( ereg( '([^/]+)$', $wiki, $m ) ) {
				$selected = $this->curid == $m[1] ? ' selected' : '';
				$options .= "<option$selected>$m[1]</option>";
			}
		}
		$wgOut->AddHtml( "<td><select name=\"wpCurid\">$options</select></td></tr>" );

		$wgOut->AddHtml( "<tr><td colspan=\"3\"><hr /></td></tr>" );

		# Site name
		$wgOut->AddHtml( "<tr><td>" . wfMsg( 'wa_sitename' ) . ":</td> " );
		$wgOut->AddHtml( "<td><input name=\"wpSitename\" value=\"{$this->sitename}\" /></td></tr>" );

		# Domain selection
		$wgOut->AddHtml( "<tr><td valign=\"top\">" . wfMsg( 'wa_domains' ) . ":</td>" );
		$wgOut->AddHtml( "<td><textarea name=\"wpDomains\">{$this->domains}</textarea></td>" );
		$wgOut->AddHtml( "<td valign=\"top\"><i>(" . wfMsg( 'wa_domain_naked' ) . ")</i></td></tr>" );

		# Codebase selection
		$wgOut->AddHtml( "<tr><td>" . wfMsg( 'wa_codebase' ) . ":</td> " );
		$options = "";
		foreach ( glob( '/var/www/empty*.sql' ) as $cb ) {
			if ( ereg( '([0-9.]+).*\.sql', $cb, $m ) ) {
				$selected = $this->codebase == $m[1] ? ' selected' : '';
				$options .= "<option>$m[1]</option>";
			}
		}
		$wgOut->AddHtml( "<td><select name=\"wpCodebase\">$options</select></td></tr>" );

		$wgOut->AddHtml( "<tr><td colspan=\"3\"><hr /></td></tr>" );

		# Sysop details
		$wgOut->AddHtml( "<tr><td>" . wfMsg( 'wa_user' ) . ":</td> " );
		$wgOut->AddHtml( "<td><input name=\"wpUser\" value=\"{$this->user}\" /></td></tr>" );
		$wgOut->AddHtml( "<tr><td>" . wfMsg( 'wa_pwd' ) . ":</td> " );
		$wgOut->AddHtml( "<td><input type=\"password\" name=\"wpPass\" /></td></tr>" );
		$wgOut->AddHtml( "<tr><td>" . wfMsg( 'wa_pwd_confirm' ) . ":</td> " );
		$wgOut->AddHtml( "<td><input type=\"password\" name=\"wpPass2\" /></td></tr>" );

		$wgOut->AddHtml( "<tr><td colspan=\"3\"><hr /></td></tr>" );
		$wgOut->AddHtml( "<tr><td /><td align=\"right\"><input type=\"submit\" name=\"wpSubmit\" value=\"" . wfMsg( 'wa_submit' ) . "\" /></td></tr>" );

		$wgOut->AddHtml( "</table></form>" );
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

			# Create the wiki dir, codebase symlink and domain symlinks
			if ( !is_dir( $mw ) ) return $this->error = wfMsg( 'wa_codebase-notfound', $mw );
			shell_exec( "mkdir $dir" );
			shell_exec( "mkdir -m 777 $dir/files" );
			shell_exec( "ln -s $mw $dir/wiki" );
			foreach( split( "\n", $this->domains ) as $domain ) shell_exec( "ln -s $dir /var/www/domains/$domain" );

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
			
			# Write the LocalSettings.php file
			file_put_contents( "$dir/LocalSettings.php", $ls );
		}
	}
}

/*
 * Initialise the new special page
 */
function wfSetupWikiaAdmin() {
	global $wgWikiaAdminMaster, $wgWikiaSettingsDir;
	
	# Only activate this extension if this is the master domain
	if ( $wgWikiaAdminMaster === $_SERVER['HTTP_HOST'] ) {

		# Install the special page
		SpecialPage::addPage( new WikiaAdmin() );
		
		# Check sanity of the storage dir
		if ( empty( $wgWikiaSettingsDir ) ) die( "\$wgWikiaSettingsDir is not set!" );
		if ( !is_dir( $wgWikiaSettingsDir ) ) die( "The \$wgWikiaSettingsDir (\"$wgWikiaSettingsDir\") doesn't exist!" );
		if ( !is_writable( $wgWikiaSettingsDir ) ) die( "Unable to write to the \$wgWikiaSettingsDir directory!" );

		# If no directory exists for this DB in the settings dir, create it now
		$dir = "$wgWikiaSettingsDir/$wgDBname";
		if ( !is_dir( $dir ) ) mkdir( $dir );
		
	}
}

