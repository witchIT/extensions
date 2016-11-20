<?php
/**
 * An experimental plugin for Oragnic Design
 *
 * @package   StatusNet
 * @author    Aran Dunkley <aran@organicdesign.co.nz>
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html AGPL 3.0
 * @link      https://www.organicdesign.co.nz/GNU_social
 */

if (!defined('STATUSNET')) {
	exit(1);
}

class OrganicDesignPlugin extends Plugin {

	function onStartShowHeadElements( $action ) {
		//$action->element( 'meta', array( 'name' => $metaname, 'content' => $content ) );
		return true;
	}

	/**
	 * Add a css
	 */
	function onEndShowStyles( $action ) {
		$action->cssLink( $this->path( 'css/organicdesign.css' ) );
		return true;
	}

	function onPluginVersion( array &$versions ) {
		$versions[] = array(
			'name' => 'Organic Design',
			'version' => '0.0.1',
			'author' => 'Aran Dunkley',
			'homepage' => 'https://www.organicdesign.co.nz/GNU_social',
			'rawdescription' => _m( 'An experimental Organic Design plugin to learn how things work' )
		);
		return true;
	}
}
