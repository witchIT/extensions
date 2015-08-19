<?php

// No direct access
defined('_JEXEC') or die;

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
