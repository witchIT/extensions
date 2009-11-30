<?php
/**
 * AAP MediaWiki extension - Adds hooks for specific job type form for importing from CSV
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author [http://www.mediawiki.org/wiki/User:Nad User:Nad]
 */
if ( !defined( 'MEDIAWIKI' ) ) die( 'Not an entry point.' );

define( 'AAP_VERSION', '0.0.1, 2009-11-30' );

$wgAAPDataDir = dirname( __FILE__ ) . '/data';

$wgHooks['WikidAdminTypeFormRender_AAPImport'][] = 'wfAAP_Render';
$wgHooks['WikidAdminTypeFormProcess_AAPImport'][] = 'wfAAP_Process'

/**
 * Render a form in Special:WikidAdmin for the AAPImport type
 */
function wfAAP_Render( &$html ) {
	global $wgDCSDataDir;
	$html = 'Use an existing file: <select name="file"><option />';
	foreach ( glob( "$wgAAPDataDir/*" ) as $file ) $html .= '<option>' . basename( $file ) . '</option>';
	$html .= '</select><br />Or upload a new file:<br /><input name="upload" type="file" />';
	return true;
}

/**
 * Process posted data from AAPImport forms
 */
function wfAAP_Process( &$args, &$start ) {
	global $wgAAPDataDir, $wgRequest, $wgSiteNotice;

	# Handle upload if one specified, otherwise use existing one (if specified)
	if ( $target = basename( $_FILES['upload']['name'] ) ) {
		$args['file'] = "$wgAAPDataDir/$target"; 
		if ( file_exists( $args['file'] ) ) unlink( $args['file'] );
		if ( move_uploaded_file( $_FILES['upload']['tmp_name'], $args['file'] ) ) {
			$wgSiteNotice = "<div class='successbox'>File \"$target\" uploaded successfully</div>";
		} else $wgSiteNotice = "<div class='errorbox'>File \"$target\" was not uploaded for some reason :-(</div>";
	} else $args['file'] = $wgAAPDataDir . '/' . $wgRequest->getText('file');

	# Start the job if a valid file was specified, error if not
	if ( !$start = is_file( $args['file'] ) ) $wgSiteNotice = "<div class='errorbox'>No valid file specified, job not started!</div>";

	if ( $wgSiteNotice ) $wgSiteNotice .= "<div style='clear:both'></div>";

	return true;
}
