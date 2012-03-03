<?php
/**
 * TemplateProperties - Synchronise article properties with template named parameters
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author Aran Dunkley (http://www.organicdesign.co.nz/nad)
 * @copyright © 2012 Aran Dunkley
 * @licence GNU General Public Licence 2.0 or later
 */
if( !defined( 'MEDIAWIKI' ) ) die( "Not an entry point." );
define( 'TEMPLATEPROPS_VERSION', "0.0.1, 2012-03-03" );

$wgExtensionFunctions[] = 'wfSetupTemplateProperties';
$wgExtensionCredits['specialpage'][] = array(
	'name'        => "TemplateProperties",
	'author'      => "[http://www.organicdesign.co.nz/nad Aran Dunkley]",
	'description' => "Synchronise article properties with template named parameters",
	'url'         => "http://www.mediawiki.org/wiki/TemplateProperties",
	'version'     => TEMPLATEPROPS_VERSION
);

// Load messages and dependencies
$dir = dirname( __FILE__ );
$wgExtensionMessagesFiles['TemplateProperties'] = "$dir/TemplateProperties.i18n.php";
$wgExtensionMessagesFiles['TemplatePropertiesMagic'] = "$dir/TemplateProperties.i18n.magic.php";
require_once( "$dir/TemplateProperties.class.php" );

function wfSetupTemplateProperties() {
	global $wgTemplateProperties;
	$wgTemplateProperties = new TemplateProperties();
}
