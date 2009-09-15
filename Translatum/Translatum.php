<?php
/**
 * Translatum MediaWiki extension - Extension to encapsulate the specific configuration required by translatum.gr wikis
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author [http://www.mediawiki.org/wiki/User:Nad User:Nad]
 */
if ( !defined( 'MEDIAWIKI' ) ) die( 'Not an entry point.' );

define( 'TRANSLATUM_VERSION', '2.0.0, 2009-09-15' );

$wgTranslatumDataDir = dirname( __FILE__ ) . '/data';

$wgExtensionFunctions[] = 'wfSetupTranslatum';

$wgExtensionCredits['parserhook'][] = array(
	'name'        => "Translatum",
	'author'      => "[http://www.mediawiki.org/wiki/User:Nad User:Nad]",
	'description' => "Extension to encapsulate the specific configuration required by translatum.gr wikis",
	'url'         => "http://www.translatum.gr",
	'version'     => TRANSLATUM_VERSION
);

class Translatum {

	function __construct() {
		global $wgHooks;
		$wgHooks['WikidAdminTypeFormRender_TranslatumImport'][] = $this;
		$wgHooks['WikidAdminTypeFormProcess_TranslatumImport'][] = $this;
	}

	/**
	 * Render a form in Special:WikidAdmin for the TranslatumImport type
	 */
	function onWikidAdminTypeFormRender_TranslatumImport( &$dcs, &$html ) {
		global $wgTranslatumDataDir;
		$html = 'Use an existing file: <select name="file"><option />';
		foreach ( glob( "$wgTranslatumDataDir/*" ) as $file ) $html .= '<option>' . basename( $file ) . '</option>';
		$html .= '</select><br />Or upload a new file:<br /><input name="upload" type="file" />';
		return true;
	}

	/**
	 * Process posted data from TranslatumImport forms
	 */
	function onWikidAdminTypeFormProcess_TranslatumImport( &$dcs, &$args, &$start ) {
		global $wgTranslatumDataDir, $wgRequest, $wgSiteNotice;

		# Handle upload if one specified, otherwise use existing one (if specified)
		if ( $target = basename( $_FILES['upload']['name'] ) ) {
			$args['file'] = "$wgTranslatumDataDir/$target";
			if ( move_uploaded_file( $_FILES['upload']['tmp_name'], $args['file'] ) ) {
				$wgSiteNotice = "File \"$target\" uploaded successfully";
			} else $wgSiteNotice = "File \"$target\" was not uploaded for some reason :-(";
		} else $args['file'] = $wgTranslatumDataDir . '/' . $wgRequest->getText( 'file' );

		# Error if none uploaded and no existing one specified either
		if ( !is_file( $args['file'] ) ) {
			$wgSiteNotice = "No valid file specified, job not started!";
			$start = false;
		}

		return true;
	}

/**
 * Called from $wgExtensionFunctions array when initialising extensions
 */
function wfSetupTranslatum() {
	global $wgTranslatum;
	$wgDCS = new Translatum();
}
