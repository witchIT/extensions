<?php
/**
 * People extension - Adds a parser function to add a list of people in the wiki
 * 
 * @package MediaWiki
 * @subpackage Extensions
 * @author [http://www.organicdesign.co.nz/nad Nad]
 * @copyright © 2012 [http://www.organicdesign.co.nz/nad Nad]
 * @licence GNU General Public Licence 2.0 or later
 */
if ( !defined( 'MEDIAWIKI' ) ) die( 'Not an entry point.' );

define( 'PEOPLE_VERSION', '0.0.1, 2012-09-20' );

$wgPeopleMagic                 = "people";
$wgExtensionFunctions[]        = 'wfSetupPeople';
$wgHooks['LanguageGetMagic'][] = 'wfPeopleLanguageGetMagic';

$wgExtensionCredits['parserhook'][] = array(
	'name'        => 'People',
	'author'      => '[http://www.organicdesign.co.nz/nad Nad]',
	'description' => 'Adds a parser function to add a list of people in the wiki',
	'url'         => 'http://www.organicdesign.co.nz/Extension:People',
	'version'     => PEOPLE_VERSION
);

class People {

	function __construct() {
		global $wgHooks, $wgParser, $wgPeopleMagic;
 		$wgParser->setFunctionHook( $wgPeopleMagic, array( $this, 'expandPeople' ) ); 
	}

	function expandPeople( &$parser ) {
		$text = '';
		$dbr = &wfGetDB(DB_SLAVE);
		$res = $dbr->select( $dbr->tableName( 'user' ), 'user_name,user_real_name' );
		while( $row = $dbr->fetchRow( $res ) ) {
			$text .= "== $row[1] ==\n";
			$text .= "[[Image:$row[0].jpg|48px|left]]{{User:$row[0]}}\n";
		}
		$dbr->freeResult( $res );
		return 'text';
        return array(
		$text,
		'found'   => true,
		'nowiki'  => false,
		'noparse' => false,
		'noargs'  => false,
		'isHTML'  => false
	);
	}

}

function wfSetupPeople() {
	global $wgPeople;
	$wgPeople = new People();
}

function wfPeopleLanguageGetMagic( &$magicWords, $langCode = 0 ) {
	global $wgPeopleMagic;
	$magicWords[$wgPeopleMagic] = array( 0, $wgPeopleMagic );
	return true;
}
