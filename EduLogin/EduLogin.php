<?php
/**
 * EduLogin extension - restricts account creation to users with .edu email addresses
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author Aran Dunkley [http://www.organicdesign.co.nz/nad User:Nad]
 * @copyright © 2011 Aran Dunkley
 * @licence GNU General Public Licence 2.0 or later
 */
if( !defined( 'MEDIAWIKI' ) ) die( "Not an entry point." );
define( 'EDULOGIN_VERSION', "1.0.10, 2012-01-09" );
define( 'EDU_EMAIL_NOT_FOUND', 'internal message - emailnotfound' );

$wgEduEmailPattern = "|\.edu$|";
$wgEduRedirectPages = array( '', 'Main_Page' );
$wgEnableEmail = true;

$dir = dirname( __FILE__ );
$wgExtensionMessagesFiles['EduLogin'] = "$dir/EduLogin.i18n.php";
require_once( "$IP/includes/SpecialPage.php" );

// Only do anything if this isn't a resource loader request
if( !preg_match( "|load\.php|", $_SERVER['REQUEST_URI'] ) ) {
	$wgExtensionFunctions[] = 'wfSetupEduLogin';
	$wgHooks['LanguageGetMagic'][] = 'wfEduLoginLanguageGetMagic';
	$wgSpecialPages['EduLogin'] = 'EduLogin';
}

$wgExtensionCredits['specialpage'][] = array(
	'name'        => "EduLogin",
	'author'      => "[http://www.organicdesign.co.nz/nad Aran Dunkley]",
	'description' => "Restricts account creation to users with .edu email addresses (for Elance job 27354439)",
	'url'         => "https://www.elance.com/php/collab/main/collab.php?bidid=27354439",
	'version'     => EDULOGIN_VERSION
);

/**
 * Create a class for the new .edu login/create account forms based on the standard MediaWiki ones
 */
class EduLoginForm extends LoginForm {

	// This is used to switch some login/create error messages to the edu ones
	var $eduError = false;

	/**
	 * Adjust some items in the initial data
	 */
	function __construct( &$request, $par = '' ) {
		parent::__construct( $request, $par );

		// If this is a login attempt, try and find the username from the email address
		if( $this->mLoginattempt || $this->mMailmypassword ) {
			$this->mName = self::getUserFromEmail( $this->mEmail );
		}

		// Otherwise if it's an account creation set up the environment ready for processing
		elseif( $this->mCreateaccountMail ) {
			global $wgHooks;
			$wgHooks['AbortNewAccount'][] = $this;
			$this->mToken = $request->getVal( 'wpCreateaccountToken' );

			// If the name for this email address doesn't already exist, create mName from first and last name fields
			$name = self::getUserFromEmail( $this->mEmail );
			if( $name == EDU_EMAIL_NOT_FOUND ) {
				$this->mName = ucwords( trim( $request->getVal( 'wpFirstName' ) ) . ' ' . trim( $request->getVal( 'wpLastName' ) ) );
			}

			// Otherwise if the email exists, force the username to conflict with the existing one
			else {
				$this->mName = $name;
				$this->eduError = 'edu-already-registered';
			}
		}

		// The user name and real name are the same for .edu users
		$this->mRealName = $this->mName;
	}

	/**
	 * Allow the account creation to be aborted if the email isn't a .edu or the name is bad
	 */
	function onAbortNewAccount( $user, &$abort ) {
		global $wgEduEmailPattern;

		// Check email is a .edu
		if( !preg_match( $wgEduEmailPattern, $this->mEmail ) ) {
			$abort = wfMsg( 'edu-bademail' );
			return false;
		}

		// Check that both first and last names have been supplied
		if( !preg_match( "| |", trim( $this->mName ) ) ) {
			$abort = wfMsg( 'edu-badname' );
			return false;
		}

		return true;
	}

	/**
	 * Return a username given an email address
	 */
	static function getUserFromEmail( $email ) {
		$name = EDU_EMAIL_NOT_FOUND;
		if( $email ) {
			$dbr = &wfGetDB( DB_SLAVE );
			$tbl = $dbr->tableName( 'user' );
			$email = $dbr->addQuotes( $email );
			if( $row = $dbr->selectRow( $tbl, 'user_name', "user_email = $email" ) ) $name = $row->user_name;
		}
		return $name;
	}

	/**
	 * Render the login and accoutn creation forms
	 */
	function mainLoginForm( $msg, $msgtype = 'error' ) {
		global $wgOut;

		// Adjust message if necessary
		if( $msg ) {
			if( $this->eduError ) $msg = wfMsg( $this->eduError, $this->mEmail );
			elseif( preg_match( '|' . EDU_EMAIL_NOT_FOUND . '|i', $msg ) ) {
				if( $this->mEmail ) {
					$url = Title::newFromText( 'EduLogin/UserLogin', NS_SPECIAL )->getLocalUrl();
					$msg = wfMsg( 'edu-emailnotfound', $this->mEmail, $url );
				} else $msg = wfMsg( 'edu-noemail' );
			}
		}

		if( $this->mCreateaccountMail )  $wgOut->addHtml( self::renderLoginAndCreate( $msg ) );
		elseif( $this->mLoginattempt )   $wgOut->addHtml( self::renderLoginAndCreate( $msg ) );
		elseif( $this->mMailmypassword ) $wgOut->addHtml( self::renderForgottenPassword( $msg ) );
	}

	/**
	 * Return the HTML for both login and account creation forms
	 */
	static function renderLoginAndCreate( $msg = '' ) {
		$login = self::renderUserLogin();
		$create = self::renderCreateAccount();
		$welcome = "<div id=\"edu-welcome\">" . cwfMsg( 'edu-welcome' ) . "</div>";
		if( $msg ) $msg = "<div class=\"errorbox\"><strong>Login error</strong><br />$msg</div>";
		return "<table class=\"edulogin\"><tr><td colspan=\"2\">$welcome<br />$msg</td></tr><tr><td valign=\"top\">$login</td><td>$create</td></tr></table>";
	}

	/**
	 * Return the HTML for the account creation form
	 */
	static function renderCreateAccount() {
		if( !self::getCreateaccountToken() ) self::setCreateaccountToken();
		$url = Title::newFromText( 'EduLogin', NS_SPECIAL )->getLocalUrl();
		$html = "<div id=\"userlogin\"><form class=\"edulogin\" id=\"edu-createaccount\" action=\"$url\" method=\"POST\">\n";
		$html .= "<h2>" . wfMsg( 'edu-dont-have-account' ) . "</h2>\n";
		$html .= "<table class=\"edulogin\">\n";
		$html .= "<tr><td class=\"edu-label\"><label for=\"wpName\">" . wfMsg( 'edu-name' ) . ":</label></td></tr>\n";
		$html .= "<tr><td class=\"edu-input\"><input name=\"wpFirstName\" size=\"20\" /> " . wfMsg( 'edu-firstname' ) . "</td></tr>\n";
		$html .= "<tr><td class=\"edu-input\"><input name=\"wpLastName\" size=\"20\" /> " . wfMsg( 'edu-lastname' ) . "</td></tr>\n";
		$html .= "<tr><td class=\"edu-label\"><label for=\"wpEmail\">" . wfMsg( 'edu-email' ) . ":</label></td></tr>\n";
		$html .= "<tr><td class=\"edu-input\"><input name=\"wpEmail\" size=\"20\" /></td></tr>\n";
		$html .= "<tr><td class=\"edu-label\">". wfMsg( 'edu-must-be-edu' ) . "</td></tr>\n";
		$html .= "<tr><td class=\"edu-submit\"><input type=\"submit\" value=\"Create account\" name=\"wpCreateaccountMail\"></td></tr>\n";
		$html .= "<tr><td class=\"edu-label\">". wfMsg( 'edu-send-temp-email' ) . "</td></tr>\n";
		$html .= "<tr><td><input type=\"hidden\" value=\"" . self::getCreateaccountToken() . "\" name=\"wpCreateaccountToken\" /></td></tr>";
		$html .= "</table></form></div>\n";
		return $html;
	}

	/**
	 * Return the HTML for the user login form
	 */
	static function renderUserLogin() {
		if( !self::getLoginToken() ) self::setLoginToken();
		$url = Title::newFromText( 'EduLogin', NS_SPECIAL )->getLocalUrl();
		$html = "<div id=\"userlogin\"><form class=\"edulogin\" id=\"edu-userlogin\" action=\"$url\" method=\"POST\">\n";
		$html .= "<h2>" . wfMsg( 'edu-have-account' ) . "</h2>\n";
		$html .= "<table class=\"edulogin\">\n";
		$html .= "<tr><td class=\"edu-label\"><label for=\"wpEmail\">" . wfMsg( 'edu-email' ) . ":</label></td></tr>\n";
		$html .= "<tr><td class=\"edu-input\"><input name=\"wpEmail\" size=\"20\" /></td></tr>\n";
		$html .= "<tr><td class=\"edu-label\"><label for=\"wpPassword\">" . wfMsg( 'edu-password' ) . ":</label></td></tr>\n";
		$html .= "<tr><td class=\"edu-input\"><input name=\"wpPassword\" type=\"password\" size=\"20\" /></td></tr>\n";
		$html .= "<tr><td>&nbsp;</td></tr>\n";
		$html .= "<tr><td class=\"edu-submit\"><input type=\"submit\" value=\"Login\" name=\"wpLoginattempt\"></td></tr>\n";
		$forgotLink = "<a href=\"$url\">". wfMsg( 'edu-forgot-password' ) . "</a>";
		$html .= "<tr><td class=\"edu-label\">$forgotLink</td></tr>\n";
		$html .= "<tr><td><input type=\"hidden\" value=\"" . self::getLoginToken() . "\" name=\"wpLoginToken\" /></td></tr>";
		$html .= "</table></form></div>\n";
		return $html;
	}

	/**
	 * Return the HTML for the forgot password form
	 */
	static function renderForgottenPassword( $msg = '' ) {
		if( !self::getLoginToken() ) self::setLoginToken();
		$url = Title::newFromText( 'EduLogin/UserLogin', NS_SPECIAL )->getLocalUrl();
		$html = "<table class=\"edulogin\">\n";
		if( $msg ) $html .= "<tr><td><div class=\"errorbox\"><strong>Login error</strong><br />$msg</div></td></tr>\n";
		$html .= "<tr><td><div id=\"userlogin\"><form class=\"edulogin\" id=\"edu-forgotpassword\" action=\"$url\" method=\"POST\">\n";
		$html .= "<h2>" . wfMsg( 'edu-forgot-password' ) . "</h2>\n";
		$html .= "<table class=\"edulogin\">\n";
		$html .= "<tr><td class=\"edu-label\">". wfMsg( 'edu-send-new-email' ) . "</td></tr>\n";
		$html .= "<tr><td class=\"edu-label\">&nbsp;</td></tr>\n";
		$html .= "<tr><td class=\"edu-label\"><label for=\"wpEmail\">" . wfMsg( 'edu-email' ) . ":</label></td></tr>\n";
		$html .= "<tr><td class=\"edu-input\"><input name=\"wpEmail\" size=\"20\" /></td></tr>\n";
		$html .= "<tr><td class=\"edu-submit\"><input type=\"submit\" value=\"Send Password\" name=\"wpMailmypassword\"></td></tr>\n";
		$html .= "<tr><td><input type=\"hidden\" value=\"" . self::getLoginToken() . "\" name=\"wpLoginToken\" /></td></tr>";
		$html .= "</table></form></div></td></tr></table>\n";
		return $html;
	}
}

/**
 * Create a class for the .edu login special page and parser-function
 */
class EduLogin extends SpecialPage {

	function __construct() {
		global $wgHooks, $wgParser, $wgUser, $wgRequest;

		// If this is the old login page, or the user is not logged in and it's in the redirect list, redirect to the new login page
		$title = $wgRequest->getText( 'title' );
		if( $title == 'Special:UserLogin' || ( $wgUser->isAnon() && !preg_match( "|Special:EduLogin|i", $title ) ) ) {
			$this->redirectToLogin();
		}

		// Initialise the special page
		SpecialPage::SpecialPage( 'EduLogin', false, true, false, false, false );

		// Create a parser-function to render login & account creations forms
		$wgParser->setFunctionHook( 'EDULOGIN', array( $this, 'expandParserFunction' ), SFH_NO_HASH );

		// Redirect people back to login after logout
		$wgHooks['UserLogoutComplete'][] = $this;

	}

	/**
	 * Render the special page
	 */
	function execute( $param ) {
		global $wgOut, $wgRequest, $wgEduRequest;
		$this->setHeaders();
		if( session_id() == '' ) wfSetupSession();
		$form = new EduLoginForm( $wgRequest );

		// If this is a create account form submission process it
		if( $form->mCreateaccountMail ) $form->execute();

		// If this is a login form submission process it
		// - a hack is required here to ensure that mName is set in the request object
		//   (this is because the request object was created before the DB was initiated,
		//    but the name can only be matched with the email by accessing the DB)
		elseif( $form->mLoginattempt || $form->mMailmypassword ) {
			$wgEduRequest = $wgRequest;
			$wgRequest = new EduRequest( $form->mName );
			$form->execute();
			$wgRequest = $wgEduRequest;
		}

		// If the UserLogin param is supplied, render login and account creation forms
		elseif( $param == 'UserLogin' ) $wgOut->addHtml( EduLoginForm::renderLoginAndCreate() );

		// By default, render the forgotten password form
		else $wgOut->addHtml( EduLoginForm::renderForgottenPassword() );
	}

	/**
	 * Redirect people back to login after logout
	 */
	function onUserLogoutComplete( &$user, &$inject_html, $old_name ) {
		$this->redirectToLogin();
	}

	/**
	 * Don't output anything, just redirect to login page
	 */
	function redirectToLogin() {
		global $wgOut;
		$wgOut->disable();
		wfResetOutputBuffers();
		$url = Title::newFromText( 'EduLogin/UserLogin', NS_SPECIAL )->getFullUrl();
		header( "Location: $url" );
	}

	/**
	 * Expand the EDULOGIN parser function
	 */
	function expandParserFunction( &$parser ) {
		global $wgUser;
		$parser->disableCache();
		if( $wgUser->isLoggedIn() ) return '';
		return array( EduLoginForm::renderLoginAndCreate(), 'isHTML' => true, 'noparse' => true);
	}
}

/**
 * A dummy requets object that returns a wpName when only a wpEmail was submitted
 * - $wgRequest is replaced with this dummy object temporarily within processUserLogin()
 * - the dummy object refers all requests to the original object except for a request for wpName
 * - requests for wpName have the new mName returned which has been matched to the posted wpEmail
 */
class EduRequest {

	var $name = false;

	function __construct( $name ) {
		$this->name = $name;
	}

	function getVal( $val, $default = false ) {
		global $wgEduRequest;
		if( $val == 'wpName' ) return $this->name;
		return $wgEduRequest->getVal( $val, $default );
	}

	function getBool( $val, $default = false ) {
		global $wgEduRequest;
		return $wgEduRequest->getBool( $val, $default );
	}

	function getCookie( $val, $default = false ) {
		global $wgEduRequest;
		return $wgEduRequest->getCookie( $val, $default );
	}

	function getCheck( $val, $default = false ) {
		global $wgEduRequest;
		return $wgEduRequest->getCheck( $val, $default );
	}

	function wasPosted() {
		global $wgEduRequest;
		return $wgEduRequest->wasPosted();
	}

	function setSessionData( $name, $val ) {
		global $wgEduRequest;
		return $wgEduRequest->setSessionData( $name, $val );
	}

	function getSessionData( $name ) {
		global $wgEduRequest;
		return $wgEduRequest->getSessionData( $name );
	}

	function checkSessionCookie() {
		global $wgEduRequest;
		return $wgEduRequest->checkSessionCookie();
	}

	function response() {
		global $wgEduRequest;
		return $wgEduRequest->response();
	}
}

/**
 * Called from $wgExtensionFunctions array when initialising extensions
 */
function wfSetupEduLogin() {
	global $wgEduLogin;
	$wgEduLogin = new EduLogin();
	SpecialPage::addPage( $wgEduLogin );
}

/**
 * Set up magic word for parser-function
 */
function wfEduLoginLanguageGetMagic( &$langMagic, $langCode = 0 ) {
	$langMagic['EDULOGIN'] = array( $langCode, 'EDULOGIN' );
	return true;
}
