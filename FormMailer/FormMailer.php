<?php
# Extension:FORMMAILER{{php}}{{Category:Extensions|FormMailer}}
# - Licenced under LGPL (http://www.gnu.org/copyleft/lesser.html)
# - Author:  [http://www.organicdesign.co.nz/nad User:Nad]
# - Started: 2007-06-17
# - See http://www.mediawiki.org/wiki/Extension:FormMailerfor installation and usage details

if (!defined('MEDIAWIKI')) die('Not an entry point.');

define('FORMMAILER_VERSION','1.0.2, 2008-10-29');

# A list of email addresses which should recieve posted forms
$wgFormMailerRecipients = array();

# If a variable of this name is posted, the data is assumed to be for mailing
$wgFormMailerVarName    = "formmailer";

# Name of sender of forms
$wgFormMailerFrom       = 'wiki@'.ereg_replace('^.+www.', '', $wgServer);

# Don't post the following posted items
$wgFormMailerDontSend   = array('title', 'action');

# Message to display after sending the form (can also be set in the form by posting formmailer_message
$wgFormMailerMessage    = "Thanks, your enquiry has been submitted!";

# Message to display after sending the form (can also be set in the form by posting formmailer_subject
$wgFormMailerSubject    = "Form submitted from $wgSitename";


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
		$wgFormMailerFrom, $wgFormMailerDontSend, $wgSimpleFormsRequestPrefix,
		$wgRequest, $wgSiteNotice, $wgSitename;

	if ($wgRequest->getText($wgSimpleFormsRequestPrefix.$wgFormMailerVarName)) {

		# Construct the message
		$body    = "Form posted from ".$_SERVER['REMOTE_ADDR']."\n\n";
		$message = $wgFormMailerMessage;
		$subject = $wgFormMailerSubject;
		foreach ($wgRequest->getValues() as $k => $v) if (!in_array($k, $wgFormMailerDontSend)) {
			if ($wgSimpleFormsRequestPrefix) $k = ereg_replace("^$wgSimpleFormsRequestPrefix", '', $k);
			$k = str_replace('_', ' ', $k);
			if     ($k == 'formmailer_message') $message = $v;
			elseif ($k == 'formmailer_subject') $subject = $v;
			elseif ($k != $wgFormMailerVarName) $body .= "$k: $v\n\n";
		}

		# Send to recipients using the MediaWiki mailer
		$err  = '';
		$user = new User();
		$from = "\"$wgSitename\"<$wgFormMailerFrom>";
		foreach ($wgFormMailerRecipients as $recipient) if (User::isValidEmailAddr($recipient)) {
			$user->setName($recipient);
			$user->setEmail($recipient);
			if ($user->sendMail($subject, $body, $from) !== true) $err = 'Failed to send!';
		}
		$wgSiteNotice .= "<div class='usermessage'>".($err ? $err : $message)."</div>";
	}
}

