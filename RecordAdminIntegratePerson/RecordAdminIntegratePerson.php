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

define( 'RAINTEGRATEPERSON_VERSION', '1.4.5, 2010-03-03' );

$wgAutoConfirmCount    = 10^10;
$wgIPDefaultImage      = '';
$wgIPMaxImageSize      = 100000;
$wgIPPersonType        = 'Person';
$wgIPRoleType          = 'Role';
$wgIPRolesField        = 'Roles';
$wgIPParentField       = 'ReportsTo';
$wgIPFixUserLinks      = false;
$wgIPAddPersonalUrls   = true;

$wgExtensionFunctions[] = 'wfSetupRAIntegratePerson';
$wgExtensionCredits['other'][] = array(
	'name'        => 'RecordAdminIntegratePerson',
	'author'      => '[http://www.organicdesign.co.nz/User:Nad User:Nad]',
	'description' => 'Integrates Person records (see RecordAdmin extension) into user preferences and account creation forms',
	'url'         => 'http://www.organicdesign.co.nz/Extension:IntegratePerson',
	'version'     => RAINTEGRATEPERSON_VERSION
);

class RAIntegratePerson {

	var $roles  = array();
	var $groups = array();

	function __construct() {
		global $wgRequest, $wgTitle, $wgHooks, $wgMessageCache, $wgParser, $wgSpecialRecordAdmin, $wgIPAddPersonalUrls, $wgIPFixUserLinks;

		if ( $wgIPAddPersonalUrls ) $wgHooks['PersonalUrls'][] = array( $this, 'addPersonalUrls' );
		if ( $wgIPFixUserLinks )    $wgHooks['BeforePageDisplay'][] = array( $this, 'fixUserLinks');

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

				// Hide fieldsets
				$('fieldset#prefsection-0 fieldset:nth-child(6)').hide(); // internationalisation
				$('fieldset#prefsection-0 fieldset:nth-child(7)').hide(); // signature
				$('fieldset#prefsection-0 fieldset:nth-child(8)').hide(); // email options

				// Defaults for the hidden email options
				$('#mw-input-enotifwatchlistpages').attr('checked','yes');
				$('#mw-input-enotifusertalkpages').attr('checked','yes');
				$('#mw-input-enotifminoredits').attr('checked','');
				$('#wpEmailFlag').attr('checked','');
				$('#mw-input-ccmeonemails').attr('checked','');

				// Hide items in the Basic Information fieldset
				$('table#mw-htmlform-info tr:nth-child(6)').hide(); // real name
				$('table#mw-htmlform-info tr:nth-child(7)').hide(); // real name comment
				$('table#mw-htmlform-info tr:nth-child(8)').hide(); // gender
				$('table#mw-htmlform-info tr:nth-child(9)').hide(); // gender comment
				
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
			"|(<fieldset>\s*<legend>Internationalisation)|s",
			"$form$1",
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
				
				// Hide items in the current form
				$('fieldset#login table tr:nth-child(4)').hide(); // email
				$('fieldset#login table tr:nth-child(5)').hide(); // real name
				$('fieldset#login table tr:nth-child(7)').hide(); // submit buttons
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

		# Return the form minus the Adminstration section
		return preg_replace( "|(^.+)<fieldset.+?Administration.+$|ms", "$1", $wgSpecialRecordAdmin->form );
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
			$name = preg_replace( '%.+(\..+?)$%', "avatar-{$wgSitename}-{$wgUser->getId()}$1", $file['name'] );
			move_uploaded_file( $file['tmp_name'], "$wgUploadDirectory/$name" );
		}
	}

	/**
	 * Build a hash of groups this user belongs to from Role records
	 */
	function initialiseRoles() {
		global $wgUser, $wgIPPersonType, $wgIPRoleType, $wgIPRolesField, $wgIPParentField;
		
		# Build a reverse lookup of roles structure
		$roles = array();
		foreach( SpecialRecordAdmin::getRecordsByType( $wgIPRoleType ) as $t ) {
			$args = SpecialRecordAdmin::getRecordArgs( $t, $wgIPRoleType );
			$role = $t->getText();
			if ( !isset( $roles[$role] ) ) $roles[$role] = array();
			if ( isset( $args[$wgIPParentField] ) ) {
				$parent = $args[$wgIPParentField];
				if ( !isset( $roles[$parent] ) ) $roles[$parent] = array();
				array_push( $roles[$parent], $role );
			}
		}

		# Scan the role structure and make child list contain all descendents
		foreach( $roles as $i => $role ) $roles[$i] = array_unique( array_merge( $roles[$i], self::recursiveRoleScan( $roles, $role ) ) );

		# Loop through this user's roles and assign the user to role-groups
		$query = array( 'type' => $wgIPPersonType, 'record' => $wgUser->getRealname(), 'field' => $wgIPRolesField );
		foreach( preg_split( '/\s*^\s*/', SpecialRecordAdmin::getFieldValue( $query ) ) as $role1 ) {
			if ( isset( $roles[$role1] ) ) {
				self::addGroup( $this->groups, $role1 );
				foreach( $roles[$role1] as $role2 ) self::addGroup( $this->groups, $role2 );
			}
		}
		
		$this->roles = $roles;
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
		static $bail = 100;
		if ( $bail-- == 0) die;
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
	
}

function wfSetupRAIntegratePerson() {
	global $wgRAIntegratePerson, $wgLanguageCode, $wgMessageCache;

	# Add the messages used by the specialpage
	if ( $wgLanguageCode == 'en' ) {
		$wgMessageCache->addMessages( array(
			'ip-preftab'   => "Person Record",
			'ip-prefmsg'   => "<br><b>Fill in your Personal details here...</b><br>"
		) );
	}

	# Instantiate the IntegratePerson singleton now that the environment is prepared
	$wgRAIntegratePerson = new RAIntegratePerson();

}
