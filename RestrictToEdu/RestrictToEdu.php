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

$wgEduEmailPattern = "|\.edu$|";

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

	/**
	 * Render the user login form
	 */
	function mainLoginForm( $msg, $msgtype = 'error' ) {
		global $wgOut, $wgUser;
		if( $msg ) {
			$msg = wfMsg( $msg );
			$msg = substr( $msg, 4, strlen( $msg ) -8 ); // hack to remove &lt; and &gt;
			$wgOut->addHtml( "<div class=\"errorbox\"><strong>Login error</strong><br />$msg</div>" );
			$wgOut->addHtml( "<div class=\"visualClear\"></div>" );
		}
		if( $this->mCreateaccount ) $wgOut->addHtml( self::renderCreateAccount() );
		elseif( $this->mLoginattempt ) $wgOut->addHtml( self::renderUserLogin() );
		elseif( $this->mMailmypassword ) $wgOut->addHtml( self::renderForgottenPassword() );
	}
	/**
	 * Render the account creation form
	 */
	static function renderCreateAccount() {
		global $wgOut, $wgUser;
		$url = Title::newFromText( 'RestrictToEdu/CreateAccount', NS_SPECIAL )->getLocalUrl();
		$html = "<div id=\"userlogin\"><form id=\"userlogin2\" action=\"$url\" method=\"POST\">\n";
		$html .= "<h2>" . wfMsg( 'edu-dont-have-account' ) . "</h2>\n";
		$html .= "<table>\n";
		$html .= "<tr><td class=\"edu-label\"><label for=\"wpRealName\">" . wfMsg( 'edu-name-format' ) . ":</label></td></tr>\n";
		$html .= "<tr></tr><td class=\"edu-input\"><input name=\"wpRealName\" size=\"20\" /></td></tr>\n";
		$html .= "<tr><td class=\"edu-label\"><label for=\"wpEmail\">E-mail:</label></td></tr>\n";
		$html .= "<tr></tr><td class=\"edu-input\"><input name=\"wpEmail\" size=\"20\" /></td></tr>\n";
		$html .= "<tr><td class=\"edu-label\">". wfMsg( 'edu-must-be-edu' ) . "</td></tr>\n";
		$html .= "<tr><td class=\"edu-submit\"><input type=\"submit\" value=\"Create account\" name=\"wpCreateaccountMail\"></td></tr>\n";
		$html .= "<tr><td class=\"edu-label\">". wfMsg( 'edu-send-temp-email' ) . "</td></tr>\n";
		$html .= "</table></form></div>\n";
		return $html;
	}

	/**
	 * Render the user login form
	 */
	static function renderUserLogin() {
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
		
		// hook in to post-login and check if it's a temporary password, if so, confirm the users email

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
			//getPasswordValidity
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
		$login = $this->renderUserLogin();
		$create = $this->renderCreateAccount();
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
		//mailPasswordInternal
	}

	
	/**
	 * Process a submitted account creation form
	 */
	function processCreateAccount() {
		global $wgRequest;
		$form = new EduLoginForm( $wgRequest );
		$form->execute();

		// maybe: change the temporary password messge
		
		// do the send-temporary password process
		
	}

	/**
	 * Process a submitted user login form
	 */
	function processUserLogin() {
		global $wgRequest;
		$form = new EduLoginForm( $wgRequest );
		$form->execute();
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
