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
					if( $table === false ) $wgOut->addHTML( "No DB table name defined for \"$class\" class\n" );
					elseif( $cols === false ) $wgOut->addHTML( "No DB columns defined for \"$class\" class\n" );

					// Create table for this class if it doesn't exists
					if( $table ) {
						$tbl = $dbw->tableName( $table );
						if( !$dbw->tableExists( $tbl ) ) {
							$query = "CREATE TABLE $tbl (\n    `{$prefix}page` INT(11) NOT NULL";
							$comma = ",\n";
							foreach( $cols as $name => $type ) {
								$name = ArticleProperties::getColumnName( $name );
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
			$wgOut->addHTML( '</pre>' );
		} else {
			$url = Title::newFromText( 'ArticleProperties/submit', NS_SPECIAL )->getLocalUrl();
			$wgOut->addHTML("<form action=\"$url\"><br /><input type=\"submit\" value=\"Update ArticleProperties database tables\" /><br /></form>" );
		}
	}

}
