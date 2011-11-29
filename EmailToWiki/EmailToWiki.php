<?php
/* EmailToWiki extension - Allows emails to be sent to the wiki and added to an existing or new article
 * Started: 2007-05-25, version 2 started 2011-11-13
 * Contact: neill@prescientsoftware.co.uk
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @copyright © 2007 - 2011 Aran Dunkley
 * @licence GNU General Public Licence 2.0 or later
 */
if ( !defined( 'MEDIAWIKI' ) ) die( 'Not an entry point.' );
define( 'EMAILTOWIKI_VERSION', '2.0.5, 2011-11-29' );

$dir = dirname( __FILE__ );
$wgExtensionMessagesFiles['EmailToWiki'] = "$dir/EmailToWiki.i18n.php";
$wgEmailToWikiTmpDir = "$dir/EmailToWiki.tmp";
$wgEmailToWikiErrLog = "$dir/EmailToWiki.log";

$wgExtensionFunctions[] = 'wfSetupEmailToWiki';
$wgExtensionCredits['other'][] = array(
	'name'        => 'EmailToWiki',
	'author'      => '[http://www.organicdesign.co.nz/nad User:Nad]',
	'description' => 'Allows emails to be sent to the wiki and added to an existing or new article',
	'url'         => 'http://www.mediawiki.org/wiki/Extension:EmailToWiki',
	'version'     => EMAILTOWIKI_VERSION
);

class EmailToWiki {

	function __construct() {
		global $wgHooks, $wgGroupPermissions;
		$wgHooks['UnknownAction'][] = $this;

		// Allow the emailtowiki action to bypass security - it will be blocked later if non-local
		if( array_key_exists( 'action', $_GET ) && $_GET['action'] == 'emailtowiki' ) $wgGroupPermissions['*']['read'] = true;
	}

	/**
	 * Add a new email to the wiki
	 * - only works for local requests
	 */
	function onUnknownAction( $action, $article ) {
		global $wgOut, $wgRequest;
		if( $action == 'emailtowiki' ) {
			$wgOut->disable();
			if( preg_match_all( "|inet6? addr:\s*([0-9a-f.:]+)|", `/sbin/ifconfig`, $matches ) && !in_array( $_SERVER['REMOTE_ADDR'], $matches[1] ) ) {
				header( 'Bad Request', true, 400 );
				print $this->logAdd( "Emails can only be added by the EmailToWiki.pl script running on the local host!" );
			} else $this->processEmails();
		}
	}

	/**
	 * Process any unprocesseed email files created by EmailToWiki.pl
	 */
	function processEmails() {
		global $wgEmailToWikiTmpDir;
		$this->logAdd( "EmailToWiki.php (" . EMAILTOWIKI_VERSION . ") started" );
		if( !is_dir( $wgEmailToWikiTmpDir ) ) die( $this->logAdd( "Directory \"$wgEmailToWikiTmpDir\" doesn't exist!" ) );

		// Scan messages in folder
		foreach( glob( "$wgEmailToWikiTmpDir/*" ) as $dir ) {
			$msg = basename( $dir );
			$title = Title::newFromText( $msg );
			if( !$title->exists() ) {

				// Scan attachments in this msg folder and upload into wiki
				$files = '';
				foreach( glob( "$dir/__*" ) as $file ) {
					$name = substr( basename( $file ), 2 );
					preg_match( "/_(.+)/", $name, $m );
					$attachment = $m[1];
					$comment = wfMsg( 'emailtowiki_uploadcomment', $msg );
					$text = wfMsg( 'emailtowiki_uploadtext', $msg );
					$status = $this->upload( $file, $name, $comment, $text );
					if( $status === true ) $files .= "*[[:$name|$attachment]]\n";
					else $this->logAdd( $status );
				}

				// Create article for bodytext
				$article = new Article( $title );
				$content = file_get_contents( "$dir/_BODYTEXT_" );
				if( $files ) $content .= "\n== " . wfMsg( 'emailtowiki_attachsection' ) . " ==\n$files";
				$article->doEdit( $content, wfMsg( 'emailtowiki_articlecomment' ), EDIT_NEW|EDIT_FORCE_BOT );
			} else $this->logAdd( "email \"$msg\" already exists!" );
				
			// Remove the processed message folder
			exec( "rm -rf \"$dir\"" );
		}
		print "Finished.";
	}

	/**
	 * Upload passed filename into the wiki as if it were posted by the normal upload form
	 * - $name is updated since the upload method may modify the filename used
	 */
	function upload( $file, &$name, $comment, $text ) {
		$user = User::newFromName( 'EmailToWiki' );
		$_GET['wpDestFile'] = $name;
		$_GET['wpDestFileWarningAck'] = 1;
		$_FILES['wpUploadFile'] = array( 'name' => basename( $file ), 'tmp_name' => $file, 'size' => filesize( $file ) );
		$request = new WebRequest();
		$upload = UploadBase::createFromRequest( $request, 'File' );
		$upload->verifyUpload();
		$status = $upload->performUpload( $comment, $text, false, $user );
		$name = $upload->getTitle()->getPrefixedText();
		return $status->isGood() ? true : $status->getWikiText();
	}

	/**
	 * Append an error message to the log
	 */
	function logAdd( $err ) {
		global $wgEmailToWikiErrLog;
		$fh = fopen( $wgEmailToWikiErrLog, 'a' );
		fwrite( $fh, "PHP: $err\n" );
		fclose( $fh );
		return $err;
	}
}

/**
 * Called from $wgExtensionFunctions array when initialising extensions
 */
function wfSetupEmailToWiki() {
	global $wgEmailToWiki;
	$wgEmailToWiki = new EmailToWiki();
}
