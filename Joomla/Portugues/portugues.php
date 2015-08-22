<?php
/**
 * Modifies Joomshopping shipping types (PAC, SEDEX and Carta registrada) to calculate cost during checkout
 * - see components/com_jshopping/controllers/checkout.php for relavent events
 * 
 * @copyright	Copyright (C) 2015 Aran Dunkley
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

/**
 * @package		Joomla.Plugin
 * @subpackage	System.ligminchafreight
 * @since 2.5
 */
class plgSystemLigminchaFreight extends JPlugin {

	public static $cartaPrices = array(); // the table of prices per weight for carta registrada
	public static $allbooks;              // whether the order consists only of book or not (whether carta registrada is allowed or not)

	public function onAfterInitialise() {

		// And the Carta registrada prices (add the tracking price to each)
		$t = str_replace( ',', '.', $this->params->get( 'carta_track' ) );
		foreach( array( 100, 150, 200, 250, 300, 350, 400, 450 ) as $d ) {
				self::$cartaPrices[$d] = str_replace( ',', '.', $this->params->get( "carta$d" ) ) + $t;
		}

		// Install our extended shipping type if not already there
		// (should be done from onExtensionAfterInstall but can't get it to be called)
		// (or better, should be done from the xml with install/uninstall element, but couldn't get that to work either)
		$db = JFactory::getDbo();
		$tbl = '#__jshopping_shipping_ext_calc';
		$db->setQuery( "SELECT 1 FROM `$tbl` WHERE `name`='LigminchaFreight'" );
		$row = $db->loadRow();
		if( !$row ) {

			// Add the shipping type extension
			$query = "INSERT INTO `$tbl` "
				. "(`name`, `alias`, `description`, `params`, `shipping_method`, `published`, `ordering`) "
				. "VALUES( 'LigminchaFreight', 'sm_ligmincha_freight', 'LigminchaFreight', '', '', 1, 1 )";
			$db->setQuery( $query );
			$db->query();

			// Add our freight cost cache table
			$tbl = '#__ligmincha_freight_cache';
			$query = "CREATE TABLE IF NOT EXISTS `$tbl` (
				id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
				cep    INT UNSIGNED NOT NULL,
				weight INT UNSIGNED NOT NULL,
				time   INT UNSIGNED NOT NULL,
				pac    DECIMAL(5,2) NOT NULL,
				sedex  DECIMAL(5,2) NOT NULL,
				PRIMARY KEY (id)
			)";
			$db->setQuery( $query );
			$db->query();

			// Copy the sm_ligmincha_freight class into the proper place
			// (there's probably a proper way to do this from the xml file)
			$path = JPATH_ROOT . '/components/com_jshopping/shippings/sm_ligmincha_freight';
			$file = 'sm_ligmincha_freight.php';
			if( !is_dir( $path ) ) mkdir( $path );
			copy( __DIR__ . "/$file", "$path/$file" );
		}

	}

	/**
	 * Called on removal of the extension
	 */
	public function onExtensionAfterUnInstall() {

		// Remove our extended shipping type
		$db = JFactory::getDbo();
		$tbl = '#__jshopping_shipping_ext_calc';
		$db->setQuery( "DELETE FROM `$tbl` WHERE `name`='LigminchaFreight'" );
		$db->query();

		// Remove our freight cost cache table
		$tbl = '#__ligmincha_freight_cache';
		$db->setQuery( "DROP TABLE IF EXISTS `$tbl`" );
		$db->query();

		// Remove the script
		$path = JPATH_ROOT . '/components/com_jshopping/shippings/sm_ligmincha_freight';
		$file = 'sm_ligmincha_freight.php';
		if( file_exists( "$path/$file" ) ) unlink( "$path/$file" );
		if( is_dir( $path ) ) rmdir( $path );
	}

	/**
	 * If the order is not all books, remove the Carta registrada option
	 * (the $allbooks settings is updated in checkout by sm_ligmincha_freight class)
	 */
	public function onBeforeDisplayCheckoutStep4View( &$view ) {
			if( !self::$allbooks ) unset( $view->shipping_methods[2] ); // TODO: we should remove this by name not assume that it's id is 2
	}
}
