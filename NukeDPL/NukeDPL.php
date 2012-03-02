<?php
/**
 * NukeDPL extension - Mass delete by DPL query
 * {{Category:Extensions|NukeDPL}}{{php}}
 * See http://www.mediawiki.org/wiki/Extension:NukeDPL for installation and usage details
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author Aran Dunkley [http://www.organicdesign.co.nz/nad User:Nad]
 * @copyright © 2007 Aran Dunkley
 * @licence GNU General Public Licence 2.0 or later
 */

if( !defined( 'MEDIAWIKI' ) ) die( 'Not a valid entry point.' );

define( 'NUKEDPL_VERSION', '1.2.6, 2012-03-02' );

$wgGroupPermissions['sysop']['nuke'] = true;
$wgAvailableRights[] = 'nuke';
$wgExtensionFunctions[] = 'wfSetupNukeDPL';

# Text to be added into textbox by default
$wgNukeDPLDefaultText = '
distinct          = true | false
ignorecase        = true | false
title             = Article
nottitle          = Article
titlematch        = %fragment%
nottitlematch     = %fragment%
titleregexp       = ^.+$
nottitleregexp    = ^.+$
category          = Category1 | Category2
notcategory       = Category1 | Category2
categorymatch     = %fragment%
notcategorymatch  = %fragment%
categoryregexp    = ^.+$
notcategoryregexp = ^.+$
namespace         = Namespace1 | Namespace2
notnamespace      = Namespace1 | Namespace2
linksfrom         = Foo | Bar
notlinksfrom      = Foo | Bar
linksto           = Foo|Bar
notlinksto        = Foo|Bar
imageused         = Foo.jpg
imagecontainer    = Article1 | Article2
uses              = Template1 | Template2
notuses           = Template1 | Template2
redirects         = exclude | include | only
createdby         = User
notcreatedby      = User
modifiedby        = User
notmodifiedby     = User
lastmodifiedby    = User
notlastmodifiedby = User
';

$wgExtensionCredits['specialpage'][] = array(
	'name'        => 'NukeDPL',
	'author'      => '[http://www.organicdesign.co.nz/nad User:Nad]',
	'description' => 'Mass delete by DPL query',
	'url'         => 'http://www.mediawiki.org/wiki/Extension:NukeDPL',
	'version'     => NUKEDPL_VERSION
);

$dir = dirname( __FILE__ );
$wgExtensionMessagesFiles['NukeDPL'] = "$dir/NukeDPL.i18n.php";
require_once( "$IP/includes/SpecialPage.php" );
require_once( "$dir/NukeDPL.class.php" );

function wfSetupNukeDPL() {
	global $wgNukeDPL;
	$wgNukeDPL = new NukeDPL();
	SpecialPage::addPage( $wgNukeDPL );
}

