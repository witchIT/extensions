<?php
# CategoryHook Extension{{php}}{{Category:Extensions|CategoryHook}}
# - Adds a hook allowing articles to be added to additional categories based on wikitext content
# - Started: 2007-04-18
# - See http://www.mediawiki.org/wiki/Extension:CategoryHook for installation and usage details
# - Licenced under LGPL (http://www.gnu.org/copyleft/lesser.html)
# - Author: http://www.organicdesign.co.nz/nad
 
if (!defined('MEDIAWIKI')) die('Not an entry point.');
 
define('CATEGORYHOOK_VERSION', '0.2.0, 2007-10-09');

$wgCatHookMagic                = "category";
$wgCatHookIfMagic              = "ifcat";
$wgExtensionFunctions[]        = 'wfSetupCatHook';
$wgHooks['LanguageGetMagic'][] = 'wfCatHookLanguageGetMagic';
 
$wgExtensionCredits['parserhook'][] = array(
	'name'        => 'CategoryHook',
	'author'      => '[http://www.organicdesign.co.nz/nad User:Nad]',
	'description' => 'Adds a hook called "CategoryHook" which allows rules-based categorisation, adds a parser-function for categorisation and adds a parser-function for checking if the current title is a member of a given category.',
	'url'         => 'http://www.mediawiki.org/wiki/Extension:CategoryHook',
	'version'     => CATEGORYHOOK_VERSION
	);
 
class CatHook {

	var $catList = array();
 
	# Constructor
	function CatHook() {
		global $wgParser,$wgCatHookMagic,$wgCatHookIfMagic,$wgHooks;
		$wgParser->setFunctionHook($wgCatHookMagic,array($this,'magicCategory'),SFH_NO_HASH);
		$wgParser->setFunctionHook($wgCatHookIfMagic,array($this,'magicIf'));

		# Only process if action is submit, no changes to categories otherwise
		if (array_key_exists( 'action', $_REQUEST ) && $_REQUEST['action'] == 'submit') {
			$wgCategoryHookCatList = array();
			$wgHooks['ParserBeforeStrip'][] = $this;
			$wgHooks['ParserAfterTidy'][]   = $this;
			}
		}
 
	# Expand the category-magic to nothing and parse separately as normal category links
	function magicCategory(&$parser,$cat,$sortkey = '') {
		if ($sortkey) $sortkey = "|$sortkey";
		$parser->parse("[[Category:$cat$sortkey]]",$parser->mTitle,$parser->mOptions,false,false);
		return '';
		}

	# Expand the #ifcat condition
	function magicIf(&$parser,$cat,$then = '',$else = '') {
		global $wgTitle;
		if (!is_object($wgTitle)) return $else;
		$id      = $wgTitle->getArticleID();
		$db      = &wfGetDB(DB_SLAVE);
		$cl      = $db->tableName('categorylinks');
		$result  = $db->query("SELECT 0 FROM $cl WHERE cl_from = $id AND cl_to = '$cat'");
		if ($result instanceof ResultWrapper) $result = $result->result;
		return is_array($db->fetchRow($result)) ? $then : $else;
		}
 
	# Run the hook to determine the categories to add/remove
	# - each item in the CatList is: catname => [ add(true) | del(false) , sortkey ]
	function onParserBeforeStrip(&$parser,&$text) {
                $title = $parser->mTitle;
                if (is_object($title) && is_object($parser->mOptions))
                        wfRunHooks('CategoryHook',array(&$parser,&$text,&$this->catList,$title->getDBkey()));
                return true;
		}

	# Add the categories
	function onParserAfterTidy(&$parser) {
		foreach ($this->catList as $dbkey => $item) {
			list($add,$sortkey) = $item;
			if ($add) $parser->mOutput->addCategory($dbkey,$sortkey);
			else unset($parser->mOutput->mCategories[$dbkey]);
			}
		return true;
		}

	# Needed in some versions to prevent Special:Version from breaking
	function __toString() { return 'CategoryHook'; }
 	}
 
# Called from $wgExtensionFunctions array when initialising extensions
function wfSetupCatHook() {
	global $wgCatHook;
	$wgCatHook = new CatHook();
	}
 
# Needed in MediaWiki >1.8.0 for magic word hooks to work properly
function wfCatHookLanguageGetMagic(&$magicWords,$langCode = 0) {
	global $wgCatHookMagic,$wgCatHookIfMagic;
	$magicWords[$wgCatHookMagic]   = array(0,$wgCatHookMagic);
	$magicWords[$wgCatHookIfMagic] = array(0,$wgCatHookIfMagic);
	return true;
	}

