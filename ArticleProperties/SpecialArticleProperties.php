<?php

class SpecialArticleProperties extends SpecialPage {

	function __construct() {
		SpecialPage::SpecialPage( 'SpecialArticleProperties', false );
	}

	function execute( $param ) {
		global $wgOut;
		$this->setHeaders();
		if( $param == 'submit' ) {
			$dbw = &wfGetDB( DB_MASTER );

			// Check all ArticleProperties sub-classes checking that their tables exists and they have matching columns
			foreach( get_declared_classes() as $class ) {
				if( get_parent_class( $class ) == 'ArticleProperties' ) {

					// Get the table name, prefix and columns names/types
					$vars = get_class_vars( $class );
					$prefix = $vars['prefix'];
					$table = $vars['table'];
					$cols = $vars['columns'];
					if( $table === false ) die( "No DB table name defined for ArticleProperties class \"$class\"" );
					if( $cols === false ) die( "No DB columns defined for ArticleProperties class \"$class\"" );

					// Create table for this class if it doesn't exists
					$tbl = $dbw->tableName( $table );
					if( !$dbw->tableExists( $tbl ) ) {
						$query = "CREATE TABLE $tbl (";
						$comma = '';
						foreach( $cols as $name => $type ) {
							$name = $prefix . strtolower( $name );
							$query .= "$comma`$name` $type";
							$comma = ',';
						}
						$query .= ")";
						$wgOut->addHTML( "<pre>$query</pre>\n" );
						$dbw->query( $query );
					}

					// If it does exist, check all the columns exist
					// TODO: check and adjust column types if necessary
					else {
					}
				}
			}
		} else {
			$url = Title::newFromText( 'ArticleProperties/submit', NS_SPECIAL )->getLocalUrl();
			$wgOut->addHTML("<form action=\"$url\"><input type=\"submit\" value=\"update ArticleProperties database tables\" /></form>" );
		}
	}

}
