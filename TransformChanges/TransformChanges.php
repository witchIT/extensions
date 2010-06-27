<?php
if ( !defined( 'MEDIAWIKI' ) ) die( 'Not an entry point.' );
/**
 * TransformChanges extension - Makes recent-changes and watchlists render in nice columns for easier reading
 *
 * See http://www.mediawiki.org/wiki/Extension:TransformChanges for installation and usage details
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author Aran Dunkley [http://www.organicdesign.co.nz/nad User:Nad]
 * @copyright © 2007-2010 Aran Dunkley
 * @licence GNU General Public Licence 2.0 or later
 */

define( 'TRANSFORMCHANGES_VERSION', '2.0.1, 2010-06-27' );

$wgExtensionCredits['other'][] = array(
	'name'        => "TransformChanges",
	'author'      => "[http://www.organicdesign.co.nz/nad User:Nad], [http://www.mediawiki.org/wikiUser:Fish1203 User:Fish1203]",
	'description' => "Makes recent-changes and watchlists render in nice columns for easier reading.",
	'url'         => "http://www.organicdesign.co.nz/Extension:TransformChanges",
	'version'     => TRANSFORMCHANGES_VERSION
);

# Disable enhanced recent changes
$wgExtensionFunctions[] = 'wfSetupTransformChanges';
function wfSetupTransformChanges() {
	global $wgUser;
	$wgUser->setOption( 'usenewrc', false );
}

$wgHooks['SkinAfterContent'][] = 'wfTransformChanges';
function wfTransformChanges() {
	global $wgOut;

	# Bail if not recentchanges or watchlist
	if ( strpos( $wgOut->getHTMLTitle(), wfMsg('recentchanges') ) !== 0
		&& strpos( $wgOut->getHTMLTitle(), wfMsg('watchlist') ) !== 0 ) return true;

	$text =& $wgOut->mBodytext;
	$text = preg_replace( '|<li[^>]+>|s', '<li>', $text );
	$text = preg_replace( '|(</ul>\\s*)?<h4>(.+?)</h4>\\s*(<ul class="special">)<li>|s', '$3<li $2>', $text );

	# Edits by Fish1203
	# (http://www.mediawiki.org/wiki/User:Fish1203)
	# solving the PHP preg_replace_callback() pcre.backtrack_limit problem

	# splitting in days
	$parts = explode( "<ul class=\"special\">", $text );

	$first = 1;
	$nbedits = 50;
	$text = $parts[0] . "<table class=\"changes\">";
	foreach ( $parts as $part ) {
		# skipping first part (= Special:RecentChanges "header")
		if ( $first == 1 ) $first = 0;
		else {

			$nbsubparts = ( substr_count( $part, "</li>" ) - 1 ); # how many edits for this day ?

			# if more than $nbedits edits for this day
			if ( $nbsubparts > $nbedits ) {
				$divide = intval( abs( $nbsubparts / $nbedits ) );

				# splits each day in parts containing $nbedits edits (max.)
				$start = 0;
				$parts2 = array();
				for ( $lp=0; $lp < $divide; $lp++ ) {
					$parts2[$lp] = substr( $part, $start, ( strposn( $part, "</li>", ( $lp + 1 ) * $nbedits ) ) - $start );
					$start = ( strposn( $part, "</li>", ( $lp + 1 ) * $nbedits ) );
				}
				
				# last part for the day
				$parts2[$divide] = substr( $part, $start, strlen( $part ) );
 
				foreach ( $parts2 as $part2 ) {
					$part2 = "<ul class=\"special\">$part2</ul>";
					$text .= preg_replace_callback( "|<ul class=\"special\">(.+?)</ul>|s", 'wfTransformChangesUL', $part2 );
				}
			}
			else {
				$part = "<ul class=\"special\">$part</ul>";
				$text .= preg_replace_callback( "|<ul class=\"special\">(.+?)</ul>|s", 'wfTransformChangesUL', $part );
			}
		}
	}

	# there may be one last </ul> tag remaining... just remove it... :-)
	$text = str_replace( "</ul>", "", $text );
	$text .= "</table>";
	return true;
}

function wfTransformChangesUL( $match ) {
	global $wgTransformChangesRow;
	$wgTransformChangesRow = 'odd';
	$rows = preg_replace_callback( '|<li\\s*(.*?)>(.+?)</li>|s', 'wfTransformChangesLI', $match[1] );
	return $rows;
}

function wfTransformChangesLI( $match ) {
	global $wgTransformChangesRow, $wgSimpleSecurity;
	$wgTransformChangesRow = $wgTransformChangesRow == 'even' ? 'odd' : 'even';
	$talk = '';
	list( , $date, $text ) = $match;
	#$cols = array('time', 'title', 'user', 'talk', 'info', 'comment', 'diff');

	# OrganicDesign has different columns and order
	$cols = array( 'time', 'diff', 'title', 'comment', 'user', 'info' );

	$ncols = count( $cols );
	$row = '';
	$error = '<td colspan="$ncols"><font color="red"><b>Error: match failed!</b></font></td>';
	if ( $date ) {
		$row = "<tr><td class=\"heading\" colspan=\"$ncols\">$date</td></tr>\n"; 
		$wgTransformChangesRow = 'even';

		# OrganicDesign's table header
		static $head = "<tr><th>Time</th><th>Actions</th><th>Item changed</th><th>Description of change</th><th>Changed by</th><th>Details</th>";
		$row .= $head;
		$head = '';
	}
	$row .= "<tr class=\"mw-line-$wgTransformChangesRow\">";

	if (preg_match( '%^(.*?);(&#32;|\\s+)(\\d+:\\d+)(.+?)(<a.+?</a>\\))(</span>)?\\s*(.*?)$%', $text, $m ) ) {
		list( , $diff,, $time, $bytes, $user,, $comment ) = $m;

		if ( preg_match( '|^(.+\\)).*?\\. \\.\\s*(.*?)\\s*(<a.+)$|', $diff, $m ) ) list( , $diff, $info, $title ) = $m; else $info = $title = '';
		if ( preg_match( '|(\\(.+?\\))|', $bytes, $m ) ) $info .= "<small>$m[1]</small>";

		# Remove talk for email or IP users and make user lowercase
		if ( preg_match('|(<a.+?</a>).+?(\\(.+?</a>\\))|', $user, $m ) ) {
			list( , $user, $talk ) = $m;
			if ( ereg( '@', $user ) || !eregi( '[a-z]',$user ) ) {
				$talk = '';
				$user = strtolower( $user );
			}
		}

		# Remove brackets from comment
		if (preg_match('|^<.+?>\\((.+)\\)<|', $comment, $m)) $comment = $m[1];

		# Only show row if ok by SimpleSecurity extension
		$allowed = true;
		if ( preg_match('|title="(.+?)"|', $title, $m ) && is_object( $wgSimpleSecurity ) ) {
			global $wgUser;
			$t = Title::newFromText( $m[1] );
			$allowed = version_compare( SIMPLESECURITY_VERSION, '4.0.0' ) < 0
				? $wgSimpleSecurity->validateTitle( 'view', $t )
				: $wgSimpleSecurity->userCanReadTitle( $wgUser, $t, $error );
		}

		# OrganicDesign has an edit link instead of talk and hist
		$talk = preg_replace( '| \\|.+</a>|', '', $talk );
		$diff = preg_replace( '|curid=[0-9]+&(amp;)?action=history|', 'action=edit', $diff );
		$diff = preg_replace( '|>hist<|', '>edit<', $diff );
		$user .= "&nbsp;<small>$talk</small>";

		if ( $allowed ) foreach ( $cols as $col ) {
			$val = isset($$col) ? $$col : '';
			$row .= "<td class=\"$col\">$val</td>";
		}
	} else $row = $error;
	$row .= "</tr>\n";
	return $row;
}

# Returns the position of the $n-th $c token (string) in $haystack (= position at the end of the token)
function strposn( $haystack, $c, $n ) {
	$a = explode( $c, $haystack, $n + 1 );
	if ( $n <= 0 || count( $a ) <= $n ) return false;
	return strlen( $haystack ) - strlen( $a[$n] );
}
