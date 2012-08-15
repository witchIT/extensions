<?php
/**
 * This class is used as the sign-up page associated with page-id 13 "Account registration"
 * It also has a corresponding database table to give extra properties to all created users
 * it does this by creating a users page on account creattion and associates properties with
 * the new page.
 * Note that its the upAccount class that handles the rendering of NS_USER pages
 * - upUser and upAccount may be merged at some point
 */

class UserProfile extends ArticleProperties {

	// This ArticleProperties class has a corresponding database table for its instances
	public static $table = 'userprofile';
	public static $prefix = 'up_';
	public static $columns = array(
		'FirstName' => 'VARCHAR(32) NOT NULL',
		'LastName'  => 'VARCHAR(32) NOT NULL',
		'Country'   => 'VARCHAR(2) NOT NULL',
		'Language'  => 'VARCHAR(16)',
		'Mobile'    => 'VARCHAR(32)',
		'RegType'   => 'VARCHAR(16) NOT NULL'
	);

	var $uid = 0;
	var $jsI18n = array(
		'up-reg-fill-in-all',
		'badretype',
		'up-reg-emailretype'
		'up-reg-fill-in-all',
		'up-acc-prefs-updated'
	);
	var $myProps = array();

	function __construct( $title, $passive = true ) {
		global $wgOut, $wgRequest;

		// If the passed "title" is numeric treat as a UID and change it to the userpage title
		if( is_numeric( $title ) ) {
			$user = User::newFromId( $title );
			$title = $user->getUserPage();
		}

		// If not passive, adjust skin elements and force to view
		if( !$passive ) {
			$wgRequest->setVal( 'action', 'view' );
			$skin = $wgOut->getSkin();
			$skin->upPage = true;
			$skin->showTitle = true;
		}

		return parent::__construct( $title );
	}

	/**
	 * Render Account registration page
	 */
	function renderSignup() {
		global $wgOut, $wgRequest;
		$ec = ec;
		$title = 'Terms and conditions';
		$tc = Title::newFromText( $title )->getLocalUrl();
		$tc = "<a href=\"$tc\" title=\"$title\">$title</a>";
		$title = 'Privacy policy';
		$pp = Title::newFromText( $title )->getLocalUrl();
		$pp = "<a href=\"$pp\" title=\"$title\">$title</a>";

		// Set the return page from the referrer
		if( array_key_exists( 'HTTP_REFERER', $_SERVER ) ) {
			preg_match( "|//.+?(/.*)$|", $_SERVER['HTTP_REFERER'], $m );
			$return = $m[1];
		} else $return = '';

		// Set up the user session and token
		if( session_id() == '' ) wfSetupSession();
		if( !$wgRequest->getSessionData( 'wsCreateaccountToken' ) ) $wgRequest->setSessionData( 'wsCreateaccountToken', User::generateToken() );
		$token = $wgRequest->getSessionData( 'wsCreateaccountToken' );

		// Set the page title
		$wgOut->setPageTitle( wfMsg( 'up-registration-title' ) );

		// Build the form
		$html = "<form id=\"up-registration-form\" action=\"\" method=\"POST\" onsubmit=\"return up_signup()\">
		<table id=\"up-registration\">
		<tr style=\"display:none\" id=\"signup-result\"><td colspan=\"2\">
			<div id=\"up-ajax-loader\">" . wfMsg( 'up-processing' ) . "</div><div class=\"err\"></div>
		</td></tr><tr>
		<td class=\"left-panel\">
			<table>
				<tr><td colspan=\"2\"><h2>" . wfMsg( 'up-reg-details' ) . "</h2></td></tr>" .
					$this->inputRow( 'up-reg-firstname', 'FirstName' ) .
					$this->inputRow( 'up-reg-lastname', 'LastName' ) .
					"<tr><th><label for=\"wpCountry\">" . wfMsg( 'up-reg-country' ) . "</label></th>" .
					"<td>" . $this->select( 'Country', upCountry::getList(), '', '', false ) . "</td></tr>" .
					"<tr><th><label for=\"wpLanguage\">" . wfMsg( 'up-reg-lang' ) . "</label></th>" .
					"<td>" . $this->select( 'Language', up::languages(), '', '', false ) . "</td></tr>" .
				"<tr><td colspan=\"2\" class=\"border-top\"><h2>" . wfMsg( 'up-reg-creds' ) . "</h2></td></tr>" .
					$this->inputRow( 'up-reg-username', 'Name' ) .
					$this->inputRow( 'up-reg-passwd', 'Password', '', '', array( 'type' => 'password' ) ) .
					$this->inputRow( 'up-reg-passwd2', 'Retype', '', '', array( 'type' => 'password' ) ) .
				"<tr><td colspan=\"2\" class=\"border-top\"><h2>" . wfMsg( 'up-reg-auth' ) . "</h2></td></tr>" .
					$this->inputRow( 'up-reg-email', 'Email' ) .
					$this->inputRow( 'up-reg-email2', 'EmailConfirm' ) .
					$this->inputRow( 'up-reg-mobile', 'Mobile' ) .
			"</table>
		</td>
		<td class=\"right-panel\">
			<h2>" . wfMsg( 'up-reg-type' ) . "</h2>
			<p><input type=\"radio\" name=\"wpRegType\" value=\"Standard\" /><label>" .
				wfMsg( 'up-reg-standard' ) . "</label> : " . wfMsg( 'up-reg-standard-info' ) . "</input></p>
			<p><input type=\"radio\" name=\"wpRegType\" value=\"Premium\" checked=\"yes\" /><label>" .
				wfMsg( 'up-reg-premium' ) . "</label> : " . wfMsg( 'up-reg-premium-info' ) . "</input></p>
			<p><i>(" . wfMsg( 'up-reg-offer' ) . "</i>)</p>
			<input type=\"submit\" value=\"" . wfMsg( 'up-signup' ) . "\" name=\"wpCreateaccount\" id=\"wpCreateaccount\" />
			<input type=\"hidden\" value=\"$token\" name=\"wpCreateaccountToken\" />
			<input type=\"hidden\" value=\"$return\" id=\"wpReturnTo\" name=\"wpReturnTo\" />
			<p>" . wfMsg( 'up-reg-agree', $tc, $pp ) . "</p>
			<p class=\"nb\">" . wfMsg( 'up-reg-agree-nb' ) . "</p>
		</td></tr></table></form>";

		$wgOut->addHTML( $html );
	}


	/**
	 * When a user goes to their user page show the profile with various updatable fields depending on perms
	 */
	function renderProfile() {
		global $wgTitle, $wgUser, $wgOut, $wgRequest;
		$ec = ec;
		$user = $wgUser->getName();

		// The current user is viewing another users account page
		$page = $wgTitle->getText();
		if( $user != $page ) {
			$wgOut->setPageTitle( $page );
			$html = "<table id=\"up-account\"><tr id=\"signup-result\"><td>";
			$html .= "<div class=\"err\">Sorry, public user profiles have not been implemented yet</div></td></tr></table>";
			$wgOut->addHTML( $html );
			return;
		}

		list( $bids, $bidCount ) = $this->renderBids();
		list( $properties, $propCount ) = $this->renderProperties(); // sets $this->myProps
		list( $watchlist, $watchCount ) = $this->renderWatchlist();
		list( $feedback, $feedbackCount ) = $this->renderFeedback();
		list( $feedbackRcvd, $feedbackRcvdCount ) = $this->renderFeedbackRcvd(); // uses $this->myProps
		$userpage = urlencode( $wgUser->getUserpage()->getPrefixedText() );
		$prefs = $this->renderPreferences();

		// Set the page title
		$wgOut->setPageTitle( wfMsg( 'up-acc-title' ) );

		// Build the main content
		$html = "<table id=\"up-account\"><tr id=\"signup-result\"><td colspan=\"2\">";
		$html .= "<div style=\"display:none\" id=\"up-ajax-loader\">" . wfMsg( 'up-processing' ) . "</div>
			<div style=\"display:none\" class=\"err\"></div></td></tr><tr>";

		$html .= "<td class=\"left-panel\">
			<table>
				<tr><td><h2>$user</h2></td></tr>
				<tr><td>
					<p><a id=\"up-acc-summary-link\" href=\"#\" onclick=\"acc_lplink_click('summary')\">" . ucwords( wfMsg( 'up-acc-summary' ) ) . "</a></p>
					<p><a id=\"up-acc-prefs-link\" href=\"#\" onclick=\"acc_lplink_click('prefs')\">" . ucwords( wfMsg( 'up-prefs' ) ) . "</a></p>
					<p><a href=\"/Special:ChangePassword?returnto=$userpage\">" . ucwords( wfMsg( 'changepassword' ) ) . "</a></p>
				</td></tr>
				<tr><td colspan=\"2\" class=\"border-top\"><h2>" . ucwords( wfMsg( 'up-buy' ) ) . "</h2></td></tr>
				<tr><td>
					<p><a id=\"up-acc-buy-link\" href=\"#\" onclick=\"acc_lplink_click('buy')\">" . ucwords( wfMsg( 'up-bids' ) ) . " ($bidCount)</a></p>
					<p><a id=\"up-acc-watchlist-link\" href=\"#\" onclick=\"acc_lplink_click('watchlist')\">" . ucwords( wfMsg( 'up-watchlist' ) ) . " ($watchCount)</a></p>
					<p><a id=\"up-acc-feedback-link\" href=\"#\" onclick=\"acc_lplink_click('feedback')\">" . ucwords( wfMsg( 'up-feedback' ) ) . " ($feedbackCount)</a></p>
				</td></tr>
				<tr><td colspan=\"2\" class=\"border-top\"><h2>" . ucwords( wfMsg( 'up-sell' ) ) . "</h2></td></tr>
				<tr><td>
					<p><a id=\"up-acc-properties-link\" href=\"#\" onclick=\"acc_lplink_click('properties')\">" . ucwords( wfMsg( 'up-properties' ) ) . " ($propCount)</a></p>
					<p><a id=\"up-acc-feedbackrcvd-link\" href=\"#\" onclick=\"acc_lplink_click('feedbackrcvd')\">" . ucwords( wfMsg( 'up-feedbackrcvd' ) ) . " ($feedbackRcvdCount)</a></p>
					<p><a id=\"up-acc-listproperty-link\" href=\"/Property:Search+List\">" . ucwords( wfMsg( 'up-list-property' ) ) . "</a></p>
				</td></tr>
			</table>
		</td>
		<td class=\"right-panel\">
			<div class=\"tabset\" id=\"up-acc-summary\">
				<h2>" . wfMsg( 'up-acc-getmore' ) . "</h2>
				<p>" . wfMsg( 'up-acc-desc' ) . "</p>
				<table><tr>
					<td id=\"up-acc-find\"><h3>" . wfMsg( 'up-acc-find' ) . "</h3><p>" . wfMsg( 'up-acc-find-desc' ) . "</p></td>
					<td id=\"up-acc-watch\"><h3>" . wfMsg( 'up-acc-watch' ) . "</h3><p>" . wfMsg( 'up-acc-watch-desc' ) . "</p></td>
					<td id=\"up-acc-list\"><h3>" . wfMsg( 'up-acc-list' ) . "</h3><p>" . wfMsg( 'up-acc-list-desc' ) . "</p></td>
				</tr></table>
				<div id=\"up-acc-bid\">
					<h3>" . wfMsg( 'up-acc-bid' ) . "</h3>
					<p>" . wfMsg( 'up-acc-bid-desc' ) . "</p>
					<p>" . wfMsg( 'up-acc-bid-start' ) . "</p>
				</div>
			</div>
			<div class=\"tabset\" id=\"up-acc-buy\" style=\"display:none\">
				<h2>" . wfMsg( 'up-bids' ) . "</h2>
				$bids
			</div>
			<div class=\"tabset\" id=\"up-acc-watchlist\" style=\"display:none\">
				<h2>" . wfMsg( 'up-watchlist' ) . "</h2>
				$watchlist
			</div>
			<div class=\"tabset\" id=\"up-acc-feedback\" style=\"display:none\">
				<h2>" . wfMsg( 'up-feedback' ) . "</h2>
				$feedback
			</div>
			<div class=\"tabset\" id=\"up-acc-properties\" style=\"display:none\">
				<h2>" . wfMsg( 'up-properties' ) . "</h2>
				$properties
			</div>
			<div class=\"tabset\" id=\"up-acc-feedbackrcvd\" style=\"display:none\">
				<h2>" . wfMsg( 'up-feedbackrcvd' ) . "</h2>
				$feedbackRcvd
			</div>
			<div class=\"tabset\" id=\"up-acc-prefs\" style=\"display:none\">
				<h2>" . ucwords( wfMsg( 'up-prefs' ) ) . "</h2>
				$prefs
			</div>
			<div class=\"tabset\" id=\"up-acc-listproperty\" style=\"display:none\">
				<h2>" . wfMsg( 'up-list-property' ) . "</h2>
			</div>
		</td></tr></table>";

		$wgOut->addHTML( $html );
	}

	/**
	 * Render the user preferences
	 */
	function renderPreferences() {
		global $wgUser;
		$zu = new upUser( $this->uid );
		$email = $wgUser->getEmail();
		$uc = $wgUser->isEmailConfirmed() ? '' : '<tr><td></td><td class="up-error">' . wfMsg( 'up-reg-confirm' ) . '</td></tr>';

		// Build the countries option list
		$html = "<table>" .
			"<tr><th><label for=\"wpCountry\">" . wfMsg( 'up-reg-country' ) . "</label></th>" .
			"<td>" . $zu->select( 'Country', upCountry::getList(), '', '', false ) . "</td></tr>" .
			"<tr><th><label for=\"wpLanguage\">" . wfMsg( 'up-reg-lang' ) . "</label></th>" .
			"<td>" . $zu->select( 'Language', up::languages(), '', '', false ) . "</td></tr>" .
			"<tr><th><label for=\"wpEmail\">" . wfMsg( 'up-reg-email' ) . "</label></th>" .
			"<td><input name=\"wpEmail\" id=\"wpEmail\" value=\"$email\" /></td></tr>$uc" .
			$zu->inputRow( 'up-reg-mobile', 'Mobile' ) .
			"<tr><td></td><td><input type=\"button\" value=\"" . wfMsg( 'up-acc-update-prefs' ) . "\" onclick=\"up_update_prefs()\" /></td></tr>" .
			"</table>";
		return $html;
	}

	/**
	 * Process preferences posted by Ajax
	 */
	static function updatePreferences( $country, $lang, $email, $mobile ) {
		global $wgUser;
		$res = 'success';

		// Set the email address if its changed
		if( $email != $wgUser->getEmail() ) {
			$wgUser->setEmail( $email );
			$wgUser->invalidateEmail();
			$wgUser->saveSettings();
			$wgUser->sendConfirmationMail( 'changed' );
			$res = wfMsg( 'up-reg-confirm' );
		}

		// Update the up-specific properties
		$zu = new UserProfile( $wgUser->getID() );
		$zu->properties( array(
			'Country' => $country,
			'Language' => $lang,
			'Mobile' => $mobile
		) );

		return $res;
	}
}
