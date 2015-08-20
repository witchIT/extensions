<?php

// No direct access
defined('_JEXEC') or die;

class sm_ligmincha_freight extends shippingextRoot {

	var $version = 2;

	var $cost = array();

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

		// Check if all products are books and gather their weights
		$weights = array();
		plgSystemLigminchaFreight::$allbooks = true;
		foreach( $cart->products as $item ) {
			if( $item['category_id'] == 1 ) $weights[] = $item['weight'];
			else plgSystemLigminchaFreight::$allbooks = false;
		}

		// If it's one of ours, calculate the price
		switch( $type ) {

			case 'PAC':
				$prices['shipping'] = $this->getFreightPrice( $weight, 1 );
				$prices['package'] = 0;
			break;

			case 'SEDEX':
				$prices['shipping'] = $this->getFreightPrice( $weight, 2 );
				$prices['package'] = 0;
			break;

			case 'Carta Registrada':
				$price = 0;
				foreach( $weights as $w ) {
						$i = 50*(int)($w/50);
						$price += plgSystemLigminchaFreight::$cartaPrices[$i];
				}
				$prices['shipping'] = $price;
				$prices['package'] = 0;
			break;

		}

		return $prices;
	}

	private function getFreightPrice( $weight, $type ) {
		$vendor = JSFactory::getTable('vendor', 'jshop');
		$vendor->loadMain();
		$client = JSFactory::getUser();

		// Local cache
		if( array_key_exists( $type, $this->cost ) ) return $this->cost[$type];

		// DB cache
		//$cost = $this->getCache( $weight, $type, $cep );
		//if( $cost ) return $cost;

		$cep1 = $vendor->zip;
		$cep2 = preg_replace( '|[^\d]|', '', $client->d_zip ? $client->d_zip : $client->zip );
		$weight /= 1000;
		$url = "http://developers.agenciaideias.com.br/correios/frete/json/$cep1/$cep2/$weight/1.00";
		$result = file_get_contents( $url );
		if( preg_match( '|"sedex":"([\d.]+)","pac":"([\d.]+)"|', $result, $m ) ) {
			$this->cost[1] = $m[2];
			$this->cost[2] = $m[1];
			$cost = $this->cost[$type];
		} else JError::raiseWarning( '', 'Failed to obtain freight price!' );
		//$this->setCache( $weight, $type, $cep, $cost );
		return $cost;
	}

	/**
	 * Check if any cache entry exists for these parameters
	 */
	private function getCache( $weight, $type, $cep ) {
		$db = JFactory::getDbo();
		$tbl = '#__ligmincha_freight_cache';

		// Delete any expired items after a day
		$expire = time() - 86400;
		$query = "DELETE FROM `$tbl` WHERE time < $expire";
		$db->setQuery( $query );
		$db->query();

		// Load and return the item if any match our parameters
		$db->setQuery( "SELECT cost FROM `$tbl` WHERE type=$type AND cep=$cep AND weight=$weight ORDER BY time DESC LIMIT 1" );
		$row = $db->loadRow();
		return $row ? $row[0] : 0;
	}

	/**
	 * Create a cache entry for these parameters
	 */
	private function setCache( $weight, $type, $cep, $cost ) {
		$db = JFactory::getDbo();
		$tbl = '#__ligmincha_freight_cache';

		// Delete any of the same parameters
		$query = "DELETE FROM `$tbl` WHERE type=$type AND cep=$cep AND weight=$weight";
		$db->setQuery( $query );
		$db->query();

		// Insert new item with these parameters
		$query = "INSERT INTO `$tbl` (type, cep, weight, time, cost) VALUES( $type, $cep, $weight, " . time() . ", $cost )";
		$db->setQuery( $query );
		$db->query();
	}

}
