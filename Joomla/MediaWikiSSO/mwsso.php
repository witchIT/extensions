<?php
/**
 * @copyright	Copyright (C) 2015 Aran Dunkley
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

/**
 * LDAP Authentication Plugin
 *
 * @package		Joomla.Plugin
 * @subpackage	Authentication.mwsso
 * @since 2.5
 */
class plgSystemMwSSO extends JPlugin {

	/**
	 * We authenticate the user by checking the MW cookies and database
	 */
	public function onAfterInitialise() {
		$app = &JFactory::getApplication( 'site' );
		$config = JFactory::getConfig();
		$database = $config->get( 'db' );
		$prefix = $this->params->get( 'mw_prefix' );
		$group = $this->params->get( 'mw_group' );
		$user = ucfirst( $this->params->get( 'juser' ) );

		// load plugin params info
		//$mwsso_group = $this->params->get('mwsso_group');
		$mwsso_group = 'joomla';

		// Get the MW user ID from the cookie, or bail if none
		$cookie = $database . '_' . $prefix . 'UserID';
		$mwuser = array_key_exists( $cookie, $_COOKIE ) ? $_COOKIE[$cookie] : 0;

		// If user not logged in to MW log out
		if( $mwuser < 1 ) {
			JFactory::getApplication()->enqueueMessage( "Please log in to the wiki" );
			$app->logout();
			return;
		}

		// TODO: need to validate the MW cookie so users can't simply create a cookie with the UserID in it
		// (e.g. MW could set a cookie that is the hash of the user's password DB entry and the joomla's secret)

		// Connect to the MediaWiki database using all the same access details as the Joomla except the table prefix
		$option = array(
			'driver'   => $config->get( 'dbtype' ),
			'host'     => $config->get( 'host' ),
			'user'     => $config->get( 'user' ),
			'password' => $config->get( 'password' ),
			'database' => $database,
			'prefix'   => $prefix
		);
		$db = JDatabase::getInstance( $option );
		$query = $db->getQuery (true );
		$query->select( '1' );
		$query->from( $db->quoteName( $prefix . 'user_groups' ) );
		$query->where( "ug_user=$mwuser AND ug_group='$group'" );
		$db->setQuery($query);

		// If MW user is not in the specified group, log out
		if( !$db->loadRow() ) {
			JFactory::getApplication()->enqueueMessage( "Your wiki user must be in the \"$group\" group." );
			$app->logout();
			return;
		}

		// Get the user object from the name opton
		$query = $db->getQuery( true );
		$query->select( 'id' );
		$query->from( $db->quoteName( $config->get( 'dbprefix' ) . 'users' ) );
		$query->where( "username='$user'" );
		$db->setQuery($query);
		$row = $db->loadRow();
		if( !$row ) {
			JFactory::getApplication()->enqueueMessage( "Joomla user \"$user\" not found!" );
			$app->logout();
			return;
		}

		// Log in as the user
		$jUser = JFactory::getUser( $row[0] );
		$session =& JFactory::getSession();
		$session->set( 'user', $jUser );
	}
}
