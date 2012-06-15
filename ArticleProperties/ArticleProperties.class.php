<?php
abstract class ArticleProperties extends Article {

	// These are set by a sub-class if it should use its own database table
	public static $table = false;
	public static $columns = false;
	public static $prefix = '';

	// If set this includes member properties and i18n messages that should be available from the JavaScript side from mw.config()
	var $jsObj = false;
	var $jsProp = array();
	var $jsI18n = array();

	// Some methods can benefit from caching their results
	private static  $cache = array();

	// Record whether or not this request is passive here so we only call save() for non-passive edits
	var $mPassive = true;

	/**
	 * Contstruct as a normal article with no differences
	 */
	function __construct( $param ) {
		global $wgHooks;

		// Initialise the function cache
		self::$cache = array();

		// The text for newly created ArticleProperties articles should be preloaded with a default message
		// NOTE: this is not called for page previews
		$wgHooks['EditFormPreloadText'][] = $this;

		// Allow sub-classes to have an edit method that can add its own fields
		$wgHooks['EditPage::showEditForm:fields'][] = array( $this, 'onShowEditFormFields' );

		// Allow sub-classes to have a save method that can store its fields into the page_props
		$wgHooks['ArticleSaveComplete'][] = $this;

		// Remove associated properties when an article is deleted
		$wgHooks['ArticleDeleteComplete'][] = $this;

		return parent::__construct( $param );
	}

	/**
	 * Cache for expensive methods with small inputs and deterministic results
	 * - we use AP_VOID so that storing false or null doesn't confuse the cache logic
	 */
	function cache( $func, $key, $val = AP_VOID ) {
		$key = "$func\x07$key";
		if( $val === AP_VOID ) return array_key_exists( $key, self::$cache ) ? self::$cache[$key] : AP_VOID;
		return self::$cache[$key] = $val;
	}

	/**
	 * When a new article is created, allow PageProperties sub-class to specify if they or their sub-classes should be used for this article
	 * - this is called from Article::newFromTitle() which in a normal page render is called from MediaWiki::initializeArticle()
	 */
	public static function onArticleFromTitle( $title, &$page ) {
		global $wgOut, $wgJsMimeType;

		// ArticleProperties sub-classes can use this to select a new class for the page Article
		// - a new class name is returned for pre-defined classes
		// - or an array of ( classname, filename ) to lazy-load the class
		$class = null;
		wfRunHooks( 'ArticlePropertiesClassFromTitle', array( &$title, &$class ) );
		if( !$class ) return true;
		if( !is_array( $class ) ) $class = array( $class, false );
		list( $classname, $classfile ) = $class;

		// If a file was specified, declare the class now
		if( $classfile ) {
			require_once( $classfile );
		}

		// Set the page to an instance of the class specifying it to be non-passive (i.e. a full page render)
		$page = new $classname( $title, false );
		$page->mPassive = false;

		// Add any required properties to mw.config or specified object
		if( $obj = $page->jsObj ) {
			$script = '';
			$c = '';
			foreach( $page->jsProp as $k ) {
				$v = $page->$k;
				if( $v === true ) $v = 'true';
				elseif( $v === false ) $v = 'false';
				elseif( !is_numeric( $v ) ) $v = "\"$v\"";
				$script .= "$c\n\t$k: $v";
				$c = ',';
			}
			$wgOut->addScript( "<script type=\"$wgJsMimeType\">window.$obj = {" . $script . "\n};</script>" );
		}

		// No object specified, add props to mw.config
		else {
			foreach( $page->jsProp as $prop ) $wgOut->addJsConfigVars( $prop, $page->$prop );
		}

		// Add required i18n messages to mw.config
		foreach( $page->jsI18n as $msg ) $wgOut->addJsConfigVars( $msg, wfMsg( $msg ) );

		return true;
	}

	/**
	 * The text for newly created ArticleProperties articles should be preloaded with a default message,
	 * if this isn't done then the article won't get created since text content is needed
	 */
	function onEditFormPreloadText( &$textbox, &$title ) {
		$textbox = wfMsg( 'ap_preloadtext' );
		return true;
	}

	/**
	 * Executed for showEditForm hook of our article types and calls the sub-class edit function if exists
	 */
	function onShowEditFormFields( &$editpage, $out ) {
		$this->edit( $editpage, $out );
		return true;
	}

	/**
	 * Executed for ArticleSave hook of our article types and calls the sub-class save function if exists
	 * - only call sub-classes save() once incase they in turn do article-edits
	 * - and only call it for non-passive requests, not for programatic edits as they deal with properties themselves
	 */
	function onArticleSaveComplete( &$article, &$user, $text, $summary, $minor, $watch, $section, &$flags, $rev, &$status, $baseRevId ) {
		global $wgRequest;
		static $done = false;
		if( $done ) return true;
		$done = true;
		if( !$this->mPassive) $this->save( $wgRequest );
		return true;
	}

	function edit( &$editpage, $out ) {
	}

	function save( $request = false ) {
	}

	/**
	 * After an article is deleted remove it's properties
	 */
	function onArticleDeleteComplete( &$article, &$user, $reason, $id ) {
		$dbw = wfGetDB( DB_MASTER );
		$class = get_class( $this );
		$table = $dbw->tableName( $class::$table );
		$prefix = $class::$prefix;
		$page = $prefix . 'page';
		$dbw->delete( $table, array( $page => $id ) );
		return true;
	}

	/**
	 * Convert a property name to a DB column name
	 */
	public static function getColumnName( $name, $prefix ) {
		return $prefix . strtolower( $name );
	}

	/**
	 * Add a properties method to interface with the article's DB data in either page_props or its own table
	 */
	public function properties( $props = array() ) {
		$title = $this->getTitle();
		if( $id = $title->getArticleID() ) {
			$dbr = wfGetDB( DB_SLAVE );
			$class = get_class( $this );
			//if( $class::$table === false ) return array();
			$table = $dbr->tableName( $class::$table );
			$prefix = $class::$prefix;
			$page = $prefix . 'page';
			$change = array();
			$update = array();

			// Get the row if it exists
			if( !$row = $dbr->selectRow( $table, '*', array( $page => $id ) ) ) $row = array();

			// If the input array is empty, fill in all values from the row
			// (a reverse lookup array is needed to find a property name from a database column name)
			if( count( $props ) == 0 ) {
				$rev = array();
				foreach( $class::$columns as $prop => $type ) $rev[$prop] = self::getColumnName( $prop, $prefix );
				$rev = array_flip( $rev );
				foreach( $row as $k => $v ) {
					if( array_key_exists( $k, $rev ) ) $props[$rev[$k]] = $this->dbGetValue( $rev[$k], $v );
				}
			}

			// Otherwise return only those specified
			else {
				$ns = $title->getNamespace();
				foreach( $props as $k => $v1 ) {
					$col = self::getColumnName( $k, $prefix );

					// Read the current value of this property
					$v0 = array_key_exists( $col, $row ) ? $this->dbGetValue( $k, $row->$col ) : false;

					// If a key has a null value, then set to the read value
					if( $v1 === null ) $props[$k] = $v0;

					// Otherwise add to the change and update arrays if changed
					elseif( $v0 !== $v1 ) {
						$change[$k] = array( $v0, $v1 );
						$update[$col] = $this->dbSetValue( $k, $v1 );
					}
				}
			}

			// If anything changed, update the row and execute the change hook
			if( count( $change ) > 0 ) {
				$dbw = wfGetDB( DB_MASTER );
				if( $row ) $dbw->update( $table, $update, array( $page => $id ) );
				else {
					$update[$page] = $id;
					$dbw->insert( $table, $update );
				}
				wfRunHooks( 'ArticlePropertiesChanged', array( &$this, &$change ) );
			}
		}

		return $props;
	}

	/**
	 * Check if values read from the database have a processing function and call if so
	 */
	function dbGetValue( $k, $v ) {
		$method = "get$k";
		return in_array( $method, get_class_methods( $this ) ) ? $this->$method( $v ) : $v;
	}

	/**
	 * Check if values being written to the database have a processing function and call if so
	 */
	function dbSetValue( $k, $v ) {
		$method = "set$k";
		return in_array( $method, get_class_methods( $this ) ) ? $this->$method( $v ) : $v;
	}

	/**
	 * Simple wrapper to Database::select to abstract caller from table and column names, returns array of title results
	 */
	public static function query( $class, $conds = array(), $options = array() ) {
		$dbr = wfGetDB( DB_SLAVE );
		$table = $dbr->tableName( $class::$table );
		$prefix = $class::$prefix;
		$res = $dbr->select( $table, $prefix . 'page', $conds, __METHOD__, $options );
		$titles = array();
		while( $row = $dbr->fetchRow( $res ) ) $titles[] = Title::newFromID( $row[0] );
		$dbr->freeResult( $res );
		return $titles;
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

	/**
	 * Set an array of values from the global request
	 * - return the number of items changed
	 */
	function updatePropertiesFromRequest( $names ) {
		global $wgRequest;
		$cur = $this->properties();

		$props = array();
		foreach( $names as $k ) {
			$v = $wgRequest->getText( "wp$k", false );
			if( $v !== false ) {
				if( array_key_exists( $k, $cur ) && $cur[$k] != $v ) $changed++;
				$props[$k] = $v;
			}
		}
		$new = $this->properties( $props );

print_r($cur);
print_r($new);
die;

		$changed = 0;
		foreach( $names as $k ) if( array_key_exists( $k, $cur ) && $cur[$k] != $new[$k] ) $changed++;

		return $changed;
	}

	/**
	 * Get a value for a field from the current article
	 */
	function getValue( $name, $default = false ) {
		if( !$this->getTitle()->exists() ) return $default;
		$prop = $this->properties( array( $name => null ) );
		return $prop[$name] ? $prop[$name] : $default;
	}

	/**
	 * Set a value for a field from the current article
	 */
	function setValue( $name, $value ) {
		if( !$this->getTitle()->exists() ) return false;
		return $this->properties( array( $name => $value ) );
	}

	/**
	 * Render a label element for an input
	 */
	function label( $label, $name = false ) {
		if( $name === false ) $name = ucfirst( $label );
		return "<label for=\"wp$name\">" . wfMsg( $label ) . "</label>";
	}

	/**
	 * Render an input element with current value if the article already exists
	 */
	function input( $name, $default = '', $atts = false ) {
		if( !is_array( $atts ) ) $atts = array();
		if( !array_key_exists( 'id', $atts ) ) $atts['id'] = "wp$name";
		if( !array_key_exists( 'name', $atts ) ) $atts['name'] = "wp$name";
		if( !array_key_exists( 'type', $atts ) ) $atts['type'] = "text";
		$attstxt = '';
		foreach( $atts as $k => $v ) $attstxt .= " $k=\"$v\"";
		$value = $this->getValue( $name, $default );
		return "<input value=\"$value\"$attstxt />";
	}

	/**
	 * Render combined label and input as a table row
	 */
	function inputRow( $label, $name = false, $default = '', $extra = '', $atts = false ) {
		$label = $this->label( $label, $name );
		$input = $this->input( $name, $default, $atts );
		return "<tr><th>$label</th><td>$input$extra</td></tr>";
	}

	/**
	 * Render a select list with supplied options list and selected/default value from page_props if any
	 * - first parameter can be an array of attributes including name and id or just a text name
	 * - Name parameter will be given a preceding "wp" for input and id values
	 */
	function select( $atts, $options, $first = '', $default = '', $messages = true ) {

		// Build attributes
		if( is_array( $atts ) ) {
			$name = $atts['name'];
			$atts['name'] = "wp$name";
		} else {
			$name = $atts;
			$atts = array( 'name' => "wp$name" );
		}
		if( !array_key_exists( 'id', $atts ) ) $atts['id'] = $atts['name'];
		$attstxt = '';
		foreach( $atts as $k => $v ) $attstxt .= " $k=\"$v\"";

		if( $first === false ) $first = '';
		elseif( $first == '' ) $first = "<option />";
		else $first = "<option value=\"\">$first</option>";
		$value = $this->getValue( $name, $default );
		$html = "<select$attstxt>$first";
		foreach( $options as $k => $v ) {
			if( is_numeric( $k ) ) $k = $v;
			$text = $messages ? wfMsg( $v ) : $v;
			$selected = $value == $k ? ' selected="yes"' : '';
			$html .= "<option value=\"$k\"$selected>$text</option>";
		}
		return $html . "</select>";
	}

	/**
	 * Render a radio option group with supplied options list and selected/default value from page_props if any
	 */
	function options( $name, $options, $default = '' ) {
		$value = $this->getValue( $name, $options[0] );
		$html = '';
		foreach( $options as $opt ) {
			$text = wfMsg( $opt );
			$checked = $value == $opt ? ' checked="yes"' : '';
			$html .= "<input type=\"radio\" name=\"wp$name\" value=\"$opt\"$checked /><label>$text</label>";
		}
		return $html;
	}

	/**
	 * Render a textarea
	 */
	function textarea( $name, $default = '' ) {
		$value = $this->getValue( $name, $default );
		return "<textarea name=\"wp$name\" id=\"wp$name\">$value</textarea>";
	}

}
