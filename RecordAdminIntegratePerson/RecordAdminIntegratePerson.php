<?php
/**
 * IntegratePerson extension - Integrates Person records into user preferences and account creation forms
 *
 * @package MediaWiki
 * @subpackage Extensions
 * @author Aran Dunkley [http://www.organicdesign.co.nz/nad User:Nad]
 * @licence GNU General Public Licence 2.0 or later
 */

# Check dependency extensions
if ( !defined( 'MEDIAWIKI' ) )              die( 'Not an entry point.' );
if ( !defined( 'RECORDADMIN_VERSION' ) )    die( 'RecordAdminIntegratePerson depends on the RecordAdmin extension' );
if ( !defined( 'JAVASCRIPT_VERSION' ) )     die( 'RecordAdminIntegratePerson depends on the JavaScript extension' );

# Ensure running at least MediaWiki version 1.16
if ( version_compare( substr( $wgVersion, 0, 4 ), '1.16' ) < 0 )
	die( "Sorry, RecordAdminIntegratePerson requires at least MediaWiki version 1.16 (this is version $wgVersion)" );

define( 'RAINTEGRATEPERSON_VERSION', '1.7.1, 2010-05-18' );

$wgAutoConfirmCount           = 10^10;
$wgIPDefaultImage             = '';
$wgIPMaxImageSize             = 100000;
$wgIPPersonType               = 'Person';
$wgIPRoleType                 = 'Role';
$wgIPRolesField               = 'Roles';
$wgIPParentField              = 'ReportsTo';
$wgIPExternalContributorField = 'External';
$wgIPFixUserLinks             = false;
$wgIPAddPersonalUrls          = true;

$wgExtensionFunctions[] = 'wfSetupRAIntegratePerson';
$wgHooks['LanguageGetMagic'][] = 'wfRAIntegratePersonLanguageGetMagic';
$wgExtensionCredits['other'][] = array(
	'name'        => 'RecordAdminIntegratePerson',
	'author'      => '[http://www.organicdesign.co.nz/User:Nad User:Nad]',
	'description' => 'Integrates Person records (see RecordAdmin extension) into user preferences and account creation forms',
	'url'         => 'http://www.organicdesign.co.nz/Extension:IntegratePerson',
	'version'     => RAINTEGRATEPERSON_VERSION
);

# Allow articles in Category:External contributor to be accessible if this user is an external contributor
$wgHooks['UserGetRights'][] = 'wfContributorPermissions';

class RAIntegratePerson {

	var $root           = '';
	var $people         = array();
	var $roles          = array();
	var $directRoles    = array();
	var $inheritedRoles = array();
	var $groups         = array();

	function __construct() {
		global $wgRequest, $wgTitle, $wgHooks, $wgMessageCache, $wgParser, $wgSpecialRecordAdmin, $wgIPAddPersonalUrls, $wgIPFixUserLinks;

		if ( $wgIPAddPersonalUrls ) $wgHooks['PersonalUrls'][] = array( $this, 'addPersonalUrls' );
		if ( $wgIPFixUserLinks )    $wgHooks['BeforePageDisplay'][] = array( $this, 'fixUserLinks');

		# Add the #roles parser function
		$wgParser->setFunctionHook( 'roles', array( $this, 'expandRoles' ) );

		$title = $wgSpecialRecordAdmin->title = Title::newFromText( $wgRequest->getText( 'title' ) );
		if ( !is_object( $wgTitle ) ) $wgTitle = $title;
		if ( is_object( $title ) ) {

			# Hook rendering mods into prefs
			if ( $title->getPrefixedText() == 'Special:Preferences' ) {
				$wgHooks['BeforePageDisplay'][] = array( $this, 'modPreferences' );
				$this->processForm();
			}

			# Hook rendering mods into account-creation
			if ( $title->getPrefixedText() == 'Special:UserLogin' && $wgRequest->getText( 'type' ) == 'signup' ) {
				$wgHooks['BeforePageDisplay'][] = array( $this, 'modAccountCreate' );
				$this->processForm();
			}
		}

		# Process an uploaded profile image if one was posted
		if ( array_key_exists( 'ra_Avatar', $_FILES ) && $_FILES['ra_Avatar']['size'] > 0 )
			$this->processUploadedImage( $_FILES['ra_Avatar'] );

		# Modify group membership for this user based on Role structure
		$this->initialiseRoles();
		$wgHooks['UserEffectiveGroups'][] = $this;

	}

	/**
	 * Add 'My Worklog' and 'My category' to personal URL's
	 */
	function addPersonalUrls( &$urls, &$title ) {
		global $wgUser;
		if ( $person = $wgUser->getRealName() ) {
			$userpage = array_shift( $urls );
			$talkpage = array_shift( $urls );
			$mycat    = str_replace( '$1', $person, '/Category:$1' );
			$mywork   = str_replace( '$1', $person, '/wiki/index.php?title=Category:Activities&Person=$1' );
			$urls     = array(
				'userpage' => $userpage,
				'talkpage' => $talkpage,
				'mycat'    => array( 'text' => 'My category', 'href' => $mycat  ),
				'mywork'   => array( 'text' => 'My worklog',  'href' => $mywork )
			) + $urls;
		}
		return true;
	}

	/**
	 * Change links to user page to link to the Person record instead
	 * (still in testing)
	 */
	function fixUserLinks( &$out, $skin = false ) {
		$out->mBodytext = preg_replace_callback(
			'%(<a [^<>]*?href=[\'"][^"\']+?)(User|User.talk):([^"\']+?)([#/&?][^"\']*)?([\'"][^<>]*>)([^<>]+)</a>%',
			array( $this, 'fixUserLinksCallback' ),
			$out->mBodytext
		);
		return true;
	}

	function fixUserLinksCallback( $m ) {
		$link   = $m[0];
		$ns     = $m[2] == 'User' ? '' : 'Talk:';
		$name   = $m[3];
		$qs     = $m[4];
		$anchor = $m[6];
		$user   = User::newFromName( $name );
		if ( is_object( $user ) && $user->idFromName( $name ) && $real = $user->getRealName() ) {
			if ( $anchor == $name ) $anchor = $real;
			$link = "$m[1]$ns$real$m[3]$anchor</a>";
		}
		return $link;
	}

	/**
	 * Modify the prefs page
	 */
	function modPreferences( &$out, $skin = false ) {
		global $wgJsMimeType;

		# Add JS
		$out->addScript( "<script type='$wgJsMimeType'>
			function ipSubmit() {
				document.getElementById('mw-input-realname').value = document.getElementById('FirstName').value + ' ' + document.getElementById('Surname').value
				document.getElementById('mw-input-emailaddress').value = document.getElementById('Email').value
			}
			function ipOnload() {

				// Hide some fieldsets
				$('legend:contains(\"Signature\")').parent().hide();
				$('legend:contains(\"E-mail options\")').parent().hide();
				
				// Defaults for the hidden email options
				$('#mw-input-enotifwatchlistpages').attr('checked','yes');
				$('#mw-input-enotifusertalkpages').attr('checked','yes');
				$('#mw-input-enotifminoredits').attr('checked','');
				$('#wpEmailFlag').attr('checked','');
				$('#mw-input-ccmeonemails').attr('checked','');

				// Hide realname and gender rows (and their following comment row)
				$('#mw-input-realname').parent().parent().hide().next().hide();
				$('#mw-input-gender').parent().parent().hide().next().hide();
				
			}
			addOnloadHook(ipOnload);
		</script>" );

		# Modify the forms enctype to allow uploaded image
		$out->mBodytext = str_replace(
			'<form',
			'<form onsubmit="return ipSubmit(this)" enctype="multipart/form-data"',
			$out->mBodytext
		);

		# Integrate the Person record
		$form = $this->getForm();
		$out->mBodytext = preg_replace(
			"|(<fieldset>\s*<legend>Internationalisation.+?</fieldset>)|s",
			"$1$form",
			$out->mBodytext
		);

		return true;
	}

	/**
	 * Modify the account-create page
	 */
	function modAccountCreate( &$out, $skin = false ) {
		global $wgJsMimeType;
		
		# Add JS
		$out->addScript( "<script type='$wgJsMimeType'>
			function ipSubmit() {
				document.getElementById('wpRealName').value = document.getElementById('FirstName').value + ' ' + document.getElementById('Surname').value
				document.getElementById('wpEmail').value = document.getElementById('Email').value
			}
			function ipOnload() {
				
				// Hide email, realname and standard create-account button
				$('#wpEmail').parent().parent().hide();
				$('#wpRealName').parent().parent().hide();
				$('td.mw-submit').parent().hide();
			}
			addOnloadHook(ipOnload);
		</script>" );

		# Modify the forms enctype to allow uploaded image
		$out->mBodytext = str_replace(
			'<form',
			'<form onsubmit="return ipSubmit(this)" enctype="multipart/form-data"',
			$out->mBodytext
		);

		# Integrate the Person record and add new submits at the bottom
		$form = $this->getForm();
		$submit = '<input type="submit" name="wpCreateaccount" id="wpCreateaccount" value="Create account" />';
		$submit .= '<input type="submit" name="wpCreateaccountMail" id="wpCreateaccountMail" value="by e-mail" />';
		$out->mBodytext = preg_replace(
			"|(<table.+?</table>)|s",
			"<fieldset id='login'><legend>Login details</legend>$1</fieldset>$form$submit",
			$out->mBodytext
		);

		return true;
	}

	/**
	 * Get the HTML for the Person form from RecordAdmin
	 */
	function getForm() {
		global $wgSpecialRecordAdmin, $wgIPPersonType, $wgUser;

		# Use RecordAdmin to create, examine and populate the form
		$wgSpecialRecordAdmin->preProcessForm( $wgIPPersonType );
		$wgSpecialRecordAdmin->examineForm();

		# If the user has a Person record, populate the form with its data
		$title = Title::newFromText( $wgUser->getRealName() );
		if ( is_object( $title ) && $title->exists() ) {
			$record = new Article( $title );
			$record = $record->getContent();
			$wgSpecialRecordAdmin->populateForm( $record );
		}

		# Get the form
		$form = $wgSpecialRecordAdmin->form;

		# If not a sysop, remove the administration section
		if ( !in_array( 'sysop', $wgUser->getGroups() ) ) $form = preg_replace( "|<fieldset.+?Administration.+</fieldset>|", "", $form );

		return $form;
	}

	/**
	 * Process any posted inputs from the Person record
	 */
	function processForm( ) {
		global $wgUser, $wgSpecialRecordAdmin, $wgIPPersonType;

		# Update the record values from posted data
		$this->getForm();
		$posted = false;
		foreach ( $_REQUEST as $k => $v ) if ( preg_match( '|^ra_(\\w+)|', $k, $m ) ) {
			$k = $m[1];
			if ( isset( $wgSpecialRecordAdmin->types[$k] ) ) {
				if ( is_array( $v ) ) $v = join( "\n", $v );
				elseif ( $wgSpecialRecordAdmin->types[$k] == 'bool' ) $v = 'yes';
				$wgSpecialRecordAdmin->values[$k] = $v;
				$posted = true;
			}
		}

		# If any values were posted update or ceate the record
		if ( $posted ) {

			# If new user created, use the username from the posted data, otehrwise use $wgUser
			$user = array_key_exists( 'wpName', $_REQUEST ) ? User::newFromName( $_REQUEST['wpName'] ) : $wgUser;
			$userpage = $user->getUserPage();
			$username = $user->getName();
			$wgSpecialRecordAdmin->values['User'] = $username;

			# Get the title if the users Person record and bail if invalid
			$name = $wgSpecialRecordAdmin->values['FirstName'] . ' ' . $wgSpecialRecordAdmin->values['Surname'];
			$title = Title::newFromText( $name );
			if ( !is_object( $title ) ) return false;

			# Change the user page to a redirect and grab any existing content
			$redirect = "#redirect [[$name]]";
			$article  = new Article( $userpage );
			$usertext = '';
			if ( $userpage->exists() ) {
				$text = $article->getContent();
				if ( !preg_match( '/^#redirect/', $text ) ) $usertext = "\n\n== Content original user page ==\n$text";
				$success = $article->doEdit( $redirect, "Changed userpage to redirect to [[$name]]", EDIT_UPDATE );
			} else $success = $article->doEdit( $redirect, "Created redirect to [[$name]]", EDIT_NEW );

			# Construct the record brace text
			$record = '';
			foreach ( $wgSpecialRecordAdmin->values as $k => $v ) $record .= " | $k = $v\n";
			$record = "{{" . "$wgIPPersonType\n$record}}";

			# Create or update the article
			$page = $_REQUEST['title'];
			$article = new Article( $title );
			if ( $title->exists() ) {
				$text = $article->getContent();
				$braces = false;
				foreach ( $wgSpecialRecordAdmin->examineBraces( $text ) as $brace ) if ( $brace['NAME'] == $wgIPPersonType ) $braces = $brace;
				if ( $braces ) $text = substr_replace( $text, $record, $braces['OFFSET'], $braces['LENGTH'] );
				elseif ( $text ) $text = "$record\n\n$text";
				else $text = $record;
				$text .= $usertext;
				$success = $article->doEdit( $text, "Record updated via $page", EDIT_UPDATE );
			} else $success = $article->doEdit( "$record$usertext", "Record created via $page", EDIT_NEW );

		}
	}
	
	/**
	 * Process uploaded image file
	 */
	function processUploadedImage( $file ) {
		global $wgUser, $wgSitename, $wgSiteNotice, $wgUploadDirectory, $wgIPMaxImageSize;
		$error = false;
		if ( !ereg( '^image/(jpeg|png|gif)$', $file['type'] ) ) $error = 'Uploaded file was not of a valid type!';
		if ( $file['size'] > $wgIPMaxImageSize )                $error = 'Profile images are restricted to a maximum of 100KBytes';
		if ( $file['error'] > 0 )                               $error = 'Uploaded error number ' . $file['error'] . ' occurred';
		if ( $error ) $wgSiteNotice = "<div class='errorbox'>$error</div>";
		else {
			$id = $wgUser->getId();
			$name = preg_replace( '%.+(\..+?)$%', "avatar-$wgSitename-$id$1", $file['name'] );
			foreach( glob( "$wgUploadDirectory/avatar-$wgSitename-$id.*" ) as $img ) unlink( $img );
			move_uploaded_file( $file['tmp_name'], "$wgUploadDirectory/$name" );
		}
	}

	/**
	 * Build a hash of groups this user belongs to from Role records
	 */
	function initialiseRoles() {
		global $wgUser, $wgIPPersonType, $wgIPRoleType, $wgIPRolesField, $wgIPParentField;
		
		# Store all the Person records and args
		foreach( SpecialRecordAdmin::getRecordsByType( $wgIPPersonType ) as $t ) {
			$person = SpecialRecordAdmin::getRecordArgs( $t, $wgIPPersonType );
			$name = $t->getText();
			$roles = isset( $person[$wgIPRolesField] ) ? SpecialRecordAdmin::split( $person[$wgIPRolesField] ) : array();
			$person[$wgIPRolesField] = $roles;
			$this->people[] = $person;
			foreach ( $roles as $role ) {
				if ( isset( $this->people[$role] ) ) $this->people[$role][] = $name;
				else $this->people[$role] = array( $name );
			}
		}

		# Build a reverse lookup of roles structure
		$this->roles = array();
		$roles = array();
		foreach( SpecialRecordAdmin::getRecordsByType( $wgIPRoleType ) as $t ) {
			$args = SpecialRecordAdmin::getRecordArgs( $t, $wgIPRoleType );
			$role = $t->getText();
			if ( !isset( $this->people[$role] ) ) $this->people[$role] = array();
			if ( !isset( $roles[$role] ) ) $roles[$role] = array();
			if ( isset( $args[$wgIPParentField] ) ) {
				$parent = $args[$wgIPParentField];
				$this->roles[$role] = $parent;
				if ( !isset( $roles[$parent] ) ) $roles[$parent] = array();
				array_push( $roles[$parent], $role );
			} else {
				$this->roles[$role] = false;
				$this->root = $role;
			}
		}
		$this->tree = $roles;

		# Scan the role structure and make child list contain all descendents
		foreach( $roles as $i => $role ) $roles[$i] = array_unique( array_merge( $roles[$i], self::recursiveRoleScan( $roles, $role ) ) );

		# Loop through this user's roles and assign the user to role-groups
		$query = array( 'type' => $wgIPPersonType, 'record' => $wgUser->getRealname(), 'field' => $wgIPRolesField );
		foreach( SpecialRecordAdmin::getFieldValue( $query, true ) as $role1 ) {
			if ( isset( $roles[$role1] ) ) {
				self::addGroup( $this->groups, $role1 );
				$this->directRoles[] = $role1;
				foreach( $roles[$role1] as $role2 ) {
					self::addGroup( $this->groups, $role2 );
					$this->inheritedRoles[] = $role2;
				}
			}
		}
	}

	/**
	 * Make user a memeber of a group apssociated with a role
	 */
	static function addGroup( &$groups, $role ) {
		global 	$wgGroupPermissions, $wgRestrictionLevels, $wgMessageCache;
		$group = str_replace( ' ', '-', strtolower( $role ) );
		$groups[] = $group;
		$wgRestrictionLevels[] = $group;
		$wgMessageCache->addMessages( array( "protect-level-$group" => $role ) );
		$wgMessageCache->addMessages( array( "right-$group" => wfMsg( 'security-restricttogroup', $role ) ) );
		$wgGroupPermissions[$group][$group]  = true;  # members of group must be allowed to perform group-action
		$wgGroupPermissions['sysop'][$group] = true;  # sysops must be allowed to perform group-action as well
	}

	/**
	 * Scan the role structure and make child list contain all descendents
	 */
	static function recursiveRoleScan( &$roles, &$role ) {
		static $bail = 200;
		if ( $bail-- == 0) die( "recursiveRoleScan bailout point (200) reached" );
		$tmp = $role;
		foreach( $role as $r ) $tmp = array_merge( $tmp, self::recursiveRoleScan( $roles, $roles[$r] ) );
		return $tmp;
	}
	
	/**
	 * Set the group permissions for the current user from the Role records
	 */
	function onUserEffectiveGroups( &$user, &$groups ) {
		$groups = array_unique( array_merge( $groups, $this->groups ) );
		return true;		
	}
	
	/**
	 * Expand #roles parser function
	 */
	function expandRoles( &$parser, $format ) {
		$text = '';
		$this->recursiveRoleTree( $text, 1, $this->root, $format );
		return $text;
	}

	/**
	 * Recursive function for expandRoles
	 */
	function recursiveRoleTree( &$text, $depth, $role, $format ) {
		static $bail = 200;
		if ( $bail-- == 0) die( "recursiveRoleTree bailout point (200) reached" );
		$indent = str_repeat( '*', $depth );
		$i = in_array( $role, $this->inheritedRoles ) ? "''" : "";
		if ( in_array( $role, $this->directRoles ) ) $i = "'''";
		$text .= "{$indent}{$i}[[$role]]$i\n";
		if ( $format == 'ShowPeople' ) {
			global $wgUser;
			$name = $wgUser->getRealName();
			foreach( $this->people[$role] as $person ) {
				$b = $person == $name ? "'''" : "";
				$text .= "*{$indent}<small>{$b}[[$person]]{$b}</small>\n";
			}
		}	
		foreach( $this->tree[$role] as $child ) $this->recursiveRoleTree( $text, $depth + 1, $child, $format );
	}
}

/**
 * Magic words for parser function
 */
function wfRAIntegratePersonLanguageGetMagic( &$langMagic, $langCode = 0 ) {
	$langMagic['roles'] = array( $langCode, 'roles' );
	return true;
}

/**
 * Handle Category:Articles readbale by *
 * - if an article is a member of such a category, then it should be readable by only the designated people
 */
function wfContributorPermissions( &$user, &$rights ) {
	global $wgTitle, $wgWhitelistRead, $wgGroupPermissions;
	if ( $user->isAnon() || in_array( 'sysop', $user->getGroups() ) ) return true;
	$dbr   = &wfGetDB( DB_SLAVE );
	$cl    = $dbr->tableName( 'categorylinks' );
	$id    = $wgTitle->getArticleID();
	$res   = $dbr->select( $cl, 'cl_to', "cl_from = $id", __METHOD__, array( 'ORDER BY' => 'cl_sortkey' ) );
	$match = wfMsg( 'ip-lockedarticle', '' );
	$name  = $user->getRealName();
	while ( $row = $dbr->fetchRow( $res ) ) {
		$cat = str_replace( '_', ' ', $row[0] );
		if ( preg_match( "/^$match/", $cat ) ) {
			if ( preg_match( "/$name$/", $cat ) ) $wgWhitelistRead[] = $wgTitle->getText();
			else $rights = array();
		}
	}
	$dbr->freeResult( $res );

	# If this user is an external contributor then remove all rights
	global $wgIPPersonType, $wgIPExternalContributorField, $wgIPExternalContributorCat;
	$query = array( 'type' => $wgIPPersonType, 'record' => $user->getRealname(), 'field' => $wgIPExternalContributorField );
	if ( SpecialRecordAdmin::getFieldValue( $query ) ) $rights = array();

	return true;
}

function wfSetupRAIntegratePerson() {
	global $wgRAIntegratePerson, $wgLanguageCode, $wgMessageCache;

	# Add the messages used by the specialpage
	if ( $wgLanguageCode == 'en' ) {
		$wgMessageCache->addMessages( array(
			'ip-preftab'       => "Person Record",
			'ip-lockedarticle' => "Articles readable by $1",
			'ip-prefmsg'       => "<br /><b>Fill in your Personal details here...</b><br />"
		) );
	}

	# Instantiate the IntegratePerson singleton now that the environment is prepared
	$wgRAIntegratePerson = new RAIntegratePerson();

}
