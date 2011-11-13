<?php
/**
 * MyFavorites extension - special page for organising favorite articles in the wiki
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author Aran Dunkley [http://www.organicdesign.co.nz/nad User:Nad]
 * @copyright © 2011 Aran Dunkley
 * @licence GNU General Public Licence 2.0 or later
 */
if( !defined( 'MEDIAWIKI' ) ) die( "Not an entry point." );
define( 'MYFAVORITES_VERSION', "1.0.2, 2011-11-10" );

$wgMyFavoritesColumns = 4;
$wgMyFavoritesTitleAjax = true;

$dir = dirname( __FILE__ );
$wgExtensionMessagesFiles['MyFavorites'] = "$dir/MyFavorites.i18n.php";
require_once( "$IP/includes/SpecialPage.php" );

$dir = preg_match( "|[\\/]extensions([\\/].*?)[\\/]MyFavorites\.php$|", __FILE__, $m ) ? $m[1] : '';
$wgMyFavoritesBaseUrl = "$wgScriptPath/extensions$dir/prototype-portal";
$wgUseAjax = true;
$wgExtensionFunctions[] = 'wfSetupMyFavorites';
$wgSpecialPages['MyFavorites'] = 'MyFavorites';
$wgSpecialPageGroups['WikidAdmin'] = 'od';
$wgExtensionCredits['specialpage'][] = array(
	'name'        => "MyFavorites",
	'author'      => "[http://www.organicdesign.co.nz/nad Aran Dunkley]",
	'description' => "Creates a [{{SERVER}}/Special:WhatLeadership special page] for organising favorite articles for Elance job 25366124",
	'url'         => "https://www.elance.com/php/collab/main/collab.php?bidid=25366124",
	'version'     => MYFAVORITES_VERSION
);

class MyFavorites extends SpecialPage {

	function __construct() {
		global $wgHooks, $wgUser, $wgTitle, $wgAjaxExportList, $wgUseAjax, $wgMyFavoritesTitleAjax;

		SpecialPage::SpecialPage( 'MyFavorites', false, true, false, false, false );

		$wgHooks['PersonalUrls'][] = $this;
		$wgHooks['UnknownAction'][] = $this;
		$wgAjaxExportList[] = 'MyFavorites::ajaxFavorites';
		$wgAjaxExportList[] = 'MyFavorites::ajaxPositions';
		if( $wgUseAjax ) $this->addPageScript();

		// Add the add/remove links in actions or by title
		if( $wgMyFavoritesTitleAjax ) {
			$wgHooks['OutputPageBeforeHTML'][] = $this;
		} else {
			$wgHooks['SkinTemplateTabs'][] = $this;
			$wgHooks['SkinTemplateNavigation'][] = $this;
		}
	}


	/**
	 * Render the special page
	 */
	function execute( $param ) {
		global $wgOut, $wgJsMimeType;
		$this->setHeaders();
		$this->addPortalScript();
		$this->renderPortlets();
	}


	/**
	 * Add the current title to favourite (none AJAX method)
	 */
	function onUnknownAction( $action, $article ) {
		global $wgOut;
		$title = $article->getTitle();
		$page = $title->getPrefixedText();
		$url = $title->getLocalUrl();
		$link = "<a href=\"$url\">$page</a>";
		
		if( $action == 'addfavorite' ) {
			if( $this->addFavorite( $page ) ) {
				$wgOut->addHtml( '<div class="successbox">' . wfMsg( 'myfavorites-added' ) . '</div>' );
			} else $wgOut->addHtml( '<div class="errorbox">' . wfMsg( 'myfavorites-notadded' ) . '</div>' );
			$wgOut->addHtml( '<div style="clear:both"></div>' );
			$wgOut->addHtml( wfMsg( 'returnto', $link ) );
			return false;
		}

		if( $action == 'removefavorite' ) {
			if( $this->removeFavorite( $page ) ) {
				$wgOut->addHtml( '<div class="successbox">' . wfMsg( 'myfavorites-removed' ) . '</div>' );
			} else $wgOut->addHtml( '<div class="errorbox">' . wfMsg( 'myfavorites-notremoved' ) . '</div>' );
			$wgOut->addHtml( '<div style="clear:both"></div>' );
			$wgOut->addHtml( wfMsg( 'returnto', $link ) );
			return false;
		}

		return true;
	}


	/**
	 * Add 'MyPortal' to personal URL's links
	 */
	function onPersonalUrls( &$urls, &$title ) {
		global $wgUser;
		if( $wgUser->isLoggedIn() && $title->getText() != 'MyFavorites' ) {
			$url = Title::newFromText( 'MyFavorites', NS_SPECIAL )->getLocalUrl();
			$urls = array(
				'myfavorites' => array( 'text' => wfMsg( 'myfavorites-personallink' ), 'href' => $url  ),
			) + $urls;
		}
		return true;
	}


	/**
	 * Add 'Add to favorites' to action links (monobook derivitives)
	 */
	function onSkinTemplateTabs( $skin, &$actions ) {
		global $wgTitle, $wgUser, $wgUseAjax;
		if( $wgUser->isLoggedIn() && is_object( $wgTitle ) && $wgTitle->exists() ) {
			$opt = $this->opt( $wgTitle->getPrefixedText() );
			if( $opt && $wgUser->getOption( $opt ) ) {
				$url = $wgUseAjax ? 'javascript:mfFavorites()' : $wgTitle->getLocalUrl( 'action=removefavorite' );
				$actions['myfavorites'] = array( 'text' => wfMsg( 'myfavorites-action-remove' ), 'class' => false, 'href' => $url );
			} else {
				$url = $wgUseAjax ? 'javascript:mfFavorites()' : $wgTitle->getLocalUrl( 'action=addfavorite' );
				$actions['myfavorites'] = array( 'text' => wfMsg( 'myfavorites-action-add' ), 'class' => false, 'href' => $url );
			}
		}
		return true;
	}


	/**
	 * Add 'Add to favorites' to action links (vector derivitives)
	 */
	function onSkinTemplateNavigation( $skin, &$actions ) {
		global $wgTitle, $wgUser, $wgUseAjax;
		if( $wgUser->isLoggedIn() && is_object( $wgTitle ) && $wgTitle->exists() ) {
			$opt = $this->opt( $wgTitle->getPrefixedText() );
			if( $opt && $wgUser->getOption( $opt ) ) {
				$url = $wgUseAjax ? 'javascript:mfFavorites()' : $wgTitle->getLocalUrl( 'action=removefavorite' );
				$actions['views']['myfavorites'] = array( 'text' => wfMsg( 'myfavorites-action-remove' ), 'class' => false, 'href' => $url );
			} else {
				$url = $wgUseAjax ? 'javascript:mfFavorites()' : $wgTitle->getLocalUrl( 'action=addfavorite' );
				$actions['views']['myfavorites'] = array( 'text' => wfMsg( 'myfavorites-action-add' ), 'class' => false, 'href' => $url );
			}
		}
		return true;
	}


	/**
	 * Add 'Add to favorites' after page title
	 */
	function onOutputPageBeforeHTML() {
		global $wgSciptPath, $wgTitle, $wgUser, $wgUseAjax, $wgOut, $wgJsMimeType;
		if( $wgUser->isLoggedIn() && is_object( $wgTitle ) && $wgTitle->exists() ) {
			$opt = $this->opt( $wgTitle->getPrefixedText() );
			if( $opt && $wgUser->getOption( $opt ) ) {
				$url = $wgUseAjax ? 'javascript:mfFavorites()' : $wgTitle->getLocalUrl( 'action=removefavorite' );
				$msg = wfMsg( 'myfavorites-action-remove' );
			} else {
				$url = $wgUseAjax ? 'javascript:mfFavorites()' : $wgTitle->getLocalUrl( 'action=addfavorite' );
				$msg = wfMsg( 'myfavorites-action-add' );
			}
			$style = "vertical-align:middle;padding-left:10pt;font-size:11pt;font-family:sans-serif";
			$wgOut->addScript( "<script type=\"$wgJsMimeType\">
				var t = document.getElementById( 'firstHeading' );
				t.innerHTML = t.innerHTML + '<span id=\"mf-title-ajax\" style=\"$style\"><a href=\"$url\">($msg)</a></span>';
			</script>" );
			$opt = $this->opt( $wgTitle->getPrefixedText() );
		}
		return true;
	}


	/**
	 * Add a title to the current users favorites
	 * - inserts the new item in the first position of the first column
	 * - returns false if favorite already exists
	 */
	function addFavorite( $page ) {
		global $wgUser;
		if( !$opt = $this->opt( $page ) ) {
			$opt = $this->opt( $page, true );
			$wgUser->setOption( $opt, $page );
			$wgUser->saveSettings();
			$pos = $this->getPositions( 0 );
			array_unshift( $pos, $this->optIndex( $opt ) );
			$this->setPositions( 0, $pos );
			return true;
		}
		return false;
	}


	/**
	 * Remove a title from the current users favorites
	 * - returns false if no such favorite exists
	 * - remove any references to nonexistent favorites in position data
	 */
	function removeFavorite( $page ) {
		global $wgUser, $wgMyFavoritesColumns;
		if( $opt = $this->opt( $page ) ) {
			$wgUser->setOption( $opt, false );
			$wgUser->saveSettings();
			$optn = $this->optIndex( $opt );
			for( $col = 0; $col < $wgMyFavoritesColumns; $col++ ) {
				$pos = $this->getPositions( $col );
				$i = array_search( $optn, $pos );
				if( $i === false ) $i = array_search( $optn . '9999', $pos );
				if( $i !== false ) {
					unset( $pos[$i] );
					$this->setPositions( $col, $pos );
				}
			}
			return true;
		}
		return false;
	}


	/**
	 * Return a list of all the favorites for the current user
	 * - id values that end in 9999 are minimised
	 */
	function getFavorites() {
		global $wgUser;
		$opts = array();
		foreach( $wgUser->getOptions() as $k => $page ) {
			if( preg_match( '|^mf-(\d+)|', $k, $m ) ) {
				$title = Title::newFromText( $page );
				if( is_object( $title ) ) $opts[$m[1]] = $page;
			}
		}
		return $opts;
	}

	/**
	 * Return array of items in the given column
	 */
	function getPositions( $col ) {
		global $wgUser;
		$opt = $wgUser->getOption( "mfpos$col", '' );
		return $opt !== '' ? explode( '|', $opt ) : array();
	}


	/**
	 * Update the position data for the given column
	 */
	function setPositions( $col, $pos ) {
		global $wgUser;
		$wgUser->setOption( "mfpos$col", implode( '|', $pos ) );
		$wgUser->saveSettings();
	}


	/**
	 * Called by AJAX to add the passed title to the users favorites
	 */
	function ajaxPositions() {
		global $wgMyFavoritesColumns, $wgRequest;
		for( $col = 0; $col < $wgMyFavoritesColumns; $col++ ) {
			$pos = $wgRequest->getArray( "widget_col_$col", array() );
			foreach( $pos as $k => $v ) $pos[$k] = substr( $v, 7 );
			self::setPositions( $col, $pos );
		}
		return '';
	}


	/**
	 * Called by AJAX to add the passed title to the users favorites
	 */
	function ajaxFavorites( $page ) {
		global $wgMyFavorites, $wgMyFavoritesTitleAjax;
		$page = str_replace( '_', ' ', $page );
		$opt = $wgMyFavorites->opt( $page );
		if( $opt ) {
			$wgMyFavorites->removeFavorite( $page );
			$msg = wfMsg( 'myfavorites-action-add' );
		} else {
			$opt = $wgMyFavorites->opt( $page, true );
			$wgMyFavorites->addFavorite( $page );
			$msg = wfMsg( 'myfavorites-action-remove' );
		}
		if( $wgMyFavoritesTitleAjax ) $msg = "($msg)";
		return "<a href=\"javascript:mfFavorites()\">$msg</a>";
	}


	/**
	 * Make necessary Javascript functions available to the portal special page
	 * - currently using prototype-portal from http://blog.xilinus.com/2007/9/4/prototype-portal-class-2
	 */
	function addPortalScript() {
		global $wgOut, $wgJsMimeType, $wgMyFavoritesBaseUrl;
		$wgOut->addExtensionStyle( "$wgMyFavoritesBaseUrl/../MyFavorites.css" );
		$wgOut->addExtensionStyle( "$wgMyFavoritesBaseUrl/themes/default.css" );
		$wgOut->addScript( "<script type=\"$wgJsMimeType\" src=\"$wgMyFavoritesBaseUrl/lib/prototype.js\"></script>" );
		$wgOut->addScript( "<script type=\"$wgJsMimeType\" src=\"$wgMyFavoritesBaseUrl/lib/effects.js\"></script>" );
		$wgOut->addScript( "<script type=\"$wgJsMimeType\" src=\"$wgMyFavoritesBaseUrl/lib/builder.js\"></script>" );
		$wgOut->addScript( "<script type=\"$wgJsMimeType\" src=\"$wgMyFavoritesBaseUrl/lib/dragdrop.js\"></script>" );
		$wgOut->addScript( "<script type=\"$wgJsMimeType\" src=\"$wgMyFavoritesBaseUrl/src/portal.js\"></script>" );
	}


	/**
	 * Make necessary Javascript functions available to the normal pages
	 */
	function addPageScript() {
		global $wgOut, $wgJsMimeType, $wgScriptPath;
		$wait = wfMsg( 'myfavorites-wait' );
		$load = "$wgScriptPath/skins/common/images/spinner.gif";
		$wgOut->addScript( "<script type=\"$wgJsMimeType\">
			function mfFavorites() {
				var e = document.getElementById('ca-myfavorites');
				if( e ) {
					e = e.childNodes[0];
					e.childNodes[0].innerHTML = '$wait';
				} else {
					e = document.getElementById('mf-title-ajax');
					e.innerHTML = '<img src=\"$load\" />&nbsp;$wait';
				}
				sajax_do_call('MyFavorites::ajaxFavorites',[wgPageName],e);
			}
		</script>" );
	}


	/**
	 * Render array of favorites as portlets
	 */
	function renderPortlets() {
		global $wgOut, $wgJsMimeType, $wgParser, $wgScriptPath, $wgMyFavoritesColumns;
		$favorites = $this->getFavorites();
		if( count( $favorites ) > 0 ) {

			// Fill portal columns from stored position data
			$init = '';
			$content = '';
			$i = 0;
			for( $col = 0; $col < $wgMyFavoritesColumns; $col++ ) {
				foreach( $this->getPositions( $col ) as $f ) {
					if( preg_match( "|^(.*?)9999$|", $f, $m ) ) {
						$f = 0 + ( '0' . $m[1] );
						$min = true;
					} else $min = false;
					$page = $favorites[$f];
					$title = Title::newFromText( $page );
					$url = $title->getLocalUrl();
					$article = new Article( $title );
					$text = $article->getSection( $article->getContent(), 0 );
					$html = $wgParser->parse( $text, $title, $wgParser->mOptions, true, true )->getText();
					$content .= "<div id=\"portlet-content-$i\" style=\"display:none\">$html</div>\n";
					$init .= "portal.add(new Xilinus.Widget('widget','widget_$f').setTitle('$page').setContent(document.getElementById('portlet-content-$i').innerHTML), $col);\n";
					if( $min ) $init .= "document.getElementById('content_widget_$f').hide();\n";
					$i++;
				}
			}

			// Add the portal columns, content and script
			$columns = '';
			$w = 100 / $wgMyFavoritesColumns;
			for( $i = 0; $i < $wgMyFavoritesColumns; $i++ ) {
				$columns .= "<div class=\"widget_col\" id=\"widget_col_$i\" style=\"width:$w%\"></div>\n";
			}
			$wgOut->addHTML( "$content\n<div id=\"page\">\n$columns</div>" );
			$wgOut->addHTML( "<div id=\"control_buttons\" style=\"display:none\">
					<a href=\"#\" onclick=\"minmaxWidget(this); return false;\" id=\"edit_button\"></a>
					<a href=\"#\" onclick=\"removeWidget(this); return false;\" id=\"delete_button\"></a>
				</div>" );
			$wgOut->addScript( "<script type=\"$wgJsMimeType\">
				var portal;
				function onOverWidget(portal, widget) {
					widget.getElement().insertBefore($('control_buttons'), widget.getElement().firstChild);
					$('control_buttons').show(); 
				} 
				function onOutWidget(portal, widget) {
					$('control_buttons').hide();
				} 
				function removeWidget(element) {
					var widget = $(element).up('.widget').widget;
					if (confirm('" . wfMsg( 'myfavorites-confirmremove' ) . "')) { 
						document.body.appendChild($('control_buttons').hide())
						new Ajax.Request('$wgScriptPath/index.php?action=ajax&rs=MyFavorites::ajaxFavorites&rsargs[]='+widget.getTitle().innerHTML);
						portal.remove(widget);
					}
				}
				function minmaxWidget(element) {
					var widget = $(element).up('.widget').widget;
					var content = document.getElementById('content_'+widget.getElement().getAttribute('id'));
					var display = content.getAttribute('style');
					content.style.display = content.style.display ? '' : 'none';
					onUpdate(portal);
				}
				function onUpdate(portal) {
					var parameters = '';
					portal._columns.each(function(column) {   
						var p = column.immediateDescendants().collect(function(element) {
							var c = document.getElementById('content_' + element.id);
							return column.id + '[]=' + element.id + (c.style.display ? '9999' : '');
						}).join('&') 
						parameters += p + '&';
					});
					new Ajax.Request('$wgScriptPath/index.php?action=ajax&rs=MyFavorites::ajaxPositions', {parameters: parameters});
				}
				function init() {
					portal = new Xilinus.Portal('#page div', {
						onOverWidget: onOverWidget,
						onOutWidget: onOutWidget,
						onUpdate: onUpdate,
						removeEffect: Effect.SwitchOff
					} );
					$init
					portal.addWidgetControls('control_buttons');
				}
				Event.observe(window, 'load', init);
			</script>" );
		} else $wgOut->addHTML( "<i>" . wfMsg( 'myfavorites-nofavorites' ) . "</i>\n" );
	}


	/**
	 * Return the name of the user option used to reference a favorite
	 * - returns false if no such favorite exists
	 * - set $create to true to return a new option name
	 */
	function opt( $page, $create = false ) {
		$opts = $this->getFavorites();
		$key = array_search( $page, $opts );
		if( $key === false && $create === true ) {
			$key = count( $opts ) > 0 ? max( array_keys( $opts ) ) + 1 : 0;
		}
		return $key !== false ? "mf-$key" : false;
	}

	/**
	 * Return the numeric index of the passed option name
	 */
	function optIndex( $opt ) {
		return substr( $opt, 3 );
	}
}

/**
 * Called from $wgExtensionFunctions array when initialising extensions
 */
function wfSetupMyFavorites() {
	global $wgMyFavorites;
	$wgMyFavorites = new MyFavorites();
	SpecialPage::addPage( $wgMyFavorites );
}

