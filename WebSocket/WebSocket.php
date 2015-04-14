<?php
/**
 * WebSocket extension - Allows live connections between the server and other current clients using WebSocket with fall-back to Ajax
 *
 * See http://www.mediawiki.org/wiki/Extension:TreeAndMenu for installation and usage details
 * - Tree component uses the FancyTree jQuery plugin, see http://wwwendt.de/tech/fancytree (changed from dTree to FancyTree in version 4, March 2015)
 * - Menu component uses Son of Suckerfish, see http://alistapart.com/article/dropdowns
 * 
 * @file
 * @ingroup Extensions
 * @author Aran Dunkley [http://www.organicdesign.co.nz/aran Aran Dunkley]
 * @copyright © 2015 Aran Dunkley
 * @licence GNU General Public Licence 2.0 or later
 */
if( !defined( 'MEDIAWIKI' ) ) die( 'Not an entry point.' );

define( 'WEBSOCKET_VERSION','0.0.0, 2015-04-14' );
define( 'WEBSOCKET_PORT', 1729 );

$wgTreeAndMenuPersistIfId = false; // Makes trees with id attributes have persistent state

$wgExtensionCredits['other'][] = array(
	'path'           => __FILE__,
	'name'           => 'WebSocket',
	'author'         => '[http://www.organicdesign.co.nz/aran Aran Dunkley]',
	'url'            => 'http://www.organicdesign.co.nz/Extension:WebSocket',
	'descriptionmsg' => 'websocket-desc',
	'version'        => WEBSOCKET_VERSION,
);

$wgExtensionMessagesFiles['WebSocket'] = __DIR__ . '/WebSocket.i18n.php';

class WebSocket {
}

new WebSocket();
