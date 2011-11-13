<?php
# Extension:EmailToWiki{{Category:Extensions|EmailToWiki}}{{php}}{{Category:Extensions created with Template:Extension|EmailToWiki}}
# - Licenced under LGPL (http://www.gnu.org/copyleft/lesser.html)
# - Author:  [http://www.organicdesign.co.nz/nad User:Nad]
# - Started: 2007-05-25
# - See http://www.mediawiki.org/wiki/Extension:EmailToWiki for installation and usage details

# NOTE: This script just adds the version and extension information to Special:version,
#       the actual work is done by EmailToWiki.pl which should be run from the crontab

if (!defined('MEDIAWIKI')) die('Not an entry point.');

define('EMAILTOWIKI_VERSION','1.0.2, 2008-05-29');

$wgExtensionCredits['other'][] = array(
	'name'        => 'EmailToWiki',
	'author'      => '[http://www.organicdesign.co.nz/nad User:Nad]',
	'description' => 'Allows emails to be sent to the wiki and added to an existing or new article',
	'url'         => 'http://www.mediawiki.org/wiki/Extension:EmailToWiki',
	'version'     => EMAILTOWIKI_VERSION
	);

# Add a MediaWiki variable to get the page's email address
$wgETWCustomVariables = array('EMAILTOWIKI');
 
$wgHooks['MagicWordMagicWords'][]          = 'wfETWAddCustomVariable';
$wgHooks['MagicWordwgVariableIDs'][]       = 'wfETWAddCustomVariableID';
$wgHooks['LanguageGetMagic'][]             = 'wfETWAddCustomVariableLang';
$wgHooks['ParserGetVariableValueSwitch'][] = 'wfETWGetCustomVariable';
 
function wfETWAddCustomVariable(&$magicWords) {
	foreach($GLOBALS['wgETWCustomVariables'] as $var) $magicWords[] = "MAG_$var";
	return true;
	}
 
function wfETWAddCustomVariableID(&$variables) {
	foreach($GLOBALS['wgETWCustomVariables'] as $var) $variables[] = constant("MAG_$var");
	return true;
	}
 
function wfETWAddCustomVariableLang(&$langMagic, $langCode = 0) {
	foreach($GLOBALS['wgETWCustomVariables'] as $var) {
		$magic = "MAG_$var";
		$langMagic[defined($magic) ? constant($magic) : $magic] = array(0,$var);
		}
	return true;
	}
 
function wfETWGetCustomVariable(&$parser,&$cache,&$index,&$ret) {
	if ($index == MAG_EMAILTOWIKI) {
		global $wgTitle,$wgServer;
		$url  = parse_url($wgServer);
		$host = ereg_replace('^www.','',$url['host']);
		$ret  = $wgTitle->getPrefixedURL();
		$ret  = str_replace(':','&3A',$ret);
		$ret  = eregi_replace('%([0-9a-z]{2})','&$1',$ret);
		$ret  = "$ret@".$url['host'];
		$ret  = "[mailto:$ret $ret]";
		}
	return true;
	}

