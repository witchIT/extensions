<?php

// No direct access
defined('_JEXEC') or die;

class sm_ligmincha_freight extends shippingextRoot {

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

		// Check if all products are books
		plgSystemLigminchaFreight::$allbooks = true;
		foreach( $cart->products as $item ) {
			if( $item['category_id'] != 1 ) plgSystemLigminchaFreight::$allbooks = false;
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
				$prices['shipping'] = $prices['shipping'] * $cart->count_product;
				$prices['package'] = 0;
			break;

		}

		return $prices;
	}

	private function getFreightPrice( $weight, $type ) {
		$vendor = JSFactory::getTable('vendor', 'jshop');
		$vendor->loadMain();
		$client = JSFactory::getUser();
		$cep = preg_replace( '|[^\d]|', '', $client->d_zip ? $client->d_zip : $client->zip );
		$cost = $this->getCache( $weight, $type, $cep );
		if( $cost ) return $cost;
		$data = array(
			'email' => plgSystemLigminchaFreight::$pagseguro_email,
			'token' => plgSystemLigminchaFreight::$pagseguro_token,
			'currency' => 'BRL',
			'itemId1' => 1,
			'itemDescription1' => 'Livros do Ligmincha Brasil Loja',
			'itemAmount1' => '1.00',
			'itemQuantity1' => 1,
			'itemWeight1' => $weight,
			'reference' => strtoupper( substr( uniqid( 'LB' ), 1, 6 ) ),
			'senderName' => $vendor->shop_name,
			'senderAreaCode' => $vendor->zip, // Note this setting must be a phone area code not a CEP
			'senderEmail' => $vendor->email,
			'shippingType' => $type,
			'shippingAddressStreet' => $client->d_street ? $client->d_street : $client->street,
			'shippingAddressNumber' => $client->d_street_nr ? $client->d_street_nr : $client->street_nr,
			'shippingAddressPostalCode' => $cep,
			'shippingAddressCity' => $client->d_city ? $client->d_city : $client->city,
			'shippingAddressState' => $client->d_state ? $client->d_state : $client->state,
			'shippingAddressCountry' => 'BRA'
		);

		$result = $this->post( 'https://ws.pagseguro.uol.com.br/v2/checkout/', $data );
		$code = preg_match( '|<code>(.+?)</code>|', $result, $m ) ? $m[1] : false;
		if( $code ) {
			JFactory::getApplication()->enqueueMessage( "Code: $code" );
			$html = file_get_contents( "https://pagseguro.uol.com.br/v2/checkout/payment.html?code=$code" );
			$cost = preg_match( '|"freightRow".+?R\$.+?([\d,]+)|s', $html, $m ) ? $m[1] : 0;
			$cost = str_replace( ',', '.', $cost );
		} else JError::raiseWarning( '', curl_error( $result ) );
		if( $cost == 0 ) JError::raiseWarning( '', 'Failed to obtain freight price!' );
		else $this->setCache( $weight, $type, $cep, $cost );
		return $cost;
	}

	/**
	 * Send a POST requst using cURL
	 */
	private function post( $url, $data ) {
		$options = array(
			CURLOPT_POST => 1,
			CURLOPT_HEADER => 0,
			CURLOPT_URL => $url,
			CURLOPT_FRESH_CONNECT => 1,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_FORBID_REUSE => 1,
			CURLOPT_TIMEOUT => 4,
			CURLOPT_POSTFIELDS => http_build_query( $data )
		);

		$ch = curl_init();
		curl_setopt_array( $ch, $options );

		if( $result = curl_exec( $ch ) ) return $result;
		else JError::raiseWarning( '', curl_error( $ch ) );

		curl_close( $ch );
		return $result;
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
