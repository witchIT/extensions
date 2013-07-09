<?php
/**
 * ExtraMagic extension - Adds useful variables and parser functions
 *
 * See http://www.organicdesign.co.nz/Extension:ExtraMagic.php
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author [http://www.organicdesign.co.nz/User:Nad User:Nad]
 * @copyright © 2007 [http://www.organicdesign.co.nz/User:Nad User:Nad]
 * @licence GNU General Public Licence 2.0 or later
 */
if( !defined( 'MEDIAWIKI' ) ) die('Not an entry point.' );

define( 'EXTRAMAGIC_VERSION', '3.1.0, 2013-07-09' );

$wgExtensionCredits['parserhook'][] = array(
	'name'        => 'ExtraMagic',
	'author'      => '[http://www.organicdesign.co.nz/User:Nad User:Nad]',
	'description' => 'Adds useful variables and parser functions',
	'url'         => 'http://www.organicdesign.co.nz/Extension:ExtraMagic.php',
	'version'     => EXTRAMAGIC_VERSION
);

$wgExtraMagicVariables = array(
	'CURRENTUSER',
	'CURRENTPERSON',
	'CURRENTLANG',
	'CURRENTSKIN',
	'ARTICLEID',
	'IPADDRESS',
	'DOMAIN',
	'GUID',
	'USERPAGESELFEDITS'
);


class ExtraMagic {

	function __construct() {
		global $wgHooks, $wgExtensionFunctions;
		$wgExtensionFunctions[] = array( $this, 'setup' );
		$wgHooks['LanguageGetMagic'][] = $this;
		$wgHooks['MagicWordwgVariableIDs'][] = $this;
		$wgHooks['ParserGetVariableValueVarCache'][] = $this;
	}

	function setup() {
		global $wgParser;
		$wgParser->setFunctionHook( 'REQUEST', array( $this, 'expandRequest' ), SFH_NO_HASH );
		$wgParser->setFunctionHook( 'COOKIE',  array( $this, 'expandCookie' ), SFH_NO_HASH );
		$wgParser->setFunctionHook( 'USERID',  array( $this, 'expandUserID' ), SFH_NO_HASH );
		$wgParser->setFunctionHook( 'IFGROUP', array( $this, 'expandIfGroup' ) );
	}

	function onLanguageGetMagic( &$magicWords, $langCode = null ) {
		global $wgExtraMagicVariables;
 
 		// Magic words
		foreach( $wgExtraMagicVariables as $var ) $magicWords[strtolower( $var )] = array( 1, $var );

		// Parser functions
		$magicWords['REQUEST'] = array( 0, 'REQUEST' );
		$magicWords['COOKIE']  = array( 0, 'COOKIE' );
		$magicWords['USERID']  = array( 0, 'USERID' );
		$magicWords['IFGROUP'] = array( 0, 'IFGROUP' );

		return true;
	}

	function onMagicWordwgVariableIDs( &$variableIDs ) {
		global $wgExtraMagicVariables;
		foreach( $wgExtraMagicVariables as $var ) $variableIDs[] = strtolower( $var );
		return true;
	}

	function onParserGetVariableValueVarCache( &$parser, &$varCache ) {
		global $wgUser, $wgTitle;

		// CURRENTUSER
		$varCache['currentuser'] = $wgUser->mName;

		// CURRENTPERSON:
		$varCache['currentperson'] = $wgUser->getRealName();

		// CURRENTLANG:
		$varCache['currentlang'] = $wgUser->getOption( 'language' );

		// CURRENTSKIN:
		$varCache['currentlang'] = $wgUser->getOption( 'skin' );

		// ARTICLEID:
		$varCache['articleid'] = is_object( $wgTitle ) ? $ret = $wgTitle->getArticleID() : 'NULL';

		// IPADDRESS:
		$varCache['ipaddress'] = $_SERVER['REMOTE_ADDR'];

		// DOMAIN:
		$varCache['domain'] = str_replace( 'www.', '', $_SERVER['SERVER_NAME'] );

		// GUID:
		$varCache['guid'] = strftime( '%Y%m%d', time() ) . '-' . substr( strtoupper( uniqid('', true) ), -5 );

		// USERPAGESELFEDITS
		$out = '';
		$dbr = wfGetDB( DB_SLAVE );
		$tbl = array( 'user', 'page', 'revision' );
		$cond = array(
			'user_name = page_title',
			'rev_page  = page_id',
			'rev_user  = user_id'
		);
		$res = $dbr->select( $tbl, 'user_name', $cond, __METHOD__, array( 'DISTINCT', 'ORDER BY' => 'user_name' ) );
		foreach( $res as $row ) $out .= "*[[User:{$row->user_name}|{$row->user_name}]]\n";
		$varCache['userpageselfedits'] = $dbr->lastQuery();

		return true;
	}



	/**
	 * Expand parser functions
	 */
	function expandRequest( &$parser, $param, $default = '', $seperator = "\n" ) {
		$parser->disableCache();
		$val = array_key_exists( $param, $_REQUEST ) ? $_REQUEST[$param] : $default;
		if( is_array( $val ) ) $val = implode( $seperator, $val );
		return $val;
	}

	function expandCookie( &$parser, $param, $default = '' ) {
		$parser->disableCache();
		return array_key_exists( $param, $_COOKIE ) ? $_COOKIE[$param] : $default;
	}

	function expandUserID( &$parser, $param ) {
		if( $param ) {
			$col = strpos( $param, ' ' ) ? 'user_real_name' : 'user_name';
			$dbr = wfGetDB( DB_SLAVE );
			if( $row = $dbr->selectRow( 'user', array( 'user_id' ), array( $col => $param ) ) ) return $row->user_id;
		} else {
			global $wgUser;
			return $wgUser->getID();
		}
		return '';
	}

	function expandIfGroup( &$parser, $groups, $then, $else = '' ) {
		global $wgUser;
		$intersection = array_intersect( array_map( 'strtolower', explode( ',', $groups ) ), $wgUser->getEffectiveGroups() );
		return count( $intersection ) > 0 ? $then : $else;
	}
}

new ExtraMagic();
