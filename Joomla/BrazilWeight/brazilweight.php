<?php
/**
 * @copyright	Copyright (C) 2015 Aran Dunkley
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

/**
 * @package		Joomla.Plugin
 * @subpackage	System.mwsso
 * @since 2.5
 */
class plgSystemBrazilWeight extends JPlugin {

	/**
	 * Install our extension into the shipping_ext table if its not there
	 */
/*
	public function onAfterInitialise() {
		$app = &JFactory::getApplication( 'site' );
		$config = JFactory::getConfig();

		// Insert our extension into the ext table if it's not there
		$db = JFactory::getDbo();
		$tbl = '#__jshopping_shipping_ext_calc';
		$query = $db->getQuery( true );
		$query->select( '1' );
		$query->from( $tbl );
		$query->where( "name='BrazilWeight'" );
		$db->setQuery( $query );
		if( !$db->loadRow() ) {
			$query = "INSERT INTO `$tbl` "
				. "(`id`, `name`, `alias`, `description`, `params`, `shipping_method`, `published`, `ordering`) "
				. "VALUES (2, 'BrazilWeight', 'sm_brazil_weight', 'BrazilWeight', '', '', 1, 2)";
			$db->setQuery( $query );
		}
	}
}
*/
class sm_brazil_weight extends shippingextRoot {
	var $version = 2;

	function showShippingPriceForm($params, &$shipping_ext_row, &$template){        
		echo 'prices calculated automatically';
	}

	function showConfigForm($config, &$shipping_ext, &$template){
		echo 'no configuration required';
	}

	function getPrices( $cart, $params, $prices, &$shipping_ext_row, &$shipping_method_price ){
		$weight = $cart->getWeightProducts();
		$prices['shipping'] = $weight * 10;
		$prices['package'] = 0;
		return $prices;
	}
}
