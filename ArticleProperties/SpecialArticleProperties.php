<?php

class SpecialArticleProperties extends SpecialPage {

	function __construct() {
		SpecialPage::SpecialPage( 'ArticleProperties', false );
	}

	function execute( $param ) {
		global $wgOut;
		$this->setHeaders();

		if( $param == 'submit' ) {
			$wgOut->addHTML( '<pre>' );
			$dbw = &wfGetDB( DB_MASTER );

			// Check all ArticleProperties sub-classes checking that their tables exists and they have matching columns
			foreach( get_declared_classes() as $class ) {
				if( get_parent_class( $class ) == 'ArticleProperties' ) {
					$wgOut->addHTML( "\nChecking class \"$class\"\n" );

					// Get the table name, prefix and columns names/types
					$vars = get_class_vars( $class );
					$prefix = $vars['prefix'];
					$table = $vars['table'];
					$cols = $vars['columns'];
					if( $table === false ) $wgOut->addHTML( "No DB table name defined for ArticleProperties class \"$class\"\n" );
					elseif( $cols === false ) $wgOut->addHTML( "No DB columns defined for ArticleProperties class \"$class\"\n" );

					// Create table for this class if it doesn't exists
					if( $table ) {
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
			}
			$wgOut->addHTML( '</pre>' );
		} else {
			$url = Title::newFromText( 'ArticleProperties/submit', NS_SPECIAL )->getLocalUrl();
			$wgOut->addHTML("<form action=\"$url\"><br /><input type=\"submit\" value=\"Update ArticleProperties database tables\" /><br /></form>" );
		}
	}

}
