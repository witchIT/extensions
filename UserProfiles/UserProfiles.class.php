<?php
class UserProfiles {

	var $sysop = false;
	var $user = false;

	public static $confirmEmail = false;

	function __construct() {
		global $wgContLang, $wgOut, $wgHooks, $wgUser, $wgShowExceptionDetails, $wgShowSQLErrors, $wgDefaultSkin, $wgResourceModules;

		// Force some user options
		$wgUser->setOption( 'skin', $wgDefaultSkin );
		$wgUser->setOption( 'showtoolbar', false );

		// Set some user status variables
		$this->user = $wgUser->isLoggedIn();
		$this->sysop = in_array( 'sysop', $wgUser->getEffectiveGroups() );

		// Add some relevant keywords
		$wgOut->addMeta('keywords', 'real estate,buying real estate,selling real estate,property,buying property,selling property' );
		$wgOut->addMeta('robots', 'index,follow');

		// Add hooks
		$wgHooks['UserGetRights'][] = $this;
		$wgHooks['ArticlePropertiesClassFromTitle'][] = $this;
		$wgHooks['OutputPageBodyAttributes'][] = $this;
		$wgHooks['MediaWikiPerformAction'][] = $this;
		$wgHooks['AddNewAccount'][] = $this;
		$wgHooks['UserSetEmailAuthenticationTimestamp'][] = $this;

		// Set up Javascript resource
		$wgResourceModules['ext.userprofiles'] = array(
			'scripts'       => array( 'userprofiles.js' ),
			'dependencies'  => array( 'jquery.ui.autocomplete' ),
			'localBasePath' => dirname( __FILE__ ),
			'remoteExtPath' => basename( dirname( __FILE__ ) ),
		);
		$wgOut->addModules( 'ext.userprofiles' );

		// Make a default error message re confirming email if logged in and not confirmed
		if( $wgUser->isLoggedIn() && !$wgUser->isEmailConfirmed() ) self::message( wfMsg( 'znazza-reg-confirm' ), 'error' );

	}

	/**
	 * Determine from article title and namespace whether to use an ArticleProperties class
	 */
	public static function onArticlePropertiesClassFromTitle( $title, &$class ) {
		global $wgZnazzaPageClasses;
		$ns = $title->getNamespace();
		$id = $title->getArticleId();
		$zp = false;
		if( array_key_exists( $ns, $wgZnazzaPageClasses['ns'] ) ) $zp = $wgZnazzaPageClasses['ns'][$ns];
		elseif( $id && array_key_exists( $id, $wgZnazzaPageClasses['id'] ) ) $zp = $wgZnazzaPageClasses['id'][$id];
		if( $zp ) {
			if( is_array( $zp ) ) $zp[1] = dirname( __FILE__ ) . "/pages/$zp[1].class.php";
			$class = $zp;
		}
		return true;
	}

	/**
	 * Add some body classes
	 */
	function onOutputPageBodyAttributes( $out, $sk, $bodyAttrs ) {
		$bodyAttrs['class'] .= $this->user ? ' user' : ' anon';
		if( $this->sysop ) $bodyAttrs['class'] .= ' sysop';
		return true;
	}

	/**
	 * Permissions: For normal articles, return a 404 if page not created
	 */
	function onMediaWikiPerformAction( $output, $article, $title, $user, $request, $wiki ) {
		if( is_object( $title ) && !$this->sysop ) {
			if( ( $title->getNamespace() != NS_PROPERTY && !$title->exists() )
				|| ( $title->getNamespace() != NS_MAIN && $title->getNamespace() != NS_PROPERTY && $title->getNamespace() != NS_USER )
			) {
				$output->disable();
				wfResetOutputBuffers();
				$code = 404;
				include( dirname( __FILE__ ) . "/error.php" );
				die;
			}
		}
		return true;
	}

	/**
	 * Permissions: For non-sysops lock all but a few specials down - return a 404 if not allowed to read
	 */
	function onUserGetRights( $user, &$rights ) {
		global $wgTitle, $wgOut, $wgContLang;
		$specials = $wgContLang->getSpecialPageAliases();
		$title = explode( '/', $wgTitle->getText() );
		$title = $title[0];
		if( is_object( $wgTitle )
			&& $wgTitle->getNamespace() == NS_SPECIAL
			&& !self::sysop()
			&& $title != $specials['Userlogin'][0]
			&& $title != $specials['Userlogout'][0]
			&& $title != $specials['ChangePassword'][0]
			&& $title != $specials['PasswordReset'][0]
			&& $title != $specials['Confirmemail'][0]
		) {
			$wgOut->disable();
			wfResetOutputBuffers();
			$code = 404;
			include( dirname( __FILE__ ) . "/error.php" );
			die;
		}
		return true;
	}


	/**
	 * After a new user account is created, create a UserProfile instance and store the posted data into it
	 */
	public static function onAddNewAccount( $user, $byEmail ) {
		global $wgUserProfileStaff;
		$zu = new UserProfile( $user->getId() );
		$summary = "Userpage created automatically to associate UserProfile properties with";
		$zu->doEdit( wfMsg( 'ap_preloadtext' ), $summary, EDIT_NEW );
		$zu->updatePropertiesFromRequest( array_keys( UserProfile::$columns ) );

		// Display a message to the user (after the ajax response page has rendered)
		self::message( wfMsg( 'userprofile-reg-created' ), '1success' );

		// Send notification to the staff of the new registration
		$username = $zu->getName();
		foreach( $wgUserProfileStaff as $name => $email ) {
			self::sendMail(
				$email,
				wfMsg( 'userprofile-notify-newuser', $name, $username ),
				wfMsg( 'userprofile-notify-newuser-subject', $username )
			);
		}

		return true;
	}

	/**
	 * Return current users sysop status
	 */
	public static function sysop() {
		global $wgUser;
		return in_array( 'sysop', $wgUser->getEffectiveGroups() );
	}

	/**
	 * Send an email from the site to a user
	 */
	static function sendMail( $to, $body, $subject, $from = false ) {
		global $wgPasswordSender, $wgSitename;
		if( $from === false ) $from = $wgPasswordSender;
		$from = new MailAddress( $from, $wgSitename );
		$to = new MailAddress( $to );
		$body = wordwrap( $body, 72 );
		return UserMailer::send( $to, $from, $subject, $body );
	}

	/**
	 * Set a persistent user message
	 */
	static function message( $msg, $type = 'success' ) {
		global $wgUser;
		$wgUser->setOption( ZNAZZA_MSGOPT, $wgUser->getOption( USERPROFILES_MSGOPT, '' ) . "||$type|$msg" );
		$wgUser->saveSettings();
	}

	/**
	 * Return a list of the messages in the queue for this user
	 * returned as msg => type so that the same message can't be displayed twice
	 */
	static function messages() {
		global $wgUser;
		$messages = array();
		foreach( explode( '||', $wgUser->getOption( USERPROFILES_MSGOPT, '' ) ) as $msg ) {
			if( $msg ) {
				list( $type, $msg ) = explode( '|', $msg );
				$messages[$msg] = $type;
			}
		}
		return $messages;
	}

	/**
	 * Remove and return messages in users queue
	 */
	static function popMessages() {
		global $wgUser;
		$messages = array();
		$delayed = array();
		foreach( self::messages() as $msg => $type ) {

			// Delay the message if it's type starts with an integer (nType) to delay by n views before showing
			if( preg_match( "|(\d)(.+)|", $type, $m ) ) {
				$type = $m[1] > 1 ? ($m[1] - 1) . $m[2] : $m[2];
				$delayed[$type] = $msg;
			}

			// Display the message now
			else {
				$msg = "<p>$msg</p>";
				if( array_key_exists( $type, $messages ) ) $messages[$type] .= $msg;
				else $messages[$type] = $msg;
			}
		}

		// Hack needed here, must not save user settings when confirming email
		if( self::$confirmEmail ) {
			$messages = array();
			$wgUser->mEmailAuthenticated = self::$confirmEmail;
		}

		// Clear the queue
		$wgUser->setOption( USERPROFILES_MSGOPT, '' );
		$wgUser->saveSettings();


		// Put any delayed messages back on the queue
		foreach( $delayed as $type => $msg ) self::message( $msg, $type );

		// Return the messages that were rmemoved for display
		return $messages;
	}

	/**
	 * Hack to fix bug preventing saveSettings() when confirming email as same user
	 */
	public static function onUserSetEmailAuthenticationTimestamp( $user, &$confirm ) {
		self::$confirmEmail = $confirm;
		return true;
	}

	/**
	 * Return a user object given an email address
	 */
	static function userFromEmail( $email ) {
		$user = false;
		$dbr = &wfGetDB( DB_SLAVE );
		$tbl = $dbr->tableName( 'user' );
		$email = $dbr->addQuotes( $email );
		if( $row = $dbr->selectRow( $tbl, 'user_id', "user_email = $email" ) ) $user = User::newFromId( $row->user_id );
		return $user;
	}

}
