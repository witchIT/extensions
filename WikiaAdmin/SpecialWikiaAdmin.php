<?php
/**
 * Extension:WikiaInfo
 * {{Category:Extensions}}{{php}}{{Category:Extensions created with Template:SpecialPage}}
 * 
 * @package MediaWiki
 * @subpackage Extensions
 * @author Aran Dunkley [http://www.organicdesign.co.nz/nad User:Nad]
 * @copyright © 2007 Aran Dunkley
 * @licence GNU General Public Licence 2.0 or later
 */

if ( !defined('MEDIAWIKI' ) ) die( 'Not an entry point.' );

define( 'WIKIAADMIN_VERSION', '0.0.2, 2009-10-12' );

$wgWikiaAdminDomains = array();

$wgExtensionFunctions[] = 'wfSetupWikiaAdmin';

$wgExtensionCredits['specialpage'][] = array(
	'name'        => 'Special:WikiaAdmin',
	'author'      => '[http://www.organicdesign.co.nz/nad User:Nad]',
	'description' => 'Manage the wikis in this wikia',
	'url'         => 'http://www.organicdesign.co.nz/Extension:WikiaAdmin.php',
	'version'     => WIKIAADMIN_VERSION
);

require_once( "$IP/includes/SpecialPage.php" );

/**
 * Define a new class based on the SpecialPage class
 */
class SpecialWikiaAdmin extends SpecialPage {

	var $error = '';
	var $result = '';

	function SpecialWikiaAdmin() {
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
		$this->user     = $wgRequest->getText( 'wpUser', 'WikiSysop' );
		$this->pass     = $wgRequest->getText( 'wpPass' );
		$this->pass2    = $wgRequest->getText( 'wpPass2' );

		# Process request if form submitted
		if ( $wgRequest->getText( 'wpSubmit' ) ) $this->processForm();

		# Render any errors or results set during processing
		if ( !empty( $this->error ) ) $wgOut->addHtml( $this->error );
		if ( !empty( $this->result ) ) $wgOut->addHtml( $this->result );

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
		$wgOut->AddHtml( "<tr><td>Enter ID for new wiki:</td> " );
		$wgOut->AddHtml( "<td><input name=\"wpNewid\" value=\"{$this->newid}\" /></td><td><i>(lowercase a-z, used for FS location and for DB prefix)</i></td></tr>" );

		# or an ID of an existing one to modify
		$wgOut->AddHtml( "<tr><td>Or modify an existing wiki:</td> " );
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
		$wgOut->AddHtml( "<tr><td>Full site name:</td> " );
		$wgOut->AddHtml( "<td><input name=\"wpSitename\" value=\"{$this->sitename}\" /></td></tr>" );

		# Domain selection
		$wgOut->AddHtml( "<tr><td valign=\"top\">Domains:</td>" );
		$wgOut->AddHtml( "<td><textarea name=\"wpDomains\">{$this->domains}</textarea></td>" );
		$wgOut->AddHtml( "<td valign=\"top\"><i>(naked domains automatically<br />work for www and wiki as well)</i></td></tr>" );

		# Codebase selection
		$wgOut->AddHtml( "<tr><td>MediaWiki version:</td> " );
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
		$wgOut->AddHtml( "<tr><td>Sysop user:</td> " );
		$wgOut->AddHtml( "<td><input name=\"wpUser\" value=\"{$this->user}\" /></td></tr>" );
		$wgOut->AddHtml( "<tr><td>Sysop password:</td> " );
		$wgOut->AddHtml( "<td><input type=\"password\" name=\"wpPass\" /></td></tr>" );
		$wgOut->AddHtml( "<tr><td>Confirm password:</td> " );
		$wgOut->AddHtml( "<td><input type=\"password\" name=\"wpPass2\" /></td></tr>" );

		$wgOut->AddHtml( "<tr><td colspan=\"3\"><hr /></td></tr>" );
		$wgOut->AddHtml( "<tr><td /><td align=\"right\"><input type=\"submit\" name=\"wpSubmit\" value=\"Submit\" /></td></tr>" );

		$wgOut->AddHtml( "</table></form>" );
	}

	/**
	 * Create or update a wiki in accord with submitted data
	 */
	function processForm() {
		$dir = "/var/www/wikis/{$this->newid}";
		$mw = "/var/www/mediawiki-{$this->codebase}";

		# Validation (should use friendly JS instead)
		if ( empty( $this->pass ) )                           return $this->error = 'Please fill in the password fields!';
		if ( $this->pass !== $this->pass2 )                   return $this->error = 'The password fields do not match!';
		if ( empty( $this->newid ) && empty( $this->curid ) ) return $this->error = 'No ID specified!';
		if ( ereg( '[^a-zA-Z0-9_]', $this->newid ) )          return $this->error = 'Invalid wiki ID!';
		if ( is_dir( $dir ) )                                 return $this->error = 'A wiki with that ID already exists!';
		if ( empty( $this->domains ) )                        return $this->error = 'No domain specified!';
		if ( empty( $this->sitename ) )                       return $this->error = 'No site name specified!';
		if ( empty( $this->pass ) )                           return $this->error = 'Please fill in the password fields!';
		if ( empty( $this->user ) || ereg( '[^a-zA-Z0-9_]', $this->user ) ) return $this->error = 'Invalid user name!';

		if ( !empty( $this->newid ) ) {
			$id = $this->newid;

			# Create the wiki dir and codebase symlink
			if ( !is_dir( $mw ) ) return $this->error = "MediaWiki codebasee '$mw' not found!";
			shell_exec( "mkdir $dir" );
			shell_exec( "mkdir -m 777 $dir/files" );
			shell_exec( "ln -s $mw $dir/wiki" );

			# Create LocalSettings
			$ls = "<?php\n";
			$ls .= "\$wgSitename          = '{$this->sitename}';\n";
			$ls .= "\$wgShortName         = '$id';\n";
			$ls .= "\$wgDBname            = 'wikia';\n";
			$ls .= "\$wgDBprefix          = '{$id}_';\n";
			file_put_contents( "$dir/LocalSettings.php", $ls );
			
			# Add the database template to the "wikia" DB
			shell_exec( "/var/www/tools/add-db /var/www/empty-{$this->codebase}.sql wikia.{$id}_" );

			# Add the domain symlinks
			foreach( split( "\n", $this->domains ) as $domain ) {
				shell_exec( "ln -s $dir /var/www/domains/$domain" );
			}
		}

	}

}

/*
 * Called from $wgExtensionFunctions array when initialising extensions
 */
function wfSetupWikiaAdmin() {
	global $wgLanguageCode, $wgMessageCache;
	$wgMessageCache->addMessages( array( 'wikiaadmin' => 'Wikia Administration' ) );
	SpecialPage::addPage( new SpecialWikiaAdmin() );
}

