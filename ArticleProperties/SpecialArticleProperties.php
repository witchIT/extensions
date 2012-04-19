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
					$prefix = $class::$prefix;
					$table = $class::$table;
					$cols = $class::$columns;
					if( $table === false ) $wgOut->addHTML( "No DB table name defined for \"$class\" class\n" );
					elseif( $cols === false ) $wgOut->addHTML( "No DB columns defined for \"$class\" class\n" );

					// Create table for this class if it doesn't exists
					if( $table ) {
						$tbl = $dbw->tableName( $table );
						if( !$dbw->tableExists( $tbl ) ) {
							$query = "CREATE TABLE $tbl (\n    `{$prefix}page` INT(11) NOT NULL";
							$comma = ",\n";
							foreach( $cols as $name => $type ) {
								$name = ArticleProperties::getColumnName( $name, $prefix );
								$query .= "$comma    `$name` $type";
							}
							$query .= "\n)";
							$wgOut->addHTML( "<pre>$query</pre>\n" );
							$dbw->query( $query );
						}

						// If it does exist, check all the columns exist
						// TODO: check and adjust column types if necessary
						else {
							/*
							$chkcol = mysql_query("SELECT * FROM `my_table_name` LIMIT 1");
							$mycol = mysql_fetch_array($chkcol);
							if(!isset($mycol['my_new_column']))
								mysql_query("ALTER TABLE `my_table_name` ADD `my_new_column` BOOL NOT NULL DEFAULT '0'");
							*/
						}
					}
				}
			}

			// TMP: If there's an article_properties table, copy the property data to the zp_property table
			$this->migrateArticleProperties( 'properties', 'zp_', 20000 );

			$wgOut->addHTML( '</pre>' );
		}

		// Nothing submitted, render the form
		else {
			$url = Title::newFromText( 'ArticleProperties/submit', NS_SPECIAL )->getLocalUrl();
			$wgOut->addHTML("<form action=\"$url\"><br /><input type=\"submit\" value=\"Update ArticleProperties database tables\" /><br /></form>" );
		}
	}

	/**
	 * Migrates data from a single article_properties table into a class-specific table
	 */
	function migrateArticleProperties( $table, $prefix, $ns ) {
		global $wgOut;
		$dbw = &wfGetDB( DB_MASTER );

		// Get all the properties of the given type and store in $props hash
		$tbl = $dbw->tableName( 'article_properties' );
		$res = $dbw->select( $tbl, 'ap_page,ap_propname,ap_value', "ap_namespace = $ns" );
		$props = array();
		while( $row = $dbw->fetchRow( $res ) ) {
			$k = $row[0];
			if( array_key_exists( $k, $props ) ) $props[$k] = array( $row[1] => $row[2] );
			else $props[$k][$row[1]] = $row[2];
		}
		$dbw->freeResult( $res );

		// Insert them into the class-specific table
		$tbl = $dbw->tableName( $table );
		print_r($props);
		foreach( $props as $page => $data ) {
			$row = array( $prefix . 'page' => $page );
			foreach( $data as $k => $v ) {
				$col = ArticleProperties::getColumnName( $k, $prefix );
				$wgOut->addHTML("\t$k = $v\n");
				$row[$col] = $v;
			}
			$dbw->insert( $tbl, $row );
		}
	}
}
