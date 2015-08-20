<?php

// No direct access
defined('_JEXEC') or die;

class sm_pagseguro_freight extends shippingextRoot {

	var $version = 2;

	function showShippingPriceForm( $params, &$shipping_ext_row, &$template ) {
	}

	function showConfigForm( $config, &$shipping_ext, &$template ) {
	}

	function getPrices( $cart, $params, $prices, &$shipping_ext_row, &$shipping_method_price ) {
		$weight = $cart->getWeightProducts();

		// Get the shipping type
		$id = $shipping_method_price->shipping_method_id;
		$type = JSFactory::getTable( 'shippingMethod', 'jshop' );
		$type->load( $id );
		$type = $type->getProperties()['name_pt-BR'];

		// If it's one of ours, calculate the price
		switch( $type ) {

			case 'PAC':
				$prices['shipping'] = 0;
				$prices['package'] = 0;
			break;

			case 'SEDEX':
				$prices['shipping'] = 0;
				$prices['package'] = 0;
			break;

			case '':
				$prices['shipping'] = 0;
				$prices['package'] = 0;
			break;

		}

		return $prices;
	}
}
