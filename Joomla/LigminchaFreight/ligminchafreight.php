<?php
/**
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

	public function onAfterInitialise() {

		// Install our extended shipping type if not already there
		// (should be done from onExtensionAfterInstall but can't get it to be called)
		$db = JFactory::getDbo();
		$tbl = '#__jshopping_shipping_ext_calc';
		$db->setQuery( "SELECT 1 FROM `$tbl` WHERE `name`='LigminchaFreight'" );
		$row = $db->loadRow();
		if( !$row ) {
			$query = "INSERT INTO `$tbl` "
				. "(`name`, `alias`, `description`, `params`, `shipping_method`, `published`, `ordering`) "
				. "VALUES( 'LigminchaFreight', 'sm_ligmincha_freight', 'LigminchaFreight', '', '', 1, 1 )";
			$db->setQuery( $query );
			$db->query();
			JFactory::getApplication()->enqueueMessage( "sm_ligmincha_script added into 'jshopping_shipping_ext_calc' database table." );
		}

	}

	public function onExtensionAfterUnInstall() {

		// Remove our extended shipping type
		$db = JFactory::getDbo();
		$tbl = '#__jshopping_shipping_ext_calc';
		$db->setQuery( "DELETE FROM `$tbl` WHERE `name`='LigminchaFreight'" );
		$db->query();
		JFactory::getApplication()->enqueueMessage( "sm_ligmincha_script removed from 'jshopping_shipping_ext_calc' database table." );
	}
}
