<?php

// No direct access
defined('_JEXEC') or die;

class sm_ligmincha_freight extends shippingextRoot {

	var $version = 2;

	var $cost = array(); // local cache of the shipping prices for this request

	function showShippingPriceForm( $params, &$shipping_ext_row, &$template ) {
	}

	function showConfigForm( $config, &$shipping_ext, &$template ) {
	}

	/**
	 * This method is called for each of the shipping methods options in the checkout
	 * (and also on subsequent pages for the chosen method)
	 */
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
			if( $item['category_id'] == 1 ) {
				for( $i = 0; $i < $item['quantity']; $i++ ) $weights[] = $item['weight'];
			} else plgSystemLigminchaFreight::$allbooks = false;
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
					$i = 50*(int)($w*20); // price divisions are in multiples of 50 grams
					$price += plgSystemLigminchaFreight::$cartaPrices[$i];
				}
				$prices['shipping'] = $price;
				$prices['package'] = 0;
			break;

		}

		return $prices;
	}

	/**
	 * Return a single price given a weight and shipping type
	 */
	private function getFreightPrice( $weight, $type ) {
		$vendor = JSFactory::getTable('vendor', 'jshop');
		$vendor->loadMain();
		$client = JSFactory::getUser();
		$cep2 = preg_replace( '|[^\d]|', '', $client->d_zip ? $client->d_zip : $client->zip );

		// Local cache (stores in an array so that no lookups are required for the same data in the same request)
		if( array_key_exists( $type, $this->cost ) ) return $this->cost[$type];

		// DB cache (costs for pac and sedex are stored in the database per weight and CEP for a day)
		$cost = $this->getCache( $weight, $cep2 );
		if( $cost ) return $cost[$type];

		// Not cached locally or in the database, get price from the external API and store locally and in database
		$cep1 = $vendor->zip;
		$url = "http://developers.agenciaideias.com.br/correios/frete/json/$cep1/$cep2/$weight/1.00";
		$result = file_get_contents( $url );
		if( preg_match( '|"sedex":"([\d.]+)","pac":"([\d.]+)"|', $result, $m ) ) {
			$this->cost[1] = $m[2];
			$this->cost[2] = $m[1];
			$cost = $this->cost[$type];
			$this->setCache( $weight, $cep2, $this->cost );
		} else JError::raiseWarning( '', "Error: $result" );
		return $cost;
	}

	/**
	 * Check if a database cache entry exists for this weight and destination
	 */
	private function getCache( $weight, $cep ) {
		$weight *= 1000;
		$db = JFactory::getDbo();
		$tbl = '#__ligmincha_freight_cache';

		// Only keep cache entries forr a day
		$expire = time() - 86400;
		$query = "DELETE FROM `$tbl` WHERE time < $expire";
		$db->setQuery( $query );
		$db->query();

		// Load and return the item if any match our parameters
		$db->setQuery( "SELECT pac,sedex FROM `$tbl` WHERE cep=$cep AND weight=$weight ORDER BY time DESC LIMIT 1" );
		$row = $db->loadRow();
		return $row ? array( 1 => $row[0], 2 => $row[1] ) : false;
	}

	/**
	 * Create a database cache entry for this weight and destination
	 */
	private function setCache( $weight, $cep, $costs ) {
		$weight *= 1000;
		$db = JFactory::getDbo();
		$tbl = '#__ligmincha_freight_cache';

		// Delete any of the same parameters
		$query = "DELETE FROM `$tbl` WHERE cep=$cep AND weight=$weight";
		$db->setQuery( $query );
		$db->query();

		// Insert new item with these parameters
		$pac = $costs[1];
		$sedex = $costs[2];
		$query = "INSERT INTO `$tbl` (cep, weight, time, pac, sedex) VALUES( $cep, $weight, " . time() . ", $pac, $sedex )";
		$db->setQuery( $query );
		$db->query();
	}
}
