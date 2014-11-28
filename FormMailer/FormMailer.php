<?php
/**
 * FormMailer extension - Formats and sends posted form fields to email recipients
 *
 * See http://www.mediawiki.org/wiki/Extension:FormMailer for installation and usage details
 * Started: 2007-06-17
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author [http://www.organicdesign.co.nz/aran Aran Dunkley]
 * @copyright © 2007 Aran Dunkley
 * @licence GNU General Public Licence 2.0 or later
 */
if( !defined( 'MEDIAWIKI' ) ) die( 'Not an entry point.' );
define( 'FORMMAILER_VERSION', '1.0.7, 2014-11-28' );

// A list of email addresses which should recieve posted forms
$wgFormMailerRecipients = array();

// If a variable of this name is posted, the data is assumed to be for mailing
$wgFormMailerVarName = "formmailer";

// Name of sender of forms
$wgFormMailerFrom = 'wiki@' . preg_replace( '|^.+www\.|', '', $wgServer );

// Don't post the following posted items
$wgFormMailerDontSend = array( 'title', 'action' );

// Add a JavaScript test to protect against spambot posts
$wgFormMailerAntiSpam = true;

$wgExtensionFunctions[] = 'wfSetupFormMailer';
$wgExtensionMessagesFiles['FormMailer'] = __DIR__ . "/FormMailer.i18n.php";
$wgExtensionCredits['other'][] = array(
	'name'        => 'FormMailer',
	'author'      => '[http://www.organicdesign.co.nz/aran Aran Dunkley]',
	'description' => 'Formats and sends posted form fields to email recipients',
	'url'         => 'http://www.mediawiki.org/wiki/Extension:FormMailer',
	'version'     => FORMMAILER_VERSION
);

function wfSetupFormMailer() {
	global $wgFormMailerVarName, $wgFormMailerRecipients, $wgFormMailerFrom, $wgFormMailerDontSend, $wgResourceModules,
		$wgRequest, $wgSiteNotice, $wgSitename, $wgFormMailerAntiSpam, $wgOut, $wgJsMimeType;

	$ip = $_SERVER['REMOTE_ADDR'];
	$md5 = md5( $ip );
	$ap = $wgFormMailerAntiSpam ? "$wgFormMailerVarName-$md5" : '';
	$from_email = '';

	if( $wgRequest->getText( $ap ) ) {

		// Construct the message
		$body = '';
		$message = wfMsg( 'formmailer-message' );
		$subject = wfMsg( 'formmailer-subject', $wgSitename );
		foreach( $wgRequest->getValues() as $k => $v ) {
			if( !in_array( $k, $wgFormMailerDontSend ) ) {
				$k = str_replace( '_', ' ', $k );
				if     ( $k == 'formmailer message' ) $message = $v;
				elseif ( $k == 'formmailer subject' ) $subject = $v;
				elseif ( $k != $ap ) $body .= "$k: $v\n\n";
				if( preg_match( "|^email|i", $k ) ) $from_email = $v;
			}
		}

		// Only continue if the email is valid
		if( User::isValidEmailAddr( $from_email ) ) {

			// Send to recipients using the MediaWiki mailer
			$err  = '';
			foreach( $wgFormMailerRecipients as $recipient ) {
				if( User::isValidEmailAddr( $recipient ) ) {
					$from = new MailAddress( $from_email );
					$to = new MailAddress( $recipient );
					$status = UserMailer::send( $to, $from, $subject, wfMsg( 'formmailer-posted', $ip ) . "\n\n$body" );
					if( !is_object( $status ) || !$status->ok ) $err = wfMsg( 'formmailer-failed' );
				}
			}

			// Send a confirmation to the sender
			$from = new MailAddress( "\"$wgSitename\"<$wgFormMailerFrom>" );
			$to = new MailAddress( $from_email );
			$body = wfMsg( 'formmailer-confirmmessage' ) . "\n\n$body";
			$status = UserMailer::send( $to, $from, wfMsg( 'formmailer-confirmsubject', $wgSitename ), $body );
			if( !is_object( $status ) || !$status->ok ) $err = wfMsg( 'formmailer-failed' );

			// Show the thankyou message
			if( $err ) $wgSiteNotice .= "<div class='errorbox'>$err</div><div style='clear:both'></div>";
			else $wgSiteNotice .= "<div class='usermessage'>$message</div>";
		}
		
		// The inquirer's email wasn't valid
		else $wgSiteNotice .= "<div class='errorbox'>" . wfMsg( 'formmailer-invalidemail', $from_email ) . "</div><div style='clear:both'></div>";
	}
	
	// Add the antispam script
	// - adds the MD5 of the IP address to the formmailer input name after page load
	if( $wgFormMailerAntiSpam ) {
		$wgResourceModules['ext.formmailer'] = array(
			'scripts'       => array( 'formmailer.js' ),
			'localBasePath' => __DIR__,
			'remoteExtPath' => basename( __DIR__ ),
		);
		$wgOut->addModules( 'ext.formmailer' );
		$wgOut->addJsConfigVars( 'wgFormMailerAP', $md5 );
	}
}

