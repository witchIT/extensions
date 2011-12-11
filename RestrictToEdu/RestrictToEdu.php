<?php
/**
 * RestrictToEdu extension - restricts account creation to users with .edu email addresses
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author Aran Dunkley [http://www.organicdesign.co.nz/nad User:Nad]
 * @copyright © 2011 Aran Dunkley
 * @licence GNU General Public Licence 2.0 or later
 */
if( !defined( 'MEDIAWIKI' ) ) die( "Not an entry point." );
define( 'RESTRICTTOEDU_VERSION', "0.0.1, 2011-12-10" );

define( 'EDU_EMAIL_NOT_FOUND', 'email address not found in database' );

$wgEduEmailPattern = "|\.edu$|";
$wgEnableEmail = true;

$dir = dirname( __FILE__ );
$wgExtensionMessagesFiles['RestrictToEdu'] = "$dir/RestrictToEdu.i18n.php";
require_once( "$IP/includes/SpecialPage.php" );

$wgExtensionFunctions[] = 'wfSetupRestrictToEdu';
$wgHooks['LanguageGetMagic'][] = 'wfRestrictToEduLanguageGetMagic';
$wgSpecialPages['RestrictToEdu'] = 'RestrictToEdu';
$wgExtensionCredits['specialpage'][] = array(
	'name'        => "RestrictToEdu",
	'author'      => "[http://www.organicdesign.co.nz/nad Aran Dunkley]",
	'description' => "Restricts account creation to users with .edu email addresses (for Elance job 27354439)",
	'url'         => "https://www.elance.com/php/collab/main/collab.php?bidid=27354439",
	'version'     => RESTRICTTOEDU_VERSION
);

class EduLoginForm extends LoginForm {

	var $eduError = false;

	/**
	 * Adjust some items in the initial data
	 */
	function __construct( &$request, $par = '' ) {
		parent::__construct( $request, $par );

		// Try and find the username from the email address
		if( $this->mLoginattempt ) {
			$this->mName = self::getUserFromEmail( $this->mEmail );
		}
		
		elseif( $this->mCreateaccountMail ) {
			$this->mToken = $request->getVal( 'wpCreateaccountToken' );
			
			// If the email exists, force the username to conflict with the existing one
			$name = self::getUserFromEmail( $this->mEmail );
			if( $name != EDU_EMAIL_NOT_FOUND ) {
				$this->mName = $name;
				$this->eduError = 'edu-already-registered';
			}
		}
		
		// The user name and real name are the same for .edu users
		$this->mRealName = $this->mName;
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
	 * Render the user login form
	 */
	function mainLoginForm( $msg, $msgtype = 'error' ) {
		global $wgOut, $wgUser;

		if( $msg ) {
			if( $this->eduError ) $msg = wfMsg( $this->eduError, $this->mEmail );
			elseif( preg_match( '|' . EDU_EMAIL_NOT_FOUND . '|', $msg ) ) {
				$url = Title::newFromText( 'RestrictToEdu/CreateAccount', NS_SPECIAL )->getLocalUrl();
				$msg = wfMsg( 'edu-emailnotfound', $this->mEmail, $url );
			}
			$wgOut->addHtml( "<div class=\"errorbox\"><strong>Login error</strong><br />$msg</div>" );
			$wgOut->addHtml( "<div class=\"visualClear\"></div>" );
		}

		if( $this->mCreateaccountMail ) {
			$login = self::renderUserLogin();
			$create = self::renderCreateAccount();
			$wgOut->addHtml( "<table><tr><td>$login</td><td>$create</td></tr></table>" );
		}

		elseif( $this->mLoginattempt ) {
			$login = self::renderUserLogin();
			$create = self::renderCreateAccount();
			$wgOut->addHtml( "<table><tr><td>$login</td><td>$create</td></tr></table>" );
		}

		elseif( $this->mMailmypassword ) $wgOut->addHtml( self::renderForgottenPassword() );
	}
	/**
	 * Render the account creation form
	 */
	static function renderCreateAccount() {
		if( !self::getCreateaccountToken() ) self::setCreateaccountToken();
		$url = Title::newFromText( 'RestrictToEdu/CreateAccount', NS_SPECIAL )->getLocalUrl();
		$html = "<div id=\"userlogin\"><form id=\"userlogin2\" action=\"$url\" method=\"POST\">\n";
		$html .= "<h2>" . wfMsg( 'edu-dont-have-account' ) . "</h2>\n";
		$html .= "<table>\n";
		$html .= "<tr><td class=\"edu-label\"><label for=\"wpName\">" . wfMsg( 'edu-name-format' ) . ":</label></td></tr>\n";
		$html .= "<tr></tr><td class=\"edu-input\"><input name=\"wpName\" size=\"20\" /></td></tr>\n";
		$html .= "<tr><td class=\"edu-label\"><label for=\"wpEmail\">E-mail:</label></td></tr>\n";
		$html .= "<tr></tr><td class=\"edu-input\"><input name=\"wpEmail\" size=\"20\" /></td></tr>\n";
		$html .= "<tr><td class=\"edu-label\">". wfMsg( 'edu-must-be-edu' ) . "</td></tr>\n";
		$html .= "<tr><td class=\"edu-submit\"><input type=\"submit\" value=\"Create account\" name=\"wpCreateaccountMail\"></td></tr>\n";
		$html .= "<tr><td class=\"edu-label\">". wfMsg( 'edu-send-temp-email' ) . "</td></tr>\n";
		$html .= "<input type=\"hidden\" value=\"" . self::getCreateaccountToken() . "\" name=\"wpCreateaccountToken\" />";
		$html .= "</table></form></div>\n";
		return $html;
	}

	/**
	 * Render the user login form
	 */
	static function renderUserLogin() {
		if ( !self::getLoginToken() ) self::setLoginToken();
		$url = Title::newFromText( 'RestrictToEdu/UserLogin', NS_SPECIAL )->getLocalUrl();
		$html = "<div id=\"userlogin\"><form id=\"userlogin2\" action=\"$url\" method=\"POST\">\n";
		$html .= "<h2>" . wfMsg( 'edu-have-account' ) . "</h2>\n";
		$html .= "<table>\n";
		$html .= "<tr><td class=\"edu-label\"><label for=\"wpEmail\">E-mail:</label></td></tr>\n";
		$html .= "<tr></tr><td class=\"edu-input\"><input name=\"wpEmail\" size=\"20\" /></td></tr>\n";
		$html .= "<tr><td class=\"edu-label\"><label for=\"wpPassword\">Password</label></td></tr>\n";
		$html .= "<tr></tr><td class=\"edu-input\"><input name=\"wpPassword\" type=\"password\" size=\"20\" /></td></tr>\n";
		$html .= "<tr><td class=\"edu-label\">&nbsp;</td></tr>\n";
		$html .= "<tr><td class=\"edu-submit\"><input type=\"submit\" value=\"Login\" name=\"wpLoginattempt\"></td></tr>\n";
		$forgotUrl = Title::newFromText( 'RestrictToEdu', NS_SPECIAL )->getLocalUrl();
		$forgotLink = "<a href=\"$forgotUrl\">". wfMsg( 'edu-forgot-password' ) . "</a>";
		$html .= "<tr><td class=\"edu-label\">$forgotLink</td></tr>\n";
		$html .= "<input type=\"hidden\" value=\"" . self::getLoginToken() . "\" name=\"wpLoginToken\" />";
		$html .= "</table></form></div>\n";
		return $html;
	}

	/**
	 * Render the forgot password form
	 */
	static function renderForgottenPassword() {
		$url = Title::newFromText( 'RestrictToEdu/ForgotPassword', NS_SPECIAL )->getLocalUrl();
		$html = "<div id=\"userlogin\"><form id=\"userlogin2\" action=\"$url\" method=\"POST\">\n";
		$html .= "<h2>" . wfMsg( 'edu-forgot-password' ) . "</h2>\n";
		$html .= "<table>\n";
		$html .= "<tr><td class=\"edu-label\">". wfMsg( 'edu-send-new-email' ) . "</td></tr>\n";
		$html .= "<tr><td class=\"edu-label\">&nbsp;</td></tr>\n";
		$html .= "<tr><td class=\"edu-label\"><label for=\"wpEmail\">E-mail:</label></td></tr>\n";
		$html .= "<tr></tr><td class=\"edu-input\"><input name=\"wpEmail\" size=\"20\" /></td></tr>\n";
		$html .= "<tr><td class=\"edu-submit\"><input type=\"submit\" value=\"Send Password\" name=\"wpMailmypassword\"></td></tr>\n";
		$html .= "</table></form></div>\n";
		return $html;
	}
}

class RestrictToEdu extends SpecialPage {

	function __construct() {
		global $wgHooks, $wgParser;

		SpecialPage::SpecialPage( 'RestrictToEdu', false, true, false, false, false );

		// Create a parser-function to render login
		$wgParser->setFunctionHook( 'EDULOGIN', array( $this, 'expandParserFunction' ), SFH_NO_HASH );

	}

	/**
	 * Render the special page
	 */
	function execute( $param ) {
		global $wgOut;
		$this->setHeaders();

		if( $param == 'ForgotPassword' ) {
			$this->processForgottenPassword();
		}

		elseif( $param == 'CreateAccount' ) {
			$this->processCreateAccount();
		}

		elseif( $param == 'UserLogin' ) {
			$this->processUserLogin();
		}

		else {
			$wgOut->addHtml( EduLoginForm::renderForgottenPassword() );
		}
	}

	/**
	 * Expand the EDULOGIN parser function
	 */
	function expandParserFunction( &$parser ) {
		global $wgUser;
		$parser->disableCache();
		if( $wgUser->isLoggedIn() ) return '';
		$login = EduLoginForm::renderUserLogin();
		$create = EduLoginForm::renderCreateAccount();
		$html = "<table><tr><td>$login</td><td>$create</td></tr></table>";
		return array( $html, 'isHTML' => true, 'noparse' => true);
	}

	/**
	 * Process a forgotten password form
	 */
	function processForgottenPassword() {
		global $wgRequest;
		$form = new EduLoginForm( $wgRequest );
		$form->execute();
	}

	
	/**
	 * Process a submitted account creation form
	 */
	function processCreateAccount() {
		global $wgRequest;
		if( session_id() == '' ) wfSetupSession();
		$form = new EduLoginForm( $wgRequest );
		$form->execute();
	}

	/**
	 * Process a submitted user login form
	 */
	function processUserLogin() {
		global $wgRequest, $wgEduRequest;
		if( session_id() == '' ) wfSetupSession();
		$form = new EduLoginForm( $wgRequest );
		$wgEduRequest = $wgRequest;
		$wgRequest = new EduRequest( $form->mName );
		$form->execute();
		$wgRequest = $wgEduRequest;
	}

	/**
	 * Confirm email address
	 */
	function confirmEmail() {
		
		// when a person logs in with their temporary email address
		// their email address should be confirmed
		
	}

}

/**
 * A dummy requets object that returns a wpName when only a wpEmail was submitted
 * - $wgRequest is replaced with this dummy object temporarily within processUserLogin()
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
function wfSetupRestrictToEdu() {
	global $wgRestrictToEdu;
	$wgRestrictToEdu = new RestrictToEdu();
	SpecialPage::addPage( $wgRestrictToEdu );
}

/**
 * Set up magic word for parser-function
 */
function wfRestrictToEduLanguageGetMagic( &$langMagic, $langCode = 0 ) {
	$langMagic['EDULOGIN'] = array( $langCode, 'EDULOGIN' );
	return true;
}
