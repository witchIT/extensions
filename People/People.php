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
$wgExtensionMessagesFiles['People'] = dirname( __FILE__ ) . '/People.i18n.php';
$wgExtensionCredits['parserhook'][] = array(
	'name'        => 'People',
	'author'      => '[http://www.organicdesign.co.nz/nad Nad]',
	'description' => 'Adds a parser function to add a list of people in the wiki',
	'url'         => 'http://www.organicdesign.co.nz/Extension:People',
	'version'     => PEOPLE_VERSION
);

class People {

	function __construct() {
		global $wgParser, $wgPeopleMagic;
 		$wgParser->setFunctionHook( $wgPeopleMagic, array( $this, 'expandPeople' ) ); 
	}

	function expandPeople( &$parser ) {
		$parser->disableCache();
		$text = '';
		$dbr = &wfGetDB(DB_SLAVE);
		$res = $dbr->select( $dbr->tableName( 'user' ), 'user_name,user_real_name' );
		while( $row = $dbr->fetchRow( $res ) ) {
			$user = $row[0];
			$name = $row[1] ? $row [1] : $user;
			$text .= "== $name ==\n";
			$img = "$user.jpg";
			if( wfLocalFile( $img )->exists() ) $text .= "[[Image:$user.jpg|48px|left|link=User:$user]]";
			else {
				$url = Title::newFromText( 'Upload', NS_SPECIAL )->getFullUrl( "wpDestFile=$img" );
				$text .= "[[Image:Anon.png|48px|left|link=$url]]";
				$text .= "<div class=\"plainlinks\">[$url " . wfMsg( 'people-upload-image' ) . "]</div>\n\n";
			}
			$title = Title::newFromText( $user, NS_USER );
			if( $title->exists() ) {
				$article = new Article( $title );
				$text .= $article->getContent();
			}
			else $text .= "[[User:$user|" . wfMsg( 'people-create-intro' ) . "]]\n<div style=\"clear:both\"></div>\n";
		}
		$dbr->freeResult( $res );
		return $text;
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
