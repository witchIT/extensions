<?php
class LinkAttributes {

	public static function onLinkerMakeExternalLink( &$url, &$text, &$link, &$attribs, $linktype ) {
		global $wgAllowedLinkAttributes;
		if( substr( $text, 0, 1 ) == '{' ) {
			if( preg_match( '|(\{.+?\})|', $text, $match ) ) {
				$data = json_decode( $match[1], true );
				if( is_array( $data ) ) {
					$keys = array_unique( array_merge( $wgAllowedLinkAttributes, array_keys( $data ) ) );
					if( count( $keys ) <= count( $wgAllowedLinkAttributes ) ) {
						foreach( $data as $k => $v ) $attribs[$k] = array_key_exists( $k, $attribs ) ? $attribs[$k] . ' ' . $v : $v;
						$text = preg_replace( '|^\{.+?\} *|', '', $text );
						$link = "<a href=\"$url\"";
						foreach( $attribs as $k => $v ) $link .= " $k=\"$v\"";
						$link .= ">$text</a>";
						return false;
					}
				}
			}
		}
		return true;
	}

}
