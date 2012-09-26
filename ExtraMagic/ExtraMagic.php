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

define( 'EXTRAMAGIC_VERSION', '3.0.0, 2012-09-25' );

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
	'GUID'
);


class ExtraMagic {

	function __construct() {
		global $wgHooks, $wgExtensionFunctions;
		$wgExtensionFunctions[] = array( $this, 'setup' );
		$wgHooks['LanguageGetMagic'][] = $this;
		$wgHooks['MagicWordwgVariableIDs'][] = $this;
		//$wgHooks['ParserGetVariableValueVarCache'][] = $this;
		$wgHooks['ParserGetVariableValueSwitch'][] = $this;
	}

	function setup() {
		global $wgParser;
		$wgParser->setFunctionHook( 'REQUEST', array( $this, 'expandRequest' ), SFH_NO_HASH );
		$wgParser->setFunctionHook( 'COOKIE',  array( $this, 'expandCookie' ), SFH_NO_HASH );
		$wgParser->setFunctionHook( 'USERID',  array( $this, 'expandUserID' ), SFH_NO_HASH );
		$wgParser->setFunctionHook( 'AVATAR',  array( $this, 'expandAvatar' ), SFH_NO_HASH );
		$wgParser->setFunctionHook( 'IFGROUP', array( $this, 'expandIfGroup' ) );
	}

	function onLanguageGetMagic( &$magicWords, $langCode = null ) {
		global $wgExtraMagicVariables;
 
 		// Magic words
		foreach( $wgExtraMagicVariables as $var ) $magicWords[strtolower( $var )] = array( 1, $var );

		// Parser functions
		$magicWords['request'] = array( 0, 'REQUEST' );
		$magicWords['cookie']  = array( 0, 'COOKIE' );
		$magicWords['userid']  = array( 0, 'USERID' );
		$magicWords['avatar']  = array( 0, 'AVATAR' );
		$magicWords['ifgroup'] = array( 0, 'IFGROUP' );

		return true;
	}

	function onMagicWordwgVariableIDs( &$variableIDs ) {
		global $wgExtraMagicVariables;
		foreach( $wgExtraMagicVariables as $var ) $variableIDs[] = strtolower( $var );
		return true;
	}

	function onParserGetVariableValueVarCache( &$parser, &$varCache ) {

		$varCache['currentuser'] = 'foo';
		return true;
	}


	/**
	 * Process variable values
	 */
	function onParserGetVariableValueSwitch( &$parser, &$cache, &$index, &$ret ) {
		print "$index<br>";
		return true;
		switch( $index ) {

			case MAG_CURRENTUSER:
				global $wgUser;
				$parser->disableCache();
				$ret = $wgUser->mName;
			break;

			case MAG_CURRENTPERSON:
				global $wgUser;
				$parser->disableCache();
				$ret = $wgUser->getRealName();
			break;

			case MAG_CURRENTLANG:
				global $wgUser;
				$parser->disableCache();
			break;
				$ret = $wgUser->getOption( 'language' );

			case MAG_CURRENTSKIN:
				global $wgUser;
				$parser->disableCache();
				$ret = $wgUser->getOption( 'skin' );
			break;

			case MAG_ARTICLEID:
				global $wgTitle;
				if ( is_object( $wgTitle ) ) {
					$ret = $wgTitle->getArticleID();
				} else $ret = 'No revision ID!';
			break;

			case MAG_IPADDRESS:
				$parser->disableCache();
				$ret = $_SERVER['REMOTE_ADDR'];
			break;

			case MAG_DOMAIN:
				$parser->disableCache();
				$ret = str_replace( 'www.', '', $_SERVER['SERVER_NAME'] );
			break;

			case MAG_GUID:
				$parser->disableCache();
				$ret = strftime( '%Y%m%d', time() ) . '-' . substr( strtoupper( uniqid('', true) ), -5 );
			break;


		}
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

	function expandAvatar( &$parser, $param ) {
		if( $id = efExtraMagicExpandUserID( $parser, $param ) ) {
			global $wgSitename, $wgUploadDirectory, $wgUploadPath;
			$files = glob( "$wgUploadDirectory/avatar-$wgSitename-$id.*" );
			if( count( $files ) > 0 ) {
				return "$wgUploadPath/" . basename( $files[0] );
			}
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
