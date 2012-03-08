<?php
class ArticleProperties extends Article {

	/**
	 * Contstruct as a normal article with no differences
	 */
	function __construct( $param ) {
		return parent::__contruct( $param );
	}

	/**
	 * Add a properties method to interface with the article's page_props
	 */
	public function properties( $props = array() ) {
		if( $id = $this->getArticleId() ) {
			$changed = false;
			$dbr = wfGetDB( DB_SLAVE );
			$dbw = false;

			// If the input array is empty, return all properties
			if( count( $props ) == 0 ) {
				$res = $dbr->select( 'page_props', 'pp_propname,pp_value', array( 'pp_page' => $id ) );
				while( $row = $dbr->fetchRow( $res ) ) $props[$row[0]] = $row[1];
				$dbr->freeResult( $res );
			}

			// Otherwise return only those specified
			else {
				foreach( $props as $k => $v1 ) {

					// Read the current value of this property
					$key = "ap_$k";
					$v0 = $dbr->selectField( 'page_props', 'pp_value', array( 'pp_page' => $id, 'pp_propname' => $key ) );

					// If a key has a null value, then read the value if there was one
					if( $v1 === null && $v0 !== false ) $v1 = $v0;

					// Otherwise set the value if it's changed
					elseif( $v0 !== $v1 ) {

						// Get a db connection to write to if we don't have one yet
						if( $dbw === false ) $dbw = wfGetDB( DB_MASTER );

						// Update the existing value in the props table
						if( $v0 === false ) {
							$dbw->insert( 'page_props', array( 'pp_page' => $id, 'prop_name' => $key, 'pp_value' => $v ) );
						}

						// Create this value in the props table
						else {
							$dbw->update( 'page_props', array( 'pp_value' => $v ), array( 'pp_page' => $id, 'pp_propname' => $key ) );
						}

						// add to array that will be sent ot the change event
						$changed[$k] = array( $v0, $v1 );
					}
				}
			}

			if( $changed ) wfRunHook( 'ArticlePropertiesChanged', array( &$this, &$changed ) );
		}

		return $props;
	}

	/**
	 * Add a static query method to select a list of articles by SQL conditions and options
	 */
	public static function query( $type, $conds, $options = null ) {
		$list = array();
		$dbr = wfGetDB( DB_SLAVE );
		$res = $dbr->select( 'page_props', 'pp_page', $conds, $options );
		while( $row = $dbr->fetchRow( $res ) ) $list[] = Title::newFromId( $row[0] );
		$dbr->freeResult( $res );
		return $list;
	}

	/**
	 * Add a static method to render results in a table format
	 */
	public static function table( &$titles, $atts = array(), $fields = false ) {

		// Open the table
		$html = "<table";
		if( array_key_exists( 'class', $atts ) ) $atts['class'] .= ' ap_results';
		else $atts['class'] = 'ap_results';
		foreach( $atts as $k => $v ) $html .= " $k=\"$v\"";
		$html .= ">\n";

		// Get fields from the first title if none specified
		if( !is_array( $fields ) ) {
			$ap = new ArticleProperties( $titles[0] );
			$fields = array_keys( $ap->properties() );
		}

		// Render the table header
		$html .= "<tr>";
		foreach( $fields as $field ) $html .= "<th>$field</th>";
		$html .= "</tr>\n";

		// Render the rows
		$html .= "<tr>";
		foreach( $titles as $title ) {
			$ap = new ArticleProperties( $title );
			foreach( $fields as $field ) {
				$prop = array( $field => null );
				$ap->properties( $prop );
				$val = $prop[$field];
				$html .= "<td>$val</td>";
			}
		}
		$html .= "</tr>\n";

		// Close the table and return content
		$html .= "</table>\n";
		return $html;
	}

}
