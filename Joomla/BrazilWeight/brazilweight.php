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

	// Install our extension into the shipping_ext table if its not there
	public function onAfterRoute() {
/*
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
*/
	require_once( __DIR__ . '/sm_brazil_weight.php' );
}
