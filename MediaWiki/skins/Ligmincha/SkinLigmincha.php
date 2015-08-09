<?php
/**
 * DCS MediaWiki skin
 *
 * @file
 * @ingroup Skins
 */
if( !defined( 'MEDIAWIKI' ) ) die( -1 );

/**
 * SkinTemplate class for Dcs skin
 * @ingroup Skins
 */
class SkinLigmincha extends SkinTemplate {

	var $skinname = 'ligmincha', $stylename = 'ligmincha',
		$template = 'LigminchaTemplate', $useHeadElement = true,
		$dcsPage = false, $showTitle = true;

	public static function onRegistration() {
		global $wgExtensionFunctions;
		$wgExtensionFunctions[] = __CLASS__ . '::setup';
	}

	public static function setup() {
		global $wgDefaultSkin, $wgUser;
		//$wgDefaultSkin = 'ligmincha';
		//$wgUser->setOption( 'skin', $wgDefaultSkin );
	}

	/**
	 * Initializes output page and sets up skin-specific parameters
	 * @param $out OutputPage object to initialize
	 */
	public function initPage( OutputPage $out ) {
		global $wgExtensionAssetsPath;
		$out->addStyle( $wgExtensionAssetsPath . '/LigminchaSkin/styles/main.css' );
		parent::initPage( $out );
	}

	/**
	 * Load skin and user CSS files in the correct order
	 * fixes bug 22916
	 * @param $out OutputPage object
	 */
	function setupSkinUserCss( OutputPage $out ){
		parent::setupSkinUserCss( $out );
		$out->addModuleStyles( "skin.ligmincha" );
	}
}
