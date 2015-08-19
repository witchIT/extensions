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

		// Check if we're in the admin site
		$admin = preg_match( '|^/administrator/|', $_SERVER['REQUEST_URI'] );

		// Get the MW user ID from the cookie, or bail if none
		$cookie = $database . '_' . $prefix . 'UserID';
		$mwuser = array_key_exists( $cookie, $_COOKIE ) ? $_COOKIE[$cookie] : 0;

		// If user not logged in to MW log out
		if( $mwuser < 1 ) {
			if( $admin ) JFactory::getApplication()->enqueueMessage( "Please log in to the wiki" );
			$app->logout();
			return;
		}

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

		// Get the user's token cookie
		$cookie = $database . '_' . $prefix . 'Token';
		$token = array_key_exists( $cookie, $_COOKIE ) ? $_COOKIE[$cookie] : false;
		if( !$token ) {
			JFactory::getApplication()->enqueueMessage( "No token!" );
			$app->logout();
			return;
		}

		// Ensure that the token cookie matches their database entry
		$query = $db->getQuery (true );
		$query->select( 'user_token' );
		$query->from( $db->quoteName( $prefix . 'user' ) );
		$query->where( "user_id=$mwuser" );
		$db->setQuery($query);
		$db_token = $db->loadRow();
		$db_token = $db_token ? $db_token[0] : false;
		if( $token !== $db_token ) {
			JFactory::getApplication()->enqueueMessage( "Invalid token!" );
			$app->logout();
			return;
		}

		// If MW user is not in the specified group, log out
		$query = $db->getQuery (true );
		$query->select( '1' );
		$query->from( $db->quoteName( $prefix . 'user_groups' ) );
		$query->where( "ug_user=$mwuser AND ug_group='$group'" );
		$db->setQuery($query);
		if( !$db->loadRow() ) {
			if( $admin ) JFactory::getApplication()->enqueueMessage( "Your wiki user must be in the \"$group\" group." );
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
			if( $admin ) JFactory::getApplication()->enqueueMessage( "Joomla user \"$user\" not found!" );
			$app->logout();
			return;
		}

		// Log in as the user
		$jUser = JFactory::getUser( $row[0] );
		$session =& JFactory::getSession();
		$session->set( 'user', $jUser );
	}
}
