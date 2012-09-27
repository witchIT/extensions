<?php
/**
 * FormMailer extension - Formats and sends posted form fields to email recipients
 *
 * See http://www.mediawiki.org/wiki/Extension:FormMailer for installation and usage details
 * Started: 2007-06-17
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author [http://www.organicdesign.co.nz/User:Nad User:Nad]
 * @copyright © 2007 [http://www.organicdesign.co.nz/User:Nad User:Nad]
 * @licence GNU General Public Licence 2.0 or later
 */
if( !defined( 'MEDIAWIKI' ) ) die( 'Not an entry point.' );
define( 'FORMMAILER_VERSION', '1.0.4, 2012-09-27' );

# A list of email addresses which should recieve posted forms
$wgFormMailerRecipients = array();

# If a variable of this name is posted, the data is assumed to be for mailing
$wgFormMailerVarName    = "formmailer";

# Name of sender of forms
$wgFormMailerFrom       = 'wiki@' . preg_replace( '|^.+www\.|', '', $wgServer );

# Don't post the following posted items
$wgFormMailerDontSend   = array( 'title', 'action' );

# Message to display after sending the form (can also be set in the form by posting formmailer_message
$wgFormMailerMessage    = "Thanks, your enquiry has been submitted!";

# Message to display after sending the form (can also be set in the form by posting formmailer_subject
$wgFormMailerSubject    = "Form submitted from $wgSitename";

# Add a JavaScript test to protect against spambot posts
$wgFormMailerAntiSpam   = true;

$wgExtensionFunctions[] = 'wfSetupFormMailer';

$wgExtensionCredits['other'][] = array(
	'name'        => 'FormMailer',
	'author'      => '[http://www.organicdesign.co.nz/nad User:Nad]',
	'description' => 'Formats and sends posted form fields to email recipients',
	'url'         => 'http://www.mediawiki.org/wiki/Extension:FormMailer',
	'version'     => FORMMAILER_VERSION
);

function wfSetupFormMailer() {
	global $wgFormMailerVarName, $wgFormMailerRecipients, $wgFormMailerMessage, $wgFormMailerSubject,
		$wgFormMailerFrom, $wgFormMailerDontSend,
		$wgRequest, $wgSiteNotice, $wgSitename, $wgFormMailerAntiSpam, $wgOut, $wgJsMimeType;

	$ip = $_SERVER['REMOTE_ADDR'];
	$ap = $wgFormMailerAntiSpam ? '-' . md5( $ip ) : '';

	if( $wgRequest->getText( $wgFormMailerVarName . $ap ) ) {

		// Construct the message
		$body    = "Form posted from $ip\n\n";
		$message = $wgFormMailerMessage;
		$subject = $wgFormMailerSubject;
		foreach( $wgRequest->getValues() as $k => $v ) {
			if( !in_array( $k, $wgFormMailerDontSend ) ) {
				$k = str_replace( '_', ' ', $k );
				if     ( $k == 'formmailer message' ) $message = $v;
				elseif ( $k == 'formmailer subject' ) $subject = $v;
				elseif ( $k != $wgFormMailerVarName ) $body .= "$k: $v\n\n";
			}
		}

		// Send to recipients using the MediaWiki mailer
		$err  = '';
		$user = new User();
		$from = "\"$wgSitename\"<$wgFormMailerFrom>";
		foreach( $wgFormMailerRecipients as $recipient ) {
			if( User::isValidEmailAddr( $recipient ) ) {
				$user->setName( $recipient );
				$user->setEmail( $recipient );
				if( $user->sendMail( $subject, $body, $from ) !== true ) $err = 'Failed to send!';
			}
		}
		$wgSiteNotice .= "<div class='usermessage'>" . ( $err ? $err : $message ) . "</div>";
	}
	
	// Add the antispam script
	// - adds the MD5 of the IP address to the formmailer input name after page load
	if( $wgFormMailerAntiSpam ) {
		$wgOut->addScript( "<script type='$wgJsMimeType'>
		$(document).ready(function formMailerOnLoad() {
			e = document.getElementsByTagName( 'input' );
			for( i = 0; i < e.length; i++ ) {
				if( e[i].name == 'formmailer' ) e[i].name += '$ap';
			}
		});
		</script>" );
	}

}

