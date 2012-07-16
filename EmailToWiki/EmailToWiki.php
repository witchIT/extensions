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
if( !defined( 'MEDIAWIKI' ) ) die( 'Not an entry point.' );
define( 'EMAILTOWIKI_VERSION', '2.2.9, 2012-05-16' );

// Set this if you want the attachments to be passed to a template
$wgAttachmentTemplate = false;

// Default DB table and field for checking existence of From address when filtering
$wgEmailToWikiFilterTable = 'user';
$wgEmailToWikiFilterField = 'user_email';

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
			} else $this->processEmails( $wgRequest->getText( 'prefix', false ) );
		}
		return true;
	}

	/**
	 * Process any unprocesseed email files created by EmailToWiki.pl
	 */
	function processEmails( $prefix = false ) {
		global $wgEmailToWikiTmpDir, $wgWikiEmailsOnly;

		// Allow different tmp directory to be used
		if( $prefix ) $wgEmailToWikiTmpDir = dirname( $wgEmailToWikiTmpDir ) . "/$prefix.tmp";

		$this->logAdd( "EmailToWiki.php (" . EMAILTOWIKI_VERSION . ") started processing " . basename( $wgEmailToWikiTmpDir ) );
		if( !is_dir( $wgEmailToWikiTmpDir ) ) die( $this->logAdd( "Directory \"$wgEmailToWikiTmpDir\" doesn't exist!" ) );

		// Scan messages in folder
		$nemails = 0;
		$nfiles = 0;
		foreach( glob( "$wgEmailToWikiTmpDir/*" ) as $dir ) {
			$msg = basename( $dir );
			$title = Title::newFromText( $msg );
			if( !$title->exists() ) {

				// Get bodytext for thsi message
				$content = file_get_contents( "$dir/_BODYTEXT_" );

				// Apply filtering
				if( $this->filter( $content ) ) {

					// Scan attachments in this msg folder and upload into wiki
					$files = '';
					foreach( glob( "$dir/__*" ) as $file ) {
						$name = substr( basename( $file ), 2 );
						preg_match( "/_(.+)/", $name, $m );
						$attachment = $m[1];
						$comment = wfMsg( 'emailtowiki_uploadcomment', $msg );
						$text = wfMsg( 'emailtowiki_uploadtext', $msg );
						$size = $this->filesize( $file );
						$status = $this->upload( $file, $name, $comment, $text );
						if( $status === true ) {
							global $wgAttachmentTemplate;
							$files .= $wgAttachmentTemplate ? '{{' . "$wgAttachmentTemplate|$name|$attachment|$size}}" : "*[[:$name|$attachment]] ($size)\n";
							$nfiles++;
						}
						else $this->logAdd( $status );
					}

					// Create article for bodytext
					$article = new Article( $title );
					if( $files ) $content .= "\n== " . wfMsg( 'emailtowiki_attachsection' ) . " ==\n$files";
					$article->doEdit( $content, wfMsg( 'emailtowiki_articlecomment' ), EDIT_NEW|EDIT_FORCE_BOT );
					$nemails++;
				} else $this->logAdd( "email \"$msg\" was blocked by the filter." );

			} else $this->logAdd( "email \"$msg\" already exists!" );

			// Remove the processed message folder
			exec( "rm -rf \"$dir\"" );
		}
		$this->logAdd( "Finished ($nemails messages and $nfiles files imported)" );
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
		$title = $upload->getTitle();
		if( is_object( $title ) ) {
			$status = $upload->performUpload( $comment, $text, false, $user );
			$name = $title->getPrefixedText();
		} else return 'File "' . basename( $file ) . '" could not be uploaded, the file-extension is probably not permitted by the wiki';
		return $status->isGood() ? true : $status->getWikiText();
	}

	/**
	 * Return filesize of passed file in human readable format
	 * - Adapted from: http://www.php.net/manual/en/function.filesize.php
	 */
	function filesize( $file ) {
		$size = filesize( $file );
		$units = array( 'Bytes', 'KB', 'MB', 'GB', 'TB', 'PB' );
		for( $i = 0; $size > 1024; $i++ ) $size /= 1024;
		return round( $size, 2 ) . ' ' . $units[$i];
	}         

	/**
	 * Append an error message to the log
	 */
	function logAdd( $err ) {
		global $wgEmailToWikiErrLog;
		$fh = fopen( $wgEmailToWikiErrLog, 'a' );
		$time = date('d M Y, H:i:s');
		fwrite( $fh, "PHP [$time]: $err\n" );
		fclose( $fh );
		return $err;
	}

	/**
	 * Apply filtering rules to the email and return whether allowed or not
	 * - if the filter option is set for this message, check the given DB table and field for existence of the From or Forward addresses
	 */
	function filter( $message ) {
		global $wgEmailToWikiFilterTable, $wgEmailToWikiFilterField;
		if( preg_match( "/^\s*\|\s*filter\s*=/m", $message ) ) {
			$dbr = &wfGetDB( DB_SLAVE );
			$tbl = $dbr->tableName( $wgEmailToWikiFilterTable );
			if( preg_match( "/^\s*\|\s*forward\s*=\s*(.+?)\s*$/m", $message, $m ) ) {
			foreach( explode( ',', $m[1] ) as $email ) {
				if( preg_match( "/<(.+?)>$/", $email, $m ) ) $email = $m[1];
				if( $dbr->selectRow( $tbl, '1', "$wgEmailToWikiFilterField REGEXP ':?$email$'" ) ) return true;
			}
			return false;
		}
		return true;
	}
}

/**
 * Called from $wgExtensionFunctions array when initialising extensions
 */
function wfSetupEmailToWiki() {
	global $wgEmailToWiki;
	$wgEmailToWiki = new EmailToWiki();
}
